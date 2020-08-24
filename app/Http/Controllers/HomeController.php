<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Google\Auth\Credentials\UserRefreshCredentials;
use Google\Photos\Library\V1\PhotosLibraryClient;
use Google\Photos\Library\V1\PhotosLibraryResourceFactory;
use App\GPhoto;
use App\User;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function makeClient($token = null) {
        $client = new \Google_Client();
        $client->setApplicationName('sample001');
        $client->setScopes(\Google_Service_PhotosLibrary::PHOTOSLIBRARY_READONLY);
        $client->setAuthConfig(base_path('resources/credentials.json'));
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');
        $client->setIncludeGrantedScopes(true);
        $client->setRedirectUri('http://localhost:8000/oauthcallback');
        if ($token) {
            $client->setAccessToken($token);
        }
        return $client;
    }

    private function makePhotosCli($client, $token = null, $gpid = null) {
        $refreshToken = $client->getRefreshToken();
        if ($client->isAccessTokenExpired()) {
            if ($refreshToken) {
                $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
                $gphoto = GPhoto::where('id', $gpid)->first();
                $gphoto->access_token = json_encode($newToken);
                $gphoto->save();
            } else {
                return null;
            }
        }
        $authCredentials = new UserRefreshCredentials(
            \Google_Service_PhotosLibrary::PHOTOSLIBRARY_READONLY, [
            'client_id' => $client->getClientId(),
            'client_secret' => $client->getClientSecret(),
            'refresh_token' => $refreshToken ? $refreshToken : $token
        ]);
        return new PhotosLibraryClient(['credentials' => $authCredentials]);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home', [
            'apiToken' => Auth::user()->api_token,
        ]);
    }

    public function setting(Request $req) {
        $id = Auth::user()->id;
        $gphoto = GPhoto::where('id', $id)->first();
        if (!$gphoto) {
            $gphoto = new GPhoto();
            $gphoto->user_id = $id;
            $gphoto->save();
        }
        $token = $req->session()->get('token');
        if ($token) {
            $gphoto->access_token = json_encode($token);
            $gphoto->save();
        }
        $client = $this->makeClient();
        $authUrl = $client->createAuthUrl();
        return view('setting', [
            'album' => $gphoto->album_title,
            'status' => $gphoto->access_token ? 'ok' : 'ng',
            'apiToken' => Auth::user()->api_token,
            'authUrl' => $authUrl
        ]);
    }

    public function callback(Request $req) {
        $code = $req->input('code');
        $client = $this->makeClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);
        $req->session()->put('token', $token);
        return redirect('/home');
    }

    public function save(Request $req) {
        try {
            $id = Auth::user()->id;
            $gphoto = GPhoto::where('id', $id)->first();
            $gphoto->album_title = $req->input('albumTitle');
            $gphoto->save();
            return response('ok');
        } catch (\Throwable $th) { 
            return response('ng');
        }
    }

    public function album(Request $req) {
        $data = [];
        $expired = [];
        $users = User::with(['gphoto'])->get();
        foreach ($users as $user) {
            $title = $user->gphoto->album_title;
            $token = json_decode($user->gphoto->access_token, true);
            if ($token) {
                $client = $this->makeClient($token);
                $cli = $this->makePhotosCli($client, $token['access_token'], $user->gphoto->id);
                if ($cli) {
                    $resp = $cli->listAlbums();
                    $tgtAlbumId = null;
                    foreach ($resp->iterateAllElements() as $item) {
                        if ($item->getTitle() === $title) {
                            $tgtAlbumId = $item->getId();
                        }
                    }
                    if ($tgtAlbumId) {
                        $resp = $cli->searchMediaItems(['albumId' => $tgtAlbumId]);
                        foreach ($resp->iterateAllElements() as $item) {
                            $mediaResp = $cli->getMediaItem($item->getId());
                            $metadata = $item->getMediaMetadata();
                            $data[] = [
                                'id' => $item->getId(),
                                'fileName' => $item->getFilename(),
                                'description' => $item->getDescription(),
                                'baseUrl' => $mediaResp->getBaseUrl(),
                                'height' => $metadata->getHeight(),
                                'width' => $metadata->getWidth(),
                                'date' => $metadata->getCreationTime()
                            ];
                        }
                    }
                } else {
                    $expired[] = $client->createAuthUrl();
                }
            }
        }
        return response()->json([
            'data' => $data,
            'expired' => $expired
        ]);
    }

}

<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\GPhoto;

class ApiController extends Controller {

    public function __construct() {}

    public function save(Request $req) {
        
        return response('ok', 200);
    }

}
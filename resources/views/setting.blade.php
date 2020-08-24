@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">setting</div>
                <div class="card-body">
                    <div class="form-group">
                        <input type="hidden" id="token" value="{{ $apiToken }}">
                        <label>album title</label>
                        <input type="text" id="inAlbumTitle" class="form-control" value="{{ $album }}">
                        <br>
                        <a href="#" id="saveBtn" class="btn btn-primary">save setting</a>
                        <hr>
                        <label>account relation:{{ $status }}</label>
                        <br>
                        <a href="{{ $authUrl }}" class="btn btn-primary">go to OAuth authentication page</a>
                        <hr>
                        <a href="/home" class="btn btn-primary">back to home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
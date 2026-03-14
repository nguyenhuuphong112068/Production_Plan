<?php

use App\Http\Controllers\n8n\Recorder\MeetingController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::get('/meeting/recorder', function () {
    return view('n8n.meeting.recorder');
});

Route::post('/meeting/transcribe', [MeetingController::class, 'transcribe']);

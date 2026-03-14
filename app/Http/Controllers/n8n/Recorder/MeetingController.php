<?php

namespace App\Http\Controllers\n8n\Recorder;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MeetingController extends Controller
{
    public function transcribe(Request $request)
    {
        $file = $request->file('audio');

        $path = $file->store('meeting');

        $response = Http::attach(
            'audio',
            file_get_contents(storage_path('app/' . $path)),
            'chunk.wav'
        )->post('http://127.0.0.1:8000/transcribe');

        return $response->json();
    }
}

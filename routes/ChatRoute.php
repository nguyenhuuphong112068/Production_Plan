<?php

use App\Http\Controllers\General\ChatController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/chat')
    ->name('chat.')
    ->middleware(CheckLogin::class)
    ->controller(ChatController::class)
    ->group(function () {
        Route::get('/groups', 'getGroups')->name('groups');
        Route::get('/messages/{groupId}', 'getMessages')->name('messages');
        Route::post('/send', 'sendMessage')->name('send');
        Route::post('/get-direct-chat', 'getOrCreateDirectChat')->name('getDirectChat');
        Route::get('/users', 'getAllUsers')->name('users');
        Route::post('/create-group', 'createGroupChat')->name('createGroup');
        Route::post('/mark-as-read', 'markAsRead')->name('markAsRead');
        Route::post('/recall', 'recallMessage')->name('recall');
    });

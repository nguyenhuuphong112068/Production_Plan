<?php

use App\Http\Middleware\CheckLogin;
use App\Plugins\RoomWip\Http\RoomWipController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', CheckLogin::class])
    ->prefix('/Schedual/room-wip')
    ->name('plugins.room_wip.')
    ->controller(RoomWipController::class)
    ->group(function () {
        Route::get('access', 'access')->name('access');
        Route::get('data', 'data')->name('data');
    });

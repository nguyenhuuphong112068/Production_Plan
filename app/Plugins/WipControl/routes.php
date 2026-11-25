<?php

use App\Http\Middleware\CheckLogin;
use App\Http\Middleware\RestrictWeighingScheduler;
use App\Plugins\WipControl\Http\WipControlController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', CheckLogin::class, RestrictWeighingScheduler::class])
    ->prefix('/Schedual/wip-control')
    ->name('plugins.wip_control.')
    ->controller(WipControlController::class)
    ->group(function () {
        Route::get('settings', 'settings')->name('settings');
        Route::post('run', 'run')->name('run');
    });

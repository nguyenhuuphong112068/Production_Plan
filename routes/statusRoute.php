<?php

use App\Http\Controllers\Pages\Status\StatusController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

    Route::prefix('/status')
    ->controller(StatusController::class)
    ->name('pages.status.')
    ->group(function(){
        
            Route::get('','show');
            Route::get('/next','next')->name('next');
            Route::get('index','index')->name('index');
            Route::post('store','store')->name('store');
            Route::post('getLastStatusRoom','getLastStatusRoom')->name('getLastStatusRoom');
    });
    

?>
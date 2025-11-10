<?php

use App\Http\Controllers\Pages\Status\StatusController;
use App\Http\Controllers\Pages\Status\StatusHPLCController;
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
            Route::post('store_general_notification','store_general_notification')->name('store_general_notification');
    });

    Route::prefix('/status_HPLC')
    ->controller(StatusHPLCController::class)
    ->name('pages.status_HPLC.')
    ->group(function(){
        
            Route::get('','show');
            Route::get('/next','next')->name('next');
            Route::get('index','index')->name('index');
            Route::post('store','store')->name('store');
            Route::post('getLastStatusRoom','getLastStatusRoom')->name('getLastStatusRoom');
            Route::post('store_general_notification','store_general_notification')->name('store_general_notification');
    });
    

?>
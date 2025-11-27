<?php

use App\Http\Controllers\Pages\Status\StatusController;
use App\Http\Controllers\Pages\Status\StatusHistoryController;
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
            Route::post('getQuota','getQuota')->name('getQuota');
    });

     Route::prefix('/status/history')
        ->controller(StatusHistoryController::class)
        ->name('pages.status.history.')
        ->group(function(){
        
            Route::get('','index')->name('index');
            Route::get('/next','next')->name('next');
            Route::get('show','show')->name('show');
            Route::post('update','update')->name('update');
            Route::post('deActive','deActive')->name('deActive');
            
    });



    Route::prefix('/status_HPLC')
    ->controller(StatusHPLCController::class)
    ->name('pages.status_HPLC.')
    ->group(function(){
            Route::get('','show');
            Route::POST('import','import')->name('import');
    });
    

?>
<?php

use App\Http\Controllers\Pages\Status\StatusController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

    Route::prefix('/status')
    ->controller(StatusController::class)
    ->name('pages.status.')
    ->group(function(){
        
            Route::get('','index')->name('list');
            Route::get('/next','next')->name('next');
    });
    

?>
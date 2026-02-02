<?php

use App\Http\Controllers\Pages\Assignment\ABCDController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

    

    Route::prefix('/assignemnt/production')
        ->controller(ABCDController::class)
        ->name('pages.assignment.production.')
        ->middleware(CheckLogin::class)
        ->group(function(){
        
            Route::get('','index')->name('index');
            Route::post('view', 'view')->name('view');

   

      
    });



      


?>
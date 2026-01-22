<?php

use App\Http\Controllers\Pages\Assignment\ABCDController;
use Illuminate\Support\Facades\Route;


    Route::prefix('/assignemnt/production')
        ->controller(ABCDController::class)
        ->name('pages.assignment.production.')
        ->group(function(){
        
            Route::get('','index')->name('index');
            // Route::post('detail','detail')->name('detail');
            // Route::post('explain','explain')->name('explain');
            // Route::post('getExplainationContent','getExplainationContent')->name('getExplainationContent');
            // Route::post('store','store')->name('store');
            // Route::post('update','update')->name('update');
            // Route::post('deActive','deActive')->name('deActive');
        
      
    });


?>
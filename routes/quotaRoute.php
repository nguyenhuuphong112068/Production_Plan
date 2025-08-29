<?php

use App\Http\Controllers\Pages\Quota\MaintenanceQuotaController;
use App\Http\Controllers\Pages\Quota\ProductionQuotaController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;



Route::prefix('/quota')
->name('pages.quota.')
->middleware(CheckLogin::class)
->group(function(){

    Route::prefix('/production')
    ->name('production.')
    ->controller(ProductionQuotaController::class)
    ->group(function(){
            Route::get('','index')->name('list');
            Route::match(['post', 'put'], 'store', 'store');
            //Route::post('store','store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive/{id}','deActive')->name('deActive');
            
    });

    Route::prefix('/maintenance')
    ->name('maintenance.')
    ->controller(MaintenanceQuotaController::class)
    ->group(function(){
            Route::get('','index')->name('list');
            Route::post('store','store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive/{id}','deActive')->name('deActive'); 
    });


});
   

?>
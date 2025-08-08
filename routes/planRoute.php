<?php
    // use Illuminate\Routing\Route;

use App\Http\Controllers\Pages\Plan\MaintenancePlanController;
use App\Http\Controllers\Pages\Plan\ProductionPlanController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;



Route::prefix('/plan')
->name('pages.plan.')
->middleware(CheckLogin::class)
->group(function(){

    Route::prefix('/production')
    ->name('production.')
    ->controller(ProductionPlanController::class)
    ->group(function(){
            Route::get('','index')->name('list');
            Route::post('open','open')->name('open');
            Route::post('store','store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive/{id}','deActive')->name('deActive');
            Route::post('send','send')->name('send');
    });

    Route::prefix('/maintenance')
    ->name('maintenance.')
    ->controller(MaintenancePlanController::class)
    ->group(function(){
            Route::get('','index')->name('list');
            Route::post('store','store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive/{id}','deActive')->name('deActive'); 
    });







     

});
   

?>
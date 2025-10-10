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
            // các hàm xử lý plan_list
            Route::post('create_plan_list','create_plan_list')->name('create_plan_list');


            // các hàm xử lý plan_master
            Route::get('open','open')->name('open');
            Route::post('store','store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive','deActive')->name('deActive');
            Route::post('send','send')->name('send');
            Route::post('history','history')->name('history');
            Route::post('source_material','source_material')->name('source_material');
            Route::post('store_source','store_source')->name('store_source');

            Route::post('updateInput','updateInput')->name('updateInput');
            Route::post('first_batch','first_batch')->name('first_batch');
            Route::post('get_last_id','get_last_id')->name('get_last_id');
    });

    Route::prefix('/maintenance')
    ->name('maintenance.')
    ->controller(MaintenancePlanController::class)
    ->group(function(){
            Route::get('','index')->name('list');
            // các hàm xử lý plan_list
            Route::post('create_plan_list','create_plan_list')->name('create_plan_list');


            // các hàm xử lý plan_master
            Route::get('open','open')->name('open');
            Route::post('store','store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive','deActive')->name('deActive');
            Route::post('send','send')->name('send');
            Route::post('history','history')->name('history');
            Route::post('source_material','source_material')->name('source_material');
    
    });







     

});
   

?>
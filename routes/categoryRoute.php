<?php
    // use Illuminate\Routing\Route;

use App\Http\Controllers\Pages\Category\IntermediateCategoryController;
use App\Http\Controllers\Pages\Category\MaintenanceCategoryController;
use App\Http\Controllers\Pages\Category\ProductCategoryController;

use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;



Route::prefix('/category')
->name('pages.category.')
->middleware(CheckLogin::class)
->group(function(){

    Route::prefix('/intermediate')
    ->name('intermediate.')
    ->controller(IntermediateCategoryController::class)
    ->group(function(){
            Route::get('','index')->name('list');
            Route::post('store','store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive','deActive')->name('deActive'); 
    });


    Route::prefix('/product')
    ->name('product.')
    ->controller(ProductCategoryController::class)
    ->group(function(){
            Route::get('','index')->name('list');
            Route::post('store','store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive','deActive')->name('deActive'); 

            Route::post('getJsonFPCategogy','getJsonFPCategogy')->name('getJsonFPCategogy'); 
    });


    Route::prefix('/maintenance')
    ->name('maintenance.')
    ->controller(MaintenanceCategoryController::class)
    ->group(function(){
            Route::get('','index')->name('list');
            Route::post('store','store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive','deActive')->name('deActive'); 
            Route::post('check_code_room_id','check_code_room_id')->name('check_code_room_id'); 

            
    });

});
   

?>
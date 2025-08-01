<?php
    // use Illuminate\Routing\Route;

use App\Http\Controllers\Pages\Category\ProductCategoryController;

use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;



Route::prefix('/category')
->name('pages.category.')
->middleware(CheckLogin::class)
->group(function(){

    Route::prefix('/product')
    ->name('product.')
    ->controller(ProductCategoryController::class)
    ->group(function(){
            Route::get('','index')->name('list');
            Route::post('store','store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive/{id}','deActive')->name('deActive'); 
    });







     

});
   

?>
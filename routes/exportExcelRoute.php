<?php

use App\Http\Controllers\Pages\Category\ProductCategoryController;

use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;


Route::prefix('/exportExcel')
->name('pages.exportExcel.')
->group(function(){

    Route::prefix('/FP')
    ->name('FP.')
    ->controller(ProductCategoryController::class)
    ->group(function(){
            Route::get('getJsonFPCategory','getJsonFPCategory')->name('getJsonFPCategory'); 
    });


 

});
   

?>
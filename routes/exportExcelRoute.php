<?php

use App\Http\Controllers\Pages\Category\ProductCategoryController;
use App\Http\Controllers\Pages\Plan\ProductionPlanController;
use Illuminate\Support\Facades\Route;

///exportExcel/Plan_feekback/open_feedback_API
Route::prefix('/exportExcel')
->name('pages.exportExcel.')
->group(function(){

    Route::prefix('/FP')
    ->name('FP.')
    ->controller(ProductCategoryController::class)
    ->group(function(){
            Route::get('getJsonFPCategory','getJsonFPCategory')->name('getJsonFPCategory'); 
    });

     Route::prefix('/Plan_feekback')
    ->name('Plan_feekback.')
    ->controller(ProductionPlanController::class)
    ->group(function(){
            Route::get('open_feedback_API','open_feedback_API')->name('open_feedback_API'); 
    });


 

});
   

?>
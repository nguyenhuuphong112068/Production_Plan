<?php
    // use Illuminate\Routing\Route;

use App\Http\Controllers\Pages\MMS\FinishedProductController;
use App\Http\Controllers\Pages\MMS\MaterialController;
use App\Http\Controllers\Pages\MMS\PackagingController;
use App\Http\Controllers\Pages\MMS\BOMController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/MMS')
->name('pages.MMS.')
->middleware(CheckLogin::class)
->group(function(){

        Route::prefix('/material')
        ->name('material.')
        ->controller(MaterialController::class)
        ->group(function(){
                Route::get('','index')->name('list');
        
        });

        Route::prefix('/packaging')
        ->name('packaging.')
        ->controller(PackagingController::class)
        ->group(function(){
                Route::get('','index')->name('list');
        
        });

        Route::prefix('/finished_product')
        ->name('finished_product.')
        ->controller(FinishedProductController::class)
        ->group(function(){
                Route::get('','index')->name('list');
        
        });

  





       

});
   

?>
<?php
    // use Illuminate\Routing\Route;

use App\Http\Controllers\Pages\MaterData\AnalystController;
use App\Http\Controllers\Pages\MaterData\GroupsController;
use App\Http\Controllers\Pages\MaterData\InstrumentController;
use App\Http\Controllers\Pages\MaterData\ProductNameController;
use App\Http\Controllers\Pages\MaterData\TestingController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;
   
Route::prefix('/materData')
->name('pages.materData.')
->middleware(CheckLogin::class)
->group(function(){

    Route::prefix('/productName')
    ->name('productName.')
    ->controller(ProductNameController::class)
    ->group(function(){
            Route::get('','index')->name('list');
            Route::post('store','store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive/{id}','deActive')->name('deActive'); 
    });

    Route::prefix('/Testing')
    ->name('Testing.')
    ->controller(TestingController::class)
    ->group(function(){
            Route::get('','index')->name('list');
            Route::post('store','store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive/{id}','deActive')->name('deActive');     
    });

    Route::prefix('/Instrument')
    ->name('Instrument.')
    ->controller(InstrumentController::class)
    ->group(function(){
            Route::get('','index')->name('list');
            Route::post('store','store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive/{id}','deActive')->name('deActive');          
    });

    Route::prefix('/Groups')
    ->name('Groups.')
    ->controller(GroupsController::class)
    ->group(function(){
            Route::get('','index')->name('list');
            Route::post('store','store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive/{id}','deActive')->name('deActive');          
    });

    Route::prefix('/Analyst')
    ->name('Analyst.')
    ->controller(AnalystController::class)
    ->group(function(){
            Route::get('','index')->name('list');
            Route::post('store','store')->name('store');
            Route::post('update', 'update')->name('update');
            Route::post('deActive/{id}','deActive')->name('deActive');          
    });
     

});
   

?>
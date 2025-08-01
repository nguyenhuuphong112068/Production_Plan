<?php
    // use Illuminate\Routing\Route;

use App\Http\Controllers\Pages\Import\ImportController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;
   
Route::prefix('/Import')
->controller(ImportController::class)
->name('pages.Import.')
->middleware(CheckLogin::class)
->group(function(){

        Route::get('','index')->name('list');
        Route::post('store','store')->name('store');
        Route::post('update', 'update')->name('update');
        Route::post('deActive/{id}','deActive')->name('deActive'); 
    
});
   

?>
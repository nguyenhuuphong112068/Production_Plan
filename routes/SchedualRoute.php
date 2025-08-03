<?php
    // use Illuminate\Routing\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Pages\Schedual\SchedualController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;
   
Route::prefix('/Schedual')
->controller(SchedualController::class)
->name('pages.Schedual.')
->middleware(CheckLogin::class)
->group(function(){

        Route::get('view','view');
        Route::get('','index')->name('list');
        
    
        Route::put('finished','finished')->name('finished'); 

        Route::put('deActive/{id}','deActive')->name('deActive'); 
        Route::put('store','store')->name('store');
        Route::put('update', 'update')->name('update');
});
   

?>
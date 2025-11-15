<?php

use App\Http\Controllers\Pages\Quarantine\QuarantineRoomController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;



Route::prefix('/quarantine')
->name('pages.quarantine.')
->middleware(CheckLogin::class)
->controller(QuarantineRoomController::class)
->group(function(){

    Route::prefix('/theory')
    ->name('theory.')
   
    ->group(function(){
            Route::get('','index')->name('list');           
    });

    Route::prefix('/actual')
    ->name('actual.')
    ->group(function(){
            Route::get('','index_actual')->name('index_actual'); 
            Route::post('detail','detail')->name('detail');          
    });


});
   

?>
<?php

use App\Http\Controllers\Pages\Quarantine\QuarantineRoomController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;



Route::prefix('/quarantine')
->name('pages.quarantine.')
->middleware(CheckLogin::class)
->group(function(){

    Route::prefix('/room')
    ->name('room.')
    ->controller(QuarantineRoomController::class)
    ->group(function(){
            Route::get('','index')->name('list');
            
            // Route::post('store','store')->name('store');
            // Route::post('update', 'update')->name('update');
            // Route::post('deActive','deActive')->name('deActive');
            // Route::post('check_code_room_id','check_code_room_id')->name('check_code_room_id'); 
            // Route::post('tank_keepDry','tank_keepDry')->name('tank_keepDry');
            // Route::post('updateTime','updateTime')->name('updateTime');
            
    });


});
   

?>
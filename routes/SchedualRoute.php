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
        Route::put('deActive','deActive')->name('deActive');
        Route::put('deActiveAll','deActiveAll')->name('deActiveAll');
        Route::put('store','store')->name('store');
        Route::put('multiStore','multiStore')->name('multiStore');
        Route::put('update', 'update')->name('update');
        Route::put('addEventContent/{id}', 'addEventContent')->name('addEventContent');

        // Sắp Thứ Tự Trong Bảng KH CD
        Route::put('updateOrder', 'updateOrder')->name('updateOrder');

        // Sắp Lịch Tư Động //
        Route::put('scheduleAll', 'scheduleAll')->name('scheduleAll');
        //Route::get('/schedule/stage/{stageCode}','scheduleStage')->name('scheduleStage');

});
   

?>
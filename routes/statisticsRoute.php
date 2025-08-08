<?php
    // use Illuminate\Routing\Route;
use App\Http\Controllers\Pages\Statistics\StatisticProductController;
use App\Http\Controllers\Pages\Statistics\StatisticRoomController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;



Route::prefix('/statistics')
->name('pages.statistics.')
->middleware(CheckLogin::class)
->group(function(){

    Route::prefix('/product')
    ->name('product.')
    ->controller(StatisticProductController::class)
    ->group(function(){
            Route::get('','index')->name('list');
    });

    Route::prefix('/room')
    ->name('room.')
    ->controller(StatisticRoomController::class)
    ->group(function(){
            Route::get('','index')->name('list');
    });
     

});
   

?>
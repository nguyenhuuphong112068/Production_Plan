<?php
    // use Illuminate\Routing\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Pages\Schedual\SchedualController;
use App\Http\Controllers\Pages\Schedual\SchedualStepController;
use App\Http\Controllers\Pages\Schedual\SchedualViewController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;
   
        Route::prefix('/Schedual')
        ->controller(SchedualController::class)
        ->name('pages.Schedual.')
        ->middleware(CheckLogin::class)
        ->group(function(){

                Route::match(['get', 'put'], 'view', 'view');
                Route::put('finished','finished')->name('finished'); 
                Route::put('deActive','deActive')->name('deActive');
                Route::put('deActiveAll','deActiveAll')->name('deActiveAll');
                Route::put('store','store')->name('store');
                Route::put('multiStore','multiStore')->name('multiStore');
                Route::put('update', 'update')->name('update');
                Route::put('addEventContent/{id}', 'addEventContent')->name('addEventContent');
                Route::put('update', 'update')->name('update');

                // Sắp Thứ Tự Trong Bảng KH CD
                Route::put('updateOrder', 'updateOrder')->name('updateOrder');
                // Tạo Mã Chiến Dịch
                Route::put('createManualCampain', 'createManualCampain')->name('createManualCampain');
                Route::put('createAutoCampain', 'createAutoCampain')->name('createAutoCampain');
                Route::put('createOrderPlan', 'createOrderPlan')->name('createOrderPlan');

                // Sắp Lịch Tư Động //
                Route::put('scheduleAll', 'scheduleAll')->name('scheduleAll');

               
        });


        Route::prefix('/Schedual')
        ->name('pages.Schedual.')
        ->middleware(CheckLogin::class)
        ->group(function(){

                Route::prefix('/list')
                ->controller(SchedualViewController::class)
                ->name('list.')
                ->group(function(){
                        Route::get('','list')->name('list');
                });

                Route::prefix('/step')
                ->controller(SchedualStepController::class)
                ->name('step.')
                ->group(function(){
                        Route::get('','list')->name('list');
                });       

        });
   
   

?>
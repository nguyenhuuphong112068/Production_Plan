<?php
    // use Illuminate\Routing\Route;

use App\Http\Controllers\Pages\Schedual\SchedualAuditController;
use Illuminate\Http\Request;
use App\Http\Controllers\Pages\Schedual\SchedualController;
use App\Http\Controllers\Pages\Schedual\SchedualFinisedController;
use App\Http\Controllers\Pages\Schedual\SchedualReportController;
use App\Http\Controllers\Pages\Schedual\SchedualStepController;
use App\Http\Controllers\Pages\Schedual\SchedualViewController;
use App\Http\Controllers\Pages\Schedual\ShedualYieldController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;
   
        Route::prefix('/Schedual')
        ->controller(SchedualController::class)
        ->name('pages.Schedual.')
        ->middleware(CheckLogin::class)
        ->group(function(){
                Route::get('','index')->name('index'); 
                Route::post('view', 'view')->name('view');
                Route::put('finished','finished')->name('finished'); 
                Route::put('deActive','deActive')->name('deActive');
                Route::put('deActiveAll','deActiveAll')->name('deActiveAll');
                Route::put('store','store')->name('store');
                Route::put('store_maintenance','store_maintenance')->name('store_maintenance');
                Route::put('multiStore','multiStore')->name('multiStore');
                Route::put('update', 'update')->name('update');
                Route::put('addEventContent/{id}', 'addEventContent')->name('addEventContent');
                Route::put('update', 'update')->name('update');
                Route::post('getSumaryData', 'getSumaryData')->name('getSumaryData');

                // Sắp Thứ Tự Trong Bảng KH CD
                Route::put('updateOrder', 'updateOrder')->name('updateOrder');
                // Tạo Mã Chiến Dịch
                Route::put('createManualCampain', 'createManualCampain')->name('createManualCampain');
                Route::put('createAutoCampain', 'createAutoCampain')->name('createAutoCampain');
                Route::put('createOrderPlan', 'createOrderPlan')->name('createOrderPlan');
                Route::put('DeActiveOrderPlan', 'DeActiveOrderPlan')->name('DeActiveOrderPlan');

                // Sắp Lịch Tư Động //
                Route::post('scheduleAll', 'scheduleAll')->name('scheduleAll');
                Route::put('getInforSoure','getInforSoure')->name('getInforSoure');
                Route::put('confirm_source','confirm_source')->name('confirm_source');
                Route::put('history','history')->name('history');
                Route::put('Sorted','Sorted')->name('Sorted');
                Route::put('submit','submit')->name('submit');
                Route::put('required_room','required_room')->name('required_room');

                
                Route::get('test','test')->name('test');

                
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
                
                
                Route::prefix('/report')
                ->controller(SchedualReportController::class)
                ->name('report.')
                ->group(function(){
                        Route::get('','list')->name('list');
                        Route::get('test','test')->name('test');
                });

                Route::prefix('/yield')
                ->controller(ShedualYieldController::class)
                ->name('yield.')
                ->group(function(){
                        Route::get('','index')->name('index');
                        
                });  

                Route::prefix('/audit')
                ->controller(SchedualAuditController::class)
                ->name('audit.')
                ->group(function(){
                        Route::get('','index')->name('index');
                        Route::post('history','history')->name('history');
                        
                });
                
                Route::prefix('/finised')
                ->controller(SchedualFinisedController::class)
                ->name('finised.')
                ->group(function(){
                        Route::get('','index')->name('index');
                        Route::post('store','store')->name('store');
                        
                });  


        });

   

?>
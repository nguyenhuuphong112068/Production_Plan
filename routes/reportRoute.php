<?php

use App\Http\Controllers\Pages\Report\DailyReportController;
use App\Http\Controllers\Pages\Report\MonthlyReportController;
use App\Http\Controllers\Pages\Report\WeeklyReportController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;


    Route::prefix('/report/daily_report')
        ->controller(DailyReportController::class)
        ->name('pages.report.daily_report.')
        ->group(function(){
        
            Route::get('','index')->name('index');
            Route::post('detail','detail')->name('detail');
            Route::post('explain','explain')->name('explain');
            Route::post('getExplainationContent','getExplainationContent')->name('getExplainationContent');
            Route::post('store','store')->name('store');
            Route::post('update','update')->name('update');
            Route::post('deActive','deActive')->name('deActive');
        
      
    });

    Route::prefix('/report/weekly_report')
        ->controller(WeeklyReportController::class)
        ->name('pages.report.weekly_report.')
        ->group(function(){
            Route::get('','index')->name('index');
            Route::post('updateInput','updateInput')->name('updateInput');
    });

    Route::prefix('/report/monthly_report')
        ->controller(MonthlyReportController::class)
        ->name('pages.report.monthly_report.')
        ->group(function(){
            Route::get('','index')->name('index');
            Route::post('updateInput','updateInput')->name('updateInput');
        
    });


?>
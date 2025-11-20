<?php

use App\Http\Controllers\Pages\Report\DailyReportController;
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
        
      
    });


?>
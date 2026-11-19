<?php

use App\Http\Controllers\Api\ExcelReportApiController;
use Illuminate\Support\Facades\Route;

// API cho file Excel (WEBSERVICE) — không qua CheckLogin, xác thực bằng ?key= (PM_EXCEL_API_KEY)
Route::prefix('/api/excel')
    ->controller(ExcelReportApiController::class)
    ->name('api.excel.')
    ->group(function () {
        Route::get('productions', 'productions')->name('productions');
        Route::get('actual-batches', 'actualBatches')->name('actual_batches');
    });

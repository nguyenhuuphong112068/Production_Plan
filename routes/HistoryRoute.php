<?php
    // use Illuminate\Routing\Route;

use App\Http\Controllers\Pages\History\ProductionHistoryController;
use App\Http\Controllers\Pages\History\MaintenanceHistoryController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/History')
    ->middleware(CheckLogin::class)
    ->group(function () {
        Route::get('', [ProductionHistoryController::class, 'index'])->name('pages.History.list');
        Route::get('/production', [ProductionHistoryController::class, 'index'])->name('pages.History.production.list');
        Route::get('/maintenance', [MaintenanceHistoryController::class, 'index'])->name('pages.History.maintenance.list');
        
        Route::post('production/returnStage', [ProductionHistoryController::class, 'returnStage'])->name('pages.History.production.returnStage');
        Route::post('maintenance/returnStage', [MaintenanceHistoryController::class, 'returnStage'])->name('pages.History.maintenance.returnStage');
        Route::post('returnStage', [ProductionHistoryController::class, 'returnStage'])->name('pages.History.returnStage');
    });
   

?>
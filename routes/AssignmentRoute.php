<?php

use App\Http\Controllers\Pages\Assignment\MaintenanceAssignmentController;
use App\Http\Controllers\Pages\Assignment\ProductionAssignmentController;
use App\Http\Controllers\Pages\Assignment\PersonnelController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;



Route::get('/public-assignments', [MaintenanceAssignmentController::class, 'publicView'])->name('pages.assignment.public');
Route::get('/public-production-assignments', [ProductionAssignmentController::class, 'publicView'])->name('pages.assignment.production.public');

Route::prefix('/assignemnt/production')
    ->controller(ProductionAssignmentController::class)
    ->name('pages.assignment.production.')
    ->middleware(CheckLogin::class)
    ->group(function () {
        Route::get('', 'index')->name('index');
        Route::post('store', 'store')->name('store');
        Route::delete('destroy/{id}', 'destroy')->name('destroy');
    });

Route::prefix('/assignemnt/maintenance')
    ->controller(MaintenanceAssignmentController::class)
    ->name('pages.assignment.maintenance.')
    ->middleware(CheckLogin::class)
    ->group(function () {
        Route::get('/portal', 'portal')->name('portal');
        Route::get('', 'index')->name('index');
        // Route cho các tác vụ khác nếu cần
        Route::post('store', 'store')->name('store');
        Route::delete('destroy/{id}', 'destroy')->name('destroy');
    });

Route::prefix('/assignemnt/personnel')
    ->controller(PersonnelController::class)
    ->name('pages.assignment.personnel.')
    ->middleware(CheckLogin::class)
    ->group(function () {
        Route::get('/{department?}', 'index')->name('list');
        Route::post('store', 'store')->name('store');
        Route::post('update', 'update')->name('update');
        Route::get('deActive/{id}', 'deActive')->name('deActive');
    });

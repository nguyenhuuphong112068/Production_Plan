<?php

use App\Http\Controllers\Pages\Assignment\MaintenanceAssignmentController;
use App\Http\Controllers\Pages\Assignment\ProductionAssignmentController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;



Route::get('/public-assignments', [MaintenanceAssignmentController::class, 'publicView'])->name('pages.assignment.public');
Route::get('/public-production-assignments', [ProductionAssignmentController::class, 'publicView'])->name('pages.assignment.production.public');
Route::get('/public-production-assignments/shifts', [ProductionAssignmentController::class, 'getPersonnelShifts'])->name('pages.assignment.production.public.shifts');
Route::get('/public-assignments/shifts', [MaintenanceAssignmentController::class, 'getPersonnelShifts'])->name('pages.assignment.public.shifts');

Route::prefix('/assignemnt/production')
    ->controller(ProductionAssignmentController::class)
    ->name('pages.assignment.production.')
    ->middleware(CheckLogin::class)
    ->group(function () {
        Route::get('', 'index')->name('index');
        Route::post('store', 'store')->name('store');
        Route::get('shifts', 'getPersonnelShifts')->name('shifts');
        Route::post('update-has-assignment', 'updateHasAssignment')->name('update_has_assignment');
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
        Route::get('shifts', 'getPersonnelShifts')->name('shifts');
        Route::post('update-has-assignment', 'updateHasAssignment')->name('update_has_assignment');
        Route::post('store', 'store')->name('store');
        Route::delete('destroy/{id}', 'destroy')->name('destroy');
    });

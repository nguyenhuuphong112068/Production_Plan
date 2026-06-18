<?php

use App\Http\Controllers\Pages\Assignment\MaintenanceAssignmentController;
use App\Http\Controllers\Pages\Assignment\ProductionAssignmentController;
use App\Http\Controllers\Pages\Assignment\DashBoardController;
use App\Http\Controllers\Pages\Assignment\OvertimePolicyController;
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
        Route::post('clone-custom-task', 'cloneCustomTask')->name('clone_custom_task');
        Route::get('shifts', 'getPersonnelShifts')->name('shifts');
        Route::post('approve-overtime', 'approveOvertime')->name('approve_overtime');
        Route::post('update-personnel-time', 'updatePersonnelTime')->name('update_personnel_time');
        Route::post('update-has-assignment', 'updateHasAssignment')->name('update_has_assignment');
        Route::delete('destroy/{id}', 'destroy')->name('destroy');

        Route::get('portal', 'portal')->name('portal');
        Route::get('chart', 'chartIndex')->name('chart');
        Route::post('chart/view', 'chartView')->name('chart_view');
        Route::put('chart/store', 'chartStore')->name('chart_store');
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

Route::prefix('/assignemnt/dashboard')
    ->controller(DashBoardController::class)
    ->name('pages.assignment.dashboard.')
    ->middleware(CheckLogin::class)
    ->group(function () {
        Route::get('', 'index')->name('index');
        Route::get('data', 'getData')->name('data');
    });

Route::prefix('/assignemnt/overtime-policy')
    ->controller(OvertimePolicyController::class)
    ->name('pages.assignment.overtime_policy.')
    ->middleware(CheckLogin::class)
    ->group(function () {
        Route::get('', 'index')->name('index');
        Route::post('store', 'store')->name('store');
        Route::get('history', 'history')->name('history');
    });

<?php

use App\Http\Controllers\Pages\Quota\MoldController;
use App\Http\Controllers\Pages\Quota\ProductionQuotaController;
use App\Http\Controllers\Pages\Quota\PersonnelController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

Route::prefix('/quota')
    ->name('pages.quota.')
    ->middleware(CheckLogin::class)
    ->group(function () {

        Route::prefix('/personnel')
            ->controller(PersonnelController::class)
            ->name('personnel.')
            ->group(function () {
                Route::get('/{department?}', 'index')->name('list');
                Route::get('sync/{department?}', 'sync')->name('sync');
                Route::post('store', 'store')->name('store');
                Route::post('update', 'update')->name('update');
                Route::post('update-permissions', 'updatePermissions')->name('updatePermissions');
                Route::post('update-productions', 'updateProductions')->name('updateProductions');

                Route::get('deActive/{id}', 'deActive')->name('deActive');
            });

        Route::prefix('/production')
            ->name('production.')
            ->controller(ProductionQuotaController::class)
            ->group(function () {
                Route::get('', 'index')->name('list');
                Route::match(['post', 'put'], 'store', 'store');
                Route::post('store', 'store')->name('store');
                Route::post('update', 'update')->name('update');
                Route::post('deActive', 'deActive')->name('deActive');
                Route::post('check_code_room_id', 'check_code_room_id')->name('check_code_room_id');
                Route::post('tank_keepDry', 'tank_keepDry')->name('tank_keepDry');
                Route::post('updateTime', 'updateTime')->name('updateTime');

            });

        Route::prefix('/mold')
            ->name('mold.')
            ->controller(MoldController::class)
            ->group(function () {
                Route::get('', 'index')->name('list');
                Route::post('update', 'update')->name('update');
            });
    });

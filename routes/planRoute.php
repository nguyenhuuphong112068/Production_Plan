<?php
// use Illuminate\Routing\Route;

use App\Http\Controllers\Pages\Plan\MaintenancePlanController;
use App\Http\Controllers\Pages\Plan\ProductionPlanController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;



Route::prefix('/plan')
    ->name('pages.plan.')
    ->middleware(CheckLogin::class)
    ->group(function () {

        Route::prefix('/production')
            ->name('production.')
            ->controller(ProductionPlanController::class)
            ->group(function () {
                Route::get('', 'index')->name('list');
                // các hàm xử lý plan_list
                Route::post('create_plan_list', 'create_plan_list')->name('create_plan_list');

                // các hàm xử lý plan_master
                Route::get('search_all', 'search_all')->name('search_all');
                Route::get('open', 'open')->name('open');
                Route::get('open_stock', 'open_stock')->name('open_stock');
                Route::post('backup_stock', 'backup_stock')->name('backup_stock');
                Route::post('open_bacth_detail', 'open_bacth_detail')->name('open_bacth_detail');
                Route::post('store', 'store')->name('store');
                Route::post('update', 'update')->name('update');
                Route::post('splitting', 'splitting')->name('splitting');
                Route::post('splittingUpdate', 'splittingUpdate')->name('splittingUpdate');
                Route::post('deActive', 'deActive')->name('deActive');
                Route::post('send', 'send')->name('send');
                Route::post('history', 'history')->name('history');
                Route::post('source_material', 'source_material')->name('source_material');
                Route::post('store_source', 'store_source')->name('store_source');

                Route::post('updateInput', 'updateInput')->name('updateInput');
                Route::post('first_batch', 'first_batch')->name('first_batch');
                Route::post('get_last_id', 'get_last_id')->name('get_last_id');

                Route::get('feedback_list', 'feedback_list')->name('feedback_list');
                Route::get('get_waiting_plans', 'getWaitingPlans')->name('get_waiting_plans');
                Route::get('get_batches_by_status', 'getBatchesByStatus')->name('get_batches_by_status');
                Route::get('open_feedback', 'open_feedback')->name('open_feedback');
                Route::post('accept_expected_date', 'accept_expected_date')->name('accept_expected_date');
                Route::post('all_feedback', 'all_feedback')->name('all_feedback');
                Route::post('order', 'order')->name('order');
                Route::post('recipe_show_update', 'recipe_show_update')->name('recipe_show_update');


                Route::get('update_plan_master_material', 'update_plan_master_material')->name('update_plan_master_material');
                Route::get('equipment_allocation/{id}', 'getEquipmentAllocation')->name('equipment_allocation');
            });

        Route::prefix('/validation_tracking')
            ->name('validation_tracking.')
            ->controller(App\Http\Controllers\Pages\Plan\ValidationTrackingController::class)
            ->group(function () {
                Route::get('', 'index')->name('list');
                Route::post('store', 'store')->name('store');
                Route::post('update', 'update')->name('update');
                Route::post('approve', 'approve')->name('approve');
                Route::get('check_validation', 'checkValidation')->name('check_validation');
                Route::get('get_plan_masters/{tracking_id}', 'getPlanMasters')->name('get_plan_masters');
            });



        Route::prefix('/maintenance')
            ->name('maintenance.')
            ->controller(MaintenancePlanController::class)
            ->group(function () {
                Route::get('', 'index')->name('list');
                // các hàm xử lý plan_list
                Route::post('create_plan_list', 'create_plan_list')->name('create_plan_list');
                Route::post('auto_create_plan', 'autoCreatePlan')->name('auto_create_plan');


                // các hàm xử lý plan_master
                Route::get('open', 'open')->name('open');
                Route::post('store', 'store')->name('store');
                Route::post('update', 'update')->name('update');
                Route::post('deActive', 'deActive')->name('deActive');
                Route::post('send', 'send')->name('send');
                Route::post('history', 'history')->name('history');
                Route::post('source_material', 'source_material')->name('source_material');
            });

        Route::prefix('/annual')
            ->name('annual.')
            ->controller(\App\Http\Controllers\Pages\Plan\AnnualPlanController::class)
            ->group(function () {
                Route::get('', 'index')->name('list');
                Route::get('/{id}', 'show')->name('show');
                Route::get('/{id}/unassigned-products', 'unassignedProducts')->name('unassigned_products');
                Route::post('', 'store')->name('store');
                Route::post('/{id}/add-products', 'addProducts')->name('add_products');
                Route::post('update-monthly-data', 'updateMonthlyData')->name('update_monthly_data');
            });
    });

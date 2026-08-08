<?php
// use Illuminate\Routing\Route;

use App\Http\Controllers\Pages\Category\IntermediateCategoryController;
use App\Http\Controllers\Pages\Category\MaintenanceCategoryController;
use App\Http\Controllers\Pages\Category\MaterialCheckController;
use App\Http\Controllers\Pages\Category\MaterialSourceWarningController;
use App\Http\Controllers\Pages\Category\ProductCategoryController;
use App\Http\Controllers\Pages\Category\PublicationTrackingController;

use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;



Route::prefix('/category')
        ->name('pages.category.')
        ->middleware(CheckLogin::class)
        ->group(function () {

                Route::prefix('/intermediate')
                        ->name('intermediate.')
                        ->controller(IntermediateCategoryController::class)
                        ->group(function () {
                                Route::get('', 'index')->name('list');
                                Route::post('store', 'store')->name('store');
                                Route::post('update', 'update')->name('update');
                                Route::post('deActive', 'deActive')->name('deActive');
                                Route::post('recipe', 'recipe')->name('recipe');
                                Route::post('save_bom', 'save_bom')->name('resave_bomcipe');
                                Route::get('get_material_name', 'getMaterialName')->name('getMaterialName');
                                Route::get('history', 'history')->name('history');
                        });


                Route::prefix('/product')
                        ->name('product.')
                        ->controller(ProductCategoryController::class)
                        ->group(function () {
                                Route::get('', 'index')->name('list');
                                Route::post('store', 'store')->name('store');
                                Route::post('update', 'update')->name('update');
                                Route::post('deActive', 'deActive')->name('deActive');
                                Route::post('recipe', 'recipe')->name('recipe');
                                Route::post('save_bom', 'save_bom')->name('save_bom');
                                Route::get('getJsonFPCategory', 'getJsonFPCategory')->name('getJsonFPCategory');
                                Route::get('history', 'history')->name('history');
                        });


                Route::prefix('/maintenance')
                        ->name('maintenance.')
                        ->controller(MaintenanceCategoryController::class)
                        ->group(function () {
                                Route::get('', 'index')->name('list');
                                Route::post('store', 'store')->name('store');
                                Route::post('updateTime', 'updateTime')->name('updateTime');
                                Route::post('deActive', 'deActive')->name('deActive');
                                Route::post('is_HVAC', 'is_HVAC')->name('is_HVAC');
                                Route::post('updateRoom', 'updateRoom')->name('updateRoom');
                                Route::post('updateDepartment', 'updateDepartment')->name('updateDepartment');
                                Route::get('history', 'history')->name('history');
                        });


                Route::prefix('/material_check')
                        ->name('material_check.')
                        ->controller(MaterialCheckController::class)
                        ->group(function () {
                                Route::get('', 'index')->name('list');
                        });


                Route::prefix('/material_source_warning')
                        ->name('material_source_warning.')
                        ->controller(MaterialSourceWarningController::class)
                        ->group(function () {
                                Route::get('', 'index')->name('list');
                                Route::get('intermediate_data', 'intermediateData')->name('intermediateData');
                                Route::post('store', 'store')->name('store');
                                Route::post('update', 'update')->name('update');
                                Route::post('deActive', 'deActive')->name('deActive');
                        });


                Route::prefix('/publication_tracking')
                        ->name('publication_tracking.')
                        ->controller(PublicationTrackingController::class)
                        ->group(function () {
                                Route::get('', 'index')->name('list');
                                Route::post('sync', 'sync')->name('sync');
                                Route::post('task/store', 'storeTask')->name('storeTask');
                                Route::post('task/update', 'updateTask')->name('updateTask');
                                Route::post('task/delete', 'deleteTask')->name('deleteTask');
                                Route::get('task/history', 'taskHistory')->name('taskHistory');
                                Route::get('detail/history', 'detailHistory')->name('detailHistory');
                                Route::post('detail/decision', 'updateDetailDecision')->name('updateDetailDecision');
                                Route::post('task_item/carry', 'carryTaskItem')->name('carryTaskItem');
                                Route::post('toggle_status', 'toggleStatus')->name('toggleStatus');
                                Route::get('{id}', 'show')->whereNumber('id')->name('show');
                        });
        });

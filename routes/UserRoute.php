<?php
    // use Illuminate\Routing\Route;

use App\Http\Controllers\Pages\User\LoginHistoryController;
use App\Http\Controllers\Pages\User\PermissionContoller;
use App\Http\Controllers\Pages\User\RoleController;
use App\Http\Controllers\Pages\User\UserController;
use App\Http\Controllers\Pages\User\UserPermissionController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;
   
Route::prefix('/User')
->name('pages.User.')
->middleware(CheckLogin::class)
->group(function(){

    Route::prefix('/user')
    ->name('user.')
    ->controller(UserController::class)
    ->group(function(){

        Route::get('','index')->name('list');
        Route::post('store','store')->name('store');
        Route::post('update', 'update')->name('update');
        Route::post('deActive/{id}','deActive')->name('deActive');
        Route::post('active/{id}','active')->name('active');

    });

    Route::prefix('/user_permission')
    ->name('user_permission.')
    ->controller(UserPermissionController::class)
    ->group(function(){

        Route::get('{userId}','index')->name('list');
        Route::post('store_or_update','store_or_update')->name('store_or_update');

    });

    // Lịch sử đăng nhập: toàn hệ thống (quyền login_history_view, kiểm tra trong controller) + của tôi
    Route::prefix('/login_history')
    ->name('login_history.')
    ->controller(LoginHistoryController::class)
    ->group(function(){
        Route::get('','index')->name('list');
        Route::get('mine','mine')->name('mine');
    });

    Route::prefix('/permission')
    ->name('permission.')
    ->controller(PermissionContoller::class)
    ->group(function(){
        Route::get('','index')->name('list');    
    });

    
    Route::prefix('/role')
    ->name('role.')
    ->controller(RoleController::class)
    ->group(function(){
        
        Route::get('','index')->name('list');
        Route::post('store_or_update','store_or_update')->name('store_or_update');
        Route::post('saveRole','saveRole')->name('saveRole');
        Route::post('deActive/{id}','deActive')->name('deActive');

    });


});
   

?>
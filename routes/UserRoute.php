<?php
    // use Illuminate\Routing\Route;

use App\Http\Controllers\Pages\User\UserController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;
   
Route::prefix('/User')
->controller(UserController::class)
->name('pages.User.')
->middleware(CheckLogin::class)
->group(function(){

        Route::get('','index')->name('list');
        Route::get('create','showCreateProductNameForm')->name('create');
        Route::post('store','store')->name('store');
        Route::get('detail/{id}', 'detail')->name('detail');
        Route::post('update', 'update')->name('update');
        Route::post('deActive/{id}','deActive')->name('deActive'); 
    
});
   

?>
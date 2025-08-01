<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\General\HomeController;
use App\Http\Controllers\HomeController as ControllersHomeController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;


Route::get('/', [LoginController::class, 'showLogin']);
Route::post('/', [LoginController::class, 'login'])->name('login');

Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/home', [HomeController::class, 'showHomeForm'])->name('pages.general.home')->middleware(CheckLogin::class);








?>
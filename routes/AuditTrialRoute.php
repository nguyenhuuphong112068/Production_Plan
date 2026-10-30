<?php

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Http\Middleware\CheckLogin;
use Illuminate\Support\Facades\Route;

    Route::get('AuditTrail', [AuditTrialController::class, 'index'])->name('pages.AuditTrail.list')->middleware(CheckLogin::class);

?>
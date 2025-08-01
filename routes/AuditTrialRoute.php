<?php

use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use Illuminate\Support\Facades\Route;
   
    Route::get('AuditTrail', [AuditTrialController::class, 'index'])->name('pages.AuditTrail.list');

?>
<?php

use App\Http\Controllers\General\NotificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckLogin;

Route::middleware([CheckLogin::class])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'list'])->name('notifications.list');
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
});

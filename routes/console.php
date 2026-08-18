<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('notify:unscheduled-batches')->dailyAt('08:00');
Schedule::command('notify:validation-sampling')->dailyAt('08:00');

// Chốt tồn bán thành phẩm đúng đầu ngày công 06:00
Schedule::command('wip:snapshot-coverage')->dailyAt('06:00')->withoutOverlapping();

// Đồng bộ danh sách nhân sự từ eO2 PMS.
// Chạy nền vì máy chủ nguồn mất ~9.5s (PXTN) đến ~88s (PXV1) mỗi request —
// không thể để trong luồng đăng nhập. Chạy trước giờ vào ca sáng, và lặp lại
// giữa ngày để bắt các thay đổi nhân sự phát sinh.
Schedule::command('employees:sync-roster')->dailyAt('05:00')->withoutOverlapping();
Schedule::command('employees:sync-roster')->dailyAt('12:30')->withoutOverlapping();

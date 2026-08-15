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

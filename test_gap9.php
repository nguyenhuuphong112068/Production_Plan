<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

$ctrl = app()->make(\App\Http\Controllers\Pages\Schedual\SchedualController::class);
$reflection = new ReflectionClass($ctrl);

// Load off dates
$loadOffDateMethod = $reflection->getMethod("loadOffDate");
$loadOffDateMethod->setAccessible(true);
$loadOffDateMethod->invokeArgs($ctrl, ["asc"]);

// Load room availability for S9 (id=13)
$loadRoomAvailabilityMethod = $reflection->getMethod("loadRoomAvailability");
$loadRoomAvailabilityMethod->setAccessible(true);
$loadRoomAvailabilityMethod->invokeArgs($ctrl, ["asc", 13]);

$roomAvailabilityProperty = $reflection->getProperty("roomAvailability");
$roomAvailabilityProperty->setAccessible(true);
$roomAvailability = $roomAvailabilityProperty->getValue($ctrl);

echo "=== S9 Busy List (merged) from loadRoomAvailability ===\n";
foreach ($roomAvailability[13] as $i => $busy) {
    echo "[$i] start: " . $busy['start']->toDateTimeString() . " -> end: " . $busy['end']->toDateTimeString() . "\n";
}

echo "\n=== Finding gaps from 2026-06-24 09:45 ===\n";
$current_start = Carbon::parse("2026-06-24 09:45:00");
$need = 2520; // 42 hours
$prev_end = $current_start;

$busyList = $roomAvailability[13];
foreach ($busyList as $i => $busy) {
    if ($busy['start']->lt($current_start)) continue;
    $gap = $prev_end->diffInMinutes($busy['start']);
    echo "Gap before busy[$i]: " . $prev_end->toDateTimeString() . " -> " . $busy['start']->toDateTimeString() . " = $gap phut (" . round($gap/60, 1) . "h) " . ($gap >= $need ? "[DU $need phut]" : "[THIEU]") . "\n";
    $prev_end = $busy['end'];
}

$gap_after_last = null;
if (!empty($busyList)) {
    $last_busy = end($busyList);
    if ($last_busy['end']->gt($current_start)) {
        $gap_after_last = "sau last busy: " . $last_busy['end']->toDateTimeString() . " -> ...";
    }
}
if ($gap_after_last) echo "Gap $gap_after_last\n";

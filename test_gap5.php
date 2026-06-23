<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$ctrl = app()->make(\App\Http\Controllers\Pages\Schedual\SchedualController::class);
$reflection = new ReflectionClass($ctrl);
$loadOffDateMethod = $reflection->getMethod("loadOffDate");
$loadOffDateMethod->setAccessible(true);
$loadOffDateMethod->invokeArgs($ctrl, ["asc"]);
$offDateProperty = $reflection->getProperty("offDate");
$offDateProperty->setAccessible(true);
$offDateList = $offDateProperty->getValue($ctrl);

use Carbon\Carbon;
$current_start = Carbon::parse("2026-06-27 15:15:00");
$need = 2520;
$current_end = $current_start->copy()->addMinutes($need);

$newOffTime = 0;
foreach ($offDateList as $off) {
    if ($off["end"] <= $current_start || $off["start"] >= $current_end) {
        continue;
    }
    $overlapStart = $off["start"]->greaterThan($current_start) ? $off["start"] : $current_start;
    $overlapEnd = $off["end"]->lessThan($current_end) ? $off["end"] : $current_end;
    echo "Found overlap: " . $off["start"]->toDateString() . "\n";
    $newOffTime += $overlapStart->diffInMinutes($overlapEnd);
}
echo "New OffTime: $newOffTime\n";

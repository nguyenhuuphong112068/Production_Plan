<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

$ctrl = app()->make(\App\Http\Controllers\Pages\Schedual\SchedualController::class);

$reflection = new ReflectionClass($ctrl);

$loadOffDateMethod = $reflection->getMethod("loadOffDate");
$loadOffDateMethod->setAccessible(true);
$loadOffDateMethod->invokeArgs($ctrl, ["asc"]);

$offDateProperty = $reflection->getProperty("offDate");
$offDateProperty->setAccessible(true);
$offDateList = $offDateProperty->getValue($ctrl);

$skipOffTimeMethod = $reflection->getMethod("skipOffTime");
$skipOffTimeMethod->setAccessible(true);

$current_start = Carbon::parse("2026-07-06 14:30:00");
$bStart = Carbon::parse("2026-07-10 22:30:00");
$gap = $current_start->diffInMinutes($bStart);
$need = 2400; // 40 hours

$offTime = 0;
do {
    $current_end = $current_start->copy()->addMinutes($need + $offTime);
    $newOffTime = 0;
    foreach ($offDateList as $off) {
        if ($off['end'] <= $current_start || $off['start'] >= $current_end) {
            continue;
        }
        $overlapStart = $off['start']->greaterThan($current_start) ? $off['start'] : $current_start;
        $overlapEnd = $off['end']->lessThan($current_end) ? $off['end'] : $current_end;
        $newOffTime += $overlapStart->diffInMinutes($overlapEnd);
    }
    $changed = ($newOffTime > $offTime);
    $offTime = $newOffTime;
} while ($changed);

echo "Gap: " . $gap . "\n";
echo "Need: " . $need . "\n";
echo "OffTime: " . $offTime . "\n";
echo "Result: " . ($gap >= $need + $offTime ? "TRUE (GAP SUFFICIENT)" : "FALSE (GAP TOO SMALL)") . "\n";


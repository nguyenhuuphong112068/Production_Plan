<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;

$ctrl = app()->make(\App\Http\Controllers\Pages\Schedual\SchedualController::class);

$reflection = new ReflectionClass($ctrl);
$loadRoomMethod = $reflection->getMethod("loadRoomAvailability");
$loadRoomMethod->setAccessible(true);
$loadRoomMethod->invokeArgs($ctrl, ["asc", 13]); // S9

$findSlotMethod = $reflection->getMethod("findEarliestSlot2");
$findSlotMethod->setAccessible(true);

$offDateProperty = $reflection->getProperty("offDate");
$offDateProperty->setAccessible(true);
$offDateProperty->setValue($ctrl, [
    ["start" => Carbon::parse("2026-06-20 00:00:00"), "end" => Carbon::parse("2026-06-20 23:59:59")],
    ["start" => Carbon::parse("2026-06-21 00:00:00"), "end" => Carbon::parse("2026-06-21 23:59:59")],
    ["start" => Carbon::parse("2026-06-28 00:00:00"), "end" => Carbon::parse("2026-06-28 23:59:59")],
    ["start" => Carbon::parse("2026-07-04 00:00:00"), "end" => Carbon::parse("2026-07-04 23:59:59")],
    ["start" => Carbon::parse("2026-07-05 00:00:00"), "end" => Carbon::parse("2026-07-05 23:59:59")],
    ["start" => Carbon::parse("2026-07-12 00:00:00"), "end" => Carbon::parse("2026-07-12 23:59:59")],
    ["start" => Carbon::parse("2026-07-18 00:00:00"), "end" => Carbon::parse("2026-07-18 23:59:59")],
    ["start" => Carbon::parse("2026-07-19 00:00:00"), "end" => Carbon::parse("2026-07-19 23:59:59")]
]);

$start = Carbon::parse("2026-06-24 12:15:00");
$candidate = $findSlotMethod->invokeArgs($ctrl, [
    13, 
    $start, 
    2400, // 40 hours
    0, 
    0, 
    0, 
    "stage_plan", 
    2, 
    60, 
    null
]);

echo json_encode($candidate);

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

$property = $reflection->getProperty("roomAvailability");
$property->setAccessible(true);
$avail = $property->getValue($ctrl)[13];

echo json_encode($avail, JSON_PRETTY_PRINT);

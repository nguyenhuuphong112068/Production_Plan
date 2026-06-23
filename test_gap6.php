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
echo "Count: " . count($offDateList) . "\n";

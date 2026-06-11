<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$method = new ReflectionMethod('App\Http\Controllers\Pages\Schedual\SchedualController', 'getEvents');
$method->setAccessible(true);
$controller = $app->make('App\Http\Controllers\Pages\Schedual\SchedualController');

try {
    $events = $method->invoke($controller, 'PXV1', '2026-06-01', '2026-06-30', 1, 1);
    
    $found = 0;
    foreach ($events as $event) {
        if (isset($event['blister_mold_code'])) {
            echo "Event ID: " . $event['id'] . " Mold: " . $event['blister_mold_code'] . "\n";
            $found++;
            if ($found >= 5) break;
        }
    }
    if ($found == 0) echo "NO events with blister_mold_code found!\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . " at line " . $e->getLine() . "\n";
}

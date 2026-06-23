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

$findEarliestSlot2Method = $reflection->getMethod("findEarliestSlot2");
$findEarliestSlot2Method->setAccessible(true);

$candidate = $findEarliestSlot2Method->invokeArgs($ctrl, [
    13, // S9
    Carbon::parse("2026-06-25"), // Earliest start (predecessor end)
    2520, // 40 hours
    0, 0, 0, 'stage_plan', 2, 60, null
]);

if ($candidate === null) {
    echo "findEarliestSlot2 returned NULL\n";
} else {
    $candidateStart = is_array($candidate) ? $candidate['start'] : $candidate;
    echo "Candidate Start: " . $candidateStart->toDateTimeString() . "\n";
}

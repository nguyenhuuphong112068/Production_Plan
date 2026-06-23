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

// Call findEarliestSlot2 for S9 (13) with Earliest = 2026-06-24 09:45, need=2520
$findEarliestSlot2Method = $reflection->getMethod("findEarliestSlot2");
$findEarliestSlot2Method->setAccessible(true);

$earliest = Carbon::parse("2026-06-24 09:45:00");
$need = 2520;

$candidate = $findEarliestSlot2Method->invokeArgs($ctrl, [
    13, $earliest, $need, 0, 0, 0, 'stage_plan', 2, 60, null
]);
$candidateStart = is_array($candidate) ? $candidate['start'] : $candidate;
echo "findEarliestSlot2 result: " . ($candidateStart ? $candidateStart->toDateTimeString() : "NULL") . "\n";

// Now trace: what happens in the scheduleCampaign loop when pred_end overrides bestStart?
echo "\n=== Checking predecessor_code end dates for campaign 7639_1 (stage 4) ===\n";
$campaignTasks = DB::table('stage_plan')
    ->select('id', 'predecessor_code', 'title')
    ->where('campaign_code', '7639_1')
    ->where('stage_code', 4)
    ->orderBy('id')
    ->get();

foreach ($campaignTasks as $task) {
    $pred = DB::table('stage_plan')->where('code', $task->predecessor_code)->select('id', 'start', 'end', 'stage_code', 'title')->first();
    if ($pred) {
        echo "Task {$task->id}: pred_end = {$pred->end} (stage {$pred->stage_code})\n";
    } else {
        echo "Task {$task->id}: predecessor NOT FOUND (code={$task->predecessor_code})\n";
    }
}

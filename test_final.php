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

$campaignTasks = DB::table('stage_plan')
    ->select('id', 'predecessor_code', 'title', 'stage_code', 'product_caterogy_id')
    ->where('campaign_code', '7639_1')
    ->where('stage_code', 4)
    ->orderBy('id')
    ->get();

$firstTask = $campaignTasks->first();
$stageCode = 4;

// Compute avg times
$avg_m_time = DB::table('quota')
    ->selectRaw('AVG(TIME_TO_SEC(m_time)/60) as v')
    ->where('intermediate_code', function($q) use ($firstTask) {
        $q->select('intermediate_code')->from('finished_product_category')->where('id', $firstTask->product_caterogy_id);
    })
    ->where('active', 1)->where('stage_code', $stageCode)
    ->value('v') ?? 15;

$avg_C1_time = DB::table('quota')
    ->selectRaw('AVG(TIME_TO_SEC(C1_time)/60) as v')
    ->where('intermediate_code', function($q) use ($firstTask) {
        $q->select('intermediate_code')->from('finished_product_category')->where('id', $firstTask->product_caterogy_id);
    })
    ->where('active', 1)->where('stage_code', $stageCode)
    ->value('v') ?? 0;

$avg_slot_time = $avg_m_time + $avg_C1_time;
echo "avg_m_time=$avg_m_time, avg_C1_time=$avg_C1_time, avg_slot_time=$avg_slot_time\n";

$candidates = [Carbon::parse("2026-06-23 10:00:00")];
$batch_index = 0;
foreach ($campaignTasks as $campaignTask) {
    $pred = DB::table('stage_plan')->where('code', $campaignTask->predecessor_code)->first();
    if ($pred && !in_array($pred->stage_code, [1, 2])) {
        $cand = Carbon::parse($pred->end)->addMinutes(30)->subMinutes($batch_index * $avg_slot_time);
        $candidates[] = $cand;
        echo "Lo $batch_index: pred_end={$pred->end}, candidate=$cand\n";
    }
    $batch_index++;
}

$earliestStart = collect($candidates)->max();
echo "\nearliestStart = $earliestStart\n";

// Call findEarliestSlot2 for S9 (13)
$candidate = $findEarliestSlot2Method->invokeArgs($ctrl, [
    13, $earliestStart, 2520, 0, 0, 0, 'stage_plan', 2, 60, null
]);
$candidateStart = is_array($candidate) ? $candidate['start'] : $candidate;
echo "findEarliestSlot2 result: " . ($candidateStart ? $candidateStart->toDateTimeString() : "NULL") . "\n";

// Verify all constraints
echo "\n=== Xac nhan constraint cua tung lo ===\n";
$m_time = 240; $C1_time = 30; $p_time = 30;
$T = $candidateStart;
foreach ($campaignTasks as $i => $task) {
    $pred = DB::table('stage_plan')->where('code', $task->predecessor_code)->first();
    if ($i == 0) {
        $batch_start = $T->copy();
    } else {
        $batch_start = $T->copy()->addMinutes($p_time + $i * ($m_time + $C1_time));
    }
    $ok = $pred && $batch_start->gte(Carbon::parse($pred->end)) ? "OK" : "FAIL (pred_end={$pred->end})";
    echo "Lo $i (id={$task->id}) bat dau: $batch_start $ok\n";
}

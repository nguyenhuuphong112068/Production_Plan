<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

$ctrl = app()->make(\App\Http\Controllers\Pages\Schedual\SchedualController::class);

$reflection = new ReflectionClass($ctrl);

// Force load offDate
$loadOffDateMethod = $reflection->getMethod("loadOffDate");
$loadOffDateMethod->setAccessible(true);
$loadOffDateMethod->invokeArgs($ctrl, ["asc"]);

$offDateProperty = $reflection->getProperty("offDate");
$offDateProperty->setAccessible(true);
$offDate = $offDateProperty->getValue($ctrl);
// print_r($offDate); // Too much output

// Get campaign tasks
$campaignTasks = DB::table('stage_plan')
    ->select('id', 'plan_master_id', 'product_caterogy_id', 'predecessor_code', 'nextcessor_code', 'campaign_code', 'code', 'stage_code', 'tank', 'keep_dry', 'order_by', 'required_room_code', 'immediately')
    ->where('campaign_code', '7639_1')
    ->where('stage_code', 4)
    ->orderBy('id')
    ->get();

if ($campaignTasks->isEmpty()) {
    die("Campaign not found!\n");
}

$firstTask = $campaignTasks->first();

// Get earliestStart exactly like scheduleCampaign
$candidates = [];
$candidates[] = Carbon::parse('2026-06-20'); // Dummy start date

$avg_m_time = DB::table('quota')
    ->selectRaw('AVG(TIME_TO_SEC(m_time)/60) as avg_m_time_minutes')
    ->where('intermediate_code', function($q) use ($firstTask) {
        $q->select('intermediate_code')->from('finished_product_category')->where('id', $firstTask->product_caterogy_id);
    })
    ->where('active', 1)
    ->where('stage_code', 4)
    ->value('avg_m_time_minutes') ?? 15;

$batch_index = 0;
foreach ($campaignTasks as $campaignTask) {
    $pred = DB::table('stage_plan')->where('code', $campaignTask->predecessor_code)->first();
    if ($pred && !in_array($pred->stage_code, [1, 2])) {
        $candidates[] = Carbon::parse($pred->end)->addMinutes(30)->subMinutes($batch_index * $avg_m_time);
    }
    $batch_index++;
}
$earliestStart = collect($candidates)->max();
echo "Earliest Start: " . $earliestStart->toDateTimeString() . "\n";

// S9 is room 13
$roomId = 13;
$room = DB::table('quota')->select(
    'room_id',
    DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
    DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
    DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
    DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
)
->where('room_id', $roomId)
->where('stage_code', 4)
->first();

$totalMunites = $room->p_time_minutes + ($campaignTasks->count() * $room->m_time_minutes)
    + ($campaignTasks->count() - 1) * ($room->C1_time_minutes)
    + $room->C2_time_minutes;

echo "Total Minutes: " . $totalMunites . " (" . ($totalMunites/60) . " hours)\n";

$findEarliestSlot2Method = $reflection->getMethod("findEarliestSlot2");
$findEarliestSlot2Method->setAccessible(true);

$candidate = $findEarliestSlot2Method->invokeArgs($ctrl, [
    $room->room_id,
    $earliestStart,
    $totalMunites,
    0,
    0,
    0,
    'stage_plan',
    2,
    60,
    null
]);

if ($candidate === null) {
    echo "findEarliestSlot2 returned NULL\n";
} else {
    $candidateStart = is_array($candidate) ? $candidate['start'] : $candidate;
    echo "Candidate Start: " . $candidateStart->toDateTimeString() . "\n";
}

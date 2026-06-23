<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

$ctrl = app()->make(\App\Http\Controllers\Pages\Schedual\SchedualController::class);

$reflection = new ReflectionClass($ctrl);

// Prepare the tasks for campaign 230626 Stage 4
$campaignTasks = DB::table('stage_plan as sp')
    ->select(
        'sp.id',
        'sp.plan_master_id',
        'sp.product_caterogy_id',
        'sp.predecessor_code',
        'sp.nextcessor_code',
        'sp.campaign_code',
        'sp.code',
        'sp.stage_code',
        'sp.campaign_code',
        'sp.tank',
        'sp.keep_dry',
        'sp.order_by',
        'sp.required_room_code',
        'sp.immediately',

        'plan_master.batch',
        'plan_master.is_val',
        'plan_master.code_val',
        'plan_master.expected_date',
        'plan_master.after_weigth_date',
        'plan_master.after_parkaging_date',
        'plan_master.allow_weight_before_date',

        'finished_product_category.product_name_id',
        'finished_product_category.market_id',
        'finished_product_category.finished_product_code',
        'finished_product_category.intermediate_code'
    )
    ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
    ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
    ->where('sp.stage_code', 4)
    ->where('plan_master.batch', '>=', '230626')
    ->where('plan_master.batch', '<=', '300626')
    ->whereIn('plan_master.id', function($q) {
        $q->select('id')->from('plan_master')->where('code_val', 'LIKE', '%Pracetam 800%');
    })
    ->orderBy('plan_master.batch')
    ->get();

if ($campaignTasks->count() == 0) {
    // fallback 
    $campaignTasks = DB::table('stage_plan as sp')
        ->select(
            'sp.id',
            'sp.plan_master_id',
            'sp.product_caterogy_id',
            'sp.predecessor_code',
            'sp.nextcessor_code',
            'sp.campaign_code',
            'sp.code',
            'sp.stage_code',
            'sp.campaign_code',
            'sp.tank',
            'sp.keep_dry',
            'sp.order_by',
            'sp.required_room_code',
            'sp.immediately',
    
            'plan_master.batch',
            'plan_master.is_val',
            'plan_master.code_val',
            'plan_master.expected_date',
            'plan_master.after_weigth_date',
            'plan_master.after_parkaging_date',
            'plan_master.allow_weight_before_date',
    
            'finished_product_category.product_name_id',
            'finished_product_category.market_id',
            'finished_product_category.finished_product_code',
            'finished_product_category.intermediate_code'
        )
        ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
        ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
        ->where('sp.stage_code', 4)
        ->where('plan_master.id', '>=', 7639) // ID for 230626
        ->where('plan_master.id', '<=', 7646) // Assuming 8 batches
        ->orderBy('plan_master.batch')
        ->get();
}

echo "Tasks count: " . $campaignTasks->count() . "\n";

// Override saveSchedule to just print the result instead of saving to DB
$scheduleCampaignMethod = $reflection->getMethod("scheduleCampaign");
$scheduleCampaignMethod->setAccessible(true);

$loadOffDateMethod = $reflection->getMethod("loadOffDate");
$loadOffDateMethod->setAccessible(true);
$loadOffDateMethod->invokeArgs($ctrl, ["asc"]);

$firstTask = $campaignTasks->first();
$avg_m_time = DB::table('quota')
    ->selectRaw('AVG(TIME_TO_SEC(m_time)/60) as avg_m_time_minutes')
    ->where('intermediate_code', $firstTask->intermediate_code)
    ->where('active', 1)
    ->where('stage_code', 4)
    ->value('avg_m_time_minutes') ?? 15;

$batch_index = 0;
$candidates = [];
$candidates[] = Carbon::parse('2026-06-20 00:00:00'); // random early date
foreach ($campaignTasks as $campaignTask) {
    $pred = DB::table('stage_plan')->where('code', $campaignTask->predecessor_code)->first();
    if ($pred && !in_array($pred->stage_code, [1, 2])) {
        $candidates[] = Carbon::parse($pred->end)->addMinutes(0)->subMinutes($batch_index * $avg_m_time);
    }
    $batch_index++;
}
$earliestStart = collect($candidates)->max();
echo "Earliest start: " . $earliestStart->toDateTimeString() . "\n";

$rooms = DB::table('quota')->select(
    'room_id',
    DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
    DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
    DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
    DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
)
    ->where('intermediate_code', $firstTask->intermediate_code)
    ->where('active', 1)
    ->where('stage_code', 4)
    ->get();

echo "Rooms: " . json_encode($rooms->pluck('room_id')) . "\n";

$loadRoomAvailabilityMethod = $reflection->getMethod("loadRoomAvailability");
$loadRoomAvailabilityMethod->setAccessible(true);

$findEarliestSlot2Method = $reflection->getMethod("findEarliestSlot2");
$findEarliestSlot2Method->setAccessible(true);

foreach ($rooms as $room) {
    $loadRoomAvailabilityMethod->invokeArgs($ctrl, ["asc", $room->room_id]);
    
    $p_adj = (float) $room->p_time_minutes * 1;
    $m_adj = (float) $room->m_time_minutes * 1;
    $totalMunites = $p_adj + ($campaignTasks->count() * $m_adj)
        + ($campaignTasks->count() - 1) * ($room->C1_time_minutes)
        + $room->C2_time_minutes;

    echo "Total minutes for room {$room->room_id}: $totalMunites\n";

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

    $cStart = is_array($candidate) ? $candidate['start'] : $candidate;
    echo "Room {$room->room_id} candidate start: " . ($cStart ? $cStart->toDateTimeString() : "NULL") . "\n";
}

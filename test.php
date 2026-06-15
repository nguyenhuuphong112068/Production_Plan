<?php
namespace App\Http\Controllers\Pages\Plan;
use Illuminate\Support\Facades\DB;

$departmentCode = 'PXV1';
$effectiveStageCode = 7;

$maxStageFinished = DB::table('stage_plan')
    ->where('finished', 1)
    ->where('active', 1)
    ->where('stage_code', '!=', 8)
    ->where('deparment_code', $departmentCode)
    ->select('plan_master_id', DB::raw('MAX(stage_code) as max_stage_code'))
    ->groupBy('plan_master_id');

$maxPossibleStage = DB::table('stage_plan')
    ->where('active', 1)
    ->where('stage_code', '!=', 8)
    ->where('deparment_code', $departmentCode)
    ->select('plan_master_id', DB::raw('MAX(stage_code) as max_possible_stage_code'))
    ->groupBy('plan_master_id');

$planMasterQuery = DB::table('plan_master as pm')
    ->join('plan_list as pl', 'pm.plan_list_id', '=', 'pl.id')
    ->leftJoinSub($maxStageFinished, 'sp_max', function ($join) {
        $join->on('pm.id', '=', 'sp_max.plan_master_id');
    })
    ->leftJoinSub($maxPossibleStage, 'sp_possible', function ($join) {
        $join->on('pm.id', '=', 'sp_possible.plan_master_id');
    })
    ->leftJoin('stage_plan as sp', function ($join) {
        $join->on('pm.id', '=', 'sp.plan_master_id')
            ->on('sp.stage_code', '=', 'sp_max.max_stage_code');
    })
    ->where('pm.active', 1)
    ->where('pl.type', 1)
    ->where('pm.only_parkaging', 0)
    ->where('pm.plan_list_id', '>', 23)
    ->where('pm.cancel', 0)
    ->where('pm.deparment_code', $departmentCode)
    ->whereRaw("NOT (
        (IFNULL(sp.finished, 0) = 1 AND IFNULL(sp_max.max_stage_code, 0) < 7 AND IFNULL(sp_max.max_stage_code, 0) = IFNULL(sp_possible.max_possible_stage_code, -1)) 
        OR (IFNULL(sp.finished, 0) = 1 AND IFNULL(sp_max.max_stage_code, 0) = 7)
    )");

$planMasterIds = (clone $planMasterQuery)->pluck('pm.id')->toArray();
echo "Total planMasterIds: " . count($planMasterIds) . "\n";

$scheduledCounts = DB::table('stage_plan')
    ->whereIn('plan_master_id', $planMasterIds)
    ->where('stage_code', $effectiveStageCode)
    ->where(function($query) {
        $query->whereNotNull('actual_start')
              ->orWhereNotNull('schedualed_at');
    })
    ->select('required_room_code', DB::raw('COUNT(*) as scheduled_count'))
    ->groupBy('required_room_code')
    ->pluck('scheduled_count', 'required_room_code')
    ->toArray();

var_dump($scheduledCounts);
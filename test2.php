<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
session(['user' => ['production_code' => 'PXV1', 'userId' => 1]]);
$c = app()->make('App\Http\Controllers\Pages\Plan\ProductionPlanController');
$r = new \Illuminate\Http\Request();
$data = $c->index($r)->getData();
$batch_status = $data['batch_summary'] ?? collect(); 
$production_code = 'PXV1';

$maxStageFinished = DB::table('stage_plan')
->where('finished', 1)
->where('active', 1)
->where('stage_code', '!=', 8)
->where('deparment_code', $production_code)
->select(
    'plan_master_id',
    DB::raw('MAX(stage_code) as max_stage_code')
)
->groupBy('plan_master_id');

$maxPossibleStage = DB::table('stage_plan')
->where('active', 1)
->where('stage_code', '!=', 8)
->where('deparment_code', $production_code)
->select(
    'plan_master_id',
    DB::raw('MAX(stage_code) as max_possible_stage_code')
)
->groupBy('plan_master_id');

$batch_status = DB::table('plan_master as pm')
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
->leftJoin('finished_product_category as fc', 'pm.product_caterogy_id', '=', 'fc.id')
->where('pm.active', 1)
->where('pl.type', 1)
->where('pm.only_parkaging', 0)
->where('pm.plan_list_id', '!=', 0)
->where('pm.plan_list_id', '>', 23)
->where('pm.deparment_code', $production_code)
->select(
    'pm.plan_list_id',
    'fc.batch_qty',
    DB::raw("DATE_FORMAT(pm.expected_date, '%m-%Y') as expected_month"),
    DB::raw("DATE_FORMAT(sp.actual_start, '%m-%Y') as actual_month"),
    DB::raw("
    CASE
    WHEN pm.cancel = 1 THEN 'Hủy'
    WHEN sp.finished = 1 AND sp_max.max_stage_code < 7 AND sp_max.max_stage_code = sp_possible.max_possible_stage_code THEN 'Hoàn Tất'
    WHEN sp.finished = 1 AND sp_max.max_stage_code = 1 THEN 'Đã Cân'
    WHEN sp.finished = 1 AND sp_max.max_stage_code = 3 THEN 'Đã Pha chế'
    WHEN sp.finished = 1 AND sp_max.max_stage_code = 4 THEN 'Đã THT'
    WHEN sp.finished = 1 AND sp_max.max_stage_code = 5 THEN 'Đã định hình'
    WHEN sp.finished = 1 AND sp_max.max_stage_code = 6 THEN 'Đã Bao phim'
    WHEN sp.finished = 1 AND sp_max.max_stage_code = 7 THEN 'Hoàn Tất ĐG'
    ELSE 'Chưa làm'
    END AS status
    ")
)
->get();

$filtered = $batch_status->filter(function ($row) {
    return !empty($row->actual_month) && $row->status !== 'Chưa làm';
});
echo "\nCount actual_month not null and not Chua lam: " . $filtered->count();

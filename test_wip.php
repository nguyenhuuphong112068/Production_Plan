<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$production = 'PXV1';

$allDatas = Illuminate\Support\Facades\DB::table('stage_plan')
    ->where('stage_plan.active', 1)
    ->where('stage_plan.deparment_code', $production)
    ->leftJoin('plan_master',  'stage_plan.plan_master_id', 'plan_master.id')
    ->where('plan_master.only_parkaging',  0)
    ->where('stage_plan.stage_code', '<', 8)
    ->select('stage_plan.plan_master_id', 'stage_plan.stage_code', 'stage_plan.finished')
    ->get()
    ->groupBy('plan_master_id');

$wipDatasUnmapped = collect();
foreach ($allDatas as $plan_master_id => $stages) {
    $weighingFinished = false;
    $packagingFinished = false;
    foreach ($stages as $s) {
        if (in_array($s->stage_code, [1, 2]) && $s->finished == 1) {
            $weighingFinished = true;
        }
        if ($s->stage_code >= 7 && $s->finished == 1) {
            $packagingFinished = true;
        }
    }
    if ($weighingFinished && !$packagingFinished) {
        $wipDatasUnmapped->put($plan_master_id, $stages);
    }
}

echo '4396 in wipDatas: ' . ($wipDatasUnmapped->has(4396) ? 'Yes' : 'No') . "\n";
echo '4454 in wipDatas: ' . ($wipDatasUnmapped->has(4454) ? 'Yes' : 'No') . "\n";

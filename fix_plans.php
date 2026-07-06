<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';
$app = app();
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$wrongPlans = DB::table('stage_plan as sp')
    ->join('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
    ->where('sp.stage_code', 8)
    ->where('sp.finished', 1)
    ->whereYear('pm.expected_date', '>=', 2026) // Future plans
    ->whereYear('sp.actual_start', '<=', 2025) // But finished in past years (2018, 2019...)
    ->select('sp.id as sp_id')
    ->get();

$count = 0;
foreach ($wrongPlans as $plan) {
    DB::table('stage_plan')->where('id', $plan->sp_id)->update([
        'actual_start' => null,
        'actual_end' => null,
        'finished_by' => null,
        'finished' => 0,
        'yields' => null,
    ]);
    $count++;
}

echo "Fixed $count wrongly synced plans.\n";

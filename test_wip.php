<?php
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$planYear = 2026;

$wipQuery = "
SELECT 
    pm.id as plan_id,
    pm.product_caterogy_id AS fpc_id,
    sp_start.actual_start AS start_date,
    sp_end.actual_end AS end_date,
    sp_end.finished as is_finished,
    fpc.batch_qty
FROM plan_master pm
JOIN finished_product_category fpc ON pm.product_caterogy_id = fpc.id
JOIN stage_plan sp_start ON sp_start.plan_master_id = pm.id AND sp_start.stage_code = 1 AND sp_start.finished = 1
JOIN (
    SELECT plan_master_id, MAX(stage_code) as max_stage_code
    FROM stage_plan
    GROUP BY plan_master_id
) max_sp ON max_sp.plan_master_id = pm.id
JOIN stage_plan sp_end ON sp_end.plan_master_id = pm.id AND sp_end.stage_code = max_sp.max_stage_code
WHERE pm.active = 1 AND pm.cancel = 0
";

$batches = DB::select($wipQuery);
echo "Found " . count($batches) . " batches.\n";
$sample = array_slice($batches, 0, 10);
print_r($sample);

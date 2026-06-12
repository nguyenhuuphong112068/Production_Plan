<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$data = DB::table('stage_plan as t')
    ->leftJoin('plan_master', 't.plan_master_id', '=', 'plan_master.id')
    ->leftJoin('finished_product_category as fc', 't.product_caterogy_id', '=', 'fc.id')
    ->where('plan_master.batch', '190626')
    ->where(function($q) {
        $q->where('fc.finished_product_code', '3119000215')->orWhere('fc.intermediate_code', '3119000215');
    })
    ->select('t.id', 't.yields', 't.stage_code')
    ->get();
print_r($data->toArray());

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$data = DB::table('stage_plan as t')
    ->leftJoin('plan_master', 't.plan_master_id', '=', 'plan_master.id')
    ->leftJoin('finished_product_category as fc', 't.product_caterogy_id', '=', 'fc.id')
    ->leftJoin('product_name', 'fc.product_name_id', '=', 'product_name.id')
    ->where('product_name.name', 'like', '%Stellavon 8%')
    ->select('t.id', 't.yields', 't.stage_code', 'product_name.name', 'plan_master.batch', 'plan_master.actual_batch', 't.finished')
    ->get();
foreach ($data as $d) {
    $y = DB::table('yields')->where('stage_plan_id', $d->id)->sum('yield');
    if (($d->actual_batch == '190626' || $d->batch == '190626') && $d->finished == 1) {
        print_r(['id' => $d->id, 'batch' => $d->batch, 'actual_batch' => $d->actual_batch, 'stage' => $d->stage_code, 't_yields' => $d->yields, 'sum_yields' => $y]);
    }
}

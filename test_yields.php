<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$plan = DB::table("stage_plan as t")->join("plan_master as pm", "t.plan_master_id", "=", "pm.id")->where("pm.batch", "190626")->where("t.finished", 1)->select("t.id", "pm.batch")->first();
if ($plan) {
    $yields = DB::table("yields")->where("stage_plan_id", $plan->id)->get();
    print_r(["stage_plan_id" => $plan->id, "yields_in_db" => $yields->toArray()]);
} else {
    echo "not found";
}

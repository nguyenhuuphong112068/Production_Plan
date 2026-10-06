<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
foreach (array_slice($argv, 1) as $id) { $r = DB::table('stage_plan as sp')->leftJoin('room as r','sp.resourceId','=','r.id')->where('sp.id',$id)->first(['sp.id','sp.plan_master_id','sp.stage_code','r.code','sp.start','sp.end','sp.end_clearning','sp.not_schedule']); echo json_encode($r), "\n"; }

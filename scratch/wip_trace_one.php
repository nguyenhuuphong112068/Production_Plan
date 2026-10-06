<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Plugins\WipControl\Services\WipAwareScheduler;
use Carbon\Carbon; use Illuminate\Http\Request; use Illuminate\Support\Facades\DB;
session()->put('user', ['production_code' => 'PXV1', 'fullName' => 'TEST', 'id' => 1, 'userGroup' => 'Admin']);
$pm = (int) ($argv[1] ?? 11381);
$rows = DB::table('stage_plan')->where('plan_master_id', $pm)->where('active',1)->whereBetween('stage_code',[3,7])->orderBy('stage_code')->get(['id','stage_code','start','end','code','predecessor_code','nextcessor_code','campaign_code','immediately']);
foreach ($rows as $r) echo json_encode($r) . "\n";
$watch = $rows->where('stage_code', '>=', 5)->pluck('id')->all();
DB::beginTransaction();
try {
  DB::listen(function ($q) use ($watch) {
    if (stripos($q->sql, 'update') === 0) foreach ($q->bindings as $b) if (in_array($b, $watch, true) || (is_numeric($b) && in_array((int)$b, $watch) && stripos($q->sql,'`id`') !== false)) { echo "UPD: " . substr($q->sql,0,200) . " | " . json_encode(array_slice($q->bindings, -3)) . "\n"; break; }
  });
  DB::table('stage_plan')->where('plan_master_id',$pm)->where('stage_code','<=',4)->where('finished',0)->update(['start'=>null,'end'=>null,'start_clearning'=>null,'end_clearning'=>null,'resourceId'=>null,'schedualed'=>0]);
  $req = Request::create('/x','POST',['selectedStep'=>'ĐG','prev_orderBy'=>true]);
  $s = app(WipAwareScheduler::class)->configure($req, 7, [$pm => Carbon::parse('2026-10-10 12:00')]);
  $s->rescheduleUnscheduled($req, Carbon::now()->setTime(6,0), 7);
  foreach (DB::table('stage_plan')->whereIn('id', $rows->pluck('id'))->orderBy('stage_code')->get(['id','stage_code','start','end']) as $r) echo "AFTER " . json_encode($r) . "\n";
} finally { DB::rollBack(); }

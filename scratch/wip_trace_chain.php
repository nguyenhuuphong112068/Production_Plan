<?php
// Sắp lại PC→ĐH của 1 lô (+ campaign) với mốc lùi, in lịch cũ/mới từng công đoạn. ROLLBACK.
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Plugins\WipControl\Services\WipAwareScheduler; use Carbon\Carbon; use Illuminate\Http\Request; use Illuminate\Support\Facades\DB;
session()->put('user', ['production_code' => 'PXV1', 'fullName' => 'TEST', 'id' => 1]);
$pm = (int) $argv[1]; $hint = Carbon::parse($argv[2]);
$camps = DB::table('stage_plan')->where('plan_master_id', $pm)->whereBetween('stage_code', [3, 5])->whereNotNull('campaign_code')->pluck('campaign_code');
$pms = DB::table('stage_plan')->whereIn('campaign_code', $camps)->pluck('plan_master_id')->push($pm)->unique()->values();
$show = function ($label) use ($pms) { foreach (DB::table('stage_plan as sp')->leftJoin('room as r','sp.resourceId','=','r.id')->whereIn('sp.plan_master_id', $pms)->whereBetween('sp.stage_code',[3,6])->where('sp.active',1)->orderBy('sp.plan_master_id')->orderBy('sp.stage_code')->get(['sp.plan_master_id','sp.stage_code','r.code','sp.start','sp.end','sp.finished','sp.campaign_code']) as $r) echo "$label pm{$r->plan_master_id} st{$r->stage_code} {$r->code} {$r->start} → {$r->end} fin{$r->finished} c={$r->campaign_code}\n"; };
$show('OLD');
DB::beginTransaction();
try {
  $ids = DB::table('stage_plan')->whereIn('plan_master_id', $pms)->whereBetween('stage_code',[3,5])->where('finished',0)->whereNull('actual_start')->pluck('id');
  DB::table('stage_plan')->whereIn('id', $ids)->update(['start'=>null,'end'=>null,'start_clearning'=>null,'end_clearning'=>null,'resourceId'=>null,'schedualed'=>0]);
  $park = DB::table('stage_plan')->where('deparment_code','PXV1')->where('active',1)->where('finished',0)->whereNull('start')->where('not_schedule',0)->whereNotIn('id',$ids)->pluck('id');
  DB::table('stage_plan')->whereIn('id',$park)->update(['not_schedule'=>1]);
  $req = Request::create('/x','POST',['prev_orderBy'=>true]);
  $hints = []; foreach ($pms as $p) $hints[$p] = $hint;
  foreach ([3,4,5] as $top) {} // một lượt max_Step 5 như plugin
  app(WipAwareScheduler::class)->configure($req, 5, $hints)->rescheduleUnscheduled($req, Carbon::now()->setTime(6,0), 5);
  $show('NEW');
} finally { DB::rollBack(); }

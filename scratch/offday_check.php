<?php
// CHỈ ĐỌC: lô có giờ sản xuất rơi vào ngày nghỉ (06:00 → 06:00 hôm sau)
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$prod = $argv[1] ?? 'PXV1';
echo "Lần chạy plugin gần nhất: " . json_encode(DB::table('wip_control_runs')->orderByDesc('id')->first(['id','status','created_at'])) . "\n";
$off = DB::table('off_days')->where('off_date', '>=', date('Y-m-d'))->orderBy('off_date')->limit(6)->pluck('off_date');
echo "off_days: " . $off->implode(', ') . "\n\n";
foreach ($off->take(3) as $d) {
  $from = "$d 06:00:00"; $to = date('Y-m-d H:i:s', strtotime($from) + 86400);
  $rows = DB::table('stage_plan as sp')->join('plan_master as pm','sp.plan_master_id','=','pm.id')->leftJoin('room as r','sp.resourceId','=','r.id')
    ->where('sp.deparment_code',$prod)->where('sp.active',1)->whereBetween('sp.stage_code',[3,7])->whereNotNull('sp.start')
    ->where('sp.start','<',$to)->where('sp.end','>',$from)
    ->orderBy('r.code')->orderBy('sp.start')
    ->get(['sp.id','r.code as room','sp.stage_code','pm.batch','sp.campaign_code','sp.first_in_campaign','sp.start','sp.end','sp.schedualed_at','sp.schedualed_by','sp.overlap']);
  $inside = $rows->filter(fn($r) => $r->start >= $from);   // BẮT ĐẦU trong ngày nghỉ
  echo "== Ngày nghỉ $d: " . $rows->count() . " lô có giờ chạy trong ngày, " . $inside->count() . " lô BẮT ĐẦU trong ngày nghỉ\n";
  foreach ($inside as $r) {
    $hist = DB::table('stage_plan_history')->where('stage_plan_id',$r->id)->orderByDesc('id')->value('type_of_change');
    printf("  %-6s st%d lô %-8s camp=%-8s first=%d %s→%s  xếp lúc %s bởi %s | lịch sử: %s\n", $r->room, $r->stage_code, $r->batch, $r->campaign_code ?? '-', $r->first_in_campaign, substr($r->start,5,11), substr($r->end,5,11), substr($r->schedualed_at,5,11), $r->schedualed_by, mb_substr($hist ?? '-', 0, 40));
  }
}

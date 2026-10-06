<?php
// CHỈ ĐỌC: tải phòng BP 30 ngày tới, và lô chờ S28 chạy được ở phòng BP nào khác (theo quota).
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB; use Carbon\Carbon;
$prod = 'PXV1'; $from = Carbon::now(); $to = Carbon::now()->addDays(30);
echo "Dòng có lịch: " . json_encode(DB::table('stage_plan')->where('deparment_code',$prod)->where('active',1)->where('finished',0)->whereIn('stage_code',[5,6])->selectRaw('stage_code, SUM(start IS NOT NULL) s')->groupBy('stage_code')->pluck('s','stage_code')) . "\n\n";
$rooms = DB::table('room')->where('deparment_code', $prod)->where('stage_code', 6)->pluck('code', 'id');
$rows = DB::table('stage_plan as sp')->join('plan_master as pm','sp.plan_master_id','=','pm.id')
  ->leftJoin('finished_product_category as fpc','pm.product_caterogy_id','=','fpc.id')
  ->where('sp.active',1)->where('sp.stage_code',6)->whereIn('sp.resourceId', $rooms->keys())->whereNotNull('sp.start')
  ->where('sp.start','<',$to)->whereRaw('COALESCE(sp.end_clearning, sp.end) > ?', [$from])
  ->get(['sp.id','sp.plan_master_id','sp.resourceId','sp.start','sp.end','sp.end_clearning','sp.finished','fpc.intermediate_code','pm.batch','sp.Theoretical_yields as qty']);
$quota = DB::table('quota')->where('stage_code',6)->get(['intermediate_code','room_id'])->groupBy('intermediate_code')->map(fn($g)=>$g->pluck('room_id')->unique()->values()->all());
$span = $to->timestamp - $from->timestamp;
printf("%-6s %8s %8s %6s   %s\n", 'Phòng', 'Giờ bận', 'Giờ rảnh', 'Tải%', 'Lần trống đầu tiên ≥ 24h');
foreach ($rooms as $id => $code) {
  $rs = $rows->where('resourceId', $id)->sortBy('start')->values(); $busy = 0; $cursor = $from->timestamp; $firstGap = null;
  foreach ($rs as $r) { $s = max(strtotime($r->start), $from->timestamp); $e = min(strtotime($r->end_clearning ?: $r->end), $to->timestamp);
    if ($s - $cursor >= 86400 && !$firstGap) $firstGap = date('d/m H:i', $cursor) . ' (' . round(($s-$cursor)/3600) . 'h)';
    if ($e > $s) $busy += $e - max($s, $cursor > $s ? $cursor : $s); $cursor = max($cursor, $e); }
  if (!$firstGap && $to->timestamp - $cursor >= 86400) $firstGap = date('d/m H:i', $cursor) . ' → hết kỳ';
  printf("%-6s %8d %8d %5d%%   %s\n", $code, $busy/3600, ($span-$busy)/3600, 100*$busy/$span, $firstGap ?? '-');
}
// Lô BP đang xếp ở S28 / S30: phòng BP khác được phép theo quota
foreach (['S28','S30'] as $code) {
  $rid = $rooms->search($code); $rs = $rows->where('resourceId', $rid)->where('finished', 0);
  $alt = []; foreach ($rs as $r) foreach ($quota[$r->intermediate_code] ?? [] as $q) if ($q != $rid && isset($rooms[$q])) $alt[$rooms[$q]][] = $r->batch;
  echo "\nLô BP xếp ở $code (30 ngày): " . $rs->count() . " lô, " . round($rs->sum(fn($r)=> (strtotime($r->end)-strtotime($r->start))/3600)) . " giờ. Có quota phòng khác:\n";
  foreach ($alt as $c => $b) echo "   $c: " . count($b) . " lô\n";
  if (!$alt) echo "   (không có — sản phẩm chỉ có định mức ở $code)\n";
}

// Thời gian vệ sinh, năng suất BP và giờ rảnh rơi vào ngày nghỉ
$open = $rows->where('finished', 0);
$prodH = $open->sum(fn($r) => (strtotime($r->end) - strtotime($r->start)) / 3600);
$cleanH = $open->sum(fn($r) => $r->end_clearning ? (strtotime($r->end_clearning) - strtotime($r->end)) / 3600 : 0);
$qty = $open->sum('qty');
printf("\nBP 30 ngày: %d lô, sản xuất %d giờ, vệ sinh %d giờ (%.0f%%), năng suất TB %s viên/giờ\n", $open->count(), $prodH, $cleanH, 100*$cleanH/($prodH+$cleanH), number_format($qty/$prodH));
$off = DB::table('off_days')->whereBetween('off_date', [$from->toDateString(), $to->toDateString()])->pluck('off_date')->all();
echo "Ngày nghỉ trong kỳ: " . implode(', ', array_map(fn($d) => date('d/m', strtotime($d)), $off)) . "\n";
$idleOff = 0; foreach ($rooms as $id => $code) foreach ($off as $d) { $ds = strtotime("$d 06:00"); $de = $ds + 86400; $busy = 0;
  foreach ($rows->where('resourceId',$id) as $r) { $s = max($ds, strtotime($r->start)); $e = min($de, strtotime($r->end_clearning ?: $r->end)); if ($e > $s) $busy += $e - $s; }
  $idleOff += (86400 - $busy) / 3600; }
printf("Giờ phòng BP rảnh trong ngày nghỉ (5 phòng): %d giờ ≈ %s viên\n", $idleOff, number_format($idleOff * $qty / $prodH));
// Số lần đổi sản phẩm liên tiếp ở BP (mỗi lần thường là VS-II)
$changes = 0; $same = 0; foreach ($rooms as $id => $code) { $prev = null; foreach ($rows->where('resourceId',$id)->sortBy('start') as $r) { if ($prev !== null) ($prev === $r->intermediate_code ? $same++ : $changes++); $prev = $r->intermediate_code; } }
echo "Chuyển tiếp giữa 2 lô BP liền nhau: đổi sản phẩm $changes lần, cùng sản phẩm $same lần\n";

<?php
// CHỈ ĐỌC: đánh giá phương án "ngưng nguồn lô bao phim, đẩy mạnh lô không bao phim".
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB; use Carbon\Carbon;
$prod = 'PXV1'; $now = Carbon::now(); $to = $now->copy()->addDays(30); $peakEnd = Carbon::parse('2026-10-21 06:00');
$coated = DB::table('stage_plan')->where('stage_code', 6)->where('active', 1)->pluck('plan_master_id')->flip();
$rows = DB::table('stage_plan as sp')->join('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
  ->leftJoin('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
  ->leftJoin('room as r', 'sp.resourceId', '=', 'r.id')
  ->where('sp.deparment_code', $prod)->where('sp.active', 1)->where('pm.active', 1)->whereBetween('sp.stage_code', [3, 7])
  ->get(['sp.id','sp.plan_master_id as pm','sp.stage_code as st','sp.start','sp.end','sp.end_clearning','sp.finished','sp.actual_start','sp.resourceId','r.code as room',
         'fpc.intermediate_code as ic','pm.batch','pm.expected_date','pm.after_weigth_date','pm.after_parkaging_date','sp.Theoretical_yields as qty']);
$byPm = $rows->groupBy('pm');
$h = fn($r) => (strtotime($r->end) - strtotime($r->start)) / 3600;
$started = fn($r) => (int) $r->finished === 1 || $r->actual_start;

// 1. Lô bao phim có ĐH 14–20/10 mà PC/THT chưa chạy: giờ PC/THT giải phóng được nếu dời
$free = [3 => 0, 4 => 0]; $n = 0;
foreach ($byPm as $pm => $rs) { if (!isset($coated[$pm])) continue; $dh = $rs->firstWhere('st', 5);
  if (!$dh || !$dh->start || $dh->start < '2026-10-13 06:00' || $dh->start >= '2026-10-21 06:00') continue; $n++;
  foreach ($rs->whereIn('st', [3, 4]) as $r) if (!$started($r) && $r->start) $free[$r->st] += $h($r); }
printf("1) Lô bao phim ĐH 13–20/10: %d lô; giờ PC giải phóng %d, THT %d (nếu dời toàn bộ)\n", $n, $free[3], $free[4]);

// 2. Lô KHÔNG bao phim chưa chạy ĐH, ĐH đang xếp sau 21/10 hoặc chưa có lịch: có NL kịp không
$quota5 = DB::table('quota')->where('stage_code', 5)->get(['intermediate_code', 'room_id'])->groupBy('intermediate_code')->map(fn($g) => $g->pluck('room_id')->all());
$pool = ['ready' => [0, 0, 0], 'nl_late' => [0, 0, 0], 'no_nl' => [0, 0, 0]];
$poolPcDone = 0;
foreach ($byPm as $pm => $rs) { if (isset($coated[$pm])) continue; $dh = $rs->firstWhere('st', 5); if (!$dh || $started($dh)) continue;
  if ($dh->start && $dh->start < '2026-10-21 06:00') continue;
  $f = $rs->first(); $k = !$f->after_weigth_date ? 'no_nl' : ($f->after_weigth_date <= '2026-10-12' ? 'ready' : 'nl_late');
  $pool[$k][0]++; $pool[$k][1] += $dh->qty; $pool[$k][2] += $dh->start ? $h($dh) : 0;
  $pcDone = $rs->whereIn('st', [3, 4])->every(fn($r) => $started($r)); if ($pcDone) $poolPcDone++; }
echo "2) Lô KHÔNG bao phim có thể kéo lên (ĐH đang sau 21/10 / chưa lịch):\n";
foreach (['ready' => 'Có NL trước 12/10', 'nl_late' => 'NL về sau 12/10', 'no_nl' => 'Chưa có ngày NL'] as $k => $t)
  printf("   %-20s %3d lô, %s viên\n", $t, $pool[$k][0], number_format($pool[$k][1]));
echo "   (trong đó PC/THT đã xong: $poolPcDone lô)\n";

// 3 + 4. Tải phòng 13–20/10 theo nhóm phòng
$load = function ($stage, $filter = null) use ($rows, $now, $peakEnd) { $out = [];
  foreach ($rows->where('st', $stage)->whereNotNull('resourceId')->groupBy('room') as $room => $rs) { if ($filter && !$filter($room)) continue; $busy = 0;
    foreach ($rs as $r) { if (!$r->start) continue; $s = max(strtotime($r->start), strtotime('2026-10-13 06:00')); $e = min(strtotime($r->end_clearning ?: $r->end), $peakEnd->timestamp); if ($e > $s) $busy += $e - $s; }
    $out[$room] = round(100 * $busy / ($peakEnd->timestamp - strtotime('2026-10-13 06:00'))); }
  ksort($out); return $out; };
echo "3) Tải phòng 13–20/10 (%):\n   PC : " . json_encode($load(3)) . "\n   THT: " . json_encode($load(4)) . "\n   ĐH : " . json_encode($load(5)) . "\n   ĐG : " . json_encode($load(7)) . "\n";

// 5. Bao bì cho ĐG và phòng ĐH còn trống (tải < 60%) có quota cho lô không bao phim
$dhLoad = $load(5); $roomIds = DB::table('room')->where('deparment_code', $prod)->where('stage_code', 5)->pluck('id', 'code');
$spare = array_keys(array_filter($dhLoad, fn($p) => $p < 60));
$pkg = ['<=20/10' => 0, '21-31/10' => 0, '>31/10' => 0, 'chưa có' => 0]; $fit = 0; $fitQty = 0; $exp = ['<=31/10' => 0, '11/2026' => 0, '>=12/2026' => 0];
foreach ($byPm as $pm => $rs) { if (isset($coated[$pm])) continue; $dh = $rs->firstWhere('st', 5); if (!$dh || $started($dh) || ($dh->start && $dh->start < '2026-10-21 06:00')) continue;
  $f = $rs->first(); $p = $f->after_parkaging_date;
  $pkg[!$p ? 'chưa có' : ($p <= '2026-10-20' ? '<=20/10' : ($p <= '2026-10-31' ? '21-31/10' : '>31/10'))]++;
  $e = $f->expected_date; $exp[$e <= '2026-10-31' ? '<=31/10' : ($e < '2026-12-01' ? '11/2026' : '>=12/2026')]++;
  foreach ($quota5[$f->ic] ?? [] as $rid) if (in_array(array_search($rid, $roomIds->all()), $spare, true)) { $fit++; $fitQty += $dh->qty; break; } }
echo "5) Lô không bao phim trong nhóm kéo lên được:\n   Ngày nhận bao bì (ĐG chạy được từ): " . json_encode($pkg, JSON_UNESCAPED_UNICODE) . "\n   Ngày cần hàng: " . json_encode($exp, JSON_UNESCAPED_UNICODE) . "\n";
echo "   Phòng ĐH tải < 60% tuần 13–20/10: " . implode(', ', $spare) . "\n   Số lô có định mức ở các phòng đó: $fit lô, " . number_format($fitQty) . " viên\n";

<?php
// CHỈ ĐỌC: tồn chờ ĐH (lô sẽ bao phim) đủ cho ĐH chạy bao lâu nếu PC/THT dừng hẳn.
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB; use Carbon\Carbon;
$prod = 'PXV1'; $now = Carbon::now();
$svc = app(App\Services\WipCoverageService::class);
$d = $svc->ledgers($prod, $now, 30);
$coated = DB::table('stage_plan')->where('stage_code', 6)->where('active', 1)->pluck('plan_master_id')->flip();
$at = $now->format('Y-m-d H:i:s');

// Hàng ĐÃ có trong kho chờ ĐH lúc này (PC/THT đã chạy), chia theo lô bao phim / không
$inStock = ['bp' => 0, 'no' => 0]; $lotsBp = [];
foreach ($d['ledgers']['DH'] ?? [] as $lot) { $q = $svc->lotStockAtMoment($lot, $at); if ($q <= 0) continue;
  $k = isset($coated[$lot['plan_master_id']]) ? 'bp' : 'no'; $inStock[$k] += $q; if ($k === 'bp') $lotsBp[(int)$lot['plan_master_id']] = true; }
printf("Chờ ĐH hiện có: %s viên (lô bao phim %s, không bao phim %s)\n", number_format(array_sum($inStock)), number_format($inStock['bp']), number_format($inStock['no']));

// Lịch ĐH các lô bao phim theo ngày: lô nào dùng hàng ĐÃ có trong kho, lô nào cần PC/THT chạy trong tương lai
$rows = DB::table('stage_plan as sp')->where('sp.deparment_code', $prod)->where('sp.stage_code', 5)->where('sp.active', 1)->where('sp.finished', 0)
  ->whereNull('sp.actual_start')->whereNotNull('sp.start')->where('sp.start', '>=', $at)->where('sp.start', '<', $now->copy()->addDays(30))
  ->orderBy('sp.start')->get(['sp.plan_master_id', 'sp.start', 'sp.Theoretical_yields as qty']);
$byDay = [];
foreach ($rows as $r) { if (!isset($coated[$r->plan_master_id])) continue; $day = date('d/m', strtotime($r->start) - 6*3600);
  $k = isset($lotsBp[$r->plan_master_id]) ? 'kho' : 'pc'; $byDay[$day][$k] = ($byDay[$day][$k] ?? 0) + $r->qty; }
echo "\nĐH chạy lô bao phim theo ngày (viên): [dùng hàng đã có trong kho | cần PC/THT chạy sau hôm nay]\n";
$firstPc = null; $cum = ['kho' => 0, 'pc' => 0];
foreach ($byDay as $day => $v) { $cum['kho'] += $v['kho'] ?? 0; $cum['pc'] += $v['pc'] ?? 0; if (!$firstPc && ($v['pc'] ?? 0) > 0) $firstPc = $day;
  printf("  %s  %12s | %12s\n", $day, number_format($v['kho'] ?? 0), number_format($v['pc'] ?? 0)); }
echo "\nNgày đầu tiên ĐH cần hàng bao phim từ PC/THT chạy sau hôm nay: $firstPc\n";
echo "Cộng 30 ngày: dùng hàng trong kho " . number_format($cum['kho']) . ", cần PC/THT mới " . number_format($cum['pc']) . "\n";

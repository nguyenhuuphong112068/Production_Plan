<?php
// Chỉ đọc: lô Chờ ĐH đang trong kho lúc 06:00 ngày sắp lịch, khi nào ĐH tiêu thụ, có phải lô thẩm định (chờ KCS 5 ngày)
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Carbon\Carbon; use Illuminate\Support\Facades\DB;
$svc = app(App\Services\WipCoverageService::class);
$at = Carbon::now()->setTime(6, 0); $mom = $at->format('Y-m-d H:i:s');
$lots = $svc->ledgers('PXV1', $at, 30)['ledgers']['DH'] ?? [];
$tot = 0; $rows = [];
foreach ($lots as $l) {
    $q = $svc->lotStockAtMoment($l, $mom); if ($q <= 0) continue; $tot += $q;
    $pm = $l['plan_master_id'];
    $dh = DB::table('stage_plan')->where('plan_master_id', $pm)->where('stage_code', 5)->where('active', 1)->first(['start', 'resourceId', 'actual_start']);
    $src = DB::table('stage_plan')->where('plan_master_id', $pm)->where('stage_code', $l['stage_code'])->where('active', 1)->first(['end', 'actual_end', 'finished']);
    $isVal = (int) DB::table('plan_master')->where('id', $pm)->value('is_val');
    $rows[] = [$q, $l['batch'], mb_substr((string) $l['product_name'], 0, 28), $src->finished ? 'xong '.substr($src->actual_end ?? $src->end, 0, 16) : 'đang chạy', $dh->start ? substr($dh->start, 0, 16) : 'CHƯA CÓ LỊCH', $isVal];
}
usort($rows, fn($a, $b) => strcmp($a[4], $b[4]));
printf("Tồn Chờ ĐH lúc %s: %s viên, %d lô\n", $mom, number_format($tot), count($rows));
$b = []; foreach ($rows as $r) { $k = $r[4] === 'CHƯA CÓ LỊCH' ? $r[4] : substr($r[4], 0, 10); $b[$k] = ($b[$k] ?? 0) + $r[0]; $v[$r[5]] = ($v[$r[5]] ?? 0) + $r[0]; }
echo "Theo ngày ĐH bắt đầu tiêu thụ:\n"; foreach ($b as $k => $q) printf("  %s  %s\n", $k, number_format($q));
echo "Lô thẩm định: " . number_format($v[1] ?? 0) . " | thương mại: " . number_format($v[0] ?? 0) . "\n";
foreach ($rows as $r) printf("  %12s %-8s %-28s %-22s ĐH %s %s\n", number_format($r[0]), $r[1], $r[2], $r[3], $r[4], $r[5] ? 'TĐ' : '');

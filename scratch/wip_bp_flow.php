<?php
// Chỉ đọc: dòng chảy kho Chờ BP theo ngày — nhập (ĐH bắt đầu lô bao phim) và xuất (BP bắt đầu)
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Carbon\Carbon;
$svc = app(App\Services\WipCoverageService::class);
$at = Carbon::now()->setTime(6, 0);
$r = $svc->ledgers('PXV1', $at, 30);
foreach ($r['series'] as $k => $v) { if (is_array($v)) echo "series key: $k -> " . json_encode(array_slice((array) $v, 0, 1)) . "\n"; }
$lots = $r['ledgers']['BP'] ?? [];
$in = []; $out = [];
foreach ($lots as $l) {
    if ($l['entry']) { $d = date('Y-m-d', strtotime($l['entry']) - 6 * 3600); $in[$d] = ($in[$d] ?? 0) + $l['qty_dvl']; }
    foreach ($l['exits'] as $e) { $t = $e['at'] ?? $e['start'] ?? null; $q = $e['qty'] ?? $e['qty_dvl'] ?? $l['qty_dvl'];
        if ($t) { $d = date('Y-m-d', strtotime($t) - 6 * 3600); $out[$d] = ($out[$d] ?? 0) + $q; } }
}
echo "mẫu exit: " . json_encode($lots[0]['exits'][0] ?? null) . "\n";
$off = DB::table('off_days')->pluck('off_date')->map(fn($d) => substr($d, 0, 10))->flip();
$sumO = 0; $n = 0;
for ($i = 0; $i < 30; $i++) { $d = $at->copy()->addDays($i)->format('Y-m-d');
    printf("%s %s  nhập(ĐH) %6.1f tr  xuất(BP) %6.1f tr\n", $d, isset($off[$d]) ? 'NGHỈ' : '    ', ($in[$d] ?? 0) / 1e6, ($out[$d] ?? 0) / 1e6);
    if ($i < 21 && ! isset($off[$d])) { $sumO += $out[$d] ?? 0; $n++; } }
printf("BP xuất TB ngày làm việc (3 tuần đầu): %.1f tr\n", $sumO / max(1, $n) / 1e6);

<?php
// Chỉ đọc: thành phần tồn Chờ ĐH theo ngày, tách lô đã vào kho (không lùi được) và lô còn lùi được
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Carbon\Carbon;
$svc = app(App\Services\WipCoverageService::class);
$at = Carbon::now()->setTime(6, 0);
$r = $svc->ledgers($argv[1] ?? 'PXV1', $at, 30);
$lots = $r['ledgers']['DH'] ?? [];
foreach (array_slice($r['days'], 0, 8) as $d) {
    $mom = is_array($d) ? ($d['end'] ?? $d['to'] ?? reset($d)) : $d;
    $mom = (string) $mom;
    $tot = 0; $in = 0; $fut = 0; $kcs = 0; $byExit = [];
    foreach ($lots as $l) {
        $q = $svc->lotStockAtMoment($l, $mom);
        if ($q <= 0) continue;
        $tot += $q;
        if ($l['entry'] && strtotime($l['entry']) < $at->getTimestamp()) $in += $q; else $fut += $q;
        $ex = $l['exits'][0]['at'] ?? ($l['exits'][0] ?? null); $ex = is_array($ex) ? json_encode($ex) : $ex;
        $byExit[substr((string) $ex, 0, 10) ?: 'chưa có lịch ĐH'] = ($byExit[substr((string) $ex, 0, 10) ?: 'chưa có lịch ĐH'] ?? 0) + $q;
    }
    ksort($byExit);
    printf("%s  tồn %s | đã vào kho trước ngày sắp lịch %s | vào sau %s\n", $mom, number_format($tot), number_format($in), number_format($fut));
}
echo "\nMẫu exits: " . json_encode($lots[0]['exits'] ?? null) . "\nMẫu day: " . json_encode($r['days'][0]) . "\n";

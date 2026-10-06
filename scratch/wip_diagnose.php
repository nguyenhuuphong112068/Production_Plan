<?php
// Chẩn đoán (CHỈ ĐỌC): lô nào đang nằm trong "chờ <nhóm>" vào ngày đỉnh, vì sao không lùi được.
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Plugins\WipControl\Services\{WipThrottleService, WipAwareScheduler};
use App\Services\WipCoverageService; use Carbon\Carbon; use Illuminate\Http\Request;

$prod = $argv[1] ?? 'PXV1'; $group = $argv[2] ?? 'BP'; $max = (float) ($argv[3] ?? 50000000);
session()->put('user', ['production_code' => $prod, 'fullName' => 'DIAG', 'id' => 1]);
$req = Request::create('/x', 'POST', ['prev_orderBy' => true]);

$svc = app(WipThrottleService::class);
$ref = new ReflectionClass($svc);
$set = function ($k, $v) use ($ref, $svc) { $p = $ref->getProperty($k); $p->setAccessible(true); $p->setValue($svc, $v); };
$call = function ($m, ...$a) use ($ref, $svc) { $f = $ref->getMethod($m); $f->setAccessible(true); return $f->invoke($svc, ...$a); };
$at = Carbon::now();
$set('productionCode', $prod); $set('request', $req); $set('selectedStep', 7); $set('startDate', $at->copy()->setTime(6,0));
$set('at', $at); $set('limits', [$group => $max]); $set('lockValidation', false); $set('waits', WipAwareScheduler::waitTimes($req));
$set('overdueCampaigns', app(WipAwareScheduler::class)->configure($req, 7, [])->overdueCampaignCodes());

$m = $call('measure');
$viol = array_values(array_filter($m['violations'], fn($v) => $v['group'] === $group));
echo "Ngày vượt ($group > " . number_format($max) . "): " . count($viol) . "\n";
foreach ($viol as $v) echo "  {$v['date']} tồn " . number_format($v['stock']) . "\n";
usort($viol, fn($a, $b) => $b['stock'] <=> $a['stock']);
$peak = $viol[0];
echo "\n=== Ngày đỉnh {$peak['date']} — tồn " . number_format($peak['stock']) . " ===\n";

$consumer = WipThrottleService::CONSUMER_STAGE[$group]; $top = $consumer - 1;
$horizonEnd = end($m['days'])['end']->getTimestamp();
$lots = [];
foreach ($m['ledgers'][$group] as $lot) {
    $q = app(WipCoverageService::class)->lotStockAtMoment($lot, $peak['day_start']); if ($q <= 0) continue;
    $lots[(int)$lot['plan_master_id']] = [$lot, $q];
}
$call('loadInfo', array_keys($lots));
$info = (function () { return $this->info; })->call($svc);
$byReason = []; $rows = [];
foreach ($lots as $pm => [$lot, $q]) {
    $reason = $call('lockOf', $pm, $top);
    $wanted = $delta = null;
    if ($reason === null) {
        $wanted = $call('shiftFor', $lot, $pm, $consumer, $peak['day_start'], $peak, $m, $horizonEnd);
        $delta = min($wanted, $call('maxShift', $pm, $top), $call('dueShift', $pm, $lot, $peak['day_start']));
        $entryTs = strtotime($lot['entry']);
        if ($delta < 3600 || $entryTs + $delta <= strtotime($peak['day_start'])) {
            $reason = $delta < $wanted ? 'Vướng hạn công đoạn / ngày cần hàng' : 'Lùi tới mốc "vừa kịp" vẫn còn trong kho ngày này (công đoạn sau sát ngày)';
        } else $reason = 'LÙI ĐƯỢC';
    }
    $nextExit = null; foreach ($lot['exits'] as $e) if ($e['start'] > $peak['day_start'] && ($nextExit === null || $e['start'] < $nextExit)) $nextExit = $e['start'];
    $byReason[$reason][0] = ($byReason[$reason][0] ?? 0) + 1; $byReason[$reason][1] = ($byReason[$reason][1] ?? 0) + $q;
    $rows[] = [$reason, $info[$pm]['batch'], mb_substr($info[$pm]['product_name'] ?? '', 0, 28), $lot['stage_code'], substr($lot['entry'],0,16), $nextExit ? substr($nextExit,0,16) : '(chưa có lịch)', $q, $delta !== null ? round($delta/3600,1) : null];
}
uasort($byReason, fn($a, $b) => $b[1] <=> $a[1]);
echo "\nPhân loại tồn ngày đỉnh theo lý do (số lô | lượng | % tồn):\n";
foreach ($byReason as $r => [$n, $q]) printf("  %-75s %3d lô  %15s  %5.1f%%\n", $r, $n, number_format($q), 100*$q/$peak['stock']);
usort($rows, fn($a, $b) => [$a[0], $a[4]] <=> [$b[0], $b[4]]);
echo "\nChi tiết (lý do | lô | SP | CĐ nguồn | vào kho | rút ra | lượng | giờ lùi được):\n";
foreach ($rows as $r) printf("  %-40s %-8s %-28s %d %s → %s %12s %s\n", mb_substr($r[0],0,40), $r[1], $r[2], $r[3], $r[4], $r[5], number_format($r[6]), $r[7] ?? '');

// ===== Tổng hợp trên MỌI ngày vượt: lô (duy nhất) theo lý do =====
$all = [];
foreach ($viol as $v) foreach ($m['ledgers'][$group] as $lot) {
    if (app(WipCoverageService::class)->lotStockAtMoment($lot, $v['day_start']) <= 0) continue;
    $all[(int) $lot['plan_master_id']] = $lot;
}
$call('loadInfo', array_keys($all));
$info = (function () { return $this->info; })->call($svc);
$groups = [];
foreach ($all as $pm => $lot) {
    $r = $call('lockOf', $pm, $top) ?? 'LÙI ĐƯỢC (về nguyên tắc)';
    $groups[$r][] = $info[$pm]['batch'] . ' ' . mb_substr($info[$pm]['product_name'] ?? '', 0, 22);
}
echo "
===== Mọi lô nằm trong kho vào ít nhất 1 ngày vượt: " . count($all) . " lô =====
";
uasort($groups, fn($a, $b) => count($b) <=> count($a));
foreach ($groups as $r => $list) { echo "
[" . count($list) . "] $r
"; $prod = []; foreach ($list as $x) { [$b, $n] = explode(' ', $x, 2); $prod[$n][] = $b; } foreach ($prod as $n => $bs) echo "   - $n: " . implode(', ', $bs) . "
"; }

echo "\n===== Sàn tồn không giảm được theo ngày (hàng đã vào kho / đã bắt đầu / VIP / nhạy cảm) =====\n";
foreach ($m['series'][$group] as $i => $pt) {
    $at = $m['days'][$i]['start']->format('Y-m-d H:i:s'); $fixed = 0;
    foreach ($m['ledgers'][$group] as $lot) {
        $q = app(WipCoverageService::class)->lotStockAtMoment($lot, $at); if ($q <= 0) continue;
        $pm = (int) $lot['plan_master_id']; if (!isset($info[$pm])) { $call('loadInfo', [$pm]); $info = (function () { return $this->info; })->call($svc); }
        if ($call('lockOf', $pm, $top) !== null) $fixed += $q;
    }
    if ($pt['stock_dvl'] > 0) printf("  %s  tồn %12s   không giảm được %12s  (%4.1f%%)%s\n", $pt['date'], number_format($pt['stock_dvl']), number_format($fixed), 100*$fixed/$pt['stock_dvl'], $fixed > $max ? '  ← vượt Max dù lùi hết' : '');
}

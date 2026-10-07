<?php
// Chỉ đọc: đường tồn Chờ BP lý thuyết khi lô bao phim chưa chạy ĐH vào kho "vừa kịp" BP (đệm N giờ)
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Carbon\Carbon; use Illuminate\Support\Facades\DB;
$bufH = (float) ($argv[1] ?? 24);
session()->put('user', ['production_code' => 'PXV1', 'fullName' => 'TEST', 'id' => 1, 'userGroup' => 'Admin']);
if ($code = getenv('RESTORE')) {
    DB::beginTransaction();
    register_shutdown_function(function () { while (DB::transactionLevel() > 0) DB::rollBack(); echo "ROLLBACK
"; });
    echo 'Khôi phục tạm ' . $code . ': ' . app(App\Http\Controllers\Pages\Schedual\SchedualController::class)->restore_schedualer(Illuminate\Http\Request::create('/x', 'POST', ['bkc_code' => $code]))->getContent() . "
";
}
$svc = app(App\Services\WipCoverageService::class);
$at = Carbon::now()->setTime(6, 0); $atTs = $at->getTimestamp();
$r = $svc->ledgers('PXV1', $at, 30);
$lots = $r['ledgers']['BP'] ?? [];
$pms = array_column($lots, 'plan_master_id');
$dh = DB::table('stage_plan')->whereIn('plan_master_id', $pms)->where('stage_code', 5)->where('active', 1)->get(['plan_master_id', 'start', 'end', 'actual_start', 'finished'])->keyBy('plan_master_id');
$bp = DB::table('stage_plan')->whereIn('plan_master_id', $pms)->where('stage_code', 6)->where('active', 1)->pluck('start', 'plan_master_id');
$isVal = DB::table('plan_master')->whereIn('id', $pms)->pluck('is_val', 'id');
$horizonEnd = $atTs + 30 * 86400;
$moved = 0; $noBp = 0; $new = [];
foreach ($lots as $l) {
    $pm = $l['plan_master_id']; $d = $dh[$pm] ?? null;
    if ($d && ! $d->actual_start && ! $d->finished && $d->start && strtotime($d->start) >= $atTs) {
        $dur = strtotime($d->end) - strtotime($d->start);
        $wait = ($isVal[$pm] ?? 0) ? 5 * 86400 : 0;
        if (! empty($bp[$pm])) {
            $target = strtotime($bp[$pm]) - $wait - $bufH * 3600 - $dur;
            if ($target > strtotime($d->start)) { $l['entry'] = date('Y-m-d H:i:s', $target); $moved++; }
        } else { $l['entry'] = date('Y-m-d H:i:s', $horizonEnd); $noBp++; }
    }
    $new[] = $l;
}
printf("Đệm %sh: lùi %d lô, %d lô chưa có lịch BP đẩy ra sau khung\n", $bufH, $moved, $noBp);
$peakA = $peakB = 0;
foreach ($r['days'] as $i => $day) {
    if ($i >= 30) break;
    $m = $day['start']->copy()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
    $a = $r['series']['BP'][$i]['stock_dvl']; $b = 0;
    foreach ($new as $l) $b += $svc->lotStockAtMoment($l, $m);
    $peakA = max($peakA, $a); $peakB = max($peakB, $b);
    printf("%s  hiện tại %6.1f tr   vừa kịp %6.1f tr\n", $day['date'], $a / 1e6, $b / 1e6);
}
printf("Đỉnh: hiện tại %.1f tr -> vừa kịp %.1f tr\n", $peakA / 1e6, $peakB / 1e6);

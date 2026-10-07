<?php
// Chỉ đọc (khôi phục tạm bản sao lưu trong transaction rồi rollback):
// mô phỏng quy tắc "ngưng ĐH lô bao phim khi tồn Chờ BP vượt Max, phòng ĐH chạy lô không bao phim hoặc để trống".
//   php scratch/wip_gate_sim.php [Max=50000000] [đệm giờ=24]      RESTORE=<bkc_code>
// Giản lược: chỉ mô phỏng công đoạn ĐH (PC/THT coi như lùi theo), lô giữ phòng & thời lượng cũ,
// lịch BP/ĐG giữ nguyên, không giữ liền campaign, không bắt đầu trong ngày nghỉ.
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

$prod = 'PXV1';
$max = (float) ($argv[1] ?? 50000000);
$buffer = (int) (($argv[2] ?? 24) * 3600);
session()->put('user', ['production_code' => $prod, 'fullName' => 'SIM', 'id' => 1, 'userGroup' => 'Admin']);
if ($code = getenv('RESTORE')) {
    DB::beginTransaction();
    register_shutdown_function(function () { while (DB::transactionLevel() > 0) DB::rollBack(); });
    app(App\Http\Controllers\Pages\Schedual\SchedualController::class)->restore_schedualer(Illuminate\Http\Request::create('/x', 'POST', ['bkc_code' => $code]));
}

$at = Carbon::now()->setTime(6, 0);
$atTs = $at->getTimestamp();
$H = 30;
$horizonEnd = $atTs + $H * 86400;
$day = 86400;

$cov = app(App\Services\WipCoverageService::class);
$ledgerBP = $cov->ledgers($prod, $at, $H)['ledgers']['BP'] ?? [];

$coated = DB::table('stage_plan')->where('deparment_code', $prod)->where('stage_code', 6)->where('active', 1)
    ->pluck('plan_master_id')->mapWithKeys(fn($p) => [(int) $p => true])->all();
$isVal = DB::table('plan_master')->pluck('is_val', 'id')->all();
$wait = fn(int $pm) => ! empty($isVal[$pm]) ? 5 * $day : 0;   // chờ KCS trước ĐH và trước BP (mặc định modal)

// Ngày nghỉ (06:00 → 06:00)
$off = [];
foreach (DB::table('off_days')->where('off_date', '>=', $at->toDateString())->pluck('off_date') as $d) {
    $s = strtotime(substr($d, 0, 10) . ' 06:00:00');
    $off[] = [$s, $s + $day];
}
$skipOff = function (int $t) use ($off) {
    do { $moved = false; foreach ($off as [$a, $b]) if ($t >= $a && $t < $b) { $t = $b; $moved = true; } } while ($moved);
    return $t;
};

// Lô ĐH chưa chạy được mô phỏng
$rows = DB::table('stage_plan')->where('deparment_code', $prod)->where('stage_code', 5)->where('active', 1)
    ->where('finished', 0)->whereNull('actual_start')->whereNotNull('start')->whereNotNull('resourceId')
    ->where('start', '>=', $at->format('Y-m-d H:i:s'))->where('start', '<', date('Y-m-d H:i:s', $horizonEnd + 15 * $day))
    ->get(['id', 'plan_master_id', 'resourceId', 'start', 'end', 'end_clearning', 'predecessor_code']);
$simIds = $rows->pluck('id')->flip()->all();
$pms = $rows->pluck('plan_master_id')->map(fn($p) => (int) $p)->all();
$bpStart = DB::table('stage_plan')->whereIn('plan_master_id', $pms)->where('stage_code', 6)->where('active', 1)->pluck('start', 'plan_master_id')->all();
$predEnd = DB::table('stage_plan')->whereIn('code', $rows->pluck('predecessor_code')->filter()->all())->where('active', 1)
    ->get(['code', 'end', 'actual_end', 'finished'])->mapWithKeys(fn($r) => [$r->code => strtotime($r->finished ? ($r->actual_end ?? $r->end) : $r->end)])->all();
$qty = [];
foreach ($ledgerBP as $l) $qty[(int) $l['plan_master_id']] = (float) $l['qty_dvl'];

// Tồn cố định: mọi lô trong sổ Chờ BP không được mô phỏng
$events = [];   // [t, delta]
$simCoated = [];
foreach ($rows as $r) if (isset($coated[(int) $r->plan_master_id])) $simCoated[(int) $r->plan_master_id] = true;
foreach ($ledgerBP as $l) {
    if (isset($simCoated[(int) $l['plan_master_id']]) || $l['entry'] === null) continue;
    $events[] = [strtotime($l['entry']), $l['qty_dvl']];
    foreach ($l['exits'] as $e) $events[] = [strtotime($e['start']), -$l['qty_dvl'] * $e['weight']];
}

// Khối cố định trong phòng (công đoạn khác, bảo trì, lô đang chạy, lô ngoài phạm vi)
$roomIds = $rows->pluck('resourceId')->unique()->all();
$fixed = [];
foreach (DB::table('stage_plan')->whereIn('resourceId', $roomIds)->where('active', 1)->whereNotNull('start')
    ->whereRaw('COALESCE(end_clearning, end) > ?', [$at->format('Y-m-d H:i:s')])->where('finished', 0)
    ->get(['id', 'resourceId', 'start', 'end', 'end_clearning']) as $f) {
    if (! isset($simIds[$f->id])) $fixed[(int) $f->resourceId][] = [strtotime($f->start), strtotime($f->end_clearning ?: $f->end)];
}

$lots = [];
foreach ($rows as $r) {
    $pm = (int) $r->plan_master_id;
    $s = strtotime($r->start);
    $occ = strtotime($r->end_clearning ?: $r->end) - $s;
    $dur = strtotime($r->end) - $s;
    $c = isset($coated[$pm]);
    $lots[] = [
        'pm' => $pm, 'room' => (int) $r->resourceId, 'old' => $s, 'occ' => $occ, 'dur' => $dur, 'coated' => $c,
        'ready' => max($atTs, ($r->predecessor_code && isset($predEnd[$r->predecessor_code]) ? $predEnd[$r->predecessor_code] : $atTs) + $wait($pm)),
        'bp' => $c && ! empty($bpStart[$pm]) ? strtotime($bpStart[$pm]) : null,
        'qty' => $qty[$pm] ?? 0.0,
    ];
}

function simulate(array $lots, array $events, array $fixed, float $max, int $buffer, bool $force, int $atTs, callable $skipOff, callable $wait): array
{
    $byRoom = [];
    foreach ($lots as $i => $l) $byRoom[$l['room']][] = $i;
    foreach ($byRoom as &$q) usort($q, fn($a, $b) => $lots[$a]['old'] <=> $lots[$b]['old']);
    unset($q);

    $stockAt = function (int $t) use (&$events) { $s = 0.0; foreach ($events as [$et, $d]) if ($et <= $t) $s += $d; return $s; };
    $free = array_fill_keys(array_keys($byRoom), $atTs);
    $done = [];
    $idle = array_fill_keys(array_keys($byRoom), 0);
    $step = 1800;

    for ($guard = 0; $guard < 200000; $guard++) {
        $room = null;
        foreach ($byRoom as $r => $q) if ($q !== [] && ($room === null || $free[$r] < $free[$room])) $room = $r;
        if ($room === null) break;
        $t = $skipOff($free[$room]);
        foreach ($fixed[$room] ?? [] as [$a, $b]) if ($t >= $a && $t < $b) $t = $skipOff($b);
        if ($t !== $free[$room]) { $free[$room] = $t; continue; }

        $fits = function (array $l) use ($t, $room, $fixed) {
            foreach ($fixed[$room] ?? [] as [$a, $b]) if ($t < $b && $t + $l['occ'] > $a) return false;
            return true;
        };
        $stock = $stockAt($t);
        $pick = null;
        // Lô bao phim: tới hạn (bắt buộc) hoặc tồn còn chỗ; hạn gần trước
        $coatedQ = array_values(array_filter($byRoom[$room], fn($i) => $lots[$i]['coated'] && $lots[$i]['ready'] <= $t && $fits($lots[$i])));
        usort($coatedQ, fn($a, $b) => ($lots[$a]['bp'] ?? PHP_INT_MAX) <=> ($lots[$b]['bp'] ?? PHP_INT_MAX));
        foreach ($coatedQ as $i) {
            $l = $lots[$i];
            $latest = $l['bp'] !== null ? $l['bp'] - $wait($l['pm']) - $buffer - $l['dur'] : PHP_INT_MAX;
            if (($force && $latest <= $t + $step) || $stock + $l['qty'] <= $max) { $pick = $i; break; }
        }
        // Không thì lô không bao phim sẵn sàng sớm nhất theo thứ tự cũ
        if ($pick === null) {
            foreach ($byRoom[$room] as $i) if (! $lots[$i]['coated'] && $lots[$i]['ready'] <= $t && $fits($lots[$i])) { $pick = $i; break; }
        }
        if ($pick === null) { $free[$room] = $t + $step; $idle[$room] += $step; continue; }

        $l = $lots[$pick];
        $done[$pick] = $t;
        $byRoom[$room] = array_values(array_diff($byRoom[$room], [$pick]));
        $free[$room] = $t + $l['occ'];
        if ($l['coated'] && $l['qty'] > 0) {
            $events[] = [$t, $l['qty']];
            if ($l['bp'] !== null) $events[] = [max($l['bp'], $t + $l['dur']), -$l['qty']];   // trễ thì BP lùi theo
        }
    }

    return [$done, $events, $idle];
}

$fmt = fn($v) => number_format($v / 1e6, 1) . ' tr';
$report = function (string $name, array $done, array $events, array $idle) use ($lots, $atTs, $horizonEnd, $max, $wait, $day, $fmt) {
    $series = [];
    for ($d = 0; $d < 30; $d++) { $t = $atTs + $d * $day; $s = 0.0; foreach ($events as [$et, $dl]) if ($et <= $t) $s += $dl; $series[date('d/m', $t)] = $s; }
    $over = array_filter($series, fn($s) => $s > $max);
    $late = []; $pulled = 0; $delayedC = 0; $unsched = 0;
    foreach ($lots as $i => $l) {
        if (! isset($done[$i])) { $unsched++; continue; }
        $t = $done[$i];
        if ($l['coated'] && $l['bp'] !== null) {
            $lateBy = $t + $l['dur'] + $wait($l['pm']) - $l['bp'];
            if ($lateBy > 0) $late[] = $lateBy / 3600;
            if ($t > $l['old'] + 3600) $delayedC++;
        }
        if (! $l['coated'] && $t < $l['old'] - 3600) $pulled++;
    }
    echo "\n=== $name\n";
    echo "Tồn Chờ BP 06:00: " . implode(' ', array_map(fn($k, $v) => "$k:" . round($v / 1e6), array_keys($series), $series)) . "\n";
    echo "Đỉnh " . $fmt(max($series)) . ", số ngày vượt " . count($over) . ", tổng vượt " . $fmt(array_sum(array_map(fn($s) => $s - $max, $over))) . "\n";
    echo "Lô bao phim lùi: $delayedC, TRỄ BP: " . count($late) . ($late ? " (trung bình " . round(array_sum($late) / count($late)) . "h, lớn nhất " . round(max($late)) . "h ⇒ BP phải lùi theo)" : '') . "\n";
    echo "Lô không bao phim chạy sớm hơn: $pulled; lô chưa xếp được trong khung: $unsched\n";
    arsort($idle);
    $rooms = DB::table('room')->pluck('code', 'id');
    echo "Giờ phòng ĐH để trống (≥ 24h): " . implode(', ', array_map(fn($r, $h) => ($rooms[$r] ?? $r) . ' ' . round($h / 3600) . 'h', array_keys($idle), $idle)) . "\n";
};

// Lịch hiện tại để so
$cur = []; foreach ($lots as $i => $l) $cur[$i] = $l['old'];
$curEvents = $events;
foreach ($lots as $l) if ($l['coated'] && $l['qty'] > 0) { $curEvents[] = [$l['old'], $l['qty']]; if ($l['bp']) $curEvents[] = [$l['bp'], -$l['qty']]; }
$report('Lịch hiện tại', $cur, $curEvents, []);

[$d, $e, $i] = simulate($lots, $events, $fixed, $max, $buffer, false, $atTs, $skipOff, $wait);
$report('A. Ngưng tuyệt đối khi tồn + lô > Max', $d, $e, array_filter($i, fn($h) => $h >= 86400));
[$d, $e, $i] = simulate($lots, $events, $fixed, $max, $buffer, true, $atTs, $skipOff, $wait);
$report('B. Ngưng, nhưng lô sắp trễ BP (đệm ' . ($buffer / 3600) . 'h) vẫn chạy', $d, $e, array_filter($i, fn($h) => $h >= 86400));

<?php
// Test plugin WipControl trên DB local: chạy trong transaction rồi ROLLBACK.
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\WipControl\Services\WipThrottleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$prod = $argv[1] ?? 'PXV1';
$factor = isset($argv[2]) ? (float) $argv[2] : null;   // null = chỉ đo
$iter = (int) ($argv[3] ?? 5);

session()->put('user', ['production_code' => $prod, 'fullName' => 'TEST WipControl', 'id' => 1, 'userGroup' => 'Admin']);

$req = Request::create('/x', 'POST', [
    'selectedStep' => 'ĐG', 'runType' => 'stage', 'start_date' => Carbon::now()->toDateString(),
    'reason' => 'test', 'selectedDates' => [], 'work_sunday' => false, 'prev_orderBy' => true,
    'limit_mold_change' => true, 'mold_change_tolerance' => 72,
]);

config(['wip_control.debug' => true]);
// RESTORE=<bkc_code>: khôi phục tạm bản sao lưu trong transaction ngoài cùng, cuối script rollback hết
if ($code = getenv('RESTORE')) {
    DB::beginTransaction();
    register_shutdown_function(function () { while (DB::transactionLevel() > 0) DB::rollBack(); echo "ROLLBACK ngoài cùng (bỏ khôi phục tạm)
"; });
    $res = app(App\Http\Controllers\Pages\Schedual\SchedualController::class)->restore_schedualer(Request::create('/x', 'POST', ['bkc_code' => $code]));
    echo "Khôi phục tạm $code: " . $res->getContent() . "
";
}
$svc = app(WipThrottleService::class);
$huge = ['DH' => 1e15, 'BP' => 1e15, 'DG' => 1e15];
$t = microtime(true);
$base = $svc->run(['production_code' => $prod, 'limits' => $huge, 'iterations' => 1, 'lock_validation' => false,
    'selected_step' => 7, 'start_date' => Carbon::now()->setTime(6,0), 'request' => $req]);
echo "Đo: " . round(microtime(true) - $t, 1) . "s, peak mem " . round(memory_get_peak_usage(true)/1048576) . "MB\n";
foreach ($base['before']['groups'] as $g) echo "  {$g['group']}: đỉnh " . number_format($g['peak']) . " ngày {$g['peak_date']}\n";

if ($factor === null) exit;

$limits = [];
if (str_starts_with((string) ($argv[2] ?? ''), '{')) $limits = json_decode($argv[2], true);
else foreach ($base['before']['groups'] as $g) $limits[$g['group']] = round($g['peak'] * $factor);
echo "Max thử: " . json_encode($limits) . "\n";

$snap = DB::table('stage_plan')->where('deparment_code', $prod)->where('active',1)->whereBetween('stage_code',[3,7])
    ->select('id','plan_master_id','stage_code','start','end','resourceId','finished')->get()->keyBy('id');
$integrity = function () use ($prod) {
    $rows = DB::table('stage_plan')->where('deparment_code', $prod)->where('active', 1)->whereNotNull('start')
        ->whereBetween('stage_code', [3, 7])->where('finished', 0)->where('start', '>=', date('Y-m-d H:i:s'))
        ->get(['id', 'resourceId', 'stage_code', 'code', 'predecessor_code', 'start', 'end', 'end_clearning', 'overlap']);
    $byRoom = $rows->groupBy('resourceId'); $overlap = [];
    foreach ($byRoom as $room => $rs) { $rs = $rs->sortBy('start')->values(); for ($i = 1; $i < count($rs); $i++) if ($rs[$i]->start < ($rs[$i-1]->end_clearning ?: $rs[$i-1]->end) && !$rs[$i]->overlap && !$rs[$i-1]->overlap) $overlap[] = $rs[$i-1]->id . '-' . $rs[$i]->id; }
    $byCode = $rows->keyBy('code'); $prec = [];
    foreach ($rows as $r) if ($r->predecessor_code && isset($byCode[$r->predecessor_code]) && $r->start < $byCode[$r->predecessor_code]->end) $prec[] = $r->id;
    return ['overlap' => $overlap, 'prec' => $prec];
};
$ib = $integrity();
DB::beginTransaction();
try {
    $t = microtime(true);
    $r = app(WipThrottleService::class)->run(['production_code' => $prod, 'limits' => $limits, 'iterations' => $iter,
        'lock_validation' => false, 'prioritize_non_coated' => getenv('NOMIX') ? false : true, 'selected_step' => 7, 'start_date' => Carbon::now()->setTime(6,0), 'request' => $req]);
    echo "Chạy: " . round(microtime(true) - $t, 1) . "s, peak mem " . round(memory_get_peak_usage(true)/1048576) . "MB\n";
    $ia = $integrity();
    echo "Đè giờ cùng phòng: trước " . count($ib['overlap']) . ", sau " . count($ia['overlap']) . ", MỚI: " . json_encode(array_values(array_diff($ia['overlap'], $ib['overlap']))) . "
";
    echo "Công đoạn sau bắt đầu trước khi công đoạn trước xong: trước " . count($ib['prec']) . ", sau " . count($ia['prec']) . ", MỚI: " . json_encode(array_values(array_diff($ia['prec'], $ib['prec']))) . "
";
    $GLOBALS['newOverdue'] = $r['new_overdue']; unset($r['skipped']);
    $trace = $r['trace']; unset($r['trace']);
    $cnt = []; foreach ($trace as $t) if (str_starts_with((string) $t[0], 'broken')) { $cnt[$t[0]] = ($cnt[$t[0]] ?? 0) + 1; if (getenv('SHOWBROKEN')) echo "BROKEN " . json_encode($t, JSON_UNESCAPED_UNICODE) . "
"; }
    echo "Lý do trả về: " . json_encode($cnt) . "
";
    if ($ids = getenv('WATCHROWS')) { $w = array_map('intval', explode(',', $ids));
        foreach ($trace as $t) { if ($t[0] === 'room_apply' && array_intersect($w, array_keys($t[3]))) echo "ROOM {$t[1]} phòng {$t[2]}: " . json_encode(array_intersect_key($t[3], array_flip($w))) . "
";
            if (in_array($t[0], ['unschedule','revert']) && array_intersect([10928,11335,11392], (array) ($t[2] ?? $t[1]))) echo "TR " . json_encode($t) . "
"; }
        foreach (DB::table('stage_plan')->whereIn('id', $w)->get(['id','plan_master_id','stage_code','resourceId','start','end','end_clearning','overlap']) as $r) echo "AFTER " . json_encode($r) . "
"; }
    foreach ($trace as $t) if ($t[0] === 'mix_try') echo "MIX thử x{$t[1]}: {$t[2]} lô, trả về {$t[3]}
";
    foreach ($trace as $t) if ($t[0] === 'mix') echo "MIX: lùi {$t[1]} lô bao phim, kéo {$t[2]} lô không bao phim, ngân sách {$t[3]}h / dùng {$t[4]}h (CĐ {$t[5]})
";
    foreach ($trace as $t) { if (in_array(11381, (array) ($t[2] ?? $t[1]))) echo "TRACE 11381: " . json_encode($t) . "
"; }
    $delayed = $r['delayed']; unset($r['delayed']);
    echo json_encode($r, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    echo "Kéo sớm: " . count($r["pulled"] ?? []) . "
"; echo "Lô bị lùi: " . count($delayed) . ", trễ trước đó: " . count(array_filter($delayed, fn($d) => $d["late_before"])) . ", trễ do lùi: " . count(array_filter($delayed, fn($d) => $d['late'])) . ", chưa sắp được: " . count(array_filter($delayed, fn($d) => $d['unscheduled'])) . "\n";
    foreach (array_slice($delayed, 0, 12) as $d) echo "  {$d['batch']} {$d['group']} {$d['old_start']} -> {$d['new_start']} cần {$d['expected_date']}" . ($d['late'] ? ' TRỄ' : '') . ($d['unscheduled'] ? ' CHƯA SẮP' : '') . "\n";
    $watch = array_map('intval', explode(',', getenv('WATCH') ?: ''));
    foreach ($trace as $t) { foreach ($watch as $w) if ($w && in_array($w, (array) ($t[2] ?? $t[1]))) echo "TRACE $w: " . json_encode($t) . "
"; }
    foreach (array_filter($delayed, fn($d) => $d['late'] || $d['unscheduled'] || in_array($d['plan_master_id'], $watch)) as $d) {
        echo "--- {$d['batch']} pm {$d['plan_master_id']} group {$d['group']} cần {$d['expected_date']} seed=" . ($d['seed']?1:0) . "
";
        foreach (DB::table('stage_plan')->where('plan_master_id', $d['plan_master_id'])->where('active',1)->whereBetween('stage_code',[3,7])->orderBy('stage_code')->get() as $r) {
            $o = $snap[$r->id] ?? null;
            echo "   st{$r->stage_code} fin{$r->finished} OLD " . ($o->start ?? '-') . " .. " . ($o->end ?? '-') . " r" . ($o->resourceId ?? '-') . "  NEW " . ($r->start ?? '-') . " .. " . ($r->end ?? '-') . " r" . ($r->resourceId ?? '-') . "
";
        }
    }
    $ov = DB::table('stage_plan')->whereIn('campaign_code', $r_new = $GLOBALS['newOverdue'] ?? [])->count();
} finally {
    DB::rollBack();
    echo "ROLLBACK xong\n";
}

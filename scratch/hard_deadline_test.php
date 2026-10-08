<?php
// Test chen lịch theo hạn HH NL/BB trên DB local: xoá lịch + sắp lịch tự động trong transaction rồi ROLLBACK.
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Pages\Schedual\SchedualController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$prod = $argv[1] ?? 'PXV1';
session()->put('user', ['production_code' => $prod, 'fullName' => 'TEST hard deadline', 'id' => 1, 'userId' => 1, 'department' => 'COMP', 'userGroup' => 'Admin']);
$now = Carbon::now();
if (getenv('NOSKIP')) config(['scheduling.campaign_skip_maintenance' => false]);
if (getenv('RERUNS') !== false) config(['scheduling.hard_deadline_reruns' => (int) getenv('RERUNS')]);
$metrics = function () use ($prod, $now) {
    $rows = DB::table('stage_plan as sp')->join('plan_master as pm', 'pm.id', '=', 'sp.plan_master_id')
        ->where('sp.deparment_code', $prod)->where('sp.active', 1)->where('sp.finished', 0)->whereNotNull('sp.start')
        ->whereBetween('sp.stage_code', [3, 7])->where('sp.start', '>=', $now->format('Y-m-d H:i:s'))
        ->get(['sp.id', 'sp.code', 'sp.predecessor_code', 'sp.resourceId', 'sp.stage_code', 'sp.start', 'sp.end', 'sp.end_clearning', 'sp.overlap',
            'pm.expected_date', 'pm.preperation_before_date', 'pm.blending_before_date', 'pm.forming_before_date', 'pm.coating_before_date', 'pm.parkaging_before_date']);
    $ov = 0;
    foreach ($rows->groupBy('resourceId') as $rs) { $rs = $rs->sortBy('start')->values(); for ($i = 1; $i < count($rs); $i++) if ($rs[$i]->start < ($rs[$i-1]->end_clearning ?: $rs[$i-1]->end) && !$rs[$i]->overlap && !$rs[$i-1]->overlap) { $ov++; if (getenv('OV')) echo "OV " . json_encode([$rs[$i-1], $rs[$i]]) . "
"; } }
    $ends = DB::table('stage_plan')->where('deparment_code', $prod)->whereNotNull('end')->pluck('end', 'code');
    $prec = 0; foreach ($rows as $r) if ($r->predecessor_code && isset($ends[$r->predecessor_code]) && $r->start < $ends[$r->predecessor_code]) $prec++;
    $late = 0; $lateDays = 0; $dl = 0;
    $f = [3 => 'preperation_before_date', 4 => 'blending_before_date', 5 => 'forming_before_date', 6 => 'coating_before_date', 7 => 'parkaging_before_date'];
    foreach ($rows as $r) {
        if ((int) $r->stage_code === 7 && $r->expected_date && substr($r->end, 0, 10) > $r->expected_date) { $late++; $lateDays += (strtotime(substr($r->end, 0, 10)) - strtotime($r->expected_date)) / 86400; }
        $x = $f[(int) $r->stage_code]; if (!empty($r->$x) && substr($r->start, 0, 10) > substr($r->$x, 0, 10)) $dl++;
    }
    $lastEnd = $rows->max('end');
    return "đè giờ $ov, sai thứ tự CĐ $prec, ĐG trễ ngày cần hàng $late lô / " . round($lateDays) . " ngày, trễ hạn PC/THT/ĐH/BP/ĐG trước $dl, lô xếp " . count($rows) . ", kết thúc muộn nhất $lastEnd";
};

$violations = function () use ($prod) {
    $rules = [['after_weigth_date', 3, '>'], ['allow_weight_before_date', 3, '>'], ['expired_material_date', 3, '<'],
        ['expired_packing_date', 7, '<'], ['after_parkaging_date', 7, '>']];
    $rows = DB::table('stage_plan as sp')->join('plan_master as pm', 'pm.id', '=', 'sp.plan_master_id')
        ->where('sp.deparment_code', $prod)->where('sp.active', 1)->where('sp.finished', 0)->whereNotNull('sp.start')
        ->whereNull('sp.actual_start')->whereIn('sp.stage_code', [3, 7])
        ->get(['sp.id', 'sp.stage_code', 'sp.start', 'sp.title', 'pm.batch', 'pm.after_weigth_date', 'pm.allow_weight_before_date', 'pm.expired_material_date', 'pm.expired_packing_date', 'pm.after_parkaging_date']);
    $out = [];
    foreach ($rows as $r) {
        $day = substr($r->start, 0, 10);
        foreach ($rules as [$f, $st, $op]) {
            if ((int) $r->stage_code !== $st || empty($r->$f)) continue;
            $d = substr($r->$f, 0, 10);
            if ($op === '<' ? $d < $day : $d > $day) $out[] = "{$r->id}:{$f} lô {$r->batch} start {$day} mốc {$d}";
        }
    }
    return $out;
};

DB::beginTransaction();
try {
    $c = app(SchedualController::class);
    $c->deActiveAll(Request::create('/x', 'PUT', ['mode' => 'step', 'selectedStep' => 'PC', 'start_date' => $now->format('Y-m-d H:i:s')]));
    echo "Sau xoá: " . count($violations()) . " vi phạm (lô đã có lịch cố định)\n";
    $req = Request::create('/x', 'POST', [
        'selectedStep' => 'ĐG', 'runType' => 'stage', 'start_date' => $now->toDateString(), 'reason' => 'test',
        'selectedDates' => DB::table('off_days')->where('off_date', '>=', date('Y-m-d'))->pluck('off_date')->all(),
        'work_sunday' => false, 'prev_orderBy' => true, 'limit_mold_change' => true, 'mold_change_tolerance' => 72,
        'wt_bleding' => 0, 'wt_forming' => 0, 'wt_coating' => 0, 'wt_blitering' => 0,
    ]);
    $t = microtime(true);
    $res = app(SchedualController::class)->scheduleAll($req);
    echo "Sắp lịch: " . round(microtime(true) - $t, 1) . "s\n";
    $d = json_decode($res->getContent(), true);
    echo "hard_deadline: " . json_encode(array_map(fn($l) => $l['batch'] . ' ' . $l['rule'], $d["hard_deadline"]['late'] ?? []), JSON_UNESCAPED_UNICODE) . "
";
    echo "Chỉ số: " . $metrics() . "
";
    $ms = $d['maintenance_shifted'] ?? [];
    echo "BT-HC-TI đã dời: " . count($ms) . ", dời sớm: " . count(array_filter($ms, fn($m) => $m['to'] < $m['from'])) . ", vẫn trễ hạn BT: " . count(array_filter($ms, fn($m) => $m['late'])) . "
";
    foreach ($ms as $m) if ($m['late'] || $m['to'] < $m['from']) echo "  MAINT " . json_encode($m, JSON_UNESCAPED_UNICODE) . "
";
    foreach (array_slice($d['maintenance_shifted'] ?? [], 0, (int) (getenv('SHOWMAINT') ?: 5)) as $m) echo "  " . json_encode($m, JSON_UNESCAPED_UNICODE) . "
";
    $mo = DB::selectOne("SELECT COUNT(*) c FROM stage_plan m JOIN stage_plan p ON p.resourceId = m.resourceId AND p.stage_code BETWEEN 3 AND 7 AND p.active = 1 AND p.finished = 0 AND p.start IS NOT NULL
        AND p.start < COALESCE(m.end_clearning, m.end) AND COALESCE(p.end_clearning, p.end) > m.start
        WHERE m.stage_code = 8 AND m.active = 1 AND m.finished = 0 AND m.actual_start IS NULL AND m.start >= ? AND m.deparment_code = ?", [$now->format('Y-m-d H:i:s'), $prod])->c;
    echo "BT-HC-TI còn bị lô SX đè: $mo cặp
";
    $v = $violations();
    echo "Vi phạm 5 mốc: " . count($v) . "\n";
    foreach ($v as $x) echo "  $x\n";
    foreach (DB::table('stage_plan')->where('plan_master_id', (int) (getenv('WATCH') ?: 11057))->whereBetween('stage_code', [3, 7])->get(['id', 'stage_code', 'start', 'end', 'resourceId']) as $r) echo "WATCH " . json_encode($r) . "\n";
    if ($room = getenv('ROOM')) foreach (DB::table('stage_plan as sp')->join('plan_master as pm','pm.id','=','sp.plan_master_id')->where('sp.resourceId', (int) $room)->where('sp.end', '>=', $now->format('Y-m-d H:i:s'))->where('sp.start', '<=', $now->copy()->addDays(6)->format('Y-m-d H:i:s'))->orderBy('sp.start')->get(['sp.id','sp.plan_master_id','sp.title','pm.batch','sp.start','sp.end','sp.end_clearning','sp.schedualed_at','pm.expected_date']) as $r) echo "ROOM " . json_encode($r, JSON_UNESCAPED_UNICODE) . "
";
} finally {
    while (DB::transactionLevel() > 0) DB::rollBack();
    echo "ROLLBACK\n";
}

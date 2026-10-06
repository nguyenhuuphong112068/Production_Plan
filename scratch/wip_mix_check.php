<?php
// CHỈ ĐỌC: cơ cấu lô ĐH có / không bao phim theo phòng, và khả năng đổi chỗ.
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB; use Carbon\Carbon;
$prod = $argv[1] ?? 'PXV1'; $days = (int) ($argv[2] ?? 30);
$from = Carbon::now()->format('Y-m-d H:i:s'); $to = Carbon::now()->addDays($days)->format('Y-m-d H:i:s');

$coated = DB::table('stage_plan')->where('stage_code', 6)->where('active', 1)->pluck('plan_master_id')->flip();
$dh = DB::table('stage_plan as sp')
  ->join('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
  ->leftJoin('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
  ->leftJoin('room as r', 'sp.resourceId', '=', 'r.id')
  ->where('sp.deparment_code', $prod)->where('sp.stage_code', 5)->where('sp.active', 1)->where('sp.finished', 0)->whereNull('sp.actual_start')
  ->select('sp.plan_master_id', 'sp.start', 'sp.end', 'sp.resourceId', 'r.code as room', 'fpc.intermediate_code', 'pm.expected_date', 'sp.Theoretical_yields as qty')
  ->get();

$in = $dh->filter(fn($r) => $r->start && $r->start >= $from && $r->start < $to);
$later = $dh->filter(fn($r) => !$r->start || $r->start >= $to);
echo "ĐH chưa chạy, có lịch trong $days ngày: " . $in->count() . " lô | sau đó hoặc chưa có lịch: " . $later->count() . " lô\n\n";

// Phòng ĐH nào sản phẩm được phép chạy (quota)
$quota = DB::table('quota')->where('stage_code', 5)->get(['intermediate_code', 'room_id'])->groupBy('intermediate_code')->map(fn($g) => $g->pluck('room_id')->unique()->all());

printf("%-10s %6s %6s %10s %10s   %s\n", 'Phòng', 'BP', 'Ko BP', 'giờ BP', 'giờ ko BP', 'Lô ko BP (ngoài 30 ngày/chưa lịch) chạy được ở phòng này, cần hàng sớm nhất');
foreach ($in->groupBy('room') as $room => $rows) {
  $rid = $rows->first()->resourceId;
  $c = $rows->filter(fn($r) => isset($coated[$r->plan_master_id])); $n = $rows->reject(fn($r) => isset($coated[$r->plan_master_id]));
  $h = fn($set) => round($set->sum(fn($r) => (strtotime($r->end) - strtotime($r->start)) / 3600));
  $pool = $later->reject(fn($r) => isset($coated[$r->plan_master_id]))->filter(fn($r) => in_array($rid, $quota[$r->intermediate_code] ?? []));
  printf("%-10s %6d %6d %10d %10d   %d lô, cần sớm nhất %s\n", $room, $c->count(), $n->count(), $h($c), $h($n), $pool->count(), $pool->min('expected_date') ?? '-');
}

// ĐG: tồn chờ ĐG hiện tại có đang căng không
$svc = app(App\Services\WipCoverageService::class)->compute($prod, Carbon::now(), $days);
foreach ($svc['groups'] as $g) if (in_array($g['code'] ?? $g['group_code'] ?? '', ['DG','BP','DH'])) echo "\n";
foreach ($svc['groups'] as $g) { $s = array_column($g['series'] ?? $g['daily_series'] ?? [], 'stock_dvl'); if ($s) printf("Tồn chờ %-3s: đầu kỳ %s, đỉnh %s, cuối kỳ %s\n", $g['code'] ?? $g['group_code'] ?? '?', number_format($s[0]), number_format(max($s)), number_format(end($s))); }

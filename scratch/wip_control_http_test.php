<?php
// Gọi thẳng controller của plugin như request thật, trong transaction rồi ROLLBACK.
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Plugins\WipControl\Http\WipControlController; use Illuminate\Http\Request; use Illuminate\Support\Facades\DB;
session()->put('user', ['production_code' => 'PXV1', 'fullName' => 'TEST WipControl', 'id' => 1, 'userGroup' => 'Admin']);
$c = app(WipControlController::class);
DB::beginTransaction();
try {
  echo "settings: " . $c->settings()->getContent() . "\n";
  $base = ['selectedStep' => 'ĐG', 'runType' => 'stage', 'start_date' => date('Y-m-d'), 'reason' => 'test', 'selectedDates' => [], 'prev_orderBy' => true];
  // 1. Không cài Max -> skipped, không đổi lịch
  $r = $c->run(Request::create('/x','POST', $base + ['wip_control' => ['max_dh' => null, 'max_bp' => '', 'max_dg' => null, 'iterations' => 5]]));
  echo "rỗng: " . $r->getContent() . "\n";
  // 2. Chạy PC -> ngoài phạm vi
  $r = $c->run(Request::create('/x','POST', ['selectedStep' => 'PC'] + $base + ['wip_control' => ['max_dg' => 70000000]]));
  echo "PC: " . substr($r->getContent(), 0, 200) . "\n";
  // 3. Chạy thật
  $r = json_decode($c->run(Request::create('/x','POST', $base + ['wip_control' => ['max_dh' => 71350438, 'max_bp' => 48480000, 'max_dg' => 70746450, 'iterations' => 5, 'lock_validation' => false]]))->getContent(), true);
  echo "run: status={$r['status']} vòng={$r['iterations']} lùi=" . count($r['delayed']) . " run_id={$r['run_id']} backup={$r['undo_code']}\n";
  echo "settings sau: " . $c->settings()->getContent() . "\n";
  echo "log: " . json_encode(DB::table('wip_control_runs')->orderByDesc('id')->first(['id','status','iterations','delayed_lots','duration_seconds'])) . "\n";
  echo "lock: " . json_encode(DB::table('scheduling_locks')->where('deparment_code','PXV1')->first(['is_scheduling'])) . "\n";
  echo "not_schedule kẹt: " . DB::table('stage_plan')->where('deparment_code','PXV1')->where('not_schedule',1)->count() . "\n";
} finally { DB::rollBack(); echo "ROLLBACK\n"; }
echo "not_schedule=1 hiện có trước test (đối chiếu): " . DB::table('stage_plan')->where('deparment_code','PXV1')->where('not_schedule',1)->count() . "\n";

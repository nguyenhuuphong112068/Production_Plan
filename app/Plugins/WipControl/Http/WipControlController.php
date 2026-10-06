<?php

namespace App\Plugins\WipControl\Http;

use App\Http\Controllers\Controller;
use App\Plugins\WipControl\Services\WipAwareScheduler;
use App\Plugins\WipControl\Services\WipThrottleService;
use App\Services\SchedulingLock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WipControlController extends Controller
{
    /** Giới hạn đã lưu của phân xưởng, dùng điền sẵn vào modal Sắp lịch tự động */
    public function settings()
    {
        $productionCode = session('user.production_code');

        $row = DB::table('wip_control_settings')->where('production_code', $productionCode)->first();

        return response()->json([
            'enabled'         => true,
            'production_code' => $productionCode,
            'max_dh'          => $row && $row->max_dh_dvl !== null ? (float) $row->max_dh_dvl : null,
            'max_bp'          => $row && $row->max_bp_dvl !== null ? (float) $row->max_bp_dvl : null,
            'max_dg'          => $row && $row->max_dg_dvl !== null ? (float) $row->max_dg_dvl : null,
            'iterations'      => $row->max_iterations ?? (int) config('wip_control.default_iterations', 5),
            'lock_validation' => (bool) ($row->lock_validation ?? false),
            'prioritize_non_coated' => (bool) ($row->prioritize_non_coated ?? true),
            'max_iterations'  => (int) config('wip_control.max_iterations', 10),
        ]);
    }

    /**
     * Chạy sau khi "Sắp lịch tự động" xong. Nhận lại nguyên payload của modal
     * (start_date, selectedStep, runType, wt_*, selectedDates...) kèm wip_control.
     */
    public function run(Request $request)
    {
        $productionCode = session('user.production_code');
        $userName = session('user.fullName');

        $wip = (array) $request->input('wip_control', []);
        $maxIterations = (int) config('wip_control.max_iterations', 10);

        $limits = [
            'DH' => $this->number($wip['max_dh'] ?? null),
            'BP' => $this->number($wip['max_bp'] ?? null),
            'DG' => $this->number($wip['max_dg'] ?? null),
        ];
        $iterations = max(1, min($maxIterations, (int) ($wip['iterations'] ?? config('wip_control.default_iterations', 5))));
        $lockValidation = filter_var($wip['lock_validation'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $prioritize = filter_var($wip['prioritize_non_coated'] ?? true, FILTER_VALIDATE_BOOLEAN);

        // Lưu làm giá trị mặc định cho lần sau, độc lập với wip_stock_limits
        $values = [
            'max_dh_dvl'      => $limits['DH'],
            'max_bp_dvl'      => $limits['BP'],
            'max_dg_dvl'      => $limits['DG'],
            'max_iterations'  => $iterations,
            'lock_validation' => $lockValidation ? 1 : 0,
            'prioritize_non_coated' => $prioritize ? 1 : 0,
            'updated_by'      => $userName,
            'updated_at'      => now(),
        ];

        $settings = DB::table('wip_control_settings')->where('production_code', $productionCode);
        if ($settings->exists()) {
            $settings->update($values);
        } else {
            DB::table('wip_control_settings')->insert($values + [
                'production_code' => $productionCode,
                'created_at'      => now(),
            ]);
        }

        if (array_filter($limits, fn($v) => $v !== null) === []) {
            return response()->json(['status' => 'skipped', 'message' => 'Không cài Max nào, giữ nguyên lịch vừa sắp.']);
        }

        $stepCode = WipAwareScheduler::STEP_CODES[$request->input('selectedStep', 'ĐG')] ?? null;
        if ($request->input('runType', 'stage') !== 'stage' || $stepCode === null) {
            return response()->json([
                'status'  => 'skipped',
                'message' => 'Kiểm soát tồn BTP chỉ áp dụng khi sắp lịch theo công đoạn (PC → ĐG).',
            ]);
        }

        if (! SchedulingLock::acquire($productionCode, $userName)) {
            $lock = SchedulingLock::active($productionCode);

            return response()->json([
                'status'  => 'error',
                'message' => $lock ? SchedulingLock::message($lock) : '⏳ Phân xưởng đang chạy sắp lịch tự động, vui lòng thử lại sau.',
            ], 423);
        }

        set_time_limit(1200);
        ini_set('max_execution_time', 1200);
        // Server mặc định 128M; đo tồn theo từng lô cộng sắp lại lịch cần rộng hơn
        ini_set('memory_limit', '512M');

        $today = Carbon::now()->toDateString();
        $startDate = Carbon::createFromFormat('Y-m-d', $request->input('start_date') ?: $today)->setTime(6, 0, 0);

        try {
            $report = app(WipThrottleService::class)->run([
                'production_code' => $productionCode,
                'limits'          => $limits,
                'iterations'      => $iterations,
                'lock_validation' => $lockValidation,
                'prioritize_non_coated' => $prioritize,
                'selected_step'   => $stepCode,
                'start_date'      => $startDate,
                'request'         => $request,
            ]);
        } catch (\Throwable $e) {
            Log::error('WipControl run lỗi', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            $this->log($productionCode, $userName, $limits, ['status' => 'error', 'message' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        } finally {
            SchedulingLock::release($productionCode);
        }

        $report['limits'] = $limits;
        $report['run_id'] = $this->log($productionCode, $userName, $limits, $report);

        return response()->json($report);
    }

    private function number($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (float) str_replace([',', ' '], '', (string) $value);

        return $value > 0 ? $value : null;
    }

    private function log(string $productionCode, ?string $userName, array $limits, array $report): int
    {
        return (int) DB::table('wip_control_runs')->insertGetId([
            'production_code'  => $productionCode,
            'status'           => $report['status'] ?? 'error',
            'iterations'       => $report['iterations'] ?? 0,
            'delayed_lots'     => count($report['delayed'] ?? []),
            'undo_code'        => $report['undo_code'] ?? null,
            'limits'           => json_encode($limits),
            'report'           => json_encode($report, JSON_UNESCAPED_UNICODE),
            'duration_seconds' => $report['duration_seconds'] ?? 0,
            'created_by'       => $userName,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }
}

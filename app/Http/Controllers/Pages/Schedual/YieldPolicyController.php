<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class YieldPolicyController extends Controller
{
    /**
     * Trang chính: Hiển thị chính sách + sản lượng lý thuyết
     */
    public function index(Request $request)
    {
        $year  = (int)($request->year  ?? now()->year);
        $month = (int)($request->month ?? now()->month);
        $productionCode = session('user')['production_code'];

        // Load chính sách hiện tại
        $policy = DB::table('yield_policies')
            ->where('production_code', $productionCode)
            ->where('year',  $year)
            ->where('month', $month)
            ->first();

        // Load override từng ngày
        $dailyOverrides = [];
        if ($policy) {
            $overrides = DB::table('yield_policy_daily_overrides')
                ->where('policy_id', $policy->id)
                ->get()
                ->keyBy(fn($r) => $r->target_date);
            $dailyOverrides = $overrides->toArray();
        }

        // Tính sản lượng lý thuyết theo ngày
        $startDate = Carbon::create($year, $month, 1)->setTime(6, 0, 0);
        $endDate   = $startDate->copy()->endOfMonth()->addDay()->setTime(6, 0, 0);
        $dailyYield = $this->getDailyYieldTheory($startDate, $endDate, $productionCode);

        // Tổng lý thuyết cả tháng
        $totalTheoryDvl = collect($dailyYield)->sum('theory_dvl');

        // Tính % đạt so với target
        $summary = $this->buildSummary($dailyYield, $policy, $dailyOverrides);

        session()->put(['title' => 'CHÍNH SÁCH SẢN LƯỢNG']);

        $totalWorkingDays = collect($dailyYield)->where('is_off_day', false)->count();
        $totalOffDays     = collect($dailyYield)->where('is_off_day', true)->count();

        return view('pages.Schedual.yield_policy.index', [
            'year'            => $year,
            'month'           => $month,
            'policy'          => $policy,
            'dailyOverrides'  => $dailyOverrides,
            'dailyYield'      => $dailyYield,
            'totalTheoryDvl'  => round($totalTheoryDvl, 0),
            'summary'         => $summary,
            'totalWorkingDays'=> $totalWorkingDays,
            'totalOffDays'    => $totalOffDays,
            'productionName'  => session('user')['production_name'] ?? $productionCode,
        ]);
    }

    /**
     * Lưu/cập nhật chính sách target tháng
     */
    public function store(Request $request)
    {
        $request->validate([
            'year'             => 'required|integer|min:2020|max:2099',
            'month'            => 'required|integer|min:1|max:12',
            'target_month_dvl' => 'nullable|numeric|min:0',
            'target_daily_dvl' => 'nullable|numeric|min:0',
            'min_submit_pct'   => 'nullable|numeric|min:0|max:100',
        ]);

        $productionCode = session('user')['production_code'];
        $user           = session('user')['fullName'] ?? session('user')['userName'] ?? 'System';

        DB::table('yield_policies')->updateOrInsert(
            [
                'production_code' => $productionCode,
                'year'            => $request->year,
                'month'           => $request->month,
            ],
            [
                'target_month_dvl' => $request->target_month_dvl ?: null,
                'target_daily_dvl' => $request->target_daily_dvl ?: null,
                'min_submit_pct'   => $request->min_submit_pct !== null && $request->min_submit_pct !== '' ? (float)$request->min_submit_pct : 100,
                'note'             => $request->note,
                'updated_by'       => $user,
                'created_by'       => $user,
                'updated_at'       => now(),
                'created_at'       => now(),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Đã lưu chính sách sản lượng!']);
    }

    /**
     * Lưu override target theo ngày cụ thể
     */
    public function storeDaily(Request $request)
    {
        $request->validate([
            'year'            => 'required|integer',
            'month'           => 'required|integer',
            'target_date'     => 'required|date',
            'target_qty_dvl'  => 'nullable|numeric|min:0',
        ]);

        $productionCode = session('user')['production_code'];
        $user           = session('user')['fullName'] ?? session('user')['userName'] ?? 'System';

        // Tìm hoặc tạo policy của tháng
        $policy = DB::table('yield_policies')
            ->where('production_code', $productionCode)
            ->where('year',  $request->year)
            ->where('month', $request->month)
            ->first();

        if (!$policy) {
            $policyId = DB::table('yield_policies')->insertGetId([
                'production_code' => $productionCode,
                'year'            => $request->year,
                'month'           => $request->month,
                'created_by'      => $user,
                'updated_by'      => $user,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        } else {
            $policyId = $policy->id;
        }

        DB::table('yield_policy_daily_overrides')->updateOrInsert(
            [
                'policy_id'   => $policyId,
                'target_date' => $request->target_date,
            ],
            [
                'target_qty_dvl' => $request->target_qty_dvl ?: null,
                'updated_by'     => $user,
                'updated_at'     => now(),
                'created_at'     => now(),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Đã lưu target ngày ' . $request->target_date]);
    }

    /**
     * Kiểm tra sản lượng lý thuyết so với chính sách trước khi submit lịch
     */
    public function checkYieldPolicy(Request $request)
    {
        $year  = (int)($request->year  ?? now()->year);
        $month = (int)($request->month ?? now()->month);
        $productionCode = session('user')['production_code'];

        // Lấy chính sách tháng
        $policy = DB::table('yield_policies')
            ->where('production_code', $productionCode)
            ->where('year',  $year)
            ->where('month', $month)
            ->first();

        // Ngưỡng submit: lấy từ policy, mặc định 100%
        $minPct = (float)($policy->min_submit_pct ?? 100);

        // Nếu chưa cài chính sách => cho phép submit
        if (!$policy || (!$policy->target_daily_dvl && !$policy->target_month_dvl)) {
            return response()->json([
                'can_submit'    => true,
                'min_submit_pct'=> $minPct,
                'message'       => 'Chưa thiết lập chính sách sản lượng, cho phép submit.',
            ]);
        }

        // Lấy override từng ngày
        $dailyOverrides = DB::table('yield_policy_daily_overrides')
            ->where('policy_id', $policy->id)
            ->get()
            ->keyBy('target_date');

        // Tính sản lượng lý thuyết
        $startDate = Carbon::create($year, $month, 1)->setTime(6, 0, 0);
        $endDate   = $startDate->copy()->endOfMonth()->addDay()->setTime(6, 0, 0);
        $dailyYield = $this->getDailyYieldTheory($startDate, $endDate, $productionCode);

        $violations = []; // Danh sách ngày không đạt

        foreach ($dailyYield as $day) {
            if ($day['is_off_day']) continue; // Bỏ qua ngày nghỉ

            $override   = $dailyOverrides[$day['date']] ?? null;
            $targetDvl  = $override?->target_qty_dvl ?? $policy->target_daily_dvl ?? null;

            if (!$targetDvl || $targetDvl <= 0) continue; // Không có target => bỏ qua

            $pct = $day['theory_dvl'] / $targetDvl * 100;
            if ($pct < $minPct) {
                $violations[] = [
                    'date'       => $day['date'],
                    'theory_dvl' => round($day['theory_dvl'], 0),
                    'target_dvl' => $targetDvl,
                    'pct'        => round($pct, 1),
                ];
            }
        }

        // Kiểm tra tổng tháng — luôn yêu cầu đạt 100% (cứng)
        $totalTheory  = collect($dailyYield)->sum('theory_dvl');
        $targetMonth  = $policy->target_month_dvl ?? null;
        $monthOk      = !$targetMonth || ($totalTheory / $targetMonth * 100 >= 100);
        $monthPct     = $targetMonth ? round($totalTheory / $targetMonth * 100, 1) : null;

        if (count($violations) > 0 || !$monthOk) {
            return response()->json([
                'can_submit'      => false,
                'violations'      => $violations,
                'month_ok'        => $monthOk,
                'month_pct'       => $monthPct,
                'total_theory'    => round($totalTheory, 0),
                'target_month'    => $targetMonth,
                'min_submit_pct'  => $minPct,
                'message'         => 'Sản lượng lý thuyết chưa đáp ứng chính sách.',
            ]);
        }

        return response()->json([
            'can_submit'     => true,
            'month_pct'      => $monthPct,
            'total_theory'   => round($totalTheory, 0),
            'min_submit_pct' => $minPct,
            'message'        => 'Sản lượng lý thuyết đáp ứng chính sách.',
        ]);
    }

    /**
     * Tính sản lượng lý thuyết từng ngày trong tháng
     */
    private function getDailyYieldTheory(Carbon $startDate, Carbon $endDate, string $productionCode): array
    {
        $result = [];
        $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate);

        $offDays = DB::table('off_days')
            ->whereDate('off_date', '>=', $startDate->format('Y-m-d'))
            ->whereDate('off_date', '<', $endDate->format('Y-m-d'))
            ->pluck('off_date')
            ->toArray();

        foreach ($period as $date) {
            $date     = Carbon::instance($date);
            $dayStart = $date->copy()->setTime(6, 0, 0);
            $dayEnd   = $date->copy()->addDay()->setTime(6, 0, 0);

            $dayStartStr = $dayStart->format('Y-m-d H:i:s');
            $dayEndStr   = $dayEnd->format('Y-m-d H:i:s');

            // SL lý thuyết ĐVL (chỉ tính ở stage_code = 7 - Công đoạn cuối ĐGSC-ĐGTC)
            $dvlRow = DB::table('stage_plan as sp')
                ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
                ->join('room as r', 'sp.resourceId', '=', 'r.id')
                ->where('sp.deparment_code', $productionCode)
                ->where('r.deparment_code', $productionCode)
                ->whereNotNull('sp.start')
                ->where('sp.stage_code', 7)
                ->whereRaw('(sp.start <= ? AND sp.end >= ?)', [$dayEndStr, $dayStartStr])
                ->selectRaw("
                    ROUND(SUM(
                        (sp.Theoretical_yields * COALESCE(plan_master.percent_parkaging, 1)) *
                        TIME_TO_SEC(TIMEDIFF(LEAST(sp.end, '$dayEndStr'), GREATEST(sp.start, '$dayStartStr'))) /
                        NULLIF(TIME_TO_SEC(TIMEDIFF(sp.end, sp.start)), 0)
                    ), 2) as total_dvl
                ")
                ->first();

            $result[] = [
                'date'       => $date->format('Y-m-d'),
                'date_label' => $date->format('d/m'),
                'dow'        => $date->locale('vi')->dayName,
                'is_off_day' => in_array($date->format('Y-m-d'), $offDays),
                'theory_dvl' => round((float)($dvlRow->total_dvl ?? 0), 2),
            ];
        }

        return $result;
    }

    /**
     * Build summary stats
     */
    private function buildSummary(array $dailyYield, $policy, array $dailyOverrides): array
    {
        $workingDays = collect($dailyYield)->where('is_off_day', false)->all();
        $totalDays   = count($workingDays);
        $daysOk      = 0;
        $daysWarn    = 0;
        $daysFail    = 0;
        $daysNoTarget = 0;

        $minPct = (float)($policy->min_submit_pct ?? 100);

        foreach ($workingDays as $day) {
            $override = $dailyOverrides[$day['date']] ?? null;
            $targetDvl = $override?->target_qty_dvl ?? $policy?->target_daily_dvl ?? null;

            if ($targetDvl === null || $targetDvl <= 0) {
                $daysNoTarget++;
                continue;
            }

            $pct = $day['theory_dvl'] / $targetDvl * 100;
            if ($pct >= 100)       $daysOk++;
            elseif ($pct >= $minPct) $daysWarn++;
            else                   $daysFail++;
        }

        return [
            'total_days'    => $totalDays,
            'days_ok'       => $daysOk,
            'days_warn'     => $daysWarn,
            'days_fail'     => $daysFail,
            'days_no_target'=> $daysNoTarget,
        ];
    }
}

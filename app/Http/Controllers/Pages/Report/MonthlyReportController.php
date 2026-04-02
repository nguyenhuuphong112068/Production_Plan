<?php

namespace App\Http\Controllers\Pages\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlyReportController extends Controller
{
    public function index(Request $request)
    {
        $reportedMonth = (int) ($request->month ?? now()->month);
        $reportedYear  = (int) ($request->year ?? now()->year);

        // 1️⃣ Xác định đầu & cuối tháng
        $startMonth = Carbon::create($reportedYear, $reportedMonth, 1)->startOfMonth();
        $endMonth   = $startMonth->copy()->endOfMonth();

        // Xác định tuần
        // 2️⃣ Lấy danh sách off days trong tháng
        $offDays = DB::table('off_days')
            ->whereBetween('off_date', [
                $startMonth->toDateString(),
                $endMonth->toDateString()
            ])
            ->pluck('off_date')
            ->toArray();

        $totalWorkingDays = 0;
        $current = $startMonth->copy();

        // 3️⃣ Lặp từng ngày trong tháng
        while ($current <= $endMonth) {

            if (
                !$current->isWeekend() && // bỏ Thứ 7 & CN
                !in_array($current->toDateString(), $offDays) // bỏ ngày nghỉ lễ
            ) {
                $totalWorkingDays++;
            }

            $current->addDay();
        }

        //dd ();


        $start = Carbon::create($reportedYear, $reportedMonth, 1)
            ->startOfDay()
            ->setTime(6, 0, 0);

        $end = (clone $start)->addMonth();

        $check = DB::table('room_sheet_month')->where('reported_month', $reportedMonth)->where('reported_year', $reportedYear)->exists();

        if (!$check) {
            $rooms = DB::table('room')
                ->where('active', 1)
                //->whereNotNull('capacity')
                ->select(
                    'id as room_id',
                    'capacity',
                    DB::raw($reportedMonth . ' as reported_month'),
                    DB::raw("'" . $reportedYear . "' as reported_year")
                )
                ->get();

            DB::table('room_sheet_month')->insert(
                $rooms->map(fn($r) => (array) $r)->toArray()
            );
        }



        $time = $this->getOperatedTime($start, $end);
        $yield_actual =    $this->yield_actual($start, $end);
        $yield_theory =   $this->yield_theory($start, $end);

        $datas = DB::table('room_sheet_month')
            ->whereNotNull('room_sheet_month.capacity')
            ->where('deparment_code', session('user')['production_code'])
            ->where('reported_year', $reportedYear)
            ->where('reported_month', $reportedMonth)
            ->leftJoin('room', 'room_sheet_month.room_id', '=', 'room.id')
            ->select(
                'room_sheet_month.id',
                'room_sheet_month.room_id',
                'room_sheet_month.reported_month',
                'room_sheet_month.reported_year',
                'room.name as room_name',
                'room.code as room_code',
                'room.stage_code',
                'room.main_equiment_name',

                DB::raw('AVG(room_sheet_month.capacity) as capacity'),
                DB::raw('AVG(room_sheet_month.shift) as shift'),
                DB::raw('AVG(room_sheet_month.day_in_month) as day_in_month')
            )
            ->groupBy(
                'room_sheet_month.id',
                'room_sheet_month.room_id',
                'room_sheet_month.reported_month',
                'room_sheet_month.reported_year',
                'room.name',
                'room.code',
                'room.stage_code',
                'room.main_equiment_name'
            )
            ->orderBy('room.stage_code')
            ->orderBy('room.code')
            ->get();


        $timeByResource = collect($time)->keyBy('resourceId');
        $actualByResource = collect($yield_actual)->keyBy('resourceId');
        $theoryByResource = collect($yield_theory)->keyBy('resourceId');

        $datas = $datas->map(function ($row) use (
            $timeByResource,
            $actualByResource,
            $theoryByResource,
            $totalWorkingDays
        ) {
            $resourceId = (int) $row->room_id;

            $time   = $timeByResource->get($resourceId);

            $actual = $actualByResource->get($resourceId);
            $theory = $theoryByResource->get($resourceId);

            /* ================= TIME ================= */
            $row->total_hours    = $time['total_hours']    ?? 0;
            $row->work_hours     = $time['work_hours']     ?? 0;
            $row->cleaning_hours = $time['cleaning_hours'] ?? 0;
            $row->busy_hours     = $time['busy_hours']    ?? 0;
            $row->free_hours     = $time['free_hours']    ?? 0;
            $row->day_in_months     = $totalWorkingDays    ?? 0;

            /* ================= YIELD ================= */
            $row->yield_actual = $actual->total_qty ?? 0;
            $row->yield_theory = $theory->total_qty ?? 0;
            // /* ================= KPI ================= */
            $row->output_thery = $row->work_hours > 0
                ? round($row->work_hours * $row->capacity, 2)
                : 0;

            $row->OEE = $row->output_thery > 0 ? round(($row->yield_actual / $row->output_thery) * 100) : 0;

            $row->H_in_month = $row->shift *  $totalWorkingDays * 8;

            $row->loading = $row->H_in_month > 0 ? round($row->work_hours /  $row->H_in_month * 100, 2) : 0;

            $row->TEEP = round($row->loading * $row->OEE / 100, 2);

            return $row;
        });
        $datas = $datas->sortBy('stage_code');
        session()->put([
            'title' => "BÁO CÁO THÁNG $reportedMonth (" .
                $start->format('H:i d/m/Y') . " - " .
                $end->format('H:i d/m/Y') . ")"
        ]);
        //dd ($datas);
        return view('pages.report.monthly_report.list', [
            'datas' => $datas
        ]);
    }

    public function getOperatedTime($startDate, $endDate)
    {
        //dd ($startDate, $endDate);
        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);

        $startTs = $start->timestamp;
        $endTs   = $end->timestamp;

        $totalSeconds = $start->diffInSeconds($end);

        // 🔥 Join yields
        $rows = DB::table('stage_plan as sp')
            ->join('yields as y', 'sp.id', '=', 'y.stage_plan_id')

            ->select(
                'sp.resourceId',
                'y.start as yield_start',
                'y.end as yield_end',
                'sp.actual_end',
                'sp.actual_end_clearning'
            )
            ->where('sp.deparment_code', session('user')['production_code'])

            // overlap theo yield time
            ->whereRaw(
                'GREATEST(y.start, ?) < LEAST(y.end, ?)',
                [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]
            )

            ->orderBy('sp.resourceId')
            ->orderBy('y.start')
            ->get();


        // Merge function giữ nguyên
        $mergeIntervals = function (array $intervals) {
            if (empty($intervals)) return [];

            usort($intervals, fn($a, $b) => $a[0] <=> $b[0]);

            $merged = [$intervals[0]];

            foreach ($intervals as $current) {
                $lastIndex = count($merged) - 1;

                if ($current[0] <= $merged[$lastIndex][1]) {
                    $merged[$lastIndex][1] = max(
                        $merged[$lastIndex][1],
                        $current[1]
                    );
                } else {
                    $merged[] = $current;
                }
            }

            return $merged;
        };

        $result = [];

        foreach ($rows->groupBy('resourceId') as $resourceId => $items) {

            $workIntervals  = [];
            $cleanIntervals = [];

            foreach ($items as $r) {

                // ✅ WORK từ yields
                if ($r->yield_start && $r->yield_end) {
                    $workIntervals[] = [
                        max(Carbon::parse($r->yield_start)->timestamp, $startTs),
                        min(Carbon::parse($r->yield_end)->timestamp, $endTs),
                    ];
                }

                // ✅ CLEANING giữ nguyên
                if ($r->actual_end && $r->actual_end_clearning) {
                    $cleanIntervals[] = [
                        max(Carbon::parse($r->actual_end)->timestamp, $startTs),
                        min(Carbon::parse($r->actual_end_clearning)->timestamp, $endTs),
                    ];
                }
            }

            $workMerged  = $mergeIntervals($workIntervals);
            $cleanMerged = $mergeIntervals($cleanIntervals);

            $workSeconds = array_sum(
                array_map(fn($i) => max(0, $i[1] - $i[0]), $workMerged)
            );

            $cleanSeconds = array_sum(
                array_map(fn($i) => max(0, $i[1] - $i[0]), $cleanMerged)
            );

            $busySeconds = $workSeconds + $cleanSeconds;
            $freeSeconds = max(0, $totalSeconds - $busySeconds);

            $result[] = [
                'resourceId'      => $resourceId,
                'total_hours'     => round($totalSeconds / 3600, 2),
                'work_hours'      => round($workSeconds / 3600, 2),
                'cleaning_hours'  => round($cleanSeconds / 3600, 2),
                'busy_hours'      => round($busySeconds / 3600, 2),
                'free_hours'      => round($freeSeconds / 3600, 2),
            ];
        }

        return collect($result);
    }

    public function yield_theory($startDate, $endDate)
    {
        $startDateStr = \Carbon\Carbon::parse($startDate)->startOfDay()->format('Y-m-d H:i:s');
        $endDateStr   = \Carbon\Carbon::parse($endDate)->endOfDay()->format('Y-m-d H:i:s');

        $result = DB::table("stage_plan as sp")
            ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
            ->whereNotNull('sp.start')
            ->whereNotNull('sp.resourceId')
            ->where('sp.deparment_code', session('user')['production_code'])
            // 🔥 Lấy tất cả các giai đoạn giao thoa với khoảng thời gian báo cáo
            ->whereRaw('(sp.start < ? AND sp.end > ?)', [$endDateStr, $startDateStr])
            ->select(
                "sp.resourceId",
                DB::raw("
                    SUM(
                        (CASE WHEN plan_master.only_parkaging = 1 THEN sp.Theoretical_yields * plan_master.percent_parkaging ELSE sp.Theoretical_yields END) *
                        TIME_TO_SEC(
                            TIMEDIFF(
                                LEAST(sp.end, '$endDateStr'),
                                GREATEST(sp.start, '$startDateStr')
                            )
                        ) /
                        NULLIF(TIME_TO_SEC(TIMEDIFF(sp.end, sp.start)), 0)
                    ) as total_qty
                ")
            )
            ->groupBy("sp.resourceId")
            ->get();

        return collect($result)->map(function ($item) {
            return (object)[
                'resourceId' => $item->resourceId,
                'total_qty'  => round($item->total_qty, 2)
            ];
        });
    }

    public function yield_actual($startDate, $endDate)
    {
        $startDateStr = \Carbon\Carbon::parse($startDate)->startOfDay()->format('Y-m-d H:i:s');
        $endDateStr   = \Carbon\Carbon::parse($endDate)->endOfDay()->format('Y-m-d H:i:s');

        $result = DB::table('stage_plan as sp')
            ->join('yields as y', 'sp.id', '=', 'y.stage_plan_id')
            ->whereNotNull('sp.resourceId')
            ->where('sp.deparment_code', session('user')['production_code'])
            // 🔥 chỉ lấy phần overlap của từng record yield
            ->whereRaw('(y.start < ? AND y.end > ?)', [$endDateStr, $startDateStr])
            ->select(
                'sp.resourceId',
                DB::raw("
                    SUM(
                        y.yield *
                        TIME_TO_SEC(
                            TIMEDIFF(
                                LEAST(y.end, '$endDateStr'),
                                GREATEST(y.start, '$startDateStr')
                            )
                        ) /
                        NULLIF(TIME_TO_SEC(TIMEDIFF(y.end, y.start)), 0)
                    ) as total_qty
                ")
            )
            ->groupBy('sp.resourceId')
            ->get();

        return collect($result)->map(function ($item) {
            return (object)[
                'resourceId' => $item->resourceId,
                'total_qty'  => round($item->total_qty, 2)
            ];
        });
    }

    public function updateInput(Request $request)
    {
        DB::table('room_sheet_month')
            ->where('id', $request->id)
            ->update([
                $request->name => $request->time
            ]);
        return response()->json(['success' => true]);
    }
}

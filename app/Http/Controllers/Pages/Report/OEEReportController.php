<?php

namespace App\Http\Controllers\Pages\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OEEReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->startDate ?? now()->startOfMonth()->toDateString();
        $endDate   = $request->endDate ?? now()->toDateString();

        $start = Carbon::parse($startDate)->startOfDay()->setTime(6, 0, 0);
        $end   = Carbon::parse($endDate)->endOfDay()->addHours(6); // To match the 6 AM next day logic if needed, or just end of day

        // For OEE report, we use the literal range provided
        $startRange = Carbon::parse($startDate)->startOfDay();
        $endRange   = Carbon::parse($endDate)->endOfDay();

        // 1️⃣ Lấy danh sách off days trong khoảng
        $offDays = DB::table('off_days')
            ->whereBetween('off_date', [
                $startRange->toDateString(),
                $endRange->toDateString()
            ])
            ->pluck('off_date')
            ->toArray();

        $totalWorkingDays = 0;
        $current = $startRange->copy();

        while ($current <= $endRange) {
            if (!$current->isWeekend() && !in_array($current->toDateString(), $offDays)) {
                $totalWorkingDays++;
            }
            $current->addDay();
        }

        // Fetch metrics
        $time = $this->getOperatedTime($start, $end);
        $yield_actual = $this->yield_actual($start, $end);
        $yield_theory = $this->yield_theory($start, $end);

        // Fetch room settings from room_sheet_month based on startDate's month
        $reportedMonth = (int) Carbon::parse($startDate)->month;
        $reportedYear  = (int) Carbon::parse($startDate)->year;

        $datas = DB::table('room')
            ->where('room.active', 1)
            ->where('room.deparment_code', session('user')['production_code'])
            ->whereNotIn('room.stage_code', [1, 2, 8])
            ->leftJoin('room_sheet_month', function ($join) use ($reportedMonth, $reportedYear) {
                $join->on('room.id', '=', 'room_sheet_month.room_id')
                    ->where('room_sheet_month.reported_month', '=', $reportedMonth)
                    ->where('room_sheet_month.reported_year', '=', $reportedYear);
            })
            ->select(
                'room.id as room_id',
                'room.name as room_name',
                'room.code as room_code',
                'room.stage_code',
                'room.main_equiment_name',
                DB::raw('COALESCE(room_sheet_month.capacity, room.capacity, 0) as capacity'),
                DB::raw('COALESCE(room_sheet_month.shift, 0) as shift')
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

            $row->total_hours    = $time['total_hours']    ?? 0;
            $row->work_hours     = $time['work_hours']     ?? 0;
            $row->cleaning_hours = $time['cleaning_hours'] ?? 0;
            $row->busy_hours     = $time['busy_hours']    ?? 0;
            $row->free_hours     = $time['free_hours']    ?? 0;
            $row->day_in_range   = $totalWorkingDays;

            $row->yield_actual = $actual->total_qty ?? 0;
            $row->yield_theory = $theory->total_qty ?? 0;

            $row->output_theory = $row->work_hours > 0
                ? round($row->work_hours * $row->capacity, 2)
                : 0;

            $row->OEE = $row->output_theory > 0 ? round(($row->yield_actual / $row->output_theory) * 100) : 0;
            $row->H_total = $row->shift * $totalWorkingDays * 8;
            $row->loading = $row->H_total > 0 ? round($row->work_hours / $row->H_total * 100, 2) : 0;
            $row->TEEP = round($row->loading * $row->OEE / 100, 2);

            return $row;
        });

        session()->put([
            'title' => "BÁO CÁO OEE (" . Carbon::parse($startDate)->format('d/m/Y') . " - " . Carbon::parse($endDate)->format('d/m/Y') . ")"
        ]);

        return view('pages.report.oee_report.list', [
            'datas' => $datas,
            'startDate' => $startDate,
            'endDate' => $endDate
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
        // Sử dụng luôn object Carbon truyền vào để giữ nguyên mốc 06:00:00 nếu có
        $startDateStr = $startDate instanceof \Carbon\Carbon ? $startDate->toDateTimeString() : \Carbon\Carbon::parse($startDate)->toDateTimeString();
        $endDateStr   = $endDate instanceof \Carbon\Carbon ? $endDate->toDateTimeString() : \Carbon\Carbon::parse($endDate)->toDateTimeString();

        $result = DB::table("stage_plan as sp")
            ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
            ->where('sp.active', 1) // 🔥 Quan trọng: Chỉ lấy những lô đang hoạt động
            ->whereNotNull('sp.start')
            ->whereNotNull('sp.resourceId')
            ->where('sp.deparment_code', session('user')['production_code'])
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
        $startDateStr = $startDate instanceof \Carbon\Carbon ? $startDate->toDateTimeString() : \Carbon\Carbon::parse($startDate)->toDateTimeString();
        $endDateStr   = $endDate instanceof \Carbon\Carbon ? $endDate->toDateTimeString() : \Carbon\Carbon::parse($endDate)->toDateTimeString();

        $result = DB::table('stage_plan as sp')
            ->join('yields as y', 'sp.id', '=', 'y.stage_plan_id')
            ->where('sp.active', 1) // 🔥 Chỉ lấy sản lượng của những lô đang hoạt động
            ->whereNotNull('sp.resourceId')
            ->where('sp.deparment_code', session('user')['production_code'])
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
}

<?php

namespace App\Http\Controllers\Pages\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class WeeklyReportController extends Controller
{
        public function index(Request $request) {

           
            $reportedWeek = (int) ($request->week_number ?? now()->weekOfYear);
            $reportedYear = (int) ($request->year ?? now()->year);

            $start = Carbon::now()
                ->setISODate($reportedYear, $reportedWeek)
                ->startOfWeek()
                ->setTime(6, 0, 0);

            $end = (clone $start)->addWeek();
        
            $check = DB::table('room_sheet')->where('reported_week', $reportedWeek)->where('reported_year', $reportedYear)->exists();
            
            if (!$check){
                    $rooms = DB::table('room')
                        ->where('active', 1)
                        //->whereNotNull('capacity')
                        ->select(
                            'id as room_id',
                            'capacity',
                            DB::raw($reportedWeek . ' as reported_week'),
                            DB::raw("'" . $reportedYear . "' as reported_year")
                        )
                        ->get();

                    DB::table('room_sheet')->insert(
                        $rooms->map(fn ($r) => (array) $r)->toArray()
                    );
            }

            $time = $this->getOperatedTime ($start, $end);
            $yield_actual =    $this->yield_actual ($start, $end);
            $yield_theory =   $this->yield_theory ($start, $end);

            $datas = DB::table('room_sheet')
                    ->whereNotNull('room_sheet.capacity')
                    ->where('deparment_code', session ('user')['production_code'])
                    ->where('reported_year', $reportedYear)
                    ->where('reported_week', $reportedWeek)
                    ->leftJoin('room','room_sheet.room_id','room.id')
                    ->select (
                        'room_sheet.*',
                        'room.name as room_name',
                        'room.code as room_code',
                        'room.stage_code',
                        'room.main_equiment_name as main_equiment_name',
                    )
            ->orderBy('stage_code')
            ->orderBy('order_by')
            ->get();

            $timeByResource = collect($time)->keyBy('resourceId');
            $actualByResource = collect($yield_actual)->keyBy('resourceId');
            $theoryByResource = collect($yield_theory)->keyBy('resourceId');

            $datas = $datas->map(function ($row) use (
                $timeByResource,
                $actualByResource,
                $theoryByResource
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
                
                /* ================= YIELD ================= */
                $row->yield_actual = $actual->total_qty ?? 0;
                $row->yield_theory = $theory->total_qty ?? 0;
                // /* ================= KPI ================= */
                $row->output_thery = $row->work_hours > 0
                    ? round($row->work_hours * $row->capacity, 2)
                    : 0;

                $row->OEE = $row->output_thery > 0? round(($row->yield_actual/$row->output_thery) *100):0;

                $row->H_in_week = $row->shift *  $row->day_in_week * 8;

                $row->loading = $row->H_in_week > 0 ? round($row->work_hours /  $row->H_in_week * 100,2) : 0;

                $row->TEEP =round( $row->loading * $row->OEE / 100 ,2);


                return $row;
            });
            $datas = $datas->sortBy('stage_code');
            session()->put([
                'title' => "BÁO CÁO TUẦN $reportedWeek (" .
                    $start->format('H:i d/m/Y') . " - " .
                    $end->format('H:i d/m/Y') . ")"
            ]);
            //dd ($datas);
            return view('pages.report.weekly_report.list', [
                    'datas' => $datas
            ]);
        }

        public function getOperatedTime($startDate, $endDate){
            // 1. Chuẩn hoá thời gian
            $start = Carbon::parse($startDate);
            $end   = Carbon::parse($endDate);

            $startTs = $start->timestamp;
            $endTs   = $end->timestamp;

            $totalSeconds = $start->diffInSeconds($end);

            // 2. Lấy RAW event (KHÔNG SUM)
            $rows = DB::table('stage_plan as sp')
                ->select(
                    'sp.resourceId',
                    'sp.actual_start',
                    'sp.actual_end',
                    'sp.actual_end_clearning'
                )
                ->where('sp.deparment_code', session('user')['production_code'])
                ->whereRaw(
                    'GREATEST(sp.actual_start, ?) < LEAST(COALESCE(sp.actual_end_clearning, sp.actual_end), ?)',
                    [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]
                )
                ->orderBy('sp.resourceId')
                ->orderBy('sp.actual_start')
                ->get();

            // 3. Hàm merge interval (loại trùng)
            $mergeIntervals = function (array $intervals) {
                if (empty($intervals)) return [];

                usort($intervals, fn ($a, $b) => $a[0] <=> $b[0]);

                $merged = [$intervals[0]];

                foreach ($intervals as $current) {
                    $lastIndex = count($merged) - 1;

                    if ($current[0] <= $merged[$lastIndex][1]) {
                        // chồng thời gian → merge
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

            // 4. Xử lý theo từng resource
            $result = [];

            foreach ($rows->groupBy('resourceId') as $resourceId => $items) {

                $workIntervals  = [];
                $cleanIntervals = [];

                foreach ($items as $r) {

                    // WORK interval
                    if ($r->actual_start && $r->actual_end) {
                        $workIntervals[] = [
                            max(Carbon::parse($r->actual_start)->timestamp, $startTs),
                            min(Carbon::parse($r->actual_end)->timestamp, $endTs),
                        ];
                    }

                    // CLEANING interval
                    if ($r->actual_end && $r->actual_end_clearning) {
                        $cleanIntervals[] = [
                            max(Carbon::parse($r->actual_end)->timestamp, $startTs),
                            min(Carbon::parse($r->actual_end_clearning)->timestamp, $endTs),
                        ];
                    }
                }

                // 5. Merge interval
                $workMerged  = $mergeIntervals($workIntervals);
                $cleanMerged = $mergeIntervals($cleanIntervals);

                // 6. Tính seconds
                $workSeconds = array_sum(
                    array_map(fn ($i) => max(0, $i[1] - $i[0]), $workMerged)
                );

                $cleanSeconds = array_sum(
                    array_map(fn ($i) => max(0, $i[1] - $i[0]), $cleanMerged)
                );

                $busySeconds = $workSeconds + $cleanSeconds;
                $freeSeconds = max(0, $totalSeconds - $busySeconds);

                // 7. Kết quả
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

        public function yield_theory($startDate, $endDate){
            // ------------------------------
        
            // 1️⃣ Giai đoạn nằm hoàn toàn trong 1 ngày
            // ------------------------------
            $stage_plan_100 = DB::table("stage_plan as sp")
                ->whereNotNull('sp.start')
                ->whereNotNull('sp.resourceId')
                ->whereRaw('(sp.start >= ? AND sp.end <= ?)', [$startDate, $endDate])
                ->where('sp.deparment_code', session('user')['production_code'])
                ->select(
                    "sp.resourceId",
                    DB::raw('SUM(sp.yields) as total_qty'),
                )
                ->groupBy("sp.resourceId")
            ->get();


            // ------------------------------
            // 2️⃣ Giai đoạn giao nhau 1 phần trong 1 ngày
            // ------------------------------
            $stage_plan_part = DB::table("stage_plan as sp")
                ->whereNotNull('sp.start')
                ->whereNotNull('sp.resourceId')
                ->whereRaw('(sp.start < ? AND sp.end > ?)', [$endDate, $startDate])
                ->whereRaw('NOT (sp.start >= ? AND sp.end <= ?)', [$startDate, $endDate])
                ->where('sp.deparment_code', session('user')['production_code'])
                ->select(
                    "sp.resourceId",
                    DB::raw('
                        SUM(
                            sp.yields *
                            TIME_TO_SEC(TIMEDIFF(LEAST(sp.end, "'.$endDate.'"), GREATEST(sp.start, "'.$startDate.'"))) /
                            TIME_TO_SEC(TIMEDIFF(sp.end, sp.start))
                        ) as total_qty
                    ')
                )
                ->groupBy("sp.resourceId")
            ->get();

            // ------------------------------
            // 3️⃣ Gom 2 phần lại
            // ------------------------------

            $merged = $stage_plan_100->merge($stage_plan_part)
                ->groupBy(function ($item) {
                    return $item->resourceId ;
                })
                ->map(function ($items) {
                    $first = $items->first();
                    $total_qty = round($items->sum('total_qty'), 2);

    
                    return (object)[
                        'resourceId'     => $first->resourceId,
                        'total_qty'   => $total_qty
                    ];
                })
                ->values();
            
           return collect($merged);
        }

        public function yield_actual($startDate, $endDate){
            // ------------------------------
        
            // 1️⃣ Giai đoạn nằm hoàn toàn trong 1 ngày
            // ------------------------------
            $stage_plan_100 = DB::table("stage_plan as sp")
                ->whereNotNull('sp.actual_start')
                ->whereNotNull('sp.resourceId')
                ->whereRaw('(sp.actual_start >= ? AND sp.actual_end <= ?)', [$startDate, $endDate])
                ->where('sp.deparment_code', session('user')['production_code'])
                ->select(
                    "sp.resourceId",
                    DB::raw('SUM(sp.yields) as total_qty'),
                )
                ->groupBy("sp.resourceId")
            ->get();


            // ------------------------------
            // 2️⃣ Giai đoạn giao nhau 1 phần trong 1 ngày
            // ------------------------------
            $stage_plan_part = DB::table("stage_plan as sp")
                ->whereNotNull('sp.actual_start')
                ->whereNotNull('sp.resourceId')
                ->whereRaw('(sp.actual_start < ? AND sp.actual_end > ?)', [$endDate, $startDate])
                ->whereRaw('NOT (sp.actual_start >= ? AND sp.actual_end <= ?)', [$startDate, $endDate])
                ->where('sp.deparment_code', session('user')['production_code'])
                ->select(
                    "sp.resourceId",
                    DB::raw('
                        SUM(
                            sp.yields *
                            TIME_TO_SEC(TIMEDIFF(LEAST(sp.actual_end, "'.$endDate.'"), GREATEST(sp.actual_start, "'.$startDate.'"))) /
                            TIME_TO_SEC(TIMEDIFF(sp.actual_end, sp.actual_start))
                        ) as total_qty
                    ')
                )
                ->groupBy("sp.resourceId")
            ->get();

            // ------------------------------
            // 3️⃣ Gom 2 phần lại
            // ------------------------------

            $merged = $stage_plan_100->merge($stage_plan_part)
                ->groupBy(function ($item) {
                    return $item->resourceId ;
                })
                ->map(function ($items) {
                    $first = $items->first();
                    $total_qty = round($items->sum('total_qty'), 2);

    
                    return (object)[
                        'resourceId'     => $first->resourceId,
                        'total_qty'   => $total_qty
                    ];
                })
                ->values();
            
            return collect($merged); 
                
        }

        public function updateInput(Request $request){
                DB::table('room_sheet')
                        ->where('id', $request->id)
                        ->update([
                                $request->name => $request->time
                        ]);
                return response()->json(['success' => true]);
        }


}

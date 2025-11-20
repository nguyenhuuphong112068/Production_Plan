<?php

namespace App\Http\Controllers\Pages\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyReportController extends Controller
{
    public function index(Request $request) {

        $reportedDate = $request->reportedDate ?? Carbon::yesterday()->format('Y-m-d');

        $reportedDate = Carbon::parse ($reportedDate)->addDays(1)->setTime (6,0,0);
        $endDate =  $reportedDate->copy();
        $startDate =  $endDate->copy()->subDays(1);
        
        // // 1) Lấy toàn bộ dữ liệu gốc
        // $datasRaw = DB::table('stage_plan as t')
        //     ->leftJoin('stage_plan as t2', function ($join) {
        //         $join->on('t2.code','=','t.nextcessor_code');
        //     })
        //     ->leftJoin('plan_master','t.plan_master_id','plan_master.id')
        //     ->leftJoin('finished_product_category as fc', 't.product_caterogy_id', '=', 'fc.id')
        //     ->leftJoin('product_name','fc.product_name_id','product_name.id')
        //     ->leftJoin('quarantine_room','t.quarantine_room_code','quarantine_room.code')
        //     ->leftJoin('room','t2.resourceId','room.id')
        //     ->whereNotNull('t.start')
        //     ->whereNotNull('t.yields')
        //     ->whereNotNull('t.quarantine_room_code')
        //     ->where('t2.start','>',now())
        //     ->where('t.active', 1)
        //     ->where('t.finished', 1)
        //     ->where('quarantine_room.deparment_code', session('user')['production_code'])
        //     ->select(
        //         'fc.finished_product_code',
        //         'fc.intermediate_code',
        //         't.plan_master_id',
        //         'product_name.name as product_name',
        //         'plan_master.batch',
        //         't.quarantine_room_code',
        //         'quarantine_room.name',
        //         't.yields',
        //         't.stage_code',
        //         't.number_of_boxes',
        //         't.finished_by',
        //         't.finished_date',
    
        //         't2.stage_code as next_stage',
        //         't2.start as next_start',
        //         't2.resourceId as next_room_id',
        //         DB::raw("CONCAT(room.code, ' - ', room.name, ' - ', room.main_equiment_name) as next_room"),
        //        'room.production_group as production_group',
        //        'room.stage as stage',
        //        'room.group_code',
        //     )
        //     ->orderBy('t.plan_master_id')
        //     ->orderBy('t.stage_code')
        //     ->get();

        // // 2) Group theo phòng (datas chính)
        // $datas = $datasRaw->groupBy('quarantine_room_code')->map(function ($items) {
        //     return [
        //         'room_name' => $items->first()->name,
        //         'total_yields' => $items->sum('yields'),
        //         'details' => $items
                
        //     ];
        // });

        $yield_actual = $this->yield_actual( $startDate, $endDate, 'resourceId');

        dd ($yield_actual);

        $sum_by_next_room = DB::table('stage_plan as t')
            ->leftJoin('stage_plan as t2', function ($join) {
                $join->on('t2.code','=','t.nextcessor_code');
            })
            ->leftJoin('room','t2.resourceId','room.id')
            ->whereNotNull('t.yields')
            ->where('t2.start','>', $reportedDate)
            ->where('t.active', 1)
            ->where('t.finished', 1)
            ->select(
                DB::raw("SUM(t.yields) as sum_yields"),
                DB::raw("CONCAT(room.code, ' - ', room.name, ' - ', room.main_equiment_name) as next_room"),
                DB::raw("MIN(room.production_group) as production_group"),
                DB::raw("MIN(room.stage) as stage"),
                DB::raw("MIN(room.stage_code) as stage_code"),
                DB::raw("MIN(room.group_code) as group_code"),
                DB::raw("MIN(room.id) as room_id")

            )
            ->groupBy('next_room')
            ->orderBy('group_code')   // sắp xếp theo stage
            ->get();

        $reportedDate = $reportedDate->subDays(1)->format ('d/m/Y');    
        session()->put(['title' => "BÁO CÁO NGÀY $reportedDate"]);
        
        return view('pages.report.daily_report.list', [
            //'datas' => $datas,
            'sum_by_next_room' => $sum_by_next_room ,
            'reportedDate' => $reportedDate
        ]);

    }

    public function detail(Request $request) {


            $reportedDate = Carbon::parse ($request->reportedDate)->addDays(1)->setTime (6,0,0);

            $detial = DB::table('stage_plan as t')
                ->leftJoin('stage_plan as t2', function ($join) {
                    $join->on('t2.code','=','t.nextcessor_code');
                })
                ->leftJoin('plan_master','t.plan_master_id','plan_master.id')
                ->leftJoin('finished_product_category as fc', 't.product_caterogy_id', '=', 'fc.id')
                ->leftJoin('product_name','fc.product_name_id','product_name.id')
                ->leftJoin('quarantine_room','t.quarantine_room_code','quarantine_room.code')
                ->leftJoin('room','t.resourceId','room.id')
                ->whereNotNull('t.start')
                ->whereNotNull('t.yields')
                ->where('t2.resourceId',$request->room_id)
                ->where('t2.start','>',$reportedDate)
                ->where('t.active', 1)
                ->where('t.finished', 1)
                ->select(
                    'fc.finished_product_code',
                    'fc.intermediate_code',
                    'product_name.name as product_name',
                    'plan_master.batch',
                    't.quarantine_room_code',
                    'quarantine_room.name',
                    't.yields',
                    't.stage_code',
                    't2.stage_code as next_stage',
                    't2.start as next_start',
                    DB::raw("CONCAT(room.code, ' - ', room.name, ' - ', room.main_equiment_name) as pre_room"),
                    'room.production_group as production_group',
                    'room.stage as stage',
                    'room.group_code',
                
                )
                ->orderBy('t.plan_master_id')
                ->orderBy('t.stage_code')
            ->get();

            return response()->json($detial);
        
    }

    public function yield_actual($startDate, $endDate, $group_By){
        
        // --- 1️⃣ Giai đoạn nằm hoàn toàn trong khoảng
        $stage_plan_100 = DB::table("stage_plan as sp")
            ->whereNotNull('sp.actual_start')
            ->whereNotNull('sp.start')
            ->whereRaw('((sp.actual_start >= ? AND sp.actual_end <= ?))', [$startDate, $endDate])
            ->where('sp.deparment_code', session('user')['production_code'])
            ->select(
                "sp.$group_By",
                DB::raw('SUM(sp.yields) as total_qty'),
                DB::raw('
                    CASE
                        WHEN sp.stage_code <= 4 THEN "Kg"
                        ELSE "ĐVL"
                    END as unit
                ')
            )
            ->groupBy("sp.$group_By", "unit")
            ->get();

        // --- 2️⃣ Giai đoạn chỉ giao nhau 1 phần
        $stage_plan_part = DB::table("stage_plan as sp")
            ->whereNotNull('sp.actual_start')
            ->whereRaw('(sp.actual_start < ? AND sp.actual_end > ?) AND NOT (sp.actual_start >= ? AND sp.actual_end <= ?)', [$endDate, $startDate, $startDate, $endDate])
            ->whereNotNull('sp.start')
            ->where('sp.deparment_code', session('user')['production_code'])
            ->select(
                "sp.$group_By",
                DB::raw('
                    SUM(
                        sp.yields *
                        TIME_TO_SEC(TIMEDIFF(LEAST(sp.actual_end, "'.$endDate.'"), GREATEST(sp.actual_start, "'.$startDate.'"))) /
                        TIME_TO_SEC(TIMEDIFF(sp.actual_end, sp.actual_start))
                    ) as total_qty
                '),
                DB::raw('
                    CASE
                        WHEN sp.stage_code <= 4 THEN "Kg"
                        ELSE "ĐVL"
                    END as unit
                ')
            )
            ->groupBy("sp.$group_By", "unit")
            ->get();

        // --- 3️⃣ Gộp và tổng hợp
        $merged = $stage_plan_100->merge($stage_plan_part)
            ->groupBy(function ($item) use ($group_By) {
                return $item->$group_By . '-' . $item->unit;
            })
            ->map(function ($items) use ($group_By) {
                $first = $items->first();
                $total_qty = round($items->sum('total_qty'), 2);

                // Nếu group_By là room_id hoặc resourceId → lấy thêm thông tin phòng
                if ($group_By === 'room_id' || $group_By === 'resourceId') {
                    $room = DB::table('room')
                        ->select('code', 'name', 'stage_code', 'order_by')
                        ->where('id', $first->$group_By)
                        ->first();

                    return (object)[
                        'stage_code' => $room->stage_code,
                        'order_by' => $room->order_by,
                        $group_By => $first->$group_By,
                        'room_code' => $room->code ?? null,
                        'room_name' => $room->name ?? null,
                        'unit' => $first->unit,
                        'total_qty' => $total_qty,

                    ];
                }

                // Các trường hợp khác (stage, machine, v.v.)
                return (object)[
                    $group_By => $first->$group_By,
                    'unit' => $first->unit,
                    'total_qty' => $total_qty,
                ];
            })
        ->values();


        // 🔹 Bước 2: Group lại theo stage_code
        $merged_by_stage = $merged
            ->groupBy('stage_code')
            ->map(function ($group) {
                return (object)[
                    'stage_code' => $group->first()->stage_code,
                    'total_qty'  => round($group->sum('total_qty'), 2),
                    'details'    => $group->values(), // nếu muốn giữ danh sách chi tiết
                ];
            })
            ->values();
       
        
        // --- 4️⃣ Tính tổng theo từng ngày
        $dailyTotals = collect();
        $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate->copy()->addDay());
        
        foreach ($period as $date) {
            $date = Carbon::instance($date);
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            $totalForDay = DB::table("stage_plan as sp")
                ->join('room as r', 'sp.resourceId', '=', 'r.id') // 👈 JOIN thêm bảng room
                ->where('sp.deparment_code', session('user')['production_code'])
                ->whereNotNull('sp.start')
                ->whereNotNull('sp.actual_start')
                ->whereRaw('(sp.actual_start <= ? AND sp.actual_end >= ?)', [$dayEnd, $dayStart])
                ->select(
                    "sp.$group_By",
                    'r.code as room_code',
                    'r.name as room_name',
                    'r.stage_code as stage_code',
                    DB::raw('
                        SUM(
                            sp.yields *
                            TIME_TO_SEC(TIMEDIFF(LEAST(sp.actual_end, "'.$dayEnd.'"), GREATEST(sp.actual_start, "'.$dayStart.'"))) /
                            TIME_TO_SEC(TIMEDIFF(sp.actual_end, sp.actual_start))
                        ) as total_qty
                    '),
                    DB::raw('
                        CASE
                            WHEN sp.stage_code <= 4 THEN "Kg"
                            ELSE "ĐVL"
                        END as unit
                    ')
                )
                ->groupBy("sp.$group_By", "r.code", "r.name",  "r.stage_code", "unit")
                ->get();

            foreach ($totalForDay as $item) {
                $dailyTotals->push([
                    $group_By => $item->$group_By,
                    "stage_code" => $item->stage_code,
                    "room_code" => $item->room_code,
                    "room_name" => $item->room_name,
                    "unit" => $item->unit,
                    "date" => $date->format('Y-m-d'),
                    "total_qty" => round($item->total_qty ?? 0, 2),
                ]);
            }
        }
        $dailyTotals = $dailyTotals->groupBy("date");
        $merged = $merged->sortBy('stage_code')->values();
       // dd ($merged,$dailyTotals, $merged_by_stage);
        // --- 5️⃣ Trả về cả 2 phần
        return [
            'yield_room' => $merged,
            'yield_day' => $dailyTotals,
            'yield_stage' => $merged_by_stage
        ];
    }
}

<?php

namespace App\Http\Controllers\Pages\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DailyReportController extends Controller
{
    public function index(Request $request) {

        $reportedDate = $request->reportedDate ?? Carbon::yesterday()->format('Y-m-d');

        $reportedDate = Carbon::parse ($reportedDate)->addDays(1)->setTime (6,0,0);
        $endDate =  $reportedDate->copy();
        $startDate =  $endDate->copy()->subDays(1);
    
       
        $actual = $this->yield_actual($startDate, $endDate, 'resourceId');
        $theory = $this->yield_theory($startDate, $endDate, 'resourceId');

        


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
       // dd ($sum_by_next_room, $theory);
        return view('pages.report.daily_report.list', [
            'actual' => $actual,
            'theory' => $theory,
            'sum_by_next_room' => $sum_by_next_room ,
            'reportedDate' => $reportedDate
        ]);

    }

    public function yield_actual($startDate, $endDate, $group_By){
        // ------------------------------
        // 1️⃣ Giai đoạn nằm hoàn toàn trong 1 ngày
        // ------------------------------
        $stage_plan_100 = DB::table("stage_plan as sp")
            ->whereNotNull('sp.actual_start')
            ->whereNotNull('sp.start')
            ->whereRaw('(sp.actual_start >= ? AND sp.actual_end <= ?)', [$startDate, $endDate])
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

        // ------------------------------
        // 2️⃣ Giai đoạn giao nhau 1 phần trong 1 ngày
        // ------------------------------
        $stage_plan_part = DB::table("stage_plan as sp")
            ->whereNotNull('sp.actual_start')
            ->whereNotNull('sp.start')
            ->whereRaw('(sp.actual_start < ? AND sp.actual_end > ?)', [$endDate, $startDate])
            ->whereRaw('NOT (sp.actual_start >= ? AND sp.actual_end <= ?)', [$startDate, $endDate])
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

        // ------------------------------
        // 3️⃣ Gom 2 phần lại
        // ------------------------------
        $merged = $stage_plan_100->merge($stage_plan_part)
            ->groupBy(function ($item) use ($group_By) {
                return $item->$group_By . '-' . $item->unit;
            })
            ->map(function ($items) use ($group_By) {
                $first = $items->first();
                $total_qty = round($items->sum('total_qty'), 2);

                // Lấy thông tin phòng
                $room = DB::table('room')
                    ->select('code', 'name', 'stage_code', 'order_by')
                    ->where('id', $first->$group_By)
                    ->first();

                return (object)[
                    $group_By     => $first->$group_By,
                    'room_code'   => $room->code ?? null,
                    'room_name'   => $room->name ?? null,
                    'stage_code'  => $room->stage_code ?? null,
                    'order_by'    => $room->order_by ?? null,
                    'unit'        => $first->unit,
                    'total_qty'   => $total_qty
                ];
            })
            ->values();

        // ------------------------------
        // 4️⃣ Tổng hợp theo ROOM (resourceId)
        // ------------------------------
        $yield_room = $merged->sortBy('stage_code')->values();

        // ------------------------------
        // 5️⃣ Tổng hợp theo STAGE
        // ------------------------------
        $yield_stage = $yield_room
            ->groupBy('stage_code')
            ->map(function ($group) {
                return (object)[
                    'stage_code' => $group->first()->stage_code,
                    'total_qty'  => round($group->sum('total_qty'), 2),
                    'details'    => $group->values()
                ];
            })
            ->values();

        // ------------------------------
        // 6️⃣ Tạo dailyTotals cho 1 ngày duy nhất
        // ------------------------------
        $dailyTotals = collect();
        $dayStart = $startDate->copy()->startOfDay();
        $dayEnd   = $startDate->copy()->endOfDay();

        $totalForDay = DB::table("stage_plan as sp")
            ->join('room as r', 'sp.resourceId', '=', 'r.id')
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
            ->groupBy("sp.$group_By", "r.code", "r.name", "r.stage_code", "unit")
            ->get();

        foreach ($totalForDay as $item) {
            $dailyTotals->push([
                $group_By    => $item->$group_By,
                "stage_code" => $item->stage_code,
                "room_code"  => $item->room_code,
                "room_name"  => $item->room_name,
                "unit"       => $item->unit,
                "date"       => $dayStart->format('Y-m-d'),
                "total_qty"  => round($item->total_qty ?? 0, 2),
            ]);
        }

        $dailyTotals = $dailyTotals->groupBy("date");

        // ------------------------------
        // 7️⃣ Trả về dữ liệu
        // ------------------------------
        return [
            'yield_room'  => $yield_room,          // theo room
            'yield_stage' => $yield_stage,         // theo stage
            'yield_day'   => $dailyTotals          // 1 ngày duy nhất
        ];
    }

    public function yield_theory($startDate, $endDate, $group_By){
        // ------------------------------
        // 1️⃣ Giai đoạn nằm hoàn toàn trong khoảng
        // ------------------------------
        $stage_plan_100 = DB::table("stage_plan as sp")
            ->whereNotNull('sp.start')
            ->whereRaw('(sp.start >= ? AND sp.end <= ?)', [$startDate, $endDate])
            ->where('sp.deparment_code', session('user')['production_code'])
            ->select(
                "sp.$group_By",
                DB::raw('SUM(sp.Theoretical_yields) as total_qty'),
                DB::raw('
                    CASE
                        WHEN sp.stage_code <= 4 THEN "Kg"
                        ELSE "ĐVL"
                    END as unit
                ')
            )
            ->groupBy("sp.$group_By", "unit")
            ->get();

        // ------------------------------
        // 2️⃣ Giai đoạn giao nhau 1 phần
        // ------------------------------
        $stage_plan_part = DB::table("stage_plan as sp")
            ->whereNotNull('sp.start')
            ->whereRaw('(sp.start < ? AND sp.end > ?) AND NOT (sp.start >= ? AND sp.end <= ?)', [$endDate, $startDate, $startDate, $endDate])
            ->where('sp.deparment_code', session('user')['production_code'])
            ->select(
                "sp.$group_By",
                DB::raw('
                    SUM(
                        sp.Theoretical_yields *
                        TIME_TO_SEC(TIMEDIFF(LEAST(sp.end, "'.$endDate.'"), GREATEST(sp.start, "'.$startDate.'"))) /
                        TIME_TO_SEC(TIMEDIFF(sp.end, sp.start))
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

        // ------------------------------
        // 3️⃣ Gộp và tổng hợp
        // ------------------------------
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
                        'stage_code' => $room->stage_code ?? null,
                        'order_by' => $room->order_by ?? null,
                        $group_By => $first->$group_By,
                        'room_code' => $room->code ?? null,
                        'room_name' => $room->name ?? null,
                        'unit' => $first->unit,
                        'total_qty' => $total_qty,
                    ];
                }

                // Trường hợp khác (stage, machine,...)
                return (object)[
                    $group_By => $first->$group_By,
                    'unit' => $first->unit,
                    'total_qty' => $total_qty,
                ];
            })
            ->values();

        // ------------------------------
        // 4️⃣ Tổng hợp theo ROOM
        // ------------------------------
        $yield_room = $merged->sortBy('stage_code')->values();

        // ------------------------------
        // 5️⃣ Tổng hợp theo STAGE
        // ------------------------------
        $yield_stage = $yield_room
            ->groupBy('stage_code')
            ->map(function ($group) {
                return (object)[
                    'stage_code' => $group->first()->stage_code,
                    'total_qty' => round($group->sum('total_qty'), 2),
                    'details' => $group->values(),
                ];
            })
            ->values();

        // ------------------------------
        // 6️⃣ Tạo dailyTotals cho 1 ngày duy nhất
        // ------------------------------
        $dailyTotals = collect();
        $dayStart = $startDate->copy()->startOfDay();
        $dayEnd   = $startDate->copy()->endOfDay();

        $totalForDay = DB::table("stage_plan as sp")
            ->join('room as r', 'sp.resourceId', '=', 'r.id')
            ->where('sp.deparment_code', session('user')['production_code'])
            ->whereNotNull('sp.start')
            ->whereRaw('(sp.start <= ? AND sp.end >= ?)', [$dayEnd, $dayStart])
            ->select(
                "sp.$group_By",
                'r.code as room_code',
                'r.name as room_name',
                'r.stage_code as stage_code',
                DB::raw('
                    SUM(
                        sp.Theoretical_yields *
                        TIME_TO_SEC(TIMEDIFF(LEAST(sp.end, "'.$dayEnd.'"), GREATEST(sp.start, "'.$dayStart.'"))) /
                        TIME_TO_SEC(TIMEDIFF(sp.end, sp.start))
                    ) as total_qty
                '),
                DB::raw('
                    CASE
                        WHEN sp.stage_code <= 4 THEN "Kg"
                        ELSE "ĐVL"
                    END as unit
                ')
            )
            ->groupBy("sp.$group_By", "r.code", "r.name", "r.stage_code", "unit")
            ->get();

        foreach ($totalForDay as $item) {
            $dailyTotals->push([
                $group_By => $item->$group_By,
                "stage_code" => $item->stage_code,
                "room_code" => $item->room_code,
                "room_name" => $item->room_name,
                "unit" => $item->unit,
                "date" => $dayStart->format('Y-m-d'),
                "total_qty" => round($item->total_qty ?? 0, 2),
            ]);
        }

        $dailyTotals = $dailyTotals->groupBy("date");

        // ------------------------------
        // 7️⃣ Trả về dữ liệu
        // ------------------------------
        return [
            'yield_room' => $yield_room,
            'yield_day' => $dailyTotals,
            'yield_stage' => $yield_stage
        ];
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

    public function getExplainationContent(Request $request) {
       
        $data = DB::table('explanation')
            ->where('reported_date', $request->reported_date)
            ->where('stage_code', $request->stage_code)
            ->first();

        if (!$data) {
            // Chưa có thì tạo mới
            DB::table('explanation')->insert([
                'reported_date' => $request->reported_date,
                'stage_code' => $request->stage_code,
                'content' => "Chưa Có Ghi Chú",
                'created_by' => session ('user')['fullName'],
                'created_at' => now(),
               
            ]);

            // Lấy lại dữ liệu sau khi insert
            $data = DB::table('explanation')
                ->where('reported_date', $request->reported_date)
                ->where('stage_code', $request->stage_code)
                ->first();
        }


        return response()->json($data);
    }

    public function explain (Request $request) {

         DB::table('explanation')
            ->where ('reported_date', $request->reported_date)
            ->where ('stage_code', $request->stage_code)
            ->update([
                    'content' => $request->note,
                    'created_by' => session ('user')['fullName'],
                    'updated_at' => now(),
                ]);
        return redirect()->back()->with('success', 'Đã thêm thành công!');    
    }


}

<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class ShedualYieldController extends Controller
{
    
    
    public function index (Request $request){
       
        $startDate = $request->from_date
            ? Carbon::parse($request->from_date)
            : Carbon::now()->startOfMonth();

        $endDate = $request->to_date
            ? Carbon::parse($request->to_date)
            : Carbon::now()->endOfMonth();

        $theory  = $this->yield_theory ( $startDate, $endDate, 'resourceId');
        $actual = $this->yield_actual ( $startDate, $endDate, 'resourceId');
        
       //dd ($theory, $theory2);
        session()->put(['title'=> 'SẢN LƯỢNG LÝ THUYẾT - THỰC TẾ']);
        return view('pages.Schedual.yield.list',[   
            'theory' => $theory,
            'actual' => $actual,
                        
        ]);
    }
    
    public function yield_theory ($startDate, $endDate, $group_By){
        
        // --- 1️⃣ Giai đoạn nằm hoàn toàn trong khoảng
        $stage_plan_100 = DB::table("stage_plan as sp")
            ->whereRaw('((sp.start >= ? AND sp.end <= ?))', [$startDate, $endDate])
            ->whereNotNull('sp.start')
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

        // --- 2️⃣ Giai đoạn chỉ giao nhau 1 phần
        $stage_plan_part = DB::table("stage_plan as sp")
            ->whereRaw('(sp.start < ? AND sp.end > ?) AND NOT (sp.start >= ? AND sp.end <= ?)', [$endDate, $startDate, $startDate, $endDate])
            ->whereNotNull('sp.start')
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

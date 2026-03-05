<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class ShedualYieldController extends Controller
{

    public function index (Request $request){
        //dd ($request->all());
        $startDate = $request->from_date
            ? Carbon::parse($request->from_date)
            : Carbon::now()->addDays(1)->startOfMonth();

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
            ->whereRaw('((sp.start >= ? AND sp.end <= ?))', [$startDate->toDateTimeString(), $endDate->toDateTimeString()])
            ->whereNotNull('sp.start')
            ->where('sp.deparment_code', session('user')['production_code'])
            ->select(
                "sp.$group_By",
                //'sp.start',
                DB::raw('SUM(sp.Theoretical_yields) as total_qty'),
                DB::raw('SUM(sp.Theoretical_yields_qty) as total_qty_unit'),
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
                    SUM(
                        sp.Theoretical_yields_qty *
                        TIME_TO_SEC(TIMEDIFF(LEAST(sp.end, "'.$endDate.'"), GREATEST(sp.start, "'.$startDate.'"))) /
                        TIME_TO_SEC(TIMEDIFF(sp.end, sp.start))
                    ) as total_qty_unit
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
                $total_qty_unit = round($items->sum('total_qty_unit'), 2);

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
                        'total_qty_unit' => $total_qty_unit

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
                    'total_qty_unit'  => round($group->sum('total_qty_unit'), 2),
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
                ->where('r.deparment_code', session('user')['production_code'])
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
                        SUM(
                            sp.Theoretical_yields_qty *
                            TIME_TO_SEC(TIMEDIFF(LEAST(sp.end, "'.$dayEnd.'"), GREATEST(sp.start, "'.$dayStart.'"))) /
                            TIME_TO_SEC(TIMEDIFF(sp.end, sp.start))
                        ) as total_qty_unit
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
                    "total_qty_unit" => round($item->total_qty_unit ?? 0, 2),
                ]);
            }
        }
        $dailyTotals = $dailyTotals->groupBy("date");
        $merged = $merged->sortBy('stage_code')->values();
        //dd ($merged,$dailyTotals, $merged_by_stage);
        // --- 5️⃣ Trả về cả 2 phần
        return [
            'yield_room' => $merged,
            'yield_day' => $dailyTotals,
            'yield_stage' => $merged_by_stage
        ];
    }

    // public function yield_actual($startDate, $endDate, $group_By){
       
    //     // --- 1️⃣ Giai đoạn nằm hoàn toàn trong khoảng
    //     $stage_plan_100 = DB::table("stage_plan as sp")
    //         ->whereNotNull('sp.actual_start')
    //         ->whereNotNull('sp.resourceId')
    //         ->whereRaw('((sp.actual_start >= ? AND sp.actual_end <= ?))', [$startDate, $endDate])
    //         ->where('sp.deparment_code', session('user')['production_code'])
    //         ->select(
    //             "sp.$group_By",
    //             DB::raw('SUM(sp.yields) as total_qty'),
    //             DB::raw('
    //                 CASE
    //                     WHEN sp.stage_code <= 4 THEN "Kg"
    //                     ELSE "ĐVL"
    //                 END as unit
    //             ')
    //         )
    //         ->groupBy("sp.resourceId", "unit")
    //         ->get();
       

    //     // --- 2️⃣ Giai đoạn chỉ giao nhau 1 phần
    //     $stage_plan_part = DB::table("stage_plan as sp")
    //         ->whereNotNull('sp.actual_start')
    //         ->whereRaw('(sp.actual_start < ? AND sp.actual_end > ?) AND NOT (sp.actual_start >= ? AND sp.actual_end <= ?)', [$endDate, $startDate, $startDate, $endDate])
    //         ->whereNotNull('sp.resourceId')
    //         ->where('sp.deparment_code', session('user')['production_code'])
    //         ->select(
    //             "sp.$group_By",
    //             DB::raw('
    //                 SUM(
    //                     sp.yields *
    //                     TIME_TO_SEC(TIMEDIFF(LEAST(sp.actual_end, "'.$endDate.'"), GREATEST(sp.actual_start, "'.$startDate.'"))) /
    //                     TIME_TO_SEC(TIMEDIFF(sp.actual_end, sp.actual_start))
    //                 ) as total_qty
    //             '),
    //             DB::raw('
    //                 CASE
    //                     WHEN sp.stage_code <= 4 THEN "Kg"
    //                     ELSE "ĐVL"
    //                 END as unit
    //             ')
    //         )
    //         ->groupBy("sp.$group_By", "unit")
    //     ->get();

        
    //     // --- 3️⃣ Gộp và tổng hợp
    //     $merged = $stage_plan_100->merge($stage_plan_part)
    //         ->groupBy(function ($item) use ($group_By) {
    //             return $item->$group_By . '-' . $item->unit;
    //         })
    //         ->map(function ($items) use ($group_By) {
    //             $first = $items->first();
    //             $total_qty = round($items->sum('total_qty'), 2);

    //             // Nếu group_By là room_id hoặc resourceId → lấy thêm thông tin phòng
    //             if ($group_By === 'room_id' || $group_By === 'resourceId') {

    //                 $room = DB::table('room')
    //                     ->select('code', 'name', 'stage_code', 'order_by')
    //                     ->where('id', $first->$group_By)
    //                     ->first();
                    
    //                     if ($room->stage_code == null){
    //                         dd ($room);
    //                     }

    //                 return (object)[
    //                     'stage_code' => $room->stage_code,
    //                     'order_by' => $room->order_by,
    //                     $group_By => $first->$group_By,
    //                     'room_code' => $room->code ?? null,
    //                     'room_name' => $room->name ?? null,
    //                     'unit' => $first->unit,
    //                     'total_qty' => $total_qty,

    //                 ];
    //             }

    //             // Các trường hợp khác (stage, machine, v.v.)
    //             return (object)[
    //                 $group_By => $first->$group_By,
    //                 'unit' => $first->unit,
    //                 'total_qty' => $total_qty,
    //             ];
    //         })
    //     ->values();


    //     // 🔹 Bước 2: Group lại theo stage_code
    //     $merged_by_stage = $merged
    //         ->groupBy('stage_code')
    //         ->map(function ($group) {
    //             return (object)[
    //                 'stage_code' => $group->first()->stage_code,
    //                 'total_qty'  => round($group->sum('total_qty'), 2),
    //                 'details'    => $group->values(), // nếu muốn giữ danh sách chi tiết
    //             ];
    //         })
    //         ->values();
       
        
    //     // --- 4️⃣ Tính tổng theo từng ngày
    //     $dailyTotals = collect();
    //     $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate->copy()->addDay());
        
    //     foreach ($period as $date) {
            
    //         $date = Carbon::instance($date);
    //         $dayStart = $date->copy()->setTime(6,0,0);
    //         $dayEnd = $date->copy()->addDay(1)->setTime(6,0,0);

    //         $totalForDay = DB::table("stage_plan as sp")
    //             ->join('room as r', 'sp.resourceId', '=', 'r.id') // 👈 JOIN thêm bảng room
    //             ->where('sp.deparment_code', session('user')['production_code'])
    //             ->whereNotNull('sp.resourceId')
    //             ->whereNotNull('sp.actual_start')
    //             ->whereRaw('(sp.actual_start <= ? AND sp.actual_end >= ?)', [$dayEnd, $dayStart])
    //             ->select(
    //                 "sp.$group_By",
    //                 'r.code as room_code',
    //                 'r.name as room_name',
    //                 'r.stage_code as stage_code',
    //                 DB::raw('
    //                     SUM(
    //                         sp.yields *
    //                         TIME_TO_SEC(TIMEDIFF(LEAST(sp.actual_end, "'.$dayEnd.'"), GREATEST(sp.actual_start, "'.$dayStart.'"))) /
    //                         TIME_TO_SEC(TIMEDIFF(sp.actual_end, sp.actual_start))
    //                     ) as total_qty
    //                 '),
    //                 DB::raw('
    //                     CASE
    //                         WHEN sp.stage_code <= 4 THEN "Kg"
    //                         ELSE "ĐVL"
    //                     END as unit
    //                 ')
    //             )
    //             ->groupBy("sp.$group_By", "r.code", "r.name",  "r.stage_code", "unit")
    //             ->get();

    //         foreach ($totalForDay as $item) {
    //             $dailyTotals->push([
    //                 $group_By => $item->$group_By,
    //                 "stage_code" => $item->stage_code,
    //                 "room_code" => $item->room_code,
    //                 "room_name" => $item->room_name,
    //                 "unit" => $item->unit,
    //                 "date" => $date->format('Y-m-d'),
    //                 "total_qty" => round($item->total_qty ?? 0, 2),
    //             ]);
    //         }
    //     }

    //     $dailyTotals = $dailyTotals->groupBy("date");
    //     $merged = $merged->sortBy('stage_code')->values();


    //     //dd ($dailyTotals, $dailyTotals, $merged_by_stage);
    //     return [
    //         'yield_room' => $merged,
    //         'yield_day' => $dailyTotals,
    //         'yield_stage' => $merged_by_stage
    //     ];
    // }

    public function yield_actual($startDate, $endDate, $group_By){
        $startDateStr = $startDate->format('Y-m-d H:i:s');
        $endDateStr   = $endDate->format('Y-m-d H:i:s');

        /*
        |--------------------------------------------------------------------------
        | 1️⃣ LẤY SẢN LƯỢNG THEO YIELDS (OVERLAP CHUẨN)
        |--------------------------------------------------------------------------
        */
        $baseQuery = DB::table('stage_plan as sp')
            ->join('yields as y', 'sp.id', '=', 'y.stage_plan_id')
            ->leftJoin('room as r', 'sp.resourceId', '=', 'r.id')

            ->where('sp.deparment_code', session('user')['production_code'])
            ->whereNotNull('sp.resourceId')

            // overlap yield time
            ->whereRaw('(y.start < ? AND y.end > ?)', [$endDateStr, $startDateStr])

            ->select(
                "sp.$group_By",
                "r.code as room_code",
                "r.name as room_name",
                "r.stage_code",
                "r.order_by",

                DB::raw('
                    CASE
                        WHEN sp.stage_code <= 4 THEN "Kg"
                        ELSE "ĐVL"
                    END as unit
                '),

                DB::raw("
                    ROUND(
                        SUM(
                            y.yield *
                            TIME_TO_SEC(
                                TIMEDIFF(
                                    LEAST(y.end, '$endDateStr'),
                                    GREATEST(y.start, '$startDateStr')
                                )
                            ) /
                            NULLIF(TIME_TO_SEC(TIMEDIFF(y.end, y.start)), 0)
                        )
                    ,2) as total_qty
                ")
            )
            ->groupBy(
                "sp.$group_By",
                "r.code",
                "r.name",
                "r.stage_code",
                "r.order_by",
                "unit"
            )
            ->get();


        /*
        |--------------------------------------------------------------------------
        | 2️⃣ GROUP THEO STAGE
        |--------------------------------------------------------------------------
        */
        $yield_stage = $baseQuery
            ->groupBy('stage_code')
            ->map(function ($group) {
                return (object)[
                    'stage_code' => $group->first()->stage_code,
                    'total_qty'  => round($group->sum('total_qty'), 2),
                    'details'    => $group->values(),
                ];
            })
            ->values();


        /*
        |--------------------------------------------------------------------------
        | 3️⃣ TÍNH TỔNG THEO NGÀY (6AM → 6AM)
        |--------------------------------------------------------------------------
        */
        $dailyTotals = collect();

        $period = new \DatePeriod(
            $startDate,
            new \DateInterval('P1D'),
            $endDate->copy()->addDay()
        );

        foreach ($period as $date) {

            $date = Carbon::instance($date);
            $dayStart = $date->copy()->setTime(6,0,0);
            $dayEnd   = $date->copy()->addDay()->setTime(6,0,0);

            $dayStartStr = $dayStart->format('Y-m-d H:i:s');
            $dayEndStr   = $dayEnd->format('Y-m-d H:i:s');

            $dayQuery = DB::table('stage_plan as sp')
                ->join('yields as y', 'sp.id', '=', 'y.stage_plan_id')
                ->leftJoin('room as r', 'sp.resourceId', '=', 'r.id')

                ->where('sp.deparment_code', session('user')['production_code'])
                ->whereNotNull('sp.resourceId')

                ->whereRaw('(y.start < ? AND y.end > ?)', [$dayEndStr, $dayStartStr])

                ->select(
                    "sp.$group_By",
                    'r.code as room_code',
                    'r.name as room_name',
                    'r.stage_code',

                    DB::raw('
                        CASE
                            WHEN sp.stage_code <= 4 THEN "Kg"
                            ELSE "ĐVL"
                        END as unit
                    '),

                    DB::raw("
                        ROUND(
                            SUM(
                                y.yield *
                                TIME_TO_SEC(
                                    TIMEDIFF(
                                        LEAST(y.end, '$dayEndStr'),
                                        GREATEST(y.start, '$dayStartStr')
                                    )
                                ) /
                                NULLIF(TIME_TO_SEC(TIMEDIFF(y.end, y.start)), 0)
                            )
                        ,2) as total_qty
                    ")
                )
                ->groupBy(
                    "sp.$group_By",
                    "r.code",
                    "r.name",
                    "r.stage_code",
                    "unit"
                )
                ->get();

            foreach ($dayQuery as $item) {
                $dailyTotals->push([
                    $group_By   => $item->$group_By,
                    "stage_code"=> $item->stage_code,
                    "room_code" => $item->room_code,
                    "room_name" => $item->room_name,
                    "unit"      => $item->unit,
                    "date"      => $date->format('Y-m-d'),
                    "total_qty" => $item->total_qty ?? 0,
                ]);
            }
        }

        $dailyTotals = $dailyTotals->groupBy("date");

        return [
            'yield_room'  => $baseQuery->sortBy('stage_code')->values(),
            'yield_stage' => $yield_stage,
            'yield_day'   => $dailyTotals
        ];
    }
    
}
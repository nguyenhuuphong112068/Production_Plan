<?php

namespace App\Http\Controllers\Pages\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DailyReportController extends Controller
{
    public function index(Request $request) {

        $department = DB::table('user_management')->where('userName', session('user')['userName'])->value('deparment');

        if ($department == session('user')['production_code']){{
             $reportedDate = $request->reportedDate ?? Carbon::now()->format('Y-m-d');
        }}else {
            $reportedDate = $request->reportedDate ?? Carbon::yesterday()->format('Y-m-d');
        }
       
        $reportedDate = Carbon::parse($reportedDate)->setTime (6,0,0);
        
        $startDate =  $reportedDate->copy();
        $endDate =  $reportedDate->copy()->addDays(1);
        

        //$actual = $this->yield_actual($startDate, $endDate, 'resourceId');

        $yield_actual_detial = $this->yield_actual_detial($startDate, $endDate, 'resourceId');

        $theory = $this->yield_theory($startDate, $endDate, 'resourceId');
        

        $sum_by_next_room = DB::table('stage_plan as t')
            ->leftJoin('stage_plan as t2', function ($join) {
                $join->on('t2.code','=','t.nextcessor_code');
            })
            ->leftJoin('room','t2.resourceId','room.id')
            ->whereNotNull('t.yields')
            ->where('t.deparment_code', session('user')['production_code'])
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
                DB::raw("MIN(room.id) as room_id"),
                

            )
            ->groupBy('next_room')
            ->orderBy('group_code') 
            ->orderBy('next_room')   // sắp xếp theo stage
            ->get();
       
            $explanation = DB::table('explanation')
            ->where('deparment_code', session('user')['production_code'])
            ->where('reported_date', $reportedDate->toDateString())->pluck('content','stage_code');
         
            $displayDate = $reportedDate->format('d/m/Y');
            session()->put(['title' => "BÁO CÁO NGÀY $displayDate"]);
                
            return view('pages.report.daily_report.list', [
                //'actual' => $actual,
                'yield_actual_detial' => $yield_actual_detial,
                'theory' => $theory,
                'sum_by_next_room' => $sum_by_next_room ,
                'reportedDate'    => $displayDate,
                'explanation' => $explanation
            ]);

    }

    public function yield_actual_detial($startDate, $endDate, $group_By){
        $startDateStr = $startDate->format('Y-m-d H:i:s');
        $endDateStr   = $endDate->format('Y-m-d H:i:s');

        /*
        |--------------------------------------------------------------------------
        | 1️⃣ PRODUCTION (TÍNH THEO BẢNG YIELDS)
        |--------------------------------------------------------------------------
        */
        $production = DB::table('stage_plan as sp')
            ->join('yields as y', 'sp.id', '=', 'y.stage_plan_id')
            ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
            ->leftJoin('finished_product_category', 'sp.product_caterogy_id', '=', 'finished_product_category.id')
            ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', '=', 'intermediate_category.intermediate_code')
            ->leftJoin('product_name','finished_product_category.product_name_id','product_name.id')
            ->leftJoin('dosage as d', 'intermediate_category.dosage_id', '=', 'd.id')

            ->where('sp.deparment_code', session('user')['production_code'])

            // 🔥 Yield overlap với khoảng cần tính
            ->whereRaw('(y.start < ? AND y.end > ?)', [$endDateStr, $startDateStr])

            ->select(
                "sp.$group_By",
                "sp.stage_code",

                DB::raw("CONCAT(sp.id, '-yield-', y.id) AS id"),
                DB::raw("CONCAT(product_name.name,'-',COALESCE(plan_master.actual_batch, plan_master.batch)) AS title"),

                // Clamp thời gian theo khoảng cần tính
                DB::raw("GREATEST(y.start, '$startDateStr') AS actual_start"),
                DB::raw("LEAST(y.end, '$endDateStr') AS actual_end"),

                // 🔥 Tính yield theo overlap thật sự
                DB::raw("
                    ROUND(
                        y.yield *
                        TIME_TO_SEC(
                            TIMEDIFF(
                                LEAST(y.end, '$endDateStr'),
                                GREATEST(y.start, '$startDateStr')
                            )
                        ) /
                        NULLIF(TIME_TO_SEC(TIMEDIFF(y.end, y.start)), 0)
                    ,2) AS yields
                "),

                DB::raw("CASE WHEN sp.stage_code = 4 THEN 
                        ROUND(
                                sp.Theoretical_yields_qty *
                                (
                                    (
                                        y.yield *
                                        TIME_TO_SEC(
                                            TIMEDIFF(
                                                LEAST(y.end, '$endDateStr'),
                                                GREATEST(y.start, '$startDateStr')
                                            )
                                        ) /
                                        NULLIF(TIME_TO_SEC(TIMEDIFF(y.end, y.start)), 0)
                                    )
                                    / NULLIF(sp.Theoretical_yields, 0)
                                )
                            , 2)
                    ELSE 0 END AS total_qty_unit"),

                DB::raw('CASE WHEN sp.stage_code <= 4 THEN "Kg" ELSE "ĐVL" END AS unit'),

                DB::raw("
                    CASE 
                        WHEN sp.stage_code = 5 AND d.name COLLATE utf8mb4_unicode_ci LIKE '%phim%' THEN 'coating'
                        WHEN sp.stage_code = 5 AND d.name COLLATE utf8mb4_unicode_ci LIKE '%nang%' THEN 'capsule'
                        WHEN sp.stage_code = 5 THEN 'tablet'
                        ELSE 'NA'
                    END as table_type
                "),

                "sp.note"
            )
        ->get();

        //dd ($production);

        /*
        |--------------------------------------------------------------------------
        | 2️⃣ CLEANING
        |--------------------------------------------------------------------------
        */
        $cleaning = DB::table("stage_plan as sp")
            ->whereNotNull('sp.actual_start_clearning')
            ->whereNotNull('sp.actual_end_clearning')
            ->whereRaw('(sp.actual_start_clearning < ? AND sp.actual_end_clearning > ?)', [$endDateStr, $startDateStr])
            ->where('sp.deparment_code', session('user')['production_code'])

            ->select(
                "sp.$group_By",
                "sp.stage_code",
                DB::raw("CONCAT(sp.id, '-clearning') AS id"),
                DB::raw("CONCAT(sp.title_clearning, ' (', sp.title, ') ') AS title"),
                DB::raw("GREATEST(sp.actual_start_clearning, '$startDateStr') AS actual_start"),
                DB::raw("LEAST(sp.actual_end_clearning, '$endDateStr') AS actual_end"),
                DB::raw("NULL as yields"),
                DB::raw("NULL as yields_batch_qty"),
                DB::raw("NULL as unit"),
                DB::raw("'NA' as table_type"),
                DB::raw("NULL as note")
            )
        ->get();


        /*
        |--------------------------------------------------------------------------
        | 3️⃣ ROOM ACTION
        |--------------------------------------------------------------------------
        */
        $order_action = DB::table("room_status as rs")
            ->leftJoin('room', 'rs.room_id', 'room.id')

            ->whereNotNull('rs.start')
            ->whereNotNull('rs.end')
            ->where('rs.is_daily_report', 1)
            ->where('rs.active', 1)
            ->where('rs.deparment_code', session('user')['production_code'])

            ->whereRaw('(rs.start < ? AND rs.end > ?)', [$endDateStr, $startDateStr])

            ->select(
                "rs.room_id as $group_By",
                "room.stage_code",
                DB::raw("CONCAT(rs.id, '-action') AS id"),
                "rs.in_production as title",
                DB::raw("GREATEST(rs.start, '$startDateStr') AS actual_start"),
                DB::raw("LEAST(rs.end, '$endDateStr') AS actual_end"),
                DB::raw("NULL as yields"),
                DB::raw("NULL as yields_batch_qty"),
                DB::raw("NULL as unit"),
                DB::raw("'NA' as table_type"),
                "rs.notification as note",
                "rs.is_daily_report"
            )
        ->get();


        /*
        |--------------------------------------------------------------------------
        | 4️⃣ MERGE ALL
        |--------------------------------------------------------------------------
        */
        $actual_detail = collect()
            ->concat($production)
            ->concat($cleaning)
            ->concat($order_action)
            ->map(function ($item) use ($group_By) {
                return (object)[
                    'resourceId'    => $item->$group_By,
                    'reported_date' => substr($item->actual_start, 0, 10),
                    'id'            => $item->id,
                    'title'         => $item->title,
                    'start'         => $item->actual_start,
                    'end'           => $item->actual_end,
                    'yields'        => $item->yields,
                    'yields_batch_qty' => $item->total_qty_unit??0,
                    'unit'          => $item->unit,
                    'note'          => $item->note ?? null,
                    'is_order_action' => $item->is_daily_report ?? 0,
                    'table_type'    => $item->table_type ?? 'NA',
                    'stage_code'    => $item->stage_code
                ];
            })
            ->sortBy('start')
            ->values();


        /*
        |--------------------------------------------------------------------------
        | 5️⃣ TỔNG SẢN LƯỢNG THEO RESOURCE
        |--------------------------------------------------------------------------
        */
        
        $yield_day = $actual_detail
            ->whereNotNull('yields')
            ->groupBy('resourceId')
            ->map(function ($items) {
                return [
                    'resourceId'    => $items->first()->resourceId,
                    'total_qty'     => round($items->sum('yields'), 2),
                    'total_qty_unit'   => round($items->sum('yields_batch_qty'), 2),
                    'unit'          => $items->first()->unit,
                    'table_type'    => $items->first()->table_type,
                    'stage_code'    => $items->first()->stage_code,
                ];
            })
            ->values();
       // dd ($yield_day->where ('stage_code', 4));
        return [
            'actual_detail' => $actual_detail,
            'yield_day'     => $yield_day
        ];
    }

    // public function yield_actual_detial ($startDate, $endDate, $group_By){

    //         $startDateStr = $startDate->format('Y-m-d H:i:s');
    //         $endDateStr   = $endDate->format('Y-m-d H:i:s');

    //         // 1) FULL PRODUCTION
    //         $production_full = DB::table("stage_plan as sp")
    //             ->leftJoin('yields', 'sp.id', 'yields.stage_plan_id')
    //             ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
    //             ->leftJoin('finished_product_category', 'sp.product_caterogy_id', '=', 'finished_product_category.id')
    //             ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', '=', 'intermediate_category.intermediate_code')
    //             ->leftJoin('product_name','finished_product_category.product_name_id','product_name.id')
    //             ->leftJoin('dosage as d', 'intermediate_category.dosage_id', '=', 'd.id')
    //             ->whereNotNull('sp.actual_start')
    //             ->whereNotNull('sp.actual_end')
    //             ->whereRaw('(sp.actual_start >= ? AND sp.actual_end <= ?)', [$startDate, $endDate])
    //             ->where('sp.deparment_code', session('user')['production_code'])
    //             ->select(
    //                 "sp.$group_By",
    //                 DB::raw("CONCAT(sp.id, '-main') AS id"),
    //                 DB::raw("CONCAT(product_name.name,'-',COALESCE(plan_master.actual_batch, plan_master.batch)) AS title"),
    //                 "sp.actual_start",
    //                 "sp.actual_end",
    //                 "sp.yields",
    //                 "sp.yields_batch_qty",
    //                 "sp.note",
    //                 "sp.stage_code",
    //                 DB::raw('CASE WHEN sp.stage_code <= 4 THEN "Kg" ELSE "ĐVL" END as unit'),
    //                 DB::raw("CASE 
    //                     WHEN sp.stage_code = 5 AND d.name LIKE '%phim%' THEN 'coating'
    //                     WHEN sp.stage_code = 5 AND d.name LIKE '%nang%' THEN 'capsule'
    //                     WHEN sp.stage_code = 5 AND  (d.name NOT LIKE '%phim%' OR d.name IS NULL) AND (d.name NOT LIKE '%nang%' OR d.name IS NULL) THEN 'tablet'
    //                     ELSE 'NA' END as table_type
    //                     "),
    //             )->get();

    //         // 2) FULL CLEANING
    //         $cleaning_full = DB::table("stage_plan as sp")
    //             ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
    //             ->leftJoin('finished_product_category', 'sp.product_caterogy_id', '=', 'finished_product_category.id')
    //             ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', '=', 'intermediate_category.intermediate_code')
    //             ->leftJoin('product_name','finished_product_category.product_name_id','product_name.id')
    //             ->whereNotNull('sp.actual_start_clearning')
    //             ->whereNotNull('sp.actual_end_clearning')
    //             ->whereRaw('(sp.actual_start_clearning >= ? AND sp.actual_end_clearning <= ?)', [$startDate, $endDate])
    //             ->where('sp.deparment_code', session('user')['production_code'])
    //             ->select(
    //                 "sp.$group_By",
    //                 DB::raw("CONCAT(sp.id, '-clearning') AS id"),
    //                 DB::raw("CONCAT(sp.title_clearning, ' (', sp.title, ') ') AS title"),
    //                // "sp.title_clearning as title",
    //                 "sp.actual_start_clearning",
    //                 "sp.actual_end_clearning",
    //                 "sp.stage_code",
                    
    //         )->get();

    //         // 3) PARTIAL PRODUCTION
    //         $production_partial = DB::table("stage_plan as sp")
    //             ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
    //             ->leftJoin('finished_product_category', 'sp.product_caterogy_id', '=', 'finished_product_category.id')
    //             ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', '=', 'intermediate_category.intermediate_code')
    //             ->leftJoin('product_name','finished_product_category.product_name_id','product_name.id')
    //             ->leftJoin('dosage as d', 'intermediate_category.dosage_id', '=', 'd.id')
    //             ->whereNotNull('sp.actual_start')
    //             ->whereNotNull('sp.actual_end')
    //             ->whereRaw('(sp.actual_start < ? AND sp.actual_end > ?)', [$endDate, $startDate])
    //             ->where('sp.deparment_code', session('user')['production_code'])
    //             ->select(
    //                 "sp.$group_By",
    //                 "sp.stage_code",
    //                 DB::raw("CONCAT(product_name.name,'-',COALESCE(plan_master.actual_batch, plan_master.batch)) AS title"),
    //                 "sp.note",
    //                 DB::raw("CONCAT(sp.id, '-main') AS id"),

    //                 // 🔹 clamp thời gian
    //                 DB::raw("CASE WHEN sp.actual_start < '$startDateStr' THEN '$startDateStr' ELSE sp.actual_start END AS actual_start"),
    //                 DB::raw("CASE WHEN sp.actual_end   > '$endDateStr'   THEN '$endDateStr'   ELSE sp.actual_end   END AS actual_end"),

    //                 // 🔹 yield overlap
    //                 DB::raw("
    //                     ROUND(
    //                         sp.yields *
    //                         TIME_TO_SEC(
    //                             TIMEDIFF(LEAST(sp.actual_end, '$endDateStr'),GREATEST(sp.actual_start, '$startDateStr'))) /
    //                             NULLIF(TIME_TO_SEC(TIMEDIFF(sp.actual_end, sp.actual_start)), 0),
    //                     2) AS yield_overlap
    //                 "),

    //                 // 🔹 yields_batch_qty overlap (CHỈ stage 4)
    //                 DB::raw("
    //                     CASE 
    //                         WHEN sp.stage_code = 4 THEN
    //                             ROUND(
    //                                 sp.yields_batch_qty *
    //                                 TIME_TO_SEC(
    //                                     TIMEDIFF(
    //                                         LEAST(sp.actual_end, '$endDateStr'),
    //                                         GREATEST(sp.actual_start, '$startDateStr')
    //                                     )
    //                                 ) /
    //                                 NULLIF(TIME_TO_SEC(TIMEDIFF(sp.actual_end, sp.actual_start)), 0),
    //                             2)
    //                         ELSE 0
    //                     END AS yields_batch_qty_overlap
    //                 "),

    //                 // 🔹 đơn vị
    //                 DB::raw('CASE WHEN sp.stage_code <= 4 THEN "Kg" ELSE "ĐVL" END AS unit'),
    //                 DB::raw("CASE 
    //                     WHEN sp.stage_code = 5 AND d.name LIKE '%phim%' THEN 'coating'
    //                     WHEN sp.stage_code = 5 AND d.name LIKE '%nang%' THEN 'capsule'
    //                     WHEN sp.stage_code = 5 AND  (d.name NOT LIKE '%phim%' OR d.name IS NULL) AND (d.name NOT LIKE '%nang%' OR d.name IS NULL) THEN 'tablet'
    //                     ELSE 'NA' END as table_type
    //                     "),

    //             )
    //         ->get();
    //         // 4) PARTIAL CLEANING
    //         $cleaning_partial = DB::table("stage_plan as sp")
    //             ->whereNotNull('sp.actual_start_clearning')
    //             ->whereNotNull('sp.actual_end_clearning')
    //             ->whereRaw('(sp.actual_start_clearning < ? AND sp.actual_end_clearning > ?)', [$endDate, $startDate])
    //             ->where('sp.deparment_code', session('user')['production_code'])
    //             ->select(
    //                 "sp.$group_By",
    //                 "sp.stage_code",
    //                 //"sp.title_clearning as title",
    //                 DB::raw("CONCAT(sp.title_clearning, ' (', sp.title,') ') AS title"),
    //                 DB::raw("CONCAT(sp.id, '-clearning') AS id"),
    //                 DB::raw("CASE WHEN sp.actual_start_clearning < '$startDateStr' THEN '$startDateStr' ELSE sp.actual_start_clearning END AS actual_start_clearning"),
    //                 DB::raw("CASE WHEN sp.actual_end_clearning   > '$endDateStr'   THEN '$endDateStr'   ELSE sp.actual_end_clearning   END AS actual_end_clearning")
    //             )->get();

    //         // 5) ROOM_STATUS

    //         $order_action_full = DB::table("room_status as sp")
    //             ->leftJoin('room', 'sp.room_id', 'room.id')
    //             ->whereNotNull('sp.start')
    //             ->whereNotNull('sp.end')
    //             ->where ('sp.is_daily_report', 1)
    //             ->where ('sp.active', 1)
    //             ->whereRaw('(sp.start >= ? AND sp.end <= ?)', [$startDate, $endDate])
    //             ->where('sp.deparment_code', session('user')['production_code'])
    //             ->select(
    //                 "sp.id",
    //                 "room.stage_code",
    //                 //DB::raw("CONCAT(sp.id, '-action') AS id"),
    //                 "sp.room_id as $group_By",
    //                 "sp.start as actual_start",
    //                 "sp.end as actual_end",
    //                 "sp.notification as note",
    //                 "sp.in_production as title",
    //                 'sp.is_daily_report'
    //             )->get();

    //             //dd ($order_action_full);
                    
    //         // 6) ROOM_STATUS_parti      
           
    //         $order_action_partial = DB::table("room_status as sp")
    //             ->leftJoin('room', 'sp.room_id', 'room.id')
    //             ->whereNotNull('sp.start')
    //             ->whereNotNull('sp.end')
    //             ->where('sp.is_daily_report', 1)
    //             ->where('sp.active', 1)
    //             ->where('sp.deparment_code', session('user')['production_code'])

    //             // Giao nhau
    //             ->whereRaw('(sp.start < ? AND sp.end > ?)', [$endDate, $startDate])

    //             // Nhưng loại bỏ FULL
    //             ->whereRaw('NOT (sp.start >= ? AND sp.end <= ?)', [$startDate, $endDate])

    //             ->select(

    //                 "sp.id",
    //                 //DB::raw("CONCAT(sp.id, '-action') AS id"),
    //                 "sp.room_id as $group_By",
    //                 "room.stage_code",
    //                 // Cắt thời gian đang vượt ra ngoài khoảng
    //                 DB::raw("CASE WHEN sp.start < '$startDate' THEN '$startDate' ELSE sp.start END AS actual_start"),
    //                 DB::raw("CASE WHEN sp.end   > '$endDate'   THEN '$endDate'   ELSE sp.end END AS actual_end"),

    //                 "sp.notification as note",
    //                 "sp.in_production as title",
    //                 'sp.is_daily_report'
    //             )
    //         ->get();
                

        
    //         $actual_detail = collect()
    //             ->concat($production_full)
    //             ->concat($cleaning_full)
    //             ->concat($production_partial)
    //             ->concat($cleaning_partial)
    //             ->concat($order_action_full)
    //             ->concat($order_action_partial)
    //             ->unique('id')
    //             ->map(function ($item) use ($group_By) {
    //                 return (object)[
    //                     'resourceId'    => $item->$group_By,
    //                     'reported_date' => substr($item->actual_start ?? $item->actual_start_clearning, 0, 10),
    //                     'id'            => $item->id,
    //                     'title'         => $item->title,
    //                     'start'         => $item->actual_start ?? $item->actual_start_clearning,
    //                     'end'           => $item->actual_end ?? $item->actual_end_clearning,
    //                     'yields'        => $item->yield_overlap ?? $item->yields ?? null,
    //                     'yields_batch_qty'        => $item->yields_batch_qty_overlap ?? $item->yields_batch_qty ?? null,
    //                     'unit'          => $item->unit ?? null,
    //                     "note"          => $item->note ?? null,
    //                     "is_order_action"          => $item->is_daily_report ?? 0,
    //                     'table_type' =>  $item->table_type ?? 'NA',
    //                     "stage_code"  =>  $item->stage_code
    //                 ];
    //             })
    //             ->sortBy('start')
    //         ->values();

    //         $yield_day = collect($actual_detail)
    //             ->groupBy('resourceId')
    //             ->map(function ($items) {
    //                 return [
    //                     'resourceId' => $items->first()->resourceId,
    //                     'total_qty' => round($items->sum(function ($item) {
    //                         return (float) ($item->yields ?? 0);
    //                     }), 2),
    //                     'total_qty_unit' => round($items->sum(function ($item) {
    //                         return (float) ($item->yields_batch_qty ?? 0);
    //                     }), 2),
    //                     'unit' => $items->first()->unit,
    //                     'table_type' => $items->first()->table_type,
    //                     'stage_code' => $items->first()->stage_code,
    //                 ];
    //             })
    //             ->values();

    //         //dd ($yield_day) ;
           
    //         return [
    //             'actual_detail'  => $actual_detail,
    //             'yield_day'   => $yield_day
    //         ];
    // }

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
                    SUM(
                        CASE
                            WHEN sp.Theoretical_yields_qty > 0 THEN sp.Theoretical_yields_qty
                            ELSE 0
                        END
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
                    SUM(
                        CASE
                            WHEN sp.Theoretical_yields_qty > 0 THEN
                                sp.Theoretical_yields_qty *
                                TIME_TO_SEC(TIMEDIFF(LEAST(sp.end, "'.$endDate.'"), GREATEST(sp.start, "'.$startDate.'"))) /
                                TIME_TO_SEC(TIMEDIFF(sp.end, sp.start))
                            ELSE 0
                        END
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
                $total_qty_unit = round($items->sum('total_qty_unit'), 2);

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
                        'total_qty_unit' => $total_qty_unit
                    ];
                }

                // Trường hợp khác (stage, machine,...)
                return (object)[
                    $group_By => $first->$group_By,
                    'unit' => $first->unit,
                    'total_qty' => $total_qty,
                    'total_qty_unit' => $total_qty_unit
                ];
            })
            ->values();


                  // ------------------------------
        // 4️⃣ MERGE ROOMS + MERGED DATA
        // ------------------------------
        $rooms = DB::table ('room')->where('deparment_code', session('user')['production_code'])->get();
        $yield_room = $rooms->map(function ($room) use ($merged, $group_By) {

            // Tìm xem phòng có dữ liệu sản lượng không
            $found = $merged->firstWhere($group_By, $room->id);

            return (object)[
                $group_By     => $room->id,
                'room_code'   => $room->code,
                'room_name'   => $room->name,
                'stage_code'  => $room->stage_code,
                'order_by'    => $room->order_by,
                'unit'        => $found->unit ?? null,
                'total_qty'   => $found->total_qty ?? 0,
                'total_qty_unit' => $found->total_qty_unit?? 0
            ];
        })->sortBy('order_by')->values();

       
        // ------------------------------
        // 4️⃣ Tổng hợp theo ROOM
        // ------------------------------
        $yield_room = $yield_room->sortBy('stage_code')->values();

       
        // ------------------------------
        // 5️⃣ Tổng hợp theo STAGE
        // ------------------------------
        $yield_stage = $yield_room
            ->groupBy('stage_code')
            ->map(function ($group) {
                return (object)[
                    'stage_code' => $group->first()->stage_code,
                    'total_qty' => round($group->sum('total_qty'), 2),
                    'total_qty_unit' => round($group->sum('total_qty_unit'), 2),
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
                "total_qty_unit" => round($item->total_qty_unit ?? 0, 2),
            ]);
        }
      

        //$dailyTotals1 = $dailyTotals->groupBy("date");

       
        //dd ($dailyTotals, $yield_stage);
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
                     DB::raw("COALESCE(plan_master.actual_batch, plan_master.batch) AS batch"),
                    //'plan_master.batch',
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
                //'created_by' => session ('user')['fullName'],
                //'created_at' => now(),
               
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
       //dd ($request->all(), session('user')['production_code']);
        DB::table('explanation')->updateOrInsert(
            [
                'reported_date'   => $request->reported_date,
                'stage_code'      => $request->stage_code,
                'deparment_code'  => session('user')['production_code'],
            ],
            [
                'content'     => $request->note,
                'created_by'  => session('user')['fullName'],
                'updated_at'  => now(),
                'created_at'  => now(),
            ]
        );
        return redirect()->back()->with('success', 'Đã thêm thành công!');    
    }

    public function store (Request $request) {
            
                $validator = Validator::make($request->all(), [
                    'in_production' => 'required',
                    'start' => 'required',
                    'end' => 'required',
                ],[
            
                    'in_production.required' => 'Hoạt Động Không Được Để Trống', 
                    'start.required' => 'Nhập Giờ Bắt Đầu',  
                    'end.required' => 'Nhập Giờ Kết Thúc',
                ]);

                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }
                
                DB::table('room_status')->insert([
                        'room_id' => $request->room_id,
                        'status' => 1,
                        'start' => $request->start,
                        'end' => $request->end,
                        'in_production' => $request->in_production,
                        'notification' => $request->notification??"NA",
                        'is_daily_report' => 1,
                        'deparment_code' => session('user')['production_code'],
                        'created_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Đã thêm thành công!');    
    }

    public function update (Request $request) {
                
                $validator = Validator::make($request->all(), [
                    'id' => 'required',
                    'in_production' => 'required',
                    'start' => 'required',
                    'end' => 'required',
                ],[
                    'id.required' => 'Chọn Hoạt Động Cần Sửa', 
                    'in_production.required' => 'Hoạt Động Không Được Để Trống', 
                    'start.required' => 'Nhập Giờ Bắt Đầu',  
                    'end.required' => 'Nhập Giờ Kết Thúc',
                ]);

                //dd ($request->all(), $validator->fails());

                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                }
                
                DB::table('room_status')->where ('id', explode ("-",$request->id)[0])->update([
            
                        'start' => $request->start,
                        'end' => $request->end,
                        'in_production' => $request->in_production,
                        'notification' => $request->notification??"NA",
                        
                        'created_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);

                return redirect()->back()->with('success', 'Đã cập nhật thành công!');    
    }

    public function deActive (Request $request) {
                //dd ($request->all());
                $validator = Validator::make($request->all(), [
                    'id' => 'required',
                ],[
                    'id.required' => 'Chọn Hoạt Động Cần Sửa', 
                ]);

                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }
                
                DB::table('room_status')->where ('id', $request->id)->update([
                        'active' => 0,
                        'created_by' => session('user')['fullName'],
                        'created_at' => now(),
                ]);

                return redirect()->back()->with('success', 'Đã hủy thành công!');    
    }


}

  
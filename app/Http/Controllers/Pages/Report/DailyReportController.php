<?php

namespace App\Http\Controllers\Pages\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyReportController extends Controller
{
    public function index(Request $request) {

        $reportedDate = Carbon::parse ($request->reportedDate)->addDays(1)->setTime (6,0,0) ?? Carbon::parse (now ())->setTime (6,0,0);
        
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
}

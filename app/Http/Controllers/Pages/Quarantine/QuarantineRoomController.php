<?php

namespace App\Http\Controllers\Pages\Quarantine;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuarantineRoomController extends Controller
{
    
    public function index(Request $request) {

        $startDate = $request->from_date
            ? Carbon::parse($request->from_date)->setTime(5, 59, 59)
            : Carbon::now()->startOfMonth()->setTime(5, 59, 59);

        $endDate = $request->to_date
            ? Carbon::parse($request->to_date)->setTime(6, 0, 0)
            : Carbon::now()->endOfMonth()->setTime(6, 0, 0);

        $intervalHours = 24;

        $timePoints = [];
        for ($t = strtotime($startDate); $t <= strtotime($endDate); $t += 3600 * $intervalHours) {
            $timePoints[] = date('Y-m-d H:i:s', $t);
        }

        $results = [];
    
        $stages = DB::table('stage_plan as t')
            ->leftJoin('stage_plan as t2', function ($join) {
                $join->on('t2.code','=','t.nextcessor_code');
            })
            ->leftJoin('finished_product_category as fc', 't.product_caterogy_id', '=', 'fc.id')
            ->whereNotNull('t.start')
            ->where('t.active', 1)
            ->where('t.finished', 0)
            ->select(
                'fc.finished_product_code',
                'fc.intermediate_code',
                't.plan_master_id',
                't.stage_code',
                't.theoretical_yields',
                't.nextcessor_code',
                't.start',
                
                
                't2.start as next_start'
            )
            ->orderBy('t.plan_master_id')
            ->orderBy('t.stage_code')
            ->get();
        
        $results = [];

        foreach ($timePoints as $T) {
            $filtered = $stages->filter(function ($r) use ($T) {
                return ($r->start <= $T) && (is_null($r->next_start) || $r->next_start > $T) && $r->stage_code < 7;
            });
            foreach ($filtered as $r) {
                     $results[$T][$r->stage_code][] = [
                    'next_stage_code' => explode("_",$r->nextcessor_code??"_0")[1],
                    'plan_master_id' => $r->plan_master_id,
                    'time_point' => $T,
                    'intermediate_code' => $r->intermediate_code,
                    'finished_product_code' => $r->finished_product_code,
                    'stage_code' => $r->stage_code,
                    'theoretical_stock' => (float)$r->theoretical_yields,
                ];
            }
        }

        $totals = [];

        foreach ($results as $timePoint => $stages) {
            foreach ($stages as $stageCode => $entries) {
                // Tính tổng theoretical_stock cho stage_code này
                $sum = collect($entries)->sum('theoretical_stock');

                // Lưu kết quả
                $totals[$stageCode][$timePoint] = $sum;
            }
        }

        $stageTimeSeries = [];

        foreach ($totals as $stageCode => $points) {
            foreach ($points as $timePoint => $value) {
                $stageTimeSeries[$stageCode][] = [
                    'stage_code'   => $stageCode,
                    'time_point'   => $timePoint,
                    'room_id'      => 0, // giả lập vì không có room
                    'room_name'    => 'Tổng tồn', // tên hiển thị trên legend
                    'total_stock'  => $value,
                ];
            }
        }

        
        session()->put(['title' => 'TỒN BÁN THÀNH PHẨM THEO CÔNG ĐOẠN']);

        return view('pages.quarantine.theory.list', [
            'totals' => $totals,
            'timePoints' => $timePoints,
            'stageTimeSeries' => $stageTimeSeries,
        ]);

    }

    public function index_actual(Request $request) {
       
        
        // 1) Lấy toàn bộ dữ liệu gốc
        $datasRaw = DB::table('stage_plan as t')
            ->leftJoin('stage_plan as t2', function ($join) {
                $join->on('t2.code','=','t.nextcessor_code');
            })
            ->leftJoin('plan_master','t.plan_master_id','plan_master.id')
            ->leftJoin('finished_product_category as fc', 't.product_caterogy_id', '=', 'fc.id')
            ->leftJoin('product_name','fc.product_name_id','product_name.id')
            ->leftJoin('quarantine_room','t.quarantine_room_code','quarantine_room.code')
            ->leftJoin('room','t2.resourceId','room.id')
            ->whereNotNull('t.start')
            ->whereNotNull('t.yields')
            ->whereNotNull('t.quarantine_room_code')
            ->where('t2.start','>',now())
            ->where('t.active', 1)
            ->where('t.finished', 1)
            ->where('quarantine_room.deparment_code', session('user')['production_code'])
            ->select(
                'fc.finished_product_code',
                'fc.intermediate_code',
                't.plan_master_id',
                'product_name.name as product_name',
                'plan_master.batch',
                't.quarantine_room_code',
                'quarantine_room.name',
                't.yields',
                't.stage_code',
                't.number_of_boxes',
    
                't2.stage_code as next_stage',
                't2.start as next_start',
                't2.resourceId as next_room_id',
                DB::raw("CONCAT(room.code, ' - ', room.name, ' - ', room.main_equiment_name) as next_room"),
               'room.production_group as production_group',
               'room.stage as stage',
               'room.group_code',
            )
            ->orderBy('t.plan_master_id')
            ->orderBy('t.stage_code')
            ->get();

        // 2) Group theo phòng (datas chính)
        $datas = $datasRaw->groupBy('quarantine_room_code')->map(function ($items) {
            return [
                'room_name' => $items->first()->name,
                'total_yields' => $items->sum('yields'),
                'details' => $items
                
            ];
        });


       $sum_by_next_room = DB::table('stage_plan as t')
            ->leftJoin('stage_plan as t2', function ($join) {
                $join->on('t2.code','=','t.nextcessor_code');
            })
            ->leftJoin('room','t2.resourceId','room.id')
            ->whereNotNull('t.start')
            ->whereNotNull('t.yields')
            ->where('t2.start','>',now())
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

        //dd ($sum_by_next_room);

        

        session()->put(['title' => 'QUẢN LÝ BIỆT TRỮ']);

        return view('pages.quarantine.actual.list', [
            'datas' => $datas,
            'sum_by_next_room' => $sum_by_next_room ,
        ]);

    }

    public function detail(Request $request) {

        
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
            ->where('t2.start','>',now())
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
    
}

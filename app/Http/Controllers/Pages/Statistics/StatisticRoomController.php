<?php

namespace App\Http\Controllers\Pages\Statistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StatisticRoomController extends Controller
{
    public function index(Request $request){
        $production = session('user')['production'];

        // ---- 1. Xác định khoảng thời gian người dùng chọn hoặc mặc định ----
        $fromDate = $request->from_date ?? Carbon::now()->subMonth(1)->toDateString();
        $toDate   = $request->to_date   ?? Carbon::now()->toDateString();

        $fromDate = Carbon::parse($fromDate);
        $toDate   = Carbon::parse($toDate);

        $totalDays   = $fromDate->diffInDays($toDate);
        $totalHours  = $totalDays * 24;

        $rooms = DB::table('room')->select('id', 'code', 'name', 'stage_code')->where('deparment_code', $production)->get();
       
        $datas = DB::table('stage_plan')
                ->select(
                        'stage_plan.resourceId', 
                        DB::raw('COUNT(DISTINCT stage_plan.plan_master_id) as so_lo'),
                        DB::raw('SUM(TIMESTAMPDIFF(HOUR, stage_plan.start_clearning, stage_plan.end_clearning)) as tong_thoi_gian_vesinh'),
                        DB::raw('SUM(TIMESTAMPDIFF(HOUR, stage_plan.start, stage_plan.end)) as tong_thoi_gian_sanxuat'),
                        DB::raw('SUM(stage_plan.yields) as san_luong_thuc_te')
                )
                ->whereBetween('stage_plan.start', [$fromDate, $toDate])
                ->where('stage_plan.active', 1)
                ->where('stage_plan.deparment_code', $production)
                ->where('stage_plan.finished', 1)
                ->groupBy('stage_plan.resourceId')
                ->get();

         
        
        $yields = $this->yield($fromDate,  $toDate, 'resourceId');

        $rooms = collect($rooms)->map(function ($room) use ($datas, $yields) {
                // Tìm dữ liệu thực tế theo resourceId
                $dataItem = $datas->firstWhere('resourceId', $room->id);
                // Tìm sản lượng lý thuyết theo resourceId
                $theoretical = $yields->firstWhere('resourceId', $room->id);

                return (object)[
                        'id'                  => $room->id,
                        'code'                => $room->code,
                        'stage_code'          => $room->stage_code,
                        'name'                => $room->name,
                        'label'               => $room->name ."-".$room->code,
                        'so_lo'               => $dataItem->so_lo ?? 0,
                        'tong_thoi_gian_vesinh' => $dataItem->tong_thoi_gian_vesinh ?? 0,
                        'tong_thoi_gian_sanxuat' => $dataItem->tong_thoi_gian_sanxuat ?? 0,
                        'san_luong_thuc_te'   => $dataItem->san_luong_thuc_te ?? 0,
                        'san_luong_ly_thuyet' => $theoretical->total_qty ?? 0,
                ];
        });

        $groupedByStage = $rooms->groupBy('stage_code');
        
        //dd ($groupedByStage);
        
        session()->put(['title' => 'THỐNG KÊ THỜI GIAN HOẠT ĐỘNG THEO PHÒNG SẢN XUẤT']);
       
        return view('pages.statistics.room.list', [
            'rooms'     => $rooms,
            'groupedByStage' =>  $groupedByStage,
            'totalHours'   => $totalHours,
        ]);
    }
    
    public function yield($startDate, $endDate, $group_By){
               return DB::table('stage_plan as sp')
                ->leftJoin('intermediate_category as ic', 'sp.product_caterogy_id', '=', 'ic.id')
                ->leftJoin('finished_product_category as fc', 'sp.product_caterogy_id', '=', 'fc.id')
                ->whereBetween('sp.start', [$startDate, $endDate])
                ->whereNotNull('sp.start')
                ->select(
                    "sp.$group_By",
                    DB::raw('
                        SUM(
                            CASE 
                                WHEN sp.stage_code <= 4 THEN ic.batch_size
                                WHEN sp.stage_code <= 6 THEN ic.batch_qty
                                ELSE fc.batch_qty
                            END
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
    }
}

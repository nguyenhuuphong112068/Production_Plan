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
            ->leftJoin('finished_product_category', 'stage_plan.product_caterogy_id', '=', 'finished_product_category.id')
            ->leftJoin('intermediate_category', 'intermediate_category.intermediate_code', '=', 'finished_product_category.intermediate_code')
            ->select(
                'stage_plan.resourceId', 
                DB::raw('COUNT(DISTINCT stage_plan.plan_master_id) as so_lo'),
                DB::raw('SUM(TIMESTAMPDIFF(HOUR, stage_plan.start_clearning, stage_plan.end_clearning)) as tong_thoi_gian_vesinh'),
                DB::raw('SUM(TIMESTAMPDIFF(HOUR, stage_plan.start, stage_plan.end)) as tong_thoi_gian_sanxuat'),
                DB::raw('SUM(stage_plan.yields) as san_luong_thuc_te'),
                DB::raw('
                    SUM(
                        CASE 
                            WHEN stage_plan.stage_code <= 4 THEN intermediate_category.batch_size
                            WHEN stage_plan.stage_code <= 6 THEN intermediate_category.batch_qty
                            ELSE finished_product_category.batch_qty
                        END
                    ) as total_qty
                ')
            )
            ->whereBetween('stage_plan.start', [$fromDate, $toDate])
            ->where('stage_plan.active', 1)
            ->where('stage_plan.deparment_code', $production)
            ->where('stage_plan.finished', 1)
            ->groupBy('stage_plan.resourceId')
            ->get();
      

        //$yields = $this->yield($fromDate,  $toDate, 'resourceId');
        
        

        $rooms = collect($rooms)->map(function ($room) use ($datas) {
                // Tìm dữ liệu thực tế theo resourceId
                $dataItem = $datas->firstWhere('resourceId', $room->id);
                // Tìm sản lượng lý thuyết theo resourceId

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
                        'san_luong_ly_thuyet' => $dataItem->total_qty ?? 0,
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
    

}

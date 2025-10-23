<?php

namespace App\Http\Controllers\Pages\Status;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StatusController extends Controller
{
        public function index(){
                $production =  session('user')['production_code']??"PXV1";

                $now = Carbon::now();
                $now = Carbon::now();

                //dd ($datas);
                $datas = DB::table('room')
                ->leftJoin('stage_plan', function ($join) use ($now) {
                        $join->on('room.id', '=', 'stage_plan.resourceId')
                        ->where('stage_plan.active', true)
                        ->where('stage_plan.finished', false)
                        ->whereRaw('? BETWEEN stage_plan.start AND stage_plan.end', [$now]);
                })
                ->leftJoin('plan_master', 'stage_plan.plan_master_id', '=', 'plan_master.id')
                ->leftJoin('finished_product_category', 'stage_plan.product_caterogy_id', '=', 'finished_product_category.id')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                // ✅ Lấy dòng room_status mới nhất theo room_id
                ->leftJoinSub(
                        DB::table('room_status as rs1')
                        ->select('rs1.room_id', 'rs1.status', 'rs1.in_production', 'rs1.notification')
                        ->whereRaw('rs1.id = (SELECT MAX(rs2.id) FROM room_status rs2 WHERE rs2.room_id = rs1.room_id)')
                        , 'rs', function ($join) {
                                $join->on('room.id', '=', 'rs.room_id');
                        }
                )
                ->where('room.deparment_code', $production)
                ->select(
                        'room.stage_code',
                        'room.stage',
                        DB::raw("CONCAT(room.code,'-', room.name) as room_name"),
                        DB::raw("COALESCE(product_name.name, 'Không có lịch sản xuất') as product_name"),
                        'plan_master.batch',
                        // ✅ Giá trị mặc định khi không có room_status
                        DB::raw("COALESCE(rs.status, 0) as status"),
                        DB::raw("COALESCE(rs.in_production, 'Không sản xuất') as in_production"),
                        DB::raw("COALESCE(rs.notification, 'NA') as notification")
                )
                ->orderBy('room.order_by')
                ->get();
                //dd ($datas);
                
                session()->put(['title'=> "TRANG THÁI PHÒNG SẢN XUẤT"]);
              
                return view('pages.status.list',[
                        'datas' =>  $datas 
                        
                ]);
        }
}

<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SchedualFinisedController extends Controller
{
        public function index(Request $request){
                //dd ($request->all());

                $fromDate = $request->from_date ?? Carbon::now()->toDateString();
                $toDate   = $request->to_date ?? Carbon::now()->addMonth(2)->toDateString(); 
                $stage_code = $request->stage_code??3;
                $production = session('user')['production_code'];
      
                // 🔹 1. Lấy dữ liệu mới nhất cho mỗi stage_plan_id
                $datas = DB::table('stage_plan as sp')
                    ->select(
                        'sp.*',
                        'room.name as room_name',
                        'room.code as room_code',
                        'room.stage as stage',
                        'plan_master.batch',
                        'plan_master.expected_date',
                        'plan_master.is_val',
                        'finished_product_category.intermediate_code',
                        'finished_product_category.finished_product_code',
                        'finished_product_category.batch_qty',
                        'finished_product_category.unit_batch_qty',
                        'product_name.name as product_name'
                    
                    )
                    ->leftJoin('room', 'sp.resourceId', '=', 'room.id')
                    ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
                    ->leftJoin('finished_product_category', 'sp.product_caterogy_id', '=', 'finished_product_category.id')
                    ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                    ->whereBetween('sp.start', [$fromDate, $toDate])
                    ->where('sp.stage_code', $stage_code)
                    ->where('sp.deparment_code', $production)
                    ->whereNotNull('sp.start')
                    ->where('sp.finished',0)
                    ->get();

                $quarantine_room = DB::table('quarantine_room')
                        ->where(function ($query) use ($production) {
                                $query->where('deparment_code', $production)
                                ->orWhere('deparment_code', 'NA');
                        })
                        ->where('active', true)
                ->get();

                  
        
                $stages = DB::table('stage_plan')
                    ->select('stage_plan.stage_code', 'room.stage')
                    ->where('stage_plan.deparment_code', $production)
        
                    ->whereNotNull('stage_plan.start')
                    ->leftJoin('room', 'stage_plan.resourceId', 'room.id')
                    ->distinct()
                    ->orderby ('stage_code')
                ->get();

                 $stageCode = $request->input('stage_code', optional($stages->first())->stage_code);
                
                //dd ($datas);
                session()->put(['title'=> 'XÁC NHẬN HOÀN THÀNH LÔ SẢN XUẤT']);
                return view('pages.Schedual.finised.list',[

                        'datas' => $datas,
                        'stages' => $stages,
                        'stageCode' => $stageCode,
                        'quarantine_room' => $quarantine_room
                    
                ]);
        }

        public function store(Request $request) {
              
                DB::table('stage_plan')
                        ->where('id', $request->id)
                        ->update([
                        'start'           => $request->start,
                        'end'             => $request->end,
                        'start_clearning' => $request->start_clearning,
                        'end_clearning'   => $request->end_clearning,
                        'yields'   => $request->yields,
                        'quarantine_room_code'   => $request->quarantine_room_code,
                        'finished'        => 1,
                        'finished_by'   => session('user')['fullName'],
                        'finished_date'   => now(),
                ]);


                 return redirect()->back()->with('success', 'Đã thêm thành công!');   
        }


}

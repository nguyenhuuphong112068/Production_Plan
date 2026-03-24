<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
//use Illuminate\Support\Facades\Log;

class SchedualQuarantineRoomController extends Controller
{
        public function index(Request $request)
        {
                //dd ($request->all());

                //$fromDate = $request->from_date ?? Carbon::now()->toDateString();
                //$toDate   = $request->to_date ?? Carbon::now()->addMonth(2)->toDateString(); 
                $stage_code = $request->stage_code ?? 1;
                $production = session('user')['production_code'];

                $yieldSub = DB::table('yields')
                        ->select(
                                'stage_plan_id',
                                DB::raw('SUM(yield) as sum_actual_yeild')
                        )
                        ->groupBy('stage_plan_id');

                // 🔹 1. Lấy dữ liệu mới nhất cho mỗi stage_plan_id
                $datas = DB::table('stage_plan as sp')
                        ->leftJoinSub($yieldSub, 'y_sum', function ($join) {
                                $join->on('sp.id', '=', 'y_sum.stage_plan_id');
                        })
                        ->leftJoin('room', 'sp.resourceId', '=', 'room.id')
                        ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
                        ->leftJoin('finished_product_category', 'sp.product_caterogy_id', '=', 'finished_product_category.id')
                        ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                        ->select(
                                'sp.*',
                                'room.name as room_name',
                                'room.code as room_code',
                                'room.stage as stage',
                                DB::raw("COALESCE(plan_master.actual_batch, plan_master.batch) AS batch"),
                                'plan_master.expected_date',
                                'plan_master.is_val',
                                'finished_product_category.intermediate_code',
                                'finished_product_category.finished_product_code',
                                'finished_product_category.batch_qty',
                                'finished_product_category.unit_batch_qty',
                                'product_name.name as product_name',
                                DB::raw("
                                        TRIM(TRAILING '.' FROM TRIM(TRAILING '0' 
                                        FROM FORMAT(COALESCE(y_sum.sum_actual_yeild,0), 3)
                                        )) as yields
                                ")
                        )
                        ->where('sp.stage_code', $stage_code)
                        ->where('sp.deparment_code', $production)
                        ->whereNotNull('sp.actual_start')
                        ->where('sp.finished', 1)
                        ->whereNotNull('y_sum.sum_actual_yeild')
                        ->whereNull('sp.quarantine_room_code')
                        ->get();

                $quarantine_room = DB::table('quarantine_room')
                        ->where(function ($query) use ($production) {
                                $query->where('deparment_code', $production)
                                        ->orWhere('deparment_code', 'NA');
                        })
                        ->where('active', true)
                        ->get();



                $stages = DB::table('stage_plan')
                        ->select(
                                'stage_plan.stage_code',
                                DB::raw("
                        CASE 
                                WHEN stage_plan.stage_code = 2 THEN 'Cân Nguyên Liệu Khác'
                                ELSE room.stage
                        END AS stage
                        ")
                        )
                        ->leftJoin('room', 'stage_plan.stage_code', '=', 'room.stage_code')
                        ->where('stage_plan.deparment_code', $production)
                        ->where('stage_plan.stage_code', '<', 7)
                        ->distinct()
                        ->orderBy('stage_plan.stage_code')
                        ->get();


                $stageCode = $request->input('stage_code', optional($stages->first())->stage_code);

                //dd ($stages);
                session()->put(['title' => 'XÁC ĐỊNH VỊ TRÍ PHÒNG BIỆT TRỮ BÁN THÀNH PHẨM']);
                return view('pages.Schedual.quarantine_room.list', [

                        'datas' => $datas,
                        'stages' => $stages,
                        'stageCode' => $stageCode,
                        'quarantine_room' => $quarantine_room

                ]);
        }

        public function store(Request $request)
        {

                DB::table('stage_plan')
                        ->where('id', $request->id)
                        ->update([
                                'quarantine_room_code'   => $request->quarantine_room_code,
                                'quarantined_by'   => session('user')['fullName'],
                                'quarantined_date'   => now(),
                        ]);


                return redirect()->back()->with('success', 'Đã thêm thành công!');
        }
}

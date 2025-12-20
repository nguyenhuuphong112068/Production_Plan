<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

//use Illuminate\Support\Facades\Log;

class SchedualFinisedController extends Controller
{
        public function index(Request $request){
              

                //$fromDate = $request->from_date ?? Carbon::now()->toDateString();
                //$toDate   = $request->to_date ?? Carbon::now()->addMonth(2)->toDateString(); 
                
                $stage_code = $request->stage_code??1;

                $stage_code_room =  $stage_code;

                if ($stage_code == 2){ $stage_code_room = 1;}
                
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
                ->where('sp.stage_code', $stage_code)
                ->where('sp.deparment_code', $production)

                // 🔹 finished logic
                ->where(function ($q) {
                        $q->where('sp.finished', 0)
                        ->orWhere(function ($q2) {
                        $q2->where('sp.finished', 1)
                                ->whereNull('sp.actual_start_clearning');
                        });
                })

                // 🔹 loại trừ bản ghi lỗi
                ->whereNot(function ($q) {
                        $q->where('sp.finished', 1)
                        ->whereNull('sp.actual_start')
                        ->whereNull('sp.start');
                })

                ->orderBy('sp.start')
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
                ->distinct()
                ->orderBy('stage_plan.stage_code')
                ->get();

            

                //dd ($stages);

                $stageCode = $request->input('stage_code', optional($stages->first())->stage_code);
                $room_stages = DB::table('room')
                        ->where ('stage_code', $stage_code_room)
                         ->where('deparment_code', $production)
                         ->get();


                //dd ($datas);
                session()->put(['title'=> 'XÁC NHẬN HOÀN THÀNH LÔ SẢN XUẤT']);
                return view('pages.Schedual.finised.list',[

                        'datas' => $datas,
                        'stages' => $stages,
                        'stageCode' => $stageCode,
                        'room_stages' => $room_stages
                        //'quarantine_room' => $quarantine_room
                    
                ]);
        }

        public function store(Request $request){
                Log::info($request->all());

                /* ===============================
                1. FORMAT DATE TIME (LUÔN Ở ĐẦU)
                =============================== */
                $actualStart = $request->start
                ? Carbon::parse($request->start)->format('Y-m-d H:i:s')
                : null;

                $actualEnd = $request->end
                ? Carbon::parse($request->end)->format('Y-m-d H:i:s')
                : null;

                $actualStartCleaning = $request->start_clearning
                ? Carbon::parse($request->start_clearning)->format('Y-m-d H:i:s')
                : null;

                $actualEndCleaning = $request->end_clearning
                ? Carbon::parse($request->end_clearning)->format('Y-m-d H:i:s')
                : null;

                if ($request->actionType === 'finised') {
                        if ( $actualStartCleaning == null || $actualEndCleaning == null || $actualStart == null || $actualEnd  == null){
                                return response()->json([
                                'message' => '❌ Thời gian Sản Xuất Không Hợp Lệ'
                                ], 422);
                        }

                } else {
                        if ($actualStart == null || $actualEnd  == null){
                                return response()->json([
                                'message' => '❌ Thời gian Sản Xuất / Vệ Sinh Không Hợp Lệ'
                                ], 422);
                        }
                
                }

                /* ===============================
                2. VALIDATE TIME LOGIC
                =============================== */
                if ($actualStart && $actualEnd && $actualEnd <= $actualStart) {
                        return response()->json([
                                'message' => '❌ Thời gian kết thúc phải lớn hơn thời gian bắt đầu'
                                ], 422);
                }

                /* ===============================
                3. TÍNH YIELDS BATCH QTY (STAGE 4)
                =============================== */
                $yields_batch_qty = null;

                if ((int)$request->stage_code === 4) {

                $stagePlan = DB::table('stage_plan')
                        ->where('id', $request->id)
                        ->first();

                if ($stagePlan && $stagePlan->Theoretical_yields > 0) {

                        $batch_qty = DB::table('finished_product_category')
                        ->where('id', $stagePlan->product_caterogy_id)
                        ->value('batch_qty');

                        $yields_batch_qty = round(
                        ($request->yields / $stagePlan->Theoretical_yields) * $batch_qty,
                        2
                        );
                }
                }

                /* ===============================
                4. DATA UPDATE CHUNG
                =============================== */
                $updateData = [
                        'title'            => $request->title,
                        'resourceId'       => $request->resourceId,
                        'actual_start'     => $actualStart,
                        'actual_end'       => $actualEnd,
                        'yields'           => $request->yields,
                        'yields_batch_qty' => $yields_batch_qty,
                        'number_of_boxes'  => $request->number_of_boxes ?? 1,
                        'note'             => $request->note ?? 'NA',
                        'finished_by'      => session('user')['fullName'],
                        'finished_date'    => now(),
                ];

                /* ===============================
                5. PHÂN BIỆT FINISHED / SEMI
                =============================== */
                if ($request->actionType === 'finised') {

                        $updateData = array_merge($updateData, [
                                'actual_start_clearning' => $actualStartCleaning,
                                'actual_end_clearning'   => $actualEndCleaning,
                                'finished'               => 1,
                        ]);

                } elseif ($request->actionType === 'semi-finised') {

                        $updateData = array_merge($updateData, [
                                'finished' => 1,
                        ]);

                } else {
                       
                        return response()->json([
                                'message' => '❌ actionType không hợp lệ'
                                ], 422);
                        
                }

                /* ===============================
                6. UPDATE DB
                =============================== */
                DB::table('stage_plan')
                ->where('id', $request->id)
                ->update($updateData);

                return back()->with('success', '✅ Cập nhật công đoạn thành công!');
        }


        // public function store(Request $request) {

        //         Log::info ($request->all());
                
        //         $yields_batch_qty = null;
        //         if ($request->stage_code == 4){
        //                 $stage_plan = DB::table('stage_plan')->where('id', $request->id)->first();
        //                 $batch_qty = DB::table('finished_product_category')->where('id', $stage_plan->product_caterogy_id)->value('batch_qty');
        //                 $yields_batch_qty = round(($request->yields/$stage_plan->Theoretical_yields) * $batch_qty,2);
        //         }else

        //         $actualStart = $request->start
        //         ? Carbon::parse($request->start)->format('Y-m-d H:i:s')
        //         : null;

        //         $actualEnd = $request->end
        //         ? Carbon::parse($request->end)->format('Y-m-d H:i:s')
        //         : null;

        //         $actualStartCleaning = $request->start_clearning
        //         ? Carbon::parse($request->start_clearning)->format('Y-m-d H:i:s')
        //         : null;

        //         $actualEndCleaning = $request->end_clearning
        //         ? Carbon::parse($request->end_clearning)->format('Y-m-d H:i:s')
        //         : null;



        //         if ($request->actionType == 'finised') {
        //                 DB::table('stage_plan')
        //                 ->where('id', $request->id)
        //                 ->update([
                                   
        //                 //'start'           => $request->start,
        //                 //'end'             => $request->end,
        //                 //'start_clearning' => $request->start_clearning,
        //                 //'end_clearning'   => $request->end_clearning,
        //                 'title'                  => $request->title,
        //                 'resourceId'            => $request->resourceId,

        //                 'actual_start'           => $actualStart,
        //                 'actual_end'             => $actualEnd,
        //                 'actual_start_clearning' => $actualStartCleaning,
        //                 'actual_end_clearning'   => $actualEndCleaning,
        //                 'yields_batch_qty'        => $yields_batch_qty??null,
        //                 'yields'   => $request->yields,
        //                 'number_of_boxes'   => $request->number_of_boxes??1,
        //                 'note'   => $request->note??"NA",
        //                 'finished'        => 1,
        //                 'finished_by'   => session('user')['fullName'],
        //                 'finished_date'   => now(),
        //                 ]);

        //         }else {

        //                 DB::table('stage_plan')
        //                 ->where('id', $request->id)
        //                 ->update([
                                   
        //                 'actual_start'        => $actualStart,
        //                 'actual_end'           => $actualEnd,
       
        //                 'title'                  => $request->title,
        //                 'resourceId'            => $request->resourceId,        
        //                 'yields'   => $request->yields,
        //                 'yields_batch_qty'        => $yields_batch_qty??null,
        //                 'number_of_boxes'   => $request->number_of_boxes??1,
        //                 'note'   => $request->note??"NA",
        //                 'finished'        => 1,
        //                 'finished_by'   => session('user')['fullName'],
        //                 'finished_date'   => now(),
        //                 ]);

        //         }
                

        //          return redirect()->back()->with('success', 'Đã thêm thành công!');   
        // }


}

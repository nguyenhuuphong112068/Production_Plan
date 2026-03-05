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
              
                $stage_code = $request->stage_code??1;

                $stage_code_room =  $stage_code;

                if ($stage_code == 2){ $stage_code_room = 1;}
                
                $production = session('user')['production_code'];
      
                // 🔹 1. Lấy dữ liệu mới nhất cho mỗi stage_plan_id
                $datas = DB::table('stage_plan as sp')
                        ->leftJoin(
                                DB::raw("
                                (
                                SELECT 
                                        t.stage_plan_id,
                                        GROUP_CONCAT(
                                        CONCAT(
                                                '(', t.rownum, ') ',
                                                DATE_FORMAT(t.`start`, '%H:%i %d/%m'),
                                                ' - ',
                                                DATE_FORMAT(t.`end`, '%H:%i %d/%m'),
                                                ' = ',
                                                FORMAT(t.`yield`, 2)
                                        )
                                        ORDER BY t.`start`
                                        SEPARATOR '<br>'
                                        ) as confirmed,

                                         -- ✅ Tổng sản lượng
                                        ROUND(SUM(t.`yield`), 2) as total_confirmed
                                FROM (
                                        SELECT 
                                        y.stage_plan_id,
                                        y.`start`,
                                        y.`end`,
                                        y.`yield`,
                                        @rownum := IF(@current_sp = y.stage_plan_id, @rownum + 1, 1) as rownum,
                                        @current_sp := y.stage_plan_id
                                        FROM yields y
                                        JOIN (SELECT @rownum := 0, @current_sp := 0) vars
                                        WHERE y.`start` IS NOT NULL 
                                        AND y.`end` IS NOT NULL
                                        ORDER BY y.stage_plan_id, y.`start`
                                ) t
                                GROUP BY t.stage_plan_id
                                ) as y
                                "),
                                'sp.id',
                                '=',
                                'y.stage_plan_id'
                        )
                        ->select(
                                'sp.*',
                                'room.name as room_name',
                                'room.code as room_code',
                                'room.stage as stage',
                                DB::raw("COALESCE(plan_master.actual_batch, plan_master.batch) AS batch"),
                                'plan_master.actual_batch',
                                'plan_master.expected_date',
                                'plan_master.is_val',
                                'finished_product_category.intermediate_code',
                                'finished_product_category.finished_product_code',
                                'finished_product_category.batch_qty',
                                'finished_product_category.unit_batch_qty',
                                'product_name.name as product_name',
                                'market.code as market',
                                // ✅ confirmed yield
                                DB::raw("COALESCE(y.confirmed,'') as confirmed"),
                                DB::raw("COALESCE(y.total_confirmed,0) as total_confirmed")
                        )
                        ->leftJoin('room', 'sp.resourceId', '=', 'room.id')
                        ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
                        ->leftJoin('finished_product_category', 'sp.product_caterogy_id', '=', 'finished_product_category.id')
                        ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', '=', 'intermediate_category.intermediate_code')
                        ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
                        ->leftJoin('market', 'finished_product_category.market_id', '=', 'market.id')
                        ->where('sp.stage_code', $stage_code)
                        ->where('sp.active', 1)
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
             
                //dd ($datas);

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

                Log :: info ($request->all());
                //dd ($request->all());
                /* ===============================
                1. FORMAT DATE (GIỮ DẠNG CARBON)
                =============================== */

                $actualStart          = $request->start ? Carbon::parse($request->start) : null;
                $actualStartYield     = $request->start_yield ? Carbon::parse($request->start_yield) : null;
                $actualEnd            = $request->end ? Carbon::parse($request->end) : null;
                $actualStartCleaning  = $request->start_clearning ? Carbon::parse($request->start_clearning) : null;
                $actualEndCleaning    = $request->end_clearning ? Carbon::parse($request->end_clearning) : null;

                $now = now();

                /* ===============================
                2. VALIDATE THỜI GIAN CƠ BẢN
                =============================== */

                if ($actualStart && $actualStart->gt($now))
                        return response()->json(['message' => '❌ Thời gian bắt đầu sản xuất lớn hơn hiện tại'], 422);

                if ($actualEnd && $actualEnd->gt($now))
                        return response()->json(['message' => '❌ Thời gian kết thúc sản xuất lớn hơn hiện tại'], 422);

                if ($actualStart && $actualEnd && $actualEnd->lte($actualStart))
                        return response()->json(['message' => '❌ Thời gian kết thúc phải lớn hơn thời gian bắt đầu'], 422);

                if ($actualStart && $actualStartYield && $actualStartYield->lt($actualStart))
                        return response()->json(['message' => '❌ Thời gian chạy máy phải lớn hơn thời gian bắt đầu sản xuất'], 422);

                if ($actualEnd && $actualStartYield && $actualStartYield->gte($actualEnd))
                        return response()->json(['message' => '❌ Thời gian chạy máy phải nhỏ hơn thời gian kết thúc sản xuất'], 422);

                if ($request->actionType === 'finised') {

                        if (!$actualStart || !$actualEnd || !$actualStartCleaning || !$actualEndCleaning)
                        return response()->json(['message' => '❌ Thời gian Sản Xuất Không Hợp Lệ'], 422);

                        if ($actualStartCleaning->gt($now))
                        return response()->json(['message' => '❌ Thời gian bắt đầu vệ sinh lớn hơn hiện tại'], 422);

                        if ($actualEndCleaning->gt($now))
                        return response()->json(['message' => '❌ Thời gian kết thúc vệ sinh lớn hơn hiện tại'], 422);
                } else {

                        if (!$actualStart || !$actualEnd)
                        return response()->json(['message' => '❌ Thời gian Sản Xuất / Vệ Sinh Không Hợp Lệ'], 422);
                }

                if (!$request->resourceId)
                        return response()->json(['message' => '❌ Chọn Phòng Sản Xuất!'], 422);

                /* ===============================
                3. VALIDATE YIELD RANGE & OVERLAP
                =============================== */
              
                if ($actualStartYield && $actualEnd && $request->actionType == 'semi-finised') {

                        // phải nằm trong khoảng production
                        if ($actualStartYield->lt($actualStart) || $actualEnd->gt($actualEnd))
                        return response()->json(['message' => '❌ Thời gian Yield phải nằm trong khoảng sản xuất'], 422);

                        // không overlap
                        $overlap = DB::table('yields')
                        ->where('stage_plan_id', $request->id)
                        ->where(function ($q) use ($actualStartYield, $actualEnd) {
                                $q->where('start', '<', $actualEnd)
                                ->where('end', '>', $actualStartYield);
                        })
                        ->exists();

                        if ($overlap)
                        
                        return response()->json(['message' => '❌ Khoảng thời gian vừa nhập bị chồng lấp với các lần xác nhận trước đó, vui lòng kiểm tra lại'], 422);
                }

                /* ===============================
                4. TÍNH YIELDS_BATCH_QTY (STAGE 4)
                =============================== */

                $yields_batch_qty = null;

                $stage_code = DB::table('room')
                        ->where('id', $request->resourceId)
                        ->value('stage_code');

                if ((int)$stage_code === 4) {

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
                5. UPDATE + INSERT (TRANSACTION)
                =============================== */

                DB::transaction(function () use (
                        $request,
                        $actualStart,
                        $actualEnd,
                        $actualStartCleaning,
                        $actualEndCleaning,
                        $actualStartYield,
                        $yields_batch_qty,
                        $stage_code
                ) {
                         /* ===============================
                        1. LẤY TỔNG YIELD TRƯỚC ĐÓ
                        =============================== */

                        $previousYield = DB::table('yields')
                                ->where('stage_plan_id', $request->id)
                                ->value('yield');
                        $previousYield = $previousYield ?? 0;
                        $newYield = ($request->yields ?? 0) + $previousYield;
                        
                        $updateData = [
                        'title'            => $request->title,
                        'resourceId'       => $request->resourceId,
                        'actual_start'     => $actualStart,
                        'actual_end'       => $actualEnd,
                        'yields'           => $newYield,
                        'yields_batch_qty' => $yields_batch_qty,
                        'number_of_boxes'  => $request->number_of_boxes ?? 1,
                        'note'             => $request->note ?? 'NA',
                        'finished_by'      => session('user')['fullName'],
                        'finished_date'    => now(),
                        'finished'         => 1
                        ];

                        if ($request->actionType === 'finised') {
                                $updateData['actual_start_clearning'] = $actualStartCleaning;
                                $updateData['actual_end_clearning']   = $actualEndCleaning;
                        }

                        if ((int)$stage_code <= 2) {
                                $updateData['quarantine_room_code'] = 'W14';
                        }

                        DB::table('stage_plan')
                                ->where('id', $request->id)
                                ->update($updateData);


                        // mới
                        if ($request->actionType != 'finised' || !empty($actualStartYield)) {
                                DB::table('yields')->updateOrInsert(
                                        [
                                        'stage_plan_id' => $request->id,
                                        'start' => $actualStart,
                                        'end'   => $actualStartYield
                                        ],
                                        [
                                        'start'        => $actualStartYield,
                                        'end'          => $actualEnd,
                                        'yield'        => $request->yields ?? 0,
                                        'created_by'   => session('user')['fullName'],
                                        'created_date' => now(),
                                        ]
                                );
                        }
                        // như cũ
                        // DB::table('yields')->updateOrInsert(
                        // ['stage_plan_id' => $request->id, 
                        // //'start'=> $actualStartYield
                        // ], // điều kiện kiểm tra tồn tại
                        // [
                        //         'start'        => $actualStartYield,
                        //         'end'          => $actualEnd,
                        //         'yield'        => $request->yields ?? 0,
                        //         'created_by'   => session('user')['fullName'],
                        //         'created_date' => now(),
                        // ]
                        // );
                        

                        if ($request->actual_batch) {

                                $plan_master_id = DB::table('stage_plan')
                                        ->where('id', $request->id)
                                        ->value('plan_master_id');

                                DB::table('plan_master')
                                        ->where('id', $plan_master_id)
                                        ->update([
                                        'actual_batch' => $request->actual_batch,
                                        'weighed'      => 1
                                        ]);
                        }
                });

                return back()->with('success', '✅ Cập nhật công đoạn thành công!');
        }


}

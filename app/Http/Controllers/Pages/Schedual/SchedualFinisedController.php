<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use App\Services\ScheduleRerouteService;
use App\Services\SchedulingLock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

//use Illuminate\Support\Facades\Log;

class SchedualFinisedController extends Controller
{
        public function index(Request $request)
        {

                $stage_code = $request->stage_code ?? 1;

                $production = session('user')['production_code'];

                $now = now()->format('Y-m-d H:i:s');

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
                                        ROUND(SUM(t.`yield`), 2) as total_confirmed,
                                        MAX(t.`end`) as max_yield_end
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
                                DB::raw("COALESCE(y.total_confirmed,0) as total_confirmed"),
                                'y.max_yield_end'
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

                        // 🔹 Lô cần xác nhận hoàn thành (chưa xác nhận SX mà lịch lý thuyết đã kết thúc)
                        //    lên đầu, lô có end gần now nhất trước; các lô còn lại giữ thứ tự theo start
                        ->orderByRaw('CASE WHEN sp.finished = 0 AND sp.`end` < ? THEN 0 ELSE 1 END', [$now])
                        ->orderByRaw('CASE WHEN sp.finished = 0 AND sp.`end` < ? THEN sp.`end` END DESC', [$now])
                        ->orderBy('sp.start')
                        ->get();

                foreach ($datas as $data) {
                        $data->need_confirm = !$data->finished && $data->end && $data->end < $now;
                }

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
                // 🔹 Cân NL Khác (stage 2): xưởng nào có phòng riêng stage 2 thì hiện thêm,
                // xưởng không có thì vẫn dùng phòng cân của stage 1
                $room_stages = DB::table('room')
                        ->when(
                                $stage_code == 2,
                                fn($q) => $q->whereIn('stage_code', [1, 2]),
                                fn($q) => $q->where('stage_code', $stage_code)
                        )
                        ->where('deparment_code', $production)
                        ->where('active', 1)
                        ->orderBy('order_by')
                        ->get();


                //dd ($datas);
                session()->put(['title' => 'XÁC NHẬN HOÀN THÀNH LÔ SẢN XUẤT']);
                return view('pages.Schedual.finised.list', [

                        'datas' => $datas,
                        'stages' => $stages,
                        'stageCode' => $stageCode,
                        'room_stages' => $room_stages
                        //'quarantine_room' => $quarantine_room

                ]);
        }

        /**
         * Tìm lô sản xuất khác bị trùng giờ (overlap) với khoảng [$start, $end] trên cùng
         * một phòng/nguồn lực (resourceId) và cùng công đoạn (stage_code), chỉ dựa vào
         * thời gian thực tế (actual_*).
         *
         * Không kiểm tra ở stage_code 1 (Cân NL), 2 (Cân NL Khác), 3 (Pha Chế), 4 (Trộn Hoàn Tất).
         * Không tính là trùng với các lịch Bảo Trì/Hiệu Chuẩn (stage_code = 8) vì khác stage_code.
         *
         * @return object|null Bản ghi stage_plan bị trùng, hoặc null nếu không trùng / không cần kiểm tra.
         */
        private function overlapConflict($resourceId, $excludeId, ?Carbon $start, ?Carbon $end)
        {
                if (!$resourceId || !$start || !$end) {
                        return null;
                }

                $stage_code = DB::table('room')
                        ->where('id', $resourceId)
                        ->value('stage_code');

                if (in_array((int) $stage_code, [1, 2, 3, 4], true)) {
                        return null;
                }

                return DB::table('stage_plan as sp')
                        ->select(
                                'sp.id',
                                'sp.title',
                                'sp.actual_start',
                                'sp.actual_end',
                                'sp.actual_end_clearning'
                        )
                        ->where('sp.resourceId', $resourceId)
                        ->where('sp.stage_code', $stage_code)
                        ->where('sp.active', 1)
                        ->whereNotNull('sp.actual_start')
                        ->whereNotNull('sp.actual_end')
                        ->when($excludeId, fn($q) => $q->where('sp.id', '!=', $excludeId))
                        ->where('sp.actual_start', '<', $end)
                        ->whereRaw('COALESCE(sp.actual_end_clearning, sp.actual_end) > ?', [$start])
                        ->first();
        }

        /**
         * Endpoint AJAX: kiểm tra trùng giờ trong lúc người dùng đang nhập (cảnh báo, chưa chặn lưu).
         */
        public function checkOverlap(Request $request)
        {
                $start = $request->start ? Carbon::parse($request->start) : null;
                $end   = $request->end ? Carbon::parse($request->end) : null;

                $conflict = $this->overlapConflict($request->resourceId, $request->id, $start, $end);

                return response()->json([
                        'overlap'  => (bool) $conflict,
                        'conflict' => $conflict,
                ]);
        }

        public function store(Request $request)
        {
                // Phân xưởng đang chạy sắp lịch tự động (ở máy khác) thì chưa cho xác nhận, tránh xáo trộn lịch
                $schedulingLock = SchedulingLock::active(session('user.production_code'));
                if ($schedulingLock)
                        return response()->json(['message' => SchedulingLock::message($schedulingLock)], 423);



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

                if ($actualEnd && $actualStartYield && $actualStartYield->gte($actualEnd)) {
                        if ($request->actionType === 'finised' && $actualStartYield->equalTo($actualEnd)) {
                                $actualStartYield = null; // Bỏ qua việc tạo yield, chỉ xác nhận vệ sinh
                        } else {
                                return response()->json(['message' => '❌ Thời gian chạy máy phải nhỏ hơn thời gian kết thúc sản xuất'], 422);
                        }
                }

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

                // Lô chưa có lịch lý thuyết (chưa sắp lịch) thì không được xác nhận hoàn thành, trừ Cân NL / Cân NL Khác (stage 1, 2)
                $plan = DB::table('stage_plan')->where('id', $request->id)->first(['stage_code', 'start', 'resourceId']);
                if (!$plan || (!in_array((int) $plan->stage_code, [1, 2], true) && (!$plan->start || !$plan->resourceId)))
                        return response()->json(['message' => '❌ Không được xác nhận hoàn thành do chưa sắp lịch'], 422);

                /* ===============================
                2.5 VALIDATE TRÙNG GIỜ (OVERLAP) - CHẶN LƯU NẾU TRÙNG
                =============================== */

                $formatRange = function ($conflict) {
                        $end = $conflict->actual_end_clearning ?? $conflict->actual_end;
                        return Carbon::parse($conflict->actual_start)->format('H:i d/m/Y')
                                . ' - ' . Carbon::parse($end)->format('H:i d/m/Y');
                };

                $overlapProd = $this->overlapConflict($request->resourceId, $request->id, $actualStart, $actualEnd);
                if ($overlapProd) {
                        return response()->json([
                                'message' => '❌ Thời gian sản xuất bị trùng giờ với lô "' . $overlapProd->title
                                        . '" (' . $formatRange($overlapProd) . ') trên cùng phòng sản xuất, vui lòng kiểm tra lại!'
                        ], 422);
                }

                if ($request->actionType === 'finised') {
                        $overlapClean = $this->overlapConflict($request->resourceId, $request->id, $actualStartCleaning, $actualEndCleaning);
                        if ($overlapClean) {
                                return response()->json([
                                        'message' => '❌ Thời gian vệ sinh bị trùng giờ với lô "' . $overlapClean->title
                                                . '" (' . $formatRange($overlapClean) . ') trên cùng phòng sản xuất, vui lòng kiểm tra lại!'
                                ], 422);
                        }
                }

                /* ===============================
                3. VALIDATE YIELD RANGE & OVERLAP
                =============================== */

                if ($actualStartYield && $actualEnd) {

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

                $newYield = 0;
                if ($actualStartYield) {
                        $previousYield = DB::table('yields')
                                ->where('stage_plan_id', $request->id)
                                ->sum('yield');

                        $Theoretical_yields = DB::table('stage_plan')
                                ->where('id', $request->id)
                                ->value('Theoretical_yields');

                        $previousYield = $previousYield ?? 0;

                        $newYield = ($request->yields ?? 0) + $previousYield;

                        if ($newYield > $Theoretical_yields * 1.05) {
                                return response()->json(['message' => '❌ Sản Lượng Không Vượt Quá 105% Sản Lượng Lý Thuyết'], 422);
                        }
                }

                /* ===============================
                5. UPDATE + INSERT (TRANSACTION)
                =============================== */
                // Log::info([
                //         'newYield' => $newYield , 
                //         'Theoretical_yields' =>  $Theoretical_yields
                // ]);

                DB::transaction(function () use (
                        $request,
                        $actualStart,
                        $actualEnd,
                        $actualStartCleaning,
                        $actualEndCleaning,
                        $actualStartYield,
                        $yields_batch_qty,
                        $stage_code,
                        $newYield
                ) {
                        /* ===============================
                        1. LẤY TỔNG YIELD TRƯỚC ĐÓ
                        =============================== */



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
                                                'start' => $actualStartYield
                                        ],
                                        [
                                                'end'          => $actualEnd,
                                                'yield'        => $request->yields ?? 0,
                                                'created_by'   => session('user')['fullName'],
                                                'created_date' => now(),
                                        ]
                                );
                        }

                        if ($request->actual_batch) {

                                $plan_master_id = DB::table('stage_plan')
                                        ->where('id', $request->id)
                                        ->value('plan_master_id');

                                DB::table('plan_master')
                                        ->where('main_parkaging_id', $plan_master_id)
                                        ->update([
                                                'actual_batch' => $request->actual_batch,
                                                'weighed'      => 1
                                        ]);
                        }
                });

                /* ===============================
                6. TỊNH TUYẾN LỊCH LÝ THUYẾT THEO GIỜ HOÀN THÀNH THỰC TẾ
                Lỗi ở bước này không được làm hỏng xác nhận hoàn thành đã lưu.
                =============================== */

                $reroute = ['run_code' => null, 'delta_minutes' => 0, 'changes' => [], 'source_cleaning_moved' => false];

                // Tính năng thử nghiệm: chỉ chạy khi người dùng bật công tắc
                // "Xác nhận và điều chỉnh lịch theo thời gian thực" trên trang xác nhận.
                // Chỉ user được phép (ScheduleRerouteService::ALLOWED_USER_IDS) mới kích hoạt được, kể cả khi gửi cờ trực tiếp.
                // ✓✓ (finised): dịch theo giờ vệ sinh thực tế; ✓ (semi-finised): chỉ dịch khi lô đã trễ, dời cả vệ sinh của lô.
                if ($request->boolean('realtime_reroute') && ScheduleRerouteService::canUse()) {
                        try {
                                $service = app(ScheduleRerouteService::class);
                                $reroute = $request->actionType === 'finised'
                                        ? $service->reroute((int) $request->id)
                                        : $service->rerouteAfterProduction((int) $request->id);
                        } catch (\Throwable $e) {
                                Log::error('[Reroute] Tịnh tuyến thất bại cho stage_plan ' . $request->id, [
                                        'error' => $e->getMessage(),
                                        'trace' => $e->getTraceAsString(),
                                ]);
                        }
                }

                if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                                'message'        => '✅ Cập nhật công đoạn thành công!',
                                'reroute_run'    => $reroute['run_code'],
                                'reroute_delta'  => $reroute['delta_minutes'],
                                'reroute_count'  => count($reroute['changes']),
                                'reroute_cleaning_moved' => $reroute['source_cleaning_moved'],
                        ]);
                }

                return back()->with('success', '✅ Cập nhật công đoạn thành công!');
        }
}

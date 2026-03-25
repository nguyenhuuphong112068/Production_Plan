<?php

namespace App\Http\Controllers\Pages\MaintenanceSchedual;

use App\Http\Controllers\Pages\Schedual\SchedualController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MaintenanceSchedualController extends SchedualController
{
    public function index()
    {
        session()->put(['title' => 'LỊCH BẢO TRÌ - HIỆU CHUẨN']);
        return view('app');
    }

    public function view(Request $request)
    {


        $startDate = $request->startDate ?? Carbon::now();
        $endDate = $request->endDate ?? Carbon::now()->addDays(7);
        $viewtype = $request->viewtype ?? "resourceTimelineWeek";
        $this->theory = (int)$request->theory ?? 0;

        try {
            $production = session('user')['production_code'];
            $department = DB::table('user_management')->where('userName', session('user')['userName'])->value('deparment');

            $clearing = $request->clearning ?? true;

            if ($viewtype == "resourceTimelineQuarter") {
                $clearing = false;
            }

            if (user_has_permission(session('user')['userId'], 'loading_plan_waiting', 'boolean')) {
                $plan_waiting = $this->getPlanWaiting($production);
                $bkc_code = DB::table('stage_plan_bkc')->where('deparment_code', session('user')['production_code'])->select('bkc_code')->distinct()->orderByDesc('bkc_code')->get();
                $reason = DB::table('reason')->where('deparment_code', $production)->pluck('name');
                $quota = parent::getQuota($production);
            }


            $stageMap = DB::table('room')->where('deparment_code', $production)->pluck('stage_code', 'stage')->toArray();

            $events = parent::getEvents($production, $startDate, $endDate, $clearing, $this->theory);

            $sumBatchByStage = parent::yield($startDate, $endDate, "stage_code");

            $resources = parent::getResources($production, $startDate, $endDate);





            $type = true;

            $Lines = DB::table('room')
                ->select('stage_code', 'name', 'code')
                ->where('deparment_code', $production)
                ->whereIn('stage_code', [3, 4, 5, 6, 7])
                ->where('active', 1)
                ->orderBy('order_by')
                ->get()
                ->groupBy('stage_code')
                ->map(function ($items) {
                    return $items->map(function ($room) {
                        return [
                            'name'      => $room->code,
                            'name_code' => $room->code . ' - ' . $room->name,
                        ];
                    })->values();
                });

            $allLines = DB::table('room')
                ->select('stage_code', 'name', 'code')
                ->where('deparment_code', $production)
                ->whereIn('stage_code', [3, 4, 5, 6, 7])
                ->where('active', 1)
                ->orderBy('order_by')
                ->get();





            $authorization = session('user')['userGroup'];
            $UesrID =  session('user')['userId'];

            return response()->json([
                //'title' => $title,
                'events' => $events,
                'plan' => $plan_waiting ?? [],
                'quota' => $quota ?? [],
                'stageMap' => $stageMap ?? [],
                'resources' => $resources ?? [],
                'sumBatchByStage' =>  $sumBatchByStage ?? [],
                'reason' => $reason ?? [],
                'type' => $type,
                'authorization' => $authorization,
                'production' => $production,
                'department' => $department,
                'currentPassword' => session('user')['passWord'] ?? '',
                'Lines'       => $Lines ?? [],
                'allLines' => $allLines ?? [],
                'off_days' => DB::table('off_days')->where('off_date', '>=', now())->get()->pluck('off_date') ?? [],
                'bkc_code' => $bkc_code ?? [],
                'UesrID' => $UesrID
            ]);
        } catch (\Throwable $e) {
            // Ghi log chi tiết lỗi
            Log::error('Error in view(): ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function getPlanWaiting($production, $order_by_type = false)
    {
        $plans = DB::table("stage_plan as sp")
            ->whereNull('sp.start')
            ->where('sp.active', 1)
            ->where('sp.finished', 0)
            ->where('sp.deparment_code', $production)
            ->where('sp.stage_code', 8)
            ->leftJoin('quota_maintenance', 'sp.product_caterogy_id', '=', 'quota_maintenance.id')
            ->select(
                'sp.*',
                'quota_maintenance.inst_id as name',
                'quota_maintenance.inst_id as instrument_code',
                'quota_maintenance.is_HVAC',
                'quota_maintenance.exe_time'
            )
            ->get()
            ->groupBy('plan_master_id')
            ->map(function ($group) {
                return $group->first();
            })
            ->values();

        $quotaIds = $plans->pluck('product_caterogy_id')->filter()->unique()->toArray();
        $relatedRooms = collect();
        if (!empty($quotaIds)) {
            $relatedRooms = DB::table('quota_maintenance_rooms as qmr')
                ->join('room', 'qmr.room_id', '=', 'room.id')
                ->whereIn('qmr.quota_maintenance_id', $quotaIds)
                ->select('qmr.quota_maintenance_id', 'room.code as room_code', 'room.name as room_name')
                ->get()
                ->groupBy('quota_maintenance_id');
        }

        $instIds = $plans->pluck('instrument_code')->filter()->unique()->toArray();
        $instruments = collect();

        if (!empty($instIds)) {
            $connections = ['cal1', 'cal2'];
            $suffixes = [1, 2, 3];
            foreach ($connections as $conn) {
                foreach ($suffixes as $suffix) {
                    try {
                        $result = DB::connection($conn)
                            ->table("Inst_Master_{$suffix} as Ins")
                            ->leftJoin("Eqp_mst_{$suffix} as Eqp", 'Eqp.Eqp_ID', '=', 'Ins.Parent_Equip_id')
                            ->whereIn('Ins.Inst_id', $instIds)
                            ->select('Ins.Inst_id', 'Ins.Inst_Name', 'Ins.Parent_Equip_id', 'Eqp.Eqp_name')
                            ->get()
                            ->keyBy('Inst_id');
                        $instruments = $instruments->merge($result);
                    } catch (\Exception $e) {
                        Log::error("Error fetching instrument from {$conn}-{$suffix}: " . $e->getMessage());
                    }
                }
            }
        }

        return $plans->map(function ($item) use ($instruments, $relatedRooms) {
            $inst = $instruments[$item->instrument_code] ?? null;
            $item->Inst_Name = $inst->Inst_Name ?? $item->instrument_code;
            $item->Parent_Equip_id = $inst->Parent_Equip_id ?? '';
            $item->Eqp_name = $inst->Eqp_name ?? '';

            // Lấy danh sách phòng
            $item->related_rooms = $relatedRooms[$item->product_caterogy_id] ?? [];

            // Tính tổng thời gian thực hiện
            $item->PM = $item->exe_time ?? '00:00';

            return $item;
        });
    }

    protected function getQuota($production)
    {
        $result = DB::table('quota_maintenance as q ')
            ->leftJoin('room', 'q.room_id', '=', 'room.id')
            //->where('q.active', 1)
            ->where('q.deparment_code', $production)
            ->get()
            ->map(function ($item) {
                $item->PM = $item->exe_time ?? '00:00';
                return $item;
            });

        Log::info($result);
        return $result;
    }

    protected function toMinutes($time)
    {
        [$hours, $minutes] = explode(':', $time);
        return ((int)$hours) * 60 + (int)$minutes;
    }

    public function store(Request $request)
    {
        log::info($request->all());

        $type = $request->type;
        $targetRoomId = $request->room_id;

        DB::beginTransaction();
        try {

            $products = collect($request->products)->sortBy('id')->values();

            $start = Carbon::parse($request->start);

            // 1. Kiểm tra phòng hợp lệ cho HC và BT
            if ($type != 'TI') {
                $quotaIds = $products->pluck('product_caterogy_id')->filter()->unique()->toArray();

                // Lấy tất cả mapping phòng-định mức
                $validRoomsByQuota = DB::table('quota_maintenance_rooms')
                    ->whereIn('quota_maintenance_id', $quotaIds)
                    ->get()
                    ->groupBy('quota_maintenance_id')
                    ->map(function ($group) {
                        return $group->pluck('room_id')->toArray();
                    });

                foreach ($products as $product) {
                    $allowedRooms = $validRoomsByQuota[$product['product_caterogy_id']] ?? [];
                    if (!in_array($targetRoomId, $allowedRooms)) {
                        $instCode = $product['instrument_code'] ?? $product['name'] ?? 'Thiết bị';

                        // Lấy tên các phòng hợp lệ để thông báo cho người dùng
                        $validRoomNames = DB::table('room')->whereIn('id', $allowedRooms)->pluck('code')->toArray();
                        $roomList = implode(", ", $validRoomNames);

                        return response()->json([
                            'success' => false,
                            'message' => "Thiết bị [<b>{$instCode}</b>] không được định mức thực hiện BT-HC tại phòng này.<br/>Các phòng hợp lệ: <b>{$roomList}</b>"
                        ], 422);
                    }
                }
            }

            if ($type == 'TI') {
                $rooms = DB::table('room')->select('id', 'code')->get()->keyBy('code');

                foreach ($products as $product) {
                    $planMasterId = $product['plan_master_id'];
                    $productStart = $start->copy(); // Đảm bảo tất cả task của cùng 1 plan_master cùng start
                    $productEnd = $productStart->copy()->addMinutes($this->toMinutes($product['PM']));

                    DB::table('stage_plan')
                        ->where('plan_master_id', $planMasterId)
                        ->get()
                        ->each(function ($sp) use ($productStart, $productEnd, $rooms, $product) {
                            $roomId = isset($rooms[$sp->required_room_code]) ? $rooms[$sp->required_room_code]->id : null;

                            DB::table('stage_plan')
                                ->where('id', $sp->id)
                                ->update([
                                    'start'           => $productStart,
                                    'end'             => $productEnd,
                                    'resourceId'      => $roomId,
                                    'title'           => $product['Parent_Equip_id'] . ' - ' . $product['Eqp_name'] . ' - ' . $product['Inst_Name'] . ' - ' . $product['instrument_code'],
                                    'schedualed_by'   => session('user')['fullName'],
                                    'schedualed_at'   => now(),
                                ]);
                        });
                }
            } else {

                // 🔥 KIỂM TRA NGAY TỪ ĐẦU NẾU current_start NẰM TRONG OFFDATE
                foreach ($products as $product) {

                    $end = $start->addMinutes($this->toMinutes($product['PM']));

                    DB::table('stage_plan')
                        ->where('id', $product['id'])
                        ->update([
                            'start'           => $start,
                            'end'             => $end,
                            // 'start_clearning' => $end_man,
                            // 'end_clearning'   => $end_clearning,
                            'resourceId'      => $request->room_id,
                            'title'           => $product['Parent_Equip_id'] . ' - ' . $product['Eqp_name'] . ' - ' . $product['Inst_Name'] . ' - ' . $product['instrument_code'],
                            'schedualed_by'   => session('user')['fullName'],
                            'schedualed_at'   => now(),

                        ]);

                    $submit = DB::table('stage_plan')->where('id', $product['id'])->value('submit');

                    if ($submit == 1) {
                        $last_version = DB::table('stage_plan_history')
                            ->where('stage_plan_id', $product['id'])
                            ->max('version') ?? 0;

                        DB::table('stage_plan_history')->insert([
                            'stage_plan_id'  => $product['id'],
                            'version'        => $last_version + 1,
                            'start'           => $start,
                            'end'             => $end,
                            'resourceId'     => $request->room_id,
                            'title'           => $product['Parent_Equip_id'] . ' - ' . $product['Eqp_name'] . ' - ' . $product['Inst_Name'] . ' - ' . $product['instrument_code'],
                            'schedualed_by'   => session('user')['fullName'],
                            'schedualed_at'  => now(),
                            'deparment_code' => session('user')['production_code'],
                            'type_of_change' => $request->reason ?? "Lập Lịch Thủ Công",
                        ]);
                    }

                    $start = $end;
                }
            }





            DB::commit();
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }

        /*
                |--------------------------------------------------------------------------
                | TRẢ KẾT QUẢ
                |--------------------------------------------------------------------------
                */
        $production = session('user')['production_code'];
        $events = parent::getEvents($production, $request->startDate, $request->endDate, true, $this->theory);
        $sumBatchByStage = parent::yield($request->startDate, $request->endDate, "stage_code");
        $plan_waiting = $this->getPlanWaiting($production);

        return response()->json([
            'events' => $events,
            'plan' => $plan_waiting,
            'sumBatchByStage' => $sumBatchByStage,
        ]);
    }


    public function deActive(Request $request)
    {

        $items = collect($request->input('ids'));
        try {

            foreach ($items as $item) {
                $rowId = explode('-', $item['id'])[0];   // lấy id trước dấu -
                $stageCode = $item['stage_code'];

                if ($stageCode <= 2 || $stageCode >= 8) {
                    // chỉ cóa cân k xóa các công đoạn khác


                    DB::table('stage_plan')
                        ->where('id', $rowId)
                        ->where('finished', 0)
                        ->where('stage_code', '=', $stageCode)
                        ->update([
                            'start'            => null,
                            'end'              => null,
                            'start_clearning'  => null,
                            'end_clearning'    => null,
                            'resourceId'       => null,
                            'title'            => null,
                            'title_clearning'  => null,
                            'accept_quarantine' => 0,
                            'schedualed'       => 0,
                            'AHU_group' => 0,
                            'schedualed_by'    => session('user')['fullName'],
                            'schedualed_at'    => now(),
                        ]);
                } else {

                    $plan = DB::table('stage_plan')->where('id', $rowId)->first();

                    DB::table('stage_plan')
                        ->where('finished', 0)
                        ->where('plan_master_id', $plan->plan_master_id)->where('stage_code', '>=', $stageCode)
                        ->update([
                            'start'            => null,
                            'end'              => null,
                            'start_clearning'  => null,
                            'end_clearning'    => null,
                            'resourceId'       => null,
                            'title'            => null,
                            'title_clearning'  => null,
                            'accept_quarantine' => 0,
                            'schedualed'       => 0,
                            'schedualed_by'    => session('user')['fullName'],
                            'schedualed_at'    => now(),
                        ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }



        $production = session('user')['production_code'];
        $events = $this->getEvents($production, $request->startDate, $request->endDate, true, $this->theory);
        $plan_waiting = parent::getPlanWaiting($production);
        $resources = $this->getResources($production, $request->startDate, $request->endDate);
        $sumBatchByStage = $this->yield($request->startDate, $request->endDate, "stage_code");

        return response()->json([
            'events' => $events,
            'plan' => $plan_waiting,
            'resources' => $resources,
            'sumBatchByStage' => $sumBatchByStage,

        ]);
    }
}

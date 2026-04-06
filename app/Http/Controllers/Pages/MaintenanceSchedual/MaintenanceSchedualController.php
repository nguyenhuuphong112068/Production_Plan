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
            $showProduction = $request->production ?? true;

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

            $eventsRaw = parent::getEvents($production, $startDate, $endDate, $clearing, $this->theory);

            // 🔹 Ẩn lịch sản xuất nếu người dùng yêu cầu
            if (!$showProduction) {
                $eventsRaw = $eventsRaw->filter(fn($e) => (int)$e->stage_code >= 8);
            }

            $events = $this->groupMaintenanceEvents($eventsRaw);

            $sumBatchByStage = parent::yield($startDate, $endDate, "stage_code");

            $resources = parent::getResources($production, $startDate, $endDate, true);

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
            $groupName =  session('user')['group_name'];

            return response()->json([

                'events' => $events,
                'plan' => $plan_waiting ?? [],
                'quota' => $quota ?? [],
                'stageMap' => $stageMap ?? [],
                'resources' => $resources ?? [],
                'sumBatchByStage' =>  $sumBatchByStage ?? [],
                'reason' => $reason ?? [],
                'type' => $type,

                'Lines'       => $Lines ?? [],
                'allLines' => $allLines ?? [],
                'off_days' => DB::table('off_days')->where('off_date', '>=', now())->get()->pluck('off_date') ?? [],
                'bkc_code' => $bkc_code ?? [],

                'UesrID' => $UesrID,
                'authorization' => $authorization,
                'groupName' => $groupName,
                'production' => $production,
                'department' => $department,
                'currentPassword' => session('user')['passWord'] ?? '',

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
            ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
            ->select(
                'sp.*',
                'quota_maintenance.inst_id as name',
                'quota_maintenance.inst_id as instrument_code',
                'quota_maintenance.is_HVAC',
                'quota_maintenance.exe_time',
                'plan_master.expected_date'
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
                ->select('qmr.quota_maintenance_id', 'room.code as room_code', 'room.name as room_name', 'room.production_group')
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

        $type = $request->type;
        $targetRoomId = $request->room_id;
        Log::info($request->all());


        DB::beginTransaction();
        try {

            $products = collect($request->products)->sortBy('id')->values();

            $start = Carbon::parse($request->start);

            if ($request->has('slotDuration')  &&   $request->slotDuration  ==  1) {
                $start->setTime(7,  15,  0);
            }

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

                // 1. Tính tổng thời gian PM của tất cả thiết bị được chọn
                $totalMinutes = 0;
                foreach ($products as $product) {
                    $totalMinutes += $this->toMinutes($product['PM']);
                }
                $overallEnd = $start->copy()->addMinutes($totalMinutes);

                // 2. Cập nhật tất cả bản ghi về cùng một khung thời gian
                foreach ($products as $product) {
                    DB::table('stage_plan')
                        ->where('id', $product['id'])
                        ->update([
                            'start'           => $start,
                            'end'             => $overallEnd,
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
                            'end'             => $overallEnd,
                            'resourceId'     => $request->room_id,
                            'title'           => $product['Parent_Equip_id'] . ' - ' . $product['Eqp_name'] . ' - ' . $product['Inst_Name'] . ' - ' . $product['instrument_code'],
                            'schedualed_by'   => session('user')['fullName'],
                            'schedualed_at'  => now(),
                            'deparment_code' => session('user')['production_code'],
                            'type_of_change' => $request->reason ?? "Lập Lịch Thủ Công",
                        ]);
                    }
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
        $eventsRaw = parent::getEvents($production, $request->startDate, $request->endDate, true, $this->theory);
        $events = $this->groupMaintenanceEvents($eventsRaw);
        $sumBatchByStage = parent::yield($request->startDate, $request->endDate, "stage_code");
        $plan_waiting = $this->getPlanWaiting($production);

        return response()->json([
            'events' => $events,
            'plan' => $plan_waiting,
            'sumBatchByStage' => $sumBatchByStage,
        ]);
    }

    public function update(Request $request)
    {


        $changes = $request->input('changes', []);
        $this->theory = (int)$request->theory ?? 0;

        DB::beginTransaction();
        try {
            $offDays = DB::table('off_days')
                ->whereDate('off_date', '>=', now())
                ->pluck('off_date')
                ->toArray();

            foreach ($changes as $change) {
                $idParts = explode('-', $change['id']);
                $realIdRaw = $idParts[0] ?? null;

                if (!$realIdRaw) continue;

                $ids = explode(',', $realIdRaw);

                // Tính toán ngày nhận (tham khảo SchedualController stage 1/2)
                $newStart = \Carbon\Carbon::parse($change['start']);
                $receiveDate = $newStart->copy()->subDay();
                while (in_array($receiveDate->toDateString(), $offDays)) {
                    $receiveDate->subDay();
                }

                // KIỂM TRA TI: Nếu là TI, ta tác động lên toàn bộ stage_plan có cùng code
                $sourceStagePlans = DB::table('stage_plan')->whereIn('id', $ids)->get();
                $codes = $sourceStagePlans->pluck('code')->filter()->unique()->toArray();
                $allAffectedIds = $ids;

                $isTI = DB::table('plan_master')
                    ->join('quota_maintenance', 'plan_master.product_caterogy_id', '=', 'quota_maintenance.id')
                    ->whereIn('plan_master.id', $sourceStagePlans->pluck('plan_master_id')->unique()->toArray())
                    ->where('quota_maintenance.block', 'like', 'TI-%')
                    ->exists();

                if ($isTI && !empty($codes)) {
                    // 1. Cập nhật các bản ghi gốc (cả thời gian và resourceId)
                    DB::table('stage_plan')
                        ->whereIn('id', $ids)
                        ->update([
                            'start'           => $change['start'],
                            'end'             => $change['end'],
                            'resourceId'      => $change['resourceId'],
                            'receive_packaging_date' => $receiveDate,
                            'receive_second_packaging_date' => $receiveDate,
                            'schedualed_by'   => session('user')['fullName'],
                            'schedualed_at'   => now(),
                        ]);

                    // 2. Tìm các bản ghi liên đới cùng code nhưng khác ID gốc
                    $otherIds = DB::table('stage_plan')
                        ->whereIn('code', $codes)
                        ->whereNotIn('id', $ids)
                        ->pluck('id')->toArray();

                    if (!empty($otherIds)) {
                        DB::table('stage_plan')
                            ->whereIn('id', $otherIds)
                            ->update([
                                'start'           => $change['start'],
                                'end'             => $change['end'],
                                // KHÔNG thay đổi resourceId cho các bản ghi liên đới
                                'receive_packaging_date' => $receiveDate,
                                'receive_second_packaging_date' => $receiveDate,
                                'schedualed_by'   => session('user')['fullName'],
                                'schedualed_at'   => now(),
                            ]);
                    }
                    $allAffectedIds = array_unique(array_merge($ids, $otherIds));
                } else {
                    // Logic cũ cho Non-TI: Cập nhật tất cả bản ghi được xác định (bao gồm resourceId)
                    DB::table('stage_plan')
                        ->whereIn('id', $allAffectedIds)
                        ->update([
                            'start'           => $change['start'],
                            'end'             => $change['end'],
                            'resourceId'      => $change['resourceId'],
                            'receive_packaging_date' => $receiveDate,
                            'receive_second_packaging_date' => $receiveDate,
                            'schedualed_by'   => session('user')['fullName'],
                            'schedualed_at'   => now(),
                        ]);
                }

                // ĐỒNG BỘ NGƯỢC: Cập nhật expected_date trong plan_master
                $newDate = $newStart->format('Y-m-d');
                $planMasterIds = DB::table('stage_plan')
                    ->whereIn('id', $allAffectedIds)
                    ->whereNotNull('plan_master_id')
                    ->pluck('plan_master_id')
                    ->unique()
                    ->toArray();

                if (!empty($planMasterIds)) {
                    DB::table('plan_master')->whereIn('id', $planMasterIds)->update([
                        'expected_date' => $newDate,
                        'updated_at' => now()
                    ]);

                    // Ghi lịch sử cho plan_master
                    foreach ($planMasterIds as $pmId) {
                        if (!$pmId) continue;
                        $pm = DB::table('plan_master')->where('id', $pmId)->first();

                        if ($pm) {
                            $lastVersion = DB::table('plan_master_history')->where('plan_master_id', $pmId)->max('version') ?? 0;

                            DB::table('plan_master_history')->insert([
                                'plan_master_id' => $pmId,
                                'plan_list_id' => $pm->plan_list_id,
                                'product_caterogy_id' => $pm->product_caterogy_id,
                                'version' => $lastVersion + 1,
                                'batch' => $pm->batch,
                                'expected_date' => $newDate,
                                'note' => $pm->note,
                                'reason' => $request->reason['reason'] ?? "Cập nhật từ lịch bảo trì",
                                'deparment_code' => session('user')['production_code'],
                                'prepared_by' => session('user')['fullName'],
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }
                    }
                }

                // Ghi lịch sử cho từng thiết bị nếu đã submit (stage_plan_history)
                foreach ($allAffectedIds as $id) {
                    $row = DB::table('stage_plan')->where('id', $id)->first();
                    if ($row && $row->submit == 1) {
                        $last_version = DB::table('stage_plan_history')
                            ->where('stage_plan_id', $id)
                            ->max('version') ?? 0;

                        DB::table('stage_plan_history')->insert([
                            'stage_plan_id'  => $id,
                            'version'        => $last_version + 1,
                            'start'           => $change['start'],
                            'end'             => $change['end'],
                            'resourceId'     => $change['resourceId'],
                            'title'           => $row->title,
                            'schedualed_by'   => session('user')['fullName'],
                            'schedualed_at'  => now(),
                            'deparment_code' => session('user')['production_code'],
                            'type_of_change' => $request->reason['reason'] ?? "Cập Nhật Lịch Bảo Trì",
                        ]);
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi cập nhật lịch bảo trì:', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }

        $production = session('user')['production_code'];
        $eventsRaw = parent::getEvents($production, $request->startDate, $request->endDate, true, $this->theory);
        $events = $this->groupMaintenanceEvents($eventsRaw);
        $plan_waiting = $this->getPlanWaiting($production);
        $resources = parent::getResources($production, $request->startDate, $request->endDate);

        $sumBatchByStage = $this->yield($request->startDate, $request->endDate, "stage_code");

        return response()->json([
            'events' => $events,
            'plan' => $plan_waiting,
            'resources' => $resources,
            'sumBatchByStage' => $sumBatchByStage,
        ]);
    }


    public function deActive(Request $request)
    {

        $items = collect($request->input('ids'));
        try {

            foreach ($items as $item) {
                $rowIdStr = explode('-', $item['id'])[0];   // lấy id trước dấu -
                $stageCode = $item['stage_code'];
                $ids = explode(',', $rowIdStr);

                // KIỂM TRA TI: Nếu là TI, tác động lên toàn bộ stage_plan có cùng code
                $sourceStagePlans = DB::table('stage_plan')->whereIn('id', $ids)->get();
                $codes = $sourceStagePlans->pluck('code')->filter()->unique()->toArray();
                $allAffectedIds = $ids;

                $isTI = DB::table('plan_master')
                    ->join('quota_maintenance', 'plan_master.product_caterogy_id', '=', 'quota_maintenance.id')
                    ->whereIn('plan_master.id', $sourceStagePlans->pluck('plan_master_id')->unique()->toArray())
                    ->where('quota_maintenance.block', 'like', 'TI-%')
                    ->exists();

                if ($isTI && !empty($codes)) {
                    $allAffectedIds = DB::table('stage_plan')->whereIn('code', $codes)->pluck('id')->toArray();
                }

                if ($stageCode <= 2 || $stageCode >= 8) {
                    DB::table('stage_plan')
                        ->whereIn('id', $allAffectedIds)
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
                    $plan = $sourceStagePlans->first();
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
        $eventsRaw = parent::getEvents($production, $request->startDate, $request->endDate, true, $this->theory);
        $events = $this->groupMaintenanceEvents($eventsRaw);
        $plan_waiting = $this->getPlanWaiting($production);
        $resources = parent::getResources($production, $request->startDate, $request->endDate);
        $sumBatchByStage = $this->yield($request->startDate, $request->endDate, "stage_code");

        return response()->json([
            'events' => $events,
            'plan' => $plan_waiting,
            'resources' => $resources,
            'sumBatchByStage' => $sumBatchByStage,

        ]);
    }

    private function groupMaintenanceEvents($events)
    {
        if ($events instanceof \Illuminate\Support\Collection) {
            $events = $events;
        } else {
            $events = collect($events);
        }

        $maintenanceEvents = $events->where('stage_code', '=', 8);
        $productionEvents = $events->where('stage_code', '<', 8);

        $groupedMaintenance = $maintenanceEvents->groupBy(function ($event) {
            $e = (object)$event;
            // Nhóm theo thời gian và phòng
            return $e->start . '_' . $e->end . '_' . $e->resourceId;
        })->map(function ($group) {
            $first = (object)$group->first();
            $first = clone $first; // Tránh làm thay đổi object gốc nếu cần

            if ($group->count() > 1) {
                // Gom tất cả ID lại (chỉ lấy phần số thực tế trước dấu gạch nối)
                $allIds = $group->pluck('id')->map(function ($id) {
                    return explode('-', $id)[0];
                })->toArray();

                // Nối lại bằng dấu phẩy và thêm hậu tố chung để hàm update bóc tách chính xác
                $first->id = implode(',', $allIds) . '-maintenance';

                // Gom tiêu đề các thiết bị
                $allTitles = $group->pluck('title')->unique()->map(function ($t) {
                    $parts = explode(' - ', $t);
                    return count($parts) > 1 ? end($parts) : $t;
                })->toArray();
                $first->title = "BT Thiết Bị: " . implode(" | ", $allTitles);
            }
            return $first;
        })->values();

        return $productionEvents->concat($groupedMaintenance)->values();
    }

    public function syncExternal(Request $request)
    {
        $production = session('user')['production_code'];
        $connections = ['cal1', 'cal2'];
        $suffixes = [1, 2, 3];
        $count = 0;

        // 1. Tìm các stage_plan bảo trì chưa xong (finished = 0)
        $pendingPlans = DB::table('stage_plan as sp')
            ->join('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
            ->where('sp.stage_code', 8)
            ->where('sp.finished', 0)
            ->where('sp.deparment_code', $production)
            ->whereNotNull('pm.batch')
            ->select('sp.id as sp_id', 'pm.batch as sch_ids')
            ->get();

        if ($pendingPlans->isEmpty()) {
            return response()->json(['success' => true, 'message' => 'Không có lịch bảo trì nào cần đồng bộ.', 'count' => 0]);
        }

        // Tạo map SCH_ID -> danh sách stage_plan id liên quan
        $schIdMap = [];
        foreach ($pendingPlans as $p) {
            $ids = explode(',', $p->sch_ids);
            foreach ($ids as $id) {
                $schIdMap[trim($id)][] = $p->sp_id;
            }
        }

        $allSchIds = array_keys($schIdMap);

        DB::beginTransaction();
        try {
            foreach ($connections as $conn) {
                foreach ($suffixes as $suffix) {
                    try {
                        $remoteResults = DB::connection($conn)
                            ->table("Schedule_Master_{$suffix}")
                            ->whereIn('SCH_ID', $allSchIds)
                            ->where('sch_ap_sts', 1)
                            ->get();

                        foreach ($remoteResults as $res) {
                            $schId = (string)$res->SCH_ID;
                            if (isset($schIdMap[$schId])) {
                                foreach ($schIdMap[$schId] as $spId) {
                                    // Map Sch_Result_Status to yields (Pass=1, Fail=0, Skip=2)
                                    $yield = 1;
                                    $statusRaw = trim($res->Sch_Result_Status ?? 'Pass');
                                    if ($statusRaw == 'Fail') $yield = 0;
                                    elseif ($statusRaw == 'Skip') $yield = 2;

                                    // Cập nhật stage_plan thực tế
                                    DB::table('stage_plan')->where('id', $spId)->where('finished', 0)->update([
                                        'actual_start' => $res->Sch_caldone_to,
                                        'actual_end'   => $res->Sch_caldone_to,
                                        'finished_by'  => $res->Sch_cal_Done_by ?? null,
                                        'finished'     => 1,
                                        'yields'       => $yield,
                                        'updated_at'   => now()
                                    ]);
                                    $count++;
                                }
                                unset($schIdMap[$schId]);
                            }
                        }
                    } catch (\Exception $e) {
                        // Skip if table or connection not available
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi sync bảo trì:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Lỗi đồng bộ: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => "Đã đồng bộ thành công {$count} lệnh bảo trì.",
            'count' => $count
        ]);
    }
}

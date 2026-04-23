<?php

namespace App\Http\Controllers\Pages\MaintenanceSchedual;

use App\Http\Controllers\Pages\Schedual\SchedualController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $viewtype = $request->viewtype ?? 'resourceTimelineWeek';
        $this->theory = (int) $request->theory ?? 0;

        try {
            $production = session('user')['production_code'];
            $department = DB::table('user_management')->where('userName', session('user')['userName'])->value('deparment');

            $clearing = $request->clearning ?? true;
            $showProduction = $request->production ?? true;

            if ($viewtype == 'resourceTimelineQuarter') {
                $clearing = false;
            }

            if (user_has_permission(session('user')['userId'], 'plan_maintenance_scheduler', 'boolean')) {
                $plan_waiting = $this->getPlanWaiting($production);
                $bkc_code = DB::table('stage_plan_bkc')->where('deparment_code', session('user')['production_code'])->select('bkc_code')->distinct()->orderByDesc('bkc_code')->get();
                $reason = DB::table('reason')->where('deparment_code', $production)->pluck('name');
                $quota = [];
            }



            $stageMap = DB::table('room')->where('deparment_code', $production)->pluck('stage_code', 'stage')->toArray();

            $this->theory = (int) $request->theory ?? 0;
            $eventsRaw = parent::getEvents($production, $startDate, $endDate, $clearing, $this->theory);

            // 🔹 Ẩn lịch sản xuất nếu người dùng yêu cầu
            if (! $showProduction) {
                $eventsRaw = $eventsRaw->filter(fn($e) => (int) $e->stage_code >= 8)->values();
            }

            $events = $eventsRaw;

            $sumBatchByStage = parent::yield($startDate, $endDate, 'stage_code');

            $resources = parent::getResources($production, $startDate, $endDate, true);

            // 🔹 Thêm Resource Virtual "Toàn phân xưởng (PX)" vào đầu danh sách
            $pxResource = (object)[
                'id' => 0, // Dùng ID 0 để khớp với room_id 0 (PX) đã chọn ở dataTable
                'code' => 'PX',
                'title' => '--- TOÀN PHÂN XƯỞNG (PX) ---',
                'stage_name' => 'Bảo trì chung',
                'stage_code' => 8,
                'production_group' => '',
                'order_by' => -1, // Cho lên đầu
                'busy_hours' => 0,
                'free_hours' => 0,
                'total_hours' => 0,
                'yield' => 0,
                'unit' => ''
            ];

            $resources = collect([$pxResource])->merge($resources);

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
                            'name' => $room->code,
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

            //session('user')['userGroup'];

            $UesrID = session('user')['userId'];
            $groupName = session('user')['group_name'];
            $authorization = session('user')['userGroup'];
            $authorization_scheduler = user_has_permission($UesrID, 'plan_maintenance_scheduler', 'boolean');
            $authorization_accept = user_has_permission($UesrID, 'plan_maintenance_accept', 'boolean');


            return response()->json([

                'events' => $events,
                'plan' => $plan_waiting ?? [],
                'quota' => $quota ?? [],
                'stageMap' => $stageMap ?? [],
                'resources' => $resources ?? [],
                'sumBatchByStage' => $sumBatchByStage ?? [],
                'reason' => $reason ?? [],
                'type' => $type,

                'Lines' => $Lines ?? [],
                'allLines' => $allLines ?? [],
                'off_days' => DB::table('off_days')->where('off_date', '>=', now())->get()->pluck('off_date') ?? [],
                'bkc_code' => $bkc_code ?? [],

                'UesrID' => $UesrID,
                'authorization' => $authorization,
                'groupName' => $groupName,
                'production' => $production,
                'department' => $department,
                'currentPassword' => session('user')['passWord'] ?? '',
                'authorization_accept' => $authorization_accept,
                'authorization_scheduler' => $authorization_scheduler,

            ]);
        } catch (\Throwable $e) {
            Log::error('Error in view(): ' . $e->getMessage());

            return response()->json(['error' => true, 'message' => $e->getMessage()], 500);
        }
    }

    public function autoSchedual(Request $request)
    {
        $production = session('user')['production_code'];
        $startDateStr = $request->startDate ?? Carbon::now()->addDay()->format('Y-m-d 07:15:00');
        $startDate = Carbon::parse($startDateStr);

        // 1. Lấy dữ liệu ngày nghỉ và kế hoạch chờ
        $offDays = DB::table('off_days')->pluck('off_date')->toArray();
        $plans = $this->getPlanWaiting($production);

        $typeFilter = $request->type;
        if ($typeFilter) {
            $plans = $plans->filter(function ($plan) use ($typeFilter) {
                $code = (string)($plan->code ?? '');
                if ($typeFilter === 'HC' && str_ends_with($code, '_HC')) return true;
                if ($typeFilter === 'TB' && (str_ends_with($code, '_TB') || str_ends_with($code, '_8'))) return true;
                if ($typeFilter === 'TI' && str_ends_with($code, '_TI')) return true;
                return false;
            });
        }

        if ($plans->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Không có tác vụ nào đang chờ sắp lịch.']);
        }

        // 2. Theo dõi thời gian rảnh của phòng và số lượng việc bảo trì theo ngày (phân bổ nhân sự)
        $existingEvents = DB::table('stage_plan')
            ->where('deparment_code', $production)
            ->where('end', '>=', now()->toDateString())
            ->where('active', 1)
            ->whereNotNull('resourceId')
            ->get();

        $roomFreeTimes = [];
        $dailyOccupancy = []; // count tasks per day to distribute load

        foreach ($existingEvents as $ev) {
            $eTime = Carbon::parse($ev->end);
            if ($ev->end_clearning) {
                $cTime = Carbon::parse($ev->end_clearning);
                if ($cTime->gt($eTime)) $eTime = $cTime;
            }
            if (!isset($roomFreeTimes[$ev->resourceId]) || $eTime->gt($roomFreeTimes[$ev->resourceId])) {
                $roomFreeTimes[$ev->resourceId] = $eTime;
            }

            // Chỉ đếm các task bảo trì (stage_code 8) để tính tải trọng nhân sự bảo trì
            if ($ev->stage_code == 8) {
                $dateKey = Carbon::parse($ev->start)->toDateString();
                $dailyOccupancy[$dateKey] = ($dailyOccupancy[$dateKey] ?? 0) + 1;
            }
        }

        // 3. Nhóm theo resourceId
        $roomGroups = [];
        foreach ($plans as $plan) {
            $rid = $plan->resourceId;
            if (!$rid) continue;
            $roomGroups[$rid]['tasks'][] = $plan;
            $dueDate = $plan->expected_date ? Carbon::parse($plan->expected_date) : Carbon::now()->addYears(5);
            if (!isset($roomGroups[$rid]['min_due']) || $dueDate->lt($roomGroups[$rid]['min_due'])) {
                $roomGroups[$rid]['min_due'] = $dueDate;
            }
        }

        // Ưu tiên phòng có ngày tới hạn sớm nhất
        uasort($roomGroups, function ($a, $b) {
            return $a['min_due']->timestamp - $b['min_due']->timestamp;
        });

        $scheduledCount = 0;
        DB::beginTransaction();
        try {
            foreach ($roomGroups as $roomId => $group) {
                $subTasks = collect($group['tasks']);
                $totalDuration = 0;
                foreach ($subTasks as $task) {
                    $totalDuration += $this->toMinutes($task->exe_time ?? '01:00');
                }

                // --- LOGIC TÌM NGÀY BẮT ĐẦU TỐI ƯU ---
                $idealStart = $group['min_due']->copy()->subDays(7)->startOfDay();
                if ($idealStart->lt($startDate)) $idealStart = $startDate->copy()->startOfDay();
                $deadline = $group['min_due'];

                $possibleWorkingDays = [];
                $possibleOffDays = [];

                // Tìm trong phạm vi 15 ngày hoặc cho đến ngày hạn (tùy cái nào xa hơn)
                $searchLimit = max(15, (int)$idealStart->diffInDays($deadline) + 7);

                for ($i = 0; $i < $searchLimit; $i++) {
                    $checkDate = $idealStart->copy()->addDays($i);
                    $dateStr = $checkDate->toDateString();

                    // Kiểm tra room rảnh
                    $roomFree = $roomFreeTimes[$roomId] ?? $startDate;
                    $actualStartInDay = $checkDate->copy()->setHour(7)->setMinute(15)->setSecond(0);
                    if ($roomFree->gt($actualStartInDay)) $actualStartInDay = $roomFree->copy();

                    // Nếu ngày này phòng rảnh quá muộn (sang ngày hôm sau) thì bỏ qua
                    if ($actualStartInDay->toDateString() !== $dateStr) continue;

                    $tempEnd = $actualStartInDay->copy()->addMinutes($totalDuration);
                    $isLate = $tempEnd->gt($deadline);

                    $dayData = [
                        'start' => $actualStartInDay,
                        'load' => $dailyOccupancy[$dateStr] ?? 0,
                        'isLate' => $isLate
                    ];

                    if (in_array($dateStr, $offDays)) {
                        $possibleOffDays[] = $dayData;
                    } else {
                        $possibleWorkingDays[] = $dayData;
                    }
                }

                // Chọn ngày theo thứ tự ưu tiên
                $bestDateData = null;

                // Ưu tiên 1: Ngày làm việc & Không trễ hạn
                $bestDateData = collect($possibleWorkingDays)->where('isLate', false)->sortBy('load')->first();

                // Ưu tiên 2: Ngày nghỉ & Không trễ hạn (Cứu hạn)
                if (!$bestDateData) {
                    $bestDateData = collect($possibleOffDays)->where('isLate', false)->sortBy('load')->first();
                }

                // Ưu tiên 3: Nếu buộc phải trễ, chọn ngày làm việc sớm nhất/ít tải nhất
                if (!$bestDateData) {
                    $bestDateData = collect($possibleWorkingDays)->sortBy('load')->first();
                }

                // Ưu tiên 4: Bất kỳ ngày nào còn lại
                if (!$bestDateData) {
                    $bestDateData = collect($possibleOffDays)->sortBy('load')->first();
                }

                $taskStart = $bestDateData['start'] ?? ($roomFreeTimes[$roomId] ?? $startDate);
                $taskEnd = $taskStart->copy()->addMinutes($totalDuration);

                // Cập nhật occupancy
                $finalDateStr = $taskStart->toDateString();
                $dailyOccupancy[$finalDateStr] = ($dailyOccupancy[$finalDateStr] ?? 0) + 1;

                // --- TIẾN HÀNH LƯU LỊCH ---
                $firstTask = $subTasks->first();
                $type = strtoupper(substr($firstTask->code ?? '', -2));

                $parentIds = $subTasks->pluck('Parent_Equip_id')->unique()->filter()->implode(', ');
                $customTitle = ($parentIds ?: 'N/A') . ' _ ' . ($firstTask->Eqp_name ?? 'N/A') . ' :';
                foreach ($subTasks as $st) {
                    if ($st->instrument_code) $customTitle .= '<br/> - ' . $st->instrument_code;
                }
                $customTitle .= '<br/> Ngày tới hạn: ' . $group['min_due']->format('d/m/Y');

                $startClearning = null;
                $endClearning = null;
                if ($type === 'TB' || str_ends_with($firstTask->code ?? '', '_8')) {
                    $prevProduct = DB::table('stage_plan as sp')
                        ->join('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                        ->join('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
                        ->join('quota as q', function ($join) {
                            $join->on('fpc.intermediate_code', '=', 'q.intermediate_code')
                                ->on('sp.stage_code', '=', 'q.stage_code');
                        })
                        ->where('sp.resourceId', $roomId)
                        ->where('sp.end', '<=', $taskStart)
                        ->where('sp.stage_code', '<', 8)
                        ->orderBy('sp.end', 'desc')
                        ->select('q.C2_time')
                        ->first();

                    $dur = ($prevProduct && $prevProduct->C2_time) ? $this->toMinutes($prevProduct->C2_time) : 120;
                    $startClearning = $taskEnd->copy();
                    $endClearning = $startClearning->copy()->addMinutes($dur);
                }

                foreach ($subTasks as $plan) {
                    DB::table('stage_plan')
                        ->where('plan_master_id', $plan->plan_master_id)
                        ->update([
                            'start' => $taskStart,
                            'end' => $taskEnd,
                            'title' => $customTitle,
                            'start_clearning' => $startClearning,
                            'end_clearning' => $endClearning,
                            'title_clearning' => $startClearning ? 'VS-II' : null,
                            'schedualed_by' => session('user')['fullName'],
                            'schedualed_at' => now(),
                        ]);
                    $scheduledCount++;
                }

                $roomFreeTimes[$roomId] = $endClearning ?? $taskEnd;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AutoSchedule Error: ' . $e->getMessage());
            return response()->json(['error' => true, 'message' => $e->getMessage()], 500);
        }

        $viewStart = $request->viewStart ?? $startDate->toDateString();
        $viewEnd = $request->viewEnd ?? $startDate->copy()->addDays(7)->toDateString();
        $this->theory = (int) $request->theory ?? 0;
        $showProduction = $request->production ?? true;

        $eventsRaw = parent::getEvents($production, $viewStart, $viewEnd, true, $this->theory);

        if (! $showProduction) {
            $eventsRaw = $eventsRaw->filter(fn($e) => (int) $e->stage_code >= 8)->values();
        }

        $events = $eventsRaw;
        $plan_waiting = $this->getPlanWaiting($production);

        return response()->json([
            'success' => true,
            'scheduled_count' => $scheduledCount,
            'events' => $events,
            'plan' => $plan_waiting,
        ]);
    }

    public function cancelSchedule(Request $request)
    {

        $production = session('user')['production_code'];
        $startDate = $request->startDate;
        $typeFilter = $request->type; // HC, TB, TI
        $mode = $request->mode; // all, resource
        $resourceId = $request->resourceId;

        try {
            DB::beginTransaction();

            $query = DB::table('stage_plan')
                ->where('deparment_code', $production)
                ->where('stage_code', 8)
                ->where('active', 1)
                ->where('tank', 0)
                ->where('finished', 0)
                ->where('start', '>=', $startDate);

            // Lọc theo Type (Hậu tố mã code)
            if ($typeFilter === 'HC') {
                $query->where('code', 'like', '%\_HC');
            } elseif ($typeFilter === 'TB') {
                $query->where(function ($q) {
                    $q->where('code', 'like', '%\_TB')
                        ->orWhere('code', 'like', '%\_8');
                });
            } elseif ($typeFilter === 'TI') {
                $query->where('code', 'like', '%\_TI');
            }

            // Lọc theo mode Resource
            if ($mode === 'resource' && $resourceId) {
                $query->where('resourceId', $resourceId);
            }

            $query->update([
                'start' => null,
                'end' => null,
                'title' => null,
                'start_clearning' => null,
                'end_clearning' => null,
                'title_clearning' => null,

            ]);

            DB::commit();

            // Lấy lại dữ liệu view
            $viewStart = $request->viewStart ?? now()->startOfMonth()->toDateString();
            $viewEnd = $request->viewEnd ?? now()->endOfMonth()->toDateString();

            $this->theory = (int) $request->theory ?? 0;
            $showProduction = $request->production ?? true;

            $eventsRaw = parent::getEvents($production, $viewStart, $viewEnd, true, $this->theory);

            if (! $showProduction) {
                $eventsRaw = $eventsRaw->filter(fn($e) => (int) $e->stage_code >= 8)->values();
            }

            $events = $eventsRaw;
            $plan_waiting = $this->getPlanWaiting($production);

            return response()->json([
                'success' => true,
                'message' => 'Đã hủy lịch thành công.',
                'events' => $events,
                'plan' => $plan_waiting,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CancelSchedule Error: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getPlanWaiting($production, $order_by_type = false)
    {
        $plans = DB::table('stage_plan as sp')
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
        if (! empty($quotaIds)) {
            // 1. Lấy mapping room_id = 0 (PX)
            $pxMappings = DB::table('quota_maintenance_rooms')
                ->whereIn('quota_maintenance_id', $quotaIds)
                ->where('room_id', 0)
                ->get();

            // 2. Lấy mapping phòng thực tế
            $realRooms = DB::table('quota_maintenance_rooms as qmr')
                ->join('room', 'qmr.room_id', '=', 'room.id')
                ->whereIn('qmr.quota_maintenance_id', $quotaIds)
                ->select('qmr.quota_maintenance_id', 'room.code as room_code', 'room.name as room_name', 'room.production_group')
                ->get();

            // 3. Gộp lại
            $allRelated = $realRooms;
            foreach ($pxMappings as $px) {
                $allRelated->push((object)[
                    'quota_maintenance_id' => $px->quota_maintenance_id,
                    'room_code' => 'PX',
                    'room_name' => 'Toàn phân xưởng',
                    'production_group' => ''
                ]);
            }

            $relatedRooms = $allRelated->groupBy('quota_maintenance_id');
        }

        $instIds = $plans->pluck('instrument_code')->filter()->unique()->toArray();
        $instruments = collect();

        if (! empty($instIds)) {
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
            // ->where('q.active', 1)
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

        return ((int) $hours) * 60 + (int) $minutes;
    }

    public function store(Request $request)
    {
        $products = collect($request->products);

        $minDue = $products->pluck('expected_date')->filter()->min();
        $minDueText = $minDue ? '<br/> Ngày tới hạn: ' . Carbon::parse($minDue)->format('d/m/Y') : '';

        $firstProductCode = $products->first()['code'] ?? '';
        $type = $request->type ?? strtoupper(substr($firstProductCode, -2));
        $targetRoomId = $request->room_id;

        DB::beginTransaction();
        try {

            $products = $products->sortBy('id')->values();

            $start = Carbon::parse($request->start);

            if ($request->has('slotDuration') && $request->slotDuration == 1) {
                $start->setTime(7, 15, 0);
            }

            // 1. Kiểm tra phòng hợp lệ cho HC và BT
            // Bỏ qua kiểm tra nghiêm ngặt nếu k cập nhật resourceId, để cho phép kéo nhiều thiết bị khác phòng cùng lúc
            /*
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
            */

            if ($type == 'TI') {
                $rooms = DB::table('room')->select('id', 'code')->get()->keyBy('code');

                // Tạo tiêu đề chung theo logic autoSchedual
                $parentIds = $products->pluck('Parent_Equip_id')->unique()->filter()->implode(', ');
                $firstProduct = $products->first();
                $customTitle = ($parentIds ?: 'N/A') . ' _ ' . ($firstProduct['Eqp_name'] ?? 'N/A') . ' :';
                foreach ($products as $st) {
                    if (isset($st['instrument_code'])) {
                        $customTitle .= '<br/> - ' . $st['instrument_code'];
                    }
                }
                $customTitle .= $minDueText;

                foreach ($products as $product) {
                    $planMasterId = $product['plan_master_id'];
                    $productStart = $start->copy(); // Đảm bảo tất cả task của cùng 1 plan_master cùng start
                    $productEnd = $productStart->copy()->addMinutes($this->toMinutes($product['PM']));

                    DB::table('stage_plan')
                        ->where('plan_master_id', $planMasterId)
                        ->get()
                        ->each(function ($sp) use ($productStart, $productEnd, $customTitle) {
                            DB::table('stage_plan')
                                ->where('id', $sp->id)
                                ->update([
                                    'start' => $productStart,
                                    'end' => $productEnd,
                                    'title' => $customTitle,
                                    'schedualed_by' => session('user')['fullName'],
                                    'schedualed_at' => now(),
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

                // Thêm logic lấy thời gian vệ sinh C2 cho trường hợp BT tương tự autoSchedual
                $startClearning = null;
                $endClearning = null;
                if ($type == 'BT' || $type == 'TB' || str_ends_with($firstProductCode, '_8')) {
                    $prevProduct = DB::table('stage_plan as sp')
                        ->join('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                        ->join('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
                        ->join('quota as q', function ($join) {
                            $join->on('fpc.intermediate_code', '=', 'q.intermediate_code')
                                ->on('sp.stage_code', '=', 'q.stage_code');
                        })
                        ->where('sp.resourceId', $targetRoomId)
                        ->where(function ($q) use ($start) {
                            $q->where('sp.end', '<=', $start)
                                ->orWhere('sp.actual_end', '<=', $start);
                        })
                        ->where('sp.stage_code', '<', 8)
                        ->orderBy('sp.end', 'desc')
                        ->select('q.C2_time')
                        ->first();

                    $dur = ($prevProduct && $prevProduct->C2_time) ? $this->toMinutes($prevProduct->C2_time) : 120;
                    $startClearning = $overallEnd->copy();
                    $endClearning = $startClearning->copy()->addMinutes($dur);
                }

                // 2. Tạo tiêu đề chung theo logic autoSchedual
                $parentIds = $products->pluck('Parent_Equip_id')->unique()->filter()->implode(', ');
                $firstProduct = $products->first();
                $customTitle = ($parentIds ?: 'N/A') . ' _ ' . ($firstProduct['Eqp_name'] ?? 'N/A') . ' :';
                foreach ($products as $st) {
                    if (isset($st['instrument_code'])) {
                        $customTitle .= '<br/> - ' . $st['instrument_code'];
                    }
                }
                $customTitle .= $minDueText;

                $uniquePlanMasterIds = $products->pluck('plan_master_id')->unique();

                foreach ($uniquePlanMasterIds as $planMasterId) {
                    DB::table('stage_plan')
                        ->where('plan_master_id', $planMasterId)
                        ->update([
                            'resourceId' => $targetRoomId,
                            'start' => $start,
                            'end' => $overallEnd,
                            'title_clearning' => $startClearning ? 'VS-II' : null,
                            'start_clearning' => $startClearning,
                            'end_clearning' => $endClearning,
                            'title' => $customTitle,
                            'schedualed_by' => session('user')['fullName'],
                            'schedualed_at' => now(),
                        ]);

                    // Cập nhật history cho tất cả các task liên quan có submit = 1
                    $relatedTasks = DB::table('stage_plan')->where('plan_master_id', $planMasterId)->get();

                    foreach ($relatedTasks as $task) {
                        if ($task->submit == 1) {
                            $last_version = DB::table('stage_plan_history')
                                ->where('stage_plan_id', $task->id)
                                ->max('version') ?? 0;

                            DB::table('stage_plan_history')->insert([
                                'stage_plan_id' => $task->id,
                                'version' => $last_version + 1,
                                'start' => $start,
                                'end' => $overallEnd,
                                'start_clearning' => $startClearning,
                                'end_clearning' => $endClearning,
                                'resourceId' => $task->resourceId, // Sử dụng resourceId hiện có của task
                                'title' => $customTitle,
                                'schedualed_by' => session('user')['fullName'],
                                'schedualed_at' => now(),
                                'deparment_code' => session('user')['production_code'],
                                'type_of_change' => $request->reason ?? 'Lập Lịch Thủ Công',
                            ]);
                        }
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {

            DB::rollBack();
            Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }

        /*
                |--------------------------------------------------------------------------
                | TRẢ KẾT QUẢ
                |--------------------------------------------------------------------------
                */
        $production = session('user')['production_code'];
        $startDate = $request->startDate;
        $endDate = $request->endDate;
        $this->theory = (int) $request->theory ?? 0;
        $showProduction = $request->production ?? true;

        $eventsRaw = parent::getEvents($production, $startDate, $endDate, true, $this->theory);

        if (! $showProduction) {
            $eventsRaw = $eventsRaw->filter(fn($e) => (int) $e->stage_code >= 8)->values();
        }

        $events = $eventsRaw;
        $sumBatchByStage = parent::yield($startDate, $endDate, 'stage_code');
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
        $this->theory = (int) $request->theory ?? 0;

        DB::beginTransaction();
        try {
            $offDays = DB::table('off_days')
                ->whereDate('off_date', '>=', now())
                ->pluck('off_date')
                ->toArray();

            foreach ($changes as $change) {
                $idParts = explode('-', $change['id']);
                $realIdRaw = $idParts[0] ?? null;

                if (! $realIdRaw) {
                    continue;
                }

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

                // Thêm logic cập nhật clearning cho BT
                $startClearning = null;
                $endClearning = null;
                $isBT = DB::table('plan_master')
                    ->join('quota_maintenance', 'plan_master.product_caterogy_id', '=', 'quota_maintenance.id')
                    ->whereIn('plan_master.id', $sourceStagePlans->pluck('plan_master_id')->unique()->toArray())
                    ->where('quota_maintenance.block', 'like', 'BT-%')
                    ->exists();

                if ($isBT) {
                    $prevProduct = DB::table('stage_plan as sp')
                        ->join('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                        ->join('finished_product_category as fpc', 'pm.product_caterogy_id', '=', 'fpc.id')
                        ->join('quota as q', function ($join) {
                            $join->on('fpc.intermediate_code', '=', 'q.intermediate_code')
                                ->on('sp.stage_code', '=', 'q.stage_code');
                        })
                        ->where('sp.resourceId', $change['resourceId'])
                        ->where(function ($q) use ($newStart) {
                            $q->where('sp.end', '<=', $newStart)
                                ->orWhere('sp.actual_end', '<=', $newStart);
                        })
                        ->where('sp.stage_code', '<', 8)
                        ->orderBy('sp.end', 'desc')
                        ->select('q.C2_time')
                        ->first();

                    $dur = ($prevProduct && $prevProduct->C2_time) ? $this->toMinutes($prevProduct->C2_time) : 120;
                    $startClearning = \Carbon\Carbon::parse($change['end']);
                    $endClearning = $startClearning->copy()->addMinutes($dur);
                }

                if ($isTI && ! empty($codes)) {
                    // 1. Cập nhật các bản ghi gốc (cả thời gian và resourceId)
                    DB::table('stage_plan')
                        ->whereIn('id', $ids)
                        ->update([
                            'start' => $change['start'],
                            'end' => $change['end'],
                            'start_clearning' => $startClearning,
                            'end_clearning' => $endClearning,
                            'resourceId' => $change['resourceId'],
                            'receive_packaging_date' => $receiveDate,
                            'receive_second_packaging_date' => $receiveDate,
                            'schedualed_by' => session('user')['fullName'],
                            'schedualed_at' => now(),
                        ]);

                    // 2. Tìm các bản ghi liên đới cùng code nhưng khác ID gốc
                    $otherIds = DB::table('stage_plan')
                        ->whereIn('code', $codes)
                        ->whereNotIn('id', $ids)
                        ->pluck('id')->toArray();

                    if (! empty($otherIds)) {
                        DB::table('stage_plan')
                            ->whereIn('id', $otherIds)
                            ->update([
                                'start' => $change['start'],
                                'end' => $change['end'],
                                'start_clearning' => $startClearning,
                                'end_clearning' => $endClearning,
                                // KHÔNG thay đổi resourceId cho các bản ghi liên đới
                                'receive_packaging_date' => $receiveDate,
                                'receive_second_packaging_date' => $receiveDate,
                                'schedualed_by' => session('user')['fullName'],
                                'schedualed_at' => now(),
                            ]);
                    }
                    $allAffectedIds = array_unique(array_merge($ids, $otherIds));
                } else {
                    // Logic cũ cho Non-TI: Cập nhật tất cả bản ghi được xác định (bao gồm resourceId)
                    DB::table('stage_plan')
                        ->whereIn('id', $allAffectedIds)
                        ->update([
                            'start' => $change['start'],
                            'end' => $change['end'],
                            'start_clearning' => $startClearning,
                            'end_clearning' => $endClearning,
                            'resourceId' => $change['resourceId'],
                            'receive_packaging_date' => $receiveDate,
                            'receive_second_packaging_date' => $receiveDate,
                            'schedualed_by' => session('user')['fullName'],
                            'schedualed_at' => now(),
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

                if (! empty($planMasterIds)) {
                    DB::table('plan_master')->whereIn('id', $planMasterIds)->update([
                        'expected_date' => $newDate,
                        'updated_at' => now(),
                    ]);

                    // Ghi lịch sử cho plan_master
                    foreach ($planMasterIds as $pmId) {
                        if (! $pmId) {
                            continue;
                        }
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
                                'reason' => $request->reason['reason'] ?? 'Cập nhật từ lịch bảo trì',
                                'deparment_code' => session('user')['production_code'],
                                'prepared_by' => session('user')['fullName'],
                                'created_at' => now(),
                                'updated_at' => now(),
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
                            'stage_plan_id' => $id,
                            'version' => $last_version + 1,
                            'start' => $change['start'],
                            'end' => $change['end'],
                            'start_clearning' => $startClearning,
                            'end_clearning' => $endClearning,
                            'resourceId' => $change['resourceId'],
                            'title' => $row->title,
                            'schedualed_by' => session('user')['fullName'],
                            'schedualed_at' => now(),
                            'deparment_code' => session('user')['production_code'],
                            'type_of_change' => $request->reason['reason'] ?? 'Cập Nhật Lịch Bảo Trì',
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
        $startDate = $request->startDate;
        $endDate = $request->endDate;
        $this->theory = (int) $request->theory ?? 0;
        $showProduction = $request->production ?? true;

        $eventsRaw = parent::getEvents($production, $startDate, $endDate, true, $this->theory);

        if (! $showProduction) {
            $eventsRaw = $eventsRaw->filter(fn($e) => (int) $e->stage_code >= 8)->values();
        }

        $events = $eventsRaw;
        $plan_waiting = $this->getPlanWaiting($production);
        $resources = parent::getResources($production, $startDate, $endDate);
        $sumBatchByStage = $this->yield($startDate, $endDate, 'stage_code');

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

                // Lấy tất cả các plan_master_id liên quan đến nhóm sự kiện này
                $sourceStagePlans = DB::table('stage_plan')->whereIn('id', $ids)->get();
                $planMasterIds = $sourceStagePlans->pluck('plan_master_id')->filter()->unique()->toArray();
                $codes = $sourceStagePlans->pluck('code')->filter()->unique()->toArray();

                // KIỂM TRA TI: Nếu là TI, tác động theo code của toàn bộ thiết bị trong nhóm
                $isTI = DB::table('plan_master')
                    ->join('quota_maintenance', 'plan_master.product_caterogy_id', '=', 'quota_maintenance.id')
                    ->whereIn('plan_master.id', $planMasterIds)
                    ->where('quota_maintenance.block', 'like', 'TI-%')
                    ->exists();

                $targetPlanMasterIds = $planMasterIds;
                if ($isTI && !empty($codes)) {
                    // Nếu có TI, tìm thêm các plan_master_id khác có cùng code thiết bị
                    $additionalPMIds = DB::table('stage_plan')
                        ->whereIn('code', $codes)
                        ->pluck('plan_master_id')
                        ->filter()
                        ->unique()
                        ->toArray();
                    $targetPlanMasterIds = array_unique(array_merge($planMasterIds, $additionalPMIds));
                }

                DB::table('stage_plan')
                    ->where('finished', 0)
                    ->whereIn('plan_master_id', $targetPlanMasterIds)
                    ->where('stage_code', '>=', $stageCode)
                    ->update([
                        'start' => null,
                        'end' => null,
                        'start_clearning' => null,
                        'end_clearning' => null,
                        'title' => null,
                        'title_clearning' => null,
                        'accept_quarantine' => 0,
                        'schedualed' => 0,
                        'schedualed_by' => session('user')['fullName'],
                        'schedualed_at' => now(),
                    ]);
            }

            $production = session('user')['production_code'];
            $startDate = $request->startDate ?? now()->startOfMonth()->toDateString();
            $endDate = $request->endDate ?? now()->endOfMonth()->toDateString();

            $this->theory = (int) $request->theory ?? 0;
            $showProduction = $request->production ?? true;

            $eventsRaw = parent::getEvents($production, $startDate, $endDate, true, $this->theory);

            if (! $showProduction) {
                $eventsRaw = $eventsRaw->filter(fn($e) => (int) $e->stage_code >= 8)->values();
            }

            $events = $eventsRaw;
            $plan_waiting = $this->getPlanWaiting($production);
            $sumBatchByStage = $this->yield($startDate, $endDate, 'stage_code');

            return response()->json([
                'events' => $events,
                'plan' => $plan_waiting,
                'sumBatchByStage' => $sumBatchByStage,
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // Thừa kế getEvents từ SchedualController vì đã có gộp bảo trì mặc định ở đó

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
                            $schId = (string) $res->SCH_ID;
                            if (isset($schIdMap[$schId])) {
                                foreach ($schIdMap[$schId] as $spId) {
                                    // Map Sch_Result_Status to yields (Pass=1, Fail=0, Skip=2)
                                    $yield = 1;
                                    $statusRaw = trim($res->Sch_Result_Status ?? 'Pass');
                                    if ($statusRaw == 'Fail') {
                                        $yield = 0;
                                    } elseif ($statusRaw == 'Skip') {
                                        $yield = 2;
                                    }

                                    // Cập nhật stage_plan thực tế
                                    DB::table('stage_plan')->where('id', $spId)->where('finished', 0)->update([
                                        'actual_start' => $res->Sch_caldone_to,
                                        'actual_end' => $res->Sch_caldone_to,
                                        'finished_by' => $res->Sch_cal_Done_by ?? null,
                                        'finished' => 1,
                                        'yields' => $yield,
                                        'updated_at' => now(),
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
            'count' => $count,
        ]);
    }

    public function confirmFinish(Request $request)
    {
        $ids = $request->ids; // Mảng các stage_plan id được chọn
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'Vui lòng chọn ít nhất một sự kiện.'], 400);
        }

        // Lấy danh sách plan_master_id của các ID này
        $planMasterIds = DB::table('stage_plan')
            ->whereIn('id', $ids)
            ->pluck('plan_master_id')
            ->unique();

        DB::beginTransaction();
        try {
            $updatedCount = DB::table('stage_plan')
                ->whereIn('plan_master_id', $planMasterIds)
                ->where('stage_code', 8)
                ->where('finished', 0)
                ->update([
                    'finished' => 1,
                    'finished_date' => now(),
                    'finished_by' => session('user')['fullName'] ?? 'System',
                ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => "Đã xác nhận hoàn thành cho {$updatedCount} dòng công việc."]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function approveMaintenance(Request $request)
    {
        $ids = $request->ids; // Mảng các stage_plan id được chọn
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'Vui lòng chọn ít nhất một sự kiện.'], 400);
        }

        DB::beginTransaction();
        try {
            // Lấy các dòng được chọn
            $rows = DB::table('stage_plan')
                ->whereIn('id', $ids)
                ->where('stage_code', 8)
                ->where('finished', 0)
                ->get();

            $toApprove = $rows->where('tank', 0)->pluck('id')->toArray();
            $toUnapprove = $rows->where('tank', 1)->pluck('id')->toArray();

            $approvedCount = 0;
            $unapprovedCount = 0;

            if (!empty($toApprove)) {
                $approvedCount = DB::table('stage_plan')
                    ->whereIn('id', $toApprove)
                    ->update([
                        'tank' => 1,
                        'quarantined_date' => now(),
                        'quarantined_by' => session('user')['fullName'] ?? 'System',
                    ]);
            }

            if (!empty($toUnapprove)) {
                $unapprovedCount = DB::table('stage_plan')
                    ->whereIn('id', $toUnapprove)
                    ->update([
                        'tank' => 0,
                        'quarantined_date' => null,
                        'quarantined_by' => null,
                    ]);
            }

            DB::commit();

            $messages = [];
            if ($approvedCount > 0) $messages[] = "Đã duyệt {$approvedCount} dòng";
            if ($unapprovedCount > 0) $messages[] = "Đã hủy duyệt {$unapprovedCount} dòng";

            return response()->json(['success' => true, 'message' => implode(', ', $messages) . '.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

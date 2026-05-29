<?php

namespace App\Http\Controllers\Pages\Schedual;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;

class AutoSchedualController extends Controller
{
    /**
     * Entry point for multi-pass simulation
     */
    public function simulateScheduleAll(Request $request)
    {
        ini_set('max_execution_time', 0);
        set_time_limit(0); // 0 để vô hiệu hóa hoàn toàn giới hạn timeout của PHP

        $max_iterations = 1; // Chỉ chạy 1 lượt

        DB::beginTransaction();
        try {
            // Reset cache của cha và con
            $this->roomAvailability = [];
            $this->simulatedRoomAvailabilityCache = [];
            $this->processed_stage_code_Id = [];
            $this->simulatedScheduledIds = [];
            $this->simulatedPlanResult = [];

            // Gọi thuật toán xếp lịch thông minh
            $this->smartScheduleAll($request, 0);

            // Điểm không còn bắt buộc phải tính nếu không cần so sánh, nhưng có thể giữ lại để log
            $score = $this->evaluateScore($this->simulatedPlanResult);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage() . ' at line ' . $e->getLine()], 500);
        }

        // Dù thành công hay thất bại, luôn ROLLBACK lần mô phỏng để không lưu đè DB ảo
        DB::rollBack();

        // Tiến hành lưu luôn kết quả của lượt chạy duy nhất
        DB::beginTransaction();
        try {
            foreach ($this->simulatedPlanResult as $task) {
                $start = Carbon::parse($task['start']);
                $end = Carbon::parse($task['end']);
                $start_clearning = $task['start_clearning'] ? Carbon::parse($task['start_clearning']) : null;
                $endCleaning = $task['end_clearning'] ? Carbon::parse($task['end_clearning']) : null;
                $cleaningType = ($task['title_clearning'] == 'VS-II') ? '2' : '1';
                $direction = true;

                // Lưu thực sự vào DB bằng hàm realSaveSchedule đã copy
                $this->realSaveSchedule(
                    $task['first_in_campaign'],
                    $task['id'],
                    $task['resourceId'],
                    $start,
                    $end,
                    $start_clearning,
                    $endCleaning,
                    $cleaningType,
                    $direction
                );
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Lỗi khi lưu lịch: ' . $e->getMessage() . ' at line ' . $e->getLine()], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Đã lưu lịch tự động thành công!',
            'score' => $score
        ]);
    }

    /**
     * Smart version of scheduleAll using responsed_date EDD
     */
    protected function smartScheduleAll(Request $request, $iteration)
    {
        $this->selectedDates = $request->selectedDates ?? [];
        $this->work_sunday = $request->work_sunday ?? false;
        $this->reason = $request->reason ?? 'NA';
        $this->prev_orderBy = $request->prev_orderBy ?? false;
        $this->loadOffDate('asc');

        $today = Carbon::now()->toDateString();
        $start_date = Carbon::createFromFormat('Y-m-d', $request->start_date ?? $today)->setTime(6, 0, 0);

        if ($request->selectedStep == 'CNL') {
            $this->scheduleWeightStage($start_date);
            return;
        }

        $Step = [
            'PC' => 3,
            'THT' => 4,
            'ĐH' => 5,
            'BP' => 6,
            'ĐG' => 7,
        ];
        $selectedStep = $Step[$request->selectedStep ?? 'ĐG'];
        $this->max_Step = $selectedStep;

        $stageCodes = DB::table('stage_plan as sp')
            ->distinct()
            ->where('sp.stage_code', '>=', 3)
            ->where('sp.stage_code', '<=', $selectedStep)
            ->where('sp.deparment_code', session('user.production_code'))
            ->orderBy('sp.stage_code')
            ->pluck('sp.stage_code');



        // Chạy thuật toán xếp lịch Thông Minh (dựa trên responsed_date EDD thay vì kế hoạch)
        // Lưu ý: Không dùng scheduleIntermediate hay scheduleSensitiveProduct của class cha nữa
        // vì chúng sử dụng logic cũ không tối ưu. Mọi thứ được gom vào vòng lặp dưới đây.

        // Phase 3: Smart Forward (EDD based on responsed_date)
        foreach ($stageCodes as $i) {
            $waite_time_nomal_batch = 0;
            $waite_time_val_batch = 0;
            switch ($i) {
                case 4:
                    $waite_time_nomal_batch = ($request->wt_bleding ?? 0) * 24 * 60;
                    $waite_time_val_batch = ($request->wt_bleding_val ?? 1) * 24 * 60;
                    break;
                case 5:
                    $waite_time_nomal_batch = ($request->wt_forming ?? 0) * 24 * 60;
                    $waite_time_val_batch = ($request->wt_forming_val ?? 5) * 24 * 60;
                    break;
                case 6:
                    $waite_time_nomal_batch = ($request->wt_coating ?? 0) * 24 * 60;
                    $waite_time_val_batch = ($request->wt_coating_val ?? 5) * 24 * 60;
                    break;
                case 7:
                    $waite_time_nomal_batch = ($request->wt_blitering ?? 0) * 24 * 60;
                    $waite_time_val_batch = ($request->wt_blitering_val ?? 5) * 24 * 60;
                    break;
            }
            // Pass iteration to allow for randomization variations across passes
            $this->Auto_scheduler_Stage_Forward_Smart($i, $waite_time_nomal_batch, $waite_time_val_batch, $start_date, $iteration);
        }
    }

    /**
     * Phase 3 replacement using responsed_date instead of order_by
     */
    public function Auto_scheduler_Stage_Forward_Smart(int $stageCode, int $waite_time_nomal_batch = 0, int $waite_time_val_batch = 0, ?Carbon $start_date = null, int $iteration = 0)
    {
        // EDD Base Query
        $query = DB::table('stage_plan as sp')
            ->select(
                'sp.id',
                'sp.plan_master_id',
                'sp.product_caterogy_id',
                'sp.predecessor_code',
                'sp.nextcessor_code',
                'sp.campaign_code',
                'sp.code',
                'sp.stage_code',
                'sp.tank',
                'sp.keep_dry',
                'sp.order_by',
                'sp.required_room_code',
                'sp.immediately',
                'plan_master.batch',
                'plan_master.is_val',
                'plan_master.code_val',
                'plan_master.responsed_date',
                'plan_master.expected_date',
                'plan_master.after_weigth_date',
                'plan_master.after_parkaging_date',
                'plan_master.allow_weight_before_date',
                'finished_product_category.product_name_id',
                'finished_product_category.market_id',
                'finished_product_category.finished_product_code',
                'finished_product_category.intermediate_code',
                'product_name.name',
                'market.code as market',
                'prev.start as prev_start',
                'intermediate_category.quarantine_total'
            )
            ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
            ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
            ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
            ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', 'intermediate_category.intermediate_code')
            ->leftJoin('stage_plan as prev', 'prev.code', '=', 'sp.predecessor_code')
            ->where('sp.stage_code', $stageCode)
            ->where('sp.finished', 0)
            ->where('sp.active', 1)
            ->whereNull('sp.start')
            ->where('sp.not_schedule', 0)
            ->whereNotNull('plan_master.after_weigth_date')
            ->when($stageCode == 7, function ($q) {
                $q->whereNotNull('plan_master.after_parkaging_date');
            })
            ->where('sp.deparment_code', session('user.production_code'));

        // Bổ sung quy tắc phân cấp ưu tiên: BTP -> Nhạy cảm -> Thường
        // Ưu tiên 1: Bán thành phẩm (prev.start IS NOT NULL)
        // Ưu tiên 2: SP nhạy cảm (quarantine_total > 0)
        // Ưu tiên 3: Còn lại
        $query->orderByRaw("
            CASE 
                WHEN prev.start IS NOT NULL THEN 1 
                WHEN intermediate_category.quarantine_total > 0 THEN 2 
                ELSE 3 
            END ASC
        ");

        // Smart sort: EDD (Sau khi đã gom nhóm ưu tiên)
        $query->orderBy('plan_master.responsed_date', 'asc');
        // Adding order_by as secondary sort to respect some manual priority within same day
        $query->orderBy('sp.order_by', 'asc');

        $tasks = $query->get();

        // Introduce small randomization in later iterations for equal responsed_dates
        if ($iteration > 0 && !$this->prev_orderBy) {
            $grouped = $tasks->groupBy(function ($item) {
                return substr($item->responsed_date, 0, 10);
            });
            $shuffledTasks = collect();
            foreach ($grouped as $group) {
                $shuffledTasks = $shuffledTasks->merge($group->shuffle());
            }
            $tasks = $shuffledTasks;
        }

        $processedCampaigns = [];

        foreach ($tasks as $task) {

            // Re-check if task was scheduled recursively during this phase
            if (isset($this->simulatedScheduledIds[$task->id])) {
                continue;
            }

            $waite_time = ($task->is_val === 1) ? $waite_time_val_batch : $waite_time_nomal_batch;

            if ($task->campaign_code === null) {
                $this->sheduleNotCampaing($task, $stageCode, $waite_time, $start_date, null);
            } else {
                if (in_array($task->campaign_code, $processedCampaigns)) {
                    continue;
                }
                $campaignTasks = $tasks->where('campaign_code', $task->campaign_code)->sortBy('batch');

                // filter out already scheduled tasks in campaign
                $campaignTasks = $campaignTasks->reject(function ($ct) {
                    return isset($this->simulatedScheduledIds[$ct->id]);
                });

                if ($campaignTasks->isNotEmpty()) {
                    $this->scheduleCampaign($campaignTasks, $stageCode, $waite_time, $start_date, null);
                }
                $processedCampaigns[] = $task->campaign_code;
            }
        }
    }

    /**
     * Evaluate the Cost/Score of a simulated schedule
     */
    protected function evaluateScore($plan)
    {
        $tardinessCost = 0;
        $gapCost = 0;
        $roomPlans = [];

        $taskIds = array_column($plan, 'id');
        $metas = DB::table('stage_plan')
            ->join('plan_master', 'stage_plan.plan_master_id', '=', 'plan_master.id')
            ->whereIn('stage_plan.id', $taskIds)
            ->select('stage_plan.id', 'plan_master.responsed_date')
            ->get()
            ->keyBy('id');

        foreach ($plan as $task) {
            $meta = $metas->get($task['id']);

            if ($meta && $meta->responsed_date) {
                $endDate = Carbon::parse($task['end']);
                $respDate = Carbon::parse($meta->responsed_date)->endOfDay();

                if ($endDate->gt($respDate)) {
                    $tardinessCost += $endDate->diffInMinutes($respDate);
                }
            }
            $roomPlans[$task['room_id']][] = $task;
        }

        foreach ($roomPlans as $roomId => $tasks) {
            usort($tasks, function ($a, $b) {
                return strcmp($a['start'], $b['start']);
            });

            for ($i = 0; $i < count($tasks) - 1; $i++) {
                $endOfCurrent = Carbon::parse($tasks[$i]['end_clearning'] ?? $tasks[$i]['end']);
                $startOfNext = Carbon::parse($tasks[$i + 1]['start']);

                if ($startOfNext->gt($endOfCurrent)) {
                    $gapCost += $startOfNext->diffInMinutes($endOfCurrent);
                }
            }
        }

        return ($tardinessCost * 10) + $gapCost;
    }

    protected $simulatedRoomAvailabilityCache = [];
    protected $simulatedScheduledIds = [];
    protected $simulatedPlanResult = [];

    /**
     * Override loadRoomAvailability to use cache during simulation
     */
    protected function loadRoomAvailability(string $sort, int $roomId)
    {
        // Nếu đã cache, chỉ cần trả về
        if (isset($this->simulatedRoomAvailabilityCache[$roomId])) {
            $this->roomAvailability[$roomId] = $this->simulatedRoomAvailabilityCache[$roomId];
            return $this->roomAvailability[$roomId];
        }

        // Lần đầu gọi cho phòng này: Lấy từ DB (lịch thật + lịch đã được lưu trong transaction tính tới thời điểm này)
        $this->baseLoadRoomAvailability($sort, $roomId);

        // Cache lại để dùng cho các lần gọi kế tiếp
        $this->simulatedRoomAvailabilityCache[$roomId] = $this->roomAvailability[$roomId];

        return $this->roomAvailability[$roomId];
    }

    /**
     * Override saveSchedule to update cache in-memory instead of letting next loadRoomAvailability query DB
     */
    protected function saveSchedule($first_in_campaign, $stageId, $roomId, $start, $end, $start_clearning, $endCleaning, string $cleaningType, bool $direction)
    {
        // Đánh dấu id này đã được xếp lịch trong RAM để các vòng lặp ngoài skip ngay lập tức
        $this->simulatedScheduledIds[$stageId] = true;

        // Cập nhật nhẹ nhàng vào DB trong transaction để các query sau (orderBy prev.start) vẫn hoạt động
        DB::table('stage_plan')->where('id', $stageId)->update([
            'first_in_campaign' => $first_in_campaign ?? 0,
            'resourceId' => $roomId,
            'start' => $start,
            'end' => $end,
            'start_clearning' => $start_clearning,
            'end_clearning' => $endCleaning,
            'title_clearning' => $cleaningType,
        ]);

        // Đẩy kết quả vào mảng RAM để khỏi query lấy lại sau cùng
        $this->simulatedPlanResult[] = [
            'id' => $stageId,
            'first_in_campaign' => $first_in_campaign ?? 0,
            'resourceId' => $roomId,
            'room_id' => $roomId,
            'start' => (string) $start,
            'end' => (string) $end,
            'start_clearning' => (string) $start_clearning,
            'end_clearning' => (string) $endCleaning,
            'title_clearning' => $cleaningType,
            'receive_packaging_date' => null
        ];

        // 2. Cập nhật cache In-Memory
        $s = clone $start;
        $e = $endCleaning ? clone $endCleaning : clone $end;

        if (!isset($this->simulatedRoomAvailabilityCache[$roomId])) {
            // Khởi tạo nếu chưa có
            $this->loadRoomAvailability('asc', $roomId);
        }

        // Thêm block mới vào mảng cache
        $this->simulatedRoomAvailabilityCache[$roomId][] = [
            'start' => $s,
            'end' => $e
        ];

        // Sắp xếp và gộp các block bị chồng lấp bằng PHP thuần (nhanh gấp 100 lần so với dùng collect() trong vòng lặp)
        $cache = $this->simulatedRoomAvailabilityCache[$roomId];

        usort($cache, function ($a, $b) {
            return $a['start'] <=> $b['start'];
        });

        $finalBlocks = [];
        foreach ($cache as $currentBlock) {
            if (empty($finalBlocks)) {
                $finalBlocks[] = [
                    'start' => clone $currentBlock['start'],
                    'end' => clone $currentBlock['end']
                ];
            } else {
                $lastIndex = count($finalBlocks) - 1;
                $lastBlock = $finalBlocks[$lastIndex];

                if ($currentBlock['start']->lte($lastBlock['end'])) {
                    if ($currentBlock['end']->gt($lastBlock['end'])) {
                        $finalBlocks[$lastIndex]['end'] = clone $currentBlock['end'];
                    }
                } else {
                    $finalBlocks[] = [
                        'start' => clone $currentBlock['start'],
                        'end' => clone $currentBlock['end']
                    ];
                }
            }
        }

        $this->simulatedRoomAvailabilityCache[$roomId] = $finalBlocks;
        $this->roomAvailability[$roomId] = $this->simulatedRoomAvailabilityCache[$roomId];
    }

    /**
     * Endpoint to save the approved simulated plan
     */
    public function commitSimulatedSchedule(Request $request)
    {
        $plan = $request->input('schedule');
        if (!$plan) {
            return response()->json(['status' => 'error', 'message' => 'No schedule provided'], 400);
        }

        foreach ($plan as $task) {
            $start = Carbon::parse($task['start']);
            $end = Carbon::parse($task['end']);
            $start_clearning = $task['start_clearning'] ? Carbon::parse($task['start_clearning']) : null;
            $endCleaning = $task['end_clearning'] ? Carbon::parse($task['end_clearning']) : null;
            $cleaningType = ($task['title_clearning'] == 'VS-II') ? '2' : '1';
            $direction = true;

            // Sử dụng hàm gốc để thực sự lưu vào DB
            $this->realSaveSchedule(
                $task['first_in_campaign'],
                $task['id'],
                $task['resourceId'],
                $start,
                $end,
                $start_clearning,
                $endCleaning,
                $cleaningType,
                $direction
            );
        }

        return response()->json(['status' => 'success', 'message' => 'Lưu lịch thành công!']);
    }

    protected $order_by = 1;
    protected $selectedDates = [];
    protected $offDate = [];
    protected $work_sunday = true;
    protected $max_Step = 3;
    protected $reason = null;
    protected $theory = 0;
    protected $prev_orderBy = false;
    protected $stage_Name = [
        1 => 'Cân NL',
        3 => 'PC',
        4 => 'THT',
        5 => 'ĐH',
        6 => 'BP',
        7 => 'ĐG',
    ];
    protected $processed_stage_code_Id = [];
    protected $roomAvailability = [];


    protected function skipOffTime(Carbon $time, array $offDateList, ?int $roomId = null): Carbon
    {

        $busyList = [];

        if ($roomId) {

            $busyList = $this->loadRoomAvailability('asc', $roomId);
        }

        foreach ($offDateList as $off) {

            // đảm bảo kiểu Carbon
            $start = $off['start']  instanceof Carbon
                ? $off['start']
                : Carbon::parse($off['start']);

            $end = $off['end']  instanceof Carbon
                ? $off['end']
                : Carbon::parse($off['end']);

            // nếu time nằm trong khoảng off
            if ($time->gte($start) && $time->lt($end)) {

                return $end->copy();
                // nhảy tới cuối off
            }

            // vì offdatelist đã sort theo start
            if ($time->lt($start)) {

                break;
            }
        }

        if (! empty($busyList)) {

            foreach ($busyList as $off) {

                // đảm bảo kiểu Carbon
                $start = $off['start']  instanceof Carbon
                    ? $off['start']
                    : Carbon::parse($off['start']);

                $end = $off['end']  instanceof Carbon
                    ? $off['end']
                    : Carbon::parse($off['end']);

                // nếu time nằm trong khoảng off
                if ($time->gte($start) && $time->lt($end)) {

                    return $end->copy();
                    // nhảy tới cuối off
                }

                // vì offdatelist đã sort theo start
                if ($time->lt($start)) {

                    break;
                }
            }
        }

        return $time;
    }


    protected function baseLoadRoomAvailability(string $sort, int $roomId)
    {

        $this->roomAvailability[$roomId] = [];

        $notCampaign = DB::table('stage_plan')
            ->where('resourceId', $roomId)
            ->where('finished', 0)
            ->whereNull('campaign_code')
            ->where(function ($q) {

                $q->where('end', '>=', now())
                    ->orWhere('end_clearning', '>=', now());
            })
            ->select(
                'start',
                DB::raw('COALESCE(end_clearning, end) as end')

            )
            ->orderBy('start')
            ->get();

        $campaign = DB::table('stage_plan')
            ->where('finished', 0)
            ->where('resourceId', $roomId)
            ->whereNotNull('campaign_code')
            ->where(function ($q) {

                $q->where('end', '>=', now())
                    ->orWhere('end_clearning', '>=', now());
            })
            ->select(
                // 'id',
                // 'resourceId',
                'campaign_code',
                DB::raw('MIN(start) as start'),
                DB::raw('MAX(COALESCE(end_clearning, end)) as end')

            )
            ->groupBy('campaign_code')
            ->orderBy('start')
            ->get();

        $blocks = collect()
            ->merge($notCampaign)
            ->merge($campaign)
            ->map(function ($row) {

                return [
                    'start' => Carbon::parse($row->start),
                    'end' => Carbon::parse($row->end),
                ];
            })
            ->sortBy('start')
            ->values();

        $merged = [];

        foreach ($blocks as $row) {

            if (empty($merged)) {

                $merged[] = $row;

                continue;
            }

            $lastIndex = count($merged) - 1;

            $last = $merged[$lastIndex];

            if ($row['start']->lte($last['end'])) {

                if ($row['end']->gt($last['end'])) {

                    $merged[$lastIndex]['end'] = $row['end'];
                }
            } else {

                $merged[] = $row;
            }
        }

        // foreach ($blocks as $row) {
        //         $start = Carbon::parse($row->start);
        //         $end   = Carbon::parse($row->end);

        //         // Khoảng đầu tiên
        //         if (empty($merged)) {
        //                 $merged[] = [
        //                 'start' => $start,
        //                 'end'   => $end,
        //                 ];
        //                 continue;
        //         }

        //         // Lấy khoảng cuối cùng đã gom
        //         $lastIndex = count($merged) - 1;
        //         $last      = $merged[$lastIndex];

        //         // Nếu khoảng mới nối / chồng khoảng cũ
        //         if ($start->lte($last['end'])) {

        //                 // kéo dài end nếu cần
        //                 if ($end->gt($last['end'])) {
        //                 $merged[$lastIndex]['end'] = $end;
        //                 }

        //         } else {
        //                 // Khoảng tách biệt → tạo block mới
        //                 $merged[] = [
        //                 'start' => $start,
        //                 'end'   => $end,
        //                 ];
        //         }
        // }

        $this->roomAvailability[$roomId] = $merged;

        // ===============================
        // 3. sắp xếp theo $sort
        // ===============================
        if (! empty($this->roomAvailability[$roomId])) {

            $this->roomAvailability[$roomId] = collect($this->roomAvailability[$roomId])
                ->sortBy('start', SORT_REGULAR, $sort === 'desc')
                ->values()
                ->toArray();
        }
    }


    protected function loadOffDate(string $sort)
    {

        $this->offDate = [];

        if (! empty($this->selectedDates) && is_array($this->selectedDates)) {

            // 2.1 Parse + sort ngày (chỉ lấy date)
            $dates = collect($this->selectedDates)
                ->map(fn($d) => Carbon::parse($d)->startOfDay())
                ->sort()
                ->values();

            $ranges = [];

            $currentStart = null;

            $currentEnd = null;

            $prevDate = null;

            // 2.2 duyệt từng ngày
            foreach ($dates as $date) {

                // Quy ước off: 06:00 hôm nay -> 06:00 hôm sau
                $start = $date->copy()->setTime(6, 0, 0);

                $end = $date->copy()->addDay()->setTime(6, 0, 0);

                // khoảng đầu tiên
                if ($currentStart === null) {

                    $currentStart = $start;

                    $currentEnd = $end;

                    $prevDate = $date;

                    continue;
                }

                // ✅ điều kiện gộp chuẩn: ngày hiện tại = ngày trước + 1
                if ($date->equalTo($prevDate->copy()->addDay())) {

                    // Kéo dài end
                    $currentEnd = $end;
                } else {

                    // Lưu khoảng cũ
                    $ranges[] = [
                        'start' => $currentStart,
                        'end' => $currentEnd,
                    ];

                    // Bắt đầu khoảng mới
                    $currentStart = $start;

                    $currentEnd = $end;
                }

                $prevDate = $date;
            }

            // 2.3 push khoảng cuối cùng
            if ($currentStart !== null) {

                $ranges[] = [
                    'start' => $currentStart,
                    'end' => $currentEnd,
                ];
            }

            $this->offDate = $ranges;
        }

        if (! empty($this->offDate)) {

            $this->offDate = collect($this->offDate)
                ->sortBy('start', SORT_REGULAR, $sort === 'desc')
                ->values()
                ->toArray();
        }
    }


    protected function findEarliestSlot2($roomId, $Earliest, $intervalTime, $C2_time_minutes, $requireTank = 0, $requireAHU = 0, $stage_plan_table = 'stage_plan', $maxTank = 1, $tankInterval = 60)
    {

        $this->loadRoomAvailability('asc', $roomId);

        if (! isset($this->roomAvailability[$roomId])) {

            $this->roomAvailability[$roomId] = [];
        }

        $busyList = $this->roomAvailability[$roomId];

        $offDateList = $this->offDate ?? [];

        $current_start = Carbon::parse($Earliest);

        $current_start = $this->skipOffTime($current_start, $offDateList);

        // =========================================================
        foreach ($busyList as $busy) {

            // ==== xét gap trước busy ====
            if ($current_start->lt($busy['start'])) {

                $gap = $current_start->diffInMinutes($busy['start']);

                $need = $intervalTime + $C2_time_minutes;

                // ---- tính offTime kiểu expand ----
                $offTime = 0;

                do {

                    $current_end = $current_start->copy()->addMinutes($need + $offTime);

                    $newOffTime = 0;

                    foreach ($offDateList as $off) {

                        if ($off['end'] <= $current_start || $off['start'] >= $current_end) {

                            continue;
                        }

                        $overlapStart = $off['start']->greaterThan($current_start)
                            ? $off['start']
                            : $current_start;

                        $overlapEnd = $off['end']->lessThan($current_end)
                            ? $off['end']
                            : $current_end;

                        $newOffTime += $overlapStart->diffInMinutes($overlapEnd);
                    }

                    $changed = ($newOffTime > $offTime);

                    $offTime = $newOffTime;
                } while ($changed);

                if ($gap >= $need + $offTime) {

                    return $current_start->copy();
                }
            }

            // ==== nếu rơi vào busy → nhảy qua ====
            if ($current_start->lt($busy['end'])) {

                $current_start = $busy['end']->copy();

                $current_start = $this->skipOffTime($current_start, $offDateList);
            }
        }

        // ==== sau tất cả busy ====
        return $current_start->copy();
    }


    public function scheduleWeightStage(?Carbon $start_date = null)
    {

        $start_date = $start_date ?? now();

        $tasks = DB::table('stage_plan as sp')
            ->select(
                'sp.id',
                'sp.plan_master_id',
                'sp.product_caterogy_id',
                'sp.predecessor_code',
                'sp.nextcessor_code',
                'sp.campaign_code',
                'sp.code',
                'sp.stage_code',
                'sp.campaign_code',
                'sp.tank',
                'sp.keep_dry',
                'sp.order_by',
                'sp.required_room_code',
                'sp.immediately',

                'plan_master.batch',
                'plan_master.is_val',
                'plan_master.code_val',
                'plan_master.expected_date',
                'plan_master.batch',

                'plan_master.after_weigth_date',
                'plan_master.after_parkaging_date',
                'plan_master.allow_weight_before_date',

                'finished_product_category.product_name_id',
                'finished_product_category.market_id',
                'finished_product_category.finished_product_code',
                'finished_product_category.intermediate_code',
                'product_name.name',
                'market.code as market',

                'next.start as next_start',

            )
            ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
            ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
            ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
            ->leftJoin('stage_plan as next', 'next.code', '=', 'sp.nextcessor_code')
            ->where('sp.active', 1)
            ->where('sp.not_schedule', 0)
            ->where('next.active', 1)
            ->whereIn('sp.stage_code', [1,  2])
            ->whereNull('sp.start')
            ->where('sp.finished', 0)
            ->where('next.finished', 0)
            ->where('next.start', '>', now())
            ->whereNotNull('plan_master.after_weigth_date')
            ->where('sp.deparment_code', session('user.production_code'))
            ->orderBy('next.start', 'asc')
            ->get();

        $this->processed_stage_code_Id = [];

        // $processedcampaigns = [];
        foreach ($tasks as $task) {

            if ($task->campaign_code === null) {

                $this->scheduleweight($task, 0, false, $start_date);
            } else {

                // if (in_array($task->campaign_code . $task->stage_code , $processedcampaigns)) {continue;}
                if (in_array($task->id, $this->processed_stage_code_Id)) {

                    continue;
                }

                $campaignTasks = $tasks->where('campaign_code', $task->campaign_code)->whereNotIn('id', $this->processed_stage_code_Id)->where('stage_code', $task->stage_code)->sortBy('batch');

                $this->scheduleweight($campaignTasks, 0, true, $start_date);

                // $processedCampaigns[] = $task->campaign_code . $task->stage_code;
            }
        }
    }


    protected function sheduleNotCampaing($task, $stageCode, int $waite_time = 0, ?Carbon $start_date = null, ?string $Line = null)
    {

        $now = Carbon::now();

        $minute = $now->minute;

        $roundedMinute = ceil($minute / 15) * 15;

        if ($roundedMinute == 60) {

            $now->addHour();

            $roundedMinute = 0;
        }

        $now->minute($roundedMinute)->second(0)->microsecond(0);

        // Gom tất cả candidate time vào 1 mảng
        $candidates[] = $now;

        $candidates[] = $start_date;

        // nếu có after_weigth_date
        if ($stageCode <= 6) {

            if (! empty($task->after_weigth_date)) {

                $candidates[] = Carbon::parse($task->after_weigth_date);
            }

            if (! empty($task->allow_weight_before_date)) {

                $candidates[] = Carbon::parse($task->allow_weight_before_date);
            }
        } else {

            if (! empty($task->after_parkaging_date)) {

                $candidates[] = Carbon::parse($task->after_parkaging_date);
            }
        }

        if ($task->predecessor_code != null) {

            $pred = DB::table('stage_plan')
                ->where('code', $task->predecessor_code)->first();

            if ($pred) {

                $candidates[] = Carbon::parse($pred->end)->addMinutes($waite_time);
            }
        }

        // Lấy max
        $earliestStart = collect($candidates)->max();

        // chọn phòng sx
        if ($task->required_room_code != null || $Line != null) {

            if ($task->required_room_code != null) {

                $room_code = $task->required_room_code;
            } else {

                $room_code = $Line;
            }

            $room_id = DB::table('room')->where('code', $room_code)->value('id');

            $rooms = DB::table('quota')->select(
                'room_id',
                DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
            )
                ->when($task->stage_code <= 6, function ($query) use ($task) {

                    return $query->where('intermediate_code', $task->intermediate_code);
                }, function ($query) use ($task) {

                    return $query->where('finished_product_code', $task->finished_product_code)
                        ->where('intermediate_code', $task->intermediate_code);
                })
                ->where('room_id', $room_id)
                ->get();
        } else {

            if ($task->code_val !== null && $task->stage_code == 3 && isset($parts[1]) && $parts[1] > 1) {

                $code_val_first = $parts[0] . '_1';

                $room_id_first = DB::table('stage_plan as sp')
                    ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                    ->where('code_val', $code_val_first)
                    ->where('stage_code', $task->stage_code)
                    ->first();

                if ($room_id_first) {

                    $rooms = DB::table('quota')
                        ->select(
                            'room_id',
                            DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                            DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                            DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                            DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                        )
                        ->when($task->stage_code <= 6, function ($query) use ($task) {

                            return $query->where('intermediate_code', $task->intermediate_code);
                        }, function ($query) use ($task) {

                            return $query->where('finished_product_code', $task->finished_product_code);
                        })
                        ->where('active', 1)
                        ->where('room_id', $room_id_first->resourceId)
                        ->get();
                } else {

                    $rooms = DB::table('quota')->select(
                        'room_id',
                        DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                        DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                        DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                        DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                    )
                        ->when($task->stage_code <= 6, function ($query) use ($task) {

                            return $query->where('intermediate_code', $task->intermediate_code);
                        }, function ($query) use ($task) {

                            return $query->where('finished_product_code', $task->finished_product_code);
                        })
                        ->where('active', 1)
                        ->where('stage_code', $task->stage_code)
                        ->get();
                }
            } elseif ($task->code_val !== null && $task->stage_code > 3 && isset($parts[1]) && $parts[1] > 1) {

                $code_val_first = $parts[0];

                $room_id_first = DB::table('stage_plan as sp')
                    ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                    ->where(DB::raw("SUBSTRING_INDEX(pm.code_val, '_', 1)"), '=', $parts[0])
                    ->where('sp.stage_code', $task->stage_code)
                    ->whereNotNull('start')
                    ->get();

                if ($room_id_first) {

                    $rooms = DB::table('quota')
                        ->select(
                            'room_id',
                            DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                            DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                            DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                            DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                        )
                        ->when($task->stage_code <= 6, function ($query) use ($task) {

                            return $query->where('intermediate_code', $task->intermediate_code);
                        }, function ($query) use ($task) {

                            return $query->where('finished_product_code', $task->finished_product_code);
                        })
                        ->where('active', 1)
                        ->where('stage_code', $task->stage_code)
                        ->get();

                    if ($rooms->count() > $room_id_first->count()) {

                        foreach ($room_id_first as $first) {

                            $rooms->where('room_id', '!=', $first->resourceId);
                        }
                    }
                } else {

                    $rooms = DB::table('quota')->select(
                        'room_id',
                        DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                        DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                        DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                        DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                    )
                        ->when($task->stage_code <= 6, function ($query) use ($task) {

                            return $query->where('intermediate_code', $task->intermediate_code);
                        }, function ($query) use ($task) {

                            return $query->where('finished_product_code', $task->finished_product_code);
                        })
                        ->where('active', 1)
                        ->where('stage_code', $task->stage_code)
                        ->get();
                }
            } else {

                $rooms = DB::table('quota')->select(
                    'room_id',
                    DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                    DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                    DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                    DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                )
                    ->when($task->stage_code <= 6, function ($query) use ($task) {

                        return $query->where('intermediate_code', $task->intermediate_code);
                    }, function ($query) use ($task) {

                        return $query->where('finished_product_code', $task->finished_product_code);
                    })
                    ->where('active', 1)
                    ->where('stage_code', $task->stage_code)
                    ->get();
            }
        }

        // phòng phù hợp (quota)
        if ($rooms->isEmpty()) {
            return;
        }

        $bestRoom = null;
        $bestStart = null;

        // tim phòng tối ưu
        $ratio = 1;

        if ($stageCode == 7) {
            $pm = DB::table('plan_master')
                ->where('id', $task->plan_master_id)
                ->select('only_parkaging', 'percent_parkaging')
                ->first();

            if ($pm && $pm->only_parkaging == 1) {
                $ratio = (float) ($pm->percent_parkaging ?? 1);
            }
        }

        foreach ($rooms as $room) {
            $p_adj = (float) $room->p_time_minutes * $ratio;
            $m_adj = (float) $room->m_time_minutes * $ratio;
            $intervalTimeMinutes = $p_adj + $m_adj;

            $C2_time_minutes = (float) $room->C2_time_minutes;

            $candidateStart = $this->findEarliestSlot2(
                $room->room_id,
                $earliestStart,
                $intervalTimeMinutes,
                $C2_time_minutes,
                $task->tank,
                $task->keep_dry,
                'stage_plan',
                2,
                60
            );

            if ($bestStart === null || $candidateStart->lt($bestStart)) {
                $bestRoom = $room->room_id;
                $bestStart = $candidateStart;
                $bestEnd = $bestStart->copy()->addMinutes($intervalTimeMinutes);
                $start_clearning = $bestEnd->copy();
                $end_clearning = $bestStart->copy()->addMinutes($intervalTimeMinutes + $C2_time_minutes);
            }
        }

        if ($bestRoom === null || $bestStart === null) {
            return;
        }

        $bestStart = $this->skipOffTime($bestStart, $this->offDate, $bestRoom);

        // Re-fetch bestRoom quota to ensure we have the correct product context
        $bestQuota = DB::table('quota')
            ->where('room_id', $bestRoom)
            ->when($task->stage_code <= 6, function ($query) use ($task) {
                return $query->where('intermediate_code', $task->intermediate_code);
            }, function ($query) use ($task) {
                return $query->where('finished_product_code', $task->finished_product_code)
                    ->where('intermediate_code', $task->intermediate_code);
            })
            ->select(
                DB::raw('(TIME_TO_SEC(p_time)/60) as p_min'),
                DB::raw('(TIME_TO_SEC(m_time)/60) as m_min'),
                DB::raw('(TIME_TO_SEC(C2_time)/60) as c2_min')
            )
            ->first();

        if (! $bestQuota) {
            return;
        }

        $finalInterval = (float) ($bestQuota->p_min * $ratio) + (float) ($bestQuota->m_min * $ratio);
        if ($finalInterval < 15) {
            $finalInterval = 15;
        }

        $C2_time_minutes = (float) $bestQuota->c2_min;

        $bestEnd = $this->addWorkingMinutes($bestStart->copy(), (float) $finalInterval, $bestRoom, $this->work_sunday);

        $start_clearning = $bestEnd->copy();

        $end_clearning = $this->addWorkingMinutes($start_clearning->copy(), (float) $C2_time_minutes, $bestRoom, $this->work_sunday);

        $this->saveSchedule(
            1,
            $task->id,
            $bestRoom,
            $bestStart,
            $bestEnd,
            $start_clearning,
            $end_clearning,
            2,
            1,
        );

        // Làm liên tục các công cộng sau
        $nextTasks = collect();

        $next_stage_code = isset($task->nextcessor_code) ? (int) (explode('_', $task->nextcessor_code)[1] ?? 0) : 0;

        if ($task->nextcessor_code && $next_stage_code && $next_stage_code <= $this->max_Step) {
            // && $task->immediately

            $nextTasks = DB::table('stage_plan as sp')
                ->select(
                    'sp.id',
                    'sp.plan_master_id',
                    'sp.product_caterogy_id',
                    'sp.predecessor_code',
                    'sp.nextcessor_code',
                    'sp.campaign_code',
                    'sp.code',
                    'sp.stage_code',
                    'sp.campaign_code',
                    'sp.tank',
                    'sp.keep_dry',
                    'sp.order_by',
                    'sp.required_room_code',
                    'sp.immediately',

                    'plan_master.batch',
                    'plan_master.is_val',
                    'plan_master.code_val',
                    'plan_master.expected_date',
                    'plan_master.batch',

                    'plan_master.after_weigth_date',
                    'plan_master.after_parkaging_date',
                    'plan_master.allow_weight_before_date',

                    'finished_product_category.product_name_id',
                    'finished_product_category.market_id',
                    'finished_product_category.finished_product_code',
                    'finished_product_category.intermediate_code',
                    'product_name.name',
                    'market.code as market'
                )
                ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
                ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                ->where('sp.code', $task->nextcessor_code)
                ->where('sp.finished', 0)
                ->where('sp.active', 1)
                ->when($stageCode == 7, function ($q) {

                    $q->whereNotNull('plan_master.after_parkaging_date');
                })
                ->where('sp.deparment_code', session('user.production_code'))
                ->first();

            if ($nextTasks) {

                $this->sheduleNotCampaing(
                    $nextTasks,
                    $next_stage_code,
                    $waite_time,
                    $bestEnd,
                    null
                );
            }
        }
    }


    protected function scheduleCampaign($campaignTasks, $stageCode, int $waite_time = 0, ?Carbon $start_date = null, ?string $Line = null, ?float $totalTimeCampaign = 0)
    {

        $firstTask = $campaignTasks->first();

        $now = Carbon::now();

        $minute = $now->minute;

        $roundedMinute = ceil($minute / 15) * 15;

        if ($roundedMinute == 60) {

            $now->addHour();

            $roundedMinute = 0;
        }

        $now->minute($roundedMinute)->second(0)->microsecond(0);

        // Gom tất cả candidate time vào 1 mảng
        $candidates[] = $now;

        $candidates[] = $start_date;

        // nếu có after_weigth_date
        if ($stageCode <= 6) {

            if (! empty($firstTask->after_weigth_date)) {

                $candidates[] = Carbon::parse($firstTask->after_weigth_date);
            }

            if (! empty($task->allow_weight_before_date)) {

                $candidates[] = Carbon::parse($firstTask->allow_weight_before_date);
            }
        } else {

            if (! empty($firstTask->after_parkaging_date)) {

                $candidates[] = Carbon::parse($firstTask->after_parkaging_date);
            }
        }

        // $pre_campaign_first_batch_end = [];
        $pre_campaign_codes = [];

        foreach ($campaignTasks as $campaignTask) {

            $pred = DB::table('stage_plan')->where('code', $campaignTask->predecessor_code)->first();

            if ($pred) {

                $code = $pred->campaign_code;

                if (! in_array($code, $pre_campaign_codes) && $code != null) {

                    $pre_campaign_codes[] = $code;

                    $pre_campaign_batch = DB::table('stage_plan')
                        ->where('campaign_code', $code)
                        ->orderBy('start', 'asc')
                        ->get();

                    $pre_campaign_first_batch = $pre_campaign_batch->first();

                    $pre_campaign_last_batch = $pre_campaign_batch->last();

                    $prevCycle = DB::table('quota')
                        ->selectRaw('AVG(TIME_TO_SEC(m_time)/60) as avg_m_time_minutes')
                        ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {

                            return $query->where('intermediate_code', $firstTask->intermediate_code);
                        }, function ($query) use ($firstTask) {

                            return $query->where('finished_product_code', $firstTask->finished_product_code);
                        })
                        ->where('active', 1)
                        ->where('stage_code', $pre_campaign_first_batch->stage_code)
                        ->value('avg_m_time_minutes');

                    $currCycle = DB::table('quota')
                        ->selectRaw('AVG(TIME_TO_SEC(m_time)/60) as avg_m_time_minutes')
                        ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {

                            return $query->where('intermediate_code', $firstTask->intermediate_code);
                        }, function ($query) use ($firstTask) {

                            return $query->where('finished_product_code', $firstTask->finished_product_code);
                        })
                        ->where('active', 1)
                        ->where('stage_code', $campaignTask->stage_code)
                        ->value('avg_m_time_minutes');

                    $maxCount = max($campaignTasks->count(), $pre_campaign_batch->count());

                    if ($currCycle && $currCycle >= $prevCycle) {

                        $candidates[] = Carbon::parse($pred->end)->addMinutes($waite_time);
                    } else {

                        $hasImmediately = collect($campaignTasks)->contains('immediately', 1);

                        if ($campaignTask->immediately == false && $hasImmediately) {

                            $candidates[] = Carbon::parse($pre_campaign_last_batch->end)->subMinutes(($campaignTasks->count() - 1) * $currCycle);

                            $candidates[] = Carbon::parse($pred->end)->addMinutes($waite_time + $maxCount * ($prevCycle - $currCycle));
                        }
                    }
                }

                if ($code == null) {

                    $candidates[] = Carbon::parse($pred->end);
                }
            }
        }

        // Lấy max
        $earliestStart = collect($candidates)->max();

        // phòng phù hợp (quota)
        if ($firstTask->required_room_code != null || $Line != null) {

            if ($firstTask->required_room_code != null) {

                $room_code = $firstTask->required_room_code;
            } else {

                $room_code = $Line;
            }

            $room_id = DB::table('room')->where('code', $room_code)->value('id');

            $rooms = DB::table('quota')->select(
                'room_id',
                DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
            )
                ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {

                    return $query->where('intermediate_code', $firstTask->intermediate_code);
                }, function ($query) use ($firstTask) {

                    return $query->where('finished_product_code', $firstTask->finished_product_code)
                        ->where('intermediate_code', $firstTask->intermediate_code);
                })
                ->where('room_id', $room_id)
                ->get();
        } else {

            if ($firstTask->code_val !== null && $firstTask->stage_code == 3 && isset($parts[1]) && $parts[1] > 1) {

                $code_val_first = $parts[0] . '_1';

                $room_id_first = DB::table('stage_plan as sp')
                    ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                    ->where('code_val', $code_val_first)
                    ->where('stage_code', $firstTask->stage_code)
                    ->first();

                if ($room_id_first) {

                    $rooms = DB::table('quota')
                        ->select(
                            'room_id',
                            DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                            DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                            DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                            DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                        )
                        ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {

                            return $query->where('intermediate_code', $firstTask->intermediate_code);
                        }, function ($query) use ($firstTask) {

                            return $query->where('finished_product_code', $firstTask->finished_product_code);
                        })
                        ->where('active', 1)
                        ->where('room_id', $room_id_first->resourceId)
                        ->get();
                } else {

                    $rooms = DB::table('quota')->select(
                        'room_id',
                        DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                        DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                        DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                        DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                    )
                        ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {

                            return $query->where('intermediate_code', $firstTask->intermediate_code);
                        }, function ($query) use ($firstTask) {

                            return $query->where('finished_product_code', $firstTask->finished_product_code);
                        })
                        ->where('active', 1)
                        ->where('stage_code', $firstTask->stage_code)
                        ->get();
                }
            } elseif ($firstTask->code_val !== null && $firstTask->stage_code > 3 && isset($parts[1]) && $parts[1] > 1) {

                $code_val_first = $parts[0];

                $room_id_first = DB::table('stage_plan as sp')
                    ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                    ->where(DB::raw("SUBSTRING_INDEX(pm.code_val, '_', 1)"), '=', $parts[0])
                    ->where('sp.stage_code', $firstTask->stage_code)
                    ->whereNotNull('start')
                    ->get();

                if ($room_id_first) {

                    $rooms = DB::table('quota')
                        ->select(
                            'room_id',
                            DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                            DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                            DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                            DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                        )
                        ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {

                            return $query->where('intermediate_code', $firstTask->intermediate_code);
                        }, function ($query) use ($firstTask) {

                            return $query->where('finished_product_code', $firstTask->finished_product_code);
                        })
                        ->where('active', 1)
                        ->where('stage_code', $firstTask->stage_code)
                        ->get();

                    if ($rooms->count() > $room_id_first->count()) {

                        foreach ($room_id_first as $first) {

                            $rooms->where('room_id', '!=', $first->resourceId);
                        }
                    }
                } else {

                    $rooms = DB::table('quota')->select(
                        'room_id',
                        DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                        DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                        DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                        DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                    )
                        ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {

                            return $query->where('intermediate_code', $firstTask->intermediate_code);
                        }, function ($query) use ($firstTask) {

                            return $query->where('finished_product_code', $firstTask->finished_product_code);
                        })
                        ->where('active', 1)
                        ->where('stage_code', $firstTask->stage_code)
                        ->get();
                }
            } else {

                $rooms = DB::table('quota')->select(
                    'room_id',
                    DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                    DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                    DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                    DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                )
                    ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {

                        return $query->where('intermediate_code', $firstTask->intermediate_code);
                    }, function ($query) use ($firstTask) {

                        return $query->where('finished_product_code', $firstTask->finished_product_code)
                            ->where('intermediate_code', $firstTask->intermediate_code);
                    })
                    ->where('active', 1)
                    ->where('stage_code', $firstTask->stage_code)
                    ->get();
            }
        }

        if (! $rooms) {
            return;
        }

        // liên hê giữa pc và tht
        if ($stageCode == 4 && $firstTask->predecessor_code && explode('_', $firstTask->predecessor_code)[1] == 3 && $rooms->count() > 1) {

            $rooms_bkc = $rooms;

            $resourceId_prev = DB::table('stage_plan')
                ->where('code', $firstTask->predecessor_code)
                ->value('resourceId');

            $rooms = $rooms->filter(function ($room) use ($resourceId_prev) {

                if (in_array($resourceId_prev, [6,  7])) {

                    return in_array($room->room_id, [13,  14]);
                }

                if ($resourceId_prev == 10) {

                    return $room->room_id == 17;
                }

                return true;
            })->values();

            // ✅ rollback nếu filter làm rỗng
            if ($rooms->isEmpty()) {

                $rooms = $rooms_bkc;
            }
        }

        $bestRoom = null;

        $bestStart = null;

        // tim phòng tối ưu
        $campaign_ratio = 1;
        if ($stageCode == 7) {
            $cpm = DB::table('plan_master')->where('id', $firstTask->plan_master_id)->select('only_parkaging', 'percent_parkaging')->first();
            if ($cpm && $cpm->only_parkaging == 1) {
                $campaign_ratio = (float) ($cpm->percent_parkaging ?? 100) / 100;
            }
        }

        foreach ($rooms as $room) {
            $p_adj = (float) $room->p_time_minutes * $campaign_ratio;
            $m_adj = (float) $room->m_time_minutes * $campaign_ratio;

            $totalMunites = $p_adj + ($campaignTasks->count() * $m_adj)
                + ($campaignTasks->count() - 1) * ($room->C1_time_minutes)
                + $room->C2_time_minutes;

            if ($totalTimeCampaign > 0 && $totalTimeCampaign > $totalMunites) {
                $totalMunites = $totalTimeCampaign;
            }

            $candidateStart = $this->findEarliestSlot2(
                $room->room_id,
                $earliestStart,
                $totalMunites,
                0,
                $firstTask->tank,
                $firstTask->keep_dry,
                'stage_plan',
                2,
                60
            );

            if ($bestStart === null || $candidateStart->lt($bestStart)) {
                $bestRoom = $room;
                $bestStart = $candidateStart;
            }
        }

        if ($bestRoom === null || $bestStart === null) {
            return;
        }

        // Lưu từng batch
        $counter = 1;

        // Lưu Sự Kiện
        $firstBatachStart = null;
        $lastBatachEnd = null;

        foreach ($campaignTasks as $task) {

            $pred_end = DB::table('stage_plan')->where('code', $task->predecessor_code)->value('end');

            if (isset($pred_end) && $pred_end != null) {
                $p_end = Carbon::parse($pred_end);
                if ($p_end->gt($bestStart)) {
                    $bestStart = $p_end;
                }
            }

            $bestStart = $this->skipOffTime($bestStart, $this->offDate, $bestRoom->room_id);

            // Tỉ lệ theo từng batch
            $task_ratio = 1;
            if ($stageCode == 7) {
                $tpm = DB::table('plan_master')->where('id', $task->plan_master_id)->select('only_parkaging', 'percent_parkaging')->first();
                if ($tpm && $tpm->only_parkaging == 1) {
                    $task_ratio = (float) ($tpm->percent_parkaging ?? 100) / 100;
                }
            }

            $p_task_adj = (float) $bestRoom->p_time_minutes * $task_ratio;
            $m_task_adj = (float) $bestRoom->m_time_minutes * $task_ratio;

            if ($counter == 1) {
                $duration = $p_task_adj + $m_task_adj;
                if ($duration < 15) {
                    $duration = 15;
                }

                $bestEnd = $this->addWorkingMinutes($bestStart->copy(), $duration, $bestRoom->room_id, $this->work_sunday);

                $start_clearning = $bestEnd->copy();

                if ($campaignTasks->count() == 1) {
                    $bestEndCleaning = $this->addWorkingMinutes($start_clearning->copy(), (float) $bestRoom->C2_time_minutes, $bestRoom->room_id, $this->work_sunday);
                    $clearningType = 2;
                    $lastBatachEnd = $bestEndCleaning->copy();
                } else {
                    $bestEndCleaning = $this->addWorkingMinutes($start_clearning->copy(), (float) $bestRoom->C1_time_minutes, $bestRoom->room_id, $this->work_sunday);
                    $clearningType = 1;
                }

                $firstBatachStart = $bestStart->copy();
                $first_in_campaign = 1;
            } elseif ($counter == $campaignTasks->count()) {
                $duration = $m_task_adj;
                if ($duration < 15) {
                    $duration = 15;
                }

                $bestEnd = $this->addWorkingMinutes($bestStart->copy(), $duration, $bestRoom->room_id, $this->work_sunday);
                $start_clearning = $bestEnd->copy();
                $bestEndCleaning = $this->addWorkingMinutes($start_clearning->copy(), (float) $bestRoom->C2_time_minutes, $bestRoom->room_id, $this->work_sunday);

                $clearningType = 2;
                $lastBatachEnd = $bestEndCleaning->copy();
                $first_in_campaign = 0;
            } else {
                $duration = $m_task_adj;
                if ($duration < 15) {
                    $duration = 15;
                }

                $bestEnd = $this->addWorkingMinutes($bestStart->copy(), $duration, $bestRoom->room_id, $this->work_sunday);
                $start_clearning = $bestEnd->copy();
                $bestEndCleaning = $this->addWorkingMinutes($start_clearning->copy(), (float) $bestRoom->C1_time_minutes, $bestRoom->room_id, $this->work_sunday);

                $clearningType = 1;
                $first_in_campaign = 0;
            }

            $this->saveSchedule(
                $first_in_campaign,
                $task->id,
                $bestRoom->room_id,
                $bestStart,
                $bestEnd,
                $start_clearning,
                $bestEndCleaning,
                $clearningType,
                1,
            );

            $counter++;
            $bestStart = $bestEndCleaning->copy();
        }

        if ($firstBatachStart && $lastBatachEnd) {
            $totalTimeCampaign = abs($firstBatachStart->diffInMinutes($lastBatachEnd));
        }

        // Làm liên tục các công cộng sau
        $nextcessor_codes = collect();

        $nextTasks = collect();

        $next_stage_code = isset($firstTask->nextcessor_code)
            ? (int) (explode('_', $firstTask->nextcessor_code)[1] ?? 0)
            : 0;

        $hasImmediately = true;

        collect($campaignTasks)->contains('immediately', 1);

        if ($next_stage_code <= $this->max_Step && $hasImmediately) {

            $nextcessor_codes = $campaignTasks->pluck('nextcessor_code');

            $nextTasks = DB::table('stage_plan as sp')
                ->select(
                    'sp.id',
                    'sp.plan_master_id',
                    'sp.product_caterogy_id',
                    'sp.predecessor_code',
                    'sp.nextcessor_code',
                    'sp.campaign_code',
                    'sp.code',
                    'sp.stage_code',
                    'sp.campaign_code',
                    'sp.tank',
                    'sp.keep_dry',
                    'sp.order_by',
                    'sp.required_room_code',
                    'sp.immediately',
                    'plan_master.batch',
                    'plan_master.is_val',
                    'plan_master.code_val',
                    'plan_master.expected_date',
                    'plan_master.batch',
                    'plan_master.after_weigth_date',
                    'plan_master.after_parkaging_date',
                    'finished_product_category.product_name_id',
                    'finished_product_category.market_id',
                    'finished_product_category.finished_product_code',
                    'finished_product_category.intermediate_code',
                    'product_name.name',
                    'market.code as market',
                    'prev.start as prev_start'
                )
                ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
                ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                ->leftJoin('stage_plan as prev', 'prev.code', '=', 'sp.predecessor_code')
                ->whereIn('sp.code', $nextcessor_codes)
                // ->where('sp.stage_code', $nextcessor_code)
                ->where('sp.active', 1)
                // ->whereNotNull('plan_master.after_weigth_date')
                ->where('sp.deparment_code', session('user.production_code'))
                ->orderBy('prev.start', 'asc')
                ->get();

            if ($nextTasks->isNotEmpty()) {

                $this->scheduleCampaign(
                    $nextTasks,
                    $next_stage_code,
                    $waite_time,
                    $start_date,
                    null,
                    $totalTimeCampaign,
                );
            }
        }
    }


    public function addWorkingMinutes(Carbon $start, int $minutes, int $roomId, bool $workSunday = false): Carbon
    {

        $room = DB::table('room')->where('id', $roomId)->first();

        if (! $room) {
            return $start;
        }

        $current = $start->copy();

        $remain = $minutes;

        // ===== Khai báo ca làm việc =====
        $shifts = [];

        if ($room->sheet_regular == 1) {

            // Ca hành chánh
            $shifts[] = ['start' => 7,  'end' => 16];
        } else {

            if ($room->sheet_1 == 1) {
                $shifts[] = ['start' => 6,   'end' => 14];
            }

            if ($room->sheet_2 == 1) {
                $shifts[] = ['start' => 14,  'end' => 22];
            }

            if ($room->sheet_3 == 1) {
                $shifts[] = ['start' => 22,  'end' => 30];
            }
            // qua ngày
        }

        if (empty($shifts)) {
            return $current;
        }

        while ($remain > 0) {

            // ===== chủ nhật =====
            if (! $workSunday && $current->isSunday()) {

                $current = $current->addDay()->setTime($shifts[0]['start'] % 24, 0, 0);

                continue;
            }

            $hour = $current->hour + ($current->hour < 6 ? 24 : 0);

            // ===== Tìm ca hiện tại =====
            $currentShift = null;

            foreach ($shifts as $shift) {

                if ($hour >= $shift['start'] && $hour < $shift['end']) {

                    $currentShift = $shift;

                    break;
                }
            }

            // ===== ngoài ca → nhảy ca kế =====
            if (! $currentShift) {

                $jumped = false;

                foreach ($shifts as $shift) {

                    if ($hour < $shift['start']) {

                        $current = $current->setTime($shift['start'] % 24, 0, 0);

                        $jumped = true;

                        break;
                    }
                }

                if (! $jumped) {

                    $current = $current->addDay()
                        ->setTime($shifts[0]['start'] % 24, 0, 0);
                }

                continue;
            }

            // ===== Trong ca =====
            $endOfShift = $current->copy()->setTime(
                $currentShift['end'] % 24,
                0,
                0
            );

            if ($currentShift['end'] >= 24) {

                $endOfShift->addDay();
            }

            $canWork = $current->diffInMinutes($endOfShift);

            // ===== làm chưa hết ca =====
            if ($remain <= $canWork) {

                return $current->addMinutes($remain);
            }

            // ===== Làm hết ca =====
            $remain -= $canWork;

            $current = $endOfShift;
        }

        return $current;
    }


    protected function findLatestSlot(
        $roomId,
        $latestEnd,
        $beforeIntervalMinutes,
        $afterIntervalMinutes,
        $time_clearning_tank = 60,

        ?Carbon $start_date = null,
        bool $requireTank = false,
        bool $requireAHU = false,
        int $maxTank = 2,
        string $stage_plan_table = 'stage_plan'
    ) {

        $this->loadRoomAvailability('desc', $roomId);

        $start_date = $start_date ?? Carbon::now();

        $AHU_group = DB::table('room')->where('id', $roomId)->value('AHU_group');

        if (! isset($this->roomAvailability[$roomId])) {

            $this->roomAvailability[$roomId] = [];
        }

        $busyList = $this->roomAvailability[$roomId];
        // collect($this->roomAvailability[$roomId])->sortByDesc('end');
        $current_end_clearning = Carbon::parse($latestEnd)->copy()->addMinutes($afterIntervalMinutes);

        $tryCount = 0;

        while (true) {

            foreach ($busyList as $busy) {

                // nếu current nằm sau block bận
                if ($current_end_clearning->gt($busy['end'])) {

                    $gap = $current_end_clearning->diffInMinutes($busy['end']);

                    if ($gap >= ($beforeIntervalMinutes + $afterIntervalMinutes)) {

                        // kiểm tra tank nếu cần
                        if ($requireTank == true) {

                            $bestEnd = $current_end_clearning->copy()->subMinutes($afterIntervalMinutes);

                            $bestStart = $bestEnd->copy()->subMinutes($beforeIntervalMinutes);

                            $overlapTankCount = DB::table($stage_plan_table)
                                ->whereNotNull('start')
                                ->where('tank', 1)
                                ->where('stage_code', 3)
                                ->where('start', '<', $bestEnd)
                                ->where('end', '>', $bestStart)
                                ->count();

                            if ($overlapTankCount >= $maxTank) {

                                // Nếu tank đã đầy thì lùi thêm 15 phút và thử lại
                                $current_end_clearning = $bestStart->copy()->addMinutes($beforeIntervalMinutes + $time_clearning_tank);

                                $tryCount++;

                                if ($tryCount > 100) {
                                    return false;
                                }

                                // tránh vòng lặp vô hạn
                                continue;
                                // quay lại while
                            }
                        }

                        if ($requireAHU == true && $AHU_group == true) {

                            $bestEnd = $current_end_clearning->copy()->subMinutes($afterIntervalMinutes);

                            $bestStart = $bestEnd->copy()->subMinutes($beforeIntervalMinutes);

                            $overlapAHUCount = DB::table($stage_plan_table)
                                ->whereNotNull('start')
                                ->where('stage_code', 7)
                                ->where('keep_dry', 1)
                                ->where('AHU_group', $AHU_group)
                                ->where('start', '<', $bestEnd)
                                ->where('end', '>', $bestStart)
                                ->count();

                            if ($overlapAHUCount >= 3) {

                                $current_end_clearning = $bestStart
                                    ->copy()
                                    ->addMinutes($beforeIntervalMinutes);

                                $tryCount++;

                                if ($tryCount > 100) {
                                    return false;
                                }

                                // tránh vòng lặp vô hạn
                                continue;
                                // quay lại vòng while
                            }
                        }

                        return $current_end_clearning;
                    }
                }

                // nếu current rơi vào block bận
                if ($current_end_clearning->gt($busy['start'])) {

                    $current_end_clearning = $busy['start']->copy();
                }
            }

            if (($current_end_clearning->copy()->subMinutes($beforeIntervalMinutes + $afterIntervalMinutes))->lt($start_date)) {

                return false;
            }

            // kiểm tra tank ở vị trí cuối cùng (ngoài busylist)
            if ($requireTank == true) {

                $bestEnd = $current_end_clearning->copy()->subMinutes($afterIntervalMinutes);

                $bestStart = $bestEnd->copy()->subMinutes($beforeIntervalMinutes);

                $overlapTankCount = DB::table($stage_plan_table)
                    ->whereNotNull('start')
                    ->where('tank', 1)
                    ->where('stage_code', 3)
                    ->where('start', '<', $bestEnd)
                    ->where('end', '>', $bestStart)
                    ->count();

                if ($overlapTankCount >= $maxTank) {

                    // $current_end_clearning = $bestStart->copy()->subMinutes(15);
                    $current_end_clearning = $bestStart->copy()->addMinutes($beforeIntervalMinutes + $time_clearning_tank);

                    $tryCount++;

                    if ($tryCount > 100) {
                        return false;
                    }

                    continue;
                    // thử lại
                }
            }

            if ($requireAHU == true && $AHU_group == true) {

                $bestEnd = $current_end_clearning->copy()->subMinutes($afterIntervalMinutes);

                $bestStart = $bestEnd->copy()->subMinutes($beforeIntervalMinutes);

                $overlapAHUCount = DB::table($stage_plan_table)
                    ->whereNotNull('start')
                    ->where('stage_code', 7)
                    ->where('keep_dry', 1)
                    ->where('AHU_group', $AHU_group)
                    ->where('start', '<', $bestEnd)
                    ->where('end', '>', $bestStart)
                    ->count();

                if ($overlapAHUCount >= $maxTank) {

                    // $current_end_clearning = $bestStart->copy()->subMinutes(15);
                    $current_end_clearning = $bestStart->copy()->addMinutes($beforeIntervalMinutes);

                    $tryCount++;

                    if ($tryCount > 100) {
                        return false;
                    }

                    continue;
                    // thử lại
                }
            }

            return $current_end_clearning;
        }
    }


    protected function syncPackagingDate($stagePlanId, $date, $type)
    {
        $plan = DB::table('stage_plan')->where('id', $stagePlanId)->first(['received', 'received_second_packaging']);
        if ($plan) {
            if ($type == 0 && $plan->received == 1) {
                return;
            }
            if ($type == 1 && $plan->received_second_packaging == 1) {
                return;
            }
        }

        $latest = DB::table('packaging_issuance_date')
            ->where('stage_plane_id', $stagePlanId)
            ->where('type_packaging', $type)
            ->orderBy('ver', 'desc')
            ->first();

        if (! $latest || $latest->receive_packaging_date != $date) {
            DB::table('packaging_issuance_date')->insert([
                'stage_plane_id' => $stagePlanId,
                'type_packaging' => $type,
                'receive_packaging_date' => $date,
                'ver' => ($latest->ver ?? 0) + 1,
                'created_at' => now(),
                'created_by' => session('user')['fullName'] ?? 'System',
            ]);
        }
    }


    protected function realSaveSchedule($first_in_campaign, $stageId, $roomId, $start, $end, $start_clearning, $endCleaning, string $cleaningType, bool $direction)
    {

        DB::transaction(function () use ($first_in_campaign, $stageId, $roomId, $start, $end, $start_clearning, $endCleaning, $cleaningType, $direction) {

            if ($cleaningType == 2) {

                $titleCleaning = 'VS-II';
            } else {

                $titleCleaning = 'VS-I';
            }

            $AHU_group = DB::table('room')->where('id', $roomId)->value('AHU_group') ?? 0;

            $code = DB::table('stage_plan')->where('id', $stageId)->value('code');

            $offDays = DB::table('off_days')
                ->whereDate('off_date', '<=', $start)
                ->pluck('off_date')
                ->toArray();

            $receiveDate = Carbon::parse($start)->subDay();

            while (in_array($receiveDate->toDateString(), $offDays)) {

                $receiveDate->subDay();
            }

            $receiveDate = $receiveDate->toDateString();

            DB::table('stage_plan')
                ->where('id', $stageId)
                ->update([
                    'first_in_campaign' => $first_in_campaign ?? 0,
                    'resourceId' => $roomId,
                    'start' => $start,
                    'end' => $end,
                    'start_clearning' => $start_clearning,
                    'end_clearning' => $endCleaning,
                    'title_clearning' => $titleCleaning,
                    'scheduling_direction' => $direction,
                    'AHU_group' => $AHU_group ?? null,
                    'schedualed_at' => now(),
                    'receive_packaging_date' => DB::raw("CASE WHEN received = 0 THEN '$receiveDate' ELSE receive_packaging_date END"),
                    'receive_second_packaging_date' => DB::raw("CASE WHEN received_second_packaging = 0 THEN '$receiveDate' ELSE receive_second_packaging_date END"),
                ]);

            $submit = DB::table('stage_plan')->where('id', $stageId)->value('submit');

            // nếu muốn log cả cleaning vào room_schedule thì thêm block này:
            if ($submit == 1) {

                $this->syncPackagingDate($stageId, $receiveDate, 0);
                $this->syncPackagingDate($stageId, $receiveDate, 1);

                DB::table('stage_plan_history')
                    ->insert([
                        'stage_plan_id' => $stageId,
                        'version' => (DB::table('stage_plan_history')->where('stage_plan_id', $stageId)->max('version') ?? 0) + 1,
                        'start' => $start,
                        'end' => $end,
                        'start_clearning' => $start_clearning,
                        'end_clearning' => $endCleaning,
                        'resourceId' => $roomId,
                        'schedualed_by' => session('user')['fullName'],
                        'schedualed_at' => now(),
                        'deparment_code' => session('user.production_code'),
                        'type_of_change' => $this->reason ?? 'Lập Lịch Tự Động',
                    ]);
            }
        });
    }
}

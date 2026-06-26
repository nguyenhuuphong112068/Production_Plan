<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SchedualController extends Controller
{
    protected $roomAvailability = [];
    protected $moldSchedules = [];

    protected function loadMoldSchedules(array $moldIds)
    {
        $toLoad = array_diff($moldIds, array_keys($this->moldSchedules));
        if (!empty($toLoad)) {
            $schedules = DB::table('stage_plan')
                ->where('stage_code', 7)
                ->whereIn('blister_mold_id', $toLoad)
                ->where('active', 1)
                ->where('finished', 0)
                ->whereNotNull('start')
                ->whereNotNull('resourceId')
                ->get(['blister_mold_id', 'start', 'end', 'resourceId']);

            foreach ($toLoad as $id) {
                $this->moldSchedules[$id] = [];
            }
            foreach ($schedules as $s) {
                $this->moldSchedules[$s->blister_mold_id][] = [
                    'start' => Carbon::parse($s->start),
                    'end' => Carbon::parse($s->end),
                    'resourceId' => $s->resourceId
                ];
            }
        }
    }

    protected function checkMoldAvailability($compatibleMolds, Carbon $start, Carbon $end)
    {
        $moldIds = [];
        foreach ($compatibleMolds as $m) {
            $moldIds[] = is_array($m) ? $m['id'] : $m->id;
        }
        $this->loadMoldSchedules($moldIds);

        foreach ($compatibleMolds as $mold) {
            $moldId = is_array($mold) ? $mold['id'] : $mold->id;
            $amount = is_array($mold) ? $mold['amount'] : $mold->amount;

            if (!isset($this->moldSchedules[$moldId])) {
                continue;
            }
            $schedules = $this->moldSchedules[$moldId];
            $concurrentRooms = [];
            foreach ($schedules as $s) {
                if ($s['start']->lt($end) && $s['end']->gt($start)) {
                    $concurrentRooms[$s['resourceId']] = true;
                }
            }
            if (count($concurrentRooms) < $amount) {
                return $moldId;
            }
        }
        return null;
    }

    protected $order_by = 1;

    protected $selectedDates = [];

    // . lưu ngày nghĩ lấy từ fe
    protected $offDate = [];

    // tạo các khoảng  offdate
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

    public function test()
    {
        // $this->Auto_updateDepartment ();

    }


    public function index()
    {

        session()->put(['title' => 'LỊCH SẢN XUẤT']);

        return view('app');
    }

    // thời gian của từng phòng
    public function getRoomStatistics($startDate, $endDate)
    {

        // chuẩn hoá ngày giờ (chuỗi dạng MySQL)
        $start = Carbon::parse($startDate)->format('Y-m-d H:i:s');

        $end = Carbon::parse($endDate)->format('Y-m-d H:i:s');

        $startCarbon = Carbon::parse($start);

        $endCarbon = Carbon::parse($end);

        $totalSeconds = $startCarbon->diffInSeconds($endCarbon);

        if ($totalSeconds <= 0) {

            return collect();
        }

        // Lấy tất cả các bản ghi chồng lấn với khoảng thời gian yêu cầu
        $plans = DB::table('stage_plan as sp')
            ->select('sp.resourceId', 'sp.start', 'sp.end', 'sp.end_clearning')
            ->where('sp.deparment_code', session('user.production_code'))
            ->whereRaw('GREATEST(sp.start, ?) < LEAST(COALESCE(sp.end_clearning, sp.end, sp.start), ?)', [$start,  $end])
            ->get();

        // Nhóm theo resourceId
        $grouped = $plans->groupBy('resourceId');

        $result = $grouped->map(function ($items, $resourceId) use ($start, $end, $totalSeconds) {

            $intervals = [];

            foreach ($items as $item) {

                $e = $item->end_clearning ?? $item->end ?? $item->start;

                $itemStart = max(strtotime($item->start), strtotime($start));

                $itemEnd = min(strtotime($e), strtotime($end));

                if ($itemStart < $itemEnd) {

                    $intervals[] = [
                        'start' => $itemStart,
                        'end' => $itemEnd,
                    ];
                }
            }

            // Thuật toán gộp các khoảng thời gian (Merge Intervals)
            usort($intervals, function ($a, $b) {

                return $a['start'] <=> $b['start'];
            });

            $merged = [];

            if (! empty($intervals)) {

                $current = $intervals[0];

                for ($i = 1; $i < count($intervals); $i++) {

                    if ($intervals[$i]['start'] <= $current['end']) {

                        $current['end'] = max($current['end'], $intervals[$i]['end']);
                    } else {

                        $merged[] = $current;

                        $current = $intervals[$i];
                    }
                }

                $merged[] = $current;
            }

            $busySeconds = 0;

            foreach ($merged as $interval) {

                $busySeconds += ($interval['end'] - $interval['start']);
            }

            $busy_hours = $busySeconds / 3600;

            $total_hours = $totalSeconds / 3600;

            return (object) [
                'resourceId' => $resourceId,
                'total_hours' => $total_hours,
                'busy_hours' => $busy_hours,
                'free_hours' => $total_hours - $busy_hours,
            ];
        })->values();

        return $result;
    }

    // trả về tổngsản lượng lý thuyết
    public function yield($startDate, $endDate, $group_By)
    {

        $startDate = Carbon::parse($startDate);

        $endDate = Carbon::parse($endDate);

        $stage_plan_100 = DB::table('stage_plan as sp')
            ->whereRaw('((sp.start >= ? AND sp.end <= ?))', [$startDate,  $endDate])
            ->whereNotNull('sp.start')
            ->where('sp.deparment_code', session('user.production_code'))
            ->select(
                "sp.$group_By",
                DB::raw('SUM(sp.Theoretical_yields) as total_qty'),
                DB::raw('
                        CASE
                                WHEN sp.stage_code <= 4 THEN "Kg"
                                ELSE "ĐVL"
                        END as unit
                        ')
            )
            ->groupBy("sp.$group_By", 'unit')
            ->get();

        $stage_plan_part = DB::table('stage_plan as sp')
            ->whereRaw('(sp.start < ? AND sp.end > ?) AND NOT (sp.start >= ? AND sp.end <= ?)', [$endDate,  $startDate,  $startDate,  $endDate])
            ->whereNotNull('sp.start')
            ->where('sp.deparment_code', session('user.production_code'))
            ->select(
                "sp.$group_By",
                DB::raw('
                        SUM(
                                sp.Theoretical_yields *
                                TIME_TO_SEC(TIMEDIFF(LEAST(sp.end, "' . $endDate . '"), GREATEST(sp.start, "' . $startDate . '"))) /
                                TIME_TO_SEC(TIMEDIFF(sp.end, sp.start))
                        ) as total_qty
                        '),
                DB::raw('
                        CASE
                                WHEN sp.stage_code <= 4 THEN "Kg"
                                ELSE "ĐVL"
                        END as unit
                        ')
            )
            ->groupBy("sp.$group_By", 'unit')
            ->get();

        $merged = $stage_plan_100->merge($stage_plan_part)
            ->groupBy(function ($item) use ($group_By) {

                return $item->$group_By . '-' . $item->unit;
            })
            ->map(function ($items) use ($group_By) {

                return (object) [
                    $group_By => $items->first()->$group_By,
                    'unit' => $items->first()->unit,
                    'total_qty' => round($items->sum('total_qty'), 2), // 👈 làm tròn 2 chữ số
                ];
            })
            ->values();

        return $merged;
    }

    protected function getEvents($production, $startDate, $endDate, $clearning, int $theory)
    {

        $startDate = Carbon::parse($startDate)->toDateTimeString();

        $endDate = Carbon::parse($endDate)->toDateTimeString();

        $room_code = DB::table('room')->where('deparment_code', $production)->pluck('code', 'id');

        $has_permission_maintenance = user_has_permission(session('user')['userId'], 'plan_maintenance_scheduler', 'boolean');
        $has_permission_production = in_array(session('user')['userGroup'], ['Schedualer',  'Admin',  'Leader']);
        $maxFinishedStage = DB::table('stage_plan')
            ->where('finished', 1)
            ->select(
                'plan_master_id',
                DB::raw('MAX(stage_code) as max_finished_stage')
            )
            ->groupBy('plan_master_id');


        // 2️⃣ Lấy danh sách stage_plan (gộp toàn bộ join)
        $event_plans = DB::table('stage_plan as sp')
            ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
            ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
            ->leftJoin('quota_maintenance', 'plan_master.product_caterogy_id', '=', 'quota_maintenance.id')
            ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', '=', 'intermediate_category.intermediate_code')
            ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
            ->leftJoin('dosage', 'intermediate_category.dosage_id', '=', 'dosage.id')
            ->leftJoinSub($maxFinishedStage, 'sp_max', function ($join) {

                $join->on('sp.plan_master_id', '=', 'sp_max.plan_master_id');
            })
            ->leftJoin('stage_plan as sp_last', function ($join) {

                $join->on('sp.plan_master_id', '=', 'sp_last.plan_master_id')
                    ->on('sp_last.stage_code', '=', 'sp_max.max_finished_stage');
            })
            ->leftJoin('blister_mold', 'sp.blister_mold_id', '=', 'blister_mold.id')

            ->where('sp.active', 1)
            ->whereNotNull('sp.resourceId')
            ->where(function ($query) use ($has_permission_production, $has_permission_maintenance) {
                $query->where(function ($q) use ($has_permission_production) {
                    $q->where('sp.stage_code', '!=', 8);
                    if (!$has_permission_production) {
                        $q->where('sp.submit', 1);
                    }
                })->orWhere(function ($q) use ($has_permission_maintenance) {
                    $q->where('sp.stage_code', '=', 8);
                    if (!$has_permission_maintenance) {
                        $q->where('sp.submit', 1);
                    }
                });
            })
            ->where('sp.deparment_code', $production)
            ->where(function ($q) {

                $q->whereNotNull('sp.start')
                    ->orWhereNotNull('sp.actual_start');
            })
            ->where(function ($q) use ($startDate, $endDate) {

                $q->whereRaw('(sp.start <= ? AND sp.end >= ?)', [$endDate,  $startDate])
                    ->orWhereRaw('(sp.start_clearning <= ? AND sp.end_clearning >= ?)', [$endDate,  $startDate])
                    ->orWhereRaw('(sp.actual_start <= ? AND sp.actual_end >= ?)', [$endDate,  $startDate])
                    ->orWhereRaw('(sp.actual_start_clearning <= ? AND sp.actual_end_clearning >= ?)', [$endDate,  $startDate]);
            })
            ->select(
                'sp.id',
                'sp.code',

                DB::raw("
                                CASE
                                        WHEN sp_max.max_finished_stage IS NULL THEN 'Chưa làm'
                                        WHEN sp_max.max_finished_stage = 1 THEN 'Đã Cân'
                                        WHEN sp_max.max_finished_stage = 3 THEN 'Đã PC'
                                        WHEN sp_max.max_finished_stage = 4 THEN 'Đã THT'
                                        WHEN sp_max.max_finished_stage = 5 THEN 'Đã ĐH'
                                        WHEN sp_max.max_finished_stage = 6 THEN 'Đã BP'
                                        WHEN sp_max.max_finished_stage = 7 THEN 'Hoàn Tất'
                                        ELSE 'Chưa làm'
                                END AS status
                                "),

                DB::raw("
                                CASE
                                        WHEN sp.stage_code >=8 THEN sp.title
                                        ELSE CONCAT(
                                        product_name.name,
                                        '-',
                                        COALESCE(plan_master.actual_batch, plan_master.batch)
                                        )
                                END AS title,
                                product_name.name as product_name,
                                COALESCE(plan_master.actual_batch, plan_master.batch) as batch_name,
                                plan_master.actual_batch as actual_batch
                        "),
                DB::raw("
                                CASE
                                        WHEN sp.stage_code = 2 AND dosage.name LIKE '%phim%' THEN 'Tá dược BP'
                                        WHEN sp.stage_code = 2 AND dosage.name LIKE '%nang%' THEN 'Nang Rỗng'
                                        ELSE NULL
                                END AS w2
                        "),

                'sp.start',
                'sp.end',
                'sp.start_clearning',
                'sp.end_clearning',

                'sp.actual_start',
                'sp.actual_end',
                'sp.actual_start_clearning',
                'sp.actual_end_clearning',

                'sp.first_in_campaign',

                'sp.title_clearning',
                'sp.resourceId',
                'sp.plan_master_id',
                'sp.product_caterogy_id',
                'sp.stage_code',
                'sp.finished',
                'sp.quarantine_time',
                'sp.tank',
                'sp.keep_dry',
                'sp.yields',
                'sp.order_by',
                'sp.clearning_validation',
                'sp.predecessor_code',
                'sp.nextcessor_code',
                'sp.immediately',
                'sp.submit',
                'sp.accept_quarantine',
                'sp.campaign_code',
                'sp.schedualed_by',
                'sp.blister_mold_id',
                'blister_mold.code as blister_mold_code',
                //'quota_maintenance.Inst_sch_type',

                'finished_product_category.intermediate_code',
                'plan_master.expected_date',
                'plan_master.after_weigth_date',
                'plan_master.after_parkaging_date',

                'plan_master.expired_material_date',
                'plan_master.allow_weight_before_date',

                'plan_master.preperation_before_date',
                'plan_master.blending_before_date',
                'plan_master.coating_before_date',

                'plan_master.parkaging_before_date',
                'plan_master.expired_packing_date',
                
                'plan_master.percent_parkaging',

                'plan_master.is_val',
                'plan_master.level',
                'intermediate_category.quarantine_total',

                DB::raw("CASE
                                        WHEN sp.stage_code = 7 THEN 
                                        CONCAT(finished_product_category.intermediate_code, '_', finished_product_category.finished_product_code, '_', sp.resourceId)
                                        ELSE 
                                        CONCAT(finished_product_category.intermediate_code, '_NA_', sp.resourceId)
                                END as process_code
                                "),

                DB::raw('
                                CASE
                                WHEN sp.stage_code IN (1,2) THEN
                                        CASE WHEN intermediate_category.quarantine_time_unit = 1
                                        THEN intermediate_category.quarantine_weight * 24
                                        ELSE intermediate_category.quarantine_weight END
                                WHEN sp.stage_code = 3 THEN
                                        CASE WHEN intermediate_category.quarantine_time_unit = 1
                                        THEN intermediate_category.quarantine_preparing * 24
                                        ELSE intermediate_category.quarantine_preparing END
                                WHEN sp.stage_code = 4 THEN
                                        CASE WHEN intermediate_category.quarantine_time_unit = 1
                                        THEN intermediate_category.quarantine_blending * 24
                                        ELSE intermediate_category.quarantine_blending END
                                WHEN sp.stage_code = 5 THEN
                                        CASE WHEN intermediate_category.quarantine_time_unit = 1
                                        THEN intermediate_category.quarantine_forming * 24
                                        ELSE intermediate_category.quarantine_forming END
                                WHEN sp.stage_code = 6 THEN
                                        CASE WHEN intermediate_category.quarantine_time_unit = 1
                                        THEN intermediate_category.quarantine_coating * 24
                                        ELSE intermediate_category.quarantine_coating END
                                ELSE 0
                                END as quarantine_time_limit_hour')
            )
            ->orderBy('sp.plan_master_id')
            ->orderBy('sp.stage_code')
            ->get();

        // 4️⃣ Gom nhóm theo plan_master_id
        $groupedPlans = $event_plans->groupBy('plan_master_id');

        $events = collect();

        // 5️⃣ duyệt từng nhóm (theo batch sản xuất)
        foreach ($groupedPlans as $plans) {

            $plans = $plans->values();
            // sắp sẵn theo stage_code ở query
            for ($i = 0,  $n = $plans->count(); $i < $n; $i++) {

                $storage_capacity = null;

                $plan = $plans[$i];

                $subtitle = null;
                $violation_colors = [];
                $mold_code = null;

                [$color_event,  $textColor,  $subtitle, $violation_colors, $mold_warning, $mold_code, $v_pre_id, $v_pre_end, $v_suc_id, $v_suc_start] = $this->colorEvent($plan, $plans, $i, $room_code);

                // 🎯 lịch chưa hoàn thành
                if (($plan->start && ! $plan->actual_start && $plan->finished == 0)) {

                    $events->push([
                        'plan_id' => $plan->id,
                        'id' => "{$plan->id}-main",
                        'title' => $plan->title . '-' . $plan->w2,
                        'start' => $plan->start,
                        'end' => $plan->end,
                        'resourceId' => $plan->resourceId,
                        'color' => $plan->finished == 1 ? '#002af9ff' : $color_event,
                        'textColor' => $textColor,
                        'plan_master_id' => $plan->plan_master_id,
                        'stage_code' => $plan->stage_code,
                        'is_clearning' => false,
                        'finished' => $plan->finished,
                        'level' => $plan->level,
                        'process_code' => $plan->process_code,
                        'keep_dry' => $plan->keep_dry,
                        'tank' => $plan->tank,
                        'expected_date' => $plan->expected_date ? Carbon::parse($plan->expected_date)->format('Y-m-d') : null,
                        'submit' => $plan->submit,
                        'storage_capacity' => $storage_capacity,
                        'subtitle' => $subtitle,
                        'violation_colors' => $violation_colors,
                        'violation_predecessor_id' => $v_pre_id,
                        'violation_predecessor_end' => $v_pre_end,
                        'violation_successor_id' => $v_suc_id,
                        'violation_successor_start' => $v_suc_start,
                        'campaign_code' => $plan->campaign_code,
                        'status' => $plan->status,
                        'first_in_campaign' => $plan->first_in_campaign,
                        'product_name' => $plan->product_name,
                        'batch_name' => $plan->batch_name,
                        'actual_batch' => $plan->actual_batch,
                        'code' => $plan->code,
                        'predecessor_code' => $plan->predecessor_code,
                        'schedualed_by' => $plan->schedualed_by,
                        'title_clearning' => $plan->title_clearning,
                        'mold_warning' => $mold_warning,
                        'blister_mold_code' => $plan->blister_mold_code ?? $mold_code,
                    ]);
                }

                // 🎯 lịch đã hoàn thành
                if (($clearning && $plan->start_clearning && ! $plan->actual_start_clearning && $plan->yields >= 0 && $plan->finished == 0) ||
                    ($clearning && $plan->actual_start_clearning && ! $plan->actual_start_clearning && $plan->yields >= 0 && $plan->finished == 0)
                ) {

                    $events->push([
                        'plan_id' => $plan->id,
                        'id' => "{$plan->id}-cleaning",
                        'title' => $plan->title_clearning ?? 'VS',
                        'start' => $plan->actual_start_clearning ?? $plan->start_clearning,
                        'end' => $plan->actual_end_clearning ?? $plan->end_clearning,
                        'resourceId' => $plan->resourceId,
                        'color' => '#a1a2a2ff',
                        'textColor' => $textColor,
                        'plan_master_id' => $plan->plan_master_id,
                        'stage_code' => $plan->stage_code,
                        'is_clearning' => true,
                        'finished' => $plan->finished,
                        'process_code' => $plan->process_code,
                        'campaign_code' => $plan->campaign_code,
                        'product_name' => $plan->product_name,
                        'batch_name' => $plan->batch_name,
                        'actual_batch' => $plan->actual_batch,
                        'code' => $plan->code,
                        'predecessor_code' => $plan->predecessor_code,
                        'expected_date' => $plan->expected_date ? Carbon::parse($plan->expected_date)->format('Y-m-d') : null,
                        'title_clearning' => $plan->title_clearning,
                    ]);
                }

                // 🎯 Lịch bảo trì hoàn thành theo KH (không có actual_start)
                if ($plan->stage_code == 8 && $plan->finished == 1 && $plan->start && !$plan->actual_start) {
                    $events->push([
                        'plan_id' => $plan->id,
                        'id' => "{$plan->id}-main",
                        'title' => $plan->title . ' (X)',
                        'start' => $plan->start,
                        'end' => $plan->end,
                        'code' => $plan->code,
                        'predecessor_code' => $plan->predecessor_code,
                        'resourceId' => $plan->resourceId,
                        'color' => '#aed9f1',
                        'textColor' => '#003A4F',
                        'plan_master_id' => $plan->plan_master_id,
                        'stage_code' => $plan->stage_code,
                        'is_clearning' => false,
                        'status' => $plan->status,
                        'finished' => $plan->finished,
                        'level' => $plan->level,
                        'process_code' => $plan->process_code,
                        'keep_dry' => $plan->keep_dry,
                        'tank' => $plan->tank,
                        'storage_capacity' => $storage_capacity,
                        'campaign_code' => $plan->campaign_code,
                        'product_name' => $plan->product_name,
                        'batch_name' => $plan->batch_name,
                        'actual_batch' => $plan->actual_batch,
                        'expected_date' => $plan->expected_date ? Carbon::parse($plan->expected_date)->format('Y-m-d') : null,
                        'title_clearning' => $plan->title_clearning,
                    ]);
                }

                if ($plan->actual_start && $plan->finished == 1) {

                    if ($theory == 0) {

                        // Lich thực tế
                        $events->push([
                            'plan_id' => $plan->id,
                            'id' => "{$plan->id}-main",
                            'title' => $plan->title,
                            'start' => $plan->actual_start,
                            'end' => $plan->actual_end,
                            'resourceId' => $plan->resourceId,
                            'color' => '#002af9ff',
                            'textColor' => $textColor,
                            'plan_master_id' => $plan->plan_master_id,
                            'stage_code' => $plan->stage_code,
                            'is_clearning' => false,
                            'status' => $plan->status,
                            'finished' => $plan->finished,
                            'level' => $plan->level,
                            'process_code' => $plan->process_code,
                            'keep_dry' => $plan->keep_dry,
                            'tank' => $plan->tank,
                            'storage_capacity' => $storage_capacity,
                            'campaign_code' => $plan->campaign_code,
                            'product_name' => $plan->product_name,
                            'batch_name' => $plan->batch_name,
                            'actual_batch' => $plan->actual_batch,
                            'code' => $plan->code,
                            'predecessor_code' => $plan->predecessor_code,
                            'expected_date' => $plan->expected_date ? Carbon::parse($plan->expected_date)->format('Y-m-d') : null,
                            'mold_warning' => $mold_warning,
                            'blister_mold_code' => $plan->blister_mold_code ?? $mold_code,
                        ]);

                        // event lich vs thực tế
                        if ($clearning && $plan->yields >= 0) {

                            $events->push([
                                'plan_id' => $plan->id,
                                'id' => "{$plan->id}-cleaning",
                                'title' => $plan->title_clearning,
                                'start' => $plan->actual_start_clearning,
                                'end' => $plan->actual_end_clearning,
                                'resourceId' => $plan->resourceId,
                                'color' => '#002af9ff',
                                'textColor' => $textColor,
                                'plan_master_id' => $plan->plan_master_id,
                                'stage_code' => $plan->stage_code,
                                'is_clearning' => true,
                                'finished' => $plan->finished,
                                'process_code' => $plan->process_code,
                                'campaign_code' => $plan->campaign_code,
                                'product_name' => $plan->product_name,
                                'batch_name' => $plan->batch_name,
                                'actual_batch' => $plan->actual_batch,
                                'code' => $plan->code,
                                'predecessor_code' => $plan->predecessor_code,
                                'expected_date' => $plan->expected_date ? Carbon::parse($plan->expected_date)->format('Y-m-d') : null,
                                'title_clearning' => $plan->title_clearning,
                            ]);
                        }
                    } elseif ($theory == 1) {

                        if ($plan->start) {

                            $events->push([
                                'plan_id' => $plan->id,
                                'id' => "{$plan->id}-main-theory",
                                'title' => trim($plan->title . '- Lịch Lý Thuyết' ?? ''),
                                'start' => $plan->start,
                                'end' => $plan->end,
                                'resourceId' => $plan->resourceId,
                                'color' => '#8397faff',
                                'textColor' => $textColor,
                                'plan_master_id' => $plan->plan_master_id,
                                'stage_code' => $plan->stage_code,
                                'is_clearning' => false,
                                'status' => $plan->status,
                                'finished' => $plan->finished,
                                'level' => $plan->level,
                                'process_code' => $plan->process_code,
                                'keep_dry' => $plan->keep_dry,
                                'tank' => $plan->tank,
                                'storage_capacity' => $storage_capacity,
                                'campaign_code' => $plan->campaign_code,
                                'product_name' => $plan->product_name,
                                'batch_name' => $plan->batch_name,
                                'actual_batch' => $plan->actual_batch,
                                'code' => $plan->code,
                                'predecessor_code' => $plan->predecessor_code,
                                'expected_date' => $plan->expected_date ? Carbon::parse($plan->expected_date)->format('Y-m-d') : null,

                            ]);
                        }

                        // event lich vs lý thuyết
                        if ($clearning && $plan->yields >= 0 && $plan->start_clearning) {

                            $events->push([
                                'plan_id' => $plan->id,
                                'id' => "{$plan->id}-cleaning-theory",
                                'title' => $plan->title_clearning . ' - Lịch Lý Thuyết' ?? 'Vệ sinh',
                                'start' => $plan->start_clearning,
                                'end' => $plan->end_clearning,
                                'resourceId' => $plan->resourceId,
                                'color' => '#8397faff',
                                'textColor' => $textColor,
                                'plan_master_id' => $plan->plan_master_id,
                                'stage_code' => $plan->stage_code,
                                'is_clearning' => true,
                                'finished' => $plan->finished,
                                'process_code' => $plan->process_code,
                                'campaign_code' => $plan->campaign_code,
                                'product_name' => $plan->product_name,
                                'batch_name' => $plan->batch_name,
                                'actual_batch' => $plan->actual_batch,
                                'code' => $plan->code,
                                'predecessor_code' => $plan->predecessor_code,
                                'expected_date' => $plan->expected_date ? Carbon::parse($plan->expected_date)->format('Y-m-d') : null,

                            ]);
                        }
                    } elseif ($theory == 2) {

                        $events->push([
                            'plan_id' => $plan->id,
                            'id' => "{$plan->id}-main",
                            'title' => $plan->title,
                            'start' => $plan->actual_start,
                            'end' => $plan->actual_end,
                            'resourceId' => $plan->resourceId,
                            'color' => '#002af9ff',
                            'textColor' => $textColor,
                            'plan_master_id' => $plan->plan_master_id,
                            'stage_code' => $plan->stage_code,
                            'is_clearning' => false,
                            'status' => $plan->status,
                            'finished' => $plan->finished,
                            'level' => $plan->level,
                            'process_code' => $plan->process_code,
                            'keep_dry' => $plan->keep_dry,
                            'tank' => $plan->tank,
                            'storage_capacity' => $storage_capacity,
                            'campaign_code' => $plan->campaign_code,
                            'product_name' => $plan->product_name,
                            'batch_name' => $plan->batch_name,
                            'actual_batch' => $plan->actual_batch,
                            'code' => $plan->code,
                            'predecessor_code' => $plan->predecessor_code,
                            'expected_date' => $plan->expected_date ? Carbon::parse($plan->expected_date)->format('Y-m-d') : null,
                            'title_clearning' => $plan->title_clearning,
                        ]);

                        // event lich vs thực tế
                        if ($clearning && $plan->yields >= 0) {

                            $events->push([
                                'plan_id' => $plan->id,
                                'id' => "{$plan->id}-cleaning",
                                'title' => $plan->title_clearning,
                                'start' => $plan->actual_start_clearning,
                                'end' => $plan->actual_end_clearning,
                                'resourceId' => $plan->resourceId,
                                'color' => '#002af9ff',
                                'textColor' => $textColor,
                                'plan_master_id' => $plan->plan_master_id,
                                'stage_code' => $plan->stage_code,
                                'is_clearning' => true,
                                'finished' => $plan->finished,
                                'process_code' => $plan->process_code,
                                'campaign_code' => $plan->campaign_code,
                                'product_name' => $plan->product_name,
                                'batch_name' => $plan->batch_name,
                                'actual_batch' => $plan->actual_batch,
                                'code' => $plan->code,
                                'predecessor_code' => $plan->predecessor_code,
                                'expected_date' => $plan->expected_date ? Carbon::parse($plan->expected_date)->format('Y-m-d') : null,
                                'title_clearning' => $plan->title_clearning,
                            ]);
                        }

                        if ($plan->start) {

                            $events->push([
                                'plan_id' => $plan->id,
                                'id' => "{$plan->id}-main-theory",
                                'title' => trim($plan->title . '- Lịch Lý Thuyết' ?? ''),
                                'start' => $plan->start,
                                'end' => $plan->end,
                                'resourceId' => $plan->resourceId,
                                'color' => '#8397faff',
                                'textColor' => $textColor,
                                'plan_master_id' => $plan->plan_master_id,
                                'stage_code' => $plan->stage_code,
                                'is_clearning' => false,
                                'status' => $plan->status,
                                'finished' => $plan->finished,
                                'level' => $plan->level,
                                'process_code' => $plan->process_code,
                                'keep_dry' => $plan->keep_dry,
                                'tank' => $plan->tank,
                                'storage_capacity' => $storage_capacity,
                                'campaign_code' => $plan->campaign_code,
                                'product_name' => $plan->product_name,
                                'batch_name' => $plan->batch_name,
                                'actual_batch' => $plan->actual_batch,
                                'code' => $plan->code,
                                'predecessor_code' => $plan->predecessor_code,
                                'expected_date' => $plan->expected_date ? Carbon::parse($plan->expected_date)->format('Y-m-d') : null,
                                'title_clearning' => $plan->title_clearning,
                            ]);
                        }

                        // event lich vs lý thuyết
                        if ($clearning && $plan->yields >= 0 && $plan->start_clearning) {

                            $events->push([
                                'plan_id' => $plan->id,
                                'id' => "{$plan->id}-cleaning-theory",
                                'title' => $plan->title_clearning . ' - Lịch Lý Thuyết' ?? 'Vệ sinh',
                                'start' => $plan->start_clearning,
                                'end' => $plan->end_clearning,
                                'resourceId' => $plan->resourceId,
                                'color' => '#8397faff',
                                'textColor' => $textColor,
                                'plan_master_id' => $plan->plan_master_id,
                                'stage_code' => $plan->stage_code,
                                'is_clearning' => true,
                                'finished' => $plan->finished,
                                'process_code' => $plan->process_code,
                                'campaign_code' => $plan->campaign_code,
                                'product_name' => $plan->product_name,
                                'batch_name' => $plan->batch_name,
                                'actual_batch' => $plan->actual_batch,
                                'code' => $plan->code,
                                'predecessor_code' => $plan->predecessor_code,
                                'expected_date' => $plan->expected_date ? Carbon::parse($plan->expected_date)->format('Y-m-d') : null,
                                'title_clearning' => $plan->title_clearning,
                            ]);
                        }
                    }
                }
            }
        }

        // 🔥 Gộp các công đoạn 1 & 2 mang cùng Campaign, thời gian, phòng
        $weighingStages = $events->whereIn('stage_code', [1,  2]);

        $otherStages = $events->whereNotIn('stage_code', [1,  2]);

        $groupedWeighing = $weighingStages->groupBy(function ($event) {

            $e = (object) $event;

            $isTheory = strpos($e->id, '-theory') !== false;

            $isFinished = ($e->finished ?? 0) == 1;

            // tiêu đề yêu cầu mới: chỉ so khớp start, resourceid và stage_code
            // vẫn giữ is_clearning và istheory để tránh gộp main và cleaning hoặc theory và actual
            return $e->start . '_' . $e->resourceId . '_' . $e->stage_code . '_' . ($e->is_clearning ? 'CL' : 'MN') . '_' . ($isTheory ? 'TH' : 'AC');
        })->map(function ($group) {

            $first = (object) $group->first();

            $first = clone $first;

            $isTheory = strpos($first->id, '-theory') !== false;

            $isFinished = ($first->finished ?? 0) == 1;

            if ($group->count() > 1) {

                $pureIds = $group->pluck('plan_id')->toArray();

                $suffix = $isTheory ? '-theory' : '';

                $typeSuffix = $first->is_clearning ? '-cleaning' : '-main';

                $first->id = implode(',', $pureIds) . $typeSuffix . $suffix;

                // Tính toán min Start và max End cho tất cả các sự kiện gộp Stage 1 & 2
                $first->start = $group->min('start');

                $first->end = $group->max('end');

                if (! $first->is_clearning) {

                    // Gom danh sách số lô (batch) - Ưu tiên actual_batch cho Finished/Theory
                    $batchField = ($isFinished || $isTheory) ? 'actual_batch' : 'batch_name';

                    $allBatches = $group->pluck($batchField)->unique()->filter()->toArray();

                    // fallback nếu actual_batch trống (đặc biệt cho theory)
                    if (empty($allBatches)) {

                        $allBatches = $group->pluck('batch_name')->unique()->filter()->toArray();
                    }

                    // Tiêu đề gộp: Product_Name (Batch1, Batch2...)
                    $productName = $first->product_name ?? $first->title ?? 'Sản phẩm';

                    $batchList = implode(', ', $allBatches);

                    $first->title = "{$productName} ({$batchList})";
                }
            }

            return $first;
        })->values();

        $events = $otherStages->concat($groupedWeighing)->values();

        $percentMap = $event_plans->pluck('percent_parkaging', 'id')->toArray();
        $events = $events->map(function ($event) use ($percentMap) {
            $plan_id = is_object($event) ? ($event->plan_id ?? null) : ($event['plan_id'] ?? null);
            if ($plan_id && isset($percentMap[$plan_id])) {
                if (is_object($event)) {
                    $event->percent_parkaging = (float) $percentMap[$plan_id];
                } else {
                    $event['percent_parkaging'] = (float) $percentMap[$plan_id];
                }
            } else {
                if (is_object($event)) {
                    $event->percent_parkaging = 1;
                } else {
                    $event['percent_parkaging'] = 1;
                }
            }
            return $event;
        });

        return $this->groupMaintenanceEvents($events);
    }

    protected function groupMaintenanceEvents($events)
    {
        if ($events instanceof \Illuminate\Support\Collection) {
            $events = $events;
        } else {
            $events = collect($events);
        }

        $maintenanceEvents = $events->where('stage_code', '=', 8);
        $productionEvents = $events->where('stage_code', '<', 8);

        $groupedMaintenance = $maintenanceEvents->groupBy(function ($event) {
            $e = (object) $event;
            $isTheory = strpos($e->id, '-theory') !== false;
            $isClearning = $e->is_clearning ?? false;

            // Nhóm theo start, resourceId, loại vệ sinh/chính, loại lý thuyết/thực tế (loại bỏ end để tránh lệch giây/phút)
            return $e->start . '_' . $e->resourceId . '_' . ($isClearning ? 'CL' : 'MN') . '_' . ($isTheory ? 'TH' : 'AC');
        })->map(function ($group) {
            $first = (object) $group->first();
            $first = clone $first;

            if ($group->count() > 1) {
                // Gom tất cả ID lại
                $allIds = $group->pluck('plan_id')->map(function ($id) {
                    return explode('-', $id)[0];
                })->toArray();

                $isTheory = strpos($first->id, '-theory') !== false;
                $suffix = $isTheory ? '-theory' : '';
                $typeSuffix = ($first->is_clearning ?? false) ? '-cleaning' : '';
                $first->id = implode(',', $allIds) . '-maintenance' . $typeSuffix . $suffix;

                // Tính toán min Start và max End cho các sự kiện gộp
                $first->start = $group->min('start');
                $first->end = $group->max('end');

                // Gom tiêu đề
                $uniqueTitles = $group->pluck('title')->unique();
                if ($uniqueTitles->count() === 1 && strpos($uniqueTitles->first(), ' _ ') !== false) {
                    $first->title = $uniqueTitles->first();
                } else {
                    $allTitles = $uniqueTitles->map(function ($t) {
                        $parts = explode(' - ', $t);
                        return count($parts) > 1 ? end($parts) : $t;
                    })->toArray();
                    $first->title = 'BT Thiết Bị: ' . implode(' | ', $allTitles);
                }
            }

            return (array) $first;
        })->values();

        return $productionEvents->concat($groupedMaintenance)->values();
    }

    protected function colorEvent($plan, $plans, $i, $room_code)
    {

        $subtitles = [];
        $violation_colors = [];
        $mold_warning = null;
        $mold_code = null;

        $textColor = '#fefefee2';

        $color_event = '#eb0cb3ff';
        // default fallback

        /* 1️⃣ finished */
        if ($plan->finished == 1) {
            return ['#002af9ff',  '#fefefee2',  '', [], null, $mold_code, null, null, null, null];
        }

        /* 2️⃣ màu mặc định theo stage */
        if ($plan->stage_code <= 7) {
            $color_event = '#4CAF50';
        } elseif ($plan->stage_code == 8) {
            // Mặc định cho Bảo trì (BT)
            $color_event = '#003A4F';
            // tinh chỉnh màu theo loại block (hc, bt, ti)
            if (isset($plan->code)) {
                if (substr($plan->code, -2) === 'HC') {
                    $color_event = '#9a1b72ff';
                    // Tím đậm cho Hiệu chuẩn
                } elseif (substr($plan->code, -2) === 'TI') {
                    $color_event = '#830cbfff';
                    // Cam đất cho Tiện ích
                }
            }
        }

        /* 3️⃣ validation ok */
        if ($plan->is_val == 1) {
            $color_event = '#40E0D0';
        }

        // 🚨 Mold validation

        if ($plan->stage_code == 7 && $plan->finished == 0 && $plan->start && $plan->end && $plan->resourceId) {
            if (!empty($plan->blister_mold_id)) {
                // Đã được gán khuôn cụ thể. Kiểm tra xem có bị quá tải không (trùng).
                $mold = DB::table('blister_mold')->where('id', $plan->blister_mold_id)->first();
                if ($mold) {
                    $mold_code = $mold->code;
                    $room = DB::table('room')->where('id', $plan->resourceId)->first();

                    $moldTypes = [];
                    if (!empty($mold->blister_type_code)) {
                        $decoded = json_decode($mold->blister_type_code, true);
                        $moldTypes = is_array($decoded) ? $decoded : [$mold->blister_type_code];
                    }
                    if ($room && !empty($room->blister_type_code) && !empty($mold->blister_type_code) && !in_array($room->blister_type_code, $moldTypes)) {
                        $subtitles[] = "❌ Sai Khuôn: {$mold->code} không lắp được cho máy {$room->blister_type_code}";
                        $color_event = '#e54a4aff'; // Đỏ báo lỗi
                        $textColor = '#ffffff';
                        $violation_colors[] = '#e54a4aff';
                        $mold_warning = "❌ Sai Khuôn: {$mold->code} / {$room->blister_type_code}";
                    } else {
                        $concurrentCount = DB::table('stage_plan')
                            ->where('stage_code', 7)
                            ->where('blister_mold_id', $mold->id)
                            ->where('active', 1)
                            ->where('finished', 0)
                            ->whereNotNull('start')
                            ->whereNotNull('resourceId')
                            ->where(function ($q) use ($plan) {
                                $q->where('start', '<', $plan->end)
                                    ->where('end', '>', $plan->start);
                            })
                            ->pluck('resourceId')
                            ->unique()
                            ->count();

                        if ($concurrentCount > $mold->amount) {
                            $subtitles[] = "⚠️ Trùng Khuôn: {$mold->code} (Đang dùng: {$concurrentCount}, Tổng: {$mold->amount})";
                            $color_event = '#ffd500ff';
                            $textColor = '#ffffff';
                            $violation_colors[] = '#ffd500ff';
                            $mold_warning = "⚠️ Trùng Khuôn: {$mold->code}";
                        }
                    }
                }
            } else {
                // Cảnh báo thiếu khuôn nếu lô được xếp lên máy ép vỉ (có blister_type_code) nhưng chưa gán khuôn
                $room = DB::table('room')->where('id', $plan->resourceId)->first();
                if ($room && !empty($room->blister_type_code)) {
                    $subtitles[] = "❌ Thiếu Khuôn!";
                    $color_event = '#e54a4aff'; // Đỏ báo lỗi
                    $textColor = '#ffffff';
                    $violation_colors[] = '#e54a4aff';
                    $mold_warning = "❌ Thiếu Khuôn!";
                }
            }
        }

        /* 4️⃣ clearning */
        if ($plan->clearning_validation == 1) {

            $color_event = '#e4e405e2';
            $textColor = '#fb0101e2';
            $violation_colors[] = '#e4e405e2';
        }

        /* 5️⃣ biệt trữ */
        if ($i > 0 && $plan->quarantine_total == 0 && $plan->stage_code > 3 && $plan->stage_code < 7 && $plan->accept_quarantine == 0) {

            $prev = $plans->firstWhere('code', $plan->predecessor_code);

            if ($prev && $plan->start) {

                $diffMinutes = Carbon::parse($prev->end)
                    ->diffInMinutes(Carbon::parse($plan->start), false);

                $limitMinutes = $prev->quarantine_time_limit_hour * 60;

                if ($limitMinutes > 0 && $diffMinutes > $limitMinutes) {

                    $h = minutesToDayHoursMinutesString($diffMinutes);

                    $lh = minutesToDayHoursMinutesString($limitMinutes);

                    $subtitles[] = "➡️ (KT {$this->stage_Name[$prev->stage_code]}: "
                        . Carbon::parse($prev->end)->format('H:i d/m/y')
                        . " || TGTB thực tế: $h"
                        . " || TGTB cho phép: $lh)";

                    $color_event = '#bda124ff';
                    $textColor = '#ffffff';
                    $violation_colors[] = '#bda124ff';
                }
            }
        }

        /* 6️⃣ HẠN CẦN HÀNG */
        $Stage_plan_7 = $plans->firstWhere('stage_code', 7);

        $overExpected = ($Stage_plan_7 && \Carbon\Carbon::parse($plan->expected_date)->startOfDay()->lt(\Carbon\Carbon::parse($Stage_plan_7->end)->startOfDay()))
            || \Carbon\Carbon::parse($plan->expected_date)->startOfDay()->lt(\Carbon\Carbon::parse($plan->end)->startOfDay());

        if ($overExpected && $plan->stage_code <= 7) {

            $color_event = '#e54a4aff';
            $textColor = '#ffffff';
            $violation_colors[] = '#e54a4aff';

            $endStage7 = $Stage_plan_7 && $Stage_plan_7->end ? Carbon::parse($Stage_plan_7->end)->format('d/m/y') : 'Chưa xác định';

            $subtitles[] = '➡️ Ngày dự kiến KCS: ' . Carbon::parse($plan->expected_date)->format('d/m/y') . ' | Ngày KT ĐG: ' . $endStage7;
        }

        $plan_start = ($plan->finished == 1 && $plan->actual_start) ? $plan->actual_start : $plan->start;
        $plan_end = ($plan->finished == 1 && $plan->actual_end) ? $plan->actual_end : $plan->end;

        /* 7️⃣ predecessor / successor */
        if ($plan->predecessor_code) {

            $pre = $plans->firstWhere('code', $plan->predecessor_code);

            if ($pre) {
                $pre_end = ($pre->finished == 1 && $pre->actual_end) ? $pre->actual_end : $pre->end;

                if ($plan_start < $pre_end  && $plan_end < $pre_end) {

                    $subtitles[] = "➡️ (KT {$this->stage_Name[$pre->stage_code]} tại {$room_code[$pre->resourceId]}: "
                        . Carbon::parse($pre_end)->format('H:i d/m/y') . ')';

                    $color_event = '#4d4b4bff'; // '#4d4b4bff'
                    $textColor = '#ffffff';
                    $violation_colors[] = '#4d4b4bff';

                    $plan->violation_predecessor_id = $pre->id;
                    $plan->violation_predecessor_end = $pre_end;
                }
            }
        }

        if ($plan->nextcessor_code) {

            $next = $plans->firstWhere('code', $plan->nextcessor_code);

            if ($next) {
                $next_start = ($next->finished == 1 && $next->actual_start) ? $next->actual_start : $next->start;

                if ($plan_end > $next_start  && $plan_start > $next_start) {

                    $subtitles[] = "➡️ (BĐ {$this->stage_Name[$next->stage_code]} tại {$room_code[$next->resourceId]}: "
                        . Carbon::parse($next_start)->format('H:i d/m/y') . ')';

                    $color_event = '#4d4b4bff'; // '#4d4b4bff'
                    $textColor = '#ffffff';
                    $violation_colors[] = '#4d4b4bff';

                    $plan->violation_successor_id = $next->id;
                    $plan->violation_successor_start = $next_start;
                }
            }
        }

        // Nếu có trùng khuôn thì đổi màu
        if ($mold_warning) {
            $color_event = '#ffd500ff';
            $textColor = '#ffffff';
        }

        $criticalChecks = [
            [1,  3,  'after_weigth_date',         '➡️ Ngày có đủ NL',  '>'],
            [1,  3,  'allow_weight_before_date',  '➡️ Ngày được phép cân',  '>'],
            [1,  3,  'expired_material_date',     '➡️ Ngày hết hạn NL chính',  '<'],
            [7,  7,  'expired_packing_date',     '➡️ Ngày hết hạn BB',  '<'],
            [3,  3,  'preperation_before_date',  '➡️ Phải PC trước ngày',  '<'],
            [4,  4,  'blending_before_date',    '➡️ Phải THT trước ngày',  '<'],
            [6,  6,  'coating_before_date',     '➡️ Phải BP trước ngày',  '<'],
            [7,  7,  'parkaging_before_date',     '➡️ Phải ĐG trước ngày ',  '<'],
            [7,  7,  'after_parkaging_date',    '➡️ Ngày có đủ BB',  '>'],
        ];

        foreach ($criticalChecks as [$from,  $to,  $field,  $label,  $operator]) {
            if (
                $plan->stage_code < $from ||
                $plan->stage_code > $to ||
                empty($plan->$field)
            ) {

                continue;
            }

            $left = Carbon::parse($plan->$field)->startOfDay();

            $right = Carbon::parse($plan->start)->startOfDay();

            $matched = match ($operator) {

                '<' => $left->lt($right),

                '<=' => $left->lte($right),

                '>' => $left->gt($right),

                '>=' => $left->gte($right),

                '==' => $left->eq($right),

                default => false,
            };

            if ($matched) {

                $subtitles[] = "{$label}: "
                    . $left->format('d/m/y')
                    . " {$operator} "
                    . $right->format('d/m/y');

                $color_event = '#920000ff';
                $textColor = '#ffffff';
                $violation_colors[] = '#920000ff';
            }
        }



        $violation_colors = array_unique($violation_colors);

        $filtered_violation_colors = array_filter($violation_colors, function ($color) use ($color_event) {
            return $color !== $color_event;
        });

        $finalSubtitle = implode("\n", $subtitles);

        return [
            $color_event,
            $textColor,
            $finalSubtitle,
            array_values($filtered_violation_colors),
            $mold_warning,
            $mold_code,
            $plan->violation_predecessor_id ?? null,
            $plan->violation_predecessor_end ?? null,
            $plan->violation_successor_id ?? null,
            $plan->violation_successor_start ?? null
        ];
    }

    // hàm lấy quota
    protected function getQuota($production)
    {

        $result = DB::table('quota')
            ->leftJoin('room', 'quota.room_id', '=', 'room.id')
            ->where('quota.active', 1)
            ->where('quota.deparment_code', $production)
            ->get();

        $result = $result->map(function ($item) {
            $toSeconds = fn($time) => (($h = (int) explode(':', $time)[0]) * 3600) + ((int) explode(':', $time)[1] * 60);
            $toTime = fn($seconds) => sprintf('%02d:%02d', floor($seconds / 3600), floor(($seconds % 3600) / 60));

            // Convert to decimal hours for frontend
            $item->p_time = round($toSeconds($item->p_time) / 3600, 2);
            $item->m_time = round($toSeconds($item->m_time) / 3600, 2);
            $item->C1_time = round($toSeconds($item->C1_time) / 3600, 2);
            $item->C2_time = round($toSeconds($item->C2_time) / 3600, 2);

            $item->PM = $toTime(($item->p_time + $item->m_time) * 3600);

            return $item;
        });

        return $result;
    }

    public function getPlanWaiting($production, $order_by_type = false)
    {

        $order_by_column = 'sp.order_by';

        if ($order_by_type) {

            $order_by_column = 'sp.order_by_line';
        }

        $plan_waiting = DB::table('stage_plan as sp')
            ->whereNull('sp.start')
            ->where('sp.active', 1)
            ->where('sp.finished', 0)
            ->where('sp.stage_code', '!=', 8)
            ->where('sp.deparment_code', $production)
            ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
            ->leftJoin('plan_list', 'sp.plan_list_id', '=', 'plan_list.id')
            ->leftJoin('source_material', 'plan_master.material_source_id', '=', 'source_material.id')
            ->leftJoin('finished_product_category', function ($join) {

                $join->on('sp.product_caterogy_id', '=', 'finished_product_category.id')
                    ->where('sp.stage_code', '<=', 7);
            })
            ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', '=', 'intermediate_category.intermediate_code')
            ->leftJoin('product_name', function ($join) {

                $join->on('intermediate_category.product_name_id', '=', 'product_name.id')
                    ->where('sp.stage_code', '<=', 7);
            })
            ->leftJoin('market', 'finished_product_category.market_id', '=', 'market.id')
            ->select(
                'sp.id',
                'sp.code',
                'sp.plan_master_id',
                'sp.product_caterogy_id',
                'sp.campaign_code',
                'sp.stage_code',
                'sp.order_by',
                'sp.order_by_line',
                'sp.clearning_validation',
                'sp.required_room_code',
                'sp.predecessor_code',
                'sp.nextcessor_code',
                'sp.immediately',
                'sp.not_schedule',
                'sp.blister_mold_id',

                DB::raw("
                                        CASE
                                                WHEN sp.stage_code >= 8 THEN sp.title
                                                ELSE CONCAT(
                                                product_name.name,
                                                '-',
                                                COALESCE(plan_master.actual_batch, plan_master.batch)
                                                )
                                        END AS title,
                                        product_name.name as name,
                                        COALESCE(plan_master.actual_batch, plan_master.batch) as batch
                                "),

                'plan_master.id as plan_master_id',
                // 'plan_master.batch',
                'plan_master.expected_date',
                'plan_master.responsed_date',
                'plan_master.is_val',
                'plan_master.note',
                'plan_master.level',
                'plan_master.after_weigth_date',
                'plan_master.after_parkaging_date',

                'plan_master.allow_weight_before_date',
                'plan_master.preperation_before_date',
                'plan_master.blending_before_date',
                'plan_master.coating_before_date',
                'plan_master.expired_material_date',

                'plan_master.material_source_id',
                'plan_master.only_parkaging',
                'plan_master.percent_parkaging',
                'plan_master.main_parkaging_id',
                'plan_list.month',
                'market.code as market',
                'source_material.name as source_material_name',
                'finished_product_category.intermediate_code',
                'finished_product_category.finished_product_code',
                // DB::raw("CASE WHEN sp.stage_code <= 7 THEN product_name.name ELSE sp.code END as name")
            )
            ->orderBy($order_by_column, 'asc')
            ->get();

        if ($plan_waiting->isEmpty()) {

            return $plan_waiting;
        }

        // 3️⃣ Lấy dữ liệu liên quan chỉ 1 lần
        $quota = DB::table('quota')
            ->leftJoin('room', 'quota.room_id', '=', 'room.id')
            ->where('quota.active', 1)
            ->where('quota.deparment_code', $production)
            ->select(
                'quota.*',
                'room.name',
                'room.code'
            )
            ->get();

        // Tạo map tra cứu nhanh
        $quotaByIntermediate = $quota->groupBy(function ($q) {

            return $q->intermediate_code . '_' . $q->stage_code;
        });

        $quotaByFinished = $quota->groupBy(function ($q) {

            return $q->intermediate_code . '_' . $q->finished_product_code . '_' . $q->stage_code;
        });

        // 4️⃣ Map dữ liệu permission_room (cực nhanh)
        $plan_waiting->transform(function ($plan) use ($quotaByIntermediate, $quotaByFinished) {

            if ($plan->stage_code <= 6) {

                $key = $plan->intermediate_code . '_' . $plan->stage_code;

                $matched = $quotaByIntermediate[$key] ?? collect();
            } elseif ($plan->stage_code == 7) {

                $key = $plan->intermediate_code . '_' . $plan->finished_product_code . '_' . $plan->stage_code;

                $matched = $quotaByFinished[$key] ?? collect();
            } else {

                $matched = collect();
            }

            // Mảng phòng được phép
            $plan->permisson_room = collect($matched)->pluck('code', 'room_id')->unique();

            // ✅ Thêm field để React có thể filter/search nhanh
            $plan->permisson_room_filter = $plan->permisson_room->values()->implode(', ');

            // Lấy danh sách khuôn cho stage 7
            if ($plan->stage_code == 7) {
                static $moldCache = [];
                if (!array_key_exists($plan->product_caterogy_id, $moldCache)) {
                    $moldCache[$plan->product_caterogy_id] = DB::table('finished_product_mold')
                        ->join('blister_mold', 'finished_product_mold.blister_mold_id', '=', 'blister_mold.id')
                        ->where('finished_product_mold.finished_product_category_id', $plan->product_caterogy_id)
                        ->where('blister_mold.active', 1)
                        ->select('blister_mold.id', 'blister_mold.code', 'blister_mold.amount')
                        ->get();
                }
                $plan->compatible_molds = $moldCache[$plan->product_caterogy_id];
            } else {
                $plan->compatible_molds = [];
            }

            return $plan;
        });

        return $plan_waiting;
    }

    // hàm lấy sản lượng và thời gian sản xuất theo phòng
    protected function getResources($production, $startDate, $endDate, $hasMaintenance = false)
    {

        $roomStatus = $this->getRoomStatistics($startDate, $endDate);

        $sumBatchQtyResourceId = $this->yield($startDate, $endDate, 'resourceId');

        $statsMap = $roomStatus->keyBy('resourceId');

        $yieldMap = $sumBatchQtyResourceId->keyBy('resourceId');

        $result = DB::table('room')
            ->select(
                'id',
                'code',
                'sheet_1',
                'sheet_2',
                'sheet_3',
                'sheet_regular',
                DB::raw("CONCAT(code,'-', name) as title"),
                'main_equiment_name',
                'order_by',
                'stage_code',
                'production_group',
                DB::raw("
                                CASE
                                WHEN stage_code IN (3, 4) THEN 'Pha chế'
                                ELSE stage
                                END AS stage_name
                        ")
            )
            ->where('active', 1)
            ->where('room.deparment_code', $production)
            ->when(
                ! $hasMaintenance,
                fn($query) => $query->where('only_maintenance', 0)
            )

            ->orderBy('order_by', 'asc')
            ->get()
            ->map(function ($room) use ($statsMap, $yieldMap) {

                $stat = $statsMap->get($room->id);

                $yield = $yieldMap->get($room->id);

                $room->busy_hours = $stat->busy_hours ?? 0;

                $room->free_hours = $stat->free_hours ?? 0;

                $room->total_hours = $stat->total_hours ?? 0;

                $room->yield = $yield->total_qty ?? 0;

                $room->unit = $yield->unit ?? '';

                return $room;
            });

        // Sắp xếp lại theo 'stage_code'
        $sortedResult = $result->sortBy('stage_code')->values();

        $finalResources = [];
        foreach ($sortedResult as $room) {
            $finalResources[] = $room;

            // Dòng con đại diện cho nhân sự trực thuộc phòng này
            $personnelSub = new \stdClass();
            $personnelSub->id = 'personnel-' . $room->id;
            $personnelSub->parentId = (string) $room->id;
            $personnelSub->code = $room->code;
            $personnelSub->title = '👥 Nhân sự trực';
            $personnelSub->main_equiment_name = '';
            $personnelSub->order_by = $room->order_by;
            $personnelSub->stage_code = $room->stage_code;
            $personnelSub->production_group = $room->production_group;
            $personnelSub->stage_name = $room->stage_name;
            $personnelSub->sheet_1 = 0;
            $personnelSub->sheet_2 = 0;
            $personnelSub->sheet_3 = 0;
            $personnelSub->sheet_regular = 0;
            $personnelSub->busy_hours = 0;
            $personnelSub->free_hours = 0;
            $personnelSub->total_hours = 0;
            $personnelSub->yield = 0;
            $personnelSub->unit = '';
            $personnelSub->is_personnel_sub = true;

            $finalResources[] = $personnelSub;
        }

        return collect($finalResources);
    }

    // hàm view gọn hơn request
    public function view(Request $request)
    {

        $startDate = $request->startDate ?? Carbon::now();

        $endDate = $request->endDate ?? Carbon::now()->addDays(7);

        $viewtype = $request->viewtype ?? 'resourceTimelineWeek';

        $this->theory = (int) $request->theory ?? 0;

        try {

            $production = session('user.production_code');

            $department = DB::table('user_management')->where('userName', session('user')['userName'])->value('deparment');

            $clearing = $request->clearning ?? true;

            // if ($viewtype == 'resourceTimelineQuarter') {

            //    $clearing = false;
            // }

            if (user_has_permission(session('user')['userId'], 'loading_plan_waiting', 'boolean')) {

                $plan_waiting = $this->getPlanWaiting($production);

                $bkc_code = DB::table('stage_plan_bkc')->where('deparment_code', session('user.production_code'))->select('bkc_code')->distinct()->orderByDesc('bkc_code')->get();

                $reason = DB::table('reason')->where('deparment_code', $production)->pluck('name');

                $quota = $this->getQuota($production);
            }

            $stageMap = DB::table('room')->where('deparment_code', $production)->pluck('stage_code', 'stage')->toArray();

            $events = $this->getEvents($production, $startDate, $endDate, $clearing, $this->theory);

            $sumBatchByStage = $this->yield($startDate, $endDate, 'stage_code');

            $resources = $this->getResources($production, $startDate, $endDate);

            $title = 'LỊCH SẢN XUẤT';

            $type = true;

            $Lines = DB::table('room')
                ->select('stage_code', 'name', 'code')
                ->where('deparment_code', $production)
                ->whereIn('stage_code', [3,  4,  5,  6,  7])
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
                ->whereIn('stage_code', [3,  4,  5,  6,  7])
                ->where('active', 1)
                ->orderBy('order_by')
                ->get();

            $authorization = session('user')['userGroup'];

            $UesrID = session('user')['userId'];

            // Truy vấn lịch phân công nhân sự trong khoảng thời gian đang xem
            $assignments = DB::table('assignments as a')
                ->join('assignment_personnel as ap', 'a.id', '=', 'ap.assignment_id')
                ->join('employees as e', 'ap.personnel_id', '=', 'e.id')
                ->where('a.active', 1)
                ->where('a.deparment_code', $production)
                ->whereBetween('a.start', [$startDate, $endDate])
                ->select('a.id', 'a.room_id', 'a.start', 'a.end', 'e.name as employee_name')
                ->get()
                ->groupBy('id');

            $personnelEvents = [];
            foreach ($assignments as $assignmentId => $items) {
                $first = $items->first();
                $names = $items->pluck('employee_name')->implode(', ');

                $personnelEvents[] = [
                    'id' => 'personnel-' . $assignmentId,
                    'resourceId' => 'personnel-' . $first->room_id,
                    'start' => $first->start,
                    'end' => $first->end,
                    'title' => '👥 ' . $names,
                    'color' => '#dbeafe',
                    'textColor' => '#1e40af',
                    'borderColor' => '#bfdbfe',
                    'editable' => false,
                    'is_personnel' => true,
                    'display' => 'block'
                ];
            }

            $room_links = DB::table('room_links')
                ->join('room as src', 'src.id', '=', 'room_links.source_room_id')
                ->where('src.deparment_code', $production)
                ->where('room_links.active', 1)
                ->select('room_links.source_room_id', 'room_links.target_room_id')
                ->get();

            return response()->json([
                'title' => $title,
                'events' => $events,
                'plan' => $plan_waiting ?? [],  // [phân quyền]
                'quota' => $quota ?? [],
                'stageMap' => $stageMap ?? [],
                'resources' => $resources ?? [],
                'sumBatchByStage' => $sumBatchByStage ?? [],
                'reason' => $reason ?? [],
                'type' => $type,
                'authorization' => $authorization,
                'production' => $production,
                'department' => $department,
                'currentPassword' => session('user')['passWord'] ?? '',
                'Lines' => $Lines ?? [],
                'allLines' => $allLines ?? [],
                'off_days' => DB::table('off_days')->where('off_date', '>=', now())->get()->pluck('off_date') ?? [],
                'bkc_code' => $bkc_code ?? [],
                'UesrID' => $UesrID,
                'personnel_events' => $personnelEvents,
                'room_links' => $room_links,
            ]);
        } catch (\Throwable  $e) {

            // Ghi log chi tiết lỗi
            Log::error('Error in view(): ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // hàm tính tổng sản lượng lý thuyết theo stage
    public function getSumaryData(Request $request)
    {

        $sumBatchByStage = $this->yield($request->startDate, $request->endDate, 'stage_code');

        return response()->json([
            'sumBatchByStage' => $sumBatchByStage,
        ]);
    }

    public function getInforSoure(Request $request)
    {

        $plan_master = DB::table('plan_master')
            ->select('finished_product_category.intermediate_code', 'product_name.name as product_name', 'plan_master.material_source_id', 'source_material.name')
            ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
            ->leftJoin('source_material', 'plan_master.material_source_id', 'source_material.id')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
            ->where('plan_master.id', $request->plan_master_id)
            ->first();

        return response()->json([
            'sourceInfo' => $plan_master,
        ]);
    }

    public function confirm_source(Request $request)
    {

        try {

            DB::table('room_source')->insert([
                'intermediate_code' => $request->intermediate_code,
                'room_id' => $request->room_id,
                'source_id' => $request->source_id,
                'prepared_by' => session('user')['fullName'],
                'created_at' => now(),
            ]);

            $production = session('user.production_code');

            $events = $this->getEvents($production, $request->startDate, $request->endDate, true, $this->theory);

            return response()->json([
                'events' => $events,
            ]);
        } catch (\Exception  $e) {

            DB::rollBack();

            Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error',  'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {

        $this->selectedDates = $request->offdate ?? [];
        // giữ để tạo $this->offdate
        $this->loadOffDate('asc');
        // Tạo  $this->offdate

        $multi_stage = $request->multiStage ?? false;

        $start_date = null;

        DB::beginTransaction();

        try {

            // Sắp xếp products theo batch
            $products = collect($request->products)->sortBy('batch')->values();

            // Thời gian bắt đầu ban đầu
            $current_start = Carbon::parse($request->start);

            $slotDuration = $request->slotDuration;

            if ($request->has('slotDuration') && $request->slotDuration == 1) {

                $room = DB::table('room')->where('id', $request->room_id)->first();

                if ($room) {

                    if ($room->sheet_regular == 1) {

                        $current_start->setTime(7, 15, 0);
                    } elseif ($room->sheet_1 == 1) {

                        $current_start->setTime(6, 0, 0);
                    } elseif ($room->sheet_1 == 0 && $room->sheet_2 == 1) {

                        $current_start->setTime(14, 0, 0);
                    } else {

                        $current_start->setTime(6, 0, 0);
                    }
                }
            }

            // 🔥 kiểm tra ngay từ đầu nếu current_start nằm trong offdate
            foreach ($products as $index => $product) {

                /*
                |--------------------------------------------------------------------------
                | lấy quota
                |--------------------------------------------------------------------------
                */
                if ($index === 0 && $product['stage_code'] !== 9) {
                    if ($product['stage_code'] < 7) {
                        $process_code = $product['intermediate_code'] . '_NA_' . $request->room_id;
                    } elseif ($product['stage_code'] === 7) {
                        $process_code = $product['intermediate_code'] . '_' . $product['finished_product_code'] . '_' . $request->room_id;
                    }

                    $quota = DB::table('quota')
                        ->select(
                            'room_id',
                            'campaign_index',
                            DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                            DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                            DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                            DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes'),
                        )
                        ->where('process_code', 'like', $process_code . '%')
                        ->first();

                    $p_time_minutes = $quota->p_time_minutes ?? 0;
                    $m_time_minutes = $quota->m_time_minutes ?? 0;
                    $C1_time_minutes = $quota->C1_time_minutes ?? 0;
                    $C2_time_minutes = $quota->C2_time_minutes ?? 0;
                } elseif ($index === 0 && $product['stage_code'] === 9) {
                    $p_time_minutes = 30;
                    $m_time_minutes = 60;
                    $C1_time_minutes = 30;
                    $C2_time_minutes = 60;
                }

                // 🔥 Điều chỉnh quota cho công đoạn 7 và only_parkaging
                $p_time_adj = (float) $p_time_minutes;
                $m_time_adj = (float) $m_time_minutes;

                if ($product['stage_code'] == 7) {
                    $pm = DB::table('plan_master')
                        ->where('id', $product['plan_master_id'])
                        ->select('only_parkaging', 'percent_parkaging')
                        ->first();

                    if ($pm) {
                        $ratio = (float) ($pm->percent_parkaging ?? 1);
                        $p_time_adj *= $ratio;
                        $m_time_adj *= $ratio;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | tính thời gian sản xuất + vệ sinh
                |--------------------------------------------------------------------------
                */
                if ($product['stage_code'] <= 2) {

                    $end_man = $current_start->copy()->addMinutes($p_time_adj + $m_time_adj * $quota->campaign_index);

                    $end_clearning = $end_man->copy()->addMinutes((float) $C2_time_minutes);
                    $clearning_type = 'VS-II';
                    $firstBatachStart = $current_start;
                } else {

                    if ($products->count() === 1) {
                        $current_start = $this->skipOffTime($current_start, $this->offDate, $request->room_id);

                        $end_man = $this->addWorkingMinutes($current_start->copy(), $p_time_adj + $m_time_adj, $request->room_id, $this->work_sunday);

                        $start_clearning = $end_man->copy();
                        $end_clearning = $this->addWorkingMinutes($start_clearning->copy(), (float) $C2_time_minutes, $request->room_id, $this->work_sunday);
                        $clearning_type = 'VS-II';

                        $start_date = $end_man;
                        $firstBatachStart = $current_start;
                        $lastBatachEnd = $end_clearning;
                    } else {

                        if ($index === 0) {

                            $end_man = $this->addWorkingMinutes($current_start->copy(), $p_time_adj + $m_time_adj, $request->room_id, $this->work_sunday);
                            $start_clearning = $end_man->copy();
                            $end_clearning = $this->addWorkingMinutes($start_clearning->copy(), (float) $C1_time_minutes, $request->room_id, $this->work_sunday);

                            $start_date = $end_man;
                            $clearning_type = 'VS-I';
                            $firstBatachStart = $current_start;
                        } elseif ($index === $products->count() - 1) {

                            $end_man = $this->addWorkingMinutes($current_start->copy(), $m_time_adj, $request->room_id, $this->work_sunday);
                            $start_clearning = $end_man->copy();
                            $end_clearning = $this->addWorkingMinutes($start_clearning->copy(), (float) $C2_time_minutes, $request->room_id, $this->work_sunday);
                            $clearning_type = 'VS-II';
                            $lastBatachEnd = $end_clearning;
                        } else {

                            $end_man = $this->addWorkingMinutes($current_start->copy(), $m_time_adj, $request->room_id, $this->work_sunday);
                            $start_clearning = $end_man->copy();
                            $end_clearning = $this->addWorkingMinutes($start_clearning->copy(), (float) $C1_time_minutes, $request->room_id, $this->work_sunday);
                            $clearning_type = 'VS-I';
                        }
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | lưu stage_plan
                |--------------------------------------------------------------------------
                */
                if ($product['stage_code'] === 9) {

                    DB::table('stage_plan')
                        ->where('id', $product['id'])
                        ->update([
                            'start' => $current_start,
                            'end' => $end_man,
                            'start_clearning' => $start_clearning,
                            'end_clearning' => $end_clearning,
                            'resourceId' => $request->room_id,
                            'schedualed' => 1,
                            'schedualed_by' => session('user')['fullName'],
                            'schedualed_at' => now(),
                        ]);
                } else {

                    $offDays = DB::table('off_days')
                        ->whereDate('off_date', '<=', $current_start)
                        ->pluck('off_date')
                        ->toArray();

                    $receiveDate = Carbon::parse($current_start)->subDay();

                    while (in_array($receiveDate->toDateString(), $offDays)) {

                        $receiveDate->subDay();
                    }

                    $receiveDateStr = $receiveDate->toDateString();

                    DB::table('stage_plan')
                        ->where('id', $product['id'])
                        ->update([
                            'start' => $current_start,
                            'end' => $end_man,
                            'start_clearning' => $end_man,
                            'end_clearning' => $end_clearning,
                            'resourceId' => $request->room_id,
                            'title' => $product['stage_code'] === 9
                                ? ($product['title'] . '-' . $product['batch'])
                                : ($product['name'] . '-' . $product['batch'] . '-' . $product['market']),
                            'title_clearning' => $clearning_type,
                            'schedualed' => 1,
                            'schedualed_by' => session('user')['fullName'],
                            'schedualed_at' => now(),
                            'receive_packaging_date' => DB::raw("CASE WHEN received = 0 AND stage_code = 7 THEN '$receiveDateStr' ELSE receive_packaging_date END"),
                            'receive_second_packaging_date' => DB::raw("CASE WHEN received_second_packaging = 0 AND stage_code = 7 THEN '$receiveDateStr' ELSE receive_second_packaging_date END"),
                        ]);
                }

                /*
                |--------------------------------------------------------------------------
                | LƯU LỊCH SỬ
                |--------------------------------------------------------------------------
                */
                $submit = DB::table('stage_plan')->where('id', $product['id'])->value('submit');

                if ($submit == 1) {

                    $last_version = DB::table('stage_plan_history')
                        ->where('stage_plan_id', $product['id'])
                        ->max('version') ?? 0;

                    $this->syncPackagingDate($product['id'], $receiveDate, 0, 'SchedualController.store');
                    $this->syncPackagingDate($product['id'], $receiveDate, 1, 'SchedualController.store');

                    $update_row = DB::table('stage_plan')->where('id', $product['id'])->first();
                    if ($update_row) {
                        DB::table('stage_plan_history')->insert([
                            'stage_plan_id' => $product['id'],
                            'plan_list_id' => $update_row->plan_list_id,
                            'plan_master_id' => $update_row->plan_master_id,
                            'product_caterogy_id' => $update_row->product_caterogy_id,
                            'campaign_code' => $update_row->campaign_code,
                            'code' => $update_row->code,
                            'order_by' => $update_row->order_by,
                            'schedualed' => $update_row->schedualed,
                            'stage_code' => $update_row->stage_code,
                            'title' => $update_row->title,
                            'start' => $update_row->start,
                            'end' => $update_row->end,
                            'resourceId' => $update_row->resourceId,
                            'title_clearning' => $update_row->title_clearning,
                            'start_clearning' => $update_row->start_clearning,
                            'end_clearning' => $update_row->end_clearning,
                            'tank' => $update_row->tank,
                            'keep_dry' => $update_row->keep_dry,
                            'AHU_group' => $update_row->AHU_group,
                            'schedualed_by' => $update_row->schedualed_by,
                            'schedualed_at' => $update_row->schedualed_at,
                            'version' => $last_version + 1,
                            'note' => $update_row->note,
                            'deparment_code' => session('user.production_code'),
                            'type_of_change' => $request->reason ?? 'Lập Lịch Thủ Công',
                            'created_date' => now(),
                            'created_by' => session('user')['fullName'],
                        ]);
                    }
                }

                // Cập nhật submit = 0 sau khi lưu lịch sử (Ngoại trừ lịch bảo trì)
                DB::table('stage_plan')
                    ->where('id', $product['id'])
                    ->where('stage_code', '!=', 8)
                    ->update(['submit' => 0]);

                /*
                |--------------------------------------------------------------------------
                | tính current_start cho sản phẩm tiếp theo
                |--------------------------------------------------------------------------
                */
                if ($product['stage_code'] > 2) {

                    $current_start = $end_clearning;
                }

                // 🔥 SAU KHI TĂNG current_start → KIỂM TRA NGÀY OFF
                $current_start = $this->skipOffTime($current_start, $this->offDate, $request->room_id);
            }

            // // set lại mã chiến dịch
            if ($product['stage_code'] == 3) {

                $campaign_code = $products->first()['plan_master_id'];

                DB::table('stage_plan')
                    ->whereIn('plan_master_id', $products->pluck('plan_master_id'))
                    ->update([
                        'campaign_code' => $campaign_code,
                    ]);
            }

            if ($multi_stage) {

                $this->max_Step = 7;

                $totalTimeCampaign = abs($firstBatachStart->diffInMinutes($lastBatachEnd));

                // Làm liên tục các công cộng sau
                $nextcessor_codes = collect();

                $nextTasks = collect();

                $firstTask = $products->first();

                $next_stage_code = isset($firstTask->nextcessor_code)
                    ? (int) (explode('_', $firstTask->nextcessor_code)[1] ?? 0)
                    : 0;

                // $hasimmediately = collect($campaigntasks)->contains('immediately', 1);

                if ($next_stage_code <= $this->max_Step) {
                    // && $firstTask->immediately == 1

                    $nextcessor_codes = $products->pluck('nextcessor_code');

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
                        ->leftJoin('stage_plan as prev', function ($join) {
                            $join->on('prev.code', '=', 'sp.predecessor_code')
                                ->whereNotIn('prev.stage_code', [1, 2]);
                        })
                        ->whereIn('sp.code', $nextcessor_codes)
                        ->where('sp.active', 1)
                        ->where('sp.deparment_code', session('user.production_code'))
                        ->orderBy('prev.start', 'asc')
                        ->get();

                    if ($nextTasks->isNotEmpty()) {

                        $waite_time = 0;

                        if ($nextTasks->contains('is_val', 1)) {

                            $waite_time = 5 * 24 * 60;
                        }

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

            DB::commit();
        } catch (\Exception  $e) {

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
        $production = session('user.production_code');

        $events = $this->getEvents($production, $request->startDate, $request->endDate, true, $this->theory);

        $plan_waiting = $this->getPlanWaiting($production);

        $sumBatchByStage = $this->yield($request->startDate, $request->endDate, 'stage_code');

        return response()->json([
            'events' => $events,
            'plan' => $plan_waiting,
            'sumBatchByStage' => $sumBatchByStage,
        ]);
    }

    public function toggleNotSchedule(Request $request)
    {
        $ids = $request->input('ids');

        $planMasterIds = DB::table('stage_plan')
            ->whereIn('id', $ids)
            ->pluck('plan_master_id');

        DB::table('stage_plan')
            ->whereIn('plan_master_id', $planMasterIds)
            ->update([
                'not_schedule' => DB::raw('CASE WHEN not_schedule = 1 THEN 0 ELSE 1 END')
            ]);

        return response()->json([
            'plan' => $this->getPlanWaiting(session('user.production_code'))
        ]);
    }

    public function history(Request $request)
    {

        try {

            // Lấy dữ liệu lịch sử theo stage_plan_id
            $history_data = DB::table('stage_plan_history')
                ->leftJoin('stage_plan', 'stage_plan_history.stage_plan_id', 'stage_plan.id')
                ->leftJoin('room', 'stage_plan_history.resourceId', 'room.id')
                ->where('stage_plan_id', $request->stage_code_id)
                ->select(
                    'stage_plan_history.*',
                    'stage_plan.title',
                    DB::raw("CONCAT(room.name, ' ', room.code) as room_name")
                )
                ->orderBy('version', 'desc')
                ->get();

            // nếu không có dữ liệu thì trả về version = 0
            if ($history_data->isEmpty()) {

                $history_data = collect([
                    [
                        'version' => 0,
                        'start' => null,
                        'end' => null,
                        'start_clearning' => null,
                        'end_clearning' => null,
                        'schedualed_at' => null,
                    ],
                ]);
            }

            // trả dữ liệu về frontend
            return response()->json([
                'history_data' => $history_data,
            ]);
        } catch (\Exception  $e) {

            Log::error('Lỗi lấy history:', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Không thể lấy dữ liệu history',
            ], 500);
        }
    }

    public function store_maintenance(Request $request)
    {

        DB::beginTransaction();

        try {

            $products = collect($request->products);

            $current_start = Carbon::parse($request->start);

            if ($request->is_HVAC == true) {

                foreach ($products as $index => $product) {

                    if ($index === 0) {

                        $quota = DB::table('maintenance_category')
                            ->where('code', $product['instrument_code'])
                            ->selectRaw('TIME_TO_SEC(quota) / 60 as quota_minutes')
                            ->first();

                        $execute_time_minutes = (int) ($quota->quota_minutes ?? 0);

                        $end_man = $current_start->copy()->addMinutes($execute_time_minutes);

                        $room_id = array_keys($product['permisson_room']);
                    }

                    DB::table('stage_plan')
                        ->where('id', $product['id'])
                        ->update([
                            'start' => $current_start,
                            'end' => $end_man,
                            'resourceId' => $room_id[$index],
                            'title' => $product['name'],
                            'schedualed' => 1,
                            'schedualed_by' => session('user')['fullName'],
                            'schedualed_at' => now(),
                        ]);

                    $submit = DB::table('stage_plan')->where('id', $product['id'])->value('submit');

                    if ($submit === 1) {

                        $latest_history = DB::table('stage_plan_history')
                            ->where('stage_plan_id', $product['id'])
                            ->orderBy('version', 'desc')
                            ->first();

                        $update_row = DB::table('stage_plan')->where('id', $product['id'])->first();
                        if ($update_row) {
                            $should_insert = true;
                            if ($latest_history) {
                                if (
                                    $latest_history->resourceId == $update_row->resourceId &&
                                    $latest_history->start == $update_row->start &&
                                    $latest_history->end == $update_row->end &&
                                    $latest_history->start_clearning == $update_row->start_clearning &&
                                    $latest_history->end_clearning == $update_row->end_clearning
                                ) {
                                    $should_insert = false;
                                }
                            }

                            if ($should_insert) {
                                DB::table('stage_plan_history')
                                    ->insert([
                                        'stage_plan_id' => $product['id'],
                                        'plan_list_id' => $update_row->plan_list_id,
                                        'plan_master_id' => $update_row->plan_master_id,
                                        'product_caterogy_id' => $update_row->product_caterogy_id,
                                        'campaign_code' => $update_row->campaign_code,
                                        'code' => $update_row->code,
                                        'order_by' => $update_row->order_by,
                                        'schedualed' => $update_row->schedualed,
                                        'stage_code' => $update_row->stage_code,
                                        'title' => $update_row->title,
                                        'start' => $update_row->start,
                                        'end' => $update_row->end,
                                        'resourceId' => $update_row->resourceId,
                                        'title_clearning' => $update_row->title_clearning,
                                        'start_clearning' => $update_row->start_clearning,
                                        'end_clearning' => $update_row->end_clearning,
                                        'tank' => $update_row->tank,
                                        'keep_dry' => $update_row->keep_dry,
                                        'AHU_group' => $update_row->AHU_group,
                                        'schedualed_by' => $update_row->schedualed_by,
                                        'schedualed_at' => $update_row->schedualed_at,
                                        'version' => $latest_history ? $latest_history->version + 1 : 1,
                                        'note' => $update_row->note,
                                        'deparment_code' => session('user.production_code'),
                                        'type_of_change' => $this->reason ?? 'Lập Lịch Thủ Công',
                                        'created_date' => now(),
                                        'created_by' => session('user')['fullName'],
                                    ]);
                            }
                        }
                    }
                }
            } else {

                foreach ($products as $index => $product) {

                    $quota = DB::table('maintenance_category')
                        ->where('code', $product['instrument_code'])
                        ->selectRaw('TIME_TO_SEC(quota) / 60 as quota_minutes')
                        ->first();

                    $execute_time_minutes = (int) ($quota->quota_minutes ?? 0);

                    $end_man = $current_start->copy()->addMinutes($execute_time_minutes);

                    $room_id = array_keys($product['permisson_room']);

                    DB::table('stage_plan')
                        ->where('id', $product['id'])
                        ->update([
                            'start' => $current_start,
                            'end' => $end_man,
                            'resourceId' => $room_id[0],
                            'title' => $product['name'],
                            'schedualed' => 1,
                            'schedualed_by' => session('user')['fullName'],
                            'schedualed_at' => now(),
                        ]);

                    $update_row = DB::table('stage_plan')->where('id', $product['id'])->first();

                    if ($update_row->submit === 1) {

                        $latest_history = DB::table('stage_plan_history')
                            ->where('stage_plan_id', $product['id'])
                            ->orderBy('version', 'desc')
                            ->first();

                        $should_insert = true;
                        if ($latest_history) {
                            if (
                                $latest_history->resourceId == $update_row->resourceId &&
                                $latest_history->start == $update_row->start &&
                                $latest_history->end == $update_row->end &&
                                $latest_history->start_clearning == $update_row->start_clearning &&
                                $latest_history->end_clearning == $update_row->end_clearning
                            ) {
                                $should_insert = false;
                            }
                        }

                        if ($should_insert) {
                            DB::table('stage_plan_history')
                                ->insert([
                                    'stage_plan_id' => $product['id'],
                                    'plan_list_id' => $update_row->plan_list_id,
                                    'plan_master_id' => $update_row->plan_master_id,
                                    'product_caterogy_id' => $update_row->product_caterogy_id,
                                    'campaign_code' => $update_row->campaign_code,
                                    'code' => $update_row->code,
                                    'order_by' => $update_row->order_by,
                                    'schedualed' => $update_row->schedualed,
                                    'stage_code' => $update_row->stage_code,
                                    'title' => $update_row->title,
                                    'start' => $update_row->start,
                                    'end' => $update_row->end,
                                    'resourceId' => $update_row->resourceId,
                                    'title_clearning' => $update_row->title_clearning,
                                    'start_clearning' => $update_row->start_clearning,
                                    'end_clearning' => $update_row->end_clearning,
                                    'tank' => $update_row->tank,
                                    'keep_dry' => $update_row->keep_dry,
                                    'AHU_group' => $update_row->AHU_group,
                                    'schedualed_by' => $update_row->schedualed_by,
                                    'schedualed_at' => $update_row->schedualed_at,
                                    'version' => $latest_history ? $latest_history->version + 1 : 1,
                                    'note' => $update_row->note,
                                    'deparment_code' => session('user.production_code'),
                                    'type_of_change' => $request->reason,
                                    'created_date' => now(),
                                    'created_by' => session('user')['fullName'],
                                ]);
                        }
                    }

                    $current_start = $end_man;
                }
            }

            DB::commit();
        } catch (\Exception  $e) {
            DB::rollBack();

            Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error',  'message' => $e->getMessage()], 500);
        }

        $production = session('user.production_code');

        $events = $this->getEvents($production, $request->startDate, $request->endDate, true, $this->theory);

        $plan_waiting = $this->getPlanWaiting($production);

        $sumBatchByStage = $this->yield($request->startDate, $request->endDate, 'stage_code');

        return response()->json([
            'events' => $events,
            'plan' => $plan_waiting,
            'sumBatchByStage' => $sumBatchByStage,
        ]);
    }

    public function update(Request $request)
    {

        //Log::info($request->all());
        //return;
        $offDays = DB::table('off_days')
            ->whereDate('off_date', '>=', now())
            ->pluck('off_date')
            ->toArray();

        $changes = $request->input('changes', []);
        $this->theory = (int) $request->theory ?? 0;

        try {
            DB::beginTransaction();
            // Lưu lý do một lần duy nhất nếu cần
            if (is_array($request->reason) && ($request->reason['saveReason'] ?? false)) {
                DB::table('reason')->updateOrInsert(
                    [
                        'name' => $request->reason['reason'],
                        'deparment_code' => session('user.production_code'),
                    ],
                    [
                        'created_by' => session('user')['fullName'],
                        'created_at' => now(),
                    ]
                );
            }

            $cascade = $request->input('cascade', false);
            $allManualIds = [];
            $alreadyCascadedIds = []; // Tránh dịch chuyển lặp lại trong cùng 1 request (đặc biệt khi drop theo nhóm)
            foreach ($changes as $c) {
                $parts = explode('-', $c['id']);
                $ids = explode(',', $parts[0]);
                $allManualIds = array_merge($allManualIds, $ids);
            }

            $sharedStart = null;
            $sharedResource = null;
            $isMoveSelectedBatches = $request->input('move_selected_batches', false);

            foreach ($changes as $change) {
                $idParts = explode('-', $change['id']);
                $realId = $idParts[0] ?? null;
                $type = $idParts[1] ?? null;

                if (! $realId) {
                    continue;
                }

                if ($type && strpos($type, 'cleaning') !== false) {
                    DB::table('stage_plan')
                        ->whereIn('id', explode(',', $realId))
                        ->update([
                            'start_clearning' => $change['start'],
                            'end_clearning' => $change['end'],
                            'resourceId' => $change['resourceId'],
                        ]);
                } else {
                    $receiveDate = Carbon::parse($change['start'])->subDay();
                    while (in_array($receiveDate->toDateString(), $offDays)) {
                        $receiveDate->subDay();
                    }
                    $receiveDateStr = $receiveDate->toDateString();
                    $idsArray = explode(',', $realId);
                    $original_event = DB::table('stage_plan')->where('id', $idsArray[0])->first();

                    $updateData = [
                        'schedualed_by' => session('user')['fullName'],
                        'schedualed_at' => now(),
                        'accept_quarantine' => 0,
                        'receive_packaging_date' => DB::raw("CASE WHEN received = 0 AND stage_code = 7 THEN '$receiveDateStr' ELSE receive_packaging_date END"),
                        'receive_second_packaging_date' => DB::raw("CASE WHEN received_second_packaging = 0 AND stage_code = 7 THEN '$receiveDateStr' ELSE receive_second_packaging_date END"),
                    ];

                    // 🔹 [Audit] Đánh dấu nếu Phân xưởng (PXV) tác động vào lịch Bảo trì - Hiệu chuẩn (stage_code 8)
                    if (strpos(session('user.production_code'), 'PXV') === 0) {
                        $updateData['keep_dry'] = DB::raw("CASE WHEN stage_code = 8 THEN 1 ELSE keep_dry END");
                    }

                    if ($request->input('update_campaign', false) && $original_event) {
                        $main_parkaging_id = DB::table('plan_master')->where('id', $original_event->plan_master_id)->value('main_parkaging_id');
                        
                        $root_event = DB::table('stage_plan')
                            ->where('plan_master_id', $main_parkaging_id)
                            ->where('stage_code', $original_event->stage_code)
                            ->first();
                            
                        $baseEvents = collect();
                        if ($root_event && $root_event->campaign_code) {
                            $baseEvents = DB::table('stage_plan')
                                ->where('campaign_code', $root_event->campaign_code)
                                ->where('stage_code', $original_event->stage_code)
                                ->where('finished', 0)
                                ->orderBy('start')
                                ->get();
                        } else {
                            if ($root_event && $root_event->finished == 0) {
                                $baseEvents = collect([$root_event]);
                            } else {
                                $baseEvents = collect([$original_event]);
                            }
                        }

                        $main_ids = $baseEvents->pluck('plan_master_id')->unique();

                        $subTasks = DB::table('stage_plan')
                            ->join('plan_master', 'stage_plan.plan_master_id', '=', 'plan_master.id')
                            ->whereIn('plan_master.main_parkaging_id', $main_ids)
                            ->whereNotIn('stage_plan.plan_master_id', $main_ids)
                            ->where('stage_plan.stage_code', $original_event->stage_code)
                            ->where('stage_plan.finished', 0)
                            ->select('stage_plan.*', 'plan_master.main_parkaging_id', 'plan_master.id as pm_id')
                            ->orderBy('plan_master.id')
                            ->get();

                        $campaignEvents = collect();
                        foreach ($baseEvents as $ev) {
                            $campaignEvents->push($ev);
                            $subs = $subTasks->where('main_parkaging_id', $ev->plan_master_id);
                            foreach ($subs as $sub) {
                                $campaignEvents->push($sub);
                            }
                        }

                        $currentStart = Carbon::parse($change['start']);
                        $idsArray = []; // Cập nhật danh sách ID để ghi log history ở phía dưới
                        $newMTime = $request->input('newMTime');
                        $pTime = $request->input('pTime', 0);

                        foreach ($campaignEvents as $ev) {
                            if ($newMTime > 0) {
                                $reqMTime = $newMTime;
                                $reqPTime = $pTime;
                                
                                if ($ev->stage_code == 7) {
                                    $pm = DB::table('plan_master')->where('id', $ev->plan_master_id)->select('percent_parkaging')->first();
                                    if ($pm) {
                                        $ratio = (float) ($pm->percent_parkaging ?? 1);
                                        $reqMTime = $reqMTime * $ratio;
                                        $reqPTime = $reqPTime * $ratio;
                                    }
                                }
                                
                                // Tính thời lượng mới bằng reqMTime + reqPTime (nếu là mẻ đầu hoặc VS-II)
                                $durationHours = ($ev->first_in_campaign == 1 || $ev->title_clearning == "VS-II") ? ($reqMTime + $reqPTime) : $reqMTime;
                                $duration = $durationHours * 3600; // Đổi ra giây
                            } else {
                                $duration = Carbon::parse($ev->start)->diffInSeconds(Carbon::parse($ev->end));
                            }

                            $newStart = $currentStart->copy();
                            $newEnd = $newStart->copy()->addSeconds($duration);

                            $evUpdateData = $updateData;
                            $evUpdateData['start'] = $newStart;
                            $evUpdateData['end'] = $newEnd;
                            $evUpdateData['resourceId'] = $change['resourceId'];

                            if ($ev->start_clearning && $ev->end_clearning) {
                                $cleanDuration = Carbon::parse($ev->start_clearning)->diffInSeconds(Carbon::parse($ev->end_clearning));
                                $evUpdateData['start_clearning'] = $newEnd;
                                $evUpdateData['end_clearning'] = $newEnd->copy()->addSeconds($cleanDuration);
                                $currentStart = $evUpdateData['end_clearning']->copy();
                            } else {
                                $currentStart = $newEnd->copy();
                            }

                            DB::table('stage_plan')->where('id', $ev->id)->update($evUpdateData);
                            $idsArray[] = $ev->id;
                        }
                    } else {
                        if ($isMoveSelectedBatches && $sharedStart) {
                            $updateData['start'] = $sharedStart->copy();
                        } else {
                            $updateData['start'] = Carbon::parse($change['start']);
                        }
                        
                        if ($isMoveSelectedBatches && $sharedResource) {
                            $updateData['resourceId'] = $sharedResource;
                        } else {
                            $updateData['resourceId'] = $change['resourceId'];
                            if ($isMoveSelectedBatches && !$sharedResource) {
                                $sharedResource = $change['resourceId'];
                            }
                        }

                        $reqNewMTime = (float) $request->input('newMTime');
                        if ($reqNewMTime > 0) {
                            $reqPTime = (float) $request->input('pTime', 0);
                            $task_ratio = 1;
                            if ($original_event && $original_event->stage_code == 7) {
                                $pm = DB::table('plan_master')->where('id', $original_event->plan_master_id)->select('percent_parkaging')->first();
                                if ($pm) {
                                    $task_ratio = (float) ($pm->percent_parkaging ?? 1);
                                }
                            }
                            $reqNewMTime = $reqNewMTime * $task_ratio;
                            $reqPTime = $reqPTime * $task_ratio;
                            
                            $durationHours = ($original_event->first_in_campaign == 1 || $original_event->title_clearning == "VS-II") ? ($reqNewMTime + $reqPTime) : $reqNewMTime;
                            $updateData['end'] = $updateData['start']->copy()->addSeconds($durationHours * 3600);
                        } else {
                            if ($isMoveSelectedBatches && $sharedStart) {
                                $duration = Carbon::parse($change['start'])->diffInSeconds(Carbon::parse($change['end']));
                                $updateData['end'] = $updateData['start']->copy()->addSeconds($duration);
                            } else {
                                $updateData['end'] = Carbon::parse($change['end']);
                            }
                        }

                        DB::table('stage_plan')
                            ->whereIn('id', $idsArray)
                            ->update($updateData);

                        foreach ($idsArray as $sid) {
                            $update_row = DB::table('stage_plan')->where('id', $sid)->first();

                            // 🔹 Cập nhật cleaning ngay sau khi event kết thúc (luôn áp dụng cho move_selected_batches hoặc PXV1)
                            if (($isMoveSelectedBatches || session('user.production_code') == 'PXV1') && $update_row->start_clearning && $update_row->end_clearning) {
                                $durationSeconds = Carbon::parse($update_row->start_clearning)->diffInSeconds(Carbon::parse($update_row->end_clearning));
                                $new_start_clearning = $updateData['end']->copy();
                                $new_end_clearning = $new_start_clearning->copy()->addSeconds($durationSeconds);

                                DB::table('stage_plan')->where('id', $sid)->update([
                                    'start_clearning' => $new_start_clearning,
                                    'end_clearning' => $new_end_clearning,
                                ]);
                                
                                if ($isMoveSelectedBatches) {
                                    $sharedStart = $new_end_clearning->copy();
                                }
                            } else {
                                if ($isMoveSelectedBatches) {
                                    $sharedStart = $updateData['end']->copy();
                                }
                            }
                        }
                    }

                    // 🔹 [Source of Truth: Frontend] 
                    // Toàn bộ logic Cascade đã được tính toán ở Frontend và gửi về qua mảng 'changes'.
                    // Loại bỏ cơ chế tự động tịnh tiến ở Backend để tránh sai lệch dữ liệu.



                    foreach ($idsArray as $sid) {

                        $update_row = DB::table('stage_plan')->where('id', $sid)->first();



                        if ($update_row && $update_row->submit == 1) {

                            $this->syncPackagingDate($sid, $receiveDate, 0, 'SchedualController.multiStore');
                            $this->syncPackagingDate($sid, $receiveDate, 1, 'SchedualController.multiStore');

                            try {
                                DB::table('stage_plan_history')
                                    ->insert([
                                        'stage_plan_id' => $sid,
                                        'plan_list_id' => $update_row->plan_list_id,
                                        'plan_master_id' => $update_row->plan_master_id,
                                        'product_caterogy_id' => $update_row->product_caterogy_id,
                                        'campaign_code' => $update_row->campaign_code,
                                        'code' => $update_row->code,
                                        'order_by' => $update_row->order_by,
                                        'schedualed' => $update_row->schedualed,
                                        'stage_code' => $update_row->stage_code,
                                        'title' => $update_row->title,
                                        'start' => $update_row->start,
                                        'end' => $update_row->end,
                                        'resourceId' => $update_row->resourceId,
                                        'title_clearning' => $update_row->title_clearning,
                                        'start_clearning' => $update_row->start_clearning,
                                        'end_clearning' => $update_row->end_clearning,
                                        'tank' => $update_row->tank,
                                        'keep_dry' => $update_row->keep_dry,
                                        'AHU_group' => $update_row->AHU_group,
                                        'schedualed_by' => $update_row->schedualed_by,
                                        'schedualed_at' => $update_row->schedualed_at,
                                        'version' => DB::table('stage_plan_history')->where('stage_plan_id', $sid)->max('version') + 1 ?? 1,
                                        'note' => $update_row->note,
                                        'deparment_code' => session('user.production_code'),
                                        'type_of_change' => $request->reason['reason'] ?? null,
                                        'created_date' => now(),
                                        'created_by' => session('user')['fullName'],
                                    ]);
                            } catch (\Exception $he) {
                                Log::error('[History Debug] INSERT FAILED for sid=' . $sid, ['error' => $he->getMessage()]);
                            }
                        } else {
                            Log::info('[History Debug] SKIP sid=' . $sid . ' (submit=' . ($update_row->submit ?? 'NULL') . ')');
                        }

                        // Cập nhật submit = 0 sau khi lưu lịch sử (Ngoại trừ lịch bảo trì)
                        DB::table('stage_plan')
                            ->where('id', $sid)
                            ->where('stage_code', '!=', 8)
                            ->update(['submit' => 0]);
                    }
                }
            }
            DB::commit();

            // ✅ Auto-reset expected_date_change = 0 cho các plan_master không còn vi phạm KCS
            // Sau khi lưu lịch thành công, kiểm tra lại các lô bị ảnh hưởng:
            // Nếu MAX(stage_plan.end) của stage_code=7 đã đáp ứng (end <= expected_date - 5 ngày),
            // tự động reset cờ expected_date_change về 0 để lô không còn "treo" ở tab Đề Nghị.
            try {
                // Thu thập tất cả plan_master_id từ các stage_plan vừa được cập nhật
                $affectedStagePlanIds = [];
                foreach ($changes as $c) {
                    $parts = explode('-', $c['id']);
                    $type = $parts[1] ?? null;
                    // Chỉ quan tâm đến stage_code=7 (đóng gói) vì đó là stage quyết định KCS
                    if (!$type || strpos($type, 'cleaning') === false) {
                        $idsInChange = explode(',', $parts[0]);
                        foreach ($idsInChange as $sid) {
                            if (is_numeric(trim($sid))) {
                                $affectedStagePlanIds[] = (int) trim($sid);
                            }
                        }
                    }
                }

                if (!empty($affectedStagePlanIds)) {
                    // Lấy plan_master_id của các stage_plan bị ảnh hưởng và có stage_code = 7
                    $affectedPlanMasterIds = DB::table('stage_plan')
                        ->whereIn('id', $affectedStagePlanIds)
                        ->where('stage_code', 7)
                        ->pluck('plan_master_id')
                        ->unique()
                        ->toArray();

                    if (!empty($affectedPlanMasterIds)) {
                        // Tìm các plan_master đang có cờ expected_date_change = 1
                        $proposedPlans = DB::table('plan_master')
                            ->whereIn('id', $affectedPlanMasterIds)
                            ->where('expected_date_change', 1)
                            ->select('id', 'expected_date')
                            ->get();

                        foreach ($proposedPlans as $pm) {
                            if (!$pm->expected_date) continue;

                            // Kiểm tra xem MAX(end) của stage_code=7 có còn vi phạm không
                            $maxEnd = DB::table('stage_plan')
                                ->where('plan_master_id', $pm->id)
                                ->where('stage_code', 7)
                                ->where('active', 1)
                                ->where('finished', 0)
                                ->whereNotNull('start')
                                ->max('end');

                            if (!$maxEnd) continue;

                            $kcsDeadline = Carbon::parse($pm->expected_date)->subDays(5)->startOfDay();
                            $endDate = Carbon::parse($maxEnd)->startOfDay();

                            // Nếu ngày kết thúc đã đáp ứng (không còn vi phạm) → reset cờ
                            if ($endDate->lte($kcsDeadline)) {
                                DB::table('plan_master')
                                    ->where('id', $pm->id)
                                    ->update(['expected_date_change' => 0]);

                                Log::info("[SchedualController] Auto-reset expected_date_change=0 for plan_master_id={$pm->id} (maxEnd={$maxEnd}, deadline={$kcsDeadline})");
                            }
                        }
                    }
                }
            } catch (\Exception $resetEx) {
                // Lỗi reset cờ không nên làm hỏng toàn bộ response
                Log::warning('[SchedualController] Auto-reset expected_date_change failed: ' . $resetEx->getMessage());
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json(['error' => 'Lỗi hệ thống'], 500);
        }

        $production = session('user.production_code');

        // Nếu startDate = null → đây là batch trung gian (không phải batch cuối)
        // → bỏ qua việc load lại events (rất nặng) để tránh timeout
        if (!$request->startDate || !$request->endDate) {
            return response()->json(['status' => 'ok', 'batch' => 'intermediate']);
        }

        $events = $this->getEvents($production, $request->startDate, $request->endDate, true, $this->theory);
        $sumBatchByStage = $this->yield($request->startDate, $request->endDate, 'stage_code');
        $resources = $this->getResources($production, $request->startDate, $request->endDate);

        return response()->json([
            'events' => $events,
            'resources' => $resources,
            'sumBatchByStage' => $sumBatchByStage,
        ]);
    }

    public function updateClearning(Request $request)
    {

        $changes = $request->input('changes', []);

        try {

            foreach ($changes as $change) {

                // Tách id: "102-main" -> 102
                $idParts = explode('-', $change['id']);

                $realId = $idParts[0] ?? null;

                if (! $realId) {

                    continue;
                    // bỏ qua nếu id không hợp lệ
                }

                // Nếu là sự kiện vệ sinh (title chứa "VS-")

                DB::table('stage_plan')
                    ->where('id', $realId)
                    ->update([
                        'start_clearning' => $change['start'],
                        'end_clearning' => $change['end'],
                        'resourceId' => $change['resourceId'],
                        'schedualed_by' => session('user')['fullName'],
                        'schedualed_at' => now(),
                    ]);
            }
        } catch (\Exception  $e) {

            Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Lỗi hệ thống'], 500);
        }

        $production = session('user.production_code');

        $events = $this->getEvents($production, $request->startDate, $request->endDate, true, $this->theory);

        $plan_waiting = $this->getPlanWaiting($production);

        $sumBatchByStage = $this->yield($request->startDate, $request->endDate, 'stage_code');

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

                $rowId = explode('-', $item['id'])[0];
                // lấy id trước dấu -
                $stageCode = $item['stage_code'];

                if ($stageCode <= 2 || $stageCode >= 8) {

                    // chỉ cóa cân k xóa các công đoạn khác

                    DB::table('stage_plan')
                        ->whereIn('id', explode(',', $rowId))
                        ->where('finished', 0)
                        ->update([
                            'start' => null,
                            'end' => null,
                            'start_clearning' => null,
                            'end_clearning' => null,
                            'resourceId' => null,
                            'title' => null,
                            'title_clearning' => null,
                            'accept_quarantine' => 0,
                            'schedualed' => 0,
                            'AHU_group' => 0,
                            'schedualed_by' => session('user')['fullName'],
                            'schedualed_at' => now(),
                            'submit' => 0,
                        ]);
                } else {

                    $plan = DB::table('stage_plan')->where('id', $rowId)->first();

                    DB::table('stage_plan')
                        ->where('finished', 0)
                        ->where('plan_master_id', $plan->plan_master_id)
                        ->where('stage_code', '>=', $stageCode)
                        ->where('stage_code', '!=', 8) // CHẶN: không xóa lan tỏa tới bảo trì
                        ->update([
                            'start' => null,
                            'end' => null,
                            'start_clearning' => null,
                            'end_clearning' => null,
                            'resourceId' => null,
                            'title' => null,
                            'title_clearning' => null,
                            'accept_quarantine' => 0,
                            'schedualed' => 0,
                            'schedualed_by' => session('user')['fullName'],
                            'schedualed_at' => now(),
                            'submit' => 0,
                        ]);
                }
            }
        } catch (\Exception  $e) {

            Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error',  'message' => $e->getMessage()], 500);
        }

        $production = session('user.production_code');

        $events = $this->getEvents($production, $request->startDate, $request->endDate, true, $this->theory);

        $plan_waiting = $this->getPlanWaiting($production);

        $sumBatchByStage = $this->yield($request->startDate, $request->endDate, 'stage_code');

        $resources = $this->getResources($production, $request->startDate, $request->endDate);

        return response()->json([
            'events' => $events,
            'plan' => $plan_waiting,
            'resources' => $resources,
            'sumBatchByStage' => $sumBatchByStage,
        ]);
    }

    public function deActiveAll(Request $request)
    {

        $production = session('user.production_code');

        try {

            if ($request->mode == 'step') {

                if ($request->selectedStep == 'CNL') {

                    $ids = DB::table('stage_plan')
                        ->where('deparment_code', $production)
                        ->whereNotNull('start')
                        ->where('start', '>=', $request->start_date)
                        ->where('active', 1)
                        ->where('finished', 0)
                        ->where('stage_code', '<=', 2)
                        ->pluck('id');
                } else {

                    $Step = ['PC' => 3,  'THT' => 4,  'ĐH' => 5,  'BP' => 6,  'ĐG' => 7];

                    $stage_code = $Step[$request->selectedStep];

                    $ids = DB::table('stage_plan')
                        ->where('deparment_code', $production)
                        ->whereNotNull('start')
                        ->where('start', '>=', $request->start_date)
                        ->where('active', 1)
                        ->where('finished', 0)
                        ->where('stage_code', '>=', $stage_code)
                        ->pluck('id');
                }
            } elseif ($request->mode == 'resource') {

                $stage_code = DB::table('room')->where('id', $request->resourceId)->value('stage_code');

                if ($stage_code >= 3) {

                    $plan_master_ids = DB::table('stage_plan')
                        ->where('resourceId', '=', $request->resourceId)
                        ->where('deparment_code', $production)
                        ->whereNotNull('start')
                        ->where('start', '>=', $request->start_date)
                        ->where('active', 1)
                        ->where('finished', 0)
                        ->pluck('plan_master_id');

                    $ids = DB::table('stage_plan')
                        ->whereIn('plan_master_id', $plan_master_ids)
                        ->where('stage_code', '>=', $stage_code)
                        ->pluck('id');
                } else {

                    $ids = DB::table('stage_plan')
                        ->where('resourceId', '=', $request->resourceId)
                        ->where('deparment_code', $production)
                        ->whereNotNull('start')
                        ->where('start', '>=', $request->start_date)
                        ->where('active', 1)
                        ->where('finished', 0)
                        ->pluck('id');
                }
            }

            if ($ids->isEmpty()) {

                $production = session('user.production_code');

                $events = $this->getEvents($production, $request->startDate, $request->endDate, true, $this->theory);

                $plan_waiting = $this->getPlanWaiting($production);

                $sumBatchByStage = $this->yield($request->startDate, $request->endDate, 'stage_code');

                $resources = $this->getResources($production, $request->startDate, $request->endDate);

                return response()->json([
                    'events' => $events,
                    'plan' => $plan_waiting,
                    'resources' => $resources,
                    'sumBatchByStage' => $sumBatchByStage,
                ]);
            }

            if ($ids->isNotEmpty()) {

                // Lấy danh sách campaign_code + stage_code của các dòng bị xoá
                $deletedRows = DB::table('stage_plan')
                    ->where('deparment_code', $production)
                    ->whereIn('id', $ids)
                    ->select('campaign_code', 'stage_code')
                    ->get();

                // Lấy thêm các id khác cùng campaign_code & stage_code, start < start_date
                $relatedIds = DB::table('stage_plan')
                    ->where('deparment_code', $production)
                    ->where(function ($query) use ($deletedRows) {

                        foreach ($deletedRows as $row) {

                            $query->orWhere(function ($q) use ($row) {

                                $q->where('campaign_code', $row->campaign_code)
                                    ->where('stage_code', $row->stage_code);
                            });
                        }
                    })
                    ->where('start', '<', $request->start_date)
                    ->pluck('id');

                // Gộp danh sách id lại
                $ids = $ids->merge($relatedIds)->unique();
            }

            DB::table('stage_plan')
                ->whereIn('id', $ids)
                ->where('stage_code', '!=', 8) // CHẶN: không xóa trắng công đoạn bảo trì
                ->update([
                    'start' => null,
                    'end' => null,
                    'start_clearning' => null,
                    'end_clearning' => null,
                    'resourceId' => null,
                    'title_clearning' => null,
                    'accept_quarantine' => 0,
                    'schedualed' => 0,
                    'AHU_group' => 0,
                    'schedualed_by' => session('user')['fullName'],
                    'schedualed_at' => now(),
                    'submit' => 0,
                ]);
        } catch (\Exception  $e) {

            Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error',  'message' => $e->getMessage()], 500);
        }

        $production = session('user.production_code');

        $events = $this->getEvents($production, $request->startDate, $request->endDate, true, $this->theory);

        $plan_waiting = $this->getPlanWaiting($production);

        $sumBatchByStage = $this->yield($request->startDate, $request->endDate, 'stage_code');

        return response()->json([
            'events' => $events,
            'plan' => $plan_waiting,
            'sumBatchByStage' => $sumBatchByStage,
        ]);
    }

    public function finished(Request $request)
    {

        $ids = $request->id;

        try {

            if (isset($request->temp)) {

                foreach ($ids as $id) {

                    DB::table('stage_plan')
                        ->where('plan_master_id', $id)
                        ->where('stage_code', '<=', $request->stage_code)
                        ->update([
                            'finished' => 1,
                        ]);
                }
            } else {

                DB::table('stage_plan')
                    ->where('id', $ids)
                    ->update([
                        'quarantine_room_code' => $request->room,
                        'yields' => $request->input('yields'),
                        'finished' => 1,
                    ]);
            }
        } catch (\Exception  $e) {

            Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error',  'message' => $e->getMessage()], 500);
        }

        $production = session('user.production_code');

        if (isset($request->temp)) {

            $plan_waiting = $this->getPlanWaiting($production);

            return response()->json([
                'plan_waiting' => $plan_waiting,
            ]);
        } else {

            $events = $this->getEvents($production, $request->startDate, $request->endDate, true, $this->theory);

            return response()->json([
                'events' => $events,
            ]);
        }
    }

    public function updateOrder(Request $request)
    {

        $data = $request->input('updateOrderData');
        // lấy đúng mảng
        $column_order = 'order_by';

        if ($request->isShowLine) {

            $column_order = 'order_by_line';
        }

        $cases = [];

        $codes = [];

        foreach ($data as $item) {

            $code = $item['code'];
            // vì $item bây giờ là array thực sự
            $orderBy = $item['order_by'];

            $cases[$code] = $orderBy;
            // dùng cho CASE WHEN
            $codes[] = $code;
            // dùng cho WHERE IN
        }

        $updateQuery = "UPDATE stage_plan SET $column_order = CASE code ";

        foreach ($cases as $code => $orderBy) {

            $updateQuery .= "WHEN '{$code}' THEN {$orderBy} ";
        }

        $updateQuery .= "END WHERE code IN ('" . implode("','", $codes) . "')";

        DB::statement($updateQuery);

        return response()->json([
            'plan' => $this->getPlanWaiting(session('user.production_code'), $request->isShowLine),
        ]);
    }

    public function createManualCampain(Request $request)
    {

        $datas = $request->input('data');

        $modeCreate = true;

        $firstCode = null;

        try {

            if ($datas && count($datas) > 0) {

                foreach ($datas as $data) {

                    if ($data['campaign_code'] !== null) {

                        $modeCreate = false;

                        $firstCode = $data['campaign_code'];

                        break;
                    }
                }

                if ($modeCreate === true && count($datas) > 1) {

                    $firstCode = $datas[0]['predecessor_code'];

                    if ($firstCode === null) {

                        $firstCode = '0_' . $datas[0]['code'];
                    }

                    $ids = collect($datas)->pluck('id')->toArray();

                    DB::table('stage_plan')
                        ->whereIn('id', $ids)
                        ->update([
                            'campaign_code' => $firstCode,
                        ]);
                } else {

                    DB::table('stage_plan')
                        ->where('campaign_code', $firstCode)
                        ->update([
                            'campaign_code' => null,
                        ]);
                }
            }
        } catch (\Exception  $e) {

            Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Lỗi hệ thống'], 500);
        }

        return response()->json([
            'plan' => $this->getPlanWaiting(session('user.production_code')),
        ]);
    }

    public function immediately(Request $request)
    {

        $datas = $request->input('data', []);

        $modeCreate = true;
        // mặc định true
        try {

            // không có dữ liệu → bỏ qua
            if (empty($datas)) {

                return response()->json(['error' => 'No data'], 400);
            }

            // 1. kiểm tra nếu bất kỳ dòng nào đang có immediately = true
            foreach ($datas as $data) {

                if ($data['immediately'] == true) {

                    $modeCreate = false;

                    break;
                }
            }

            // 2. Nếu KHÔNG có dòng nào có immediately → BẬT cho tất cả
            $ids = collect($datas)->pluck('id')->filter()->toArray();

            DB::table('stage_plan')
                ->whereIn('id', $ids)
                ->update([
                    'immediately' => $modeCreate,
                ]);
        } catch (\Exception  $e) {

            Log::error('Lỗi cập nhật sự kiện immediately:', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json(['error' => 'Lỗi hệ thống'], 500);
        }

        // trả lại dữ liệu mới
        return response()->json([
            'plan' => $this->getPlanWaiting(session('user.production_code')),
        ]);
    }

    public function clearningValidation(Request $request)
    {

        $ids = $request->ids;

        if (is_array($ids)) {

            $ids = array_values($ids);
        }

        if (empty($ids)) {

            return response()->json(['error' => 'No id provided'], 400);
        }

        try {

            DB::table('stage_plan')
                ->whereIn('id', $ids)
                ->update([
                    'clearning_validation' => DB::raw('NOT clearning_validation'),
                ]);
        } catch (\Exception  $e) {

            Log::error('Lỗi toggle clearning_validation', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json(['error' => 'Lỗi hệ thống'], 500);
        }

        $events = $this->getEvents(session('user.production_code'), $request->startDate, $request->endDate, true, $this->theory);

        return response()->json([
            'events' => $events,

        ]);
    }

    public function cleaninglevelchange(Request $request)
    {

        $ids = $request->ids;

        if (is_array($ids)) {

            $ids = array_values($ids);
        }

        if (empty($ids)) {

            return response()->json(['error' => 'No id provided'], 400);
        }

        try {

            $clearning_type = $request->clearning_type;

            $this->loadOffDate('asc');

            foreach ($ids as $id) {

                // 1. Lấy thông tin hiện tại của stage_plan để xác định process_code và thời gian bắt đầu vệ sinh
                $plan = DB::table('stage_plan as sp')
                    ->leftJoin('finished_product_category as fpc', 'sp.product_caterogy_id', '=', 'fpc.id')
                    ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                    ->where('sp.id', $id)
                    ->select(
                        'sp.id',
                        'sp.stage_code',
                        'sp.resourceId',
                        'sp.end',  // Thời gian kết thúc sản xuất = Bắt đầu vệ sinh
                        'fpc.intermediate_code',
                        'fpc.finished_product_code'
                    )
                    ->first();

                if (! $plan) {
                    continue;
                }

                // 2. xác định process_code để tra cứu quota
                if ($plan->stage_code < 7) {

                    $process_code = $plan->intermediate_code . '_NA_' . $plan->resourceId;
                } elseif ($plan->stage_code === 7) {

                    $process_code = $plan->intermediate_code . '_' . $plan->finished_product_code . '_' . $plan->resourceId;
                } else {

                    // Với các stage_code >= 8 (bảo trì hoặc khác), chỉ cập nhật title
                    DB::table('stage_plan')->where('id', $id)->update(['title_clearning' => $clearning_type]);

                    continue;
                }

                // 3. Tra cứu quota
                $quota = DB::table('quota')
                    ->select(
                        DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                        DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                    )
                    ->where('process_code', 'like', $process_code . '%')
                    ->first();

                if ($quota) {

                    $duration = ($clearning_type === 'VS-I') ? (float) $quota->C1_time_minutes : (float) $quota->C2_time_minutes;

                    // 4. Cập nhật start_clearning (bằng thời gian kết thúc sản xuất) và end_clearning
                    $start_clearning = Carbon::parse($plan->end);

                    $new_end_clearning = $this->addWorkingMinutes($start_clearning->copy(), $duration, $plan->resourceId, $this->work_sunday);

                    DB::table('stage_plan')
                        ->where('id', $id)
                        ->update([
                            'title_clearning' => $clearning_type,
                            'start_clearning' => $start_clearning,
                            'end_clearning' => $new_end_clearning,
                        ]);
                } else {

                    // Nếu không tìm thấy quota, chỉ cập nhật tên cấp vệ sinh
                    DB::table('stage_plan')->where('id', $id)->update(['title_clearning' => $clearning_type]);
                }
            }
        } catch (\Exception  $e) {

            Log::error('Lỗi toggle title_clearning', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json(['error' => 'Lỗi hệ thống'], 500);
        }

        $events = $this->getEvents(session('user.production_code'), $request->startDate, $request->endDate, true, $this->theory);

        return response()->json([
            'events' => $events,

        ]);
    }

    public function createManualCampainStage(Request $request)
    {

        $datas = $request->input('data');

        $campaign_code = $datas[0]['predecessor_code'] ?? null;

        if (count($datas) <= 1) {

            return response()->json([]);
        }

        try {

            $plan_master_ids = collect($datas)->pluck('plan_master_id')->unique();

            DB::table('stage_plan')
                ->whereIn('plan_master_id', $plan_master_ids)
                ->update([
                    'campaign_code' => $campaign_code,
                ]);
        } catch (\Exception  $e) {

            Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Lỗi hệ thống'], 500);
        }

        return response()->json([
            'plan' => $this->getPlanWaiting(session('user.production_code')),
        ]);
    }

    public function createAutoCampain(Request $request)
    {

        $mode_date = 'expected_date';

        $mode_order_by = 'order_by';

        if ($request->mode == 'response') {

            $mode_date = 'responsed_date';

            $mode_order_by = 'order_by_line';
        }

        DB::beginTransaction();

        try {

            // ====================================================
            // 1. Reset campaign_code cho các plan chưa chạy
            // ====================================================
            DB::table('stage_plan')
                ->where('finished', 0)
                ->whereNull('start')
                ->where('active', 1)
                ->update(['campaign_code' => null]);

            // ====================================================
            // 2. Load toàn bộ dữ liệu 1 lần
            // ====================================================
            $stage_plans = DB::table('stage_plan as sp')
                ->select(
                    'sp.id',
                    'sp.stage_code',
                    'sp.plan_master_id',
                    'sp.predecessor_code',
                    'sp.nextcessor_code',
                    'sp.campaign_code',
                    'sp.code',
                    'plan_master.expected_date',
                    'plan_master.responsed_date',
                    'plan_master.is_val',
                    'plan_master.code_val',
                    'finished_product_category.intermediate_code',
                    'finished_product_category.finished_product_code'
                )
                ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
                ->leftJoin('finished_product_category', 'sp.product_caterogy_id', '=', 'finished_product_category.id')
                ->where('sp.finished', 0)
                ->whereNull('sp.start')
                ->where('sp.active', 1)
                ->orderBy("sp.$mode_order_by", 'asc')
                ->get();

            // ====================================================
            // 3. Theo dõi plan_master_id đã được gán campaign
            // ====================================================
            $processedPlanMasters = collect();

            // ====================================================
            // 4. loop qua các stage
            // ====================================================
            for ($i = 3; $i <= 7; $i++) {

                $product_code = ($i <= 6) ? 'intermediate_code' : 'finished_product_code';

                // ------------------------------------------------
                // 4.1. Lấy stage hiện tại + CHƯA xử lý
                // ------------------------------------------------
                $stage_plans_stage = $stage_plans
                    ->where('stage_code', $i)
                    ->whereNotIn('plan_master_id', $processedPlanMasters);

                if ($stage_plans_stage->isEmpty()) {

                    continue;
                }

                // ------------------------------------------------
                // 4.2. Filter code_val an toàn
                // ------------------------------------------------
                $stage_plans_stage = $stage_plans_stage->filter(function ($item) {

                    if ($item->code_val === null) {

                        return true;
                    }

                    $parts = explode('_', $item->code_val);

                    return isset($parts[1]) && (int) $parts[1] > 1;
                });

                if ($stage_plans_stage->isEmpty()) {

                    continue;
                }

                // ------------------------------------------------
                // 4.3. Group dữ liệu
                // ------------------------------------------------
                $groups = $stage_plans_stage
                    ->groupBy(function ($item) use ($product_code, $mode_date) {

                        // / đanh dấu nếu muốn tách lô thẩm định 2 và 3
                        // if ($item->code_val === null || explode('_', $item->code_val)[0] > 1) {
                        //         $cvflag = 'null';
                        // } else {
                        //         $cvflag = 1; //explode('_', $item->code_val)[0];
                        // }

                        return $item->$mode_date . '|' . $item->$product_code;
                        // . '|' . $cvFlag;
                    })
                    ->filter(fn($group) => $group->count() > 1);

                if ($groups->isEmpty()) {

                    continue;
                }

                // ------------------------------------------------
                // 4.4. Tạo campaign
                // ------------------------------------------------
                $updates = [];

                foreach ($groups as $groupKey => $items) {

                    [,  $code] = explode('|', $groupKey);

                    $quota = DB::table('quota')
                        ->where($product_code, $code)
                        ->where('stage_code', $i)
                        ->first();

                    $maxBatch = $quota->maxofbatch_campaign ?? 0;

                    if ($maxBatch <= 1) {

                        continue;
                    }

                    $items = $items->values();

                    $countInBatch = 0;

                    $campaignCode = $items[0]->predecessor_code ?? ('0_' . $items[0]->code);

                    foreach ($items as $item) {

                        if ($countInBatch >= $maxBatch) {

                            $campaignCode = $item->predecessor_code ?? ('0_' . $item->code);

                            $countInBatch = 0;
                        }

                        $updates[] = [
                            'plan_master_id' => $item->plan_master_id,
                            'campaign_code' => $campaignCode,
                        ];

                        $countInBatch++;
                    }
                }

                // ------------------------------------------------
                // 4.5. update db + đánh dấu đã xử lý
                // ------------------------------------------------
                if (! empty($updates)) {

                    $plan_master_ids = collect($updates)->pluck('plan_master_id')->unique()->implode(',');

                    $caseSql = 'CASE plan_master_id ';

                    foreach ($updates as $row) {

                        $caseSql .= "WHEN {$row['plan_master_id']} THEN '{$row['campaign_code']}' ";
                    }

                    $caseSql .= 'END';

                    DB::update("
                                        UPDATE stage_plan
                                        SET campaign_code = $caseSql
                                        WHERE plan_master_id IN ($plan_master_ids)
                                ");

                    // đánh dấu đã xử lý
                    $processedPlanMasters = $processedPlanMasters
                        ->merge(collect($updates)->pluck('plan_master_id'))
                        ->unique();
                }
            }

            DB::commit();

            return response()->json([
                'plan' => $this->getPlanWaiting(session('user.production_code')),
            ]);
        } catch (\Exception  $e) {

            DB::rollBack();

            Log::error('Lỗi createAutoCampain', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Lỗi hệ thống'], 500);
        }
    }

    public function DeleteAutoCampain(Request $request)
    {

        $plan_master_ids = collect($request->data)->pluck('plan_master_id')->unique();

        DB::table('stage_plan')
            ->where('finished', 0)
            ->where('start', null)
            ->where('active', 1)
            ->whereIn('plan_master_id', $plan_master_ids)
            ->update(['campaign_code' => null]);

        return response()->json([
            'plan' => $this->getPlanWaiting(session('user.production_code')),
        ]);
    }

    public function createOrderPlan(Request $request)
    {

        try {

            DB::transaction(function () use ($request) {

                $planMasterId = DB::table('plan_master')->insertGetId([
                    'plan_list_id' => 0,
                    'product_caterogy_id' => 0,
                    'level' => 4,
                    'batch' => $request->batch,
                    'expected_date' => '2025-01-01',
                    'is_val' => false,
                    'only_parkaging' => false,
                    'percent_parkaging' => 1,
                    'note' => $request->note ?? 'NA',
                    'deparment_code' => session('user.production_code'),
                    'created_at' => now(),
                    'prepared_by' => session('user')['fullName'],
                ]);

                $number_of_batch = $request->number_of_batch ?? 1;

                for ($i = 1; $i <= $number_of_batch; $i++) {

                    // Insert stage_plan và gán plan_master_id
                    DB::table('stage_plan')->insert([
                        'plan_list_id' => 0,
                        'product_caterogy_id' => 0,
                        'plan_master_id' => $planMasterId,
                        'schedualed' => 0,
                        'finished' => 0,
                        'active' => 1,
                        'stage_code' => 9,
                        'deparment_code' => session('user.production_code'),
                        'title' => $request->title,
                        'yields' => $request->checkedClearning ? 0 : -1,
                        'created_by' => session('user')['fullName'],
                        'created_date' => now(),
                    ]);
                }
            });
        } catch (\Exception  $e) {

            Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Lỗi hệ thống'], 500);
        }

        return response()->json([
            'plan' => $this->getPlanWaiting(session('user.production_code')),
        ]);
    }

    public function DeActiveOrderPlan(Request $request)
    {

        try {

            $ids = collect($request->all())->pluck('id');
            // lấy ra danh sách id

            DB::table('stage_plan')
                ->whereIn('id', $ids)
                ->update([
                    'active' => 0,
                    'finished_by' => session('user')['fullName'] ?? 'System',
                    'finished_date' => now(),
                ]);
        } catch (\Exception  $e) {

            Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Lỗi hệ thống'], 500);
        }

        return response()->json([
            'plan' => $this->getPlanWaiting(session('user.production_code')),
        ]);
    }

    public function groupGranulationAndBlending(Request $request)
    {
        $ids = $request->ids;
        if (empty($ids)) {
            return response()->json(['message' => 'Vui lòng chọn ít nhất một lô sản phẩm.'], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($ids as $id) {
                // Lấy thông tin công đoạn đang được chọn để gộp (3 hoặc 4)
                $currentStage = DB::table('stage_plan')->where('id', $id)->first();
                if (! $currentStage) {
                    continue;
                }

                $pmId = $currentStage->plan_master_id;
                $currentCode = $currentStage->code;
                $preCode = $currentStage->predecessor_code;
                $nextCode = $currentStage->nextcessor_code;

                // 1. Vô hiệu hóa công đoạn hiện tại
                DB::table('stage_plan')
                    ->where('id', $id)
                    ->update(['active' => 0]);

                // 2. Cập nhật mắt xích phía trước trỏ thẳng tới công đoạn phía sau
                if ($preCode) {
                    DB::table('stage_plan')
                        ->where('plan_master_id', $pmId)
                        ->where('code', $preCode)
                        ->update(['nextcessor_code' => $nextCode]);
                }

                // 3. Cập nhật mắt xích phía sau trỏ ngược lại công đoạn phía trước
                if ($nextCode) {
                    DB::table('stage_plan')
                        ->where('plan_master_id', $pmId)
                        ->where('code', $nextCode)
                        ->update(['predecessor_code' => $preCode]);
                }
            }
            DB::commit();
        } catch (\Exception  $e) {
            DB::rollBack();

            return response()->json(['message' => 'Lỗi khi xử lý gộp công đoạn: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'plan' => $this->getPlanWaiting(session('user.production_code')),
        ]);
    }

    public function Sorted(Request $request)
    {

        if ($request->sortType === 'response') {

            if (
                $request->filled('plan_master_ids') &&
                is_array($request->plan_master_ids) &&
                count($request->plan_master_ids) > 0 &&
                $request->filled('response_date')
            ) {

                DB::table('plan_master')
                    ->whereIn('id', $request->plan_master_ids)
                    ->update([
                        'responsed_date' => $request->response_date,
                    ]);
            }

            $sortType = 'responsed_date';
        } else {

            $sortType = 'expected_date';
        }

        $stageCode = $request->stage_code ?? 3;

        // Danh sách cấu hình sắp xếp
        $stages = [
            ['codes' => [1,  2,  3],  'orderBy' => [
                [$sortType,  'asc'],
                ['level',  'asc'],
                [DB::raw('batch + 0'),  'asc'],
            ]],
            ['codes' => [4],  'orderBy' => [
                ['intermediate_category.quarantine_blending',  'asc'],
                [$sortType,  'asc'],
                ['level',  'asc'],
                [DB::raw('batch + 0'),  'asc'],
            ]],
            ['codes' => [5],  'orderBy' => [
                ['intermediate_category.quarantine_forming',  'asc'],
                [$sortType,  'asc'],
                ['level',  'asc'],
                [DB::raw('batch + 0'),  'asc'],
            ]],
            ['codes' => [6],  'orderBy' => [
                ['intermediate_category.quarantine_coating',  'asc'],
                [$sortType,  'asc'],
                ['level',  'asc'],
                [DB::raw('batch + 0'),  'asc'],
            ]],
        ];

        // Tìm stage group tương ứng với stage_code được gửi lên
        $stageGroup = collect($stages)->first(fn($group) => in_array($stageCode, $group['codes']));

        if (! $stageGroup) {

            return response()->json(['error' => 'Stage code không hợp lệ!'], 400);
        }

        // Xây query cho plan_master
        $query = DB::table('plan_master')
            ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', 'finished_product_category.id')
            ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', 'intermediate_category.intermediate_code');

        // thêm thứ tự sắp xếp tương ứng
        foreach ($stageGroup['orderBy'] as [$column,  $direction]) {

            $query->orderBy($column, $direction);
        }

        // Lấy danh sách ID
        $planMasters = $query->pluck('plan_master.id');

        if ($planMasters->isEmpty()) {

            return response()->json(['message' => 'Không có kế hoạch để sắp xếp.']);
        }

        // Cập nhật order_by cho stage được chọn
        DB::table('stage_plan')
            ->whereNull('start')
            ->where('stage_code', $stageCode)
            ->where('finished', 0)
            ->where('active', 1)
            ->where('deparment_code', session('user.production_code'))
            ->whereIn('plan_master_id', $planMasters)
            ->orderByRaw('FIELD(plan_master_id, ' . implode(',', $planMasters->toArray()) . ')')
            ->update([
                'order_by' => DB::raw('FIELD(plan_master_id, ' . implode(',', $planMasters->toArray()) . ')'),
            ]);

        return response()->json([
            'plan' => $this->getPlanWaiting(session('user.production_code')),
            'message' => "Đã sắp xếp lại kế hoạch cho stage {$stageCode}.",
        ]);
    }

    public function submit(Request $request)
    {

        // 1️⃣ Lấy danh sách các dòng sẽ update
        $submitType = $request->input('submit_type', 'production'); // production, HC, BT, TI

        $updatedRows = DB::table('stage_plan as sp')
            ->select(
                'sp.*',
                DB::raw("COALESCE(intermediate_category.intermediate_code, finished_product_category.finished_product_code) as product_code"),
                DB::raw("COALESCE(p2.name, p1.name) as real_product_name"),
                'plan_master.batch'
            )
            ->whereNotNull('sp.start')
            ->where('sp.finished', 0)
            ->where('sp.active', 1)
            ->where('sp.submit', 0)
            ->where('sp.deparment_code', session('user.production_code'))
            ->when($submitType === 'production', function ($query) {
                $query->where('sp.stage_code', '!=', 8);
            })
            ->when(in_array($submitType, ['HC', 'BT', 'TI']), function ($query) use ($submitType) {
                $query->where('sp.stage_code', 8)
                    ->where('sp.code', 'LIKE', '%_' . $submitType);
            })
            ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
            ->leftJoin('finished_product_category', function ($join) {
                $join->on('sp.product_caterogy_id', '=', 'finished_product_category.id')
                    ->where('sp.stage_code', '<=', 7);
            })
            ->leftJoin('product_name as p1', 'finished_product_category.product_name_id', '=', 'p1.id')
            ->leftJoin('intermediate_category', function ($join) {
                $join->on('sp.product_caterogy_id', '=', 'intermediate_category.id')
                    ->where('sp.stage_code', '<=', 2);
            })
            ->leftJoin('product_name as p2', 'intermediate_category.product_name_id', '=', 'p2.id')
            ->get();

        $notification = $request->input('notification');

        if ($updatedRows->isEmpty() && empty($notification)) {
            return response()->json(['message' => 'Không có lịch mới để submit!', 'type' => 'info']);
        }

        $newSchedules = 0;
        $modifiedSchedules = 0;

        if (!$updatedRows->isEmpty()) {
            // 2️⃣ Update submit = 1
            DB::table('stage_plan')
                ->whereIn('id', $updatedRows->pluck('id'))
                ->update(['submit' => 1]);

            $historyData = collect([]);
            foreach ($updatedRows as $row) {
                $latest_history = DB::table('stage_plan_history')
                    ->where('stage_plan_id', $row->id)
                    ->orderBy('version', 'desc')
                    ->first();

                $should_insert = true;
                if ($latest_history) {
                    if (
                        $latest_history->resourceId == $row->resourceId &&
                        $latest_history->start == $row->start &&
                        $latest_history->end == $row->end &&
                        $latest_history->start_clearning == $row->start_clearning &&
                        $latest_history->end_clearning == $row->end_clearning
                    ) {
                        $should_insert = false;
                    }
                }

                if ($should_insert) {
                    $maxVersion = $latest_history ? $latest_history->version : 0;
                    if ($maxVersion == 0) {
                        $newSchedules++;
                    } else {
                        $modifiedSchedules++;
                    }

                    $historyData->push([
                        'stage_plan_id' => $row->id,
                        'plan_list_id' => $row->plan_list_id,
                        'plan_master_id' => $row->plan_master_id,
                        'product_caterogy_id' => $row->product_caterogy_id,
                        'campaign_code' => $row->campaign_code,
                        'code' => $row->code,
                        'order_by' => $row->order_by,
                        'schedualed' => $row->schedualed,
                        'stage_code' => $row->stage_code,
                        'title' => $row->title,
                        'start' => $row->start,
                        'end' => $row->end,
                        'resourceId' => $row->resourceId,
                        'title_clearning' => $row->title_clearning,
                        'start_clearning' => $row->start_clearning,
                        'end_clearning' => $row->end_clearning,
                        'tank' => $row->tank,
                        'keep_dry' => $row->keep_dry,
                        'AHU_group' => $row->AHU_group,
                        'schedualed_by' => $row->schedualed_by,
                        'schedualed_at' => $row->schedualed_at,
                        'version' => $maxVersion + 1,
                        'note' => $row->note,
                        'deparment_code' => session('user.production_code'),
                        'type_of_change' => $maxVersion == 0 ? 'Tạo Mới Lịch' : 'Cập Nhật Lịch',
                        'created_date' => now(),
                        'created_by' => session('user')['fullName'],
                    ]);
                }
            }

            // 🔹 Chia nhỏ insert để tránh lỗi 1390
            if ($historyData->isNotEmpty()) {
                $historyData->chunk(500)->each(function ($chunk) {
                    DB::table('stage_plan_history')->insert($chunk->toArray());
                });
            }
        }

        // / Gửi thông Báo
        $senderName = session('user')['fullName'];
        $productionName = session('user')['production_name'];
        $sendDate = now()->format('d/m/Y H:i');
        $notification = $request->input('notification');

        $typeLabels = [
            'production' => 'Lịch Sản Xuất',
            'HC' => 'Lịch Hiệu Chuẩn',
            'BT' => 'Lịch Bảo Trì Thiết Bị',
            'TB' => 'Lịch Bảo Trì Thiết Bị',
            'TI' => 'Lịch Bảo Trì Tiện Ích',
        ];
        $typeLabel = $typeLabels[$submitType] ?? 'Lịch Sản Xuất';

        $message = "{$senderName} đã Submit {$typeLabel} ngày {$sendDate} PX {$productionName}";
        if ($newSchedules > 0 || $modifiedSchedules > 0) {
            $message .= " (Bao gồm: {$newSchedules} tạo mới, {$modifiedSchedules} thay đổi)";
        }

        if ($notification) {
            $message .= "\nNhắc nhở: {$notification}";
        }

        $targetUrl = in_array($submitType, ['HC', 'BT', 'TB', 'TI'])
            ? url('/maintenance-calendar')
            : route('pages.Schedual.index');

        // Logic lọc người nhận: Không gửi cho 4 phân xưởng còn lại nếu người gửi thuộc 1 trong 5 phân xưởng
        $workshops = ['PXV1', 'PXV2', 'PXDN', 'PXTN', 'PXVH'];
        $myWorkshop = session('user.production_code');
        $targetUserIds = 'all';

        if (in_array($myWorkshop, $workshops)) {
            $excludeWorkshops = array_diff($workshops, [$myWorkshop]);
            $targetUserIds = DB::table('user_management')
                ->where('isActive', 1)
                ->whereNotIn('deparment', $excludeWorkshops)
                ->pluck('id')
                ->toArray();
        }
        // Trích xuất ID người được nhắc tên (@Tên[ID]) và thêm vào danh sách người nhận
        $mentionedUserIds = [];
        if ($notification) {
            preg_match_all('/@.*?\[(\d+)\]/', $notification, $matches);
            if (!empty($matches[1])) {
                $mentionedUserIds = array_unique(array_map('intval', $matches[1]));
                if ($targetUserIds !== 'all') {
                    $targetUserIds = array_unique(array_merge($targetUserIds, $mentionedUserIds));
                }
            }
        }

        $modalContentExtend = null;
        if (isset($updatedRows) && !$updatedRows->isEmpty()) {
            $html = '<div style="margin-top: 15px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">';

            foreach ($updatedRows as $row) {
                // Lấy 2 dòng lịch sử gần nhất
                $histories = DB::table('stage_plan_history')
                    ->where('stage_plan_id', $row->id)
                    ->orderBy('version', 'desc')
                    ->limit(2)
                    ->get();

                $currentSubmit = $histories->count() > 0 ? $histories[0] : null;
                $lastSubmit = $histories->count() > 1 ? $histories[1] : null;

                $oldRoomTitle = '-';
                $oldVersion = '';
                if ($lastSubmit) {
                    $oldStart = \Carbon\Carbon::parse($lastSubmit->start)->format('H:i d/m/Y');
                    $oldEnd = \Carbon\Carbon::parse($lastSubmit->end)->format('H:i d/m/Y');
                    $oldCreatedAt = \Carbon\Carbon::parse($lastSubmit->created_date)->format('H:i d/m/Y');
                    $oldVersion = "v." . $lastSubmit->version;

                    $oldRoomObj = DB::table('room')->where('id', $lastSubmit->resourceId)->first();
                    $oldRoomTitle = $oldRoomObj ? "{$oldRoomObj->name}" : '-';
                } else {
                    $oldStart = "-";
                    $oldEnd = "-";
                    $oldCreatedAt = "-";
                    $oldVersion = "v.0";
                }

                $start = \Carbon\Carbon::parse($row->start)->format('H:i d/m/Y');
                $end = \Carbon\Carbon::parse($row->end)->format('H:i d/m/Y');
                $createdAt = $currentSubmit ? \Carbon\Carbon::parse($currentSubmit->created_date)->format('H:i d/m/Y') : \Carbon\Carbon::parse($row->schedualed_at)->format('H:i d/m/Y');

                $code = $row->product_code ?: ($row->code ?: '-');
                $title = $row->title ?: ($row->real_product_name ? "{$row->real_product_name} - {$row->batch}" : '-');

                $room = DB::table('room')->where('id', $row->resourceId)->first();
                $roomTitle = $room ? "{$room->name}" : '-';

                $html .= '<div style="border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; font-family: \'Inter\', sans-serif;">';

                // Header - chỉ mã và tên, không có badge
                $html .= '<div style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">';
                $html .= "<span style='font-weight: 700; color: #1e293b; font-size: 13px;'>{$code}</span>";
                $html .= "<span style='color: #94a3b8; font-size: 12px;'>·</span>";
                $html .= "<span style='font-weight: 500; color: #334155; font-size: 12px;'>{$title}</span>";
                $html .= '</div>';

                // Content - 4 ô thông tin nằm ngang
                $html .= '<div style="display: flex; gap: 8px; padding: 10px; flex-wrap: wrap;">';

                // Phòng
                $html .= '<div style="flex: 1; min-width: 110px; border: 1px solid #fbbf24; border-radius: 5px; padding: 8px;">';
                $html .= '<div style="font-size: 10px; font-weight: 600; color: #64748b; margin-bottom: 6px; text-transform: uppercase;"><i class="fas fa-building" style="margin-right: 3px;"></i> PHÒNG</div>';
                $html .= '<div style="display: flex; justify-content: space-between; align-items: center; background: #fee2e2; color: #b91c1c; padding: 4px 6px; border-radius: 3px; font-size: 11px; margin-bottom: 4px; border: 1px solid #fca5a5;">';
                $html .= "<span>{$oldRoomTitle}</span>";
                $html .= "<span style='background: #dc2626; color: #fff; padding: 1px 4px; border-radius: 3px; font-size: 10px; font-weight: bold; white-space: nowrap;'>{$oldVersion}</span>";
                $html .= '</div>';
                $html .= '<div style="display: flex; justify-content: space-between; align-items: center; background: #dcfce7; color: #15803d; padding: 4px 6px; border-radius: 3px; font-size: 11px; border: 1px solid #86efac;">';
                $html .= "<span>{$roomTitle}</span>";
                $html .= "<span style='background: #16a34a; color: #fff; padding: 1px 4px; border-radius: 3px; font-size: 10px; font-weight: bold; white-space: nowrap;'>Hiện hành</span>";
                $html .= '</div></div>';

                // Bắt đầu
                $html .= '<div style="flex: 1; min-width: 110px; border: 1px solid #fbbf24; border-radius: 5px; padding: 8px;">';
                $html .= '<div style="font-size: 10px; font-weight: 600; color: #64748b; margin-bottom: 6px; text-transform: uppercase;"><i class="fas fa-play-circle" style="margin-right: 3px;"></i> BẮT ĐẦU</div>';
                $html .= '<div style="background: #fee2e2; color: #b91c1c; padding: 4px 6px; border-radius: 3px; font-size: 11px; margin-bottom: 4px; border: 1px solid #fca5a5;">';
                $html .= "<span>{$oldStart}</span></div>";
                $html .= '<div style="background: #dcfce7; color: #15803d; padding: 4px 6px; border-radius: 3px; font-size: 11px; border: 1px solid #86efac;">';
                $html .= "<span>{$start}</span></div></div>";

                // Kết thúc
                $html .= '<div style="flex: 1; min-width: 110px; border: 1px solid #fbbf24; border-radius: 5px; padding: 8px;">';
                $html .= '<div style="font-size: 10px; font-weight: 600; color: #64748b; margin-bottom: 6px; text-transform: uppercase;"><i class="fas fa-stop-circle" style="margin-right: 3px;"></i> KẾT THÚC</div>';
                $html .= '<div style="background: #fee2e2; color: #b91c1c; padding: 4px 6px; border-radius: 3px; font-size: 11px; margin-bottom: 4px; border: 1px solid #fca5a5;">';
                $html .= "<span>{$oldEnd}</span></div>";
                $html .= '<div style="background: #dcfce7; color: #15803d; padding: 4px 6px; border-radius: 3px; font-size: 11px; border: 1px solid #86efac;">';
                $html .= "<span>{$end}</span></div></div>";

                // Ngày tạo lịch
                $html .= '<div style="flex: 1; min-width: 110px; border: 1px solid #fbbf24; border-radius: 5px; padding: 8px;">';
                $html .= '<div style="font-size: 10px; font-weight: 600; color: #64748b; margin-bottom: 6px; text-transform: uppercase;"><i class="fas fa-calendar-plus" style="margin-right: 3px;"></i> NGÀY TẠO LỊCH</div>';
                $html .= '<div style="background: #fee2e2; color: #b91c1c; padding: 4px 6px; border-radius: 3px; font-size: 11px; margin-bottom: 4px; border: 1px solid #fca5a5;">';
                $html .= "<span>{$oldCreatedAt}</span></div>";
                $html .= '<div style="background: #dcfce7; color: #15803d; padding: 4px 6px; border-radius: 3px; font-size: 11px; border: 1px solid #86efac;">';
                $html .= "<span>{$createdAt}</span></div></div>";

                $html .= '</div></div>';
            }
            $html .= '</div>';
            $modalContentExtend = $html;
        }

        // Gửi thông báo chung
        \App\Http\Controllers\General\NotificationController::sendNotification(
            $message,
            "Submit {$typeLabel}",
            null,
            $targetUserIds,
            [],
            $targetUrl,
            $modalContentExtend
        );

        // Gửi thông báo riêng cho người được nhắc tên
        if (!empty($mentionedUserIds)) {
            \App\Http\Controllers\General\NotificationController::sendNotification(
                "{$senderName} đã nhắc đến bạn trong {$typeLabel} PX {$productionName}: {$notification}",
                'Nhắc tên',
                null,
                $mentionedUserIds,
                [],
                $targetUrl,
                $modalContentExtend
            );
        }

        $msg = $updatedRows->isEmpty()
            ? 'Đã gửi thông báo nhắc nhở.'
            : 'Đã submit ' . $updatedRows->count() . ' lịch.';
        $type = $updatedRows->isEmpty() ? 'info' : 'success';

        return response()->json(['message' => $msg, 'type' => $type]);
    }

    public function accpectQuarantine(Request $request)
    {

        // Log::info ($request->all());
        $items = collect($request->input('ids'));

        try {

            foreach ($items as $item) {

                $rowId = explode('-', $item['id'])[0];
                // lấy id trước dấu -
                DB::table('stage_plan')
                    ->where('id', $rowId)
                    ->where('finished', 0)
                    ->update([
                        'accept_quarantine' => 1,
                    ]);
            }
        } catch (\Exception  $e) {

            Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'error',  'message' => $e->getMessage()], 500);
        }

        $events = $this->getEvents(session('user.production_code'), $request->startDate, $request->endDate, true, $this->theory);

        return response()->json([
            'events' => $events,

        ]);
    }

    public function required_room(Request $request)
    {

        // Log::info ($request->all());
        $campaign_code = DB::table('stage_plan')->where('id', $request->stage_plan_id)->value('campaign_code');

        $room = DB::table('room')->where('code', $request->room_code)->first();

        // $room_id = db::table('room')->where ('code', $request->room_code)->value('id');
        // log::info (['request' => $request->all(),'stage_code' => $stage_code]);
        if ($campaign_code && ! $request->checked) {

            DB::table('stage_plan')
                ->where('id', $request->stage_plan_id)
                ->update(['required_room_code' => null]);
        } elseif ($campaign_code && $request->checked) {

            $plans = DB::table('stage_plan')
                ->leftJoin('finished_product_category', 'finished_product_category.id', 'stage_plan.product_caterogy_id')
                ->select(
                    'stage_plan.id',
                    'stage_plan.stage_code',
                    'finished_product_category.intermediate_code',
                    'finished_product_category.finished_product_code'
                )
                ->where('stage_plan.campaign_code', $campaign_code)
                ->where('stage_plan.stage_code', $room->stage_code)
                ->get();

            foreach ($plans as $p) {

                // tạo process_code đúng tiêu chí
                if ($p->stage_code < 7) {

                    // $process_code = $p->intermediate_code . "_NA_" . $room_id;
                    $quota = DB::table('quota')
                        ->where('room_id', $room->id)
                        ->where('intermediate_code', $p->intermediate_code)
                        ->first();
                } else {

                    // $process_code = $p->intermediate_code . "_" . $p->finished_product_code . "_" . $room_id;
                    $quota = DB::table('quota')
                        ->where('room_id', $room->id)
                        ->where('intermediate_code', $p->intermediate_code)
                        ->where('finished_product_code', $p->finished_product_code)
                        ->first();
                }

                if (! $quota) {

                    return response()->json([
                        'status' => 'error',
                        'message' => "Lô ID {$p->id} không có định mức cho phòng {$room->id}. Không thể yêu cầu phòng!",
                    ], 422);
                }
            }

            DB::table('stage_plan')
                ->where('campaign_code', $campaign_code)
                ->where('stage_plan.stage_code', $room->stage_code)
                ->update(['required_room_code' => $request->room_code]);
        } else {

            DB::table('stage_plan')
                ->where('id', $request->stage_plan_id)
                ->update(['required_room_code' => $request->checked ? $request->room_code : null]);
        }

        return response()->json([
            'plan' => $this->getPlanWaiting(session('user.production_code')),
        ]);
    }

    public function change_sheet(Request $request)
    {

        $roomCode = $request->room_code;

        $sheet = $request->sheet;
        // sheet_1 | sheet_2 | sheet_3 | sheet_regular
        $checked = (int) $request->checked;
        // 1 | 0

        // validate sheet name
        $validSheets = ['sheet_1',  'sheet_2',  'sheet_3',  'sheet_regular'];

        if (! in_array($sheet, $validSheets)) {

            return response()->json(['error' => 'Invalid sheet'], 400);
        }

        // dữ liệu update
        $update = [
            $sheet => $checked,
        ];

        // 🔥 case 1: bật hành chính
        if ($sheet === 'sheet_regular' && $checked === 1) {

            $update['sheet_1'] = 0;

            $update['sheet_2'] = 0;

            $update['sheet_3'] = 0;
        }

        // 🔥 case 2: bật ca 1 / 2 / 3
        if (in_array($sheet, ['sheet_1',  'sheet_2',  'sheet_3']) && $checked === 1) {

            $update['sheet_regular'] = 0;
        }

        DB::table('room')
            ->where('code', $roomCode)
            ->update($update);

        return response()->json([
            'success' => true,
            'update' => $update,
        ]);
    }

    
    public function createUndoRestorePoint($stage_plan_ids, $plan_master_ids = [])
    {
        $bkcCode = 'UNDO_' . \Carbon\Carbon::now()->format('Ymd_His') . '_' . session('user.id', rand(100,999));
        DB::table('stage_plan_bkc')->insertUsing(
            [
                'stage_plan_id', 'bkc_code', 'plan_list_id', 'plan_master_id', 'product_caterogy_id', 'predecessor_code', 'nextcessor_code', 'campaign_code', 'code', 'order_by', 'order_by_line', 'clearning_validation', 'schedualed', 'finished', 'active', 'stage_code', 'title', 'start', 'end', 'resourceId', 'required_room_code', 'title_clearning', 'start_clearning', 'end_clearning', 'scheduling_direction', 'tank', 'keep_dry', 'immediately', 'submit', 'AHU_group', 'quarantine_time', 'schedualed_by', 'schedualed_at', 'actual_start', 'actual_end', 'actual_start_clearning', 'actual_end_clearning', 'note', 'yields', 'yields_batch_qty', 'number_of_boxes', 'Theoretical_yields', 'quarantine_room_code', 'deparment_code', 'created_date', 'created_by', 'finished_date', 'finished_by', 'quarantined_by', 'quarantined_date'
            ],
            DB::table('stage_plan')->select([
                'id as stage_plan_id', DB::raw("'\$bkcCode' as bkc_code"), 'plan_list_id', 'plan_master_id', 'product_caterogy_id', 'predecessor_code', 'nextcessor_code', 'campaign_code', 'code', 'order_by', 'order_by_line', 'clearning_validation', 'schedualed', 'finished', 'active', 'stage_code', 'title', 'start', 'end', 'resourceId', 'required_room_code', 'title_clearning', 'start_clearning', 'end_clearning', 'scheduling_direction', 'tank', 'keep_dry', 'immediately', 'submit', 'AHU_group', 'quarantine_time', 'schedualed_by', 'schedualed_at', 'actual_start', 'actual_end', 'actual_start_clearning', 'actual_end_clearning', 'note', 'yields', 'yields_batch_qty', 'number_of_boxes', 'Theoretical_yields', 'quarantine_room_code', 'deparment_code', 'created_date', 'created_by', 'finished_date', 'finished_by', 'quarantined_by', 'quarantined_date'
            ])->whereIn('id', $stage_plan_ids)
        );

        session()->put('last_undo', [
            'bkc_code' => $bkcCode,
            'timestamp' => now()->toDateTimeString(),
            'stage_plan_ids' => $stage_plan_ids,
            'plan_master_ids' => $plan_master_ids
        ]);
    }

    public function undoBackend()
    {
        $lastUndo = session()->get('last_undo');
        if (!$lastUndo) {
            return response()->json(['success' => false, 'message' => 'Kh�ng c� d? li?u kh�i ph?c ho?c phi�n l�m vi?c d� h?t h?n.']);
        }

        DB::beginTransaction();
        try {
            // Kh�i ph?c stage_plan
            $bkcData = DB::table('stage_plan_bkc')->where('bkc_code', $lastUndo['bkc_code'])->get();
            foreach ($bkcData as $row) {
                $updateData = (array) $row;
                unset($updateData['id'], $updateData['bkc_code'], $updateData['bkc_created_at']);
                DB::table('stage_plan')->where('id', $row->stage_plan_id)->update($updateData);
            }

            // X�a history r�c m?i sinh
            DB::table('stage_plan_history')
                ->whereIn('stage_plan_id', $lastUndo['stage_plan_ids'])
                ->where('created_at', '>=', $lastUndo['timestamp'])
                ->delete();

            // Ph?c h?i expected_date_change cho plan_master
            if (!empty($lastUndo['plan_master_ids'])) {
                DB::table('plan_master')->whereIn('id', $lastUndo['plan_master_ids'])->update(['expected_date_change' => 1]);
            }

            DB::commit();
            session()->forget('last_undo');
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'L?i h? th?ng: ' . $e->getMessage()]);
        }
    }

    public function backup_schedualer()
    {

        $bkcCode = Carbon::now()->format('d/m/Y_H:i');

        DB::table('stage_plan_bkc')->insertUsing(
            [
                'stage_plan_id',
                'bkc_code',
                'plan_list_id',
                'plan_master_id',
                'product_caterogy_id',
                'predecessor_code',
                'nextcessor_code',
                'campaign_code',
                'code',
                'order_by',
                'order_by_line',
                'clearning_validation',
                'schedualed',
                'finished',
                'active',
                'stage_code',
                'title',
                'start',
                'end',
                'resourceId',
                'required_room_code',
                'title_clearning',
                'start_clearning',
                'end_clearning',
                'scheduling_direction',
                'tank',
                'keep_dry',
                'immediately',
                'submit',
                'AHU_group',
                'quarantine_time',
                'schedualed_by',
                'schedualed_at',
                'actual_start',
                'actual_end',
                'actual_start_clearning',
                'actual_end_clearning',
                'note',
                'yields',
                'yields_batch_qty',
                'number_of_boxes',
                'Theoretical_yields',
                'quarantine_room_code',
                'deparment_code',
                'created_date',
                'created_by',
                'finished_date',
                'finished_by',
                'quarantined_by',
                'quarantined_date',
            ],
            DB::table('stage_plan')
                ->select([
                    'id as stage_plan_id',
                    DB::raw("'" . $bkcCode . "' as bkc_code"),
                    'plan_list_id',
                    'plan_master_id',
                    'product_caterogy_id',
                    'predecessor_code',
                    'nextcessor_code',
                    'campaign_code',
                    'code',
                    'order_by',
                    'order_by_line',
                    'clearning_validation',
                    'schedualed',
                    'finished',
                    'active',
                    'stage_code',
                    'title',
                    'start',
                    'end',
                    'resourceId',
                    'required_room_code',
                    'title_clearning',
                    'start_clearning',
                    'end_clearning',
                    'scheduling_direction',
                    'tank',
                    'keep_dry',
                    'immediately',
                    'submit',
                    'AHU_group',
                    'quarantine_time',
                    'schedualed_by',
                    'schedualed_at',
                    'actual_start',
                    'actual_end',
                    'actual_start_clearning',
                    'actual_end_clearning',
                    'note',
                    'yields',
                    'yields_batch_qty',
                    'number_of_boxes',
                    'Theoretical_yields',
                    'quarantine_room_code',
                    'deparment_code',
                    'created_date',
                    'created_by',
                    'finished_date',
                    'finished_by',
                    'quarantined_by',
                    'quarantined_date',
                ])
                ->where('finished', 0)
                ->where('deparment_code', session('user.production_code'))
        );

        // Xóa các bản sao lưu cũ để chỉ giữ lại 5 bản gần nhất cho phân xưởng hiện tại
        $department = session('user.production_code');
        $recentBkcs = DB::table('stage_plan_bkc')
            ->where('deparment_code', $department)
            ->select('bkc_code')
            ->groupBy('bkc_code')
            ->orderByRaw('MAX(id) DESC')
            ->pluck('bkc_code')
            ->toArray();

        if (count($recentBkcs) > 5) {
            $bkcsToDelete = array_slice($recentBkcs, 5);
            DB::table('stage_plan_bkc')
                ->where('deparment_code', $department)
                ->whereIn('bkc_code', $bkcsToDelete)
                ->delete();
        }

        return response()->json([
            'bkcCode' => $bkcCode,
        ]);
    }

    public function restore_schedualer(Request $request)
    {

        $bkcCode = $request->input('bkc_code');
        // ⚠️ dùng đúng key axios gửi

        if (! $bkcCode) {

            Log::warning('Restore scheduler failed: missing bkc_code', [
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Thiếu mã bản sao lưu',
            ], 422);
        }

        try {

            DB::beginTransaction();

            $affected = DB::table('stage_plan as sp')
                ->join('stage_plan_bkc as bkc', 'bkc.stage_plan_id', '=', 'sp.id')
                ->where('sp.finished', 0)
                ->where('sp.deparment_code', session('user.production_code'))
                ->where('bkc.bkc_code', $bkcCode)
                ->update([
                    'sp.start' => DB::raw('bkc.start'),
                    'sp.end' => DB::raw('bkc.end'),
                    'sp.resourceId' => DB::raw('bkc.resourceId'),
                    'sp.start_clearning' => DB::raw('bkc.start_clearning'),
                    'sp.end_clearning' => DB::raw('bkc.end_clearning'),
                    'sp.schedualed' => DB::raw('bkc.schedualed'),
                    'sp.order_by' => DB::raw('bkc.order_by'),
                    'sp.order_by_line' => DB::raw('bkc.order_by_line'),
                    'sp.campaign_code' => DB::raw('bkc.campaign_code'),
                    'sp.immediately' => DB::raw('bkc.immediately'),

                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'affected' => $affected,
            ]);
        } catch (\Throwable  $e) {

            DB::rollBack();

            Log::error('Restore scheduler error', [
                'bkc_code' => $bkcCode,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Khôi phục thất bại, vui lòng kiểm tra log',
            ], 500);
        }
    }

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

    protected function loadRoomAvailability(string $sort, int $roomId)
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

    protected function findEarliestSlot2($roomId, $Earliest, $intervalTime, $C2_time_minutes, $requireTank = 0, $requireAHU = 0, $stage_plan_table = 'stage_plan', $maxTank = 1, $tankInterval = 60, $compatibleMolds = null)
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
        $loop_count = 0;
        while (true) {
            $loop_count++;
            if ($loop_count > 1000) {
                return null;
            }
            $conflictFound = false;

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
                            $overlapStart = $off['start']->greaterThan($current_start) ? $off['start'] : $current_start;
                            $overlapEnd = $off['end']->lessThan($current_end) ? $off['end'] : $current_end;
                            $newOffTime += $overlapStart->diffInMinutes($overlapEnd);
                        }
                        $changed = ($newOffTime > $offTime);
                        $offTime = $newOffTime;
                    } while ($changed);

                    if ($gap >= $need + $offTime) {
                        if ($compatibleMolds !== null) {
                            $moldId = $this->checkMoldAvailability($compatibleMolds, $current_start, $current_end);
                            if ($moldId) {
                                return ['start' => $current_start->copy(), 'mold_id' => $moldId];
                            } else {
                                $current_start = $current_start->addMinutes(30);
                                $current_start = $this->skipOffTime($current_start, $offDateList);
                                $conflictFound = true;
                                break;
                            }
                        } else {
                            return $current_start->copy();
                        }
                    }
                }

                // ==== nếu rơi vào busy → nhảy qua ====
                if ($current_start->lt($busy['end'])) {
                    $current_start = $busy['end']->copy();
                    $current_start = $this->skipOffTime($current_start, $offDateList);
                    $conflictFound = true;
                    break;
                }
            }

            // ==== sau tất cả busy ====
            if (!$conflictFound) {
                if ($compatibleMolds !== null) {
                    $need = $intervalTime + $C2_time_minutes;
                    $offTime = 0;
                    do {
                        $current_end = $current_start->copy()->addMinutes($need + $offTime);
                        $newOffTime = 0;
                        foreach ($offDateList as $off) {
                            if ($off['end'] <= $current_start || $off['start'] >= $current_end) {
                                continue;
                            }
                            $overlapStart = $off['start']->greaterThan($current_start) ? $off['start'] : $current_start;
                            $overlapEnd = $off['end']->lessThan($current_end) ? $off['end'] : $current_end;
                            $newOffTime += $overlapStart->diffInMinutes($overlapEnd);
                        }
                        $changed = ($newOffTime > $offTime);
                        $offTime = $newOffTime;
                    } while ($changed);

                    $moldId = $this->checkMoldAvailability($compatibleMolds, $current_start, $current_end);
                    if ($moldId) {
                        return ['start' => $current_start->copy(), 'mold_id' => $moldId];
                    } else {
                        $current_start = $current_start->addMinutes(30);
                        $current_start = $this->skipOffTime($current_start, $offDateList);
                        continue;
                    }
                }
                return $current_start->copy();
            }
        }
    }

    protected function saveSchedule($first_in_campaign, $stageId, $roomId, $start, $end, $start_clearning, $endCleaning, string $cleaningType, bool $direction, $blister_mold_id = null)
    {

        DB::transaction(function () use ($first_in_campaign, $stageId, $roomId, $start, $end, $start_clearning, $endCleaning, $cleaningType, $direction, $blister_mold_id) {

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
                    'receive_packaging_date' => DB::raw("CASE WHEN received = 0 AND stage_code = 7 THEN '$receiveDate' ELSE receive_packaging_date END"),
                    'receive_second_packaging_date' => DB::raw("CASE WHEN received_second_packaging = 0 AND stage_code = 7 THEN '$receiveDate' ELSE receive_second_packaging_date END"),
                    'blister_mold_id' => $blister_mold_id,
                ]);

            if ($blister_mold_id !== null) {
                if (!isset($this->moldSchedules[$blister_mold_id])) {
                    $this->moldSchedules[$blister_mold_id] = [];
                }
                $this->moldSchedules[$blister_mold_id][] = [
                    'start' => Carbon::parse($start),
                    'end' => Carbon::parse($end),
                    'resourceId' => $roomId
                ];
            }

            $submit = DB::table('stage_plan')->where('id', $stageId)->value('submit');

            // nếu muốn log cả cleaning vào room_schedule thì thêm block này:
            if ($submit == 1) {

                $this->syncPackagingDate($stageId, $receiveDate, 0, 'SchedualController.update');
                $this->syncPackagingDate($stageId, $receiveDate, 1, 'SchedualController.update');

                $update_row = DB::table('stage_plan')->where('id', $stageId)->first();
                if ($update_row) {
                    $latest_history = DB::table('stage_plan_history')
                        ->where('stage_plan_id', $stageId)
                        ->orderBy('version', 'desc')
                        ->first();

                    $should_insert = true;
                    if ($latest_history) {
                        if (
                            $latest_history->resourceId == $update_row->resourceId &&
                            $latest_history->start == $update_row->start &&
                            $latest_history->end == $update_row->end &&
                            $latest_history->start_clearning == $update_row->start_clearning &&
                            $latest_history->end_clearning == $update_row->end_clearning
                        ) {
                            $should_insert = false;
                        }
                    }

                    if ($should_insert) {
                        DB::table('stage_plan_history')
                            ->insert([
                                'stage_plan_id' => $stageId,
                                'plan_list_id' => $update_row->plan_list_id,
                                'plan_master_id' => $update_row->plan_master_id,
                                'product_caterogy_id' => $update_row->product_caterogy_id,
                                'campaign_code' => $update_row->campaign_code,
                                'code' => $update_row->code,
                                'order_by' => $update_row->order_by,
                                'schedualed' => $update_row->schedualed,
                                'stage_code' => $update_row->stage_code,
                                'title' => $update_row->title,
                                'start' => $update_row->start,
                                'end' => $update_row->end,
                                'resourceId' => $update_row->resourceId,
                                'title_clearning' => $update_row->title_clearning,
                                'start_clearning' => $update_row->start_clearning,
                                'end_clearning' => $update_row->end_clearning,
                                'tank' => $update_row->tank,
                                'keep_dry' => $update_row->keep_dry,
                                'AHU_group' => $update_row->AHU_group,
                                'schedualed_by' => $update_row->schedualed_by,
                                'schedualed_at' => $update_row->schedualed_at,
                                'version' => $latest_history ? $latest_history->version + 1 : 1,
                                'note' => $update_row->note,
                                'deparment_code' => session('user.production_code'),
                                'type_of_change' => $this->reason ?? 'Lập Lịch Tự Động',
                                'created_date' => now(),
                                'created_by' => session('user')['fullName'],
                            ]);
                    }
                }
            }

            // Cập nhật submit = 0 sau khi lưu lịch sử (Ngoại trừ lịch bảo trì)
            DB::table('stage_plan')
                ->where('id', $stageId)
                ->where('stage_code', '!=', 8)
                ->update(['submit' => 0]);
        });
    }

    public function scheduleAll(Request $request)
    {
        set_time_limit(1200);
        ini_set('max_execution_time', 1200);


        $this->selectedDates = $request->selectedDates ?? [];

        $this->work_sunday = $request->work_sunday ?? false;

        $this->reason = $request->reason ?? 'NA';

        $this->prev_orderBy = $request->prev_orderBy ?? false;

        $this->loadOffDate('asc');

        $today = Carbon::now()->toDateString();

        $start_date = Carbon::createFromFormat('Y-m-d', $request->start_date ?? $today)->setTime(6, 0, 0);

        // / chạy công đoạn cân nl
        if ($request->selectedStep == 'CNL') {

            $this->scheduleWeightStage($start_date);

            return response()->json([]);
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

        $waite_time = [];

        $waite_time[3] = ['waite_time_nomal_batch' => 0,  'waite_time_val_batch' => 0];

        $waite_time[4] = ['waite_time_nomal_batch' => (($request->wt_bleding ?? 0) * 24 * 60),  'waite_time_val_batch' => (($request->wt_bleding_val ?? 1) * 24 * 60)];

        $waite_time[5] = ['waite_time_nomal_batch' => (($request->wt_forming ?? 0) * 24 * 60),  'waite_time_val_batch' => (($request->wt_forming_val ?? 1) * 24 * 60)];

        $waite_time[6] = ['waite_time_nomal_batch' => (($request->wt_coating ?? 0) * 24 * 60),  'waite_time_val_batch' => (($request->wt_coating_val ?? 1) * 24 * 60)];

        $waite_time[7] = ['waite_time_nomal_batch' => (($request->wt_blitering ?? 0) * 24 * 60),  'waite_time_val_batch' => (($request->wt_blitering_val ?? 5) * 24 * 60)];

        // $this->schedulestartbackward($start_date, $waite_time);

        // / chạy theo line
        if ($request->runType == 'line') {

            $stage_code_line = DB::table('room')->where('code', $request->lines)->value('stage_code');

            $this->scheduleLine($request->lines, $request->stage_plan_ids, $stage_code_line, 0, 0, $start_date);

            return response()->json([]);
        }

        // ///bán thành phầm
        for ($i = $selectedStep; $i >= 3; $i--) {

            $this->scheduleIntermediate($i, 0, 0, $start_date);
        }

        // ///stage_plan có cảnh báo NL/BB (chạy sau bán thành phẩm, trước sản phẩm nhạy cảm)
        for ($i = $selectedStep; $i >= 3; $i--) {

            $this->scheduleWarningMR($i, 0, 0, $start_date);
        }

        // ///sản phẩm nhạy cảm
        for ($i = 3; $i <= $selectedStep; $i++) {

            $this->scheduleSensitiveProduct($i, 0, 0, $start_date);
        }

        // / chạy theo stage_z
        foreach ($stageCodes as $i) {

            $waite_time_nomal_batch = 0;

            $waite_time_val_batch = 0;

            switch ($i) {

                case 3:

                    $waite_time_nomal_batch = 0;

                    $waite_time_val_batch = 0;

                    break;

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
                    // Đóng gói
                    $waite_time_nomal_batch = ($request->wt_blitering ?? 0) * 24 * 60;

                    $waite_time_val_batch = ($request->wt_blitering_val ?? 5) * 24 * 60;

                    break;
            }

            $this->Auto_scheduler_Stage_Forward($i, $waite_time_nomal_batch, $waite_time_val_batch, $start_date);
        }

        $overdueCampaigns = $this->scanOverdueTasks();

        return response()->json(['overdueCampaigns' => $overdueCampaigns]);
    }

    public function scheduleAllPass2(Request $request)
    {
        set_time_limit(1200);
        ini_set('max_execution_time', 1200);
        $overdueCampaigns = $request->overdueCampaigns;

        if (empty($overdueCampaigns)) {
            $overdueCampaigns = $this->scanOverdueTasks();
        }

        if (empty($overdueCampaigns)) {
            return response()->json(['success' => false, 'message' => 'Không có sự kiện nào bị quá hạn biệt trữ.']);
        }

        $this->selectedDates = $request->selectedDates ?? [];
        $this->work_sunday = $request->work_sunday ?? false;
        $this->reason = $request->reason ?? 'NA';
        $this->prev_orderBy = $request->prev_orderBy ?? false;
        $this->loadOffDate('asc');

        $today = Carbon::now()->toDateString();
        $start_date = Carbon::createFromFormat('Y-m-d', $request->start_date ?? $today)->setTime(6, 0, 0);

        $Step = [
            'PC' => 3,
            'THT' => 4,
            'ĐH' => 5,
            'BP' => 6,
            'ĐG' => 7,
        ];
        $selectedStep = $Step[$request->selectedStep ?? 'ĐG'];
        $this->max_Step = $selectedStep;

        // --- BƯỚC 3: ROLLBACK LỊCH CHỌN LỌC ---
        foreach ($overdueCampaigns as $overdue) {
            $campaignCode = $overdue['campaign_code'];
            $stageCode = $overdue['stage_code'];

            if (in_array($stageCode, [1, 2])) {
                // Lỗi ở Cân: Chỉ xóa lịch Cân (stage_code 1, 2)
                DB::table('stage_plan')
                    ->where('campaign_code', $campaignCode)
                    ->whereIn('stage_code', [1, 2])
                    ->where('schedualed', 1)
                    ->update([
                        'start' => null,
                        'end' => null,
                        'start_clearning' => null,
                        'end_clearning' => null,
                        'resourceId' => null,
                        'schedualed' => 0,
                    ]);
            } else {
                // Lỗi ở đoạn khác: Xóa lịch toàn bộ các batch trong campaign đó, CHỪ STAGE 1, 2 và 3 ĐÃ CHỐT TRƯỚC (NẾU MUỐN).
                // Nhưng theo kế hoạch, nếu lỗi ở 4,5,7 thì xóa toàn bộ:
                DB::table('stage_plan')
                    ->where('campaign_code', $campaignCode)
                    ->where('schedualed', 1)
                    ->update([
                        'start' => null,
                        'end' => null,
                        'start_clearning' => null,
                        'end_clearning' => null,
                        'resourceId' => null,
                        'schedualed' => 0,
                    ]);
            }
        }

        // --- BƯỚC 4: CHẠY PASS 2 VỚI ĐỘ ƯU TIÊN TUYỆT ĐỐI ---
        $this->scheduleOverdueCampaigns($overdueCampaigns, $request, $start_date);

        // Sau đó chạy lại phần Auto Schedule bình thường cho các lô chưa xếp
        $stageCodes = DB::table('stage_plan as sp')
            ->distinct()
            ->where('sp.stage_code', '>=', 3)
            ->where('sp.stage_code', '<=', $selectedStep)
            ->where('sp.deparment_code', session('user.production_code'))
            ->orderBy('sp.stage_code')
            ->pluck('sp.stage_code');

        for ($i = $selectedStep; $i >= 3; $i--) {
            $this->scheduleIntermediate($i, 0, 0, $start_date);
        }

        for ($i = $selectedStep; $i >= 3; $i--) {
            $this->scheduleWarningMR($i, 0, 0, $start_date);
        }

        for ($i = 3; $i <= $selectedStep; $i++) {
            $this->scheduleSensitiveProduct($i, 0, 0, $start_date);
        }

        foreach ($stageCodes as $i) {
            $waite_time_nomal_batch = 0;
            $waite_time_val_batch = 0;

            switch ($i) {
                case 3:
                    $waite_time_nomal_batch = 0;
                    $waite_time_val_batch = 0;
                    break;
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

            $this->Auto_scheduler_Stage_Forward($i, $waite_time_nomal_batch, $waite_time_val_batch, $start_date);
        }

        return response()->json(['status' => 'Pass 2 Completed']);
    }

    protected function scheduleOverdueCampaigns($overdueCampaigns, Request $request, $start_date)
    {
        $hasWeightFix = false;
        foreach ($overdueCampaigns as $overdue) {
            if (in_array($overdue['stage_code'], [1, 2])) {
                $hasWeightFix = true;
            }
        }
        
        // --- XỬ LÝ LỖI STAGE 1, 2: Xếp Backward Scheduling (cân sớm nhất có thể nhưng sát ngày Pha Chế) ---
        if ($hasWeightFix) {
            $this->scheduleWeightStage($start_date);
        }

        // --- XỬ LÝ LỖI STAGE 4, 5, 7: Xếp Tiến (Forward Scheduling) bằng Priority Engine ---
        $vipCampaigns = [];
        foreach ($overdueCampaigns as $overdue) {
            if (!in_array($overdue['stage_code'], [1, 2])) {
                $vipCampaigns[] = $overdue['campaign_code'];
            }
        }

        if (empty($vipCampaigns)) {
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

        $stageCodes = DB::table('stage_plan as sp')
            ->distinct()
            ->where('sp.stage_code', '>=', 3)
            ->where('sp.stage_code', '<=', $selectedStep)
            ->whereIn('sp.campaign_code', $vipCampaigns)
            ->where('sp.deparment_code', session('user.production_code'))
            ->orderBy('sp.stage_code')
            ->pluck('sp.stage_code');

        foreach ($stageCodes as $i) {
            $waite_time_nomal_batch = 0;
            $waite_time_val_batch = 0;

            switch ($i) {
                case 3:
                    $waite_time_nomal_batch = 0;
                    $waite_time_val_batch = 0;
                    break;
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

            $this->autoScheduleVIPCampaigns($i, $waite_time_nomal_batch, $waite_time_val_batch, $start_date, $vipCampaigns);
        }
    }

    protected function autoScheduleVIPCampaigns(int $stageCode, int $waite_time_nomal_batch = 0, int $waite_time_val_batch = 0, ?Carbon $start_date = null, array $vipCampaigns = [])
    {
        if (empty($vipCampaigns)) return;

        $tasks = DB::table('stage_plan as sp')
            ->select(
                'sp.id', 'sp.plan_master_id', 'sp.product_caterogy_id', 'sp.predecessor_code',
                'sp.nextcessor_code', 'sp.campaign_code', 'sp.code', 'sp.stage_code', 'sp.tank', 'sp.keep_dry',
                'sp.order_by', 'sp.required_room_code', 'sp.immediately',
                'plan_master.batch', 'plan_master.is_val', 'plan_master.code_val', 'plan_master.expected_date',
                'plan_master.after_weigth_date', 'plan_master.after_parkaging_date', 'plan_master.allow_weight_before_date',
                'finished_product_category.product_name_id', 'finished_product_category.market_id',
                'finished_product_category.finished_product_code', 'finished_product_category.intermediate_code',
                'product_name.name', 'market.code as market', 'prev.start as prev_start'
            )
            ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
            ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
            ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
            ->leftJoin('stage_plan as prev', function ($join) {
                $join->on('prev.code', '=', 'sp.predecessor_code')
                     ->whereNotIn('prev.stage_code', [1, 2]);
            })
            ->where('sp.stage_code', $stageCode)
            ->where('sp.finished', 0)
            ->where('sp.active', 1)
            ->where('sp.not_schedule', 0)
            ->whereNull('sp.start')
            ->whereIn('sp.campaign_code', $vipCampaigns)
            ->where('sp.deparment_code', session('user.production_code'))
            ->orderByRaw("FIELD(sp.campaign_code, '" . implode("','", $vipCampaigns) . "') ASC")
            ->orderBy('plan_master.batch', 'asc')
            ->get();

        if ($tasks->isEmpty()) return;

        $processedCampaigns = [];

        foreach ($tasks as $task) {
            $waite_time = ($task->is_val === 1) ? $waite_time_val_batch : $waite_time_nomal_batch;
            $start_date_temp = $start_date;

            if ($task->campaign_code === null) {
                $this->sheduleNotCampaing($task, $stageCode, $waite_time, $start_date_temp, null);
            } else {
                if (in_array($task->campaign_code, $processedCampaigns)) continue;

                $campaignTasks = $tasks->where('campaign_code', $task->campaign_code)->sortBy('batch');
                $this->scheduleCampaign($campaignTasks, $stageCode, $waite_time, $start_date_temp, null);
                $processedCampaigns[] = $task->campaign_code;
            }
        }
    }

    protected function scanOverdueTasks()
    {
        $overdueCampaigns = [];

        $tasks = DB::table('stage_plan as sp')
            ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
            ->whereNotNull('sp.start')
            ->where('sp.finished', 0)
            ->where('sp.active', 1)
            ->where('sp.not_schedule', 0)
            ->where('sp.deparment_code', session('user.production_code'))
            ->select('sp.id', 'sp.campaign_code', 'sp.stage_code', 'sp.start', 'plan_master.expired_material_date', 'plan_master.preperation_before_date', 'plan_master.blending_before_date', 'plan_master.coating_before_date', 'plan_master.parkaging_before_date', 'plan_master.expired_packing_date')
            ->get();

        foreach ($tasks as $task) {
            $overdueStart = null;
            $start = Carbon::parse($task->start);

            if (in_array($task->stage_code, [1, 2, 3])) {
                if ($task->expired_material_date && $start->gt(Carbon::parse($task->expired_material_date)->setTime(6, 0, 0))) {
                    $overdueStart = Carbon::parse($task->expired_material_date)->setTime(6, 0, 0);
                } elseif ($task->preperation_before_date && $start->gt(Carbon::parse($task->preperation_before_date)->setTime(6, 0, 0))) {
                    $overdueStart = Carbon::parse($task->preperation_before_date)->setTime(6, 0, 0);
                }
            } elseif ($task->stage_code == 4) {
                if ($task->blending_before_date && $start->gt(Carbon::parse($task->blending_before_date)->setTime(6, 0, 0))) {
                    $overdueStart = Carbon::parse($task->blending_before_date)->setTime(6, 0, 0);
                }
            } elseif ($task->stage_code == 5 || $task->stage_code == 6) {
                if ($task->coating_before_date && $start->gt(Carbon::parse($task->coating_before_date)->setTime(6, 0, 0))) {
                    $overdueStart = Carbon::parse($task->coating_before_date)->setTime(6, 0, 0);
                }
            } elseif ($task->stage_code == 7) {
                if ($task->parkaging_before_date && $start->gt(Carbon::parse($task->parkaging_before_date)->setTime(6, 0, 0))) {
                    $overdueStart = Carbon::parse($task->parkaging_before_date)->setTime(6, 0, 0);
                } elseif ($task->expired_packing_date && $start->gt(Carbon::parse($task->expired_packing_date)->setTime(6, 0, 0))) {
                    $overdueStart = Carbon::parse($task->expired_packing_date)->setTime(6, 0, 0);
                }
            }

            if ($overdueStart && $task->campaign_code) {
                $tardiness = $start->diffInMinutes($overdueStart);
                if (!isset($overdueCampaigns[$task->campaign_code]) || $overdueCampaigns[$task->campaign_code]['tardiness'] < $tardiness) {
                    $overdueCampaigns[$task->campaign_code] = [
                        'campaign_code' => $task->campaign_code,
                        'tardiness' => $tardiness,
                        'stage_code' => $task->stage_code
                    ];
                }
            }
        }

        $overdueArray = array_values($overdueCampaigns);
        usort($overdueArray, function($a, $b) {
            return $b['tardiness'] <=> $a['tardiness'];
        });

        return $overdueArray;
    }

    /**
     * Nhóm 2b: Xếp lịch các stage_plan có ràng buộc cảnh báo NL/BB.
     * Chạy sau scheduleIntermediate, trước scheduleSensitiveProduct.
     * Lấy task chưa xếp lịch, có ít nhất 1 cột cảnh báo not null.
     * Sắp xếp theo ngày deadline chặt nhất (earliest deadline first) để tránh vi phạm.
     */
    public function scheduleWarningMR(int $stageCode, int $waite_time_nomal_batch = 0, int $waite_time_val_batch = 0, ?Carbon $start_date = null)
    {
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
                'sp.tank',
                'sp.keep_dry',
                'sp.order_by',
                'sp.required_room_code',
                'sp.immediately',

                'plan_master.batch',
                'plan_master.is_val',
                'plan_master.code_val',
                'plan_master.expected_date',
                'plan_master.responsed_date',

                'plan_master.after_weigth_date',
                'plan_master.after_parkaging_date',
                'plan_master.allow_weight_before_date',
                'plan_master.expired_material_date',
                'plan_master.expired_packing_date',
                'plan_master.preperation_before_date',
                'plan_master.blending_before_date',
                'plan_master.coating_before_date',
                'plan_master.parkaging_before_date',

                'finished_product_category.product_name_id',
                'finished_product_category.market_id',
                'finished_product_category.finished_product_code',
                'finished_product_category.intermediate_code',
                'product_name.name',
                'market.code as market',
                'prev.start as prev_start',
            )
            ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
            ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
            ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
            ->leftJoin('stage_plan as prev', function ($join) {
                $join->on('prev.code', '=', 'sp.predecessor_code')
                    ->whereNotIn('prev.stage_code', [1, 2]);
            })
            ->where('sp.stage_code', $stageCode)
            ->where('sp.finished', 0)
            ->where('sp.not_schedule', 0)
            ->where('sp.active', 1)
            ->whereNull('sp.start')
            ->where(function ($q) {
                // Chỉ sắp nếu predecessor đã có lịch hoặc không có predecessor (stage 3)
                $q->whereNotNull('prev.start')
                  ->orWhereNull('sp.predecessor_code');
            })
            // Chỉ lấy task có ít nhất 1 ràng buộc cảnh báo NL/BB not null
            ->where(function ($q) use ($stageCode) {
                $q->whereNotNull('plan_master.after_weigth_date')
                    ->orWhereNotNull('plan_master.allow_weight_before_date')
                    ->orWhereNotNull('plan_master.expired_material_date')
                    ->orWhereNotNull('plan_master.preperation_before_date')
                    ->orWhereNotNull('plan_master.blending_before_date')
                    ->orWhereNotNull('plan_master.coating_before_date');
                if ($stageCode == 7) {
                    $q->orWhereNotNull('plan_master.after_parkaging_date')
                        ->orWhereNotNull('plan_master.expired_packing_date')
                        ->orWhereNotNull('plan_master.parkaging_before_date');
                }
            })
            ->where('sp.deparment_code', session('user.production_code'))
            // Sắp xếp: ưu tiên task có deadline chặt nhất trước (Earliest Deadline First)
            ->orderByRaw("
                LEAST(
                    COALESCE(plan_master.expired_material_date, '9999-12-31'),
                    COALESCE(plan_master.allow_weight_before_date, '9999-12-31'),
                    COALESCE(plan_master.preperation_before_date, '9999-12-31'),
                    COALESCE(plan_master.blending_before_date, '9999-12-31'),
                    COALESCE(plan_master.coating_before_date, '9999-12-31'),
                    COALESCE(plan_master.parkaging_before_date, '9999-12-31'),
                    COALESCE(plan_master.expired_packing_date, '9999-12-31')
                ) ASC
            ")
            ->orderBy('prev.start', 'asc')
            ->get();

        if (! $tasks->isNotEmpty()) {
            return;
        }

        $processedCampaigns = [];

        foreach ($tasks as $task) {

            if ($task->is_val === 1) {
                $waite_time = $waite_time_val_batch;
            } else {
                $waite_time = $waite_time_nomal_batch;
            }

            // ─── BƯỚC 1: Lower bound (operator '>') ───────────────────────────────────
            // Task phải bắt đầu SAU ngày có đủ NL / ngày được phép cân
            $start_date_effective = $start_date ? clone $start_date : null;

            $lowerBounds = array_filter([
                $task->after_weigth_date,
                $task->allow_weight_before_date,
            ]);

            foreach ($lowerBounds as $boundDate) {
                $bound = Carbon::parse($boundDate)->setTime(6, 0, 0);
                if ($start_date_effective === null || $bound->gt($start_date_effective)) {
                    $start_date_effective = $bound;
                }
            }

            // Bỏ BƯỚC 2: Không áp dụng Just-In-Time nữa để ưu tiên xếp khi có phòng trống sớm nhất


            if ($task->campaign_code === null) {

                $this->sheduleNotCampaing($task, $stageCode, $waite_time, $start_date_effective, null);
            } else {

                if (in_array($task->campaign_code, $processedCampaigns)) {
                    continue;
                }

                $campaignTasks = $tasks->where('campaign_code', $task->campaign_code)->sortBy('batch');

                $this->scheduleCampaign($campaignTasks, $stageCode, $waite_time, $start_date_effective, null);

                $processedCampaigns[] = $task->campaign_code;
            }
        }
    }

    public function scheduleIntermediate(int $stageCode, int $waite_time_nomal_batch = 0, int $waite_time_val_batch = 0, ?Carbon $start_date = null)
    {

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
                'plan_master.responsed_date',
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
                'prev.start as prev_start',

            )
            ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
            ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
            ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
            ->leftJoin('stage_plan as prev', function ($join) {
                $join->on('prev.code', '=', 'sp.predecessor_code')
                    ->whereNotIn('prev.stage_code', [1, 2]);
            })
            ->where('sp.stage_code', $stageCode)
            ->where('sp.finished', 0)
            ->where('sp.not_schedule', 0)
            ->where('sp.active', 1)
            ->whereNull('sp.start')
            ->whereNotNull('prev.start')
            ->whereNotNull('plan_master.after_weigth_date')
            ->when($stageCode == 7, function ($q) {

                $q->whereNotNull('plan_master.after_parkaging_date');
            })
            ->where('sp.deparment_code', session('user.production_code'))
            ->orderBy('prev.start', 'asc')
            ->get();

        if (! $tasks->isNotEmpty()) {

            return;
        }

        $processedCampaigns = [];
        // campaign đã xử lý

        foreach ($tasks as $task) {

            if ($task->is_val === 1) {

                $waite_time = $waite_time_val_batch;
            } else {

                $waite_time = $waite_time_nomal_batch;
            }

            if ($task->campaign_code === null) {

                $this->sheduleNotCampaing($task, $stageCode, $waite_time, $start_date, null);
            } else {

                if (in_array($task->campaign_code, $processedCampaigns)) {

                    continue;
                }

                // Gom nhóm campaign
                $campaignTasks = $tasks->where('campaign_code', $task->campaign_code)->sortBy('batch');

                $this->scheduleCampaign($campaignTasks, $stageCode, $waite_time, $start_date, null);

                // Đánh dấu campaign đã xử lý
                $processedCampaigns[] = $task->campaign_code;
            }

            // $this->order_by++;
        }
    }

    public function scheduleSensitiveProduct(int $stageCode, int $waite_time_nomal_batch = 0, int $waite_time_val_batch = 0, ?Carbon $start_date = null)
    {

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
                'plan_master.responsed_date',
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

                'prev.start as prev_start',

                'intermediate_category.quarantine_total'   // lấy start của công đoạn trước
            )
            ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
            ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
            ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', 'intermediate_category.intermediate_code')
            ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
            ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
            ->leftJoin('stage_plan as prev', function ($join) {
                $join->on('prev.code', '=', 'sp.predecessor_code')
                    ->whereNotIn('prev.stage_code', [1, 2]);
            })
            ->where('sp.not_schedule', 0)
            ->where('sp.stage_code', $stageCode)
            ->where('sp.finished', 0)
            ->where('sp.active', 1)
            ->where('intermediate_category.quarantine_total', '>', 0)
            ->whereNull('sp.start')
            ->whereNotNull('plan_master.after_weigth_date')
            ->when($stageCode == 7, function ($q) {

                $q->whereNotNull('plan_master.after_parkaging_date');
            })
            ->where('sp.deparment_code', session('user.production_code'))
            ->orderBy('prev.start', 'asc')
            ->get();

        if (! $tasks->isNotEmpty()) {

            return;
        }

        $processedCampaigns = [];
        // campaign đã xử lý

        foreach ($tasks as $task) {

            if ($task->is_val === 1) {

                $waite_time = $waite_time_val_batch;
            } else {

                $waite_time = $waite_time_nomal_batch;
            }

            $start_date_temp = $start_date;

            if ($task->campaign_code === null) {

                $startDate_responsed_date = Carbon::parse($task->responsed_date)->subDays((int) $task->quarantine_total);

                if ($startDate_responsed_date->gt($start_date)) {

                    $start_date_temp = $startDate_responsed_date;
                }

                $this->sheduleNotCampaing($task, $stageCode, $waite_time, $start_date_temp, null);
            } else {

                if (in_array($task->campaign_code, $processedCampaigns)) {

                    continue;
                }

                $campaignTasks = $tasks->where('campaign_code', $task->campaign_code)->sortBy('batch');

                $startDate_responsed_date = Carbon::parse($task->responsed_date)->subDays((int) $task->quarantine_total);

                if ($startDate_responsed_date->gt($start_date)) {

                    $start_date_temp = $startDate_responsed_date;
                }

                $this->scheduleCampaign($campaignTasks, $stageCode, $waite_time, $start_date_temp, null);

                // Đánh dấu campaign đã xử lý
                $processedCampaigns[] = $task->campaign_code;
            }

            // $this->order_by++;
        }
    }

    public function Auto_scheduler_Stage_Forward(int $stageCode, int $waite_time_nomal_batch = 0, int $waite_time_val_batch = 0, ?Carbon $start_date = null)
    {

        if ($this->prev_orderBy && $stageCode > 3) {

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

                    'prev.start as prev_start',

                    // 'intermediate_category.quarantine_total'   // lấy start của công đoạn trước
                )
                ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
                ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
                // ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', 'intermediate_category.intermediate_code')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                ->leftJoin('stage_plan as prev', function ($join) {
                    $join->on('prev.code', '=', 'sp.predecessor_code')
                        ->whereNotIn('prev.stage_code', [1, 2]);
                })
                ->where('sp.stage_code', $stageCode)
                ->where('sp.finished', 0)
                ->where('sp.active', 1)
                ->whereNull('sp.start')
                ->where('sp.not_schedule', 0)
                ->whereNotNull('plan_master.after_weigth_date')
                ->where('sp.deparment_code', session('user.production_code'))
                ->orderBy('prev.start', 'asc')
                ->get();
        } else {

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
                )
                ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
                ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                ->where('sp.stage_code', $stageCode)
                ->where('sp.finished', 0)
                ->where('sp.active', 1)
                ->whereNull('sp.start')
                ->where('sp.not_schedule', 0)
                ->whereNotNull('plan_master.after_weigth_date')
                ->when($stageCode == 7, function ($q) {

                    $q->whereNotNull('plan_master.after_parkaging_date');
                })
                ->where('sp.deparment_code', session('user.production_code'))
                ->orderBy('order_by', 'asc')
                ->get();
        }


        //Log::info(['tasks' => $tasks]);
        //return;

        $processedCampaigns = [];
        // campaign đã xử lý

        foreach ($tasks as $task) {

            if ($task->is_val === 1) {

                $waite_time = $waite_time_val_batch;
            } else {

                $waite_time = $waite_time_nomal_batch;
            }

            if ($task->campaign_code === null) {

                $this->sheduleNotCampaing($task, $stageCode, $waite_time, $start_date, null);
            } else {

                if (in_array($task->campaign_code, $processedCampaigns)) {

                    continue;
                }

                // Gom nhóm campaign
                $campaignTasks = $tasks->where('campaign_code', $task->campaign_code)->sortBy('batch');

                $this->scheduleCampaign($campaignTasks, $stageCode, $waite_time, $start_date, null);

                // Đánh dấu campaign đã xử lý
                $processedCampaigns[] = $task->campaign_code;
            }

            // $this->order_by++;
        }
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

    public function scheduleLine(string $required_room, array $stage_plan_ids, int $stageCode, int $waite_time_nomal_batch = 0, int $waite_time_val_batch = 0, ?Carbon $start_date = null)
    {

        if ($this->prev_orderBy && $stageCode >= 4) {

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

                    'prev.start as prev_start'   // lấy start của công đoạn trước
                )
                ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
                ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                ->leftJoin('stage_plan as prev', function ($join) {
                    $join->on('prev.code', '=', 'sp.predecessor_code')
                        ->whereNotIn('prev.stage_code', [1, 2]);
                })
                ->whereNotNull('prev.start')
                ->where('sp.not_schedule', 0)
                ->whereIn('sp.id', $stage_plan_ids)
                ->whereNotNull('plan_master.after_weigth_date')
                ->when($stageCode == 7, function ($q) {

                    $q->whereNotNull('plan_master.after_parkaging_date');
                })
                ->where('sp.deparment_code', session('user.production_code'))
                ->orderBy('prev.start', 'asc')
                ->get();
        } else {

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
                    'market.code as market'
                )
                ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
                ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                ->whereIn('sp.id', $stage_plan_ids)
                ->whereNotNull('plan_master.after_weigth_date')
                ->when($stageCode == 7, function ($q) {

                    $q->whereNotNull('plan_master.after_parkaging_date');
                })
                ->when($stageCode >= 4, function ($query) {

                    $query->leftJoin('stage_plan as prev', function ($join) {
                        $join->on('prev.code', '=', 'sp.predecessor_code')
                            ->whereNotIn('prev.stage_code', [1, 2]);
                    })
                        ->whereNotNull('prev.start');
                })
                ->where('sp.deparment_code', session('user.production_code'))
                ->orderBy('order_by_line', 'asc')
                ->get();
        }

        $processedCampaigns = [];
        // campaign đã xử lý

        foreach ($tasks as $task) {

            if ($task->is_val === 1) {

                $waite_time = $waite_time_val_batch;
            } else {

                $waite_time = $waite_time_nomal_batch;
            }

            if ($task->campaign_code === null) {

                $this->sheduleNotCampaing($task, $stageCode, $waite_time, $start_date, $required_room);
            } else {

                if (in_array($task->campaign_code, $processedCampaigns)) {

                    continue;
                }

                // Gom nhóm campaign
                $campaignTasks = $tasks->where('campaign_code', $task->campaign_code)->sortBy('batch');

                $this->scheduleCampaign($campaignTasks, $stageCode, $waite_time, $start_date, $required_room);

                // Đánh dấu campaign đã xử lý
                $processedCampaigns[] = $task->campaign_code;
            }

            $this->order_by++;
        }
    }

    protected function sheduleNotCampaing($task, $stageCode, int $waite_time = 0, ?Carbon $start_date = null, ?string $Line = null)
    {
        $pm = DB::table('plan_master')->where('id', $task->plan_master_id)->first();
        if ($pm && $pm->main_parkaging_id != $pm->id) {
            return; // Là lô con, sẽ được xếp lịch cùng lô mẹ
        }

        $mySubs = DB::table('stage_plan')
            ->join('plan_master', 'stage_plan.plan_master_id', '=', 'plan_master.id')
            ->where('plan_master.main_parkaging_id', $task->plan_master_id)
            ->where('stage_plan.plan_master_id', '!=', $task->plan_master_id)
            ->where('stage_plan.stage_code', $stageCode)
            ->where('stage_plan.finished', 0)
            ->whereNull('stage_plan.start')
            ->select('stage_plan.*', 'plan_master.main_parkaging_id as pm_main_id')
            ->orderBy('plan_master.id')
            ->get();

        if ($mySubs->isNotEmpty()) {
            $familyTasks = collect([$task])->concat($mySubs);
            return $this->scheduleCampaign($familyTasks, $stageCode, $waite_time, $start_date, $Line, 0);
        }

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

                            $rooms = $rooms->where('room_id', '!=', $first->resourceId);
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

        // --- NEW LOGIC: Room Linking ---
        if ($stageCode == 4 && $task->predecessor_code != null) {
            $pred = DB::table('stage_plan')->where('code', $task->predecessor_code)->first();
            if ($pred && $pred->resourceId) {
                // Check if there is a mandatory link
                $link = DB::table('room_links')
                    ->where('source_room_id', $pred->resourceId)
                    ->where('active', 1)
                    ->first();
                if ($link) {
                    $filtered = $rooms->where('room_id', $link->target_room_id)->values();
                    if (!$filtered->isEmpty()) {
                        $rooms = $filtered;
                    }
                }
            }
        }
        // -------------------------------

        // phòng phù hợp (quota)
        if ($rooms->isEmpty()) {
            return;
        }

        $bestRoom = null;
        $bestStart = null;
        $bestMoldId = null;

        // tim phòng tối ưu
        $ratio = 1;

        if ($stageCode == 7) {
            $pm = DB::table('plan_master')
                ->where('id', $task->plan_master_id)
                ->select('only_parkaging', 'percent_parkaging')
                ->first();

            if ($pm) {
                $ratio = (float) ($pm->percent_parkaging ?? 1);
            }
        }

        $allCompatibleMolds = null;
        if ($stageCode == 7) {
            $allCompatibleMolds = DB::table('finished_product_mold')
                ->join('blister_mold', 'finished_product_mold.blister_mold_id', '=', 'blister_mold.id')
                ->where('finished_product_mold.finished_product_category_id', $task->product_caterogy_id)
                ->where('blister_mold.active', 1)
                ->select('blister_mold.id', 'blister_mold.code', 'blister_mold.amount', 'blister_mold.blister_type_code')
                ->get();
        }

        foreach ($rooms as $room) {
            $p_adj = (float) $room->p_time_minutes * $ratio;
            $m_adj = (float) $room->m_time_minutes * $ratio;
            $intervalTimeMinutes = $p_adj + $m_adj;

            $C2_time_minutes = (float) $room->C2_time_minutes;

            $compatibleMolds = null;
            if ($stageCode == 7 && $allCompatibleMolds && $allCompatibleMolds->isNotEmpty()) {
                // SP có khai báo khuôn → lọc theo loại máy
                $roomType = DB::table('room')->where('id', $room->room_id)->value('blister_type_code');
                $filtered = $allCompatibleMolds->filter(function ($m) use ($roomType) {
                    $moldTypes = [];
                    if (!empty($m->blister_type_code)) {
                        $decoded = json_decode($m->blister_type_code, true);
                        $moldTypes = is_array($decoded) ? $decoded : [$m->blister_type_code];
                    }
                    return empty($roomType) || empty($m->blister_type_code) || in_array($roomType, $moldTypes);
                })->values()->toArray();

                if (empty($filtered)) {
                    continue; // Phòng này không có khuôn lắp vừa → bỏ qua
                }
                $compatibleMolds = $filtered;
            }
            // Nếu SP chưa khai báo khuôn: $compatibleMolds = null → sắp lịch bình thường

            $candidate = $this->findEarliestSlot2(
                $room->room_id,
                $earliestStart,
                $intervalTimeMinutes,
                $C2_time_minutes,
                $task->tank,
                $task->keep_dry,
                'stage_plan',
                2,
                60,
                $compatibleMolds
            );

            $candidateStart = is_array($candidate) ? $candidate['start'] : $candidate;
            $candidateMoldId = is_array($candidate) ? $candidate['mold_id'] : null;

            if ($candidateStart !== null && ($bestStart === null || $candidateStart->lt($bestStart))) {
                $bestRoom = $room->room_id;
                $bestStart = $candidateStart;
                $bestMoldId = $candidateMoldId;
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
            $bestMoldId
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
        $main_ids = $campaignTasks->pluck('plan_master_id')->unique();
        $subTasks = DB::table('stage_plan')
            ->join('plan_master', 'stage_plan.plan_master_id', '=', 'plan_master.id')
            ->whereIn('plan_master.main_parkaging_id', $main_ids)
            ->whereNotIn('stage_plan.plan_master_id', $main_ids)
            ->where('stage_plan.stage_code', $stageCode)
            ->where('stage_plan.finished', 0)
            ->whereNull('stage_plan.start')
            ->select('stage_plan.*', 'plan_master.main_parkaging_id as pm_main_id')
            ->orderBy('plan_master.id')
            ->get();

        if ($subTasks->isNotEmpty()) {
            $finalCampaignTasks = collect();
            foreach ($campaignTasks as $ev) {
                $finalCampaignTasks->push($ev);
                $subs = $subTasks->where('pm_main_id', $ev->plan_master_id);
                foreach ($subs as $sub) {
                    $finalCampaignTasks->push($sub);
                }
            }
            $campaignTasks = $finalCampaignTasks;
        }

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

        $avg_m_time = DB::table('quota')
            ->selectRaw('AVG(TIME_TO_SEC(m_time)/60) as avg_m_time_minutes')
            ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {
                return $query->where('intermediate_code', $firstTask->intermediate_code);
            }, function ($query) use ($firstTask) {
                return $query->where('finished_product_code', $firstTask->finished_product_code);
            })
            ->where('active', 1)
            ->where('stage_code', $stageCode)
            ->value('avg_m_time_minutes') ?? 15;

        $avg_C1_time = DB::table('quota')
            ->selectRaw('AVG(TIME_TO_SEC(C1_time)/60) as avg_C1_time_minutes')
            ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {
                return $query->where('intermediate_code', $firstTask->intermediate_code);
            }, function ($query) use ($firstTask) {
                return $query->where('finished_product_code', $firstTask->finished_product_code);
            })
            ->where('active', 1)
            ->where('stage_code', $stageCode)
            ->value('avg_C1_time_minutes') ?? 0;

        // avg_slot_time = thời gian trung bình mỗi lô chiếm (m_time + C1_time)
        $avg_slot_time = $avg_m_time + $avg_C1_time;

        $batch_index = 0;
        foreach ($campaignTasks as $campaignTask) {
            $pred = DB::table('stage_plan')->where('code', $campaignTask->predecessor_code)->first();
            if ($pred && !in_array($pred->stage_code, [1, 2])) {
                // Công thức đúng: pred_end[N] - N * slot_per_batch
                // Ý nghĩa: nếu campaign bắt đầu tại T, lô N bắt đầu tại T + N*slot_time
                // Để lô N >= pred_end[N]: T >= pred_end[N] - N*slot_time
                $candidates[] = Carbon::parse($pred->end)->addMinutes($waite_time)->subMinutes($batch_index * $avg_slot_time);
            }
            $batch_index++;
        }

        // Lấy max → đây là thời điểm sớm nhất hợp lệ để bắt đầu campaign
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

                            $rooms = $rooms->where('room_id', '!=', $first->resourceId);
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

        // liên hê giữa pc và tht (Room Links logic)
        if ($stageCode == 4 && $firstTask->predecessor_code && $rooms->count() > 1) {
            $resourceId_prev = DB::table('stage_plan')
                ->where('code', $firstTask->predecessor_code)
                ->value('resourceId');
            if ($resourceId_prev) {
                $link = DB::table('room_links')
                    ->where('source_room_id', $resourceId_prev)
                    ->where('active', 1)
                    ->first();
                if ($link) {
                    $filtered = $rooms->where('room_id', $link->target_room_id)->values();
                    if (!$filtered->isEmpty()) {
                        $rooms = $filtered;
                    }
                }
            }
        }

        $bestRoom = null;
        $bestStart = null;
        $bestMoldId = null;

        // tim phòng tối ưu
        $campaign_ratio = 1;
        if ($stageCode == 7) {
            $cpm = DB::table('plan_master')->where('id', $firstTask->plan_master_id)->select('only_parkaging', 'percent_parkaging')->first();
            if ($cpm) {
                $campaign_ratio = (float) ($cpm->percent_parkaging ?? 1);
            }
        }

        $allCompatibleMolds = null;
        if ($stageCode == 7) {
            $allCompatibleMolds = DB::table('finished_product_mold')
                ->join('blister_mold', 'finished_product_mold.blister_mold_id', '=', 'blister_mold.id')
                ->where('finished_product_mold.finished_product_category_id', $firstTask->product_caterogy_id)
                ->where('blister_mold.active', 1)
                ->select('blister_mold.id', 'blister_mold.code', 'blister_mold.amount', 'blister_mold.blister_type_code')
                ->get();
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

            $compatibleMolds = null;
            if ($stageCode == 7 && $allCompatibleMolds && $allCompatibleMolds->isNotEmpty()) {
                // SP có khai báo khuôn → lọc theo loại máy
                $roomType = DB::table('room')->where('id', $room->room_id)->value('blister_type_code');
                $filtered = $allCompatibleMolds->filter(function ($m) use ($roomType) {
                    $moldTypes = [];
                    if (!empty($m->blister_type_code)) {
                        $decoded = json_decode($m->blister_type_code, true);
                        $moldTypes = is_array($decoded) ? $decoded : [$m->blister_type_code];
                    }
                    return empty($roomType) || empty($m->blister_type_code) || in_array($roomType, $moldTypes);
                })->values()->toArray();

                if (empty($filtered)) {
                    continue; // Phòng này không có khuôn lắp vừa → bỏ qua
                }
                $compatibleMolds = $filtered;
            }
            // Nếu SP chưa khai báo khuôn: $compatibleMolds = null → sắp lịch bình thường

            $candidate = $this->findEarliestSlot2(
                $room->room_id,
                $earliestStart,
                $totalMunites,
                0,
                $firstTask->tank,
                $firstTask->keep_dry,
                'stage_plan',
                2,
                60,
                $compatibleMolds
            );

            $candidateStart = is_array($candidate) ? $candidate['start'] : $candidate;
            $candidateMoldId = is_array($candidate) ? $candidate['mold_id'] : null;

            if ($candidateStart !== null && ($bestStart === null || $candidateStart->lt($bestStart))) {
                $bestRoom = $room;
                $bestStart = $candidateStart;
                $bestMoldId = $candidateMoldId;
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

            $bestStart = $this->skipOffTime($bestStart, $this->offDate, $bestRoom->room_id);

            // Tỉ lệ theo từng batch
            $task_ratio = 1;
            if ($stageCode == 7) {
                $tpm = DB::table('plan_master')->where('id', $task->plan_master_id)->select('only_parkaging', 'percent_parkaging')->first();
                if ($tpm) {
                    $task_ratio = (float) ($tpm->percent_parkaging ?? 1);
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
                $bestMoldId
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
                ->leftJoin('stage_plan as prev', function ($join) {
                    $join->on('prev.code', '=', 'sp.predecessor_code')
                        ->whereNotIn('prev.stage_code', [1, 2]);
                })
                ->whereIn('sp.code', $nextcessor_codes)
                // ->where('sp.stage_code', $nextcessor_code)
                ->where('sp.active', 1)
                ->where('sp.finished', 0)
                ->where('sp.not_schedule', 0)
                ->whereNull('sp.start')  // Chỉ sắp task chưa có lịch
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

    protected function scheduleweight($tasks, int $waite_time = 0, $mode = false, ?Carbon $start_date = null)
    {

        $now = Carbon::now();

        $minute = $now->minute;

        $roundedMinute = ceil($minute / 15) * 15;

        if ($roundedMinute == 60) {

            $now->addHour();

            $roundedMinute = 0;
        }

        $now->minute($roundedMinute)->second(0)->microsecond(0);

        $candidates[] = $now;

        if ($mode) {

            $task = $tasks->first();

            $start = Carbon::parse($tasks->min('next_start'))->setTime(6, 0, 0);
        } else {

            $task = $tasks;

            $start = Carbon::parse($task->next_start)->setTime(6, 0, 0);
        }

        $daysToSubtract = 3;

        while ($daysToSubtract > 0) {

            $start->subDay();

            // nếu không phải ngày nghỉ → tính là 1 ngày làm việc
            if (! in_array($start->toDateString(), $this->selectedDates, true)) {

                $daysToSubtract--;
            }
        }

        $candidates[] = $start;

        $candidates[] = $start_date;

        // nếu có after_weigth_date
        if (! empty($task->after_weigth_date)) {

            $candidates[] = Carbon::parse($task->after_weigth_date);
        }

        if (! empty($task->allow_weight_before_date)) {

            $candidates[] = Carbon::parse($task->allow_weight_before_date);
        }

        // Lấy max
        $earliestStart = collect($candidates)->max();

        // chọn phòng sx
        if ($task->required_room_code != null) {

            if ($task->required_room_code != null) {

                $room_code = $task->required_room_code;
            }

            $room_id = DB::table('room')->where('code', $room_code)->value('id');

            $rooms = DB::table('quota')->select(
                'room_id',
                'campaign_index',
                'maxofbatch_campaign',
                DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
            )
                ->where('intermediate_code', $task->intermediate_code)
                ->where('stage_code', $task->stage_code)
                ->where('room_id', $room_id)
                ->get();
        } else {

            $rooms = DB::table('quota')->select(
                'room_id',
                'campaign_index',
                'maxofbatch_campaign',
                DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
            )
                ->where('intermediate_code', $task->intermediate_code)
                ->where('stage_code', $task->stage_code)
                ->where('active', 1)
                ->orderBy('room_id', 'desc')
                ->get();
        }

        // phòng phù hợp (quota)

        $bestRoom = null;

        $bestStart = null;

        $clearning_type = 2;

        $maxofbatch_campaign = 1;

        // tim phòng tối ưu
        foreach ($rooms as $room) {

            if ($mode) {

                $campaign_index = 1 + ($room->campaign_index - 1) * $tasks->count();
            } else {

                $campaign_index = 1;
            }

            $intervalTimeMinutes = (float) $room->p_time_minutes + ((float) $room->m_time_minutes) * (float) $campaign_index;

            if ((float) $room->C2_time_minutes > 0) {

                $C2_time_minutes = (float) $room->C2_time_minutes;

                $clearning_type = 2;
            } else {

                $C2_time_minutes = (float) $room->C1_time_minutes;

                $clearning_type = 1;
            }

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

                $maxofbatch_campaign = $room->maxofbatch_campaign;
            }
        }

        $bestStart = $this->skipOffTime($bestStart, $this->offDate, $bestRoom);

        $bestEnd = $this->addWorkingMinutes($bestStart->copy(), (float) $intervalTimeMinutes, $bestRoom, $this->work_sunday);

        $start_clearning = $bestEnd->copy();

        $end_clearning = $this->addWorkingMinutes($start_clearning->copy(), (float) $C2_time_minutes, $bestRoom, $this->work_sunday);

        if ($mode) {

            $count_max = 1;

            foreach ($tasks as $task) {

                $this->saveSchedule(
                    1,
                    $task->id,
                    $bestRoom,
                    $bestStart,
                    $bestEnd,
                    $start_clearning,
                    $end_clearning,
                    $clearning_type,
                    1,
                );

                $count_max++;

                $this->processed_stage_code_Id[] = $task->id;

                if ($count_max > $maxofbatch_campaign) {
                    return;
                }
            }
        } else {

            $this->saveSchedule(
                1,
                $task->id,
                $bestRoom,
                $bestStart,
                $bestEnd,
                $start_clearning,
                $end_clearning,
                $clearning_type,
                1,
            );

            $this->processed_stage_code_Id[] = $task->id;
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
        $loop_count2 = 0;

        while (true) {
            $loop_count2++;
            if ($loop_count2 > 1000) {
                return null;
            }

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


    protected function syncPackagingDate($stagePlanId, $date, $type, $updateType = null)
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

        $latestDateStr = ($latest && $latest->receive_packaging_date) ? \Carbon\Carbon::parse($latest->receive_packaging_date)->format('Y-m-d') : null;
        $newDateStr = $date ? \Carbon\Carbon::parse($date)->format('Y-m-d') : null;

        if (! $latest || $latestDateStr !== $newDateStr) {
            DB::table('packaging_issuance_date')->insert([
                'stage_plane_id' => $stagePlanId,
                'type_packaging' => $type,
                'receive_packaging_date' => $date,
                'ver' => ($latest->ver ?? 0) + 1,
                'type' => $updateType,
                'created_at' => now(),
                'created_by' => session('user')['fullName'] ?? 'System',
            ]);
        }
    }
    public function checkMissingMoldQuotas(Request $request)
    {
        try {
            $type = $request->input('type', 'missing');
            $now = now();
            $productionCode = session('user')['production_code'] ?? null;

            $query = DB::table('stage_plan')
                ->where('stage_code', 7)
                ->where('finished', 0)
                ->whereNotNull('start')
                ->where('start', '>=', $now);

            if ($productionCode) {
                $query->where('deparment_code', $productionCode);
            }

            if ($type === 'missing') {
                $query->whereNull('blister_mold_id');
            }

            $plans = $query->get();

            $missingProducts = [];

            // Lấy danh sách danh mục sản phẩm (ID)
            $categoryIds = $plans->pluck('product_caterogy_id')->unique()->filter()->toArray();

            if (!empty($categoryIds)) {
                // Kiểm tra xem danh mục nào KHÔNG CÓ khuôn hợp lệ
                $categoriesWithMolds = DB::table('finished_product_mold')
                    ->join('blister_mold', 'finished_product_mold.blister_mold_id', '=', 'blister_mold.id')
                    ->whereIn('finished_product_mold.finished_product_category_id', $categoryIds)
                    ->where('blister_mold.active', 1)
                    ->pluck('finished_product_mold.finished_product_category_id')
                    ->unique()
                    ->toArray();

                foreach ($plans as $plan) {
                    if ($plan->product_caterogy_id && !in_array($plan->product_caterogy_id, $categoriesWithMolds)) {
                        // Sản phẩm này không có khai báo khuôn
                        $missingProducts[] = $plan->title;
                    }
                }
            }

            // Loại bỏ trùng lặp tên sản phẩm nếu có nhiều lô cùng sản phẩm
            $missingProducts = array_values(array_unique($missingProducts));

            return response()->json([
                'success' => true,
                'missing' => $missingProducts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi kiểm tra định mức khuôn: ' . $e->getMessage()
            ], 500);
        }
    }

    public function autoAllocateMold(Request $request)
    {
        try {
            $type = $request->input('type', 'missing');
            $now = now();
            $productionCode = session('user')['production_code'] ?? null;

            if ($type === 'all') {
                $resetQuery = DB::table('stage_plan')
                    ->where('stage_code', 7)
                    ->where('finished', 0)
                    ->where('start', '>=', $now);

                if ($productionCode) {
                    $resetQuery->where('deparment_code', $productionCode);
                }

                $resetQuery->update(['blister_mold_id' => null]);
            }

            $plansQuery = DB::table('stage_plan')
                ->where('stage_code', 7)
                ->where('finished', 0)
                ->whereNull('blister_mold_id')
                ->whereNotNull('start')
                ->where('start', '>=', $now);

            if ($productionCode) {
                $plansQuery->where('deparment_code', $productionCode);
            }

            $plans = $plansQuery->orderBy('start', 'asc')->get();

            $allocatedCount = 0;

            foreach ($plans as $plan) {
                $room = DB::table('room')->where('id', $plan->resourceId)->first();
                $roomType = $room ? $room->blister_type_code : null;

                $compatibleMoldsQuery = DB::table('finished_product_mold')
                    ->join('blister_mold', 'finished_product_mold.blister_mold_id', '=', 'blister_mold.id')
                    ->where('finished_product_mold.finished_product_category_id', $plan->product_caterogy_id)
                    ->where('blister_mold.active', 1);

                if (!empty($roomType)) {
                    $compatibleMoldsQuery->where(function ($q) use ($roomType) {
                        $q->where('blister_mold.blister_type_code', $roomType)
                            ->orWhere('blister_mold.blister_type_code', 'LIKE', '%"' . $roomType . '"%')
                            ->orWhereNull('blister_mold.blister_type_code');
                    });
                }

                $compatibleMolds = $compatibleMoldsQuery->select('blister_mold.id', 'blister_mold.code', 'blister_mold.amount')->get();

                foreach ($compatibleMolds as $mold) {
                    if ($mold->amount > 0) {
                        $concurrentCount = DB::table('stage_plan')
                            ->where('stage_code', 7)
                            ->where('blister_mold_id', $mold->id)
                            ->where('active', 1)
                            ->where('finished', 0)
                            ->whereNotNull('start')
                            ->where(function ($q) use ($plan) {
                                $q->where('start', '<', $plan->end)
                                    ->where('end', '>', $plan->start);
                            })
                            ->pluck('resourceId')
                            ->unique()
                            ->count();

                        if ($concurrentCount < $mold->amount) {
                            DB::table('stage_plan')
                                ->where('id', $plan->id)
                                ->update(['blister_mold_id' => $mold->id]);
                            $allocatedCount++;
                            break;
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Đã phân bổ khuôn cho {$allocatedCount} lô sản xuất.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateBlisterMold(Request $request)
    {
        try {
            $stagePlanId = $request->input('stage_plan_id');
            $moldId = $request->input('blister_mold_id');

            DB::table('stage_plan')
                ->where('id', $stagePlanId)
                ->update(['blister_mold_id' => $moldId ?: null]);

            $plan_waiting = $this->getSumaryDataArray(session('user.production_code'));
            return response()->json([
                'success' => true,
                'plan' => $plan_waiting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
}

function toMinutes($time)
{

    [$hours,  $minutes] = explode(':', $time);

    return ((int) $hours) * 60 + (int) $minutes;
}

function minutesToDayHoursMinutesString(int $minutes): string
{

    $days = intdiv($minutes, 1440);
    // 60 * 24
    $remain = $minutes % 1440;

    $hours = intdiv($remain, 60);

    $mins = $remain % 60;

    return ($days > 0 ? "{$days}d " : '')
        . ($hours > 0 ? "{$hours}h" : '')
        . "{$mins}p";
}

function minutesToHoursMinutes(int $minutes): array
{

    $hours = intdiv($minutes, 60);

    $mins = $minutes % 60;

    return [$hours,  $mins];
}




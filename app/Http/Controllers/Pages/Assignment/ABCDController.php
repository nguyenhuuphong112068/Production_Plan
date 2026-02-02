<?php

namespace App\Http\Controllers\Pages\Assignment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Pages\Schedual\SchedualController;

class ABCDController extends Controller
{
    

        protected $theory = 0;
        protected $stage_Name = [
                1 => "Cân NL",
                3 => "PC",
                4 => "THT",
                5 => "ĐH",
                6 => "BP",
                7 => "ĐG",
        ];

         //Thời gian của từng phòng
        public function getRoomStatistics($startDate, $endDate){
                // chuẩn hoá ngày giờ (chuỗi dạng MySQL)
                $start = Carbon::parse($startDate)->format('Y-m-d H:i:s') ?? '2026-01-01 00:00:00';
                $end   = Carbon::parse($endDate)->format('Y-m-d H:i:s')?? '2026-01-31 00:00:00';

                $totalSeconds = Carbon::parse($start)->diffInSeconds(Carbon::parse($end));

                $selectRaw = '
                        sp.resourceId,
                        ? / 3600 as total_hours,
                        SUM(
                        GREATEST(
                                0,
                                TIMESTAMPDIFF(
                                SECOND,
                                GREATEST(sp.start, ?),
                                LEAST( COALESCE(sp.end_clearning, sp.end, sp.start), ? )
                                )
                        )
                        ) / 3600 as busy_hours
                ';

                $query = DB::table("stage_plan as sp")
                        ->selectRaw($selectRaw, [$totalSeconds, $start, $end])
                        ->where('sp.deparment_code', session('user')['production_code'])
                        ->whereRaw('GREATEST(sp.start, ?) < LEAST(COALESCE(sp.end_clearning, sp.end, sp.start), ?)', [$start, $end])
                        ->groupBy('sp.resourceId');

                $data = $query->get();

                // bảo đảm không null và tính free_hours
                $result = $data->map(function ($item) {
                        $item->busy_hours = $item->busy_hours ?? 0;
                        $item->free_hours = ($item->total_hours ?? 0) - $item->busy_hours;
                        return $item;
                });

                //dd ( $result);

                return $result;
        }

        // trả về tổngsản lượng lý thuyết
        public function yield($startDate, $endDate, $group_By){

                $startDate = Carbon::parse($startDate);
                $endDate = Carbon::parse($endDate);

                $stage_plan_100 = DB::table("stage_plan as sp")
                ->whereRaw('((sp.start >= ? AND sp.end <= ?))', [ $startDate, $endDate])
                ->whereNotNull('sp.start')
                ->where('sp.deparment_code', session('user')['production_code'])
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
                ->groupBy("sp.$group_By", "unit")
                ->get();

                
                $stage_plan_part = DB::table("stage_plan as sp")
                ->whereRaw('(sp.start < ? AND sp.end > ?) AND NOT (sp.start >= ? AND sp.end <= ?)', [$endDate, $startDate, $startDate, $endDate])
                ->whereNotNull('sp.start')
                ->where('sp.deparment_code', session('user')['production_code'])
                ->select(
                        "sp.$group_By",
                        DB::raw('
                        SUM(
                                sp.Theoretical_yields *
                                TIME_TO_SEC(TIMEDIFF(LEAST(sp.end, "'.$endDate.'"), GREATEST(sp.start, "'.$startDate.'"))) /
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
                ->groupBy("sp.$group_By", "unit")
                ->get();

                $merged = $stage_plan_100->merge($stage_plan_part)
                        ->groupBy(function ($item) use ($group_By) {
                        return $item->$group_By . '-' . $item->unit;
                        })
                        ->map(function ($items) use ($group_By) {
                        return (object)[
                                $group_By => $items->first()->$group_By,
                                'unit' => $items->first()->unit,
                                'total_qty' => round($items->sum('total_qty'), 2), // 👈 làm tròn 2 chữ số
                        ];
                        })
                ->values();

                return $merged;

        }

        protected function getEvents($production, $startDate, $endDate, $clearning, int $theory){

                $startDate = Carbon::parse($startDate)->toDateTimeString();
                $endDate   = Carbon::parse($endDate)->toDateTimeString();

                $room_code = DB::table('room')->where('deparment_code', $production)->pluck('code', 'id');

                $maxFinishedStage = DB::table('stage_plan')
                ->where('finished', 1)
                ->where('stage_plan.stage_code', 3)
                ->select(
                        'plan_master_id',
                        DB::raw('MAX(stage_code) as max_finished_stage')
                )
                ->groupBy('plan_master_id');

                // 2️⃣ Lấy danh sách stage_plan (gộp toàn bộ join)
                $event_plans = DB::table("stage_plan as sp")
                        ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
                        ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
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
                        ->where('sp.stage_code', 3)
                        ->where('sp.active', 1)
                        ->whereNotNull('sp.resourceId')
                        ->when(!in_array(session('user')['userGroup'], ['Schedualer', 'Admin', 'Leader']),fn($query) => $query->where('sp.submit', 1))
                        ->where('sp.deparment_code', $production)
                        ->where(function ($q) {
                        $q->whereNotNull('sp.start')
                        ->orWhereNotNull('sp.actual_start');
                        })
                        ->where(function ($q) use ($startDate, $endDate) {
                                $q->whereRaw('(sp.start <= ? AND sp.end >= ?)',[$endDate, $startDate])
                                ->orWhereRaw('(sp.start_clearning <= ? AND sp.end_clearning >= ?)', [$endDate, $startDate])
                                ->orWhereRaw('(sp.actual_start <= ? AND sp.actual_end >= ?)',[$endDate, $startDate])
                                ->orWhereRaw('(sp.actual_start_clearning <= ? AND sp.actual_end_clearning >= ?)',[$endDate, $startDate]);
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
                                        WHEN sp.stage_code = 9 THEN sp.title
                                        ELSE CONCAT(
                                        product_name.name,
                                        '-',
                                        COALESCE(plan_master.actual_batch, plan_master.batch)
                                        )
                                END AS title
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

                        'sp.title_clearning',
                        'sp.resourceId',
                        'sp.plan_master_id',
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
                        
                        'plan_master.is_val',
                        'plan_master.level',
                        'intermediate_category.quarantine_total',

                        DB::raw("CASE
                                        WHEN sp.stage_code = 7 THEN 
                                        CONCAT(finished_product_category.intermediate_code, '_', finished_product_category.finished_product_code)
                                        ELSE 
                                        CONCAT(finished_product_category.intermediate_code, '_NA')
                                END as process_code
                                "),

                        DB::raw("
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
                                END as quarantine_time_limit_hour")
                        )
                        ->orderBy('sp.plan_master_id')
                        ->orderBy('sp.stage_code')
                ->get();

                

                // 4️⃣ Gom nhóm theo plan_master_id
                $groupedPlans = $event_plans->groupBy('plan_master_id');
                $events = collect();

        
                // 5️⃣ Duyệt từng nhóm (theo batch sản xuất)
                foreach ($groupedPlans as $plans) {
                        $plans = $plans->values(); // sắp sẵn theo stage_code ở query
                        for ($i = 0, $n = $plans->count(); $i < $n; $i++) {
                                $storage_capacity = null;
                                $plan = $plans[$i];
                                $subtitle = null;

                                [$color_event, $textColor, $subtitle] = $this->colorEvent($plan, $plans, $i, $room_code);
                
                                // 🎯 Lịch chưa hoàn thành
                                if (($plan->start && !$plan->actual_start && $plan->finished == 0) ) {
                                        $events->push([
                                                'plan_id' => $plan->id,
                                                'id' => "{$plan->id}-main",
                                                'title' => $plan->title ."-". $plan->w2,
                                                'start' =>  $plan->start,
                                                'end' =>  $plan->end,
                                                'resourceId' => $plan->resourceId,
                                                'color' =>  $plan->finished == 1?'#002af9ff': $color_event,
                                                'textColor' => $textColor,
                                                'plan_master_id' => $plan->plan_master_id,
                                                'stage_code' => $plan->stage_code,
                                                'is_clearning' => false,
                                                'finished' => $plan->finished,
                                                'level' => $plan->level,
                                                'process_code' => $plan->process_code,
                                                'keep_dry' => $plan->keep_dry,
                                                'tank' => $plan->tank,
                                                'expected_date' => Carbon::parse($plan->expected_date)->format('d/m/y'),
                                                'submit' => $plan->submit,
                                                'storage_capacity' => $storage_capacity,
                                                'subtitle' => $subtitle,
                                                'campaign_code' => $plan->campaign_code,
                                                'status'  => $plan->status
                                        ]);
                                }
                                // 🎯 Lịch đã hoàn thành
                                if (($clearning && $plan->start_clearning  && !$plan->actual_start_clearning  && $plan->yields >= 0  && $plan->finished == 0) || 
                                        ($clearning && $plan->actual_start_clearning  && !$plan->actual_start_clearning && $plan->yields >= 0 && $plan->finished == 0)  ) {
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
                                        ]);
                                }

                                if ($plan->actual_start && $plan->finished == 1) {

                                        if ($theory == 0) {
                                                //Lich thực tế
                                                $events->push([
                                                        'plan_id' => $plan->id,
                                                        'id' => "{$plan->id}-main",
                                                        'title' =>$plan->title ,
                                                        'start' =>  $plan->actual_start,
                                                        'end' =>  $plan->actual_end,
                                                        'resourceId' => $plan->resourceId,
                                                        'color' => '#002af9ff',
                                                        'textColor' => $textColor,
                                                        'plan_master_id' => $plan->plan_master_id,
                                                        'stage_code' => $plan->stage_code,
                                                        'is_clearning' => false,
                                                        'finished' => $plan->finished,
                                                        'level' => $plan->level,
                                                        'process_code' => $plan->process_code,
                                                        'keep_dry' => $plan->keep_dry,
                                                        'tank' => $plan->tank,
                                                        'storage_capacity' => $storage_capacity
                                                        ]);
                                                                                                        // event Lich VS thực tế
                                                if ($clearning  && $plan->yields >= 0) {
                                                        $events->push([
                                                        'plan_id' => $plan->id,
                                                        'id' => "{$plan->id}-cleaning",
                                                        'title' => $plan->title_clearning,
                                                        'start' => $plan->actual_start_clearning,
                                                        'end' =>  $plan->actual_end_clearning,
                                                        'resourceId' => $plan->resourceId,
                                                        'color' => '#002af9ff',
                                                        'textColor' => $textColor,
                                                        'plan_master_id' => $plan->plan_master_id,
                                                        'stage_code' => $plan->stage_code,
                                                        'is_clearning' => true,
                                                        'finished' => $plan->finished,
                                                        'process_code' => $plan->process_code,
                                                        ]);
                                                }
                                                
                                        }else if ($theory == 1){
                                                if ($plan->start) {
                                                        $events->push([
                                                        'plan_id' => $plan->id,
                                                        'id' => "{$plan->id}-main-theory",
                                                        'title' => trim($plan->title . "- Lịch Lý Thuyết"?? '') ,
                                                        'start' =>  $plan->start,
                                                        'end' =>  $plan->end,
                                                        'resourceId' => $plan->resourceId,
                                                        'color' => '#8397faff',
                                                        'textColor' => $textColor,
                                                        'plan_master_id' => $plan->plan_master_id,
                                                        'stage_code' => $plan->stage_code,
                                                        'is_clearning' => false,
                                                        'finished' => $plan->finished,
                                                        'level' => $plan->level,
                                                        'process_code' => $plan->process_code,
                                                        'keep_dry' => $plan->keep_dry,
                                                        'tank' => $plan->tank,
                                                        'storage_capacity' => $storage_capacity
                                                        ]);
                                                }
                                                // event Lich VS lý thuyết
                                                if ($clearning && $plan->yields >= 0 && $plan->start_clearning) {
                                                        $events->push([
                                                        'plan_id' => $plan->id,
                                                        'id' => "{$plan->id}-cleaning-theory",
                                                        'title' => $plan->title_clearning . " - Lịch Lý Thuyết" ?? 'Vệ sinh',
                                                        'start' => $plan->start_clearning,
                                                        'end' =>  $plan->end_clearning,
                                                        'resourceId' => $plan->resourceId,
                                                        'color' => '#8397faff',
                                                        'textColor' => $textColor,
                                                        'plan_master_id' => $plan->plan_master_id,
                                                        'stage_code' => $plan->stage_code,
                                                        'is_clearning' => true,
                                                        'finished' => $plan->finished,
                                                        'process_code' => $plan->process_code,
                                                        ]);
                                                }

                                        }else if ($theory == 2) {
                                              
                                                $events->push([
                                                        'plan_id' => $plan->id,
                                                        'id' => "{$plan->id}-main",
                                                        'title' =>$plan->title ,
                                                        'start' =>  $plan->actual_start,
                                                        'end' =>  $plan->actual_end,
                                                        'resourceId' => $plan->resourceId,
                                                        'color' => '#002af9ff',
                                                        'textColor' => $textColor,
                                                        'plan_master_id' => $plan->plan_master_id,
                                                        'stage_code' => $plan->stage_code,
                                                        'is_clearning' => false,
                                                        'finished' => $plan->finished,
                                                        'level' => $plan->level,
                                                        'process_code' => $plan->process_code,
                                                        'keep_dry' => $plan->keep_dry,
                                                        'tank' => $plan->tank,
                                                        'storage_capacity' => $storage_capacity
                                                ]);

                                                 // event Lich VS thực tế
                                                if ($clearning  && $plan->yields >= 0) {
                                                        $events->push([
                                                        'plan_id' => $plan->id,
                                                        'id' => "{$plan->id}-cleaning",
                                                        'title' => $plan->title_clearning,
                                                        'start' => $plan->actual_start_clearning,
                                                        'end' =>  $plan->actual_end_clearning,
                                                        'resourceId' => $plan->resourceId,
                                                        'color' => '#002af9ff',
                                                        'textColor' => $textColor,
                                                        'plan_master_id' => $plan->plan_master_id,
                                                        'stage_code' => $plan->stage_code,
                                                        'is_clearning' => true,
                                                        'finished' => $plan->finished,
                                                        'process_code' => $plan->process_code,
                                                        ]);
                                                }
                

                                                if ($plan->start) {
                                                        $events->push([
                                                                'plan_id' => $plan->id,
                                                                'id' => "{$plan->id}-main-theory",
                                                                'title' => trim($plan->title . "- Lịch Lý Thuyết"?? '') ,
                                                                'start' =>  $plan->start,
                                                                'end' =>  $plan->end,
                                                                'resourceId' => $plan->resourceId,
                                                                'color' => '#8397faff',
                                                                'textColor' => $textColor,
                                                                'plan_master_id' => $plan->plan_master_id,
                                                                'stage_code' => $plan->stage_code,
                                                                'is_clearning' => false,
                                                                'finished' => $plan->finished,
                                                                'level' => $plan->level,
                                                                'process_code' => $plan->process_code,
                                                                'keep_dry' => $plan->keep_dry,
                                                                'tank' => $plan->tank,
                                                                'storage_capacity' => $storage_capacity
                                                                ]);
                                                }
                                                        // event Lich VS lý thuyết
                                                if ($clearning && $plan->yields >= 0 && $plan->start_clearning) {
                                                                $events->push([
                                                                'plan_id' => $plan->id,
                                                                'id' => "{$plan->id}-cleaning-theory",
                                                                'title' => $plan->title_clearning . " - Lịch Lý Thuyết" ?? 'Vệ sinh',
                                                                'start' => $plan->start_clearning,
                                                                'end' =>  $plan->end_clearning,
                                                                'resourceId' => $plan->resourceId,
                                                                'color' => '#8397faff',
                                                                'textColor' => $textColor,
                                                                'plan_master_id' => $plan->plan_master_id,
                                                                'stage_code' => $plan->stage_code,
                                                                'is_clearning' => true,
                                                                'finished' => $plan->finished,
                                                                'process_code' => $plan->process_code,
                                                                ]);
                                                }
                                        }
                                }

                        }
                
                }

 
                return $events;
        }

        protected function colorEvent($plan, $plans, $i, $room_code){
                
                $subtitle   = '';
                $textColor  = '#fefefee2';
                $color_event = '#eb0cb3ff'; // default fallback

                /* 1️⃣ FINISHED */
                if ($plan->finished == 1) {
                        return ['#002af9ff', $textColor, $subtitle];
                }

                /* 2️⃣ MÀU MẶC ĐỊNH THEO STAGE */
                if ($plan->stage_code <= 7) {
                        $color_event = '#4CAF50';
                } elseif ($plan->stage_code == 8) {
                        $color_event = '#003A4F';
                }

                /* 3️⃣ VALIDATION OK */
                if ($plan->is_val == 1) {
                        $color_event = '#40E0D0';
                }

                /* 4️⃣ CLEARNING */
                if ($plan->clearning_validation == 1) {
                        return ['#e4e405e2', '#fb0101e2', $subtitle];
                }

                /* 5️⃣ BIỆT TRỮ */
                if ($i > 0 && $plan->quarantine_total == 0 && $plan->stage_code > 3 && $plan->stage_code < 7 && $plan->accept_quarantine == 0 ) {
                        $prev = $plans->firstWhere('code', $plan->predecessor_code);
                        if ($prev && $plan->start) {
                              $diffMinutes = Carbon::parse($prev->end)
                                ->diffInMinutes(Carbon::parse($plan->start), false);
                                $limitMinutes = $prev->quarantine_time_limit_hour * 60;

                                if ($limitMinutes > 0 && $diffMinutes > $limitMinutes) {

                                $h = minutesToDayHoursMinutesString($diffMinutes);
                                $lh = minutesToDayHoursMinutesString($limitMinutes);

                                $subtitle =
                                        "➡️ (KT {$this->stage_Name[$prev->stage_code]}: "
                                        . Carbon::parse($prev->end)->format('H:i d/m/y')
                                        . " || TGTB thực tế: $h"
                                        . " || TGTB cho phép: $lh";

                                return ['#bda124ff', $textColor, $subtitle];
                                }
                        }
                }

                /* 6️⃣ HẠN CẦN HÀNG */
                $Stage_plan_7 = $plans->firstWhere('stage_code', 7);

                $overExpected = ($Stage_plan_7 && $plan->expected_date < $Stage_plan_7->end) || $plan->expected_date < $plan->end;

                if ($overExpected && $plan->stage_code < 9) {
                        $color_event = '#e54a4aff';
                        $endStage7 = $Stage_plan_7 && $Stage_plan_7->end ? Carbon::parse($Stage_plan_7->end)->format('d/m/y') : 'Chưa xác định';
                        $subtitle = "➡️ Ngày dự kiến KCS: " . Carbon::parse($plan->expected_date)->format('d/m/y') . " | Ngày KT ĐG: " . $endStage7;
                }

                /* 7️⃣ PREDECESSOR / SUCCESSOR */
                if ($plan->predecessor_code) {
                        $pre = $plans->firstWhere('code', $plan->predecessor_code);
                        if ($pre && $plan->start < $pre->end) {
                                $subtitle = "➡️ (KT {$this->stage_Name[$pre->stage_code]} tại {$room_code[$pre->resourceId]}: "
                                        . Carbon::parse($pre->end)->format('H:i d/m/y') . ")";
                                return ['#4d4b4bff', $textColor, $subtitle];
                        }
                }

                if ($plan->nextcessor_code) {
                        $next = $plans->firstWhere('code', $plan->nextcessor_code);
                        if ($next && $plan->end > $next->start) {
                                $subtitle = "➡️ (BĐ {$this->stage_Name[$next->stage_code]} tại {$room_code[$next->resourceId]}: "
                                        . Carbon::parse($next->start)->format('H:i d/m/y') . ")";
                                return ['#4d4b4bff', $textColor, $subtitle];
                        }
                }

                /* 8️⃣ NGUYÊN LIỆU / BAO BÌ */
                $criticalChecks = [
                        [1, 3, 'after_weigth_date',        '➡️ Ngày có đủ NL' , ">"],
                        [1, 3, 'allow_weight_before_date', '➡️ Ngày được phép cân', ">"],
                        [1, 3, 'expired_material_date',    '➡️ Ngày hết hạn NL chính', "<"],
                        [7, 7, 'expired_packing_date',    '➡️ Ngày hết hạn BB', "<"],
                        [3, 3, 'preperation_before_date','➡️ Phải PC trước ngày', "<"],
                        [4, 4, 'blending_before_date',   '➡️ Phải THT trước ngày', "<"],
                        [6, 6, 'coating_before_date',    '➡️ Phải BP trước ngày', "<"],
                        [7, 7, 'parkaging_before_date',    '➡️ Phải ĐG trước ngày ', "<"],
                        [7, 7, 'after_parkaging_date',   '➡️ Ngày có đủ BB', ">"],
                       
                ];

                foreach ($criticalChecks as [$from, $to, $field, $label, $operator]) {

                        if (
                                $plan->stage_code < $from ||
                                $plan->stage_code > $to ||
                                empty($plan->$field)
                        ) {
                                continue;
                        }

                        $left  = Carbon::parse($plan->$field);
                        $right = Carbon::parse($plan->start);

                        $matched = match ($operator) {
                                '<'  => $left->lt($right),
                                '<=' => $left->lte($right),
                                '>'  => $left->gt($right),
                                '>=' => $left->gte($right),
                                '==' => $left->eq($right),
                                default => false,
                        };

                        if ($matched) {
                                $subtitle = "{$label}: "
                                . $left->format('d/m/y')
                                . " {$operator} "
                                . $right->format('d/m/y');

                                return ['#920000ff', $textColor, $subtitle];
                        }
                }


                return [$color_event, $textColor, $subtitle];
        }
        // Hàm lấy quota
        protected function getQuota($production){
                $result = DB::table('quota')
                ->leftJoin('room', 'quota.room_id', '=', 'room.id')
                ->where('quota.active', 1)
                ->where('quota.deparment_code', $production)
                ->get()
                ->map(function ($item) {
                        $toSeconds = fn($time) => (($h = (int)explode(':',$time)[0]) * 3600) + ((int)explode(':',$time)[1] * 60);
                        $toTime = fn($seconds) => sprintf('%02d:%02d', floor($seconds / 3600), floor(($seconds % 3600) / 60));
                        $item->PM = $toTime($toSeconds($item->p_time) + $toSeconds($item->m_time));
                        return $item;
                });

                
                return $result;
        }

        public function getPlanWaiting($production, $order_by_type = false){
                
                $order_by_column = "sp.order_by";
                if ($order_by_type){ 
                        $order_by_column = "sp.order_by_line"; 
                }

                $plan_waiting = DB::table("stage_plan as sp")
                        ->whereNull('sp.start')
                        ->where('sp.active', 1)
                        ->where('sp.finished', 0)
                        ->where('sp.deparment_code', $production)
                        ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
                        ->leftJoin('plan_list', 'sp.plan_list_id', '=', 'plan_list.id')
                        ->leftJoin('source_material', 'plan_master.material_source_id', '=', 'source_material.id')
                        ->leftJoin('finished_product_category', function ($join) {
                                $join->on('sp.product_caterogy_id', '=', 'finished_product_category.id')
                                        ->where('sp.stage_code', '<=', 7);
                                })
                        ->leftJoin('product_name', function ($join) {
                                $join->on('finished_product_category.product_name_id', '=', 'product_name.id')
                                        ->where('sp.stage_code', '<=', 7);
                                })
                        ->leftJoin('maintenance_category', function ($join) {
                                $join->on('sp.product_caterogy_id', '=', 'maintenance_category.id')
                                        ->where('sp.stage_code', '=', 8);
                                })
                        ->leftJoin('market', 'finished_product_category.market_id', '=', 'market.id')
                        ->select(
                        'sp.id',
                        'sp.code',
                        'sp.plan_master_id',
                        'sp.campaign_code',
                        'sp.stage_code',
                        'sp.order_by',
                        'sp.order_by_line',
                        'sp.clearning_validation',
                        'sp.required_room_code',
                        'sp.predecessor_code',
                        'sp.nextcessor_code',
                        'sp.immediately',
                      
                       
                        'plan_master.id as plan_master_id',       
                        'plan_master.batch',
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
                        'plan_list.month',
                        'market.code as market',
                        'source_material.name as source_material_name',
                        'finished_product_category.intermediate_code',
                        'finished_product_category.finished_product_code',
                        DB::raw("CASE WHEN sp.stage_code <= 7 THEN product_name.name ELSE maintenance_category.name END as name"),
                        DB::raw("CASE WHEN sp.stage_code = 8 THEN maintenance_category.code END as instrument_code"),
                        DB::raw("CASE WHEN sp.stage_code = 8 THEN maintenance_category.is_HVAC END as is_HVAC")
                        )
                        ->orderBy($order_by_column, 'asc')
                        ->get();

                if ($plan_waiting->isEmpty()) {
                        return $plan_waiting;
                }
  
                // 3️⃣ Lấy dữ liệu liên quan chỉ 1 lần
                $maintenance_category = DB::table('maintenance_category')
                        ->where('active', 1)
                        ->where('deparment_code', $production)
                        ->get(['id', 'code', 'room_id']);

                $quota = DB::table('quota')
                ->leftJoin('room', 'quota.room_id', '=', 'room.id')
                ->where('quota.active', 1)
                ->where('quota.deparment_code', $production)
                ->select (
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
                        return  $q->intermediate_code . '_' . $q->finished_product_code . '_' . $q->stage_code;
                });


                $quotaByRoom = $quota->groupBy('room_id');
                $roomIdByInstrument = $maintenance_category->pluck('room_id', 'code');

                // 4️⃣ Map dữ liệu permission_room (cực nhanh)
                $plan_waiting->transform(function ($plan) use ($quotaByIntermediate, $quotaByFinished, $quotaByRoom, $roomIdByInstrument) {
                     if ($plan->stage_code <= 6) {
                                $key = $plan->intermediate_code . '_' . $plan->stage_code;
                                $matched = $quotaByIntermediate[$key] ?? collect();
                        } elseif ($plan->stage_code == 7) {
                                $key = $plan->intermediate_code . '_' .  $plan->finished_product_code . '_' . $plan->stage_code;
                                $matched = $quotaByFinished[$key] ?? collect();
                        } elseif ($plan->stage_code == 8) {
                                $room_id = $roomIdByInstrument[$plan->instrument_code] ?? null;
                                $matched = $room_id ? ($quotaByRoom[$room_id] ?? collect()) : collect();
                        } else {
                                $matched = collect();
                        }

                        // Mảng phòng được phép
                        $plan->permisson_room = collect($matched)->pluck('code', 'room_id')->unique();

                        // ✅ Thêm field để React có thể filter/search nhanh
                        $plan->permisson_room_filter = $plan->permisson_room->values()->implode(', ');

                        return $plan;
                });


                return $plan_waiting;
        }

        // Hàm lấy sản lượng và thời gian sản xuất theo phòng
        protected function getResources($production, $startDate, $endDate){

                $roomStatus = $this->getRoomStatistics($startDate, $endDate);
                $sumBatchQtyResourceId = $this->yield($startDate, $endDate, "resourceId");

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
                ->where('room.stage_code', 3)
                ->where('room.deparment_code', $production)
                //->where('room.id', '>=', 4)
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

                return $result;

        }

        // Hàm view gọn hơn Request
        public function view(Request $request){
                
                //Log::info ($request->all());
                $startDate = $request->startDate ?? Carbon::now();
                $endDate = $request->endDate ?? Carbon::now()->addDays(7);
                $viewtype = $request->viewtype ?? "resourceTimelineWeek";
                $this->theory = (int)$request->theory ?? 0;
                
                try {
                        $production = session('user')['production_code'];
                        $department = DB::table('user_management')->where('userName', session('user')['userName'])->value('deparment');
                       
                        $clearing = $request->clearning??true;

                        if ( $viewtype == "resourceTimelineQuarter") {
                                $clearing = false;
                        }

                        if (user_has_permission(session('user')['userId'], 'loading_plan_waiting', 'boolean')){
                                $plan_waiting = $this->getPlanWaiting($production);
                                $bkc_code = DB::table('stage_plan_bkc')->where('deparment_code', session('user')['production_code'])->select('bkc_code')->distinct()->orderByDesc('bkc_code')->get();
                                $reason = DB::table('reason')->where('deparment_code', $production)->pluck('name');
                                $quota = $this->getQuota($production);
                        }
                       

                        $stageMap = DB::table('room')->where('deparment_code', $production)->pluck('stage_code', 'stage')->toArray();

                        $events = $this->getEvents($production, $startDate, $endDate, $clearing , $this->theory);
                      
                        $sumBatchByStage = $this->yield($startDate, $endDate, "stage_code");

                        $resources = $this->getResources($production, $startDate, $endDate);

                        
                        

                        $title = 'LỊCH SẢN XUẤT';
                        $type = true;

                        // $Lines = DB::table('room')
                        //         ->select('stage_code', 'name', 'code')
                        //         ->where('deparment_code', $production)
                        //         ->whereIn('stage_code', [3, 4, 5, 6, 7])
                        //         ->where('active', 1)
                        //         ->orderBy('order_by')
                        //         ->get()
                        //         ->groupBy('stage_code')
                        //         ->map(function ($items) {
                        //                 return $items->map(function ($room) {
                        //                 return [
                        //                         'name'      => $room->code,
                        //                         'name_code' => $room->code . ' - ' . $room->name,
                        //                 ];
                        //                 })->values();
                        //         });

                         $allLines = DB::table('room')
                                ->select('stage_code', 'name', 'code')
                                ->where('deparment_code', $production)
                                ->whereIn('stage_code', [3, 4, 5, 6, 7])
                                ->where('active', 1)
                                ->orderBy('order_by')
                                ->get();
                               
                               
                        
                        
               
                        $authorization = session('user')['userGroup'];
       

                        return response()->json([
                                'title' => $title,
                                'events' => $events,
                                'plan' => $plan_waiting ?? [], // [phân quyền]
                                'quota' => [], // $quota ?? [],
                                'stageMap' => $stageMap ?? [],
                                'resources' => $resources?? [],
                                'sumBatchByStage' =>  $sumBatchByStage ?? [],
                                'reason' => $reason ?? [],
                                'type' => $type,
                                'authorization' => $authorization,
                                'production' => $production,
                                'department' => $department,
                                'currentPassword' => session('user')['passWord']??'',
                                'Lines'       => [], // $Lines ?? [],
                                'allLines' => $allLines ?? [],
                                'off_days' => DB::table('off_days')->where ('off_date','>=',now())->get()->pluck('off_date') ?? [],
                                'bkc_code' => $bkc_code ?? []
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

        // Hàm tính tổng sản lượng lý thuyết theo stage
        public function getSumaryData(Request $request){
                $sumBatchByStage = $this->yield($request->startDate, $request->endDate, "stage_code");
                return response()->json([
                        'sumBatchByStage' => $sumBatchByStage,
                ]);
        } 
       
        public function getInforSoure (Request $request) {

                $plan_master = DB::table('plan_master')
                        ->select('finished_product_category.intermediate_code', 'product_name.name as product_name', 'plan_master.material_source_id', 'source_material.name')
                        ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
                        ->leftJoin('source_material','plan_master.material_source_id','source_material.id')
                        ->leftJoin('product_name','finished_product_category.product_name_id','product_name.id')
                        ->where('plan_master.id',$request->plan_master_id)
                ->first();

                return response()->json([
                        'sourceInfo' => $plan_master,
                ]);
        }


        public function store(Request $request) {

                $offdate =  $request->offdate;

                DB::beginTransaction();
                try {

                        // Sắp xếp products theo batch
                        $products = collect($request->products)->sortBy('batch')->values();
                       
                        // Thời gian bắt đầu ban đầu
                        $current_start = Carbon::parse($request->start);

                        // 🔥 KIỂM TRA NGAY TỪ ĐẦU NẾU current_start NẰM TRONG OFFDATE
                        $current_start = $this->check_offdate($current_start, $offdate);

                        foreach ($products as $index => $product) {

                                /*
                                |--------------------------------------------------------------------------
                                | LẤY QUOTA
                                |--------------------------------------------------------------------------
                                */
                                if ($index === 0 && $product['stage_code'] !== 9) {

                                        if ($product['stage_code'] < 7) {
                                        $process_code = $product['intermediate_code'] . "_NA_" . $request->room_id;
                                        } else if ($product['stage_code'] === 7) {
                                        $process_code = $product['intermediate_code'] . "_" . $product['finished_product_code'] . "_" . $request->room_id;
                                        }

                                        $quota = DB::table('quota')
                                        ->select(
                                                'room_id', 'campaign_index',
                                                DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes'),
                                        )
                                        ->where('process_code', 'like',  $process_code . '%')
                                        ->first();

                                        $p_time_minutes  = $quota->p_time_minutes ?? 0;
                                        $m_time_minutes  = $quota->m_time_minutes ?? 0;
                                        $C1_time_minutes = $quota->C1_time_minutes ?? 0;
                                        $C2_time_minutes = $quota->C2_time_minutes ?? 0;

                                } elseif ($index === 0 && $product['stage_code'] === 9) {
                                        $p_time_minutes  = 30;
                                        $m_time_minutes  = 60;
                                        $C1_time_minutes = 30;
                                        $C2_time_minutes = 60;
                                }

                                /*
                                |--------------------------------------------------------------------------
                                | TÍNH THỜI GIAN SẢN XUẤT + VỆ SINH
                                |--------------------------------------------------------------------------
                                */
                                if ($product['stage_code'] <= 2) {

                                        $end_man = $current_start->copy()->addMinutes((float)$p_time_minutes + (float)$m_time_minutes * $quota->campaign_index);

                                        $end_clearning = $end_man->copy()->addMinutes((float)$C2_time_minutes);
                                        $clearning_type = "VS-II";

                                } else {

                                        if ($products->count() === 1) {

                                        $end_man = $current_start->copy()->addMinutes(
                                                (float)$p_time_minutes + (float)$m_time_minutes
                                        );

                                        $end_clearning = $end_man->copy()->addMinutes((float)$C2_time_minutes);
                                        $clearning_type = "VS-II";

                                        } else {

                                        if ($index === 0) {
                                                $end_man = $current_start->copy()->addMinutes(
                                                (float)$p_time_minutes + (float)$m_time_minutes
                                                );
                                                $end_clearning = $end_man->copy()->addMinutes((float)$C1_time_minutes);
                                                $clearning_type = "VS-I";
                                        }

                                        elseif ($index === $products->count() - 1) {
                                                $end_man = $current_start->copy()->addMinutes((float)$m_time_minutes);
                                                $end_clearning = $end_man->copy()->addMinutes((float)$C2_time_minutes);
                                                $clearning_type = "VS-II";
                                        }

                                        else {
                                                $end_man = $current_start->copy()->addMinutes((float)$m_time_minutes);
                                                $end_clearning = $end_man->copy()->addMinutes((float)$C1_time_minutes);
                                                $clearning_type = "VS-I";
                                        }
                                        }
                                }

                                /*
                                |--------------------------------------------------------------------------
                                | LƯU stage_plan
                                |--------------------------------------------------------------------------
                                */
                                if ($product['stage_code'] === 9) {
                                        DB::table('stage_plan')
                                        ->where('id', $product['id'])
                                        ->update([
                                        'start'           => $current_start,
                                        'end'             => $end_man,
                                        'start_clearning' => $end_man,
                                        'end_clearning'   => $end_clearning,
                                        'resourceId'      => $request->room_id,
                                        //'title_clearning' => $clearning_type,
                                        'schedualed'      => 1,
                                        'schedualed_by'   => session('user')['fullName'],
                                        'schedualed_at'   => now(),
                                        ]);
                                }else{
                                        DB::table('stage_plan')
                                        ->where('id', $product['id'])
                                        ->update([
                                        'start'           => $current_start,
                                        'end'             => $end_man,
                                        'start_clearning' => $end_man,
                                        'end_clearning'   => $end_clearning,
                                        'resourceId'      => $request->room_id,
                                        'title'           => $product['stage_code'] === 9
                                                ? ($product['title'] . "-" . $product['batch'])
                                                : ($product['name'] . "-" . $product['batch'] . "-" . $product['market']),
                                        'title_clearning' => $clearning_type,
                                        'schedualed'      => 1,
                                        'schedualed_by'   => session('user')['fullName'],
                                        'schedualed_at'   => now(),
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

                                        DB::table('stage_plan_history')->insert([
                                        'stage_plan_id'  => $product['id'],
                                        'version'        => $last_version + 1,
                                        'start'          => $current_start,
                                        'end'            => $end_man,
                                        'resourceId'     => $request->room_id,
                                        'schedualed_by'  => session('user')['fullName'],
                                        'schedualed_at'  => now(),
                                        'deparment_code' => session('user')['production_code'],
                                        'type_of_change' => $request->reason ?? "Lập Lịch Thủ Công",
                                        ]);
                                }

                                /*
                                |--------------------------------------------------------------------------
                                | TÍNH current_start CHO SẢN PHẨM TIẾP THEO
                                |--------------------------------------------------------------------------
                                */
                                if ($product['stage_code'] > 2) {
                                        $current_start = $end_clearning;
                                }

                                // 🔥 SAU KHI TĂNG current_start → KIỂM TRA NGÀY OFF
                                $current_start = $this->check_offdate($current_start, $offdate);
                        }

                        //// Set lại mã chiến dịch
                        if ($product['stage_code'] == 3 ) {
                                $campaign_code = $products->first()['plan_master_id'];

                                DB::table('stage_plan')
                                  ->whereIn('plan_master_id', $products->pluck('plan_master_id'))
                                  ->update([
                                        'campaign_code'  => $campaign_code,
                                ]);
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
                $events = $this->getEvents($production, $request->startDate, $request->endDate , true, $this->theory);
                $plan_waiting = $this->getPlanWaiting($production);
                $sumBatchByStage = $this->yield($request->startDate, $request->endDate, "stage_code");

                return response()->json([
                        'events' => $events,
                        'plan' => $plan_waiting,
                        'sumBatchByStage' => $sumBatchByStage,
                ]);
        }

        public function update(Request $request){
               
                $changes = $request->input('changes', []);

                try {
                foreach ($changes as $change) {
                        // Tách id: "102-main" -> 102
                        $idParts = explode('-', $change['id']);
                        $realId = $idParts[0] ?? null;

                        if (!$realId) {
                                continue; // bỏ qua nếu id không hợp lệ
                        }

                        if ($request->reason['saveReason']){
                                DB::table('reason')
                                ->insert([
                                        'name'                  => $request->reason['reason'],
                                        'deparment_code'        => session('user')['production_code'],
                                        'created_by'            => session('user')['fullName'],
                                        'created_at'            => now(),
                                ]);
                        } 

                        // Nếu là sự kiện vệ sinh (title chứa "VS-")
                        if (strpos($change['title'], "VS-") !== false) {
                                DB::table('stage_plan')
                                ->where('id', $realId)
                                ->update([
                                        'start_clearning' => $change['start'],
                                        'end_clearning'   => $change['end'],
                                        'resourceId'      => $change['resourceId'],
                                        
                                        'schedualed_by'   => session('user')['fullName'],
                                        'schedualed_at'   => now(),
                                ]);
                        } else {
                                DB::table('stage_plan')
                                ->where('id', $realId)
                                ->update([
                                        'start'           => $change['start'],
                                        'end'             => $change['end'],
                                        'resourceId'      => $change['resourceId'],
                                        'schedualed_by'   => session('user')['fullName'],
                                        'schedualed_at'   => now(),
                                        'accept_quarantine'=> 0,
                                ]);
                                
                                $update_row = DB::table('stage_plan')->where('id',$realId)->first();

                                if ($update_row->submit == 1){
                                        $check = DB::table('stage_plan_history')
                                        ->insert([
                                        'stage_plan_id' => $realId,
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
                                        'version' =>  DB::table('stage_plan_history')->where('stage_plan_id',$realId)->max('version') + 1 ?? 1,
                                        'note' => $update_row->note,
                                        'deparment_code' => session('user')['production_code'],
                                        'type_of_change' => $request->reason['reason'],
                                        'created_date' => now(),
                                        'created_by' => session('user')['fullName'],
                                        
                                        ]);
                                }
                        }


                }

                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['error' => 'Lỗi hệ thống'], 500);
                }

                $production = session('user')['production_code'];
                $events = $this->getEvents($production, $request->startDate, $request->endDate , true, $this->theory);
                $plan_waiting = $this->getPlanWaiting($production);
                $sumBatchByStage = $this->yield($request->startDate, $request->endDate, "stage_code");

                return response()->json([
                        'events' => $events,
                        'plan' => $plan_waiting,
                        'sumBatchByStage' => $sumBatchByStage,
                ]);
        }

        public function deActive(Request $request){
                
                $items = collect($request->input('ids'));
                try {

                        foreach ($items as $item) {
                        $rowId = explode('-', $item['id'])[0];   // lấy id trước dấu -
                        $stageCode = $item['stage_code'];
                        
                        if ($stageCode <= 2 || $stageCode >= 8 ) {
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
                                                'accept_quarantine'=> 0,
                                                'schedualed'       => 0,
                                                'AHU_group' => 0,
                                                'schedualed_by'    => session('user')['fullName'],
                                                'schedualed_at'    => now(),
                                        ]);

                        }else {

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
                                                'accept_quarantine'=> 0,
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
                $events = $this->getEvents($production, $request->startDate, $request->endDate , true, $this->theory);
                $plan_waiting = $this->getPlanWaiting($production);
                $sumBatchByStage = $this->yield($request->start, $request->end, "stage_code");

                return response()->json([
                                'events' => $events,
                                'plan' => $plan_waiting,
                                'sumBatchByStage' => $sumBatchByStage,
                ]);


        }

        public function deActiveAll(Request $request){

                // Log::info ($request->all());
                // dd ("sa");

                $production = session('user')['production_code'];
                try {   
                       if ($request->mode == "step"){
                                if ($request->selectedStep == "CNL" ){
                                        $ids = DB::table('stage_plan')
                                        ->where('deparment_code', $production)
                                        ->whereNotNull('start')
                                        ->where ('start', '>=', $request->start_date)
                                        ->where('active', 1)
                                        ->where('finished', 0)
                                        ->where('stage_code', "<=",2)
                                        ->pluck('id');
                                }else {
                                        $Step = ["PC" => 3, "THT" => 4,"ĐH" => 5,"BP" => 6,"ĐG" => 7];
                                        $stage_code = $Step[$request->selectedStep];

                                        $ids = DB::table('stage_plan')
                                        ->where('deparment_code', $production)
                                        ->whereNotNull('start')
                                        ->where ('start', '>=', $request->start_date)
                                        ->where('active', 1)
                                        ->where('finished', 0)
                                        ->where('stage_code', ">=", $stage_code)
                                        ->pluck('id');
                                }

                        }else if ($request->mode == "resource"){
                                $ids = DB::table('stage_plan')
                                ->where('deparment_code', $production)
                                ->whereNotNull('start')
                                ->where ('start', '>=', $request->start_date)
                                ->where('active', 1)
                                ->where('finished', 0)
                                ->where('resourceId', "=", $request->resourceId)
                                ->pluck('id');
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
                                        ->where(function($query) use ($deletedRows) {
                                        foreach ($deletedRows as $row) {
                                                $query->orWhere(function($q) use ($row) {
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


                        if ($ids->isEmpty()) {
                                $production = session('user')['production_code'];
                                $events = $this->getEvents($production, $request->startDate, $request->endDate , true, $this->theory);
                                $plan_waiting = $this->getPlanWaiting($production);
                                $sumBatchByStage = $this->yield($request->startDate, $request->endDate, "stage_code");
                                return response()->json([
                                        'events' => $events,
                                        'plan' => $plan_waiting,
                                        'sumBatchByStage' => $sumBatchByStage,
                                ]);
                        }

                        DB::table('stage_plan')
                                ->whereIn('id',  $ids)
                                ->update([
                                        'start' => null,
                                        'end' => null,
                                        'start_clearning' => null,
                                        'end_clearning' => null,
                                        'resourceId' => null,
                                        'title_clearning' => null,
                                        'accept_quarantine'=> 0,
                                        'schedualed' => 0,
                                        'AHU_group' => 0,
                                        'schedualed_by' =>  session('user')['fullName'],
                                        'schedualed_at' => now(),
                        ]);

                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                }

                $production = session('user')['production_code'];
                $events = $this->getEvents($production, $request->startDate, $request->endDate , true, $this->theory);
                $plan_waiting = $this->getPlanWaiting($production);
                $sumBatchByStage = $this->yield($request->startDate, $request->endDate, "stage_code");
                return response()->json([
                        'events' => $events,
                        'plan' => $plan_waiting,
                        'sumBatchByStage' => $sumBatchByStage,
                ]);

        }


        public function Sorted(Request $request){

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
                                        'responsed_date' => $request->response_date
                                ]);
                        }
                        
                        $sortType = 'responsed_date';

                } else {
                        $sortType = 'expected_date';
                }


                $stageCode =  $request->stage_code??3;
             
                // Danh sách cấu hình sắp xếp
                $stages = [
                        ['codes' => [1, 2, 3], 'orderBy' => [
                        [$sortType, 'asc'],
                        ['level', 'asc'],
                        [DB::raw('batch + 0'), 'asc']
                        ]],
                        ['codes' => [4], 'orderBy' => [
                        ['intermediate_category.quarantine_blending', 'asc'],
                        [$sortType, 'asc'],
                        ['level', 'asc'],
                        [DB::raw('batch + 0'), 'asc']
                        ]],
                        ['codes' => [5], 'orderBy' => [
                        ['intermediate_category.quarantine_forming', 'asc'],
                        [$sortType, 'asc'],
                        ['level', 'asc'],
                        [DB::raw('batch + 0'), 'asc']
                        ]],
                        ['codes' => [6], 'orderBy' => [
                        ['intermediate_category.quarantine_coating', 'asc'],
                        [$sortType, 'asc'],
                        ['level', 'asc'],
                        [DB::raw('batch + 0'), 'asc']
                        ]],
                ];
                  
                // Tìm stage group tương ứng với stage_code được gửi lên
                $stageGroup = collect($stages)->first(fn($group) => in_array($stageCode, $group['codes']));

                if (!$stageGroup) {
                        return response()->json(['error' => 'Stage code không hợp lệ!'], 400);
                }

                // Xây query cho plan_master
                $query = DB::table('plan_master')
                        ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', 'finished_product_category.id')
                        ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', 'intermediate_category.intermediate_code');

                // Thêm thứ tự sắp xếp tương ứng
                foreach ($stageGroup['orderBy'] as [$column, $direction]) {
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
                        ->where('deparment_code', session('user')['production_code'])
                        ->whereIn('plan_master_id', $planMasters)
                        ->orderByRaw("FIELD(plan_master_id, " . implode(',', $planMasters->toArray()) . ")")
                        ->update([
                        'order_by' => DB::raw("FIELD(plan_master_id, " . implode(',', $planMasters->toArray()) . ")")
                        ]);

                return response()->json([
                        'plan' => $this->getPlanWaiting(session('user')['production_code']),
                        'message' => "Đã sắp xếp lại kế hoạch cho stage {$stageCode}."
                ]);
        }


        private function check_offdate(Carbon $current_start, $offdate){
                if (!$offdate || count($offdate) === 0) {
                        return $current_start;
                }

                // 1) chuẩn hóa, loại trùng, và sắp xếp ngày off asc
                $dates = collect($offdate)
                        ->filter()                     // loại null/empty
                        ->map(function ($d) {
                        return Carbon::parse($d)->startOfDay();
                        })
                        ->unique()
                        ->sort()
                        ->values();

                if ($dates->isEmpty()) {
                        return $current_start;
                }

                // 2) tạo mảng khoảng [start, end) cho mỗi ngày off
                $intervals = [];
                foreach ($dates as $date) {
                        $start = $date->copy();                    // 00:00 ngày off
                        $end = $date->copy()->addDay()->setTime(6, 0, 0); // 06:00 ngày tiếp theo
                        $intervals[] = ['start' => $start, 'end' => $end];
                }

                // 3) hợp nhất các khoảng chồng lấn/tiếp xúc để đơn giản hoá (optional nhưng an toàn)
                $merged = [];
                foreach ($intervals as $int) {
                        if (empty($merged)) {
                        $merged[] = $int;
                        continue;
                        }

                        $last = &$merged[count($merged) - 1];

                        // Nếu khoảng mới bắt đầu trước hoặc đúng lúc last end (chồng/tiếp xúc) -> nối
                        if ($int['start']->lte($last['end'])) {
                        // mở rộng end nếu cần
                        if ($int['end']->gt($last['end'])) {
                                $last['end'] = $int['end']->copy();
                        }
                        } else {
                        // không chồng -> thêm mới
                        $merged[] = $int;
                        }
                }

                // 4) lặp cho đến khi current_start không rơi vào bất kỳ khoảng off nào
                $changed = true;
                while ($changed) {
                        $changed = false;
                        foreach ($merged as $int) {
                        // kiểm tra thuộc khoảng [start, end) — dùng < end để tránh boundary ambiguity
                        if ($current_start->gte($int['start']) && $current_start->lt($int['end'])) {
                                // nhảy đến end của khoảng đó
                                $current_start = $int['end']->copy();
                                $changed = true;
                                // cần break để lặp lại kiểm tra từ đầu (vì end có thể vào khoảng sau)
                                break;
                        }
                        }
                }

                return $current_start;
        }




}


        function toMinutes($time) {
                [$hours, $minutes] = explode(':', $time);
                return ((int)$hours) * 60 + (int)$minutes;
        }

        function minutesToDayHoursMinutesString(int $minutes): string{
                $days    = intdiv($minutes, 1440); // 60 * 24
                $remain  = $minutes % 1440;

                $hours   = intdiv($remain, 60);
                $mins    = $remain % 60;

                return ($days > 0 ? "{$days}d " : "")
                        . ($hours > 0 ? "{$hours}h" : "")
                        . "{$mins}p";
        }

        function minutesToHoursMinutes(int $minutes): array{
                                $hours = intdiv($minutes, 60);
                                $mins  = $minutes % 60;
                                return [$hours, $mins];
        }


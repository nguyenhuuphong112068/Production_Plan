<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use PhpOffice\PhpSpreadsheet\Calculation\Logical\Boolean;

class SchedualController extends Controller
{
        protected $roomAvailability = [];
        protected $offDate = [];
        protected $order_by = 1;
        protected $selectedDates = [];
        protected $work_sunday = true;
        protected $reason = null;
        protected $theory = false;
        protected $prev_orderBy = false;
        protected $stage_Name = [
                1 => "Cân NL",
                3 => "PC",
                4 => "THT",
                5 => "ĐH",
                6 => "BP",
                7 => "ĐG",
        ];

        public function test(){
              //$this->scheduleAll (null);
              //$this->createAutoCampain();
              //$this->view (null);
             // $this->Sorted (null);
             //$this->getEvents ('PXV1', '2025-12-15','2025-12-21', false ,false);
        }

        public function index (){

                session()->put(['title'=> 'LỊCH SẢN XUẤT']);
                return view('app');
        }

        //Thời gian của từng phòng
        public function getRoomStatistics($startDate, $endDate){
                // chuẩn hoá ngày giờ (chuỗi dạng MySQL)
                $start = Carbon::parse($startDate)->format('Y-m-d H:i:s');
                $end   = Carbon::parse($endDate)->format('Y-m-d H:i:s');

                $totalSeconds = Carbon::parse($startDate)->diffInSeconds(Carbon::parse($endDate));

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

        protected function getEvents($production, $startDate, $endDate, $clearning, bool $theory = false){
                 
                $startDate = Carbon::parse($startDate)->toDateTimeString();
                $endDate   = Carbon::parse($endDate)->toDateTimeString();

                $room_code = DB::table('room')->where('deparment_code', $production)->pluck('code', 'id');

                // 2️⃣ Lấy danh sách stage_plan (gộp toàn bộ join)
                $event_plans = DB::table("stage_plan as sp")
                        ->leftJoin('plan_master', 'sp.plan_master_id', '=', 'plan_master.id')
                        ->leftJoin('finished_product_category', 'plan_master.product_caterogy_id', '=', 'finished_product_category.id')
                        ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', '=', 'intermediate_category.intermediate_code')
                        ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                        ->where('sp.active', 1)
                        ->whereNotNull('sp.resourceId')
                        ->when(!in_array(session('user')['userGroup'], ['Schedualer', 'Admin', 'Leader']),fn($query) => $query->where('submit', 1))
                        ->where('sp.deparment_code', $production)
                        //->whereRaw('(sp.start <= ? OR sp.end >= ? OR sp.start_clearning <= ? OR sp.end_clearning >= ?)',[$endDate, $startDate, $endDate, $startDate])
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
                        //'sp.title',
                        //DB::raw("CONCAT(product_name.name, '-', plan_master.batch) AS title"),
                        DB::raw("
                        CASE
                                WHEN sp.stage_code = 9 THEN sp.title
                                ELSE CONCAT(product_name.name, '-', plan_master.batch)
                        END AS title
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

                        
                        'finished_product_category.intermediate_code',
                        'plan_master.expected_date',
                        'plan_master.after_weigth_date',
                        'plan_master.after_parkaging_date',

                        'plan_master.expired_material_date',       
                        'plan_master.allow_weight_before_date',
                        
                        'plan_master.preperation_before_date',
                        'plan_master.blending_before_date',
                        'plan_master.coating_before_date',
                        
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

                        // 🎯 Push event chính
                        if ($plan->start || $plan->actual_start) {
                                $events->push([
                                'plan_id' => $plan->id,
                                'id' => "{$plan->id}-main",
                                'title' => trim($plan->title ?? '') ,
                                'start' => $plan->actual_start ?? $plan->start,
                                'end' => $plan->actual_end ?? $plan->end,
                                'resourceId' => $plan->resourceId,
                                'color' =>  $color_event,
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
                                'subtitle' => $subtitle
                                ]);
                        }

                        // 🧽 Push event vệ sinh
                        if (($clearning && $plan->start_clearning  && $plan->yields >= 0) || ($clearning && $plan->actual_start_clearning && $plan->yields >= 0)  ) {
                                $events->push([
                                'plan_id' => $plan->id,
                                'id' => "{$plan->id}-cleaning",
                                'title' => $plan->title_clearning ?? 'VS',
                                'start' => $plan->actual_start_clearning ?? $plan->start_clearning,
                                'end' => $plan->actual_end_clearning ?? $plan->end_clearning,
                                'resourceId' => $plan->resourceId,
                                'color' => $plan->finished == 1?'#002af9ff':'#a1a2a2ff',
                                'textColor' => $textColor,
                                'plan_master_id' => $plan->plan_master_id,
                                'stage_code' => $plan->stage_code,
                                'is_clearning' => true,
                                'finished' => $plan->finished,
                                'process_code' => $plan->process_code,
                                ]);
                        }
                        
                        // event Lich chính lý thuyết
                        if ($plan->actual_start && $theory) {
                               
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
                        if ($clearning && $plan->actual_start && $plan->yields >= 0 && $theory) {
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
                if ($i > 0 && $plan->quarantine_total == 0 && $plan->stage_code > 3 && $plan->stage_code < 7) {
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
                        [3, 3, 'preperation_before_date','➡️ Phải PC trước ngày', "<"],
                        [4, 4, 'blending_before_date',   '➡️ Phải pha THT', "<"],
                        [6, 6, 'coating_before_date',    '➡️ Phải pha BP', "<"],
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
                return DB::table('quota')
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

                // preload quota (tối đa chỉ 1 query)
                $quota = $this->getQuota($production);

                // Tạo map tra cứu nhanh
                $quotaByIntermediate = $quota->groupBy(function ($q) {
                        return $q->intermediate_code . '-' . $q->stage_code;
                });

                $quotaByFinished = $quota->groupBy(function ($q) {
                        return  $q->intermediate_code . '-' . $q->finished_product_code . '-' . $q->stage_code;
                });

                $quotaByRoom = $quota->groupBy('room_id');
                $roomIdByInstrument = $maintenance_category->pluck('room_id', 'code');

                // 4️⃣ Map dữ liệu permission_room (cực nhanh)
                $plan_waiting->transform(function ($plan) use ($quotaByIntermediate, $quotaByFinished, $quotaByRoom, $roomIdByInstrument) {
                        if ($plan->stage_code <= 6) {
                                $key = $plan->intermediate_code . '-' . $plan->stage_code;
                                $matched = $quotaByIntermediate[$key] ?? collect();
                        } elseif ($plan->stage_code == 7) {
                                $key = $plan->intermediate_code . '-' .  $plan->finished_product_code . '-' . $plan->stage_code;
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
                //->where('room.stage_code', '<=', 4)
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
           
                $startDate = $request->startDate ?? Carbon::now();
                $endDate = $request->endDate ?? Carbon::now()->addDays(7);
                $viewtype = $request->viewtype ?? "resourceTimelineWeek";
                $this->theory = $request->theory ?? false;
                
                try {
                        $production = session('user')['production_code'];
                        $department = DB::table('user_management')->where('userName', session('user')['userName'])->value('deparment');
                       
                        $clearing = $request->clearning??true;

                        if ( $viewtype == "resourceTimelineQuarter") {
                                $clearing = false;
                        }

                        if (user_has_permission(session('user')['userId'], 'loading_plan_waiting', 'boolean')){
                                $plan_waiting = $this->getPlanWaiting($production);
                                $bkc_code = DB::table('stage_plan_bkc')->select('bkc_code')->distinct()->orderByDesc('bkc_code')->get();
                                $reason = DB::table('reason')->where('deparment_code', $production)->pluck('name');
                                $quota = $this->getQuota($production);
                        }
                       

                        $stageMap = DB::table('room')->where('deparment_code', $production)->pluck('stage_code', 'stage')->toArray();

                        $events = $this->getEvents($production, $startDate, $endDate, $clearing , $this->theory);
                      
                        $sumBatchByStage = $this->yield($startDate, $endDate, "stage_code");

                        $resources = $this->getResources($production, $startDate, $endDate);

                        
                        

                        $title = 'LỊCH SẢN XUẤT';
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
       

                        return response()->json([
                                'title' => $title,
                                'events' => $events,
                                'plan' => $plan_waiting ?? [], // [phân quyền]
                                'quota' => $quota ?? [],
                                'stageMap' => $stageMap ?? [],
                                'resources' => $resources?? [],
                                'sumBatchByStage' =>  $sumBatchByStage ?? [],
                                'reason' => $reason ?? [],
                                'type' => $type,
                                'authorization' => $authorization,
                                'production' => $production,
                                'department' => $department,
                                'currentPassword' => session('user')['passWord']??'',
                                'Lines'       => $Lines,
                                'allLines' => $allLines,
                                'off_days' => DB::table('off_days')->where ('off_date','>=',now())->get()->pluck('off_date'),
                                'bkc_code' => $bkc_code
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

        public function confirm_source (Request $request) {
                try {
                        DB::table('room_source')->insert ([
                        'intermediate_code' =>  $request->intermediate_code,
                        'room_id' =>  $request->room_id,
                        'source_id' =>  $request->source_id,
                        'prepared_by' => session('user')['fullName'],
                        'created_at' => now()
                        ]);

                        $production = session('user')['production_code'];
                        $events = $this->getEvents($production, $request->startDate, $request->endDate, true, $this->theory);
                        return response()->json([
                                'events' => $events,
                        ]);
                 } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                }

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

        public function store(Request $request) {

                $offdate =  $request->offdate;

                DB::beginTransaction();
                try {

                        // Sắp xếp products theo batch
                        $products = collect($request->products)->sortBy('batch')->values();
                        Log::info($products);
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

                                $end_man = $current_start->copy()->addMinutes(
                                (float)$p_time_minutes + (float)$m_time_minutes * $quota->campaign_index
                                );

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
                        if ($product['stage_code'] !== 1) {
                                $current_start = $end_clearning;
                        }

                        // 🔥 SAU KHI TĂNG current_start → KIỂM TRA NGÀY OFF
                        $current_start = $this->check_offdate($current_start, $offdate);
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

        public function history(Request $request){
                try {
                // Lấy dữ liệu lịch sử theo stage_plan_id
                $history_data = DB::table('stage_plan_history')
                ->leftJoin('stage_plan','stage_plan_history.stage_plan_id','stage_plan.id')
                ->leftJoin('room','stage_plan_history.resourceId','room.id')
                ->where('stage_plan_id', $request->stage_code_id)
                ->select(
                        'stage_plan_history.*',
                        'stage_plan.title',
                        DB::raw("CONCAT(room.name, ' ', room.code) as room_name"))
                ->orderBy('version', 'desc')
                ->get();

                // Nếu không có dữ liệu thì trả về version = 0
                if ($history_data->isEmpty()) {
                        $history_data = collect([
                                [
                                'version' => 0,
                                'start' => null,
                                'end' => null,
                                'start_clearning' => null,
                                'end_clearning' => null,
                                'schedualed_at' => null,
                                ]
                        ]);
                }

              

                // Trả dữ liệu về frontend
                return response()->json([
                        'history_data' => $history_data,
                ]);

                } catch (\Exception $e) {
                Log::error('Lỗi lấy history:', ['error' => $e->getMessage()]);
                return response()->json([
                'message' => 'Không thể lấy dữ liệu history',
                ], 500);
                }
        }

        public function store_maintenance (Request $request){

                DB::beginTransaction();
                try {
                $products = collect($request->products);
                $current_start = Carbon::parse($request->start);
                        if ($request->is_HVAC == true){
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
                                                'start'           => $current_start,
                                                'end'             => $end_man,
                                                'resourceId'      => $room_id[$index],
                                                'title'           => $product['name'] ,
                                                'schedualed'      => 1,
                                                'schedualed_by'   => session('user')['fullName'],
                                                'schedualed_at'   => now(),
                                        ]);

                                        $submit = DB::table('stage_plan')->where('id', $product['id'])->value('submit');

                                        if ($submit === 1){
                                                $last_version = DB::table('stage_plan_history')->where('stage_plan_id', $product['id'])->max('version') ?? 0;
                                                DB::table('stage_plan_history')
                                                        ->insert([
                                                        'stage_plan_id'   => $product['id'],
                                                        'version'         => $last_version + 1,
                                                        'start'           => $current_start,
                                                        'end'             => $end_man,
                                                        'resourceId'      => $request->room_id,
                                                        'schedualed_by'   => session('user')['fullName'],
                                                        'schedualed_at'   => now(),
                                                        'deparment_code'  => session('user')['production_code'],
                                                        'type_of_change'  => $this->reason??"Lập Lịch Thủ Công"
                                                ]);
                                        }

                                }


                        }else{

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
                                                'start'           => $current_start,
                                                'end'             => $end_man,
                                                'resourceId'      => $room_id[0],
                                                'title'           => $product['name'] ,
                                                'schedualed'      => 1,
                                                'schedualed_by'   => session('user')['fullName'],
                                                'schedualed_at'   => now(),
                                        ]);
                                        $update_row = DB::table('stage_plan')->where('id', $product['id'])->first();
                                        if ( $update_row->submit === 1){
                                                $last_version = DB::table('stage_plan_history')->where('stage_plan_id', $product['id'])->max('version') ?? 0;
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
                                                        'version' =>  DB::table('stage_plan_history')->where('stage_plan_id',$product['id'])->max('version') + 1 ?? 1,
                                                        'note' => $update_row->note,
                                                        'deparment_code' => session('user')['production_code'],
                                                        'type_of_change' => $request->reason,
                                                        'created_date' => now(),
                                                        'created_by' => session('user')['fullName'],
                                                        ]);
                                        }
                                        $current_start = $end_man;
                                }
                        }

                        DB::commit();
                } catch (\Exception $e) {
                        DB::rollBack();
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

        public function updateClearning(Request $request){

               
                $changes = $request->input('changes', []);

                try {
                foreach ($changes as $change) {
                        // Tách id: "102-main" -> 102
                        $idParts = explode('-', $change['id']);
                        $realId = $idParts[0] ?? null;

                        if (!$realId) {
                                continue; // bỏ qua nếu id không hợp lệ
                        }

                        // Nếu là sự kiện vệ sinh (title chứa "VS-")
                     
                                DB::table('stage_plan')
                                ->where('id', $realId)
                                ->update([
                                        'start_clearning' => $change['start'],
                                        'end_clearning'   => $change['end'],
                                        'resourceId'      => $change['resourceId'],
                                        'schedualed_by'   => session('user')['fullName'],
                                        'schedualed_at'   => now(),
                                ]);

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
        
                $production = session('user')['production_code'];
                try {   
                       if ($request->mode == "step"){
                                $Step = ["PC" => 3, "THT" => 4,"ĐH" => 5,"BP" => 6,"ĐG" => 7,];
                                $stage_code = $Step[$request->selectedStep];
                                $ids = DB::table('stage_plan')
                                ->where('deparment_code', $production)
                                ->whereNotNull('start')
                                ->where ('start', '>=', $request->start_date)
                                ->where('active', 1)
                                ->where('finished', 0)
                                ->where('stage_code', ">=", $stage_code)
                                ->pluck('id');
                                
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
                                // Lấy danh sách campain_code của các dòng bị xoá
                                $campainCodes = DB::table('stage_plan')
                                ->where('deparment_code', $production)
                                ->whereIn('id', $ids)
                                ->pluck('campaign_code')
                                ->unique();

                                // Lấy thêm các id khác có cùng campain_code, nhưng start < start_date
                                $relatedIds = DB::table('stage_plan')
                                ->where('deparment_code', $production)
                                ->whereIn('campaign_code', $campainCodes)
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

        public function finished(Request $request){
                $ids = $request->id;
                try {
                        if (isset($request->temp)) {
                                foreach ($ids as $id) {
                                        DB::table('stage_plan')
                                                ->where('plan_master_id', $id)
                                                ->where('stage_code','<=', $request->stage_code)
                                                ->update([
                                                'finished' => 1
                                                ]);
                                }
                        }else {
                                DB::table('stage_plan')
                                        ->where('id', $ids)
                                        ->update([
                                        'quarantine_room_code' => $request->room,
                                        'yields' => $request->input('yields'),
                                        'finished' => 1
                                        ]);
                        }


                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
                }

                $production = session('user')['production_code'];


                if (isset($request->temp)) {
                        $plan_waiting = $this->getPlanWaiting($production);
                        return response()->json([
                                'plan_waiting' => $plan_waiting
                        ]);
                }else {
                        $events = $this->getEvents($production, $request->startDate, $request->endDate, true, $this->theory);
                        return response()->json([
                                'events' => $events,
                        ]);
                }


        }

        public function updateOrder(Request $request) {
                
                $data = $request->input('updateOrderData'); // lấy đúng mảng
                $column_order = "order_by";
                if ($request->isShowLine){
                        $column_order = "order_by_line";
                }
                

                $cases = [];
                $codes = [];

                foreach ($data as $item) {
                        $code = $item['code'];       // vì $item bây giờ là array thực sự
                        $orderBy = $item['order_by'];

                        $cases[$code] = $orderBy;    // dùng cho CASE WHEN
                        $codes[] = $code;            // dùng cho WHERE IN
                }

                $updateQuery = "UPDATE stage_plan SET $column_order = CASE code ";

                foreach ($cases as $code => $orderBy) {
                        $updateQuery .= "WHEN '{$code}' THEN {$orderBy} ";
                }
                $updateQuery .= "END WHERE code IN ('" . implode("','", $codes) . "')";
                


                DB::statement($updateQuery);

                return response()->json([
                        'plan' => $this->getPlanWaiting(session('user')['production_code'], $request->isShowLine)
                ]);
        }

        public function createManualCampain(Request $request){

               
                $datas = $request->input ('data');
                $modeCreate = true;
                $firstCode = null;

                try {
                if ($datas && count($datas) > 0) {

                        foreach ($datas as $data){
                                if ($data['campaign_code'] !== null){
                                        $modeCreate = false;
                                        $firstCode =  $data['campaign_code'];
                                 break;
                        }}

                        if ($modeCreate === true && count($datas) > 1){
                                $firstCode = $datas[0]['predecessor_code'];
                                if ($firstCode === null) {$firstCode = "0_".$datas[0]['code'];}
                                $ids = collect($datas)->pluck('id')->toArray();
                                DB::table('stage_plan')
                                        ->whereIn('id', $ids)
                                        ->update([
                                        'campaign_code' => $firstCode
                                        ]);
                        }else {

                                DB::table('stage_plan')
                                        ->where('campaign_code', $firstCode)
                                        ->update([
                                        'campaign_code' => null
                                ]);
                        }


                }}  catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['error' => 'Lỗi hệ thống'], 500);
                }

                return response()->json([
                        'plan' => $this->getPlanWaiting(session('user')['production_code'])
                ]);
        }

        public function immediately(Request $request){

                $datas = $request->input('data', []);
                $modeCreate = true; // mặc định true
                try {
                        // Không có dữ liệu → bỏ qua
                        if (empty($datas)) {
                                return response()->json(['error' => 'No data'], 400);
                        }

                        // 1. Kiểm tra nếu bất kỳ dòng nào đang có immediately = true
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
                                'immediately' => $modeCreate
                        ]);

                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện immediately:', [
                        'error' => $e->getMessage(),
                        'line' => $e->getLine(),
                        ]);
                        return response()->json(['error' => 'Lỗi hệ thống'], 500);
                }

                // Trả lại dữ liệu mới
                return response()->json([
                        'plan' => $this->getPlanWaiting(session('user')['production_code'])
                ]);
        }

        public function clearningValidation(Request $request){

                $datas = $request->input('data', []);
             
                try {
                        // Không có dữ liệu → bỏ qua
                        if (empty($datas)) {
                                return response()->json(['error' => 'No data'], 400);
                        }

                        // 1. Kiểm tra nếu bất kỳ dòng nào đang có immediately = true
                        $cases = [];
                        $ids = [];

                        foreach ($datas as $data) {
                        $ids[] = $data['id'];
                        $cases[] = "WHEN {$data['id']} THEN " . ($data['clearning_validation'] ? 0 : 1);
                        }

                        $ids_list = implode(',', $ids);
                        $cases_sql = implode(' ', $cases);

                        DB::update("UPDATE stage_plan SET clearning_validation = CASE id $cases_sql END WHERE id IN ($ids_list)");

                        // 2. Nếu KHÔNG có dòng nào có immediately → BẬT cho tất cả
                        //$ids = collect($datas)->pluck('id')->filter()->toArray();   
                       

                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện immediately:', [
                        'error' => $e->getMessage(),
                        'line' => $e->getLine(),
                        ]);
                        return response()->json(['error' => 'Lỗi hệ thống'], 500);
                }

                // Trả lại dữ liệu mới
                return response()->json([
                        'plan' => $this->getPlanWaiting(session('user')['production_code'])
                ]);
        }

        public function createManualCampainStage(Request $request){
                $datas = $request->input('data');
                if (count($datas) <= 1){
                        return response()->json([]);
                }

                $firstPlanMasterId = $datas[0]['plan_master_id'] ?? null;

                
                $stage_codes = DB::table('stage_plan')
                        ->where('plan_master_id', $firstPlanMasterId)
                         ->where('stage_code', '>=', $request->input('stage_code'))
                        ->pluck('stage_code');
               

                try {
                        $pre_stage_code = null;
                        foreach ($stage_codes as $stage_code){
                                foreach ($datas as $data){
                                        $campaign_code = DB::table('stage_plan')
                                                ->where('plan_master_id', $data['plan_master_id'])
                                                ->where('stage_code', $stage_code)
                                                ->value('campaign_code');

                                        if ($campaign_code !== null){
                                                DB::table('stage_plan')
                                                ->where('campaign_code', $campaign_code)
                                                ->update([
                                                        'campaign_code' => null
                                                ]);
                                        }
                                }

                                $plan_master_ids = collect($datas)->pluck('plan_master_id')->toArray();

                              
                                DB::table('stage_plan')
                                        ->where('stage_code', $stage_code)
                                        ->whereIn('plan_master_id', $plan_master_ids)
                                        ->update([
                                                'campaign_code' => $pre_stage_code == null?$datas[0]['predecessor_code'] : $firstPlanMasterId ."_". $pre_stage_code
                                ]);
                                
                                $pre_stage_code = $stage_code;
                        }

                }  catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['error' => 'Lỗi hệ thống'], 500);
                }

                return response()->json([
                        'plan' => $this->getPlanWaiting(session('user')['production_code'])
                ]);
        }

        public function createAutoCampain(Request $request){
   
                try {
                // Lấy toàn bộ stage_plan chưa hoàn thành và active
                DB::table('stage_plan')
                        ->where('finished', 0)
                        ->where('start', null)
                        ->where('active', 1)
                        ->where('stage_code',"=", $request->stage_code)
                        
                ->update(['campaign_code' => null]);

                $stage_plans = DB::table("stage_plan as sp")
                        ->select(
                                'sp.id',
                                'sp.stage_code',
                                'sp.predecessor_code',
                                'sp.campaign_code',
                                'sp.code',
                                'plan_master.expected_date',
                                'plan_master.is_val',
                                'plan_master.code_val',
                                'finished_product_category.intermediate_code',
                                'finished_product_category.finished_product_code'
                        )
                        ->leftJoin('plan_master', 'sp.plan_master_id' , '=', 'plan_master.id')
                        ->leftJoin('finished_product_category', 'sp.product_caterogy_id', '=', 'finished_product_category.id')
                        ->where('sp.finished', 0)
                        ->whereNull('sp.start')
                        ->where('sp.active', 1)
                        ->where('sp.stage_code',"=", $request->stage_code)
                        ->orderBy('sp.order_by', 'asc')
                ->get();

                for ($i=3; $i<=7; $i++){
                        $stage_plans_stage = $stage_plans->where('stage_code',$i);
                        if ($stage_plans_stage->isEmpty()) {continue;}
                        if ($i <=6) {$product_code = "intermediate_code";} else {$product_code = "finished_product_code";}
                        $updates = [];

                        // Lọc dữ liệu theo điều kiện code_val
                        if ($i == 3) {

                                $stage_plans_stage = $stage_plans_stage->filter(function($item) {
                                        return $item->code_val === null || explode("_", $item->code_val)[1] > 1;
                                });

                                $groups = $stage_plans_stage
                                ->groupBy(function ($item) use ($product_code) {
                                        // tách code_val
                                        if ($item->code_val === null) {
                                        $cvFlag = 'NULL';
                                        } else {
                                        $parts = explode('_', $item->code_val);
                                        $cvFlag = $parts[0]; // chỉ lấy phần yy (trước dấu "_")
                                        }

                                        return $item->expected_date . '|' . $item->$product_code . '|' . $cvFlag;
                                })
                                ->filter(function ($group) {
                                        return $group->count() > 1; // chỉ giữ group có > 1 phần tử
                                });

                        } else {
                                // i > 3 thì loại bỏ những record có code_val (chỉ giữ code_val == null)
                                $stage_plans_stage = $stage_plans_stage->filter(function($item){
                                        return empty($item->code_val);
                                });
                                // Group theo expected_date + product_code
                                $groups = $stage_plans_stage
                                ->groupBy(function ($item) use ($product_code) {
                                        return $item->expected_date . '|' . $item->$product_code;
                                })
                                ->filter(function ($group) {
                                        return $group->count() > 1;
                                });
                        }

                        foreach ($groups as $groupKey => $items) {
                                [$expected_date, $code] = explode('|', $groupKey);
                                $quota = DB::table('quota')->where($product_code, $code)->where('stage_code',$i)->first();
                                $maxBatch = $quota->maxofbatch_campaign ?? 0;

                        if ($i != 3) {

                                $maxPrevCampaignBatch = 0;

                                foreach ($items as $item) {

                                        if (!$item->predecessor_code) {
                                        continue;
                                        }

                                        // Lấy campaign của predecessor
                                        $prevCampaignCode = DB::table('stage_plan')
                                        ->where('code', $item->predecessor_code)
                                        ->value('campaign_code');

                                        if (!$prevCampaignCode) {
                                        continue;
                                        }

                                        // Đếm số batch của campaign trước
                                        $countPrevCampaign = DB::table('stage_plan')
                                        ->where('campaign_code', $prevCampaignCode)
                                        ->count();

                                        if ($countPrevCampaign > $maxPrevCampaignBatch) {
                                        $maxPrevCampaignBatch = $countPrevCampaign;
                                        }
                                }

                                if ($maxPrevCampaignBatch > 0 && $maxBatch > $maxPrevCampaignBatch) {
                                                $maxBatch = $maxPrevCampaignBatch;
                                }
                        }

                                if ($maxBatch <= 1) {continue;}

                                $items = $items->values(); // reset index
                                $countInBatch = 0;
                                $first = $items[0];
                                $campaignCode = $first->predecessor_code ?? ("0_" . $first->code);

                                foreach ($items as $item) {

                                        if ($countInBatch >= $maxBatch) {
                                                $campaignCode = $item->predecessor_code ?? ("0_" . $item->code);
                                                $countInBatch = 1;
                                        }

                                        $updates[] = [
                                                'id' => $item->id,
                                                'campaign_code' => $campaignCode,
                                        ];

                                        $countInBatch++;
                                }
                        }

                        if (!empty($updates)) {
                                $ids = collect($updates)->pluck('id')->implode(',');

                                $caseSql = "CASE id ";
                                foreach ($updates as $row) {
                                $caseSql .= "WHEN {$row['id']} THEN '{$row['campaign_code']}' ";
                                }
                                $caseSql .= "END";

                                DB::update("UPDATE stage_plan SET campaign_code = $caseSql WHERE id IN ($ids)");
                        }
                }



                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['error' => 'Lỗi hệ thống'], 500);
                }
                return response()->json([
                        'plan' => $this->getPlanWaiting(session('user')['production_code'])
                ]);
        }

        public function DeleteAutoCampain (Request $request){  
              
                DB::table('stage_plan')
                        ->where('finished', 0)
                        ->where('start', null)
                        ->where('active', 1)
                        ->where('stage_code',"=", $request->stage_code)
                        
                ->update(['campaign_code' => null]);    
                return response()->json([
                        'plan' => $this->getPlanWaiting(session('user')['production_code'])
                ]); 
        }

        public function createOrderPlan (Request $request) {

                try {
                        DB::transaction(function () use ($request) {
                        $planMasterId = DB::table('plan_master')->insertGetId([
                                'plan_list_id'        => 0,
                                'product_caterogy_id' => 0,
                                'level'               => 4,
                                'batch'               => $request->batch,
                                'expected_date'       => '2025-01-01',
                                'is_val'              => false,
                                'only_parkaging'      => false,
                                'percent_parkaging'   => 1,
                                'note'                => $request->note ?? "NA",
                                'deparment_code'      => session('user')['production_code'],
                                'created_at'          => now(),
                                'prepared_by'         => session('user')['fullName'],
                        ]);
                        $number_of_batch = $request->number_of_batch??1;
                        for ($i = 1; $i  <= $number_of_batch; $i++) {
                                // Insert stage_plan và gán plan_master_id
                                DB::table('stage_plan')->insert([
                                        'plan_list_id'        => 0,
                                        'product_caterogy_id' => 0,
                                        'plan_master_id'      => $planMasterId,
                                        'schedualed'          => 0,
                                        'finished'            => 0,
                                        'active'              => 1,
                                        'stage_code'          => 9,
                                        'deparment_code'      => session('user')['production_code'],
                                        'title'               => $request->title,
                                        'yields'              => $request->checkedClearning ? 0 : -1,
                                        'created_by'          => session('user')['fullName'],
                                        'created_date'        => now(),
                                ]);
                        }


                });

                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['error' => 'Lỗi hệ thống'], 500);
                }

                return response()->json([
                        'plan' => $this->getPlanWaiting(session('user')['production_code'])
                ]);

        }

        public function DeActiveOrderPlan (Request $request) {

                try {
                        $ids = collect($request->all())->pluck('id'); // lấy ra danh sách id

                        DB::table('stage_plan')
                        ->whereIn('id', $ids)
                        ->update([
                                'active'        => 0,
                                'finished_by'   => session('user')['fullName'] ?? 'System',
                                'finished_date' => now(),
                        ]);
                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['error' => 'Lỗi hệ thống'], 500);
                }

                return response()->json([
                        'plan' => $this->getPlanWaiting(session('user')['production_code'])
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

        public function submit(Request $request){
                // 1️⃣ Lấy danh sách các dòng sẽ update
                $updatedRows = DB::table('stage_plan')
                        ->whereNotNull('start')
                        ->where('finished', 0)
                        ->where('active', 1)
                        ->where('submit', 0)
                        ->where('deparment_code', session('user')['production_code'])
                        ->get();

                if ($updatedRows->isEmpty()) {
                        return response()->json(['message' => 'Không có lịch mới để submit!']);
                }

                // 2️⃣ Update submit = 1
                DB::table('stage_plan')
                        ->whereIn('id', $updatedRows->pluck('id'))
                        ->update(['submit' => 1]);

                // 3️⃣ Insert log cho từng dòng
                $historyData = $updatedRows->map(function ($row) {
                        $maxVersion = DB::table('stage_plan_history')
                        ->where('stage_plan_id', $row->id)
                        ->max('version') ?? 0;

                        return [
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
                        'deparment_code' => session('user')['production_code'],
                        'type_of_change' => "Tạo Mới Lịch",
                        'created_date' => now(),
                        'created_by' => session('user')['fullName'],
                        ];
                });

                // 🔹 Chia nhỏ insert để tránh lỗi 1390
                $historyData->chunk(500)->each(function ($chunk) {
                        DB::table('stage_plan_history')->insert($chunk->toArray());
                });

                return response()->json(['message' => "Đã submit " . $updatedRows->count() . " lịch."]);
        }

        public function required_room (Request $request) {

        
                $campaign_code = DB::table('stage_plan')->where('id', $request->stage_plan_id)->value('campaign_code');
                $room_id = DB::table('room')->where ('code', $request->room_code)->value('id');

                if ($campaign_code && !$request->checked ){
                        DB::table('stage_plan')
                        ->where('id', $request->stage_plan_id)
                        ->update(['required_room_code' => null]);

                }else if ($campaign_code && $request->checked){

                        $plans = DB::table('stage_plan')
                        ->leftJoin('finished_product_category','finished_product_category.id','stage_plan.product_caterogy_id')
                        ->select('stage_plan.id', 
                                'stage_plan.stage_code',
                                'finished_product_category.intermediate_code', 
                                'finished_product_category.finished_product_code'
                                )
                        ->where('stage_plan.campaign_code', $campaign_code)
                        ->get();


                        foreach ($plans as $p) {

                        // Tạo process_code đúng tiêu chí
                                if ($p->stage_code < 7) {
                                        $process_code = $p->intermediate_code . "_NA_" . $room_id;
                                } else {
                                        $process_code = $p->intermediate_code . "_" . $p->finished_product_code . "_" . $room_id;
                                }

                                $quota = DB::table('quota')
                                        ->where('process_code', 'like', $process_code . '%')
                                        ->first();

                                if (!$quota) {
                                        return response()->json([
                                        'status' => 'error',
                                        'message' => "Lô ID {$p->id} không có định mức cho phòng {$room_id}. Không thể yêu cầu phòng!"
                                        ], 422);
                                }
                        }



                        DB::table('stage_plan')
                        ->where('campaign_code', $campaign_code)
                        ->update(['required_room_code' => $request->room_code]);
                }else{
                        DB::table('stage_plan')
                        ->where('id', $request->stage_plan_id)
                        ->update(['required_room_code' => $request->checked?$request->room_code:null]);
                }

                return response()->json([
                        'plan' => $this->getPlanWaiting(session('user')['production_code'])
                ]);
        }

        function backup_schedualer(){
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
                        'quarantined_date'
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
                                'quarantined_date'
                        ])
                        ->where('finished', 0)
                );
                return response()->json([
                        'bkcCode' => $bkcCode
                ]);
               
        }

        public function restore_schedualer(Request $request){
                $bkcCode = $request->input('bkc_code'); // ⚠️ dùng đúng key axios gửi

                if (!$bkcCode) {
                        Log::warning('Restore scheduler failed: missing bkc_code', [
                        'payload' => $request->all()
                        ]);

                        return response()->json([
                        'success' => false,
                        'message' => 'Thiếu mã bản sao lưu'
                        ], 422);
                }

                try {
                        DB::beginTransaction();

                        $affected = DB::table('stage_plan as sp')
                        ->join('stage_plan_bkc as bkc', 'bkc.stage_plan_id', '=', 'sp.id')
                        ->where('sp.finished', 0)
                        ->where('bkc.bkc_code', $bkcCode)
                        ->update([
                                'sp.start'                  => DB::raw('bkc.start'),
                                'sp.end'                    => DB::raw('bkc.end'),
                                'sp.resourceId'             => DB::raw('bkc.resourceId'),
                                'sp.start_clearning'        => DB::raw('bkc.start_clearning'),
                                'sp.end_clearning'          => DB::raw('bkc.end_clearning'),
                                'sp.schedualed'             => DB::raw('bkc.schedualed'),
                                'sp.actual_start'           => DB::raw('bkc.actual_start'),
                                'sp.actual_end'             => DB::raw('bkc.actual_end'),
                                'sp.actual_start_clearning' => DB::raw('bkc.actual_start_clearning'),
                                'sp.actual_end_clearning'   => DB::raw('bkc.actual_end_clearning'),
                                'sp.note'                   => DB::raw('bkc.note'),
                                'sp.yields'                 => DB::raw('bkc.yields'),
                                'sp.yields_batch_qty'       => DB::raw('bkc.yields_batch_qty'),
                                'sp.number_of_boxes'        => DB::raw('bkc.number_of_boxes'),
                        ]);

                        DB::commit();

                        return response()->json([
                        'success' => true,
                        'affected' => $affected
                        ]);

                } catch (\Throwable $e) {

                        DB::rollBack();

                        Log::error('Restore scheduler error', [
                        'bkc_code' => $bkcCode,
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                        ]);

                        return response()->json([
                        'success' => false,
                        'message' => 'Khôi phục thất bại, vui lòng kiểm tra log'
                        ], 500);
                }
        }

        protected function skipOffTime(Carbon $time, array $offDateList): Carbon {
                foreach ($offDateList as $off) {

                        // đảm bảo kiểu Carbon
                        $start = $off['start'] instanceof Carbon
                        ? $off['start']
                        : Carbon::parse($off['start']);

                        $end = $off['end'] instanceof Carbon
                        ? $off['end']
                        : Carbon::parse($off['end']);

                        // nếu time nằm trong khoảng off
                        if ($time->gte($start) && $time->lt($end)) {
                        return $end->copy(); // nhảy tới cuối off
                        }

                        // vì offDateList đã sort theo start
                        if ($time->lt($start)) {
                        break;
                        }
                }

                return $time;
        }

        protected function loadRoomAvailability(string $sort, int $roomId){
                // ===============================
                // 0. Reset dữ liệu
                // ===============================
                $this->roomAvailability[$roomId] = [];
       

                // ===============================
                // 1. Lấy lịch bận thực tế
                // ===============================
                // $stagePlanNotCampain = DB::table('stage_plan')
                //         ->where('resourceId', $roomId)
                //         ->whereNull('campaign_code')
                //         ->where(function ($q) {
                //                 $q->where('end', '>=', now())
                //                 ->orWhere('end_clearning', '>=', now());
                //         })
                //         ->select(
                //                 'id',
                //                 'resourceId',
                //                 'start',
                //                 DB::raw('COALESCE(end_clearning, end) as end')
                //         )
                //         ->distinct()
                //         ->orderBy('start')
                // ->get();

                $notCampaign = DB::table('stage_plan')
                ->where('resourceId', $roomId)
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
                        ->where('resourceId', $roomId)
                        ->whereNotNull('campaign_code')
                        ->where(function ($q) {
                                $q->where('end', '>=', now())
                                ->orWhere('end_clearning', '>=', now());
                        })
                        ->select(
                                //'id',
                                //'resourceId',
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
                                'end'   => Carbon::parse($row->end),
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
                        $last      = $merged[$lastIndex];

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
                // 3. Sắp xếp theo $sort
                // ===============================
                if (!empty($this->roomAvailability[$roomId])) {
                        $this->roomAvailability[$roomId] = collect($this->roomAvailability[$roomId])
                        ->sortBy('start', SORT_REGULAR, $sort === 'desc')
                        ->values()
                        ->toArray();
                }

        }

        protected function loadOffDate(string $sort){
    
                $this->offDate = [];

                if (!empty($this->selectedDates) && is_array($this->selectedDates)) {

                        // 2.1 Parse + sort ngày (chỉ lấy date)
                        $dates = collect($this->selectedDates)
                        ->map(fn ($d) => Carbon::parse($d)->startOfDay())
                        ->sort()
                        ->values();

                        $ranges = [];

                        $currentStart = null;
                        $currentEnd   = null;
                        $prevDate     = null;

                        // 2.2 Duyệt từng ngày
                        foreach ($dates as $date) {

                        // Quy ước off: 06:00 hôm nay -> 06:00 hôm sau
                        $start = $date->copy()->setTime(6, 0, 0);
                        $end   = $date->copy()->addDay()->setTime(6, 0, 0);

                        // Khoảng đầu tiên
                        if ($currentStart === null) {
                                $currentStart = $start;
                                $currentEnd   = $end;
                                $prevDate     = $date;
                                continue;
                        }

                        // ✅ Điều kiện gộp CHUẨN: ngày hiện tại = ngày trước + 1
                        if ($date->equalTo($prevDate->copy()->addDay())) {
                                // Kéo dài end
                                $currentEnd = $end;
                        } else {
                                // Lưu khoảng cũ
                                $ranges[] = [
                                'start' => $currentStart,
                                'end'   => $currentEnd,
                                ];

                                // Bắt đầu khoảng mới
                                $currentStart = $start;
                                $currentEnd   = $end;
                        }

                        $prevDate = $date;
                        }

                        // 2.3 Push khoảng cuối cùng
                        if ($currentStart !== null) {
                        $ranges[] = [
                                'start' => $currentStart,
                                'end'   => $currentEnd,
                        ];
                        }

                        $this->offDate = $ranges;
                }

                if (!empty($this->offDate)) {
                        $this->offDate = collect($this->offDate)
                        ->sortBy('start', SORT_REGULAR, $sort === 'desc')
                        ->values()
                        ->toArray();
                }
        }

        protected function findEarliestSlot2( $roomId, $Earliest, $intervalTime, $C2_time_minutes, $requireTank = 0, $requireAHU = 0, $stage_plan_table = 'stage_plan', $maxTank = 1, $tankInterval = 60) {
                $this->loadRoomAvailability('asc', $roomId);

                if (!isset($this->roomAvailability[$roomId])) {
                        $this->roomAvailability[$roomId] = [];
                }

                $busyList    = $this->roomAvailability[$roomId];
                $offDateList = $this->offDate?? [];
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
                               // $current_start = $this->skipOffTime($current_start, $offDateList);
                        }
                }
                
                // ==== sau tất cả busy ====
                return $current_start->copy();
        }

        /** Ghi kết quả vào stage_plan + log vào room_status*/
        protected function saveSchedule($title, $stageId, $roomId,  $start,  $end, $start_clearning,  $endCleaning, string $cleaningType, bool $direction) {

                DB::transaction(function() use ($title, $stageId, $roomId, $start, $end, $start_clearning,  $endCleaning, $cleaningType, $direction) {
                                             
                        if ($cleaningType == 2){$titleCleaning = "VS-II";} else {$titleCleaning = "VS-I";}
                        $AHU_group  = DB::table ('room')->where ('id',$roomId)->value('AHU_group')?? 0;

                        DB::table('stage_plan')
                                ->where('id', $stageId)
                                ->update([
                                'title'           => $title,
                                'resourceId'      => $roomId,
                                'start'           => $start,
                                'end'             => $end,
                                'start_clearning' => $start_clearning,
                                'end_clearning'   => $endCleaning,
                                'title_clearning' => $titleCleaning,
                                'scheduling_direction' => $direction,
                                'AHU_group' => $AHU_group??null,
                                'schedualed_at'      => now(),
                        ]);

                        $submit = DB::table('stage_plan')->where('id', $stageId)->value('submit');

                        // nếu muốn log cả cleaning vào room_schedule thì thêm block này:
                        if ($submit == 1){
                                DB::table('stage_plan_history')
                                        ->insert([
                                        'stage_plan_id'   => $stageId,
                                        'version'         => (DB::table('stage_plan_history')->where('stage_plan_id',$stageId)->max('version')?? 0) + 1,
                                        'start'           => $start,
                                        'end'             => $end,
                                        'resourceId'      => $roomId,
                                        'schedualed_by'   => session('user')['fullName'],
                                        'schedualed_at'   => now(),
                                        'deparment_code'  => session('user')['production_code'],
                                        'type_of_change'  => $this->reason??"Lập Lịch Tự Động",
                                ]);
                        }

                });
        }

        /** Scheduler cho tất cả stage Request */
        public function scheduleAll( Request $request) {
              
                $this->selectedDates = $request->selectedDates??[];
                $this->work_sunday = $request->work_sunday??false;
                $this->reason = $request->reason??"NA";
                $this->prev_orderBy =  $request->prev_orderBy??false;
                $this->loadOffDate('asc');

           
                $Step = [
                        "PC" => 3,
                        "THT" => 4,
                        "ĐH" => 5,
                        "BP" => 6,
                        "ĐG" => 7,
                ];

                $selectedStep = $Step[$request->selectedStep??"ĐG"];

                $today = Carbon::now()->toDateString();
                $start_date = Carbon::createFromFormat('Y-m-d', $request->start_date?? $today)->setTime(6, 0, 0);
               
                $stageCodes = DB::table("stage_plan as sp")
                        ->distinct()
                        ->where('sp.stage_code',">=",3)
                        ->where('sp.stage_code',"<=",$selectedStep)
                        ->where('sp.deparment_code', session('user')['production_code'])
                        ->orderBy('sp.stage_code')
                ->pluck('sp.stage_code');

                $waite_time = [];
                $waite_time[3] = ['waite_time_nomal_batch' => 0,'waite_time_val_batch'   => 0,];
                $waite_time[4] = ['waite_time_nomal_batch' => (($request->wt_bleding ?? 0) * 24 * 60),'waite_time_val_batch'   => (($request->wt_bleding_val ?? 1) * 24 * 60)];
                $waite_time[5] = ['waite_time_nomal_batch' => (($request->wt_forming ?? 0) * 24 * 60) ,'waite_time_val_batch'   => (($request->wt_forming_val ?? 1) * 24 * 60)];
                $waite_time[6] = ['waite_time_nomal_batch' => (($request->wt_coating ?? 0) * 24 * 60)  ,'waite_time_val_batch'   => (($request->wt_coating_val ?? 1) * 24 * 60)];
                $waite_time[7] = ['waite_time_nomal_batch' => (($request->wt_blitering ?? 0) * 24 * 60) ,'waite_time_val_batch'   => (($request->wt_blitering_val ?? 5) * 24 * 60)];

                $this->scheduleStartBackward($start_date, $waite_time);

                if ($request->runType == 'line'){
                        $stage_code_line = DB::table("room")->where('code', $request->lines)->value('stage_code');
                        $this->scheduleLine ($request->lines, $request->stage_plan_ids, $stage_code_line, 0, 0, $start_date);
                        return response()->json([]);
                }

               
                foreach ($stageCodes as $stageCode) {
                        $waite_time_nomal_batch = 0;
                        $waite_time_val_batch   = 0;
                        switch ($stageCode) {
                                case 3:
                                        $waite_time_nomal_batch = 0;
                                        $waite_time_val_batch   = 0;
                                       
                                        break;
                                case 4:
                                        $waite_time_nomal_batch = ($request->wt_bleding ?? 0)  * 24 * 60 ;
                                        $waite_time_val_batch   = ($request->wt_bleding_val ?? 1) * 24 * 60;
                                       
                                        break;

                                case 5:
                                        $waite_time_nomal_batch = ($request->wt_forming?? 0) * 24 * 60;
                                        $waite_time_val_batch   = ($request->wt_forming_val ?? 5) * 24 * 60;

                                        break;

                                case 6:
                                        $waite_time_nomal_batch = ($request->wt_coating?? 0) * 24 * 60;
                                        $waite_time_val_batch   = ($request->wt_coating_val ?? 5) * 24 * 60;

                                        break;

                                case 7: // Đóng gói
                                        $waite_time_nomal_batch = ($request->wt_blitering ?? 0) * 24 * 60;
                                        $waite_time_val_batch   = ($request->wt_blitering_val ?? 5) * 24 * 60;

                                        break;
                        }
                        $this->scheduleStage($stageCode, $waite_time_nomal_batch , $waite_time_val_batch, $start_date);
                }
                
                return response()->json([]);
        }

        /** Scheduler cho 1 stage*/
        public function scheduleStage(int $stageCode, int $waite_time_nomal_batch = 0, int $waite_time_val_batch = 0,  ?Carbon $start_date = null) {

                
                if ($this->prev_orderBy && $stageCode >= 4) {
                        $tasks = DB::table("stage_plan as sp")
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

                                        'prev.start as prev_start'   // lấy start của công đoạn trước
                                )
                                ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
                                ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
                                ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                                ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                                ->leftJoin('stage_plan as prev', 'prev.code', '=', 'sp.predecessor_code')
                                ->where('sp.stage_code', $stageCode)
                                ->where('sp.finished',0)
                                ->where('sp.active',1)
                                ->whereNull('sp.start')
                                ->whereNotNull('plan_master.after_weigth_date')
                                //->when($stageCode == 7, function ($q) {
                                //        $q->whereNotNull('plan_master.after_parkaging_date');
                                //})
                                ->where('sp.deparment_code', session('user')['production_code'])
                                ->orderBy('prev.start', 'asc')

                        ->get();
                  
                }       
                else{
                         $tasks = DB::table("stage_plan as sp")
                                ->select('sp.id',
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
                                'market.code as market'
                                )
                                ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
                                ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
                                ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                                ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                                ->where('sp.stage_code', $stageCode)
                                ->where('sp.finished',0)
                                ->where('sp.active',1)
                                ->whereNull('sp.start')
                                ->whereNotNull('plan_master.after_weigth_date')
                                ->when($stageCode == 7, function ($q) {
                                        $q->whereNotNull('plan_master.after_parkaging_date');
                                })
                                ->where('sp.deparment_code', session('user')['production_code'])
                                ->orderBy('order_by','asc')
                        ->get();

                         
                       
                }


               

                $processedCampaigns = []; // campaign đã xử lý

                foreach ($tasks as $task) {
                        if ($task->is_val === 1) { $waite_time = $waite_time_val_batch; }else {$waite_time = $waite_time_nomal_batch;}

                        if ($task->campaign_code === null) {

                                $this->sheduleNotCampaing ($task, $stageCode, $waite_time, $start_date, null);
                        }else {
                                if (in_array($task->campaign_code, $processedCampaigns)) {continue;}
                                // Gom nhóm campaign
                                $campaignTasks = $tasks->where('campaign_code', $task->campaign_code)->sortBy('batch');;
                                $this->scheduleCampaign( $campaignTasks, $stageCode, $waite_time,  $start_date, null);
                                // Đánh dấu campaign đã xử lý
                                $processedCampaigns[] = $task->campaign_code;
                        }
                        $this->order_by++;
                }
        }

        public function scheduleLine(string $required_room, array $stage_plan_ids, int $stageCode, int $waite_time_nomal_batch = 0, int $waite_time_val_batch = 0,  ?Carbon $start_date = null) {


                if ($this->prev_orderBy && $stageCode >= 4){
                        
                        $tasks = DB::table("stage_plan as sp")
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

                                        'prev.start as prev_start'   // lấy start của công đoạn trước
                                )
                                ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
                                ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
                                ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                                ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                                ->leftJoin('stage_plan as prev', 'prev.code', '=', 'sp.predecessor_code')
                                ->whereNotNull('prev.start')
                                ->whereIn('sp.id', $stage_plan_ids)
                                ->whereNotNull('plan_master.after_weigth_date')
                                ->when($stageCode == 7, function ($q) {
                                        $q->whereNotNull('plan_master.after_parkaging_date');
                                })
                                ->where('sp.deparment_code', session('user')['production_code'])
                                ->orderBy('prev.start', 'asc')
                        ->get();

                }else{

                        $tasks = DB::table("stage_plan as sp")
                                ->select('sp.id',
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
                                $query->leftJoin('stage_plan as prev', 'prev.code', '=', 'sp.predecessor_code')
                                        ->whereNotNull('prev.start');
                                })
                                ->where('sp.deparment_code', session('user')['production_code'])
                                ->orderBy('order_by_line','asc')
                        ->get();
                }

                $processedCampaigns = []; // campaign đã xử lý

                foreach ($tasks as $task) {
                        if ($task->is_val === 1) { $waite_time = $waite_time_val_batch; }else {$waite_time = $waite_time_nomal_batch;}

                        if ($task->campaign_code === null) {

                                $this->sheduleNotCampaing ($task, $stageCode, $waite_time, $start_date, $required_room);
                        }else {
                                if (in_array($task->campaign_code, $processedCampaigns)) {continue;}
                                // Gom nhóm campaign
                                $campaignTasks = $tasks->where('campaign_code', $task->campaign_code)->sortBy('batch');;
                                $this->scheduleCampaign( $campaignTasks, $stageCode, $waite_time, $start_date, $required_room);
                                // Đánh dấu campaign đã xử lý
                                $processedCampaigns[] = $task->campaign_code;
                        }
                        $this->order_by++;
                }
        }
        
        /** Scheduler lô thường*/
        protected function sheduleNotCampaing ($task, $stageCode,  int $waite_time = 0,  ?Carbon $start_date = null, ?string $Line = null){

                        $title = $task->name ."- ". $task->batch; //."- ". $task->market;
                        $now = Carbon::now();
                        $minute = $now->minute;
                        $roundedMinute = ceil($minute / 15) * 15;
                        if ($roundedMinute == 60) {
                                $now->addHour();
                                $roundedMinute = 0;
                        }
                        $now->minute($roundedMinute)->second(0)->microsecond(0);

                        // Gom tất cả candidate time vào 1 mảng
                        $candidates [] = $now;
                        $candidates[] = $start_date;

                        // Nếu có after_weigth_date
                        if ($stageCode <=6){
                                if ($task->after_weigth_date) {$candidates[] = Carbon::parse($task->after_weigth_date);}
                        }else {
                                if ($task->after_parkaging_date) {$candidates[] = Carbon::parse($task->after_parkaging_date);}
                        }

                        if ($task->predecessor_code != null){
                                $pred = DB::table('stage_plan')
                                ->where('code', $task->predecessor_code)->first();
                                if ($pred){
                                         $candidates[] = Carbon::parse($pred->end)->addMinutes($waite_time);
                                }
                        }


                        // Lấy max
                        $earliestStart = collect($candidates)->max();

                        // Chọn Phòng SX
                if ($task->required_room_code != null || $Line != null ){
                        if ($task->required_room_code != null){
                                $room_code = $task->required_room_code;
                        }else{
                                $room_code = $Line;
                        }

                        $room_id =  DB::table('room')->where('code', $room_code)->value('id');
                        
                        $rooms = DB::table('quota')->select('room_id',
                                DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes'))
                                ->when($task->stage_code <= 6, function ($query) use ($task) {
                                                return $query->where('intermediate_code', $task->intermediate_code);
                                }, function ($query) use ($task) {
                                        return $query->where('finished_product_code', $task->finished_product_code)
                                                        ->where('intermediate_code', $task->intermediate_code);
                                })
                                ->where('room_id', $room_id)
                                ->get();
                        }else{
                                if ($task->code_val !== null && $task->stage_code == 3 && isset($parts[1]) && $parts[1] > 1) {
                                $code_val_first = $parts[0] . '_1';

                                $room_id_first = DB::table("stage_plan as sp")
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

                                        $rooms = DB::table('quota')->select('room_id',
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
                                elseif ($task->code_val !== null && $task->stage_code > 3 && isset($parts[1]) && $parts[1] > 1) {
                                        $code_val_first = $parts[0];

                                        $room_id_first = DB::table("stage_plan as sp")
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


                                                if ($rooms->count () > $room_id_first->count ()) {
                                                        foreach ($room_id_first as $first) {
                                                                $rooms->where('room_id', '!=', $first->resourceId);
                                                        }
                                                }

                                        } else {
                                                $rooms = DB::table('quota')->select('room_id',
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

                                }else {
                                        $rooms = DB::table('quota')->select('room_id',
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
                        

                        $bestRoom = null;
                        $bestStart = null;

                     

                        //Tim phòng tối ưu
                        foreach ($rooms as $room) {
                                $intervalTimeMinutes = (float) $room->p_time_minutes + (float) $room->m_time_minutes;
                                $C2_time_minutes =  (float) $room->C2_time_minutes;


                                $candidateStart = $this->findEarliestSlot2(
                                        $room->room_id,
                                        $earliestStart,
                                        $intervalTimeMinutes,
                                        $C2_time_minutes,
                                        $task->tank,
                                        $task->keep_dry,
                                        "stage_plan",
                                        2,
                                        60
                                );

                                if ($bestStart === null || $candidateStart->lt($bestStart)) {
                                        $bestRoom = $room->room_id;
                                        $bestStart = $candidateStart;
                                        $bestEnd = $bestStart->copy()->addMinutes($intervalTimeMinutes);
                                        $start_clearning =  $bestEnd->copy();
                                        $end_clearning =  $bestStart->copy()->addMinutes($intervalTimeMinutes +  $C2_time_minutes);
                                }

                        }
                        

                        if ($this->work_sunday == false) {
                                //Giả sử $bestStart là Carbon instance

                                $startOfSunday = (clone $bestStart)->startOfWeek()->addDays(6)->setTime(6, 0, 0); // CN 6h sáng
                                $endOfPeriod   = (clone $startOfSunday)->addDay()->setTime(6, 0, 0);   // T2 tuần kế tiếp 6h sáng
                               
                                if ($bestStart->between($startOfSunday, $endOfPeriod)) {
                                        $bestStart = $endOfPeriod->copy();
                                        $bestEnd = $bestStart->copy()->addMinutes($intervalTimeMinutes);
                                        $start_clearning =  $bestEnd->copy();
                                        $end_clearning =  $bestStart->copy()->addMinutes($intervalTimeMinutes +  $C2_time_minutes);


                                }else if ($bestEnd->between($startOfSunday, $endOfPeriod)) {
                                        $bestEnd = $bestEnd->copy()->addMinutes(1440);
                                        $start_clearning =  $bestEnd->copy();
                                        $end_clearning =  $bestStart->copy()->addMinutes($intervalTimeMinutes +  $C2_time_minutes);
                                }

                                if (isset($start_clearning) &&  $start_clearning->between($startOfSunday, $endOfPeriod)) {
                                        $start_clearning =  $endOfPeriod->copy();
                                        $end_clearning =  $start_clearning->copy()->addMinutes($C2_time_minutes);

                                }else if ($end_clearning->between($startOfSunday, $endOfPeriod)) {
                                                $end_clearning =  $end_clearning->copy()->addMinutes(1440);
                                }

                        }

                        $this->saveSchedule(
                                        $title,
                                        $task->id,
                                        $bestRoom,
                                        $bestStart,
                                        $bestEnd,
                                        $start_clearning,
                                        $end_clearning,
                                        2,
                                        1,
                                      
                        );
        }

        /** Scheduler lô chiến dịch*/
        protected function scheduleCampaign( $campaignTasks, $stageCode, int $waite_time = 0, ?Carbon $start_date = null , ?string $Line = null){


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
                $candidates [] = $now;
                $candidates[] = $start_date;

                // Nếu có after_weigth_date
                if ($stageCode <=6){
                        if ($firstTask->after_weigth_date) {$candidates[] = Carbon::parse($firstTask->after_weigth_date);}
                }else {
                        if ($firstTask->after_parkaging_date) {$candidates[] = Carbon::parse($firstTask->after_parkaging_date);}
                }

                //$pre_campaign_first_batch_end = [];
                $pre_campaign_codes = [];

                foreach ($campaignTasks as $campaignTask) {

                        $pred = DB::table('stage_plan')->where('code', $campaignTask->predecessor_code)->first();

                        if ($pred ) {

                                $code = $pred->campaign_code;

                                if (!in_array($code, $pre_campaign_codes) && $code != null) {
                                        $pre_campaign_codes [] = $code ;

                                        $pre_campaign_batch = DB::table('stage_plan')
                                        ->where('campaign_code', $code)
                                        ->orderBy('start', 'asc')
                                        ->get();

                                        $pre_campaign_first_batch =  $pre_campaign_batch->first();
                                        $pre_campaign_last_batch =  $pre_campaign_batch->last();

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

                                        if ($currCycle && $currCycle >= $prevCycle){
                                                $candidates[] = Carbon::parse($pred->end)->addMinutes($waite_time);
                                        }else  {
                                  
                                                if ($campaignTask->immediately == false && $stageCode != 4){
                                                        $candidates[] = Carbon::parse($pre_campaign_last_batch->end)->subMinutes(($campaignTasks->count() - 1) * $currCycle);
                                                        $candidates[] = Carbon::parse($pred->end)->addMinutes($waite_time + $maxCount * ($prevCycle - $currCycle));
                                                }    
                                        }
                                }

                                if ($code == null){
                                        $candidates [] =  Carbon::parse($pred->end);
                                }
                        }
                }
                // Lấy max
                $earliestStart = collect($candidates)->max();

                // phòng phù hợp (quota)
                if ($firstTask->required_room_code != null || $Line != null ){
                        if ($firstTask->required_room_code != null){
                                $room_code = $firstTask->required_room_code;
                        }else{
                                $room_code = $Line;
                        }

                        $room_id =  DB::table('room')->where('code', $room_code)->value('id');
                        
                        $rooms = DB::table('quota')->select('room_id',
                                DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes'))
                                ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {
                                                return $query->where('intermediate_code', $firstTask->intermediate_code);
                                }, function ($query) use ($firstTask) {
                                        return $query->where('finished_product_code', $firstTask->finished_product_code)
                                                        ->where('intermediate_code', $firstTask->intermediate_code);
                                })
                                ->where('room_id', $room_id)
                                ->get();
                }else{
                        if ($firstTask->code_val !== null && $firstTask->stage_code == 3 && isset($parts[1]) && $parts[1] > 1) {
                  
                                $code_val_first = $parts[0] . '_1';

                                $room_id_first = DB::table("stage_plan as sp")
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

                                        $rooms = DB::table('quota')->select('room_id',
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
                        }
                        elseif ($firstTask->code_val !== null && $firstTask->stage_code > 3 && isset($parts[1]) && $parts[1] > 1) {
                      
                                        $code_val_first = $parts[0];

                                        $room_id_first = DB::table("stage_plan as sp")
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


                                                if ($rooms->count () > $room_id_first->count ()) {
                                                        foreach ($room_id_first as $first) {
                                                                $rooms->where('room_id', '!=', $first->resourceId);
                                                        }
                                                }

                                        } else {
                                                $rooms = DB::table('quota')->select('room_id',
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

                        }else {
                                $rooms = DB::table('quota')->select('room_id',
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

                // Liên hê giữa PC và THT 
                if ( $stageCode == 4 &&  $firstTask->predecessor_code &&  explode('_', $firstTask->predecessor_code)[1] == 3 && $rooms->count() > 1) {
                        $rooms_bkc = $rooms;

                        $resourceId_prev = DB::table('stage_plan')
                                ->where('code', $firstTask->predecessor_code)
                                ->value('resourceId');

                        $rooms = $rooms->filter(function ($room) use ($resourceId_prev) {

                                if (in_array($resourceId_prev, [6, 7])) {
                                        return in_array($room->room_id, [13, 14]);
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
                //Tim phòng tối ưu
                foreach ($rooms as $room) {

                        $totalMunites = $room->p_time_minutes + ($campaignTasks->count() * $room->m_time_minutes)
                                + ($campaignTasks->count()-1) * ($room->C1_time_minutes)
                                + $room->C2_time_minutes;

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

                //Log::info (['candidateStart' => $candidateStart]);
               

                // Lưu từng batch
                $counter = 1;

                foreach ($campaignTasks as $index => $task) {
                      
                        $nextTask = null;
                        if ($this->work_sunday == false) {
                                $startOfSunday = (clone $bestStart)->startOfWeek()->addDays(6)->setTime(6, 0, 0);
                                $endOfPeriod   = (clone $startOfSunday)->addDay()->setTime(6, 0, 0);
                        }

                        $bestStart = $this->skipOffTime($bestStart, $this->offDate);
                        
                        $pred_end = DB::table('stage_plan')->where('code', $task->predecessor_code)->value('end');

                        if (isset($pred_end) && $pred_end != null && $pred_end > $bestStart) {$bestStart = Carbon::parse($pred_end);}

                        if ($counter == 1) {
                                $bestEnd = $bestStart->copy()->addMinutes((float) $bestRoom->p_time_minutes + $bestRoom->m_time_minutes);
                                if ($bestEnd->between($startOfSunday, $endOfPeriod)) {
                                        $bestEnd = $bestEnd->addMinutes(1440);;
                                }
                                $start_clearning = $bestEnd->copy();
                                $bestEndCleaning = $bestEnd->copy()->addMinutes((float)$bestRoom->C1_time_minutes); //Lô đâu tiên chiến dịch
                                $clearningType = 1;
                                // $nextTask = $campaignTasks[$index + 1] ?? null;
                                
                                // if ($nextTask && $nextTask->predecessor_code) {
                                       
                                //         $next_pred_end = DB::table('stage_plan')
                                //                 ->where('code', $nextTask->predecessor_code)
                                //                 ->value('end');
                                        
                                //         if ($next_pred_end && Carbon::parse($next_pred_end)->gt($bestEndCleaning)) {
                                //                 $bestEndCleaning = Carbon::parse($next_pred_end);

                                //         }
                                // }

                                

                        }elseif ($counter == $campaignTasks->count()){
                           
                                $bestEnd = $bestStart->copy()->addMinutes((float) $bestRoom->m_time_minutes);
                                $start_clearning = $bestEnd->copy();
                                $bestEndCleaning = $bestEnd->copy()->addMinutes((float)$bestRoom->C2_time_minutes); //Lô cuối chiến dịch
                                if ($bestEnd->between($startOfSunday, $endOfPeriod)) {
                                        $bestEnd = $bestEnd->addMinutes(1440);
                                        $start_clearning =  $bestEnd->copy();
                                        $bestEndCleaning =  $start_clearning->copy()->addMinutes((float)$bestRoom->C2_time_minutes);
                                }else if ($bestEndCleaning->between($startOfSunday, $endOfPeriod)) {
                                        $bestEndCleaning = $bestEndCleaning->addMinutes(1440);;
                                }

                                $clearningType = 2;
                        }else {
                                $bestEnd = $bestStart->copy()->addMinutes((float) $bestRoom->m_time_minutes);
                                if ($bestEnd->between($startOfSunday, $endOfPeriod)) {
                                        $bestEnd = $bestEnd->addMinutes(1440);;
                                }
                                $start_clearning = $bestEnd->copy();

                                $bestEndCleaning = $start_clearning->copy()->addMinutes((float)$bestRoom->C1_time_minutes);

                                 //Lô giữa chiến dịch
                                $clearningType = 1;
                        }

                        $this->saveSchedule(
                                $task->name."-".$task->batch, //."-".$task->market,
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
        }

        ///////// Sắp Lịch Theo Plan_Master_ID ////////
        public function scheduleStartBackward( $start_date, $waite_time) {

                $planMasters = DB::table('plan_master as pm')
                        ->leftJoin('finished_product_category', 'pm.product_caterogy_id', 'finished_product_category.id')
                        ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', 'intermediate_category.intermediate_code')
                        ->where ('quarantine_total','>',0)
                        ->whereIn('pm.id', function ($query) {
                                $query->select(DB::raw('DISTINCT sp.plan_master_id'))
                                ->from("stage_plan as sp")
                                ->whereNull('sp.start')
                                ->where('sp.active', 1)
                                ->where('sp.finished', 0)
                                ->where('sp.deparment_code', session('user')['production_code']);
                        })
                        ->orderBy('pm.expected_date', 'asc')
                        ->orderBy('pm.level', 'asc')
                        ->orderByRaw('batch + 0 ASC')
                ->pluck('pm.id');
  
              
                foreach ($planMasters as $planId) {

                        $check_plan_master_id_complete =  DB::table("stage_plan as sp")
                        ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
                        ->where ('plan_master_id', $planId)
                        ->whereNull ('sp.start')
                        ->where ('sp.active', 1)
                        ->where ('sp.finished', 0)
                        ->where ('sp.stage_code',">", 2)
                        ->where('sp.deparment_code', session('user')['production_code'])
                        ->exists();

                        if ($check_plan_master_id_complete){

                                //$this->schedulePlanBackwardPlanMasterId($planId, $work_sunday, $bufferDate, $waite_time , $start_date);
                                
                                $this->schedulePlanForwardPlanMasterId ($planId, $waite_time, $start_date);

                        }
                        $this->order_by++;
                }

        } // khởi động và lấy mãng plan_master_id

        protected function schedulePlanForwardPlanMasterId($planId,  $waite_time,  ?Carbon $start_date = null) {

           
                $now = Carbon::now();
                $minute = $now->minute;
                $roundedMinute = ceil($minute / 15) * 15;

                // toàn bộ các row trong stage_plan cùng plan_master_id của các công đoạn từ ĐG - PC
                $tasks = DB::table("stage_plan as sp")
                        ->select (
                                'sp.id',
                                'sp.plan_master_id',
                                'sp.product_caterogy_id',
                                'sp.predecessor_code',
                                'sp.campaign_code',
                                'sp.code',
                                'sp.stage_code',
                                'sp.tank',
                                'sp.keep_dry',
                                'fc.finished_product_code',
                                'fc.intermediate_code',
                                'pm.is_val',
                                'pm.code_val',
                                'pm.expected_date',
                                'pm.batch',
                                'pm.after_weigth_date',
                                'pm.before_weigth_date',
                                'pm.after_parkaging_date',
                                'pm.before_parkaging_date',
                                'mk.code as market',
                                'pn.name',
                        )
                        ->leftJoin('finished_product_category as fc', 'sp.product_caterogy_id', '=', 'fc.id')
                        ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                        ->leftJoin('product_name as pn', 'fc.product_name_id', '=', 'pn.id')
                        ->leftJoin('market as mk', 'fc.market_id', '=', 'mk.id')
                        ->whereNull('start')
                        ->where('plan_master_id', $planId)
                        ->where('sp.finished', 0)
                        ->where('stage_code',">=",3)
                        ->where('stage_code',"<=",7)
                        ->whereNotNull('pm.after_weigth_date')
                        ->where(function ($q) {
                                $q->where('sp.stage_code', '!=', 7)
                                ->orWhere(function ($q2) {
                                $q2->where('sp.stage_code', 7)
                                        ->whereNotNull('pm.after_parkaging_date');
                                });
                        })
                ->orderBy('stage_code', 'asc') // chạy thuận
                ->get(); // 1 lô gồm tất cả các stage
                
                

                foreach ($tasks as  $task) { // Vòng lập chính duyệt qua toàn bộ các task cùng plan_master_id
                        $waite_time_for_task = null;

                        if (!$task->is_val) {
                                $waite_time_for_task = $waite_time[$task->stage_code]['waite_time_nomal_batch'];
                        } else {
                                $waite_time_for_task = $waite_time[$task->stage_code]['waite_time_val_batch'];
                        }


                        $campaign_tasks = null;
                        $candidatesEarliest = [];
                        if ($task->campaign_code){ // trường hợp chiến dịch
                                $campaign_tasks = DB::table("stage_plan as sp")
                                  ->select (
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
                                        'fc.finished_product_code',
                                        'fc.intermediate_code',
                                        'pm.is_val',
                                        'pm.code_val',
                                        'pm.expected_date',
                                        'pm.level',
                                        'pm.batch',
                                        'pm.after_weigth_date',
                                        'pm.before_weigth_date',
                                        'pm.after_parkaging_date',
                                        'pm.before_parkaging_date',
                                        'mk.code as market',
                                        'pn.name')
                                ->leftJoin('finished_product_category as fc', 'sp.product_caterogy_id', '=', 'fc.id')
                                ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
                                ->leftJoin('product_name as pn', 'fc.product_name_id', '=', 'pn.id')
                                ->leftJoin('market as mk', 'fc.market_id', '=', 'mk.id')
                                ->whereNull('start')
                                ->where('finished', 0)
                                ->where('campaign_code',$task->campaign_code)
                                ->orderBy('expected_date', 'asc')
                                ->orderBy('level', 'asc')
                                ->orderBy('batch', 'asc')
                                ->get();
                        }
                        
                        /// Tìm Phòng Sản Xuất Thịch Hợp
                        if ($task->code_val !== null && $task->stage_code == 3 && isset($parts[1]) && $parts[1] > 1) {
                                $code_val_first = $parts[0] . '_1';

                                $room_id_first = DB::table("stage_plan as sp")
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
                                        ->where('room_id', $room_id_first->resourceId)
                                        ->get();

                                } else {

                                        $rooms = DB::table('quota')->select('room_id',
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
                                        ->where('stage_code', $task->stage_code)
                                        ->get();

                                }
                        }
                        elseif ($task->code_val !== null && $task->stage_code > 3 && isset($parts[1]) && $parts[1] > 1) {
                                $code_val_first = $parts[0];

                                $room_id_first = DB::table("stage_plan as sp")
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
                                        ->where('stage_code', $task->stage_code)
                                        ->get();


                                        if ($rooms->count () > $room_id_first->count ()) {
                                                foreach ($room_id_first as $first) {
                                                        $rooms->where('room_id', '!=', $first->resourceId);
                                                }
                                        }

                                } else {
                                        $rooms = DB::table('quota')->select('room_id',
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
                                        ->where('stage_code', $task->stage_code)
                                        ->get();
                                }

                        }else {
                                $rooms = DB::table('quota')->select('room_id',
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
                                        ->where('stage_code', $task->stage_code)
                                        ->get();
                        }

                        

                       
                        $bestRoom = null;
                        $bestRoomId = null;
                        $bestStart = null;
                        $bestEnd = null;
                        $bestEndCleaning = null;

                        if ($roundedMinute == 60) {
                                $now->addHour();
                                $roundedMinute = 0;
                        }
                        $now->minute($roundedMinute)->second(0)->microsecond(0);

                        $candidatesEarliest [] = Carbon::parse($now);
                        $candidatesEarliest[] = $start_date;
                         
                        $startDateWeek = Carbon::parse($task->expected_date)->subDays(5+7);
                        $candidatesEarliest[] = $startDateWeek->startOfWeek(Carbon::MONDAY)->setTime(6, 0, 0);

                        if ($task->stage_code == 7){
                                $candidatesEarliest[] = Carbon::parse($task->after_parkaging_date);
                        }elseif ($task->stage_code == 3) {
                                $candidatesEarliest[] = Carbon::parse($task->after_weigth_date);
                        }


                        // Gom tất cả candidate time vào 1 mảng
                        $pre_stage_code = explode('_', $task->predecessor_code)[1];

                        if ($campaign_tasks){
                                $pre_campaign_codes = [];
                               
                                foreach ($campaign_tasks as $campaignTask) {


                                        $code = null;
                                        $pred = DB::table("stage_plan")->where('code', $campaignTask->predecessor_code)->first();

                                        if ($pred) {
                                                $code = $pred->campaign_code;
                                                if (!in_array($code, $pre_campaign_codes) && $code != null) {
                                                        $pre_campaign_codes [] = $code ;

                                                        $pre_campaign_batch = DB::table("stage_plan")
                                                        ->where('campaign_code', $code)
                                                        ->orderBy('start', 'asc')
                                                        ->get();

                                                        $pre_campaign_first_batch =  $pre_campaign_batch->first();
                                                        $pre_campaign_last_batch =  $pre_campaign_batch->last();

                                                        $prevCycle = DB::table('quota')
                                                                ->selectRaw('AVG(TIME_TO_SEC(m_time)/60) as avg_m_time_minutes')
                                                                ->when($task->stage_code <= 6, function ($query) use ($task) {
                                                                        return $query->where('intermediate_code', $task->intermediate_code);
                                                                }, function ($query) use ($task) {
                                                                        return $query->where('finished_product_code', $task->finished_product_code);
                                                                })
                                                                ->where('active', 1)
                                                                ->where('stage_code', $pre_campaign_first_batch->stage_code)
                                                                ->value('avg_m_time_minutes');

                                                        $currCycle = DB::table('quota')
                                                                ->selectRaw('AVG(TIME_TO_SEC(m_time)/60) as avg_m_time_minutes')
                                                                ->when($task->stage_code <= 6, function ($query) use ($task) {
                                                                        return $query->where('intermediate_code', $task->intermediate_code);
                                                                }, function ($query) use ($task) {
                                                                        return $query->where('finished_product_code', $task->finished_product_code);
                                                                })
                                                                ->where('active', 1)
                                                                ->where('stage_code', $campaignTask->stage_code)
                                                        ->value('avg_m_time_minutes');
                                                        
                                                        if ($currCycle && $currCycle >= $prevCycle){
                                                                $candidatesEarliest[] = Carbon::parse($pred->end);
                                                                
                                                        }else {
                                                                $candidatesEarliest[] = Carbon::parse($pre_campaign_last_batch->end)->subMinutes(($campaign_tasks->count() - 1) * $currCycle);
                                                        }
                                                }

                                                if ($code == null){
                                                        $candidatesEarliest [] =  Carbon::parse($pred->end);
                                                }
                                        }
                                }
                        }else {
                                $pre_stage_code = explode('_', $task->predecessor_code)[1];
                                $prev_stage_end = DB::table ("stage_plan")->where('code', $task->predecessor_code)->value('end');

                                if ($pre_stage_code >= 3 && $waite_time_for_task){
                                        $candidatesEarliest[] = Carbon::parse($prev_stage_end)->copy()->addMinutes($waite_time_for_task);
                                }else {
                                        $candidatesEarliest[] = Carbon::parse($prev_stage_end);
                                }
                        }



                        $earliestStart = collect($candidatesEarliest)->max();

                        
                        foreach ($rooms as $room) { // duyệt qua toàn bộ các room đã định mức để tìm bestroom
                                $intervalTimeMinutes = (float) $room->p_time_minutes + (float) $room->m_time_minutes;
                                $C2_time_minutes =  (float) $room->C2_time_minutes;

                                if ($campaign_tasks !== null){ // chỉ thực hiện khi có chiến dịch
                                        $intervalTimeMinutes = (float) $room->p_time_minutes + (float) $room->m_time_minutes * $campaign_tasks->count() + (float) $room->C1_time_minutes * ($campaign_tasks->count()-1);
                                        $C2_time_minutes =  (float) $room->C2_time_minutes;
                                        $currCycle =  (float) $room->m_time_minutes;
                                }

                                $candidateStart = $this->findEarliestSlot2(
                                        $room->room_id,
                                        $earliestStart,
                                        $intervalTimeMinutes,
                                        $C2_time_minutes,
                                        $task->tank,
                                        $task->keep_dry,
                                        "stage_plan",
                                        2,
                                        60
                                );
                               
                                if ($bestStart === null || $candidateStart->lt(Carbon::parse($bestStart))) {
                                        $bestRoom = $room;
                                        $bestRoomId = $room->room_id;
                                        $bestStart = $candidateStart;
                                        $bestEnd = $bestStart->copy()->addMinutes((float) $room->p_time_minutes + (float) $room->m_time_minutes);
                                        $bestEndCleaning  = $bestEnd->copy()->addMinutes( (float) $room->C2_time_minutes);
                                }
                        }

          
                        if ($campaign_tasks !== null){
                                $counter = 1;
                   
                                foreach ($campaign_tasks as  $task) {

                                        if ($this->work_sunday == false) {
                                                 $startOfSunday = (clone $bestStart)->startOfWeek()->addDays(6)->setTime(6, 0, 0);
                                                 $endOfPeriod   = (clone $startOfSunday)->addDay()->setTime(6, 0, 0);
                                                if ($bestStart->between($startOfSunday, $endOfPeriod)) {
                                                        $bestStart = $endOfPeriod->copy();
                                                }
                                        }
                                        
                                        $pred_end = DB::table("stage_plan")->where('code', $task->predecessor_code)->value('end');

                                        if (isset($pred_end) && $pred_end != null && $pred_end > $bestStart) {$bestStart = Carbon::parse($pred_end);}

                                        if ($counter == 1) {
                                                $bestEnd = $bestStart->copy()->addMinutes((float) $bestRoom->p_time_minutes + $bestRoom->m_time_minutes);
                                                if ($bestEnd->between($startOfSunday, $endOfPeriod)) {
                                                        $bestEnd = $bestEnd->addMinutes(1440);;
                                                }
                                                $start_clearning = $bestEnd->copy();
                                                $bestEndCleaning = $bestEnd->copy()->addMinutes((float)$bestRoom->C1_time_minutes); //Lô đâu tiên chiến dịch
                                                $clearningType = 1;
                                        }elseif ($counter == $campaign_tasks->count()){
                                        
                                                $bestEnd = $bestStart->copy()->addMinutes((float) $bestRoom->m_time_minutes);
                                                $start_clearning = $bestEnd->copy();
                                                $bestEndCleaning = $bestEnd->copy()->addMinutes((float)$bestRoom->C2_time_minutes); //Lô cuối chiến dịch
                                                if ($start_clearning->between($startOfSunday, $endOfPeriod)) {
                                                        $start_clearning =  $endOfPeriod->copy();
                                                        $bestEndCleaning =  $start_clearning->copy()->addMinutes((float)$bestRoom->C2_time_minutes);
                                                }else if ($bestEndCleaning->between($startOfSunday, $endOfPeriod)) {
                                                        $bestEndCleaning = $bestEndCleaning->addMinutes(1440);;
                                                }

                                                $clearningType = 2;
                                        }else {
                                                $bestEnd = $bestStart->copy()->addMinutes((float) $bestRoom->m_time_minutes);

                                                if ($bestEnd->between($startOfSunday, $endOfPeriod)) {
                                                        $bestEnd = $bestEnd->addMinutes(1440);;
                                                }
                                                $start_clearning = $bestEnd->copy();
                                                $bestEndCleaning = $start_clearning->copy()->addMinutes((float)$bestRoom->C1_time_minutes); //Lô giữa chiến dịch
                                                $clearningType = 1;
                                        }

                                        $this->saveSchedule(
                                                $task->name."-".$task->batch, //."-".$task->market,
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


                        }else {
                                if ($this->work_sunday == false) { 
                                        //Giả sử $bestStart là Carbon instance
                                       
                                        $startOfSunday = (clone $bestStart)->startOfWeek()->addDays(6)->setTime(0, 0, 0); // CN 6h sáng
                                        $endOfPeriod   = (clone $startOfSunday)->addDay()->setTime(6, 0, 0); // T2 tuần kế tiếp 6h sáng

                                        if ($bestStart->between($startOfSunday, $endOfPeriod)) {
                                                $bestStart = $endOfPeriod->copy();
                                                $bestEnd = $bestStart->copy()->addMinutes($intervalTimeMinutes);
                                                $start_clearning =  $bestEnd->copy();
                                        }
                                        if (isset($start_clearning) &&  $start_clearning->between($startOfSunday, $endOfPeriod)) {
                                                $start_clearning =  $endOfPeriod->copy();
                                        }
                                }
                                
                                $this->saveSchedule(
                                        $task->name ."-". $task->batch ,
                                        $task->id,
                                        $bestRoomId,
                                        $bestStart,
                                        $bestEnd,
                                        $bestEnd,
                                        $bestEndCleaning,
                                        2,
                                        1
                                );
                        }
                }
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






        // protected function findEarliestSlot2($roomId, $Earliest, $intervalTime, $C2_time_minutes, $requireTank = 0, $requireAHU = 0, $stage_plan_table = 'stage_plan',  $maxTank = 1, $tankInterval = 60){

        //         $this->loadRoomAvailability('asc', $roomId);

        //         if (!isset($this->roomAvailability[$roomId])) {$this->roomAvailability[$roomId] = [];}

        //         $busyList = $this->roomAvailability[$roomId]; //[$roomId]; // danh sách block bận
        //         $offDateList = $this->offDate[$roomId];

        //         $current_start = Carbon::parse($Earliest);

        //         $AHU_group  = DB::table ('room')->where ('id',$roomId)->value('AHU_group');


        //         // $tryCount = 0;
        //         // while (true) {
        //         foreach ($busyList as $busy) {

        //                 // Log::info('Busy time', [
        //                 // 'start' => $busy['start'],
        //                 // 'end'   => $busy['end'],
        //                 // ]);

        //                 // $startOfSunday = (clone $current_start)->startOfWeek()->addDays(6)->setTime(6, 0, 0); // CN 6h sáng
        //                 // $endOfPeriod   = (clone $startOfSunday)->addDay()->setTime(6, 0, 0);   // T2 tuần kế tiếp 6h sáng

        //                 // if ($current_start->between($startOfSunday, $endOfPeriod) && $this->work_sunday == false) {
        //                 //         $current_start = $endOfPeriod;
        //                 // }


                              
        //                 if ($current_start->lt($busy['start'])) {
                                        
        //                         $gap = abs($current_start->diffInMinutes($busy['start']));

        //                         // $sundayCount = 0;
        //                         // $work_sunday_time = 0;
        //                         // if ($this->work_sunday == false) {
        //                         //         $current_end = $current_start->copy()->addMinutes($intervalTime + $C2_time_minutes);
        //                         //         foreach (CarbonPeriod::create($current_start, $current_end) as $date) {
        //                         //                 if ($date->dayOfWeek === 0) {
        //                         //                         $sundayCount++;
        //                         //                 }
        //                         //         }
        //                         // }

        //                         // if ($sundayCount > 0){
        //                         //         $work_sunday_time = 1440 * $sundayCount;
        //                         // }

        //                         foreach ($offDateList as $off) {
        //                                 if ($current_start->gte($off['start']) && $current_start->lt($off['end'])) {
        //                                         $current_start = $off['end']->copy();
        //                                         break; // thoát vòng vì current_start đã đổi
        //                                 }

        //                         }

        //                         $offTime = 0;

        //                         $current_end = $current_start->copy()->addMinutes($intervalTime + $C2_time_minutes);

        //                         foreach ($offDateList as $off) {

        //                                 $offStart = $off['start'];
        //                                 $offEnd   = $off['end'];

        //                                 // Nếu offDate KHÔNG overlap với khoảng đang xét → bỏ qua
        //                                 if ($offEnd <= $current_start || $offStart >= $current_end) {
        //                                         continue;
        //                                 }

        //                                 // Tính phần overlap
        //                                 $overlapStart = $offStart->greaterThan($current_start)
        //                                         ? $offStart
        //                                         : $current_start;

        //                                 $overlapEnd = $offEnd->lessThan($current_end)
        //                                         ? $offEnd
        //                                         : $current_end;

        //                                 $offTime += $overlapStart->diffInMinutes($overlapEnd);
        //                         }



        //                         if ($gap >= $intervalTime + $C2_time_minutes + $offTime) {

        //                                 return Carbon::parse($current_start);
        //                                         // --- kiểm tra tank ---
        //                                         // if ($requireTank == true){
        //                                         //         $bestEnd   = $current_start->copy()->addMinutes($intervalTime);
        //                                         //         $bestStart = $current_start->copy();

        //                                         //         $overlapTankCount = DB::table($stage_plan_table) // thay bằng $stage_plan_table nếu cần
        //                                         //         ->whereNotNull('start')
        //                                         //         ->where('tank', 1)
        //                                         //         ->whereIn('stage_code', [3, 4])
        //                                         //         ->where('start', '<', $bestEnd)
        //                                         //         ->where('end', '>', $bestStart)
        //                                         //         ->count();

        //                                         //         if ($overlapTankCount >= $maxTank) {
        //                                         //                 // Nếu tank đã đầy → dời thêm $tankInterval phút rồi thử lại
        //                                         //                 $current_start = $busy['end']->copy()->addMinutes($tankInterval);
        //                                         //                 $tryCount++;
        //                                         //                 if ($tryCount > 100) return false; // tránh vòng lặp vô hạn
        //                                         //                 //continue; // quay lại while
        //                                         //         }
        //                                         // }
        //                                         // if ($requireAHU == true && $AHU_group == true) {
        //                                         //         $bestEnd = $current_start->copy()->addMinutes($intervalTime);
        //                                         //         $bestStart = $current_start->copy();

        //                                         //         $overlapAHUCount = DB::table($stage_plan_table)
        //                                         //                 ->whereNotNull('start')
        //                                         //                 ->where('stage_code', 7)
        //                                         //                 ->where('keep_dry', 1)
        //                                         //                 ->where('AHU_group', $AHU_group)
        //                                         //                 ->where('start', '<', $bestEnd)
        //                                         //                 ->where('end', '>', $bestStart)
        //                                         //         ->count();

        //                                         //         if ($overlapAHUCount >= 3) {
        //                                         //                 $current_start = $busy['end']->copy()->addMinutes($tankInterval);
        //                                         //                 $tryCount++;
        //                                         //                 if ($tryCount > 100) return false; // tránh vòng lặp vô hạn
        //                                         //                 //continue ; // quay lại vòng while
        //                                         //         }
        //                                         // }


                                         
        //                         }
        //                 }

        //                 // nếu current rơi VÀO block bận
        //                 if ($current_start->lt($busy['end'])) {
        //                         // nhảy tới ngay sau block bận
        //                         $current_start = $busy['end']->copy();
        //                 }
        //         }

        //                 // nếu không vướng block nào → kiểm tra tank trước khi trả về
        //                 // if ($requireTank == true) {
        //                 //                 $bestEnd   = $current_start->copy()->addMinutes($intervalTime);
        //                 //                 $bestStart = $current_start->copy();

        //                 //                 $overlapTankCount = DB::table('stage_plan')
        //                 //                         ->whereNotNull('start')
        //                 //                         ->where('tank', 1)
        //                 //                         ->whereIn('stage_code', [3, 4])
        //                 //                         ->where('start', '<', $bestEnd)
        //                 //                         ->where('end', '>', $bestStart)
        //                 //                         ->count();

        //                 //                 if ($overlapTankCount >= $maxTank) {
        //                 //                         $current_start->addMinutes($tankInterval);
        //                 //                         $tryCount++;
        //                 //                         if ($tryCount > 100) return false;
        //                 //                         //continue; // quay lại while
        //                 //                 }

        //                 // }


        //                 // if ($requireAHU == true && $AHU_group == true) {
        //                 //                         $bestEnd = $current_start->copy()->addMinutes($intervalTime);
        //                 //                         $bestStart = $current_start->copy();

        //                 //                         $overlapAHUCount = DB::table($stage_plan_table)
        //                 //                                 ->whereNotNull('start')
        //                 //                                 ->where('stage_code', 7)
        //                 //                                 ->where('keep_dry', 1)
        //                 //                                 ->where('AHU_group', $AHU_group)
        //                 //                                 ->where('start', '<', $bestEnd)
        //                 //                                 ->where('end', '>', $bestStart)
        //                 //                         ->count();

        //                 //                         if ($overlapAHUCount >= 3) {
        //                 //                                 $current_start->addMinutes(15);
        //                 //                                 $tryCount++;
        //                 //                                 if ($tryCount > 100) return false; // tránh vòng lặp vô hạn
        //                 //                                 //continue ; // quay lại vòng while
        //                 //                         }
        //                 // }

        //                 return Carbon::parse($current_start);
        //         // }
        // }

        /**Load room_status để lấy các slot đã bận*/

        // protected function loadRoomAvailability(string $sort, int $roomId){
        //         $this->roomAvailability[$roomId] = []; // reset

        //         // --- 1. Lấy lịch hiện có ---
        //         $schedules = DB::table("stage_plan")
        //                 ->where('start', ">=", now())
        //                 ->where('resourceId', $roomId)
        //                 ->select('resourceId', 'start', DB::raw('COALESCE(end_clearning, end) as end'))
        //                 ->get();


        //         // --- 2. Nạp lịch bận thực tế ---
        //         foreach ($schedules as $row) {
        //                 $this->roomAvailability[$roomId][] = [
        //                 'start' => Carbon::parse($row->start),
        //                 'end'   => Carbon::parse($row->end),
        //                 ];
        //         }

        //         // --- 4. Thêm các ngày được chọn từ selectedDates ---
        //         if (!empty($this->selectedDates) && is_array($this->selectedDates)) {

        //                 // 1️⃣ Parse + sort ngày
        //                 $dates = collect($this->selectedDates)
        //                         ->map(fn ($d) => Carbon::parse($d)->startOfDay())
        //                         ->sort()
        //                         ->values();

        //                 $ranges = [];

        //                 $currentStart = null;
        //                 $currentEnd   = null;
        //                 $prevDate     = null;

        //                 // 2️⃣ Duyệt ngày
        //                 foreach ($dates as $date) {

        //                         // start = 06:00 của ngày đó
        //                         $start = $date->copy()->setTime(6, 0, 0);
        //                         // end   = 06:00 ngày hôm sau
        //                         $end   = $date->copy()->addDay()->setTime(6, 0, 0);

        //                         // Khoảng đầu tiên
        //                         if ($currentStart === null) {
        //                         $currentStart = $start;
        //                         $currentEnd   = $end;
        //                         $prevDate     = $date;
        //                         continue;
        //                         }

        //                         // Nếu ngày hiện tại liên tục ngày trước
        //                         if ($date->diffInDays($prevDate) === 1) {
        //                         // chỉ kéo dài end
        //                         $currentEnd = $end;
        //                         } else {
        //                         // lưu khoảng cũ
        //                         $ranges[] = [
        //                                 'start' => $currentStart,
        //                                 'end'   => $currentEnd,
        //                         ];

        //                         // bắt đầu khoảng mới
        //                         $currentStart = $start;
        //                         $currentEnd   = $end;
        //                         }

        //                         $prevDate = $date;
        //                 }

        //                 // 3️⃣ Push khoảng cuối
        //                 if ($currentStart !== null) {
        //                         $ranges[] = [
        //                         'start' => $currentStart,
        //                         'end'   => $currentEnd,
        //                         ];
        //                 }

        //                 // 4️⃣ Gán cho room
        //                 $this->offDate[$roomId] = $ranges;
        //         }


        //         // --- 4. Sắp xếp lại theo $sort ---
        //         if (!empty($this->roomAvailability[$roomId])) {
        //                 $this->roomAvailability[$roomId] = collect($this->roomAvailability[$roomId])
        //                 ->sortBy('start', SORT_REGULAR, $sort === 'desc')
        //                 ->values()
        //                 ->toArray();
        //         }

        //         if (!empty($this->offDate[$roomId])) {
        //                 $this->offDate[$roomId] = collect($this->offDate[$roomId])
        //                 ->sortBy('start', SORT_REGULAR, $sort === 'desc')
        //                 ->values()
        //                 ->toArray();
        //         }
        // }

        // protected function findEarliestSlot2($roomId, $Earliest, $intervalTime, $C2_time_minutes, $requireTank = 0, $requireAHU = 0, $stage_plan_table = 'stage_plan',  $maxTank = 1, $tankInterval = 60){

        //         $this->loadRoomAvailability('asc', $roomId);

        //         if (!isset($this->roomAvailability[$roomId])) {$this->roomAvailability[$roomId] = [];}

        //         $busyList = $this->roomAvailability[$roomId]; //[$roomId]; // danh sách block bận
        //         $offDateList = $this->offDate[$roomId];

        //         $current_start = Carbon::parse($Earliest);
        //         //$AHU_group  = DB::table ('room')->where ('id',$roomId)->value('AHU_group');




        //         foreach ($busyList as $busy) {

        //                 foreach ($offDateList as $off) {
        //                         if ($current_start->gte($off['start']) && $current_start->lt($off['end'])) {
        //                                 $current_start = $off['end']->copy();
        //                                 break; // thoát vòng vì current_start đã đổi
        //                         }
        //                 }
                              
        //                 if ($current_start->lt($busy['start'])) {
                                        
        //                         $gap = abs($current_start->diffInMinutes($busy['start']));
        //                         if ($gap >= $intervalTime + $C2_time_minutes) {
        //                                 $offTime = 0;
        //                                 $current_end = $current_start->copy()->addMinutes($intervalTime + $C2_time_minutes);

        //                                 foreach ($offDateList as $off) {

        //                                         $offStart = $off['start'];
        //                                         $offEnd   = $off['end'];

        //                                         // Nếu offDate KHÔNG overlap với khoảng đang xét → bỏ qua
        //                                         if ($offEnd <= $current_start || $offStart >= $current_end) {
        //                                                 continue;
        //                                         }

        //                                         // Tính phần overlap
        //                                         $overlapStart = $offStart->greaterThan($current_start)
        //                                                 ? $offStart
        //                                                 : $current_start;

        //                                         $overlapEnd = $offEnd->lessThan($current_end)
        //                                                 ? $offEnd
        //                                                 : $current_end;

        //                                         $offTime += $overlapStart->diffInMinutes($overlapEnd);
        //                                 }
        //                                 if ($gap >= $intervalTime + $C2_time_minutes + $offTime) {
        //                                         return Carbon::parse($current_start);
        //                                 }     
        //                         }
        //                 }

        //                 if ($current_start->lt($busy['end'])) {
        //                         $current_start = $busy['end']->copy();
        //                 }
        //         }
        //                 return Carbon::parse($current_start);
        // }
               // if ($plan->finished == 1){
                        //         $color_event = '#002af9ff';
                        //         $textColor= '#fefefee2';
                        // }else{
                        //         // 🎨 Màu mặc định
                        //         if ($plan->stage_code <= 7 ) {
                        //                 $color_event = '#4CAF50';
                        //                 $textColor= '#fefefee2';
                        //         } elseif ($plan->stage_code == 8) {
                        //                 $color_event = '#003A4F';
                        //                 $textColor= '#fefefee2';
                        //         } else {
                        //                 $color_event = '#eb0cb3ff';
                        //                 $textColor= '#fefefee2';
                        //         }

                        //         // ✅ Nếu hoàn thành
                        //         if ($plan->is_val == 1) {
                        //                 $color_event = '#40E0D0';
                        //                 $textColor= '#fefefee2';
                        //         }


                        //         // ⏱ Kiểm tra biệt trữ giữa các công đoạn
                               
                        //         if ($i > 0) {
                        //                 if ($plan->quarantine_total == 0) {
                        //                 $prev = $plans[$i - 1];
                        //                         if ($plan->stage_code > 2 && $plan->stage_code < 7) {
                        //                                 $diff = round((strtotime($plan->start) - strtotime($prev->end)) / 3600,1);
                        //                                 if ($prev->quarantine_time_limit > 0){
                        //                                         $storage_capacity =  round($diff/$prev->quarantine_time_limit, 2);
                        //                                 }
                        //                                 if ($diff > $prev->quarantine_time_limit) {
                        //                                         $color_event = '#bda124ff';
                        //                                         $textColor= '#fefefee2';
                        //                                         //$subtitle = "Quá Hạn Biệt Trữ: {$diff}h / {$prev->quarantine_time_limit}h";
                        //                                 }
                        //                         }
                        //                 }

                        //         }

  

                        //         // ⏰ Hạn cần hàng / bảo trì
                        //         if ($plan->expected_date < $plan->end && $plan->stage_code < 9 && $color_event != '#bda124ff') {
                        //                 $color_event = '#e54a4aff';
                        //                 $textColor= '#fefefee2';
                        //                 //$subtitle = $plan->stage_code == 8
                        //                 //? "Không Đáp Ứng Hạn Bảo Trì: {$plan->expected_date}"
                        //                 //: "Không Đáp Ứng Ngày Cần Hàng: {$plan->expected_date}";
                        //         }

                        //         if ($plan->clearning_validation == 1) {
                        //                 $color_event = '#e4e405e2';
                        //                 $textColor= '#fb0101e2';
                        //         }


                        //         // 🔗 Kiểm tra predecessor / successor
                        //         if ($plan->predecessor_code) {
                        //                 $prePlan = $plans->firstWhere('code', $plan->predecessor_code);
                        //                 if ($prePlan && $plan->start < $prePlan->end) {
                        //                         $color_event = '#4d4b4bff';
                        //                         $textColor= '#fefefee2';
                        //                         $subtitle = "  ===> (KT " . $this->stage_Name [$prePlan->stage_code] . " tại ". $room_code[$prePlan->resourceId] .": " . Carbon::parse($prePlan->end)->format('H:i d/m/y')  .")";
                        //                 }
                        //         }

                        //         if ($plan->nextcessor_code) {
                        //                 $nextPlan = $plans->firstWhere('code', $plan->nextcessor_code);
                        //                 if ($nextPlan && $plan->end > $nextPlan->start) {
                        //                         $color_event = '#4d4b4bff';
                        //                         $textColor= '#fefefee2';
                        //                         $subtitle = " ===> (BĐ " .  $this->stage_Name [$nextPlan->stage_code] . " tại ". $room_code[$nextPlan->resourceId] .": ". Carbon::parse($nextPlan->start)->format('H:i d/m/y')  .")";
                        //                 }
                        //         }

                        //         // ⚠️ Kiểm tra nguyên liệu / bao bì
                        //         if (($plan->stage_code === 1 || $plan->stage_code === 3)  && $plan->after_weigth_date > $plan->start) {
                        //                 $color_event = '#920000ff';
                        //                 $textColor= '#fefefee2';
                        //                 $subtitle = $subtitle . "Ngày có đủ NL: $plan->after_weigth_date < $plan->start";}

                        //         if (($plan->stage_code === 1 || $plan->stage_code === 3)  && $plan->allow_weight_before_date > $plan->start) {
                        //                 $color_event = '#920000ff';
                        //                 $textColor= '#fefefee2';
                        //                 $subtitle = $subtitle . "Ngày có đủ NL: $plan->allow_weight_before_date < $plan->start";}

                        //         if (($plan->stage_code === 1 || $plan->stage_code === 3)  && $plan->expired_material_date > $plan->start) {
                        //                 $color_event = '#920000ff';
                        //                 $textColor= '#fefefee2';
                        //                 $subtitle = $subtitle . "Ngày có đủ NL: $plan->expired_material_date < $plan->start";}

                        //         if ($plan->stage_code === 3 && $plan->preperation_before_date > $plan->start) {
                        //                 $color_event = '#920000ff';
                        //                 $textColor= '#fefefee2';
                        //                 $subtitle = "Phải PCTrước Ngày: $plan->preperation_before_date";}

                        //         if ($plan->stage_code === 4 && $plan->blending_before_date > $plan->start) {
                        //                 $color_event = '#920000ff';
                        //                 $textColor= '#fefefee2';
                        //                 $subtitle = "Phải Pha THT: $plan->blending_before_date";
                        //         }

                        //         if ($plan->stage_code === 6 && $plan->coating_before_date > $plan->start) {
                        //                 $color_event = '#920000ff';
                        //                 $textColor= '#fefefee2';
                        //                 $subtitle = "Phải Pha BP: $plan->coating_before_date";
                        //         }
                                
                        //         if ($plan->stage_code === 7 && $plan->after_parkaging_date > $plan->start) {
                        //                 $color_event = '#920000ff';
                        //                 $textColor= '#fefefee2';
                        //                 $subtitle = "Ngày có đủ BB: $plan->after_parkaging_date < $plan->start";
                        //         }


                        // }
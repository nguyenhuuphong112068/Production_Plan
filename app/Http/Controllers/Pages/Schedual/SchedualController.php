<?php

namespace App\Http\Controllers\Pages\Schedual;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use PhpOffice\PhpSpreadsheet\Calculation\Logical\Boolean;

class SchedualController  extends  Controller

{

        protected  $roomAvailability  =  [];

        protected  $order_by  =  1;

        protected  $selectedDates  =  [];
        //. lưu ngày nghĩ lấy từ fe
        protected  $offDate  =  [];
        // tạo các khoảng  offdate
        protected  $work_sunday  =  true;

        protected  $max_Step  =  3;

        protected  $reason  =  null;

        protected  $theory  =  0;

        protected  $prev_orderBy  =  false;

        protected  $stage_Name  =  [
                1  =>  "Cân NL",
                3  =>  "PC",
                4  =>  "THT",
                5  =>  "ĐH",
                6  =>  "BP",
                7  =>  "ĐG",
        ];

        protected  $processed_stage_code_Id  =  [];


        public  function  test()
        {
                //$this->Auto_scheduler_Stage_Backward (7,0,0,Carbon::parse(now()));

        }


        public  function  index()

        {

                session()->put(['title'  =>  'LỊCH SẢN XUẤT']);

                return  view('app');
        }


        //thời gian của từng phòng
        public  function  getRoomStatistics($startDate,  $endDate)

        {

                // chuẩn hoá ngày giờ (chuỗi dạng MySQL)
                $start  =  Carbon::parse($startDate)->format('Y-m-d H:i:s');

                $end    =  Carbon::parse($endDate)->format('Y-m-d H:i:s');

                $startCarbon  =  Carbon::parse($start);

                $endCarbon  =  Carbon::parse($end);

                $totalSeconds  =  $startCarbon->diffInSeconds($endCarbon);


                if ($totalSeconds  <=  0) {

                        return  collect();
                }


                // Lấy tất cả các bản ghi chồng lấn với khoảng thời gian yêu cầu
                $plans  =  DB::table("stage_plan as sp")
                        ->select('sp.resourceId',  'sp.start',  'sp.end',  'sp.end_clearning')
                        ->where('sp.deparment_code',  session('user')['production_code'])
                        ->whereRaw('GREATEST(sp.start, ?) < LEAST(COALESCE(sp.end_clearning, sp.end, sp.start), ?)',  [$start,  $end])
                        ->get();


                // Nhóm theo resourceId
                $grouped  =  $plans->groupBy('resourceId');


                $result  =  $grouped->map(function ($items,  $resourceId)  use ($start,  $end,  $totalSeconds) {

                        $intervals  =  [];

                        foreach ($items  as  $item) {

                                $e  =  $item->end_clearning  ??  $item->end  ??  $item->start;

                                $itemStart  =  max(strtotime($item->start),  strtotime($start));

                                $itemEnd  =  min(strtotime($e),  strtotime($end));


                                if ($itemStart  <  $itemEnd) {

                                        $intervals[]  =  [
                                                'start'  =>  $itemStart,
                                                'end'    =>  $itemEnd
                                        ];
                                }
                        }


                        // Thuật toán gộp các khoảng thời gian (Merge Intervals)
                        usort($intervals,  function ($a,  $b) {

                                return  $a['start']  <=>  $b['start'];
                        });


                        $merged  =  [];

                        if (!empty($intervals)) {

                                $current  =  $intervals[0];

                                for ($i  =  1; $i  <  count($intervals); $i++) {

                                        if ($intervals[$i]['start']  <=  $current['end']) {

                                                $current['end']  =  max($current['end'],  $intervals[$i]['end']);
                                        } else {

                                                $merged[]  =  $current;

                                                $current  =  $intervals[$i];
                                        }
                                }

                                $merged[]  =  $current;
                        }


                        $busySeconds  =  0;

                        foreach ($merged  as  $interval) {

                                $busySeconds  +=  ($interval['end']  -  $interval['start']);
                        }


                        $busy_hours  =  $busySeconds  /  3600;

                        $total_hours  =  $totalSeconds  /  3600;


                        return (object)[
                                'resourceId'   =>  $resourceId,
                                'total_hours'  =>  $total_hours,
                                'busy_hours'   =>  $busy_hours,
                                'free_hours'   =>  $total_hours  -  $busy_hours
                        ];
                })->values();


                return  $result;
        }


        // trả về tổngsản lượng lý thuyết
        public  function  yield($startDate,  $endDate,  $group_By)

        {


                $startDate  =  Carbon::parse($startDate);

                $endDate  =  Carbon::parse($endDate);


                $stage_plan_100  =  DB::table("stage_plan as sp")
                        ->whereRaw('((sp.start >= ? AND sp.end <= ?))',  [$startDate,  $endDate])
                        ->whereNotNull('sp.start')
                        ->where('sp.deparment_code',  session('user')['production_code'])
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
                        ->groupBy("sp.$group_By",  "unit")
                        ->get();



                $stage_plan_part  =  DB::table("stage_plan as sp")
                        ->whereRaw('(sp.start < ? AND sp.end > ?) AND NOT (sp.start >= ? AND sp.end <= ?)',  [$endDate,  $startDate,  $startDate,  $endDate])
                        ->whereNotNull('sp.start')
                        ->where('sp.deparment_code',  session('user')['production_code'])
                        ->select(
                                "sp.$group_By",
                                DB::raw('
                        SUM(
                                sp.Theoretical_yields *
                                TIME_TO_SEC(TIMEDIFF(LEAST(sp.end, "'  .  $endDate  .  '"), GREATEST(sp.start, "'  .  $startDate  .  '"))) /
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
                        ->groupBy("sp.$group_By",  "unit")
                        ->get();


                $merged  =  $stage_plan_100->merge($stage_plan_part)
                        ->groupBy(function ($item)  use ($group_By) {

                                return  $item->$group_By  .  '-'  .  $item->unit;
                        })
                        ->map(function ($items)  use ($group_By) {

                                return (object)[
                                        $group_By  =>  $items->first()->$group_By,
                                        'unit'  =>  $items->first()->unit,
                                        'total_qty'  =>  round($items->sum('total_qty'),  2), // 👈 làm tròn 2 chữ số
                                ];
                        })
                        ->values();


                return  $merged;
        }


        protected  function  getEvents($production,  $startDate,  $endDate,  $clearning,  int  $theory)

        {


                $startDate  =  Carbon::parse($startDate)->toDateTimeString();

                $endDate    =  Carbon::parse($endDate)->toDateTimeString();


                $room_code  =  DB::table('room')->where('deparment_code',  $production)->pluck('code',  'id');


                $maxFinishedStage  =  DB::table('stage_plan')
                        ->where('finished',  1)
                        ->select(
                                'plan_master_id',
                                DB::raw('MAX(stage_code) as max_finished_stage')
                        )
                        ->groupBy('plan_master_id');


                // 2️⃣ Lấy danh sách stage_plan (gộp toàn bộ join)
                $event_plans  =  DB::table("stage_plan as sp")
                        ->leftJoin('plan_master',  'sp.plan_master_id',  '=',  'plan_master.id')
                        ->leftJoin('finished_product_category',  'plan_master.product_caterogy_id',  '=',  'finished_product_category.id')
                        ->leftJoin('intermediate_category',  'finished_product_category.intermediate_code',  '=',  'intermediate_category.intermediate_code')
                        ->leftJoin('product_name',  'intermediate_category.product_name_id',  '=',  'product_name.id')
                        ->leftJoin('dosage',  'intermediate_category.dosage_id',  '=',  'dosage.id')

                        ->leftJoinSub($maxFinishedStage,  'sp_max',  function ($join) {

                                $join->on('sp.plan_master_id',  '=',  'sp_max.plan_master_id');
                        })
                        ->leftJoin('stage_plan as sp_last',  function ($join) {

                                $join->on('sp.plan_master_id',  '=',  'sp_last.plan_master_id')
                                        ->on('sp_last.stage_code',  '=',  'sp_max.max_finished_stage');
                        })

                        ->where('sp.active',  1)
                        ->whereNotNull('sp.resourceId')
                        ->when(!in_array(session('user')['userGroup'],  ['Schedualer',  'Admin',  'Leader']),  fn($query)  =>  $query->where('sp.submit',  1))
                        ->where('sp.deparment_code',  $production)
                        ->where(function ($q) {

                                $q->whereNotNull('sp.start')
                                        ->orWhereNotNull('sp.actual_start');
                        })
                        ->where(function ($q)  use ($startDate,  $endDate) {

                                $q->whereRaw('(sp.start <= ? AND sp.end >= ?)',  [$endDate,  $startDate])
                                        ->orWhereRaw('(sp.start_clearning <= ? AND sp.end_clearning >= ?)',  [$endDate,  $startDate])
                                        ->orWhereRaw('(sp.actual_start <= ? AND sp.actual_end >= ?)',  [$endDate,  $startDate])
                                        ->orWhereRaw('(sp.actual_start_clearning <= ? AND sp.actual_end_clearning >= ?)',  [$endDate,  $startDate]);
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
                $groupedPlans  =  $event_plans->groupBy('plan_master_id');

                $events  =  collect();



                // 5️⃣ duyệt từng nhóm (theo batch sản xuất)
                foreach ($groupedPlans  as  $plans) {


                        $plans  =  $plans->values();
                        // sắp sẵn theo stage_code ở query
                        for ($i  =  0,  $n  =  $plans->count(); $i  <  $n; $i++) {

                                $storage_capacity  =  null;

                                $plan  =  $plans[$i];

                                $subtitle  =  null;


                                [$color_event,  $textColor,  $subtitle]  =  $this->colorEvent($plan,  $plans,  $i,  $room_code);


                                // 🎯 lịch chưa hoàn thành
                                if (($plan->start  &&  !$plan->actual_start  &&  $plan->finished  ==  0)) {

                                        $events->push([
                                                'plan_id'  =>  $plan->id,
                                                'id'  =>  "{$plan->id}-main",
                                                'title'  =>  $plan->title  .  "-"  .  $plan->w2,
                                                'start'  =>   $plan->start,
                                                'end'  =>   $plan->end,
                                                'resourceId'  =>  $plan->resourceId,
                                                'color'  =>   $plan->finished  ==  1  ?  '#002af9ff'  :  $color_event,
                                                'textColor'  =>  $textColor,
                                                'plan_master_id'  =>  $plan->plan_master_id,
                                                'stage_code'  =>  $plan->stage_code,
                                                'is_clearning'  =>  false,
                                                'finished'  =>  $plan->finished,
                                                'level'  =>  $plan->level,
                                                'process_code'  =>  $plan->process_code,
                                                'keep_dry'  =>  $plan->keep_dry,
                                                'tank'  =>  $plan->tank,
                                                'expected_date'  =>  Carbon::parse($plan->expected_date)->format('d/m/y'),
                                                'submit'  =>  $plan->submit,
                                                'storage_capacity'  =>  $storage_capacity,
                                                'subtitle'  =>  $subtitle,
                                                'campaign_code'  =>  $plan->campaign_code,
                                                'status'   =>  $plan->status,
                                                'first_in_campaign'  =>  $plan->first_in_campaign,
                                                'product_name'  =>  $plan->product_name,
                                                'batch_name'  =>  $plan->batch_name,
                                                'actual_batch'  =>  $plan->actual_batch
                                        ]);
                                }

                                // 🎯 lịch đã hoàn thành
                                if (($clearning  &&  $plan->start_clearning   &&  !$plan->actual_start_clearning   &&  $plan->yields  >=  0   &&  $plan->finished  ==  0)  ||
                                        ($clearning  &&  $plan->actual_start_clearning   &&  !$plan->actual_start_clearning  &&  $plan->yields  >=  0  &&  $plan->finished  ==  0)
                                ) {

                                        $events->push([
                                                'plan_id'  =>  $plan->id,
                                                'id'  =>  "{$plan->id}-cleaning",
                                                'title'  =>  $plan->title_clearning  ??  'VS',
                                                'start'  =>  $plan->actual_start_clearning  ??  $plan->start_clearning,
                                                'end'  =>  $plan->actual_end_clearning  ??  $plan->end_clearning,
                                                'resourceId'  =>  $plan->resourceId,
                                                'color'  =>  '#a1a2a2ff',
                                                'textColor'  =>  $textColor,
                                                'plan_master_id'  =>  $plan->plan_master_id,
                                                'stage_code'  =>  $plan->stage_code,
                                                'is_clearning'  =>  true,
                                                'finished'  =>  $plan->finished,
                                                'process_code'  =>  $plan->process_code,
                                                'campaign_code'  =>  $plan->campaign_code,
                                                'product_name'  =>  $plan->product_name,
                                                'batch_name'  =>  $plan->batch_name,
                                                'actual_batch'  =>  $plan->actual_batch
                                        ]);
                                }


                                if ($plan->actual_start  &&  $plan->finished  ==  1) {


                                        if ($theory  ==  0) {

                                                //Lich thực tế
                                                $events->push([
                                                        'plan_id'  =>  $plan->id,
                                                        'id'  =>  "{$plan->id}-main",
                                                        'title'  =>  $plan->title,
                                                        'start'  =>   $plan->actual_start,
                                                        'end'  =>   $plan->actual_end,
                                                        'resourceId'  =>  $plan->resourceId,
                                                        'color'  =>  '#002af9ff',
                                                        'textColor'  =>  $textColor,
                                                        'plan_master_id'  =>  $plan->plan_master_id,
                                                        'stage_code'  =>  $plan->stage_code,
                                                        'is_clearning'  =>  false,
                                                        'finished'  =>  $plan->finished,
                                                        'level'  =>  $plan->level,
                                                        'process_code'  =>  $plan->process_code,
                                                        'keep_dry'  =>  $plan->keep_dry,
                                                        'tank'  =>  $plan->tank,
                                                        'storage_capacity'  =>  $storage_capacity,
                                                        'campaign_code'  =>  $plan->campaign_code,
                                                        'product_name'  =>  $plan->product_name,
                                                        'batch_name'  =>  $plan->batch_name,
                                                        'actual_batch'  =>  $plan->actual_batch,

                                                ]);

                                                // event lich vs thực tế
                                                if ($clearning   &&  $plan->yields  >=  0) {

                                                        $events->push([
                                                                'plan_id'  =>  $plan->id,
                                                                'id'  =>  "{$plan->id}-cleaning",
                                                                'title'  =>  $plan->title_clearning,
                                                                'start'  =>  $plan->actual_start_clearning,
                                                                'end'  =>   $plan->actual_end_clearning,
                                                                'resourceId'  =>  $plan->resourceId,
                                                                'color'  =>  '#002af9ff',
                                                                'textColor'  =>  $textColor,
                                                                'plan_master_id'  =>  $plan->plan_master_id,
                                                                'stage_code'  =>  $plan->stage_code,
                                                                'is_clearning'  =>  true,
                                                                'finished'  =>  $plan->finished,
                                                                'process_code'  =>  $plan->process_code,
                                                                'campaign_code'  =>  $plan->campaign_code,
                                                                'product_name'  =>  $plan->product_name,
                                                                'batch_name'  =>  $plan->batch_name,
                                                                'actual_batch'  =>  $plan->actual_batch,
                                                        ]);
                                                }
                                        } else if ($theory  ==  1) {

                                                if ($plan->start) {

                                                        $events->push([
                                                                'plan_id'  =>  $plan->id,
                                                                'id'  =>  "{$plan->id}-main-theory",
                                                                'title'  =>  trim($plan->title  .  "- Lịch Lý Thuyết"  ??  ''),
                                                                'start'  =>   $plan->start,
                                                                'end'  =>   $plan->end,
                                                                'resourceId'  =>  $plan->resourceId,
                                                                'color'  =>  '#8397faff',
                                                                'textColor'  =>  $textColor,
                                                                'plan_master_id'  =>  $plan->plan_master_id,
                                                                'stage_code'  =>  $plan->stage_code,
                                                                'is_clearning'  =>  false,
                                                                'finished'  =>  $plan->finished,
                                                                'level'  =>  $plan->level,
                                                                'process_code'  =>  $plan->process_code,
                                                                'keep_dry'  =>  $plan->keep_dry,
                                                                'tank'  =>  $plan->tank,
                                                                'storage_capacity'  =>  $storage_capacity,
                                                                'campaign_code'  =>  $plan->campaign_code,
                                                                'product_name'  =>  $plan->product_name,
                                                                'batch_name'  =>  $plan->batch_name,
                                                                'actual_batch'  =>  $plan->actual_batch
                                                        ]);
                                                }

                                                // event lich vs lý thuyết
                                                if ($clearning  &&  $plan->yields  >=  0  &&  $plan->start_clearning) {

                                                        $events->push([
                                                                'plan_id'  =>  $plan->id,
                                                                'id'  =>  "{$plan->id}-cleaning-theory",
                                                                'title'  =>  $plan->title_clearning  .  " - Lịch Lý Thuyết"  ??  'Vệ sinh',
                                                                'start'  =>  $plan->start_clearning,
                                                                'end'  =>   $plan->end_clearning,
                                                                'resourceId'  =>  $plan->resourceId,
                                                                'color'  =>  '#8397faff',
                                                                'textColor'  =>  $textColor,
                                                                'plan_master_id'  =>  $plan->plan_master_id,
                                                                'stage_code'  =>  $plan->stage_code,
                                                                'is_clearning'  =>  true,
                                                                'finished'  =>  $plan->finished,
                                                                'process_code'  =>  $plan->process_code,
                                                                'campaign_code'  =>  $plan->campaign_code,
                                                                'product_name'  =>  $plan->product_name,
                                                                'batch_name'  =>  $plan->batch_name,
                                                                'actual_batch'  =>  $plan->actual_batch
                                                        ]);
                                                }
                                        } else if ($theory  ==  2) {


                                                $events->push([
                                                        'plan_id'  =>  $plan->id,
                                                        'id'  =>  "{$plan->id}-main",
                                                        'title'  =>  $plan->title,
                                                        'start'  =>   $plan->actual_start,
                                                        'end'  =>   $plan->actual_end,
                                                        'resourceId'  =>  $plan->resourceId,
                                                        'color'  =>  '#002af9ff',
                                                        'textColor'  =>  $textColor,
                                                        'plan_master_id'  =>  $plan->plan_master_id,
                                                        'stage_code'  =>  $plan->stage_code,
                                                        'is_clearning'  =>  false,
                                                        'finished'  =>  $plan->finished,
                                                        'level'  =>  $plan->level,
                                                        'process_code'  =>  $plan->process_code,
                                                        'keep_dry'  =>  $plan->keep_dry,
                                                        'tank'  =>  $plan->tank,
                                                        'storage_capacity'  =>  $storage_capacity,
                                                        'campaign_code'  =>  $plan->campaign_code,
                                                        'product_name'  =>  $plan->product_name,
                                                        'batch_name'  =>  $plan->batch_name,
                                                        'actual_batch'  =>  $plan->actual_batch
                                                ]);


                                                // event lich vs thực tế
                                                if ($clearning   &&  $plan->yields  >=  0) {

                                                        $events->push([
                                                                'plan_id'  =>  $plan->id,
                                                                'id'  =>  "{$plan->id}-cleaning",
                                                                'title'  =>  $plan->title_clearning,
                                                                'start'  =>  $plan->actual_start_clearning,
                                                                'end'  =>   $plan->actual_end_clearning,
                                                                'resourceId'  =>  $plan->resourceId,
                                                                'color'  =>  '#002af9ff',
                                                                'textColor'  =>  $textColor,
                                                                'plan_master_id'  =>  $plan->plan_master_id,
                                                                'stage_code'  =>  $plan->stage_code,
                                                                'is_clearning'  =>  true,
                                                                'finished'  =>  $plan->finished,
                                                                'process_code'  =>  $plan->process_code,
                                                                'campaign_code'  =>  $plan->campaign_code,
                                                                'product_name'  =>  $plan->product_name,
                                                                'batch_name'  =>  $plan->batch_name,
                                                                'actual_batch'  =>  $plan->actual_batch
                                                        ]);
                                                }



                                                if ($plan->start) {

                                                        $events->push([
                                                                'plan_id'  =>  $plan->id,
                                                                'id'  =>  "{$plan->id}-main-theory",
                                                                'title'  =>  trim($plan->title  .  "- Lịch Lý Thuyết"  ??  ''),
                                                                'start'  =>   $plan->start,
                                                                'end'  =>   $plan->end,
                                                                'resourceId'  =>  $plan->resourceId,
                                                                'color'  =>  '#8397faff',
                                                                'textColor'  =>  $textColor,
                                                                'plan_master_id'  =>  $plan->plan_master_id,
                                                                'stage_code'  =>  $plan->stage_code,
                                                                'is_clearning'  =>  false,
                                                                'finished'  =>  $plan->finished,
                                                                'level'  =>  $plan->level,
                                                                'process_code'  =>  $plan->process_code,
                                                                'keep_dry'  =>  $plan->keep_dry,
                                                                'tank'  =>  $plan->tank,
                                                                'storage_capacity'  =>  $storage_capacity,
                                                                'campaign_code'  =>  $plan->campaign_code,
                                                                'product_name'  =>  $plan->product_name,
                                                                'batch_name'  =>  $plan->batch_name,
                                                                'actual_batch'  =>  $plan->actual_batch
                                                        ]);
                                                }

                                                // event lich vs lý thuyết
                                                if ($clearning  &&  $plan->yields  >=  0  &&  $plan->start_clearning) {

                                                        $events->push([
                                                                'plan_id'  =>  $plan->id,
                                                                'id'  =>  "{$plan->id}-cleaning-theory",
                                                                'title'  =>  $plan->title_clearning  .  " - Lịch Lý Thuyết"  ??  'Vệ sinh',
                                                                'start'  =>  $plan->start_clearning,
                                                                'end'  =>   $plan->end_clearning,
                                                                'resourceId'  =>  $plan->resourceId,
                                                                'color'  =>  '#8397faff',
                                                                'textColor'  =>  $textColor,
                                                                'plan_master_id'  =>  $plan->plan_master_id,
                                                                'stage_code'  =>  $plan->stage_code,
                                                                'is_clearning'  =>  true,
                                                                'finished'  =>  $plan->finished,
                                                                'process_code'  =>  $plan->process_code,
                                                                'campaign_code'  =>  $plan->campaign_code,
                                                                'product_name'  =>  $plan->product_name,
                                                                'batch_name'  =>  $plan->batch_name,
                                                                'actual_batch'  =>  $plan->actual_batch
                                                        ]);
                                                }
                                        }
                                }
                        }
                }


                // 🔥 Gộp các công đoạn 1 & 2 mang cùng Campaign, thời gian, phòng
                $weighingStages  =  $events->whereIn('stage_code',  [1,  2]);

                $otherStages  =  $events->whereNotIn('stage_code',  [1,  2]);


                $groupedWeighing  =  $weighingStages->groupBy(function ($event) {

                        $e  = (object)$event;

                        $isTheory  =  strpos($e->id,  '-theory')  !==  false;

                        $isFinished  =  ($e->finished  ??  0)  ==  1;


                        // tiêu đề yêu cầu mới: chỉ so khớp start, resourceid và stage_code
                        // vẫn giữ is_clearning và istheory để tránh gộp main và cleaning hoặc theory và actual
                        return  $e->start  .  '_'  .  $e->resourceId  .  '_'  .  $e->stage_code  .  '_'  .  ($e->is_clearning  ?  'CL'  :  'MN')  .  '_'  .  ($isTheory  ?  'TH'  :  'AC');
                })->map(function ($group) {

                        $first  = (object)$group->first();

                        $first  =  clone  $first;


                        $isTheory  =  strpos($first->id,  '-theory')  !==  false;

                        $isFinished  =  ($first->finished  ??  0)  ==  1;


                        if ($group->count()  >  1) {

                                $pureIds  =  $group->pluck('plan_id')->toArray();

                                $suffix  =  $isTheory  ?  '-theory'  :  '';

                                $typeSuffix  =  $first->is_clearning  ?  '-cleaning'  :  '-main';

                                $first->id  =  implode(',',  $pureIds)  .  $typeSuffix  .  $suffix;


                                // Tính toán min Start và max End cho tất cả các sự kiện gộp Stage 1 & 2
                                $first->start  =  $group->min('start');

                                $first->end  =  $group->max('end');


                                if (!$first->is_clearning) {

                                        // Gom danh sách số lô (batch) - Ưu tiên actual_batch cho Finished/Theory
                                        $batchField  =  ($isFinished  ||  $isTheory)  ?  'actual_batch'  :  'batch_name';

                                        $allBatches  =  $group->pluck($batchField)->unique()->filter()->toArray();


                                        // fallback nếu actual_batch trống (đặc biệt cho theory)
                                        if (empty($allBatches)) {

                                                $allBatches  =  $group->pluck('batch_name')->unique()->filter()->toArray();
                                        }


                                        // Tiêu đề gộp: Product_Name (Batch1, Batch2...)
                                        $productName  =  $first->product_name  ??  $first->title  ??  "Sản phẩm";

                                        $batchList  =  implode(", ",  $allBatches);

                                        $first->title  =  "{$productName} ({$batchList})";
                                }
                        }

                        return  $first;
                })->values();


                $events  =  $otherStages->concat($groupedWeighing)->values();


                return  $events;
        }


        protected  function  colorEvent($plan,  $plans,  $i,  $room_code)

        {


                $subtitle    =  '';

                $textColor   =  '#fefefee2';

                $color_event  =  '#eb0cb3ff';
                // default fallback

                /* 1️⃣ finished */
                if ($plan->finished  ==  1) {

                        return  ['#002af9ff',  $textColor,  $subtitle];
                }


                /* 2️⃣ màu mặc định theo stage */
                if ($plan->stage_code  <=  7) {

                        $color_event  =  '#4CAF50';
                } elseif ($plan->stage_code  ==  8) {

                        // Mặc định cho Bảo trì (BT)
                        $color_event  =  '#003A4F';


                        // tinh chỉnh màu theo loại block (hc, bt, ti)
                        if (isset($plan->code)) {

                                if (substr($plan->code,  -2)  ===  'HC') {

                                        $color_event  =  '#9a1b72ff';
                                        // Tím đậm cho Hiệu chuẩn
                                } elseif (substr($plan->code,  -2)  ===  'TI') {

                                        $color_event  =  '#830cbfff';
                                        // Cam đất cho Tiện ích
                                }
                        }
                }


                /* 3️⃣ validation ok */
                if ($plan->is_val  ==  1) {

                        $color_event  =  '#40E0D0';
                }


                /* 4️⃣ clearning */
                if ($plan->clearning_validation  ==  1) {

                        return  ['#e4e405e2',  '#fb0101e2',  $subtitle];
                }


                /* 5️⃣ biệt trữ */
                if ($i  >  0  &&  $plan->quarantine_total  ==  0  &&  $plan->stage_code  >  3  &&  $plan->stage_code  <  7  &&  $plan->accept_quarantine  ==  0) {

                        $prev  =  $plans->firstWhere('code',  $plan->predecessor_code);

                        if ($prev  &&  $plan->start) {

                                $diffMinutes  =  Carbon::parse($prev->end)
                                        ->diffInMinutes(Carbon::parse($plan->start),  false);

                                $limitMinutes  =  $prev->quarantine_time_limit_hour  *  60;


                                if ($limitMinutes  >  0  &&  $diffMinutes  >  $limitMinutes) {


                                        $h  =  minutesToDayHoursMinutesString($diffMinutes);

                                        $lh  =  minutesToDayHoursMinutesString($limitMinutes);


                                        $subtitle  =
                                                "➡️ (KT {$this->stage_Name[$prev->stage_code]}: "
                                                .  Carbon::parse($prev->end)->format('H:i d/m/y')
                                                .  " || TGTB thực tế: $h"
                                                .  " || TGTB cho phép: $lh";


                                        return  ['#bda124ff',  $textColor,  $subtitle];
                                }
                        }
                }


                /* 6️⃣ HẠN CẦN HÀNG */
                $Stage_plan_7  =  $plans->firstWhere('stage_code',  7);


                $overExpected  =  ($Stage_plan_7  &&  $plan->expected_date  <  $Stage_plan_7->end)  ||  $plan->expected_date  <  $plan->end;


                if ($overExpected  &&  $plan->stage_code  <=  7) {

                        $color_event  =  '#e54a4aff';

                        $endStage7  =  $Stage_plan_7  &&  $Stage_plan_7->end  ?  Carbon::parse($Stage_plan_7->end)->format('d/m/y')  :  'Chưa xác định';

                        $subtitle  =  "➡️ Ngày dự kiến KCS: "  .  Carbon::parse($plan->expected_date)->format('d/m/y')  .  " | Ngày KT ĐG: "  .  $endStage7;
                }


                /* 7️⃣ predecessor / successor */
                if ($plan->predecessor_code) {

                        $pre  =  $plans->firstWhere('code',  $plan->predecessor_code);

                        if ($pre  &&  $plan->start  <  $pre->end) {

                                $subtitle  =  "➡️ (KT {$this->stage_Name[$pre->stage_code]} tại {$room_code[$pre->resourceId]}: "
                                        .  Carbon::parse($pre->end)->format('H:i d/m/y')  .  ")";

                                return  ['#4CAF50',  $textColor,  $subtitle]; //'#4d4b4bff'

                        }
                }


                if ($plan->nextcessor_code) {

                        $next  =  $plans->firstWhere('code',  $plan->nextcessor_code);

                        if ($next  &&  $plan->end  >  $next->start) {

                                $subtitle  =  "➡️ (BĐ {$this->stage_Name[$next->stage_code]} tại {$room_code[$next->resourceId]}: "
                                        .  Carbon::parse($next->start)->format('H:i d/m/y')  .  ")";

                                return  ['#4CAF50',  $textColor,  $subtitle]; //'#4d4b4bff'

                        }
                }


                /* 8️⃣ NGUYÊN LIỆU / BAO BÌ */
                $criticalChecks  =  [
                        [1,  3,  'after_weigth_date',         '➡️ Ngày có đủ NL',  ">"],
                        [1,  3,  'allow_weight_before_date',  '➡️ Ngày được phép cân',  ">"],
                        [1,  3,  'expired_material_date',     '➡️ Ngày hết hạn NL chính',  "<"],
                        [7,  7,  'expired_packing_date',     '➡️ Ngày hết hạn BB',  "<"],
                        [3,  3,  'preperation_before_date',  '➡️ Phải PC trước ngày',  "<"],
                        [4,  4,  'blending_before_date',    '➡️ Phải THT trước ngày',  "<"],
                        [6,  6,  'coating_before_date',     '➡️ Phải BP trước ngày',  "<"],
                        [7,  7,  'parkaging_before_date',     '➡️ Phải ĐG trước ngày ',  "<"],
                        [7,  7,  'after_parkaging_date',    '➡️ Ngày có đủ BB',  ">"],

                ];


                foreach ($criticalChecks  as  [$from,  $to,  $field,  $label,  $operator]) {


                        if (
                                $plan->stage_code  <  $from  ||
                                $plan->stage_code  >  $to  ||
                                empty($plan->$field)
                        ) {

                                continue;
                        }


                        $left   =  Carbon::parse($plan->$field);

                        $right  =  Carbon::parse($plan->start);


                        $matched  =  match ($operator) {

                                '<'   =>  $left->lt($right),

                                '<='  =>  $left->lte($right),

                                '>'   =>  $left->gt($right),

                                '>='  =>  $left->gte($right),

                                '=='  =>  $left->eq($right),

                                default  =>  false,
                        };


                        if ($matched) {

                                $subtitle  =  "{$label}: "
                                        .  $left->format('d/m/y')
                                        .  " {$operator} "
                                        .  $right->format('d/m/y');


                                return  ['#920000ff',  $textColor,  $subtitle];
                        }
                }



                return  [$color_event,  $textColor,  $subtitle];
        }

        // hàm lấy quota
        protected  function  getQuota($production)

        {

                $result  =  DB::table('quota')
                        ->leftJoin('room',  'quota.room_id',  '=',  'room.id')
                        ->where('quota.active',  1)
                        ->where('quota.deparment_code',  $production)
                        ->get()
                        ->map(function ($item) {

                                $toSeconds  =  fn($time)  => (($h  = (int)explode(':',  $time)[0])  *  3600)  +  ((int)explode(':',  $time)[1]  *  60);

                                $toTime  =  fn($seconds)  =>  sprintf('%02d:%02d',  floor($seconds  /  3600),  floor(($seconds  %  3600)  /  60));

                                $item->PM  =  $toTime($toSeconds($item->p_time)  +  $toSeconds($item->m_time));

                                return  $item;
                        });



                return  $result;
        }


        public  function  getPlanWaiting($production,  $order_by_type  =  false)
        {


                $order_by_column  =  "sp.order_by";

                if ($order_by_type) {

                        $order_by_column  =  "sp.order_by_line";
                }


                $plan_waiting  =  DB::table("stage_plan as sp")
                        ->whereNull('sp.start')
                        ->where('sp.active',  1)
                        ->where('sp.finished',  0)
                        ->where('sp.stage_code',  '!=',  8)
                        ->where('sp.deparment_code',  $production)
                        ->leftJoin('plan_master',  'sp.plan_master_id',  '=',  'plan_master.id')
                        ->leftJoin('plan_list',  'sp.plan_list_id',  '=',  'plan_list.id')
                        ->leftJoin('source_material',  'plan_master.material_source_id',  '=',  'source_material.id')

                        ->leftJoin('finished_product_category',  function ($join) {

                                $join->on('sp.product_caterogy_id',  '=',  'finished_product_category.id')
                                        ->where('sp.stage_code',  '<=',  7);
                        })
                        ->leftJoin('intermediate_category',  'finished_product_category.intermediate_code',  '=',  'intermediate_category.intermediate_code')
                        ->leftJoin('product_name',  function ($join) {

                                $join->on('intermediate_category.product_name_id',  '=',  'product_name.id')
                                        ->where('sp.stage_code',  '<=',  7);
                        })
                        ->leftJoin('market',  'finished_product_category.market_id',  '=',  'market.id')


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
                                //'plan_master.batch', 
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
                                //DB::raw("CASE WHEN sp.stage_code <= 7 THEN product_name.name ELSE sp.code END as name")
                        )
                        ->orderBy($order_by_column,  'asc')
                        ->get();


                if ($plan_waiting->isEmpty()) {

                        return  $plan_waiting;
                }


                // 3️⃣ Lấy dữ liệu liên quan chỉ 1 lần
                $quota  =  DB::table('quota')
                        ->leftJoin('room',  'quota.room_id',  '=',  'room.id')
                        ->where('quota.active',  1)
                        ->where('quota.deparment_code',  $production)
                        ->select(
                                'quota.*',
                                'room.name',
                                'room.code'
                        )
                        ->get();



                // Tạo map tra cứu nhanh
                $quotaByIntermediate  =  $quota->groupBy(function ($q) {

                        return  $q->intermediate_code  .  '_'  .  $q->stage_code;
                });



                $quotaByFinished  =  $quota->groupBy(function ($q) {

                        return   $q->intermediate_code  .  '_'  .  $q->finished_product_code  .  '_'  .  $q->stage_code;
                });



                // 4️⃣ Map dữ liệu permission_room (cực nhanh)
                $plan_waiting->transform(function ($plan)  use ($quotaByIntermediate,  $quotaByFinished) {

                        if ($plan->stage_code  <=  6) {

                                $key  =  $plan->intermediate_code  .  '_'  .  $plan->stage_code;

                                $matched  =  $quotaByIntermediate[$key]  ??  collect();
                        } elseif ($plan->stage_code  ==  7) {

                                $key  =  $plan->intermediate_code  .  '_'  .   $plan->finished_product_code  .  '_'  .  $plan->stage_code;

                                $matched  =  $quotaByFinished[$key]  ??  collect();
                        } else {

                                $matched  =  collect();
                        }


                        // Mảng phòng được phép
                        $plan->permisson_room  =  collect($matched)->pluck('code',  'room_id')->unique();


                        // ✅ Thêm field để React có thể filter/search nhanh
                        $plan->permisson_room_filter  =  $plan->permisson_room->values()->implode(', ');


                        return  $plan;
                });



                return  $plan_waiting;
        }


        // hàm lấy sản lượng và thời gian sản xuất theo phòng
        protected  function  getResources($production,  $startDate,  $endDate, $hasMaintenance = false)

        {


                $roomStatus  =  $this->getRoomStatistics($startDate,  $endDate);

                $sumBatchQtyResourceId  =  $this->yield($startDate,  $endDate,  "resourceId");


                $statsMap  =  $roomStatus->keyBy('resourceId');

                $yieldMap  =  $sumBatchQtyResourceId->keyBy('resourceId');


                $result  =  DB::table('room')
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
                        ->where('active',  1)
                        ->where('room.deparment_code',  $production)
                        ->when(
                                !$hasMaintenance,
                                fn($query)  =>  $query->where('only_maintenance',  0)
                        )
                        ->orderBy('order_by',  'asc')
                        ->get()
                        ->map(function ($room)  use ($statsMap,  $yieldMap) {

                                $stat  =  $statsMap->get($room->id);

                                $yield  =  $yieldMap->get($room->id);

                                $room->busy_hours  =  $stat->busy_hours  ??  0;

                                $room->free_hours  =  $stat->free_hours  ??  0;

                                $room->total_hours  =  $stat->total_hours  ??  0;

                                $room->yield  =  $yield->total_qty  ??  0;

                                $room->unit  =  $yield->unit  ??  '';

                                return  $room;
                        });


                return  $result;
        }


        // hàm view gọn hơn request
        public  function  view(Request  $request)

        {



                $startDate  =  $request->startDate  ??  Carbon::now();

                $endDate  =  $request->endDate  ??  Carbon::now()->addDays(7);

                $viewtype  =  $request->viewtype  ??  "resourceTimelineWeek";

                $this->theory  = (int)$request->theory  ??  0;


                try {

                        $production  =  session('user')['production_code'];

                        $department  =  DB::table('user_management')->where('userName',  session('user')['userName'])->value('deparment');


                        $clearing  =  $request->clearning  ??  true;


                        if ($viewtype  ==  "resourceTimelineQuarter") {

                                $clearing  =  false;
                        }


                        if (user_has_permission(session('user')['userId'],  'loading_plan_waiting',  'boolean')) {

                                $plan_waiting  =  $this->getPlanWaiting($production);

                                $bkc_code  =  DB::table('stage_plan_bkc')->where('deparment_code',  session('user')['production_code'])->select('bkc_code')->distinct()->orderByDesc('bkc_code')->get();

                                $reason  =  DB::table('reason')->where('deparment_code',  $production)->pluck('name');

                                $quota  =  $this->getQuota($production);
                        }



                        $stageMap  =  DB::table('room')->where('deparment_code',  $production)->pluck('stage_code',  'stage')->toArray();


                        $events  =  $this->getEvents($production,  $startDate,  $endDate,  $clearing,  $this->theory);


                        $sumBatchByStage  =  $this->yield($startDate,  $endDate,  "stage_code");


                        $resources  =  $this->getResources($production,  $startDate,  $endDate);





                        $title  =  'LỊCH SẢN XUẤT';

                        $type  =  true;


                        $Lines  =  DB::table('room')
                                ->select('stage_code',  'name',  'code')
                                ->where('deparment_code',  $production)
                                ->whereIn('stage_code',  [3,  4,  5,  6,  7])
                                ->where('active',  1)
                                ->orderBy('order_by')
                                ->get()
                                ->groupBy('stage_code')
                                ->map(function ($items) {

                                        return  $items->map(function ($room) {

                                                return  [
                                                        'name'       =>  $room->code,
                                                        'name_code'  =>  $room->code  .  ' - '  .  $room->name,
                                                ];
                                        })->values();
                                });


                        $allLines  =  DB::table('room')
                                ->select('stage_code',  'name',  'code')
                                ->where('deparment_code',  $production)
                                ->whereIn('stage_code',  [3,  4,  5,  6,  7])
                                ->where('active',  1)
                                ->orderBy('order_by')
                                ->get();






                        $authorization  =  session('user')['userGroup'];

                        $UesrID  =   session('user')['userId'];


                        return  response()->json([
                                'title'  =>  $title,
                                'events'  =>  $events,
                                'plan'  =>  $plan_waiting  ??  [],  // [phân quyền]
                                'quota'  =>  $quota  ??  [],
                                'stageMap'  =>  $stageMap  ??  [],
                                'resources'  =>  $resources  ??  [],
                                'sumBatchByStage'  =>   $sumBatchByStage  ??  [],
                                'reason'  =>  $reason  ??  [],
                                'type'  =>  $type,
                                'authorization'  =>  $authorization,
                                'production'  =>  $production,
                                'department'  =>  $department,
                                'currentPassword'  =>  session('user')['passWord']  ??  '',
                                'Lines'        =>  $Lines  ??  [],
                                'allLines'  =>  $allLines  ??  [],
                                'off_days'  =>  DB::table('off_days')->where('off_date',  '>=',  now())->get()->pluck('off_date')  ??  [],
                                'bkc_code'  =>  $bkc_code  ??  [],
                                'UesrID'  =>  $UesrID
                        ]);
                } catch (\Throwable  $e) {

                        // Ghi log chi tiết lỗi
                        Log::error('Error in view(): '  .  $e->getMessage(),  [
                                'line'  =>  $e->getLine(),
                                'file'  =>  $e->getFile(),
                                'trace'  =>  $e->getTraceAsString()
                        ]);


                        return  response()->json([
                                'error'  =>  true,
                                'message'  =>  $e->getMessage(),
                        ],  500);
                }
        }


        // hàm tính tổng sản lượng lý thuyết theo stage
        public  function  getSumaryData(Request  $request)

        {

                $sumBatchByStage  =  $this->yield($request->startDate,  $request->endDate,  "stage_code");

                return  response()->json([
                        'sumBatchByStage'  =>  $sumBatchByStage,
                ]);
        }


        public  function  getInforSoure(Request  $request)

        {


                $plan_master  =  DB::table('plan_master')
                        ->select('finished_product_category.intermediate_code',  'product_name.name as product_name',  'plan_master.material_source_id',  'source_material.name')
                        ->leftJoin('finished_product_category',  'plan_master.product_caterogy_id',  '=',  'finished_product_category.id')
                        ->leftJoin('source_material',  'plan_master.material_source_id',  'source_material.id')
                        ->leftJoin('product_name',  'finished_product_category.product_name_id',  'product_name.id')
                        ->where('plan_master.id',  $request->plan_master_id)
                        ->first();


                return  response()->json([
                        'sourceInfo'  =>  $plan_master,
                ]);
        }


        public  function  confirm_source(Request  $request)

        {

                try {

                        DB::table('room_source')->insert([
                                'intermediate_code'  =>   $request->intermediate_code,
                                'room_id'  =>   $request->room_id,
                                'source_id'  =>   $request->source_id,
                                'prepared_by'  =>  session('user')['fullName'],
                                'created_at'  =>  now()
                        ]);


                        $production  =  session('user')['production_code'];

                        $events  =  $this->getEvents($production,  $request->startDate,  $request->endDate,  true,  $this->theory);

                        return  response()->json([
                                'events'  =>  $events,
                        ]);
                } catch (\Exception  $e) {

                        DB::rollBack();

                        Log::error('Lỗi cập nhật sự kiện:',  ['error'  =>  $e->getMessage()]);

                        return  response()->json(['status'  =>  'error',  'message'  =>  $e->getMessage()],  500);
                }
        }


        public  function  store(Request  $request)

        {


                $this->selectedDates  =   $request->offdate  ??  [];
                //giữ để tạo $this->offdate
                $this->loadOffDate('asc');
                // Tạo  $this->offdate

                $multi_stage  =  $request->multiStage  ??  false;

                $start_date  =  null;


                DB::beginTransaction();

                try {


                        // Sắp xếp products theo batch
                        $products  =  collect($request->products)->sortBy('batch')->values();


                        // Thời gian bắt đầu ban đầu
                        $current_start  =  Carbon::parse($request->start);


                        $slotDuration  =  $request->slotDuration;


                        if ($request->has('slotDuration')  &&   $request->slotDuration  ==  1) {

                                $room  =  DB::table('room')->where('id',  $request->room_id)->first();


                                if ($room) {

                                        if ($room->sheet_regular  ==  1) {

                                                $current_start->setTime(7,  15,  0);
                                        } elseif ($room->sheet_1  ==  1) {

                                                $current_start->setTime(6,  0,  0);
                                        } elseif ($room->sheet_1  ==  0  &&  $room->sheet_2  ==  1) {

                                                $current_start->setTime(14,  0,  0);
                                        } else {

                                                $current_start->setTime(6,  0,  0);
                                        }
                                }
                        }


                        // 🔥 kiểm tra ngay từ đầu nếu current_start nằm trong offdate
                        foreach ($products  as  $index  =>  $product) {


                                /*
                                |--------------------------------------------------------------------------
                                | lấy quota
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

                                        if ($pm && $pm->only_parkaging == 1) {
                                                $ratio = $pm->percent_parkaging;
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

                                        $end_clearning = $end_man->copy()->addMinutes((float)$C2_time_minutes);
                                        $clearning_type = "VS-II";
                                        $firstBatachStart = $current_start;
                                } else {

                                        if ($products->count() === 1) {
                                                $current_start = $this->skipOffTime($current_start, $this->offDate, $request->room_id);

                                                $end_man = $this->addWorkingMinutes($current_start->copy(), $p_time_adj + $m_time_adj, $request->room_id, $this->work_sunday);

                                                $start_clearning = $end_man->copy();
                                                $end_clearning = $this->addWorkingMinutes($start_clearning->copy(), (float) $C2_time_minutes, $request->room_id, $this->work_sunday);
                                                $clearning_type = "VS-II";

                                                $start_date = $end_man;
                                                $firstBatachStart = $current_start;
                                                $lastBatachEnd = $end_clearning;
                                        } else {

                                                if ($index === 0) {

                                                        $end_man = $this->addWorkingMinutes($current_start->copy(), $p_time_adj + $m_time_adj, $request->room_id, $this->work_sunday);
                                                        $start_clearning = $end_man->copy();
                                                        $end_clearning = $this->addWorkingMinutes($start_clearning->copy(), (float) $C1_time_minutes, $request->room_id, $this->work_sunday);

                                                        $start_date = $end_man;
                                                        $clearning_type = "VS-I";
                                                        $firstBatachStart = $current_start;
                                                } elseif ($index === $products->count() - 1) {

                                                        $end_man = $this->addWorkingMinutes($current_start->copy(), $m_time_adj, $request->room_id, $this->work_sunday);
                                                        $start_clearning = $end_man->copy();
                                                        $end_clearning = $this->addWorkingMinutes($start_clearning->copy(), (float) $C2_time_minutes, $request->room_id, $this->work_sunday);
                                                        $clearning_type = "VS-II";
                                                        $lastBatachEnd = $end_clearning;
                                                } else {

                                                        $end_man = $this->addWorkingMinutes($current_start->copy(), $m_time_adj, $request->room_id, $this->work_sunday);
                                                        $start_clearning = $end_man->copy();
                                                        $end_clearning = $this->addWorkingMinutes($start_clearning->copy(), (float) $C1_time_minutes, $request->room_id, $this->work_sunday);
                                                        $clearning_type = "VS-I";
                                                }
                                        }
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | lưu stage_plan
                                |--------------------------------------------------------------------------
                                */
                                if ($product['stage_code']  ===  9) {

                                        DB::table('stage_plan')
                                                ->where('id',  $product['id'])
                                                ->update([
                                                        'start'            =>  $current_start,
                                                        'end'              =>  $end_man,
                                                        'start_clearning'  =>  $start_clearning,
                                                        'end_clearning'    =>  $end_clearning,
                                                        'resourceId'       =>  $request->room_id,
                                                        'schedualed'       =>  1,
                                                        'schedualed_by'    =>  session('user')['fullName'],
                                                        'schedualed_at'    =>  now(),
                                                ]);
                                } else {


                                        $offDays  =  DB::table('off_days')
                                                ->whereDate('off_date',  '<=',  $current_start)
                                                ->pluck('off_date')
                                                ->toArray();


                                        $receiveDate  =  Carbon::parse($current_start)->subDay();


                                        while (in_array($receiveDate->toDateString(),  $offDays)) {

                                                $receiveDate->subDay();
                                        }


                                        DB::table('stage_plan')
                                                ->where('id',  $product['id'])
                                                ->update([
                                                        'start'            =>  $current_start,
                                                        'end'              =>  $end_man,
                                                        'start_clearning'  =>  $end_man,
                                                        'end_clearning'    =>  $end_clearning,
                                                        'resourceId'       =>  $request->room_id,
                                                        'title'            =>  $product['stage_code']  ===  9
                                                                ?  ($product['title']  .  "-"  .  $product['batch'])
                                                                : ($product['name']  .  "-"  .  $product['batch']  .  "-"  .  $product['market']),
                                                        'title_clearning'  =>  $clearning_type,
                                                        'schedualed'       =>  1,
                                                        'schedualed_by'    =>  session('user')['fullName'],
                                                        'schedualed_at'    =>  now(),
                                                        'receive_packaging_date'    => DB::raw("CASE WHEN received = 0 THEN '$receiveDate' ELSE receive_packaging_date END"),
                                                        'receive_second_packaging_date' => DB::raw("CASE WHEN received_second_packaging = 0 THEN '$receiveDate' ELSE receive_second_packaging_date END")
                                                ]);
                                }



                                /*
                                |--------------------------------------------------------------------------
                                | LƯU LỊCH SỬ
                                |--------------------------------------------------------------------------
                                */
                                $submit  =  DB::table('stage_plan')->where('id',  $product['id'])->value('submit');


                                if ($submit  ==  1) {

                                        $last_version  =  DB::table('stage_plan_history')
                                                ->where('stage_plan_id',  $product['id'])
                                                ->max('version')  ??  0;

                                        $this->syncPackagingDate($product['id'], $receiveDate, 0);
                                        $this->syncPackagingDate($product['id'], $receiveDate, 1);

                                        DB::table('stage_plan_history')->insert([
                                                'stage_plan_id'   =>  $product['id'],
                                                'version'         =>  $last_version  +  1,
                                                'start'           =>  $current_start,
                                                'end'             =>  $end_man,
                                                'resourceId'      =>  $request->room_id,
                                                'schedualed_by'   =>  session('user')['fullName'],
                                                'schedualed_at'   =>  now(),
                                                'deparment_code'  =>  session('user')['production_code'],
                                                'type_of_change'  =>  $request->reason  ??  "Lập Lịch Thủ Công",
                                        ]);
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | tính current_start cho sản phẩm tiếp theo
                                |--------------------------------------------------------------------------
                                */
                                if ($product['stage_code']  >  2) {

                                        $current_start  =  $end_clearning;
                                }


                                // 🔥 SAU KHI TĂNG current_start → KIỂM TRA NGÀY OFF
                                $current_start  =  $this->skipOffTime($current_start,  $this->offDate,  $request->room_id);
                        }


                        //// set lại mã chiến dịch
                        if ($product['stage_code']  ==  3) {

                                $campaign_code  =  $products->first()['plan_master_id'];


                                DB::table('stage_plan')
                                        ->whereIn('plan_master_id',  $products->pluck('plan_master_id'))
                                        ->update([
                                                'campaign_code'   =>  $campaign_code,
                                        ]);
                        }




                        if ($multi_stage) {


                                $this->max_Step  =  7;

                                $totalTimeCampaign  =  abs($firstBatachStart->diffInMinutes($lastBatachEnd));

                                // Làm liên tục các công cộng sau
                                $nextcessor_codes  =  collect();

                                $nextTasks  =  collect();

                                $firstTask  =  $products->first();

                                $next_stage_code  =  isset($firstTask->nextcessor_code)
                                        ? (int) (explode('_',  $firstTask->nextcessor_code)[1]  ??  0)
                                        :  0;


                                // $hasimmediately = collect($campaigntasks)->contains('immediately', 1);

                                if ($next_stage_code  <=  $this->max_Step) {
                                        //&& $firstTask->immediately == 1

                                        $nextcessor_codes  =  $products->pluck('nextcessor_code');


                                        $nextTasks  =   DB::table("stage_plan as sp")
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
                                                ->leftJoin('plan_master',  'sp.plan_master_id',  'plan_master.id')
                                                ->leftJoin('finished_product_category',  'sp.product_caterogy_id',  'finished_product_category.id')
                                                ->leftJoin('product_name',  'finished_product_category.product_name_id',  'product_name.id')
                                                ->leftJoin('market',  'finished_product_category.market_id',  'market.id')
                                                ->leftJoin('stage_plan as prev',  'prev.code',  '=',  'sp.predecessor_code')
                                                ->whereIn('sp.code',  $nextcessor_codes)
                                                ->where('sp.active',  1)
                                                ->where('sp.deparment_code',  session('user')['production_code'])
                                                ->orderBy('prev.start',  'asc')
                                                ->get();



                                        if ($nextTasks->isNotEmpty()) {

                                                $waite_time  =  0;

                                                if ($nextTasks->contains('is_val',  1)) {

                                                        $waite_time  =  5  *  24  *  60;
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

                        Log::error('Lỗi cập nhật sự kiện:',  ['error'  =>  $e->getMessage()]);

                        return  response()->json([
                                'status'   =>  'error',
                                'message'  =>  $e->getMessage()
                        ],  500);
                }


                /*
                |--------------------------------------------------------------------------
                | TRẢ KẾT QUẢ
                |--------------------------------------------------------------------------
                */
                $production  =  session('user')['production_code'];

                $events  =  $this->getEvents($production,  $request->startDate,  $request->endDate,  true,  $this->theory);

                $plan_waiting  =  $this->getPlanWaiting($production);

                $sumBatchByStage  =  $this->yield($request->startDate,  $request->endDate,  "stage_code");


                return  response()->json([
                        'events'  =>  $events,
                        'plan'  =>  $plan_waiting,
                        'sumBatchByStage'  =>  $sumBatchByStage,
                ]);
        }


        public  function  history(Request  $request)

        {

                try {

                        // Lấy dữ liệu lịch sử theo stage_plan_id
                        $history_data  =  DB::table('stage_plan_history')
                                ->leftJoin('stage_plan',  'stage_plan_history.stage_plan_id',  'stage_plan.id')
                                ->leftJoin('room',  'stage_plan_history.resourceId',  'room.id')
                                ->where('stage_plan_id',  $request->stage_code_id)
                                ->select(
                                        'stage_plan_history.*',
                                        'stage_plan.title',
                                        DB::raw("CONCAT(room.name, ' ', room.code) as room_name")
                                )
                                ->orderBy('version',  'desc')
                                ->get();


                        // nếu không có dữ liệu thì trả về version = 0
                        if ($history_data->isEmpty()) {

                                $history_data  =  collect([
                                        [
                                                'version'  =>  0,
                                                'start'  =>  null,
                                                'end'  =>  null,
                                                'start_clearning'  =>  null,
                                                'end_clearning'  =>  null,
                                                'schedualed_at'  =>  null,
                                        ]
                                ]);
                        }




                        // trả dữ liệu về frontend
                        return  response()->json([
                                'history_data'  =>  $history_data,
                        ]);
                } catch (\Exception  $e) {

                        Log::error('Lỗi lấy history:',  ['error'  =>  $e->getMessage()]);

                        return  response()->json([
                                'message'  =>  'Không thể lấy dữ liệu history',
                        ],  500);
                }
        }


        public  function  store_maintenance(Request  $request)

        {


                DB::beginTransaction();

                try {

                        $products  =  collect($request->products);

                        $current_start  =  Carbon::parse($request->start);

                        if ($request->is_HVAC  ==  true) {

                                foreach ($products  as  $index  =>  $product) {

                                        if ($index  ===  0) {

                                                $quota  =  DB::table('maintenance_category')
                                                        ->where('code',  $product['instrument_code'])
                                                        ->selectRaw('TIME_TO_SEC(quota) / 60 as quota_minutes')
                                                        ->first();


                                                $execute_time_minutes  = (int) ($quota->quota_minutes  ??  0);

                                                $end_man  =  $current_start->copy()->addMinutes($execute_time_minutes);

                                                $room_id  =  array_keys($product['permisson_room']);
                                        }


                                        DB::table('stage_plan')
                                                ->where('id',  $product['id'])
                                                ->update([
                                                        'start'            =>  $current_start,
                                                        'end'              =>  $end_man,
                                                        'resourceId'       =>  $room_id[$index],
                                                        'title'            =>  $product['name'],
                                                        'schedualed'       =>  1,
                                                        'schedualed_by'    =>  session('user')['fullName'],
                                                        'schedualed_at'    =>  now(),
                                                ]);


                                        $submit  =  DB::table('stage_plan')->where('id',  $product['id'])->value('submit');


                                        if ($submit  ===  1) {

                                                $last_version  =  DB::table('stage_plan_history')->where('stage_plan_id',  $product['id'])->max('version')  ??  0;

                                                DB::table('stage_plan_history')
                                                        ->insert([
                                                                'stage_plan_id'    =>  $product['id'],
                                                                'version'          =>  $last_version  +  1,
                                                                'start'            =>  $current_start,
                                                                'end'              =>  $end_man,
                                                                'resourceId'       =>  $request->room_id,
                                                                'schedualed_by'    =>  session('user')['fullName'],
                                                                'schedualed_at'    =>  now(),
                                                                'deparment_code'   =>  session('user')['production_code'],
                                                                'type_of_change'   =>  $this->reason  ??  "Lập Lịch Thủ Công"
                                                        ]);
                                        }
                                }
                        } else {


                                foreach ($products  as  $index  =>  $product) {


                                        $quota  =  DB::table('maintenance_category')
                                                ->where('code',  $product['instrument_code'])
                                                ->selectRaw('TIME_TO_SEC(quota) / 60 as quota_minutes')
                                                ->first();


                                        $execute_time_minutes  = (int) ($quota->quota_minutes  ??  0);

                                        $end_man  =  $current_start->copy()->addMinutes($execute_time_minutes);

                                        $room_id  =  array_keys($product['permisson_room']);


                                        DB::table('stage_plan')
                                                ->where('id',  $product['id'])
                                                ->update([
                                                        'start'            =>  $current_start,
                                                        'end'              =>  $end_man,
                                                        'resourceId'       =>  $room_id[0],
                                                        'title'            =>  $product['name'],
                                                        'schedualed'       =>  1,
                                                        'schedualed_by'    =>  session('user')['fullName'],
                                                        'schedualed_at'    =>  now(),
                                                ]);

                                        $update_row  =  DB::table('stage_plan')->where('id',  $product['id'])->first();

                                        if ($update_row->submit  ===  1) {

                                                $last_version  =  DB::table('stage_plan_history')->where('stage_plan_id',  $product['id'])->max('version')  ??  0;

                                                DB::table('stage_plan_history')
                                                        ->insert([

                                                                'stage_plan_id'  =>  $product['id'],
                                                                'plan_list_id'  =>  $update_row->plan_list_id,
                                                                'plan_master_id'  =>  $update_row->plan_master_id,
                                                                'product_caterogy_id'  =>  $update_row->product_caterogy_id,
                                                                'campaign_code'  =>  $update_row->campaign_code,
                                                                'code'  =>  $update_row->code,
                                                                'order_by'  =>  $update_row->order_by,
                                                                'schedualed'  =>  $update_row->schedualed,
                                                                'stage_code'  =>  $update_row->stage_code,
                                                                'title'  =>  $update_row->title,
                                                                'start'  =>  $update_row->start,
                                                                'end'  =>  $update_row->end,
                                                                'resourceId'  =>  $update_row->resourceId,
                                                                'title_clearning'  =>  $update_row->title_clearning,
                                                                'start_clearning'  =>  $update_row->start_clearning,
                                                                'end_clearning'  =>  $update_row->end_clearning,
                                                                'tank'  =>  $update_row->tank,
                                                                'keep_dry'  =>  $update_row->keep_dry,
                                                                'AHU_group'  =>  $update_row->AHU_group,
                                                                'schedualed_by'  =>  $update_row->schedualed_by,
                                                                'schedualed_at'  =>  $update_row->schedualed_at,
                                                                'version'  =>   DB::table('stage_plan_history')->where('stage_plan_id',  $product['id'])->max('version')  +  1  ??  1,
                                                                'note'  =>  $update_row->note,
                                                                'deparment_code'  =>  session('user')['production_code'],
                                                                'type_of_change'  =>  $request->reason,
                                                                'created_date'  =>  now(),
                                                                'created_by'  =>  session('user')['fullName'],
                                                        ]);
                                        }

                                        $current_start  =  $end_man;
                                }
                        }


                        DB::commit();
                } catch (\Exception  $e) {
                        DB::rollBack();

                        Log::error('Lỗi cập nhật sự kiện:',  ['error'  =>  $e->getMessage()]);

                        return  response()->json(['status'  =>  'error',  'message'  =>  $e->getMessage()],  500);
                }


                $production  =  session('user')['production_code'];

                $events  =  $this->getEvents($production,  $request->startDate,  $request->endDate,  true,  $this->theory);

                $plan_waiting  =  $this->getPlanWaiting($production);

                $sumBatchByStage  =  $this->yield($request->startDate,  $request->endDate,  "stage_code");


                return  response()->json([
                        'events'  =>  $events,
                        'plan'  =>  $plan_waiting,
                        'sumBatchByStage'  =>  $sumBatchByStage,
                ]);
        }


        public function update(Request $request)
        {
                $offDays = DB::table('off_days')
                        ->whereDate('off_date', '>=', now())
                        ->pluck('off_date')
                        ->toArray();

                $changes = $request->input('changes', []);
                $this->theory = (int)$request->theory ?? 0;

                try {
                        // Lưu lý do một lần duy nhất nếu cần
                        if (is_array($request->reason) && ($request->reason['saveReason'] ?? false)) {
                                DB::table('reason')->updateOrInsert(
                                        [
                                                'name'           => $request->reason['reason'],
                                                'deparment_code' => session('user')['production_code']
                                        ],
                                        [
                                                'created_by'     => session('user')['fullName'],
                                                'created_at'     => now(),
                                        ]
                                );
                        }

                        foreach ($changes as $change) {
                                $idParts = explode('-', $change['id']);
                                $realId  = $idParts[0] ?? null;
                                $type    = $idParts[1] ?? null;

                                if (!$realId) continue;

                                if ($type && strpos($type, 'cleaning') !== false) {
                                        DB::table('stage_plan')
                                                ->whereIn('id', explode(',', $realId))
                                                ->update([
                                                        'start_clearning' => $change['start'],
                                                        'end_clearning'   => $change['end'],
                                                        'resourceId'      => $change['resourceId'],
                                                ]);
                                } else {
                                        $receiveDate = Carbon::parse($change['start'])->subDay();
                                        while (in_array($receiveDate->toDateString(), $offDays)) {
                                                $receiveDate->subDay();
                                        }

                                        $idsArray = explode(',', $realId);
                                        DB::table('stage_plan')
                                                ->whereIn('id', $idsArray)
                                                ->update([
                                                        'start'                     => $change['start'],
                                                        'end'                       => $change['end'],
                                                        'resourceId'                => $change['resourceId'],
                                                        'schedualed_by'             => session('user')['fullName'],
                                                        'schedualed_at'             => now(),
                                                        'accept_quarantine'         => 0,
                                                        // Không update ngày nhận ở đây nếu đã nhận (sẽ được xử lý trong loop bên dưới)
                                                        'receive_packaging_date'    => DB::raw("CASE WHEN received = 0 THEN '$receiveDate' ELSE receive_packaging_date END"),
                                                        'receive_second_packaging_date' => DB::raw("CASE WHEN received_second_packaging = 0 THEN '$receiveDate' ELSE receive_second_packaging_date END")
                                                ]);

                                        foreach ($idsArray as $sid) {
                                                $update_row = DB::table('stage_plan')->where('id', $sid)->first();

                                                if ($update_row && $update_row->submit == 1) {

                                                        $this->syncPackagingDate($sid, $receiveDate, 0);
                                                        $this->syncPackagingDate($sid, $receiveDate, 1);

                                                        DB::table('stage_plan_history')
                                                                ->insert([
                                                                        'stage_plan_id'   => $sid,
                                                                        'campaign_code'   => $update_row->campaign_code,
                                                                        'code'            => $update_row->code,
                                                                        'order_by'        => $update_row->order_by,
                                                                        'schedualed'      => $update_row->schedualed,
                                                                        'stage_code'      => $update_row->stage_code,
                                                                        'title'           => $update_row->title,
                                                                        'start'           => $update_row->start,
                                                                        'end'             => $update_row->end,
                                                                        'resourceId'      => $update_row->resourceId,
                                                                        'title_clearning' => $update_row->title_clearning,
                                                                        'start_clearning' => $update_row->start_clearning,
                                                                        'end_clearning'   => $update_row->end_clearning,
                                                                        'tank'            => $update_row->tank,
                                                                        'keep_dry'        => $update_row->keep_dry,
                                                                        'AHU_group'       => $update_row->AHU_group,
                                                                        'schedualed_by'   => $update_row->schedualed_by,
                                                                        'schedualed_at'   => $update_row->schedualed_at,
                                                                        'version'         => DB::table('stage_plan_history')->where('stage_plan_id', $sid)->max('version') + 1 ?? 1,
                                                                        'note'            => $update_row->note,
                                                                        'deparment_code'  => session('user')['production_code'],
                                                                        'type_of_change'  => $request->reason['reason'],
                                                                        'created_date'    => now(),
                                                                        'created_by'      => session('user')['fullName'],
                                                                ]);
                                                }
                                        }
                                }
                        }
                } catch (\Exception $e) {
                        Log::error('Lỗi cập nhật sự kiện:', ['error' => $e->getMessage()]);
                        return response()->json(['error' => 'Lỗi hệ thống'], 500);
                }

                $production = session('user')['production_code'];
                $events     = $this->getEvents($production, $request->startDate, $request->endDate, true, $this->theory);
                $sumBatchByStage = $this->yield($request->startDate, $request->endDate, "stage_code");
                $resources  = $this->getResources($production, $request->startDate, $request->endDate);

                return response()->json([
                        'events'          => $events,
                        'resources'       => $resources,
                        'sumBatchByStage' => $sumBatchByStage,
                ]);
        }


        public  function  updateClearning(Request  $request)

        {



                $changes  =  $request->input('changes',  []);


                try {

                        foreach ($changes  as  $change) {

                                // Tách id: "102-main" -> 102
                                $idParts  =  explode('-',  $change['id']);

                                $realId  =  $idParts[0]  ??  null;


                                if (!$realId) {

                                        continue;
                                        // bỏ qua nếu id không hợp lệ
                                }


                                // Nếu là sự kiện vệ sinh (title chứa "VS-")

                                DB::table('stage_plan')
                                        ->where('id',  $realId)
                                        ->update([
                                                'start_clearning'  =>  $change['start'],
                                                'end_clearning'    =>  $change['end'],
                                                'resourceId'       =>  $change['resourceId'],
                                                'schedualed_by'    =>  session('user')['fullName'],
                                                'schedualed_at'    =>  now(),
                                        ]);
                        }
                } catch (\Exception  $e) {

                        Log::error('Lỗi cập nhật sự kiện:',  ['error'  =>  $e->getMessage()]);

                        return  response()->json(['error'  =>  'Lỗi hệ thống'],  500);
                }


                $production  =  session('user')['production_code'];

                $events  =  $this->getEvents($production,  $request->startDate,  $request->endDate,  true,  $this->theory);

                $plan_waiting  =  $this->getPlanWaiting($production);

                $sumBatchByStage  =  $this->yield($request->startDate,  $request->endDate,  "stage_code");


                return  response()->json([
                        'events'  =>  $events,
                        'plan'  =>  $plan_waiting,
                        'sumBatchByStage'  =>  $sumBatchByStage,
                ]);
        }


        public  function  deActive(Request  $request)

        {


                $items  =  collect($request->input('ids'));

                try {


                        foreach ($items  as  $item) {

                                $rowId  =  explode('-',  $item['id'])[0];
                                // lấy id trước dấu -
                                $stageCode  =  $item['stage_code'];


                                if ($stageCode  <=  2  ||  $stageCode  >=  8) {

                                        // chỉ cóa cân k xóa các công đoạn khác


                                        DB::table('stage_plan')
                                                ->whereIn('id',  explode(',',  $rowId))
                                                ->where('finished',  0)
                                                ->update([
                                                        'start'             =>  null,
                                                        'end'               =>  null,
                                                        'start_clearning'   =>  null,
                                                        'end_clearning'     =>  null,
                                                        'resourceId'        =>  null,
                                                        'title'             =>  null,
                                                        'title_clearning'   =>  null,
                                                        'accept_quarantine'  =>  0,
                                                        'schedualed'        =>  0,
                                                        'AHU_group'  =>  0,
                                                        'schedualed_by'     =>  session('user')['fullName'],
                                                        'schedualed_at'     =>  now(),
                                                ]);
                                } else {


                                        $plan  =  DB::table('stage_plan')->where('id',  $rowId)->first();


                                        DB::table('stage_plan')
                                                ->where('finished',  0)
                                                ->where('plan_master_id',  $plan->plan_master_id)
                                                ->where('stage_code',  '>=',  $stageCode)
                                                ->where('stage_code',  '!=',  8) // CHẶN: không xóa lan tỏa tới bảo trì
                                                ->update([
                                                        'start'             =>  null,
                                                        'end'               =>  null,
                                                        'start_clearning'   =>  null,
                                                        'end_clearning'     =>  null,
                                                        'resourceId'        =>  null,
                                                        'title'             =>  null,
                                                        'title_clearning'   =>  null,
                                                        'accept_quarantine'  =>  0,
                                                        'schedualed'        =>  0,
                                                        'schedualed_by'     =>  session('user')['fullName'],
                                                        'schedualed_at'     =>  now(),
                                                ]);
                                }
                        }
                } catch (\Exception  $e) {

                        Log::error('Lỗi cập nhật sự kiện:',  ['error'  =>  $e->getMessage()]);

                        return  response()->json(['status'  =>  'error',  'message'  =>  $e->getMessage()],  500);
                }




                $production  =  session('user')['production_code'];

                $events  =  $this->getEvents($production,  $request->startDate,  $request->endDate,  true,  $this->theory);

                $plan_waiting  =  $this->getPlanWaiting($production);

                $sumBatchByStage  =  $this->yield($request->startDate,  $request->endDate,  "stage_code");

                $resources  =  $this->getResources($production,  $request->startDate,  $request->endDate);


                return  response()->json([
                        'events'  =>  $events,
                        'plan'  =>  $plan_waiting,
                        'resources'  =>  $resources,
                        'sumBatchByStage'  =>  $sumBatchByStage,
                ]);
        }


        public  function  deActiveAll(Request  $request)

        {

                $production  =  session('user')['production_code'];

                try {

                        if ($request->mode  ==  "step") {

                                if ($request->selectedStep  ==  "CNL") {

                                        $ids  =  DB::table('stage_plan')
                                                ->where('deparment_code',  $production)
                                                ->whereNotNull('start')
                                                ->where('start',  '>=',  $request->start_date)
                                                ->where('active',  1)
                                                ->where('finished',  0)
                                                ->where('stage_code',  "<=",  2)
                                                ->pluck('id');
                                } else {

                                        $Step  =  ["PC"  =>  3,  "THT"  =>  4,  "ĐH"  =>  5,  "BP"  =>  6,  "ĐG"  =>  7];

                                        $stage_code  =  $Step[$request->selectedStep];


                                        $ids  =  DB::table('stage_plan')
                                                ->where('deparment_code',  $production)
                                                ->whereNotNull('start')
                                                ->where('start',  '>=',  $request->start_date)
                                                ->where('active',  1)
                                                ->where('finished',  0)
                                                ->where('stage_code',  ">=",  $stage_code)
                                                ->pluck('id');
                                }
                        } else if ($request->mode  ==  "resource") {

                                $stage_code  =  DB::table('room')->where('id',  $request->resourceId)->value('stage_code');

                                if ($stage_code  >=  3) {

                                        $plan_master_ids  =  DB::table('stage_plan')
                                                ->where('resourceId',  "=",  $request->resourceId)
                                                ->where('deparment_code',  $production)
                                                ->whereNotNull('start')
                                                ->where('start',  '>=',  $request->start_date)
                                                ->where('active',  1)
                                                ->where('finished',  0)
                                                ->pluck('plan_master_id');


                                        $ids  =  DB::table('stage_plan')
                                                ->whereIn('plan_master_id',  $plan_master_ids)
                                                ->where('stage_code',  ">=",  $stage_code)
                                                ->pluck('id');
                                } else {


                                        $ids  =  DB::table('stage_plan')
                                                ->where('resourceId',  "=",  $request->resourceId)
                                                ->where('deparment_code',  $production)
                                                ->whereNotNull('start')
                                                ->where('start',  '>=',  $request->start_date)
                                                ->where('active',  1)
                                                ->where('finished',  0)
                                                ->pluck('id');
                                }
                        }




                        if ($ids->isEmpty()) {

                                $production  =  session('user')['production_code'];

                                $events  =  $this->getEvents($production,  $request->startDate,  $request->endDate,  true,  $this->theory);

                                $plan_waiting  =  $this->getPlanWaiting($production);

                                $sumBatchByStage  =  $this->yield($request->startDate,  $request->endDate,  "stage_code");

                                $resources  =  $this->getResources($production,  $request->startDate,  $request->endDate);

                                return  response()->json([
                                        'events'  =>  $events,
                                        'plan'  =>  $plan_waiting,
                                        'resources'  =>  $resources,
                                        'sumBatchByStage'  =>  $sumBatchByStage,
                                ]);
                        }




                        if ($ids->isNotEmpty()) {

                                // Lấy danh sách campaign_code + stage_code của các dòng bị xoá
                                $deletedRows  =  DB::table('stage_plan')
                                        ->where('deparment_code',  $production)
                                        ->whereIn('id',  $ids)
                                        ->select('campaign_code',  'stage_code')
                                        ->get();


                                // Lấy thêm các id khác cùng campaign_code & stage_code, start < start_date
                                $relatedIds  =  DB::table('stage_plan')
                                        ->where('deparment_code',  $production)
                                        ->where(function ($query)  use ($deletedRows) {

                                                foreach ($deletedRows  as  $row) {

                                                        $query->orWhere(function ($q)  use ($row) {

                                                                $q->where('campaign_code',  $row->campaign_code)
                                                                        ->where('stage_code',  $row->stage_code);
                                                        });
                                                }
                                        })
                                        ->where('start',  '<',  $request->start_date)
                                        ->pluck('id');


                                // Gộp danh sách id lại
                                $ids  =  $ids->merge($relatedIds)->unique();
                        }



                        DB::table('stage_plan')
                                ->whereIn('id',   $ids)
                                ->where('stage_code',  '!=',  8) // CHẶN: không xóa trắng công đoạn bảo trì
                                ->update([
                                        'start'  =>  null,
                                        'end'  =>  null,
                                        'start_clearning'  =>  null,
                                        'end_clearning'  =>  null,
                                        'resourceId'  =>  null,
                                        'title_clearning'  =>  null,
                                        'accept_quarantine'  =>  0,
                                        'schedualed'  =>  0,
                                        'AHU_group'  =>  0,
                                        'schedualed_by'  =>   session('user')['fullName'],
                                        'schedualed_at'  =>  now(),
                                ]);
                } catch (\Exception  $e) {

                        Log::error('Lỗi cập nhật sự kiện:',  ['error'  =>  $e->getMessage()]);

                        return  response()->json(['status'  =>  'error',  'message'  =>  $e->getMessage()],  500);
                }


                $production  =  session('user')['production_code'];

                $events  =  $this->getEvents($production,  $request->startDate,  $request->endDate,  true,  $this->theory);

                $plan_waiting  =  $this->getPlanWaiting($production);

                $sumBatchByStage  =  $this->yield($request->startDate,  $request->endDate,  "stage_code");

                return  response()->json([
                        'events'  =>  $events,
                        'plan'  =>  $plan_waiting,
                        'sumBatchByStage'  =>  $sumBatchByStage,
                ]);
        }


        public  function  finished(Request  $request)

        {

                $ids  =  $request->id;

                try {

                        if (isset($request->temp)) {

                                foreach ($ids  as  $id) {

                                        DB::table('stage_plan')
                                                ->where('plan_master_id',  $id)
                                                ->where('stage_code',  '<=',  $request->stage_code)
                                                ->update([
                                                        'finished'  =>  1
                                                ]);
                                }
                        } else {

                                DB::table('stage_plan')
                                        ->where('id',  $ids)
                                        ->update([
                                                'quarantine_room_code'  =>  $request->room,
                                                'yields'  =>  $request->input('yields'),
                                                'finished'  =>  1
                                        ]);
                        }
                } catch (\Exception  $e) {

                        Log::error('Lỗi cập nhật sự kiện:',  ['error'  =>  $e->getMessage()]);

                        return  response()->json(['status'  =>  'error',  'message'  =>  $e->getMessage()],  500);
                }


                $production  =  session('user')['production_code'];



                if (isset($request->temp)) {

                        $plan_waiting  =  $this->getPlanWaiting($production);

                        return  response()->json([
                                'plan_waiting'  =>  $plan_waiting
                        ]);
                } else {

                        $events  =  $this->getEvents($production,  $request->startDate,  $request->endDate,  true,  $this->theory);

                        return  response()->json([
                                'events'  =>  $events,
                        ]);
                }
        }


        public  function  updateOrder(Request  $request)

        {


                $data  =  $request->input('updateOrderData');
                // lấy đúng mảng
                $column_order  =  "order_by";

                if ($request->isShowLine) {

                        $column_order  =  "order_by_line";
                }



                $cases  =  [];

                $codes  =  [];


                foreach ($data  as  $item) {

                        $code  =  $item['code'];
                        // vì $item bây giờ là array thực sự
                        $orderBy  =  $item['order_by'];


                        $cases[$code]  =  $orderBy;
                        // dùng cho CASE WHEN
                        $codes[]  =  $code;
                        // dùng cho WHERE IN
                }


                $updateQuery  =  "UPDATE stage_plan SET $column_order = CASE code ";


                foreach ($cases  as  $code  =>  $orderBy) {

                        $updateQuery  .=  "WHEN '{$code}' THEN {$orderBy} ";
                }

                $updateQuery  .=  "END WHERE code IN ('"  .  implode("','",  $codes)  .  "')";




                DB::statement($updateQuery);


                return  response()->json([
                        'plan'  =>  $this->getPlanWaiting(session('user')['production_code'],  $request->isShowLine)
                ]);
        }


        public  function  createManualCampain(Request  $request)

        {



                $datas  =  $request->input('data');

                $modeCreate  =  true;

                $firstCode  =  null;


                try {

                        if ($datas  &&  count($datas)  >  0) {


                                foreach ($datas  as  $data) {

                                        if ($data['campaign_code']  !==  null) {

                                                $modeCreate  =  false;

                                                $firstCode  =   $data['campaign_code'];

                                                break;
                                        }
                                }


                                if ($modeCreate  ===  true  &&  count($datas)  >  1) {

                                        $firstCode  =  $datas[0]['predecessor_code'];

                                        if ($firstCode  ===  null) {

                                                $firstCode  =  "0_"  .  $datas[0]['code'];
                                        }

                                        $ids  =  collect($datas)->pluck('id')->toArray();

                                        DB::table('stage_plan')
                                                ->whereIn('id',  $ids)
                                                ->update([
                                                        'campaign_code'  =>  $firstCode
                                                ]);
                                } else {


                                        DB::table('stage_plan')
                                                ->where('campaign_code',  $firstCode)
                                                ->update([
                                                        'campaign_code'  =>  null
                                                ]);
                                }
                        }
                } catch (\Exception  $e) {

                        Log::error('Lỗi cập nhật sự kiện:',  ['error'  =>  $e->getMessage()]);

                        return  response()->json(['error'  =>  'Lỗi hệ thống'],  500);
                }


                return  response()->json([
                        'plan'  =>  $this->getPlanWaiting(session('user')['production_code'])
                ]);
        }


        public  function  immediately(Request  $request)

        {


                $datas  =  $request->input('data',  []);

                $modeCreate  =  true;
                // mặc định true
                try {

                        // không có dữ liệu → bỏ qua
                        if (empty($datas)) {

                                return  response()->json(['error'  =>  'No data'],  400);
                        }


                        // 1. kiểm tra nếu bất kỳ dòng nào đang có immediately = true
                        foreach ($datas  as  $data) {

                                if ($data['immediately']  ==  true) {

                                        $modeCreate  =  false;

                                        break;
                                }
                        }


                        // 2. Nếu KHÔNG có dòng nào có immediately → BẬT cho tất cả
                        $ids  =  collect($datas)->pluck('id')->filter()->toArray();

                        DB::table('stage_plan')
                                ->whereIn('id',  $ids)
                                ->update([
                                        'immediately'  =>  $modeCreate
                                ]);
                } catch (\Exception  $e) {

                        Log::error('Lỗi cập nhật sự kiện immediately:',  [
                                'error'  =>  $e->getMessage(),
                                'line'  =>  $e->getLine(),
                        ]);

                        return  response()->json(['error'  =>  'Lỗi hệ thống'],  500);
                }


                // trả lại dữ liệu mới
                return  response()->json([
                        'plan'  =>  $this->getPlanWaiting(session('user')['production_code'])
                ]);
        }


        public  function  clearningValidation(Request  $request)

        {


                $ids  =  $request->ids;


                if (is_array($ids)) {

                        $ids  =  array_values($ids);
                }


                if (empty($ids)) {

                        return  response()->json(['error'  =>  'No id provided'],  400);
                }


                try {


                        DB::table('stage_plan')
                                ->whereIn('id',  $ids)
                                ->update([
                                        'clearning_validation'  =>  DB::raw('NOT clearning_validation')
                                ]);
                } catch (\Exception  $e) {

                        Log::error('Lỗi toggle clearning_validation',  [
                                'error'  =>  $e->getMessage(),
                                'line'   =>  $e->getLine(),
                        ]);


                        return  response()->json(['error'  =>  'Lỗi hệ thống'],  500);
                }


                $events  =  $this->getEvents(session('user')['production_code'],  $request->startDate,  $request->endDate,  true,  $this->theory);

                return  response()->json([
                        'events'  =>  $events,

                ]);
        }



        public  function  cleaninglevelchange(Request  $request)

        {

                $ids  =  $request->ids;


                if (is_array($ids)) {

                        $ids  =  array_values($ids);
                }


                if (empty($ids)) {

                        return  response()->json(['error'  =>  'No id provided'],  400);
                }


                try {

                        $clearning_type  =  $request->clearning_type;

                        $this->loadOffDate('asc');


                        foreach ($ids  as  $id) {

                                // 1. Lấy thông tin hiện tại của stage_plan để xác định process_code và thời gian bắt đầu vệ sinh
                                $plan  =  DB::table('stage_plan as sp')
                                        ->leftJoin('finished_product_category as fpc',  'sp.product_caterogy_id',  '=',  'fpc.id')
                                        ->leftJoin('plan_master as pm',  'sp.plan_master_id',  '=',  'pm.id')
                                        ->where('sp.id',  $id)
                                        ->select(
                                                'sp.id',
                                                'sp.stage_code',
                                                'sp.resourceId',
                                                'sp.end',  // Thời gian kết thúc sản xuất = Bắt đầu vệ sinh
                                                'fpc.intermediate_code',
                                                'fpc.finished_product_code'
                                        )
                                        ->first();


                                if (!$plan)  continue;


                                // 2. xác định process_code để tra cứu quota
                                if ($plan->stage_code  <  7) {

                                        $process_code  =  $plan->intermediate_code  .  "_NA_"  .  $plan->resourceId;
                                } else if ($plan->stage_code  ===  7) {

                                        $process_code  =  $plan->intermediate_code  .  "_"  .  $plan->finished_product_code  .  "_"  .  $plan->resourceId;
                                } else {

                                        // Với các stage_code >= 8 (bảo trì hoặc khác), chỉ cập nhật title
                                        DB::table('stage_plan')->where('id',  $id)->update(['title_clearning'  =>  $clearning_type]);

                                        continue;
                                }


                                // 3. Tra cứu quota
                                $quota  =  DB::table('quota')
                                        ->select(
                                                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                        )
                                        ->where('process_code',  'like',  $process_code  .  '%')
                                        ->first();


                                if ($quota) {

                                        $duration  =  ($clearning_type  ===  'VS-I')  ? (float)$quota->C1_time_minutes  : (float)$quota->C2_time_minutes;


                                        // 4. Cập nhật start_clearning (bằng thời gian kết thúc sản xuất) và end_clearning
                                        $start_clearning  =  Carbon::parse($plan->end);

                                        $new_end_clearning  =  $this->addWorkingMinutes($start_clearning->copy(),  $duration,  $plan->resourceId,  $this->work_sunday);


                                        DB::table('stage_plan')
                                                ->where('id',  $id)
                                                ->update([
                                                        'title_clearning'  =>  $clearning_type,
                                                        'start_clearning'  =>  $start_clearning,
                                                        'end_clearning'    =>  $new_end_clearning
                                                ]);
                                } else {

                                        // Nếu không tìm thấy quota, chỉ cập nhật tên cấp vệ sinh
                                        DB::table('stage_plan')->where('id',  $id)->update(['title_clearning'  =>  $clearning_type]);
                                }
                        }
                } catch (\Exception  $e) {

                        Log::error('Lỗi toggle title_clearning',  [
                                'error'  =>  $e->getMessage(),
                                'line'   =>  $e->getLine(),
                        ]);


                        return  response()->json(['error'  =>  'Lỗi hệ thống'],  500);
                }


                $events  =  $this->getEvents(session('user')['production_code'],  $request->startDate,  $request->endDate,  true,  $this->theory);

                return  response()->json([
                        'events'  =>  $events,

                ]);
        }


        public  function  createManualCampainStage(Request  $request)

        {


                $datas  =  $request->input('data');

                $campaign_code  =  $datas[0]['predecessor_code']  ??  null;


                if (count($datas)  <=  1) {

                        return  response()->json([]);
                }


                try {


                        $plan_master_ids  =  collect($datas)->pluck('plan_master_id')->unique();

                        DB::table('stage_plan')
                                ->whereIn('plan_master_id',  $plan_master_ids)
                                ->update([
                                        'campaign_code'  =>  $campaign_code,
                                ]);
                } catch (\Exception  $e) {

                        Log::error('Lỗi cập nhật sự kiện:',  ['error'  =>  $e->getMessage()]);

                        return  response()->json(['error'  =>  'Lỗi hệ thống'],  500);
                }


                return  response()->json([
                        'plan'  =>  $this->getPlanWaiting(session('user')['production_code'])
                ]);
        }


        public  function  createAutoCampain(Request  $request)

        {

                $mode_date  =  'expected_date';

                $mode_order_by  =  'order_by';


                if ($request->mode  ==  'response') {

                        $mode_date  =  'responsed_date';

                        $mode_order_by  =  'order_by_line';
                }


                DB::beginTransaction();


                try {


                        // ====================================================
                        // 1. Reset campaign_code cho các plan chưa chạy
                        // ====================================================
                        DB::table('stage_plan')
                                ->where('finished',  0)
                                ->whereNull('start')
                                ->where('active',  1)
                                ->update(['campaign_code'  =>  null]);


                        // ====================================================
                        // 2. Load toàn bộ dữ liệu 1 lần
                        // ====================================================
                        $stage_plans  =  DB::table("stage_plan as sp")
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
                                ->leftJoin('plan_master',  'sp.plan_master_id',  '=',  'plan_master.id')
                                ->leftJoin('finished_product_category',  'sp.product_caterogy_id',  '=',  'finished_product_category.id')
                                ->where('sp.finished',  0)
                                ->whereNull('sp.start')
                                ->where('sp.active',  1)
                                ->orderBy("sp.$mode_order_by",  'asc')
                                ->get();


                        // ====================================================
                        // 3. Theo dõi plan_master_id đã được gán campaign
                        // ====================================================
                        $processedPlanMasters  =  collect();


                        // ====================================================
                        // 4. loop qua các stage
                        // ====================================================
                        for ($i  =  3; $i  <=  7; $i++) {


                                $product_code  =  ($i  <=  6)  ?  'intermediate_code'  :  'finished_product_code';


                                // ------------------------------------------------
                                // 4.1. Lấy stage hiện tại + CHƯA xử lý
                                // ------------------------------------------------
                                $stage_plans_stage  =  $stage_plans
                                        ->where('stage_code',  $i)
                                        ->whereNotIn('plan_master_id',  $processedPlanMasters);


                                if ($stage_plans_stage->isEmpty()) {

                                        continue;
                                }


                                // ------------------------------------------------
                                // 4.2. Filter code_val an toàn
                                // ------------------------------------------------
                                $stage_plans_stage  =  $stage_plans_stage->filter(function ($item) {

                                        if ($item->code_val  ===  null) {

                                                return  true;
                                        }


                                        $parts  =  explode('_',  $item->code_val);

                                        return  isset($parts[1])  && (int)$parts[1]  >  1;
                                });


                                if ($stage_plans_stage->isEmpty()) {

                                        continue;
                                }


                                // ------------------------------------------------
                                // 4.3. Group dữ liệu
                                // ------------------------------------------------
                                $groups  =  $stage_plans_stage
                                        ->groupBy(function ($item)  use ($product_code,  $mode_date) {


                                                /// đanh dấu nếu muốn tách lô thẩm định 2 và 3
                                                // if ($item->code_val === null || explode('_', $item->code_val)[0] > 1) {
                                                //         $cvflag = 'null';
                                                // } else {
                                                //         $cvflag = 1; //explode('_', $item->code_val)[0];
                                                // }

                                                return  $item->$mode_date  .  '|'  .  $item->$product_code;
                                                //. '|' . $cvFlag;
                                        })
                                        ->filter(fn($group)  =>  $group->count()  >  1);


                                if ($groups->isEmpty()) {

                                        continue;
                                }


                                // ------------------------------------------------
                                // 4.4. Tạo campaign
                                // ------------------------------------------------
                                $updates  =  [];


                                foreach ($groups  as  $groupKey  =>  $items) {


                                        [,  $code]  =  explode('|',  $groupKey);


                                        $quota  =  DB::table('quota')
                                                ->where($product_code,  $code)
                                                ->where('stage_code',  $i)
                                                ->first();


                                        $maxBatch  =  $quota->maxofbatch_campaign  ??  0;

                                        if ($maxBatch  <=  1) {

                                                continue;
                                        }


                                        $items  =  $items->values();

                                        $countInBatch  =  0;


                                        $campaignCode  =  $items[0]->predecessor_code  ??  ('0_'  .  $items[0]->code);


                                        foreach ($items  as  $item) {


                                                if ($countInBatch  >=  $maxBatch) {

                                                        $campaignCode  =  $item->predecessor_code  ??  ('0_'  .  $item->code);

                                                        $countInBatch  =  0;
                                                }


                                                $updates[]  =  [
                                                        'plan_master_id'  =>  $item->plan_master_id,
                                                        'campaign_code'   =>  $campaignCode,
                                                ];


                                                $countInBatch++;
                                        }
                                }


                                // ------------------------------------------------
                                // 4.5. update db + đánh dấu đã xử lý
                                // ------------------------------------------------
                                if (!empty($updates)) {


                                        $plan_master_ids  =  collect($updates)->pluck('plan_master_id')->unique()->implode(',');




                                        $caseSql  =  "CASE plan_master_id ";


                                        foreach ($updates  as  $row) {

                                                $caseSql  .=  "WHEN {$row['plan_master_id']} THEN '{$row['campaign_code']}' ";
                                        }

                                        $caseSql  .=  "END";




                                        DB::update("
                                        UPDATE stage_plan
                                        SET campaign_code = $caseSql
                                        WHERE plan_master_id IN ($plan_master_ids)
                                ");


                                        // đánh dấu đã xử lý
                                        $processedPlanMasters  =  $processedPlanMasters
                                                ->merge(collect($updates)->pluck('plan_master_id'))
                                                ->unique();
                                }
                        }


                        DB::commit();


                        return  response()->json([
                                'plan'  =>  $this->getPlanWaiting(session('user')['production_code'])
                        ]);
                } catch (\Exception  $e) {

                        DB::rollBack();

                        Log::error('Lỗi createAutoCampain',  ['error'  =>  $e->getMessage()]);

                        return  response()->json(['error'  =>  'Lỗi hệ thống'],  500);
                }
        }


        public  function  DeleteAutoCampain(Request  $request)

        {


                $plan_master_ids  =  collect($request->data)->pluck('plan_master_id')->unique();

                DB::table('stage_plan')
                        ->where('finished',  0)
                        ->where('start',  null)
                        ->where('active',  1)
                        ->whereIn('plan_master_id',  $plan_master_ids)
                        ->update(['campaign_code'  =>  null]);


                return  response()->json([
                        'plan'  =>  $this->getPlanWaiting(session('user')['production_code'])
                ]);
        }


        public  function  createOrderPlan(Request  $request)

        {


                try {

                        DB::transaction(function ()  use ($request) {

                                $planMasterId  =  DB::table('plan_master')->insertGetId([
                                        'plan_list_id'         =>  0,
                                        'product_caterogy_id'  =>  0,
                                        'level'                =>  4,
                                        'batch'                =>  $request->batch,
                                        'expected_date'        =>  '2025-01-01',
                                        'is_val'               =>  false,
                                        'only_parkaging'       =>  false,
                                        'percent_parkaging'    =>  1,
                                        'note'                 =>  $request->note  ??  "NA",
                                        'deparment_code'       =>  session('user')['production_code'],
                                        'created_at'           =>  now(),
                                        'prepared_by'          =>  session('user')['fullName'],
                                ]);

                                $number_of_batch  =  $request->number_of_batch  ??  1;

                                for ($i  =  1; $i   <=  $number_of_batch; $i++) {

                                        // Insert stage_plan và gán plan_master_id
                                        DB::table('stage_plan')->insert([
                                                'plan_list_id'         =>  0,
                                                'product_caterogy_id'  =>  0,
                                                'plan_master_id'       =>  $planMasterId,
                                                'schedualed'           =>  0,
                                                'finished'             =>  0,
                                                'active'               =>  1,
                                                'stage_code'           =>  9,
                                                'deparment_code'       =>  session('user')['production_code'],
                                                'title'                =>  $request->title,
                                                'yields'               =>  $request->checkedClearning  ?  0  :  -1,
                                                'created_by'           =>  session('user')['fullName'],
                                                'created_date'         =>  now(),
                                        ]);
                                }
                        });
                } catch (\Exception  $e) {

                        Log::error('Lỗi cập nhật sự kiện:',  ['error'  =>  $e->getMessage()]);

                        return  response()->json(['error'  =>  'Lỗi hệ thống'],  500);
                }


                return  response()->json([
                        'plan'  =>  $this->getPlanWaiting(session('user')['production_code'])
                ]);
        }


        public  function  DeActiveOrderPlan(Request  $request)

        {


                try {

                        $ids  =  collect($request->all())->pluck('id');
                        // lấy ra danh sách id

                        DB::table('stage_plan')
                                ->whereIn('id',  $ids)
                                ->update([
                                        'active'         =>  0,
                                        'finished_by'    =>  session('user')['fullName']  ??  'System',
                                        'finished_date'  =>  now(),
                                ]);
                } catch (\Exception  $e) {

                        Log::error('Lỗi cập nhật sự kiện:',  ['error'  =>  $e->getMessage()]);

                        return  response()->json(['error'  =>  'Lỗi hệ thống'],  500);
                }


                return  response()->json([
                        'plan'  =>  $this->getPlanWaiting(session('user')['production_code'])
                ]);
        }


        public  function  Sorted(Request  $request)

        {


                if ($request->sortType  ===  'response') {


                        if (
                                $request->filled('plan_master_ids')  &&
                                is_array($request->plan_master_ids)  &&
                                count($request->plan_master_ids)  >  0  &&
                                $request->filled('response_date')
                        ) {

                                DB::table('plan_master')
                                        ->whereIn('id',  $request->plan_master_ids)
                                        ->update([
                                                'responsed_date'  =>  $request->response_date
                                        ]);
                        }


                        $sortType  =  'responsed_date';
                } else {

                        $sortType  =  'expected_date';
                }



                $stageCode  =   $request->stage_code  ??  3;


                // Danh sách cấu hình sắp xếp
                $stages  =  [
                        ['codes'  =>  [1,  2,  3],  'orderBy'  =>  [
                                [$sortType,  'asc'],
                                ['level',  'asc'],
                                [DB::raw('batch + 0'),  'asc']
                        ]],
                        ['codes'  =>  [4],  'orderBy'  =>  [
                                ['intermediate_category.quarantine_blending',  'asc'],
                                [$sortType,  'asc'],
                                ['level',  'asc'],
                                [DB::raw('batch + 0'),  'asc']
                        ]],
                        ['codes'  =>  [5],  'orderBy'  =>  [
                                ['intermediate_category.quarantine_forming',  'asc'],
                                [$sortType,  'asc'],
                                ['level',  'asc'],
                                [DB::raw('batch + 0'),  'asc']
                        ]],
                        ['codes'  =>  [6],  'orderBy'  =>  [
                                ['intermediate_category.quarantine_coating',  'asc'],
                                [$sortType,  'asc'],
                                ['level',  'asc'],
                                [DB::raw('batch + 0'),  'asc']
                        ]],
                ];


                // Tìm stage group tương ứng với stage_code được gửi lên
                $stageGroup  =  collect($stages)->first(fn($group)  =>  in_array($stageCode,  $group['codes']));


                if (!$stageGroup) {

                        return  response()->json(['error'  =>  'Stage code không hợp lệ!'],  400);
                }


                // Xây query cho plan_master
                $query  =  DB::table('plan_master')
                        ->leftJoin('finished_product_category',  'plan_master.product_caterogy_id',  'finished_product_category.id')
                        ->leftJoin('intermediate_category',  'finished_product_category.intermediate_code',  'intermediate_category.intermediate_code');


                // thêm thứ tự sắp xếp tương ứng
                foreach ($stageGroup['orderBy']  as  [$column,  $direction]) {

                        $query->orderBy($column,  $direction);
                }


                // Lấy danh sách ID
                $planMasters  =  $query->pluck('plan_master.id');


                if ($planMasters->isEmpty()) {

                        return  response()->json(['message'  =>  'Không có kế hoạch để sắp xếp.']);
                }


                // Cập nhật order_by cho stage được chọn
                DB::table('stage_plan')
                        ->whereNull('start')
                        ->where('stage_code',  $stageCode)
                        ->where('finished',  0)
                        ->where('active',  1)
                        ->where('deparment_code',  session('user')['production_code'])
                        ->whereIn('plan_master_id',  $planMasters)
                        ->orderByRaw("FIELD(plan_master_id, "  .  implode(',',  $planMasters->toArray())  .  ")")
                        ->update([
                                'order_by'  =>  DB::raw("FIELD(plan_master_id, "  .  implode(',',  $planMasters->toArray())  .  ")")
                        ]);


                return  response()->json([
                        'plan'  =>  $this->getPlanWaiting(session('user')['production_code']),
                        'message'  =>  "Đã sắp xếp lại kế hoạch cho stage {$stageCode}."
                ]);
        }


        public  function  submit(Request  $request)

        {

                // 1️⃣ Lấy danh sách các dòng sẽ update
                $updatedRows  =  DB::table('stage_plan')
                        ->whereNotNull('start')
                        ->where('finished',  0)
                        ->where('active',  1)
                        ->where('submit',  0)
                        ->where('deparment_code',  session('user')['production_code'])
                        ->get();


                if ($updatedRows->isEmpty()) {

                        return  response()->json(['message'  =>  'Không có lịch mới để submit!']);
                }


                // 2️⃣ Update submit = 1
                DB::table('stage_plan')
                        ->whereIn('id',  $updatedRows->pluck('id'))
                        ->update(['submit'  =>  1]);


                // 3️⃣ Insert log cho từng dòng
                $historyData  =  $updatedRows->map(function ($row) {

                        $maxVersion  =  DB::table('stage_plan_history')
                                ->where('stage_plan_id',  $row->id)
                                ->max('version')  ??  0;


                        return  [
                                'stage_plan_id'  =>  $row->id,
                                'plan_list_id'  =>  $row->plan_list_id,
                                'plan_master_id'  =>  $row->plan_master_id,
                                'product_caterogy_id'  =>  $row->product_caterogy_id,
                                'campaign_code'  =>  $row->campaign_code,
                                'code'  =>  $row->code,
                                'order_by'  =>  $row->order_by,
                                'schedualed'  =>  $row->schedualed,
                                'stage_code'  =>  $row->stage_code,
                                'title'  =>  $row->title,
                                'start'  =>  $row->start,
                                'end'  =>  $row->end,
                                'resourceId'  =>  $row->resourceId,
                                'title_clearning'  =>  $row->title_clearning,
                                'start_clearning'  =>  $row->start_clearning,
                                'end_clearning'  =>  $row->end_clearning,
                                'tank'  =>  $row->tank,
                                'keep_dry'  =>  $row->keep_dry,
                                'AHU_group'  =>  $row->AHU_group,
                                'schedualed_by'  =>  $row->schedualed_by,
                                'schedualed_at'  =>  $row->schedualed_at,
                                'version'  =>  $maxVersion  +  1,
                                'note'  =>  $row->note,
                                'deparment_code'  =>  session('user')['production_code'],
                                'type_of_change'  =>  "Tạo Mới Lịch",
                                'created_date'  =>  now(),
                                'created_by'  =>  session('user')['fullName'],
                        ];
                });


                // 🔹 Chia nhỏ insert để tránh lỗi 1390
                $historyData->chunk(500)->each(function ($chunk) {

                        DB::table('stage_plan_history')->insert($chunk->toArray());
                });



                /// Gửi thông Báo
                $senderName  =  session('user')['fullName'];

                $productionName  =  session('user')['production_name'];

                $sendDate  =  now()->format('d/m/Y H:i');

                $message  =  "{$senderName} đã Submit Lịch Sản Xuất ngày {$sendDate} PX {$productionName}";

                $targetUrl  =  route('pages.Schedual.index');

                // Logic lọc người nhận: Không gửi cho 4 phân xưởng còn lại nếu người gửi thuộc 1 trong 5 phân xưởng
                $workshops  =  ['PXV1',  'PXV2',  'PXDN',  'PXTN',  'PXVH'];

                $myWorkshop  =  session('user')['production_code'];

                $targetUserIds  =  'all';


                if (in_array($myWorkshop,  $workshops)) {

                        $excludeWorkshops  =  array_diff($workshops,  [$myWorkshop]);

                        $targetUserIds  =  DB::table('user_management')
                                ->where('isActive',  1)
                                ->whereNotIn('deparment',  $excludeWorkshops)
                                ->pluck('id')
                                ->toArray();
                }


                \App\Http\Controllers\General\NotificationController::sendNotification(
                        $message,
                        'Submit Lịch Sản Xuất',
                        null,
                        $targetUserIds,
                        [],
                        $targetUrl
                );


                return  response()->json(['message'  =>  "Đã submit "  .  $updatedRows->count()  .  " lịch."]);
        }


        public  function  accpectQuarantine(Request  $request)

        {

                //Log::info ($request->all());
                $items  =  collect($request->input('ids'));


                try {


                        foreach ($items  as  $item) {

                                $rowId  =  explode('-',  $item['id'])[0];
                                // lấy id trước dấu - 
                                DB::table('stage_plan')
                                        ->where('id',  $rowId)
                                        ->where('finished',  0)
                                        ->update([
                                                'accept_quarantine'  =>  1,
                                        ]);
                        }
                } catch (\Exception  $e) {

                        Log::error('Lỗi cập nhật sự kiện:',  ['error'  =>  $e->getMessage()]);

                        return  response()->json(['status'  =>  'error',  'message'  =>  $e->getMessage()],  500);
                }


                $events  =  $this->getEvents(session('user')['production_code'],  $request->startDate,  $request->endDate,  true,  $this->theory);


                return  response()->json([
                        'events'  =>  $events

                ]);
        }


        public  function  required_room(Request  $request)

        {

                //Log::info ($request->all());
                $campaign_code  =  DB::table('stage_plan')->where('id',  $request->stage_plan_id)->value('campaign_code');

                $room  =  DB::table('room')->where('code',  $request->room_code)->first();

                //$room_id = db::table('room')->where ('code', $request->room_code)->value('id');
                //log::info (['request' => $request->all(),'stage_code' => $stage_code]);
                if ($campaign_code  &&  !$request->checked) {

                        DB::table('stage_plan')
                                ->where('id',  $request->stage_plan_id)
                                ->update(['required_room_code'  =>  null]);
                } else if ($campaign_code  &&  $request->checked) {


                        $plans  =  DB::table('stage_plan')
                                ->leftJoin('finished_product_category',  'finished_product_category.id',  'stage_plan.product_caterogy_id')
                                ->select(
                                        'stage_plan.id',
                                        'stage_plan.stage_code',
                                        'finished_product_category.intermediate_code',
                                        'finished_product_category.finished_product_code'
                                )
                                ->where('stage_plan.campaign_code',  $campaign_code)
                                ->where('stage_plan.stage_code',  $room->stage_code)
                                ->get();


                        foreach ($plans  as  $p) {


                                // tạo process_code đúng tiêu chí
                                if ($p->stage_code  <  7) {

                                        //$process_code = $p->intermediate_code . "_NA_" . $room_id;
                                        $quota  =  DB::table('quota')
                                                ->where('room_id',  $room->id)
                                                ->where('intermediate_code',  $p->intermediate_code)
                                                ->first();
                                } else {

                                        //$process_code = $p->intermediate_code . "_" . $p->finished_product_code . "_" . $room_id;
                                        $quota  =  DB::table('quota')
                                                ->where('room_id',  $room->id)
                                                ->where('intermediate_code',  $p->intermediate_code)
                                                ->where('finished_product_code',  $p->finished_product_code)
                                                ->first();
                                }




                                if (!$quota) {

                                        return  response()->json([
                                                'status'  =>  'error',
                                                'message'  =>  "Lô ID {$p->id} không có định mức cho phòng {$room->id}. Không thể yêu cầu phòng!"
                                        ],  422);
                                }
                        }


                        DB::table('stage_plan')
                                ->where('campaign_code',  $campaign_code)
                                ->where('stage_plan.stage_code',  $room->stage_code)
                                ->update(['required_room_code'  =>  $request->room_code]);
                } else {

                        DB::table('stage_plan')
                                ->where('id',  $request->stage_plan_id)
                                ->update(['required_room_code'  =>  $request->checked  ?  $request->room_code  :  null]);
                }


                return  response()->json([
                        'plan'  =>  $this->getPlanWaiting(session('user')['production_code'])
                ]);
        }


        public  function  change_sheet(Request  $request)

        {



                $roomCode  =  $request->room_code;

                $sheet     =  $request->sheet;
                // sheet_1 | sheet_2 | sheet_3 | sheet_regular
                $checked   = (int) $request->checked;
                // 1 | 0

                // validate sheet name
                $validSheets  =  ['sheet_1',  'sheet_2',  'sheet_3',  'sheet_regular'];

                if (!in_array($sheet,  $validSheets)) {

                        return  response()->json(['error'  =>  'Invalid sheet'],  400);
                }


                // dữ liệu update
                $update  =  [
                        $sheet  =>  $checked
                ];


                // 🔥 case 1: bật hành chính
                if ($sheet  ===  'sheet_regular'  &&  $checked  ===  1) {

                        $update['sheet_1']  =  0;

                        $update['sheet_2']  =  0;

                        $update['sheet_3']  =  0;
                }


                // 🔥 case 2: bật ca 1 / 2 / 3
                if (in_array($sheet,  ['sheet_1',  'sheet_2',  'sheet_3'])  &&  $checked  ===  1) {

                        $update['sheet_regular']  =  0;
                }


                DB::table('room')
                        ->where('code',  $roomCode)
                        ->update($update);


                return  response()->json([
                        'success'  =>  true,
                        'update'   =>  $update
                ]);
        }


        public  function  backup_schedualer()

        {

                $bkcCode  =  Carbon::now()->format('d/m/Y_H:i');


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
                                        DB::raw("'"  .  $bkcCode  .  "' as bkc_code"),
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
                                ->where('finished',  0)
                                ->where('deparment_code',  session('user')['production_code'])
                );

                return  response()->json([
                        'bkcCode'  =>  $bkcCode
                ]);
        }


        public  function  restore_schedualer(Request  $request)

        {

                $bkcCode  =  $request->input('bkc_code');
                // ⚠️ dùng đúng key axios gửi

                if (!$bkcCode) {

                        Log::warning('Restore scheduler failed: missing bkc_code',  [
                                'payload'  =>  $request->all()
                        ]);


                        return  response()->json([
                                'success'  =>  false,
                                'message'  =>  'Thiếu mã bản sao lưu'
                        ],  422);
                }


                try {

                        DB::beginTransaction();


                        $affected  =  DB::table('stage_plan as sp')
                                ->join('stage_plan_bkc as bkc',  'bkc.stage_plan_id',  '=',  'sp.id')
                                ->where('sp.finished',  0)
                                ->where('sp.deparment_code',  session('user')['production_code'])
                                ->where('bkc.bkc_code',  $bkcCode)
                                ->update([
                                        'sp.start'                   =>  DB::raw('bkc.start'),
                                        'sp.end'                     =>  DB::raw('bkc.end'),
                                        'sp.resourceId'              =>  DB::raw('bkc.resourceId'),
                                        'sp.start_clearning'         =>  DB::raw('bkc.start_clearning'),
                                        'sp.end_clearning'           =>  DB::raw('bkc.end_clearning'),
                                        'sp.schedualed'              =>  DB::raw('bkc.schedualed'),
                                        'sp.order_by'                =>  DB::raw('bkc.order_by'),
                                        'sp.order_by_line'           =>  DB::raw('bkc.order_by_line'),
                                        'sp.campaign_code'               =>  DB::raw('bkc.campaign_code'),
                                        'sp.immediately'                    =>  DB::raw('bkc.immediately'),

                                ]);


                        DB::commit();


                        return  response()->json([
                                'success'  =>  true,
                                'affected'  =>  $affected
                        ]);
                } catch (\Throwable  $e) {


                        DB::rollBack();


                        Log::error('Restore scheduler error',  [
                                'bkc_code'  =>  $bkcCode,
                                'message'  =>  $e->getMessage(),
                                'trace'  =>  $e->getTraceAsString()
                        ]);


                        return  response()->json([
                                'success'  =>  false,
                                'message'  =>  'Khôi phục thất bại, vui lòng kiểm tra log'
                        ],  500);
                }
        }


        protected  function  skipOffTime(Carbon  $time,  array  $offDateList,  ?int  $roomId  =  null): Carbon

        {


                $busyList  =  [];

                if ($roomId) {

                        $busyList  =  $this->loadRoomAvailability('asc',  $roomId);
                }


                foreach ($offDateList  as  $off) {


                        // đảm bảo kiểu Carbon
                        $start  =  $off['start']  instanceof  Carbon
                                ?  $off['start']
                                :  Carbon::parse($off['start']);


                        $end  =  $off['end']  instanceof  Carbon
                                ?  $off['end']
                                :  Carbon::parse($off['end']);


                        // nếu time nằm trong khoảng off
                        if ($time->gte($start)  &&  $time->lt($end)) {

                                return  $end->copy();
                                // nhảy tới cuối off
                        }


                        // vì offdatelist đã sort theo start
                        if ($time->lt($start)) {

                                break;
                        }
                }


                if (!empty($busyList)) {

                        foreach ($busyList  as  $off) {


                                // đảm bảo kiểu Carbon
                                $start  =  $off['start']  instanceof  Carbon
                                        ?  $off['start']
                                        :  Carbon::parse($off['start']);


                                $end  =  $off['end']  instanceof  Carbon
                                        ?  $off['end']
                                        :  Carbon::parse($off['end']);


                                // nếu time nằm trong khoảng off
                                if ($time->gte($start)  &&  $time->lt($end)) {

                                        return  $end->copy();
                                        // nhảy tới cuối off
                                }


                                // vì offdatelist đã sort theo start
                                if ($time->lt($start)) {

                                        break;
                                }
                        }
                }



                return  $time;
        }


        protected  function  loadRoomAvailability(string  $sort,  int  $roomId)

        {


                $this->roomAvailability[$roomId]  =  [];



                $notCampaign  =  DB::table('stage_plan')
                        ->where('resourceId',  $roomId)
                        ->where('finished',  0)
                        ->whereNull('campaign_code')
                        ->where(function ($q) {

                                $q->where('end',  '>=',  now())
                                        ->orWhere('end_clearning',  '>=',  now());
                        })
                        ->select(
                                'start',
                                DB::raw('COALESCE(end_clearning, end) as end')

                        )
                        ->orderBy('start')
                        ->get();


                $campaign  =  DB::table('stage_plan')
                        ->where('finished',  0)
                        ->where('resourceId',  $roomId)
                        ->whereNotNull('campaign_code')
                        ->where(function ($q) {

                                $q->where('end',  '>=',  now())
                                        ->orWhere('end_clearning',  '>=',  now());
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



                $blocks  =  collect()
                        ->merge($notCampaign)
                        ->merge($campaign)
                        ->map(function ($row) {

                                return  [
                                        'start'  =>  Carbon::parse($row->start),
                                        'end'    =>  Carbon::parse($row->end),
                                ];
                        })
                        ->sortBy('start')
                        ->values();




                $merged  =  [];


                foreach ($blocks  as  $row) {


                        if (empty($merged)) {

                                $merged[]  =  $row;

                                continue;
                        }


                        $lastIndex  =  count($merged)  -  1;

                        $last       =  $merged[$lastIndex];


                        if ($row['start']->lte($last['end'])) {

                                if ($row['end']->gt($last['end'])) {

                                        $merged[$lastIndex]['end']  =  $row['end'];
                                }
                        } else {

                                $merged[]  =  $row;
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

                $this->roomAvailability[$roomId]  =  $merged;


                // ===============================
                // 3. sắp xếp theo $sort
                // ===============================
                if (!empty($this->roomAvailability[$roomId])) {

                        $this->roomAvailability[$roomId]  =  collect($this->roomAvailability[$roomId])
                                ->sortBy('start',  SORT_REGULAR,  $sort  ===  'desc')
                                ->values()
                                ->toArray();
                }
        }


        protected  function  loadOffDate(string  $sort)

        {


                $this->offDate  =  [];


                if (!empty($this->selectedDates)  &&  is_array($this->selectedDates)) {


                        // 2.1 Parse + sort ngày (chỉ lấy date)
                        $dates  =  collect($this->selectedDates)
                                ->map(fn($d)  =>  Carbon::parse($d)->startOfDay())
                                ->sort()
                                ->values();


                        $ranges  =  [];


                        $currentStart  =  null;

                        $currentEnd    =  null;

                        $prevDate      =  null;


                        // 2.2 duyệt từng ngày
                        foreach ($dates  as  $date) {


                                // Quy ước off: 06:00 hôm nay -> 06:00 hôm sau
                                $start  =  $date->copy()->setTime(6,  0,  0);

                                $end    =  $date->copy()->addDay()->setTime(6,  0,  0);


                                // khoảng đầu tiên
                                if ($currentStart  ===  null) {

                                        $currentStart  =  $start;

                                        $currentEnd    =  $end;

                                        $prevDate      =  $date;

                                        continue;
                                }


                                // ✅ điều kiện gộp chuẩn: ngày hiện tại = ngày trước + 1
                                if ($date->equalTo($prevDate->copy()->addDay())) {

                                        // Kéo dài end
                                        $currentEnd  =  $end;
                                } else {

                                        // Lưu khoảng cũ
                                        $ranges[]  =  [
                                                'start'  =>  $currentStart,
                                                'end'    =>  $currentEnd,
                                        ];


                                        // Bắt đầu khoảng mới
                                        $currentStart  =  $start;

                                        $currentEnd    =  $end;
                                }


                                $prevDate  =  $date;
                        }


                        // 2.3 push khoảng cuối cùng
                        if ($currentStart  !==  null) {

                                $ranges[]  =  [
                                        'start'  =>  $currentStart,
                                        'end'    =>  $currentEnd,
                                ];
                        }


                        $this->offDate  =  $ranges;
                }


                if (!empty($this->offDate)) {

                        $this->offDate  =  collect($this->offDate)
                                ->sortBy('start',  SORT_REGULAR,  $sort  ===  'desc')
                                ->values()
                                ->toArray();
                }
        }


        protected  function  findEarliestSlot2($roomId,  $Earliest,  $intervalTime,  $C2_time_minutes,  $requireTank  =  0,  $requireAHU  =  0,  $stage_plan_table  =  'stage_plan',  $maxTank  =  1,  $tankInterval  =  60)

        {

                $this->loadRoomAvailability('asc',  $roomId);


                if (!isset($this->roomAvailability[$roomId])) {

                        $this->roomAvailability[$roomId]  =  [];
                }


                $busyList  =  $this->roomAvailability[$roomId];


                $offDateList  =  $this->offDate  ??  [];

                $current_start  =  Carbon::parse($Earliest);


                $current_start  =  $this->skipOffTime($current_start,  $offDateList);

                // =========================================================
                foreach ($busyList  as  $busy) {


                        // ==== xét gap trước busy ====
                        if ($current_start->lt($busy['start'])) {


                                $gap  =  $current_start->diffInMinutes($busy['start']);

                                $need  =  $intervalTime  +  $C2_time_minutes;


                                // ---- tính offTime kiểu expand ----
                                $offTime  =  0;


                                do {

                                        $current_end  =  $current_start->copy()->addMinutes($need  +  $offTime);

                                        $newOffTime  =  0;


                                        foreach ($offDateList  as  $off) {

                                                if ($off['end']  <=  $current_start  ||  $off['start']  >=  $current_end) {

                                                        continue;
                                                }


                                                $overlapStart  =  $off['start']->greaterThan($current_start)
                                                        ?  $off['start']
                                                        :  $current_start;


                                                $overlapEnd  =  $off['end']->lessThan($current_end)
                                                        ?  $off['end']
                                                        :  $current_end;


                                                $newOffTime  +=  $overlapStart->diffInMinutes($overlapEnd);
                                        }



                                        $changed  =  ($newOffTime  >  $offTime);

                                        $offTime  =  $newOffTime;
                                } while ($changed);


                                if ($gap  >=  $need  +  $offTime) {

                                        return  $current_start->copy();
                                }
                        }


                        // ==== nếu rơi vào busy → nhảy qua ====
                        if ($current_start->lt($busy['end'])) {

                                $current_start  =  $busy['end']->copy();

                                $current_start  =  $this->skipOffTime($current_start,  $offDateList);
                        }
                }


                // ==== sau tất cả busy ====
                return  $current_start->copy();
        }


        protected  function  saveSchedule($first_in_campaign,  $stageId,  $roomId,   $start,   $end,  $start_clearning,   $endCleaning,  string  $cleaningType,  bool  $direction)

        {


                DB::transaction(function ()  use ($first_in_campaign,  $stageId,  $roomId,  $start,  $end,  $start_clearning,   $endCleaning,  $cleaningType,  $direction) {


                        if ($cleaningType  ==  2) {

                                $titleCleaning  =  "VS-II";
                        } else {

                                $titleCleaning  =  "VS-I";
                        }

                        $AHU_group   =  DB::table('room')->where('id',  $roomId)->value('AHU_group')  ??  0;


                        $code  =  DB::table('stage_plan')->where('id',  $stageId)->value('code');


                        $offDays  =  DB::table('off_days')
                                ->whereDate('off_date',  '<=',  $start)
                                ->pluck('off_date')
                                ->toArray();


                        $receiveDate  =  Carbon::parse($start)->subDay();


                        while (in_array($receiveDate->toDateString(),  $offDays)) {

                                $receiveDate->subDay();
                        }


                        $receiveDate  =  $receiveDate->toDateString();


                        DB::table('stage_plan')
                                ->where('id',  $stageId)
                                //->where('code',  $code)
                                ->update([
                                        'first_in_campaign'      =>  $first_in_campaign  ??  0,
                                        'resourceId'             =>  $roomId,
                                        'start'                  =>  $start,
                                        'end'                    =>  $end,
                                        'start_clearning'        =>  $start_clearning,
                                        'end_clearning'          =>  $endCleaning,
                                        'title_clearning'        =>  $titleCleaning,
                                        'scheduling_direction'   =>  $direction,
                                        'AHU_group'              =>  $AHU_group  ??  null,
                                        'schedualed_at'          =>  now(),
                                        'receive_packaging_date'    => DB::raw("CASE WHEN received = 0 THEN '$receiveDate' ELSE receive_packaging_date END"),
                                        'receive_second_packaging_date' => DB::raw("CASE WHEN received_second_packaging = 0 THEN '$receiveDate' ELSE receive_second_packaging_date END")
                                ]);



                        $submit  =  DB::table('stage_plan')->where('id',  $stageId)->value('submit');


                        // nếu muốn log cả cleaning vào room_schedule thì thêm block này:
                        if ($submit  ==  1) {

                                $this->syncPackagingDate($stageId,  $receiveDate,  0);
                                $this->syncPackagingDate($stageId,  $receiveDate,  1);

                                DB::table('stage_plan_history')
                                        ->insert([
                                                'stage_plan_id'    =>  $stageId,
                                                'version'          => (DB::table('stage_plan_history')->where('stage_plan_id',  $stageId)->max('version')  ??  0)  +  1,
                                                'start'            =>  $start,
                                                'end'              =>  $end,
                                                'resourceId'       =>  $roomId,
                                                'schedualed_by'    =>  session('user')['fullName'],
                                                'schedualed_at'    =>  now(),
                                                'deparment_code'   =>  session('user')['production_code'],
                                                'type_of_change'   =>  $this->reason  ??  "Lập Lịch Tự Động",
                                        ]);
                        }
                });
        }


        public  function  scheduleAll(Request  $request)

        {


                $this->selectedDates  =  $request->selectedDates  ??  [];


                $this->work_sunday  =  $request->work_sunday  ??  false;

                $this->reason  =  $request->reason  ??  "NA";

                $this->prev_orderBy  =   $request->prev_orderBy  ??  false;

                $this->loadOffDate('asc');

                $today  =  Carbon::now()->toDateString();

                $start_date  =  Carbon::createFromFormat('Y-m-d',  $request->start_date  ??  $today)->setTime(6,  0,  0);


                /// chạy công đoạn cân nl
                if ($request->selectedStep  ==  "CNL") {

                        $this->scheduleWeightStage($start_date);

                        return  response()->json([]);
                }


                $Step  =  [
                        "PC"  =>  3,
                        "THT"  =>  4,
                        "ĐH"  =>  5,
                        "BP"  =>  6,
                        "ĐG"  =>  7,
                ];


                $selectedStep  =  $Step[$request->selectedStep  ??  "ĐG"];

                $this->max_Step  =   $selectedStep;



                $stageCodes  =  DB::table("stage_plan as sp")
                        ->distinct()
                        ->where('sp.stage_code',  ">=",  3)
                        ->where('sp.stage_code',  "<=",  $selectedStep)
                        ->where('sp.deparment_code',  session('user')['production_code'])
                        ->orderBy('sp.stage_code')
                        ->pluck('sp.stage_code');


                $waite_time  =  [];

                $waite_time[3]  =  ['waite_time_nomal_batch'  =>  0,  'waite_time_val_batch'    =>  0,];

                $waite_time[4]  =  ['waite_time_nomal_batch'  => (($request->wt_bleding  ??  0)  *  24  *  60),  'waite_time_val_batch'    => (($request->wt_bleding_val  ??  1)  *  24  *  60)];

                $waite_time[5]  =  ['waite_time_nomal_batch'  => (($request->wt_forming  ??  0)  *  24  *  60),  'waite_time_val_batch'    => (($request->wt_forming_val  ??  1)  *  24  *  60)];

                $waite_time[6]  =  ['waite_time_nomal_batch'  => (($request->wt_coating  ??  0)  *  24  *  60),  'waite_time_val_batch'    => (($request->wt_coating_val  ??  1)  *  24  *  60)];

                $waite_time[7]  =  ['waite_time_nomal_batch'  => (($request->wt_blitering  ??  0)  *  24  *  60),  'waite_time_val_batch'    => (($request->wt_blitering_val  ??  5)  *  24  *  60)];


                //$this->schedulestartbackward($start_date, $waite_time);

                /// chạy theo line
                if ($request->runType  ==  'line') {

                        $stage_code_line  =  DB::table("room")->where('code',  $request->lines)->value('stage_code');

                        $this->scheduleLine($request->lines,  $request->stage_plan_ids,  $stage_code_line,  0,  0,  $start_date);

                        return  response()->json([]);
                }


                /////bán thành phầm
                for ($i  =  $selectedStep; $i  >=  3; $i--) {

                        $this->scheduleIntermediate($i,  0,  0,  $start_date);
                }


                /////sản phẩm nhạy cảm
                for ($i  =  3; $i  <=  $selectedStep; $i++) {

                        $this->scheduleSensitiveProduct($i,  0,  0,  $start_date);
                }


                /// chạy theo stage_z
                foreach ($stageCodes  as  $i) {

                        $waite_time_nomal_batch  =  0;

                        $waite_time_val_batch    =  0;

                        switch ($i) {

                                case  3:

                                        $waite_time_nomal_batch  =  0;

                                        $waite_time_val_batch    =  0;


                                        break;

                                case  4:

                                        $waite_time_nomal_batch  =  ($request->wt_bleding  ??  0)   *  24  *  60;

                                        $waite_time_val_batch    =  ($request->wt_bleding_val  ??  1)  *  24  *  60;


                                        break;


                                case  5:

                                        $waite_time_nomal_batch  =  ($request->wt_forming  ??  0)  *  24  *  60;

                                        $waite_time_val_batch    =  ($request->wt_forming_val  ??  5)  *  24  *  60;


                                        break;


                                case  6:

                                        $waite_time_nomal_batch  =  ($request->wt_coating  ??  0)  *  24  *  60;

                                        $waite_time_val_batch    =  ($request->wt_coating_val  ??  5)  *  24  *  60;


                                        break;


                                case  7:
                                        // Đóng gói
                                        $waite_time_nomal_batch  =  ($request->wt_blitering  ??  0)  *  24  *  60;

                                        $waite_time_val_batch    =  ($request->wt_blitering_val  ??  5)  *  24  *  60;


                                        break;
                        }

                        $this->Auto_scheduler_Stage_Forward($i,  $waite_time_nomal_batch,  $waite_time_val_batch,  $start_date);
                }


                return  response()->json([]);
        }


        public  function  scheduleIntermediate(int  $stageCode,  int  $waite_time_nomal_batch  =  0,  int  $waite_time_val_batch  =  0,   ?Carbon  $start_date  =  null)

        {

                $tasks  =  DB::table("stage_plan as sp")
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
                        ->leftJoin('plan_master',  'sp.plan_master_id',  'plan_master.id')
                        ->leftJoin('finished_product_category',  'sp.product_caterogy_id',  'finished_product_category.id')
                        ->leftJoin('product_name',  'finished_product_category.product_name_id',  'product_name.id')
                        ->leftJoin('market',  'finished_product_category.market_id',  'market.id')
                        ->leftJoin('stage_plan as prev',  'prev.code',  '=',  'sp.predecessor_code')
                        ->where('sp.stage_code',  $stageCode)
                        ->where('sp.finished',  0)
                        ->where('sp.active',  1)
                        ->whereNull('sp.start')
                        ->whereNotNull('prev.start')
                        ->whereNotNull('plan_master.after_weigth_date')
                        ->when($stageCode  ==  7,  function ($q) {

                                $q->whereNotNull('plan_master.after_parkaging_date');
                        })
                        ->where('sp.deparment_code',  session('user')['production_code'])
                        ->orderBy('prev.start',  'asc')

                        ->get();


                if (!$tasks->isNotEmpty()) {

                        return;
                };


                $processedCampaigns  =  [];
                // campaign đã xử lý


                foreach ($tasks  as  $task) {

                        if ($task->is_val  ===  1) {

                                $waite_time  =  $waite_time_val_batch;
                        } else {

                                $waite_time  =  $waite_time_nomal_batch;
                        }


                        if ($task->campaign_code  ===  null) {

                                $this->sheduleNotCampaing($task,  $stageCode,  $waite_time,  $start_date,  null);
                        } else {

                                if (in_array($task->campaign_code,  $processedCampaigns)) {

                                        continue;
                                }

                                // Gom nhóm campaign
                                $campaignTasks  =  $tasks->where('campaign_code',  $task->campaign_code)->sortBy('batch');;

                                $this->scheduleCampaign($campaignTasks,  $stageCode,  $waite_time,   $start_date,  null);

                                // Đánh dấu campaign đã xử lý
                                $processedCampaigns[]  =  $task->campaign_code;
                        }

                        //$this->order_by++;
                }
        }


        public  function  scheduleSensitiveProduct(int  $stageCode,  int  $waite_time_nomal_batch  =  0,  int  $waite_time_val_batch  =  0,   ?Carbon  $start_date  =  null)

        {



                $tasks  =  DB::table("stage_plan as sp")
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
                        ->leftJoin('plan_master',  'sp.plan_master_id',  'plan_master.id')
                        ->leftJoin('finished_product_category',  'sp.product_caterogy_id',  'finished_product_category.id')
                        ->leftJoin('intermediate_category',  'finished_product_category.intermediate_code',  'intermediate_category.intermediate_code')
                        ->leftJoin('product_name',  'finished_product_category.product_name_id',  'product_name.id')
                        ->leftJoin('market',  'finished_product_category.market_id',  'market.id')
                        ->leftJoin('stage_plan as prev',  'prev.code',  '=',  'sp.predecessor_code')
                        ->where('sp.stage_code',  $stageCode)
                        ->where('sp.finished',  0)
                        ->where('sp.active',  1)
                        ->where('intermediate_category.quarantine_total',  '>',  0)
                        ->whereNull('sp.start')
                        ->whereNotNull('plan_master.after_weigth_date')
                        ->when($stageCode  ==  7,  function ($q) {

                                $q->whereNotNull('plan_master.after_parkaging_date');
                        })
                        ->where('sp.deparment_code',  session('user')['production_code'])
                        ->orderBy('prev.start',  'asc')

                        ->get();



                if (!$tasks->isNotEmpty()) {

                        return;
                };


                $processedCampaigns  =  [];
                // campaign đã xử lý


                foreach ($tasks  as  $task) {

                        if ($task->is_val  ===  1) {

                                $waite_time  =  $waite_time_val_batch;
                        } else {

                                $waite_time  =  $waite_time_nomal_batch;
                        }

                        $start_date_temp  =  $start_date;


                        if ($task->campaign_code  ===  null) {

                                $startDate_responsed_date  =   Carbon::parse($task->responsed_date)->subDays((int) $task->quarantine_total);


                                if ($startDate_responsed_date->gt($start_date)) {

                                        $start_date_temp  =  $startDate_responsed_date;
                                }

                                $this->sheduleNotCampaing($task,  $stageCode,  $waite_time,  $start_date_temp,  null);
                        } else {

                                if (in_array($task->campaign_code,  $processedCampaigns)) {

                                        continue;
                                }


                                $campaignTasks  =  $tasks->where('campaign_code',  $task->campaign_code)->sortBy('batch');

                                $startDate_responsed_date  =   Carbon::parse($task->responsed_date)->subDays((int) $task->quarantine_total);

                                if ($startDate_responsed_date->gt($start_date)) {

                                        $start_date_temp  =  $startDate_responsed_date;
                                }

                                $this->scheduleCampaign($campaignTasks,  $stageCode,  $waite_time,   $start_date_temp,  null);

                                // Đánh dấu campaign đã xử lý
                                $processedCampaigns[]  =  $task->campaign_code;
                        }

                        //$this->order_by++;
                }
        }


        public  function  Auto_scheduler_Stage_Forward(int  $stageCode,  int  $waite_time_nomal_batch  =  0,  int  $waite_time_val_batch  =  0,   ?Carbon  $start_date  =  null)

        {


                if ($this->prev_orderBy  &&  $stageCode  >  3) {


                        $tasks  =  DB::table("stage_plan as sp")
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

                                        //'intermediate_category.quarantine_total'   // lấy start của công đoạn trước
                                )
                                ->leftJoin('plan_master',  'sp.plan_master_id',  'plan_master.id')
                                ->leftJoin('finished_product_category',  'sp.product_caterogy_id',  'finished_product_category.id')
                                //->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', 'intermediate_category.intermediate_code')
                                ->leftJoin('product_name',  'finished_product_category.product_name_id',  'product_name.id')
                                ->leftJoin('market',  'finished_product_category.market_id',  'market.id')
                                ->leftJoin('stage_plan as prev',  'prev.code',  '=',  'sp.predecessor_code')
                                ->where('sp.stage_code',  $stageCode)
                                ->where('sp.finished',  0)
                                ->where('sp.active',  1)
                                ->whereNull('sp.start')
                                //->where('plan_master.only_parkaging',  0)
                                ->whereNotNull('plan_master.after_weigth_date')
                                // ->when($stageCode == 7, function ($q) {
                                //        $q->whereNotNull('plan_master.after_parkaging_date');
                                // })
                                ->where('sp.deparment_code',  session('user')['production_code'])
                                ->orderBy('prev.start',  'asc')

                                ->get();
                } else {

                        $tasks  =  DB::table("stage_plan as sp")
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

                                        //'intermediate_category.quarantine_total'

                                )
                                ->leftJoin('plan_master',  'sp.plan_master_id',  'plan_master.id')
                                ->leftJoin('finished_product_category',  'sp.product_caterogy_id',  'finished_product_category.id')
                                //->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', 'intermediate_category.intermediate_code')
                                ->leftJoin('product_name',  'finished_product_category.product_name_id',  'product_name.id')
                                ->leftJoin('market',  'finished_product_category.market_id',  'market.id')
                                ->where('sp.stage_code',  $stageCode)
                                ->where('sp.finished',  0)
                                ->where('sp.active',  1)
                                ->whereNull('sp.start')
                                //->where('plan_master.only_parkaging',  0)
                                ->whereNotNull('plan_master.after_weigth_date')
                                ->when($stageCode  ==  7,  function ($q) {

                                        $q->whereNotNull('plan_master.after_parkaging_date');
                                })
                                ->where('sp.deparment_code',  session('user')['production_code'])
                                ->orderBy('order_by',  'asc')
                                ->get();
                }


                $processedCampaigns  =  [];
                // campaign đã xử lý


                foreach ($tasks  as  $task) {

                        if ($task->is_val  ===  1) {

                                $waite_time  =  $waite_time_val_batch;
                        } else {

                                $waite_time  =  $waite_time_nomal_batch;
                        }


                        if ($task->campaign_code  ===  null) {

                                $this->sheduleNotCampaing($task,  $stageCode,  $waite_time,  $start_date,  null);
                        } else {

                                if (in_array($task->campaign_code,  $processedCampaigns)) {

                                        continue;
                                }

                                // Gom nhóm campaign
                                $campaignTasks  =  $tasks->where('campaign_code',  $task->campaign_code)->sortBy('batch');

                                $this->scheduleCampaign($campaignTasks,  $stageCode,  $waite_time,   $start_date,  null);

                                // Đánh dấu campaign đã xử lý
                                $processedCampaigns[]  =  $task->campaign_code;
                        }

                        //$this->order_by++;
                }
        }


        public  function  Auto_scheduler_Stage_Backward(int  $stageCode,  int  $waite_time_nomal_batch  =  0,  int  $waite_time_val_batch  =  0,   ?Carbon  $start_date  =  null)

        {

                //$this->prev_orderby &&
                if ($stageCode  =  7) {

                        $tasks  =  DB::table("stage_plan as sp")
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
                                ->leftJoin('plan_master',  'sp.plan_master_id',  'plan_master.id')
                                ->leftJoin('finished_product_category',  'sp.product_caterogy_id',  'finished_product_category.id')

                                ->leftJoin('product_name',  'finished_product_category.product_name_id',  'product_name.id')
                                ->leftJoin('market',  'finished_product_category.market_id',  'market.id')
                                ->leftJoin('stage_plan as next',  'next.code',  '=',  'sp.nextcessor_code')
                                ->where('sp.stage_code',  $stageCode)
                                ->where('sp.finished',  0)
                                ->where('sp.active',  1)
                                ->whereNull('sp.start')
                                ->whereNotNull('plan_master.after_weigth_date')
                                ->when($stageCode  ==  7,  function ($q) {

                                        $q->whereNotNull('plan_master.after_parkaging_date');
                                })
                                ->where('sp.deparment_code',  session('user')['production_code'])
                                ->orderBy('next.order_by_line')
                                ->get();
                } else {

                        $tasks  =  DB::table("stage_plan as sp")
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
                                ->leftJoin('plan_master',  'sp.plan_master_id',  'plan_master.id')
                                ->leftJoin('finished_product_category',  'sp.product_caterogy_id',  'finished_product_category.id')
                                ->leftJoin('product_name',  'finished_product_category.product_name_id',  'product_name.id')
                                ->leftJoin('market',  'finished_product_category.market_id',  'market.id')
                                ->leftJoin('stage_plan as next',  'next.code',  '=',  'sp.nextcessor_code')
                                ->where('sp.stage_code',  $stageCode)
                                ->where('sp.finished',  0)
                                ->where('sp.active',  1)
                                ->whereNull('sp.start')
                                ->whereNotNull('plan_master.after_weigth_date')
                                ->when($stageCode  ==  7,  function ($q) {

                                        $q->whereNotNull('plan_master.after_parkaging_date');
                                })
                                ->where('sp.deparment_code',  session('user')['production_code'])
                                ->orderBy('next.start',  'asc')
                                ->get();
                }


                $processedCampaigns  =  [];
                // campaign đã xử lý


                foreach ($tasks  as  $task) {

                        if ($task->is_val  ===  1) {

                                $waite_time  =  $waite_time_val_batch;
                        } else {

                                $waite_time  =  $waite_time_nomal_batch;
                        }


                        if ($task->campaign_code  ===  null) {


                                $this->sheduleNotCampaing_BW($task,  $stageCode,  $waite_time,  $start_date,  null);
                        } else {

                                if (in_array($task->campaign_code,  $processedCampaigns)) {

                                        continue;
                                }

                                // Gom nhóm campaign
                                $campaignTasks  =  $tasks->where('campaign_code',  $task->campaign_code)->sortBy('batch',  'desc');

                                $this->scheduleCampaign_BW($campaignTasks,  $stageCode,  $waite_time,   $start_date,  null);

                                // Đánh dấu campaign đã xử lý
                                $processedCampaigns[]  =  $task->campaign_code;
                        }
                }
        }


        public  function  scheduleWeightStage(?Carbon  $start_date  =  null)

        {


                $start_date  =  $start_date  ??  now();


                $tasks  =  DB::table("stage_plan as sp")
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
                        ->leftJoin('plan_master',  'sp.plan_master_id',  'plan_master.id')
                        ->leftJoin('finished_product_category',  'sp.product_caterogy_id',  'finished_product_category.id')
                        ->leftJoin('product_name',  'finished_product_category.product_name_id',  'product_name.id')
                        ->leftJoin('market',  'finished_product_category.market_id',  'market.id')
                        ->leftJoin('stage_plan as next',  'next.code',  '=',  'sp.nextcessor_code')
                        ->where('sp.active',  1)
                        ->where('next.active',  1)
                        ->whereIn('sp.stage_code',  [1,  2])
                        ->whereNull('sp.start')
                        ->where('sp.finished',  0)
                        ->where('next.finished',  0)
                        ->where('next.start',  '>',  now())
                        ->whereNotNull('plan_master.after_weigth_date')
                        ->where('sp.deparment_code',  session('user')['production_code'])
                        ->orderBy('next.start',  'asc')
                        ->get();



                $this->processed_stage_code_Id  =   [];

                //$processedcampaigns = [];
                foreach ($tasks  as  $task) {

                        if ($task->campaign_code  ===  null) {

                                $this->scheduleweight($task,  0,  false,  $start_date);
                        } else {


                                //if (in_array($task->campaign_code . $task->stage_code , $processedcampaigns)) {continue;}
                                if (in_array($task->id,  $this->processed_stage_code_Id)) {

                                        continue;
                                }

                                $campaignTasks  =  $tasks->where('campaign_code',  $task->campaign_code)->whereNotIn('id',  $this->processed_stage_code_Id)->where('stage_code',  $task->stage_code)->sortBy('batch');

                                $this->scheduleweight($campaignTasks,   0,  true,  $start_date);

                                //$processedCampaigns[] = $task->campaign_code . $task->stage_code;
                        }
                }
        }


        public  function  scheduleLine(string  $required_room,  array  $stage_plan_ids,  int  $stageCode,  int  $waite_time_nomal_batch  =  0,  int  $waite_time_val_batch  =  0,   ?Carbon  $start_date  =  null)

        {



                if ($this->prev_orderBy  &&  $stageCode  >=  4) {

                        $tasks  =  DB::table("stage_plan as sp")
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
                                ->leftJoin('plan_master',  'sp.plan_master_id',  'plan_master.id')
                                ->leftJoin('finished_product_category',  'sp.product_caterogy_id',  'finished_product_category.id')
                                ->leftJoin('product_name',  'finished_product_category.product_name_id',  'product_name.id')
                                ->leftJoin('market',  'finished_product_category.market_id',  'market.id')
                                ->leftJoin('stage_plan as prev',  'prev.code',  '=',  'sp.predecessor_code')
                                ->whereNotNull('prev.start')
                                ->whereIn('sp.id',  $stage_plan_ids)
                                ->whereNotNull('plan_master.after_weigth_date')
                                ->when($stageCode  ==  7,  function ($q) {

                                        $q->whereNotNull('plan_master.after_parkaging_date');
                                })
                                ->where('sp.deparment_code',  session('user')['production_code'])
                                ->orderBy('prev.start',  'asc')
                                ->get();
                } else {


                        $tasks  =  DB::table("stage_plan as sp")
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
                                ->leftJoin('plan_master',  'sp.plan_master_id',  'plan_master.id')
                                ->leftJoin('finished_product_category',  'sp.product_caterogy_id',  'finished_product_category.id')
                                ->leftJoin('product_name',  'finished_product_category.product_name_id',  'product_name.id')
                                ->leftJoin('market',  'finished_product_category.market_id',  'market.id')

                                ->whereIn('sp.id',  $stage_plan_ids)
                                ->whereNotNull('plan_master.after_weigth_date')
                                ->when($stageCode  ==  7,  function ($q) {

                                        $q->whereNotNull('plan_master.after_parkaging_date');
                                })
                                ->when($stageCode  >=  4,  function ($query) {

                                        $query->leftJoin('stage_plan as prev',  'prev.code',  '=',  'sp.predecessor_code')
                                                ->whereNotNull('prev.start');
                                })
                                ->where('sp.deparment_code',  session('user')['production_code'])
                                ->orderBy('order_by_line',  'asc')
                                ->get();
                }


                $processedCampaigns  =  [];
                // campaign đã xử lý

                foreach ($tasks  as  $task) {

                        if ($task->is_val  ===  1) {

                                $waite_time  =  $waite_time_val_batch;
                        } else {

                                $waite_time  =  $waite_time_nomal_batch;
                        }


                        if ($task->campaign_code  ===  null) {


                                $this->sheduleNotCampaing($task,  $stageCode,  $waite_time,  $start_date,  $required_room);
                        } else {

                                if (in_array($task->campaign_code,  $processedCampaigns)) {

                                        continue;
                                }

                                // Gom nhóm campaign
                                $campaignTasks  =  $tasks->where('campaign_code',  $task->campaign_code)->sortBy('batch');;

                                $this->scheduleCampaign($campaignTasks,  $stageCode,  $waite_time,  $start_date,  $required_room);

                                // Đánh dấu campaign đã xử lý
                                $processedCampaigns[]  =  $task->campaign_code;
                        }

                        $this->order_by++;
                }
        }


        protected  function  sheduleNotCampaing($task,  $stageCode,   int  $waite_time  =  0,   ?Carbon  $start_date  =  null,  ?string  $Line  =  null)

        {


                $now  =  Carbon::now();

                $minute  =  $now->minute;

                $roundedMinute  =  ceil($minute  /  15)  *  15;

                if ($roundedMinute  ==  60) {

                        $now->addHour();

                        $roundedMinute  =  0;
                }

                $now->minute($roundedMinute)->second(0)->microsecond(0);


                // Gom tất cả candidate time vào 1 mảng
                $candidates[]  =  $now;

                $candidates[]  =  $start_date;


                // nếu có after_weigth_date
                if ($stageCode  <=  6) {

                        if (!empty($task->after_weigth_date)) {

                                $candidates[]  =  Carbon::parse($task->after_weigth_date);
                        }

                        if (!empty($task->allow_weight_before_date)) {

                                $candidates[]  =  Carbon::parse($task->allow_weight_before_date);
                        }
                } else {

                        if (!empty($task->after_parkaging_date)) {

                                $candidates[]  =  Carbon::parse($task->after_parkaging_date);
                        }
                }


                if ($task->predecessor_code  !=  null) {

                        $pred  =  DB::table('stage_plan')
                                ->where('code',  $task->predecessor_code)->first();

                        if ($pred) {

                                $candidates[]  =  Carbon::parse($pred->end)->addMinutes($waite_time);
                        }
                }


                // Lấy max
                $earliestStart  =  collect($candidates)->max();


                // chọn phòng sx
                if ($task->required_room_code  !=  null  ||  $Line  !=  null) {


                        if ($task->required_room_code  !=  null) {

                                $room_code  =  $task->required_room_code;
                        } else {

                                $room_code  =  $Line;
                        }


                        $room_id  =   DB::table('room')->where('code',  $room_code)->value('id');


                        $rooms  =  DB::table('quota')->select(
                                'room_id',
                                DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                        )
                                ->when($task->stage_code  <=  6,  function ($query)  use ($task) {

                                        return  $query->where('intermediate_code',  $task->intermediate_code);
                                },  function ($query)  use ($task) {

                                        return  $query->where('finished_product_code',  $task->finished_product_code)
                                                ->where('intermediate_code',  $task->intermediate_code);
                                })
                                ->where('room_id',  $room_id)
                                ->get();
                } else {

                        if ($task->code_val  !==  null  &&  $task->stage_code  ==  3  &&  isset($parts[1])  &&  $parts[1]  >  1) {

                                $code_val_first  =  $parts[0]  .  '_1';


                                $room_id_first  =  DB::table("stage_plan as sp")
                                        ->leftJoin('plan_master as pm',  'sp.plan_master_id',  '=',  'pm.id')
                                        ->where('code_val',  $code_val_first)
                                        ->where('stage_code',  $task->stage_code)
                                        ->first();


                                if ($room_id_first) {

                                        $rooms  =  DB::table('quota')
                                                ->select(
                                                        'room_id',
                                                        DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                                        DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                                        DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                                        DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                                )
                                                ->when($task->stage_code  <=  6,  function ($query)  use ($task) {

                                                        return  $query->where('intermediate_code',  $task->intermediate_code);
                                                },  function ($query)  use ($task) {

                                                        return  $query->where('finished_product_code',  $task->finished_product_code);
                                                })
                                                ->where('active',  1)
                                                ->where('room_id',  $room_id_first->resourceId)
                                                ->get();
                                } else {


                                        $rooms  =  DB::table('quota')->select(
                                                'room_id',
                                                DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                        )
                                                ->when($task->stage_code  <=  6,  function ($query)  use ($task) {

                                                        return  $query->where('intermediate_code',  $task->intermediate_code);
                                                },  function ($query)  use ($task) {

                                                        return  $query->where('finished_product_code',  $task->finished_product_code);
                                                })
                                                ->where('active',  1)
                                                ->where('stage_code',  $task->stage_code)
                                                ->get();
                                }
                        } elseif ($task->code_val  !==  null  &&  $task->stage_code  >  3  &&  isset($parts[1])  &&  $parts[1]  >  1) {

                                $code_val_first  =  $parts[0];


                                $room_id_first  =  DB::table("stage_plan as sp")
                                        ->leftJoin('plan_master as pm',  'sp.plan_master_id',  '=',  'pm.id')
                                        ->where(DB::raw("SUBSTRING_INDEX(pm.code_val, '_', 1)"),  '=',  $parts[0])
                                        ->where('sp.stage_code',  $task->stage_code)
                                        ->whereNotNull('start')
                                        ->get();


                                if ($room_id_first) {


                                        $rooms  =  DB::table('quota')
                                                ->select(
                                                        'room_id',
                                                        DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                                        DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                                        DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                                        DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                                )
                                                ->when($task->stage_code  <=  6,  function ($query)  use ($task) {

                                                        return  $query->where('intermediate_code',  $task->intermediate_code);
                                                },  function ($query)  use ($task) {

                                                        return  $query->where('finished_product_code',  $task->finished_product_code);
                                                })
                                                ->where('active',  1)
                                                ->where('stage_code',  $task->stage_code)
                                                ->get();



                                        if ($rooms->count()  >  $room_id_first->count()) {

                                                foreach ($room_id_first  as  $first) {

                                                        $rooms->where('room_id',  '!=',  $first->resourceId);
                                                }
                                        }
                                } else {

                                        $rooms  =  DB::table('quota')->select(
                                                'room_id',
                                                DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                        )
                                                ->when($task->stage_code  <=  6,  function ($query)  use ($task) {

                                                        return  $query->where('intermediate_code',  $task->intermediate_code);
                                                },  function ($query)  use ($task) {

                                                        return  $query->where('finished_product_code',  $task->finished_product_code);
                                                })
                                                ->where('active',  1)
                                                ->where('stage_code',  $task->stage_code)
                                                ->get();
                                }
                        } else {

                                $rooms  =  DB::table('quota')->select(
                                        'room_id',
                                        DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                        DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                        DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                        DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                )
                                        ->when($task->stage_code  <=  6,  function ($query)  use ($task) {

                                                return  $query->where('intermediate_code',  $task->intermediate_code);
                                        },  function ($query)  use ($task) {

                                                return  $query->where('finished_product_code',  $task->finished_product_code);
                                        })
                                        ->where('active',  1)
                                        ->where('stage_code',  $task->stage_code)
                                        ->get();
                        }
                }

                // phòng phù hợp (quota)
                if ($rooms->isEmpty()) return;

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
                                $ratio = (float)($pm->percent_parkaging ?? 1);
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
                                "stage_plan",
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

                if ($bestRoom === null || $bestStart === null) return;

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

                if (!$bestQuota) return;

                $finalInterval = (float)($bestQuota->p_min * $ratio) + (float)($bestQuota->m_min * $ratio);
                if ($finalInterval < 15) $finalInterval = 15;

                $C2_time_minutes = (float)$bestQuota->c2_min;

                $bestEnd = $this->addWorkingMinutes($bestStart->copy(), (float) $finalInterval, $bestRoom, $this->work_sunday);

                $start_clearning  =  $bestEnd->copy();

                $end_clearning  =  $this->addWorkingMinutes($start_clearning->copy(), (float) $C2_time_minutes,  $bestRoom,  $this->work_sunday);



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
                $nextTasks  =  collect();

                $next_stage_code  =  isset($task->nextcessor_code)  ? (int) (explode('_',  $task->nextcessor_code)[1]  ??  0)  :  0;

                if ($task->nextcessor_code  &&  $next_stage_code  &&   $next_stage_code  <=  $this->max_Step) {
                        //&& $task->immediately

                        $nextTasks  =  DB::table("stage_plan as sp")
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
                                ->leftJoin('plan_master',  'sp.plan_master_id',  'plan_master.id')
                                ->leftJoin('finished_product_category',  'sp.product_caterogy_id',  'finished_product_category.id')
                                ->leftJoin('product_name',  'finished_product_category.product_name_id',  'product_name.id')
                                ->leftJoin('market',  'finished_product_category.market_id',  'market.id')
                                ->where('sp.code',  $task->nextcessor_code)
                                ->where('sp.finished',  0)
                                ->where('sp.active',  1)
                                ->when($stageCode  ==  7,  function ($q) {

                                        $q->whereNotNull('plan_master.after_parkaging_date');
                                })
                                ->where('sp.deparment_code',  session('user')['production_code'])
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


        protected  function  scheduleCampaign($campaignTasks,  $stageCode,  int  $waite_time  =  0,  ?Carbon  $start_date  =  null,  ?string  $Line  =  null,   ?float  $totalTimeCampaign  =  0)

        {


                $firstTask  =  $campaignTasks->first();

                $now  =  Carbon::now();

                $minute  =  $now->minute;

                $roundedMinute  =  ceil($minute  /  15)  *  15;

                if ($roundedMinute  ==  60) {

                        $now->addHour();

                        $roundedMinute  =  0;
                }

                $now->minute($roundedMinute)->second(0)->microsecond(0);


                // Gom tất cả candidate time vào 1 mảng
                $candidates[]  =  $now;

                $candidates[]  =  $start_date;


                // nếu có after_weigth_date
                if ($stageCode  <=  6) {

                        if (!empty($firstTask->after_weigth_date)) {

                                $candidates[]  =  Carbon::parse($firstTask->after_weigth_date);
                        }

                        if (!empty($task->allow_weight_before_date)) {

                                $candidates[]  =  Carbon::parse($firstTask->allow_weight_before_date);
                        }
                } else {

                        if (!empty($firstTask->after_parkaging_date)) {

                                $candidates[]  =  Carbon::parse($firstTask->after_parkaging_date);
                        }
                }


                //$pre_campaign_first_batch_end = [];
                $pre_campaign_codes  =  [];


                foreach ($campaignTasks  as  $campaignTask) {


                        $pred  =  DB::table('stage_plan')->where('code',  $campaignTask->predecessor_code)->first();


                        if ($pred) {


                                $code  =  $pred->campaign_code;


                                if (!in_array($code,  $pre_campaign_codes)  &&  $code  !=  null) {

                                        $pre_campaign_codes[]  =  $code;


                                        $pre_campaign_batch  =  DB::table('stage_plan')
                                                ->where('campaign_code',  $code)
                                                ->orderBy('start',  'asc')
                                                ->get();


                                        $pre_campaign_first_batch  =   $pre_campaign_batch->first();

                                        $pre_campaign_last_batch  =   $pre_campaign_batch->last();


                                        $prevCycle  =  DB::table('quota')
                                                ->selectRaw('AVG(TIME_TO_SEC(m_time)/60) as avg_m_time_minutes')
                                                ->when($firstTask->stage_code  <=  6,  function ($query)  use ($firstTask) {

                                                        return  $query->where('intermediate_code',  $firstTask->intermediate_code);
                                                },  function ($query)  use ($firstTask) {

                                                        return  $query->where('finished_product_code',  $firstTask->finished_product_code);
                                                })
                                                ->where('active',  1)
                                                ->where('stage_code',  $pre_campaign_first_batch->stage_code)
                                                ->value('avg_m_time_minutes');


                                        $currCycle  =  DB::table('quota')
                                                ->selectRaw('AVG(TIME_TO_SEC(m_time)/60) as avg_m_time_minutes')
                                                ->when($firstTask->stage_code  <=  6,  function ($query)  use ($firstTask) {

                                                        return  $query->where('intermediate_code',  $firstTask->intermediate_code);
                                                },  function ($query)  use ($firstTask) {

                                                        return  $query->where('finished_product_code',  $firstTask->finished_product_code);
                                                })
                                                ->where('active',  1)
                                                ->where('stage_code',  $campaignTask->stage_code)
                                                ->value('avg_m_time_minutes');


                                        $maxCount  =  max($campaignTasks->count(),  $pre_campaign_batch->count());


                                        if ($currCycle  &&  $currCycle  >=  $prevCycle) {

                                                $candidates[]  =  Carbon::parse($pred->end)->addMinutes($waite_time);
                                        } else {


                                                $hasImmediately  =  collect($campaignTasks)->contains('immediately',  1);


                                                if ($campaignTask->immediately  ==  false  &&  $hasImmediately) {

                                                        $candidates[]  =  Carbon::parse($pre_campaign_last_batch->end)->subMinutes(($campaignTasks->count()  -  1)  *  $currCycle);

                                                        $candidates[]  =  Carbon::parse($pred->end)->addMinutes($waite_time  +  $maxCount  *  ($prevCycle  -  $currCycle));
                                                }
                                        }
                                }


                                if ($code  ==  null) {

                                        $candidates[]  =   Carbon::parse($pred->end);
                                }
                        }
                }

                // Lấy max
                $earliestStart  =  collect($candidates)->max();


                // phòng phù hợp (quota)
                if ($firstTask->required_room_code  !=  null  ||  $Line  !=  null) {

                        if ($firstTask->required_room_code  !=  null) {

                                $room_code  =  $firstTask->required_room_code;
                        } else {

                                $room_code  =  $Line;
                        }


                        $room_id  =   DB::table('room')->where('code',  $room_code)->value('id');


                        $rooms  =  DB::table('quota')->select(
                                'room_id',
                                DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                        )
                                ->when($firstTask->stage_code  <=  6,  function ($query)  use ($firstTask) {

                                        return  $query->where('intermediate_code',  $firstTask->intermediate_code);
                                },  function ($query)  use ($firstTask) {

                                        return  $query->where('finished_product_code',  $firstTask->finished_product_code)
                                                ->where('intermediate_code',  $firstTask->intermediate_code);
                                })
                                ->where('room_id',  $room_id)
                                ->get();
                } else {

                        if ($firstTask->code_val  !==  null  &&  $firstTask->stage_code  ==  3  &&  isset($parts[1])  &&  $parts[1]  >  1) {


                                $code_val_first  =  $parts[0]  .  '_1';


                                $room_id_first  =  DB::table("stage_plan as sp")
                                        ->leftJoin('plan_master as pm',  'sp.plan_master_id',  '=',  'pm.id')
                                        ->where('code_val',  $code_val_first)
                                        ->where('stage_code',  $firstTask->stage_code)
                                        ->first();


                                if ($room_id_first) {

                                        $rooms  =  DB::table('quota')
                                                ->select(
                                                        'room_id',
                                                        DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                                        DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                                        DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                                        DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                                )
                                                ->when($firstTask->stage_code  <=  6,  function ($query)  use ($firstTask) {

                                                        return  $query->where('intermediate_code',  $firstTask->intermediate_code);
                                                },  function ($query)  use ($firstTask) {

                                                        return  $query->where('finished_product_code',  $firstTask->finished_product_code);
                                                })
                                                ->where('active',  1)
                                                ->where('room_id',  $room_id_first->resourceId)
                                                ->get();
                                } else {


                                        $rooms  =  DB::table('quota')->select(
                                                'room_id',
                                                DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                        )
                                                ->when($firstTask->stage_code  <=  6,  function ($query)  use ($firstTask) {

                                                        return  $query->where('intermediate_code',  $firstTask->intermediate_code);
                                                },  function ($query)  use ($firstTask) {

                                                        return  $query->where('finished_product_code',  $firstTask->finished_product_code);
                                                })
                                                ->where('active',  1)
                                                ->where('stage_code',  $firstTask->stage_code)
                                                ->get();
                                }
                        } elseif ($firstTask->code_val  !==  null  &&  $firstTask->stage_code  >  3  &&  isset($parts[1])  &&  $parts[1]  >  1) {


                                $code_val_first  =  $parts[0];


                                $room_id_first  =  DB::table("stage_plan as sp")
                                        ->leftJoin('plan_master as pm',  'sp.plan_master_id',  '=',  'pm.id')
                                        ->where(DB::raw("SUBSTRING_INDEX(pm.code_val, '_', 1)"),  '=',  $parts[0])
                                        ->where('sp.stage_code',  $firstTask->stage_code)
                                        ->whereNotNull('start')
                                        ->get();


                                if ($room_id_first) {


                                        $rooms  =  DB::table('quota')
                                                ->select(
                                                        'room_id',
                                                        DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                                        DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                                        DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                                        DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                                )
                                                ->when($firstTask->stage_code  <=  6,  function ($query)  use ($firstTask) {

                                                        return  $query->where('intermediate_code',  $firstTask->intermediate_code);
                                                },  function ($query)  use ($firstTask) {

                                                        return  $query->where('finished_product_code',  $firstTask->finished_product_code);
                                                })
                                                ->where('active',  1)
                                                ->where('stage_code',  $firstTask->stage_code)
                                                ->get();



                                        if ($rooms->count()  >  $room_id_first->count()) {

                                                foreach ($room_id_first  as  $first) {

                                                        $rooms->where('room_id',  '!=',  $first->resourceId);
                                                }
                                        }
                                } else {

                                        $rooms  =  DB::table('quota')->select(
                                                'room_id',
                                                DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                        )
                                                ->when($firstTask->stage_code  <=  6,  function ($query)  use ($firstTask) {

                                                        return  $query->where('intermediate_code',  $firstTask->intermediate_code);
                                                },  function ($query)  use ($firstTask) {

                                                        return  $query->where('finished_product_code',  $firstTask->finished_product_code);
                                                })
                                                ->where('active',  1)
                                                ->where('stage_code',  $firstTask->stage_code)
                                                ->get();
                                }
                        } else {

                                $rooms  =  DB::table('quota')->select(
                                        'room_id',
                                        DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                        DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                        DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                        DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                                )
                                        ->when($firstTask->stage_code  <=  6,  function ($query)  use ($firstTask) {

                                                return  $query->where('intermediate_code',  $firstTask->intermediate_code);
                                        },  function ($query)  use ($firstTask) {

                                                return  $query->where('finished_product_code',  $firstTask->finished_product_code)
                                                        ->where('intermediate_code',  $firstTask->intermediate_code);
                                        })
                                        ->where('active',  1)
                                        ->where('stage_code',  $firstTask->stage_code)
                                        ->get();
                        }
                }


                if (!$rooms)  return;


                // liên hê giữa pc và tht 
                if ($stageCode  ==  4  &&   $firstTask->predecessor_code  &&   explode('_',  $firstTask->predecessor_code)[1]  ==  3  &&  $rooms->count()  >  1) {

                        $rooms_bkc  =  $rooms;


                        $resourceId_prev  =  DB::table('stage_plan')
                                ->where('code',  $firstTask->predecessor_code)
                                ->value('resourceId');


                        $rooms  =  $rooms->filter(function ($room)  use ($resourceId_prev) {


                                if (in_array($resourceId_prev,  [6,  7])) {

                                        return  in_array($room->room_id,  [13,  14]);
                                }


                                if ($resourceId_prev  ==  10) {

                                        return  $room->room_id  ==  17;
                                }


                                return  true;
                        })->values();


                        // ✅ rollback nếu filter làm rỗng
                        if ($rooms->isEmpty()) {

                                $rooms  =  $rooms_bkc;
                        }
                }


                $bestRoom  =  null;

                $bestStart  =  null;



                // tim phòng tối ưu
                $campaign_ratio = 1;
                if ($stageCode == 7) {
                        $cpm = DB::table('plan_master')->where('id', $firstTask->plan_master_id)->select('only_parkaging', 'percent_parkaging')->first();
                        if ($cpm && $cpm->only_parkaging == 1) {
                                $campaign_ratio = (float)($cpm->percent_parkaging ?? 100) / 100;
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

                if ($bestRoom === null || $bestStart === null) return;

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
                                        $task_ratio = (float)($tpm->percent_parkaging ?? 100) / 100;
                                }
                        }

                        $p_task_adj = (float) $bestRoom->p_time_minutes * $task_ratio;
                        $m_task_adj = (float) $bestRoom->m_time_minutes * $task_ratio;

                        if ($counter == 1) {
                                $duration = $p_task_adj + $m_task_adj;
                                if ($duration < 15) $duration = 15;

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
                                if ($duration < 15) $duration = 15;

                                $bestEnd = $this->addWorkingMinutes($bestStart->copy(), $duration, $bestRoom->room_id, $this->work_sunday);
                                $start_clearning = $bestEnd->copy();
                                $bestEndCleaning = $this->addWorkingMinutes($start_clearning->copy(), (float) $bestRoom->C2_time_minutes, $bestRoom->room_id, $this->work_sunday);

                                $clearningType = 2;
                                $lastBatachEnd = $bestEndCleaning->copy();
                                $first_in_campaign = 0;
                        } else {
                                $duration = $m_task_adj;
                                if ($duration < 15) $duration = 15;

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
                $nextcessor_codes  =  collect();

                $nextTasks  =  collect();

                $next_stage_code  =  isset($firstTask->nextcessor_code)
                        ? (int) (explode('_',  $firstTask->nextcessor_code)[1]  ??  0)
                        :  0;

                $hasImmediately  =  true;

                collect($campaignTasks)->contains('immediately',  1);


                if ($next_stage_code  <=  $this->max_Step   &&  $hasImmediately) {


                        $nextcessor_codes  =  $campaignTasks->pluck('nextcessor_code');


                        $nextTasks  =   DB::table("stage_plan as sp")
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
                                ->leftJoin('plan_master',  'sp.plan_master_id',  'plan_master.id')
                                ->leftJoin('finished_product_category',  'sp.product_caterogy_id',  'finished_product_category.id')
                                ->leftJoin('product_name',  'finished_product_category.product_name_id',  'product_name.id')
                                ->leftJoin('market',  'finished_product_category.market_id',  'market.id')
                                ->leftJoin('stage_plan as prev',  'prev.code',  '=',  'sp.predecessor_code')
                                ->whereIn('sp.code',  $nextcessor_codes)
                                //->where('sp.stage_code', $nextcessor_code)
                                ->where('sp.active',  1)
                                //->whereNotNull('plan_master.after_weigth_date')
                                ->where('sp.deparment_code',  session('user')['production_code'])
                                ->orderBy('prev.start',  'asc')
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


        protected  function  scheduleweight($tasks,   int  $waite_time  =  0,  $mode  =  false,   ?Carbon  $start_date  =  null,)

        {


                $now  =  Carbon::now();

                $minute  =  $now->minute;

                $roundedMinute  =  ceil($minute  /  15)  *  15;

                if ($roundedMinute  ==  60) {

                        $now->addHour();

                        $roundedMinute  =  0;
                }

                $now->minute($roundedMinute)->second(0)->microsecond(0);

                $candidates[]  =  $now;



                if ($mode) {

                        $task  =   $tasks->first();

                        $start  =  Carbon::parse($tasks->min('next_start'))->setTime(6,  0,  0);
                } else {

                        $task  =  $tasks;

                        $start  =  Carbon::parse($task->next_start)->setTime(6,  0,  0);
                }


                $daysToSubtract  =  3;


                while ($daysToSubtract  >  0) {

                        $start->subDay();


                        // nếu không phải ngày nghỉ → tính là 1 ngày làm việc
                        if (!in_array($start->toDateString(),  $this->selectedDates,  true)) {

                                $daysToSubtract--;
                        }
                }


                $candidates[]  =  $start;


                $candidates[]  =  $start_date;

                // nếu có after_weigth_date
                if (!empty($task->after_weigth_date)) {

                        $candidates[]  =  Carbon::parse($task->after_weigth_date);
                }

                if (!empty($task->allow_weight_before_date)) {

                        $candidates[]  =  Carbon::parse($task->allow_weight_before_date);
                }

                // Lấy max
                $earliestStart  =  collect($candidates)->max();


                // chọn phòng sx
                if ($task->required_room_code  !=  null) {


                        if ($task->required_room_code  !=  null) {

                                $room_code  =  $task->required_room_code;
                        }


                        $room_id  =   DB::table('room')->where('code',  $room_code)->value('id');


                        $rooms  =  DB::table('quota')->select(
                                'room_id',
                                'campaign_index',
                                'maxofbatch_campaign',
                                DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                        )
                                ->where('intermediate_code',  $task->intermediate_code)
                                ->where('stage_code',  $task->stage_code)
                                ->where('room_id',  $room_id)
                                ->get();
                } else {


                        $rooms  =  DB::table('quota')->select(
                                'room_id',
                                'campaign_index',
                                'maxofbatch_campaign',
                                DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
                                DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
                                DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
                                DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
                        )
                                ->where('intermediate_code',  $task->intermediate_code)
                                ->where('stage_code',  $task->stage_code)
                                ->where('active',  1)
                                ->orderBy('room_id',  'desc')
                                ->get();
                }

                // phòng phù hợp (quota)

                $bestRoom  =  null;

                $bestStart  =  null;

                $clearning_type  =  2;

                $maxofbatch_campaign  =  1;

                //tim phòng tối ưu
                foreach ($rooms  as  $room) {


                        if ($mode) {

                                $campaign_index  =  1  +  ($room->campaign_index  -  1)  *  $tasks->count();
                        } else {

                                $campaign_index  =  1;
                        }




                        $intervalTimeMinutes  = (float) $room->p_time_minutes  +  ((float) $room->m_time_minutes)  * (float)$campaign_index;


                        if ((float) $room->C2_time_minutes   >  0) {

                                $C2_time_minutes  = (float) $room->C2_time_minutes;

                                $clearning_type  =  2;
                        } else {

                                $C2_time_minutes  = (float) $room->C1_time_minutes;

                                $clearning_type  =  1;
                        }




                        $candidateStart  =  $this->findEarliestSlot2(
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

                        if ($bestStart  ===  null  ||  $candidateStart->lt($bestStart)) {

                                $bestRoom  =  $room->room_id;

                                $bestStart  =  $candidateStart;

                                $bestEnd  =  $bestStart->copy()->addMinutes($intervalTimeMinutes);

                                $start_clearning  =   $bestEnd->copy();

                                $end_clearning  =   $bestStart->copy()->addMinutes($intervalTimeMinutes  +   $C2_time_minutes);

                                $maxofbatch_campaign  =   $room->maxofbatch_campaign;
                        }
                }


                $bestStart  =  $this->skipOffTime($bestStart,  $this->offDate,  $bestRoom);

                $bestEnd  =  $this->addWorkingMinutes($bestStart->copy(), (float) $intervalTimeMinutes,  $bestRoom,  $this->work_sunday);

                $start_clearning  =  $bestEnd->copy();

                $end_clearning  =  $this->addWorkingMinutes($start_clearning->copy(), (float) $C2_time_minutes,  $bestRoom,  $this->work_sunday);


                if ($mode) {

                        $count_max  =  1;

                        foreach ($tasks  as  $task) {

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

                                $this->processed_stage_code_Id[]  =   $task->id;

                                if ($count_max  >  $maxofbatch_campaign)  return;
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

                        $this->processed_stage_code_Id[]  =   $task->id;
                }
        }


        public  function  addWorkingMinutes(Carbon  $start,  int  $minutes,  int  $roomId,  bool  $workSunday  =  false): Carbon

        {


                $room  =  DB::table('room')->where('id',  $roomId)->first();

                if (!$room)  return  $start;


                $current  =  $start->copy();

                $remain   =  $minutes;


                // ===== Khai báo ca làm việc =====
                $shifts  =  [];


                if ($room->sheet_regular  ==  1) {

                        // Ca hành chánh
                        $shifts[]  =  ['start'  =>  7,  'end'  =>  16];
                } else {

                        if ($room->sheet_1  ==  1)  $shifts[]  =  ['start'  =>  6,   'end'  =>  14];

                        if ($room->sheet_2  ==  1)  $shifts[]  =  ['start'  =>  14,  'end'  =>  22];

                        if ($room->sheet_3  ==  1)  $shifts[]  =  ['start'  =>  22,  'end'  =>  30];
                        // qua ngày
                }


                if (empty($shifts))  return  $current;


                while ($remain  >  0) {


                        // ===== chủ nhật =====
                        if (!$workSunday  &&  $current->isSunday()) {

                                $current  =  $current->addDay()->setTime($shifts[0]['start']  %  24,  0,  0);

                                continue;
                        }


                        $hour  =  $current->hour  +  ($current->hour  <  6  ?  24  :  0);


                        // ===== Tìm ca hiện tại =====
                        $currentShift  =  null;

                        foreach ($shifts  as  $shift) {

                                if ($hour  >=  $shift['start']  &&  $hour  <  $shift['end']) {

                                        $currentShift  =  $shift;

                                        break;
                                }
                        }


                        // ===== ngoài ca → nhảy ca kế =====
                        if (!$currentShift) {

                                $jumped  =  false;


                                foreach ($shifts  as  $shift) {

                                        if ($hour  <  $shift['start']) {

                                                $current  =  $current->setTime($shift['start']  %  24,  0,  0);

                                                $jumped  =  true;

                                                break;
                                        }
                                }


                                if (!$jumped) {

                                        $current  =  $current->addDay()
                                                ->setTime($shifts[0]['start']  %  24,  0,  0);
                                }


                                continue;
                        }


                        // ===== Trong ca =====
                        $endOfShift  =  $current->copy()->setTime(
                                $currentShift['end']  %  24,
                                0,
                                0
                        );


                        if ($currentShift['end']  >=  24) {

                                $endOfShift->addDay();
                        }


                        $canWork  =  $current->diffInMinutes($endOfShift);


                        // ===== làm chưa hết ca =====
                        if ($remain  <=  $canWork) {

                                return  $current->addMinutes($remain);
                        }


                        // ===== Làm hết ca =====
                        $remain   -=  $canWork;

                        $current  =  $endOfShift;
                }


                return  $current;
        }


        protected  function  findLatestSlot(
                $roomId,
                $latestEnd,
                $beforeIntervalMinutes,
                $afterIntervalMinutes,
                $time_clearning_tank  =  60,

                ?Carbon  $start_date  =  null,
                bool  $requireTank  =  false,
                bool  $requireAHU  =  false,
                int  $maxTank  =  2,
                string  $stage_plan_table  =  'stage_plan'
        ) {

                $this->loadRoomAvailability('desc',  $roomId);

                $start_date  =  $start_date  ??  Carbon::now();

                $AHU_group   =  DB::table('room')->where('id',  $roomId)->value('AHU_group');


                if (!isset($this->roomAvailability[$roomId])) {

                        $this->roomAvailability[$roomId]  =  [];
                }

                $busyList  =  $this->roomAvailability[$roomId];
                // collect($this->roomAvailability[$roomId])->sortByDesc('end');
                $current_end_clearning  =  Carbon::parse($latestEnd)->copy()->addMinutes($afterIntervalMinutes);


                $tryCount  =  0;

                while (true) {

                        foreach ($busyList  as  $busy) {

                                // nếu current nằm sau block bận
                                if ($current_end_clearning->gt($busy['end'])) {

                                        $gap  =  $current_end_clearning->diffInMinutes($busy['end']);

                                        if ($gap  >=  ($beforeIntervalMinutes  +  $afterIntervalMinutes)) {

                                                // kiểm tra tank nếu cần
                                                if ($requireTank  ==  true) {

                                                        $bestEnd  =  $current_end_clearning->copy()->subMinutes($afterIntervalMinutes);

                                                        $bestStart  =  $bestEnd->copy()->subMinutes($beforeIntervalMinutes);


                                                        $overlapTankCount  =  DB::table($stage_plan_table)
                                                                ->whereNotNull('start')
                                                                ->where('tank',  1)
                                                                ->where('stage_code',  3)
                                                                ->where('start',  '<',  $bestEnd)
                                                                ->where('end',  '>',  $bestStart)
                                                                ->count();


                                                        if ($overlapTankCount  >=  $maxTank) {

                                                                // Nếu tank đã đầy thì lùi thêm 15 phút và thử lại
                                                                $current_end_clearning  =  $bestStart->copy()->addMinutes($beforeIntervalMinutes  +  $time_clearning_tank);

                                                                $tryCount++;

                                                                if ($tryCount  >  100)  return  false;
                                                                // tránh vòng lặp vô hạn
                                                                continue;
                                                                // quay lại while
                                                        }
                                                }


                                                if ($requireAHU  ==  true  &&  $AHU_group  ==  true) {

                                                        $bestEnd  =  $current_end_clearning->copy()->subMinutes($afterIntervalMinutes);

                                                        $bestStart  =  $bestEnd->copy()->subMinutes($beforeIntervalMinutes);


                                                        $overlapAHUCount  =  DB::table($stage_plan_table)
                                                                ->whereNotNull('start')
                                                                ->where('stage_code',  7)
                                                                ->where('keep_dry',  1)
                                                                ->where('AHU_group',  $AHU_group)
                                                                ->where('start',  '<',  $bestEnd)
                                                                ->where('end',  '>',  $bestStart)
                                                                ->count();


                                                        if ($overlapAHUCount  >=  3) {

                                                                $current_end_clearning  =  $bestStart
                                                                        ->copy()
                                                                        ->addMinutes($beforeIntervalMinutes);

                                                                $tryCount++;

                                                                if ($tryCount  >  100)  return  false;
                                                                // tránh vòng lặp vô hạn
                                                                continue;
                                                                // quay lại vòng while
                                                        }
                                                }

                                                return  $current_end_clearning;
                                        }
                                }


                                // nếu current rơi vào block bận
                                if ($current_end_clearning->gt($busy['start'])) {

                                        $current_end_clearning  =  $busy['start']->copy();
                                }
                        }


                        if (($current_end_clearning->copy()->subMinutes($beforeIntervalMinutes  +  $afterIntervalMinutes))->lt($start_date)) {

                                return  false;
                        }


                        // kiểm tra tank ở vị trí cuối cùng (ngoài busylist)
                        if ($requireTank  ==  true) {

                                $bestEnd  =  $current_end_clearning->copy()->subMinutes($afterIntervalMinutes);

                                $bestStart  =  $bestEnd->copy()->subMinutes($beforeIntervalMinutes);

                                $overlapTankCount  =  DB::table($stage_plan_table)
                                        ->whereNotNull('start')
                                        ->where('tank',  1)
                                        ->where('stage_code',  3)
                                        ->where('start',  '<',  $bestEnd)
                                        ->where('end',  '>',  $bestStart)
                                        ->count();

                                if ($overlapTankCount  >=  $maxTank) {

                                        // $current_end_clearning = $bestStart->copy()->subMinutes(15);
                                        $current_end_clearning  =  $bestStart->copy()->addMinutes($beforeIntervalMinutes  +  $time_clearning_tank);

                                        $tryCount++;

                                        if ($tryCount  >  100)  return  false;

                                        continue;
                                        // thử lại
                                }
                        }


                        if ($requireAHU  ==  true  &&  $AHU_group  ==  true) {


                                $bestEnd  =  $current_end_clearning->copy()->subMinutes($afterIntervalMinutes);

                                $bestStart  =  $bestEnd->copy()->subMinutes($beforeIntervalMinutes);


                                $overlapAHUCount  =  DB::table($stage_plan_table)
                                        ->whereNotNull('start')
                                        ->where('stage_code',  7)
                                        ->where('keep_dry',  1)
                                        ->where('AHU_group',  $AHU_group)
                                        ->where('start',  '<',  $bestEnd)
                                        ->where('end',  '>',  $bestStart)
                                        ->count();


                                if ($overlapAHUCount  >=  $maxTank) {

                                        // $current_end_clearning = $bestStart->copy()->subMinutes(15);
                                        $current_end_clearning  =  $bestStart->copy()->addMinutes($beforeIntervalMinutes);

                                        $tryCount++;

                                        if ($tryCount  >  100)  return  false;

                                        continue;
                                        // thử lại
                                }
                        }


                        return  $current_end_clearning;
                }
        }


        ///////// Sắp Lịch Ngược ////////
        // protected function sheduleNotCampaing_BW ($task, $stageCode,  int $waite_time = 0,  ?Carbon $start_date = null, ?string $Line = null){

        //                 $now = Carbon::now();
        //                 $minute = $now->minute;
        //                 $roundedMinute = ceil($minute / 15) * 15;
        //                 if ($roundedMinute == 60) {
        //                         $now->addHour();
        //                         $roundedMinute = 0;
        //                 }
        //                 $now->minute($roundedMinute)->second(0)->microsecond(0);

        //                 // Gom tất cả candidate time vào 1 mảng
        //                 $candidates [] = $now;
        //                 $candidates[] = $start_date;

        //                 // Nếu có after_weigth_date
        //                 if ($stageCode <=6){
        //                         if (!empty($task->after_weigth_date)) {$candidates[] = Carbon::parse($task->after_weigth_date);}
        //                         if (!empty($task->allow_weight_before_date)) {$candidates[] = Carbon::parse($task->allow_weight_before_date);}
        //                 }else {
        //                         if (!empty($task->after_parkaging_date)) {$candidates[] = Carbon::parse($task->after_parkaging_date);}
        //                 }

        //                 if ($task->predecessor_code != null){
        //                         $pred = DB::table('stage_plan')
        //                         ->where('code', $task->predecessor_code)->first();
        //                         if ($pred){
        //                                 $candidates[] = Carbon::parse($pred->end)->addMinutes($waite_time);
        //                         }
        //                 }

        //                 // Lấy max
        //                 $earliestStart = collect($candidates)->max();

        //                 // Chọn Phòng SX
        //                 if ($task->required_room_code != null || $Line != null ){

        //                         if ($task->required_room_code != null){
        //                                 $room_code = $task->required_room_code;
        //                         }else{
        //                                 $room_code = $Line;
        //                         }

        //                         $room_id =  DB::table('room')->where('code', $room_code)->value('id');

        //                         $rooms = DB::table('quota')->select('room_id',
        //                                 DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                 DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                 DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                 DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes'))
        //                                 ->when($task->stage_code <= 6, function ($query) use ($task) {
        //                                                 return $query->where('intermediate_code', $task->intermediate_code);
        //                                 }, function ($query) use ($task) {
        //                                         return $query->where('finished_product_code', $task->finished_product_code)
        //                                                         ->where('intermediate_code', $task->intermediate_code);
        //                                 })
        //                                 ->where('room_id', $room_id)
        //                                 ->get();
        //                 }else{
        //                                 if ($task->code_val !== null && $task->stage_code == 3 && isset($parts[1]) && $parts[1] > 1) {
        //                                 $code_val_first = $parts[0] . '_1';

        //                                 $room_id_first = DB::table("stage_plan as sp")
        //                                         ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
        //                                         ->where('code_val', $code_val_first)
        //                                         ->where('stage_code', $task->stage_code)
        //                                         ->first();

        //                                 if ($room_id_first) {
        //                                         $rooms = DB::table('quota')
        //                                         ->select(
        //                                                 'room_id',
        //                                                 DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                                 DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                                 DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                                 DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
        //                                         )
        //                                         ->when($task->stage_code <= 6, function ($query) use ($task) {
        //                                                 return $query->where('intermediate_code', $task->intermediate_code);
        //                                         }, function ($query) use ($task) {
        //                                                 return $query->where('finished_product_code', $task->finished_product_code);
        //                                         })
        //                                         ->where('active', 1)
        //                                         ->where('room_id', $room_id_first->resourceId)
        //                                         ->get();

        //                                 } else {

        //                                         $rooms = DB::table('quota')->select('room_id',
        //                                                         DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                                         DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                                         DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                                         DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
        //                                                 )
        //                                         ->when($task->stage_code <= 6, function ($query) use ($task) {
        //                                                 return $query->where('intermediate_code', $task->intermediate_code);
        //                                         }, function ($query) use ($task) {
        //                                                 return $query->where('finished_product_code', $task->finished_product_code);
        //                                         })
        //                                         ->where('active', 1)
        //                                         ->where('stage_code', $task->stage_code)
        //                                         ->get();

        //                                 }
        //                                 }
        //                                 elseif ($task->code_val !== null && $task->stage_code > 3 && isset($parts[1]) && $parts[1] > 1) {
        //                                         $code_val_first = $parts[0];

        //                                         $room_id_first = DB::table("stage_plan as sp")
        //                                         ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
        //                                         ->where(DB::raw("SUBSTRING_INDEX(pm.code_val, '_', 1)"), '=', $parts[0])
        //                                         ->where('sp.stage_code', $task->stage_code)
        //                                         ->whereNotNull('start')
        //                                         ->get();

        //                                         if ($room_id_first) {

        //                                                 $rooms = DB::table('quota')
        //                                                 ->select(
        //                                                         'room_id',
        //                                                         DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                                         DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                                         DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                                         DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
        //                                                         )
        //                                                         ->when($task->stage_code <= 6, function ($query) use ($task) {
        //                                                         return $query->where('intermediate_code', $task->intermediate_code);
        //                                                         }, function ($query) use ($task) {
        //                                                         return $query->where('finished_product_code', $task->finished_product_code);
        //                                                         })
        //                                                 ->where('active', 1)
        //                                                 ->where('stage_code', $task->stage_code)
        //                                                 ->get();


        //                                                 if ($rooms->count () > $room_id_first->count ()) {
        //                                                         foreach ($room_id_first as $first) {
        //                                                                 $rooms->where('room_id', '!=', $first->resourceId);
        //                                                         }
        //                                                 }

        //                                         } else {
        //                                                 $rooms = DB::table('quota')->select('room_id',
        //                                                                 DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                                                 DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                                                 DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                                                 DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
        //                                                         )
        //                                                 ->when($task->stage_code <= 6, function ($query) use ($task) {
        //                                                         return $query->where('intermediate_code', $task->intermediate_code);
        //                                                 }, function ($query) use ($task) {
        //                                                         return $query->where('finished_product_code', $task->finished_product_code);
        //                                                 })
        //                                                 ->where('active', 1)
        //                                                 ->where('stage_code', $task->stage_code)
        //                                                 ->get();
        //                                         }

        //                                 }else {
        //                                         $rooms = DB::table('quota')->select('room_id',
        //                                                         DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                                         DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                                         DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                                         DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
        //                                                 )
        //                                         ->when($task->stage_code <= 6, function ($query) use ($task) {
        //                                                 return $query->where('intermediate_code', $task->intermediate_code);
        //                                         }, function ($query) use ($task) {
        //                                                 return $query->where('finished_product_code', $task->finished_product_code);
        //                                         })
        //                                         ->where('active', 1)
        //                                         ->where('stage_code', $task->stage_code)
        //                                         ->get();
        //                                 }
        //                 }
        //                 // phòng phù hợp (quota)
        //                 $bestRoom = null;
        //                 $bestStart = null;

        //                 //Tim phòng tối ưu
        //                 foreach ($rooms as $room) {
        //                         $intervalTimeMinutes = (float) $room->p_time_minutes + (float) $room->m_time_minutes;
        //                         $C2_time_minutes =  (float) $room->C2_time_minutes;


        //                         $candidateStart = $this->findLatestSlot(
        //                                 $room->room_id,
        //                                 $latestEnd,
        //                                 $beforeIntervalMinutes,
        //                                 $afterIntervalMinutes,
        //                                 60,
        //                                 $start_date,
        //                                 $task->tank,
        //                                 $task->keep_dry,
        //                                 2,
        //                                 $stage_plan_table
        //                         );

        //                         if ($bestStart === null || $candidateStart->lt($bestStart)) {
        //                                 $bestRoom = $room->room_id;
        //                                 $bestStart = $candidateStart;
        //                                 $bestEnd = $bestStart->copy()->addMinutes($intervalTimeMinutes);
        //                                 $start_clearning =  $bestEnd->copy();
        //                                 $end_clearning =  $bestStart->copy()->addMinutes($intervalTimeMinutes +  $C2_time_minutes);
        //                         }

        //                 }


        //                 //if ($this->work_sunday == false) {
        //                         //Giả sử $bestStart là Carbon instance

        //                         //$startOfSunday = (clone $bestStart)->startOfWeek()->addDays(6)->setTime(6, 0, 0); // CN 6h sáng
        //                         //$endOfPeriod   = (clone $startOfSunday)->addDay()->setTime(6, 0, 0);   // T2 tuần kế tiếp 6h sáng

        //                         // if ()
        //                         // $pred_end = DB::table('stage_plan')->where('code', $task->predecessor_code)->value('end');
        //                         // if (isset($pred_end) && $pred_end != null && $pred_end > $bestStart) {$bestStart = Carbon::parse($pred_end);}

        //                         //$bestStart = $this->skipOffTime($bestStart, $this->offDate, $bestRoom);

        //                         // if ($bestStart->between($startOfSunday, $endOfPeriod)) {
        //                         //         $bestStart = $endOfPeriod->copy();
        //                         //         $bestEnd = $bestStart->copy()->addMinutes($intervalTimeMinutes);
        //                         //         $start_clearning =  $bestEnd->copy();
        //                         //         $end_clearning =  $bestStart->copy()->addMinutes($intervalTimeMinutes +  $C2_time_minutes);


        //                         // }else if ($bestEnd->between($startOfSunday, $endOfPeriod)) {
        //                         //         $bestEnd = $bestEnd->copy()->addMinutes(1440);
        //                         //         $start_clearning =  $bestEnd->copy();
        //                         //         $end_clearning =  $bestStart->copy()->addMinutes($intervalTimeMinutes +  $C2_time_minutes);
        //                         // }

        //                         $bestEnd = $this->addWorkingMinutes ( $bestStart->copy(), (float) $intervalTimeMinutes, $bestRoom, $this->work_sunday);
        //                         $start_clearning = $bestEnd->copy();
        //                         $end_clearning = $this->addWorkingMinutes ( $start_clearning->copy(), (float) $C2_time_minutes, $bestRoom, $this->work_sunday);                       


        //                         // if (isset($start_clearning) &&  $start_clearning->between($startOfSunday, $endOfPeriod)) {
        //                         //         $start_clearning =  $endOfPeriod->copy();
        //                         //         $end_clearning =  $start_clearning->copy()->addMinutes($C2_time_minutes);

        //                         // }else if ($end_clearning->between($startOfSunday, $endOfPeriod)) {
        //                         //                 $end_clearning =  $end_clearning->copy()->addMinutes(1440);
        //                         // }

        //                 //}

        //                 $this->saveSchedule(
        //                                 null,
        //                                 $task->id,
        //                                 $bestRoom,
        //                                 $bestStart,
        //                                 $bestEnd,
        //                                 $start_clearning,
        //                                 $end_clearning,
        //                                 2,
        //                                 1,

        //                 );

        //         // Làm liên tục các công cộng sau
        //         $nextTasks = collect();
        //         $next_stage_code = isset($task->nextcessor_code)
        //                         ? (int) (explode('_', $task->nextcessor_code)[1] ?? 0)
        //                         : 0;

        //         if ($task->nextcessor_code && $next_stage_code &&  $next_stage_code <= $this->max_Step && $task->immediately ){     

        //                 $nextTasks = DB::table("stage_plan as sp")
        //                         ->select(
        //                                 'sp.id',
        //                                 'sp.plan_master_id',
        //                                 'sp.product_caterogy_id',
        //                                 'sp.predecessor_code',
        //                                 'sp.nextcessor_code',
        //                                 'sp.campaign_code',
        //                                 'sp.code',
        //                                 'sp.stage_code',
        //                                 'sp.campaign_code',
        //                                 'sp.tank',
        //                                 'sp.keep_dry',
        //                                 'sp.order_by',
        //                                 'sp.required_room_code',
        //                                 'sp.immediately',

        //                                 'plan_master.batch',
        //                                 'plan_master.is_val',
        //                                 'plan_master.code_val',
        //                                 'plan_master.expected_date',
        //                                 'plan_master.batch',

        //                                 'plan_master.after_weigth_date',
        //                                 'plan_master.after_parkaging_date',
        //                                 'plan_master.allow_weight_before_date',

        //                                 'finished_product_category.product_name_id',
        //                                 'finished_product_category.market_id',
        //                                 'finished_product_category.finished_product_code',
        //                                 'finished_product_category.intermediate_code',
        //                                 'product_name.name',
        //                                 'market.code as market'
        //                         )
        //                         ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
        //                         ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
        //                         ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
        //                         ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
        //                         ->where('sp.code', $task->nextcessor_code)
        //                         ->where('sp.finished',0)
        //                         ->where('sp.active',1)
        //                         ->when($stageCode == 7, function ($q) {
        //                                $q->whereNotNull('plan_master.after_parkaging_date');
        //                         })
        //                         ->where('sp.deparment_code', session('user')['production_code'])
        //                 ->first();

        //                 if ($nextTasks){
        //                        $this->sheduleNotCampaing(
        //                                 $nextTasks,
        //                                 $next_stage_code,
        //                                 $waite_time,
        //                                 $bestEnd,
        //                                 null
        //                         );

        //                 }





        //         }


        // }

        // protected function scheduleCampaign_BW( $campaignTasks, $stageCode, int $waite_time = 0, ?Carbon $start_date = null , ?string $Line = null,  ?float $totalTimeCampaign = 0){

        //         $firstTask = $campaignTasks->first();
        //         $now = Carbon::now();
        //         $minute = $now->minute;
        //         $roundedMinute = ceil($minute / 15) * 15;
        //         if ($roundedMinute == 60) {
        //                 $now->addHour();
        //                 $roundedMinute = 0;
        //         }
        //         $now->minute($roundedMinute)->second(0)->microsecond(0);

        //         // Gom tất cả candidate time vào 1 mảng
        //         $candidates [] = $now;
        //         $candidates[] = $start_date;

        //         // Nếu có after_weigth_date
        //         if ($stageCode <=6){
        //                 if (!empty($firstTask->after_weigth_date)) {$candidates[] = Carbon::parse($firstTask->after_weigth_date);}
        //                 if (!empty($task->allow_weight_before_date))  {$candidates[] = Carbon::parse($firstTask->allow_weight_before_date);}
        //         }else {
        //                 if (!empty($firstTask->after_parkaging_date) ) {$candidates[] = Carbon::parse($firstTask->after_parkaging_date);}
        //         }

        //         //$pre_campaign_first_batch_end = [];
        //         $pre_campaign_codes = [];

        //         foreach ($campaignTasks as $campaignTask) {

        //                 $pred = DB::table('stage_plan')->where('code', $campaignTask->predecessor_code)->first();

        //                 if ($pred ) {

        //                         $code = $pred->campaign_code;

        //                         if (!in_array($code, $pre_campaign_codes) && $code != null) {
        //                                 $pre_campaign_codes [] = $code ;

        //                                 $pre_campaign_batch = DB::table('stage_plan')
        //                                 ->where('campaign_code', $code)
        //                                 ->orderBy('start', 'asc')
        //                                 ->get();

        //                                 $pre_campaign_first_batch =  $pre_campaign_batch->first();
        //                                 $pre_campaign_last_batch =  $pre_campaign_batch->last();

        //                                 $prevCycle = DB::table('quota')
        //                                 ->selectRaw('AVG(TIME_TO_SEC(m_time)/60) as avg_m_time_minutes')
        //                                 ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {
        //                                         return $query->where('intermediate_code', $firstTask->intermediate_code);
        //                                 }, function ($query) use ($firstTask) {
        //                                         return $query->where('finished_product_code', $firstTask->finished_product_code);
        //                                 })
        //                                 ->where('active', 1)
        //                                 ->where('stage_code', $pre_campaign_first_batch->stage_code)
        //                                 ->value('avg_m_time_minutes');

        //                                 $currCycle = DB::table('quota')
        //                                         ->selectRaw('AVG(TIME_TO_SEC(m_time)/60) as avg_m_time_minutes')
        //                                         ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {
        //                                                 return $query->where('intermediate_code', $firstTask->intermediate_code);
        //                                         }, function ($query) use ($firstTask) {
        //                                                 return $query->where('finished_product_code', $firstTask->finished_product_code);
        //                                         })
        //                                         ->where('active', 1)
        //                                         ->where('stage_code', $campaignTask->stage_code)
        //                                 ->value('avg_m_time_minutes');

        //                                 $maxCount = max($campaignTasks->count(), $pre_campaign_batch->count());

        //                                 if ($currCycle && $currCycle >= $prevCycle){
        //                                         $candidates[] = Carbon::parse($pred->end)->addMinutes($waite_time);
        //                                 }else  {

        //                                         $hasImmediately = collect($campaignTasks)->contains('immediately', 1);

        //                                         if ($campaignTask->immediately == false && $hasImmediately){
        //                                                 $candidates[] = Carbon::parse($pre_campaign_last_batch->end)->subMinutes(($campaignTasks->count() - 1) * $currCycle);
        //                                                 $candidates[] = Carbon::parse($pred->end)->addMinutes($waite_time + $maxCount * ($prevCycle - $currCycle));
        //                                         }
        //                                 }
        //                         }

        //                         if ($code == null){
        //                                 $candidates [] =  Carbon::parse($pred->end);
        //                         }
        //                 }
        //         }
        //         // Lấy max
        //         $earliestStart = collect($candidates)->max();

        //         // phòng phù hợp (quota)
        //         if ($firstTask->required_room_code != null || $Line != null ){
        //                 if ($firstTask->required_room_code != null){
        //                         $room_code = $firstTask->required_room_code;
        //                 }else{
        //                         $room_code = $Line;
        //                 }

        //                 $room_id =  DB::table('room')->where('code', $room_code)->value('id');

        //                 $rooms = DB::table('quota')->select('room_id',
        //                         DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                         DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                         DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                         DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes'))
        //                         ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {
        //                                         return $query->where('intermediate_code', $firstTask->intermediate_code);
        //                         }, function ($query) use ($firstTask) {
        //                                 return $query->where('finished_product_code', $firstTask->finished_product_code)
        //                                                 ->where('intermediate_code', $firstTask->intermediate_code);
        //                         })
        //                         ->where('room_id', $room_id)
        //                         ->get();
        //         }else{
        //                 if ($firstTask->code_val !== null && $firstTask->stage_code == 3 && isset($parts[1]) && $parts[1] > 1) {

        //                         $code_val_first = $parts[0] . '_1';

        //                         $room_id_first = DB::table("stage_plan as sp")
        //                                 ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
        //                                 ->where('code_val', $code_val_first)
        //                                 ->where('stage_code', $firstTask->stage_code)
        //                                 ->first();

        //                         if ($room_id_first) {
        //                                 $rooms = DB::table('quota')
        //                                 ->select(
        //                                         'room_id',
        //                                         DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
        //                                 )
        //                                 ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {
        //                                         return $query->where('intermediate_code', $firstTask->intermediate_code);
        //                                 }, function ($query) use ($firstTask) {
        //                                         return $query->where('finished_product_code', $firstTask->finished_product_code);
        //                                 })
        //                                 ->where('active', 1)
        //                                 ->where('room_id', $room_id_first->resourceId)
        //                                 ->get();

        //                         } else {

        //                                 $rooms = DB::table('quota')->select('room_id',
        //                                         DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
        //                                         )
        //                                 ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {
        //                                         return $query->where('intermediate_code', $firstTask->intermediate_code);
        //                                 }, function ($query) use ($firstTask) {
        //                                         return $query->where('finished_product_code', $firstTask->finished_product_code);
        //                                 })
        //                                 ->where('active', 1)
        //                                 ->where('stage_code', $firstTask->stage_code)
        //                                 ->get();

        //                                 }
        //                 }
        //                 elseif ($firstTask->code_val !== null && $firstTask->stage_code > 3 && isset($parts[1]) && $parts[1] > 1) {

        //                                 $code_val_first = $parts[0];

        //                                 $room_id_first = DB::table("stage_plan as sp")
        //                                 ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
        //                                 ->where(DB::raw("SUBSTRING_INDEX(pm.code_val, '_', 1)"), '=', $parts[0])
        //                                 ->where('sp.stage_code', $firstTask->stage_code)
        //                                 ->whereNotNull('start')
        //                                 ->get();

        //                                 if ($room_id_first) {

        //                                         $rooms = DB::table('quota')
        //                                         ->select(
        //                                                 'room_id',
        //                                                 DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                                 DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                                 DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                                 DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
        //                                                 )
        //                                                 ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {
        //                                                 return $query->where('intermediate_code', $firstTask->intermediate_code);
        //                                                 }, function ($query) use ($firstTask) {
        //                                                 return $query->where('finished_product_code', $firstTask->finished_product_code);
        //                                                 })
        //                                         ->where('active', 1)
        //                                         ->where('stage_code', $firstTask->stage_code)
        //                                         ->get();


        //                                         if ($rooms->count () > $room_id_first->count ()) {
        //                                                 foreach ($room_id_first as $first) {
        //                                                         $rooms->where('room_id', '!=', $first->resourceId);
        //                                                 }
        //                                         }

        //                                 } else {
        //                                         $rooms = DB::table('quota')->select('room_id',
        //                                                         DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                                         DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                                         DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                                         DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
        //                                                 )
        //                                         ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {
        //                                                 return $query->where('intermediate_code', $firstTask->intermediate_code);
        //                                         }, function ($query) use ($firstTask) {
        //                                                 return $query->where('finished_product_code', $firstTask->finished_product_code);
        //                                         })
        //                                         ->where('active', 1)
        //                                         ->where('stage_code', $firstTask->stage_code)
        //                                         ->get();
        //                                 }

        //                 }else {
        //                         $rooms = DB::table('quota')->select('room_id',
        //                                                 DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                                 DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                                 DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                                 DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
        //                                         )
        //                                 ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {
        //                                         return $query->where('intermediate_code', $firstTask->intermediate_code);
        //                                 }, function ($query) use ($firstTask) {
        //                                         return $query->where('finished_product_code', $firstTask->finished_product_code)
        //                                                       ->where('intermediate_code', $firstTask->intermediate_code);
        //                                 })
        //                                 ->where('active', 1)
        //                                 ->where('stage_code', $firstTask->stage_code)
        //                         ->get();
        //                 }
        //         }

        //         // Liên hê giữa PC và THT 
        //         if ( $stageCode == 4 &&  $firstTask->predecessor_code &&  explode('_', $firstTask->predecessor_code)[1] == 3 && $rooms->count() > 1) {
        //                 $rooms_bkc = $rooms;

        //                 $resourceId_prev = DB::table('stage_plan')
        //                         ->where('code', $firstTask->predecessor_code)
        //                         ->value('resourceId');

        //                 $rooms = $rooms->filter(function ($room) use ($resourceId_prev) {

        //                         if (in_array($resourceId_prev, [6, 7])) {
        //                                 return in_array($room->room_id, [13, 14]);
        //                         }

        //                         if ($resourceId_prev == 10) {
        //                                 return $room->room_id == 17;
        //                         }

        //                         return true;

        //                 })->values();

        //                 // ✅ rollback nếu filter làm rỗng
        //                 if ($rooms->isEmpty()) {
        //                         $rooms = $rooms_bkc;
        //                 }
        //         }

        //         $bestRoom = null;
        //         $bestStart = null;


        //         //Tim phòng tối ưu
        //         foreach ($rooms as $room) {

        //                 $totalMunites = $room->p_time_minutes + ($campaignTasks->count() * $room->m_time_minutes)
        //                         + ($campaignTasks->count()-1) * ($room->C1_time_minutes)
        //                         + $room->C2_time_minutes;

        //                 if ( $totalTimeCampaign > 0 && $totalTimeCampaign > $totalMunites){
        //                         $totalMunites = $totalTimeCampaign;
        //                 }

        //                 $candidateStart = $this->findEarliestSlot2(
        //                         $room->room_id,
        //                         $earliestStart,
        //                         $totalMunites,
        //                         0,
        //                         $firstTask->tank,
        //                         $firstTask->keep_dry,
        //                         'stage_plan',
        //                         2,
        //                         60
        //                 );
        //                 if ($bestStart === null || $candidateStart->lt($bestStart)) {
        //                         $bestRoom = $room;
        //                         $bestStart = $candidateStart;
        //                 }
        //         }


        //         // Lưu từng batch
        //         $counter = 1;
        //         // Lưu Sự Kiện
        //         $firstBatachStart = null;
        //         $lastBatachEnd = null; // dung cho chạy công đoạn tiếp theo
        //         $totalTimeCampaign = 0;// dung cho chạy công đoạn tiếp theo

        //         foreach ($campaignTasks as $task) {


        //                 // if ($this->work_sunday == false) {
        //                 //         $startOfSunday = (clone $bestStart)->startOfWeek()->addDays(6)->setTime(6, 0, 0);
        //                 //         $endOfPeriod   = (clone $startOfSunday)->addDay()->setTime(6, 0, 0);
        //                 // }

        //                 $pred_end = DB::table('stage_plan')->where('code', $task->predecessor_code)->value('end');

        //                 if (isset($pred_end) && $pred_end != null && $pred_end > $bestStart) {$bestStart = Carbon::parse($pred_end);}
        //                 $bestStart = $this->skipOffTime($bestStart, $this->offDate, $bestRoom->room_id);

        //                 if ($counter == 1) {
        //                         //$bestEnd = $bestStart->copy()->addMinutes((float) $bestRoom->p_time_minutes + $bestRoom->m_time_minutes);
        //                         // if ($bestEnd->between($startOfSunday, $endOfPeriod)) {
        //                         //         $bestEnd = $bestEnd->addMinutes(1440);

        //                         // }
        //                         // $bestEndCleaning = $bestEnd->copy()->addMinutes((float)$bestRoom->C1_time_minutes); //Lô đâu tiên chiến dịch

        //                         $bestEnd = $this->addWorkingMinutes ( $bestStart->copy(), (float) $bestRoom->p_time_minutes + (float) $bestRoom->m_time_minutes, $bestRoom->room_id, $this->work_sunday);
        //                         $start_clearning = $bestEnd->copy();
        //                         $bestEndCleaning = $this->addWorkingMinutes ( $start_clearning->copy(), (float) $bestRoom->C1_time_minutes, $bestRoom->room_id, $this->work_sunday);
        //                         $clearningType = 1;
        //                         $firstBatachStart = $bestEnd->copy();

        //                 }elseif ($counter == $campaignTasks->count()){

        //                         // $bestEnd = $bestStart->copy()->addMinutes((float) $bestRoom->m_time_minutes);
        //                         // $start_clearning = $bestEnd->copy();
        //                         // $bestEndCleaning = $bestEnd->copy()->addMinutes((float)$bestRoom->C2_time_minutes); //Lô cuối chiến dịch


        //                         // $bestEnd = $this->addWorkingMinutes ( $bestEnd, 8 * 60 , $bestRoom->room_id, $this->work_sunday);
        //                         // $start_clearning =  $bestEnd->copy();
        //                         // $bestEndCleaning =  $start_clearning->copy()->addMinutes((float)$bestRoom->C2_time_minutes);


        //                         ///

        //                         // if ($bestEnd->between($startOfSunday, $endOfPeriod)) {
        //                         //         $bestEnd = $bestEnd->addMinutes(1440);
        //                         //         $start_clearning =  $bestEnd->copy();
        //                         //         $bestEndCleaning =  $start_clearning->copy()->addMinutes((float)$bestRoom->C2_time_minutes);
        //                         // }else if ($bestEndCleaning->between($startOfSunday, $endOfPeriod)) {
        //                         //         $bestEndCleaning = $bestEndCleaning->addMinutes(1440);;
        //                         // }

        //                         $bestEnd = $this->addWorkingMinutes ( $bestStart->copy(), (float) $bestRoom->m_time_minutes, $bestRoom->room_id, $this->work_sunday);
        //                         $start_clearning = $bestEnd->copy();
        //                         $bestEndCleaning = $this->addWorkingMinutes ( $start_clearning->copy(), (float) $bestRoom->C1_time_minutes, $bestRoom->room_id, $this->work_sunday);                       

        //                         $clearningType = 2;
        //                         $lastBatachEnd = $bestEnd->copy();
        //                 }else {
        //                         $bestEnd = $this->addWorkingMinutes ( $bestStart->copy(), (float) $bestRoom->m_time_minutes, $bestRoom->room_id, $this->work_sunday);
        //                         $start_clearning = $bestEnd->copy();
        //                         $bestEndCleaning = $this->addWorkingMinutes ( $start_clearning->copy(), (float) $bestRoom->C1_time_minutes, $bestRoom->room_id, $this->work_sunday);
        //                         $clearningType = 1;

        //                         //$bestEnd = $bestStart->copy()->addMinutes((float) $bestRoom->m_time_minutes);
        //                         // if ($bestEnd->between($startOfSunday, $endOfPeriod)) {
        //                         //         $bestEnd = $bestEnd->addMinutes(1440);       
        //                         // }

        //                 }

        //                 $this->saveSchedule(
        //                         null, //."-".$task->market,
        //                         $task->id,
        //                         $bestRoom->room_id,
        //                         $bestStart,
        //                         $bestEnd,
        //                         $start_clearning,
        //                         $bestEndCleaning,
        //                         $clearningType,
        //                         1,

        //                 );
        //                 $counter++;
        //                 $bestStart = $bestEndCleaning->copy();
        //         }

        //         $totalTimeCampaign = abs($firstBatachStart->diffInMinutes($lastBatachEnd));
        //         // Làm liên tục các công cộng sau
        //         $nextcessor_codes = collect();
        //         $nextTasks = collect();
        //         $next_stage_code = isset($firstTask->nextcessor_code)
        //                         ? (int) (explode('_', $firstTask->nextcessor_code)[1] ?? 0)
        //                         : 0;
        //         $hasImmediately = collect($campaignTasks)->contains('immediately', 1);

        //         if ($next_stage_code <= $this->max_Step  && $hasImmediately){   //&& $firstTask->immediately == 1

        //                 $nextcessor_codes = $campaignTasks->pluck('nextcessor_code');

        //                 $nextTasks =  DB::table("stage_plan as sp")
        //                         ->select('sp.id',
        //                         'sp.plan_master_id',
        //                         'sp.product_caterogy_id',
        //                         'sp.predecessor_code',
        //                         'sp.nextcessor_code',
        //                         'sp.campaign_code',
        //                         'sp.code',
        //                         'sp.stage_code',
        //                         'sp.campaign_code',
        //                         'sp.tank',
        //                         'sp.keep_dry',
        //                         'sp.order_by',
        //                         'sp.required_room_code',
        //                         'sp.immediately',
        //                         'plan_master.batch',
        //                         'plan_master.is_val',
        //                         'plan_master.code_val',
        //                         'plan_master.expected_date',
        //                         'plan_master.batch',
        //                         'plan_master.after_weigth_date',
        //                         'plan_master.after_parkaging_date',
        //                         'finished_product_category.product_name_id',
        //                         'finished_product_category.market_id',
        //                         'finished_product_category.finished_product_code',
        //                         'finished_product_category.intermediate_code',
        //                         'product_name.name',
        //                         'market.code as market',
        //                         'prev.start as prev_start' 
        //                         )
        //                         ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
        //                         ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
        //                         ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
        //                         ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
        //                         ->leftJoin('stage_plan as prev', 'prev.code', '=', 'sp.predecessor_code')
        //                         ->whereIn('sp.code', $nextcessor_codes)
        //                         //->where('sp.stage_code', $nextcessor_code)
        //                         ->where('sp.active',1)
        //                         //->whereNotNull('plan_master.after_weigth_date')
        //                         ->where('sp.deparment_code', session('user')['production_code'])
        //                         ->orderBy('prev.start', 'asc')
        //                 ->get();


        //                if ($nextTasks->isNotEmpty()) {
        //                         $this->scheduleCampaign(
        //                                 $nextTasks,
        //                                 $next_stage_code,
        //                                 $waite_time,
        //                                 $start_date,
        //                                 null,
        //                                 $totalTimeCampaign,
        //                         );
        //                 }


        //         }

        // }
        // public function scheduleStartBackward( $start_date, $waite_time) {


        //         $planMasters = DB::table('plan_master as pm')
        //                 ->leftJoin('finished_product_category', 'pm.product_caterogy_id', 'finished_product_category.id')
        //                 ->leftJoin('intermediate_category', 'finished_product_category.intermediate_code', 'intermediate_category.intermediate_code')
        //                 ->where ('quarantine_total','>',0)
        //                 ->whereIn('pm.id', function ($query)  {
        //                         $query->select(DB::raw('DISTINCT sp.plan_master_id'))
        //                         ->from("stage_plan as sp")
        //                         ->whereNull('sp.start')
        //                         ->where('sp.active', 1)
        //                         ->where('sp.finished', 0)
        //                         ->where('sp.deparment_code', session('user')['production_code']);

        //                 })
        //                 ->orderBy('pm.expected_date', 'asc')
        //                 ->orderBy('pm.level', 'asc')
        //                 ->orderByRaw('batch + 0 ASC')
        //         ->pluck('pm.id');


        //         foreach ($planMasters as $planId) {

        //                 $check_plan_master_id_complete =  DB::table("stage_plan as sp")
        //                 ->where ('plan_master_id', $planId)
        //                 ->whereNull ('sp.start')
        //                 ->where ('sp.active', 1)
        //                 ->where ('sp.finished', 0)
        //                 ->where('sp.deparment_code', session('user')['production_code'])
        //                 ->exists();

        //                 if ($check_plan_master_id_complete){
        //                         $this->schedulePlanBackwardPlanMasterId($planId, $work_sunday, 0, $waite_time , $start_date); 
        //                         //$this->schedulePlanForwardPlanMasterId ($planId, $waite_time, $start_date);
        //                 }
        //                 $this->order_by++;
        //         }

        // } 

        ///////// Bỏ ////////
        // protected function schedulePlanBackwardPlanMasterId($plan_master_id,bool $working_sunday = false,int $bufferDate, $waite_time, Carbon $start_date) {
        //         $stage_plan_ids = [];

        //         //$stage_plan_ids_null = [];

        //         if (session('fullCalender')['mode'] === 'offical') {
        //                 $stage_plan_table = 'stage_plan';
        //         } else {
        //                 $stage_plan_table = 'stage_plan_temp';
        //         }

        //         // toàn bộ các row trong stage_plan cùng plan_master_id của các công đoạn từ ĐG - PC
        //         $tasks = DB::table("$stage_plan_table as sp")
        //         ->select (
        //                 'sp.id',
        //                 'sp.plan_master_id',
        //                 'sp.product_caterogy_id',
        //                 'sp.predecessor_code',
        //                 'sp.nextcessor_code',
        //                 'sp.campaign_code',
        //                 'sp.code',
        //                 'sp.stage_code',
        //                 'sp.campaign_code',
        //                 'sp.tank',
        //                 'sp.keep_dry',
        //                 'fc.finished_product_code',
        //                 'fc.intermediate_code',
        //                 'pm.is_val',
        //                 'pm.code_val',
        //                 'pm.expected_date',
        //                 'pm.level',
        //                 'pm.batch',
        //                 'pm.after_weigth_date',
        //                 'pm.before_weigth_date',
        //                 'pm.after_parkaging_date',
        //                 'pm.before_parkaging_date',
        //                 'mk.code as market',
        //                 'pn.name',
        //         )
        //         ->leftJoin('finished_product_category as fc', 'sp.product_caterogy_id', '=', 'fc.id')
        //         ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
        //         ->leftJoin('product_name as pn', 'fc.product_name_id', '=', 'pn.id')
        //         ->leftJoin('market as mk', 'fc.market_id', '=', 'mk.id')
        //         ->where('plan_master_id', $plan_master_id)
        //         ->where('stage_code',">=",3)
        //         ->orderBy('stage_code', 'desc')
        //         ->get(); // 1 lô gồm tất cả các stage



        //         $latestEnd = Carbon::parse($tasks->first()->expected_date)->subDays(5 + $bufferDate); //latestEnd1


        //         //Nếu latestEnd mà nhờ hơn hoặc bằng
        //         // if ($latestEnd->lte($start_date)){
        //         //         $this->schedulePlanForwardPlanMasterId ($plan_master_id, $working_sunday, $waite_time, $start_date);
        //         //         return false;
        //         // }

        //         $nextCycle = 0; // thời gian sản xuất công đoạn trước = p_time + m_time

        //         foreach ($tasks as $task) { // Vòng lập chính duyệt qua toàn bộ các task cùng plan_master_id

        //                 // lấy được $waite_time_for_task từ $waite_time dựa vào $next_stage_code và is_val
        //                 if ($task->nextcessor_code){
        //                         $next_stage_code = explode('_', $task->nextcessor_code)[1];
        //                         if ($next_stage_code  && !$task->is_val) {
        //                                 $waite_time_for_task = $waite_time[$next_stage_code]['waite_time_nomal_batch'];
        //                         } else {
        //                                 $waite_time_for_task = $waite_time[$next_stage_code]['waite_time_val_batch'];
        //                         }
        //                 }else {$waite_time_for_task = null;}


        //                 $campaign_tasks = null;

        //                  // chứa id các row đã lưu. trường hợp các stage sau rơi và quá khứ sẽ dùng id này để xóa lịch đã sắp
        //                 if ($task->campaign_code){ // trường hợp chiến dịch
        //                          $campaign_tasks = DB::table("$stage_plan_table as sp")
        //                           ->select (
        //                                 'sp.id',
        //                                 'sp.plan_master_id',
        //                                 'sp.product_caterogy_id',
        //                                 'sp.predecessor_code',
        //                                 'sp.nextcessor_code',
        //                                 'sp.campaign_code',
        //                                 'sp.code',
        //                                 'sp.stage_code',
        //                                 'sp.tank',
        //                                 'sp.keep_dry',
        //                                 'fc.finished_product_code',
        //                                 'fc.intermediate_code',
        //                                 'pm.is_val',
        //                                 'pm.code_val',
        //                                 'pm.expected_date',
        //                                 'pm.level',
        //                                 'pm.batch',
        //                                 'pm.after_weigth_date',
        //                                 'pm.before_weigth_date',
        //                                 'pm.after_parkaging_date',
        //                                 'pm.before_parkaging_date',
        //                                 'mk.code as market',
        //                                 'pn.name')
        //                         ->leftJoin('finished_product_category as fc', 'sp.product_caterogy_id', '=', 'fc.id')
        //                         ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
        //                         ->leftJoin('product_name as pn', 'fc.product_name_id', '=', 'pn.id')
        //                         ->leftJoin('market as mk', 'fc.market_id', '=', 'mk.id')
        //                         ->where('sp.campaign_code',$task->campaign_code)
        //                         ->orderBy('expected_date', 'desc')
        //                         ->orderBy('level', 'desc')
        //                         ->orderBy('batch', 'desc')
        //                         ->get();
        //                 }

        //                 $parts = explode("_", $task->code_val);

        //                 /// Tìm Phòng Sản Xuất Thích Hợp
        //                 // Trường hợp Lô thẩm định && Công Đoạn Pha Chế && Không phải lô thẩm định thứ nhất
        //                 if ($task->code_val !== null && $task->stage_code == 3 && isset($parts[1]) && $parts[1] > 1) {
        //                         $code_val_first = $parts[0] . '_1';

        //                         $room_id_first = DB::table("$stage_plan_table as sp")
        //                                 ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
        //                                 ->where('code_val', $code_val_first)
        //                                 ->where('stage_code', $task->stage_code)
        //                         ->first();

        //                         if ($room_id_first) {
        //                                 $rooms = DB::table('quota')
        //                                 ->select(
        //                                         'room_id',
        //                                         DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
        //                                 )
        //                                 ->when($task->stage_code <= 6, function ($query) use ($task) {
        //                                         return $query->where('intermediate_code', $task->intermediate_code);
        //                                 }, function ($query) use ($task) {
        //                                         return $query->where('finished_product_code', $task->finished_product_code);
        //                                 })
        //                                 ->where('room_id', $room_id_first->resourceId)
        //                                 ->get();

        //                         } else {
        //                                 $rooms = DB::table('quota')
        //                                 ->select(
        //                                 'room_id',
        //                                 DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                 DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                 DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                 DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
        //                                 )
        //                                 ->when($task->stage_code <= 6, function ($query) use ($task) {
        //                                 return $query->where('intermediate_code', $task->intermediate_code);
        //                                 }, function ($query) use ($task) {
        //                                 return $query->where('finished_product_code', $task->finished_product_code);
        //                                 })
        //                                 ->where('stage_code', $task->stage_code)
        //                                 ->get();
        //                         }
        //                 }
        //                 // Trường hợp Lô thẩm định && Không Công Đoạn Pha Chế && Không phải lô thẩm định thứ nhất
        //                 elseif ($task->code_val !== null && $task->stage_code > 3 && isset($parts[1]) && $parts[1] > 1) {
        //                         $code_val_first = $parts[0];

        //                         $room_id_first = DB::table("$stage_plan_table as sp")
        //                         ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
        //                         ->where(DB::raw("SUBSTRING_INDEX(pm.code_val, '_', 1)"), '=', $parts[0])
        //                         ->where('sp.stage_code', $task->stage_code)
        //                         ->whereNotNull('start')
        //                         ->get();

        //                         if ($room_id_first) {

        //                                 $rooms = DB::table('quota')
        //                                 ->select(
        //                                         'room_id',
        //                                         DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
        //                                         )
        //                                         ->when($task->stage_code <= 6, function ($query) use ($task) {
        //                                         return $query->where('intermediate_code', $task->intermediate_code);
        //                                         }, function ($query) use ($task) {
        //                                         return $query->where('finished_product_code', $task->finished_product_code);
        //                                         })
        //                                 ->where('stage_code', $task->stage_code)
        //                                 ->get();


        //                                 if ($rooms->count () > $room_id_first->count ()) {
        //                                         foreach ($room_id_first as $first) {
        //                                                 $rooms->where('room_id', '!=', $first->resourceId);
        //                                         }
        //                                 }
        //                         // Không Phải lô thẩm định
        //                         } else {
        //                                 $rooms = DB::table('quota')
        //                                 ->select(
        //                                 'room_id',
        //                                 DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                 DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                 DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                 DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
        //                                 )
        //                                 ->when($task->stage_code <= 6, function ($query) use ($task) {
        //                                 return $query->where('intermediate_code', $task->intermediate_code);
        //                                 }, function ($query) use ($task) {
        //                                 return $query->where('finished_product_code', $task->finished_product_code);
        //                                 })
        //                                 ->where('stage_code', $task->stage_code)
        //                                 ->get();
        //                         }


        //                 }else {
        //                         $rooms = DB::table('quota')
        //                                 ->select(
        //                                 'room_id',
        //                                 DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                 DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                 DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                 DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
        //                                 )
        //                                 ->when($task->stage_code <= 6, function ($query) use ($task) {
        //                                 return $query->where('intermediate_code', $task->intermediate_code);
        //                                 }, function ($query) use ($task) {
        //                                 return $query->where('finished_product_code', $task->finished_product_code);
        //                                 })
        //                                 ->where('stage_code', $task->stage_code)
        //                                 ->get(); // dùng first() để đồng nhất với nhánh if
        //                 }

        //                 $count_room = 1;
        //                 $bestRoom = null;
        //                 $bestRoomId = null;
        //                 $bestStart = null;
        //                 $bestEnd = null;
        //                 $bestEndCleaning = null;
        //                 $index_campaign_tasks = null;

        //                 /// tim Phòng thich hợp
        //                 foreach ($rooms as $room) { // duyệt qua toàn bộ các room đã định mức để tìm bestroom

        //                         if ($campaign_tasks !== null){ $number_of_batch = $campaign_tasks->count();}else {$number_of_batch = 1;}

        //                         $beforeIntervalMinutes = (float) $room->p_time_minutes + (float) $room->m_time_minutes * $number_of_batch + (float) $room->C1_time_minutes * ($number_of_batch - 1);
        //                         $afterIntervalMinutes =  (float) $room->C2_time_minutes;
        //                         $currCycle = (float) $room->m_time_minutes;

        //                         if ($task->nextcessor_code != null){ // Không phải là stage cuối cùng

        //                                 $next_stage_code  = explode('_', $task->nextcessor_code)[1];
        //                                 $batch_of_next_campaign = DB::table($stage_plan_table)
        //                                         ->where('plan_master_id', $task->plan_master_id)
        //                                         ->where('stage_code', $next_stage_code)
        //                                         ->when(session('fullCalender')['mode'] === 'temp', function ($query) {return $query->where('stage_plan_temp_list_id',
        //                                                         session('fullCalender')['stage_plan_temp_list_id']);})
        //                                 ->first();

        //                                 if ($campaign_tasks === null){
        //                                         $latestEnd = Carbon::parse($batch_of_next_campaign->start); //latestEnd2
        //                                 }else {
        //                                         $nextCycle = Carbon::parse($batch_of_next_campaign->start)->diffInMinutes(Carbon::parse($batch_of_next_campaign->end));

        //                                         if ($currCycle >= $nextCycle){
        //                                                 if ($count_room == 1){ // chỉ dò $index_campaign_tasks ở lần đầu tiên
        //                                                         foreach ($campaign_tasks as $campaign_task) {
        //                                                                 $next_last_batch = DB::table($stage_plan_table)
        //                                                                 ->whereNotNull ('start')
        //                                                                 ->where('stage_code', $next_stage_code)
        //                                                                 ->where('plan_master_id', $campaign_task->plan_master_id)
        //                                                                 ->when(session('fullCalender')['mode'] === 'temp', function ($query) {
        //                                                                 return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
        //                                                                 ->first();

        //                                                                 if ($next_last_batch &&  $next_last_batch->plan_master_id !== null){break;}
        //                                                         }
        //                                                 }
        //                                         }else {

        //                                                   if ($count_room == 1){ // chỉ dò $index_campaign_tasks ở lần đầu tiên
        //                                                         foreach ($campaign_tasks->reverse() as $campaign_task) {
        //                                                                 $next_last_batch = DB::table($stage_plan_table)
        //                                                                         ->whereNotNull ('start')
        //                                                                         ->where('stage_code', $next_stage_code)
        //                                                                         ->where('plan_master_id', $campaign_task->plan_master_id)
        //                                                                         ->when(session('fullCalender')['mode'] === 'temp', function ($query) {
        //                                                                                                 return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
        //                                                                         ->first();
        //                                                                 if ($next_last_batch &&  $next_last_batch->plan_master_id !== null){
        //                                                                         break;
        //                                                                 }
        //                                                         }
        //                                                 }
        //                                         }

        //                                         $index_campaign_tasks = $campaign_tasks->search(function ($item) use ($next_last_batch) {
        //                                                                 return $item->plan_master_id == $next_last_batch->plan_master_id;});
        //                                         $latestEnd = Carbon::parse($next_last_batch->start); //latestEnd3
        //                                         $beforeIntervalMinutes = (float) $room->p_time_minutes + (float) $room->m_time_minutes * ($number_of_batch - $index_campaign_tasks) + ((float) $room->C1_time_minutes * ($number_of_batch - $index_campaign_tasks) - 1);
        //                                         $afterIntervalMinutes =  ((float) $room->m_time_minutes * ($index_campaign_tasks)) + ((float) $room->C1_time_minutes * ($index_campaign_tasks - 1)) + (float) $room->C2_time_minutes;
        //                                 }
        //                         }

        //                         if ($waite_time_for_task != null){
        //                                 $latestEnd = $latestEnd->copy()->subMinutes($waite_time_for_task);  //latestEnd4
        //                         }

        //                         if ($task->stage_code == 7 ){
        //                                 $before_parkaging_date = Carbon::parse($task->before_parkaging_date);
        //                                 if ($latestEnd->gt($before_parkaging_date)){
        //                                         $latestEnd = $before_parkaging_date;
        //                                 }
        //                         }elseif ($task->stage_code == 3) {
        //                                 $before_weigth_date = Carbon::parse($task->before_weigth_date);
        //                                 if ($latestEnd->gt($before_weigth_date)){
        //                                         $latestEnd = $before_weigth_date;
        //                                 }
        //                         }

        //                         $candidateEndClearning = $this->findLatestSlot(
        //                                 $room->room_id,
        //                                 $latestEnd,
        //                                 $beforeIntervalMinutes,
        //                                 $afterIntervalMinutes,
        //                                 60,
        //                                 $start_date,
        //                                 $task->tank,
        //                                 $task->keep_dry,
        //                                 2,
        //                                 $stage_plan_table
        //                         );

        //                        // candidateEndClearning Có vi phảm vào quá khứ không
        //                         if ($candidateEndClearning == false){
        //                                 if ($stage_plan_ids) {
        //                                         //dd ($stage_plan_ids, $this->order_by, $task);

        //                                         DB::table($stage_plan_table)
        //                                         ->whereIn('id', $stage_plan_ids)
        //                                         ->when(session('fullCalender')['mode'] === 'temp',function ($query)
        //                                         {return $query->where('stage_plan_temp_list_id',session('fullCalender')['stage_plan_temp_list_id']);})
        //                                         ->update([
        //                                                 'start'            => null,
        //                                                 'end'              => null,
        //                                                 'start_clearning'  => null,
        //                                                 'end_clearning'    => null,
        //                                                 'resourceId'       => null,
        //                                                 'title'            => null,
        //                                                 'title_clearning'  => null,
        //                                                 'schedualed'       => 0,
        //                                         ]);
        //                                 }
        //                                 //$this->schedulePlanForwardPlanMasterId ($plan_master_id, $working_sunday, $waite_time, $start_date);
        //                                 return false;
        //                         }

        //                         if ($bestEndCleaning === null || $candidateEndClearning->gt($bestEndCleaning)) {
        //                                 $bestRoom = $room;
        //                                 $bestRoomId = $room->room_id;
        //                                 $bestEndCleaning  = $candidateEndClearning;
        //                                 $bestEnd = $bestEndCleaning->copy()->subMinutes((float) $afterIntervalMinutes);
        //                                 $bestStart = $bestEnd->copy()->subMinutes((float) $beforeIntervalMinutes);
        //                         }
        //                         $count_room++;
        //                 }

        //                 /// Lưu
        //                 if ($campaign_tasks !== null){
        //                         $campaign_counter = 1;
        //                         $current_end_clearning = $candidateEndClearning;
        //                         foreach ($campaign_tasks as $campaign_task){
        //                                 if ($campaign_counter == 1) {
        //                                         $bestEndClearning = $current_end_clearning;
        //                                         $bestEnd = $bestEndClearning->copy()->subMinutes((float) $bestRoom->C2_time_minutes);
        //                                         $bestStart = $bestEnd->copy()->subMinutes((float) $bestRoom->m_time_minutes); ;
        //                                         $clearningType = 2;

        //                                 }elseif ($campaign_counter == $campaign_tasks->count()){

        //                                         $bestEndClearning = $current_end_clearning;
        //                                         $bestEnd = $bestEndClearning->copy()->subMinutes((float) $bestRoom->C1_time_minutes);
        //                                         $bestStart = $bestEnd->copy()->subMinutes((float) $bestRoom->p_time_minutes + (float) $bestRoom->m_time_minutes); ;
        //                                         $clearningType = 1;
        //                                 }else {
        //                                         $bestEndClearning = $current_end_clearning;
        //                                         $bestEnd = $bestEndClearning->copy()->subMinutes((float) $bestRoom->C1_time_minutes);
        //                                         $bestStart = $bestEnd->copy()->subMinutes((float) $bestRoom->m_time_minutes); //Lô giữa chiến dịch
        //                                         $clearningType = 1;
        //                                 }
        //                                 $title = $campaign_task->name ."- ". $campaign_task->batch ."-". $campaign_task->market;
        //                                 $this->saveSchedule(
        //                                         $title,
        //                                         $campaign_task->id,
        //                                         $bestRoomId,
        //                                         $bestStart,
        //                                         $bestEnd,
        //                                         $bestEnd,
        //                                         $bestEndClearning,
        //                                         $clearningType,
        //                                         0,

        //                                 );
        //                                 $current_end_clearning = $bestStart ;
        //                                 $stage_plan_ids [] = $campaign_task->id;
        //                                 $campaign_counter++;
        //                                 //$stage_plan_ids_null = [...$stage_plan_ids_null, ...DB::table($stage_plan_table)->where('plan_master_id',$campaign_task->plan_master_id)->where('stage_code','>=',3)->pluck('id')->toArray()];

        //                         }
        //                 }else {
        //                         $title = $task->name ."- ". $task->batch ."- ". $task->market ;
        //                         $this->saveSchedule(
        //                                 null,
        //                                 $task->id,
        //                                 $bestRoomId,
        //                                 $bestStart,
        //                                 $bestEnd,
        //                                 $bestEnd,
        //                                 $bestEndCleaning,
        //                                 2,
        //                                 0,

        //                         );
        //                         $stage_plan_ids [] = $task->id;
        //                 }
        //                 // cập nhật latestEnd cho stage tiếp theo

        //         }

        // } 

        // protected function schedulePlanForwardPlanMasterId($planId,  $waite_time,  ?Carbon $start_date = null) {

        //         if (session('fullCalender')['mode'] === 'offical') {
        //                 $stage_plan_table = 'stage_plan';
        //         } else {
        //                 $stage_plan_table = 'stage_plan_temp';
        //         }
        //         $now = Carbon::now();
        //         $minute = $now->minute;
        //         $roundedMinute = ceil($minute / 15) * 15;

        //         // toàn bộ các row trong stage_plan cùng plan_master_id của các công đoạn từ ĐG - PC
        //         $tasks = DB::table("$stage_plan_table as sp")
        //                 ->select (
        //                         'sp.id',
        //                         'sp.plan_master_id',
        //                         'sp.product_caterogy_id',
        //                         'sp.predecessor_code',
        //                         'sp.campaign_code',
        //                         'sp.code',
        //                         'sp.stage_code',
        //                         'sp.tank',
        //                         'sp.keep_dry',
        //                         'fc.finished_product_code',
        //                         'fc.intermediate_code',
        //                         'pm.is_val',
        //                         'pm.code_val',
        //                         'pm.expected_date',
        //                         'pm.batch',
        //                         'pm.after_weigth_date',
        //                         'pm.before_weigth_date',
        //                         'pm.after_parkaging_date',
        //                         'pm.before_parkaging_date',
        //                         'mk.code as market',
        //                         'pn.name',
        //                 )
        //         ->leftJoin('finished_product_category as fc', 'sp.product_caterogy_id', '=', 'fc.id')
        //         ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
        //         ->leftJoin('product_name as pn', 'fc.product_name_id', '=', 'pn.id')
        //         ->leftJoin('market as mk', 'fc.market_id', '=', 'mk.id')
        //         ->whereNull('start')
        //         ->where('plan_master_id', $planId)
        //         ->where('sp.finished', 0)
        //         ->where('stage_code',">=",3)
        //         ->where('stage_code',"<=",7)
        //         ->orderBy('stage_code', 'asc') // chạy thuận
        //         ->get(); // 1 lô gồm tất cả các stage


        //         foreach ($tasks as  $task) { // Vòng lập chính duyệt qua toàn bộ các task cùng plan_master_id
        //                 $waite_time_for_task = null;

        //                 if (!$task->is_val) {
        //                         $waite_time_for_task = $waite_time[$task->stage_code]['waite_time_nomal_batch'];
        //                 } else {
        //                         $waite_time_for_task = $waite_time[$task->stage_code]['waite_time_val_batch'];
        //                 }


        //                 $campaign_tasks = null;
        //                 $candidatesEarliest = [];
        //                 if ($task->campaign_code){ // trường hợp chiến dịch
        //                         $campaign_tasks = DB::table("$stage_plan_table as sp")
        //                           ->select (
        //                                 'sp.id',
        //                                 'sp.plan_master_id',
        //                                 'sp.product_caterogy_id',
        //                                 'sp.predecessor_code',
        //                                 'sp.nextcessor_code',
        //                                 'sp.campaign_code',
        //                                 'sp.code',
        //                                 'sp.stage_code',
        //                                 'sp.campaign_code',
        //                                 'sp.tank',
        //                                 'sp.keep_dry',
        //                                 'fc.finished_product_code',
        //                                 'fc.intermediate_code',
        //                                 'pm.is_val',
        //                                 'pm.code_val',
        //                                 'pm.expected_date',
        //                                 'pm.level',
        //                                 'pm.batch',
        //                                 'pm.after_weigth_date',
        //                                 'pm.before_weigth_date',
        //                                 'pm.after_parkaging_date',
        //                                 'pm.before_parkaging_date',
        //                                 'mk.code as market',
        //                                 'pn.name')
        //                         ->leftJoin('finished_product_category as fc', 'sp.product_caterogy_id', '=', 'fc.id')
        //                         ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
        //                         ->leftJoin('product_name as pn', 'fc.product_name_id', '=', 'pn.id')
        //                         ->leftJoin('market as mk', 'fc.market_id', '=', 'mk.id')
        //                         ->whereNull('start')
        //                         ->where('finished', 0)
        //                         ->where('campaign_code',$task->campaign_code)
        //                         ->orderBy('expected_date', 'asc')
        //                         ->orderBy('level', 'asc')
        //                         ->orderBy('batch', 'asc')
        //                         ->get();
        //                 }

        //                 /// Tìm Phòng Sản Xuất Thịch Hợp
        //                 if ($task->code_val !== null && $task->stage_code == 3 && isset($parts[1]) && $parts[1] > 1) {
        //                         $code_val_first = $parts[0] . '_1';

        //                         $room_id_first = DB::table("$stage_plan_table as sp")
        //                                 ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
        //                                 ->where('code_val', $code_val_first)
        //                                 ->where('stage_code', $task->stage_code)
        //                                 ->first();

        //                         if ($room_id_first) {
        //                                 $rooms = DB::table('quota')
        //                                 ->select(
        //                                         'room_id',
        //                                         DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
        //                                 )
        //                                 ->when($task->stage_code <= 6, function ($query) use ($task) {
        //                                         return $query->where('intermediate_code', $task->intermediate_code);
        //                                 }, function ($query) use ($task) {
        //                                         return $query->where('finished_product_code', $task->finished_product_code);
        //                                 })
        //                                 ->where('room_id', $room_id_first->resourceId)
        //                                 ->get();

        //                         } else {

        //                                 $rooms = DB::table('quota')->select('room_id',
        //                                                 DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                                 DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                                 DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                                 DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
        //                                         )
        //                                 ->when($task->stage_code <= 6, function ($query) use ($task) {
        //                                         return $query->where('intermediate_code', $task->intermediate_code);
        //                                 }, function ($query) use ($task) {
        //                                         return $query->where('finished_product_code', $task->finished_product_code);
        //                                 })
        //                                 ->where('stage_code', $task->stage_code)
        //                                 ->get();

        //                         }
        //                 }
        //                 elseif ($task->code_val !== null && $task->stage_code > 3 && isset($parts[1]) && $parts[1] > 1) {
        //                         $code_val_first = $parts[0];

        //                         $room_id_first = DB::table("$stage_plan_table as sp")
        //                         ->leftJoin('plan_master as pm', 'sp.plan_master_id', '=', 'pm.id')
        //                         ->where(DB::raw("SUBSTRING_INDEX(pm.code_val, '_', 1)"), '=', $parts[0])
        //                         ->where('sp.stage_code', $task->stage_code)
        //                         ->whereNotNull('start')
        //                         ->get();

        //                         if ($room_id_first) {

        //                                 $rooms = DB::table('quota')
        //                                 ->select(
        //                                         'room_id',
        //                                         DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
        //                                         )
        //                                         ->when($task->stage_code <= 6, function ($query) use ($task) {
        //                                         return $query->where('intermediate_code', $task->intermediate_code);
        //                                         }, function ($query) use ($task) {
        //                                         return $query->where('finished_product_code', $task->finished_product_code);
        //                                         })
        //                                 ->where('stage_code', $task->stage_code)
        //                                 ->get();


        //                                 if ($rooms->count () > $room_id_first->count ()) {
        //                                         foreach ($room_id_first as $first) {
        //                                                 $rooms->where('room_id', '!=', $first->resourceId);
        //                                         }
        //                                 }

        //                         } else {
        //                                 $rooms = DB::table('quota')->select('room_id',
        //                                                 DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                                 DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                                 DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                                 DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
        //                                         )
        //                                 ->when($task->stage_code <= 6, function ($query) use ($task) {
        //                                         return $query->where('intermediate_code', $task->intermediate_code);
        //                                 }, function ($query) use ($task) {
        //                                         return $query->where('finished_product_code', $task->finished_product_code);
        //                                 })
        //                                 ->where('stage_code', $task->stage_code)
        //                                 ->get();
        //                         }

        //                 }else {
        //                         $rooms = DB::table('quota')->select('room_id',
        //                                         DB::raw('(TIME_TO_SEC(p_time)/60) as p_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(m_time)/60) as m_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(C1_time)/60) as C1_time_minutes'),
        //                                         DB::raw('(TIME_TO_SEC(C2_time)/60) as C2_time_minutes')
        //                                 )
        //                                 ->when($task->stage_code <= 6, function ($query) use ($task) {
        //                                 return $query->where('intermediate_code', $task->intermediate_code);
        //                                 }, function ($query) use ($task) {
        //                                 return $query->where('finished_product_code', $task->finished_product_code);
        //                                 })
        //                                 ->where('stage_code', $task->stage_code)
        //                                 ->get();
        //                 }


        //                 $bestRoom = null;
        //                 $bestRoomId = null;
        //                 $bestStart = null;
        //                 $bestEnd = null;
        //                 $bestEndCleaning = null;

        //                 if ($roundedMinute == 60) {
        //                         $now->addHour();
        //                         $roundedMinute = 0;
        //                 }
        //                 $now->minute($roundedMinute)->second(0)->microsecond(0);

        //                 $candidatesEarliest [] = Carbon::parse($now);
        //                 $candidatesEarliest[] = $start_date;

        //                 $startDateWeek = Carbon::parse($task->expected_date)->subDays(5+7);
        //                 $candidatesEarliest[] = $startDateWeek->startOfWeek(Carbon::MONDAY)->setTime(6, 0, 0);

        //                 if ($task->stage_code == 7){
        //                         $candidatesEarliest[] = Carbon::parse($task->after_parkaging_date);
        //                 }elseif ($task->stage_code == 3) {
        //                         $candidatesEarliest[] = Carbon::parse($task->after_weigth_date);
        //                 }


        //                 // Gom tất cả candidate time vào 1 mảng
        //                 $pre_stage_code = explode('_', $task->predecessor_code)[1];

        //                 if ($campaign_tasks){
        //                         $pre_campaign_codes = [];

        //                         foreach ($campaign_tasks as $campaignTask) {


        //                                 $code = null;
        //                                 $pred = DB::table($stage_plan_table)
        //                                         ->when(session('fullCalender')['mode'] === 'temp', function ($query) {
        //                                         return $query->where('stage_plan_temp_list_id', session('fullCalender')['stage_plan_temp_list_id']);
        //                                         })
        //                                         ->where('code', $campaignTask->predecessor_code)
        //                                 ->first();

        //                                 if ($pred) {
        //                                         $code = $pred->campaign_code;
        //                                         if (!in_array($code, $pre_campaign_codes) && $code != null) {
        //                                                 $pre_campaign_codes [] = $code ;

        //                                                 $pre_campaign_batch = DB::table($stage_plan_table)
        //                                                 ->when(session('fullCalender')['mode'] === 'temp', function ($query) {
        //                                                         return $query->where('stage_plan_temp_list_id', session('fullCalender')['stage_plan_temp_list_id']);
        //                                                 })
        //                                                 ->where('campaign_code', $code)
        //                                                 ->orderBy('start', 'asc')
        //                                                 ->get();

        //                                                 $pre_campaign_first_batch =  $pre_campaign_batch->first();
        //                                                 $pre_campaign_last_batch =  $pre_campaign_batch->last();

        //                                                 $prevCycle = DB::table('quota')
        //                                                 ->selectRaw('AVG(TIME_TO_SEC(m_time)/60) as avg_m_time_minutes')
        //                                                 ->when($task->stage_code <= 6, function ($query) use ($task) {
        //                                                         return $query->where('intermediate_code', $task->intermediate_code);
        //                                                 }, function ($query) use ($task) {
        //                                                         return $query->where('finished_product_code', $task->finished_product_code);
        //                                                 })
        //                                                 ->where('active', 1)
        //                                                 ->where('stage_code', $pre_campaign_first_batch->stage_code)
        //                                                 ->value('avg_m_time_minutes');

        //                                                 $currCycle = DB::table('quota')
        //                                                         ->selectRaw('AVG(TIME_TO_SEC(m_time)/60) as avg_m_time_minutes')
        //                                                         ->when($task->stage_code <= 6, function ($query) use ($task) {
        //                                                                 return $query->where('intermediate_code', $task->intermediate_code);
        //                                                         }, function ($query) use ($task) {
        //                                                                 return $query->where('finished_product_code', $task->finished_product_code);
        //                                                         })
        //                                                         ->where('active', 1)
        //                                                         ->where('stage_code', $campaignTask->stage_code)
        //                                                 ->value('avg_m_time_minutes');

        //                                                 if ($currCycle && $currCycle >= $prevCycle){
        //                                                         $candidatesEarliest[] = Carbon::parse($pred->end);

        //                                                 }else {
        //                                                         $candidatesEarliest[] = Carbon::parse($pre_campaign_last_batch->end)->subMinutes(($campaign_tasks->count() - 1) * $currCycle);
        //                                                 }
        //                                         }

        //                                         if ($code == null){
        //                                                 $candidatesEarliest [] =  Carbon::parse($pred->end);
        //                                         }
        //                                 }
        //                         }
        //                 }else {
        //                         $pre_stage_code = explode('_', $task->predecessor_code)[1];
        //                         $prev_stage_end = DB::table ($stage_plan_table)->where('code', $task->predecessor_code)->value('end');

        //                         if ($pre_stage_code >= 3 && $waite_time_for_task){
        //                                 $candidatesEarliest[] = Carbon::parse($prev_stage_end)->copy()->addMinutes($waite_time_for_task);
        //                         }else {
        //                                 $candidatesEarliest[] = Carbon::parse($prev_stage_end);
        //                         }
        //                 }



        //                 $earliestStart = collect($candidatesEarliest)->max();


        //                 foreach ($rooms as $room) { // duyệt qua toàn bộ các room đã định mức để tìm bestroom
        //                         $intervalTimeMinutes = (float) $room->p_time_minutes + (float) $room->m_time_minutes;
        //                         $C2_time_minutes =  (float) $room->C2_time_minutes;

        //                         if ($campaign_tasks !== null){ // chỉ thực hiện khi có chiến dịch
        //                                 $intervalTimeMinutes = (float) $room->p_time_minutes + (float) $room->m_time_minutes * $campaign_tasks->count() + (float) $room->C1_time_minutes * ($campaign_tasks->count()-1);
        //                                 $C2_time_minutes =  (float) $room->C2_time_minutes;
        //                                 $currCycle =  (float) $room->m_time_minutes;
        //                         }

        //                         $candidateStart = $this->findEarliestSlot2(
        //                                 $room->room_id,
        //                                 $earliestStart,
        //                                 $intervalTimeMinutes,
        //                                 $C2_time_minutes,
        //                                 $task->tank,
        //                                 $task->keep_dry,
        //                                 $stage_plan_table,
        //                                 2,
        //                                 60
        //                         );

        //                         if ($bestStart === null || $candidateStart->lt(Carbon::parse($bestStart))) {
        //                                 $bestRoom = $room;
        //                                 $bestRoomId = $room->room_id;
        //                                 $bestStart = $candidateStart;
        //                                 $bestEnd = $bestStart->copy()->addMinutes((float) $room->p_time_minutes + (float) $room->m_time_minutes);
        //                                 $bestEndCleaning  = $bestEnd->copy()->addMinutes( (float) $room->C2_time_minutes);
        //                         }
        //                 }

        //                 // foreach ($campaignTasks as  $task) {

        //                 //         if ($this->work_sunday == false) {
        //                 //                 $startOfSunday = (clone $bestStart)->startOfWeek()->addDays(6)->setTime(6, 0, 0);
        //                 //                 $endOfPeriod   = (clone $startOfSunday)->addDay()->setTime(6, 0, 0);
        //                 //                 if ($bestStart->between($startOfSunday, $endOfPeriod)) {
        //                 //                         $bestStart = $endOfPeriod->copy();
        //                 //                 }
        //                 //         }

        //                 //         $pred_end = DB::table($stage_plan_table)
        //                 //                 ->when(session('fullCalender')['mode'] === 'temp', function ($query) {
        //                 //                 return $query->where('stage_plan_temp_list_id', session('fullCalender')['stage_plan_temp_list_id']);
        //                 //                 })
        //                 //         ->where('code', $task->predecessor_code)->value('end');

        //                 //         if (isset($pred_end) && $pred_end != null && $pred_end > $bestStart) {$bestStart = Carbon::parse($pred_end);}

        //                 //         if ($counter == 1) {
        //                 //                 $bestEnd = $bestStart->copy()->addMinutes((float) $bestRoom->p_time_minutes + $bestRoom->m_time_minutes);
        //                 //                 if ($bestEnd->between($startOfSunday, $endOfPeriod)) {
        //                 //                         $bestEnd = $bestEnd->addMinutes(1440);;
        //                 //                 }
        //                 //                 $start_clearning = $bestEnd->copy();
        //                 //                 $bestEndCleaning = $bestEnd->copy()->addMinutes((float)$bestRoom->C1_time_minutes); //Lô đâu tiên chiến dịch
        //                 //                 $clearningType = 1;
        //                 //         }elseif ($counter == $campaignTasks->count()){

        //                 //                 $bestEnd = $bestStart->copy()->addMinutes((float) $bestRoom->m_time_minutes);
        //                 //                 $start_clearning = $bestEnd->copy();
        //                 //                 $bestEndCleaning = $bestEnd->copy()->addMinutes((float)$bestRoom->C2_time_minutes); //Lô cuối chiến dịch
        //                 //                 if ($start_clearning->between($startOfSunday, $endOfPeriod)) {
        //                 //                         $start_clearning =  $endOfPeriod->copy();
        //                 //                         $bestEndCleaning =  $start_clearning->copy()->addMinutes((float)$bestRoom->C2_time_minutes);
        //                 //                 }else if ($bestEndCleaning->between($startOfSunday, $endOfPeriod)) {
        //                 //                         $bestEndCleaning = $bestEndCleaning->addMinutes(1440);;
        //                 //                 }

        //                 //                 $clearningType = 2;
        //                 //         }else {
        //                 //                 $bestEnd = $bestStart->copy()->addMinutes((float) $bestRoom->m_time_minutes);
        //                 //                 if ($bestEnd->between($startOfSunday, $endOfPeriod)) {
        //                 //                         $bestEnd = $bestEnd->addMinutes(1440);;
        //                 //                 }
        //                 //                 $start_clearning = $bestEnd->copy();
        //                 //                 $bestEndCleaning = $start_clearning->copy()->addMinutes((float)$bestRoom->C1_time_minutes); //Lô giữa chiến dịch
        //                 //                 $clearningType = 1;
        //                 //         }

        //                 //         $this->saveSchedule(
        //                 //                 $task->name."-".$task->batch, //."-".$task->market,
        //                 //                 $task->id,
        //                 //                 $bestRoom->room_id,
        //                 //                 $bestStart,
        //                 //                 $bestEnd,
        //                 //                 $start_clearning,
        //                 //                 $bestEndCleaning,
        //                 //                 $clearningType,
        //                 //                 1,

        //                 //         );
        //                 //         $counter++;
        //                 //         $bestStart = $bestEndCleaning->copy();
        //                 // }

        //                 if ($campaign_tasks !== null){
        //                         $counter = 1;
        //                         // foreach ($campaign_tasks as $task){

        //                         //         if ($this->work_sunday == false) {
        //                         //                 $startOfSunday = (clone $bestStart)->startOfWeek()->addDays(6)->setTime(6, 0, 0);
        //                         //                 $endOfPeriod   = (clone $startOfSunday)->addDay()->setTime(6, 0, 0);
        //                         //                 if ($bestStart->between($startOfSunday, $endOfPeriod)) {
        //                         //                         $bestStart = $endOfPeriod->copy();
        //                         //                 }
        //                         //         }
        //                         //         $bestEnd = $bestStart->copy()->addMinutes((float) $bestRoom->m_time_minutes);

        //                         //         if ($bestEnd->between($startOfSunday, $endOfPeriod)) {
        //                         //                 $bestEnd = $bestEnd->copy()->addMinutes(1440);
        //                         //         }

        //                         //         if ($campaign_counter == 1) {
        //                         //                 $start_clearning = $bestEnd->copy();
        //                         //                 $bestEndCleaning = $bestEnd->copy()->addMinutes((float) $bestRoom->C1_time_minutes);
        //                         //                 $clearningType = 1;

        //                         //         }elseif ($campaign_counter == $campaign_tasks->count()){

        //                         //                 $start_clearning = $bestEnd->copy();
        //                         //                 $bestEndCleaning = $bestEnd->copy()->addMinutes((float) $bestRoom->C2_time_minutes);
        //                         //                 $clearningType = 2;

        //                         //                 if ($start_clearning->between($startOfSunday, $endOfPeriod)) {
        //                         //                         $start_clearning =  $endOfPeriod->copy();
        //                         //                         $bestEndCleaning =  $start_clearning->copy()->addMinutes((float)$bestRoom->C2_time_minutes);
        //                         //                 }

        //                         //         }else {
        //                         //                 $start_clearning = $bestEnd->copy();
        //                         //                 $bestEndCleaning = $bestEnd->copy()->addMinutes((float) $bestRoom->C1_time_minutes);
        //                         //                 $clearningType = 1;
        //                         //         }

        //                         //         $this->saveSchedule(
        //                         //                 $task->name ."-". $task->batch,
        //                         //                 $task->id,
        //                         //                 $bestRoomId,
        //                         //                 $bestStart,
        //                         //                 $bestEnd,
        //                         //                 $start_clearning,
        //                         //                 $bestEndCleaning,
        //                         //                 $clearningType,
        //                         //                 1
        //                         //         );
        //                         //         $bestStart = $bestEndCleaning;
        //                         //         $campaign_counter++;
        //                         // }

        //                         foreach ($campaign_tasks as  $task) {

        //                                 if ($this->work_sunday == false) {
        //                                          $startOfSunday = (clone $bestStart)->startOfWeek()->addDays(6)->setTime(6, 0, 0);
        //                                          $endOfPeriod   = (clone $startOfSunday)->addDay()->setTime(6, 0, 0);
        //                                         if ($bestStart->between($startOfSunday, $endOfPeriod)) {
        //                                                 $bestStart = $endOfPeriod->copy();
        //                                         }
        //                                 }

        //                                 $pred_end = DB::table($stage_plan_table)
        //                                         ->when(session('fullCalender')['mode'] === 'temp', function ($query) {
        //                                         return $query->where('stage_plan_temp_list_id', session('fullCalender')['stage_plan_temp_list_id']);
        //                                         })
        //                                 ->where('code', $task->predecessor_code)->value('end');

        //                                 if (isset($pred_end) && $pred_end != null && $pred_end > $bestStart) {$bestStart = Carbon::parse($pred_end);}

        //                                 if ($counter == 1) {
        //                                         $bestEnd = $bestStart->copy()->addMinutes((float) $bestRoom->p_time_minutes + $bestRoom->m_time_minutes);
        //                                         if ($bestEnd->between($startOfSunday, $endOfPeriod)) {
        //                                                 $bestEnd = $bestEnd->addMinutes(1440);;
        //                                         }
        //                                         $start_clearning = $bestEnd->copy();
        //                                         $bestEndCleaning = $bestEnd->copy()->addMinutes((float)$bestRoom->C1_time_minutes); //Lô đâu tiên chiến dịch
        //                                         $clearningType = 1;
        //                                 }elseif ($counter == $campaign_tasks->count()){

        //                                         $bestEnd = $bestStart->copy()->addMinutes((float) $bestRoom->m_time_minutes);
        //                                         $start_clearning = $bestEnd->copy();
        //                                         $bestEndCleaning = $bestEnd->copy()->addMinutes((float)$bestRoom->C2_time_minutes); //Lô cuối chiến dịch
        //                                         if ($start_clearning->between($startOfSunday, $endOfPeriod)) {
        //                                                 $start_clearning =  $endOfPeriod->copy();
        //                                                 $bestEndCleaning =  $start_clearning->copy()->addMinutes((float)$bestRoom->C2_time_minutes);
        //                                         }else if ($bestEndCleaning->between($startOfSunday, $endOfPeriod)) {
        //                                                 $bestEndCleaning = $bestEndCleaning->addMinutes(1440);;
        //                                         }

        //                                         $clearningType = 2;
        //                                 }else {
        //                                         $bestEnd = $bestStart->copy()->addMinutes((float) $bestRoom->m_time_minutes);

        //                                         if ($bestEnd->between($startOfSunday, $endOfPeriod)) {
        //                                                 $bestEnd = $bestEnd->addMinutes(1440);;
        //                                         }
        //                                         $start_clearning = $bestEnd->copy();
        //                                         $bestEndCleaning = $start_clearning->copy()->addMinutes((float)$bestRoom->C1_time_minutes); //Lô giữa chiến dịch
        //                                         $clearningType = 1;
        //                                 }

        //                                 $this->saveSchedule(
        //                                         $task->name."-".$task->batch, //."-".$task->market,
        //                                         $task->id,
        //                                         $bestRoom->room_id,
        //                                         $bestStart,
        //                                         $bestEnd,
        //                                         $start_clearning,
        //                                         $bestEndCleaning,
        //                                         $clearningType,
        //                                         1,

        //                                 );
        //                                 $counter++;
        //                                 $bestStart = $bestEndCleaning->copy();
        //                         }


        //                 }else {
        //                         if ($this->work_sunday == false) { 
        //                                 //Giả sử $bestStart là Carbon instance
        //                                 $startOfSunday = (clone $bestStart)->startOfWeek()->addDays(6)->setTime(0, 0, 0); // CN 6h sáng
        //                                 $endOfPeriod   = (clone $startOfSunday)->addDay()->setTime(6, 0, 0); // T2 tuần kế tiếp 6h sáng
        //                                 if ($bestStart->between($startOfSunday, $endOfPeriod)) {
        //                                         $bestStart = $endOfPeriod->copy();
        //                                         $bestEnd = $bestStart->copy()->addMinutes($intervalTimeMinutes);
        //                                         $start_clearning =  $bestEnd->copy();
        //                                 }
        //                                 if (isset($start_clearning) &&  $start_clearning->between($startOfSunday, $endOfPeriod)) {
        //                                         $start_clearning =  $endOfPeriod->copy();
        //                                 }
        //                         }

        //                         $this->saveSchedule(
        //                                 $task->name ."-". $task->batch ,
        //                                 $task->id,
        //                                 $bestRoomId,
        //                                 $bestStart,
        //                                 $bestEnd,
        //                                 $bestEnd,
        //                                 $bestEndCleaning,
        //                                 2,
        //                                 1
        //                         );
        //         }
        // }


        protected function syncPackagingDate($stagePlanId, $date, $type)
        {
                $plan = DB::table('stage_plan')->where('id', $stagePlanId)->first(['received', 'received_second_packaging']);
                if ($plan) {
                        if ($type == 0 && $plan->received == 1) return;
                        if ($type == 1 && $plan->received_second_packaging == 1) return;
                }

                $latest = DB::table('packaging_issuance_date')
                        ->where('stage_plane_id', $stagePlanId)
                        ->where('type_packaging', $type)
                        ->orderBy('ver', 'desc')
                        ->first();

                if (!$latest || $latest->receive_packaging_date != $date) {
                        DB::table('packaging_issuance_date')->insert([
                                'stage_plane_id'         => $stagePlanId,
                                'type_packaging'         => $type,
                                'receive_packaging_date' => $date,
                                'ver'                    => ($latest->ver ?? 0) + 1,
                                'created_at'             => now(),
                                'created_by'             => session('user')['fullName'] ?? 'System'
                        ]);
                }
        }
}



function  toMinutes($time)

{

        [$hours,  $minutes]  =  explode(':',  $time);

        return ((int)$hours)  *  60  + (int)$minutes;
}


function  minutesToDayHoursMinutesString(int  $minutes): string

{

        $days     =  intdiv($minutes,  1440);
        // 60 * 24
        $remain   =  $minutes  %  1440;


        $hours    =  intdiv($remain,  60);

        $mins     =  $remain  %  60;


        return ($days  >  0  ?  "{$days}d "  :  "")
                .  ($hours  >  0  ?  "{$hours}h"  :  "")
                .  "{$mins}p";
}


function  minutesToHoursMinutes(int  $minutes): array

{

        $hours  =  intdiv($minutes,  60);

        $mins   =  $minutes  %  60;

        return  [$hours,  $mins];
}

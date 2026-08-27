<?php

namespace App\Http\Controllers\Pages\Schedual;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait ValidateSubmitLogic
{
    public function validateSubmit(Request $request)
    {
        $submitType = $request->input('submit_type', 'production');
        $production = session('user.production_code');
        $now = now()->format('Y-m-d H:i:s');

        // Chặn submit_type lạ: nếu không hợp lệ thì dừng, tránh quét không có filter stage_code.
        $maintenanceTypes = ['HC', 'TB', 'BT', 'TI'];
        if ($submitType !== 'production' && !in_array($submitType, $maintenanceTypes)) {
            return response()->json(['errors' => [], 'message' => "Loại submit không hợp lệ: {$submitType}"], 422);
        }

        // 1. Tìm các sự kiện chưa submit, start >= now
        $unsubmittedRows = DB::table('stage_plan as sp')
            ->select('sp.plan_master_id')
            ->whereNotNull('sp.start')
            ->where('sp.start', '>=', $now) // Do not scan past events
            ->where('sp.finished', 0)
            ->where('sp.active', 1)
            ->where('sp.submit', 0)
            ->where('sp.deparment_code', $production)
            ->when($submitType === 'production', function ($query) {
                $query->where('sp.stage_code', '!=', 8);
            })
            ->when(in_array($submitType, $maintenanceTypes), function ($query) use ($submitType) {
                $query->where('sp.stage_code', 8)
                    ->where(function ($q) use ($submitType) {
                        // Lịch bảo trì thiết bị có 2 dạng hậu tố: '_TB' và '_8'
                        if (in_array($submitType, ['TB', 'BT'])) {
                            $q->where('sp.code', 'LIKE', '%\_TB')
                                ->orWhere('sp.code', 'LIKE', '%\_8');
                        } else {
                            $q->where('sp.code', 'LIKE', '%\_' . $submitType);
                        }
                    });
            })
            ->get();

        if ($unsubmittedRows->isEmpty()) {
            return response()->json(['errors' => []]);
        }

        $planMasterIds = $unsubmittedRows->pluck('plan_master_id')->filter()->unique()->toArray();

        // Loại bỏ các lô đã hoàn thành tới stage_code = 7
        $completedStage7 = DB::table('stage_plan')
            ->whereIn('plan_master_id', $planMasterIds)
            ->where('stage_code', 7)
            ->where('finished', 1)
            ->pluck('plan_master_id')->toArray();

        $planMasterIdsToProcess = array_diff($planMasterIds, $completedStage7);

        if (empty($planMasterIdsToProcess)) {
            return response()->json(['errors' => []]);
        }

        // 2. Fetch full details for these plan_master_ids using similar logic to getEvents
        $room_code = DB::table('room')->where('deparment_code', $production)->pluck('code', 'id');

        $maxFinishedStage = DB::table('stage_plan')
            ->where('finished', 1)
            ->select('plan_master_id', DB::raw('MAX(stage_code) as max_finished_stage'))
            ->groupBy('plan_master_id');

        $plans = DB::table('stage_plan as sp')
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
            ->whereIn('sp.plan_master_id', $planMasterIdsToProcess)
            ->select(
                'sp.*',
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
                    COALESCE(plan_master.actual_batch, plan_master.batch) as batch_name
                "),
                'blister_mold.code as blister_mold_code',

                // Các cột colorEvent() cần để chấm màu vi phạm. Thiếu cột nào thì check tương ứng
                // sẽ im lặng bỏ qua (empty()) hoặc bắn Undefined property làm hỏng cả endpoint.
                'plan_master.expected_date',
                'plan_master.is_val',
                'plan_master.after_weigth_date',
                'plan_master.after_parkaging_date',
                'plan_master.expired_material_date',
                'plan_master.allow_weight_before_date',
                'plan_master.preperation_before_date',
                'plan_master.blending_before_date',
                'plan_master.forming_before_date',
                'plan_master.coating_before_date',
                'plan_master.parkaging_before_date',
                'plan_master.expired_packing_date',

                DB::raw('
                                CASE
                                WHEN sp.stage_code = 1 THEN
                                        CASE WHEN intermediate_category.quarantine_time_unit = 1
                                        THEN intermediate_category.quarantine_weight * 24
                                        ELSE intermediate_category.quarantine_weight END
                                WHEN sp.stage_code = 2 THEN
                                        CASE WHEN intermediate_category.quarantine_time_unit = 1
                                        THEN 45 * 24
                                        ELSE 45 END
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
                                END as quarantine_time_limit_hour'),
                DB::raw('CASE WHEN intermediate_category.quarantine_time_unit = 1
                                        THEN intermediate_category.quarantine_blending * 24
                                        ELSE intermediate_category.quarantine_blending END as quarantine_blending_hour')
            )
            ->orderBy('sp.plan_master_id', 'asc')
            ->orderBy('sp.stage_code', 'asc')
            ->get();

        // Các lô PC+THT làm chung phòng PC: hạn biệt trữ sau PC được chấm theo quarantine_blending
        $this->loadMergedPcThtPlanMasters($planMasterIdsToProcess);

        $errors = [];
        // Chỉ những màu thực sự chặn submit. Lỗi khuôn (#ffd500ff: thiếu / sai / trùng khuôn)
        // là cảnh báo: vẫn hiện màu + sọc trên lịch nhưng không ngăn submit.
        $errorColors = [
            '#920000ff' => 'Cảnh Báo Ngày Đáp Ứng NL/BB',
            '#e54a4aff' => 'Không Đáp Ứng Ngày Cần Hàng Theo Kế Hoạch',
            '#4d4b4bff' => 'Lỗi Cân Nguyên Liệu',
        ];

        // colorEvent() tra công đoạn 7 / predecessor / successor bằng firstWhere() trên tập truyền
        // vào, nên phải gom nhóm theo lô như getEvents. Để chung cả tập thì hạn KCS của lô này sẽ
        // bị đem so với công đoạn 7 của lô khác.
        foreach ($plans->groupBy('plan_master_id') as $groupedPlans) {

            $groupedPlans = $groupedPlans->values();

            for ($i = 0, $n = $groupedPlans->count(); $i < $n; $i++) {

                $plan = $groupedPlans[$i];

                // Only check unsubmitted events in the future
                if ($plan->submit != 0 || $plan->start < $now) continue;

                if ($submitType === 'production' && $plan->stage_code == 8) continue;
                if (in_array($submitType, $maintenanceTypes)) {
                    if ($plan->stage_code != 8) continue;

                    $matchType = in_array($submitType, ['TB', 'BT'])
                        ? (str_ends_with($plan->code, '_TB') || str_ends_with($plan->code, '_8'))
                        : str_ends_with($plan->code, '_' . $submitType);

                    if (!$matchType) continue;
                }

                list($color_event, $textColor, $subtitle, $violation_colors, $mold_warning, $mold_code) = $this->colorEvent($plan, $groupedPlans, $i, $room_code);

                // Gộp color_event (màu nền chính) và các violation_colors: một lô có thể vi phạm
                // nhiều thứ cùng lúc (vd Thiếu Khuôn + trễ hạn KCS) nên phải giữ đủ, không break sớm.
                $allColors = array_map('strtolower', array_merge([$color_event], $violation_colors));
                $matchedColors = array_values(array_unique(array_filter(
                    $allColors,
                    fn($c) => isset($errorColors[$c])
                )));

                if ($matchedColors) {
                    $errors[] = [
                        'plan_id' => $plan->id,
                        'title' => $plan->title,
                        'start' => $plan->start,
                        'backgroundColor' => $matchedColors[0],
                        'colors' => $matchedColors,
                        'reason' => $subtitle ?: implode(' | ', array_map(fn($c) => $errorColors[$c], $matchedColors))
                    ];
                }
            }
        }

        return response()->json(['errors' => $errors]);
    }
}

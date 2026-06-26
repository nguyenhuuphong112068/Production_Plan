    public function validateSubmit(Request $request)
    {
        $submitType = $request->input('submit_type', 'production');
        $production = session('user.production_code');
        $now = now()->format('Y-m-d H:i:s');

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
            ->when(in_array($submitType, ['HC', 'BT', 'TI']), function ($query) use ($submitType) {
                $query->where('sp.stage_code', 8)
                    ->where('sp.code', 'LIKE', '%_' . $submitType);
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
                'plan_master.expected_date',
                'blister_mold.code as blister_mold_code',
                'plan_master.predecessor_code'
            )
            ->orderBy('sp.stage_code', 'asc')
            ->get();

        $errors = [];
        $errorColors = ['#920000ff', '#e54a4aff', '#4d4b4bff'];

        foreach ($plans as $i => $plan) {
            // Only check unsubmitted events in the future
            if ($plan->submit == 0 && $plan->start >= $now) {
                if ($submitType === 'production' && $plan->stage_code == 8) continue;
                if (in_array($submitType, ['HC', 'BT', 'TI']) && ($plan->stage_code != 8 || !str_ends_with($plan->code, '_' . $submitType))) continue;

                list($color_event, $textColor, $subtitle, $violation_colors, $mold_warning, $mold_code) = $this->colorEvent($plan, $plans, $i, $room_code);
                
                $hasError = false;
                $reason = 'Lỗi không xác định';
                $bg = '';

                // Gộp color_event (màu nền chính) và các violation_colors lại để kiểm tra
                $allColors = array_merge([$color_event], $violation_colors);
                
                foreach ($allColors as $c) {
                    if (in_array(strtolower($c), $errorColors)) {
                        $hasError = true;
                        $bg = strtolower($c);
                        if ($bg === '#920000ff') {
                            $reason = 'Cảnh Báo Ngày Đáp Ứng NL/BB';
                        } elseif ($bg === '#e54a4aff') {
                            $reason = 'Không Đáp Ứng Ngày Cần Hàng Theo Kế Hoạch / Thiếu Khuôn';
                        } elseif ($bg === '#4d4b4bff') {
                            $reason = 'Lỗi Cân Nguyên Liệu';
                        }
                        break;
                    }
                }

                if ($hasError) {
                    $errors[] = [
                        'title' => $plan->title,
                        'start' => $plan->start,
                        'backgroundColor' => $bg,
                        'reason' => $reason
                    ];
                }
            }
        }

        return response()->json(['errors' => $errors]);
    }

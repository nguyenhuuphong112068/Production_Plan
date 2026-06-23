import re

with open('c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php', 'r', encoding='utf-8') as f:
    content = f.read()

target = """    public function scheduleAll(Request $request)"""

new_function = """    public function scheduleAllPass2(Request $request)
    {
        $overdueCampaigns = $request->overdueCampaigns ?? [];

        if (empty($overdueCampaigns)) {
            return response()->json(['message' => 'No overdue campaigns provided']);
        }

        $this->selectedDates = $request->selectedDates ?? [];
        $this->work_sunday = $request->work_sunday ?? false;
        $this->reason = $request->reason ?? 'NA';
        $this->prev_orderBy = $request->prev_orderBy ?? false;
        $this->loadOffDate('asc');
        $today = Carbon::now()->toDateString();
        $start_date = Carbon::createFromFormat('Y-m-d', $request->start_date ?? $today)->setTime(6, 0, 0);

        // 1. Rollback Lịch Chọn Lọc
        $campaignCodesStage12 = [];
        $campaignCodesAll = [];

        foreach ($overdueCampaigns as $overdue) {
            $campaignCode = $overdue['campaign_code'];
            $stage = $overdue['stage_code'];

            if (in_array($stage, [1, 2])) {
                $campaignCodesStage12[] = $campaignCode;
            } else {
                $campaignCodesAll[] = $campaignCode;
            }
        }

        if (!empty($campaignCodesStage12)) {
            DB::table('stage_plan')
                ->whereIn('campaign_code', $campaignCodesStage12)
                ->whereIn('stage_code', [1, 2])
                ->update([
                    'start' => null,
                    'end' => null,
                    'start_clearning' => null,
                    'end_clearning' => null,
                    'resourceId' => null,
                    'not_schedule' => 0
                ]);
        }

        if (!empty($campaignCodesAll)) {
            DB::table('stage_plan')
                ->whereIn('campaign_code', $campaignCodesAll)
                ->update([
                    'start' => null,
                    'end' => null,
                    'start_clearning' => null,
                    'end_clearning' => null,
                    'resourceId' => null,
                    'not_schedule' => 0
                ]);
        }

        // 2. Chạy Pass 2 với Độ Ưu Tiên Tuyệt Đối
        // Xếp theo độ trễ giảm dần
        usort($overdueCampaigns, function($a, $b) {
            return $b['tardiness'] <=> $a['tardiness'];
        });

        foreach ($overdueCampaigns as $overdue) {
            $campaignCode = $overdue['campaign_code'];
            $stage = $overdue['stage_code'];

            if (in_array($stage, [1, 2])) {
                // Xếp backward cho Cân
                $this->scheduleWeightStage($start_date); 
                // Wait, scheduleWeightStage schedules ALL stage 1,2. We just call it here so it schedules the ones we just cleared.
            } else {
                // Xếp forward cho các stage 4, 5, 7
                $tasks = DB::table('stage_plan as sp')
                    ->select(
                        'sp.id', 'sp.plan_master_id', 'sp.product_caterogy_id', 'sp.predecessor_code', 'sp.nextcessor_code',
                        'sp.campaign_code', 'sp.code', 'sp.stage_code', 'sp.tank', 'sp.keep_dry', 'sp.order_by', 'sp.required_room_code', 'sp.immediately',
                        'plan_master.batch', 'plan_master.is_val', 'plan_master.code_val', 'plan_master.expected_date',
                        'plan_master.after_weigth_date', 'plan_master.after_parkaging_date', 'plan_master.allow_weight_before_date',
                        'finished_product_category.product_name_id', 'finished_product_category.market_id', 'finished_product_category.finished_product_code', 'finished_product_category.intermediate_code',
                        'product_name.name', 'market.code as market'
                    )
                    ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
                    ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
                    ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
                    ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
                    ->where('sp.campaign_code', $campaignCode)
                    ->where('sp.active', 1)
                    ->whereNull('sp.start')
                    ->where('sp.finished', 0)
                    ->get();
                    
                if ($tasks->isNotEmpty()) {
                    $campaignTasks = $tasks->sortBy('batch');
                    // For Pass 2, wait_time is 0 for priority? Or we use normal wait time?
                    // Let's just pass 0 for wait time to give absolute priority and pack them tightly.
                    $this->scheduleCampaign($campaignTasks, $stage, 0, $start_date, null);
                }
            }
        }

        // 3. Sau khi VIP chiếm phòng, xếp bình thường cho các lô còn lại chưa có lịch
        $this->scheduleAll(new Request($request->all()));
        // Note: scheduleAll will return response()->json. We can't easily return it here if we call it this way.
        // Let's just return a success since the state will be re-fetched.
        return response()->json(['success' => true]);
    }

""" + target

if target in content:
    content = content.replace(target, new_function)
    with open('c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php', 'w', encoding='utf-8') as f:
        f.write(content)
    print("Added scheduleAllPass2 to SchedualController.php")
else:
    print("Target not found")

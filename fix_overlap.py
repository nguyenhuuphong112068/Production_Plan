import re

with open('c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php', 'r', encoding='utf-8') as f:
    content = f.read()

target = """        foreach ($campaignTasks as $campaignTask) {

            $pred = DB::table('stage_plan')->where('code', $campaignTask->predecessor_code)->first();

            if ($pred && !in_array($pred->stage_code, [1, 2])) {

                $code = $pred->campaign_code;

                if (! in_array($code, $pre_campaign_codes) && $code != null) {

                    $pre_campaign_codes[] = $code;

                    // $pre_campaign_batch = DB::table('stage_plan')
                    //     ->where('campaign_code', $code)
                    //     ->where('stage_code', $pred->stage_code)
                    //     ->orderBy('start', 'asc')
                    //     ->get();

                    // $pre_campaign_first_batch = $pre_campaign_batch->first();

                    // $pre_campaign_last_batch = $pre_campaign_batch->last();

                    // $prevCycle = DB::table('quota')
                    //     ->selectRaw('AVG(TIME_TO_SEC(m_time)/60) as avg_m_time_minutes')
                    //     ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {

                    //         return $query->where('intermediate_code', $firstTask->intermediate_code);
                    //     }, function ($query) use ($firstTask) {

                    //         return $query->where('finished_product_code', $firstTask->finished_product_code);
                    //     })
                    //     ->where('active', 1)
                    //     ->where('stage_code', $pre_campaign_first_batch->stage_code)
                    //     ->value('avg_m_time_minutes');

                    // $currCycle = DB::table('quota')
                    //     ->selectRaw('AVG(TIME_TO_SEC(m_time)/60) as avg_m_time_minutes')
                    //     ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {

                    //         return $query->where('intermediate_code', $firstTask->intermediate_code);
                    //     }, function ($query) use ($firstTask) {

                    //         return $query->where('finished_product_code', $firstTask->finished_product_code);
                    //     })
                    //     ->where('active', 1)
                    //     ->where('stage_code', $campaignTask->stage_code)
                    //     ->value('avg_m_time_minutes');

                    // $maxCount = max($campaignTasks->count(), $pre_campaign_batch->count());

                    // if ($currCycle && $currCycle >= $prevCycle) {

                    //     $candidates[] = Carbon::parse($pred->end)->addMinutes($waite_time);
                    // } else {

                    //     $hasImmediately = collect($campaignTasks)->contains('immediately', 1);

                    //     if ($campaignTask->immediately == false && $hasImmediately) {

                    //         $candidates[] = Carbon::parse($pre_campaign_last_batch->end)->subMinutes(($campaignTasks->count() - 1) * $currCycle);

                    //         $candidates[] = Carbon::parse($pred->end)->addMinutes($waite_time + $maxCount * ($prevCycle - $currCycle));
                    //     }
                    // }

                    $candidates[] = Carbon::parse($pred->end)->addMinutes($waite_time);
                }

                if ($code == null) {

                    $candidates[] = Carbon::parse($pred->end);
                }
            }
        }"""

replacement = """        $avg_m_time = DB::table('quota')
            ->selectRaw('AVG(TIME_TO_SEC(m_time)/60) as avg_m_time_minutes')
            ->when($firstTask->stage_code <= 6, function ($query) use ($firstTask) {
                return $query->where('intermediate_code', $firstTask->intermediate_code);
            }, function ($query) use ($firstTask) {
                return $query->where('finished_product_code', $firstTask->finished_product_code);
            })
            ->where('active', 1)
            ->where('stage_code', $stageCode)
            ->value('avg_m_time_minutes') ?? 15;

        $batch_index = 0;
        foreach ($campaignTasks as $campaignTask) {
            $pred = DB::table('stage_plan')->where('code', $campaignTask->predecessor_code)->first();
            if ($pred && !in_array($pred->stage_code, [1, 2])) {
                $candidates[] = Carbon::parse($pred->end)->addMinutes($waite_time)->subMinutes($batch_index * $avg_m_time);
            }
            $batch_index++;
        }"""

if target in content:
    content = content.replace(target, replacement)
    with open('c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php', 'w', encoding='utf-8') as f:
        f.write(content)
    print("Fixed overlapping issue in scheduleCampaign")
else:
    print("Target not found")

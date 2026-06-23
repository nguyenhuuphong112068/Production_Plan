with open('c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Change 1: Removing Just-In-Time (User request)
jit_start = "            // ─── BƯỚC 2: Just-In-Time — tránh xếp quá sớm ────────────────────────────"
jit_end = "            if ($stageEarliestDeadline !== null && ($start_date_effective === null || $stageEarliestDeadline->gt($start_date_effective))) {\n                $start_date_effective = $stageEarliestDeadline;\n            }"
idx_start = content.find(jit_start)
idx_end = content.find(jit_end, idx_start)
if idx_start != -1 and idx_end != -1:
    content = content[:idx_start] + "// Bỏ BƯỚC 2: Không áp dụng Just-In-Time nữa để ưu tiên xếp khi có phòng trống sớm nhất\n" + content[idx_end + len(jit_end):]

# Change 2: User's change of removing hasPredecessor loop
pred_start = "        $hasPredecessor = false;\n        foreach ($campaignTasks as $t) {\n            if (!empty($t->predecessor_code)) {\n                $hasPredecessor = true;\n                break;\n            }\n        }\n\n        // Gom tất cả candidate time vào 1 mảng\n        if (!$hasPredecessor || $stageCode == 1) {\n            $candidates[] = $now;\n        }"
pred_replace = "        // Gom tất cả candidate time vào 1 mảng\n        $candidates[] = $now;"
if pred_start in content:
    content = content.replace(pred_start, pred_replace)

# Change 3: Cycle time logic replacement (My previous fix)
cycle_start = "                    $pre_campaign_batch = DB::table('stage_plan')"
cycle_end = "                    $candidates[] = Carbon::parse($pred->end)->addMinutes($waite_time);\n                }"
idx_cycle_start = content.find(cycle_start)
idx_cycle_end = content.find(cycle_end, idx_cycle_start)
if idx_cycle_start != -1 and idx_cycle_end != -1:
    content = content[:idx_cycle_start] + "                    $candidates[] = Carbon::parse($pred->start)->addMinutes($waite_time);\n                }" + content[idx_cycle_end + len(cycle_end):]

# Change 4: User uncommented totalMunites = totalTimeCampaign
total_start = "            // Bỏ logic ghi đè bằng $totalTimeCampaign để phòng không bị booking khoảng thời gian trống sai lệch\n            // if ($totalTimeCampaign > 0 && $totalTimeCampaign > $totalMunites) {\n            //     $totalMunites = $totalTimeCampaign;\n            // }"
total_replace = "            if ($totalTimeCampaign > 0 && $totalTimeCampaign > $totalMunites) {\n                $totalMunites = $totalTimeCampaign;\n            }"
if total_start in content:
    content = content.replace(total_start, total_replace)

with open('c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php', 'w', encoding='utf-8') as f:
    f.write(content)
print("Fixes applied successfully")

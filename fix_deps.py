with open('c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php', 'r', encoding='utf-8') as f:
    content = f.read()

target = "->leftJoin('stage_plan as prev', 'prev.code', '=', 'sp.predecessor_code')"
replacement = "->leftJoin('stage_plan as prev', function ($join) {\n                $join->on('prev.code', '=', 'sp.predecessor_code')\n                     ->whereNotIn('prev.stage_code', [1, 2]);\n            })"

content = content.replace(target, replacement)

# Now scheduleCampaign
target_camp = "$pred = DB::table('stage_plan')->where('code', $campaignTask->predecessor_code)->first();\n\n            if ($pred) {"
replacement_camp = "$pred = DB::table('stage_plan')->where('code', $campaignTask->predecessor_code)->first();\n\n            if ($pred && !in_array($pred->stage_code, [1, 2])) {"
content = content.replace(target_camp, replacement_camp)

# Now sheduleNotCampaing
target_not_camp = "$pred = DB::table('stage_plan')->where('code', $task->predecessor_code)->first();\n\n        if ($pred) {"
replacement_not_camp = "$pred = DB::table('stage_plan')->where('code', $task->predecessor_code)->first();\n\n        if ($pred && !in_array($pred->stage_code, [1, 2])) {"
content = content.replace(target_not_camp, replacement_not_camp)

with open('c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php', 'w', encoding='utf-8') as f:
    f.write(content)
print("Updated forward dependencies")

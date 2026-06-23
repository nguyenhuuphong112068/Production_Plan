import re

file = 'c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php'
with open(file, 'r', encoding='utf-8') as f:
    content = f.read()

target_regex = r"public function scheduleAllPass2\(Request \$request\)\s*\{\s*\$overdueCampaigns = \$request->overdueCampaigns \?\? \[\];\s*if \(empty\(\$overdueCampaigns\)\) \{\s*return response\(\)->json\(\[\]\);\s*\}"

replacement = """public function scheduleAllPass2(Request $request)
    {
        $overdueCampaigns = $request->overdueCampaigns;

        if (empty($overdueCampaigns)) {
            $overdueCampaigns = $this->scanOverdueTasks();
        }

        if (empty($overdueCampaigns)) {
            return response()->json(['success' => false, 'message' => 'Không có sự kiện nào bị quá hạn biệt trữ.']);
        }"""

new_content = re.sub(target_regex, replacement, content)

if new_content != content:
    with open(file, 'w', encoding='utf-8') as f:
        f.write(new_content)
    print("Updated scheduleAllPass2 in SchedualController")
else:
    print("Failed to replace using regex")


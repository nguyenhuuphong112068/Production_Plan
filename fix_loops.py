import re

file = 'c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php'
with open(file, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Fix infinite loop in findEarliestSlot2
target1 = """        // =========================================================
        while (true) {
            $conflictFound = false;"""

replacement1 = """        // =========================================================
        $loop_count = 0;
        while (true) {
            $loop_count++;
            if ($loop_count > 1000) {
                return null;
            }
            $conflictFound = false;"""

if target1 in content:
    content = content.replace(target1, replacement1)
    print("Fixed findEarliestSlot2 infinite loop")
else:
    print("Could not find findEarliestSlot2 while loop")

# 2. Fix infinite loop in findEarliestSlot
target2 = """        $tryCount = 0;

        while (true) {"""

replacement2 = """        $tryCount = 0;
        $loop_count2 = 0;

        while (true) {
            $loop_count2++;
            if ($loop_count2 > 1000) {
                return null;
            }"""

if target2 in content:
    content = content.replace(target2, replacement2)
    print("Fixed findEarliestSlot infinite loop")
else:
    print("Could not find findEarliestSlot while loop")

# 3. Handle null in scheduleCampaign
target3 = """            $candidateStart = is_array($candidate) ? $candidate['start'] : $candidate;
            $candidateMoldId = is_array($candidate) ? $candidate['mold_id'] : null;

            if ($bestStart === null || $candidateStart->lt($bestStart)) {"""

replacement3 = """            $candidateStart = is_array($candidate) ? $candidate['start'] : $candidate;
            $candidateMoldId = is_array($candidate) ? $candidate['mold_id'] : null;

            if ($candidateStart !== null && ($bestStart === null || $candidateStart->lt($bestStart))) {"""

if target3 in content:
    content = content.replace(target3, replacement3)
    print("Handled null in scheduleCampaign")
else:
    print("Could not find scheduleCampaign candidate processing")
    
# 4. Set max_execution_time in scheduleAllPass2
target4 = """    public function scheduleAllPass2(Request $request)
    {
        $overdueCampaigns = $request->overdueCampaigns;"""

replacement4 = """    public function scheduleAllPass2(Request $request)
    {
        set_time_limit(1200);
        ini_set('max_execution_time', 1200);
        $overdueCampaigns = $request->overdueCampaigns;"""
        
if target4 in content:
    content = content.replace(target4, replacement4)
    print("Increased max_execution_time in scheduleAllPass2")
else:
    print("Could not find scheduleAllPass2 method start")

with open(file, 'w', encoding='utf-8') as f:
    f.write(content)

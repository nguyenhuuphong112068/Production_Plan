import re

with open('c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php', 'r', encoding='utf-8') as f:
    content = f.read()

# remove the first one I added
target = """    public function scheduleAllPass2(Request $request)
    {"""

# We just remove the block I added, up to `public function scheduleAll(Request $request)`
start_idx = content.find(target)
if start_idx != -1:
    end_idx = content.find("    public function scheduleAll(Request $request)", start_idx)
    if end_idx != -1:
        content = content[:start_idx] + content[end_idx:]
        with open('c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php', 'w', encoding='utf-8') as f:
            f.write(content)
        print("Removed the duplicate scheduleAllPass2")

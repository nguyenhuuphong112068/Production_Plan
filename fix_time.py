import re

file = 'c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php'
with open(file, 'r', encoding='utf-8') as f:
    content = f.read()

target = """    public function scheduleAll(Request $request)
    {"""

replacement = """    public function scheduleAll(Request $request)
    {
        set_time_limit(1200);
        ini_set('max_execution_time', 1200);"""

if target in content:
    content = content.replace(target, replacement)
    with open(file, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Increased max_execution_time in scheduleAll")
else:
    print("Could not find scheduleAll method start")

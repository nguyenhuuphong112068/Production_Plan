import re

file_path = r'C:\PMS\Production_Plan\app\Http\Controllers\Pages\Quarantine\QuarantineRoomController.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace 't.yields', with DB::raw("(SELECT SUM(yield) FROM yields WHERE stage_plan_id = t.id) AS yields"),
# But only in the select() clauses!
if "'t.yields'," in content:
    content = content.replace("'t.yields',", "DB::raw(\"(SELECT SUM(yield) FROM yields WHERE stage_plan_id = t.id) AS yields\"),")
    
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Replaced 't.yields', with subquery.")
else:
    print("Already replaced or not found.")

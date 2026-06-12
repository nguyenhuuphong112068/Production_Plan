import re

file_path = r'C:\PMS\Production_Plan\app\Http\Controllers\Pages\Quarantine\QuarantineRoomController.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace("'y.sum_yield as yields',", "DB::raw('COALESCE(y.sum_yield, t.yields) as yields'),")

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print('Updated query to use fallback')

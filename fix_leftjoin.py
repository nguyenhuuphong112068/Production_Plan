import re

file_path = r'C:\PMS\Production_Plan\app\Http\Controllers\Pages\Quarantine\QuarantineRoomController.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

join_stmt = "->leftJoin('room', 't2.resourceId', '=', 'room.id')\n                ->leftJoin(DB::raw('(SELECT stage_plan_id, SUM(yield) as sum_yield FROM yields GROUP BY stage_plan_id) as y'), 't.id', '=', 'y.stage_plan_id')"
content = content.replace("->leftJoin('room', 't2.resourceId', '=', 'room.id')", join_stmt)

join_stmt2 = "->leftJoin('room', 't.resourceId', 'room.id')\n                ->leftJoin(DB::raw('(SELECT stage_plan_id, SUM(yield) as sum_yield FROM yields GROUP BY stage_plan_id) as y'), 't.id', '=', 'y.stage_plan_id')"
content = content.replace("->leftJoin('room', 't.resourceId', 'room.id')", join_stmt2)

content = content.replace('DB::raw("(SELECT SUM(yield) FROM yields WHERE stage_plan_id = t.id) AS yields"),', "'y.sum_yield as yields',")

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print('Updated query to use leftJoin')

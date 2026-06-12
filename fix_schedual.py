import re

# 1. Update Blade file
view_path = r'C:\PMS\Production_Plan\resources\views\pages\Schedual\finised\dataTable.blade.php'
with open(view_path, 'r', encoding='utf-8') as f:
    view_content = f.read()

# Replace empty start_yield with pre-filled one
old_str_1 = '''                                    <input type="datetime-local" class="time start_yield" id = "start_yield"
                                        name="start_yield">'''
new_str_1 = '''                                    <input type="datetime-local" class="time start_yield" id="start_yield"
                                        name="start_yield"
                                        value="{{ \\Carbon\\Carbon::parse($data->actual_start ?? $data->start)->format('Y-m-d\\TH:i') }}">'''

view_content = view_content.replace(old_str_1, new_str_1)

with open(view_path, 'w', encoding='utf-8') as f:
    f.write(view_content)


# 2. Update Controller
controller_path = r'C:\PMS\Production_Plan\app\Http\Controllers\Pages\Schedual\SchedualFinisedController.php'
with open(controller_path, 'r', encoding='utf-8') as f:
    ctrl_content = f.read()

# Fix finding previous yield using SUM instead of value()
old_prev_yield = '''                        $previousYield = DB::table('yields')
                                ->where('stage_plan_id', $request->id)
                                ->value('yield');'''
new_prev_yield = '''                        $previousYield = DB::table('yields')
                                ->where('stage_plan_id', $request->id)
                                ->sum('yield');'''
ctrl_content = ctrl_content.replace(old_prev_yield, new_prev_yield)

# Fix updateOrInsert conditions
old_update_insert = '''                                DB::table('yields')->updateOrInsert(
                                        [
                                                'stage_plan_id' => $request->id,
                                                'start' => $actualStart,
                                                // 'end'   => $actualStartYield,
                                                'yield'   => $request->yields
                                        ],
                                        [
                                                'start'        => $actualStartYield,
                                                'end'          => $actualEnd,
                                                'yield'        => $request->yields ?? 0,
                                                'created_by'   => session('user')['fullName'],
                                                'created_date' => now(),
                                        ]
                                );'''

new_update_insert = '''                                DB::table('yields')->updateOrInsert(
                                        [
                                                'stage_plan_id' => $request->id,
                                                'start' => $actualStartYield
                                        ],
                                        [
                                                'end'          => $actualEnd,
                                                'yield'        => $request->yields ?? 0,
                                                'created_by'   => session('user')['fullName'],
                                                'created_date' => now(),
                                        ]
                                );'''
ctrl_content = ctrl_content.replace(old_update_insert, new_update_insert)

with open(controller_path, 'w', encoding='utf-8') as f:
    f.write(ctrl_content)

print("Done fixing files")

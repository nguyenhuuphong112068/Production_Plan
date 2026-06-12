import re

# 1. Update Controller
controller_path = r'C:\PMS\Production_Plan\app\Http\Controllers\Pages\Category\MaintenanceCategoryController.php'
with open(controller_path, 'r', encoding='utf-8') as f:
    controller_content = f.read()

history_counts_query = """
                $history_counts = DB::table('quota_maintenance_history')
                        ->select('category_id', DB::raw('count(*) as total'))
                        ->groupBy('category_id')
                        ->pluck('total', 'category_id')
                        ->toArray();

                $datas = collect();"""

if 'history_counts = DB::table' not in controller_content:
    controller_content = controller_content.replace('$datas = collect();', history_counts_query)
    controller_content = controller_content.replace("'created_at' => $quota->created_time,", "'created_at' => $quota->created_time,\n                                'history_count' => $history_counts[$quota->id] ?? 0,")
    
    with open(controller_path, 'w', encoding='utf-8') as f:
        f.write(controller_content)

# 2. Update dataTable.blade.php
blade_path = r'C:\PMS\Production_Plan\resources\views\pages\category\maintenance\dataTable.blade.php'
with open(blade_path, 'r', encoding='utf-8') as f:
    blade_content = f.read()

old_render = """render: function(data, type, row) {
                        return '<button type="button" class="btn btn-info btn-sm btn-history" data-id="' + row.id + '"><i class="fas fa-history"></i></button>';
                    }"""

new_render = """render: function(data, type, row) {
                        var badge = '';
                        if (row.history_count && row.history_count > 0) {
                            badge = '<span class="badge badge-danger" style="position: absolute; top: -5px; right: -5px; font-size: 10px; border-radius: 50%; padding: 3px 6px; box-shadow: 0 0 3px rgba(0,0,0,0.3);">' + row.history_count + '</span>';
                        }
                        return '<div style="position: relative; display: inline-block;"><button type="button" class="btn btn-info btn-sm btn-history" data-id="' + row.id + '"><i class="fas fa-history"></i></button>' + badge + '</div>';
                    }"""

if 'badge = \'<span class="badge badge-danger"' not in blade_content:
    blade_content = blade_content.replace(old_render, new_render)
    
    with open(blade_path, 'w', encoding='utf-8') as f:
        f.write(blade_content)

print("Added history count badge")

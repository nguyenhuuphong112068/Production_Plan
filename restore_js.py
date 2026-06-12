import os
import re

configs = [
    {
        "dir": r"C:\PMS\Production_Plan\resources\views\pages\materData\Unit",
        "route": "pages.materData.Unit.history",
        "fields": ["code", "name"]
    },
    {
        "dir": r"C:\PMS\Production_Plan\resources\views\pages\materData\StageGroup",
        "route": "pages.materData.StageGroup.history",
        "fields": ["code", "name"]
    },
    {
        "dir": r"C:\PMS\Production_Plan\resources\views\pages\materData\Specification",
        "route": "pages.materData.Specification.history",
        "fields": ["code", "name"]
    },
    {
        "dir": r"C:\PMS\Production_Plan\resources\views\pages\materData\source_material",
        "route": "pages.materData.source_material.history",
        "fields": ["active", "intermediate_code", "name"]
    },
    {
        "dir": r"C:\PMS\Production_Plan\resources\views\pages\materData\room",
        "route": "pages.materData.room.history",
        "fields": ["active", "code", "name", "main_equiment_name", "capacity", "stage_code", "blister_type_code", "production_group", "department_id"]
    },
    {
        "dir": r"C:\PMS\Production_Plan\resources\views\pages\materData\Market",
        "route": "pages.materData.Market.history",
        "fields": ["code", "name"]
    },
    {
        "dir": r"C:\PMS\Production_Plan\resources\views\pages\materData\productName",
        "route": "pages.materData.productName.history",
        "fields": ["active", "name", "short_name", "type_id"] 
    },
    {
        "dir": r"C:\PMS\Production_Plan\resources\views\pages\materData\Dosage",
        "route": "pages.materData.Dosage.history",
        "fields": ["code", "name"]
    },
    {
        "dir": r"C:\PMS\Production_Plan\resources\views\pages\materData\BlisterType",
        "route": "pages.materData.BlisterType.history",
        "fields": ["code", "name"]
    },
    {
        "dir": r"C:\PMS\Production_Plan\resources\views\pages\materData\Department",
        "route": "pages.materData.Department.history",
        "fields": ["active", "code", "name"]
    },
    {
        "dir": r"C:\PMS\Production_Plan\resources\views\pages\materData\BlisterMold",
        "route": "pages.materData.BlisterMold.history",
        "fields": ["code", "name"]
    },
    {
        "dir": r"C:\PMS\Production_Plan\resources\views\pages\category\intermediate",
        "route": "pages.category.intermediate.history",
        "fields": ["active", "intermediate_code", "product_name_id", "batch_size", "unit_batch_size", "batch_qty", "unit_batch_qty", "dosage_id"]
    },
    {
        "dir": r"C:\PMS\Production_Plan\resources\views\pages\category\product",
        "route": "pages.category.product.history",
        "fields": ["active", "finished_product_code", "intermediate_code", "product_name_id", "market_id", "specification_id", "blister_type_id", "blister_mold_id"]
    }
]

def generate_js(route, fields):
    js = f"""
<script>
    $(document).ready(function() {{
        $('.btn-history').off('click').on('click', function() {{
            var id = $(this).data('id');
            $.ajax({{
                url: "{{{{ route('{route}') }}}}",
                type: "GET",
                data: {{ id: id }},
                success: function(res) {{
                    var tbody = $('#data_table_history_body');
                    tbody.empty();
                    var current = res.current;
                    if (current) {{
                        var html = '<tr style="background-color: #e8f4f8; font-weight: bold;">';
                        html += '<td class="text-center align-middle">Hiện Hành</td>';
                        html += '<td class="text-center align-middle">' + (current.created_by ? current.created_by : '') + '</td>';
"""
    for f in fields:
        js += f"                        html += '<td class=\"text-center align-middle\">' + (current.{f} !== null ? current.{f} : '') + '</td>';\n"
    
    js += f"""                        html += '</tr>';
                        tbody.append(html);
                    }}

                    if(res.history.length === 0) {{
                        tbody.append('<tr><td colspan="100%" class="text-center align-middle">Chưa có lịch sử thay đổi</td></tr>');
                    }} else {{
                        res.history.forEach(function(item) {{
                            var html = '<tr>';
                            html += '<td class="text-center align-middle">' + (item.updated_at ? item.updated_at : item.created_at) + '</td>';
                            html += '<td class="text-center align-middle">' + (item.created_by ? item.created_by : '') + '</td>';
"""
    for f in fields:
        js += f"                            html += '<td class=\"text-center align-middle\">' + (item.{f} !== null ? item.{f} : '') + '</td>';\n"
    
    js += f"""                            html += '</tr>';
                            tbody.append(html);
                        }});
                    }}
                    $('#historyModal').modal('show');
                }},
                error: function() {{
                    Swal.fire('Lỗi', 'Không thể lấy lịch sử thay đổi', 'error');
                }}
            }});
        }});
    }});
</script>
"""
    return js

for cfg in configs:
    d = cfg["dir"]
    data_file = os.path.join(d, 'dataTable.blade.php')
    if os.path.exists(data_file):
        with open(data_file, 'r', encoding='utf-8') as f:
            content = f.read()

        # Remove existing if any
        if "url: \"{{ route('" + cfg["route"] + "') }}\"" in content:
            content = re.sub(r'<script>\s*\$\(document\)\.ready\(function\(\)\s*\{\s*\$\(\'\.btn-history\'\)[\s\S]*?</script>', '', content)
            # Or if it was injected via the bad script:
            content = re.sub(r"\$\('\.btn-history'\)\.off\('click'\)\.on\('click'[\s\S]*?Swal\.fire\('Lỗi'[^>]*\}\);[\s\n]*\}\);", "", content)

        js_block = generate_js(cfg["route"], cfg["fields"])
        
        # Append to the end
        content += "\n" + js_block

        with open(data_file, 'w', encoding='utf-8') as f:
            f.write(content)

print("JS appended to all dataTable.blade.php files!")

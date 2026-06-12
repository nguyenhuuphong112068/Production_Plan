import os
import re

configs = [
    {
        "dir": r"C:\PMS\Production_Plan\resources\views\pages\category\intermediate",
        "route": "pages.category.intermediate.history",
        "fields": ["active", "product_name", "intermediate_code", "dosage_name", "batch_size", "batch_qty", "quarantine_time_unit", "weight_1", "prepering", "blending", "forming", "coating", "excution_time"]
    },
    {
        "dir": r"C:\PMS\Production_Plan\resources\views\pages\category\product",
        "route": "pages.category.product.history",
        "fields": ["active", "product_name", "finished_product_code", "intermediate_code", "market_name", "specification_name", "batch_qty", "primary_parkaging"]
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
                data: {{ category_id: id }},
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
        js += f"                        html += '<td class=\"text-center align-middle\">' + (current.{f} !== null && current.{f} !== undefined ? current.{f} : '') + '</td>';\n"
    
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
        js += f"                            html += '<td class=\"text-center align-middle\">' + (item.{f} !== null && item.{f} !== undefined ? item.{f} : '') + '</td>';\n"
    
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

        # Remove the generic JS block we inserted earlier
        content = re.sub(r'<script>\s*\$\(document\)\.ready\(function\(\)\s*\{\s*\$\(\'\.btn-history\'\)[\s\S]*?</script>', '', content)
        
        js_block = generate_js(cfg["route"], cfg["fields"])
        
        # Append to the end
        content += "\n" + js_block

        with open(data_file, 'w', encoding='utf-8') as f:
            f.write(content)

print("JS appended for category!")

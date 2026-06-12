import re

# 1. Update Controller
controller_path = r'C:\PMS\Production_Plan\app\Http\Controllers\Pages\Schedual\SchedualFinisedController.php'
with open(controller_path, 'r', encoding='utf-8') as f:
    ctrl_content = f.read()

old_sql = "ROUND(SUM(t.`yield`), 2) as total_confirmed"
new_sql = "ROUND(SUM(t.`yield`), 2) as total_confirmed,\n                                        MAX(t.`end`) as max_yield_end"
ctrl_content = ctrl_content.replace(old_sql, new_sql)

old_select = 'DB::raw("COALESCE(y.total_confirmed,0) as total_confirmed")'
new_select = 'DB::raw("COALESCE(y.total_confirmed,0) as total_confirmed"),\n                                \'y.max_yield_end\''
ctrl_content = ctrl_content.replace(old_select, new_select)

with open(controller_path, 'w', encoding='utf-8') as f:
    f.write(ctrl_content)


# 2. Update Blade file
view_path = r'C:\PMS\Production_Plan\resources\views\pages\Schedual\finised\dataTable.blade.php'
with open(view_path, 'r', encoding='utf-8') as f:
    view_content = f.read()

old_blade = '''                                    <input type="datetime-local" class="time start_yield" id="start_yield"
                                        name="start_yield"
                                        value="{{ \\Carbon\\Carbon::parse($data->actual_start ?? $data->start)->format('Y-m-d\\TH:i') }}">'''

new_blade = '''                                    <input type="datetime-local" class="time start_yield" id="start_yield"
                                        name="start_yield"
                                        value="{{ $data->max_yield_end ? \\Carbon\\Carbon::parse($data->max_yield_end)->format('Y-m-d\\TH:i') : \\Carbon\\Carbon::parse($data->actual_start ?? $data->start)->format('Y-m-d\\TH:i') }}">
                                    <input type="hidden" class="max_yield_end" value="{{ $data->max_yield_end ? \\Carbon\\Carbon::parse($data->max_yield_end)->format('Y-m-d\\TH:i') : '' }}">'''

view_content = view_content.replace(old_blade, new_blade)

# Inject JS validation for max_yield_end
js_validation_old = "const startProd = new Date(startProdInput.value);"
js_validation_new = '''const startYieldInput = row.querySelector('#start_yield');
                const maxYieldEndStr = row.querySelector('.max_yield_end')?.value;
                if (maxYieldEndStr && startYieldInput && startYieldInput.value) {
                    if (new Date(startYieldInput.value) < new Date(maxYieldEndStr)) {
                        Swal.fire({
                            icon: "warning",
                            title: "Thời gian BĐCM không hợp lệ",
                            html: "Thời gian BĐCM (Bắt đầu tạo ra sản lượng) phải lớn hơn hoặc bằng thời gian Kết thúc của lần xác nhận trước đó (" + maxYieldEndStr.replace('T', ' ') + ")!<br><br><b>Vui lòng kiểm tra lại!</b>",
                            confirmButtonText: 'Kiểm tra lại',
                            confirmButtonColor: '#3085d6'
                        });
                        return;
                    }
                }
                const startProd = new Date(startProdInput.value);'''

view_content = view_content.replace(js_validation_old, js_validation_new)

# Apply to semi-finised as well
js_semi_old = "const start = row.querySelector('#start').value;"
js_semi_new = '''const startYieldInput = row.querySelector('#start_yield');
                const maxYieldEndStr = row.querySelector('.max_yield_end')?.value;
                if (maxYieldEndStr && startYieldInput && startYieldInput.value) {
                    if (new Date(startYieldInput.value) < new Date(maxYieldEndStr)) {
                        Swal.fire({
                            icon: "warning",
                            title: "Thời gian BĐCM không hợp lệ",
                            html: "Thời gian BĐCM (Bắt đầu tạo ra sản lượng) phải lớn hơn hoặc bằng thời gian Kết thúc của lần xác nhận trước đó (" + maxYieldEndStr.replace('T', ' ') + ")!<br><br><b>Vui lòng kiểm tra lại!</b>",
                            confirmButtonText: 'Kiểm tra lại',
                            confirmButtonColor: '#3085d6'
                        });
                        return;
                    }
                }
                const start = row.querySelector('#start').value;'''

view_content = view_content.replace(js_semi_old, js_semi_new)

with open(view_path, 'w', encoding='utf-8') as f:
    f.write(view_content)

print("Done")

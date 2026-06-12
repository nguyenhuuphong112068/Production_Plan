import os
import re

file_path = r'C:\PMS\Production_Plan\resources\views\pages\category\maintenance\dataTable.blade.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add column header
if '<th>Lịch Sử</th>' not in content:
    content = content.replace('<th>Vô Hiệu</th>', '<th>Vô Hiệu</th>\n                        <th>Lịch Sử</th>')

# 2. Add history column to DataTable columns array
history_column_js = """
                {
                    data: null,
                    className: 'text-center align-middle',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return '<button type="button" class="btn btn-info btn-sm btn-history" data-id="' + row.id + '"><i class="fas fa-history"></i></button>';
                    }
                }
"""
if 'btn-history' not in content:
    content = re.sub(r'(<i class="fas fa-trash"></i></button>\';\s*}\s*)}', r'\1},\n' + history_column_js, content)

# 3. Add Modal HTML
modal_html = """
<div class="modal fade" id="historyModal" tabindex="-1" role="dialog" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog history-modal-dialog" role="document" style="max-width: 90% !important; width: 90% !important;">
        <div class="modal-content">
            <div class="modal-header">
                <a href="{{ route('pages.general.home') }}" class="mr-3">
                    <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.85; max-width: 42px;">
                </a>

                <h5 class="modal-title w-100 text-center" id="historyModalLabel">
                    Lịch Sử Thay Đổi: Cài Đặt Bảo Trì
                </h5>

                <button type="button" class="close ml-auto" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table id="data_table_history" class="table table-bordered table-striped w-100">
                    <thead id="data_table_history_head">
                        <tr>
                            <th class="text-center align-middle">Ngày Sửa</th>
                            <th class="text-center align-middle">Người Sửa</th>
                            <th class="text-center align-middle">Mã TB Lớn</th>
                            <th class="text-center align-middle">Tên TB Lớn</th>
                            <th class="text-center align-middle">Mã TB Con</th>
                            <th class="text-center align-middle">Tên TB Con</th>
                            <th class="text-center align-middle">Tần Suất BT-HC</th>
                            <th class="text-center align-middle">Vị Trí Lắp Đặt</th>
                            <th class="text-center align-middle">Phân Xưởng</th>
                            <th class="text-center align-middle">Phòng SX Liên Quan</th>
                            <th class="text-center align-middle">Thời Gian TH</th>
                        </tr>
                    </thead>
                    <tbody id="data_table_history_body">
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Đóng</button>
            </div>
        </div>
    </div>
</div>
"""
if 'historyModal' not in content:
    content = content.replace('<script src="{{ asset(\'js/vendor/jquery-1.12.4.min.js\') }}"></script>', modal_html + '\n<script src="{{ asset(\'js/vendor/jquery-1.12.4.min.js\') }}"></script>')

# 4. Add JS logic
js_logic = """
<script>
    $(document).ready(function() {
        $(document).on('click', '.btn-history', function() {
            var id = $(this).data('id');
            $.ajax({
                url: "{{ route('pages.category.maintenance.history') }}",
                type: "GET",
                data: { id: id },
                success: function(res) {
                    var tbody = $('#data_table_history_body');
                    tbody.empty();
                    var current = res.current;
                    if (current) {
                        var modifier = current.created_by || current.prepareBy || current.prepared_by || '';
                        var html = '<tr style="background-color: #e8f4f8; font-weight: bold;">';
                        html += '<td class="text-center align-middle">Hiện Hành</td>';
                        html += '<td class="text-center align-middle">' + modifier + '</td>';
                        html += '<td class="text-center align-middle">' + (current.parent_eqp_id || '') + '</td>';
                        html += '<td class="text-center align-middle">' + (current.Eqp_name || '') + '</td>';
                        html += '<td class="text-center align-middle">' + (current.inst_id || '') + '</td>';
                        html += '<td class="text-center align-middle">' + (current.inst_name || '') + '</td>';
                        html += '<td class="text-center align-middle">' + (current.Inst_sch_type || '') + '</td>';
                        html += '<td class="text-center align-middle">' + (current.block || '') + '</td>';
                        html += '<td class="text-center align-middle">' + (current.deparment_code || '') + '</td>';
                        html += '<td class="text-center align-middle">' + (current.room_names || '') + '</td>';
                        html += '<td class="text-center align-middle">' + (current.exe_time || '') + '</td>';
                        html += '</tr>';
                        tbody.append(html);
                    }

                    if(res.history.length === 0) {
                        tbody.append('<tr><td colspan="100%" class="text-center align-middle">Chưa có lịch sử thay đổi</td></tr>');
                    } else {
                        res.history.forEach(function(item) {
                            var modifier = item.created_by || item.prepareBy || item.prepared_by || '';
                            var html = '<tr>';
                            html += '<td class="text-center align-middle">' + (item.updated_at ? item.updated_at : item.created_at) + '</td>';
                            html += '<td class="text-center align-middle">' + modifier + '</td>';
                            html += '<td class="text-center align-middle">' + (item.parent_eqp_id || '') + '</td>';
                            html += '<td class="text-center align-middle">' + (item.Eqp_name || '') + '</td>';
                            html += '<td class="text-center align-middle">' + (item.inst_id || '') + '</td>';
                            html += '<td class="text-center align-middle">' + (item.inst_name || '') + '</td>';
                            html += '<td class="text-center align-middle">' + (item.Inst_sch_type || '') + '</td>';
                            html += '<td class="text-center align-middle">' + (item.block || '') + '</td>';
                            html += '<td class="text-center align-middle">' + (item.deparment_code || '') + '</td>';
                            html += '<td class="text-center align-middle">' + (item.room_names || '') + '</td>';
                            html += '<td class="text-center align-middle">' + (item.exe_time || '') + '</td>';
                            html += '</tr>';
                            tbody.append(html);
                        });
                    }
                    $('#historyModal').modal('show');
                },
                error: function() {
                    Swal.fire('Lỗi', 'Không thể lấy lịch sử thay đổi', 'error');
                }
            });
        });
    });
</script>
"""
if '$(document).on(\'click\', \'.btn-history\'' not in content:
    content += '\n' + js_logic

# Add the style for the history modal!
css_style = """<style>
    .history-modal-dialog {
        max-width: 90% !important;
        width: 90% !important;
        margin: 1.75rem auto;
    }

    #historyModal .modal-content {
        background-color: #ffffff;
        border-radius: 10px;
        overflow: hidden;
    }

    #historyModal .modal-header {
        background-color: #ffffff;
        border-bottom: 2px solid #CDC717;
        padding: 14px 20px;
    }

    #historyModal .modal-title {
        color: #003A4F;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    #historyModal .modal-body {
        padding: 0;
        max-height: 75vh;
        overflow-y: auto;
        overflow-x: auto;
        background: #ffffff;
    }

    #historyModal .modal-footer {
        background-color: #f8f9fa;
        border-top: 1px solid #dee2e6;
    }

    #data_table_history {
        font-size: 14px;
        margin-bottom: 0;
    }

    #data_table_history thead th {
        background-color: #f4f6f9 !important;
        color: #003A4F !important;
        font-weight: 700;
        white-space: nowrap;
        padding: 10px;
        position: sticky;
        top: 0;
        z-index: 10;
        text-align: center;
        border-bottom: 2px solid #dee2e6;
    }

    #data_table_history tbody td {
        padding: 8px 10px;
        vertical-align: middle;
        text-align: center;
    }
</style>
"""
if '.history-modal-dialog' not in content:
    content = css_style + content

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print('Injected history into Maintenance')

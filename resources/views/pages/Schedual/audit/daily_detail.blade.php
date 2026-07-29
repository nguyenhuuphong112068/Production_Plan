<style>
    .daily-detail-dialog {
        max-width: 95% !important;
        width: 95% !important;
        margin: 1.75rem auto;
    }

    #dailyDetailModal .modal-content {
        background-color: #ffffff;
        border-radius: 10px;
        overflow: hidden;
    }

    #dailyDetailModal .modal-header {
        background-color: #ffffff;
        border-bottom: 2px solid #CDC717;
        padding: 14px 20px;
    }

    #dailyDetailModal .modal-title {
        color: #003A4F;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    #dailyDetailModal .modal-body {
        padding: 12px 16px;
        max-height: 78vh;
        overflow-y: auto;
        overflow-x: auto;
        background: #ffffff;
    }

    #dailyDetailModal .modal-footer {
        background-color: #f8f9fa;
        border-top: 1px solid #dee2e6;
    }

    #daily_detail_summary {
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
        align-items: center;
        margin-bottom: 12px;
        padding: 10px 14px;
        border: 1px solid #d5e3ea;
        border-left: 4px solid #003A4F;
        border-radius: 8px;
        background: #f4f9fb;
        font-size: 14px;
        color: #003A4F;
    }

    #daily_detail_summary b {
        font-size: 16px;
    }

    #data_table_daily_detail {
        font-size: 13px;
    }

    #data_table_daily_detail thead th {
        background-color: #CDC717 !important;
        color: #003A4F !important;
        font-weight: 700;
        white-space: nowrap;
        padding: 6px 10px;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    #data_table_daily_detail tbody td {
        padding: 5px 8px;
        vertical-align: middle;
    }

    .dd-old {
        color: #b02a37;
        text-decoration: line-through;
        white-space: nowrap;
    }

    .dd-new {
        color: #0f5132;
        font-weight: 600;
        white-space: nowrap;
    }
</style>

<div class="modal fade" id="dailyDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog daily-detail-dialog" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <a href="{{ route('pages.general.home') }}" class="mr-3">
                    <img src="{{ asset('img/iconstella.svg') }}" style="opacity: 0.85; max-width: 42px;">
                </a>

                <h5 class="modal-title" id="dailyDetailModalLabel">
                    Chi Tiết Thay Đổi Lịch Sản Xuất
                </h5>

                <button type="button" class="close ml-auto" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div id="daily_detail_summary"></div>

                <div class="table-responsive">
                    <table id="data_table_daily_detail" class="table table-bordered table-striped w-100">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Mã Sản Phẩm</th>
                                <th>Sản Phẩm</th>
                                <th>Số Lô</th>
                                <th>Công Đoạn</th>
                                <th>Phòng</th>
                                <th>Thời Gian Sản Xuất</th>
                                <th>Thời Gian Vệ Sinh</th>
                                @if ($canViewReason)
                                    <th>Lý Do</th>
                                @endif
                                <th>Người Thay Đổi / Thời Điểm</th>
                                <th>Version</th>
                            </tr>
                        </thead>
                        <tbody id="data_table_daily_detail_body"></tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Đóng
                </button>
            </div>

        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Quyền xem lý do: Admin / Schedualer hoặc user thuộc phòng COMP
        const CAN_VIEW_REASON = @json($canViewReason);
        const COL_COUNT = CAN_VIEW_REASON ? 11 : 10;

        const STAGE_NAMES = {
            1: 'Cân NL',
            2: 'Cân NL Khác',
            3: 'Pha Chế',
            4: 'Trộn Hoàn Tất',
            5: 'Định Hình',
            6: 'Bao Phim',
            7: 'Đóng Gói',
            8: 'Bảo Trì - Hiệu Chuẩn',
            9: 'Khác'
        };

        let dailyDT = null;

        function esc(val) {
            if (val === null || val === undefined) return '';
            return $('<div>').text(val).html();
        }

        // "yyyy-mm-dd HH:mm:ss" → "dd/mm/yyyy HH:mm"
        function fmtDT(val) {
            if (!val) return '-';
            const s = String(val).replace('T', ' ');
            const [datePart, timePart] = s.split(' ');
            if (!datePart) return '-';
            const [y, m, d] = datePart.split('-');
            const hm = timePart ? timePart.substring(0, 5) : '';
            return `${d}/${m}/${y}${hm ? ' ' + hm : ''}`;
        }

        // Hiển thị "cũ → mới", chỉ gạch giá trị cũ khi thực sự khác
        function fmtChange(oldVal, newVal, formatter) {
            const fmt = formatter || fmtDT;
            const newTxt = fmt(newVal);
            if (!oldVal || String(oldVal) === String(newVal)) return `<span class="dd-new">${newTxt}</span>`;
            return `<span class="dd-old">${fmt(oldVal)}</span><br><span class="dd-new">${newTxt}</span>`;
        }

        function fmtRoom(name, code) {
            if (!name && !code) return '-';
            return [name, code].filter(Boolean).join(' - ');
        }

        $(document).on('click', '.btn-daily-detail', function() {
            const date = $(this).data('date');
            const dateText = $(this).data('date-text');
            const reason = $(this).data('reason') || '';

            if (dailyDT) {
                dailyDT.destroy();
                dailyDT = null;
            }
            $('#data_table_daily_detail_body').empty();
            $('#daily_detail_summary').html('Đang tải dữ liệu...');
            $('#dailyDetailModal').modal('show');

            $.ajax({
                url: '{{ route('pages.Schedual.audit.daily') }}',
                method: 'GET',
                data: {
                    date: date,
                    reason: reason
                },
                success: function(rows) {
                    rows = rows || [];

                    const reasons = [...new Set(rows.map(r => (r.type_of_change || '')
                        .trim() || 'Không ghi nhận lý do'))];
                    const users = [...new Set(rows.map(r => r.created_by).filter(Boolean))];
                    const plans = [...new Set(rows.map(r => r.stage_plan_id))];

                    $('#daily_detail_summary').html(
                        `<span>Ngày thay đổi: <b>${esc(dateText)}</b></span>` +
                        (CAN_VIEW_REASON ?
                            `<span>Số lần thay đổi: <b>${reasons.length}</b></span>` : '') +
                        `<span>Số lô liên quan: <b>${plans.length}</b></span>` +
                        `<span>Người thay đổi: <b>${esc(users.join(', ')) || '-'}</b></span>` +
                        (CAN_VIEW_REASON && reason ?
                            `<span>Lọc theo lý do: <b>${esc(reason)}</b></span>` : '')
                    );

                    if (rows.length === 0) {
                        $('#data_table_daily_detail_body').html(
                            `<tr><td colspan="${COL_COUNT}" class="text-center text-muted">Không có dữ liệu</td></tr>`
                        );
                    } else {
                        const html = rows.map(function(r, idx) {
                            const code = esc(r.intermediate_code || '') + (r
                                .finished_product_code ?
                                '<br>' + esc(r.finished_product_code) : '');
                            const stage = esc(r.stage || STAGE_NAMES[r.stage_code] || r
                                .stage_code || '-');
                            const room = fmtChange(
                                r.prev_room_name || r.prev_room_code ? fmtRoom(r
                                    .prev_room_name, r.prev_room_code) : '',
                                fmtRoom(r.room_name, r.room_code),
                                v => esc(v || '-')
                            );
                            const prod = esc(r.product_name || r.title || '-');
                            const batchQty = [r.batch_qty, r.unit_batch_qty].filter(
                                Boolean).join(' ');

                            return `<tr>
                                    <td class="text-center">${idx + 1}</td>
                                    <td>${code || '-'}</td>
                                    <td>${prod}${batchQty ? '<br><small class="text-muted">' + esc(batchQty) + '</small>' : ''}</td>
                                    <td class="text-center">${esc(r.batch || '-')}</td>
                                    <td>${stage}</td>
                                    <td>${room}</td>
                                    <td>${fmtChange(r.prev_start, r.start)}<br>${fmtChange(r.prev_end, r.end)}</td>
                                    <td>${fmtDT(r.start_clearning)}<br>${fmtDT(r.end_clearning)}</td>
                                    ${CAN_VIEW_REASON ? `<td>${esc(r.type_of_change || 'Không ghi nhận lý do')}</td>` : ''}
                                    <td>${esc(r.created_by || '-')}<br>${fmtDT(r.created_date)}</td>
                                    <td class="text-center">${esc(r.version)}</td>
                                </tr>`;
                        }).join('');

                        $('#data_table_daily_detail_body').html(html);
                    }

                    dailyDT = $('#data_table_daily_detail').DataTable({
                        paging: true,
                        pageLength: 25,
                        deferRender: true,
                        lengthMenu: [
                            [25, 50, 100, -1],
                            [25, 50, 100, "Tất cả"]
                        ],
                        searching: true,
                        ordering: true,
                        info: true,
                        autoWidth: false,
                        language: {
                            search: "Tìm kiếm:",
                            lengthMenu: "Hiển thị _MENU_ dòng",
                            info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
                            zeroRecords: "Không có dữ liệu",
                            emptyTable: "Không có dữ liệu",
                            paginate: {
                                previous: "Trước",
                                next: "Sau"
                            }
                        },
                    });
                },
                error: function(xhr) {
                    console.error('Daily detail error:', xhr.status, xhr.responseText);
                    $('#daily_detail_summary').html('');
                    $('#data_table_daily_detail_body').html(
                        `<tr><td colspan="${COL_COUNT}" class="text-center text-danger">Lỗi tải dữ liệu (HTTP ` +
                        xhr.status + ')</td></tr>');
                }
            });
        });
    });
</script>

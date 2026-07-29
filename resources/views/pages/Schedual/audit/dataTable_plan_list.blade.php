<style>
    .audit-filter {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 12px;
        margin-bottom: 16px;
        padding: 12px 14px;
        border: 1px solid #d5e3ea;
        border-left: 4px solid #003A4F;
        border-radius: 8px;
        background: #f4f9fb;
    }

    .audit-filter-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .audit-filter-group label {
        margin: 0;
        font-size: 13px;
        font-weight: 600;
        color: #003A4F;
    }

    .audit-filter-group .form-control {
        width: 175px;
        height: 38px;
    }

    .audit-quick {
        display: flex;
        gap: 6px;
        padding-bottom: 2px;
    }

    .audit-stats {
        display: flex;
        gap: 10px;
        margin-left: auto;
    }

    .audit-stat {
        min-width: 120px;
        padding: 6px 14px;
        border: 1px solid #d5e3ea;
        border-radius: 8px;
        background: #ffffff;
        text-align: center;
    }

    .audit-stat-main {
        background: #003A4F;
        border-color: #003A4F;
    }

    .audit-stat-value {
        display: block;
        font-size: 22px;
        font-weight: 700;
        line-height: 1.2;
        color: #003A4F;
    }

    .audit-stat-main .audit-stat-value {
        color: #CDC717;
    }

    .audit-stat-label {
        display: block;
        font-size: 12px;
        color: #6c757d;
    }

    .audit-stat-main .audit-stat-label {
        color: #e8eef1;
    }

    #data_table_audit_daily {
        font-size: 15px;
    }

    #data_table_audit_daily thead th {
        background-color: #CDC717;
        color: #003A4F;
        font-weight: 700;
        vertical-align: middle;
        text-align: center;
        white-space: nowrap;
    }

    #data_table_audit_daily tbody td {
        vertical-align: middle;
    }

    .audit-date {
        font-weight: 700;
        color: #003A4F;
        white-space: nowrap;
    }

    .audit-date small {
        display: block;
        font-weight: 400;
        color: #6c757d;
        font-size: 12px;
    }

    .audit-count {
        display: inline-block;
        min-width: 42px;
        padding: 4px 12px;
        border-radius: 20px;
        background-color: #003A4F;
        color: #fff;
        font-weight: 700;
        font-size: 16px;
    }

    .audit-reason {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        max-width: 100%;
        margin: 2px 4px 2px 0;
        padding: 4px 10px;
        border-radius: 6px;
        background-color: #fffbe6;
        border: 1px solid #ffe066;
        color: #7a5c00;
        font-size: 13.5px;
        line-height: 1.3;
    }

    .audit-reason.btn-daily-detail {
        cursor: pointer;
        transition: background-color .15s ease, box-shadow .15s ease;
    }

    .audit-reason.btn-daily-detail:hover {
        background-color: #fff3bf;
        box-shadow: 0 0 0 2px rgba(255, 193, 7, .25);
    }

    .audit-reason .badge {
        background-color: #ffc107;
        color: #4a3800;
        font-size: 11px;
    }

    .audit-user {
        display: inline-block;
        margin: 2px 4px 2px 0;
        padding: 3px 10px;
        border-radius: 20px;
        background-color: #eef4f7;
        border: 1px solid #d5e3ea;
        color: #003A4F;
        font-size: 13px;
        white-space: nowrap;
    }
</style>

<div class="content-wrapper">
    <div class="card">
        <div class="card-body mt-5" style="height: 96vh; overflow-y: auto;">

            <form id="auditFilterForm" method="GET" action="{{ route('pages.Schedual.audit.index') }}"
                class="audit-filter">
                <div class="audit-filter-group">
                    <label for="from_date">Từ ngày</label>
                    <input type="date" id="from_date" name="from_date" class="form-control"
                        value="{{ $summary->from }}">
                </div>

                <div class="audit-filter-group">
                    <label for="to_date">Đến ngày</label>
                    <input type="date" id="to_date" name="to_date" class="form-control"
                        value="{{ $summary->to }}">
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter mr-1"></i> Lọc
                </button>

                <div class="audit-quick">
                    <button type="button" class="btn btn-outline-secondary btn-sm audit-quick-btn"
                        data-range="7">7 ngày</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm audit-quick-btn"
                        data-range="30">30 ngày</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm audit-quick-btn"
                        data-range="month">Tháng này</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm audit-quick-btn"
                        data-range="year">Năm nay</button>
                </div>

                <div class="audit-stats">
                    <div class="audit-stat audit-stat-main">
                        <span class="audit-stat-value">{{ number_format($summary->change_count) }}</span>
                        <span class="audit-stat-label">Lần thay đổi</span>
                    </div>
                    <div class="audit-stat">
                        <span class="audit-stat-value">{{ number_format($summary->day_count) }}</span>
                        <span class="audit-stat-label">Ngày có thay đổi</span>
                    </div>
                    <div class="audit-stat">
                        <span class="audit-stat-value">{{ number_format($summary->plan_count) }}</span>
                        <span class="audit-stat-label">Lô bị ảnh hưởng</span>
                    </div>
                </div>
            </form>

            <table id="data_table_audit_daily" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th style="width: 60px;">STT</th>
                        <th style="width: 160px;">Ngày Thay Đổi</th>
                        <th style="width: 140px;">Số Lần Thay Đổi</th>
                        @if ($canViewReason)
                            <th>Lý Do</th>
                        @endif
                        <th style="width: 220px;">Người Thay Đổi</th>
                        <th style="width: 100px;">Chi Tiết</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($datas as $data)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>

                            <td class="text-center audit-date" data-order="{{ $data->change_date }}">
                                {{ \Carbon\Carbon::parse($data->change_date)->format('d/m/Y') }}
                                <small>{{ $data->plan_count }} lượt lô bị ảnh hưởng</small>
                            </td>

                            <td class="text-center" data-order="{{ $data->change_count }}">
                                <span class="audit-count">{{ $data->change_count }}</span>
                            </td>

                            @if ($canViewReason)
                                <td>
                                    @foreach ($data->reasons as $reason)
                                        <span class="audit-reason btn-daily-detail" title="Xem chi tiết theo lý do này"
                                            data-date="{{ $data->change_date }}"
                                            data-date-text="{{ \Carbon\Carbon::parse($data->change_date)->format('d/m/Y') }}"
                                            data-reason="{{ $reason->reason }}">
                                            {{ $reason->reason }}
                                            <span class="badge">{{ $reason->plan_count }}</span>
                                        </span>
                                    @endforeach
                                </td>
                            @endif

                            <td>
                                @forelse ($data->changed_by as $user)
                                    <span class="audit-user">{{ $user }}</span>
                                @empty
                                    <span class="text-muted">NA</span>
                                @endforelse
                            </td>

                            <td class="text-center">
                                <button type="button" class="btn btn-success btn-daily-detail"
                                    data-date="{{ $data->change_date }}"
                                    data-date-text="{{ \Carbon\Carbon::parse($data->change_date)->format('d/m/Y') }}">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";

        // Chọn nhanh khoảng thời gian rồi lọc lại
        $('.audit-quick-btn').on('click', function() {
            const range = $(this).data('range');
            const today = new Date();
            let from = new Date();

            if (range === 'month') {
                from = new Date(today.getFullYear(), today.getMonth(), 1);
            } else if (range === 'year') {
                from = new Date(today.getFullYear(), 0, 1);
            } else {
                from.setDate(today.getDate() - (parseInt(range, 10) - 1));
            }

            const fmt = d => d.getFullYear() + '-' +
                String(d.getMonth() + 1).padStart(2, '0') + '-' +
                String(d.getDate()).padStart(2, '0');

            $('#from_date').val(fmt(from));
            $('#to_date').val(fmt(today));
            $('#auditFilterForm').submit();
        });

        $('#data_table_audit_daily').DataTable({
            paging: true,
            lengthChange: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            pageLength: 25,
            order: [
                [1, 'desc']
            ],
            columnDefs: [{
                orderable: false,
                targets: [0, -1] // STT & Chi Tiết
            }],
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Tất cả"]
            ],
            language: {
                search: "Tìm kiếm:",
                lengthMenu: "Hiển thị _MENU_ dòng",
                info: "Hiển thị _START_ đến _END_ của _TOTAL_ dòng",
                zeroRecords: "Không có dữ liệu thay đổi",
                emptyTable: "Không có dữ liệu thay đổi",
                paginate: {
                    previous: "Trước",
                    next: "Sau"
                }
            },
        });
    });
</script>

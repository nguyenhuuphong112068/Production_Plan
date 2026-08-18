@php
    // Gợi ý người đọc hồ sơ từ những tên đã được nhập trước đó
    $readerOptions = $records->pluck('reader')->filter()->unique()->sort()->values();
@endphp

<form method="GET" action="{{ route('pages.plan.kcs_tracking.list') }}" class="form-row align-items-end mb-3">
    <div class="col-md-2">
        <label class="mb-1"><i class="fas fa-industry text-primary"></i> Phân Xưởng</label>
        <select class="form-control form-control-sm" name="department">
            <option value="">Tất cả phân xưởng</option>
            @foreach ($departments as $code => $name)
                <option value="{{ $code }}" {{ $code === $department ? 'selected' : '' }}>{{ $name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="mb-1 {{ $kcsMonth !== '' ? 'text-muted' : '' }}">
            Từ tháng kế hoạch
            @if ($kcsMonth !== '')
                <i class="fas fa-ban" title="Không áp dụng khi đang lọc theo Tháng KCS"></i>
            @endif
        </label>
        <input type="month" class="form-control form-control-sm" name="from_month" value="{{ $fromMonth }}"
            {{ $kcsMonth !== '' ? 'style=opacity:.5' : '' }}>
    </div>
    <div class="col-md-2">
        <label class="mb-1 {{ $kcsMonth !== '' ? 'text-muted' : '' }}">
            Đến tháng kế hoạch
            @if ($kcsMonth !== '')
                <i class="fas fa-ban" title="Không áp dụng khi đang lọc theo Tháng KCS"></i>
            @endif
        </label>
        <input type="month" class="form-control form-control-sm" name="to_month" value="{{ $toMonth }}"
            {{ $kcsMonth !== '' ? 'style=opacity:.5' : '' }}>
    </div>
    <div class="col-md-2">
        <label class="mb-1" title="Lọc theo tháng của Ngày KCS thật (từ MMS), không phải tháng kế hoạch.">
            <i class="fas fa-clipboard-check text-success"></i> Tháng KCS
        </label>
        <input type="month" class="form-control form-control-sm" name="kcs_month" value="{{ $kcsMonth }}">
    </div>
    <div class="col-md-2">
        <label class="mb-1" title="Chỉ lô đã có dòng theo dõi và đã chấm được kết quả mới hiện ra.">
            <i class="fas fa-check-circle text-success"></i> Kết Quả
        </label>
        <select class="form-control form-control-sm" name="result">
            <option value="">Tất cả</option>
            <option value="{{ \App\Models\PlanMasterKcs::RESULT_MET }}"
                {{ $result === \App\Models\PlanMasterKcs::RESULT_MET ? 'selected' : '' }}>Đáp Ứng</option>
            <option value="{{ \App\Models\PlanMasterKcs::RESULT_NOT_MET }}"
                {{ $result === \App\Models\PlanMasterKcs::RESULT_NOT_MET ? 'selected' : '' }}>Trễ Hạn</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="mb-1">Tìm kiếm</label>
        <input type="text" class="form-control form-control-sm" name="keyword" value="{{ $keyword }}"
            placeholder="Mã / tên SP / số lô...">
    </div>
    <div class="col-12 mt-2">
        <input type="hidden" name="summary_year" value="{{ $summaryYear }}">
        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i> Lọc</button>
        <a href="{{ route('pages.plan.kcs_tracking.list') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-redo"></i> Mặc định
        </a>
        <span class="ml-2 text-muted small">{{ $datas->count() }} lô</span>
        @if ($kcsMonth !== '')
            <span class="badge badge-success">KCS {{ \Carbon\Carbon::parse($kcsMonth . '-01')->format('m/Y') }}</span>
        @endif
        @if ($result !== '')
            <span
                class="badge {{ $result === \App\Models\PlanMasterKcs::RESULT_MET ? 'badge-success' : 'badge-danger' }}">
                {{ $result === \App\Models\PlanMasterKcs::RESULT_MET ? 'Đáp Ứng' : 'Trễ Hạn' }}
            </span>
        @endif
        @if ($kcsMonth !== '')
            <span class="text-muted small ml-2">
                <i class="fas fa-info-circle"></i>
                Đang lọc theo <b>Tháng KCS</b> nên <b>khoảng tháng kế hoạch không áp dụng</b> - lô kế hoạch
                tháng trước vẫn hiện nếu được KCS trong tháng này, nhờ vậy số lô khớp với tab Tổng Kết Tỉ Lệ.
            </span>
        @endif
    </div>
</form>
{{--
@unless ($canUpdate)
     <div class="alert alert-warning py-2">
        <i class="fas fa-lock"></i> Bạn chỉ có quyền xem. Liên hệ quản trị để được cấp quyền
        <b>Cập Nhật Theo Dõi Hồ Sơ KCS</b>.
    </div> 
@endunless --}}

@unless ($mmsAvailable)
    <div class="alert alert-secondary py-2 small">
        <i class="fas fa-plug"></i> Chưa kết nối được <b>MMS</b>: các ô <b>Ngày KCS</b> và
        <b>Số Phiếu COATP</b> không được điền sẵn, vui lòng nhập tay.
    </div>
@endunless

{{-- Cố ý không có banner tổng cho phần MMS: ô nền xanh đã cho biết dữ liệu lấy từ MMS,
     còn lô lệch mã TP thì cảnh báo ngay tại nhãn "MMS: <mã>" ở cột Mã TP của đúng dòng đó. --}}

{{-- <div class="alert alert-light border py-2 small">
    <i class="fas fa-info-circle text-info"></i>
    Dòng có dấu <i class="fas fa-circle text-muted"></i> là lô <b>chưa lưu theo dõi KCS</b>: các ô ngày đã được
    điền sẵn từ kế hoạch (ngày ra hồ sơ, ngày KCS) chỉ để tham khảo và
    <b>chưa được tính vào bảng tổng kết</b>. Hãy bổ sung <b>Ngày Đọc Xong HS</b> rồi lưu để lô được chấm kết quả.
    Mỗi ô sửa xong sẽ tự lưu cả dòng.
</div> --}}

<datalist id="kcs_reader_options">
    @foreach ($readerOptions as $reader)
        <option value="{{ $reader }}"></option>
    @endforeach
</datalist>

<div class="table-responsive" id="kcs_table_scroll">
    <table class="table table-bordered table-sm table-hover mb-0" id="kcs_tracking_table">
        <thead class="text-center align-middle">
            <tr>
                <th class="kcs-sticky kcs-sticky-1 bg-light" style="min-width: 50px;">STT</th>
                <th class="kcs-sticky kcs-sticky-2 bg-light" style="min-width: 100px;">Số Lệnh</th>
                <th class="kcs-sticky kcs-sticky-3 bg-light" style="min-width: 90px;">Số Lô</th>
                <th class="kcs-sticky kcs-sticky-4 bg-light" style="min-width: 40px;">LS</th>
                <th class="kcs-sticky kcs-sticky-5 bg-light" style="min-width: 200px;">Tên Sản Phẩm</th>
                <th class="bg-light" style="min-width: 130px;">Mã BTP</th>
                <th class="bg-light" style="min-width: 130px;">Mã TP</th>
                <th class="bg-light" style="min-width: 110px;">Thị Trường</th>
                <th class="bg-light" style="min-width: 150px;">Quy Cách</th>
                <th class="bg-light" style="min-width: 100px;">Cỡ Lô</th>
                <th class="bg-light" style="min-width: 70px;">Lô TĐ</th>

                <th class="bg-warning" style="min-width: 140px;">Ngày Nhận Hồ Sơ</th>
                <th class="bg-warning" style="min-width: 110px;">Người Đọc</th>
                <th class="bg-warning" style="min-width: 140px;">Ngày Đọc Xong HS</th>
                <th class="bg-warning" style="min-width: 120px;">Số Phiếu COATP</th>
                <th class="bg-warning" style="min-width: 140px;"
                    title="Ngày KCS nhận được COATP. Không phải &quot;Ngày ra phiếu TP&quot; của QC ở trang Phản Hồi Kế Hoạch Tháng.">
                    Ngày Nhận COATP</th>
                <th class="bg-warning" style="min-width: 100px;">DR/IR</th>
                <th class="bg-warning" style="min-width: 140px;">Ngày AP DR/IR</th>
                <th class="bg-warning" style="min-width: 100px;">OOS</th>
                <th class="bg-warning" style="min-width: 140px;">Ngày AP OOS</th>
                <th class="bg-warning" style="min-width: 140px;">Ngày AP DR/IR KCQ</th>
                <th class="bg-warning" style="min-width: 140px;">Ngày AP OPV/PVR</th>
                <th class="bg-warning" style="min-width: 150px;"
                    title="Ngày lô được đưa vào hàng chờ KCS đúng thứ tự lô. Cũng là một mốc xét Ngày Đủ Điều Kiện.">
                    Ngày Chờ KCS Theo Đúng Thứ Tự Lô</th>
                <th class="bg-warning" style="min-width: 140px;">Ngày KCS</th>
                <th class="bg-warning" style="min-width: 150px;">Ghi Chú</th>

                <th class="bg-info text-white" style="min-width: 110px;">Ngày Đủ Điều Kiện</th>
                <th class="bg-info text-white" style="min-width: 90px;">Số Ngày HT</th>
                <th class="bg-info text-white" style="min-width: 90px;">KCS Pending</th>
                <th class="bg-info text-white" style="min-width: 80px;">Tháng KCS</th>
                <th class="bg-info text-white" style="min-width: 140px;">Mốc Quyết Định</th>
                <th class="bg-info text-white" style="min-width: 130px;">Kết Quả</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($datas as $index => $data)
                @php
                    $record = $records->get($data->id);
                    $bom = $bomVersions->get($data->id);
                    $prefill = \App\Http\Controllers\Pages\Plan\KcsTrackingController::PREFILL_FROM_PLAN_MASTER;
                    $mms = $mmsSuggestions->get($data->id);
                    $codeMismatch = $mmsCodeMismatch->get($data->id);

                    // Ngày KCS và Số phiếu COATP do MMS quyết định, không nhập tay. Ô hiện
                    // giá trị của MMS; lô chưa có trên MMS thì hiện giá trị cũ đã lưu (nếu có).
                    //
                    // Hai ô này cố ý KHÔNG mang class kcs-input để JS không gom vào payload:
                    // server tự lấy lại từ MMS khi lưu, không nhận giá trị từ trình duyệt.
                    $mmsValue = function ($field) use ($mms, $record) {
                        return $mms[$field] ?? ($record?->getRawOriginal($field) ?? '');
                    };

                    // Ngày KCS đang hiệu lực, dùng chung cho ô Ngày KCS và cột Tháng KCS để
                    // hai chỗ không bao giờ vênh nhau (bộ lọc Tháng KCS cũng chạy trên nó).
                    $kcsDate = $kcsDates[$data->id] ?? null;

                    // Các ô người dùng nhập: đã có dòng theo dõi thì lấy đúng dữ liệu đã lưu,
                    // chưa có thì gợi ý mốc sẵn có trên kế hoạch để khỏi gõ lại rồi tự bấm lưu.
                    $value = function ($field) use ($record, $data, $prefill) {
                        if ($record) {
                            return $record->getRawOriginal($field) ?? '';
                        }

                        return isset($prefill[$field]) ? $data->{$prefill[$field]} ?? '' : '';
                    };
                @endphp
                <tr data-plan-master-id="{{ $data->id }}" @class(['table-light' => !$record])>
                    <td class="text-center align-middle kcs-sticky kcs-sticky-1">
                        {{ $index + 1 }}
                        @unless ($record)
                            <i class="fas fa-circle text-muted kcs-unsaved" title="Chưa lưu theo dõi KCS"></i>
                        @endunless
                    </td>
                    <td class="text-center align-middle kcs-sticky kcs-sticky-2">
                        {{ $data->order_number_R1 ?: '' }}
                        @if ($data->order_number_R2)
                            <div class="text-muted small">{{ $data->order_number_R2 }}</div>
                        @endif
                    </td>
                    <td class="text-center align-middle font-weight-bold kcs-sticky kcs-sticky-3">
                        {{ $data->actual_batch ?: $data->batch }}
                    </td>
                    <td class="text-center align-middle kcs-sticky kcs-sticky-4">
                        <button type="button" class="btn btn-xs btn-outline-secondary btn-kcs-history"
                            title="Xem lịch sử chỉnh sửa">
                            <i class="fas fa-history"></i>
                        </button>
                    </td>
                    <td class="align-middle kcs-sticky kcs-sticky-5">{{ $data->product_name }}</td>
                    <td class="text-center align-middle">
                        {{ $data->intermediate_code }}
                        @if ($bom?->btp_version)
                            <span class="badge badge-secondary" title="Version BOM lúc có số lệnh">
                                v{{ $bom->btp_version }}
                            </span>
                        @endif
                    </td>
                    <td class="text-center align-middle">
                        {{ $data->finished_product_code }}
                        @if ($bom?->tp_version)
                            <span class="badge badge-secondary" title="Version BOM lúc có số lệnh">
                                v{{ $bom->tp_version }}
                            </span>
                        @endif
                        @if ($codeMismatch)
                            <i class="fas fa-exclamation-triangle text-warning"
                                title="Lệch mã thành phẩm giữa PMS và MMS. Cùng số lệnh {{ $data->order_number_R1 ?: $data->order_number_R2 }} và số lô {{ $data->actual_batch ?: $data->batch }}, nhưng MMS ghi mã {{ $codeMismatch }}. Vì vậy Ngày KCS và Số Phiếu COATP không tự điền được - vui lòng nhập tay và kiểm tra lại mã TP."></i>
                            <span class="badge badge-warning">MMS: {{ $codeMismatch }}</span>
                        @endif
                    </td>
                    <td class="align-middle">{{ $data->market }}</td>
                    <td class="align-middle">{{ $data->specification }}</td>
                    <td class="text-right align-middle">
                        {{ number_format((float) $data->batch_qty, 0, ',', '.') }} {{ $data->unit_batch_qty }}
                    </td>
                    <td class="text-center align-middle">
                        @if ($data->is_val)
                            <span class="badge badge-info">TĐ</span>
                        @endif
                    </td>

                    <td><input type="date" class="form-control form-control-sm kcs-input"
                            name="record_received_date" value="{{ $value('record_received_date') }}"
                            {{ $canUpdate ? '' : 'disabled' }}></td>
                    <td><input type="text" class="form-control form-control-sm kcs-input" name="reader"
                            list="kcs_reader_options" value="{{ $value('reader') }}"
                            {{ $canUpdate ? '' : 'disabled' }}>
                    </td>
                    <td><input type="date" class="form-control form-control-sm kcs-input" name="record_done_date"
                            value="{{ $value('record_done_date') }}" {{ $canUpdate ? '' : 'disabled' }}></td>
                    <td><input type="text" class="form-control form-control-sm kcs-locked" name="coatp_number"
                            value="{{ $mmsValue('coatp_number') }}" disabled
                            title="Số phiếu COATP lấy từ MMS (fgqc.coano), không nhập tay."></td>
                    <td><input type="date" class="form-control form-control-sm kcs-input"
                            name="coatp_received_date" value="{{ $value('coatp_received_date') }}"
                            {{ $canUpdate ? '' : 'disabled' }}></td>
                    <td><input type="text" class="form-control form-control-sm kcs-input" name="dr_ir"
                            value="{{ $value('dr_ir') }}" {{ $canUpdate ? '' : 'disabled' }}></td>
                    <td><input type="date" class="form-control form-control-sm kcs-input"
                            name="dr_ir_approval_date" value="{{ $value('dr_ir_approval_date') }}"
                            {{ $canUpdate ? '' : 'disabled' }}></td>
                    <td><input type="text" class="form-control form-control-sm kcs-input" name="oos"
                            value="{{ $value('oos') }}" {{ $canUpdate ? '' : 'disabled' }}></td>
                    <td><input type="date" class="form-control form-control-sm kcs-input" name="oos_approval_date"
                            value="{{ $value('oos_approval_date') }}" {{ $canUpdate ? '' : 'disabled' }}></td>
                    <td><input type="date" class="form-control form-control-sm kcs-input"
                            name="dr_ir_kcq_approval_date" value="{{ $value('dr_ir_kcq_approval_date') }}"
                            {{ $canUpdate ? '' : 'disabled' }}></td>
                    <td><input type="date" class="form-control form-control-sm kcs-input"
                            name="opv_pvr_approval_date" value="{{ $value('opv_pvr_approval_date') }}"
                            {{ $canUpdate ? '' : 'disabled' }}></td>
                    <td><input type="date" class="form-control form-control-sm kcs-input" name="kcs_queue_date"
                            value="{{ $value('kcs_queue_date') }}" {{ $canUpdate ? '' : 'disabled' }}></td>
                    <td><input type="date" class="form-control form-control-sm kcs-locked" name="kcs_date"
                            value="{{ $kcsDate }}" disabled
                            title="Ngày KCS lấy từ MMS (bước QA Approval), không nhập tay."></td>
                    <td><input type="text" class="form-control form-control-sm kcs-input" name="note"
                            value="{{ $value('note') }}" {{ $canUpdate ? '' : 'disabled' }}></td>

                    <td class="text-center align-middle" data-cell="eligible_date">
                        {{ optional($record?->eligible_date)->format('d/m/Y') }}
                    </td>
                    <td class="text-center align-middle" data-cell="completion_days">
                        {{ $record?->completion_days }}
                    </td>
                    <td class="text-center align-middle" data-cell="kcs_pending">{{ $record?->kcs_pending }}</td>
                    <td class="text-center align-middle" data-cell="kcs_month">
                        {{ $kcsDate ? (int) substr($kcsDate, 5, 2) : '' }}
                    </td>
                    <td class="text-center align-middle" data-cell="bottleneck">{{ $record?->bottleneck }}</td>
                    <td class="text-center align-middle" data-cell="result">
                        @if ($record?->result)
                            <span
                                class="badge {{ $record->result === \App\Models\PlanMasterKcs::RESULT_MET ? 'badge-success' : 'badge-danger' }}">
                                {{ $record->result }}
                            </span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="31" class="text-center text-muted py-4">
                        Không có lô sản xuất nào trong khoảng đã chọn
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="modal fade" id="kcsHistoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 90%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-history text-primary"></i> Lịch Sử Chỉnh Sửa - Lô
                    <span id="kcsHistoryBatch"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="kcsHistoryBody"></div>
        </div>
    </div>
</div>

<style>
    /* Chiều cao thật do fitTableHeight() tính theo vị trí bảng trên màn hình;
       giá trị dưới đây chỉ là dự phòng khi JS chưa chạy. */
    #kcs_table_scroll {
        max-height: calc(100vh - 330px);
        overflow: auto;
    }

    #kcs_tracking_table thead th {
        position: sticky;
        top: 0;
        z-index: 3;
        font-size: 12px;
        white-space: normal;
    }

    #kcs_tracking_table td {
        font-size: 12px;
        white-space: nowrap;
    }

    #kcs_tracking_table .form-control-sm {
        font-size: 12px;
        padding: 2px 4px;
        height: auto;
    }

    /* Giữ 3 cột định danh luôn nhìn thấy khi cuộn ngang bảng rất rộng */
    #kcs_tracking_table .kcs-sticky {
        position: sticky;
        background-color: #fff;
        z-index: 2;
    }

    #kcs_tracking_table thead .kcs-sticky {
        z-index: 4;
        background-color: #f8f9fa;
    }

    #kcs_tracking_table .kcs-sticky-1 {
        left: 0;
    }

    #kcs_tracking_table .kcs-sticky-2 {
        left: 50px;
    }

    #kcs_tracking_table .kcs-sticky-3 {
        left: 150px;
    }

    #kcs_tracking_table .kcs-sticky-4 {
        left: 240px;
    }

    /* Cột cuối của nhóm ghim: đổ bóng để thấy rõ ranh giới khi cuộn ngang */
    #kcs_tracking_table .kcs-sticky-5 {
        left: 280px;
        box-shadow: 2px 0 3px -1px rgba(0, 0, 0, .2);
        white-space: normal;
    }

    #kcs_tracking_table tr:hover .kcs-sticky {
        background-color: #f2f2f2;
    }

    #kcs_tracking_table .kcs-saving {
        background-color: #fff3cd;
    }

    /* Ô khoá vì lấy từ MMS: tô khác hẳn ô bị khoá do thiếu quyền (xám mặc định của
       Bootstrap) để người dùng thấy ngay đây là dữ liệu hệ thống gốc, không phải bị chặn. */
    #kcs_tracking_table .kcs-locked:disabled {
        background-color: #d4edda;
        color: #155724;
        font-weight: 600;
        cursor: not-allowed;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const RESULT_MET = @json(\App\Models\PlanMasterKcs::RESULT_MET);
        const saveUrl = '{{ route('pages.plan.kcs_tracking.save') }}';

        // Cho bảng cao hết phần màn hình còn lại thay vì trừ một số cố định:
        // thanh lọc có thể xuống dòng, có thể hiện thêm cảnh báo "chỉ có quyền xem"...
        // nên vị trí bắt đầu của bảng không cố định.
        function fitTableHeight() {
            const box = document.getElementById('kcs_table_scroll');

            // Tab đang ẩn thì không đo được, để lần hiện tab tính lại
            if (!box || box.offsetParent === null) {
                return;
            }

            const footer = document.querySelector('.main-footer');
            const footerHeight = footer && footer.offsetParent !== null ?
                footer.getBoundingClientRect().height :
                0;

            // Chừa chỗ cho padding dưới của card và footer
            const available = window.innerHeight - box.getBoundingClientRect().top - footerHeight - 24;

            box.style.maxHeight = Math.max(available, 240) + 'px';
        }

        fitTableHeight();
        window.addEventListener('resize', fitTableHeight);
        $('#tab-records-tab').on('shown.bs.tab', fitTableHeight);
        // Thu/mở menu trái làm bảng đổi bề ngang -> thanh lọc có thể xuống dòng
        $(document).on('collapsed.lte.pushmenu shown.lte.pushmenu', fitTableHeight);

        // Lưu cả dòng mỗi khi một ô đổi giá trị: các cột dẫn xuất phụ thuộc lẫn nhau
        // nên phải để server tính lại trên toàn bộ dữ liệu của dòng.
        $('#kcs_tracking_table').on('change', '.kcs-input', function() {
            const $row = $(this).closest('tr');
            const payload = {
                _token: '{{ csrf_token() }}',
                plan_master_id: $row.data('plan-master-id')
            };

            $row.find('.kcs-input').each(function() {
                payload[this.name] = this.value;
            });

            $row.find('.kcs-input').addClass('kcs-saving');

            $.ajax({
                url: saveUrl,
                method: 'POST',
                data: payload,
                success: function(res) {
                    $row.find('.kcs-input').removeClass('kcs-saving');

                    if (!res.success) {
                        toastr.error(res.message || 'Không lưu được');
                        return;
                    }

                    const d = res.derived;
                    $row.find('[data-cell="eligible_date"]').text(d.eligible_date || '');
                    $row.find('[data-cell="completion_days"]').text(
                        d.completion_days === null ? '' : d.completion_days);
                    $row.find('[data-cell="bottleneck"]').text(d.bottleneck || '');
                    $row.find('[data-cell="kcs_pending"]').text(
                        d.kcs_pending === null ? '' : d.kcs_pending);
                    $row.find('[data-cell="kcs_month"]').text(d.kcs_month || '');

                    $row.find('[data-cell="result"]').html(
                        d.result ?
                        '<span class="badge ' + (d.result === RESULT_MET ?
                            'badge-success' :
                            'badge-danger') + '">' + d.result + '</span>' :
                        ''
                    );

                    $row.trigger('kcs:saved');
                    toastr.success('Đã lưu lô ' + $row.find('.kcs-sticky-3').text().trim());
                },
                error: function(xhr) {
                    $row.find('.kcs-input').removeClass('kcs-saving');
                    const message = xhr.responseJSON && xhr.responseJSON.message ?
                        xhr.responseJSON.message :
                        'Lỗi khi lưu dữ liệu';
                    toastr.error(message);
                }
            });
        });

        // Sau khi lưu, dòng đã có bản ghi nên bỏ dấu "chưa lưu"
        $('#kcs_tracking_table').on('kcs:saved', 'tr', function() {
            $(this).removeClass('table-light').find('.kcs-unsaved').remove();
        });

        $('#kcs_tracking_table').on('click', '.btn-kcs-history', function() {
            const $row = $(this).closest('tr');

            $('#kcsHistoryBatch').text($row.find('.kcs-sticky-3').text().trim());
            $('#kcsHistoryBody').html('<div class="text-center p-4">Đang tải dữ liệu...</div>');
            $('#kcsHistoryModal').modal('show');

            $.ajax({
                url: '{{ route('pages.plan.kcs_tracking.history') }}',
                method: 'GET',
                data: {
                    plan_master_id: $row.data('plan-master-id')
                },
                success: function(res) {
                    $('#kcsHistoryBody').html(res.html);
                },
                error: function() {
                    $('#kcsHistoryBody').html(
                        '<div class="text-center text-danger p-3">Lỗi khi tải lịch sử</div>'
                    );
                }
            });
        });
    });
</script>

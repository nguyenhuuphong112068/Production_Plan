@php
    // Gợi ý người đọc hồ sơ từ những tên đã được nhập trước đó
    $readerOptions = $records->pluck('reader')->filter()->unique()->sort()->values();

    // Lọc theo Tháng KCS hoặc Ngày Dự Kiến KCS thì khoảng tháng kế hoạch không áp dụng
    // (xem KcsTrackingController::queryFilters())
    $hasExpected = $expectedFrom !== '' || $expectedTo !== '';
    $planIgnored = $kcsMonth !== '' || $hasExpected;
    $planIgnoredBy = $kcsMonth !== '' ? 'Tháng KCS' : 'Ngày Dự Kiến KCS';
    $fmtDay = fn($day) => $day !== '' ? \Carbon\Carbon::parse($day)->format('d/m/Y') : '';

    // Các bộ lọc đang áp dụng, hiện thành thẻ có nút x để bỏ riêng từng bộ lọc
    // (null trong fullUrlWithQuery() = bỏ tham số đó khỏi URL)
    $activeFilters = [];

    if ($kcsMonth !== '') {
        $activeFilters[] = [
            'label' => 'Tháng KCS: ' . \Carbon\Carbon::parse($kcsMonth . '-01')->format('m/Y'),
            'url' => request()->fullUrlWithQuery(['kcs_month' => null]),
        ];
    }

    if ($hasExpected) {
        $activeFilters[] = [
            'label' => 'Dự kiến KCS: ' . ($fmtDay($expectedFrom) ?: '...') . ' – ' . ($fmtDay($expectedTo) ?: '...'),
            'url' => request()->fullUrlWithQuery(['expected_from' => null, 'expected_to' => null]),
        ];
    }

    if ($result !== '') {
        $activeFilters[] = [
            'label' => 'Kết quả: ' . ($result === \App\Models\PlanMasterKcs::RESULT_MET ? 'Đáp Ứng' : 'Trễ Hạn'),
            'url' => request()->fullUrlWithQuery(['result' => null]),
        ];
    }

    if ($keyword !== '') {
        $activeFilters[] = [
            'label' => 'Tìm: "' . $keyword . '"',
            'url' => request()->fullUrlWithQuery(['keyword' => null]),
        ];
    }
@endphp

<form method="GET" action="{{ route('pages.plan.kcs_tracking.list') }}" class="kcs-filter">
    <input type="hidden" name="summary_year" value="{{ $summaryYear }}">

    <div class="form-row">
        <div class="form-group col-sm-6 col-lg-4 col-xl-2">
            <label class="kcs-filter-label"><i class="fas fa-industry text-primary"></i> Phân xưởng</label>
            <select class="form-control form-control-sm" name="department">
                <option value="">Tất cả phân xưởng</option>
                @foreach ($departments as $code => $name)
                    <option value="{{ $code }}" {{ $code === $department ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group col-sm-6 col-lg-4 col-xl-3">
            <label class="kcs-filter-label {{ $planIgnored ? 'text-muted' : '' }}">
                <i class="far fa-calendar text-primary"></i> Tháng kế hoạch
                @if ($planIgnored)
                    <span class="badge badge-light border font-weight-normal"
                        title="Đang lọc theo {{ $planIgnoredBy }} nên khoảng tháng kế hoạch không áp dụng: lô kế hoạch tháng khác vẫn hiện nếu thuộc {{ $planIgnoredBy }} đang chọn.">
                        <i class="fas fa-ban"></i> không áp dụng
                    </span>
                @endif
            </label>
            <div class="input-group input-group-sm {{ $planIgnored ? 'kcs-filter-muted' : '' }}">
                <input type="month" class="form-control" name="from_month" value="{{ $fromMonth }}"
                    title="Từ tháng kế hoạch">
                <div class="input-group-append">
                    <span class="input-group-text">đến</span>
                </div>
                <input type="month" class="form-control" name="to_month" value="{{ $toMonth }}"
                    title="Đến tháng kế hoạch">
            </div>
        </div>

        <div class="form-group col-sm-6 col-lg-4 col-xl-2">
            <label class="kcs-filter-label"
                title="Lọc theo tháng của Ngày KCS thật (từ MMS), không phải tháng kế hoạch.">
                <i class="fas fa-clipboard-check text-success"></i> Tháng KCS
            </label>
            <input type="month" class="form-control form-control-sm" name="kcs_month" value="{{ $kcsMonth }}">
        </div>

        <div class="form-group col-sm-6 col-lg-6 col-xl-3">
            <label class="kcs-filter-label"
                title="Lọc theo Ngày dự kiến KCS trên Kế Hoạch Sản Xuất. Bỏ trống một đầu để lọc mở (từ ngày... / đến ngày...).">
                <i class="fas fa-calendar-alt text-info"></i> Ngày dự kiến KCS
            </label>
            <div class="input-group input-group-sm">
                <input type="text" class="form-control kcs-date" name="expected_from" placeholder="dd/mm/yyyy"
                    autocomplete="off" title="Từ ngày" value="{{ $fmtDay($expectedFrom) }}">
                <div class="input-group-append">
                    <span class="input-group-text">đến</span>
                </div>
                <input type="text" class="form-control kcs-date" name="expected_to" placeholder="dd/mm/yyyy"
                    autocomplete="off" title="Đến ngày" value="{{ $fmtDay($expectedTo) }}">
            </div>
        </div>

        <div class="form-group col-sm-6 col-lg-6 col-xl-2">
            <label class="kcs-filter-label" title="Chỉ lô đã có dòng theo dõi và đã chấm được kết quả mới hiện ra.">
                <i class="fas fa-check-circle text-success"></i> Kết quả
            </label>
            <select class="form-control form-control-sm" name="result">
                <option value="">Tất cả</option>
                <option value="{{ \App\Models\PlanMasterKcs::RESULT_MET }}"
                    {{ $result === \App\Models\PlanMasterKcs::RESULT_MET ? 'selected' : '' }}>Đáp Ứng</option>
                <option value="{{ \App\Models\PlanMasterKcs::RESULT_NOT_MET }}"
                    {{ $result === \App\Models\PlanMasterKcs::RESULT_NOT_MET ? 'selected' : '' }}>Trễ Hạn</option>
            </select>
        </div>
    </div>

    <div class="form-row align-items-center">
        <div class="form-group col-md-6 col-xl-5">
            <div class="input-group input-group-sm">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" class="form-control" name="keyword" value="{{ $keyword }}"
                    placeholder="Tìm theo mã TP / mã BTP / tên sản phẩm / số lô...">
            </div>
        </div>
        <div class="form-group col-md-6 col-xl-7 d-flex justify-content-md-end kcs-filter-actions">
            <button type="submit" class="btn btn-sm btn-primary px-3">
                <i class="fas fa-filter"></i> Lọc
            </button>
            <a href="{{ route('pages.plan.kcs_tracking.list') }}" class="btn btn-sm btn-outline-secondary"
                title="Bỏ mọi bộ lọc, về tháng kế hoạch hiện tại">
                <i class="fas fa-undo"></i> Đặt lại
            </a>
            <a href="{{ route('pages.plan.kcs_tracking.export', [
                'department' => $department,
                'from_month' => $fromMonth,
                'to_month' => $toMonth,
                'kcs_month' => $kcsMonth,
                'result' => $result,
                'expected_from' => $expectedFrom,
                'expected_to' => $expectedTo,
                'keyword' => $keyword,
            ]) }}" class="btn btn-sm btn-outline-success" title="Xuất Excel đúng dữ liệu đang lọc trên lưới">
                <i class="fas fa-file-excel"></i> Xuất Excel
            </a>
        </div>
    </div>
</form>

<div class="kcs-filter-status">
    <div class="d-flex flex-wrap align-items-center">
        <span class="kcs-result-count"><b>{{ number_format($datas->count(), 0, ',', '.') }}</b> lô</span>
        @foreach ($activeFilters as $filter)
            <span class="kcs-chip">
                {{ $filter['label'] }}
                <a href="{{ $filter['url'] }}" title="Bỏ bộ lọc này">&times;</a>
            </span>
        @endforeach
    </div>
    <span class="text-muted">
        <i class="fas fa-sort"></i> Bấm tiêu đề cột để sắp xếp · <kbd>Shift</kbd> + bấm để sắp thêm cột
    </span>
</div>
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
                <th class="bg-light" style="min-width: 110px;">Lô Thẩm Định / Thu Hồi</th>
                <th class="bg-light" style="min-width: 100px;"
                    title="Ngày dự kiến KCS trên Kế Hoạch Sản Xuất (plan_master.expected_date), chỉ xem.">
                    Ngày Dự Kiến KCS</th>

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
                <th class="bg-warning" style="min-width: 140px;">Ngày AP OPV/PVR</th>
                <th class="bg-warning" style="min-width: 150px;"
                    title="Ngày lô được đưa vào hàng chờ KCS đúng thứ tự lô. Cũng là một mốc xét Ngày Đủ Điều Kiện.">
                    Ngày Chờ KCS Theo Đúng Thứ Tự Lô</th>
                <th class="bg-warning" style="min-width: 110px;"
                    title="Ngày nhập kho thành phẩm lấy từ MMS (phiếu nhập kho FGGRN, ngày kho tạo phiếu). Lô nhập kho nhiều đợt thì lấy phiếu đầu tiên; lô chia thì lấy phiếu có số lượng khớp phần lô đó.">
                    Ngày Nhập Kho</th>
                <th class="bg-warning" style="min-width: 140px;">Ngày KCS</th>
                <th class="bg-warning" style="min-width: 150px;">Ghi Chú</th>

                <th class="bg-info text-white" style="min-width: 110px;">Ngày Đủ Điều Kiện</th>
                <th class="bg-info text-white" style="min-width: 90px;"
                    title="Số ngày làm việc từ Ngày Đủ Điều Kiện đến Ngày KCS. Đã loại Chủ nhật và các ngày nghỉ theo lịch công ty. Đáp Ứng khi &le; {{ \App\Models\PlanMasterKcs::ON_TIME_DAYS }} ngày.">
                    Số Ngày HT</th>
                <th class="bg-info text-white" style="min-width: 90px;"
                    title="Số ngày làm việc từ Ngày Nhận Hồ Sơ đến Ngày KCS. Đã loại Chủ nhật và các ngày nghỉ theo lịch công ty.">
                    KCS Pending</th>
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
                    // Phiếu nhập kho TP trên MMS: ['date', 'grn', 'qty', 'count'] hoặc null
                    $stockIn = $mmsStockIn->get($data->id);

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

                    // Ô ngày hiện dd/mm/yyyy cố định. Không dùng input type="date" vì trình duyệt
                    // hiện theo Region của Windows (13-Oct-2026, 10/13/2026...), JS không đổi được.
                    // Khi lưu, JS đổi ngược về Y-m-d (xem toIsoDate()).
                    $dmy = function ($date) {
                        if (!$date) {
                            return '';
                        }

                        try {
                            return \Carbon\Carbon::parse($date)->format('d/m/Y');
                        } catch (\Throwable) {
                            return (string) $date;
                        }
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
                    <td class="text-right align-middle" data-sort="{{ (float) $data->batch_qty }}">
                        {{ number_format((float) $data->batch_qty, 0, ',', '.') }} {{ $data->unit_batch_qty }}
                    </td>
                    <td class="text-center align-middle">
                        <x-lot-flags :val="$data->is_val" :ret="$data->excess_pro_return_lot" />
                    </td>
                    <td class="text-center align-middle">
                        {{ $data->expected_date ? \Carbon\Carbon::parse($data->expected_date)->format('d/m/Y') : '' }}
                    </td>

                    <td><input type="text" class="form-control form-control-sm kcs-input kcs-date" placeholder="dd/mm/yyyy"
                            autocomplete="off" name="record_received_date" value="{{ $dmy($value('record_received_date')) }}"
                            {{ $canUpdate ? '' : 'disabled' }}></td>
                    <td><input type="text" class="form-control form-control-sm kcs-input" name="reader"
                            list="kcs_reader_options" value="{{ $value('reader') }}"
                            {{ $canUpdate ? '' : 'disabled' }}>
                    </td>
                    <td><input type="text" class="form-control form-control-sm kcs-input kcs-date" placeholder="dd/mm/yyyy" autocomplete="off" name="record_done_date"
                            value="{{ $dmy($value('record_done_date')) }}" {{ $canUpdate ? '' : 'disabled' }}></td>
                    <td><input type="text" class="form-control form-control-sm kcs-locked" name="coatp_number"
                            value="{{ $mmsValue('coatp_number') }}" disabled
                            title="Số phiếu COATP lấy từ MMS (fgqc.coano), không nhập tay."></td>
                    <td><input type="text" class="form-control form-control-sm kcs-input kcs-date" placeholder="dd/mm/yyyy"
                            autocomplete="off" name="coatp_received_date" value="{{ $dmy($value('coatp_received_date')) }}"
                            {{ $canUpdate ? '' : 'disabled' }}></td>
                    <td><input type="text" class="form-control form-control-sm kcs-input" name="dr_ir"
                            value="{{ $value('dr_ir') }}" {{ $canUpdate ? '' : 'disabled' }}></td>
                    <td><input type="text" class="form-control form-control-sm kcs-input kcs-date" placeholder="dd/mm/yyyy"
                            autocomplete="off" name="dr_ir_approval_date" value="{{ $dmy($value('dr_ir_approval_date')) }}"
                            {{ $canUpdate ? '' : 'disabled' }}></td>
                    <td><input type="text" class="form-control form-control-sm kcs-input" name="oos"
                            value="{{ $value('oos') }}" {{ $canUpdate ? '' : 'disabled' }}></td>
                    <td><input type="text" class="form-control form-control-sm kcs-input kcs-date" placeholder="dd/mm/yyyy" autocomplete="off" name="oos_approval_date"
                            value="{{ $dmy($value('oos_approval_date')) }}" {{ $canUpdate ? '' : 'disabled' }}></td>
                    <td><input type="text" class="form-control form-control-sm kcs-input kcs-date" placeholder="dd/mm/yyyy"
                            autocomplete="off" name="opv_pvr_approval_date" value="{{ $dmy($value('opv_pvr_approval_date')) }}"
                            {{ $canUpdate ? '' : 'disabled' }}></td>
                    <td><input type="text" class="form-control form-control-sm kcs-input kcs-date" placeholder="dd/mm/yyyy" autocomplete="off" name="kcs_queue_date"
                            value="{{ $dmy($value('kcs_queue_date')) }}" {{ $canUpdate ? '' : 'disabled' }}></td>
                    <td><input type="text" class="form-control form-control-sm kcs-locked" name="stock_in_date"
                            value="{{ $dmy($stockIn['date'] ?? null) }}" disabled
                            title="{{ $stockIn
                                ? 'Ngày nhập kho lấy từ MMS. Phiếu ' . $stockIn['grn'] . ', SL ' . number_format($stockIn['qty'], 0, ',', '.')
                                    . ($stockIn['count'] > 1 ? ' (lô có ' . $stockIn['count'] . ' phiếu nhập kho)' : '')
                                : 'Chưa có phiếu nhập kho trên MMS' }}"></td>
                    <td><input type="text" class="form-control form-control-sm kcs-locked" name="kcs_date"
                            value="{{ $dmy($kcsDate) }}" disabled
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
                    <td colspan="32" class="text-center text-muted py-4">
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
    /* ---- Thanh lọc ---- */
    .kcs-filter {
        background-color: #f8f9fa;
        border: 1px solid #e3e6ea;
        border-radius: 6px;
        padding: 10px 12px 2px;
        margin-bottom: 8px;
    }

    .kcs-filter .form-group {
        margin-bottom: 8px;
    }

    .kcs-filter-label {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        font-weight: 600;
        color: #495057;
        margin-bottom: 3px;
        white-space: nowrap;
    }

    .kcs-filter-label .badge {
        font-size: 10px;
    }

    .kcs-filter .input-group-text {
        font-size: 11px;
        color: #6c757d;
        background-color: #e9ecef;
    }

    .kcs-filter-muted {
        opacity: .5;
    }

    .kcs-filter-actions {
        gap: 6px;
    }

    .kcs-filter-status {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        margin-bottom: 8px;
    }

    .kcs-result-count {
        margin-right: 8px;
        color: #343a40;
    }

    .kcs-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 1px 4px 1px 10px;
        margin: 2px 6px 2px 0;
        border: 1px solid #b8daff;
        border-radius: 12px;
        background-color: #e7f1ff;
        color: #004085;
    }

    .kcs-chip a {
        display: inline-block;
        width: 16px;
        line-height: 16px;
        text-align: center;
        border-radius: 50%;
        color: #004085;
        font-weight: bold;
        text-decoration: none;
    }

    .kcs-chip a:hover {
        background-color: #004085;
        color: #fff;
    }

    /* Layout chung đặt body { overflow-y: hidden } (resources/views/layout/css.blade.php)
       nên cửa sổ không bao giờ cuộn được: mỗi tab phải có hộp cuộn dọc của riêng nó.

       Chiều cao thật do fitTableHeight() tính theo vị trí hộp trên màn hình;
       các giá trị calc() dưới đây chỉ là dự phòng khi JS chưa chạy. */
    #kcs_table_scroll {
        max-height: calc(100vh - 330px);
        overflow: auto;
    }

    /* Tab Tổng Kết Tỉ Lệ - phần tử nằm trong summary.blade.php, CSS để chung ở đây vì
       khối đó được nạp lại bằng AJAX (xem ghi chú cuối summary.blade.php). */
    #kcs_summary_scroll {
        max-height: calc(100vh - 300px);
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* Ghim tiêu đề cột khi cuộn. Nền phải đặt trên chính th: bg-light nằm ở thead nên
       ô sticky sẽ trong suốt và để lộ các dòng chạy bên dưới. */
    #kcs_summary_scroll thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background-color: #f8f9fa;
    }

    /* Dòng tổng luôn nhìn thấy: đây mới là con số cần đọc khi cuộn qua 12 tháng */
    #kcs_summary_scroll tfoot td {
        position: sticky;
        bottom: 0;
        z-index: 2;
        background-color: #f8f9fa;
    }

    #kcs_tracking_table thead th {
        position: sticky;
        top: 0;
        z-index: 3;
        font-size: 12px;
        white-space: normal;
    }

    #kcs_tracking_table th.kcs-sortable {
        cursor: pointer;
        user-select: none;
    }

    #kcs_tracking_table .kcs-sort-icon {
        opacity: .35;
        font-size: 10px;
    }

    #kcs_tracking_table .kcs-sort-icon.kcs-sort-active {
        opacity: 1;
    }

    #kcs_tracking_table .kcs-sort-order {
        font-size: 9px;
        font-weight: bold;
        margin-left: 1px;
    }

    /* Ô ngày dd/mm/yyyy là input text: cố định bề ngang cho vừa 10 ký tự */
    #kcs_tracking_table input.kcs-date,
    #kcs_tracking_table input.kcs-locked[name="kcs_date"],
    #kcs_tracking_table input.kcs-locked[name="stock_in_date"] {
        min-width: 95px;
        text-align: center;
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

    /* Lô sửa nhiều lần có hàng trăm dòng lịch sử; cho phần thân modal tự cuộn để
       tiêu đề modal và tiêu đề cột luôn nằm trong tầm nhìn. */
    #kcsHistoryBody {
        max-height: calc(100vh - 200px);
        overflow-y: auto;
        padding: 0;
    }

    #kcsHistoryBody thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background-color: #f8f9fa;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const RESULT_MET = @json(\App\Models\PlanMasterKcs::RESULT_MET);
        const saveUrl = '{{ route('pages.plan.kcs_tracking.save') }}';

        // Cho hộp cao hết phần màn hình còn lại thay vì trừ một số cố định:
        // thanh lọc có thể xuống dòng, có thể hiện thêm cảnh báo "chỉ có quyền xem"...
        // nên vị trí bắt đầu của hộp không cố định.
        function fitBoxHeight(id) {
            const box = document.getElementById(id);

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

        // Cả hai tab đều phải tự cuộn: layout chung đặt body { overflow-y: hidden }
        // (resources/views/layout/css.blade.php) nên cửa sổ không bao giờ cuộn được.
        function fitTableHeight() {
            fitBoxHeight('kcs_table_scroll');
            fitBoxHeight('kcs_summary_scroll');
        }

        fitTableHeight();
        window.addEventListener('resize', fitTableHeight);
        $('#tab-records-tab, #tab-summary-tab').on('shown.bs.tab', fitTableHeight);
        // Thu/mở menu trái làm bảng đổi bề ngang -> thanh lọc có thể xuống dòng
        $(document).on('collapsed.lte.pushmenu shown.lte.pushmenu', fitTableHeight);
        // Bảng tổng kết được tải lại bằng AJAX -> hộp cuộn mới, phải đo lại
        $(document).on('kcs:summary-loaded', fitTableHeight);

        // ---- Ô ngày dd/mm/yyyy ----
        // Ô ngày là input text (không dùng type="date" vì trình duyệt hiện theo Region của
        // Windows). Gõ tay dd/mm/yyyy (chấp nhận 5/11/2026) hoặc chọn trên lịch.

        // 'dd/mm/yyyy' -> 'yyyy-mm-dd'; ô trống -> ''; sai định dạng / ngày không có -> null
        function toIsoDate(text) {
            text = (text || '').trim();

            if (text === '') {
                return '';
            }

            const m = text.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);

            if (!m) {
                return null;
            }

            const day = Number(m[1]);
            const month = Number(m[2]);
            const year = Number(m[3]);
            const date = new Date(year, month - 1, day);

            // new Date tự "tràn" ngày (31/02 -> 03/03), so lại để bắt ngày không tồn tại
            if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
                return null;
            }

            return year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
        }

        // 'yyyy-mm-dd' -> 'dd/mm/yyyy' để hiện lại đúng 2 chữ số (gõ 5/1/2026 -> 05/01/2026)
        function toDisplayDate(iso) {
            return iso ? iso.substring(8, 10) + '/' + iso.substring(5, 7) + '/' + iso.substring(0, 4) : '';
        }

        // Gắn lịch chọn ngày khi ô được focus lần đầu: lưới có hàng trăm lô x 7 ô ngày,
        // khởi tạo hết lúc tải trang sẽ rất chậm.
        $(document).on('focus', 'input.kcs-date', function() {
            const $input = $(this);

            if (this.disabled || $input.data('daterangepicker') || typeof $.fn.daterangepicker !== 'function') {
                return;
            }

            $input.daterangepicker({
                singleDatePicker: true,
                autoUpdateInput: false, // để ô được phép trống
                autoApply: true,
                showDropdowns: true,
                drops: 'auto',
                locale: {
                    format: 'DD/MM/YYYY',
                    daysOfWeek: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
                    monthNames: ['Th1', 'Th2', 'Th3', 'Th4', 'Th5', 'Th6', 'Th7', 'Th8', 'Th9', 'Th10',
                        'Th11', 'Th12'
                    ],
                    firstDay: 1
                }
            });

            $input.on('apply.daterangepicker', function(e, picker) {
                const value = picker.startDate.format('DD/MM/YYYY');

                if (this.value !== value) {
                    this.value = value;
                    $(this).trigger('change');
                }
            });

            $input.data('daterangepicker').show();
        });

        // Ô ngày ở thanh lọc: chặn gửi ngày sai để bộ lọc không bị bỏ qua trong im lặng
        $('input.kcs-date[name="expected_from"]').closest('form').on('submit', function(e) {
            let ok = true;

            $(this).find('input.kcs-date').each(function() {
                const iso = toIsoDate(this.value);

                $(this).toggleClass('is-invalid', iso === null);
                ok = ok && iso !== null;
            });

            if (!ok) {
                e.preventDefault();
                toastr.error('Ngày dự kiến KCS không hợp lệ, nhập theo dạng dd/mm/yyyy');
            }
        });

        // Lưu cả dòng mỗi khi một ô đổi giá trị: các cột dẫn xuất phụ thuộc lẫn nhau
        // nên phải để server tính lại trên toàn bộ dữ liệu của dòng.
        $('#kcs_tracking_table').on('change', '.kcs-input', function() {
            const $row = $(this).closest('tr');
            const payload = {
                _token: '{{ csrf_token() }}',
                plan_master_id: $row.data('plan-master-id')
            };
            let invalidDate = null;

            $row.find('.kcs-input').each(function() {
                if (!$(this).hasClass('kcs-date')) {
                    payload[this.name] = this.value;
                    return;
                }

                const iso = toIsoDate(this.value);

                $(this).toggleClass('is-invalid', iso === null);

                if (iso === null) {
                    invalidDate = invalidDate || this.value;
                    return;
                }

                this.value = toDisplayDate(iso);
                payload[this.name] = iso;
            });

            // Có ô ngày gõ sai thì không lưu cả dòng, tránh ghi đè giá trị cũ bằng ô trống
            if (invalidDate !== null) {
                toastr.error('Ngày "' + invalidDate + '" không hợp lệ, nhập theo dạng dd/mm/yyyy');
                return;
            }

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

        // ---- Sắp xếp theo cột (phía trình duyệt, không tải lại trang) ----
        // Bấm tiêu đề: sắp theo đúng cột đó (bấm lần nữa để đảo chiều).
        // Shift + bấm: thêm cột đó làm khoá sắp phụ (đã có thì đảo chiều khoá đó), số nhỏ
        // cạnh mũi tên là thứ tự ưu tiên. Ô trống luôn xuống cuối dù chiều nào.
        // Bấm cột STT để về lại thứ tự gốc của server (STT giữ nguyên số ban đầu của dòng).
        const $kcsTable = $('#kcs_tracking_table');
        let sortKeys = []; // [{col, dir}] theo thứ tự ưu tiên; dir 1 = tăng, -1 = giảm

        $kcsTable.find('thead th').each(function(index) {
            // Cột LS chỉ có nút xem lịch sử, không có gì để sắp
            if ($(this).text().trim() === 'LS') {
                return;
            }

            $(this).addClass('kcs-sortable').attr('data-col', index)
                .append(' <i class="fas fa-sort kcs-sort-icon"></i>');
        });

        // Giá trị dùng để so: ưu tiên data-sort, sau đó giá trị ô nhập, cuối cùng là chữ hiển
        // thị. Ngày dạng dd/mm/yyyy đổi về Y-m-d để so đúng thứ tự thời gian (các ô dẫn xuất
        // được JS ghi lại dạng d/m/Y sau mỗi lần lưu nên không dùng data-sort).
        function sortValue(td) {
            if (!td) {
                return '';
            }

            if (td.dataset.sort !== undefined) {
                return td.dataset.sort;
            }

            const input = td.querySelector('input');
            const raw = (input ? input.value : td.textContent).trim().replace(/\s+/g, ' ');
            const iso = toIsoDate(raw);

            return iso ? iso : raw;
        }

        function compareValues(a, b) {
            const na = Number(a);
            const nb = Number(b);

            if (!isNaN(na) && !isNaN(nb)) {
                return na - nb;
            }

            return a.localeCompare(b, 'vi', {
                numeric: true,
                sensitivity: 'base'
            });
        }

        function applySort() {
            const $tbody = $kcsTable.find('tbody');
            const rows = $tbody.children('tr[data-plan-master-id]').get();

            const keyed = rows.map(function(tr, i) {
                return {
                    tr: tr,
                    i: i,
                    v: sortKeys.map(k => sortValue(tr.children[k.col]))
                };
            });

            keyed.sort(function(x, y) {
                for (let k = 0; k < sortKeys.length; k++) {
                    const a = x.v[k];
                    const b = y.v[k];

                    if (a === b) continue;
                    if (a === '') return 1;
                    if (b === '') return -1;

                    const diff = compareValues(a, b) * sortKeys[k].dir;

                    if (diff !== 0) return diff;
                }

                return x.i - y.i; // giữ thứ tự hiện tại khi bằng nhau mọi khoá
            });

            $tbody.append(keyed.map(k => k.tr));

            // Cập nhật mũi tên + số thứ tự ưu tiên (chỉ hiện số khi sắp nhiều cột)
            $kcsTable.find('.kcs-sort-icon').attr('class', 'fas fa-sort kcs-sort-icon');
            $kcsTable.find('.kcs-sort-order').remove();

            sortKeys.forEach(function(key, index) {
                const $th = $kcsTable.find('th.kcs-sortable[data-col="' + key.col + '"]');

                $th.find('.kcs-sort-icon').attr('class',
                    'fas kcs-sort-icon kcs-sort-active ' + (key.dir === 1 ? 'fa-sort-up' : 'fa-sort-down'));

                if (sortKeys.length > 1) {
                    $th.find('.kcs-sort-icon').after('<sup class="kcs-sort-order">' + (index + 1) + '</sup>');
                }
            });
        }

        $kcsTable.on('click', 'th.kcs-sortable', function(e) {
            const col = Number($(this).attr('data-col'));
            const found = sortKeys.findIndex(k => k.col === col);

            if (e.shiftKey) {
                if (found >= 0) {
                    sortKeys[found].dir = -sortKeys[found].dir;
                } else {
                    sortKeys.push({
                        col: col,
                        dir: 1
                    });
                }
            } else if (sortKeys.length === 1 && found === 0) {
                sortKeys[0].dir = -sortKeys[0].dir;
            } else {
                sortKeys = [{
                    col: col,
                    dir: 1
                }];
            }

            applySort();
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

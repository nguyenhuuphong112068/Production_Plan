<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<script src="{{ asset('assets/plugins/local_cdn/xlsx.bundle.js') }}"></script>

<style>
    :root {
        --wk-border: #d8dee5;
        /* Vàng Stella cho tiêu đề bảng */
        --wk-head: #CDC717;
        --wk-head-text: #003A4F;
    }

    .content-wrapper {
        background-color: #f4f6f9;
        height: calc(100vh);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .wk-toolbar {
        background: #fff;
        border-bottom: 1px solid var(--wk-border);
    }

    .wk-table-container {
        flex: 1;
        overflow: auto;
        background: #fff;
        position: relative;
    }

    /* Bảng không co ép theo màn hình: cột giữ nguyên bề rộng,
       phần dư tràn ra và cuộn ngang trong .wk-table-container */
    table.wk-table {
        border-collapse: separate;
        border-spacing: 0;
        width: auto;
        min-width: 100%;
        table-layout: fixed;
        font-size: 0.78rem;
    }

    table.wk-table th,
    table.wk-table td {
        border-right: 1px solid var(--wk-border);
        border-bottom: 1px solid var(--wk-border);
        vertical-align: top;
        padding: 4px 6px;
    }

    /* Hàng tiêu đề ngày: dính khi cuộn dọc */
    table.wk-table thead th {
        position: sticky;
        top: 0;
        z-index: 30;
        background: var(--wk-head);
        color: var(--wk-head-text);
        text-align: center;
        vertical-align: middle;
        padding: 8px 4px;
    }

    /* Cột phòng: dính khi cuộn ngang */
    table.wk-table .wk-room-col {
        position: sticky;
        left: 0;
        z-index: 20;
        background: #f8f9fa;
        width: 160px;
        min-width: 160px;
        box-shadow: 2px 0 3px rgba(0, 0, 0, 0.06);
    }

    table.wk-table thead th.wk-room-col {
        z-index: 40;
        background: var(--wk-head);
    }

    table.wk-table .wk-day-col {
        width: var(--wk-day-width, 320px);
        min-width: var(--wk-day-width, 320px);
    }

    .wk-day-weekend {
        background: #fff8e1;
    }

    .wk-day-today {
        background: #e7f3ff;
    }

    thead .wk-day-today {
        background: #0d6efd !important;
        color: #fff !important;
    }

    .wk-day-name {
        font-weight: 800;
        font-size: 1.05rem;
    }

    .wk-day-sub {
        font-size: 0.85rem;
        font-weight: 700;
        opacity: 0.9;
    }

    /* Ô phòng */
    .wk-room-code {
        font-weight: 700;
        color: #003A4F;
    }

    .wk-room-name {
        font-size: 0.72rem;
        color: #495057;
        line-height: 1.15;
    }

    .wk-room-meta {
        font-size: 0.68rem;
        color: #868e96;
    }

    /* Khối một ca trong ngày */
    .wk-shift {
        border: 1px solid var(--wk-border);
        border-left-width: 4px;
        border-radius: 4px;
        padding: 3px 5px;
        margin-bottom: 4px;
        background: #fff;
    }

    .wk-shift:last-child {
        margin-bottom: 0;
    }

    .wk-shift-1 {
        border-left-color: #007bff;
    }

    .wk-shift-2 {
        border-left-color: #28a745;
    }

    .wk-shift-3 {
        border-left-color: #dc3545;
    }

    .wk-shift-4 {
        border-left-color: #fd7e14;
    }

    .wk-shift-6 {
        border-left-color: #6f42c1;
    }

    .wk-shift-other {
        border-left-color: #868e96;
    }

    .wk-badge {
        display: inline-block;
        border-radius: 3px;
        padding: 0 5px;
        font-size: 0.68rem;
        font-weight: 700;
        color: #fff;
    }

    .wk-badge-1 {
        background: #007bff;
    }

    .wk-badge-2 {
        background: #28a745;
    }

    .wk-badge-3 {
        background: #dc3545;
    }

    .wk-badge-4 {
        background: #fd7e14;
    }

    .wk-badge-6 {
        background: #6f42c1;
    }

    .wk-badge-other {
        background: #868e96;
    }

    /* Badge trạng thái ngày ở trục nhân sự */
    .wk-day-badge {
        display: inline-block;
        border-radius: 3px;
        padding: 1px 6px;
        margin-bottom: 3px;
        font-size: 0.68rem;
        font-weight: 700;
        color: #fff;
    }

    /* Màu giống các ô thống kê của Dashboard tình hình nhân sự */
    .wk-badge-under {
        background: #ffc107;
        color: #212529;
    }

    .wk-badge-full {
        background: #28a745;
    }

    .wk-badge-over {
        background: #0d6efd;
    }

    .wk-badge-leave {
        background: #6c757d;
    }

    .wk-badge-unassigned {
        background: #dc3545;
    }

    .wk-badge-maternity {
        background: #d81b60;
    }

    .wk-badge-long_leave {
        background: #6f42c1;
    }

    /* Ô trống nhưng ngày đó người này đã có ca ở tổ khác */
    .wk-badge-other-group {
        background: #fff;
        color: #6c757d;
        border: 1px solid #6c757d;
    }

    .wk-shift-time {
        font-weight: 700;
        color: #212529;
        font-size: 0.72rem;
    }

    .wk-shift-sum {
        font-size: 0.68rem;
        color: #6c757d;
    }

    /* Danh sách công việc: luôn hiện đầy đủ, không cắt dòng */
    .wk-jobs {
        font-size: 0.82rem;
        font-weight: 700;
        color: #212529;
        line-height: 1.35;
        margin: 3px 0;
        border-left: 2px solid #e9ecef;
        padding-left: 5px;
        white-space: normal;
        overflow-wrap: break-word;
        word-break: break-word;
    }

    .wk-jobs>div {
        margin-bottom: 1px;
    }

    /* Tên phòng hiển thị trong ô khi xem theo trục nhân sự */
    .wk-room-tag {
        display: inline-block;
        background: #eef2f5;
        color: #003A4F;
        border-radius: 3px;
        padding: 0 5px;
        margin-top: 2px;
        font-size: 0.72rem;
        font-weight: 700;
    }

    /* Nhân sự */
    .wk-person {
        display: flex;
        align-items: baseline;
        gap: 4px;
        font-size: 0.7rem;
        line-height: 1.3;
    }

    .wk-person-label {
        display: inline-block;
        width: 13px;
        height: 13px;
        line-height: 13px;
        text-align: center;
        border-radius: 2px;
        background: #e9ecef;
        color: #495057;
        font-size: 0.6rem;
        font-weight: 700;
        flex: 0 0 auto;
    }

    .wk-person-name {
        flex: 1 1 auto;
        overflow-wrap: break-word;
    }

    .wk-person-time {
        flex: 0 0 auto;
        color: #0b6b3a;
        font-weight: 600;
        font-size: 0.66rem;
    }

    .wk-person-time.adjusted {
        color: #b54708;
    }

    .wk-empty {
        color: #ced4da;
        font-size: 0.72rem;
        text-align: center;
        display: block;
        padding-top: 6px;
    }

    .wk-row-total {
        font-size: 0.66rem;
        color: #6c757d;
    }

    .wk-group-row td {
        background: #eef2f5;
        padding: 3px 6px;
    }

    /* Nhãn tổ bám mép trái khi cuộn ngang */
    .wk-group-label {
        position: sticky;
        left: 6px;
        display: inline-block;
        font-weight: 700;
        color: #003A4F;
        font-size: 0.75rem;
    }

    .wk-hit {
        background: #fff3cd;
    }

    @media print {

        .wk-toolbar,
        .main-sidebar,
        .main-header {
            display: none !important;
        }

        .content-wrapper {
            height: auto;
            overflow: visible;
            margin: 0 !important;
        }

        .wk-table-container {
            overflow: visible;
        }

        table.wk-table {
            font-size: 0.6rem;
            width: 100%;
            min-width: 0;
        }
    }
</style>

@php
    /**
     * Bảng lịch công tác theo tuần, dùng chung cho sản xuất và bảo trì/hiệu chuẩn.
     * Trang gọi phải truyền các biến sau:
     *   $weeklyRoute   tên route của chính trang tuần đó
     *   $dailyUrl      link sang trang xem theo ngày
     *   $groupOptions  danh sách tổ: [(object)['value'=>..,'label'=>..]]
     *   $groupDisabled khoá ô chọn tổ hay không
     *   $rowColTitle / $hideEmptyLabel / $searchPlaceholder  nhãn hiển thị
     *   $exportTitle / $exportFilePrefix  tiêu đề và tên file khi Xuất Excel
     * cùng dữ liệu chung: $days, $rows, $cells, $rowTotals, $groupNames,
     * $group_code, $weekStart, $weekEnd, $anchorDate, $totalPeople, $totalHours.
     */
    $shiftClass = function ($s) {
        return in_array((string) $s, ['1', '2', '3', '4', '6'], true) ? (string) $s : 'other';
    };
    $prevWeek = \Carbon\Carbon::parse($weekStart)->subWeek()->format('Y-m-d');
    $nextWeek = \Carbon\Carbon::parse($weekStart)->addWeek()->format('Y-m-d');
    $rowColTitle = $rowColTitle ?? 'Phòng / Thiết Bị';
    $hideEmptyLabel = $hideEmptyLabel ?? 'Ẩn phòng trống';
    $searchPlaceholder = $searchPlaceholder ?? 'Tìm nhân sự / phòng...';
    $groupDisabled = $groupDisabled ?? false;
    $exportTitle = $exportTitle ?? 'Lịch Công Tác Theo Tuần';
    $exportFilePrefix = $exportFilePrefix ?? 'Lich_Cong_Tac_Tuan';
    // Dùng cho bản công khai (không có thanh điều hướng của layout đăng nhập)
    $toolbarOffset = $toolbarOffset ?? 60;
    $extraParams = $extraParams ?? [];
    $extraQuery = $extraParams ? '&' . http_build_query($extraParams) : '';
    $productionOptions = $productionOptions ?? null;

    // Trục dòng: phòng (mặc định) hoặc nhân sự. Chỉ render trục đang xem để
    // trang không phình gấp đôi khi chọn "Tất cả" các tổ.
    $axis = request('axis') === 'person' ? 'person' : 'room';
    $isPersonAxis = $axis === 'person';
    $boardRows = $isPersonAxis ? $personRows : $rows;
    $boardCells = $isPersonAxis ? $personCells : $cells;
    $boardTotals = $isPersonAxis ? $personTotals : $rowTotals;
    // Badge trạng thái ngày [personKey][date] chỉ có ý nghĩa ở trục nhân sự (xem AssignmentWeek::attachDayStatus)
    $boardDayStatus = $isPersonAxis ? ($personDayStatus ?? []) : [];
    $dayStatusLabels = [
        'under' => '< 8h',
        'full' => 'Đủ 8h',
        'over' => '> 8h',
        'leave' => 'Nghỉ phép',
        'unassigned' => 'Chưa phân công',
        'maternity' => 'Thai sản',
        'long_leave' => 'Nghỉ phép dài hạn',
    ];
@endphp

<div class="content-wrapper">
    <div class="wk-toolbar py-2 px-3" style="margin-top: {{ $toolbarOffset }}px;">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <form action="{{ route($weeklyRoute) }}" method="GET" class="form-inline" id="wk-filter-form">
                @foreach ($extraParams as $paramName => $paramValue)
                    @if (!($productionOptions && $paramName === 'production_code'))
                        <input type="hidden" name="{{ $paramName }}" value="{{ $paramValue }}">
                    @endif
                @endforeach

                @if ($productionOptions)
                    <span class="mr-2 font-weight-bold">Phân xưởng:</span>
                    {{-- Mỗi phân xưởng có danh sách tổ riêng nên đổi phân xưởng thì bỏ bộ lọc tổ --}}
                    <select name="production_code" class="form-control form-control-sm mr-3 shadow-sm"
                        style="border: 2px solid #003A4F; width: 90px"
                        onchange="this.form.elements['group_code'].disabled = true; this.form.submit()">
                        @foreach ($productionOptions as $opt)
                            <option value="{{ $opt }}"
                                {{ ($extraParams['production_code'] ?? '') === $opt ? 'selected' : '' }}>
                                {{ $opt }}</option>
                        @endforeach
                    </select>
                @endif

                <span class="mr-2 font-weight-bold">Tổ:</span>
                <select name="group_code" class="form-control form-control-sm mr-3 shadow-sm"
                    style="border: 2px solid #003A4F" onchange="this.form.submit()"
                    {{ $groupDisabled ? 'disabled' : '' }}>
                    @foreach ($groupOptions as $opt)
                        <option value="{{ $opt->value }}"
                            {{ (string) $group_code === (string) $opt->value ? 'selected' : '' }}>
                            {{ $opt->label }}</option>
                    @endforeach
                </select>

                <span class="mr-2 font-weight-bold">Trục dòng:</span>
                <select name="axis" class="form-control form-control-sm mr-3 shadow-sm"
                    style="border: 2px solid #003A4F; width: 110px" onchange="this.form.submit()"
                    title="Chọn dữ liệu hiển thị trên trục dòng">
                    <option value="room" {{ $isPersonAxis ? '' : 'selected' }}>Phòng</option>
                    <option value="person" {{ $isPersonAxis ? 'selected' : '' }}>Nhân sự</option>
                </select>

                <span class="mr-2 font-weight-bold">Tuần:</span>
                <a href="{{ route($weeklyRoute) }}?group_code={{ $group_code }}&axis={{ $axis }}&reportedDate={{ $prevWeek }}{{ $extraQuery }}"
                    class="btn btn-sm btn-outline-secondary" title="Tuần trước"><i class="fas fa-chevron-left"></i></a>
                <input type="date" name="reportedDate" value="{{ $anchorDate }}"
                    class="form-control form-control-sm shadow-sm mx-1" style="border: 2px solid #003A4F"
                    onchange="this.form.submit()">
                <a href="{{ route($weeklyRoute) }}?group_code={{ $group_code }}&axis={{ $axis }}&reportedDate={{ $nextWeek }}{{ $extraQuery }}"
                    class="btn btn-sm btn-outline-secondary" title="Tuần sau"><i class="fas fa-chevron-right"></i></a>
                <a href="{{ route($weeklyRoute) }}?group_code={{ $group_code }}&axis={{ $axis }}{{ $extraQuery }}"
                    class="btn btn-sm btn-outline-primary ml-1">Tuần này</a>

                <span class="badge badge-light border ml-3 py-1" style="font-size: 0.78rem;">
                    {{ \Carbon\Carbon::parse($weekStart)->format('d/m/Y') }} –
                    {{ \Carbon\Carbon::parse($weekEnd)->format('d/m/Y') }}
                </span>
                <span class="badge badge-info ml-2 py-1" style="font-size: 0.78rem;">
                    {{ $totalPeople }} nhân sự · {{ number_format($totalHours, 1) }} giờ
                </span>
            </form>

            <div class="d-flex align-items-center">
                <input type="text" id="wk-search" class="form-control form-control-sm shadow-sm mr-2"
                    style="width: 190px; border: 2px solid #003A4F" placeholder="{{ $searchPlaceholder }}">
                {{-- Trục nhân sự chỉ gồm người đã có lịch nên không cần tuỳ chọn này --}}
                <div class="custom-control custom-checkbox mr-3" {{ $isPersonAxis ? 'hidden' : '' }}>
                    <input type="checkbox" class="custom-control-input" id="wk-hide-empty" checked>
                    <label class="custom-control-label" for="wk-hide-empty"
                        style="font-size: 0.8rem;">{{ $hideEmptyLabel }}</label>
                </div>
                <span class="mr-1" style="font-size: 0.8rem;">Rộng cột:</span>
                <select id="wk-col-width" class="form-control form-control-sm shadow-sm mr-2"
                    style="width: 88px; border: 2px solid #003A4F" title="Bề rộng ô nội dung mỗi ngày">
                    <option value="260">260px</option>
                    <option value="320" selected>320px</option>
                    <option value="400">400px</option>
                    <option value="520">520px</option>
                </select>
                <a class="btn btn-sm btn-primary shadow-sm mr-2" href="{{ $dailyUrl }}">
                    <i class="fas fa-calendar-day"></i> Xem theo ngày
                </a>
                <button class="btn btn-sm btn-success shadow-sm" id="wk-export-excel">
                    <i class="fas fa-file-excel"></i> Xuất Excel
                </button>
            </div>
        </div>
    </div>

    <div class="wk-table-container">
        <table class="wk-table">
            <thead>
                <tr>
                    <th class="wk-room-col">
                        {{ $isPersonAxis ? 'Nhân Sự' : $rowColTitle }}
                    </th>
                    @foreach ($days as $day)
                        <th class="wk-day-col {{ $day->is_today ? 'wk-day-today' : '' }}">
                            <div class="wk-day-name">{{ $day->label }}</div>
                            <div class="wk-day-sub">{{ $day->short }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php $lastGroup = null; @endphp
                @foreach ($boardRows as $row)
                    @php
                        $rowCells = $boardCells[$row->row_key] ?? [];
                        // Dòng nhân sự trắng lịch vẫn có badge Nghỉ phép/Chưa phân công nên không được coi là dòng trống
                        $hasData = !empty($rowCells) || !empty($boardDayStatus[$row->row_key] ?? []);
                        $totals = $boardTotals[$row->row_key] ?? ['hours' => 0, 'slots' => 0, 'shifts' => 0];
                    @endphp

                    @if ($lastGroup !== $row->group_code)
                        @php $lastGroup = $row->group_code; @endphp
                        <tr class="wk-group-row">
                            <td colspan="{{ count($days) + 1 }}">
                                <span class="wk-group-label">
                                    {{ $groupNames[$row->group_code] ?? 'Tổ ' . $row->group_code }}
                                </span>
                            </td>
                        </tr>
                    @endif

                    <tr class="wk-row" data-has-data="{{ $hasData ? 1 : 0 }}">
                        <td class="wk-room-col">
                            @if ($isPersonAxis)
                                <div class="wk-room-name font-weight-bold" style="font-size: 0.8rem;">
                                    {{ $row->name }}</div>
                                @if ($row->code)
                                    <div class="wk-room-meta">{{ $row->code }}</div>
                                @endif
                            @else
                                <div class="wk-room-code">{{ $row->code }}</div>
                                <div class="wk-room-name">{{ $row->name }}</div>
                                @if ($row->meta)
                                    <div class="wk-room-meta">{{ $row->meta }}</div>
                                @endif
                            @endif
                            @if ($hasData)
                                <div class="wk-row-total mt-1">
                                    {{ $totals['shifts'] ?? 0 }} ca
                                    @if (!$isPersonAxis)
                                        · {{ $totals['slots'] ?? 0 }} lượt
                                    @endif
                                    · {{ number_format($totals['hours'] ?? 0, 1) }}h
                                </div>
                            @endif
                        </td>

                        @foreach ($days as $day)
                            @php $shifts = $rowCells[$day->date] ?? []; @endphp
                            <td
                                class="wk-day-col {{ $day->is_today ? 'wk-day-today' : ($day->is_weekend ? 'wk-day-weekend' : '') }}">
                                @php $dayStatus = $boardDayStatus[$row->row_key][$day->date] ?? null; @endphp
                                @if ($dayStatus)
                                    <div class="wk-day-badge wk-badge-{{ $dayStatus }}">{{ $dayStatusLabels[$dayStatus] }}</div>
                                    @if (empty($shifts) && in_array($dayStatus, ['under', 'full', 'over'], true))
                                        <div class="wk-day-badge wk-badge-other-group"
                                            title="Ngày này đã được phân công ở tổ khác">Tổ khác</div>
                                    @endif
                                @endif
                                @forelse ($shifts as $s)
                                    <div class="wk-shift wk-shift-{{ $shiftClass($s->shift) }}">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span>
                                                <span
                                                    class="wk-badge wk-badge-{{ $shiftClass($s->shift) }}">{{ $s->shift_name }}</span>
                                                @if ($isPersonAxis)
                                                    <span
                                                        class="wk-shift-time ml-1 {{ $s->adjusted ? 'text-warning' : '' }}">{{ $s->time }}</span>
                                                @else
                                                    <span
                                                        class="wk-shift-time ml-1">{{ $s->start }}-{{ $s->end }}</span>
                                                @endif
                                            </span>
                                            <span class="wk-shift-sum">
                                                @if (!$isPersonAxis)
                                                    {{ $s->headcount }}ng ·
                                                @endif
                                                {{ number_format($s->hours, 1) }}h
                                            </span>
                                        </div>

                                        @if ($isPersonAxis && $s->room_label)
                                            <div class="wk-room-tag">{{ $s->room_label }}</div>
                                        @endif

                                        @if (!empty($s->jobs))
                                            <div class="wk-jobs">
                                                @foreach ($s->jobs as $job)
                                                    <div>{{ $job }}</div>
                                                @endforeach
                                            </div>
                                        @endif

                                        @if ($isPersonAxis)
                                            @if ($s->note)
                                                <div class="wk-person text-success">{{ $s->note }}</div>
                                            @endif
                                        @else
                                            @foreach ($s->people as $p)
                                                <div class="wk-person"
                                                    title="{{ $p->name }}{{ $p->code ? ' - ' . $p->code : '' }}{{ $p->note ? ' | ' . $p->note : '' }}">
                                                    <span class="wk-person-label">{{ $p->label }}</span>
                                                    <span class="wk-person-name">{{ $p->name }}</span>
                                                    <span
                                                        class="wk-person-time {{ $p->adjusted ? 'adjusted' : '' }}">{{ $p->time }}</span>
                                                </div>
                                            @endforeach

                                            @if ($s->headcount === 0)
                                                <div class="wk-person text-danger">Chưa gán nhân sự</div>
                                            @endif
                                        @endif
                                    </div>
                                @empty
                                    <span class="wk-empty">–</span>
                                @endforelse
                            </td>
                        @endforeach
                    </tr>
                @endforeach

                <tr id="wk-no-data" style="display: none;">
                    <td colspan="{{ count($days) + 1 }}" class="text-center text-muted py-4">
                        Không có dữ liệu phù hợp bộ lọc trong tuần này.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    (function() {
        // Bề rộng ô nội dung mỗi ngày, nhớ lựa chọn của người dùng
        var selWidth = document.getElementById('wk-col-width');
        var savedWidth = localStorage.getItem('wk-col-width');
        if (savedWidth) selWidth.value = savedWidth;

        function applyWidth() {
            document.documentElement.style.setProperty('--wk-day-width', selWidth.value + 'px');
            localStorage.setItem('wk-col-width', selWidth.value);
        }
        selWidth.addEventListener('change', applyWidth);
        applyWidth();

        var chkHideEmpty = document.getElementById('wk-hide-empty');
        var inputSearch = document.getElementById('wk-search');

        function applyFilter() {
            var keyword = inputSearch.value.trim().toLowerCase();
            var hideEmpty = chkHideEmpty.checked;
            var visibleRows = 0;
            var tbody = document.querySelector('table.wk-table tbody');

            tbody.querySelectorAll('tr.wk-row').forEach(function(tr) {
                var hasData = tr.dataset.hasData === '1';
                var matched = true;

                // Khi tìm kiếm: chỉ giữ dòng có tên nhân sự hoặc tên phòng khớp
                if (keyword) {
                    var roomText = tr.querySelector('.wk-room-col').innerText.toLowerCase();
                    matched = roomText.indexOf(keyword) !== -1;

                    tr.querySelectorAll('.wk-person').forEach(function(p) {
                        var hit = p.innerText.toLowerCase().indexOf(keyword) !== -1;
                        p.classList.toggle('wk-hit', hit);
                        if (hit) matched = true;
                    });
                } else {
                    tr.querySelectorAll('.wk-hit').forEach(function(p) {
                        p.classList.remove('wk-hit');
                    });
                }

                var visible = matched && (hasData || !hideEmpty);
                tr.style.display = visible ? '' : 'none';
                if (visible) visibleRows++;
            });

            var emptyRow = tbody.querySelector('tr[id^="wk-no-data"]');
            if (emptyRow) emptyRow.style.display = visibleRows ? 'none' : '';

            // Ẩn tiêu đề tổ nếu toàn bộ dòng bên dưới đã bị ẩn
            var groupRow = null;
            var groupHasVisible = false;
            tbody.querySelectorAll('tr.wk-group-row, tr.wk-row').forEach(function(tr) {
                if (tr.classList.contains('wk-group-row')) {
                    if (groupRow) groupRow.style.display = groupHasVisible ? '' : 'none';
                    groupRow = tr;
                    groupHasVisible = false;
                } else if (tr.style.display !== 'none') {
                    groupHasVisible = true;
                }
            });
            if (groupRow) groupRow.style.display = groupHasVisible ? '' : 'none';
        }

        chkHideEmpty.addEventListener('change', applyFilter);
        inputSearch.addEventListener('input', applyFilter);
        applyFilter();
    })();
</script>

<script>
    // Xuất Excel: đọc trực tiếp từ bảng đang hiển thị (đã áp bộ lọc tìm kiếm /
    // ẩn dòng trống) nên file xuất ra đúng những gì người dùng đang thấy trên màn hình.
    (function() {
        function cellText(el) {
            return el ? el.innerText.replace(/[ \t]+/g, ' ').trim() : '';
        }

        // Gom nội dung của 1 ô ngày (có thể có nhiều ca) thành text nhiều dòng
        function extractDayCell(td) {
            var shifts = td.querySelectorAll('.wk-shift');
            if (!shifts.length) return '';

            var blocks = [];
            shifts.forEach(function(shift) {
                var lines = [];
                var badge = shift.querySelector('.wk-badge');
                var time = shift.querySelector('.wk-shift-time');
                var sum = shift.querySelector('.wk-shift-sum');
                lines.push([cellText(badge), cellText(time), sum ? '(' + cellText(sum) + ')' : '']
                    .filter(Boolean).join(' '));

                // Trục nhân sự: phòng là thông tin thay đổi theo từng ca
                var roomTag = shift.querySelector('.wk-room-tag');
                if (roomTag) lines.push('  ' + cellText(roomTag));

                shift.querySelectorAll('.wk-jobs > div').forEach(function(job) {
                    lines.push('  ' + cellText(job));
                });
                shift.querySelectorAll('.wk-person').forEach(function(p) {
                    lines.push('  ' + cellText(p));
                });

                blocks.push(lines.join('\n'));
            });
            return blocks.join('\n\n');
        }

        function buildRoomLabel(tr) {
            var code = cellText(tr.querySelector('.wk-room-code'));
            var name = cellText(tr.querySelector('.wk-room-name'));
            var meta = cellText(tr.querySelector('.wk-room-meta'));
            var total = cellText(tr.querySelector('.wk-row-total'));
            return [[code, name].filter(Boolean).join(' - '), meta, total].filter(Boolean).join('\n');
        }

        document.getElementById('wk-export-excel').addEventListener('click', function() {
            var table = document.querySelector('table.wk-table');
            var dayHeaders = Array.from(table.querySelectorAll('thead .wk-day-col')).map(function(th) {
                var name = cellText(th.querySelector('.wk-day-name'));
                var short = cellText(th.querySelector('.wk-day-sub'));
                return name + ' ' + short;
            });

            var groupSelect = document.querySelector('select[name="group_code"]');
            var groupLabel = groupSelect ? groupSelect.options[groupSelect.selectedIndex].text : '';

            var aoa = [
                ['{{ $exportTitle }}'],
                ['Tổ: ' + groupLabel + '    Tuần: {{ \Carbon\Carbon::parse($weekStart)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($weekEnd)->format('d/m/Y') }}'],
                ['{{ $isPersonAxis ? 'Nhân Sự' : $rowColTitle }}'].concat(dayHeaders),
            ];
            var metaRows = 3;

            table.querySelectorAll('tbody tr').forEach(function(tr) {
                if (tr.style.display === 'none' || tr.id === 'wk-no-data') return;

                if (tr.classList.contains('wk-group-row')) {
                    aoa.push([cellText(tr.querySelector('.wk-group-label'))]);
                    return;
                }

                var row = [buildRoomLabel(tr)];
                tr.querySelectorAll('td.wk-day-col').forEach(function(td) {
                    row.push(extractDayCell(td));
                });
                aoa.push(row);
            });

            var wb = XLSX.utils.book_new();
            var ws = XLSX.utils.aoa_to_sheet(aoa);

            var colCount = dayHeaders.length + 1;
            ws['!cols'] = [{
                wch: 26
            }].concat(dayHeaders.map(function() {
                return {
                    wch: 42
                };
            }));
            ws['!merges'] = [
                {
                    s: {
                        r: 0,
                        c: 0
                    },
                    e: {
                        r: 0,
                        c: colCount - 1
                    }
                },
                {
                    s: {
                        r: 1,
                        c: 0
                    },
                    e: {
                        r: 1,
                        c: colCount - 1
                    }
                },
            ];

            var range = XLSX.utils.decode_range(ws['!ref']);
            var thinBorder = {
                style: 'thin',
                color: {
                    auto: 1
                }
            };
            var allBorders = {
                top: thinBorder,
                bottom: thinBorder,
                left: thinBorder,
                right: thinBorder
            };

            for (var R = range.s.r; R <= range.e.r; R++) {
                var isGroupRow = aoa[R] && aoa[R].length === 1 && R >= metaRows;
                if (isGroupRow) {
                    ws['!merges'].push({
                        s: {
                            r: R,
                            c: 0
                        },
                        e: {
                            r: R,
                            c: colCount - 1
                        }
                    });
                }
                for (var C = 0; C < colCount; C++) {
                    var addr = XLSX.utils.encode_cell({
                        r: R,
                        c: C
                    });
                    if (!ws[addr]) ws[addr] = {
                        t: 's',
                        v: ''
                    };
                    ws[addr].s = ws[addr].s || {};
                    ws[addr].s.border = allBorders;
                    ws[addr].s.alignment = {
                        wrapText: true,
                        vertical: 'top'
                    };

                    if (R === 0) {
                        ws[addr].s.font = {
                            bold: true,
                            sz: 14
                        };
                    } else if (R === 1) {
                        ws[addr].s.font = {
                            bold: true,
                            sz: 11
                        };
                    } else if (R === 2) {
                        ws[addr].s.font = {
                            bold: true
                        };
                        ws[addr].s.fill = {
                            fgColor: {
                                rgb: 'CDC717'
                            }
                        };
                        ws[addr].s.alignment.horizontal = 'center';
                        ws[addr].s.alignment.vertical = 'middle';
                    } else if (isGroupRow) {
                        ws[addr].s.font = {
                            bold: true
                        };
                        ws[addr].s.fill = {
                            fgColor: {
                                rgb: 'EEF2F5'
                            }
                        };
                    }
                }
            }

            XLSX.utils.book_append_sheet(wb, ws, 'Lich_Tuan');
            var fileName =
                '{{ $exportFilePrefix }}{{ $isPersonAxis ? '_NhanSu' : '' }}_{{ \Carbon\Carbon::parse($weekStart)->format('Ymd') }}_{{ \Carbon\Carbon::parse($weekEnd)->format('Ymd') }}.xlsx';
            XLSX.writeFile(wb, fileName);
        });
    })();
</script>

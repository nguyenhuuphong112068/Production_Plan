<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/select2/select2.min.css') }}" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link href="{{ asset('assets/plugins/nouislider/nouislider.min.css') }}" rel="stylesheet" />
<style>
/* Slider Styling */
.time-slider .noUi-handle {
    width: 14px !important;
    height: 14px !important;
    right: -7px !important;
    top: -5px !important;
    border-radius: 50% !important;
    box-shadow: 0 1px 4px rgba(0,0,0,0.4) !important;
    border: 2px solid #fff !important;
    background: #555 !important;
    cursor: grab;
}
.time-slider .noUi-handle:active {
    cursor: grabbing;
}
.time-slider .noUi-handle::before, .time-slider .noUi-handle::after { display: none !important; }
.time-slider {
    border: none !important;
    background: #e9ecef !important;
    height: 4px !important;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.1) !important;
    margin-top: 5px;
}
.shift-1-slider .noUi-connect { background: #007bff !important; }
.shift-2-slider .noUi-connect { background: #28a745 !important; }
.shift-3-slider .noUi-connect { background: #dc3545 !important; }
</style>

<style>
    :root {
        --primary-gold: #007bff;
        --light-gold: #e7f3ff;
        --border-color: #ddd;
    }

    .content-wrapper {

        background-color: #f4f6f9;
        height: calc(100vh);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .table-container {
        flex: 1;
        overflow: auto;
        padding: 0;
        background: #fff;
        position: relative;
    }

    /* Sidebar Styles */
    .main-content-layout {
        display: flex;
        flex: 1;
        overflow: hidden;
        position: relative;
    }

    .personnel-sidebar {
        width: 350px;
        background: #fff;
        border-left: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        transition: all 0.3s ease;
        z-index: 100;
        box-shadow: -2px 0 5px rgba(0, 0, 0, 0.1);
    }

    .personnel-sidebar.collapsed {
        width: 0;
        margin-right: -350px;
        opacity: 0;
    }

    .sidebar-toggle-btn {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        z-index: 101;
        background: #003A4F;
        color: white;
        border: none;
        padding: 15px 5px;
        border-radius: 8px 0 0 8px;
        cursor: pointer;
        transition: right 0.3s ease;
    }

    .sidebar-toggle-btn:hover {
        background: #005a7a;
    }

    .sidebar-toggle-btn.active {
        right: 350px;
    }

    .shift-badge {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        font-size: 0.8rem;
    }

    .shift-c1 {
        background-color: #007bff;
    }

    .shift-c2 {
        background-color: #28a745;
    }

    .shift-c3 {
        background-color: #dc3545;
    }

    .shift-c4 {
        background-color: #6f42c1;
    }

    .shift-hc {
        background-color: #ffc107;
        color: black;
    }

    .shift-p {
        background-color: #6c757d;
    }

    .selected-for-drag {
        background-color: #d1ecf1 !important;
        border-left: 3px solid #0c5460 !important;
    }

    .room-row:has(.btn-save-room.is-dirty) .btn-clone-row,
    .room-row:has(.btn-save-room.btn-warning) .btn-clone-row,
    .room-row:has(.btn-save-room.is-dirty) .btn-clone-shift,
    .room-row:has(.btn-save-room.btn-warning) .btn-clone-shift {
        display: none !important;
    }

    .table-assignment {
        border-collapse: separate;
        border-spacing: 0;
        background: #fff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        table-layout: fixed;
        width: max-content;
        min-width: 100%;
    }

    .table-assignment thead th {
        position: sticky;
        top: -1px;
        z-index: 10;
        background-color: #c5c500 !important;
        color: #fff;
        font-weight: bold;
        text-align: center;
        border: 1px solid #aaa !important;
        padding: 10px 5px;
        font-size: 0.95rem;
    }

    .table-assignment tbody td {
        border: 1px solid var(--border-color) !important;
        vertical-align: top !important;
        padding: 0 !important;
    }

    .room-name-cell {
        background-color: #fff;
        font-weight: bold;
        text-align: center;
        padding: 10px !important;
    }

    .theory-cell {
        background-color: var(--light-gold);
        font-size: 0.85rem;
        padding: 10px !important;
        position: relative;
    }

    .off-stream-row,
    .off-stream-row .room-name-cell,
    .off-stream-row .theory-cell,
    .off-stream-row .assignment-inner-table td {
        background-color: #fa9898 !important;
    }

    .btn-copy-theory {
        position: absolute;
        right: 5px;
        top: 50%;
        transform: translateY(-50%);
        background: #007bff;
        color: white;
        border: none;
        border-radius: 3px;
        padding: 2px 6px;
        font-size: 0.75rem;
        cursor: pointer;
        opacity: 0.7;
        transition: opacity 0.2s;
    }

    .job-desc {
        min-height: 80px;
        padding: 10px;
        border: 1px solid #c0daf5;
        border-radius: 4px;
        background-color: #fff;
        text-align: left;
        white-space: pre-wrap;
        font-size: 1rem;
        font-weight: bold;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .job-desc.active-target {
        border-color: #007bff;
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
    }

    .btn-save-room.is-dirty {
        background-color: #ffc107 !important;
        border-color: #ffc107 !important;
        color: #000 !important;
    }

    .personnel-label {
        width: 22px;
        height: 22px;
        background-color: #003A4F;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        margin-right: 8px;
        flex-shrink: 0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }

    .btn-copy-theory:hover {
        opacity: 1;
    }

    .assignment-inner-table {
        width: 100%;
        margin-bottom: 0;
        border: none;
        table-layout: fixed;
    }

    .assignment-inner-table td {
        border: none !important;
        border-bottom: 1px solid var(--border-color) !important;
        padding: 5px !important;
        vertical-align: top !important;
    }

    .assignment-inner-table tr:last-child td {
        border-bottom: none !important;
    }

    .form-control-sm {
        border-radius: 2px;
        border: 1px solid #ccc;
    }

    .select2-container--default .select2-selection--single {
        height: 31px !important;
        border-radius: 2px !important;
        border: 1px solid #ccc !important;
    }

    .btn-add-shift {
        color: #28a745;
        cursor: pointer;
        font-size: 1.0rem;
        margin-left: 10px;
    }

    .btn-remove-shift {
        color: #dc3545;
        cursor: pointer;
        font-size: 1rem;
    }

    /* Tùy chỉnh Select2 Multiple hiển thị xuống dòng và chữ đen */
    .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        display: flex !important;
        flex-direction: column !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        display: block !important;
        width: 95% !important;
        float: none !important;
        color: #000 !important;
        background-color: #f1f3f4 !important;
        border: 1px solid #ccc !important;
        margin: 2px 0 !important;
        padding: 2px 8px !important;
        font-size: 13px !important;
    }

    .btn-add-person {
        color: #007bff;
        text-decoration: none !important;
        font-weight: bold;
        font-size: 0.9rem;
        padding: 2px 5px;
    }

    .job-desc:empty:before {
        content: attr(placeholder);
        color: #adb5bd;
        font-style: italic;
    }

    .theory-cell .btn-copy-theory {
        display: none;
    }

    .plan-item:hover .btn-copy-plan {
        display: block !important;
    }

    .draggable-person {
        cursor: grab;
        user-select: none;
    }

    .draggable-person:active {
        cursor: grabbing;
    }

    .personnel-container.drag-over {
        background-color: #e7f3ff !important;
        border: 2px dashed #007bff !important;
        min-height: 40px;
    }

    .personnel-row.shift-mismatch {
        background-color: #ffe8cc !important;
    }

    /* Styles for assigned personnel in sidebar */
    .draggable-person.person-assigned {
        opacity: 0.6;
    }

    .draggable-person.person-assigned::after {
        display: none;
    }

    .person-assigned-c1 {
        background-color: rgba(40, 167, 69, 0.15) !important;
        border-left: 4px solid #28a745 !important;
    }

    .person-assigned-c2 {
        background-color: rgba(0, 123, 255, 0.15) !important;
        border-left: 4px solid #007bff !important;
    }

    .person-assigned-c3 {
        background-color: rgba(220, 53, 69, 0.15) !important;
        border-left: 4px solid #dc3545 !important;
    }

    .person-assigned-hc {
        background-color: rgba(255, 193, 7, 0.15) !important;
        border-left: 4px solid #ffc107 !important;
    }

    .person-assigned-khác {
        background-color: rgba(108, 117, 125, 0.15) !important;
        border-left: 4px solid #6c757d !important;
    }

    .theory-col {
        display: none;
    }
    .overlap-warning .select2-container--default .select2-selection--single {
        border-color: #dc3545 !important;
        background-color: #ffe6e6 !important;
    }
</style>
@php
    $production_code = session('user')['production_code'];
    $reportedDateObj = \Carbon\Carbon::parse($reportedDate)->startOfDay();
    $todayObj = \Carbon\Carbon::today();
    $isPastDate = $reportedDateObj->lt($todayObj);
    $hasEditPermission = user_has_permission(session('user')['userId'], 'production_assignment', 'boolean');
    $canEdit = $hasEditPermission && !$isPastDate && (!empty($group_code) || $production_code != 'PXV1');
@endphp

<div class="content-wrapper">
    <div class="content-header py-2 px-3" style="margin-top: 60px;">
        <div class="d-flex justify-content-between align-items-center">
            <form action="{{ route('pages.assignment.production.index') }}" method="GET" class="form-inline">
                <span class="mr-2 font-weight-bold">Tổ:</span>
                <select name="group_code" class="form-control form-control-sm mr-4 shadow-sm"
                    style="border: 2px solid #003A4F" onchange="this.form.submit()"
                    {{ $isLocked || $production_code != 'PXV1' ? 'disabled' : '' }}>
                    <option value="">-- Tất cả --</option>
                    @foreach ($groups as $g)
                        <option value="{{ $g->group_code }}" {{ $group_code == $g->group_code ? 'selected' : '' }}>
                            {{ $g->production_group }}</option>
                    @endforeach
                </select>

                <span class="mr-2 font-weight-bold">Chọn Ngày:</span>
                <input type="date" name="reportedDate" value="{{ $reportedDate }}"
                    class="form-control form-control-sm shadow-sm" style="border: 2px solid #003A4F"
                    onchange="this.form.submit()">
            </form>

            <div class="d-flex align-items-center">
                <button class="btn btn-sm btn-secondary shadow-sm ml-2" id="btn-view-report"
                    title="Xem báo cáo tình hình nhân sự hiện tại">
                    <i class="fas fa-chart-bar"></i> Xem báo cáo
                </button>

                @if ($canEdit)
                    <button class="btn btn-sm btn-success shadow-sm" id="btn-add-custom-task">
                        <i class="fas fa-plus"></i> Thêm Công Tác Khác
                    </button>
                    <button class="btn btn-sm btn-info shadow-sm ml-2" id="btn-auto-assign"
                        title="Sắp xếp tự động nhân sự cho các phòng">
                        <i class="fas fa-robot"></i> Tự động phân công
                    </button>
                    <button class="btn btn-sm btn-primary shadow-sm ml-2" id="btn-save-all">
                        <i class="fas fa-save"></i> Lưu toàn bộ lịch
                    </button>
                @endif
                <button class="btn btn-sm btn-info shadow-sm ml-2" id="btn-toggle-theory"
                    title="Ẩn/Hiện cột Lịch Lý Thuyết">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-dark shadow-sm ml-2" id="btn-print-schedule" title="In lịch công tác"
                    data-url="{{ route('pages.assignment.production.public') }}?production_code={{ $production_code }}&group_code={{ $group_code }}&reportedDate={{ $reportedDate }}&print=1">
                    <i class="fas fa-print"></i> In Lịch
                </button>
            </div>

        </div>
    </div>

    <div class="main-content-layout">
        <div class="table-container">
            <table class="table table-assignment">
                <thead>
                    <tr>
                        <th style="width: 100px">Phòng / Thiết Bị</th>
                        <th style="width: 150px" class="theory-col">Lịch Lý Thuyết</th>
                        <th style="width: 100px">Ca / SL</th>
                        <th style="width: 250px">Nhân sự</th>
                        <th style="width: 350px">Hoạt Động</th>
                        {{-- <th style="width: 150px">Chi Tiết Công Việc</th> --}}
                        <th style="width: 60px">Hủy</th>
                        <th style="width: 60px" class="text-center">Lưu</th>
                        {{-- <th style="width: 200px">Báo cáo hoạt động</th> --}}
                    </tr>
                </thead>
                <tbody id="main-assignment-tbody">
                    @foreach ($tasks as $task)
                        <tr class="room-row {{ $task->assignments->first()->off_stream ?? 0 ? 'off-stream-row' : '' }}"
                            data-sp-id="{{ $task->sp_id ?: (count($task->assignments) > 0 ? 'EXT_EXISTING_' . $task->assignments[0]->id : '') }}"
                            data-room-id="{{ $task->room_id }}" data-group-code="{{ $task->group_code }}"
                            data-n1="{{ $task->number_of_employes_on_sheet1 }}"
                            data-n2="{{ $task->number_of_employes_on_sheet2 }}"
                            data-n3="{{ $task->number_of_employes_on_sheet3 }}"
                            data-n4="{{ $task->number_of_employes_on_sheet4 }}"
                            data-nr="{{ $task->number_of_employes_on_sheet_regular }}">
                            <td class="room-name-cell">
                                @if (!$task->room_id || str_starts_with($task->sp_id, 'EXT_'))
                                    <div class="mb-1 text-primary font-weight-bold" style="font-size: 11px;">Công tác
                                        khác</div>
                                    <input type="text" list="room-list-options-{{ $loop->index }}"
                                        class="form-control form-control-sm room-select-custom mb-2"
                                        value="{{ $task->room_name !== 'Công tác khác' ? $task->room_name : '' }}"
                                        placeholder="-- Vị trí công tác --">
                                    <datalist id="room-list-options-{{ $loop->index }}">
                                        @foreach ($allRooms as $r)
                                            <option value="{{ $r->name }}">{{ $r->code }} -
                                                {{ $r->name }}</option>
                                        @endforeach
                                    </datalist>
                                @else
                                    <div><b>{{ $task->room_code }}</b></div>
                                    <div>{{ $task->room_name }}</div>
                                    @if (!empty($task->main_equiment_name))
                                        <div class="text-muted" style="font-size: 0.85em;">
                                            {{ $task->main_equiment_name }}
                                        </div>
                                    @endif
                                @endif
                                <div class="suggestion-container mt-2"></div>
                                <div class="mt-2 text-center">
                                    <button class="btn btn-outline-success btn-circle btn-add-shift mb-1"
                                        title="Thêm ca làm việc" {{ !$canEdit ? 'disabled' : '' }}>
                                        <i class="fas fa-plus"></i> Thêm Ca
                                    </button>
                                    @if (!$task->room_id || str_starts_with($task->sp_id, 'EXT_'))
                                        <br>
                                        <button class="btn btn-outline-info btn-circle btn-clone-row"
                                            title="Nhân bản toàn bộ ca" {{ !$canEdit ? 'disabled' : '' }}>
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    @endif
                                </div>
                                @if (!$task->room_id || str_starts_with($task->sp_id, 'EXT_'))
                                    @php
                                        $uniqueOsId = $task->room_id
                                            ? 'os_' . $task->room_id
                                            : 'os_ext_' . ($task->sp_id ?: rand(1000, 9999));
                                    @endphp
                                    <div class="custom-control custom-checkbox mt-2 text-center">
                                        <input type="checkbox" class="custom-control-input off-stream-check"
                                            id="{{ $uniqueOsId }}"
                                            {{ $task->assignments->first()->off_stream ?? 0 ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="{{ $uniqueOsId }}"
                                            style="font-size: 11px; cursor: pointer;">Không chính qui</label>
                                    </div>
                                @endif
                            </td>
                            <td class="theory-cell text-left position-relative theory-col">
                                <div class="theory-content">{!! $task->theory_display !!}</div>
                                @if ($task->theory_display != '<span class="text-muted italic">Không có lịch</span>')
                                    <button class="btn btn-xs btn-outline-primary btn-copy-theory-all mt-2"
                                        title="Chép toàn bộ" style="font-size: 10px; padding: 2px 6px;"
                                        {{ !$canEdit ? 'disabled' : '' }}>
                                        >>>
                                    </button>
                                @endif
                            </td>
                            <td colspan="4" class="p-0">
                                <table class="assignment-inner-table">
                                    <tbody class="assignment-container">
                                        @forelse($task->assignments as $assignment)
                                            <tr class="assignment-item {{ $assignment->is_foreign ?? false ? 'foreign-assignment' : '' }}"
                                                data-id="{{ $assignment->id }}"
                                                data-theory-start="{{ $task->theory_start }}"
                                                data-theory-end="{{ $task->theory_end }}">
                                                <td style="width: 100px">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <select class="form-control form-control-sm shift-select mb-1"
                                                            {{ !$canEdit ? 'disabled' : '' }}>
                                                            <option value="1"
                                                                {{ $assignment->Sheet == 1 ? 'selected' : '' }}>1
                                                            </option>
                                                            <option value="2"
                                                                {{ $assignment->Sheet == 2 ? 'selected' : '' }}>2
                                                            </option>
                                                            <option value="3"
                                                                {{ $assignment->Sheet == 3 ? 'selected' : '' }}>3
                                                            </option>
                                                            <option value="6"
                                                                {{ $assignment->Sheet == 6 ? 'selected' : '' }}>4
                                                            </option>
                                                            <option value="4"
                                                                {{ $assignment->Sheet == 4 ? 'selected' : '' }}>HC
                                                            </option>
                                                            <option value="5"
                                                                {{ $assignment->Sheet == 5 ? 'selected' : '' }}>Khác
                                                            </option>
                                                        </select>
                                                        <input type="time"
                                                            class="form-control form-control-sm start-time-input mb-1"
                                                            value="{{ $assignment->start_time_display }}"
                                                            {{ !$canEdit || ($assignment->is_foreign ?? false) ? 'disabled' : '' }}>
                                                        <input type="time"
                                                            class="form-control form-control-sm end-time-input mb-1"
                                                            value="{{ $assignment->end_time_display }}"
                                                            {{ !$canEdit || ($assignment->is_foreign ?? false) ? 'disabled' : '' }}>
                                                        <div class="input-group input-group-sm">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i
                                                                        class="fas fa-users"></i></span>
                                                            </div>
                                                            <input type="number"
                                                                class="form-control person-count-input"
                                                                value="{{ $assignment->number_of_employes ?? 1 }}"
                                                                min="1" title="Số lượng nhân sự cần"
                                                                {{ !$canEdit || ($assignment->is_foreign ?? false) ? 'disabled' : '' }}>
                                                        </div>
                                                        <div class="input-group input-group-sm mt-1"
                                                            title="Nhân sự chuyên nghiệp (cấp ≥ 3) tối thiểu cần có">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"
                                                                    style="font-size:0.7rem;padding:2px 5px;"><i
                                                                        class="fas fa-star"></i></span>
                                                            </div>
                                                            <input type="number"
                                                                class="form-control professional-count-input"
                                                                value="{{ $assignment->Num_of_per_Level_3 ?? 1 }}"
                                                                min="0" style="font-size:0.75rem;"
                                                                title="Số lượng nhân sự chuyên nghiệp (cấp ≥ 3) tối thiểu cần có"
                                                                placeholder="Cấp ≥3"
                                                                {{ !$canEdit || ($assignment->is_foreign ?? false) ? 'disabled' : '' }}>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="p-0" style="width: 350px">
                                                    <div class="personnel-container">
                                                        @foreach ($assignment->personnel_data as $p_info)
                                                            <div
                                                                class="personnel-row d-flex flex-column p-1 border-bottom" data-p-start="{{ $p_info->start ?? '' }}" data-p-end="{{ $p_info->end ?? '' }}">
                                                                <div class="d-flex align-items-center w-100">
                                                                <div class="personnel-label">
                                                                    {{ chr(65 + $loop->index) }}
                                                                </div>
                                                                <div style="flex: 1"
                                                                    class="d-flex align-items-center">
                                                                    <select
                                                                        class="form-control form-control-sm person-select"
                                                                        style="width: 40%"
                                                                        data-selected="{{ $p_info->personnel_id }}"
                                                                        data-op-type="{{ $p_info->operation_type ?? 'thủ công' }}"
                                                                        {{ !$canEdit || ($assignment->is_foreign ?? false) ? 'disabled' : '' }}>
                                                                        <option value="">-- Chọn người --
                                                                        </option>
                                                                    </select>
                                                                    @if (strtolower($p_info->operation_type ?? 'thủ công') == 'tự động')
                                                                        <i class="fas fa-robot text-info ml-1 op-icon"
                                                                            title="Sắp tự động"
                                                                            style="font-size: 0.8rem;"></i>
                                                                    @elseif(strtolower($p_info->operation_type ?? 'thủ công') == 'nhân bản')
                                                                        <i class="fas fa-copy text-success ml-1 op-icon"
                                                                            title="Nhân bản"
                                                                            style="font-size: 0.8rem;"></i>
                                                                    @else
                                                                        <i class="fas fa-hand-paper text-secondary ml-1 op-icon"
                                                                            title="Sắp thủ công"
                                                                            style="font-size: 0.8rem;"></i>
                                                                    @endif
                                                                    <input type="text"
                                                                        class="form-control form-control-sm person-notif ml-1"
                                                                        style="width: 50%; font-size: 0.7rem; height: 28px; padding: 2px 5px;"
                                                                        value="{{ $p_info->notification ?? '' }}"
                                                                        placeholder="Lưu ý (nếu có)..."
                                                                        {{ !$canEdit || ($assignment->is_foreign ?? false) ? 'disabled' : '' }}>
                                                                </div>

                                                                @if ($canEdit && !($assignment->is_foreign ?? false))
                                                                    <i
                                                                        class="fas fa-times text-danger ml-1 btn-remove-person cursor-pointer"></i>
                                                                @endif
                                                                </div>
                                                                <div class="d-flex align-items-center w-100 pl-4 pr-2 mt-1 mb-1 time-slider-container" style="{{ !$canEdit || ($assignment->is_foreign ?? false) ? 'opacity: 0.6; pointer-events: none;' : '' }}">
                                                                    <div class="time-slider flex-grow-1"></div>
                                                                    <div class="time-display ml-3 font-weight-bold" style="font-size: 0.75rem; width: 85px; text-align: right; color: #444;"></div>
                                                                    <input type="hidden" class="p-start-input" value="{{ $p_info->start ? \Carbon\Carbon::parse($p_info->start)->format('H:i') : '' }}">
                                                                    <input type="hidden" class="p-end-input" value="{{ $p_info->end ? \Carbon\Carbon::parse($p_info->end)->format('H:i') : '' }}">
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    @if ($canEdit)
                                                        <div class="text-left p-1"
                                                            style="border-top: 1px dashed #eee">
                                                            <a href="javascript:void(0)" class="btn-add-person"><i
                                                                    class="fas fa-plus-square"></i></a>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td style="width: 500px">
                                                    @if ($assignment->is_foreign ?? false)
                                                        <div class="badge badge-info mb-1">Lịch của
                                                            {{ $assignment->stage_groups_code == 7 ? 'ĐGSC' : 'ĐGTC' }}
                                                        </div>
                                                    @endif
                                                    @php
                                                        $isJobEditable =
                                                            $canEdit && !($assignment->is_foreign ?? false);
                                                        if ($group_code == 8 && ($assignment->is_scheduled ?? false)) {
                                                            $isJobEditable = false;
                                                        }
                                                    @endphp
                                                    <div class="form-control form-control-sm job-desc"
                                                        contenteditable="{{ $isJobEditable ? 'true' : 'false' }}"
                                                        style="min-height: 80px; height: auto; white-space: pre-wrap;"
                                                        placeholder="Nội dung...">{!! $assignment->Job_description !!}</div>

                                                    @if (!empty($assignment->id) && !empty($assignment->assigner_name))
                                                        <div class="mt-1 text-muted text-right"
                                                            style="font-size: 0.75rem; font-style: italic;">
                                                            <i class="fas fa-user-edit"></i> Phân công bởi:
                                                            {{ $assignment->assigner_name }}
                                                            @if (!empty($assignment->created_at))
                                                                lúc
                                                                {{ \Carbon\Carbon::parse($assignment->created_at)->format('d/m/Y H:i') }}
                                                            @endif
                                                        </div>
                                                    @endif
                                                </td>
                                                <td style="width: 60px" class="text-center">
                                                    @if ($canEdit)
                                                        <i class="fas fa-times-circle btn-remove-shift cursor-pointer text-danger mb-2"
                                                            style="font-size: 1.1rem" title="Xóa ca này"></i>
                                                        <br />
                                                        <i class="fas fa-copy btn-clone-shift cursor-pointer text-info mt-1"
                                                            style="font-size: 1.1rem; display: none;"
                                                            title="Nhân bản ca này"></i>
                                                    @else
                                                        <i class="fas fa-lock text-muted"
                                                            title="Không thể chỉnh sửa"></i>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="assignment-item" data-theory-start="{{ $task->theory_start }}"
                                                data-theory-end="{{ $task->theory_end }}">
                                                <td style="width: 100px">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <select class="form-control form-control-sm shift-select mb-1"
                                                            {{ !$canEdit ? 'disabled' : '' }}>
                                                            <option value="1"
                                                                {{ $production_code == 'PXV1' ? 'selected' : '' }}>1
                                                            </option>
                                                            <option value="2">2</option>
                                                            <option value="3">3</option>
                                                            <option value="6">4</option>
                                                            <option value="4"
                                                                {{ $production_code == 'PXV1' ? '' : 'selected' }}>HC
                                                            </option>
                                                            <option value="5">Khác</option>
                                                        </select>
                                                        <input type="time"
                                                            class="form-control form-control-sm start-time-input mb-1"
                                                            value="{{ $production_code == 'PXV1' ? '06:00' : '07:15' }}"
                                                            {{ !$canEdit ? 'disabled' : '' }}>
                                                        <input type="time"
                                                            class="form-control form-control-sm end-time-input mb-1"
                                                            value="{{ $production_code == 'PXV1' ? '14:00' : '16:00' }}"
                                                            {{ !$canEdit ? 'disabled' : '' }}>
                                                        <div class="input-group input-group-sm">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"><i
                                                                        class="fas fa-users"></i></span>
                                                            </div>
                                                            <input type="number"
                                                                class="form-control person-count-input" value="1"
                                                                min="1" title="Số lượng nhân sự cần"
                                                                {{ !$canEdit ? 'disabled' : '' }}>
                                                        </div>
                                                        <div class="input-group input-group-sm mt-1"
                                                            title="Nhân sự chuyên nghiệp (cấp ≥ 3) tối thiểu cần có">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text"
                                                                    style="font-size:0.7rem;padding:2px 5px;"><i
                                                                        class="fas fa-star"></i></span>
                                                            </div>
                                                            <input type="number"
                                                                class="form-control professional-count-input"
                                                                value="1" min="0"
                                                                style="font-size:0.75rem;"
                                                                title="Số lượng nhân sự chuyên nghiệp (cấp ≥ 3) tối thiểu cần có"
                                                                placeholder="Cấp ≥3"
                                                                {{ !$canEdit ? 'disabled' : '' }}>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="p-0" style="width: 350px">
                                                    <div class="personnel-container">
                                                        <div
                                                            class="personnel-row d-flex flex-column p-1 border-bottom">
                                                            <div class="d-flex align-items-center w-100">
                                                            <div class="personnel-label">A</div>
                                                            <div style="flex: 1" class="d-flex align-items-center">
                                                                <select
                                                                    class="form-control form-control-sm person-select"
                                                                    style="width: 40%" data-op-type="thủ công"
                                                                    {{ !$canEdit ? 'disabled' : '' }}>
                                                                    <option value="">-- Chọn người --</option>
                                                                </select>
                                                                <i class="fas fa-hand-paper text-secondary ml-1 op-icon"
                                                                    title="Sắp thủ công"
                                                                    style="font-size: 0.8rem;"></i>
                                                                <input type="text"
                                                                    class="form-control form-control-sm person-notif ml-1"
                                                                    style="width: 50%; font-size: 0.7rem; height: 28px; padding: 2px 5px;"
                                                                    placeholder="Lưu ý (nếu có)..."
                                                                    {{ !$canEdit ? 'disabled' : '' }}>
                                                            </div>
                                                            @if ($canEdit)
                                                                <i
                                                                    class="fas fa-times text-danger ml-1 btn-remove-person cursor-pointer"></i>
                                                            @endif
                                                            </div>
                                                            <div class="d-flex align-items-center w-100 pl-4 pr-2 mt-1 mb-1 time-slider-container" style="{{ !$canEdit ? 'opacity: 0.6; pointer-events: none;' : '' }}">
                                                                <div class="time-slider flex-grow-1"></div>
                                                                <div class="time-display ml-3 font-weight-bold" style="font-size: 0.75rem; width: 85px; text-align: right; color: #444;"></div>
                                                                <input type="hidden" class="p-start-input" value="">
                                                                <input type="hidden" class="p-end-input" value="">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @if ($canEdit)
                                                        <div class="text-left p-1"
                                                            style="border-top: 1px dashed #eee">
                                                            <a href="javascript:void(0)" class="btn-add-person"><i
                                                                    class="fas fa-plus-square"></i></a>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td style="width: 500px">
                                                    <div class="form-control form-control-sm job-desc"
                                                        contenteditable="{{ $canEdit ? 'true' : 'false' }}"
                                                        style="min-height: 80px; height: auto; white-space: pre-wrap;"
                                                        placeholder="Nội dung..."></div>
                                                </td>
                                                <td style="width: 60px" class="text-center">
                                                    @if ($canEdit)
                                                        <i class="fas fa-times-circle btn-remove-shift cursor-pointer text-danger mb-2"
                                                            style="font-size: 1.1rem" title="Xóa ca này"></i>
                                                        <br />
                                                        <i class="fas fa-copy btn-clone-shift cursor-pointer text-info mt-1"
                                                            style="font-size: 1.1rem; display: none;"
                                                            title="Nhân bản ca này"></i>
                                                    @else
                                                        <i class="fas fa-lock text-muted"
                                                            title="Không thể chỉnh sửa"></i>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot class="timeline-tfoot">
                                        <tr>
                                            <td colspan="4" class="pt-2 pb-2 px-1 border-top-0">
                                                <div class="timeline-container position-relative"
                                                    style="height: 6px; background: #e9ecef; border-radius: 3px; width: 100%; margin-top: 25px; margin-bottom: 5px;">
                                                    <div
                                                        style="position: absolute; left: 0%; top: -6px; width:1px; height:18px; border-left:1px solid #aaa;">
                                                    </div>
                                                    <div
                                                        style="position: absolute; left: 0%; top: -18px; font-size: 10px; color:#000; font-weight: 600; transform:translateX(-50%);">
                                                        06h</div>

                                                    <div
                                                        style="position: absolute; left: 25%; top: -4px; width:1px; height:14px; border-left:1px dashed #ccc;">
                                                    </div>
                                                    <div
                                                        style="position: absolute; left: 25%; top: -16px; font-size: 9px; color:#000; transform:translateX(-50%);">
                                                        12h</div>

                                                    <div
                                                        style="position: absolute; left: 50%; top: -4px; width:1px; height:14px; border-left:1px dashed #ccc;">
                                                    </div>
                                                    <div
                                                        style="position: absolute; left: 50%; top: -16px; font-size: 9px; color:#000; transform:translateX(-50%);">
                                                        18h</div>

                                                    <div
                                                        style="position: absolute; left: 75%; top: -4px; width:1px; height:14px; border-left:1px dashed #ccc;">
                                                    </div>
                                                    <div
                                                        style="position: absolute; left: 75%; top: -16px; font-size: 9px; color:#000; transform:translateX(-50%);">
                                                        00h</div>

                                                    <div
                                                        style="position: absolute; left: 100%; top: -6px; width:1px; height:18px; border-left:1px solid #aaa;">
                                                    </div>
                                                    <div
                                                        style="position: absolute; left: 100%; top: -18px; font-size: 10px; color:#000; font-weight: 600; transform:translateX(-50%);">
                                                        06h</div>

                                                    <div class="timeline-bg"
                                                        style="position: absolute; top:0; left:0; width: 100%; height: 100%; overflow: hidden; border-radius: 3px;">
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </td>
                            <td class="text-center" style="vertical-align: middle !important; width: 60px;">
                                @php
                                    $isDirty = false;
                                    foreach ($task->assignments as $a) {
                                        if (is_null($a->id)) {
                                            $isDirty = true;
                                            break;
                                        }
                                    }
                                @endphp
                                <button
                                    class="btn btn-xs {{ $isDirty ? 'is-dirty' : 'btn-primary' }} btn-save-room shadow-sm"
                                    {{ !$canEdit ? 'disabled' : '' }}>
                                    <i class="fas fa-save"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Sidebar Nhân Sự -->
        <div class="personnel-sidebar" id="personnel-sidebar">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light shadow-sm">
                <h6 class="mb-0 font-weight-bold text-primary"><i class="fas fa-users mr-2"></i>Tình Hình Nhân Sự</h6>
                <button class="btn btn-sm btn-link text-muted p-0" id="close-sidebar-btn"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="p-2 border-bottom bg-white">
                <div class="input-group input-group-sm shadow-sm mb-2">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white border-right-0"><i
                                class="fas fa-search text-muted"></i></span>
                    </div>
                    <input type="text" class="form-control border-left-0" id="sidebar-personnel-search"
                        placeholder="Tìm tên hoặc mã NV...">
                </div>
                <div class="custom-control custom-switch pl-4">
                    <input type="checkbox" class="custom-control-input" id="filter-under-8h">
                    <label class="custom-control-label small text-muted font-weight-bold cursor-pointer"
                        for="filter-under-8h">Chỉ hiện nhân sự < 8h làm việc</label>
                </div>
            </div>
            <div class="sidebar-body p-0 overflow-auto" id="sidebar-data-container" style="flex: 1">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted">Đang tải dữ liệu...</div>
                </div>
            </div>
            <div class="p-2 border-top bg-light">
                <div class="d-flex justify-content-around font-weight-bold small text-center"
                    style="font-size: 0.75rem;">
                    <div>
                        <div class="shift-badge shift-c1 mx-auto mb-1">C1</div>
                        <div>Ca 1</div>
                        <div class="text-primary mt-1"><span id="sidebar-count-c1">0</span></div>
                    </div>
                    <div>
                        <div class="shift-badge shift-c2 mx-auto mb-1">C2</div>
                        <div>Ca 2</div>
                        <div class="text-primary mt-1"><span id="sidebar-count-c2">0</span></div>
                    </div>
                    <div>
                        <div class="shift-badge shift-c3 mx-auto mb-1">C3</div>
                        <div>Ca 3</div>
                        <div class="text-primary mt-1"><span id="sidebar-count-c3">0</span></div>
                    </div>
                    <div>
                        <div class="shift-badge shift-c4 mx-auto mb-1">C4</div>
                        <div>Ca 4</div>
                        <div class="text-primary mt-1"><span id="sidebar-count-c4">0</span></div>
                    </div>
                    <div>
                        <div class="shift-badge shift-hc mx-auto mb-1">HC</div>
                        <div>HC</div>
                        <div class="text-primary mt-1"><span id="sidebar-count-hc">0</span></div>
                    </div>
                    <div>
                        <div class="shift-badge shift-p mx-auto mb-1">P</div>
                        <div>Phép</div>
                        <div class="text-primary mt-1"><span id="sidebar-count-p">0</span></div>
                    </div>
                </div>
            </div>
        </div>

        <button class="sidebar-toggle-btn active" id="toggle-sidebar-btn" title="Xem lịch trực nhân sự">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
</div>

<!-- Modal Xem Bậc Kỹ Năng -->
<div class="modal fade" id="modalSkillView" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-graduation-cap mr-2"></i>Bậc Kỹ Năng Nhân Sự
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 bg-light border-bottom d-flex align-items-center">
                    <div class="avatar-circle mr-3 bg-info text-white d-flex align-items-center justify-content-center"
                        style="width: 45px; height: 45px; border-radius: 50%; font-size: 1.2rem; font-weight: bold;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 font-weight-bold text-dark" id="skill-modal-name">Họ và Tên</h6>
                        <small class="text-muted" id="skill-modal-code">Mã NV: 00000</small>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light small font-weight-bold">
                            <tr>
                                <th class="border-top-0">Phòng Sản Xuất</th>
                                <th class="border-top-0 text-center" style="width: 100px;">Bậc</th>
                            </tr>
                        </thead>
                        <tbody id="skill-modal-body">
                            <!-- Data injected here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Clone Custom Task -->
<div class="modal fade" id="modalCloneCustomTask" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-copy mr-2"></i>Nhân Bản Công Tác Khác
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {{-- <div class="alert alert-warning small">
                    <i class="fas fa-info-circle mr-1"></i> Chọn các ngày trong tương lai (lớn hơn ngày hiện tại) để nhân bản công tác này. Dữ liệu công tác và nhân sự sẽ được sao chép nguyên trạng.
                </div> --}}
                <div class="form-group d-flex justify-content-center">
                    <input type="text" id="clone-date-input" class="d-none">
                </div>
                <div class="mt-3">
                    <label class="font-weight-bold">Các ngày đã chọn:</label>
                    <div id="clone-dates-container" class="d-flex flex-wrap"
                        style="gap: 8px; min-height: 40px; border: 1px dashed #ccc; padding: 10px; border-radius: 5px;">
                        <span class="text-muted small w-100 text-center" id="clone-dates-empty">Chưa có ngày nào được
                            chọn</span>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="clone-as-suggestion" value="1">
                        <label class="custom-control-label font-weight-bold text-info" for="clone-as-suggestion">
                            <i class="fas fa-lightbulb mr-1"></i> Chỉ lưu làm Gợi ý Nhân sự (Không lưu thành phân công
                            chính thức)
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-info font-weight-bold" id="btn-confirm-clone">
                    <i class="fas fa-check mr-1"></i> Xác nhận Clone
                </button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('assets/plugins/nouislider/nouislider.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    const dbAssignments = @json($dbAssignments ?? []);
    const assignmentSuggestions = @json($suggestions ?? []);

    const employeeCodeToId = {
        @foreach ($personnel as $p)
            "{{ $p->code }}": "{{ $p->id }}",
        @endforeach
    };

    const personnelInfo = {
        @foreach ($personnel as $p)
            "{{ $p->id }}": {
                name: {!! json_encode($p->name) !!},
                code: "{{ $p->code }}"
            },
        @endforeach
    };

    const roomNames = {
        @foreach (DB::table('room')->where('deparment_code', $production_code)->get() as $r)
            "{{ $r->id }}": {!! json_encode($r->name) !!},
        @endforeach
    };

    const personnelSkills = {
        @foreach ($skills as $pid => $s)
            "{{ $pid }}": "{{ $s->allowed_rooms_with_levels }}",
        @endforeach
    };

    function getPersonLevel(pid, roomId) {
        if (!pid || !roomId) return 0;
        const skillsStr = personnelSkills[pid] || '';
        if (!skillsStr) return 0;
        const pairs = skillsStr.split('|');
        for (const pair of pairs) {
            const parts = pair.split(':');
            if (parts[0] === roomId.toString()) return parseInt(parts[1] || 0);
        }
        return 0;
    }

    function validateProfRequirement($item) {
        const roomId = $item.closest('.room-row').attr('data-room-id');
        if (!roomId) return;
        const requiredProf = parseInt($item.find('.professional-count-input').val()) || 0;
        let actualProfCount = 0;
        $item.find('.personnel-container .person-select').each(function() {
            const pid = $(this).val();
            if (pid && getPersonLevel(pid, roomId) >= 3) {
                actualProfCount++;
            }
        });

        if (requiredProf > 0 && actualProfCount < requiredProf) {
            $item.find('.professional-count-input').addClass('is-invalid');
            $item.find('.professional-count-input').closest('.input-group').find('.input-group-text').addClass(
                'bg-danger text-white');
        } else {
            $item.find('.professional-count-input').removeClass('is-invalid');
            $item.find('.professional-count-input').closest('.input-group').find('.input-group-text').removeClass(
                'bg-danger text-white');
        }
    }

    let currentSidebarData = [];
    let currentSidebarDay = null;

    function validateShiftDiscrepancy($item) {
        if (!currentSidebarData || currentSidebarData.length === 0) return;

        const shiftVal = $item.find('.shift-select').val();
        let shiftName = '';
        switch (shiftVal) {
            case '1':
                shiftName = 'C1';
                break;
            case '2':
                shiftName = 'C2';
                break;
            case '3':
                shiftName = 'C3';
                break;
            case '4':
                shiftName = 'HC';
                break;
            case '6':
                shiftName = 'C4';
                break;
            default:
                shiftName = 'Khác';
                break;
        }

        $item.find('.personnel-row').each(function() {
            const pid = $(this).find('.person-select').val();
            let isDiscrepancy = false;

            if (pid) {
                const dayKey = 'day' + currentSidebarDay;
                const personCode = Object.keys(employeeCodeToId).find(key => employeeCodeToId[key] == pid);

                if (personCode) {
                    const personData = currentSidebarData.find(p => (p.employeeId || p.code) == personCode);
                    if (personData) {
                        const personShift = (personData.days && personData.days[dayKey]) ? personData.days[
                            dayKey].toUpperCase() : 'HC';
                        if (shiftName !== 'Khác' && personShift !== 'P' && personShift !== shiftName) {
                            isDiscrepancy = true;
                        }
                    }
                }
            }

            if (isDiscrepancy) {
                $(this).css('background-color', '#ffe8d6');
            } else {
                $(this).css('background-color', '');
            }
        });
    }

    function updateSidebarPersonnelTimes() {
        // Helper to calculate hours between HH:mm times
        function calculateDurationHours(startStr, endStr) {
            if (!startStr || !endStr) return 0;
            const sParts = startStr.split(':');
            const eParts = endStr.split(':');

            let sMin = parseInt(sParts[0], 10) * 60 + parseInt(sParts[1], 10);
            let eMin = parseInt(eParts[0], 10) * 60 + parseInt(eParts[1], 10);

            if (eMin < sMin) {
                // Crosses midnight
                eMin += 24 * 60;
            }

            return (eMin - sMin) / 60;
        }

        const filterUnder8h = $('#filter-under-8h').is(':checked');

        $('.draggable-person').each(function() {
            const $el = $(this);
            const code = $el.attr('data-code');
            const personId = employeeCodeToId[code];
            const isLeave = $el.attr('data-shift-key') === 'P';

            // Remove existing badges container
            $el.find('.personnel-time-ranges').remove();

            let totalHours = 0;

            if (personId) {
                const assignments = [];
                // 1. Scan DOM
                $('.room-row .assignment-item:not(.foreign-assignment)').each(function() {
                    const $item = $(this);
                    const assId = $item.attr('data-id');
                    let found = false;
                    $item.find('.personnel-container .person-select').each(function() {
                        if ($(this).val() == personId.toString()) {
                            found = true;
                        }
                    });
                    if (found) {
                        const roomRow = $item.closest('.room-row');
                        let roomCode = 'Khác';
                        const customSelect = roomRow.find('.room-select-custom');
                        if (customSelect.length > 0) {
                            const selectedOption = customSelect.find('option:selected');
                            const selectedText = selectedOption.text().trim();
                            if (selectedText && !selectedText.startsWith('--')) {
                                roomCode = selectedText.split('-')[0].trim();
                            } else {
                                roomCode = 'Khác';
                            }
                        } else {
                            roomCode = roomRow.find('.room-name-cell b').text().trim() || 'NA';
                        }
                        const start = $item.find('.start-time-input').val() || '';
                        const end = $item.find('.end-time-input').val() || '';

                        if (start || end) {
                            assignments.push({
                                assignment_id: assId,
                                room: roomCode,
                                start: start,
                                end: end,
                                is_local: true
                            });
                        }
                    }
                });

                // 2. Scan DB assignments from other groups/departments
                const dbList = dbAssignments[personId.toString()] || [];
                dbList.forEach(dbAss => {
                    const existsInDom = dbAss.assignment_id && $(
                        `.assignment-item[data-id="${dbAss.assignment_id}"]`).length > 0;
                    if (!existsInDom) {
                        assignments.push({
                            assignment_id: dbAss.assignment_id,
                            room: dbAss.room_code || 'Khác',
                            start: dbAss.start,
                            end: dbAss.end,
                            is_local: false,
                            group_name: dbAss.group_name
                        });
                    }
                });

                if (assignments.length > 0) {
                    assignments.forEach(a => {
                        totalHours += calculateDurationHours(a.start, a.end);
                    });
                    totalHours = Math.round(totalHours * 100) / 100;

                    let badgeHtml = '<div class="personnel-time-ranges mt-1">';
                    badgeHtml +=
                        `<span class="badge badge-success text-white mr-1" style="font-size: 0.65rem; padding: 2px 4px; font-weight: bold;"><i class="fas fa-hourglass-half mr-1"></i>Tổng: ${totalHours}h</span>`;
                    assignments.forEach(a => {
                        if (a.is_local) {
                            badgeHtml +=
                                `<span class="badge badge-info text-white mr-1" style="font-size: 0.65rem; padding: 2px 4px; font-weight: normal;"><i class="far fa-clock mr-1"></i>${a.room}: ${a.start}-${a.end}</span>`;
                        } else {
                            badgeHtml +=
                                `<span class="badge text-white mr-1" style="font-size: 0.65rem; padding: 2px 4px; font-weight: normal; background-color: #6c757d;" title="Tổ khác: ${a.group_name}"><i class="fas fa-exchange-alt mr-1"></i>${a.group_name} (${a.room}): ${a.start}-${a.end}</span>`;
                        }
                    });
                    badgeHtml += '</div>';
                    $el.append(badgeHtml);
                }
            }

            // Apply filter under 8h
            if (filterUnder8h) {
                if (totalHours >= 8 || isLeave) {
                    $el.hide();
                } else {
                    $el.show();
                }
            } else {
                $el.show();
            }
        });

        // Hide empty shift headers or update visible count
        $('.shift-header-item').each(function() {
            const shiftKey = $(this).attr('data-shift-key');
            const visibleCount = $(`.draggable-person[data-shift-key="${shiftKey}"]:visible`).length;
            if (visibleCount === 0) {
                $(this).hide();
            } else {
                $(this).show();
                $(this).find('.shift-count-badge').text(visibleCount);
            }
        });

        validateAllOverlaps();
    }

    function validateAllOverlaps() {
        $('.personnel-row').removeClass('overlap-warning');
        $('.personnel-row .overlap-badge-container, .personnel-row .overlap-badge').remove();

        let overlapExists = false;
        const personnelData = [];

        $('.room-row .assignment-item:not(.foreign-assignment)').each(function() {
            const $item = $(this);
            const startStr = $item.find('.start-time-input').val();
            const endStr = $item.find('.end-time-input').val();
            if (!startStr || !endStr) return;
            
            const sOffset = timeToOffset(startStr);
            let eOffset = timeToOffset(endStr);
            if (eOffset <= sOffset) {
                eOffset += 24.0;
            }
            
            $item.find('.personnel-row').each(function() {
                const $row = $(this);
                const pid = $row.find('.person-select').val();
                if (pid) {
                    const roomCode = $item.closest('.room-row').find('.room-name-cell b').text().trim() || 'Khác';
                    personnelData.push({
                        pid: pid.toString(),
                        start: sOffset,
                        end: eOffset,
                        $row: $row,
                        room: roomCode,
                        startStr: startStr,
                        endStr: endStr
                    });
                }
            });
        });

        for (let i = 0; i < personnelData.length; i++) {
            for (let j = i + 1; j < personnelData.length; j++) {
                const p1 = personnelData[i];
                const p2 = personnelData[j];
                if (p1.pid === p2.pid) {
                    if (p1.start < p2.end && p2.start < p1.end) {
                        overlapExists = true;
                        
                        p1.$row.addClass('overlap-warning');
                        if (p1.$row.find('.overlap-badge').length === 0) {
                            let target1 = p1.$row.find('.time-slider-container');
                            $(`<div class="w-100 pl-4 mb-1 overlap-badge-container"><span class="badge badge-danger overlap-badge" title="Trùng lịch với ca khác (${p2.startStr}-${p2.endStr} tại ${p2.room})" style="font-size:0.75rem;"><i class="fas fa-exclamation-triangle mr-1"></i>Trùng: ${p2.startStr}-${p2.endStr} tại ${p2.room}</span></div>`).insertBefore(target1);
                        }
                        
                        p2.$row.addClass('overlap-warning');
                        if (p2.$row.find('.overlap-badge').length === 0) {
                            let target2 = p2.$row.find('.time-slider-container');
                            $(`<div class="w-100 pl-4 mb-1 overlap-badge-container"><span class="badge badge-danger overlap-badge" title="Trùng lịch với ca khác (${p1.startStr}-${p1.endStr} tại ${p1.room})" style="font-size:0.75rem;"><i class="fas fa-exclamation-triangle mr-1"></i>Trùng: ${p1.startStr}-${p1.endStr} tại ${p1.room}</span></div>`).insertBefore(target2);
                        }
                    }
                }
            }
        }

        return overlapExists;
    }

    function markRoomDirty(row) {
        row.find('.btn-save-room').addClass('is-dirty').removeClass('btn-primary');
    }

    function markRoomSaved(row) {
        row.find('.btn-save-room').removeClass('is-dirty').addClass('btn-primary');
    }

    function timeToOffset(timeStr) {
        if (!timeStr) return null;
        const parts = timeStr.split(':');
        let h = parseInt(parts[0], 10);
        let m = parseInt(parts[1], 10);
        let t = h + m / 60.0;
        let offset = t - 6.0;
        if (offset < 0) offset += 24.0;
        return offset;
    }

    function offsetToTime(offset) {
        let t = (offset % 24.0) + 6.0;
        if (t >= 24.0) t -= 24.0;
        let h = Math.floor(t);
        let m = Math.round((t - h) * 60);
        if (m === 60) {
            h++;
            m = 0;
        }
        if (h === 24) h = 0;
        return (h < 10 ? '0' + h : h) + ':' + (m < 10 ? '0' + m : m);
    }

    function findLongestGap(row, currentItemRow = null) {
        let occupied = [];
        row.find('.assignment-item').each(function() {
            if (currentItemRow && $(this)[0] === currentItemRow[0]) return;
            const s = $(this).find('.start-time-input').val();
            const e = $(this).find('.end-time-input').val();
            if (s && e) {
                let startOff = timeToOffset(s);
                let endOff = timeToOffset(e);
                if (endOff <= startOff) endOff += 24;
                occupied.push({
                    s: startOff,
                    e: endOff
                });
            }
        });

        // Merge overlaps
        occupied.sort((a, b) => a.s - b.s);
        let merged = [];
        if (occupied.length > 0) {
            let curr = occupied[0];
            for (let i = 1; i < occupied.length; i++) {
                if (occupied[i].s < curr.e) {
                    curr.e = Math.max(curr.e, occupied[i].e);
                } else {
                    merged.push(curr);
                    curr = occupied[i];
                }
            }
            merged.push(curr);
        }

        // Find gaps in [0, 24]
        let gaps = [];
        let lastE = 0;
        merged.forEach(m => {
            if (m.s > lastE) gaps.push({
                s: lastE,
                e: m.s
            });
            lastE = Math.max(lastE, m.e);
        });
        if (lastE < 24) gaps.push({
            s: lastE,
            e: 24
        });

        // Longest gap
        let longest = {
            s: 7.25,
            e: 16,
            len: 0
        }; // Default to HC if nothing
        gaps.forEach(g => {
            let len = g.e - g.s;
            if (len > longest.len) {
                longest = {
                    s: g.s,
                    e: g.e,
                    len: len
                };
            }
        });

        // Truncate to 24 if wrapped
        if (longest.e > 24) longest.e = 24;

        return {
            start: offsetToTime(longest.s),
            end: offsetToTime(longest.e)
        };
    }

    function updateTimelines() {
        const bgColors = ['#0d6efd', '#198754', '#dc3545', '#ffc107', '#0dcaf0', '#6f42c1', '#fd7e14'];

        $('.room-row').each(function() {
            const row = $(this);
            const timelineBg = row.find('.timeline-bg');
            if (timelineBg.length === 0) return;

            timelineBg.find('.timeline-segment').remove();

            let colorIndex = 0;
            row.find('.assignment-item').each(function() {
                const itemRow = $(this);
                const startStr = itemRow.find('.start-time-input').val();
                const endStr = itemRow.find('.end-time-input').val();

                if (startStr && endStr) {
                    let startOff = timeToOffset(startStr);
                    let endOff = timeToOffset(endStr);

                    if (endOff === 0 || endOff <= startOff) {
                        endOff += 24.0;
                    }

                    const dur = Math.min(24.0, endOff) - startOff;
                    if (dur > 0 && startOff < 24.0) {
                        const leftPct = (startOff / 24.0 * 100);
                        const widthPct = (dur / 24.0 * 100);

                        const color = bgColors[colorIndex % bgColors.length];
                        itemRow.css('border-left', `4px solid ${color}`);

                        const $seg = $(`<div class="timeline-segment">
                            <div class="resize-handle handle-start" style="position:absolute; left:0; top:0; width:6px; height:100%; cursor:col-resize; z-index:10;"></div>
                            <div class="resize-handle handle-end" style="position:absolute; right:0; top:0; width:6px; height:100%; cursor:col-resize; z-index:10;"></div>
                        </div>`).css({
                            position: 'absolute',
                            top: 0,
                            left: leftPct + '%',
                            width: widthPct + '%',
                            height: '100%',
                            background: color,
                            opacity: 0.8,
                            borderRadius: '3px',
                            boxShadow: '0 1px 2px rgba(0,0,0,0.2)'
                        }).attr('title', startStr + ' - ' + endStr);

                        // Lưu tham chiếu đến hàng ca để kéo
                        $seg.data('target-row', itemRow);

                        timelineBg.append($seg);
                        colorIndex++;
                    } else {
                        itemRow.css('border-left', 'none');
                    }
                } else {
                    itemRow.css('border-left', 'none');
                }
            });
        });
    }

    let isResizing = false;
    let currentHandle = null;
    let currentSeg = null;
    let currentTargetRow = null;
    let startX, startLeft, startWidth, containerWidth;

    $(document).on('mousedown', '.resize-handle', function(e) {
        e.preventDefault();
        isResizing = true;
        currentHandle = $(this);
        currentSeg = currentHandle.closest('.timeline-segment');
        currentTargetRow = currentSeg.data('target-row');

        const container = currentSeg.parent();
        containerWidth = container.width();

        startX = e.pageX;
        startLeft = parseFloat(currentSeg[0].style.left);
        startWidth = parseFloat(currentSeg[0].style.width);

        $(document).on('mousemove.resizing', function(em) {
            if (!isResizing) return;

            let deltaPx = em.pageX - startX;
            let deltaPct = (deltaPx / containerWidth) * 100;

            if (currentHandle.hasClass('handle-start')) {
                let newLeft = startLeft + deltaPct;
                let newWidth = startWidth - deltaPct;
                if (newWidth > 1) { // Min width 1%
                    currentSeg.css({
                        left: newLeft + '%',
                        width: newWidth + '%'
                    });

                    // Cập nhật input
                    let startOffset = (newLeft / 100) * 24.0;
                    currentTargetRow.find('.start-time-input').val(offsetToTime(startOffset));
                }
            } else {
                let newWidth = startWidth + deltaPct;
                if (newWidth > 1) {
                    currentSeg.css({
                        width: newWidth + '%'
                    });

                    // Cập nhật input
                    let endOffset = ((startLeft + newWidth) / 100) * 24.0;
                    currentTargetRow.find('.end-time-input').val(offsetToTime(endOffset));
                }
            }
        });

        $(document).on('mouseup.resizing', function() {
            if (isResizing) {
                isResizing = false;
                $(document).off('.resizing');
                updateTimelines(); // Vẽ lại chuẩn
                if (currentTargetRow) {
                    markRoomDirty(currentTargetRow.closest('.room-row'));
                }
                updateSidebarPersonnelTimes();
            }
        });
    });

    $(document).ready(function() {
        const productionCode = "{{ $production_code }}";
        const globalPersonnelOptions = @json(
            $personnel->map(function ($p) use ($skills) {
                    $skillStr = $skills[$p->id]->allowed_rooms_with_levels ?? '';
                    return ['id' => $p->id, 'text' => $p->name, 'skills' => $skillStr];
                })->values());



        // Theo dõi thay đổi trong các input/select/div
        $(document).on('change input', '.room-row select, .room-row input, .room-row .job-desc', function() {
            markRoomDirty($(this).closest('.room-row'));
        });

        $(document).on('change', '.person-select', function() {
            if (!isProgrammaticChange) {
                $(this).attr('data-op-type', 'thủ công');
            }
            markRoomDirty($(this).closest('.room-row'));
            validateProfRequirement($(this).closest('.assignment-item'));
            validateShiftDiscrepancy($(this).closest('.assignment-item'));
            updateSidebarPersonnelTimes();
        });

        $(document).on('focus', '.start-time-input, .end-time-input', function() {
            $(this).data('prev-val', $(this).val());
        });

        $(document).on('change', '.start-time-input, .end-time-input', function() {
            const $el = $(this);
            $el.data('prev-val', $el.val());
            updateTimelines();
            updateSidebarPersonnelTimes();
        });

        function initSelect2(selector = '.person-select', roomId = null) {
            if (!roomId) {
                // Thử đọc roomId từ .room-row gần nhất khi gọi cho nhóm
            }
            $(selector).each(function() {
                let $this = $(this);
                const rid = roomId || $this.closest('.room-row').attr('data-room-id') || null;
                if (!$this.hasClass("select2-hidden-accessible")) {
                    $this.select2({
                        placeholder: "-- Chọn người --",
                        allowClear: true,
                        width: '100%',
                        data: globalPersonnelOptions,
                        templateResult: function(option) {
                            if (!option.id) return option.text;
                            const r = rid || $this.closest('.room-row').attr(
                                'data-room-id');
                            let level = 0;
                            if (r && option.skills) {
                                const pairs = (option.skills + '').split('|');
                                for (const pair of pairs) {
                                    const parts = pair.split(':');
                                    if (parts[0] == r) {
                                        level = parseInt(parts[1] || 0);
                                        break;
                                    }
                                }
                            }
                            const badge = level > 0 ?
                                `<span class="badge badge-${level >= 3 ? 'success' : (level == 2 ? 'warning' : 'secondary')}" style="font-size:0.7rem;min-width:26px;">B${level}</span> ` :
                                `<span class="badge badge-light" style="font-size:0.7rem;min-width:26px;border:1px solid #ccc;">-</span> `;
                            return $(badge + $('<span>').text(option.text).prop(
                                'outerHTML'));
                        },
                        templateSelection: function(option) {
                            if (!option.id) return option.text;
                            const r = rid || $this.closest('.room-row').attr(
                                'data-room-id');
                            let level = 0;
                            if (r && option.skills) {
                                const pairs = (option.skills + '').split('|');
                                for (const pair of pairs) {
                                    const parts = pair.split(':');
                                    if (parts[0] == r) {
                                        level = parseInt(parts[1] || 0);
                                        break;
                                    }
                                }
                            }
                            const badge = level > 0 ?
                                `<span class="badge badge-${level >= 3 ? 'success' : (level == 2 ? 'warning' : 'secondary')}" style="font-size:0.7rem;min-width:22px;">B${level}</span> ` :
                                '';
                            return $(badge + $('<span>').text(option.text).prop(
                                'outerHTML'));
                        }
                    });

                    let selected = $this.data('selected');
                    if (selected) {
                        $this.val(selected).trigger('change.select2');
                    }
                }
            });
        }

        initSelect2();

        // Initial validation of professional requirements for all assignment items
        $('.assignment-item').each(function() {
            validateProfRequirement($(this));
        });
        updateSidebarPersonnelTimes();

        $(document).on('change', '.shift-select', function() {
            const shift = $(this).val();
            const row = $(this).closest('.assignment-item');
            const startInput = row.find('.start-time-input');
            const endInput = row.find('.end-time-input');

            switch (shift) {
                case '1':
                    startInput.val('06:00');
                    endInput.val('14:00');
                    break;
                case '2':
                    startInput.val('14:00');
                    endInput.val('22:00');
                    break;
                case '3':
                    startInput.val('22:00');
                    endInput.val('06:00');
                    break;
                case '4':
                    startInput.val('07:15');
                    endInput.val('16:00');
                    break;
                case '5':
                    const roomRow = $(this).closest('.room-row');
                    const gap = findLongestGap(roomRow, row);
                    startInput.val(gap.start);
                    endInput.val(gap.end);
                    break;
            }
            updateTimelines();
            validateShiftDiscrepancy(row);
            updateSidebarPersonnelTimes();
        });

        let isProgrammaticChange = false;

        function updatePersonnelLabels(container) {
            container.find('.personnel-row').each(function(index) {
                let label = String.fromCharCode(65 + index);
                let labelDiv = $(this).find('.personnel-label');
                if (labelDiv.length === 0) {
                    $(this).prepend(`<div class="personnel-label">${label}</div>`);
                } else {
                    labelDiv.text(label);
                }
            });
        }

        function addPersonRow(container, personId = null, opType = 'thủ công') {
            // Kiểm tra xem personId đã tồn tại trong container chưa
            if (personId) {
                let exists = false;
                container.find('.person-select').each(function() {
                    if ($(this).val() == personId) {
                        exists = true;
                        return false;
                    }
                });
                if (exists) return null;

                // Tìm dòng đầu tiên chưa chọn người để điền vào (Vị trí A, B, ...)
                let emptySelect = null;
                container.find('.person-select').each(function() {
                    if (!$(this).val()) {
                        emptySelect = $(this);
                        return false;
                    }
                });

                if (emptySelect) {
                    emptySelect.attr('data-op-type', opType);
                    let icon = emptySelect.siblings('.op-icon');
                    if (icon.length) {
                        if ((opType || '').toLowerCase() === 'tự động') {
                            icon.removeClass('fa-hand-paper text-secondary fa-copy text-success').addClass(
                                'fa-robot text-info').attr('title', 'Sắp tự động');
                        } else if ((opType || '').toLowerCase() === 'nhân bản') {
                            icon.removeClass('fa-hand-paper text-secondary fa-robot text-info').addClass(
                                'fa-copy text-success').attr('title', 'Nhân bản');
                        } else {
                            icon.removeClass('fa-robot text-info fa-copy text-success').addClass(
                                'fa-hand-paper text-secondary').attr('title', 'Sắp thủ công');
                        }
                    }
                    emptySelect.val(personId).trigger('change');
                    return emptySelect.closest('.personnel-row');
                }
            }


            const newPersonRow = $(`
                <div class="personnel-row d-flex flex-column p-1 border-bottom">
                    <div class="d-flex align-items-center w-100">
                        <div class="personnel-label"></div>
                    <div style="flex: 1" class="d-flex align-items-center">
                        <select class="form-control form-control-sm person-select" data-op-type="${opType}" style="width: 40%">
                            <option value="">-- Chọn người --</option>${globalPersonnelOptions}
                        </select>
                        ${(opType || '').toLowerCase() === 'tự động' 
                            ? '<i class="fas fa-robot text-info ml-1 op-icon" title="Sắp tự động" style="font-size: 0.8rem;"></i>' 
                            : ((opType || '').toLowerCase() === 'nhân bản' 
                                ? '<i class="fas fa-copy text-success ml-1 op-icon" title="Nhân bản" style="font-size: 0.8rem;"></i>'
                                : '<i class="fas fa-hand-paper text-secondary ml-1 op-icon" title="Sắp thủ công" style="font-size: 0.8rem;"></i>')}
                        <input type="text" class="form-control form-control-sm person-notif ml-1" 
                               style="width: 50%; font-size: 0.7rem; height: 28px; padding: 2px 5px;"
                               placeholder="Lưu ý...">
                    </div>
                        <i class="fas fa-times text-danger ml-1 btn-remove-person cursor-pointer"></i>
                    </div>
                    <div class="d-flex align-items-center w-100 pl-4 pr-2 mt-1 mb-1 time-slider-container">
                        <div class="time-slider flex-grow-1"></div>
                        <div class="time-display ml-3 font-weight-bold" style="font-size: 0.75rem; width: 85px; text-align: right; color: #444;"></div>
                        <input type="hidden" class="p-start-input" value="">
                        <input type="hidden" class="p-end-input" value="">
                    </div>
                </div>
            `);
            container.append(newPersonRow);
            updatePersonnelLabels(container);
            initSelect2(newPersonRow.find('.person-select'));
            initTimeSlider(newPersonRow);

            if (personId) {
                newPersonRow.find('.person-select').val(personId).trigger('change');
            }

            return newPersonRow;
        }

        // Click to select multiple personnel
        $(document).on('click', '.draggable-person', function(e) {
            if ($(e.target).closest('.custom-control, .btn-view-skills, .btn-toggle-has-assign')
                .length > 0) {
                return;
            }
            if ($(this).hasClass('person-on-leave')) return;

            $(this).toggleClass('selected-for-drag');
        });

        // Drag & Drop Handlers
        $(document).on('dragstart', '.draggable-person', function(e) {
            const $this = $(this);
            if ($this.hasClass('person-on-leave')) {
                e.preventDefault();
                return;
            }

            let $draggedItems = $('.draggable-person.selected-for-drag');
            // If dragging an item that isn't selected, just drag that one item
            if (!$this.hasClass('selected-for-drag')) {
                $draggedItems = $this;
            }

            const personsData = [];
            $draggedItems.each(function() {
                personsData.push({
                    code: $(this).data('code'),
                    name: $(this).data('name'),
                    shiftKey: $(this).data('shift-key')
                });
            });

            e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify(personsData));
            e.originalEvent.dataTransfer.effectAllowed = 'copy';
        });

        $(document).on('dragover', '.personnel-container', function(e) {
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'copy';
            $(this).addClass('drag-over');
        });

        $(document).on('dragleave', '.personnel-container', function(e) {
            $(this).removeClass('drag-over');
        });

        function getOfficialShift(personCode) {
            if (!currentSidebarData || !currentSidebarDay) return null;
            const person = currentSidebarData.find(p => (p.employeeId || p.code) == personCode);
            if (!person) return null;
            const dayKey = 'day' + currentSidebarDay;
            return (person.days && person.days[dayKey]) ? person.days[dayKey].toUpperCase() : 'HC';
        }

        function checkShiftMismatch(personId, targetShiftCode, callback) {
            // Tìm code từ id
            let personCode = null;
            for (let code in employeeCodeToId) {
                if (employeeCodeToId[code] == personId) {
                    personCode = code;
                    break;
                }
            }
            if (!personCode) {
                callback(true);
                return;
            }

            const officialShift = getOfficialShift(personCode);
            // Chuẩn hóa targetShiftCode (1, 2, 3, 4 -> C1, C2, C3, HC)
            const shiftMapping = {
                '1': 'C1',
                '2': 'C2',
                '3': 'C3',
                '4': 'HC'
            };
            const normalizedTarget = shiftMapping[targetShiftCode] || 'KHÁC';

            if (officialShift && officialShift !== normalizedTarget) {
                Swal.fire({
                    title: 'Cảnh báo lệch ca',
                    text: `Nhân viên này có lịch trực chính thức là ${officialShift}, nhưng bạn đang sắp vào ${normalizedTarget}. Bạn có muốn tiếp tục?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Đồng ý',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        callback(true);
                    } else {
                        callback(false);
                    }
                });
            } else {
                callback(true);
            }
        }

        function checkTimeOverlapForEmployee(personId, currentAssignmentItem) {
            if (!personId) return {
                overlap: false
            };

            const startStr = currentAssignmentItem.find('.start-time-input').val();
            const endStr = currentAssignmentItem.find('.end-time-input').val();
            if (!startStr || !endStr) return {
                overlap: false
            };

            const sOffset = timeToOffset(startStr);
            let eOffset = timeToOffset(endStr);
            if (eOffset <= sOffset) {
                eOffset += 24.0;
            }

            let hasOverlap = false;
            let overlapMsg = '';

            // 1. Scan DOM
            $('.room-row .assignment-item:not(.foreign-assignment)').each(function() {
                const $item = $(this);
                if ($item.is(currentAssignmentItem)) return; // Skip ourselves!

                let hasPerson = false;
                $item.find('.personnel-container .person-select').each(function() {
                    if ($(this).val() == personId.toString()) {
                        hasPerson = true;
                    }
                });

                if (hasPerson) {
                    const otherStart = $item.find('.start-time-input').val();
                    const otherEnd = $item.find('.end-time-input').val();
                    if (otherStart && otherEnd) {
                        const sOther = timeToOffset(otherStart);
                        let eOther = timeToOffset(otherEnd);
                        if (eOther <= sOther) {
                            eOther += 24.0;
                        }

                        if (sOffset < eOther && sOther < eOffset) {
                            hasOverlap = true;
                            const roomRow = $item.closest('.room-row');
                            let roomCode = 'Khác';
                            const customSelect = roomRow.find('.room-select-custom');
                            if (customSelect.length > 0) {
                                const selectedOption = customSelect.find('option:selected');
                                const selectedText = selectedOption.text().trim();
                                if (selectedText && !selectedText.startsWith('--')) {
                                    roomCode = selectedText.split('-')[0].trim();
                                }
                            } else {
                                roomCode = roomRow.find('.room-name-cell b').text().trim() || 'NA';
                            }
                            overlapMsg =
                                `Trùng lịch trên trang hiện tại: Nhân sự đã được phân công tại phòng ${roomCode} trong khoảng ${otherStart} - ${otherEnd}.`;
                            return false; // Break loop
                        }
                    }
                }
            });

            if (hasOverlap) {
                return {
                    overlap: true,
                    message: overlapMsg
                };
            }

            // 2. Scan DB assignments
            const dbList = dbAssignments[personId.toString()] || [];
            for (const dbAss of dbList) {
                const existsInDom = dbAss.assignment_id && $(
                    `.assignment-item[data-id="${dbAss.assignment_id}"]`).length > 0;
                if (existsInDom) continue;

                const sOther = timeToOffset(dbAss.start);
                let eOther = timeToOffset(dbAss.end);
                if (eOther <= sOther) {
                    eOther += 24.0;
                }

                if (sOffset < eOther && sOther < eOffset) {
                    return {
                        overlap: true,
                        message: `Trùng lịch với tổ khác: Nhân sự đã được phân công tại ${dbAss.group_name} (${dbAss.room_name}) trong khoảng ${dbAss.start} - ${dbAss.end}.`
                    };
                }
            }

            return {
                overlap: false
            };
        }

        function checkRoomAuthorization(personId, roomId, $roomRow, callback) {
            // Bỏ qua kiểm tra định mức cho "Công tác khác"
            if ($roomRow && ($roomRow.find('.room-select-custom').length > 0 || ($roomRow.attr('data-sp-id') &&
                    $roomRow.attr('data-sp-id').startsWith('EXT_')))) {
                callback(true);
                return;
            }

            if (!roomId) {
                callback(true);
                return;
            }

            const skillData = personnelSkills[personId];
            if (!skillData) {
                Swal.fire({
                    icon: 'error',
                    title: 'Không được phép',
                    text: 'Nhân sự này chưa được định mức (phân quyền) làm việc tại bất kỳ phòng nào.'
                });
                callback(false);
                return;
            }

            const pairs = skillData.split('|');
            const allowedRoomIds = pairs.map(p => p.split(':')[0]);

            if (!allowedRoomIds.includes(roomId.toString())) {
                const roomName = roomNames[roomId] || 'phòng này';
                Swal.fire({
                    icon: 'error',
                    title: 'Không được phép',
                    text: `Nhân sự này chưa được phép để làm việc tại: ${roomName}.`
                });
                callback(false);
            } else {
                callback(true);
            }
        }

        $(document).on('drop', '.personnel-container', async function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');

            const dataStr = e.originalEvent.dataTransfer.getData('text/plain');
            if (!dataStr) return;

            try {
                let persons = JSON.parse(dataStr);
                if (!Array.isArray(persons)) {
                    persons = [persons];
                }

                const $container = $(this);
                const $roomRow = $container.closest('.room-row');
                const roomId = $roomRow.attr('data-room-id');
                const targetShiftCode = $container.closest('.assignment-item').find('.shift-select')
                    .val();

                for (const person of persons) {
                    if (person.shiftKey === 'P') {
                        await Swal.fire({
                            icon: 'error',
                            title: 'Không thể sắp lịch',
                            text: `Nhân sự ${person.name} đang nghỉ phép (P), không thể sắp vào ca sản xuất.`
                        });
                        continue;
                    }

                    const personId = employeeCodeToId[person.code];
                    if (personId) {
                        // 1. Kiểm tra định mức phòng (Authorization)
                        const isAuthorized = await new Promise(resolve => {
                            checkRoomAuthorization(personId, roomId, $roomRow, resolve);
                        });
                        if (!isAuthorized) continue;

                        // 2. Kiểm tra lệch ca (Shift Mismatch)
                        const canProceed = await new Promise(resolve => {
                            checkShiftMismatch(personId, targetShiftCode, resolve);
                        });

                        if (canProceed) {
                            isProgrammaticChange = true;
                            const newRow = addPersonRow($container, personId);
                            isProgrammaticChange = false;

                            if (newRow) {
                                markRoomDirty($container.closest('.room-row'));
                            }
                        }
                    } else {
                        await Swal.fire({
                            icon: 'warning',
                            title: 'Thông báo',
                            text: `Không tìm thấy nhân sự có mã ${person.code} trong hệ thống.`
                        });
                    }
                }

                updateSidebarHighlights();
                $('.draggable-person').removeClass('selected-for-drag');

            } catch (err) {
                console.error("Drop error:", err);
            }
        });

        $(document).on('click', '.btn-add-person', function() {
            const container = $(this).closest('td').find('.personnel-container');
            addPersonRow(container);
            markRoomDirty($(this).closest('.room-row'));
            updateSidebarHighlights();
        });

        $(document).on('click', '.btn-remove-person', function() {
            const container = $(this).closest('.personnel-container');
            const item = $(this).closest('.assignment-item');
            const row = $(this).closest('.room-row');
            
            $(this).closest('.personnel-row').remove();

            if (container.find('.personnel-row').length === 0) {
                const $target = item.find('.job-desc');
                $target.empty();
                
                const planItems = row.find('.theory-cell .plan-item');
                planItems.each(function() {
                    const planStart = $(this).data('start');
                    const planHtml = $(this).find('.plan-text').parent().prop('outerHTML');
                    if ($target.find(`.plan-item[data-start="${planStart}"]`).length === 0) {
                        $target.append(planHtml);
                    }
                });
                
                const items = $target.find('.plan-item').get();
                items.sort(function(a, b) {
                    const startA = $(a).data('start');
                    const startB = $(b).data('start');
                    return startA < startB ? -1 : (startA > startB ? 1 : 0);
                });
                
                $target.empty();
                $.each(items, function(i, itm) {
                    const $itm = $(itm).clone();
                    $itm.removeClass('hover-show-btn position-relative mb-1 pb-1 border-bottom');
                    $itm.css({
                        'margin-bottom': '2px',
                        'padding-bottom': '0',
                        'border-bottom': 'none'
                    });
                    $itm.find('.time-text').remove();
                    $itm.find('.btn-copy-plan').remove();
                    $target.append($itm);
                });
            }

            updatePersonnelLabels(container);
            markRoomDirty(row);
            updateSidebarHighlights();
            validateProfRequirement(item);
            updateSidebarPersonnelTimes();
            toggleCloneShiftButton(item);
        });

        function toggleCloneShiftButton($assignmentItem) {
            let hasPerson = false;
            $assignmentItem.find('.person-select').each(function() {
                if ($(this).val() && $(this).val() !== '') {
                    hasPerson = true;
                }
            });
            if (hasPerson) {
                $assignmentItem.find('.btn-clone-shift').show();
            } else {
                $assignmentItem.find('.btn-clone-shift').hide();
            }
        }

        $(document).on('change', '.person-select, .person-notif, .off-stream-check', function() {
            const $el = $(this);
            const $roomRow = $el.closest('.room-row');
            markRoomDirty($roomRow);

            if ($el.hasClass('person-select')) {
                validateProfRequirement($el.closest('.assignment-item'));
                updateSidebarPersonnelTimes();
                const personId = $el.val();

                if (isProgrammaticChange) {
                    return;
                }

                if (personId) {
                    const roomId = $roomRow.attr('data-room-id');
                    const targetShiftCode = $el.closest('.assignment-item').find('.shift-select').val();

                    // 1. Kiểm tra định mức phòng
                    checkRoomAuthorization(personId, roomId, $roomRow, function(isAuthorized) {
                        if (!isAuthorized) {
                            isProgrammaticChange = true;
                            $el.val(null).trigger('change');
                            isProgrammaticChange = false;
                            return;
                        }
                        // 2. Kiểm tra lệch ca
                        checkShiftMismatch(personId, targetShiftCode, function(canProceed) {
                            if (!canProceed) {
                                isProgrammaticChange = true;
                                $el.val(null).trigger('change');
                                isProgrammaticChange = false;
                            }
                            if (canProceed) {
                                toggleCloneShiftButton($el.closest('.assignment-item'));
                            }
                            updateSidebarHighlights();
                        });
                    });
                } else {
                    toggleCloneShiftButton($el.closest('.assignment-item'));
                    updateSidebarHighlights();
                }
            }
        });

        $(document).on('click', '.btn-view-skills', function() {
            let personId = $(this).attr('data-id');
            if (!personId) {
                // Try to get from sidebar if clicked there
                const $parent = $(this).closest('.draggable-person');
                if ($parent.length) {
                    const code = $parent.data('code');
                    personId = employeeCodeToId[code];
                }
            }

            if (!personId) return;

            const info = personnelInfo[personId];
            const skillData = personnelSkills[personId];

            $('#skill-modal-name').text(info.name);
            $('#skill-modal-code').text('Mã NV: ' + info.code);

            let html = '';
            if (skillData) {
                const pairs = skillData.split('|');
                pairs.forEach(pair => {
                    const [roomId, level] = pair.split(':');
                    const rName = roomNames[roomId] || 'Phòng không xác định';
                    const lvlClass = 'lvl-' + level;

                    html += `
                        <tr>
                            <td class="align-middle">${rName}</td>
                            <td class="text-center align-middle">
                                <span class="badge ${lvlClass}" style="width: 40px; padding: 5px 0;">${level}</span>
                            </td>
                        </tr>
                    `;
                });
            } else {
                html =
                    '<tr><td colspan="2" class="text-center py-4 text-muted">Chưa cập nhật bậc kỹ năng.</td></tr>';
            }

            $('#skill-modal-body').html(html);
            $('#modalSkillView').modal('show');
        });

        function updateSidebarHighlights() {
            const assignedCounts = {};
            const employeeShiftMap = {};

            // Lấy ca thực tế của nhân sự từ sidebar
            $('.draggable-person').each(function() {
                const code = $(this).data('code');
                const id = employeeCodeToId[code];
                const shiftKey = $(this).data('shift-key'); // C1, C2, C3, C4, HC, P
                if (id && shiftKey) {
                    employeeShiftMap[id.toString()] = shiftKey.toUpperCase();
                }
            });

            const mapAssignedShiftToKey = function(val) {
                if (val == '1') return 'C1';
                if (val == '2') return 'C2';
                if (val == '3') return 'C3';
                if (val == '6') return 'C4';
                if (val == '4') return 'HC';
                return 'Khác';
            };

            $('.person-select').each(function() {
                const val = $(this).val();
                const $personnelRow = $(this).closest('.personnel-row');

                if (val) {
                    const idStr = val.toString();
                    assignedCounts[idStr] = (assignedCounts[idStr] || 0) + 1;

                    // Logic kiểm tra lệch ca
                    const empShift = employeeShiftMap[idStr];
                    if (empShift) {
                        const $assignmentItem = $(this).closest('.assignment-item');
                        const shiftSelectVal = $assignmentItem.find('.shift-select').val();
                        const assignedShiftKey = mapAssignedShiftToKey(shiftSelectVal);

                        if (assignedShiftKey !== 'Khác' && empShift !== assignedShiftKey) {
                            $personnelRow.addClass('shift-mismatch');
                        } else {
                            $personnelRow.removeClass('shift-mismatch');
                        }
                    } else {
                        $personnelRow.removeClass('shift-mismatch');
                    }
                } else {
                    $personnelRow.removeClass('shift-mismatch');
                }
            });

            $('.draggable-person').each(function() {
                const code = $(this).data('code');
                const id = employeeCodeToId[code];
                const $item = $(this);
                const shiftKey = $item.data('shift-key') ? $item.data('shift-key').toLowerCase() : '';

                let $badge = $item.find('.assign-count-badge');
                const count = (id && assignedCounts[id.toString()]) ? assignedCounts[id.toString()] : 0;

                if (count > 0) {
                    if ($badge.length === 0) {
                        const $rightSpan = $item.find('.float-right');
                        $badge = $(
                            `<span class="badge badge-success badge-pill assign-count-badge mr-1" style="font-size: 0.7rem; padding: 2px 5px; background-color: #28a745; color: white;" title="Số lần phân công trong ngày">${count}</span>`
                        );
                        $rightSpan.prepend($badge);
                    } else {
                        $badge.text(count).show();
                    }
                    $item.addClass('person-assigned');
                    $item.addClass('person-assigned-' + shiftKey);
                } else {
                    if ($badge.length > 0) {
                        $badge.hide();
                    }
                    $item.removeClass('person-assigned');
                    $item.removeClass(
                        'person-assigned-c1 person-assigned-c2 person-assigned-c3 person-assigned-hc person-assigned-khác'
                    );
                }
            });
        }

        function addShiftHtml(nextShift, startTime, endTime) {
            return `
                <tr class="assignment-item">
                    <td style="width: 14.3%">
                        <div class="d-flex flex-column align-items-center">
                            <select class="form-control form-control-sm shift-select mb-1">
                                <option value="1" ${nextShift == '1' ? 'selected' : ''}>1</option>
                                <option value="2" ${nextShift == '2' ? 'selected' : ''}>2</option>
                                <option value="3" ${nextShift == '3' ? 'selected' : ''}>3</option>
                                <option value="6" ${nextShift == '6' ? 'selected' : ''}>4</option>
                                <option value="4" ${nextShift == '4' ? 'selected' : ''}>HC</option>
                                <option value="5" ${nextShift == '5' ? 'selected' : ''}>Khác</option>
                            </select>
                            <input type="time" class="form-control form-control-sm start-time-input mb-1" value="${startTime}">
                            <input type="time" class="form-control form-control-sm end-time-input mb-1" value="${endTime}">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-users"></i></span>
                                </div>
                                <input type="number" class="form-control person-count-input" value="1" min="1" title="Số lượng nhân sự cần">
                            </div>
                            <div class="input-group input-group-sm mt-1" title="Nhân sự chuyên nghiệp (cấp ≥ 3) tối thiểu cần có">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" style="font-size:0.7rem;padding:2px 5px;"><i class="fas fa-star"></i></span>
                                </div>
                                <input type="number" class="form-control professional-count-input" value="1" min="0" style="font-size:0.75rem;" title="Số lượng nhân sự chuyên nghiệp (cấp ≥ 3) tối thiểu cần có" placeholder="Cấp ≥3">
                            </div>
                        </div>
                    </td>
                    <td style="width: 36.1%" class="p-0">
                        <div class="personnel-container">
                        </div>
                        <div class="text-left p-1" style="border-top: 1px dashed #eee"><a href="javascript:void(0)" class="btn-add-person"><i class="fas fa-plus-square"></i></a></div>
                    </td>
                    <td style="width: 44.4%">
                        <div class="form-control form-control-sm job-desc" contenteditable="true" style="min-height: 80px; height: auto; white-space: pre-wrap;" placeholder="Nội dung..."></div>
                    </td>
                    <td style="width: 5.3%" class="text-center">
                        <i class="fas fa-times-circle btn-remove-shift cursor-pointer text-danger mb-2" style="font-size: 1.1rem" title="Xóa ca này"></i>
                        <br/>
                        <i class="fas fa-copy btn-clone-shift cursor-pointer text-info mt-1" style="font-size: 1.1rem; display: none;" title="Nhân bản ca này"></i>
                    </td>
                </tr>
            `;
        }

        $(document).on('click', '.btn-add-shift', function() {
            const container = $(this).closest('tr').find('.assignment-container');

            // Tìm các ca hiện có trong container này
            let existingShifts = [];
            container.find('.shift-select').each(function() {
                existingShifts.push($(this).val());
            });

            let nextShift = '5'; // Mặc định là Khác
            let startTime = '07:15';
            let endTime = '16:00';

            if (existingShifts.length === 0) {
                if (productionCode === 'PXV1') {
                    nextShift = '1';
                    startTime = '06:00';
                    endTime = '14:00';
                } else {
                    nextShift = '4';
                    startTime = '07:15';
                    endTime = '16:00';
                }
            } else {
                if (!existingShifts.includes('1')) {
                    nextShift = '1';
                    startTime = '06:00';
                    endTime = '14:00';
                } else if (!existingShifts.includes('2')) {
                    nextShift = '2';
                    startTime = '14:00';
                    endTime = '22:00';
                } else if (!existingShifts.includes('3')) {
                    nextShift = '3';
                    startTime = '22:00';
                    endTime = '06:00';
                } else {
                    nextShift = '5';
                    const roomRow = $(this).closest('.room-row');
                    const gap = findLongestGap(roomRow);
                    startTime = gap.start;
                    endTime = gap.end;
                }
            }

            const personnel_options = "";

            const newRow = $(addShiftHtml(nextShift, startTime, endTime));
            container.append(newRow);

            if (newRow.find('.personnel-row').length === 0) {
                addPersonRow(newRow.find('.personnel-container'));
            }

            newRow.find('.btn-clone-shift').show();

            // Tự động điền số lượng nhân sự định mức
            newRow.find('.shift-select').trigger('change');

            initSelect2(newRow.find('.person-select'));
            updateTimelines();
            markRoomDirty($(this).closest('.room-row'));
        });

        // Tự động gợi ý số lượng nhân sự khi đổi ca
        $(document).on('change', '.shift-select', function() {
            $(this).closest('.assignment-item').find('.person-count-input').val(1);
            // Reset professional count khi đổi ca
            $(this).closest('.assignment-item').find('.professional-count-input').val(1);
        });

        // Validation: professional-count không được vượt person-count
        $(document).on('input change', '.person-count-input', function() {
            const $item = $(this).closest('.assignment-item');
            const total = parseInt($(this).val()) || 0;
            const $prof = $item.find('.professional-count-input');
            const prof = parseInt($prof.val()) || 0;
            if (prof > total) {
                $prof.val(total);
                $prof.addClass('is-invalid');
                setTimeout(() => $prof.removeClass('is-invalid'), 1500);
            }
        });

        $(document).on('input change', '.professional-count-input', function() {
            const $item = $(this).closest('.assignment-item');
            const total = parseInt($item.find('.person-count-input').val()) || 0;
            const prof = parseInt($(this).val()) || 0;
            if (prof > total) {
                $(this).val(total);
                $(this).addClass('is-invalid');
                setTimeout(() => $(this).removeClass('is-invalid'), 1500);
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: 'Số nhân sự chuyên nghiệp không thể vượt tổng nhân sự cần',
                    showConfirmButton: false,
                    timer: 2500
                });
            }
            if (prof < 0) $(this).val(0);
            validateProfRequirement($item);
        });

        $(document).on('click', '.btn-remove-shift', function() {
            const row = $(this).closest('.assignment-item');
            const assignmentId = row.data('id');
            if (assignmentId) {
                Swal.fire({
                    title: 'Xác nhận xóa?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Đồng ý',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('pages.assignment.production.destroy', ['id' => ':id']) }}"
                                .replace(':id', assignmentId),
                            method: "DELETE",
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            success: function(res) {
                                if (res.success) {
                                    row.remove();
                                    updateTimelines();
                                    updateSidebarHighlights();
                                    updateSidebarPersonnelTimes();
                                }
                            }
                        });
                    }
                });
            } else {
                const roomRow = $(this).closest('.room-row');
                const roomId = roomRow.attr('data-room-id');
                const isCustomTask = !roomId || roomId === '';

                if (row.closest('.assignment-container').find('.assignment-item').length > 1) {
                    row.remove();
                    updateTimelines();
                    markRoomDirty(roomRow);
                    updateSidebarHighlights();
                    updateSidebarPersonnelTimes();
                } else if (isCustomTask) {
                    // Nếu là công tác khác và là ca cuối cùng, cho phép hủy cả dòng
                    Swal.fire({
                        title: 'Hủy công tác này?',
                        text: "Toàn bộ thông tin dòng này sẽ bị xóa!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Đồng ý hủy',
                        cancelButtonText: 'Quay lại'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            roomRow.fadeOut(300, function() {
                                $(this).remove();
                                updateTimelines();
                                updateSidebarHighlights();
                                updateSidebarPersonnelTimes();
                            });
                        }
                    });
                }
            }
        });

        function saveRoom(row, silent = false, force = false) {
            return new Promise((resolve, reject) => {
                const btn = row.find('.btn-save-room');

                // Chỉ gửi request nếu có thay đổi hoặc được force lưu
                if (!force && !btn.hasClass('is-dirty')) {
                    return resolve(false);
                }

                const assignments = [];
                let validationError = null;
                const isOffStream = row.find('.off-stream-check').is(':checked') ? 1 : 0;
                row.find('.assignment-item:not(.foreign-assignment)').each(function(idx) {
                    const p_list = [];
                    $(this).find('.personnel-row').each(function() {
                        const pid = $(this).find('.person-select').val();
                        let pStart = $(this).find('.p-start-input').val() || '';
                        let pEnd = $(this).find('.p-end-input').val() || '';
                        if (pid) p_list.push({
                            personnel_id: pid,
                            notification: $(this).find('.person-notif').val(),
                            operation_type: $(this).find('.person-select').attr('data-op-type') || 'thủ công',
                            start: pStart,
                            end: pEnd
                        });
                    });

                    const jobDesc = $(this).find('.job-desc').html().trim();
                    const shiftName = $(this).find('.shift-select option:selected').text();

                    if (!jobDesc || jobDesc === '<br>' || jobDesc === 'Nội dung...') {
                        validationError = `Ca ${shiftName}: Vui lòng nhập nội dung công việc.`;
                        return false;
                    }
                    if (p_list.length === 0) {
                        return true;
                    }

                    assignments.push({
                        shift: $(this).find('.shift-select').val(),
                        start_time: $(this).find('.start-time-input').val(),
                        end_time: $(this).find('.end-time-input').val(),
                        job_description: jobDesc,
                        number_of_employes: $(this).find('.person-count-input').val() ||
                            0,
                        num_of_per_level_3: $(this).find('.professional-count-input')
                            .val() || 0,
                        off_stream: isOffStream,
                        personnel_list: p_list
                    });
                });

                if (validationError) {
                    if (!silent) Swal.fire('Thiếu thông tin', validationError, 'warning');
                    return resolve(false);
                }

                // Nếu không có ca nào, vẫn cho phép gửi để xóa sạch ca cũ của phòng đó
                btn.prop('disabled', true);
                const roomId = row.attr('data-room-id');
                let spId = row.attr('data-sp-id');

                // Fallback nếu thiếu cả roomId và spId (có thể do trang chưa reload)
                if (!roomId && (!spId || spId === 'undefined')) {
                    spId = 'EXT_FALLBACK_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
                    row.attr('data-sp-id', spId);
                }

                $.ajax({
                    url: "{{ route('pages.assignment.production.store') }}",
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        sp_id: spId,
                        room_id: roomId,
                        reportedDate: "{{ $reportedDate }}",
                        production_code: "{{ $production_code }}",
                        stage_groups_code: $('select[name="group_code"]').val() || row.attr(
                            'data-group-code'),
                        assignments: assignments
                    },
                    success: function(res) {
                        if (res.success) {
                            if (!silent) Swal.fire('Thành công', res.message, 'success');
                            markRoomSaved(row);
                            resolve(true);
                        } else {
                            if (!silent) Swal.fire('Lỗi', res.message, 'error');
                            resolve(false);
                        }
                    },
                    error: function(xhr) {
                        const msg = xhr.responseJSON ? xhr.responseJSON.message :
                            'Lỗi kết nối server';
                        if (!silent) Swal.fire('Lỗi', 'Không thể lưu phòng ' + row.find(
                            '.room-name-cell b').text() + ': ' + msg, 'error');
                        resolve(false);
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                    }
                });
            });
        }

        $(document).on('click', '.btn-save-room', async function() {
            if (validateAllOverlaps()) {
                const result = await Swal.fire({
                    title: 'Cảnh báo',
                    text: 'Có nhân sự đang bị phân công trùng giờ làm việc. Bạn có chắc chắn muốn lưu lại các phân công trùng giờ này không?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Có, lưu lại',
                    cancelButtonText: 'Không lưu'
                });
                if (!result.isConfirmed) return;
            }
            saveRoom($(this).closest('.room-row'));
        });

        $(document).on('click', '#btn-save-all', async function() {
            if (validateAllOverlaps()) {
                const result = await Swal.fire({
                    title: 'Cảnh báo',
                    text: 'Có nhân sự đang bị phân công trùng giờ làm việc. Bạn có chắc chắn muốn lưu toàn bộ lịch bao gồm cả các phân công trùng giờ này không?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Có, lưu toàn bộ',
                    cancelButtonText: 'Không lưu'
                });
                if (!result.isConfirmed) return;
            }
            
            const rows = $('.room-row');
            let successCount = 0;
            let totalProcessed = 0;

            Swal.fire({
                title: 'Đang lưu...',
                html: 'Vui lòng chờ trong giây lát',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const errors = [];
            for (let i = 0; i < rows.length; i++) {
                const $row = $(rows[i]);
                const roomName = $row.find('.room-name-cell b').text() || 'Công tác khác';
                const roomId = $row.attr('data-room-id');
                let spId = $row.attr('data-sp-id');

                // Fallback ID cho công việc ngoài lịch chưa có mã định danh
                if (!roomId && (!spId || spId === 'undefined')) {
                    spId = 'EXT_FB_ALL_' + Date.now() + '_' + i;
                    $row.attr('data-sp-id', spId);
                }

                if (!roomId && !spId) {
                    totalProcessed++;
                    continue;
                }

                let isValid = true;
                $row.find('.assignment-item:not(.foreign-assignment)').each(function() {
                    const jobDesc = $(this).find('.job-desc').html().trim();
                    let pCount = 0;
                    $(this).find('.person-select').each(function() {
                        if ($(this).val()) pCount++;
                    });
                    if (!jobDesc || jobDesc === '<br>' || jobDesc === 'Nội dung...' ||
                        pCount === 0) {
                        isValid = false;
                        return false; // Break loop
                    }
                });

                if (!isValid) {
                    totalProcessed++;
                    continue; // Bỏ qua không lưu
                }

                try {
                    const res = await $.ajax({
                        url: "{{ route('pages.assignment.production.store') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            sp_id: spId,
                            room_id: roomId,
                            reportedDate: "{{ $reportedDate }}",
                            production_code: "{{ $production_code }}",
                            stage_groups_code: $('select[name="group_code"]').val() || $row
                                .attr('data-group-code'),
                            assignments: getRoomAssignments($row)
                        }
                    });

                    if (res.success) {
                        successCount++;
                        markRoomSaved($row);
                    } else {
                        errors.push(`${roomName}: ${res.message}`);
                    }
                } catch (xhr) {
                    const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Lỗi kết nối';
                    errors.push(`${roomName}: ${msg}`);
                }
                totalProcessed++;
            }

            if (errors.length > 0) {
                Swal.fire({
                    title: 'Hoàn tất có lỗi',
                    html: `Đã lưu thành công ${successCount}/${rows.length} phòng.<br/><br/><b>Lỗi:</b><br/>${errors.join('<br/>')}`,
                    icon: 'warning'
                });
            } else {
                Swal.fire('Hoàn tất', `Đã lưu thành công ${successCount}/${rows.length} phòng.`,
                    'success');
            }
        });

        $(document).on('click', '#btn-print-schedule', function() {
            const url = $(this).attr('data-url');

            // Hiện thông báo đang chuẩn bị trang in
            Swal.fire({
                title: 'Đang chuẩn bị trang in...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Xóa iframe cũ nếu có
            $('#print-iframe').remove();

            // Tạo iframe ẩn
            const iframe = document.createElement('iframe');
            iframe.id = 'print-iframe';
            iframe.style.position = 'absolute';
            iframe.style.width = '0px';
            iframe.style.height = '0px';
            iframe.style.border = 'none';
            // iframe.style.display = 'none'; // DO NOT hide iframe via display:none as it blocks printing in some browsers
            iframe.src = url;

            document.body.appendChild(iframe);

            // Iframe tải xong sẽ tự gọi window.print() từ bên trong do có mã xử lý phía publicView
            // Chúng ta chỉ cần đóng Swal sau vài giây
            setTimeout(() => {
                Swal.close();
            }, 2000);
        });

        function getRoomAssignments(row) {
            const assignments = [];
            const isOffStream = row.find('.off-stream-check').is(':checked') ? 1 : 0;
            row.find('.assignment-item:not(.foreign-assignment)').each(function() {
                const p_list = [];
                $(this).find('.personnel-row').each(function() {
                    const pid = $(this).find('.person-select').val();
                    let pStart = $(this).find('.p-start-input').val() || '';
                        let pEnd = $(this).find('.p-end-input').val() || '';
                        if (pid) p_list.push({
                            personnel_id: pid,
                            notification: $(this).find('.person-notif').val(),
                            operation_type: $(this).find('.person-select').attr('data-op-type') || 'thủ công',
                            start: pStart,
                            end: pEnd
                        });
                });

                if (p_list.length === 0) return true;

                assignments.push({
                    shift: $(this).find('.shift-select').val(),
                    start_time: $(this).find('.start-time-input').val(),
                    end_time: $(this).find('.end-time-input').val(),
                    job_description: $(this).find('.job-desc').html().trim(),
                    number_of_employes: $(this).find('.person-count-input').val() || 0,
                    num_of_per_level_3: $(this).find('.professional-count-input').val() || 0,
                    off_stream: isOffStream,
                    personnel_list: p_list
                });
            });
            return assignments;
        }

        $(document).on('click focus', '.job-desc', function() {
            const roomRow = $(this).closest('.room-row');
            roomRow.find('.job-desc').removeClass('active-target');
            $(this).addClass('active-target');
        });

        $(document).on('click', '.btn-copy-plan', function() {
            const planItem = $(this).closest('.plan-item');
            const planHtml = planItem.find('.plan-text').parent().prop('outerHTML'); // Get wrapping div
            const planStart = planItem.data('start');
            const roomRow = $(this).closest('.room-row');

            // Find active target, fallback to first if none
            let targets = roomRow.find('.job-desc.active-target');
            if (targets.length === 0) {
                targets = roomRow.find('.job-desc').first();
                targets.addClass('active-target');
            }

            targets.each(function() {
                const $target = $(this);

                // 1. Kiểm tra xem mục này đã tồn tại chưa (tránh trùng lặp)
                // Chúng ta có thể dùng data-start để nhận diện
                if ($target.find(`.plan-item[data-start="${planStart}"]`).length > 0) {
                    return; // Skip if already exists
                }

                // 2. Append mới
                $target.append(planHtml);

                // 3. Sắp xếp lại các plan-item bên trong ô job-desc theo data-start
                const items = $target.find('.plan-item').get();
                items.sort(function(a, b) {
                    const startA = $(a).data('start');
                    const startB = $(b).data('start');
                    return startA < startB ? -1 : (startA > startB ? 1 : 0);
                });

                // Xóa nội dung cũ và chèn lại các mục đã sắp xếp
                $target.empty();
                $.each(items, function(i, itm) {
                    // Loại bỏ style hover/button và các margin/padding dư thừa khi đã sang bên dán
                    const $itm = $(itm).clone();
                    $itm.removeClass(
                        'hover-show-btn position-relative mb-1 pb-1 border-bottom');
                    $itm.css({
                        'margin-bottom': '2px',
                        'padding-bottom': '0',
                        'border-bottom': 'none'
                    });
                    $itm.find('.time-text').remove(); // Loại bỏ phần thời gian
                    $itm.find('.btn-copy-plan').remove();
                    $target.append($itm);
                });
            });
            markRoomDirty(roomRow);
        });

        // Chép toàn bộ Lịch Lý Thuyết
        $(document).on('click', '.btn-copy-theory-all', function() {
            const roomRow = $(this).closest('.room-row');
            const planItems = roomRow.find('.theory-cell .plan-item');

            let targets = roomRow.find('.job-desc.active-target');
            if (targets.length === 0) {
                targets = roomRow.find('.job-desc').first();
                targets.addClass('active-target');
            }

            targets.each(function() {
                const $target = $(this);

                planItems.each(function() {
                    const planStart = $(this).data('start');
                    const planHtml = $(this).find('.plan-text').parent().prop(
                        'outerHTML');

                    if ($target.find(`.plan-item[data-start="${planStart}"]`).length ===
                        0) {
                        $target.append(planHtml);
                    }
                });

                // Sắp xếp lại
                const items = $target.find('.plan-item').get();
                items.sort(function(a, b) {
                    const startA = $(a).data('start');
                    const startB = $(b).data('start');
                    return startA < startB ? -1 : (startA > startB ? 1 : 0);
                });

                // Xóa và dán lại
                $target.empty();
                $.each(items, function(i, itm) {
                    const $itm = $(itm).clone();
                    $itm.removeClass(
                        'hover-show-btn position-relative mb-1 pb-1 border-bottom');
                    $itm.css({
                        'margin-bottom': '2px',
                        'padding-bottom': '0',
                        'border-bottom': 'none'
                    });
                    $itm.find('.time-text').remove();
                    $itm.find('.btn-copy-plan').remove();
                    $target.append($itm);
                });
            });
            markRoomDirty(roomRow);
        });

        $(document).on('click', '#btn-add-custom-task', function() {
            const customSpId = 'EXT_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
            const room_options =
                `@foreach ($allRooms as $r)<option value="{{ $r->name }}">{{ $r->code }} - {{ $r->name }}</option>@endforeach`;

            const newRoomRow = $(`
                <tr class="room-row" data-sp-id="${customSpId}" data-room-id="">
                    <td class="room-name-cell">
                        <div class="mb-1 text-primary font-weight-bold" style="font-size: 11px;">Công tác khác</div>
                        <input type="text" list="room-list-options-${customSpId}" class="form-control form-control-sm room-select-custom mb-2" placeholder="-- Vị trí công tác --">
                        <datalist id="room-list-options-${customSpId}">
                            ${room_options}
                        </datalist>
                        <div class="mt-2 text-center">
                            <button class="btn btn-outline-success btn-circle btn-add-shift mb-1" title="Thêm ca làm việc">
                                <i class="fas fa-plus"></i> Thêm Ca
                            </button>
                            <br>
                            <button class="btn btn-outline-info btn-circle btn-clone-row" title="Nhân bản toàn bộ ca">
                                <i class="fas fa-copy"></i> Nhân bản
                            </button>
                        </div>
                        <div class="custom-control custom-checkbox mt-2 text-center">
                            <input type="checkbox" class="custom-control-input off-stream-check" id="os_new_${Date.now()}">
                            <label class="custom-control-label" for="os_new_${Date.now()}" style="font-size: 11px; cursor: pointer;">Không chính qui</label>
                        </div>
                    </td>
                    <td class="theory-cell text-left theory-col">
                        <div class="theory-content"><span class="text-danger font-weight-bold">NA</span></div>
                        <button class="btn-copy-theory" title="Chép sang nội dung"> >> </button>
                    </td>
                    <td colspan="4" class="p-0">
                        <table class="assignment-inner-table">
                            <tbody class="assignment-container">
                                <tr class="assignment-item" data-theory-start="07:15" data-theory-end="16:00">
                                    <td style="width: 100px">
                                        <div class="d-flex flex-column align-items-center">
                                            <select class="form-control form-control-sm shift-select mb-1">
                                                <option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="6">4</option><option value="4" selected>HC</option><option value="5">Khác</option>
                                            </select>
                                            <input type="time" class="form-control form-control-sm start-time-input mb-1" value="07:15">
                                            <input type="time" class="form-control form-control-sm end-time-input mb-1" value="16:00">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-users"></i></span>
                                                </div>
                                                <input type="number" class="form-control person-count-input" value="1" min="1" title="Số lượng nhân sự cần">
                                            </div>
                                            <div class="input-group input-group-sm mt-1" title="Nhân sự chuyên nghiệp (cấp ≥ 3) tối thiểu cần có">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text" style="font-size:0.7rem;padding:2px 5px;"><i class="fas fa-star"></i></span>
                                                </div>
                                                <input type="number" class="form-control professional-count-input" value="1" min="0" style="font-size:0.75rem;" title="Số lượng nhân sự chuyên nghiệp (cấp ≥ 3) tối thiểu cần có" placeholder="Cấp ≥3">
                                            </div>
                                        </div>
                                    </td>
                                    <td style="width: 350px" class="p-0">
                                        <div class="personnel-container">
                                            <div class="personnel-row d-flex flex-column p-1 border-bottom">
                                                <div class="d-flex align-items-center w-100">
                                                    <div class="personnel-label">A</div>
                                                    <div style="flex: 1" class="d-flex align-items-center">
                                                        <select class="form-control form-control-sm person-select" data-op-type="thủ công" style="width: 40%">
                                                            <option value="">-- Chọn người --</option>${globalPersonnelOptions}
                                                        </select>
                                                        <i class="fas fa-hand-paper text-secondary ml-1 op-icon" title="Sắp thủ công" style="font-size: 0.8rem;"></i>
                                                        <input type="text" class="form-control form-control-sm person-notif ml-1" 
                                                               style="width: 50%; font-size: 0.7rem; height: 28px; padding: 2px 5px;"
                                                               placeholder="Lưu ý...">
                                                    </div>
                                                    <i class="fas fa-times text-danger ml-1 btn-remove-person cursor-pointer"></i>
                                                </div>
                                                <div class="d-flex align-items-center w-100 pl-4 pr-2 mt-1 mb-1 time-slider-container">
                                                    <div class="time-slider flex-grow-1"></div>
                                                    <div class="time-display ml-3 font-weight-bold" style="font-size: 0.75rem; width: 85px; text-align: right; color: #444;"></div>
                                                    <input type="hidden" class="p-start-input" value="">
                                                    <input type="hidden" class="p-end-input" value="">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-left p-1" style="border-top: 1px dashed #eee">
                                            <a href="javascript:void(0)" class="btn-add-person"><i class="fas fa-plus-square"></i></a>
                                        </div>
                                    </td>
                                    <td style="width: 500px">
                                        <div class="form-control form-control-sm job-desc" contenteditable="true" style="min-height: 80px; height: auto; white-space: pre-wrap;" placeholder="Nội dung..."></div>
                                    </td>
                                    <td style="width: 60px" class="text-center">
                                        <i class="fas fa-times-circle btn-remove-shift cursor-pointer mb-2"></i><br/>
                                        <i class="fas fa-copy btn-clone-shift cursor-pointer text-info mt-1" style="font-size: 1.1rem" title="Nhân bản ca này"></i>
                                    </td>
                                </tr>
                            </tbody>
                                <tfoot class="timeline-tfoot">
                                    <tr>
                                        <td colspan="4" class="pt-2 pb-2 px-1 border-top-0">
                                            <div class="timeline-container position-relative" style="height: 6px; background: #e9ecef; border-radius: 3px; width: 100%; margin-top: 25px; margin-bottom: 5px;">
                                                <div style="position: absolute; left: 0%; top: -6px; width:1px; height:18px; border-left:1px solid #aaa;"></div>
                                                <div style="position: absolute; left: 0%; top: -18px; font-size: 10px; color:#000; font-weight: 600; transform:translateX(-50%);">06h</div>
                                                
                                                <div style="position: absolute; left: 25%; top: -4px; width:1px; height:14px; border-left:1px dashed #ccc;"></div>
                                                <div style="position: absolute; left: 25%; top: -16px; font-size: 9px; color:#000; transform:translateX(-50%);">12h</div>
                                                
                                                <div style="position: absolute; left: 50%; top: -4px; width:1px; height:14px; border-left:1px dashed #ccc;"></div>
                                                <div style="position: absolute; left: 50%; top: -16px; font-size: 9px; color:#000; transform:translateX(-50%);">18h</div>
                                                
                                                <div style="position: absolute; left: 75%; top: -4px; width:1px; height:14px; border-left:1px dashed #ccc;"></div>
                                                <div style="position: absolute; left: 75%; top: -16px; font-size: 9px; color:#000; transform:translateX(-50%);">00h</div>
                                                
                                                <div style="position: absolute; left: 100%; top: -6px; width:1px; height:18px; border-left:1px solid #aaa;"></div>
                                                <div style="position: absolute; left: 100%; top: -18px; font-size: 10px; color:#000; font-weight: 600; transform:translateX(-50%);">06h</div>

                                                <div class="timeline-bg" style="position: absolute; top:0; left:0; width: 100%; height: 100%; overflow: hidden; border-radius: 3px;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                        </table>
                    </td>
                    <td class="text-center" style="vertical-align: middle !important; width: 60px;">
                        <button class="btn btn-xs btn-warning btn-save-room shadow-sm">
                            <i class="fas fa-save"></i>
                        </button>
                    </td>
                </tr>
            `);
            $('#main-assignment-tbody').append(newRoomRow);

            Swal.fire({
                icon: 'success',
                title: 'Đã thêm hàng mới',
                text: 'Vui lòng chọn phòng sản xuất và nhập nội dung.',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });

            initSelect2(newRoomRow.find('.person-select'));

            // Tự động cuộn xuống dưới cùng
            const container = $('.table-container');
            container.animate({
                scrollTop: container[0].scrollHeight
            }, 500);
        });

        // Cập nhật data-room-id khi chọn phòng thủ công
        $(document).on('change', '.off-stream-check', function() {
            const row = $(this).closest('.room-row');
            if (this.checked) {
                row.addClass('off-stream-row');
            } else {
                row.removeClass('off-stream-row');
            }
            markRoomDirty(row);
        });

        $(document).on('change', '.room-select-custom', function() {
            $(this).closest('.room-row').attr('data-room-id', $(this).val());
            updateSidebarPersonnelTimes();
        });

        // Cập nhật thanh thời gian khi đổi giờ bắt đầu/kết thúc (chỉ cập nhật giao diện khi gõ)
        $(document).on('input', '.start-time-input, .end-time-input', function() {
            updateTimelines();
        });

        // Gọi update thanh thời gian ngay lúc load trang
        updateTimelines();

        // Ẩn/Hiện Lịch Lý Thuyết
        $(document).on('click', '#btn-toggle-theory', function() {
            $('.theory-col').toggle();
            const isVisible = $('.theory-col').is(':visible');
            if (isVisible) {
                $(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // ------------------ TỰ ĐỘNG PHÂN CÔNG ------------------
        async function autoAssign() {
            try {
                const result = await Swal.fire({
                    title: 'Tự động phân công',
                    text: 'Hệ thống sẽ tự động phân công nhân sự vào các vị trí còn trống. Các phân công thủ công hiện tại sẽ được giữ lại. Bạn có chắc chắn?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Đồng ý',
                    cancelButtonText: 'Hủy'
                });
                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'Đang xử lý...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                // Tải dữ liệu lịch trực (sidebar) nếu chưa có
                if (!isSidebarLoaded) {
                    await fetchPersonnelShiftsPromise();
                }

                // Thu thập tất cả ID nhân sự đã được sắp thủ công trên màn hình để loại trừ khỏi pool
                const allManuallyAssignedIds = new Set();
                $('.room-row .assignment-item .personnel-container .person-select').each(function() {
                    const val = $(this).val();
                    if (val) {
                        allManuallyAssignedIds.add(val.toString());
                    }
                });

                // 1. Phân loại nhân sự theo ca (loại trừ các nhân sự đã sắp thủ công)
                const pools = {
                    'C1': [],
                    'C2': [],
                    'C3': [],
                    'C4': [],
                    'HC': [],
                    'Khác': []
                };

                currentSidebarData.forEach(person => {
                    const dayKey = 'day' + currentSidebarDay;
                    const shiftCode = (person.days && person.days[dayKey]) ? person.days[dayKey]
                        .toUpperCase() : 'HC';
                    const personCode = person.employeeId || person.code || '';
                    const personId = employeeCodeToId[personCode];

                    if (!personId) return;
                    if (allowedPersonnelCodes.length > 0 && !allowedPersonnelCodes.includes(
                            personCode.toString())) return;
                    if (shiftCode === 'P') return; // Nghỉ phép
                    if (person.hasAssignment == 0) return; // Chặn tự động sắp
                    if (allManuallyAssignedIds.has(personId.toString()))
                        return; // Loại trừ nếu đã được sắp thủ công

                    let targetShift = shiftCode;
                    if (!pools[targetShift]) targetShift = 'Khác';

                    pools[targetShift].push(personId);
                });

                // 2. Thu thập danh sách các công việc (tasks) từ UI
                const tasks = [];
                $('.room-row').each(function() {
                    const $roomRow = $(this);
                    const roomId = $roomRow.attr('data-room-id');
                    if (!roomId) return;

                    const roomNameText = $roomRow.find('.room-name-cell b').text() + ' - ' +
                        $roomRow.find('.room-name-cell div').eq(1).text();

                    $roomRow.find('.assignment-item').each(function() {
                        const $item = $(this);
                        const shiftVal = $item.find('.shift-select').val();
                        let shiftKey = 'Khác';
                        if (shiftVal === '1') shiftKey = 'C1';
                        else if (shiftVal === '2') shiftKey = 'C2';
                        else if (shiftVal === '3') shiftKey = 'C3';
                        else if (shiftVal === '6') shiftKey = 'C4';
                        else if (shiftVal === '4') shiftKey = 'HC';

                        const requiredCount = parseInt($item.find('.person-count-input')
                            .val()) || 0;
                        const requiredProfCount = parseInt($item.find(
                                '.professional-count-input')
                            .val()) || 0;
                        if (requiredCount > 0) {
                            // Thu thập nhân sự đã được sắp thủ công ở ca này
                            const manuallyAssigned = [];
                            $item.find('.personnel-container .person-select').each(
                                function() {
                                    const val = $(this).val();
                                    if (val) {
                                        manuallyAssigned.push(val.toString());
                                    }
                                });

                            tasks.push({
                                roomId: roomId.toString(),
                                roomName: roomNameText,
                                shiftKey: shiftKey,
                                required: requiredCount,
                                requiredProf: requiredProfCount, // SL nhân sự cấp >= 3 tối thiểu
                                assigned: manuallyAssigned, // Khởi tạo danh sách bằng các nhân sự đã sắp
                                $item: $item
                            });
                        }
                    });
                });

                // 3. Sắp xếp Round-robin theo ca
                // Hàm lấy level của person cho room (sử dụng personnelSkills)
                function getPersonLevel(pid, roomId) {
                    const skillsStr = personnelSkills[pid] || '';
                    if (!skillsStr) return 0;
                    const pairs = skillsStr.split('|');
                    for (const pair of pairs) {
                        const parts = pair.split(':');
                        if (parts[0] === roomId.toString()) return parseInt(parts[1] || 0);
                    }
                    return 0;
                }

                // Hàm lấy mức độ ưu tiên của person cho room
                function getPersonPriority(pid, roomId) {
                    const skillsStr = personnelSkills[pid] || '';
                    if (!skillsStr) return 1;
                    const pairs = skillsStr.split('|');
                    for (const pair of pairs) {
                        const parts = pair.split(':');
                        if (parts[0] === roomId.toString()) return parseInt(parts[2] || 1);
                    }
                    return 1;
                }

                const shiftKeys = Object.keys(pools);
                shiftKeys.forEach(shiftKey => {
                    const shiftTasks = tasks.filter(t => t.shiftKey === shiftKey);
                    if (shiftTasks.length === 0) return;

                    const pool = [...pools[shiftKey]];

                    // --- GIAI ĐOẠN 0: ƯU TIÊN GÁN NHÂN SỰ KHO (WH) (MỖI PHÒNG 1 NGƯỜI) ---
                    const activeGroupCode = $('select[name="group_code"]').val() || '{!! $group_code ?? "" !!}';
                    if (activeGroupCode == '1' && productionCode === 'PXV1') {
                        // Lọc các nhân sự kho (mã hoặc tên chứa WH)
                        const whPool = pool.filter(pid => {
                            const person = currentSidebarData.find(p => (employeeCodeToId[p.employeeId || p.code] == pid));
                            const pName = person ? (person.employeeName || person.fullName || '') : '';
                            return person && ((person.employeeId || person.code || '').includes('WH') || pName.includes('WH'));
                        });

                        const whPoolMutable = [...whPool];
                        let phase0Continue = true;
                        
                        while (phase0Continue && whPoolMutable.length > 0) {
                            phase0Continue = false;
                            for (const task of shiftTasks) {
                                if (whPoolMutable.length === 0) break;
                                if (task.assigned.length >= task.required) continue;
                                
                                // Đảm bảo mỗi phòng chỉ nhận 1 nhân sự WH
                                const hasWhAssigned = task.assigned.some(aPid => whPool.includes(parseInt(aPid)));
                                if (hasWhAssigned) continue;
                                
                                let bestPid = null, bestLevel = -1, bestPriority = 9999, bestIdx = -1;
                                for (let i = 0; i < whPoolMutable.length; i++) {
                                    const pid = whPoolMutable[i];
                                    if (task.assigned.includes(pid.toString())) continue;
                                    
                                    const lv = getPersonLevel(pid, task.roomId);
                                    const prio = getPersonPriority(pid, task.roomId);
                                    if (lv > bestLevel || (lv === bestLevel && prio < bestPriority)) {
                                        bestLevel = lv;
                                        bestPriority = prio;
                                        bestPid = pid;
                                        bestIdx = i;
                                    }
                                }
                                
                                if (bestPid !== null) {
                                    task.assigned.push(bestPid.toString());
                                    whPoolMutable.splice(bestIdx, 1);
                                    
                                    const globalIdx = pool.indexOf(bestPid);
                                    if (globalIdx >= 0) pool.splice(globalIdx, 1);
                                    
                                    phase0Continue = true;
                                }
                            }
                        }
                    }

                    // --- GIAI ĐOẠN 1: ƯU TIÊN XUẤT NHÂN SỰ CẤP >= 3 CHO CÁC PHÒNG CÓ YÊU CẦU ---
                    // Xây dựng danh sách tất cả nhân sự >= level 3 trong pool
                    const profPool = pool.filter(pid => {
                        return shiftTasks.some(t => getPersonLevel(pid, t.roomId) >= 3);
                    });

                    // Theo dõi số nhân sự cấp >= 3 đã gán cho mỗi task
                    shiftTasks.forEach(t => {
                        t.assignedProfCount = 0;
                    });

                    // Phase 1a: Round-robin nhân sự cấp >= 3 vào các phòng có requiredProf > 0, chia đều
                    let phase1Continue = true;
                    const profPoolMutable = [...profPool];
                    while (phase1Continue && profPoolMutable.length > 0) {
                        phase1Continue = false;
                        for (const task of shiftTasks) {
                            if (task.requiredProf <= 0) continue;
                            if (task.assignedProfCount >= task.requiredProf) continue;
                            if (task.assigned.length >= task.required) continue;

                            let bestPid = null,
                                bestLevel = -1,
                                bestPriority = 9999,
                                bestIdx = -1;
                            for (let i = 0; i < profPoolMutable.length; i++) {
                                const pid = profPoolMutable[i];
                                if (task.assigned.includes(pid.toString())) continue;
                                const lv = getPersonLevel(pid, task.roomId);
                                const prio = getPersonPriority(pid, task.roomId);
                                if (lv >= 3 && (lv > bestLevel || (lv === bestLevel && prio < bestPriority))) {
                                    bestLevel = lv;
                                    bestPriority = prio;
                                    bestPid = pid;
                                    bestIdx = i;
                                }
                            }
                            if (bestPid !== null) {
                                task.assigned.push(bestPid.toString());
                                task.assignedProfCount++;
                                // Xóa khỏi cả hai pool (global & prof)
                                profPoolMutable.splice(bestIdx, 1);
                                const globalIdx = pool.indexOf(bestPid);
                                if (globalIdx >= 0) pool.splice(globalIdx, 1);
                                phase1Continue = true;
                            }
                        }
                    }

                    // --- GIAI ĐOẠN 2: ROUND-ROBIN BÌNH THƯỜNG CHO CÁC VỊ TRÍ CÒN LẠI ---
                    let hasMoreNeeds = true;
                    while (hasMoreNeeds && pool.length > 0) {
                        hasMoreNeeds = false;
                        let progressMadeInThisRound = false;

                        for (const task of shiftTasks) {
                            if (task.assigned.length < task.required) {
                                hasMoreNeeds = true;

                                let bestPersonId = null;
                                let bestLevel = -1;
                                let bestPriority = 9999;
                                let bestIndex = -1;

                                for (let i = 0; i < pool.length; i++) {
                                    const pid = pool[i];
                                    const level = getPersonLevel(pid, task.roomId);
                                    const prio = getPersonPriority(pid, task.roomId);

                                    if (level > 0 && (level > bestLevel || (level === bestLevel && prio < bestPriority))) {
                                        bestLevel = level;
                                        bestPriority = prio;
                                        bestPersonId = pid;
                                        bestIndex = i;
                                    }
                                }

                                if (bestPersonId) {
                                    task.assigned.push(bestPersonId);
                                    pool.splice(bestIndex, 1);
                                    progressMadeInThisRound = true;
                                }
                            }
                        }

                        if (!progressMadeInThisRound) {
                            break;
                        }
                    }
                });

                // 4. Cập nhật UI
                isProgrammaticChange = true;
                tasks.forEach(task => {
                    const $item = task.$item;
                    const $container = $item.find('.personnel-container');

                    // Lưu trữ ghi chú (notes) của các nhân sự đã sắp
                    const notesMap = {};
                    $container.find('.personnel-row').each(function() {
                        const val = $(this).find('.person-select').val();
                        const note = $(this).find('.person-notif').val();
                        if (val) {
                            notesMap[val.toString()] = note;
                        }
                    });

                    // Clear current 
                    $container.empty();

                    if (task.assigned.length === 0) {
                        addPersonRow($container);
                    } else {
                        task.assigned.forEach(pid => {
                            const newRow = addPersonRow($container, pid, 'tự động');
                            if (newRow) {
                                if (notesMap[pid]) {
                                    newRow.find('.person-notif').val(notesMap[pid]);
                                }
                            }
                        });
                    }
                    markRoomDirty($item.closest('.room-row'));
                    validateProfRequirement($item);
                });
                isProgrammaticChange = false;

                updateSidebarHighlights();
                updateSidebarPersonnelTimes();

                // 5. Hiển thị báo cáo
                showAssignmentReport();

            } catch (err) {
                console.error(err);
                Swal.fire('Lỗi', 'Đã xảy ra lỗi trong quá trình tự động phân công', 'error');
                isProgrammaticChange = false;
            }
        }

        async function fetchPersonnelShiftsPromise() {
            return new Promise((resolve, reject) => {
                const dateStr = '{{ $reportedDate }}';
                const date = new Date(dateStr);
                const month = date.getMonth() + 1;
                const year = date.getFullYear();
                const day = date.getDate();
                const depMapping = {
                    'PXV1': 15,
                    'PXV2': 31,
                    'PXVH': 30,
                    'PXDN': 30,
                    'EN': 3,
                    'PXTN': 6
                };
                const department = depMapping['{{ $production_code }}'] || 15;

                $.ajax({
                    url: `{{ route('pages.assignment.production.shifts') }}`,
                    method: 'GET',
                    data: {
                        month,
                        year,
                        department
                    },
                    success: function(res) {
                        isSidebarLoaded = true;
                        currentSidebarData = res;
                        currentSidebarDay = day;
                        renderSidebarData(res, day);
                        resolve(res);
                    },
                    error: reject
                });
            });
        }

        async function showAssignmentReport() {
            try {
                if (!isSidebarLoaded) {
                    Swal.fire({
                        title: 'Đang tải dữ liệu...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    await fetchPersonnelShiftsPromise();
                    Swal.close();
                }

                const totalPools = {
                    'C1': [],
                    'C2': [],
                    'C3': [],
                    'C4': [],
                    'HC': [],
                    'Khác': []
                };
                currentSidebarData.forEach(person => {
                    const dayKey = 'day' + currentSidebarDay;
                    const shiftCode = (person.days && person.days[dayKey]) ? person.days[dayKey]
                        .toUpperCase() : 'HC';
                    const personCode = person.employeeId || person.code || '';
                    const personId = employeeCodeToId[personCode];
                    if (!personId || shiftCode === 'P') return;
                    if (allowedPersonnelCodes.length > 0 && !allowedPersonnelCodes.includes(
                            personCode.toString())) return;

                    let targetShift = shiftCode;
                    if (!totalPools[targetShift]) targetShift = 'Khác';
                    totalPools[targetShift].push(personId);
                });

                let totalReq = 0;
                let totalAssigned = 0;
                const shiftStats = {
                    'C1': {
                        req: 0,
                        assig: 0
                    },
                    'C2': {
                        req: 0,
                        assig: 0
                    },
                    'C3': {
                        req: 0,
                        assig: 0
                    },
                    'C4': {
                        req: 0,
                        assig: 0
                    },
                    'HC': {
                        req: 0,
                        assig: 0
                    },
                    'Khác': {
                        req: 0,
                        assig: 0
                    }
                };
                const missingDetails = [];
                const assignedPersonnelIds = new Set();

                $('.room-row').each(function() {
                    const $roomRow = $(this);
                    const roomNameText = $roomRow.find('.room-name-cell b').text() + ' - ' +
                        $roomRow.find('.room-name-cell div').eq(1).text();

                    $roomRow.find('.assignment-item').each(function() {
                        const $item = $(this);
                        const shiftVal = $item.find('.shift-select').val();
                        let shiftKey = 'Khác';
                        if (shiftVal === '1') shiftKey = 'C1';
                        else if (shiftVal === '2') shiftKey = 'C2';
                        else if (shiftVal === '3') shiftKey = 'C3';
                        else if (shiftVal === '6') shiftKey = 'C4';
                        else if (shiftVal === '4') shiftKey = 'HC';

                        const req = parseInt($item.find('.person-count-input').val()) || 0;
                        let assigCount = 0;
                        $item.find('.person-select').each(function() {
                            const pid = $(this).val();
                            if (pid) {
                                assigCount++;
                                assignedPersonnelIds.add(pid);
                            }
                        });

                        totalReq += req;
                        totalAssigned += assigCount;
                        if (shiftStats[shiftKey]) {
                            shiftStats[shiftKey].req += req;
                            shiftStats[shiftKey].assig += assigCount;
                        }

                        if (assigCount < req) {
                            missingDetails.push({
                                roomName: roomNameText,
                                shiftKey: shiftKey,
                                missing: req - assigCount
                            });
                        }
                    });
                });

                const shiftKeys = Object.keys(totalPools);
                shiftKeys.forEach(k => {
                    const leftoverCount = totalPools[k].filter(pid => !assignedPersonnelIds.has(
                        pid)).length;
                    shiftStats[k].poolSize = leftoverCount;
                });

                const totalUnassigned = Object.values(shiftStats).reduce((sum, s) => sum + (s.poolSize ||
                    0), 0);

                const totalStaff = currentSidebarData.filter(p => employeeCodeToId[p.employeeId || p.code])
                    .length;

                let htmlReport = `
                <div class="container-fluid text-left">
                    <div class="row mb-3">
                        <div class="col-6 col-md-3 mb-2">
                            <div class="card bg-light">
                                <div class="card-body p-2 text-center">
                                    <small class="text-muted">Tổng yêu cầu</small>
                                    <h4 class="mb-0 text-dark">${totalReq}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-2">
                            <div class="card bg-success text-white">
                                <div class="card-body p-2 text-center">
                                    <small>Đã sắp</small>
                                    <h4 class="mb-0">${totalAssigned}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-2">
                            <div class="card ${totalReq > totalAssigned ? 'bg-danger text-white' : 'bg-info text-white'}">
                                <div class="card-body p-2 text-center">
                                    <small>${totalReq > totalAssigned ? 'Còn thiếu' : 'Tổng chưa sắp'}</small>
                                    <h4 class="mb-0">${totalReq > totalAssigned ? (totalReq - totalAssigned) : totalUnassigned}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-2">
                            <div class="card bg-info text-white">
                                <div class="card-body p-2 text-center">
                                    <small>Tỷ lệ</small>
                                    <h4 class="mb-0">${totalStaff > 0 ? Math.round((totalAssigned / totalStaff) * 100) : 0}%</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="font-weight-bold border-bottom pb-1">Chi tiết theo ca:</h6>
                    <table class="table table-sm table-bordered text-center" style="font-size: 0.85rem">
                        <thead class="bg-light">
                            <tr><th>Ca</th><th>Yêu cầu</th><th>Đã xếp</th><th>Nhân sự chưa sắp</th></tr>
                        </thead>
                        <tbody>
                `;

                shiftKeys.forEach(k => {
                    if (shiftStats[k].req > 0 || totalPools[k].length > 0) {
                        htmlReport += `
                            <tr>
                                <td><b>${k}</b></td>
                                <td>${shiftStats[k].req}</td>
                                <td class="${shiftStats[k].assig < shiftStats[k].req ? 'text-danger font-weight-bold' : 'text-success'}">${shiftStats[k].assig}</td>
                                <td>${shiftStats[k].poolSize}</td>
                            </tr>
                        `;
                    }
                });

                htmlReport += `</tbody></table>`;

                if (missingDetails.length > 0) {
                    htmlReport += `
                    <h6 class="font-weight-bold text-danger border-bottom pb-1 mt-3">Các phòng chưa xếp đủ người:</h6>
                    <ul class="text-danger small pl-3" style="max-height: 150px; overflow-y: auto;">
                    `;
                    missingDetails.forEach(m => {
                        htmlReport +=
                            `<li><b>${m.roomName}</b> (Ca ${m.shiftKey}): Thiếu ${m.missing} người</li>`;
                    });
                    htmlReport += `</ul>
                    <div class="alert alert-warning p-2 small mt-2">
                        <i class="fas fa-info-circle"></i> Nguyên nhân có thể do hết nhân sự trong ca, hoặc nhân sự rảnh rỗi chưa có định mức kỹ năng (level) tại phòng này.
                    </div>`;
                } else if (totalReq > 0) {
                    htmlReport +=
                        `<div class="alert alert-success p-2 small mt-3"><i class="fas fa-check-circle"></i> Tuyệt vời! Tất cả các phòng đã được xếp đủ số lượng yêu cầu.</div>`;
                } else {
                    htmlReport +=
                        `<div class="alert alert-secondary p-2 small mt-3"><i class="fas fa-info-circle"></i> Chưa có yêu cầu nhân sự nào được cấu hình cho các phòng (Vui lòng điền "Số lượng" > 0).</div>`;
                }

                htmlReport += `</div>`;

                Swal.fire({
                    title: 'Báo cáo Tình hình Phân công',
                    html: htmlReport,
                    width: '800px',
                    icon: 'info',
                    confirmButtonText: 'Đóng'
                });
            } catch (err) {
                console.error(err);
                Swal.fire('Lỗi', 'Không thể tạo báo cáo', 'error');
            }
        }

        $(document).on('click', '#btn-view-report', showAssignmentReport);

        $(document).on('click', '#btn-auto-assign', autoAssign);
        // --------------------------------------------------------

        // --- Xử lý Sidebar Nhân Sự ---
        const $sidebar = $('#personnel-sidebar');
        const $toggleBtn = $('#toggle-sidebar-btn');
        const $closeBtn = $('#close-sidebar-btn');
        const $container = $('#sidebar-data-container');

        let isSidebarLoaded = false;

        function toggleSidebar() {
            $sidebar.toggleClass('collapsed');
            $toggleBtn.toggleClass('active');
            const icon = $toggleBtn.find('i');
            if ($sidebar.hasClass('collapsed')) {
                icon.removeClass('fa-chevron-right').addClass('fa-chevron-left');
            } else {
                icon.removeClass('fa-chevron-left').addClass('fa-chevron-right');
                if (!isSidebarLoaded) fetchPersonnelShifts();
            }
        }

        $('#sidebar-personnel-search').on('input', function() {
            const query = $(this).val();
            renderSidebarData(currentSidebarData, currentSidebarDay, query);
        });

        $(document).on('change', '#filter-under-8h', function() {
            const query = $('#sidebar-personnel-search').val();
            renderSidebarData(currentSidebarData, currentSidebarDay, query);
        });

        $toggleBtn.on('click', toggleSidebar);
        $closeBtn.on('click', toggleSidebar);

        function fetchPersonnelShifts() {
            const dateStr = '{{ $reportedDate }}';
            const date = new Date(dateStr);
            const month = date.getMonth() + 1;
            const year = date.getFullYear();
            const day = date.getDate();
            const depMapping = {
                'PXV1': 15,
                'PXV2': 31,
                'PXVH': 30,
                'PXDN': 34,
                'EN': 3,
                'PXTN': 6
            };
            const department = depMapping[productionCode] || 15;

            $container.html(
                '<div class="text-center py-5"><div class="spinner-border text-primary"></div><div class="mt-2 text-muted">Đang tải dữ liệu...</div></div>'
            );

            $.ajax({
                url: `{{ route('pages.assignment.production.shifts') }}`,
                method: 'GET',
                data: {
                    month,
                    year,
                    department
                },
                success: function(res) {
                    isSidebarLoaded = true;
                    currentSidebarData = res;
                    currentSidebarDay = day;
                    renderSidebarData(res, day);
                    updateSidebarHighlights();
                },
                error: function() {
                    $container.html(
                        '<div class="alert alert-danger m-3">Không thể tải dữ liệu từ máy chủ API.</div>'
                    );
                }
            });
        }

        const allowedPersonnelCodes = {!! json_encode($allowedPersonnelCodes ?? []) !!};
        const isGroupFiltered = {!! isset($group_code) && $group_code != '' && $group_code != 'HC' ? 'true' : 'false' !!};

        function renderSidebarData(data, currentDay, query = '') {
            if (!data || data.length === 0) {
                $container.html('<div class="p-3 text-center text-muted">Không có dữ liệu lịch trực.</div>');
                return;
            }

            const searchStr = query.toLowerCase().trim();

            // Nhóm nhân sự theo ca của ngày hiện tại
            const shifts = {
                'C1': [],
                'C2': [],
                'C3': [],
                'C4': [],
                'HC': [],
                'P': [],
                'Khác': []
            };

            data.forEach(person => {
                const dayKey = 'day' + currentDay;
                let shiftCode = (person.days && person.days[dayKey]) ? person.days[dayKey]
                    .toUpperCase() : 'HC';

                const personName = person.employeeName || person.name || '';
                const personCode = person.employeeId || person.code || '';

                // Lọc theo tìm kiếm
                if (searchStr && !personName.toLowerCase().includes(searchStr) && !personCode
                    .toLowerCase().includes(searchStr)) {
                    return;
                }

                // Lọc theo Tổ đang chọn (nếu có)
                if (isGroupFiltered) {
                    if (!allowedPersonnelCodes.includes(personCode.toString())) {
                        return;
                    }
                }

                const personInfo = {
                    name: personName,
                    code: personCode,
                    hasAssignment: person.hasAssignment !== undefined ? person.hasAssignment : 1
                };

                if (shifts.hasOwnProperty(shiftCode)) {
                    shifts[shiftCode].push(personInfo);
                } else if (shiftCode) {
                    shifts['Khác'].push(personInfo);
                }
            });

            // Kiểm tra xem có dữ liệu sau khi lọc không
            const hasVisibleData = Object.values(shifts).some(arr => arr.length > 0);
            if (!hasVisibleData && searchStr) {
                $container.html(
                    '<div class="p-3 text-center text-muted">Không tìm thấy nhân sự phù hợp.</div>');
                return;
            }

            let html = '<div class="list-group list-group-flush">';

            const shiftLabels = {
                'C1': 'Ca 1',
                'C2': 'Ca 2',
                'C3': 'Ca 3',
                'C4': 'Ca 4',
                'HC': 'Hành chính',
                'P': 'Nghỉ phép',
                'Khác': 'Khác'
            };

            Object.keys(shifts).forEach(key => {
                if (shifts[key].length > 0) {
                    const bgClass = 'shift-' + key.toLowerCase();
                    html += `
                        <div class="list-group-item bg-light py-2 font-weight-bold d-flex align-items-center shift-header-item" data-shift-key="${key}">
                            <div class="shift-badge ${bgClass} mr-2" style="width:25px; height:25px; font-size:0.7rem">${key}</div>
                            ${shiftLabels[key]} (<span class="shift-count-badge">${shifts[key].length}</span>)
                        </div>
                    `;
                    const isLeave = key === 'P';
                    shifts[key].forEach(p => {
                        html += `
                            <div class="list-group-item py-1 pl-5 small draggable-person ${isLeave ? 'person-on-leave text-muted' : ''}" 
                                 draggable="${isLeave ? 'false' : 'true'}" 
                                 data-code="${p.code}" 
                                 data-name="${p.name}"
                                 data-has-assign="${p.hasAssignment}"
                                 data-shift-key="${key}"
                                 ${isLeave ? 'style="cursor: not-allowed; background-color: #f8f9fa;"' : ''}>
                                <div class="custom-control custom-checkbox d-inline-block mr-1" style="vertical-align: middle;">
                                    <input type="checkbox" class="custom-control-input btn-toggle-has-assign" id="ha_${p.code}" ${p.hasAssignment ? 'checked' : ''} data-code="${p.code}">
                                    <label class="custom-control-label" for="ha_${p.code}" title="Cho phép tự động sắp"></label>
                                </div>
                                <span class="${isLeave ? 'text-decoration-line-through' : 'text-dark'} ${!p.hasAssignment ? 'text-muted' : ''}">${p.name}</span>
                                <span class="text-muted float-right">
                                    ${p.code}
                                    <i class="fas fa-eye text-info btn-view-skills ml-1 cursor-pointer" title="Xem bậc kỹ năng"></i>
                                </span>
                            </div>
                        `;
                    });
                }
            });

            html += '</div>';
            $container.html(html);

            updateSidebarPersonnelTimes();

            // Handler cho nút cho phép tự động sắp
            $('.btn-toggle-has-assign').on('change', function(e) {
                e.stopPropagation();
                const code = $(this).data('code');
                const isChecked = $(this).is(':checked');

                $.ajax({
                    url: "{{ route('pages.assignment.production.update_has_assignment') }}",
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        code: code,
                        hasAssignment: isChecked ? 1 : 0
                    },
                    success: function(res) {
                        if (res.success) {
                            // Cập nhật lại data trong currentSidebarData để đồng bộ
                            const pIndex = currentSidebarData.findIndex(p => (p
                                .employeeId || p
                                .code) == code);
                            if (pIndex !== -1) {
                                currentSidebarData[pIndex].hasAssignment = isChecked ? 1 :
                                    0;
                            }
                            // Thêm hiệu ứng gạch ngang hoặc mờ nếu cần
                            $(`.draggable-person[data-code="${code}"]`).find(
                                    'span.text-dark')
                                .toggleClass('text-muted', !isChecked);
                            $(`.draggable-person[data-code="${code}"]`).attr(
                                'data-has-assign',
                                isChecked ? 1 : 0);
                        } else {
                            Swal.fire('Lỗi', res.message, 'error');
                        }
                    }
                });
            });

            // Cập nhật số lượng ở chú thích ca bên dưới
            $('#sidebar-count-c1').text(shifts['C1'].length);
            $('#sidebar-count-c2').text(shifts['C2'].length);
            $('#sidebar-count-c3').text(shifts['C3'].length);
            $('#sidebar-count-c4').text(shifts['C4'].length);
            $('#sidebar-count-hc').text(shifts['HC'].length);
            $('#sidebar-count-p').text(shifts['P'].length);

            $('.room-row .assignment-item:not(.foreign-assignment)').each(function() {
                validateShiftDiscrepancy($(this));
            });
        }

        // Tự động mở và load dữ liệu sidebar khi vào trang
        if (!$sidebar.hasClass('collapsed')) {
            const icon = $toggleBtn.find('i');
            icon.removeClass('fa-chevron-left').addClass('fa-chevron-right');
            fetchPersonnelShifts();
        }

        // --- Logic cho chức năng Clone Công Tác Khác ---
        let currentCloneTarget = null;
        let cloneTargetDates = new Set();
        const reportedDateStr = "{{ $reportedDate }}";

        // Khởi tạo Flatpickr
        const fp = flatpickr("#clone-date-input", {
            inline: true,
            mode: "multiple",
            minDate: new Date(new Date(reportedDateStr).getTime() + 86400000), // > reportedDateStr
            dateFormat: "Y-m-d",
            onChange: function(selectedDates, dateStr, instance) {
                cloneTargetDates = new Set(dateStr.split(', ').filter(d => d));
                renderCloneDates();
            }
        });

        function setupCloneModalCheckbox(roomRow) {
            const isCustom = (!roomRow.attr('data-room-id') && roomRow.attr('data-sp-id')) || roomRow.find(
                '.room-select-custom').length > 0;
            const $chk = $('#clone-as-suggestion');
            if (!isCustom) {
                $chk.prop('checked', true);
                $chk.prop('disabled', true);
            } else {
                $chk.prop('checked', false);
                $chk.prop('disabled', false);
            }
        }

        $(document).on('click', '.btn-clone-shift', function() {
            currentCloneTarget = $(this).closest('.assignment-item');
            setupCloneModalCheckbox(currentCloneTarget.closest('.room-row'));
            cloneTargetDates.clear();
            fp.clear();
            renderCloneDates();
            $('#modalCloneCustomTask').modal('show');
        });

        $(document).on('click', '.btn-clone-row', function() {
            currentCloneTarget = $(this).closest('.room-row');
            setupCloneModalCheckbox(currentCloneTarget);
            cloneTargetDates.clear();
            fp.clear();
            renderCloneDates();
            $('#modalCloneCustomTask').modal('show');
        });

        $(document).on('click', '.btn-remove-clone-date', function() {
            const dateToRemove = $(this).data('date');
            cloneTargetDates.delete(dateToRemove);
            fp.setDate(Array.from(cloneTargetDates));
            renderCloneDates();
        });

        function renderCloneDates() {
            const container = $('#clone-dates-container');
            const emptyLabel = $('#clone-dates-empty');

            container.find('.badge').remove();

            if (cloneTargetDates.size === 0) {
                emptyLabel.show();
            } else {
                emptyLabel.hide();
                const sortedDates = Array.from(cloneTargetDates).sort();
                sortedDates.forEach(dateStr => {
                    if (!dateStr) return;
                    const parts = dateStr.split('-');
                    const displayDate = parts[2] + '/' + parts[1] + '/' + parts[0];
                    const badgeHtml = `
                        <span class="badge badge-info p-2 d-flex align-items-center" style="font-size: 14px;">
                            <i class="far fa-calendar-alt mr-2"></i> ${displayDate}
                            <i class="fas fa-times ml-2 text-white cursor-pointer btn-remove-clone-date" data-date="${dateStr}"></i>
                        </span>
                    `;
                    container.append(badgeHtml);
                });
            }
        }

        $('#btn-confirm-clone').on('click', function() {
            if (cloneTargetDates.size === 0) {
                Swal.fire('Lỗi', 'Vui lòng chọn ít nhất 1 ngày để nhân bản.', 'warning');
                return;
            }

            if (!currentCloneTarget) return;

            let assignments = [];
            let roomRow = null;

            if (currentCloneTarget.hasClass('assignment-item')) {
                roomRow = currentCloneTarget.closest('.room-row');

                // Lấy dữ liệu của 1 ca hiện tại
                const p_list = [];
                currentCloneTarget.find('.personnel-row').each(function() {
                    const pid = $(this).find('.person-select').val();
                    if (pid) p_list.push({
                        personnel_id: pid,
                        notification: $(this).find('.person-notif').val(),
                        operation_type: 'nhân bản'
                    });
                });

                const jobDesc = currentCloneTarget.find('.job-desc').html().trim();
                const shiftName = currentCloneTarget.find('.shift-select option:selected').text();

                if (!jobDesc || jobDesc === '<br>' || jobDesc === 'Nội dung...') {
                    Swal.fire('Thiếu thông tin', `Ca ${shiftName}: Vui lòng nhập nội dung công việc.`,
                        'warning');
                    return;
                }
                if (p_list.length === 0) {
                    return;
                }

                const isOffStream = roomRow.find('.off-stream-check').is(':checked') ? 1 : 0;
                assignments.push({
                    shift: currentCloneTarget.find('.shift-select').val(),
                    start_time: currentCloneTarget.find('.start-time-input').val(),
                    end_time: currentCloneTarget.find('.end-time-input').val(),
                    job_description: jobDesc,
                    number_of_employes: currentCloneTarget.find('.person-count-input').val() ||
                        0,
                    num_of_per_level_3: currentCloneTarget.find('.professional-count-input')
                        .val() || 0,
                    off_stream: isOffStream,
                    personnel_list: p_list
                });
            } else if (currentCloneTarget.hasClass('room-row')) {
                roomRow = currentCloneTarget;

                let isValid = true;
                let validationError = '';
                const isOffStream = roomRow.find('.off-stream-check').is(':checked') ? 1 : 0;

                roomRow.find('.assignment-item:not(.foreign-assignment)').each(function() {
                    const jobDesc = $(this).find('.job-desc').html().trim();
                    const shiftName = $(this).find('.shift-select option:selected').text();

                    let pCount = 0;
                    const p_list = [];
                    $(this).find('.personnel-row').each(function() {
                        const pid = $(this).find('.person-select').val();
                        if (pid) {
                            pCount++;
                            p_list.push({
                                personnel_id: pid,
                                notification: $(this).find('.person-notif')
                                    .val(),
                                operation_type: 'nhân bản'
                            });
                        }
                    });

                    if (!jobDesc || jobDesc === '<br>' || jobDesc === 'Nội dung...') {
                        validationError = `Ca ${shiftName}: Vui lòng nhập nội dung công việc.`;
                        isValid = false;
                        return false;
                    }
                    if (pCount === 0) {
                        return true;
                    }

                    assignments.push({
                        shift: $(this).find('.shift-select').val(),
                        start_time: $(this).find('.start-time-input').val(),
                        end_time: $(this).find('.end-time-input').val(),
                        job_description: jobDesc,
                        number_of_employes: $(this).find('.person-count-input').val() ||
                            0,
                        num_of_per_level_3: $(this).find('.professional-count-input')
                            .val() || 0,
                        off_stream: isOffStream,
                        personnel_list: p_list
                    });
                });

                if (!isValid) {
                    Swal.fire('Thiếu thông tin', validationError, 'warning');
                    return;
                }

                if (assignments.length === 0) {
                    Swal.fire('Lỗi', 'Không có ca nào để nhân bản.', 'warning');
                    return;
                }
            }

            let spId = roomRow.attr('data-sp-id');
            const roomId = roomRow.attr('data-room-id');
            const groupCode = $('select[name="group_code"]').val() || roomRow.attr('data-group-code');

            if (!roomId && (!spId || spId === 'undefined')) {
                spId = 'EXT_FALLBACK_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
            }

            const payload = {
                _token: "{{ csrf_token() }}",
                sp_id: spId,
                room_id: roomId,
                production_code: "{{ $production_code }}",
                stage_groups_code: groupCode,
                target_dates: Array.from(cloneTargetDates),
                assignments: assignments,
                is_suggestion: $('#clone-as-suggestion').is(':checked') ? 1 : 0
            };

            const btn = $(this);
            btn.prop('disabled', true).html(
                '<i class="fas fa-spinner fa-spin mr-1"></i> Đang xử lý...');

            $.ajax({
                url: "{{ route('pages.assignment.production.clone_custom_task') }}",
                method: "POST",
                data: payload,
                success: function(res) {
                    btn.prop('disabled', false).html(
                        '<i class="fas fa-check mr-1"></i> Xác nhận Clone');
                    if (res.success) {
                        $('#modalCloneCustomTask').modal('hide');
                        Swal.fire('Thành công', res.message, 'success');
                    } else {
                        Swal.fire('Lỗi', res.message || 'Có lỗi xảy ra', 'error');
                    }
                },
                error: function(err) {
                    btn.prop('disabled', false).html(
                        '<i class="fas fa-check mr-1"></i> Xác nhận Clone');
                    const msg = err.responseJSON && err.responseJSON.message ? err
                        .responseJSON.message : 'Không thể kết nối đến máy chủ';
                    Swal.fire('Lỗi', msg, 'error');
                }
            });
        });

        function renderSuggestions() {
            if (!assignmentSuggestions || assignmentSuggestions.length === 0) return;

            assignmentSuggestions.forEach(sug => {
                let $roomRow = null;
                if (sug.room_id) {
                    $roomRow = $(`.room-row[data-room-id="${sug.room_id}"]`);
                } else if (sug.work_location) {
                    $('.room-row[data-room-id=""]').each(function() {
                        const val = $(this).find('.room-select-custom').val();
                        if (val === sug.work_location) {
                            $roomRow = $(this);
                        }
                    });
                }

                if ($roomRow && $roomRow.length > 0) {
                    const shift = sug.shift;
                    const start = sug.start_time;
                    const end = sug.end_time;
                    const pData = typeof sug.personnel_data === 'string' ? JSON.parse(sug
                        .personnel_data) : sug.personnel_data;

                    let $targetItem = null;
                    let isExistingSaved = false;

                    $roomRow.find('.assignment-item').each(function() {
                        if ($(this).find('.shift-select').val() == shift) {
                            $targetItem = $(this);
                            if ($targetItem.attr('data-id') && $targetItem.attr('data-id') !==
                                '') {
                                isExistingSaved = true;
                            }
                        }
                    });

                    // Only apply if the shift is not already officially saved
                    if (!isExistingSaved) {
                        if (!$targetItem) {
                            const $container = $roomRow.find('.assignment-container');
                            const startHM = start ? start.substring(0, 5) : '07:15';
                            const endHM = end ? end.substring(0, 5) : '16:00';
                            $container.append(addShiftHtml(shift, startHM, endHM));
                            $targetItem = $container.find('.assignment-item').last();
                            if ($targetItem.find('.personnel-row').length === 0) {
                                addPersonRow($targetItem.find('.personnel-container'));
                            }
                        }

                        if ($targetItem && pData && pData.length > 0) {
                            const $pContainer = $targetItem.find('.personnel-container');
                            $pContainer.empty();

                            isProgrammaticChange = true;
                            pData.forEach(p => {
                                const opType = p.operation_type || 'thủ công';
                                const newRow = addPersonRow($pContainer, p.personnel_id,
                                opType);
                                if (newRow && p.notification) {
                                    newRow.find('.person-notif').val(p.notification);
                                }
                            });
                            isProgrammaticChange = false;

                            $targetItem.find('.person-count-input').val(pData.length);

                            // Mark as dirty so user can save it
                            markRoomDirty($roomRow);
                            validateProfRequirement($targetItem);
                            toggleCloneShiftButton($targetItem);
                            updateSidebarHighlights();
                            updateSidebarPersonnelTimes();
                        }
                    }
                }
            });
        }

        // Execute suggestion logic on load
        renderSuggestions();

        $('.assignment-item').each(function() {
            toggleCloneShiftButton($(this));
        });

    });

        
        function initTimeSlider(row) {
            const sliderEl = row.find('.time-slider')[0];
            const displayEl = row.find('.time-display');
            
            if (!sliderEl || sliderEl.noUiSlider) return;

            let assignItem = row.closest('.assignment-item');
            if (assignItem.length === 0) assignItem = row.closest('tr');
            
            let assignStartStr = assignItem.find('.start-time-input').val() || assignItem.find('.assign-start').val() || '06:00';
            let assignEndStr = assignItem.find('.end-time-input').val() || assignItem.find('.assign-end').val() || '14:00';
            
            let shiftVal = assignItem.find('.shift-select').val() || '1';
            sliderEl.classList.add('shift-' + shiftVal + '-slider');
            
            function timeToMinutes(t) {
                if(t === undefined || t === null || t === '') return 0;
                if(typeof t === 'number') return t;
                t = String(t);
                if(t.indexOf(':') === -1) return parseFloat(t) || 0;
                let parts = t.split(':');
                let h = parseInt(parts[0], 10) || 0;
                let m = parseInt(parts[1], 10) || 0;
                return h * 60 + m;
            }
            function minutesToTime(m) {
                if (m < 0) m += 24 * 60;
                let h = Math.floor(m / 60) % 24;
                let mins = Math.floor(m % 60);
                return String(h).padStart(2, '0') + ':' + String(mins).padStart(2, '0');
            }

            let pStart = row.find('.p-start-input').val();
            let pEnd = row.find('.p-end-input').val();

            if(pStart && pStart.length > 10) pStart = pStart.substring(11, 16);
            if(pEnd && pEnd.length > 10) pEnd = pEnd.substring(11, 16);

            let shiftStart = timeToMinutes(assignStartStr);
            let shiftEnd = timeToMinutes(assignEndStr);
            if (shiftEnd <= shiftStart) shiftEnd += 24 * 60;

            let valStart = pStart ? timeToMinutes(pStart) : shiftStart;
            let valEnd = pEnd ? timeToMinutes(pEnd) : shiftEnd;

            if (valStart < shiftStart - 240 && valStart < 12 * 60) valStart += 24 * 60;
            if (valEnd < valStart) valEnd += 24 * 60;
            
            let minRange = shiftStart - 60;
            let maxRange = shiftEnd + 60;
            if (minRange > valStart) minRange = valStart - 60;
            if (maxRange < valEnd) maxRange = valEnd + 60;
            
            // Đảm bảo minRange là bội số của 15 để step luôn nhảy đúng mốc 15 phút (00, 15, 30, 45)
            minRange = Math.floor(minRange / 15) * 15;

            noUiSlider.create(sliderEl, {
                start: [valStart, valEnd],
                connect: true,
                range: {
                    'min': minRange,
                    'max': maxRange
                },
                step: 15,
                format: {
                    to: function(v) { return minutesToTime(v); },
                    from: function(v) { return timeToMinutes(v); }
                }
            });

            sliderEl.noUiSlider.on('update', function(values) {
                displayEl.text(values[0] + ' - ' + values[1]);
                row.find('.p-start-input').val(values[0]);
                row.find('.p-end-input').val(values[1]);
            });
        }

        $(document).ready(function() {
            setTimeout(() => {
                $('.personnel-row').each(function() {
                    initTimeSlider($(this));
                });
            }, 500);
        });

</script>

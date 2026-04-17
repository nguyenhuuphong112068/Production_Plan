<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    :root {
        --primary-gold: #c5c500;
        --light-gold: #fdfde0;
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
        overflow-y: auto;
        padding: 0 0 0 0;
        /* Loại bỏ padding để chạm đáy */
        background: #fff;
    }

    .table-assignment {
        border-collapse: separate;
        border-spacing: 0;
        background: #fff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        table-layout: fixed;
        /* Cố định chiều rộng cột */
        width: 100%;
    }

    .table-assignment thead th {
        position: sticky;
        top: -1px;
        /* Đảm bảo dính chặt lên mép trên của khung cuộn */
        z-index: 10;
        background-color: var(--primary-gold) !important;
        color: #000;
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
        width: 150px;
        padding: 10px !important;
    }

    .theory-cell {
        background-color: var(--light-gold);
        font-size: 0.85rem;
        padding: 10px !important;
        position: relative;
        min-width: 200px;
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

    .btn-copy-theory:hover {
        opacity: 1;
    }

    .assignment-inner-table {
        width: 100%;
        margin-bottom: 0;
        border: none;
        table-layout: fixed;
        /* Đồng bộ với bảng cha */
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

    /* Form controls */
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

    .action-bar {
        background-color: #f8f9fa;
        padding: 5px 10px;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .btn-save-room {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 3px 12px;
        font-size: 0.75rem;
        border-radius: 3px;
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
        /* Chữ màu đen */
        background-color: #f1f3f4 !important;
        border: 1px solid #ccc !important;
        margin: 2px 0 !important;
        padding: 2px 8px !important;
        font-size: 13px !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #d9534f !important;
        margin-right: 5px !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        background: transparent !important;
        color: #c9302c !important;
    }

    .btn-add-person {
        color: #007bff;
        text-decoration: none !important;
        font-weight: bold;
        font-size: 0.9rem;
        padding: 2px 5px;
        transition: color 0.2s;
    }

    .btn-add-person:hover {
        color: #0056b3;
    }

    /* CSS cho placeholder của contenteditable div */
    .job-desc:empty:before {
        content: attr(placeholder);
        color: #adb5bd;
        font-style: italic;
    }

    /* Timeline styles */
    .timeline-container {
        border: 1px solid #dee2e6;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .timeline-segment {
        border: 1px solid rgba(0, 0, 0, 0.1);
        cursor: pointer;
    }

    .resize-handle {
        background: rgba(255, 255, 255, 0.3);
    }

    .resize-handle:hover {
        background: rgba(255, 255, 255, 0.6);
    }

    .theory-cell .btn-copy-theory {
        visibility: hidden;
        opacity: 0;
        transition: opacity 0.2s, visibility 0.2s;
    }

    .theory-cell:hover .btn-copy-theory {
        visibility: visible;
        opacity: 1;
    }

    .item-locked {
        background-color: #f8f9fa;
        color: #6c757d;
    }

    .item-locked input, 
    .item-locked select, 
    .item-locked .job-desc {
        background-color: #e9ecef !important;
        cursor: not-allowed;
    }

    .item-locked-row .resize-handle {
        display: none !important;
    }

    .seg-locked {
        cursor: default !important;
        opacity: 0.5 !important;
        box-shadow: none !important;
    }

    .seg-locked .resize-handle {
        display: none !important;
    }
</style>
@php
    $reportedDateObj = \Carbon\Carbon::parse($reportedDate)->startOfDay();
    $todayObj = \Carbon\Carbon::today();
    $now = \Carbon\Carbon::now();
    $isPastDate = $reportedDateObj->lt($todayObj);
    $isToday = $reportedDateObj->eq($todayObj);

    $currentGroup = collect($stage_groups)->firstWhere('code', $group_code);
    $currentGroupName = $currentGroup ? $currentGroup->name : '';

    $user = session('user');
    $userGroup = $user['userGroup'] ?? '';
    $department = $user['department'] ?? '';
    $userGroupNameSession = $user['GroupName'] ?? '';

    $hasBasePermission = user_has_permission($user['userId'], 'maintenance_assignment', 'boolean');

    $canAccessGroup = false;
    if (str_contains($userGroup, 'Admin')) {
        $canAccessGroup = true;
    } elseif (str_contains($userGroup, 'Calibration and Maintenance Planning Manager')) {
        if ($department === 'QA') {
            $canAccessGroup = ($group_code == 17); // Chỉ được HC Thiết Bị
        } elseif ($department === 'EN') {
            $canAccessGroup = ($group_code != 17); // Được 4 nhóm trừ HC Thiết Bị
        }
    } elseif (str_contains($userGroup, 'Calibration and Maintenance Scheduler')) {
        // So sánh tên tổ (loại bỏ chữ "Tổ " để khớp với GroupName)
        $cleanCurrentName = trim(str_replace('Tổ ', '', $currentGroupName));
        $cleanUserGroupName = trim(str_replace('Tổ ', '', $userGroupNameSession));
        $canAccessGroup = ($cleanCurrentName === $cleanUserGroupName);
    }

    $hasEditPermission = $hasBasePermission && $canAccessGroup;
    $canEditReport = $hasEditPermission && !$isPastDate;
@endphp

<div class="content-wrapper">
    <div class="content-header py-2 px-3" style="margin-top: 60px;">
        <div class="d-flex justify-content-between align-items-center">
            <form action="{{ route('pages.assignment.maintenance.index') }}" method="GET" class="form-inline">
                <a href="{{ route('pages.assignment.maintenance.portal') }}" class="btn btn-sm mr-4 shadow-sm"
                    style="background: #CDC717; color: white;" title="Đổi tổ khác">
                    <i class="fas fa-chevron-left"></i>
                    {{ $currentGroup ? $currentGroup->name : 'Chọn Tổ' }}
                </a>
                <input type="hidden" name="group_code" value="{{ $group_code }}">

                <span class="mr-2 font-weight-bold">Chọn Ngày:</span>
                <input type="date" name="reportedDate" value="{{ $reportedDate }}"
                    class="form-control form-control-sm shadow-sm" style="border: 2px solid var(--primary-gold)"
                    onchange="this.form.submit()">
            </form>
            <button class="btn btn-sm btn-success shadow-sm" id="btn-add-custom-task" {{ !$canEditReport ? 'disabled' : '' }}>
                <i class="fas fa-plus"></i> Thêm công việc ngoài lịch
            </button>
        </div>
    </div>

    <div class="table-container">
        <table class="table table-assignment w-100">
            <thead>
                <tr>
                    <th style="width: 15%">Lịch Lý Thuyết</th>
                    <th style="width: 8%">Phòng / Khu Vực</th>
                    <th style="width: 8%">Ca</th>
                    <th style="width: 34%">Nội Dung Công Việc</th>
                    <th style="width: 15%">Người thực Hiện</th>
                    <th style="width: 15%">Chi Tiết Công Việc</th>
                    <th style="width: 3%">Hủy</th>
                    <th style="width: 2%" class="text-center">Lưu</th>
                </tr>
            </thead>
            <tbody id="main-assignment-tbody">
                @php $selectedGroupCode = $group_code ?? ''; @endphp
                @foreach ($tasks as $task)
                    <tr class="room-row" data-sp-id="{{ $task->sp_id }}" data-room-id="{{ $task->room_id }}"
                        data-group-code="{{ $selectedGroupCode }}">
                        <td class="theory-cell text-left" style="width: 15%">
                            <div class="theory-content">{!! $task->theory_display !!}</div>
                            <button class="btn-copy-theory" title="Chép sang nội dung"> >> </button>
                        </td>
                        <td class="room-name-cell" style="width: 8%">
                            @if ($task->sp_id)
                                <div><b>{{ $task->room_code }}</b></div>
                                <div>{{ $task->room_name }}</div>
                            @else
                                <select class="form-control form-control-sm room-select-custom">
                                    @foreach ($rooms as $r)
                                        <option value="{{ $r->id }}"
                                            {{ $r->id == $task->room_id ? 'selected' : '' }}>
                                            {{ $r->code }} - {{ $r->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                            <div class="mt-2 text-center">
                                <button class="btn btn-outline-success btn-circle btn-add-shift"
                                    title="Thêm ca làm việc" {{ !$canEditReport ? 'disabled' : '' }}>
                                    <i class="fas fa-plus"></i> Thêm Ca
                                </button>
                            </div>
                        </td>
                        <td colspan="5" class="p-0" style="width: 75%">
                            <table class="assignment-inner-table">
                                <tbody class="assignment-container">
                                    @forelse($task->assignments->sortBy('start') as $assignment)
                                        @php
                                            $itemLocked = $isPastDate || !$hasEditPermission;
                                        @endphp
                                        <tr class="assignment-item {{ $itemLocked ? 'item-locked' : '' }}" data-id="{{ $assignment->id }}"
                                            data-theory-start="{{ $task->theory_start }}"
                                            data-theory-end="{{ $task->theory_end }}">
                                            <td style="width: 10.7%">
                                                <div class="d-flex flex-column align-items-center">
                                                    <select class="form-control form-control-sm shift-select mb-1" {{ $itemLocked ? 'disabled' : '' }}>
                                                        <option value="1"
                                                            {{ $assignment->Sheet == 1 ? 'selected' : '' }}>1</option>
                                                        <option value="2"
                                                            {{ $assignment->Sheet == 2 ? 'selected' : '' }}>2</option>
                                                        <option value="3"
                                                            {{ $assignment->Sheet == 3 ? 'selected' : '' }}>3</option>
                                                        <option value="4"
                                                            {{ $assignment->Sheet == 4 ? 'selected' : '' }}>HC</option>
                                                        <option value="5"
                                                            {{ $assignment->Sheet == 5 ? 'selected' : '' }}>Khác
                                                        </option>
                                                    </select>
                                                    <input type="time"
                                                        class="form-control form-control-sm start-time-input mb-1"
                                                        value="{{ $assignment->start_time_display }}" {{ $itemLocked ? 'disabled' : '' }}>
                                                    <input type="time"
                                                        class="form-control form-control-sm end-time-input"
                                                        value="{{ $assignment->end_time_display }}" {{ $itemLocked ? 'disabled' : '' }}>
                                                </div>
                                            </td>
                                            <td style="width: 45.3%">
                                                <div class="form-control form-control-sm job-desc"
                                                    contenteditable="{{ $itemLocked ? 'false' : 'true' }}"
                                                    style="min-height: 80px; height: auto; white-space: pre-wrap; {{ $itemLocked ? 'background-color: #e9ecef;' : '' }}"
                                                    placeholder="Nội dung...">{!! $assignment->Job_description !!}</div>
                                            </td>
                                            <td colspan="2" class="p-0" style="width: 40%">
                                                <div class="personnel-container">
                                                    @foreach ($assignment->personnel_data as $p_info)
                                                        <div
                                                            class="personnel-row d-flex align-items-center p-1 border-bottom">
                                                            <div style="flex: 1" class="mr-1">
                                                                <select
                                                                    class="form-control form-control-sm person-select" {{ $itemLocked ? 'disabled' : '' }}>
                                                                    <option value="">-- Chọn người --</option>
                                                                    @foreach ($personnel as $p)
                                                                        <option value="{{ $p->id }}"
                                                                            {{ $p->id == $p_info->personnel_id ? 'selected' : '' }}>
                                                                            {{ $p->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div style="flex: 1">
                                                                <input type="text"
                                                                    class="form-control form-control-sm person-notif"
                                                                    value="{{ $p_info->notification }}"
                                                                    placeholder="Chi tiết..." {{ $itemLocked ? 'disabled' : '' }}>
                                                            </div>
                                                            @if(!$itemLocked)
                                                                <i class="fas fa-times text-danger ml-1 btn-remove-person cursor-pointer"
                                                                    title="Xóa người này"></i>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @if(!$itemLocked)
                                                    <div class="text-left p-1" style="border-top: 1px dashed #eee">
                                                        <a href="javascript:void(0)" class="btn-add-person"
                                                            title="Thêm người thực hiện">
                                                            <i class="fas fa-plus-square"></i>
                                                        </a>
                                                    </div>
                                                @endif
                                            </td>
                                            <td style="width: 4%" class="text-center">
                                                @if(!$itemLocked)
                                                    <i class="fas fa-times-circle btn-remove-shift cursor-pointer"
                                                        title="Xóa ca này"></i>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="assignment-item {{ !$canEditReport ? 'item-locked' : '' }}" data-theory-start="{{ $task->theory_start }}"
                                            data-theory-end="{{ $task->theory_end }}">
                                            <td style="width: 10.7%">
                                                <div class="d-flex flex-column align-items-center">
                                                    <select class="form-control form-control-sm shift-select mb-1" {{ !$canEditReport ? 'disabled' : '' }}>
                                                        <option value="1">1</option>
                                                        <option value="2">2</option>
                                                        <option value="3">3</option>
                                                        <option value="4" selected>HC</option>
                                                        <option value="5">Khác</option>
                                                    </select>
                                                    <input type="time"
                                                        class="form-control form-control-sm start-time-input mb-1"
                                                        value="{{ $task->theory_start }}" {{ !$canEditReport ? 'disabled' : '' }}>
                                                    <input type="time"
                                                        class="form-control form-control-sm end-time-input"
                                                        value="{{ $task->theory_end }}" {{ !$canEditReport ? 'disabled' : '' }}>
                                                </div>
                                            </td>
                                            <td style="width: 45.3%">
                                                <div class="form-control form-control-sm job-desc"
                                                    contenteditable="{{ $canEditReport ? 'true' : 'false' }}"
                                                    style="min-height: 80px; height: auto; white-space: pre-wrap; {{ !$canEditReport ? 'background-color: #e9ecef;' : '' }}"
                                                    placeholder="Nội dung..."></div>
                                            </td>
                                            <td colspan="2" class="p-0" style="width: 40%">
                                                <div class="personnel-container">
                                                    <div
                                                        class="personnel-row d-flex align-items-center p-1 border-bottom">
                                                        <div style="flex: 1" class="mr-1">
                                                            <select class="form-control form-control-sm person-select" {{ !$canEditReport ? 'disabled' : '' }}>
                                                                <option value="">-- Chọn người --</option>
                                                                @foreach ($personnel as $p)
                                                                    <option value="{{ $p->id }}">
                                                                        {{ $p->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div style="flex: 1">
                                                            <input type="text"
                                                                class="form-control form-control-sm person-notif"
                                                                placeholder="Chi tiết..." {{ !$canEditReport ? 'disabled' : '' }}>
                                                        </div>
                                                        @if($canEditReport)
                                                            <i class="fas fa-times text-danger ml-1 btn-remove-person cursor-pointer"
                                                                title="Xóa người này"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                                @if($canEditReport)
                                                    <div class="text-left p-1" style="border-top: 1px dashed #eee">
                                                        <a href="javascript:void(0)" class="btn-add-person"
                                                            title="Thêm người thực hiện">
                                                            <i class="fas fa-plus-square"></i>
                                                        </a>
                                                    </div>
                                                @endif
                                            </td>
                                            <td style="width: 4%" class="text-center">
                                                @if($canEditReport)
                                                    <i class="fas fa-times-circle btn-remove-shift cursor-pointer"
                                                        title="Xóa ca này"></i>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="timeline-tfoot">
                                    <tr>
                                        <td colspan="4" class="pt-2 pb-2 px-1 border-top-0">
                                            <div class="timeline-container position-relative"
                                                style="height: 12px; background: #e9ecef; border-radius: 6px; width: 100%; margin-top: 25px; margin-bottom: 5px;">
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
                        <td class="text-center" style="vertical-align: middle !important; width: 2%;">
                            <button class="btn btn-xs btn-primary btn-save-room shadow-sm" {{ !$canEditReport ? 'disabled' : '' }}>
                                <i class="fas fa-save"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

<script>
    function timeToOffset(str) {
        if (!str) return 0;
        let parts = str.split(':');
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

                        if (itemRow.hasClass('item-locked')) {
                            $seg.addClass('seg-locked');
                        }

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

    $(document).ready(function() {
        function initSelect2(selector = '.person-select') {
            $(selector).select2({
                placeholder: "-- Chọn người --",
                allowClear: true,
                width: '100%',
                dropdownParent: $('body')
            });
        }

        initSelect2();

        let isResizing = false;
        let currentHandle = null;
        let currentSeg = null;
        let currentTargetRow = null;
        let startX, startLeft, startWidth, containerWidth;

        $(document).on('mousedown', '.resize-handle', function(e) {
            e.preventDefault();
            currentHandle = $(this);
            currentSeg = currentHandle.closest('.timeline-segment');
            currentTargetRow = currentSeg.data('target-row');

            if (currentTargetRow.hasClass('item-locked')) {
                return;
            }

            isResizing = true;
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
                        currentTargetRow.find('.start-time-input').val(offsetToTime(
                            startOffset));
                    }
                } else {
                    let newWidth = startWidth + deltaPct;
                    if (newWidth > 1) {
                        currentSeg.css({
                            width: newWidth + '%'
                        });

                        // Cập nhật input
                        let endOffset = ((startLeft + newWidth) / 100) *
                        25.0; // Adjust for some wrap logic or just keep 24
                        let endOffsetFinal = ((startLeft + newWidth) / 100) * 24.0;
                        currentTargetRow.find('.end-time-input').val(offsetToTime(
                            endOffsetFinal));
                    }
                }
            });

            $(document).on('mouseup.resizing', function() {
                if (isResizing) {
                    isResizing = false;
                    $(document).off('.resizing');
                    updateTimelines(); // Vẽ lại chuẩn
                }
            });
        });

        $(document).on('change', '.start-time-input, .end-time-input', function() {
            updateTimelines();
        });

        // Gọi ban đầu
        updateTimelines();
        // Tự động điền giờ khi thay đổi Ca
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
                case '4': // HC
                    startInput.val('07:15');
                    endInput.val('16:00');
                    break;
                case '5': // Khác
                default:
                    break;
            }
            updateTimelines();
        });

        // Thêm nhân sự (+) trong một phân công
        $(document).on('click', '.btn-add-person', function() {
            const container = $(this).closest('td').find('.personnel-container');
            const personnel_options =
                `@foreach ($personnel as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach`;
            const newPersonRow = $(`
                <div class="personnel-row d-flex align-items-center p-1 border-bottom">
                    <div style="flex: 1" class="mr-1">
                        <select class="form-control form-control-sm person-select">
                            <option value="">-- Chọn người --</option>
                            ${personnel_options}
                        </select>
                    </div>
                    <div style="flex: 1">
                        <input type="text" class="form-control form-control-sm person-notif" placeholder="Chi tiết...">
                    </div>
                    <i class="fas fa-times text-danger ml-1 btn-remove-person cursor-pointer" title="Xóa người này"></i>
                </div>
            `);
            container.append(newPersonRow);
            initSelect2(newPersonRow.find('.person-select'));
        });

        // Xóa nhân sự (x)
        $(document).on('click', '.btn-remove-person', function() {
            const container = $(this).closest('.personnel-container');
            if (container.find('.personnel-row').length > 1) {
                $(this).closest('.personnel-row').remove();
            } else {
                $(this).closest('.personnel-row').find('select').val('');
                $(this).closest('.personnel-row').find('input').val('');
            }
        });

        // Thêm ca mới (+)
        $(document).on('click', '.btn-add-shift', function() {
            const container = $(this).closest('tr').find('.assignment-container');
            const theoryStart = $(this).closest('tr').find('.assignment-item').first().data(
                'theory-start') || '07:15';
            const theoryEnd = $(this).closest('tr').find('.assignment-item').first().data(
                'theory-end') || '16:00';
            const personnel_options =
                `@foreach ($personnel as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach`;

            const newRow = $(`
                <tr class="assignment-item">
                    <td style="width: 10.7%">
                        <div class="d-flex flex-column align-items-center">
                            <select class="form-control form-control-sm shift-select mb-1">
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">HC</option>
                                <option value="5" selected>Khác</option>
                            </select>
                            <input type="time" class="form-control form-control-sm start-time-input mb-1" value="${theoryStart}">
                            <input type="time" class="form-control form-control-sm end-time-input" value="${theoryEnd}">
                        </div>
                    </td>
                    <td style="width: 45.3%">
                        <div class="form-control form-control-sm job-desc" contenteditable="true" style="min-height: 80px; height: auto; white-space: pre-wrap;" placeholder="Nội dung..."></div>
                    </td>
                    <td colspan="2" class="p-0" style="width: 40%">
                        <div class="personnel-container">
                            <div class="personnel-row d-flex align-items-center p-1 border-bottom">
                                <div style="flex: 1" class="mr-1">
                                    <select class="form-control form-control-sm person-select">
                                        <option value="">-- Chọn người --</option>
                                        ${personnel_options}
                                    </select>
                                </div>
                                <div style="flex: 1">
                                    <input type="text" class="form-control form-control-sm person-notif" placeholder="Chi tiết...">
                                </div>
                                <i class="fas fa-times text-danger ml-1 btn-remove-person cursor-pointer"></i>
                            </div>
                        </div>
                        <div class="text-left p-1" style="border-top: 1px dashed #eee">
                            <a href="javascript:void(0)" class="btn-add-person" title="Thêm người thực hiện">
                                <i class="fas fa-plus-square"></i>
                            </a>
                        </div>
                    </td>
                    <td style="width: 4%" class="text-center">
                        <i class="fas fa-times-circle btn-remove-shift cursor-pointer"></i>
                    </td>
                </tr>
            `);

            container.append(newRow);
            initSelect2(newRow.find('.person-select'));
            updateTimelines();
        });

        // Xóa ca (x)
        $(document).on('click', '.btn-remove-shift', function() {
            const row = $(this).closest('.assignment-item');
            const assignmentId = row.data('id');
            const container = $(this).closest('.assignment-container');

            if (assignmentId) {
                Swal.fire({
                    title: 'Xác nhận xóa?',
                    text: "Ca làm việc này sẽ bị xóa khỏi hệ thống!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Đồng ý xóa',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('pages.assignment.maintenance.destroy', ['id' => ':id']) }}"
                                .replace(':id', assignmentId),
                            method: "DELETE",
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            success: function(response) {
                                if (response.success) {
                                    if (container.find('.assignment-item').length >
                                        1) {
                                        row.remove();
                                        updateTimelines();
                                    } else {
                                        row.find('select').val('');
                                        row.find('textarea, input').val('');
                                        row.removeAttr('data-id');
                                    }
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Đã xóa',
                                        text: response.message,
                                        timer: 1000,
                                        showConfirmButton: false,
                                        toast: true,
                                        position: 'top-end'
                                    });
                                } else {
                                    Swal.fire('Lỗi', response.message, 'error');
                                }
                            }
                        });
                    }
                });
            } else {
                if (container.find('.assignment-item').length > 1) {
                    row.remove();
                    updateTimelines();
                } else {
                    row.find('select').val('');
                    row.find('textarea, input').val('');
                }
            }
        });

        // Lưu từng công việc
        $(document).on('click', '.btn-save-room', function() {
            const btn = $(this);
            const row = btn.closest('.room-row');
            const spId = row.data('sp-id');
            const roomId = row.data('room-id');
            const groupCode = row.data('group-code') || '';

            const assignments = [];
            row.find('.assignment-item').each(function() {
                const personnel_list = [];
                $(this).find('.personnel-row').each(function() {
                    const pId = $(this).find('.person-select').val();
                    if (pId) {
                        personnel_list.push({
                            personnel_id: pId,
                            notification: $(this).find('.person-notif').val()
                        });
                    }
                });

                if (personnel_list.length > 0) {
                    assignments.push({
                        shift: $(this).find('.shift-select').val(),
                        start_time: $(this).find('.start-time-input').val(),
                        end_time: $(this).find('.end-time-input').val(),
                        job_description: $(this).find('.job-desc').html(),
                        personnel_list: personnel_list
                    });
                }
            });

            if (assignments.length === 0) {
                Swal.fire('Chú ý', 'Vui lòng chọn ít nhất một nhân sự để lưu', 'warning');
                return;
            }

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: "{{ route('pages.assignment.maintenance.store') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    sp_id: spId,
                    room_id: roomId,
                    reportedDate: "{{ $reportedDate }}",
                    stage_groups_code: groupCode,
                    assignments: assignments
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    } else {
                        Swal.fire('Lỗi', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Lỗi', 'Không thể kết nối máy chủ', 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fas fa-save"></i>');
                }
            });
        });

        // Tính năng Sao chép lịch lý thuyết sang nội dung công việc (>>)
        $(document).on('click', '.btn-copy-theory', function() {
            const row = $(this).closest('.room-row');
            const theoryHtml = row.find('.theory-content').html().trim();

            if (theoryHtml && theoryHtml !== '---') {
                let count = 0;
                row.find('.job-desc').each(function() {
                    if ($(this).attr('contenteditable') === 'true') {
                        $(this).html(theoryHtml);
                        count++;
                    }
                });

                if (count > 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Đã sao chép',
                        text: 'Nội dung lịch đã được đưa vào cột Công việc.',
                        timer: 1000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Không thể sao chép',
                        text: 'Dữ liệu thiết bị này đã bị khóa (ngày báo cáo trong quá khứ).',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                }
            }
        });

        // Tính năng Tự động co giãn (Không cần cho div contenteditable)

        // Thêm công việc ngoài lịch
        $(document).off('click', '#btn-add-custom-task').on('click', '#btn-add-custom-task', function() {
            const room_options =
                `@foreach ($rooms ?? [] as $r)<option value="{{ $r->id }}">{{ $r->code }} - {{ $r->name }}</option>@endforeach`;
            const personnel_options =
                `@foreach ($personnel as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach`;

            const newRoomRow = $(`
                <tr class="room-row" data-sp-id="" data-room-id="" data-group-code="{{ $group_code ?? '' }}">
                    <td class="theory-cell text-center" style="width: 15%">
                        <span class="text-danger font-weight-bold">NA</span>
                    </td>
                    <td class="room-name-cell" style="width: 8%">
                        <select class="form-control form-control-sm room-select-custom mb-2">
                            <option value="">-- Chọn phòng --</option>
                            ${room_options}
                        </select>
                        <div class="text-center">
                            <button class="btn btn-outline-success btn-circle btn-add-shift" title="Thêm ca làm việc">
                                <i class="fas fa-plus"> Thêm Ca </i>
                            </button>
                        </div>
                    </td>
                    <td colspan="5" class="p-0" style="width: 75%">
                        <table class="assignment-inner-table">
                            <tbody class="assignment-container">
                                <tr class="assignment-item" data-theory-start="07:15" data-theory-end="16:00">
                                    <td style="width: 10.7%">
                                        <div class="d-flex flex-column align-items-center">
                                            <select class="form-control form-control-sm shift-select mb-1">
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                                <option value="3">3</option>
                                                <option value="4" selected>HC</option>
                                                <option value="5">Khác</option>
                                            </select>
                                            <input type="time" class="form-control form-control-sm start-time-input mb-1" value="07:15">
                                            <input type="time" class="form-control form-control-sm end-time-input" value="16:00">
                                        </div>
                                    </td>
                                    <td style="width: 45.3%">
                                        <div class="form-control form-control-sm job-desc" contenteditable="true" style="min-height: 80px; height: auto; white-space: pre-wrap;" placeholder="Nội dung..."></div>
                                    </td>
                                    <td colspan="2" class="p-0" style="width: 40%">
                                        <div class="personnel-container">
                                            <div class="personnel-row d-flex align-items-center p-1 border-bottom">
                                                <div style="flex: 1" class="mr-1">
                                                    <select class="form-control form-control-sm person-select">
                                                        <option value="">-- Chọn người --</option>
                                                        ${personnel_options}
                                                    </select>
                                                </div>
                                                <div style="flex: 1">
                                                    <input type="text" class="form-control form-control-sm person-notif" placeholder="Chi tiết...">
                                                </div>
                                                <i class="fas fa-times text-danger ml-1 btn-remove-person cursor-pointer" title="Xóa người này"></i>
                                            </div>
                                        </div>
                                        <div class="text-left p-1" style="border-top: 1px dashed #eee">
                                            <a href="javascript:void(0)" class="btn-add-person" title="Thêm người thực hiện">
                                                <i class="fas fa-plus-square"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <td style="width: 4%" class="text-center">
                                        <i class="fas fa-times-circle btn-remove-shift cursor-pointer"></i>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="timeline-tfoot">
                                <tr>
                                    <td colspan="4" class="pt-2 pb-2 px-1 border-top-0">
                                        <div class="timeline-container position-relative" style="height: 12px; background: #e9ecef; border-radius: 6px; width: 100%; margin-top: 25px; margin-bottom: 5px;">
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
                    <td class="text-center" style="vertical-align: middle !important; width: 2%">
                        <button class="btn btn-xs btn-primary btn-save-room shadow-sm"><i class="fas fa-save"></i></button>
                    </td>
                </tr>
            `);

            $('#main-assignment-tbody').append(newRoomRow);
            initSelect2(newRoomRow.find('.person-select'));
            updateTimelines();

            // Auto resize textareas (If any left, but we use div now)
            newRoomRow.find('textarea').each(function() {
                this.style.height = (this.scrollHeight) + 'px';
            });
        });

        $(document).on('change', '.room-select-custom', function() {
            $(this).closest('.room-row').attr('data-room-id', $(this).val());
        });

    });
</script>

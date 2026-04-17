<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    :root {
        --primary-gold: #007bff;
        /* Production can use blue or gold, user said logic follow maintenance */
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
        overflow-y: auto;
        padding: 0;
        background: #fff;
    }

    .table-assignment {
        border-collapse: separate;
        border-spacing: 0;
        background: #fff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        table-layout: fixed;
        width: 100%;
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

    .job-desc {
        min-height: 80px;
        padding: 10px;
        border: 1px solid #c0daf5;
        border-radius: 4px;
        background-color: #fff;
        text-align: left;
        white-space: pre-wrap;
        font-size: 0.9rem;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .job-desc.active-target {
        border-color: #007bff;
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
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
</style>
@php
    $production_code = session('user')['production_code'];
    $reportedDateObj = \Carbon\Carbon::parse($reportedDate)->startOfDay();
    $todayObj = \Carbon\Carbon::today();
    $isPastDate = $reportedDateObj->lt($todayObj);
    $hasEditPermission = user_has_permission(session('user')['userId'], 'production_assignment', 'boolean');
    $canEdit = $hasEditPermission && !$isPastDate;
@endphp

<div class="content-wrapper">
    <div class="content-header py-2 px-3" style="margin-top: 60px;">
        <div class="d-flex justify-content-between align-items-center">
            <form action="{{ route('pages.assignment.production.index') }}" method="GET" class="form-inline">
                <span class="mr-2 font-weight-bold">Tổ:</span>
                <select name="group_code" class="form-control form-control-sm mr-4 shadow-sm"
                    style="border: 2px solid #003A4F" onchange="this.form.submit()" {{ $isLocked ? 'disabled' : '' }}>
                    <option value="">-- Tất cả --</option>
                    @foreach ($groups as $g)
                        <option value="{{ $g->group_code }}" {{ $group_code == $g->group_code ? 'selected' : '' }}>
                            {{ $g->production_group }}</option>
                    @endforeach
                    <option value="HC" {{ $group_code == 'HC' ? 'selected' : '' }}>Tổ HC Thiết Bị (QA)</option>
                </select>

                <span class="mr-2 font-weight-bold">Chọn Ngày:</span>
                <input type="date" name="reportedDate" value="{{ $reportedDate }}"
                    class="form-control form-control-sm shadow-sm" style="border: 2px solid #003A4F"
                    onchange="this.form.submit()">
            </form>
            <div class="d-flex align-items-center">
                <button class="btn btn-sm btn-success shadow-sm" id="btn-add-custom-task"
                    {{ !$canEdit ? 'disabled' : '' }}>
                    <i class="fas fa-plus"></i> Thêm công việc ngoài lịch
                </button>
                <button class="btn btn-sm btn-primary shadow-sm ml-2" id="btn-save-all"
                    {{ !$canEdit ? 'disabled' : '' }}>
                    <i class="fas fa-save"></i> Lưu toàn bộ lịch
                </button>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table class="table table-assignment w-100">
            <thead>
                <tr>
                    <th style="width: 10%">Phòng / Thiết Bị</th>
                    <th style="width: 15%">Lịch Lý Thuyết</th>
                    <th style="width: 8%">Ca</th>
                    <th style="width: 32%">Nội Dung Công Việc</th>
                    <th style="width: 15%">Người thực Hiện</th>
                    <th style="width: 15%">Chi Tiết Công Việc</th>
                    <th style="width: 3%">Hủy</th>
                    <th style="width: 2%" class="text-center">Lưu</th>
                </tr>
            </thead>
            <tbody id="main-assignment-tbody">
                @foreach ($tasks as $task)
                    <tr class="room-row" data-sp-id="{{ $task->sp_id }}" data-room-id="{{ $task->room_id }}">
                        <td class="room-name-cell">
                            <div><b>{{ $task->room_code }}</b></div>
                            <div>{{ $task->room_name }}</div>
                            <div class="mt-2 text-center">
                                <button class="btn btn-outline-success btn-circle btn-add-shift"
                                    title="Thêm ca làm việc" {{ !$canEdit ? 'disabled' : '' }}>
                                    <i class="fas fa-plus"></i> Thêm Ca
                                </button>
                            </div>
                        </td>
                        <td class="theory-cell text-left position-relative">
                            <div class="theory-content">{!! $task->theory_display !!}</div>
                            @if ($task->theory_display != '<span class="text-muted italic">Không có lịch</span>')
                                <button class="btn btn-xs btn-outline-primary btn-copy-theory-all mt-2"
                                    title="Chép toàn bộ" style="font-size: 10px; padding: 2px 6px;"
                                    {{ !$canEdit ? 'disabled' : '' }}>
                                    >>>
                                </button>
                            @endif
                        </td>
                        <td colspan="5" class="p-0">
                            <table class="assignment-inner-table">
                                <tbody class="assignment-container">
                                    @forelse($task->assignments as $assignment)
                                        <tr class="assignment-item" data-id="{{ $assignment->id }}"
                                            data-theory-start="{{ $task->theory_start }}"
                                            data-theory-end="{{ $task->theory_end }}">
                                            <td style="width: 13.3%">
                                                <div class="d-flex flex-column align-items-center">
                                                    <select class="form-control form-control-sm shift-select mb-1"
                                                        {{ !$canEdit ? 'disabled' : '' }}>
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
                                                        value="{{ $assignment->start_time_display }}"
                                                        {{ !$canEdit ? 'disabled' : '' }}>
                                                    <input type="time"
                                                        class="form-control form-control-sm end-time-input"
                                                        value="{{ $assignment->end_time_display }}"
                                                        {{ !$canEdit ? 'disabled' : '' }}>
                                                </div>
                                            </td>
                                            <td style="width: 42.7%">
                                                <div class="form-control form-control-sm job-desc"
                                                    contenteditable="{{ $canEdit ? 'true' : 'false' }}"
                                                    style="min-height: 80px; height: auto; white-space: pre-wrap;"
                                                    placeholder="Nội dung...">{!! $assignment->Job_description !!}</div>
                                            </td>
                                            <td colspan="2" class="p-0" style="width: 40%">
                                                <div class="personnel-container">
                                                    @foreach ($assignment->personnel_data as $p_info)
                                                        <div
                                                            class="personnel-row d-flex align-items-center p-1 border-bottom">
                                                            <div style="flex: 1" class="mr-1">
                                                                <select
                                                                    class="form-control form-control-sm person-select"
                                                                    {{ !$canEdit ? 'disabled' : '' }}>
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
                                                                    placeholder="Chi tiết..."
                                                                    {{ !$canEdit ? 'disabled' : '' }}>
                                                            </div>
                                                            @if ($canEdit)
                                                                <i
                                                                    class="fas fa-times text-danger ml-1 btn-remove-person cursor-pointer"></i>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @if ($canEdit)
                                                    <div class="text-left p-1" style="border-top: 1px dashed #eee">
                                                        <a href="javascript:void(0)" class="btn-add-person"><i
                                                                class="fas fa-plus-square"></i></a>
                                                    </div>
                                                @endif
                                            </td>
                                            <td style="width: 4%" class="text-center">
                                                @if ($canEdit)
                                                    <i class="fas fa-times-circle btn-remove-shift cursor-pointer"></i>
                                                @else
                                                    <i class="fas fa-lock text-muted" title="Không thể chỉnh sửa"></i>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="assignment-item" data-theory-start="{{ $task->theory_start }}"
                                            data-theory-end="{{ $task->theory_end }}">
                                            <td style="width: 10.7%">
                                                <div class="d-flex flex-column align-items-center">
                                                    <select class="form-control form-control-sm shift-select mb-1"
                                                        {{ !$canEdit ? 'disabled' : '' }}>
                                                        <option value="1"
                                                            {{ $production_code == 'PXV1' ? 'selected' : '' }}>1
                                                        </option>
                                                        <option value="2">2</option>
                                                        <option value="3">3</option>
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
                                                        class="form-control form-control-sm end-time-input"
                                                        value="{{ $production_code == 'PXV1' ? '14:00' : '16:00' }}"
                                                        {{ !$canEdit ? 'disabled' : '' }}>
                                                </div>
                                            </td>
                                            <td style="width: 42.7%">
                                                <div class="form-control form-control-sm job-desc"
                                                    contenteditable="{{ $canEdit ? 'true' : 'false' }}"
                                                    style="min-height: 80px; height: auto; white-space: pre-wrap;"
                                                    placeholder="Nội dung..."></div>
                                            </td>
                                            <td colspan="2" class="p-0" style="width: 40%">
                                                <div class="personnel-container">
                                                    <div
                                                        class="personnel-row d-flex align-items-center p-1 border-bottom">
                                                        <div style="flex: 1" class="mr-1">
                                                            <select class="form-control form-control-sm person-select"
                                                                {{ !$canEdit ? 'disabled' : '' }}>
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
                                                                placeholder="Chi tiết..."
                                                                {{ !$canEdit ? 'disabled' : '' }}>
                                                        </div>
                                                        @if ($canEdit)
                                                            <i
                                                                class="fas fa-times text-danger ml-1 btn-remove-person cursor-pointer"></i>
                                                        @endif
                                                    </div>
                                                </div>
                                                @if ($canEdit)
                                                    <div class="text-left p-1" style="border-top: 1px dashed #eee">
                                                        <a href="javascript:void(0)" class="btn-add-person"><i
                                                                class="fas fa-plus-square"></i></a>
                                                    </div>
                                                @endif
                                            </td>
                                            <td style="width: 4%" class="text-center">
                                                @if ($canEdit)
                                                    <i class="fas fa-times-circle btn-remove-shift cursor-pointer"></i>
                                                @else
                                                    <i class="fas fa-lock text-muted" title="Không thể chỉnh sửa"></i>
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
                        <td class="text-center" style="vertical-align: middle !important; width: 2%;">
                            <button class="btn btn-xs btn-primary btn-save-room shadow-sm"
                                {{ !$canEdit ? 'disabled' : '' }}>
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
            }
        });
    });

    $(document).ready(function() {
        const productionCode = "{{ $production_code }}";

        function initSelect2(selector = '.person-select') {
            $(selector).select2({
                placeholder: "-- Chọn người --",
                allowClear: true,
                width: '100%'
            });
        }

        initSelect2();

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
        });

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
                    <i class="fas fa-times text-danger ml-1 btn-remove-person cursor-pointer"></i>
                </div>
            `);
            container.append(newPersonRow);
            initSelect2(newPersonRow.find('.person-select'));
        });

        $(document).on('click', '.btn-remove-person', function() {
            const container = $(this).closest('.personnel-container');
            if (container.find('.personnel-row').length > 1) {
                $(this).closest('.personnel-row').remove();
            }
        });

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

            const personnel_options =
                `@foreach ($personnel as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach`;

            const newRow = $(`
                <tr class="assignment-item">
                    <td style="width: 13.3%">
                        <div class="d-flex flex-column align-items-center">
                            <select class="form-control form-control-sm shift-select mb-1">
                                <option value="1" ${nextShift === '1' ? 'selected' : ''}>1</option>
                                <option value="2" ${nextShift === '2' ? 'selected' : ''}>2</option>
                                <option value="3" ${nextShift === '3' ? 'selected' : ''}>3</option>
                                <option value="4" ${nextShift === '4' ? 'selected' : ''}>HC</option>
                                <option value="5" ${nextShift === '5' ? 'selected' : ''}>Khác</option>
                            </select>
                            <input type="time" class="form-control form-control-sm start-time-input mb-1" value="${startTime}">
                            <input type="time" class="form-control form-control-sm end-time-input" value="${endTime}">
                        </div>
                    </td>
                    <td style="width: 42.7%">
                        <div class="form-control form-control-sm job-desc" contenteditable="true" style="min-height: 80px; height: auto; white-space: pre-wrap;" placeholder="Nội dung..."></div>
                    </td>
                    <td colspan="2" class="p-0" style="width: 40%">
                        <div class="personnel-container">
                            <div class="personnel-row d-flex align-items-center p-1 border-bottom">
                                <div style="flex: 1" class="mr-1">
                                    <select class="form-control form-control-sm person-select">
                                        <option value="">-- Chọn người --</option>${personnel_options}
                                    </select>
                                </div>
                                <div style="flex: 1"><input type="text" class="form-control form-control-sm person-notif" placeholder="Chi tiết..."></div>
                                <i class="fas fa-times text-danger ml-1 btn-remove-person cursor-pointer"></i>
                            </div>
                        </div>
                        <div class="text-left p-1" style="border-top: 1px dashed #eee"><a href="javascript:void(0)" class="btn-add-person"><i class="fas fa-plus-square"></i></a></div>
                    </td>
                    <td style="width: 4%" class="text-center"><i class="fas fa-times-circle btn-remove-shift cursor-pointer"></i></td>
                </tr>
            `);
            container.append(newRow);
            initSelect2(newRow.find('.person-select'));
            updateTimelines();
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
                                }
                            }
                        });
                    }
                });
            } else {
                if ($(this).closest('.assignment-container').find('.assignment-item').length > 1) {
                    row.remove();
                    updateTimelines();
                }
            }
        });

        function saveRoom(row, silent = false) {
            return new Promise((resolve, reject) => {
                const assignments = [];
                row.find('.assignment-item').each(function() {
                    const p_list = [];
                    $(this).find('.personnel-row').each(function() {
                        const pid = $(this).find('.person-select').val();
                        if (pid) p_list.push({
                            personnel_id: pid,
                            notification: $(this).find('.person-notif').val()
                        });
                    });
                    if (p_list.length > 0) {
                        assignments.push({
                            shift: $(this).find('.shift-select').val(),
                            start_time: $(this).find('.start-time-input').val(),
                            end_time: $(this).find('.end-time-input').val(),
                            job_description: $(this).find('.job-desc').html(),
                            personnel_list: p_list
                        });
                    }
                });

                if (assignments.length === 0) {
                    return resolve(false); // Bỏ qua nếu không có dữ liệu nhân sự
                }

                const btn = row.find('.btn-save-room');
                btn.prop('disabled', true);
                $.ajax({
                    url: "{{ route('pages.assignment.production.store') }}",
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        sp_id: row.data('sp-id'),
                        room_id: row.data('room-id'),
                        reportedDate: "{{ $reportedDate }}",
                        stage_groups_code: $('select[name="group_code"]').val(),
                        assignments: assignments
                    },
                    success: function(res) {
                        if (!silent) Swal.fire('Thành công', res.message, 'success');
                        resolve(true);
                    },
                    error: function() {
                        if (!silent) Swal.fire('Lỗi', 'Không thể lưu phòng ' + row.find(
                            '.room-name-cell b').text(), 'error');
                        resolve(false);
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                    }
                });
            });
        }

        $(document).on('click', '.btn-save-room', function() {
            saveRoom($(this).closest('.room-row'));
        });

        $(document).on('click', '#btn-save-all', async function() {
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

            for (let i = 0; i < rows.length; i++) {
                const ok = await saveRoom($(rows[i]), true);
                if (ok) successCount++;
                totalProcessed++;
            }

            Swal.fire('Hoàn tất',
                `Đã lưu thành công ${successCount}/${rows.length} phòng có dữ liệu.`, 'success');
        });

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
        });

        // Thêm công việc ngoài lịch (Thêm 1 hàng phòng mới)
        $(document).on('click', '#btn-add-custom-task', function() {
            const personnel_options =
                `@foreach ($personnel as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach`;
            const room_options =
                `@foreach ($rooms as $r)<option value="{{ $r->id }}">{{ $r->code }} - {{ $r->name }}</option>@endforeach`;

            const newRoomRow = $(`
                <tr class="room-row" data-sp-id="" data-room-id="">
                    <td class="room-name-cell">
                        <select class="form-control form-control-sm room-select-custom mb-2">
                            <option value="">-- Chọn phòng --</option>
                            ${room_options}
                        </select>
                        <div class="mt-2 text-center">
                            <button class="btn btn-outline-success btn-circle btn-add-shift" title="Thêm ca làm việc">
                                <i class="fas fa-plus"></i> Thêm Ca
                            </button>
                        </div>
                    </td>
                    <td class="theory-cell text-left">
                        <div class="theory-content"><span class="text-danger font-weight-bold">NA</span></div>
                        <button class="btn-copy-theory" title="Chép sang nội dung"> >> </button>
                    </td>
                    <td colspan="5" class="p-0">
                        <table class="assignment-inner-table">
                            <tbody class="assignment-container">
                                <tr class="assignment-item" data-theory-start="07:15" data-theory-end="16:00">
                                    <td style="width: 13.3%">
                                        <div class="d-flex flex-column align-items-center">
                                            <select class="form-control form-control-sm shift-select mb-1">
                                                <option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4" selected>HC</option><option value="5">Khác</option>
                                            </select>
                                            <input type="time" class="form-control form-control-sm start-time-input mb-1" value="07:15">
                                            <input type="time" class="form-control form-control-sm end-time-input" value="16:00">
                                        </div>
                                    </td>
                                    <td style="width: 42.7%">
                                        <div class="form-control form-control-sm job-desc" contenteditable="true" style="min-height: 80px; height: auto; white-space: pre-wrap;" placeholder="Nội dung..."></div>
                                    </td>
                                    <td colspan="2" class="p-0" style="width: 40%">
                                        <div class="personnel-container">
                                            <div class="personnel-row d-flex align-items-center p-1 border-bottom">
                                                <div style="flex: 1" class="mr-1">
                                                    <select class="form-control form-control-sm person-select">
                                                        <option value="">-- Chọn người --</option>${personnel_options}
                                                    </select>
                                                </div>
                                                <div style="flex: 1"><input type="text" class="form-control form-control-sm person-notif" placeholder="Chi tiết..."></div>
                                                <i class="fas fa-times text-danger ml-1 btn-remove-person cursor-pointer"></i>
                                            </div>
                                        </div>
                                        <div class="text-left p-1" style="border-top: 1px dashed #eee">
                                            <a href="javascript:void(0)" class="btn-add-person"><i class="fas fa-plus-square"></i></a>
                                        </div>
                                    </td>
                                    <td style="width: 4%" class="text-center"><i class="fas fa-times-circle btn-remove-shift cursor-pointer"></i></td>
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
                    <td class="text-center" style="vertical-align: middle !important; width: 2%;">
                        <button class="btn btn-xs btn-primary btn-save-room shadow-sm">
                            <i class="fas fa-save"></i>
                        </button>
                    </td>
                </tr>
            `);
            $('#main-assignment-tbody').append(newRoomRow);
            initSelect2(newRoomRow.find('.person-select'));
        });

        // Cập nhật data-room-id khi chọn phòng thủ công
        $(document).on('change', '.room-select-custom', function() {
            $(this).closest('.room-row').attr('data-room-id', $(this).val());
        });

        // Cập nhật thanh thời gian khi đổi giờ bắt đầu/kết thúc
        $(document).on('change', '.start-time-input, .end-time-input', function() {
            updateTimelines();
        });

        // Gọi update thanh thời gian ngay lúc load trang
        updateTimelines();
    });
</script>

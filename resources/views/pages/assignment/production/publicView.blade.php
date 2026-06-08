<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Phân Công Sản Xuất - {{ $production_code }}</title>
    <!-- Google Fonts -->
    <link href="{{ asset('assets/vendor/google-fonts/poppins.css') }}" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/font-awesome/css/all.min.css') }}">
    <!-- Bootstrap -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('img/iconstella.svg') }}">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
            display: flex;
            flex-direction: column;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        @media print {
            @page {
                size: landscape;
                margin: 10mm;
            }

            body {
                background-color: #fff;
                overflow: visible !important;
                height: auto !important;
                display: block !important;
            }

            .header-bar,
            .personnel-sidebar,
            .sidebar-toggle-btn {
                display: none !important;
            }

            .main-content-layout {
                overflow: visible !important;
                display: block !important;
                height: auto !important;
            }

            .table-container {
                overflow: visible !important;
                padding: 0 !important;
                height: auto !important;
            }

            .table-assignment {
                box-shadow: none !important;
            }

            .table-assignment thead th {
                position: static !important;
            }

            .print-header {
                display: block !important;
            }

            .no-assignment {
                display: none !important;
            }

            .timeline-container {
                display: none !important;
            }

            .table-assignment tr {
                page-break-inside: avoid;
            }

            .table-assignment thead th {
                padding: 4px !important;
                font-size: 0.85rem !important;
            }

            .assignment-inner-table td, 
            .room-name-cell, 
            .theory-cell {
                padding: 4px !important;
                font-size: 0.85rem !important;
            }

            .job-desc {
                font-size: 0.85rem !important;
                padding: 4px !important;
                min-height: auto !important;
            }

            .personnel-label {
                width: 16px !important;
                height: 16px !important;
                font-size: 0.7rem !important;
                line-height: 16px !important;
            }

            .print-header {
                font-size: 1.2rem !important;
                margin-bottom: 10px !important;
            }
        }

        .print-header {
            display: none;
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.5rem;
            font-weight: bold;
            color: #000;
        }

        :root {
            --primary-gold: #c5c500;
            --light-gold: #fdfde0;
            --border-color: #ddd;
        }

        .header-bar {
            background-color: var(--primary-gold);
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 100;
        }

        .header-bar h3 {
            margin: 0;
            color: #003A4F;
            font-weight: 700;
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
            top: 0;
            z-index: 10;
            background-color: #003A4F !important;
            color: white;
            font-weight: bold;
            text-align: center;
            border: 1px solid #aaa !important;
            padding: 10px 5px;
            font-size: 1.1rem;
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
            font-size: 1.1rem;
        }

        .theory-cell {
            background-color: var(--light-gold);
            font-size: 0.85rem;
            padding: 10px !important;
            position: relative;
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
            padding: 10px !important;
            vertical-align: top !important;
            font-size: 1.1rem;
        }

        .assignment-inner-table tr:last-child td {
            border-bottom: none !important;
        }

        .job-desc {
            min-height: 50px;
            padding: 10px;
            text-align: left !important;
            white-space: pre-wrap;
            font-size: 1.1rem;
            font-weight: bold;
            display: block;
            width: 100%;
        }

        .job-desc.multi-col {
            column-count: 2;
            column-gap: 20px;
        }

        .job-desc * {
            text-align: left !important;
        }

        .personnel-list {
            margin: 0;
            padding-left: 15px;
            font-size: 14px;
        }

        /* Timeline Styles */
        .timeline-container {
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            width: 100%;
            margin-top: 25px;
            margin-bottom: 5px;
            position: relative;
        }

        .timeline-marker {
            position: absolute;
            font-size: 10px;
            color: #000;
            font-weight: 600;
            transform: translateX(-50%);
            top: -18px;
        }

        .timeline-line-solid {
            position: absolute;
            width: 1px;
            height: 18px;
            border-left: 1px solid #aaa;
            top: -6px;
        }

        .timeline-line-dashed {
            position: absolute;
            width: 1px;
            height: 14px;
            border-left: 1px dashed #ccc;
            top: -4px;
        }

        .timeline-segment {
            position: absolute;
            top: 0;
            height: 100%;
            opacity: 0.8;
            border-radius: 3px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .personnel-label {
            width: 22px;
            height: 22px;
            background-color: #003A4F;
            color: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            margin-right: 5px;
            flex-shrink: 0;
            vertical-align: middle;
        }

        .off-stream-row,
        .off-stream-row .room-name-cell,
        .off-stream-row .assignment-inner-table td {
            background-color: #fff0f0 !important;
        }

        /* Sidebar Styles */
        .main-content-layout {
            display: flex;
            flex: 1;
            overflow: hidden;
            position: relative;
            min-height: 0;
        }

        .table-container {
            flex: 1;
            overflow: auto;
            padding: 15px;
            background: #fff;
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
            height: 100%;
            min-height: 0;
            overflow: hidden;
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
    </style>
</head>

@php
    if (!function_exists('timeToOffset')) {
        function timeToOffset($timeStr)
        {
            if (!$timeStr) {
                return 0;
            }
            $parts = explode(':', $timeStr);
            $h = (int) $parts[0];
            $m = (int) ($parts[1] ?? 0);
            $t = $h + $m / 60.0;
            $offset = $t - 6.0;
            if ($offset < 0) {
                $offset += 24.0;
            }
            return $offset;
        }
    }
    $bgColors = ['#0d6efd', '#198754', '#dc3545', '#ffc107', '#0dcaf0', '#6f42c1', '#fd7e14'];
@endphp

<body>

    <div class="header-bar">
        <h3><i class="fas fa-industry"></i> Lịch Phân Công Sản Xuất</h3>
        <div>
            <form action="{{ route('pages.assignment.production.public') }}" method="GET" class="form-inline">

                <span class="mr-2 font-weight-bold" style="color: #003A4F;">Phân Xưởng:</span>
                <select name="production_code" class="form-control form-control-sm mr-3 shadow-sm"
                    onchange="this.form.submit()">
                    @php
                        $depts = ['PXV1', 'PXV2', 'PXVH', 'PXTN', 'PXDN'];
                    @endphp
                    @foreach ($depts as $dept)
                        <option value="{{ $dept }}" {{ $production_code == $dept ? 'selected' : '' }}>
                            {{ $dept }}</option>
                    @endforeach
                </select>

                <span class="mr-2 font-weight-bold" style="color: #003A4F;">Tổ:</span>
                <select name="group_code" class="form-control form-control-sm mr-3 shadow-sm"
                    onchange="this.form.submit()">
                    <option value="">-- Tất cả --</option>
                    @foreach ($groups as $g)
                        <option value="{{ $g->group_code }}" {{ $group_code == $g->group_code ? 'selected' : '' }}>
                            {{ $g->production_group }}</option>
                    @endforeach
                </select>

                <span class="mr-2 font-weight-bold" style="color: #003A4F;">Ngày:</span>
                <input type="date" name="reportedDate" value="{{ $reportedDate }}"
                    class="form-control form-control-sm shadow-sm" style="border: 1px solid #003A4F"
                    onchange="this.form.submit()">

                <a href="{{ route('login') }}" class="btn btn-sm btn-outline-light ml-3">
                    <i class="fas fa-sign-in-alt"></i> Quay lại Đăng Nhập
                </a>
            </form>
        </div>
    </div>

    <div class="print-header">
        @php
            $groupNamePrint = 'Tất cả';
            if ($group_code) {
                $gObj = collect($groups)->where('group_code', $group_code)->first();
                if ($gObj) {
                    $groupNamePrint = $gObj->production_group;
                }
            }
        @endphp
        Lịch Công Tác {{ $groupNamePrint }} Ngày {{ date('d/m/Y', strtotime($reportedDate)) }}
    </div>

    <div class="main-content-layout">
        <div class="table-container">
            <table class="table table-assignment w-100">
                <thead>
                    <tr>
                        <th style="width: 8%">Phòng / Thiết Bị</th>
                        <th style="width: 10%">Ca / Thời Gian</th>
                        <th style="width: 50%">Nội Dung Phân Công</th>
                        <th style="width: 22%">Người thực Hiện</th>
                        <th style="width: 10%">Ghi chú / Lưu ý</th>
                    </tr>
                </thead>
                <tbody>
                    @if (count($tasks) === 0)
                        <tr>
                            <td colspan="5" class="text-center font-weight-bold text-muted p-4">
                                Không có lịch phân công nào.
                            </td>
                        </tr>
                    @endif
                    @php
                        $sortedTasks = collect($tasks)->map(function($task, $index) {
                            $hasJob = false;
                            if (!empty($task->assignments) && count($task->assignments) > 0) {
                                foreach ($task->assignments as $a) {
                                    $desc = trim(strip_tags(str_replace(['&nbsp;', '<br>', '<br/>'], '', $a->Job_description ?? '')));
                                    if ($desc !== '' && $desc !== 'Nội dung...') {
                                        $hasJob = true;
                                        break;
                                    }
                                }
                            }
                            return (object)[
                                'task' => $task,
                                'hasJob' => $hasJob,
                                'originalIndex' => $index
                            ];
                        })->sortBy(function($item) {
                            return ($item->hasJob ? 0 : 1) . '-' . sprintf('%06d', $item->originalIndex);
                        })->pluck('task')->all();
                    @endphp
                    @foreach ($sortedTasks as $task)
                        <tr
                            class="{{ $task->assignments->first()->off_stream ?? 0 ? 'off-stream-row' : '' }} {{ count($task->assignments ?? []) === 0 ? 'no-assignment' : '' }}">
                            <!-- Phòng / Khu vực -->
                            <td class="room-name-cell">
                                <div class="d-block">{{ $task->room_code }}</div>
                                <div class="text-muted">{{ $task->room_name }}</div>
                                @if (!empty($task->main_equiment_name))
                                    <div class="text-muted font-italic" style="font-size: 0.95rem;">
                                        {{ $task->main_equiment_name }}</div>
                                @endif
                            </td>

                            <td colspan="4" class="p-0">
                                @if (count($task->assignments ?? []) === 0)
                                    <div class="p-3 text-center text-muted font-italic">Chưa có phân công</div>
                                @else
                                    <table class="table assignment-inner-table">
                                        <colgroup>
                                            <col style="width: 10.9%"> {{-- Ca/Thời gian --}}
                                            <col style="width: 54.3%"> {{-- Nội dung --}}
                                            <col style="width: 23.9%"> {{-- Người thực hiện --}}
                                            <col style="width: 10.9%"> {{-- Ghi chú --}}
                                        </colgroup>
                                        <tbody>
                                            @foreach ($task->assignments as $a)
                                                <tr data-id="{{ $a->id }}">
                                                    <!-- Ca làm việc -->
                                                    <td class="text-center">
                                                        @php
                                                            $shiftNames = ['1' => 'Ca 1', '2' => 'Ca 2', '3' => 'Ca 3', '4' => 'HC', '5' => 'Khác', '6' => 'Ca 4'];
                                                            $sName = isset($a->Sheet) ? ($shiftNames[$a->Sheet] ?? 'Ca ' . $a->Sheet) : '-';
                                                        @endphp
                                                        <div class="font-weight-bold">{{ $sName }}</div>
                                                        <small class="text-primary"><span
                                                                class="start-time-text">{{ $a->start_time_display }}</span>
                                                            -
                                                            <span
                                                                class="end-time-text">{{ $a->end_time_display }}</span></small>
                                                    </td>

                                                    <td>
                                                        @php
                                                            $jobText = trim($a->Job_description);
                                                            $lines = preg_split("/\r\n|\n|\r|<br\s*\/?>/i", $jobText);
                                                            $linesCount = count(array_filter($lines, 'trim'));
                                                        @endphp
                                                        <div class="job-desc {{ $linesCount > 1 ? 'multi-col' : '' }}">{!! $jobText !!}</div>
                                                    </td>

                                                    <!-- Người thực hiện -->
                                                    <td>
                                                        @if (count($a->personnel_data ?? []) > 0)
                                                            @foreach ($a->personnel_data as $pData)
                                                                @php
                                                                    $personName =
                                                                        collect($personnel)
                                                                            ->where('id', $pData->personnel_id)
                                                                            ->first()->name ?? 'N/A';
                                                                @endphp
                                                                <div class="mb-1 d-flex align-items-center personnel-assigned-item"
                                                                    data-personnel-id="{{ $pData->personnel_id }}">
                                                                    <span
                                                                        class="personnel-label">{{ chr(65 + $loop->index) }}</span>
                                                                    <span>{{ $personName }}</span>
                                                                </div>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted font-italic">Chưa chỉ định</span>
                                                        @endif
                                                    </td>

                                                    <!-- Ghi chú/Lưu ý -->
                                                    <td>
                                                        @foreach ($a->personnel_data as $pData)
                                                            <div class="mb-1">
                                                                @if ($pData->notification)
                                                                    <i class="fas fa-info-circle text-success"></i>
                                                                    {{ $pData->notification }}
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="4" class="p-2 border-top-0">
                                                    <div class="timeline-container">
                                                        <!-- Markers -->
                                                        <div class="timeline-line-solid" style="left: 0%"></div>
                                                        <div class="timeline-marker" style="left: 0%">06h</div>
                                                        <div class="timeline-line-dashed" style="left: 25%"></div>
                                                        <div class="timeline-marker" style="left: 25%">12h</div>
                                                        <div class="timeline-line-dashed" style="left: 50%"></div>
                                                        <div class="timeline-marker" style="left: 50%">18h</div>
                                                        <div class="timeline-line-dashed" style="left: 75%"></div>
                                                        <div class="timeline-marker" style="left: 75%">00h</div>
                                                        <div class="timeline-line-solid" style="left: 100%"></div>
                                                        <div class="timeline-marker" style="left: 100%">06h</div>

                                                        <!-- Segments -->
                                                        @foreach ($task->assignments as $idx => $a)
                                                            @php
                                                                $startOff = timeToOffset($a->start_time_display);
                                                                $endOff = timeToOffset($a->end_time_display);
                                                                if ($endOff <= $startOff) {
                                                                    $endOff += 24.0;
                                                                }
                                                                $width = (($endOff - $startOff) / 24.0) * 100;
                                                                $left = ($startOff / 24.0) * 100;
                                                                $color = $bgColors[$idx % count($bgColors)];
                                                            @endphp
                                                            <div class="timeline-segment"
                                                                style="left: {{ $left }}%; width: {{ $width }}%; background: {{ $color }};"
                                                                title="{{ $a->start_time_display }} - {{ $a->end_time_display }}">
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                @endif
                                {{--
                        <td class="text-left" style="background:#d7eaff; font-size:12px; padding: 10px !important;">
                            @if (count($task->actual_details ?? []) > 0)
                                @php $idx = 1; @endphp
                                @foreach ($task->actual_details as $d)
                                    @php
                                        $start = \Carbon\Carbon::parse($d->start);
                                        $end = \Carbon\Carbon::parse($d->end);
                                        if ($end->lessThan($start)) {
                                            $end->addDay();
                                        }
                                        $minutes = $start->diffInMinutes($end);
                                        $hours = intdiv($minutes, 60);
                                        $mins = $minutes % 60;
                                    @endphp
                                    <div class="mb-2" style="border-bottom: 1px dashed #aaa; padding-bottom: 5px;">
                                        <span class="font-weight-bold">{{ $idx++ }}.
                                            {{ $d->title ?? 'VS' }}</span><br>
                                        <small class="text-dark">({{ $start->format('H:i') }} -
                                            {{ $end->format('H:i') }} =
                                            <b>{{ $hours }}h{{ $mins }}p</b>)</small>
                                        @if ($d->yields)
                                            <br><span class="text-primary"
                                                style="font-weight: 600; font-size: 11px;">Sản Lượng:
                                                {{ number_format($d->yields, 2) }} {{ $d->unit }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        --}}
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
            <div class="sidebar-body p-0" id="sidebar-data-container"
                style="flex: 1; min-height: 0; overflow-y: scroll;">
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
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>

    <script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script>
        const dbAssignments = @json($dbAssignments ?? []);

        const employeeCodeToId = {
            @foreach ($personnel as $p)
                "{{ $p->code }}": "{{ $p->id }}",
            @endforeach
        };

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
                const isLeave = $el.attr('data-shift-key') === 'P' || $el.hasClass('person-on-leave');

                // Remove existing badges container
                $el.find('.personnel-time-ranges').remove();

                let totalHours = 0;

                if (personId) {
                    const assignments = [];
                    // 1. Scan DOM
                    $('.personnel-assigned-item').each(function() {
                        const $assignedItem = $(this);
                        if ($assignedItem.attr('data-personnel-id') == personId.toString()) {
                            const $assignmentRow = $assignedItem.closest('tr');
                            const assId = $assignmentRow.attr('data-id');
                            const start = $assignmentRow.find('.start-time-text').text().trim() || '';
                            const end = $assignmentRow.find('.end-time-text').text().trim() || '';

                            const roomCell = $assignmentRow.closest('td').siblings('.room-name-cell');
                            const roomCode = roomCell.find('div.d-block').text().trim() || roomCell.text()
                                .trim() || 'NA';

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
                            `.assignment-inner-table tr[data-id="${dbAss.assignment_id}"]`).length > 0;
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
                        let totalHoursCalculated = 0;
                        assignments.forEach(a => {
                            totalHoursCalculated += calculateDurationHours(a.start, a.end);
                        });
                        totalHours = Math.round(totalHoursCalculated * 100) / 100;

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
        }

        $(document).ready(function() {
            const $sidebar = $('#personnel-sidebar');
            const $toggleBtn = $('#toggle-sidebar-btn');
            const $closeBtn = $('#close-sidebar-btn');
            const $container = $('#sidebar-data-container');
            const productionCode = '{{ $production_code }}';

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

            let currentSidebarData = [];
            let currentSidebarDay = null;

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
                    url: `{{ route('pages.assignment.production.public.shifts') }}`,
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

                    if (searchStr && !personName.toLowerCase().includes(searchStr) && !personCode
                        .toLowerCase().includes(searchStr)) {
                        return;
                    }

                    if (isGroupFiltered) {
                        if (!allowedPersonnelCodes.includes(personCode.toString())) {
                            return;
                        }
                    }

                    const personInfo = {
                        name: personName,
                        code: personCode
                    };

                    if (shifts.hasOwnProperty(shiftCode)) {
                        shifts[shiftCode].push(personInfo);
                    } else if (shiftCode) {
                        shifts['Khác'].push(personInfo);
                    }
                });

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
                                <div class="list-group-item py-2 pl-4 small draggable-person ${isLeave ? 'person-on-leave text-muted' : ''}" 
                                     data-code="${p.code}" 
                                     data-name="${p.name}"
                                     data-shift-key="${key}"
                                     ${isLeave ? 'style="background-color: #f8f9fa;"' : ''}>
                                    <span class="${isLeave ? 'text-decoration-line-through' : 'text-dark'}">${p.name}</span>
                                    <span class="text-muted float-right">
                                        ${p.code}
                                    </span>
                                </div>
                            `;
                        });
                    }
                });

                html += '</div>';
                $container.html(html);

                updateSidebarPersonnelTimes();

                $('#sidebar-count-c1').text(shifts['C1'].length);
                $('#sidebar-count-c2').text(shifts['C2'].length);
                $('#sidebar-count-c3').text(shifts['C3'].length);
                $('#sidebar-count-c4').text(shifts['C4'].length);
                $('#sidebar-count-hc').text(shifts['HC'].length);
                $('#sidebar-count-p').text(shifts['P'].length);
            }

            if (!$sidebar.hasClass('collapsed')) {
                const icon = $toggleBtn.find('i');
                icon.removeClass('fa-chevron-left').addClass('fa-chevron-right');
                fetchPersonnelShifts();
            }

            @if (request()->has('print'))
                // Chờ load xong giao diện rồi in
                setTimeout(function() {
                    window.print();
                    // Tuỳ chọn: sau khi in có thể đóng tab hoặc làm gì đó
                }, 1000);
            @endif
        });
    </script>
</body>

</html>

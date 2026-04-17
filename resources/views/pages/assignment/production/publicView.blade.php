<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Phân Công Sản Xuất - {{ $production_code }}</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Bootstrap -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
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
            position: sticky;
            top: 0;
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
            top: 65px;
            z-index: 10;
            background-color: #003A4F !important;
            color: white;
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
        }

        .assignment-inner-table tr:last-child td {
            border-bottom: none !important;
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

    <div class="container-fluid mt-3">
        <table class="table table-assignment w-100">
            <thead>
                <tr>
                    <th style="width: 10%">Phòng / Thiết Bị</th>
                    <th style="width: 15%">Lịch Lý Thuyết</th>
                    <th style="width: 10%">Ca / Thời Gian</th>
                    <th style="width: 35%">Nội Dung Phân Công</th>
                    <th style="width: 15%">Người thực Hiện</th>
                    <th style="width: 15%">Ghi chú / Lưu ý</th>
                </tr>
            </thead>
            <tbody>
                @if (count($tasks) === 0)
                    <tr>
                        <td colspan="6" class="text-center font-weight-bold text-muted p-4">
                            Không có lịch phân công nào.
                        </td>
                    </tr>
                @endif
                @foreach ($tasks as $task)
                    <tr>
                        <!-- Phòng / Khu vực -->
                        <td class="room-name-cell">
                            <div class="d-block">{{ $task->room_code }}</div>
                            <div class="text-muted">{{ $task->room_name }}</div>
                        </td>

                        <!-- Lịch lý thuyết -->
                        <td class="theory-cell">
                            {!! $task->theory_display !!}
                        </td>

                        <td colspan="4" class="p-0">
                            @if (count($task->assignments ?? []) === 0)
                                <div class="p-3 text-center text-muted font-italic">Chưa có phân công</div>
                            @else
                                <table class="table assignment-inner-table">
                                    <colgroup>
                                        <col style="width: 13.3%"> <!-- Tương ứng 10% của bảng cha -->
                                        <col style="width: 46.7%"> <!-- Tương ứng 35% của bảng cha -->
                                        <col style="width: 20%"> <!-- Tương ứng 15% của bảng cha -->
                                        <col style="width: 20%"> <!-- Tương ứng 15% của bảng cha -->
                                    </colgroup>
                                    <tbody>
                                        @foreach ($task->assignments as $a)
                                            <tr>
                                                <!-- Ca làm việc -->
                                                <td>
                                                    <div class="font-weight-bold">Ca {{ $a->Sheet ?? '-' }}</div>
                                                    <small class="text-primary">{{ $a->start_time_display }} -
                                                        {{ $a->end_time_display }}</small>
                                                </td>

                                                <td>
                                                    <div class="job-desc" style="white-space: pre-wrap;">
                                                        {!! trim($a->Job_description) !!}</div>
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
                                                            <div class="mb-1">
                                                                <i class="fas fa-user-circle text-info"></i>
                                                                {{ $personName }}
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
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</body>

</html>

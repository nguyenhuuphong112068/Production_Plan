<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân Công Công Việc Hàng Ngày - {{ $production_code }}</title>
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
            /* Chiều cao header-bar */
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
    </style>
</head>

<body>

    <div class="header-bar">
        <h3><i class="fas fa-calendar-alt"></i> Phân Công Bảo Trì - Hàng Ngày</h3>
        <div>
            <form action="{{ route('pages.assignment.public') }}" method="GET" class="form-inline">
                <span class="mr-2 font-weight-bold mx-2" style="color: #003A4F;">Vùng:</span>
                <select name="production_code" class="form-control form-control-sm mr-3" onchange="this.form.submit()">
                    <option value="PXV1" {{ $production_code == 'PXV1' ? 'selected' : '' }}>PXV1</option>
                    <option value="PXV2" {{ $production_code == 'PXV2' ? 'selected' : '' }}>PXV2</option>
                    <option value="PXVH" {{ $production_code == 'PXVH' ? 'selected' : '' }}>PXVH</option>
                    <option value="PXTN" {{ $production_code == 'PXTN' ? 'selected' : '' }}>PXTN</option>
                    <option value="PXDN" {{ $production_code == 'PXDN' ? 'selected' : '' }}>PXDN</option>
                </select>

                <span class="mr-2 font-weight-bold" style="color: #003A4F;">Chọn Ngày:</span>
                <input type="date" name="reportedDate" value="{{ $reportedDate }}"
                    class="form-control form-control-sm shadow-sm" style="border: 2px solid white"
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
                    <th style="width: 15%">Lịch Lý Thuyết</th>
                    <th style="width: 10%">Phòng / Thiết Bị</th>
                    <th style="width: 10%">Ca / Thời Gian</th>
                    <th style="width: 35%">Nội Dung Công Việc</th>
                    <th style="width: 15%">Người thực Hiện</th>
                    <th style="width: 15%">Chi Tiết & Lưu Ý</th>
                </tr>
            </thead>
            <tbody>
                @if (count($tasks) === 0)
                    <tr>
                        <td colspan="6" class="text-center font-weight-bold text-muted p-4">
                            Không có nhiệm vụ bảo trì nào trong ngày này.
                        </td>
                    </tr>
                @endif
                @foreach ($tasks as $task)
                    <tr>
                        <!-- Lịch lý thuyết -->
                        <td class="theory-cell">
                            {!! $task->theory_display !!}
                        </td>

                        <!-- Phòng / Khu vực -->
                        <td class="room-name-cell">
                            <span class="d-block">{{ $task->room_name }}</span>
                            <small class="text-muted">{{ $task->room_code }}</small>
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
                                        @foreach ($task->assignments->sortBy('start') as $a)
                                            <tr>
                                                <!-- Ca làm việc -->
                                                <td>
                                                    <div class="font-weight-bold">Ca {{ $a->Sheet ?? '-' }}</div>
                                                    <small class="text-primary">{{ $a->start_time_display }} -
                                                        {{ $a->end_time_display }}</small>
                                                </td>

                                                <!-- Nội Dung Công Việc -->
                                                <td>
                                                    <div class="job-desc" style="white-space: pre-wrap;">
                                                        {!! $a->Job_description ?: '<span class="text-muted">Không có thông tin</span>' !!}</div>
                                                </td>

                                                <!-- Người thực hiện -->
                                                <td>
                                                    @if (count($a->personnel_data ?? []) > 0)
                                                        <ul class="personnel-list">
                                                            @foreach ($a->personnel_data as $pData)
                                                                @php
                                                                    $personName =
                                                                        collect($personnel)
                                                                            ->where('id', $pData->personnel_id)
                                                                            ->first()->name ?? 'N/A';
                                                                @endphp
                                                                <li>{{ $personName }}</li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <span class="text-muted font-italic">Chưa chỉ định</span>
                                                    @endif
                                                </td>

                                                <!-- Ghi chú/Lưu ý của mỗi người -->
                                                <td>
                                                    @if (count($a->personnel_data ?? []) > 0)
                                                        <ul class="personnel-list"
                                                            style="list-style-type: none; padding-left: 0;">
                                                            @foreach ($a->personnel_data as $pData)
                                                                @if ($pData->notification)
                                                                    <li><i class="fas fa-angle-right text-success"></i>
                                                                        {{ $pData->notification }}</li>
                                                                @else
                                                                    <li><span class="text-muted">-</span></li>
                                                                @endif
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
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

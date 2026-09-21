<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $kind === 'production' ? 'Lịch Công Tác Sản Xuất - Theo Tuần' : 'Lịch Công Tác BT-HC - Theo Tuần' }}</title>
    <link href="{{ asset('assets/vendor/google-fonts/poppins.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/vendor/font-awesome/css/all.min.css') }}">
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

        .header-bar {
            background-color: #c5c500;
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

        /* Không còn thanh điều hướng của layout đăng nhập nên bảng chiếm phần còn lại của trang */
        .content-wrapper {
            height: auto !important;
            flex: 1;
            min-height: 0;
        }

        @media print {
            .header-bar {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    @php
        if ($kind === 'production') {
            $pageTitle = 'Lịch Công Tác Sản Xuất - Theo Tuần';
            $weeklyRoute = 'pages.assignment.production.public.weekly';
            $groupDisabled = $production_code != 'PXV1';
            $groupOptions = collect([(object) ['value' => '', 'label' => '-- Tất cả --']])
                ->concat($groups->map(fn($g) => (object) ['value' => $g->group_code, 'label' => $g->production_group]));
            $dailyUrl = route('pages.assignment.production.public') . '?' . http_build_query(array_filter([
                'production_code' => $production_code,
                'group_code' => $group_code,
                'reportedDate' => $anchorDate,
            ], fn($v) => $v !== null && $v !== ''));
            $rowColTitle = 'Phòng / Thiết Bị';
            $hideEmptyLabel = 'Ẩn phòng trống';
            $searchPlaceholder = 'Tìm nhân sự / phòng...';
            $exportTitle = $pageTitle;
            $exportFilePrefix = 'Lich_Cong_Tac_SX_Tuan';
            $extraParams = ['production_code' => $production_code];
            $productionOptions = ['PXV1', 'PXV2', 'PXVH', 'PXTN', 'PXDN'];
        } else {
            $pageTitle = 'Lịch Công Tác BT-HC - Theo Tuần';
            $weeklyRoute = 'pages.assignment.public.weekly';
            $groupDisabled = false;
            $groupOptions = collect($groups)->map(fn($g) => (object) ['value' => $g->code, 'label' => $g->name]);
            $dailyUrl = route('pages.assignment.public') . '?' . http_build_query(array_filter([
                'group_code' => $group_code,
                'reportedDate' => $anchorDate,
            ], fn($v) => $v !== null && $v !== ''));
            $rowColTitle = 'Phòng / Vị Trí';
            $hideEmptyLabel = 'Ẩn dòng trống';
            $searchPlaceholder = 'Tìm nhân sự / vị trí...';
            $exportTitle = $pageTitle;
            $exportFilePrefix = 'Lich_Cong_Tac_BTHC_Tuan';
        }
        $toolbarOffset = 0;
    @endphp

    <div class="header-bar">
        <h3><i class="fas fa-calendar-week"></i> {{ $pageTitle }}</h3>
        <a href="{{ route('login') }}" class="btn btn-sm btn-outline-light">
            <i class="fas fa-sign-in-alt"></i> Quay lại Đăng Nhập
        </a>
    </div>

    @include('pages.assignment.weeklyBoard')
</body>

</html>

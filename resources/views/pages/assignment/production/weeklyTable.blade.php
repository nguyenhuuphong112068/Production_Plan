@php
    // Trang Sản Xuất - Theo Tuần: cấp tham số riêng rồi dùng chung khung bảng
    // với trang Bảo Trì (xem pages.assignment.weeklyBoard).
    $weeklyRoute = 'pages.assignment.production.weekly';
    $groupDisabled = $isLocked || $production_code != 'PXV1';
    $groupOptions = collect([(object) ['value' => '', 'label' => '-- Tất cả --']])
        ->concat($groups->map(fn($g) => (object) ['value' => $g->group_code, 'label' => $g->production_group]));
    $dailyUrl = route('pages.assignment.production.index') . '?group_code=' . $group_code . '&reportedDate=' . $anchorDate;
    $rowColTitle = 'Phòng / Thiết Bị';
    $hideEmptyLabel = 'Ẩn phòng trống';
    $searchPlaceholder = 'Tìm nhân sự / phòng...';
    $exportTitle = 'Lịch Công Tác Sản Xuất - Theo Tuần';
    $exportFilePrefix = 'Lich_Cong_Tac_SX_Tuan';
@endphp

@include('pages.assignment.weeklyBoard')

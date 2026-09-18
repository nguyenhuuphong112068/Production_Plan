@php
    // Trang BT-HC - Theo Tuần: cấp tham số riêng rồi dùng chung khung bảng
    // với trang Sản Xuất (xem pages.assignment.weeklyBoard).
    $weeklyRoute = 'pages.assignment.maintenance.weekly';
    $groupDisabled = false;
    $groupOptions = collect($groups)->map(fn($g) => (object) ['value' => $g->code, 'label' => $g->name]);
    $dailyUrl = route('pages.assignment.maintenance.index') . '?group_code=' . $group_code . '&reportedDate=' . $anchorDate;
    $rowColTitle = 'Phòng / Vị Trí';
    $hideEmptyLabel = 'Ẩn dòng trống';
    $searchPlaceholder = 'Tìm nhân sự / vị trí...';
    $exportTitle = 'Lịch Công Tác BT-HC - Theo Tuần';
    $exportFilePrefix = 'Lich_Cong_Tac_BTHC_Tuan';
@endphp

@include('pages.assignment.weeklyBoard')

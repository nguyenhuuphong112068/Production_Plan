<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - LỊCH CÔNG TÁC
|--------------------------------------------------------------------------
| Nhóm menu Lịch Công Tác (routes/AssignmentRoute.php, controller Pages/Assignment), quyền layout_assignment.
| Nghiệp vụ chi tiết: .agents/skills/assignments/SKILL.md (eO2, tự động phân công, ràng buộc nhân sự).
*/

$ac = 'app/Http/Controllers/Pages/Assignment/';
$av = 'resources/views/pages/assignment/';
$perm = 'layout_assignment';

return [
    'Bắt đầu' => [
        ['id' => 'tong-quan', 'file' => 'overview', 'title' => 'Tổng Quan & Nguồn Dữ Liệu', 'icon' => 'fas fa-compass',
            'sources' => ['app/Services/ShiftApiService.php'],
            'keywords' => 'eo2 lich truc ca nghi phep tang ca bac ky nang'],
    ],
    'Sản xuất' => [
        ['id' => 'dashboard', 'shotWait' => 15000, 'shotMaxHeight' => 900, 'file' => 'dashboard', 'title' => 'DashBoard Nhân Sự', 'icon' => 'fas fa-tachometer-alt',
            'path' => 'Lịch Công Tác › DashBoard Nhân Sự', 'route' => 'pages.assignment.dashboard.index', 'perm' => $perm,
            'sources' => [$ac . 'DashBoardController.php', $av . 'DashBoard'],
            'keywords' => 'tang ca chinh sach ti le phan cong dong bo eo'],
        ['id' => 'phan-cong-san-xuat', 'file' => 'production', 'title' => 'Phân Công Sản Xuất (Theo Ngày)', 'icon' => 'fas fa-user-cog',
            'path' => 'Lịch Công Tác › Sản Xuất', 'route' => 'pages.assignment.production.index', 'perm' => $perm,
            'helpRoutes' => ['pages.assignment.production.portal'],
            'sources' => [$ac . 'ProductionAssignmentController.php', $av . 'production/dataTable.blade.php', $av . 'production/index.blade.php'],
            'keywords' => 'phan cong ca nhan su hoat dong chi tiet cong viec them ca nhan ban in xuat excel luu toan bo'],
        ['id' => 'tu-dong-phan-cong', 'file' => 'autoAssign', 'title' => 'Tự Động Phân Công & Ràng Buộc', 'icon' => 'fas fa-magic',
            'sources' => [$av . 'production/dataTable.blade.php'],
            'keywords' => 'tu dong phan cong round robin bac ky nang nghi phep lech ca tang ca vuot tran'],
        ['id' => 'san-xuat-theo-tuan', 'file' => 'productionWeekly', 'title' => 'Sản Xuất - Theo Tuần', 'icon' => 'fas fa-calendar-week',
            'path' => 'Lịch Công Tác › Sản Xuất - Theo Tuần', 'route' => 'pages.assignment.production.weekly', 'perm' => $perm,
            'sources' => [$av . 'weeklyBoard.blade.php', $av . 'production/weekly.blade.php'],
            'keywords' => 'tuan truc phong nhan su xuat excel'],
    ],
    'Bảo trì / Hiệu chuẩn' => [
        ['id' => 'phan-cong-bt-hc', 'shotWait' => 15000, 'shotMaxHeight' => 900, 'file' => 'maintenance', 'title' => 'Phân Công Bảo Trì / Hiệu Chuẩn', 'icon' => 'fas fa-tools',
            'path' => 'Lịch Công Tác › Bảo trì/Hiệu Chuẩn', 'route' => 'pages.assignment.maintenance.portal', 'perm' => $perm,
            'helpRoutes' => ['pages.assignment.maintenance.index'],
            'sources' => [$ac . 'MaintenanceAssignmentController.php', $av . 'maintenance'],
            'keywords' => 'to bao tri dien lanh hieu chuan qa ky thuat bao tri'],
        ['id' => 'bt-hc-theo-tuan', 'file' => 'maintenanceWeekly', 'title' => 'BT-HC - Theo Tuần', 'icon' => 'fas fa-calendar-week',
            'path' => 'Lịch Công Tác › BT-HC - Theo Tuần', 'route' => 'pages.assignment.maintenance.weekly', 'perm' => $perm,
            'sources' => [$av . 'maintenance/weekly.blade.php'],
            'keywords' => 'tuan bao tri hieu chuan'],
    ],
    'Phân quyền' => [
        ['id' => 'phan-quyen', 'file' => 'permissions', 'title' => 'Phân Quyền', 'icon' => 'fas fa-user-shield',
            'sources' => ['app/Support/GuidePermissions.php'],
            'keywords' => 'phan quyen nhom quyen ai duoc lam gi quyen rieng nut bi an mo khong thay menu'],
    ],
    'Hỗ trợ' => [
        ['id' => 'loi-thuong-gap', 'file' => 'faq', 'title' => 'Lỗi & Câu Hỏi Thường Gặp', 'icon' => 'fas fa-question-circle',
            'keywords' => 'khong phan cong duoc nghi phep chua dong bo eo2'],
    ],
];

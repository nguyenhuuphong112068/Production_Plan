<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - LỊCH HC-BT
|--------------------------------------------------------------------------
| Nhóm menu Lịch HC-BT: Lịch HC-BT Chart (React "MaintenanceCalender .jsx", route /maintenance-calendar,
| tên route pages.schedual.maintenance_calendar.index) và Lịch HC-BT Tuần.
| Nguyên lý sắp lịch tự động BT-HC-TI: MaintenanceSchedualController::autoSchedual, Maintenance_Scheduling_Rules.md.
*/

$cal = ['resources/js/Pages/MaintenanceCalender .jsx', 'resources/js/Components/MaintenanceSidebar.jsx'];
$ctl = 'app/Http/Controllers/Pages/MaintenanceSchedual/MaintenanceSchedualController.php';

return [
    'Bắt đầu' => [
        ['id' => 'tong-quan', 'file' => 'overview', 'title' => 'Tổng Quan & Quyền', 'icon' => 'fas fa-compass',
            'sources' => [$ctl],
            'keywords' => 'hieu chuan bao tri tien ich quyen sap lich duyet'],
    ],
    'Lịch HC-BT Chart' => [
        ['id' => 'man-hinh-lich', 'file' => 'calendar', 'title' => 'Màn Hình Lịch HC-BT Chart', 'icon' => 'fas fa-stream',
            'path' => 'Lịch HC-BT › Lịch HC-BT Chart', 'route' => 'pages.schedual.maintenance_calendar.index', 'shotWait' => 12000,
            'sources' => $cal,
            'keywords' => 'thanh cong cu ke hoach keo tha luu submit hoan thanh duyet danh gia'],
        ['id' => 'sap-lich-tu-dong', 'file' => 'autoSchedule', 'title' => 'Sắp Lịch Tự Động BT-HC-TI', 'icon' => 'fas fa-robot',
            'path' => 'Lịch HC-BT › Lịch HC-BT Chart › Kế Hoạch › Sắp Lịch',
            'sources' => [$ctl],
            'keywords' => 'sap lich tu dong ngay toi han gom nhom thiet bi lon huy lich'],
    ],
    'Lịch tuần' => [
        ['id' => 'lich-tuan', 'file' => 'weekly', 'title' => 'Lịch HC-BT Tuần', 'icon' => 'fas fa-calendar-week',
            'path' => 'Lịch HC-BT › Lịch HC-BT Tuần', 'route' => 'pages.report.maintenance_weekly_report.index',
            'sources' => ['app/Http/Controllers/Pages/MaintenanceSchedual/MaintenanceWeeklyReportController.php'],
            'keywords' => 'lich tuan in lich'],
    ],
    'Phân quyền' => [
        ['id' => 'phan-quyen', 'file' => 'permissions', 'title' => 'Phân Quyền', 'icon' => 'fas fa-user-shield',
            'sources' => ['app/Support/GuidePermissions.php'],
            'keywords' => 'phan quyen nhom quyen ai duoc lam gi quyen rieng nut bi an mo khong thay menu'],
    ],
];

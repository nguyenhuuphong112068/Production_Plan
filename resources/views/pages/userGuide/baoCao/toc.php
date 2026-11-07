<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - BÁO CÁO
|--------------------------------------------------------------------------
| Nhóm menu Báo Cáo (routes/reportRoute.php, controller Pages/Report), quyền layout_daily_report;
| Báo Cáo Tuần / Tháng / OEE thêm quyền layout_weekly_report.
| Báo Cáo Ngày: phần Xuất Excel chép logic _yield_detail - sửa phải sửa cả 2.
*/

$rc = 'app/Http/Controllers/Pages/Report/';
$rv = 'resources/views/pages/report/';

return [
    'Báo cáo' => [
        ['id' => 'bao-cao-ngay', 'file' => 'daily', 'title' => 'Báo Cáo Ngày SX', 'icon' => 'fas fa-calendar-day',
            'path' => 'Báo Cáo › Báo Cáo Ngày SX', 'route' => 'pages.report.daily_report.index', 'perm' => 'layout_daily_report',
            'sources' => [$rc . 'DailyReportController.php', $rv . 'daily_report'],
            'keywords' => 'bao cao ngay san luong ly thuyet thuc te dap ung giai trinh ton cong doan truoc xuat excel'],
        ['id' => 'chi-so-oee', 'file' => 'oeeFormula', 'title' => 'Các Chỉ Số OEE, Loading, TEEP', 'icon' => 'fas fa-percentage',
            'sources' => [$rc . 'OEEReportController.php'],
            'keywords' => 'oee loading teep cong thuc cong suat thoi gian lam viec'],
        ['id' => 'bao-cao-tuan', 'file' => 'weekly', 'title' => 'Báo Cáo Tuần', 'icon' => 'fas fa-calendar-week',
            'path' => 'Báo Cáo › Báo Cáo Tuần', 'route' => 'pages.report.weekly_report.index', 'perm' => 'layout_weekly_report',
            'sources' => [$rc . 'WeeklyReportController.php', $rv . 'weekly_report'],
            'keywords' => 'bao cao tuan oee may'],
        ['id' => 'bao-cao-thang', 'file' => 'monthly', 'title' => 'Báo Cáo Tháng', 'icon' => 'fas fa-calendar-alt',
            'path' => 'Báo Cáo › Báo Cáo Tháng', 'route' => 'pages.report.monthly_report.index', 'perm' => 'layout_weekly_report',
            'sources' => [$rc . 'MonthlyReportController.php', $rv . 'monthly_report'],
            'keywords' => 'bao cao thang oee'],
        ['id' => 'bao-cao-oee', 'file' => 'oee', 'title' => 'Báo Cáo OEE', 'icon' => 'fas fa-tachometer-alt',
            'path' => 'Báo Cáo › Báo Cáo OEE', 'route' => 'pages.report.oee_report.index', 'perm' => 'layout_weekly_report',
            'sources' => [$rc . 'OEEReportController.php', $rv . 'oee_report'],
            'keywords' => 'oee tu ngay den ngay'],
    ],
    'Phân quyền' => [
        ['id' => 'phan-quyen', 'file' => 'permissions', 'title' => 'Phân Quyền', 'icon' => 'fas fa-user-shield',
            'sources' => ['app/Support/GuidePermissions.php'],
            'keywords' => 'phan quyen nhom quyen ai duoc lam gi quyen rieng nut bi an mo khong thay menu'],
    ],
];

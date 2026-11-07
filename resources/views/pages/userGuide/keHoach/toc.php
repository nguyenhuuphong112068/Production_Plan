<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - KẾ HOẠCH
|--------------------------------------------------------------------------
| Nhóm menu Kế Hoạch (routes/planRoute.php, controller Pages/Plan).
*/

$pc = 'app/Http/Controllers/Pages/Plan/';
$pv = 'resources/views/pages/plan/';
$byWs = ['Theo phân xưởng đang chọn', ''];

return [
    'Bắt đầu' => [
        ['id' => 'tong-quan', 'file' => 'overview', 'title' => 'Tổng Quan & Dòng Chảy Kế Hoạch', 'icon' => 'fas fa-compass',
            'keywords' => 'ke hoach nam thang gui sap lich phan hoi'],
    ],
    'Kế hoạch sản xuất' => [
        ['id' => 'ke-hoach-nam', 'file' => 'annual', 'title' => 'Kế Hoạch Năm Dự Kiến', 'icon' => 'fas fa-calendar-alt',
            'path' => 'Kế Hoạch › Kế Hoạch Năm Dự Kiến', 'route' => 'pages.plan.annual.list', 'perm' => 'layout_annual_plan',
            'helpRoutes' => ['pages.plan.annual.show'],
            'sources' => [$pc . 'AnnualPlanController.php', $pv . 'annual'],
            'keywords' => 'nam du kien so lo thang day vao ke hoach thang phan bo thiet bi ton kho do dang'],
        ['id' => 'ke-hoach-san-xuat', 'file' => 'production', 'title' => 'Kế Hoạch Sản Xuất (Tháng)', 'icon' => 'fas fa-clipboard-list',
            'path' => 'Kế Hoạch › Kế Hoạch Sản Xuất', 'route' => 'pages.plan.production.list', 'chips' => [$byWs],
            'helpRoutes' => ['pages.plan.production.open'],
            'sources' => [$pc . 'ProductionPlanController.php', $pv . 'production/dataTable.blade.php', $pv . 'production/create.blade.php', $pv . 'production/dataTable_plan_list.blade.php'],
            'keywords' => 'them ke hoach lo so lo ngay du kien kcs uu tien ngay co du nguyen lieu bao bi han dung tham dinh khuyen mai'],
        ['id' => 'thao-tac-lo', 'file' => 'productionActions', 'title' => 'Gửi, Sửa, Hủy, Chia Lô & Dự Trù', 'icon' => 'fas fa-paper-plane',
            'path' => 'Kế Hoạch › Kế Hoạch Sản Xuất › mở một kế hoạch',
            'sources' => [$pc . 'ProductionPlanController.php', $pv . 'production/batch_splitting.blade.php'],
            'keywords' => 'gui ke hoach sua nhieu lo huy nhieu lo chia lo dong goi du tru nguyen lieu bao bi xuat excel lich su'],
        ['id' => 'phan-hoi-khsx', 'file' => 'feedback', 'title' => 'Phản Hồi KHSX', 'icon' => 'fas fa-reply',
            'path' => 'Kế Hoạch › Phản hồi KHSX', 'route' => 'pages.plan.production.feedback_list',
            'helpRoutes' => ['pages.plan.production.open_feedback'],
            'sources' => [$pv . 'production/dataTable_feedback.blade.php', $pv . 'production/dataTable_feekback_list.blade.php'],
            'keywords' => 'phan hoi qa ngay du kien kcs ho so lo chay coi khuon so lenh'],
    ],
    'Theo dõi' => [
        ['id' => 'theo-doi-tham-dinh', 'file' => 'validation', 'title' => 'Theo Dõi Thẩm Định', 'icon' => 'fas fa-vial',
            'path' => 'Kế Hoạch › Theo Dõi Thẩm Định', 'route' => 'pages.plan.validation_tracking.list',
            'sources' => [$pc . 'ValidationTrackingController.php', $pv . 'validation_tracking'],
            'keywords' => 'tham dinh nguyen lieu nguon moi cc kiem soat thay doi duyet'],
        ['id' => 'theo-doi-ho-so-kcs', 'file' => 'kcs', 'title' => 'Theo Dõi Hồ Sơ KCS', 'icon' => 'fas fa-folder-open',
            'path' => 'Kế Hoạch › Theo Dõi Hồ Sơ KCS', 'route' => 'pages.plan.kcs_tracking.list',
            'sources' => [$pc . 'KcsTrackingController.php', $pv . 'kcs_tracking'],
            'keywords' => 'ho so kcs coatp dr ir oos opv pvr dap ung dung han ti le'],
        ['id' => 'thong-bao-dong-goi', 'file' => 'packaging', 'title' => 'Thông Báo Đóng Gói', 'icon' => 'fas fa-bullhorn',
            'path' => 'Kế Hoạch › Thông Báo Đóng Gói', 'route' => 'pages.plan.packaging_notification.list',
            'helpRoutes' => ['pages.plan.packaging_notification.open'],
            'sources' => [$pc . 'PackagingNotificationController.php', $pv . 'packaging_notification'],
            'keywords' => 'so po lay mau chau au ngoai chau au viet nam khoa mo khoa'],
    ],
    'Kế hoạch HC-BT' => [
        ['id' => 'ke-hoach-hc-bt', 'file' => 'maintenance', 'title' => 'Kế Hoạch Hiệu Chuẩn / Bảo Trì / Tiện Ích', 'icon' => 'fas fa-tools',
            'path' => 'Kế Hoạch › Kế Hoạch HC-BT › Hiệu Chuẩn / Bảo Trì / Tiện Ích', 'route' => 'pages.plan.maintenance.list',
            'params' => ['type' => 2], 'helpRoutes' => ['pages.plan.maintenance.open'],
            'sources' => [$pc . 'MaintenancePlanController.php', $pv . 'maintenance'],
            'keywords' => 'tao ke hoach tu dong thiet bi ngay du kien gui'],
    ],
    'Phân quyền' => [
        ['id' => 'phan-quyen', 'file' => 'permissions', 'title' => 'Phân Quyền', 'icon' => 'fas fa-user-shield',
            'sources' => ['app/Support/GuidePermissions.php'],
            'keywords' => 'phan quyen nhom quyen ai duoc lam gi quyen rieng nut bi an mo khong thay menu'],
    ],
    'Hỗ trợ' => [
        ['id' => 'loi-thuong-gap', 'file' => 'faq', 'title' => 'Lỗi & Câu Hỏi Thường Gặp', 'icon' => 'fas fa-question-circle',
            'keywords' => 'lo khong hien tren lich chua gui'],
    ],
];

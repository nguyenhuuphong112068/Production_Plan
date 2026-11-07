<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - LỊCH SỬ
|--------------------------------------------------------------------------
| Nhóm menu Lịch Sử (routes/HistoryRoute.php), quyền layout_history.
*/

return [
    'Lịch sử' => [
        ['id' => 'lich-su-san-xuat', 'file' => 'production', 'title' => 'Lịch Sử Sản Xuất', 'icon' => 'fas fa-industry',
            'path' => 'Lịch Sử › Lịch Sử Sản Xuất', 'route' => 'pages.History.production.list', 'perm' => 'layout_history',
            'sources' => ['app/Http/Controllers/Pages/History/ProductionHistoryController.php', 'resources/views/pages/History/Production'],
            'keywords' => 'lich su lo da hoan thanh tra ve return san luong'],
        ['id' => 'lich-su-hc-bt', 'file' => 'maintenance', 'title' => 'Lịch Sử HC - BT', 'icon' => 'fas fa-tools',
            'path' => 'Lịch Sử › Lịch Sử HC - BT', 'route' => 'pages.History.maintenance.list', 'perm' => 'layout_history',
            'sources' => ['app/Http/Controllers/Pages/History/MaintenanceHistoryController.php', 'resources/views/pages/History/Maintenance'],
            'keywords' => 'lich su bao tri hieu chuan ket qua nguoi thuc hien'],
    ],
    'Phân quyền' => [
        ['id' => 'phan-quyen', 'file' => 'permissions', 'title' => 'Phân Quyền', 'icon' => 'fas fa-user-shield',
            'sources' => ['app/Support/GuidePermissions.php'],
            'keywords' => 'phan quyen nhom quyen ai duoc lam gi quyen rieng nut bi an mo khong thay menu'],
    ],
];

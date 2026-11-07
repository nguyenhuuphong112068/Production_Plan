<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - TỒN BTP
|--------------------------------------------------------------------------
| Nhóm menu Tồn BTP, quyền layout_quarantine. Tồn Kho Lý Thuyết: React WipCoverage.jsx + WipCoverageService.
*/

return [
    'Tồn bán thành phẩm' => [
        ['id' => 'ton-ly-thuyet', 'shotWait' => 15000, 'shotMaxHeight' => 900, 'file' => 'theory', 'title' => 'Tồn Kho Lý Thuyết', 'icon' => 'fas fa-chart-area',
            'path' => 'Tồn BTP › Tồn Kho Lý Thuyết', 'route' => 'pages.Schedual.wip_coverage.index', 'perm' => 'layout_wip_coverage',
            'sources' => ['app/Services/WipCoverageService.php', 'resources/js/Pages/WipCoverage.jsx'],
            'keywords' => 'ton cho dinh hinh bao phim dong goi theo ngay nhap xuat ton du bao'],
        ['id' => 'ton-thuc-te', 'file' => 'actual', 'title' => 'Tồn BTP Thực Tế', 'icon' => 'fas fa-pallet',
            'path' => 'Tồn BTP › Tồn BTP Thực Tế', 'route' => 'pages.quarantine.actual.index_actual', 'perm' => 'layout_quarantine',
            'sources' => ['app/Http/Controllers/Pages/Quarantine/QuarantineRoomController.php', 'resources/views/pages/quarantine/actual'],
            'keywords' => 'biet tru thung phong biet tru cong doan tiep theo'],
    ],
    'Phân quyền' => [
        ['id' => 'phan-quyen', 'file' => 'permissions', 'title' => 'Phân Quyền', 'icon' => 'fas fa-user-shield',
            'sources' => ['app/Support/GuidePermissions.php'],
            'keywords' => 'phan quyen nhom quyen ai duoc lam gi quyen rieng nut bi an mo khong thay menu'],
    ],
];

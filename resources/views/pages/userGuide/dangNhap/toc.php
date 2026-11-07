<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - LỊCH SỬ ĐĂNG NHẬP
|--------------------------------------------------------------------------
| Nhóm menu "Lịch Sử Đăng Nhập": mục "Toàn Hệ Thống" (quyền login_history_view) = LoginHistoryController;
| mục "Lịch Sử Đăng Nhập" = AuditTrialController (nhật ký thao tác - Audit Trail).
*/

return [
    'Nhật ký' => [
        ['id' => 'toan-he-thong', 'file' => 'loginHistory', 'title' => 'Lịch Sử Đăng Nhập Toàn Hệ Thống', 'icon' => 'fas fa-globe',
            'path' => 'Lịch Sử Đăng Nhập › Toàn Hệ Thống', 'route' => 'pages.User.login_history.list', 'shots' => false, 'perm' => 'login_history_view',
            'helpRoutes' => ['pages.User.login_history.mine'],
            'sources' => ['app/Http/Controllers/Pages/User/LoginHistoryController.php', 'resources/views/pages/User/login_history'],
            'keywords' => 'dang nhap that bai bi khoa ip trinh duyet dang truc tuyen'],
        ['id' => 'audit-trail', 'file' => 'auditTrail', 'title' => 'Nhật Ký Thao Tác (Audit Trail)', 'icon' => 'fas fa-clipboard-list',
            'path' => 'Lịch Sử Đăng Nhập › Lịch Sử Đăng Nhập', 'route' => 'pages.AuditTrail.list', 'shots' => false,
            'sources' => ['app/Http/Controllers/Pages/AuditTrail/AuditTrialController.php', 'resources/views/pages/AuditTrail'],
            'keywords' => 'audit trail gia tri cu moi hoat dong'],
    ],
    'Phân quyền' => [
        ['id' => 'phan-quyen', 'file' => 'permissions', 'title' => 'Phân Quyền', 'icon' => 'fas fa-user-shield',
            'sources' => ['app/Support/GuidePermissions.php'],
            'keywords' => 'phan quyen nhom quyen ai duoc lam gi quyen rieng nut bi an mo khong thay menu'],
    ],
];

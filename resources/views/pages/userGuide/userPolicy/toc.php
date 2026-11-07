<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - USER POLICY
|--------------------------------------------------------------------------
| Nhóm menu User Policy (routes/UserRoute.php), quyền layout_User.
| Tên nhóm quyền Admin / Schedualer / Executor bị so sánh cứng trong code: đổi tên là mất chức năng.
*/

$uc = 'app/Http/Controllers/Pages/User/';
$uv = 'resources/views/pages/User/';

return [
    'Người dùng & phân quyền' => [
        ['id' => 'tong-quan', 'file' => 'overview', 'title' => 'Cách Phân Quyền Hoạt Động', 'icon' => 'fas fa-compass',
            'keywords' => 'nhom quyen quyen rieng admin schedualer executor'],
        ['id' => 'danh-sach-user', 'file' => 'users', 'title' => 'Danh Sách User', 'icon' => 'fas fa-users',
            'path' => 'User Policy › Danh Sách User', 'route' => 'pages.User.user.list', 'perm' => 'layout_User',
            'sources' => [$uc . 'UserController.php', $uc . 'UserPermissionController.php', $uv . 'user'],
            'keywords' => 'them user ten dang nhap mat khau phong ban to mail quyen rieng vo hieu kich hoat dat lai mat khau'],
        ['id' => 'nhom-phan-quyen', 'file' => 'roles', 'title' => 'Nhóm Phân Quyền', 'icon' => 'fas fa-user-shield',
            'path' => 'User Policy › Nhóm Phân Quyền', 'route' => 'pages.User.role.list', 'perm' => 'layout_User',
            'sources' => [$uc . 'RoleController.php', $uv . 'role'],
            'keywords' => 'nhom quyen ma tran tick quyen dien giai vo hieu'],
    ],
    'Phân quyền' => [
        ['id' => 'phan-quyen', 'file' => 'permissions', 'title' => 'Phân Quyền', 'icon' => 'fas fa-user-shield',
            'sources' => ['app/Support/GuidePermissions.php'],
            'keywords' => 'phan quyen nhom quyen ai duoc lam gi quyen rieng nut bi an mo khong thay menu'],
    ],
];

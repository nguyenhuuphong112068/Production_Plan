<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - BẢO MẬT & PHÂN QUYỀN
|--------------------------------------------------------------------------
| Tài liệu chung cho toàn phần mềm (dựng theo khuôn tài liệu Phân Quyền của WMS).
| Bảng / ma trận quyền đọc thẳng CSDL qua App\Support\GuidePermissions, nên tự khớp khi phân quyền đổi.
| Mỗi tài liệu chức năng có mục "Phân Quyền" riêng dùng chung nguồn đó (shared/permissionSection).
*/

use App\Http\Controllers\Pages\UserGuide\UserGuideController;
use App\Support\GuidePermissions;

$gp = ['app/Support/GuidePermissions.php'];
$roleList = ['route' => 'pages.User.role.list', 'perm' => 'layout_User', 'path' => 'User Policy › Nhóm Phân Quyền'];

// Một mục ma trận cho mỗi nhóm menu leftNAV (cùng thứ tự với GUIDES).
// Không dùng tên biến $guide / $key: file này được require trong UserGuideLinks::groups() và sẽ ghi đè biến ở đó.
$matrixItems = [];
foreach (UserGuideController::GUIDES as $mxKey => $mxGuide) {
    if (!isset(GuidePermissions::MAP[$mxKey])) {
        continue;
    }
    $matrixItems[] = ['id' => 'bang-' . $mxKey, 'file' => 'matrixModule', 'module' => $mxKey,
        'title' => $mxGuide['title'], 'icon' => $mxGuide['icon'], 'sources' => $gp,
        'keywords' => 'ma tran nhom quyen ' . $mxKey, 'shots' => false] + $roleList;
}

return [
    'Bắt đầu' => [
        ['id' => 'tong-quan', 'file' => 'overview', 'title' => 'Cách Phần Mềm Phân Quyền', 'icon' => 'fas fa-sitemap',
            'sources' => ['app/Authorization/PermissionHelper.php', 'app/Services/UserRoleSync.php'],
            'keywords' => 'nhom quyen quyen rieng cho phep tu choi nhom quyen chinh vo hieu phan xuong phong ban'],
        ['id' => 'nhom-quyen-dac-biet', 'file' => 'specialRoles', 'title' => 'Nhóm Quyền & Phòng Ban Đặc Biệt', 'icon' => 'fas fa-user-tag',
            'sources' => $gp,
            'keywords' => 'admin schedualer leader executor engineer manager bod comp qa en doi ten nhom quyen'],
        ['id' => 'cap-quyen', 'file' => 'setup', 'title' => 'Cấp Quyền Cho Người Dùng', 'icon' => 'fas fa-tools',
            'path' => 'User Policy › Danh Sách User / Nhóm Phân Quyền',
            'route' => 'pages.User.user.list', 'perm' => 'layout_User', 'shots' => false,
            'helpRoutes' => ['pages.User.permission.list'],
            'keywords' => 'tao user gan nhom quyen quyen rieng nguoi moi nghi viec vo hieu'],
    ],
    // Ma trận nhóm quyền × quyền theo từng nhóm menu
    'Bảng phân quyền chung' => $matrixItems,
    'Bảo mật - 21 CFR Part 11' => [
        ['id' => 'cfr-part-11', 'file' => 'cfr', 'title' => 'Tuân Thủ 21 CFR Part 11', 'icon' => 'fas fa-shield-alt',
            'sources' => ['app/Http/Controllers/Auth/LoginController.php', 'config/security.php'],
            'keywords' => 'cfr part 11 ho so dien tu chu ky dien tu audit trail mat khau khoa tai khoan het phien'],
        ['id' => 'quyen-khong-dung', 'file' => 'unused', 'title' => 'Quyền Không Còn Dùng', 'icon' => 'fas fa-ban',
            'sources' => $gp,
            'keywords' => 'quyen thua quyen chet don dep'],
    ],
    'Hỗ trợ' => [
        ['id' => 'loi-thuong-gap', 'file' => 'faq', 'title' => 'Lỗi & Câu Hỏi Thường Gặp', 'icon' => 'fas fa-question-circle',
            'keywords' => 'khong thay menu nut mo khong bam duoc doi phan xuong chi xem'],
    ],
];

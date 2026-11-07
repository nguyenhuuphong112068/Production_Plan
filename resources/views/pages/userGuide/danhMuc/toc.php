<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - DANH MỤC
|--------------------------------------------------------------------------
| Nhóm menu Danh Mục (routes/categoryRoute.php, controller Pages/Category).
| Danh mục BTP / TP: chức năng Thêm / Cập Nhật Danh Mục Giả Định đã xoá code 06/11/2026.
*/

$cc = 'app/Http/Controllers/Pages/Category/';
$cv = 'resources/views/pages/category/';
$reason = ['Sửa phải ghi lý do', 'is-warn'];
$mms = ['Lấy dữ liệu MMS', ''];

return [
    'Bắt đầu' => [
        ['id' => 'tong-quan', 'file' => 'overview', 'title' => 'Tổng Quan', 'icon' => 'fas fa-compass',
            'keywords' => 'btp tp bmr bpr mms'],
    ],
    'Sản phẩm' => [
        ['id' => 'ban-thanh-pham', 'file' => 'intermediate', 'title' => 'Danh Mục Bán Thành Phẩm', 'icon' => 'fas fa-flask',
            'path' => 'Danh Mục › Bán Thành Phẩm', 'route' => 'pages.category.intermediate.list', 'chips' => [$reason],
            'sources' => [$cc . 'IntermediateCategoryController.php', $cv . 'intermediate'],
            'keywords' => 'ma btp co lo dang bao che cong doan bao gom thoi gian biet tru cong thuc mms nguyen lieu'],
        ['id' => 'thanh-pham', 'file' => 'product', 'title' => 'Danh Mục Thành Phẩm', 'icon' => 'fas fa-box',
            'path' => 'Danh Mục › Thành Phẩm', 'route' => 'pages.category.product.list', 'chips' => [$reason],
            'sources' => [$cc . 'ProductCategoryController.php', $cv . 'product'],
            'keywords' => 'ma tp ten bpr thi truong qui cach dgsc dgtc cong thuc bao bi'],
    ],
    'Theo dõi & cảnh báo' => [
        ['id' => 'theo-doi-an-ban', 'file' => 'publication', 'title' => 'Theo Dõi Lên Ấn Bản', 'icon' => 'fas fa-file-signature',
            'path' => 'Danh Mục › Theo Dõi Lên Ấn Bản', 'route' => 'pages.category.publication_tracking.list',
            'helpRoutes' => ['pages.category.publication_tracking.show'],
            'sources' => [$cc . 'PublicationTrackingController.php', $cv . 'publication_tracking'],
            'keywords' => 'an ban ky theo doi noi dung quyet dinh co thuc hien ho so san sang y kien dspt chuyen ky sau dong bo ma'],
        ['id' => 'kiem-tra-nl-bb', 'file' => 'materialCheck', 'title' => 'Kiểm Tra NL/BB', 'icon' => 'fas fa-search',
            'path' => 'Danh Mục › Kiểm Tra NL/BB', 'route' => 'pages.category.material_check.list', 'chips' => [$mms],
            'sources' => [$cc . 'MaterialCheckController.php', $cv . 'material_check'],
            'keywords' => 'tra cuu ma nguyen lieu bao bi cong thuc dung xuat excel'],
        ['id' => 'canh-bao-nguon-nl', 'file' => 'sourceWarning', 'title' => 'Cảnh Báo Nguồn NL', 'icon' => 'fas fa-exclamation-triangle',
            'path' => 'Danh Mục › Cảnh Báo Nguồn NL', 'route' => 'pages.category.material_source_warning.list',
            'sources' => [$cc . 'MaterialSourceWarningController.php', $cv . 'material_source_warning'],
            'keywords' => 'ma tran nguon nguyen lieu thi truong duoc phep thiet bi tender'],
    ],
    'Bảo trì - Hiệu chuẩn - Tiện ích' => [
        ['id' => 'danh-muc-bt-hc', 'file' => 'maintenance', 'title' => 'Danh Mục BT - HC - TI (B1, B2)', 'icon' => 'fas fa-tools',
            'path' => 'Danh Mục › BT - HC B1 / B2 › Hiệu Chuẩn / Bảo Trì / Tiện Ích', 'route' => 'pages.category.maintenance.list',
            'params' => ['block' => 'B1', 'type' => 2],
            'sources' => [$cc . 'MaintenanceCategoryController.php', $cv . 'maintenance'],
            'keywords' => 'thiet bi lon con tan suat thoi gian thuc hien phong lien quan hvac'],
    ],
    'Phân quyền' => [
        ['id' => 'phan-quyen', 'file' => 'permissions', 'title' => 'Phân Quyền', 'icon' => 'fas fa-user-shield',
            'sources' => ['app/Support/GuidePermissions.php'],
            'keywords' => 'phan quyen nhom quyen ai duoc lam gi quyen rieng nut bi an mo khong thay menu'],
    ],
    'Hỗ trợ' => [
        ['id' => 'loi-thuong-gap', 'file' => 'faq', 'title' => 'Lỗi & Câu Hỏi Thường Gặp', 'icon' => 'fas fa-question-circle',
            'keywords' => 'khong lay duoc cong thuc mms ma da lien ket'],
    ],
];

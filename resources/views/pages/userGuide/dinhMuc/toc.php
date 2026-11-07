<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - ĐỊNH MỨC
|--------------------------------------------------------------------------
| Nhóm menu Định Mức (routes/quotaRoute.php, controller Pages/Quota).
*/

$qc = 'app/Http/Controllers/Pages/Quota/';
$qv = 'resources/views/pages/quota/';
$byWs = ['Theo phân xưởng đang chọn', ''];

return [
    'Bắt đầu' => [
        ['id' => 'tong-quan', 'file' => 'overview', 'title' => 'Tổng Quan', 'icon' => 'fas fa-compass',
            'keywords' => 'dinh muc phong thoi gian khuon nhan su'],
    ],
    'Định mức sản xuất' => [
        ['id' => 'dinh-muc-san-xuat', 'file' => 'production', 'title' => 'Định Mức Sản Xuất', 'icon' => 'fas fa-stopwatch',
            'path' => 'Định Mức › Sản Xuất', 'route' => 'pages.quota.production.list', 'chips' => [$byWs],
            'sources' => [$qc . 'ProductionQuotaController.php', $qv . 'production'],
            'keywords' => 'phong san xuat thoi gian chuan bi san xuat ve sinh cap 1 cap 2 so lo chien dich he so bon lp khu am ev'],
        ['id' => 'dinh-muc-khuon', 'file' => 'mold', 'title' => 'Định Mức Khuôn EV (Đóng Gói)', 'icon' => 'fas fa-th',
            'path' => 'Định Mức › Khuôn EV', 'route' => 'pages.quota.mold.list',
            'sources' => [$qc . 'MoldController.php', $qv . 'mold/dataTable.blade.php', $qv . 'mold/list.blade.php'],
            'keywords' => 'khuon ev khuon mau khuon ep vi thanh pham may ep vi'],
        ['id' => 'dinh-muc-khuon-dh', 'file' => 'formingMold', 'title' => 'Định Mức Khuôn Định Hình', 'icon' => 'fas fa-shapes',
            'path' => 'Định Mức › Khuôn Định Hình', 'route' => 'pages.quota.mold.forming',
            'sources' => [$qc . 'MoldController.php', $qv . 'mold/forming.blade.php'],
            'keywords' => 'khuon dap vien dinh hinh ban thanh pham'],
    ],
    'Nhân sự' => [
        ['id' => 'danh-sach-nhan-vien', 'shotWait' => 15000, 'shotMaxHeight' => 900, 'file' => 'personnel', 'title' => 'Danh Sách Nhân Viên', 'icon' => 'fas fa-id-badge',
            'path' => 'Định Mức › Danh Sách Nhân Viên', 'route' => 'pages.quota.personnel.portal',
            'helpRoutes' => ['pages.quota.personnel.list'],
            'sources' => [$qc . 'PersonnelController.php', $qv . 'personnel'],
            'keywords' => 'nhan vien to phong cong tac bac ky nang uu tien thai san nghi dai han eo2 ca xuat excel'],
        ['id' => 'san-pham-duoc-phep', 'file' => 'personnelProduct', 'title' => 'Sản Phẩm Được Phép (Nhân Viên)', 'icon' => 'fas fa-user-check',
            'path' => 'Định Mức › Danh Sách Nhân Viên › tab Sản phẩm được phép',
            'sources' => [$qv . 'personnel_product'],
            'keywords' => 'nhan vien san pham btp tp duoc phep nhan ban canh bao'],
    ],
    'Phân quyền' => [
        ['id' => 'phan-quyen', 'file' => 'permissions', 'title' => 'Phân Quyền', 'icon' => 'fas fa-user-shield',
            'sources' => ['app/Support/GuidePermissions.php'],
            'keywords' => 'phan quyen nhom quyen ai duoc lam gi quyen rieng nut bi an mo khong thay menu'],
    ],
    'Hỗ trợ' => [
        ['id' => 'loi-thuong-gap', 'file' => 'faq', 'title' => 'Lỗi & Câu Hỏi Thường Gặp', 'icon' => 'fas fa-question-circle',
            'keywords' => 'chua dinh muc dinh dang hh mm'],
    ],
];

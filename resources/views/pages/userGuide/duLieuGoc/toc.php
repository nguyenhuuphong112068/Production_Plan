<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - DỮ LIỆU GỐC
|--------------------------------------------------------------------------
| Nhóm menu Dữ Liệu Gốc (routes/materDataRoute.php, controller Pages/MaterData). Mỗi màn hình một mục.
| Thao tác chung (Thêm, Sửa, Vô hiệu, Lịch sử) viết ở batDau#thao-tac-chung.
*/

$mc = 'app/Http/Controllers/Pages/MaterData/';
$mv = 'resources/views/pages/materData/';
$byWs = ['Theo phân xưởng đang chọn', ''];

return [
    'Bắt đầu' => [
        ['id' => 'tong-quan', 'file' => 'overview', 'title' => 'Tổng Quan & Thứ Tự Khai Báo', 'icon' => 'fas fa-compass',
            'keywords' => 'thu tu khai bao du lieu goc'],
    ],
    'Sản phẩm' => [
        ['id' => 'ten-san-pham', 'file' => 'productName', 'title' => 'Tên Sản Phẩm', 'icon' => 'fas fa-capsules',
            'path' => 'Dữ Liệu Gốc › Tên Sản Phẩm', 'route' => 'pages.materData.productName.list',
            'sources' => [$mc . 'ProductNameController.php', $mv . 'productName'],
            'keywords' => 'ten viet tat loai san pham thanh pham bulk nguyen lieu xu ly'],
        ['id' => 'dang-bao-che', 'file' => 'dosage', 'title' => 'Dạng Bào Chế', 'icon' => 'fas fa-pills',
            'path' => 'Dữ Liệu Gốc › Dạng Bào Chế', 'route' => 'pages.materData.Dosage.list',
            'sources' => [$mc . 'DosageController.php', $mv . 'Dosage'],
            'keywords' => 'vien nen nang dang bao che'],
        ['id' => 'don-vi-tinh', 'file' => 'unit', 'title' => 'Đơn Vị Tính', 'icon' => 'fas fa-balance-scale',
            'path' => 'Dữ Liệu Gốc › Đơn Vị Tính', 'route' => 'pages.materData.Unit.list',
            'sources' => [$mc . 'UnitController.php', $mv . 'Unit'],
            'keywords' => 'don vi kg vien'],
        ['id' => 'thi-truong', 'file' => 'market', 'title' => 'Thị Trường', 'icon' => 'fas fa-globe-asia',
            'path' => 'Dữ Liệu Gốc › Thị Trường', 'route' => 'pages.materData.Market.list',
            'sources' => [$mc . 'MarketController.php', $mv . 'Market'],
            'keywords' => 'chau au eu thong bao dong goi'],
        ['id' => 'qui-cach', 'file' => 'specification', 'title' => 'Qui Cách Sản Phẩm', 'icon' => 'fas fa-box-open',
            'path' => 'Dữ Liệu Gốc › Qui Cách Sản Phẩm', 'route' => 'pages.materData.Specification.list',
            'sources' => [$mc . 'SpecificationController.php', $mv . 'Specification'],
            'keywords' => 'quy cach dong goi hop vi'],
    ],
    'Phòng & tổ chức' => [
        ['id' => 'phong-san-xuat', 'file' => 'room', 'title' => 'Phòng Sản Xuất', 'icon' => 'fas fa-door-open',
            'path' => 'Dữ Liệu Gốc › Phòng Sản Xuất', 'route' => 'pages.materData.room.list', 'chips' => [$byWs],
            'sources' => [$mc . 'RoomController.php', $mv . 'room'],
            'keywords' => 'ma phong thiet bi chinh cong suat cong doan to quan ly may ep vi'],
        ['id' => 'lien-ket-phong', 'file' => 'roomLinks', 'title' => 'Liên Kết Phòng', 'icon' => 'fas fa-link',
            'path' => 'Dữ Liệu Gốc › Liên kết Phòng', 'route' => 'pages.materData.room_links.list', 'chips' => [$byWs],
            'sources' => [$mc . 'RoomLinkController.php', $mv . 'room_links'],
            'keywords' => 'pha che tron hoan tat phong nguon phong dich canh bao'],
        ['id' => 'to-quan-ly', 'file' => 'stageGroup', 'title' => 'Tổ Quản Lý', 'icon' => 'fas fa-users',
            'path' => 'Dữ Liệu Gốc › Tổ Quản Lý', 'route' => 'pages.materData.stageGroup.list',
            'sources' => [$mc . 'StageGroupController.php', 'resources/views/pages/materData/StageGroup'],
            'keywords' => 'ma to ten to'],
        ['id' => 'phong-ban', 'file' => 'department', 'title' => 'Phòng Ban', 'icon' => 'fas fa-building',
            'path' => 'Dữ Liệu Gốc › Phòng Ban', 'route' => 'pages.materData.department.list',
            'sources' => [$mc . 'DepartmentController.php', 'resources/views/pages/materData/Department'],
            'keywords' => 'ten viet tat phong ban'],
        ['id' => 'ngay-nghi', 'file' => 'offDays', 'title' => 'Ngày Nghỉ Công Ty', 'icon' => 'fas fa-calendar-times',
            'chips' => [['Tự động từ eO2', '']],
            'sources' => ['app/Services/OffDaysSyncService.php', 'app/Console/Commands/SyncOffDays.php'],
            'keywords' => 'ngay nghi le tet chu nhat lam bu eo2 ca truc cap nhat ngay nghi khong sap lich'],
    ],
    'Khuôn & nhóm máy' => [
        ['id' => 'khuon-mau', 'file' => 'blisterMold', 'title' => 'Khuôn Mẫu', 'icon' => 'fas fa-th',
            'path' => 'Dữ Liệu Gốc › Khuôn Mẫu', 'route' => 'pages.materData.blister_mold.list',
            'sources' => [$mc . 'BlisterMoldController.php', 'resources/views/pages/materData/BlisterMold'],
            'keywords' => 'ma khuon so luong loai may ep vi khuon dinh hinh'],
        ['id' => 'loai-may-ep-vi', 'file' => 'blisterType', 'title' => 'Nhóm Máy', 'icon' => 'fas fa-cogs',
            'path' => 'Dữ Liệu Gốc › Nhóm Máy', 'route' => 'pages.materData.blister_type.list',
            'sources' => [$mc . 'BlisterTypeController.php', 'resources/views/pages/materData/BlisterType'],
            'keywords' => 'ma nhom may loai may dinh hinh dong goi dap vien dong nang ima zibo kiem soat trung khuon'],
    ],
    'Phân quyền' => [
        ['id' => 'phan-quyen', 'file' => 'permissions', 'title' => 'Phân Quyền', 'icon' => 'fas fa-user-shield',
            'sources' => ['app/Support/GuidePermissions.php'],
            'keywords' => 'phan quyen nhom quyen ai duoc lam gi quyen rieng nut bi an mo khong thay menu'],
    ],
    'Hỗ trợ' => [
        ['id' => 'loi-thuong-gap', 'file' => 'faq', 'title' => 'Lỗi & Câu Hỏi Thường Gặp', 'icon' => 'fas fa-question-circle',
            'keywords' => 'da ton tai khong thay trong danh sach chon'],
    ],
];

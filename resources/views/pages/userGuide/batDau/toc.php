<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - BẮT ĐẦU
|--------------------------------------------------------------------------
| Đăng nhập, mật khẩu, màn hình công khai ở trang đăng nhập, Trang chủ, chọn phân xưởng (mục đầu
| tiên của leftNAV), thanh trên cùng, thao tác chung trên các màn hình danh sách.
| Nguồn: LoginController, config/security.php, login.blade.php, layout/master + topNAV, SwitchProductionController.
*/

return [
    'Bắt đầu' => [
        ['id' => 'dang-nhap', 'file' => 'login', 'title' => 'Đăng Nhập & Đăng Xuất', 'icon' => 'fas fa-sign-in-alt',
            'sources' => ['app/Http/Controllers/Auth/LoginController.php', 'resources/views/login.blade.php'],
            'keywords' => 'dang nhap ten dang nhap mat khau dang xuat het phien tu dong executor'],
        ['id' => 'mat-khau', 'file' => 'password', 'title' => 'Mật Khẩu & Khoá Tài Khoản', 'icon' => 'fas fa-key',
            'sources' => ['app/Http/Controllers/Auth/LoginController.php', 'config/security.php'],
            'keywords' => 'doi mat khau het han 90 ngay khoa 5 lan 15 phut do phuc tap lan dau quen'],
        ['id' => 'man-hinh-cong-khai', 'file' => 'publicScreens', 'title' => 'Màn Hình Xem Nhanh Không Cần Đăng Nhập', 'icon' => 'fas fa-tv',
            'sources' => ['resources/views/login.blade.php'],
            'keywords' => 'trang thai san xuat phan cong lich tuan bao tri tv cong khai'],
    ],
    'Giao diện chung' => [
        ['id' => 'trang-chu', 'shotWait' => 15000, 'shotMaxHeight' => 900, 'file' => 'home', 'title' => 'Trang Chủ & Thanh Trên Cùng', 'icon' => 'fas fa-home',
            'route' => 'pages.general.home', 'helpRoutes' => ['switch'],
            'sources' => ['resources/views/layout/topNAV.blade.php'],
            'keywords' => 'trang chu thong bao chuong nhac ten huong dan dang xuat'],
        ['id' => 'phan-xuong', 'file' => 'workshop', 'title' => 'Chọn Phân Xưởng', 'icon' => 'fas fa-capsules',
            'path' => 'Mục đầu tiên trên menu trái (tên phân xưởng đang chọn)',
            'sources' => ['app/Http/Controllers/General/SwitchProductionController.php'],
            'keywords' => 'pxv1 pxv2 pxvh pxn pxdn phan xuong chuyen'],
        ['id' => 'menu', 'file' => 'menu', 'title' => 'Các Nhóm Chức Năng Trên Menu', 'icon' => 'fas fa-bars',
            'sources' => ['resources/views/layout/leftNAV.blade.php'],
            'keywords' => 'menu trai nhom chuc nang quyen'],
        ['id' => 'thao-tac-chung', 'file' => 'commonActions', 'title' => 'Thao Tác Chung Trên Màn Hình Danh Sách', 'icon' => 'fas fa-hand-pointer',
            'keywords' => 'them sua khoa mo khoa lich su thay doi tim kiem phan trang'],
    ],
    'Phân quyền' => [
        ['id' => 'phan-quyen', 'file' => 'permissions', 'title' => 'Phân Quyền', 'icon' => 'fas fa-user-shield',
            'sources' => ['app/Support/GuidePermissions.php', 'app/Http/Controllers/Pages/UserGuide/UserGuideController.php'],
            'keywords' => 'phan quyen xem huong dan su dung nut hoi cham hoi khong thay menu huong dan'],
    ],
    'Hỗ trợ' => [
        ['id' => 'loi-thuong-gap', 'file' => 'faq', 'title' => 'Lỗi & Câu Hỏi Thường Gặp', 'icon' => 'fas fa-question-circle',
            'keywords' => 'khong dang nhap duoc bi khoa khong thay menu'],
    ],
];

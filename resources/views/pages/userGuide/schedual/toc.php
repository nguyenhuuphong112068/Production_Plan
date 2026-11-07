<?php

/*
|--------------------------------------------------------------------------
| HƯỚNG DẪN SỬ DỤNG - LỊCH SẢN XUẤT & SẮP LỊCH TỰ ĐỘNG
|--------------------------------------------------------------------------
| Màn hình chính: Lịch Sản Xuất › Lịch Sản Xuất Chart (/Schedual, React FullCalender.jsx).
| Phần "Nguyên lý" khớp tài liệu cho khách hàng storage/app/schedualer/nguyen-ly/<bản mới nhất>
| và skill .agents/skills/schedual + wip-control. Đổi nguyên lý sắp lịch thì sửa cả ba nơi.
|
| Mỗi mục: id (neo, không dấu), file (sections/<file>.blade.php), title, icon (FA 5)
| tuỳ chọn: path (đường đi trên menu), route + perm (nút "Mở màn hình"; perm = tên quyền hoặc
| 'role:Admin,Schedualer'), chips, keywords (tìm không dấu), helpRoutes (route khác cũng dẫn về mục này).
| Nút "?" trên topNAV mở mục ĐẦU TIÊN có route trùng màn hình đang xem.
*/

$edit = ['Admin / Schedualer', 'is-warn'];
$byWorkshop = ['Theo phân xưởng đang chọn', ''];

// 'sources': file mã nguồn mà mục mô tả. pms:user-guide-check cảnh báo khi file sửa SAU file section,
// nghĩa là chức năng đã đổi mà hướng dẫn chưa được xem lại.
$gantt = ['resources/js/Pages/FullCalender.jsx'];
$khsx = ['resources/js/Components/ModalSidebar.jsx'];
$core = ['app/Http/Controllers/Pages/Schedual/SchedualController.php', 'app/Services/MaintenanceShiftService.php'];
$wip = ['app/Plugins/WipControl', 'resources/js/Plugins/WipControl/index.js'];

return [
    'Bắt đầu' => [
        ['id' => 'tong-quan', 'file' => 'overview', 'title' => 'Tổng Quan & Thuật Ngữ', 'icon' => 'fas fa-compass',
            'keywords' => 'cong doan pha che tron hoan tat dinh hinh bao phim dong goi btp chien dich viet tat quy trinh'],
        ['id' => 'phan-quyen', 'sources' => array_merge($gantt, ['app/Support/GuidePermissions.php']), 'file' => 'permissions', 'title' => 'Quyền Truy Cập', 'icon' => 'fas fa-user-shield',
            'keywords' => 'admin schedualer bod phan xuong khoa ttsx'],
    ],
    'Lịch Sản Xuất Chart' => [
        ['id' => 'man-hinh-gantt', 'sources' => $gantt, 'file' => 'gantt', 'title' => 'Màn Hình Lịch Sản Xuất Chart', 'icon' => 'fas fa-stream',
            'path' => 'Lịch Sản Xuất › Lịch Sản Xuất Chart',
            'route' => 'pages.Schedual.index', 'chips' => [$byWorkshop], 'shotWait' => 15000, 'shotMaxHeight' => 1000,
            'keywords' => 'gantt thanh cong cu nut khung thoi gian ngay tuan thang khsx loc phong'],
        ['id' => 'mau-canh-bao', 'sources' => $gantt, 'file' => 'colors', 'title' => 'Màu Cảnh Báo Trên Lịch', 'icon' => 'fas fa-palette',
            'path' => 'Lịch Sản Xuất › Lịch Sản Xuất Chart › ℹ️',
            'keywords' => 'mau do den vang ve sinh biet tru han can hang nguyen lieu bao bi bao tri hieu chuan tien ich'],
    ],
    'Sắp Lịch Thủ Công' => [
        ['id' => 'danh-sach-khsx', 'sources' => $khsx, 'file' => 'khsxList', 'title' => 'Danh Sách KHSX (Lô Chờ Sắp Lịch)', 'icon' => 'fas fa-list-ol',
            'path' => 'Lịch Sản Xuất › Lịch Sản Xuất Chart › KHSX', 'chips' => [$edit],
            'keywords' => 'khsx lo cho sap lich thu tu uu tien keo dong chien dich ma chien dich chi dinh phong khuon khong sap lich tu dong line thieu dinh muc su kien khac gop pha che'],
        ['id' => 'sap-lich-thu-cong', 'sources' => array_merge($khsx, $core), 'file' => 'manualSchedule', 'title' => 'Thả Lô Vào Phòng (Sắp Lịch Thủ Công)', 'icon' => 'fas fa-hand-holding',
            'path' => 'Lịch Sản Xuất › Lịch Sản Xuất Chart › KHSX', 'chips' => [$edit],
            'keywords' => 'sap lich thu cong sap tay keo tha lo vao phong chon lo khong dung phong dinh muc sap lien tuc cong doan sau'],
        ['id' => 'chinh-lich-tay', 'sources' => $gantt, 'file' => 'manualEdit', 'title' => 'Chỉnh Lịch Trên Gantt & Lưu', 'icon' => 'fas fa-hand-pointer',
            'path' => 'Lịch Sản Xuất › Lịch Sản Xuất Chart', 'chips' => [$edit],
            'keywords' => 'keo tha luu ctrl s ctrl z nhap tinh tien cascade xoa lich submit khoa ttsx sua gio chon nhieu lo chuot phai loi den xem truoc chuoi'],
    ],
    'Sắp Lịch Tự Động' => [
        ['id' => 'hop-thoai-sap-lich', 'sources' => $gantt, 'file' => 'autoDialog', 'title' => 'Hộp Thoại Sắp Lịch Tự Động', 'icon' => 'fas fa-robot',
            'path' => 'Lịch Sản Xuất › Lịch Sản Xuất Chart › 🤖', 'chips' => [$edit],
            'keywords' => 'auto scheduler ngay bat dau theo cong doan theo line thoi gian cho kiem nghiem xuong khuon ngay khong sap lich phan bo khuon'],
        ['id' => 'nguyen-ly-tong-quan', 'file' => 'principle', 'title' => 'Nguyên Lý: Hai Lớp Sắp Lịch', 'icon' => 'fas fa-project-diagram',
            'keywords' => 'nguyen ly so do dong chay forward som nhat'],
        ['id' => 'du-lieu-dau-vao', 'sources' => $core, 'file' => 'inputData', 'title' => 'Dữ Liệu Đầu Vào', 'icon' => 'fas fa-database',
            'keywords' => 'ke hoach dinh muc ngay co du nguyen lieu duoc phep can bao bi het han ngay nghi'],
        ['id' => 'chien-dich', 'sources' => $core, 'file' => 'campaign', 'title' => 'Chiến Dịch', 'icon' => 'fas fa-layer-group',
            'keywords' => 'campaign gom lo ve sinh cap 1 cap 2 nguyen khoi'],
        ['id' => 'thu-tu-uu-tien', 'sources' => $core, 'file' => 'priority', 'title' => 'Thứ Tự Ưu Tiên', 'icon' => 'fas fa-sort-amount-down',
            'keywords' => 'nhom ban thanh pham nhay cam thuong mai khuyen mai chen han'],
        ['id' => 'chon-phong-gio', 'sources' => $core, 'file' => 'roomSlot', 'title' => 'Cách Chọn Phòng & Giờ Bắt Đầu', 'icon' => 'fas fa-door-open',
            'keywords' => 'khoang trong phong som nhat ngay nghi khuon ep vi 72 gio'],
        ['id' => 'tu-kiem-tra', 'sources' => $core, 'file' => 'selfCheck', 'title' => 'Tự Kiểm Tra Sau Khi Xếp', 'icon' => 'fas fa-clipboard-check',
            'keywords' => 'han het han nguyen lieu bao bi sap lai 3 lan bao tri hieu chuan tien ich bt-hc-ti doi lich'],
        ['id' => 'ket-qua-sao-luu', 'sources' => $gantt, 'file' => 'result', 'title' => 'Kết Quả, Sao Lưu & Khôi Phục', 'icon' => 'fas fa-history',
            'keywords' => 'thong bao vi pham han backup restore ma sao luu khoi phuc'],
    ],
    'Kiểm Soát Tồn BTP' => [
        ['id' => 'kiem-soat-ton-btp', 'sources' => $wip, 'file' => 'wipControl', 'title' => 'Nguyên Lý Kiểm Soát Tồn BTP', 'icon' => 'fas fa-boxes',
            'path' => 'Hộp thoại Sắp lịch tự động › ② Ràng buộc › Giới hạn tồn BTP (viên)', 'chips' => [$edit],
            'keywords' => 'wip max ton cho dinh hinh bao phim dong goi ngung nguon'],
        ['id' => 'vi-du-ton-btp', 'sources' => $wip, 'file' => 'wipExamples', 'title' => 'Ví Dụ Theo Từng Trường Hợp', 'icon' => 'fas fa-lightbulb',
            'keywords' => 'vi du minh hoa vuot max ep chay'],
        ['id' => 'ket-qua-ton-btp', 'sources' => $wip, 'file' => 'wipResult', 'title' => 'Ràng Buộc, Kết Quả & Giới Hạn', 'icon' => 'fas fa-shield-alt',
            'keywords' => 'vong an toan 10 vong 15 phut ket qua kiem soat btp canh bao khong kha thi'],
    ],
    'Thực thi & xác nhận' => [
        ['id' => 'thuc-thi-san-xuat', 'file' => 'execution', 'title' => 'Thực Thi Sản Xuất (Nhận / Trả Phòng)', 'icon' => 'fas fa-door-closed',
            'path' => 'Lịch Sản Xuất › Thực Thi Sản Xuất', 'route' => 'pages.Schedual.execution.index', 'perm' => 'layout_production_execution',
            'helpRoutes' => ['pages.Schedual.record.index'],
            'sources' => ['app/Http/Controllers/Pages/Schedual/ProductionExecutionController.php', 'app/Services/RoomOccupancyService.php', 'resources/views/pages/Schedual/execution'],
            'keywords' => 'nhan phong tra phong phong ban san sang hoan tac ve sinh sau bt ghi nhan san xuat executor'],
        ['id' => 'tinh-tuyen-lich', 'file' => 'reroute', 'title' => 'Tịnh Tuyến Lịch Theo Thời Gian Thực', 'icon' => 'fas fa-route',
            'sources' => ['app/Services/ScheduleRerouteService.php', 'app/Services/RealtimeRerouteSwitch.php'],
            'keywords' => 'tinh tuyen dich lich tre som tang ca ngay nghi cong tac thoi gian thuc'],
        ['id' => 'xac-nhan-hoan-thanh', 'file' => 'finished', 'title' => 'Xác Nhận Hoàn Thành', 'icon' => 'fas fa-check-double',
            'path' => 'Lịch Sản Xuất › Xác Nhận Hoàn Thành', 'route' => 'pages.Schedual.finised.index', 'perm' => 'layout_finised',
            'sources' => ['app/Http/Controllers/Pages/Schedual/SchedualFinisedController.php', 'resources/views/pages/Schedual/finised'],
            'keywords' => 'xac nhan hoan thanh san luong thuc te so thung ghi nhan san xuat toan bo'],
        ['id' => 'phong-biet-tru', 'file' => 'quarantineRoom', 'title' => 'Xác Định Phòng Biệt Trữ', 'icon' => 'fas fa-warehouse',
            'path' => 'Lịch Sản Xuất › Xác Định Phòng BT', 'route' => 'pages.Schedual.quarantine_room.index', 'perm' => 'layout_finised',
            'sources' => ['app/Http/Controllers/Pages/Schedual/SchedualQuarantineRoomController.php', 'resources/views/pages/Schedual/quarantine_room'],
            'keywords' => 'phong biet tru ban thanh pham vi tri'],
    ],
    'Xem & theo dõi lịch' => [
        ['id' => 'lich-tuan', 'file' => 'weekly', 'title' => 'Lịch Sản Xuất Tuần', 'icon' => 'fas fa-calendar-week',
            'path' => 'Lịch Sản Xuất › Lịch Sản Xuất Tuần', 'route' => 'pages.report.weekly_production_schedule.index',
            'sources' => ['app/Http/Controllers/Pages/Report/WeeklyProductionScheduleController.php', 'resources/views/pages/MaintenanceSchedual/production_weekly'],
            'keywords' => 'lich tuan in ke hoach xuat excel ly thuyet'],
        ['id' => 'danh-sach-lich', 'file' => 'scheduleList', 'title' => 'Danh Sách Lịch', 'icon' => 'fas fa-list',
            'path' => 'Lịch Sản Xuất › Danh Sách Lịch', 'route' => 'pages.Schedual.list.list',
            'sources' => ['app/Http/Controllers/Pages/Schedual/SchedualViewController.php', 'resources/views/pages/Schedual/list'],
            'keywords' => 'danh sach lich phong thoi gian xuat'],
        ['id' => 'tien-trinh', 'file' => 'step', 'title' => 'Tiến Trình Sản Xuất', 'icon' => 'fas fa-tasks',
            'path' => 'Lịch Sản Xuất › Tiến Trình Sản Xuất', 'route' => 'pages.Schedual.step.list',
            'sources' => ['app/Http/Controllers/Pages/Schedual/SchedualStepController.php', 'resources/views/pages/Schedual/step'],
            'keywords' => 'tien trinh biet tru qua han con lai dang cho'],
        ['id' => 'de-nghi-thay-doi', 'file' => 'warning', 'title' => 'Đề Nghị Thay Đổi KH', 'icon' => 'fas fa-exchange-alt',
            'path' => 'Lịch Sản Xuất › Đề Nghị Thay Đổi KH', 'route' => 'pages.Schedual.warning.index', 'perm' => 'layout_warning',
            'sources' => ['app/Http/Controllers/Pages/Schedual/SchedualWarningController.php', 'resources/views/pages/Schedual/warning'],
            'keywords' => 'de nghi doi ngay kcs ngay dap ung nguyen lieu bao bi chap nhan tu choi trao doi'],
        ['id' => 'lich-su-thay-doi', 'file' => 'audit', 'title' => 'Lịch Sử Thay Đổi Lịch', 'icon' => 'fas fa-history',
            'path' => 'Lịch Sản Xuất › Lịch Sử Thay Đổi Lịch', 'route' => 'pages.Schedual.audit.index', 'perm' => 'schedual_audit',
            'helpRoutes' => ['pages.Schedual.audit.open', 'pages.Schedual.audit.compare'],
            'sources' => ['app/Http/Controllers/Pages/Schedual/SchedualAuditController.php', 'resources/views/pages/Schedual/audit'],
            'keywords' => 'lich su thay doi ly do nguoi thay doi version so sanh'],
    ],
    'Lịch phụ trợ & sản lượng' => [
        ['id' => 'tham-dinh-ve-sinh', 'file' => 'cleaningValidation', 'title' => 'Lịch Thẩm Định Vệ Sinh', 'icon' => 'fas fa-shower',
            'path' => 'Lịch Sản Xuất › Lịch Thẩm Định Vệ Sinh', 'route' => 'pages.Schedual.clearning_validation.index',
            'sources' => ['app/Http/Controllers/Pages/Schedual/ClearningValidationController.php'],
            'keywords' => 'tham dinh ve sinh'],
        ['id' => 'nhan-bao-bi', 'file' => 'receivePackaging', 'title' => 'Lịch Nhận Bao Bì', 'icon' => 'fas fa-boxes',
            'path' => 'Lịch Sản Xuất › Lịch Nhận Bao Bì', 'route' => 'pages.Schedual.receive_packaging.list',
            'sources' => ['app/Http/Controllers/Pages/Schedual/ReceivePackagingController.php', 'resources/views/pages/Schedual/receive_packaging'],
            'keywords' => 'nhan bao bi so cap thu cap da nhan trao doi'],
        ['id' => 'san-luong', 'file' => 'yield', 'title' => 'Sản Lượng', 'icon' => 'fas fa-chart-bar',
            'path' => 'Lịch Sản Xuất › Sản Lượng', 'route' => 'pages.Schedual.yield.index',
            'sources' => ['app/Http/Controllers/Pages/Schedual/ShedualYieldController.php', 'resources/views/pages/Schedual/yield'],
            'keywords' => 'san luong ly thuyet thuc te phong'],
        ['id' => 'chinh-sach-san-luong', 'file' => 'yieldPolicy', 'title' => 'Chính Sách Sản Lượng', 'icon' => 'fas fa-bullseye',
            'path' => 'Lịch Sản Xuất › Chính Sách Sản Lượng', 'route' => 'pages.Schedual.yield_policy.index',
            'sources' => ['app/Http/Controllers/Pages/Schedual/YieldPolicyController.php', 'resources/views/pages/Schedual/yield_policy'],
            'keywords' => 'target thang ngay nguong submit gioi han ton'],
    ],
    'Hỗ trợ' => [
        ['id' => 'loi-thuong-gap', 'file' => 'faq', 'title' => 'Lỗi & Câu Hỏi Thường Gặp', 'icon' => 'fas fa-question-circle',
            'keywords' => 'loi khong chay nut mo dang sap lich 423 thieu dinh muc lo khong duoc xep'],
    ],
];

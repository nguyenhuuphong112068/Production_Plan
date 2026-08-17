<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Lịch trực (eO2 PMS)
    |--------------------------------------------------------------------------
    |
    | Thay cho endpoint cũ http://s-webdev:5070/api/shifts/by-department.
    | Hệ thống nguồn đã tách thành 3 endpoint con, đều nhận tham số
    | fromdate / todate (Y-m-d) và department:
    |
    |   GET {base}/range?fromdate=..&todate=..&department=..     -> ca trực
    |   GET {base}/leave?fromdate=..&todate=..&department=..     -> đơn nghỉ phép
    |   GET {base}/overtime?fromdate=..&todate=..&department=..  -> giờ tăng ca
    |
    */

    'base_url' => env('SHIFT_API_BASE_URL', 'https://eo2pms.stellapharm.int/api/shifts'),

    // Bộ phận PXV1 (15) có ít nhân sự Kho (17) làm việc tại Trung Tâm Cân.
    'warehouse_department' => 17,

    /*
    | Chứng thư TLS của eo2pms.stellapharm.int do CA nội bộ cấp nên PHP không
    | xác thực được (curl trả về lỗi 60). Đặt SHIFT_API_VERIFY_TLS=true sau khi
    | CA nội bộ đã được cài vào máy chủ.
    */
    'verify_tls' => env('SHIFT_API_VERIFY_TLS', false),

    /*
    | Payload của phân xưởng lớn (PXV1) cho một tháng nặng ~700KB và server
    | nguồn có thể mất tới ~60s để trả. Toàn bộ request (mọi tháng × mọi bộ
    | phận × 3 endpoint) được gom vào MỘT mẻ curl_multi nên thời gian chờ xấp
    | xỉ request chậm nhất thay vì cộng dồn.
    */
    'timeout' => env('SHIFT_API_TIMEOUT', 90),

    /*
    | Số kết nối đồng thời tối đa tới server nguồn.
    |
    | Mặc định 3 = vừa đúng bộ 3 endpoint của một tháng chạy cùng lúc. Đo thực
    | tế: mỗi request tốn cố định ~9.5s bất kể cửa sổ ngày lớn hay nhỏ, nên chạy
    | 3 cái song song rút từ ~19s xuống ~10s.
    |
    | KHÔNG nên tăng bừa: server nguồn có rate limit, dồn 6 request nặng
    | (PXV1 ~700KB/tháng) liên tục sẽ bị trả HTTP 429, khi đó hệ thống phải rơi
    | về bản sao lưu 24h (dữ liệu cũ). Thà chậm vài giây còn hơn hiển thị số liệu
    | cũ. Chỉ tăng khi đã xác nhận máy chủ eO2 chịu được.
    */
    'max_concurrency' => env('SHIFT_API_MAX_CONCURRENCY', 3),

    /*
    | Giờ làm việc chuẩn một ngày. Bộ 3 endpoint mới KHÔNG trả
    | `regular_working_Hours` nên giá trị này được suy ra: mặc định 8h, ngày
    | nghỉ phép thì trừ số giờ nghỉ, ngày không có ca thì bằng 0.
    */
    'standard_working_hours' => env('SHIFT_API_STANDARD_HOURS', 8),

    // Cache "nóng" dùng lại giữa các request gần nhau.
    'cache_ttl' => env('SHIFT_API_CACHE_TTL', 120),

    // Bản sao lưu dùng khi API lỗi/timeout để giao diện vẫn hoạt động.
    'backup_ttl' => env('SHIFT_API_BACKUP_TTL', 86400),

    /*
    | Trạng thái đơn nghỉ phép được TÍNH là nghỉ.
    | Giá trị thực tế từ API gồm: Approved, Rejected, Cancelled và các biến thể
    | chờ duyệt như "Waiting TLE Approval/Chờ tổ trưởng duyệt",
    | "Waiting DH Approval/Chờ trưởng phòng duyệt",
    | "Waiting BOD Approval/Chờ BGD duyệt".
    | Vì chuỗi chờ duyệt không cố định nên khớp theo quy tắc:
    |   status == "Approved"  HOẶC  (chứa "Waiting" VÀ chứa "Approval")
    */
    'leave_approved_status' => 'approved',
    'leave_pending_tokens' => ['waiting', 'approval'],

];

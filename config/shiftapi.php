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
    | Trước đây để 3 = vừa đúng bộ 3 endpoint của một tháng chạy cùng lúc (mỗi
    | request tốn cố định ~9.5s nên 3 cái song song rút từ ~19s xuống ~10s).
    |
    | Hạ xuống 2 ngày 18/09/2026 vì nghi eO2 giới hạn SỐ KẾT NỐI ĐỒNG THỜI, chứ
    | không chỉ giới hạn theo cửa sổ thời gian. Dấu hiệu: hai lượt 10:59:26 và
    | 11:10:02 đều chỉ chết ĐÚNG MỘT endpoint và luôn là cái thứ 3 trong hàng
    | (`overtime` bộ phận 15), trong khi hạn ngạch nội bộ còn trống nguyên và cả
    | mẻ chỉ có 6 request - tức không phải do gửi quá nhiều, mà do gửi quá dày.
    |
    | KHÔNG nên tăng bừa: server nguồn có rate limit, dồn 6 request nặng
    | (PXV1 ~700KB/tháng) liên tục sẽ bị trả HTTP 429, khi đó hệ thống phải rơi
    | về bản sao lưu 24h (dữ liệu cũ). Thà chậm vài giây còn hơn hiển thị số liệu
    | cũ. Chỉ tăng khi đã xác nhận máy chủ eO2 chịu được.
    */
    'max_concurrency' => env('SHIFT_API_MAX_CONCURRENCY', 2),

    /*
    |--------------------------------------------------------------------------
    | Kích thước mẻ gọi eO2
    |--------------------------------------------------------------------------
    |
    | Cắt danh sách URL thành từng mẻ `max_batch` cái, chạy lần lượt, nghỉ
    | `batch_pause` giây giữa hai mẻ.
    |
    | QUY TẮC CHẶN CỦA eO2: hai lời gọi liên tiếp tới CÙNG MỘT endpoint phải cách
    | nhau hơn 60 giây. Giới hạn tính theo TỪNG API (`range`, `leave`, `overtime`
    | riêng biệt), KHÔNG tính theo tổng số request và KHÔNG phân biệt `department`
    | - `range?department=15` và `range?department=17` là cùng một api.
    |
    | Vì vậy hai con số dưới đây phải đi cùng nhau:
    |
    |   - `max_batch = 3` để một mẻ đúng bằng một "ô" dữ liệu mà
    |     `loadMonthIndexes` sinh ra, tức `range` + `leave` + `overtime` mỗi cái
    |     ĐÚNG MỘT LẦN. Đổi sang số khác là phá vỡ tính chất này: mẻ 6 sẽ gọi
    |     `range` hai lần cách nhau vài giây và chắc chắn bị chặn.
    |   - `batch_pause = 70` (> 60s, có biên) vì mẻ sau gọi lại đúng 3 api đó.
    |
    | Số liệu 18/09/2026 khớp quy tắc này: mẻ 6 request (PXV1 gộp Kho, mỗi api bị
    | gọi 2 lần trong ~30s) luôn bị chặn ở nửa sau, dù thử max_concurrency 3 rồi
    | 2 rồi cắt 3+3 nghỉ 10s; còn lượt chỉ hỏi MỘT ô (mỗi api một lần) thì qua
    | ngay - 11:33:19 và 11:57:46, ~15-17s, 556 nhân sự.
    |
    | Đổi lại một lượt làm mới đủ tháng PXV1 mất ~110s (20s + 70s nghỉ + 20s).
    |
    | LƯU Ý khi thêm luồng mới: `roster()` cũng gọi `range`, nên nó phải cách lần
    | gọi `range` gần nhất hơn 60s, nếu không chính nó bị chặn.
    |
    | Đặt max_batch = 0 để tắt việc cắt mẻ (gửi tất cả trong một mẻ như trước).
    */
    'max_batch' => env('SHIFT_API_MAX_BATCH', 3),
    'batch_pause' => env('SHIFT_API_BATCH_PAUSE', 70),

    /*
    |--------------------------------------------------------------------------
    | Hạn ngạch dùng chung tới eO2 PMS
    |--------------------------------------------------------------------------
    |
    | `max_concurrency` ở trên chỉ giới hạn được TRONG MỘT tiến trình PHP. Năm
    | người cùng mở trang chưa có cache là năm tiến trình độc lập, không ai biết
    | ai, tổng lưu lượng vượt xa hạn mức của máy chủ nguồn.
    |
    | Bộ đếm này nằm trong bảng `cache` nên MỌI tiến trình (web, command nạp nền,
    | nút Đồng bộ) cùng nhìn một con số. Hết hạn ngạch thì tự dừng gọi và rơi về
    | bản sao lưu, thay vì bắn tiếp để bị eO2 trả HTTP 429.
    |
    | Đo thực tế 18/08/2026: eO2 chặn sau ~24 request trong ~175s, cửa sổ tính
    | hạn mức là 5 phút. Đặt 18/300s để chừa biên cho các luồng khác. Một lượt
    | `shifts:warm-cache` là 24 request nên sẽ trải qua 2 cửa sổ — command tự chờ
    | hết cửa sổ rồi chạy tiếp, không cần can thiệp tay.
    |
    | `rate_window` là ĐỘ DÀI cửa sổ, không phải mốc reset: bộ đếm được đọc theo
    | cửa sổ TRƯỢT (trọn ô hiện tại + phần ô trước còn trong cửa sổ). Nếu reset
    | cứng theo ô thì tại biên ô sẽ lọt 2× hạn mức — 18 request lúc 10:04:59 cộng
    | 18 request lúc 10:05:00 là 36 request trong 2 giây, vượt xa mức eO2 chịu
    | được và đó chính là nguyên nhân loạt 429 ngày 18/09/2026.
    |
    | Đặt rate_limit = 0 để tắt hẳn cơ chế này.
    */
    'rate_limit' => env('SHIFT_API_RATE_LIMIT', 18),
    'rate_window' => env('SHIFT_API_RATE_WINDOW', 300),

    /*
    | Giờ làm việc chuẩn một ngày. Bộ 3 endpoint mới KHÔNG trả
    | `regular_working_Hours` nên giá trị này được suy ra: mặc định 8h, ngày
    | nghỉ phép thì trừ số giờ nghỉ, ngày không có ca thì bằng 0.
    */
    'standard_working_hours' => env('SHIFT_API_STANDARD_HOURS', 8),

    /*
    | Cache "nóng" mà luồng web đọc.
    |
    | Trước đây đặt 120s, quá ngắn so với nhịp thay đổi thật của lịch trực (vài
    | lần/ngày). Hệ quả: gần như mọi lần vào trang đều là cache miss -> 6 request
    | nặng tới eO2 -> trang chờ ~20s và thường xuyên dính HTTP 429.
    |
    | Đặt 12 giờ. Con số này bám theo lịch `shifts:warm-cache` (00:00, 05:55,
    | 12:00, 16:00): khoảng trống dài nhất giữa hai lượt là 8 giờ, cộng thêm biên
    | 4 giờ để một lượt nạp lỗi cũng chưa làm cache nguội. Đặt ngắn hơn 8 giờ thì
    | cache chết giữa chừng và người dùng lại phải chờ API ~20s.
    |
    | Độ tươi của số liệu do 4 lượt nạp/ngày quyết định, KHÔNG phải do TTL này.
    | Cần mới ngay thì bấm nút Đồng bộ ở sidebar Lịch công tác - nút đó xoá khoá
    | này rồi gọi lại API.
    |
    | Lưu ý: KHÔNG tốn thêm dung lượng. Cùng nội dung này vốn đã được ghi song
    | song vào khoá backup với TTL 24h.
    */
    'cache_ttl' => env('SHIFT_API_CACHE_TTL', 43200),

    // Bản sao lưu dùng khi API lỗi/timeout để giao diện vẫn hoạt động.
    'backup_ttl' => env('SHIFT_API_BACKUP_TTL', 86400),

    /*
    | Cache riêng cho danh sách nhân sự (`roster()`): dữ liệu này rất ít thay đổi
    | nên giữ lâu hơn nhiều so với lịch trực. Mặc định 6 giờ.
    */
    'roster_cache_ttl' => env('SHIFT_API_ROSTER_CACHE_TTL', 21600),

    /*
    | Timeout cho thao tác đồng bộ nhân sự THỦ CÔNG (nút Sync ở trang Quản lý
    | nhân sự) và cho command `employees:sync-roster`.
    |
    | Phải rộng: đo thực tế bộ phận 15 (PXV1) mất ~88s cho một ngày duy nhất,
    | nên timeout mặc định 90s là quá sát.
    */
    'manual_sync_timeout' => env('SHIFT_API_MANUAL_SYNC_TIMEOUT', 180),

    /*
    | Số giờ tối thiểu giữa 2 lần đồng bộ nhân sự.
    |
    | Đồng bộ chính do command `employees:sync-roster` chạy nền đảm nhiệm. Luồng
    | đăng nhập chỉ ghi lại từ cache và đóng vai trò lưới an toàn khi mốc này hết
    | hạn (phòng trường hợp scheduler không chạy). Đặt 0 để bỏ chặn tần suất.
    */
    'login_sync_interval_hours' => env('SHIFT_API_LOGIN_SYNC_INTERVAL_HOURS', 12),

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

<p>
    <b>Dashboard tình hình nhân sự</b> theo phân xưởng / tổ: số người đi ca, tỷ lệ phân công, giờ tăng ca so với chính sách.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/lichCongTac/dashboard.png', 'caption' => 'Màn hình Lịch Công Tác › DashBoard Nhân Sự'])
<ul>
    <li>Bộ lọc: <b>Phân xưởng</b>, <b>Tổ (Nhóm)</b>, <b>Loại thống kê</b>, <b>Ngày</b>. Bấm <b>Đồng bộ dữ liệu e-o</b> để lấy lịch trực mới nhất từ eO2.</li>
    <li><b>Tỷ lệ phân công</b>, <b>Biểu đồ tổng quan</b>; bấm một ngày để xem <i>Chi Tiết Theo Ngày</i>.</li>
    <li><b>Danh sách chi tiết nhân sự</b>: Mã NS, Họ và Tên, Tổ, Đăng ký đi ca, Giờ làm việc theo e-office, TC (h), Trạng thái
        (đánh dấu nghỉ thai sản / nghỉ phép dài hạn).</li>
    <li><b>Tăng Ca Theo Tổ / Theo Phòng</b>: tổng giờ tăng ca, số người tăng ca.</li>
</ul>

<h3>Chính sách tăng ca</h3>
<p>Bảng <b>Chính Sách Tăng Ca</b> theo cấp độ và tổ: <b>Tối đa Người/Ngày</b>, <b>Tối đa Giờ/Ngày</b> (để trống = không giới hạn).
    Bấm <b>Lưu Cấu Hình</b>; <b>Xem lịch sử</b> để xem các lần đổi chính sách. Phân công vượt trần phải được chấp nhận ở trang
    Phân Công Sản Xuất.</p>

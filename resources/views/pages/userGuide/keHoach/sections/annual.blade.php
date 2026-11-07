<p>
    Lập kế hoạch sản xuất dự kiến cả năm cho một phân xưởng, dùng để cân năng lực thiết bị và đẩy dần lô sang kế hoạch tháng.
    Màn hình chỉ hiện với tài khoản có quyền.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/keHoach/ke-hoach-nam.png', 'caption' => 'Màn hình Kế Hoạch › Kế Hoạch Năm Dự Kiến'])

<h3>Tạo kế hoạch năm</h3>
<ol class="ug-steps">
    <li>Bấm <span class="btn btn-sm btn-success ug-ui">Thêm Kế Hoạch</span>, nhập <b>Năm</b> (2020-2100), chọn <b>Phân xưởng</b>,
        <b>Ghi chú</b>, bấm <b>Tạo mới</b>.</li>
    <li>Bấm nút ở cột <b>Chi Tiết</b> để mở <b>Bảng Tính Kế Hoạch Năm</b>.</li>
</ol>

<h3>Bảng Tính Kế Hoạch Năm</h3>
<ul>
    <li><b>Thêm sản phẩm</b>: chọn các sản phẩm chưa có trong kế hoạch năm, bấm <b>Xác nhận Thêm</b>.</li>
    <li>Nhập số lô dự kiến theo từng tháng ngay trên lưới, bấm <b>Lưu thay đổi</b>. Tab <i>Bảng dự kiến kế hoạch năm</i> và
        <i>Tỉ lệ thực xuất và dự trữ an toàn</i>; bấm số liệu để xem <i>Chi Tiết Các Lô BTP Dở Dang</i> hoặc <i>Chi Tiết Các Lô
        Tồn Kho thực tế (MMS)</i>.</li>
    <li><b>Phân bổ thiết bị</b>: so sánh giờ máy cần (số lô × định mức giờ/lô) với năng lực từng thiết bị theo công đoạn;
        <i>Thống kê theo dòng máy</i>.</li>
    <li><b>Đẩy vào KH Tháng</b>: chọn <b>Tháng cần đẩy từ KH Năm</b> và <b>Kế hoạch tháng nhận</b> (phải là kế hoạch tháng
        <b>chưa gửi</b>), bấm <b>Xác nhận đẩy</b>. Không có kế hoạch tháng chưa gửi thì tạo trước ở Kế Hoạch Sản Xuất.</li>
</ul>

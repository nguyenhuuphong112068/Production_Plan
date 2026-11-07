<p>
    Biểu đồ dạng Gantt giống Lịch Sản Xuất Chart: mỗi dòng một phòng, mỗi thanh một lịch BT-HC-TI. Các thiết bị con cùng thiết bị
    lớn trong một phòng được gom thành <b>một thanh</b> (tiêu đề: mã - tên thiết bị lớn, rồi từng mã thiết bị con).
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/lichHcBt/man-hinh-lich.png', 'caption' => 'Màn hình Lịch HC-BT › Lịch HC-BT Chart'])

<h3>Thanh công cụ</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead><tr><th style="width: 140px">Nút</th><th>Tác dụng</th></tr></thead>
        <tbody>
            <tr><td>⏴ Hiện Tại ⏵</td><td>Lùi / về hiện tại / tiến một khung thời gian.</td></tr>
            <tr><td>ℹ️</td><td>Ẩn / hiện chú thích màu.</td></tr>
            <tr><td>🏭</td><td>Ẩn / hiện lịch sản xuất (để thấy khoảng trống của phòng).</td></tr>
            <tr><td>👥</td><td>Ẩn / hiện phân công nhân sự tại từng phòng.</td></tr>
            <tr><td>⭐</td><td>Tạo đánh giá cho công việc bảo trì - hiệu chuẩn không có trên lịch. Bấm vào một lịch để đánh giá lịch đó.</td></tr>
            <tr><td>💾</td><td>Lưu thay đổi kéo thả (hoặc <span class="ug-key">Ctrl</span> + <span class="ug-key">S</span>).</td></tr>
            <tr><td>🗑️</td><td>Xoá các lịch đang chọn.</td></tr>
            <tr><td>✅</td><td>Xác nhận hoàn thành (tạm thời) các lịch đang chọn.</td></tr>
            <tr><td>📤</td><td>Submit lịch để các bộ phận khác thấy.</td></tr>
            <tr><td>👌</td><td>Duyệt các lịch bảo trì đang chọn (giữ <span class="ug-key">Shift</span> và quét chuột để chọn nhiều).</td></tr>
            <tr><td>Kế Hoạch</td><td>Mở danh sách kế hoạch HC-BT chờ sắp lịch: kéo thả vào phòng, hoặc dùng <b>Sắp Lịch</b> / <b>Huỷ Lịch</b>.</td></tr>
            <tr><td>Ngày / Tuần / Tháng / 3 Tháng, Slot, cỡ chữ, tìm kiếm</td><td>Như Lịch Sản Xuất Chart.</td></tr>
        </tbody>
    </table>
</div>

<h3>Sửa một lịch</h3>
<p>Kéo thả để dời giờ / phòng rồi lưu 💾. Bấm vào lịch để sửa thời gian thực hiện (<i>Cập nhật Thời gian Bảo trì</i>).
    Lịch đã hoàn thành có thể hiện lịch lý thuyết (🧭) để so.</p>

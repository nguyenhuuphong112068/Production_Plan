<p>
    Xác nhận lô đã hoàn thành ở từng công đoạn, kèm thời gian thực tế, sản lượng và số thùng. Chọn công đoạn ở ô lọc (Cân NL, Cân
    NL Khác / Xử lý Bao Bì, Pha Chế, Trộn Hoàn Tất, Định Hình, Bao Phim, Đóng Gói), chọn khoảng ngày bằng « ».
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/schedual/xac-nhan-hoan-thanh.png', 'caption' => 'Màn hình Lịch Sản Xuất › Xác Nhận Hoàn Thành'])

<h3>Trên mỗi dòng lô</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Phòng Sản Xuất', true, 'Phòng thực tế đã chạy (đổi được nếu chạy khác phòng lịch).'],
    ['Thời gian sản xuất (bắt đầu - kết thúc)', true, 'Mặc định lấy giờ Nhận phòng; sửa nếu cần.'],
    ['Sản Lượng Thực Tế', true, 'Sản lượng của lô ở công đoạn này.'],
    ['Số Thùng', false, ''],
    ['Ghi Chú', false, ''],
    ['Thời gian vệ sinh', false, 'Kết thúc vệ sinh = lúc Trả phòng ở trang Thực Thi Sản Xuất (chỉ hiển thị, không sửa).'],
]])

<h3>Hai nút xác nhận</h3>
<ul>
    <li><b>Ghi Nhận Sản Xuất</b> (<i class="fas fa-check"></i>): ghi phần sản xuất, chưa chốt vệ sinh.</li>
    <li><b>Ghi Nhận Toàn Bộ</b> (<i class="fas fa-check"></i><i class="fas fa-check"></i>): chốt cả sản xuất và vệ sinh, lô
        <i>✓ Đã hoàn thành</i>. Phần mềm kiểm tra trùng giờ trong phòng, phòng và thời gian hợp lệ trước khi lưu.</li>
</ul>
<p>Lô đã xác nhận có thể xem lịch sử các phiên bản (Versions, Lý Do). Bấm ✓ / ✓✓ không dịch lịch; lịch được dịch khi Trả phòng
    nếu công tắc tịnh tuyến đang bật (xem <a href="#tinh-tuyen-lich">Tịnh tuyến lịch</a>).</p>

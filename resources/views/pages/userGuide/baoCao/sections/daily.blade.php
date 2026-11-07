<p>
    Báo cáo một ngày sản xuất (tính từ 06:00 hôm đó tới 06:00 hôm sau) của phân xưởng đang chọn. <b>Chọn Ngày</b> ở đầu trang;
    <b>Xuất Excel</b> tải báo cáo ngày đang xem.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/baoCao/bao-cao-ngay.png', 'caption' => 'Màn hình Báo Cáo › Báo Cáo Ngày SX'])

<h3>Khối Sản Lượng</h3>
<ul>
    <li>Mỗi phòng một dòng: <b>Sản lượng lý thuyết</b> (theo lịch), <b>Sản lượng thực tế</b> (theo Xác Nhận Hoàn Thành), <b>Đáp ứng</b>.</li>
    <li>Cột <b>Chi tiết</b>: danh sách lô của phòng trong ngày, kèm tổng sản lượng <i>xác định</i> / <i>không xác định</i>.</li>
    <li>Bấm biểu tượng sửa để ghi <b>giải trình</b>: <b>Nội Dung Giải Trình</b>, <b>Lý Do</b> (lưu kèm người và ngày ghi chú).</li>
    <li>Thu gọn / mở rộng các phòng bằng nút trên đầu khối.</li>
</ul>

<h3>Bảng tồn công đoạn trước</h3>
<p>Mỗi phòng: <b>Công Đoạn Tiếp Theo</b>, <b>Tồn Thực Tế Công Đoạn trước</b> (bán thành phẩm đã xong công đoạn trước đang chờ vào
    phòng), biểu đồ so sánh. Bấm <b>Chi Tiết</b> để xem <i>Chi Tiết Bán Thành Phẩm Biệt Trữ</i>: Mã / Tên sản phẩm, Số lô, Phòng SX
    công đoạn trước, Sản lượng thực tế, Thời gian SX dự kiến, Phòng biệt trữ.</p>

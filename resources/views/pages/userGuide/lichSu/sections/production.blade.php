<p>
    Tra cứu các lô <b>đã hoàn thành</b>. Chọn khoảng ngày (<b>From / To</b>) và <b>Công đoạn</b>. Bảng: Mã / Tên sản phẩm (kèm mã thị trường), Số lô
    (ưu tiên số lô thực tế, tô xanh), Cỡ lô, Lô thẩm định / thu hồi (badge <b>Thẩm định</b> / <b>Thu hồi dư phẩm</b>), Phòng, Thời gian sản xuất, Thời gian vệ sinh, Sản lượng TT, Phòng BT (biệt trữ), Ghi chú.
</p>
<p>Các mốc thời gian hiển thị bằng nhãn màu giống trang <b>Xác Nhận Hoàn Thành</b> (chỉ xem, không sửa):</p>
<ul>
    <li><b>Thời gian sản xuất</b>: <b>Nhận phòng</b> (lúc nhận phòng sản xuất), <b>BĐCM</b> (bắt đầu tạo ra sản lượng = lần xác
        nhận sản lượng đầu tiên), <b>Kết thúc lô</b>.</li>
    <li><b>Thời gian vệ sinh</b>: <b>BĐVS</b> (bắt đầu vệ sinh), <b>Trả phòng</b> (kết thúc vệ sinh).</li>
    <li><b>Sản lượng TT</b>: liệt kê sản lượng của từng lần xác nhận theo khoảng thời gian, ví dụ
        <code>(1) 06:00 18/11 - 13:20 18/11 = 450</code>, và dòng <b>Tổng</b>.</li>
</ul>
<p>Mốc nào chưa có dữ liệu hiển thị <i>Chưa ghi nhận</i>.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/lichSu/lich-su-san-xuat.png', 'caption' => 'Màn hình Lịch Sử › Lịch Sử Sản Xuất'])

<h3>Trả về (Return)</h3>
<ol class="ug-steps">
    <li>Bấm nút <b>Trả về</b> trên lô xác nhận hoàn thành nhầm.</li>
    <li>Lô quay lại trạng thái <b>chưa hoàn thành</b>: xoá sản lượng, giờ kết thúc sản xuất và giờ bắt đầu vệ sinh để xác nhận lại ở
        Xác Nhận Hoàn Thành / Thực Thi Sản Xuất. <b>Giờ nhận phòng</b> và <b>giờ trả phòng</b> được giữ nguyên.</li>
    <li>Giá trị cũ (sản lượng, thời gian) được lưu ở <b>Lịch Sử Trả Về</b> (người trả về, thời gian).</li>
</ol>
<p>Nút <i class="fas fa-history"></i> xem lịch sử thay đổi của lô.</p>

<p>
    Tra cứu các lô <b>đã hoàn thành</b>. Chọn khoảng ngày (<b>From / To</b>) và <b>Công đoạn</b>. Bảng: Mã / Tên sản phẩm, Cỡ lô, Ngày
    dự kiến KCS, Lô thẩm định, Phòng, Thời gian sản xuất, Thời gian vệ sinh, Sản lượng TT, Phòng BT (biệt trữ), Ghi chú.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/lichSu/lich-su-san-xuat.png', 'caption' => 'Màn hình Lịch Sử › Lịch Sử Sản Xuất'])

<h3>Trả về (Return)</h3>
<ol class="ug-steps">
    <li>Bấm nút <b>Trả về</b> trên lô xác nhận hoàn thành nhầm.</li>
    <li>Lô quay lại trạng thái <b>chưa hoàn thành</b>: xoá giờ thực tế (nhận phòng, kết thúc, vệ sinh) để xác nhận lại ở Xác Nhận
        Hoàn Thành / Thực Thi Sản Xuất.</li>
    <li>Giá trị cũ (sản lượng, thời gian) được lưu ở <b>Lịch Sử Trả Về</b> (người trả về, thời gian).</li>
</ol>
<p>Nút <i class="fas fa-history"></i> xem lịch sử thay đổi của lô.</p>

<p>
    Danh sách <b>kế hoạch tháng</b> của phân xưởng đang chọn. Mỗi dòng cho biết tình trạng, người gửi, sản lượng lý thuyết và
    tiến độ sản xuất (số lô Chưa Làm, Đã Cân, Đã PC, Đã THT, Đã ĐH, Đã BP, Đã ĐG, Hủy - bấm số để xem danh sách lô).
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/keHoach/ke-hoach-san-xuat.png', 'caption' => 'Màn hình Kế Hoạch › Kế Hoạch Sản Xuất'])

<h3>Màn hình danh sách kế hoạch</h3>
<ul>
    <li><span class="btn btn-sm btn-success ug-ui">Thêm Kế Hoạch</span>: nhập <b>Tên Kế Hoạch</b>, <b>Tháng</b>, <b>Năm</b>.</li>
    <li><b>Tìm Kiếm</b>: tìm lô trên mọi kế hoạch.</li>
    <li>Nút <b>Phân bổ Thiết bị</b>: giờ máy cần theo kế hoạch so với năng lực thiết bị.</li>
    <li>Các bảng tổng hợp: <i>Tổng Hợp Kế Hoạch Theo Tháng KCS</i>, <i>Tổng Hợp Tiến Độ Theo Tháng (Thực Tế)</i>, biểu đồ tương
        quan, <i>Danh Sách Lịch Chưa Sắp</i> theo công đoạn.</li>
    <li>Bấm nút ở cột <b>Chi Tiết</b> để mở danh sách lô của kế hoạch.</li>
</ul>

<h3>Thêm lô (nút Thêm trong kế hoạch)</h3>
<ol class="ug-steps">
    <li>Chọn sản phẩm (danh mục thành phẩm): Mã BTP, Mã TP, Qui Cách - Thị Trường, Tên Sản Phẩm, Cỡ Lô tự điền.</li>
    <li>Nhập các ô dưới đây rồi <b>Lưu</b>. Phần mềm hiện công thức nguyên liệu / bao bì của sản phẩm để chọn.</li>
</ol>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/keHoach/ke-hoach-san-xuat-form.png', 'caption' => 'Hộp thoại thêm mới - Kế Hoạch Sản Xuất (Tháng)'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Số lô', true, 'Số lô cần tạo.'],
    ['Ngày dự kiên KCS', true, 'Ngày cần hàng. Lịch Sản Xuất tô đỏ lô kết thúc trễ hơn ngày này.'],
    ['Lô Thẩm Định', false, 'Đánh dấu lô thẩm định (<i>Lô thứ nhất / thứ hai / thứ ba</i>; nhập số lô trước). Lô thẩm định chờ kiểm nghiệm lâu hơn khi sắp lịch.'],
    ['SẢN PHẨM KHUYẾN MÃI', false, 'Hàng khuyến mãi được sắp lịch sau cùng.'],
    ['Mức Độ Ưu Tiên', true, '<b>1</b>: Hàng gấp, hàng thầu · <b>2</b>: Hàng gấp, sắp hết số đăng ký · <b>3</b>: Hàng sản xuất dự trù theo kế hoạch bán hàng, đăng ký thuốc · <b>4</b>: Hàng không cần gấp.'],
    ['Ngày có đủ nguyên liệu Pha Chế', false, 'Để trống nếu chưa xác định. <b>Lô chưa có ngày này thì chưa được sắp lịch tự động.</b>'],
    ['Ngày có đủ bao bì đóng gói', false, 'Mốc sớm nhất cho công đoạn Đóng gói.'],
    ['Ngày được phép cân', false, 'Mặc định theo ngày có đủ NL.'],
    ['Ngày hết hạn nguyên liệu / bao bì', false, 'Pha chế phải bắt đầu trước HH NL chính, Đóng gói trước HH BB. Sắp lịch tự động tự chen lô sắp trễ lên trước.'],
    ['Pha chế / THT / Định Hình / Bao Phim trước ngày', false, 'Hạn của từng công đoạn, nếu có.'],
    ['Ghi Chú', false, ''],
]])

<h3>Bảng lô</h3>
<p>
    Cột <b>Tình Trạng</b> lọc được theo Hủy / Chưa làm / Đã Cân / Đã Pha chế / Đã THT / Đã định hình / Đã Bao phim / Hoàn Tất ĐG.
    Ô <b>Ưu Tiên/KH</b> sửa trực tiếp (số nguyên dương) - đây là thứ tự ưu tiên khi sắp lịch nhóm thương mại.
    Nút <span class="btn btn-sm btn-info ug-ui"><i class="fas fa-history"></i></span> xem lịch sử thay đổi của lô.
</p>

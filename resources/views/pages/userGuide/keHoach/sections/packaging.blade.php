<p>
    Thông báo đóng gói theo kế hoạch tháng: trang đầu liệt kê kế hoạch tháng (Số Lượng lô, Đã Lấy Mẫu); bấm <b>Chi Tiết</b> để mở
    lưới nhập chia 3 tab <b>Sản Phẩm Châu Âu</b>, <b>Sản Phẩm Ngoài Châu Âu</b>, <b>Sản Phẩm Việt Nam</b> (theo cờ <i>Thuộc Châu
    Âu</i> của thị trường ở Dữ Liệu Gốc). Mọi lô còn hiệu lực của kế hoạch đều có trên lưới.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/keHoach/thong-bao-dong-goi.png', 'caption' => 'Màn hình Kế Hoạch › Thông Báo Đóng Gói'])

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Số PO', false, 'Tối đa 50 ký tự. Cần quyền nhập Số PO.'],
    ['Mẫu sơ cấp / Mẫu thứ cấp', false, 'Thông tin lấy mẫu, tối đa 2000 ký tự. Cần quyền lấy mẫu.'],
    ['Đã lấy mẫu', false, 'Đánh dấu đã lấy mẫu.'],
    ['Lý do', false, 'Tối đa 255 ký tự.'],
]])
<p class="mt-2">Dòng được lưu ngay khi một ô đổi giá trị; biểu tượng lịch sử xem các lần sửa.</p>

<h3>Khoá / mở khoá</h3>
<p>Người có quyền khoá có thể khoá các cột Số PO và lấy mẫu của lô để không ai sửa nữa. <b>Mở khoá bắt buộc nhập lý do.</b></p>

<p>Danh sách thị trường tiêu thụ của thành phẩm.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/thi-truong.png', 'caption' => 'Màn hình Dữ Liệu Gốc › Thị Trường'])

@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/thi-truong-form.png', 'caption' => 'Hộp thoại thêm mới - Thị Trường'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Mã Thị Trường', true, 'Mã ngắn, ví dụ <i>VN</i>. Không trùng.'],
    ['Tên Thị Trường', true, 'Ví dụ <i>Việt Nam</i>. Không trùng.'],
    ['Thuộc Châu Âu', false, 'Đánh dấu nếu là thị trường châu Âu. Dùng để tách 2 tab <b>Sản Phẩm Châu Âu / Ngoài Châu Âu</b> ở trang Kế Hoạch › Thông Báo Đóng Gói.'],
]])

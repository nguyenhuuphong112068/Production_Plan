<p>Danh sách phòng ban. Tài khoản người dùng gắn với một phòng ban; phân xưởng mặc định khi đăng nhập lấy theo phòng ban này.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/phong-ban.png', 'caption' => 'Màn hình Dữ Liệu Gốc › Phòng Ban'])

@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/phong-ban-form.png', 'caption' => 'Hộp thoại thêm mới - Phòng Ban'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Viết Tắt', true, 'Mã ngắn, ví dụ <i>PXV1</i>. Không trùng.'],
    ['Tên Phòng Ban', true, 'Không trùng.'],
]])

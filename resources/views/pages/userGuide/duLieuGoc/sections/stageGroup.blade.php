<p>Danh sách tổ quản lý phòng sản xuất (tổ Pha chế, tổ Đóng gói...). Mỗi phòng sản xuất thuộc một tổ.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/to-quan-ly.png', 'caption' => 'Màn hình Dữ Liệu Gốc › Tổ Quản Lý'])

@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/to-quan-ly-form.png', 'caption' => 'Hộp thoại thêm mới - Tổ Quản Lý'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Mã Tổ', true, 'Không trùng.'],
    ['Tên Tổ', true, 'Không trùng.'],
]])

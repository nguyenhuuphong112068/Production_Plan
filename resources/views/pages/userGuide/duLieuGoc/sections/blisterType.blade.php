<p>Danh sách loại máy ép vỉ. Phòng Đóng gói chọn loại máy; khuôn khai loại máy lắp được - hai thông tin này quyết định khuôn nào chạy được ở phòng nào.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/loai-may-ep-vi.png', 'caption' => 'Màn hình Dữ Liệu Gốc › Loại Máy Ép Vỉ'])

@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/loai-may-ep-vi-form.png', 'caption' => 'Hộp thoại thêm mới - Loại Máy Ép Vỉ'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Loại Máy Ép Vỉ', true, 'Không trùng.'],
    ['Mã Nhóm Máy (Code)', true, 'Số nguyên (1, 2, 3...). Đây là mã được chọn ở ô Loại Máy Ép Vỉ của phòng Đóng gói và của khuôn.'],
]])
<p class="mt-2">Lọc bảng bằng nút <b>Đang Hoạt Động</b> / <b>Đã Vô Hiệu</b>. Màn hình chỉ hiện trên menu với tài khoản có quyền tương ứng.</p>

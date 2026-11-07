<p>Danh sách qui cách đóng gói (ví dụ hộp 10 vỉ x 10 viên) dùng khi khai báo thành phẩm.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/qui-cach.png', 'caption' => 'Màn hình Dữ Liệu Gốc › Qui Cách Sản Phẩm'])

@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/qui-cach-form.png', 'caption' => 'Hộp thoại thêm mới - Qui Cách Sản Phẩm'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Qui Cách', true, 'Mô tả qui cách. Không trùng qui cách đã có.'],
]])

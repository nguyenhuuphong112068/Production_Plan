<p>Danh sách tên sản phẩm dùng khi khai báo danh mục Bán Thành Phẩm và Thành Phẩm.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/ten-san-pham.png', 'caption' => 'Màn hình Dữ Liệu Gốc › Tên Sản Phẩm'])

@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/ten-san-pham-form.png', 'caption' => 'Hộp thoại thêm mới - Tên Sản Phẩm'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Sản Phẩm', true, 'Tên đầy đủ, tối đa 255 ký tự.'],
    ['Tên viết tắt', true, 'Tên ngắn hiện trên lịch và báo cáo.'],
    ['Loại Sản Phẩm', true, 'Chọn <b>Thành Phẩm</b>, <b>Nguyên liệu xữ lý</b> hoặc <b>Bulk</b>.'],
]])

<p class="mt-2">Bảng có thêm cột Người Tạo / Ngày Tạo; nút sửa <span class="btn btn-sm btn-warning ug-ui"><i class="fas fa-edit"></i></span>,
    vô hiệu <span class="btn btn-sm btn-danger ug-ui"><i class="fas fa-lock"></i></span>, lịch sử
    <span class="btn btn-sm btn-info ug-ui"><i class="fas fa-history"></i></span>.</p>

<p>Đơn vị tính dùng cho số lượng lô, sản lượng, tồn.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/don-vi-tinh.png', 'caption' => 'Màn hình Dữ Liệu Gốc › Đơn Vị Tính'])

@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/don-vi-tinh-form.png', 'caption' => 'Hộp thoại thêm mới - Đơn Vị Tính'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Đơn Vị Tính', true, 'Ký hiệu ngắn, ví dụ <i>Kg</i>. Không trùng đơn vị đã có.'],
    ['Đơn Vị Tính Đầy Đủ', true, 'Tên đầy đủ, ví dụ <i>Kilogram</i>. Không trùng.'],
]])

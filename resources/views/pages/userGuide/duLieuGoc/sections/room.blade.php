<p>
    Danh sách phòng sản xuất của phân xưởng đang chọn, sắp theo công đoạn. Mỗi phòng là một dòng trên biểu đồ Lịch Sản Xuất;
    phòng chỉ được xếp lô khi có định mức cho sản phẩm (xem Định Mức › Sản Xuất).
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/phong-san-xuat.png', 'caption' => 'Màn hình Dữ Liệu Gốc › Phòng Sản Xuất'])

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/phong-san-xuat-form.png', 'caption' => 'Hộp thoại thêm mới - Phòng Sản Xuất'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Mã Phòng', true, 'Mã phòng, không trùng. <b>Không sửa được</b> sau khi tạo.'],
    ['Tên Phòng', true, 'Không trùng. Không sửa được sau khi tạo.'],
    ['Tên Thiết Bị Chính', true, 'Thiết bị chính của phòng.'],
    ['Công Suất Máy', true, 'Công suất theo đơn vị / giờ, dùng cho báo cáo năng suất.'],
    ['Công Đoạn', true, 'Công đoạn phòng chạy (Cân, Pha chế, Trộn hoàn tất, Định hình, Bao phim, Đóng gói...). Không sửa được sau khi tạo.'],
    ['Loại Máy Ép Vỉ', false, 'Chỉ với phòng Đóng gói có máy ép vỉ: dùng để chọn khuôn phù hợp khi phân bổ khuôn.'],
    ['Tổ Quản Lý', true, 'Tổ phụ trách phòng (danh sách ở <a href="#to-quan-ly">Tổ Quản Lý</a>). Không sửa được sau khi tạo.'],
]])

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Phòng mới tạo thuộc phân xưởng đang chọn. Khai sai Mã / Tên / Công đoạn / Tổ thì không sửa được: vô hiệu phòng đó và
        tạo phòng mới. Phòng đã vô hiệu không còn hiện trên lịch và danh sách chọn phòng.</div>
</div>

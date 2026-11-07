<p>
    Danh mục thiết bị cần <b>Hiệu Chuẩn</b>, <b>Bảo Trì</b>, <b>Tiện Ích</b> của khối <b>B1</b> và <b>B2</b> (6 mục trên menu,
    cùng một màn hình lọc theo khối và loại). Danh sách thiết bị và tần suất được <b>đồng bộ tự động</b> từ hệ thống quản lý
    hiệu chuẩn - bảo trì mỗi khi mở màn hình; người dùng chỉ bổ sung thông tin phục vụ sắp lịch.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/danhMuc/danh-muc-bt-hc.png', 'caption' => 'Màn hình Danh Mục › BT - HC B1 / B2 › Hiệu Chuẩn / Bảo Trì / Tiện Ích'])

<h3>Cột trên bảng</h3>
<p>Mã / Tên Thiết Bị Lớn, Mã / Tên Thiết Bị Con, Tần Suất BT-HC, Vị Trí Lắp Đặt, Phân Xưởng, Phòng SX Liên Quan, Thời gian Thực Hiện.</p>

<h3>Thông tin sửa được ngay trên bảng</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Thời gian Thực Hiện', false, 'Dạng <b>hh:mm</b> (ví dụ 01:30; giờ 0-99, phút 0-59). Là độ dài của lịch BT-HC-TI trên Lịch HC-BT và Lịch Sản Xuất.'],
    ['Phân Xưởng', false, 'Phân xưởng sở hữu thiết bị.'],
    ['Phòng SX Liên Quan', false, 'Phòng sản xuất bị chiếm khi làm BT-HC-TI (chọn <i>Tất cả phòng trong phân xưởng</i> nếu ảnh hưởng toàn xưởng). Lịch sẽ hiện trên dòng của phòng này.'],
    ['HVAC', false, 'Đánh dấu thiết bị thuộc hệ thống HVAC.'],
]])
<p class="mt-2">Mỗi thay đổi lưu ngay và báo "Cập nhật ... thành công". Nút vô hiệu để bỏ thiết bị khỏi danh mục (có hỏi xác nhận).</p>

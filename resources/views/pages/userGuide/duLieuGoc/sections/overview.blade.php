<p>
    Dữ Liệu Gốc là các danh mục nền mà mọi chức năng khác chọn lại: danh mục sản phẩm, định mức, kế hoạch và lịch sản xuất đều
    tham chiếu tới đây. Khai báo đúng và đủ trước khi làm các bước sau.
</p>

<h3>Thứ tự khai báo khuyến nghị</h3>
<div class="ug-flow">
    <div class="ug-flow-step"><span class="ug-flow-no">1</span><b>Tổ chức</b>
        <a href="#phong-ban">Phòng Ban</a>, <a href="#to-quan-ly">Tổ Quản Lý</a>.</div>
    <div class="ug-flow-step"><span class="ug-flow-no">2</span><b>Danh mục dùng chung</b>
        <a href="#don-vi-tinh">Đơn Vị Tính</a>, <a href="#dang-bao-che">Dạng Bào Chế</a>, <a href="#thi-truong">Thị Trường</a>,
        <a href="#qui-cach">Qui Cách</a>, <a href="#ten-san-pham">Tên Sản Phẩm</a>.</div>
    <div class="ug-flow-step"><span class="ug-flow-no">3</span><b>Thiết bị</b>
        <a href="#loai-may-ep-vi">Nhóm Máy</a>, <a href="#khuon-mau">Khuôn Mẫu</a>.</div>
    <div class="ug-flow-step"><span class="ug-flow-no">4</span><b>Phòng</b>
        <a href="#phong-san-xuat">Phòng Sản Xuất</a>, <a href="#lien-ket-phong">Liên Kết Phòng</a>.</div>
    <div class="ug-flow-step"><span class="ug-flow-no">5</span><b>Lịch làm việc</b>
        <a href="#ngay-nghi">Ngày Nghỉ Công Ty</a> tự lấy từ eO2, không cần khai báo.</div>
</div>

<p class="mt-3">
    Sau Dữ Liệu Gốc là <a href="{{ route('pages.userGuide.show', 'danhMuc') }}">Danh Mục</a> (bán thành phẩm, thành phẩm) và
    <a href="{{ route('pages.userGuide.show', 'dinhMuc') }}">Định Mức</a> (phòng và thời gian sản xuất từng sản phẩm).
    Các nút Thêm / Sửa / Vô hiệu / Lịch sử dùng chung: xem
    <a href="{{ route('pages.userGuide.show', 'batDau') }}#thao-tac-chung">Thao tác chung</a>.
</p>

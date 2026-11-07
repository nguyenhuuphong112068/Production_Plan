<p>
    Phần mềm không cấp quyền cho từng người mà cấp theo <b>nhóm quyền</b> (vai trò). Mỗi tài khoản được gán một hoặc nhiều
    nhóm quyền. Trong các nhóm được gán, nhóm <b>đứng trên cùng</b> ở danh sách Nhóm Phân Quyền là <b>nhóm quyền chính</b>
    (không phụ thuộc thứ tự bấm chọn), hiện ở góc phải thanh trên cùng (🛡️).
</p>

<h3>Quyền của bạn được tính thế nào</h3>
<ol class="ug-steps">
    <li>Gộp mọi quyền đã tick cho <b>các nhóm quyền đang hoạt động</b> của bạn ở <b>User Policy › Nhóm Phân Quyền</b>.
        Nhóm quyền đã bị vô hiệu không cấp quyền nào.</li>
    <li>Áp <b>quyền riêng</b> của tài khoản (nút ở cột <b>Quyền</b> của Danh Sách User): <b>Cho phép</b> thì thêm quyền,
        <b>Từ chối</b> thì bỏ quyền dù nhóm quyền có.</li>
    <li>Một số chức năng còn xét thêm <b>tên nhóm quyền chính</b>, <b>phòng ban</b> của tài khoản hoặc <b>phân xưởng</b> đang
        chọn (xem <a href="#nhom-quyen-dac-biet">Nhóm quyền &amp; phòng ban đặc biệt</a>). Các điều kiện này nằm trong mã, không
        tick ở Nhóm Phân Quyền được.</li>
</ol>

<h3>Ba kiểu quyền</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead><tr><th style="width: 200px">Kiểu</th><th>Tác dụng</th></tr></thead>
        <tbody>
            <tr><td>Thấy menu</td><td style="font-weight: 400">Hiện nhóm menu hoặc mục menu trên thanh trái (ví dụ <i>Chức Năng Xem Báo Cáo Ngày</i>, <i>Chức Năng User</i>). Nhiều màn hình không có quyền này: mọi tài khoản đều thấy.</td></tr>
            <tr><td>Thao tác</td><td style="font-weight: 400">Hiện / bật nút Thêm, Cập nhật, Vô hiệu, Gửi, Duyệt, Nhận phòng... Thiếu quyền thì nút không hiện hoặc bị mờ.</td></tr>
            <tr><td>Điều kiện cứng</td><td style="font-weight: 400">Theo tên nhóm quyền chính, phòng ban, phân xưởng (ví dụ sửa Lịch Sản Xuất Chart cần nhóm quyền chính Admin / Schedualer của đúng phân xưởng).</td></tr>
        </tbody>
    </table>
</div>

<h3>Phân xưởng và phòng ban</h3>
<ul>
    <li>Khi đăng nhập, phần mềm chọn sẵn phân xưởng theo phòng ban của tài khoản. Bạn chuyển được sang phân xưởng khác
        ở menu trái để <b>xem</b>.</li>
    <li>Các chức năng sửa lịch (Lịch Sản Xuất Chart, Khóa TTSX) chỉ cho sửa khi phân xưởng đang chọn <b>trùng phòng ban</b>
        của tài khoản; phòng ban BOD sửa được mọi phân xưởng.</li>
    <li>Quyền tick ở Nhóm Phân Quyền thì <b>không</b> đổi theo phân xưởng: có quyền ở phân xưởng này là có ở mọi phân xưởng.</li>
</ul>

<h3>Tìm quyền của một chức năng</h3>
<p>
    Mỗi tài liệu hướng dẫn có mục <b>Phân Quyền</b> ở cuối: bảng từng thao tác cần quyền gì và nhóm quyền nào đang được phép.
    Phần <a href="#bang-duLieuGoc">Bảng phân quyền chung</a> của tài liệu này gom các bảng đó thành ma trận nhóm quyền × quyền
    theo từng nhóm menu. Mọi bảng đọc trực tiếp cấu hình hiện tại lúc mở trang.
</p>

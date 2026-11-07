<p>
    Mở địa chỉ phần mềm, trang đăng nhập hiện ra. Mỗi người dùng một tài khoản riêng do quản trị tạo ở
    <b>User Policy › Danh Sách User</b>.
</p>

<h3>Các bước đăng nhập</h3>
<ol class="ug-steps">
    <li>Nhập <b>Tên đăng nhập</b> (thường là mã số nhân viên) và <b>Mật khẩu</b>.</li>
    <li>Bấm đăng nhập. Phần mềm chọn sẵn phân xưởng theo phòng ban của tài khoản (xem <a href="#phan-xuong">Chọn phân xưởng</a>).</li>
    <li>Lần đăng nhập đầu tiên hoặc khi mật khẩu đã hết hạn, trang chuyển sang khung <b>Thiết lập mật khẩu mới</b>:
        đổi mật khẩu rồi đăng nhập lại (xem <a href="#mat-khau">Mật khẩu</a>).</li>
</ol>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Tài khoản nhóm quyền <b>Executor</b> (người ghi nhận sản xuất tại phòng) chỉ vào được màn hình Ghi nhận sản xuất;
        các màn hình khác tự chuyển về đó.</div>
</div>

<h3>Đăng xuất</h3>
<ul>
    <li>Bấm biểu tượng <i class="fas fa-sign-out-alt text-primary"></i> ở góc phải thanh trên cùng.</li>
    <li><b>Tự đăng xuất:</b> không thao tác (chuột, bàn phím) trong <b>{{ config('session.lifetime') }} phút</b> thì phần mềm
        tự quay về trang đăng nhập. Dữ liệu chưa lưu trên màn hình sẽ mất.</li>
</ul>

<h3>Câu báo lỗi khi đăng nhập</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead><tr><th style="width: 380px">Câu báo</th><th>Nguyên nhân / cách xử lý</th></tr></thead>
        <tbody>
            <tr><td>User Không Tồn Tại, Vui Lòng Đăng Nhập Lại!</td><td>Sai tên đăng nhập.</td></tr>
            <tr><td>PassWord Không Chính Xác. Còn n Lần Thử...</td><td>Sai mật khẩu; hết số lần thử thì tài khoản bị khoá tạm.</td></tr>
            <tr><td>Tài Khoản Đã Bị Khoá Do Nhập Sai Nhiều Lần...</td><td>Chờ hết thời gian khoá (câu báo ghi số phút còn lại) rồi thử lại.</td></tr>
            <tr><td>Tài Khoản Đã Bị Vô Hiệu Hoá...</td><td>Tài khoản đã bị khoá ở Danh Sách User. Liên hệ quản trị.</td></tr>
        </tbody>
    </table>
</div>
<p class="text-muted small">Mọi lần đăng nhập thành công / thất bại đều được ghi lại, xem ở nhóm menu <b>Lịch Sử Đăng Nhập</b>.</p>

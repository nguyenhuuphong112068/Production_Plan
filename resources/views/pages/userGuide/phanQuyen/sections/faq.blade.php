<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead><tr><th style="width: 320px">Hiện tượng</th><th>Nguyên nhân / cách xử lý</th></tr></thead>
        <tbody>
            <tr><td>Không thấy một nhóm menu / mục menu</td><td style="font-weight: 400">Thiếu quyền "thấy menu" của mục đó. Tra mục Phân Quyền của tài liệu tương ứng để biết tên quyền, nhờ quản trị cấp.</td></tr>
            <tr><td>Thấy màn hình nhưng không có nút Thêm / Cập nhật, hoặc nút bị mờ</td><td style="font-weight: 400">Thiếu quyền thao tác. Một số nút còn đòi đúng phòng ban (ví dụ kế hoạch Hiệu chuẩn chỉ phòng QA).</td></tr>
            <tr><td>Được cấp nhóm Schedualer mà vẫn không sửa được Lịch Sản Xuất Chart</td><td style="font-weight: 400">
                <ul class="mb-0">
                    <li>Nhóm quyền chính không phải Schedualer: tài khoản được gán thêm nhóm đứng trên Schedualer (Admin, Planner, Viewer). Xem biểu tượng 🛡️ ở góc phải thanh trên cùng.</li>
                    <li>Phân xưởng đang chọn khác phòng ban của tài khoản: chuyển về đúng phân xưởng.</li>
                    <li>Vừa đổi nhóm quyền: đăng xuất rồi đăng nhập lại.</li>
                </ul></td></tr>
            <tr><td>Chuyển sang phân xưởng khác thì không sửa được nữa</td><td style="font-weight: 400">Đúng thiết kế: phân xưởng khác phòng ban của bạn chỉ để xem (trừ phòng ban BOD).</td></tr>
            <tr><td>Đã cho quyền ở nhóm quyền mà một người vẫn không có</td><td style="font-weight: 400">Người đó đang bị <b>Từ chối</b> quyền này ở quyền riêng, hoặc nhóm quyền đã bị vô hiệu. Kiểm tra nút ở cột Quyền của Danh Sách User.</td></tr>
            <tr><td>Đổi tên nhóm quyền xong, nhiều người mất quyền</td><td style="font-weight: 400">Nhóm đó được kiểm tra theo tên (xem <a href="#nhom-quyen-dac-biet">Nhóm quyền đặc biệt</a>). Đổi lại đúng tên cũ.</td></tr>
            <tr><td>Không thấy menu Hướng Dẫn Sử Dụng / nút ? trên thanh trên cùng</td><td style="font-weight: 400">Thiếu quyền <b>Xem Hướng Dẫn Sử Dụng</b> (nhóm Chức Năng Chung (Menu)). Nhờ quản trị tick cho nhóm quyền của bạn hoặc cấp riêng.</td></tr>
            <tr><td>Tài khoản bị khoá</td><td style="font-weight: 400">Nhập sai mật khẩu {{ config('security.max_login_attempts', 5) }} lần: chờ {{ config('security.lockout_minutes', 15) }} phút. Tài khoản bị vô hiệu: liên hệ quản trị.</td></tr>
        </tbody>
    </table>
</div>

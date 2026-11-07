<p>21 CFR Part 11 là quy định của FDA (Hoa Kỳ) về <b>hồ sơ điện tử</b> và <b>chữ ký điện tử</b> trong môi trường GMP: hồ sơ trên
    máy phải đáng tin cậy như hồ sơ giấy. Bảng dưới tóm tắt các yêu cầu chính và cách phần mềm đáp ứng, để bạn biết vì sao có
    những ràng buộc như khoá tài khoản, tự đăng xuất hay bắt nhập lý do thay đổi.</p>

<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr>
                <th style="width: 120px">Điều khoản</th>
                <th style="width: 220px">Yêu cầu</th>
                <th>Phần mềm đáp ứng thế nào</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>§11.10(d)</td>
                <td>Chỉ người được phép mới truy cập</td>
                <td style="font-weight: 400">Mỗi người một tài khoản riêng. Quyền theo nhóm quyền (xem
                    <a href="#tong-quan">Cách phần mềm phân quyền</a>); menu và nút không có quyền thì không hiện. Tự đăng xuất
                    sau <b>{{ config('session.lifetime') }} phút</b> không thao tác.</td>
            </tr>
            <tr>
                <td>§11.10(e)</td>
                <td>Nhật ký truy vết có dấu thời gian, do máy tự ghi</td>
                <td style="font-weight: 400"><b>Audit Trail</b> (nhóm menu Lịch Sử Đăng Nhập) tự ghi người thực hiện, thời điểm,
                    địa chỉ IP, thao tác, giá trị cũ và mới của đăng nhập và các thay đổi dữ liệu gốc, danh mục, kế hoạch, thực thi
                    sản xuất. Thay đổi lịch được ghi riêng ở <b>Lịch Sử Thay Đổi Lịch</b>. Không có màn hình nào sửa hay xoá nhật ký.</td>
            </tr>
            <tr>
                <td>§11.10(e)</td>
                <td>Thay đổi hồ sơ không che mất thông tin cũ</td>
                <td style="font-weight: 400">Cập nhật danh mục Bán Thành Phẩm, Thành Phẩm bắt buộc nhập <b>Lý do thay đổi</b>, lưu kèm lịch sử.
                    Dữ liệu không dùng nữa được <b>vô hiệu</b>, không xoá.</td>
            </tr>
            <tr>
                <td>§11.10(g)</td>
                <td>Kiểm tra thẩm quyền trước khi thao tác</td>
                <td style="font-weight: 400">Mỗi thao tác cần quyền riêng (bảng ở mục Phân Quyền của từng tài liệu). Các thao tác
                    ảnh hưởng sản xuất như Nhận / Trả phòng, khoá thực thi được kiểm tra lại khi lưu.</td>
            </tr>
            <tr>
                <td>§11.10(i)</td>
                <td>Người dùng được đào tạo</td>
                <td style="font-weight: 400">Hướng dẫn sử dụng này (nút <b>?</b> trên thanh trên cùng mở đúng mục của màn hình đang
                    xem) là tài liệu tham khảo; đào tạo theo quy trình của công ty.</td>
            </tr>
            <tr>
                <td>§11.100</td>
                <td>Mỗi tài khoản là duy nhất, không cấp lại cho người khác</td>
                <td style="font-weight: 400">Tên đăng nhập không trùng. Tài khoản người nghỉ việc chỉ bị vô hiệu hoá, không xoá.</td>
            </tr>
            <tr>
                <td>§11.300</td>
                <td>Kiểm soát mật khẩu</td>
                <td style="font-weight: 400">Mật khẩu đủ phức tạp, hết hạn sau <b>{{ config('security.password_expiry_days', 90) }} ngày</b>,
                    không dùng lại <b>{{ config('security.password_history_count', 3) }}</b> mật khẩu gần nhất, tài khoản mới phải đổi
                    mật khẩu ở lần đăng nhập đầu, khoá <b>{{ config('security.lockout_minutes', 15) }} phút</b> sau
                    <b>{{ config('security.max_login_attempts', 5) }}</b> lần sai, lưu dạng mã hoá một chiều. Mọi lần đăng nhập thành
                    công / thất bại được ghi vào Lịch Sử Đăng Nhập.</td>
            </tr>
        </tbody>
    </table>
</div>

<p class="text-muted small">Phần mềm không dùng chữ ký điện tử theo §11.50 / §11.200: các bước duyệt, xác nhận ghi lại tên tài
    khoản và thời điểm thực hiện, không thay cho chữ ký trên hồ sơ giấy.</p>

<h3>Trách nhiệm của người dùng</h3>
<ol class="ug-steps">
    <li>Giữ bí mật mật khẩu, không dùng chung tài khoản, không thao tác bằng tài khoản người khác.</li>
    <li>Đăng xuất hoặc khoá máy khi rời chỗ.</li>
    <li>Nhập <b>lý do thay đổi</b> rõ ràng, đúng sự thật.</li>
    <li>Thấy đăng nhập lạ, đổi mật khẩu ngay và báo quản trị.</li>
</ol>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Mật khẩu, khoá tài khoản, đăng xuất: xem <a href="{{ route('pages.userGuide.show', 'batDau') }}#mat-khau">Bắt Đầu ›
        Mật khẩu</a>. Xem nhật ký: <a href="{{ route('pages.userGuide.show', 'dangNhap') }}">Hướng dẫn Lịch Sử Đăng Nhập</a>.
        Các quy trình ngoài phần mềm (thẩm định hệ thống, đào tạo, cam kết chữ ký điện tử) do quy trình chất lượng của công ty
        quy định.</div>
</div>

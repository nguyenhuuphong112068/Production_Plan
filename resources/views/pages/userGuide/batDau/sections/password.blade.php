<p>
    Đổi mật khẩu ở trang đăng nhập (khung <b>Cập nhật thông tin bảo mật tài khoản</b>). Phần mềm tự chuyển sang khung này khi
    đăng nhập lần đầu hoặc mật khẩu đã hết hạn.
</p>

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên đăng nhập', true, 'Tài khoản cần đổi mật khẩu.'],
    ['Mật khẩu hiện tại', true, 'Mật khẩu đang dùng (với tài khoản mới: mật khẩu quản trị cấp).'],
    ['Mật khẩu mới', true, 'Ít nhất <b>6 ký tự</b>, có đủ <b>chữ thường, chữ hoa, chữ số và ký tự đặc biệt</b>. Không được trùng mật khẩu hiện tại và <b>' . config('security.password_history_count', 3) . ' mật khẩu gần nhất</b>.'],
    ['Nhập lại mật khẩu mới', true, 'Gõ lại đúng mật khẩu mới.'],
]])

<h3>Chính sách mật khẩu</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead><tr><th style="width: 260px">Quy định</th><th>Giá trị hiện tại</th></tr></thead>
        <tbody>
            <tr><td>Hiệu lực mật khẩu</td><td><b>{{ config('security.password_expiry_days', 90) }} ngày</b> kể từ lần đổi; quá hạn phải đổi khi đăng nhập.</td></tr>
            <tr><td>Nhập sai liên tiếp</td><td><b>{{ config('security.max_login_attempts', 5) }} lần</b> thì tài khoản bị khoá.</td></tr>
            <tr><td>Thời gian khoá</td><td><b>{{ config('security.lockout_minutes', 15) }} phút</b>, sau đó tự mở khoá.</td></tr>
            <tr><td>Mật khẩu cũ không dùng lại</td><td>{{ config('security.password_history_count', 3) }} mật khẩu gần nhất.</td></tr>
        </tbody>
    </table>
</div>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Quên mật khẩu: liên hệ quản trị đặt lại ở <b>User Policy › Danh Sách User</b>. Nên đổi lại mật khẩu riêng sau khi được
        cấp mật khẩu tạm.</div>
</div>

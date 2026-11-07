<p>
    Danh sách tài khoản: Tên Đăng Nhập, Nhóm Người Dùng, Tên Người Dùng, Phòng Ban, Tổ, Mail, Trạng Thái, Quyền Riêng.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/userPolicy/danh-sach-user.png', 'caption' => 'Màn hình User Policy › Danh Sách User'])

<h3>Các ô nhập (Thêm / Edit)</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/userPolicy/danh-sach-user-form.png', 'caption' => 'Hộp thoại thêm mới - Danh Sách User'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Đăng Nhập', true, '<b>Mã số nhân viên</b>, 5-10 ký tự, không trùng. Trang Ghi Nhận Sản Xuất và phân công dựa vào mã này.'],
    ['Mật Khẩu', true, 'Khi thêm: ít nhất 6 ký tự, có chữ hoa, chữ thường, số và ký tự đặc biệt. Khi sửa: để trống nếu không đổi; nhập mật khẩu mới để đặt lại. Tài khoản <b>mới tạo</b> phải đổi mật khẩu ở lần đăng nhập đầu tiên; mật khẩu đặt lại khi sửa thì không bắt đổi ngay.'],
    ['Tên Người Dùng', true, 'Họ tên đầy đủ, 5-255 ký tự.'],
    ['Phân Quyền (Chọn 1 hoặc nhiều)', true, 'Các nhóm quyền của tài khoản.'],
    ['Tổ', true, ''],
    ['Phòng Ban', true, 'Quyết định phân xưởng mặc định khi đăng nhập và quyền sửa theo phân xưởng.'],
    ['Mail', true, 'Không có mail thì nhập <b>NA</b>.'],
]])

<h3>Quyền riêng</h3>
<p>Bấm nút ở cột <b>Quyền</b> để mở <b>QUYỀN RIÊNG</b> của tài khoản: mỗi quyền hiện <i>Từ Nhóm Quyền</i> (đã có qua nhóm),
    <i>Cấp Riêng</i> (tick để cấp thêm) và <i>Kết Quả</i>. Tìm quyền theo tên hoặc mã; <i>Chỉ quyền đang cấp riêng</i> để lọc. Bấm <b>Lưu</b>.</p>

<h3>Vô hiệu / kích hoạt lại</h3>
<p>Nút <b>DeActive</b> khoá tài khoản (không đăng nhập được, có hỏi xác nhận); nút <b>Kích hoạt lại</b> mở lại.</p>

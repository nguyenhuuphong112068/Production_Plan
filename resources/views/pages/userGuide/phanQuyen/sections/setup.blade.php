<p>Chỉ tài khoản có quyền <b>Chức Năng User</b> mới thấy nhóm menu <b>User Policy</b> để làm các bước dưới. Chi tiết từng
    ô nhập xem <a href="{{ route('pages.userGuide.show', 'userPolicy') }}">Hướng dẫn User Policy</a>.</p>

<h3>Cấp quyền cho người mới</h3>
<ol class="ug-steps">
    <li>Tra mục <b>Phân Quyền</b> của tài liệu hướng dẫn ứng với công việc của người đó để biết cần quyền nào, nhóm quyền nào
        đang có sẵn các quyền ấy.</li>
    <li>Nếu chưa có nhóm phù hợp: <b>User Policy › Nhóm Phân Quyền</b>, tạo nhóm mới và tick quyền. Nên tạo theo vai trò công
        việc (người lập lịch, tổ trưởng...), không tạo nhóm cho từng người.</li>
    <li><b>User Policy › Danh Sách User</b>, thêm tài khoản: tên đăng nhập (mã nhân viên), phòng ban, tổ, mật khẩu tạm và
        <b>nhóm quyền</b>. Kiểm tra nhóm đứng trên cùng trong các nhóm đã chọn là đúng <b>nhóm quyền chính</b> mong muốn
        (xem <a href="#nhom-quyen-dac-biet">Nhóm quyền đặc biệt</a>).</li>
    <li>Cần thêm / bớt một vài quyền lẻ cho riêng người đó: bấm nút ở cột <b>Quyền</b>, chọn <b>Cho phép</b> hoặc
        <b>Từ chối</b> từng quyền.</li>
    <li>Báo người dùng đăng nhập bằng mật khẩu tạm; phần mềm bắt đổi mật khẩu ở lần đăng nhập đầu.</li>
</ol>

<h3>Khi người dùng đổi công việc hoặc nghỉ việc</h3>
<ul>
    <li><b>Đổi công việc:</b> sửa nhóm quyền của tài khoản, rà lại quyền riêng còn cần không.</li>
    <li><b>Nghỉ việc:</b> <b>vô hiệu</b> tài khoản ở Danh Sách User. Tài khoản không bị xoá để lịch sử thao tác vẫn gắn đúng
        người; tên đăng nhập không cấp lại cho người khác.</li>
    <li>Định kỳ rà các nhóm quyền và quyền riêng, gỡ quyền không còn cần (xem <a href="#bang-duLieuGoc">Bảng phân quyền chung</a>).</li>
</ul>

<div class="ug-callout is-warn">
    <i class="fas fa-sync-alt"></i>
    <div>Quyền mới cấp có hiệu lực từ lần mở trang kế tiếp. Riêng <b>nhóm quyền chính</b> được đọc lúc đăng nhập: đổi nhóm quyền
        thì người dùng cần <b>đăng xuất rồi đăng nhập lại</b>.</div>
</div>

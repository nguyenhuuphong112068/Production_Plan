<p>
    Ma trận <b>quyền × nhóm quyền</b>: mỗi cột là một nhóm quyền (kèm số người dùng), mỗi dòng là một quyền, gom theo nhóm chức năng.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/userPolicy/nhom-phan-quyen.png', 'caption' => 'Màn hình User Policy › Nhóm Phân Quyền'])
<ul>
    <li>Tick ô để cấp / bỏ quyền cho nhóm. Tick ở đầu dòng: cấp quyền đó cho <b>tất cả nhóm đang hiện</b> (đã tick hết thì bỏ hết, có hỏi xác nhận).</li>
    <li>Tìm quyền theo tên hoặc mã; <b>Chỉ quyền đã cấp</b> để lọc; chọn các nhóm quyền cần hiện (<b>Tất cả nhóm quyền</b>);
        <b>Thu gọn tất cả</b> các nhóm chức năng. Badge cho biết số quyền trong nhóm chức năng và số quyền đã tick của từng nhóm quyền.</li>
    <li>Cột của nhóm <b>Admin</b> được tô riêng: Admin có toàn quyền.</li>
</ul>

<h3>Quản Lý Nhóm Quyền</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Nhóm Quyền', true, 'Không trùng, ví dụ <i>QC Manager</i>.'],
    ['Diễn Giải', false, 'Mô tả ngắn.'],
]])
<p class="mt-2">Bấm <b>Quản Lý Nhóm Quyền</b> để thêm, sửa (<b>Huỷ sửa</b> để bỏ), vô hiệu / kích hoạt lại nhóm. Xem lưu ý không đổi tên
    các nhóm Admin, Schedualer, Executor ở <a href="#tong-quan">Cách phân quyền hoạt động</a>.</p>

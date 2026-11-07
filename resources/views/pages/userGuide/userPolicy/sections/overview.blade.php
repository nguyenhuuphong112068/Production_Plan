<p>
    Quyền của một tài khoản = quyền của <b>các nhóm quyền</b> được gán + <b>quyền riêng</b> cấp thêm cho tài khoản đó. Quyền quyết
    định nhóm menu / màn hình nào hiện và nút nào bấm được.
</p>
<div class="ug-flow">
    <div class="ug-flow-step"><span class="ug-flow-no">1</span><b>Nhóm Phân Quyền</b>Tạo nhóm (ví dụ QC Manager), tick các quyền.</div>
    <div class="ug-flow-step"><span class="ug-flow-no">2</span><b>Danh Sách User</b>Gán một hoặc nhiều nhóm quyền cho tài khoản.</div>
    <div class="ug-flow-step"><span class="ug-flow-no">3</span><b>Quyền riêng</b>Cấp thêm quyền lẻ cho từng tài khoản khi cần.</div>
</div>

<div class="ug-callout is-danger mt-3">
    <i class="fas fa-exclamation-circle"></i>
    <div>
        <ul class="mb-0">
            <li>Một số chức năng kiểm tra đúng <b>tên</b> nhóm quyền chính: <b>Admin</b>, <b>Schedualer</b> (sửa lịch sản xuất),
                <b>Leader</b>, <b>Executor</b> (chỉ vào trang Ghi Nhận Sản Xuất)... <b>Không đổi tên</b> các nhóm này.</li>
            <li><b>Nhóm quyền chính</b> = trong các nhóm gán cho tài khoản, nhóm đứng trên cùng ở danh sách Nhóm Phân Quyền
                (không theo thứ tự bấm chọn). Gán Viewer + Schedualer thì nhóm chính là Viewer và không sửa được lịch.</li>
            <li>Vô hiệu một nhóm quyền thì mọi tài khoản thuộc nhóm đó mất các quyền của nhóm.</li>
            <li>Danh sách đầy đủ và bảng quyền của mọi chức năng: xem
                <a href="{{ route('pages.userGuide.show', 'phanQuyen') }}">Hướng dẫn Bảo Mật &amp; Phân Quyền</a>.</li>
        </ul>
    </div>
</div>

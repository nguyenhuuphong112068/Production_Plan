{{--
| Thân mục "Phân Quyền" dùng chung cho mọi tài liệu chức năng: lời dẫn + bảng GuidePermissions của tài liệu.
| <khoá>/sections/permissions.blade.php @include file này, rồi thêm lưu ý riêng của tài liệu (nếu có).
--}}
<p>
    Bảng dưới cho biết mỗi thao tác cần quyền gì và <b>nhóm quyền nào đang được phép</b>. Cột cuối đọc trực tiếp cấu hình
    <b>User Policy › Nhóm Phân Quyền</b> lúc mở trang, nên luôn khớp với phân quyền hiện tại. Thiếu quyền thì mục menu bị ẩn
    hoặc nút không hiện / bị mờ.
</p>

@include('pages.userGuide.shared.permissionTable')

<div class="ug-callout">
    <i class="fas fa-user-shield"></i>
    <div>
        Quyền thực tế của một tài khoản = quyền của các nhóm quyền được gán, cộng / trừ <b>quyền riêng</b> của tài khoản đó.
        Điều kiện ghi <b>nhóm quyền chính</b> chỉ xét một nhóm: trong các nhóm gán cho tài khoản, nhóm đứng trên cùng ở danh
        sách Nhóm Phân Quyền. Cách tính đầy đủ, cách cấp quyền và
        bảng tổng hợp mọi chức năng xem
        <a href="{{ route('pages.userGuide.show', 'phanQuyen') }}">Hướng dẫn Bảo Mật &amp; Phân Quyền</a>.
    </div>
</div>

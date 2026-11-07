<p class="text-muted small mb-2">
    Ma trận các quyền tick được của nhóm menu này, chỉ hiện các nhóm quyền có ít nhất một quyền. Rê chuột lên dòng để xem tên
    quyền. Điều kiện theo tên nhóm quyền / phòng ban và các thao tác mọi tài khoản đều làm được xem mục
    <a href="{{ route('pages.userGuide.show', $item['module']) }}#phan-quyen">Phân Quyền</a> của tài liệu tương ứng.
</p>
@include('pages.userGuide.shared.permissionMatrix', ['moduleKey' => $item['module']])

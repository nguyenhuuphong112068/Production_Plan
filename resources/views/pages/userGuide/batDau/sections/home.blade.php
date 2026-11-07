<p>Đăng nhập xong, phần mềm mở <b>Trang chủ</b>. Từ đây chọn chức năng trên menu bên trái.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/batDau/trang-chu.png', 'caption' => 'Màn hình Trang Chủ & Thanh Trên Cùng'])

<h3>Thanh trên cùng</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead><tr><th style="width: 160px">Thành phần</th><th>Tác dụng</th></tr></thead>
        <tbody>
            <tr><td><i class="fas fa-bars"></i></td><td>Thu gọn / mở rộng menu trái (thu gọn chỉ còn biểu tượng, rê chuột vào để bung ra).</td></tr>
            <tr><td>Tiêu đề giữa</td><td>Tên màn hình đang mở.</td></tr>
            <tr><td><i class="fas fa-question-circle" style="color:#CDC717;font-size:1.2rem"></i></td>
                <td><b>Hướng dẫn</b>: mở đúng mục hướng dẫn của màn hình đang xem (tab mới). Màn hình chưa có hướng dẫn riêng thì mở danh sách hướng dẫn. Chỉ hiện với tài khoản có quyền <b>Xem Hướng Dẫn Sử Dụng</b>.</td></tr>
            <tr><td><i class="far fa-bell" style="color:#CDC717;font-size:1.2rem"></i></td><td><b>Thông báo</b>: số trên chuông là thông báo chưa đọc; nhãn đỏ <i>@ Nhắc tên</i> khi có người nhắc tới bạn. Bấm chuông mở danh sách, bấm một thông báo để đánh dấu đã đọc và mở màn hình liên quan.</td></tr>
            <tr><td>👤 / 🛡️</td><td>Họ tên và nhóm quyền của tài khoản đang đăng nhập.</td></tr>
            <tr><td><i class="fas fa-sign-out-alt text-primary"></i></td><td>Đăng xuất.</td></tr>
        </tbody>
    </table>
</div>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Thông báo tự động hiện có: lô chưa được sắp lịch (gửi người lập lịch mỗi ngày từ 08:00), nhắc lấy mẫu thẩm định,
        đề nghị thay đổi kế hoạch... Mỗi thông báo dẫn tới màn hình xử lý.</div>
</div>

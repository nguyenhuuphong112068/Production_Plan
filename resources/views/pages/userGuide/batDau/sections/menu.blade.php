<p>
    Menu trái chia theo nhóm chức năng. Mỗi nhóm có một tài liệu hướng dẫn riêng (cột cuối). Nhóm nào không hiện trên menu của
    bạn là do tài khoản chưa được cấp quyền - liên hệ quản trị (<b>User Policy › Nhóm Phân Quyền</b>).
</p>

<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead><tr><th style="width: 190px">Nhóm menu</th><th>Dùng để</th><th style="width: 150px">Hướng dẫn</th></tr></thead>
        <tbody>
            <tr><td>Dữ Liệu Gốc</td><td>Danh mục nền: tên sản phẩm, phòng sản xuất, đơn vị, thị trường, tổ, phòng ban, khuôn, nhóm máy (ngày nghỉ công ty tự lấy từ eO2).</td>
                <td><a href="{{ route('pages.userGuide.show', 'duLieuGoc') }}">Dữ Liệu Gốc</a></td></tr>
            <tr><td>Danh Mục</td><td>Danh mục bán thành phẩm, thành phẩm, theo dõi ấn bản, kiểm tra NL/BB, cảnh báo nguồn NL, danh mục BT-HC-TI.</td>
                <td><a href="{{ route('pages.userGuide.show', 'danhMuc') }}">Danh Mục</a></td></tr>
            <tr><td>Định Mức</td><td>Định mức phòng - thời gian sản xuất, định mức khuôn ép vỉ và khuôn Định hình, danh sách nhân viên.</td>
                <td><a href="{{ route('pages.userGuide.show', 'dinhMuc') }}">Định Mức</a></td></tr>
            <tr><td>Kế Hoạch</td><td>Kế hoạch năm, kế hoạch sản xuất, theo dõi thẩm định / hồ sơ KCS, thông báo đóng gói, kế hoạch HC-BT.</td>
                <td><a href="{{ route('pages.userGuide.show', 'keHoach') }}">Kế Hoạch</a></td></tr>
            <tr><td>Lịch Sản Xuất</td><td>Sắp lịch (Gantt, tự động), thực thi sản xuất, danh sách lịch, sản lượng, xác nhận hoàn thành.</td>
                <td><a href="{{ route('pages.userGuide.show', 'schedual') }}">Lịch Sản Xuất</a></td></tr>
            <tr><td>Lịch HC-BT</td><td>Lịch hiệu chuẩn - bảo trì - tiện ích dạng biểu đồ và theo tuần.</td>
                <td><a href="{{ route('pages.userGuide.show', 'lichHcBt') }}">Lịch HC-BT</a></td></tr>
            <tr><td>Lịch Công Tác</td><td>Phân công nhân sự sản xuất và bảo trì. Cần quyền xem nhóm này.</td>
                <td><a href="{{ route('pages.userGuide.show', 'lichCongTac') }}">Lịch Công Tác</a></td></tr>
            <tr><td>Báo Cáo</td><td>Báo cáo ngày, tuần, tháng, OEE. Cần quyền xem nhóm này.</td>
                <td><a href="{{ route('pages.userGuide.show', 'baoCao') }}">Báo Cáo</a></td></tr>
            <tr><td>Tồn BTP</td><td>Tồn kho lý thuyết, tồn bán thành phẩm thực tế. Cần quyền xem nhóm này.</td>
                <td><a href="{{ route('pages.userGuide.show', 'tonBtp') }}">Tồn BTP</a></td></tr>
            <tr><td>Lịch Sử</td><td>Tra cứu lịch sử sản xuất và HC-BT theo phòng, lô. Cần quyền xem nhóm này.</td>
                <td><a href="{{ route('pages.userGuide.show', 'lichSu') }}">Lịch Sử</a></td></tr>
            <tr><td>User Policy</td><td>Tài khoản người dùng và nhóm phân quyền. Chỉ quản trị.</td>
                <td><a href="{{ route('pages.userGuide.show', 'userPolicy') }}">User Policy</a></td></tr>
            <tr><td>Lịch Sử Đăng Nhập</td><td>Nhật ký đăng nhập và nhật ký thao tác.</td>
                <td><a href="{{ route('pages.userGuide.show', 'dangNhap') }}">Lịch Sử Đăng Nhập</a></td></tr>
            <tr><td>Hướng Dẫn Sử Dụng</td><td>Các tài liệu này. Cần quyền <b>Xem Hướng Dẫn Sử Dụng</b>.</td><td>—</td></tr>
        </tbody>
    </table>
</div>

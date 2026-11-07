<p>
    <b>Tồn kho lý thuyết theo công đoạn</b>: dự báo lượng bán thành phẩm <b>đang chờ vào công đoạn kế tiếp</b> theo từng ngày, tính
    từ lịch đã sắp. Tồn được gom theo <b>công đoạn đích</b> - Định hình, Bao phim, Đóng gói: một lô chờ Đóng gói tính vào "chờ Đóng
    gói" dù nó đến từ Pha chế, Định hình hay Bao phim.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/tonBtp/ton-ly-thuyet.png', 'caption' => 'Màn hình Tồn BTP › Tồn Kho Lý Thuyết'])
<ul>
    <li>Biểu đồ <b>Tồn chờ từng công đoạn theo ngày</b>; mỗi ngày có <b>Nhập</b> (lô xong công đoạn trước), <b>Xuất</b> (lô bắt đầu
        công đoạn đích), <b>Tồn</b>. Đơn vị ĐV hoặc Kg.</li>
    <li>Bấm một ngày / một nhóm để xem <b>Chi tiết theo mã bán thành phẩm</b> và số lô; có thể xem theo phòng.</li>
    <li>Cùng cách đo này được dùng cho <b>Kiểm soát tồn BTP</b> khi sắp lịch tự động (xem
        <a href="{{ route('pages.userGuide.show', 'schedual') }}#kiem-soat-ton-btp">Lịch Sản Xuất › Kiểm soát tồn BTP</a>).</li>
</ul>
<p class="text-muted small">Màn hình hiện trên menu với tài khoản có quyền xem tồn kho lý thuyết.</p>

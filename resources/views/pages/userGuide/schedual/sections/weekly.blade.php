<p>
    <b>Chi Tiết Kế Hoạch Sản Xuất Tuần</b>: lịch theo từng phòng / khu vực trong một tuần, dạng bảng để in hoặc gửi tổ sản xuất.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/schedual/lich-tuan.png', 'caption' => 'Màn hình Lịch Sản Xuất › Lịch Sản Xuất Tuần'])
<ul>
    <li><b>Chọn Tuần</b> ở đầu trang.</li>
    <li>Mỗi ô: tên sản phẩm, số lô, tình trạng, thời gian. Thẻ trong một ô xếp theo giờ bắt đầu.</li>
    <li>Công đoạn cân: thẻ lịch cân có nhãn <b>loại nguyên liệu</b> giống nhãn trên Lịch Sản Xuất Chart —
        <b>🧪 NL PC</b> / <b>🧪 NL THT</b> (Cân NL cho Pha chế / Trộn hoàn tất), <b>💊 Nang rỗng</b> (cho Định hình),
        <b>🖌️ NL BP</b> (cho Bao phim), <b>📦 NL ĐG</b> (cho Đóng gói). File Excel ghi loại này trong ngoặc vuông sau tên sản phẩm.
        Các lô <b>cân chung</b> (cùng sản phẩm, cùng giờ, cùng trạng thái, cùng loại nguyên liệu — ví dụ nhận phòng cân nhiều lô
        một lần, hoặc lịch cân ghép chung) gộp thành <b>1 thẻ</b>, ghi các số lô và nhãn "n lô cân chung"; thẻ vệ sinh chung cũng gộp như vậy.</li>
    <li>Màu thẻ theo tình trạng (chú thích dưới dòng tuần):
        <ul>
            <li><b>Lý thuyết</b> (nhãn xanh "Lý Thuyết"): lô chưa Nhận phòng, giờ theo lịch đã sắp.</li>
            <li><b>▶ Đang SX</b> (nền cam): lô đã <b>Nhận phòng</b> ở trang Thực Thi Sản Xuất / Nhận – Trả Phòng mà chưa Trả phòng.
                Thẻ bắt đầu lúc Nhận phòng, dài bằng thời gian giữ phòng theo lịch (sản xuất + vệ sinh); phần sau hiện tại ghi
                <i>(dự kiến)</i>. Quá giờ dự kiến mà chưa Trả phòng thì thẻ kéo dài tới hiện tại và ghi <span class="text-danger">(quá giờ)</span>.</li>
            <li><b>⏳ Đã trả phòng · Chờ XN</b> (nền tím): lô đã <b>Trả phòng</b> nhưng chưa xác nhận hoàn thành ✓✓. Thẻ từ lúc Nhận phòng tới lúc Trả phòng.</li>
            <li><b>Đã xác nhận</b> (nền xanh nhạt): giờ thực tế sau khi xác nhận hoàn thành, kèm sản lượng.</li>
        </ul>
        Lô Đang SX / Chờ XN không còn hiện khối lý thuyết riêng (sản xuất và vệ sinh) — giống thanh ▶ / ⏳ trên Lịch Sản Xuất Chart.
        Mỗi thẻ ghi mốc Nhận phòng, Trả phòng (hoặc dự kiến trả). Tải lại trang để thấy thao tác Nhận / Trả phòng mới nhất.</li>
    <li><b>Hiện/Ẩn Lý Thuyết</b>: hiện thêm giờ lý thuyết để so với thực tế (lô Đang SX / Chờ XN: giờ lý thuyết gồm cả vệ sinh).</li>
    <li><b>Xuất Excel</b>, <b>In Kế Hoạch</b>.</li>
</ul>

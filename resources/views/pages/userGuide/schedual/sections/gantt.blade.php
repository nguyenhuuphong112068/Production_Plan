<p>
    Biểu đồ Gantt của phân xưởng đang chọn: mỗi dòng là một phòng sản xuất, mỗi thanh là một lô (hoặc lịch vệ sinh,
    lịch BT-HC-TI) đặt đúng giờ bắt đầu - kết thúc. Rê chuột lên mỗi nút để xem chú thích ngắn của nút đó.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/schedual/man-hinh-gantt.png', 'caption' => 'Màn hình Lịch Sản Xuất › Lịch Sản Xuất Chart'])

<h3>Thanh công cụ bên trái</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr><th style="width: 120px">Nút</th><th>Tác dụng</th></tr>
        </thead>
        <tbody>
            <tr><td>⏴ Hiện Tại ⏵</td><td>Lùi / về ngày hiện tại / tiến một khung thời gian.</td></tr>
            <tr><td>🔄</td><td>Làm mới dữ liệu lịch (chạy ngầm, giữ nguyên khung xem và vị trí cuộn).</td></tr>
            <tr><td>ℹ️</td><td>Ẩn / hiện bảng chú thích màu (xem <a href="#mau-canh-bao">Màu cảnh báo</a>).</td></tr>
            <tr><td>🙈 / 👀</td><td>Ẩn / hiện lịch vệ sinh.</td></tr>
            <tr><td>🧭</td><td>Hiện lịch lý thuyết của các lô đã hoàn thành (so với lịch thực tế).</td></tr>
            <tr><td>🔀</td><td>Bật / tắt chế độ tịnh tiến: kéo một lô thì các lô phía sau trong cùng phòng dời theo.</td></tr>
            <tr><td>📜</td><td>Bật / tắt hiện lịch sử thay đổi khi rê chuột lên lô.</td></tr>
            <tr><td>🔍</td><td>Bật / tắt hiện chi tiết lô khi rê chuột.</td></tr>
            <tr><td>🤖</td><td>Mở hộp thoại <a href="#hop-thoai-sap-lich">Sắp lịch tự động</a>.</td></tr>
            <tr><td>🗑️</td><td>Xoá lịch theo công đoạn hoặc theo phòng, từ một ngày trở đi (lô đã chạy không bị xoá).</td></tr>
            <tr><td>💾</td><td>Lưu các thay đổi kéo thả (hoặc <span class="ug-key">Ctrl</span> + <span class="ug-key">S</span>).</td></tr>
            <tr><td>🚫</td><td>Xoá các lịch đang được chọn.</td></tr>
            <tr><td>👁️</td><td>Hiện thông tin thêm trên thanh lô: lý do đổi màu, mã chiến dịch.</td></tr>
            <tr><td>✅</td><td>Chấp nhận lô quá hạn biệt trữ: chọn lịch rồi bấm nút.</td></tr>
            <tr><td>🚿</td><td>Xác định lịch thẩm định vệ sinh: chọn lịch rồi bấm nút.</td></tr>
            <tr><td>🆚</td><td>Đổi cấp vệ sinh của các lịch đang chọn.</td></tr>
            <tr><td>👥</td><td>Ẩn / hiện phân công nhân sự tại từng phòng.</td></tr>
        </tbody>
    </table>
</div>

<h3>Thanh công cụ bên phải</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr><th style="width: 120px">Nút</th><th>Tác dụng</th></tr>
        </thead>
        <tbody>
            <tr><td>📤</td><td><b>Submit lịch</b>: sắp lịch xong bấm để các bộ phận khác thấy lịch mới.</td></tr>
            <tr><td>Cỡ chữ, ô tìm</td><td>Đổi cỡ chữ trên thanh lô; tìm nhanh lô.</td></tr>
            <tr><td>Slot</td><td>Đổi độ chia thời gian của khung Tuần / Tháng (1 giờ hoặc 4 giờ).</td></tr>
            <tr><td>Ngày / Tuần / Tháng / 3 Tháng</td><td>Đổi khung thời gian hiển thị. Khung 3 tháng tải nhiều dữ liệu, mở chậm hơn.</td></tr>
            <tr><td>KHSX</td><td>Mở <a href="#danh-sach-khsx">danh sách kế hoạch đang chờ sắp lịch</a> (kéo lô từ đây thả vào phòng để
                <a href="#sap-lich-thu-cong">sắp lịch thủ công</a>).</td></tr>
            <tr><td>⛶</td><td>Toàn màn hình.</td></tr>
        </tbody>
    </table>
</div>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Phòng đang có lô đã <b>Nhận phòng</b> (trang Thực Thi Sản Xuất) hiện thêm thanh "phòng đang bận" chỉ để xem:
        không chọn, không kéo, không sửa được thanh này.</div>
</div>

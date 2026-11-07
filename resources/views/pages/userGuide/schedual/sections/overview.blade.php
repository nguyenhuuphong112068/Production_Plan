<p>
    Nhóm chức năng <b>Lịch Sản Xuất</b> xếp từng lô của kế hoạch sản xuất vào phòng sản xuất theo thời gian, từ
    Pha chế tới Đóng gói. Người lập lịch có thể để phần mềm <b>sắp lịch tự động</b>, sau đó xem cảnh báo màu trên
    biểu đồ Gantt và chỉnh tay những chỗ cần thiết.
</p>

<h3>Dòng chảy công đoạn</h3>
<div class="ug-flow">
    <div class="ug-flow-step"><span class="ug-flow-no">0</span><b>Cân NL (CNL)</b>Cân nguyên liệu chính và phụ, xếp ngược theo ngày Pha chế.</div>
    <div class="ug-flow-step"><span class="ug-flow-no">1</span><b>Pha chế (PC)</b>Công đoạn đầu tiên được sắp lịch tự động.</div>
    <div class="ug-flow-step"><span class="ug-flow-no">2</span><b>Trộn hoàn tất (THT)</b>Một số sản phẩm không có công đoạn này.</div>
    <div class="ug-flow-step"><span class="ug-flow-no">3</span><b>Định hình (ĐH)</b>Dập viên, đóng nang.</div>
    <div class="ug-flow-step"><span class="ug-flow-no">4</span><b>Bao phim (BP)</b>Chỉ lô bao phim.</div>
    <div class="ug-flow-step"><span class="ug-flow-no">5</span><b>Đóng gói (ĐG)</b>Ép vỉ, đóng gói; cần đủ bao bì.</div>
</div>
<p class="mt-3">
    Ngoài lịch sản xuất, mỗi phòng còn có lịch <b>Bảo trì, Hiệu chuẩn, Tiện ích</b> (BT-HC-TI) hiện trên cùng biểu đồ.
</p>

<h3>Thứ tự làm việc khuyến nghị</h3>
<ol class="ug-steps">
    <li>Kế hoạch sản xuất đã có lô cần xếp (<b>Kế Hoạch › Kế Hoạch Sản Xuất</b>) và sản phẩm đã được định mức phòng,
        thời gian ở <b>Định Mức › Sản Xuất</b>. Sản phẩm chưa định mức sẽ bị báo ngay trên hộp thoại sắp lịch.</li>
    <li>Kiểm tra các ngày được chọn sẵn trong ô <i>Ngày không sắp lịch</i>: đó là
        <a href="{{ route('pages.userGuide.show', 'duLieuGoc') }}#ngay-nghi">ngày nghỉ công ty</a>, tự lấy từ eO2 mỗi sáng.</li>
    <li>Mở <b>Lịch Sản Xuất Chart</b>, bấm <span class="ug-emoji">🤖</span>, bấm <b>Tạo bản sao lưu</b> rồi chạy sắp lịch
        tự động (xem <a href="#hop-thoai-sap-lich">Hộp thoại Sắp lịch tự động</a>).</li>
    <li>Đọc thông báo kết quả, xem các lô bị tô màu cảnh báo (<a href="#mau-canh-bao">Màu cảnh báo</a>), chỉnh tay nếu
        cần rồi bấm <span class="ug-emoji">💾</span> để lưu (<a href="#chinh-lich-tay">Chỉnh lịch thủ công</a>).</li>
    <li>Bấm <span class="ug-emoji">📤</span> <b>Submit lịch</b> để các bộ phận khác thấy lịch mới.</li>
</ol>

<h3>Thuật ngữ viết tắt</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr><th style="width: 140px">Viết tắt</th><th>Ý nghĩa</th></tr>
        </thead>
        <tbody>
            <tr><td>PC</td><td>Pha chế</td></tr>
            <tr><td>THT</td><td>Trộn hoàn tất</td></tr>
            <tr><td>ĐH</td><td>Định hình (dập viên, đóng nang)</td></tr>
            <tr><td>BP</td><td>Bao phim</td></tr>
            <tr><td>ĐG</td><td>Đóng gói / Ép vỉ</td></tr>
            <tr><td>CNL</td><td>Cân nguyên liệu</td></tr>
            <tr><td>BTP</td><td>Bán thành phẩm: sản phẩm đã qua một số công đoạn, đang chờ công đoạn tiếp theo</td></tr>
            <tr><td>NL / BB</td><td>Nguyên liệu / Bao bì</td></tr>
            <tr><td>HH</td><td>Hết hạn</td></tr>
            <tr><td>BT-HC-TI</td><td>Lịch Bảo trì, Hiệu chuẩn, Tiện ích của thiết bị và phòng</td></tr>
            <tr><td>Chiến dịch</td><td>Nhóm lô cùng sản phẩm chạy liền nhau trong một phòng</td></tr>
            <tr><td>Max</td><td>Mức tồn BTP tối đa người dùng đặt cho một nhóm chờ (đơn vị viên)</td></tr>
            <tr><td>Lô thẩm định</td><td>Lô có mã thẩm định; chờ kết quả kiểm nghiệm lâu hơn lô thương mại</td></tr>
        </tbody>
    </table>
</div>

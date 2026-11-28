<p>
    Mỗi lô được kiểm tra nhiều điều kiện. Lô vi phạm thì đổi màu nền theo lỗi <b>nặng nhất</b>; các lỗi còn lại hiện thành
    các dải màu dọc nhỏ ở mép phải thanh lô. Bấm <span class="ug-emoji">👁️</span> để đọc lý do đổi màu.
</p>

<h3>Màu vi phạm (từ nhẹ tới nặng)</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr><th style="width: 70px">Màu</th><th style="width: 220px">Lỗi</th><th>Ý nghĩa</th></tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="ug-swatch" style="background:#e4e405"></span></td>
                <td>Vệ sinh</td>
                <td>Không đủ thời gian vệ sinh giữa hai lô trong cùng phòng (chữ đổi màu đỏ).</td>
            </tr>
            <tr>
                <td><span class="ug-swatch" style="background:#bda124"></span></td>
                <td>Quá thời gian biệt trữ</td>
                <td>Thời gian chờ giữa hai công đoạn của lô vượt mức cho phép.</td>
            </tr>
            <tr>
                <td><span class="ug-swatch" style="background:#e54a4a"></span></td>
                <td>Trễ ngày cần hàng</td>
                <td>Lô dự kiến kết thúc trễ hơn ngày cần hàng / ngày dự kiến KCS.</td>
            </tr>
            <tr>
                <td><span class="ug-swatch" style="background:#4d4b4b"></span></td>
                <td>Sai thứ tự công đoạn</td>
                <td>Công đoạn sau bắt đầu khi công đoạn trước của chính lô đó chưa xong.</td>
            </tr>
            <tr>
                <td><span class="ug-swatch" style="background:#920000"></span></td>
                <td>Nguyên liệu / Bao bì</td>
                <td>Nặng nhất: chưa đủ NL / BB, bắt đầu trước ngày được phép cân, hoặc sau ngày HH NL chính / HH BB...</td>
            </tr>
        </tbody>
    </table>
</div>

<h3>Cảnh báo khuôn (Đóng gói, Định hình ở phòng có loại máy)</h3>
<p>
    Lô có khuôn hiện mã khuôn ở cuối tên lô. Lô vi phạm về khuôn có nền <span class="ug-swatch" style="background:#ffd500"></span>
    vàng và dòng cảnh báo trên thanh lô; nút <b>Lịch trùng khuôn</b> trên thanh công cụ nhảy lần lượt tới từng lô bị cảnh báo.
</p>
<p>
    Lô Đóng gói còn có nhãn <span class="ug-swatch" style="background:#059669"></span> xanh lá gồm <b>cờ quốc gia + mã thị trường</b> (thị trường không phải quốc gia như Tender, ALDE... hiện 🌐)
    phía trên bên trái thanh lịch (cùng vị trí nhãn loại NL của lịch cân), ghi thị trường của
    thành phẩm (VN, MY, HK, BE...; rê chuột để xem tên thị trường), cả trên thanh đang chạy / chờ xác nhận. Thị trường lấy từ
    danh mục thành phẩm; thành phẩm chưa khai báo thị trường thì không có nhãn.
</p>
<p>
    Lô thu hồi dư phẩm (tích <b>Thu hồi</b> ở cột Phân loại lô của Kế Hoạch Sản Xuất tháng) có nhãn
    <span class="ug-swatch" style="background:#dc2626"></span> đỏ <b>Thu hồi dư phẩm</b> treo phía trên bên trái thanh lịch ở
    <b>mọi công đoạn</b> (cân, pha chế, ... đóng gói; cả thanh đang chạy / chờ xác nhận; không gắn cho thanh vệ sinh).
    Các nhãn treo xếp nối nhau theo thứ tự: loại NL của lịch cân · thị trường · Thu hồi dư phẩm.
</p>
<ul>
    <li><b>⚠️ Trùng Khuôn</b>: số phòng dùng cùng khuôn trong cùng thời gian vượt số lượng khuôn đang có.</li>
    <li><b>❌ Thiếu Khuôn!</b>: lô đã lên phòng cần khuôn nhưng chưa được gán khuôn (sản phẩm chưa khai báo khuôn, hoặc
        mọi bộ khuôn đều bận). Dùng <i>Phân bổ khuôn</i> trong hộp thoại Sắp lịch tự động hoặc chọn khuôn ở danh sách lô chờ.</li>
    <li><b>❌ Sai Khuôn</b>: khuôn đã gán không lắp được cho loại máy của phòng (khuôn ép vỉ với máy ép vỉ, khuôn Định hình với máy Định hình).</li>
</ul>

<h3>Huy hiệu ⚖ (khi bật nút Lô cần cân)</h3>
<p>
    Chỉ hiện khi bật <b>⚖ Lô cần cân</b>: vòng tròn ⚖ vắt qua góc trên bên trái của lô PC / THT / ĐH / BP còn công đoạn cân
    chưa cân xong. <span class="ug-swatch" style="background:#ea580c"></span> cam = chưa có lịch cân,
    <span class="ug-swatch" style="background:#16a34a"></span> xanh = đã có lịch cân. Xem
    <a href="#sap-lich-can">Sắp lịch cân bán tự động</a>.
</p>
<p>
    Khối cân <b>nháp</b> do nút Gợi ý lịch cân đặt lên lịch (chưa lưu): nền <span class="ug-swatch" style="background:#0ea5e9"></span>
    xanh dương, tên có dấu ✎, viền trắng nét đứt; nền đen nếu cân xong sau giờ bắt đầu công đoạn sau.
</p>

<h3>Màu lịch Bảo trì, Hiệu chuẩn, Tiện ích</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr><th style="width: 70px">Màu</th><th>Loại lịch</th></tr>
        </thead>
        <tbody>
            <tr><td><span class="ug-swatch" style="background:#003A4F"></span></td><td>Bảo trì</td></tr>
            <tr><td><span class="ug-swatch" style="background:#9a1b72"></span></td><td>Hiệu chuẩn</td></tr>
            <tr><td><span class="ug-swatch" style="background:#830cbf"></span></td><td>Tiện ích</td></tr>
        </tbody>
    </table>
</div>
<p>Lịch BT-HC-TI tới hạn hoặc quá hạn có viền cảnh báo.</p>

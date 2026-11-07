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
<ul>
    <li><b>⚠️ Trùng Khuôn</b>: số phòng dùng cùng khuôn trong cùng thời gian vượt số lượng khuôn đang có.</li>
    <li><b>❌ Thiếu Khuôn!</b>: lô đã lên phòng cần khuôn nhưng chưa được gán khuôn (sản phẩm chưa khai báo khuôn, hoặc
        mọi bộ khuôn đều bận). Dùng <i>Phân bổ khuôn</i> trong hộp thoại Sắp lịch tự động hoặc chọn khuôn ở danh sách lô chờ.</li>
    <li><b>❌ Sai Khuôn</b>: khuôn đã gán không lắp được cho loại máy của phòng (khuôn ép vỉ với máy ép vỉ, khuôn Định hình với máy Định hình).</li>
</ul>

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

<p>
    Lô đã có trên Gantt (do sắp lịch tự động hoặc thả tay) được chỉnh trực tiếp trên biểu đồ: kéo sang giờ / phòng khác,
    kéo dãn thời lượng, sửa giờ chính xác, đổi cấp vệ sinh, xoá lịch... Kéo thả trên Gantt chỉ là <b>bản nháp</b> cho tới
    khi bấm lưu.
</p>

<h3>Chọn lô</h3>
<ul>
    <li><b>Bấm</b> vào thanh lô để chọn (viền nổi bật); bấm lần nữa để bỏ chọn. Chọn được nhiều lô, kể cả ở nhiều phòng.</li>
    <li>Các nút 🚫, ✅, 🚿, 🆚 trên thanh công cụ và hộp thoại sửa giờ áp dụng cho <b>các lô đang chọn</b>.</li>
</ul>

<h3>Kéo thả và lưu</h3>
<ol class="ug-steps">
    <li>Kéo thanh lô sang giờ khác hoặc phòng khác; kéo mép thanh để đổi thời lượng. Lô bám theo bước 15 phút. Đang chọn
        nhiều lô thì kéo một lô trong nhóm, <b>cả nhóm dời cùng một khoảng</b>. Thả vào phòng không có định mức cho sản
        phẩm thì báo <b>Thiếu Định Mức</b> và lô không dời.</li>
    <li><b>Hút sát:</b> thả lô cách lô (hoặc lịch vệ sinh) đứng trước cùng phòng, cùng công đoạn <b>không quá 1 giờ</b> thì
        lô được kéo sát vào; thả đè lên lô trước thì lô được lùi ra ngay sau lô đó. Không áp cho Cân và Bảo trì.</li>
    <li><b>Lô phía sau bị đè:</b> khi tắt <span class="ug-emoji">🔀</span>, các lô phía sau trong cùng phòng bị chồng lấn
        được đẩy trễ ra vừa đủ, gặp khoảng trống thì dừng. Bật <span class="ug-emoji">🔀</span> trước khi kéo thì các lô
        phía sau và các công đoạn sau của lô dời theo cả chuỗi; lô thuộc chiến dịch có thể dời cả chiến dịch.</li>
    <li>Kéo nhầm: bấm nút <b>Nháp (n)</b> hoặc <span class="ug-key">Ctrl</span> + <span class="ug-key">Z</span> để hoàn tác
        từng thao tác kéo thả (n = số thao tác hoàn tác được).</li>
    <li>Bấm <span class="ug-emoji">💾</span> hoặc <span class="ug-key">Ctrl</span> + <span class="ug-key">S</span> để lưu.
        Thanh lô chưa lưu có viền cam đứt nét. Đóng trang khi chưa lưu thì mất các thay đổi.</li>
    <li>Xong việc, bấm <span class="ug-emoji">📤</span> <b>Submit lịch</b> để các bộ phận khác thấy lịch mới. Submit bị chặn
        nếu còn lô vi phạm nặng hoặc chưa đạt chính sách sản lượng (xem <a href="#chinh-sach-san-luong">Chính sách sản lượng</a>).</li>
</ol>

<h3>Sửa giờ chính xác</h3>
<p>
    <b>Bấm đúp</b> vào thanh lô (hoặc bấm nút ✏️ trên thanh) để mở hộp thoại <b>Cập nhật lịch sản xuất</b>: sửa
    <b>Định mức SX (h)</b>, <b>Thời gian bắt đầu / kết thúc</b>, <b>Phòng</b> (chỉ các phòng có định mức). Đổi định mức
    hoặc giờ bắt đầu thì giờ kết thúc tự tính lại. Chọn chế độ di chuyển:
</p>
<ul>
    <li><b>Di chuyển cả chiến dịch</b>: các lô cùng chiến dịch dời theo.</li>
    <li><b>Di chuyển các lô được chọn</b>: chỉ dời các lô đang chọn; các lô này phải <b>cùng một chiến dịch</b>.</li>
</ul>
<p>Bấm <b>Lưu</b> là lưu ngay, không qua nút 💾.</p>

<h3>Thao tác chuột nhanh</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead><tr><th style="width: 220px">Thao tác</th><th>Tác dụng</th></tr></thead>
        <tbody>
            <tr><td><span class="ug-key">Ctrl</span> + bấm đúp</td><td>Tô nổi cả chuỗi công đoạn của lô (hoặc của các lô đang chọn); làm lại để tắt.</td></tr>
            <tr><td><span class="ug-key">Alt</span> + bấm</td><td>Kéo lịch vệ sinh của các lô đang chọn về sát ngay sau lô.</td></tr>
            <tr><td><span class="ug-key">Shift</span> + bấm</td><td>Sao chép thông tin các lô đang chọn (tên, phòng, giờ bắt đầu, kết thúc, công đoạn) để dán sang Excel.</td></tr>
            <tr><td>Chuột phải › <b>Tự động điều chỉnh lỗi đen</b></td><td>Giữ nguyên lô vừa bấm, dời các công đoạn trước / sau của chính lô đó tới chỗ trống gần nhất để hết lỗi sai thứ tự công đoạn (màu đen). Kết quả là bản nháp, kiểm tra rồi bấm 💾.</td></tr>
            <tr><td>Chuột phải › <b>Xem trước chuỗi</b></td><td>Lọc Gantt chỉ còn các phòng có công đoạn của lô, tô bóng chuỗi để xem lô đi qua những phòng nào, lúc nào.</td></tr>
            <tr><td>Chuột phải › <b>Lịch sử tịnh tuyến</b></td><td>Xem các lần lô bị dời do <a href="#tinh-tuyen-lich">tịnh tuyến lịch</a> (chỉ tài khoản có quyền).</td></tr>
        </tbody>
    </table>
</div>

<h3>Xoá lịch</h3>
<ul>
    <li><span class="ug-emoji">🚫</span>: xoá lịch của các lô đang chọn, lô quay về danh sách KHSX.</li>
    <li><span class="ug-emoji">🗑️</span>: chọn <b>Xóa Lịch Từ Ngày</b>, chọn <b>Xóa theo công đoạn</b> hoặc
        <b>Xóa theo phòng SX</b>, bấm <b>Xóa</b>. Thường dùng trước khi sắp lịch tự động lại từ một ngày.
        Lô đã nhận phòng hoặc đã hoàn thành không bị xoá.</li>
</ul>

<div class="ug-callout is-warn">
    <i class="fas fa-lock"></i>
    <div>
        <b>Khóa TTSX:</b> khi bạn sửa lịch thủ công mà chưa bật khoá, phần mềm tự khoá Nhận / Trả phòng của phân xưởng
        (hiện áp dụng cho PXV1) để trang Thực Thi Sản Xuất không đổi lịch cùng lúc với bạn. Khoá tự mở sau 10 phút không
        thao tác; sắp lịch xong nhớ bấm <b>Đang khóa TTSX</b> ở thanh trên của danh sách KHSX để mở khoá.
    </div>
</div>

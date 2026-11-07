<p>
    Sắp lịch thủ công là tự chọn lô trên <a href="#danh-sach-khsx">danh sách KHSX</a> và thả vào đúng phòng, đúng giờ trên
    Gantt. Dùng khi cần xếp một vài lô gấp, lô bị bỏ qua khi sắp lịch tự động (lô <i>Không sắp lịch tự động</i>, lô
    thiếu điều kiện...), hoặc sự kiện khác ngoài sản xuất.
</p>

<h3>Các bước</h3>
<ol class="ug-steps">
    <li>Bấm <b>KHSX</b>, chọn công đoạn cần xếp, rồi bấm <b>biểu tượng Stella</b> để thu hẹp danh sách: Gantt hiện lại bên
        phải, danh sách còn các cột Sản phẩm, Số lô, Thẩm định, Ngày DK KCS, Thị trường, Ưu tiên.</li>
    <li><b>Tick chọn</b> lô cần xếp. Chọn nhiều lô cùng lúc chỉ được khi các lô <b>cùng mã bán thành phẩm</b>; tick lô
        khác sản phẩm thì danh sách chỉ giữ lô vừa tick. Khi chọn, Gantt tự cuộn tới phòng đầu tiên lô được phép chạy.</li>
    <li>Bật nút <i class="fas fa-project-diagram"></i> (chuyển màu xanh) nếu muốn phần mềm <b>tự xếp luôn các công đoạn
        sau</b> của các lô này (xem bên dưới). Để xám nếu chỉ xếp công đoạn đang chọn.</li>
    <li>Kéo biểu tượng <i class="fas fa-arrows-alt"></i> ở cột đầu danh sách, thả vào <b>dòng phòng</b> trên Gantt tại
        <b>giờ muốn bắt đầu</b>. Tất cả lô đang tick được xếp một lượt, không phụ thuộc kéo từ dòng nào.</li>
    <li>Lô xuất hiện trên Gantt và biến mất khỏi danh sách KHSX. Kiểm tra màu cảnh báo, chỉnh lại nếu cần
        (<a href="#chinh-lich-tay">Chỉnh lịch trên Gantt</a>), xong bấm <span class="ug-emoji">📤</span> <b>Submit lịch</b>.</li>
</ol>

<div class="ug-callout is-warn">
    <i class="fas fa-save"></i>
    <div>Thả lô từ danh sách KHSX <b>lưu ngay</b> vào lịch, không cần bấm 💾 và không hoàn tác bằng <b>Nháp</b>. Thả nhầm
        thì kéo lô sang chỗ khác rồi lưu, hoặc chọn lô và bấm 🚫 để xoá lịch, lô quay về danh sách KHSX.</div>
</div>

<h3>Phần mềm xếp các lô như thế nào</h3>
<ul>
    <li><b>Thứ tự:</b> theo <b>số lô</b> tăng dần, không theo thứ tự tick.</li>
    <li><b>Giờ bắt đầu:</b> lô đầu bắt đầu tại điểm thả; các lô sau nối tiếp liền nhau, xen vệ sinh cấp I giữa các lô và
        vệ sinh cấp II sau lô cuối, tự nhảy qua ngày nghỉ. Thời gian lấy theo định mức của sản phẩm tại phòng được thả.</li>
    <li><b>Khung Tháng / 3 Tháng chia theo ngày:</b> thả vào ô ngày thì lô bắt đầu từ đầu ca của phòng: 7:15 (phòng làm
        giờ hành chính), 6:00 (phòng có ca 1), 14:00 (phòng chỉ chạy ca 2).</li>
    <li><b>Chờ công đoạn trước:</b> lô nào bị đẩy sớm hơn lúc công đoạn trước của <b>chính lô đó</b> xong thì được kéo về
        đúng mốc đó, nên giữa các lô có thể hở khoảng trống. Đây là chủ ý: lô N của công đoạn sau chỉ chờ lô N của công
        đoạn trước, các lô khác vẫn bám sát nhau.</li>
    <li><b>Pha Chế:</b> các lô thả cùng một lần được gán <b>chung một mã chiến dịch</b> cho mọi công đoạn.</li>
    <li><b>Đóng gói lô chia nhỏ:</b> chọn một phần của lô đóng gói đã chia thì các phần còn lại của lô đó được xếp theo.</li>
    <li><b>Khuôn:</b> Đóng gói và Định hình ở phòng cần khuôn (phòng Định hình có loại máy) được gán khuôn luôn, chỉ khuôn lắp được cho loại máy của phòng, ưu tiên khuôn đã chọn ở cột Khuôn của
        danh sách KHSX.</li>
    <li><b>Ngày nhận bao bì</b> (Đóng gói) được đặt là ngày làm việc liền trước ngày bắt đầu, nếu bao bì chưa nhận.</li>
    <li>Lịch vừa xếp ở trạng thái <b>chưa Submit</b>. Ở PXV1, xác nhận của Lead trên lô (nếu có) bị gỡ để Lead xác nhận lại.</li>
</ul>

<h3>Xếp liên tục các công đoạn sau</h3>
<p>
    Khi nút <i class="fas fa-project-diagram"></i> đang bật, sau khi xếp xong công đoạn được thả, phần mềm tự xếp tiếp công
    đoạn kế tiếp của đúng các lô đó (tới Đóng gói), theo cách xếp chiến dịch của
    <a href="#chon-phong-gio">sắp lịch tự động</a>: tự chọn phòng có định mức và giờ sớm nhất sau khi công đoạn trước
    xong. Có lô thẩm định thì công đoạn sau chờ thêm 5 ngày.
</p>

<h3>Câu báo khi thả không được</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead><tr><th style="width: 330px">Câu báo</th><th>Nguyên nhân / cách xử lý</th></tr></thead>
        <tbody>
            <tr><td>Vui Lòng Chọn Sản Phẩm Muốn Sắp Lịch</td><td>Chưa tick lô nào. Tick lô trước rồi mới kéo.</td></tr>
            <tr><td>Biểu tượng kéo màu xám, không kéo được</td><td>Lô <b>chưa xác định Ngày có NL</b> (Đóng gói: <b>Ngày có BB</b>). Cập nhật ngày ở Kế hoạch sản xuất tháng.</td></tr>
            <tr><td>Sản Phẩm Chưa Được Định Mức</td><td>Lô chưa có định mức phòng. Khai ở Định Mức › Định Mức Sản Xuất hoặc bấm ô <b>Thiếu Định Mức</b> trên danh sách KHSX.</td></tr>
            <tr><td>Sản Phẩm Sắp Lịch Không Đúng Phòng Đã Định Mức</td><td>Thả vào phòng không có định mức cho sản phẩm. Xem cột Phòng SX ở chế độ toàn màn hình.</td></tr>
            <tr><td>Thời gian bắt đầu nhỏ hơn thời gian hiện tại!</td><td>Thả vào giờ đã qua. Chỉ xếp được từ bây giờ trở đi.</td></tr>
            <tr><td>Không tạo được lịch - Chưa có định mức cho ... tại phòng ...</td><td>Định mức của sản phẩm tại phòng này chưa khai hoặc đã bị vô hiệu.</td></tr>
        </tbody>
    </table>
</div>

<div class="ug-callout">
    <i class="fas fa-calendar-plus"></i>
    <div><b>Sự kiện khác</b> (công đoạn 9): tạo ở danh sách KHSX bằng nút <i class="fas fa-plus"></i>, rồi thả vào phòng bất
        kỳ như một lô. Sự kiện không cần định mức; kéo dãn thanh trên Gantt để đổi thời lượng.</div>
</div>

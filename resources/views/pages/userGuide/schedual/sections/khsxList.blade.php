<p>
    Danh sách KHSX là các lô <b>đã có trong kế hoạch nhưng chưa được xếp lịch</b>, chia theo từng công đoạn. Đây là nơi
    chuẩn bị trước khi sắp lịch: sắp thứ tự lô, gom chiến dịch, chỉ định phòng, chọn khuôn... Những thông tin này dùng cho
    cả sắp lịch thủ công lẫn <a href="#hop-thoai-sap-lich">sắp lịch tự động</a>.
</p>

<h3>Mở danh sách và đổi kiểu hiển thị</h3>
<ol class="ug-steps">
    <li>Trên Lịch Sản Xuất Chart, bấm nút <b>KHSX</b> ở thanh công cụ bên phải. Danh sách mở <b>toàn màn hình</b> để quản lý.</li>
    <li>Chọn công đoạn ở ô giữa thanh trên (<i>1. Cân, 2. NL Khác (PXTN: Xử Lý Bao Bì), 3. Pha Chế, 4. Trộn Hoàn Tất,
        5. Định Hình, 6. Bao Phim, 7. ĐGSC-ĐGTC, 9. Sự Kiện Khác</i>). Ô <b>Tìm kiếm...</b> lọc theo tên, số lô, thị trường, mã, ghi chú, phòng...</li>
    <li>Bấm <b>biểu tượng Stella</b> ở góc phải để đổi: toàn màn hình → <b>thu hẹp</b> (dùng khi
        <a href="#sap-lich-thu-cong">thả lô vào phòng</a>) → đóng.</li>
</ol>

<h3>Các cột chính (chế độ toàn màn hình)</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead><tr><th style="width: 200px">Cột</th><th>Ý nghĩa / thao tác</th></tr></thead>
        <tbody>
            <tr><td>STT</td><td>Thứ tự của lô trên danh sách (số dưới là mã dòng). Kéo biểu tượng ở cột kế bên để <b>đổi thứ tự</b> (xem bên dưới).</td></tr>
            <tr><td>Phòng SX</td><td>Phòng đã có định mức cho lô. Lô có nhiều phòng: tick một phòng để <b>chỉ định phòng</b>, sắp lịch tự động sẽ chỉ xếp lô vào phòng đó; bỏ tick để trả về tự chọn. Ô đỏ <b>Thiếu Định Mức</b>: bấm để khai định mức ngay tại đây (một hoặc nhiều phòng, thời gian chuẩn bị, sản xuất, vệ sinh cấp I / II, số lô tối đa của chiến dịch).</td></tr>
            <tr><td>Ngày DK KCS, Ngày đáp ứng</td><td>Hạn cần hàng của lô, dùng để sắp thứ tự và tô màu trễ hạn.</td></tr>
            <tr><td>Ưu tiên</td><td>Mức ưu tiên 1 (đỏ, gấp nhất) đến 4 (xanh lá), lấy từ kế hoạch tháng.</td></tr>
            <tr><td>Thẩm Định</td><td>Lô thẩm định.</td></tr>
            <tr><td>Ngày có NL / Ngày có BB</td><td>Ngày có đủ nguyên liệu (công đoạn 1-6) hoặc bao bì (Đóng gói). <span class="text-danger"><b>Chưa xác định</b></span> thì lô <b>không thả vào lịch được</b>.</td></tr>
            <tr><td>Ngày Được Cân, Ngày HH NL, PC / THT / BP trước</td><td>Các mốc ràng buộc: không cân trước ngày được cân, phải xong trước ngày hết hạn NL hoặc hạn từng công đoạn.</td></tr>
            <tr><td>Nguồn nguyên liệu</td><td>Nguồn NL của lô.</td></tr>
            <tr><td>Mã Chiến Dịch</td><td>Các lô cùng mã (cùng màu) chạy liền nhau trong một phòng (xem <a href="#chien-dich">Chiến dịch</a>).</td></tr>
            <tr><td>🚿</td><td>Lô thẩm định vệ sinh (bật / tắt bằng nút 🚿 ở thanh trên).</td></tr>
            <tr><td>Khuôn</td><td>Chỉ ở Định Hình và Đóng Gói: chọn khuôn cho lô; để <i>Tự động / Chưa gán</i> thì phần mềm tự chọn khuôn khi xếp lịch.</td></tr>
        </tbody>
    </table>
</div>
<p class="text-muted small">Dòng tô khác màu là lô đang bật <b>Không sắp lịch tự động</b>.</p>

<h3>Các nút trên thanh trên (chế độ toàn màn hình)</h3>
<p>Phần lớn nút áp dụng cho <b>các dòng đang tick chọn</b>.</p>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead><tr><th style="width: 160px">Nút</th><th>Tác dụng</th></tr></thead>
        <tbody>
            <tr><td><span class="ug-chip is-danger">n Lô Thiếu Định Mức</span></td><td>Chỉ hiện khi có lô chưa có định mức phòng; bấm để lọc ra các lô đó.</td></tr>
            <tr><td><i class="fas fa-cubes"></i> (n)</td><td><b>Tạo mã chiến dịch</b> cho các lô đang chọn (n = số lô), áp cho mọi công đoạn của các lô. Phải chọn từ 2 lô.</td></tr>
            <tr><td><i class="fas fa-trash"></i></td><td><b>Xoá mã chiến dịch</b> của các lô đang chọn (hỏi mật khẩu đăng nhập). Chỉ xoá ở công đoạn chưa xếp lịch, chưa chạy.</td></tr>
            <tr><td><i class="fas fa-flag-checkered"></i></td><td>Chỉ ở <b>Pha Chế</b>: <b>Tạo mã chiến dịch tự động</b> cho mọi công đoạn (hỏi mật khẩu, chọn gom <i>Theo ngày KCS</i> hoặc <i>Theo ngày đáp ứng</i>). Phần mềm xoá mã chiến dịch cũ của các lô chưa chạy, rồi gom các lô <b>cùng sản phẩm và cùng ngày</b> thành chiến dịch, mỗi chiến dịch tối đa bằng số lô khai trong định mức. Lô thẩm định thứ nhất không được gom.</td></tr>
            <tr><td><i class="fas fa-object-group"></i></td><td>Chỉ ở Pha Chế / Trộn Hoàn Tất: <b>gộp Pha Chế vào Trộn Hoàn Tất</b> thành một lần sản xuất chung cho các lô đang chọn.</td></tr>
            <tr><td><i class="fas fa-sort"></i></td><td><b>Sắp xếp lại thứ tự</b> theo ngày KCS hoặc ngày đáp ứng (hỏi mật khẩu), rồi đến mức ưu tiên, rồi số lô. Ở Trộn Hoàn Tất, Định Hình, Bao Phim, lô có thời gian biệt trữ ngắn hơn lên trước. Chọn <i>Theo ngày đáp ứng</i> khi đang tick lô thì phải nhập ngày, và ngày này được <b>ghi vào Ngày đáp ứng</b> của các lô đã chọn.</td></tr>
            <tr><td>🚿</td><td>Bật / tắt <b>lô thẩm định vệ sinh</b> cho các lô đang chọn.</td></tr>
            <tr><td><i class="fas fa-calendar-times"></i></td><td>Bật / tắt <b>Không sắp lịch tự động</b>: lô vẫn nằm trên danh sách, sắp lịch tự động bỏ qua, chỉ xếp được bằng tay.</td></tr>
            <tr><td>Line / Stage</td><td>Công đoạn 3-7: chuyển sang xem theo <b>Line</b> (từng phòng). Chọn phòng ở ô giữa: danh sách hiện các lô chỉ chạy được ở phòng đó hoặc đã chỉ định phòng đó, với <b>thứ tự riêng của Line</b>. Thứ tự này dùng khi sắp lịch tự động <i>Theo Line</i>. Bấm lại để về xem theo công đoạn.</td></tr>
            <tr><td><i class="fas fa-lock"></i> Khóa TTSX</td><td>Khoá Nhận / Trả phòng ở trang Thực Thi Sản Xuất trong lúc sắp lịch (xem <a href="#chinh-lich-tay">Chỉnh lịch trên Gantt</a>).</td></tr>
            <tr><td><i class="fas fa-plus"></i>, <i class="fas fa-trash"></i></td><td>Chỉ ở <b>9. Sự kiện khác</b>: tạo sự kiện ngoài sản xuất (nội dung, số lô, ghi chú) để thả vào phòng như một lô; xoá sự kiện đang chọn.</td></tr>
        </tbody>
    </table>
</div>

<h3>Đổi thứ tự lô</h3>
<p>
    Thứ tự trên danh sách là <b>thứ tự ưu tiên của kế hoạch</b>: sắp lịch tự động xếp lô đứng trên trước trong cùng nhóm
    ưu tiên (xem <a href="#thu-tu-uu-tien">Thứ tự ưu tiên</a>).
</p>
<ul>
    <li>Kéo biểu tượng ở cột thứ ba của một dòng lên / xuống để đổi chỗ. Thứ tự được lưu ngay.</li>
    <li>Muốn dời nhiều lô cùng lúc: tick chọn các lô, rồi kéo một trong số đó tới vị trí mới. Cả nhóm được chèn liền nhau
        tại vị trí thả, giữ thứ tự cũ giữa chúng.</li>
    <li>Ở chế độ xem theo Line, kéo dòng đổi thứ tự riêng của Line, không ảnh hưởng thứ tự theo công đoạn.</li>
</ul>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Các thao tác trên danh sách KHSX (thứ tự, chiến dịch, chỉ định phòng, khuôn, đánh dấu) <b>lưu ngay</b> vào phần mềm,
        không qua nút 💾 và không hoàn tác bằng <b>Nháp</b>.</div>
</div>

<p class="text-muted small">
    Không còn nút / cột ⚡ <i>Sắp ngay sau kết thúc công đoạn trước</i> (đã bỏ 06/11/2026): sắp lịch tự động luôn xếp tiếp
    công đoạn sau của mọi lô ngay sau công đoạn trước, tới công đoạn cuối đã chọn, nên không cần đánh dấu từng lô
    (xem <a href="#chon-phong-gio">Cách chọn phòng &amp; giờ</a>).
</p>

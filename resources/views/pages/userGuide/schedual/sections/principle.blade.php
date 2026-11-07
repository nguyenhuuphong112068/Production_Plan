<p>
    Phần mềm xếp lịch theo <b>hai lớp</b> chạy nối tiếp nhau trong một lần bấm <b>Chạy</b>:
</p>

<div class="ug-flow">
    <div class="ug-flow-step"><span class="ug-flow-no">Lớp 1</span><b>Sắp lịch tự động</b>
        Đưa mọi lô vào phòng <b>sớm nhất có thể</b> để khai thác tối đa năng lực phòng sản xuất, mà vẫn đúng các ràng buộc
        (ngày NL/BB, thứ tự công đoạn, thời gian chờ, ngày nghỉ, không đè giờ trong phòng).</div>
    <div class="ug-flow-step"><span class="ug-flow-no">Lớp 2</span><b>Kiểm soát tồn BTP</b>
        Chỉ chạy khi có nhập Max. Đo bán thành phẩm chờ giữa các công đoạn và <b>giãn đầu nguồn</b> để tồn không vượt
        Max (xem <a href="#kiem-soat-ton-btp">Kiểm soát tồn BTP</a>).</div>
</div>

<figure class="ug-figure">
    <img src="{{ asset('img/userGuide/schedual/so-do-dong-chay.png') }}" alt="Dòng chảy công đoạn và 3 nhóm tồn BTP chờ">
    <figcaption>Dòng chảy công đoạn và 3 nhóm tồn BTP chờ (Chờ ĐH, Chờ BP, Chờ ĐG)</figcaption>
</figure>

<h3>Lớp 1 làm việc theo 5 bước</h3>
<ol class="ug-steps">
    <li><b>Đọc dữ liệu</b> của các lô chưa có lịch: kế hoạch, định mức, các ngày NL/BB, ngày nghỉ, lịch đang có
        (<a href="#du-lieu-dau-vao">Dữ liệu đầu vào</a>).</li>
    <li><b>Gom lô thành chiến dịch</b> để chạy liền mạch trong một phòng (<a href="#chien-dich">Chiến dịch</a>).</li>
    <li><b>Xếp theo nhóm ưu tiên</b>: nhóm trước chọn phòng và giờ trước, nhóm sau lấp vào chỗ còn trống
        (<a href="#thu-tu-uu-tien">Thứ tự ưu tiên</a>).</li>
    <li><b>Mỗi lô / chiến dịch chọn phòng cho giờ bắt đầu sớm nhất</b>, xong công đoạn này xếp luôn công đoạn sau của
        lô đó (<a href="#chon-phong-gio">Cách chọn phòng &amp; giờ</a>).</li>
    <li><b>Tự kiểm tra</b> hạn HH NL chính / HH BB và lịch BT-HC-TI, sắp lại nếu cần
        (<a href="#tu-kiem-tra">Tự kiểm tra sau khi xếp</a>).</li>
</ol>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>
        Sắp lịch tự động là <b>xếp tiến</b> (từ ngày bắt đầu đi tới): lô được đặt vào khoảng trống sớm nhất, không cố
        dồn lô sát ngày cần hàng. Vì vậy một lô cần hàng muộn vẫn có thể chạy sớm nếu phòng đang rảnh - Kiểm soát tồn
        BTP (lớp 2) là cách giãn những lô đó ra khi bán thành phẩm dồn quá nhiều.
    </div>
</div>

<p class="text-muted small mb-0">
    Tài liệu nguyên lý bản Word / PDF để trình bày với khách hàng được lưu theo từng phiên bản cùng mã nguồn; nội dung
    các mục Nguyên lý trong hướng dẫn này khớp bản mới nhất.
</p>

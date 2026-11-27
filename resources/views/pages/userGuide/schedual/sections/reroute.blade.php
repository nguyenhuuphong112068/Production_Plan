<p>
    Khi lô thực tế chạy sớm hoặc trễ hơn lịch, phần mềm tự <b>dịch các lô bị ảnh hưởng</b> trên lịch lý thuyết để lịch luôn sát
    thực tế. Bật / tắt bằng công tắc <b>Xác nhận và điều chỉnh lịch theo thời gian thực</b> (thử nghiệm) ở trang Xác Nhận Hoàn
    Thành, theo từng phân xưởng; chỉ người được phép mới đổi công tắc.
</p>

<h3>Khi nào lịch được dịch</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead><tr><th style="width: 220px">Thao tác</th><th>Cách dịch</th></tr></thead>
        <tbody>
            <tr><td>Bấm <b>Nhận phòng</b> trễ từ <b>30 phút</b> so với giờ bắt đầu lý thuyết</td><td><b>Chỉ đẩy trễ</b>: giờ kết thúc dự kiến = giờ kết thúc lý thuyết + độ trễ bắt đầu.</td></tr>
            <tr><td>Bấm <b>Trả phòng</b> (công tắc đang bật)</td><td>Dịch <b>2 chiều</b> theo giờ trả phòng, chỉ bù phần chênh còn lại so với giờ dự kiến.</td></tr>
            <tr><td><b>Tự động mỗi giờ</b> (đầu giờ): lô đã quá giờ bắt đầu lý thuyết từ <b>30 phút</b> mà <b>chưa Nhận phòng</b>,
                    giờ bắt đầu nằm trong <b>2 ngày</b> gần nhất, ở phòng <b>đang không mở</b> (Sẵn Sàng)</td>
                <td><b>Chỉ đẩy lui, không kéo sớm</b>: dời chính lô đó tới giờ quét (làm tròn lên 15 phút; vướng lịch bảo trì thì dời ra
                    sau), các lô sau dịch theo. Lô vẫn chưa Nhận phòng thì giờ sau bị dời tiếp. Lô quá giờ hơn 2 ngày không bị dời.
                    Phòng đang mở (Phòng Bận, Chờ / Đang VS sau BT) không bị dời: lịch được xếp lại lúc Trả phòng.<br>
                    Lần quét trùng lúc người sắp lịch đang sắp lịch thủ công (<b>Khóa TTSX</b>, kể cả khóa phòng cân), phân xưởng đang
                    <b>sắp lịch tự động</b>, hoặc đang có <b>Nhận / Trả phòng</b> chưa xong thì bỏ qua và <b>thử lại sau 15 phút</b>
                    (lô đã dời trước khi gặp khóa giữ nguyên).</td></tr>
            <tr><td>Bấm ✓ / ✓✓ ở Xác Nhận Hoàn Thành</td><td>Không dịch lịch.</td></tr>
        </tbody>
    </table>
</div>

<h3>Lô nào bị dịch</h3>
<ul>
    <li>Lô kế tiếp trên <b>cùng phòng</b> và công đoạn kế tiếp của <b>cùng lô</b>, lan truyền tiếp cho tới khi khoảng trống trên
        lịch hấp thụ hết độ lệch. Thứ tự lô giữ nguyên.</li>
    <li><b>Trễ</b>: khoảng trống hấp thụ độ trễ. <b>Sớm</b>: chỉ kéo sớm đúng lượng lô trước sớm lên, khoảng trống cố ý vẫn giữ;
        không kéo sớm hơn hiện tại và ngày có NL/BB.</li>
    <li><b>Ngày nghỉ</b>: dịch trễ thì được chạy trong ngày nghỉ (tăng ca) để hấp thụ trễ; lô vắt ngang ngày nghỉ chỉ lấn phần nghỉ
        vừa đủ. Kéo sớm thì không tăng ca.</li>
    <li><b>Phòng cân</b> (Cân nguyên liệu, Cân NL khác): Nhận phòng / Trả phòng ở phòng cân <b>chỉ dịch các lịch trên chính phòng
        cân đó</b> (vd. nhận / trả phòng W4 thì chỉ dịch lịch W4), không dịch Pha chế hay công đoạn sau. Ngược lại, lô ở công
        đoạn khác chạy sớm / trễ cũng không làm dịch lịch cân. Các lô cân cùng nhóm đang chồng giờ nhau vẫn giữ chồng giờ khi bị đẩy.</li>
</ul>
<p>Mọi lần dịch được ghi lại kèm giờ cũ để có thể hoàn tác. Lần quét tự động ghi người sửa là <i>Tự động (quét lô quá giờ)</i>,
    lý do bắt đầu bằng <i>Tịnh tuyến tự động: quá giờ chưa Nhận phòng</i> (xem ở Lịch Sử Thay Đổi Lịch và chuột phải › Lịch sử
    tịnh tuyến trên lịch).</p>

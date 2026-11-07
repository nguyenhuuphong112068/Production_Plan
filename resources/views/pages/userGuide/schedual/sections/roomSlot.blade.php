<p>
    Mỗi lô hoặc chiến dịch được đặt vào phòng cho phép <b>bắt đầu sớm nhất</b>, qua 5 bước:
</p>

<ol class="ug-steps">
    <li><b>Tính mốc sớm nhất</b> = ngày muộn nhất trong: ngày bắt đầu sắp lịch (và giờ hiện tại), Ngày có đủ NL /
        Ngày được phép cân (Đóng gói: Ngày có đủ BB), giờ công đoạn trước của lô kết thúc cộng thời gian chờ.</li>
    <li><b>Tìm khoảng trống</b> trong từng phòng có định mức cho sản phẩm và công đoạn đó: khoảng đầu tiên từ mốc trên
        đủ chứa lô (chuẩn bị + sản xuất), kể cả thời gian vệ sinh. Phòng đang chạy lô được tính bận tới giờ dự kiến kết
        thúc. Lô cần khuôn (Đóng gói; Định hình ở phòng S23, S24) còn phải có ít nhất một khuôn trong định mức
        <b>rảnh suốt khoảng đó</b>; nếu mọi bộ khuôn đang chạy ở phòng khác thì lô lùi tiếp tới khi có khuôn rảnh.
        Sản phẩm chưa khai báo khuôn vẫn được xếp bình thường nhưng lịch báo <i>Thiếu Khuôn</i>.</li>
    <li><b>Tránh ngày nghỉ:</b> lô không bắt đầu trong ngày nghỉ, nhưng lô đang chạy dở thì được chạy xuyên qua.</li>
    <li><b>Chọn phòng</b> cho giờ bắt đầu sớm nhất. Riêng Đóng gói khi bật <i>Hạn chế xuống khuôn</i>: ưu tiên phòng
        đang gắn đúng khuôn nếu chỉ bắt đầu trễ hơn không quá <i>Trễ tối đa để giữ khuôn</i> (mặc định 72 giờ).</li>
    <li><b>Ghi lịch</b> (kèm lịch vệ sinh ngay sau lô) rồi xếp tiếp công đoạn sau của lô theo cùng cách. Việc xếp
        tiếp áp dụng cho <b>mọi lô</b>, tới công đoạn cuối đã chọn trong hộp thoại. "Ngay" nghĩa là được xếp liền trong
        lượt, còn giờ bắt đầu vẫn theo bước 1-4: nếu phòng của công đoạn sau đang bận thì giữa hai công đoạn có khoảng chờ.</li>
</ol>

<h3>Trường hợp riêng</h3>
<ul>
    <li><b>Lô thẩm định</b> nhiều lô: Pha chế các lô sau ưu tiên cùng phòng với lô thẩm định đầu tiên.</li>
    <li><b>Lô bắt buộc phòng</b> (có chỉ định phòng) hoặc chạy <b>Theo Line</b>: chỉ xét đúng phòng đó.</li>
    <li><b>Đóng gói một phần</b>: thời gian Đóng gói tính theo tỷ lệ phần trăm đóng gói của lô.</li>
    <li>Lô đã có công đoạn sau bắt đầu chạy thì công đoạn trước còn thiếu lịch được <b>bỏ qua</b>, không xếp chen vào.</li>
</ul>

<div class="ug-example">
    <b>Ví dụ</b>
    Lô C Định hình được ở phòng ĐH-01 và ĐH-02. Trộn hoàn tất của lô C xong 10:00 ngày 12/11, lô thương mại không cần chờ.
    ĐH-01 trống từ 14:00 ngày 12/11, ĐH-02 trống từ 08:00 ngày 13/11 → lô C vào ĐH-01 lúc 14:00 ngày 12/11. Nếu ngày 12/11
    là ngày không sắp lịch thì mốc bắt đầu chuyển sang 06:00 ngày 13/11 và phòng nào trống sớm hơn từ mốc đó được chọn.
</div>

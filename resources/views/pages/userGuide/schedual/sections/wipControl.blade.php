<p>
    Sắp lịch tự động đưa mọi công đoạn vào sớm nhất có thể. Các công đoạn đầu (Pha chế, Trộn hoàn tất, Định hình)
    thường nhanh hơn công đoạn sau (Bao phim, Đóng gói), nên bán thành phẩm dồn lại chờ. Tồn BTP cao chiếm diện tích
    kho biệt trữ và vốn, kéo dài thời gian chờ (dễ quá hạn biệt trữ), mà không làm hàng ra nhanh hơn vì đầu ra vẫn bị
    giới hạn bởi công đoạn sau.
</p>

<h3>Cách bật</h3>
<ol class="ug-steps">
    <li>Trong hộp thoại Sắp lịch tự động, khối ②, bật <b>Giới hạn tồn BTP (viên)</b>.</li>
    <li>Nhập Max cho 1, 2 hoặc cả 3 nhóm chờ; để trống là không giới hạn. Max được nhớ cho lần sau.</li>
    <li>Bấm <b>Chạy</b>. Kiểm soát tồn BTP chạy ngay sau khi sắp lịch xong (không chạy với kiểu <i>Theo Line</i> và
        bước <i>CNL</i>).</li>
</ol>

<h3>Cách đo tồn</h3>
<p>
    Tồn mỗi nhóm là lượng BTP đã ra khỏi công đoạn nguồn nhưng chưa vào công đoạn tiêu thụ. Phần mềm dự báo tồn lúc
    <b>06:00 mỗi ngày trong 30 ngày tới</b>, theo đúng lịch vừa xếp; ngày nào tồn lớn hơn Max là một ngày vượt.
</p>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr><th style="width: 190px">Nhóm chờ</th><th>Tồn tăng khi xong</th><th style="width: 150px">Tồn giảm khi bắt đầu</th><th>Khi vượt Max thì ngưng</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Chờ Định hình (ĐH)</td>
                <td>Trộn hoàn tất, hoặc Pha chế của lô không có THT</td>
                <td>Định hình</td>
                <td>Trộn hoàn tất, Pha chế lùi theo</td>
            </tr>
            <tr>
                <td>Chờ Bao phim (BP)</td>
                <td>Định hình của lô bao phim</td>
                <td>Bao phim</td>
                <td>Định hình của lô bao phim</td>
            </tr>
            <tr>
                <td>Chờ Đóng gói / Ép vỉ (ĐG)</td>
                <td>Bao phim; Định hình của lô không bao phim; Trộn hoàn tất của lô không ĐH, không BP (lô không có THT: Pha chế)</td>
                <td>Đóng gói</td>
                <td>Bao phim; Định hình của lô không bao phim; THT / PC của lô không ĐH, không BP (khi Chờ Định hình cũng có Max)</td>
            </tr>
        </tbody>
    </table>
</div>

<h3>Nguyên lý ngưng nguồn</h3>
<p>
    Công đoạn tiêu thụ (nút cổ chai, có cài Max) <b>giữ nguyên lịch</b>; chỉ đầu nguồn được giãn ra. Một lô chỉ được vào
    công đoạn nguồn khi dự báo tồn của nhóm nó sắp vào còn dưới Max.
</p>
<ul>
    <li><b>Lô chưa được vào thì đợi.</b> Phòng nguồn không bỏ trống mà chạy thay lô khác: lô đi vào nhóm không cài Max
        hoặc nhóm còn dưới Max.</li>
    <li><b>Lô cần hàng muộn nhất đợi trước.</b> Lô sắp tới hạn (ngày cần hàng hoặc ngày NL/BB) được ép chạy đúng giờ dù
        tồn đang cao.</li>
    <li><b>Công đoạn sau lùi theo</b> lô bị đợi, giữ đúng thứ tự và thời gian chờ.</li>
    <li><b>Công đoạn trước được kéo sát lại</b> (vừa kịp), để không chuyển đống tồn sang nhóm chờ phía trước.</li>
    <li>Cài Max nhiều nhóm: xử lý <b>từ cuối dây chuyền lên</b> - Chờ Đóng gói trước, rồi Chờ Bao phim, cuối cùng Chờ
        Định hình. Một lô chỉ được chạy thay khi nhóm nó sắp vào cũng không vượt Max.</li>
</ul>

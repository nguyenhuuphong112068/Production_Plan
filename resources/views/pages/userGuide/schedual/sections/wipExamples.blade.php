<p>Các ví dụ dùng số liệu giả định, chỉ để minh hoạ cách phần mềm quyết định.</p>

<div class="ug-example">
    <b>Ví dụ 1 - Chờ Định hình vượt Max: ngưng Trộn hoàn tất, Pha chế lùi theo</b>
    Max Chờ Định hình = 30 triệu viên; dự báo ngày 14/10 là 36 triệu viên.
    <ol class="mb-0">
        <li>Phòng Trộn hoàn tất sắp trộn lô A (cần hàng 25/11). Trộn xong thì Chờ Định hình tăng thêm, nên lô A đợi.</li>
        <li>Phòng Trộn hoàn tất chạy thay lô B: lô không qua Định hình (trộn xong đi thẳng sang Đóng gói), thuộc nhóm Chờ
            Đóng gói đang dưới Max.</li>
        <li>Khi phòng Định hình dập bớt, tồn xuống dưới 30 triệu viên, lô A mới được trộn. Định hình, Bao phim, Đóng gói
            của lô A lùi theo.</li>
        <li>Pha chế của lô A được dời sát ngày trộn mới, không để bán thành phẩm nằm chờ ở bước trước.</li>
    </ol>
</div>

<div class="ug-example">
    <b>Ví dụ 2 - Chờ Bao phim vượt Max: ngưng Định hình của lô bao phim</b>
    Max Chờ Bao phim = 50 triệu viên; dự báo ngày 15/10 là 62 triệu viên.
    <ol class="mb-0">
        <li>Phòng Định hình sắp dập lô X (bao phim, cần hàng 30/11). Chờ Bao phim đang vượt nên lô X đợi.</li>
        <li>Phòng Định hình chạy thay lô Y không bao phim, đi thẳng sang Đóng gói.</li>
        <li>Khi Bao phim rút tồn xuống dưới 50 triệu viên, lô X mới được dập; Bao phim và Đóng gói của lô X lùi theo.</li>
        <li>Trộn hoàn tất của lô X được dời sát ngày dập mới, không để tồn dồn sang Chờ Định hình.</li>
    </ol>
</div>

<div class="ug-example">
    <b>Ví dụ 3 - Chờ Đóng gói vượt Max: ngưng mọi nguồn đổ vào Đóng gói</b>
    Max Chờ Đóng gói = 40 triệu viên; dự báo ngày 20/10 là 47 triệu viên. Ba nguồn đổ vào nhóm này được xử lý như sau:
    <ol class="mb-0">
        <li><b>Bao phim:</b> phòng Bao phim sắp bao lô C (cần hàng 28/11) thì lô C đợi. Mọi lô bao phim đều đổ vào Chờ
            Đóng gói nên phòng không có lô chạy thay; phòng chỉ chạy lô sắp tới hạn (xem Ví dụ 4).</li>
        <li><b>Định hình của lô không bao phim:</b> phòng Định hình sắp dập lô E (không bao phim) thì lô E đợi; phòng chạy
            thay lô F bao phim nếu Chờ Bao phim không cài Max hoặc còn dưới Max.</li>
        <li><b>Lô không qua Định hình và Bao phim:</b> Trộn hoàn tất (hoặc Pha chế, nếu lô không có Trộn hoàn tất) của lô
            G đợi. Bước này chỉ áp dụng khi nhóm Chờ Định hình cũng có cài Max.</li>
        <li>Khi Đóng gói rút tồn xuống dưới 40 triệu viên, các lô C, E, G lần lượt được chạy theo ngày cần hàng; các công
            đoạn sau của chúng lùi theo.</li>
    </ol>
</div>

<div class="ug-example">
    <b>Ví dụ 4 - Lô sắp tới hạn được ép chạy đúng giờ</b>
    Chờ Bao phim đang vượt Max, nhưng lô H (bao phim) cần hàng ngày 18/10, Ngày HH BB 22/10.
    <ol class="mb-0">
        <li>Nếu lô H đợi tới khi tồn giảm, Đóng gói của lô H sẽ trễ ngày cần hàng và Ngày HH BB.</li>
        <li>Vì vậy Định hình của lô H vẫn chạy đúng giờ đã xếp, dù tồn Chờ Bao phim tạm thời cao hơn Max.</li>
        <li>Lô H được ghi trong mục Cảnh báo của thông báo kết quả là lô phải chạy đúng hạn; các lô cần hàng muộn hơn sẽ
            đợi thay.</li>
    </ol>
</div>

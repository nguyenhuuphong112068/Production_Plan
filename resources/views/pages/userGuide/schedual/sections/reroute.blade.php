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
            <tr><td>Bấm <b>Nhận phòng</b> trễ</td><td><b>Chỉ đẩy trễ</b>: giờ kết thúc dự kiến = giờ kết thúc lý thuyết + độ trễ bắt đầu.</td></tr>
            <tr><td>Bấm <b>Trả phòng</b> (công tắc đang bật)</td><td>Dịch <b>2 chiều</b> theo giờ trả phòng, chỉ bù phần chênh còn lại so với giờ dự kiến.</td></tr>
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
</ul>
<p>Mọi lần dịch được ghi lại kèm giờ cũ để có thể hoàn tác.</p>

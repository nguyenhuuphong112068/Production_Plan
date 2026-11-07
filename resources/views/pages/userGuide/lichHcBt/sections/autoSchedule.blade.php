<p>
    Mở danh sách <b>Kế Hoạch</b> trên Lịch HC-BT Chart, bấm <b>Sắp Lịch</b>: phần mềm xếp mọi lịch BT-HC-TI đã gửi mà chưa có lịch,
    bắt đầu từ thời điểm hiện tại.
</p>

<h3>Nguyên lý</h3>
<ol class="ug-steps">
    <li><b>Gom nhóm:</b> thiết bị con cùng thiết bị lớn, cùng phòng thành một lịch; thời gian = tổng thời gian thực hiện các thiết bị con.</li>
    <li><b>Thứ tự:</b> phòng có ngày tới hạn sớm nhất xếp trước.</li>
    <li><b>Ngày bắt đầu mong muốn:</b> khoảng <b>7 ngày trước ngày tới hạn</b>, nhưng không sớm hơn ngày 1 của tháng tới hạn và
        không sớm hơn hiện tại.</li>
    <li><b>Không chồng lịch sản xuất:</b> phòng chỉ rảnh sau khi lô sản xuất và vệ sinh sau sản xuất kết thúc; các lịch BT xếp
        trong cùng lần chạy nối tiếp nhau, phân bổ đều tải theo ngày.</li>
    <li><b>Hạn BT:</b> hiệu chuẩn phải xong đúng ngày tới hạn; lịch hàng tháng được thêm 7 ngày; loại khác thêm 21 ngày.
        Lịch xếp sau hạn bị đánh dấu trễ (viền cảnh báo).</li>
    <li><b>Vệ sinh:</b> chỉ lịch Bảo trì có thời gian vệ sinh sau BT; Hiệu chuẩn và Tiện ích không có.</li>
</ol>

<h3>Huỷ lịch</h3>
<p>Bấm <b>Huỷ Lịch</b>: chọn <b>Ngày bắt đầu hủy</b> và <b>Chế độ hủy</b> (toàn bộ loại đang chọn, hoặc <i>Hủy theo phòng chọn</i>).
    Chỉ huỷ lịch chưa hoàn thành, từ ngày đã chọn.</p>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Khi Lịch Sản Xuất sắp lịch tự động, chiến dịch được xếp xuyên qua lịch BT-HC-TI chưa bắt đầu và lịch BT-HC-TI bị đè được
        dời đi (xem hướng dẫn <a href="{{ route('pages.userGuide.show', 'schedual') }}#tu-kiem-tra">Lịch Sản Xuất › Tự kiểm tra</a>).</div>
</div>

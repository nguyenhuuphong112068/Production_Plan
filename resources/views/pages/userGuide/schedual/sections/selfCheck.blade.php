<p>
    Xếp xong, phần mềm tự rà lại hai loại hạn trước khi trả kết quả.
</p>

<h3>a) Ngày HH NL chính và Ngày HH BB không được vi phạm</h3>
<ol class="ug-steps">
    <li>Nếu Pha chế bắt đầu <b>sau Ngày HH NL chính</b>, hoặc Đóng gói bắt đầu <b>sau Ngày HH BB</b> (so theo ngày), mà
        vẫn còn kịp: phần mềm huỷ kết quả vừa xếp và xếp lại, đưa lô đó cùng cả chiến dịch của nó lên đầu hàng
        (nhóm 1 ở <a href="#thu-tu-uu-tien">Thứ tự ưu tiên</a>).</li>
    <li>Lặp <b>tối đa 3 lần</b> sắp lại, giữ phương án có ít lô trễ nhất (rồi tới tổng số ngày trễ ít nhất).</li>
    <li>Lô <b>không thể kịp</b> (hạn đã qua trước hôm nay, hoặc trước ngày có đủ NL / BB) được liệt kê riêng trên thông
        báo kết quả để người lập lịch xử lý.</li>
</ol>
<div class="ug-callout">
    <i class="fas fa-clock"></i>
    <div>Mỗi lần sắp lại tốn khoảng bằng một lần chạy, nên có lô cần chen thì thời gian chạy tăng gấp đôi hoặc hơn.</div>
</div>

<h3>b) Lịch Bảo trì, Hiệu chuẩn, Tiện ích (BT-HC-TI)</h3>
<ul>
    <li>Chiến dịch được xếp <b>xuyên qua</b> lịch BT-HC-TI chưa bắt đầu, để không bị cắt khúc. Lô lẻ vẫn tránh lịch
        BT-HC-TI. BT-HC-TI đã bắt đầu làm thì luôn được giữ.</li>
    <li>Lịch BT-HC-TI bị đè được <b>dời sang khoảng trống kế tiếp</b> trong cùng phòng, giữ nguyên độ dài; được dời vào
        cả ngày nghỉ (bảo trì thường làm cuối tuần).</li>
    <li>Nếu dời ra sau làm trễ hạn BT, phần mềm tìm khoảng trống <b>sớm hơn</b>, gần giờ cũ nhất mà vẫn kịp hạn.
        Hạn BT: hiệu chuẩn đúng ngày tới hạn; lịch hàng tháng thêm 7 ngày; loại khác thêm 21 ngày.</li>
    <li>Không còn khoảng trống kịp hạn thì lịch đó được báo đỏ trên thông báo kết quả.</li>
</ul>

<h3>Nguyên lý tự động phân công (sản xuất)</h3>
<ol class="ug-steps">
    <li>Lấy nhân sự từ lịch trực của ngày, gom theo ca (C1, C2, HC...). <b>Bỏ người nghỉ phép</b> (kể cả phép chờ duyệt) và người
        nghỉ dài hạn.</li>
    <li>Mỗi phòng cần bao nhiêu người đọc từ ô <b>số lượng nhân sự cần</b> của ca. Người <b>chưa được cấp phòng đó</b> (chưa có bậc
        kỹ năng tại phòng) bị loại khỏi phòng đó.</li>
    <li><b>Ưu tiên tay nghề</b>: người có bậc kỹ năng cao nhất tại phòng được chọn trước.</li>
    <li><b>Chia đều</b> (lần lượt): mỗi phòng có nhu cầu nhận 1 người, rồi mới quay lại xếp người thứ 2..., để không phòng nào giành
        hết nhân sự.</li>
    <li>Xong tự mở báo cáo: phòng / ca còn thiếu người để xếp tay.</li>
</ol>
<p>Phân công BT-HC tự động cũng chia đều như trên nhưng không xét bậc kỹ năng và phòng.</p>

<h3>Ràng buộc khi xếp tay</h3>
<ul>
    <li>Không xếp một người vào cùng phòng nhiều lần trong ngày.</li>
    <li>Không xếp người vào phòng chưa được cấp (chặn).</li>
    <li>Người nghỉ phép bị gạch tên, không kéo thả được.</li>
    <li><b>Cảnh báo lệch ca</b> khi xếp khác ca trực trên eO2.</li>
    <li>Số người chuyên nghiệp không được vượt tổng số người cần.</li>
    <li>Vượt trần chính sách tăng ca phải được chấp nhận.</li>
    <li>Cảnh báo khi nhân viên chưa được khai báo sản phẩm đang làm (nếu chức năng cảnh báo Nhân sự - Sản phẩm đang bật).</li>
</ul>

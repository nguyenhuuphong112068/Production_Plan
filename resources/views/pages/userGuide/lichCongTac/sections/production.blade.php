<p>
    <b>Lịch Công Tác Sản Xuất</b> của một tổ trong một ngày. Chọn tổ và ngày ở đầu trang. Mỗi dòng là một phòng / thiết bị.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/lichCongTac/phan-cong-san-xuat.png', 'caption' => 'Màn hình Lịch Công Tác › Sản Xuất'])

<h3>Các cột</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead><tr><th style="width: 190px">Cột</th><th>Ý nghĩa</th></tr></thead>
        <tbody>
            <tr><td>Phòng / Thiết Bị</td><td>Phòng của tổ.</td></tr>
            <tr><td>Lịch Lý Thuyết</td><td>Lô / lịch đang xếp vào phòng trong ngày (ẩn / hiện được).</td></tr>
            <tr><td>Ca / SL</td><td>Ca làm việc (1, 2, 3, 4, HC, Khác) và <b>số lượng nhân sự cần</b>, kèm số người chuyên nghiệp (bậc ≥ 3) tối thiểu.</td></tr>
            <tr><td>Nhân sự</td><td>Chọn nhân viên cho ca (kéo từ thanh nhân sự bên cạnh hoặc chọn trong ô). Mỗi người hiện bậc kỹ năng tại phòng.</td></tr>
            <tr><td>Hoạt Động / Chi Tiết Công Việc</td><td>Lô / việc sẽ làm; <b>Thêm lô từ lịch của phòng</b>, nội dung, lưu ý.</td></tr>
        </tbody>
    </table>
</div>

<h3>Các nút</h3>
<ul>
    <li><b>Thêm Ca</b>, <b>Nhân bản ca này</b> / <b>Nhân bản toàn bộ ca</b>, <b>Xóa ca này</b>.</li>
    <li><b>Thêm Công Tác Khác</b>: công việc ngoài phòng sản xuất (vị trí công tác, giờ, nội dung); <i>Nhân Bản Công Tác Khác</i> sang các ngày khác.</li>
    <li><b>Tự động phân công</b>: xếp nhân sự tự động (xem <a href="#tu-dong-phan-cong">Tự động phân công</a>).</li>
    <li><b>Lưu toàn bộ lịch</b>. Tick <i>Chỉ lưu làm Gợi ý Nhân sự</i> nếu chưa muốn thành phân công chính thức.</li>
    <li><b>Xem báo cáo</b>: Tổng yêu cầu, đã đáp ứng, còn thiếu, tỷ lệ; chi tiết theo ca, nhân sự chưa sắp.</li>
    <li><b>Chấp Nhận Tăng ca</b> (vượt trần chính sách), <b>Lịch sử duyệt TC</b>, <b>Lịch sử chỉnh giờ công tác</b>.</li>
    <li><b>In Lịch</b>, <b>Xuất Excel</b>, <b>Đồng bộ</b> lịch trực từ eO2.</li>
</ul>
<p>Thanh nhân sự bên cạnh lọc theo tổ: <i>Không chính qui</i>, <i>Nhân sự &lt; 8h</i>, <i>Nhân sự &gt; 8h</i>; biểu tượng con mắt xem bậc kỹ năng.</p>

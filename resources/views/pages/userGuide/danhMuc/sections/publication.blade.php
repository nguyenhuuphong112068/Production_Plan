<p>
    Theo dõi các nội dung cần làm khi một mã <b>lên ấn bản</b> (đổi công thức, đổi hồ sơ BMR / BPR) theo từng <b>kỳ theo dõi</b>
    của mỗi phân xưởng. Dược sĩ phụ trách quyết định có thực hiện không và ghi kết quả.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/danhMuc/theo-doi-an-ban.png', 'caption' => 'Màn hình Danh Mục › Theo Dõi Lên Ấn Bản'])

<h3>Kỳ theo dõi</h3>
<ul>
    <li>Mỗi chu kỳ chạy từ <b>ngày 20</b> tháng này tới <b>ngày 19</b> tháng sau; kỳ mang tên <b>tháng lên ấn bản</b>, đi sau
        chu kỳ 2 tháng (chu kỳ 20/07 - 19/08 là kỳ tháng 09). Kỳ mới được <b>tạo tự động</b>.</li>
    <li>Kỳ mới tự nhận mọi nội dung còn dang dở của kỳ liền trước.</li>
    <li>Màn hình đầu là danh sách kỳ (Phân Xưởng, Kỳ Theo Dõi, Số Mã BTP, Số Mã TP, Trạng Thái). Lọc theo phân xưởng, bấm
        <span class="btn btn-sm btn-primary ug-ui">Xem</span> để mở chi tiết. Xem được kỳ của mọi phân xưởng, nhưng
        <b>chỉ sửa được kỳ của phân xưởng mình</b>.</li>
</ul>

<h3>Màn hình chi tiết kỳ</h3>
<ol class="ug-steps">
    <li><b>Đồng bộ mã hiệu lực</b>: lấy danh sách mã BTP (BMR) và TP (BPR) đang hiệu lực vào kỳ. Lọc <i>Tất cả / Chỉ BMR / Chỉ BPR</i>,
        và lọc theo kế hoạch: <i>Có kế hoạch / Chưa có kế hoạch / Có lô phát sinh</i> - để biết mã nào sắp được sản xuất
        (đếm số lô trong kế hoạch tháng của kỳ, kể cả kế hoạch chưa gửi).</li>
    <li><b>Tạo nội dung theo dõi</b>: nhập <b>Nội dung công việc</b>, tick các mã áp dụng (<i>Chọn tất cả kết quả lọc</i> /
        <i>Bỏ chọn hết</i>) rồi <b>Lưu</b>. Một nội dung có thể dùng chung cho nhiều mã; sửa nội dung là sửa cho tất cả mã
        đang dùng (badge cho biết số mã).</li>
    <li>Với từng mã: ghi <b>Quyết Định</b> (<i>Có thực hiện / Không thực hiện</i>), <b>Ngày hoàn thành dự kiến</b>,
        <b>Kết Quả Thực Hiện</b> và ngày hoàn thành, đánh dấu <b>Hồ sơ sẵn sàng</b>, ghi <b>Ý kiến DSPT</b>. Rê chuột lên
        từng ô để biết người ghi gần nhất.</li>
    <li>Nội dung chưa xong có thể <b>chuyển sang kỳ sau</b> (nút ghi rõ tên kỳ đích).</li>
    <li>Nút <i class="fas fa-history"></i> trên từng nội dung / mã để xem lịch sử thay đổi.</li>
</ol>
<p>Cột version cho biết <b>version công thức hiện hành trên MMS</b> tại lúc xem (không phải lúc đồng bộ).</p>

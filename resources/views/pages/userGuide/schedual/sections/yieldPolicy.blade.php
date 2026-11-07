<p>
    Đặt <b>mục tiêu sản lượng</b> theo tháng / ngày và <b>giới hạn tồn bán thành phẩm</b>. Mục tiêu dùng để kiểm tra trước khi
    <b>Submit lịch</b> (📤) trên Lịch Sản Xuất Chart.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/schedual/chinh-sach-san-luong.png', 'caption' => 'Màn hình Lịch Sản Xuất › Chính Sách Sản Lượng'])

<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tháng, Năm', true, 'Tháng áp dụng. Bấm <b>Xem Sản Lượng</b> để tải bảng theo ngày.'],
    ['Target cả tháng — ĐV', false, 'Mục tiêu sản lượng cả tháng (đơn vị liều).'],
    ['Target mỗi ngày — ĐV', false, 'Mục tiêu mặc định mỗi ngày. <b>Áp Dụng Target Mặc Định</b> điền cho mọi ngày.'],
    ['Ngưỡng đáp ứng mỗi ngày để Submit lịch', false, '% sản lượng lý thuyết / target ngày tối thiểu (mặc định 100).'],
    ['Ghi chú', false, ''],
]])
<p class="mt-2">Bấm <b>Lưu Chính Sách</b>. Bảng theo ngày (Ngày, Thứ, SL LT, Target Ngày, % Đạt, Trạng Thái) cho sửa target riêng
    từng ngày, bấm <b>Lưu Tất Cả Override</b>. Phần <b>Giới hạn tồn bán thành phẩm</b>: nhập tồn tối thiểu / tối đa theo tổ, bấm <b>Lưu giới hạn</b>.</p>

<h3>Submit lịch bị chặn khi nào</h3>
<ul>
    <li>Còn lô (từ hôm nay trở đi) vi phạm nặng: <b>ngày đáp ứng NL/BB</b>, <b>không đáp ứng ngày cần hàng</b>, hoặc lỗi màu xám
        đen. Hộp thoại liệt kê từng lô và lỗi.</li>
    <li>Đã cài chính sách cho tháng đang xem, và: có ngày làm việc (bỏ qua ngày nghỉ, ngày không có target) có sản lượng lý thuyết dưới ngưỡng % target ngày, hoặc tổng tháng chưa đạt 100% target tháng. Tháng chưa cài chính sách thì không kiểm tra sản lượng.</li>
</ul>
<p>Các màu chỉ cảnh báo (khuôn, sai thiết bị nguồn NL, quá hạn biệt trữ, thẩm định vệ sinh, quá giờ chưa xác nhận) không chặn Submit.</p>

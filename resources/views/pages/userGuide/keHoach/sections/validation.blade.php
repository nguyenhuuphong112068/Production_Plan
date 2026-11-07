<p>
    Theo dõi <b>thẩm định nguyên liệu</b> (nguồn mới, thay đổi theo kiểm soát thay đổi): khai báo nguyên liệu cần theo dõi và các
    bán thành phẩm áp dụng, phần mềm gắn các lô sản xuất dùng nguyên liệu đó để theo dõi tiến độ.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/keHoach/theo-doi-tham-dinh.png', 'caption' => 'Màn hình Kế Hoạch › Theo Dõi Thẩm Định'])

<h3>Thêm nguyên liệu theo dõi</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/keHoach/theo-doi-tham-dinh-form.png', 'caption' => 'Hộp thoại thêm mới - Theo Dõi Thẩm Định'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Mã Nguyên Liệu', true, 'Ví dụ <i>12110120001</i>, tối đa 50 ký tự.'],
    ['Tên Nguyên Liệu', true, 'Ví dụ <i>Kollidon VA 64</i>.'],
    ['Mục Đích Thẩm Định', false, 'Ví dụ: bổ sung nguồn, theo dõi độ ổn định...'],
    ['Số Kiểm Soát Thay Đổi (CC_num)', false, 'Ví dụ <i>QA/CC010126</i>.'],
    ['Ghi Chú Chung', false, ''],
    ['Danh sách Bán Thành Phẩm áp dụng', true, 'Bấm <b>Chọn từ danh mục BTP</b>, tick các BTP rồi <b>Xác nhận chọn</b>; nhập số lô theo dõi và ghi chú cho từng BTP.'],
]])

<h3>Theo dõi và duyệt</h3>
<ul>
    <li>Bảng chính: Mã NL, Tên Nguyên Liệu, Mục Đích, Số CC, Trạng Thái, Bán Thành Phẩm Theo Dõi. Mới tạo ở trạng thái
        <i>Đang theo dõi</i>.</li>
    <li>Mở một dòng để xem <b>Các lô sản xuất đã gắn</b> (số lô, phân xưởng, thời gian đóng gói dự kiến, tiến độ) và <b>Thống Kê
        Theo Bán Thành Phẩm</b>; bấm <i class="fas fa-external-link-alt"></i> để mở kế hoạch chứa lô.</li>
    <li><b>Sửa</b> để đổi thông tin; <b>Duyệt</b> khi đã thẩm định xong.</li>
</ul>

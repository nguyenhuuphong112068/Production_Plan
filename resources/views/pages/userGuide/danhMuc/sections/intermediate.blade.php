<p>
    Danh sách mã bán thành phẩm. Các cột <b>Cân NL, Xử lý Bao Bì, Cân NL Khác, PC, THT, ĐH, BP</b> cho biết mã đi qua công đoạn
    nào và thời gian biệt trữ sau công đoạn đó. Thông tin này quyết định các công đoạn được tạo cho mỗi lô khi lập kế hoạch.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/danhMuc/ban-thanh-pham.png', 'caption' => 'Màn hình Danh Mục › Bán Thành Phẩm'])

<h3>Các ô nhập (Thêm Danh Mục / Cập nhật)</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/danhMuc/ban-thanh-pham-form.png', 'caption' => 'Hộp thoại thêm mới - Danh Mục Bán Thành Phẩm'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Sản Phẩm', true, 'Chọn từ Dữ Liệu Gốc › Tên Sản Phẩm.'],
    ['Mã Bán Thành Phẩm', true, 'Mã BTP theo MMS (ví dụ <i>IS...</i>). Không trùng.'],
    ['Dạng Bào Chế', true, 'Chọn từ Dữ Liệu Gốc › Dạng Bào Chế.'],
    ['Cỡ Lô Theo Khối Lượng', true, 'Cỡ lô và <b>Đơn Vị</b>.'],
    ['Cỡ Lô Theo Đơn Vị Liều', true, 'Số viên / nang của một lô.'],
    ['Công Đoạn Bao Gồm', false, 'Tick các công đoạn mã đi qua: <b>Cân Nguyên Liệu</b>, <b>Có xử lý bao bì không?</b>, <b>Pha Chế</b>, <b>Cốm sửa hạt khô</b> (nhập tỷ lệ, ví dụ 1:4), <b>Trộn Hoàn Tất</b>, <b>Định Hình</b>, <b>Bao Phim</b>. Ô bên cạnh mỗi công đoạn là <b>thời gian biệt trữ</b> sau công đoạn đó.'],
    ['Thời gian biệt trữ từ Pha Chế đến trước ĐGSC', false, 'Tổng thời gian biệt trữ cho phép. Mã có khai ô này được Sắp lịch tự động coi là <b>sản phẩm nhạy cảm</b> (xem hướng dẫn Lịch Sản Xuất).'],
    ['Lý Do Thay Đổi', true, 'Chỉ khi <b>Cập nhật</b>: bắt buộc, tối đa 500 ký tự. Được lưu vào lịch sử thay đổi.'],
]])

<div class="ug-callout is-warn">
    <i class="fas fa-sync-alt"></i>
    <div>Sửa cỡ lô của một BTP thì phần mềm <b>tự đồng bộ cỡ lô</b> sang các mã TP đang gắn với BTP đó (ghi lịch sử với lý do
        "Đồng bộ cỡ lô theo BTP ...").</div>
</div>

<h3>Công thức</h3>
<p>
    Nút ở cột <b>Công Thức</b> mở <b>Công Thức Bán Thành Phẩm</b>: danh sách nguyên liệu (mã, tên, khối lượng, đơn vị) và
    version của ấn bản hiện hành trên MMS. Rê chuột lên nút để biết đang là <i>Công thức MMS - ấn bản hiện hành</i> hay
    <i>Công thức giả định</i> (mã chưa có công thức trên MMS).
</p>

<h3>Vô hiệu</h3>
<p>Vô hiệu một mã BTP bắt buộc nhập lý do (tối đa 480 ký tự). Kích hoạt lại không cần lý do.</p>

<h3>Mã giả định cũ</h3>
<p>Phần mềm không còn tạo mã giả định mới. Các mã giả định tạo trước đây (dòng tô nổi bật) chỉ hiện với tài khoản có quyền xem
    mã giả định; nút vô hiệu trên các dòng này là <b>Hủy mã giả định</b> (bắt buộc ghi lý do).</p>

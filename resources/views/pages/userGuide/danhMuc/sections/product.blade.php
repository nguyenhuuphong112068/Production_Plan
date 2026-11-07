<p>
    Danh sách mã thành phẩm. Mỗi mã TP gắn với một mã BTP và có thị trường, qui cách riêng. Kế hoạch sản xuất lập theo mã TP.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/danhMuc/thanh-pham.png', 'caption' => 'Màn hình Danh Mục › Thành Phẩm'])

<h3>Các ô nhập (Thêm Danh Mục / Cập nhật)</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/danhMuc/thanh-pham-form.png', 'caption' => 'Bấm Thêm Danh Mục: chọn mã Bán Thành Phẩm trước, sau đó mới nhập thông tin Thành Phẩm'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Mã Bán Thành Phẩm', true, 'Bấm để mở danh sách <b>DANH MỤC BÁN THÀNH PHẨM</b> và chọn: tên sản phẩm, cỡ lô được điền theo BTP. Một cặp BTP - TP chỉ được khai một lần.'],
    ['Mã Thành Phẩm', true, 'Mã TP theo MMS (ví dụ <i>FP...</i>).'],
    ['Tên Sản Phẩm Theo BPR', true, 'Tên trên hồ sơ đóng gói.'],
    ['Cỡ Lô Theo Khối Lượng / Theo Đơn Vị Liều', true, 'Lấy theo BTP.'],
    ['Thị Trường', true, 'Chọn từ Dữ Liệu Gốc › Thị Trường.'],
    ['Qui Cách', true, 'Chọn từ Dữ Liệu Gốc › Qui Cách Sản Phẩm.'],
    ['Công Đoạn Bao Gồm: ĐGSC - ĐGTC', false, 'Tick nếu mã có đóng gói sơ cấp - thứ cấp.'],
    ['Lý Do Thay Đổi', true, 'Chỉ khi <b>Cập nhật</b>: bắt buộc, tối đa 500 ký tự.'],
]])

<h3>Công thức bao bì</h3>
<p>Nút ở cột <b>Công Thức</b> mở danh sách bao bì đóng gói (mã, tên, số lượng, đơn vị, version) của ấn bản hiện hành trên MMS.</p>
<p>Vô hiệu mã TP bắt buộc nhập lý do, như Bán Thành Phẩm. Khi chọn Mã Bán Thành Phẩm chỉ chọn được mã BTP thật; mã TP giả định
    tạo trước đây vẫn hiện (với tài khoản có quyền xem mã giả định) và được hủy bằng nút vô hiệu.</p>

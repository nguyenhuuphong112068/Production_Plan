<p>
    Khai báo phòng và thời gian cho từng mã ở từng công đoạn. Chọn công đoạn ở ô lọc đầu trang (<b>Cân NL, Pha Chế, Trộn Hoàn
    Tất, Định Hình, Bao Phim, Đóng Gói</b>): bảng liệt kê các mã có công đoạn đó (theo danh mục BTP / TP) của phân xưởng đang
    chọn. Mã chưa có định mức hiện nút thêm ở cột <b>Thêm</b>.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/dinhMuc/dinh-muc-san-xuat.png', 'caption' => 'Màn hình Định Mức › Sản Xuất'])

<h3>Các ô nhập (thêm định mức)</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/dinhMuc/dinh-muc-san-xuat-form.png', 'caption' => 'Hộp thoại thêm mới - Định Mức Sản Xuất'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Sản Phẩm / Mã Sản Phẩm', true, 'Điền sẵn theo dòng đã bấm.'],
    ['Phòng Sản Xuất', true, 'Chọn <b>một hoặc nhiều</b> phòng của công đoạn. Mỗi phòng thành một dòng định mức; Sắp lịch tự động chỉ xếp lô vào các phòng này.'],
    ['Thời Gian Chuẩn Bị', true, 'Dạng <b>HH:mm</b>, phút là 00, 15, 30 hoặc 45. Chỉ cộng cho lô đầu của chiến dịch.'],
    ['Thời Gian Sản Xuất', true, 'HH:mm. Thời gian chạy một lô.'],
    ['Vệ Sinh Cấp I', true, 'HH:mm. Vệ sinh giữa các lô trong cùng chiến dịch.'],
    ['Vệ Sinh Cấp II', true, 'HH:mm. Vệ sinh khi hết chiến dịch hoặc đổi sản phẩm.'],
    ['Số lô chiến dịch tối đa', true, 'Số nguyên dương: tối đa bao nhiêu lô cùng sản phẩm được gom thành một chiến dịch.'],
    ['Hệ Số chiến Dịch', false, 'Số thập phân 1 chữ số lẻ: hệ số tăng thời gian khi chạy chiến dịch.'],
    ['Ghi Chú', false, ''],
]])

<h3>Sửa nhanh trên bảng</h3>
<ul>
    <li>Các ô thời gian (Chuẩn Bị, Sản Xuất, Vệ Sinh Cấp I, II), Số Lô CĐ, Hệ Số CD sửa trực tiếp trên dòng; sai định dạng
        thì báo "Lỗi định dạng!".</li>
    <li><b>Bồn LP</b>, <b>Khử Ẩm EV</b>: đánh dấu lô của mã này cần bồn LP / cần khử ẩm. Sắp lịch tự động xét các yêu cầu này
        khi tìm khoảng trống cho lô.</li>
    <li>Nút <span class="btn btn-sm btn-danger ug-ui"><i class="fas fa-lock"></i></span> vô hiệu một dòng định mức (phòng đó
        không còn được dùng cho mã này); <span class="btn btn-sm btn-info ug-ui"><i class="fas fa-history"></i></span> xem lịch sử.</li>
</ul>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Đổi định mức không tự sửa lịch đã xếp. Muốn lịch theo định mức mới thì xoá lịch các lô liên quan rồi sắp lịch lại.</div>
</div>

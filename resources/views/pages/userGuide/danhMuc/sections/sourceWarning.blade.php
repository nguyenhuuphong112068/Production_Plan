<p>
    Khai báo <b>ma trận cảnh báo</b>: với một mã BTP (và một nguồn nguyên liệu), thị trường nào được phép và thiết bị nào được
    phép chạy. Khi tạo / sửa lô ở Kế Hoạch Sản Xuất, phần mềm đối chiếu thị trường của mã TP với ma trận và cảnh báo nếu không
    khớp.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/danhMuc/canh-bao-nguon-nl.png', 'caption' => 'Màn hình Danh Mục › Cảnh Báo Nguồn NL'])

<h3>Các ô nhập (Thêm Ma Trận / Cập nhật)</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/danhMuc/canh-bao-nguon-nl-form.png', 'caption' => 'Hộp thoại thêm mới - Cảnh Báo Nguồn NL'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Tên Sản Phẩm / Mã Bán Thành Phẩm', true, 'Chọn mã BTP; <b>Cỡ Lô</b> tự điền.'],
    ['Nguồn Nguyên Liệu (Mã NL)', false, 'Chọn từ công thức ấn bản mới nhất trên MMS. Để trống = áp dụng cho mọi nguồn NL.'],
    ['Thị Trường Được Phép', false, 'Bấm <b>Thêm thị trường</b> để thêm dòng; chọn thị trường và (khi cần tách riêng) <b>kênh / khách hàng</b>, ví dụ <i>Tender</i>, tối đa 50 ký tự. Để trống = không giới hạn thị trường.'],
    ['Thiết Bị Được Phép Thực Hiện Theo Công Đoạn', false, 'Tick thiết bị theo từng công đoạn (Pha chế, Định hình, Bao phim...; không có công đoạn cân). Để trống = không giới hạn thiết bị.'],
    ['Ghi Chú', false, ''],
]])

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>
        <ul class="mb-0">
            <li>Bỏ trống cả thị trường lẫn thiết bị thì dòng khai báo không cảnh báo được gì.</li>
            <li>Cùng một mã BTP + mã NL, mỗi thị trường (kèm kênh) chỉ được khai một lần trong phân xưởng.</li>
            <li>Lô chỉ mang thị trường cấp quốc gia nên đối chiếu theo thị trường; kênh / khách hàng chỉ hiện trong nhãn để người
                lập kế hoạch tự đối chiếu.</li>
        </ul>
    </div>
</div>

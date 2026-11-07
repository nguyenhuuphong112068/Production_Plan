<p>
    Các lô <b>cùng sản phẩm</b> (cùng mã BTP / TP), ngày cần hàng gần nhau được gom thành một <b>chiến dịch</b>, tối đa
    bằng số lô khai trong định mức. Chiến dịch chạy liền trong cùng một phòng: giữa các lô chỉ vệ sinh cấp I, hết chiến
    dịch mới vệ sinh cấp II.
</p>

<ul>
    <li><b>Lợi ích:</b> ít thời gian vệ sinh, ít lần thay khuôn, phòng chạy được nhiều lô hơn.</li>
    <li><b>Xếp nguyên khối:</b> chiến dịch không bị tách ra nhiều phòng hay nhiều đoạn.</li>
    <li><b>Mốc bắt đầu:</b> không sớm hơn ngày muộn nhất trong các lô của chiến dịch (Ngày có đủ NL, Ngày được phép
        cân; riêng Đóng gói là Ngày có đủ BB). Một lô thiếu NL làm cả chiến dịch chờ theo.</li>
    <li><b>Từng lô trong chiến dịch</b> vẫn chờ công đoạn trước của chính nó xong (cộng thời gian chờ) rồi mới được bắt
        đầu.</li>
    <li><b>Xếp xuyên lịch BT-HC-TI chưa bắt đầu</b> để không bị cắt khúc; lịch BT-HC-TI bị đè được dời sau đó
        (xem <a href="#tu-kiem-tra">Tự kiểm tra</a>). Lô lẻ thì vẫn tránh lịch BT-HC-TI.</li>
</ul>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>
        Chiến dịch <b>không tự tách</b> khi bị trễ hạn. Muốn tách, người lập lịch tạo lại chiến dịch thủ công trên
        <a href="#danh-sach-khsx">danh sách KHSX</a> (xoá mã chiến dịch rồi tạo lại cho từng nhóm lô) trước khi chạy sắp
        lịch tự động.
    </div>
</div>

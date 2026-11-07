<p>
    <b>Định mức Khuôn Định Hình cho Bán Thành Phẩm</b>: mỗi mã BTP dùng được những khuôn Định hình (khuôn dập) nào. Bảng: Mã BTP,
    Tên Sản Phẩm, Cỡ Lô, Khuôn Định Hình, Phòng SX Đã định mức.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/dinhMuc/dinh-muc-khuon-dh.png', 'caption' => 'Màn hình Định Mức › Khuôn Định Hình'])
<ol class="ug-steps">
    <li>Ở cột <b>Khuôn Định Hình</b>, chọn một hoặc nhiều khuôn (khuôn có Công Đoạn = Định Hình ở Dữ Liệu Gốc › Khuôn Mẫu).
        Ô chỉ hiện khuôn lắp được cho loại máy của các phòng ở cột <b>Phòng SX Đã định mức</b> (khuôn đã chọn trước đó luôn được giữ); tên khuôn kèm loại máy trong ngoặc.</li>
    <li>Khuôn Định hình chỉ được kiểm soát ở các phòng Định hình đã chọn <b>Loại Máy Định Hình</b> (Dữ Liệu Gốc › Phòng Sản Xuất);
        số bộ khuôn (Số Lượng) giới hạn số phòng chạy cùng khuôn cùng lúc.</li>
</ol>
<div class="ug-callout"><i class="fas fa-info-circle"></i><div>
    Bảng chỉ liệt kê BTP có định mức Định hình ở phòng Định hình có loại máy (hiện là S23, S24 - máy IMA Zibo). Cùng BTP chạy ở phòng
    chưa chọn loại máy (ví dụ S25, S36) thì không kiểm tra khuôn. Muốn kiểm soát khuôn thêm máy nào (máy dập viên, máy đóng nang khác):
    khai loại máy ở Dữ Liệu Gốc › Nhóm Máy (Công Đoạn Định Hình), chọn loại máy cho phòng, khai khuôn rồi gán khuôn cho BTP ở trang này.
    BTP chưa khai báo khuôn vẫn được sắp lịch bình thường, nhưng lô ở phòng có loại máy sẽ báo <i>Thiếu Khuôn</i>.
</div></div>

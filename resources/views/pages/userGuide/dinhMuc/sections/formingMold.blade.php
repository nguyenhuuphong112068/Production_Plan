<p>
    <b>Định mức Khuôn Định Hình cho Bán Thành Phẩm</b>: mỗi mã BTP dùng được những khuôn Định hình (khuôn dập) nào. Bảng: Mã BTP,
    Tên Sản Phẩm, Cỡ Lô, Khuôn Định Hình, Phòng SX Đã định mức.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/dinhMuc/dinh-muc-khuon-dh.png', 'caption' => 'Màn hình Định Mức › Khuôn Định Hình'])
<ol class="ug-steps">
    <li>Ở cột <b>Khuôn Định Hình</b>, chọn một hoặc nhiều khuôn (khuôn có Công Đoạn = Định Hình ở Dữ Liệu Gốc › Khuôn Mẫu).</li>
    <li>Khuôn Định hình chỉ được kiểm soát ở các phòng Định hình có yêu cầu khuôn (hiện là S23, S24); số bộ khuôn (Số Lượng)
        giới hạn số phòng chạy cùng khuôn cùng lúc.</li>
</ol>
<div class="ug-callout"><i class="fas fa-info-circle"></i><div>
    Bảng chỉ liệt kê BTP có định mức Định hình ở phòng S23 / S24. Cùng BTP chạy ở phòng khác (ví dụ S25, S36) thì không
    kiểm tra khuôn. BTP chưa khai báo khuôn vẫn được sắp lịch bình thường, nhưng lô trên S23 / S24 sẽ báo <i>Thiếu Khuôn</i>.
</div></div>

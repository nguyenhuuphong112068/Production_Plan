<p>
    Danh sách khuôn dùng khi sắp lịch: <b>khuôn ép vỉ</b> của công đoạn Đóng Gói và <b>khuôn Định Hình</b>. Khuôn ép vỉ được gán cho
    thành phẩm ở <b>Định Mức › Khuôn EV</b>, khuôn Định Hình gán cho bán thành phẩm ở <b>Định Mức › Khuôn Định Hình</b>, sau đó được phân bổ cho lô (nút <i>Phân bổ khuôn</i> trong hộp thoại Sắp lịch tự động).
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/khuon-mau.png', 'caption' => 'Màn hình Dữ Liệu Gốc › Khuôn Mẫu'])
<p>
    Khuôn mẫu <b>tách theo phân xưởng</b>: trang chỉ hiện khuôn của phân xưởng đang chọn, khuôn thêm mới thuộc phân xưởng đó.
    Hai phân xưởng có thể đặt cùng một mã khuôn; khuôn của phân xưởng này không dùng được cho sản phẩm của phân xưởng khác.
</p>

@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/khuon-mau-form.png', 'caption' => 'Hộp thoại thêm mới - Khuôn Mẫu'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Mã Khuôn Mẫu', true, 'Tối đa 50 ký tự, không trùng trong cùng phân xưởng.'],
    ['Số Lượng', false, 'Số bộ khuôn đang có (số nguyên ≥ 0). Một khuôn chỉ chạy được ở tối đa <b>Số Lượng</b> phòng cùng lúc; khi sắp lịch, lô cần khuôn đang bận ở đủ số phòng sẽ phải chờ hoặc sang khuôn khác.'],
    ['Công Đoạn', true, '<b>Đóng Gói</b> hoặc <b>Định Hình</b>.'],
    ['Loại Máy Ép Vỉ / Loại Máy Định Hình', true, 'Chọn một hoặc nhiều loại máy lắp được khuôn. Ô chỉ hiện loại máy cùng công đoạn với khuôn (danh sách ở <a href="#loai-may-ep-vi">Nhóm Máy</a>); đổi Công Đoạn thì các loại máy khác công đoạn bị bỏ chọn. Khuôn chỉ được phân cho phòng có loại máy phù hợp.'],
]])

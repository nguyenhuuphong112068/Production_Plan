<p>
    Màn hình <b>Nhóm Máy</b> (trước đây tên là Loại Máy Ép Vỉ): danh sách <b>loại máy</b> và mã nhóm máy của công đoạn <b>Đóng Gói</b> (máy ép vỉ) và <b>Định Hình</b> (máy dập viên, máy đóng nang).
    Phòng chọn loại máy ở <a href="#phong-san-xuat">Phòng Sản Xuất</a>; khuôn khai các loại máy lắp được ở <a href="#khuon-mau">Khuôn Mẫu</a>.
    Hai thông tin này quyết định khuôn nào chạy được ở phòng nào khi sắp lịch.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/loai-may-ep-vi.png', 'caption' => 'Màn hình Dữ Liệu Gốc › Nhóm Máy'])

<p>Bảng có các cột Công Đoạn, Loại Máy, Mã Nhóm Máy (kèm tên các loại máy cùng nhóm) và <b>Phòng Đang Gán</b> (các phòng đang chọn mã nhóm máy này, mọi phân xưởng).</p>

@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/loai-may-ep-vi-form.png', 'caption' => 'Hộp thoại thêm mới - Loại Máy'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Công Đoạn', true, '<b>Đóng Gói</b> (máy ép vỉ, khớp khuôn ép vỉ) hoặc <b>Định Hình</b> (khớp khuôn Định hình). Loại máy chỉ hiện ở ô chọn của phòng và khuôn cùng công đoạn.'],
    ['Tên Loại Máy', true, 'Không trùng với loại máy khác.'],
    ['Mã Nhóm Máy (Code)', true, 'Số nguyên 1 - 127. Các loại máy <b>lắp lẫn khuôn cho nhau</b> thì đặt chung một mã. Một mã chỉ thuộc một công đoạn: mã đã dùng cho Đóng Gói thì không đặt cho Định Hình và ngược lại.'],
]])

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Không đổi được Mã Nhóm Máy hoặc Công Đoạn của loại máy khi còn phòng đang gán mã đó (và không còn loại máy nào khác cùng mã):
        đổi loại máy của các phòng ở Phòng Sản Xuất trước. Khuôn đã khai mã cũ cũng phải chọn lại loại máy ở Khuôn Mẫu.</div>
</div>

<div class="ug-example"><b>Ví dụ</b> Hai máy đóng nang IMA Zibo ở S23, S24 lắp lẫn khuôn cho nhau: tạo một loại máy
    <i>IMA Zibo</i>, Công Đoạn Định Hình; chọn loại máy này cho phòng S23, S24 và cho các khuôn Định hình của máy IMA.
    Máy MG2 (S25, S36) không lắp được khuôn IMA: nếu cần kiểm soát khuôn thì tạo loại máy riêng với mã khác.</div>

<p class="mt-2">Bấm nút <b>Đang Hoạt Động</b> / <b>Đã Vô Hiệu</b> ở cột Trạng Thái để vô hiệu / kích hoạt lại. Loại máy đã vô hiệu không còn hiện ở ô chọn của phòng và khuôn.</p>

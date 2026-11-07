<p>
    Quy định phòng Trộn Hoàn Tất nào nhận bán thành phẩm từ phòng Pha Chế nào (ví dụ hai phòng nối đường ống). Chỉ liệt kê
    phòng của phân xưởng đang chọn.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/lien-ket-phong.png', 'caption' => 'Màn hình Dữ Liệu Gốc › Liên kết Phòng'])

@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/duLieuGoc/lien-ket-phong-form.png', 'caption' => 'Hộp thoại thêm mới - Liên Kết Phòng'])

@include('pages.userGuide.shared.fields', ['fields' => [
    ['Phòng Nguồn (Pha Chế)', true, 'Chọn phòng Pha chế đang hoạt động.'],
    ['Phòng Đích (Trộn Hoàn Tất)', true, 'Chọn phòng Trộn hoàn tất đi kèm.'],
]])

<h3>Tác dụng trên Lịch Sản Xuất</h3>
<p>
    Khi kéo một lô Trộn hoàn tất sang phòng khác với phòng đích đã liên kết với phòng Pha chế của chính lô đó, Lịch Sản Xuất
    Chart hiện cảnh báo <i>"⚠️ Cảnh Báo Liên Kết Phòng!"</i> và hỏi có tiếp tục không.
</p>
<p>Nút <span class="btn btn-sm btn-danger ug-ui">…</span> / <span class="btn btn-sm btn-success ug-ui">…</span> trên dòng dùng để
    ngưng / bật lại liên kết (có hỏi xác nhận).</p>

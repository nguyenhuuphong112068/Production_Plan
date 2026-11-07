<p>
    Trang đầu là <b>Cổng phân công bảo trì</b>: mỗi thẻ là một tổ (Tổ Bảo Trì, Tổ Điện Lạnh - Nước tinh khiết, Tổ Hiệu chuẩn QA,
    Kỹ Thuật Bảo Trì...). Bấm thẻ để mở lịch công tác của tổ - cách thao tác giống <a href="#phan-cong-san-xuat">Phân công sản xuất</a>.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/lichCongTac/phan-cong-bt-hc.png', 'caption' => 'Màn hình Lịch Công Tác › Bảo trì/Hiệu Chuẩn'])

<h3>Lịch lý thuyết theo tổ</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead><tr><th style="width: 260px">Tổ</th><th>Công việc tải lên</th><th style="width: 220px">Phân xưởng</th></tr></thead>
        <tbody>
            <tr><td>Tổ Bảo Trì 1</td><td>Bảo trì</td><td>PXV1, PXTN</td></tr>
            <tr><td>Tổ Bảo Trì (PXV2-PXDN)</td><td>Bảo trì</td><td>PXV2, PXDN</td></tr>
            <tr><td>Tổ Bảo Trì (PXVH)</td><td>Bảo trì</td><td>PXVH</td></tr>
            <tr><td>Tổ Điện Lạnh - Nước tinh khiết B1</td><td>Tiện ích</td><td>PXV1, PXTN</td></tr>
            <tr><td>Tổ Điện Lạnh - Nước tinh khiết B2</td><td>Tiện ích</td><td>PXV2, PXDN, PXVH</td></tr>
            <tr><td>Tổ Hiệu chuẩn QA</td><td>Hiệu chuẩn</td><td>Tất cả</td></tr>
            <tr><td>Kỹ Thuật Bảo Trì</td><td>Bảo trì + Tiện ích, <b>chỉ xem</b> các lịch đã phân công</td><td>Tất cả</td></tr>
        </tbody>
    </table>
</div>
<p>Tự động phân công BT-HC không xét bậc kỹ năng, chỉ đảm bảo đủ số lượng và không xếp người nghỉ phép.</p>

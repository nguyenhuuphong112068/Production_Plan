<p>
    Theo dõi <b>thời gian biệt trữ</b> của bán thành phẩm giữa các công đoạn: lô đã chờ bao lâu, còn bao nhiêu thời gian trước khi
    quá hạn biệt trữ.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/schedual/tien-trinh.png', 'caption' => 'Màn hình Lịch Sản Xuất › Tiến Trình Sản Xuất'])
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead><tr><th style="width: 230px">Cột</th><th>Ý nghĩa</th></tr></thead>
        <tbody>
            <tr><td>Khoảng biệt trữ / HBT chuẩn</td><td>Giữa hai công đoạn nào và hạn biệt trữ chuẩn (từ danh mục BTP).</td></tr>
            <tr><td>Kết thúc CĐ trước</td><td>Giờ công đoạn trước xong.</td></tr>
            <tr><td>Hạn vào CĐ sau</td><td>Muộn nhất phải bắt đầu công đoạn sau.</td></tr>
            <tr><td>Thực tế vào CĐ sau</td><td>Giờ công đoạn sau đã / sẽ bắt đầu.</td></tr>
            <tr><td>Đã chờ / Còn lại</td><td>Thời gian đã nằm chờ và thời gian còn lại trước hạn.</td></tr>
            <tr><td>HBT tổng, Bắt đầu PC/THT, Hạn kết thúc ĐG, ĐG kết thúc, Ngày còn hạn</td><td>Hạn biệt trữ tổng từ Pha chế tới trước Đóng gói.</td></tr>
        </tbody>
    </table>
</div>
<p class="mt-2"><b>Lọc Quá Hạn Biệt Trữ</b> để chỉ xem lô đã quá hạn; <b>Xem tiến trình sản xuất tất cả công đoạn</b> để xem cả
    dòng chảy của lô. Lô quá hạn biệt trữ trên Gantt có màu cam / nâu; chấp nhận bằng nút ✅ trên Lịch Sản Xuất Chart.</p>

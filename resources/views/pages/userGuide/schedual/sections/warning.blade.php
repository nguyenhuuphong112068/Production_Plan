<p>
    Liệt kê lô theo lịch hiện tại <b>không đáp ứng</b> ngày cần, để đề nghị đổi ngày và trao đổi giữa người lập lịch và kế hoạch.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/schedual/de-nghi-thay-doi.png', 'caption' => 'Màn hình Lịch Sản Xuất › Đề Nghị Thay Đổi KH'])

<h3>Hai loại cảnh báo</h3>
<ul>
    <li><b>Không đáp ứng ngày cần hàng</b>: Đóng gói kết thúc sau (Ngày dự kiến KCS − 5 ngày). Bảng: Bắt đầu / Kết thúc dự kiến,
        Ngày Đáp Ứng Dự Kiến, Ngày KCS Dự Kiến, Số Ngày Trễ Hạn.</li>
    <li><b>Cảnh báo ngày đáp ứng NL/BB</b>: lịch công đoạn không phù hợp các ngày NL/BB của lô; cột <i>Loại Ngày Cảnh Báo</i>
        cho biết ngày nào.</li>
</ul>

<h3>Quy trình</h3>
<ol class="ug-steps">
    <li>Người có quyền <b>đề nghị</b> tick lô, bấm <b>Đề nghị chấp nhận ngày đáp ứng</b> (ngày KCS mới mặc định = ngày đáp ứng + 2
        ngày, hoặc chọn ngày chung) hoặc <b>Đề nghị chấp nhận thay đổi NL/BB</b> (chọn trường cần cập nhật và ngày mới).</li>
    <li>Người có quyền <b>duyệt</b> bấm <b>Chấp nhận</b> / <b>Không chấp nhận</b> từng lô, hoặc <b>Chấp nhận mục đã chọn</b> hàng
        loạt. Chấp nhận thì ngày của lô trong kế hoạch được cập nhật.</li>
    <li>Cột <b>Trao đổi thông tin</b>: nhập nội dung, bấm <b>Gửi</b>. Nút <i class="fas fa-history"></i> xem <b>Lịch Sử Đề Nghị</b>.</li>
</ol>
<p class="text-muted small">Quyền đề nghị và quyền duyệt cấp riêng ở Nhóm Phân Quyền.</p>

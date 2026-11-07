<p>
    Tab <b>Sản phẩm được phép</b> trong Danh Sách Nhân Viên: khai báo mỗi nhân viên được làm những <b>BTP / TP</b> nào. Phòng được
    làm suy ra từ định mức của sản phẩm. Bảng: Mã NV, Tên Nhân Viên, Tổ, Phòng SX, BTP được phép, TP được phép.
</p>

<ol class="ug-steps">
    <li>Bấm <span class="btn btn-xs btn-primary ug-ui"><i class="fas fa-edit"></i></span> <b>Khai báo sản phẩm</b> trên dòng nhân viên
        (nút mờ khi nhân viên chưa được cấp phòng sản xuất nào); tick sản phẩm (<i>Chọn tất cả đang hiển thị</i> / <i>Bỏ chọn đang
        hiển thị</i>, <i>Chỉ hiện đã chọn</i>), lọc theo phòng, rồi <b>Lưu khai báo</b>.</li>
    <li><b>Nhân bản</b> (nút trên dòng): chép khai báo của nhân viên này sang nhân viên khác <b>cùng phòng SX</b>. Chọn
        <i>Cộng thêm</i> (giữ sản phẩm người nhận đang có) hoặc <i>Ghi đè</i> (người nhận giống hệt người nguồn).</li>
</ol>

<p><b>Ai sửa được:</b> nhóm quyền Admin, hoặc tài khoản thuộc đúng bộ phận đó. Người khác chỉ xem
    (<i class="fas fa-eye"></i>). Công đoạn Cân tới Bao phim xét theo BTP, Đóng gói xét theo TP.</p>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Khai báo dùng để <b>cảnh báo</b> khi phân công nhân viên làm sản phẩm chưa được phép (không chặn). Cảnh báo đang
        <b>{{ config('app.employee_product_warning') ? 'bật' : 'tắt' }}</b> theo cấu hình hệ thống.</div>
</div>

@include('pages.userGuide.shared.permissionSection')

<h3>Ai sửa được Lịch Sản Xuất Chart</h3>
<p>
    Mọi tài khoản thấy menu <b>Lịch Sản Xuất</b> đều xem được biểu đồ. Sửa lịch (kéo thả, xoá, lưu, sắp lịch tự động, thả
    lô từ danh sách KHSX, Submit) không dùng quyền tick ở Nhóm Phân Quyền mà xét <b>nhóm quyền chính</b> và phân xưởng:
</p>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr><th style="width: 260px">Tài khoản</th><th>Được làm gì trên Lịch Sản Xuất Chart</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Nhóm quyền chính <b>Admin</b> hoặc <b>Schedualer</b></td>
                <td>Sửa lịch và sắp lịch tự động, <b>chỉ khi phân xưởng đang chọn trùng phòng ban của tài khoản</b>.
                    Chuyển sang phân xưởng khác (menu chọn phân xưởng PXV1, PXV2...) thì chỉ xem.</td>
            </tr>
            <tr>
                <td>Tài khoản thuộc phòng ban <b>BOD</b></td>
                <td>Sửa lịch ở mọi phân xưởng.</td>
            </tr>
            <tr>
                <td>Các tài khoản khác</td>
                <td>Chỉ xem. Các nút sửa lịch (<span class="ug-emoji">🤖 🗑️ 💾 🚫</span>...) bấm vào không có tác dụng.</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="ug-callout is-warn">
    <i class="fas fa-lock"></i>
    <div>
        <b>Một phân xưởng chỉ một người sắp lịch tự động tại một thời điểm.</b> Nếu người khác đang chạy, phần mềm báo
        <i>Đang sắp lịch</i> và không chạy lần thứ hai. Khi sắp lịch thủ công, phần mềm còn tự bật <b>Khóa TTSX</b>
        (khoá Nhận / Trả phòng của trang Thực Thi Sản Xuất; hiện áp dụng cho PXV1) - xem <a href="#chinh-lich-tay">Chỉnh lịch trên Gantt</a>.
    </div>
</div>

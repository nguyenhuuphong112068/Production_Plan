<p>
    Các nhóm quyền và phòng ban dưới đây được phần mềm kiểm tra <b>theo tên</b>, ngoài các quyền tick ở Nhóm Phân Quyền.
    Chỉ <b>nhóm quyền chính</b> được xét: trong các nhóm gán cho tài khoản, nhóm đứng trên cùng ở danh sách Nhóm Phân Quyền.
</p>

<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead><tr><th style="width: 260px">Nhóm quyền / phòng ban</th><th>Phần mềm dùng để làm gì</th></tr></thead>
        <tbody>
            <tr><td>Admin</td><td style="font-weight: 400">Sửa Lịch Sản Xuất Chart (cùng phân xưởng), Khóa TTSX; tạo / gửi kế hoạch HC-BT và sửa danh mục BT-HC-TI mọi loại; sửa danh sách nhân viên mọi phòng; phân công BT-HC mọi tổ; nút Xong trên Lịch HC-BT; thấy lịch chưa Submit, cột Lý do ở Lịch Sử Thay Đổi Lịch, mã dòng (ID) trên các bảng.</td></tr>
            <tr><td>Schedualer</td><td style="font-weight: 400">Sửa Lịch Sản Xuất Chart, sắp lịch tự động, Submit, Khóa TTSX (cùng phân xưởng); thấy lịch chưa Submit; xem cột Lý do ở Lịch Sử Thay Đổi Lịch; nhận thông báo đề nghị thay đổi KH.</td></tr>
            <tr><td>Leader</td><td style="font-weight: 400">Thấy lịch chưa Submit; xác nhận lịch ở Lịch Sản Xuất Tuần (PXV1); ở Lịch HC-BT của PXV1 chỉ thấy phòng thuộc tổ mình.</td></tr>
            <tr><td>Executor</td><td style="font-weight: 400">Chỉ vào được trang <b>Ghi Nhận Sản Xuất</b>; mọi màn hình khác tự chuyển về trang này.</td></tr>
            <tr><td>Engineer Manager, Calibration and Maintenance Planning Manager, Calibration and Maintenance Scheduler</td><td style="font-weight: 400">Phạm vi tổ được phân công ở Lịch Công Tác › Bảo trì / Hiệu Chuẩn (xem mục Phân Quyền của tài liệu Lịch Công Tác).</td></tr>
            <tr><td>Phòng ban BOD</td><td style="font-weight: 400">Sửa Lịch Sản Xuất Chart ở mọi phân xưởng; sửa danh mục BT-HC-TI.</td></tr>
            <tr><td>Phòng ban COMP</td><td style="font-weight: 400">Xem cột Lý do ở Lịch Sử Thay Đổi Lịch.</td></tr>
            <tr><td>Phòng ban QA / EN</td><td style="font-weight: 400">QA: tạo / gửi kế hoạch và sửa danh mục <b>Hiệu chuẩn</b>. EN: <b>Bảo trì</b> và <b>Tiện ích</b>.</td></tr>
        </tbody>
    </table>
</div>

<div class="ug-callout is-danger">
    <i class="fas fa-exclamation-circle"></i>
    <div>
        <b>Không đổi tên</b> các nhóm quyền trên ở Nhóm Phân Quyền: đổi tên thì các chức năng kiểm tra theo tên ngừng nhận ra
        nhóm, người dùng mất quyền tương ứng.
    </div>
</div>

<div class="ug-callout is-warn">
    <i class="fas fa-layer-group"></i>
    <div>
        <b>Gán nhiều nhóm quyền cho một tài khoản:</b> nhóm quyền chính là nhóm đứng trên cùng trong danh sách Nhóm Phân Quyền
        (Admin, Planner, Viewer, Schedualer...). Ví dụ gán <i>Viewer</i> + <i>Schedualer</i> thì nhóm chính là <i>Viewer</i>:
        tài khoản có đủ quyền tick của cả hai nhóm nhưng <b>không sửa được Lịch Sản Xuất Chart</b>. Người cần quyền của một
        nhóm đặc biệt thì không gán kèm nhóm nào đứng trên nhóm đó; quyền lẻ còn thiếu thì cấp bằng <b>quyền riêng</b>.
        Nhóm chính đang hiện ở góc phải thanh trên cùng (🛡️) khi người đó đăng nhập.
    </div>
</div>

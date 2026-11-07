<p>
    Quản lý nhân sự theo bộ phận: tổ, phòng được phép công tác, bậc kỹ năng. Dữ liệu này dùng khi phân công ở nhóm Lịch Công Tác.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/dinhMuc/danh-sach-nhan-vien.png', 'caption' => 'Màn hình Định Mức › Danh Sách Nhân Viên'])

<h3>Màn hình chọn bộ phận</h3>
<p>
    Mỗi thẻ là một bộ phận, kèm số liệu ca trực hôm nay lấy từ eO2: <b>Tổng nhân sự, Ca 1-4, Hành chính, Nghỉ phép</b>. Thẻ báo
    "Chưa nạp được lịch trực..." khi chưa lấy được số liệu (số liệu nạp nền tự động hoặc khi bấm Đồng bộ ở Lịch Công Tác /
    Dashboard). Bấm thẻ để mở danh sách nhân sự của bộ phận.
</p>

<h3>Tab Tổ &amp; Phòng công tác</h3>
<ul>
    <li><b>Lọc theo Tổ</b>, <b>Lọc theo Phòng</b> ở đầu bảng; nút <i class="fas fa-sync-alt"></i> tải lại dữ liệu.</li>
    <li>Mỗi nhân viên có các tổ công tác (luôn phải còn <b>ít nhất 1 tổ hoạt động</b>) và danh sách <b>phòng được phép công tác</b>
        của từng tổ. <b>Thêm phòng</b> để gán thêm (mỗi phòng chỉ gán một lần cho một người), biểu tượng thùng rác để xoá,
        kéo thả để sắp <b>độ ưu tiên</b> phòng.</li>
    <li><b>Bậc Kỹ Năng</b> 1-4 cho từng phòng; cột giờ cho biết <b>Giờ Năm Nay</b> và <b>Tổng Giờ</b> đã làm tại phòng.</li>
    <li>Tick <b>Đang nghỉ thai sản</b> / <b>Đang nghỉ phép dài hạn</b> để loại nhân viên khỏi phân công.</li>
    <li><b>Hiển thị Dashboard</b>: thống kê nhân sự <i>Theo Tổ/Vị Trí, Theo Phòng, Tổng Hợp Chi Tiết</i> theo Ngày / Tuần / Tháng.</li>
    <li><b>Xuất Excel</b>: "Bảng định mức kỹ năng nhân viên" theo bộ lọc đang chọn.</li>
</ul>

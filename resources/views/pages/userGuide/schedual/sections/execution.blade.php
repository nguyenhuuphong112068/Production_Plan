<p>
    Bảng trạng thái các phòng của phân xưởng: <b>Sẵn Sàng</b> hoặc <b>Phòng Bận</b>. Tổ sản xuất bấm <b>Nhận phòng</b> khi bắt đầu
    một lô / lịch và <b>Trả phòng</b> khi xong (kể cả vệ sinh). Mốc thời gian là <b>giờ hệ thống lúc bấm nút</b>, không sửa tay.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/schedual/thuc-thi-san-xuat.png', 'caption' => 'Màn hình Lịch Sản Xuất › Thực Thi Sản Xuất'])

<h3>Thẻ phòng</h3>
<ul>
    <li>Ô tìm phòng / sản phẩm / số lô ở đầu trang; nút <i class="fas fa-sync-alt"></i> tải lại trạng thái các phòng.</li>
    <li>Phòng bận hiện lô đang chạy, thời gian <i>tính từ lúc nhận phòng</i>, nhãn <i>Lô thẩm định</i> nếu có.</li>
    <li>Thẻ hiện người được phân công tại phòng lúc này theo <b>Lịch Công Tác › Sản Xuất</b>.</li>
    <li>Nút <i class="fas fa-history"></i> <b>Lịch sử nhận trả phòng</b>: các lần Nhận phòng, Kết thúc BT / Nhận VS, Trả phòng, thời gian giữ phòng.</li>
</ul>

<h3>Nhận phòng</h3>
<ol class="ug-steps">
    <li>Bấm <b>Nhận phòng</b> trên thẻ phòng Sẵn Sàng.</li>
    <li>Chọn chế độ <b>Nhận phòng sản xuất</b> (lô sản xuất) hoặc <b>Nhận phòng BT</b> (lịch Bảo trì / Tiện ích / Hiệu chuẩn đã sắp
        vào phòng). Lọc <i>Tất cả công đoạn</i> hoặc từng công đoạn; ô tìm sản phẩm, số lô, thiết bị, mã.</li>
    <li>Chọn lô / lịch rồi bấm <b>Nhận phòng</b>. Phòng chuyển sang Bận. Nhận phòng trễ so với lịch thì các lô sau được đẩy trễ
        theo (xem <a href="#tinh-tuyen-lich">Tịnh tuyến lịch</a>).</li>
</ol>

<h3>Trả phòng</h3>
<ul>
    <li>Lô sản xuất: bấm <b>Trả phòng</b> khi xong sản xuất và vệ sinh; phòng về Sẵn Sàng.</li>
    <li>Lịch BT / TI có vệ sinh: Trả phòng kết thúc bảo trì → phòng ở trạng thái chờ vệ sinh → bấm <b>Nhận phòng vệ sinh sau BT</b>
        → vệ sinh xong bấm Trả phòng. Lịch hiệu chuẩn không có vệ sinh: Trả phòng là xong.</li>
    <li>Bấm nhầm: nút <b>Hoàn tác</b> trên thẻ, chỉ trong <b>2 phút</b> sau thao tác.</li>
</ul>

<div class="ug-callout is-warn">
    <i class="fas fa-user-lock"></i>
    <div>
        <ul class="mb-0">
            <li>Nhân viên phân xưởng chỉ thao tác được trên phòng của phân xưởng mình; phân xưởng khác chỉ xem. EN / QA chỉ xem.
                Mỗi nút Nhận phòng / Nhận phòng vệ sinh / Trả phòng cần quyền riêng.</li>
            <li>Khi người sắp lịch đang bật <b>Khóa TTSX</b> (hoặc phân xưởng đang sắp lịch tự động), Nhận / Trả phòng bị chặn tạm thời.</li>
            <li>Tài khoản nhóm <b>Executor</b> dùng trang <b>Ghi Nhận Sản Xuất</b>: cùng cách thao tác nhưng chỉ thấy các phòng
                mình đang được phân công lúc này; hết ca thì phòng tự biến mất.</li>
        </ul>
    </div>
</div>
<p class="text-muted small">Sản lượng thực tế, số thùng xác nhận ở <a href="#xac-nhan-hoan-thanh">Xác Nhận Hoàn Thành</a>.
    Màn hình xem nhanh <i>Trạng Thái · Sản Xuất</i> trên trang đăng nhập hiển thị đúng bảng này, không cần đăng nhập.</p>

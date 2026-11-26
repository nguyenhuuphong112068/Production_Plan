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
        vào phòng). Lọc <i>Tất cả công đoạn</i> hoặc từng công đoạn; ô tìm sản phẩm, số lô, thiết bị, mã.
        Lô mà công đoạn kế tiếp đã xác nhận hoàn thành không hiện trong danh sách. Ở phòng cân, sau tên sản phẩm có nhãn loại
        nguyên liệu: <b>🧪 NL PC</b> / <b>🧪 NL THT</b>, <b>💊 Nang rỗng</b>, <b>🖌️ NL BP</b>, <b>📦 NL ĐG</b> (cùng nhãn trên
        Gantt, xem <a href="#sap-lich-can">Sắp lịch cân bán tự động</a>).</li>
    <li>Chọn lô / lịch rồi bấm <b>Nhận phòng</b>. Phòng chuyển sang Bận. Nhận phòng trễ so với lịch thì các lô sau được đẩy trễ
        theo (xem <a href="#tinh-tuyen-lich">Tịnh tuyến lịch</a>).</li>
</ol>

<h3>Trả phòng</h3>
<ul>
    <li>Lô sản xuất: bấm <b>Trả phòng</b> khi xong sản xuất và vệ sinh; phòng về Sẵn Sàng.</li>
    <li>Lịch BT / TI có vệ sinh: Trả phòng kết thúc bảo trì → phòng ở trạng thái chờ vệ sinh → bấm <b>Nhận phòng vệ sinh sau BT</b>
        → vệ sinh xong bấm Trả phòng. Phòng chỉ giữ lịch hiệu chuẩn (HC): hộp Trả phòng hỏi
        <b>Có vệ sinh phòng sau Hiệu chuẩn không?</b> — chọn <b>Không</b> thì xong, phòng về Sẵn Sàng; chọn <b>Có</b> thì phòng
        chuyển sang chờ vệ sinh và làm tiếp như lịch BT.</li>
    <li>Bấm nhầm: nút <b>Hoàn tác</b> trên thẻ, chỉ trong <b>2 phút</b> sau thao tác.</li>
    <li>Nhận nhầm lô: <b>Leader trở lên</b> bấm <b>Đổi sản phẩm</b> trên thẻ phòng đang bận, chọn lô đúng. Lô mới giữ nguyên giờ
        nhận phòng cũ, lô cũ trở về chưa nhận phòng. Không đổi được khi phòng giữ lịch bảo trì hoặc lô đã được xác nhận
        ở trang Xác nhận hoàn thành.</li>
    <li>Phòng đôi làm chiến dịch gói đầu (S3, S7+S8, S10+S11 — đặt ở Dữ Liệu Gốc → Phòng Sản Xuất, ô <b>Số lô giữ phòng cùng lúc</b>):
        phòng đang Bận với 1 lô có thêm nút <b>Nhận lô gói đầu</b>, chỉ chọn được <b>1 lô cùng mã BTP</b>, tối đa <b>2 lô</b> cùng lúc.
        Khi giữ 2 lô, <b>Trả phòng</b> hỏi trả lô nào (mặc định lô nhận trước), phòng vẫn Bận với lô còn lại; trả lô cuối thì về
        Sẵn Sàng. Phòng giữ 2 lô thì không Đổi sản phẩm được — dùng Hoàn tác hoặc Trả phòng lô nhầm.</li>
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
<h3>Cảnh báo khi Nhận / Trả phòng bị chặn</h3>
<p>
    Nhận phòng, Trả phòng và Hoàn tác có thể <b>dời lịch</b> các lô sau (tịnh tuyến lịch). Để lịch không bị ghi đè lẫn nhau,
    phần mềm chặn thao tác trong các trường hợp dưới đây. Khi gặp các cảnh báo này, thao tác <b>chưa được lưu</b>: chờ rồi bấm lại.
</p>

<h4>1. Người sắp lịch đang khóa (Khóa TTSX)</h4>
<p>
    Người sắp lịch bật <b>Khóa TTSX</b> trên Gantt (hoặc phần mềm tự khóa khi họ sửa lịch thủ công mà quên khóa). Đầu trang
    Thực Thi hiện dải vàng, và bấm Nhận / Trả phòng sẽ báo lỗi. Khóa tự mở khi người sắp lịch mở khóa hoặc không sắp lịch thêm
    10 phút. Người sắp lịch cân (nhóm quyền <b>Weighing Scheduler</b>) khóa thì <b>chỉ các phòng cân</b> (Cân NL, Cân NL khác)
    bị chặn; các phòng khác vẫn Nhận / Trả phòng bình thường. Khóa này <b>không hiện dải vàng</b> đầu trang, chỉ báo khi bấm
    Nhận / Trả phòng ở phòng cân.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/schedual/canh-bao-banner-khoa.png', 'caption' => 'Dải cảnh báo đầu trang khi phân xưởng đang bị khóa'])
<div class="ug-shot-row">
    @include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/schedual/canh-bao-khoa-ttsx.png', 'caption' => 'Người sắp lịch đang khóa TTSX'])
    @include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/schedual/canh-bao-sap-lich-tu-dong.png', 'caption' => 'Phân xưởng đang chạy sắp lịch tự động'])
</div>

<h4>2. Nhiều người Nhận / Trả phòng cùng lúc</h4>
<ul>
    <li>Hai phòng cùng phân xưởng bấm cùng lúc và cùng phải dời lịch: các thao tác chạy <b>lần lượt</b>, người sau chờ người
        trước dời lịch xong. Thường chỉ chờ vài giây.</li>
    <li>Chờ quá <b>60 giây</b>: thông báo <i>"đang có thao tác khác tịnh tuyến lịch… CHƯA được lưu"</i>, bấm lại.</li>
    <li>Hai người cùng bấm trên <b>một phòng</b>: người bấm sau nhận thông báo phòng đã đổi trạng thái; thẻ phòng tự tải lại,
        kiểm tra rồi thao tác tiếp nếu cần.</li>
</ul>
<div class="ug-shot-row">
    @include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/schedual/canh-bao-dang-tinh-tuyen.png', 'caption' => 'Phòng khác đang dời lịch quá 60 giây'])
    @include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/schedual/canh-bao-nguoi-khac-vua-thao-tac.png', 'caption' => 'Người khác vừa thao tác trên cùng phòng'])
</div>

<p class="text-muted small">Sản lượng thực tế, số thùng xác nhận ở <a href="#xac-nhan-hoan-thanh">Xác Nhận Hoàn Thành</a>.
    Màn hình xem nhanh <i>Trạng Thái · Sản Xuất</i> trên trang đăng nhập hiển thị đúng bảng này, không cần đăng nhập.</p>

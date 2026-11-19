<p>
    Mỗi lô sản xuất là một dòng. Người dùng nhập các mốc hồ sơ; phần mềm tính <b>Ngày Đủ Điều Kiện</b>, <b>Số Ngày HT</b> và chấm
    <b>Đáp Ứng / Không Đáp Ứng</b>, kèm bảng tỉ lệ lô KCS đúng hạn.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/keHoach/theo-doi-ho-so-kcs.png', 'caption' => 'Màn hình Kế Hoạch › Theo Dõi Hồ Sơ KCS'])

<h3>Lọc</h3>
<p><b>Phân Xưởng</b>, <b>Từ / Đến tháng kế hoạch</b>, <b>Tháng KCS</b> (theo ngày KCS thật từ MMS, không phải tháng kế hoạch),
    <b>Ngày Dự Kiến KCS</b> (từ ngày - đến ngày, có thể bỏ trống một đầu),
    <b>Kết Quả</b>, ô <b>Tìm kiếm</b> (mã / tên SP / số lô), bấm <b>Lọc</b>. <b>Xuất Excel</b> đúng dữ liệu đang lọc.</p>
<p>Khi lọc theo <b>Tháng KCS</b> hoặc <b>Ngày Dự Kiến KCS</b>, khoảng tháng kế hoạch không áp dụng (ô mờ đi).</p>
<p><b>Sắp xếp</b>: bấm tiêu đề cột để sắp tăng dần, bấm lần nữa để giảm dần; ô trống luôn nằm cuối.
    Giữ <b>Shift</b> + bấm tiêu đề cột khác để <b>sắp theo nhiều cột</b> (số nhỏ cạnh mũi tên là thứ tự ưu tiên;
    Shift + bấm lại cột đã chọn để đảo chiều cột đó). Bấm cột <b>STT</b> để về lại thứ tự ban đầu.
    Sắp xếp chỉ trên màn hình, bấm <b>Lọc</b> lại thì về thứ tự mặc định.</p>
<p><b>Ô ngày</b> hiển thị và nhập theo dạng <b>dd/mm/yyyy</b> (không phụ thuộc cài đặt ngày của Windows):
    gõ tay (VD 05/11/2026) hoặc bấm vào ô để chọn trên lịch. Gõ sai định dạng hoặc ngày không có thật
    (VD 31/02/2026) thì ô bị tô đỏ và dòng không được lưu.</p>

<h3>Các cột nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Ngày Nhận Hồ Sơ', false, 'Mốc bắt đầu tính số ngày hoàn thành.'],
    ['Người Đọc, Ngày Đọc Xong HS', false, ''],
    ['Ngày Nhận COATP', false, 'Ngày KCS nhận được COATP. <b>Số Phiếu COATP</b> lấy từ MMS, không nhập tay.'],
    ['DR/IR, Ngày AP DR/IR', false, ''],
    ['OOS, Ngày AP OOS', false, ''],
    ['Ngày AP OPV/PVR', false, ''],
    ['Ngày Chờ KCS Theo Đúng Thứ Tự Lô', false, 'Ngày lô vào hàng chờ KCS đúng thứ tự lô; cũng là một mốc xét Ngày Đủ Điều Kiện.'],
    ['Ghi Chú', false, ''],
]])

<h3>Cột phần mềm tính</h3>
<ul>
    <li><b>Ngày Dự Kiến KCS</b> (sau cột Lô TĐ): lấy từ Ngày dự kiến KCS của lô trên Kế Hoạch Sản Xuất, chỉ xem, không sửa ở đây.</li>
    <li><b>Ngày Nhập Kho</b> (trước cột Ngày KCS): lấy từ MMS - ngày kho tạo phiếu nhập kho thành phẩm, tra theo Mã TP + Số lô
        thực tế; có cả lô đã nhập kho nhưng chưa KCS. Lô nhập kho nhiều đợt lấy phiếu đầu tiên; lô chia thì mỗi phần lấy phiếu
        có số lượng gần nhất (cùng quy tắc với Ngày KCS). Rê chuột vào ô để xem số phiếu và số lượng nhập kho. Chỉ xem, không sửa.</li>
    <li><b>Ngày KCS</b>: lấy từ MMS (bước QA Approval), tra theo Mã TP + Số lô thực tế. Lô chia (có phần "chỉ đóng gói")
        thì mỗi phần nhận ngày KCS của phiếu nhập kho có số lượng gần nhất; phần chưa có phiếu nhập kho được KCS duyệt thì để trống.</li>
    <li><b>Ngày Đủ Điều Kiện</b> và <b>Mốc Quyết Định</b>: mốc hoàn tất muộn nhất của lô - khâu giữ hồ sơ lâu nhất.</li>
    <li><b>Số Ngày HT</b>: số ngày làm việc từ Ngày Nhận Hồ Sơ tới Ngày KCS, đã loại Chủ nhật và ngày nghỉ công ty (tự lấy từ eO2).</li>
    <li><b>Kết Quả</b>: Đáp Ứng / Không Đáp Ứng. Chỉ lô đã có Ngày Đủ Điều Kiện mới chấm được.</li>
</ul>
<p>Bảng tổng kết: <i>Tỉ Lệ Lô KCS Đúng Hạn Theo Tháng</i>, theo quý, cả năm, và <i>Nguyên Nhân Lô KCS Trễ</i> theo mốc quyết định.
    Biểu tượng <i class="fas fa-history"></i> ở cột LS xem lịch sử chỉnh sửa của lô.</p>

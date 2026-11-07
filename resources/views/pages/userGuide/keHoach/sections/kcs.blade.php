<p>
    Mỗi lô sản xuất là một dòng. Người dùng nhập các mốc hồ sơ; phần mềm tính <b>Ngày Đủ Điều Kiện</b>, <b>Số Ngày HT</b> và chấm
    <b>Đáp Ứng / Không Đáp Ứng</b>, kèm bảng tỉ lệ lô KCS đúng hạn.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/keHoach/theo-doi-ho-so-kcs.png', 'caption' => 'Màn hình Kế Hoạch › Theo Dõi Hồ Sơ KCS'])

<h3>Lọc</h3>
<p><b>Phân Xưởng</b>, <b>Từ / Đến tháng kế hoạch</b>, <b>Tháng KCS</b> (theo ngày KCS thật từ MMS, không phải tháng kế hoạch),
    <b>Kết Quả</b>, ô <b>Tìm kiếm</b> (mã / tên SP / số lô), bấm <b>Lọc</b>. <b>Xuất Excel</b> đúng dữ liệu đang lọc.</p>

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
    <li><b>Ngày KCS</b>: lấy từ MMS (bước QA Approval).</li>
    <li><b>Ngày Đủ Điều Kiện</b> và <b>Mốc Quyết Định</b>: mốc hoàn tất muộn nhất của lô - khâu giữ hồ sơ lâu nhất.</li>
    <li><b>Số Ngày HT</b>: số ngày làm việc từ Ngày Nhận Hồ Sơ tới Ngày KCS, đã loại Chủ nhật và ngày nghỉ công ty (tự lấy từ eO2).</li>
    <li><b>Kết Quả</b>: Đáp Ứng / Không Đáp Ứng. Chỉ lô đã có Ngày Đủ Điều Kiện mới chấm được.</li>
</ul>
<p>Bảng tổng kết: <i>Tỉ Lệ Lô KCS Đúng Hạn Theo Tháng</i>, theo quý, cả năm, và <i>Nguyên Nhân Lô KCS Trễ</i> theo mốc quyết định.
    Biểu tượng <i class="fas fa-history"></i> ở cột LS xem lịch sử chỉnh sửa của lô.</p>

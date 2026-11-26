<p>
    Danh sách các lô sản xuất có <b>thị trường Châu Âu</b> (thị trường được đánh dấu <i>Châu Âu</i> trong Dữ Liệu Gốc › Thị Trường),
    kèm <b>Ngày Sản Xuất</b>, <b>Hạn Dùng</b>, <b>Ngày KCS</b>, <b>Số CoA</b>, ngày / số lượng <b>nhập kho</b>, ngày / số lượng
    <b>xuất kho</b> và <b>nguyên liệu / bao bì đã dùng</b> cho lô (tất cả lấy từ MMS), để theo dõi xuất kho và lập phiếu COC.
    Ô nhập duy nhất là <b>Ngày Lập Phiếu COC</b>.
</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/keHoach/theo-doi-xuat-kho-eu.png', 'caption' => 'Màn hình Kế Hoạch › Theo Dõi Xuất Kho EU'])

<h3>Lọc</h3>
<p><b>Phân Xưởng</b>, <b>Từ / Đến tháng kế hoạch</b> (mặc định tháng hiện tại), <b>Ngày xuất kho</b> (từ ngày - đến ngày, dạng
    <b>dd/mm/yyyy</b>, gõ tay hoặc bấm vào ô để chọn trên lịch, có thể bỏ trống một đầu), ô <b>Tìm kiếm</b> (mã TP / mã BTP /
    tên sản phẩm / số lô / thị trường), bấm <b>Lọc</b>. <b>Đặt lại</b> để về tháng kế hoạch hiện tại.</p>
<p>Lọc theo <b>Ngày xuất kho</b> giữ các lô có <b>ít nhất một phiếu xuất kho</b> trong khoảng đã chọn. Khi đó khoảng tháng kế hoạch
    không áp dụng (ô mờ đi, có nhãn <i>không áp dụng</i>): lô kế hoạch tháng khác vẫn hiện nếu được xuất kho trong khoảng này
    (phần mềm xét các lô kế hoạch trong 12 tháng trước ngày bắt đầu lọc). Chỉ nhập <i>từ ngày</i> thì lọc tới hôm nay.
    Gõ sai định dạng hoặc ngày không có thật thì ô bị tô đỏ và không lọc.</p>
<p><b>Sắp xếp</b>: bấm tiêu đề cột để sắp tăng dần, bấm lần nữa để giảm dần; ô trống luôn nằm cuối. Giữ <b>Shift</b> + bấm tiêu đề
    cột khác để sắp theo nhiều cột. Bấm cột <b>STT</b> để về lại thứ tự ban đầu.</p>
<p><b>Xuất Excel</b> (nút xanh lá cạnh Đặt lại): tải file <i>.xlsx</i> đúng danh sách lô theo bộ lọc <b>đã bấm Lọc</b> (đổi ô lọc mà
    chưa bấm Lọc thì file vẫn theo bộ lọc cũ), dòng 2 của file ghi lại bộ lọc đã dùng. File có đủ các cột của lưới, thêm cột
    <b>ĐVT</b>, <b>Phiếu Xuất Kho</b> (từng phiếu: số phiếu - ngày - số lượng), <b>Người Khai Báo</b> và <b>Thời Điểm Khai Báo</b>
    Ngày Lập Phiếu COC. Số lượng nhập / xuất kho là số để cộng / lọc được trong Excel. Thứ tự dòng là thứ tự gốc của trang (không
    theo sắp xếp tạm khi bấm tiêu đề cột).</p>

<h3>Các cột</h3>
<ul>
    <li><b>Số Lệnh, Số Lô, Tên Sản Phẩm, Mã BTP, Mã TP, Thị Trường, Quy Cách, Cỡ Lô</b>: giống trang
        <a href="#theo-doi-ho-so-kcs">Theo Dõi Hồ Sơ KCS</a>. Nhãn version (v7...) cạnh Mã BTP / Mã TP là version BOM đã được chốt
        lúc lô có số lệnh (chốt khi mở trang Theo Dõi Hồ Sơ KCS).</li>
    <li><b>NL / BB</b>: hai nút mở hộp thoại <i>Nguyên Liệu &amp; Bao Bì Sử Dụng</i> của lô (xem bên dưới).</li>
    <li><b>Ngày Sản Xuất</b> và <b>Hạn Dùng</b> (nền xanh, lấy từ MMS): ghi trên phiếu nhập kho thành phẩm đầu tiên của lô.</li>
    <li><b>Ngày KCS</b> và <b>Số CoA</b> (nền xanh, lấy từ MMS - bước QA Approval): cùng giá trị với cột Ngày KCS / Số Phiếu COATP
        của trang Theo Dõi Hồ Sơ KCS (lô chia thì mỗi phần lấy phiếu có số lượng gần nhất). MMS chưa có thì hiện giá trị đã lưu ở
        trang KCS (nếu có).</li>
    <li><b>Ngày Nhập Kho</b> (nền xanh, lấy từ MMS): ngày kho tạo phiếu nhập kho thành phẩm đầu tiên của lô. Lô nhập nhiều đợt có
        nhãn <i>n phiếu</i>; rê chuột vào ô để xem từng phiếu. <b>Số Lượng Nhập Kho</b>: tổng số lượng các phiếu nhập kho của lô
        (lô chia chỉ tính phiếu ứng với phần lô đó).</li>
    <li><b>Ngày Xuất Kho</b> (nền xanh, lấy từ MMS): ngày của phiếu xuất kho thành phẩm <b>đã duyệt</b> đầu tiên của lô, tra theo
        Mã TP + Số lô thực tế qua các phiếu nhập kho của lô. Lô xuất nhiều đợt có nhãn <i>n phiếu</i>; rê chuột vào ô để xem từng
        phiếu (số phiếu, ngày, số lượng, ghi chú của kho). Lô chia (có phần "chỉ đóng gói") chỉ tính phiếu xuất của phiếu nhập kho
        ứng với phần lô đó.</li>
    <li><b>Số Lượng Xuất Kho</b> (nền xanh, lấy từ MMS): tổng số lượng các phiếu xuất kho đã duyệt của lô. Số lượng nhập / xuất
        kèm đơn vị trên phiếu nhập kho (VD viên).</li>
    <li><b>Ngày Lập Phiếu COC</b> (tiêu đề vàng, người dùng nhập): gõ <b>dd/mm/yyyy</b> hoặc bấm vào ô để chọn trên lịch, sửa xong
        ô <b>tự lưu</b> (thông báo <i>Đã lưu lô ...</i>); xoá trắng ô để bỏ ngày. Gõ sai định dạng / ngày không có thật thì ô bị tô
        đỏ và không lưu. Ô chỉ mở khi:
        <ul>
            <li>lô <b>đã có Ngày Xuất Kho và Số Lượng Xuất Kho</b> (lô chưa xuất kho thì ô bị khoá, phần mềm cũng từ chối lưu); và</li>
            <li>tài khoản có quyền <b>Nhập Ngày Lập Phiếu COC</b>.</li>
        </ul>
        Rê chuột vào ô bị khoá để xem lý do. Dòng chữ nhỏ dưới ô là <b>người khai báo</b> và <b>thời điểm khai báo</b> lần cuối.
        Biểu tượng <i class="fas fa-history"></i> cạnh ô mở <b>Lịch Sử Khai Báo</b>: mỗi lần đổi ngày là một dòng gồm thời điểm
        khai báo, người khai báo, ngày cũ và ngày mới (xoá ngày ghi là <i>(xoá)</i>).
        Lô <b>đã có Ngày Lập Phiếu COC</b> được <b>tô nền xanh nhạt cả dòng</b> (đổi màu ngay khi lưu, xoá ngày thì hết tô); file
        Excel tô cùng màu.</li>
</ul>
<p>Ô trống nghĩa là lô chưa nhập kho hoặc chưa có phiếu xuất kho được duyệt trên MMS. Không kết nối được MMS thì trang báo
    <i>Chưa kết nối được MMS</i> và các cột nền xanh để trống (riêng Ngày KCS / Số CoA đã lưu ở trang KCS vẫn hiện).</p>

<h3>Nguyên liệu &amp; bao bì sử dụng</h3>
<p>Bấm <b>NL</b> (nguyên liệu) hoặc <b>BB</b> (bao bì) trên dòng lô: hộp thoại mở đúng tab tương ứng, chuyển qua lại giữa hai tab
    <b>Nguyên Liệu</b> / <b>Bao Bì</b> (số cạnh tên tab là số dòng). Dữ liệu là các phiếu xuất kho NL/BB cho sản xuất trên MMS
    (loại <i>Against Prd Order</i>):</p>
<ul>
    <li><b>Nguyên Liệu</b>: phiếu xuất theo <b>Mã BTP</b> + số lô (công đoạn Formulation, Coating...).</li>
    <li><b>Bao Bì</b>: phiếu xuất theo <b>Mã TP</b> + số lô (Primary / Secondary packaging).</li>
</ul>
<p>Mỗi dòng là một lô NL/BB của một công đoạn: <b>Công Đoạn</b>, <b>Mã</b>, <b>Tên</b>, <b>Số Lô</b> (số lô kiểm soát nội bộ),
    <b>Số Lô NSX</b> (số lô của nhà sản xuất), <b>Số COA</b>, <b>Số Lượng</b> (đã cộng dồn các bao / lần cân) và <b>ĐVT</b>,
    <b>Ngày Xuất</b> (lần xuất đầu tiên), <b>Phiếu Xuất</b>. Nhiều lô sản phẩm cùng mã BTP và số lô (VD đóng gói cho nhiều thị
    trường) thì dùng chung danh sách nguyên liệu.</p>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/keHoach/theo-doi-xuat-kho-eu-nlbb.png', 'caption' => 'Hộp thoại Nguyên Liệu & Bao Bì Sử Dụng (tab Nguyên Liệu)'])

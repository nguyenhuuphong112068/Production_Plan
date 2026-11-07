<p>
    Chạy xong, biểu đồ tự tải lại. Tuỳ kết quả, phần mềm hiện một trong các thông báo sau:
</p>

<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr><th style="width: 260px">Thông báo</th><th>Ý nghĩa và việc cần làm</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Hoàn Thành Sắp Lịch <small class="text-muted">(tự đóng)</small></td>
                <td>Xếp xong, không lô nào vi phạm Ngày HH NL chính / HH BB, không lịch BT-HC-TI nào phải dời.</td>
            </tr>
            <tr>
                <td>Còn lô vi phạm hạn NL/BB</td>
                <td>Danh sách lô vẫn bắt đầu sau hạn sau khi đã sắp lại: số lô, hạn, ngày bắt đầu PC / ĐG. Dòng ghi
                    <i>"không thể đáp ứng"</i> là hạn sớm hơn ngày sớm nhất lô được chạy - cần xem lại ngày NL/BB hoặc kế
                    hoạch. Các lô còn lại có thể chỉnh tay (đổi phòng, đẩy lô khác ra sau).</td>
            </tr>
            <tr>
                <td>Lịch BT-HC-TI bị chiến dịch xếp đè / trễ hạn BT</td>
                <td>Mỗi dòng: phòng, lịch, giờ cũ → giờ mới (<i>dời sớm để kịp hạn</i> nếu dời về trước). Dòng đỏ
                    <i>trễ hạn BT, không còn khe trống kịp hạn</i> cần người lập lịch xếp lại tay.</td>
            </tr>
            <tr>
                <td>Kết quả kiểm soát tồn BTP</td>
                <td>Khi có nhập Max: xem <a href="#ket-qua-ton-btp">Ràng buộc, kết quả &amp; giới hạn</a>.</td>
            </tr>
            <tr>
                <td>Đang sắp lịch</td>
                <td>Người khác đang chạy sắp lịch tự động cho phân xưởng này. Chờ họ chạy xong rồi thử lại.</td>
            </tr>
        </tbody>
    </table>
</div>

<h3>Sao lưu và khôi phục</h3>
<ol class="ug-steps">
    <li>Trước khi chạy: mở <span class="ug-emoji">🤖</span>, khối ③, bấm <b>Tạo bản sao lưu</b>. Mã sao lưu mới được thêm
        và chọn sẵn trong danh sách.</li>
    <li>Kết quả không như ý: mở lại <span class="ug-emoji">🤖</span>, chọn mã ở <b>Sao lưu / Khôi phục lịch</b>, bấm
        <b>Khôi phục</b>, xác nhận. Lịch các lô chưa hoàn thành của phân xưởng trở về đúng lúc sao lưu.</li>
    <li>Kiểm soát tồn BTP tự tạo bản sao lưu trước khi lùi lô; mã này hiện trong thông báo kết quả để khôi phục khi cần.</li>
</ol>

<div class="ug-callout is-danger">
    <i class="fas fa-exclamation-circle"></i>
    <div>Khôi phục đưa <b>toàn bộ</b> lịch chưa hoàn thành của phân xưởng về lúc sao lưu: các chỉnh tay làm sau lúc đó cũng mất. Chỉ khôi phục ngay sau
        lần chạy cần huỷ.</div>
</div>

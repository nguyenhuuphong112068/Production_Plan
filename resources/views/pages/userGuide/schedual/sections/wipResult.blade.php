<p>
    Kiểm soát tồn BTP chỉ giãn lịch trong phạm vi các ràng buộc dưới đây, <b>không đánh đổi chúng lấy tồn thấp</b>.
</p>

<h3>Những điều luôn được giữ</h3>
<ul>
    <li><b>7 ngày NL/BB:</b> Ngày được phép cân, Ngày HH NL chính, Ngày HH BB, hạn PC / THT / ĐH / BP trước. Lô bị lùi
        không được vi phạm các ngày này.</li>
    <li><b>Quy tắc phòng:</b> không đè giờ trong cùng phòng, đúng thứ tự công đoạn và thời gian chờ, không bắt đầu trong
        ngày nghỉ.</li>
    <li><b>Lô không động tới:</b> lô đã chạy hoặc đang chạy, lô bắt đầu trước ngày sắp lịch.</li>
</ul>

<h3>Cách chạy và cơ chế an toàn</h3>
<ol class="ug-steps">
    <li><b>Tạo bản sao lưu</b> lịch trước khi sửa; mã sao lưu hiện trên thông báo kết quả, khôi phục bằng nút
        <b>Khôi phục</b> của hộp thoại Sắp lịch tự động.</li>
    <li><b>Chạy theo vòng:</b> đo tồn, ngưng nguồn, đổi chỗ hoặc giãn lịch trong phòng nguồn; không được thì lùi cả chuỗi
        công đoạn của lô, rồi đo lại.</li>
    <li><b>Mỗi vòng là một giao dịch trọn vẹn:</b> lỗi giữa chừng thì vòng đó tự huỷ, lịch trở về như trước vòng. Vòng nào
        làm phát sinh vi phạm ngày NL/BB cũng bị huỷ.</li>
    <li><b>Dừng</b> khi mọi nhóm đã dưới Max, sau tối đa 10 vòng hoặc 15 phút, hoặc khi 2 vòng liên tiếp không giảm được tồn.</li>
    <li><b>Dời lịch BT-HC-TI</b> bị lô mới đè lên, theo đúng cách ở <a href="#tu-kiem-tra">Tự kiểm tra</a>.</li>
</ol>

<h3>Thông báo "Kết quả kiểm soát tồn BTP"</h3>
<ul>
    <li><b>Trạng thái:</b> đạt (mọi nhóm trong Max), hết vòng hoặc hết thời gian mà còn vượt, không khả thi, hoặc dừng để
        giữ ngày NL/BB.</li>
    <li><b>Tồn trước và sau</b> của từng nhóm: đỉnh tồn, ngày đỉnh, số ngày vượt Max. Dòng <i>đã vào kho</i> là phần hàng
        đã nhập trước ngày sắp lịch, lùi lịch không bớt được.</li>
    <li><b>Danh sách lô bị lùi</b>, công đoạn lùi theo, và lô được kéo lên sớm hoặc chạy thay.</li>
    <li><b>Cảnh báo:</b> lô phải chạy đúng hạn, chiến dịch mới bị quá hạn biệt trữ, lịch BT-HC-TI đã dời, mã sao lưu.
        Lô ghi "Sắp lại lô" có thể đã lùi Pha chế: kiểm tra và chạy lại lịch <b>Cân NL (CNL)</b>.</li>
</ul>

<h3>Giới hạn cần biết</h3>
<ul>
    <li><b>Giảm tồn đổi lại đầu nguồn chạy muộn hơn.</b> Lô cần hàng muộn có thể ra thành phẩm trễ hơn lịch trước khi kiểm soát.</li>
    <li><b>Max quá thấp</b> so với năng lực công đoạn sau thì không đạt được, thông báo ghi không khả thi. Khi đó nên nâng
        Max hoặc tăng năng lực Bao phim / Đóng gói.</li>
    <li><b>Tồn từ lô đã chạy không giảm được</b>, vì các lô này không được dời.</li>
    <li><b>Vi phạm có sẵn</b> trong lịch trước khi kiểm soát không được sửa ở bước này; kiểm soát tồn BTP chỉ bảo đảm không
        phát sinh vi phạm mới.</li>
</ul>

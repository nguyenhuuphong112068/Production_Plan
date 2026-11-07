<p>
    Phần mềm chỉ xếp các công đoạn <b>chưa có lịch</b>; lô đã chạy hoặc đang chạy giữ nguyên. Mỗi lần xếp dựa trên các
    dữ liệu sau - lịch sai thường do một trong các dữ liệu này chưa đúng:
</p>

<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead>
            <tr><th style="width: 330px">Dữ liệu (khai ở đâu)</th><th>Phần mềm dùng để</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Kế hoạch sản xuất: lô, sản phẩm, số lượng, ngày cần hàng, thứ tự ưu tiên<br>
                    <small class="text-muted">Kế Hoạch › Kế Hoạch Sản Xuất</small></td>
                <td>Biết lô nào cần xếp và lô nào gấp hơn.</td>
            </tr>
            <tr>
                <td>Định mức theo sản phẩm và công đoạn<br><small class="text-muted">Định Mức › Sản Xuất</small></td>
                <td>Biết phòng nào được chạy, thời gian chuẩn bị và sản xuất mỗi lô, thời gian vệ sinh cấp I / cấp II,
                    số lô tối đa của một chiến dịch.</td>
            </tr>
            <tr>
                <td>Ngày có đủ NL, Ngày được phép cân, Ngày có đủ BB</td>
                <td>Mốc <b>sớm nhất</b> lô được bắt đầu. Lô chưa có Ngày có đủ NL thì
                    <b>chưa được xếp</b>.</td>
            </tr>
            <tr>
                <td>Ngày HH NL chính, Ngày HH BB, hạn PC / THT / ĐH / BP / ĐG trước</td>
                <td>Mốc <b>muộn nhất</b> lô phải bắt đầu.</td>
            </tr>
            <tr>
                <td>Thời gian chờ giữa hai công đoạn (biệt trữ, chờ kết quả kiểm nghiệm); lô thẩm định chờ lâu hơn</td>
                <td>Khoảng cách tối thiểu giữa hai công đoạn của cùng một lô.</td>
            </tr>
            <tr>
                <td>Ngày không sắp lịch<br><small class="text-muted">Ngày nghỉ công ty tự lấy từ eO2, chỉnh thêm trên hộp thoại</small></td>
                <td>Lô không bắt đầu trong ngày nghỉ (06:00 hôm đó tới 06:00 hôm sau).</td>
            </tr>
            <tr>
                <td>Lô đang thực thi (đã Nhận phòng ở Thực Thi Sản Xuất)</td>
                <td>Phòng được coi là bận tới giờ dự kiến kết thúc của lô đó, kể cả khi lô đang chạy quá giờ.</td>
            </tr>
            <tr>
                <td>Lịch đang có trên Gantt (lô đã xếp, vệ sinh, BT-HC-TI)</td>
                <td>Khoảng thời gian phòng đã bận; lô mới chỉ lấp vào khoảng trống.</td>
            </tr>
            <tr>
                <td>Định mức khuôn: khuôn ép vỉ theo thành phẩm (Định Mức › Khuôn EV), khuôn Định hình theo BTP
                    (Định Mức › Khuôn Định Hình); số lượng (số bộ) của mỗi khuôn ở Dữ Liệu Gốc › Khuôn Mẫu</td>
                <td>Không để nhiều phòng dùng cùng một khuôn quá số bộ đang có (chống trùng khuôn). Khuôn Định hình chỉ
                    áp dụng cho phòng Định hình đã chọn loại máy ở Dữ Liệu Gốc › Phòng Sản Xuất (hiện là S23 và S24);
                    khuôn chỉ lắp được cho phòng có loại máy khai ở khuôn (cả Đóng gói và Định hình).</td>
            </tr>
            <tr>
                <td>Khuôn ép vỉ đang gắn trên máy</td>
                <td>Hạn chế thay khuôn ở công đoạn Đóng gói.</td>
            </tr>
        </tbody>
    </table>
</div>

<p>
    Công đoạn cân không cần thả từng lô bằng tay: chọn trên Gantt các lô của công đoạn cần nguyên liệu đã cân, phần mềm
    gợi ý giờ cân ở <b>phòng cân trống sớm nhất</b>, tránh ngày nghỉ. Người lập lịch xem bảng gợi ý, bỏ bớt khối không
    muốn rồi mới ghi.
</p>

<h3>Lô nào cần chuẩn bị cân</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead><tr><th style="width: 180px">Công đoạn cân</th><th>Cấp cho công đoạn</th></tr></thead>
        <tbody>
            <tr><td><b>Cân NL</b></td><td><b>PC</b>; lô không có PC thì <b>THT</b>.</td></tr>
            <tr><td><b>Cân NL khác</b></td><td><b>ĐH</b> hoặc <b>BP</b> (một số ít lô: <b>ĐG</b>).</td></tr>
        </tbody>
    </table>
</div>
<p>
    Một lô công đoạn sau được tính là <b>cần chuẩn bị cân</b> khi công đoạn cân cấp cho nó chưa cân xong, và bản thân lô đó đã
    có lịch nhưng chưa nhận phòng / chưa hoàn thành.
</p>

<h3>Nút "⚖ Lô cần cân": chỉ hiện lô cần chuẩn bị cân</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/schedual/sap-lich-can.png', 'caption' => 'Đang bật ⚖ Lô cần cân: chỉ còn phòng cân và các phòng có lô cần chuẩn bị cân'])
<ul>
    <li>Nút nằm trên thanh trên cùng, cạnh nút <b>Tồn BTP</b>. Bấm để bật, bấm lần nữa để hiện lại toàn bộ lịch.</li>
    <li>Khi bật: các <b>phòng cân luôn hiện</b> (cả khi bộ lọc công đoạn đang bỏ nhóm Cân), các phòng sản xuất khác chỉ còn
        phòng có lô cần chuẩn bị cân trong khung đang xem. Lô không cần chuẩn bị cân và lịch cân đã cân xong bị ẩn; lịch
        BT-HC-TI trên phòng cân vẫn hiện để thấy vì sao phòng không trống.</li>
    <li>Lô cần chuẩn bị cân có huy hiệu tròn ⚖ lớn ở góc trên bên trái (nằm ngoài khung lô để không che tên):
        <span class="ug-swatch" style="background:#ea580c"></span> <b>cam</b> = chưa có lịch cân,
        <span class="ug-swatch" style="background:#16a34a"></span> <b>xanh</b> = đã có lịch cân nhưng chưa cân xong.</li>
    <li>Số trong ngoặc trên nút = số lô <b>chưa có lịch cân</b> trong khung đang xem (rê chuột lên nút để xem cả số lô đã có lịch cân).</li>
    <li>Biểu tượng <i class="far fa-check-square"></i> trong nút: chọn nhanh mọi lô chưa có lịch cân đang hiện trên lịch.</li>
</ul>

<h3>Nút "Gợi ý lịch cân"</h3>
<ol class="ug-steps">
    <li>Chọn các lô <b>PC / THT</b> (cần Cân NL) hoặc <b>ĐH / BP</b> (cần Cân NL khác) trên lịch: bấm từng lô, hoặc bấm
        <i class="far fa-check-square"></i> trong nút <b>⚖ Lô cần cân</b>. Chọn lẫn cả hai loại được; lịch cân, bảo trì
        và lô không có công đoạn cân liên kết trong vùng chọn được bỏ qua.</li>
    <li>Bấm <b>Gợi ý lịch cân (n)</b>, n = số lô công đoạn sau đang chọn. Nút hiện khi đang bật <b>⚖ Lô cần cân</b>
        hoặc khi đang chọn lô.</li>
    <li>Bảng <b>Gợi ý lịch cân</b> hiện ra, mỗi dòng là một <b>khối cân</b>: loại cân, phòng, giờ bắt đầu, giờ kết thúc
        (kèm cấp vệ sinh và giờ vệ sinh xong), các lô trong khối và giờ bắt đầu công đoạn sau của từng lô.
        <b>Dòng nền đỏ</b>: phòng cân không kịp cân xong trước giờ bắt đầu công đoạn sau (ghi rõ trễ bao lâu) - lô đó sẽ
        báo lỗi đen trên lịch; nên dời công đoạn sau hoặc bỏ tick để tự xử lý.</li>
    <li>Bỏ tick khối không muốn xếp, bấm <b>Xếp lịch cân</b>. Lịch ghi <b>ngay</b> (không qua nút 💾), ở trạng thái
        <b>chưa submit</b>. Sau đó vẫn kéo thả chỉnh được như lịch thường; xoá bằng 🚫. Kiểm tra xong bấm 📤 Submit.</li>
</ol>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/schedual/sap-lich-can-goi-y.png', 'caption' => 'Bảng Gợi ý lịch cân: mỗi dòng một khối cân, dòng đỏ là cân không kịp công đoạn sau'])

<h3>Phần mềm chọn phòng và giờ cân thế nào</h3>
<ul>
    <li><b>Thứ tự:</b> lô có công đoạn sau bắt đầu sớm nhất được xếp trước, nên lô gấp lấy giờ cân sớm trước.</li>
    <li><b>Phòng:</b> phòng cân đang hoạt động của phân xưởng có <b>định mức</b> cho BTP và đúng loại cân (Cân NL / Cân NL
        khác); lô có chỉ định phòng thì chỉ xét phòng đó. Trong các phòng hợp lệ, chọn phòng có giờ trống <b>sớm nhất</b>.</li>
    <li><b>Không sớm hơn:</b> thời điểm hiện tại, <b>Ngày có đủ NL</b>, <b>Ngày được phép cân</b>; với Cân NL, không sớm hơn
        giờ bắt đầu công đoạn sau trừ <b>thời gian biệt trữ sau cân</b> khai ở danh mục BTP (để NL đã cân không chờ quá hạn).</li>
    <li><b>Tránh ngày nghỉ:</b> cả khối cân lẫn vệ sinh sau đó phải nằm trong ca làm việc của phòng (phòng 3 ca chạy suốt
        ngày đêm, phòng hành chính 07:00 - 16:00) và không chạm ngày nghỉ (ngày nghỉ tính từ 06:00 tới 06:00 hôm sau). Khối
        sẽ chạm ngày nghỉ thì dời sang sau ngày nghỉ.</li>
    <li><b>Không chồng lịch:</b> tránh mọi lịch chưa hoàn thành trên phòng cân (kể cả BT-HC-TI và lô đang giữ phòng) và
        các khối vừa gợi ý trước đó trong cùng bảng. Giờ bắt đầu làm tròn lên bước 15 phút.</li>
    <li><b>Cân chung:</b> lô cùng chiến dịch, cùng BTP, cùng loại cân được cân chung một khối, tối đa <b>Số lô tối đa</b>
        của định mức phòng; dư thì tách khối mới. Thời gian khối = thời gian chuẩn bị + thời gian sản xuất ×
        (1 + (Hệ số chiến dịch − 1) × (số lô − 1)); một lô = chuẩn bị + sản xuất. Vệ sinh sau khối: cấp II nếu định mức có
        thời gian vệ sinh cấp II, không thì cấp I.</li>
</ul>
<div class="ug-example">
    <b>Ví dụ</b> định mức cân: chuẩn bị 30 phút, sản xuất 5 giờ, hệ số chiến dịch 1,8, số lô tối đa 3. Chiến dịch 7 lô
    được chia 3 khối 3 + 3 + 1 lô: khối 3 lô mất 30 phút + 5 giờ × 2,6 = 13 giờ 30 phút, khối 1 lô mất 5 giờ 30 phút. Các
    khối được đặt vào các phòng cân trống sớm nhất, có thể khác phòng nhau.
</div>

<h3>Lô không được gợi ý</h3>
<p>Liệt kê ở cuối bảng gợi ý (bấm để mở) kèm lý do:</p>
<ul>
    <li>Đã có lịch cân (ghi phòng, giờ) hoặc đã cân xong - muốn đổi thì kéo thả lịch cân trên Gantt.</li>
    <li>Công đoạn sau đã nhận phòng / hoàn thành.</li>
    <li>Lô đang đánh dấu <b>Không sắp lịch</b> trong danh sách KHSX.</li>
    <li>Chưa có định mức cân cho BTP (hoặc tại phòng chỉ định) - khai định mức rồi gợi ý lại.</li>
    <li>Không tìm được giờ trống trong 120 ngày tới.</li>
</ul>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Lúc bấm <b>Xếp lịch cân</b>, phần mềm kiểm tra lại phòng còn trống. Nếu người khác vừa xếp lịch chen vào cùng giờ,
        khối đó <b>không được ghi</b> và được báo trong thông báo kết quả: bấm <b>Gợi ý lịch cân</b> lại. Khác với Sắp lịch tự
        động (bước Cân NL), chức năng này xếp cân <b>sớm nhất có thể</b> chứ không canh theo một số ngày trước công đoạn sau.</div>
</div>

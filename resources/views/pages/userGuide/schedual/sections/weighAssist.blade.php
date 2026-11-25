<p>
    Công đoạn cân không cần thả từng lô bằng tay: chọn trên Gantt các lô của công đoạn cần nguyên liệu đã cân, phần mềm
    gợi ý giờ cân ở <b>phòng cân trống sớm nhất</b>, tránh ngày nghỉ. Gợi ý hiện thành khối cân nháp trên lịch: người lập lịch
    chỉnh tay bằng kéo thả rồi mới bấm 💾 để lưu.
</p>
<p>
    Người lập lịch trung tâm cân dùng nhóm quyền <b>Weighing Scheduler</b>: làm được mọi thao tác ở mục này và Submit lịch cân,
    nhưng không sửa được lịch các công đoạn khác (xem <a href="#phan-quyen">Quyền Truy Cập</a>).
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

<h3>Nhãn loại cân trên lịch cân</h3>
<p>
    Mỗi thanh lịch cân (kể cả khối cân nháp) có một nhãn nhỏ ở mép trên bên trái, cho biết nguyên liệu cân cấp cho công đoạn nào:
</p>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead><tr><th style="width: 180px">Nhãn</th><th>Ý nghĩa</th></tr></thead>
        <tbody>
            <tr><td><span class="badge" style="background:#1d4ed8;color:#fff">🧪 NL PC</span></td><td>Cân NL cho <b>PC</b> (<span class="badge" style="background:#1d4ed8;color:#fff">🧪 NL THT</span>: lô không có PC, cấp thẳng cho <b>THT</b>).</td></tr>
            <tr><td><span class="badge" style="background:#7e22ce;color:#fff">💊 Nang rỗng</span></td><td>Cân NL khác: nang rỗng cho <b>ĐH</b>.</td></tr>
            <tr><td><span class="badge" style="background:#be185d;color:#fff">🖌️ NL BP</span></td><td>Cân NL khác: nguyên liệu (tá dược) cho <b>BP</b>.</td></tr>
            <tr><td><span class="badge" style="background:#475569;color:#fff">📦 NL ĐG</span></td><td>Cân NL khác cho <b>ĐG</b> (rất ít lô).</td></tr>
        </tbody>
    </table>
</div>

<h3>Nút "⚖ Lô cần cân": chỉ hiện lô cần chuẩn bị cân</h3>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/schedual/sap-lich-can.png', 'caption' => 'Đang bật ⚖ Lô cần cân: chỉ còn phòng cân và các phòng có lô cần chuẩn bị cân'])
<ul>
    <li>Ba nút sắp lịch cân nằm chung một cụm <b>⚖ Sắp lịch cân</b> ở cuối thanh trên cùng (sát mép phải), theo thứ tự
        thao tác: <b>Lô cần cân</b> → <b>Gợi ý lịch cân</b> → <b>Ghép cân chung</b>. Cụm luôn đứng yên một chỗ; nút chưa
        dùng được thì mờ đi, rê chuột lên để biết cần làm gì trước. Bấm <b>Lô cần cân</b> để bật, bấm lần nữa để hiện lại toàn bộ lịch.</li>
    <li>Khi bật: các <b>phòng cân luôn hiện</b> (cả khi bộ lọc công đoạn đang bỏ nhóm Cân), các phòng sản xuất khác chỉ còn
        phòng có lô cần chuẩn bị cân trong khung đang xem. Lô không cần chuẩn bị cân và lịch cân đã cân xong bị ẩn; lịch
        BT-HC-TI trên phòng cân vẫn hiện để thấy vì sao phòng không trống.</li>
    <li>Lô cần chuẩn bị cân có huy hiệu tròn ⚖ lớn ở góc trên bên trái (nằm ngoài khung lô để không che tên):
        <span class="ug-swatch" style="background:#ea580c"></span> <b>cam</b> = chưa có lịch cân,
        <span class="ug-swatch" style="background:#16a34a"></span> <b>xanh</b> = đã có lịch cân nhưng chưa cân xong,
        <span class="ug-swatch" style="background:#0ea5e9"></span> <b>xanh dương</b> = đã có khối cân nháp (gợi ý chưa lưu), cùng màu khối nháp.</li>
    <li>Số trong ngoặc trên nút = số lô <b>chưa có lịch cân</b> trong khung đang xem (rê chuột lên nút để xem cả số lô đã có lịch cân).</li>
    <li>Biểu tượng <i class="far fa-check-square"></i> trong nút: chọn nhanh mọi lô chưa có lịch cân đang hiện trên lịch.</li>
</ul>

<h3>Nút "Gợi ý lịch cân"</h3>
<ol class="ug-steps">
    <li>Chọn các lô <b>PC / THT</b> (cần Cân NL) hoặc <b>ĐH / BP</b> (cần Cân NL khác) trên lịch: bấm từng lô, hoặc bấm
        <i class="far fa-check-square"></i> trong nút <b>⚖ Lô cần cân</b>. Chọn lẫn cả hai loại được; lịch cân, bảo trì
        và lô không có công đoạn cân liên kết trong vùng chọn được bỏ qua.</li>
    <li>Bấm <b>Gợi ý lịch cân (n)</b>, n = số lô công đoạn sau đang chọn. Nút mờ đi khi chưa chọn lô.</li>
    <li>Phần mềm đặt các <b>khối cân nháp</b> lên lịch, ở phòng cân và giờ gợi ý: màu
        <span class="ug-swatch" style="background:#0ea5e9"></span> <b>xanh dương</b>, tên có dấu ✎, viền trắng nét đứt, kèm
        lịch vệ sinh ngay sau. Lô cùng chiến dịch cân chung thành một khối. Khối <b>màu đen</b>: phòng cân không kịp cân xong
        trước giờ bắt đầu công đoạn sau (rê chuột để xem trễ bao lâu). Nếu khối sớm nhất nằm ngoài khung đang xem, bấm
        <b>Xem khối nháp</b> để lịch chuyển tới đó.</li>
    <li>Khối nháp <b>chỉ có trên màn hình</b>, được đếm vào <b>Thay đổi chưa lưu</b> như kéo thả thường. Kéo sang phòng /
        giờ khác, kéo dãn để chỉnh (khối giữ nguyên thời lượng khi kéo, kể cả khối ghép nhiều lô). Lịch vệ sinh <b>luôn chạy
        ngay sau khi cân xong</b>: kéo / kéo dãn khối cân thì vệ sinh dời theo ngay trên lịch, cùng phòng, giữ thời lượng vệ
        sinh; kéo riêng vệ sinh đi chỗ khác thì nó quay về sát sau khối cân (kéo dãn mép phải của vệ sinh để đổi thời lượng).
        Áp dụng cho mọi lịch cân đang chỉnh chưa lưu, không chỉ khối nháp. Bấm đúp vào khối nháp
        không mở hộp thoại sửa giờ (hộp thoại đó lưu ngay).</li>
    <li>Bấm <span class="ug-emoji">💾</span> (hoặc <span class="ug-key">Ctrl</span> + <span class="ug-key">S</span>) để lưu
        cùng các thay đổi khác; lịch cân mới ở trạng thái <b>chưa submit</b>, kiểm tra xong bấm 📤 Submit. Bấm ✕ ở
        <b>Thay đổi chưa lưu</b> để bỏ toàn bộ khối nháp (cùng các kéo thả chưa lưu khác).</li>
    <li>Gợi ý thêm lần nữa khi còn nháp chưa lưu: lô đã có nháp không gợi ý lại, khối mới không chồng giờ lên nháp đang có.
        Muốn gợi ý lại một khối nháp (ví dụ tách khối ghép thành từng lô): chọn khối nháp đó rồi bấm <b>Gợi ý lịch cân</b>,
        khối cũ được thay bằng khối mới.</li>
</ol>
@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/schedual/sap-lich-can-goi-y.png', 'caption' => 'Khối cân nháp (xanh dương, dấu ✎) trên phòng cân, chờ chỉnh tay rồi bấm 💾'])

<h3>Nút "Ghép cân chung": tự chọn các lô cân cùng chiến dịch</h3>
<ol class="ug-steps">
    <li>Chọn từ 2 lô trở lên: lô PC / THT / ĐH / BP chưa có lịch cân, và / hoặc các <b>khối cân nháp</b> ✎ đã có trên lịch.
        Nút <b>Ghép cân chung (n)</b> (cạnh nút Gợi ý lịch cân) chỉ bấm được khi đang chọn từ 2 lô.</li>
    <li>Bấm <b>Ghép cân chung</b>: mọi lô đang chọn <b>cùng BTP và cùng loại cân</b> được cân chung <b>một khối</b>, đặt ở
        phòng cân trống sớm nhất, bất kể mã chiến dịch có sẵn. Lô khác BTP / loại cân không ghép được với nhau: mỗi nhóm
        thành một khối riêng (thông báo ghi rõ). Khối nháp đang chọn được thay bằng khối ghép.</li>
    <li>Khối ghép là nháp như mọi khối khác: kéo thả chỉnh tay, bấm 💾 để lưu. Lưu xong các lô có chung giờ bắt đầu, phòng
        nên hiện thành một khối cân trên Gantt; mã chiến dịch của lô không đổi.</li>
</ol>
<ul>
    <li>Khối ghép không bị giới hạn bởi <b>Số lô tối đa</b> của định mức; vượt thì khối có cảnh báo ⚠ (rê chuột để xem).</li>
    <li>Khối ghép tính mốc 3 ngày theo lô <b>cần dùng sớm nhất</b>: khối bắt đầu từ 3 ngày trước ngày cần dùng của lô đó, nên
        lô cần sớm không bị trễ. Các lô cần dùng muộn hơn được <b>cân sớm hơn 3 ngày</b> trước ngày cần dùng của mình; khối có
        cảnh báo ⚠ ghi rõ từng lô (rê chuột để xem).</li>
    <li>Muốn tách lại: chọn khối ghép rồi bấm <b>Gợi ý lịch cân</b>.</li>
</ul>

<h3>Phần mềm chọn phòng và giờ cân thế nào</h3>
<ul>
    <li><b>Thứ tự:</b> lô có công đoạn sau bắt đầu sớm nhất được xếp trước, nên lô gấp lấy giờ cân sớm trước.</li>
    <li><b>Phòng:</b> phòng cân đang hoạt động của phân xưởng có <b>định mức</b> cho BTP và đúng loại cân (Cân NL / Cân NL
        khác); lô có chỉ định phòng thì chỉ xét phòng đó. Trong các phòng hợp lệ, chọn phòng có giờ trống <b>sớm nhất</b>.</li>
    <li><b>Không sớm hơn 3 ngày trước ngày cần dùng:</b> khối cân bắt đầu từ 06:00 của ngày bắt đầu công đoạn sau trừ 3 ngày
        trở đi (ngày tính từ 06:00 như ngày nghỉ). Ví dụ PC bắt đầu 09:00 ngày 04/12 thì cân sớm nhất 06:00 ngày 01/12; PC bắt
        đầu 03:00 ngày 04/12 (ca đêm của ngày 03/12) thì sớm nhất 06:00 ngày 30/11.</li>
    <li><b>Không sớm hơn</b> (thêm): thời điểm hiện tại, <b>Ngày có đủ NL</b>, <b>Ngày được phép cân</b>; với Cân NL, không sớm hơn
        giờ bắt đầu công đoạn sau trừ <b>thời gian biệt trữ sau cân</b> khai ở danh mục BTP (để NL đã cân không chờ quá hạn).</li>
    <li><b>Tránh ngày nghỉ:</b> cả khối cân lẫn vệ sinh sau đó phải nằm trong ca làm việc của phòng (phòng 3 ca chạy suốt
        ngày đêm, phòng hành chính 07:00 - 16:00) và không chạm ngày nghỉ (ngày nghỉ tính từ 06:00 tới 06:00 hôm sau). Khối
        sẽ chạm ngày nghỉ thì dời sang sau ngày nghỉ.</li>
    <li><b>Không chồng lịch:</b> tránh mọi lịch chưa hoàn thành trên phòng cân (kể cả BT-HC-TI và lô đang giữ phòng) và
        các khối nháp đang có trên lịch. Giờ bắt đầu làm tròn lên bước 15 phút.</li>
    <li><b>Cân chung:</b> lô cùng chiến dịch, cùng BTP, cùng loại cân được cân chung một khối, tối đa <b>Số lô tối đa</b>
        của định mức phòng; dư thì tách khối mới. Lô cần dùng muộn tới mức khối phải chờ qua giờ bắt đầu công đoạn sau của
        lô đầu (theo mốc 3 ngày) cũng được tách sang khối mới. Muốn tự quyết lô nào cân chung: dùng <b>Ghép cân chung</b>. Thời gian khối = thời gian chuẩn bị + thời gian sản xuất ×
        (1 + (Hệ số chiến dịch − 1) × (số lô − 1)); một lô = chuẩn bị + sản xuất. Vệ sinh sau khối: cấp II nếu định mức có
        thời gian vệ sinh cấp II, không thì cấp I.</li>
</ul>
<div class="ug-example">
    <b>Ví dụ</b> định mức cân: chuẩn bị 30 phút, sản xuất 5 giờ, hệ số chiến dịch 1,8, số lô tối đa 3. Chiến dịch 7 lô
    được chia 3 khối 3 + 3 + 1 lô: khối 3 lô mất 30 phút + 5 giờ × 2,6 = 13 giờ 30 phút, khối 1 lô mất 5 giờ 30 phút. Các
    khối được đặt vào các phòng cân trống sớm nhất, có thể khác phòng nhau.
</div>

<h3>Lô không được gợi ý</h3>
<p>Liệt kê trong thông báo sau khi gợi ý (bấm để mở) kèm lý do:</p>
<ul>
    <li>Đã có lịch cân (ghi phòng, giờ) hoặc đã cân xong - muốn đổi thì kéo thả lịch cân trên Gantt.</li>
    <li>Công đoạn sau đã nhận phòng / hoàn thành.</li>
    <li>Lô đang đánh dấu <b>Không sắp lịch</b> trong danh sách KHSX.</li>
    <li>Chưa có định mức cân cho BTP (hoặc tại phòng chỉ định) - khai định mức rồi gợi ý lại.</li>
    <li>Không tìm được giờ trống trong 120 ngày tới.</li>
</ul>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>Lúc bấm <span class="ug-emoji">💾</span>, phần mềm kiểm tra lại từng khối nháp: phòng còn trống (người khác có thể vừa xếp
        lịch chen vào), phòng có định mức cân. Khối không đạt <b>không được ghi</b> và được liệt kê trong thông báo kết quả: bấm <b>Gợi ý lịch cân</b> lại.</div>
</div>

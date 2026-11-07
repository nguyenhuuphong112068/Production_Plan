<p>
    Bấm <span class="ug-emoji">🤖</span> trên thanh công cụ của Lịch Sản Xuất Chart. Hộp thoại gồm 3 khối; điền xong
    bấm <span class="btn btn-sm btn-primary ug-ui">Chạy</span>. Phần mềm xếp lịch cho mọi lô <b>chưa có lịch</b> của phân
    xưởng đang chọn; lô đã có lịch, đã chạy hoặc đang chạy giữ nguyên. Muốn xếp lại từ đầu một đoạn thời gian thì
    xoá lịch đoạn đó bằng <span class="ug-emoji">🗑️</span> trước.
</p>

<div class="ug-callout is-warn">
    <i class="fas fa-exclamation-triangle"></i>
    <div>
        Nếu thấy dòng đỏ <i>"Một hoặc nhiều sản phẩm chưa được định mức!"</i> thì có sản phẩm chưa định mức phòng cho một
        công đoạn. Các công đoạn từ công đoạn thiếu định mức trở đi bị ẩn khỏi lựa chọn; thiếu ngay từ Pha chế thì không
        chạy được. Định mức đầy đủ ở <b>Định Mức › Sản Xuất</b> rồi mở lại hộp thoại.
    </div>
</div>

<h3>① Phạm vi sắp lịch</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Ngày bắt đầu sắp lịch', true, 'Lịch mới bắt đầu từ <b>06:00</b> ngày này (không sớm hơn giờ hiện tại). Mặc định hôm nay.'],
    ['Kiểu sắp lịch: Theo Công Đoạn', false, 'Mặc định. Chọn <b>công đoạn cuối</b> cần xếp trên dải bước: <b>PC</b> (chỉ Pha chế), <b>THT</b> (Pha chế → Trộn hoàn tất), <b>ĐH</b>, <b>BP</b>, <b>ĐG</b> (Pha chế → Đóng gói). Bước <b>CNL</b> chỉ xếp lịch Cân nguyên liệu, lùi từ ngày bắt đầu công đoạn sau của lô đã có lịch.'],
    ['Kiểu sắp lịch: Theo Line', false, 'Chọn một Line (phòng): các lô đang chờ của Line đó được xếp vào đúng phòng này theo thứ tự trên danh sách của Line. Chế độ này không chạy Kiểm soát tồn BTP.'],
]])

<h3>② Ràng buộc sắp lịch</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Thời gian chờ kết quả kiểm nghiệm (ngày)', false, 'Khoảng chờ tối thiểu trước Trộn hoàn tất, Định hình, Bao phim, Đóng gói, tách lô thẩm định và lô thương mại. Giá trị đang hiện là giá trị phần mềm dùng: lô thẩm định THT 1 ngày, ĐH / BP / ĐG 5 ngày; lô thương mại 0 ngày. <b>Lưu ý:</b> hiện sửa các ô này chưa có tác dụng, phần mềm luôn dùng giá trị trên. Thời gian chờ chỉ áp dụng cho nhóm Thương mại và Khuyến mãi (xem <a href="#thu-tu-uu-tien">Thứ tự ưu tiên</a>).'],
    ['Hạn chế xuống khuôn (ĐG)', false, 'Bật (mặc định): ở Đóng gói, ưu tiên xếp lô vào phòng đang gắn đúng khuôn để giảm số lần thay khuôn.'],
    ['Trễ tối đa để giữ khuôn (giờ)', false, 'Chỉ hiện khi bật ô trên. Phòng đúng khuôn được chọn nếu bắt đầu trễ hơn phòng sớm nhất <b>không quá</b> số giờ này (mặc định 72).'],
    ['Giới hạn tồn BTP (viên)', false, 'Bật để nhập mức tồn tối đa (Max) cho <b>Tồn chờ Định hình (ĐH)</b>, <b>Tồn chờ Bao phim (BP)</b>, <b>Tồn chờ Đóng gói / Ép vỉ (ĐG)</b>. Để trống = không giới hạn. Có Max thì sau khi sắp lịch phần mềm chạy tiếp <a href="#kiem-soat-ton-btp">Kiểm soát tồn BTP</a>. Max đã nhập được nhớ theo phân xưởng.'],
]])

<h3>③ Ngày nghỉ &amp; công cụ</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Ngày không sắp lịch', false, 'Lịch chọn nhiều ngày; chọn sẵn các ngày nghỉ của phân xưởng. Lô <b>không bắt đầu</b> trong ngày đã chọn (tính từ 06:00 hôm đó tới 06:00 hôm sau). Bấm vào ngày để thêm / bỏ cho lần chạy này.'],
    ['Phân bổ khuôn Đóng gói + Định hình (Auto)', false, 'Khi bấm <b>Chạy</b>, phần mềm đã tự chọn khuôn rảnh cho lô Đóng gói và lô Định hình ở phòng S23, S24 (lô phải chờ tới khi có khuôn rảnh). Hai nút này dùng khi lịch đã có sẵn: sau khi đổi định mức khuôn hoặc kéo thả tay. <b>Bổ sung khuôn thiếu</b>: gán khuôn cho lô chưa có khuôn (lô Định hình có tiền tố <b>[ĐH]</b> trong danh sách sản phẩm chưa khai khuôn). <b>Làm mới &amp; Phân bổ</b>: xoá phân bổ khuôn cũ của các lô chưa bắt đầu và phân bổ lại từ đầu (có hỏi xác nhận). Thông báo kết quả ghi số lô đã gán khuôn và số lô <b>không có khuôn rảnh</b> theo từng công đoạn; các nút này không dời giờ lô, nên lô không có khuôn rảnh vẫn báo <i>Thiếu Khuôn</i> cho tới khi bạn dời lô, xoá lịch rồi chạy lại sắp lịch tự động, hoặc tăng số bộ khuôn.'],
    ['Sao lưu / Khôi phục lịch', false, '<b>Tạo bản sao lưu</b> lưu toàn bộ lịch hiện tại với một mã. Chọn mã trong danh sách rồi bấm <b>Khôi phục</b> để đưa lịch về đúng lúc sao lưu. Xem <a href="#ket-qua-sao-luu">Kết quả, sao lưu &amp; khôi phục</a>.'],
]])

<h3>Các bước chạy</h3>
<ol class="ug-steps">
    <li>Bấm <b>Tạo bản sao lưu</b> (khối ③) để có đường lui.</li>
    <li>Chọn ngày bắt đầu, kiểu sắp lịch và công đoạn cuối (thường là <b>ĐG</b>).</li>
    <li>Kiểm tra ngày không sắp lịch, bật / tắt hạn chế xuống khuôn, nhập Max tồn BTP nếu cần.</li>
    <li>Bấm <span class="btn btn-sm btn-primary ug-ui">Chạy</span> và chờ. Một phân xưởng lớn có thể mất 1-2 phút, lâu
        hơn khi phải sắp lại để kịp hạn NL/BB hoặc khi chạy Kiểm soát tồn BTP. Không đóng trang trong lúc chạy.</li>
    <li>Đọc thông báo kết quả rồi kiểm tra lịch (xem <a href="#ket-qua-sao-luu">Kết quả</a>).</li>
</ol>

<div class="ug-callout">
    <i class="fas fa-lightbulb"></i>
    <div>Phần mềm xếp như thế nào, vì sao lô này đứng trước lô kia: xem các mục <a href="#nguyen-ly-tong-quan">Nguyên lý</a>
        ngay bên dưới.</div>
</div>

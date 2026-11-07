<p>
    Các màn hình danh mục (Dữ Liệu Gốc, Danh Mục, Định Mức...) dùng chung một kiểu bảng và cùng bộ nút. Hướng dẫn từng màn hình
    chỉ ghi phần riêng; phần chung xem ở đây.
</p>

<h3>Bảng danh sách</h3>
<ul>
    <li><b>Search</b> (góc phải trên bảng): gõ để lọc nhanh trên mọi cột đang hiện.</li>
    <li>Bấm tiêu đề cột để sắp xếp; chọn số dòng mỗi trang và chuyển trang ở dưới bảng.</li>
    <li>Màn hình hẹp: các cột không đủ chỗ được thu lại, bấm dấu <span class="ug-key">+</span> đầu dòng để xem.</li>
</ul>

<h3>Các nút thường gặp</h3>
<div class="table-responsive">
    <table class="table table-bordered ug-table">
        <thead><tr><th style="width: 130px">Nút</th><th>Tác dụng</th></tr></thead>
        <tbody>
            <tr><td><span class="btn btn-sm btn-success ug-ui"><i class="fas fa-plus"></i> Thêm</span></td>
                <td>Mở hộp thoại thêm mới. Nhập các ô (ô có dấu <span class="ug-req">*</span> là bắt buộc), bấm <b>Lưu</b>.
                    Ô bị lỗi hiện câu báo đỏ ngay dưới ô.</td></tr>
            <tr><td><span class="btn btn-sm btn-warning ug-ui"><i class="fas fa-edit"></i></span></td>
                <td>Sửa dòng: hộp thoại cập nhật mở sẵn dữ liệu cũ.</td></tr>
            <tr><td><span class="btn btn-sm btn-danger ug-ui"><i class="fas fa-lock"></i></span>
                <span class="btn btn-sm btn-success ug-ui"><i class="fas fa-unlock"></i></span></td>
                <td><b>Vô hiệu (DeActive)</b> / <b>kích hoạt lại</b>. Dữ liệu không bị xoá, chỉ ngưng dùng: không còn hiện trong các
                    danh sách chọn ở màn hình khác. Phần mềm hỏi xác nhận trước khi đổi.</td></tr>
            <tr><td><span class="btn btn-sm btn-info ug-ui"><i class="fas fa-history"></i></span></td>
                <td><b>Lịch sử thay đổi</b>: ai sửa, lúc nào, giá trị trước - sau. Số nhỏ trên nút là số lần đã sửa.</td></tr>
        </tbody>
    </table>
</div>

<div class="ug-callout">
    <i class="fas fa-info-circle"></i>
    <div>Mọi thao tác thêm / sửa / vô hiệu đều ghi người làm và thời điểm (cột Người Tạo, Ngày Tạo, Người Sửa, Ngày Sửa) để phục
        vụ truy vết.</div>
</div>

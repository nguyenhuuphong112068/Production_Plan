# Business Rules Summary - Maintenance Scheduling System (PMS)

Tài liệu này tóm tắt các quy tắc nghiệp vụ quan trọng đã được thiết lập cho hệ thống Tự động sắp lịch Bảo trì (HC/BT/TI).

## 1. Logic Sắp Lịch Tự Động (Auto-Scheduling)

### A. Gom Nhóm Thiết Bị (Grouping)
- **Quy tắc**: Các thiết bị con có cùng mã **Thiết bị Lớn (`Parent_Equip_id`)** trong cùng một phòng máy sẽ được gôm lại thành **một khối sự kiện duy nhất** trên lịch.
- **Thời gian**: Khung thời gian (`start` đến `end`) của sự kiện gôm nhóm này sẽ bao gồm tổng thời gian thực hiện (`PM`) của tất cả các thiết bị con cộng lại.

### B. Kiểm Tra Xung Đột Thời Gian (Overlap Prevention)
- **Quy tắc**: Lịch bảo trì không được chồng lấn lên lịch sản xuất và lịch vệ sinh sau sản xuất (`VS-II`).
- **Logic**: Thời điểm rảnh sớm nhất của một phòng máy được xác định bằng:  
  `max(Giờ kết thúc sản xuất, Giờ kết thúc vệ sinh sau sản xuất - end_clearning)`.

### C. Logic Vệ Sinh Dọn Dẹp (`Clearning`)
- **Chỉ áp dụng cho**: Loại **Bảo trì (TB)** hoặc các mã công đoạn kết thúc bằng `_8`.
- **Loại trừ**: Không áp dụng tính toán `clearning` cho **Hiệu chuẩn (HC)** và **Tiện ích (TI)**. Các trường liên quan đến vệ sinh của 2 loại này sẽ được đặt về `NULL`.

---

## 2. Định Dạng Hiển Thị (Display Formatting)

### A. Quy Tắc Tiêu Đề (`Event Title`)
Tiêu đề sự kiện gôm nhóm được định dạng để hiển thị rõ ràng thông tin máy cha và máy con:
- **Dòng 1**: `[Mã Thiết Bị Lớn] _ [Tên Thiết Bị Lớn] :`
- **Các dòng tiếp theo**: Mỗi dòng là một dấu gạch ngang và mã thiết bị con `- [Mã Thiết Bị Con]`.
- **Kỹ thuật**: Sử dụng thẻ `<br/>` trong chuỗi tiêu đề và cấu hình FullCalendar để render HTML.

### B. CSS Hỗ Trợ
- Đã bổ sung `white-space: pre-wrap !important;` vào class `.fc-event-title` để hỗ trợ xuống dòng tự động và nhận diện thẻ xuống dòng.

---

## 3. Chức Năng Hủy Lịch (Cancel Schedule)

- **Chế độ 1 - Hủy Toàn Bộ**: Xóa lịch của tất cả các thiết bị thuộc loại đang chọn (HC, BT, hoặc TI).
- **Chế độ 2 - Hủy Theo Thiết Bị**: Chỉ xóa lịch của thiết bị (Resource) được chọn trong Modal.
- **Điều kiện**: Chỉ tác động đến các bản ghi chưa hoàn thành (`finished = 0`) và không phải là Tank (`tank = 0`), tính từ ngày bắt đầu được chọn trong đầu vào.

---

## 4. Cấu Trúc File Trọng Yếu
- **Backend**: `app/Http/Controllers/Pages/MaintenanceSchedual/MaintenanceSchedualController.php` (Chứa logic `autoSchedual` và `cancelSchedule`).
- **Frontend**: `resources/js/Pages/MaintenanceCalender .jsx` (Chứa cấu hình hiển thị lịch và render HTML).
- **Style**: `resources/js/Pages/calendar.css` (Chứa CSS hỗ trợ hiển thị nhiều dòng).

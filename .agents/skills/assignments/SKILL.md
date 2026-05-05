---
name: Quản lý Nhân sự & Phân công Sản xuất
description: Hệ thống toàn diện để quản lý bậc kỹ năng nhân sự theo phòng (1-4) và lập lịch phân công sản xuất với cơ chế xác thực quyền hạn nghiêm ngặt và kiểm tra thời gian thực.
---

# Quản lý Nhân sự & Phân công Sản xuất

Kỹ năng này tài liệu hóa quy trình quản lý nhân sự và phân công sản xuất hiện đại hóa được triển khai cho hệ thống Lập kế hoạch Sản xuất.

## Các Tính năng Cốt lõi

### 1. Mô hình Kỹ năng theo Phòng Sản xuất
- **Chuyển đổi:** Chuyển từ hệ thống "Bậc" chung sang mô hình định mức tay nghề theo từng phòng cụ thể.
- **Lưu trữ:** Dữ liệu kỹ năng được lưu trong bảng `employee_rooms` với định dạng `room_id:level`.
- **Bậc kỹ năng:** Các mức từ 1 đến 4, trong đó 4 là mức thành thạo cao nhất.
- **Phản hồi trực quan:** Các bậc được mã hóa màu bằng dải màu xanh dương (Xanh nhạt cho Bậc 1 đến Xanh đậm cho Bậc 4).

### 2. Lập lịch & Xác thực Thông minh
- **Chặn trùng lặp nghiêm ngặt:** Ngăn chặn việc phân công cùng một nhân sự vào cùng một phòng nhiều lần trong một ngày.
- **Xác thực quyền hạn theo phòng:** Người lập lịch bị chặn phân công nhân sự vào các phòng mà họ chưa được cấp định mức tay nghề.
- **Cảnh báo lệch ca:** Cảnh báo người dùng nếu họ phân công nhân viên vào ca làm việc khác với lịch trực chính thức của họ (ví dụ: phân vào Ca 1 trong khi lịch chính thức là Hành chính).
- **Quản lý Nghỉ phép:** 
    - Nhân sự nghỉ phép (Ca 'P') được làm mờ và gạch ngang tên trong thanh bên (sidebar).
    - Tính năng kéo-thả bị vô hiệu hóa đối với nhân sự nghỉ phép.
    - Cơ chế xác thực nghiêm ngặt chặn mọi nỗ lực phân công nhân sự nghỉ phép thông qua cảnh báo popup.

### 3. Bộ lọc Động & Tối ưu hóa Giao diện (UI)
- **Lọc theo Tổ sản xuất:** 
    - Các menu chọn trong bảng chính tự động lọc nhân sự dựa trên Tổ đang chọn.
    - Thanh bên "Tình hình nhân sự" tự động lọc chỉ hiển thị nhân viên thuộc Tổ đang hoạt động.
    - Việc chọn phòng trong giao diện Quản lý nhân sự bị giới hạn trong các phòng do Tổ đó quản lý.
- **Quy trình làm việc tinh gọn:**
    - Nút "Thêm phòng" tự động gợi ý phòng đầu tiên còn trống (chưa được chọn).
    - Các phân công phòng mới mặc định ở Bậc 4 (tay nghề cao nhất).
    - Gỡ bỏ các nút dư thừa ("Đồng bộ", "Cập nhật") để thay bằng cơ chế lưu tự động qua AJAX (`triggerRoomUpdate`).

### 4. Trực quan hóa Kỹ năng & Khả năng Tiếp cận
- **Modal xem kỹ năng:** Truy cập thông qua biểu tượng "con mắt" trong thanh bên nhân sự.
- **Truy cập thời gian thực:** Người lập lịch có thể kiểm tra ngay lập tức các phòng và bậc tay nghề của nhân viên trước khi đưa ra quyết định phân công.
- **Giao diện sạch sẽ:** Các biểu tượng con mắt đã được gỡ bỏ khỏi bảng chính để giảm rối mắt, tập trung vào thanh bên nơi chúng phục vụ như một nguồn tham khảo.

### 5. Quản lý Hồ sơ & Kinh nghiệm làm việc
- **Bộ lọc Quản trị Nâng cao:** Bổ sung bộ lọc theo "Tổ" và "Phòng" tại giao diện Quản lý Nhân sự, giúp nhanh chóng tìm kiếm đội ngũ nhân sự chuyên biệt.
- **Hệ thống Đếm giờ làm việc Thực tế:** 
    - Mỗi phòng sản xuất được gán cho nhân sự sẽ đi kèm với một thẻ "Chỉ số kinh nghiệm".
    - **Định mức năm:** Tổng số giờ nhân sự đã thực tế làm việc tại phòng đó trong năm hiện hành.
    - **Tổng thời gian:** Tổng số giờ tích lũy từ trước đến nay nhân sự đã làm việc tại phòng đó.
- **Ý nghĩa Profile:** Giúp nhà quản lý đánh giá chính xác độ thuần thục của nhân sự dựa trên thời lượng cọ xát thực tế, không chỉ dựa trên bậc kỹ năng lý thuyết.

## Chi tiết Triển khai Kỹ thuật

### Cấu trúc Cơ sở dữ liệu
- `employee_rooms`: Lưu `employees_id`, `room_id`, và `level`.
- `employee_groups`: Liên kết nhân viên với các nhóm sản xuất (`stage_groups`).

### Các Hàm Quan trọng (dataTable.blade.php)
- `checkRoomAuthorization(personId, roomId, callback)`: Xác thực quyền hạn dựa trên tay nghề.
- `checkShiftMismatch(personId, targetShiftCode, callback)`: Xác thực sự phù hợp của ca làm việc.
- `renderSidebarData(data, day, query)`: Xử lý hiển thị danh sách nhân sự và các chỉ số trạng thái nghỉ phép.
- `triggerRoomUpdate()`: Xử lý AJAX cốt lõi để lưu định mức tay nghề theo phòng.

## Hướng dẫn Sử dụng
1. **Thêm Kỹ năng:** Sử dụng giao diện Quản lý Nhân sự để gán phòng và bậc tay nghề cho nhân viên.
2. **Lập lịch:** Chọn Tổ sản xuất trước để lọc danh sách nhân sự và phòng liên quan.
3. **Xác nhận:** Sử dụng biểu tượng con mắt ở thanh bên để kiểm tra tay nghề nhân viên trước khi kéo họ vào ca làm việc.
4. **Xử lý Lỗi:** Chú ý đến các cảnh báo màu đỏ "Không được phép" hoặc "Nghỉ phép" để ngăn chặn việc nhập dữ liệu sai.

### 6. Ánh xạ Dữ liệu API Bên ngoài (S-WebDev)
- **Endpoint:** `http://s-webdev:5070/api/shifts/by-department`
- **Mục đích:** Đồng bộ lịch trực (shifts) của nhân sự từ hệ thống quản lý nhân sự tập trung.
- **Bảng ánh xạ mã Bộ phận (Department Mapping):**
    - `EN` (Kỹ thuật): **3**
    - `PXTN` (Phân xưởng Thực nghiệm): **6**
    - `PXV1` (Phân xưởng Viên 1): **15**
    - `PXVH` (Phân xưởng Viên H): **29**
    - `PXDN` (Phân xưởng Đa năng): **30**
    - `PXV2` (Phân xưởng Viên 2): **31**
    - *Ghi chú:* Các mã ID này được sử dụng làm tham số `department` trong URL API để lấy đúng dữ liệu ca trực của từng đơn vị.

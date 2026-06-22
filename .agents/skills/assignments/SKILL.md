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
    - Nút "Tự động phân công": Thực hiện thuật toán chia bài thông minh dựa trên kỹ năng.
    - Nút "Xem báo cáo": Mở dashboard quét tình hình nhân sự thời gian thực (nhu cầu vs thực tế).
    - Nút "Thêm phòng": Tự động gợi ý phòng đầu tiên còn trống (chưa được chọn).
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
    - `EN` (Kỹ Thuật): **3**
    - `PXTN` (Phân xưởng Thuốc Nước): **6**
    - `PXV1` (Phân xưởng Viên 1): **15**
    - `WH` (Kho): **17**
    - `PXVH` (Phân xưởng Viên H): **30**
    - `PXDN` (Phân xưởng Dùng Ngoài): **34**
    - `PXV2` (Phân xưởng Viên 2): **32**
    - *Ghi chú:* Các mã ID này được sử dụng làm tham số `department` trong URL API để lấy đúng dữ liệu ca trực của từng đơn vị.
- **Quy tắc Ghép nối Dữ liệu Thời gian (Date Mapping Rule):**
    - Khoảng thời gian chấm công/đi ca của hệ thống bắt đầu từ ngày 21 tháng trước đến ngày 20 tháng hiện tại.
    - Khi truy vấn lịch trực cho một tháng `M` (ví dụ: `M = 6`), dữ liệu sẽ được lấy và ghép nối từ hai nguồn API:
        - **Ngày 01 -> 20:** Lấy từ API theo tháng hiện tại `month = M` (ví dụ: `month = 6`).
        - **Ngày 21 -> 31:** Lấy từ API theo tháng tiếp theo `month = M + 1` (ví dụ: `month = 7`). (Hệ thống sẽ tự động tăng lên `month = 1` của năm `year + 1` nếu `M = 12`).

### 7.1 Tự động Phân công Nhân sự Sản Xuất (Auto Assign)
- **Chỉ định theo ca:** Nhân sự được tự động lấy từ danh sách lịch trực (sidebar) và gom vào các nhóm (pool) tương ứng với từng ca làm việc (C1, C2, HC...). Những nhân sự đang nghỉ phép (P) sẽ bị loại bỏ.
- **Tuân thủ Số lượng & Quyền hạn:**
    - Thuật toán đọc số lượng "Nhân sự cần thiết" của mỗi phòng để xác định số lượng người cần thiết phải xếp.
    - Hệ thống kiểm tra nghiêm ngặt bảng định mức tay nghề: Nếu nhân sự không có quyền làm việc tại phòng đó (`level` chưa được cấp) sẽ tự động bị loại khỏi danh sách cân nhắc cho phòng đó.
- **Ưu tiên Tay nghề & Chia đều (Round-Robin):**
    - **Ưu tiên:** Người có bậc kỹ năng (`level`) cao nhất đối với một phòng sẽ được ưu tiên chọn trước cho phòng đó.
    - **Công bằng:** Thuật toán áp dụng cơ chế chia bài (Round-robin), rải nhân sự lần lượt từng người một cho tất cả các phòng có nhu cầu trong ca. Sau khi mỗi phòng đã có 1 người, thuật toán mới quay lại xếp người thứ 2... Điều này ngăn chặn tình trạng một phòng giành hết nhân sự trong khi phòng khác trống rỗng.
- **Tối ưu Hiệu suất (Chống treo):** Tích hợp chốt chặn an toàn giúp hệ thống lập tức thoát khỏi vòng lặp tính toán nếu không còn nhân sự phù hợp (bậc tay nghề chưa đạt), đảm bảo tốc độ phản hồi tức thì ngay trên Client-side.
    - **Tự động báo cáo:** Sau khi chạy xong, hệ thống tự động bật Dashboard tổng kết kết quả.


### 7.2 Tự động Phân công Nhân sự BT-HC (Auto Assign)
- **Chỉ định theo ca:** Nhân sự được tự động lấy từ danh sách lịch trực (sidebar) và gom vào các nhóm (pool) tương ứng với các khoảng thời gian làm việc của công việc và thời gian của ca làm việc. Những nhân sự đang nghỉ phép (P) sẽ bị loại bỏ.
- **Tuân thủ Số lượng & Quyền hạn:**
    - Thuật toán đọc số lượng "Nhân sự cần thiết" của mỗi phòng để xác định số lượng người cần thiết phải xếp.
    - Không cần kiểm tra định mức tay nghề và phòng sản xuất liên quan, chỉ cần đảm bảo số lượng và không có người nghỉ phép.
    - **Công bằng:** Thuật toán áp dụng cơ chế chia bài (Round-robin), rải nhân sự lần lượt từng người một cho tất cả các phòng có nhu cầu trong ca. Sau khi mỗi phòng đã có 1 người, thuật toán mới quay lại xếp người thứ 2... Điều này ngăn chặn tình trạng một phòng giành hết nhân sự trong khi phòng khác trống rỗng.

    - **Tự động báo cáo:** Sau khi chạy xong, hệ thống tự động bật Dashboard tổng kết kết quả.

### 8. Dashboard Báo cáo & Giám sát (Reporting)
- **Truy cập:** Thông qua nút "Xem báo cáo" hoặc tự động hiện ra sau khi chạy "Tự động phân công".
- **Cơ chế Quét thời gian thực:** Mỗi khi mở báo cáo, hệ thống quét toàn bộ UI để đọc lại: Số lượng yêu cầu, số người đã được chọn vào ô, và số người còn dư trong ca trực.
- **Các chỉ số Dashboard:**
    - **Thống kê tổng:** Tổng yêu cầu, Đã đáp ứng, Còn thiếu, Tỷ lệ đáp ứng (%).
    - **Chi tiết theo ca:** Bảng so sánh Yêu cầu vs Thực tế và số lượng **Nhân sự rảnh rỗi** (chưa được phân vào bất kỳ phòng nào).
    - **Danh sách thiếu hụt:** Liệt kê đích danh các phòng và ca đang bị thiếu người để quản lý can thiệp thủ công.

### 9. Ràng buộc Nhân sự Đơn nhất (Personnel Constraints)
- **Nguyên tắc:** Một nhân sự tại một thời điểm chỉ có thể hoạt động tại duy nhất 01 Phân xưởng và 01 Tổ.
- **Phân xưởng (Workshops):**
    - Hệ thống áp dụng cơ chế loại trừ (Mutually Exclusive). Kích hoạt 01 PX tạm thời sẽ tự động vô hiệu hóa PX trực thuộc và các PX tạm thời khác.
    - Khi vô hiệu hóa PX tạm thời đang hoạt động, hệ thống tự động kích hoạt lại PX trực thuộc làm mặc định.
- **Tổ (Groups):** Kích hoạt 01 Tổ mới sẽ tự động vô hiệu hóa các Tổ khác mà nhân viên đang tham gia.
- **Lợi ích:** Đảm bảo dữ liệu nhân sự luôn duy nhất, không bị đếm trùng khi điều phối nguồn lực liên phân xưởng hoặc liên tổ.

### 10. Quy tắc Tải Lịch Lý Thuyết BT-HC (Theoretical Schedule Rules)
Hệ thống tải lịch lý thuyết (`stage_plan`) dựa trên mã Tổ (`group_code`), Mã công đoạn (`stage_plan.code`) và Bộ phận của phòng (`room.deparment_code`):

| STT | Tổ (Group Name) | Group Code | Mã Công Việc (Rule) | Bộ phận (Zone) |
| 1 | Kỹ Thuật Bảo Trì | 11 + 12 + 14 + 15 + 16 | `code` like `%TB%`, `%BT%`, `%\_8` , `%TI%` | `PXV1`, `PXTN`, `PXV2`, `PXDN` , `PXVH` |
| 2 | Tổ Bảo Trì 1 | 11 | `code` like `%TB%`, `%BT%`, `%\_8` | `PXV1`, `PXTN` |
| 3 | Tổ Điện Lạnh - Nước tinh khiết B1 | 12 | `code` like `%TI%` | `PXV1`, `PXTN` |
| 4 | Tổ Điện Lạnh - Nước tinh khiết B2 | 14 | `code` like `%TI%` | `PXV2`, `PXDN` , `PXVH` |
| 5 | Tổ Bảo Trì (PXV2-PXDN) | 15 | `code` like `%TB%`, `%BT%`, `%\_8` | `PXV2`, `PXDN` |
| 6 | Tổ Bảo Trì (PXVH) | 16 | `code` like `%TB%`, `%BT%`, `%\_8` | `PXVH` |

| 7 | Tổ Hiệu chuẩn QA | 20 | `code` like `%HC%` | Tất cả |

*Ghi chú:* 
- Đối với Tổ 14, quy tắc áp dụng tương tự Tổ 12/13 nhưng phạm vi mở rộng sang PXV2, PXDN, PXVH.
- Đối với Tổ 18 (Hiệu chuẩn), chỉ quan tâm mã công việc có chứa `HC`.
- Đối với Kỹ Thuật Bảo Trì, các nút thêm/edit/lưu sẽ disabled (read only) vì đây là trang chỉ xem. Chỉ lọc và hiển thị các lịch phân công đã được lưu có phân công nhân sự.

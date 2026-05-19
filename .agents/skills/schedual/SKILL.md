---
name: Lập Lịch Sản Xuất, Hiệu Chuẩn & Bảo Trì (Schedual)
description: Các tính năng, logic ưu tiên và quy tắc cốt lõi của tính năng sắp lịch Sản Xuất, Hiệu Chuẩn và Bảo Trì.
---

# Kỹ Năng Sắp Lịch Sản Xuất & Bảo Trì (Schedual)

Skill này chứa các thông tin tổng hợp về nghiệp vụ, quy tắc ưu tiên và logic lập lịch để tham chiếu khi bảo trì code, đặc biệt tại `SchedualController.php` và `FullCalender.jsx`.

## 1. Mức Độ Ưu Tiên Màu Sắc (Từ Thấp Đến Cao)
Hệ thống sử dụng nhiều logic kiểm tra vi phạm (validation) để cảnh báo người dùng. Khi một sự kiện vi phạm nhiều lỗi cùng lúc, màu nền sẽ lấy theo lỗi có mức ưu tiên cao nhất, các lỗi còn lại sẽ được hiển thị thành các dải màu dọc (violation bars) chạy song song ở cạnh phải.

Thứ tự kiểm tra trong code (phía dưới sẽ ghi đè phía trên để tạo mức ưu tiên cao nhất):
1. **Vệ sinh (Clearning)** - `#e4e405e2` (Vàng tươi): Cảnh báo vi phạm thời gian vệ sinh giữa các lô. Chữ đổi sang đỏ (`#fb0101e2`).
2. **Vi phạm thời gian biệt trữ** - `#bda124ff` (Cam/Nâu): Thời gian chờ giữa 2 công đoạn (ví dụ chờ sấy, chờ kiểm nghiệm) vượt mức cho phép. Chữ đổi sang trắng (`#ffffff`).
3. **Hạn cần hàng (Expected Date)** - `#e54a4aff` (Đỏ nhạt): Lịch dự kiến kết thúc trễ hơn hạn giao hàng hoặc ngày xuất xưởng KCS. Chữ đổi sang trắng (`#ffffff`) để tăng tính tương phản.
4. **Liên kết công đoạn trước sau (Predecessor / Successor)** - `#4d4b4bff` (Xám đen): Lô công đoạn sau bắt đầu trước khi lô công đoạn trước kết thúc (lỗi gối đầu). Chữ đổi sang trắng (`#ffffff`).
5. **Nguyên liệu / Bao bì (Critical Checks)** - `#920000ff` (Đỏ sẫm): Vi phạm nghiêm trọng nhất (chưa đủ nguyên liệu, hết hạn nguyên liệu chính, chưa có bao bì, v.v). Chữ đổi sang trắng (`#ffffff`).

## 2. Các Tính Năng Giao Diện (FullCalendar)
* **Dải màu dọc song song**: Ở `FullCalender.jsx`, các mã màu lỗi còn dư (sau khi đã trừ đi màu nền chính) sẽ được vẽ thành các thanh `div` rộng `4px`, sắp xếp theo chiều ngang (`flex-direction: row`) tại mép phải của hộp sự kiện (`right: 0`, `top: 0`, `bottom: 0`), kèm theo hiệu ứng `box-shadow` để dễ nhận biết. Thiết kế này giúp người quản lý nhìn thấy ngay có bao nhiêu lỗi phụ đang tồn tại song song với lỗi chính.
* **Không che khuất Badge**: Chấm tròn (badge submit) nằm ở góc phải (`top: 2px; right: 2px`) có `z-index: 10`, trong khi thanh vi phạm có `z-index: 1`, đảm bảo thanh cảnh báo nằm trọn 100% chiều cao mà không che khuất badge.

## 3. Logic "Di Chuyển Cả Chiến Dịch" (Campaign Cascade)
Khi người dùng sửa đổi lịch của 1 lô thuộc một "chiến dịch" (Campaign) và tích chọn **"Di chuyển cả chiến dịch"**:
* Backend lấy toàn bộ lô thuộc campaign đó, sắp xếp theo thời gian (`start`).
* Lô được sửa sẽ nhận thời gian mới.
* Các lô tiếp theo sẽ tự động lùi/tiến nối tiếp liên tục (back-to-back) ngay sau lô trước đó, cộng dồn với thời lượng sản xuất và thời gian vệ sinh (nếu có). Tránh tình trạng các lô trong chiến dịch bị dồn cục vào cùng một thời điểm.

## 4. Bảo Trì (BT), Hiệu Chuẩn (HC), Tiện Ích (TI)
* Mã công đoạn (`stage_code`) cho toàn bộ nhóm này là `8`.
* Phân loại màu sắc nhận diện: 
  * Bảo trì (mặc định): `#003A4F`
  * Hiệu chuẩn (kết thúc bằng `_HC`): `#9a1b72ff` (Tím đậm)
  * Tiện ích (kết thúc bằng `_TI`): `#830cbfff`
* Cảnh báo tới hạn/quá hạn: Viền cảnh báo được tính toán dưa trên `expected_date` hoặc `min_due` từ backend.

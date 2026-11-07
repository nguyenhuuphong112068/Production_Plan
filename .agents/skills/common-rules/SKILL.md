---
name: Các Quy Tắc Chung (Common Rules)
description: Quy tắc chung của dự án PMS - đặt tên file, xử lý file tạm, cấu trúc module, phân biệt KH sản xuất / bảo trì, và BẮT BUỘC cập nhật Hướng Dẫn Sử Dụng tích hợp (userGuide) mỗi khi sửa bất kỳ chức năng nào người dùng thấy được (mục 5). Đọc trước khi sửa mọi chức năng.
---

# Quy Tắc Chung (Common Rules)

## 1. Xử Lý File Tạm (Temporary Files)
- Trong quá trình agent sử dụng terminal/Powershell để thực hiện các kịch bản dài, đôi khi cần dùng file tạm (scratch files) để tránh lỗi cú pháp (Syntax errors khi escape chuỗi).
- **Quy tắc:** Các file tạm này KHÔNG được phép để lại trong thư mục gốc của dự án. 
- Agent phải lưu các file dạng đệm vào thư mục riêng biệt hoặc phải sử dụng lệnh để **XÓA SẠCH** (ví dụ: `temp_script.js`, `temp_chk.txt`) ngay sau khi nhiệm vụ hoàn thành.
- Tên các file tạm phải chứa tiền tố `_agent_temp_` để dễ dàng nhận biết và dọn dẹp.

## 2. Quy Tắc Đặt Tên (Naming Convention)
- Các file chứa logic chuyên biệt, tái sử dụng được thì nên đặt trong `resources/js/components` hoặc các thư mục module tương ứng, không được lưu ở thư mục gốc.
- Đặt tên file theo chuẩn CamelCase hoặc PascalCase tùy thuộc vào framework (VD: React component phải là PascalCase `MyComponent.jsx`).
- File nên có tên gợi nhớ chức năng cốt lõi của nó, ví dụ `SmartRippleShift.jsx` thay vì `temp_script.js`.

## 3. Kiến Trúc Modular
- Tránh viết các hàm helper quá dài trực tiếp vào bên trong các Component giao diện chính (như `FullCalender.jsx`).
- Nếu hàm helper có thể độc lập, hãy tách nó ra các file `utils.js` hoặc custom hooks.

## 4. Ph�n Bi?t K? Ho?ch S?n Xu?t & B?o Tr� trong plan_master
- B?ng plan_master luu chung c? S?n xu?t v� B?o tr�, s? d?ng chung c?t product_caterogy_id d? li�n k?t d?n hai b?ng kh�c nhau (finished_product_category v� quota_maintenance).
- Tr�nh join tr?c ti?p finished_product_category ho?c quota_maintenance ch? v?i product_caterogy_id v� s? g�y tr�ng l?p (d�nh d? li?u ch�o).
- **C�ch ph�n bi?t an to�n:** Join th�m b?ng plan_list th�ng qua plan_list_id v� ki?m tra c?t plan_list.type.
  - plan_list.type = 1: K? ho?ch S?n xu?t (join v?i finished_product_category)
  - plan_list.type = 0: K? ho?ch B?o tr� (join v?i quota_maintenance)

## 5. BẮT BUỘC: Cập Nhật Hướng Dẫn Sử Dụng Khi Sửa Chức Năng
- Người dùng yêu cầu từ 05/11/2026: **mọi thay đổi chức năng người dùng thấy được** (thêm / bỏ / đổi nút, ô nhập, cột, màu, quyền, luồng thao tác, quy tắc nghiệp vụ, nguyên lý sắp lịch...) thì trong **CÙNG lượt làm việc** phải cập nhật Hướng Dẫn Sử Dụng tích hợp trong phần mềm (menu "Hướng Dẫn Sử Dụng", `resources/views/pages/userGuide/<khoá>/`). Cách làm: skill `user-guide` (`.agents/skills/user-guide/SKILL.md`).
- Các bước:
  1. Tìm mục hướng dẫn mô tả chức năng vừa sửa: grep tên màn hình / route / file đã sửa trong `resources/views/pages/userGuide/*/toc.php` (khoá `route`, `sources`).
  2. Sửa đúng file `sections/<file>.blade.php` theo code mới (nhãn đúng như màn hình, tiếng Việt cho người dùng cuối, không tên bảng / hàm). Chức năng mới chưa có mục thì thêm mục vào `toc.php` (khai `route`, `sources`).
  3. Đổi `updated_at` của tài liệu trong `UserGuideController::GUIDES` thành ngày hôm nay.
  3b. **Đổi quyền / điều kiện** (thêm quyền mới vào bảng `permissions`, đổi quyền kiểm tra ở nút / menu, thêm kiểm tra theo tên nhóm quyền / phòng ban / phân xưởng): sửa `app/Support/GuidePermissions.php` (`MAP` theo khoá tài liệu; quyền bỏ dùng chuyển vào `UNUSED`). Mục "Phân Quyền" của mọi tài liệu và ma trận ở tài liệu `phanQuyen` đọc từ đây.
  4. Chạy `php artisan pms:user-guide-check`: phải 0 lỗi, và không còn cảnh báo "sửa sau hướng dẫn" của các mục liên quan tới thay đổi của mình.
- Đổi **nguyên lý sắp lịch tự động / Kiểm soát tồn BTP**: ngoài các bước trên còn phải làm mục 0 của skill `schedual` (skill + phiên bản tài liệu mới trong `storage/app/schedualer`); phần Nguyên lý trong hướng dẫn (`userGuide/schedual`) phải khớp bản tài liệu mới nhất.
- Chỉ sửa lỗi nội bộ mà người dùng không thấy khác gì thì không cần sửa hướng dẫn, nhưng phải nói rõ điều đó khi báo cáo.
- Khi báo cáo xong việc, liệt kê mục hướng dẫn đã sửa (hoặc lý do không cần sửa).

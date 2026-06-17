---
name: Các Quy Tắc Chung (Common Rules)
description: Hướng dẫn về cách thức đặt tên file, xử lý file tạm và cấu trúc module.
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

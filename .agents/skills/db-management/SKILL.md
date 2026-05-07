---
name: db-management
description: Hướng dẫn và công cụ để quản lý cơ sở dữ liệu MySQL/MariaDB trong môi trường XAMPP, bao gồm import, export và sửa lỗi collation.
---

# Quản lý Cơ sở dữ liệu & Import XAMPP

Skill này giúp thực hiện các thao tác quản lý database trong môi trường Windows/XAMPP, đặc biệt là xử lý các lỗi tương thích khi import file SQL từ các phiên bản MySQL khác nhau.

## Các thao tác chính

### 1. Import Database vào XAMPP
Khi import file SQL lớn (như file 110MB), nên sử dụng command line thay vì phpMyAdmin để tránh lỗi timeout.

**Lệnh thực hiện (trong CMD):**
```cmd
"C:\xampp\mysql\bin\mysql.exe" -u [username] -p [database_name] < [file_name].sql
```
*Lưu ý: Nếu không có mật khẩu, bỏ qua phần `-p`.*

### 2. Xử lý lỗi Collation (`utf8mb4_0900_ai_ci`)
Nếu gặp lỗi `Unknown collation: 'utf8mb4_0900_ai_ci'` khi import vào MariaDB, cần chuyển đổi collation trong file SQL về `utf8mb4_unicode_ci`.

**Cách thực hiện:** Sử dụng script PHP `scripts/fix_collation.php` trong thư mục skill này để xử lý file SQL lớn mà không làm treo máy.

### 3. Quy trình Import an toàn
1. Kiểm tra database đích đã tồn tại chưa.
2. Nếu muốn ghi đè, thực hiện lệnh:
   ```cmd
   "C:\xampp\mysql\bin\mysql.exe" -u root -e "DROP DATABASE [name]; CREATE DATABASE [name];"
   ```
3. Chạy script fix collation nếu cần.
4. Thực hiện lệnh import qua `cmd /c`.

## Các kịch bản hỗ trợ
- `scripts/fix_collation.php`: Tự động thay thế các collation không tương thích trong file SQL lớn bằng cách đọc từng dòng (stream), tiết kiệm bộ nhớ.

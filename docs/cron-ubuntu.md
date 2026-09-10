# Cài lịch chạy nền trên server production (Ubuntu)

Mọi tác vụ định kỳ của hệ thống được khai báo trong [`routes/console.php`](../routes/console.php).
Laravel **không tự chạy** chúng — phải có cron của hệ điều hành gọi `schedule:run`
mỗi phút, rồi Laravel mới quyết định phút đó tới lượt lệnh nào.

> **Sự cố tham chiếu (18/08 → 10/09/2026).** Cron này không tồn tại trên máy chạy.
> Hậu quả: bảng `employees` đứng yên 3 tuần. Sidebar *Tình Hình Nhân Sự* vẫn đúng
> vì nó đọc thẳng API eO2, còn Dashboard và trang định mức đọc từ DB nên thiếu
> người mới (VD nhân sự 23220). Không ai phát hiện vì lượt đồng bộ **không chạy**
> thì cũng **không để lại log**. Đó là lý do có mục "Kiểm tra" ở cuối tài liệu này.

## 1. Cài cron

Chạy dưới đúng user sở hữu source (thường là `www-data` hoặc user deploy) — nếu
chạy bằng `root` thì file log và cache sẽ sai quyền, lần sau web ghi vào không được:

```bash
sudo -u www-data crontab -e
```

Thêm đúng một dòng (sửa lại đường dẫn cho khớp server):

```cron
* * * * * cd /var/www/production_plan && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Kiểm tra đã vào chưa:

```bash
sudo -u www-data crontab -l
```

Dòng này chạy mỗi phút nhưng **rất nhẹ**: hầu hết các phút Laravel chỉ đọc bảng
lịch rồi thoát ngay. Đây là cách cài chuẩn của Laravel, và nó kích hoạt **toàn
bộ** tác vụ trong `routes/console.php`, không riêng đồng bộ nhân sự:

| Giờ | Lệnh | Việc |
|---|---|---|
| 00:00 | `shifts:warm-cache --months=2` | Nạp cache lịch trực tháng kế tiếp |
| 05:00 | `employees:sync-roster` | **Đồng bộ nhân sự** (xem mục 2) |
| 05:55 / 12:00 / 16:00 | `shifts:warm-cache` | Giữ cache lịch trực luôn nóng |
| 06:00 | `wip:snapshot-coverage` | Chốt tồn bán thành phẩm đầu ngày công |
| 08:00 | `notify:unscheduled-batches`, `notify:validation-sampling` | Cảnh báo |

## 2. Lệnh đồng bộ nhân sự

```
php artisan employees:sync-roster
```

Chạy tuần tự 7 bộ phận (`EN, QA, PXTN, PXV1, PXVH, PXDN, PXV2`), mất khoảng
2–4 phút vì máy chủ eO2 trả chậm (~9.5s cho PXTN đến ~88s cho PXV1). Cố ý
**không chạy song song**: eO2 có rate limit, dồn request sẽ bị trả HTTP 429.

Nó chỉ ghi vào **2 bảng nghiệp vụ**:

- **`employees`** — thêm người mới, cập nhật tên, đặt `active`/`resign`.
- **`employee_assignments`** — tạo phân công gốc (`is_main = 1`) cho người mới,
  đặt `active = 0` cho người không còn trong roster eO2.

Ngoài ra có ghi bảng hạ tầng `cache` (vì `CACHE_STORE=database`) cho 3 khoá
`shiftapi:roster:*`, `shiftapi:quota:*`, `employee_sync_last_run:*`. Không đụng
`assignments`, `assignment_personnel` hay bất kỳ bảng nào khác.

### Chạy tay khi cần gấp

```bash
# Một bộ phận
php artisan employees:sync-roster --department=PXDN

# Tất cả
php artisan employees:sync-roster
```

### Hai điều cần biết

**Người mới vào tổ `-`.** Bản ghi phân công được tạo với `group_id = 0`, nên trên
Dashboard người mới nằm ở nhóm `-` cho tới khi được xếp tổ thủ công. Đây là hành
vi cố ý: hệ thống không đoán tổ thay người quản lý.

**Lượt đồng bộ có thể bị bỏ qua, và như vậy là đúng.** Nếu eO2 lỗi hoặc trả danh
sách rỗng, lệnh bỏ hẳn lượt đó thay vì ghi đè. Không có bảo vệ này thì một cú
timeout sẽ vô hiệu hoá sạch nhân sự của cả phân xưởng. Bộ phận `QA` được miễn
khỏi logic vô hiệu hoá vì có nhân sự quản lý thủ công ngoài eO2.

## 3. Kiểm tra cron còn sống

Lượt 05:00 ghi kết quả vào `storage/logs/sync-roster.log`:

```bash
tail -20 storage/logs/sync-roster.log
```

Mong đợi thấy dòng của sáng nay:

```
Đang đồng bộ PXDN ...
  PXDN: 69 nhân sự (12.4s)
Xong: 7 thành công, 0 không đồng bộ được.
```

**Không thấy dòng của hôm nay ⇒ cron đã chết.** Kiểm tra theo thứ tự:

```bash
sudo -u www-data crontab -l           # dòng schedule:run còn không?
systemctl status cron                 # dịch vụ cron còn chạy không?
grep CRON /var/log/syslog | tail -20  # cron có thực sự gọi lệnh không?
php artisan schedule:list             # Laravel có thấy lịch không?
```

Một dấu hiệu gián tiếp nữa trong `storage/logs/laravel.log` — nếu thấy dòng này
lặp lại hằng ngày thì đồng bộ nền đang không chạy:

```
WARNING: Cache danh sach nhan su rong - hay kiem tra scheduler co chay 'employees:sync-roster' khong
```

## 4. Quyền ghi

Cron chạy khác user với web sẽ sinh lỗi `Permission denied` khi ghi log/cache:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## 5. Timeout cho nút "Đồng bộ dữ liệu e-o"

Nút này trên Dashboard làm cùng việc với lệnh 05:00 nhưng **chỉ cho một phân
xưởng đang chọn**, và chạy đồng bộ trong một HTTP request. Một lượt PXV1 có thể
mất tới ~90 giây (6 request lịch trực + 2 request danh sách nhân sự).

`DashBoardController::warmCache()` đã gọi `set_time_limit(300)`, nhưng **cái đó
chỉ điều khiển PHP**. Trên Ubuntu production còn hai tầng nữa cắt sớm hơn nhiều:

```nginx
# /etc/nginx/sites-available/<site>  — trong block location ~ \.php$
fastcgi_read_timeout 300;
```

```ini
; /etc/php/8.2/fpm/pool.d/www.conf
request_terminate_timeout = 300
```

Mặc định `fastcgi_read_timeout` của nginx là **60s** — không sửa thì nút sẽ trả
**504 Gateway Timeout** với PXV1, trong khi trên máy dev XAMPP (Apache + mod_php)
lại chạy tốt. Đây là kiểu lỗi chỉ xuất hiện trên production.

Sau khi sửa:

```bash
sudo nginx -t && sudo systemctl reload nginx
sudo systemctl reload php8.2-fpm
```

Không muốn nới timeout thì cứ để lịch 05:00 lo, và chỉ dùng nút cho các phân
xưởng nhỏ (PXDN, PXTN ~15s). Lệnh chạy tay qua SSH thì không vướng giới hạn này.

## 6. Sau khi sửa `routes/console.php`

Cron không cần cài lại, nhưng phải xoá cache config nếu server có
`php artisan config:cache`:

```bash
php artisan config:clear && php artisan config:cache
php artisan schedule:list   # xác nhận lịch mới đã có hiệu lực
```

---
name: Hướng Dẫn Sử Dụng tích hợp (User Guide)
description: Viết / bổ sung / cập nhật trang Hướng Dẫn Sử Dụng tích hợp trong PMS (menu "Hướng Dẫn Sử Dụng" trên leftNAV, route /userGuide/{khoá}, nút "?" trên topNAV). Dùng khi người dùng muốn viết hướng dẫn, help, manual cho một nhóm menu hoặc một màn hình, và BẮT BUỘC dùng sau mọi thay đổi chức năng người dùng thấy được (quy tắc 5 của common-rules) - sửa section tương ứng rồi chạy php artisan pms:user-guide-check. Gồm kiến trúc file, khuôn toc.php, khuôn nội dung, cách đọc mã để viết đúng nghiệp vụ, cách render và chụp kiểm tra.
---

# Hướng Dẫn Sử Dụng tích hợp trong PMS

Dựng theo khuôn của dự án WMS (`C:\WMS\.claude\skills\wms-user-guide`), đã chỉnh cho PMS (quyền theo `user_has_permission`
và `userGroup`, topNAV fixed-top, không có biến CSS toàn cục). Nội dung lưu bằng Blade trong git, **không lưu CSDL**.

## 1. Kiến trúc

| Thành phần | Đường dẫn |
|---|---|
| Route | `routes/userGuideRoute.php` (require trong `routes/web.php`) → `pages.userGuide.list` (`/userGuide`), `pages.userGuide.show` (`/userGuide/{guide}`), middleware `CheckLogin`, không cần quyền riêng |
| Controller | `app/Http/Controllers/Pages/UserGuide/UserGuideController.php`, hằng `GUIDES` = danh sách tài liệu (`title`, `icon`, `summary`, `updated_at`) |
| Mục lục + nút "?" | `app/Support/UserGuideLinks.php`: đọc mọi `toc.php`; topNAV dẫn route hiện tại tới mục có cùng `route` / `helpRoutes`, chọn theo tab (`pane`), không khớp thì thử `<nhóm>.list`, cuối cùng mở danh sách |
| Trang danh sách | `resources/views/pages/userGuide/list.blade.php` |
| Dùng chung | `resources/views/pages/userGuide/shared/`: `assets` (CSS/JS: mục lục sticky, scrollspy, lọc không dấu, in, bảng màu `.ug-page`), `page` (khung), `sectionHead` (đầu mục + nút "Mở màn hình"), `fields` (bảng ô nhập), `shot` (ảnh chụp, bấm phóng to) |
| Một tài liệu | `resources/views/pages/userGuide/<khoá>/toc.php` + `sections/<file>.blade.php`. Khung trang dùng chung `shared/guide.blade.php`; chỉ tạo `<khoá>/index.blade.php` khi cần khung riêng |
| Menu | leftNAV, nhóm "Hướng Dẫn Sử Dụng" cuối menu, tự sinh mục con từ `GUIDES` |
| Lệnh kiểm tra | `php artisan pms:user-guide-check` (`--all` in thêm các màn hình trên menu chưa có hướng dẫn) |
| Ảnh | `public/img/userGuide/<khoá>/` |

Tài liệu hiện có - **mỗi mục lớn của leftNAV là một tài liệu**, thứ tự `GUIDES` = thứ tự trên menu:

| Khoá | Mục lớn trên leftNAV | Ghi chú |
|---|---|---|
| `batDau` | (Trang chủ, chọn phân xưởng, đăng nhập) | Đăng nhập, mật khẩu (`config/security.php`), màn hình công khai, thanh trên cùng, bảng các nhóm menu, thao tác chung trên danh sách |
| `duLieuGoc` | Dữ Liệu Gốc | |
| `danhMuc` | Danh Mục | Chức năng Thêm / Cập Nhật Danh Mục Giả Định đã xoá 06/11/2026; vẫn còn xem mã giả định cũ (quyền view_Hypothesis_category) và Công thức giả định |
| `dinhMuc` | Định Mức | Có tab Sản phẩm được phép (`personnel_product`) |
| `keHoach` | Kế Hoạch | |
| `schedual` | Lịch Sản Xuất | Phần Nguyên lý phải khớp `storage/app/schedualer/nguyen-ly/<bản mới nhất>` và skill `schedual`, `wip-control`. Nhóm "Sắp Lịch Thủ Công" (khsxList, manualSchedule, manualEdit) mô tả `ModalSidebar.jsx` + `SchedualController::store()` + thao tác kéo thả trong `FullCalender.jsx` |
| `lichHcBt` | Lịch HC-BT | Route `/maintenance-calendar` đã được đặt tên `pages.schedual.maintenance_calendar.index` để nút "?" nhận ra |
| `lichCongTac` | Lịch Công Tác | Nghiệp vụ chi tiết ở skill `assignments` |
| `baoCao` | Báo Cáo | |
| `tonBtp` | Tồn BTP | |
| `lichSu` | Lịch Sử | |
| `userPolicy` | User Policy | Nhắc không đổi tên nhóm quyền Admin / Schedualer / Leader / Executor |
| `phanQuyen` | (tài liệu chung) Bảo Mật & Phân Quyền | Theo khuôn tài liệu Phân Quyền của WMS: cách tính quyền, nhóm quyền chính, nhóm quyền / phòng ban đặc biệt, cấp quyền, ma trận theo từng tài liệu (mục `bang-<khoá>` sinh từ GUIDES), 21 CFR Part 11, quyền không dùng |
| `dangNhap` | Lịch Sử Đăng Nhập | Mục "Lịch Sử Đăng Nhập" thứ hai thực chất mở Audit Trail |

Thêm màn hình mới vào leftNAV → thêm mục vào `toc.php` của tài liệu ứng với mục lớn chứa nó.
`php artisan pms:user-guide-check --all` liệt kê màn hình trên menu (bỏ phần đã comment ẩn) chưa có mục hướng dẫn - phải rỗng.

### Phân quyền trong hướng dẫn
- Xem hướng dẫn cần quyền `user_guide_view` (`UserGuideController::PERMISSION` / `canView()`): chặn menu leftNAV, nút "?" topNAV và route /userGuide (abort 403). Script render thử hướng dẫn phải cấp quyền này trong transaction rồi rollback, và chạy mỗi trường hợp ở tiến trình riêng (`user_permission_names()` cache theo request).
- `app/Support/GuidePermissions.php` là nguồn duy nhất: `MAP[khoá tài liệu] = [[màn hình, thao tác, ['perm' => tên quyền] | ['rule' => mô tả, 'roles' => [...]]], ...]`, `UNUSED` = quyền còn trong bảng nhưng mã không kiểm tra.
- Mỗi tài liệu chức năng có mục `phan-quyen` (sections/permissions.blade.php → `shared/permissionSection` → `shared/permissionTable`); tài liệu `phanQuyen` có ma trận `shared/permissionMatrix`. Bảng đọc CSDL lúc mở trang (nhóm quyền active + quyền riêng user_permission).
- `pms:user-guide-check` báo lỗi khi quyền trong MAP không còn trong bảng `permissions`, cảnh báo khi quyền trong bảng chưa khai ở MAP / UNUSED.
- **Nhóm quyền chính** (`user_management.userGroup`, các chỗ so sánh cứng 'Admin' / 'Schedualer'...) = nhóm đứng trên cùng trong danh sách roles trong các nhóm được chọn (thẻ `<select multiple>` gửi theo thứ tự DOM, không theo thứ tự bấm). Viết hướng dẫn phải nói đúng điều này.
- Không viết vào hướng dẫn chuyện quyền "thấy menu" chỉ ẩn menu mà route không chặn (hướng dẫn ai đăng nhập cũng đọc).

## 2. Khuôn `toc.php`

```php
return [
    'Tên nhóm' => [
        ['id' => 'neo-khong-dau', 'file' => 'tenFile', 'title' => 'Tên Mục', 'icon' => 'fas fa-...',
            'path' => 'Menu › Màn hình',            // đường đi trên leftNAV
            'route' => 'pages.x.y.list',             // nút "Mở màn hình" + nút "?" của màn hình đó
            'perm' => 'layout_xxx',                  // giống @if trên leftNAV; hoặc 'role:Admin,Schedualer'
            'chips' => [['Admin / Schedualer', 'is-warn']],
            'sources' => ['resources/js/Pages/X.jsx', 'app/Http/Controllers/...', 'app/Plugins/X'],
            'helpRoutes' => ['pages.x.y.detail'],    // trang con cũng dẫn về mục này
            'pane' => 'idVungTab',                   // màn hình nhiều tab
            'keywords' => 'tu khoa khong dau'],
    ],
];
```

- **`sources` (đặc thù PMS)**: file / thư mục mã nguồn mà mục mô tả. `pms:user-guide-check` cảnh báo khi nguồn sửa SAU file
  section, tức chức năng đã đổi mà hướng dẫn chưa được xem lại. Màn hình React (`/Schedual`, Gantt...) không suy ra được view
  từ controller nên phải khai `sources` bằng tay.
- Nút "?" mở mục **đầu tiên** có `route` trùng màn hình đang xem: đặt mục tổng quan của màn hình lên trước.
- Luôn có nhóm "Bắt đầu" (Tổng quan, Quyền) và cuối là "Hỗ trợ › Lỗi & Câu Hỏi Thường Gặp".
- Khoá tài liệu: camelCase không dấu (`duLieuGoc`, `lichCongTac`). Tránh chuỗi khiến menu khác bung nhầm — leftNAV dùng `str_contains(url()->current(), 'plan' | 'quota' |
  'category' | 'Schedual' | 'assignment' ...)` để mở menu, URL `/userGuide/<khoá>` chứa các chuỗi đó sẽ làm nhóm menu kia bung theo.

## 3. Đọc mã để viết đúng nghiệp vụ (bắt buộc, không đoán)

1. Nhãn ô, nút, chú thích: lấy đúng chữ trên màn hình (`label`, `title`, `hint:` của nút FullCalendar, `placeholder`).
   Màn hình React: grep trong `resources/js/Pages/*.jsx`, `resources/js/Components`, `resources/js/Plugins`.
2. Quyền: điều kiện `@if (user_has_permission(..., 'xxx', 'boolean'))` trên leftNAV; quyền sửa trong React thường ở
   `setAuthorization(...)` (ví dụ Gantt: `userGroup` ∈ Admin/Schedualer và phân xưởng = phòng ban, hoặc BOD).
3. Câu khẳng định về luồng xử lý (xếp gì, lọc gì, mặc định bao nhiêu) phải truy tới code thật đang chạy, kể cả khi
   docblock / skill / giao diện nói khác. Ví dụ đã gặp: 8 ô thời gian chờ trên hộp thoại sắp lịch không có `name` nên
   không gửi lên server - hướng dẫn phải ghi "chưa có tác dụng", không được ghi như thể đổi được.
4. Văn phong: tiếng Việt có dấu, câu ngắn, nói với người dùng cuối (người lập lịch, tổ trưởng), **không** tên bảng, tên
   hàm, route, class. Thuật ngữ dùng như trên màn hình (PC, THT, ĐH, BP, ĐG, BTP, NL/BB, BT-HC-TI).

## 4. Khuôn nội dung một mục

```blade
<p>1-2 câu: màn hình dùng để làm gì.</p>
<h3>Các ô nhập</h3>
@include('pages.userGuide.shared.fields', ['fields' => [
    ['Nhãn ô đúng như màn hình', true, 'Cách nhập, giới hạn. Được dùng <b>, <a href="#neo">, <span class="ug-key">'],
]])
<ol class="ug-steps"><li>Bước...</li></ol>
<div class="ug-callout | is-warn | is-danger"><i class="fas fa-info-circle"></i><div>Lưu ý</div></div>
<div class="ug-example"><b>Ví dụ</b> ...</div>
<div class="ug-flow"><div class="ug-flow-step"><span class="ug-flow-no">1</span><b>Tiêu đề ô</b> mô tả</div></div>
<figure class="ug-figure"><img src="{{ asset('img/userGuide/<khoá>/x.png') }}" alt=""><figcaption>...</figcaption></figure>
```

- Nút minh hoạ: đúng class Bootstrap của nút thật + `ug-ui`; nút emoji của Gantt: `<span class="ug-emoji">🤖</span>`.
- Hộp màu: `<span class="ug-swatch" style="background:#..."></span>` (lấy đúng mã màu trong code).
- Liên kết chéo bằng `href="#id"`; sang tài liệu khác: `{{ route('pages.userGuide.show', 'khoá') }}#id`.
- FontAwesome bản 5 (`fa-exclamation-triangle`, không dùng tên FA6).

## 5. Thêm một tài liệu mới

1. Thêm dòng vào `UserGuideController::GUIDES` đúng vị trí theo menu (`updated_at` = hôm nay `Y-m-d`). Menu leftNAV tự có mục con.
2. Viết `toc.php` và `sections/*.blade.php` (không cần index riêng).
3. Chạy kiểm tra (mục 6).

## 6. Kiểm tra trước khi báo xong

1. `php artisan pms:user-guide-check` phải 0 lỗi; không để lại cảnh báo "sửa sau hướng dẫn" do thay đổi của mình
   (sửa section thì mtime section mới hơn nguồn, cảnh báo tự hết). `php -l` cho controller, `toc.php`.
2. Render không cần đăng nhập web: bootstrap app, `session()->put('user', [...])` gồm `userId, fullName, userGroup,
   production_code, production_name, department, userName` (leftNAV đọc `production_name`), gọi
   `(new UserGuideController)->show('<khoá>')->render()`; đếm `class="ug-section"` = số mục. Khoá lạ phải 404.
3. Xem bố cục: `php -S 127.0.0.1:8099 -t public` chạy nền, lưu HTML đã render vào `public/__ug_*.html` sau khi thay
   `http://localhost/` bằng `http://127.0.0.1:8099/`, chụp bằng Edge headless
   (`"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" --headless=new --screenshot=... --window-size=1440,2000`).
   Xem một mục giữa trang: chèn `<style>.ug-section:not(#id){display:none}</style>`. **Xoá `public/__ug_*`** và chỉ dừng
   đúng tiến trình `php -S 127.0.0.1:8099` (tra theo CommandLine) - không kill mọi php.exe.
4. Cập nhật `updated_at` của tài liệu trong `GUIDES`.

## 6b. Ảnh chụp màn hình (BẮT BUỘC chụp lại khi giao diện đổi) - theo bộ công cụ WMS

Ảnh ở `public/img/userGuide/<khoá>/`: `<id>.png` (màn hình) và `<id>-form.png` (hộp thoại Thêm), `<id>` = id mục trong toc.php.
Chèn bằng `@include('pages.userGuide.shared.shot', ['src' => 'img/userGuide/<khoá>/<id>.png', 'caption' => '...'])` (bấm ảnh để phóng to).
Sửa bố cục / cột / nút / ô nhập của màn hình nào thì **chụp lại ảnh các mục dùng màn hình đó trong cùng lượt**. `pms:user-guide-check`
cảnh báo "màn hình chưa có ảnh chụp" và "giao diện đã sửa sau khi chụp ảnh" (controller / view mới hơn ảnh).

Công cụ `.agents/skills/user-guide/scripts/` (Node >= 22, Chrome hoặc Edge, Python + Pillow, Vite dev server đang chạy cho trang React):

```bash
php -S 127.0.0.1:8099 -t public                    # chạy nền
cd .agents/skills/user-guide/scripts
php plan.php <khoá> [id1,id2] > "$TMP/plan.json"   # kế hoạch chụp từ toc.php
node shoot.mjs "$TMP/plan.json" "$TMP/shots"        # chụp; in 'MISSING' / 'blocked'
php cleanup.php                                     # BẮT BUỘC: xoá các phiên đăng nhập do mint.php tạo
python compress.py "$TMP/shots" <khoá>             # nén 256 màu, chép vào public/img/userGuide/<khoá>/
php insert.php <khoá> [--dry]                       # chèn ảnh chưa có (<id>.png sau đoạn mở đầu, <id>-form.png trên bảng ô nhập)
```

- `mint.php` tạo phiên trực tiếp (tài khoản `Admin`, phân xưởng PXV1): không ghi Lịch Sử Đăng Nhập / Audit Trail, không chạy đồng bộ nhân sự.
- **An toàn dữ liệu** (DB local là dữ liệu thật): `shoot.mjs` chặn mọi request không phải GET tới ứng dụng, trừ `READ_ONLY_POST`
  (hiện chỉ `/Schedual/view`). Ảnh thiếu dữ liệu và có dòng `blocked` -> đọc controller, chỉ thêm endpoint vào `READ_ONLY_POST`
  khi chắc chắn không ghi gì. `clickCreate()` chỉ bấm nút "Thêm..." có `data-toggle="modal"` / `data-target`.
- Khai trong toc.php: `'shots' => false` (không chụp - đã đặt cho Lịch Sử Đăng Nhập vì có IP / nhật ký người khác, và các mục
  ma trận của phanQuyen), `'shots' => [[...]]` / `'extraShots' => [[...]]` (khoá: name, url, js, clip, highlight, maxHeight,
  wait, afterJs), `'shotProduction' => 'PXV2'`, `'shotWait' => 12000` (Gantt React).
- **Luôn xem lại ảnh** trước khi chép (ghép ảnh thu nhỏ thành một tấm): bảng còn đang tải, hộp thoại sai, bảng trống, sửa chú thích
  khi hộp thoại không phải form thêm mới (vd Thành Phẩm: bước chọn mã BTP).

## 7. Bẫy đã gặp
- `toc.php` được `require` bên trong `UserGuideLinks::groups(string $guide)`: đặt biến `$guide` / `$key` trong toc.php sẽ ghi đè tham số của hàm (lỗi groups(): Argument #1 must be of type string, array given). Dùng tên biến khác.

- Route không có tên (ví dụ `href="/Schedual"`, `/maintenance-calendar` trước đây) thì nút "?" không nhận ra: đặt `->name(...)` cho route rồi khai vào `toc.php`.
- Nhiều phiên cùng sửa một màn hình: phiên đổi chức năng phải tự sửa section liên quan (quy tắc 5 của common-rules); cảnh báo "sửa sau hướng dẫn" là tín hiệu cho đúng việc đó, không tắt bằng cách chạm file mà chưa đọc lại nội dung.
- Bash heredoc dài chứa nhiều dấu `'` hay bị lỗi "unexpected EOF": ghi script Python ra file trong scratchpad rồi chạy.

- topNAV PMS là `fixed-top`: `.ug-page` phải chừa `padding-top` (~76px) nếu không khối tiêu đề bị che.
- `.ug-flow-step > b:first-of-type` là tiêu đề ô; `<b>` khác trong câu vẫn nằm cùng dòng (bản WMS làm mọi `<b>` thành khối).
- PMS không có `user_can()` / `user_has_any_role()` như WMS: `sectionHead` kiểm `user_has_permission()` hoặc `role:` theo `session('user.userGroup')`.
- DB local là dữ liệu thật: render thử chỉ đọc, không gọi action ghi.

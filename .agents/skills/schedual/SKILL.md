---
name: Lập Lịch Sản Xuất, Hiệu Chuẩn & Bảo Trì (Schedual)
description: Các tính năng, logic ưu tiên và quy tắc cốt lõi của tính năng sắp lịch Sản Xuất, Hiệu Chuẩn và Bảo Trì. Đọc trước khi sửa SchedualController / FullCalender.jsx / sắp lịch tự động. Mọi thay đổi nguyên lý sắp lịch tự động PHẢI cập nhật skill này và tạo phiên bản mới trong storage/app/schedualer (xem mục 0).
---

# Kỹ Năng Sắp Lịch Sản Xuất & Bảo Trì (Schedual)

Skill này chứa các thông tin tổng hợp về nghiệp vụ, quy tắc ưu tiên và logic lập lịch để tham chiếu khi bảo trì code, đặc biệt tại `SchedualController.php` và `FullCalender.jsx`.

## 0. Quy Tắc Bắt Buộc Khi Thay Đổi Nguyên Lý Sắp Lịch Tự Động

Người dùng (Nguyễn Hữu Phong) yêu cầu từ 10/10/2026: **mỗi khi thay đổi hoặc bổ sung nguyên lý sắp lịch tự động** (thứ tự nhóm, cách chọn phòng, ràng buộc ngày, chiến dịch, BT-HC-TI, plugin Kiểm soát tồn BTP...) thì trong CÙNG lượt làm việc phải:

1. **Cập nhật skill** – skill này (lõi) và/hoặc `.agents/skills/wip-control/SKILL.md` (plugin). Ghi cả quyết định của người dùng và việc KHÔNG được làm lại.
2. **Tạo phiên bản mới của tài liệu Nguyên lý cho khách hàng** trong `storage/app/schedualer/` theo đúng quy trình ở `storage/app/schedualer/README.md`:
   - chép thư mục phiên bản mới nhất `nguyen-ly/v<X.Y>_<YYYY-MM-DD>/` thành thư mục phiên bản mới (MAJOR khi đổi cách làm, MINOR khi bổ sung/làm rõ), không sửa đè bản cũ;
   - sửa file `.md` (viết cho khách hàng: tiếng Việt dễ hiểu, không tên hàm/tên bảng), sửa dòng "Phiên bản … · ngày";
   - xuất Word bằng `python storage/app/schedualer/_tools/md_to_docx.py "<file .md>"`, rồi PDF bằng `powershell -ExecutionPolicy Bypass -File storage/app/schedualer/_tools/docx_to_pdf.ps1 "<file .docx>"` (cần Microsoft Word); mở PDF kiểm tra bố cục;
   - thêm mục ở đầu `storage/app/schedualer/CHANGELOG.md`.
3. Nếu thay đổi chỉ là sửa lỗi code mà nguyên lý không đổi thì không cần phiên bản mới, nhưng vẫn cập nhật skill nếu skill mô tả sai.

Bản trực tuyến v1.0 (Claude Docs): https://claude.ai/artifact/SZRvWgbQheSqVwHp41KuBw — nội dung giống `storage/app/schedualer/nguyen-ly/v1.0_2026-10-10/`.

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
* **Chặn vi phạm công đoạn trước - theo TỪNG LÔ** (`update()` trong `SchedualController.php`): khi rải từng lô, nếu lô sắp ghi có thời điểm bắt đầu sớm hơn `pred.end` của **chính lô đó** thì kéo về đúng `pred.end`, chấp nhận hở một khoảng trước lô. Các lô sau vẫn bám sát lô liền trước nếu đã hợp lệ. Trả về `campaign_shift_batches` (số lô bị kéo) và `campaign_shift_minutes` (mức kéo lớn nhất) để frontend báo cho người dùng.

  > ⚠️ **Không** dời cả chiến dịch ra sau chiến dịch công đoạn trước. Lô N của công đoạn sau chỉ phụ thuộc lô N của công đoạn trước, nên đúng nghiệp vụ là ĐH lô 1 chạy ngay khi THT lô 1 xong (song song với THT lô 2), chứ không chờ toàn bộ THT kết thúc.

### 3.1. Tạo Lịch Thủ Công - Thả Nhóm Lô Vào Phòng (`store()`)

Nút "tạo chiến dịch" (`createManualCampain`, `createManualCampainStage`) **chỉ gán `campaign_code`**, không sinh thời gian. Thời gian chỉ xuất hiện khi người dùng thả nhóm lô vào phòng → `store()`.

* Các lô được rải liên tục (back-to-back) từ điểm thả: lô đầu `p_time + m_time`, các lô sau `m_time`, xen vệ sinh C1 và kết bằng C2; giữa các lô có `skipOffTime()` để nhảy ngày nghỉ / phòng bận.
* `manualBatchTimes()` tính trước thời gian của cả nhóm; `predecessorReadyTimes()` lấy `pred.end` của từng lô (bỏ qua công đoạn cân 1, 2 và lô có công đoạn trước chưa xếp).
* **Chặn vi phạm công đoạn trước - theo TỪNG LÔ**: trong lúc rải, lô nào bị lô trước đẩy tới sớm hơn `pred.end` của **chính nó** thì kéo về đúng `pred.end` rồi `skipOffTime()`; các lô sau tiếp tục bám sát lô liền trước. Một lượt duyệt xuôi là đủ, không cần lặp. Trả về `manual_shift_batches` và `manual_shift_minutes` (mức kéo lớn nhất).

  > ⚠️ Cố ý **cho phép hở khoảng trống** giữa các lô. Kéo cả nhóm ra sau toàn bộ công đoạn trước là sai nghiệp vụ: lô N chỉ phụ thuộc lô N của công đoạn trước.
* Thả nhóm lô công đoạn PC (stage 3) còn gán luôn `campaign_code` cho **mọi công đoạn** của các lô đó (`whereIn('plan_master_id', ...)`, không lọc stage).

## 4. Bảo Trì (BT), Hiệu Chuẩn (HC), Tiện Ích (TI)
* Mã công đoạn (`stage_code`) cho toàn bộ nhóm này là `8`.
* Phân loại màu sắc nhận diện: 
  * Bảo trì (mặc định): `#003A4F`
  * Hiệu chuẩn (kết thúc bằng `_HC`): `#9a1b72ff` (Tím đậm)
  * Tiện ích (kết thúc bằng `_TI`): `#830cbfff`
* Cảnh báo tới hạn/quá hạn: Viền cảnh báo được tính toán dưa trên `expected_date` hoặc `min_due` từ backend.
* Khi sắp lịch tự động: chiến dịch xếp xuyên BT-HC-TI chưa bắt đầu, sau đó BT-HC-TI bị đè được dời và kiểm tra hạn BT – xem **mục 6.2**.

---

## 5. Logic Sắp Lịch Sản Xuất Tự Động (Auto-Scheduling)

Toàn bộ logic nằm trong file [SchedualController.php](file:///c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php).

### 5.1. Tổng Quan Kiến Trúc

Hệ thống sắp lịch tự động sử dụng thuật toán **Forward Scheduling** (đẩy tiến từ ngày bắt đầu). Điểm vào chính là hàm `scheduleAll()` nhận request từ frontend và điều phối toàn bộ quy trình.

> ⚠️ Các dòng "**Vị trí:** Lxxxx" trong mục 5 là số dòng CŨ (trước 10/2026), file đã dài ~10.000 dòng. Luôn tìm theo tên hàm (`grep -n "function scheduleCampaign"`), đừng tin số dòng. Mục 6 mô tả các cơ chế mới nhất.

#### Bảng Mã Công Đoạn (Stage Code)

| Stage Code | Tên Công Đoạn | Viết Tắt |
|------------|---------------|----------|
| 1          | Cân nguyên liệu (Cân chính) | CNL |
| 2          | Cân nguyên liệu (Cân phụ)   | CNL |
| 3          | Pha chế (Preparation/Compounding) | PC |
| 4          | Trộn hạt / Tạo hạt (Blending/Granulation) | THT |
| 5          | Dập hình / Đóng nang (Forming) | ĐH |
| 6          | Bao phim (Coating) | BP |
| 7          | Đóng gói (Packaging/Blistering) | ĐG |
| 8          | Bảo trì / Hiệu chuẩn / Tiện ích | BT/HC/TI |

---

### 5.2. Hàm Điều Phối Chính: `scheduleAll(Request $request)` 
**Vị trí:** [L4375-L4730](file:///c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php#L4375-L4730)

#### Tham Số Đầu Vào (từ Request)

| Tham số | Mô tả | Mặc định |
|---------|-------|----------|
| `selectedDates` | Mảng ngày nghỉ (off days) được chọn | `[]` |
| `work_sunday` | Cho phép làm việc Chủ nhật | `false` |
| `reason` | Lý do lập lịch (ghi log) | `'NA'` |
| `prev_orderBy` | Sắp xếp theo thứ tự công đoạn trước | `false` |
| `start_date` | Ngày bắt đầu lập lịch | Ngày hiện tại, 06:00 |
| `selectedStep` | Công đoạn cuối cùng cần lập lịch (`CNL`, `PC`, `THT`, `ĐH`, `BP`, `ĐG`) | `'ĐG'` |
| `runType` | Chế độ chạy: `'line'` (theo phòng/line) hoặc mặc định (toàn bộ) | — |
| `lines` | Mã phòng (room code) khi `runType = 'line'` | — |
| `stage_plan_ids` | Mảng ID stage_plan khi chạy theo line | — |
| `wt_bleding` | Thời gian chờ (ngày) giữa PC → THT (lô thường) | `0` |
| `wt_bleding_val` | Thời gian chờ (ngày) giữa PC → THT (lô validation) | `1` |
| `wt_forming` | Thời gian chờ (ngày) giữa THT → ĐH (lô thường) | `0` |
| `wt_forming_val` | Thời gian chờ (ngày) giữa THT → ĐH (lô validation) | `1` |
| `wt_coating` | Thời gian chờ (ngày) giữa ĐH → BP (lô thường) | `0` |
| `wt_coating_val` | Thời gian chờ (ngày) giữa ĐH → BP (lô validation) | `1` |
| `wt_blitering` | Thời gian chờ (ngày) giữa BP → ĐG (lô thường) | `0` |
| `wt_blitering_val` | Thời gian chờ (ngày) giữa BP → ĐG (lô validation) | `5` |

> **Lưu ý**: Thời gian chờ (wait time) từ request tính theo **ngày**, được nhân `× 24 × 60` để chuyển sang **phút** trong code.

#### Luồng Xử Lý Chính (cập nhật 10/10/2026)

```
scheduleAll()  → withSchedulingLock()  (khoá is_scheduling theo phân xưởng; đang có người chạy → HTTP 423)
└── runScheduleAll($request)
    ├── 1. Khởi tạo: selectedDates, work_sunday, reason, prev_orderBy,
    │      limit_mold_change (mặc định true), mold_change_tolerance (giờ, mặc định 72),
    │      campaignSkipsMaintenance = config('scheduling.campaign_skip_maintenance', true)
    ├── 2. loadOffDate('asc')  → gộp ngày nghỉ thành khoảng 06:00 → 06:00
    ├── 3. start_date = request.start_date hoặc hôm nay, lúc 06:00
    │
    ├── [selectedStep == 'CNL'] → scheduleWeightStage(start_date) → return
    ├── [runType == 'line']     → scheduleLine(...) → shiftMaintenanceAfterSchedule() → return
    │
    ├── 4. scheduleWithHardDeadlines()          ← mục 6.1 (chen lịch theo hạn HH NL/BB)
    │      lặp tối đa 1 + config('scheduling.hard_deadline_reruns', 3) lượt, mỗi lượt trong savepoint:
    │      └── runScheduleGroups()  (trên bản clone "pristine" của controller)
    │          ├── a. scheduleDeadlinePriority()       nhóm CHEN THEO HẠN (chỉ có từ lượt 2)
    │          ├── b. scheduleIntermediate(i)  i = selectedStep → 3   BÁN THÀNH PHẨM
    │          ├── c. scheduleWarningMR(i)     i = selectedStep → 3   CẢNH BÁO NL/BB (EDF)
    │          ├── d. scheduleSensitiveProduct(i) i = 3 → selectedStep  NHẠY CẢM
    │          └── e. foreach promotional [0, 1]: foreach stage 3 → selectedStep:
    │                 Auto_scheduler_Stage_Forward(i, wt_normal, wt_val, start_date, promotional)
    │                 (0 = thương mại trước, 1 = khuyến mãi sau)
    ├── 5. shiftMaintenanceAfterSchedule()      ← mục 6.2 (dời BT-HC-TI bị chiến dịch đè)
    ├── 6. scanOverdueTasks()                    → campaign quá hạn biệt trữ (cho Pass 2)
    └── response: { overdueCampaigns, hard_deadline: {reruns, priority_lots, late[]}, maintenance_shifted[] }
```

- **Pass 2** (`scheduleAllPass2` → `runScheduleAllPass2`): xoá lịch các campaign quá hạn, `scheduleOverdueCampaigns` xếp chúng trước (VIP), rồi chạy lại b → e. Pass 2 KHÔNG có bước chen theo hạn (6.1), nhưng có bật `campaignSkipsMaintenance` và gọi `shiftMaintenanceAfterSchedule()`.
- **Plugin Kiểm soát tồn BTP** chạy SAU khi `scheduleAll` trả về (frontend gọi `/Schedual/wip-control/run` nếu có Max) – xem skill `wip-control`. Plugin dùng `WipAwareScheduler extends SchedualController` để gọi lại b → e (`rescheduleUnscheduled`), không có nhóm chen theo hạn.
- **Frontend** (`FullCalender.jsx`, modal Sắp lịch tự động): sau khi xong, nếu `hard_deadline.late` hoặc `maintenance_shifted` không rỗng thì hiện Swal liệt kê (thay cho toast "Hoàn Thành Sắp Lịch"); đường plugin thì danh sách BT-HC-TI hiện trong modal Kết quả kiểm soát BTP.

**Ý nghĩa thứ tự ưu tiên (nhóm trước chọn phòng/giờ trước, nhóm sau lấp chỗ trống):**
1. **Chen theo hạn** (`scheduleDeadlinePriority`): lô sẽ trễ Ngày HH NL chính / HH BB + cả chiến dịch của nó (mục 6.1).
2. **Bán thành phẩm** (`scheduleIntermediate`): công đoạn trước đã có lịch (`prev.start` not null), xếp theo `prev.start ASC`. ⚠️ Nhóm này KHÔNG xét hạn; đây là lý do lô hạn gần từng bị lô hạn xa chiếm phòng (sự cố Stadxicam 7.5 lô 051026, 08/10/2026) → sinh ra mục 6.1.
3. **Cảnh báo NL/BB** (`scheduleWarningMR`): lô có ít nhất một cột NL/BB, sắp `LEAST(expired_material_date, allow_weight_before_date, preperation_before_date, blending_before_date, forming_before_date, coating_before_date, parkaging_before_date, expired_packing_date) ASC` (EDF), rồi `prev.start`.
4. **Sản phẩm nhạy cảm** (`scheduleSensitiveProduct`): `quarantine_total > 0`.
5. **Thương mại** (`promotional_products = 0`) theo `order_by` (hoặc `prev.start` khi `prev_orderBy`).
6. **Khuyến mãi** (`promotional_products = 1`) xếp sau cùng.

> ⚠️ Các nhóm b, c (và nhóm chen a) gọi với thời gian chờ **0** (`scheduleIntermediate($i, 0, 0, ...)`); chỉ nhóm e dùng `wt_*`. Ngoài ra 8 ô thời gian chờ trên modal thiếu thuộc tính `name` nên KHÔNG BAO GIỜ gửi lên backend → nhóm e luôn dùng mặc định (`wt_*_val`: THT 1 ngày, ĐH/BP/ĐG 5 ngày; lô thường 0). Đừng "sửa" thời gian chờ của nhóm chen sang 5 ngày: lô thẩm định 051026 sẽ không kịp hạn BB (đã thử 08/10/2026).

---

### 5.3. Hàm Sắp Lịch Bán Thành Phẩm: `scheduleIntermediate()`
**Vị trí:** [L4511-L4632](file:///c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php#L4511-L4632)

#### Điều Kiện Lọc Task
- `stage_code = stageCode`
- `finished = 0` (chưa hoàn thành)
- `not_schedule = 0` (không bị loại trừ)
- `active = 1` (còn hoạt động)
- `start IS NULL` (chưa được xếp lịch)
- `prev.start IS NOT NULL` (công đoạn trước ĐÃ được xếp lịch)
- `after_weigth_date IS NOT NULL` (đã cân nguyên liệu)
- Nếu `stage_code == 7`: thêm `after_parkaging_date IS NOT NULL` (đã có bao bì)
- Lọc theo `deparment_code` của user hiện tại
- Sắp xếp theo `prev.start ASC` (công đoạn trước bắt đầu sớm nhất → ưu tiên xếp trước)

#### Logic Xử Lý
```
Với mỗi task:
├── Xác định waite_time: is_val ? waite_time_val_batch : waite_time_nomal_batch
├── [Nếu campaign_code == null]
│   └── sheduleNotCampaing(task, stageCode, waite_time, start_date, null)
└── [Nếu có campaign_code]
    ├── Skip nếu campaign_code đã xử lý
    └── Gom tất cả task cùng campaign → sortBy('batch')
        └── scheduleCampaign(campaignTasks, stageCode, waite_time, start_date, null)
```

---

### 5.4. Hàm Sắp Lịch Sản Phẩm Nhạy Cảm: `scheduleSensitiveProduct()`
**Vị trí:** [L4634-L4730](file:///c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php#L4634-L4730)

#### Khác Biệt So Với `scheduleIntermediate()`
- **Thêm JOIN** `intermediate_category` để lấy `quarantine_total` (số ngày biệt trữ).
- **Thêm điều kiện**: `quarantine_total > 0`.
- **Tính toán `start_date_temp`**: Lấy `responsed_date - quarantine_total` ngày. Nếu giá trị này > `start_date` → dùng nó thay vì `start_date`. Điều này đảm bảo sản phẩm nhạy cảm được lập lịch muộn hơn nếu có đủ thời gian biệt trữ trước ngày giao hàng.

---

### 5.5. Hàm Sắp Lịch Chính Theo Stage: `Auto_scheduler_Stage_Forward()`
**Vị trí:** [L4732-L4878](file:///c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php#L4732-L4878)

#### Logic
- Lấy tất cả task chưa được xếp lịch (`start IS NULL`) theo `stage_code`.
- **Nếu `prev_orderBy == true` và `stageCode > 3`**: Sắp xếp theo `prev.start ASC` (theo thời gian bắt đầu của công đoạn trước).
- **Ngược lại**: Sắp xếp theo `order_by ASC` (thứ tự ưu tiên do người dùng thiết lập).
- Duyệt từng task:
  - Lô đơn → `sheduleNotCampaing()`
  - Lô campaign → gom nhóm → `scheduleCampaign()`

---

### 5.6. Hàm Sắp Lịch Cân Nguyên Liệu: `scheduleWeightStage()`
**Vị trí:** [L4880-L4963](file:///c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php#L4880-L4963)

#### Đặc Thù
- Chỉ xử lý `stage_code IN (1, 2)` (cân chính, cân phụ).
- **Lập lịch ngược** dựa trên `next.start` (thời gian bắt đầu của công đoạn SAU):
  - Lấy `next.start`, trừ lùi 3 ngày làm việc (bỏ qua ngày nghỉ) → thời điểm cần bắt đầu cân.
- Sắp xếp theo `next.start ASC`.
- Dùng `scheduleweight()` (không phải `sheduleNotCampaing()`).

#### Điều Kiện Lọc
- `sp.active = 1`, `next.active = 1`
- `sp.start IS NULL`, `sp.finished = 0`, `next.finished = 0`
- `next.start > now()` (công đoạn sau đã được xếp và chưa bắt đầu)
- `after_weigth_date IS NOT NULL`

---

### 5.7. Hàm Sắp Lịch Theo Line: `scheduleLine()`
**Vị trí:** [L4965-L5111](file:///c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php#L4965-L5111)

#### Tham Số
- `required_room`: Mã phòng cố định để xếp tất cả task vào.
- `stage_plan_ids`: Mảng ID stage_plan cần xếp (do frontend truyền).

#### Logic
- Nếu `prev_orderBy == true` và `stageCode >= 4`: sắp xếp theo `prev.start ASC`.
- Ngược lại: sắp xếp theo `order_by_line ASC`.
- Duyệt từng task → ép tất cả vào phòng `required_room`.
- Task đơn → `sheduleNotCampaing(task, ..., required_room)`.
- Campaign → `scheduleCampaign(campaignTasks, ..., required_room)`.

---

### 5.8. Hàm Lõi: `sheduleNotCampaing()` – Xếp Lịch 1 Lô Đơn
**Vị trí:** [L5113-L5468](file:///c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php#L5113-L5468)

Đây là hàm cốt lõi xử lý việc tìm phòng + thời gian tối ưu cho **một lô đơn lẻ** (không thuộc campaign).

#### Bước 1: Xác Định Thời Điểm Bắt Đầu Sớm Nhất (`earliestStart`)

Gom tất cả các "ứng viên thời gian" vào mảng `$candidates`, rồi lấy **MAX**:

| Ứng viên | Mô tả |
|-----------|-------|
| `now()` | Thời điểm hiện tại (làm tròn lên 15 phút) |
| `start_date` | Ngày bắt đầu từ request |
| `after_weigth_date` | Ngày sau khi cân xong NL (nếu stage ≤ 6) |
| `allow_weight_before_date` | Ngày cho phép cân NL sớm nhất (nếu stage ≤ 6) |
| `after_parkaging_date` | Ngày sau khi có bao bì (nếu stage = 7) |
| `pred.end + waite_time` | Thời gian kết thúc predecessor + thời gian chờ |

> **Quy tắc**: `earliestStart = MAX(tất cả candidates)` → đảm bảo không vi phạm bất kỳ ràng buộc nào.

#### Bước 2: Chọn Phòng Sản Xuất (Room Selection)

**Ưu tiên lấy quota theo thứ tự:**

1. **Nếu task có `required_room_code`** hoặc `Line` được truyền vào → dùng phòng đó.
2. **Nếu task là lô validation (`code_val` != null)**:
   - Stage = 3 và batch > 1: tìm phòng đã sử dụng cho lô val đầu tiên (batch 1) → cùng phòng.
   - Stage > 3 và batch > 1: tìm phòng đã sử dụng cho cùng `code_val` ở stage hiện tại → ưu tiên phòng khác (phân tải).
3. **Mặc định**: Lấy tất cả phòng có quota `active = 1` cho `stage_code` và `intermediate_code` (hoặc `finished_product_code` nếu stage = 7).

**Truy vấn quota trả về**: `room_id`, `p_time_minutes` (thời gian chuẩn bị), `m_time_minutes` (thời gian sản xuất), `C1_time_minutes` (vệ sinh cấp 1), `C2_time_minutes` (vệ sinh cấp 2).

#### Bước 3: Tìm Phòng & Thời Gian Tối Ưu

```
Với mỗi room trong danh sách rooms:
│
├── Tính intervalTimeMinutes = p_time + m_time (× ratio nếu stage 7 + only_parkaging)
│
├── Gọi findEarliestSlot2(room_id, earliestStart, intervalTime, C2_time, tank, keep_dry, ...)
│   → Trả về candidateStart (thời điểm sớm nhất phòng này rảnh)
│
└── So sánh: nếu candidateStart < bestStart → chọn phòng này
```

> **`ratio`**: Nếu stage = 7 và `only_parkaging == 1` → `ratio = percent_parkaging / 100`. Dùng để giảm thời gian sản xuất khi chỉ đóng gói một phần.

#### Bước 4: Áp Dụng Ngày Nghỉ & Tính Toán Thời Gian Cuối

```
bestStart = skipOffTime(bestStart, offDate, bestRoom)  → nhảy qua ngày nghỉ

bestEnd = addWorkingMinutes(bestStart, finalInterval, bestRoom)  → cộng phút làm việc (bỏ qua ngoài ca, Chủ nhật, ngày nghỉ)

start_clearning = bestEnd
end_clearning = addWorkingMinutes(start_clearning, C2_time, bestRoom)
```

> **`finalInterval`** được tính lại từ quota của bestRoom (không dùng giá trị tạm từ vòng lặp), minimum = 15 phút.

#### Bước 5: Lưu Lịch

Gọi `saveSchedule(1, task.id, bestRoom, bestStart, bestEnd, start_clearning, end_clearning, 2, 1)`.

#### Bước 6: Đệ Quy Lập Lịch Công Đoạn Kế Tiếp

Nếu task có `nextcessor_code` và `next_stage_code <= max_Step`:
→ Truy vấn task ở công đoạn kế tiếp.
→ Gọi đệ quy `sheduleNotCampaing(nextTask, next_stage_code, waite_time, bestEnd, null)`.

> Cơ chế này tạo **chuỗi liên tục** (chain scheduling): khi 1 lô PC được xếp xong → tự động xếp luôn THT → ĐH → BP → ĐG nếu có thể.

---

### 5.9. Hàm Lõi: `scheduleCampaign()` – Xếp Lịch Campaign (Nhiều Lô Liên Tục)
**Vị trí:** [L5470-L6044](file:///c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php#L5470-L6044)

Campaign là nhóm nhiều lô (batch) của cùng sản phẩm chạy liên tục trên cùng phòng, chỉ cần vệ sinh cấp 1 (C1) giữa các lô, vệ sinh cấp 2 (C2) chỉ ở lô cuối.

#### Bước 1: Xác Định `earliestStart`

Ràng buộc công đoạn trước được áp **theo từng lô**, và phụ thuộc nhịp lô (slot) của **từng phòng** nên phải tính riêng cho mỗi phòng trong vòng chọn phòng:

```
predReadyList[N] = pred_end[N] + waite_time      (bỏ qua predecessor ở stage 1, 2)
baseEarliestStart = MAX(now, start_date, after_weigth_date, after_parkaging_date, ...)

Với mỗi phòng ứng viên:
    slot_room = m_time(phòng) × ratio + C1_time(phòng)
    earliestStart(phòng) = campaignEarliestStart(baseEarliestStart, predReadyList, slot_room)
                         = MAX( baseEarliestStart, MAX_N( predReadyList[N] − N × slot_room ) )
```

> **Vì sao trừ `N × slot`**: lô thứ N bắt đầu tại `T + N × slot`, nên để lô N không sớm hơn công đoạn trước thì `T >= predReady[N] − N × slot`.

> ⚠️ Trước 18/08/2026 công thức này dùng `avg_slot_time` = trung bình `m_time` của **mọi phòng** trong quota. Khi phòng được chọn chạy nhanh hơn mức trung bình, mỗi lô lệch sớm dần và các lô cuối chiến dịch bắt đầu trước khi công đoạn trước kết thúc (sự cố Paracetamol EG 1g, lệch tới 108 giờ). Nay dùng nhịp của chính phòng đang xét.

#### Bước 2: Chọn Phòng

Giống `sheduleNotCampaing()`, với thêm logic:
- **Liên hệ PC → THT (stage 3 → 4)**: Nếu phòng PC trước là room `6, 7` → ưu tiên phòng THT `13, 14`. Nếu PC ở room `10` → ưu tiên THT room `17`. Rollback nếu filter trống.

#### Bước 3: Tính Tổng Thời Gian Campaign

```
totalMinutes = p_time + (count × m_time) + (count - 1) × C1_time + C2_time
```

> Nếu `totalTimeCampaign` (từ campaign trước) > `totalMinutes` → dùng `totalTimeCampaign` để đảm bảo phòng được book đủ lâu.

#### Bước 4: Xếp Thử → Kiểm Tra Từng Lô → Dời Cả Chiến Dịch

`buildCampaignBatchTimes()` tính trước thời gian của **tất cả** các lô, sau đó đối chiếu từng lô với `predReadyList`:

```
batchPlan = buildCampaignBatchTimes(tasks, bestRoom, bestStart, stageCode)

Lặp tối đa 5 lần:
├── shift = MAX_N( predReadyList[N] − batchPlan[N].start )   (chỉ tính lô vi phạm)
├── Nếu shift <= 0 → thoát (lịch hợp lệ)
├── bestStart = findEarliestSlot2(bestRoom, bestStart + shift, ...)
│   └── Không tìm được slot mới / không tiến triển → giữ nguyên, thoát (chống lặp vô hạn)
└── batchPlan = buildCampaignBatchTimes(..., bestStart, ...)
```

> **Dời cả chiến dịch, không chèn khoảng trống** giữa các lô — campaign phải chạy liên tục, chỉ xen vệ sinh cấp 1.

> Lưới an toàn này bắt các trường hợp nhịp thực tế ngắn hơn `slot_room`, ví dụ campaign ĐG có `percent_parkaging` khác nhau giữa các lô (`slot_room` tính theo tỉ lệ của lô đầu).

Sau khi chốt được `batchPlan`, mỗi lô được lưu theo đúng thời gian đã kiểm tra:

```
foreach batchPlan:
│
├── [Batch đầu tiên (counter == 1)]
│   ├── duration = p_time + m_time (chuẩn bị + sản xuất)
│   ├── cleaning = C1 (nếu >1 batch) hoặc C2 (nếu chỉ 1 batch)
│   └── first_in_campaign = 1
│
├── [Batch cuối cùng (counter == count)]
│   ├── duration = m_time (chỉ sản xuất, không chuẩn bị)
│   ├── cleaning = C2 (vệ sinh cấp 2)
│   └── first_in_campaign = 0
│
├── [Batch giữa]
│   ├── duration = m_time
│   ├── cleaning = C1 (vệ sinh cấp 1)
│   └── first_in_campaign = 0
│
└── saveSchedule(first_in_campaign, task.id, bestRoom, start, end, start_clearning, end_clearning, ...)
```

> Toàn bộ phép tính thời lượng ở trên nằm trong `buildCampaignBatchTimes()`, dùng chung cho cả lần xếp thử và lần lưu — bảo đảm lịch đã kiểm tra và lịch lưu luôn khớp tuyệt đối.

#### Bước 5: Đệ Quy Campaign Kế Tiếp

Nếu có `nextcessor_code` và `hasImmediately`:
→ Gom tất cả `nextcessor_code` từ campaign tasks.
→ Query task ở stage kế → gọi `scheduleCampaign()` cho campaign công đoạn sau.
→ Truyền `totalTimeCampaign` để pipeline balancing.

---

### 5.10. Hàm Xếp Lịch Cân NL: `scheduleweight()`
**Vị trí:** [L6047-L6264](file:///c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php#L6047-L6264)

#### Đặc Thù
- **Tính ngày bắt đầu**: `next.start` (công đoạn kế) → trừ lùi **3 ngày làm việc** (bỏ qua selectedDates/ngày nghỉ).
- **Mode campaign** (`$mode = true`): Nhận collection tasks, gộp tất cả batch lên cùng slot.
  - `campaign_index` = 1 + (quota.campaign_index - 1) × count → nhân hệ số theo số batch.
  - `maxofbatch_campaign`: giới hạn số batch tối đa trong 1 lần cân.
- **Mode đơn** (`$mode = false`): Xử lý 1 task duy nhất.
- Phòng cân không có thời gian chuẩn bị riêng theo stage 1/2.

---

### 5.11. Các Hàm Phụ Trợ

#### `loadOffDate(string $sort)` – Tải Ngày Nghỉ
**Vị trí:** [L4140-L4222](file:///c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php#L4140-L4222)

- Parse `selectedDates` thành các khoảng off `[start: 06:00, end: 06:00 ngày sau]`.
- **Gộp ngày liên tiếp** thành 1 block lớn (tối ưu hóa).
- Sort theo `start` (asc/desc).
- Kết quả lưu vào `$this->offDate`.

#### `skipOffTime(Carbon $time, array $offDateList, ?int $roomId)` – Nhảy Qua Ngày Nghỉ
**Vị trí:** [L3943-L4007](file:///c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php#L3943-L4007)

- Nếu `$time` nằm trong khoảng off → trả về `off.end` (nhảy tới cuối).
- Nếu `roomId` được truyền → cũng kiểm tra thêm `roomAvailability` (phòng bận).
- Nếu `$time` nằm trước tất cả off → trả về chính nó.

#### `loadRoomAvailability(string $sort, int $roomId)` – Tải Lịch Bận Phòng
**Vị trí:** [L4009-L4138](file:///c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php#L4009-L4138)

- Query `stage_plan` lấy tất cả event **chưa hoàn thành** (`finished = 0`) + **trong tương lai** (`end >= now()`).
- **Lô đơn**: lấy `start → COALESCE(end_clearning, end)`.
- **Campaign**: GROUP BY `campaign_code`, lấy `MIN(start) → MAX(COALESCE(end_clearning, end))` → coi cả campaign như 1 block lớn.
- **Merge overlapping blocks** để tránh trùng lặp.
- Kết quả lưu vào `$this->roomAvailability[$roomId]`.

#### `findEarliestSlot2()` – Tìm Slot Trống Sớm Nhất
**Vị trí:** [L4224-L4301](file:///c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php#L4224-L4301)

```
Input: roomId, Earliest, intervalTime, C2_time, requireTank, requireAHU, ...
Output: Carbon (thời điểm bắt đầu sớm nhất có thể)
```

**Thuật toán:**
1. Load room availability (busyList).
2. `current_start = skipOffTime(Earliest)`.
3. Duyệt từng busy block:
   - Nếu `current_start < busy.start`:
     - Tính `gap = current_start → busy.start`.
     - Tính `offTime` trong gap (iterative expansion).
     - Nếu `gap >= need + offTime` → **tìm thấy slot** → return.
   - Nếu `current_start` nằm trong busy → nhảy tới `busy.end`, skipOffTime lại.
4. Nếu hết busyList → return `current_start` (phòng trống phía sau).

#### `addWorkingMinutes(Carbon $start, int $minutes, int $roomId, bool $workSunday)` – Cộng Phút Làm Việc
**Vị trí:** [L6266-L6384](file:///c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php#L6266-L6384)

- Tra cứu **ca làm việc** của phòng từ bảng `room`:
  - `sheet_regular = 1` → Ca hành chánh: 07:00 – 16:00.
  - `sheet_1 = 1` → Ca 1: 06:00 – 14:00.
  - `sheet_2 = 1` → Ca 2: 14:00 – 22:00.
  - `sheet_3 = 1` → Ca 3: 22:00 – 06:00 (qua ngày, biểu diễn 22 → 30).
- Bỏ qua **Chủ nhật** (nếu `workSunday = false`).
- Cộng dồn phút chỉ trong khoảng ca làm việc, nhảy qua thời gian ngoài ca.

#### `findLatestSlot()` – Tìm Slot Trống Muộn Nhất (Backward)
**Vị trí:** [L6386-L6573](file:///c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php#L6386-L6573)

- Tương tự `findEarliestSlot2()` nhưng duyệt **ngược** từ `latestEnd`.
- Kiểm tra thêm: **Tank overlap** (tối đa `maxTank` lô dùng tank cùng lúc) và **AHU overlap** (tối đa 3 lô cùng AHU group cho stage = 7, keep_dry = 1).
- Trả về `false` nếu không tìm được slot (vượt quá 100 lần thử).

#### `saveSchedule()` – Lưu Kết Quả Xếp Lịch
**Vị trí:** [L4303-L4373](file:///c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php#L4303-L4373)

**Dữ liệu cập nhật vào `stage_plan`:**

| Trường | Giá trị |
|--------|---------|
| `first_in_campaign` | 1 (batch đầu campaign) / 0 |
| `resourceId` | Room ID |
| `start` | Thời điểm bắt đầu sản xuất |
| `end` | Thời điểm kết thúc sản xuất |
| `start_clearning` | Thời điểm bắt đầu vệ sinh |
| `end_clearning` | Thời điểm kết thúc vệ sinh |
| `title_clearning` | `'VS-I'` hoặc `'VS-II'` |
| `scheduling_direction` | 1 (forward) / 0 (backward) |
| `AHU_group` | Nhóm AHU từ bảng room |
| `schedualed_at` | Thời điểm xếp lịch |
| `receive_packaging_date` | Ngày nhận bao bì (tính = start - 1 ngày, bỏ qua off_days) |

**Nếu `submit == 1`** (lô đã duyệt):
- Đồng bộ `packaging_date` qua `syncPackagingDate()`.
- Ghi lịch sử vào `stage_plan_history` (version, start, end, resourceId, người xếp, lý do).

---

### 5.12. Sơ Đồ Tổng Thể Quan Hệ Giữa Các Hàm

```
scheduleAll()
│
├── scheduleWeightStage()
│   └── scheduleweight()  (mode đơn / mode campaign)
│
├── scheduleLine()
│   ├── sheduleNotCampaing()
│   └── scheduleCampaign()
│
├── scheduleIntermediate()
│   ├── sheduleNotCampaing()
│   └── scheduleCampaign()
│
├── scheduleSensitiveProduct()
│   ├── sheduleNotCampaing()
│   └── scheduleCampaign()
│
└── Auto_scheduler_Stage_Forward()
    ├── sheduleNotCampaing()
    └── scheduleCampaign()

Hàm phụ trợ dùng chung:
├── loadOffDate()          → Tải ngày nghỉ
├── skipOffTime()          → Nhảy qua ngày nghỉ / phòng bận
├── loadRoomAvailability() → Tải lịch bận phòng
├── findEarliestSlot2()    → Tìm slot trống sớm nhất (forward)
├── findLatestSlot()       → Tìm slot trống muộn nhất (backward)
├── addWorkingMinutes()    → Cộng phút chỉ trong ca làm việc
└── saveSchedule()         → Lưu kết quả vào DB
```

---

### 5.13. Cấu Trúc Dữ Liệu Chính

#### Bảng `stage_plan` (Kế hoạch công đoạn)
| Trường | Mô tả |
|--------|-------|
| `id` | PK |
| `plan_master_id` | FK → plan_master |
| `product_caterogy_id` | FK → finished_product_category |
| `code` | Mã duy nhất của stage plan (vd: `PM001_3`) |
| `stage_code` | Mã công đoạn (1-8) |
| `predecessor_code` | Mã stage_plan công đoạn trước |
| `nextcessor_code` | Mã stage_plan công đoạn sau |
| `campaign_code` | Mã campaign (null nếu lô đơn) |
| `resourceId` | FK → room.id (phòng được gán) |
| `start` | Thời điểm bắt đầu (null = chưa xếp) |
| `end` | Thời điểm kết thúc |
| `start_clearning` | Bắt đầu vệ sinh |
| `end_clearning` | Kết thúc vệ sinh |
| `title_clearning` | VS-I / VS-II |
| `tank` | Cần bể chứa (0/1) |
| `keep_dry` | Cần AHU/hút ẩm (0/1) |
| `order_by` | Thứ tự ưu tiên |
| `required_room_code` | Mã phòng bắt buộc (null = tự chọn) |
| `immediately` | Cần chạy liền sau predecessor (0/1) |
| `finished` | Đã hoàn thành (0/1) |
| `active` | Còn hoạt động (0/1) |
| `not_schedule` | Loại trừ khỏi lịch tự động (0/1) |
| `first_in_campaign` | Batch đầu tiên trong campaign (0/1) |
| `scheduling_direction` | 1 = forward, 0 = backward |

#### Bảng `quota` (Năng suất phòng)
| Trường | Mô tả |
|--------|-------|
| `room_id` | FK → room |
| `stage_code` | Công đoạn |
| `intermediate_code` | Mã bán thành phẩm |
| `finished_product_code` | Mã thành phẩm |
| `p_time` | Thời gian chuẩn bị (TIME) |
| `m_time` | Thời gian sản xuất (TIME) |
| `C1_time` | Thời gian vệ sinh cấp 1 (TIME) |
| `C2_time` | Thời gian vệ sinh cấp 2 (TIME) |
| `campaign_index` | Hệ số campaign cho cân NL |
| `maxofbatch_campaign` | Số batch tối đa trong 1 campaign cân NL |
| `active` | Còn hoạt động (0/1) |

#### Bảng `room` (Phòng sản xuất)
| Trường | Mô tả |
|--------|-------|
| `id` | PK |
| `code` | Mã phòng |
| `stage_code` | Công đoạn chính của phòng |
| `sheet_regular` | Ca hành chánh (0/1) |
| `sheet_1` | Ca 1: 06-14h (0/1) |
| `sheet_2` | Ca 2: 14-22h (0/1) |
| `sheet_3` | Ca 3: 22-06h (0/1) |
| `AHU_group` | Nhóm AHU (0 = không có) |

---

## 6. Các Cơ Chế Bổ Sung (10/2026) – Đọc Kỹ Trước Khi Sửa

### 6.1. Chen Lịch Theo Hạn HH NL Chính / HH BB (`scheduleWithHardDeadlines`)

**Mục tiêu (yêu cầu người dùng 08/10/2026):** 5 mốc trên `plan_master` không được vi phạm: Ngày có đủ NL (`after_weigth_date`), Ngày có đủ BB (`after_parkaging_date`), Ngày được phép cân (`allow_weight_before_date`), Ngày HH NL chính (`expired_material_date`), Ngày HH BB (`expired_packing_date`).
- 3 mốc "có đủ / được phép cân" là **mốc sớm nhất**, đã chặn trong `$candidates` của `sheduleNotCampaing` và `scheduleCampaign` (stage ≤ 6: `after_weigth_date`, `allow_weight_before_date`; stage 7: `after_parkaging_date`).
- 2 mốc HH là **mốc muộn nhất**, lõi xếp tiến nên không thể chặn trực tiếp → dùng cơ chế chen + sắp lại dưới đây.

**Thuật toán:**
1. `$runIds` = các dòng `stage_plan` của phân xưởng, stage 3..selectedStep, `finished=0, active=1, start IS NULL` (chỉ dòng lượt này xếp mới bị đánh giá).
2. `$pristine = clone $this` ngay sau khi cấu hình (chưa có cache phòng/khuôn).
3. Lượt 0: `DB::beginTransaction()` (savepoint) → `$this->runScheduleGroups()` → `hardDeadlineLate($runIds, $today)`.
4. `hardDeadlineLate`: PC (stage 3) bắt đầu sau `expired_material_date`, hoặc ĐG (stage 7) bắt đầu sau `expired_packing_date`, so theo **NGÀY** (`substr(start,0,10) > deadline`, giống cảnh báo Gantt `criticalChecks`). `avoidable = deadline >= max(hôm nay, after_weigth_date, allow_weight_before_date)` (PC) hoặc `max(hôm nay, after_parkaging_date)` (ĐG). Một lô trễ cả PC và ĐG giữ dòng stage lớn hơn.
5. Điểm = `[số lô trễ avoidable, tổng ngày trễ]`, giữ lượt tốt nhất (so mảng PHP).
6. `expandDeadlinePriority`: thêm lô trễ vào `$deadlinePriority[pm] = ['deadline' => Y-m-d, 'upto' => stage]`, rồi **kéo theo mọi lô cùng `campaign_code`** ở các công đoạn ≤ upto (lặp tới khi không đổi) → không tách chiến dịch.
7. Không còn lô trễ avoidable, hoặc không thêm được lô mới, hoặc hết số lượt → nếu lượt hiện tại là tốt nhất thì `DB::commit()`; ngược lại rollback và chạy lại với `priority` của lượt tốt nhất rồi commit. Còn lại: `DB::rollBack()` và lượt mới trên `clone $pristine` với `deadlinePriority = $next`.
8. `scheduleDeadlinePriority` (nhóm a): với stage 3 → selectedStep, lấy các dòng của lô trong `deadlinePriority` có `upto >= stage`, sắp theo hạn của chiến dịch (min deadline các lô) rồi `order_by`, gọi `sheduleNotCampaing` / `scheduleCampaign` với **wait 0**.
9. Kết quả trả về `hard_deadline.late[]` = {stage_plan_id, plan_master_id, stage_code, batch, rule, deadline, start, late_days, avoidable}; log `Sắp lịch: chen lô theo hạn HH NL/BB`.

**Config:** `scheduling.hard_deadline_reruns` (mặc định 3; **0 = tắt**). Không có file config riêng, chỉ đọc `config()` với mặc định.
**Chi phí:** mỗi lượt sắp lại ≈ 1 lần chạy lõi (PXV1 ~40 s) → có lô cần chen thì ~80 s.
**Kết quả thử PXV1 08/10/2026:** vi phạm HH NL/BB 9 → 3 (3 lô còn lại hạn đã qua), ĐG trễ ngày cần hàng 228 → 217 lô, không tăng đè giờ / sai thứ tự.
**Giới hạn:** Pass 2 và vòng sắp lại của plugin không có nhóm chen (plugin tự chặn vi phạm MỚI).

### 6.2. Chiến Dịch Xếp Xuyên BT-HC-TI, Rồi Dời BT-HC-TI (`MaintenanceShiftService`)

**Yêu cầu người dùng 08/10/2026:** khi sắp lịch tự động (lõi và plugin), lô **chiến dịch** không cần tránh lịch BT-HC-TI (stage_code 8) để tối ưu thời gian chiến dịch; BT-HC-TI được dời sau đó. **Lô lẻ vẫn tránh** BT-HC-TI trong lõi.

**Lõi:**
- `protected bool $campaignSkipsMaintenance` = `config('scheduling.campaign_skip_maintenance', true)`, bật ở `runScheduleAll`, `runScheduleAllPass2`, `WipAwareScheduler::configure`.
- `scheduleCampaign()` giờ là wrapper: lưu cờ cũ, đặt `$ignorePendingMaintenance = $campaignSkipsMaintenance`, gọi `scheduleCampaignBody()` (thân cũ), `finally` trả cờ. (Đệ quy công đoạn sau vẫn gọi `scheduleCampaign` → qua wrapper.)
- `loadRoomAvailability()`: khi `$ignorePendingMaintenance` thì cả 2 truy vấn (lô lẻ, nhóm theo campaign) thêm `where(stage_code != 8 OR actual_start IS NOT NULL)` → BT-HC-TI đang làm (đã nhận phòng) vẫn chặn.
- Sau khi sắp: `shiftMaintenanceAfterSchedule()` → `app(MaintenanceShiftService::class)->shiftOverlapping(production, 'Dời BT-HC-TI sau lịch sản xuất (sắp lịch tự động)')`.

**`App\Services\MaintenanceShiftService::shiftOverlapping($production, $typeOfChange)`:**
1. BT-HC-TI chờ dời: stage 8, `active=1, finished=0, actual_start IS NULL, start >= now`, có `resourceId`; join `plan_master` lấy `expected_date`, `actual_batch`.
2. Khối bận theo phòng = dòng stage 3..7 (`active=1, finished=0`, `COALESCE(end_clearning,end) > now`) + `RoomOccupancyService::heldUntil()`.
3. Nhóm BT theo `resourceId|start|end|end_clearning` (một lịch nhiều thiết bị) → dời cùng nhau, giữ độ dài và vệ sinh (cộng cùng delta cho start/end/start_clearning/end_clearning).
4. Không bị đè → bỏ qua, TRỪ khi lịch từng bị bước này dời (có history `type_of_change LIKE HISTORY_PREFIX%`) mà đang trễ hạn → thử kéo về khe sớm hơn.
5. Bị đè → khe trống sớm nhất từ giờ cũ trở đi (nhảy tới cuối khối đè, lặp). **Được rơi vào ngày nghỉ** – bảo trì vốn hay xếp T7/CN (bản đầu cấm ngày nghỉ đã dời nhầm 22 lịch cố ý đặt cuối tuần).
6. Hạn BT `dueLimit()`: ngày tới hạn đọc từ title `Ngày tới hạn: dd/mm/yyyy` (không có thì `plan_master.expected_date`); `_HC` +0 ngày, `actual_batch = 'Monthly'` +7, loại khác +21 (giống `MaintenanceSchedualController::autoSchedual`). Trễ = ngày bắt đầu > limit.
7. Dời ra sau mà trễ → `latestGapBefore()`: khe trống muộn nhất trước giờ cũ (bội 15 phút, từ now) đủ dài; nếu ngày bắt đầu ≤ limit thì dùng. Không có → vẫn dời ra sau (nếu bị đè) hoặc giữ nguyên, báo `late: true`.
8. Ghi `stage_plan` + `StagePlanHistory::record` (version tra một lần cho cả nhóm id – bảng history không có index `stage_plan_id`).
9. Trả về `[{room, title, from, to, rows, due, late}]`; `to < from` = dời sớm, `to == from && late` = kẹt.

**Config:** `scheduling.campaign_skip_maintenance` (mặc định true; false = chiến dịch tránh BT như cũ, bước dời vẫn chạy).
**⚠️ Lệch quy tắc trên Gantt:** `FullCalender.jsx` tô viền đỏ BT trễ dùng `props.Inst_sch_type`, nhưng backend đã comment cột `quota_maintenance.Inst_sch_type` → Gantt luôn gia hạn 21 ngày kể cả Monthly. Trang Lịch Bảo Trì (`MaintenanceCalender .jsx`) và service dùng `actual_batch` (đúng hơn). Chưa sửa Gantt (đã đề xuất, chờ người dùng).
**Kết quả thử PXV1:** dời 21 lịch, 0 BT còn bị đè, ĐG trễ ngày cần hàng 219 → 193 lô.

### 6.3. Phòng Đang Nhận Phòng Bận Tới Giờ Dự Kiến Kết Thúc (`heldUntil`)

`loadRoomAvailability()` thêm khối `[received, expectedEnd]` từ `RoomOccupancyService::heldUntil()` (cache trong `$this->heldUntil`): lô đã Nhận phòng (actual_start ≥ TRACK_FROM) chưa Trả phòng → bận tới `actual_start + thời lượng kế hoạch`, nhưng không sớm hơn now. Trước đó phòng bị coi trống từ now khi lô chạy quá giờ.

### 6.4. Chiến Dịch: Mốc Sớm Nhất Lấy Từ MỌI Lô

Trong `scheduleCampaignBody`, `$candidates` lấy ngày MUỘN NHẤT của mọi lô trong campaign: stage ≤ 6 `after_weigth_date`, `allow_weight_before_date`; stage 7 `after_parkaging_date`. Lỗi cũ: điều kiện ngày được phép cân kiểm tra biến `$task` không tồn tại nên luôn bỏ qua, và chỉ xét lô đầu (sự cố Stadlacil 071026, 08/10/2026).

### 6.5. Hạn Chế Xuống Khuôn (ĐG)

Chọn phòng = phòng có giờ bắt đầu sớm nhất. Riêng stage 7 khi `limit_mold_change` bật: phòng có khuôn liền trước trùng mã khuôn được ưu tiên nếu bắt đầu trễ hơn phòng tốt nhất ≤ `mold_change_tolerance` giờ (mặc định 72).

### 6.6. Quyết Định Của Người Dùng – KHÔNG Làm Lại

| Ngày | Đã thử / đề xuất | Quyết định |
| --- | --- | --- |
| 08/10/2026 | Tự tách chiến dịch trễ hạn thành 2 trong lõi (`trySplitCampaign`, mã `_S`) | **Gỡ hẳn. Không tách chiến dịch trong lõi.** |
| 08/10/2026 | Plugin Drum–Buffer–Rope (sắp ngược PC/THT/ĐH theo BP/ĐG) | **Gỡ hẳn**, kiểm soát BTP kém. Không đề xuất sắp ngược kiểu này. |
| 08/10/2026 | Phương án 1: chỉ đổi thứ tự trong nhóm bán thành phẩm theo hạn | Người dùng chọn **phương án 2** (chen + sắp lại, mục 6.1). |
| 10/2026 | Checkbox "cho phép vi phạm" 7 ngày NL/BB trong plugin | **Bỏ**: 7 ngày luôn là hạn cứng. |
| 08/10/2026 | Dòng "Khả thi / Không khả thi" trên modal kết quả BTP | Bỏ. |

### 6.7. Bẫy Đã Gặp

- **DB local là dữ liệu thật**: test ghi phải trong transaction + rollback. Không chạy `php artisan migrate` trần (bảng migrations lệch) – dùng `--path`.
- **`stage_plan_history` (~150k dòng) không có index `stage_plan_id`**: tra version từng dòng là quét cả bảng → luôn tra một lần cho cả nhóm id (xem `RoomSequencer::$historyVersion`, `MaintenanceShiftService`).
- **Vệ sinh kéo qua ngày nghỉ đè lô sau**: lô lẻ chèn vào khe ngay trước một chiến dịch, `end_clearning` bị kéo dài qua ngày nghỉ, lấn lô kế tiếp (1–3 cặp mỗi lần chạy PXV1). Lỗi cũ của lõi, chưa sửa.
- **`skipOffTime($time, $offDates, $roomId)`**: `$busyList = $this->loadRoomAvailability(...)` nhận `void` → phần kiểm tra phòng bận trong hàm này không chạy; chỉ ngày nghỉ có tác dụng.
- **Lịch lý thuyết có sẵn chồng giờ** (~234 cặp/30 ngày) – đừng coi mọi chồng giờ là lỗi mới; so trước/sau.
- **Carbon 3 `createFromTimestamp` trả UTC** – dùng `date()`/`strtotime()` hoặc set timezone.

### 6.8. Cách Test

- `scratch/hard_deadline_test.php [PXV1]`: trong transaction, `deActiveAll` (xoá lịch từ now) → `scheduleAll` → in thời gian, `hard_deadline`, chỉ số (đè giờ, sai thứ tự, ĐG trễ ngày cần hàng, trễ hạn PC..ĐG), số BT-HC-TI đã dời / còn bị đè, vi phạm 5 mốc → ROLLBACK.
  - `RERUNS=0` tắt chen theo hạn; `NOSKIP=1` tắt chiến dịch xuyên BT; `OV=1` in cặp đè giờ; `ROOM=<id>` in lịch phòng; `WATCH=<plan_master_id>`.
- Session test cần: `production_code`, `fullName`, `userId`, `department`, `userGroup`.
- Sau test kiểm tra: `stage_plan where schedualed_by like 'TEST%'` = 0 và `information_schema.innodb_trx` rỗng.


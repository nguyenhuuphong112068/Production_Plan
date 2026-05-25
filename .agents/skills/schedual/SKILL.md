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

---

## 5. Logic Sắp Lịch Sản Xuất Tự Động (Auto-Scheduling)

Toàn bộ logic nằm trong file [SchedualController.php](file:///c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php).

### 5.1. Tổng Quan Kiến Trúc

Hệ thống sắp lịch tự động sử dụng thuật toán **Forward Scheduling** (đẩy tiến từ ngày bắt đầu). Điểm vào chính là hàm `scheduleAll()` nhận request từ frontend và điều phối toàn bộ quy trình.

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

#### Luồng Xử Lý Chính

```
scheduleAll()
│
├── 1. Khởi tạo: selectedDates, work_sunday, reason, prev_orderBy
├── 2. loadOffDate('asc')  → Tải và gộp ngày nghỉ thành các khoảng off
├── 3. start_date = request.start_date hoặc now() lúc 06:00
│
├── [Nếu selectedStep == 'CNL']
│   └── scheduleWeightStage(start_date) → return
│
├── [Nếu runType == 'line']
│   └── scheduleLine(lines, stage_plan_ids, stage_code, 0, 0, start_date) → return
│
├── 4. Vòng lặp NGƯỢC (selectedStep → 3): 
│   └── scheduleIntermediate(i, 0, 0, start_date)
│       → Sắp lịch các lô BÁN THÀNH PHẨM (đã có predecessor_start)
│
├── 5. Vòng lặp XUÔI (3 → selectedStep):
│   └── scheduleSensitiveProduct(i, 0, 0, start_date)
│       → Sắp lịch các sản phẩm NHẠY CẢM (quarantine_total > 0)
│
└── 6. Vòng lặp XUÔI theo stage_plan (3 → selectedStep):
    └── Auto_scheduler_Stage_Forward(i, wt_normal, wt_val, start_date)
        → Sắp lịch tất cả các lô còn lại chưa được xếp
```

**Ý nghĩa thứ tự ưu tiên:**
1. **Bán thành phẩm** (Intermediate) được xếp trước vì chúng đã có công đoạn trước (predecessor) đã chạy → cần xếp tiếp nhanh.
2. **Sản phẩm nhạy cảm** (Sensitive Product) có `quarantine_total > 0` → phải trừ lùi ngày giao hàng để đảm bảo kịp biệt trữ.
3. **Tất cả lô còn lại** được xếp theo thứ tự `order_by`.

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

Tương tự `sheduleNotCampaing()`, nhưng thêm logic đặc biệt cho **pipeline balancing** giữa campaign hiện tại và campaign công đoạn trước:

```
Nếu campaign trước tồn tại:
│
├── Lấy avg cycle time (m_time) của cả 2 stage (prev và current)
│
├── [Nếu currCycle >= prevCycle]
│   └── candidate = pred.end + waite_time  (chờ đủ)
│
└── [Nếu currCycle < prevCycle] (công đoạn sau nhanh hơn)
    └── candidate = pre_campaign_last_batch.end - (count-1) × currCycle
        → Đẩy lùi để các lô "đuổi kịp" nhau, tránh pipeline starvation
```

> **Pipeline Balancing**: Nếu công đoạn sau xử lý nhanh hơn công đoạn trước, hệ thống tự tính offset để batch cuối của cả 2 campaign kết thúc gần nhau.

#### Bước 2: Chọn Phòng

Giống `sheduleNotCampaing()`, với thêm logic:
- **Liên hệ PC → THT (stage 3 → 4)**: Nếu phòng PC trước là room `6, 7` → ưu tiên phòng THT `13, 14`. Nếu PC ở room `10` → ưu tiên THT room `17`. Rollback nếu filter trống.

#### Bước 3: Tính Tổng Thời Gian Campaign

```
totalMinutes = p_time + (count × m_time) + (count - 1) × C1_time + C2_time
```

> Nếu `totalTimeCampaign` (từ campaign trước) > `totalMinutes` → dùng `totalTimeCampaign` để đảm bảo phòng được book đủ lâu.

#### Bước 4: Tìm Slot & Lưu Từng Batch

```
foreach campaignTasks:
│
├── Kiểm tra pred.end → đẩy bestStart nếu predecessor chưa xong
├── skipOffTime(bestStart) → nhảy qua ngày nghỉ
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
├── saveSchedule(first_in_campaign, task.id, bestRoom, bestStart, bestEnd, ...)
│
└── bestStart = bestEndCleaning  → batch kế tiếp bắt đầu ngay sau vệ sinh
```

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

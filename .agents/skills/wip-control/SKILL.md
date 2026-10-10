---
name: Kiểm Soát Tồn BTP (WipControl plugin)
description: Plugin app/Plugins/WipControl giới hạn tồn bán thành phẩm (Chờ ĐH / Chờ BP / Chờ ĐG) ngay sau Sắp lịch tự động bằng cơ chế "ngưng nguồn – cho lùi". Đọc trước khi sửa WipThrottleService, WipGate, RoomSequencer, WipAwareScheduler, WipCoverageService hoặc resources/js/Plugins/WipControl. Thay đổi nguyên lý phải cập nhật skill này + tạo phiên bản tài liệu mới trong storage/app/schedualer.
---

# Kiểm Soát Tồn BTP (plugin WipControl)

> **Quy tắc bắt buộc** (người dùng yêu cầu 10/10/2026): thay đổi/bổ sung nguyên lý của plugin → cập nhật skill này VÀ tạo phiên bản mới của tài liệu Nguyên lý trong `storage/app/schedualer/` (quy trình: `storage/app/schedualer/README.md`, chi tiết ở mục 0 của skill `schedual`). Bản giải thích cho khách hàng: `storage/app/schedualer/nguyen-ly/<phiên bản mới nhất>/`.

## 1. Mục Đích Và Vị Trí Trong Luồng

- Sắp lịch tự động (lõi, skill `schedual`) xếp mọi công đoạn sớm nhất có thể → PC/THT/ĐH nhanh hơn BP/ĐG (nút cổ chai) → BTP dồn chờ.
- Người dùng nhập **Max (đơn vị liều = viên)** cho 1, 2 hoặc 3 nhóm trong modal Sắp lịch tự động; ô trống = không giới hạn. Không có Min.
- Frontend (`FullCalender.jsx`): `scheduleAll` trả về → nếu `hasWipLimits(wip_control)` và không phải chạy theo line / CNL / mô phỏng → `runWipControl()` gọi `POST /Schedual/wip-control/run` với nguyên payload modal + `wip_control`.
- Bật/tắt toàn plugin: `WIP_CONTROL_ENABLED` trong `.env`, hoặc bỏ `WipControlServiceProvider` khỏi `bootstrap/providers.php`.

## 2. File

| File | Vai trò |
| --- | --- |
| `app/Plugins/WipControl/WipControlServiceProvider.php` | Đăng ký config, routes, migrations |
| `app/Plugins/WipControl/routes.php` | `GET /Schedual/wip-control/settings`, `POST /Schedual/wip-control/run` (middleware web + CheckLogin) |
| `app/Plugins/WipControl/Http/WipControlController.php` | Đọc Max, lưu `wip_control_settings`, gọi service, ghi `wip_control_runs` |
| `app/Plugins/WipControl/Services/WipThrottleService.php` | Vòng lặp chính: đo tồn → ngưng nguồn → đổi chỗ/giãn → sắp lại → kiểm tra |
| `app/Plugins/WipControl/Services/WipGate.php` | Mô phỏng phòng nguồn theo thời gian, quyết định lô nào được vào / phải đợi / chạy thay |
| `app/Plugins/WipControl/Services/RoomSequencer.php` | Trình tự lô trong một phòng: dời trễ (`shiftPlan`, `maxShift`), đổi chỗ (`swapPlan`), ghi (`apply`) |
| `app/Plugins/WipControl/Services/WipAwareScheduler.php` | `extends SchedualController`: gọi lại các nhóm sắp lịch của lõi + mốc "không sớm hơn" (`extraEarliestStart`) |
| `app/Services/WipCoverageService.php` | Sổ tồn (`ledgers`) 3 nhóm theo ngày – dùng chung với trang Tồn BTP |
| `app/Services/MaintenanceShiftService.php` | Dời BT-HC-TI bị lô đè (chung với lõi, skill `schedual` mục 6.2) |
| `resources/js/Plugins/WipControl/index.js` | Khối nhập Max trong modal, gọi run, modal "Kết quả kiểm soát BTP" |
| `app/Plugins/WipControl/config/wip_control.php` | Config (mục 7) |
| Bảng `wip_control_settings` | Max theo phân xưởng (`max_dh_dvl`, `max_bp_dvl`, `max_dg_dvl`) – KHÔNG dùng `wip_stock_limits` |
| Bảng `wip_control_runs` | Nhật ký mỗi lần chạy |

## 3. Đo Tồn (`WipCoverageService::ledgers`)

- Nhóm nguồn `SOURCE_GROUPS = PC (stage 3+4), DH (5), BP (6)`; nhóm đích `NEXT_GROUPS = DH, BP, DG`.
- Đầu ra nhóm PC lấy ở **công đoạn lớn nhất có thật** (THT, lô không có THT thì PC).
- Lô được cộng vào sổ của **nhóm đích đầu tiên** mà công đoạn sau của nó chạm tới (`nextGroupRows` đi theo `nextcessor_code`, bỏ qua công đoạn cùng nhóm). Hệ quả:

| Nhóm chờ | Tồn tăng khi xong | Tồn giảm khi bắt đầu | Bước ngưng nguồn chặn |
| --- | --- | --- | --- |
| Chờ ĐH | THT (không THT: PC) | ĐH | stage [3,4] – chỉ chạy khi Max ĐH có |
| Chờ BP | ĐH của lô bao phim | BP | stage [5] |
| Chờ ĐG | BP; ĐH lô không bao phim; THT (hoặc PC) lô không ĐH, không BP | ĐG | stage [6] và [5]; THT/PC của lô đi thẳng ĐG chỉ bị chặn khi bước [3,4] chạy (tức Max ĐH cũng có) |

- Dự báo lúc 06:00 mỗi ngày, `horizon_days` = 30 ngày; ngày có tồn > Max = 1 ngày vượt; `total_excess` = tổng phần vượt.
- ⚠️ **Lỗ hổng đã biết (chưa sửa, đã báo người dùng 10/10/2026):** chỉ cài Max ĐG (không cài Max ĐH) thì THT/PC của lô "chỉ PC/THT rồi đóng gói" không bị ngưng. Muốn sửa: cho `gateSteps` chạy [3,4] khi có Max DG, `groupOf` của các lô đó đã là DG.

## 4. Nguyên Lý "Ngưng Nguồn – Cho Lùi" (`gateSteps` / `gateStep` / `WipGate::plan`)

Thứ tự bước trong vòng 1 (từ cuối dây chuyền lên): Max ĐG → `[6]`; Max BP hoặc ĐG → `[5]`; Max ĐH → `[3,4]`.

Mỗi `gateStep($round, $stages)`:
1. Lấy các dòng của công đoạn nguồn trong phạm vi (bỏ PC của lô có THT, bỏ lô `gatePinned`, lô đã chạy / bắt đầu trước ngày sắp lịch bị khoá).
2. `groupOf[pm][stage] = [nhóm, lượng]` từ sổ tồn. Lô thuộc nhóm KHÔNG cài Max = **lô chạy thay** (filler).
3. `WipGate::plan($lots, $events, $blocks, $offRanges, $max, $from, $until)`:
   - Sự kiện trước `$from` gộp thành tồn nền; mô phỏng từng phòng theo thời gian.
   - Lô chỉ được bắt đầu khi tồn dự báo của nhóm nó + lượng lô ≤ Max; không thì đợi, phòng chạy lô filler hoặc lô nhóm khác còn dưới Max.
   - Ép chạy (`forced`) khi: trễ, hoặc tới hạn (`must` từ ngày cần hàng, chỉ khi lô không có cổng), hoặc tới hạn cứng (`hardMust` từ 7 ngày NL/BB).
   - Lô cần hàng muộn nhất đợi trước.
   - Khối cố định trong phòng (`$blocks`): mọi dòng khác + `heldUntil`; **BT-HC-TI chưa bắt đầu không tính** (mục 6).
4. Ghi giờ mới; `repairSuccessors` / `pushLater` đẩy công đoạn sau lùi theo (giữ thứ tự + thời gian chờ, nhảy ngày nghỉ, ghi `bpShifted`, `pushOrigin`).
5. Công đoạn trước được kéo sát lại (`pullOrderAndJustify`, ALAP) để không chuyển tồn sang nhóm phía trước.
6. Phát sinh vi phạm ngày NL/BB → `hardHits` → ném lỗi → rollback bước → lô gốc vào `gateForced` (lần sau ép chạy đúng hạn); đã ép mà vẫn vi phạm → `gatePinned` (giữ giờ cũ). Thử lại tối đa `wip_control.hard_date_retries` (mặc định 8) rồi `gateFailed`.

Các bước khác trong vòng (`run()`): `roomStep` (đổi chỗ khối lô trong phòng nguồn + giãn bằng `RoomSequencer`); nếu cả vòng không dời được gì → `pickLots` → `expandAndHint` (kéo theo cả campaign / lô con đóng gói, tối đa `max_group_lots`) → `unschedule` → `reschedule` bằng `WipAwareScheduler` với mốc sớm nhất → `validateRound` (lô sắp lại không kịp → trả về lịch cũ, `conflictsWith`, tối đa `MAX_FAILURES = 3` lần/lô).

## 5. Luôn Giữ (Không Đánh Đổi)

- **7 ngày NL/BB là hạn cứng, LUÔN bật** (`DATE_RULES`: được phép cân `min` stage 3; HH NL chính `max` stage 3; HH BB `max` stage 7; PC/THT/ĐH/BP trước `max` stage 3/4/5/6) + `STAGE_DEADLINES` (ĐG trước). So theo **ngày** như cảnh báo Gantt. Người dùng đã **bỏ checkbox tuỳ chọn** (10/2026) – không đề xuất lại. Cột `wip_control_settings.date_rules` (migration `2026_10_08_090000`) còn trong DB local nhưng không dùng.
- Chỉ chặn vi phạm **MỚI**: `hardViolation()` bỏ qua nếu giờ cũ đã vi phạm cùng mốc (nếu không, bước ngưng nguồn không bao giờ hội tụ vì lịch xuôi đã có vi phạm sẵn).
- Không đè giờ cùng phòng (trừ `overlap = 1`), đúng thứ tự công đoạn + thời gian chờ, không BẮT ĐẦU trong ngày nghỉ (`skipOff` nhảy tới cuối khoảng nghỉ; được chạy xuyên qua).
- Lô đã chạy / đang chạy, lô bắt đầu trước ngày sắp lịch: khoá. Lô thẩm định VẪN được lùi (`lock_validation = false` cố định). Lô `immediately` vẫn được lùi.

## 6. An Toàn Và Kết Thúc

1. `createUndoPoint()` = `backup_schedualer` (nút Khôi phục) trước vòng đầu có thay đổi. Không dùng `createUndoRestorePoint` của lõi (ghi literal `'$bkcCode'`, không có route).
2. Mỗi vòng trong `DB::beginTransaction()`; lỗi → rollback cả vòng.
3. Lưới an toàn cuối vòng: `startsSnapshot()` trước vòng, `hardNet()` sau vòng → còn vi phạm NL/BB mới → rollback cả vòng, trả state, `status = 'hard_date'`.
4. Dừng: hết vượt (`ok`), quá `max_iterations` (10), quá `time_budget_seconds` (900 s → `timeout`), `stagnant_rounds` (2) vòng không giảm ≥ 0,1 % (`infeasible`), hoặc vòng không dời được gì (`infeasible`).
5. Sau vòng lặp (nếu có ít nhất 1 vòng): `MaintenanceShiftService::shiftOverlapping(..., 'Dời BT-HC-TI sau lịch sản xuất (kiểm soát tồn BTP)')` – plugin bỏ qua BT-HC-TI chưa bắt đầu cho **mọi lô nó dời** (RoomSequencer::room, khối WipGate, tường chắn pushLater `orWhereNotIn stage [3..8]`, kiểm tra đè giờ `brokenLots`), khác lõi (chỉ chiến dịch). Lý do: RoomSequencer xử lý cả phòng theo một trình tự; tách lô lẻ phức tạp. Người dùng chưa yêu cầu đổi.
6. `RoomSequencer::resetHistoryCache()` đầu `run()`; `apply()` ghi history với version nạp một lần (bảng history không có index `stage_plan_id`; trước đây chạy 143 s → 6–30 s).

Report (`finish`): `status, iterations, rounds, before, after, delayed, pulled, skipped, skipped_by_reason, bp_shifted, gate_error, gate_warnings, date_rules, hard_forced, hard_pinned, hard_blocked, undo_code, duration_seconds, out_of_scope, new_overdue, maintenance_shifted` (+ `trace` khi `wip_control.debug`). Modal: `STATUS` trong `index.js` (`ok`, `hard_date`, `infeasible`, `max_iterations`, `timeout`, `skipped`); không hiện dòng "Khả thi / Không khả thi" (người dùng bỏ).

## 7. Config `wip_control.*`

`enabled` (env `WIP_CONTROL_ENABLED`), `default_iterations` = 10, `max_iterations` = 10, `stagnant_rounds` = 2, `horizon_days` = 30, `time_budget_seconds` = 900, `max_group_lots` = 40, `min_shift_minutes` = 60, `safety_buffer_hours` = 24 (cố định, không có ô trên modal), `hard_date_retries` (mặc định 8, đọc bằng `config(..., 8)`), `debug`.

## 8. Quyết Định Của Người Dùng – KHÔNG Làm Lại

| Ngày | Nội dung |
| --- | --- |
| 06/10/2026 | Chỉ Max, không Min; đơn vị viên; Max lưu `wip_control_settings`, không đụng `wip_stock_limits`. |
| 07/10/2026 | Chỉ giữ chế độ **"Ngưng nguồn – cho lùi"** (`bp_strategy` luôn `gate_shift`). Đã xoá mixStep, pullStep, pullNonCoated, ô chọn cách giảm tồn; cột `prioritize_non_coated`, `pull_mode` còn trong bảng nhưng không dùng. Ẩn ô đệm an toàn và số vòng lặp. |
| 07/10/2026 | Mở rộng ngưng nguồn cho cả 3 nhóm; nhóm nào có Max thì được kiểm soát (riêng lẻ, 2 hoặc 3). |
| 08/10/2026 | Plugin riêng **Drum–Buffer–Rope** (sắp ngược) đã thử và **gỡ hẳn** – không đề xuất lại. |
| 08/10/2026 | Bỏ dòng "Khả thi / Không khả thi" trên modal kết quả. |
| 10/2026 | Bỏ checkbox cho phép vi phạm ngày NL/BB → 7 ngày luôn là hạn cứng. |

## 9. Giới Hạn Và Bẫy

- Giảm tồn đổi lại đầu nguồn chạy muộn hơn; Max thấp hơn năng lực BP/ĐG → `infeasible`.
- Tồn từ lô đã chạy PC/THT (khoá) không giảm được; lùi riêng ĐH chỉ chuyển tồn sang Chờ ĐH.
- Lõi sắp lại theo kiểu tiến vào khung PC/THT/ĐH đã kín → lô hay rơi trễ hơn mốc → trả về lịch cũ (phân tích 06/10/2026).
- Lõi xếp mọi dòng `start IS NULL` của phân xưởng → plugin tạm đặt `not_schedule = 1` cho dòng không liên quan (`parkPendingRows`) khi sắp lại.
- `sheduleNotCampaing` xếp xong 1 công đoạn thì xếp lại cả các công đoạn sau tới `max_Step` kể cả đã có lịch → plugin sắp lại theo từng "top" với `max_Step = top`.
- Dữ liệu bất thường (vd. BP có `actual_start` khi PC/THT/ĐH chưa chạy) → giữ nguyên, báo `gate_warnings`.
- Script test cũ của phiên khác có thể giữ khoá `stage_plan` (lỗi 1205) – tra `information_schema.innodb_trx` trước khi sửa code.

## 10. Test

`php -d memory_limit=2G scratch/wip_control_test.php PXV1 <factor|json Max> <vòng>` – đo, chạy trong transaction rồi ROLLBACK, in: đè giờ / bắt đầu ngày nghỉ / vi phạm NL/BB / sai thứ tự (trước, sau, MỚI), số BT-HC-TI đã dời và còn bị đè. Env: `RESTORE=<bkc_code>` (khôi phục tạm bản sao lưu trong transaction), `SHOWBROKEN`, `SHOWLATE`, `WATCHROWS`, `DIAG=<Y-m-d>`. Ví dụ: `... PXV1 0.8 5` = Max bằng 80 % đỉnh hiện tại.

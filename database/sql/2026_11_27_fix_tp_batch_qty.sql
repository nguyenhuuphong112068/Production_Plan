-- =====================================================================
-- 27/11/2026 - Đồng bộ cỡ lô (batch_qty / unit_batch_qty) của Danh mục TP
--              theo Danh mục BTP cho các mã đang hoạt động bị lệch.
-- Mỗi câu UPDATE có điều kiện giá trị cũ: chạy lại lần 2 không ảnh hưởng gì.
-- =====================================================================

-- 0) Xem trước toàn bộ mã TP đang hoạt động lệch cỡ lô với BTP
SELECT f.id, f.finished_product_code, f.intermediate_code,
       f.batch_qty AS tp_batch_qty, f.unit_batch_qty AS tp_unit,
       i.batch_size, i.unit_batch_size,
       i.batch_qty AS btp_batch_qty, i.unit_batch_qty AS btp_unit
FROM finished_product_category f
JOIN intermediate_category i ON i.intermediate_code = f.intermediate_code AND i.active = 1
WHERE f.active = 1
  AND (f.batch_qty <> i.batch_qty
       OR COALESCE(f.unit_batch_qty, '') <> COALESCE(i.unit_batch_qty, ''));

START TRANSACTION;

-- 1) Donestad 10 - TP 31040000055 / BTP IS000000427: 1.000.000 viên -> 100.000 viên (dư 1 số 0)
UPDATE finished_product_category
SET batch_qty = 100000, updated_at = NOW()
WHERE id = 1471 AND finished_product_code = '31040000055' AND batch_qty = 1000000;

-- 2) TP 13191190002 / BTP 13191190002: đơn vị 'NA' -> 'Kg' (số lượng 1500 đã khớp)
UPDATE finished_product_category
SET unit_batch_qty = 'Kg', updated_at = NOW()
WHERE id = 1587 AND finished_product_code = '13191190002' AND unit_batch_qty = 'NA';

-- 3) CẦN XÁC NHẬN TRƯỚC KHI CHẠY (có thể lệch thật do quy cách đóng gói):
--    bỏ dấu "-- " ở đầu câu nào muốn cập nhật theo BTP.

-- TP 31190000204 / BTP ISS00000373 (564 Kg): 30.000 gói -> 150.000 gói
-- UPDATE finished_product_category SET batch_qty = 150000, updated_at = NOW()
-- WHERE id = 1589 AND finished_product_code = '31190000204' AND batch_qty = 30000;

-- TP 32010000018 và 32010000023 / BTP IL000000033 (200 Kg): 4.166 chai -> 3.333 chai
-- UPDATE finished_product_category SET batch_qty = 3333, updated_at = NOW()
-- WHERE id IN (1549, 1710) AND intermediate_code = 'IL000000033' AND batch_qty = 4166;

COMMIT;

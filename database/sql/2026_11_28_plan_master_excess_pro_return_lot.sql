-- =====================================================================
-- 28/11/2026 - Thêm cột "Lô Thu Hồi" cho plan_master.
-- Chỉ chạy 1 lần (chạy lần 2 sẽ báo lỗi "Duplicate column name" - bỏ qua được).
-- =====================================================================

ALTER TABLE plan_master
    ADD COLUMN excess_pro_return_lot TINYINT(1) NOT NULL DEFAULT 0 AFTER additional;

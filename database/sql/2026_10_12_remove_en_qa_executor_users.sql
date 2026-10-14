-- =====================================================================
-- PMS Production Plan - Gỡ tài khoản Executor của EN và QA
-- Ngày    : 2026-10-12
--
-- CÁCH IMPORT:
--   mysql -u <user> -p <database> < 2026_10_12_remove_en_qa_executor_users.sql
--
-- LÝ DO:
--   Từ 12/10/2026 Nhận / Trả phòng (kể cả lịch Bảo trì / Tiện ích / Hiệu chuẩn) do nhân viên phân xưởng thực hiện
--   ở trang Thực Thi Sản Xuất / Nhận – Trả Phòng. EN và QA không còn thao tác, nên bỏ các tài khoản role Executor
--   (do lệnh users:create-executors tạo) của 2 phòng ban này. Tài khoản EN / QA thuộc role khác KHÔNG bị đụng tới.
--
-- LƯU Ý:
--   - Chạy lại nhiều lần vẫn an toàn (không còn dòng nào thì không xóa gì).
--   - Nhật ký audittriallog giữ nguyên (lưu userName, không phụ thuộc id tài khoản).
--   - Nên sao lưu bảng user_management trước khi chạy.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- PHẦN 1 (xem trước): các tài khoản sẽ bị gỡ
-- ---------------------------------------------------------------------
SELECT id, userName, fullName, deparment, groupName, userGroup
  FROM user_management
 WHERE deparment IN ('EN', 'QA') AND userGroup = 'Executor'
 ORDER BY deparment, userName;

-- ---------------------------------------------------------------------
-- PHẦN 2: xóa tài khoản và các dòng gắn với tài khoản
-- ---------------------------------------------------------------------
START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS tmp_en_qa_executors;
CREATE TEMPORARY TABLE tmp_en_qa_executors (id INT PRIMARY KEY)
SELECT id FROM user_management WHERE deparment IN ('EN', 'QA') AND userGroup = 'Executor';

DELETE FROM user_role               WHERE user_id IN (SELECT id FROM tmp_en_qa_executors);
DELETE FROM user_permission         WHERE user_id IN (SELECT id FROM tmp_en_qa_executors);
DELETE FROM user_table_preferences  WHERE user_id IN (SELECT id FROM tmp_en_qa_executors);
DELETE FROM sessions                WHERE user_id IN (SELECT id FROM tmp_en_qa_executors);
DELETE FROM user_management         WHERE id      IN (SELECT id FROM tmp_en_qa_executors);

DROP TEMPORARY TABLE IF EXISTS tmp_en_qa_executors;

COMMIT;

-- ---------------------------------------------------------------------
-- KIỂM TRA SAU KHI CHẠY: phải ra 0
-- ---------------------------------------------------------------------
SELECT COUNT(*) AS con_lai FROM user_management WHERE deparment IN ('EN', 'QA') AND userGroup = 'Executor';

-- =====================================================================
-- HẾT.
-- =====================================================================

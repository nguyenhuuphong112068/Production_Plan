-- =====================================================================
-- PMS Production Plan - Khóa thực thi sản xuất thủ công (nút ở sidebar lịch chờ sắp trên Gantt)
-- Ngày    : 2026-10-06
-- Phạm vi : 2 migration
--   2026_10_06_110000_add_manual_lock_to_scheduling_locks
--   2026_10_06_120000_add_manual_active_at_to_scheduling_locks
--
-- CÁCH IMPORT:
--   mysql -u <user> -p <database> < 2026_10_06_scheduling_locks_manual_lock.sql
--
-- LƯU Ý:
--   - Thêm 4 cột vào bảng scheduling_locks: manual_locked, manual_by, manual_at, manual_active_at.
--     Người sắp lịch bật khóa → trang Thực Thi / Ghi Nhận Sản Xuất của phân xưởng không Nhận / Trả phòng được
--     (tự mở khi không còn thao tác sắp lịch thủ công 10 phút, tối đa 8 giờ). Không ảnh hưởng khóa sắp lịch tự động (is_scheduling).
--   - Chạy lại nhiều lần vẫn an toàn: chỉ thêm cột khi chưa có.
--   - Phải chạy TRƯỚC khi deploy code mới (code đọc các cột này ở trang Thực Thi).
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- PHẦN 1: Thêm cột (bỏ qua nếu đã có)
-- ---------------------------------------------------------------------

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'scheduling_locks' AND COLUMN_NAME = 'manual_locked') = 0,
  'ALTER TABLE `scheduling_locks` ADD COLUMN `manual_locked` TINYINT(1) NOT NULL DEFAULT 0 AFTER `started_at`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'scheduling_locks' AND COLUMN_NAME = 'manual_by') = 0,
  'ALTER TABLE `scheduling_locks` ADD COLUMN `manual_by` VARCHAR(255) NULL AFTER `manual_locked`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'scheduling_locks' AND COLUMN_NAME = 'manual_at') = 0,
  'ALTER TABLE `scheduling_locks` ADD COLUMN `manual_at` DATETIME NULL AFTER `manual_by`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'scheduling_locks' AND COLUMN_NAME = 'manual_active_at') = 0,
  'ALTER TABLE `scheduling_locks` ADD COLUMN `manual_active_at` DATETIME NULL AFTER `manual_at`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- PHẦN 2: Đánh dấu migration đã chạy (để `php artisan migrate` bỏ qua)
-- Bỏ phần này nếu bên bạn quản lý bảng `migrations` theo cách khác.
-- ---------------------------------------------------------------------

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.`migration`, (SELECT MAX(`batch`) + 1 FROM `migrations` b)
  FROM (
    SELECT '2026_10_06_110000_add_manual_lock_to_scheduling_locks' AS `migration`
    UNION ALL SELECT '2026_10_06_120000_add_manual_active_at_to_scheduling_locks'
  ) m
 WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) x WHERE x.`migration` = m.`migration`
 );

-- ---------------------------------------------------------------------
-- KIỂM TRA SAU KHI CHẠY: có 4 cột manual_locked, manual_by, manual_at, manual_active_at
-- ---------------------------------------------------------------------
-- SHOW COLUMNS FROM `scheduling_locks`;

-- =====================================================================
-- HẾT.
-- =====================================================================

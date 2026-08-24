-- =====================================================================
-- PMS Production Plan - Thông Báo Đóng Gói: Khoá/Mở Khoá Mẫu Đóng Gói
-- Ngày    : 2026-08-24
-- Phạm vi : 2 migration
--   2026_08_24_090000_add_lock_to_plan_master_infor_parkaging
--   2026_08_24_090100_add_packaging_notification_lock_permission
--
-- CÁCH IMPORT:
--   mysql -u <user> -p <database> < 2026_08_24_packaging_notification_lock.sql
--
-- LƯU Ý:
--   - Chạy SAU khi bảng plan_master_infor_parkaging (migration 2026_08_20_090000)
--     và permission group 15 (migration 2026_08_20_090200) đã có trên server.
--   - PHẦN 1 (ALTER TABLE thêm cột) chỉ chạy ĐÚNG MỘT LẦN.
--   - PHẦN 2 (thêm quyền) dùng ON DUPLICATE KEY / INSERT IGNORE nên chạy lại
--     nhiều lần vẫn an toàn.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- PHẦN 1: Khoá/mở khoá Mẫu Đóng Gói Sơ Cấp, Thứ Cấp và Lý Do (migration 090000)
-- ever_locked là cờ RIÊNG, không suy ra được từ is_locked: is_locked quay lại
-- 0 mỗi lần mở khoá, còn ever_locked giữ nguyên 1 vĩnh viễn sau lần khoá đầu
-- tiên - dùng để quyết định thời điểm bắt đầu ghi lịch sử nhập liệu.
-- ---------------------------------------------------------------------

ALTER TABLE `plan_master_infor_parkaging`
  ADD COLUMN `is_locked` tinyint(1) NOT NULL DEFAULT 0 AFTER `sampled_confirmed`,
  ADD COLUMN `ever_locked` tinyint(1) NOT NULL DEFAULT 0 AFTER `is_locked`,
  ADD COLUMN `locked_by` varchar(100) DEFAULT NULL AFTER `ever_locked`,
  ADD COLUMN `locked_at` timestamp NULL DEFAULT NULL AFTER `locked_by`;

-- ---------------------------------------------------------------------
-- PHẦN 2: Quyền khoá/mở khoá Thông Báo Đóng Gói (migration 090100)
-- Cấp sẵn cho Admin, giống các quyền packaging_notification_* khác.
-- ---------------------------------------------------------------------

INSERT INTO `permissions` (`permission_group`, `name`, `display_name`, `description`, `created_at`, `updated_at`)
VALUES
  (15, 'packaging_notification_lock', 'Khoá/Mở Khoá Thông Báo Đóng Gói', 'Khoá hoặc mở khoá Mẫu Đóng Gói Sơ Cấp, Thứ Cấp và Lý Do của thông báo đóng gói', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `permission_group` = VALUES(`permission_group`),
  `display_name`     = VALUES(`display_name`),
  `description`      = VALUES(`description`),
  `updated_at`       = NOW();

INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT `r`.`id`, `p`.`id`
  FROM `roles` `r`
  JOIN `permissions` `p` ON `p`.`name` = 'packaging_notification_lock'
 WHERE `r`.`name` = 'Admin';

-- ---------------------------------------------------------------------
-- PHẦN 3: Đánh dấu 2 migration đã chạy (để `php artisan migrate` bỏ qua)
-- Bỏ phần này nếu bên bạn quản lý bảng `migrations` theo cách khác.
-- ---------------------------------------------------------------------

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.`migration`, (SELECT MAX(`batch`) + 1 FROM `migrations` b)
  FROM (
    SELECT '2026_08_24_090000_add_lock_to_plan_master_infor_parkaging' AS `migration`
    UNION ALL SELECT '2026_08_24_090100_add_packaging_notification_lock_permission'
  ) m
 WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) x WHERE x.`migration` = m.`migration`
 );

-- =====================================================================
-- HẾT.
-- =====================================================================

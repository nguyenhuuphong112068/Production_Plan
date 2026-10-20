-- =====================================================================
-- PMS Production Plan - Lịch Sử Đăng Nhập (theo dự án EDL)
-- Ngày    : 2026-10-19
-- Phạm vi : 2 migration
--   2026_10_19_090000_create_login_histories_table
--   2026_10_19_090100_add_login_history_permission
--
-- CÁCH IMPORT:
--   mysql -u <user> -p <database> < 2026_10_19_login_history.sql
--
-- LƯU Ý:
--   - Chạy lại nhiều lần vẫn an toàn (IF NOT EXISTS / ON DUPLICATE KEY / INSERT IGNORE).
--   - Deploy code cùng lúc: LoginController ghi vào login_histories ở mọi lần đăng nhập,
--     thiếu bảng là đăng nhập lỗi.
--   - Quyền login_history_view (trang Toàn Hệ Thống) cấp sẵn cho Admin, QA Manager, Compliance_Officer.
--     Trang "Của Tôi" ai đăng nhập cũng xem được.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- PHẦN 1: Bảng login_histories
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `login_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `userName` varchar(60) NOT NULL COMMENT 'Tên đăng nhập đã nhập (kể cả không tồn tại)',
  `status` varchar(20) NOT NULL COMMENT 'success | failed | locked | pwchange',
  `reason` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `browser` varchar(60) DEFAULT NULL,
  `platform` varchar(60) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `login_at` timestamp NULL DEFAULT NULL,
  `last_seen_at` timestamp NULL DEFAULT NULL COMMENT 'Lần thao tác gần nhất trong phiên',
  `logout_at` timestamp NULL DEFAULT NULL,
  `logout_type` varchar(20) DEFAULT NULL COMMENT 'manual',
  PRIMARY KEY (`id`),
  KEY `login_histories_user_id_index` (`user_id`),
  KEY `login_histories_username_index` (`userName`),
  KEY `login_histories_status_index` (`status`),
  KEY `login_histories_ip_address_index` (`ip_address`),
  KEY `login_histories_session_id_index` (`session_id`),
  KEY `login_histories_login_at_index` (`login_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- PHẦN 2: Quyền xem toàn hệ thống + cấp sẵn
-- ---------------------------------------------------------------------

INSERT INTO `permissions` (`permission_group`, `name`, `display_name`, `description`, `created_at`, `updated_at`)
VALUES
  (0, 'login_history_view', 'Xem Lịch Sử Đăng Nhập', 'Lượt đăng nhập của mọi tài khoản (thành công / sai mật khẩu / bị khoá), phiên đang mở, tài khoản nhập sai bất thường, xuất Excel', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `display_name` = VALUES(`display_name`),
  `description`  = VALUES(`description`),
  `updated_at`   = NOW();

INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT `r`.`id`, `p`.`id`
  FROM `roles` `r`
  JOIN `permissions` `p` ON `p`.`name` = 'login_history_view'
 WHERE `r`.`name` IN ('Admin', 'QA Manager', 'Compliance_Officer');

-- ---------------------------------------------------------------------
-- PHẦN 3: Đánh dấu migration đã chạy
-- ---------------------------------------------------------------------

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.`migration`, (SELECT MAX(`batch`) + 1 FROM `migrations` b)
  FROM (
    SELECT '2026_10_19_090000_create_login_histories_table' AS `migration`
    UNION ALL SELECT '2026_10_19_090100_add_login_history_permission'
  ) m
 WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) x WHERE x.`migration` = m.`migration`
 );

-- =====================================================================
-- HẾT.
-- =====================================================================

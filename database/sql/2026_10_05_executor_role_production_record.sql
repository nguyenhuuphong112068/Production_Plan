-- =====================================================================
-- PMS Production Plan - Role "Người Thực Thi" (Executor) + trang Ghi Nhận Sản Xuất
-- Ngày    : 2026-10-05
-- Phạm vi : 1 migration
--   2026_10_05_090000_add_executor_role_and_production_record_permission
--
-- CÁCH IMPORT:
--   mysql -u <user> -p <database> < 2026_10_05_executor_role_production_record.sql
--
-- LƯU Ý:
--   - Chạy lại nhiều lần vẫn an toàn.
--   - User có role CHÍNH (role đầu tiên khi tạo/sửa user) là Executor: đăng nhập vào thẳng
--     /Schedual/record và không mở được trang nào khác (middleware RestrictExecutor).
--   - Tên role 'Executor' so sánh cứng trong code: KHÔNG đổi tên role này ở /User/role.
--   - Tài khoản phải có userName = MSNV (employees.code) thì mới thấy phòng được phân công.
-- =====================================================================

SET NAMES utf8mb4;

-- PHẦN 1: Quyền vào trang Ghi Nhận Sản Xuất
INSERT INTO `permissions` (`permission_group`, `name`, `display_name`, `description`, `created_at`, `updated_at`)
VALUES
  (6, 'layout_production_record', 'Trang Ghi Nhận Sản Xuất', 'Vào trang Ghi Nhận Sản Xuất: nhận / trả phòng tại các phòng mình đang được phân công trên Lịch Công Tác', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `permission_group` = VALUES(`permission_group`),
  `display_name`     = VALUES(`display_name`),
  `description`      = VALUES(`description`),
  `updated_at`       = NOW();

-- PHẦN 2: Role Executor (roles.name unique → đã có thì bỏ qua)
INSERT IGNORE INTO `roles` (`name`, `display_name`, `description`, `active`, `created_at`, `updated_at`)
VALUES ('Executor', 'Người Thực Thi', 'Người Thực Thi - đăng nhập chỉ thấy trang Ghi Nhận Sản Xuất với các phòng đang được phân công', 1, NOW(), NOW());

-- PHẦN 3: Cấp quyền cho Admin và Executor
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT `r`.`id`, `p`.`id`
  FROM `roles` `r`
  JOIN `permissions` `p` ON `p`.`name` = 'layout_production_record'
 WHERE `r`.`name` IN ('Admin', 'Executor');

-- PHẦN 4: Đánh dấu migration đã chạy
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.`migration`, (SELECT MAX(`batch`) + 1 FROM `migrations` b)
  FROM (
    SELECT '2026_10_05_090000_add_executor_role_and_production_record_permission' AS `migration`
  ) m
 WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) x WHERE x.`migration` = m.`migration`
 );

-- =====================================================================
-- HẾT.
-- =====================================================================

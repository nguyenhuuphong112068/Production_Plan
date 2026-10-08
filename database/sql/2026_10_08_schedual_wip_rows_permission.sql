-- =====================================================================
-- PMS Production Plan - Quyền xem Tồn BTP trên Gantt (plugin RoomWip)
-- Ngày    : 2026-10-08
-- Phạm vi : 1 migration
--   2026_10_08_120000_add_schedual_wip_rows_permission
--   (file nằm ở app/Plugins/RoomWip/database/migrations)
--
-- CÁCH IMPORT:
--   mysql -u <user> -p <database> < 2026_10_08_schedual_wip_rows_permission.sql
--
-- LƯU Ý:
--   - Chạy lại nhiều lần vẫn an toàn (ON DUPLICATE KEY / INSERT IGNORE).
--   - Người có quyền thấy nút [Tồn BTP] trên Lịch Sản Xuất; server chặn (403) nếu thiếu quyền.
--   - Cấp sẵn cho 2 nhóm: Admin, Schedualer. Nhóm khác tự tick ở trang phân quyền
--     (nhóm "Lịch Sản Xuất - Thực Thi").
-- =====================================================================

SET NAMES utf8mb4;

INSERT INTO `permissions` (`permission_group`, `name`, `display_name`, `description`, `created_at`, `updated_at`)
VALUES
  (6, 'schedual_wip_rows', 'Lịch SX: Xem Tồn BTP Trên Gantt', 'Thấy nút [Tồn BTP] trên Lịch Sản Xuất: dòng tổng tồn chờ từng công đoạn và tồn chờ vào từng phòng (slot 1 ngày)', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `permission_group` = VALUES(`permission_group`),
  `display_name`     = VALUES(`display_name`),
  `description`      = VALUES(`description`),
  `updated_at`       = NOW();

INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT `r`.`id`, `p`.`id`
  FROM `roles` `r`
  JOIN `permissions` `p` ON `p`.`name` = 'schedual_wip_rows'
 WHERE `r`.`name` IN ('Admin', 'Schedualer');

-- Đánh dấu migration đã chạy (để `php artisan migrate` bỏ qua)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.`migration`, (SELECT MAX(`batch`) + 1 FROM `migrations` b)
  FROM (SELECT '2026_10_08_120000_add_schedual_wip_rows_permission' AS `migration`) m
 WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) x WHERE x.`migration` = m.`migration`
 );

-- KIỂM TRA (mong đợi: 1 quyền, 2 nhóm Admin, Schedualer)
-- SELECT `p`.`name`, GROUP_CONCAT(`r`.`name`) FROM `permissions` `p`
--   LEFT JOIN `role_permission` `rp` ON `rp`.`permission_id` = `p`.`id`
--   LEFT JOIN `roles` `r` ON `r`.`id` = `rp`.`role_id`
--  WHERE `p`.`name` = 'schedual_wip_rows' GROUP BY `p`.`name`;

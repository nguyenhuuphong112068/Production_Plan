-- =====================================================================
-- PMS Production Plan - Quyền thao tác Nhận phòng / Trả phòng (trang Thực Thi Sản Xuất, Ghi Nhận Sản Xuất)
-- Ngày    : 2026-10-06
-- Phạm vi : 1 migration
--   2026_10_06_100000_add_execution_receive_release_permissions
--
-- CÁCH IMPORT:
--   mysql -u <user> -p <database> < 2026_10_06_execution_receive_release_permissions.sql
--
-- LƯU Ý:
--   - Chạy lại nhiều lần vẫn an toàn (ON DUPLICATE KEY / INSERT IGNORE).
--   - Chạy SAU file 2026_10_06_remove_obsolete_execution_permissions.sql (nếu server chưa xóa 5 quyền cũ).
--   - Code phải được deploy cùng lúc: sau khi deploy, nút Nhận phòng / Trả phòng chỉ hiện với nhóm quyền
--     được cấp các quyền này, và server chặn (403) nếu thiếu quyền.
--   - Cấp sẵn cho 4 nhóm: Admin, Executor, Leader, Production Clerk. Nhóm khác tự tick ở trang phân quyền
--     (nhóm "Lịch Sản Xuất - Thực Thi"). Muốn cấp thêm nhóm khác ngay: sửa danh sách tên ở PHẦN 2.
--   - permission_group = 6, cùng nhóm layout_production_execution.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- PHẦN 1: Thêm 3 quyền
-- ---------------------------------------------------------------------

INSERT INTO `permissions` (`permission_group`, `name`, `display_name`, `description`, `created_at`, `updated_at`)
VALUES
  (6, 'execution_receive', 'Thực Thi SX: Nhận Phòng', 'Bấm [Nhận phòng]: chọn lô / lịch bảo trì và ghi giờ nhận phòng (actual_start)', NOW(), NOW()),
  (6, 'execution_receive_cleaning', 'Thực Thi SX: Nhận Phòng Vệ Sinh Sau Bảo Trì', 'Bấm [Nhận phòng vệ sinh sau BT] khi phòng đã bảo trì xong và chờ vệ sinh (actual_start_clearning)', NOW(), NOW()),
  (6, 'execution_release', 'Thực Thi SX: Trả Phòng', 'Bấm [Trả phòng]: ghi giờ trả phòng (actual_end_clearning / actual_end của lịch bảo trì) và tịnh tuyến lịch nếu công tắc đang bật', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `permission_group` = VALUES(`permission_group`),
  `display_name`     = VALUES(`display_name`),
  `description`      = VALUES(`description`),
  `updated_at`       = NOW();

-- ---------------------------------------------------------------------
-- PHẦN 2: Cấp sẵn cho các nhóm vận hành (tránh gián đoạn sau khi deploy)
-- ---------------------------------------------------------------------

INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT `r`.`id`, `p`.`id`
  FROM `roles` `r`
  JOIN `permissions` `p` ON `p`.`name` IN (
        'execution_receive',
        'execution_receive_cleaning',
        'execution_release'
       )
 WHERE `r`.`name` IN ('Admin', 'Executor', 'Leader', 'Production Clerk');

-- ---------------------------------------------------------------------
-- PHẦN 3: Đánh dấu migration đã chạy (để `php artisan migrate` bỏ qua)
-- Bỏ phần này nếu bên bạn quản lý bảng `migrations` theo cách khác.
-- ---------------------------------------------------------------------

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.`migration`, (SELECT MAX(`batch`) + 1 FROM `migrations` b)
  FROM (
    SELECT '2026_10_06_100000_add_execution_receive_release_permissions' AS `migration`
  ) m
 WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) x WHERE x.`migration` = m.`migration`
 );

-- ---------------------------------------------------------------------
-- KIỂM TRA SAU KHI CHẠY (kết quả mong đợi: 3 quyền, mỗi quyền 4 nhóm)
-- ---------------------------------------------------------------------
-- SELECT `p`.`name`, GROUP_CONCAT(`r`.`name` ORDER BY `r`.`id`) AS `roles`
--   FROM `permissions` `p`
--   LEFT JOIN `role_permission` `rp` ON `rp`.`permission_id` = `p`.`id`
--   LEFT JOIN `roles` `r` ON `r`.`id` = `rp`.`role_id`
--  WHERE `p`.`name` IN ('execution_receive', 'execution_receive_cleaning', 'execution_release')
--  GROUP BY `p`.`name`;

-- =====================================================================
-- HẾT.
-- =====================================================================

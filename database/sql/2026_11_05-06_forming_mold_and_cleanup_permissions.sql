-- =====================================================================
-- PMS Production Plan - Thay đổi CSDL ngày 05/11/2026 và 06/11/2026
-- Phạm vi : 4 migration
--   2026_11_05_090000_add_forming_mold_support
--   2026_11_06_090000_add_deparment_code_to_blister_mold
--   2026_11_06_100000_remove_permissions_of_deleted_features
--   2026_11_06_110000_add_user_guide_view_permission
--
-- CÁCH IMPORT:
--   mysql -u <user> -p <database> < 2026_11_05-06_forming_mold_and_cleanup_permissions.sql
--
-- LƯU Ý:
--   - Chạy lại nhiều lần vẫn an toàn: cột / bảng / chỉ mục đều kiểm tra qua
--     information_schema trước, xoá quyền theo tên (không có thì bỏ qua).
--   - Phần 4 xoá 12 quyền không còn dùng (4 quyền của chức năng đã xoá code
--     + 8 quyền mã không kiểm tra ở đâu), sao lưu trước vào 3 bảng
--     bk_20261106_* để khôi phục khi cần. Kiểm tra xong có thể DROP 3 bảng này.
--   - Phần 5 thêm quyền Xem Hướng Dẫn Sử Dụng (user_guide_view) và cấp sẵn cho
--     MỌI nhóm quyền đang hoạt động (không ai mất quyền xem). Nhóm nào không cần
--     xem thì bỏ tick ở User Policy > Nhóm Phân Quyền. Chưa chạy phần này thì
--     KHÔNG ai thấy menu / nút "?" Hướng Dẫn Sử Dụng.
--   - Ngày nghỉ (off_days) KHÔNG cần SQL: màn hình nhập tay đã bỏ, dữ liệu nay
--     lấy từ lệnh `php artisan offdays:sync` (lịch chạy 05:30 hằng ngày trong
--     routes/console.php -> server phải có cron `schedule:run`).
--   - Danh mục khuôn Định Hình (blister_mold stage_code = 5) và định mức khuôn
--     theo BTP (intermediate_mold) là DỮ LIỆU, file này chỉ tạo cấu trúc; nhập
--     trên giao diện server (Định Mức > Khuôn > Định Hình).
-- =====================================================================

SET NAMES utf8mb4;

-- =====================================================================
-- PHẦN 1 (05/11): Khuôn Định Hình - cột stage_code cho danh mục khuôn
--   7 = khuôn ép vỉ (Đóng Gói), 5 = khuôn Định Hình. Khuôn cũ mặc định 7.
-- =====================================================================

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'blister_mold'
              AND `column_name` = 'stage_code');
SET @sql := IF(@x = 0,
    'ALTER TABLE `blister_mold` ADD COLUMN `stage_code` TINYINT(4) NOT NULL DEFAULT 7 AFTER `code`',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'blister_mold_history'
              AND `column_name` = 'stage_code');
SET @sql := IF(@x = 0,
    'ALTER TABLE `blister_mold_history` ADD COLUMN `stage_code` TINYINT(4) NOT NULL DEFAULT 7 AFTER `code`',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- Bảng định mức khuôn Định Hình theo BTP (intermediate_code)
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `intermediate_mold` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `intermediate_code` VARCHAR(50) NOT NULL,
  `blister_mold_id` BIGINT(20) UNSIGNED NOT NULL,
  `created_by` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `intermediate_mold_intermediate_code_blister_mold_id_unique` (`intermediate_code`, `blister_mold_id`),
  KEY `intermediate_mold_blister_mold_id_foreign` (`blister_mold_id`),
  KEY `intermediate_mold_intermediate_code_index` (`intermediate_code`),
  CONSTRAINT `intermediate_mold_blister_mold_id_foreign`
    FOREIGN KEY (`blister_mold_id`) REFERENCES `blister_mold` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- room.require_mold: phòng Định Hình phải kiểm soát khuôn
-- ---------------------------------------------------------------------

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'room'
              AND `column_name` = 'require_mold');
SET @sql := IF(@x = 0,
    'ALTER TABLE `room` ADD COLUMN `require_mold` TINYINT(1) NOT NULL DEFAULT 0 AFTER `blister_type_code`',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Bật cho S23, S24 (máy đóng nang IMA Zibo) - Định Hình PXV1
UPDATE `room`
   SET `require_mold` = 1
 WHERE `stage_code` = 5
   AND `deparment_code` = 'PXV1'
   AND `code` IN ('S23', 'S24');

-- =====================================================================
-- PHẦN 2 (06/11): Khuôn tách theo phân xưởng - cột deparment_code
--   Dữ liệu cũ: mọi khuôn đang dùng ở PXV1 -> gán PXV1.
-- =====================================================================

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'blister_mold'
              AND `column_name` = 'deparment_code');
SET @sql := IF(@x = 0,
    'ALTER TABLE `blister_mold` ADD COLUMN `deparment_code` VARCHAR(5) NULL DEFAULT NULL AFTER `code`',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'blister_mold_history'
              AND `column_name` = 'deparment_code');
SET @sql := IF(@x = 0,
    'ALTER TABLE `blister_mold_history` ADD COLUMN `deparment_code` VARCHAR(5) NULL DEFAULT NULL AFTER `code`',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `blister_mold`         SET `deparment_code` = 'PXV1' WHERE `deparment_code` IS NULL;
UPDATE `blister_mold_history` SET `deparment_code` = 'PXV1' WHERE `deparment_code` IS NULL;

-- ---------------------------------------------------------------------
-- Mã khuôn chỉ cần không trùng trong cùng phân xưởng:
--   bỏ unique(code) -> thay bằng unique(deparment_code, code)
-- ---------------------------------------------------------------------

SET @x := (SELECT COUNT(*) FROM `information_schema`.`statistics`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'blister_mold'
              AND `index_name` = 'blister_mold_code_unique');
SET @sql := IF(@x > 0, 'ALTER TABLE `blister_mold` DROP INDEX `blister_mold_code_unique`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`statistics`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'blister_mold'
              AND `index_name` = 'blister_mold_dept_code_unique');
SET @sql := IF(@x = 0,
    'ALTER TABLE `blister_mold` ADD UNIQUE KEY `blister_mold_dept_code_unique` (`deparment_code`, `code`)',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================================
-- PHẦN 3 (06/11): Sao lưu quyền sắp xoá (để khôi phục khi cần)
--   Chạy TRƯỚC transaction vì CREATE TABLE tự commit.
--   CREATE ... LIKE + INSERT IGNORE: chạy lại, hoặc bảng sao lưu đã có từ
--   bản file cũ (4 quyền), vẫn sao lưu đủ 12 quyền.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `bk_20261106_permissions`     LIKE `permissions`;
CREATE TABLE IF NOT EXISTS `bk_20261106_role_permission` LIKE `role_permission`;
CREATE TABLE IF NOT EXISTS `bk_20261106_user_permission` LIKE `user_permission`;

INSERT IGNORE INTO `bk_20261106_permissions`
SELECT * FROM `permissions`
 WHERE `name` IN (
    'layout_MMS', 'layout_statistics', 'layout_status',
    'create_intermediate_Hypothesis_category',
    'category_maintenance_create', 'category_maintenance_deActive',
    'plan_maintenance_create', 'create_general_notification',
    'layout_feedback', 'Record_KCS_Date', 'personnel_assignment',
    'layout_monthly_report'
 );

INSERT IGNORE INTO `bk_20261106_role_permission`
SELECT rp.* FROM `role_permission` rp
  JOIN `permissions` p ON p.`id` = rp.`permission_id`
 WHERE p.`name` IN (
    'layout_MMS', 'layout_statistics', 'layout_status',
    'create_intermediate_Hypothesis_category',
    'category_maintenance_create', 'category_maintenance_deActive',
    'plan_maintenance_create', 'create_general_notification',
    'layout_feedback', 'Record_KCS_Date', 'personnel_assignment',
    'layout_monthly_report'
 );

INSERT IGNORE INTO `bk_20261106_user_permission`
SELECT up.* FROM `user_permission` up
  JOIN `permissions` p ON p.`id` = up.`permission_id`
 WHERE p.`name` IN (
    'layout_MMS', 'layout_statistics', 'layout_status',
    'create_intermediate_Hypothesis_category',
    'category_maintenance_create', 'category_maintenance_deActive',
    'plan_maintenance_create', 'create_general_notification',
    'layout_feedback', 'Record_KCS_Date', 'personnel_assignment',
    'layout_monthly_report'
 );

-- =====================================================================
-- PHẦN 4 (06/11): Gỡ 12 quyền không còn dùng
--   a) Quyền của chức năng đã xoá code:
--      layout_MMS        : menu MMS (Tồn kho NL / BB / TP)
--      layout_statistics : menu Thống Kê
--      layout_status     : menu Trạng Thái (bảng tin trạng thái phòng, HPLC)
--      create_intermediate_Hypothesis_category : nút Thêm / Cập Nhật DM Giả Định
--   b) Quyền còn trong bảng nhưng mã không kiểm tra ở đâu (tick hay không
--      đều không có tác dụng):
--      category_maintenance_create, category_maintenance_deActive
--                        : danh mục BT-HC-TI chỉ có nút cập nhật
--      plan_maintenance_create : thêm KH HC-BT dùng plan_maintenance_create_plan_list
--      create_general_notification : thông báo chung không kiểm tra quyền
--      layout_feedback   : menu Phản hồi KHSX không kiểm tra quyền
--      Record_KCS_Date   : ngày KCS thực tế không còn nhập ở trang phản hồi
--      personnel_assignment : điều kiện menu đã bỏ
--      layout_monthly_report : Báo Cáo Tháng đi theo layout_weekly_report
--   GIỮ view_Hypothesis_category (vẫn xem mã giả định cũ).
-- =====================================================================

START TRANSACTION;

DELETE FROM `role_permission`
 WHERE `permission_id` IN (
        SELECT `id` FROM (
            SELECT `id` FROM `permissions`
             WHERE `name` IN (
                'layout_MMS', 'layout_statistics', 'layout_status',
    'create_intermediate_Hypothesis_category',
    'category_maintenance_create', 'category_maintenance_deActive',
    'plan_maintenance_create', 'create_general_notification',
    'layout_feedback', 'Record_KCS_Date', 'personnel_assignment',
    'layout_monthly_report'
             )
        ) AS `removed`
 );

DELETE FROM `user_permission`
 WHERE `permission_id` IN (
        SELECT `id` FROM (
            SELECT `id` FROM `permissions`
             WHERE `name` IN (
                'layout_MMS', 'layout_statistics', 'layout_status',
    'create_intermediate_Hypothesis_category',
    'category_maintenance_create', 'category_maintenance_deActive',
    'plan_maintenance_create', 'create_general_notification',
    'layout_feedback', 'Record_KCS_Date', 'personnel_assignment',
    'layout_monthly_report'
             )
        ) AS `removed`
 );

DELETE FROM `permissions`
 WHERE `name` IN (
    'layout_MMS', 'layout_statistics', 'layout_status',
    'create_intermediate_Hypothesis_category',
    'category_maintenance_create', 'category_maintenance_deActive',
    'plan_maintenance_create', 'create_general_notification',
    'layout_feedback', 'Record_KCS_Date', 'personnel_assignment',
    'layout_monthly_report'
 );

COMMIT;

-- =====================================================================
-- PHẦN 5 (06/11): Quyền Xem Hướng Dẫn Sử Dụng
--   user_guide_view - nhóm 0 "Chức Năng Chung (Menu)": menu Hướng Dẫn Sử Dụng,
--   nút "?" trên thanh trên cùng, mọi trang /userGuide (kiểm tra ở server).
--   Cấp cho mọi nhóm quyền đang hoạt động (roles.active = 1).
-- =====================================================================

START TRANSACTION;

INSERT IGNORE INTO `permissions`
    (`permission_group`, `name`, `display_name`, `description`, `created_at`, `updated_at`)
VALUES
    (0, 'user_guide_view', 'Xem Hướng Dẫn Sử Dụng',
     'Thấy menu Hướng Dẫn Sử Dụng, nút ? trên thanh trên cùng và mở các trang hướng dẫn',
     NOW(), NOW());

INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
  FROM `roles` r
  JOIN `permissions` p ON p.`name` = 'user_guide_view'
 WHERE r.`active` = 1;

COMMIT;

-- =====================================================================
-- PHẦN 6: Đánh dấu 4 migration đã chạy (để `php artisan migrate` bỏ qua)
-- Bỏ phần này nếu bên bạn quản lý bảng `migrations` theo cách khác.
-- =====================================================================

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.`migration`, (SELECT MAX(`batch`) + 1 FROM `migrations` b)
  FROM (
    SELECT '2026_11_05_090000_add_forming_mold_support' AS `migration`
    UNION ALL SELECT '2026_11_06_090000_add_deparment_code_to_blister_mold'
    UNION ALL SELECT '2026_11_06_100000_remove_permissions_of_deleted_features'
    UNION ALL SELECT '2026_11_06_110000_add_user_guide_view_permission'
  ) m
 WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) x WHERE x.`migration` = m.`migration`
 );

-- =====================================================================
-- KIỂM TRA SAU KHI CHẠY
-- =====================================================================
-- 1) Cột mới (mong đợi: 5 dòng)
-- SELECT `table_name`, `column_name`, `column_type`, `column_default`
--   FROM `information_schema`.`columns`
--  WHERE `table_schema` = DATABASE()
--    AND ((`table_name` IN ('blister_mold', 'blister_mold_history')
--          AND `column_name` IN ('stage_code', 'deparment_code'))
--      OR (`table_name` = 'room' AND `column_name` = 'require_mold'));
--
-- 2) Phòng kiểm soát khuôn (mong đợi: S23, S24)
-- SELECT `id`, `code`, `stage_code`, `deparment_code` FROM `room` WHERE `require_mold` = 1;
--
-- 3) Chỉ mục khuôn (mong đợi: còn blister_mold_dept_code_unique, không còn blister_mold_code_unique)
-- SHOW INDEX FROM `blister_mold`;
--
-- 4) Quyền đã gỡ (mong đợi: 0; còn lại 94 quyền nếu CSDL khớp local 06/11)
-- SELECT COUNT(*) FROM `permissions`
--  WHERE `name` IN ('layout_MMS', 'layout_statistics', 'layout_status',
--                   'create_intermediate_Hypothesis_category',
--                   'category_maintenance_create', 'category_maintenance_deActive',
--                   'plan_maintenance_create', 'create_general_notification',
--                   'layout_feedback', 'Record_KCS_Date', 'personnel_assignment',
--                   'layout_monthly_report');

--
-- 5) Quyền Xem Hướng Dẫn Sử Dụng (mong đợi: 1 quyền, số nhóm = số nhóm quyền đang hoạt động)
-- SELECT p.`id`, p.`name`, COUNT(rp.`role_id`) AS `so_nhom`
--   FROM `permissions` p LEFT JOIN `role_permission` rp ON rp.`permission_id` = p.`id`
--  WHERE p.`name` = 'user_guide_view' GROUP BY p.`id`, p.`name`;

-- =====================================================================
-- KHÔI PHỤC QUYỀN (chỉ khi cần, bỏ comment rồi chạy)
-- =====================================================================
-- INSERT IGNORE INTO `permissions`      SELECT * FROM `bk_20261106_permissions`;
-- INSERT IGNORE INTO `role_permission`  SELECT * FROM `bk_20261106_role_permission`;
-- INSERT IGNORE INTO `user_permission`  SELECT * FROM `bk_20261106_user_permission`;
--
-- Kiểm tra ổn thì dọn bảng sao lưu:
-- DROP TABLE IF EXISTS `bk_20261106_permissions`, `bk_20261106_role_permission`, `bk_20261106_user_permission`;

-- =====================================================================
-- HẾT.
-- =====================================================================

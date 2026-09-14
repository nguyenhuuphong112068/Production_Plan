-- =============================================================================
-- File: calibration_maintenance_assessment.sql
-- Mô tả: Tổng hợp 4 migration Laravel thành 1 file SQL để import lên DB server.
--   1) Tạo bảng calibration_maintenance_assessment
--   2) Thêm các cột manual (assessment_date, room_id, equipment_name, ...)
--   3) Thêm cột room_name
--   4) Seed 3 permissions + gán cho role Admin (role_id = 1)
-- Tương thích: MySQL / MariaDB
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. Tạo bảng calibration_maintenance_assessment
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `calibration_maintenance_assessment` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `stage_plan_id` BIGINT UNSIGNED NULL,
    `plan_master_id` BIGINT UNSIGNED NULL,
    `assessment_date` DATETIME NULL,
    `room_id` BIGINT UNSIGNED NULL,
    `room_name` VARCHAR(255) NULL COMMENT 'Khu vực tự nhập khi công việc ngoài kế hoạch không thuộc phòng nào trong danh mục',
    `equipment_name` VARCHAR(255) NULL,
    `type_name` VARCHAR(50) NULL,
    `deparment_code` VARCHAR(50) NULL,
    `star_rating` TINYINT UNSIGNED NOT NULL,
    `comment` TEXT NULL,
    `employees_code` JSON NULL,
    `created_by` VARCHAR(255) NULL,
    `updated_by` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `calibration_maintenance_assessment_stage_plan_id_unique` (`stage_plan_id`),
    INDEX `calibration_maintenance_assessment_plan_master_id_index` (`plan_master_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Seed permissions: Đánh Giá Bảo Trì - Hiệu Chuẩn
-- -----------------------------------------------------------------------------
INSERT INTO `permissions` (`permission_group`, `name`, `display_name`, `description`, `created_at`, `updated_at`)
VALUES
    (1, 'maintenance_assessment_view',             'Xem Đánh Giá BT-HC',                 'Xem trang đánh giá công tác bảo trì - hiệu chuẩn theo tháng',                                    NOW(), NOW()),
    (1, 'maintenance_assessment_create',           'Đánh Giá Công Tác BT-HC',             'Tạo / sửa đánh giá công tác bảo trì - hiệu chuẩn, kể cả đánh giá ngoài kế hoạch',               NOW(), NOW()),
    (1, 'maintenance_assessment_update_employees', 'Cập Nhật Nhân Sự Đánh Giá BT-HC',     'Cập nhật lại danh sách nhân sự liên quan của một đánh giá đã có',                                 NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `display_name`     = VALUES(`display_name`),
    `description`      = VALUES(`description`),
    `permission_group` = VALUES(`permission_group`),
    `updated_at`       = NOW();

-- -----------------------------------------------------------------------------
-- 3. Gán permissions cho role Admin (role_id = 1)
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT 1, `id`
FROM `permissions`
WHERE `name` IN (
    'maintenance_assessment_view',
    'maintenance_assessment_create',
    'maintenance_assessment_update_employees'
);

-- -----------------------------------------------------------------------------
-- 4. Ghi nhận vào bảng migrations (để Laravel biết đã chạy)
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO `migrations` (`migration`, `batch`)
VALUES
    ('2026_09_14_100000_create_calibration_maintenance_assessment_table',                    (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` AS m)),
    ('2026_09_14_150000_add_manual_fields_to_calibration_maintenance_assessment_table',      (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` AS m)),
    ('2026_09_14_160000_add_room_name_to_calibration_maintenance_assessment_table',          (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` AS m)),
    ('2026_09_14_170000_add_maintenance_assessment_permissions',                             (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations` AS m));

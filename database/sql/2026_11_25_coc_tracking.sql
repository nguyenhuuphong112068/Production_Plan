-- =====================================================================================
-- 25/11/2026 - Theo Dõi Xuất Kho EU: cột "Ngày Lập Phiếu COC" do người dùng nhập
--
-- - plan_master_coc: 1 lô (plan_master) = 1 dòng, coc_date = Ngày lập phiếu COC
-- - plan_master_coc_history: vết sửa từng ô (giá trị cũ / mới, người sửa, thời điểm)
-- - Quyền coc_tracking_update (Nhập Ngày Lập Phiếu COC), cấp sẵn cho nhóm Admin.
--   Cấp cho nhóm khác ở trang Phân Quyền (/User/role).
--
-- Giống migration 2026_11_25_140000_create_plan_master_coc_table.php. Chạy lại nhiều lần không sao.
-- =====================================================================================

CREATE TABLE IF NOT EXISTS `plan_master_coc` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `plan_master_id` BIGINT UNSIGNED NOT NULL,
    `coc_date` DATE NULL,
    `updated_by` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `plan_master_coc_plan_master_id_unique` (`plan_master_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `plan_master_coc_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `plan_master_id` BIGINT UNSIGNED NOT NULL,
    `field` VARCHAR(50) NOT NULL,
    `old_value` VARCHAR(255) NULL,
    `new_value` VARCHAR(255) NULL,
    `changed_by` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `plan_master_coc_history_plan_master_id_index` (`plan_master_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`permission_group`, `name`, `display_name`, `description`, `created_at`, `updated_at`)
SELECT 15, 'coc_tracking_update', 'Nhập Ngày Lập Phiếu COC', 'Nhập / sửa Ngày Lập Phiếu COC trên trang Theo Dõi Xuất Kho EU (chỉ lô đã xuất kho)', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'coc_tracking_update');

INSERT INTO `role_permission` (`role_id`, `permission_id`)
SELECT `r`.`id`, `p`.`id`
  FROM `roles` `r`
  JOIN `permissions` `p` ON `p`.`name` = 'coc_tracking_update'
 WHERE `r`.`name` = 'Admin'
   AND NOT EXISTS (SELECT 1 FROM `role_permission` `x` WHERE `x`.`role_id` = `r`.`id` AND `x`.`permission_id` = `p`.`id`);

-- KIỂM TRA
SELECT `p`.`id`, `p`.`name`, COUNT(`rp`.`role_id`) AS `so_nhom_quyen`
  FROM `permissions` `p` LEFT JOIN `role_permission` `rp` ON `rp`.`permission_id` = `p`.`id`
 WHERE `p`.`name` = 'coc_tracking_update'
 GROUP BY `p`.`id`, `p`.`name`;

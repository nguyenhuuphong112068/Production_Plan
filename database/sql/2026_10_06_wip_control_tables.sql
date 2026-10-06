-- Plugin Kiểm soát tồn BTP khi sắp lịch tự động (app/Plugins/WipControl)
-- Tương đương migration app/Plugins/WipControl/database/migrations/2026_10_06_150000_create_wip_control_tables.php

CREATE TABLE IF NOT EXISTS `wip_control_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `production_code` VARCHAR(10) NOT NULL COMMENT 'Mã phân xưởng: PXV1, PXV2...',
  `max_dh_dvl` DECIMAL(18,2) NULL COMMENT 'Max tồn chờ Định hình (đơn vị liều), null = không giới hạn',
  `max_bp_dvl` DECIMAL(18,2) NULL COMMENT 'Max tồn chờ Bao phim (đơn vị liều), null = không giới hạn',
  `max_dg_dvl` DECIMAL(18,2) NULL COMMENT 'Max tồn chờ Đóng gói (đơn vị liều), null = không giới hạn',
  `max_iterations` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `lock_validation` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = không lùi lô thẩm định',
  `updated_by` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wip_control_settings_production_code_unique` (`production_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wip_control_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `production_code` VARCHAR(10) NOT NULL,
  `status` VARCHAR(20) NOT NULL COMMENT 'ok, infeasible, timeout, max_iterations, skipped, error',
  `iterations` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `delayed_lots` INT UNSIGNED NOT NULL DEFAULT 0,
  `undo_code` VARCHAR(60) NULL COMMENT 'bkc_code điểm hoàn tác trong stage_plan_bkc',
  `limits` LONGTEXT NULL COMMENT 'JSON: Max đã dùng',
  `report` LONGTEXT NULL COMMENT 'JSON: báo cáo đầy đủ',
  `duration_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_by` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `wip_control_runs_production_code_index` (`production_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bước "Ưu tiên lô không bao phim" (migration 2026_10_06_160000)
ALTER TABLE `wip_control_settings`
  ADD COLUMN `prioritize_non_coated` TINYINT(1) NOT NULL DEFAULT 1
  COMMENT '1 = khi chờ BP vượt Max: ngưng nguồn lô bao phim, kéo lô không bao phim lên' AFTER `lock_validation`;

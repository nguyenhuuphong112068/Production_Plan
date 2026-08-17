-- =====================================================================
-- PMS Production Plan - Cảnh báo tồn bán thành phẩm giữa các công đoạn
-- Ngày    : 2026-08-14
-- Phạm vi : 5 migration
--   2026_08_14_090000_create_wip_coverage_tables
--   2026_08_14_090100_add_code_indexes_to_stage_plan_table
--   2026_08_14_090200_add_wip_coverage_permissions
--   2026_08_14_100000_add_lowest_stock_to_wip_coverage_snapshots
--   2026_08_14_110000_switch_wip_coverage_to_capacity_basis
--
-- CÁCH IMPORT:
--   mysql -u <user> -p <database> < 2026_08_14_wip_coverage.sql
--
-- LƯU Ý:
--   - 3 bảng dưới đây được viết thẳng ở dạng CUỐI CÙNG sau cả 5 migration,
--     không dựng bảng rồi ALTER lại, vì server chưa hề có 3 bảng này.
--   - File chạy lại được nhiều lần mà không lỗi (bảng, index, quyền đều có
--     kiểm tra tồn tại trước).
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- PHẦN 1: Ngưỡng cảnh báo (migration 090000)
-- Cấu hình trên trang Chính sách sản lượng, mỗi phân xưởng - nhóm công
-- đoạn một dòng.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `wip_coverage_thresholds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `production_code` varchar(10) NOT NULL COMMENT 'Mã phân xưởng: PXV1, PXV2...',
  `stage_group_code` varchar(4) NOT NULL COMMENT 'Nhóm công đoạn nguồn: PC, DH, BP',
  `critical_days` decimal(6,2) DEFAULT NULL COMMENT 'Dưới ngưỡng này là nguy cấp (ngày)',
  `warn_days` decimal(6,2) DEFAULT NULL COMMENT 'Dưới ngưỡng này là cảnh báo (ngày)',
  `horizon_days` smallint(5) unsigned NOT NULL DEFAULT 30 COMMENT 'Số ngày dự báo về phía trước',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wip_threshold` (`production_code`,`stage_group_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- PHẦN 2: Bản chốt tồn 06:00 hằng ngày (migration 090000 + 100000 + 110000)
--
-- load_hours và capacity_basis là của migration 110000: số ngày đáp ứng
-- tính theo định mức giờ máy (quota.m_time) chia cho nhịp chạy đo được của
-- công đoạn tiêu thụ, thay cho cách cũ trừ dần theo lịch đã sắp.
--
-- lowest_stock_dvl / lowest_stock_date là của migration 100000: tồn được
-- tính lại mỗi ngày nên đường tồn lên xuống chứ không chỉ đi xuống, mốc
-- đáng lo là lúc thấp nhất chứ không nhất thiết là lúc chạm 0.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `wip_coverage_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `snapshot_date` date NOT NULL COMMENT 'Ngày chốt',
  `snapshot_at` datetime NOT NULL COMMENT 'Mốc thời gian tính tồn, thường là 06:00',
  `production_code` varchar(10) NOT NULL,
  `stage_group_code` varchar(4) NOT NULL COMMENT 'Nhóm công đoạn giữ tồn: PC, DH, BP',
  `next_stage_group_code` varchar(4) DEFAULT NULL COMMENT 'Nhóm tiêu thụ chính, chỉ để hiển thị nhãn',
  `stock_dvl` decimal(18,2) NOT NULL DEFAULT 0.00 COMMENT 'Lượng tồn quy về đơn vị liều',
  `stock_lots` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Số lô đang nằm tồn',
  `orphan_lots` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Số lô không xác định được công đoạn kế tiếp',
  `load_hours` decimal(12,1) DEFAULT NULL COMMENT 'Giờ máy mà lượng tồn này đặt lên công đoạn tiêu thụ',
  `days_of_cover` decimal(8,2) DEFAULT NULL COMMENT 'Số ngày đáp ứng, rỗng nghĩa là không có nhu cầu',
  `first_shortage_date` date DEFAULT NULL COMMENT 'Ngày tồn cạn',
  `lowest_stock_dvl` decimal(18,2) DEFAULT NULL COMMENT 'Mức tồn thấp nhất trong khoảng dự báo',
  `lowest_stock_date` date DEFAULT NULL COMMENT 'Ngày tồn xuống thấp nhất',
  `top_product_days` decimal(8,2) DEFAULT NULL COMMENT 'Mã BTP cạn sớm nhất',
  `top_product_code` varchar(50) DEFAULT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'ok' COMMENT 'ok | warn | critical | no_demand',
  `horizon_days` smallint(5) unsigned NOT NULL DEFAULT 30,
  `capacity_basis` longtext DEFAULT NULL COMMENT 'JSON: [{stage_code, rooms, hours_per_day, share_per_day, lots_measured, window_days}]',
  `daily_series` longtext DEFAULT NULL COMMENT 'JSON: [{date, demand_dvl, supply_dvl, remaining_dvl}]',
  `computed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wip_snapshot` (`snapshot_date`,`production_code`,`stage_group_code`),
  KEY `idx_wip_snapshot_lookup` (`production_code`,`snapshot_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- PHẦN 3: Chi tiết bản chốt theo mã bán thành phẩm (migration 090000 + 110000)
-- Xoá bản chốt là xoá luôn chi tiết (ON DELETE CASCADE).
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `wip_coverage_snapshot_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `snapshot_id` bigint(20) unsigned NOT NULL,
  `intermediate_code` varchar(50) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL COMMENT 'Đơn vị thật của mã này, ví dụ viên hoặc Kg',
  `stock_dvl` decimal(18,2) NOT NULL DEFAULT 0.00,
  `stock_lots` int(10) unsigned NOT NULL DEFAULT 0,
  `load_hours` decimal(12,1) DEFAULT NULL COMMENT 'Giờ máy mà riêng mã này đặt lên công đoạn tiêu thụ',
  `days_of_cover` decimal(8,2) DEFAULT NULL,
  `last_out_date` date DEFAULT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'ok',
  `batches` longtext DEFAULT NULL COMMENT 'JSON: [{plan_master_id, batch, stage_code, start, qty_dvl}]',
  PRIMARY KEY (`id`),
  KEY `idx_wip_detail_snapshot` (`snapshot_id`),
  CONSTRAINT `fk_wip_detail_snapshot` FOREIGN KEY (`snapshot_id`) REFERENCES `wip_coverage_snapshots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- PHẦN 4: Chỉ mục cho stage_plan (migration 090100)
-- stage_plan.code và stage_plan.nextcessor_code chưa hề có chỉ mục, trong
-- khi QuarantineRoomController và các màn tính tồn đều tự nối bảng qua cặp
-- cột này. Với ~30k dòng, phép nối đó phải quét 30k x 30k.
--
-- MySQL không có CREATE INDEX IF NOT EXISTS nên phải kiểm tra thủ công.
-- Bảng stage_plan hay bị sửa ngoài migration nên bước kiểm tra là cần thiết.
-- Ba lệnh ALTER này chạy khá lâu trên bảng lớn, đừng ngắt giữa chừng.
-- ---------------------------------------------------------------------

SET @idx := (SELECT COUNT(*) FROM `information_schema`.`statistics`
              WHERE `table_schema` = DATABASE() AND `table_name` = 'stage_plan'
                AND `index_name` = 'idx_code');
SET @sql := IF(@idx = 0, 'ALTER TABLE `stage_plan` ADD INDEX `idx_code` (`code`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(*) FROM `information_schema`.`statistics`
              WHERE `table_schema` = DATABASE() AND `table_name` = 'stage_plan'
                AND `index_name` = 'idx_nextcessor_code');
SET @sql := IF(@idx = 0, 'ALTER TABLE `stage_plan` ADD INDEX `idx_nextcessor_code` (`nextcessor_code`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx := (SELECT COUNT(*) FROM `information_schema`.`statistics`
              WHERE `table_schema` = DATABASE() AND `table_name` = 'stage_plan'
                AND `index_name` = 'idx_predecessor_code');
SET @sql := IF(@idx = 0, 'ALTER TABLE `stage_plan` ADD INDEX `idx_predecessor_code` (`predecessor_code`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- PHẦN 5: Quyền xem màn cảnh báo (migration 090200)
-- Nhóm 9, cùng nhóm với layout_warning - "Chức Năng Canh Báo Lịch Vi Phạm".
-- ---------------------------------------------------------------------

INSERT INTO `permissions` (`permission_group`, `name`, `display_name`, `description`, `created_at`, `updated_at`)
VALUES (
  9,
  'layout_wip_coverage',
  'Chức Năng Cảnh Báo Tồn BTP',
  'Xem tồn bán thành phẩm giữa các công đoạn và số ngày đáp ứng cho công đoạn sau',
  NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
  `permission_group` = VALUES(`permission_group`),
  `display_name`     = VALUES(`display_name`),
  `description`      = VALUES(`description`),
  `updated_at`       = NOW();

-- Cấp sẵn cho nhóm Admin. INSERT IGNORE dựa vào khoá chính (role_id, permission_id)
-- nên chạy lại không sinh dòng trùng.
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id`
  FROM `roles` r
  JOIN `permissions` p ON p.`name` = 'layout_wip_coverage'
 WHERE r.`name` = 'Admin';

-- ---------------------------------------------------------------------
-- PHẦN 6: Đánh dấu 5 migration đã chạy (để `php artisan migrate` bỏ qua)
-- Bỏ phần này nếu bên bạn quản lý bảng `migrations` theo cách khác.
-- ---------------------------------------------------------------------

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.`migration`, (SELECT MAX(`batch`) + 1 FROM `migrations` b)
  FROM (
    SELECT '2026_08_14_090000_create_wip_coverage_tables' AS `migration`
    UNION ALL SELECT '2026_08_14_090100_add_code_indexes_to_stage_plan_table'
    UNION ALL SELECT '2026_08_14_090200_add_wip_coverage_permissions'
    UNION ALL SELECT '2026_08_14_100000_add_lowest_stock_to_wip_coverage_snapshots'
    UNION ALL SELECT '2026_08_14_110000_switch_wip_coverage_to_capacity_basis'
  ) m
 WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) x WHERE x.`migration` = m.`migration`
 );

-- =====================================================================
-- HẾT.
-- =====================================================================

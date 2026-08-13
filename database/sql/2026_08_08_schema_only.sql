-- =====================================================================
-- PMS Production Plan - Export CẤU TRÚC (KHÔNG có dữ liệu)
-- Nguồn   : MariaDB 10.4 (local) - database `pms`
-- Ngày    : 2026-08-08
-- Phạm vi : 11 bảng mới + 2 bảng cũ được ALTER
--           (tương ứng các migration 2026_08_06_090000 -> 2026_08_08_140200)
--
-- CÁCH IMPORT trên Ubuntu:
--   mysql -u <user> -p <database> < 2026_08_08_schema_only.sql
--
-- LƯU Ý QUAN TRỌNG (case-sensitive):
--   Ubuntu mặc định lower_case_table_names=0 -> tên bảng PHÂN BIỆT HOA/THƯỜNG.
--   File này đã đặt đúng `plan_master_KCS` và `plan_master_KCS_history`
--   (bản dump gốc từ Windows là chữ thường). ĐỪNG đổi lại thành chữ thường.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- PHẦN 1: 11 BẢNG MỚI
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `assignment_personnel_time_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `assignment_id` bigint(20) unsigned NOT NULL,
  `personnel_id` bigint(20) unsigned NOT NULL,
  `personnel_name` varchar(255) DEFAULT NULL,
  `reported_date` date DEFAULT NULL,
  `production_code` varchar(50) DEFAULT NULL,
  `group_code` varchar(50) DEFAULT NULL,
  `room_name` varchar(255) DEFAULT NULL,
  `old_start` datetime DEFAULT NULL,
  `old_end` datetime DEFAULT NULL,
  `new_start` datetime DEFAULT NULL,
  `new_end` datetime DEFAULT NULL,
  `old_hours` decimal(6,2) DEFAULT NULL,
  `new_hours` decimal(6,2) DEFAULT NULL,
  `changed_by` varchar(100) DEFAULT NULL,
  `changed_by_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `apt_logs_assignment_personnel` (`assignment_id`,`personnel_id`),
  KEY `apt_logs_lookup` (`reported_date`,`production_code`,`group_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `publication_tracking_period` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `year` smallint(5) unsigned NOT NULL,
  `month` tinyint(3) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `deparment_code` varchar(5) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Đang mở',
  `note` text DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pt_period_dept_year_month` (`deparment_code`,`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `publication_tracking_detail` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `period_id` bigint(20) unsigned NOT NULL,
  `category_type` varchar(3) NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `code` varchar(40) NOT NULL,
  `process_code` varchar(40) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `batch_size` varchar(100) DEFAULT NULL,
  `dosage_name` varchar(100) DEFAULT NULL,
  `pharmacist_id` bigint(20) unsigned DEFAULT NULL,
  `pharmacist_name` varchar(255) DEFAULT NULL,
  `decision` tinyint(1) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `decision_by` varchar(100) DEFAULT NULL,
  `decision_at` timestamp NULL DEFAULT NULL,
  `result_by` varchar(100) DEFAULT NULL,
  `result_at` timestamp NULL DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `updated_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pt_detail_period_category` (`period_id`,`category_type`,`category_id`),
  KEY `pt_detail_period_type` (`period_id`,`category_type`),
  CONSTRAINT `fk_pt_detail_period_id` FOREIGN KEY (`period_id`) REFERENCES `publication_tracking_period` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `publication_tracking_task` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `period_id` bigint(20) unsigned NOT NULL,
  `content` varchar(500) NOT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pt_task_period` (`period_id`),
  CONSTRAINT `fk_pt_task_period_id` FOREIGN KEY (`period_id`) REFERENCES `publication_tracking_period` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `publication_tracking_task_item` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `detail_id` bigint(20) unsigned NOT NULL,
  `moved_to_period_id` bigint(20) unsigned DEFAULT NULL,
  `moved_to_item_id` bigint(20) unsigned DEFAULT NULL,
  `moved_at` timestamp NULL DEFAULT NULL,
  `moved_by` varchar(100) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pt_task_item_task_detail` (`task_id`,`detail_id`),
  KEY `pt_task_item_detail` (`detail_id`),
  KEY `pt_task_item_moved_period` (`moved_to_period_id`),
  CONSTRAINT `fk_pt_task_item_detail_id` FOREIGN KEY (`detail_id`) REFERENCES `publication_tracking_detail` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pt_task_item_task_id` FOREIGN KEY (`task_id`) REFERENCES `publication_tracking_task` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `publication_tracking_task_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `period_id` bigint(20) unsigned NOT NULL,
  `action` varchar(20) NOT NULL,
  `old_content` text DEFAULT NULL,
  `new_content` text DEFAULT NULL,
  `detail_id` bigint(20) unsigned DEFAULT NULL,
  `detail_code` varchar(40) DEFAULT NULL,
  `affected_count` smallint(5) unsigned DEFAULT NULL,
  `changed_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pt_task_history_task` (`task_id`,`id`),
  KEY `pt_task_history_period` (`period_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `plan_master_KCS` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plan_master_id` bigint(20) unsigned NOT NULL,
  `record_received_date` date DEFAULT NULL,
  `reader` varchar(100) DEFAULT NULL,
  `record_done_date` date DEFAULT NULL,
  `stock_in_qty` bigint(20) unsigned DEFAULT NULL,
  `kcs_date` date DEFAULT NULL,
  `coatp_number` varchar(50) DEFAULT NULL,
  `coatp_received_date` date DEFAULT NULL,
  `dr_ir` varchar(100) DEFAULT NULL,
  `dr_ir_approval_date` date DEFAULT NULL,
  `oos` varchar(100) DEFAULT NULL,
  `oos_approval_date` date DEFAULT NULL,
  `dr_ir_kcq_approval_date` date DEFAULT NULL,
  `opv_pvr_approval_date` date DEFAULT NULL,
  `note` text DEFAULT NULL,
  `eligible_date` date DEFAULT NULL,
  `completion_days` int(11) DEFAULT NULL,
  `bottleneck` varchar(50) DEFAULT NULL,
  `kcs_pending` int(11) DEFAULT NULL,
  `kcs_year` smallint(5) unsigned DEFAULT NULL,
  `kcs_month` tinyint(3) unsigned DEFAULT NULL,
  `result` varchar(20) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pm_kcs_plan_master` (`plan_master_id`),
  KEY `pm_kcs_year_month` (`kcs_year`,`kcs_month`),
  KEY `pm_kcs_result` (`result`),
  CONSTRAINT `fk_pm_kcs_plan_master_id` FOREIGN KEY (`plan_master_id`) REFERENCES `plan_master` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `plan_master_KCS_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plan_master_id` bigint(20) unsigned NOT NULL,
  `kcs_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(20) NOT NULL,
  `field` varchar(40) NOT NULL,
  `old_value` varchar(255) DEFAULT NULL,
  `new_value` varchar(255) DEFAULT NULL,
  `changed_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pm_kcs_history_plan_master` (`plan_master_id`,`id`),
  KEY `pm_kcs_history_kcs` (`kcs_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `plan_master_bom_version` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plan_master_id` bigint(20) unsigned NOT NULL,
  `order_number` varchar(20) DEFAULT NULL,
  `btp_code` varchar(40) DEFAULT NULL,
  `btp_version` varchar(20) DEFAULT NULL,
  `tp_code` varchar(40) DEFAULT NULL,
  `tp_version` varchar(20) DEFAULT NULL,
  `captured_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pm_bom_version_plan_master` (`plan_master_id`),
  CONSTRAINT `fk_pm_bom_version_plan_master_id` FOREIGN KEY (`plan_master_id`) REFERENCES `plan_master` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `material_source_warning` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `intermediate_code` varchar(20) NOT NULL,
  `material_code` varchar(50) NOT NULL,
  `material_name` varchar(255) DEFAULT NULL,
  `market_id` bigint(20) unsigned NOT NULL,
  `bom_revision` smallint(5) unsigned DEFAULT NULL,
  `note` text DEFAULT NULL,
  `deparment_code` varchar(5) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `prepared_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `msw_unique_matrix` (`deparment_code`,`intermediate_code`,`material_code`,`market_id`),
  KEY `msw_intermediate_code` (`intermediate_code`),
  KEY `msw_material_code` (`material_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `material_source_warning_room` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `warning_id` bigint(20) unsigned NOT NULL,
  `stage_code` smallint(5) unsigned NOT NULL,
  `room_code` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `msw_room_unique` (`warning_id`,`stage_code`,`room_code`),
  CONSTRAINT `msw_room_warning_fk` FOREIGN KEY (`warning_id`) REFERENCES `material_source_warning` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ---------------------------------------------------------------------
-- PHẦN 2: ALTER các bảng đã có sẵn (thêm cột pharmacist_id)
-- migration: 2026_08_06_110000_add_pharmacist_id_to_category_tables.php
-- Chỉ còn 2 bảng bán thành phẩm - xem ghi chú ở cuối phần này.
-- ---------------------------------------------------------------------

ALTER TABLE `intermediate_category`
  ADD COLUMN `pharmacist_id` bigint(20) unsigned DEFAULT NULL AFTER `deparment_code`;

ALTER TABLE `intermediate_category_history`
  ADD COLUMN `pharmacist_id` bigint(20) unsigned DEFAULT NULL;

-- Hai bảng thành phẩm KHÔNG cần cột này nữa: mã TP luôn liên kết đúng một mã BTP
-- qua `intermediate_code` nên dược sĩ phụ trách lấy từ `intermediate_category`.
-- (migration 2026_08_06_110000 có thêm cột cho 2 bảng TP, nhưng
--  migration 2026_08_13_090000 đã xoá lại - ở đây bỏ hẳn cho gọn.)

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- HẾT. Nội dung KHÔNG bao gồm (vì là DỮ LIỆU, không phải cấu trúc):
--   - INSERT vào `permissions` / `role_permission` / `user_permission`
--     của 4 migration phân quyền (140100, 120000, 130000, 140200)
--   - Bản ghi trong bảng `migrations`
--   => Xem ghi chú kèm theo để xử lý riêng.
-- =====================================================================

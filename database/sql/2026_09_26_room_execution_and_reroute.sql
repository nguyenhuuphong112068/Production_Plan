-- =====================================================================
-- PMS Production Plan - Thực Thi Sản Xuất (room_execution_log) &
--                        tịnh tuyến lịch theo thời gian thực
-- Ngày    : 2026-09-26
-- Phạm vi : 5 migration
--   2026_09_26_080000_create_room_execution_log_table
--   2026_09_26_080100_add_execution_indexes_to_stage_plan_and_yields
--   2026_09_26_090000_create_schedule_reroute_settings_table
--   2026_09_26_100000_add_dept_start_index_to_assignments
--   2026_09_26_110000_add_from_execution_to_room_status
--
-- CÁCH IMPORT:
--   mysql -u <user> -p <database> < 2026_09_26_room_execution_and_reroute.sql
--
-- LƯU Ý:
--   - 2 bảng mới: room_execution_log, schedule_reroute_settings.
--   - 3 phần còn lại chỉ ALTER bảng đã có (stage_plan, yields, assignments,
--     room_status) - tự kiểm tra qua INFORMATION_SCHEMA trước khi ALTER
--     (không dùng cú pháp "IF NOT EXISTS" của MariaDB vì server MySQL
--     thuần không hiểu) nên chạy lại nhiều lần vẫn an toàn.
--   - PHẦN 5 có kèm 1 UPDATE dữ liệu (đánh dấu from_execution cho các dòng
--     room_status trang Thực Thi Sản Xuất đã tạo trước khi có cột này).
--     Trên server import lần đầu, room_execution_log chưa có dữ liệu nên
--     UPDATE này không đụng dòng nào - giữ lại chỉ để đúng logic migration
--     gốc (xem database/migrations/2026_09_26_110000_add_from_execution_to_room_status.php).
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- PHẦN 1: Tạo bảng room_execution_log (migration 080000)
-- Nhật ký trạng thái phòng của trang "Thực Thi Sản Xuất". Mỗi dòng là 1
-- khoảng trạng thái của phòng; dòng có ended_at NULL (và chưa hủy) là
-- trạng thái hiện tại. Không dùng room_status.status vì trang "Trạng
-- Thái Phòng" đã dùng mã 0-4 với nghĩa khác, và không ghi actual_start
-- vào stage_plan lúc mới bắt đầu vì lịch Gantt ẩn lô có actual_start mà
-- finished = 0.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `room_execution_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` bigint(20) unsigned NOT NULL,
  `deparment_code` varchar(5) DEFAULT NULL,
  `state` tinyint(3) unsigned NOT NULL COMMENT '1 Phòng sạch, 2 Đang SX, 3 Cần VS, 4 Đang VS, 6 Tạm dừng SX (5 Cần VS lại = phòng sạch quá hạn, chỉ tính khi hiển thị)',
  `stage_plan_id` bigint(20) unsigned DEFAULT NULL,
  `yield_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Dòng yields sinh ra khi đóng khoảng Đang SX',
  `room_status_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Dòng room_status (Báo cáo ngày) sinh ra khi đóng khoảng này',
  `cleaning_level` varchar(10) DEFAULT NULL COMMENT 'VS-I, VS-II, VS-LAI',
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `expired_at` datetime DEFAULT NULL COMMENT 'Hạn phòng sạch',
  `note` varchar(255) DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `ended_by` varchar(100) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rel_room_open` (`room_id`,`ended_at`),
  KEY `idx_rel_stage_plan` (`stage_plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- PHẦN 2: Index phục vụ trang "Thực Thi Sản Xuất" (migration 080100)
-- - stage_plan(resourceId, actual_start): tìm lô thực tế mới nhất / lô kế
--   tiếp của từng phòng (trước đây phải quét cả bảng).
-- - yields(stage_plan_id): tổng sản lượng đã xác nhận của lô (bảng yields
--   chưa có index nào ngoài khóa chính).
-- ---------------------------------------------------------------------

SET @idx_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stage_plan' AND INDEX_NAME = 'idx_sp_resource_actual'
);
SET @ddl := IF(@idx_exists = 0,
  'ALTER TABLE `stage_plan` ADD INDEX `idx_sp_resource_actual` (`resourceId`,`actual_start`)',
  'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'yields' AND INDEX_NAME = 'idx_yields_stage_plan'
);
SET @ddl := IF(@idx_exists = 0,
  'ALTER TABLE `yields` ADD INDEX `idx_yields_stage_plan` (`stage_plan_id`)',
  'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- PHẦN 3: Tạo bảng schedule_reroute_settings (migration 090000)
-- Công tắc "Xác nhận và điều chỉnh lịch theo thời gian thực" theo phân
-- xưởng. Bật thì mỗi lần xác nhận vệ sinh của lô (✓✓ trang Xác nhận hoàn
-- thành, Kết thúc vệ sinh trang Thực Thi Sản Xuất) sẽ tịnh tuyến lịch lý
-- thuyết (ScheduleRerouteService::reroute).
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `schedule_reroute_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `deparment_code` varchar(5) NOT NULL,
  `realtime_reroute` tinyint(1) NOT NULL DEFAULT 0,
  `updated_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `schedule_reroute_settings_deparment_code_unique` (`deparment_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- PHẦN 4: Index assignments(deparment_code, start) (migration 100000)
-- Trang Thực Thi Sản Xuất đọc nhân sự đang được phân công (Lịch Công Tác
-- → Sản Xuất) theo phân xưởng + thời điểm hiện tại. Bảng assignments
-- chưa có index nào ngoài khóa chính nên mỗi lần phải quét toàn bảng;
-- các trang Lịch Công Tác cũng lọc theo deparment_code + start.
-- ---------------------------------------------------------------------

SET @idx_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'assignments' AND INDEX_NAME = 'idx_assignments_dept_start'
);
SET @ddl := IF(@idx_exists = 0,
  'ALTER TABLE `assignments` ADD INDEX `idx_assignments_dept_start` (`deparment_code`,`start`)',
  'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- PHẦN 5: Cột from_execution trên room_status (migration 110000)
-- Đánh dấu hoạt động của Báo cáo ngày (room_status, is_daily_report = 1)
-- do trang Thực Thi Sản Xuất tạo ra: hoạt động khác, khoảng tạm dừng, vệ
-- sinh không gắn lô. Trang Báo cáo ngày không được sửa/xóa các dòng này.
-- ---------------------------------------------------------------------

SET @col_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'room_status' AND COLUMN_NAME = 'from_execution'
);
SET @ddl := IF(@col_exists = 0,
  'ALTER TABLE `room_status` ADD COLUMN `from_execution` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''1 = tạo từ trang Thực Thi Sản Xuất: Báo cáo ngày không được sửa/xóa'' AFTER `is_daily_report`',
  'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Các dòng trang Thực Thi Sản Xuất đã tạo trước khi có cột này: khoảng
-- tạm dừng / vệ sinh gắn với log trạng thái, và hoạt động khai báo trên
-- trang (giờ hệ thống có giây; Báo cáo ngày nhập theo phút nên giây luôn
-- = 0). Không đụng gì nếu room_execution_log chưa có dữ liệu.

SET @first := (SELECT MIN(`created_at`) FROM `room_execution_log`);

UPDATE `room_status` rs
SET rs.`from_execution` = 1
WHERE @first IS NOT NULL
  AND rs.`is_daily_report` = 1
  AND (
    rs.`id` IN (SELECT `room_status_id` FROM `room_execution_log` WHERE `room_status_id` IS NOT NULL)
    OR (rs.`start` >= @first AND SECOND(rs.`start`) <> 0)
  );

-- ---------------------------------------------------------------------
-- PHẦN 6: Đánh dấu 5 migration đã chạy (để `php artisan migrate` bỏ qua)
-- Bỏ phần này nếu bên bạn quản lý bảng `migrations` theo cách khác.
-- ---------------------------------------------------------------------

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.`migration`, (SELECT MAX(`batch`) + 1 FROM `migrations` b)
  FROM (
    SELECT '2026_09_26_080000_create_room_execution_log_table' AS `migration`
    UNION ALL SELECT '2026_09_26_080100_add_execution_indexes_to_stage_plan_and_yields'
    UNION ALL SELECT '2026_09_26_090000_create_schedule_reroute_settings_table'
    UNION ALL SELECT '2026_09_26_100000_add_dept_start_index_to_assignments'
    UNION ALL SELECT '2026_09_26_110000_add_from_execution_to_room_status'
  ) m
 WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) x WHERE x.`migration` = m.`migration`
 );

-- ---------------------------------------------------------------------
-- PHẦN 7: Kiểm tra kết quả
-- ---------------------------------------------------------------------

SELECT TABLE_NAME, TABLE_ROWS
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('room_execution_log', 'schedule_reroute_settings');

SELECT INDEX_NAME, TABLE_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND INDEX_NAME IN ('idx_sp_resource_actual', 'idx_yields_stage_plan', 'idx_assignments_dept_start');

SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'room_status'
  AND COLUMN_NAME = 'from_execution';

-- =====================================================================
-- HẾT.
-- =====================================================================

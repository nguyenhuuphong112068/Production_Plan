-- =====================================================================
-- PMS Production Plan - Thực Thi Sản Xuất: kiểm tra vệ sinh &
--                        nhóm nhiều lô chạy chung 1 lần mở phòng
-- Ngày    : 2026-09-28
-- Phạm vi : 2 migration
--   2026_09_28_100000_add_check_to_room_execution_log
--   2026_09_28_120000_create_room_execution_batch
--
-- CÁCH IMPORT:
--   mysql -u <user> -p <database> < 2026_09_28_room_execution_check_and_batch.sql
--   Hoặc dán thẳng vào ô "SQL" của phpMyAdmin, giữ nguyên dấu phân tách
--   mặc định ";" (KHÔNG cần đổi gì), rồi bấm "Thực hiện".
--
-- AN TOÀN DỮ LIỆU:
--   - Không có câu lệnh DROP/DELETE/TRUNCATE nào trong file này.
--   - CREATE TABLE dùng IF NOT EXISTS -> không đụng bảng đã có.
--   - 3 câu ALTER TABLE ADD COLUMN ở PHẦN 1 là câu lệnh trần, không dùng
--     PROCEDURE hay INFORMATION_SCHEMA (user DB trên server này bị chặn
--     quyền SELECT vào information_schema, lỗi 1044, đã gặp ở lần chạy
--     trước). Nếu bạn chạy file này LẦN THỨ HAI trở đi và cột đã tồn tại
--     sẵn, riêng câu ALTER đó sẽ báo lỗi "Duplicate column name" (1060)
--     -- lỗi này VÔ HẠI, không mất dữ liệu, cứ bỏ qua; phpMyAdmin vẫn
--     tiếp tục chạy các câu lệnh còn lại phía sau.
--   - Phải import 2026_09_26_room_execution_and_reroute.sql trước (PHẦN 1
--     ALTER bảng room_execution_log tạo ở file đó).
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- PHẦN 1: Cột kiểm tra vệ sinh trên room_execution_log (migration 100000)
-- Trạng thái "Chờ Kiểm Tra": người kiểm tra, thời điểm và kết quả được
-- ghi trên chính dòng Chờ kiểm tra.
-- Nếu cột đã tồn tại (chạy lại lần 2+), câu tương ứng sẽ báo lỗi
-- "Duplicate column name" -- bỏ qua, không ảnh hưởng dữ liệu.
-- ---------------------------------------------------------------------

ALTER TABLE `room_execution_log`
  ADD COLUMN `checked_by` varchar(255) DEFAULT NULL AFTER `cancelled_by`;

ALTER TABLE `room_execution_log`
  ADD COLUMN `checked_at` datetime DEFAULT NULL AFTER `checked_by`;

ALTER TABLE `room_execution_log`
  ADD COLUMN `check_result` tinyint(4) DEFAULT NULL COMMENT '1 Đạt, 0 Không đạt' AFTER `checked_at`;

-- ---------------------------------------------------------------------
-- PHẦN 2: Tạo bảng room_execution_batch (migration 120000)
-- Nhóm nhiều lô chạy chung 1 lần mở phòng (Cân NL): mỗi lô kết thúc
-- riêng, vệ sinh chung cho cả nhóm.
-- IF NOT EXISTS -> chạy lại bao nhiêu lần cũng an toàn.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `room_execution_batch` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` bigint(20) unsigned NOT NULL,
  `stage_plan_id` bigint(20) unsigned NOT NULL,
  `open_log_id` bigint(20) unsigned NOT NULL COMMENT 'Dòng room_execution_log lúc mở phòng',
  `started_at` datetime NOT NULL COMMENT 'BĐSX',
  `ended_at` datetime DEFAULT NULL COMMENT 'Lúc bấm kết thúc lô',
  `ended_by` varchar(255) DEFAULT NULL,
  `cleaned_at` datetime DEFAULT NULL COMMENT 'Nhóm đã vệ sinh xong (kiểm tra đạt) / đóng',
  `cancelled_at` datetime DEFAULT NULL COMMENT 'Hủy thao tác mở phòng',
  `created_by` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `room_execution_batch_room_id_cleaned_at_index` (`room_id`,`cleaned_at`),
  KEY `room_execution_batch_stage_plan_id_index` (`stage_plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- PHẦN 3: Đánh dấu 2 migration đã chạy (để `php artisan migrate` bỏ qua)
-- Chỉ INSERT dòng chưa có, không đụng dòng cũ. Bỏ phần này nếu bên bạn
-- quản lý bảng `migrations` theo cách khác.
-- ---------------------------------------------------------------------

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.`migration`, (SELECT MAX(`batch`) + 1 FROM `migrations` b)
  FROM (
    SELECT '2026_09_28_100000_add_check_to_room_execution_log' AS `migration`
    UNION ALL SELECT '2026_09_28_120000_create_room_execution_batch'
  ) m
 WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) x WHERE x.`migration` = m.`migration`
 );

-- ---------------------------------------------------------------------
-- PHẦN 4: Kiểm tra kết quả (dùng SHOW thay vì INFORMATION_SCHEMA, vì
-- user DB trên server này không có quyền SELECT vào information_schema)
-- ---------------------------------------------------------------------

SHOW COLUMNS FROM `room_execution_log` WHERE `Field` IN ('checked_by', 'checked_at', 'check_result');

SHOW TABLES LIKE 'room_execution_batch';

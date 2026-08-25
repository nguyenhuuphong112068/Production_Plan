-- =====================================================================
-- PMS Production Plan - Tồn kho lý thuyết theo công đoạn
-- Ngày    : 2026-08-24
-- Phạm vi : 1 migration
--   2026_08_24_090000_switch_wip_coverage_to_stock_only
--
-- CÁCH IMPORT:
--   mysql -u <user> -p <database> < 2026_08_24_wip_coverage_stock_only.sql
--
-- NỘI DUNG:
--   1. Bỏ hẳn phần "số ngày còn đáp ứng được". Số ngày đó dựa vào định mức
--      giờ máy (quota.m_time) và nhịp chạy đo được của công đoạn sau, cả hai
--      đều là ước lượng nên con số ra được đọc như một lời hứa mà không kiểm
--      chứng được bằng gì trên hiện trường.
--
--   2. Đổi cách gom tồn từ "theo công đoạn NGUỒN" sang "theo công đoạn ĐÍCH".
--      Trước đây mỗi công đoạn nguồn (Pha chế, Định hình, Bao phim) là một
--      dòng riêng. Người dùng thực ra chỉ quan tâm câu hỏi "đang chờ ĐÓNG GÓI
--      bao nhiêu", bất kể lô đó xuất phát từ đâu — hàng đi đủ tuần tự qua Bao
--      phim hay hàng bỏ qua Bao phim/Định hình đều phải cộng chung vào một
--      con số. Nay mỗi dòng là MỘT CÔNG ĐOẠN ĐÍCH (Định hình, Bao phim, Đóng
--      gói), khoá bằng cột next_stage_group_code có sẵn từ migration đầu
--      tiên — trước đây cột này chỉ mang tính hiển thị (nhãn của nguồn nào
--      đông nhất), nay trở thành khoá chính, cột stage_group_code (nguồn) bị
--      bỏ hẳn.
--
-- LƯU Ý:
--   - Bản chốt cũ bị xoá sạch: một dòng cho công đoạn nguồn không tách ngược
--     ra thành các nhóm theo đích được. Lệnh wip:snapshot-coverage chạy
--     06:00 hôm sau sẽ dựng lại.
--   - File chạy lại được nhiều lần mà không lỗi (cột, index, bảng đều có
--     kiểm tra tồn tại trước).
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- PHẦN 1: Xoá bản chốt cũ
-- Chi tiết bị xoá theo qua ON DELETE CASCADE, xoá tường minh cho chắc.
-- ---------------------------------------------------------------------

DELETE FROM `wip_coverage_snapshot_details`;
DELETE FROM `wip_coverage_snapshots`;

-- ---------------------------------------------------------------------
-- PHẦN 2: Bảng wip_coverage_snapshots
--
-- MySQL không có ADD COLUMN / DROP COLUMN IF (NOT) EXISTS ở mọi phiên bản
-- đang chạy, nên mỗi cột đều kiểm tra qua information_schema trước.
-- ---------------------------------------------------------------------

-- Bỏ hai chỉ mục cũ trước, vì unique cũ khoá theo stage_group_code
SET @x := (SELECT COUNT(*) FROM `information_schema`.`statistics`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshots'
              AND `index_name` = 'unique_wip_snapshot');
SET @sql := IF(@x > 0, 'ALTER TABLE `wip_coverage_snapshots` DROP INDEX `unique_wip_snapshot`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`statistics`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshots'
              AND `index_name` = 'idx_wip_snapshot_lookup');
SET @sql := IF(@x > 0, 'ALTER TABLE `wip_coverage_snapshots` DROP INDEX `idx_wip_snapshot_lookup`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Cột nguồn (stage_group_code) và cột đích cũ (next_stage_group_code, vốn
-- nullable) bị bỏ để dựng lại next_stage_group_code làm khoá chính, NOT NULL
SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshots'
              AND `column_name` = 'stage_group_code');
SET @sql := IF(@x > 0, 'ALTER TABLE `wip_coverage_snapshots` DROP COLUMN `stage_group_code`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshots'
              AND `column_name` = 'next_stage_group_code');
SET @sql := IF(@x > 0, 'ALTER TABLE `wip_coverage_snapshots` DROP COLUMN `next_stage_group_code`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshots'
              AND `column_name` = 'orphan_lots');
SET @sql := IF(@x > 0, 'ALTER TABLE `wip_coverage_snapshots` DROP COLUMN `orphan_lots`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshots'
              AND `column_name` = 'load_hours');
SET @sql := IF(@x > 0, 'ALTER TABLE `wip_coverage_snapshots` DROP COLUMN `load_hours`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshots'
              AND `column_name` = 'days_of_cover');
SET @sql := IF(@x > 0, 'ALTER TABLE `wip_coverage_snapshots` DROP COLUMN `days_of_cover`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshots'
              AND `column_name` = 'capacity_basis');
SET @sql := IF(@x > 0, 'ALTER TABLE `wip_coverage_snapshots` DROP COLUMN `capacity_basis`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshots'
              AND `column_name` = 'status');
SET @sql := IF(@x > 0, 'ALTER TABLE `wip_coverage_snapshots` DROP COLUMN `status`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshots'
              AND `column_name` = 'top_product_days');
SET @sql := IF(@x > 0, 'ALTER TABLE `wip_coverage_snapshots` DROP COLUMN `top_product_days`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Cột đích mới, làm khoá chính
SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshots'
              AND `column_name` = 'next_stage_group_code');
SET @sql := IF(@x = 0, 'ALTER TABLE `wip_coverage_snapshots` ADD COLUMN `next_stage_group_code` varchar(4) NOT NULL DEFAULT '''' COMMENT ''Công đoạn mà lượng tồn này đang chờ để bước vào: DH, BP, DG, hoặc NA nếu chưa rõ'' AFTER `production_code`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshots'
              AND `column_name` = 'highest_stock_dvl');
SET @sql := IF(@x = 0, 'ALTER TABLE `wip_coverage_snapshots` ADD COLUMN `highest_stock_dvl` decimal(18,2) DEFAULT NULL COMMENT ''Mức tồn cao nhất trong khoảng dự báo'' AFTER `lowest_stock_date`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshots'
              AND `column_name` = 'highest_stock_date');
SET @sql := IF(@x = 0, 'ALTER TABLE `wip_coverage_snapshots` ADD COLUMN `highest_stock_date` date DEFAULT NULL COMMENT ''Ngày tồn lên cao nhất'' AFTER `highest_stock_dvl`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshots'
              AND `column_name` = 'avg_stock_dvl');
SET @sql := IF(@x = 0, 'ALTER TABLE `wip_coverage_snapshots` ADD COLUMN `avg_stock_dvl` decimal(18,2) DEFAULT NULL COMMENT ''Mức tồn trung bình trong khoảng dự báo'' AFTER `highest_stock_date`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshots'
              AND `column_name` = 'top_product_dvl');
SET @sql := IF(@x = 0, 'ALTER TABLE `wip_coverage_snapshots` ADD COLUMN `top_product_dvl` decimal(18,2) DEFAULT NULL COMMENT ''Lượng tồn của mã BTP giữ nhiều hàng nhất'' AFTER `top_product_code`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Chỉ mục mới, khoá theo next_stage_group_code
SET @x := (SELECT COUNT(*) FROM `information_schema`.`statistics`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshots'
              AND `index_name` = 'unique_wip_snapshot');
SET @sql := IF(@x = 0, 'ALTER TABLE `wip_coverage_snapshots` ADD UNIQUE KEY `unique_wip_snapshot` (`snapshot_date`,`production_code`,`next_stage_group_code`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`statistics`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshots'
              AND `index_name` = 'idx_wip_snapshot_lookup');
SET @sql := IF(@x = 0, 'ALTER TABLE `wip_coverage_snapshots` ADD KEY `idx_wip_snapshot_lookup` (`production_code`,`next_stage_group_code`,`snapshot_date`)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- PHẦN 3: Bảng wip_coverage_snapshot_details
-- ---------------------------------------------------------------------

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshot_details'
              AND `column_name` = 'share_pct');
SET @sql := IF(@x = 0, 'ALTER TABLE `wip_coverage_snapshot_details` ADD COLUMN `share_pct` decimal(6,1) DEFAULT NULL COMMENT ''Phần trăm lượng tồn của nhóm mà riêng mã này chiếm'' AFTER `stock_lots`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshot_details'
              AND `column_name` = 'load_hours');
SET @sql := IF(@x > 0, 'ALTER TABLE `wip_coverage_snapshot_details` DROP COLUMN `load_hours`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshot_details'
              AND `column_name` = 'days_of_cover');
SET @sql := IF(@x > 0, 'ALTER TABLE `wip_coverage_snapshot_details` DROP COLUMN `days_of_cover`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'wip_coverage_snapshot_details'
              AND `column_name` = 'status');
SET @sql := IF(@x > 0, 'ALTER TABLE `wip_coverage_snapshot_details` DROP COLUMN `status`', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- PHẦN 4: Bỏ bảng ngưỡng cảnh báo
-- Ngưỡng nguy cấp / cảnh báo chỉ có nghĩa với số ngày đáp ứng, mục cấu hình
-- ở trang Chính sách sản lượng cũng đã gỡ theo.
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `wip_coverage_thresholds`;

-- ---------------------------------------------------------------------
-- PHẦN 5: Sửa mô tả quyền cho khớp chức năng mới
-- ---------------------------------------------------------------------

UPDATE `permissions`
   SET `display_name` = 'Chức Năng Tồn Kho Lý Thuyết Theo Công Đoạn',
       `description`  = 'Xem thống kê tồn bán thành phẩm lý thuyết đang chờ Định hình, Bao phim hoặc Đóng gói',
       `updated_at`   = NOW()
 WHERE `name` = 'layout_wip_coverage';

-- ---------------------------------------------------------------------
-- PHẦN 6: Đánh dấu migration đã chạy (để `php artisan migrate` bỏ qua)
-- Bỏ phần này nếu bên bạn quản lý bảng `migrations` theo cách khác.
-- ---------------------------------------------------------------------

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.`migration`, (SELECT MAX(`batch`) + 1 FROM `migrations` b)
  FROM (
    SELECT '2026_08_24_090000_switch_wip_coverage_to_stock_only' AS `migration`
  ) m
 WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) x WHERE x.`migration` = m.`migration`
 );

-- =====================================================================
-- HẾT.
-- =====================================================================

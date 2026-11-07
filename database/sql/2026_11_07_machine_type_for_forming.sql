-- =====================================================================
-- PMS Production Plan - Thay đổi CSDL ngày 07/11/2026
-- Phạm vi : migration 2026_11_07_090000_add_stage_code_to_blister_type
--
-- CẦN CHẠY TRƯỚC: 2026_11_05-06_forming_mold_and_cleanup_permissions.sql
--   (cột blister_mold.stage_code, room.require_mold).
--
-- CÁCH IMPORT:
--   mysql -u <user> -p <database> < 2026_11_07_machine_type_for_forming.sql
--
-- NỘI DUNG:
--   - Loại Máy (Dữ Liệu Gốc > Nhóm Máy, trước là "Loại Máy Ép Vỉ") có thêm Công Đoạn:
--     7 = Đóng Gói (máy ép vỉ, dữ liệu cũ), 5 = Định Hình.
--   - Phòng Định Hình có Loại Máy => phòng được kiểm soát trùng khuôn ĐH
--     (thay cho cờ room.require_mold; cột này giữ lại nhưng phần mềm không đọc nữa).
--   - Chuyển dữ liệu: tạo loại máy "IMA Zibo" (Định Hình), gán cho phòng đang
--     bật require_mold (S23, S24) và cho khuôn ĐH chưa có loại máy.
--   - Chạy lại nhiều lần vẫn an toàn.
-- =====================================================================

SET NAMES utf8mb4;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'blister_type'
              AND `column_name` = 'stage_code');
SET @sql := IF(@x = 0,
    'ALTER TABLE `blister_type` ADD COLUMN `stage_code` TINYINT(4) NOT NULL DEFAULT 7 AFTER `code`',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @x := (SELECT COUNT(*) FROM `information_schema`.`columns`
            WHERE `table_schema` = DATABASE() AND `table_name` = 'blister_type_history'
              AND `column_name` = 'stage_code');
SET @sql := IF(@x = 0,
    'ALTER TABLE `blister_type_history` ADD COLUMN `stage_code` TINYINT(4) NOT NULL DEFAULT 7 AFTER `code`',
    'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- Loại máy Định Hình "IMA Zibo" (mã = mã lớn nhất + 1), chỉ tạo khi chưa có
-- ---------------------------------------------------------------------

INSERT INTO `blister_type` (`name`, `code`, `stage_code`, `active`, `created_by`, `created_at`)
SELECT 'IMA Zibo', IFNULL(MAX(`code`), 0) + 1, 5, 1, 'Hệ thống (chuyển từ room.require_mold)', NOW()
  FROM `blister_type`
HAVING NOT EXISTS (SELECT 1 FROM `blister_type` WHERE `name` = 'IMA Zibo');

-- Đã tạo tay "IMA Zibo" trên giao diện cũ (không có Công Đoạn -> mặc định 7): chuyển sang Định Hình,
-- trừ khi mã của nó đang dùng chung với loại máy Đóng Gói khác (khi đó sửa tay trên trang Nhóm Máy).
UPDATE `blister_type` t
  LEFT JOIN (SELECT `code` FROM `blister_type` WHERE `name` <> 'IMA Zibo') o ON o.`code` = t.`code`
   SET t.`stage_code` = 5
 WHERE t.`name` = 'IMA Zibo'
   AND o.`code` IS NULL;

SET @ima := (SELECT `code` FROM `blister_type` WHERE `name` = 'IMA Zibo' AND `stage_code` = 5 LIMIT 1);

-- Phòng ĐH đang bật require_mold (S23, S24) -> loại máy IMA Zibo
UPDATE `room`
   SET `blister_type_code` = @ima
 WHERE `stage_code` = 5
   AND `require_mold` = 1
   AND `blister_type_code` IS NULL;

-- Khuôn ĐH chưa có loại máy -> IMA Zibo (định dạng JSON như khuôn ép vỉ: ["13"])
UPDATE `blister_mold`
   SET `blister_type_code` = CONCAT('["', @ima, '"]')
 WHERE `stage_code` = 5
   AND (`blister_type_code` IS NULL OR `blister_type_code` = '');

-- Kiểm tra sau khi chạy:
-- SELECT `id`, `name`, `code`, `stage_code` FROM `blister_type` ORDER BY `stage_code`, `code`;
-- SELECT `code`, `deparment_code`, `blister_type_code` FROM `room` WHERE `stage_code` = 5 AND `blister_type_code` IS NOT NULL;
-- SELECT `code`, `deparment_code`, `blister_type_code` FROM `blister_mold` WHERE `stage_code` = 5;

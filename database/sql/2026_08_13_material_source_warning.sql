-- =====================================================================
-- PMS Production Plan - Cập nhật CẤU TRÚC ma trận cảnh báo nguồn NL
-- Ngày    : 2026-08-13
-- Phạm vi : 3 migration
--   2026_08_13_100000_create_material_source_warning_market_table
--   2026_08_13_110000_make_material_code_nullable_on_material_source_warning
--   2026_08_13_120000_add_reminder_to_material_source_warning_room
--
-- CÁCH IMPORT:
--   mysql -u <user> -p <database> < 2026_08_13_material_source_warning.sql
--
-- LƯU Ý: chạy SAU file 2026_08_08_schema_only.sql (cần sẵn 2 bảng
--        material_source_warning và material_source_warning_room).
--        File có chuyển dữ liệu cũ nên chỉ chạy ĐÚNG MỘT LẦN.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- PHẦN 1: Tách thị trường ra bảng riêng (migration 100000)
-- Trước đây mỗi dòng ma trận chỉ khai được 1 thị trường nên cùng 1 mã BTP -
-- mã NL phải khai lặp nhiều dòng. Tách ra để 1 dòng khai được nhiều thị
-- trường (VN, HK, HK (Tender)...) đúng như ma trận giấy đang dùng.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `material_source_warning_market` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `warning_id` bigint(20) unsigned NOT NULL,
  `market_id` bigint(20) unsigned NOT NULL,
  -- Kênh/khách hàng của thị trường, vd: Tender. Bảng market chỉ có mã quốc gia
  -- ISO nên biến thể "HK (Tender)" lưu ở đây thay vì thêm mã ảo vào danh mục.
  `channel` varchar(50) NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `msw_market_unique` (`warning_id`,`market_id`,`channel`),
  CONSTRAINT `msw_market_warning_fk` FOREIGN KEY (`warning_id`) REFERENCES `material_source_warning` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chuyển dữ liệu đã khai theo cấu trúc cũ sang bảng mới
INSERT INTO `material_source_warning_market`
  (`warning_id`, `market_id`, `channel`, `created_at`, `updated_at`)
SELECT `id`, `market_id`, '', NOW(), NOW()
  FROM `material_source_warning`
 WHERE `market_id` IS NOT NULL;

-- Unique cũ có market_id nên phải bỏ trước khi xoá cột
ALTER TABLE `material_source_warning`
  DROP INDEX `msw_unique_matrix`,
  DROP COLUMN `market_id`,
  ADD KEY `msw_matrix_lookup` (`deparment_code`,`intermediate_code`,`material_code`);

-- ---------------------------------------------------------------------
-- PHẦN 2: Cho phép bỏ trống mã NL (migration 110000)
-- Mã NL để trống nghĩa là ma trận áp dụng cho mọi nguồn NL của mã BTP đó.
-- Cùng với thị trường/thiết bị để trống, dòng khai báo chỉ ràng buộc đúng
-- phần thông tin đã khai.
-- ---------------------------------------------------------------------

ALTER TABLE `material_source_warning`
  MODIFY COLUMN `material_code` varchar(50) DEFAULT NULL;

-- Dữ liệu cũ có thể đã lưu chuỗi rỗng - đưa về NULL cho thống nhất
UPDATE `material_source_warning` SET `material_code` = NULL WHERE `material_code` = '';

-- ---------------------------------------------------------------------
-- PHẦN 3: Nhắc nhở theo thiết bị (migration 120000)
-- Nội dung nhắc nhở của riêng thiết bị đó, vd: "Khử ẩm".
-- Hiện lên lịch sản xuất khi lô được xếp đúng thiết bị này.
-- ---------------------------------------------------------------------

ALTER TABLE `material_source_warning_room`
  ADD COLUMN `reminder` varchar(255) DEFAULT NULL AFTER `room_code`;

-- ---------------------------------------------------------------------
-- PHẦN 4: Đánh dấu 3 migration đã chạy (để `php artisan migrate` bỏ qua)
-- Bỏ phần này nếu bên bạn quản lý bảng `migrations` theo cách khác.
-- ---------------------------------------------------------------------

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.`migration`, (SELECT MAX(`batch`) + 1 FROM `migrations` b)
  FROM (
    SELECT '2026_08_13_100000_create_material_source_warning_market_table' AS `migration`
    UNION ALL SELECT '2026_08_13_110000_make_material_code_nullable_on_material_source_warning'
    UNION ALL SELECT '2026_08_13_120000_add_reminder_to_material_source_warning_room'
  ) m
 WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) x WHERE x.`migration` = m.`migration`
 );

-- =====================================================================
-- HẾT.
-- =====================================================================

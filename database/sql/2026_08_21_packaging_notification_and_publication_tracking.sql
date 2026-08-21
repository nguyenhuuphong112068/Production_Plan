-- =====================================================================
-- PMS Production Plan - Thông Báo Đóng Gói (lấy mẫu, lịch sử) & Theo Dõi
--                        Công Bố (nội dung dài, hồ sơ sẵn sàng)
-- Ngày    : 2026-08-21
-- Phạm vi : 9 migration
--   2026_08_21_090000_add_is_manual_to_plan_master_infor_parkaging
--   2026_08_21_090000_change_content_to_text_on_publication_tracking_task_table
--   2026_08_21_090100_split_packaging_notification_permissions
--   2026_08_21_100000_create_plan_master_infor_parkaging_history_table
--   2026_08_21_110000_replace_sampling_fields_with_primary_secondary
--   2026_08_21_110100_widen_packaging_history_value_columns
--   2026_08_21_120000_add_sampled_confirmation_to_plan_master_infor_parkaging
--   2026_08_21_130000_drop_sampled_by_date_from_plan_master_infor_parkaging
--   2026_08_21_140000_add_ready_to_publication_tracking_detail
--
-- CÁCH IMPORT:
--   mysql -u <user> -p <database> < 2026_08_21_packaging_notification_and_publication_tracking.sql
--
-- LƯU Ý:
--   - File này thay thế đúng 1 lần cho cả 9 migration ở trên, chạy SAU khi
--     bảng plan_master_infor_parkaging (migration 2026_08_20_090000) đã có
--     trên server.
--   - Bảng plan_master_infor_parkaging_history được viết thẳng ở dạng CUỐI
--     CÙNG (sau cả 2 migration 100000 + 110100, cột old_value/new_value là
--     TEXT ngay từ đầu) vì server chưa hề có bảng này.
--   - Cặp cột sampled_by/sampled_date (migration 120000) bị migration
--     130000 xoá ngay sau đó và chưa từng có dữ liệu thật, nên PHẦN 7 dưới
--     đây bỏ qua luôn 2 cột này, chỉ thêm thẳng sampled_confirmed.
--   - PHẦN 1, 2, 5, 7, 9 (ALTER TABLE thêm/xoá cột) chỉ chạy ĐÚNG MỘT LẦN.
--   - PHẦN 3, 4, 8 dùng ON DUPLICATE KEY / INSERT IGNORE / IF NOT EXISTS
--     nên chạy lại nhiều lần vẫn an toàn.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- PHẦN 1: Đánh dấu dòng thông báo đóng gói tạo tay (migration 090000a)
-- Dòng sinh tự động lúc gửi kế hoạch có is_manual = 0. Dòng người dùng
-- bấm nút "Tạo Thông Báo Khác" có is_manual = 1, nhờ đó lưới vẫn hiển thị
-- được lô nằm ngoài quy tắc và chỉ những dòng này mới cho phép gỡ ra.
-- ---------------------------------------------------------------------

ALTER TABLE `plan_master_infor_parkaging`
  ADD COLUMN `is_manual` tinyint(1) NOT NULL DEFAULT 0 AFTER `plan_list_id`,
  ADD COLUMN `created_by` varchar(100) DEFAULT NULL AFTER `updated_by`;

-- ---------------------------------------------------------------------
-- PHẦN 2: Bỏ giới hạn 500 ký tự của nội dung theo dõi (migration 090000b)
-- Nội dung theo dõi thực tế có thể dài hơn 500 ký tự (mô tả nhiều bước,
-- nhiều lưu ý), nên đổi cột content sang TEXT.
-- ---------------------------------------------------------------------

ALTER TABLE `publication_tracking_task`
  MODIFY COLUMN `content` text NOT NULL;

-- ---------------------------------------------------------------------
-- PHẦN 3: Tách quyền cập nhật Thông Báo Đóng Gói (migration 090100)
-- Cột "Số PO" do bộ phận kế hoạch/đơn hàng nhập, các cột lấy mẫu do bộ
-- phận khác nhập, nên một quyền chung không đủ. Quyền cũ
-- packaging_notification_update được thay bằng 2 quyền mới; ai đang có
-- quyền cũ sẽ được cấp cả hai quyền mới để không ai mất quyền sau khi
-- chạy. Nếu server chưa từng có quyền cũ (cài mới) thì cấp thẳng cho Admin.
-- ---------------------------------------------------------------------

INSERT INTO `permissions` (`permission_group`, `name`, `display_name`, `description`, `created_at`, `updated_at`)
VALUES
  (15, 'packaging_notification_update_po', 'Nhập Số PO Thông Báo Đóng Gói', 'Nhập / sửa cột Số PO của thông báo đóng gói', NOW(), NOW()),
  (15, 'packaging_notification_update_sampling', 'Nhập Thông Tin Lấy Mẫu Thông Báo Đóng Gói', 'Nhập / sửa quy cách, số lần, số lượng, đơn vị lấy mẫu và lý do', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `permission_group` = VALUES(`permission_group`),
  `display_name`     = VALUES(`display_name`),
  `description`      = VALUES(`description`),
  `updated_at`       = NOW();

SET @old_permission_id := (SELECT `id` FROM `permissions` WHERE `name` = 'packaging_notification_update' LIMIT 1);
SET @po_permission_id := (SELECT `id` FROM `permissions` WHERE `name` = 'packaging_notification_update_po' LIMIT 1);
SET @sampling_permission_id := (SELECT `id` FROM `permissions` WHERE `name` = 'packaging_notification_update_sampling' LIMIT 1);

-- Chuyển nguyên trạng phân quyền theo vai trò từ quyền cũ sang cả hai quyền mới
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT `role_id`, @po_permission_id FROM `role_permission` WHERE `permission_id` = @old_permission_id;
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT `role_id`, @sampling_permission_id FROM `role_permission` WHERE `permission_id` = @old_permission_id;

-- Chuyển nguyên trạng phân quyền theo user từ quyền cũ sang cả hai quyền mới, giữ cờ is_denied
INSERT INTO `user_permission` (`user_id`, `permission_id`, `is_denied`)
SELECT `user_id`, @po_permission_id, `is_denied` FROM `user_permission` WHERE `permission_id` = @old_permission_id
ON DUPLICATE KEY UPDATE `is_denied` = VALUES(`is_denied`);
INSERT INTO `user_permission` (`user_id`, `permission_id`, `is_denied`)
SELECT `user_id`, @sampling_permission_id, `is_denied` FROM `user_permission` WHERE `permission_id` = @old_permission_id
ON DUPLICATE KEY UPDATE `is_denied` = VALUES(`is_denied`);

-- Xoá quyền cũ và mọi phân quyền gắn với nó
DELETE FROM `role_permission` WHERE `permission_id` = @old_permission_id;
DELETE FROM `user_permission` WHERE `permission_id` = @old_permission_id;
DELETE FROM `permissions` WHERE `id` = @old_permission_id;

-- Cài mới hoàn toàn (không có quyền cũ để kế thừa): cấp 2 quyền mới cho Admin
INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT `r`.`id`, `p`.`id`
  FROM `roles` `r`
  JOIN `permissions` `p` ON `p`.`name` IN ('packaging_notification_update_po', 'packaging_notification_update_sampling')
 WHERE `r`.`name` = 'Admin' AND @old_permission_id IS NULL;

-- ---------------------------------------------------------------------
-- PHẦN 4: Lịch sử nhập liệu Thông Báo Đóng Gói (migration 100000 + 110100)
-- Mỗi ô thay đổi (Số PO, thông tin lấy mẫu, lý do) là một dòng ghi vết,
-- cùng cấu trúc với plan_master_KCS_history. Cố ý KHÔNG đặt khoá ngoại
-- tới plan_master_infor_parkaging: dòng thêm tay bị gỡ thì vết lịch sử
-- vẫn phải còn; plan_master_id được lưu kèm để tra cứu theo lô kể cả khi
-- dòng gốc đã bị xoá. old_value/new_value là TEXT ngay từ đầu vì
-- primary_sample/secondary_sample (PHẦN 5) có thể dài hơn 255 ký tự.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `plan_master_infor_parkaging_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plan_master_id` bigint(20) unsigned NOT NULL,
  `infor_parkaging_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(20) NOT NULL,
  `field` varchar(50) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `changed_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pm_infor_parkaging_history_plan_master` (`plan_master_id`, `id`),
  KEY `pm_infor_parkaging_history_parkaging` (`infor_parkaging_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- PHẦN 5: Mẫu đóng gói sơ cấp/thứ cấp thay cho 4 ô lấy mẫu rời (migration 110000)
-- Phiếu lấy mẫu QA thật cần 2 bộ dữ liệu song song - sơ cấp (vỉ) và thứ
-- cấp (hộp) - mỗi bộ có thể có nhiều điểm lấy mẫu trong lô với giá trị
-- gộp kiểu "5 hộp + 7 hộp SC". Một ô số + một ô đơn vị không gõ được
-- dạng này, nên đổi sang 2 ô văn bản tự do, gõ nguyên văn như phiếu giấy
-- gốc. Dữ liệu cũ không cần chuyển đổi (dữ liệu thử nghiệm) - vết lịch
-- sử cũ vẫn còn nguyên trong plan_master_infor_parkaging_history.
-- ---------------------------------------------------------------------

ALTER TABLE `plan_master_infor_parkaging`
  DROP COLUMN `Sampling_specifications`,
  DROP COLUMN `Sampling_times`,
  DROP COLUMN `Sampling_amount`,
  DROP COLUMN `sampling_uint`,
  ADD COLUMN `primary_sample` text DEFAULT NULL AFTER `PO_no`,
  ADD COLUMN `secondary_sample` text DEFAULT NULL AFTER `primary_sample`;

-- ---------------------------------------------------------------------
-- PHẦN 6: Xác nhận đã lấy mẫu (migration 120000 + 130000, gộp)
-- Xác nhận đã thật sự lấy mẫu ngoài hiện trường - khác với việc chỉ mô
-- tả kế hoạch lấy mẫu ở primary_sample/secondary_sample. Chỉ giữ cờ xác
-- nhận, không ghi ai lấy/lấy lúc nào (xem LƯU Ý ở đầu file).
-- ---------------------------------------------------------------------

ALTER TABLE `plan_master_infor_parkaging`
  ADD COLUMN `sampled_confirmed` tinyint(1) NOT NULL DEFAULT 0 AFTER `Reason`;

-- ---------------------------------------------------------------------
-- PHẦN 7: Cờ "Hồ sơ sẵn sàng" trên Theo Dõi Công Bố (migration 140000)
-- Dược sĩ phụ trách tự tick khi hồ sơ lô của mã đã ổn, không phụ thuộc
-- nội dung theo dõi / quyết định có hay không. Mặc định false: mã nào
-- chưa được ai xác nhận thì tính là hồ sơ chưa sẵn sàng.
-- ---------------------------------------------------------------------

ALTER TABLE `publication_tracking_detail`
  ADD COLUMN `ready` tinyint(1) NOT NULL DEFAULT 0 AFTER `opinion_at`,
  ADD COLUMN `ready_by` varchar(100) DEFAULT NULL AFTER `ready`,
  ADD COLUMN `ready_at` timestamp NULL DEFAULT NULL AFTER `ready_by`;

-- ---------------------------------------------------------------------
-- PHẦN 8: Đánh dấu 9 migration đã chạy (để `php artisan migrate` bỏ qua)
-- Bỏ phần này nếu bên bạn quản lý bảng `migrations` theo cách khác.
-- ---------------------------------------------------------------------

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.`migration`, (SELECT MAX(`batch`) + 1 FROM `migrations` b)
  FROM (
    SELECT '2026_08_21_090000_add_is_manual_to_plan_master_infor_parkaging' AS `migration`
    UNION ALL SELECT '2026_08_21_090000_change_content_to_text_on_publication_tracking_task_table'
    UNION ALL SELECT '2026_08_21_090100_split_packaging_notification_permissions'
    UNION ALL SELECT '2026_08_21_100000_create_plan_master_infor_parkaging_history_table'
    UNION ALL SELECT '2026_08_21_110000_replace_sampling_fields_with_primary_secondary'
    UNION ALL SELECT '2026_08_21_110100_widen_packaging_history_value_columns'
    UNION ALL SELECT '2026_08_21_120000_add_sampled_confirmation_to_plan_master_infor_parkaging'
    UNION ALL SELECT '2026_08_21_130000_drop_sampled_by_date_from_plan_master_infor_parkaging'
    UNION ALL SELECT '2026_08_21_140000_add_ready_to_publication_tracking_detail'
  ) m
 WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) x WHERE x.`migration` = m.`migration`
 );

-- =====================================================================
-- HẾT.
-- =====================================================================

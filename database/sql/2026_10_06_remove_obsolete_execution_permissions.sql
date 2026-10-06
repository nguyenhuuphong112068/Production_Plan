-- =====================================================================
-- PMS Production Plan - Xóa 5 quyền theo nút của trang Thực Thi Sản Xuất bản cũ
-- Ngày    : 2026-10-06
-- Phạm vi : 1 migration
--   2026_10_06_090000_remove_obsolete_execution_permissions
--
-- CÁCH IMPORT:
--   mysql -u <user> -p <database> < 2026_10_06_remove_obsolete_execution_permissions.sql
--
-- LƯU Ý:
--   - Trang Thực Thi Sản Xuất đã tinh gọn còn 2 nút Nhận phòng / Trả phòng, các nút
--     mở phòng - bắt đầu - kết thúc, vệ sinh, kiểm tra vệ sinh, chuyển sang cần vệ
--     sinh, hoàn tác không còn nên bỏ quyền tương ứng.
--   - GIỮ NGUYÊN 2 quyền: layout_production_execution, layout_production_record.
--   - Toàn bộ file chạy lại nhiều lần vẫn an toàn (xóa theo tên quyền, không có thì bỏ qua).
--   - Xóa cả dòng gán quyền ở role_permission và user_permission trước, rồi mới xóa quyền.
--   - KHÔNG chạy lại file 2026_10_01_production_execution_permissions.sql sau file này
--     (file đó tạo lại 6 quyền, trong đó có 5 quyền vừa xóa).
--   - Muốn hoàn tác: chạy `php artisan migrate:rollback --path=database/migrations/2026_10_06_090000_remove_obsolete_execution_permissions.php`
--     hoặc tạo lại 5 quyền theo file 2026_10_01 (chỉ các dòng execution_*).
-- =====================================================================

SET NAMES utf8mb4;

START TRANSACTION;

-- ---------------------------------------------------------------------
-- PHẦN 1: Xóa dòng gán quyền cho nhóm quyền (role) và cho từng người dùng
-- ---------------------------------------------------------------------

DELETE FROM `role_permission`
 WHERE `permission_id` IN (
        SELECT `id` FROM (
            SELECT `id` FROM `permissions`
             WHERE `name` IN (
                    'execution_production',
                    'execution_cleaning',
                    'execution_clean_check',
                    'execution_mark_dirty',
                    'execution_undo'
                   )
        ) AS `obsolete`
 );

DELETE FROM `user_permission`
 WHERE `permission_id` IN (
        SELECT `id` FROM (
            SELECT `id` FROM `permissions`
             WHERE `name` IN (
                    'execution_production',
                    'execution_cleaning',
                    'execution_clean_check',
                    'execution_mark_dirty',
                    'execution_undo'
                   )
        ) AS `obsolete`
 );

-- ---------------------------------------------------------------------
-- PHẦN 2: Xóa 5 quyền
-- ---------------------------------------------------------------------

DELETE FROM `permissions`
 WHERE `name` IN (
        'execution_production',
        'execution_cleaning',
        'execution_clean_check',
        'execution_mark_dirty',
        'execution_undo'
       );

COMMIT;

-- ---------------------------------------------------------------------
-- PHẦN 3: Đánh dấu migration đã chạy (để `php artisan migrate` bỏ qua)
-- Bỏ phần này nếu bên bạn quản lý bảng `migrations` theo cách khác.
-- ---------------------------------------------------------------------

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.`migration`, (SELECT MAX(`batch`) + 1 FROM `migrations` b)
  FROM (
    SELECT '2026_10_06_090000_remove_obsolete_execution_permissions' AS `migration`
  ) m
 WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) x WHERE x.`migration` = m.`migration`
 );

-- ---------------------------------------------------------------------
-- KIỂM TRA SAU KHI CHẠY (kết quả mong đợi: 2 dòng
--   layout_production_execution, layout_production_record)
-- ---------------------------------------------------------------------
-- SELECT `name`, `display_name` FROM `permissions`
--  WHERE `name` LIKE 'execution\_%' OR `name` LIKE 'layout\_production\_%';

-- =====================================================================
-- HẾT.
-- =====================================================================

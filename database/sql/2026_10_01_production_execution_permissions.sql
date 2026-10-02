-- =====================================================================
-- PMS Production Plan - Quyền trang Thực Thi Sản Xuất
-- Ngày    : 2026-10-01
-- Phạm vi : 1 migration
--   2026_10_01_090000_add_production_execution_permissions
--
-- CÁCH IMPORT:
--   mysql -u <user> -p <database> < 2026_10_01_production_execution_permissions.sql
--
-- LƯU Ý:
--   - Toàn bộ file dùng ON DUPLICATE KEY / INSERT IGNORE nên chạy lại nhiều
--     lần vẫn an toàn.
--   - Chỉ THÊM dòng quyền và cấp cho Admin. Việc CHẶN thao tác trong
--     controller/view là bước riêng, chưa nằm trong file này: trước khi code
--     chặn thì các quyền này chưa có tác dụng.
--   - permission_group = 6, cùng nhóm layout_finised / return_event vì trang
--     Thực Thi Sản Xuất thay cho trang Xác Nhận Hoàn Thành.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- PHẦN 1: Thêm 6 quyền của trang Thực Thi Sản Xuất
-- ---------------------------------------------------------------------

INSERT INTO `permissions` (`permission_group`, `name`, `display_name`, `description`, `created_at`, `updated_at`)
VALUES
  (6, 'layout_production_execution', 'Trang Thực Thi Sản Xuất', 'Vào trang Thực Thi Sản Xuất, xem bảng trạng thái phòng và lịch sử phòng của phân xưởng mình', NOW(), NOW()),
  (6, 'execution_production', 'Thực Thi SX: Mở Phòng - Bắt Đầu - Kết Thúc', 'Chọn lô mở phòng, bắt đầu / tạm dừng / tiếp tục / kết thúc sản xuất và ghi hoạt động phòng', NOW(), NOW()),
  (6, 'execution_cleaning', 'Thực Thi SX: Thực Hiện Vệ Sinh', 'Bắt đầu và kết thúc vệ sinh phòng (VS-I, VS-II, VS-III)', NOW(), NOW()),
  (6, 'execution_clean_check', 'Thực Thi SX: Kiểm Tra Vệ Sinh', 'Ký kết quả kiểm tra vệ sinh Đạt / Không đạt. Quyền xét trên tài khoản người kiểm tra nhập lại, không phải phiên đang đăng nhập', NOW(), NOW()),
  (6, 'execution_mark_dirty', 'Thực Thi SX: Chuyển Phòng Sang Cần Vệ Sinh', 'Chuyển phòng đang sạch sang Cần Vệ Sinh kèm lý do (sau bảo trì, sự cố, hết hạn phòng sạch)', NOW(), NOW()),
  (6, 'execution_undo', 'Thực Thi SX: Hoàn Tác', 'Hoàn tác thao tác vừa ghi trên phòng - xoá mốc thời gian đã lưu và trả phòng về trạng thái trước đó', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `permission_group` = VALUES(`permission_group`),
  `display_name`     = VALUES(`display_name`),
  `description`      = VALUES(`description`),
  `updated_at`       = NOW();

-- ---------------------------------------------------------------------
-- PHẦN 2: Cấp sẵn cả 6 quyền cho nhóm Admin
-- ---------------------------------------------------------------------

INSERT IGNORE INTO `role_permission` (`role_id`, `permission_id`)
SELECT `r`.`id`, `p`.`id`
  FROM `roles` `r`
  JOIN `permissions` `p` ON `p`.`name` IN (
        'layout_production_execution',
        'execution_production',
        'execution_cleaning',
        'execution_clean_check',
        'execution_mark_dirty',
        'execution_undo'
       )
 WHERE `r`.`name` = 'Admin';

-- ---------------------------------------------------------------------
-- PHẦN 3: Đánh dấu migration đã chạy (để `php artisan migrate` bỏ qua)
-- Bỏ phần này nếu bên bạn quản lý bảng `migrations` theo cách khác.
-- ---------------------------------------------------------------------

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.`migration`, (SELECT MAX(`batch`) + 1 FROM `migrations` b)
  FROM (
    SELECT '2026_10_01_090000_add_production_execution_permissions' AS `migration`
  ) m
 WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT `migration` FROM `migrations`) x WHERE x.`migration` = m.`migration`
 );

-- =====================================================================
-- HẾT.
-- =====================================================================

-- =====================================================================
-- PMS Production Plan - Bù dòng user_role cho các user Executor
-- Ngày    : 2026-10-06
--
-- Tình huống: user Executor đã có trong user_management (userGroup = 'Executor')
-- nhưng chưa có dòng tương ứng trong user_role → trang /User không hiện role,
-- PermissionHelper không cấp quyền của role Executor cho user đó.
--
-- CÁCH IMPORT (chạy SAU 2026_10_05_executor_role_production_record.sql):
--   mysql -u <user> -p <database> < 2026_10_06_executor_users_user_role.sql
--
-- LƯU Ý: chạy lại nhiều lần vẫn an toàn (INSERT IGNORE, khóa chính user_id + role_id).
-- =====================================================================

SET NAMES utf8mb4;

-- Kiểm tra trước: số user Executor đang thiếu user_role
SELECT COUNT(*) AS `thieu_user_role`
  FROM `user_management` `u`
  JOIN `roles` `r` ON `r`.`name` = 'Executor'
 WHERE `u`.`userGroup` = 'Executor'
   AND NOT EXISTS (SELECT 1 FROM `user_role` `ur` WHERE `ur`.`user_id` = `u`.`id` AND `ur`.`role_id` = `r`.`id`);

INSERT IGNORE INTO `user_role` (`user_id`, `role_id`)
SELECT `u`.`id`, `r`.`id`
  FROM `user_management` `u`
  JOIN `roles` `r` ON `r`.`name` = 'Executor'
 WHERE `u`.`userGroup` = 'Executor';

-- Kiểm tra sau: phải ra 0
SELECT COUNT(*) AS `con_thieu`
  FROM `user_management` `u`
  JOIN `roles` `r` ON `r`.`name` = 'Executor'
 WHERE `u`.`userGroup` = 'Executor'
   AND NOT EXISTS (SELECT 1 FROM `user_role` `ur` WHERE `ur`.`user_id` = `u`.`id` AND `ur`.`role_id` = `r`.`id`);

-- =====================================================================
-- HẾT.
-- =====================================================================

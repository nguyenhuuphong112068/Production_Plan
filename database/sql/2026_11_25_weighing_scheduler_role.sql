-- =====================================================================================
-- 25/11/2026 - Thêm nhóm quyền "Weighing Scheduler" (Người Lập Lịch Cân - sắp lịch trung tâm cân)
--
-- Nhóm quyền CHÍNH (user_management.userGroup) là Weighing Scheduler thì trên Lịch Sản Xuất Chart:
--   - chỉ sắp / kéo thả / xoá lịch công đoạn cân (stage_code 1, 2), chỉ vào phòng cân;
--   - Submit chỉ submit lịch cân, lịch các công đoạn khác giữ nguyên chưa submit;
--   - không dùng được sắp lịch tự động, xoá hàng loạt, tịnh tiến, chiến dịch...
-- Code: App\Support\WeighingScheduler, App\Http\Middleware\RestrictWeighingScheduler, FullCalender.jsx.
-- Tên role so sánh cứng trong code: KHÔNG đổi tên ở /User/role.
--
-- Giống migration 2026_11_25_100000_add_weighing_scheduler_role.php. Chạy lại nhiều lần không sao.
-- =====================================================================================

INSERT INTO `roles` (`name`, `display_name`, `description`, `active`, `created_at`, `updated_at`)
SELECT 'Weighing Scheduler', 'Người Lập Lịch Cân',
       'Sắp lịch trung tâm cân: trên Lịch Sản Xuất Chart chỉ sắp lịch và Submit công đoạn cân, không đổi lịch các công đoạn khác',
       1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `name` = 'Weighing Scheduler');

-- Quyền cấp sẵn: tải lô chờ sắp lịch + các màn hình xem. Thêm / bớt ở trang Phân Quyền (/User/role).
INSERT INTO `role_permission` (`role_id`, `permission_id`)
SELECT `r`.`id`, `p`.`id`
  FROM `roles` `r`
  JOIN `permissions` `p` ON `p`.`name` IN (
        'loading_plan_waiting', 'layout_history', 'layout_quarantine', 'layout_warning',
        'layout_wip_coverage', 'layout_daily_report', 'layout_weekly_report',
        'schedual_wip_rows', 'user_guide_view')
 WHERE `r`.`name` = 'Weighing Scheduler'
   AND NOT EXISTS (SELECT 1 FROM `role_permission` `x` WHERE `x`.`role_id` = `r`.`id` AND `x`.`permission_id` = `p`.`id`);

-- KIỂM TRA
SELECT `r`.`id`, `r`.`name`, COUNT(`rp`.`permission_id`) AS `so_quyen`
  FROM `roles` `r` LEFT JOIN `role_permission` `rp` ON `rp`.`role_id` = `r`.`id`
 WHERE `r`.`name` = 'Weighing Scheduler'
 GROUP BY `r`.`id`, `r`.`name`;

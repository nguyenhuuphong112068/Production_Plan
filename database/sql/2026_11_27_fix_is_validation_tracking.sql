-- 27/11/2026: Dọn cờ plan_master.is_validation_tracking bị bật sai.
-- Nguyên nhân: tạo nhiều lô cùng lúc có tick "Theo dõi thẩm định" thì MỌI lô đều được gán cờ = 1,
-- nhưng chỉ lô dùng hết suất theo dõi (num_of_tracking_batch) mới được gắn vào validation_tracking_plan_master.
-- Các lô còn lại hiện sai ở Theo Dõi Thẩm Định > Kế Hoạch Đang Sản Xuất và bị gửi thông báo lấy mẫu.
-- Quy tắc đúng (như update/quickUpdate): cờ = 1 khi và chỉ khi có liên kết active.

-- Xem trước:
SELECT pm.id, pm.batch, pm.created_at
FROM plan_master pm
WHERE pm.is_validation_tracking = 1
  AND NOT EXISTS (SELECT 1 FROM validation_tracking_plan_master v
                  WHERE v.plan_master_id = pm.id AND v.active = 1);

-- Sửa:
UPDATE plan_master pm
SET pm.is_validation_tracking = 0
WHERE pm.is_validation_tracking = 1
  AND NOT EXISTS (SELECT 1 FROM validation_tracking_plan_master v
                  WHERE v.plan_master_id = pm.id AND v.active = 1);

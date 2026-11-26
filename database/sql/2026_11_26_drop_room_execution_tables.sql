-- =====================================================================================
-- 26/11/2026 - Bỏ 2 bảng của bản Thực Thi Sản Xuất cũ: room_execution_log, room_execution_batch
--
-- Từ 05/10/2026 Nhận/Trả phòng ghi thẳng stage_plan (RoomOccupancyService), không còn code nào
-- đọc/ghi 2 bảng này (đã xoá ProductionExecutionService). Trên server cả 2 bảng đều trống.
--
-- Giống migration 2026_11_26_090000_drop_room_execution_tables.php. Chạy lại nhiều lần không sao.
-- =====================================================================================

-- Kiểm tra trước (nên = 0 cả hai):
-- SELECT COUNT(*) FROM `room_execution_log`;
-- SELECT COUNT(*) FROM `room_execution_batch`;

DROP TABLE IF EXISTS `room_execution_batch`;
DROP TABLE IF EXISTS `room_execution_log`;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_26_090000_drop_room_execution_tables', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_26_090000_drop_room_execution_tables');

-- Kiểm tra sau (không còn dòng nào):
SHOW TABLES LIKE 'room_execution%';

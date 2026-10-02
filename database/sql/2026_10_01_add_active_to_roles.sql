-- =============================================================================
--  PMS - Quan ly nhom quyen (tao / sua / vo hieu hoa role tren giao dien)
--  Them cot trang thai `active` cho bang roles
--
--  Tuong duong migration :
--      database/migrations/2026_10_01_100000_add_active_to_roles_table.php
--
--  Bang dich : roles
--  Script co the chay lai nhieu lan ma khong gay loi (idempotent).
-- =============================================================================

SET NAMES utf8mb4;

--  active = 1 : nhom quyen dang dung
--  active = 0 : da vo hieu hoa - khong con cap quyen cho user,
--               khong hien o o chon phan quyen khi tao / sua user
ALTER TABLE `roles`
    ADD COLUMN IF NOT EXISTS `active` tinyint(1) NOT NULL DEFAULT 1 AFTER `description`;

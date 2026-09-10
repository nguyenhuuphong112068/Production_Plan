-- =============================================================================
--  PMS - Tinh nang bao mat dang nhap
--  Script 1/2 : Them cot khoa tai khoan + noi rong cot lich su mat khau
--
--  Tuong duong migration :
--      database/migrations/2026_09_10_150000_add_account_security_to_user_management.php
--
--  Moi truong da kiem thu : MariaDB 10.4.32 (XAMPP)
--  Bang dich             : user_management  (utf8mb4 / utf8mb4_unicode_ci)
--
--  LUU Y : chay script 1 TRUOC, sau do moi chay script 2.
--          Script co the chay lai nhieu lan ma khong gay loi (idempotent).
-- =============================================================================

-- Khop voi APP_TIMEZONE = Asia/Ho_Chi_Minh
SET NAMES utf8mb4;
SET time_zone = '+07:00';


-- -----------------------------------------------------------------------------
-- 1. Them 3 cot phuc vu khoa tai khoan / bat buoc doi mat khau
-- -----------------------------------------------------------------------------
--  failed_attempts      : so lan nhap sai mat khau lien tiep
--  locked_at            : thoi diem bi khoa (dung de tu mo khoa sau 15 phut)
--  must_change_password : =1 thi bat buoc doi mat khau o lan dang nhap ke tiep
-- -----------------------------------------------------------------------------

ALTER TABLE `user_management`
    ADD COLUMN IF NOT EXISTS `failed_attempts`      smallint(5) unsigned NOT NULL DEFAULT 0 AFTER `isLocked`,
    ADD COLUMN IF NOT EXISTS `locked_at`            timestamp            NULL     DEFAULT NULL AFTER `failed_attempts`,
    ADD COLUMN IF NOT EXISTS `must_change_password` tinyint(1)           NOT NULL DEFAULT 0 AFTER `locked_at`;


-- -----------------------------------------------------------------------------
-- 2. Noi rong hisPW_1..3 tu varchar(20) -> varchar(255)
-- -----------------------------------------------------------------------------
--  Cot cu chi 20 ky tu, KHONG the chua hash bcrypt (60 ky tu) nen tinh nang
--  "khong trung 3 mat khau gan nhat" se khong hoat dong neu khong sua.
--  Khong khai bao CHARACTER SET -> giu nguyen mac dinh cua bang (utf8mb4_unicode_ci).
-- -----------------------------------------------------------------------------

ALTER TABLE `user_management`
    MODIFY COLUMN `hisPW_1` varchar(255) DEFAULT NULL,
    MODIFY COLUMN `hisPW_2` varchar(255) DEFAULT NULL,
    MODIFY COLUMN `hisPW_3` varchar(255) DEFAULT NULL;


-- -----------------------------------------------------------------------------
-- 3. Don du lieu rac cu : gia tri khoi tao "0" -> NULL
-- -----------------------------------------------------------------------------

UPDATE `user_management` SET `hisPW_1` = NULL WHERE `hisPW_1` = '0';
UPDATE `user_management` SET `hisPW_2` = NULL WHERE `hisPW_2` = '0';
UPDATE `user_management` SET `hisPW_3` = NULL WHERE `hisPW_3` = '0';


-- -----------------------------------------------------------------------------
-- 4. Danh dau migration da chay
-- -----------------------------------------------------------------------------
--  De sau nay chay `php artisan migrate` tren server se KHONG chay lai file PHP
--  tuong ung. Bo qua buoc nay neu server khong dung artisan migrate.
-- -----------------------------------------------------------------------------

--  KHONG dung bien @ trong phep so sanh voi cot `migration` : bien mang
--  collation cua connection (utf8mb4_general_ci) con cot la utf8mb4_unicode_ci,
--  hai ben cung do uu tien IMPLICIT nen MySQL bao loi 1267 Illegal mix of
--  collations. Dung thang chuoi literal thi khong bi.

SET @batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

DELETE FROM `migrations`
WHERE `migration` = '2026_09_10_150000_add_account_security_to_user_management';

INSERT INTO `migrations` (`migration`, `batch`)
VALUES ('2026_09_10_150000_add_account_security_to_user_management', @batch);


-- -----------------------------------------------------------------------------
-- 5. Kiem tra ket qua
-- -----------------------------------------------------------------------------

SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'user_management'
  AND COLUMN_NAME IN ('isLocked','failed_attempts','locked_at','must_change_password',
                      'changePWdate','hisPW_1','hisPW_2','hisPW_3')
ORDER BY ORDINAL_POSITION;

-- Ket qua mong doi :
--   isLocked             tinyint(1)            null=NO   def=0
--   failed_attempts      smallint(5) unsigned  null=NO   def=0
--   locked_at            timestamp             null=YES  def=NULL
--   must_change_password tinyint(1)            null=NO   def=0
--   changePWdate         date                  null=NO   def=NULL
--   hisPW_1              varchar(255)          null=YES  def=NULL
--   hisPW_2              varchar(255)          null=YES  def=NULL
--   hisPW_3              varchar(255)          null=YES  def=NULL

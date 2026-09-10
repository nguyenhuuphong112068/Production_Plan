-- =============================================================================
--  PMS - Tinh nang bao mat dang nhap
--  Script 2/2 : Dat lai moc het han mat khau khi bat dau ap dung chinh sach 90 ngay
--
--  Tuong duong migration :
--      database/migrations/2026_09_10_150100_reset_password_expiry_baseline.php
--
--  VI SAO CAN SCRIPT NAY
--  ---------------------
--  Cot `changePWdate` da co san tu truoc nhung CHUA BAO GIO duoc kiem tra luc
--  dang nhap, nen phan lon user dang mang ngay rat cu (nhieu user van o moc seed
--  2026-01-01). Neu bat thang tinh nang ma khong chay script nay thi gan nhu
--  TOAN BO user se bi bat doi mat khau cung mot luc ngay ngay deploy.
--
--  Tren DB dev : 240/259 user dang hoat dong roi vao truong hop nay.
--
--  CACH XU LY : cap lai chu ky 90 ngay ke tu ngay chay script cho nhung user
--  DA qua han. User CON han duoc giu nguyen - neu reset ca nhom nay se vo tinh
--  KEO DAI tuoi tho mat khau cua ho, tuc la lam yeu di thay vi siet chat.
--
--  LUU Y : chay SAU script 1.
--          Chay lai lan 2 se khong doi gi (khong con user nao qua han).
-- =============================================================================

-- Khop voi APP_TIMEZONE = Asia/Ho_Chi_Minh
SET NAMES utf8mb4;
SET time_zone = '+07:00';


-- -----------------------------------------------------------------------------
-- 0. Xem truoc so user se bi anh huong  (chay rieng dong nay truoc neu muon)
-- -----------------------------------------------------------------------------

SELECT COUNT(*) AS `so_user_se_duoc_reset`
FROM `user_management`
WHERE `isActive` = 1
  AND `changePWdate` <= CURDATE();


-- -----------------------------------------------------------------------------
-- 1. Cap lai chu ky 90 ngay cho user da qua han
-- -----------------------------------------------------------------------------

UPDATE `user_management`
SET `changePWdate` = DATE_ADD(CURDATE(), INTERVAL 90 DAY)
WHERE `isActive` = 1
  AND `changePWdate` <= CURDATE();


-- -----------------------------------------------------------------------------
-- 2. Danh dau migration da chay
-- -----------------------------------------------------------------------------

SET @batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

DELETE FROM `migrations`
WHERE `migration` = '2026_09_10_150100_reset_password_expiry_baseline';

INSERT INTO `migrations` (`migration`, `batch`)
VALUES ('2026_09_10_150100_reset_password_expiry_baseline', @batch);


-- -----------------------------------------------------------------------------
-- 3. Kiem tra ket qua
-- -----------------------------------------------------------------------------

-- Phai ra 0 : khong con ai bi bat doi mat khau ngay lap tuc
SELECT COUNT(*) AS `con_bi_bat_doi_ngay`
FROM `user_management`
WHERE `isActive` = 1
  AND `changePWdate` <= CURDATE();

-- Phan bo ngay het han sau khi reset
SELECT `changePWdate`, COUNT(*) AS `so_user`
FROM `user_management`
WHERE `isActive` = 1
GROUP BY `changePWdate`
ORDER BY `changePWdate`;

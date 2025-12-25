-- notices 테이블에서 is_important 필드 제거

USE `mvno_db`;

-- is_important 컬럼 제거
ALTER TABLE `notices` DROP COLUMN `is_important`;




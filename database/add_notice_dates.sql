-- notices 테이블에 start_at과 end_at 필드 추가
USE `mvno_db`;

ALTER TABLE `notices` 
ADD COLUMN `start_at` DATE DEFAULT NULL COMMENT '메인공지 시작일' AFTER `show_on_main`,
ADD COLUMN `end_at` DATE DEFAULT NULL COMMENT '메인공지 종료일' AFTER `start_at`;




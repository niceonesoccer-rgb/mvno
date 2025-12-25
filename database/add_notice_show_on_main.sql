-- notices 테이블에 show_on_main 필드 추가
USE `mvno_db`;

ALTER TABLE `notices` 
ADD COLUMN `show_on_main` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '메인페이지 새창 표시 여부' AFTER `is_published`;





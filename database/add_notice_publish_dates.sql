-- notices 테이블에 publish_start_at과 publish_end_at 필드 추가
USE `mvno_db`;

ALTER TABLE `notices` 
ADD COLUMN `publish_start_at` DATE DEFAULT NULL COMMENT '발행 시작일' AFTER `is_published`,
ADD COLUMN `publish_end_at` DATE DEFAULT NULL COMMENT '발행 종료일' AFTER `publish_start_at`;

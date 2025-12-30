-- 판매자 문의 테이블에 관리자 확인 필드 추가
USE `mvno_db`;

ALTER TABLE `seller_inquiries` 
ADD COLUMN `admin_viewed_at` DATETIME DEFAULT NULL COMMENT '관리자 확인 시간' AFTER `answered_by`,
ADD COLUMN `admin_viewed_by` VARCHAR(50) DEFAULT NULL COMMENT '확인한 관리자 user_id' AFTER `admin_viewed_at`;




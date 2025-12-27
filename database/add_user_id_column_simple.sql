-- application_customers 테이블에 user_id 컬럼 추가
-- 이 SQL을 phpMyAdmin이나 MySQL 클라이언트에서 직접 실행하세요

-- 1. 컬럼 추가
ALTER TABLE `application_customers` 
ADD COLUMN `user_id` VARCHAR(50) DEFAULT NULL COMMENT '회원 user_id (비회원 신청 가능)' 
AFTER `application_id`;

-- 2. 인덱스 추가 (선택사항, 성능 향상)
ALTER TABLE `application_customers` 
ADD INDEX `idx_user_id` (`user_id`);









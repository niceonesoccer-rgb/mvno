-- ============================================
-- 리뷰 테이블에 application_id 컬럼 추가
-- 각 신청(application)별로 별도의 리뷰를 작성할 수 있도록
-- ============================================

USE `mvno_db`;

-- product_reviews 테이블에 application_id 컬럼 추가
ALTER TABLE `product_reviews` 
ADD COLUMN `application_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '신청 ID (application별 리뷰 구분용)' AFTER `product_id`;

-- 인덱스 추가
ALTER TABLE `product_reviews` 
ADD INDEX `idx_application_id` (`application_id`);





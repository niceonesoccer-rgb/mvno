-- ============================================
-- product_reviews 테이블에 application_id 컬럼 추가
-- 주문별로 별도 리뷰를 작성할 수 있도록 함
-- ============================================

USE `mvno_db`;

-- application_id 컬럼 추가 (NULL 허용 - 기존 리뷰는 NULL 가능)
ALTER TABLE `product_reviews` 
ADD COLUMN `application_id` INT(11) UNSIGNED NULL COMMENT '신청 ID (주문별 리뷰 구분용)' AFTER `product_id`;

-- 인덱스 추가 (application_id로 빠른 조회를 위해)
ALTER TABLE `product_reviews` 
ADD INDEX `idx_application_id` (`application_id`);

-- 외래키 제약 조건 추가 (선택사항 - 데이터 무결성 보장)
-- ALTER TABLE `product_reviews` 
-- ADD CONSTRAINT `fk_review_application` FOREIGN KEY (`application_id`) REFERENCES `product_applications` (`id`) ON DELETE SET NULL;






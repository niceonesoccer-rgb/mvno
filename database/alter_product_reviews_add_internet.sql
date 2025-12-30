-- ============================================
-- product_reviews 테이블에 'internet' 타입 추가
-- ============================================

USE `mvno_db`;

-- product_reviews 테이블의 product_type ENUM에 'internet' 추가
ALTER TABLE `product_reviews` 
MODIFY COLUMN `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL COMMENT '상품 타입';












-- ============================================
-- products 테이블의 product_type ENUM에 'mno-sim' 추가
-- ============================================

USE `mvno_db`;

-- products 테이블의 product_type ENUM에 'mno-sim' 추가
ALTER TABLE `products` 
MODIFY COLUMN `product_type` ENUM('mvno', 'mno', 'internet', 'mno-sim') NOT NULL COMMENT '상품 타입';





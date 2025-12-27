-- product_favorites 테이블에 mno-sim 타입 추가
-- 실행 방법: MySQL에서 이 파일을 실행하거나 phpMyAdmin에서 실행

USE `mvno_db`;

-- product_favorites 테이블의 product_type ENUM에 'mno-sim' 추가
ALTER TABLE `product_favorites` 
MODIFY COLUMN `product_type` ENUM('mvno', 'mno', 'internet', 'mno-sim') NOT NULL COMMENT '상품 타입';



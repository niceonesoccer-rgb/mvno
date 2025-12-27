-- ============================================
-- 인터넷 리뷰를 자동 승인 상태로 업데이트
-- ============================================

USE `mvno_db`;

-- product_reviews 테이블의 product_type ENUM에 'internet' 추가 (아직 추가되지 않은 경우)
ALTER TABLE `product_reviews` 
MODIFY COLUMN `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL COMMENT '상품 타입';

-- 이미 작성된 인터넷 리뷰를 'approved' 상태로 업데이트
UPDATE `product_reviews` 
SET `status` = 'approved' 
WHERE `product_type` = 'internet' 
AND `status` = 'pending';









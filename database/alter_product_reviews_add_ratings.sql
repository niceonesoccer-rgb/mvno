-- ============================================
-- 인터넷 리뷰를 위한 별점 필드 추가
-- ============================================

USE `mvno_db`;

-- product_reviews 테이블에 kindness_rating과 speed_rating 컬럼 추가
ALTER TABLE `product_reviews` 
ADD COLUMN `kindness_rating` TINYINT(1) UNSIGNED DEFAULT NULL COMMENT '친절해요 별점 (인터넷 리뷰용)' AFTER `rating`,
ADD COLUMN `speed_rating` TINYINT(1) UNSIGNED DEFAULT NULL COMMENT '설치 빨라요 별점 (인터넷 리뷰용)' AFTER `kindness_rating`;








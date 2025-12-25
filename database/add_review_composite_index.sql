-- 리뷰 쿼리 성능 최적화를 위한 복합 인덱스 추가
-- 동시 접속 시 쿼리 성능 향상

-- 기본 복합 인덱스 (가장 많이 사용되는 쿼리 패턴)
ALTER TABLE `product_reviews` 
ADD INDEX `idx_product_id_type_status` (`product_id`, `product_type`, `status`);

-- kindness_rating 집계 쿼리 최적화
ALTER TABLE `product_reviews` 
ADD INDEX `idx_product_id_type_status_kindness` (`product_id`, `product_type`, `status`, `kindness_rating`);

-- speed_rating 집계 쿼리 최적화
ALTER TABLE `product_reviews` 
ADD INDEX `idx_product_id_type_status_speed` (`product_id`, `product_type`, `status`, `speed_rating`);





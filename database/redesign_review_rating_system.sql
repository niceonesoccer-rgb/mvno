-- ============================================
-- 리뷰 평점 시스템 하이브리드 방식 재설계
-- 인터넷, 알뜰폰(MVNO), 통신사폰(MNO) 모두 지원
-- ============================================

USE `mvno_db`;

-- ============================================
-- 1. 기존 통계 테이블 삭제 (재생성용)
-- ============================================
DROP TABLE IF EXISTS `product_review_statistics`;

-- ============================================
-- 2. 리뷰 통계 테이블 재생성 (하이브리드 방식)
-- ============================================
CREATE TABLE `product_review_statistics` (
    `product_id` INT(11) UNSIGNED NOT NULL PRIMARY KEY,
    
    -- 실시간 통계 (화면 표시용, 수정/삭제 시 업데이트)
    `total_rating_sum` DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '별점 합계 (실시간)',
    `total_review_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '리뷰 개수 (실시간)',
    
    `kindness_rating_sum` DECIMAL(12,2) DEFAULT 0 COMMENT '친절해요 합계 (실시간)',
    `kindness_review_count` INT(11) UNSIGNED DEFAULT 0 COMMENT '친절해요 리뷰 개수 (실시간)',
    `speed_rating_sum` DECIMAL(12,2) DEFAULT 0 COMMENT '개통/설치 빨라요 합계 (실시간)',
    `speed_review_count` INT(11) UNSIGNED DEFAULT 0 COMMENT '개통/설치 빨라요 리뷰 개수 (실시간)',
    
    -- 처음 작성 시점 통계 (고정값, 수정/삭제 시 변경 안 됨)
    `initial_total_rating_sum` DECIMAL(12,2) DEFAULT 0 COMMENT '처음 작성 시점 별점 합계',
    `initial_total_review_count` INT(11) UNSIGNED DEFAULT 0 COMMENT '처음 작성 시점 리뷰 개수',
    
    `initial_kindness_rating_sum` DECIMAL(12,2) DEFAULT 0 COMMENT '처음 작성 시점 친절해요 합계',
    `initial_kindness_review_count` INT(11) UNSIGNED DEFAULT 0 COMMENT '처음 작성 시점 친절해요 개수',
    `initial_speed_rating_sum` DECIMAL(12,2) DEFAULT 0 COMMENT '처음 작성 시점 개통/설치 빨라요 합계',
    `initial_speed_review_count` INT(11) UNSIGNED DEFAULT 0 COMMENT '처음 작성 시점 개통/설치 빨라요 개수',
    
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY `idx_updated_at` (`updated_at`),
    CONSTRAINT `fk_product_statistics` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품별 리뷰 통계 (하이브리드: 실시간 + 처음 작성 시점 값)';

-- ============================================
-- 3. product_reviews 테이블 수정 (internet 타입 추가)
-- ============================================
-- 기존 테이블이 있으면 수정, 없으면 생성

-- product_type에 'internet' 추가
ALTER TABLE `product_reviews` 
MODIFY COLUMN `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL COMMENT '상품 타입';

-- kindness_rating, speed_rating 컬럼 확인 및 추가
ALTER TABLE `product_reviews` 
ADD COLUMN IF NOT EXISTS `kindness_rating` TINYINT(1) UNSIGNED DEFAULT NULL COMMENT '친절해요 평점 (1-5)',
ADD COLUMN IF NOT EXISTS `speed_rating` TINYINT(1) UNSIGNED DEFAULT NULL COMMENT '개통/설치 빨라요 평점 (1-5)',
ADD COLUMN IF NOT EXISTS `application_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '신청 ID (주문별 리뷰 구분)';

-- 인덱스 추가 (성능 최적화)
ALTER TABLE `product_reviews` 
ADD INDEX IF NOT EXISTS `idx_product_id_type_status` (`product_id`, `product_type`, `status`),
ADD INDEX IF NOT EXISTS `idx_product_id_type_status_kindness` (`product_id`, `product_type`, `status`, `kindness_rating`),
ADD INDEX IF NOT EXISTS `idx_product_id_type_status_speed` (`product_id`, `product_type`, `status`, `speed_rating`),
ADD INDEX IF NOT EXISTS `idx_application_id` (`application_id`);

-- ============================================
-- 4. 복합 인덱스 추가 (성능 최적화)
-- ============================================
-- 이미 위에서 추가했지만, 중복 방지를 위해 IF NOT EXISTS 사용 불가 시 별도 처리





-- ============================================
-- 상품별 리뷰 시스템으로 마이그레이션
-- 기존 통합 리뷰 → 상품별 리뷰 전환
-- ============================================

USE `mvno_db`;

-- ============================================
-- 1. 기존 테이블 및 트리거 삭제
-- ============================================

-- 기존 트리거 삭제
DROP TRIGGER IF EXISTS `trg_review_insert`;
DROP TRIGGER IF EXISTS `trg_review_delete`;
DROP TRIGGER IF EXISTS `trg_update_review_statistics_on_insert`;
DROP TRIGGER IF EXISTS `trg_invalidate_rating_cache`;

-- 기존 리뷰 테이블 삭제 (데이터 포함)
DROP TABLE IF EXISTS `product_reviews`;

-- 기존 통계 테이블 삭제 (있다면)
DROP TABLE IF EXISTS `product_review_statistics`;

-- ============================================
-- 2. 새로운 상품별 리뷰 테이블 생성
-- ============================================

CREATE TABLE `product_reviews` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID (상품별 리뷰)',
    `user_id` VARCHAR(50) NOT NULL COMMENT '작성자 user_id (users.user_id)',
    `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL COMMENT '상품 타입',
    
    -- 기본 평점
    `rating` TINYINT(1) UNSIGNED NOT NULL COMMENT '평점 (1-5)',
    
    -- 인터넷 상품 항목별 평점
    `kindness_rating` TINYINT(1) UNSIGNED DEFAULT NULL COMMENT '친절해요 평점 (1-5, 인터넷용)',
    `speed_rating` TINYINT(1) UNSIGNED DEFAULT NULL COMMENT '설치 빨라요 평점 (1-5, 인터넷용)',
    
    -- 리뷰 내용
    `title` VARCHAR(200) DEFAULT NULL COMMENT '리뷰 제목',
    `content` TEXT NOT NULL COMMENT '리뷰 내용',
    
    -- 추가 정보
    `application_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '신청 ID (구매 인증용)',
    `order_number` VARCHAR(50) DEFAULT NULL COMMENT '주문번호',
    `is_verified` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '구매 인증 여부',
    `helpful_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '도움됨 수',
    
    -- 상태 관리
    `status` ENUM('pending', 'approved', 'rejected', 'deleted') NOT NULL DEFAULT 'pending' COMMENT '리뷰 상태',
    
    -- 타임스탬프
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '작성일시',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    
    PRIMARY KEY (`id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_product_type` (`product_type`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_product_status` (`product_id`, `status`),
    KEY `idx_application_id` (`application_id`),
    CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품별 리뷰 (상품별로 독립적으로 관리)';

-- ============================================
-- 3. 상품별 리뷰 통계 테이블 생성
-- ============================================

CREATE TABLE `product_review_statistics` (
    `product_id` INT(11) UNSIGNED NOT NULL,
    
    -- 총 평균 계산용 (합계 + 개수)
    `total_rating_sum` DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '별점 합계',
    `total_review_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '리뷰 개수',
    
    -- 인터넷 상품 항목별 통계
    `kindness_rating_sum` DECIMAL(12,2) DEFAULT 0 COMMENT '친절해요 합계',
    `kindness_review_count` INT(11) UNSIGNED DEFAULT 0 COMMENT '친절해요 리뷰 개수',
    `speed_rating_sum` DECIMAL(12,2) DEFAULT 0 COMMENT '설치 빨라요 합계',
    `speed_review_count` INT(11) UNSIGNED DEFAULT 0 COMMENT '설치 빨라요 리뷰 개수',
    
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`product_id`),
    KEY `idx_updated_at` (`updated_at`),
    CONSTRAINT `fk_product_statistics` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품별 리뷰 통계 (Immutable: 추가만, 수정/삭제 반영 안함)';

-- ============================================
-- 4. 리뷰 추가 시 통계 자동 업데이트 트리거
-- ============================================

DELIMITER $$

CREATE TRIGGER `trg_update_review_statistics_on_insert`
AFTER INSERT ON `product_reviews`
FOR EACH ROW
BEGIN
    -- 승인된 리뷰만 통계에 반영
    IF NEW.status = 'approved' THEN
        -- 기본 별점 통계 업데이트
        INSERT INTO `product_review_statistics` 
            (`product_id`, `total_rating_sum`, `total_review_count`)
        VALUES (NEW.product_id, NEW.rating, 1)
        ON DUPLICATE KEY UPDATE
            `total_rating_sum` = `total_rating_sum` + NEW.rating,
            `total_review_count` = `total_review_count` + 1,
            `updated_at` = NOW();
        
        -- 인터넷, MVNO, MNO 상품의 경우 항목별 통계도 업데이트
        IF NEW.product_type IN ('internet', 'mvno', 'mno') THEN
            IF NEW.kindness_rating IS NOT NULL THEN
                UPDATE `product_review_statistics`
                SET 
                    `kindness_rating_sum` = COALESCE(`kindness_rating_sum`, 0) + NEW.kindness_rating,
                    `kindness_review_count` = COALESCE(`kindness_review_count`, 0) + 1,
                    `updated_at` = NOW()
                WHERE product_id = NEW.product_id;
            END IF;
            
            IF NEW.speed_rating IS NOT NULL THEN
                UPDATE `product_review_statistics`
                SET 
                    `speed_rating_sum` = COALESCE(`speed_rating_sum`, 0) + NEW.speed_rating,
                    `speed_review_count` = COALESCE(`speed_review_count`, 0) + 1,
                    `updated_at` = NOW()
                WHERE product_id = NEW.product_id;
            END IF;
        END IF;
    END IF;
END$$

DELIMITER ;

-- ============================================
-- 5. 기존 리뷰 데이터 삭제 확인
-- ============================================

-- 기존 리뷰 데이터는 이미 테이블 삭제 시 함께 삭제됨
-- 추가 확인을 위한 쿼리 (실행 시 데이터가 없어야 함)
SELECT COUNT(*) as existing_reviews FROM product_reviews;

-- ============================================
-- 6. 인덱스 최적화
-- ============================================

-- 상품별 리뷰 조회 최적화
ALTER TABLE `product_reviews` 
ADD INDEX `idx_product_status_created` (`product_id`, `status`, `created_at`);

-- 평점 조회 최적화
ALTER TABLE `product_reviews` 
ADD INDEX `idx_product_rating` (`product_id`, `rating`);

-- ============================================
-- 완료 메시지
-- ============================================

SELECT '상품별 리뷰 시스템 마이그레이션 완료!' as message;












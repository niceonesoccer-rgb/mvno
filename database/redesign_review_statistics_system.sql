-- ============================================
-- 리뷰 통계 시스템 재설계
-- 모든 리뷰의 평균값을 정확히 계산하여 저장
-- ============================================

USE `mvno_db`;

-- ============================================
-- 1. 기존 통계 테이블 삭제 및 재생성
-- ============================================

-- 기존 트리거 삭제
DROP TRIGGER IF EXISTS `trg_update_review_statistics_on_insert`;
DROP TRIGGER IF EXISTS `trg_update_review_statistics_on_update`;
DROP TRIGGER IF EXISTS `trg_update_review_statistics_on_delete`;

-- 기존 통계 테이블 삭제 (주의: 데이터가 삭제됩니다)
-- DROP TABLE IF EXISTS `product_review_statistics`;

-- 통계 테이블 재생성 (간단하고 명확한 구조)
CREATE TABLE IF NOT EXISTS `product_review_statistics` (
    `product_id` INT(11) UNSIGNED NOT NULL PRIMARY KEY,
    
    -- 전체 평균 계산용 (모든 리뷰의 rating 합계)
    `total_rating_sum` DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '전체 별점 합계 (모든 리뷰의 rating 합)',
    `total_review_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '전체 리뷰 개수 (approved 상태만)',
    
    -- 항목별 평균 계산용 (MVNO, MNO, Internet 공통)
    `kindness_rating_sum` DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '친절해요 합계 (모든 리뷰의 kindness_rating 합)',
    `kindness_review_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '친절해요 리뷰 개수',
    
    `speed_rating_sum` DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '개통/설치 빨라요 합계 (모든 리뷰의 speed_rating 합)',
    `speed_review_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '개통/설치 빨라요 리뷰 개수',
    
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY `idx_updated_at` (`updated_at`),
    CONSTRAINT `fk_product_statistics` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품별 리뷰 통계 (모든 리뷰의 평균값 저장)';

-- ============================================
-- 2. INSERT 트리거: 리뷰 작성 시 통계 누적
-- ============================================

DELIMITER $$

CREATE TRIGGER `trg_update_review_statistics_on_insert`
AFTER INSERT ON `product_reviews`
FOR EACH ROW
BEGIN
    -- 승인된 리뷰만 통계에 반영
    IF NEW.status = 'approved' THEN
        -- 전체 별점 통계 누적 (합계 + 개수)
        INSERT INTO `product_review_statistics` 
            (`product_id`, `total_rating_sum`, `total_review_count`)
        VALUES (NEW.product_id, NEW.rating, 1)
        ON DUPLICATE KEY UPDATE
            `total_rating_sum` = `total_rating_sum` + NEW.rating,
            `total_review_count` = `total_review_count` + 1,
            `updated_at` = NOW();
        
        -- 항목별 통계 누적 (MVNO, MNO, Internet 모두)
        IF NEW.product_type IN ('internet', 'mvno', 'mno') THEN
            IF NEW.kindness_rating IS NOT NULL AND NEW.kindness_rating > 0 THEN
                UPDATE `product_review_statistics`
                SET 
                    `kindness_rating_sum` = `kindness_rating_sum` + NEW.kindness_rating,
                    `kindness_review_count` = `kindness_review_count` + 1,
                    `updated_at` = NOW()
                WHERE product_id = NEW.product_id;
            END IF;
            
            IF NEW.speed_rating IS NOT NULL AND NEW.speed_rating > 0 THEN
                UPDATE `product_review_statistics`
                SET 
                    `speed_rating_sum` = `speed_rating_sum` + NEW.speed_rating,
                    `speed_review_count` = `speed_review_count` + 1,
                    `updated_at` = NOW()
                WHERE product_id = NEW.product_id;
            END IF;
        END IF;
    END IF;
END$$

-- ============================================
-- 3. UPDATE 트리거: 리뷰 수정 시 통계 업데이트
-- ============================================

CREATE TRIGGER `trg_update_review_statistics_on_update`
AFTER UPDATE ON `product_reviews`
FOR EACH ROW
BEGIN
    -- 기존 리뷰가 승인된 상태였다면 통계에서 제거
    IF OLD.status = 'approved' THEN
        -- 전체 별점 통계에서 제거
        UPDATE `product_review_statistics`
        SET 
            `total_rating_sum` = GREATEST(`total_rating_sum` - OLD.rating, 0),
            `total_review_count` = GREATEST(`total_review_count` - 1, 0),
            `updated_at` = NOW()
        WHERE product_id = OLD.product_id;
        
        -- 항목별 통계에서 제거
        IF OLD.product_type IN ('internet', 'mvno', 'mno') THEN
            IF OLD.kindness_rating IS NOT NULL AND OLD.kindness_rating > 0 THEN
                UPDATE `product_review_statistics`
                SET 
                    `kindness_rating_sum` = GREATEST(`kindness_rating_sum` - OLD.kindness_rating, 0),
                    `kindness_review_count` = GREATEST(`kindness_review_count` - 1, 0),
                    `updated_at` = NOW()
                WHERE product_id = OLD.product_id;
            END IF;
            
            IF OLD.speed_rating IS NOT NULL AND OLD.speed_rating > 0 THEN
                UPDATE `product_review_statistics`
                SET 
                    `speed_rating_sum` = GREATEST(`speed_rating_sum` - OLD.speed_rating, 0),
                    `speed_review_count` = GREATEST(`speed_review_count` - 1, 0),
                    `updated_at` = NOW()
                WHERE product_id = OLD.product_id;
            END IF;
        END IF;
    END IF;
    
    -- 새 리뷰가 승인된 상태라면 통계에 추가
    IF NEW.status = 'approved' THEN
        -- 전체 별점 통계에 추가
        INSERT INTO `product_review_statistics` 
            (`product_id`, `total_rating_sum`, `total_review_count`)
        VALUES (NEW.product_id, NEW.rating, 1)
        ON DUPLICATE KEY UPDATE
            `total_rating_sum` = `total_rating_sum` + NEW.rating,
            `total_review_count` = `total_review_count` + 1,
            `updated_at` = NOW();
        
        -- 항목별 통계에 추가
        IF NEW.product_type IN ('internet', 'mvno', 'mno') THEN
            IF NEW.kindness_rating IS NOT NULL AND NEW.kindness_rating > 0 THEN
                UPDATE `product_review_statistics`
                SET 
                    `kindness_rating_sum` = `kindness_rating_sum` + NEW.kindness_rating,
                    `kindness_review_count` = `kindness_review_count` + 1,
                    `updated_at` = NOW()
                WHERE product_id = NEW.product_id;
            END IF;
            
            IF NEW.speed_rating IS NOT NULL AND NEW.speed_rating > 0 THEN
                UPDATE `product_review_statistics`
                SET 
                    `speed_rating_sum` = `speed_rating_sum` + NEW.speed_rating,
                    `speed_review_count` = `speed_review_count` + 1,
                    `updated_at` = NOW()
                WHERE product_id = NEW.product_id;
            END IF;
        END IF;
    END IF;
END$$

-- ============================================
-- 4. DELETE 트리거: 리뷰 삭제 시 통계 업데이트
-- ============================================

CREATE TRIGGER `trg_update_review_statistics_on_delete`
AFTER DELETE ON `product_reviews`
FOR EACH ROW
BEGIN
    -- 삭제된 리뷰가 승인된 상태였다면 통계에서 제거
    IF OLD.status = 'approved' THEN
        -- 전체 별점 통계에서 제거
        UPDATE `product_review_statistics`
        SET 
            `total_rating_sum` = GREATEST(`total_rating_sum` - OLD.rating, 0),
            `total_review_count` = GREATEST(`total_review_count` - 1, 0),
            `updated_at` = NOW()
        WHERE product_id = OLD.product_id;
        
        -- 항목별 통계에서 제거
        IF OLD.product_type IN ('internet', 'mvno', 'mno') THEN
            IF OLD.kindness_rating IS NOT NULL AND OLD.kindness_rating > 0 THEN
                UPDATE `product_review_statistics`
                SET 
                    `kindness_rating_sum` = GREATEST(`kindness_rating_sum` - OLD.kindness_rating, 0),
                    `kindness_review_count` = GREATEST(`kindness_review_count` - 1, 0),
                    `updated_at` = NOW()
                WHERE product_id = OLD.product_id;
            END IF;
            
            IF OLD.speed_rating IS NOT NULL AND OLD.speed_rating > 0 THEN
                UPDATE `product_review_statistics`
                SET 
                    `speed_rating_sum` = GREATEST(`speed_rating_sum` - OLD.speed_rating, 0),
                    `speed_review_count` = GREATEST(`speed_review_count` - 1, 0),
                    `updated_at` = NOW()
                WHERE product_id = OLD.product_id;
            END IF;
        END IF;
    END IF;
END$$

DELIMITER ;

-- ============================================
-- 5. 통계 테이블 인덱스 최적화
-- ============================================

-- 이미 PRIMARY KEY로 product_id가 인덱스되어 있음
-- 추가 인덱스는 필요시 생성

-- ============================================
-- 완료
-- ============================================







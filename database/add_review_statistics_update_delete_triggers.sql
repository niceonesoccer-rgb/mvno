-- ============================================
-- 리뷰 통계 UPDATE/DELETE 트리거 추가
-- 리뷰 수정/삭제 시 통계 자동 업데이트
-- ============================================

USE `mvno_db`;

DELIMITER $$

-- ============================================
-- 1. UPDATE 트리거: 리뷰 수정 시 통계 업데이트
-- ============================================
DROP TRIGGER IF EXISTS `trg_update_review_statistics_on_update`;

CREATE TRIGGER `trg_update_review_statistics_on_update`
AFTER UPDATE ON `product_reviews`
FOR EACH ROW
BEGIN
    -- 기존 리뷰가 승인된 상태였다면 통계에서 제거
    IF OLD.status = 'approved' THEN
        -- 기본 별점 통계 제거
        UPDATE `product_review_statistics`
        SET 
            `total_rating_sum` = GREATEST(`total_rating_sum` - OLD.rating, 0),
            `total_review_count` = GREATEST(`total_review_count` - 1, 0),
            `updated_at` = NOW()
        WHERE product_id = OLD.product_id;
        
        -- 항목별 통계 제거 (인터넷, MVNO, MNO, MNO-SIM)
        IF OLD.product_type IN ('internet', 'mvno', 'mno', 'mno-sim') THEN
            IF OLD.kindness_rating IS NOT NULL THEN
                UPDATE `product_review_statistics`
                SET 
                    `kindness_rating_sum` = GREATEST(`kindness_rating_sum` - OLD.kindness_rating, 0),
                    `kindness_review_count` = GREATEST(`kindness_review_count` - 1, 0),
                    `updated_at` = NOW()
                WHERE product_id = OLD.product_id;
            END IF;
            
            IF OLD.speed_rating IS NOT NULL THEN
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
        -- 기본 별점 통계 추가
        INSERT INTO `product_review_statistics` 
            (`product_id`, `total_rating_sum`, `total_review_count`)
        VALUES (NEW.product_id, NEW.rating, 1)
        ON DUPLICATE KEY UPDATE
            `total_rating_sum` = `total_rating_sum` + NEW.rating,
            `total_review_count` = `total_review_count` + 1,
            `updated_at` = NOW();
        
        -- 항목별 통계 추가 (인터넷, MVNO, MNO, MNO-SIM)
        IF NEW.product_type IN ('internet', 'mvno', 'mno', 'mno-sim') THEN
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

-- ============================================
-- 2. DELETE 트리거: 리뷰 삭제 시 통계 업데이트
-- ============================================
DROP TRIGGER IF EXISTS `trg_update_review_statistics_on_delete`;

CREATE TRIGGER `trg_update_review_statistics_on_delete`
AFTER DELETE ON `product_reviews`
FOR EACH ROW
BEGIN
    -- 삭제된 리뷰가 승인된 상태였다면 통계에서 제거
    IF OLD.status = 'approved' THEN
        -- 기본 별점 통계 제거
        UPDATE `product_review_statistics`
        SET 
            `total_rating_sum` = GREATEST(`total_rating_sum` - OLD.rating, 0),
            `total_review_count` = GREATEST(`total_review_count` - 1, 0),
            `updated_at` = NOW()
        WHERE product_id = OLD.product_id;
        
        -- 항목별 통계 제거 (인터넷, MVNO, MNO, MNO-SIM)
        IF OLD.product_type IN ('internet', 'mvno', 'mno', 'mno-sim') THEN
            IF OLD.kindness_rating IS NOT NULL THEN
                UPDATE `product_review_statistics`
                SET 
                    `kindness_rating_sum` = GREATEST(`kindness_rating_sum` - OLD.kindness_rating, 0),
                    `kindness_review_count` = GREATEST(`kindness_review_count` - 1, 0),
                    `updated_at` = NOW()
                WHERE product_id = OLD.product_id;
            END IF;
            
            IF OLD.speed_rating IS NOT NULL THEN
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
-- 3. 트리거 확인
-- ============================================
SELECT 
    TRIGGER_NAME,
    EVENT_MANIPULATION,
    EVENT_OBJECT_TABLE,
    ACTION_TIMING
FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = DATABASE()
AND TRIGGER_NAME LIKE 'trg_update_review_statistics%'
ORDER BY TRIGGER_NAME;






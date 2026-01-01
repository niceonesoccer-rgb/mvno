-- ============================================
-- rotation_advertisements 테이블 성능 최적화
-- 동시 접속 시 쿼리 성능 향상을 위한 복합 인덱스 추가
-- ============================================

USE `mvno_db`;

-- 기존 인덱스 확인
SHOW INDEX FROM `rotation_advertisements`;

-- 최적화된 복합 인덱스 추가
-- mno-sim.php에서 사용하는 쿼리 패턴에 맞춘 인덱스:
-- WHERE product_type = 'mno_sim' AND status = 'active' AND end_datetime > NOW()
-- ORDER BY display_order ASC, created_at ASC
ALTER TABLE `rotation_advertisements` 
ADD INDEX `idx_active_ads_query` (`product_type`, `status`, `end_datetime`, `display_order`, `created_at`);

-- 참고: MySQL은 인덱스의 왼쪽에서 오른쪽으로 사용하므로
-- WHERE 절의 순서와 인덱스 컬럼 순서를 맞추는 것이 중요합니다.
-- 이 인덱스는 다음 쿼리에서 효율적으로 사용됩니다:
-- SELECT ... WHERE product_type = ? AND status = ? AND end_datetime > ? ORDER BY display_order, created_at

-- ============================================
-- 인덱스 사용 확인 방법
-- ============================================
-- EXPLAIN SELECT ... FROM rotation_advertisements ra
-- WHERE ra.product_type = 'mno_sim'
-- AND ra.status = 'active'
-- AND ra.end_datetime > NOW()
-- ORDER BY ra.display_order ASC, ra.created_at ASC;

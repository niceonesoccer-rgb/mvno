-- ============================================
-- Q&A 테이블에 소프트 삭제 기능 추가
-- deleted_at 컬럼 추가
-- ============================================

USE `mvno_db`;

-- deleted_at 컬럼 추가
ALTER TABLE `qna` 
ADD COLUMN `deleted_at` DATETIME NULL COMMENT '삭제 일시 (소프트 삭제)' AFTER `updated_at`;

-- deleted_at에 인덱스 추가 (조회 성능 향상)
ALTER TABLE `qna` 
ADD INDEX `idx_deleted_at` (`deleted_at`);

-- 기존 데이터는 삭제되지 않은 것으로 표시 (deleted_at = NULL)
-- 이미 삭제된 데이터는 복구 불가능







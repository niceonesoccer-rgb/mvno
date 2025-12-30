-- 관리자 확인 관련 컬럼 제거 스크립트
-- seller_inquiries 테이블에서 admin_viewed_at, admin_viewed_by 컬럼 삭제

-- 1. 컬럼 삭제
ALTER TABLE `seller_inquiries` 
DROP COLUMN IF EXISTS `admin_viewed_at`,
DROP COLUMN IF EXISTS `admin_viewed_by`;




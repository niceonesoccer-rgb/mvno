-- ============================================
-- 입찰 시스템 테이블 삭제 스크립트
-- 생성일: 2025-01-XX
-- 주의: 이 스크립트는 모든 입찰 관련 테이블과 데이터를 삭제합니다!
-- ============================================

-- 데이터베이스 선택
USE `mvno_db`;

-- ============================================
-- 외래키 제약조건 때문에 삭제 순서가 중요합니다
-- 자식 테이블부터 먼저 삭제해야 합니다
-- ============================================

-- 1. 예치금 거래 내역 테이블 삭제 (외래키 없음)
DROP TABLE IF EXISTS `seller_deposit_transactions`;

-- 2. 판매자 예치금 계정 테이블 삭제 (외래키 없음)
DROP TABLE IF EXISTS `seller_deposits`;

-- 3. 낙찰자 게시물 배정 테이블 삭제 (bidding_rounds, bidding_participations, products 참조)
DROP TABLE IF EXISTS `bidding_product_assignments`;

-- 4. 입찰 참여 테이블 삭제 (bidding_rounds 참조)
DROP TABLE IF EXISTS `bidding_participations`;

-- 5. 입찰 라운드 테이블 삭제 (최상위 테이블)
DROP TABLE IF EXISTS `bidding_rounds`;

-- ============================================
-- 삭제 확인 쿼리
-- ============================================
-- 다음 쿼리로 삭제 여부를 확인할 수 있습니다:
-- 
-- SHOW TABLES LIKE 'bidding%';
-- SHOW TABLES LIKE 'seller_deposits%';
-- 
-- 결과가 없으면 정상적으로 삭제된 것입니다.

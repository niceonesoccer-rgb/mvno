-- ============================================
-- 프로덕션 DB 모든 테이블 삭제 스크립트
-- ganadamobile.co.kr (dbdanora) 배포용
-- ⚠️ 주의: 모든 데이터가 삭제됩니다!
-- ============================================

USE `dbdanora`;

-- 외래키 제약조건 비활성화 (테이블 삭제를 위해)
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- 모든 테이블 삭제 (프로덕션 DB 테이블 목록 기반)
-- ============================================

-- 상품 관련 테이블
DROP TABLE IF EXISTS `product_shares`;
DROP TABLE IF EXISTS `product_favorites`;
DROP TABLE IF EXISTS `product_reviews`;
DROP TABLE IF EXISTS `product_applications`;
DROP TABLE IF EXISTS `application_customers`;
DROP TABLE IF EXISTS `product_mno_sim_details`;
DROP TABLE IF EXISTS `product_internet_details`;
DROP TABLE IF EXISTS `product_mno_details`;
DROP TABLE IF EXISTS `product_mvno_details`;
DROP TABLE IF EXISTS `products`;

-- 포인트 관련 테이블
DROP TABLE IF EXISTS `user_point_ledger`;
DROP TABLE IF EXISTS `user_point_accounts`;

-- 사용자/판매자 관련 테이블
DROP TABLE IF EXISTS `seller_profiles`;
DROP TABLE IF EXISTS `admin_profiles`;
DROP TABLE IF EXISTS `users`;

-- 기타 테이블
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `terms_versions`;
DROP TABLE IF EXISTS `forbidden_ids`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `notices`;
DROP TABLE IF EXISTS `qna`;
DROP TABLE IF EXISTS `seller_inquiries`;
DROP TABLE IF EXISTS `seller_notices`;
DROP TABLE IF EXISTS `rotation_advertisements`;
DROP TABLE IF EXISTS `advertisement_analytics`;
DROP TABLE IF EXISTS `devices`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `profiles`;
DROP TABLE IF EXISTS `product_review_statistics`;

-- 추가 테이블들 (프로덕션에 있을 수 있는 테이블)
DROP TABLE IF EXISTS `tax_invoices`;
DROP TABLE IF EXISTS `tax_invoice_items`;
DROP TABLE IF EXISTS `email_verifications`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `login_logs`;
DROP TABLE IF EXISTS `admin_logs`;
DROP TABLE IF EXISTS `seller_logs`;

-- 외래키 제약조건 재활성화
SET FOREIGN_KEY_CHECKS = 1;

SELECT 'All tables dropped successfully!' AS message;

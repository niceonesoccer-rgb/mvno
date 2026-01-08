-- ============================================
-- 프로덕션 DB (dbdanora) 모든 테이블 삭제
-- ganadamobile.co.kr
-- ⚠️ 주의: 모든 데이터가 삭제됩니다!
-- ============================================

USE dbdanora;

-- 외래키 제약조건 비활성화 (테이블 삭제를 위해)
SET FOREIGN_KEY_CHECKS = 0;

-- 모든 테이블 삭제
DROP TABLE IF EXISTS `admin_profiles`;
DROP TABLE IF EXISTS `advertisement_analytics`;
DROP TABLE IF EXISTS `advertisement_clicks`;
DROP TABLE IF EXISTS `advertisement_impressions`;
DROP TABLE IF EXISTS `application_customers`;
DROP TABLE IF EXISTS `app_settings`;
DROP TABLE IF EXISTS `bank_accounts`;
DROP TABLE IF EXISTS `deposit_requests`;
DROP TABLE IF EXISTS `devices`;
DROP TABLE IF EXISTS `device_manufacturers`;
DROP TABLE IF EXISTS `device_storage_options`;
DROP TABLE IF EXISTS `email_verifications`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `event_detail_images`;
DROP TABLE IF EXISTS `event_products`;
DROP TABLE IF EXISTS `forbidden_ids`;
DROP TABLE IF EXISTS `notices`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `product_applications`;
DROP TABLE IF EXISTS `product_favorites`;
DROP TABLE IF EXISTS `product_internet_details`;
DROP TABLE IF EXISTS `product_mno_details`;
DROP TABLE IF EXISTS `product_mno_sim_details`;
DROP TABLE IF EXISTS `product_mvno_details`;
DROP TABLE IF EXISTS `product_reviews`;
DROP TABLE IF EXISTS `product_review_statistics`;
DROP TABLE IF EXISTS `product_shares`;
DROP TABLE IF EXISTS `qna`;
DROP TABLE IF EXISTS `rotation_advertisements`;
DROP TABLE IF EXISTS `rotation_advertisement_prices`;
DROP TABLE IF EXISTS `seller_deposit_accounts`;
DROP TABLE IF EXISTS `seller_deposit_ledger`;
DROP TABLE IF EXISTS `seller_inquiries`;
DROP TABLE IF EXISTS `seller_inquiry_attachments`;
DROP TABLE IF EXISTS `seller_inquiry_replies`;
DROP TABLE IF EXISTS `seller_profiles`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `terms_versions`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `user_point_accounts`;
DROP TABLE IF EXISTS `user_point_ledger`;

-- 외래키 제약조건 재활성화
SET FOREIGN_KEY_CHECKS = 1;

SELECT 'All tables dropped successfully!' AS message;

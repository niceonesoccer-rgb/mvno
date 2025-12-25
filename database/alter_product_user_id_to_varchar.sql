-- 상품 관련 테이블 user_id 타입 정합성 (DB-only)
-- 기존 로그인/세션의 user_id는 users.user_id(VARCHAR) 기반이므로
-- product_* 테이블의 user_id도 VARCHAR(50)로 통일한다.
--
-- 실행 전 DB 선택: USE `mvno_db`;

ALTER TABLE `product_favorites`
  MODIFY COLUMN `user_id` VARCHAR(50) NOT NULL;

ALTER TABLE `product_reviews`
  MODIFY COLUMN `user_id` VARCHAR(50) NOT NULL;

ALTER TABLE `product_shares`
  MODIFY COLUMN `user_id` VARCHAR(50) NULL;

-- MySQL 버전에 따라 "ADD COLUMN IF NOT EXISTS"가 지원되지 않을 수 있음
-- 아래는 일반 ALTER이며, 이미 컬럼이 있으면 에러가 날 수 있습니다.
-- 그 경우 이 줄은 건너뛰면 됩니다.
ALTER TABLE `product_applications`
  ADD COLUMN `user_id` VARCHAR(50) NULL AFTER `seller_id`;














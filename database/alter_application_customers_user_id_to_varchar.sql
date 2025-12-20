-- 신청 고객 정보 테이블 user_id 타입 정합성 (DB-only)
-- users.user_id(VARCHAR)와 동일한 타입으로 맞춘다.
-- 실행 전 DB 선택: USE `mvno_db`;

ALTER TABLE `application_customers`
  MODIFY COLUMN `user_id` VARCHAR(50) NULL COMMENT '회원 user_id (비회원 신청 가능)';








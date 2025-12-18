-- 이벤트 테이블 확장: 링크 저장 (DB-only)
-- 실행 전 DB 선택: USE `mvno_db`;

ALTER TABLE `events`
  ADD COLUMN `link_url` VARCHAR(1000) DEFAULT NULL AFTER `image_url`;




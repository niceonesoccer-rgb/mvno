-- 판매자 상담 URL 필드 추가
USE `mvno_db`;

-- users 테이블에 상담 관련 필드 추가
ALTER TABLE `users` 
ADD COLUMN `kakao_channel_url` VARCHAR(500) DEFAULT NULL COMMENT '카카오톡 채널 연결 URL' AFTER `mobile`,
ADD COLUMN `sns_consultation_url` VARCHAR(500) DEFAULT NULL COMMENT 'SNS 상담 연결 URL' AFTER `kakao_channel_url`,
ADD COLUMN `seller_name` VARCHAR(100) DEFAULT NULL COMMENT '판매자명 (사이트에서 표시되는 이름)' AFTER `sns_consultation_url`;


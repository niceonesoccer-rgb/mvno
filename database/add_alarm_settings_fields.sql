-- 알림 설정 필드 추가
USE `mvno_db`;

-- users 테이블에 알림 설정 필드 추가
ALTER TABLE `users` 
ADD COLUMN `alarm_settings` JSON DEFAULT NULL COMMENT '알림 설정 (JSON 형식)' AFTER `permissions_updated_at`,
ADD COLUMN `alarm_settings_updated_at` DATETIME DEFAULT NULL COMMENT '알림 설정 업데이트일' AFTER `alarm_settings`;

-- 또는 개별 필드로 저장하려면 아래처럼 할 수 있음
-- ALTER TABLE `users` 
-- ADD COLUMN `benefit_notification` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '혜택·이벤트 알림' AFTER `permissions_updated_at`,
-- ADD COLUMN `advertising_sms` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '광고성 SMS 수신 동의' AFTER `benefit_notification`,
-- ADD COLUMN `advertising_email` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '광고성 이메일 수신 동의' AFTER `advertising_sms`,
-- ADD COLUMN `advertising_push` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '광고성 앱 푸시 수신 동의' AFTER `advertising_email`,
-- ADD COLUMN `advertising_kakao` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '광고성 카카오 알림톡 수신 동의' AFTER `advertising_push`,
-- ADD COLUMN `alarm_settings_updated_at` DATETIME DEFAULT NULL COMMENT '알림 설정 업데이트일' AFTER `advertising_kakao`;


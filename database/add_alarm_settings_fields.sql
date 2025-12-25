-- 알림 설정 필드 추가
USE `mvno_db`;

-- users 테이블에 알림 설정 필드 추가 (권장: 화면 문구에 맞춘 개별 필드)
-- 필수 알림: 서비스 이용 및 혜택 안내 알림(필수)
-- 선택 알림: 광고성 정보 수신동의(선택) + 채널(이메일 / SMS,SNS / 앱푸시)
ALTER TABLE `users`
  ADD COLUMN `service_notice_opt_in` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '서비스 이용 및 혜택 안내 알림(필수)' AFTER `permissions_updated_at`,
  ADD COLUMN `marketing_opt_in` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '광고성 정보 수신동의(선택) 전체' AFTER `service_notice_opt_in`,
  ADD COLUMN `marketing_email_opt_in` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '광고성: 이메일 수신동의' AFTER `marketing_opt_in`,
  ADD COLUMN `marketing_sms_sns_opt_in` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '광고성: SMS,SNS 수신동의' AFTER `marketing_email_opt_in`,
  ADD COLUMN `marketing_push_opt_in` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '광고성: 앱 푸시 수신동의' AFTER `marketing_sms_sns_opt_in`,
  ADD COLUMN `alarm_settings_updated_at` DATETIME DEFAULT NULL COMMENT '알림 설정 업데이트일' AFTER `marketing_push_opt_in`;

-- (선택) 기존 JSON 방식도 같이 유지하고 싶으면 아래 추가
-- ALTER TABLE `users`
--   ADD COLUMN `alarm_settings` JSON DEFAULT NULL COMMENT '알림 설정 (JSON 형식)' AFTER `permissions_updated_at`,
--   ADD COLUMN `alarm_settings_updated_at` DATETIME DEFAULT NULL COMMENT '알림 설정 업데이트일' AFTER `alarm_settings`;
















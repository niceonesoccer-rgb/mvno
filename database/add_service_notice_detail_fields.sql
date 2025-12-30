-- 서비스 이용 및 혜택 안내 알림 하위 항목 필드 추가
USE `mvno_db`;

-- users 테이블에 서비스 이용 및 혜택 안내 알림 하위 항목 필드 추가
ALTER TABLE `users`
  ADD COLUMN `service_notice_plan_opt_in` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '서비스 이용 및 혜택 안내: 요금제 유지기간 만료 및 변경 안내' AFTER `service_notice_opt_in`,
  ADD COLUMN `service_notice_service_opt_in` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '서비스 이용 및 혜택 안내: 부가서비스 종료 및 이용 조건 변경 안내' AFTER `service_notice_plan_opt_in`,
  ADD COLUMN `service_notice_benefit_opt_in` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '서비스 이용 및 혜택 안내: 가입 고객 대상 혜택·이벤트 안내' AFTER `service_notice_service_opt_in`;










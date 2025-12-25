-- product_applications 테이블에 status_changed_at 필드 추가
-- 상태가 변경될 때마다 이 필드에 시간이 기록됩니다.

ALTER TABLE `product_applications` 
ADD COLUMN `status_changed_at` DATETIME DEFAULT NULL COMMENT '상태 변경일시' 
AFTER `application_status`;

-- 기존 데이터의 경우 updated_at 값을 status_changed_at에 복사
UPDATE `product_applications` 
SET `status_changed_at` = `updated_at` 
WHERE `status_changed_at` IS NULL;

















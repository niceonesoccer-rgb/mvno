-- 판매자 상담 URL 필드 추가
USE `mvno_db`;

-- users 테이블에 상담 관련 필드 추가
SET @mvno_db := DATABASE();

-- kakao_channel_url
SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @mvno_db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'kakao_channel_url'
);
SET @sql := IF(@col_exists = 0,
  "ALTER TABLE `users` ADD COLUMN `kakao_channel_url` VARCHAR(500) DEFAULT NULL COMMENT '카카오톡 채널 연결 URL'",
  "SELECT 'users.kakao_channel_url already exists' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- sns_consultation_url
SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @mvno_db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'sns_consultation_url'
);
SET @sql := IF(@col_exists = 0,
  "ALTER TABLE `users` ADD COLUMN `sns_consultation_url` VARCHAR(500) DEFAULT NULL COMMENT 'SNS 상담 연결 URL'",
  "SELECT 'users.sns_consultation_url already exists' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- seller_name (사이트 표시용 판매자명)
SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @mvno_db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'seller_name'
);
SET @sql := IF(@col_exists = 0,
  "ALTER TABLE `users` ADD COLUMN `seller_name` VARCHAR(100) DEFAULT NULL COMMENT '판매자명 (사이트에서 표시되는 이름)'",
  "SELECT 'users.seller_name already exists' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;










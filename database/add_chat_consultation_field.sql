-- 판매자 채팅상담 URL 필드 추가
USE `mvno_db`;

-- users 테이블에 채팅상담 URL 필드 추가
SET @mvno_db := DATABASE();

-- chat_consultation_url
SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @mvno_db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'chat_consultation_url'
);
SET @sql := IF(@col_exists = 0,
  "ALTER TABLE `users` ADD COLUMN `chat_consultation_url` VARCHAR(500) DEFAULT NULL COMMENT '채팅상담 URL (카카오톡 채널, 네이버톡톡 등)'",
  "SELECT 'users.chat_consultation_url already exists' AS info"
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;








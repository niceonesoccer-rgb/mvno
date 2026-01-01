-- users 테이블에 last_login 컬럼 추가
-- 3일 이상 미접속 판매자 상품 판매종료 처리 기능을 위한 컬럼

USE `mvno_db`;

-- last_login 컬럼 추가 (없는 경우에만)
SET @dbname = DATABASE();
SET @tablename = "users";
SET @columnname = "last_login";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN `", @columnname, "` DATETIME DEFAULT NULL COMMENT '최근 로그인 시간'")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 인덱스 추가 (없는 경우에만)
SET @indexname = "idx_last_login";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (INDEX_NAME = @indexname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD INDEX `", @indexname, "` (`", @columnname, "`)")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

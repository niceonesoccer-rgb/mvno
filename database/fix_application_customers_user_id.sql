-- application_customers 테이블에 user_id 컬럼 추가 (없는 경우)
-- 이 스크립트는 안전하게 실행할 수 있습니다 (이미 존재하면 에러 없이 무시됨)

-- user_id 컬럼이 있는지 확인하고 없으면 추가
SET @dbname = DATABASE();
SET @tablename = 'application_customers';
SET @columnname = 'user_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1', -- 컬럼이 이미 존재하면 아무것도 하지 않음
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` VARCHAR(50) DEFAULT NULL COMMENT ''회원 user_id (비회원 신청 가능)'' AFTER `application_id`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 인덱스 추가 (없는 경우)
SET @indexname = 'idx_user_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (index_name = @indexname)
  ) > 0,
  'SELECT 1', -- 인덱스가 이미 존재하면 아무것도 하지 않음
  CONCAT('ALTER TABLE `', @tablename, '` ADD INDEX `', @indexname, '` (`', @columnname, '`)')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SELECT 'application_customers 테이블에 user_id 컬럼이 추가되었습니다.' AS result;





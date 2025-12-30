-- 판매자 전용 공지사항 메인배너를 위한 컬럼 추가
-- notices 테이블에 target_audience, banner_type 컬럼 추가

USE `mvno_db`;

-- target_audience 컬럼 추가 (판매자 전용 공지사항 구분)
-- MySQL 5.7 이하 버전에서는 IF NOT EXISTS를 지원하지 않으므로, 컬럼 존재 여부 확인 후 추가
SET @dbname = DATABASE();
SET @tablename = 'notices';
SET @columnname = 'target_audience';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' ENUM(\'all\', \'seller\', \'user\') DEFAULT \'all\' COMMENT \'대상 사용자 (all: 전체, seller: 판매자만, user: 일반 사용자만)\' AFTER show_on_main')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- banner_type 컬럼 추가 (배너 타입: 텍스트만, 이미지만, 둘 다)
SET @columnname = 'banner_type';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' ENUM(\'text\', \'image\', \'both\') DEFAULT \'text\' COMMENT \'배너 타입 (text: 텍스트만, image: 이미지만, both: 둘 다)\' AFTER image_url')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;


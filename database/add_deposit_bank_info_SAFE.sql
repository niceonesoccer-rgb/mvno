-- ============================================
-- deposit_requests 테이블 계좌 정보 컬럼 추가
-- 안전 버전 (컬럼 존재 여부 체크 후 추가)
-- MySQL 8.0 이상 권장
-- ============================================

-- 1단계: bank_name 컬럼 추가 (없는 경우만)
SET @dbname = DATABASE();
SET @tablename = 'deposit_requests';
SET @columnname = 'bank_name';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT ''컬럼 bank_name이 이미 존재합니다.'' AS message',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(50) DEFAULT NULL COMMENT ''은행명 (입금 신청 시점의 정보)'';')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 2단계: account_number 컬럼 추가 (없는 경우만)
SET @columnname = 'account_number';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT ''컬럼 account_number가 이미 존재합니다.'' AS message',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(50) DEFAULT NULL COMMENT ''계좌번호 (입금 신청 시점의 정보)'';')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 3단계: account_holder 컬럼 추가 (없는 경우만)
SET @columnname = 'account_holder';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT ''컬럼 account_holder가 이미 존재합니다.'' AS message',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(100) DEFAULT NULL COMMENT ''예금주 (입금 신청 시점의 정보)'';')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 4단계: 기존 데이터 업데이트 (NULL이거나 빈 값인 경우만)
UPDATE deposit_requests dr
INNER JOIN bank_accounts ba ON dr.bank_account_id = ba.id
SET dr.bank_name = ba.bank_name,
    dr.account_number = ba.account_number,
    dr.account_holder = ba.account_holder
WHERE (dr.bank_name IS NULL OR dr.bank_name = '')
   OR (dr.account_number IS NULL OR dr.account_number = '')
   OR (dr.account_holder IS NULL OR dr.account_holder = '');

-- 5단계: 업데이트 결과 확인
SELECT 
    '데이터 상태 확인' AS 정보,
    COUNT(*) as 전체_입금신청,
    SUM(CASE WHEN bank_name IS NOT NULL AND bank_name != '' THEN 1 ELSE 0 END) as 은행명_있는건수,
    SUM(CASE WHEN account_number IS NOT NULL AND account_number != '' THEN 1 ELSE 0 END) as 계좌번호_있는건수,
    SUM(CASE WHEN account_holder IS NOT NULL AND account_holder != '' THEN 1 ELSE 0 END) as 예금주_있는건수,
    SUM(CASE WHEN bank_name IS NULL OR bank_name = '' THEN 1 ELSE 0 END) as 은행명_누락,
    SUM(CASE WHEN account_number IS NULL OR account_number = '' THEN 1 ELSE 0 END) as 계좌번호_누락,
    SUM(CASE WHEN account_holder IS NULL OR account_holder = '' THEN 1 ELSE 0 END) as 예금주_누락
FROM deposit_requests;

-- 완료 메시지
SELECT '완료! deposit_requests 테이블에 계좌 정보 컬럼이 추가되었습니다.' AS 완료메시지;

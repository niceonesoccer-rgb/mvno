-- ============================================
-- deposit_requests 테이블 계좌 정보 컬럼 추가
-- DBeaver에서 실행용 간단 버전
-- ============================================

-- ⚠️ 주의: 컬럼이 이미 있으면 오류가 발생할 수 있습니다. 무시하고 다음 단계로 진행하세요.

-- 1단계: 컬럼 추가 (없는 경우만)
ALTER TABLE deposit_requests 
ADD COLUMN IF NOT EXISTS bank_name VARCHAR(50) DEFAULT NULL COMMENT '은행명 (입금 신청 시점의 정보)',
ADD COLUMN IF NOT EXISTS account_number VARCHAR(50) DEFAULT NULL COMMENT '계좌번호 (입금 신청 시점의 정보)',
ADD COLUMN IF NOT EXISTS account_holder VARCHAR(100) DEFAULT NULL COMMENT '예금주 (입금 신청 시점의 정보)';

-- MySQL 5.7 이하 버전은 IF NOT EXISTS를 지원하지 않으므로 아래 방법 사용:
-- 각 컬럼을 개별적으로 추가 (오류 무시 가능)
-- ALTER TABLE deposit_requests ADD COLUMN bank_name VARCHAR(50) DEFAULT NULL COMMENT '은행명 (입금 신청 시점의 정보)';
-- ALTER TABLE deposit_requests ADD COLUMN account_number VARCHAR(50) DEFAULT NULL COMMENT '계좌번호 (입금 신청 시점의 정보)';
-- ALTER TABLE deposit_requests ADD COLUMN account_holder VARCHAR(100) DEFAULT NULL COMMENT '예금주 (입금 신청 시점의 정보)';

-- 2단계: 기존 데이터 업데이트 (NULL이거나 빈 값인 경우만)
UPDATE deposit_requests dr
INNER JOIN bank_accounts ba ON dr.bank_account_id = ba.id
SET dr.bank_name = ba.bank_name,
    dr.account_number = ba.account_number,
    dr.account_holder = ba.account_holder
WHERE (dr.bank_name IS NULL OR dr.bank_name = '')
   OR (dr.account_number IS NULL OR dr.account_number = '')
   OR (dr.account_holder IS NULL OR dr.account_holder = '');

-- 3단계: 업데이트 결과 확인
SELECT 
    COUNT(*) as 전체_입금신청,
    SUM(CASE WHEN bank_name IS NOT NULL AND bank_name != '' THEN 1 ELSE 0 END) as 은행명_있는건수,
    SUM(CASE WHEN account_number IS NOT NULL AND account_number != '' THEN 1 ELSE 0 END) as 계좌번호_있는건수,
    SUM(CASE WHEN account_holder IS NOT NULL AND account_holder != '' THEN 1 ELSE 0 END) as 예금주_있는건수,
    SUM(CASE WHEN bank_name IS NULL OR bank_name = '' THEN 1 ELSE 0 END) as 은행명_누락,
    SUM(CASE WHEN account_number IS NULL OR account_number = '' THEN 1 ELSE 0 END) as 계좌번호_누락,
    SUM(CASE WHEN account_holder IS NULL OR account_holder = '' THEN 1 ELSE 0 END) as 예금주_누락
FROM deposit_requests;

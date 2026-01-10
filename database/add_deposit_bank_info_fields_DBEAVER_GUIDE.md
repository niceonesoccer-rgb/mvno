# DBeaver에서 서버 데이터베이스 컬럼 추가 가이드

## 📋 작업 내용
`deposit_requests` 테이블에 계좌 정보 컬럼(`bank_name`, `account_number`, `account_holder`) 추가 및 기존 데이터 업데이트

---

## 🔧 방법 1: DBeaver에서 SQL 직접 실행 (권장)

### 단계 1: 서버 데이터베이스 연결
1. DBeaver 실행
2. 왼쪽 Database Navigator에서 **서버 데이터베이스 연결 선택**
   - 예: `dbdanora db.danora.gabia.io:3306`
   - 또는 프로덕션 서버 연결 선택
3. 해당 데이터베이스를 더블클릭하여 연결 확인

### 단계 2: SQL 편집기 열기
1. 상단 메뉴에서 **"SQL 편집기"** 클릭
   - 또는 단축키: `Ctrl + [` (대괄호)
   - 또는 툴바의 **SQL 편집기 아이콘** 클릭 (✏️ 모양)

### 단계 3: SQL 실행 (자동 실행 스크립트 사용)
1. 아래 SQL 문을 복사하여 SQL 편집기에 붙여넣기
2. **전체 실행** 버튼 클릭 (▶️) 또는 `Ctrl + Enter`

```sql
-- ============================================
-- deposit_requests 테이블 계좌 정보 컬럼 추가
-- ============================================

-- 1단계: 컬럼 존재 여부 확인 및 추가 (없는 경우만)
-- bank_name 컬럼 추가
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'deposit_requests' 
      AND COLUMN_NAME = 'bank_name'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE deposit_requests ADD COLUMN bank_name VARCHAR(50) DEFAULT NULL COMMENT ''은행명 (입금 신청 시점의 정보)''',
    'SELECT ''bank_name 컬럼이 이미 존재합니다.'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- account_number 컬럼 추가
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'deposit_requests' 
      AND COLUMN_NAME = 'account_number'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE deposit_requests ADD COLUMN account_number VARCHAR(50) DEFAULT NULL COMMENT ''계좌번호 (입금 신청 시점의 정보)''',
    'SELECT ''account_number 컬럼이 이미 존재합니다.'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- account_holder 컬럼 추가
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'deposit_requests' 
      AND COLUMN_NAME = 'account_holder'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE deposit_requests ADD COLUMN account_holder VARCHAR(100) DEFAULT NULL COMMENT ''예금주 (입금 신청 시점의 정보)''',
    'SELECT ''account_holder 컬럼이 이미 존재합니다.'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

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
    SUM(CASE WHEN bank_name IS NULL OR bank_name = '' THEN 1 ELSE 0 END) as 은행명_누락,
    SUM(CASE WHEN account_number IS NULL OR account_number = '' THEN 1 ELSE 0 END) as 계좌번호_누락,
    SUM(CASE WHEN account_holder IS NULL OR account_holder = '' THEN 1 ELSE 0 END) as 예금주_누락
FROM deposit_requests;

-- 완료 메시지
SELECT '완료! deposit_requests 테이블에 계좌 정보 컬럼이 추가되었습니다.' AS 완료메시지;
```

### 단계 4: 실행 결과 확인
- 아래 결과 패널에서 실행 결과 확인
- 오류가 없으면 "Success" 또는 성공 메시지 표시
- 마지막 SELECT 문에서 데이터 상태 확인

---

## 🔧 방법 2: 간단한 SQL 실행 (컬럼이 없는 경우만)

만약 서버에 아직 컬럼이 없다고 확신하는 경우, 아래 간단한 SQL만 실행하세요:

```sql
-- 1. 컬럼 추가 (이미 있으면 에러 발생 - 무시해도 됨)
ALTER TABLE deposit_requests 
ADD COLUMN bank_name VARCHAR(50) DEFAULT NULL COMMENT '은행명 (입금 신청 시점의 정보)',
ADD COLUMN account_number VARCHAR(50) DEFAULT NULL COMMENT '계좌번호 (입금 신청 시점의 정보)',
ADD COLUMN account_holder VARCHAR(100) DEFAULT NULL COMMENT '예금주 (입금 신청 시점의 정보)';

-- 2. 기존 데이터 업데이트
UPDATE deposit_requests dr
INNER JOIN bank_accounts ba ON dr.bank_account_id = ba.id
SET dr.bank_name = ba.bank_name,
    dr.account_number = ba.account_number,
    dr.account_holder = ba.account_holder
WHERE dr.bank_name IS NULL 
   OR dr.account_number IS NULL 
   OR dr.account_holder IS NULL;

-- 3. 확인
SELECT COUNT(*) as 전체건수,
       SUM(CASE WHEN bank_name IS NOT NULL THEN 1 ELSE 0 END) as 은행명_있는건수,
       SUM(CASE WHEN account_number IS NOT NULL THEN 1 ELSE 0 END) as 계좌번호_있는건수,
       SUM(CASE WHEN account_holder IS NOT NULL THEN 1 ELSE 0 END) as 예금주_있는건수
FROM deposit_requests;
```

---

## ⚠️ 주의사항

1. **백업 필수**: 작업 전에 데이터베이스 백업을 권장합니다.
   - DBeaver에서: 데이터베이스 우클릭 → "Backup" 또는 "Export"

2. **트랜잭션 확인**: 
   - DBeaver는 자동 커밋 모드일 수 있습니다.
   - 오류 발생 시 즉시 롤백하거나, 수동으로 롤백할 수 있습니다.

3. **실행 전 확인**:
   - 올바른 데이터베이스(서버 DB)에 연결되어 있는지 확인
   - 테스트 쿼리 실행으로 연결 확인: `SELECT 1;`

---

## 🔍 실행 후 확인 방법

### 방법 A: DBeaver 테이블 구조 확인
1. 왼쪽 Database Navigator에서 `deposit_requests` 테이블 찾기
2. 테이블을 우클릭 → **"Properties"** 또는 **"Edit Table"** 선택
3. Columns 탭에서 `bank_name`, `account_number`, `account_holder` 컬럼 확인

### 방법 B: SQL로 확인
```sql
-- 컬럼 존재 확인
SHOW COLUMNS FROM deposit_requests LIKE 'bank_name';
SHOW COLUMNS FROM deposit_requests LIKE 'account_number';
SHOW COLUMNS FROM deposit_requests LIKE 'account_holder';

-- 데이터 확인 (샘플)
SELECT id, seller_id, bank_name, account_number, account_holder 
FROM deposit_requests 
LIMIT 5;
```

---

## ❓ 문제 해결

### 오류: "Duplicate column name"
- 의미: 컬럼이 이미 존재함
- 해결: 무시하고 다음 단계(데이터 업데이트)로 진행

### 오류: "Table doesn't exist"
- 의미: 테이블 이름이 틀렸거나 다른 데이터베이스에 연결됨
- 해결: 올바른 데이터베이스 연결 확인

### 오류: "Foreign key constraint fails"
- 의미: `bank_accounts` 테이블의 데이터 문제
- 해결: `bank_accounts` 테이블 확인 후 다시 실행

---

## ✅ 완료 후 확인 체크리스트

- [ ] `bank_name` 컬럼이 `deposit_requests` 테이블에 추가됨
- [ ] `account_number` 컬럼이 `deposit_requests` 테이블에 추가됨
- [ ] `account_holder` 컬럼이 `deposit_requests` 테이블에 추가됨
- [ ] 기존 입금 신청 데이터에 계좌 정보가 채워짐
- [ ] 새 입금 신청 시 계좌 정보가 자동 저장됨 (코드 확인)

---

## 📞 추가 도움말

문제가 발생하면:
1. 오류 메시지 전체를 복사
2. 실행한 SQL 문 복사
3. 지원팀에 문의 또는 이 가이드 파일 공유

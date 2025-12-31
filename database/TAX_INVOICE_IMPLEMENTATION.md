# 세금계산서 발행 시스템 구현 가이드

## 📋 구현 완료 사항

### 1. 데이터베이스 스키마
- ✅ `deposit_requests` 테이블에 `tax_invoice_status` 컬럼 추가
- ✅ `tax_invoice_issued_at`, `tax_invoice_issued_by` 컬럼 추가
- ✅ 기존 `tax_invoice_issued` (TINYINT) → `tax_invoice_status` (ENUM) 마이그레이션
- ✅ 불필요한 `tax_invoice_period_start`, `tax_invoice_period_end` 컬럼 제거

### 2. 세금계산서 발행 페이지 (`/admin/tax-invoice/issue.php`)
- ✅ 기간별 입금 내역 조회
- ✅ 입금 상태 필터 (입금/미입금)
- ✅ 세금계산서 발행 상태 필터 (발행/미발행/취소)
- ✅ 기간별 합계 계산 (건수, 공급가액, 부가세, 합계금액)
- ✅ 입금 건 목록 표시
- ✅ 체크박스로 선택하여 일괄 상태 변경
- ✅ 이번달/지난달 빠른 선택 버튼

### 3. 기능 구현
- ✅ 입금 건별 세금계산서 발행 상태 관리
- ✅ 상태 변경 시 `tax_invoice_issued_at`, `tax_invoice_issued_by` 자동 업데이트
- ✅ 필터 파라미터 유지 (상태 변경 후에도 필터 유지)

---

## 🗄️ 데이터베이스 마이그레이션

### 방법 1: 마이그레이션 스크립트 실행 (권장)

브라우저에서 다음 URL 접속:
```
http://localhost/MVNO/database/create_tax_invoice_tables.php
```

이 스크립트는:
- `deposit_requests` 테이블의 세금계산서 관련 컬럼을 자동으로 확인하고 수정
- 기존 데이터를 마이그레이션
- 필요한 인덱스 생성

### 방법 2: SQL 직접 실행

`database/rotation_advertisement_schema.sql` 파일의 `deposit_requests` 테이블 부분을 확인하고, 필요한 경우 다음 SQL 실행:

```sql
-- tax_invoice_status 컬럼 추가 (없는 경우)
ALTER TABLE deposit_requests 
ADD COLUMN tax_invoice_status ENUM('unissued', 'issued', 'cancelled') NOT NULL DEFAULT 'unissued' 
COMMENT '세금계산서 발행 상태 (미발행, 발행, 취소)' 
AFTER rejected_reason;

-- tax_invoice_issued_at 컬럼 추가 (없는 경우)
ALTER TABLE deposit_requests 
ADD COLUMN tax_invoice_issued_at DATETIME DEFAULT NULL 
COMMENT '세금계산서 발행 일시' 
AFTER tax_invoice_status;

-- tax_invoice_issued_by 컬럼 추가 (없는 경우)
ALTER TABLE deposit_requests 
ADD COLUMN tax_invoice_issued_by VARCHAR(50) DEFAULT NULL 
COMMENT '세금계산서 발행 처리한 관리자 ID' 
AFTER tax_invoice_issued_at;

-- 인덱스 추가
ALTER TABLE deposit_requests ADD INDEX idx_tax_invoice_status (tax_invoice_status);

-- 기존 컬럼 제거 (있는 경우)
ALTER TABLE deposit_requests DROP COLUMN IF EXISTS tax_invoice_issued;
ALTER TABLE deposit_requests DROP COLUMN IF EXISTS tax_invoice_period_start;
ALTER TABLE deposit_requests DROP COLUMN IF EXISTS tax_invoice_period_end;
ALTER TABLE deposit_requests DROP INDEX IF EXISTS idx_tax_invoice_issued;
```

---

## 🔧 코드 구조

### 주요 파일
- `admin/tax-invoice/issue.php`: 세금계산서 발행 관리 페이지
- `database/create_tax_invoice_tables.php`: 마이그레이션 스크립트
- `database/rotation_advertisement_schema.sql`: 데이터베이스 스키마

### 주요 기능

#### 1. 입금 내역 조회
```php
// 기간별 입금 내역 조회
// - 입금 상태 필터 (confirmed/unpaid)
// - 세금계산서 발행 상태 필터 (issued/unissued/cancelled)
// - 기간 필터 (confirmed_at 또는 created_at 기준)
```

#### 2. 상태 변경
```php
// 체크박스로 선택한 입금 건들의 세금계산서 발행 상태 일괄 변경
// - 발행 상태로 변경 시 tax_invoice_issued_at, tax_invoice_issued_by 자동 기록
```

#### 3. 합계 계산
```php
// 기간별 합계 자동 계산
// - 입금 건수
// - 총 공급가액
// - 총 부가세
// - 총 합계금액
```

---

## ✅ 테스트 체크리스트

1. **데이터베이스 마이그레이션**
   - [ ] `create_tax_invoice_tables.php` 실행
   - [ ] `deposit_requests` 테이블에 `tax_invoice_status` 컬럼 확인
   - [ ] 기존 데이터가 올바르게 마이그레이션되었는지 확인

2. **페이지 접근**
   - [ ] `/admin/tax-invoice/issue.php` 접근 가능
   - [ ] 필터 영역이 정상적으로 표시되는지 확인

3. **조회 기능**
   - [ ] 기간 선택 후 조회 버튼 클릭
   - [ ] 입금 내역이 정상적으로 표시되는지 확인
   - [ ] 합계 정보가 정확하게 계산되는지 확인

4. **필터 기능**
   - [ ] 입금 상태 필터 (입금/미입금) 작동 확인
   - [ ] 세금계산서 발행 상태 필터 작동 확인
   - [ ] 이번달/지난달 버튼 작동 확인

5. **상태 변경 기능**
   - [ ] 체크박스로 입금 건 선택
   - [ ] 상태 선택 후 적용 버튼 클릭
   - [ ] 상태가 정상적으로 변경되는지 확인
   - [ ] 발행 상태로 변경 시 일시와 관리자 ID가 기록되는지 확인

---

## 🐛 문제 해결

### 문제: "deposit_requests 테이블이 존재하지 않습니다"
**해결**: `database/rotation_advertisement_schema.sql` 파일을 먼저 실행하여 테이블을 생성하세요.

### 문제: "tax_invoice_status 컬럼이 없습니다"
**해결**: `database/create_tax_invoice_tables.php` 스크립트를 실행하세요.

### 문제: 조회 결과가 없습니다
**해결**: 
- 기간을 올바르게 선택했는지 확인
- `deposit_requests` 테이블에 데이터가 있는지 확인
- 필터 조건이 너무 엄격한지 확인

### 문제: 상태 변경이 작동하지 않습니다
**해결**:
- 체크박스로 입금 건을 선택했는지 확인
- 관리자 세션이 정상적으로 설정되어 있는지 확인
- 데이터베이스 권한 확인

---

## 📝 추가 개발 사항

향후 추가할 수 있는 기능:
- [ ] 엑셀 다운로드 기능
- [ ] 페이지네이션 (입금 건이 많을 경우)
- [ ] 판매자별 그룹화 표시
- [ ] 세금계산서 발행 내역 페이지 (`/admin/tax-invoice/list.php`) 구현

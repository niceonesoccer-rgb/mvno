# 광고 시스템 변경사항 업데이트

## 📋 변경 사항 요약

### 1. 입금 신청 상태 변경

**변경 전:**
- 상태: `pending`, `confirmed`, `rejected`, `cancelled`, `unpaid`
- 거부 상태 존재

**변경 후:**
- 상태: `pending`, `confirmed`, `unpaid` (3가지)
- 거부 상태 제거
- **입금**(confirmed), **미입금**(unpaid) 두 가지로만 처리

**영향 받는 부분:**
- `deposit_requests` 테이블의 `status` 컬럼
- 관리자 입금 신청 목록 페이지 필터링
- 입금 신청 처리 로직

---

### 2. 세금계산서 발행 페이지 개선

**추가 기능:**
- 기간 설정 옆에 **"이번달"**, **"지난달"** 버튼 추가
- 클릭 시 해당 기간으로 자동 설정

**구현 예시:**
```javascript
// 이번달 버튼 클릭 시
const now = new Date();
const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);

// 지난달 버튼 클릭 시
const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
const lastMonthLastDay = new Date(now.getFullYear(), now.getMonth(), 0);
```

---

### 3. 세금계산서 발행 내역 상태 변경

**변경 전:**
- 상태: `issued`, `cancelled` (2가지)
- 기본값: `issued`

**변경 후:**
- 상태: `issued`, `unissued`, `cancelled` (3가지)
- 기본값: `unissued`
- **발행**(issued), **미발행**(unissued), **취소**(cancelled)

**영향 받는 부분:**
- `tax_invoices` 테이블의 `status` 컬럼
- 세금계산서 발행 내역 페이지 필터링

---

### 4. 광고 재신청 시 새로 등록

**요구사항:**
- 광고 종료된 상품을 다시 광고할 경우
- 새로 등록되어 이력이 순서대로 남아있어야 함

**설명:**
- 같은 상품에 대해 광고가 종료된 후 다시 신청할 때
- 새로운 레코드로 `rotation_advertisements` 테이블에 저장
- `created_at` 기준으로 이력이 순서대로 남음
- 기존 광고 레코드는 수정하지 않고 새로운 레코드 생성

**예시:**
```
상품 ID: 123

광고 1: 2025-01-01 ~ 2025-01-07 (종료)
광고 2: 2025-01-10 ~ 2025-01-17 (종료)
광고 3: 2025-01-20 ~ 2025-01-27 (진행 중)

→ 각각 별도의 레코드로 저장됨
→ created_at 기준으로 이력 관리
```

---

## 🔧 데이터베이스 변경 SQL

### 1. deposit_requests 테이블 수정

```sql
-- status 컬럼 수정 (rejected, cancelled 제거)
ALTER TABLE `deposit_requests` 
MODIFY COLUMN `status` ENUM('pending', 'confirmed', 'unpaid') NOT NULL DEFAULT 'pending' 
COMMENT '상태 (대기중, 입금확인, 미입금)';

-- 기존 rejected, cancelled 데이터가 있다면 처리 필요
-- rejected → unpaid로 변경 (또는 삭제)
-- cancelled → pending 또는 삭제
UPDATE `deposit_requests` 
SET `status` = 'unpaid' 
WHERE `status` IN ('rejected', 'cancelled');
```

### 2. tax_invoices 테이블 수정 (이미 unissued가 포함되어 있다면 수정 불필요)

```sql
-- status 컬럼에 unissued 추가 및 기본값 변경
ALTER TABLE `tax_invoices` 
MODIFY COLUMN `status` ENUM('issued', 'unissued', 'cancelled') NOT NULL DEFAULT 'unissued' 
COMMENT '상태 (발행, 미발행, 취소)';
```

---

## 📄 페이지 수정 사항

### 1. 관리자 - 입금 신청 목록 페이지

**변경 사항:**
- 필터링에서 "거부됨" 제거
- "입금", "미입금" 두 가지로만 필터링

**필터링 옵션:**
```
[전체] [대기중] [입금] [미입금]
```

---

### 2. 관리자 - 세금계산서 발행 페이지

**추가 사항:**
- 기간 설정 옆에 "이번달", "지난달" 버튼 추가

**UI 예시:**
```
기간 설정: [2025-01-01] ~ [2025-01-31] [조회] [이번달] [지난달]
```

---

### 3. 관리자 - 세금계산서 발행 내역 페이지

**변경 사항:**
- 필터링: "발행", "미발행", "취소"
- 상태 표시: "발행", "미발행", "취소"

---

### 4. 판매자 - 광고 신청 페이지

**변경 사항:**
- 광고 재신청 시 새로운 레코드로 등록 (기존 레코드 수정 안 함)
- 광고 내역 페이지에서 시간 순서대로 표시

---

## ✅ 확인 사항

1. ✅ 입금 신청 상태에서 `rejected`, `cancelled` 제거
2. ✅ 세금계산서 발행 페이지에 "이번달", "지난달" 버튼 추가
3. ✅ 세금계산서 발행 내역 상태: 발행/미발행/취소
4. ✅ 광고 재신청 시 새로 등록되어 이력 순서대로 남음

---

위 변경사항을 모든 설계 문서에 반영했습니다.

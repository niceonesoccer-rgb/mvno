# 입금 후 환불 요청 시스템 설계

## 📋 개요

입금 확인 후 환불 요청을 처리하는 시스템입니다.

---

## 🔄 현재 시스템 흐름

### 입금 확인 처리 (현재)
1. **입금 신청** (`status: pending`)
2. **입금 확인** (`status: confirmed`)
   - `seller_deposit_accounts.balance`에 `amount` 추가
   - `seller_deposit_ledger`에 `transaction_type: 'deposit'` 기록
3. **세금계산서 발행** (`tax_invoice_status: issued`)

---

## 💡 환불 기능 설계

### 1. 데이터베이스 변경

#### deposit_requests 테이블 수정
```sql
-- status ENUM에 'refunded' 추가
ALTER TABLE deposit_requests 
MODIFY COLUMN status ENUM('pending', 'confirmed', 'unpaid', 'refunded') 
NOT NULL DEFAULT 'pending' 
COMMENT '상태 (대기중, 입금, 미입금, 환불)';

-- 환불 관련 필드 추가
ALTER TABLE deposit_requests 
ADD COLUMN refunded_at DATETIME DEFAULT NULL COMMENT '환불 처리 일시' AFTER confirmed_at,
ADD COLUMN refunded_by VARCHAR(50) DEFAULT NULL COMMENT '환불 처리한 관리자 ID' AFTER refunded_at,
ADD COLUMN refund_reason TEXT DEFAULT NULL COMMENT '환불 사유' AFTER refunded_by;
```

### 2. 환불 처리 흐름

```
입금 확인 (confirmed)
    ↓
환불 요청
    ↓
환불 처리 (refunded)
    ├─ 예치금 차감 (seller_deposit_accounts.balance에서 amount 차감)
    ├─ 거래 내역 기록 (seller_deposit_ledger에 'refund' 기록)
    ├─ 세금계산서 취소 처리 (tax_invoice_status: 'cancelled')
    └─ 환불 정보 기록 (refunded_at, refunded_by, refund_reason)
```

### 3. 입금 신청 목록 표시

**상태 표시:**
- 대기중 (pending) - 노란색
- 입금 (confirmed) - 초록색
- 미입금 (unpaid) - 회색
- 환불 (refunded) - 빨간색

**필터 옵션:**
```
[전체] [대기중] [입금] [미입금] [환불]
```

**작업 버튼:**
- `pending`: 입금확인, 미입금
- `confirmed`: 환불처리 (새로 추가)
- `unpaid`: - (작업 없음)
- `refunded`: - (작업 없음)

---

## ⚠️ 잠재적 문제점 및 해결 방안

### 1. 예치금 잔액 부족 문제

**문제:**
- 입금 확인 후 예치금이 충전됨
- 판매자가 이미 예치금을 사용한 경우, 환불 시 잔액이 마이너스가 될 수 있음

**예시:**
```
입금: 100,000원 충전
사용: 80,000원 사용 (광고 신청 등)
잔액: 20,000원
환불: 100,000원 차감 → 잔액: -80,000원 ❌
```

**해결 방안:**
1. **환불 전 잔액 확인**
   ```php
   // 환불 가능 여부 확인
   $currentBalance = getSellerBalance($sellerId);
   $refundAmount = $depositRequest['amount'];
   
   if ($currentBalance < $refundAmount) {
       // 잔액 부족 시 경고 및 확인 절차
       // 또는 부분 환불 처리
   }
   ```

2. **부분 환불 허용**
   - 사용한 금액만큼은 환불 불가
   - 잔액만큼만 환불 처리

3. **환불 전 사용 내역 확인**
   - 해당 입금 건 이후의 사용 내역 확인
   - 사용 내역이 있으면 환불 불가 또는 경고

### 2. 세금계산서 발행 상태 문제

**문제:**
- 세금계산서가 이미 발행된 경우 (`tax_invoice_status: 'issued'`)
- 환불 시 세금계산서 취소 처리가 필요함

**해결 방안:**
```php
// 환불 처리 시
if ($depositRequest['tax_invoice_status'] === 'issued') {
    // 세금계산서 취소 처리
    UPDATE deposit_requests 
    SET tax_invoice_status = 'cancelled',
        tax_invoice_issued_at = NULL,
        tax_invoice_issued_by = NULL
    WHERE id = :id;
    
    // 경고 메시지: "세금계산서가 발행되었으므로 외부에서도 취소 처리 필요"
}
```

### 3. 거래 내역 일관성 문제

**문제:**
- 입금 내역과 환불 내역이 대응되지 않을 수 있음
- 환불 내역 추적이 어려울 수 있음

**해결 방안:**
```php
// seller_deposit_ledger에 환불 내역 기록
INSERT INTO seller_deposit_ledger (
    seller_id, 
    transaction_type,  // 'refund'
    amount,            // 음수 값 또는 절댓값
    balance_before, 
    balance_after,
    deposit_request_id,  // 원본 입금 신청 ID
    description,        // '예치금 환불 (입금 신청 #123)'
    created_at
) VALUES (...);
```

### 4. 동시성 문제

**문제:**
- 여러 관리자가 동시에 환불 처리 시도
- 예치금 잔액 계산 오류 가능

**해결 방안:**
```php
// 트랜잭션 및 잠금 사용
$pdo->beginTransaction();

// FOR UPDATE로 잠금
SELECT balance FROM seller_deposit_accounts 
WHERE seller_id = :seller_id FOR UPDATE;

// 환불 처리
// ...

$pdo->commit();
```

### 5. 환불 후 재환불 방지

**문제:**
- 이미 환불된 건을 다시 환불 처리할 수 있음

**해결 방안:**
```php
// 환불 처리 전 상태 확인
if ($depositRequest['status'] !== 'confirmed') {
    throw new Exception('환불 가능한 상태가 아닙니다.');
}

if ($depositRequest['status'] === 'refunded') {
    throw new Exception('이미 환불 처리된 입금 신청입니다.');
}
```

### 6. 판매자 알림 문제

**문제:**
- 환불 처리 시 판매자에게 알림이 없을 수 있음

**해결 방안:**
- 환불 처리 시 판매자에게 알림 발송 (이메일, SMS 등)
- 판매자 대시보드에 환불 내역 표시

### 7. 환불 금액 계산 문제

**문제:**
- 입금 금액 = 공급가액 + 부가세
- 환불 시 전체 금액 환불인지, 공급가액만 환불인지 불명확

**해결 방안:**
- **전체 금액 환불** (권장)
  - 입금한 금액 전체를 환불
  - `amount` (부가세 포함) 전체 차감

### 8. 환불 사유 필수 여부

**문제:**
- 환불 사유 없이 처리하면 추후 문제 발생 시 추적 어려움

**해결 방안:**
- 환불 사유 필수 입력
- 환불 사유 카테고리화 (선택사항)
  - 고객 요청
  - 입금 오류
  - 기타

---

## 📝 구현 체크리스트

### 데이터베이스
- [ ] `deposit_requests.status`에 'refunded' 추가
- [ ] `refunded_at`, `refunded_by`, `refund_reason` 컬럼 추가
- [ ] 인덱스 추가 (필요 시)

### 백엔드 로직
- [ ] 환불 처리 API 구현
- [ ] 예치금 차감 로직
- [ ] 거래 내역 기록
- [ ] 세금계산서 취소 처리
- [ ] 잔액 부족 체크
- [ ] 동시성 처리 (트랜잭션)

### 프론트엔드
- [ ] 입금 신청 목록에 '환불' 상태 표시
- [ ] 환불 필터 추가
- [ ] 환불 처리 모달
- [ ] 환불 사유 입력 필드
- [ ] 환불 전 확인 메시지

### 검증
- [ ] 환불 가능 여부 체크
- [ ] 잔액 부족 시 경고
- [ ] 세금계산서 발행 상태 확인
- [ ] 중복 환불 방지

---

## 🔍 추가 고려사항

### 1. 환불 승인 프로세스
- 단순 환불 vs 승인 필요 환불
- 대금액 환불 시 추가 승인 절차

### 2. 환불 이력 관리
- 환불 요청 → 환불 처리 → 환불 완료
- 환불 요청과 처리 분리

### 3. 부분 환불
- 전체 환불 vs 부분 환불
- 부분 환불 시 금액 입력

### 4. 환불 수단
- 원래 입금 계좌로 환불
- 다른 계좌로 환불
- 현금 환불

---

## 📊 상태 다이어그램

```
pending (대기중)
    ↓ [입금확인]
confirmed (입금)
    ↓ [환불처리]
refunded (환불)
    
pending (대기중)
    ↓ [미입금처리]
unpaid (미입금)
```

---

## 💻 코드 예시

### 환불 처리 로직 (의사코드)

```php
function processRefund($requestId, $adminId, $refundReason) {
    $pdo->beginTransaction();
    
    try {
        // 1. 입금 신청 정보 조회 (잠금)
        $request = getDepositRequest($requestId, FOR_UPDATE);
        
        // 2. 상태 확인
        if ($request['status'] !== 'confirmed') {
            throw new Exception('환불 가능한 상태가 아닙니다.');
        }
        
        // 3. 예치금 잔액 확인
        $balance = getSellerBalance($request['seller_id'], FOR_UPDATE);
        if ($balance < $request['amount']) {
            // 경고: 잔액 부족, 부분 환불 또는 확인 필요
            throw new Exception('예치금 잔액이 부족합니다. 잔액: ' . $balance);
        }
        
        // 4. 예치금 차감
        $newBalance = $balance - $request['amount'];
        updateSellerBalance($request['seller_id'], $newBalance);
        
        // 5. 거래 내역 기록
        recordTransaction(
            seller_id: $request['seller_id'],
            type: 'refund',
            amount: -$request['amount'],  // 음수
            balance_before: $balance,
            balance_after: $newBalance,
            deposit_request_id: $requestId,
            description: '예치금 환불 (입금 신청 #' . $requestId . ')'
        );
        
        // 6. 입금 신청 상태 변경
        updateDepositRequest(
            id: $requestId,
            status: 'refunded',
            refunded_at: NOW(),
            refunded_by: $adminId,
            refund_reason: $refundReason
        );
        
        // 7. 세금계산서 취소 처리 (발행된 경우)
        if ($request['tax_invoice_status'] === 'issued') {
            updateDepositRequest(
                id: $requestId,
                tax_invoice_status: 'cancelled',
                tax_invoice_issued_at: NULL,
                tax_invoice_issued_by: NULL
            );
            // 경고: 외부 세금계산서 취소 처리 필요
        }
        
        $pdo->commit();
        return ['success' => true, 'message' => '환불 처리 완료'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
```

---

## ✅ 권장 사항

1. **환불 전 확인 절차 강화**
   - 잔액 확인
   - 사용 내역 확인
   - 세금계산서 발행 상태 확인

2. **환불 사유 필수 입력**
   - 추적 가능하도록

3. **환불 이력 관리**
   - 모든 환불 내역 기록
   - 환불 처리자, 처리 일시 기록

4. **알림 시스템**
   - 판매자에게 환불 처리 알림

5. **권한 관리**
   - 환불 처리 권한 분리
   - 대금액 환불 시 추가 승인

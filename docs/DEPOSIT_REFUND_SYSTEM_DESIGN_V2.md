# 입금 후 환불 요청 시스템 설계 (V2 - 금액 입력 방식)

## 📋 개요

입금 확인 후 환불 요청을 처리하는 시스템입니다.  
**관리자가 환불 금액을 직접 입력하여 유연하게 환불 처리**할 수 있습니다.

---

## 💡 핵심 설계 변경점

### V1 (고정 금액 환불) → V2 (금액 입력 환불)

**V1의 문제점:**
- 전체 금액만 환불 가능
- 사용한 금액이 있으면 환불 불가
- 부분 환불 불가능

**V2의 장점:**
- ✅ 전체 환불 가능
- ✅ 부분 환불 가능
- ✅ 잔액만큼만 환불 가능
- ✅ 탈퇴 시 사용 중인 금액 제외하고 환불 가능
- ✅ 유연한 환불 처리

---

## 🔄 환불 처리 흐름

### 1. 환불 요청 시점
```
입금 확인 (confirmed)
    ↓
[환불처리] 버튼 클릭
    ↓
환불 모달 열림
    ├─ 원본 입금 금액: 100,000원
    ├─ 현재 예치금 잔액: 20,000원
    ├─ 환불 가능 금액: 20,000원 (최대 잔액까지)
    ├─ 환불 금액 입력: [입력 필드]
    └─ 환불 사유 입력: [입력 필드]
    ↓
[환불 처리] 버튼
```

### 2. 환불 금액 계산 로직

```php
// 환불 가능 금액 계산
function getRefundableAmount($depositRequestId) {
    // 1. 원본 입금 금액
    $originalAmount = $depositRequest['amount']; // 100,000원
    
    // 2. 현재 예치금 잔액
    $currentBalance = getSellerBalance($sellerId); // 20,000원
    
    // 3. 환불 가능 금액 = min(원본 금액, 현재 잔액)
    $refundableAmount = min($originalAmount, $currentBalance);
    
    return [
        'original_amount' => $originalAmount,      // 100,000원
        'current_balance' => $currentBalance,      // 20,000원
        'refundable_amount' => $refundableAmount,  // 20,000원 (최대)
        'used_amount' => $originalAmount - $currentBalance // 80,000원 (사용됨)
    ];
}
```

### 3. 환불 처리 프로세스

```php
function processRefund($requestId, $adminId, $refundAmount, $refundReason) {
    // 1. 입력 검증
    if ($refundAmount <= 0) {
        throw new Exception('환불 금액은 0보다 커야 합니다.');
    }
    
    // 2. 환불 가능 금액 확인
    $refundableInfo = getRefundableAmount($requestId);
    if ($refundAmount > $refundableInfo['refundable_amount']) {
        throw new Exception('환불 가능 금액을 초과했습니다. (최대: ' . $refundableInfo['refundable_amount'] . '원)');
    }
    
    // 3. 잔액 확인 (이중 체크)
    $currentBalance = getSellerBalance($sellerId, FOR_UPDATE);
    if ($refundAmount > $currentBalance) {
        throw new Exception('예치금 잔액이 부족합니다.');
    }
    
    // 4. 환불 처리 (트랜잭션)
    $pdo->beginTransaction();
    try {
        // 예치금 차감
        $newBalance = $currentBalance - $refundAmount;
        updateSellerBalance($sellerId, $newBalance);
        
        // 거래 내역 기록
        recordTransaction(
            seller_id: $sellerId,
            type: 'refund',
            amount: -$refundAmount,
            balance_before: $currentBalance,
            balance_after: $newBalance,
            deposit_request_id: $requestId,
            description: '예치금 환불 (입금 신청 #' . $requestId . ', 금액: ' . $refundAmount . '원)'
        );
        
        // 입금 신청 상태 업데이트
        // 주의: 부분 환불인 경우도 'refunded'로 처리할지, 별도 상태로 할지 결정 필요
        if ($refundAmount >= $refundableInfo['original_amount']) {
            // 전체 환불
            updateDepositRequest(
                id: $requestId,
                status: 'refunded',
                refunded_at: NOW(),
                refunded_by: $adminId,
                refund_reason: $refundReason
            );
        } else {
            // 부분 환불
            // 옵션 1: 상태를 'partially_refunded'로 (새로운 상태 추가 필요)
            // 옵션 2: 상태는 'confirmed' 유지, 별도 환불 기록 테이블 사용
            // 옵션 3: 상태를 'refunded'로 변경하되, refund_amount 필드 추가
        }
        
        $pdo->commit();
        return ['success' => true];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
```

---

## 🗄️ 데이터베이스 설계

### 1. deposit_requests 테이블 수정

```sql
-- status ENUM에 'refunded' 추가 (부분 환불도 고려)
ALTER TABLE deposit_requests 
MODIFY COLUMN status ENUM('pending', 'confirmed', 'unpaid', 'refunded', 'partially_refunded') 
NOT NULL DEFAULT 'pending' 
COMMENT '상태 (대기중, 입금, 미입금, 환불, 부분환불)';

-- 환불 관련 필드 추가
ALTER TABLE deposit_requests 
ADD COLUMN refunded_amount DECIMAL(12,2) DEFAULT NULL COMMENT '환불 금액' AFTER tax_invoice_issued_by,
ADD COLUMN refunded_at DATETIME DEFAULT NULL COMMENT '환불 처리 일시' AFTER refunded_amount,
ADD COLUMN refunded_by VARCHAR(50) DEFAULT NULL COMMENT '환불 처리한 관리자 ID' AFTER refunded_at,
ADD COLUMN refund_reason TEXT DEFAULT NULL COMMENT '환불 사유' AFTER refunded_by;

-- 인덱스 추가 (필요 시)
ALTER TABLE deposit_requests ADD INDEX idx_refunded_at (refunded_at);
```

### 2. 부분 환불 처리 방식 옵션

#### 옵션 1: 상태를 'partially_refunded'로 변경
**장점:**
- 상태로 부분 환불 여부를 명확히 알 수 있음
- 추가 환불 가능 여부를 상태로 확인 가능

**단점:**
- 상태가 복잡해짐
- 여러 번 부분 환불 시 추적 어려움

#### 옵션 2: 별도 환불 기록 테이블 사용 (권장)
```sql
CREATE TABLE deposit_refunds (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    deposit_request_id INT(11) UNSIGNED NOT NULL,
    refund_amount DECIMAL(12,2) NOT NULL,
    refunded_by VARCHAR(50) NOT NULL,
    refunded_at DATETIME NOT NULL,
    refund_reason TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_deposit_request_id (deposit_request_id),
    FOREIGN KEY (deposit_request_id) REFERENCES deposit_requests(id)
) COMMENT='입금 환불 내역';
```

**장점:**
- 여러 번 부분 환불 가능
- 환불 이력 추적 용이
- 원본 입금 신청과 분리되어 관리 용이

**단점:**
- 테이블 추가 필요

#### 옵션 3: deposit_requests에 refunded_amount만 추가 (간단)
```sql
-- deposit_requests 테이블에만 refunded_amount 추가
-- status는 'refunded'로 변경
-- 환불 금액이 원본 금액보다 작으면 부분 환불로 간주
```

**장점:**
- 구현 간단
- 추가 테이블 불필요

**단점:**
- 여러 번 부분 환불 불가 (한 번만 환불 가능)
- 환불 이력 추적 어려움

---

## 💻 UI/UX 설계

### 환불 처리 모달

```
┌─────────────────────────────────────────┐
│ 환불 처리                           [×] │
├─────────────────────────────────────────┤
│                                         │
│ 판매자: seller123                       │
│                                         │
│ ┌─────────────────────────────────┐   │
│ │ 입금 정보                        │   │
│ │ 원본 입금 금액: 100,000원        │   │
│ │ 현재 예치금 잔액: 20,000원       │   │
│ │ 사용된 금액: 80,000원            │   │
│ │ 환불 가능 금액: 20,000원         │   │
│ └─────────────────────────────────┘   │
│                                         │
│ 환불 금액 *                            │
│ ┌─────────────────────────────────┐   │
│ │ [20,000        ] [최대]          │   │
│ └─────────────────────────────────┘   │
│ 최대 환불 가능 금액: 20,000원          │
│                                         │
│ 환불 사유 *                            │
│ ┌─────────────────────────────────┐   │
│ │ [                              ] │   │
│ └─────────────────────────────────┘   │
│                                         │
│ ⚠️ 주의사항                            │
│ • 환불 후 예치금 잔액: 0원             │
│ • 환불 가능 금액 이상 환불 불가        │
│                                         │
│ [취소]  [환불 처리]                     │
└─────────────────────────────────────────┘
```

### JavaScript 유효성 검증

```javascript
function validateRefundAmount(inputAmount, maxAmount) {
    if (inputAmount <= 0) {
        return { valid: false, message: '환불 금액은 0보다 커야 합니다.' };
    }
    
    if (inputAmount > maxAmount) {
        return { 
            valid: false, 
            message: '환불 가능 금액을 초과했습니다. (최대: ' + maxAmount + '원)' 
        };
    }
    
    return { valid: true };
}

// 최대 금액 버튼 클릭
function setMaxRefundAmount() {
    document.getElementById('refundAmount').value = maxRefundableAmount;
    updateBalancePreview();
}

// 금액 입력 시 미리보기
function updateBalancePreview() {
    const refundAmount = parseFloat(document.getElementById('refundAmount').value) || 0;
    const currentBalance = parseFloat(currentBalanceValue);
    const newBalance = currentBalance - refundAmount;
    
    document.getElementById('balanceAfterRefund').textContent = 
        new Intl.NumberFormat('ko-KR').format(newBalance) + '원';
}
```

---

## ⚠️ 주요 검증 사항

### 1. 환불 금액 검증

```php
// 프론트엔드 검증 (즉시 피드백)
function validateRefundAmount(inputAmount, maxAmount) {
    if (inputAmount <= 0) {
        return { valid: false, message: '환불 금액은 0보다 커야 합니다.' };
    }
    if (inputAmount > maxAmount) {
        return { valid: false, message: '환불 가능 금액을 초과했습니다.' };
    }
    return { valid: true };
}

// 백엔드 검증 (최종 검증)
function validateRefundRequest($requestId, $refundAmount) {
    // 1. 입금 신청 상태 확인
    $request = getDepositRequest($requestId);
    if ($request['status'] !== 'confirmed') {
        throw new Exception('환불 가능한 상태가 아닙니다.');
    }
    
    // 2. 환불 가능 금액 계산
    $refundableInfo = getRefundableAmount($requestId);
    
    // 3. 입력 금액 검증
    if ($refundAmount <= 0) {
        throw new Exception('환불 금액은 0보다 커야 합니다.');
    }
    if ($refundAmount > $refundableInfo['refundable_amount']) {
        throw new Exception('환불 가능 금액을 초과했습니다.');
    }
    
    // 4. 현재 잔액 확인 (이중 체크)
    $currentBalance = getSellerBalance($request['seller_id'], FOR_UPDATE);
    if ($refundAmount > $currentBalance) {
        throw new Exception('예치금 잔액이 부족합니다.');
    }
    
    return true;
}
```

### 2. 세금계산서 발행 상태 확인

```php
// 환불 처리 전 세금계산서 상태 확인
if ($request['tax_invoice_status'] === 'issued') {
    // 경고 메시지 표시
    $warning = '세금계산서가 발행되었습니다. 환불 후 외부에서도 세금계산서 취소 처리가 필요합니다.';
    
    // 환불 처리 시 자동으로 취소 상태로 변경
    updateDepositRequest(
        id: $requestId,
        tax_invoice_status: 'cancelled'
    );
}
```

---

## 📊 사용 시나리오

### 시나리오 1: 전체 환불

```
입금: 100,000원
사용: 0원
잔액: 100,000원
환불 가능: 100,000원
환불 금액 입력: 100,000원
→ 전체 환불
```

### 시나리오 2: 부분 환불 (잔액만큼만)

```
입금: 100,000원
사용: 80,000원
잔액: 20,000원
환불 가능: 20,000원 (최대)
환불 금액 입력: 20,000원
→ 부분 환불 (사용 중인 금액 제외)
```

### 시나리오 3: 일부만 환불

```
입금: 100,000원
사용: 30,000원
잔액: 70,000원
환불 가능: 70,000원 (최대)
환불 금액 입력: 50,000원
→ 50,000원만 환불, 20,000원은 예치금 잔액에 남음
```

### 시나리오 4: 탈퇴 시 부분 환불

```
입금: 100,000원
사용: 60,000원 (광고 신청 등)
잔액: 40,000원
환불 가능: 40,000원 (최대)
환불 금액 입력: 40,000원
→ 사용 중인 60,000원은 차감, 40,000원만 환불
```

---

## ✅ 구현 우선순위

### Phase 1: 기본 환불 기능 (옵션 3 - 간단한 방식)
1. `deposit_requests`에 `refunded_amount` 필드 추가
2. 상태에 `'refunded'` 추가
3. 환불 모달 UI 구현
4. 환불 금액 입력 기능
5. 환불 처리 로직 구현

### Phase 2: 고급 기능 (옵션 2 - 환불 기록 테이블)
1. `deposit_refunds` 테이블 추가
2. 여러 번 부분 환불 기능
3. 환불 이력 조회 기능

---

## 🎯 권장 사항

1. **초기 구현: 옵션 3 (간단한 방식)**
   - 빠른 구현 가능
   - 대부분의 사용 사례 커버
   - 한 번의 환불 처리로 충분

2. **향후 확장: 옵션 2 (환불 기록 테이블)**
   - 여러 번 부분 환불 필요 시 추가
   - 환불 이력 관리 필요 시 추가

3. **환불 금액 입력 방식의 장점**
   - ✅ 유연한 환불 처리
   - ✅ 탈퇴 시 부분 환불 가능
   - ✅ 잔액 부족 문제 자연스럽게 해결
   - ✅ 관리자가 상황에 맞게 조절 가능

4. **사용자 경험 개선**
   - 환불 가능 금액 표시
   - 최대 금액 버튼 제공
   - 환불 후 잔액 미리보기
   - 명확한 경고 메시지

---

## 📝 최종 정리

**핵심 설계:**
- ✅ 관리자가 환불 금액을 직접 입력
- ✅ 환불 가능 금액을 계산하여 표시
- ✅ 입력 금액만큼 예치금 차감
- ✅ 부분 환불 지원 (탈퇴 등 상황 대응)
- ✅ 유연하고 실용적인 환불 시스템

**이 방식이 V1보다 우수한 이유:**
1. 실제 사용 사례에 맞음 (탈퇴 시 부분 환불)
2. 잔액 부족 문제 해결
3. 유연한 환불 처리
4. 관리자가 상황에 맞게 조절 가능

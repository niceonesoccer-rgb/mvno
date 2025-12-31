# 세금계산서 발행 시스템 설계서

## 📋 개요

광고 금액에 부가세 10%를 추가하여 입금을 받고, 입금 내역을 관리하여 세금계산서를 발행하는 시스템입니다.

---

## ✅ 요구사항 정리

### 1. 부가세 계산
- **공급가액**: 광고 금액 (예: 100,000원)
- **부가세**: 공급가액의 10% (예: 10,000원)
- **입금금액**: 공급가액 + 부가세 = 공급가액 × 1.1 (예: 110,000원)
- **표시**: 광고 신청 시 입금금액(부가세 포함)으로 표시

### 2. 입금 내역 관리
- **입금 확인 처리**: 관리자가 입금 확인 시 예치금 충전
- **미입금 분류**: 입금 신청 후 3일 이내 입금 확인이 안되면 "미입금"으로 자동 분류
- **미입금 입금완료 처리**: 미입금 상태에서도 나중에 입금 확인 시 입금완료 처리 가능
- **입금 내역 저장**: 세금계산서 발행 시 참고할 수 있도록 입금 내역 저장
- **입금 상태**: "대기중(pending)", "입금(confirmed)", "미입금(unpaid)" 세 가지 상태만 사용 (거부됨 상태 제거)

### 3. 세금계산서 발행 관리
- **발행 주기**: 매월 합산해서 익월말에 세금계산서 발행
- **기간 설정**: 관리자가 기간을 설정하여 조회 가능
- **빠른 기간 선택**: "이번달", "지난달" 버튼 제공
- **업체별 합산**: 입금 업체(판매자)별로 금액 합산 표시
- **발행 상태 관리**:
  - 세금계산서 발행 (issued)
  - 세금계산서 발행 미발행 (unissued) - 기본값
  - 세금계산서 발행 취소 (cancelled)
- **발행 완료 처리**: 관리자가 발행 완료 처리하면 해당 기간의 입금 내역은 "발행완료" 상태로 변경

---

## 🗄️ 데이터베이스 설계

### 1. 예치금 충전 신청 테이블 (deposit_requests) - 수정

```sql
CREATE TABLE IF NOT EXISTS `deposit_requests` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
    `bank_account_id` INT(11) UNSIGNED NOT NULL COMMENT '입금할 계좌 ID (bank_accounts.id)',
    `depositor_name` VARCHAR(100) NOT NULL COMMENT '입금자명',
    `amount` DECIMAL(12,2) NOT NULL COMMENT '입금 금액 (부가세 포함)',
    `supply_amount` DECIMAL(12,2) NOT NULL COMMENT '공급가액 (부가세 제외)',
    `tax_amount` DECIMAL(12,2) NOT NULL COMMENT '부가세 (공급가액의 10%)',
    `status` ENUM('pending', 'confirmed', 'unpaid') NOT NULL DEFAULT 'pending' COMMENT '상태 (대기중, 입금확인, 미입금)',
    `admin_id` VARCHAR(50) DEFAULT NULL COMMENT '처리한 관리자 ID',
    `confirmed_at` DATETIME DEFAULT NULL COMMENT '확인 일시',
    `rejected_reason` TEXT DEFAULT NULL COMMENT '거부 사유',
    `tax_invoice_status` ENUM('unissued', 'issued', 'cancelled') NOT NULL DEFAULT 'unissued' COMMENT '세금계산서 발행 상태 (미발행, 발행, 취소)',
    `tax_invoice_issued_at` DATETIME DEFAULT NULL COMMENT '세금계산서 발행 일시',
    `tax_invoice_issued_by` VARCHAR(50) DEFAULT NULL COMMENT '세금계산서 발행 처리한 관리자 ID',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_bank_account_id` (`bank_account_id`),
    KEY `idx_status` (`status`),
    KEY `idx_tax_invoice_status` (`tax_invoice_status`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_confirmed_at` (`confirmed_at`),
    CONSTRAINT `fk_deposit_request_bank_account` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='예치금 충전 신청 (무통장 입금)';
```

**컬럼 설명:**
- `amount`: 입금 금액 (부가세 포함, 예: 110,000원)
- `supply_amount`: 공급가액 (부가세 제외, 예: 100,000원)
- `tax_amount`: 부가세 (공급가액의 10%, 예: 10,000원)
- `tax_invoice_status`: 세금계산서 발행 상태 (unissued: 미발행, issued: 발행, cancelled: 취소)
- `tax_invoice_issued_at`: 세금계산서 발행 일시 (발행 상태로 변경 시 자동 기록)
- `tax_invoice_issued_by`: 세금계산서 발행 처리한 관리자 ID

---

### 2. 세금계산서 발행 내역 테이블 (tax_invoices) - 신규

```sql
CREATE TABLE IF NOT EXISTS `tax_invoices` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_number` VARCHAR(50) NOT NULL COMMENT '세금계산서 번호',
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID (입금 업체)',
    `period_start` DATE NOT NULL COMMENT '발행 기간 시작일',
    `period_end` DATE NOT NULL COMMENT '발행 기간 종료일',
    `total_supply_amount` DECIMAL(12,2) NOT NULL COMMENT '총 공급가액',
    `total_tax_amount` DECIMAL(12,2) NOT NULL COMMENT '총 부가세',
    `total_amount` DECIMAL(12,2) NOT NULL COMMENT '총 합계금액 (공급가액 + 부가세)',
    `deposit_request_ids` TEXT NOT NULL COMMENT '포함된 입금 신청 ID 목록 (JSON 배열)',
    `status` ENUM('issued', 'unissued', 'cancelled') NOT NULL DEFAULT 'unissued' COMMENT '상태 (발행, 미발행, 취소)',
    `issued_at` DATETIME NOT NULL COMMENT '발행 일시',
    `issued_by` VARCHAR(50) NOT NULL COMMENT '발행 처리한 관리자 ID',
    `cancelled_at` DATETIME DEFAULT NULL COMMENT '취소 일시',
    `cancelled_by` VARCHAR(50) DEFAULT NULL COMMENT '취소 처리한 관리자 ID',
    `memo` TEXT DEFAULT NULL COMMENT '메모',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_invoice_number` (`invoice_number`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_period` (`period_start`, `period_end`),
    KEY `idx_status` (`status`),
    KEY `idx_issued_at` (`issued_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='세금계산서 발행 내역';
```

---

## 🔄 시스템 플로우

### 1. 입금 신청 (판매자)

```
1. 판매자가 예치금 충전 신청
   └─> 입금자명, 입금금액 입력
       └─> 입금금액 = 공급가액 × 1.1 (부가세 10% 포함)
           └─> 공급가액 계산: supply_amount = amount / 1.1
           └─> 부가세 계산: tax_amount = supply_amount × 0.1
           └─> deposit_requests 테이블에 저장
               ├─> amount: 110,000원 (입금금액, 부가세 포함)
               ├─> supply_amount: 100,000원 (공급가액)
               ├─> tax_amount: 10,000원 (부가세)
               └─> status: 'pending'
```

---

### 2. 입금 확인 처리 (관리자)

```
1. 관리자가 입금 확인
   └─> /admin/deposit/requests.php
       ├─> 입금 신청 목록 조회 (상태: pending, unpaid 포함)
       └─> 입금 확인 버튼 클릭 (pending 또는 unpaid 상태에서 가능)
           ├─> seller_deposit_accounts.balance += amount (부가세 포함 금액)
           ├─> seller_deposit_ledger에 충전 내역 기록
           └─> deposit_requests 테이블 업데이트
               ├─> status = 'confirmed'
               ├─> confirmed_at = NOW()
               └─> admin_id = 현재 관리자 ID
```

**중요**: 
- `pending` 상태뿐만 아니라 `unpaid` 상태에서도 입금 확인 처리 가능
- 미입금으로 자동 분류된 건도 나중에 입금 확인 시 정상적으로 처리됨

---

### 3. 미입금 처리 (크론잡)

```
매일 자정 실행 (cron/check-unpaid-deposits.php)

1. 입금 신청 후 3일 이내 미확인 건 찾기
   └─> SELECT * FROM deposit_requests
       WHERE status = 'pending'
       AND created_at < DATE_SUB(NOW(), INTERVAL 3 DAY)

2. 상태를 'unpaid'로 변경
   └─> UPDATE deposit_requests
       SET status = 'unpaid'
       WHERE id = 미입금_건_ID
```

**참고**: 
- 미입금 상태로 분류된 건도 나중에 입금 확인 시 입금완료 처리 가능
- 입금 상태는 "입금"(confirmed), "미입금"(unpaid) 두 가지로만 처리 (거부 상태 없음)

---

### 4. 세금계산서 발행 상태 관리 (관리자)

```
1. 관리자가 세금계산서 발행 페이지 접속
   └─> /admin/tax-invoice/issue.php
       ├─> 기간 설정 (예: 2025-01-01 ~ 2025-01-31)
       └─> [조회] 버튼 클릭
           └─> 입금 확인된 건 목록 표시

2. 입금 건 목록 조회
   └─> SELECT 
           dr.*,
           ba.bank_name,
           ba.account_number
       FROM deposit_requests dr
       LEFT JOIN bank_accounts ba ON dr.bank_account_id = ba.id
       WHERE dr.status = 'confirmed'
       AND DATE(dr.confirmed_at) >= :period_start
       AND DATE(dr.confirmed_at) <= :period_end
       ORDER BY dr.confirmed_at DESC

3. 기간별 합계 계산
   └─> 입금 건수, 총 공급가액, 총 부가세, 총 합계금액 표시

4. 세금계산서 발행 상태 변경
   └─> 체크박스로 입금 건 선택
       ├─> 상태 선택: 미발행 / 발행 / 취소
       └─> [적용] 버튼 클릭
           └─> UPDATE deposit_requests
               SET tax_invoice_status = :status,
                   tax_invoice_issued_at = CASE WHEN :status = 'issued' THEN NOW() ELSE tax_invoice_issued_at END,
                   tax_invoice_issued_by = CASE WHEN :status = 'issued' THEN :admin_id ELSE tax_invoice_issued_by END
               WHERE id IN (:deposit_ids)
```

**참고:**
- 실제 세금계산서 발행은 외부에서 처리
- 사이트에서는 발행 상태만 관리 (발행, 미발행, 취소)
- 입금 건별로 개별 상태 관리 가능

---

## 📊 관리자 페이지 구조

### 1. 입금 신청 관리 페이지 (/admin/deposit/requests.php)

**필터링:**
- 전체
- 대기중 (pending)
- 입금 (confirmed)
- 미입금 (unpaid)

**표시 정보:**
- 신청일시
- 판매자 ID
- 입금자명
- 입금금액 (부가세 포함)
- 공급가액
- 부가세
- 상태 (대기중, 입금, 미입금)
- 확인일시 (확인된 경우)
- 세금계산서 발행 여부

**액션:**
- **입금 확인** (pending, unpaid 상태에서 가능)
- 세금계산서 발행 상태 확인

**중요**: 
- `unpaid` (미입금) 상태에서도 입금 확인 처리 가능
- 미입금으로 자동 분류된 건도 나중에 입금 확인 시 정상적으로 처리됨

---

### 2. 세금계산서 발행 페이지 (/admin/tax-invoice/issue.php)

**기능 목적:**
- 기간별 입금 금액을 확인하기 위한 페이지
- 실제 세금계산서 발행은 사이트에서 불가능하므로, 외부에서 발행 후 상태만 체크 처리
- 입금 건별로 세금계산서 발행 상태(발행/미발행/취소)를 관리

**기간 설정:**
- 시작일: [날짜 선택]
- 종료일: [날짜 선택]
- [이번달] 버튼 (이번 달 1일 ~ 오늘)
- [지난달] 버튼 (지난 달 1일 ~ 지난 달 마지막일)
- [조회] 버튼

**필터링:**
- **입금 상태**: [전체] [입금(confirmed)] [미입금(unpaid)]
- **세금계산서 발행 상태**: [전체] [발행(issued)] [미발행(unissued)] [취소(cancelled)]

**입금 건 목록 표시:**
- 입금 신청 ID
- 판매자 ID
- 입금자명
- 입금 금액 (부가세 포함)
- 공급가액
- 부가세
- 입금 상태 (입금/미입금)
- 입금 확인일시
- 세금계산서 발행 상태 (발행/미발행/취소) - 드롭다운으로 선택 가능
- [상태 변경] 버튼 (개별 건별 처리)

**액션:**
- 각 입금 건별로 세금계산서 발행 상태를 변경할 수 있음
- 상태 변경 시 `tax_invoice_status`, `tax_invoice_issued_at`, `tax_invoice_issued_by` 업데이트

---

### 3. 세금계산서 발행 내역 페이지 (/admin/tax-invoice/list.php)

**필터링:**
- 전체
- 발행
- 미발행
- 취소

**표시 정보:**
- 세금계산서 번호
- 판매자 ID
- 발행 기간
- 총 공급가액
- 총 부가세
- 총 합계금액
- 발행일시
- 발행 처리한 관리자

**액션:**
- 상세보기 (포함된 입금 내역 확인)
- 취소 (필요시)

---

## 💡 계산 방식

### 입금 신청 시

```php
// 사용자가 입력한 금액 = 입금금액 (부가세 포함)
$amount = 110000; // 입금금액

// 공급가액 계산 (부가세 제외)
$supply_amount = round($amount / 1.1, 2); // 100000

// 부가세 계산 (공급가액의 10%)
$tax_amount = round($supply_amount * 0.1, 2); // 10000

// 검증: 공급가액 + 부가세 = 입금금액
// 100000 + 10000 = 110000 ✓
```

### 광고 신청 시 입금금액 표시

```php
// 광고 금액 (공급가액)
$advertisement_price = 100000; // 공급가액

// 입금금액 계산 (부가세 포함)
$deposit_amount = round($advertisement_price * 1.1, 2); // 110000

// 표시: "입금금액: 110,000원 (부가세 포함)"
```

---

## 📝 핵심 정리

1. **입금금액 = 광고금액 × 1.1** (부가세 10% 포함)
2. **입금 신청 후 3일 이내 미확인 → 미입금(unpaid) 자동 분류**
3. **미입금 상태에서도 입금 확인 처리 가능**: 나중에 입금이 확인되면 입금완료 처리 가능
4. **입금 확인 처리 → 예치금 충전**
5. **세금계산서 발행**: 기간 설정 → 판매자별 합산 → 발행 완료 처리
6. **세금계산서 발행 완료 처리**: 해당 기간의 입금 내역이 "발행완료" 상태로 변경

---

위 설계를 기반으로 구현하면 됩니다.
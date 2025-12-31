# 로테이션 광고 시스템 최종 설계서

## 📋 개요

메뉴별(통신사단독유심, 알뜰폰, 통신사폰, 인터넷) 상품 목록 상단에 광고 섹션을 생성하고, 관리자가 설정한 시간 단위로 광고를 로테이션시키는 시스템입니다.

**수익 모델**: 시간 단위 고정 요금제 (Time-Based Fixed Pricing)

---

## ✅ 최종 확정 요구사항

### 1. 수익 모델
- **시간 단위 고정 요금제**: 로테이션 시간 단위(10초, 30초, 1분, 5분 등)로 고정 요금 청구
- **기간별 금액 설정**: 관리자가 1일, 2일, 3일, 5일, 7일, 10일 등 **일자별로 금액 설정 가능**
- **카테고리별 설정**: 각 카테고리(통신사단독유심, 알뜰폰, 통신사폰, 인터넷)마다 별도 금액 설정

### 2. 광고 신청 프로세스
- **검수 없이 바로 시작**: 판매자가 광고 신청 시 관리자 검수 없이 즉시 광고 시작
- **예치금 차감**: 광고 신청 시 예치금에서 자동 차감
- **예치금 부족 시**: 광고 신청 불가
- **상품별 광고 제한**: 같은 상품에 대해 동시에 여러 개의 광고를 진행할 수 없음 (광고 종료 후 재신청 가능)

### 3. 예치금 시스템
- **무통장 입금만 가능**: 신용카드 등 다른 결제 수단 없음
- **무통장 입금 신청 프로세스**:
  1. 판매자가 입금자명, 입금금액 입력
  2. 무통장 입금 계좌 선택 (관리자가 등록한 계좌 중 선택)
  3. 입금 신청 저장 (대기 상태)
  4. 판매자가 직접 무통장 입금
  5. 관리자가 입금 확인 후 예치금 충전

### 4. 무통장 계좌 관리
- **관리자 페이지에서 관리**: 은행명, 계좌번호, 예금주 정보 등록/수정/삭제
- **판매자가 선택 가능**: 광고 신청 시 등록된 계좌 목록에서 선택

### 5. 광고 상태 관리

#### 광고 상태 표시 (판매자 관리 페이지)
- **광고신청**: 새로운 광고를 신청할 수 있는 버튼 (상품에 광고가 없거나, 모든 광고가 종료된 경우)
- **광고중**: 광고가 진행 중일 때 표시 (광고 상태: `active` + 상품 상태: `active` + 광고 기간 남음)
- **광고중지**: 광고 기간이 남아있으나 상품이 판매종료된 경우 (광고 상태: `active` + 상품 상태: `inactive`/`deleted` + 광고 기간 남음)
- **광고종료**: 광고 기간이 만료된 경우 (광고 상태: `expired`) → 다시 광고신청 버튼 표시

#### 광고 진행 규칙
- **광고는 광고 기간이 끝날 때까지 계속 진행됨** (광고 종료 시간은 연장되지 않음)
- **상품이 판매종료되면 해당 상품은 광고에서 노출되지 않음**
- **판매종료된 상품도 광고 기간이 남아있으면, 상품을 다시 활성화하면 광고가 다시 노출됨**
- **광고는 연장되지 않으며 다시 신청해야 함**
- **같은 상품에 대해 동시에 여러 개의 광고를 진행할 수 없음 (광고 종료 후 재신청 가능)**
- **다수의 다른 상품들에 대해 각각 광고를 진행할 수 있음 (상품별로 독립적)**

#### 광고 시간 계산
- 광고 신청 시점의 정확한 시간부터 시작 (초 단위)
- 예: 2025-12-21 15:16:15에 1일 광고 신청
- 종료 시간: 2025-12-22 15:16:15 (정확히 86400초 후)

---

## 🗄️ 데이터베이스 설계

### 1. 광고 가격 설정 테이블 (rotation_advertisement_prices)

카테고리별, 시간 단위별, 기간별 가격을 설정하는 테이블입니다.

```sql
CREATE TABLE IF NOT EXISTS `rotation_advertisement_prices` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_type` ENUM('mvno', 'mno', 'internet', 'mno_sim') NOT NULL COMMENT '상품 타입',
    `rotation_duration` INT(11) NOT NULL COMMENT '로테이션 시간(초): 10, 30, 60, 300',
    `advertisement_days` INT(11) NOT NULL COMMENT '광고 기간(일): 1, 2, 3, 5, 7, 10, 14, 30 등',
    `price` DECIMAL(12,2) NOT NULL COMMENT '광고 금액',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_type_duration_days` (`product_type`, `rotation_duration`, `advertisement_days`),
    KEY `idx_product_type` (`product_type`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='로테이션 광고 가격 설정 (카테고리별, 시간 단위별, 기간별)';
```

---

### 2. 무통장 입금 계좌 테이블 (bank_accounts)

관리자가 등록한 무통장 입금 계좌 정보를 저장하는 테이블입니다.

```sql
CREATE TABLE IF NOT EXISTS `bank_accounts` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `bank_name` VARCHAR(50) NOT NULL COMMENT '은행명 (예: 국민은행, 신한은행)',
    `account_number` VARCHAR(50) NOT NULL COMMENT '계좌번호',
    `account_holder` VARCHAR(100) NOT NULL COMMENT '예금주',
    `display_order` INT(11) NOT NULL DEFAULT 0 COMMENT '표시 순서',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
    `memo` TEXT DEFAULT NULL COMMENT '메모 (관리자용)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_is_active` (`is_active`),
    KEY `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='무통장 입금 계좌 관리';
```

---

### 3. 예치금 계좌 테이블 (seller_deposit_accounts)

판매자의 예치금 잔액을 관리하는 테이블입니다.

```sql
CREATE TABLE IF NOT EXISTS `seller_deposit_accounts` (
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
    `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '예치금 잔액',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='판매자 예치금 계좌';
```

---

### 4. 예치금 내역 테이블 (seller_deposit_ledger)

예치금 충전/차감 내역을 기록하는 테이블입니다.

```sql
CREATE TABLE IF NOT EXISTS `seller_deposit_ledger` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
    `transaction_type` ENUM('deposit', 'withdraw', 'refund') NOT NULL COMMENT '거래 유형 (충전, 차감, 환불)',
    `amount` DECIMAL(12,2) NOT NULL COMMENT '금액 (충전: +, 차감: -, 환불: +)',
    `balance_before` DECIMAL(12,2) NOT NULL COMMENT '거래 전 잔액',
    `balance_after` DECIMAL(12,2) NOT NULL COMMENT '거래 후 잔액',
    `deposit_request_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '예치금 충전 신청 ID (deposit_requests.id)',
    `advertisement_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '광고 ID (rotation_advertisements.id, 차감 시)',
    `description` VARCHAR(500) DEFAULT NULL COMMENT '설명',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_transaction_type` (`transaction_type`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_deposit_request_id` (`deposit_request_id`),
    KEY `idx_advertisement_id` (`advertisement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='판매자 예치금 내역';
```

---

### 5. 예치금 충전 신청 테이블 (deposit_requests)

판매자가 무통장 입금을 신청한 정보를 저장하는 테이블입니다.
**참고**: 세금계산서 발행 관련 컬럼은 `TAX_INVOICE_SYSTEM_DESIGN.md` 참고

```sql
CREATE TABLE IF NOT EXISTS `deposit_requests` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
    `bank_account_id` INT(11) UNSIGNED NOT NULL COMMENT '입금할 계좌 ID (bank_accounts.id)',
    `depositor_name` VARCHAR(100) NOT NULL COMMENT '입금자명',
    `amount` DECIMAL(12,2) NOT NULL COMMENT '입금 금액 (부가세 포함)',
    `supply_amount` DECIMAL(12,2) NOT NULL COMMENT '공급가액 (부가세 제외)',
    `tax_amount` DECIMAL(12,2) NOT NULL COMMENT '부가세 (공급가액의 10%)',
    `status` ENUM('pending', 'confirmed', 'unpaid') NOT NULL DEFAULT 'pending' COMMENT '상태 (대기중, 입금, 미입금)',
    `admin_id` VARCHAR(50) DEFAULT NULL COMMENT '처리한 관리자 ID',
    `confirmed_at` DATETIME DEFAULT NULL COMMENT '확인 일시',
    `rejected_reason` TEXT DEFAULT NULL COMMENT '거부 사유',
    `tax_invoice_issued` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '세금계산서 발행 여부 (0: 미발행, 1: 발행완료)',
    `tax_invoice_period_start` DATE DEFAULT NULL COMMENT '세금계산서 발행 기간 시작일',
    `tax_invoice_period_end` DATE DEFAULT NULL COMMENT '세금계산서 발행 기간 종료일',
    `tax_invoice_issued_at` DATETIME DEFAULT NULL COMMENT '세금계산서 발행 일시',
    `tax_invoice_issued_by` VARCHAR(50) DEFAULT NULL COMMENT '세금계산서 발행 처리한 관리자 ID',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_bank_account_id` (`bank_account_id`),
    KEY `idx_status` (`status`),
    KEY `idx_tax_invoice_issued` (`tax_invoice_issued`),
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
- `status`: 상태는 'pending' (대기중), 'confirmed' (입금), 'unpaid' (미입금) 세 가지만 사용 (거부됨 상태 제거)
- `tax_invoice_issued`: 세금계산서 발행 여부
- 세금계산서 관련 컬럼들 추가

---

### 6. 로테이션 광고 테이블 (rotation_advertisements)

광고 신청 정보를 저장하는 테이블입니다.

```sql
CREATE TABLE IF NOT EXISTS `rotation_advertisements` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
    `product_type` ENUM('mvno', 'mno', 'internet', 'mno_sim') NOT NULL COMMENT '상품 타입',
    `rotation_duration` INT(11) NOT NULL COMMENT '로테이션 시간(초): 10, 30, 60, 300',
    `advertisement_days` INT(11) NOT NULL COMMENT '광고 기간(일): 1, 2, 3, 5, 7, 10 등',
    `price` DECIMAL(12,2) NOT NULL COMMENT '광고 금액 (신청 시점 가격)',
    `start_datetime` DATETIME NOT NULL COMMENT '광고 시작 시간 (초 단위)',
    `end_datetime` DATETIME NOT NULL COMMENT '광고 종료 시간 (초 단위)',
    `status` ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active' COMMENT '광고 상태',
    `display_order` INT(11) NOT NULL DEFAULT 0 COMMENT '로테이션 순서',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_product_type` (`product_type`),
    KEY `idx_status` (`status`),
    KEY `idx_start_end_datetime` (`start_datetime`, `end_datetime`),
    KEY `idx_display_order` (`display_order`),
    CONSTRAINT `fk_rotation_ad_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='로테이션 광고 신청';
```

**광고 상태 설명:**
- `active`: 광고 진행중 (데이터베이스 상태)
- `expired`: 종료됨 (광고 기간 만료)
- `cancelled`: 취소됨 (판매자가 취소)

**광고 표시 상태 (판매자 관리 페이지용):**
- **광고중**: `status = 'active'` AND `products.status = 'active'` AND `end_datetime > NOW()`
- **광고중지**: `status = 'active'` AND `products.status != 'active'` AND `end_datetime > NOW()`
- **광고종료**: `status = 'expired'`

**시간 계산 방식:**
- 광고 신청 시점의 정확한 시간(`start_datetime`)부터 시작
- 광고 기간(`advertisement_days`)을 초 단위로 계산 (1일 = 86400초)
- 종료 시간(`end_datetime`) = 시작 시간 + (광고 기간 × 86400초)
- 예: 2025-12-21 15:16:15에 1일 광고 신청 → 종료: 2025-12-22 15:16:15

---

## 🔄 시스템 플로우

### 1. 광고 가격 설정 (관리자)

```
1. 관리자 페이지 접속
   └─> /admin/advertisement/prices.php
       ├─> 카테고리 선택 (mvno, mno, internet, mno_sim)
       ├─> 시간 단위 선택 (10초, 30초, 1분, 5분 등)
       └─> 기간별 금액 입력
           ├─> 1일: [금액 입력]
           ├─> 2일: [금액 입력]
           ├─> 3일: [금액 입력]
           ├─> 5일: [금액 입력]
           ├─> 7일: [금액 입력]
           ├─> 10일: [금액 입력]
           └─> [저장] 버튼 클릭
               └─> rotation_advertisement_prices 테이블에 저장
```

---

### 2. 무통장 계좌 등록 (관리자)

```
1. 관리자 페이지 접속
   └─> /admin/advertisement/bank-accounts.php
       ├─> [계좌 등록] 버튼 클릭
       ├─> 은행명 입력 (예: 국민은행)
       ├─> 계좌번호 입력
       ├─> 예금주 입력
       ├─> 표시 순서 설정 (선택)
       └─> [저장] 버튼 클릭
           └─> bank_accounts 테이블에 저장
```

---

### 3. 예치금 충전 프로세스 (판매자)

```
1. 판매자 페이지 접속
   └─> /seller/deposit/charge.php
       ├─> 입금자명 입력
       ├─> 입금 금액 입력
       ├─> 무통장 계좌 선택 (관리자가 등록한 계좌 목록에서)
       └─> [입금 신청] 버튼 클릭
           └─> deposit_requests 테이블에 저장 (status = 'pending')
               └─> 판매자가 실제 무통장 입금
                   └─> 관리자가 입금 확인
                       └─> /admin/deposit/requests.php
                           ├─> 입금 확인 버튼 클릭
                           ├─> seller_deposit_accounts.balance 증가
                           └─> seller_deposit_ledger에 충전 내역 기록
```

---

### 4. 광고 신청 프로세스 (판매자)

```
1. 판매자 페이지 접속
   └─> /seller/advertisement/register.php
       ├─> 광고할 상품 선택 (자신이 등록한 상품 중)
       ├─> 카테고리 자동 선택 (상품 타입에 따라)
       ├─> 로테이션 시간 단위 선택 (10초, 30초, 1분, 5분 등)
       ├─> 광고 기간 선택 (1일, 2일, 3일, 5일, 7일, 10일 등)
       ├─> 예상 금액 표시 (실시간 계산)
       └─> [광고 신청] 버튼 클릭
           ├─> 같은 상품의 활성화된 광고 중복 체크
           │   └─> SELECT * FROM rotation_advertisements
           │       WHERE product_id = :product_id
           │       AND status = 'active'
           │       AND end_datetime > NOW()
           ├─> 활성화된 광고가 있으면: 에러 메시지 표시 ("이미 광고 중인 상품입니다")
           ├─> 예치금 잔액 확인
           ├─> 잔액 부족 시: 에러 메시지 표시
           └─> 잔액 충분 시:
               ├─> 현재 시간을 시작 시간으로 설정 (초 단위)
               │   └─> start_datetime = NOW() (예: 2025-12-21 15:16:15)
               ├─> 종료 시간 계산 (초 단위)
               │   └─> end_datetime = start_datetime + (advertisement_days × 86400초)
               │       (예: 2025-12-22 15:16:15)
               ├─> rotation_advertisements 테이블에 저장
               │   ├─> status = 'active'
               │   ├─> start_datetime = 현재 시간
               │   └─> end_datetime = 시작 시간 + 기간(초)
               ├─> seller_deposit_accounts.balance 차감
               └─> seller_deposit_ledger에 차감 내역 기록
                   └─> 광고 즉시 시작 (검수 없음)
```

---

### 5. 광고 상태 표시 로직 (판매자 관리 페이지)

```
상품별 광고 목록 조회 시:

1. 광고 상태 계산
   └─> 광고 상태 = rotation_advertisements.status
   └─> 상품 상태 = products.status
   └─> 현재 시간과 end_datetime 비교

2. 표시 상태 결정
   └─> IF status = 'active' AND products.status = 'active' AND end_datetime > NOW()
       └─> "광고중" 표시
   
   └─> IF status = 'active' AND products.status != 'active' AND end_datetime > NOW()
       └─> "광고중지" 표시
   
   └─> IF status = 'expired'
       └─> "광고종료" 표시 (다시 광고신청 버튼 표시)

3. 광고신청 버튼 표시
   └─> 상품에 광고가 없거나, 모든 광고가 'expired' 또는 'cancelled'인 경우
       └─> "광고신청" 버튼 표시
```

---

### 6. 광고 목록 조회 (프론트엔드 - 사용자 화면)

```
각 카테고리 페이지에서 광고 목록 조회:

SELECT 
    ra.*,
    p.status AS product_status
FROM rotation_advertisements ra
INNER JOIN products p ON ra.product_id = p.id
WHERE ra.product_type = :type 
AND ra.status = 'active'  -- 광고 상태가 active
AND ra.start_datetime <= NOW()  -- 광고 시작 시간이 지났음
AND ra.end_datetime > NOW()  -- 광고 종료 시간이 지나지 않았음
AND p.status = 'active'  -- 상품이 판매중인 것만 노출
ORDER BY ra.display_order, ra.created_at ASC
```

**중요:**
- 광고는 `end_datetime`까지 계속 진행됨 (광고 상태는 `active` 유지)
- 하지만 상품이 판매종료(`inactive`/`deleted`)되면 광고 목록에서 제외되어 노출되지 않음
- 판매종료된 상품도 광고 기간이 남아있으면, 상품을 다시 활성화(`active`)하면 광고 목록에 다시 노출됨

---

### 7. 광고 만료 자동 처리 (크론잡)

```
매일(또는 더 자주, 예: 1시간마다) 실행 (cron/expire-advertisements.php)

1. 만료된 광고 찾기 (초 단위로 정확히 체크)
   └─> SELECT * FROM rotation_advertisements
       WHERE status = 'active'
       AND end_datetime < NOW()

2. 광고 상태를 'expired'로 변경
   └─> UPDATE rotation_advertisements
       SET status = 'expired'
       WHERE id = 만료된_광고_ID
```

---

## 📱 판매자 관리 페이지 구조

### 상품 목록 페이지 (/seller/products/list.php)

각 상품별로 광고 상태를 표시합니다.

**버튼 표시 규칙:**

1. **광고신청 버튼**
   - 조건: 상품에 광고가 없거나, 모든 광고가 `expired` 또는 `cancelled` 상태
   - 동작: 광고 신청 페이지로 이동

2. **광고중 버튼**
   - 조건: `status = 'active'` AND `products.status = 'active'` AND `end_datetime > NOW()`
   - 스타일: 초록색 또는 활성화 스타일
   - 동작: 광고 상세 정보 표시 (클릭 시)

3. **광고중지 버튼**
   - 조건: `status = 'active'` AND `products.status != 'active'` AND `end_datetime > NOW()`
   - 스타일: 노란색 또는 경고 스타일
   - 동작: 광고 상세 정보 표시 + 상품 활성화 안내

4. **광고종료 버튼**
   - 조건: `status = 'expired'`
   - 스타일: 회색 또는 비활성화 스타일
   - 동작: 광고 상세 정보 표시 (다시 광고신청 가능 안내)

---

### 광고 내역 페이지 (/seller/advertisement/list.php)

판매자가 진행한 모든 광고 내역을 조회합니다.

**필터링 옵션:**
- 전체
- 광고중
- 광고중지
- 광고종료

**표시 정보:**
- 상품명
- 광고 상태 (광고중/광고중지/광고종료)
- 로테이션 시간 단위
- 광고 기간 (시작일 ~ 종료일)
- 광고 금액
- 광고 신청일

---

## 🔍 용어 정리

### 광고 상태 관련 용어

| 용어 | 설명 | 데이터베이스 상태 | 표시 조건 |
|-----|------|-----------------|----------|
| **광고신청** | 새로운 광고를 신청할 수 있는 상태 | - | 상품에 광고가 없거나 모든 광고가 종료/취소된 경우 |
| **광고중** | 광고가 정상적으로 진행 중인 상태 | `status = 'active'` | `products.status = 'active'` AND `end_datetime > NOW()` |
| **광고중지** | 광고 기간은 남아있으나 상품이 판매종료되어 노출되지 않는 상태 | `status = 'active'` | `products.status != 'active'` AND `end_datetime > NOW()` |
| **광고종료** | 광고 기간이 만료되어 종료된 상태 | `status = 'expired'` | `end_datetime < NOW()` |
| **광고취소** | 판매자가 광고를 취소한 상태 | `status = 'cancelled'` | 판매자가 취소한 경우 |

### 기타 용어

| 용어 | 설명 |
|-----|------|
| **로테이션 시간** | 광고가 순환되는 시간 간격 (10초, 30초, 1분, 5분 등) |
| **광고 기간** | 광고가 진행되는 총 기간 (1일, 2일, 3일, 5일, 7일, 10일 등) |
| **예치금** | 판매자가 광고 신청에 사용할 수 있는 미리 충전한 금액 |
| **무통장 입금** | 예치금 충전을 위한 결제 수단 (무통장 입금만 가능) |

---

## 🎯 핵심 요약

1. **수익 모델**: 시간 단위 고정 요금제, 기간별 직접 금액 설정
2. **검수 없음**: 광고 신청 즉시 시작
3. **예치금 시스템**: 무통장 입금만 가능
4. **광고 시간 계산**: 초 단위로 정확히 계산 (start_datetime, end_datetime)
5. **상품별 광고 제한**: 같은 상품에 대해 동시에 여러 개의 광고를 진행할 수 없음 (광고 종료 후 재신청 가능)
6. **광고 상태 표시**: 광고신청, 광고중, 광고중지, 광고종료
7. **광고 연장 불가**: 광고는 연장되지 않으며 다시 신청해야 함
8. **상품 상태 연동**: 상품이 판매종료되면 광고는 노출되지 않지만, 광고는 계속 진행됨

---

위 설계를 기반으로 단계별 구현을 진행하시면 됩니다.
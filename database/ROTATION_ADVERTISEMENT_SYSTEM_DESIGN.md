# 로테이션 광고 시스템 상세 설계서

## 📋 개요

메뉴별(통신사단독유심, 알뜰폰, 통신사폰, 인터넷) 상품 목록 상단에 광고 섹션을 생성하고, 관리자가 설정한 시간 단위로 광고를 로테이션시키는 시스템입니다.

**수익 모델**: 시간 단위 고정 요금제 (Time-Based Fixed Pricing)

---

## ✅ 확정된 요구사항

### 1. 수익 모델
- **시간 단위 고정 요금제**: 로테이션 시간 단위(10초, 30초, 1분, 5분 등)로 고정 요금 청구
- **기간별 금액 설정**: 관리자가 1일, 2일, 3일, 5일, 7일, 10일 등 **일자별로 금액 설정 가능**
- **카테고리별 설정**: 각 카테고리(통신사단독유심, 알뜰폰, 통신사폰, 인터넷)마다 별도 금액 설정

### 2. 광고 신청 프로세스
- **검수 없이 바로 시작**: 판매자가 광고 신청 시 관리자 검수 없이 자동으로 광고 시작
- **예치금 차감**: 광고 신청 시 예치금에서 금액 자동 차감
- **예치금 부족 시**: 광고 신청 불가

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
- **광고 진행**: 광고는 광고 기간이 끝날 때까지 계속 진행됨 (광고 종료 시간은 연장되지 않음)
- **상품 노출 규칙**:
  - 상품이 판매종료(`inactive`/`deleted`)되면 해당 상품은 광고에서 노출되지 않음
  - 판매종료된 상품도 광고 기간이 남아있으면, 상품을 다시 활성화(`active`)하면 광고가 다시 노출됨
- **광고 시간 계산**: 광고 신청 시작 시간부터 정확히 초 단위로 계산
  - 예: 2025-12-21 15:16:15에 1일 광고 신청
  - 종료 시간: 2025-12-22 15:16:15 (정확히 86400초 후)
- **광고 기간 종료**: 설정된 광고 종료 시간 도달 시 광고 자동 종료
- **광고 상태 값**:
  - `active`: 광고 진행중
  - `expired`: 종료됨 (기간 만료)
  - `cancelled`: 취소됨

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

**설명:**
- `product_type`: 카테고리 (mvno, mno, internet, mno_sim)
- `rotation_duration`: 로테이션 시간 단위 (초 단위: 10, 30, 60, 300 등)
- `advertisement_days`: 광고 기간 (일 단위: 1, 2, 3, 5, 7, 10, 14, 30 등)
- `price`: 해당 조합의 광고 금액
- **예시**: (mvno, 30초, 7일) = 50,000원

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

```sql
CREATE TABLE IF NOT EXISTS `deposit_requests` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
    `bank_account_id` INT(11) UNSIGNED NOT NULL COMMENT '입금할 계좌 ID (bank_accounts.id)',
    `depositor_name` VARCHAR(100) NOT NULL COMMENT '입금자명',
    `amount` DECIMAL(12,2) NOT NULL COMMENT '입금 금액',
    `status` ENUM('pending', 'confirmed', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending' COMMENT '상태 (대기중, 확인됨, 거부됨, 취소됨)',
    `admin_id` VARCHAR(50) DEFAULT NULL COMMENT '처리한 관리자 ID',
    `confirmed_at` DATETIME DEFAULT NULL COMMENT '확인 일시',
    `rejected_reason` TEXT DEFAULT NULL COMMENT '거부 사유',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_bank_account_id` (`bank_account_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_deposit_request_bank_account` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='예치금 충전 신청 (무통장 입금)';
```

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
    KEY `idx_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='로테이션 광고 신청';
```

**광고 상태 설명:**
- `active`: 광고 진행중 (정상 노출)
- `expired`: 종료됨 (광고 기간 만료)
- `cancelled`: 취소됨 (판매자가 취소)

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

### 5. 상품 상태 변경 시 광고 처리

```
상품 상태 변경 시 별도 처리 불필요 (광고 목록 조회 시 자동으로 반영됨)

- 광고는 광고 기간(end_datetime)이 종료될 때까지 계속 진행됨 (광고 상태는 active 유지)
- 상품이 판매종료(inactive/deleted)되면:
  - 광고는 계속 진행됨 (광고 종료 시간은 변경/연장되지 않음)
  - 하지만 상품이 inactive/deleted이므로 광고 목록에서 노출되지 않음
- 상품이 다시 활성화(active)되면:
  - 광고 기간이 남아있으면 (end_datetime > NOW()) 광고 목록에 다시 노출됨
  - 광고 종료 시간은 변경/연장되지 않음
- 광고 목록 조회 시: products.status = 'active' AND end_datetime > NOW()인 상품만 노출
```

---

### 6. 광고 기간 만료 자동 처리 (크론잡)

```
매일(또는 더 자주) 실행 (cron/expire-advertisements.php)

1. 만료된 광고 찾기 (초 단위로 정확히 체크)
   └─> SELECT * FROM rotation_advertisements
       WHERE status = 'active'
       AND end_datetime < NOW()

2. 광고 상태를 'expired'로 변경
   └─> UPDATE rotation_advertisements
       SET status = 'expired'
       WHERE id = 만료된_광고_ID

참고: 정확한 시간 계산을 위해 1시간마다 또는 더 자주 실행 권장
```

---

## ⚠️ 문제점 및 보강 사항

### 1. 예치금 시스템 구현 필요 ⚠️

**현재 상태:**
- 포인트 시스템은 있음 (`user_point_accounts`, `user_point_ledger`)
- 예치금 시스템은 없음 (새로 구현 필요)

**보강 사항:**
- ✅ `seller_deposit_accounts` 테이블 생성
- ✅ `seller_deposit_ledger` 테이블 생성
- ✅ 예치금 충전/차감 함수 구현
- ✅ 예치금 잔액 조회 API 구현

---

### 2. 무통장 계좌 관리 시스템 구현 필요 ⚠️

**현재 상태:**
- 무통장 계좌 관리 시스템 없음

**보강 사항:**
- ✅ `bank_accounts` 테이블 생성
- ✅ 관리자 페이지: 계좌 등록/수정/삭제 기능
- ✅ 판매자 페이지: 계좌 목록 표시 및 선택 기능

---

### 3. 무통장 입금 신청 시스템 구현 필요 ⚠️

**현재 상태:**
- 무통장 입금 신청 프로세스 없음

**보강 사항:**
- ✅ `deposit_requests` 테이블 생성
- ✅ 판매자 페이지: 입금 신청 폼 및 신청 목록
- ✅ 관리자 페이지: 입금 신청 목록 및 확인/거부 기능
- ✅ 입금 확인 시 예치금 자동 충전 로직

---

### 4. 광고 가격 설정 시스템 개선 필요 ⚠️

**현재 제안서:**
- 일일 요금 × 기간 방식 (할인율 적용)

**변경 필요:**
- ✅ 기간별 직접 금액 설정 방식으로 변경
- ✅ `rotation_advertisement_prices` 테이블 구조 변경
  - `daily_price` 제거
  - `advertisement_days` 추가 (1, 2, 3, 5, 7, 10일 등)
  - `price` 컬럼에 해당 기간의 총 금액 저장

---

### 5. 광고 시간 계산 로직 구현 필요 ⚠️

**현재 상태:**
- 광고 시작/종료 시간을 초 단위로 정확히 계산하는 로직 없음

**보강 사항:**
- ✅ 광고 신청 시 현재 시간을 `start_datetime`에 저장 (초 단위)
- ✅ 종료 시간 계산: `end_datetime = start_datetime + (advertisement_days × 86400초)`
- ✅ 광고 만료 체크 시 `end_datetime < NOW()` 비교 (초 단위)

---

### 6. 광고 신청 시 검수 프로세스 제거 필요 ⚠️

**현재 제안서:**
- 기존 제안서에는 관리자 승인 프로세스 포함

**변경 필요:**
- ✅ 검수 없이 바로 시작하도록 변경
- ✅ 광고 신청 즉시 `status = 'active'`로 설정
- ✅ 예치금 차감 후 즉시 광고 시작

---

### 7. 광고 만료 크론잡 구현 필요 ⚠️

**현재 상태:**
- 광고 만료 자동 처리 스크립트 없음

**보강 사항:**
- ✅ `cron/expire-advertisements.php` 스크립트 생성
- ✅ Windows 작업 스케줄러 또는 Linux cron 설정
- ✅ 매일 자정 실행하여 만료된 광고 자동 처리

---

### 8. 광고 로테이션 프론트엔드 구현 필요 ⚠️

**현재 상태:**
- 광고 섹션 및 로테이션 로직 없음

**보강 사항:**
- ✅ 각 카테고리 페이지 상단에 광고 섹션 추가
- ✅ JavaScript로 광고 로테이션 구현
- ✅ API로 활성 광고 목록 조회

---

## 📋 구현 체크리스트

### Phase 1: 데이터베이스 설정
- [ ] `rotation_advertisement_prices` 테이블 생성
- [ ] `bank_accounts` 테이블 생성
- [ ] `seller_deposit_accounts` 테이블 생성
- [ ] `seller_deposit_ledger` 테이블 생성
- [ ] `deposit_requests` 테이블 생성
- [ ] `rotation_advertisements` 테이블 생성

### Phase 2: 관리자 기능
- [ ] 광고 가격 설정 페이지 (`/admin/advertisement/prices.php`)
- [ ] 무통장 계좌 관리 페이지 (`/admin/advertisement/bank-accounts.php`)
- [ ] 예치금 충전 신청 관리 페이지 (`/admin/deposit/requests.php`)
- [ ] 광고 목록 관리 페이지 (`/admin/advertisement/list.php`)

### Phase 3: 판매자 기능
- [ ] 예치금 충전 신청 페이지 (`/seller/deposit/charge.php`)
- [ ] 예치금 잔액 조회 페이지 (`/seller/deposit/balance.php`)
- [ ] 예치금 내역 조회 페이지 (`/seller/deposit/history.php`)
- [ ] 광고 신청 페이지 (`/seller/advertisement/register.php`)
- [ ] 광고 목록 조회 페이지 (`/seller/advertisement/list.php`)

### Phase 4: API 및 백엔드 로직
- [ ] 예치금 충전/차감 함수 구현
- [ ] 광고 신청 API (`/api/advertisement/register.php`)
- [ ] 광고 목록 조회 API (`/api/advertisement/list.php`)
- [ ] 상품 상태 변경 감지 로직 (트리거 또는 애플리케이션 레벨)
- [ ] 광고 만료 크론잡 (`/cron/expire-advertisements.php`)

### Phase 5: 프론트엔드
- [ ] 각 카테고리 페이지에 광고 섹션 추가
- [ ] 광고 로테이션 JavaScript 구현
- [ ] 광고 카드 컴포넌트 생성

---

## 💡 추가 고려사항

### 1. 동시에 여러 광고 신청
- 같은 상품에 대해 여러 광고 신청 가능 여부 결정 필요
- 권장: 같은 상품에 대해 동시에 여러 광고 신청 불가 (중복 방지)

### 2. 광고 환불 정책
- 광고 기간 중 취소 시 환불 정책 결정 필요
- 권장: 환불 불가 또는 부분 환불 (관리자 재량)

### 3. 광고 통계
- 광고 노출 횟수, 클릭 횟수 등 통계 수집 여부 결정
- 초기에는 단순하게 시작, 향후 확장 가능

### 4. 광고 순서
- 같은 시간 단위에 여러 광고가 있을 경우 순서 결정
- 권장: 광고 신청일 순서 또는 `display_order` 설정

---

## 📝 다음 단계

1. **데이터베이스 스키마 SQL 파일 생성**
2. **관리자 페이지 구현** (가격 설정, 계좌 관리, 입금 확인)
3. **판매자 페이지 구현** (예치금 충전, 광고 신청)
4. **API 및 백엔드 로직 구현**
5. **프론트엔드 광고 섹션 및 로테이션 구현**
6. **테스트 및 검증**

---

## 🎯 핵심 요약

1. **수익 모델**: 시간 단위 고정 요금제, 기간별 직접 금액 설정
2. **검수 없음**: 광고 신청 즉시 시작
3. **예치금 시스템**: 무통장 입금만 가능
4. **광고 시간 계산**: 초 단위로 정확히 계산 (start_datetime, end_datetime)
5. **상품 상태와 무관**: 상품 판매종료되어도 광고 계속 진행
6. **기간 만료**: end_datetime 도달 시 자동 종료 (크론잡)

위 설계를 기반으로 단계별 구현을 진행하시면 됩니다.
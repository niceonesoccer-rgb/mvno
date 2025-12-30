# 입찰 시스템 전체 종합 가이드

## 📋 목차
1. [시스템 개요](#시스템-개요)
2. [핵심 정책](#핵심-정책)
3. [데이터베이스 구조](#데이터베이스-구조)
4. [입찰 프로세스](#입찰-프로세스)
5. [운용 방식](#운용-방식)
6. [연속 입찰 라운드](#연속-입찰-라운드)
7. [게시물 표시 로직](#게시물-표시-로직)
8. [예치금 시스템](#예치금-시스템)
9. [관리자 기능](#관리자-기능)
10. [판매자 안내](#판매자-안내)

---

## 시스템 개요

### 목적
- 통신사폰(MNO), 알뜰폰(MVNO), 통신사유심(MNO-SIM) 세 가지 카테고리별 입찰 시스템
- 각 게시판 상단 20개 영역을 스폰서 영역으로 운영
- 입찰 낙찰자를 입찰금액 순으로 상단 노출

### 주요 특징
- 카테고리별 독립 입찰 진행
- 예치금 기반 입찰 시스템
- 판매자당 카테고리별 1개 입찰만 가능
- 연속 입찰 라운드 지원 (기간 자유롭게 설정)
- 두 가지 운용 방식 (고정 모드 / 로테이션 모드)
- 자동 상태 전환 시스템

---

## 핵심 정책

### 1. 입찰 정책
- ✅ **판매자당 카테고리별 1개 입찰만 가능**
- ✅ **입찰 수정 불가** (취소 후 재입찰만 가능)
- ✅ **입찰 취소 가능** (입찰 확정 전까지, 예치금 즉시 환불)
- ✅ **중복 입찰 방지** (동일 라운드에 중복 입찰 불가)

### 2. 낙찰 정책
- ✅ 모든 입찰을 하나의 풀에서 비교
- ✅ 입찰 금액 내림차순 정렬
- ✅ 동일 금액 시 입찰 시간(bid_at) 빠른 순
- ✅ 상위 20개만 낙찰 (실제 낙찰 건수가 20개 미만이면 그만큼만)
- ✅ 동점 21개 이상 시 마지막 입찰 미낙찰

### 3. 예치금 정책
- ✅ 입찰 참여 시 예치금 차감
- ✅ 미낙찰 시 예치금 자동 환불
- ✅ 입찰 취소 시 예치금 즉시 환불
- ✅ 관리자가 예치금 충전/환불 관리

### 4. 기간 정책
- ✅ 입찰 기간과 게시 기간을 자유롭게 설정 가능
- ✅ 기간이 일정하지 않아도 문제없음
- ✅ 예: 입찰 3개월, 게시 6개월
- ✅ 예: 다음 입찰은 입찰 1주일, 게시 1개월

---

## 데이터베이스 구조

### 1. 입찰 라운드 테이블 (bidding_rounds)

```sql
CREATE TABLE `bidding_rounds` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `category` ENUM('mno', 'mvno', 'mno_sim') NOT NULL COMMENT '카테고리',
    `bidding_start_at` DATETIME NOT NULL COMMENT '입찰 시작일시',
    `bidding_end_at` DATETIME NOT NULL COMMENT '입찰 종료일시',
    `display_start_at` DATETIME NOT NULL COMMENT '게시 시작일시',
    `display_end_at` DATETIME NOT NULL COMMENT '게시 종료일시',
    `max_display_count` INT(11) UNSIGNED NOT NULL DEFAULT 20 COMMENT '최대 노출 개수',
    `min_bid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '최소 입찰 금액',
    `max_bid_amount` DECIMAL(12,2) NOT NULL DEFAULT 100000.00 COMMENT '최대 입찰 금액',
    `rotation_type` ENUM('fixed', 'rotating') NOT NULL DEFAULT 'fixed' COMMENT '운용 방식',
    `rotation_interval_minutes` INT(11) UNSIGNED DEFAULT NULL COMMENT '순환 간격 (분)',
    `status` ENUM('upcoming', 'bidding', 'closed', 'displaying', 'finished') NOT NULL DEFAULT 'upcoming',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` VARCHAR(50) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_category` (`category`),
    KEY `idx_status` (`status`),
    KEY `idx_bidding_period` (`bidding_start_at`, `bidding_end_at`),
    KEY `idx_display_period` (`display_start_at`, `display_end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**상태 설명:**
- `upcoming`: 입찰 예정
- `bidding`: 입찰 진행 중
- `closed`: 입찰 종료 (낙찰 처리 전)
- `displaying`: 게시 중
- `finished`: 완료

---

### 2. 입찰 참여 테이블 (bidding_participations)

```sql
CREATE TABLE `bidding_participations` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `bidding_round_id` INT(11) UNSIGNED NOT NULL,
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 user_id',
    `bid_amount` DECIMAL(12,2) NOT NULL COMMENT '입찰 금액',
    `status` ENUM('pending', 'won', 'lost', 'cancelled') NOT NULL DEFAULT 'pending',
    `rank` INT(11) UNSIGNED DEFAULT NULL COMMENT '낙찰 순위 (1~20)',
    `deposit_used` DECIMAL(12,2) NOT NULL COMMENT '사용된 예치금',
    `deposit_refunded` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '환불된 예치금',
    `bid_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '입찰 시간',
    `cancelled_at` DATETIME DEFAULT NULL COMMENT '취소 시간',
    `won_at` DATETIME DEFAULT NULL COMMENT '낙찰 확정 시간',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_round_seller` (`bidding_round_id`, `seller_id`),
    KEY `idx_bidding_round_id` (`bidding_round_id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_status` (`status`),
    KEY `idx_bid_amount` (`bid_amount`),
    KEY `idx_rank` (`rank`),
    KEY `idx_bid_at` (`bid_at`),
    CONSTRAINT `fk_bidding_participation_round` FOREIGN KEY (`bidding_round_id`) REFERENCES `bidding_rounds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 3. 낙찰자 게시물 배정 테이블 (bidding_product_assignments)

```sql
CREATE TABLE `bidding_product_assignments` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `bidding_round_id` INT(11) UNSIGNED NOT NULL,
    `bidding_participation_id` INT(11) UNSIGNED NOT NULL,
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '게시물(상품) ID',
    `display_order` INT(11) UNSIGNED NOT NULL COMMENT '노출 순서 (1~20)',
    `bid_amount` DECIMAL(12,2) NOT NULL COMMENT '입찰 금액 (참고용)',
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_rotated_at` DATETIME DEFAULT NULL COMMENT '마지막 순환 시간',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_round_order` (`bidding_round_id`, `display_order`),
    KEY `idx_bidding_round_id` (`bidding_round_id`),
    KEY `idx_bidding_participation_id` (`bidding_participation_id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_display_order` (`display_order`),
    CONSTRAINT `fk_bidding_assignment_round` FOREIGN KEY (`bidding_round_id`) REFERENCES `bidding_rounds` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bidding_assignment_participation` FOREIGN KEY (`bidding_participation_id`) REFERENCES `bidding_participations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bidding_assignment_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4. 판매자 예치금 테이블 (seller_deposits)

```sql
CREATE TABLE `seller_deposits` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `seller_id` VARCHAR(50) NOT NULL,
    `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '예치금 잔액',
    `bank_name` VARCHAR(100) DEFAULT NULL COMMENT '환불 계좌 은행명',
    `account_number` VARCHAR(50) DEFAULT NULL COMMENT '환불 계좌 번호',
    `account_holder` VARCHAR(100) DEFAULT NULL COMMENT '환불 계좌 예금주',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_seller_id` (`seller_id`),
    KEY `idx_balance` (`balance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 5. 예치금 거래 내역 테이블 (seller_deposit_transactions)

```sql
CREATE TABLE `seller_deposit_transactions` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `seller_id` VARCHAR(50) NOT NULL,
    `transaction_type` ENUM('deposit', 'bid', 'refund', 'withdrawal') NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL COMMENT '금액',
    `balance_before` DECIMAL(12,2) NOT NULL,
    `balance_after` DECIMAL(12,2) NOT NULL,
    `reference_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'bidding_participation_id 등',
    `reference_type` VARCHAR(50) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `processed_by` VARCHAR(50) DEFAULT NULL COMMENT '처리자 user_id',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_transaction_type` (`transaction_type`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_reference` (`reference_type`, `reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 입찰 프로세스

### Phase 1: 입찰 라운드 생성 (관리자)

```
1. 카테고리 선택 (mno, mvno, mno_sim)
2. 입찰 기간 설정 (bidding_start_at ~ bidding_end_at)
3. 게시 기간 설정 (display_start_at ~ display_end_at)
4. 최대 노출 개수 설정 (max_display_count, 기본 20개)
5. 최소 입찰 금액 설정 (min_bid_amount)
6. 최대 입찰 금액 설정 (max_bid_amount)
7. 운용 방식 선택:
   - 고정 모드 (fixed): min != max
   - 로테이션 모드 (rotating): min = max, 순환 간격 설정
```

---

### Phase 2: 입찰 참여 (판매자)

#### 입찰 참여 조건 검증
```
1. 판매자(seller) 역할 확인
2. 해당 카테고리 권한 확인
3. 입찰 기간 내인지 확인
4. 입찰 라운드 상태 확인 (status = 'bidding')
5. 중복 입찰 확인 (동일 라운드에 이미 입찰한 경우 불가)
6. 입찰 금액이 최소 금액 이상인지 확인
7. 입찰 금액이 최대 금액 이하인지 확인
8. 예치금 잔액 확인 (입찰 금액 이상 필요)
```

#### 입찰 참여 처리
```
1. 입찰 금액 입력 (1개만)
2. 트랜잭션 시작
3. 예치금 확인 및 차감
4. 거래 내역 기록
5. 입찰 참여 기록 (status='pending')
6. bid_at = 현재 시간 기록
7. 트랜잭션 커밋
```

#### 입찰 취소
```
1. 입찰 상태 확인 (status = 'pending'만 가능)
2. 입찰 기간 내인지 확인
3. 예치금 환불
4. 거래 내역 기록
5. 입찰 상태 변경 (status='cancelled')
```

---

### Phase 3: 낙찰 처리 (관리자)

#### 낙찰 기준
```
1. 모든 입찰을 bid_amount DESC, bid_at ASC 정렬
2. 상위 max_display_count(20)개까지 낙찰
3. 동점으로 21개 이상인 경우, 입찰 시간 기준으로 마지막 입찰 미낙찰
```

#### 낙찰 처리 프로세스
```
1. 입찰 정렬 (금액 내림차순, 시간 빠른 순)
2. 상위 N개 낙찰 (N ≤ 20):
   - status='won', rank=1~N
   - won_at=NOW()
3. 나머지 미낙찰:
   - status='lost'
   - 예치금 자동 환불
```

#### 낙찰자 게시물 선택/등록
```
1. 낙찰자가 게시 기간 시작 전까지 상품 선택 필수
2. 방법 1: 기존 등록 상품 중 선택 (권장)
3. 방법 2: 새 상품 등록 후 선택
4. 입찰 금액 순으로 display_order 자동 배정 (1~N)
5. bidding_product_assignments 테이블에 기록
```

---

### Phase 4: 게시 기간 (자동)

#### 자동 전환
```
1. 게시 시작일시 도래 → status='displaying'
2. 게시 종료일시 도래 → status='finished'
3. 다음 입찰 라운드 게시 시작일시 도래 → status='displaying'
```

---

## 운용 방식

### 1. 고정 모드 (rotation_type = 'fixed')

#### 설정
- `min_bid_amount != max_bid_amount` (금액 차등 허용)
- 예: 최소 10,000원, 최대 100,000원

#### 동작
- 입찰 금액 내림차순으로 고정 배치
- 게시 기간 동안 순서 변경 없음

#### 예시
```
1위: 100,000원 입찰자
2위: 95,000원 입찰자
3위: 90,000원 입찰자
...
20위: 50,000원 입찰자
```

---

### 2. 로테이션 모드 (rotation_type = 'rotating')

#### 설정
- 입찰 금액 설정 (단일 금액, 예: 50,000원)
- 내부적으로 `min_bid_amount = max_bid_amount = 설정한 금액`으로 저장
- `rotation_interval_minutes` 설정 (예: 10분, 60분)
- 판매자는 입찰 금액 입력 불필요 (자동으로 고정 금액 적용)

#### 동작
- 모든 입찰자는 동일 금액으로 입찰 (자동 적용)
- 입찰 시간(bid_at)에 따라 초기 순위 결정 (빠를수록 초기 상위)
- 설정된 시간 간격마다 순서 1칸씩 순환
- 1등→2등, 2등→3등, ..., N등→1등

#### 예시 (입찰 금액 50,000원, 10분 간격, 7개 낙찰)
```
관리자 설정: 입찰 금액 50,000원

입찰 참여 (판매자는 금액 입력 불필요):
- 판매자A: 10:00:01 입찰 → 50,000원 (자동)
- 판매자B: 10:00:02 입찰 → 50,000원 (자동)
- 판매자C: 10:00:03 입찰 → 50,000원 (자동)
...

노출 순서:
10:00 - 1위: A, 2위: B, 3위: C, ..., 7위: G
10:10 - 1위: B, 2위: C, 3위: D, ..., 7위: A
10:20 - 1위: C, 2위: D, 3위: E, ..., 7위: B
```

---

## 연속 입찰 라운드

### 개념
- 입찰 라운드가 연속적으로 진행됨
- 입찰 기간과 게시 기간을 자유롭게 설정 가능
- 기간이 일정하지 않아도 문제없음

### 예시 시나리오

#### 예시 1: 일정한 기간 (월별)
```
12월 입찰:
- 입찰: 2025-11-15 ~ 2025-11-30 (2주)
- 게시: 2025-12-01 ~ 2025-12-31 (1개월)

1월 입찰:
- 입찰: 2025-12-15 ~ 2025-12-31 (2주)
- 게시: 2026-01-01 ~ 2026-01-31 (1개월)
```

#### 예시 2: 불규칙한 기간
```
하반기 입찰:
- 입찰: 2025-07-01 ~ 2025-09-30 (3개월)
- 게시: 2025-10-01 ~ 2026-03-31 (6개월)

상반기 입찰:
- 입찰: 2025-12-01 ~ 2026-01-31 (2개월)
- 게시: 2026-04-01 ~ 2026-06-30 (3개월)
```

### 자동 전환
```
시점: 2025-12-31 23:59:59 → 2026-01-01 00:00:00

12월 라운드:
- display_end_at < NOW() → status='finished'

1월 라운드:
- display_start_at <= NOW() → status='displaying'
```

---

## 게시물 표시 로직

### 핵심 원칙
- 상위 20개 한도 내에서 실제 낙찰 건수만큼만 표시
- 입찰 건수가 7개인 경우 → 상단 7개만 입찰 게시물
- 나머지는 일반 게시물(등록 순서)로 표시

### 예시: 입찰 낙찰 건수 7개

```
상단 영역 (입찰 게시물):
1위: 입찰 게시물 #1
2위: 입찰 게시물 #2
3위: 입찰 게시물 #3
4위: 입찰 게시물 #4
5위: 입찰 게시물 #5
6위: 입찰 게시물 #6
7위: 입찰 게시물 #7

8위부터 (일반 게시물):
8위: 일반 게시물 (등록 순서, created_at DESC)
9위: 일반 게시물 (등록 순서)
10위: 일반 게시물 (등록 순서)
...
```

### 게시물 목록 쿼리 로직

```php
// 1. 입찰 게시물 조회 (실제 낙찰 개수만큼)
$biddingProducts = getBiddingProducts($category, $roundId, $rotationType);
// 예: 7개 낙찰 → 7개만 반환

// 2. 일반 게시물 조회
$normalProducts = getNormalProducts($category, $excludedProductIds, $offset, $limit);

// 3. 통합
$allProducts = array_merge($biddingProducts, $normalProducts);
```

---

## 예치금 시스템

### 예치금 흐름

```
1. 입찰 참여 시:
   - 예치금 차감 (seller_deposits.balance 감소)
   - 거래 내역 기록 (transaction_type='bid')

2. 입찰 취소 시:
   - 예치금 환불 (seller_deposits.balance 증가)
   - 거래 내역 기록 (transaction_type='refund')

3. 미낙찰 시:
   - 예치금 자동 환불
   - 거래 내역 기록 (transaction_type='refund')

4. 예치금 충전/환불 (관리자):
   - 관리자가 수동으로 처리
   - 거래 내역 기록 (transaction_type='deposit'/'withdrawal')
```

### 환불 계좌 관리
- 판매자가 자신의 환불 계좌 정보 등록/수정
- seller_deposits 테이블에 저장
- 은행명, 계좌번호, 예금주

---

## 관리자 기능

### 1. 입찰 라운드 관리
- 입찰 라운드 생성/수정/삭제
- 입찰 기간, 게시 기간 설정
- 최소/최대 입찰 금액 설정
- 운용 방식 선택 (고정/로테이션)
- 순환 간격 설정 (로테이션 모드)

### 2. 입찰 현황 관리
- 입찰 참여 현황 조회
- 입찰 금액 순 정렬
- 낙찰 처리 실행
- 낙찰 결과 확인

### 3. 낙찰자 관리
- 낙찰자 목록
- 게시물 배정 현황
- 게시물 미선택 낙찰자 알림

### 4. 예치금 관리
- 판매자별 예치금 잔액 조회
- 예치금 충전/환불
- 거래 내역 조회

---

## 판매자 안내

### 운용 방식 이해

#### 고정 모드 (간단 설명)
```
"입찰 금액이 높을수록 항상 상위에 노출됩니다.
게시 기간 동안 순서는 변경되지 않습니다."
```

#### 로테이션 모드 (간단 설명)
```
"모든 입찰자가 동일 금액으로 입찰하며,
설정된 시간마다 순서가 1칸씩 순환됩니다.
예: 10분마다 1등→2등→3등...→1등으로 순환"
```

### 입찰 프로세스 안내

```
1. 입찰 라운드 확인
   - 운용 방식 확인 (고정/로테이션)
   
   [고정 모드]
   - 최소/최대 입찰 금액 확인
   - 입찰 금액 입력 필요
   
   [로테이션 모드]
   - 입찰 금액 확인 (고정 금액)
   - 입찰 금액 입력 불필요 (자동 적용)
   - 순환 간격 확인

2. 예치금 확인
   - 예치금 잔액 확인
   - 입찰 금액 이상 필요 (고정 모드: 입력 금액, 로테이션 모드: 고정 금액)

3. 입찰 참여
   
   [고정 모드]
   - 입찰 금액 입력 (최소~최대 금액 사이)
   - 예치금 차감 확인
   - 입찰 등록
   
   [로테이션 모드]
   - 입찰 금액 입력 불필요 (자동으로 고정 금액 적용)
   - 예치금 차감 확인 (고정 금액만큼)
   - 입찰 등록 (입찰 시간이 빠를수록 초기 상위)

4. 낙찰 결과 확인
   - 낙찰 여부 확인
   - 낙찰 시 게시물 선택
   - 미낙찰 시 예치금 자동 환불
```

---

## 배치 작업

### 매일 자정 실행 (Cron Job)

```sql
-- 1. 게시 기간 종료된 라운드를 finished로 전환
UPDATE bidding_rounds
SET status = 'finished'
WHERE status = 'displaying'
  AND display_end_at < NOW();

-- 2. 게시 시작 시간이 된 라운드를 displaying으로 전환
UPDATE bidding_rounds
SET status = 'displaying'
WHERE status = 'closed'
  AND display_start_at <= NOW();

-- 3. 입찰 종료 시간이 된 라운드를 closed로 전환
UPDATE bidding_rounds
SET status = 'closed'
WHERE status = 'bidding'
  AND bidding_end_at < NOW();

-- 4. 입찰 시작 시간이 된 라운드를 bidding으로 전환
UPDATE bidding_rounds
SET status = 'bidding'
WHERE status = 'upcoming'
  AND bidding_start_at <= NOW();

-- 5. 로테이션 모드 순서 변경 (rotation_interval_minutes 간격)
-- (별도 함수로 구현)
```

---

## 보안 고려사항

### 1. 권한 검증
- 입찰 참여: seller 역할만 가능
- 카테고리 권한 확인
- 관리자 기능: admin/sub_admin만 접근

### 2. 예치금 보안
- 트랜잭션 처리 (ACID)
- 행 잠금 사용 (FOR UPDATE)
- 중복 차감 방지

### 3. 입찰 보안
- 입찰 기간 검증 (서버 시간 기준)
- 최소/최대 입찰 금액 검증
- 중복 입찰 방지 (UNIQUE KEY)
- SQL Injection 방지 (Prepared Statement)
- XSS 방지 (입력값 검증 및 출력 이스케이프)

---

## 전체 프로세스 요약

### 타임라인

```
[입찰 라운드 생성]
    ↓
[입찰 기간 시작] → status='bidding'
    ↓
[판매자 입찰 참여] → 예치금 차감
    ↓
[입찰 종료] → status='closed'
    ↓
[관리자 낙찰 처리] → 상위 20개 낙찰
    ↓
[낙찰자 게시물 선택]
    ↓
[게시 시작] → status='displaying'
    ↓
[게시물 상단 노출]
    ↓
[게시 종료] → status='finished'
    ↓
[다음 입찰 라운드 시작] (반복)
```

---

## 주요 문서 목록

1. **BIDDING_SYSTEM_DESIGN.md** - 메인 설계 문서
2. **BIDDING_SYSTEM_CONTINUOUS_ROUNDS.md** - 연속 입찰 라운드 상세
3. **BIDDING_SYSTEM_DISPLAY_MODE.md** - 게시물 표시 방식 상세
4. **BIDDING_SYSTEM_SELLER_GUIDE.md** - 판매자 안내서
5. **BIDDING_SYSTEM_TERMINOLOGY.md** - 용어집
6. **BIDDING_SYSTEM_COMPLETE_GUIDE.md** - 전체 종합 가이드 (본 문서)

---

## 체크리스트

### 개발 체크리스트
- [ ] 데이터베이스 테이블 생성
- [ ] 입찰 라운드 생성/관리 기능
- [ ] 입찰 참여 기능
- [ ] 입찰 취소 기능
- [ ] 낙찰 처리 기능
- [ ] 게시물 선택 기능
- [ ] 예치금 시스템 구현
- [ ] 고정 모드 게시물 표시
- [ ] 로테이션 모드 게시물 표시
- [ ] 자동 상태 전환 배치 작업
- [ ] 관리자 페이지 구현
- [ ] 판매자 페이지 구현

### 테스트 체크리스트
- [ ] 입찰 참여 테스트
- [ ] 입찰 취소 테스트
- [ ] 낙찰 처리 테스트
- [ ] 예치금 차감/환불 테스트
- [ ] 고정 모드 표시 테스트
- [ ] 로테이션 모드 표시 테스트
- [ ] 입찰 건수 20개 미만 테스트
- [ ] 자동 전환 테스트
- [ ] 보안 테스트

---

## 최종 정리

### 시스템의 핵심
1. **판매자당 카테고리별 1개 입찰만 가능**
2. **입찰 수정 불가, 취소 후 재입찰만 가능**
3. **예치금 기반 입찰 시스템**
4. **두 가지 운용 방식 (고정/로테이션)**
5. **유연한 기간 설정 (일정하지 않아도 됨)**
6. **자동 상태 전환**
7. **입찰 건수 유연성 (20개 미만 가능)**

### 설계의 강점
- 명확한 데이터 구조
- 보안 고려사항 포함
- 확장 가능한 설계
- 판매자 친화적 UI/UX
- 자동화된 프로세스


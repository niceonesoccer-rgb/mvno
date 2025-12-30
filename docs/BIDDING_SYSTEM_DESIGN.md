# 입찰 시스템 설계 문서

## 📋 목차
1. [시스템 개요](#시스템-개요)
2. [데이터베이스 설계](#데이터베이스-설계)
3. [주요 기능 설계](#주요-기능-설계)
4. [게시물 배치 로직](#게시물-배치-로직)
5. [예치금 시스템](#예치금-시스템)
6. [관리자 기능](#관리자-기능)
7. [보안 고려사항](#보안-고려사항)
8. [추가 고려사항](#추가-고려사항)

---

## 시스템 개요

### 목적
- 통신사폰(MNO), 알뜰폰(MVNO), 통신사유심(MNO-SIM) 세 가지 카테고리별 입찰 시스템
- 각 게시판 상단 20개 영역을 스폰서 영역으로 운영
- 입찰 낙찰자를 입찰금액 순으로 상단 노출

### 주요 특징
- 카테고리별 독립 입찰 진행
- 예치금 기반 입찰 시스템
- 낙찰자 게시물 선택 기능
- 순환/고정 배치 옵션
- 관리자 입찰 관리 기능
- **연속 입찰 시스템**: 연속적으로 입찰 라운드 진행
- **유연한 기간 설정**: 입찰 기간과 게시 기간을 자유롭게 설정 가능
  - 예: 입찰 3개월, 게시 6개월
  - 예: 입찰 1주일, 게시 1개월
  - 기간이 일정하지 않아도 문제없음
- **자동 전환**: 게시 종료/시작 시점에 자동으로 상태 전환
- **명확한 구분**: 현재 입찰과 다음 입찰을 시각적으로 구분

---

## 데이터베이스 설계

### 1. 입찰 테이블 (bidding_rounds)

```sql
CREATE TABLE `bidding_rounds` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `category` ENUM('mno', 'mvno', 'mno_sim') NOT NULL COMMENT '카테고리',
    `bidding_start_at` DATETIME NOT NULL COMMENT '입찰 시작일시',
    `bidding_end_at` DATETIME NOT NULL COMMENT '입찰 종료일시',
    `display_start_at` DATETIME NOT NULL COMMENT '게시 시작일시',
    `display_end_at` DATETIME NOT NULL COMMENT '게시 종료일시',
    `max_display_count` INT(11) UNSIGNED NOT NULL DEFAULT 20 COMMENT '최대 노출 개수 (상단 노출 개수)',
    `min_bid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '최소 입찰 금액',
    `max_bid_amount` DECIMAL(12,2) NOT NULL DEFAULT 100000.00 COMMENT '최대 입찰 금액',
    `rotation_type` ENUM('fixed', 'rotating') NOT NULL DEFAULT 'fixed' COMMENT '배치 유형 (고정/순환)',
    `rotation_interval_minutes` INT(11) UNSIGNED DEFAULT NULL COMMENT '순환 간격 (분, rotation_type=rotating일 때)',
    `status` ENUM('upcoming', 'bidding', 'closed', 'displaying', 'finished') NOT NULL DEFAULT 'upcoming' COMMENT '입찰 상태',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` VARCHAR(50) DEFAULT NULL COMMENT '생성자 user_id',
    PRIMARY KEY (`id`),
    KEY `idx_category` (`category`),
    KEY `idx_status` (`status`),
    KEY `idx_bidding_period` (`bidding_start_at`, `bidding_end_at`),
    KEY `idx_display_period` (`display_start_at`, `display_end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='입찰 라운드';
```

**필드 설명:**
- `max_display_count`: 상단에 노출될 최대 게시물 개수 (기본 20개)
- `min_bid_amount`: 최소 입찰 금액 (기본 0원)
- `max_bid_amount`: 최대 입찰 금액 (기본 100,000원)
- **정책**: 판매자당 카테고리별 1개 입찰만 가능

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
    `bidding_round_id` INT(11) UNSIGNED NOT NULL COMMENT '입찰 라운드 ID',
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 user_id',
    `bid_amount` DECIMAL(12,2) NOT NULL COMMENT '입찰 금액',
    `status` ENUM('pending', 'won', 'lost', 'cancelled') NOT NULL DEFAULT 'pending' COMMENT '입찰 상태',
    `rank` INT(11) UNSIGNED DEFAULT NULL COMMENT '낙찰 순위 (NULL=미낙찰, 낙찰 시 1~20)',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='입찰 참여';
```

**상태 설명:**
- `pending`: 입찰 참여 중 (결과 대기, 입찰 확정 전)
- `won`: 낙찰
- `lost`: 미낙찰 (낙찰 처리 시 20개 제한에 걸려 미낙찰)
- `cancelled`: 판매자가 취소 (입찰 확정 전 취소)

---

### 3. 낙찰자 게시물 매핑 테이블 (bidding_product_assignments)

```sql
CREATE TABLE `bidding_product_assignments` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `bidding_round_id` INT(11) UNSIGNED NOT NULL COMMENT '입찰 라운드 ID',
    `bidding_participation_id` INT(11) UNSIGNED NOT NULL COMMENT '입찰 참여 ID',
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '게시물(상품) ID',
    `display_order` INT(11) UNSIGNED NOT NULL COMMENT '노출 순서 (1~20)',
    `bid_amount` DECIMAL(12,2) NOT NULL COMMENT '입찰 금액 (참고용)',
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '배정 시간',
    `last_rotated_at` DATETIME DEFAULT NULL COMMENT '마지막 순환 시간 (순환 모드일 때)',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_round_order` (`bidding_round_id`, `display_order`),
    KEY `idx_bidding_round_id` (`bidding_round_id`),
    KEY `idx_bidding_participation_id` (`bidding_participation_id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_display_order` (`display_order`),
    CONSTRAINT `fk_bidding_assignment_round` FOREIGN KEY (`bidding_round_id`) REFERENCES `bidding_rounds` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bidding_assignment_participation` FOREIGN KEY (`bidding_participation_id`) REFERENCES `bidding_participations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bidding_assignment_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='낙찰자 게시물 배정';
```

---

### 4. 판매자 예치금 테이블 (seller_deposits)

```sql
CREATE TABLE `seller_deposits` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 user_id',
    `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '예치금 잔액',
    `bank_name` VARCHAR(100) DEFAULT NULL COMMENT '환불 계좌 은행명',
    `account_number` VARCHAR(50) DEFAULT NULL COMMENT '환불 계좌 번호',
    `account_holder` VARCHAR(100) DEFAULT NULL COMMENT '환불 계좌 예금주',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_seller_id` (`seller_id`),
    KEY `idx_balance` (`balance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='판매자 예치금 계정';
```

---

### 5. 예치금 거래 내역 테이블 (seller_deposit_transactions)

```sql
CREATE TABLE `seller_deposit_transactions` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 user_id',
    `transaction_type` ENUM('deposit', 'bid', 'refund', 'withdrawal') NOT NULL COMMENT '거래 유형',
    `amount` DECIMAL(12,2) NOT NULL COMMENT '금액 (deposit/bid: -, refund/withdrawal: +)',
    `balance_before` DECIMAL(12,2) NOT NULL COMMENT '거래 전 잔액',
    `balance_after` DECIMAL(12,2) NOT NULL COMMENT '거래 후 잔액',
    `reference_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '참조 ID (bidding_participation_id 등)',
    `reference_type` VARCHAR(50) DEFAULT NULL COMMENT '참조 타입 (bidding_participation 등)',
    `description` TEXT DEFAULT NULL COMMENT '설명',
    `processed_by` VARCHAR(50) DEFAULT NULL COMMENT '처리자 user_id (관리자)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_transaction_type` (`transaction_type`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_reference` (`reference_type`, `reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='예치금 거래 내역';
```

**거래 유형 설명:**
- `deposit`: 예치금 충전 (관리자)
- `bid`: 입찰 참여 차감
- `refund`: 미낙찰 환불 또는 관리자 환불
- `withdrawal`: 예치금 출금 (환불 처리)

---

## 주요 기능 설계

### 1. 입찰 라운드 관리

#### 1.1 입찰 라운드 생성 (관리자)
```
- 카테고리 선택: mno, mvno, mno_sim
- 입찰 기간 설정 (bidding_start_at ~ bidding_end_at)
  → 자유롭게 설정 가능 (예: 1주일, 1개월, 3개월 등)
- 게시 기간 설정 (display_start_at ~ display_end_at)
  → 자유롭게 설정 가능 (예: 1개월, 6개월 등)
- 최대 노출 개수 설정 (max_display_count, 기본 20개)
  → 상단에 노출될 최대 게시물 개수
- 최소 입찰 금액 설정 (min_bid_amount, 기본 0원)
  → 입찰 가능한 최소 금액
- 최대 입찰 금액 설정 (max_bid_amount, 기본 100,000원)
  → 입찰 가능한 최대 금액
- 운용 방식 선택:
  - **고정 모드 (fixed)**: 입찰 금액별 차등 순서 고정
    → 입찰 금액 내림차순으로 고정 배치
  - **로테이션 모드 (rotating)**: 금액 동일 시 순서 로테이션
    → 최저금액 = 최대금액으로 설정하여 모두 동일 금액 입찰
    → 순환 간격 설정 (10분, 1시간 등)
    → 설정된 시간마다 순위 1칸씩 순환
```

**정책:**
- 판매자당 카테고리별 1개 입찰만 가능
- 동일 라운드에 중복 입찰 불가 (UNIQUE KEY 제약)
- **입찰 기간과 게시 기간은 자유롭게 설정 가능**
  - 예: 입찰 기간 3개월, 게시 기간 6개월
  - 예: 다음 입찰은 입찰 기간 1주일, 게시 기간 1개월
  - 기간이 일정하지 않아도 문제없음

#### 1.2 입찰 상태 자동 변경

**자동 전환 로직 (배치 작업 필요):**
- 입찰 시작일시 도래 → `upcoming` → `bidding`
- 입찰 종료일시 도래 → `bidding` → `closed`
- 낙찰 처리 완료 → `closed` → `displaying` (관리자 수동 또는 자동)
- 게시 시작일시 도래 → `closed` → `displaying` (낙찰 처리 완료된 경우)
- 게시 종료일시 도래 → `displaying` → `finished`

**중요:**
- 게시 종료 시점(display_end_at)에 자동으로 `finished`로 전환
- 다음 입찰 라운드의 게시 시작 시점(display_start_at)에 자동으로 `displaying`으로 전환
- 이를 통해 연속적인 입찰 시스템이 자동으로 운영됨

---

### 2. 입찰 참여 프로세스

#### 2.1 입찰 참여 조건 검증
```
1. 판매자(seller) 역할인지 확인
2. 해당 카테고리 권한이 있는지 확인 (seller_profiles.permissions)
3. 입찰 기간 내인지 확인 (bidding_start_at ~ bidding_end_at)
4. 입찰 라운드 상태 확인 (status = 'bidding')
5. 중복 입찰 확인 (동일 라운드에 이미 입찰한 경우 불가)
6. 입찰 금액이 최소 입찰 금액 이상인지 확인 (min_bid_amount)
7. 입찰 금액이 최대 입찰 금액 이하인지 확인 (max_bid_amount)
8. 예치금 잔액 확인 (입찰 금액 이상 필요)
```

#### 2.2 입찰 참여 처리
```
1. 입찰 금액 입력 (1개만)
2. 트랜잭션 시작
3. 예치금 확인 및 차감 (seller_deposits.balance 감소)
4. 거래 내역 기록 (seller_deposit_transactions, transaction_type='bid')
5. 입찰 참여 기록 (bidding_participations, status='pending')
6. bid_at = 현재 시간 기록 (동점 처리 기준)
7. 트랜잭션 커밋
```

**중요:**
- 판매자당 카테고리별 1개 입찰만 가능
- 동일 라운드에 중복 입찰 불가 (UNIQUE KEY 제약)
- 기존 입찰이 있으면 취소 후 재등록해야 함

#### 2.3 입찰 취소 (입찰 확정 전까지)
```
1. 입찰 상태 확인 (status = 'pending'만 취소 가능)
2. 입찰 기간 내인지 확인 (bidding_end_at 전까지만 가능)
3. 예치금 환불 (seller_deposits.balance 증가)
4. 거래 내역 기록 (seller_deposit_transactions, transaction_type='refund')
5. 입찰 상태 변경 (bidding_participations.status = 'cancelled')
6. cancelled_at = 현재 시간 기록
```

**참고:** 취소 후 동일 라운드에 다시 입찰 참여 가능

---

### 3. 낙찰 처리

#### 3.1 낙찰 기준
```
1. 모든 판매자의 입찰을 하나의 풀에서 비교
2. 입찰 금액 내림차순 정렬
3. 동일 금액인 경우 입찰 시간(bid_at) 기준 빠른 순 (ASC)
4. 상위 max_display_count(기본 20개)만큼 낙찰
5. 동점으로 21개 이상인 경우, 입찰 시간 기준으로 마지막 등록된 입찰 미낙찰
```

**중요:**
- 판매자당 1개 입찰만 가능하므로, 각 판매자는 최대 1개만 낙찰 가능
- 판매자 구분 없이 모든 입찰을 비교
- 상위 20개만 낙찰, 나머지는 미낙찰

#### 3.2 낙찰 처리 프로세스 (상세)

**단계 1: 입찰 정렬**
```
- status = 'pending'인 입찰만 대상
- bid_amount DESC, bid_at ASC 정렬
- 판매자 구분 없이 모든 입찰을 하나의 풀에서 비교
```

**단계 2: 낙찰 처리 (1~20위)**

**예시 시나리오:**
```
입찰 현황:
- 판매자 A: 1개 입찰, 200,000원 (등록 시간: 10:00:01)
- 판매자 B: 1개 입찰, 200,000원 (등록 시간: 10:00:02)
- 판매자 C: 1개 입찰, 200,000원 (등록 시간: 10:00:03)
- 판매자 D: 1개 입찰, 200,000원 (등록 시간: 10:00:04)
- 판매자 E: 1개 입찰, 200,000원 (등록 시간: 10:00:05)
- 판매자 F: 1개 입찰, 200,000원 (등록 시간: 10:00:06)
- ... (총 25명의 판매자가 입찰)

총 25개 입찰, 모두 200,000원 (동점)

정렬 (금액 동일하므로 입찰 시간 빠른 순):
1위: 판매자 A (10:00:01) ✅ 낙찰
2위: 판매자 B (10:00:02) ✅ 낙찰
...
20위: 판매자 T (10:00:20) ✅ 낙찰
21위: 판매자 U (10:00:21) ❌ 미낙찰
22위: 판매자 V (10:00:22) ❌ 미낙찰
...

결과:
- 1~20위: 낙찰 (status='won', rank=1~20)
- 21위 이후: 미낙찰 (status='lost', 예치금 자동 환불)
```

**처리 로직:**
```
1. 모든 입찰을 bid_amount DESC, bid_at ASC 정렬
2. 상위 max_display_count(20)개만 낙찰:
   - bidding_participations.status = 'won'
   - bidding_participations.rank = 1, 2, 3, ..., 20
   - bidding_participations.won_at = NOW()
3. 나머지 입찰은 미낙찰:
   - bidding_participations.status = 'lost'
   - bidding_participations.rank = NULL
```

**단계 3: 미낙찰자 예치금 환불 처리 (자동)**
```
- status = 'lost'인 입찰
- 예치금 자동 환불 (seller_deposits.balance 증가)
- 거래 내역 기록 (seller_deposit_transactions, transaction_type='refund')
- 각 입찰별로 개별 환불 처리
```

**단계 4: 낙찰자 알림**
```
- 낙찰자에게 게시물 선택 요청 알림
- 선택은 별도 페이지에서 진행
- 낙찰 개수만큼 게시물 선택 가능
```

**특수 케이스: 동점 21개**
```
동일 금액 입찰이 정확히 21개인 경우:
- 1~20위: 낙찰
- 21위 (입찰 시간 가장 늦은 것): 미낙찰
- status = 'lost'
- 예치금 자동 환불
```

---

### 4. 낙찰자 게시물 선택

#### 4.1 게시물 선택 조건
```
1. 낙찰자가 등록한 게시물만 선택 가능
2. 해당 카테고리 게시물만 선택 가능
3. 낙찰 개수만큼만 선택 가능 (판매자당 1개 입찰이므로 1개만 선택)
4. 게시 기간 시작 전까지 선택 가능
5. status = 'won'인 입찰에 대해서만 선택 가능
```

#### 4.2 게시물 배정 프로세스

**방법 1: 기존 등록 상품 중 선택 (권장)**
```
1. 낙찰자가 "내 입찰 현황" 페이지 접속
2. 낙찰된 입찰 항목 확인
3. "게시물 선택" 버튼 클릭
4. 내가 등록한 상품 목록 표시 (해당 카테고리, active 상태만)
5. 상품 선택 (1개만)
6. bidding_product_assignments 테이블에 기록
```

**방법 2: 새 상품 등록 후 선택**
```
1. 낙찰자가 "새 상품 등록" 버튼 클릭
2. 일반 상품 등록 프로세스 진행 (카테고리는 입찰 카테고리로 고정)
3. 등록 완료 후 게시물 선택 페이지로 이동
4. 방금 등록한 상품 선택
5. bidding_product_assignments 테이블에 기록
```

**display_order 자동 배정:**
```
- 입찰 금액 내림차순, 입찰 시간 빠른 순으로 자동 배정
- 1등 (가장 높은 금액, 빠른 시간) → display_order = 1
- 2등 → display_order = 2
- ...
- N등 → display_order = N (N ≤ 20)
- 게시물 선택 시마다 자동 재계산
```

**중요:** 
- 판매자당 1개 입찰이므로 1개 게시물만 선택
- 게시 기간 시작 전까지 선택 필수
- 배정 순서는 전체 입찰 금액 기준, 동점 시 입찰 시간 빠른 순
- 상세 프로세스는 [낙찰 후 상품 등록/선택 프로세스](./BIDDING_SYSTEM_PRODUCT_ASSIGNMENT.md) 참고

---

## 게시물 배치 로직

### 1. 고정 모드 (rotation_type = 'fixed')

**사용 시나리오:**
- 입찰 금액별 차등 순서를 고정하여 매번 같은 순위로 표시
- 관리자가 `min_bid_amount != max_bid_amount`로 설정

**동작 방식:**
- 낙찰 금액 내림차순으로 고정 배치
- 게시 기간 동안 순서 변경 없음
- `bidding_product_assignments.display_order` 기준 정렬 (1~20위)
- 입찰 금액이 높을수록 상위 노출

**예시:**
```
1위: 입찰 금액 100,000원
2위: 입찰 금액 95,000원
3위: 입찰 금액 90,000원
...
20위: 입찰 금액 50,000원
```

### 2. 로테이션 모드 (rotation_type = 'rotating')

**사용 시나리오:**
- 모든 입찰자가 동일 금액으로 입찰
- 관리자가 입찰 금액 하나만 설정 (예: 50,000원)
- 판매자는 입찰 금액을 입력할 필요 없음 (자동으로 고정 금액 적용)

**관리자 설정:**
- 입찰 금액 설정 (단일 금액)
- 내부적으로 min_bid_amount = max_bid_amount = 설정한 금액으로 저장
- 순환 간격 설정 (10분, 1시간 등)

**판매자 입찰:**
- 입찰 금액 입력 불필요 (자동으로 고정 금액 적용)
- 입찰 시간(bid_at)에 따라 초기 순위 결정
- 입찰이 빠를수록 초기 상위 노출

**동작 방식:**
- 모든 입찰자는 동일 금액으로 입찰 (bid_amount = 고정 금액)
- 입찰 시간 순서로 초기 순위 결정 (빠를수록 상위)
- 일정 간격(rotation_interval_minutes)마다 순서 변경
- 예: 10분마다 1등→2등, 2등→3등, ..., N등→1등으로 순환
- `last_rotated_at` 기준으로 다음 순환 시간 계산
- 배치 작업(cron job)으로 주기적 실행
- 각 낙찰자가 공평하게 상위 노출 기회 제공

**예시 (입찰 금액 50,000원, 10분 간격, 7개 낙찰):**
```
관리자 설정:
- 입찰 금액: 50,000원
- 순환 간격: 10분

입찰 참여:
- 판매자A: 10:00:01 입찰 → 50,000원 (자동)
- 판매자B: 10:00:02 입찰 → 50,000원 (자동)
- 판매자C: 10:00:03 입찰 → 50,000원 (자동)
...

노출 순서:
10:00 - 1위: 판매자A, 2위: 판매자B, 3위: 판매자C, ..., 7위: 판매자G
10:10 - 1위: 판매자B, 2위: 판매자C, 3위: 판매자D, ..., 7위: 판매자A
10:20 - 1위: 판매자C, 2위: 판매자D, 3위: 판매자E, ..., 7위: 판매자B
```

### 3. 게시물 목록 쿼리 로직

**중요: 입찰 건수가 20개 미만일 경우 처리**
- 입찰 낙찰 건수가 7개인 경우 → 상단 7개만 입찰 게시물 표시
- 나머지 8번째부터는 일반 게시물(등록 순서)로 표시

```sql
-- 입찰 게시물 (상단 1~20개, 실제 낙찰 개수만큼만)
SELECT 
    p.*,
    bpa.display_order,
    bpa.bid_amount,
    bp.seller_id as bidder_id
FROM products p
INNER JOIN bidding_product_assignments bpa ON p.id = bpa.product_id
INNER JOIN bidding_participations bp ON bpa.bidding_participation_id = bp.id
WHERE bpa.bidding_round_id = :round_id
  AND bpa.bidding_round_id IN (
      SELECT id FROM bidding_rounds 
      WHERE category = :category 
        AND status = 'displaying'
        AND display_start_at <= NOW()
        AND display_end_at >= NOW()
  )
ORDER BY bpa.display_order ASC
-- LIMIT은 실제 낙찰 개수만큼만 (최대 20개)

UNION ALL

-- 일반 게시물 (입찰 게시물 다음부터)
SELECT 
    p.*,
    NULL as display_order,
    NULL as bid_amount,
    NULL as bidder_id
FROM products p
WHERE p.product_type = :category
  AND p.status = 'active'
  AND p.id NOT IN (
      SELECT product_id FROM bidding_product_assignments 
      WHERE bidding_round_id IN (
          SELECT id FROM bidding_rounds 
          WHERE category = :category 
            AND status = 'displaying'
      )
  )
ORDER BY p.created_at DESC
LIMIT :offset, :limit
```

**예시: 입찰 낙찰 건수 7개인 경우**

```
상단 1~7위: 입찰 게시물 (입찰 금액 순 또는 로테이션)
8위부터: 일반 게시물 (등록 순서, created_at DESC)
```

**예시: 입찰 낙찰 건수 20개인 경우**

```
상단 1~20위: 입찰 게시물 (입찰 금액 순 또는 로테이션)
21위부터: 일반 게시물 (등록 순서, created_at DESC)
```

---

## 예치금 시스템

### 1. 예치금 충전 (관리자)
```
- 관리자가 판매자 예치금 충전
- seller_deposits.balance 증가
- seller_deposit_transactions 기록
- processed_by = 관리자 user_id
```

### 2. 예치금 환불 (관리자)
```
- 관리자가 판매자 예치금 환불 처리
- seller_deposits.balance 감소
- seller_deposit_transactions 기록
- 실제 환불은 별도 프로세스 (은행 이체 등)
```

### 3. 예치금 출금 요청 (판매자)
```
- 판매자가 출금 요청 (미구현, 향후 확장 가능)
- 관리자 승인 후 환불 처리
```

### 4. 환불 계좌 관리 (판매자)
```
- 판매자가 자신의 환불 계좌 정보 등록/수정
- seller_deposits 테이블에 저장
- 보안: 암호화 저장 권장
```

---

## 관리자 기능

### 1. 입찰 라운드 관리 페이지
```
/admin/bidding/rounds/list.php
- 입찰 라운드 목록
- 입찰 라운드 생성/수정/삭제
- 상태별 필터링
- 카테고리별 필터링

입찰 라운드 생성/수정 폼:
- 카테고리 선택
- 입찰 기간 설정
- 게시 기간 설정
- 최대 노출 개수 설정
- 최소 입찰 금액 설정 (신규 추가)
- 최대 입찰 금액 설정
- 배치 유형 선택
- 순환 간격 설정 (순환 모드일 때)
```

### 2. 입찰 현황 관리 페이지
```
/admin/bidding/participations/list.php
- 입찰 참여 현황
- 입찰 금액 순 정렬
- 낙찰 처리 버튼
- 낙찰 결과 확인
```

### 3. 낙찰자 관리 페이지
```
/admin/bidding/winners/list.php
- 낙찰자 목록
- 게시물 배정 현황
- 게시물 미선택 낙찰자 알림
```

### 4. 예치금 관리 페이지
```
/admin/deposits/list.php
- 판매자별 예치금 잔액
- 예치금 충전/환불
- 거래 내역 조회
```

---

## 보안 고려사항

### 1. 권한 검증
```
- 입찰 참여: seller 역할만 가능
- 카테고리 권한 확인 (seller_profiles.permissions)
- 관리자 기능: admin/sub_admin만 접근 가능
```

### 2. 예치금 보안
```
- 예치금 차감 시 트랜잭션 처리 (ACID)
- 잔액 확인 후 차감 (FOR UPDATE 사용)
- 중복 차감 방지 (트랜잭션 격리 수준)
```

### 3. 입찰 보안
```
- 입찰 기간 검증 (서버 시간 기준)
- 최소 입찰 금액 검증 (min_bid_amount 이상)
- 최대 입찰 금액 검증 (max_bid_amount 이하)
- 중복 입찰 방지 (UNIQUE KEY)
- SQL Injection 방지 (Prepared Statement)
- XSS 방지 (입력값 검증 및 출력 이스케이프)
```

### 4. 계좌 정보 보안
```
- 계좌 정보 암호화 저장 (선택적)
- HTTPS 통신 필수
- 로그에 민감 정보 기록 금지
```

---

## 추가 고려사항

### 1. 주요 정책 명확화

#### 1.1 입찰 수정 정책
**정책:**
- ❌ 입찰 수정 불가
- 취소 후 재입찰만 가능

#### 1.2 입찰 취소 정책
**정책:**
- ✅ 입찰 확정 전까지 취소 가능
- 취소 후 동일 라운드에 다시 입찰 가능
- 취소 시 예치금 즉시 환불 (자동)

#### 1.3 낙찰 동점 처리 정책
**정책:**
- 동일 금액 입찰 시 입찰 시간(bid_at) 빠른 순으로 처리
- 입찰 시간까지 동일한 경우는 거의 없지만, 발생 시 ID 순으로 처리

#### 1.4 게시물 선택 미완료 시 처리
**문제점:**
- 낙찰자가 게시물을 선택하지 않으면 빈 슬롯 발생

**제안:**
- 선택 기한 설정 (게시 시작 24시간 전 등)
- 미선택 시 자동으로 가장 최신 게시물 배정 또는 다음 순위로 대체

#### 1.5 순환 모드 구현 복잡도
**문제점:**
- 순환 로직 구현 및 관리 복잡

**제안:**
- 초기에는 고정 모드만 구현
- 순환 모드는 2단계에서 구현

#### 1.6 예치금 부족 시 처리
**문제점:**
- 예치금이 부족한 판매자의 입찰 참여 제한만 있고, 충전 안내 부족

**제안:**
- 예치금 충전 안내 페이지 제공
- 예치금 잔액 알림 기능

#### 1.7 입찰 종료 후 낙찰 처리 지연
**문제점:**
- 입찰 종료 후 관리자가 수동으로 낙찰 처리해야 함

**제안:**
- 자동 낙찰 처리 옵션 추가
- 낙찰 처리 알림 기능 (관리자에게)

---

### 2. 성능 최적화

#### 2.1 인덱스 최적화
- 모든 외래키에 인덱스 설정
- 자주 조회되는 컬럼에 인덱스 추가

#### 2.2 캐싱
- 활성 입찰 라운드 정보 캐싱
- 게시물 목록 캐싱 (게시 기간 동안)

#### 2.3 배치 작업

**필수 배치 작업:**

1. **입찰 상태 자동 변경 (매일 자정 실행)**
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
```

2. **순환 모드 순서 변경 (rotation_type='rotating'일 때)**
- rotation_interval_minutes 간격마다 순서 변경
- bidding_product_assignments.display_order 업데이트
- 1등→2등, 2등→3등, ..., N등→1등으로 순환 (N = 실제 낙찰 건수)
- 입찰 건수가 7개인 경우: 7등→1등으로 순환 (8등 없음)

---

### 3. 알림 시스템

#### 3.1 판매자 알림
- 입찰 참여 완료 알림
- 낙찰 결과 알림
- 게시물 선택 요청 알림
- 예치금 부족 알림

#### 3.2 관리자 알림
- 입찰 종료 알림
- 낙찰 처리 요청 알림
- 게시물 미선택 낙찰자 알림

---

### 4. 로그 및 감사

#### 4.1 감사 로그
- 입찰 참여/수정/취소 로그
- 예치금 충전/환불 로그
- 관리자 작업 로그

#### 4.2 통계
- 카테고리별 입찰 통계
- 판매자별 입찰 통계
- 예치금 사용 통계

---

### 5. 확장 가능성

#### 5.1 다중 입찰 참여
- ✅ 한 판매자가 동일 라운드에 여러 입찰 참여 가능 (이미 구현됨)
- 각 입찰마다 별도 게시물 선택
- 단, 낙찰 개수 제한에 따라 일부만 낙찰될 수 있음

#### 5.2 입찰 단위 확장
- 현재: 1개 입찰 = 1개 게시물 노출
- 확장: 1개 입찰 = 여러 게시물 노출 (패키지 입찰)

#### 5.3 입찰 타입 확장
- 현재: 가격 입찰만
- 확장: 클릭률 기반 입찰, 노출 시간 기반 입찰 등

---

## 구현 우선순위

### Phase 1: 기본 입찰 시스템
1. 데이터베이스 테이블 생성
2. 예치금 시스템 기본 기능
3. 입찰 라운드 생성/관리
4. 입찰 참여 기능
5. 낙찰 처리 기능
6. 게시물 선택 기능
7. 고정 모드 게시물 배치

### Phase 2: 관리 기능 강화
1. 관리자 대시보드
2. 입찰 현황 조회
3. 예치금 관리 기능
4. 통계 및 리포트

### Phase 3: 고급 기능
1. 순환 모드 구현
2. 알림 시스템
3. 자동 낙찰 처리
4. 감사 로그

---

## 데이터베이스 관계도

```
bidding_rounds (입찰 라운드)
    ├── bidding_participations (입찰 참여) ──┐
    │                                         │
    └── bidding_product_assignments (게시물 배정) ──┼── products (게시물)
                                                    │
seller_deposits (예치금 계정) ────────────────────────┘
    └── seller_deposit_transactions (거래 내역)
```

---

## API 엔드포인트 설계 (참고)

### 판매자 API
```
GET    /api/bidding/rounds                - 입찰 라운드 목록 (상태별 필터링)
GET    /api/bidding/rounds/:id            - 입찰 라운드 상세
POST   /api/bidding/participate           - 입찰 참여
DELETE /api/bidding/participate/:id       - 입찰 취소 (입찰 확정 전까지)
GET    /api/bidding/my-participations     - 내 입찰 목록
GET    /api/bidding/my-participations/:round_id - 특정 라운드 내 입찰 정보
GET    /api/bidding/my-participations/:id/products - 낙찰자 게시물 목록 조회
POST   /api/bidding/assign-product        - 게시물 선택/배정
DELETE /api/bidding/assign-product/:id    - 게시물 선택 해제
GET    /api/deposits/balance              - 예치금 잔액 조회
PUT    /api/deposits/refund-account       - 환불 계좌 등록/수정
```

### 관리자 API
```
POST   /api/admin/bidding/rounds                - 입찰 라운드 생성
PUT    /api/admin/bidding/rounds/:id            - 입찰 라운드 수정
POST   /api/admin/bidding/rounds/:id/close      - 낙찰 처리
GET    /api/admin/bidding/participations        - 입찰 현황 조회
POST   /api/admin/deposits/charge               - 예치금 충전
POST   /api/admin/deposits/refund               - 예치금 환불
```

---

## 결론

이 설계 문서는 입찰 시스템의 전체 구조와 주요 기능을 정의합니다. 
단계적 구현을 통해 안정적인 시스템을 구축할 수 있습니다.

**주요 강점:**
- 명확한 데이터 구조
- 보안 고려사항 포함
- 확장 가능한 설계
- 단계적 구현 계획

**주요 정책:**
- ✅ 판매자당 카테고리별 1개 입찰만 가능
- ✅ 입찰 수정 불가, 취소 후 재입찰만 가능
- ✅ 입찰 확정 전까지 취소 가능, 취소 시 예치금 자동 환불
- ✅ 모든 입찰을 하나의 풀에서 비교, 상위 20개만 낙찰
- ✅ 동점 처리: 입찰 시간 빠른 순
- ✅ 동점 21개 이상 시 마지막 입찰 미낙찰, 예치금 자동 환불
- ✅ **유연한 기간 설정**: 입찰 기간과 게시 기간을 자유롭게 설정 가능
  - 입찰 3개월, 게시 6개월 등 불규칙한 기간도 가능
  - 각 라운드마다 다른 기간 설정 가능
  - 기간이 일정하지 않아도 문제없음
- ✅ **자동 전환**: 각 라운드의 display_start_at, display_end_at 기준으로 자동 전환

**참고 문서:**
- [연속 입찰 라운드 시스템 상세 설계](./BIDDING_SYSTEM_CONTINUOUS_ROUNDS.md)
- [입찰 게시물 표시 방식 설계](./BIDDING_SYSTEM_DISPLAY_MODE.md)
- [판매자 안내서](./BIDDING_SYSTEM_SELLER_GUIDE.md)
- [용어집](./BIDDING_SYSTEM_TERMINOLOGY.md)


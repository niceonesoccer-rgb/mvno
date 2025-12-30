# 상품 광고 시스템 제안서

## 📋 개요

판매자가 자신이 등록한 상품을 선택하여 카테고리별로 광고를 진행할 수 있는 시스템입니다.
관리자는 광고 기간별 금액을 설정하고, 판매자는 원하는 상품을 선택하여 광고 기간을 설정하여 광고를 진행합니다.

---

## 🎯 주요 요구사항

1. **판매자 기능**
   - 자신이 등록한 상품 중 광고할 상품 선택
   - 광고 기간 설정 (일주일 단위)
   - 광고 신청 및 결제
   - 광고 현황 조회

2. **관리자 기능**
   - 광고 기간별 금액 설정 (일주일, 한달, 3달, 6개월)
   - 광고 승인/거부
   - 광고 현황 관리
   - 광고 통계 조회

3. **시스템 기능**
   - 광고 중인 상품 우선 노출
   - 광고 종료 시 자동 해제
   - 광고 상태 관리 (대기, 승인, 진행중, 종료, 거부)

---

## 🗄️ 데이터베이스 설계

### 1. 상품 광고 설정 테이블 (product_advertisement_prices)

관리자가 설정하는 광고 기간별 금액 정보를 저장합니다.

```sql
CREATE TABLE IF NOT EXISTS `product_advertisement_prices` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL COMMENT '상품 타입',
    `period_type` ENUM('week', 'month', 'quarter', 'half_year') NOT NULL COMMENT '기간 타입',
    `period_days` INT(11) UNSIGNED NOT NULL COMMENT '기간 일수 (7, 30, 90, 180)',
    `price` DECIMAL(12,2) NOT NULL COMMENT '광고 금액',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_type_period` (`product_type`, `period_type`),
    KEY `idx_product_type` (`product_type`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 광고 기간별 가격 설정';
```

**기간 타입 설명:**
- `week`: 일주일 (7일)
- `month`: 한달 (30일)
- `quarter`: 3개월 (90일)
- `half_year`: 6개월 (180일)

**초기 데이터 예시:**
```sql
INSERT INTO `product_advertisement_prices` (`product_type`, `period_type`, `period_days`, `price`) VALUES
('mvno', 'week', 7, 50000),
('mvno', 'month', 30, 180000),
('mvno', 'quarter', 90, 500000),
('mvno', 'half_year', 180, 900000),
('mno', 'week', 7, 60000),
('mno', 'month', 30, 220000),
('mno', 'quarter', 90, 600000),
('mno', 'half_year', 180, 1100000),
('internet', 'week', 7, 55000),
('internet', 'month', 30, 200000),
('internet', 'quarter', 90, 550000),
('internet', 'half_year', 180, 1000000);
```

### 2. 상품 광고 신청 테이블 (product_advertisements)

판매자가 신청한 광고 정보를 저장합니다.

```sql
CREATE TABLE IF NOT EXISTS `product_advertisements` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
    `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL COMMENT '상품 타입',
    `period_type` ENUM('week', 'month', 'quarter', 'half_year') NOT NULL COMMENT '광고 기간 타입',
    `period_days` INT(11) UNSIGNED NOT NULL COMMENT '광고 기간 일수',
    `advertisement_price` DECIMAL(12,2) NOT NULL COMMENT '광고 금액 (신청 시점 금액)',
    `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending' COMMENT '결제 상태',
    `payment_method` VARCHAR(50) DEFAULT NULL COMMENT '결제 수단',
    `payment_id` VARCHAR(100) DEFAULT NULL COMMENT '결제 ID (외부 결제 시스템)',
    `status` ENUM('pending', 'approved', 'active', 'expired', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending' COMMENT '광고 상태',
    `start_date` DATE DEFAULT NULL COMMENT '광고 시작일',
    `end_date` DATE DEFAULT NULL COMMENT '광고 종료일',
    `rejected_reason` TEXT DEFAULT NULL COMMENT '거부 사유',
    `admin_id` VARCHAR(50) DEFAULT NULL COMMENT '처리한 관리자 ID',
    `approved_at` DATETIME DEFAULT NULL COMMENT '승인일시',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_product_type` (`product_type`),
    KEY `idx_status` (`status`),
    KEY `idx_payment_status` (`payment_status`),
    KEY `idx_start_end_date` (`start_date`, `end_date`),
    CONSTRAINT `fk_advertisement_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 광고 신청';
```

**광고 상태 설명:**
- `pending`: 대기중 (결제 대기 또는 승인 대기)
- `approved`: 승인됨 (결제 완료 후 승인)
- `active`: 진행중 (광고 기간 내)
- `expired`: 종료됨 (광고 기간 만료)
- `rejected`: 거부됨
- `cancelled`: 취소됨

**결제 상태 설명:**
- `pending`: 결제 대기
- `paid`: 결제 완료
- `failed`: 결제 실패
- `refunded`: 환불됨

### 3. products 테이블에 광고 관련 컬럼 추가

현재 광고 중인 상품을 빠르게 식별하기 위한 컬럼을 추가합니다.

```sql
ALTER TABLE `products` 
ADD COLUMN `is_advertising` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '광고 진행 여부' AFTER `application_count`,
ADD COLUMN `advertisement_end_date` DATE DEFAULT NULL COMMENT '광고 종료일' AFTER `is_advertising`,
ADD KEY `idx_is_advertising` (`is_advertising`),
ADD KEY `idx_advertisement_end_date` (`advertisement_end_date`);
```

---

## 🔧 기능 명세

### 1. 판매자 기능

#### 1.1 광고 신청 페이지
**경로:** `/seller/products/advertisement/register.php`

**기능:**
- 자신이 등록한 상품 목록 조회 (카테고리별 필터링 가능)
- 광고할 상품 선택 (체크박스 또는 라디오 버튼)
- 광고 기간 선택 (일주일, 한달, 3개월, 6개월)
- 선택한 기간에 따른 금액 표시 (실시간 계산)
- 광고 신청 폼 제출

**UI 구성:**
```
┌─────────────────────────────────────────┐
│  상품 광고 신청                          │
├─────────────────────────────────────────┤
│  카테고리: [전체 ▼] [MVNO] [MNO] [Internet] │
│                                         │
│  ┌─────────────────────────────────┐   │
│  │ ☑ 상품명: KT 5G 슈퍼플랜        │   │
│  │   카테고리: MVNO                │   │
│  │   월요금: 55,000원             │   │
│  └─────────────────────────────────┘   │
│                                         │
│  ┌─────────────────────────────────┐   │
│  │ ☐ 상품명: iPhone 15 Pro         │   │
│  │   카테고리: MNO                 │   │
│  │   가격: 1,200,000원            │   │
│  └─────────────────────────────────┘   │
│                                         │
│  광고 기간: [일주일 ▼]                 │
│  광고 금액: 50,000원                   │
│                                         │
│  [광고 신청하기]                        │
└─────────────────────────────────────────┘
```

#### 1.2 광고 현황 조회 페이지
**경로:** `/seller/products/advertisement/list.php`

**기능:**
- 자신이 신청한 광고 목록 조회
- 광고 상태별 필터링 (전체, 대기중, 진행중, 종료, 거부)
- 광고 상세 정보 확인 (기간, 금액, 상태 등)
- 광고 연장 기능 (종료 전 연장 신청 가능)
- 광고 취소 기능 (대기중 또는 진행중인 광고만)

**UI 구성:**
```
┌─────────────────────────────────────────┐
│  내 광고 현황                           │
├─────────────────────────────────────────┤
│  상태: [전체 ▼] [진행중] [대기중] [종료]│
│                                         │
│  ┌─────────────────────────────────┐   │
│  │ 상태: [진행중] 🟢               │   │
│  │ 상품: KT 5G 슈퍼플랜            │   │
│  │ 기간: 2024-01-01 ~ 2024-01-31  │   │
│  │ 금액: 180,000원                │   │
│  │ [상세보기] [연장하기]           │   │
│  └─────────────────────────────────┘   │
│                                         │
│  ┌─────────────────────────────────┐   │
│  │ 상태: [대기중] 🟡               │   │
│  │ 상품: iPhone 15 Pro             │   │
│  │ 기간: 일주일                    │   │
│  │ 금액: 60,000원                 │   │
│  │ [상세보기] [취소하기]           │   │
│  └─────────────────────────────────┘   │
└─────────────────────────────────────────┘
```

#### 1.3 광고 상세 페이지
**경로:** `/seller/products/advertisement/detail.php?id={advertisement_id}`

**기능:**
- 광고 상세 정보 표시
- 결제 정보 확인
- 광고 효과 통계 (클릭 수, 조회 수 증가율 등 - 향후 확장)

#### 1.4 API: 광고 신청
**경로:** `/api/product-advertisement/register.php`

**요청 파라미터:**
```json
{
    "product_id": 123,
    "period_type": "month"
}
```

**응답:**
```json
{
    "success": true,
    "message": "광고 신청이 완료되었습니다.",
    "advertisement": {
        "id": 1,
        "product_id": 123,
        "period_type": "month",
        "period_days": 30,
        "advertisement_price": 180000,
        "status": "pending",
        "payment_status": "pending"
    }
}
```

#### 1.5 API: 광고 금액 조회
**경로:** `/api/product-advertisement/get-price.php`

**요청 파라미터:**
```json
{
    "product_type": "mvno",
    "period_type": "month"
}
```

**응답:**
```json
{
    "success": true,
    "price": 180000,
    "period_days": 30
}
```

### 2. 관리자 기능

#### 2.1 광고 가격 설정 페이지
**경로:** `/admin/products/advertisement/prices.php`

**기능:**
- 카테고리별 광고 기간별 금액 설정
- 가격 수정 및 저장
- 가격 변경 이력 관리 (선택사항)

**UI 구성:**
```
┌─────────────────────────────────────────┐
│  광고 가격 설정                         │
├─────────────────────────────────────────┤
│  카테고리: MVNO                         │
│  ┌─────────────────────────────────┐   │
│  │ 일주일 (7일): [50,000] 원       │   │
│  │ 한달 (30일): [180,000] 원       │   │
│  │ 3개월 (90일): [500,000] 원      │   │
│  │ 6개월 (180일): [900,000] 원     │   │
│  │ [저장]                          │   │
│  └─────────────────────────────────┘   │
│                                         │
│  카테고리: MNO                          │
│  ┌─────────────────────────────────┐   │
│  │ 일주일 (7일): [60,000] 원       │   │
│  │ 한달 (30일): [220,000] 원       │   │
│  │ 3개월 (90일): [600,000] 원      │   │
│  │ 6개월 (180일): [1,100,000] 원   │   │
│  │ [저장]                          │   │
│  └─────────────────────────────────┘   │
│                                         │
│  카테고리: Internet                     │
│  ┌─────────────────────────────────┐   │
│  │ 일주일 (7일): [55,000] 원       │   │
│  │ 한달 (30일): [200,000] 원       │   │
│  │ 3개월 (90일): [550,000] 원      │   │
│  │ 6개월 (180일): [1,000,000] 원   │   │
│  │ [저장]                          │   │
│  └─────────────────────────────────┘   │
└─────────────────────────────────────────┘
```

#### 2.2 광고 승인 관리 페이지
**경로:** `/admin/products/advertisement/manage.php`

**기능:**
- 광고 신청 목록 조회 (상태별 필터링)
- 광고 승인/거부 처리
- 거부 사유 입력
- 광고 상세 정보 확인

**UI 구성:**
```
┌─────────────────────────────────────────┐
│  광고 승인 관리                         │
├─────────────────────────────────────────┤
│  상태: [전체 ▼] [대기중] [승인] [거부] │
│                                         │
│  ┌─────────────────────────────────┐   │
│  │ 신청일: 2024-01-01              │   │
│  │ 판매자: seller123               │   │
│  │ 상품: KT 5G 슈퍼플랜            │   │
│  │ 기간: 한달 (30일)               │   │
│  │ 금액: 180,000원                │   │
│  │ 결제: 완료 ✅                   │   │
│  │ [승인] [거부] [상세보기]        │   │
│  └─────────────────────────────────┘   │
└─────────────────────────────────────────┘
```

#### 2.3 광고 통계 페이지
**경로:** `/admin/products/advertisement/statistics.php`

**기능:**
- 카테고리별 광고 현황 통계
- 기간별 광고 수익 통계
- 판매자별 광고 통계
- 광고 효과 분석 (향후 확장)

#### 2.4 API: 광고 가격 설정 저장
**경로:** `/admin/api/product-advertisement/prices.php`

**요청 파라미터:**
```json
{
    "product_type": "mvno",
    "prices": {
        "week": 50000,
        "month": 180000,
        "quarter": 500000,
        "half_year": 900000
    }
}
```

#### 2.5 API: 광고 승인/거부
**경로:** `/admin/api/product-advertisement/approve.php`

**요청 파라미터:**
```json
{
    "advertisement_id": 1,
    "action": "approve" // 또는 "reject"
}
```

### 3. 상품 목록 노출 개선

#### 3.1 광고 중인 상품 우선 노출
상품 목록 조회 시 광고 중인 상품을 우선적으로 노출합니다.

**쿼리 예시:**
```sql
SELECT p.*, 
       CASE p.product_type
           WHEN 'mvno' THEN mvno.plan_name
           WHEN 'mno' THEN mno.device_name
           WHEN 'internet' THEN inet.registration_place
       END AS product_name
FROM products p
LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id AND p.product_type = 'mvno'
LEFT JOIN product_mno_details mno ON p.id = mno.product_id AND p.product_type = 'mno'
LEFT JOIN product_internet_details inet ON p.id = inet.product_id AND p.product_type = 'internet'
WHERE p.product_type = 'mvno' AND p.status = 'active'
ORDER BY 
    p.is_advertising DESC,  -- 광고 중인 상품 우선
    p.created_at DESC
LIMIT :limit OFFSET :offset;
```

#### 3.2 광고 뱃지 표시
광고 중인 상품에 "광고" 또는 "AD" 뱃지를 표시합니다.

---

## ⚙️ 시스템 로직

### 1. 광고 신청 프로세스

```
1. 판매자가 광고 신청
   └─> product_advertisements 테이블에 INSERT
       ├─> status: 'pending'
       ├─> payment_status: 'pending'
       └─> advertisement_price: 현재 가격 조회하여 저장

2. 결제 처리 (외부 결제 시스템 연동)
   └─> 결제 완료 시
       ├─> payment_status: 'paid'
       └─> payment_id 저장

3. 관리자 승인
   └─> status: 'approved'
       ├─> start_date: 현재 날짜
       ├─> end_date: start_date + period_days
       ├─> products.is_advertising: 1
       └─> products.advertisement_end_date: end_date

4. 광고 시작
   └─> status: 'active'
   └─> 상품 목록에 우선 노출

5. 광고 종료 (자동)
   └─> 매일 크론잡으로 체크
       ├─> end_date가 오늘보다 이전인 경우
       ├─> status: 'expired'
       ├─> products.is_advertising: 0
       └─> products.advertisement_end_date: NULL
```

### 2. 광고 종료 자동 처리

**크론잡 스크립트:** `/cron/expire-advertisements.php`

매일 자정에 실행하여 광고 기간이 만료된 광고를 자동으로 종료 처리합니다.

```php
// 의사코드
$expiredAdvertisements = SELECT * FROM product_advertisements 
WHERE status = 'active' AND end_date < CURDATE();

foreach ($expiredAdvertisements as $ad) {
    UPDATE product_advertisements SET status = 'expired' WHERE id = $ad->id;
    UPDATE products SET is_advertising = 0, advertisement_end_date = NULL 
    WHERE id = $ad->product_id;
}
```

### 3. 광고 중복 방지

같은 상품에 대해 동시에 여러 광고가 진행되지 않도록 체크합니다.

- 광고 신청 시 해당 상품의 `status = 'active'`인 광고가 있는지 확인
- 있다면 신청 거부 또는 기존 광고 종료 후 새 광고 시작 옵션 제공

---

## 📱 UI/UX 제안

### 1. 판매자 페이지

#### 1.1 마이페이지 메뉴 추가
- 판매자 메뉴에 "상품 광고" 섹션 추가
  - 광고 신청
  - 광고 현황

#### 1.2 상품 목록 페이지에 광고 상태 표시
- 광고 중인 상품에 "광고중" 뱃지 표시
- 광고 종료 예정일 표시 (D-7, D-3 등)

#### 1.3 광고 신청 플로우
- 단계별 안내 (1. 상품 선택 → 2. 기간 선택 → 3. 결제 → 4. 신청 완료)
- 예상 금액 미리보기
- 결제 전 확인 페이지

### 2. 관리자 페이지

#### 2.1 대시보드 위젯
- 오늘 승인 대기 광고 수
- 진행 중인 광고 수
- 오늘 종료 예정 광고 수

#### 2.2 알림 기능
- 승인 대기 광고 알림
- 광고 종료 예정 알림

---

## 🔐 보안 고려사항

1. **권한 체크**
   - 판매자는 자신의 상품만 광고 신청 가능
   - 관리자만 가격 설정 및 승인 가능

2. **결제 보안**
   - 결제 정보 암호화 저장
   - 결제 검증 (외부 결제 시스템과의 연동)

3. **데이터 무결성**
   - 외래키 제약조건 활용
   - 트랜잭션 처리 (광고 승인 시)

---

## 📊 확장 가능성

### 1. 광고 효과 분석
- 클릭 수 추적
- 조회 수 증가율
- 신청 수 증가율

### 2. 광고 위치 설정
- 메인 페이지 배너 광고
- 상세 페이지 내 광고
- 목록 페이지 상단 고정

### 3. 광고 할인 쿠폰
- 프로모션 기간 할인
- 장기 광고 할인
- 첫 광고 할인

### 4. 광고 자동 연장
- 종료 전 자동 연장 옵션
- 자동 결제 설정

---

## 🚀 구현 단계

### Phase 1: 기본 기능 구현
1. 데이터베이스 테이블 생성
2. 관리자 가격 설정 기능
3. 판매자 광고 신청 기능
4. 광고 신청 목록 조회

### Phase 2: 승인 프로세스
1. 관리자 승인 기능
2. 광고 상태 업데이트
3. 상품 목록 우선 노출

### Phase 3: 자동화
1. 광고 종료 자동 처리 (크론잡)
2. 알림 기능
3. 통계 기능

### Phase 4: 고도화
1. 결제 시스템 연동
2. 광고 효과 분석
3. 광고 위치 다양화

---

## 💡 추가 고려사항

### 1. 광고 기간 계산
- 일주일 = 7일 (정확히 7일)
- 한달 = 30일 (월별 일수가 다를 수 있으므로 고정값 사용)
- 3개월 = 90일
- 6개월 = 180일

### 2. 광고 연장
- 기존 광고 종료 전 연장 신청 가능
- 연장 시 기존 광고 종료일 다음날부터 새 광고 시작

### 3. 광고 취소 및 환불
- 대기중인 광고는 취소 가능
- 진행중인 광고는 부분 환불 정책 필요
- 거부된 광고는 전액 환불

### 4. 광고 금액 변경
- 관리자가 가격을 변경해도 이미 신청한 광고는 변경 전 가격 적용
- `advertisement_price` 컬럼에 신청 시점 가격 저장

---

## 📝 참고사항

- 이 제안서는 기본 구조를 제안하는 것이며, 실제 구현 시 요구사항에 맞게 조정이 필요합니다.
- 결제 시스템은 외부 API 연동이 필요하며, 별도 구현이 필요합니다.
- 크론잡 설정은 서버 환경에 맞게 구성해야 합니다 (Windows 작업 스케줄러 또는 Linux cron).

# 통신사단독유심 주문 관리 페이지 문제 설명

## 🔍 문제 현상

1. **알뜰폰 내용이 표시됨**: 통신사단독유심 주문 관리 페이지에서 상품명이 "알뜰폰"으로 표시됨
2. **주문이 이상함**: 통신사단독유심 주문건만 보여야 하는데 다른 타입의 주문이 섞여 나옴

## 🔎 원인 분석

### 1. DB 구조 문제

**`product_applications` 테이블의 `product_type` ENUM에 `'mno-sim'`이 없음**

```sql
-- 현재 상태 (문제)
product_type ENUM('mvno', 'mno', 'internet') NOT NULL

-- 올바른 상태
product_type ENUM('mvno', 'mno', 'internet', 'mno-sim') NOT NULL
```

**영향:**
- 통신사단독유심 주문 저장 시 `product_type`이 `'mno-sim'`으로 저장되지 못함
- 다른 타입(예: 'mvno')으로 잘못 저장되거나 오류 발생 가능
- 주문 조회 시 필터링이 제대로 작동하지 않음

### 2. 주문 저장 시 product_snapshot 문제

**저장 로직 (`api/submit-mno-sim-application.php`):**

```php
// 상품 정보 전체를 배열로 구성
$productSnapshot = [];
foreach ($product as $key => $value) {
    if ($key !== 'seller_id' && $key !== 'product_id' && $key !== 'id') {
        $productSnapshot[$key] = $value;
    }
}
```

**문제점:**
- `product_mno_sim_details` 테이블에서 가져온 데이터가 `product_snapshot`에 저장됨
- 하지만 JOIN 쿼리에서 다른 테이블의 데이터가 섞일 수 있음
- 특히 `product_mvno_details`와 필드명이 유사하여 혼동 가능

### 3. 주문 조회 쿼리 문제

**현재 쿼리 (`seller/orders/mno-sim.php`):**

```php
// WHERE 조건
$whereConditions = [
    'a.seller_id = :seller_id',
    "a.product_type = 'mno-sim'",  // ❌ ENUM에 없으면 작동 안 함
    "p.product_type = 'mno-sim'"
];
```

**문제점:**
- `product_applications.product_type`이 `'mno-sim'`이 아니면 필터링 실패
- `products.product_type`만 확인하면 잘못 저장된 주문도 조회됨

## 💡 해결 방안

### 1. DB 스키마 수정 (필수)

```sql
ALTER TABLE `product_applications` 
MODIFY COLUMN `product_type` 
ENUM('mvno', 'mno', 'internet', 'mno-sim') 
NOT NULL COMMENT '상품 타입';
```

**확인 방법:**
- `check-mno-sim-orders-db.php` 파일 실행
- 또는 직접 SQL 실행

### 2. 주문 저장 로직 개선

**`api/submit-mno-sim-application.php` 수정:**

```php
// 상품 정보 가져오기 - mno-sim만 명확히 조회
$stmt = $pdo->prepare("
    SELECT p.seller_id, mno_sim.*
    FROM products p
    INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
    WHERE p.id = ? 
    AND p.product_type = 'mno-sim' 
    AND p.status = 'active'
    LIMIT 1
");

// product_snapshot 구성 시 mno-sim 필드만 포함
$productSnapshot = [];
$mnoSimFields = [
    'provider', 'service_type', 'plan_name', 'contract_period',
    'price_main', 'price_after', 'data_amount', 'call_type', 
    // ... mno-sim 관련 필드만
];

foreach ($product as $key => $value) {
    if (in_array($key, $mnoSimFields) || strpos($key, 'mno_sim_') === 0) {
        $productSnapshot[$key] = $value;
    }
}
```

### 3. 주문 조회 쿼리 개선

**`seller/orders/mno-sim.php` 수정:**

```php
// products 테이블과 mno_sim_details 조인으로 확실히 필터링
$sql = "
    SELECT DISTINCT
        a.id as application_id,
        a.order_number,
        mno_sim.plan_name,
        mno_sim.provider,
        ...
    FROM product_applications a
    INNER JOIN application_customers c ON a.id = c.application_id
    INNER JOIN products p ON a.product_id = p.id AND p.product_type = 'mno-sim'
    INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
    WHERE a.seller_id = :seller_id
    AND (a.product_type = 'mno-sim' OR p.product_type = 'mno-sim')
    ORDER BY a.created_at DESC
";
```

### 4. 상품명 표시 로직 개선

**이미 수정 완료:**
- `plan_name`이 "알뜰폰"이거나 비어있으면 `provider + " 통신사단독유심"`으로 표시
- `plan_name`에 `provider`가 없으면 앞에 추가

## 📋 저장 구조 설명

### 정상적인 저장 구조

```
product_applications 테이블:
├── id: 주문 ID
├── product_id: 상품 ID
├── seller_id: 판매자 ID
├── product_type: 'mno-sim' ✅ (ENUM에 포함되어야 함)
└── ...

application_customers 테이블:
├── application_id: 주문 ID (FK)
├── user_id: 고객 ID
├── name: 고객명
├── phone: 전화번호
└── additional_info (JSON):
    ├── subscription_type: 'new' | 'mnp' | 'change'
    └── product_snapshot: {
        ├── provider: 'KT' | 'SKT' | 'LG U+'
        ├── plan_name: '요금제명'
        ├── service_type: 'LTE' | '5G'
        ├── price_main: 10000
        ├── data_amount: '무제한'
        └── ... (mno-sim 관련 필드만)
    }
```

### 문제가 있는 경우

**문제 1: product_type이 잘못 저장됨**
```
product_applications.product_type = 'mvno' ❌ (mno-sim이어야 함)
→ 주문 조회 시 필터링 실패
```

**문제 2: product_snapshot에 알뜰폰 데이터가 섞임**
```
product_snapshot: {
    plan_name: '알뜰폰',  ❌ (mno-sim 데이터가 아님)
    provider: '알뜰폰 통신사',
    ...
}
→ 상품명이 "알뜰폰"으로 표시됨
```

## ✅ 해결 체크리스트

- [ ] 1. DB 스키마 수정: `product_applications.product_type` ENUM에 'mno-sim' 추가
- [ ] 2. 주문 저장 로직 확인: `product_snapshot`에 mno-sim 데이터만 저장되는지 확인
- [ ] 3. 주문 조회 쿼리 개선: mno-sim만 확실히 필터링
- [ ] 4. 기존 잘못 저장된 주문 데이터 수정 (필요시)

## 🔧 실행 방법

1. **DB 확인 및 수정:**
   ```
   http://localhost/MVNO/check-mno-sim-orders-db.php
   ```

2. **자동 수정 실행:**
   ```
   http://localhost/MVNO/check-mno-sim-orders-db.php?fix=1
   ```

3. **주문 관리 페이지 확인:**
   ```
   http://localhost/MVNO/seller/orders/mno-sim.php
   ```

## 📝 참고

- `product_snapshot`은 **신청 시점의 상품 정보**를 저장하는 용도
- 분쟁 발생 시 확인용으로 사용되므로 **정확한 데이터**가 저장되어야 함
- 각 상품 타입(mvno, mno, mno-sim, internet)별로 **분리된 필드**만 저장되어야 함






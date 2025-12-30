# 입찰 게시물 표시 방식 설계

## 📌 개요

각 카테고리별 상단 영역에 입찰 게시물을 표시하는 방식은 관리자가 선택할 수 있으며, 입찰 건수가 20개 미만인 경우에도 정상적으로 작동합니다.

---

## 🎯 운용 방식

### 1. 고정 모드 (rotation_type = 'fixed')

#### 설정 조건
- 관리자가 입찰 라운드 생성 시 `rotation_type = 'fixed'` 선택
- `min_bid_amount != max_bid_amount` (금액 차등 허용)
- 예: 최저금액 10,000원, 최대금액 100,000원

#### 동작 방식
- 입찰 금액 내림차순으로 고정 배치
- 게시 기간 동안 순서 변경 없음
- 입찰 금액이 높을수록 상위 노출

#### 예시
```
1위: 입찰 금액 100,000원 (판매자A)
2위: 입찰 금액 95,000원 (판매자B)
3위: 입찰 금액 90,000원 (판매자C)
...
20위: 입찰 금액 50,000원 (판매자T)
```

#### 장점
- 입찰 금액이 높은 판매자가 항상 상위 노출
- 공정한 경쟁 구조
- 예측 가능한 노출 순서

---

### 2. 로테이션 모드 (rotation_type = 'rotating')

#### 설정 조건
- 관리자가 입찰 라운드 생성 시 `rotation_type = 'rotating'` 선택
- 입찰 금액 설정 (단일 금액, 예: 50,000원)
- 내부적으로 `min_bid_amount = max_bid_amount = 설정한 금액`으로 저장
- `rotation_interval_minutes` 설정 (예: 10분, 60분 등)
- 판매자는 입찰 금액을 입력할 필요 없음 (자동으로 고정 금액 적용)

#### 동작 방식
- 설정된 시간 간격마다 순서 1칸씩 순환
- 1등→2등, 2등→3등, ..., N등→1등 (N = 실제 낙찰 건수)
- 각 낙찰자가 공평하게 상위 노출 기회 제공

#### 예시 (10분 간격, 7개 낙찰)

**10:00**
```
1위: 판매자A
2위: 판매자B
3위: 판매자C
4위: 판매자D
5위: 판매자E
6위: 판매자F
7위: 판매자G
8위부터: 일반 게시물 (등록 순서)
```

**10:10 (10분 후)**
```
1위: 판매자B (2등 → 1등)
2위: 판매자C (3등 → 2등)
3위: 판매자D (4등 → 3등)
4위: 판매자E (5등 → 4등)
5위: 판매자F (6등 → 5등)
6위: 판매자G (7등 → 6등)
7위: 판매자A (1등 → 7등, 순환)
8위부터: 일반 게시물 (등록 순서)
```

**10:20 (20분 후)**
```
1위: 판매자C
2위: 판매자D
3위: 판매자E
4위: 판매자F
5위: 판매자G
6위: 판매자A
7위: 판매자B
8위부터: 일반 게시물 (등록 순서)
```

#### 장점
- 모든 낙찰자가 공평하게 상위 노출
- 동일 금액 입찰 시 공정한 운영
- 순환 간격은 관리자가 설정 가능 (10분, 1시간 등)

---

## 📊 입찰 건수가 20개 미만인 경우

### 핵심 원칙

**상위 20개 한도 내에서 실제 낙찰 건수만큼만 표시**

### 예시 시나리오

#### 시나리오 1: 입찰 낙찰 건수 7개

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

#### 시나리오 2: 입찰 낙찰 건수 20개

```
상단 영역 (입찰 게시물):
1위: 입찰 게시물 #1
2위: 입찰 게시물 #2
...
20위: 입찰 게시물 #20

21위부터 (일반 게시물):
21위: 일반 게시물 (등록 순서)
22위: 일반 게시물 (등록 순서)
...
```

---

## 🔄 게시물 목록 쿼리 로직

### 1. 입찰 게시물 조회

```sql
-- 실제 낙찰된 입찰 게시물만 조회 (최대 20개)
SELECT 
    p.*,
    bpa.display_order,
    bpa.bid_amount,
    bp.seller_id as bidder_id
FROM products p
INNER JOIN bidding_product_assignments bpa ON p.id = bpa.product_id
INNER JOIN bidding_participations bp ON bpa.bidding_participation_id = bp.id
WHERE bpa.bidding_round_id = :round_id
  AND bp.status = 'won'
  AND bpa.bidding_round_id IN (
      SELECT id FROM bidding_rounds 
      WHERE category = :category 
        AND status = 'displaying'
        AND display_start_at <= NOW()
        AND display_end_at >= NOW()
  )
ORDER BY 
    CASE WHEN :rotation_type = 'rotating' THEN 
        -- 로테이션 모드: last_rotated_at 기준으로 순환 순서 계산
        (display_order + TIMESTAMPDIFF(MINUTE, last_rotated_at, NOW()) / :rotation_interval) MOD (COUNT(*) OVER())
    ELSE 
        -- 고정 모드: display_order 기준
        display_order
    END ASC
```

### 2. 일반 게시물 조회

```sql
-- 입찰 게시물이 아닌 일반 게시물
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
```

### 3. 통합 조회 (PHP 로직)

```php
// 1. 입찰 게시물 조회 (실제 낙찰 개수만큼)
$biddingProducts = getBiddingProducts($category, $roundId, $rotationType);
$biddingCount = count($biddingProducts); // 예: 7개

// 2. 일반 게시물 조회
$normalProducts = getNormalProducts($category, $biddingRoundIds, $offset, $limit);

// 3. 통합
$allProducts = array_merge($biddingProducts, $normalProducts);
```

---

## ⚙️ 관리자 설정

### 입찰 라운드 생성 시 설정 항목

```
운용 방식 선택:
☐ 고정 모드 (입찰 금액별 차등 순서 고정)
  → 최저금액과 최대금액을 다르게 설정
  
☐ 로테이션 모드 (금액 동일 시 순서 로테이션)
  → 최저금액 = 최대금액으로 설정
  → 순환 간격: [10분 / 1시간 / 6시간 / 12시간 / 24시간] 선택
```

### 설정 예시

#### 고정 모드 설정
```
카테고리: 알뜰폰 (MVNO)
운용 방식: 고정 모드
최소 입찰 금액: 10,000원
최대 입찰 금액: 100,000원
→ 입찰 금액 내림차순으로 고정 배치
```

#### 로테이션 모드 설정
```
카테고리: 알뜰폰 (MVNO)
운용 방식: 로테이션 모드
입찰 금액: 50,000원 (단일 금액 설정)
순환 간격: 10분
→ 모든 입찰자 자동으로 50,000원 입찰 (판매자 입력 불필요)
→ 입찰 시간에 따라 초기 순위 결정
→ 10분마다 순서 순환
```

---

## 📋 데이터베이스 구조

### bidding_rounds 테이블 (기존 필드)

```sql
rotation_type ENUM('fixed', 'rotating') NOT NULL DEFAULT 'fixed'
rotation_interval_minutes INT(11) UNSIGNED DEFAULT NULL
min_bid_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00
max_bid_amount DECIMAL(12,2) NOT NULL DEFAULT 100000.00
```

### bidding_product_assignments 테이블 (기존 필드)

```sql
display_order INT(11) UNSIGNED NOT NULL COMMENT '노출 순서 (1~20)'
last_rotated_at DATETIME DEFAULT NULL COMMENT '마지막 순환 시간'
```

---

## 🔄 로테이션 모드 구현 로직

### 순환 알고리즘

```php
function rotateBiddingProducts($roundId, $rotationIntervalMinutes) {
    // 1. 해당 라운드의 입찰 게시물 조회
    $products = getBiddingAssignments($roundId);
    $count = count($products);
    
    if ($count <= 1) {
        return; // 1개 이하면 순환 불필요
    }
    
    // 2. 마지막 순환 시간 확인
    $lastRotatedAt = $products[0]['last_rotated_at'];
    $now = new DateTime();
    $lastRotated = new DateTime($lastRotatedAt);
    
    // 3. 순환 필요 횟수 계산
    $minutesDiff = ($now->getTimestamp() - $lastRotated->getTimestamp()) / 60;
    $rotateCount = floor($minutesDiff / $rotationIntervalMinutes);
    
    if ($rotateCount == 0) {
        return; // 아직 순환 시간이 안 됨
    }
    
    // 4. display_order 순환 (1칸씩)
    foreach ($products as $index => $product) {
        $newOrder = (($product['display_order'] - 1 + $rotateCount) % $count) + 1;
        
        updateBiddingAssignmentOrder(
            $product['id'], 
            $newOrder, 
            $now->format('Y-m-d H:i:s')
        );
    }
}
```

---

## 📝 요약

1. **운용 방식 선택 가능**
   - 고정 모드: 입찰 금액별 차등 순서 고정
   - 로테이션 모드: 금액 동일 시 순서 로테이션 (관리자 설정 간격)

2. **입찰 건수 유연성**
   - 상위 20개 한도 내에서 실제 낙찰 건수만큼만 표시
   - 입찰 건수가 7개인 경우 → 상단 7개만 입찰 게시물
   - 나머지는 일반 게시물(등록 순서)로 표시

3. **일반 게시물 표시**
   - 입찰 게시물 다음부터 일반 게시물 표시
   - 등록 순서(created_at DESC) 기준 정렬
   - 기존 카테고리 카드 운영 형태와 동일


# 리뷰 통계 시스템 설명 및 해결책

## 🔍 현재 문제 분석

### 문제 상황
리뷰를 작성할 때마다 **모든 리뷰의 평균값**이 표시되어야 하는데, 현재는 첫 번째 리뷰 값만 반영되는 것처럼 보입니다.

### 현재 시스템 동작

#### 1. 리뷰 작성 시 (INSERT)
```sql
-- 트리거: trg_update_review_statistics_on_insert
IF NEW.status = 'approved' THEN
    INSERT INTO product_review_statistics 
        (product_id, total_rating_sum, total_review_count)
    VALUES (NEW.product_id, NEW.rating, 1)
    ON DUPLICATE KEY UPDATE
        total_rating_sum = total_rating_sum + NEW.rating,  -- ✅ 누적
        total_review_count = total_review_count + 1;        -- ✅ 증가
END IF;
```

**동작 예시:**
- 리뷰 1개 (rating=5): `total_rating_sum = 5`, `count = 1` → 평균 = 5.0
- 리뷰 2개 (rating=4): `total_rating_sum = 5 + 4 = 9`, `count = 2` → 평균 = 4.5
- 리뷰 3개 (rating=3): `total_rating_sum = 9 + 3 = 12`, `count = 3` → 평균 = 4.0

**✅ 이론적으로는 올바르게 작동해야 합니다.**

#### 2. 평균 계산 (조회 시)
```php
// includes/data/plan-data.php - getProductAverageRating()
SELECT 
    CASE 
        WHEN total_review_count > 0 THEN CEIL((total_rating_sum / total_review_count) * 10) / 10
        ELSE 0
    END AS average_rating
FROM product_review_statistics
WHERE product_id = :product_id
```

**✅ 평균 계산도 올바릅니다: `총합 / 개수`**

---

## ⚠️ 가능한 문제점

### 문제 1: 트리거가 실행되지 않음
**원인:**
- 리뷰가 `status = 'pending'`으로 저장됨 (MNO의 경우)
- 트리거는 `status = 'approved'`일 때만 실행
- pending → approved 변경 시 UPDATE 트리거가 실행되어야 함

**확인 방법:**
```sql
-- 트리거 존재 확인
SHOW TRIGGERS LIKE 'trg_update_review_statistics%';

-- 통계 테이블 확인
SELECT * FROM product_review_statistics WHERE product_id = ?;

-- 실제 리뷰 개수와 통계 비교
SELECT 
    COUNT(*) as actual_count,
    SUM(rating) as actual_sum
FROM product_reviews 
WHERE product_id = ? AND status = 'approved';

SELECT 
    total_review_count as stats_count,
    total_rating_sum as stats_sum
FROM product_review_statistics 
WHERE product_id = ?;
```

### 문제 2: 트리거가 중복 실행되지 않음
**원인:**
- 첫 번째 리뷰: INSERT → 트리거 실행 → 통계 생성
- 두 번째 리뷰: INSERT → 트리거 실행 → `ON DUPLICATE KEY UPDATE`로 누적
- 하지만 트리거가 실패하거나 실행되지 않으면 누적되지 않음

### 문제 3: 통계 테이블이 초기화됨
**원인:**
- 다른 스크립트가 통계를 재계산하면서 초기화
- 트리거와 함수가 동시에 실행되어 충돌

---

## ✅ 해결책

### 해결책 1: 트리거 확인 및 수정 (즉시 적용)

#### 1-1. 트리거 존재 확인
```sql
SELECT * FROM information_schema.TRIGGERS 
WHERE TRIGGER_NAME LIKE 'trg_update_review_statistics%';
```

#### 1-2. 트리거가 없으면 생성
`add-review-statistics-triggers.php` 실행

#### 1-3. 트리거 로직 확인
INSERT 트리거가 올바르게 누적하는지 확인:
```sql
-- 올바른 로직 (현재)
ON DUPLICATE KEY UPDATE
    total_rating_sum = total_rating_sum + NEW.rating,  -- 누적
    total_review_count = total_review_count + 1;       -- 증가

-- 잘못된 로직 (이렇게 되어 있으면 문제)
ON DUPLICATE KEY UPDATE
    total_rating_sum = NEW.rating,      -- ❌ 덮어쓰기 (누적 안 됨)
    total_review_count = 1;             -- ❌ 덮어쓰기 (증가 안 됨)
```

### 해결책 2: 통계 정합성 검증 및 재계산

#### 2-1. 통계 검증 스크립트 생성
```php
// verify-review-statistics.php
// 실제 리뷰 데이터와 통계 테이블 비교
```

#### 2-2. 불일치 시 재계산
```php
// 모든 상품의 통계를 실제 리뷰 데이터로 재계산
updateReviewStatistics($productId, null, null, null, $productType);
```

### 해결책 3: 리뷰 상태 변경 시 통계 업데이트

#### 3-1. UPDATE 트리거 확인
pending → approved 변경 시 통계에 추가되어야 함:
```sql
-- UPDATE 트리거에서
IF OLD.status != 'approved' AND NEW.status = 'approved' THEN
    -- 통계에 추가 (INSERT 트리거와 동일한 로직)
END IF;
```

---

## 🔧 즉시 적용 가능한 해결책

### 방법 1: 통계 검증 및 재계산 스크립트

모든 상품의 통계를 실제 리뷰 데이터로 재계산하여 정합성 확보:

```php
// 모든 상품의 통계 재계산
$products = getProductsWithReviews();
foreach ($products as $product) {
    updateReviewStatistics($product['id'], null, null, null, $product['type']);
}
```

### 방법 2: 트리거 강화

INSERT 트리거에 안전장치 추가:
```sql
CREATE TRIGGER `trg_update_review_statistics_on_insert`
AFTER INSERT ON `product_reviews`
FOR EACH ROW
BEGIN
    IF NEW.status = 'approved' THEN
        -- 통계 테이블이 없으면 생성, 있으면 누적
        INSERT INTO `product_review_statistics` 
            (`product_id`, `total_rating_sum`, `total_review_count`)
        VALUES (NEW.product_id, NEW.rating, 1)
        ON DUPLICATE KEY UPDATE
            `total_rating_sum` = `total_rating_sum` + NEW.rating,
            `total_review_count` = `total_review_count` + 1,
            `updated_at` = NOW();
    END IF;
END;
```

### 방법 3: 조회 시 실시간 계산 (폴백)

통계 테이블이 없거나 불일치하면 실제 리뷰에서 계산:
```php
// 이미 구현되어 있음 (getProductAverageRating 함수)
// 통계 테이블 우선 조회 → 없으면 실제 리뷰에서 계산
```

---

## 📊 예상 동작 시나리오

### 시나리오 1: 정상 동작 (이상적)
```
리뷰 1 작성 (rating=5, status=approved)
→ 트리거 실행: total_rating_sum = 5, count = 1
→ 평균 = 5.0

리뷰 2 작성 (rating=4, status=approved)
→ 트리거 실행: total_rating_sum = 5 + 4 = 9, count = 2
→ 평균 = 4.5

리뷰 3 작성 (rating=3, status=approved)
→ 트리거 실행: total_rating_sum = 9 + 3 = 12, count = 3
→ 평균 = 4.0
```

### 시나리오 2: 문제 발생 (현재 상황)
```
리뷰 1 작성 (rating=5, status=approved)
→ 트리거 실행: total_rating_sum = 5, count = 1
→ 평균 = 5.0

리뷰 2 작성 (rating=4, status=approved)
→ 트리거 실행 안 됨 또는 실패
→ total_rating_sum = 5 (그대로), count = 1
→ 평균 = 5.0 (❌ 잘못됨)
```

---

## 🎯 권장 해결 순서

### 1단계: 트리거 확인
```sql
-- 트리거 존재 확인
SHOW TRIGGERS LIKE 'trg_update_review_statistics%';

-- 트리거 로직 확인
SHOW CREATE TRIGGER trg_update_review_statistics_on_insert;
```

### 2단계: 통계 검증
```sql
-- 실제 리뷰와 통계 비교
SELECT 
    p.id,
    COUNT(r.id) as actual_count,
    SUM(r.rating) as actual_sum,
    AVG(r.rating) as actual_avg,
    s.total_review_count as stats_count,
    s.total_rating_sum as stats_sum,
    (s.total_rating_sum / s.total_review_count) as stats_avg
FROM products p
LEFT JOIN product_reviews r ON p.id = r.product_id AND r.status = 'approved'
LEFT JOIN product_review_statistics s ON p.id = s.product_id
WHERE p.product_type IN ('mvno', 'mno', 'internet')
GROUP BY p.id
HAVING actual_count != stats_count OR actual_sum != stats_sum;
```

### 3단계: 통계 재계산
불일치가 발견되면 모든 상품의 통계를 재계산

### 4단계: 트리거 수정/재생성
트리거가 없거나 잘못되어 있으면 재생성

---

## 💡 핵심 포인트

1. **트리거는 누적 방식으로 작동해야 함**
   - `total_rating_sum = total_rating_sum + NEW.rating` ✅
   - `total_rating_sum = NEW.rating` ❌

2. **모든 리뷰의 평균 = 총합 / 개수**
   - `평균 = total_rating_sum / total_review_count`

3. **트리거가 실행되지 않으면 통계가 업데이트되지 않음**
   - 트리거 존재 확인 필수
   - 트리거 실행 로그 확인

4. **pending → approved 변경 시 UPDATE 트리거 필요**
   - UPDATE 트리거가 pending → approved 변경을 감지하여 통계 추가

---

## 🔍 디버깅 방법

### 1. 트리거 실행 확인
```sql
-- MySQL 일반 로그 활성화 (임시)
SET GLOBAL general_log = 'ON';
SET GLOBAL log_output = 'TABLE';

-- 리뷰 작성 후 로그 확인
SELECT * FROM mysql.general_log 
WHERE argument LIKE '%product_review_statistics%' 
ORDER BY event_time DESC LIMIT 10;
```

### 2. 통계 테이블 직접 확인
```sql
-- 특정 상품의 통계 확인
SELECT * FROM product_review_statistics WHERE product_id = ?;

-- 실제 리뷰와 비교
SELECT 
    COUNT(*) as review_count,
    SUM(rating) as rating_sum,
    AVG(rating) as rating_avg
FROM product_reviews 
WHERE product_id = ? AND status = 'approved';
```

### 3. 트리거 테스트
```sql
-- 테스트 리뷰 작성
INSERT INTO product_reviews (product_id, user_id, product_type, rating, content, status)
VALUES (1, 'test', 'mvno', 5, '테스트', 'approved');

-- 통계 확인
SELECT * FROM product_review_statistics WHERE product_id = 1;

-- 또 다른 리뷰 작성
INSERT INTO product_reviews (product_id, user_id, product_type, rating, content, status)
VALUES (1, 'test2', 'mvno', 4, '테스트2', 'approved');

-- 통계 다시 확인 (누적되었는지)
SELECT * FROM product_review_statistics WHERE product_id = 1;
-- 예상: total_rating_sum = 9, total_review_count = 2
```

---

## ✅ 최종 해결책 요약

**문제:** 리뷰를 여러 개 작성해도 평균이 첫 번째 리뷰 값만 반영됨

**원인:**
1. 트리거가 없거나 실행되지 않음
2. 트리거 로직이 누적이 아닌 덮어쓰기로 되어 있음
3. 통계 테이블이 초기화됨

**해결:**
1. 트리거 확인 및 재생성
2. 통계 검증 및 재계산
3. 트리거 로직 확인 (누적 방식인지)

**확인 방법:**
- 트리거 존재 여부 확인
- 실제 리뷰 데이터와 통계 테이블 비교
- 테스트 리뷰 작성 후 통계 누적 확인





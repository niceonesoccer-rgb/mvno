# 리뷰 통계 필드 설명

## 📊 통계 테이블 필드 용도

### 1. `total_rating_sum` (통계 합계)
**용도:** 모든 리뷰의 별점을 누적한 합계

**사용 예시:**
```
리뷰 1: 1점
리뷰 2: 4점
리뷰 3: 3점

total_rating_sum = 1 + 4 + 3 = 8
```

**왜 필요한가?**
- **증분 업데이트**: 리뷰를 추가/삭제할 때 전체 리뷰를 다시 계산하지 않고 합계만 더하거나 빼면 됨
- **성능 최적화**: 리뷰가 1000개여도 합계만 업데이트하면 되므로 빠름

**트리거에서 사용:**
```sql
-- 리뷰 추가 시
total_rating_sum = total_rating_sum + NEW.rating  -- 합계에 더하기

-- 리뷰 삭제 시
total_rating_sum = total_rating_sum - OLD.rating  -- 합계에서 빼기
```

---

### 2. `total_review_count` (통계 리뷰 수)
**용도:** 승인된 리뷰의 개수

**사용 예시:**
```
리뷰 1: approved
리뷰 2: approved
리뷰 3: pending (승인 대기)

total_review_count = 2  (approved만 카운트)
```

**왜 필요한가?**
- **평균 계산**: 합계를 개수로 나누어 평균 계산
- **리뷰 개수 표시**: "총 2개" 같은 표시에 사용

**트리거에서 사용:**
```sql
-- 리뷰 추가 시
total_review_count = total_review_count + 1  -- 개수 증가

-- 리뷰 삭제 시
total_review_count = total_review_count - 1  -- 개수 감소
```

---

### 3. **통계 평균** (계산값, 저장 안 함)
**용도:** 화면에 표시되는 평균값

**계산 공식:**
```sql
평균 = total_rating_sum / total_review_count
```

**사용 예시:**
```
total_rating_sum = 8
total_review_count = 3

평균 = 8 / 3 = 2.7
```

**왜 저장하지 않는가?**
- **데이터 정합성**: 합계와 개수만 저장하면 평균은 항상 정확하게 계산 가능
- **저장 공간 절약**: 평균을 저장하면 합계/개수와 불일치할 수 있음
- **실시간 계산**: 항상 최신 평균값 보장

**코드에서 사용:**
```php
// includes/data/plan-data.php - getProductAverageRating()
SELECT 
    ROUND((total_rating_sum / total_review_count), 1) AS average_rating
FROM product_review_statistics
WHERE product_id = :product_id
```

---

## 🔄 실제 동작 예시

### 시나리오: 리뷰 2개 (1점, 4점)

#### 1. 리뷰 1 작성 (1점)
```sql
INSERT INTO product_reviews (rating=1, status='approved')
→ 트리거 실행:
   total_rating_sum = 0 + 1 = 1
   total_review_count = 0 + 1 = 1
```

**통계 테이블:**
- `total_rating_sum = 1`
- `total_review_count = 1`
- **평균 = 1 / 1 = 1.0** ✅

---

#### 2. 리뷰 2 작성 (4점)
```sql
INSERT INTO product_reviews (rating=4, status='approved')
→ 트리거 실행:
   total_rating_sum = 1 + 4 = 5
   total_review_count = 1 + 1 = 2
```

**통계 테이블:**
- `total_rating_sum = 5`
- `total_review_count = 2`
- **평균 = 5 / 2 = 2.5** ✅

---

#### 3. 화면 표시
```php
$averageRating = getProductAverageRating($productId, 'mvno');
// SQL: SELECT ROUND((5 / 2), 1) = 2.5
// 화면에 "2.5" 표시
```

---

## 📋 필드별 역할 정리

| 필드 | 용도 | 저장 여부 | 예시 값 |
|------|------|----------|---------|
| **total_rating_sum** | 모든 리뷰 점수 합계 | ✅ 저장 | 5 (1+4) |
| **total_review_count** | 리뷰 개수 | ✅ 저장 | 2 |
| **평균** | 화면 표시용 | ❌ 계산 | 2.5 (5/2) |

---

## 💡 왜 이렇게 설계했나?

### 장점
1. **빠른 업데이트**: 리뷰 추가/삭제 시 합계만 더하거나 빼면 됨 (O(1))
2. **정확성**: 합계와 개수만 관리하면 평균은 항상 정확
3. **성능**: 리뷰가 1000개여도 합계만 업데이트하면 됨

### 대안 (비추천)
```sql
-- 평균을 직접 저장하는 경우 (문제 있음)
average_rating = 2.5  -- 저장

-- 문제점:
-- 1. 리뷰 추가 시 평균을 다시 계산해야 함
-- 2. 합계/개수와 불일치할 수 있음
-- 3. 정확성이 떨어짐
```

---

## 🎯 실제 사용 예시

### 화면에 표시되는 값
```php
// mvno-plan-detail.php
$averageRating = getProductAverageRating($plan_id, 'mvno');
// → SQL에서 계산: ROUND((total_rating_sum / total_review_count), 1)
// → 결과: 2.5
```

### 카테고리별 평균
```php
$categoryAverages = getInternetReviewCategoryAverages($plan_id, 'mvno');
// → kindness: ROUND((kindness_rating_sum / kindness_review_count), 1)
// → speed: ROUND((speed_rating_sum / speed_review_count), 1)
```

---

---

## 🔍 실제 평균 vs 통계 평균

### 차이점 설명

검증 스크립트(`rebuild-review-statistics-system.php`)에서 두 가지 평균을 비교합니다:

#### 1. **실제 평균 (Actual Average)**
**정의:** `product_reviews` 테이블에서 직접 계산한 평균

**계산 방법:**
```sql
-- 실제 리뷰 데이터에서 직접 계산
SELECT 
    AVG(rating) as actual_avg,
    SUM(rating) as actual_sum,
    COUNT(*) as actual_count
FROM product_reviews
WHERE product_id = 24 
  AND status = 'approved'
```

**예시:**
```
리뷰 1: 1점
리뷰 2: 4점
리뷰 3: 3점

실제 합계 = 1 + 4 + 3 = 8
실제 개수 = 3
실제 평균 = 8 / 3 = 2.7
```

**특징:**
- ✅ **항상 정확**: 실제 리뷰 데이터에서 계산
- ⚠️ **느림**: 리뷰가 많을수록 계산 시간 증가
- 📊 **검증용**: 통계 테이블이 정확한지 확인하는 기준

---

#### 2. **통계 평균 (Statistics Average)**
**정의:** `product_review_statistics` 테이블의 합계와 개수로 계산한 평균

**계산 방법:**
```sql
-- 통계 테이블에서 계산
SELECT 
    total_rating_sum / total_review_count as stats_avg,
    total_rating_sum as stats_sum,
    total_review_count as stats_count
FROM product_review_statistics
WHERE product_id = 24
```

**예시:**
```
통계 합계 = 8 (트리거가 누적한 값)
통계 개수 = 3 (트리거가 카운트한 값)
통계 평균 = 8 / 3 = 2.7
```

**특징:**
- ⚡ **빠름**: 합계와 개수만 나누면 됨 (O(1))
- ✅ **정확**: 트리거가 제대로 작동하면 실제 평균과 동일
- 🎯 **화면 표시용**: 실제로 화면에 표시되는 값

---

### 🔄 두 평균의 관계

#### 정상 상태 (일치)
```
실제 평균 = 통계 평균
예: 2.7 = 2.7 ✅
```

**이유:**
- 트리거가 리뷰 추가/삭제 시 통계 테이블을 정확히 업데이트
- 통계 합계 = 실제 합계
- 통계 개수 = 실제 개수

---

#### 비정상 상태 (불일치)
```
실제 평균 ≠ 통계 평균
예: 2.7 ≠ 1.0 ❌
```

**발생 원인:**
1. **트리거 미작동**
   - 트리거가 생성되지 않았거나 비활성화됨
   - 리뷰 추가 시 통계 테이블이 업데이트되지 않음

2. **트리거 오류**
   - 트리거 로직에 버그가 있음
   - 일부 리뷰가 통계에 반영되지 않음

3. **데이터 불일치**
   - 통계 테이블이 수동으로 수정됨
   - 리뷰 삭제 후 통계가 업데이트되지 않음

4. **초기화 문제**
   - 통계 테이블이 생성되기 전의 리뷰 데이터
   - 통계 재계산이 필요함

---

### 📊 검증 예시

검증 스크립트 결과:
```
상품 ID | 실제 평균 | 통계 평균 | 상태
--------|----------|----------|------
24      | 2.3      | 2.3      | ✅ 일치
43      | 2.5      | 2.5      | ✅ 일치
```

**일치하는 경우:**
- 트리거가 정상 작동
- 통계 테이블이 정확히 업데이트됨
- 화면에 표시되는 값이 정확함

**불일치하는 경우:**
- `rebuild-review-statistics-system.php` 실행 필요
- 통계 테이블 재생성 및 재계산 필요

---

### 🎯 실제 사용

#### 화면 표시 (통계 평균 사용)
```php
// mvno-plan-detail.php
$averageRating = getProductAverageRating($plan_id, 'mvno');
// → 통계 테이블에서 계산: ROUND((total_rating_sum / total_review_count), 1)
// → 빠르고 정확함 (트리거가 정상 작동 시)
```

#### 검증 (실제 평균 사용)
```php
// rebuild-review-statistics-system.php
$actualAvg = $actualSum / $actualCount;  // 실제 데이터에서 계산
$statsAvg = $statsSum / $statsCount;      // 통계 테이블에서 계산

if (abs($actualAvg - $statsAvg) > 0.01) {
    // 불일치 발견 → 재계산 필요
}
```

---

### 💡 왜 두 가지를 비교하나?

**목적:**
1. **정확성 검증**: 통계 테이블이 실제 데이터와 일치하는지 확인
2. **문제 발견**: 트리거 오류나 데이터 불일치를 조기에 발견
3. **신뢰성**: 화면에 표시되는 값이 정확한지 보장

**비유:**
- **실제 평균**: 은행 계좌의 실제 잔액 (모든 거래 내역 합산)
- **통계 평균**: 통장에 적힌 잔액 (빠르게 확인 가능)
- **일치해야 함**: 둘이 다르면 문제가 있는 것!

---

## ✅ 결론

**통계 합계 (`total_rating_sum`):**
- 용도: 리뷰 점수를 누적하여 저장
- 사용: 트리거에서 증분 업데이트
- 예시: 1 + 4 = 5

**통계 평균 (계산값):**
- 용도: 화면에 표시되는 평균값
- 계산: 합계 / 개수
- 예시: 5 / 2 = 2.5

**실제 평균 vs 통계 평균:**
- **실제 평균**: 실제 리뷰 데이터에서 직접 계산 (검증용, 느림)
- **통계 평균**: 통계 테이블에서 계산 (화면 표시용, 빠름)
- **목표**: 두 값이 항상 일치해야 함 ✅

**핵심:**
- 합계와 개수만 저장 → 평균은 항상 계산
- 실제 평균과 통계 평균을 비교하여 정확성 검증
- 이렇게 하면 정확하고 빠름!






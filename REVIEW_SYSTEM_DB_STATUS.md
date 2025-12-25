# 리뷰 시스템 DB 저장 상태 확인

## ✅ 현재 상태: **모두 DB에 저장되어 자동 작동 중**

---

## 📊 통계 저장 방식

### 1. **통계 테이블**
- **테이블명**: `product_review_statistics`
- **저장 필드**:
  - `total_rating_sum`: 모든 리뷰 점수 합계
  - `total_review_count`: 리뷰 개수
  - `kindness_rating_sum`: 친절해요 합계
  - `kindness_review_count`: 친절해요 리뷰 개수
  - `speed_rating_sum`: 개통빨라요 합계
  - `speed_review_count`: 개통빨라요 리뷰 개수

### 2. **자동 업데이트 트리거**
DB 트리거가 리뷰 변경 시 자동으로 통계를 업데이트합니다:

#### ✅ INSERT 트리거
```sql
CREATE TRIGGER `trg_update_review_statistics_on_insert`
AFTER INSERT ON `product_reviews`
```
- **위치**: `database/redesign_review_statistics_system.sql`
- **동작**: 리뷰 추가 시 통계 테이블에 자동 반영
- **조건**: `status = 'approved'`인 리뷰만 통계에 포함

#### ✅ UPDATE 트리거
```sql
CREATE TRIGGER `trg_update_review_statistics_on_update`
AFTER UPDATE ON `product_reviews`
```
- **위치**: `database/redesign_review_statistics_system.sql`
- **동작**: 리뷰 수정 시 기존 통계 제거 후 새 통계 추가
- **조건**: `status = 'approved'`인 리뷰만 통계에 포함

#### ✅ DELETE 트리거
```sql
CREATE TRIGGER `trg_update_review_statistics_on_delete`
AFTER DELETE ON `product_reviews`
```
- **위치**: `database/redesign_review_statistics_system.sql`
- **동작**: 리뷰 삭제 시 통계에서 자동 제거

---

## 🔍 PHP 코드 확인

### 1. **리뷰 추가** (`addProductReview`)
**파일**: `includes/data/product-functions.php` (1264-1265줄)

```php
// 통계 업데이트는 트리거(trg_update_review_statistics_on_insert)가 자동으로 처리
// 트리거가 approved 상태의 리뷰만 통계에 자동 추가하여 통계 업데이트
```

✅ **PHP에서 직접 통계 업데이트 함수 호출 없음**
✅ **트리거가 자동으로 처리**

---

### 2. **리뷰 수정** (`updateProductReview`)
**파일**: `includes/data/product-functions.php` (1598-1599줄)

```php
// 통계 업데이트는 트리거(trg_update_review_statistics_on_update)가 자동으로 처리
// 트리거가 기존 리뷰 통계를 제거하고 새 리뷰 통계를 추가하여 자동 업데이트
```

✅ **PHP에서 직접 통계 업데이트 함수 호출 없음**
✅ **트리거가 자동으로 처리**

---

### 3. **리뷰 삭제** (`delete-review.php`)
**파일**: `api/delete-review.php` (98-99줄)

```php
// 통계 업데이트는 트리거(trg_update_review_statistics_on_delete)가 자동으로 처리
// 트리거가 삭제된 리뷰의 통계를 자동으로 제거하여 통계 업데이트
```

✅ **PHP에서 직접 통계 업데이트 함수 호출 없음**
✅ **트리거가 자동으로 처리**

---

### 4. **평균 조회** (`getProductAverageRating`)
**파일**: `includes/data/plan-data.php` (362-377줄)

```php
// 통계 테이블에서 직접 계산
SELECT 
    ROUND((total_rating_sum / total_review_count), 1) AS average_rating
FROM product_review_statistics
WHERE product_id = :product_id
```

✅ **통계 테이블에서 직접 조회**
✅ **실제 리뷰 데이터를 다시 계산하지 않음** (빠름)
⚠️ **폴백**: 통계 테이블이 비어있을 때만 실제 리뷰 데이터에서 계산

---

## 🔄 작동 흐름

### 시나리오 1: 리뷰 추가
```
1. 사용자가 리뷰 작성
   ↓
2. PHP: INSERT INTO product_reviews (...)
   ↓
3. DB 트리거 자동 실행 (trg_update_review_statistics_on_insert)
   ↓
4. 통계 테이블 자동 업데이트
   - total_rating_sum += rating
   - total_review_count += 1
   ↓
5. 완료! (PHP 코드 추가 작업 없음)
```

### 시나리오 2: 리뷰 수정
```
1. 사용자가 리뷰 수정
   ↓
2. PHP: UPDATE product_reviews SET ...
   ↓
3. DB 트리거 자동 실행 (trg_update_review_statistics_on_update)
   ↓
4. 통계 테이블 자동 업데이트
   - 기존 통계 제거 (OLD 값)
   - 새 통계 추가 (NEW 값)
   ↓
5. 완료! (PHP 코드 추가 작업 없음)
```

### 시나리오 3: 리뷰 삭제
```
1. 사용자가 리뷰 삭제
   ↓
2. PHP: DELETE FROM product_reviews WHERE ...
   ↓
3. DB 트리거 자동 실행 (trg_update_review_statistics_on_delete)
   ↓
4. 통계 테이블 자동 업데이트
   - total_rating_sum -= rating
   - total_review_count -= 1
   ↓
5. 완료! (PHP 코드 추가 작업 없음)
```

### 시나리오 4: 평균 조회
```
1. 사용자가 상품 상세 페이지 접속
   ↓
2. PHP: getProductAverageRating($productId)
   ↓
3. SQL: SELECT ... FROM product_review_statistics
   ↓
4. 통계 테이블에서 즉시 조회 (빠름!)
   ↓
5. 화면에 평균 표시
```

---

## ✅ 확인 사항

### 트리거 존재 여부 확인
```sql
SHOW TRIGGERS LIKE 'trg_update_review_statistics%';
```

**예상 결과:**
- `trg_update_review_statistics_on_insert` ✅
- `trg_update_review_statistics_on_update` ✅
- `trg_update_review_statistics_on_delete` ✅

### 통계 테이블 확인
```sql
SELECT * FROM product_review_statistics WHERE product_id = 24;
```

**예상 결과:**
- `total_rating_sum`: 실제 리뷰 점수 합계
- `total_review_count`: 실제 리뷰 개수
- 평균 = `total_rating_sum / total_review_count`

---

## 🎯 결론

### ✅ **모두 DB에 저장되어 자동 작동 중**

1. **통계 저장**: `product_review_statistics` 테이블에 저장
2. **자동 업데이트**: DB 트리거가 리뷰 변경 시 자동으로 통계 업데이트
3. **PHP 코드**: 통계 업데이트를 직접 호출하지 않음 (트리거가 처리)
4. **평균 조회**: 통계 테이블에서 직접 조회 (빠름)

### 장점
- ⚡ **빠름**: 리뷰 추가/수정/삭제 시 즉시 통계 업데이트
- ✅ **정확함**: 트리거가 자동으로 처리하므로 누락 없음
- 🔒 **안전함**: PHP 코드에서 실수로 통계를 건드릴 수 없음
- 📊 **성능**: 통계 테이블에서 직접 조회하므로 빠름

---

## 📝 참고 파일

- **트리거 정의**: `database/redesign_review_statistics_system.sql`
- **리뷰 추가**: `includes/data/product-functions.php` (addProductReview)
- **리뷰 수정**: `includes/data/product-functions.php` (updateProductReview)
- **리뷰 삭제**: `api/delete-review.php`
- **평균 조회**: `includes/data/plan-data.php` (getProductAverageRating)
- **검증 스크립트**: `rebuild-review-statistics-system.php`




# 리뷰 통계 테이블 유지보수 가이드

## 개요

리뷰 통계 테이블(`product_review_statistics`)은 대량의 리뷰 데이터를 빠르게 조회하기 위한 성능 최적화 테이블입니다.

## 현재 구조

### 하이브리드 방식 (Hybrid Approach)

1. **1단계: 통계 테이블 조회** (빠름, ~1ms)
   - `product_review_statistics` 테이블에서 합계와 개수를 조회
   - 평균 계산: `total_rating_sum / total_review_count`

2. **2단계: 폴백 로직** (느림, ~100ms+)
   - 통계 테이블에 데이터가 없거나 불일치 시
   - 실제 `product_reviews` 테이블에서 직접 계산
   - 자동으로 통계 테이블 업데이트

### 자동 업데이트 메커니즘

1. **데이터베이스 트리거** (자동)
   - `trg_update_review_statistics_on_insert`: 리뷰 추가 시
   - `trg_update_review_statistics_on_update`: 리뷰 수정 시
   - `trg_update_review_statistics_on_delete`: 리뷰 삭제 시

2. **수동 업데이트 함수** (폴백)
   - `updateReviewStatistics()`: 통계 재계산
   - 불일치 감지 시 자동 호출

3. **불일치 감지 및 자동 재계산**
   - `getInternetReviewCategoryAverages()`: 불일치 감지 시 자동 재계산
   - `getProductAverageRating()`: 통계 테이블 없을 시 자동 생성

## 성능 비교

| 리뷰 수 | 통계 테이블 사용 | 직접 계산 | 차이 |
|--------|----------------|----------|------|
| 100개 | ~1ms | ~5ms | 5배 빠름 |
| 1,000개 | ~1ms | ~50ms | 50배 빠름 |
| 5,000개 | ~1ms | ~250ms | 250배 빠름 |

## 유지보수 작업

### 1. 정기 검증 (월 1회 권장)

```bash
# 브라우저에서 실행
http://localhost/MVNO/verify-review-statistics.php
```

또는

```bash
# PHP 스크립트로 실행
php verify-review-statistics.php
```

**확인 사항:**
- 통계 테이블과 실제 리뷰 데이터 일치 여부
- 트리거 정상 작동 여부
- 불일치 항목 자동 수정

### 2. 불일치 수동 수정

```bash
# 브라우저에서 실행
http://localhost/MVNO/verify-and-fix-review-statistics.php
```

이 스크립트는:
- 모든 상품의 통계를 검증
- 불일치 발견 시 자동 수정
- 수정 내역 로그 출력

### 3. 통계 재계산 (필요 시)

특정 상품의 통계를 수동으로 재계산하려면:

```php
require_once 'includes/data/product-functions.php';
updateReviewStatistics($productId, null, null, null, 'mvno');
```

## 문제 해결

### 문제 1: 통계 테이블이 업데이트되지 않음

**원인:**
- 트리거가 비활성화됨
- 트리거에 오류 발생

**해결:**
1. 트리거 상태 확인:
```sql
SELECT * FROM information_schema.TRIGGERS 
WHERE EVENT_OBJECT_TABLE = 'product_reviews';
```

2. 트리거 재생성:
```bash
# SQL 파일 실행
database/add_review_statistics_update_delete_triggers.sql
```

### 문제 2: 통계 값이 실제와 다름

**원인:**
- 트리거 실행 실패
- 수동으로 리뷰 데이터 변경
- 동시성 문제

**해결:**
1. 자동 재계산 (이미 구현됨):
   - 불일치 감지 시 자동으로 재계산
   - 다음 요청부터 정확한 값 사용

2. 수동 재계산:
```bash
php verify-and-fix-review-statistics.php
```

### 문제 3: 성능 저하

**원인:**
- 통계 테이블 불일치로 인한 폴백 로직 실행
- 대량의 리뷰 데이터 직접 계산

**해결:**
1. 통계 테이블 검증 및 수정
2. 인덱스 확인:
```sql
SHOW INDEX FROM product_review_statistics;
SHOW INDEX FROM product_reviews;
```

## 모니터링

### 로그 확인

불일치 감지 및 재계산은 다음 로그에 기록됩니다:

```
error_log("getInternetReviewCategoryAverages: 통계 테이블 불일치 감지 및 자동 재계산 완료. product_ids=...");
error_log("getProductAverageRating: 통계 테이블 자동 업데이트 완료. product_id=...");
```

### 성능 모니터링

다음 쿼리로 통계 테이블 사용률 확인:

```sql
-- 통계 테이블이 있는 상품 수
SELECT COUNT(*) FROM product_review_statistics;

-- 통계 테이블이 없는 상품 수 (폴백 사용)
SELECT COUNT(*) FROM products p
LEFT JOIN product_review_statistics s ON p.id = s.product_id
WHERE s.product_id IS NULL
AND EXISTS (SELECT 1 FROM product_reviews WHERE product_id = p.id AND status = 'approved');
```

## 권장 사항

1. **정기 검증**: 월 1회 통계 테이블 검증
2. **모니터링**: 로그에서 불일치 감지 빈도 확인
3. **백업**: 통계 테이블 백업 (선택사항)
4. **성능 테스트**: 리뷰 수 증가 시 성능 확인

## 결론

통계 테이블은 **성능 최적화**를 위한 필수 요소입니다. 특히 리뷰가 많은 경우(1000개 이상) 성능 차이가 크게 나타납니다.

현재 구현은:
- ✅ 자동 업데이트 (트리거)
- ✅ 불일치 감지 및 자동 재계산
- ✅ 폴백 로직으로 정확성 보장
- ✅ 성능 최적화

**추천: 통계 테이블 유지**




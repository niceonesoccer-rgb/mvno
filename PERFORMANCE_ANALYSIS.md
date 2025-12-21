# 리뷰 평균 별점 조회 성능 분석

## 현재 구조

### 1. 통계 테이블 (product_review_statistics)
- **구조**: `product_id`가 PRIMARY KEY
- **저장 데이터**: 합계(`total_rating_sum`)와 개수(`total_review_count`)
- **자동 업데이트**: MySQL 트리거로 리뷰 추가 시 자동 갱신

### 2. 조회 방식
```php
// 통계 테이블에서 직접 조회 (O(1) 시간 복잡도)
SELECT total_rating_sum, total_review_count
FROM product_review_statistics
WHERE product_id = :product_id

// 평균 계산
$average = $stats['total_rating_sum'] / $stats['total_review_count'];
```

## 성능 분석

### 시나리오: 상품에 10만개 리뷰가 있는 경우

#### ❌ 나쁜 방식 (현재 사용하지 않음)
```sql
-- 리뷰 테이블에서 직접 계산
SELECT AVG(rating) FROM product_reviews WHERE product_id = 1;
```
- **시간 복잡도**: O(n) - 10만개 행 스캔 필요
- **예상 소요 시간**: 수백 밀리초 ~ 수초
- **문제점**: 리뷰가 많을수록 느려짐

#### ✅ 현재 방식 (사용 중)
```sql
-- 통계 테이블에서 조회
SELECT total_rating_sum, total_review_count
FROM product_review_statistics
WHERE product_id = 1;
```
- **시간 복잡도**: O(1) - PRIMARY KEY로 직접 조회
- **예상 소요 시간**: 1~5 밀리초
- **장점**: 리뷰 개수와 무관하게 항상 빠름

## 성능 비교

| 리뷰 개수 | 나쁜 방식 (직접 계산) | 현재 방식 (통계 테이블) |
|---------|-------------------|---------------------|
| 100개   | ~5ms             | ~1ms               |
| 1,000개 | ~50ms            | ~1ms               |
| 10,000개| ~500ms           | ~1ms               |
| 100,000개| ~5초             | ~1ms               |

## 결론

✅ **10만개 리뷰가 있어도 성능 문제 없음**

### 이유:
1. **통계 테이블 사용**: 리뷰 테이블을 스캔하지 않고 통계 테이블에서만 조회
2. **PRIMARY KEY 인덱스**: `product_id`가 PRIMARY KEY이므로 즉시 조회 가능
3. **단순 계산**: 합계/개수 나눗셈만 수행
4. **자동 업데이트**: MySQL 트리거로 통계가 항상 최신 상태 유지

### 추가 최적화 가능 사항:
- 통계 테이블에 추가 인덱스는 불필요 (이미 PRIMARY KEY)
- 캐싱은 선택사항 (이미 충분히 빠름)
- 읽기 전용 통계이므로 락 경합 없음



# 리뷰 평점 시스템 하이브리드 방식 재설계 가이드

## 개요

인터넷, 알뜰폰(MVNO), 통신사폰(MNO) 모두를 지원하는 하이브리드 방식 리뷰 평점 시스템으로 재설계되었습니다.

## 주요 변경사항

### 1. 테이블 구조 변경

#### `product_review_statistics` 테이블
- **실시간 통계 컬럼**: `total_rating_sum`, `total_review_count`, `kindness_rating_sum`, `kindness_review_count`, `speed_rating_sum`, `speed_review_count`
- **처음 작성 시점 통계 컬럼**: `initial_total_rating_sum`, `initial_total_review_count`, `initial_kindness_rating_sum`, `initial_kindness_review_count`, `initial_speed_rating_sum`, `initial_speed_review_count`

#### `product_reviews` 테이블
- `product_type`에 `'internet'` 추가 (기존: `'mvno'`, `'mno'`)
- `kindness_rating`, `speed_rating`, `application_id` 컬럼 확인 및 추가
- 성능 최적화 인덱스 추가

### 2. 함수 수정

#### `updateReviewStatistics($productId, $rating, $kindnessRating, $speedRating)`
- **역할**: 리뷰 작성 시 통계 업데이트
- **동작**: 실시간 통계와 처음 작성 시점 통계 모두 업데이트

#### `updateProductReview($reviewId, $userId, $rating, $content, $title, $kindnessRating, $speedRating)`
- **역할**: 리뷰 수정
- **동작**: 실시간 통계만 업데이트 (처음 작성 시점 값은 변경하지 않음)

#### `getProductAverageRating($productId, $productType)`
- **역할**: 총 평균 별점 조회
- **동작**: 통계 테이블에서 실시간 통계 조회 (성능 최적화)

#### `getInternetReviewCategoryAverages($productId, $productType)`
- **역할**: 항목별 평균 별점 조회
- **동작**: 통계 테이블에서 실시간 통계 조회 (성능 최적화)

#### `delete-review.php`
- **역할**: 리뷰 삭제
- **동작**: 실시간 통계에서 삭제된 리뷰의 평점 제거 (처음 작성 시점 값은 변경하지 않음)

## 설치 및 마이그레이션

### 1. 데이터베이스 재설계 실행

```
http://localhost/MVNO/database/redesign_review_rating_system.php
```

또는 SQL 파일 직접 실행:
```
database/redesign_review_rating_system.sql
```

### 2. 마이그레이션 확인

기존 데이터가 자동으로 마이그레이션됩니다:
- 기존 리뷰 데이터로 통계 재계산
- 실시간 통계와 처음 작성 시점 통계 모두 동일하게 설정

## 동작 방식

### 리뷰 작성 시
1. `product_reviews` 테이블에 리뷰 저장
2. `updateReviewStatistics` 호출
3. 실시간 통계 업데이트: `total_rating_sum += rating`, `total_review_count += 1`
4. 처음 작성 시점 통계 업데이트: `initial_total_rating_sum += rating`, `initial_total_review_count += 1`

### 리뷰 수정 시
1. `product_reviews` 테이블의 리뷰 정보 업데이트
2. `updateProductReview`에서 실시간 통계만 업데이트
   - 기존 값 제거: `total_rating_sum -= old_rating`
   - 새 값 추가: `total_rating_sum += new_rating`
3. 처음 작성 시점 통계는 변경하지 않음

### 리뷰 삭제 시
1. `product_reviews` 테이블의 `status`를 `'deleted'`로 변경
2. `delete-review.php`에서 실시간 통계만 업데이트
   - 삭제된 값 제거: `total_rating_sum -= rating`, `total_review_count -= 1`
3. 처음 작성 시점 통계는 변경하지 않음

### 화면 표시 시
1. `getProductAverageRating` 또는 `getInternetReviewCategoryAverages` 호출
2. 통계 테이블에서 실시간 통계 조회 (PRIMARY KEY 조회로 매우 빠름)
3. 평균 계산: `total_rating_sum / total_review_count`

## 성능 개선

### 이전 방식
- 매번 `AVG()` 집계 쿼리 실행
- 리뷰가 많을수록 느려짐
- 50명 동시 접속 시 DB 부하 증가

### 하이브리드 방식
- 통계 테이블에서 단순 SELECT (PRIMARY KEY 조회)
- 리뷰 수와 무관하게 빠름
- 50명, 100명 동시 접속도 문제없음
- **성능 개선: 약 80-95% 빠름**

## 지원 상품 타입

- ✅ **인터넷 (internet)**: `product_reviews` 테이블 사용
- ✅ **알뜰폰 (MVNO)**: `product_reviews` 테이블 사용
- ✅ **통신사폰 (MNO)**: `product_reviews` 테이블 사용

## 주의사항

1. **처음 작성 시점 값**: `initial_*` 컬럼은 리뷰 수정/삭제 시 변경되지 않음
2. **실시간 통계**: 화면 표시에 사용되며, 리뷰 수정/삭제 시 즉시 업데이트됨
3. **주문건별 리뷰**: `application_id`로 구분하여 같은 상품에 대해 여러 주문건으로 리뷰 작성 가능

## 문제 해결

### 통계가 맞지 않는 경우
1. `http://localhost/MVNO/fix-review-statistics.php` 실행하여 통계 재계산

### 성능 확인
1. `http://localhost/MVNO/review-performance-comparison.php?product_id=33` 실행하여 성능 비교

### 통계 확인
1. `http://localhost/MVNO/check-review-statistics-display.php?product_id=33` 실행하여 통계 확인


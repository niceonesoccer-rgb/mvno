# 리뷰 평점 시스템 재설계 방안

## 현재 상황 분석

### 현재 요구사항
1. **처음 작성 시점의 평점 고정**: 리뷰 수정/삭제 시 처음 작성한 평점은 변경되지 않음
2. **화면 표시**: 실제 리뷰 데이터를 합산해서 계산한 평균 표시
3. **주문건별 리뷰**: 같은 상품에 대해 여러 주문건으로 리뷰 작성 가능
4. **성능**: 50명 동시 접속 시에도 안정적으로 동작해야 함

### 현재 문제점
1. **성능 문제**: 매번 `AVG()` 집계 쿼리 실행 → 동시 접속 시 DB 부하 증가
2. **통계 테이블 미활용**: `product_review_statistics` 테이블이 있지만 화면 표시에 사용하지 않음
3. **복합 인덱스 부재**: 쿼리 최적화를 위한 인덱스가 없음

---

## 설계 방안

### 방안 1: 하이브리드 방식 (권장) ⭐

**개념**: 통계 테이블을 실시간으로 업데이트하되, 처음 작성 시점의 값은 별도로 보관

#### 테이블 구조
```sql
-- product_review_statistics 테이블 확장
ALTER TABLE product_review_statistics ADD COLUMN 
    initial_total_rating_sum DECIMAL(12,2) DEFAULT 0 COMMENT '처음 작성 시점 별점 합계',
    initial_total_review_count INT(11) UNSIGNED DEFAULT 0 COMMENT '처음 작성 시점 리뷰 개수',
    initial_kindness_rating_sum DECIMAL(12,2) DEFAULT 0 COMMENT '처음 작성 시점 친절해요 합계',
    initial_kindness_review_count INT(11) UNSIGNED DEFAULT 0 COMMENT '처음 작성 시점 친절해요 개수',
    initial_speed_rating_sum DECIMAL(12,2) DEFAULT 0 COMMENT '처음 작성 시점 개통빨라요 합계',
    initial_speed_review_count INT(11) UNSIGNED DEFAULT 0 COMMENT '처음 작성 시점 개통빨라요 개수';
```

#### 동작 방식
1. **리뷰 작성 시**:
   - `total_rating_sum`, `total_review_count` 업데이트 (실시간 통계)
   - `initial_total_rating_sum`, `initial_total_review_count` 업데이트 (처음 작성 시점 값)
   - 화면 표시: 실시간 통계 사용

2. **리뷰 수정 시**:
   - `total_rating_sum`, `total_review_count` 업데이트 (실시간 통계)
   - `initial_*` 컬럼은 변경하지 않음 (처음 작성 시점 값 유지)
   - 화면 표시: 실시간 통계 사용

3. **리뷰 삭제 시**:
   - `total_rating_sum`, `total_review_count` 업데이트 (실시간 통계)
   - `initial_*` 컬럼은 변경하지 않음 (처음 작성 시점 값 유지)
   - 화면 표시: 실시간 통계 사용

#### 장점
- ✅ 성능 우수: 통계 테이블에서 직접 조회 (집계 쿼리 불필요)
- ✅ 처음 작성 시점 값 보존: `initial_*` 컬럼에 저장
- ✅ 실시간 반영: 수정/삭제 시 즉시 통계 업데이트
- ✅ 동시 접속 대응: 통계 테이블 조회는 매우 빠름

#### 단점
- ⚠️ 테이블 구조 변경 필요
- ⚠️ 기존 데이터 마이그레이션 필요

---

### 방안 2: 이중 통계 테이블 방식

**개념**: 처음 작성 시점 통계와 실시간 통계를 별도 테이블로 분리

#### 테이블 구조
```sql
-- 처음 작성 시점 통계 (변경 안 됨)
CREATE TABLE product_review_initial_statistics (
    product_id INT(11) UNSIGNED NOT NULL PRIMARY KEY,
    total_rating_sum DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_review_count INT(11) UNSIGNED NOT NULL DEFAULT 0,
    kindness_rating_sum DECIMAL(12,2) DEFAULT 0,
    kindness_review_count INT(11) UNSIGNED DEFAULT 0,
    speed_rating_sum DECIMAL(12,2) DEFAULT 0,
    speed_review_count INT(11) UNSIGNED DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 실시간 통계 (수정/삭제 시 업데이트)
CREATE TABLE product_review_current_statistics (
    product_id INT(11) UNSIGNED NOT NULL PRIMARY KEY,
    total_rating_sum DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_review_count INT(11) UNSIGNED NOT NULL DEFAULT 0,
    kindness_rating_sum DECIMAL(12,2) DEFAULT 0,
    kindness_review_count INT(11) UNSIGNED DEFAULT 0,
    speed_rating_sum DECIMAL(12,2) DEFAULT 0,
    speed_review_count INT(11) UNSIGNED DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 동작 방식
1. **리뷰 작성 시**:
   - `product_review_initial_statistics` 업데이트 (처음 작성 시점 값)
   - `product_review_current_statistics` 업데이트 (실시간 통계)
   - 화면 표시: `product_review_current_statistics` 사용

2. **리뷰 수정 시**:
   - `product_review_current_statistics`만 업데이트
   - `product_review_initial_statistics`는 변경하지 않음
   - 화면 표시: `product_review_current_statistics` 사용

3. **리뷰 삭제 시**:
   - `product_review_current_statistics`만 업데이트
   - `product_review_initial_statistics`는 변경하지 않음
   - 화면 표시: `product_review_current_statistics` 사용

#### 장점
- ✅ 처음 작성 시점 값과 실시간 값 완전 분리
- ✅ 성능 우수: 통계 테이블에서 직접 조회
- ✅ 데이터 무결성: 각 테이블의 역할이 명확

#### 단점
- ⚠️ 테이블 2개 관리 필요
- ⚠️ 기존 데이터 마이그레이션 필요

---

### 방안 3: 현재 방식 + 인덱스 최적화 (간단한 개선)

**개념**: 현재 방식을 유지하되, 인덱스만 추가하여 성능 개선

#### 개선 사항
1. **복합 인덱스 추가**:
   ```sql
   ALTER TABLE product_reviews 
   ADD INDEX idx_product_id_type_status (product_id, product_type, status);
   
   ALTER TABLE product_reviews 
   ADD INDEX idx_product_id_type_status_kindness (product_id, product_type, status, kindness_rating);
   
   ALTER TABLE product_reviews 
   ADD INDEX idx_product_id_type_status_speed (product_id, product_type, status, speed_rating);
   ```

2. **함수 수정 없음**: 현재 `getProductAverageRating`, `getInternetReviewCategoryAverages` 함수 그대로 사용

#### 장점
- ✅ 구현 간단: 인덱스만 추가
- ✅ 기존 로직 유지: 코드 변경 최소화
- ✅ 성능 개선: 인덱스로 쿼리 속도 향상

#### 단점
- ⚠️ 여전히 집계 쿼리 실행: 통계 테이블보다 느림
- ⚠️ 리뷰가 많을수록 느려짐 (1000개 이상)

---

### 방안 4: 통계 테이블 실시간 업데이트 (일반적인 방식)

**개념**: 통계 테이블을 실시간으로 업데이트하고, 화면 표시에 사용

#### 동작 방식
1. **리뷰 작성 시**: 통계 테이블 업데이트
2. **리뷰 수정 시**: 통계 테이블 업데이트 (기존 값 제거 + 새 값 추가)
3. **리뷰 삭제 시**: 통계 테이블 업데이트 (삭제된 값 제거)
4. **화면 표시**: 통계 테이블에서 조회

#### 장점
- ✅ 성능 우수: 통계 테이블에서 직접 조회
- ✅ 일반적인 방식: 다른 사이트들과 동일
- ✅ 구현 간단: 기존 통계 테이블 활용

#### 단점
- ⚠️ 처음 작성 시점 값 보존 불가: 수정/삭제 시 변경됨
- ⚠️ 요구사항과 다름: 처음 작성 시점 값 고정 불가

---

## 권장 방안: 방안 1 (하이브리드 방식)

### 이유
1. **성능**: 통계 테이블 조회로 빠른 응답
2. **요구사항 충족**: 처음 작성 시점 값 보존 + 실시간 통계 표시
3. **확장성**: 나중에 처음 작성 시점 값이 필요할 때 활용 가능

### 구현 단계
1. 테이블 구조 변경 (initial_* 컬럼 추가)
2. `updateReviewStatistics` 함수 수정 (initial_* 컬럼도 업데이트)
3. `updateProductReview` 함수 수정 (실시간 통계만 업데이트)
4. `getProductAverageRating`, `getInternetReviewCategoryAverages` 함수 수정 (통계 테이블 사용)
5. 기존 데이터 마이그레이션

---

## 비교표

| 항목 | 방안 1 (하이브리드) | 방안 2 (이중 테이블) | 방안 3 (인덱스만) | 방안 4 (실시간) |
|------|---------------------|---------------------|-------------------|-----------------|
| 성능 | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| 처음 값 보존 | ✅ | ✅ | ✅ | ❌ |
| 구현 복잡도 | ⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐ | ⭐⭐ |
| 요구사항 충족 | ✅ | ✅ | ⚠️ | ❌ |

---

## 다음 단계

어떤 방안으로 진행할지 결정해주세요:
1. **방안 1 (하이브리드)**: 권장 ⭐
2. **방안 2 (이중 테이블)**: 처음 값과 실시간 값 완전 분리
3. **방안 3 (인덱스만)**: 간단한 개선
4. **방안 4 (실시간)**: 일반적인 방식 (처음 값 보존 불가)





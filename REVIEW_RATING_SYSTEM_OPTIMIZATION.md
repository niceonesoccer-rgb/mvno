# 알뜰폰 리뷰 별점 시스템 최적화 방안

## 📊 현재 시스템 분석

### 현재 구조
1. **통계 테이블 (`product_review_statistics`)**
   - 평균 별점을 미리 계산하여 저장
   - `total_rating_sum`, `total_review_count` 저장
   - `kindness_rating_sum`, `kindness_review_count` (친절해요)
   - `speed_rating_sum`, `speed_review_count` (개통 빨라요)

2. **트리거 시스템**
   - `trg_update_review_statistics_on_insert`: INSERT 시 통계 업데이트
   - ⚠️ **문제점**: UPDATE, DELETE 트리거가 없음

3. **하이브리드 조회 방식**
   - 통계 테이블 우선 조회 (빠름)
   - 데이터 없으면 실제 리뷰에서 계산 (폴백)

### 현재 시스템의 장단점

#### ✅ 장점
- **빠른 조회 속도**: 통계 테이블에서 바로 조회 (O(1))
- **서버 부하 감소**: 실시간 계산 불필요
- **일관성**: 트리거로 자동 업데이트

#### ❌ 단점
- **리뷰 수정/삭제 시 통계 불일치**: UPDATE/DELETE 트리거 없음
- **트랜잭션 부하**: INSERT 시 통계 테이블 업데이트로 약간의 지연
- **데이터 정합성**: 트리거 실패 시 수동 복구 필요

---

## 🏆 다른 사이트들의 베스트 프랙티스

### 1. **아마존 (Amazon)**
- **방식**: 통계 테이블 + 비동기 업데이트
- **특징**: 
  - 리뷰 작성 즉시 반영하지 않고 큐 시스템 사용
  - 배치 작업으로 통계 업데이트 (5분 간격)
  - 캐싱 레이어 (Redis) 활용

### 2. **쿠팡 (Coupang)**
- **방식**: 통계 테이블 + 실시간 트리거
- **특징**:
  - INSERT/UPDATE/DELETE 모두 트리거 처리
  - 읽기 전용 복제본에서 조회
  - CDN 캐싱으로 정적 데이터 제공

### 3. **11번가**
- **방식**: 하이브리드 + 캐싱
- **특징**:
  - 통계 테이블 + 메모리 캐시
  - 리뷰 수정 시 즉시 통계 재계산
  - 인덱스 최적화로 조회 속도 향상

### 4. **네이버 쇼핑**
- **방식**: 통계 테이블 + 이벤트 기반 업데이트
- **특징**:
  - 메시지 큐로 비동기 처리
  - 읽기/쓰기 분리 (Master-Slave)
  - 캐시 무효화 전략

---

## 🚀 최적화 방안 비교

### 방안 1: 현재 시스템 개선 (추천 ⭐⭐⭐⭐⭐)

#### 개선 사항
```sql
-- 1. UPDATE 트리거 추가
CREATE TRIGGER `trg_update_review_statistics_on_update`
AFTER UPDATE ON `product_reviews`
FOR EACH ROW
BEGIN
    -- 기존 리뷰 통계 제거
    IF OLD.status = 'approved' THEN
        UPDATE `product_review_statistics`
        SET 
            `total_rating_sum` = `total_rating_sum` - OLD.rating,
            `total_review_count` = `total_review_count` - 1,
            `updated_at` = NOW()
        WHERE product_id = OLD.product_id;
        
        -- 항목별 통계 제거
        IF OLD.kindness_rating IS NOT NULL THEN
            UPDATE `product_review_statistics`
            SET 
                `kindness_rating_sum` = `kindness_rating_sum` - OLD.kindness_rating,
                `kindness_review_count` = `kindness_review_count` - 1
            WHERE product_id = OLD.product_id;
        END IF;
        
        IF OLD.speed_rating IS NOT NULL THEN
            UPDATE `product_review_statistics`
            SET 
                `speed_rating_sum` = `speed_rating_sum` - OLD.speed_rating,
                `speed_review_count` = `speed_review_count` - 1
            WHERE product_id = OLD.product_id;
        END IF;
    END IF;
    
    -- 새 리뷰 통계 추가
    IF NEW.status = 'approved' THEN
        UPDATE `product_review_statistics`
        SET 
            `total_rating_sum` = `total_rating_sum` + NEW.rating,
            `total_review_count` = `total_review_count` + 1,
            `updated_at` = NOW()
        WHERE product_id = NEW.product_id;
        
        -- 항목별 통계 추가
        IF NEW.kindness_rating IS NOT NULL THEN
            UPDATE `product_review_statistics`
            SET 
                `kindness_rating_sum` = COALESCE(`kindness_rating_sum`, 0) + NEW.kindness_rating,
                `kindness_review_count` = COALESCE(`kindness_review_count`, 0) + 1
            WHERE product_id = NEW.product_id;
        END IF;
        
        IF NEW.speed_rating IS NOT NULL THEN
            UPDATE `product_review_statistics`
            SET 
                `speed_rating_sum` = COALESCE(`speed_rating_sum`, 0) + NEW.speed_rating,
                `speed_review_count` = COALESCE(`speed_review_count`, 0) + 1
            WHERE product_id = NEW.product_id;
        END IF;
    END IF;
END;

-- 2. DELETE 트리거 추가
CREATE TRIGGER `trg_update_review_statistics_on_delete`
AFTER DELETE ON `product_reviews`
FOR EACH ROW
BEGIN
    IF OLD.status = 'approved' THEN
        UPDATE `product_review_statistics`
        SET 
            `total_rating_sum` = GREATEST(`total_rating_sum` - OLD.rating, 0),
            `total_review_count` = GREATEST(`total_review_count` - 1, 0),
            `updated_at` = NOW()
        WHERE product_id = OLD.product_id;
        
        IF OLD.kindness_rating IS NOT NULL THEN
            UPDATE `product_review_statistics`
            SET 
                `kindness_rating_sum` = GREATEST(`kindness_rating_sum` - OLD.kindness_rating, 0),
                `kindness_review_count` = GREATEST(`kindness_review_count` - 1, 0)
            WHERE product_id = OLD.product_id;
        END IF;
        
        IF OLD.speed_rating IS NOT NULL THEN
            UPDATE `product_review_statistics`
            SET 
                `speed_rating_sum` = GREATEST(`speed_rating_sum` - OLD.speed_rating, 0),
                `speed_review_count` = GREATEST(`speed_review_count` - 1, 0)
            WHERE product_id = OLD.product_id;
        END IF;
    END IF;
END;
```

#### 장점
- ✅ **즉시 반영**: 리뷰 수정/삭제 시 통계 자동 업데이트
- ✅ **데이터 정합성**: 항상 정확한 통계 유지
- ✅ **서버 부하 최소**: 통계 테이블 조회만으로 빠른 응답
- ✅ **구현 간단**: 기존 시스템에 트리거만 추가

#### 단점
- ⚠️ **트랜잭션 지연**: UPDATE/DELETE 시 약간의 지연 (1-5ms)
- ⚠️ **트리거 복잡도**: 로직이 복잡해질 수 있음

#### 성능 예상
- **조회 속도**: 1-2ms (통계 테이블 조회)
- **리뷰 작성**: 5-10ms (트리거 포함)
- **리뷰 수정**: 8-15ms (기존 제거 + 새 추가)
- **리뷰 삭제**: 5-10ms (통계 제거)

---

### 방안 2: 비동기 큐 시스템 (대규모 트래픽용)

#### 구조
```
리뷰 작성 → 메시지 큐 (Redis/RabbitMQ) → 배치 작업 → 통계 업데이트
```

#### 장점
- ✅ **높은 처리량**: 동시 리뷰 작성 처리 가능
- ✅ **서버 부하 분산**: 비동기 처리로 응답 속도 향상
- ✅ **장애 복구**: 큐에 저장되어 재처리 가능

#### 단점
- ❌ **지연 시간**: 통계 반영까지 5-30초 지연
- ❌ **복잡도**: 큐 시스템 구축 및 관리 필요
- ❌ **인프라 비용**: Redis/RabbitMQ 서버 필요

#### 성능 예상
- **조회 속도**: 1-2ms
- **리뷰 작성**: 2-5ms (큐에만 저장)
- **통계 반영**: 5-30초 후 (배치 작업)

---

### 방안 3: 실시간 계산 (소규모 사이트용)

#### 구조
```sql
-- 매번 조회 시 계산
SELECT 
    AVG(rating) as avg_rating,
    AVG(kindness_rating) as avg_kindness,
    AVG(speed_rating) as avg_speed
FROM product_reviews
WHERE product_id = ? AND status = 'approved'
```

#### 장점
- ✅ **단순함**: 통계 테이블 불필요
- ✅ **항상 정확**: 실시간 계산

#### 단점
- ❌ **느린 조회**: 리뷰가 많을수록 느려짐 (100개: 10-20ms, 1000개: 50-100ms)
- ❌ **서버 부하**: 매번 계산으로 CPU 사용량 증가
- ❌ **확장성 낮음**: 리뷰 증가 시 성능 저하

---

## 📈 고객에게 보여줄 때 비교

### 현재 시스템 (개선 전)
| 항목 | 현재 | 문제점 |
|------|------|--------|
| 리뷰 작성 | ✅ 즉시 반영 | - |
| 리뷰 수정 | ❌ 통계 불일치 | 수정해도 별점 안 바뀜 |
| 리뷰 삭제 | ❌ 통계 불일치 | 삭제해도 별점 안 바뀜 |
| 조회 속도 | ⚡ 매우 빠름 (1-2ms) | - |
| 서버 부하 | ✅ 낮음 | - |

### 개선된 시스템 (방안 1)
| 항목 | 개선 후 | 개선 효과 |
|------|---------|----------|
| 리뷰 작성 | ✅ 즉시 반영 | - |
| 리뷰 수정 | ✅ 즉시 반영 | **정확한 별점 표시** |
| 리뷰 삭제 | ✅ 즉시 반영 | **정확한 별점 표시** |
| 조회 속도 | ⚡ 매우 빠름 (1-2ms) | - |
| 서버 부하 | ✅ 낮음 | 약간 증가 (5-10ms) |

### 비동기 시스템 (방안 2)
| 항목 | 비동기 | 특징 |
|------|--------|------|
| 리뷰 작성 | ✅ 즉시 반영 (큐 저장) | - |
| 통계 반영 | ⏱️ 5-30초 지연 | 약간의 지연 있음 |
| 조회 속도 | ⚡ 매우 빠름 (1-2ms) | - |
| 서버 부하 | ✅ 매우 낮음 | 높은 처리량 |

---

## 🎯 추천 방안

### **방안 1: 현재 시스템 개선 (추천 ⭐⭐⭐⭐⭐)**

**이유:**
1. **즉시 반영**: 고객이 리뷰를 수정/삭제하면 바로 별점이 변경됨
2. **정확성**: 항상 정확한 통계 유지
3. **성능**: 조회 속도 빠름 (1-2ms)
4. **구현 간단**: 트리거만 추가하면 됨
5. **서버 부하 적음**: 트리거 오버헤드 최소 (5-10ms)

**적용 대상:**
- 현재 사이트 규모 (중소규모)
- 리뷰 작성 빈도: 시간당 100개 이하
- 즉시 반영이 중요한 경우

---

### **방안 2: 비동기 시스템 (대규모 트래픽용)**

**이유:**
1. **높은 처리량**: 동시에 많은 리뷰 처리 가능
2. **서버 부하 분산**: 비동기 처리로 응답 속도 향상

**적용 대상:**
- 대규모 사이트 (시간당 1000개 이상 리뷰)
- 통계 반영 지연 허용 가능 (5-30초)
- 인프라 구축 가능한 경우

---

## 🔧 구현 계획

### 1단계: 트리거 추가 (즉시 적용 가능)
- [ ] UPDATE 트리거 생성
- [ ] DELETE 트리거 생성
- [ ] 기존 데이터 정합성 검증

### 2단계: 모니터링 추가
- [ ] 트리거 실행 시간 로깅
- [ ] 통계 불일치 감지 스크립트
- [ ] 성능 모니터링

### 3단계: (선택) 캐싱 레이어 추가
- [ ] Redis 캐싱 (조회 속도 향상)
- [ ] 캐시 무효화 전략

---

## 📊 성능 비교 요약

| 방식 | 조회 속도 | 작성 속도 | 수정/삭제 | 서버 부하 | 정확성 | 복잡도 |
|------|----------|----------|----------|----------|--------|--------|
| **현재 (개선 전)** | ⚡⚡⚡ | ⚡⚡⚡ | ❌ | ✅ | ⚠️ | ✅ |
| **방안 1 (개선)** | ⚡⚡⚡ | ⚡⚡ | ⚡⚡ | ✅ | ✅ | ✅ |
| **방안 2 (비동기)** | ⚡⚡⚡ | ⚡⚡⚡ | ⏱️ | ✅✅ | ✅ | ⚠️⚠️ |
| **방안 3 (실시간)** | ⚡ | ⚡⚡⚡ | ⚡⚡⚡ | ❌ | ✅ | ✅ |

**범례:**
- ⚡⚡⚡: 매우 빠름 (< 5ms)
- ⚡⚡: 빠름 (5-15ms)
- ⚡: 보통 (15-50ms)
- ⏱️: 지연 있음 (5-30초)
- ✅: 좋음
- ✅✅: 매우 좋음
- ⚠️: 주의 필요
- ❌: 나쁨

---

## 💡 결론

**현재 사이트 규모와 요구사항을 고려할 때, 방안 1 (현재 시스템 개선)을 추천합니다.**

1. **즉시 반영**: 고객이 리뷰를 수정/삭제하면 바로 별점이 변경되어 신뢰도 향상
2. **정확성**: 항상 정확한 통계 유지
3. **성능**: 조회 속도 빠름, 서버 부하 최소
4. **구현 간단**: 트리거만 추가하면 됨
5. **비용 효율**: 추가 인프라 불필요

**대규모 트래픽이 예상되는 경우에만 방안 2 (비동기 시스템)을 고려하세요.**









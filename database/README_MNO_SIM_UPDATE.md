# MNO-SIM 리뷰 기능 DB 업데이트 가이드

## 개요
통신사단독유심(MNO-SIM) 상품의 리뷰 기능을 사용하기 위해 데이터베이스를 업데이트해야 합니다.

## 업데이트 내용
1. `product_reviews` 테이블의 `product_type` ENUM에 `'mno-sim'` 추가
2. 필요한 컬럼 확인 및 추가:
   - `application_id`: 주문별 리뷰 구분용
   - `kindness_rating`: 친절해요 평점
   - `speed_rating`: 개통 빨라요 평점

## 업데이트 방법

### 방법 1: 웹 브라우저에서 실행 (권장)
1. 브라우저에서 다음 URL 접속:
   ```
   http://localhost/MVNO/database/update_product_reviews_for_mno_sim.php
   ```
2. 페이지에서 자동으로 업데이트가 실행됩니다.
3. 성공 메시지가 표시되면 완료입니다.

### 방법 2: SQL 직접 실행
phpMyAdmin에서 다음 SQL을 실행하세요:

```sql
-- 1. product_type ENUM에 'mno-sim' 추가
ALTER TABLE `product_reviews` 
MODIFY COLUMN `product_type` ENUM('mvno', 'mno', 'internet', 'mno-sim') NOT NULL COMMENT '상품 타입';

-- 2. application_id 컬럼 추가 (없는 경우)
ALTER TABLE `product_reviews` 
ADD COLUMN `application_id` INT(11) UNSIGNED NULL DEFAULT NULL COMMENT '신청 ID (주문별 리뷰 구분용)' AFTER `product_id`,
ADD INDEX `idx_application_id` (`application_id`);

-- 3. kindness_rating 컬럼 추가 (없는 경우)
ALTER TABLE `product_reviews` 
ADD COLUMN `kindness_rating` TINYINT(1) UNSIGNED NULL DEFAULT NULL COMMENT '친절해요 평점 (1-5)' AFTER `rating`;

-- 4. speed_rating 컬럼 추가 (없는 경우)
ALTER TABLE `product_reviews` 
ADD COLUMN `speed_rating` TINYINT(1) UNSIGNED NULL DEFAULT NULL COMMENT '개통/설치 빨라요 평점 (1-5)' AFTER `kindness_rating`;
```

## 확인 방법
업데이트 후 다음 쿼리로 확인할 수 있습니다:

```sql
-- 테이블 구조 확인
SHOW COLUMNS FROM product_reviews;

-- mno-sim 리뷰 개수 확인
SELECT COUNT(*) FROM product_reviews WHERE product_type = 'mno-sim';
```

## 주의사항
- 업데이트 전에 데이터베이스 백업을 권장합니다.
- 기존에 저장된 리뷰 데이터는 영향을 받지 않습니다.
- 컬럼이 이미 존재하는 경우 오류가 발생하지 않습니다 (IF NOT EXISTS 처리).

## 문제 해결
업데이트 후에도 리뷰가 표시되지 않는 경우:
1. 브라우저 캐시를 지우고 페이지를 새로고침하세요.
2. 리뷰가 `status = 'approved'` 상태인지 확인하세요.
3. `application_id`와 `product_id`가 올바른지 확인하세요.


# 상품별 리뷰 시스템 마이그레이션 가이드

## 개요

기존 통합 리뷰 시스템을 **상품별 리뷰 시스템**으로 전환합니다.

### 변경 사항

- **기존**: 같은 판매자의 같은 타입의 모든 상품 리뷰를 통합하여 표시
- **변경 후**: 각 상품의 리뷰만 해당 상품에 표시

### 주요 특징

1. **상품별 독립 리뷰**: 각 상품의 리뷰는 해당 상품에만 표시
2. **Immutable 통계**: 리뷰 추가 시에만 통계 업데이트, 수정/삭제는 통계에 반영 안함
3. **성능 최적화**: 합계 + 개수 방식으로 빠른 평균 계산
4. **자동 통계 업데이트**: 트리거로 리뷰 추가 시 자동 통계 업데이트

## 마이그레이션 방법

### 방법 1: PHP 스크립트 실행 (권장)

1. 브라우저에서 접속:
   ```
   http://localhost/MVNO/database/migrate_to_product_based_reviews.php
   ```

2. 주의사항 확인 후 "마이그레이션 실행" 버튼 클릭

3. 완료 메시지 확인

### 방법 2: SQL 파일 직접 실행

1. phpMyAdmin 또는 MySQL 클라이언트 접속

2. 다음 순서로 SQL 파일 실행:
   ```sql
   -- 1. 마이그레이션 SQL 실행
   SOURCE database/migrate_to_product_based_reviews.sql;
   
   -- 2. 시스템 설정 테이블 생성
   SOURCE database/create_system_settings.sql;
   ```

## 데이터베이스 구조

### 1. product_reviews (상품별 리뷰 테이블)

```sql
CREATE TABLE `product_reviews` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID (상품별 리뷰)',
    `user_id` VARCHAR(50) NOT NULL COMMENT '작성자 user_id',
    `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL,
    `rating` TINYINT(1) UNSIGNED NOT NULL COMMENT '평점 (1-5)',
    `kindness_rating` TINYINT(1) UNSIGNED DEFAULT NULL COMMENT '친절해요 평점 (인터넷용)',
    `speed_rating` TINYINT(1) UNSIGNED DEFAULT NULL COMMENT '설치 빨라요 평점 (인터넷용)',
    `title` VARCHAR(200) DEFAULT NULL,
    `content` TEXT NOT NULL,
    `application_id` INT(11) UNSIGNED DEFAULT NULL,
    `order_number` VARCHAR(50) DEFAULT NULL,
    `is_verified` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `helpful_count` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('pending', 'approved', 'rejected', 'deleted') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_product_status` (`product_id`, `status`),
    KEY `idx_product_status_created` (`product_id`, `status`, `created_at`),
    KEY `idx_product_rating` (`product_id`, `rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2. product_review_statistics (리뷰 통계 테이블)

```sql
CREATE TABLE `product_review_statistics` (
    `product_id` INT(11) UNSIGNED NOT NULL,
    `total_rating_sum` DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '별점 합계',
    `total_review_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '리뷰 개수',
    `kindness_rating_sum` DECIMAL(12,2) DEFAULT 0 COMMENT '친절해요 합계',
    `kindness_review_count` INT(11) UNSIGNED DEFAULT 0 COMMENT '친절해요 리뷰 개수',
    `speed_rating_sum` DECIMAL(12,2) DEFAULT 0 COMMENT '설치 빨라요 합계',
    `speed_review_count` INT(11) UNSIGNED DEFAULT 0 COMMENT '설치 빨라요 리뷰 개수',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3. system_settings (시스템 설정 테이블)

```sql
CREATE TABLE `system_settings` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NOT NULL,
    `setting_type` ENUM('string', 'number', 'boolean', 'json') NOT NULL DEFAULT 'string',
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 통계 계산 방식

### 평균 별점 계산

```php
// 통계 테이블에서 합계와 개수를 가져와서 계산
$avgRating = $total_rating_sum / $total_review_count;
```

### 리뷰 추가 시

1. 리뷰가 `product_reviews` 테이블에 INSERT
2. 트리거가 자동으로 `product_review_statistics` 테이블 업데이트
   - `total_rating_sum`에 평점 추가
   - `total_review_count` 1 증가

### 리뷰 수정/삭제 시

- 통계 테이블은 변경하지 않음 (Immutable)
- 과거 통계 유지

## 주의사항

⚠️ **중요**: 마이그레이션 실행 시 기존 리뷰 데이터가 모두 삭제됩니다!

- 마이그레이션 전에 데이터베이스 백업 필수
- 운영 환경에서는 신중하게 실행
- 테스트 환경에서 먼저 검증 권장

## 마이그레이션 후 작업

1. **함수 수정**: `getProductReviews()`, `getProductAverageRating()` 등 상품별로만 조회하도록 수정
2. **관리자 페이지**: 리뷰 표시 방식 설정 UI 추가 (선택사항)
3. **테스트**: 리뷰 작성 및 통계 업데이트 확인

## 롤백 방법

마이그레이션을 되돌리려면:

1. 데이터베이스 백업에서 복원
2. 또는 기존 스키마로 다시 생성

## 문의

문제가 발생하면 데이터베이스 로그를 확인하거나 개발팀에 문의하세요.







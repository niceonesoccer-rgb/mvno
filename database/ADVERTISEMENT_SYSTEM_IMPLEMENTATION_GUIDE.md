# 광고 시스템 구현 가이드

## 📌 구인구직 사이트 카테고리 상단 광고 원리

### 핵심 원리
**카테고리 상단에 광고가 표시되는 이유는 SQL 정렬(ORDER BY)에서 광고 플래그를 우선순위로 사용하기 때문입니다.**

```sql
-- 일반 정렬 (광고 없음)
ORDER BY p.created_at DESC

-- 광고 시스템 적용 (광고 중인 상품이 상단에)
ORDER BY 
    p.is_advertising DESC,  -- 광고 중인 상품(1)이 먼저
    p.created_at DESC        -- 그 다음 최신순
```

### 작동 방식

1. **광고 신청 및 승인**
   - 판매자가 광고 신청
   - 관리자 승인 후 `products.is_advertising = 1` 설정
   - `products.advertisement_end_date`에 종료일 저장

2. **상품 목록 조회 시**
   - SQL 쿼리에서 `ORDER BY is_advertising DESC` 사용
   - `is_advertising = 1`인 상품이 자동으로 상단에 배치됨
   - `is_advertising = 0`인 일반 상품은 그 아래 표시

3. **광고 종료 시**
   - 크론잡이 매일 실행되어 만료된 광고 자동 해제
   - `is_advertising = 0`으로 변경
   - 일반 상품과 동일하게 정렬됨

---

## 🗄️ 데이터베이스 구조

### 1. products 테이블에 광고 컬럼 추가

```sql
-- products 테이블에 광고 관련 컬럼 추가
ALTER TABLE `products` 
ADD COLUMN `is_advertising` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '광고 진행 여부' AFTER `application_count`,
ADD COLUMN `advertisement_end_date` DATE DEFAULT NULL COMMENT '광고 종료일' AFTER `is_advertising`,
ADD KEY `idx_is_advertising` (`is_advertising`),
ADD KEY `idx_advertisement_end_date` (`advertisement_end_date`);
```

### 2. 광고 신청 테이블 생성

```sql
-- 광고 가격 설정 테이블
CREATE TABLE IF NOT EXISTS `product_advertisement_prices` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL COMMENT '상품 타입',
    `period_type` ENUM('week', 'month', 'quarter', 'half_year') NOT NULL COMMENT '기간 타입',
    `period_days` INT(11) UNSIGNED NOT NULL COMMENT '기간 일수 (7, 30, 90, 180)',
    `price` DECIMAL(12,2) NOT NULL COMMENT '광고 금액',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_type_period` (`product_type`, `period_type`),
    KEY `idx_product_type` (`product_type`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 광고 기간별 가격 설정';

-- 광고 신청 테이블
CREATE TABLE IF NOT EXISTS `product_advertisements` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
    `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 ID',
    `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL COMMENT '상품 타입',
    `period_type` ENUM('week', 'month', 'quarter', 'half_year') NOT NULL COMMENT '광고 기간 타입',
    `period_days` INT(11) UNSIGNED NOT NULL COMMENT '광고 기간 일수',
    `advertisement_price` DECIMAL(12,2) NOT NULL COMMENT '광고 금액 (신청 시점 금액)',
    `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending' COMMENT '결제 상태',
    `payment_method` VARCHAR(50) DEFAULT NULL COMMENT '결제 수단',
    `payment_id` VARCHAR(100) DEFAULT NULL COMMENT '결제 ID (외부 결제 시스템)',
    `status` ENUM('pending', 'approved', 'active', 'expired', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending' COMMENT '광고 상태',
    `start_date` DATE DEFAULT NULL COMMENT '광고 시작일',
    `end_date` DATE DEFAULT NULL COMMENT '광고 종료일',
    `rejected_reason` TEXT DEFAULT NULL COMMENT '거부 사유',
    `admin_id` VARCHAR(50) DEFAULT NULL COMMENT '처리한 관리자 ID',
    `approved_at` DATETIME DEFAULT NULL COMMENT '승인일시',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_seller_id` (`seller_id`),
    KEY `idx_product_type` (`product_type`),
    KEY `idx_status` (`status`),
    KEY `idx_payment_status` (`payment_status`),
    KEY `idx_start_end_date` (`start_date`, `end_date`),
    CONSTRAINT `fk_advertisement_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 광고 신청';
```

---

## 🔧 구현 방법

### 1. 상품 목록 조회 쿼리 수정

**기존 쿼리:**
```sql
SELECT p.*, ...
FROM products p
WHERE ...
ORDER BY p.created_at DESC
```

**광고 시스템 적용 후:**
```sql
SELECT p.*, ...
FROM products p
WHERE ...
ORDER BY 
    p.is_advertising DESC,  -- 광고 중인 상품 우선
    p.created_at DESC        -- 그 다음 최신순
```

### 2. 적용 위치

다음 파일들의 ORDER BY 절을 수정해야 합니다:

1. **`includes/data/plan-data.php`** - MVNO 상품 목록
   ```php
   // Line 450 부근
   $sql .= " ORDER BY p.is_advertising DESC, p.created_at DESC";
   ```

2. **`includes/data/phone-data.php`** - MNO 상품 목록
   ```php
   // ORDER BY 절에 is_advertising 추가
   ORDER BY p.is_advertising DESC, p.id DESC
   ```

3. **`seller/products/list.php`** - 판매자 상품 목록
   ```php
   // Line 119
   ORDER BY p.is_advertising DESC, p.created_at DESC
   ```

4. **`seller/products/mvno-list.php`** - 판매자 MVNO 목록
   ```php
   // ORDER BY 절 수정
   ORDER BY p.is_advertising DESC, p.created_at DESC
   ```

5. **`seller/products/mno-list.php`** - 판매자 MNO 목록
   ```php
   // ORDER BY 절 수정
   ORDER BY p.is_advertising DESC, p.id DESC
   ```

6. **`seller/products/internet-list.php`** - 판매자 Internet 목록
   ```php
   // ORDER BY 절 수정
   ORDER BY p.is_advertising DESC, p.created_at DESC
   ```

7. **`admin/products/mvno-list.php`** - 관리자 MVNO 목록
8. **`admin/products/mno-list.php`** - 관리자 MNO 목록
9. **`admin/products/internet-list.php`** - 관리자 Internet 목록

### 3. 광고 승인 시 products 테이블 업데이트

```php
// 광고 승인 시 실행할 코드
function approveAdvertisement($advertisementId, $adminId) {
    $pdo = getDBConnection();
    
    // 트랜잭션 시작
    $pdo->beginTransaction();
    
    try {
        // 광고 정보 가져오기
        $stmt = $pdo->prepare("
            SELECT product_id, period_days 
            FROM product_advertisements 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $advertisementId]);
        $ad = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ad) {
            throw new Exception("광고 정보를 찾을 수 없습니다.");
        }
        
        // 광고 상태 업데이트
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+{$ad['period_days']} days"));
        
        $stmt = $pdo->prepare("
            UPDATE product_advertisements 
            SET status = 'active',
                start_date = :start_date,
                end_date = :end_date,
                approved_at = NOW(),
                admin_id = :admin_id
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $advertisementId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':admin_id' => $adminId
        ]);
        
        // products 테이블 업데이트 (핵심!)
        $stmt = $pdo->prepare("
            UPDATE products 
            SET is_advertising = 1,
                advertisement_end_date = :end_date
            WHERE id = :product_id
        ");
        $stmt->execute([
            ':product_id' => $ad['product_id'],
            ':end_date' => $endDate
        ]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("광고 승인 실패: " . $e->getMessage());
        return false;
    }
}
```

### 4. 광고 종료 자동 처리 (크론잡)

**파일:** `cron/expire-advertisements.php`

```php
<?php
/**
 * 광고 종료 자동 처리 크론잡
 * 매일 자정에 실행 (Windows 작업 스케줄러 또는 Linux cron)
 */

require_once __DIR__ . '/../includes/data/db-config.php';

$pdo = getDBConnection();
if (!$pdo) {
    error_log("DB 연결 실패");
    exit(1);
}

try {
    // 만료된 광고 찾기
    $stmt = $pdo->prepare("
        SELECT id, product_id 
        FROM product_advertisements 
        WHERE status = 'active' 
        AND end_date < CURDATE()
    ");
    $stmt->execute();
    $expiredAds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $count = 0;
    foreach ($expiredAds as $ad) {
        $pdo->beginTransaction();
        
        try {
            // 광고 상태를 expired로 변경
            $stmt = $pdo->prepare("
                UPDATE product_advertisements 
                SET status = 'expired' 
                WHERE id = :id
            ");
            $stmt->execute([':id' => $ad['id']]);
            
            // products 테이블에서 광고 플래그 제거
            $stmt = $pdo->prepare("
                UPDATE products 
                SET is_advertising = 0,
                    advertisement_end_date = NULL
                WHERE id = :product_id
            ");
            $stmt->execute([':product_id' => $ad['product_id']]);
            
            $pdo->commit();
            $count++;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("광고 종료 처리 실패 (ID: {$ad['id']}): " . $e->getMessage());
        }
    }
    
    error_log("광고 종료 처리 완료: {$count}개");
} catch (Exception $e) {
    error_log("광고 종료 처리 오류: " . $e->getMessage());
    exit(1);
}
```

---

## 📊 광고 표시 예시

### 광고 없는 경우
```
상품 목록:
1. 상품 A (2025-01-15 생성)
2. 상품 B (2025-01-14 생성)
3. 상품 C (2025-01-13 생성)
```

### 광고 있는 경우
```
상품 목록:
1. [광고] 상품 B (2025-01-14 생성) ← 광고 중
2. 상품 A (2025-01-15 생성)
3. 상품 C (2025-01-13 생성)
```

**광고 중인 상품이 최신이 아니어도 상단에 표시됩니다!**

---

## 🎯 운영 시스템 요약

### 1. 광고 신청 프로세스
```
판매자 신청 
  → 결제 완료 
    → 관리자 승인 
      → products.is_advertising = 1 설정
        → 상품 목록 상단에 자동 표시
```

### 2. 광고 종료 프로세스
```
크론잡 실행 (매일 자정)
  → end_date < 오늘인 광고 찾기
    → products.is_advertising = 0 설정
      → 일반 상품과 동일하게 정렬
```

### 3. 핵심 포인트
- **`is_advertising` 컬럼**: 광고 여부를 나타내는 플래그 (0 또는 1)
- **ORDER BY 우선순위**: `is_advertising DESC`가 `created_at DESC`보다 먼저 적용
- **자동 관리**: 크론잡으로 만료된 광고 자동 해제

---

## ✅ 체크리스트

- [ ] `products` 테이블에 `is_advertising`, `advertisement_end_date` 컬럼 추가
- [ ] 광고 테이블 생성 (`product_advertisement_prices`, `product_advertisements`)
- [ ] 모든 상품 목록 조회 쿼리에 `ORDER BY is_advertising DESC` 추가
- [ ] 광고 승인 시 `products.is_advertising = 1` 업데이트 로직 구현
- [ ] 광고 종료 크론잡 생성 및 설정
- [ ] 광고 뱃지 UI 추가 (선택사항)

---

## 📝 참고

- 상세한 광고 시스템 설계는 `database/PRODUCT_ADVERTISEMENT_SYSTEM_PROPOSAL.md` 참고
- 광고 가격 설정, 승인 프로세스 등은 제안서에 상세히 설명되어 있습니다.

# 카테고리 상단 광고 시스템 제안서

## 📋 개요

구인구직 사이트처럼 **카테고리 페이지 상단에 광고 상품을 자동으로 배치**하는 시스템입니다.
판매자가 광고를 신청하면 해당 카테고리(MVNO, MNO, Internet) 상단에 자동으로 노출됩니다.

---

## 🎯 핵심 원리: 카테고리 상단 광고가 표시되는 방식

### 1. **SQL ORDER BY를 통한 우선순위 정렬**

카테고리 상단 광고는 **데이터베이스 쿼리의 정렬 순서**로 구현됩니다:

```sql
SELECT p.*, ...
FROM products p
WHERE p.product_type = 'mvno' AND p.status = 'active'
ORDER BY 
    p.is_advertising DESC,        -- 광고 중인 상품을 맨 위로 (1이 먼저)
    p.advertisement_priority DESC, -- 광고 우선순위 (높은 숫자가 먼저)
    p.created_at DESC              -- 일반 상품은 최신순
LIMIT 20;
```

**동작 방식:**
- `is_advertising = 1`인 상품이 먼저 표시됨
- 같은 광고 상품끼리는 `advertisement_priority` 값이 높은 순서대로
- 일반 상품(`is_advertising = 0`)은 그 다음에 최신순으로 표시

### 2. **운영 시스템 구조**

```
┌─────────────────────────────────────────────────┐
│  1. 판매자: 광고 신청                            │
│     └─> product_advertisements 테이블에 INSERT   │
│         status = 'pending'                       │
└─────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────┐
│  2. 관리자: 광고 승인                            │
│     └─> status = 'approved'                    │
│         start_date, end_date 설정               │
│         products.is_advertising = 1              │
│         products.advertisement_priority 설정     │
└─────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────┐
│  3. 시스템: 광고 활성화                          │
│     └─> start_date가 되면 자동으로              │
│         status = 'active'                       │
│         카테고리 상단에 자동 노출                │
└─────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────┐
│  4. 시스템: 광고 만료 (크론잡)                   │
│     └─> end_date가 지나면 자동으로              │
│         status = 'expired'                       │
│         products.is_advertising = 0              │
│         상단 노출 해제                          │
└─────────────────────────────────────────────────┘
```

---

## 🗄️ 데이터베이스 설계

### 1. products 테이블에 광고 컬럼 추가

```sql
ALTER TABLE `products` 
ADD COLUMN `is_advertising` TINYINT(1) NOT NULL DEFAULT 0 
    COMMENT '광고 진행 여부 (0: 일반, 1: 광고중)' 
    AFTER `application_count`,
ADD COLUMN `advertisement_priority` INT(11) NOT NULL DEFAULT 0 
    COMMENT '광고 우선순위 (높을수록 상단 노출)' 
    AFTER `is_advertising`,
ADD COLUMN `advertisement_end_date` DATE DEFAULT NULL 
    COMMENT '광고 종료일' 
    AFTER `advertisement_priority`,
ADD KEY `idx_is_advertising` (`is_advertising`),
ADD KEY `idx_advertisement_priority` (`advertisement_priority`, `is_advertising`),
ADD KEY `idx_advertisement_end_date` (`advertisement_end_date`);
```

**컬럼 설명:**
- `is_advertising`: 광고 여부 플래그 (0 또는 1)
- `advertisement_priority`: 광고 우선순위 (같은 카테고리 내에서 순서 결정)
- `advertisement_end_date`: 광고 종료일 (크론잡에서 만료 체크용)

### 2. 광고 가격 설정 테이블

```sql
CREATE TABLE IF NOT EXISTS `product_advertisement_prices` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL COMMENT '상품 타입',
    `period_type` ENUM('week', 'month', 'quarter', 'half_year') NOT NULL COMMENT '기간 타입',
    `period_days` INT(11) UNSIGNED NOT NULL COMMENT '기간 일수',
    `price` DECIMAL(12,2) NOT NULL COMMENT '광고 금액',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성화 여부',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_type_period` (`product_type`, `period_type`),
    KEY `idx_product_type` (`product_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='상품 광고 기간별 가격 설정';

-- 초기 데이터
INSERT INTO `product_advertisement_prices` (`product_type`, `period_type`, `period_days`, `price`) VALUES
('mvno', 'week', 7, 50000),
('mvno', 'month', 30, 180000),
('mvno', 'quarter', 90, 500000),
('mvno', 'half_year', 180, 900000),
('mno', 'week', 7, 60000),
('mno', 'month', 30, 220000),
('mno', 'quarter', 90, 600000),
('mno', 'half_year', 180, 1100000),
('internet', 'week', 7, 55000),
('internet', 'month', 30, 200000),
('internet', 'quarter', 90, 550000),
('internet', 'half_year', 180, 1000000);
```

### 3. 광고 신청 테이블

```sql
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
    `payment_id` VARCHAR(100) DEFAULT NULL COMMENT '결제 ID',
    `status` ENUM('pending', 'approved', 'active', 'expired', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending' COMMENT '광고 상태',
    `start_date` DATE DEFAULT NULL COMMENT '광고 시작일',
    `end_date` DATE DEFAULT NULL COMMENT '광고 종료일',
    `priority` INT(11) NOT NULL DEFAULT 0 COMMENT '광고 우선순위 (관리자 설정)',
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
    KEY `idx_start_end_date` (`start_date`, `end_date`),
    CONSTRAINT `fk_advertisement_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='상품 광고 신청';
```

**광고 상태 설명:**
- `pending`: 대기중 (결제 대기 또는 승인 대기)
- `approved`: 승인됨 (결제 완료 후 관리자 승인)
- `active`: 진행중 (광고 기간 내, 카테고리 상단 노출)
- `expired`: 종료됨 (광고 기간 만료)
- `rejected`: 거부됨
- `cancelled`: 취소됨

---

## 🔧 운영 시스템 구현

### 1. 카테고리 상품 목록 조회 (광고 우선 노출)

**기존 쿼리 수정 예시:**

#### MVNO 카테고리 (`mvno/mvno.php` 또는 `includes/data/plan-data.php`)

```php
// 기존
ORDER BY p.created_at DESC

// 변경 후
ORDER BY 
    p.is_advertising DESC,           -- 광고 상품 먼저
    p.advertisement_priority DESC,   -- 광고 우선순위
    p.created_at DESC                -- 일반 상품은 최신순
```

#### MNO 카테고리 (`mno/mno.php`)

```php
ORDER BY 
    p.is_advertising DESC,
    p.advertisement_priority DESC,
    p.id DESC
```

#### Internet 카테고리 (`internets/internets.php`)

```php
ORDER BY 
    p.is_advertising DESC,
    p.advertisement_priority DESC,
    p.created_at DESC
```

### 2. 광고 승인 프로세스 (관리자)

**파일:** `/admin/products/advertisement/approve.php`

```php
<?php
require_once __DIR__ . '/../../../includes/data/db-config.php';
require_once __DIR__ . '/../../../includes/data/auth-functions.php';

// 관리자 권한 체크
if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$advertisementId = $_POST['advertisement_id'] ?? null;
$action = $_POST['action'] ?? ''; // 'approve' or 'reject'
$priority = intval($_POST['priority'] ?? 0); // 광고 우선순위
$rejectedReason = $_POST['rejected_reason'] ?? '';

if (!$advertisementId || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

$pdo = getDBConnection();
$pdo->beginTransaction();

try {
    // 광고 정보 조회
    $stmt = $pdo->prepare("
        SELECT * FROM product_advertisements 
        WHERE id = :id AND status = 'pending'
    ");
    $stmt->execute([':id' => $advertisementId]);
    $ad = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ad) {
        throw new Exception('광고 정보를 찾을 수 없습니다.');
    }
    
    if ($action === 'approve') {
        // 광고 승인
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+{$ad['period_days']} days"));
        
        // 광고 상태 업데이트
        $updateStmt = $pdo->prepare("
            UPDATE product_advertisements 
            SET status = 'approved',
                start_date = :start_date,
                end_date = :end_date,
                priority = :priority,
                admin_id = :admin_id,
                approved_at = NOW()
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':id' => $advertisementId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
            ':priority' => $priority,
            ':admin_id' => getCurrentUser()['user_id']
        ]);
        
        // 상품 테이블 업데이트 (광고 플래그 설정)
        $productStmt = $pdo->prepare("
            UPDATE products 
            SET is_advertising = 1,
                advertisement_priority = :priority,
                advertisement_end_date = :end_date
            WHERE id = :product_id
        ");
        $productStmt->execute([
            ':product_id' => $ad['product_id'],
            ':priority' => $priority,
            ':end_date' => $endDate
        ]);
        
        // 광고 시작 (즉시 활성화)
        $activeStmt = $pdo->prepare("
            UPDATE product_advertisements 
            SET status = 'active'
            WHERE id = :id
        ");
        $activeStmt->execute([':id' => $advertisementId]);
        
        $pdo->commit();
        echo json_encode([
            'success' => true, 
            'message' => '광고가 승인되었습니다.',
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
    } else {
        // 광고 거부
        $rejectStmt = $pdo->prepare("
            UPDATE product_advertisements 
            SET status = 'rejected',
                rejected_reason = :reason,
                admin_id = :admin_id
            WHERE id = :id
        ");
        $rejectStmt->execute([
            ':id' => $advertisementId,
            ':reason' => $rejectedReason,
            ':admin_id' => getCurrentUser()['user_id']
        ]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => '광고가 거부되었습니다.']);
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("광고 승인 오류: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '처리 중 오류가 발생했습니다.']);
}
```

### 3. 광고 만료 자동 처리 (크론잡)

**파일:** `/cron/expire-advertisements.php`

```php
<?php
/**
 * 광고 만료 자동 처리 스크립트
 * 매일 자정에 실행 (Windows 작업 스케줄러 또는 Linux cron)
 */

require_once __DIR__ . '/../includes/data/db-config.php';

$pdo = getDBConnection();
if (!$pdo) {
    error_log("광고 만료 처리: DB 연결 실패");
    exit(1);
}

$today = date('Y-m-d');

try {
    $pdo->beginTransaction();
    
    // 오늘 날짜가 end_date를 지난 활성 광고 찾기
    $stmt = $pdo->prepare("
        SELECT id, product_id 
        FROM product_advertisements 
        WHERE status = 'active' 
        AND end_date < :today
    ");
    $stmt->execute([':today' => $today]);
    $expiredAds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $expiredCount = 0;
    
    foreach ($expiredAds as $ad) {
        // 광고 상태를 만료로 변경
        $updateStmt = $pdo->prepare("
            UPDATE product_advertisements 
            SET status = 'expired' 
            WHERE id = :id
        ");
        $updateStmt->execute([':id' => $ad['id']]);
        
        // 상품 테이블에서 광고 플래그 제거
        $productStmt = $pdo->prepare("
            UPDATE products 
            SET is_advertising = 0,
                advertisement_priority = 0,
                advertisement_end_date = NULL
            WHERE id = :product_id
        ");
        $productStmt->execute([':product_id' => $ad['product_id']]);
        
        $expiredCount++;
    }
    
    $pdo->commit();
    
    if ($expiredCount > 0) {
        error_log("광고 만료 처리 완료: {$expiredCount}개 광고 만료");
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("광고 만료 처리 오류: " . $e->getMessage());
    exit(1);
}
```

**Windows 작업 스케줄러 설정:**
```batch
# expire-advertisements.bat
@echo off
cd C:\xampp\htdocs\mvno
C:\xampp\php\php.exe cron\expire-advertisements.php
```

**Linux cron 설정:**
```bash
# 매일 자정에 실행
0 0 * * * /usr/bin/php /path/to/mvno/cron/expire-advertisements.php
```

### 4. 광고 시작일 자동 활성화 (선택사항)

광고 시작일이 되면 자동으로 활성화하는 크론잡:

```php
<?php
// cron/activate-advertisements.php
require_once __DIR__ . '/../includes/data/db-config.php';

$pdo = getDBConnection();
$today = date('Y-m-d');

// 오늘 시작일인 승인된 광고를 활성화
$stmt = $pdo->prepare("
    UPDATE product_advertisements 
    SET status = 'active'
    WHERE status = 'approved' 
    AND start_date = :today
");

$stmt->execute([':today' => $today]);
$activatedCount = $stmt->rowCount();

if ($activatedCount > 0) {
    error_log("광고 자동 활성화: {$activatedCount}개 광고 시작");
}
```

---

## 📱 사용자 화면 구현

### 1. 카테고리 페이지에 광고 뱃지 표시

**예시:** `mvno/mvno.php` 또는 상품 목록 템플릿

```php
<?php foreach ($products as $product): ?>
    <div class="product-card">
        <?php if ($product['is_advertising'] == 1): ?>
            <span class="ad-badge">광고</span>
        <?php endif; ?>
        
        <h3><?= htmlspecialchars($product['plan_name']) ?></h3>
        <!-- 상품 정보 -->
    </div>
<?php endforeach; ?>
```

**CSS 예시:**
```css
.ad-badge {
    display: inline-block;
    background: #ff6b6b;
    color: white;
    font-size: 12px;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: bold;
    margin-left: 8px;
}
```

### 2. 판매자 광고 신청 페이지

**경로:** `/seller/products/advertisement/register.php`

```php
<?php
// 자신의 상품 목록 조회
$stmt = $pdo->prepare("
    SELECT p.id, p.product_type,
           CASE p.product_type
               WHEN 'mvno' THEN mvno.plan_name
               WHEN 'mno' THEN mno.device_name
               WHEN 'internet' THEN inet.registration_place
           END AS product_name
    FROM products p
    LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id
    LEFT JOIN product_mno_details mno ON p.id = mno.product_id
    LEFT JOIN product_internet_details inet ON p.id = inet.product_id
    WHERE p.seller_id = :seller_id 
    AND p.status = 'active'
    ORDER BY p.created_at DESC
");
$stmt->execute([':seller_id' => $currentUser['user_id']]);
$myProducts = $stmt->fetchAll();
?>

<!-- 광고 신청 폼 -->
<form id="advertisementForm">
    <select name="product_id" required>
        <option value="">상품 선택</option>
        <?php foreach ($myProducts as $product): ?>
            <option value="<?= $product['id'] ?>" 
                    data-type="<?= $product['product_type'] ?>">
                [<?= strtoupper($product['product_type']) ?>] 
                <?= htmlspecialchars($product['product_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    
    <select name="period_type" required>
        <option value="">기간 선택</option>
        <option value="week">일주일 (7일)</option>
        <option value="month">한달 (30일)</option>
        <option value="quarter">3개월 (90일)</option>
        <option value="half_year">6개월 (180일)</option>
    </select>
    
    <div id="priceDisplay">광고 금액: -</div>
    
    <button type="submit">광고 신청하기</button>
</form>
```

### 3. 관리자 광고 승인 페이지

**경로:** `/admin/products/advertisement/manage.php`

```php
<?php
// 승인 대기 광고 목록
$stmt = $pdo->prepare("
    SELECT a.*, 
           CASE a.product_type
               WHEN 'mvno' THEN mvno.plan_name
               WHEN 'mno' THEN mno.device_name
               WHEN 'internet' THEN inet.registration_place
           END AS product_name,
           u.name AS seller_name
    FROM product_advertisements a
    LEFT JOIN products p ON a.product_id = p.id
    LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id AND a.product_type = 'mvno'
    LEFT JOIN product_mno_details mno ON p.id = mno.product_id AND a.product_type = 'mno'
    LEFT JOIN product_internet_details inet ON p.id = inet.product_id AND a.product_type = 'internet'
    LEFT JOIN users u ON a.seller_id = u.user_id
    WHERE a.status = 'pending'
    ORDER BY a.created_at DESC
");
$stmt->execute();
$pendingAds = $stmt->fetchAll();
?>

<table>
    <thead>
        <tr>
            <th>상품명</th>
            <th>판매자</th>
            <th>기간</th>
            <th>금액</th>
            <th>우선순위</th>
            <th>작업</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($pendingAds as $ad): ?>
            <tr>
                <td><?= htmlspecialchars($ad['product_name']) ?></td>
                <td><?= htmlspecialchars($ad['seller_name']) ?></td>
                <td><?= $ad['period_days'] ?>일</td>
                <td><?= number_format($ad['advertisement_price']) ?>원</td>
                <td>
                    <input type="number" 
                           id="priority_<?= $ad['id'] ?>" 
                           value="0" 
                           min="0" 
                           max="100">
                </td>
                <td>
                    <button onclick="approveAd(<?= $ad['id'] ?>)">승인</button>
                    <button onclick="rejectAd(<?= $ad['id'] ?>)">거부</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
```

---

## 🔄 전체 프로세스 흐름

```
1. 판매자 광고 신청
   ↓
   product_advertisements 테이블에 INSERT
   status = 'pending'
   payment_status = 'pending'
   
2. 결제 완료
   ↓
   payment_status = 'paid'
   
3. 관리자 승인
   ↓
   status = 'approved'
   start_date, end_date 설정
   products.is_advertising = 1
   products.advertisement_priority 설정
   
4. 광고 시작 (start_date)
   ↓
   status = 'active'
   카테고리 상단에 자동 노출
   (ORDER BY is_advertising DESC, advertisement_priority DESC)
   
5. 광고 만료 (end_date)
   ↓
   크론잡이 자동으로 처리
   status = 'expired'
   products.is_advertising = 0
   상단 노출 해제
```

---

## 📊 광고 우선순위 시스템

### 우선순위 설정 규칙

1. **기본 우선순위**: 0 (낮음)
2. **관리자 설정**: 1~100 (높을수록 상단 노출)
3. **자동 계산** (선택사항):
   - 결제 금액이 높을수록 우선순위 증가
   - 광고 기간이 길수록 우선순위 증가

**예시:**
```php
// 광고 금액과 기간에 따른 자동 우선순위 계산
$basePriority = 0;
$priceMultiplier = $advertisementPrice / 10000; // 만원당 1점
$periodMultiplier = $periodDays / 7; // 주당 1점
$autoPriority = intval($basePriority + $priceMultiplier + $periodMultiplier);

// 관리자가 수동으로 조정 가능
$finalPriority = $adminSetPriority > 0 ? $adminSetPriority : $autoPriority;
```

---

## ✅ 구현 체크리스트

### Phase 1: 데이터베이스 설정
- [ ] `products` 테이블에 광고 컬럼 추가
- [ ] `product_advertisement_prices` 테이블 생성
- [ ] `product_advertisements` 테이블 생성
- [ ] 초기 가격 데이터 입력

### Phase 2: 기본 기능
- [ ] 판매자 광고 신청 페이지
- [ ] 관리자 광고 승인 페이지
- [ ] 카테고리 목록 쿼리 수정 (ORDER BY 추가)
- [ ] 광고 뱃지 표시

### Phase 3: 자동화
- [ ] 광고 만료 크론잡 구현
- [ ] 광고 시작 자동 활성화 (선택)
- [ ] Windows/Linux 스케줄러 설정

### Phase 4: 고도화
- [ ] 광고 통계 페이지
- [ ] 광고 효과 분석
- [ ] 결제 시스템 연동

---

## 💡 핵심 정리

**카테고리 상단 광고가 표시되는 원리:**

1. **SQL ORDER BY 절**에서 `is_advertising DESC`를 첫 번째 정렬 기준으로 사용
2. 광고 중인 상품(`is_advertising = 1`)이 자동으로 상단에 배치됨
3. 같은 광고 상품끼리는 `advertisement_priority` 값으로 순서 결정
4. 일반 상품은 그 다음에 최신순으로 표시

**운영 시스템:**
- 관리자가 광고를 승인하면 `products.is_advertising = 1`로 설정
- 크론잡이 매일 실행되어 만료된 광고를 자동으로 해제
- 별도의 복잡한 로직 없이 **단순한 SQL 정렬**로 구현 가능

---

## 📝 참고사항

- 이 시스템은 구인구직 사이트(잡코리아, 사람인 등)에서 사용하는 표준 방식입니다
- 광고 상품 수가 많아지면 성능 최적화를 위해 인덱스가 중요합니다
- 광고 우선순위는 필요에 따라 더 세밀하게 조정 가능합니다 (예: 시간대별, 요일별)

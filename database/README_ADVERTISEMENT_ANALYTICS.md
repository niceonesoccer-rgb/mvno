# 광고 분석 시스템

## 📊 개요

광고 노출, 클릭, 통계를 추적하는 데이터베이스 테이블과 함수입니다.

## 🗄️ 데이터베이스 테이블

### 1. `advertisement_impressions` - 광고 노출 추적
- 광고가 화면에 표시될 때마다 기록
- 사용자 정보, 기기 정보, IP 주소 등 저장

### 2. `advertisement_clicks` - 광고 클릭 추적
- 사용자가 광고를 클릭할 때마다 기록
- 클릭 유형 (direct, detail, apply, other) 구분

### 3. `advertisement_analytics` - 광고 통계 집계
- 일별/시간별 통계를 집계하여 저장
- 노출 수, 클릭 수, CTR, 기기별 통계 등

## 🚀 설치 방법

### 1. 데이터베이스 테이블 생성

**방법 1: SQL 파일 직접 실행**
```sql
-- phpMyAdmin 또는 MySQL 클라이언트에서 실행
SOURCE database/create_advertisement_analytics_tables.sql;
```

**방법 2: PHP 스크립트 실행**
```bash
# 터미널에서
php database/create_advertisement_analytics_tables.php

# 또는 브라우저에서
http://localhost/MVNO/database/create_advertisement_analytics_tables.php
```

## 💻 사용 방법

### 1. 광고 노출 추적

```php
require_once __DIR__ . '/includes/data/advertisement-analytics-functions.php';

// 광고가 화면에 표시될 때
trackAdvertisementImpression(
    $advertisementId,  // rotation_advertisements.id
    $productId,          // products.id
    $sellerId,          // 판매자 ID
    $productType        // 'mvno', 'mno', 'internet', 'mno_sim'
);
```

### 2. 광고 클릭 추적

```php
// 사용자가 광고를 클릭할 때
trackAdvertisementClick(
    $advertisementId,
    $productId,
    $sellerId,
    $productType,
    'detail',           // 클릭 유형: 'direct', 'detail', 'apply', 'other'
    $targetUrl          // 클릭한 목적지 URL (선택사항)
);
```

### 3. 통계 집계

```php
// 일별 통계 집계 (크론잡에서 실행)
aggregateAdvertisementAnalytics($advertisementId, '2025-01-15');
// 또는 오늘 날짜로 집계
aggregateAdvertisementAnalytics($advertisementId);
```

## 📝 프론트엔드 연동 예시

### JavaScript 예시

```javascript
// 광고 노출 추적
function trackAdImpression(adId, productId, sellerId, productType) {
    fetch('/MVNO/api/track-ad-impression.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            advertisement_id: adId,
            product_id: productId,
            seller_id: sellerId,
            product_type: productType
        })
    });
}

// 광고 클릭 추적
function trackAdClick(adId, productId, sellerId, productType, clickType, targetUrl) {
    fetch('/MVNO/api/track-ad-click.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            advertisement_id: adId,
            product_id: productId,
            seller_id: sellerId,
            product_type: productType,
            click_type: clickType,
            target_url: targetUrl
        })
    });
}

// 광고 카드가 화면에 표시될 때
document.querySelectorAll('.advertisement-card').forEach(card => {
    const adId = card.dataset.advertisementId;
    const productId = card.dataset.productId;
    const sellerId = card.dataset.sellerId;
    const productType = card.dataset.productType;
    
    // Intersection Observer로 노출 감지
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                trackAdImpression(adId, productId, sellerId, productType);
                observer.unobserve(entry.target);
            }
        });
    });
    
    observer.observe(card);
    
    // 클릭 이벤트
    card.addEventListener('click', () => {
        trackAdClick(adId, productId, sellerId, productType, 'direct', card.href);
    });
});
```

## 🔧 API 엔드포인트 예시

### `api/track-ad-impression.php`

```php
<?php
require_once __DIR__ . '/../includes/data/advertisement-analytics-functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST 요청만 허용됩니다.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$result = trackAdvertisementImpression(
    $data['advertisement_id'] ?? 0,
    $data['product_id'] ?? 0,
    $data['seller_id'] ?? '',
    $data['product_type'] ?? ''
);

echo json_encode(['success' => $result]);
```

### `api/track-ad-click.php`

```php
<?php
require_once __DIR__ . '/../includes/data/advertisement-analytics-functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST 요청만 허용됩니다.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$result = trackAdvertisementClick(
    $data['advertisement_id'] ?? 0,
    $data['product_id'] ?? 0,
    $data['seller_id'] ?? '',
    $data['product_type'] ?? '',
    $data['click_type'] ?? 'direct',
    $data['target_url'] ?? null
);

echo json_encode(['success' => $result]);
```

## 📊 통계 조회 예시

```php
// 특정 광고의 일별 통계
$stmt = $pdo->prepare("
    SELECT * FROM advertisement_analytics
    WHERE advertisement_id = :ad_id
    AND stat_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY stat_date DESC
");
$stmt->execute([':ad_id' => $advertisementId]);
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 판매자별 광고 통계
$stmt = $pdo->prepare("
    SELECT 
        seller_id,
        SUM(impression_count) as total_impressions,
        SUM(click_count) as total_clicks,
        AVG(ctr) as avg_ctr
    FROM advertisement_analytics
    WHERE seller_id = :seller_id
    AND stat_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY seller_id
");
$stmt->execute([':seller_id' => $sellerId]);
$sellerStats = $stmt->fetch(PDO::FETCH_ASSOC);
```

## 🗑️ 데이터 삭제

`admin/settings/data-delete.php`에서 광고 삭제 시 분석 데이터도 함께 삭제됩니다.

- `advertisement_impressions` - 노출 데이터
- `advertisement_clicks` - 클릭 데이터
- `advertisement_analytics` - 통계 집계 데이터

## ⚙️ 크론잡 설정 (통계 집계)

매일 자정에 전날 통계를 집계하려면:

**Windows 작업 스케줄러:**
```
프로그램: C:\xampp\php\php.exe
인수: C:\xampp\htdocs\mvno\admin\cron\aggregate-ad-analytics.php
일정: 매일 00:00
```

**Linux Cron:**
```cron
0 0 * * * /usr/bin/php /path/to/mvno/admin/cron/aggregate-ad-analytics.php
```

### `admin/cron/aggregate-ad-analytics.php` 예시

```php
<?php
require_once __DIR__ . '/../../includes/data/advertisement-analytics-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

$pdo = getDBConnection();
if (!$pdo) {
    error_log("광고 통계 집계 실패: DB 연결 실패");
    exit(1);
}

// 어제 날짜
$yesterday = date('Y-m-d', strtotime('-1 day'));

// 활성 광고 목록 가져오기
$stmt = $pdo->query("
    SELECT id FROM rotation_advertisements
    WHERE status = 'active'
    AND DATE(start_datetime) <= '{$yesterday}'
    AND DATE(end_datetime) >= '{$yesterday}'
");

$count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (aggregateAdvertisementAnalytics($row['id'], $yesterday)) {
        $count++;
    }
}

error_log("광고 통계 집계 완료: {$count}개 광고 ({$yesterday})");
```

## 📈 성능 고려사항

1. **인덱스**: 테이블에 적절한 인덱스가 설정되어 있습니다.
2. **파티셔닝**: 데이터가 많아지면 날짜별 파티셔닝을 고려하세요.
3. **정리**: 오래된 데이터는 주기적으로 정리하세요 (예: 1년 이상 된 데이터).

## 🔍 참고사항

- 외래키 제약조건으로 `rotation_advertisements` 삭제 시 분석 데이터도 자동 삭제됩니다 (CASCADE).
- `advertisement_analytics`는 집계 데이터이므로 원본 데이터(`impressions`, `clicks`)와 별도로 관리됩니다.
- 통계 집계는 선택사항이며, 실시간 조회가 필요하면 원본 데이터를 직접 집계하세요.

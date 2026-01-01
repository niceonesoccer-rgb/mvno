<?php
/**
 * MVNO 광고 상품 디버깅 스크립트
 * 브라우저에서 직접 실행하여 결과 확인
 */

require_once 'includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    die("DB 연결 실패");
}

echo "<h2>MVNO 광고 상품 디버깅</h2>";
echo "<pre>";

// 1. 현재 시간 확인
echo "=== 현재 시간 ===\n";
echo date('Y-m-d H:i:s') . "\n\n";

// 2. rotation_advertisements 테이블의 product_type 값 종류 확인
echo "=== rotation_advertisements 테이블의 product_type 값 종류 ===\n";
$stmt = $pdo->query("SELECT DISTINCT product_type FROM rotation_advertisements ORDER BY product_type");
$types = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($types as $type) {
    echo "  - $type\n";
}
if (empty($types)) {
    echo "  (없음)\n";
}
echo "\n";

// 3. 모든 알뜰폰 관련 광고 조회 (product_type이 mvno 포함)
echo "=== product_type에 'mvno' 포함된 모든 광고 ===\n";
$stmt = $pdo->query("
    SELECT id, product_id, product_type, status, start_datetime, end_datetime, created_at
    FROM rotation_advertisements 
    WHERE product_type LIKE '%mvno%' 
    ORDER BY created_at DESC 
    LIMIT 10
");
$ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($ads)) {
    echo "  없음\n";
} else {
    foreach ($ads as $ad) {
        echo "  ID: {$ad['id']}, product_id: {$ad['product_id']}, product_type: '{$ad['product_type']}', status: '{$ad['status']}', start: {$ad['start_datetime']}, end: {$ad['end_datetime']}\n";
    }
}
echo "\n";

// 4. 광고 헬퍼 함수에서 사용하는 쿼리와 동일한 조건으로 조회
echo "=== 광고 헬퍼 함수 조건 (product_type = 'mvno') ===\n";
$stmt = $pdo->prepare("
    SELECT 
        ra.id as ad_id,
        ra.product_id,
        p.id as product_table_id,
        ra.product_type,
        ra.status as ad_status,
        p.status as product_status,
        ra.start_datetime,
        ra.end_datetime,
        NOW() as current_datetime
    FROM rotation_advertisements ra
    INNER JOIN products p ON ra.product_id = p.id
    LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id
    WHERE ra.product_type = 'mvno'
    ORDER BY ra.created_at DESC
    LIMIT 10
");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($results)) {
    echo "  없음\n";
} else {
    foreach ($results as $row) {
        echo "  ad_id: {$row['ad_id']}, product_id: {$row['product_id']}, product_table_id: {$row['product_table_id']}, ";
        echo "product_type: '{$row['product_type']}', ad_status: '{$row['ad_status']}', product_status: '{$row['product_status']}', ";
        echo "end_datetime: {$row['end_datetime']}, current_datetime: {$row['current_datetime']}\n";
    }
}
echo "\n";

// 5. 정확한 조건으로 조회 (광고 헬퍼 함수와 동일)
echo "=== 광고 헬퍼 함수 정확한 조건 ===\n";
$stmt = $pdo->prepare("
    SELECT 
        ra.product_id,
        p.id,
        ra.product_type,
        ra.status as ad_status,
        p.status as product_status,
        ra.end_datetime,
        mvno.product_id as mvno_product_id
    FROM rotation_advertisements ra
    INNER JOIN products p ON ra.product_id = p.id
    INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
    WHERE ra.product_type = 'mvno'
    AND ra.status = 'active'
    AND p.status = 'active'
    AND ra.end_datetime > NOW()
    ORDER BY ra.display_order ASC, ra.created_at ASC
");
$stmt->execute();
$finalResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($finalResults)) {
    echo "  없음 (이것이 문제입니다!)\n";
    echo "\n  각 조건을 개별적으로 확인:\n";
    
    // 각 조건별로 확인
    echo "\n  a) product_type = 'mvno'인 광고:\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM rotation_advertisements WHERE product_type = 'mvno'");
    $count = $stmt->fetchColumn();
    echo "    개수: $count\n";
    
    echo "\n  b) status = 'active'인 광고:\n";
    $stmt = $pdo->query("SELECT id, product_id, product_type, status, end_datetime FROM rotation_advertisements WHERE product_type = 'mvno'");
    $allMvnoAds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allMvnoAds as $ad) {
        echo "    ID: {$ad['id']}, product_id: {$ad['product_id']}, status: '{$ad['status']}', end_datetime: {$ad['end_datetime']}\n";
    }
    
    echo "\n  c) end_datetime > NOW() 조건:\n";
    echo "    현재 시간: " . date('Y-m-d H:i:s') . "\n";
    foreach ($allMvnoAds as $ad) {
        $isValid = strtotime($ad['end_datetime']) > time();
        echo "    ID: {$ad['id']}, end_datetime: {$ad['end_datetime']}, 유효: " . ($isValid ? '예' : '아니오') . "\n";
    }
    
    echo "\n  d) products 테이블 확인:\n";
    if (!empty($allMvnoAds)) {
        foreach ($allMvnoAds as $ad) {
            $stmt = $pdo->prepare("SELECT id, product_type, status FROM products WHERE id = ?");
            $stmt->execute([$ad['product_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($product) {
                echo "    product_id: {$ad['product_id']}, products.status: '{$product['status']}', products.product_type: '{$product['product_type']}'\n";
            } else {
                echo "    product_id: {$ad['product_id']}, products 테이블에 존재하지 않음\n";
            }
        }
    }
    
    echo "\n  e) product_mvno_details와의 조인:\n";
    if (!empty($allMvnoAds)) {
        foreach ($allMvnoAds as $ad) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_mvno_details WHERE product_id = ?");
            $stmt->execute([$ad['product_id']]);
            $hasDetails = $stmt->fetchColumn();
            echo "    product_id: {$ad['product_id']}, product_mvno_details 존재: " . ($hasDetails ? '예' : '아니오') . "\n";
        }
    }
} else {
    echo "  조회 성공! (" . count($finalResults) . "개)\n";
    foreach ($finalResults as $row) {
        echo "    product_id: {$row['product_id']}, id: {$row['id']}\n";
    }
}

echo "</pre>";
?>

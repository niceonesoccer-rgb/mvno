<?php
/**
 * 월 요금제 데이터 확인 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/check-monthly-fee.php
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>월 요금제 데이터 확인</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #10b981; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #10b981; color: white; font-weight: 600; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f0f0f0; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        .success { color: #10b981; font-weight: bold; }
        .info { color: #3b82f6; }
        .raw-value { font-family: monospace; background: #f3f4f6; padding: 2px 6px; border-radius: 3px; }
        .numeric-value { font-weight: bold; color: #1f2937; }
    </style>
</head>
<body>
<div class='container'>";

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        echo "<p class='error'>✗ 데이터베이스 연결 실패</p>";
        exit;
    }
    
    echo "<h1>월 요금제 데이터 확인</h1>";
    echo "<p class='success'>✓ 데이터베이스 연결 성공</p>";
    
    // 1. 전체 인터넷 상품의 monthly_fee 확인
    echo "<h2>1. 전체 인터넷 상품 월 요금제 현황</h2>";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN monthly_fee = '' OR monthly_fee IS NULL THEN 1 ELSE 0 END) as empty_count,
            SUM(CASE WHEN CAST(REPLACE(REPLACE(monthly_fee, '원', ''), ',', '') AS UNSIGNED) >= 1000000 THEN 1 ELSE 0 END) as over_1m_count,
            SUM(CASE WHEN CAST(REPLACE(REPLACE(monthly_fee, '원', ''), ',', '') AS UNSIGNED) >= 200000 THEN 1 ELSE 0 END) as over_200k_count
        FROM product_internet_details
    ");
    $stats = $stmt->fetch();
    echo "<table>";
    echo "<tr><th>전체 상품 수</th><th>빈 값</th><th>100만원 이상</th><th>20만원 이상</th></tr>";
    echo "<tr><td>{$stats['total']}</td><td>{$stats['empty_count']}</td><td class='error'>{$stats['over_1m_count']}</td><td class='warning'>{$stats['over_200k_count']}</td></tr>";
    echo "</table>";
    
    // 2. 100만원 이상인 상품 상세 조회
    echo "<h2>2. 100만원 이상인 상품 상세 정보</h2>";
    $stmt = $pdo->query("
        SELECT 
            p.id as product_id,
            p.seller_id,
            p.status,
            p.created_at,
            internet.registration_place,
            internet.service_type,
            internet.speed_option,
            internet.monthly_fee,
            LENGTH(internet.monthly_fee) as fee_length,
            CHAR_LENGTH(internet.monthly_fee) as fee_char_length,
            CAST(REPLACE(REPLACE(internet.monthly_fee, '원', ''), ',', '') AS UNSIGNED) as numeric_value
        FROM products p
        INNER JOIN product_internet_details internet ON p.id = internet.product_id
        WHERE CAST(REPLACE(REPLACE(internet.monthly_fee, '원', ''), ',', '') AS UNSIGNED) >= 1000000
        ORDER BY numeric_value DESC, p.created_at DESC
        LIMIT 20
    ");
    $highFeeProducts = $stmt->fetchAll();
    
    if (count($highFeeProducts) > 0) {
        echo "<table>";
        echo "<tr>
            <th>상품ID</th>
            <th>판매자ID</th>
            <th>가입처</th>
            <th>결합여부</th>
            <th>속도</th>
            <th>원본 값 (RAW)</th>
            <th>문자열 길이</th>
            <th>숫자 값</th>
            <th>상태</th>
            <th>등록일</th>
        </tr>";
        
        foreach ($highFeeProducts as $product) {
            $rawValue = htmlspecialchars($product['monthly_fee']);
            $numericValue = number_format($product['numeric_value']);
            $suggestedValue = number_format(floor($product['numeric_value'] / 100));
            
            echo "<tr>";
            echo "<td>{$product['product_id']}</td>";
            echo "<td>{$product['seller_id']}</td>";
            echo "<td>" . htmlspecialchars($product['registration_place'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($product['service_type'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($product['speed_option'] ?? '-') . "</td>";
            echo "<td><span class='raw-value'>{$rawValue}</span></td>";
            echo "<td>{$product['fee_length']} bytes / {$product['fee_char_length']} chars</td>";
            echo "<td class='error numeric-value'>{$numericValue}원</td>";
            echo "<td><span class='info'>예상값: {$suggestedValue}원</span></td>";
            echo "<td>" . htmlspecialchars($product['status']) . "</td>";
            echo "<td>" . htmlspecialchars($product['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='success'>✓ 100만원 이상인 상품이 없습니다.</p>";
    }
    
    // 3. 최근 주문에서 monthly_fee 확인
    echo "<h2>3. 최근 인터넷 주문의 월 요금제 값</h2>";
    $stmt = $pdo->query("
        SELECT 
            a.id as application_id,
            a.order_number,
            a.created_at,
            internet.monthly_fee,
            CAST(REPLACE(REPLACE(internet.monthly_fee, '원', ''), ',', '') AS UNSIGNED) as numeric_value,
            c.additional_info
        FROM product_applications a
        INNER JOIN products p ON a.product_id = p.id
        INNER JOIN product_internet_details internet ON p.id = internet.product_id
        LEFT JOIN application_customers c ON a.id = c.application_id
        WHERE p.product_type = 'internet'
        ORDER BY a.created_at DESC
        LIMIT 10
    ");
    $recentOrders = $stmt->fetchAll();
    
    if (count($recentOrders) > 0) {
        echo "<table>";
        echo "<tr>
            <th>주문번호</th>
            <th>신청ID</th>
            <th>월 요금제 (RAW)</th>
            <th>숫자 값</th>
            <th>주문일시</th>
        </tr>";
        
        foreach ($recentOrders as $order) {
            $rawValue = htmlspecialchars($order['monthly_fee']);
            $numericValue = $order['numeric_value'];
            $isHigh = $numericValue >= 1000000;
            
            // additional_info에서 product_snapshot 확인
            $additionalInfo = json_decode($order['additional_info'] ?? '{}', true);
            $snapshotFee = $additionalInfo['product_snapshot']['monthly_fee'] ?? null;
            
            echo "<tr" . ($isHigh ? " style='background-color: #fee2e2;'" : "") . ">";
            echo "<td>" . htmlspecialchars($order['order_number'] ?? '-') . "</td>";
            echo "<td>{$order['application_id']}</td>";
            echo "<td><span class='raw-value'>{$rawValue}</span></td>";
            echo "<td class='" . ($isHigh ? 'error' : '') . " numeric-value'>" . number_format($numericValue) . "원</td>";
            if ($snapshotFee) {
                echo "<td><span class='info'>스냅샷: " . htmlspecialchars($snapshotFee) . "</span></td>";
            } else {
                echo "<td>-</td>";
            }
            echo "<td>" . htmlspecialchars($order['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. 월 요금제 값 분포 확인
    echo "<h2>4. 월 요금제 값 분포</h2>";
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN monthly_fee = '' OR monthly_fee IS NULL THEN '빈 값'
                WHEN CAST(REPLACE(REPLACE(monthly_fee, '원', ''), ',', '') AS UNSIGNED) >= 1000000 THEN '100만원 이상'
                WHEN CAST(REPLACE(REPLACE(monthly_fee, '원', ''), ',', '') AS UNSIGNED) >= 200000 THEN '20만원~100만원'
                WHEN CAST(REPLACE(REPLACE(monthly_fee, '원', ''), ',', '') AS UNSIGNED) >= 100000 THEN '10만원~20만원'
                WHEN CAST(REPLACE(REPLACE(monthly_fee, '원', ''), ',', '') AS UNSIGNED) >= 50000 THEN '5만원~10만원'
                WHEN CAST(REPLACE(REPLACE(monthly_fee, '원', ''), ',', '') AS UNSIGNED) >= 10000 THEN '1만원~5만원'
                ELSE '1만원 미만'
            END as fee_range,
            COUNT(*) as count
        FROM product_internet_details
        GROUP BY fee_range
        ORDER BY 
            CASE fee_range
                WHEN '빈 값' THEN 0
                WHEN '100만원 이상' THEN 1
                WHEN '20만원~100만원' THEN 2
                WHEN '10만원~20만원' THEN 3
                WHEN '5만원~10만원' THEN 4
                WHEN '1만원~5만원' THEN 5
                ELSE 6
            END
    ");
    $distribution = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>금액 범위</th><th>상품 수</th></tr>";
    foreach ($distribution as $dist) {
        $class = '';
        if ($dist['fee_range'] === '100만원 이상') {
            $class = 'error';
        } elseif ($dist['fee_range'] === '20만원~100만원') {
            $class = 'warning';
        }
        echo "<tr><td class='{$class}'>{$dist['fee_range']}</td><td>{$dist['count']}</td></tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p class='error'>데이터베이스 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
?>









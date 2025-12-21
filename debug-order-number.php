<?php
/**
 * 주문번호 디버깅 스크립트
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    die('DB 연결 실패');
}

// 최근 주문 10개 확인
$stmt = $pdo->query("
    SELECT 
        pa.id,
        pa.order_number,
        pa.created_at,
        ac.name,
        ac.phone
    FROM product_applications pa
    LEFT JOIN application_customers ac ON pa.id = ac.application_id
    ORDER BY pa.id DESC
    LIMIT 10
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>최근 주문 10개 - 주문번호 확인</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>주문ID</th><th>DB 주문번호</th><th>생성일시</th><th>고객명</th><th>전화번호</th><th>판매자페이지 표시</th></tr>";

foreach ($orders as $order) {
    $orderId = $order['id'];
    $dbOrderNumber = $order['order_number'];
    
    // 판매자 페이지에서 표시되는 주문번호 계산
    $displayOrderNumber = '';
    if (!empty($order['order_number'])) {
        $displayOrderNumber = $order['order_number'];
    } else {
        $createdAt = new DateTime($order['created_at']);
        $displayOrderNumber = $createdAt->format('ymdH') . '-' . str_pad($orderId, 4, '0', STR_PAD_LEFT);
    }
    
    $isDifferent = ($dbOrderNumber !== $displayOrderNumber && !empty($dbOrderNumber));
    
    echo "<tr" . ($isDifferent ? " style='background-color: #ffcccc;'" : "") . ">";
    echo "<td>" . htmlspecialchars($orderId) . "</td>";
    echo "<td>" . ($dbOrderNumber ? htmlspecialchars($dbOrderNumber) : '<span style="color: red;">NULL</span>') . "</td>";
    echo "<td>" . htmlspecialchars($order['created_at']) . "</td>";
    echo "<td>" . htmlspecialchars($order['name'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($order['phone'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($displayOrderNumber) . ($isDifferent ? ' <span style="color: red;">⚠️ 다름!</span>' : '') . "</td>";
    echo "</tr>";
}

echo "</table>";

// 중복 확인
$stmt = $pdo->query("
    SELECT order_number, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids
    FROM product_applications 
    WHERE order_number IS NOT NULL AND order_number != ''
    GROUP BY order_number
    HAVING COUNT(*) > 1
");
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($duplicates)) {
    echo "<h2 style='color: red;'>중복된 주문번호 발견!</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>주문번호</th><th>중복 횟수</th><th>주문 ID</th></tr>";
    foreach ($duplicates as $dup) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($dup['order_number']) . "</td>";
        echo "<td>" . htmlspecialchars($dup['count']) . "</td>";
        echo "<td>" . htmlspecialchars($dup['ids']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

















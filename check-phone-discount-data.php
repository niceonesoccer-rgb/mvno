<?php
/**
 * 통신사폰 할인방법 데이터 확인 스크립트
 * Galaxy S23 (id=33) 등 특정 상품의 할인 데이터 확인
 */

require_once __DIR__ . '/includes/data/db-config.php';

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

// 확인할 상품 ID
$phone_ids = [33, 486, 426]; // Galaxy S23, iPhone 16 Pro Max, Xiaomi 13

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>통신사폰 할인방법 데이터 확인</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 30px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .json { background-color: #f9f9f9; padding: 10px; font-family: monospace; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>통신사폰 할인방법 데이터 확인</h1>";

foreach ($phone_ids as $phone_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                mno.device_name,
                mno.device_capacity,
                mno.common_provider,
                mno.common_discount_new,
                mno.common_discount_port,
                mno.common_discount_change,
                mno.contract_provider,
                mno.contract_discount_new,
                mno.contract_discount_port,
                mno.contract_discount_change,
                mno.contract_period_value
            FROM products p
            INNER JOIN product_mno_details mno ON p.id = mno.product_id
            WHERE p.id = ?
        ");
        $stmt->execute([$phone_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            echo "<h2>상품 ID: {$phone_id} - {$product['device_name']} {$product['device_capacity']}</h2>";
            echo "<table>";
            echo "<tr><th>필드</th><th>값</th></tr>";
            echo "<tr><td>common_provider</td><td class='json'>" . htmlspecialchars($product['common_provider'] ?? 'NULL') . "</td></tr>";
            echo "<tr><td>common_discount_new</td><td class='json'>" . htmlspecialchars($product['common_discount_new'] ?? 'NULL') . "</td></tr>";
            echo "<tr><td>common_discount_port</td><td class='json'>" . htmlspecialchars($product['common_discount_port'] ?? 'NULL') . "</td></tr>";
            echo "<tr><td>common_discount_change</td><td class='json'>" . htmlspecialchars($product['common_discount_change'] ?? 'NULL') . "</td></tr>";
            echo "<tr><td>contract_provider</td><td class='json'>" . htmlspecialchars($product['contract_provider'] ?? 'NULL') . "</td></tr>";
            echo "<tr><td>contract_discount_new</td><td class='json'>" . htmlspecialchars($product['contract_discount_new'] ?? 'NULL') . "</td></tr>";
            echo "<tr><td>contract_discount_port</td><td class='json'>" . htmlspecialchars($product['contract_discount_port'] ?? 'NULL') . "</td></tr>";
            echo "<tr><td>contract_discount_change</td><td class='json'>" . htmlspecialchars($product['contract_discount_change'] ?? 'NULL') . "</td></tr>";
            echo "<tr><td>contract_period_value</td><td>" . htmlspecialchars($product['contract_period_value'] ?? 'NULL') . "</td></tr>";
            echo "</table>";
            
            // getPhonesByIds로 변환된 데이터 확인
            require_once __DIR__ . '/includes/data/phone-data.php';
            $phones = getPhonesByIds([$phone_id]);
            if (!empty($phones)) {
                $phone = $phones[0];
                echo "<h3>변환된 데이터 (getPhonesByIds)</h3>";
                echo "<table>";
                echo "<tr><th>필드</th><th>값</th></tr>";
                echo "<tr><td>common_support</td><td class='json'>" . htmlspecialchars(json_encode($phone['common_support'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "</td></tr>";
                echo "<tr><td>contract_support</td><td class='json'>" . htmlspecialchars(json_encode($phone['contract_support'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "</td></tr>";
                echo "<tr><td>maintenance_period</td><td>" . htmlspecialchars($phone['maintenance_period'] ?? 'NULL') . "</td></tr>";
                echo "</table>";
            }
        } else {
            echo "<p>상품 ID {$phone_id}를 찾을 수 없습니다.</p>";
        }
    } catch (PDOException $e) {
        echo "<p>에러 (상품 ID {$phone_id}): " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

echo "</body></html>";
?>

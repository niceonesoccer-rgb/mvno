<?php
/**
 * 통신사단독유심 주문 DB 구조 확인 및 수정 스크립트
 * 
 * 문제점:
 * 1. product_applications 테이블의 product_type ENUM에 'mno-sim'이 없을 수 있음
 * 2. 주문 저장 시 product_snapshot에 올바른 데이터가 저장되는지 확인
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>통신사단독유심 주문 DB 확인</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; margin: 10px 0; }
        .warning { color: #856404; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; margin: 10px 0; }
        .info { color: #004085; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; margin: 10px 0; }
        pre { background: #f4f4f4; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <h1>통신사단독유심 주문 DB 구조 확인</h1>
    
    <?php
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            echo '<div class="error">데이터베이스 연결 실패</div>';
            exit;
        }
        
        // 1. product_applications 테이블의 product_type ENUM 확인
        echo '<h2>1. product_applications 테이블의 product_type ENUM 확인</h2>';
        $stmt = $pdo->query("SHOW COLUMNS FROM product_applications WHERE Field = 'product_type'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($column) {
            $enumType = $column['Type'];
            echo '<div class="info">현재 product_type ENUM: ' . htmlspecialchars($enumType) . '</div>';
            
            if (strpos($enumType, 'mno-sim') === false) {
                echo '<div class="error">❌ product_type ENUM에 \'mno-sim\'이 없습니다!</div>';
                echo '<div class="warning">다음 SQL을 실행하여 수정하세요:</div>';
                echo '<pre>ALTER TABLE `product_applications` MODIFY COLUMN `product_type` ENUM(\'mvno\', \'mno\', \'internet\', \'mno-sim\') NOT NULL COMMENT \'상품 타입\';</pre>';
                
                // 자동 수정 옵션
                if (isset($_GET['fix']) && $_GET['fix'] === '1') {
                    try {
                        $pdo->exec("ALTER TABLE `product_applications` MODIFY COLUMN `product_type` ENUM('mvno', 'mno', 'internet', 'mno-sim') NOT NULL COMMENT '상품 타입'");
                        echo '<div class="success">✅ product_type ENUM에 \'mno-sim\' 추가 완료!</div>';
                    } catch (PDOException $e) {
                        echo '<div class="error">❌ 수정 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                } else {
                    echo '<p><a href="?fix=1" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">자동 수정 실행</a></p>';
                }
            } else {
                echo '<div class="success">✅ product_type ENUM에 \'mno-sim\'이 포함되어 있습니다.</div>';
            }
        }
        
        // 2. mno-sim 주문 개수 확인
        echo '<h2>2. mno-sim 주문 개수 확인</h2>';
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM product_applications WHERE product_type = 'mno-sim'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $mnoSimCount = $result['cnt'] ?? 0;
            echo '<div class="info">mno-sim 타입 주문 개수: ' . $mnoSimCount . '개</div>';
            
            // 잘못된 타입으로 저장된 mno-sim 주문 확인
            $stmt = $pdo->query("
                SELECT COUNT(*) as cnt 
                FROM product_applications a
                INNER JOIN products p ON a.product_id = p.id
                WHERE p.product_type = 'mno-sim' AND a.product_type != 'mno-sim'
            ");
            $wrongType = $stmt->fetch(PDO::FETCH_ASSOC);
            $wrongTypeCount = $wrongType['cnt'] ?? 0;
            
            if ($wrongTypeCount > 0) {
                echo '<div class="error">⚠️ 잘못된 타입으로 저장된 주문: ' . $wrongTypeCount . '개</div>';
                echo '<p>이 주문들은 products 테이블은 mno-sim이지만 product_applications는 다른 타입으로 저장되어 있습니다.</p>';
            }
        } catch (PDOException $e) {
            echo '<div class="error">mno-sim 주문 조회 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        // 3. product_snapshot 확인 (샘플)
        echo '<h2>3. product_snapshot 데이터 확인 (샘플 5개)</h2>';
        try {
            $stmt = $pdo->prepare("
                SELECT a.id, a.order_number, a.product_type, c.additional_info
                FROM product_applications a
                INNER JOIN application_customers c ON a.id = c.application_id
                INNER JOIN products p ON a.product_id = p.id
                WHERE p.product_type = 'mno-sim'
                ORDER BY a.created_at DESC
                LIMIT 5
            ");
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($orders) > 0) {
                echo '<table>';
                echo '<tr><th>주문번호</th><th>product_type</th><th>product_snapshot 존재</th><th>plan_name</th><th>provider</th></tr>';
                
                foreach ($orders as $order) {
                    $additionalInfo = json_decode($order['additional_info'] ?? '{}', true) ?: [];
                    $snapshot = $additionalInfo['product_snapshot'] ?? [];
                    $hasSnapshot = !empty($snapshot);
                    $planName = $snapshot['plan_name'] ?? '없음';
                    $provider = $snapshot['provider'] ?? '없음';
                    
                    $snapshotStatus = $hasSnapshot ? '<span style="color: green;">✓ 있음</span>' : '<span style="color: red;">✗ 없음</span>';
                    
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($order['order_number'] ?? '-') . '</td>';
                    echo '<td>' . htmlspecialchars($order['product_type'] ?? '-') . '</td>';
                    echo '<td>' . $snapshotStatus . '</td>';
                    echo '<td>' . htmlspecialchars($planName) . '</td>';
                    echo '<td>' . htmlspecialchars($provider) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="info">mno-sim 주문이 없습니다.</div>';
            }
        } catch (PDOException $e) {
            echo '<div class="error">product_snapshot 확인 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        // 4. 알뜰폰 데이터가 섞인 경우 확인
        echo '<h2>4. 알뜰폰 데이터 혼입 확인</h2>';
        try {
            $stmt = $pdo->prepare("
                SELECT a.id, a.order_number, c.additional_info
                FROM product_applications a
                INNER JOIN application_customers c ON a.id = c.application_id
                INNER JOIN products p ON a.product_id = p.id
                WHERE p.product_type = 'mno-sim'
                AND c.additional_info LIKE '%알뜰폰%'
                ORDER BY a.created_at DESC
                LIMIT 10
            ");
            $stmt->execute();
            $mixedOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($mixedOrders) > 0) {
                echo '<div class="warning">⚠️ 알뜰폰 데이터가 섞인 주문: ' . count($mixedOrders) . '개</div>';
                echo '<table>';
                echo '<tr><th>주문번호</th><th>product_snapshot 내용 (일부)</th></tr>';
                
                foreach ($mixedOrders as $order) {
                    $additionalInfo = json_decode($order['additional_info'] ?? '{}', true) ?: [];
                    $snapshot = $additionalInfo['product_snapshot'] ?? [];
                    $snapshotPreview = json_encode($snapshot, JSON_UNESCAPED_UNICODE);
                    $snapshotPreview = mb_substr($snapshotPreview, 0, 200) . '...';
                    
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($order['order_number'] ?? '-') . '</td>';
                    echo '<td><pre style="font-size: 11px;">' . htmlspecialchars($snapshotPreview) . '</pre></td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="success">✅ 알뜰폰 데이터가 섞인 주문이 없습니다.</div>';
            }
        } catch (PDOException $e) {
            echo '<div class="error">알뜰폰 데이터 확인 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
    } catch (Exception $e) {
        echo '<div class="error">오류 발생: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>
    
    <h2>요약</h2>
    <ul>
        <li><strong>문제점:</strong> product_applications 테이블의 product_type ENUM에 'mno-sim'이 없으면 주문 저장 시 오류 발생</li>
        <li><strong>해결방법:</strong> 위의 SQL을 실행하여 ENUM에 'mno-sim' 추가</li>
        <li><strong>주의사항:</strong> 주문 저장 시 product_snapshot에 올바른 mno-sim 데이터만 저장되어야 함</li>
    </ul>
</body>
</html>


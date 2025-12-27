<?php
/**
 * 통신사단독유심 상품의 plan_name 값 확인 스크립트
 * 잘못 저장된 plan_name 값을 찾아서 수정
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>통신사단독유심 plan_name 확인</title>
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
        .bad-name { background: #fff3cd; }
    </style>
</head>
<body>
    <h1>통신사단독유심 plan_name 확인</h1>
    
    <?php
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            echo '<div class="error">데이터베이스 연결 실패</div>';
            exit;
        }
        
        // 1. 상품 테이블의 plan_name 확인
        echo '<h2>1. 상품 테이블 (product_mno_sim_details)의 plan_name 확인</h2>';
        $stmt = $pdo->query("
            SELECT p.id, p.seller_id, mno_sim.plan_name, mno_sim.provider, mno_sim.registration_types, mno_sim.redirect_url
            FROM products p
            INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
            WHERE p.product_type = 'mno-sim'
            ORDER BY p.created_at DESC
            LIMIT 20
        ");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($products) > 0) {
            echo '<table>';
            echo '<tr><th>상품 ID</th><th>판매자 ID</th><th>plan_name</th><th>provider</th><th>registration_types</th><th>redirect_url</th><th>상태</th></tr>';
            
            foreach ($products as $product) {
                $planName = $product['plan_name'] ?? '';
                $registrationTypes = $product['registration_types'] ?? '';
                $redirectUrl = $product['redirect_url'] ?? '';
                
                // 비정상적인 plan_name 체크
                $isBad = false;
                $badReasons = [];
                
                if (empty($planName)) {
                    $isBad = true;
                    $badReasons[] = '비어있음';
                } elseif (strpos($planName, 'URL') !== false) {
                    $isBad = true;
                    $badReasons[] = 'URL 포함';
                } elseif (strpos($planName, '없음') !== false) {
                    $isBad = true;
                    $badReasons[] = '없음 포함';
                } elseif (strpos($planName, '세가지') !== false || strpos($planName, '형태') !== false) {
                    $isBad = true;
                    $badReasons[] = 'registration_types 관련 텍스트 포함';
                } elseif (preg_match('/^\d+.*\d+$/', $planName) && mb_strlen($planName) > 15) {
                    $isBad = true;
                    $badReasons[] = '숫자 패턴 이상';
                } elseif (mb_strlen($planName) > 30) {
                    $isBad = true;
                    $badReasons[] = '너무 김 (' . mb_strlen($planName) . '자)';
                }
                
                $rowClass = $isBad ? 'bad-name' : '';
                $status = $isBad ? '<span style="color: red;">❌ 비정상</span><br><small>' . implode(', ', $badReasons) . '</small>' : '<span style="color: green;">✅ 정상</span>';
                
                echo '<tr class="' . $rowClass . '">';
                echo '<td>' . htmlspecialchars($product['id']) . '</td>';
                echo '<td>' . htmlspecialchars($product['seller_id']) . '</td>';
                echo '<td>' . htmlspecialchars($planName) . '</td>';
                echo '<td>' . htmlspecialchars($product['provider'] ?? '-') . '</td>';
                echo '<td><pre style="font-size: 11px; margin: 0;">' . htmlspecialchars($registrationTypes) . '</pre></td>';
                echo '<td>' . htmlspecialchars($redirectUrl ?: '-') . '</td>';
                echo '<td>' . $status . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<div class="info">등록된 통신사단독유심 상품이 없습니다.</div>';
        }
        
        // 2. 주문의 product_snapshot에서 plan_name 확인
        echo '<h2>2. 주문의 product_snapshot에서 plan_name 확인</h2>';
        $stmt = $pdo->query("
            SELECT a.id, a.order_number, a.product_id, c.additional_info
            FROM product_applications a
            INNER JOIN application_customers c ON a.id = c.application_id
            INNER JOIN products p ON a.product_id = p.id
            WHERE p.product_type = 'mno-sim'
            ORDER BY a.created_at DESC
            LIMIT 20
        ");
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($orders) > 0) {
            echo '<table>';
            echo '<tr><th>주문번호</th><th>상품 ID</th><th>product_snapshot.plan_name</th><th>product_snapshot.provider</th><th>product_snapshot.registration_types</th><th>product_snapshot.redirect_url</th><th>상태</th></tr>';
            
            foreach ($orders as $order) {
                $additionalInfo = json_decode($order['additional_info'] ?? '{}', true) ?: [];
                $snapshot = $additionalInfo['product_snapshot'] ?? [];
                
                $planName = $snapshot['plan_name'] ?? '';
                $registrationTypes = $snapshot['registration_types'] ?? '';
                $redirectUrl = $snapshot['redirect_url'] ?? '';
                
                // 비정상적인 plan_name 체크
                $isBad = false;
                $badReasons = [];
                
                if (empty($planName)) {
                    $isBad = true;
                    $badReasons[] = '비어있음';
                } elseif (strpos($planName, 'URL') !== false) {
                    $isBad = true;
                    $badReasons[] = 'URL 포함';
                } elseif (strpos($planName, '없음') !== false) {
                    $isBad = true;
                    $badReasons[] = '없음 포함';
                } elseif (strpos($planName, '세가지') !== false || strpos($planName, '형태') !== false) {
                    $isBad = true;
                    $badReasons[] = 'registration_types 관련 텍스트 포함';
                } elseif (preg_match('/^\d+.*\d+$/', $planName) && mb_strlen($planName) > 15) {
                    $isBad = true;
                    $badReasons[] = '숫자 패턴 이상';
                } elseif (mb_strlen($planName) > 30) {
                    $isBad = true;
                    $badReasons[] = '너무 김 (' . mb_strlen($planName) . '자)';
                }
                
                $rowClass = $isBad ? 'bad-name' : '';
                $status = $isBad ? '<span style="color: red;">❌ 비정상</span><br><small>' . implode(', ', $badReasons) . '</small>' : '<span style="color: green;">✅ 정상</span>';
                
                echo '<tr class="' . $rowClass . '">';
                echo '<td>' . htmlspecialchars($order['order_number'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($order['product_id']) . '</td>';
                echo '<td>' . htmlspecialchars($planName) . '</td>';
                echo '<td>' . htmlspecialchars($snapshot['provider'] ?? '-') . '</td>';
                echo '<td><pre style="font-size: 11px; margin: 0;">' . htmlspecialchars(is_array($registrationTypes) ? json_encode($registrationTypes, JSON_UNESCAPED_UNICODE) : $registrationTypes) . '</pre></td>';
                echo '<td>' . htmlspecialchars($redirectUrl ?: '-') . '</td>';
                echo '<td>' . $status . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        
        // 3. plan_name과 다른 필드 값 비교
        echo '<h2>3. plan_name과 다른 필드 값 비교 (의심되는 경우)</h2>';
        $stmt = $pdo->query("
            SELECT a.id, a.order_number, c.additional_info
            FROM product_applications a
            INNER JOIN application_customers c ON a.id = c.application_id
            INNER JOIN products p ON a.product_id = p.id
            WHERE p.product_type = 'mno-sim'
            AND c.additional_info LIKE '%plan_name%'
            ORDER BY a.created_at DESC
            LIMIT 10
        ");
        $suspiciousOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($suspiciousOrders) > 0) {
            echo '<table>';
            echo '<tr><th>주문번호</th><th>plan_name</th><th>registration_types</th><th>redirect_url</th><th>기타 필드</th></tr>';
            
            foreach ($suspiciousOrders as $order) {
                $additionalInfo = json_decode($order['additional_info'] ?? '{}', true) ?: [];
                $snapshot = $additionalInfo['product_snapshot'] ?? [];
                
                $planName = $snapshot['plan_name'] ?? '';
                $registrationTypes = $snapshot['registration_types'] ?? '';
                $redirectUrl = $snapshot['redirect_url'] ?? '';
                
                // registration_types가 plan_name에 포함되어 있는지 확인
                $regTypesStr = '';
                if (is_array($registrationTypes)) {
                    $regTypesStr = implode(', ', $registrationTypes);
                } elseif (is_string($registrationTypes)) {
                    $regTypesStr = $registrationTypes;
                }
                
                $containsRegTypes = false;
                if (!empty($regTypesStr) && strpos($planName, $regTypesStr) !== false) {
                    $containsRegTypes = true;
                }
                
                $containsUrl = false;
                if (!empty($redirectUrl) && strpos($planName, $redirectUrl) !== false) {
                    $containsUrl = true;
                }
                
                if ($containsRegTypes || $containsUrl || strpos($planName, 'URL') !== false || strpos($planName, '없음') !== false) {
                    echo '<tr class="bad-name">';
                    echo '<td>' . htmlspecialchars($order['order_number'] ?? '-') . '</td>';
                    echo '<td><strong>' . htmlspecialchars($planName) . '</strong></td>';
                    echo '<td>' . htmlspecialchars($regTypesStr ?: '-') . '</td>';
                    echo '<td>' . htmlspecialchars($redirectUrl ?: '-') . '</td>';
                    echo '<td>';
                    if ($containsRegTypes) echo '<span style="color: red;">⚠️ registration_types 포함</span><br>';
                    if ($containsUrl) echo '<span style="color: red;">⚠️ redirect_url 포함</span><br>';
                    if (strpos($planName, 'URL') !== false) echo '<span style="color: red;">⚠️ URL 텍스트 포함</span><br>';
                    if (strpos($planName, '없음') !== false) echo '<span style="color: red;">⚠️ 없음 텍스트 포함</span>';
                    echo '</td>';
                    echo '</tr>';
                }
            }
            echo '</table>';
        }
        
    } catch (Exception $e) {
        echo '<div class="error">오류 발생: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>
    
    <h2>분석 결과</h2>
    <ul>
        <li><strong>문제:</strong> plan_name에 다른 필드 값들(registration_types, redirect_url 등)이 섞여서 저장됨</li>
        <li><strong>원인 추정:</strong> 상품 등록 시 JavaScript에서 plan_name 필드에 다른 값들을 합치거나, 서버에서 잘못된 값이 저장됨</li>
        <li><strong>해결방법:</strong> 상품 등록 폼과 저장 로직에서 plan_name이 다른 필드와 섞이지 않도록 수정 필요</li>
    </ul>
</body>
</html>


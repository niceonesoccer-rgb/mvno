<?php
/**
 * 통신사단독유심 상품의 잘못 저장된 plan_name 값 수정 스크립트
 * 
 * 문제: plan_name에 다른 필드 값들(registration_types, redirect_url 등)이 섞여서 저장됨
 * 해결: 비정상적인 plan_name을 감지하고, 상품 정보에서 올바른 plan_name을 찾아서 수정
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>통신사단독유심 plan_name 수정</title>
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
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
    </style>
</head>
<body>
    <h1>통신사단독유심 plan_name 수정</h1>
    
    <?php
    $action = $_GET['action'] ?? 'check';
    $fix = isset($_GET['fix']) && $_GET['fix'] === '1';
    
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            echo '<div class="error">데이터베이스 연결 실패</div>';
            exit;
        }
        
        // 비정상적인 plan_name 감지 함수
        function isInvalidPlanName($planName) {
            if (empty($planName)) return true;
            
            $invalidKeywords = ['URL', '없음', '세가지', '형태', '가입', '다음', '추가'];
            foreach ($invalidKeywords as $keyword) {
                if (stripos($planName, $keyword) !== false) {
                    return true;
                }
            }
            
            // 숫자로 시작하고 끝나는 이상한 패턴
            if (preg_match('/^\d+.*\d+$/', $planName) && mb_strlen($planName) > 15) {
                return true;
            }
            
            // 너무 긴 경우
            if (mb_strlen($planName) > 30) {
                return true;
            }
            
            return false;
        }
        
        if ($action === 'fix' && $fix) {
            // 수정 실행
            echo '<h2>plan_name 수정 실행</h2>';
            
            // 1. 상품 테이블의 잘못된 plan_name 수정
            $stmt = $pdo->query("
                SELECT p.id, mno_sim.plan_name, mno_sim.provider
                FROM products p
                INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
                WHERE p.product_type = 'mno-sim'
            ");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $fixedProducts = 0;
            $fixedOrders = 0;
            
            foreach ($products as $product) {
                $planName = $product['plan_name'] ?? '';
                $provider = $product['provider'] ?? '';
                
                if (isInvalidPlanName($planName)) {
                    // 비정상적인 plan_name을 provider + " 통신사단독유심"으로 변경
                    $newPlanName = !empty($provider) ? $provider . ' 통신사단독유심' : '통신사단독유심';
                    
                    try {
                        $updateStmt = $pdo->prepare("
                            UPDATE product_mno_sim_details 
                            SET plan_name = :plan_name 
                            WHERE product_id = :product_id
                        ");
                        $updateStmt->execute([
                            ':plan_name' => $newPlanName,
                            ':product_id' => $product['id']
                        ]);
                        $fixedProducts++;
                        echo '<div class="info">상품 ID ' . $product['id'] . ': "' . htmlspecialchars($planName) . '" → "' . htmlspecialchars($newPlanName) . '"</div>';
                    } catch (Exception $e) {
                        echo '<div class="error">상품 ID ' . $product['id'] . ' 수정 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                }
            }
            
            // 2. 주문의 product_snapshot에서 잘못된 plan_name 수정
            $stmt = $pdo->query("
                SELECT a.id, a.order_number, c.additional_info
                FROM product_applications a
                INNER JOIN application_customers c ON a.id = c.application_id
                INNER JOIN products p ON a.product_id = p.id
                WHERE p.product_type = 'mno-sim'
            ");
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($orders as $order) {
                $additionalInfo = json_decode($order['additional_info'] ?? '{}', true) ?: [];
                $snapshot = $additionalInfo['product_snapshot'] ?? [];
                
                $planName = $snapshot['plan_name'] ?? '';
                $provider = $snapshot['provider'] ?? '';
                
                if (isInvalidPlanName($planName)) {
                    // 비정상적인 plan_name을 provider + " 통신사단독유심"으로 변경
                    $newPlanName = !empty($provider) ? $provider . ' 통신사단독유심' : '통신사단독유심';
                    
                    $snapshot['plan_name'] = $newPlanName;
                    $additionalInfo['product_snapshot'] = $snapshot;
                    
                    try {
                        $updateStmt = $pdo->prepare("
                            UPDATE application_customers 
                            SET additional_info = :additional_info 
                            WHERE application_id = :application_id
                        ");
                        $updateStmt->execute([
                            ':additional_info' => json_encode($additionalInfo, JSON_UNESCAPED_UNICODE),
                            ':application_id' => $order['id']
                        ]);
                        $fixedOrders++;
                        echo '<div class="info">주문 ' . htmlspecialchars($order['order_number']) . ': "' . htmlspecialchars($planName) . '" → "' . htmlspecialchars($newPlanName) . '"</div>';
                    } catch (Exception $e) {
                        echo '<div class="error">주문 ' . htmlspecialchars($order['order_number']) . ' 수정 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                }
            }
            
            echo '<div class="success">✅ 수정 완료: 상품 ' . $fixedProducts . '개, 주문 ' . $fixedOrders . '개</div>';
            echo '<p><a href="?" class="btn">확인 페이지로 돌아가기</a></p>';
            
        } else {
            // 확인만
            echo '<h2>잘못 저장된 plan_name 확인</h2>';
            
            // 상품 테이블 확인
            $stmt = $pdo->query("
                SELECT p.id, mno_sim.plan_name, mno_sim.provider
                FROM products p
                INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
                WHERE p.product_type = 'mno-sim'
            ");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $badProducts = [];
            foreach ($products as $product) {
                if (isInvalidPlanName($product['plan_name'])) {
                    $badProducts[] = $product;
                }
            }
            
            // 주문 확인
            $stmt = $pdo->query("
                SELECT a.id, a.order_number, c.additional_info
                FROM product_applications a
                INNER JOIN application_customers c ON a.id = c.application_id
                INNER JOIN products p ON a.product_id = p.id
                WHERE p.product_type = 'mno-sim'
            ");
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $badOrders = [];
            foreach ($orders as $order) {
                $additionalInfo = json_decode($order['additional_info'] ?? '{}', true) ?: [];
                $snapshot = $additionalInfo['product_snapshot'] ?? [];
                $planName = $snapshot['plan_name'] ?? '';
                
                if (isInvalidPlanName($planName)) {
                    $badOrders[] = [
                        'order' => $order,
                        'plan_name' => $planName,
                        'provider' => $snapshot['provider'] ?? ''
                    ];
                }
            }
            
            echo '<div class="info">';
            echo '<strong>비정상적인 plan_name 발견:</strong><br>';
            echo '- 상품: ' . count($badProducts) . '개<br>';
            echo '- 주문: ' . count($badOrders) . '개';
            echo '</div>';
            
            if (count($badProducts) > 0 || count($badOrders) > 0) {
                echo '<div class="warning">';
                echo '<strong>수정 방법:</strong><br>';
                echo '비정상적인 plan_name은 "{provider} 통신사단독유심" 형식으로 자동 변경됩니다.<br>';
                echo '<a href="?action=fix&fix=1" class="btn btn-danger" onclick="return confirm(\'정말 수정하시겠습니까? 이 작업은 되돌릴 수 없습니다.\')">수정 실행</a>';
                echo '</div>';
            } else {
                echo '<div class="success">✅ 모든 plan_name이 정상입니다.</div>';
            }
        }
        
    } catch (Exception $e) {
        echo '<div class="error">오류 발생: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>
</body>
</html>



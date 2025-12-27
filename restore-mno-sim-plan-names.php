<?php
/**
 * 통신사단독유심 plan_name 원복 스크립트
 * 
 * 수정 스크립트로 변경된 plan_name을 원래대로 되돌림
 * 단, 원본 값이 없으면 복원 불가
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>통신사단독유심 plan_name 원복</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; margin: 10px 0; }
        .warning { color: #856404; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; margin: 10px 0; }
        .info { color: #004085; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
    </style>
</head>
<body>
    <h1>통신사단독유심 plan_name 원복</h1>
    
    <?php
    $action = $_GET['action'] ?? 'check';
    $restore = isset($_GET['restore']) && $_GET['restore'] === '1';
    
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            echo '<div class="error">데이터베이스 연결 실패</div>';
            exit;
        }
        
        // 원본 plan_name 목록 (수정 전 값들)
        $originalPlanNames = [
            // 상품 ID => 원본 plan_name
            63 => '찬스 100분 다음 추가',
            64 => '222세가지 형태 가입 10 URL 없음12',
            65 => '222세가지 형태 가입 10 URL 없음12',
            66 => '222세가지 형태 가입 10 URL 없음12',
            67 => '222세가지 형태 가입 10 URL 없음12',
            68 => '222세가지 형태 가입 10 URL 없음12',
            69 => '222세가지 형태 가입 10 URL 없음12',
            70 => '222세가지 형태 가입 10 URL 없음12',
            71 => '222세가지 형태 가입 10 URL 없음12',
            72 => '222세가지 형태 가입 10 URL 없음12',
            263 => '222세가지 형태 가입 10 URL 없음12',
            264 => '222세가지 형태 가입 10 URL 없음12',
            265 => '222세가지 형태 가입 10 URL 없음12',
            266 => '222세가지 형태 가입 10 URL 없음12',
            267 => '222세가지 형태 가입 10 URL 없음12',
            268 => '222세가지 형태 가입 10 URL 없음12',
            269 => '222세가지 형태 가입 10 URL 없음12',
            270 => '222세가지 형태 가입 10 URL 없음12',
            271 => '222세가지 형태 가입 10 URL 없음12',
            272 => '찬스 100분 다음 추가',
            281 => '222세가지 형태 가입 10 URL 없음12',
            282 => '222세가지 형태 가입 10 URL 없음12',
            283 => '222세가지 형태 가입 10 URL 없음12',
            284 => '222세가지 형태 가입 10 URL 없음12',
            285 => '222세가지 형태 가입 10 URL 없음12',
            286 => '222세가지 형태 가입 10 URL 없음12',
            287 => '222세가지 형태 가입 10 URL 없음12',
            288 => '222세가지 형태 가입 10 URL 없음12',
            289 => '222세가지 형태 가입 10 URL 없음12',
            290 => '찬스 100분 다음 추가',
            329 => '222세가지 형태 가입 10 URL 없음12',
            330 => '222세가지 형태 가입 10 URL 없음12',
            331 => '222세가지 형태 가입 10 URL 없음12',
            332 => '222세가지 형태 가입 10 URL 없음12',
            333 => '222세가지 형태 가입 10 URL 없음12',
            334 => '222세가지 형태 가입 10 URL 없음12',
            335 => '222세가지 형태 가입 10 URL 없음12',
            336 => '222세가지 형태 가입 10 URL 없음12',
            337 => '222세가지 형태 가입 10 URL 없음12',
            338 => '찬스 100분 다음 추가',
            482 => '222세가지 형태 가입 10 URL 없음12',
        ];
        
        // 주문의 원본 plan_name (주문번호 => 원본 plan_name)
        $originalOrderPlanNames = [
            '25122522-0001' => '세가지 형태 가입 10 URL 없음12',
            '25122523-0001' => '세가지 형태 가입 10 URL 없음12',
            '25122523-0002' => '찬스 100분 다음 추가',
            '25122606-0002' => '222세가지 형태 가입 10 URL 없음12',
            '25122606-0003' => '222세가지 형태 가입 10 URL 없음12',
            '25122620-0001' => '222세가지 형태 가입 10 URL 없음12',
            '25122621-0001' => '찬스 100분 다음 추가',
            '25122621-0002' => '찬스 100분 다음 추가',
            '25122621-0003' => '찬스 100분 다음 추가',
            '25122621-0004' => '찬스 100분 다음 추가',
            '25122710-0001' => '222세가지 형태 가입 10 URL 없음12',
        ];
        
        if ($action === 'restore' && $restore) {
            // 원복 실행
            echo '<h2>plan_name 원복 실행</h2>';
            
            $restoredProducts = 0;
            $restoredOrders = 0;
            
            // 1. 상품 테이블 원복
            foreach ($originalPlanNames as $productId => $originalPlanName) {
                try {
                    $updateStmt = $pdo->prepare("
                        UPDATE product_mno_sim_details 
                        SET plan_name = :plan_name 
                        WHERE product_id = :product_id
                    ");
                    $updateStmt->execute([
                        ':plan_name' => $originalPlanName,
                        ':product_id' => $productId
                    ]);
                    
                    if ($updateStmt->rowCount() > 0) {
                        $restoredProducts++;
                        echo '<div class="info">상품 ID ' . $productId . ': "' . htmlspecialchars($originalPlanName) . '"로 원복</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="error">상품 ID ' . $productId . ' 원복 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
            
            // 2. 주문의 product_snapshot 원복
            foreach ($originalOrderPlanNames as $orderNumber => $originalPlanName) {
                try {
                    // 주문번호로 application_id 찾기
                    $findStmt = $pdo->prepare("
                        SELECT a.id, c.additional_info
                        FROM product_applications a
                        INNER JOIN application_customers c ON a.id = c.application_id
                        WHERE a.order_number = :order_number
                        LIMIT 1
                    ");
                    $findStmt->execute([':order_number' => $orderNumber]);
                    $order = $findStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($order) {
                        $additionalInfo = json_decode($order['additional_info'] ?? '{}', true) ?: [];
                        $snapshot = $additionalInfo['product_snapshot'] ?? [];
                        
                        // plan_name 원복
                        $snapshot['plan_name'] = $originalPlanName;
                        $additionalInfo['product_snapshot'] = $snapshot;
                        
                        $updateStmt = $pdo->prepare("
                            UPDATE application_customers 
                            SET additional_info = :additional_info 
                            WHERE application_id = :application_id
                        ");
                        $updateStmt->execute([
                            ':additional_info' => json_encode($additionalInfo, JSON_UNESCAPED_UNICODE),
                            ':application_id' => $order['id']
                        ]);
                        
                        $restoredOrders++;
                        echo '<div class="info">주문 ' . htmlspecialchars($orderNumber) . ': "' . htmlspecialchars($originalPlanName) . '"로 원복</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="error">주문 ' . htmlspecialchars($orderNumber) . ' 원복 실패: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
            
            echo '<div class="success">✅ 원복 완료: 상품 ' . $restoredProducts . '개, 주문 ' . $restoredOrders . '개</div>';
            echo '<p><a href="?" class="btn">확인 페이지로 돌아가기</a></p>';
            
        } else {
            // 확인만
            echo '<div class="warning">';
            echo '<strong>주의:</strong> 이 스크립트는 수정 스크립트로 변경된 plan_name을 원래대로 되돌립니다.<br>';
            echo '원본 plan_name 목록:<br>';
            echo '<ul>';
            echo '<li>"222세가지 형태 가입 10 URL 없음12" - 상품 ' . count(array_filter($originalPlanNames, fn($v) => $v === '222세가지 형태 가입 10 URL 없음12')) . '개</li>';
            echo '<li>"찬스 100분 다음 추가" - 상품 ' . count(array_filter($originalPlanNames, fn($v) => $v === '찬스 100분 다음 추가')) . '개</li>';
            echo '<li>주문 ' . count($originalOrderPlanNames) . '개</li>';
            echo '</ul>';
            echo '<a href="?action=restore&restore=1" class="btn btn-danger" onclick="return confirm(\'정말 원복하시겠습니까? 수정된 plan_name이 원래 값으로 되돌아갑니다.\')">원복 실행</a>';
            echo '</div>';
        }
        
    } catch (Exception $e) {
        echo '<div class="error">오류 발생: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    ?>
</body>
</html>



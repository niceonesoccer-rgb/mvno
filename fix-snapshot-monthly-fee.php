<?php
/**
 * additional_info의 product_snapshot.monthly_fee 값을 수정하는 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/fix-snapshot-monthly-fee.php
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>product_snapshot monthly_fee 수정</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: green; font-weight: bold; padding: 10px; background: #d1fae5; border-radius: 5px; margin: 10px 0; }
        .error { color: red; font-weight: bold; padding: 10px; background: #fee2e2; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #dbeafe; border-radius: 5px; margin: 10px 0; }
        .warning { color: orange; padding: 10px; background: #fef3c7; border-radius: 5px; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #10b981; color: white; }
        button { padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 10px 5px; }
        button:hover { background: #059669; }
        .raw-value { font-family: monospace; background: #f3f4f6; padding: 2px 6px; border-radius: 3px; }
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
    
    echo "<h1>product_snapshot.monthly_fee 수정</h1>";
    
    // 모든 application_customers의 additional_info 확인
    $stmt = $pdo->query("
        SELECT 
            c.application_id,
            a.order_number,
            c.additional_info
        FROM application_customers c
        INNER JOIN product_applications a ON c.application_id = a.id
        INNER JOIN products p ON a.product_id = p.id
        WHERE p.product_type = 'internet'
        AND c.additional_info IS NOT NULL
        AND c.additional_info != ''
        AND c.additional_info != '{}'
    ");
    $allCustomers = $stmt->fetchAll();
    
    echo "<p class='info'>총 <strong>" . count($allCustomers) . "개</strong>의 인터넷 주문을 확인합니다.</p>";
    
    $needsFix = [];
    $fixed = [];
    
    foreach ($allCustomers as $customer) {
        $additionalInfo = json_decode($customer['additional_info'], true);
        
        if (!$additionalInfo || !isset($additionalInfo['product_snapshot'])) {
            continue;
        }
        
        $snapshot = $additionalInfo['product_snapshot'];
        
        if (!isset($snapshot['monthly_fee'])) {
            continue;
        }
        
        $monthlyFee = $snapshot['monthly_fee'];
        
        // 수정이 필요한 경우 확인
        $needsUpdate = false;
        $newValue = '';
        
        // 소수점 형식인 경우 (예: 30000.00)
        if (preg_match('/^([0-9]+)\.([0-9]+)$/', $monthlyFee, $matches)) {
            $newValue = $matches[1] . '원';
            $needsUpdate = true;
        }
        // 숫자만 있는 경우 (예: 30000)
        elseif (preg_match('/^([0-9]+)$/', $monthlyFee, $matches)) {
            $newValue = $matches[1] . '원';
            $needsUpdate = true;
        }
        // 이미 "원"이 있지만 소수점이 포함된 경우 (예: 30000.00원)
        elseif (preg_match('/^([0-9]+)\.([0-9]+)(.+)$/', $monthlyFee, $matches)) {
            $newValue = $matches[1] . $matches[3];
            $needsUpdate = true;
        }
        
        if ($needsUpdate) {
            $needsFix[] = [
                'application_id' => $customer['application_id'],
                'order_number' => $customer['order_number'],
                'old_value' => $monthlyFee,
                'new_value' => $newValue
            ];
        } else {
            // 이미 올바른 형식인 경우
            $fixed[] = [
                'application_id' => $customer['application_id'],
                'order_number' => $customer['order_number'],
                'value' => $monthlyFee
            ];
        }
    }
    
    echo "<h2>1. 분석 결과</h2>";
    echo "<table>";
    echo "<tr><th>항목</th><th>개수</th></tr>";
    echo "<tr><td>수정 필요</td><td><strong class='error'>" . count($needsFix) . "개</strong></td></tr>";
    echo "<tr><td>이미 올바른 형식</td><td><strong class='success'>" . count($fixed) . "개</strong></td></tr>";
    echo "</table>";
    
    if (count($needsFix) > 0) {
        echo "<h2>2. 수정 예정 데이터 (최대 20개 미리보기)</h2>";
        echo "<table>";
        echo "<tr><th>주문번호</th><th>신청 ID</th><th>현재 값</th><th>변환 후 값</th></tr>";
        
        $previewCount = 0;
        foreach ($needsFix as $item) {
            if ($previewCount >= 20) break;
            echo "<tr>";
            echo "<td>" . htmlspecialchars($item['order_number']) . "</td>";
            echo "<td>{$item['application_id']}</td>";
            echo "<td><span class='raw-value'>" . htmlspecialchars($item['old_value']) . "</span></td>";
            echo "<td><span class='raw-value' style='background: #d1fae5;'>" . htmlspecialchars($item['new_value']) . "</span></td>";
            echo "</tr>";
            $previewCount++;
        }
        echo "</table>";
        
        if (count($needsFix) > 20) {
            echo "<p class='info'>... 외 " . (count($needsFix) - 20) . "개 더 있습니다.</p>";
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'fix') {
            try {
                $pdo->beginTransaction();
                
                $updateCount = 0;
                $errorCount = 0;
                
                foreach ($needsFix as $item) {
                    try {
                        // 해당 application_id의 additional_info 다시 조회
                        $stmt = $pdo->prepare("SELECT additional_info FROM application_customers WHERE application_id = :application_id");
                        $stmt->execute([':application_id' => $item['application_id']]);
                        $customerData = $stmt->fetch();
                        
                        if (!$customerData) {
                            $errorCount++;
                            continue;
                        }
                        
                        $additionalInfo = json_decode($customerData['additional_info'], true);
                        
                        if (!$additionalInfo || !isset($additionalInfo['product_snapshot'])) {
                            $errorCount++;
                            continue;
                        }
                        
                        // monthly_fee 값 수정
                        $additionalInfo['product_snapshot']['monthly_fee'] = $item['new_value'];
                        
                        // JSON으로 다시 인코딩
                        $newAdditionalInfo = json_encode($additionalInfo, JSON_UNESCAPED_UNICODE);
                        
                        // 업데이트
                        $updateStmt = $pdo->prepare("
                            UPDATE application_customers 
                            SET additional_info = :additional_info 
                            WHERE application_id = :application_id
                        ");
                        $updateStmt->execute([
                            ':additional_info' => $newAdditionalInfo,
                            ':application_id' => $item['application_id']
                        ]);
                        
                        $updateCount++;
                        
                    } catch (PDOException $e) {
                        error_log("Error updating application_id {$item['application_id']}: " . $e->getMessage());
                        $errorCount++;
                    }
                }
                
                $pdo->commit();
                
                echo "<p class='success' style='font-size: 18px;'>✓✓✓ 성공적으로 완료되었습니다! ✓✓✓</p>";
                echo "<p class='success'>총 <strong>{$updateCount}개</strong>의 레코드가 수정되었습니다.</p>";
                if ($errorCount > 0) {
                    echo "<p class='warning'>{$errorCount}개의 레코드에서 오류가 발생했습니다.</p>";
                }
                echo "<p><a href='fix-snapshot-monthly-fee.php'>페이지 새로고침하여 확인</a></p>";
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo "<p class='error'>오류 발생: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p>트랜잭션이 롤백되었습니다.</p>";
            }
        } else {
            echo "<form method='POST'>";
            echo "<input type='hidden' name='action' value='fix'>";
            echo "<p class='warning'><strong>주의:</strong> 이 작업은 additional_info의 product_snapshot.monthly_fee 값을 '숫자원' 형식으로 변환합니다.</p>";
            echo "<p class='warning'>예: 30000.00 → 30000원</p>";
            echo "<button type='submit'>데이터 수정 실행</button>";
            echo "</form>";
        }
    } else {
        echo "<p class='success'>✓ 모든 데이터가 이미 올바른 형식입니다. 수정이 필요하지 않습니다.</p>";
    }
    
    // 수정 후 확인 (수정 실행 후)
    if (isset($_POST['action']) && $_POST['action'] === 'fix' && count($needsFix) > 0) {
        echo "<h2>3. 수정 후 확인 (샘플)</h2>";
        
        // 수정된 데이터 중 일부 확인
        $checkStmt = $pdo->prepare("
            SELECT 
                c.application_id,
                a.order_number,
                c.additional_info
            FROM application_customers c
            INNER JOIN product_applications a ON c.application_id = a.id
            WHERE c.application_id IN (" . implode(',', array_slice(array_column($needsFix, 'application_id'), 0, 5)) . ")
        ");
        $checkStmt->execute();
        $checkResults = $checkStmt->fetchAll();
        
        if (count($checkResults) > 0) {
            echo "<table>";
            echo "<tr><th>주문번호</th><th>신청 ID</th><th>수정 후 monthly_fee</th></tr>";
            foreach ($checkResults as $result) {
                $info = json_decode($result['additional_info'], true);
                $snapshotFee = $info['product_snapshot']['monthly_fee'] ?? '-';
                echo "<tr>";
                echo "<td>" . htmlspecialchars($result['order_number']) . "</td>";
                echo "<td>{$result['application_id']}</td>";
                echo "<td><span class='raw-value'>" . htmlspecialchars($snapshotFee) . "</span></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>데이터베이스 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
?>

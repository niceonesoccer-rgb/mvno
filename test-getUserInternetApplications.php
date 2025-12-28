<?php
/**
 * getUserInternetApplications 함수 테스트
 * 사용법: http://localhost/MVNO/test-getUserInternetApplications.php?user_id=q2222222
 */

require_once __DIR__ . '/includes/data/db-config.php';
require_once __DIR__ . '/includes/data/product-functions.php';
require_once __DIR__ . '/includes/data/auth-functions.php';

header('Content-Type: text/html; charset=utf-8');

$userId = $_GET['user_id'] ?? 'q2222222';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='utf-8'><title>getUserInternetApplications 테스트</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
    h1 { color: #333; border-bottom: 2px solid #6366f1; padding-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; font-size: 13px; }
    th { background: #f8f9fa; font-weight: 600; }
    .value { font-family: monospace; background: #f8f9fa; padding: 4px 8px; border-radius: 4px; }
    .error { color: #dc2626; font-weight: 600; }
    .success { color: #10b981; font-weight: 600; }
    .warning { color: #f59e0b; font-weight: 600; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>getUserInternetApplications 함수 테스트</h1>";
echo "<p><strong>사용자 ID:</strong> " . htmlspecialchars($userId) . "</p>";

try {
    $result = getUserInternetApplications($userId);
    
    echo "<h2>결과 (총 " . count($result) . "개)</h2>";
    
    if (empty($result)) {
        echo "<p class='warning'>신청 정보가 없습니다.</p>";
    } else {
        echo "<table>";
        echo "<tr>";
        echo "<th>application_id</th>";
        echo "<th>provider<br>(최종 값)</th>";
        echo "<th>plan_name</th>";
        echo "<th>speed</th>";
        echo "<th>price</th>";
        echo "<th>order_date</th>";
        echo "<th>상태</th>";
        echo "</tr>";
        
        foreach ($result as $internet) {
            $appId = $internet['application_id'] ?? 'N/A';
            $provider = $internet['provider'] ?? 'N/A';
            $planName = $internet['plan_name'] ?? 'N/A';
            $speed = $internet['speed'] ?? 'N/A';
            $price = $internet['price'] ?? 'N/A';
            $orderDate = $internet['order_date'] ?? 'N/A';
            $status = $internet['status'] ?? 'N/A';
            
            echo "<tr>";
            echo "<td><span class='value'>{$appId}</span></td>";
            echo "<td><span class='value'>" . htmlspecialchars($provider) . "</span></td>";
            echo "<td>" . htmlspecialchars($planName) . "</td>";
            echo "<td>" . htmlspecialchars($speed) . "</td>";
            echo "<td>" . htmlspecialchars($price) . "</td>";
            echo "<td>" . htmlspecialchars($orderDate) . "</td>";
            echo "<td>" . htmlspecialchars($status) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // 첫 번째 결과 상세 확인
        if (!empty($result)) {
            $first = $result[0];
            echo "<h2>첫 번째 결과 상세 (JSON)</h2>";
            echo "<pre style='background: #1f2937; color: #f9fafb; padding: 15px; border-radius: 6px; overflow-x: auto;'>";
            echo htmlspecialchars(json_encode($first, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            echo "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</div></body></html>";








<?php
/**
 * 모든 인터넷 신청 정보 확인 스크립트
 * 사용법: http://localhost/MVNO/debug-all-applications.php
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결 실패');
    }
    
    // 모든 인터넷 신청 조회
    $stmt = $pdo->query("
        SELECT 
            a.id as application_id,
            a.order_number,
            a.product_id,
            a.application_status,
            a.created_at as order_date,
            c.user_id,
            c.name,
            c.phone,
            c.additional_info,
            internet.registration_place as current_registration_place
        FROM product_applications a
        INNER JOIN application_customers c ON a.id = c.application_id
        LEFT JOIN products p ON a.product_id = p.id
        LEFT JOIN product_internet_details internet ON p.id = internet.product_id
        WHERE a.product_type = 'internet'
        ORDER BY a.created_at DESC
        LIMIT 20
    ");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<!DOCTYPE html>";
    echo "<html><head><meta charset='utf-8'><title>디버깅: 모든 인터넷 신청</title>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #6366f1; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; font-size: 13px; }
        th { background: #f8f9fa; font-weight: 600; color: #333; position: sticky; top: 0; }
        tr:hover { background: #f8f9fa; }
        .value { font-family: monospace; background: #f8f9fa; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .error { color: #dc2626; font-weight: 600; }
        .success { color: #10b981; font-weight: 600; }
        .warning { color: #f59e0b; font-weight: 600; }
        .link { color: #6366f1; text-decoration: none; }
        .link:hover { text-decoration: underline; }
        .json-preview { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    </style></head><body>";
    
    echo "<div class='container'>";
    echo "<h1>디버깅: 모든 인터넷 신청 정보 (최근 20개)</h1>";
    
    if (empty($applications)) {
        echo "<p class='warning'>신청 정보가 없습니다.</p>";
    } else {
        echo "<table>";
        echo "<tr>";
        echo "<th>신청 ID</th>";
        echo "<th>주문번호</th>";
        echo "<th>상품 ID</th>";
        echo "<th>사용자 ID</th>";
        echo "<th>고객명</th>";
        echo "<th>신청일시</th>";
        echo "<th>상태</th>";
        echo "<th>현재 테이블<br>registration_place</th>";
        echo "<th>product_snapshot<br>registration_place</th>";
        echo "<th>상세보기</th>";
        echo "</tr>";
        
        foreach ($applications as $app) {
            // additional_info 파싱
            $additionalInfo = [];
            $additionalInfoRaw = $app['additional_info'] ?? '';
            $productSnapshot = null;
            $snapshotRegPlace = null;
            
            if (!empty($additionalInfoRaw)) {
                $additionalInfoStr = str_replace(["\n", "\r", "\t"], ['', '', ''], $additionalInfoRaw);
                $additionalInfo = json_decode($additionalInfoStr, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($additionalInfo)) {
                    $productSnapshot = $additionalInfo['product_snapshot'] ?? null;
                    if ($productSnapshot && !empty($productSnapshot)) {
                        $snapshotRegPlace = $productSnapshot['registration_place'] ?? null;
                    }
                }
            }
            
            $appId = $app['application_id'];
            $currentRegPlace = $app['current_registration_place'] ?? null;
            
            // 값 비교
            $hasMismatch = false;
            if ($snapshotRegPlace !== null && $currentRegPlace !== null && $snapshotRegPlace !== $currentRegPlace) {
                $hasMismatch = true;
            }
            
            echo "<tr" . ($hasMismatch ? " style='background: #fef3c7;'" : "") . ">";
            echo "<td><span class='value'>{$appId}</span></td>";
            echo "<td>" . htmlspecialchars($app['order_number'] ?? '-') . "</td>";
            echo "<td><span class='value'>{$app['product_id']}</span></td>";
            echo "<td>" . htmlspecialchars($app['user_id'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($app['name'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($app['order_date'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($app['application_status'] ?? '-') . "</td>";
            echo "<td><span class='value'>" . htmlspecialchars($currentRegPlace ?? 'NULL') . "</span></td>";
            echo "<td><span class='value'>" . htmlspecialchars($snapshotRegPlace ?? 'NULL') . "</span></td>";
            echo "<td><a href='debug-internet-application.php?application_id={$appId}' class='link' target='_blank'>상세보기</a></td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<p style='margin-top: 20px; padding: 10px; background: #fef3c7; border-radius: 6px;'>";
        echo "<strong>노란색 행:</strong> product_snapshot과 현재 테이블 값이 다른 신청 (문제 가능성)";
        echo "</p>";
    }
    
    echo "</div></body></html>";
    
} catch (Exception $e) {
    echo "<h1>오류 발생</h1>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}





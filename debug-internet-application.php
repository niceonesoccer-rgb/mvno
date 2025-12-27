<?php
/**
 * 인터넷 신청 정보 디버깅 스크립트
 * 사용법: http://localhost/MVNO/debug-internet-application.php?application_id=123
 */

require_once __DIR__ . '/includes/data/db-config.php';

header('Content-Type: text/html; charset=utf-8');

// application_id 파라미터 확인
$applicationId = isset($_GET['application_id']) ? intval($_GET['application_id']) : 0;

if (empty($applicationId)) {
    echo "<h1>디버깅: 인터넷 신청 정보</h1>";
    echo "<p style='color: red;'>application_id 파라미터가 필요합니다.</p>";
    echo "<p>사용법: ?application_id=123</p>";
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결 실패');
    }
    
    // 신청 정보 조회
    $stmt = $pdo->prepare("
        SELECT 
            a.id as application_id,
            a.order_number,
            a.product_id,
            a.application_status,
            a.created_at as order_date,
            c.name,
            c.phone,
            c.email,
            c.additional_info,
            internet.registration_place as current_registration_place,
            internet.service_type as current_service_type,
            internet.speed_option as current_speed_option,
            internet.monthly_fee as current_monthly_fee
        FROM product_applications a
        INNER JOIN application_customers c ON a.id = c.application_id
        LEFT JOIN products p ON a.product_id = p.id
        LEFT JOIN product_internet_details internet ON p.id = internet.product_id
        WHERE a.id = :application_id
        LIMIT 1
    ");
    $stmt->execute([':application_id' => $applicationId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        echo "<h1>디버깅: 인터넷 신청 정보</h1>";
        echo "<p style='color: red;'>신청 정보를 찾을 수 없습니다. (application_id: {$applicationId})</p>";
        exit;
    }
    
    // additional_info 파싱
    $additionalInfo = [];
    $additionalInfoRaw = $application['additional_info'] ?? '';
    if (!empty($additionalInfoRaw)) {
        $additionalInfoStr = $additionalInfoRaw;
        $additionalInfoStr = str_replace(["\n", "\r", "\t"], ['', '', ''], $additionalInfoStr);
        $additionalInfo = json_decode($additionalInfoStr, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $jsonError = json_last_error_msg();
        }
    }
    
    $productSnapshot = $additionalInfo['product_snapshot'] ?? null;
    
    echo "<!DOCTYPE html>";
    echo "<html><head><meta charset='utf-8'><title>디버깅: 신청 정보 #{$applicationId}</title>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #6366f1; padding-bottom: 10px; }
        h2 { color: #6366f1; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; color: #333; }
        .value { font-family: monospace; background: #f8f9fa; padding: 4px 8px; border-radius: 4px; }
        .error { color: #dc2626; font-weight: 600; }
        .success { color: #10b981; font-weight: 600; }
        .warning { color: #f59e0b; font-weight: 600; }
        .json-block { background: #1f2937; color: #f9fafb; padding: 15px; border-radius: 6px; overflow-x: auto; font-family: monospace; font-size: 12px; white-space: pre-wrap; }
        .comparison { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0; }
        .comparison-item { background: #f8f9fa; padding: 15px; border-radius: 6px; }
        .snapshot-value { color: #10b981; font-weight: 600; }
        .current-value { color: #6366f1; font-weight: 600; }
    </style></head><body>";
    
    echo "<div class='container'>";
    echo "<h1>디버깅: 인터넷 신청 정보 #{$applicationId}</h1>";
    
    // 기본 정보
    echo "<h2>1. 기본 신청 정보</h2>";
    echo "<table>";
    echo "<tr><th>항목</th><th>값</th></tr>";
    echo "<tr><td>신청 ID</td><td><span class='value'>{$application['application_id']}</span></td></tr>";
    echo "<tr><td>주문번호</td><td><span class='value'>" . htmlspecialchars($application['order_number'] ?? '-') . "</span></td></tr>";
    echo "<tr><td>상품 ID</td><td><span class='value'>{$application['product_id']}</span></td></tr>";
    echo "<tr><td>신청 상태</td><td><span class='value'>" . htmlspecialchars($application['application_status'] ?? '-') . "</span></td></tr>";
    echo "<tr><td>신청일시</td><td><span class='value'>" . htmlspecialchars($application['order_date'] ?? '-') . "</span></td></tr>";
    echo "<tr><td>고객명</td><td><span class='value'>" . htmlspecialchars($application['name'] ?? '-') . "</span></td></tr>";
    echo "<tr><td>전화번호</td><td><span class='value'>" . htmlspecialchars($application['phone'] ?? '-') . "</span></td></tr>";
    echo "</table>";
    
    // additional_info 파싱 결과
    echo "<h2>2. additional_info 파싱 결과</h2>";
    if (!empty($additionalInfoRaw)) {
        if (isset($jsonError)) {
            echo "<p class='error'>JSON 파싱 오류: " . htmlspecialchars($jsonError) . "</p>";
        } else {
            echo "<p class='success'>JSON 파싱 성공</p>";
        }
        echo "<p><strong>원본 데이터 (처음 500자):</strong></p>";
        echo "<div class='json-block'>" . htmlspecialchars(substr($additionalInfoRaw, 0, 500)) . "</div>";
        echo "<p><strong>파싱된 데이터:</strong></p>";
        echo "<div class='json-block'>" . htmlspecialchars(json_encode($additionalInfo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "</div>";
    } else {
        echo "<p class='warning'>additional_info가 비어있습니다.</p>";
    }
    
    // product_snapshot 확인
    echo "<h2>3. product_snapshot 확인</h2>";
    if ($productSnapshot && !empty($productSnapshot)) {
        echo "<p class='success'>product_snapshot이 존재합니다.</p>";
        echo "<div class='json-block'>" . htmlspecialchars(json_encode($productSnapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) . "</div>";
        
        $snapshotRegPlace = $productSnapshot['registration_place'] ?? null;
        $snapshotSpeed = $productSnapshot['speed_option'] ?? null;
        $snapshotService = $productSnapshot['service_type'] ?? null;
        $snapshotMonthlyFee = $productSnapshot['monthly_fee'] ?? null;
        
        echo "<h3>product_snapshot 주요 필드:</h3>";
        echo "<table>";
        echo "<tr><th>필드</th><th>값</th><th>상태</th></tr>";
        echo "<tr><td>registration_place</td><td><span class='value snapshot-value'>" . htmlspecialchars($snapshotRegPlace ?? 'NULL') . "</span></td><td>" . (isset($productSnapshot['registration_place']) ? "<span class='success'>존재함</span>" : "<span class='error'>없음</span>") . "</td></tr>";
        echo "<tr><td>speed_option</td><td><span class='value snapshot-value'>" . htmlspecialchars($snapshotSpeed ?? 'NULL') . "</span></td><td>" . (isset($productSnapshot['speed_option']) ? "<span class='success'>존재함</span>" : "<span class='error'>없음</span>") . "</td></tr>";
        echo "<tr><td>service_type</td><td><span class='value snapshot-value'>" . htmlspecialchars($snapshotService ?? 'NULL') . "</span></td><td>" . (isset($productSnapshot['service_type']) ? "<span class='success'>존재함</span>" : "<span class='error'>없음</span>") . "</td></tr>";
        echo "<tr><td>monthly_fee</td><td><span class='value snapshot-value'>" . htmlspecialchars($snapshotMonthlyFee ?? 'NULL') . "</span></td><td>" . (isset($productSnapshot['monthly_fee']) ? "<span class='success'>존재함</span>" : "<span class='error'>없음</span>") . "</td></tr>";
        echo "</table>";
    } else {
        echo "<p class='error'>product_snapshot이 없거나 비어있습니다.</p>";
    }
    
    // 현재 테이블 값과 비교
    echo "<h2>4. 신청 시점 정보 vs 현재 테이블 값 비교</h2>";
    echo "<div class='comparison'>";
    
    echo "<div class='comparison-item'>";
    echo "<h3>신청 시점 정보 (product_snapshot)</h3>";
    echo "<table>";
    echo "<tr><th>필드</th><th>값</th></tr>";
    echo "<tr><td>registration_place</td><td><span class='value snapshot-value'>" . htmlspecialchars($snapshotRegPlace ?? 'NULL') . "</span></td></tr>";
    echo "<tr><td>speed_option</td><td><span class='value snapshot-value'>" . htmlspecialchars($snapshotSpeed ?? 'NULL') . "</span></td></tr>";
    echo "<tr><td>service_type</td><td><span class='value snapshot-value'>" . htmlspecialchars($snapshotService ?? 'NULL') . "</span></td></tr>";
    echo "<tr><td>monthly_fee</td><td><span class='value snapshot-value'>" . htmlspecialchars($snapshotMonthlyFee ?? 'NULL') . "</span></td></tr>";
    echo "</table>";
    echo "</div>";
    
    echo "<div class='comparison-item'>";
    echo "<h3>현재 테이블 값 (product_internet_details)</h3>";
    echo "<table>";
    echo "<tr><th>필드</th><th>값</th></tr>";
    echo "<tr><td>registration_place</td><td><span class='value current-value'>" . htmlspecialchars($application['current_registration_place'] ?? 'NULL') . "</span></td></tr>";
    echo "<tr><td>speed_option</td><td><span class='value current-value'>" . htmlspecialchars($application['current_speed_option'] ?? 'NULL') . "</span></td></tr>";
    echo "<tr><td>service_type</td><td><span class='value current-value'>" . htmlspecialchars($application['current_service_type'] ?? 'NULL') . "</span></td></tr>";
    echo "<tr><td>monthly_fee</td><td><span class='value current-value'>" . htmlspecialchars($application['current_monthly_fee'] ?? 'NULL') . "</span></td></tr>";
    echo "</table>";
    echo "</div>";
    
    echo "</div>";
    
    // getUserInternetApplications 함수 시뮬레이션
    echo "<h2>5. getUserInternetApplications 함수 로직 시뮬레이션</h2>";
    
    $hasSnapshot = $productSnapshot && !empty($productSnapshot);
    echo "<p><strong>hasSnapshot:</strong> " . ($hasSnapshot ? "<span class='success'>true</span>" : "<span class='error'>false</span>") . "</p>";
    
    if ($hasSnapshot) {
        // 실제 함수 로직 시뮬레이션
        if (isset($productSnapshot['registration_place'])) {
            $finalRegistrationPlace = trim($productSnapshot['registration_place']);
            $source = "product_snapshot";
        } else {
            $finalRegistrationPlace = !empty($application['current_registration_place']) ? trim($application['current_registration_place']) : '';
            $source = "current_table (fallback)";
        }
        
        if (isset($productSnapshot['speed_option'])) {
            $finalSpeedOption = trim($productSnapshot['speed_option']);
        } else {
            $finalSpeedOption = !empty($application['current_speed_option']) ? trim($application['current_speed_option']) : '';
        }
        
        if (isset($productSnapshot['service_type'])) {
            $finalServiceType = trim($productSnapshot['service_type']);
        } else {
            $finalServiceType = !empty($application['current_service_type']) ? trim($application['current_service_type']) : '';
        }
        
        echo "<table>";
        echo "<tr><th>필드</th><th>최종 값</th><th>출처</th></tr>";
        echo "<tr><td>provider (registration_place)</td><td><span class='value'>" . htmlspecialchars($finalRegistrationPlace) . "</span></td><td><span class='success'>{$source}</span></td></tr>";
        echo "<tr><td>speed_option</td><td><span class='value'>" . htmlspecialchars($finalSpeedOption) . "</span></td><td>" . (isset($productSnapshot['speed_option']) ? "<span class='success'>product_snapshot</span>" : "<span class='warning'>current_table</span>") . "</td></tr>";
        echo "<tr><td>service_type</td><td><span class='value'>" . htmlspecialchars($finalServiceType) . "</span></td><td>" . (isset($productSnapshot['service_type']) ? "<span class='success'>product_snapshot</span>" : "<span class='warning'>current_table</span>") . "</td></tr>";
        echo "</table>";
        
        // 문제 진단
        echo "<h3>진단 결과</h3>";
        if ($finalRegistrationPlace !== ($snapshotRegPlace ?? '')) {
            echo "<p class='error'>⚠️ 문제 발견: registration_place가 product_snapshot에서 가져오지 않았습니다!</p>";
            echo "<p>예상 값: " . htmlspecialchars($snapshotRegPlace ?? 'NULL') . "</p>";
            echo "<p>실제 사용된 값: " . htmlspecialchars($finalRegistrationPlace) . "</p>";
        } else {
            echo "<p class='success'>✓ registration_place가 product_snapshot에서 올바르게 가져와졌습니다.</p>";
        }
    } else {
        echo "<p class='warning'>product_snapshot이 없어서 현재 테이블 값을 사용합니다.</p>";
    }
    
    echo "</div></body></html>";
    
} catch (Exception $e) {
    echo "<h1>오류 발생</h1>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}







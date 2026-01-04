<?php
/**
 * 광고 가격 조회 API
 */

// 에러 출력 방지 (JSON 응답이므로)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 출력 버퍼링 시작 (예상치 못한 출력 방지)
ob_start();

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../includes/data/db-config.php';
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '초기화 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 출력 버퍼 정리 (예상치 못한 출력 제거)
ob_clean();

$pdo = getDBConnection();

if (!$pdo) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '데이터베이스 연결 실패'], JSON_UNESCAPED_UNICODE);
    exit;
}

$productType = $_GET['product_type'] ?? '';
$advertisementDays = intval($_GET['advertisement_days'] ?? 0);

if (empty($productType) || $advertisementDays <= 0) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '필수 파라미터가 누락되었습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// rotation_advertisement_prices 테이블은 mno_sim (언더스코어)를 사용하므로 변환
// products 테이블은 mno-sim (하이픈)을 사용하지만, 광고 가격 테이블은 mno_sim을 사용
if ($productType === 'mno-sim') {
    $productType = 'mno_sim';
}

try {
    $stmt = $pdo->prepare("
        SELECT price FROM rotation_advertisement_prices 
        WHERE product_type = :product_type 
        AND advertisement_days = :advertisement_days 
        AND is_active = 1
    ");
    $stmt->execute([
        ':product_type' => $productType,
        ':advertisement_days' => $advertisementDays
    ]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 출력 버퍼 정리 후 JSON 응답
    ob_end_clean();
    
    if ($result) {
        echo json_encode(['success' => true, 'price' => floatval($result['price'])], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => '가격 정보를 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE);
    }
} catch (PDOException $e) {
    ob_end_clean();
    error_log('Advertisement price API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '가격 조회 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    ob_end_clean();
    error_log('Advertisement price API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '가격 조회 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
}

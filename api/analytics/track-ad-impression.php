<?php
/**
 * 광고 노출 추적 API
 */

// 에러 출력 방지 (JSON 응답이므로)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 출력 버퍼링 시작
ob_start();

header('Content-Type: application/json; charset=utf-8');

// 응답 함수
function sendJsonResponse($data, $statusCode = 200) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/../../includes/data/advertisement-analytics-functions.php';
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => '초기화 중 오류가 발생했습니다.'], 500);
}

// 출력 버퍼 정리
ob_clean();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'POST 요청만 허용됩니다.'], 405);
}

// JSON 데이터 읽기 (POST body)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// JSON 파싱 실패 시 POST 데이터로 시도
if ($data === null && !empty($_POST)) {
    $data = $_POST;
}

// 필수 파라미터 확인
$advertisementId = isset($data['advertisement_id']) ? (int)$data['advertisement_id'] : 0;
$productId = isset($data['product_id']) ? (int)$data['product_id'] : 0;
$sellerId = isset($data['seller_id']) ? trim($data['seller_id']) : '';
$productType = isset($data['product_type']) ? trim($data['product_type']) : '';

if ($advertisementId <= 0 || $productId <= 0 || empty($sellerId) || empty($productType)) {
    sendJsonResponse([
        'success' => false, 
        'message' => '필수 파라미터가 누락되었습니다.',
        'required' => ['advertisement_id', 'product_id', 'seller_id', 'product_type']
    ], 400);
}

// 상품 타입 검증
$validProductTypes = ['mvno', 'mno', 'internet', 'mno_sim', 'mno-sim'];
if (!in_array($productType, $validProductTypes)) {
    sendJsonResponse([
        'success' => false, 
        'message' => '유효하지 않은 상품 타입입니다.',
        'valid_types' => $validProductTypes
    ], 400);
}

// mno-sim을 mno_sim으로 변환 (DB는 언더스코어 사용)
if ($productType === 'mno-sim') {
    $productType = 'mno_sim';
}

// 광고 노출 추적
$result = trackAdvertisementImpression($advertisementId, $productId, $sellerId, $productType);

if ($result) {
    sendJsonResponse(['success' => true, 'message' => '광고 노출이 기록되었습니다.']);
} else {
    sendJsonResponse(['success' => false, 'message' => '광고 노출 기록에 실패했습니다.'], 500);
}

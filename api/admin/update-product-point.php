<?php
/**
 * 관리자 상품 포인트 및 혜택내용 업데이트 API
 */

// 에러 출력 방지 (JSON 응답을 위해)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 출력 버퍼링 시작 (에러 방지)
ob_start();

// CORS 헤더 (필요시)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Access-Control-Allow-Credentials: true");
}

// OPTIONS 요청 처리 (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

// 출력 버퍼 비우기 (이전 출력 제거)
ob_clean();

// JSON 헤더 설정 (출력 전에)
header('Content-Type: application/json; charset=utf-8');

// 관리자 권한 체크
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => '관리자만 접근할 수 있습니다.']);
    exit;
}

// POST 데이터 받기 - FormData 우선 처리
$input = null;
$rawInput = '';

// Content-Type 확인
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$isJson = strpos($contentType, 'application/json') !== false;
$isFormData = strpos($contentType, 'multipart/form-data') !== false || strpos($contentType, 'application/x-www-form-urlencoded') !== false;

error_log("API Request - Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A'));
error_log("API Request - Content-Type: " . $contentType);
error_log("API Request - Is JSON: " . ($isJson ? 'Yes' : 'No'));
error_log("API Request - Is FormData: " . ($isFormData ? 'Yes' : 'No'));

// FormData가 있으면 우선 사용 (웹서버 호환성)
if (!empty($_POST)) {
    $input = $_POST;
    error_log("API Request - Using POST data (FormData): " . json_encode($_POST));
} else if ($isJson) {
    // JSON 본문 읽기
    $rawInput = file_get_contents('php://input');
    error_log("API Request - Raw Input Length: " . strlen($rawInput));
    error_log("API Request - Raw Input (first 500 chars): " . substr($rawInput, 0, 500));
    
    if (!empty($rawInput)) {
        $input = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("API Request - JSON decode error: " . json_last_error_msg());
            error_log("API Request - Raw input: " . $rawInput);
        } else {
            error_log("API Request - Using JSON data: " . json_encode($input));
        }
    }
}

// 데이터가 없으면 GET 파라미터 시도 (디버깅용)
if (!$input || empty($input)) {
    if (!empty($_GET)) {
        $input = $_GET;
        error_log("API Request - Using GET data: " . json_encode($_GET));
    }
}

$productId = isset($input['product_id']) ? intval($input['product_id']) : 0;
$pointSetting = isset($input['point_setting']) ? intval($input['point_setting']) : 0;
$benefitDescription = isset($input['point_benefit_description']) ? trim($input['point_benefit_description']) : '';

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => '유효하지 않은 상품 ID입니다.']);
    exit;
}

// 포인트 검증 (1000원 단위)
if ($pointSetting > 0 && $pointSetting % 1000 !== 0) {
    echo json_encode(['success' => false, 'message' => '포인트는 1000원 단위로 입력해주세요.']);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    // 상품 존재 확인
    $checkStmt = $pdo->prepare("SELECT id FROM products WHERE id = :product_id AND status != 'deleted'");
    $checkStmt->execute([':product_id' => $productId]);
    if (!$checkStmt->fetch()) {
        throw new Exception('상품을 찾을 수 없습니다.');
    }
    
    // 포인트 및 혜택내용 업데이트
    $updateStmt = $pdo->prepare("
        UPDATE products 
        SET point_setting = :point_setting,
            point_benefit_description = :point_benefit_description,
            updated_at = NOW()
        WHERE id = :product_id
    ");
    
    $updateStmt->execute([
        ':point_setting' => $pointSetting,
        ':point_benefit_description' => $benefitDescription ?: null,
        ':product_id' => $productId
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => '포인트 및 혜택내용이 저장되었습니다.',
        'data' => [
            'product_id' => $productId,
            'point_setting' => $pointSetting,
            'point_benefit_description' => $benefitDescription
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Update product point error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '데이터베이스 오류가 발생했습니다.']);
} catch (Exception $e) {
    error_log("Update product point error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

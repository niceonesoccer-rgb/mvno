<?php
/**
 * 상품 일괄 상태 변경 API
 * POST /api/product-bulk-update.php
 */

// 에러 출력 방지 (JSON 응답을 위해)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 출력 버퍼링 시작 (에러 방지)
ob_start();

// CORS 헤더 설정 (필요한 경우)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

// OPTIONS 요청 처리 (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// 출력 버퍼 비우기 (이전 출력 제거)
ob_clean();

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// POST 요청만 허용
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($requestMethod !== 'POST') {
    http_response_code(405);
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'POST 메서드만 허용됩니다.',
        'received_method' => $requestMethod
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 인증 체크
$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['role'] !== 'seller') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다.'
    ]);
    exit;
}

// 판매자 승인 체크
$approvalStatus = $currentUser['approval_status'] ?? 'pending';
if ($approvalStatus !== 'approved') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => '승인된 판매자만 사용할 수 있습니다.'
    ]);
    exit;
}

// JSON 또는 FormData 데이터 읽기 (웹서버 호환성)
$data = null;

// FormData 요청 확인 (웹서버 호환성)
if (!empty($_POST)) {
    $data = $_POST;
} else {
    // JSON 요청 처리
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $data = null;
        }
    }
}

if (!$data || (!isset($data['product_ids']) && !isset($data['product_id'])) || !isset($data['status'])) {
    ob_clean();
    http_response_code(400);
    $errorResponse = [
        'success' => false,
        'message' => '잘못된 요청입니다. 필수 파라미터가 없습니다.'
    ];
    // 개발 환경에서만 디버그 정보 포함
    if (defined('DEBUG') && DEBUG) {
        $errorResponse['debug'] = [
            'has_data' => !empty($data),
            'keys' => $data ? array_keys($data) : [],
            'post_keys' => array_keys($_POST)
        ];
    }
    echo json_encode($errorResponse);
    exit;
}

// product_ids 처리 (FormData에서 JSON 문자열로 올 수 있음)
if (isset($data['product_ids'])) {
    // product_ids가 문자열인 경우 배열로 변환 (FormData에서 JSON 문자열로 전달된 경우)
    if (is_string($data['product_ids'])) {
        $decoded = json_decode($data['product_ids'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $data['product_ids'] = $decoded;
        } else {
            // JSON이 아니면 쉼표로 구분된 문자열일 수 있음
            $data['product_ids'] = array_filter(array_map('trim', explode(',', $data['product_ids'])));
        }
    }
} elseif (isset($data['product_id'])) {
    // 단일 product_id를 배열로 변환
    $data['product_ids'] = [$data['product_id']];
}

$productIds = $data['product_ids'];
$status = $data['status'];

// 상태 값 검증
$validStatuses = ['active', 'inactive'];
if (!in_array($status, $validStatuses)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '유효하지 않은 상태 값입니다.'
    ]);
    exit;
}

// 상품 ID 배열 검증
if (!is_array($productIds) || empty($productIds)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '상품 ID가 필요합니다.'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => '데이터베이스 연결에 실패했습니다.'
        ]);
        exit;
    }
    
    $sellerId = (string)$currentUser['user_id'];
    
    // 상품 ID를 정수로 변환 및 검증
    $validProductIds = [];
    foreach ($productIds as $id) {
        $id = intval($id);
        if ($id > 0) {
            $validProductIds[] = $id;
        }
    }
    
    if (empty($validProductIds)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '유효한 상품 ID가 없습니다.'
        ]);
        exit;
    }
    
    // 플레이스홀더 생성
    $placeholders = implode(',', array_fill(0, count($validProductIds), '?'));
    
    // 상품이 해당 판매자의 것인지 확인하고 상태 업데이트
    $stmt = $pdo->prepare("
        UPDATE products 
        SET status = ?, updated_at = NOW()
        WHERE id IN ($placeholders) 
        AND seller_id = ?
        AND status != 'deleted'
    ");
    
    $params = array_merge([$status], $validProductIds, [$sellerId]);
    $stmt->execute($params);
    
    $affectedRows = $stmt->rowCount();
    
    if ($affectedRows > 0) {
        ob_clean(); // 출력 버퍼 비우기
        echo json_encode([
            'success' => true,
            'message' => $affectedRows . '개의 상품이 업데이트되었습니다.',
            'affected_count' => $affectedRows
        ]);
    } else {
        ob_clean(); // 출력 버퍼 비우기
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '업데이트할 상품을 찾을 수 없습니다.'
        ]);
    }
    
} catch (PDOException $e) {
    ob_clean(); // 출력 버퍼 비우기
    error_log("Error updating products: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '상품 상태 변경 중 오류가 발생했습니다.',
        'error' => (defined('DEBUG') && DEBUG) ? $e->getMessage() : null
    ]);
} catch (Exception $e) {
    ob_clean(); // 출력 버퍼 비우기
    error_log("Unexpected error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '예기치 않은 오류가 발생했습니다.',
        'error' => (defined('DEBUG') && DEBUG) ? $e->getMessage() : null
    ]);
} finally {
    // 출력 버퍼 종료
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
}

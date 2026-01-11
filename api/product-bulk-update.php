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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'POST 메서드만 허용됩니다.'
    ]);
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

if (!$data || !isset($data['product_ids']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '잘못된 요청입니다.'
    ]);
    exit;
}

// product_ids가 문자열인 경우 배열로 변환 (FormData에서)
if (is_string($data['product_ids'])) {
    $data['product_ids'] = json_decode($data['product_ids'], true);
    if (!is_array($data['product_ids'])) {
        $data['product_ids'] = [$data['product_ids']];
    }
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
        echo json_encode([
            'success' => true,
            'message' => $affectedRows . '개의 상품이 업데이트되었습니다.',
            'affected_count' => $affectedRows
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '업데이트할 상품을 찾을 수 없습니다.'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Error updating products: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '상품 상태 변경 중 오류가 발생했습니다.'
    ]);
} catch (Exception $e) {
    error_log("Unexpected error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '예기치 않은 오류가 발생했습니다.'
    ]);
}



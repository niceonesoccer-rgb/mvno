<?php
/**
 * 상품 삭제 API
 * POST /api/product-delete.php
 */

header('Content-Type: application/json; charset=utf-8');

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

// JSON 데이터 읽기
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['product_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '잘못된 요청입니다.'
    ]);
    exit;
}

$productId = intval($data['product_id']);

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '유효하지 않은 상품 ID입니다.'
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
    
    // 상품 소유권 확인
    $stmt = $pdo->prepare("
        SELECT id, product_type FROM products 
        WHERE id = :product_id AND seller_id = :seller_id AND status != 'deleted'
    ");
    $stmt->execute([
        ':product_id' => $productId,
        ':seller_id' => $sellerId
    ]);
    
    $product = $stmt->fetch();
    
    if (!$product) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '상품을 찾을 수 없습니다.'
        ]);
        exit;
    }
    
    // 상품 삭제 (soft delete)
    $pdo->beginTransaction();
    
    try {
        $updateStmt = $pdo->prepare("
            UPDATE products 
            SET status = 'deleted', updated_at = NOW()
            WHERE id = :product_id
        ");
        $updateStmt->execute([':product_id' => $productId]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => '상품이 삭제되었습니다.'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Error deleting product: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '상품 삭제 중 오류가 발생했습니다.'
    ]);
} catch (Exception $e) {
    error_log("Unexpected error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '예기치 않은 오류가 발생했습니다.'
    ]);
}


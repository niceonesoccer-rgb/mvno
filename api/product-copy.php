<?php
/**
 * 상품 복사 API
 * POST /api/product-copy.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/product-functions.php';

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

if (!$data || !isset($data['product_id']) || !isset($data['product_type'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '잘못된 요청입니다.'
    ]);
    exit;
}

$productId = intval($data['product_id']);
$productType = $data['product_type'];

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '유효하지 않은 상품 ID입니다.'
    ]);
    exit;
}

$validTypes = ['mvno', 'mno', 'internet'];
if (!in_array($productType, $validTypes)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '유효하지 않은 상품 타입입니다.'
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
    
    // 원본 상품 확인 및 데이터 가져오기
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE id = :product_id AND seller_id = :seller_id AND status != 'deleted'
    ");
    $stmt->execute([
        ':product_id' => $productId,
        ':seller_id' => $sellerId
    ]);
    
    $originalProduct = $stmt->fetch();
    
    if (!$originalProduct) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '상품을 찾을 수 없습니다.'
        ]);
        exit;
    }
    
    // 상품 타입별 상세 정보 가져오기
    $detailData = null;
    $detailTable = '';
    
    switch ($productType) {
        case 'mvno':
            $detailTable = 'product_mvno_details';
            break;
        case 'mno':
            $detailTable = 'product_mno_details';
            break;
        case 'internet':
            $detailTable = 'product_internet_details';
            break;
    }
    
    if ($detailTable) {
        $detailStmt = $pdo->prepare("SELECT * FROM {$detailTable} WHERE product_id = :product_id");
        $detailStmt->execute([':product_id' => $productId]);
        $detailData = $detailStmt->fetch();
    }
    
    // 상품 복사
    $pdo->beginTransaction();
    
    try {
        // 1. 기본 상품 정보 복사
        $newProductStmt = $pdo->prepare("
            INSERT INTO products (seller_id, product_type, status, view_count, favorite_count, review_count, share_count, application_count)
            VALUES (:seller_id, :product_type, :status, 0, 0, 0, 0, 0)
        ");
        $newProductStmt->execute([
            ':seller_id' => $sellerId,
            ':product_type' => $productType,
            ':status' => 'active'
        ]);
        
        $newProductId = $pdo->lastInsertId();
        
        // 2. 상세 정보 복사
        if ($detailData && $detailTable) {
            // product_id 필드 제외하고 모든 필드 복사
            $detailFields = [];
            $detailValues = [];
            $detailParams = [':product_id' => $newProductId];
            
            foreach ($detailData as $key => $value) {
                if ($key !== 'id' && $key !== 'product_id' && $key !== 'created_at' && $key !== 'updated_at') {
                    $detailFields[] = $key;
                    $detailValues[] = ':' . $key;
                    $detailParams[':' . $key] = $value;
                }
            }
            
            if (!empty($detailFields)) {
                $detailFields[] = 'product_id';
                $detailValues[] = ':product_id';
                
                $insertDetailStmt = $pdo->prepare("
                    INSERT INTO {$detailTable} (" . implode(', ', $detailFields) . ")
                    VALUES (" . implode(', ', $detailValues) . ")
                ");
                $insertDetailStmt->execute($detailParams);
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => '상품이 복사되었습니다.',
            'product_id' => $newProductId
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Error copying product: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '상품 복사 중 오류가 발생했습니다.'
    ]);
} catch (Exception $e) {
    error_log("Unexpected error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '예기치 않은 오류가 발생했습니다.'
    ]);
}


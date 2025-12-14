<?php
/**
 * 상품 정보 조회 API
 * 판매자가 상품 정보를 조회할 때 사용
 */

require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

header('Content-Type: application/json; charset=utf-8');

// 로그인 체크
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다.'
    ]);
    exit;
}

$currentUser = getCurrentUser();

// 판매자 로그인 체크
if (!$currentUser || $currentUser['role'] !== 'seller') {
    echo json_encode([
        'success' => false,
        'message' => '판매자만 접근 가능합니다.'
    ]);
    exit;
}

$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$productType = isset($_GET['product_type']) ? trim($_GET['product_type']) : '';

if (empty($productId) || empty($productType)) {
    echo json_encode([
        'success' => false,
        'message' => '필수 파라미터가 누락되었습니다.'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    $sellerId = (string)$currentUser['user_id'];
    
    if ($productType === 'mno') {
        // 통신사폰 상품 정보
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                mno.*
            FROM products p
            LEFT JOIN product_mno_details mno ON p.id = mno.product_id
            WHERE p.id = ? AND p.seller_id = ? AND p.product_type = 'mno' AND p.status != 'deleted'
            LIMIT 1
        ");
        $stmt->execute([$productId, $sellerId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception('상품을 찾을 수 없습니다.');
        }
        
        echo json_encode([
            'success' => true,
            'product' => $product
        ]);
    } else if ($productType === 'mvno') {
        // 알뜰폰 상품 정보
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                mvno.*
            FROM products p
            LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id
            WHERE p.id = ? AND p.seller_id = ? AND p.product_type = 'mvno' AND p.status != 'deleted'
            LIMIT 1
        ");
        $stmt->execute([$productId, $sellerId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception('상품을 찾을 수 없습니다.');
        }
        
        echo json_encode([
            'success' => true,
            'product' => $product
        ]);
    } else if ($productType === 'internet') {
        // 인터넷 상품 정보
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                inet.*
            FROM products p
            LEFT JOIN product_internet_details inet ON p.id = inet.product_id
            WHERE p.id = ? AND p.seller_id = ? AND p.product_type = 'internet' AND p.status != 'deleted'
            LIMIT 1
        ");
        $stmt->execute([$productId, $sellerId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception('상품을 찾을 수 없습니다.');
        }
        
        echo json_encode([
            'success' => true,
            'product' => $product
        ]);
    } else {
        throw new Exception('지원하지 않는 상품 타입입니다.');
    }
    
} catch (Exception $e) {
    error_log("Get Product Info Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

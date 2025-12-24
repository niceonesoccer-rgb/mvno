<?php
/**
 * 상품 검색 API
 * 이벤트 등록 시 상품 검색용
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';

// 관리자 권한 체크
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

$query = trim($_GET['q'] ?? '');

if (empty($query) || strlen($query) < 2) {
    echo json_encode(['success' => false, 'message' => '검색어는 최소 2자 이상 입력해주세요.']);
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => '데이터베이스 연결에 실패했습니다.']);
    exit;
}

try {
    $searchTerm = '%' . $query . '%';
    
    // 모든 상품 타입에서 검색
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.product_type,
            CASE 
                WHEN p.product_type = 'mvno' THEN mvno.plan_name
                WHEN p.product_type = 'mno' THEN mno.device_name
                WHEN p.product_type = 'internet' THEN CONCAT(inet.registration_place, ' ', inet.speed_option)
                ELSE '알 수 없음'
            END AS product_name,
            CASE 
                WHEN p.product_type = 'mvno' THEN '알뜰폰'
                WHEN p.product_type = 'mno' THEN '통신사폰'
                WHEN p.product_type = 'internet' THEN '인터넷'
                ELSE '기타'
            END AS product_type_name
        FROM products p
        LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id AND p.product_type = 'mvno'
        LEFT JOIN product_mno_details mno ON p.id = mno.product_id AND p.product_type = 'mno'
        LEFT JOIN product_internet_details inet ON p.id = inet.product_id AND p.product_type = 'internet'
        WHERE p.status = 'active'
        AND (
            (p.product_type = 'mvno' AND mvno.plan_name LIKE :search)
            OR (p.product_type = 'mno' AND mno.device_name LIKE :search)
            OR (p.product_type = 'internet' AND (
                inet.registration_place LIKE :search
                OR inet.speed_option LIKE :search
            ))
        )
        ORDER BY p.created_at DESC
        LIMIT 20
    ");
    
    $stmt->execute([':search' => $searchTerm]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    foreach ($products as $product) {
        $result[] = [
            'id' => (int)$product['id'],
            'name' => $product['product_name'] ?? '알 수 없음',
            'type' => $product['product_type_name'] ?? '기타',
            'product_type' => $product['product_type']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'products' => $result,
        'count' => count($result)
    ]);
    
} catch (PDOException $e) {
    error_log('Product search error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '검색 중 오류가 발생했습니다.']);
}


<?php
/**
 * 인터넷 상품 등록 API
 * 인터넷 전용 - 다른 상품 타입과 완전히 분리됨
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/product-functions.php';

header('Content-Type: application/json');

// 로그인 체크
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['role'] !== 'seller') {
    echo json_encode(['success' => false, 'message' => '판매자만 상품을 등록할 수 있습니다.']);
    exit;
}

// 판매자 승인 체크
if (!isSellerApproved()) {
    echo json_encode(['success' => false, 'message' => '판매자 승인이 필요합니다.']);
    exit;
}

// 인터넷 권한 체크 (Internet만)
if (!hasSellerPermission($currentUser['user_id'], 'internet')) {
    echo json_encode(['success' => false, 'message' => '인터넷 등록 권한이 없습니다. 관리자에게 권한을 요청하세요.']);
    exit;
}

// 인터넷 상품 데이터 수집
$productData = [
    'seller_id' => $currentUser['user_id'],
    'registration_place' => $_POST['registration_place'] ?? '',
    'speed_option' => $_POST['speed_option'] ?? '',
    'monthly_fee' => floatval(str_replace(',', '', $_POST['monthly_fee'] ?? 0)),
    'cash_payment_names' => $_POST['cash_payment_names'] ?? [],
    'cash_payment_prices' => $_POST['cash_payment_prices'] ?? [],
    'gift_card_names' => $_POST['gift_card_names'] ?? [],
    'gift_card_prices' => $_POST['gift_card_prices'] ?? [],
    'equipment_names' => $_POST['equipment_names'] ?? [],
    'equipment_prices' => $_POST['equipment_prices'] ?? [],
    'installation_names' => $_POST['installation_names'] ?? [],
    'installation_prices' => $_POST['installation_prices'] ?? []
];

// 필수 필드 검증
if (empty($productData['registration_place'])) {
    echo json_encode([
        'success' => false,
        'message' => '인터넷가입처를 선택해주세요.'
    ]);
    exit;
}

if (empty($productData['speed_option'])) {
    echo json_encode([
        'success' => false,
        'message' => '가입속도를 선택해주세요.'
    ]);
    exit;
}

// 인터넷 상품 데이터 저장
try {
    $productId = saveInternetProduct($productData);
    
    if ($productId === false) {
        echo json_encode([
            'success' => false,
            'message' => '상품 등록에 실패했습니다. 데이터베이스 연결을 확인해주세요.'
        ]);
        exit;
    }
} catch (Exception $e) {
    error_log("Product registration error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '상품 등록 중 오류가 발생했습니다: ' . $e->getMessage()
    ]);
    exit;
}

echo json_encode([
    'success' => true, 
    'message' => '인터넷 상품이 등록되었습니다.',
    'product_id' => $productId
]);




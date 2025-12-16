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
    'product_id' => isset($_POST['product_id']) ? intval($_POST['product_id']) : 0,
    'status' => isset($_POST['status']) ? $_POST['status'] : 'active',
    'registration_place' => trim($_POST['registration_place'] ?? ''),
    'speed_option' => trim($_POST['speed_option'] ?? ''),
    'monthly_fee' => isset($_POST['monthly_fee']) ? trim($_POST['monthly_fee']) : '', // 값+단위가 결합된 문자열 (정수만)
    'monthly_fee_unit' => $_POST['monthly_fee_unit'] ?? '원',
    'cash_payment_names' => isset($_POST['cash_payment_names']) && is_array($_POST['cash_payment_names']) ? $_POST['cash_payment_names'] : [],
    'cash_payment_prices' => isset($_POST['cash_payment_prices']) && is_array($_POST['cash_payment_prices']) ? $_POST['cash_payment_prices'] : [],
    'cash_payment_price_units' => isset($_POST['cash_payment_price_units']) && is_array($_POST['cash_payment_price_units']) ? $_POST['cash_payment_price_units'] : [],
    'gift_card_names' => isset($_POST['gift_card_names']) && is_array($_POST['gift_card_names']) ? $_POST['gift_card_names'] : [],
    'gift_card_prices' => isset($_POST['gift_card_prices']) && is_array($_POST['gift_card_prices']) ? $_POST['gift_card_prices'] : [],
    'gift_card_price_units' => isset($_POST['gift_card_price_units']) && is_array($_POST['gift_card_price_units']) ? $_POST['gift_card_price_units'] : [],
    'equipment_names' => isset($_POST['equipment_names']) && is_array($_POST['equipment_names']) ? $_POST['equipment_names'] : [],
    'equipment_prices' => isset($_POST['equipment_prices']) && is_array($_POST['equipment_prices']) ? $_POST['equipment_prices'] : [],
    'installation_names' => isset($_POST['installation_names']) && is_array($_POST['installation_names']) ? $_POST['installation_names'] : [],
    'installation_prices' => isset($_POST['installation_prices']) && is_array($_POST['installation_prices']) ? $_POST['installation_prices'] : []
];

// 디버깅: installation 데이터 확인
error_log("Installation data received - names: " . json_encode($productData['installation_names']) . ", prices: " . json_encode($productData['installation_prices']));

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
        'message' => '인터넷속도를 선택해주세요.'
    ]);
    exit;
}

// 인터넷 상품 데이터 저장
try {
    $productId = saveInternetProduct($productData);
    
    if ($productId === false) {
        global $lastDbError;
        $errorMessage = '상품 등록에 실패했습니다.';
        if (isset($lastDbError) && !empty($lastDbError)) {
            error_log("DB Error: " . $lastDbError);
            // 사용자에게는 간단한 메시지만 표시
            $errorMessage = '상품 등록에 실패했습니다. 입력 정보를 확인해주세요.';
        }
        
        echo json_encode([
            'success' => false,
            'message' => $errorMessage
        ]);
        exit;
    }
} catch (Exception $e) {
    error_log("Product registration error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => '상품 등록 중 오류가 발생했습니다. 다시 시도해주세요.'
    ]);
    exit;
}

echo json_encode([
    'success' => true, 
    'message' => isset($productData['product_id']) && $productData['product_id'] > 0 ? '인터넷 상품이 수정되었습니다.' : '인터넷 상품이 등록되었습니다.',
    'product_id' => $productId
]);




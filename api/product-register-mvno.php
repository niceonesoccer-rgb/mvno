<?php
/**
 * 알뜰폰 상품 등록 API
 * 알뜰폰(MVNO) 전용 - 다른 상품 타입과 완전히 분리됨
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

// 알뜰폰 권한 체크 (MVNO만)
if (!hasSellerPermission($currentUser['user_id'], 'mvno')) {
    echo json_encode(['success' => false, 'message' => '알뜰폰 등록 권한이 없습니다. 관리자에게 권한을 요청하세요.']);
    exit;
}

// 알뜰폰 상품 데이터 수집
$productData = [
    'seller_id' => $currentUser['user_id'],
    'product_id' => isset($_POST['product_id']) ? intval($_POST['product_id']) : 0,
    'provider' => $_POST['provider'] ?? '',
    'service_type' => $_POST['service_type'] ?? '',
    'plan_name' => $_POST['plan_name'] ?? '',
    'contract_period' => $_POST['contract_period'] ?? '',
    'contract_period_days' => $_POST['contract_period_days'] ?? '',
    'discount_period' => $_POST['discount_period'] ?? '',
    'price_main' => !empty($_POST['price_main']) ? floatval(str_replace(',', '', $_POST['price_main'])) : 0,
    'price_after' => ($_POST['price_after_type_hidden'] ?? '') === 'free' ? null : (isset($_POST['price_after']) && $_POST['price_after'] !== '' ? floatval(str_replace(',', '', $_POST['price_after'])) : null),
    'price_after_type_hidden' => $_POST['price_after_type_hidden'] ?? '',
    'data_amount' => $_POST['data_amount'] ?? '',
    'data_amount_value' => $_POST['data_amount_value'] ?? '',
    'data_unit' => $_POST['data_unit'] ?? '',
    'data_additional' => $_POST['data_additional'] ?? '',
    'data_additional_value' => (!empty($_POST['data_additional']) && $_POST['data_additional'] === '직접입력') ? ($_POST['data_additional_value'] ?? '') : '',
    'data_exhausted' => $_POST['data_exhausted'] ?? '',
    'data_exhausted_value' => $_POST['data_exhausted_value'] ?? '',
    'call_type' => $_POST['call_type'] ?? '',
    'call_amount' => $_POST['call_amount'] ?? '',
    'additional_call_type' => $_POST['additional_call_type'] ?? '',
    'additional_call' => $_POST['additional_call'] ?? '',
    'sms_type' => $_POST['sms_type'] ?? '',
    'sms_amount' => $_POST['sms_amount'] ?? '',
    'mobile_hotspot' => $_POST['mobile_hotspot'] ?? '',
    'mobile_hotspot_value' => $_POST['mobile_hotspot_value'] ?? '',
    'regular_sim_available' => $_POST['regular_sim_available'] ?? '',
    'regular_sim_price' => (!empty($_POST['regular_sim_price']) && ($_POST['regular_sim_available'] ?? '') === '배송가능') ? intval(str_replace(',', '', $_POST['regular_sim_price'])) : null,
    'nfc_sim_available' => $_POST['nfc_sim_available'] ?? '',
    'nfc_sim_price' => (!empty($_POST['nfc_sim_price']) && ($_POST['nfc_sim_available'] ?? '') === '배송가능') ? intval(str_replace(',', '', $_POST['nfc_sim_price'])) : null,
    'esim_available' => $_POST['esim_available'] ?? '',
    'esim_price' => (!empty($_POST['esim_price']) && ($_POST['esim_available'] ?? '') === '개통가능') ? intval(str_replace(',', '', $_POST['esim_price'])) : null,
    'over_data_price' => $_POST['over_data_price'] ?? '',
    'over_voice_price' => $_POST['over_voice_price'] ?? '',
    'over_video_price' => $_POST['over_video_price'] ?? '',
    'over_sms_price' => $_POST['over_sms_price'] ?? '',
    'over_lms_price' => $_POST['over_lms_price'] ?? '',
    'over_mms_price' => $_POST['over_mms_price'] ?? '',
    'promotion_title' => $_POST['promotion_title'] ?? '',
    'promotions' => $_POST['promotions'] ?? [],
    'benefits' => $_POST['benefits'] ?? [],
    'status' => $_POST['product_status'] ?? ($productData['product_id'] > 0 ? null : 'active'),
    'redirect_url' => !empty($_POST['redirect_url']) ? trim($_POST['redirect_url']) : null
];

// 필수 필드 검증
if (empty($productData['plan_name'])) {
    echo json_encode([
        'success' => false,
        'message' => '요금제명을 입력해주세요.'
    ]);
    exit;
}

if (empty($productData['provider'])) {
    echo json_encode([
        'success' => false,
        'message' => '통신사를 선택해주세요.'
    ]);
    exit;
}

// 알뜰폰 상품 데이터 저장
try {
    // DB 연결 테스트
    $pdo = getDBConnection();
    if (!$pdo) {
        global $lastDbConnectionError;
        $errorMessage = '데이터베이스 연결에 실패했습니다.';
        
        // 상세 에러 정보 추가
        if (isset($lastDbConnectionError)) {
            $errorMessage .= "\n\n오류 내용: " . htmlspecialchars($lastDbConnectionError);
            $errorMessage .= "\n\n확인 사항:";
            $errorMessage .= "\n1. MySQL 서버가 실행 중인지 확인하세요.";
            $errorMessage .= "\n2. 데이터베이스 '" . DB_NAME . "'가 존재하는지 확인하세요.";
            $errorMessage .= "\n3. DB 설정 파일을 확인하세요.";
        }
        
        echo json_encode([
            'success' => false,
            'message' => $errorMessage
        ]);
        exit;
    }
    
    $productId = saveMvnoProduct($productData);
    
    if ($productId === false) {
        global $lastDbError;
        $errorMessage = '상품 등록에 실패했습니다.';
        
        // 상세 에러 정보 포함
        if (isset($lastDbError)) {
            $errorMessage .= "\n\n오류: " . htmlspecialchars($lastDbError);
        } else {
            $errorMessage .= ' 데이터베이스 연결을 확인해주세요.';
        }
        
        // POST 데이터도 로깅
        error_log("Failed product registration. POST data: " . json_encode($_POST, JSON_UNESCAPED_UNICODE));
        
        // 디버깅 정보를 항상 응답에 포함
        $response = [
            'success' => false,
            'message' => $errorMessage,
            'debug' => [
                'lastDbError' => isset($lastDbError) ? $lastDbError : null,
                'postDataKeys' => array_keys($_POST),
                'productId' => $_POST['product_id'] ?? 'not set',
                'productDataKeys' => isset($productData) ? array_keys($productData) : []
            ]
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
} catch (Exception $e) {
    error_log("Product registration error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    error_log("POST data: " . json_encode($_POST));
    
    // 개발 환경에서는 상세 에러 표시
    $errorMessage = '상품 등록 중 오류가 발생했습니다: ' . $e->getMessage();
    if (defined('DEBUG') && DEBUG) {
        $errorMessage .= "\n\nStack trace:\n" . $e->getTraceAsString();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $errorMessage
    ]);
    exit;
}

$isEditMode = isset($productData['product_id']) && $productData['product_id'] > 0;
echo json_encode([
    'success' => true, 
    'message' => $isEditMode ? '알뜰폰 상품이 수정되었습니다.' : '알뜰폰 상품이 등록되었습니다.',
    'product_id' => $productId
]);




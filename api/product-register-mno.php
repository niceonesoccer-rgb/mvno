<?php
/**
 * 통신사폰 상품 등록 API
 * 통신사폰(MNO) 전용 - 다른 상품 타입과 완전히 분리됨
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

// 통신사폰 권한 체크 (MNO만)
if (!hasSellerPermission($currentUser['user_id'], 'mno')) {
    echo json_encode(['success' => false, 'message' => '통신사폰 등록 권한이 없습니다. 관리자에게 권한을 요청하세요.']);
    exit;
}

// 수정 모드 확인
$isEditMode = isset($_POST['product_id']) && intval($_POST['product_id']) > 0;
$productId = $isEditMode ? intval($_POST['product_id']) : 0;

// 통신사폰 상품 데이터 수집
$productData = [
    'seller_id' => $currentUser['user_id'],
    'board_type' => 'mno', // 고정값
    'product_id' => $productId,
    'status' => $_POST['product_status'] ?? ($isEditMode ? null : 'active'),
    'device_name' => $_POST['device_name'] ?? '',
    'device_price' => !empty($_POST['device_price']) ? floatval(str_replace(',', '', $_POST['device_price'])) : null,
    'device_capacity' => $_POST['device_capacity'] ?? '',
    'device_colors' => $_POST['device_colors'] ?? [],
    'common_provider' => $_POST['common_provider'] ?? [],
    'common_plan' => $_POST['common_plan'] ?? [],
    'common_discount_new' => $_POST['common_discount_new'] ?? [],
    'common_discount_port' => $_POST['common_discount_port'] ?? [],
    'common_discount_change' => $_POST['common_discount_change'] ?? [],
    'contract_provider' => $_POST['contract_provider'] ?? [],
    'contract_plan' => $_POST['contract_plan'] ?? [],
    'contract_discount_new' => $_POST['contract_discount_new'] ?? [],
    'contract_discount_port' => $_POST['contract_discount_port'] ?? [],
    'contract_discount_change' => $_POST['contract_discount_change'] ?? [],
    'service_type' => $_POST['service_type'] ?? '',
    'contract_period' => $_POST['contract_period'] ?? '',
    'contract_period_value' => $_POST['contract_period_value'] ?? '',
    'price_main' => !empty($_POST['price_main']) ? floatval(str_replace(',', '', $_POST['price_main'])) : 0,
    'data_amount' => $_POST['data_amount'] ?? '',
    'data_amount_value' => $_POST['data_amount_value'] ?? '',
    'data_unit' => $_POST['data_unit'] ?? '',
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
    'regular_sim_price' => $_POST['regular_sim_price'] ?? '',
    'nfc_sim_available' => $_POST['nfc_sim_available'] ?? '',
    'nfc_sim_price' => $_POST['nfc_sim_price'] ?? '',
    'esim_available' => $_POST['esim_available'] ?? '',
    'esim_price' => $_POST['esim_price'] ?? '',
    'over_data_price' => $_POST['over_data_price'] ?? '',
    'over_voice_price' => $_POST['over_voice_price'] ?? '',
    'over_video_price' => $_POST['over_video_price'] ?? '',
    'over_sms_price' => $_POST['over_sms_price'] ?? '',
    'over_lms_price' => $_POST['over_lms_price'] ?? '',
    'over_mms_price' => $_POST['over_mms_price'] ?? '',
    'promotion_title' => $_POST['promotion_title'] ?? '',
    'promotions' => $_POST['promotions'] ?? [],
    'benefits' => $_POST['benefits'] ?? [],
    'delivery_method' => $_POST['delivery_method'] ?? 'delivery',
    'visit_region' => $_POST['visit_region'] ?? '',
    'redirect_url' => !empty($_POST['redirect_url']) ? trim($_POST['redirect_url']) : null,
    'created_at' => date('Y-m-d H:i:s')
];

// 필수 필드 검증
if (empty($productData['device_name'])) {
    echo json_encode([
        'success' => false,
        'message' => '단말기를 선택해주세요.'
    ]);
    exit;
}

// device_id도 함께 저장할 수 있도록 추가
if (!empty($_POST['device_id'])) {
    $productData['device_id'] = intval($_POST['device_id']);
}

// 디버깅: 저장될 데이터 확인
error_log("MNO Product Data to Save:");
error_log("  device_name: " . ($productData['device_name'] ?? '없음'));
error_log("  device_price: " . ($productData['device_price'] ?? '없음'));
error_log("  device_capacity: " . ($productData['device_capacity'] ?? '없음'));
error_log("  seller_id: " . ($productData['seller_id'] ?? '없음'));

// 통신사폰 상품 데이터 저장
try {
    // 테이블 존재 여부 확인
    $pdo = getDBConnection();
    if ($pdo) {
        $checkTable = $pdo->query("SHOW TABLES LIKE 'product_mno_details'");
        if (!$checkTable->fetch()) {
            // 테이블이 없으면 생성 안내 메시지
            echo json_encode([
                'success' => false,
                'message' => '데이터베이스 테이블이 생성되지 않았습니다.',
                'error_details' => 'product_mno_details 테이블이 존재하지 않습니다.',
                'solution' => '다음 URL에서 테이블을 생성하세요: /MVNO/database/install_mno_tables.php',
                'debug_info' => [
                    'device_name' => $productData['device_name'] ?? '없음',
                    'device_price' => $productData['device_price'] ?? '없음',
                    'device_capacity' => $productData['device_capacity'] ?? '없음',
                    'seller_id' => $productData['seller_id'] ?? '없음'
                ]
            ]);
            exit;
        }
    }
    
    $productId = saveMnoProduct($productData);
    
    if ($productId === false) {
        // 상세 에러 정보 가져오기
        $errorMessage = '상품 등록에 실패했습니다.';
        $errorDetails = '';
        
        // 마지막 DB 에러 확인
        global $lastDbError;
        if (isset($lastDbError)) {
            $errorDetails = $lastDbError;
        }
        
        // PDO 에러 확인
        if ($pdo) {
            $errorInfo = $pdo->errorInfo();
            if (!empty($errorInfo[2])) {
                $errorDetails = $errorInfo[2];
            }
        }
        
        echo json_encode([
            'success' => false,
            'message' => $errorMessage,
            'error_details' => $errorDetails,
            'debug_info' => [
                'device_name' => $productData['device_name'] ?? '없음',
                'device_price' => $productData['device_price'] ?? '없음',
                'device_capacity' => $productData['device_capacity'] ?? '없음',
                'seller_id' => $productData['seller_id'] ?? '없음'
            ]
        ]);
        exit;
    }
} catch (Exception $e) {
    error_log("Product registration error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => '상품 등록 중 오류가 발생했습니다.',
        'error_details' => $e->getMessage(),
        'error_trace' => $e->getTraceAsString(),
        'debug_info' => [
            'device_name' => $productData['device_name'] ?? '없음',
            'device_price' => $productData['device_price'] ?? '없음',
            'device_capacity' => $productData['device_capacity'] ?? '없음',
            'seller_id' => $productData['seller_id'] ?? '없음'
        ]
    ]);
    exit;
}

echo json_encode([
    'success' => true, 
    'message' => '통신사폰 상품이 등록되었습니다.',
    'product_id' => $productId
]);


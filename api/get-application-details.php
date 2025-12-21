<?php
/**
 * 신청 상세 정보 조회 API
 * 
 * application_id를 받아서 해당 신청의 상세 정보를 반환합니다.
 * - 고객 정보 (name, phone, email, address 등)
 * - 신청 시점의 상품 정보 (product_snapshot)
 * - 주문 정보 (order_number, order_date, status)
 */

require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/product-functions.php';
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

// 현재 사용자 정보 가져오기
$currentUser = getCurrentUser();
if (!$currentUser) {
    echo json_encode([
        'success' => false,
        'message' => '로그인 정보를 확인할 수 없습니다.'
    ]);
    exit;
}

$userId = $currentUser['user_id'];

// application_id 파라미터 확인
$applicationId = isset($_GET['application_id']) ? intval($_GET['application_id']) : 0;

if (empty($applicationId)) {
    echo json_encode([
        'success' => false,
        'message' => '신청 ID가 필요합니다.'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    // 신청 정보와 고객 정보 조회 (본인 것만 조회 가능)
    // 판매자 페이지와 동일하게 product_internet_details 테이블의 현재 값을 함께 조회
    // MVNO 상품의 경우 product_mvno_details 테이블에서 benefits도 가져오기
    $stmt = $pdo->prepare("
        SELECT 
            a.id as application_id,
            a.order_number,
            a.product_id,
            a.product_type,
            a.application_status,
            a.created_at as order_date,
            COALESCE(a.status_changed_at, a.created_at) as status_changed_at,
            p.seller_id,
            c.name,
            c.phone,
            c.email,
            c.address,
            c.address_detail,
            c.birth_date,
            c.gender,
            c.additional_info,
            internet.registration_place,
            internet.service_type,
            internet.speed_option,
            internet.monthly_fee,
            internet.cash_payment_names,
            internet.cash_payment_prices,
            internet.gift_card_names,
            internet.gift_card_prices,
            internet.equipment_names,
            internet.equipment_prices,
            internet.installation_names,
            internet.installation_prices,
            mvno.benefits as mvno_benefits
        FROM product_applications a
        INNER JOIN application_customers c ON a.id = c.application_id
        INNER JOIN products p ON a.product_id = p.id
        LEFT JOIN product_internet_details internet ON p.id = internet.product_id
        LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id
        WHERE a.id = :application_id 
        AND c.user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([
        ':application_id' => $applicationId,
        ':user_id' => $userId
    ]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        echo json_encode([
            'success' => false,
            'message' => '신청 정보를 찾을 수 없거나 접근 권한이 없습니다.'
        ]);
        exit;
    }
    
    // additional_info 파싱
    $additionalInfo = [];
    if (!empty($application['additional_info'])) {
        $decoded = json_decode($application['additional_info'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $additionalInfo = $decoded;
        }
    }
    
    // 신청 시점의 상품 정보를 우선 사용 (product_snapshot)
    // 사용자가 신청했던 당시의 값이 나중에 변경되어도 유지되어야 함
    $productSnapshot = $additionalInfo['product_snapshot'] ?? null;
    
    // product_snapshot이 없거나 비어있으면 현재 테이블 값을 fallback으로 사용
    if (!$productSnapshot || empty($productSnapshot)) {
        if (!isset($additionalInfo['product_snapshot'])) {
            $additionalInfo['product_snapshot'] = [];
        }
        // 테이블 값이 있으면 fallback으로 사용
        if (!empty($application['registration_place']) || !empty($application['service_type']) || !empty($application['speed_option']) || isset($application['monthly_fee'])) {
            $additionalInfo['product_snapshot']['registration_place'] = $application['registration_place'] ?? '';
            $additionalInfo['product_snapshot']['service_type'] = $application['service_type'] ?? '';
            $additionalInfo['product_snapshot']['speed_option'] = $application['speed_option'] ?? '';
            $additionalInfo['product_snapshot']['monthly_fee'] = $application['monthly_fee'] ?? '';
            $additionalInfo['product_snapshot']['cash_payment_names'] = $application['cash_payment_names'] ?? '';
            $additionalInfo['product_snapshot']['cash_payment_prices'] = $application['cash_payment_prices'] ?? '';
            $additionalInfo['product_snapshot']['gift_card_names'] = $application['gift_card_names'] ?? '';
            $additionalInfo['product_snapshot']['gift_card_prices'] = $application['gift_card_prices'] ?? '';
            $additionalInfo['product_snapshot']['equipment_names'] = $application['equipment_names'] ?? '';
            $additionalInfo['product_snapshot']['equipment_prices'] = $application['equipment_prices'] ?? '';
            $additionalInfo['product_snapshot']['installation_names'] = $application['installation_names'] ?? '';
            $additionalInfo['product_snapshot']['installation_prices'] = $application['installation_prices'] ?? '';
        }
    }
    
    // MVNO 상품인 경우 benefits 정보 추가
    // 신청 시점의 benefits가 있으면 우선 사용, 없으면 현재 테이블 값 사용 (fallback)
    if ($application['product_type'] === 'mvno') {
        if (!isset($additionalInfo['product_snapshot'])) {
            $additionalInfo['product_snapshot'] = [];
        }
        // product_snapshot에 이미 benefits가 있으면 유지 (신청 시점 정보)
        // 없으면 테이블에서 가져온 값 사용 (fallback)
        if (!isset($additionalInfo['product_snapshot']['benefits']) && !empty($application['mvno_benefits'])) {
            $additionalInfo['product_snapshot']['benefits'] = $application['mvno_benefits'];
        }
    }
    
    // 상태 한글 변환
    $statusKor = getApplicationStatusLabel($application['application_status']);
    
    // 응답 데이터 구성
    $responseData = [
        'application_id' => $application['application_id'],
        'order_number' => $application['order_number'] ?? '',
        'product_type' => $application['product_type'] ?? '',
        'status' => $statusKor,
        'status_changed_at' => !empty($application['status_changed_at']) ? date('Y.m.d H:i', strtotime($application['status_changed_at'])) : '',
        'customer' => [
            'name' => $application['name'] ?? '',
            'phone' => $application['phone'] ?? '',
            'email' => $application['email'] ?? '',
            'address' => $application['address'] ?? '',
            'address_detail' => $application['address_detail'] ?? '',
            'birth_date' => $application['birth_date'] ?? '',
            'gender' => $application['gender'] ?? ''
        ],
        'additional_info' => $additionalInfo
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching application details: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '정보를 불러오는 중 오류가 발생했습니다.'
    ]);
}












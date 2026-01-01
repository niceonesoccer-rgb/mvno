<?php
/**
 * MNO 상품 고객 신청 처리 API
 * 
 * 고객이 신청정보를 제출하면:
 * 1. 판매자에게 신청정보 저장
 * 2. redirect_url이 있으면 해당 URL로 리다이렉트
 * 3. redirect_url이 없으면 창 닫기 응답
 */

require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/product-functions.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/contract-type-functions.php';

header('Content-Type: application/json; charset=utf-8');

// 로그인 체크 (비회원 주문 불가)
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다. 회원가입 후 주문 신청이 가능합니다.'
    ]);
    exit;
}

// POST 데이터 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'POST 요청만 허용됩니다.'
    ]);
    exit;
}

// 필수 필드 확인
$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if (empty($productId) || empty($name) || empty($phone)) {
    echo json_encode([
        'success' => false,
        'message' => '필수 정보가 누락되었습니다.'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    // 상품 정보 전체 가져오기 (신청 시점의 상품 정보 전체를 저장하기 위해)
    $stmt = $pdo->prepare("
        SELECT p.seller_id, m.*
        FROM products p
        LEFT JOIN product_mno_details m ON p.id = m.product_id
        WHERE p.id = ? AND p.product_type = 'mno' AND p.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('상품을 찾을 수 없습니다.');
    }
    
    $sellerId = $product['seller_id'];
    $redirectUrl = !empty($product['redirect_url']) ? trim($product['redirect_url']) : null;
    
    // 주문 시점 판매자 정보 가져오기 (스냅샷 저장용)
    $sellerSnapshot = null;
    if ($sellerId) {
        require_once __DIR__ . '/../includes/data/plan-data.php';
        $sellerSnapshot = getSellerById($sellerId);
        if ($sellerSnapshot) {
            // 민감한 정보 제외하고 필요한 정보만 저장
            $sellerSnapshot = [
                'user_id' => $sellerSnapshot['user_id'] ?? null,
                'seller_name' => $sellerSnapshot['seller_name'] ?? null,
                'company_name' => $sellerSnapshot['company_name'] ?? null,
                'name' => $sellerSnapshot['name'] ?? null,
                'phone' => $sellerSnapshot['phone'] ?? null,
                'mobile' => $sellerSnapshot['mobile'] ?? null,
                'chat_consultation_url' => $sellerSnapshot['chat_consultation_url'] ?? null
            ];
        }
    }
    
    // 로그인한 사용자 정보 가져오기 (이미 로그인 체크 완료)
    $currentUser = getCurrentUser();
    $userId = $currentUser['user_id'] ?? null;
    
    if (!$userId) {
        throw new Exception('로그인 정보를 확인할 수 없습니다.');
    }
    
    // 상품 정보 전체를 배열로 구성 (product_id, id 제외)
    $productSnapshot = [];
    foreach ($product as $key => $value) {
        if ($key !== 'seller_id' && $key !== 'product_id' && $key !== 'id') {
            $productSnapshot[$key] = $value;
        }
    }
    
    // 추가 정보 수집
    $additionalInfo = [];
    
    // 신청 당시 상품 정보 전체 저장 (클레임 처리용)
    $additionalInfo['product_snapshot'] = $productSnapshot;
    
    // 신청 당시 판매자 정보 저장 (탈퇴 후에도 정보 보존)
    $additionalInfo['seller_snapshot'] = $sellerSnapshot;
    
    // 할인 정보 (고객이 선택한 정보)
    if (isset($_POST['selected_provider'])) {
        $additionalInfo['carrier'] = trim($_POST['selected_provider']);
    }
    if (isset($_POST['selected_discount_type'])) {
        $additionalInfo['discount_type'] = trim($_POST['selected_discount_type']);
    }
    if (isset($_POST['selected_subscription_type'])) {
        $additionalInfo['subscription_type'] = normalizeContractType(trim($_POST['selected_subscription_type'])); // 영문 코드로 정규화
    }
    if (isset($_POST['selected_amount'])) {
        $amount = trim($_POST['selected_amount']);
        // '0'도 유효한 값이므로 그대로 저장
        $additionalInfo['price'] = $amount;
    }
    
    // 단말기 색상 정보 (1개만 선택 가능)
    if (isset($_POST['device_color']) && is_array($_POST['device_color'])) {
        $selectedColors = array_filter(array_map('trim', $_POST['device_color']));
        if (!empty($selectedColors)) {
            // 첫 번째 색상만 저장 (1개만 선택)
            $additionalInfo['device_colors'] = [reset($selectedColors)];
        }
    }
    
    // 고객 정보 준비
    $customerData = [
        'user_id' => $userId, // 로그인한 사용자 ID
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'address' => null,
        'address_detail' => null,
        'birth_date' => null,
        'gender' => null,
        'additional_info' => $additionalInfo
    ];
    
    // 신청정보 저장
    $applicationId = addProductApplication($productId, $sellerId, 'mno', $customerData);
    
    if ($applicationId === false) {
        throw new Exception('신청정보 저장에 실패했습니다.');
    }
    
    // 알림 설정 저장 (서비스 이용 및 혜택 안내 알림, 광고성 정보 수신동의)
    $serviceNoticeOptIn = isset($_POST['service_notice_opt_in']) ? (bool)$_POST['service_notice_opt_in'] : false;
    $serviceNoticePlanOptIn = isset($_POST['service_notice_plan_opt_in']) ? (bool)$_POST['service_notice_plan_opt_in'] : false;
    $serviceNoticeServiceOptIn = isset($_POST['service_notice_service_opt_in']) ? (bool)$_POST['service_notice_service_opt_in'] : false;
    $serviceNoticeBenefitOptIn = isset($_POST['service_notice_benefit_opt_in']) ? (bool)$_POST['service_notice_benefit_opt_in'] : false;
    
    $marketingOptIn = isset($_POST['marketing_opt_in']) ? (bool)$_POST['marketing_opt_in'] : false;
    $marketingEmailOptIn = isset($_POST['marketing_email_opt_in']) ? (bool)$_POST['marketing_email_opt_in'] : false;
    $marketingSmsSnsOptIn = isset($_POST['marketing_sms_sns_opt_in']) ? (bool)$_POST['marketing_sms_sns_opt_in'] : false;
    $marketingPushOptIn = isset($_POST['marketing_push_opt_in']) ? (bool)$_POST['marketing_push_opt_in'] : false;
    
    // 서비스 이용 및 혜택 안내 알림이 체크 해제되면 하위 항목들도 모두 false로 설정
    if (!$serviceNoticeOptIn) {
        $serviceNoticePlanOptIn = false;
        $serviceNoticeServiceOptIn = false;
        $serviceNoticeBenefitOptIn = false;
    }
    
    // 마케팅 전체 동의가 체크되어 있으면 모든 하위 체크박스도 자동으로 true로 설정
    if ($marketingOptIn) {
        $marketingEmailOptIn = true;
        $marketingSmsSnsOptIn = true;
        $marketingPushOptIn = true;
        error_log("MNO Application Debug - marketing_opt_in이 true이므로 모든 하위 체크박스를 true로 설정");
    } else {
        // 마케팅 동의가 있으면 마케팅 전체 동의도 true로 설정
        if ($marketingEmailOptIn || $marketingSmsSnsOptIn || $marketingPushOptIn) {
            $marketingOptIn = true;
            error_log("MNO Application Debug - 하위 체크박스가 체크되어 있으므로 marketing_opt_in을 true로 설정");
        }
        
        // 마케팅 동의가 없으면 모든 채널을 false로 설정
        if (!$marketingOptIn) {
            $marketingEmailOptIn = false;
            $marketingSmsSnsOptIn = false;
            $marketingPushOptIn = false;
        }
    }
    
    error_log("MNO Application Debug - 최종 마케팅 설정:");
    error_log("  marketing_opt_in: " . ($marketingOptIn ? 'true' : 'false'));
    error_log("  marketing_email_opt_in: " . ($marketingEmailOptIn ? 'true' : 'false'));
    error_log("  marketing_sms_sns_opt_in: " . ($marketingSmsSnsOptIn ? 'true' : 'false'));
    error_log("  marketing_push_opt_in: " . ($marketingPushOptIn ? 'true' : 'false'));
    
    try {
        // 데이터베이스에 필드가 있는지 확인하여 동적으로 쿼리 생성
        $checkColumnsStmt = $pdo->prepare("
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'users' 
            AND COLUMN_NAME IN ('service_notice_plan_opt_in', 'service_notice_service_opt_in', 'service_notice_benefit_opt_in')
        ");
        $checkColumnsStmt->execute();
        $existingColumns = $checkColumnsStmt->fetchAll(PDO::FETCH_COLUMN);
        $hasDetailFields = in_array('service_notice_plan_opt_in', $existingColumns);
        
        if ($hasDetailFields) {
            // 하위 항목 필드가 있는 경우
            $alarmStmt = $pdo->prepare("
                UPDATE users
                SET service_notice_opt_in = :service_notice_opt_in,
                    service_notice_plan_opt_in = :service_notice_plan_opt_in,
                    service_notice_service_opt_in = :service_notice_service_opt_in,
                    service_notice_benefit_opt_in = :service_notice_benefit_opt_in,
                    marketing_opt_in = :marketing_opt_in,
                    marketing_email_opt_in = :marketing_email_opt_in,
                    marketing_sms_sns_opt_in = :marketing_sms_sns_opt_in,
                    marketing_push_opt_in = :marketing_push_opt_in,
                    alarm_settings_updated_at = NOW()
                WHERE user_id = :user_id
            ");
            
            $alarmStmt->execute([
                ':service_notice_opt_in' => $serviceNoticeOptIn ? 1 : 0,
                ':service_notice_plan_opt_in' => $serviceNoticePlanOptIn ? 1 : 0,
                ':service_notice_service_opt_in' => $serviceNoticeServiceOptIn ? 1 : 0,
                ':service_notice_benefit_opt_in' => $serviceNoticeBenefitOptIn ? 1 : 0,
                ':marketing_opt_in' => $marketingOptIn ? 1 : 0,
                ':marketing_email_opt_in' => $marketingEmailOptIn ? 1 : 0,
                ':marketing_sms_sns_opt_in' => $marketingSmsSnsOptIn ? 1 : 0,
                ':marketing_push_opt_in' => $marketingPushOptIn ? 1 : 0,
                ':user_id' => $userId
            ]);
        } else {
            // 하위 항목 필드가 없는 경우 (기존 방식)
            $alarmStmt = $pdo->prepare("
                UPDATE users
                SET service_notice_opt_in = :service_notice_opt_in,
                    marketing_opt_in = :marketing_opt_in,
                    marketing_email_opt_in = :marketing_email_opt_in,
                    marketing_sms_sns_opt_in = :marketing_sms_sns_opt_in,
                    marketing_push_opt_in = :marketing_push_opt_in,
                    alarm_settings_updated_at = NOW()
                WHERE user_id = :user_id
            ");
            
            $alarmStmt->execute([
                ':service_notice_opt_in' => $serviceNoticeOptIn ? 1 : 0,
                ':marketing_opt_in' => $marketingOptIn ? 1 : 0,
                ':marketing_email_opt_in' => $marketingEmailOptIn ? 1 : 0,
                ':marketing_sms_sns_opt_in' => $marketingSmsSnsOptIn ? 1 : 0,
                ':marketing_push_opt_in' => $marketingPushOptIn ? 1 : 0,
                ':user_id' => $userId
            ]);
        }
    } catch (PDOException $e) {
        // 알림 설정 저장 실패는 로그만 남기고 계속 진행 (신청은 성공으로 처리)
        error_log("MNO Application - Failed to save alarm settings: " . $e->getMessage());
    }
    
    // 응답 반환
    echo json_encode([
        'success' => true,
        'message' => '신청이 완료되었습니다.',
        'application_id' => $applicationId,
        'redirect_url' => $redirectUrl
    ]);
    
} catch (Exception $e) {
    error_log("MNO Application Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}










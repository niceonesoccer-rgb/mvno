<?php
/**
 * MVNO 상품 고객 신청 처리 API
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
require_once __DIR__ . '/../includes/data/point-settings.php';

header('Content-Type: application/json; charset=utf-8');

// 로그인 체크 (비회원 주문 불가)
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다. 회원가입 후 주문 신청이 가능합니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 판매자/관리자 신청 차단 (일반회원만 신청 가능)
$currentUser = getCurrentUser();
if (!$currentUser) {
    echo json_encode([
        'success' => false,
        'message' => '로그인 정보를 확인할 수 없습니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (isSeller() || isAdmin()) {
    $userRole = getUserRole();
    $roleName = $userRole === 'seller' ? '판매자' : '관리자';
    echo json_encode([
        'success' => false,
        'message' => $roleName . '는 신청할 수 없습니다. 일반회원만 신청 가능합니다.'
    ], JSON_UNESCAPED_UNICODE);
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
$subscriptionType = isset($_POST['subscription_type']) ? trim($_POST['subscription_type']) : '';

// 디버깅: 받은 데이터 로그
error_log("MVNO Application Submit - Received data:");
error_log("  product_id: " . $productId);
error_log("  name: " . $name);
error_log("  phone: " . $phone);
error_log("  email: " . $email);
error_log("  subscription_type: " . $subscriptionType);

if (empty($productId) || empty($name) || empty($phone)) {
    $missing = [];
    if (empty($productId)) $missing[] = 'product_id';
    if (empty($name)) $missing[] = 'name';
    if (empty($phone)) $missing[] = 'phone';
    
    echo json_encode([
        'success' => false,
        'message' => '필수 정보가 누락되었습니다.',
        'missing_fields' => $missing
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    // 상품 정보 전체 가져오기 (신청 시점의 상품 정보 전체를 저장하기 위해)
    error_log("MVNO Application - Fetching product info for product_id: " . $productId);
    $stmt = $pdo->prepare("
        SELECT p.seller_id, mvno.*
        FROM products p
        LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id
        WHERE p.id = ? AND p.product_type = 'mvno' AND p.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        // 상품이 없는 이유 확인
        $stmt = $pdo->prepare("SELECT id, product_type, status FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $productCheck = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$productCheck) {
            throw new Exception('상품 ID ' . $productId . '를 찾을 수 없습니다.');
        } else {
            $reason = [];
            if ($productCheck['product_type'] !== 'mvno') {
                $reason[] = '상품 타입이 mvno가 아닙니다 (' . $productCheck['product_type'] . ')';
            }
            if ($productCheck['status'] !== 'active') {
                $reason[] = '상품 상태가 active가 아닙니다 (' . $productCheck['status'] . ')';
            }
            throw new Exception('상품을 찾을 수 없습니다. ' . implode(', ', $reason));
        }
    }
    
    error_log("MVNO Application - Product found: seller_id=" . ($product['seller_id'] ?? 'null'));
    
    $sellerId = $product['seller_id'];
    $redirectUrl = !empty($product['redirect_url']) ? preg_replace('/\s+/', '', trim($product['redirect_url'])) : null;
    
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
    
    error_log("MVNO Application - Current user: " . ($userId ?? 'null'));
    
    if (!$userId) {
        error_log("MVNO Application - User ID is null. Current user data: " . json_encode($currentUser, JSON_UNESCAPED_UNICODE));
        throw new Exception('로그인 정보를 확인할 수 없습니다.');
    }
    
    // 상품 정보 전체를 배열로 구성 (product_id, id 제외)
    $productSnapshot = [];
    foreach ($product as $key => $value) {
        if ($key !== 'seller_id' && $key !== 'product_id' && $key !== 'id') {
            $productSnapshot[$key] = $value;
        }
    }
    
    // product_snapshot이 비어있으면 에러 (상품 정보가 없음)
    if (empty($productSnapshot)) {
        throw new Exception('상품 정보를 가져올 수 없습니다. 상품이 삭제되었거나 정보가 없습니다.');
    }
    
    // 운영 안전: 신청 시점 상품 스냅샷 상세 로그 제거
    
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
        'additional_info' => [
            'subscription_type' => normalizeContractType($subscriptionType), // 가입 형태 저장 (영문 코드로 정규화)
            'product_snapshot' => $productSnapshot, // 신청 당시 상품 정보 전체 저장 (클레임 처리용)
            'seller_snapshot' => $sellerSnapshot // 신청 당시 판매자 정보 저장 (탈퇴 후에도 정보 보존)
        ]
    ];
    
    // 신청정보 저장
    error_log("MVNO Application - Calling addProductApplication with:");
    error_log("  productId: " . $productId);
    error_log("  sellerId: " . $sellerId);
    error_log("  productType: mvno");
    error_log("  customerData keys: " . implode(', ', array_keys($customerData)));
    
    $applicationId = addProductApplication($productId, $sellerId, 'mvno', $customerData);
    
    error_log("MVNO Application - addProductApplication returned: " . ($applicationId === false ? 'false' : $applicationId));
    
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
        error_log("MVNO Application Debug - marketing_opt_in이 true이므로 모든 하위 체크박스를 true로 설정");
    } else {
        // 마케팅 동의가 있으면 마케팅 전체 동의도 true로 설정
        if ($marketingEmailOptIn || $marketingSmsSnsOptIn || $marketingPushOptIn) {
            $marketingOptIn = true;
            error_log("MVNO Application Debug - 하위 체크박스가 체크되어 있으므로 marketing_opt_in을 true로 설정");
        }
        
        // 마케팅 동의가 없으면 모든 채널을 false로 설정
        if (!$marketingOptIn) {
            $marketingEmailOptIn = false;
            $marketingSmsSnsOptIn = false;
            $marketingPushOptIn = false;
        }
    }
    
    error_log("MVNO Application Debug - 최종 마케팅 설정:");
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
        error_log("MVNO Application - Failed to save alarm settings: " . $e->getMessage());
    }
    
    if ($applicationId === false) {
        global $lastDbError, $lastDbConnectionError;
        $errorMsg = '신청정보 저장에 실패했습니다.';
        $errorDetails = [];
        
        if (isset($lastDbError)) {
            error_log("Database error details: " . $lastDbError);
            $errorMsg .= ' (' . $lastDbError . ')';
            $errorDetails['last_db_error'] = $lastDbError;
        } else {
            error_log("No lastDbError set, but addProductApplication returned false");
        }
        
        if (isset($lastDbConnectionError)) {
            $errorDetails['last_db_connection_error'] = $lastDbConnectionError;
        }
        
        $exception = new Exception($errorMsg);
        $exception->errorDetails = $errorDetails;
        throw $exception;
    }
    
    // 알뜰폰 신청 포인트 지급
    if (function_exists('getPointSetting') && function_exists('addPoint')) {
        $mvnoPointEnabled = getPointSetting('point_application_mvno_enabled', 0);
        if ($mvnoPointEnabled) {
            $mvnoPointAmount = getPointSetting('point_application_mvno_amount', 0);
            if ($mvnoPointAmount > 0) {
                $pointResult = addPoint($userId, $mvnoPointAmount, '알뜰폰 신청 포인트', $applicationId);
                if ($pointResult['success']) {
                    error_log("MVNO Application - 포인트 지급 완료: {$mvnoPointAmount}원 (잔액: {$pointResult['balance']}원)");
                } else {
                    error_log("MVNO Application - 포인트 지급 실패: " . ($pointResult['message'] ?? '알 수 없는 오류'));
                }
            }
        }
    }
    
    // 응답 반환
    echo json_encode([
        'success' => true,
        'message' => '신청이 완료되었습니다.',
        'application_id' => $applicationId,
        'redirect_url' => $redirectUrl
    ]);
    
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    error_log("MVNO Application Error: " . $errorMessage);
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // 개발 환경에서는 더 자세한 정보 제공
    $response = [
        'success' => false,
        'message' => $errorMessage
    ];
    
    // 로컬 환경에서는 항상 디버그 정보 포함
    $isLocal = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
                strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false);
    
    global $lastDbError, $lastDbConnectionError;
    
    // 항상 디버그 정보 포함 (로컬이 아니어도 개발 중이므로)
    $response['debug'] = [
        'error_message' => $errorMessage,
        'last_db_error' => $lastDbError ?? null,
        'last_db_connection_error' => $lastDbConnectionError ?? null,
        'product_id' => $productId ?? null,
        'user_id' => $userId ?? null,
        'seller_id' => $sellerId ?? null,
        'exception_class' => get_class($e),
        'stack_trace' => $isLocal ? $e->getTraceAsString() : null
    ];
    
    // Exception에 errorDetails가 있으면 추가
    if (isset($e->errorDetails) && is_array($e->errorDetails)) {
        $response['debug'] = array_merge($response['debug'], $e->errorDetails);
    }
    
    // 데이터베이스 관련 에러인 경우 추가 정보
    if (strpos($errorMessage, '데이터베이스') !== false || strpos($errorMessage, '테이블') !== false || strpos($errorMessage, 'Column') !== false) {
        $response['debug_info'] = [
            'db_host' => DB_HOST,
            'db_name' => DB_NAME,
            'db_user' => DB_USER,
            'suggestion' => '데이터베이스 연결 및 테이블을 확인하세요: /MVNO/api/test-db-connection.php'
        ];
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}










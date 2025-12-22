<?php
/**
 * Internet 상품 고객 신청 처리 API
 * 
 * 고객이 신청정보를 제출하면:
 * 1. 판매자에게 신청정보 저장
 * 2. redirect_url이 있으면 해당 URL로 리다이렉트
 * 3. redirect_url이 없으면 창 닫기 응답
 */

require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/product-functions.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

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

// 디버깅: 받은 데이터 로깅
error_log("Internet Application Debug - Received POST data:");
error_log("  product_id: " . var_export($productId, true));
error_log("  name: " . var_export($name, true));
error_log("  phone: " . var_export($phone, true));
error_log("  email: " . var_export($email, true));
error_log("  currentCompany: " . var_export($_POST['currentCompany'] ?? 'not set', true));
error_log("  All POST keys: " . implode(', ', array_keys($_POST)));

if (empty($productId) || empty($name) || empty($phone)) {
    $missingFields = [];
    if (empty($productId)) $missingFields[] = 'product_id';
    if (empty($name)) $missingFields[] = 'name';
    if (empty($phone)) $missingFields[] = 'phone';
    
    error_log("Internet Application Validation Failed - Missing fields: " . implode(', ', $missingFields));
    
    echo json_encode([
        'success' => false,
        'message' => '필수 정보가 누락되었습니다: ' . implode(', ', $missingFields)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    error_log("Internet Application Debug - Step 1: Starting database connection");
    
    $pdo = getDBConnection();
    if (!$pdo) {
        error_log("Internet Application Debug - Database connection failed");
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    error_log("Internet Application Debug - Step 2: Database connected, querying product ID: {$productId}");
    
    // 상품 정보 전체 가져오기 (신청 시점의 상품 정보 전체를 저장하기 위해)
    $stmt = $pdo->prepare("
        SELECT p.seller_id, inet.*
        FROM products p
        LEFT JOIN product_internet_details inet ON p.id = inet.product_id
        WHERE p.id = ? AND p.product_type = 'internet' AND p.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        error_log("Internet Application Debug - Product not found for ID: {$productId}");
        // 더 자세한 정보를 위해 상품 존재 여부 확인
        $checkStmt = $pdo->prepare("SELECT id, product_type, status FROM products WHERE id = ?");
        $checkStmt->execute([$productId]);
        $checkProduct = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if ($checkProduct) {
            error_log("Internet Application Debug - Product exists but: type={$checkProduct['product_type']}, status={$checkProduct['status']}");
        } else {
            error_log("Internet Application Debug - Product ID {$productId} does not exist");
        }
        throw new Exception('상품을 찾을 수 없습니다.');
    }
    
    $sellerId = $product['seller_id'];
    error_log("Internet Application Debug - Step 3: Product found, seller_id: {$sellerId}");
    
    // 로그인한 사용자 정보 가져오기 (이미 로그인 체크 완료)
    $currentUser = getCurrentUser();
    $userId = $currentUser['user_id'] ?? null;
    
    error_log("Internet Application Debug - Step 4: Current user - user_id: " . var_export($userId, true));
    
    if (!$userId) {
        error_log("Internet Application Debug - User ID is null or empty");
        error_log("Internet Application Debug - Current user data: " . json_encode($currentUser ?? 'null'));
        throw new Exception('로그인 정보를 확인할 수 없습니다.');
    }
    
    // 상품 정보 전체를 배열로 구성 (product_id, id 제외)
    $productSnapshot = [];
    foreach ($product as $key => $value) {
        if ($key !== 'seller_id' && $key !== 'product_id' && $key !== 'id') {
            $productSnapshot[$key] = $value;
        }
    }
    
    // 기존 인터넷 회선 정보 가져오기
    $currentCompany = isset($_POST['currentCompany']) ? trim($_POST['currentCompany']) : '';
    
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
            'product_snapshot' => $productSnapshot, // 신청 당시 상품 정보 전체 저장 (클레임 처리용)
            'currentCompany' => $currentCompany, // 기존 인터넷 회선 정보
            'existing_company' => $currentCompany, // 호환성을 위한 별칭
            'existingCompany' => $currentCompany // 호환성을 위한 별칭
        ]
    ];
    
    // 신청정보 저장
    error_log("Internet Application Debug - Step 5: Calling addProductApplication");
    error_log("Internet Application Debug - Parameters:");
    error_log("  productId: {$productId}");
    error_log("  sellerId: {$sellerId}");
    error_log("  productType: internet");
    error_log("  customerData keys: " . implode(', ', array_keys($customerData)));
    error_log("  customerData name: " . ($customerData['name'] ?? 'not set'));
    error_log("  customerData phone: " . ($customerData['phone'] ?? 'not set'));
    error_log("  customerData user_id: " . ($customerData['user_id'] ?? 'not set'));
    
    $applicationId = addProductApplication($productId, $sellerId, 'internet', $customerData);
    
    error_log("Internet Application Debug - Step 6: addProductApplication returned: " . var_export($applicationId, true));
    
    if ($applicationId === false) {
        // 더 자세한 에러 로깅
        error_log("Internet Application Save Failed - Product ID: {$productId}, Seller ID: {$sellerId}, User ID: {$userId}");
        error_log("Internet Application Save Failed - Customer Data: " . json_encode($customerData, JSON_UNESCAPED_UNICODE));
        
        // 전역 에러 변수 확인
        global $lastDbError;
        $errorMessage = '신청정보 저장에 실패했습니다. 잠시 후 다시 시도해주세요.';
        
        if (isset($lastDbError)) {
            error_log("Internet Application Save Failed - Last DB Error: " . $lastDbError);
            // 개발 환경에서는 더 자세한 에러 메시지 제공
            if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
                strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false) {
                $errorMessage .= ' (DB Error: ' . htmlspecialchars($lastDbError) . ')';
            }
        }
        
        throw new Exception($errorMessage);
    }
    
    error_log("Internet Application Debug - Step 7: Success! Application ID: {$applicationId}");
    
    // 알림 설정 저장 (서비스 이용 및 혜택 안내 알림, 광고성 정보 수신동의)
    $serviceNoticeOptIn = isset($_POST['service_notice_opt_in']) ? (bool)$_POST['service_notice_opt_in'] : false;
    $marketingOptIn = isset($_POST['marketing_opt_in']) ? (bool)$_POST['marketing_opt_in'] : false;
    $marketingEmailOptIn = isset($_POST['marketing_email_opt_in']) ? (bool)$_POST['marketing_email_opt_in'] : false;
    $marketingSmsSnsOptIn = isset($_POST['marketing_sms_sns_opt_in']) ? (bool)$_POST['marketing_sms_sns_opt_in'] : false;
    $marketingPushOptIn = isset($_POST['marketing_push_opt_in']) ? (bool)$_POST['marketing_push_opt_in'] : false;
    
    // 마케팅 동의가 있으면 마케팅 전체 동의도 true로 설정
    if ($marketingEmailOptIn || $marketingSmsSnsOptIn || $marketingPushOptIn) {
        $marketingOptIn = true;
    }
    
    // 마케팅 동의가 없으면 모든 채널을 false로 설정
    if (!$marketingOptIn) {
        $marketingEmailOptIn = false;
        $marketingSmsSnsOptIn = false;
        $marketingPushOptIn = false;
    }
    
    try {
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
        
        error_log("Internet Application Debug - Alarm settings updated successfully");
    } catch (PDOException $e) {
        error_log("Internet Application Debug - Alarm settings update failed: " . $e->getMessage());
        // 알림 설정 저장 실패해도 신청은 성공으로 처리
    }
    
    // 응답 반환
    echo json_encode([
        'success' => true,
        'message' => '신청이 완료되었습니다.',
        'application_id' => $applicationId
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("Internet Application PDO Error: " . $e->getMessage());
    error_log("PDO Error Code: " . $e->getCode());
    error_log("PDO SQL State: " . ($e->errorInfo[0] ?? 'unknown'));
    error_log("PDO Driver Error Code: " . ($e->errorInfo[1] ?? 'unknown'));
    error_log("PDO Driver Error Message: " . ($e->errorInfo[2] ?? 'unknown'));
    
    $errorMessage = '데이터베이스 오류가 발생했습니다. 잠시 후 다시 시도해주세요.';
    // 개발 환경에서는 더 자세한 에러 메시지 제공
    if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
        strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false) {
        $errorMessage .= ' (Error: ' . htmlspecialchars($e->getMessage()) . ')';
    }
    
    echo json_encode([
        'success' => false,
        'message' => $errorMessage,
        'debug' => (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false) ? [
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
            'sql_state' => $e->errorInfo[0] ?? null,
            'driver_code' => $e->errorInfo[1] ?? null
        ] : null
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Internet Application Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false) ? [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ] : null
    ], JSON_UNESCAPED_UNICODE);
}


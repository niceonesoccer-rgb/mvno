<?php
/**
 * 통신사유심 상품 고객 신청 처리 API
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
error_log("MNO-SIM Application Submit - Received data:");
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
    
    // product_applications 테이블의 product_type ENUM에 'mno-sim' 추가 확인
    try {
        // 트랜잭션 밖에서 실행 (ALTER TABLE은 트랜잭션을 커밋함)
        $wasInTransaction = $pdo->inTransaction();
        if ($wasInTransaction) {
            $pdo->commit();
        }
        
        $checkEnum = $pdo->query("SHOW COLUMNS FROM product_applications WHERE Field = 'product_type'");
        $enumInfo = $checkEnum->fetch(PDO::FETCH_ASSOC);
        if ($enumInfo && isset($enumInfo['Type'])) {
            $enumType = $enumInfo['Type'];
            if (strpos($enumType, 'mno-sim') === false) {
                // ENUM에 'mno-sim' 추가
                $pdo->exec("ALTER TABLE product_applications MODIFY COLUMN product_type ENUM('mvno', 'mno', 'internet', 'mno-sim') NOT NULL COMMENT '상품 타입'");
                error_log("MNO-SIM Application - product_applications 테이블의 product_type ENUM에 'mno-sim' 추가 완료");
            }
        }
        
        // 트랜잭션 재시작
        if ($wasInTransaction) {
            $pdo->beginTransaction();
        }
    } catch (PDOException $e) {
        error_log("MNO-SIM Application - product_type ENUM 확인 중 오류: " . $e->getMessage());
        // 트랜잭션 재시작
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
        }
    }
    
    // 상품 정보 전체 가져오기 (신청 시점의 상품 정보 전체를 저장하기 위해)
    error_log("MNO-SIM Application - Fetching product info for product_id: " . $productId);
    $stmt = $pdo->prepare("
        SELECT p.seller_id, mno_sim.*
        FROM products p
        INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
        WHERE p.id = ? AND p.product_type = 'mno-sim' AND p.status = 'active'
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
            if ($productCheck['product_type'] !== 'mno-sim') {
                $reason[] = '상품 타입이 mno-sim이 아닙니다 (' . $productCheck['product_type'] . ')';
            }
            if ($productCheck['status'] !== 'active') {
                $reason[] = '상품 상태가 active가 아닙니다 (' . $productCheck['status'] . ')';
            }
            throw new Exception('상품을 찾을 수 없습니다. ' . implode(', ', $reason));
        }
    }
    
    error_log("MNO-SIM Application - Product found: seller_id=" . ($product['seller_id'] ?? 'null'));
    
    $sellerId = $product['seller_id'];
    $redirectUrl = !empty($product['redirect_url']) ? preg_replace('/\s+/', '', trim($product['redirect_url'])) : null;
    
    // 로그인한 사용자 정보 가져오기 (이미 로그인 체크 완료)
    $currentUser = getCurrentUser();
    $userId = $currentUser['user_id'] ?? null;
    
    error_log("MNO-SIM Application - Current user: " . ($userId ?? 'null'));
    
    if (!$userId) {
        error_log("MNO-SIM Application - User ID is null. Current user data: " . json_encode($currentUser, JSON_UNESCAPED_UNICODE));
        throw new Exception('로그인 정보를 확인할 수 없습니다.');
    }
    
    // 상품 정보 전체를 배열로 구성 (판매자가 저장한 정보 전체 저장)
    // mno-sim 관련 필드만 저장 (알뜰폰 데이터가 섞이지 않도록)
    $productSnapshot = [];
    
    // mno-sim 관련 필드 목록 (product_mno_sim_details 테이블의 필드)
    $mnoSimFields = [
        'provider', 'service_type', 'registration_types', 'plan_name',
        'contract_period', 'contract_period_discount_value', 'contract_period_discount_unit',
        'price_main', 'price_main_unit',
        'discount_period', 'discount_period_value', 'discount_period_unit',
        'price_after_type', 'price_after', 'price_after_unit',
        'plan_maintenance_period_type', 'plan_maintenance_period_prefix', 
        'plan_maintenance_period_value', 'plan_maintenance_period_unit',
        'sim_change_restriction_period_type', 'sim_change_restriction_period_prefix',
        'sim_change_restriction_period_value', 'sim_change_restriction_period_unit',
        'data_amount', 'data_amount_value', 'data_unit',
        'data_additional', 'data_additional_value',
        'data_exhausted', 'data_exhausted_value',
        'call_type', 'call_amount', 'call_amount_unit',
        'additional_call_type', 'additional_call', 'additional_call_unit',
        'sms_type', 'sms_amount', 'sms_amount_unit',
        'mobile_hotspot', 'mobile_hotspot_value', 'mobile_hotspot_unit',
        'regular_sim_available', 'regular_sim_price', 'regular_sim_price_unit',
        'nfc_sim_available', 'nfc_sim_price', 'nfc_sim_price_unit',
        'esim_available', 'esim_price', 'esim_price_unit',
        'over_data_price', 'over_data_price_unit',
        'over_voice_price', 'over_voice_price_unit',
        'over_video_price', 'over_video_price_unit',
        'over_sms_price', 'over_sms_price_unit',
        'over_lms_price', 'over_lms_price_unit',
        'over_mms_price', 'over_mms_price_unit',
        'promotion_title', 'promotions', 'benefits', 'redirect_url'
    ];
    
    // mno-sim 관련 필드만 저장
    foreach ($product as $key => $value) {
        // seller_id, product_id, id는 제외
        if ($key === 'seller_id' || $key === 'product_id' || $key === 'id') {
            continue;
        }
        
        // mno-sim 관련 필드만 포함
        if (in_array($key, $mnoSimFields)) {
            // plan_name은 문자열로만 저장 (다른 값이 섞이지 않도록)
            if ($key === 'plan_name') {
                $value = trim((string)$value);
                // 비정상적인 값 체크
                $invalidKeywords = ['URL', '없음', '세가지', '형태', '가입', '다음', '추가'];
                $isInvalid = false;
                foreach ($invalidKeywords as $keyword) {
                    if (stripos($value, $keyword) !== false) {
                        $isInvalid = true;
                        break;
                    }
                }
                // 비정상적인 경우 빈 문자열로 저장하지 않고 로그만 남김
                if ($isInvalid) {
                    error_log("MNO-SIM Application - Warning: Invalid plan_name detected: " . $value);
                    // 비정상적인 키워드 제거 시도
                    foreach ($invalidKeywords as $keyword) {
                        $value = str_ireplace($keyword, '', $value);
                    }
                    $value = trim($value);
                }
            }
            
            $productSnapshot[$key] = $value;
        }
    }
    
    // product_snapshot이 비어있으면 에러 (상품 정보가 없음)
    if (empty($productSnapshot)) {
        throw new Exception('상품 정보를 가져올 수 없습니다. 상품이 삭제되었거나 정보가 없습니다.');
    }
    
    // 고객 정보 준비 (구매회원이 신청한 정보)
    $customerData = [
        'user_id' => $userId, // 로그인한 사용자 ID
        'name' => $name, // 구매회원이 입력한 이름
        'phone' => $phone, // 구매회원이 입력한 전화번호
        'email' => $email, // 구매회원이 입력한 이메일
        'address' => null,
        'address_detail' => null,
        'birth_date' => null,
        'gender' => null,
        'additional_info' => [
            'subscription_type' => normalizeContractType($subscriptionType), // 구매회원이 선택한 가입 형태 (신규/번이/기변)
            'product_snapshot' => $productSnapshot // 신청 당시 판매자가 저장한 상품 정보 전체 저장 (분쟁 발생 시 확인용)
        ]
    ];
    
    // 로그: 저장되는 정보 확인
    error_log("MNO-SIM Application - 저장되는 정보:");
    error_log("  판매자 정보 (product_snapshot): " . count($productSnapshot) . "개 필드");
    error_log("  구매회원 정보: name=" . $name . ", phone=" . $phone . ", email=" . $email);
    error_log("  가입 형태: " . $subscriptionType);
    
    // 신청정보 저장
    error_log("MNO-SIM Application - Calling addProductApplication with:");
    error_log("  productId: " . $productId);
    error_log("  sellerId: " . $sellerId);
    error_log("  productType: mno-sim");
    error_log("  customerData keys: " . implode(', ', array_keys($customerData)));
    
    $applicationId = addProductApplication($productId, $sellerId, 'mno-sim', $customerData);
    
    error_log("MNO-SIM Application - addProductApplication returned: " . ($applicationId === false ? 'false' : $applicationId));
    
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
        error_log("MNO-SIM Application Debug - marketing_opt_in이 true이므로 모든 하위 체크박스를 true로 설정");
    } else {
        // 마케팅 동의가 있으면 마케팅 전체 동의도 true로 설정
        if ($marketingEmailOptIn || $marketingSmsSnsOptIn || $marketingPushOptIn) {
            $marketingOptIn = true;
            error_log("MNO-SIM Application Debug - 하위 체크박스가 체크되어 있으므로 marketing_opt_in을 true로 설정");
        }
        
        // 마케팅 동의가 없으면 모든 채널을 false로 설정
        if (!$marketingOptIn) {
            $marketingEmailOptIn = false;
            $marketingSmsSnsOptIn = false;
            $marketingPushOptIn = false;
        }
    }
    
    error_log("MNO-SIM Application Debug - 최종 마케팅 설정:");
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
        error_log("MNO-SIM Application - Failed to save alarm settings: " . $e->getMessage());
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
    
    // 응답 반환
    echo json_encode([
        'success' => true,
        'message' => '신청이 완료되었습니다.',
        'application_id' => $applicationId,
        'redirect_url' => $redirectUrl
    ]);
    
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    error_log("MNO-SIM Application Error: " . $errorMessage);
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


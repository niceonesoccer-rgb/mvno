<?php
/**
 * 알림 설정 업데이트 API
 */

// 에러 출력 방지 (JSON만 반환)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// JSON 헤더 먼저 설정
header('Content-Type: application/json; charset=utf-8');

// auth-functions.php에서 세션 설정과 함께 세션을 시작함
require_once __DIR__ . '/../includes/data/auth-functions.php';

// 로그인 확인
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다.'
    ]);
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다.'
    ]);
    exit;
}

// 일반 사용자만 사용 가능 (role이 'member', 'user', 또는 비어있는 경우)
$userRole = $currentUser['role'] ?? '';
$allowedRoles = ['member', 'user', ''];
if (!empty($userRole) && !in_array($userRole, $allowedRoles)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => '일반 회원만 사용할 수 있습니다.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => '잘못된 요청 방법입니다.'
    ]);
    exit;
}

// POST 데이터 읽기 (JSON 또는 form-data 모두 지원)
$input = file_get_contents('php://input');
$postData = json_decode($input, true);

// JSON 데이터가 없으면 일반 POST 데이터 사용
if (empty($postData)) {
    $postData = $_POST;
}

// 알림 설정 데이터 가져오기 (화면 문구에 맞춘 신규 필드명)
// - service_notice_opt_in: 서비스 이용 및 혜택 안내 알림(필수)
// - marketing_opt_in: 광고성 정보 수신동의(선택) 전체
// - marketing_email_opt_in / marketing_sms_sns_opt_in / marketing_push_opt_in: 채널 3개
//
// 하위 호환: 이전 키(benefit_notification / advertising_*)가 들어오면 신규 키로 매핑
$serviceNoticeOptIn = isset($postData['service_notice_opt_in'])
    ? (bool)$postData['service_notice_opt_in']
    : (isset($postData['benefit_notification']) ? (bool)$postData['benefit_notification'] : true);

$marketingOptIn = isset($postData['marketing_opt_in']) ? (bool)$postData['marketing_opt_in'] : false;

$marketingEmailOptIn = isset($postData['marketing_email_opt_in'])
    ? (bool)$postData['marketing_email_opt_in']
    : (isset($postData['advertising_email']) ? (bool)$postData['advertising_email'] : false);

$marketingSmsSnsOptIn = isset($postData['marketing_sms_sns_opt_in'])
    ? (bool)$postData['marketing_sms_sns_opt_in']
    : ((isset($postData['advertising_sms']) ? (bool)$postData['advertising_sms'] : false) ||
       (isset($postData['advertising_kakao']) ? (bool)$postData['advertising_kakao'] : false));

$marketingPushOptIn = isset($postData['marketing_push_opt_in'])
    ? (bool)$postData['marketing_push_opt_in']
    : (isset($postData['advertising_push']) ? (bool)$postData['advertising_push'] : false);

// 채널 중 하나라도 체크되면 marketing_opt_in을 true로 간주(프론트가 안 보내도 동작)
$hasAnyMarketingChannel = $marketingEmailOptIn || $marketingSmsSnsOptIn || $marketingPushOptIn;
if (!isset($postData['marketing_opt_in'])) {
    $marketingOptIn = $hasAnyMarketingChannel;
}

// 마케팅 동의 OFF면 채널은 전부 OFF로 강제
if (!$marketingOptIn) {
    $marketingEmailOptIn = false;
    $marketingSmsSnsOptIn = false;
    $marketingPushOptIn = false;
    $hasAnyMarketingChannel = false;
}

// DB 업데이트 (필수)
$pdo = getDBConnection();
$dbUpdated = false;
$errorMessage = '';

if (!$pdo) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'DB 연결에 실패했습니다. DB 설정을 확인해주세요.'
    ]);
    exit;
}

// user_id 확인
$userId = $currentUser['user_id'] ?? null;
if (empty($userId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '사용자 ID를 찾을 수 없습니다.'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE users
        SET service_notice_opt_in = :service_notice_opt_in,
            marketing_opt_in = :marketing_opt_in,
            marketing_email_opt_in = :marketing_email_opt_in,
            marketing_sms_sns_opt_in = :marketing_sms_sns_opt_in,
            marketing_push_opt_in = :marketing_push_opt_in,
            alarm_settings_updated_at = NOW()
        WHERE user_id = :user_id
    ");

    $executeResult = $stmt->execute([
        ':service_notice_opt_in' => $serviceNoticeOptIn ? 1 : 0,
        ':marketing_opt_in' => $marketingOptIn ? 1 : 0,
        ':marketing_email_opt_in' => $marketingEmailOptIn ? 1 : 0,
        ':marketing_sms_sns_opt_in' => $marketingSmsSnsOptIn ? 1 : 0,
        ':marketing_push_opt_in' => $marketingPushOptIn ? 1 : 0,
        ':user_id' => $userId
    ]);
    
    if ($executeResult) {
        $rowCount = $stmt->rowCount();
        if ($rowCount > 0) {
            $dbUpdated = true;
        } else {
            $errorMessage = '업데이트된 행이 없습니다. 사용자 ID를 확인해주세요.';
            error_log("Alarm settings update: No rows updated for user_id: " . $userId);
        }
    } else {
        $errorMessage = 'SQL 실행에 실패했습니다.';
        error_log("Alarm settings update: Execute failed for user_id: " . $userId);
    }
} catch (PDOException $e) {
    $errorMessage = $e->getMessage();
    error_log("Alarm settings DB update error: " . $errorMessage . " (user_id: " . $userId . ")");
    $dbUpdated = false;
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    error_log("Alarm settings update error: " . $errorMessage . " (user_id: " . $userId . ")");
    $dbUpdated = false;
}

if (!$dbUpdated) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $errorMessage ?: 'DB 저장에 실패했습니다. users 테이블에 알림 설정 컬럼을 추가했는지 확인해주세요. (database/add_alarm_settings_fields.sql 실행)'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => '알림 설정이 DB에 저장되었습니다.',
    'settings' => [
        'service_notice_opt_in' => $serviceNoticeOptIn,
        'marketing_opt_in' => $marketingOptIn,
        'marketing_email_opt_in' => $marketingEmailOptIn,
        'marketing_sms_sns_opt_in' => $marketingSmsSnsOptIn,
        'marketing_push_opt_in' => $marketingPushOptIn
    ]
]);
exit;





















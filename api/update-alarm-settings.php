<?php
/**
 * 알림 설정 업데이트 API
 */

// auth-functions.php에서 세션 설정과 함께 세션을 시작함
require_once __DIR__ . '/../includes/data/auth-functions.php';

header('Content-Type: application/json; charset=utf-8');

// 로그인 확인
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다.'
    ]);
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['role'] !== 'user') {
    echo json_encode([
        'success' => false,
        'message' => '일반 회원만 사용할 수 있습니다.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

if (!$pdo) {
    echo json_encode([
        'success' => false,
        'message' => 'DB 연결에 실패했습니다. DB 설정을 확인해주세요.'
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

    $dbUpdated = $stmt->execute([
        ':service_notice_opt_in' => $serviceNoticeOptIn ? 1 : 0,
        ':marketing_opt_in' => $marketingOptIn ? 1 : 0,
        ':marketing_email_opt_in' => $marketingEmailOptIn ? 1 : 0,
        ':marketing_sms_sns_opt_in' => $marketingSmsSnsOptIn ? 1 : 0,
        ':marketing_push_opt_in' => $marketingPushOptIn ? 1 : 0,
        ':user_id' => $currentUser['user_id']
    ]);
} catch (PDOException $e) {
    error_log("Alarm settings DB update error: " . $e->getMessage());
    $dbUpdated = false;
}

if (!$dbUpdated) {
    echo json_encode([
        'success' => false,
        'message' => 'DB 저장에 실패했습니다. users 테이블에 알림 설정 컬럼을 추가했는지 확인해주세요. (database/add_alarm_settings_fields.sql 실행)'
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













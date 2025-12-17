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

// 알림 설정 데이터 가져오기
$alarmSettings = [
    'benefit_notification' => isset($postData['benefit_notification']) ? (bool)$postData['benefit_notification'] : false,
    'advertising_sms' => isset($postData['advertising_sms']) ? (bool)$postData['advertising_sms'] : false,
    'advertising_phone' => isset($postData['advertising_phone']) ? (bool)$postData['advertising_phone'] : false,
    'advertising_email' => isset($postData['advertising_email']) ? (bool)$postData['advertising_email'] : false,
    'advertising_push' => isset($postData['advertising_push']) ? (bool)$postData['advertising_push'] : false,
    'advertising_kakao' => isset($postData['advertising_kakao']) ? (bool)$postData['advertising_kakao'] : false,
    'updated_at' => date('Y-m-d H:i:s')
];

// DB 업데이트 시도 (우선)
$pdo = getDBConnection();
$dbUpdated = false;

if ($pdo) {
    try {
        // alarm_settings 컬럼이 있는지 확인하고 업데이트
        $stmt = $pdo->prepare("
            UPDATE users 
            SET alarm_settings = :alarm_settings,
                alarm_settings_updated_at = NOW()
            WHERE user_id = :user_id
        ");
        
        $dbUpdated = $stmt->execute([
            ':alarm_settings' => json_encode($alarmSettings, JSON_UNESCAPED_UNICODE),
            ':user_id' => $currentUser['user_id']
        ]);
        
        if ($dbUpdated && $stmt->rowCount() === 0) {
            // 필드가 없을 수 있으므로 컬럼 존재 확인 후 재시도
            $dbUpdated = false;
        }
    } catch (PDOException $e) {
        // alarm_settings 컬럼이 없으면 JSON 파일로 폴백
        error_log("Alarm settings DB update error: " . $e->getMessage());
        $dbUpdated = false;
    }
}

// DB 업데이트 실패 시 JSON 파일 사용
if (!$dbUpdated) {
    $file = getUsersFilePath();
    if (!file_exists($file)) {
        echo json_encode([
            'success' => false,
            'message' => '사용자 데이터 파일을 찾을 수 없습니다.'
        ]);
        exit;
    }

    $content = file_get_contents($file);
    $data = json_decode($content, true);
    if (!is_array($data) || !isset($data['users'])) {
        echo json_encode([
            'success' => false,
            'message' => '데이터 형식이 올바르지 않습니다.'
        ]);
        exit;
    }

    // 사용자 알림 설정 업데이트
    $updated = false;
    foreach ($data['users'] as &$user) {
        if (isset($user['user_id']) && $user['user_id'] === $currentUser['user_id']) {
            // 알림 설정 초기화 (없는 경우)
            if (!isset($user['alarm_settings'])) {
                $user['alarm_settings'] = [];
            }
            
            // 알림 설정 업데이트
            $user['alarm_settings'] = array_merge($user['alarm_settings'], $alarmSettings);
            
            $updated = true;
            break;
        }
    }

    if (!$updated) {
        echo json_encode([
            'success' => false,
            'message' => '사용자를 찾을 수 없습니다.'
        ]);
        exit;
    }

    // 파일 저장
    if (!file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        echo json_encode([
            'success' => false,
            'message' => '알림 설정 업데이트에 실패했습니다.'
        ]);
        exit;
    }
}

echo json_encode([
    'success' => true,
    'message' => '알림 설정이 업데이트되었습니다.',
    'settings' => $alarmSettings
]);


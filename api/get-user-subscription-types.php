<?php
/**
 * 사용자 가입 형태 조회 API
 * 사용자가 이전에 가입한 형태(신규가입, 번호이동, 기기변경)를 확인
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: application/json; charset=utf-8');

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 로그인 확인
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다.',
        'subscription_types' => []
    ]);
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser) {
    echo json_encode([
        'success' => false,
        'message' => '사용자 정보를 찾을 수 없습니다.',
        'subscription_types' => []
    ]);
    exit;
}

$userId = $currentUser['user_id'] ?? $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode([
        'success' => false,
        'message' => '사용자 ID를 찾을 수 없습니다.',
        'subscription_types' => []
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    // 사용자의 이전 신청 내역에서 가입 형태 확인
    // product_applications와 application_customers를 조인하여 확인
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            a.product_type,
            c.additional_info
        FROM product_applications a
        INNER JOIN application_customers c ON a.id = c.application_id
        WHERE c.user_id = ? 
        AND a.product_type IN ('mvno', 'mno')
        ORDER BY a.created_at DESC
    ");
    
    // user_id가 문자열이거나 숫자일 수 있으므로 문자열로 변환
    $stmt->execute([(string)$userId]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $subscriptionTypes = [];
    
    // 기본적으로 모든 형태를 사용 가능하다고 가정
    // 이전 신청 내역이 있으면 그 정보를 사용
    $hasNewSubscription = false;
    $hasNumberPort = false;
    $hasDeviceChange = false;
    
    foreach ($applications as $app) {
        $additionalInfo = [];
        if (!empty($app['additional_info'])) {
            $additionalInfo = json_decode($app['additional_info'], true) ?: [];
        }
        
        // 가입 형태 정보 확인
        $subscriptionType = $additionalInfo['subscription_type'] ?? $additionalInfo['join_type'] ?? null;
        
        if ($subscriptionType === 'new' || $subscriptionType === '신규가입') {
            $hasNewSubscription = true;
        }
        if ($subscriptionType === 'mnp' || $subscriptionType === 'port' || $subscriptionType === '번호이동' || $subscriptionType === 'number_port') {
            $hasNumberPort = true;
        }
        if ($subscriptionType === 'change' || $subscriptionType === '기기변경' || $subscriptionType === 'device_change') {
            $hasDeviceChange = true;
        }
    }
    
    // 이전 신청 내역이 없으면 모든 형태 사용 가능 (신규 사용자)
    if (empty($applications)) {
        $hasNewSubscription = true;
        $hasNumberPort = true;
        $hasDeviceChange = true;
    }
    
    // 사용 가능한 가입 형태 추가
    if ($hasNewSubscription) {
        $subscriptionTypes[] = [
            'type' => 'new',
            'label' => '신규가입',
            'description' => '새로운 번호로 가입할래요'
        ];
    }
    
    if ($hasNumberPort) {
        $subscriptionTypes[] = [
            'type' => 'port',
            'label' => '번호이동',
            'description' => '지금 쓰는 번호 그대로 사용할래요'
        ];
    }
    
    if ($hasDeviceChange) {
        $subscriptionTypes[] = [
            'type' => 'change',
            'label' => '기기변경',
            'description' => '기기만 변경하고 번호는 유지할래요'
        ];
    }
    
    // 신청 내역이 없으면 모든 형태 표시 (기본값)
    if (empty($subscriptionTypes)) {
        $subscriptionTypes = [
            [
                'type' => 'new',
                'label' => '신규가입',
                'description' => '새로운 번호로 가입할래요'
            ],
            [
                'type' => 'mnp',
                'label' => '번호이동',
                'description' => '지금 쓰는 번호 그대로 사용할래요'
            ],
            [
                'type' => 'change',
                'label' => '기기변경',
                'description' => '기기만 변경하고 번호는 유지할래요'
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'subscription_types' => $subscriptionTypes
    ]);
    
} catch (Exception $e) {
    error_log("Get User Subscription Types Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'subscription_types' => []
    ]);
}























<?php
/**
 * SNS 로그인 처리 API
 */

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/data/auth-functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// API 설정 읽기
function getApiSettings() {
    $file = __DIR__ . '/../includes/data/api-settings.json';
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

// 네이버 로그인
if ($action === 'naver') {
    $settings = getApiSettings();
    $clientId = $settings['naver']['client_id'] ?? '';
    $redirectUri = $settings['naver']['redirect_uri'] ?? '';
    
    // API 설정이 없으면 테스트용으로 바로 로그인
    if (empty($clientId) || empty($redirectUri)) {
        $testUserId = 'nvr_3456789';
        
        // 사용자가 없으면 생성
        $user = getUserById($testUserId);
        if (!$user) {
            $usersFile = getUsersFilePath();
            $data = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : ['users' => []];
            
            $newUser = [
                'user_id' => $testUserId,
                'email' => 'test_naver@example.com',
                'name' => '네이버 테스트',
                'role' => 'user',
                'sns_provider' => 'naver',
                'sns_id' => '3456789',
                'created_at' => date('Y-m-d H:i:s'),
                'seller_approved' => false
            ];
            
            $data['users'][] = $newUser;
            file_put_contents($usersFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $user = $newUser;
        }
        
        // 로그인 처리
        loginUser($testUserId);
        
        echo json_encode(['success' => true, 'redirect' => '/MVNO/']);
        exit;
    }
    
    $state = bin2hex(random_bytes(16));
    $_SESSION['naver_state'] = $state;
    
    $naverAuthUrl = 'https://nid.naver.com/oauth2.0/authorize?' . http_build_query([
        'response_type' => 'code',
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'state' => $state
    ]);
    
    echo json_encode(['success' => true, 'auth_url' => $naverAuthUrl]);
    exit;
}

// 카카오 로그인
if ($action === 'kakao') {
    $settings = getApiSettings();
    $restApiKey = $settings['kakao']['rest_api_key'] ?? '';
    $redirectUri = $settings['kakao']['redirect_uri'] ?? '';
    
    // API 설정이 없으면 테스트용으로 바로 로그인
    if (empty($restApiKey) || empty($redirectUri)) {
        $testUserId = 'kko_4567890';
        
        // 사용자가 없으면 생성
        $user = getUserById($testUserId);
        if (!$user) {
            $usersFile = getUsersFilePath();
            $data = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : ['users' => []];
            
            $newUser = [
                'user_id' => $testUserId,
                'email' => 'test_kakao@example.com',
                'name' => '카카오 테스트',
                'role' => 'user',
                'sns_provider' => 'kakao',
                'sns_id' => '4567890',
                'created_at' => date('Y-m-d H:i:s'),
                'seller_approved' => false
            ];
            
            $data['users'][] = $newUser;
            file_put_contents($usersFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $user = $newUser;
        }
        
        // 로그인 처리
        loginUser($testUserId);
        
        echo json_encode(['success' => true, 'redirect' => '/MVNO/']);
        exit;
    }
    
    $kakaoAuthUrl = 'https://kauth.kakao.com/oauth/authorize?' . http_build_query([
        'client_id' => $restApiKey,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code'
    ]);
    
    echo json_encode(['success' => true, 'auth_url' => $kakaoAuthUrl]);
    exit;
}

// 구글 로그인
if ($action === 'google') {
    $settings = getApiSettings();
    $clientId = $settings['google']['client_id'] ?? '';
    $redirectUri = $settings['google']['redirect_uri'] ?? '';
    
    // API 설정이 없으면 테스트용으로 바로 로그인
    if (empty($clientId) || empty($redirectUri)) {
        $testUserId = 'gol_5678901';
        
        // 사용자가 없으면 생성
        $user = getUserById($testUserId);
        if (!$user) {
            $usersFile = getUsersFilePath();
            $data = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : ['users' => []];
            
            $newUser = [
                'user_id' => $testUserId,
                'email' => 'test_google@example.com',
                'name' => '구글 테스트',
                'role' => 'user',
                'sns_provider' => 'google',
                'sns_id' => '5678901',
                'created_at' => date('Y-m-d H:i:s'),
                'seller_approved' => false
            ];
            
            $data['users'][] = $newUser;
            file_put_contents($usersFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $user = $newUser;
        }
        
        // 로그인 처리
        loginUser($testUserId);
        
        echo json_encode(['success' => true, 'redirect' => '/MVNO/']);
        exit;
    }
    
    $state = bin2hex(random_bytes(16));
    $_SESSION['google_state'] = $state;
    
    $googleAuthUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $state
    ]);
    
    echo json_encode(['success' => true, 'auth_url' => $googleAuthUrl]);
    exit;
}

echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);


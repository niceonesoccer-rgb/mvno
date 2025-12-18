<?php
/**
 * SNS 로그인 처리 API
 */

// auth-functions.php에서 세션 설정과 함께 세션을 시작함
require_once __DIR__ . '/../includes/data/auth-functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// API 설정 읽기
function getApiSettings() {
    require_once __DIR__ . '/../includes/data/app-settings.php';
    return getAppSettings('api', []);
}

// 네이버 로그인
if ($action === 'naver') {
    $settings = getApiSettings();
    $clientId = $settings['naver']['client_id'] ?? '';
    $redirectUri = $settings['naver']['redirect_uri'] ?? '';
    
    // DB-only: API 설정이 없으면 로그인 불가
    if (empty($clientId) || empty($redirectUri)) {
        echo json_encode(['success' => false, 'message' => 'SNS 로그인 설정이 필요합니다. (관리자: API 설정 관리에서 등록)']);
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
    
    // DB-only: API 설정이 없으면 로그인 불가
    if (empty($restApiKey) || empty($redirectUri)) {
        echo json_encode(['success' => false, 'message' => 'SNS 로그인 설정이 필요합니다. (관리자: API 설정 관리에서 등록)']);
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
    
    // DB-only: API 설정이 없으면 로그인 불가
    if (empty($clientId) || empty($redirectUri)) {
        echo json_encode(['success' => false, 'message' => 'SNS 로그인 설정이 필요합니다. (관리자: API 설정 관리에서 등록)']);
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


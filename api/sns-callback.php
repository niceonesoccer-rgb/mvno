<?php
/**
 * SNS 로그인 콜백 처리
 */

// auth-functions.php에서 세션 설정과 함께 세션을 시작함
require_once __DIR__ . '/../includes/data/auth-functions.php';

// API 설정 읽기
function getApiSettings() {
    require_once __DIR__ . '/../includes/data/app-settings.php';
    return getAppSettings('api', []);
}

$provider = $_GET['provider'] ?? '';
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

if (empty($provider) || empty($code)) {
    header('Location: /MVNO/auth/login.php?error=invalid_request');
    exit;
}

$settings = getApiSettings();

// 네이버 콜백 처리
if ($provider === 'naver') {
    // State 검증
    if (!isset($_SESSION['naver_state']) || $_SESSION['naver_state'] !== $state) {
        header('Location: /MVNO/auth/login.php?error=invalid_state');
        exit;
    }
    unset($_SESSION['naver_state']);
    
    $clientId = $settings['naver']['client_id'] ?? '';
    $clientSecret = $settings['naver']['client_secret'] ?? '';
    $redirectUri = $settings['naver']['redirect_uri'] ?? '';
    
    // 액세스 토큰 요청
    $tokenUrl = 'https://nid.naver.com/oauth2.0/token';
    $tokenParams = [
        'grant_type' => 'authorization_code',
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'code' => $code,
        'state' => $state
    ];
    
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenParams));
    $tokenResponse = curl_exec($ch);
    curl_close($ch);
    
    $tokenData = json_decode($tokenResponse, true);
    if (!isset($tokenData['access_token'])) {
        header('Location: /MVNO/auth/login.php?error=token_failed');
        exit;
    }
    
    // 사용자 정보 요청
    $userInfoUrl = 'https://openapi.naver.com/v1/nid/me';
    $ch = curl_init($userInfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenData['access_token']]);
    $userResponse = curl_exec($ch);
    curl_close($ch);
    
    $userData = json_decode($userResponse, true);
    if (!isset($userData['response'])) {
        header('Location: /MVNO/auth/login.php?error=user_info_failed');
        exit;
    }
    
    $naverUser = $userData['response'];
    $snsId = $naverUser['id'] ?? '';
    $email = $naverUser['email'] ?? '';
    $name = $naverUser['name'] ?? '';
    
    if (empty($snsId)) {
        header('Location: /MVNO/auth/login.php?error=invalid_user');
        exit;
    }
    
    // 사용자 등록 또는 로그인
    $user = getUserBySnsId('naver', $snsId);
    if (!$user) {
        $user = registerSnsUser('naver', $snsId, $email, $name);
    }

    if (!$user) {
        header('Location: /MVNO/auth/login.php?error=invalid_request');
        exit;
    }
    
    // 관리자/판매자는 SNS 로그인 불가
    if ($user['role'] === 'admin' || $user['role'] === 'sub_admin' || $user['role'] === 'seller') {
        header('Location: /MVNO/?error=sns_login_not_allowed');
        exit;
    }
    
    loginUser($user['user_id']);
    
    // 전화번호가 없으면 추가 정보 입력 페이지로 리다이렉트
    // 리다이렉트 URL은 이미 세션에 저장되어 있으므로 그대로 유지
    if (empty($user['phone'])) {
        header('Location: /MVNO/auth/complete-profile.php');
        exit;
    }
    
    // 세션에 저장된 리다이렉트 URL 확인
    $redirectUrl = $_SESSION['redirect_url'] ?? null;
    if ($redirectUrl) {
        unset($_SESSION['redirect_url']);
        header('Location: ' . $redirectUrl);
    } else {
        header('Location: /MVNO/');
    }
    exit;
}

// 카카오 콜백 처리
if ($provider === 'kakao') {
    $restApiKey = $settings['kakao']['rest_api_key'] ?? '';
    $redirectUri = $settings['kakao']['redirect_uri'] ?? '';
    
    // 액세스 토큰 요청
    $tokenUrl = 'https://kauth.kakao.com/oauth/token';
    $tokenParams = [
        'grant_type' => 'authorization_code',
        'client_id' => $restApiKey,
        'redirect_uri' => $redirectUri,
        'code' => $code
    ];
    
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenParams));
    $tokenResponse = curl_exec($ch);
    curl_close($ch);
    
    $tokenData = json_decode($tokenResponse, true);
    if (!isset($tokenData['access_token'])) {
        header('Location: /MVNO/auth/login.php?error=token_failed');
        exit;
    }
    
    // 사용자 정보 요청
    $userInfoUrl = 'https://kapi.kakao.com/v2/user/me';
    $ch = curl_init($userInfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenData['access_token']]);
    $userResponse = curl_exec($ch);
    curl_close($ch);
    
    $userData = json_decode($userResponse, true);
    if (!isset($userData['id'])) {
        header('Location: /MVNO/auth/login.php?error=user_info_failed');
        exit;
    }
    
    $snsId = (string)$userData['id'];
    $email = $userData['kakao_account']['email'] ?? '';
    $name = $userData['kakao_account']['profile']['nickname'] ?? '';
    
    // 사용자 등록 또는 로그인
    $user = getUserBySnsId('kakao', $snsId);
    if (!$user) {
        $user = registerSnsUser('kakao', $snsId, $email, $name);
    }

    if (!$user) {
        header('Location: /MVNO/auth/login.php?error=invalid_request');
        exit;
    }
    
    // 관리자/판매자는 SNS 로그인 불가
    if ($user['role'] === 'admin' || $user['role'] === 'sub_admin' || $user['role'] === 'seller') {
        header('Location: /MVNO/?error=sns_login_not_allowed');
        exit;
    }
    
    loginUser($user['user_id']);
    
    // 전화번호가 없으면 추가 정보 입력 페이지로 리다이렉트
    // 리다이렉트 URL은 이미 세션에 저장되어 있으므로 그대로 유지
    if (empty($user['phone'])) {
        header('Location: /MVNO/auth/complete-profile.php');
        exit;
    }
    
    // 세션에 저장된 리다이렉트 URL 확인
    $redirectUrl = $_SESSION['redirect_url'] ?? null;
    if ($redirectUrl) {
        unset($_SESSION['redirect_url']);
        header('Location: ' . $redirectUrl);
    } else {
        header('Location: /MVNO/');
    }
    exit;
}

// 구글 콜백 처리
if ($provider === 'google') {
    // State 검증
    if (!isset($_SESSION['google_state']) || $_SESSION['google_state'] !== $state) {
        header('Location: /MVNO/auth/login.php?error=invalid_state');
        exit;
    }
    unset($_SESSION['google_state']);
    
    $clientId = $settings['google']['client_id'] ?? '';
    $clientSecret = $settings['google']['client_secret'] ?? '';
    $redirectUri = $settings['google']['redirect_uri'] ?? '';
    
    // 액세스 토큰 요청
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $tokenParams = [
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenParams));
    $tokenResponse = curl_exec($ch);
    curl_close($ch);
    
    $tokenData = json_decode($tokenResponse, true);
    if (!isset($tokenData['access_token'])) {
        header('Location: /MVNO/auth/login.php?error=token_failed');
        exit;
    }
    
    // 사용자 정보 요청
    $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
    $ch = curl_init($userInfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenData['access_token']]);
    $userResponse = curl_exec($ch);
    curl_close($ch);
    
    $userData = json_decode($userResponse, true);
    if (!isset($userData['id'])) {
        header('Location: /MVNO/auth/login.php?error=user_info_failed');
        exit;
    }
    
    $snsId = (string)$userData['id'];
    $email = $userData['email'] ?? '';
    $name = $userData['name'] ?? '';
    
    // 사용자 등록 또는 로그인
    $user = getUserBySnsId('google', $snsId);
    if (!$user) {
        $user = registerSnsUser('google', $snsId, $email, $name);
    }

    if (!$user) {
        header('Location: /MVNO/auth/login.php?error=invalid_request');
        exit;
    }
    
    // 관리자/판매자는 SNS 로그인 불가
    if ($user['role'] === 'admin' || $user['role'] === 'sub_admin' || $user['role'] === 'seller') {
        header('Location: /MVNO/?error=sns_login_not_allowed');
        exit;
    }
    
    loginUser($user['user_id']);
    
    // 전화번호가 없으면 추가 정보 입력 페이지로 리다이렉트
    // 리다이렉트 URL은 이미 세션에 저장되어 있으므로 그대로 유지
    if (empty($user['phone'])) {
        header('Location: /MVNO/auth/complete-profile.php');
        exit;
    }
    
    // 세션에 저장된 리다이렉트 URL 확인
    $redirectUrl = $_SESSION['redirect_url'] ?? null;
    if ($redirectUrl) {
        unset($_SESSION['redirect_url']);
        header('Location: ' . $redirectUrl);
    } else {
        header('Location: /MVNO/');
    }
    exit;
}

header('Location: /MVNO/auth/login.php?error=invalid_provider');


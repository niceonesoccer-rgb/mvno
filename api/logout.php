<?php
/**
 * 로그아웃 API
 * 모든 사용자 타입(관리자, 판매자, 일반회원) 공통 사용
 */

// 경로 설정 파일 먼저 로드
require_once __DIR__ . '/../includes/data/path-config.php';

require_once __DIR__ . '/../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 현재 사용자 정보 확인 (리다이렉트 경로 결정용 - 로그아웃 전에)
$currentUser = null;
$userRole = null;
try {
    $currentUser = getCurrentUser();
    $userRole = $currentUser['role'] ?? null;
} catch (Exception $e) {
    // 사용자 정보 가져오기 실패해도 로그아웃은 진행
    error_log('logout: getCurrentUser error - ' . $e->getMessage());
}

// 리다이렉트 경로 결정
$redirectUrl = '/';
if ($userRole === 'admin' || $userRole === 'sub_admin') {
    // 관리자는 로그아웃 후 관리자 로그인 페이지로
    $redirectUrl = getAssetPath('/admin/login.php');
} elseif ($userRole === 'seller') {
    // 판매자는 판매자 센터로
    $redirectUrl = getAssetPath('/seller/');
} else {
    // 일반 사용자는 홈으로
    $redirectUrl = getAssetPath('/');
}

// 로그아웃 처리 (세션 데이터만 정리, 세션 파괴는 하지 않음)
if (isset($_SESSION)) {
    $_SESSION = []; // 세션 배열 초기화
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/'); // 세션 쿠키 삭제
    }
    session_destroy(); // 세션 파괴
}

// 리다이렉트 (절대 URL 사용)
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$absoluteUrl = $protocol . '://' . $host . $redirectUrl;
header('Location: ' . $absoluteUrl);
exit;

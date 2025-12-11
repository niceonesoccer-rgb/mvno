<?php
/**
 * 로그아웃 API
 * 모든 사용자 타입(관리자, 판매자, 일반회원) 공통 사용
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 현재 사용자 정보 확인 (리다이렉트 경로 결정용)
$currentUser = getCurrentUser();
$userRole = $currentUser['role'] ?? null;

// 로그아웃 처리
logoutUser();

// 모든 사용자는 로그아웃 후 홈화면으로 이동
header('Location: /MVNO/');
exit;

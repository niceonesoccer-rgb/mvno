<?php
/**
 * 판매자 로그아웃 페이지
 * 경로: /MVNO/seller/logout.php
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 로그아웃 처리
logoutUser();

// 로그아웃 후 홈화면으로 리다이렉트
header('Location: /MVNO/');
exit;

















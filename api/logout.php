<?php
/**
 * 로그아웃 처리
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';

// 로그아웃 전에 현재 사용자 역할 확인
$currentUser = getCurrentUser();
$userRole = null;
if ($currentUser) {
    $userRole = getUserRole();
}

// 로그아웃 처리
logoutUser();

// 관리자(admin, sub_admin) 또는 판매자(seller)인 경우 관리자 로그인 페이지로 리다이렉트
// 일반 사용자는 SNS 로그인을 사용하므로 메인 페이지로 리다이렉트
if ($userRole === 'admin' || $userRole === 'sub_admin' || $userRole === 'seller') {
    header('Location: /MVNO/admin/');
} else {
    header('Location: /MVNO/');
}
exit;


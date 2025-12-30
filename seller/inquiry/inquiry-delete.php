<?php
/**
 * 판매자 1:1 문의 삭제 처리
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/seller-inquiry-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = getCurrentUser();

// 판매자 로그인 체크
if (!$currentUser || $currentUser['role'] !== 'seller') {
    header('Location: /MVNO/seller/login.php');
    exit;
}

// 판매자 승인 체크
$isApproved = isset($currentUser['seller_approved']) && $currentUser['seller_approved'] === true;
if (!$isApproved) {
    header('Location: /MVNO/seller/waiting.php');
    exit;
}

$sellerId = $currentUser['user_id'];
$inquiryId = intval($_GET['id'] ?? 0);

if (!$inquiryId) {
    header('Location: /MVNO/seller/inquiry/inquiry-list.php');
    exit;
}

// 문의 삭제
if (deleteSellerInquiry($inquiryId, $sellerId)) {
    header('Location: /MVNO/seller/inquiry/inquiry-list.php?success=deleted');
} else {
    header('Location: /MVNO/seller/inquiry/inquiry-detail.php?id=' . $inquiryId . '&error=cannot_delete');
}
exit;




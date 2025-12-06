<?php
/**
 * 판매자 탈퇴 요청 취소 API
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$currentUser = getCurrentUser();

// 판매자 로그인 체크 (탈퇴 요청 상태여도 취소는 가능하도록 세션 체크 완화)
if (!$currentUser || $currentUser['role'] !== 'seller') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '인증이 필요합니다.']);
    exit;
}

// 탈퇴 요청한 상태인지 확인
if (!isset($currentUser['withdrawal_requested']) || $currentUser['withdrawal_requested'] !== true) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '탈퇴 요청 내역이 없습니다.']);
    exit;
}

$userId = $currentUser['user_id'];

if (cancelSellerWithdrawal($userId)) {
    echo json_encode(['success' => true, 'message' => '탈퇴 요청이 취소되었습니다.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '탈퇴 요청 취소에 실패했습니다.']);
}


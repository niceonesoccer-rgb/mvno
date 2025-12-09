<?php
/**
 * 판매자 탈퇴 요청 API
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

// 판매자 로그인 체크
if (!$currentUser || $currentUser['role'] !== 'seller') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '인증이 필요합니다.']);
    exit;
}

// 승인된 판매자만 탈퇴 요청 가능
$approvalStatus = $currentUser['approval_status'] ?? 'pending';
if ($approvalStatus !== 'approved') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '승인된 판매자만 탈퇴를 요청할 수 있습니다.']);
    exit;
}

// 이미 탈퇴 요청한 경우
if (isset($currentUser['withdrawal_requested']) && $currentUser['withdrawal_requested'] === true) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '이미 탈퇴 요청이 접수되었습니다.']);
    exit;
}

$json = json_decode(file_get_contents('php://input'), true);
$reason = $json['reason'] ?? '';

$userId = $currentUser['user_id'];

if (requestSellerWithdrawal($userId, $reason)) {
    // 세션 로그아웃 (계정 비활성화됨)
    logoutUser();
    
    echo json_encode(['success' => true, 'message' => '탈퇴 요청이 접수되었습니다.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '탈퇴 요청 처리에 실패했습니다.']);
}
















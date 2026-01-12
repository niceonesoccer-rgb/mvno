<?php
/**
 * 판매자 탈퇴 요청 취소 API
 */

// 에러 출력 방지 (JSON 응답을 위해)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 출력 버퍼링 시작 (에러 방지)
ob_start();

// CORS 헤더 설정 (필요한 경우)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

// OPTIONS 요청 처리 (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';

// 출력 버퍼 비우기 (include 후 출력 제거)
ob_clean();

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// POST 요청만 허용
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($requestMethod !== 'POST') {
    http_response_code(405);
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'POST 메서드만 허용됩니다.',
        'received_method' => $requestMethod
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 인증 체크 (탈퇴 요청 상태여도 취소는 가능하도록 세션 체크 완화)
$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['role'] !== 'seller') {
    http_response_code(401);
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => '인증이 필요합니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 탈퇴 요청한 상태인지 확인
if (!isset($currentUser['withdrawal_requested']) || $currentUser['withdrawal_requested'] !== true) {
    http_response_code(400);
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => '탈퇴 요청 내역이 없습니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = $currentUser['user_id'];

try {
    if (cancelSellerWithdrawal($userId)) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => '탈퇴 요청이 취소되었습니다.'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => '탈퇴 요청 취소에 실패했습니다.'
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    error_log('cancelSellerWithdrawal error: ' . $e->getMessage());
    http_response_code(500);
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => '탈퇴 요청 취소 중 오류가 발생했습니다.'
    ], JSON_UNESCAPED_UNICODE);
}
















<?php
/**
 * 리다이렉트 URL 저장 API
 * 회원가입/로그인 후 돌아올 주소를 세션에 저장
 */

// auth-functions.php에서 세션 설정과 함께 세션을 시작함
require_once __DIR__ . '/../includes/data/auth-functions.php';

header('Content-Type: application/json');

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// JSON 데이터 읽기
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$redirectUrl = $data['redirect_url'] ?? '';

if (empty($redirectUrl)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'redirect_url is required']);
    exit;
}

// 세션에 리다이렉트 URL 저장
$_SESSION['redirect_url'] = $redirectUrl;

echo json_encode(['success' => true]);












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

// POST 데이터 읽기 (JSON 또는 form-data 모두 지원)
$input = file_get_contents('php://input');
$postData = json_decode($input, true);

// JSON 데이터가 없으면 일반 POST 데이터 사용
if (empty($postData)) {
    $postData = $_POST;
}

$redirectUrl = $postData['redirect_url'] ?? '';

if (empty($redirectUrl)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'redirect_url is required']);
    exit;
}

// 세션에 리다이렉트 URL 저장
$_SESSION['redirect_url'] = $redirectUrl;

echo json_encode(['success' => true]);































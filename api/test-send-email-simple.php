<?php
/**
 * 간단한 테스트 API (이메일 발송 API 테스트용)
 * 실제 send-email-verification.php와 동일한 방식으로 요청 받기
 */

// 가장 먼저 헤더 출력
header('Content-Type: application/json; charset=UTF-8');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'POST 요청만 허용됩니다.',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
    ]);
    exit;
}

// POST 데이터 받기
$rawInput = file_get_contents('php://input');

echo json_encode([
    'success' => true,
    'message' => 'POST 요청을 성공적으로 받았습니다.',
    'request_info' => [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
        'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'not set',
        'raw_input_length' => strlen($rawInput),
        'raw_input_preview' => substr($rawInput, 0, 200)
    ],
    'parsed_data' => json_decode($rawInput, true),
    'json_error' => json_last_error() !== JSON_ERROR_NONE ? json_last_error_msg() : null
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

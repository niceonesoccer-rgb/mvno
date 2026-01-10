<?php
/**
 * API 경로 테스트 파일 (GET 요청)
 * 브라우저에서 직접 접속하여 확인: http://ganadamobile.co.kr/api/test-api-path.php
 */

header('Content-Type: application/json; charset=UTF-8');

// POST 요청 테스트 (실제 API처럼 동작)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    echo json_encode([
        'success' => true,
        'message' => 'POST 요청 테스트 성공',
        'received_data' => json_decode($rawInput, true),
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
        'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'not set'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'API 파일이 정상적으로 실행됩니다.',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'server_info' => [
        'php_version' => PHP_VERSION,
        'host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown'
    ],
    'file_exists' => [
        'send-email-verification.php' => file_exists(__DIR__ . '/send-email-verification.php'),
        'verify-email-code.php' => file_exists(__DIR__ . '/verify-email-code.php'),
        'change-password.php' => file_exists(__DIR__ . '/change-password.php'),
        'update-email.php' => file_exists(__DIR__ . '/update-email.php'),
        'auth-functions.php' => file_exists(__DIR__ . '/../includes/data/auth-functions.php'),
        'mail-helper.php' => file_exists(__DIR__ . '/../includes/data/mail-helper.php')
    ],
    'path_config' => [
        'base_path' => defined('BASE_PATH') ? BASE_PATH : 'not defined',
        'path_config_exists' => file_exists(__DIR__ . '/../includes/data/path-config.php')
    ],
    'note' => 'POST 요청 테스트: 이 URL로 POST 요청을 보내면 요청 데이터를 확인할 수 있습니다.'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

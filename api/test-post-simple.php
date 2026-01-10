<?php
/**
 * 가장 간단한 POST 테스트 파일
 * 서버가 요청을 거부하는지 확인
 */

// 헤더 출력 (가장 먼저)
if (!headers_sent()) {
    header('Content-Type: text/plain; charset=UTF-8');
}

echo "=== POST 테스트 시작 ===\n\n";

echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'not set') . "\n";
echo "CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . "\n";
echo "CONTENT_LENGTH: " . ($_SERVER['CONTENT_LENGTH'] ?? 'not set') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n\n";

echo "=== POST 데이터 ===\n";
if (!empty($_POST)) {
    echo "POST 배열:\n";
    print_r($_POST);
} else {
    echo "POST 배열이 비어있습니다.\n";
}

echo "\n=== php://input ===\n";
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    echo "Raw input 길이: " . strlen($rawInput) . " bytes\n";
    echo "Raw input (처음 500자):\n";
    echo substr($rawInput, 0, 500) . "\n";
    
    // JSON 파싱 시도
    $json = json_decode($rawInput, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "\nJSON 파싱 성공:\n";
        print_r($json);
    } else {
        echo "\nJSON 파싱 실패: " . json_last_error_msg() . "\n";
    }
} else {
    echo "php://input이 비어있습니다.\n";
}

echo "\n=== 모든 서버 변수 ===\n";
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'not set') . "\n";
echo "HTTP_ACCEPT: " . ($_SERVER['HTTP_ACCEPT'] ?? 'not set') . "\n";
echo "HTTP_USER_AGENT: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'not set') . "\n";

<?php
/**
 * 파일 인코딩 수정 스크립트 - null 바이트 제거
 */

$filePath = __DIR__ . '/includes/data/product-functions.php';
$phpPath = 'C:\\xampp\\php\\php.exe';

// 파일을 바이너리로 읽기
$bytes = file_get_contents($filePath, false);

echo "File size: " . strlen($bytes) . " bytes\n";

// 파일 끝 부분에 null 바이트가 있는지 확인
$nullPos = strrpos($bytes, "\x00");
if ($nullPos !== false) {
    echo "Found null byte at position: $nullPos (file end at " . (strlen($bytes) - 1) . ")\n";
    
    // null 바이트부터 끝까지의 내용 확인
    $tail = substr($bytes, max(0, $nullPos - 50), 100);
    echo "Content around null byte:\n";
    for ($i = 0; $i < strlen($tail); $i++) {
        $char = $tail[$i];
        $ord = ord($char);
        if ($ord === 0) {
            echo "[NULL]";
        } elseif ($ord < 32 || $ord > 126) {
            echo "[$ord]";
        } else {
            echo $char;
        }
    }
    echo "\n";
    
    // null 바이트 이전까지만 유지
    $cleanContent = substr($bytes, 0, $nullPos);
    
    // 파일이 올바르게 끝나는지 확인 (닫는 중괄호나 세미콜론)
    $trimmed = rtrim($cleanContent);
    if (substr($trimmed, -1) === '}' || substr($trimmed, -1) === ';') {
        $cleanContent = $trimmed . "\n";
    }
    
    file_put_contents($filePath, $cleanContent);
    echo "Removed null bytes. New file size: " . strlen($cleanContent) . " bytes\n";
} else {
    echo "No null bytes found.\n";
}

// PHP 파싱 테스트
$output = [];
$return = 0;
exec("\"$phpPath\" -l \"$filePath\" 2>&1", $output, $return);
if ($return === 0) {
    echo "SUCCESS: File parses correctly!\n";
} else {
    echo "ERROR: Parse error still exists:\n";
    echo implode("\n", $output) . "\n";
}

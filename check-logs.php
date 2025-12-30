<?php
/**
 * 로그 파일 분석 스크립트
 * 브라우저에서 실행: http://localhost/MVNO/check-logs.php
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>로그 파일 분석</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .log-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    pre { background: #f8f8f8; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 500px; overflow-y: auto; }
    .error { color: red; }
    .success { color: green; }
    .info { color: blue; }
    .warning { color: orange; }
</style>";

// 로그 파일 경로들
$logPaths = [
    'Apache Error Log' => 'C:/xampp/apache/logs/error.log',
    'PHP Error Log' => 'C:/xampp/php/logs/php_error_log.log',
    'Project Error Log' => __DIR__ . '/error_log',
    'Apache Access Log' => 'C:/xampp/apache/logs/access.log',
];

// 상품 ID 62 관련 로그 검색
$productId = 62;
$searchKeywords = [
    "Product ID: $productId",
    "product_id.*$productId",
    "product_mno_sim_details",
    "Error loading product",
    "Product Data Loaded",
    "product_mno_sim_details에서 데이터를 찾을 수 없습니다"
];

echo "<div class='log-section'>";
echo "<h2>로그 파일 위치 확인</h2>";

foreach ($logPaths as $name => $path) {
    echo "<h3>$name</h3>";
    if (file_exists($path)) {
        $size = filesize($path);
        $modified = date('Y-m-d H:i:s', filemtime($path));
        echo "<p class='success'>✅ 파일 존재: $path</p>";
        echo "<p>크기: " . number_format($size) . " bytes</p>";
        echo "<p>수정일: $modified</p>";
        
        // 최근 50줄 읽기
        if ($size > 0) {
            $lines = file($path);
            $recentLines = array_slice($lines, -50);
            $recentContent = implode('', $recentLines);
            
            // 상품 ID 62 관련 로그 찾기
            $found = false;
            foreach ($searchKeywords as $keyword) {
                if (stripos($recentContent, $keyword) !== false || preg_match("/$keyword/i", $recentContent)) {
                    $found = true;
                    break;
                }
            }
            
            if ($found) {
                echo "<p class='info'>🔍 상품 ID $productId 관련 로그가 발견되었습니다.</p>";
                echo "<details>";
                echo "<summary>최근 50줄 보기</summary>";
                echo "<pre>" . htmlspecialchars($recentContent) . "</pre>";
                echo "</details>";
            } else {
                echo "<p>상품 ID $productId 관련 로그가 없습니다.</p>";
            }
        }
    } else {
        echo "<p class='error'>❌ 파일이 존재하지 않습니다: $path</p>";
    }
    echo "<hr>";
}

echo "</div>";

// PHP error_log 함수로 기록된 로그 검색
echo "<div class='log-section'>";
echo "<h2>PHP error_log() 함수로 기록된 로그</h2>";

// XAMPP의 기본 PHP 에러 로그 위치들
$phpLogPaths = [
    'C:/xampp/php/logs/php_error_log.log',
    'C:/xampp/apache/logs/error.log',
    __DIR__ . '/error_log',
    ini_get('error_log') ?: '설정되지 않음'
];

foreach ($phpLogPaths as $logPath) {
    if ($logPath === '설정되지 않음') {
        echo "<p class='warning'>⚠️ PHP error_log 경로가 설정되지 않았습니다.</p>";
        continue;
    }
    
    if (file_exists($logPath)) {
        echo "<h3>$logPath</h3>";
        $content = file_get_contents($logPath);
        
        // 상품 ID 62 관련 라인 찾기
        $lines = explode("\n", $content);
        $matchedLines = [];
        
        foreach ($lines as $lineNum => $line) {
            foreach ($searchKeywords as $keyword) {
                if (stripos($line, $keyword) !== false || preg_match("/$keyword/i", $line)) {
                    $matchedLines[] = [
                        'line' => $lineNum + 1,
                        'content' => $line
                    ];
                    break;
                }
            }
        }
        
        if (!empty($matchedLines)) {
            echo "<p class='success'>✅ " . count($matchedLines) . "개의 관련 로그를 찾았습니다.</p>";
            echo "<pre>";
            foreach ($matchedLines as $match) {
                echo "라인 {$match['line']}: " . htmlspecialchars($match['content']) . "\n";
            }
            echo "</pre>";
        } else {
            echo "<p>상품 ID $productId 관련 로그가 없습니다.</p>";
        }
    }
}

echo "</div>";

// 최근 에러 로그 표시
echo "<div class='log-section'>";
echo "<h2>최근 에러 로그 (상위 20개)</h2>";

$allErrors = [];

foreach ($logPaths as $name => $path) {
    if (file_exists($path) && filesize($path) > 0) {
        $lines = file($path);
        foreach ($lines as $lineNum => $line) {
            if (stripos($line, 'error') !== false || 
                stripos($line, 'Error') !== false || 
                stripos($line, 'ERROR') !== false ||
                stripos($line, 'Warning') !== false) {
                $allErrors[] = [
                    'file' => $name,
                    'line' => $lineNum + 1,
                    'content' => trim($line)
                ];
            }
        }
    }
}

// 최근 20개만 표시
$recentErrors = array_slice($allErrors, -20);

if (!empty($recentErrors)) {
    echo "<pre>";
    foreach ($recentErrors as $error) {
        echo "[{$error['file']}:{$error['line']}] " . htmlspecialchars($error['content']) . "\n";
    }
    echo "</pre>";
} else {
    echo "<p>최근 에러 로그가 없습니다.</p>";
}

echo "</div>";
?>







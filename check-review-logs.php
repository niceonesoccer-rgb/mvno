<?php
/**
 * 리뷰 관련 로그 확인 스크립트
 * 브라우저에서 실행: http://localhost/mvno/check-review-logs.php
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>리뷰 로그 확인</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .log-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        pre { background: #f8f8f8; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 600px; overflow-y: auto; font-size: 12px; line-height: 1.5; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        .warning { color: orange; }
        .log-entry { margin: 5px 0; padding: 5px; border-left: 3px solid #ddd; }
        .log-entry.mno-sim { border-left-color: #6366f1; }
        .log-entry.submit { border-left-color: #10b981; }
        .log-entry.query { border-left-color: #f59e0b; }
        h1 { color: #1f2937; }
        h2 { color: #374151; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }
        .refresh-btn { background: #6366f1; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 10px 0; }
        .refresh-btn:hover { background: #4f46e5; }
    </style>
</head>
<body>
    <h1>리뷰 관련 로그 확인</h1>
    <button class='refresh-btn' onclick='location.reload()'>새로고침</button>
";

// 로그 파일 경로들
$logPaths = [
    'Apache Error Log' => 'C:/xampp/apache/logs/error.log',
    'PHP Error Log' => 'C:/xampp/php/logs/php_error_log',
];

// 리뷰 관련 키워드
$reviewKeywords = [
    'MNO-SIM',
    'mno-sim',
    'addProductReview',
    'submit-review',
    '리뷰',
    'review',
    'product_reviews',
];

echo "<div class='log-section'>";
echo "<h2>로그 파일 위치 확인</h2>";

$allReviewLogs = [];

foreach ($logPaths as $name => $path) {
    echo "<h3>$name</h3>";
    
    if (file_exists($path)) {
        $size = filesize($path);
        $modified = date('Y-m-d H:i:s', filemtime($path));
        echo "<p class='success'>✅ 파일 존재: $path</p>";
        echo "<p>크기: " . number_format($size) . " bytes | 수정일: $modified</p>";
        
        if ($size > 0) {
            // 파일이 너무 크면 최근 부분만 읽기
            $maxLines = 1000;
            $lines = file($path);
            $totalLines = count($lines);
            $startLine = max(0, $totalLines - $maxLines);
            $relevantLines = array_slice($lines, $startLine);
            
            // 리뷰 관련 로그 필터링
            $reviewLines = [];
            foreach ($relevantLines as $lineNum => $line) {
                foreach ($reviewKeywords as $keyword) {
                    if (stripos($line, $keyword) !== false) {
                        $reviewLines[] = [
                            'file' => $name,
                            'line' => $startLine + $lineNum + 1,
                            'content' => trim($line),
                            'timestamp' => extractTimestamp($line)
                        ];
                        break;
                    }
                }
            }
            
            if (!empty($reviewLines)) {
                echo "<p class='info'>🔍 리뷰 관련 로그 " . count($reviewLines) . "개 발견</p>";
                $allReviewLogs = array_merge($allReviewLogs, $reviewLines);
            } else {
                echo "<p>리뷰 관련 로그가 없습니다.</p>";
            }
        }
    } else {
        echo "<p class='error'>❌ 파일이 존재하지 않습니다: $path</p>";
    }
    echo "<hr>";
}

echo "</div>";

// 리뷰 로그 정렬 (최신순)
usort($allReviewLogs, function($a, $b) {
    return $b['line'] - $a['line'];
});

// 최근 100개만 표시
$recentLogs = array_slice($allReviewLogs, 0, 100);

echo "<div class='log-section'>";
echo "<h2>리뷰 관련 로그 (최근 " . count($recentLogs) . "개)</h2>";

if (!empty($recentLogs)) {
    echo "<pre>";
    foreach ($recentLogs as $log) {
        $class = '';
        if (stripos($log['content'], 'mno-sim') !== false || stripos($log['content'], 'MNO-SIM') !== false) {
            $class = 'mno-sim';
        } elseif (stripos($log['content'], 'submit-review') !== false || stripos($log['content'], 'addProductReview') !== false) {
            $class = 'submit';
        } elseif (stripos($log['content'], 'SELECT') !== false || stripos($log['content'], 'INSERT') !== false || stripos($log['content'], 'UPDATE') !== false) {
            $class = 'query';
        }
        
        echo "<div class='log-entry $class'>";
        echo "[{$log['file']}:{$log['line']}] ";
        echo htmlspecialchars($log['content']);
        echo "</div>";
    }
    echo "</pre>";
} else {
    echo "<p>리뷰 관련 로그가 없습니다.</p>";
}

echo "</div>";

// MNO-SIM 리뷰 관련 로그만 필터링
echo "<div class='log-section'>";
echo "<h2>MNO-SIM 리뷰 관련 로그 (상세)</h2>";

$mnoSimLogs = array_filter($allReviewLogs, function($log) {
    return stripos($log['content'], 'mno-sim') !== false || 
           stripos($log['content'], 'MNO-SIM') !== false ||
           stripos($log['content'], 'submit-review') !== false ||
           stripos($log['content'], 'addProductReview') !== false ||
           stripos($log['content'], 'product_reviews') !== false;
});

$mnoSimLogs = array_slice($mnoSimLogs, 0, 50);

if (!empty($mnoSimLogs)) {
    echo "<pre>";
    foreach ($mnoSimLogs as $log) {
        $class = 'mno-sim';
        if (stripos($log['content'], 'submit-review') !== false || stripos($log['content'], 'addProductReview') !== false) {
            $class = 'submit';
        }
        
        echo "<div class='log-entry $class'>";
        echo "[{$log['file']}:{$log['line']}] ";
        echo htmlspecialchars($log['content']);
        echo "</div>";
    }
    echo "</pre>";
} else {
    echo "<p>MNO-SIM 리뷰 관련 로그가 없습니다.</p>";
}

echo "</div>";

echo "</body></html>";

// 타임스탬프 추출 함수
function extractTimestamp($line) {
    // Apache 로그 형식: [Sun Jan 11 20:24:22.440444 2026]
    if (preg_match('/\[([^\]]+)\]/', $line, $matches)) {
        return $matches[1];
    }
    return '';
}

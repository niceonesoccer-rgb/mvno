<?php
/**
 * Q&A 관련 로그 확인 도구
 */
require_once __DIR__ . '/../includes/data/auth-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isAdmin()) {
    die('관리자 권한이 필요합니다.');
}

// 에러 로그 파일 찾기
$errorLogPaths = [
    ini_get('error_log'),
    'C:\\xampp\\apache\\logs\\error.log',
    'C:\\xampp\\php\\logs\\php_error_log',
    'C:\\xampp\\php\\logs\\error.log',
    __DIR__ . '/../error.log'
];

$errorLogPath = null;
foreach ($errorLogPaths as $path) {
    if ($path && file_exists($path)) {
        $errorLogPath = $path;
        break;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Q&A 로그 확인</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; max-height: 500px; overflow-y: auto; }
        .error { color: red; }
        .success { color: green; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>Q&A 관련 로그 확인</h1>
    
    <?php
    if ($errorLogPath) {
        echo '<div class="section">';
        echo '<h2>에러 로그 파일: ' . htmlspecialchars($errorLogPath) . '</h2>';
        
        $lines = file($errorLogPath);
        $qnaLines = array_filter($lines, function($line) {
            return stripos($line, 'qna') !== false || 
                   stripos($line, 'QnA') !== false ||
                   stripos($line, 'debug_') !== false ||
                   stripos($line, 'delete_') !== false;
        });
        
        // 최근 100줄만 표시
        $qnaLines = array_slice($qnaLines, -100);
        
        if (empty($qnaLines)) {
            echo '<p class="info">QnA 관련 로그가 없습니다.</p>';
        } else {
            echo '<p class="success">QnA 관련 로그 ' . count($qnaLines) . '줄 발견</p>';
            echo '<pre>';
            foreach ($qnaLines as $line) {
                // 디버그 ID로 그룹화하여 표시
                echo htmlspecialchars($line);
            }
            echo '</pre>';
        }
        echo '</div>';
    } else {
        echo '<div class="section">';
        echo '<h2>에러 로그 파일을 찾을 수 없습니다</h2>';
        echo '<p>다음 경로를 확인했습니다:</p>';
        echo '<ul>';
        foreach ($errorLogPaths as $path) {
            echo '<li>' . htmlspecialchars($path ?: 'NULL') . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    
    // PHP 설정 확인
    echo '<div class="section">';
    echo '<h2>PHP 설정</h2>';
    echo '<p><strong>error_log:</strong> ' . htmlspecialchars(ini_get('error_log') ?: '설정되지 않음') . '</p>';
    echo '<p><strong>log_errors:</strong> ' . (ini_get('log_errors') ? 'ON' : 'OFF') . '</p>';
    echo '<p><strong>display_errors:</strong> ' . (ini_get('display_errors') ? 'ON' : 'OFF') . '</p>';
    echo '</div>';
    ?>
    
    <div class="section">
        <a href="/MVNO/admin/debug-qna-deletion.php">진단 도구로</a> |
        <a href="/MVNO/admin/content/qna-manage.php">관리자 페이지로</a>
    </div>
</body>
</html>









<?php
/**
 * 디버깅 페이지 - 파일 업로드 상태 확인
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/seller-inquiry-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = getCurrentUser();

if (!$currentUser || $currentUser['role'] !== 'seller') {
    die('Access denied');
}

$sellerId = $currentUser['user_id'];
$inquiryId = intval($_GET['id'] ?? 0);

if (!$inquiryId) {
    die('No inquiry ID');
}

$inquiry = getSellerInquiryById($inquiryId);
$attachments = getSellerInquiryAttachments($inquiryId);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>디버깅 정보</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; }
        .error { color: red; }
        .success { color: green; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>문의 디버깅 정보</h1>
    
    <div class="section">
        <h2>문의 정보</h2>
        <pre><?php print_r($inquiry); ?></pre>
    </div>
    
    <div class="section">
        <h2>첨부파일 정보 (DB)</h2>
        <pre><?php print_r($attachments); ?></pre>
    </div>
    
    <div class="section">
        <h2>파일 시스템 확인</h2>
        <?php
        $uploadBaseDir = __DIR__ . '/../../uploads/seller-inquiries/';
        echo "<p>업로드 기본 디렉토리: <strong>$uploadBaseDir</strong></p>";
        echo "<p>디렉토리 존재: " . (is_dir($uploadBaseDir) ? '<span class="success">예</span>' : '<span class="error">아니오</span>') . "</p>";
        
        if ($inquiryId) {
            $inquiryDir = $uploadBaseDir . $inquiryId . '/';
            echo "<p>문의 디렉토리: <strong>$inquiryDir</strong></p>";
            echo "<p>디렉토리 존재: " . (is_dir($inquiryDir) ? '<span class="success">예</span>' : '<span class="error">아니오</span>') . "</p>";
            
            if (is_dir($inquiryDir)) {
                $files = scandir($inquiryDir);
                $files = array_filter($files, function($f) { return $f !== '.' && $f !== '..'; });
                echo "<p>디렉토리 내 파일:</p>";
                echo "<ul>";
                foreach ($files as $file) {
                    $fullPath = $inquiryDir . $file;
                    $size = filesize($fullPath);
                    $exists = file_exists($fullPath);
                    echo "<li>$file - " . number_format($size) . " bytes - " . ($exists ? '<span class="success">존재</span>' : '<span class="error">없음</span>') . "</li>";
                }
                echo "</ul>";
            }
        }
        
        foreach ($attachments as $att) {
            // DB 경로를 실제 파일 시스템 경로로 변환
            // DB 경로: /MVNO/uploads/... -> 실제 경로: __DIR__/../../uploads/...
            $dbPath = $att['file_path'];
            $actualPath = str_replace('/MVNO', '', $dbPath);
            // __DIR__은 seller/inquiry이므로 ../../로 루트로 이동
            $filePath = __DIR__ . '/../..' . $actualPath;
            
            echo "<div style='margin: 10px 0; padding: 10px; background: #f9f9f9; border-radius: 4px;'>";
            echo "<strong>파일: " . htmlspecialchars($att['file_name']) . "</strong><br>";
            echo "DB 경로: " . htmlspecialchars($dbPath) . "<br>";
            echo "실제 경로: " . htmlspecialchars($filePath) . "<br>";
            echo "파일 존재: " . (file_exists($filePath) ? '<span class="success">예</span>' : '<span class="error">아니오</span>') . "<br>";
            if (file_exists($filePath)) {
                echo "파일 크기: " . number_format(filesize($filePath)) . " bytes<br>";
            }
            echo "</div>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>PHP 설정</h2>
        <ul>
            <li>upload_max_filesize: <?php echo ini_get('upload_max_filesize'); ?></li>
            <li>post_max_size: <?php echo ini_get('post_max_size'); ?></li>
            <li>max_file_uploads: <?php echo ini_get('max_file_uploads'); ?></li>
            <li>file_uploads: <?php echo ini_get('file_uploads') ? 'On' : 'Off'; ?></li>
        </ul>
    </div>
    
    <div class="section">
        <h2>에러 로그 (최근 50줄)</h2>
        <pre><?php
        $logFile = __DIR__ . '/../../error_log';
        if (file_exists($logFile)) {
            $lines = file($logFile);
            $lines = array_slice($lines, -50);
            echo htmlspecialchars(implode('', $lines));
        } else {
            echo "로그 파일이 없습니다.";
        }
        ?></pre>
    </div>
</body>
</html>


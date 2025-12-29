<?php
/**
 * 문의 수정 디버깅 페이지
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/seller-inquiry-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = getCurrentUser();
if (!$user || !isSeller($user['user_id'])) {
    header('Location: /MVNO/seller/login.php');
    exit;
}

$sellerId = $user['user_id'];
$inquiryId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$inquiryId) {
    die('문의 ID가 필요합니다.');
}

$inquiry = getSellerInquiryById($inquiryId);
if (!$inquiry || $inquiry['seller_id'] !== $sellerId) {
    die('문의를 찾을 수 없거나 권한이 없습니다.');
}

$existingAttachments = getSellerInquiryAttachments($inquiryId);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>문의 수정 디버깅</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .debug-section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .debug-section h2 {
            margin-top: 0;
            color: #333;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .file-info {
            margin: 10px 0;
            padding: 10px;
            background: #f9f9f9;
            border-left: 3px solid #6366f1;
        }
        .file-exists {
            color: #10b981;
        }
        .file-not-exists {
            color: #ef4444;
        }
    </style>
</head>
<body>
    <h1>문의 수정 디버깅 정보</h1>
    
    <div class="debug-section">
        <h2>문의 정보</h2>
        <pre><?php print_r($inquiry); ?></pre>
    </div>
    
    <div class="debug-section">
        <h2>기존 첨부파일 (DB)</h2>
        <pre><?php print_r($existingAttachments); ?></pre>
        
        <h3>파일 시스템 확인</h3>
        <?php if (!empty($existingAttachments)): ?>
            <?php foreach ($existingAttachments as $attachment): ?>
                <div class="file-info">
                    <strong><?php echo htmlspecialchars($attachment['file_name']); ?></strong><br>
                    DB 경로: <?php echo htmlspecialchars($attachment['file_path']); ?><br>
                    <?php
                    $dbPath = $attachment['file_path'];
                    $actualPath = str_replace('/MVNO', '', $dbPath);
                    $filePath = __DIR__ . '/../..' . $actualPath;
                    $exists = file_exists($filePath);
                    ?>
                    실제 경로: <?php echo htmlspecialchars($filePath); ?><br>
                    <span class="<?php echo $exists ? 'file-exists' : 'file-not-exists'; ?>">
                        파일 존재: <?php echo $exists ? '예' : '아니오'; ?>
                    </span>
                    <?php if ($exists): ?>
                        <br>파일 크기: <?php echo number_format(filesize($filePath)); ?> bytes
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>등록된 첨부파일이 없습니다.</p>
        <?php endif; ?>
    </div>
    
    <div class="debug-section">
        <h2>업로드 디렉토리 확인</h2>
        <?php
        $uploadBaseDir = __DIR__ . '/../../uploads/seller-inquiries/';
        $inquiryDir = $uploadBaseDir . $inquiryId . '/';
        ?>
        <p>기본 디렉토리: <code><?php echo htmlspecialchars($uploadBaseDir); ?></code></p>
        <p>존재: <?php echo is_dir($uploadBaseDir) ? '예' : '아니오'; ?></p>
        <p>문의 디렉토리: <code><?php echo htmlspecialchars($inquiryDir); ?></code></p>
        <p>존재: <?php echo is_dir($inquiryDir) ? '예' : '아니오'; ?></p>
        
        <?php if (is_dir($inquiryDir)): ?>
            <h3>디렉토리 내 파일</h3>
            <?php
            $files = scandir($inquiryDir);
            $files = array_filter($files, function($f) { return $f !== '.' && $f !== '..'; });
            if (!empty($files)): ?>
                <ul>
                    <?php foreach ($files as $file): ?>
                        <li>
                            <?php echo htmlspecialchars($file); ?> - 
                            <?php echo number_format(filesize($inquiryDir . $file)); ?> bytes
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>디렉토리가 비어있습니다.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <div class="debug-section">
        <h2>PHP 설정</h2>
        <ul>
            <li>upload_max_filesize: <?php echo ini_get('upload_max_filesize'); ?></li>
            <li>post_max_size: <?php echo ini_get('post_max_size'); ?></li>
            <li>max_file_uploads: <?php echo ini_get('max_file_uploads'); ?></li>
            <li>file_uploads: <?php echo ini_get('file_uploads') ? 'On' : 'Off'; ?></li>
        </ul>
    </div>
    
    <div class="debug-section">
        <h2>에러 로그 (최근 50줄)</h2>
        <?php
        $logFile = __DIR__ . '/../../error_log';
        if (file_exists($logFile)) {
            $lines = file($logFile);
            $recentLines = array_slice($lines, -50);
            echo '<pre>' . htmlspecialchars(implode('', $recentLines)) . '</pre>';
        } else {
            echo '<p>로그 파일이 없습니다.</p>';
        }
        ?>
    </div>
    
    <div style="margin-top: 20px;">
        <a href="/MVNO/seller/inquiry/inquiry-edit.php?id=<?php echo $inquiryId; ?>">수정 페이지로 돌아가기</a> |
        <a href="/MVNO/seller/inquiry/inquiry-detail.php?id=<?php echo $inquiryId; ?>">상세 페이지로 가기</a>
    </div>
</body>
</html>


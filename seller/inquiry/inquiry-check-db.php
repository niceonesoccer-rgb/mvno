<?php
/**
 * DB 확인 페이지
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

ensureSellerInquiryTables();

$pdo = getDBConnection();
if (!$pdo) {
    die('DB connection failed');
}

// 문의 정보
$stmt = $pdo->prepare("SELECT * FROM seller_inquiries WHERE id = :id");
$stmt->execute([':id' => $inquiryId]);
$inquiry = $stmt->fetch(PDO::FETCH_ASSOC);

// 첨부파일 정보
$stmt = $pdo->prepare("SELECT * FROM seller_inquiry_attachments WHERE inquiry_id = :id ORDER BY created_at ASC");
$stmt->execute([':id' => $inquiryId]);
$attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>DB 확인</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>DB 확인 - 문의 ID: <?php echo $inquiryId; ?></h1>
    
    <div class="section">
        <h2>문의 정보 (seller_inquiries)</h2>
        <?php if ($inquiry): ?>
            <table>
                <?php foreach ($inquiry as $key => $value): ?>
                    <tr>
                        <th><?php echo htmlspecialchars($key); ?></th>
                        <td><?php echo htmlspecialchars($value ?? 'NULL'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p style="color: red;">문의를 찾을 수 없습니다.</p>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>첨부파일 정보 (seller_inquiry_attachments)</h2>
        <p>총 <?php echo count($attachments); ?>개</p>
        <?php if (!empty($attachments)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>파일명</th>
                        <th>경로</th>
                        <th>크기</th>
                        <th>타입</th>
                        <th>업로드자</th>
                        <th>생성일</th>
                        <th>파일 존재</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attachments as $att): ?>
                        <?php
                        $filePath = __DIR__ . '/../..' . $att['file_path'];
                        $fileExists = file_exists($filePath);
                        ?>
                        <tr>
                            <td><?php echo $att['id']; ?></td>
                            <td><?php echo htmlspecialchars($att['file_name']); ?></td>
                            <td><?php echo htmlspecialchars($att['file_path']); ?></td>
                            <td><?php echo number_format($att['file_size']); ?> bytes</td>
                            <td><?php echo htmlspecialchars($att['file_type']); ?></td>
                            <td><?php echo htmlspecialchars($att['uploaded_by']); ?></td>
                            <td><?php echo htmlspecialchars($att['created_at']); ?></td>
                            <td style="color: <?php echo $fileExists ? 'green' : 'red'; ?>;">
                                <?php echo $fileExists ? '✓ 존재' : '✗ 없음'; ?>
                                <?php if ($fileExists): ?>
                                    (<?php echo number_format(filesize($filePath)); ?> bytes)
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: orange;">첨부파일이 없습니다.</p>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>SQL 쿼리</h2>
        <pre>SELECT * FROM seller_inquiry_attachments WHERE inquiry_id = <?php echo $inquiryId; ?>;</pre>
    </div>
    
    <div class="section">
        <h2>파일 시스템</h2>
        <?php
        $uploadDir = __DIR__ . '/../../uploads/seller-inquiries/' . $inquiryId . '/';
        echo "<p>경로: <strong>$uploadDir</strong></p>";
        echo "<p>존재: " . (is_dir($uploadDir) ? '✓' : '✗') . "</p>";
        
        if (is_dir($uploadDir)) {
            $files = scandir($uploadDir);
            $files = array_filter($files, function($f) { return $f !== '.' && $f !== '..'; });
            echo "<p>파일 목록:</p><ul>";
            foreach ($files as $file) {
                $fullPath = $uploadDir . $file;
                echo "<li>$file - " . number_format(filesize($fullPath)) . " bytes</li>";
            }
            echo "</ul>";
        }
        ?>
    </div>
</body>
</html>


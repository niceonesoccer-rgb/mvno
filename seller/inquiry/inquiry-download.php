<?php
/**
 * 판매자 문의 첨부파일 다운로드
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/seller-inquiry-functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = getCurrentUser();

// 판매자 로그인 체크
if (!$currentUser || $currentUser['role'] !== 'seller') {
    http_response_code(403);
    exit('Access denied');
}

$sellerId = $currentUser['user_id'];
$fileId = intval($_GET['file_id'] ?? 0);

if (!$fileId) {
    http_response_code(400);
    exit('Invalid file ID');
}

$pdo = getDBConnection();
if (!$pdo) {
    http_response_code(500);
    exit('Database error');
}

// 파일 정보 조회
$stmt = $pdo->prepare("
    SELECT a.*, i.seller_id
    FROM seller_inquiry_attachments a
    INNER JOIN seller_inquiries i ON a.inquiry_id = i.id
    WHERE a.id = :file_id
");
$stmt->execute([':file_id' => $fileId]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file || $file['seller_id'] !== $sellerId) {
    http_response_code(403);
    exit('Access denied');
}

$filePath = __DIR__ . '/../..' . $file['file_path'];

if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File not found');
}

// 파일 다운로드
header('Content-Type: ' . $file['file_type']);
header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
header('Content-Length: ' . filesize($filePath));

readfile($filePath);
exit;


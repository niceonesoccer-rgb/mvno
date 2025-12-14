<?php
/**
 * 주문 상태 변경 API
 * 판매자가 주문의 진행상황을 변경할 때 사용
 */

require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

header('Content-Type: application/json; charset=utf-8');

// 로그인 체크
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다.'
    ]);
    exit;
}

$currentUser = getCurrentUser();

// 판매자 로그인 체크
if (!$currentUser || $currentUser['role'] !== 'seller') {
    echo json_encode([
        'success' => false,
        'message' => '판매자만 접근 가능합니다.'
    ]);
    exit;
}

// POST 데이터 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'POST 요청만 허용됩니다.'
    ]);
    exit;
}

// 필수 필드 확인
$applicationId = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
$newStatus = isset($_POST['status']) ? trim($_POST['status']) : '';

// 유효한 상태 값 체크
$validStatuses = ['received', 'activating', 'on_hold', 'cancelled', 'activation_completed', 'installation_completed'];

if (empty($applicationId) || empty($newStatus)) {
    echo json_encode([
        'success' => false,
        'message' => '필수 정보가 누락되었습니다.'
    ]);
    exit;
}

if (!in_array($newStatus, $validStatuses)) {
    echo json_encode([
        'success' => false,
        'message' => '유효하지 않은 상태 값입니다.'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    $sellerId = (string)$currentUser['user_id'];
    
    // 주문 정보 확인 (판매자의 주문인지 확인)
    $stmt = $pdo->prepare("
        SELECT a.id, a.seller_id
        FROM product_applications a
        WHERE a.id = ? AND a.seller_id = ?
        LIMIT 1
    ");
    $stmt->execute([$applicationId, $sellerId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        throw new Exception('주문을 찾을 수 없거나 접근 권한이 없습니다.');
    }
    
    // 상태 업데이트
    $stmt = $pdo->prepare("
        UPDATE product_applications
        SET application_status = :status, updated_at = NOW()
        WHERE id = :application_id AND seller_id = :seller_id
    ");
    $stmt->execute([
        ':status' => $newStatus,
        ':application_id' => $applicationId,
        ':seller_id' => $sellerId
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => '상태가 변경되었습니다.',
        'status' => $newStatus
    ]);
    
} catch (Exception $e) {
    error_log("Update Order Status Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

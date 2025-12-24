<?php
/**
 * 이벤트 상품 순서 업데이트 API
 * 드래그 앤 드롭으로 순서 변경 시 사용
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';

// 관리자 권한 체크
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST 요청만 허용됩니다.']);
    exit;
}

$eventId = trim($_POST['event_id'] ?? '');
$productIdsJson = $_POST['product_ids'] ?? '';

if (empty($eventId)) {
    echo json_encode(['success' => false, 'message' => '이벤트 ID가 필요합니다.']);
    exit;
}

// JSON 문자열을 배열로 변환
$productIds = json_decode($productIdsJson, true);
if (!is_array($productIds) || empty($productIds)) {
    echo json_encode(['success' => false, 'message' => '상품 ID 목록이 필요합니다.']);
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => '데이터베이스 연결에 실패했습니다.']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // 각 상품의 순서 업데이트
    foreach ($productIds as $order => $productId) {
        $productId = (int)$productId;
        $displayOrder = (int)$order;
        
        $stmt = $pdo->prepare("
            UPDATE event_products 
            SET display_order = :display_order 
            WHERE event_id = :event_id AND product_id = :product_id
        ");
        $stmt->execute([
            ':event_id' => $eventId,
            ':product_id' => $productId,
            ':display_order' => $displayOrder
        ]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => '순서가 업데이트되었습니다.'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Update event product order error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '순서 업데이트 중 오류가 발생했습니다.']);
}


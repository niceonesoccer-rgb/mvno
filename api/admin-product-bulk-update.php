<?php
/**
 * 관리자용 상품 일괄 상태 변경 API
 * POST /api/admin-product-bulk-update.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'POST 메서드만 허용됩니다.'
    ]);
    exit;
}

// 관리자 인증 체크
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => '관리자 권한이 필요합니다.'
    ]);
    exit;
}

// JSON 데이터 읽기
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// 디버깅을 위한 로그 (개발 환경에서만)
if (isset($_ENV['DEBUG']) && $_ENV['DEBUG']) {
    error_log("Bulk update request: " . $input);
}

if (!$data) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '잘못된 요청입니다. (JSON 파싱 실패)'
    ]);
    exit;
}

if (!isset($data['product_ids'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '상품 ID가 필요합니다. (product_ids 필드 없음)'
    ]);
    exit;
}

if (!isset($data['status'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '상태 값이 필요합니다. (status 필드 없음)'
    ]);
    exit;
}

$productIds = $data['product_ids'];
$status = trim($data['status'] ?? '');

// 상태 값 검증 (대소문자 구분 없이)
$validStatuses = ['active', 'inactive'];
$statusLower = strtolower($status);
if (!in_array($statusLower, $validStatuses)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '유효하지 않은 상태 값입니다. (받은 값: ' . htmlspecialchars($status) . ')'
    ]);
    exit;
}

// 소문자로 정규화
$status = $statusLower;

// 상품 ID 배열 검증
if (!is_array($productIds) || empty($productIds)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '상품 ID가 필요합니다.'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => '데이터베이스 연결에 실패했습니다.'
        ]);
        exit;
    }
    
    // 상품 ID를 정수로 변환 및 검증
    $validProductIds = [];
    foreach ($productIds as $id) {
        $id = intval($id);
        if ($id > 0) {
            $validProductIds[] = $id;
        }
    }
    
    if (empty($validProductIds)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '유효한 상품 ID가 없습니다.'
        ]);
        exit;
    }
    
    // 플레이스홀더 생성
    $placeholders = implode(',', array_fill(0, count($validProductIds), '?'));
    
    // 판매중으로 변경하려는 경우, 탈퇴 신청/완료 회원의 상품인지 확인
    if ($status === 'active') {
        // 탈퇴 신청 또는 탈퇴 완료한 판매자의 상품 조회
        $checkStmt = $pdo->prepare("
            SELECT p.id, p.seller_id, u.user_id
            FROM products p
            LEFT JOIN users u ON p.seller_id = u.user_id
            LEFT JOIN seller_profiles sp ON u.user_id = sp.user_id
            WHERE p.id IN ($placeholders)
            AND p.status != 'deleted'
            AND (
                -- 탈퇴 신청
                COALESCE(sp.withdrawal_requested, u.withdrawal_requested, 0) = 1
                OR u.approval_status = 'withdrawal_requested'
                -- 탈퇴 완료
                OR COALESCE(sp.withdrawal_completed, u.withdrawal_completed, 0) = 1
                OR u.approval_status = 'withdrawn'
            )
        ");
        $checkStmt->execute($validProductIds);
        $withdrawalProducts = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($withdrawalProducts)) {
            $withdrawalCount = count($withdrawalProducts);
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "탈퇴 신청/완료 회원의 상품 {$withdrawalCount}개는 판매중으로 변경할 수 없습니다."
            ]);
            exit;
        }
    }
    
    // 관리자는 모든 상품의 상태를 변경할 수 있음 (seller_id 체크 없음)
    $stmt = $pdo->prepare("
        UPDATE products 
        SET status = ?, updated_at = NOW()
        WHERE id IN ($placeholders) 
        AND status != 'deleted'
    ");
    
    $params = array_merge([$status], $validProductIds);
    $stmt->execute($params);
    
    $affectedRows = $stmt->rowCount();
    
    if ($affectedRows > 0) {
        echo json_encode([
            'success' => true,
            'message' => $affectedRows . '개의 상품이 업데이트되었습니다.',
            'affected_count' => $affectedRows
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => '업데이트할 상품을 찾을 수 없습니다.'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Error updating products: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '상품 상태 변경 중 오류가 발생했습니다.'
    ]);
} catch (Exception $e) {
    error_log("Unexpected error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '예기치 않은 오류가 발생했습니다.'
    ]);
}




































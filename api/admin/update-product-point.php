<?php
/**
 * 관리자 상품 포인트 및 혜택내용 업데이트 API
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

header('Content-Type: application/json');

// 관리자 권한 체크
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => '관리자만 접근할 수 있습니다.']);
    exit;
}

// POST 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

$productId = isset($input['product_id']) ? intval($input['product_id']) : 0;
$pointSetting = isset($input['point_setting']) ? intval($input['point_setting']) : 0;
$benefitDescription = isset($input['point_benefit_description']) ? trim($input['point_benefit_description']) : '';

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => '유효하지 않은 상품 ID입니다.']);
    exit;
}

// 포인트 검증 (1000원 단위)
if ($pointSetting > 0 && $pointSetting % 1000 !== 0) {
    echo json_encode(['success' => false, 'message' => '포인트는 1000원 단위로 입력해주세요.']);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    // 상품 존재 확인
    $checkStmt = $pdo->prepare("SELECT id FROM products WHERE id = :product_id AND status != 'deleted'");
    $checkStmt->execute([':product_id' => $productId]);
    if (!$checkStmt->fetch()) {
        throw new Exception('상품을 찾을 수 없습니다.');
    }
    
    // 포인트 및 혜택내용 업데이트
    $updateStmt = $pdo->prepare("
        UPDATE products 
        SET point_setting = :point_setting,
            point_benefit_description = :point_benefit_description,
            updated_at = NOW()
        WHERE id = :product_id
    ");
    
    $updateStmt->execute([
        ':point_setting' => $pointSetting,
        ':point_benefit_description' => $benefitDescription ?: null,
        ':product_id' => $productId
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => '포인트 및 혜택내용이 저장되었습니다.',
        'data' => [
            'product_id' => $productId,
            'point_setting' => $pointSetting,
            'point_benefit_description' => $benefitDescription
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Update product point error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '데이터베이스 오류가 발생했습니다.']);
} catch (Exception $e) {
    error_log("Update product point error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

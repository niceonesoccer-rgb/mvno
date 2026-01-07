<?php
/**
 * 상품별 포인트 설정 조회 API
 */
require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: application/json; charset=utf-8');

$type = $_GET['type'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (empty($type) || $id <= 0) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'DB 연결 실패']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.point_setting,
            p.point_benefit_description,
            CASE p.product_type
                WHEN 'mvno' THEN mvno.plan_name
                WHEN 'mno' THEN mno.device_name
                WHEN 'mno-sim' THEN mno_sim.plan_name
                WHEN 'internet' THEN internet.registration_place
                ELSE NULL
            END as product_name
        FROM products p
        LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id AND p.product_type = 'mvno'
        LEFT JOIN product_mno_details mno ON p.id = mno.product_id AND p.product_type = 'mno'
        LEFT JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id AND p.product_type = 'mno-sim'
        LEFT JOIN product_internet_details internet ON p.id = internet.product_id AND p.product_type = 'internet'
        WHERE p.id = :id AND p.product_type = :type AND p.status != 'deleted'
        LIMIT 1
    ");
    
    $stmt->execute([':id' => $id, ':type' => $type]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => '상품을 찾을 수 없습니다.']);
        exit;
    }
    
    $point_setting = intval($product['point_setting'] ?? 0);
    $point_benefit_description = $product['point_benefit_description'] ?? '';
    
    // 포인트 설정이 0이거나 할인 혜택이 없으면 포인트 사용 불가
    $can_use_point = ($point_setting > 0 && !empty($point_benefit_description));
    
    echo json_encode([
        'success' => true,
        'point_setting' => $point_setting,
        'point_benefit_description' => $point_benefit_description,
        'product_name' => $product['product_name'] ?? '',
        'can_use_point' => $can_use_point
    ]);
} catch (PDOException $e) {
    error_log('get-product-point-setting error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '조회 중 오류가 발생했습니다.']);
}

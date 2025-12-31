<?php
/**
 * 상품명 조회 API
 * 경로: /api/get-product-name.php
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

$productId = intval($_GET['product_id'] ?? 0);

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            p.product_type,
            CASE p.product_type
                WHEN 'mvno' THEN mvno.plan_name
                WHEN 'mno' THEN mno.device_name
                WHEN 'mno_sim' THEN mno_sim.plan_name
                WHEN 'internet' THEN CONCAT(inet.registration_place, ' ', inet.speed_option)
            END AS product_name
        FROM products p
        LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id AND p.product_type = 'mvno'
        LEFT JOIN product_mno_details mno ON p.id = mno.product_id AND p.product_type = 'mno'
        LEFT JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id AND p.product_type = 'mno_sim'
        LEFT JOIN product_internet_details inet ON p.id = inet.product_id AND p.product_type = 'internet'
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo json_encode([
            'success' => true,
            'product_name' => $product['product_name'] ?? '',
            'product_type' => $product['product_type'] ?? ''
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
} catch (PDOException $e) {
    error_log('Error fetching product name: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

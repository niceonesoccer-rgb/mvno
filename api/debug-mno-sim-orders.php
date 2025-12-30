<?php
/**
 * 통신사유심 주문 디버깅 API
 * 실제 DB에 저장된 데이터 확인
 */

require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

header('Content-Type: application/json; charset=utf-8');

// 판매자 로그인 체크
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['role'] !== 'seller') {
    echo json_encode(['success' => false, 'message' => '판매자만 접근 가능합니다.']);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결 실패');
    }
    
    $sellerId = (string)$currentUser['user_id'];
    
    // 1. product_applications 테이블 확인
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.order_number,
            a.product_id,
            a.seller_id,
            a.product_type,
            a.application_status,
            a.user_id,
            a.created_at,
            p.status as product_status
        FROM product_applications a
        LEFT JOIN products p ON a.product_id = p.id
        WHERE a.seller_id = :seller_id
        ORDER BY a.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([':seller_id' => $sellerId]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. mno-sim 타입만 필터링
    $mnoSimApplications = array_filter($applications, function($app) {
        return $app['product_type'] === 'mno-sim';
    });
    
    // 3. application_customers 조인 확인
    $stmt2 = $pdo->prepare("
        SELECT 
            a.id as application_id,
            a.order_number,
            a.product_type,
            c.id as customer_id,
            c.name,
            c.phone,
            c.email,
            c.additional_info
        FROM product_applications a
        LEFT JOIN application_customers c ON a.id = c.application_id
        WHERE a.seller_id = :seller_id
        AND a.product_type = 'mno-sim'
        ORDER BY a.created_at DESC
        LIMIT 10
    ");
    $stmt2->execute([':seller_id' => $sellerId]);
    $joinedData = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. product_mno_sim_details 조인 확인
    $stmt3 = $pdo->prepare("
        SELECT 
            a.id as application_id,
            a.order_number,
            mno_sim.id as detail_id,
            mno_sim.plan_name,
            mno_sim.provider
        FROM product_applications a
        LEFT JOIN products p ON a.product_id = p.id
        LEFT JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
        WHERE a.seller_id = :seller_id
        AND a.product_type = 'mno-sim'
        ORDER BY a.created_at DESC
        LIMIT 10
    ");
    $stmt3->execute([':seller_id' => $sellerId]);
    $withDetails = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. 전체 쿼리 테스트 (실제 페이지에서 사용하는 쿼리)
    $stmt4 = $pdo->prepare("
        SELECT DISTINCT
            a.id as application_id,
            a.order_number,
            a.product_id,
            a.application_status,
            a.status_changed_at,
            a.created_at,
            c.name,
            c.phone,
            c.email,
            c.additional_info,
            p.id as product_id,
            mno_sim.plan_name,
            mno_sim.provider
        FROM product_applications a
        INNER JOIN application_customers c ON a.id = c.application_id
        INNER JOIN products p ON a.product_id = p.id
        LEFT JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
        WHERE a.seller_id = :seller_id
        AND a.product_type = 'mno-sim'
        ORDER BY a.created_at DESC
        LIMIT 10
    ");
    $stmt4->execute([':seller_id' => $sellerId]);
    $fullQuery = $stmt4->fetchAll(PDO::FETCH_ASSOC);
    
    // 6. 통계 정보
    $stats = [
        'total_applications' => count($applications),
        'mno_sim_applications' => count($mnoSimApplications),
        'joined_with_customers' => count($joinedData),
        'joined_with_details' => count($withDetails),
        'full_query_results' => count($fullQuery)
    ];
    
    echo json_encode([
        'success' => true,
        'seller_id' => $sellerId,
        'stats' => $stats,
        'all_applications' => $applications,
        'mno_sim_applications' => array_values($mnoSimApplications),
        'joined_with_customers' => $joinedData,
        'joined_with_details' => $withDetails,
        'full_query_results' => $fullQuery
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}







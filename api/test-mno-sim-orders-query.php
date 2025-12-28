<?php
/**
 * 통신사유심 주문 쿼리 테스트
 * 실제 페이지에서 사용하는 쿼리를 그대로 실행하여 결과 확인
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
    
    // 페이지와 동일한 조건으로 쿼리 테스트
    $dateRange = $_GET['date_range'] ?? '7';
    $dateFrom = '';
    $dateTo = '';
    if ($dateRange !== 'all') {
        $days = ['7' => 7, '30' => 30, '365' => 365][$dateRange] ?? 7;
        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
        $dateTo = date('Y-m-d');
    }
    
    $whereConditions = [
        'a.seller_id = :seller_id',
        "a.product_type = 'mno-sim'"
    ];
    $params = [':seller_id' => $sellerId];
    
    // 날짜 필터
    if ($dateFrom && $dateFrom !== '') {
        $whereConditions[] = 'DATE(a.created_at) >= :date_from';
        $params[':date_from'] = $dateFrom;
    }
    if ($dateTo && $dateTo !== '') {
        $whereConditions[] = 'DATE(a.created_at) <= :date_to';
        $params[':date_to'] = $dateTo;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // 전체 개수 조회
    $countSql = "
        SELECT COUNT(DISTINCT a.id) as total
        FROM product_applications a
        INNER JOIN application_customers c ON a.id = c.application_id
        WHERE $whereClause
    ";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalOrders = $countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // 주문 목록 조회
    $sql = "
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
        WHERE $whereClause
        ORDER BY a.created_at DESC, a.id DESC
        LIMIT 10
    ";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 날짜 필터 없이도 테스트
    $whereConditionsNoDate = [
        'a.seller_id = :seller_id',
        "a.product_type = 'mno-sim'"
    ];
    $paramsNoDate = [':seller_id' => $sellerId];
    $whereClauseNoDate = implode(' AND ', $whereConditionsNoDate);
    
    $sqlNoDate = "
        SELECT DISTINCT
            a.id as application_id,
            a.order_number,
            a.created_at,
            DATE(a.created_at) as created_date
        FROM product_applications a
        INNER JOIN application_customers c ON a.id = c.application_id
        WHERE $whereClauseNoDate
        ORDER BY a.created_at DESC
        LIMIT 10
    ";
    $stmtNoDate = $pdo->prepare($sqlNoDate);
    $stmtNoDate->execute($paramsNoDate);
    $ordersNoDate = $stmtNoDate->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'seller_id' => $sellerId,
        'date_range' => $dateRange,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'where_clause' => $whereClause,
        'params' => $params,
        'total_orders' => $totalOrders,
        'orders_count' => count($orders),
        'orders' => $orders,
        'orders_no_date_filter' => $ordersNoDate,
        'sql' => $sql
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}





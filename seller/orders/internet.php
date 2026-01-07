<?php
/**
 * 인터넷 주문 관리 페이지
 * 경로: /seller/orders/internet.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = getCurrentUser();

// 판매자 로그인 체크
if (!$currentUser || $currentUser['role'] !== 'seller') {
    header('Location: /MVNO/seller/login.php');
    exit;
}

// 판매자 승인 체크
$approvalStatus = $currentUser['approval_status'] ?? 'pending';
if ($approvalStatus !== 'approved') {
    header('Location: /MVNO/seller/waiting.php');
    exit;
}

// 탈퇴 요청 상태 확인
if (isset($currentUser['withdrawal_requested']) && $currentUser['withdrawal_requested'] === true) {
    header('Location: /MVNO/seller/waiting.php');
    exit;
}

// 필터 파라미터
$status = isset($_GET['status']) && trim($_GET['status']) !== '' ? trim($_GET['status']) : null;
$searchKeyword = trim($_GET['search_keyword'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPageValue = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
$perPage = in_array($perPageValue, [10, 20, 50, 100]) ? $perPageValue : 10;

// DB에서 주문 목록 가져오기
$orders = [];
$totalOrders = 0;
$totalPages = 1;

try {
    $pdo = getDBConnection();
    if ($pdo) {
        $sellerId = (string)$currentUser['user_id'];
        
        // WHERE 조건 구성
        $whereConditions = [
            'a.seller_id = :seller_id',
            "a.product_type = 'internet'",
            "p.product_type = 'internet'"
        ];
        $params = [':seller_id' => $sellerId];
        
        // 진행상황 필터
        if (!empty($status)) {
            // 'received' 필터링 시 빈 문자열, null, 'pending'도 포함 (정규화 로직과 일치)
            if ($status === 'received') {
                $whereConditions[] = "(a.application_status = :status OR a.application_status = '' OR a.application_status IS NULL OR LOWER(TRIM(a.application_status)) = 'pending')";
                $params[':status'] = $status;
            } else {
                $whereConditions[] = 'a.application_status = :status';
                $params[':status'] = $status;
            }
        }
        
        // 통합검색
        if ($searchKeyword && $searchKeyword !== '') {
            $searchConditions = [];
            
            // 고객명 검색
            $searchConditions[] = 'c.name LIKE :search_name';
            $params[':search_name'] = '%' . $searchKeyword . '%';
            
            // 전화번호 검색
            $cleanPhone = preg_replace('/[^0-9]/', '', $searchKeyword);
            if (strlen($cleanPhone) >= 3) {
                $searchConditions[] = "REPLACE(REPLACE(REPLACE(c.phone, '-', ''), ' ', ''), '.', '') LIKE :search_phone";
                $params[':search_phone'] = '%' . $cleanPhone . '%';
            } else {
                $searchConditions[] = 'c.phone LIKE :search_phone_fallback';
                $params[':search_phone_fallback'] = '%' . $searchKeyword . '%';
            }
            
            // 주문번호 검색
            $cleanOrder = preg_replace('/[^0-9]/', '', $searchKeyword);
            error_log("Internet Orders - Search Debug: searchKeyword='$searchKeyword', cleanOrder='$cleanOrder', strlen(cleanOrder)=" . strlen($cleanOrder));
            
            if (strlen($cleanOrder) >= 2) {
                // 하이픈 제거한 숫자 검색
                $searchConditions[] = "REPLACE(a.order_number, '-', '') LIKE :search_order";
                $params[':search_order'] = '%' . $cleanOrder . '%';
                
                // 원본 주문번호 검색 (하이픈 포함)
                $searchConditions[] = 'a.order_number LIKE :search_order_original';
                $params[':search_order_original'] = '%' . $searchKeyword . '%';
                
                error_log("Internet Orders - Search Debug: Added order search conditions. search_order='%$cleanOrder%', search_order_original='%$searchKeyword%'");
                
                // 주문번호 검색 시에는 날짜 검색을 제거 (너무 많은 결과를 반환함)
                // 날짜 검색은 주문번호가 아닌 다른 검색에서만 사용
            } else {
                error_log("Internet Orders - Search Debug: cleanOrder length < 2, skipping order number search");
            }
            
            if (!empty($searchConditions)) {
                $whereConditions[] = '(' . implode(' OR ', $searchConditions) . ')';
                error_log("Internet Orders - Search Debug: Final searchConditions count=" . count($searchConditions));
            } else {
                error_log("Internet Orders - Search Debug: searchConditions is empty!");
            }
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // 전체 개수 조회 (중복 방지를 위해 DISTINCT 사용)
        $countSql = "
            SELECT COUNT(DISTINCT a.id) as total
            FROM product_applications a
            INNER JOIN application_customers c ON a.id = c.application_id
            INNER JOIN products p ON a.product_id = p.id AND p.product_type = 'internet'
            INNER JOIN product_internet_details internet ON p.id = internet.product_id
            WHERE $whereClause
        ";
        
        // 디버깅 로그
        error_log("Internet Orders - Search Keyword: " . ($searchKeyword ?? 'empty'));
        error_log("Internet Orders - WHERE Clause: " . $whereClause);
        error_log("Internet Orders - COUNT SQL: " . $countSql);
        error_log("Internet Orders - Params: " . json_encode($params));
        
        $countStmt = $pdo->prepare($countSql);
        try {
            $countStmt->execute($params);
            $totalOrders = $countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            error_log("Internet Orders - COUNT Query Success. Total Orders: " . $totalOrders);
        } catch (PDOException $e) {
            error_log("Internet Orders - COUNT Query Error: " . $e->getMessage());
            error_log("Internet Orders - COUNT SQL: " . $countSql);
            error_log("Internet Orders - COUNT Params: " . json_encode($params));
            $totalOrders = 0;
        }
        
        $totalPages = $perPage > 0 ? max(1, ceil($totalOrders / $perPage)) : 1;
        
        // 주문 목록 조회 (중복 방지를 위해 DISTINCT 사용)
        $offset = ($page - 1) * $perPage;
        $sql = "
            SELECT DISTINCT
                a.id,
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
                internet.registration_place,
                internet.service_type,
                internet.speed_option,
                internet.monthly_fee,
                internet.cash_payment_names,
                internet.cash_payment_prices,
                internet.gift_card_names,
                internet.gift_card_prices,
                internet.equipment_names,
                internet.equipment_prices,
                internet.installation_names,
                internet.installation_prices,
                p.point_benefit_description,
                (SELECT ABS(delta) FROM user_point_ledger 
                 WHERE user_id = c.user_id 
                   AND item_id = a.product_id 
                   AND type = 'internet' 
                   AND delta < 0 
                   AND created_at <= a.created_at
                 ORDER BY created_at DESC LIMIT 1) as used_point
            FROM product_applications a
            INNER JOIN application_customers c ON a.id = c.application_id
            INNER JOIN products p ON a.product_id = p.id AND p.product_type = 'internet'
            INNER JOIN product_internet_details internet ON p.id = internet.product_id
            WHERE $whereClause
            ORDER BY a.created_at DESC, a.id DESC
            LIMIT :limit OFFSET :offset
        ";
        
        error_log("Internet Orders - SELECT SQL: " . $sql);
        error_log("Internet Orders - SELECT Params: " . json_encode($params));
        error_log("Internet Orders - Limit: $perPage, Offset: $offset");
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        try {
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Internet Orders - SELECT Query Success. Fetched " . count($orders) . " orders");
            
            // 검색 결과의 주문번호 확인
            if (!empty($orders) && !empty($searchKeyword)) {
                $orderNumbers = array_column($orders, 'order_number');
                error_log("Internet Orders - Search Result Order Numbers (first 10): " . implode(', ', array_slice($orderNumbers, 0, 10)));
                if (count($orderNumbers) > 10) {
                    error_log("Internet Orders - Total order numbers in result: " . count($orderNumbers));
                }
            }
        } catch (PDOException $e) {
            error_log("Internet Orders - SELECT Query Error: " . $e->getMessage());
            error_log("Internet Orders - SELECT SQL: " . $sql);
            error_log("Internet Orders - SELECT Params: " . json_encode($params));
            $orders = [];
        }
        
        // 주문 데이터 정규화
        foreach ($orders as &$order) {
            $orderStatus = strtolower(trim($order['application_status'] ?? ''));
            $order['application_status'] = in_array($orderStatus, ['pending', '']) ? 'received' : ($orderStatus ?: 'received');
            
            $order['additional_info'] = json_decode($order['additional_info'] ?? '{}', true) ?: [];
            
            // 신청 시점의 상품 정보를 우선 사용 (product_snapshot)
            // 사용자가 신청했던 당시의 값이 나중에 변경되어도 유지되어야 함
            $snapshot = $order['additional_info']['product_snapshot'] ?? [];
            if ($snapshot && !empty($snapshot)) {
                // product_snapshot이 있으면 신청 시점 정보로 덮어쓰기
                $exclude = ['id', 'product_id', 'seller_id', 'order_number', 'application_id', 'created_at'];
                foreach ($snapshot as $key => $value) {
                    if (!in_array($key, $exclude) && $value !== null && $value !== '') {
                        $order[$key] = $value;
                    }
                }
            }
            // product_snapshot이 없으면 현재 테이블 값 사용 (fallback)
            
            $jsonFields = ['cash_payment_names', 'cash_payment_prices', 'gift_card_names', 'gift_card_prices',
                          'equipment_names', 'equipment_prices', 'installation_names', 'installation_prices'];
            foreach ($jsonFields as $field) {
                $order[$field] = is_string($order[$field] ?? null) 
                    ? (json_decode($order[$field], true) ?: []) 
                    : (is_array($order[$field] ?? null) ? $order[$field] : []);
            }
        }
        unset($order);
    }
} catch (PDOException $e) {
    error_log("Error fetching orders: " . $e->getMessage());
}

// 상태별 한글명
$statusLabels = [
    'received' => '접수',
    'activating' => '개통중',
    'on_hold' => '보류',
    'cancelled' => '취소',
    'activation_completed' => '개통완료',
    'installation_completed' => '설치완료',
    'pending' => '접수',
    'processing' => '개통중',
    'completed' => '설치완료',
    'rejected' => '보류',
    'closed' => '종료',
    'terminated' => '종료'
];

// 가입형태 한글명
$subscriptionTypeLabels = [
    'new' => '신규가입',
    'port' => '번호이동',
    'change' => '기기변경'
];

$pageStyles = '
    .orders-container {
        max-width: 95%;
        margin: 0 auto;
        width: 100%;
    }
    
    .orders-header {
        margin-bottom: 24px;
    }
    
    .orders-header h1 {
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 8px;
    }
    
    .orders-filters {
        background: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }
    
    .filter-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }
    
    .filter-row:last-child {
        margin-bottom: 0;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
    }
    
    .filter-label {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
    }
    
    .filter-input,
    .filter-select {
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .filter-input:focus,
    .filter-select:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .filter-actions {
        display: flex;
        gap: 8px;
        margin-top: 16px;
    }
    
    .btn-filter {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }
    
    .btn-filter-primary {
        background: #10b981;
        color: white;
    }
    
    .btn-filter-primary:hover {
        background: #059669;
    }
    
    .btn-filter-secondary {
        background: #f3f4f6;
        color: #374151;
    }
    
    .btn-filter-secondary:hover {
        background: #e5e7eb;
    }
    
    .orders-table-container {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        overflow-x: auto;
    }
    
    .orders-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .orders-table th {
        background: #f9fafb;
        padding: 12px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        border-bottom: 2px solid #e5e7eb;
        white-space: nowrap;
    }
    
    .orders-table td {
        padding: 16px 12px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 14px;
        color: #1f2937;
        white-space: nowrap;
        min-width: fit-content;
    }
    
    .orders-table tr:hover {
        background: #f9fafb;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }
    
    .status-cell-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }
    
    .status-edit-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 6px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        background: white;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.2s;
        line-height: 1;
    }
    
    .status-edit-btn:hover {
        background: #f3f4f6;
        border-color: #10b981;
        color: #10b981;
    }
    
    .status-edit-btn:active {
        transform: scale(0.95);
    }
    
    .status-modal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        align-items: center;
        justify-content: center;
    }
    
    .status-modal-content {
        background-color: white;
        padding: 24px;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        width: 90%;
        max-width: 400px;
        position: relative;
    }
    
    .status-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .status-modal-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: #111827;
    }
    
    .status-modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #6b7280;
        padding: 0;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: all 0.2s;
    }
    
    .status-modal-close:hover {
        background: #f3f4f6;
        color: #111827;
    }
    
    .status-modal-body {
        margin-bottom: 20px;
    }
    
    .status-modal-body label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
    }
    
    .status-modal-select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        background: white;
        color: #374151;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .status-modal-select:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .status-modal-actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }
    
    .status-modal-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .status-modal-btn-cancel {
        background: #f3f4f6;
        color: #374151;
    }
    
    .status-modal-btn-cancel:hover {
        background: #e5e7eb;
    }
    
    .status-modal-btn-save {
        background: #10b981;
        color: white;
    }
    
    .status-modal-btn-save:hover {
        background: #059669;
    }
    
    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }
    
    .status-processing {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .status-completed {
        background: #d1fae5;
        color: #065f46;
    }
    
    .status-cancelled {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .status-rejected {
        background: #f3f4f6;
        color: #374151;
    }
    
    .status-received {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .status-activating {
        background: #fef3c7;
        color: #92400e;
    }
    
    .status-on_hold {
        background: #f3f4f6;
        color: #374151;
    }
    
    .status-activation_completed {
        background: #d1fae5;
        color: #065f46;
    }
    
    .status-installation_completed {
        background: #d1fae5;
        color: #065f46;
    }
    
    .status-closed {
        background: #f3f4f6;
        color: #374151;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        margin-top: 24px;
    }
    
    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 14px;
        color: #374151;
        border: 1px solid #d1d5db;
        background: white;
    }
    
    .pagination a:hover {
        background: #f3f4f6;
        border-color: #10b981;
    }
    
    .pagination .current {
        background: #10b981;
        color: white;
        border-color: #10b981;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }
    
    .empty-state-icon {
        font-size: 48px;
        margin-bottom: 16px;
    }
    
    .empty-state-text {
        font-size: 16px;
        margin-bottom: 8px;
    }
    
    .empty-state-subtext {
        font-size: 14px;
        color: #9ca3af;
    }
    
    .product-name-link {
        color: #10b981;
        cursor: pointer;
        text-decoration: underline;
        font-weight: 500;
    }
    
    .product-name-link:hover {
        color: #059669;
    }
    
    .product-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        overflow: auto;
    }
    
    .product-modal-content {
        background-color: #fff;
        margin: 5% auto;
        padding: 0;
        border-radius: 12px;
        width: 90%;
        max-width: 800px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        max-height: 85vh;
        overflow-y: auto;
    }
    
    .product-modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f9fafb;
        border-radius: 12px 12px 0 0;
    }
    
    .product-modal-header h2 {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
    }
    
    .product-modal-close {
        color: #6b7280;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        line-height: 1;
    }
    
    .product-modal-close:hover {
        color: #1f2937;
    }
    
    .product-modal-body {
        padding: 24px;
    }
    
    .product-info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    
    .product-info-table th {
        background: #f9fafb;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #374151;
        border: 1px solid #e5e7eb;
        width: 30%;
    }
    
    .product-info-table td {
        padding: 12px;
        border: 1px solid #e5e7eb;
        color: #1f2937;
    }
    
    .product-info-table tr:nth-child(even) {
        background: #f9fafb;
    }
    
    .bulk-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        background: #f9fafb;
        border-radius: 8px;
        margin-bottom: 16px;
        border: 1px solid #e5e7eb;
    }
    
    .bulk-actions-info {
        font-size: 14px;
        color: #374151;
        font-weight: 500;
    }
    
    .bulk-actions-select {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        background: white;
        color: #374151;
        cursor: pointer;
    }
    
    .bulk-actions-select:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .bulk-actions-btn {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        background: #10b981;
        color: white;
        transition: all 0.2s;
    }
    
    .bulk-actions-btn:hover {
        background: #059669;
    }
    
    .bulk-actions-btn:disabled {
        background: #d1d5db;
        color: #9ca3af;
        cursor: not-allowed;
    }
    
    .order-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #10b981;
    }
    
    .orders-table th.checkbox-column {
        width: 40px;
        text-align: center;
    }
    
    .orders-table td.checkbox-column {
        text-align: center;
        padding: 16px 8px;
    }
';

include __DIR__ . '/../includes/seller-header.php';
?>

<div class="orders-container">
    <div class="orders-header">
        <h1>인터넷 주문 관리</h1>
        <p>인터넷 상품 주문 내역을 확인하고 관리하세요</p>
    </div>
    
    <!-- 필터 -->
    <div class="orders-filters">
        <form method="GET" action="">
            <div class="filter-row">
                <div class="filter-group">
                    <label class="filter-label">진행상황</label>
                    <select name="status" class="filter-select">
                        <option value="" <?php echo (empty($status) || $status === null) ? 'selected' : ''; ?>>전체</option>
                        <option value="received" <?php echo ($status === 'received') ? 'selected' : ''; ?>>접수</option>
                        <option value="on_hold" <?php echo ($status === 'on_hold') ? 'selected' : ''; ?>>보류</option>
                        <option value="cancelled" <?php echo ($status === 'cancelled') ? 'selected' : ''; ?>>취소</option>
                        <option value="installation_completed" <?php echo ($status === 'installation_completed') ? 'selected' : ''; ?>>설치완료</option>
                        <option value="closed" <?php echo ($status === 'closed') ? 'selected' : ''; ?>>종료</option>
                    </select>
                </div>
                
                <div class="filter-group" style="flex: 2;">
                    <label class="filter-label">통합검색</label>
                    <input type="text" name="search_keyword" class="filter-input" placeholder="주문번호, 고객명, 전화번호 검색" value="<?php echo htmlspecialchars($searchKeyword); ?>" onkeypress="if(event.key === 'Enter') { event.preventDefault(); this.form.submit(); }">
                </div>
                
                <div class="filter-actions" style="display: flex; align-items: flex-end; gap: 8px; margin-top: 0;">
                    <button type="submit" class="btn-filter btn-filter-primary">검색</button>
                    <a href="?" class="btn-filter btn-filter-secondary">초기화</a>
                </div>
                
                <div class="filter-group" style="margin-left: auto; text-align: right;">
                    <label class="filter-label">페이지당 표시</label>
                    <select name="per_page" class="filter-select" style="min-width: 100px;" onchange="this.form.submit()">
                        <option value="10" <?php echo $perPage === 10 ? 'selected' : ''; ?>>10개</option>
                        <option value="20" <?php echo $perPage === 20 ? 'selected' : ''; ?>>20개</option>
                        <option value="50" <?php echo $perPage === 50 ? 'selected' : ''; ?>>50개</option>
                        <option value="100" <?php echo $perPage === 100 ? 'selected' : ''; ?>>100개</option>
                    </select>
                </div>
            </div>
        </form>
    </div>
    
    <!-- 주문 목록 -->
    <div class="orders-table-container">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <div class="empty-state-text">주문 내역이 없습니다</div>
                <div class="empty-state-subtext">고객이 주문하면 여기에 표시됩니다</div>
            </div>
        <?php else: ?>
            <!-- 일괄 변경 UI -->
            <div class="bulk-actions" id="bulkActions" style="display: none;">
                <span class="bulk-actions-info">
                    <span id="selectedCount">0</span>개 선택됨
                </span>
                <select id="bulkStatusSelect" class="bulk-actions-select">
                    <option value="">진행상황 선택</option>
                    <option value="received">접수</option>
                    <option value="on_hold">보류</option>
                    <option value="cancelled">취소</option>
                    <option value="installation_completed">설치완료</option>
                    <option value="closed">종료</option>
                </select>
                <button type="button" class="bulk-actions-btn" onclick="bulkUpdateStatus()" id="bulkUpdateBtn" disabled>일괄 변경</button>
            </div>
            
            <table class="orders-table">
                <thead>
                    <tr>
                        <th class="checkbox-column">
                            <input type="checkbox" id="selectAll" class="order-checkbox" onchange="toggleSelectAll(this)">
                        </th>
                        <th>순번</th>
                        <th>주문번호</th>
                        <th>신청 인터넷 회선</th>
                        <th>결합여부</th>
                        <th>속도</th>
                        <th>기존 인터넷 회선</th>
                        <th>고객명</th>
                        <th>전화번호</th>
                        <th>이메일</th>
                        <th>포인트</th>
                        <th>혜택내용</th>
                        <th>상태변경시각</th>
                        <th>진행상황</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $orderIndex = $totalOrders - (($page - 1) * $perPage);
                    foreach ($orders as $order): 
                    ?>
                        <tr>
                            <td class="checkbox-column">
                                <input type="checkbox" class="order-checkbox order-checkbox-item" 
                                       value="<?php echo $order['id']; ?>" 
                                       onchange="updateBulkActions()">
                            </td>
                            <td><?php echo $orderIndex--; ?></td>
                            <td><?php echo htmlspecialchars($order['order_number'] ?? '-'); ?></td>
                            <td>
                                <span class="product-name-link" onclick="showProductInfo(<?php echo htmlspecialchars(json_encode($order)); ?>, 'internet')">
                                    <?php 
                                    $place = htmlspecialchars($order['registration_place'] ?? '');
                                    echo $place ?: '-';
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $serviceType = $order['service_type'] ?? '인터넷';
                                $serviceTypeDisplay = $serviceType;
                                if ($serviceType === '인터넷+TV') {
                                    $serviceTypeDisplay = '인터넷 + TV 결합';
                                } elseif ($serviceType === '인터넷+TV+핸드폰') {
                                    $serviceTypeDisplay = '인터넷 + TV + 핸드폰 결합';
                                }
                                echo htmlspecialchars($serviceTypeDisplay);
                                ?>
                            </td>
                            <td>
                                <?php 
                                $speed = htmlspecialchars($order['speed_option'] ?? '');
                                echo $speed ?: '-';
                                ?>
                            </td>
                            <td>
                                <?php 
                                // 기존 인터넷 회선 정보 가져오기
                                $existingCompany = $order['additional_info']['currentCompany'] ?? 
                                                   $order['additional_info']['existing_company'] ?? 
                                                   $order['additional_info']['existingCompany'] ?? '';
                                echo $existingCompany ? htmlspecialchars($existingCompany) : '-';
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($order['name']); ?></td>
                            <td><?php echo htmlspecialchars($order['phone']); ?></td>
                            <td><?php echo htmlspecialchars($order['email'] ?? '-'); ?></td>
                            <td style="text-align: center;">
                                <?php 
                                $usedPoint = isset($order['used_point']) ? intval($order['used_point']) : 0;
                                if ($usedPoint > 0): 
                                ?>
                                    <span style="color: #6366f1; font-weight: 600;"><?php echo number_format($usedPoint); ?>P</span>
                                <?php else: ?>
                                    <span style="color: #9ca3af;">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php 
                                $benefitDesc = $order['point_benefit_description'] ?? '';
                                if (!empty($benefitDesc)): 
                                ?>
                                    <span style="color: #10b981; font-weight: 500; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block;" title="<?php echo htmlspecialchars($benefitDesc); ?>">
                                        <?php echo htmlspecialchars($benefitDesc); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #9ca3af;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $statusChangedAt = $order['status_changed_at'] ?? null;
                                if ($statusChangedAt) {
                                    echo date('Y-m-d', strtotime($statusChangedAt));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="status-cell-wrapper">
                                    <span class="status-badge status-<?php echo $order['application_status']; ?>">
                                        <?php echo $statusLabels[$order['application_status']] ?? $order['application_status']; ?>
                                    </span>
                                    <button type="button" class="status-edit-btn" onclick="openStatusEditModal(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['application_status'], ENT_QUOTES); ?>')" title="상태 변경">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- 페이지네이션 -->
            <?php if ($totalPages > 1): 
                $paginationParams = array_filter($_GET, fn($v, $k) => $k !== 'status' || $v !== '', ARRAY_FILTER_USE_BOTH);
                // 페이지 그룹 계산 (10개씩 그룹화)
                $pageGroupSize = 10;
                $currentGroup = ceil($page / $pageGroupSize);
                $startPage = ($currentGroup - 1) * $pageGroupSize + 1;
                $endPage = min($currentGroup * $pageGroupSize, $totalPages);
                $prevGroupLastPage = ($currentGroup - 1) * $pageGroupSize;
                $nextGroupFirstPage = $currentGroup * $pageGroupSize + 1;
            ?>
                <div class="pagination">
                    <?php if ($currentGroup > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($paginationParams, ['page' => $prevGroupLastPage])); ?>">이전</a>
                    <?php endif; ?>
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?<?php echo http_build_query(array_merge($paginationParams, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($nextGroupFirstPage <= $totalPages): ?>
                        <a href="?<?php echo http_build_query(array_merge($paginationParams, ['page' => $nextGroupFirstPage])); ?>">다음</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>

// 상품 정보 모달 표시
function showProductInfo(order, productType) {
    const modal = document.getElementById('productInfoModal');
    const modalBody = document.getElementById('productInfoModalBody');
    
    let html = '';
    
    if (productType === 'internet') {
        // JSON 필드 파싱
        const parseJsonField = (field) => {
            if (!field) return [];
            if (typeof field === 'string') {
                try {
                    return JSON.parse(field);
                } catch (e) {
                    return [];
                }
            }
            return Array.isArray(field) ? field : [];
        };
        
        // 필드명 정리 함수 (인코딩 오류 및 오타 수정)
        const cleanFieldName = (name) => {
            if (!name || typeof name !== 'string') return name;
            
            // 공백 제거
            name = name.trim();
            
            // 일반적인 오타 및 인코딩 오류 수정
            const corrections = [
                // 와이파이공유기 관련 오타
                { pattern: /와이파이공유기\s*[ㅇㄹㅁㄴㅂㅅ]+/g, replacement: '와이파이공유기' },
                { pattern: /와이파이공유기\s*[ㅇㄹ]/g, replacement: '와이파이공유기' },
                // 설치비 관련 오타
                { pattern: /스?\s*설[ㅊㅈ]?이비/g, replacement: '설치비' },
                { pattern: /설[ㅊㅈ]?이비/g, replacement: '설치비' },
                // 연속된 공백을 하나로
                { pattern: /\s+/g, replacement: ' ' },
            ];
            
            // 패턴 기반 수정
            corrections.forEach(({ pattern, replacement }) => {
                name = name.replace(pattern, replacement);
            });
            
            // 특수문자나 이상한 문자 제거 (한글, 숫자, 영문, 공백만 허용)
            // 단, 의미있는 한글 자음은 보존 (예: "ㅇㄹ" 같은 의미없는 자음만 제거)
            name = name.replace(/[^\uAC00-\uD7A3a-zA-Z0-9\s]/g, '');
            
            // 단어 끝에 의미없는 자음이 붙은 경우 제거 (예: "와이파이공유기 ㅇㄹ" -> "와이파이공유기")
            name = name.replace(/\s+[ㅇㄹㅁㄴㅂㅅㅇㄹ]+$/g, '');
            
            // 앞뒤 공백 제거
            name = name.trim();
            
            return name;
        };
        
        // 중복 제거 및 유효성 검사 함수
        const cleanNamePricePairs = (names, prices) => {
            const seen = new Set();
            const result = [];
            
            for (let i = 0; i < names.length; i++) {
                const name = cleanFieldName(names[i]);
                const price = prices[i] || '';
                
                // 빈 이름 제거
                if (!name || name.trim() === '' || name === '-') continue;
                
                // 중복 제거 (이름 기준)
                const key = name.toLowerCase().trim();
                if (seen.has(key)) continue;
                seen.add(key);
                
                result.push({ name: name, price: price });
            }
            
            return result;
        };
        
        // 월 요금제 검증 및 정리
        const validateMonthlyFee = (fee) => {
            if (!fee) return '-';
            return formatPrice(fee);
        };
        
        const cashPairs = cleanNamePricePairs(
            parseJsonField(order.cash_payment_names),
            parseJsonField(order.cash_payment_prices)
        );
        const giftPairs = cleanNamePricePairs(
            parseJsonField(order.gift_card_names),
            parseJsonField(order.gift_card_prices)
        );
        const equipPairs = cleanNamePricePairs(
            parseJsonField(order.equipment_names),
            parseJsonField(order.equipment_prices)
        );
        const installPairs = cleanNamePricePairs(
            parseJsonField(order.installation_names),
            parseJsonField(order.installation_prices)
        );
        
        const serviceType = order.service_type || '인터넷';
        let serviceTypeDisplay = serviceType;
        if (serviceType === '인터넷+TV') {
            serviceTypeDisplay = '인터넷 + TV 결합';
        } else if (serviceType === '인터넷+TV+핸드폰') {
            serviceTypeDisplay = '인터넷 + TV + 핸드폰 결합';
        }
        
        html = `
            <table class="product-info-table">
                <tr>
                    <th>인터넷 가입처</th>
                    <td>${order.registration_place || '-'}</td>
                </tr>
                <tr>
                    <th>결합여부</th>
                    <td>${serviceTypeDisplay}</td>
                </tr>
                <tr>
                    <th>가입 속도</th>
                    <td>${order.speed_option || '-'}</td>
                </tr>
                <tr>
                    <th>월 요금제</th>
                    <td>${validateMonthlyFee(order.monthly_fee)}</td>
                </tr>`;
        
        // 포인트 사용 정보
        if (order.used_point && parseInt(order.used_point) > 0) {
            const usedPoint = parseInt(order.used_point);
            const formattedPoint = usedPoint.toLocaleString('ko-KR');
            html += `
                <tr>
                    <th>포인트 사용</th>
                    <td style="color: #6366f1; font-weight: 600;">${formattedPoint}P</td>
                </tr>`;
        }
        
        // 할인 혜택 내용
        if (order.point_benefit_description) {
            const escapeHtml = (text) => {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            };
            html += `
                <tr>
                    <th>혜택내용</th>
                    <td style="color: #10b981; font-weight: 500;">${escapeHtml(order.point_benefit_description)}</td>
                </tr>`;
        }
        
        html += `
            </table>
        `;
        
        // 현금지급 정보
        if (cashPairs.length > 0) {
            html += `<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">현금지급</h3>`;
            html += `<table class="product-info-table">`;
            cashPairs.forEach((item) => {
                html += `
                    <tr>
                        <th>${item.name || '-'}</th>
                        <td>${formatPrice(item.price)}</td>
                    </tr>
                `;
            });
            html += `</table>`;
        }
        
        // 상품권 지급 정보
        if (giftPairs.length > 0) {
            html += `<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">상품권 지급</h3>`;
            html += `<table class="product-info-table">`;
            giftPairs.forEach((item) => {
                html += `
                    <tr>
                        <th>${item.name || '-'}</th>
                        <td>${formatPrice(item.price)}</td>
                    </tr>
                `;
            });
            html += `</table>`;
        }
        
        // 장비 제공 정보
        if (equipPairs.length > 0) {
            html += `<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">장비 제공</h3>`;
            html += `<table class="product-info-table">`;
            equipPairs.forEach((item) => {
                html += `
                    <tr>
                        <th>${item.name || '-'}</th>
                        <td>${formatPrice(item.price)}</td>
                    </tr>
                `;
            });
            html += `</table>`;
        }
        
        // 설치 및 기타 서비스 정보
        if (installPairs.length > 0) {
            html += `<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">설치 및 기타 서비스</h3>`;
            html += `<table class="product-info-table">`;
            installPairs.forEach((item) => {
                html += `
                    <tr>
                        <th>${item.name || '-'}</th>
                        <td>${formatPrice(item.price)}</td>
                    </tr>
                `;
            });
            html += `</table>`;
        }
        
        // 기존 인터넷 회선 정보
        const additionalInfo = order.additional_info || {};
        const existingCompany = additionalInfo.currentCompany || additionalInfo.existing_company || additionalInfo.existingCompany || '';
        if (existingCompany) {
            html += `<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">기존 인터넷 회선</h3>`;
            html += `<table class="product-info-table">`;
            html += `
                <tr>
                    <th>기존 인터넷 회선</th>
                    <td>${existingCompany}</td>
                </tr>
            `;
            html += `</table>`;
        }
    }
    
    modalBody.innerHTML = html;
    modal.style.display = 'block';
}

// 숫자 포맷팅 함수
function number_format(num) {
    if (!num) return '0';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// 가격 포맷팅 함수 (단위 포함 처리)
function formatPrice(price) {
    if (!price) return '-';
    
    // 문자열로 변환
    const priceStr = String(price);
    
    // 이미 단위가 포함되어 있는지 확인 (한글이 포함된 경우)
    if (/[가-힣]/.test(priceStr)) {
        // 숫자 부분만 추출하여 포맷팅 (소수점 제거)
        const numericValue = priceStr.replace(/[^0-9]/g, '');
        if (!numericValue) return priceStr; // 숫자가 없으면 원본 반환
        
        const formatted = number_format(parseInt(numericValue));
        // 원본에서 단위 추출 (한글 부분, 소수점과 쉼표 제거)
        const unit = priceStr.replace(/[0-9,.]/g, '').trim();
        return formatted + (unit || '원');
    }
    
    // 숫자만 있는 경우 (소수점 포함 가능)
    const numericValue = priceStr.replace(/[^0-9]/g, '');
    if (!numericValue) return '-';
    
    // 정수로 변환하여 소수점 제거
    return number_format(parseInt(numericValue)) + '원';
}

// 모달 닫기
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('productInfoModal');
    const closeBtn = document.querySelector('.product-modal-close');
    
    if (closeBtn) {
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        };
    }
    
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };
});

// 상태 변경 모달 열기
function openStatusEditModal(applicationId, currentStatus) {
    const modal = document.getElementById('statusEditModal');
    const select = document.getElementById('statusEditSelect');
    
    if (!modal || !select) return;
    
    // 현재 상태 정규화 및 기본값 설정
    let status = 'received'; // 기본값
    if (currentStatus) {
        const normalizedStatus = String(currentStatus).trim().toLowerCase();
        if (normalizedStatus !== '') {
            // 'pending' 값도 'received'로 매핑
            status = (normalizedStatus === 'pending') ? 'received' : normalizedStatus;
        }
    }
    
    // 셀렉트박스에 값 설정 (값이 유효한 옵션인지 확인)
    const validStatuses = ['received', 'on_hold', 'cancelled', 'installation_completed', 'closed'];
    if (validStatuses.includes(status)) {
        select.value = status;
    } else {
        // 유효하지 않은 값이면 기본값 'received' 사용
        select.value = 'received';
    }
    
    select.setAttribute('data-application-id', applicationId);
    
    // 모달 표시
    modal.style.display = 'flex';
}

// 상태 변경 모달 닫기
function closeStatusEditModal() {
    const modal = document.getElementById('statusEditModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// 주문 상태 변경 함수
function updateOrderStatus() {
    const select = document.getElementById('statusEditSelect');
    if (!select) return;
    
    const applicationId = select.getAttribute('data-application-id');
    const newStatus = select.value;
    
    if (!applicationId || !newStatus) {
        return;
    }
    
    // API 호출
    fetch('/MVNO/api/update-order-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `application_id=${applicationId}&status=${encodeURIComponent(newStatus)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeStatusEditModal();
            if (typeof showAlert === 'function') {
                showAlert('상태가 변경되었습니다.', '완료');
            } else {
                alert('상태가 변경되었습니다.');
            }
            // 페이지 새로고침
            location.reload();
        } else {
            if (typeof showAlert === 'function') {
                showAlert(data.message || '상태 변경에 실패했습니다.', '오류', true);
            } else {
                alert(data.message || '상태 변경에 실패했습니다.');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showAlert === 'function') {
            showAlert('상태 변경 중 오류가 발생했습니다.', '오류', true);
        } else {
            alert('상태 변경 중 오류가 발생했습니다.');
        }
    });
}

// 모달 닫기 이벤트
document.addEventListener('DOMContentLoaded', function() {
    const statusModal = document.getElementById('statusEditModal');
    const statusModalClose = document.querySelector('.status-modal-close');
    
    if (statusModalClose) {
        statusModalClose.addEventListener('click', closeStatusEditModal);
    }
    
    if (statusModal) {
        statusModal.addEventListener('click', function(event) {
            if (event.target === statusModal) {
                closeStatusEditModal();
            }
        });
    }
    
    // 일괄 변경 셀렉트박스 변경 이벤트
    const bulkStatusSelect = document.getElementById('bulkStatusSelect');
    if (bulkStatusSelect) {
        bulkStatusSelect.addEventListener('change', function() {
            const bulkUpdateBtn = document.getElementById('bulkUpdateBtn');
            if (bulkUpdateBtn) {
                bulkUpdateBtn.disabled = !this.value || getSelectedOrderIds().length === 0;
            }
        });
    }
});

// 전체 선택/해제
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.order-checkbox-item');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateBulkActions();
}

// 선택된 주문 ID 목록 가져오기
function getSelectedOrderIds() {
    const checkboxes = document.querySelectorAll('.order-checkbox-item:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

// 일괄 변경 UI 업데이트
function updateBulkActions() {
    const selectedIds = getSelectedOrderIds();
    const selectedCount = selectedIds.length;
    const bulkActions = document.getElementById('bulkActions');
    const selectedCountSpan = document.getElementById('selectedCount');
    const bulkUpdateBtn = document.getElementById('bulkUpdateBtn');
    const bulkStatusSelect = document.getElementById('bulkStatusSelect');
    const selectAllCheckbox = document.getElementById('selectAll');
    
    if (selectedCountSpan) {
        selectedCountSpan.textContent = selectedCount;
    }
    
    if (bulkActions) {
        bulkActions.style.display = selectedCount > 0 ? 'flex' : 'none';
    }
    
    if (bulkUpdateBtn) {
        bulkUpdateBtn.disabled = selectedCount === 0 || !bulkStatusSelect || !bulkStatusSelect.value;
    }
    
    // 전체 선택 체크박스 상태 업데이트
    if (selectAllCheckbox) {
        const allCheckboxes = document.querySelectorAll('.order-checkbox-item');
        const checkedCount = document.querySelectorAll('.order-checkbox-item:checked').length;
        selectAllCheckbox.checked = allCheckboxes.length > 0 && checkedCount === allCheckboxes.length;
        selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < allCheckboxes.length;
    }
}

// 일괄 상태 변경
function bulkUpdateStatus() {
    const selectedIds = getSelectedOrderIds();
    const statusSelect = document.getElementById('bulkStatusSelect');
    
    if (selectedIds.length === 0) {
        alert('선택된 주문이 없습니다.');
        return;
    }
    
    if (!statusSelect || !statusSelect.value) {
        alert('변경할 진행상황을 선택해주세요.');
        return;
    }
    
    const newStatus = statusSelect.value;
    const statusLabels = {
        'received': '접수',
        'on_hold': '보류',
        'cancelled': '취소',
        'installation_completed': '설치완료',
        'closed': '종료'
    };
    
    if (!confirm(`선택한 ${selectedIds.length}개의 주문을 "${statusLabels[newStatus]}"로 변경하시겠습니까?`)) {
        return;
    }
    
    // 일괄 변경 API 호출
    const promises = selectedIds.map(id => {
        return fetch('/MVNO/api/update-order-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `application_id=${id}&status=${encodeURIComponent(newStatus)}`
        })
        .then(response => response.json())
        .then(data => ({ id, success: data.success, message: data.message }));
    });
    
    // 모든 요청 완료 대기
    Promise.all(promises)
        .then(results => {
            const successCount = results.filter(r => r.success).length;
            const failCount = results.length - successCount;
            
            if (failCount === 0) {
                if (typeof showAlert === 'function') {
                    showAlert(`${successCount}개의 주문 상태가 변경되었습니다.`, '완료');
                } else {
                    alert(`${successCount}개의 주문 상태가 변경되었습니다.`);
                }
                // 페이지 새로고침
                setTimeout(() => {
                    location.reload();
                }, 500);
            } else {
                if (typeof showAlert === 'function') {
                    showAlert(`${successCount}개 성공, ${failCount}개 실패했습니다.`, '알림', true);
                } else {
                    alert(`${successCount}개 성공, ${failCount}개 실패했습니다.`);
                }
                // 페이지 새로고침
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        })
        .catch(error => {
            console.error('Bulk update error:', error);
            if (typeof showAlert === 'function') {
                showAlert('일괄 변경 중 오류가 발생했습니다: ' + error.message, '오류', true);
            } else {
                alert('일괄 변경 중 오류가 발생했습니다: ' + error.message);
            }
        });
}
</script>

<!-- 상품 정보 모달 -->
<div id="productInfoModal" class="product-modal">
    <div class="product-modal-content">
        <div class="product-modal-header">
            <h2>상품 정보</h2>
            <span class="product-modal-close">&times;</span>
        </div>
        <div class="product-modal-body" id="productInfoModalBody">
        </div>
    </div>
</div>

<!-- 상태 변경 모달 -->
<div id="statusEditModal" class="status-modal">
    <div class="status-modal-content">
        <div class="status-modal-header">
            <h3>진행상황 변경</h3>
            <button type="button" class="status-modal-close">&times;</button>
        </div>
        <div class="status-modal-body">
            <label for="statusEditSelect">진행상황 선택</label>
            <select id="statusEditSelect" class="status-modal-select">
                <option value="received" selected>접수</option>
                <option value="on_hold">보류</option>
                <option value="cancelled">취소</option>
                <option value="installation_completed">설치완료</option>
                <option value="closed">종료</option>
            </select>
        </div>
        <div class="status-modal-actions">
            <button type="button" class="status-modal-btn status-modal-btn-cancel" onclick="closeStatusEditModal()">취소</button>
            <button type="button" class="status-modal-btn status-modal-btn-save" onclick="updateOrderStatus()">변경</button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>


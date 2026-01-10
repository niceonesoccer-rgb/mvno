<?php
/**
 * 알뜰폰 상품 목록 페이지 (관리자)
 * 경로: /admin/products/mvno-list.php
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../../includes/data/path-config.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/product-functions.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';

// 검색 필터 파라미터 처리
$status = $_GET['status'] ?? null;
if ($status === '') $status = null;

$provider = $_GET['provider'] ?? '';
$search_query = $_GET['search_query'] ?? ''; // 통합 검색 필드
$contract_period = $_GET['contract_period'] ?? '';
$contract_period_days_min = $_GET['contract_period_days_min'] ?? '';
$contract_period_days_max = $_GET['contract_period_days_max'] ?? '';
$price_after_type = $_GET['price_after_type'] ?? '';
$price_after_min = $_GET['price_after_min'] ?? '';
$price_after_max = $_GET['price_after_max'] ?? '';
$service_type = $_GET['service_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = intval($_GET['per_page'] ?? 20);
if (!in_array($perPage, [10, 20, 50, 100])) {
    $perPage = 20;
}

// 상품 목록 조회
$products = [];
$totalProducts = 0;
$totalPages = 1;
$pdo = null;

try {
    $pdo = getDBConnection();
    if ($pdo) {
        // WHERE 조건 구성
        $whereConditions = ["p.product_type = 'mvno'"];
        $params = [];
        
        // 상태 필터
        if ($status && $status !== '') {
            $whereConditions[] = 'p.status = :status';
            $params[':status'] = $status;
        } else {
            $whereConditions[] = "p.status != 'deleted'";
        }
        
        // 판매자 필터
        if (isset($_GET['seller_id']) && $_GET['seller_id'] !== '') {
            $seller_id = $_GET['seller_id'];
            $whereConditions[] = 'p.seller_id = :seller_id';
            $params[':seller_id'] = $seller_id;
        }
        
        // 통신사 필터
        if ($provider && $provider !== '') {
            $whereConditions[] = 'mvno.provider = :provider';
            $params[':provider'] = $provider;
        }
        
        // 요금제명 필터 (통합 검색으로 대체 가능하지만 유지)
        if (isset($_GET['plan_name']) && $_GET['plan_name'] !== '') {
            $plan_name = $_GET['plan_name'];
            $whereConditions[] = 'mvno.plan_name LIKE :plan_name';
            $params[':plan_name'] = '%' . $plan_name . '%';
        }
        
        // 약정기간 필터
        if ($contract_period && $contract_period !== '') {
            if ($contract_period === '무약정') {
                $whereConditions[] = 'mvno.contract_period = :contract_period';
                $params[':contract_period'] = '무약정';
            } else if ($contract_period === '기간입력') {
                $periodConditions = ['mvno.contract_period = :contract_period'];
                $params[':contract_period'] = '직접입력';
                
                if ($contract_period_days_min && $contract_period_days_min !== '') {
                    $periodConditions[] = 'mvno.contract_period_days >= :contract_period_days_min';
                    $params[':contract_period_days_min'] = intval($contract_period_days_min);
                }
                
                if ($contract_period_days_max && $contract_period_days_max !== '') {
                    $periodConditions[] = 'mvno.contract_period_days <= :contract_period_days_max';
                    $params[':contract_period_days_max'] = intval($contract_period_days_max);
                }
                
                $whereConditions[] = '(' . implode(' AND ', $periodConditions) . ')';
            }
        }
        
        // 할인 후 요금 필터
        if ($price_after_type === 'free') {
            $whereConditions[] = '(mvno.price_after IS NULL OR mvno.price_after = 0)';
        } else if ($price_after_type === 'amount') {
            $priceConditions = ['mvno.price_after IS NOT NULL AND mvno.price_after > 0'];
            
            if ($price_after_min && $price_after_min !== '') {
                $priceConditions[] = 'mvno.price_after >= :price_after_min';
                $params[':price_after_min'] = floatval($price_after_min);
            }
            
            if ($price_after_max && $price_after_max !== '') {
                $priceConditions[] = 'mvno.price_after <= :price_after_max';
                $params[':price_after_max'] = floatval($price_after_max);
            }
            
            $whereConditions[] = '(' . implode(' AND ', $priceConditions) . ')';
        }
        
        // 데이터속도 필터
        if ($service_type && $service_type !== '') {
            $whereConditions[] = 'mvno.service_type = :service_type';
            $params[':service_type'] = $service_type;
        }
        
        // 등록일 구간 필터
        if ($date_from && $date_from !== '') {
            $whereConditions[] = 'DATE(p.created_at) >= :date_from';
            $params[':date_from'] = $date_from;
        }
        if ($date_to && $date_to !== '') {
            $whereConditions[] = 'DATE(p.created_at) <= :date_to';
            $params[':date_to'] = $date_to;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // 판매자 정보를 항상 가져오기 위해 users 테이블 LEFT JOIN
        $searchJoin = "LEFT JOIN users u ON p.seller_id = u.user_id AND u.role = 'seller'";
        
        // 통합 검색 조건 추가
        $searchWhere = '';
        if ($search_query && $search_query !== '') {
            $searchParam = '%' . $search_query . '%';
            $searchWhere = " AND (
                mvno.plan_name LIKE :search_query1
                OR CAST(p.seller_id AS CHAR) LIKE :search_query2
                OR u.user_id LIKE :search_query3
                OR COALESCE(u.seller_name, '') LIKE :search_query4
                OR u.name LIKE :search_query5
                OR COALESCE(u.company_name, '') LIKE :search_query6
            )";
            $params[':search_query1'] = $searchParam;
            $params[':search_query2'] = $searchParam;
            $params[':search_query3'] = $searchParam;
            $params[':search_query4'] = $searchParam;
            $params[':search_query5'] = $searchParam;
            $params[':search_query6'] = $searchParam;
        }
        
        // 전체 개수 조회
        $countStmt = $pdo->prepare("
            SELECT COUNT(DISTINCT p.id) as total
            FROM products p
            INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
            {$searchJoin}
            WHERE {$whereClause}{$searchWhere}
        ");
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $totalProducts = $countStmt->fetch()['total'];
        $totalPages = ceil($totalProducts / $perPage);
        
        // 상품 목록 조회
        $offset = ($page - 1) * $perPage;
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                mvno.plan_name AS product_name,
                mvno.provider,
                mvno.price_after AS monthly_fee,
                p.seller_id AS seller_user_id,
                COALESCE(NULLIF(u.seller_name,''), NULLIF(u.company_name,''), NULLIF(u.name,''), u.user_id) AS seller_name,
                COALESCE(u.company_name,'') AS company_name,
                p.point_setting,
                p.point_benefit_description
            FROM products p
            INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
            {$searchJoin}
            WHERE {$whereClause}{$searchWhere}
            ORDER BY p.id DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $products = $stmt->fetchAll();
        
        // 판매자 정보 정리 (SQL에서 이미 가져왔지만, 없는 경우 처리)
        foreach ($products as &$product) {
            $sellerId = (string)($product['seller_user_id'] ?? $product['seller_id'] ?? '');
            if (empty($product['seller_name']) || $product['seller_name'] === null) {
                $product['seller_name'] = '-';
            }
            if (empty($product['company_name']) || $product['company_name'] === null) {
                $product['company_name'] = '-';
            }
        }
        unset($product);
    }
} catch (PDOException $e) {
    error_log("Error fetching MVNO products: " . $e->getMessage());
    error_log("SQL Error Info: " . json_encode($e->errorInfo ?? []));
    error_log("Where Clause: " . ($whereClause ?? 'N/A'));
    error_log("Params: " . json_encode($params ?? []));
}

?>

<style>
    .product-list-container {
        width: 100%;
        margin: 0;
        padding: 0 20px;
        box-sizing: border-box;
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
        flex-wrap: wrap;
        gap: 16px;
    }
    
    .page-header h1 {
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        margin: 0;
    }
    
    .filter-bar {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        display: flex;
        gap: 20px;
        align-items: flex-start;
    }
    
    .filter-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .filter-buttons {
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 100px;
        position: sticky;
        top: 20px;
    }
    
    .filter-group {
        display: flex;
        align-items: center;
        gap: 8px;
        justify-content: flex-start;
    }
    
    .filter-label {
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        min-width: 80px;
        white-space: nowrap;
        margin-right: 4px;
        text-align: right;
    }
    
    .filter-select,
    .filter-input {
        padding: 8px 12px;
        font-size: 14px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: white;
        text-align: left;
    }
    
    .filter-input:focus {
        outline: none;
        border-color: #10b981;
    }
    
    .search-button {
        padding: 8px 20px;
        font-size: 14px;
        font-weight: 600;
        border: none;
        border-radius: 6px;
        background: #10b981;
        color: white;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }
    
    .search-button:hover {
        background: #059669;
    }
    
    .search-button.secondary {
        background: #6b7280;
    }
    
    .search-button.secondary:hover {
        background: #4b5563;
    }
    
    .filter-row {
        display: flex;
        gap: 16px;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }
    
    .filter-row:last-child {
        margin-bottom: 0;
    }
    
    .filter-input-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .filter-input-group .filter-input {
        width: 120px;
    }
    
    .btn {
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 600;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-secondary {
        background: #6b7280;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #4b5563;
    }
    
    .btn-danger {
        background: #ef4444;
        color: white;
    }
    
    .btn-danger:hover {
        background: #dc2626;
    }
    
    .product-table-wrapper {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }
    
    .product-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .product-table thead {
        background: #f9fafb;
    }
    
    .product-table th {
        padding: 16px;
        text-align: left;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .product-table td {
        padding: 16px;
        font-size: 14px;
        color: #1f2937;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .product-table tbody tr:hover {
        background: #f9fafb;
    }
    
    .product-table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 12px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 12px;
    }
    
    .badge-active {
        background: #d1fae5;
        color: #065f46;
    }
    
    .badge-inactive {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 13px;
        border-radius: 6px;
    }
    
    .btn-edit {
        background: #3b82f6;
        color: white;
        text-decoration: none;
    }
    
    .btn-edit:hover {
        background: #2563eb;
    }
    
    .product-checkbox,
    #selectAll {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    
    .empty-state {
        padding: 60px 20px;
        text-align: center;
        color: #6b7280;
    }
    
    .empty-state-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 16px;
        opacity: 0.5;
    }
    
    .empty-state-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 8px;
        color: #374151;
    }
    
    .empty-state-text {
        font-size: 14px;
        margin-bottom: 24px;
    }
    
    .checkbox-column {
        width: 50px;
        text-align: center;
    }
    
    .product-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    
    .bulk-actions {
        display: none;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 8px;
        margin-bottom: 16px;
    }
    
    .bulk-actions-info {
        font-size: 14px;
        font-weight: 600;
        color: #0369a1;
    }
    
    .bulk-actions-select {
        padding: 8px 12px;
        font-size: 14px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: white;
        cursor: pointer;
        min-width: 150px;
    }
    
    .bulk-actions-btn {
        padding: 8px 20px;
        font-size: 14px;
        font-weight: 600;
        border: none;
        border-radius: 6px;
        background: #10b981;
        color: white;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .bulk-actions-btn:hover:not(:disabled) {
        background: #059669;
    }
    
    .bulk-actions-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        overflow: auto;
    }
    
    .modal-overlay.show {
        display: flex;
    }
    
    body.modal-open {
        overflow: hidden;
    }
    
    .modal {
        background: white;
        border-radius: 12px;
        padding: 24px;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }
    
    .modal-title {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 16px;
    }
    
    .modal-message {
        font-size: 14px;
        color: #4b5563;
        margin-bottom: 24px;
        line-height: 1.6;
        white-space: pre-line;
    }
    
    .modal-buttons {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }
    
    .modal-btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }
    
    .modal-btn-cancel {
        background: #f3f4f6;
        color: #374151;
    }
    
    .modal-btn-cancel:hover {
        background: #e5e7eb;
    }
    
    .modal-btn-confirm {
        background: #10b981;
        color: white;
    }
    
    .modal-btn-confirm:hover {
        background: #059669;
    }
    
    .modal-btn-ok {
        background: #3b82f6;
        color: white;
    }
    
    .modal-btn-ok:hover {
        background: #2563eb;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 24px;
        padding: 20px;
    }
    
    .pagination-btn {
        padding: 8px 16px;
        font-size: 14px;
        font-weight: 500;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: #f9fafb;
        color: #374151;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s;
        min-width: 40px;
        text-align: center;
    }
    
    .pagination-btn:hover:not(.disabled):not(.active) {
        background: #e5e7eb;
        border-color: #9ca3af;
    }
    
    .pagination-btn.active {
        background: #10b981;
        color: white;
        border-color: #10b981;
        font-weight: 600;
    }
    
    .pagination-btn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background: #f3f4f6;
    }
    
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        overflow: auto;
    }
    
    .modal-overlay.show {
        display: flex;
    }
    
    body.modal-open {
        overflow: hidden;
    }
    
    .modal {
        background: white;
        border-radius: 12px;
        padding: 24px;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }
    
    .modal-title {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 16px;
    }
    
    .modal-message {
        font-size: 14px;
        color: #4b5563;
        margin-bottom: 24px;
        line-height: 1.6;
    }
    
    .modal-buttons {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }
    
    .modal-btn {
        padding: 10px 20px;
        font-size: 14px;
        font-weight: 600;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .modal-btn-cancel {
        background: #f3f4f6;
        color: #374151;
    }
    
    .modal-btn-cancel:hover {
        background: #e5e7eb;
    }
    
    .modal-btn-confirm {
        background: #ef4444;
        color: white;
    }
    
    .modal-btn-confirm:hover {
        background: #dc2626;
    }
    
    .modal-btn-ok {
        background: #10b981;
        color: white;
    }
    
    .modal-btn-ok:hover {
        background: #059669;
    }
    
    .product-nav-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 24px;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 0;
    }
    
    .product-nav-tab {
        padding: 12px 24px;
        font-size: 15px;
        font-weight: 600;
        color: #6b7280;
        text-decoration: none;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        transition: all 0.2s;
        background: transparent;
        border-top: none;
        border-left: none;
        border-right: none;
        cursor: pointer;
    }
    
    .product-nav-tab:hover {
        color: #3b82f6;
        background: #f9fafb;
    }
    
    .product-nav-tab.active {
        color: #3b82f6;
        border-bottom-color: #3b82f6;
    }
    
    @media (max-width: 768px) {
        .product-table {
            font-size: 12px;
        }
        
        .product-table th,
        .product-table td {
            padding: 12px 8px;
        }
        
        .product-nav-tabs {
            flex-wrap: wrap;
        }
        
        .product-nav-tab {
            padding: 10px 16px;
            font-size: 14px;
        }
    }
</style>

<div class="admin-content">
    <div class="product-list-container">
        <div class="page-header">
            <h1>알뜰폰 상품 관리</h1>
            <div style="display: flex; align-items: center; gap: 8px;">
                <label style="font-size: 14px; color: #374151; font-weight: 600;">페이지당 표시:</label>
                <select class="filter-select" id="per_page_select" onchange="changePerPage()" style="width: 80px;">
                    <option value="10" <?php echo $perPage === 10 ? 'selected' : ''; ?>>10개</option>
                    <option value="20" <?php echo $perPage === 20 ? 'selected' : ''; ?>>20개</option>
                    <option value="50" <?php echo $perPage === 50 ? 'selected' : ''; ?>>50개</option>
                    <option value="100" <?php echo $perPage === 100 ? 'selected' : ''; ?>>100개</option>
                </select>
            </div>
        </div>
        
        <!-- 필터 바 -->
        <div class="filter-bar">
            <div class="filter-content">
                <div class="filter-row">
                    <div class="filter-group">
                        <label class="filter-label">상태:</label>
                        <select class="filter-select" id="filter_status">
                            <option value="">전체</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>판매중</option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>판매종료</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">통신사:</label>
                        <select class="filter-select" id="filter_provider">
                            <option value="">전체</option>
                            <option value="SK알뜰폰" <?php echo $provider === 'SK알뜰폰' ? 'selected' : ''; ?>>SK알뜰폰</option>
                            <option value="KT알뜰폰" <?php echo $provider === 'KT알뜰폰' ? 'selected' : ''; ?>>KT알뜰폰</option>
                            <option value="LG알뜰폰" <?php echo $provider === 'LG알뜰폰' ? 'selected' : ''; ?>>LG알뜰폰</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">데이터속도:</label>
                        <select class="filter-select" id="filter_service_type">
                            <option value="">전체</option>
                            <option value="LTE" <?php echo $service_type === 'LTE' ? 'selected' : ''; ?>>LTE</option>
                            <option value="5G" <?php echo $service_type === '5G' ? 'selected' : ''; ?>>5G</option>
                        </select>
                    </div>
                    
                    <div class="filter-group" style="display: flex; align-items: center; gap: 4px;">
                        <div>
                            <label class="filter-label">약정기간:</label>
                            <select class="filter-select" id="filter_contract_period" onchange="toggleContractPeriodInput()">
                                <option value="">전체</option>
                                <option value="무약정" <?php echo $contract_period === '무약정' ? 'selected' : ''; ?>>무약정</option>
                                <option value="기간입력" <?php echo $contract_period === '기간입력' ? 'selected' : ''; ?>>기간입력</option>
                            </select>
                        </div>
                        <div id="contract_period_days_wrapper" style="display: <?php echo ($contract_period === '기간입력') ? 'flex' : 'none'; ?>; align-items: center; gap: 4px;">
                            <input type="number" class="filter-input" id="filter_contract_period_days_min" placeholder="최소" value="<?php echo htmlspecialchars($contract_period_days_min); ?>" min="1" max="99999" style="width: 100px;">
                            <span style="color: #6b7280;">~</span>
                            <input type="number" class="filter-input" id="filter_contract_period_days_max" placeholder="최대" value="<?php echo htmlspecialchars($contract_period_days_max); ?>" min="1" max="99999" style="width: 100px;">
                            <span style="color: #6b7280; font-size: 12px;">일</span>
                        </div>
                    </div>
                </div>
                
                <div class="filter-row">
                    
                    <div class="filter-group">
                        <label class="filter-label">등록일:</label>
                        <div class="filter-input-group">
                            <input type="date" class="filter-input" id="filter_date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                            <span style="color: #6b7280;">~</span>
                            <input type="date" class="filter-input" id="filter_date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                    </div>
                    
                    <div class="filter-group" style="display: flex; align-items: center; gap: 8px;">
                        <div>
                            <label class="filter-label">할인 후 요금:</label>
                            <select class="filter-select" id="filter_price_after_type" onchange="togglePriceAfterInput()">
                                <option value="">전체</option>
                                <option value="free" <?php echo $price_after_type === 'free' ? 'selected' : ''; ?>>공짜</option>
                                <option value="amount" <?php echo $price_after_type === 'amount' ? 'selected' : ''; ?>>금액입력</option>
                            </select>
                        </div>
                        <div id="price_after_amount_wrapper" style="display: <?php echo ($price_after_type === 'amount') ? 'flex' : 'none'; ?>; align-items: center; gap: 4px;" class="filter-input-group">
                            <input type="number" class="filter-input" id="filter_price_after_min" placeholder="최소" value="<?php echo htmlspecialchars($price_after_min); ?>" min="0">
                            <span style="color: #6b7280;">~</span>
                            <input type="number" class="filter-input" id="filter_price_after_max" placeholder="최대" value="<?php echo htmlspecialchars($price_after_max); ?>" min="0">
                            <span style="color: #6b7280; font-size: 12px;">원</span>
                        </div>
                    </div>
                    
                    <div class="filter-group" style="flex: 1;">
                        <label class="filter-label">통합 검색:</label>
                        <input type="text" class="filter-input" id="filter_search_query" placeholder="판매자 ID / 판매자명 / 회사명 / 요금제명 검색" value="<?php echo htmlspecialchars($search_query); ?>" style="width: 50%;" onkeypress="if(event.key === 'Enter') { event.preventDefault(); applyFilters(); }">
                    </div>
                </div>
            </div>
            
            <div class="filter-buttons">
                <button class="search-button" onclick="applyFilters()" style="width: 100%; text-align: center;">검색</button>
                <button class="search-button secondary" onclick="resetFilters()" style="width: 100%; text-align: center;">초기화</button>
            </div>
        </div>
        
        <!-- 상품 테이블 -->
        <div class="product-table-wrapper">
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                    <div class="empty-state-title">등록된 상품이 없습니다</div>
                    <div class="empty-state-text">검색 조건을 변경해보세요</div>
                </div>
            <?php else: ?>
                <!-- 일괄 변경 UI -->
                <div class="bulk-actions" id="bulkActions" style="display: none;">
                    <span class="bulk-actions-info">
                        <span id="selectedCount">0</span>개 선택됨
                    </span>
                    <select id="bulkActionSelect" class="bulk-actions-select">
                        <option value="">작업 선택</option>
                        <option value="active">판매중으로 변경</option>
                        <option value="inactive">판매종료로 변경</option>
                    </select>
                    <button type="button" class="bulk-actions-btn" onclick="executeBulkAction()" id="bulkActionBtn" disabled>실행</button>
                </div>
                
                <table class="product-table">
                    <thead>
                        <tr>
                            <th class="checkbox-column">
                                <input type="checkbox" id="selectAll" class="product-checkbox" onchange="toggleSelectAll(this)">
                            </th>
                            <th style="text-align: center;">상품등록번호</th>
                            <th style="text-align: center;">아이디</th>
                            <th style="text-align: center;">판매자명</th>
                            <th style="text-align: center;">회사명</th>
                            <th style="text-align: center;">요금제명</th>
                            <th>통신사</th>
                            <th style="text-align: right;">할인 후 요금</th>
                            <th style="text-align: right;">조회수</th>
                            <th style="text-align: right;">찜</th>
                            <th style="text-align: right;">리뷰</th>
                            <th style="text-align: right;">신청</th>
                            <th style="text-align: center;">포인트</th>
                            <th style="text-align: center;">혜택내용</th>
                            <th style="text-align: center;">상태</th>
                            <th style="text-align: center;">등록일</th>
                            <th style="text-align: center;">관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $index => $product): ?>
                            <tr>
                                <td class="checkbox-column">
                                    <input type="checkbox" class="product-checkbox product-checkbox-item" 
                                           value="<?php echo $product['id']; ?>">
                                </td>
                                <td style="text-align: center;"><?php 
                                    $productNumber = getProductNumberByType($product['id'], 'mvno');
                                    echo $productNumber ? htmlspecialchars($productNumber) : htmlspecialchars($product['id'] ?? '-');
                                ?></td>
                                <td style="text-align: center;">
                                    <?php 
                                    $sellerId = $product['seller_user_id'] ?? $product['seller_id'] ?? '-';
                                    if ($sellerId && $sellerId !== '-') {
                                        echo '<a href="' . getAssetPath('/admin/users/seller-detail.php') . '?user_id=' . urlencode($sellerId) . '" style="color: #3b82f6; text-decoration: none; font-weight: 600;">' . htmlspecialchars($sellerId) . '</a>';
                                    } else {
                                        echo htmlspecialchars($sellerId);
                                    }
                                    ?>
                                </td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($product['seller_name'] ?? '-'); ?></td>
                                <td style="text-align: center;"><?php echo htmlspecialchars($product['company_name'] ?? '-'); ?></td>
                                <td style="text-align: left;"><?php echo htmlspecialchars($product['product_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($product['provider'] ?? '-'); ?></td>
                                <td style="text-align: right;">
                                    <?php 
                                    $monthlyFee = $product['monthly_fee'] ?? 0;
                                    if ($monthlyFee == 0 || $monthlyFee == null) {
                                        echo '<span style="color: #10b981; font-weight: 600;">공짜</span>';
                                    } else {
                                        echo number_format($monthlyFee, 0, '', '') . '원';
                                    }
                                    ?>
                                </td>
                                <td style="text-align: right;"><?php echo number_format($product['view_count'] ?? 0); ?></td>
                                <td style="text-align: right;"><?php echo number_format($product['favorite_count'] ?? 0); ?></td>
                                <td style="text-align: right;">
                                    <a href="#" 
                                       class="review-link" 
                                       onclick="showProductReviews(<?php echo $product['id']; ?>, 'mvno'); return false;"
                                       style="color: #3b82f6; text-decoration: none; font-weight: 600; cursor: pointer;">
                                        <?php echo number_format($product['review_count'] ?? 0); ?>
                                        <?php if (($product['review_count'] ?? 0) > 0): ?>
                                            <?php
                                            require_once __DIR__ . '/../../../includes/data/plan-data.php';
                                            $avgRating = getSingleProductAverageRating($product['id'], 'mvno');
                                            if ($avgRating > 0):
                                            ?>
                                                <span style="color: #f59e0b; margin-left: 4px;">⭐ <?php echo number_format($avgRating, 1); ?></span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td style="text-align: right;"><?php echo number_format($product['application_count'] ?? 0); ?></td>
                                <td style="text-align: center;">
                                    <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                        <?php 
                                        $pointSetting = isset($product['point_setting']) ? intval($product['point_setting']) : 0;
                                        if ($pointSetting > 0): 
                                        ?>
                                            <span style="color: #6366f1; font-weight: 600;"><?php echo number_format($pointSetting); ?>P</span>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">-</span>
                                        <?php endif; ?>
                                        <button type="button" 
                                                class="point-edit-btn" 
                                                onclick="openPointEditModal(<?php echo $product['id']; ?>, <?php echo $pointSetting; ?>, '<?php echo htmlspecialchars($product['point_benefit_description'] ?? '', ENT_QUOTES); ?>')"
                                                title="포인트 편집"
                                                style="background: none; border: none; padding: 4px; cursor: pointer; color: #6b7280; display: inline-flex; align-items: center; justify-content: center;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                        <?php 
                                        $benefitDesc = $product['point_benefit_description'] ?? '';
                                        if (!empty($benefitDesc)): 
                                        ?>
                                            <span style="color: #10b981; font-weight: 500; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($benefitDesc); ?>">
                                                <?php echo htmlspecialchars($benefitDesc); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">-</span>
                                        <?php endif; ?>
                                        <button type="button" 
                                                class="point-edit-btn" 
                                                onclick="openPointEditModal(<?php echo $product['id']; ?>, <?php echo $pointSetting; ?>, '<?php echo htmlspecialchars($benefitDesc, ENT_QUOTES); ?>')"
                                                title="혜택내용 편집"
                                                style="background: none; border: none; padding: 4px; cursor: pointer; color: #6b7280; display: inline-flex; align-items: center; justify-content: center;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge <?php echo ($product['status'] ?? 'active') === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo ($product['status'] ?? 'active') === 'active' ? '판매중' : '판매종료'; ?>
                                    </span>
                                </td>
                                <td style="text-align: center;"><?php echo isset($product['created_at']) ? date('Y-m-d', strtotime($product['created_at'])) : '-'; ?></td>
                                <td style="text-align: center;">
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-sm btn-edit" onclick="showProductInfo(<?php echo $product['id']; ?>, 'mvno')">보기</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <!-- 페이지네이션 (상품이 없을 때도 표시) -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination" style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 20px; flex-wrap: wrap;">
                    <?php
                    // 현재 페이지 URL 경로
                    $currentPagePath = getAssetPath('/admin/products/mvno-list.php');
                    
                    // 쿼리 파라미터 배열 생성
                    $queryParams = [];
                    if ($status) $queryParams['status'] = $status;
                    if (isset($seller_id) && $seller_id) $queryParams['seller_id'] = $seller_id;
                    if ($provider) $queryParams['provider'] = $provider;
                    if ($search_query) $queryParams['search_query'] = $search_query;
                    if ($contract_period) $queryParams['contract_period'] = $contract_period;
                    if ($contract_period_days_min) $queryParams['contract_period_days_min'] = $contract_period_days_min;
                    if ($contract_period_days_max) $queryParams['contract_period_days_max'] = $contract_period_days_max;
                    if ($price_after_type) $queryParams['price_after_type'] = $price_after_type;
                    if ($price_after_min) $queryParams['price_after_min'] = $price_after_min;
                    if ($price_after_max) $queryParams['price_after_max'] = $price_after_max;
                    if ($service_type) $queryParams['service_type'] = $service_type;
                    if ($date_from) $queryParams['date_from'] = $date_from;
                    if ($date_to) $queryParams['date_to'] = $date_to;
                    $queryParams['per_page'] = $perPage;
                    
                    // 페이지 그룹 계산 (10개씩 그룹화)
                    $pageGroupSize = 10;
                    $currentGroup = ceil($page / $pageGroupSize);
                    $startPage = ($currentGroup - 1) * $pageGroupSize + 1;
                    $endPage = min($currentGroup * $pageGroupSize, $totalPages);
                    $prevGroupLastPage = ($currentGroup - 1) * $pageGroupSize;
                    $nextGroupFirstPage = $currentGroup * $pageGroupSize + 1;
                    ?>
                    <!-- 이전 버튼 -->
                    <?php if ($currentGroup > 1): ?>
                        <?php 
                        $queryParams['page'] = $prevGroupLastPage;
                        $prevUrl = $currentPagePath . '?' . http_build_query($queryParams);
                        ?>
                        <a href="<?php echo htmlspecialchars($prevUrl); ?>" 
                           class="pagination-btn">이전</a>
                    <?php else: ?>
                        <span class="pagination-btn disabled">이전</span>
                    <?php endif; ?>
                    
                    <!-- 페이지 번호 표시 (현재 그룹만) -->
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <?php 
                        $queryParams['page'] = $i;
                        $pageUrl = $currentPagePath . '?' . http_build_query($queryParams);
                        $isActive = (intval($i) === intval($page));
                        ?>
                        <a href="<?php echo htmlspecialchars($pageUrl); ?>" 
                           class="pagination-btn <?php echo $isActive ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <!-- 다음 버튼 -->
                    <?php if ($nextGroupFirstPage <= $totalPages): ?>
                        <?php 
                        $queryParams['page'] = $nextGroupFirstPage;
                        $nextUrl = $currentPagePath . '?' . http_build_query($queryParams);
                        ?>
                        <a href="<?php echo htmlspecialchars($nextUrl); ?>" 
                           class="pagination-btn">다음</a>
                    <?php else: ?>
                        <span class="pagination-btn disabled">다음</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 확인 모달 -->
<div id="confirmModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-title" id="confirmTitle">확인</div>
        <div class="modal-message" id="confirmMessage"></div>
        <div class="modal-buttons">
            <button class="modal-btn modal-btn-cancel" onclick="closeConfirmModal(true)">취소</button>
            <button class="modal-btn modal-btn-confirm" onclick="confirmBulkChange()">확인</button>
        </div>
    </div>
</div>

<div id="alertModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-title" id="alertTitle">알림</div>
        <div class="modal-message" id="alertMessage"></div>
        <div class="modal-buttons">
            <button class="modal-btn modal-btn-ok" onclick="closeAlertModal()">확인</button>
        </div>
    </div>
</div>

<script>
// API 경로 설정 (절대 URL)
<?php
$apiUpdatePointPath = getAssetPath("/api/admin/update-product-point.php");
// 프로덕션에서 절대 URL 필요시
if (strpos($apiUpdatePointPath, 'http') !== 0 && isset($_SERVER['HTTP_HOST'])) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $apiUpdatePointPath = $protocol . '://' . $_SERVER['HTTP_HOST'] . $apiUpdatePointPath;
}
?>
const API_UPDATE_POINT_URL = '<?php echo htmlspecialchars($apiUpdatePointPath, ENT_QUOTES, 'UTF-8'); ?>';

// 모달 함수
function showConfirmModal(title, message) {
    const modal = document.getElementById('confirmModal');
    const titleEl = document.getElementById('confirmTitle');
    const messageEl = document.getElementById('confirmMessage');
    
    if (titleEl) titleEl.textContent = title;
    if (messageEl) messageEl.textContent = message;
    if (modal) {
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }
}

function closeConfirmModal(cancel = false) {
    const modal = document.getElementById('confirmModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }
    // 취소 버튼을 눌렀을 때만 초기화
    if (cancel) {
        pendingProductIds = [];
        pendingStatus = '';
    }
}

function showAlertModal(title, message) {
    document.getElementById('alertTitle').textContent = title;
    document.getElementById('alertMessage').textContent = message;
    document.getElementById('alertModal').classList.add('show');
    document.body.classList.add('modal-open');
}

function closeAlertModal() {
    document.getElementById('alertModal').classList.remove('show');
    document.body.classList.remove('modal-open');
}

// 필터 적용
function applyFilters() {
    const params = new URLSearchParams();
    
    const status = document.getElementById('filter_status').value;
    if (status) params.set('status', status);
    
    const provider = document.getElementById('filter_provider').value;
    if (provider) params.set('provider', provider);
    
    const searchInput = document.getElementById('filter_search_query');
    const searchQuery = searchInput ? searchInput.value.trim() : '';
    if (searchQuery) {
        params.set('search_query', searchQuery);
    }
    
    const contractPeriod = document.getElementById('filter_contract_period').value;
    if (contractPeriod) {
        params.set('contract_period', contractPeriod);
        if (contractPeriod === '기간입력') {
            const min = document.getElementById('filter_contract_period_days_min').value.trim();
            const max = document.getElementById('filter_contract_period_days_max').value.trim();
            if (min) params.set('contract_period_days_min', min);
            if (max) params.set('contract_period_days_max', max);
        }
    }
    
    const priceAfterType = document.getElementById('filter_price_after_type').value;
    if (priceAfterType) {
        params.set('price_after_type', priceAfterType);
        if (priceAfterType === 'amount') {
            const min = document.getElementById('filter_price_after_min').value.trim();
            const max = document.getElementById('filter_price_after_max').value.trim();
            if (min) params.set('price_after_min', min);
            if (max) params.set('price_after_max', max);
        }
    }
    
    const serviceType = document.getElementById('filter_service_type').value;
    if (serviceType) params.set('service_type', serviceType);
    
    const dateFrom = document.getElementById('filter_date_from').value;
    if (dateFrom) params.set('date_from', dateFrom);
    
    const dateTo = document.getElementById('filter_date_to').value;
    if (dateTo) params.set('date_to', dateTo);
    
    const perPage = new URLSearchParams(window.location.search).get('per_page');
    if (perPage) params.set('per_page', perPage);
    
    window.location.href = '?' + params.toString();
}

// 페이지당 표시 변경
function changePerPage() {
    const params = new URLSearchParams(window.location.search);
    params.set('per_page', document.getElementById('per_page_select').value);
    params.set('page', '1');
    window.location.href = '?' + params.toString();
}

// 약정기간 입력 필드 토글
function toggleContractPeriodInput() {
    const wrapper = document.getElementById('contract_period_days_wrapper');
    const contractPeriod = document.getElementById('filter_contract_period').value;
    wrapper.style.display = contractPeriod === '기간입력' ? 'flex' : 'none';
    if (contractPeriod !== '기간입력') {
        document.getElementById('filter_contract_period_days_min').value = '';
        document.getElementById('filter_contract_period_days_max').value = '';
    }
}

// 할인 후 요금 입력 필드 토글
function togglePriceAfterInput() {
    const wrapper = document.getElementById('price_after_amount_wrapper');
    const priceAfterType = document.getElementById('filter_price_after_type').value;
    wrapper.style.display = priceAfterType === 'amount' ? 'flex' : 'none';
    if (priceAfterType !== 'amount') {
        document.getElementById('filter_price_after_min').value = '';
        document.getElementById('filter_price_after_max').value = '';
    }
}

// 필터 초기화
function resetFilters() {
    document.getElementById('filter_status').value = '';
    document.getElementById('filter_provider').value = '';
    document.getElementById('filter_search_query').value = '';
    document.getElementById('filter_contract_period').value = '';
    document.getElementById('filter_contract_period_days_min').value = '';
    document.getElementById('filter_contract_period_days_max').value = '';
    document.getElementById('filter_price_after_type').value = '';
    document.getElementById('filter_price_after_min').value = '';
    document.getElementById('filter_price_after_max').value = '';
    document.getElementById('filter_service_type').value = '';
    document.getElementById('filter_date_from').value = '';
    document.getElementById('filter_date_to').value = '';
    
    toggleContractPeriodInput();
    togglePriceAfterInput();
    
    window.location.href = window.location.pathname;
}

// 전체 선택/해제
// 전체 선택/해제
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.product-checkbox-item');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateBulkActions();
}

// 선택된 상품 ID 목록 가져오기
function getSelectedProductIds() {
    const checkboxes = document.querySelectorAll('.product-checkbox-item:checked');
    const ids = Array.from(checkboxes)
        .map(cb => {
            const value = cb.value;
            // 숫자로 변환 가능한지 확인
            const numValue = parseInt(value, 10);
            return isNaN(numValue) ? null : numValue;
        })
        .filter(id => id !== null && id > 0);
    return ids;
}

// 일괄 변경 UI 업데이트
function updateBulkActions() {
    const selectedIds = getSelectedProductIds();
    const selectedCount = selectedIds.length;
    const bulkActions = document.getElementById('bulkActions');
    const selectedCountSpan = document.getElementById('selectedCount');
    const bulkActionBtn = document.getElementById('bulkActionBtn');
    const bulkActionSelect = document.getElementById('bulkActionSelect');
    const selectAllCheckbox = document.getElementById('selectAll');
    
    if (selectedCountSpan) {
        selectedCountSpan.textContent = selectedCount;
    }
    
    if (bulkActions) {
        bulkActions.style.display = selectedCount > 0 ? 'flex' : 'none';
    }
    
    if (bulkActionBtn) {
        bulkActionBtn.disabled = selectedCount === 0 || !bulkActionSelect || !bulkActionSelect.value;
    }
    
    // 전체 선택 체크박스 상태 업데이트
    if (selectAllCheckbox) {
        const allCheckboxes = document.querySelectorAll('.product-checkbox-item');
        const checkedCount = document.querySelectorAll('.product-checkbox-item:checked').length;
        selectAllCheckbox.checked = allCheckboxes.length > 0 && checkedCount === allCheckboxes.length;
        selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < allCheckboxes.length;
    }
}

// 일괄 상태 변경
let pendingProductIds = [];
let pendingStatus = '';

// 일괄 작업 실행
function executeBulkAction() {
    const selectedIds = getSelectedProductIds();
    const actionSelect = document.getElementById('bulkActionSelect');
    
    if (selectedIds.length === 0) {
        showAlertModal('알림', '선택된 상품이 없습니다.');
        return;
    }
    
    if (!actionSelect || !actionSelect.value) {
        showAlertModal('알림', '작업을 선택해주세요.');
        return;
    }
    
    const action = String(actionSelect.value).trim().toLowerCase();
    
    if (action === 'active' || action === 'inactive') {
        bulkChangeStatus(action);
    } else {
        console.error('Invalid action value:', action);
        showAlertModal('오류', '유효하지 않은 작업입니다: ' + action);
    }
}

// 일괄 상태 변경
function bulkChangeStatus(status) {
    // 상태 값 검증 및 정규화
    const normalizedStatus = String(status || '').trim().toLowerCase();
    
    if (normalizedStatus !== 'active' && normalizedStatus !== 'inactive') {
        console.error('Invalid status value:', status);
        showAlertModal('오류', '유효하지 않은 상태 값입니다: ' + (status || 'undefined'));
        return;
    }
    
    const selectedIds = getSelectedProductIds();
    if (selectedIds.length === 0) {
        showAlertModal('알림', '선택된 상품이 없습니다.');
        return;
    }
    
    const productCount = selectedIds.length;
    const statusText = normalizedStatus === 'active' ? '판매중' : '판매종료';
    const message = '선택한 ' + productCount + '개의 상품을 ' + statusText + ' 처리하시겠습니까?';
    
    // 모달에 표시할 제목 설정
    const title = normalizedStatus === 'active' ? '판매중 변경 확인' : '판매종료 변경 확인';
    
    // 대기 중인 데이터 저장 (정규화된 값 사용)
    pendingProductIds = selectedIds;
    pendingStatus = normalizedStatus;
    
    // 확인 모달 표시
    showConfirmModal(title, message);
}

// 확인 모달에서 확인 버튼 클릭 시 실행
function confirmBulkChange() {
    if (pendingProductIds.length === 0 || !pendingStatus) {
        console.error('Missing data for bulk change:', { 
            productIds: pendingProductIds, 
            status: pendingStatus 
        });
        closeConfirmModal();
        showAlertModal('오류', '상태 변경에 필요한 데이터가 없습니다.');
        return;
    }
    
    // 상태 값 재검증
    const normalizedStatus = String(pendingStatus).trim().toLowerCase();
    if (normalizedStatus !== 'active' && normalizedStatus !== 'inactive') {
        closeConfirmModal();
        console.error('Invalid pending status:', pendingStatus);
        showAlertModal('오류', '유효하지 않은 상태 값입니다: ' + pendingStatus);
        return;
    }
    
    closeConfirmModal();
    processBulkChangeStatus(pendingProductIds, normalizedStatus);
}

// 일괄 상태 변경 처리
function processBulkChangeStatus(productIds, status) {
    // 상품 ID 검증 및 정수 변환
    if (!Array.isArray(productIds) || productIds.length === 0) {
        console.error('Invalid productIds:', productIds);
        showAlertModal('오류', '선택된 상품 ID가 없습니다.');
        return;
    }
    
    // 문자열 ID를 정수로 변환
    const validProductIds = productIds
        .map(id => {
            const numId = parseInt(id, 10);
            return isNaN(numId) ? null : numId;
        })
        .filter(id => id !== null && id > 0);
    
    if (validProductIds.length === 0) {
        console.error('No valid product IDs:', productIds);
        showAlertModal('오류', '유효한 상품 ID가 없습니다.');
        return;
    }
    
    const statusText = status === 'active' ? '판매중' : '판매종료';
    
    // 상태 값 검증 및 정규화
    const normalizedStatus = String(status).trim().toLowerCase();
    if (normalizedStatus !== 'active' && normalizedStatus !== 'inactive') {
        showAlertModal('오류', '유효하지 않은 상태 값입니다: ' + status);
        return;
    }
    
    console.log('Sending bulk update:', {
        product_ids: validProductIds,
        status: normalizedStatus
    });
    
    fetch('<?php echo getAssetPath("/api/admin-product-bulk-update.php"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_ids: validProductIds,
            status: normalizedStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlertModal('성공', data.message || productIds.length + '개의 상품이 ' + statusText + ' 처리되었습니다.');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlertModal('오류', data.message || '상품 상태 변경에 실패했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlertModal('오류', '상품 상태 변경 중 오류가 발생했습니다.');
    });
}


// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    // 초기 상태 업데이트
    updateBulkActions();
    
    // 체크박스 이벤트 리스너 추가
    const checkboxes = document.querySelectorAll('.product-checkbox-item');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkActions();
        });
    });
    
    // Enter 키로 검색
    const searchInputs = [
        'filter_search_query',
        'filter_price_after_min',
        'filter_price_after_max',
        'filter_contract_period_days_min',
        'filter_contract_period_days_max'
    ];
    
    searchInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    applyFilters();
                }
            });
        }
    });
    
    // 일괄 변경 셀렉트박스 변경 이벤트
    const bulkActionSelect = document.getElementById('bulkActionSelect');
    if (bulkActionSelect) {
        bulkActionSelect.addEventListener('change', function() {
            const bulkActionBtn = document.getElementById('bulkActionBtn');
            if (bulkActionBtn) {
                bulkActionBtn.disabled = !this.value || getSelectedProductIds().length === 0;
            }
        });
    }
    
    // 모달 오버레이 클릭 시 닫기
    const confirmModal = document.getElementById('confirmModal');
    const alertModal = document.getElementById('alertModal');
    
    if (confirmModal) {
        confirmModal.addEventListener('click', function(e) {
            if (e.target === confirmModal) {
                closeConfirmModal();
            }
        });
    }
    
    if (alertModal) {
        alertModal.addEventListener('click', function(e) {
            if (e.target === alertModal) {
                closeAlertModal();
            }
        });
    }
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (confirmModal && confirmModal.classList.contains('show')) {
                closeConfirmModal();
            }
            if (alertModal && alertModal.classList.contains('show')) {
                closeAlertModal();
            }
        }
    });
    
    // 필드 표시 여부 확인
    toggleContractPeriodInput();
    togglePriceAfterInput();
    
    // 리뷰 모달 기능
    function showProductReviews(productId, productType) {
        const modal = document.getElementById('productReviewModal');
        const modalContent = document.getElementById('productReviewContent');
        const modalTitle = document.getElementById('productReviewTitle');
        
        if (!modal || !modalContent) return;
        
        // 모달 표시
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // 로딩 표시
        modalContent.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner"></div><p>리뷰를 불러오는 중...</p></div>';
        
        // API 호출
        fetch(`<?php echo getAssetPath("/api/get-product-reviews.php"); ?>?product_id=${productId}&product_type=${productType}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayReviews(data.data, productId, productType);
                } else {
                    modalContent.innerHTML = `<div style="text-align: center; padding: 40px; color: #dc2626;"><p>${data.message || '리뷰를 불러오는 중 오류가 발생했습니다.'}</p></div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                modalContent.innerHTML = '<div style="text-align: center; padding: 40px; color: #dc2626;"><p>리뷰를 불러오는 중 오류가 발생했습니다.</p></div>';
            });
    }
    
    function displayReviews(data, productId, productType) {
        const modalContent = document.getElementById('productReviewContent');
        const modalTitle = document.getElementById('productReviewTitle');
        
        if (!modalContent) return;
        
        const reviews = data.reviews || [];
        const averageRating = data.average_rating || 0;
        const reviewCount = data.review_count || 0;
        
        let html = '<div style="padding: 20px;">';
        
        // 요약 정보
        html += '<div style="background: #f9fafb; padding: 16px; border-radius: 8px; margin-bottom: 20px;">';
        html += `<div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">`;
        html += `<div><strong>평균 별점:</strong> <span style="color: #f59e0b; font-weight: 600;">⭐ ${averageRating.toFixed(1)}</span></div>`;
        html += `<div><strong>리뷰 수:</strong> ${reviewCount}개</div>`;
        html += `</div></div>`;
        
        // 리뷰 목록
        if (reviews.length > 0) {
            html += '<div style="max-height: 500px; overflow-y: auto;">';
            reviews.forEach(review => {
                const stars = '⭐'.repeat(review.rating) + '☆'.repeat(5 - review.rating);
                html += '<div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 12px; background: white;">';
                html += `<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">`;
                html += `<div><strong>${review.author_name || '익명'}</strong> <span style="color: #6b7280; font-size: 12px;">${review.date_ago || ''}</span></div>`;
                html += `<div style="color: #f59e0b;">${stars}</div>`;
                html += `</div>`;
                if (review.title) {
                    html += `<div style="font-weight: 600; margin-bottom: 8px;">${escapeHtml(review.title)}</div>`;
                }
                html += `<div style="color: #374151; line-height: 1.6;">${escapeHtml(review.content || '')}</div>`;
                html += `</div>`;
            });
            html += '</div>';
        } else {
            html += '<div style="text-align: center; padding: 40px; color: #6b7280;">등록된 리뷰가 없습니다.</div>';
        }
        
        html += '</div>';
        modalContent.innerHTML = html;
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // 모달 닫기
    const reviewModal = document.getElementById('productReviewModal');
    const reviewModalClose = document.getElementById('productReviewModalClose');
    const reviewModalOverlay = document.getElementById('productReviewModalOverlay');
    
    if (reviewModalClose) {
        reviewModalClose.addEventListener('click', function() {
            if (reviewModal) {
                reviewModal.style.display = 'none';
                document.body.style.overflow = '';
            }
        });
    }
    
    if (reviewModalOverlay) {
        reviewModalOverlay.addEventListener('click', function() {
            if (reviewModal) {
                reviewModal.style.display = 'none';
                document.body.style.overflow = '';
            }
        });
    }
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && reviewModal && reviewModal.style.display === 'flex') {
            reviewModal.style.display = 'none';
            document.body.style.overflow = '';
        }
    });
});
</script>

<!-- 리뷰 모달 -->
<div id="productReviewModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 10000; align-items: center; justify-content: center;">
    <div id="productReviewModalOverlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0;"></div>
    <div style="position: relative; background: white; border-radius: 12px; width: 90%; max-width: 800px; max-height: 90vh; display: flex; flex-direction: column; z-index: 10001;">
        <div style="padding: 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
            <h2 id="productReviewTitle" style="font-size: 24px; font-weight: bold; margin: 0; color: #1f2937;">상품 리뷰</h2>
            <button id="productReviewModalClose" style="background: none; border: none; cursor: pointer; padding: 8px; display: flex; align-items: center; justify-content: center; border-radius: 6px; transition: background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#374151" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div id="productReviewContent" style="padding: 24px; overflow-y: auto; flex: 1;">
            <div style="text-align: center; padding: 40px;">
                <div class="spinner" style="border: 3px solid #f3f4f6; border-top: 3px solid #6366f1; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 16px;"></div>
                <p>리뷰를 불러오는 중...</p>
            </div>
        </div>
    </div>
</div>

<style>
.spinner {
    border: 3px solid #f3f4f6;
    border-top: 3px solid #6366f1;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto 16px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.review-link:hover {
    text-decoration: underline;
}

.point-edit-btn:hover {
    background: #f3f4f6 !important;
    color: #374151 !important;
}

/* 포인트 편집 모달 */
#pointEditModal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

#pointEditModal.show {
    display: flex;
}

.point-edit-modal-content {
    position: relative;
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    padding: 0;
    z-index: 10001;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.point-edit-modal-header {
    padding: 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.point-edit-modal-header h2 {
    font-size: 20px;
    font-weight: 700;
    margin: 0;
    color: #1f2937;
}

.point-edit-modal-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: background 0.2s;
}

.point-edit-modal-close:hover {
    background: #f3f4f6;
}

.point-edit-modal-body {
    padding: 24px;
}

.point-edit-form-group {
    margin-bottom: 20px;
}

.point-edit-form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.point-edit-form-input {
    width: 100%;
    padding: 10px 12px;
    font-size: 14px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    box-sizing: border-box;
}

.point-edit-form-input:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.point-edit-form-textarea {
    width: 100%;
    padding: 10px 12px;
    font-size: 14px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    box-sizing: border-box;
    min-height: 100px;
    resize: vertical;
    font-family: inherit;
}

.point-edit-form-textarea:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.point-edit-form-help {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.point-edit-modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.point-edit-btn {
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.point-edit-btn-primary {
    background: #6366f1;
    color: white;
}

.point-edit-btn-primary:hover {
    background: #4f46e5;
}

.point-edit-btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.point-edit-btn-secondary:hover {
    background: #e5e7eb;
}

.point-edit-btn-danger {
    background: #ef4444;
    color: white;
}

.point-edit-btn-danger:hover {
    background: #dc2626;
}

.point-edit-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>

<!-- 포인트 편집 모달 -->
<div id="pointEditModal" class="point-edit-modal">
    <div class="point-edit-modal-content">
        <div class="point-edit-modal-header">
            <h2>포인트 및 혜택내용 편집</h2>
            <button type="button" class="point-edit-modal-close" onclick="closePointEditModal()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6L18 18" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="point-edit-modal-body">
            <form id="pointEditForm" onsubmit="savePointEdit(event)">
                <input type="hidden" id="pointEditProductId" value="">
                <div class="point-edit-form-group">
                    <label class="point-edit-form-label" for="pointEditPointSetting">
                        포인트 설정
                    </label>
                    <input type="number" 
                           id="pointEditPointSetting" 
                           class="point-edit-form-input" 
                           min="0" 
                           step="1000" 
                           placeholder="0"
                           required>
                    <div class="point-edit-form-help">1000포인트 단위로 입력해주세요. (예: 3000, 5000, 10000) 삭제하려면 0을 입력하세요.</div>
                </div>
                <div class="point-edit-form-group">
                    <label class="point-edit-form-label" for="pointEditBenefitDescription">
                        혜택내용
                    </label>
                    <textarea id="pointEditBenefitDescription" 
                              class="point-edit-form-textarea" 
                              placeholder="예: 네이버페이 5000원 지급 익월말"></textarea>
                    <div class="point-edit-form-help">고객에게 표시될 혜택 내용을 입력해주세요.</div>
                </div>
            </form>
        </div>
        <div class="point-edit-modal-footer">
            <button type="button" class="point-edit-btn point-edit-btn-danger" onclick="deletePointEdit()">삭제</button>
            <button type="button" class="point-edit-btn point-edit-btn-secondary" onclick="closePointEditModal()">취소</button>
            <button type="button" class="point-edit-btn point-edit-btn-primary" onclick="savePointEdit()">저장</button>
        </div>
    </div>
</div>

<script>
let currentPointEditProductId = null;

function openPointEditModal(productId, pointSetting, benefitDescription) {
    currentPointEditProductId = productId;
    const modal = document.getElementById('pointEditModal');
    const pointInput = document.getElementById('pointEditPointSetting');
    const benefitInput = document.getElementById('pointEditBenefitDescription');
    const productIdInput = document.getElementById('pointEditProductId');
    
    if (modal && pointInput && benefitInput && productIdInput) {
        productIdInput.value = productId;
        pointInput.value = pointSetting || 0;
        benefitInput.value = benefitDescription || '';
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // 포인트 입력 검증
        pointInput.addEventListener('input', function() {
            const value = parseInt(this.value) || 0;
            if (value > 0 && value % 1000 !== 0) {
                this.setCustomValidity('1000포인트 단위로 입력해주세요.');
            } else {
                this.setCustomValidity('');
            }
        });
    }
}

function closePointEditModal() {
    const modal = document.getElementById('pointEditModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
        currentPointEditProductId = null;
    }
}

function deletePointEdit() {
    if (!confirm('포인트와 혜택내용을 모두 삭제하시겠습니까?')) {
        return;
    }
    
    const productId = document.getElementById('pointEditProductId').value;
    const pointInput = document.getElementById('pointEditPointSetting');
    const benefitInput = document.getElementById('pointEditBenefitDescription');
    
    // 입력 필드 비우기
    if (pointInput) pointInput.value = 0;
    if (benefitInput) benefitInput.value = '';
    
    // 저장 버튼 비활성화
    const saveBtn = document.querySelector('.point-edit-btn-primary');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.textContent = '삭제 중...';
    }
    
    // API 호출 - FormData 사용 (웹서버 호환성)
    console.log('API URL:', API_UPDATE_POINT_URL);
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('point_setting', '0');
    formData.append('point_benefit_description', '');
    
    fetch(API_UPDATE_POINT_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                console.error('API Error Response:', text);
                throw new Error('HTTP ' + response.status + ': ' + text.substring(0, 100));
            });
        }
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            return response.text().then(text => {
                console.error('Non-JSON Response:', text);
                throw new Error('서버가 JSON이 아닌 응답을 반환했습니다.');
            });
        }
    })
    .then(data => {
        if (data.success) {
            showAlertModal('성공', '포인트 및 혜택내용이 삭제되었습니다.');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlertModal('오류', data.message || '삭제에 실패했습니다.');
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = '저장';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlertModal('오류', '삭제 중 오류가 발생했습니다: ' + (error.message || '알 수 없는 오류'));
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.textContent = '저장';
        }
    });
}

function savePointEdit(event) {
    if (event) {
        event.preventDefault();
    }
    
    const productId = document.getElementById('pointEditProductId').value;
    const pointSetting = parseInt(document.getElementById('pointEditPointSetting').value) || 0;
    const benefitDescription = document.getElementById('pointEditBenefitDescription').value.trim();
    
    // 포인트 검증
    if (pointSetting > 0 && pointSetting % 1000 !== 0) {
        showAlertModal('오류', '포인트는 1000포인트 단위로 입력해주세요.');
        return;
    }
    
    // 저장 버튼 비활성화
    const saveBtn = document.querySelector('.point-edit-btn-primary');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.textContent = '저장 중...';
    }
    
    // API 호출 - FormData 사용 (웹서버 호환성)
    console.log('API URL:', API_UPDATE_POINT_URL);
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('point_setting', pointSetting);
    formData.append('point_benefit_description', benefitDescription);
    
    fetch(API_UPDATE_POINT_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                console.error('API Error Response:', text);
                throw new Error('HTTP ' + response.status + ': ' + text.substring(0, 100));
            });
        }
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            return response.text().then(text => {
                console.error('Non-JSON Response:', text);
                throw new Error('서버가 JSON이 아닌 응답을 반환했습니다.');
            });
        }
    })
    .then(data => {
        if (data.success) {
            showAlertModal('성공', '포인트 및 혜택내용이 저장되었습니다.');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlertModal('오류', data.message || '저장에 실패했습니다.');
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = '저장';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlertModal('오류', '저장 중 오류가 발생했습니다: ' + (error.message || '알 수 없는 오류'));
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.textContent = '저장';
        }
    });
}

// 모달 오버레이 클릭 시 닫기
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('pointEditModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closePointEditModal();
            }
        });
    }
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('show')) {
            closePointEditModal();
        }
    });
});
</script>

<!-- 상품 상세 정보 모달 -->
<div id="productInfoModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10001; overflow-y: auto;">
    <div style="position: relative; max-width: 1200px; margin: 40px auto; background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 2px solid #e5e7eb; padding-bottom: 16px;">
            <h2 style="font-size: 24px; font-weight: 700; color: #1f2937; margin: 0;">상품 상세 정보</h2>
            <button onclick="closeProductInfoModal()" style="background: none; border: none; font-size: 28px; color: #6b7280; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 4px; transition: all 0.2s;" onmouseover="this.style.background='#f3f4f6'; this.style.color='#374151';" onmouseout="this.style.background='none'; this.style.color='#6b7280';">×</button>
        </div>
        <div id="productInfoContent" style="color: #1f2937;">
            <div style="text-align: center; padding: 40px; color: #6b7280;">상품 정보를 불러오는 중...</div>
        </div>
    </div>
</div>

<style>
.product-info-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 24px;
}
.product-info-table th {
    background: #f9fafb;
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border: 1px solid #e5e7eb;
    width: 200px;
}
.product-info-table td {
    padding: 12px 16px;
    border: 1px solid #e5e7eb;
    color: #1f2937;
}
</style>

<script>
<?php
$getProductInfoApi = getApiPath('/api/get-product-info.php');
?>
const GET_PRODUCT_INFO_API = '<?php echo htmlspecialchars($getProductInfoApi, ENT_QUOTES, 'UTF-8'); ?>';

function showProductInfo(productId, productType) {
    const modal = document.getElementById('productInfoModal');
    const content = document.getElementById('productInfoContent');
    
    if (!modal || !content) {
        console.error('Modal elements not found');
        alert('상품 정보를 불러올 수 없습니다.');
        return;
    }
    
    document.body.style.overflow = 'hidden';
    modal.style.display = 'block';
    content.innerHTML = '<div style="text-align: center; padding: 40px; color: #6b7280;">상품 정보를 불러오는 중...</div>';
    
    fetch(GET_PRODUCT_INFO_API + '?product_id=' + productId + '&product_type=' + productType)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.product) {
                const product = data.product;
                let html = '';
                
                if (productType === 'mvno') {
                    html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">판매 상태</h3>';
                    html += '<table class="product-info-table">';
                    html += '<tr><th>상태</th><td>' + (product.status === 'active' ? '판매중' : '판매종료') + '</td></tr>';
                    html += '</table></div>';
                    
                    html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">요금제</h3>';
                    html += '<table class="product-info-table">';
                    html += '<tr><th>통신사</th><td>' + (product.provider || '-') + '</td></tr>';
                    html += '<tr><th>요금제명</th><td>' + (product.plan_name || product.product_name || '-') + '</td></tr>';
                    html += '<tr><th>월 요금</th><td>' + (product.price_main ? number_format(product.price_main) + '원' : '-') + '</td></tr>';
                    html += '</table></div>';
                    
                    html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">포인트 할인 혜택 설정</h3>';
                    html += '<table class="product-info-table">';
                    const pointSetting = product.point_setting ? parseInt(product.point_setting) : 0;
                    html += '<tr><th>포인트설정금액</th><td>' + (pointSetting > 0 ? number_format(pointSetting) + 'P' : '-') + '</td></tr>';
                    html += '<tr><th>할인혜택내용</th><td style="white-space: pre-wrap;">' + (product.point_benefit_description || '-') + '</td></tr>';
                    html += '</table></div>';
                    
                    html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">기타 정보</h3>';
                    html += '<table class="product-info-table">';
                    html += '<tr><th>등록일</th><td>' + (product.created_at ? new Date(product.created_at).toLocaleString('ko-KR') : '-') + '</td></tr>';
                    html += '</table></div>';
                }
                
                content.innerHTML = html;
            } else {
                content.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">상품 정보를 불러올 수 없습니다.</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;">상품 정보를 불러오는 중 오류가 발생했습니다.</div>';
        });
}

function closeProductInfoModal() {
    const modal = document.getElementById('productInfoModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function number_format(number) {
    const numValue = parseFloat(number) || 0;
    if (numValue % 1 === 0) {
        return Math.floor(numValue).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    } else {
        const formatted = numValue.toString().replace(/\.?0+$/, '');
        const parts = formatted.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        return parts.join('.');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('productInfoModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeProductInfoModal();
            }
        });
    }
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('productInfoModal');
            if (modal && modal.style.display === 'block') {
                closeProductInfoModal();
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

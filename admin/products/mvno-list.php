<?php
/**
 * 알뜰폰 상품 목록 페이지 (관리자)
 * 경로: /admin/products/mvno-list.php
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/product-functions.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';

// 검색 필터 파라미터 처리
$status = $_GET['status'] ?? null;
if ($status === '') $status = null;

$provider = $_GET['provider'] ?? '';
$search_query = $_GET['search_query'] ?? ''; // 통합 검색 필드
$plan_name = $_GET['plan_name'] ?? '';
$contract_period = $_GET['contract_period'] ?? '';
$contract_period_days_min = $_GET['contract_period_days_min'] ?? '';
$contract_period_days_max = $_GET['contract_period_days_max'] ?? '';
$price_after_type = $_GET['price_after_type'] ?? '';
$price_after_min = $_GET['price_after_min'] ?? '';
$price_after_max = $_GET['price_after_max'] ?? '';
$service_type = $_GET['service_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$seller_id = $_GET['seller_id'] ?? '';
$seller_name = $_GET['seller_name'] ?? '';
$company_name = $_GET['company_name'] ?? '';
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
        if ($seller_id && $seller_id !== '') {
            $whereConditions[] = 'p.seller_id = :seller_id';
            $params[':seller_id'] = $seller_id;
        }
        
        // 통신사 필터
        if ($provider && $provider !== '') {
            $whereConditions[] = 'mvno.provider = :provider';
            $params[':provider'] = $provider;
        }
        
        // 요금제명 필터
        if ($plan_name && $plan_name !== '') {
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
        
        // 전체 개수 조회
        $countStmt = $pdo->prepare("
            SELECT COUNT(DISTINCT p.id) as total
            FROM products p
            INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
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
                p.seller_id AS seller_user_id
            FROM products p
            INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
            WHERE {$whereClause}
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
        
        // 판매자 정보 매핑 (DB-only)
        $sellerIds = [];
        foreach ($products as $p) {
            $sid = (string)($p['seller_user_id'] ?? $p['seller_id'] ?? '');
            if ($sid !== '') $sellerIds[$sid] = true;
        }

        $sellerMap = [];
        if (!empty($sellerIds)) {
            $idList = array_keys($sellerIds);
            $placeholders = implode(',', array_fill(0, count($idList), '?'));
            $sellerStmt = $pdo->prepare("
                SELECT
                    u.user_id,
                    COALESCE(NULLIF(u.seller_name,''), NULLIF(u.name,''), u.user_id) AS display_name,
                    COALESCE(u.company_name,'') AS company_name
                FROM users u
                WHERE u.role = 'seller'
                  AND u.user_id IN ($placeholders)
            ");
            $sellerStmt->execute($idList);
            foreach ($sellerStmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
                $sellerMap[(string)$s['user_id']] = $s;
            }
        }

        foreach ($products as &$product) {
            $sellerId = (string)($product['seller_user_id'] ?? $product['seller_id'] ?? '');
            if ($sellerId && isset($sellerMap[$sellerId])) {
                $product['seller_name'] = $sellerMap[$sellerId]['display_name'] ?? '-';
                $product['company_name'] = $sellerMap[$sellerId]['company_name'] ?? '-';
            } else {
                $product['seller_name'] = '-';
                $product['company_name'] = '-';
            }
        }
        unset($product);
        
        // 통합 검색 필터링 (판매자 ID, 판매자명, 회사명, 요금제명)
        if ($search_query && $search_query !== '') {
            $searchLower = mb_strtolower($search_query, 'UTF-8');
            $products = array_filter($products, function($product) use ($searchLower) {
                // 판매자 ID 검색
                $sellerId = (string)($product['seller_user_id'] ?? $product['seller_id'] ?? '');
                if (mb_strpos(mb_strtolower($sellerId, 'UTF-8'), $searchLower) !== false) {
                    return true;
                }
                
                // 판매자명 검색
                $sellerName = mb_strtolower($product['seller_name'] ?? '', 'UTF-8');
                if (mb_strpos($sellerName, $searchLower) !== false) {
                    return true;
                }
                
                // 회사명 검색
                $companyName = mb_strtolower($product['company_name'] ?? '', 'UTF-8');
                if (mb_strpos($companyName, $searchLower) !== false) {
                    return true;
                }
                
                // 요금제명 검색
                $planName = mb_strtolower($product['product_name'] ?? '', 'UTF-8');
                if (mb_strpos($planName, $searchLower) !== false) {
                    return true;
                }
                
                return false;
            });
            $products = array_values($products);
        } else {
            // 개별 필터링 (하위 호환성)
            if ($seller_name && $seller_name !== '') {
                $products = array_filter($products, function($product) use ($seller_name) {
                    $name = mb_strtolower($product['seller_name'] ?? '', 'UTF-8');
                    $search = mb_strtolower($seller_name, 'UTF-8');
                    return mb_strpos($name, $search) !== false;
                });
                $products = array_values($products);
            }
            
            if ($company_name && $company_name !== '') {
                $products = array_filter($products, function($product) use ($company_name) {
                    $name = mb_strtolower($product['company_name'] ?? '', 'UTF-8');
                    $search = mb_strtolower($company_name, 'UTF-8');
                    return mb_strpos($name, $search) !== false;
                });
                $products = array_values($products);
            }
        }
        
        // 필터링 후 페이지네이션 재계산
        $totalProducts = count($products);
        $totalPages = ceil($totalProducts / $perPage);
        $offset = ($page - 1) * $perPage;
        $products = array_slice($products, $offset, $perPage);
    }
} catch (PDOException $e) {
    error_log("Error fetching MVNO products: " . $e->getMessage());
    error_log("SQL Error Info: " . json_encode($e->errorInfo ?? []));
    error_log("Where Clause: " . ($whereClause ?? 'N/A'));
    error_log("Params: " . json_encode($params ?? []));
}

// 판매자 목록 가져오기 (필터용)
$sellers = [];
try {
    if ($pdo) {
        $sellerStmt = $pdo->prepare("
            SELECT DISTINCT p.seller_id as user_id
            FROM products p
            INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
            WHERE p.product_type = 'mvno' AND p.status != 'deleted'
            ORDER BY p.seller_id
        ");
        $sellerStmt->execute();
        $sellers = $sellerStmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Error fetching sellers: " . $e->getMessage());
}
?>

<style>
    .product-list-container {
        max-width: 1400px;
        margin: 0 auto;
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
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: white;
        color: #374151;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s;
    }
    
    .pagination-btn:hover {
        background: #f9fafb;
        border-color: #10b981;
    }
    
    .pagination-btn.active {
        background: #10b981;
        color: white;
        border-color: #10b981;
    }
    
    .pagination-btn.disabled {
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
    
    @media (max-width: 768px) {
        .product-table {
            font-size: 12px;
        }
        
        .product-table th,
        .product-table td {
            padding: 12px 8px;
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
                            <option value="6G" <?php echo $service_type === '6G' ? 'selected' : ''; ?>>6G</option>
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
                        <input type="text" class="filter-input" id="filter_search_query" placeholder="판매자 ID / 판매자명 / 회사명 / 요금제명 검색" value="<?php echo htmlspecialchars($search_query); ?>" style="width: 50%;">
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
                <table class="product-table">
                    <thead>
                        <tr>
                        <th style="text-align: center; width: 80px;">
                            <button type="button" class="btn-danger" onclick="bulkInactive()" style="font-size: 11px; padding: 3px 8px; margin-bottom: 8px; width: 100%; white-space: nowrap; display: block;">판매종료</button>
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" style="display: block; margin: 0 auto;">
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
                            <th style="text-align: center;">상태</th>
                            <th style="text-align: center;">등록일</th>
                            <th style="text-align: center;">관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $index => $product): ?>
                            <tr>
                                <td style="text-align: center;">
                                    <input type="checkbox" class="product-checkbox" value="<?php echo $product['id']; ?>">
                                </td>
                                <td style="text-align: center;"><?php 
                                    $productNumber = getProductNumberByType($product['id'], 'mvno');
                                    echo $productNumber ? htmlspecialchars($productNumber) : htmlspecialchars($product['id'] ?? '-');
                                ?></td>
                                <td style="text-align: center;">
                                    <?php 
                                    $sellerId = $product['seller_user_id'] ?? $product['seller_id'] ?? '-';
                                    if ($sellerId && $sellerId !== '-') {
                                        echo '<a href="/MVNO/admin/users/seller-detail.php?user_id=' . urlencode($sellerId) . '" style="color: #3b82f6; text-decoration: none; font-weight: 600;">' . htmlspecialchars($sellerId) . '</a>';
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
                                <td style="text-align: right;"><?php echo number_format($product['review_count'] ?? 0); ?></td>
                                <td style="text-align: right;"><?php echo number_format($product['application_count'] ?? 0); ?></td>
                                <td style="text-align: center;">
                                    <span class="badge <?php echo ($product['status'] ?? 'active') === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo ($product['status'] ?? 'active') === 'active' ? '판매중' : '판매종료'; ?>
                                    </span>
                                </td>
                                <td style="text-align: center;"><?php echo isset($product['created_at']) ? date('Y-m-d', strtotime($product['created_at'])) : '-'; ?></td>
                                <td style="text-align: center;">
                                    <div class="action-buttons">
                                        <a href="/MVNO/mvno/mvno-plan-detail.php?id=<?php echo $product['id']; ?>" target="_blank" class="btn btn-sm btn-edit">보기</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- 페이지네이션 -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php
                        $queryParams = [];
                        if ($status) $queryParams['status'] = $status;
                        if ($seller_id) $queryParams['seller_id'] = $seller_id;
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
                        $queryString = http_build_query($queryParams);
                        ?>
                        <a href="?<?php echo $queryString; ?>&page=<?php echo max(1, $page - 1); ?>" 
                           class="pagination-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">이전</a>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?<?php echo $queryString; ?>&page=<?php echo $i; ?>" 
                               class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <a href="?<?php echo $queryString; ?>&page=<?php echo min($totalPages, $page + 1); ?>" 
                           class="pagination-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">다음</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 모달 -->
<div id="confirmModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-title">판매종료 확인</div>
        <div class="modal-message" id="confirmMessage"></div>
        <div class="modal-buttons">
            <button class="modal-btn modal-btn-cancel" onclick="closeConfirmModal()">취소</button>
            <button class="modal-btn modal-btn-confirm" onclick="confirmBulkInactive()">확인</button>
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
let pendingProductIds = [];

function showConfirmModal(message) {
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('confirmModal').classList.add('show');
    document.body.classList.add('modal-open');
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.remove('show');
    document.body.classList.remove('modal-open');
    pendingProductIds = [];
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
    
    const searchQuery = document.getElementById('filter_search_query').value.trim();
    if (searchQuery) params.set('search_query', searchQuery);
    
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
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

// 일괄 판매종료
function bulkInactive() {
    const checkboxes = document.querySelectorAll('.product-checkbox:checked');
    
    if (checkboxes.length === 0) {
        showAlertModal('알림', '선택한 상품이 없습니다.\n판매종료할 상품을 선택해주세요.');
        return;
    }
    
    const productIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
    pendingProductIds = productIds;
    
    showConfirmModal(`선택한 ${checkboxes.length}개의 상품을 판매종료 처리하시겠습니까?\n\n이 작업은 취소할 수 없습니다.`);
}

function confirmBulkInactive() {
    if (pendingProductIds.length === 0) {
        closeConfirmModal();
        return;
    }
    
    // product_ids를 별도 변수에 저장 (closeConfirmModal에서 초기화되기 전에)
    const productIds = [...pendingProductIds];
    closeConfirmModal();
    
    fetch('/MVNO/api/admin-product-bulk-update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_ids: productIds,
            status: 'inactive'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlertModal('성공', `성공적으로 ${productIds.length}개의 상품이 판매종료 처리되었습니다.`);
            // 성공 시 페이지 새로고침을 위해 플래그 저장
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlertModal('오류', data.message || '상품 상태 변경에 실패했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlertModal('오류', '상품 상태 변경 중 오류가 발생했습니다.\n잠시 후 다시 시도해주세요.');
    });
}

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
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
    
    // 체크박스 상태 동기화
    const checkboxes = document.querySelectorAll('.product-checkbox');
    const selectAll = document.getElementById('selectAll');
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            const someChecked = Array.from(checkboxes).some(cb => cb.checked);
            selectAll.checked = allChecked;
            selectAll.indeterminate = someChecked && !allChecked;
        });
    });
    
    // 필드 표시 여부 확인
    toggleContractPeriodInput();
    togglePriceAfterInput();
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

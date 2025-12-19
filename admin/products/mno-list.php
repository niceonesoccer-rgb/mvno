<?php
/**
 * 통신사폰 상품 목록 페이지 (관리자)
 * 경로: /admin/products/mno-list.php
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/product-functions.php';

// 검색 필터
$status = $_GET['status'] ?? '';
if ($status === '') {
    $status = null;
}
$search_query = $_GET['search_query'] ?? ''; // 통합 검색 필드
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = intval($_GET['per_page'] ?? 20);
if (!in_array($perPage, [10, 20, 50, 100])) {
    $perPage = 20;
}

// DB에서 통신사폰 상품 목록 가져오기
$products = [];
$totalProducts = 0;
$totalPages = 1;
$errorMessage = '';
$debugInfo = '';
$isDebug = (isset($_GET['debug']) && $_GET['debug'] === '1' && function_exists('isAdmin') && isAdmin());

try {
    $pdo = getDBConnection();
    if ($pdo) {
        // WHERE 조건 구성
        $whereConditions = ["p.product_type = 'mno'"];
        $params = [];
        
        // 상태 필터
        if ($status && $status !== '') {
            $whereConditions[] = 'p.status = :status';
            $params[':status'] = $status;
        } else {
            $whereConditions[] = "p.status != 'deleted'";
        }
        
        // 통합 검색 필터 (단말기명만 SQL에서 처리)
        if ($search_query && $search_query !== '') {
            $whereConditions[] = '(mno.device_name IS NOT NULL AND mno.device_name LIKE :search_query)';
            $params[':search_query'] = '%' . $search_query . '%';
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
        
        // 전체 개수 조회 (통합 검색 전)
        try {
            $countStmt = $pdo->prepare("
                SELECT COUNT(DISTINCT p.id) as total
                FROM products p
                INNER JOIN product_mno_details mno ON p.id = mno.product_id
                WHERE {$whereClause}
            ");
            $countStmt->execute($params);
            $totalProducts = $countStmt->fetch()['total'];
            $totalPages = ceil($totalProducts / $perPage);
        } catch (PDOException $e) {
            $errorMessage = "카운트 쿼리 오류: " . htmlspecialchars($e->getMessage());
            if (!empty($e->errorInfo)) {
                $errorMessage .= " (SQL State: " . htmlspecialchars($e->errorInfo[0] ?? '') . ", Error: " . htmlspecialchars($e->errorInfo[2] ?? '') . ")";
            }
            $errorMessage .= "<br>WHERE 절: " . htmlspecialchars($whereClause);
            throw $e;
        }
        
        // 상품 목록 조회
        // 통합 검색이 있으면 모든 데이터를 가져온 후 필터링, 없으면 페이지네이션 적용
        try {
            if ($search_query && $search_query !== '') {
                // 통합 검색: 모든 데이터 가져온 후 필터링
                $stmt = $pdo->prepare("
                    SELECT 
                        p.*,
                        p.seller_id,
                        mno.device_name AS product_name,
                        mno.device_price,
                        mno.price_main AS monthly_fee,
                        mno.common_provider,
                        mno.contract_provider
                    FROM products p
                    INNER JOIN product_mno_details mno ON p.id = mno.product_id
                    WHERE {$whereClause}
                    ORDER BY p.id DESC
                ");
                
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->execute();
                $products = $stmt->fetchAll();
            } else {
                // 일반 조회: 페이지네이션 적용
                $offset = ($page - 1) * $perPage;
                $stmt = $pdo->prepare("
                    SELECT 
                        p.*,
                        p.seller_id,
                        mno.device_name AS product_name,
                        mno.device_price,
                        mno.price_main AS monthly_fee,
                        mno.common_provider,
                        mno.contract_provider
                    FROM products p
                    INNER JOIN product_mno_details mno ON p.id = mno.product_id
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
            }
        } catch (PDOException $e) {
            $errorMessage = "상품 목록 조회 오류: " . htmlspecialchars($e->getMessage());
            if (!empty($e->errorInfo)) {
                $errorMessage .= " (SQL State: " . htmlspecialchars($e->errorInfo[0] ?? '') . ", Error: " . htmlspecialchars($e->errorInfo[2] ?? '') . ")";
            }
            $errorMessage .= "<br>WHERE 절: " . htmlspecialchars($whereClause);
            $errorMessage .= "<br>파라미터: " . htmlspecialchars(json_encode($params, JSON_UNESCAPED_UNICODE));
            throw $e;
        }
        
        // 디버깅: 쿼리 결과 확인
        if ($isDebug && empty($products) && $totalProducts == 0) {
            // 실제로 상품이 있는지 확인
            try {
                $debugStmt = $pdo->query("SELECT COUNT(*) as cnt FROM products WHERE product_type = 'mno' AND status != 'deleted'");
                $debugResult = $debugStmt->fetch();
                $allCount = $debugResult['cnt'] ?? 0;
                
                $debugStmt2 = $pdo->query("SELECT COUNT(*) as cnt FROM products p INNER JOIN product_mno_details mno ON p.id = mno.product_id WHERE p.product_type = 'mno' AND p.status != 'deleted'");
                $debugResult2 = $debugStmt2->fetch();
                $withJoinCount = $debugResult2['cnt'] ?? 0;
                
                $debugInfo = "디버그 정보: products 테이블의 MNO 상품 수 = {$allCount}개, JOIN 후 상품 수 = {$withJoinCount}개";
                if ($allCount > 0 && $withJoinCount == 0) {
                    $debugInfo .= " (상세 정보가 없어서 JOIN 결과가 없습니다)";
                }
                
                error_log("MNO Products Debug: All products={$allCount}, With details={$withJoinCount}, Where clause={$whereClause}");
            } catch (Exception $e) {
                $debugInfo = "디버그 오류: " . $e->getMessage();
                error_log("MNO Products Debug Error: " . $e->getMessage());
            }
        }
        
        // 판매자 정보 매핑 (DB-only)
        $sellerIds = [];
        foreach ($products as $p) {
            $sid = (string)($p['seller_id'] ?? '');
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
            $sellerId = (string)($product['seller_id'] ?? '');
            $product['seller_user_id'] = $sellerId; // 표시용
            if ($sellerId && isset($sellerMap[$sellerId])) {
                $product['seller_name'] = $sellerMap[$sellerId]['display_name'] ?? '-';
                $product['company_name'] = $sellerMap[$sellerId]['company_name'] ?? '-';
            } else {
                $product['seller_name'] = '-';
                $product['company_name'] = '-';
            }
            
            // 통신사 정보 추출 (common_provider 또는 contract_provider에서)
            $provider = '-';
            if (!empty($product['common_provider'])) {
                $commonProviders = json_decode($product['common_provider'], true);
                if (is_array($commonProviders) && !empty($commonProviders)) {
                    $provider = implode(', ', array_filter($commonProviders));
                }
            }
            if ($provider === '-' && !empty($product['contract_provider'])) {
                $contractProviders = json_decode($product['contract_provider'], true);
                if (is_array($contractProviders) && !empty($contractProviders)) {
                    $provider = implode(', ', array_filter($contractProviders));
                }
            }
            $product['provider'] = $provider;
        }
        unset($product);
        
        // 통합 검색 필터링 (판매자 ID, 판매자명, 회사명 검색)
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
                
                // 단말기명 검색 (이미 SQL에서 처리했지만 추가 검증)
                $deviceName = mb_strtolower($product['product_name'] ?? '', 'UTF-8');
                if (mb_strpos($deviceName, $searchLower) !== false) {
                    return true;
                }
                
                return false;
            });
            $products = array_values($products);
        }
        
        // 통합 검색으로 인한 필터링 후 페이지네이션 재계산
        if ($search_query && $search_query !== '') {
            // 판매자 정보로 필터링된 경우 전체 개수 재계산
            $totalProducts = count($products);
            $totalPages = ceil($totalProducts / $perPage);
            
            // 페이지네이션 적용
            $offset = ($page - 1) * $perPage;
            $products = array_slice($products, $offset, $perPage);
        } else {
            // 통합 검색이 없는 경우 기존 페이지네이션 사용 (이미 계산됨)
            // totalPages는 이미 계산되어 있음
        }
    }
} catch (PDOException $e) {
    $errorMessage = "데이터베이스 오류: " . htmlspecialchars($e->getMessage());
    if (!empty($e->errorInfo)) {
        $errorMessage .= " (SQL State: " . htmlspecialchars($e->errorInfo[0] ?? '') . ")";
    }
    error_log("Error fetching MNO products: " . $e->getMessage());
    error_log("SQL Error Info: " . json_encode($e->errorInfo ?? []));
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
        margin-bottom: 8px;
    }
    
    .page-header p {
        font-size: 16px;
        color: #6b7280;
    }
    
    .btn {
        padding: 12px 24px;
        font-size: 15px;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary {
        background: #10b981;
        color: white;
    }
    
    .btn-primary:hover {
        background: #059669;
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
        text-align: left;
    }
    
    .filter-select {
        padding: 8px 12px;
        font-size: 14px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: white;
        cursor: pointer;
        text-align: left;
    }
    
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
    }
    
    .search-button:hover {
        background: #059669;
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
    
    .bulk-actions {
        display: flex;
        gap: 8px;
        margin-bottom: 16px;
    }
    
    .btn-secondary {
        background: #6b7280;
        color: white;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 600;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-secondary:hover {
        background: #4b5563;
    }
    
    .btn-danger {
        background: #ef4444;
        color: white;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 600;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-danger:hover {
        background: #dc2626;
    }
    
    .product-checkbox,
    #selectAll {
        width: 18px;
        height: 18px;
        cursor: pointer;
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
    }
    
    .btn-edit:hover {
        background: #2563eb;
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
    
    .error-message {
        background: #fee2e2;
        border: 1px solid #fca5a5;
        color: #991b1b;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
        line-height: 1.6;
    }
    
    .debug-info {
        background: #fef3c7;
        border: 1px solid #fcd34d;
        color: #92400e;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
        line-height: 1.6;
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

<div class="product-list-container">
    <!-- 상품 관리 네비게이션 탭 -->
    <div class="product-nav-tabs">
        <a href="/MVNO/admin/products/mvno-list.php" class="product-nav-tab">알뜰폰 관리</a>
        <a href="/MVNO/admin/products/mno-list.php" class="product-nav-tab active">통신사폰 관리</a>
        <a href="/MVNO/admin/products/internet-list.php" class="product-nav-tab">인터넷 관리</a>
    </div>
    
    <div class="page-header">
        <div>
            <h1 style="margin: 0;">통신사폰 상품 관리</h1>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <label style="font-size: 14px; color: #374151; font-weight: 600;">페이지 수:</label>
            <select class="filter-select" id="per_page_select" onchange="changePerPage()" style="width: 80px;">
                <option value="10" <?php echo $perPage === 10 ? 'selected' : ''; ?>>10개</option>
                <option value="20" <?php echo $perPage === 20 ? 'selected' : ''; ?>>20개</option>
                <option value="50" <?php echo $perPage === 50 ? 'selected' : ''; ?>>50개</option>
                <option value="100" <?php echo $perPage === 100 ? 'selected' : ''; ?>>100개</option>
            </select>
        </div>
    </div>
    
    <?php if (!empty($errorMessage)): ?>
        <div class="error-message">
            <strong>오류 발생:</strong><br>
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($isDebug && !empty($debugInfo)): ?>
        <div class="debug-info">
            <strong>디버그 정보:</strong><br>
            <?php echo htmlspecialchars($debugInfo); ?>
        </div>
    <?php endif; ?>
    
    <!-- 필터 바 -->
    <div class="filter-bar">
        <div class="filter-content">
            <div class="filter-row">
                <div class="filter-group" style="margin-right: -8px;">
                    <label class="filter-label" style="text-align: right;">상태:</label>
                    <select class="filter-select" id="filter_status">
                        <option value="">전체</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>판매중</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>판매종료</option>
                    </select>
                </div>
                
                <div class="filter-group" style="margin-left: -8px; margin-right: -8px;">
                    <label class="filter-label" style="text-align: right;">등록일:</label>
                    <div class="filter-input-group">
                        <input type="date" class="filter-input" id="filter_date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        <span style="color: #6b7280;">~</span>
                        <input type="date" class="filter-input" id="filter_date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                </div>
                
                <div class="filter-group" style="flex: 1; margin-left: -8px;">
                    <label class="filter-label" style="text-align: right;">통합 검색:</label>
                    <input type="text" class="filter-input" id="filter_search_query" placeholder="판매자 ID / 판매자명 / 회사명 / 단말기명 검색" value="<?php echo htmlspecialchars($search_query); ?>" style="width: 100%;">
                </div>
            </div>
        </div>
        
        <div class="filter-buttons">
            <button class="search-button" onclick="applyFilters()" style="width: 100%; text-align: right;">검색</button>
            <button class="search-button" onclick="resetFilters()" style="background: #6b7280; width: 100%; text-align: right;">초기화</button>
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
                        <th style="text-align: center;">단말기명</th>
                        <th style="text-align: right;">가격</th>
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
                                $productNumber = getProductNumberByType($product['id'], 'mno');
                                echo $productNumber ? htmlspecialchars($productNumber) : htmlspecialchars($product['id'] ?? '-');
                            ?></td>
                            <td style="text-align: center;">
                                <?php 
                                $sellerId = $product['seller_user_id'] ?? $product['seller_id'] ?? '-';
                                if ($sellerId && $sellerId !== '-') {
                                    echo '<a href="/MVNO/admin/users/member-detail.php?user_id=' . urlencode($sellerId) . '" style="color: #3b82f6; text-decoration: none; font-weight: 600;">' . htmlspecialchars($sellerId) . '</a>';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($product['seller_name'] ?? '-'); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($product['company_name'] ?? '-'); ?></td>
                            <td style="text-align: left;"><?php echo htmlspecialchars($product['product_name'] ?? '-'); ?></td>
                            <td style="text-align: right;">
                                <?php 
                                $devicePrice = $product['device_price'] ?? null;
                                if ($devicePrice !== null && $devicePrice !== '') {
                                    echo number_format(floatval($devicePrice)) . '원';
                                } else {
                                    echo '-';
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
                                    <a href="/MVNO/mno/mno-phone-detail.php?id=<?php echo $product['id']; ?>" target="_blank" class="btn btn-sm btn-edit">보기</a>
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
                $queryParams = [];
                if ($status) $queryParams['status'] = $status;
                if ($search_query) $queryParams['search_query'] = $search_query;
                if ($date_from) $queryParams['date_from'] = $date_from;
                if ($date_to) $queryParams['date_to'] = $date_to;
                $queryParams['per_page'] = $perPage;
                $queryString = http_build_query($queryParams);
                ?>
                
                <!-- 이전 버튼 -->
                <a href="?<?php echo $queryString; ?>&page=<?php echo max(1, $page - 1); ?>" 
                   class="pagination-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">이전</a>
                
                <!-- 모든 페이지 번호 표시 -->
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?<?php echo $queryString; ?>&page=<?php echo $i; ?>" 
                       class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <!-- 다음 버튼 -->
                <a href="?<?php echo $queryString; ?>&page=<?php echo min($totalPages, $page + 1); ?>" 
                   class="pagination-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">다음</a>
            </div>
        <?php endif; ?>
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


function applyFilters() {
    const params = new URLSearchParams();
    
    // 상태
    const status = document.getElementById('filter_status').value;
    if (status && status !== '') {
        params.set('status', status);
    }
    
    // 통합 검색
    const searchQuery = document.getElementById('filter_search_query').value.trim();
    if (searchQuery) {
        params.set('search_query', searchQuery);
    }
    
    // 등록일
    const dateFrom = document.getElementById('filter_date_from').value;
    if (dateFrom) {
        params.set('date_from', dateFrom);
    }
    
    const dateTo = document.getElementById('filter_date_to').value;
    if (dateTo) {
        params.set('date_to', dateTo);
    }
    
    // per_page 유지
    const perPage = new URLSearchParams(window.location.search).get('per_page');
    if (perPage) {
        params.set('per_page', perPage);
    }
    
    // 필터 변경 시 첫 페이지로
    params.delete('page');
    
    window.location.href = '?' + params.toString();
}

function changePerPage() {
    const perPage = document.getElementById('per_page_select').value;
    const params = new URLSearchParams(window.location.search);
    
    // 모든 필터 파라미터 유지
    params.set('per_page', perPage);
    params.set('page', '1'); // 첫 페이지로 이동
    
    window.location.href = '?' + params.toString();
}

function resetFilters() {
    document.getElementById('filter_status').value = '';
    document.getElementById('filter_search_query').value = '';
    document.getElementById('filter_date_from').value = '';
    document.getElementById('filter_date_to').value = '';
    
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

// Enter 키로 검색
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('filter_search_query');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyFilters();
            }
        });
    }
    
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
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>


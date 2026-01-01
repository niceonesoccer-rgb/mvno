<?php
/**
 * 알뜰폰 상품 목록 페이지
 * 경로: /seller/products/mvno-list.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/product-functions.php';

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

// 검색 필터
$status = $_GET['status'] ?? '';
if ($status === '') {
    $status = null;
}
$provider = $_GET['provider'] ?? '';
$plan_name = $_GET['plan_name'] ?? '';
$contract_period = $_GET['contract_period'] ?? '';
$contract_period_days_min = $_GET['contract_period_days_min'] ?? '';
$contract_period_days_max = $_GET['contract_period_days_max'] ?? '';
$price_after_type = $_GET['price_after_type'] ?? ''; // 'free' or 'amount'
$price_after_min = $_GET['price_after_min'] ?? '';
$price_after_max = $_GET['price_after_max'] ?? '';
$service_type = $_GET['service_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = intval($_GET['per_page'] ?? 10);
// 허용된 per_page 값만 사용 (10, 20, 50, 100, 500)
if (!in_array($perPage, [10, 20, 50, 100, 500])) {
    $perPage = 10;
}

// DB에서 알뜰폰 상품 목록 가져오기
$products = [];
$totalProducts = 0;
$totalPages = 1;

try {
    $pdo = getDBConnection();
    if ($pdo) {
        // WHERE 조건 구성
        $sellerId = (string)$currentUser['user_id'];
        $whereConditions = ['p.seller_id = :seller_id', "p.product_type = 'mvno'"];
        $params = [':seller_id' => $sellerId];
        
        // 상태 필터
        if ($status && $status !== '') {
            $whereConditions[] = 'p.status = :status';
            $params[':status'] = $status;
        } else {
            $whereConditions[] = "p.status != 'deleted'";
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
                // 기간입력일 때는 contract_period_days 구간으로 검색
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
            // 금액입력일 때는 구간 검색
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
        
        // 데이터속도 필터 (service_type)
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
        
        // mvno 필터가 있는지 확인
        $hasMvnoFilters = !empty($provider) || !empty($plan_name) || !empty($contract_period) || 
                          !empty($price_after_type) || !empty($service_type);
        
        // 전체 개수 조회 (상세 정보가 있는 상품만 - statistics와 동일한 기준)
        $countStmt = $pdo->prepare("
            SELECT COUNT(DISTINCT p.id) as total
            FROM products p
            LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id
            WHERE {$whereClause}
            AND mvno.product_id IS NOT NULL
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
                COALESCE(prs.total_review_count, 0) AS review_count,
                CASE WHEN EXISTS (
                    SELECT 1 FROM rotation_advertisements ra 
                    WHERE ra.product_id = p.id 
                    AND ra.status = 'active' 
                    AND ra.end_datetime > NOW()
                ) THEN 1 ELSE 0 END AS has_active_ad
            FROM products p
            INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
            LEFT JOIN product_review_statistics prs ON p.id = prs.product_id
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
    error_log("Error fetching MVNO products: " . $e->getMessage());
}

// 예치금 잔액 조회 (광고 신청 모달용)
$balance = 0;
try {
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT balance FROM seller_deposit_accounts WHERE seller_id = :seller_id");
        $stmt->execute([':seller_id' => (string)$currentUser['user_id']]);
        $balanceData = $stmt->fetch(PDO::FETCH_ASSOC);
        $balance = floatval($balanceData['balance'] ?? 0);
    }
} catch (PDOException $e) {
    error_log("Error fetching balance: " . $e->getMessage());
}

$advertisementDaysOptions = [1, 2, 3, 5, 7, 10, 14, 30];

// 페이지별 스타일
$pageStyles = '
    .product-list-container {
        width: 100%;
        padding: 0 20px;
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
    }
    
    .filter-label {
        font-size: 14px;
        font-weight: 600;
        color: #374151;
    }
    
    .filter-select {
        padding: 8px 12px;
        font-size: 14px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: white;
        cursor: pointer;
    }
    
    .filter-input {
        padding: 8px 12px;
        font-size: 14px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: white;
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
    
    .product-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #10b981;
    }
    
    .product-table th.checkbox-column {
        width: 40px;
        text-align: center;
    }
    
    .product-table td.checkbox-column {
        text-align: center;
        padding: 16px 8px;
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
    
    .btn-copy {
        background: #10b981;
        color: white;
    }
    
    .btn-copy:hover {
        background: #059669;
    }
    
    .btn-delete {
        background: #ef4444;
        color: white;
    }
    
    .btn-delete:hover {
        background: #dc2626;
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
        background: transparent;
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
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        min-width: 40px;
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
    
    .pagination-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
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
';

include __DIR__ . '/../includes/seller-header.php';
?>

<div class="product-list-container">
    <div class="page-header">
        <h1>알뜰폰 등록상품</h1>
        <div style="display: flex; align-items: center; gap: 8px;">
            <label style="font-size: 14px; color: #374151; font-weight: 600;">페이지당 표시:</label>
            <select class="filter-select" id="per_page_select" onchange="changePerPage()" style="width: 80px;">
                <option value="10" <?php echo $perPage === 10 ? 'selected' : ''; ?>>10개</option>
                <option value="20" <?php echo $perPage === 20 ? 'selected' : ''; ?>>20개</option>
                <option value="50" <?php echo $perPage === 50 ? 'selected' : ''; ?>>50개</option>
                <option value="100" <?php echo $perPage === 100 ? 'selected' : ''; ?>>100개</option>
                <option value="500" <?php echo $perPage === 500 ? 'selected' : ''; ?>>500개</option>
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
                        <option value="" <?php echo ($status === null || $status === '') ? 'selected' : ''; ?>>전체</option>
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
                    <label class="filter-label">요금제명:</label>
                    <input type="text" class="filter-input" id="filter_plan_name" placeholder="요금제명 검색" value="<?php echo htmlspecialchars($plan_name); ?>" style="width: 200px;">
                </div>
                
                <div class="filter-group" style="display: flex; align-items: center; gap: 8px;">
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
                    <label class="filter-label">데이터속도:</label>
                    <select class="filter-select" id="filter_service_type">
                        <option value="">전체</option>
                        <option value="LTE" <?php echo $service_type === 'LTE' ? 'selected' : ''; ?>>LTE</option>
                        <option value="5G" <?php echo $service_type === '5G' ? 'selected' : ''; ?>>5G</option>
                        <option value="6G" <?php echo $service_type === '6G' ? 'selected' : ''; ?>>6G</option>
                    </select>
                </div>
                
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
            </div>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 8px; min-width: 100px;">
            <button type="button" class="search-button" onclick="applyFilters()">검색</button>
            <button type="button" class="search-button secondary" onclick="resetFilters()">초기화</button>
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
                <div class="empty-state-text">새로운 알뜰폰 상품을 등록해보세요</div>
                <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                    <a href="/MVNO/seller/products/mvno.php" class="btn btn-primary">알뜰폰 등록</a>
                </div>
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
                    <option value="copy">복사</option>
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
                            <td class="checkbox-column">
                                <input type="checkbox" class="product-checkbox product-checkbox-item" 
                                       value="<?php echo $product['id']; ?>" 
                                       onchange="updateBulkActions()">
                            </td>
                            <td style="text-align: center;"><?php 
                                $productNumber = getProductNumberByType($product['id'], 'mvno', $sellerId);
                                echo $productNumber ? htmlspecialchars($productNumber) : htmlspecialchars($product['id'] ?? '-');
                            ?></td>
                            <td style="text-align: left;">
                                <a href="javascript:void(0);" onclick="showProductInfo(<?php echo $product['id']; ?>, 'mvno')" style="color: #3b82f6; text-decoration: none; font-weight: 600; cursor: pointer;">
                                    <?php echo htmlspecialchars($product['product_name'] ?? '-'); ?>
                                </a>
                            </td>
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
                                    <button class="btn btn-sm btn-edit" onclick="editProduct(<?php echo $product['id']; ?>)">수정</button>
                                    <button class="btn btn-sm btn-copy" onclick="copyProduct(<?php echo $product['id']; ?>)">복사</button>
                                    <?php 
                                    $hasActiveAd = intval($product['has_active_ad'] ?? 0);
                                    if ($hasActiveAd): 
                                    ?>
                                        <span class="btn-sm" style="background: #f59e0b; color: white; border: none; padding: 4px 12px; border-radius: 4px; margin-left: 4px; font-size: 12px; font-weight: 600; display: inline-block;">광고중</span>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm" onclick="openAdModal(<?php echo $product['id']; ?>, 'mvno', '<?php echo htmlspecialchars($product['product_name'] ?? '', ENT_QUOTES); ?>')" style="background: #6366f1; color: white; border: none; padding: 4px 12px; border-radius: 4px; margin-left: 4px; font-size: 12px; cursor: pointer;">광고</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- 페이지네이션 -->
            <?php if ($totalPages > 1): ?>
                <?php
                // 페이지 그룹 계산 (10개씩 그룹화)
                $pageGroupSize = 10;
                if ($totalPages <= $pageGroupSize) {
                    // 10개 이하면 모두 표시
                    $startPage = 1;
                    $endPage = $totalPages;
                    $prevGroupLastPage = 0;
                    $nextGroupFirstPage = $totalPages + 1;
                } else {
                    // 10개 넘으면 그룹화
                    $currentGroup = ceil($page / $pageGroupSize);
                    $startPage = ($currentGroup - 1) * $pageGroupSize + 1;
                    $endPage = min($currentGroup * $pageGroupSize, $totalPages);
                    $prevGroupLastPage = ($currentGroup - 1) * $pageGroupSize;
                    $nextGroupFirstPage = $currentGroup * $pageGroupSize + 1;
                }
                
                $queryParams = [];
                if ($status) $queryParams['status'] = $status;
                if ($provider) $queryParams['provider'] = $provider;
                if ($plan_name) $queryParams['plan_name'] = $plan_name;
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
                <div class="pagination" style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 20px;">
                    <?php if ($prevGroupLastPage > 0): ?>
                        <a href="?<?php echo $queryString; ?>&page=<?php echo $prevGroupLastPage; ?>" 
                           class="pagination-btn">이전</a>
                    <?php else: ?>
                        <span class="pagination-btn disabled">이전</span>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?<?php echo $queryString; ?>&page=<?php echo $i; ?>" 
                           class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($nextGroupFirstPage <= $totalPages): ?>
                        <a href="?<?php echo $queryString; ?>&page=<?php echo $nextGroupFirstPage; ?>" 
                           class="pagination-btn">다음</a>
                    <?php else: ?>
                        <span class="pagination-btn disabled">다음</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function applyFilters() {
    const params = new URLSearchParams();
    
    // 상태
    const status = document.getElementById('filter_status').value;
    if (status && status !== '') {
        params.set('status', status);
    }
    
    // 통신사
    const provider = document.getElementById('filter_provider').value;
    if (provider && provider !== '') {
        params.set('provider', provider);
    }
    
    // 요금제명
    const planName = document.getElementById('filter_plan_name').value.trim();
    if (planName) {
        params.set('plan_name', planName);
    }
    
    // 약정기간
    const contractPeriod = document.getElementById('filter_contract_period').value;
    if (contractPeriod && contractPeriod !== '') {
        params.set('contract_period', contractPeriod);
        
        // 기간입력일 때 구간 값 추가
        if (contractPeriod === '기간입력') {
            const contractPeriodDaysMin = document.getElementById('filter_contract_period_days_min').value.trim();
            if (contractPeriodDaysMin) {
                params.set('contract_period_days_min', contractPeriodDaysMin);
            }
            
            const contractPeriodDaysMax = document.getElementById('filter_contract_period_days_max').value.trim();
            if (contractPeriodDaysMax) {
                params.set('contract_period_days_max', contractPeriodDaysMax);
            }
        }
    }
    
    // 할인 후 요금 타입
    const priceAfterType = document.getElementById('filter_price_after_type').value;
    if (priceAfterType && priceAfterType !== '') {
        params.set('price_after_type', priceAfterType);
        
        // 금액입력일 때 구간 값 추가
        if (priceAfterType === 'amount') {
            const priceAfterMin = document.getElementById('filter_price_after_min').value.trim();
            if (priceAfterMin) {
                params.set('price_after_min', priceAfterMin);
            }
            
            const priceAfterMax = document.getElementById('filter_price_after_max').value.trim();
            if (priceAfterMax) {
                params.set('price_after_max', priceAfterMax);
            }
        }
    }
    
    // 데이터속도 (service_type)
    const serviceType = document.getElementById('filter_service_type').value;
    if (serviceType && serviceType !== '') {
        params.set('service_type', serviceType);
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

function toggleContractPeriodInput() {
    const contractPeriod = document.getElementById('filter_contract_period').value;
    const wrapper = document.getElementById('contract_period_days_wrapper');
    
    if (contractPeriod === '기간입력') {
        wrapper.style.display = 'flex';
    } else {
        wrapper.style.display = 'none';
        document.getElementById('filter_contract_period_days_min').value = '';
        document.getElementById('filter_contract_period_days_max').value = '';
    }
}

function togglePriceAfterInput() {
    const priceAfterType = document.getElementById('filter_price_after_type').value;
    const wrapper = document.getElementById('price_after_amount_wrapper');
    
    if (priceAfterType === 'amount') {
        wrapper.style.display = 'flex';
    } else {
        wrapper.style.display = 'none';
        document.getElementById('filter_price_after_min').value = '';
        document.getElementById('filter_price_after_max').value = '';
    }
}

function resetFilters() {
    document.getElementById('filter_status').value = '';
    document.getElementById('filter_provider').value = '';
    document.getElementById('filter_plan_name').value = '';
    document.getElementById('filter_contract_period').value = '';
    document.getElementById('filter_contract_period_days_min').value = '';
    document.getElementById('filter_contract_period_days_max').value = '';
    
    // 할인 후 요금 셀렉트박스 초기화
    document.getElementById('filter_price_after_type').value = '';
    document.getElementById('filter_price_after_min').value = '';
    document.getElementById('filter_price_after_max').value = '';
    
    document.getElementById('filter_service_type').value = '';
    document.getElementById('filter_date_from').value = '';
    document.getElementById('filter_date_to').value = '';
    
    // 필드 숨기기
    document.getElementById('contract_period_days_wrapper').style.display = 'none';
    document.getElementById('price_after_amount_wrapper').style.display = 'none';
    
    window.location.href = window.location.pathname;
}

// Enter 키로 검색
document.addEventListener('DOMContentLoaded', function() {
    const searchInputs = [
        'filter_plan_name',
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
    
    // 페이지 로드 시 필드 표시 여부 확인
    toggleContractPeriodInput();
    togglePriceAfterInput();
});

function editProduct(productId) {
    window.location.href = '/MVNO/seller/products/mvno.php?id=' + productId;
}

function copyProduct(productId) {
    const message = '이 상품을 복사하시겠습니까?\n\n※ 복사된 상품은 판매종료 상태로 설정됩니다.';
    if (typeof showConfirm === 'function') {
        showConfirm(message, '상품 복사').then(confirmed => {
            if (confirmed) {
                processCopyProduct(productId);
            }
        });
    } else if (confirm(message)) {
        processCopyProduct(productId);
    }
}

function processCopyProduct(productId) {
    fetch('/MVNO/api/product-copy.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            product_type: 'mvno'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof showAlert === 'function') {
                showAlert('상품이 복사되었습니다.\n복사된 상품은 판매종료 상태로 설정되었습니다.', '완료');
            } else {
                alert('상품이 복사되었습니다.\n복사된 상품은 판매종료 상태로 설정되었습니다.');
            }
            location.reload();
        } else {
            if (typeof showAlert === 'function') {
                showAlert(data.message || '상품 복사에 실패했습니다.', '오류', true);
            } else {
                alert(data.message || '상품 복사에 실패했습니다.');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showAlert === 'function') {
            showAlert('상품 복사 중 오류가 발생했습니다.', '오류', true);
        } else {
            alert('상품 복사 중 오류가 발생했습니다.');
        }
    });
}

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
    return Array.from(checkboxes).map(cb => cb.value);
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

// 일괄 작업 실행
function executeBulkAction() {
    const selectedIds = getSelectedProductIds();
    const actionSelect = document.getElementById('bulkActionSelect');
    
    if (selectedIds.length === 0) {
        alert('선택된 상품이 없습니다.');
        return;
    }
    
    if (!actionSelect || !actionSelect.value) {
        alert('작업을 선택해주세요.');
        return;
    }
    
    const action = actionSelect.value;
    
    if (action === 'active' || action === 'inactive') {
        bulkChangeStatus(action);
    } else if (action === 'copy') {
        bulkCopyProducts();
    }
}

// 일괄 변경 셀렉트박스 변경 이벤트
document.addEventListener('DOMContentLoaded', function() {
    const bulkActionSelect = document.getElementById('bulkActionSelect');
    if (bulkActionSelect) {
        bulkActionSelect.addEventListener('change', function() {
            const bulkActionBtn = document.getElementById('bulkActionBtn');
            if (bulkActionBtn) {
                bulkActionBtn.disabled = !this.value || getSelectedProductIds().length === 0;
            }
        });
    }
});

function toggleStatusMenu(event) {
    event.stopPropagation();
    const menu = document.getElementById('statusMenu');
    const isVisible = menu.style.display === 'block';
    
    // 다른 메뉴 닫기
    document.querySelectorAll('#statusMenu').forEach(m => {
        if (m !== menu) m.style.display = 'none';
    });
    
    menu.style.display = isVisible ? 'none' : 'block';
}

// 메뉴 외부 클릭 시 닫기
document.addEventListener('click', function(event) {
    const menu = document.getElementById('statusMenu');
    if (menu && !menu.contains(event.target) && !event.target.closest('button[onclick="toggleStatusMenu(event)"]')) {
        menu.style.display = 'none';
    }
});

function bulkChangeStatus(status) {
    const selectedIds = getSelectedProductIds();
    if (selectedIds.length === 0) {
        if (typeof showAlert === 'function') {
            showAlert('선택된 상품이 없습니다.', '알림');
        } else {
            alert('선택된 상품이 없습니다.');
        }
        return;
    }
    
    const productCount = selectedIds.length;
    const statusText = status === 'active' ? '판매중' : '판매종료';
    const message = '선택한 ' + productCount + '개의 상품을 ' + statusText + ' 처리하시겠습니까?';
    
    if (typeof showConfirm === 'function') {
        showConfirm(message, '상태 변경 확인').then(confirmed => {
            if (confirmed) {
                processBulkChangeStatus(selectedIds, status);
            }
        });
    } else if (confirm(message)) {
        processBulkChangeStatus(selectedIds, status);
    }
}

function processBulkChangeStatus(productIds, status) {
    const statusText = status === 'active' ? '판매중' : '판매종료';
    
    fetch('/MVNO/api/product-bulk-update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_ids: productIds,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof showAlert === 'function') {
                showAlert('선택한 상품이 ' + statusText + ' 처리되었습니다.', '완료');
            } else {
                alert('선택한 상품이 ' + statusText + ' 처리되었습니다.');
            }
            location.reload();
        } else {
            if (typeof showAlert === 'function') {
                showAlert(data.message || '상품 상태 변경에 실패했습니다.', '오류', true);
            } else {
                alert(data.message || '상품 상태 변경에 실패했습니다.');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showAlert === 'function') {
            showAlert('상품 상태 변경 중 오류가 발생했습니다.', '오류', true);
        } else {
            alert('상품 상태 변경 중 오류가 발생했습니다.');
        }
    });
}

function bulkCopyProducts() {
    const selectedIds = getSelectedProductIds();
    if (selectedIds.length === 0) {
        if (typeof showAlert === 'function') {
            showAlert('선택된 상품이 없습니다.', '알림');
        } else {
            alert('선택된 상품이 없습니다.');
        }
        return;
    }
    
    const productCount = selectedIds.length;
    const message = '선택한 ' + productCount + '개의 상품을 복사하시겠습니까?\n\n※ 복사된 상품은 판매종료 상태로 설정됩니다.';
    
    if (typeof showConfirm === 'function') {
        showConfirm(message, '상품 복사').then(confirmed => {
            if (confirmed) {
                processBulkCopy(selectedIds);
            }
        });
    } else if (confirm(message)) {
        processBulkCopy(selectedIds);
    }
}

function processBulkCopy(productIds) {
    const totalCount = productIds.length;
    let completedCount = 0;
    let failedCount = 0;
    
    // 각 상품을 순차적으로 복사
    productIds.forEach((productId, index) => {
        fetch('/MVNO/api/product-copy.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                product_type: 'mvno'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                completedCount++;
            } else {
                failedCount++;
            }
            
            // 모든 복사 작업이 완료되면 결과 표시
            if (completedCount + failedCount === totalCount) {
                if (failedCount === 0) {
                    if (typeof showAlert === 'function') {
                        showAlert(totalCount + '개의 상품이 복사되었습니다.\n복사된 상품은 판매종료 상태로 설정되었습니다.', '완료');
                    } else {
                        alert(totalCount + '개의 상품이 복사되었습니다.\n복사된 상품은 판매종료 상태로 설정되었습니다.');
                    }
                    location.reload();
                } else {
                    if (typeof showAlert === 'function') {
                        showAlert(completedCount + '개의 상품이 복사되었습니다.\n' + failedCount + '개의 상품 복사에 실패했습니다.', '알림', true);
                    } else {
                        alert(completedCount + '개의 상품이 복사되었습니다.\n' + failedCount + '개의 상품 복사에 실패했습니다.');
                    }
                    location.reload();
                }
            }
        })
        .catch(error => {
            console.error('Error copying product:', error);
            failedCount++;
            
            if (completedCount + failedCount === totalCount) {
                if (typeof showAlert === 'function') {
                    showAlert(completedCount + '개의 상품이 복사되었습니다.\n' + failedCount + '개의 상품 복사 중 오류가 발생했습니다.', '오류', true);
                } else {
                    alert(completedCount + '개의 상품이 복사되었습니다.\n' + failedCount + '개의 상품 복사 중 오류가 발생했습니다.');
                }
                location.reload();
            }
        });
    });
}
</script>

<!-- 상품 상세 정보 모달 -->
<div id="productInfoModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000; overflow-y: auto;">
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
function showProductInfo(productId, productType) {
    const modal = document.getElementById('productInfoModal');
    const content = document.getElementById('productInfoContent');
    
    if (!modal || !content) {
        console.error('Modal elements not found');
        alert('상품 정보를 불러올 수 없습니다.');
        return;
    }
    
    // 배경 스크롤 방지
    document.body.style.overflow = 'hidden';
    modal.style.display = 'block';
    content.innerHTML = '<div style="text-align: center; padding: 40px; color: #6b7280;">상품 정보를 불러오는 중...</div>';
    
    fetch('/MVNO/api/get-product-info.php?product_id=' + productId + '&product_type=' + productType)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.product) {
                const product = data.product;
                let html = '';
                
                if (productType === 'mvno') {
                    // 판매 상태
                    html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">판매 상태</h3>';
                    html += '<table class="product-info-table">';
                    html += '<tr><th>상태</th><td>' + (product.status === 'active' ? '판매중' : '판매종료') + '</td></tr>';
                    html += '</table></div>';
                    
                    // 요금제
                    html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">요금제</h3>';
                    html += '<table class="product-info-table">';
                    html += '<tr><th>통신사</th><td>' + (product.provider || '-') + '</td></tr>';
                    html += '<tr><th>데이터 속도</th><td>' + (product.service_type || '-') + '</td></tr>';
                    if (product.registration_types) {
                        const regTypes = typeof product.registration_types === 'string' ? JSON.parse(product.registration_types) : product.registration_types;
                        html += '<tr><th>가입 형태</th><td>' + (Array.isArray(regTypes) ? regTypes.join(', ') : regTypes || '-') + '</td></tr>';
                    }
                    html += '<tr><th>요금제명</th><td>' + (product.plan_name || product.product_name || '-') + '</td></tr>';
                    if (product.contract_period) {
                        let contractText = product.contract_period;
                        if (product.contract_period === '직접입력' && product.contract_period_days) {
                            contractText = product.contract_period_days + (product.contract_period_unit || '일');
                        }
                        html += '<tr><th>약정기간</th><td>' + contractText + '</td></tr>';
                    }
                    html += '<tr><th>월 요금</th><td>' + (product.price_main ? number_format(product.price_main) + '원' : '-') + '</td></tr>';
                    if (product.discount_period && product.discount_period !== '프로모션 없음') {
                        let discountText = product.discount_period;
                        if (product.discount_period === '직접입력' && product.discount_period_value) {
                            discountText = product.discount_period_value + (product.discount_period_unit || '개월');
                        }
                        html += '<tr><th>할인기간(프로모션기간)</th><td>' + discountText + '</td></tr>';
                    }
                    if (product.price_after !== null && product.price_after !== undefined) {
                        html += '<tr><th>할인기간요금(프로모션기간요금)</th><td>' + (product.price_after === 0 || product.price_after === '0' ? '공짜' : (product.price_after ? number_format(product.price_after) + '원' : '-')) + '</td></tr>';
                    }
                    html += '</table></div>';
                    
                    // 데이터 정보
                    html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">데이터 정보</h3>';
                    html += '<table class="product-info-table">';
                    if (product.data_amount) {
                        let dataText = product.data_amount;
                        if (product.data_amount === '직접입력' && product.data_amount_value) {
                            dataText = product.data_amount_value + (product.data_unit || 'GB');
                        }
                        html += '<tr><th>데이터 제공량</th><td>' + dataText + '</td></tr>';
                    }
                    if (product.data_additional) {
                        let additionalText = product.data_additional;
                        if (product.data_additional === '직접입력' && product.data_additional_value) {
                            additionalText = product.data_additional_value;
                        }
                        html += '<tr><th>데이터 추가제공</th><td>' + additionalText + '</td></tr>';
                    }
                    if (product.data_exhausted) {
                        let exhaustedText = product.data_exhausted;
                        if (product.data_exhausted === '직접입력' && product.data_exhausted_value) {
                            exhaustedText = product.data_exhausted_value;
                        }
                        html += '<tr><th>데이터 소진시</th><td>' + exhaustedText + '</td></tr>';
                    }
                    if (product.call_type) {
                        let callText = product.call_type;
                        if (product.call_type === '직접입력' && product.call_amount) {
                            callText = product.call_amount + (product.call_amount_unit || '분');
                        }
                        html += '<tr><th>통화</th><td>' + callText + '</td></tr>';
                    }
                    if (product.additional_call_type) {
                        let additionalCallText = product.additional_call_type;
                        if (product.additional_call_type === '직접입력' && product.additional_call) {
                            additionalCallText = product.additional_call + (product.additional_call_unit || '분');
                        }
                        html += '<tr><th>부가·영상통화</th><td>' + additionalCallText + '</td></tr>';
                    }
                    if (product.sms_type) {
                        let smsText = product.sms_type;
                        if (product.sms_type === '직접입력' && product.sms_amount) {
                            smsText = product.sms_amount + (product.sms_amount_unit || '건');
                        }
                        html += '<tr><th>문자</th><td>' + smsText + '</td></tr>';
                    }
                    if (product.mobile_hotspot) {
                        let hotspotText = product.mobile_hotspot;
                        if ((product.mobile_hotspot === '직접입력' || product.mobile_hotspot === '직접선택') && product.mobile_hotspot_value) {
                            hotspotText = product.mobile_hotspot_value + (product.mobile_hotspot_unit || 'GB');
                        }
                        html += '<tr><th>테더링(핫스팟)</th><td>' + hotspotText + '</td></tr>';
                    }
                    html += '</table></div>';
                    
                    // 유심 정보
                    html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">유심 정보</h3>';
                    html += '<table class="product-info-table">';
                    if (product.regular_sim_available) {
                        let regularSimText = product.regular_sim_available;
                        if ((product.regular_sim_available === '배송가능' || product.regular_sim_available === '유심비 유료') && product.regular_sim_price) {
                            regularSimText += ' (' + number_format(product.regular_sim_price) + '원)';
                        }
                        html += '<tr><th>일반유심</th><td>' + regularSimText + '</td></tr>';
                    }
                    if (product.nfc_sim_available) {
                        let nfcSimText = product.nfc_sim_available;
                        if ((product.nfc_sim_available === '배송가능' || product.nfc_sim_available === '유심비 유료') && product.nfc_sim_price) {
                            nfcSimText += ' (' + number_format(product.nfc_sim_price) + '원)';
                        }
                        html += '<tr><th>NFC유심</th><td>' + nfcSimText + '</td></tr>';
                    }
                    if (product.esim_available) {
                        let esimText = product.esim_available;
                        if ((product.esim_available === '개통가능' || product.esim_available === 'eSIM 유료') && product.esim_price) {
                            esimText += ' (' + number_format(product.esim_price) + '원)';
                        }
                        html += '<tr><th>eSIM</th><td>' + esimText + '</td></tr>';
                    }
                    html += '</table></div>';
                    
                    // 기본 제공 초과 시
                    html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">기본 제공 초과 시</h3>';
                    html += '<table class="product-info-table">';
                    if (product.over_data_price) html += '<tr><th>데이터</th><td>' + number_format(product.over_data_price) + (product.over_data_price_unit || '원/MB') + '</td></tr>';
                    if (product.over_voice_price) html += '<tr><th>음성</th><td>' + number_format(product.over_voice_price) + (product.over_voice_price_unit || '원/초') + '</td></tr>';
                    if (product.over_video_price) html += '<tr><th>영상통화</th><td>' + number_format(product.over_video_price) + (product.over_video_price_unit || '원/초') + '</td></tr>';
                    if (product.over_sms_price) html += '<tr><th>단문메시지(SMS)</th><td>' + number_format(product.over_sms_price) + (product.over_sms_price_unit || '원/건') + '</td></tr>';
                    if (product.over_lms_price) html += '<tr><th>텍스트형(LMS)</th><td>' + number_format(product.over_lms_price) + (product.over_lms_price_unit || '원/건') + '</td></tr>';
                    if (product.over_mms_price) html += '<tr><th>멀티미디어형(MMS)</th><td>' + number_format(product.over_mms_price) + (product.over_mms_price_unit || '원/건') + '</td></tr>';
                    html += '</table></div>';
                    
                    // 프로모션 이벤트
                    if (product.promotion_title || (product.promotions && product.promotions.length > 0)) {
                        html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">프로모션 이벤트</h3>';
                        html += '<table class="product-info-table">';
                        if (product.promotion_title) html += '<tr><th>제목</th><td>' + product.promotion_title + '</td></tr>';
                        if (product.promotions) {
                            const promotions = typeof product.promotions === 'string' ? JSON.parse(product.promotions) : product.promotions;
                            if (Array.isArray(promotions) && promotions.length > 0) {
                                html += '<tr><th>항목</th><td>' + promotions.join(', ') + '</td></tr>';
                            }
                        }
                        html += '</table></div>';
                    }
                    
                    // 혜택 및 유의사항
                    if (product.benefits) {
                        const benefits = typeof product.benefits === 'string' ? JSON.parse(product.benefits) : product.benefits;
                        if (Array.isArray(benefits) && benefits.length > 0 && benefits[0]) {
                            html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">혜택 및 유의사항</h3>';
                            html += '<table class="product-info-table">';
                            html += '<tr><th>내용</th><td style="white-space: pre-wrap;">' + benefits[0] + '</td></tr>';
                            html += '</table></div>';
                        }
                    }
                    
                    // 등록일
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
        // 배경 스크롤 복원
        document.body.style.overflow = '';
    }
}

function number_format(number) {
    // 숫자를 파싱
    const numValue = parseFloat(number) || 0;
    
    // 소수점이 없거나 소수점 아래가 0이면 정수로 표시
    if (numValue % 1 === 0) {
        // 정수인 경우
        return Math.floor(numValue).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    } else {
        // 소수점이 있는 경우 - 소수점 아래 값 표시
        // 소수점 아래 불필요한 0 제거 (예: 34000.50 → 34000.5, 34000.00 → 34000)
        const formatted = numValue.toString().replace(/\.?0+$/, '');
        // 천 단위 구분자 추가
        const parts = formatted.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        return parts.join('.');
    }
}

// 모달 외부 클릭 시 닫기
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('productInfoModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeProductInfoModal();
            }
        });
    }
    
    // ESC 키로 모달 닫기
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

<!-- 광고 신청 모달 -->
<div id="adModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 32px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="margin: 0; font-size: 20px; font-weight: 600;">광고 신청</h2>
            <button type="button" onclick="closeAdModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        
        <form id="adForm" onsubmit="submitAdForm(event)">
            <input type="hidden" name="product_id" id="modalProductId">
            
            <div style="margin-bottom: 20px;">
                <div style="padding: 16px; background: #f8fafc; border-radius: 8px; margin-bottom: 16px;">
                    <div style="font-size: 14px; color: #64748b; margin-bottom: 4px;">상품</div>
                    <div style="font-size: 16px; font-weight: 600;" id="modalProductName"></div>
                </div>
                
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    광고 기간 <span style="color: #ef4444;">*</span>
                </label>
                <select name="advertisement_days" id="modalAdvertisementDays" required
                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;">
                    <option value="">광고 기간을 선택하세요</option>
                    <?php foreach ($advertisementDaysOptions as $days): ?>
                        <option value="<?= $days ?>"><?= $days ?>일</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="modalPricePreview" style="margin-bottom: 24px; padding: 20px; background: #f8fafc; border-radius: 8px; display: none;">
                <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">광고 금액</div>
                <div id="modalPriceAmount"></div>
                <div id="modalBalanceCheck" style="margin-top: 12px; font-size: 14px;"></div>
            </div>
            
            <div id="modalMessage" style="margin-bottom: 16px; padding: 12px; border-radius: 8px; display: none;"></div>
            
            <div style="display: flex; gap: 12px;">
                <button type="submit" id="modalSubmitBtn" disabled
                        style="flex: 1; padding: 12px 24px; background: #cbd5e1; color: #64748b; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: not-allowed;">
                    광고 신청
                </button>
                <button type="button" onclick="closeAdModal()" style="flex: 1; padding: 12px 24px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;">
                    취소
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const currentBalance = <?= $balance ?>;
let currentProductType = '';

function openAdModal(productId, productType, productName) {
    document.getElementById('modalProductId').value = productId;
    document.getElementById('modalProductName').textContent = productName;
    document.getElementById('modalAdvertisementDays').value = '';
    document.getElementById('modalPricePreview').style.display = 'none';
    document.getElementById('modalSubmitBtn').disabled = true;
    document.getElementById('modalSubmitBtn').style.background = '#cbd5e1';
    document.getElementById('modalSubmitBtn').style.color = '#64748b';
    document.getElementById('modalSubmitBtn').style.cursor = 'not-allowed';
    document.getElementById('modalMessage').style.display = 'none';
    
    currentProductType = productType;
    document.getElementById('adModal').style.display = 'flex';
}

function closeAdModal() {
    document.getElementById('adModal').style.display = 'none';
}

// 모달 배경 클릭 시 닫기
document.getElementById('adModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeAdModal();
    }
});

async function updateModalPrice() {
    const productId = document.getElementById('modalProductId').value;
    const days = document.getElementById('modalAdvertisementDays').value;
    
    if (!productId || !days || !currentProductType) {
        document.getElementById('modalPricePreview').style.display = 'none';
        document.getElementById('modalSubmitBtn').disabled = true;
        document.getElementById('modalSubmitBtn').style.background = '#cbd5e1';
        document.getElementById('modalSubmitBtn').style.color = '#64748b';
        document.getElementById('modalSubmitBtn').style.cursor = 'not-allowed';
        return;
    }
    
    try {
        const response = await fetch(`/MVNO/api/advertisement-price.php?product_type=${currentProductType}&advertisement_days=${days}`);
        const data = await response.json();
        
        if (data.success && data.price) {
            const supplyAmount = parseFloat(data.price);
            const taxAmount = supplyAmount * 0.1;
            const totalAmount = supplyAmount + taxAmount;
            
            document.getElementById('modalPriceAmount').innerHTML = `
                <div style="font-size: 24px; margin-bottom: 4px;">공급가액: ${new Intl.NumberFormat('ko-KR').format(Math.round(supplyAmount))}원</div>
                <div style="font-size: 14px; color: #64748b; margin-bottom: 4px;">부가세 (10%): ${new Intl.NumberFormat('ko-KR').format(Math.round(taxAmount))}원</div>
                <div style="font-size: 32px; font-weight: 700; color: #6366f1; margin-top: 8px;">입금금액 (부가세 포함): ${new Intl.NumberFormat('ko-KR').format(Math.round(totalAmount))}원</div>
            `;
            document.getElementById('modalPricePreview').style.display = 'block';
            
            if (currentBalance >= totalAmount) {
                document.getElementById('modalBalanceCheck').innerHTML = '<span style="color: #10b981;">✓ 예치금 잔액이 충분합니다.</span>';
                document.getElementById('modalSubmitBtn').disabled = false;
                document.getElementById('modalSubmitBtn').style.background = '#6366f1';
                document.getElementById('modalSubmitBtn').style.color = '#fff';
                document.getElementById('modalSubmitBtn').style.cursor = 'pointer';
            } else {
                document.getElementById('modalBalanceCheck').innerHTML = '<span style="color: #ef4444;">✗ 예치금 잔액이 부족합니다. 예치금을 충전해주세요.</span>';
                document.getElementById('modalSubmitBtn').disabled = true;
                document.getElementById('modalSubmitBtn').style.background = '#cbd5e1';
                document.getElementById('modalSubmitBtn').style.color = '#64748b';
                document.getElementById('modalSubmitBtn').style.cursor = 'not-allowed';
            }
        } else {
            document.getElementById('modalPricePreview').style.display = 'none';
            document.getElementById('modalSubmitBtn').disabled = true;
            document.getElementById('modalSubmitBtn').style.background = '#cbd5e1';
            document.getElementById('modalSubmitBtn').style.color = '#64748b';
            document.getElementById('modalSubmitBtn').style.cursor = 'not-allowed';
        }
    } catch (error) {
        console.error('Price fetch error:', error);
        document.getElementById('modalPricePreview').style.display = 'none';
    }
}

async function submitAdForm(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('adForm'));
    const submitBtn = document.getElementById('modalSubmitBtn');
    const messageDiv = document.getElementById('modalMessage');
    
    submitBtn.disabled = true;
    submitBtn.textContent = '처리중...';
    messageDiv.style.display = 'none';
    
    try {
        const response = await fetch('/MVNO/api/advertisement-apply.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            messageDiv.style.display = 'block';
            messageDiv.style.background = '#d1fae5';
            messageDiv.style.color = '#065f46';
            messageDiv.textContent = data.message;
            
            setTimeout(() => {
                closeAdModal();
                location.reload();
            }, 1500);
        } else {
            messageDiv.style.display = 'block';
            messageDiv.style.background = '#fee2e2';
            messageDiv.style.color = '#991b1b';
            messageDiv.textContent = data.message || '광고 신청에 실패했습니다.';
            
            submitBtn.disabled = false;
            submitBtn.textContent = '광고 신청';
        }
    } catch (error) {
        console.error('Submit error:', error);
        messageDiv.style.display = 'block';
        messageDiv.style.background = '#fee2e2';
        messageDiv.style.color = '#991b1b';
        messageDiv.textContent = '오류가 발생했습니다. 다시 시도해주세요.';
        
        submitBtn.disabled = false;
        submitBtn.textContent = '광고 신청';
    }
}

document.getElementById('modalAdvertisementDays')?.addEventListener('change', updateModalPrice);
</script>

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>


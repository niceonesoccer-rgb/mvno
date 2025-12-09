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
$perPage = intval($_GET['per_page'] ?? 20);
// 허용된 per_page 값만 사용 (10, 50, 100)
if (!in_array($perPage, [10, 50, 100])) {
    $perPage = 20;
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
        
        // 전체 개수 조회
        if ($hasMvnoFilters) {
            $countStmt = $pdo->prepare("
                SELECT COUNT(DISTINCT p.id) as total
                FROM products p
                LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id
                WHERE {$whereClause}
            ");
        } else {
            $countStmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM products p
                WHERE {$whereClause}
            ");
        }
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
                mvno.price_after AS monthly_fee
            FROM products p
            LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id
            WHERE {$whereClause}
            ORDER BY p.created_at DESC
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

// 페이지별 스타일
$pageStyles = '
    .product-list-container {
        max-width: 1200px;
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
        
        <div class="filter-buttons">
            <button class="search-button" onclick="applyFilters()" style="width: 100%;">검색</button>
            <button class="search-button" onclick="resetFilters()" style="background: #6b7280; width: 100%;">초기화</button>
        </div>
    </div>
    
    <!-- 페이지당 표시 선택 -->
    <div style="display: flex; justify-content: flex-end; margin-bottom: 16px;">
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
            <table class="product-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">
                            <div style="display: flex; flex-direction: column; gap: 8px; align-items: center;">
                                <button class="btn btn-sm" onclick="bulkInactive()" style="background: #ef4444; color: white; padding: 4px 8px; font-size: 12px; border-radius: 4px; border: none; cursor: pointer; width: 60px; white-space: nowrap;">판매종료</button>
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" style="cursor: pointer;">
                            </div>
                        </th>
                        <th style="text-align: center;">번호</th>
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
                                <input type="checkbox" class="product-checkbox" value="<?php echo $product['id']; ?>" style="cursor: pointer;">
                            </td>
                            <td style="text-align: center;"><?php echo $totalProducts - (($page - 1) * $perPage + $index); ?></td>
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
                                    <button class="btn btn-sm btn-edit" onclick="editProduct(<?php echo $product['id']; ?>)">수정</button>
                                    <button class="btn btn-sm btn-copy" onclick="copyProduct(<?php echo $product['id']; ?>)">복사</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- 페이지네이션 -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination" style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 20px;">
                    <?php
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
    if (typeof showConfirm === 'function') {
        showConfirm('이 상품을 복사하시겠습니까?', '상품 복사').then(confirmed => {
            if (confirmed) {
                processCopyProduct(productId);
            }
        });
    } else if (confirm('이 상품을 복사하시겠습니까?')) {
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
                showAlert('상품이 복사되었습니다.', '완료');
            } else {
                alert('상품이 복사되었습니다.');
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

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function bulkInactive() {
    const checkboxes = document.querySelectorAll('.product-checkbox:checked');
    if (checkboxes.length === 0) {
        if (typeof showAlert === 'function') {
            showAlert('선택된 상품이 없습니다.', '알림');
        } else {
            alert('선택된 상품이 없습니다.');
        }
        return;
    }
    
    const productCount = checkboxes.length;
    const message = '선택한 ' + productCount + '개의 상품을 판매종료 처리하시겠습니까?';
    
    if (typeof showConfirm === 'function') {
        showConfirm(message, '판매종료 확인').then(confirmed => {
            if (confirmed) {
                processBulkInactive(checkboxes);
            }
        });
    } else if (confirm(message)) {
        processBulkInactive(checkboxes);
    }
}

function processBulkInactive(checkboxes) {
    const productIds = Array.from(checkboxes).map(cb => cb.value);
    
    fetch('/MVNO/api/product-bulk-update.php', {
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
            if (typeof showAlert === 'function') {
                showAlert('선택한 상품이 판매종료 처리되었습니다.', '완료');
            } else {
                alert('선택한 상품이 판매종료 처리되었습니다.');
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
</script>

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>


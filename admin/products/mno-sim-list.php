<?php
/**
 * 관리자 통신사단독유심(MNO-SIM) 상품 목록 페이지
 * 경로: /admin/products/mno-sim-list.php
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

// 검색 필터 파라미터
$status = $_GET['status'] ?? '';
if ($status === '') {
    $status = null;
}

$search_query = $_GET['search_query'] ?? '';
$provider = $_GET['provider'] ?? '';
$seller_id = $_GET['seller_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = intval($_GET['per_page'] ?? 10);
if (!in_array($perPage, [10, 20, 50, 100])) {
    $perPage = 10;
}

// 상품 목록 조회
$products = [];
$totalProducts = 0;
$totalPages = 1;

try {
    $pdo = getDBConnection();
    if ($pdo) {
        $whereConditions = ["p.product_type = 'mno-sim'"];
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
            $whereConditions[] = 'mno_sim.provider = :provider';
            $params[':provider'] = $provider;
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
                mno_sim.plan_name LIKE :search_query1
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
            INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
            {$searchJoin}
            WHERE {$whereClause}{$searchWhere}
        ");
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $totalProducts = $countStmt->fetch()['total'];
        $totalPages = ceil($totalProducts / $perPage);
        
        // 통신사단독유심 상품 목록 조회
        $offset = ($page - 1) * $perPage;
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                mno_sim.plan_name AS product_name,
                mno_sim.provider,
                mno_sim.price_main AS monthly_fee,
                mno_sim.service_type,
                mno_sim.registration_types,
                p.seller_id AS seller_user_id,
                u.user_id AS seller_user_id_display,
                COALESCE(NULLIF(u.seller_name,''), NULLIF(u.company_name,''), NULLIF(u.name,''), u.user_id) AS seller_name,
                COALESCE(u.company_name,'') AS company_name
            FROM products p
            INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
            {$searchJoin}
            WHERE {$whereClause}{$searchWhere}
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
        
        // 판매자 정보 정리 (SQL에서 이미 가져왔지만, 없는 경우 처리)
        foreach ($products as &$product) {
            $sellerId = (string)($product['seller_user_id'] ?? $product['seller_id'] ?? '');
            // seller_user_id_display가 없으면 seller_user_id나 seller_id 사용
            if (empty($product['seller_user_id_display'])) {
                $product['seller_user_id_display'] = $sellerId;
            }
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
    error_log("Error fetching mno-sim products: " . $e->getMessage());
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
        flex-wrap: wrap;
    }
    
    .filter-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 12px;
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
    
    .filter-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .filter-label {
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        min-width: 80px;
    }
    
    .filter-select,
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
    
    .badge {
        display: inline-block;
        padding: 4px 12px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 12px;
    }
    
    .badge-mno-sim {
        background: #fef3c7;
        color: #92400e;
    }
    
    .badge-active {
        background: #d1fae5;
        color: #065f46;
    }
    
    .badge-inactive {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 13px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
    }
    
    .btn-danger {
        background: #ef4444;
        color: white;
    }
    
    .btn-danger:hover {
        background: #dc2626;
    }
    
    .empty-state {
        padding: 60px 20px;
        text-align: center;
        color: #6b7280;
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
    
    .pagination-btn:hover:not(.disabled):not(.active) {
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
</style>

<div class="admin-content">
    <div class="product-list-container">
        <div class="page-header">
            <h1>통신사단독유심 등록상품</h1>
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
                            <option value="KT" <?php echo $provider === 'KT' ? 'selected' : ''; ?>>KT</option>
                            <option value="SKT" <?php echo $provider === 'SKT' ? 'selected' : ''; ?>>SKT</option>
                            <option value="LG U+" <?php echo $provider === 'LG U+' ? 'selected' : ''; ?>>LG U+</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">검색:</label>
                        <input type="text" class="filter-input" id="filter_search_query" placeholder="아이디, 상품명, 판매자명" value="<?php echo htmlspecialchars($search_query); ?>" style="width: 250px;" onkeypress="if(event.key==='Enter') applyFilters()">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">등록일:</label>
                        <input type="date" class="filter-input" id="filter_date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        <span style="color: #6b7280;">~</span>
                        <input type="date" class="filter-input" id="filter_date_to" value="<?php echo htmlspecialchars($date_to); ?>">
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
                    <div class="empty-state-title">등록된 통신사단독유심 상품이 없습니다</div>
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
                            <th>번호</th>
                            <th>판매자 아이디</th>
                            <th>판매자</th>
                            <th>통신사</th>
                            <th>상품명</th>
                            <th>데이터 속도</th>
                            <th>가입 형태</th>
                            <th>월 요금</th>
                            <th>조회수</th>
                            <th>찜</th>
                            <th>리뷰</th>
                            <th>신청</th>
                            <th>상태</th>
                            <th>등록일</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $index => $product): ?>
                            <tr>
                                <td class="checkbox-column">
                                    <input type="checkbox" class="product-checkbox product-checkbox-item" 
                                           value="<?php echo $product['id']; ?>">
                                </td>
                                <td><?php echo $totalProducts - ($page - 1) * $perPage - $index; ?></td>
                                <td style="text-align: center;">
                                    <?php 
                                    $sellerId = $product['seller_user_id_display'] ?? $product['seller_user_id'] ?? $product['seller_id'] ?? '-';
                                    if ($sellerId && $sellerId !== '-') {
                                        echo '<a href="/MVNO/admin/users/seller-detail.php?user_id=' . urlencode($sellerId) . '" style="color: #3b82f6; text-decoration: none; font-weight: 600;">' . htmlspecialchars($sellerId) . '</a>';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['seller_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($product['provider'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($product['product_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($product['service_type'] ?? '-'); ?></td>
                                <td>
                                    <?php
                                    $registrationTypes = [];
                                    if (!empty($product['registration_types'])) {
                                        $registrationTypes = json_decode($product['registration_types'], true) ?: [];
                                    }
                                    echo !empty($registrationTypes) ? htmlspecialchars(implode(', ', $registrationTypes)) : '-';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $monthlyFee = $product['monthly_fee'] ?? 0;
                                    // 문자열인 경우 숫자만 추출
                                    if (is_string($monthlyFee)) {
                                        $monthlyFee = preg_replace('/[^0-9.]/', '', $monthlyFee);
                                    }
                                    $monthlyFee = floatval($monthlyFee);
                                    echo $monthlyFee > 0 ? number_format($monthlyFee) . '원' : '-';
                                    ?>
                                </td>
                                <td><?php echo number_format($product['view_count'] ?? 0); ?></td>
                                <td><?php echo number_format($product['favorite_count'] ?? 0); ?></td>
                                <td><?php echo number_format($product['review_count'] ?? 0); ?></td>
                                <td><?php echo number_format($product['application_count'] ?? 0); ?></td>
                                <td>
                                    <span class="badge <?php echo ($product['status'] ?? 'active') === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                                        <?php echo ($product['status'] ?? 'active') === 'active' ? '판매중' : '판매종료'; ?>
                                    </span>
                                </td>
                                <td><?php echo isset($product['created_at']) ? date('Y-m-d', strtotime($product['created_at'])) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- 페이지네이션 -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php
                        // 페이지 그룹 계산 (10개씩 그룹화)
                        $pageGroupSize = 10;
                        $currentGroup = ceil($page / $pageGroupSize);
                        $startPage = ($currentGroup - 1) * $pageGroupSize + 1;
                        $endPage = min($currentGroup * $pageGroupSize, $totalPages);
                        $prevGroupLastPage = ($currentGroup - 1) * $pageGroupSize;
                        $nextGroupFirstPage = $currentGroup * $pageGroupSize + 1;
                        $baseQuery = '?status=' . htmlspecialchars($status ?? '') . 
                                    '&search_query=' . htmlspecialchars($search_query) . 
                                    '&provider=' . htmlspecialchars($provider) . 
                                    '&seller_id=' . htmlspecialchars($seller_id) . 
                                    '&date_from=' . htmlspecialchars($date_from) . 
                                    '&date_to=' . htmlspecialchars($date_to) . 
                                    '&per_page=' . $perPage;
                        ?>
                        <?php if ($currentGroup > 1): ?>
                            <a href="<?php echo $baseQuery; ?>&page=<?php echo $prevGroupLastPage; ?>" 
                               class="pagination-btn">이전</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">이전</span>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="<?php echo $baseQuery; ?>&page=<?php echo $i; ?>" 
                               class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($nextGroupFirstPage <= $totalPages): ?>
                            <a href="<?php echo $baseQuery; ?>&page=<?php echo $nextGroupFirstPage; ?>" 
                               class="pagination-btn">다음</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">다음</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function changePerPage() {
    const perPage = document.getElementById('per_page_select').value;
    const params = new URLSearchParams(window.location.search);
    params.set('per_page', perPage);
    params.delete('page');
    window.location.href = '?' + params.toString();
}

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
    
    const dateFrom = document.getElementById('filter_date_from').value;
    if (dateFrom) params.set('date_from', dateFrom);
    
    const dateTo = document.getElementById('filter_date_to').value;
    if (dateTo) params.set('date_to', dateTo);
    
    const perPage = document.getElementById('per_page_select').value;
    params.set('per_page', perPage);
    
    window.location.href = '?' + params.toString();
}

function resetFilters() {
    window.location.href = window.location.pathname + '?per_page=10';
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
    
    fetch('/MVNO/api/admin-product-bulk-update.php', {
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
    const modal = document.getElementById('alertModal');
    const titleEl = document.getElementById('alertTitle');
    const messageEl = document.getElementById('alertMessage');
    
    if (titleEl) titleEl.textContent = title;
    if (messageEl) messageEl.textContent = message;
    if (modal) {
        modal.classList.add('show');
        document.body.classList.add('modal-open');
    }
}

function closeAlertModal() {
    const modal = document.getElementById('alertModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }
}

// 일괄 변경 셀렉트박스 변경 이벤트 및 모달 이벤트
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
});
</script>

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

<!-- 알림 모달 -->
<div id="alertModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-title" id="alertTitle">알림</div>
        <div class="modal-message" id="alertMessage"></div>
        <div class="modal-buttons">
            <button class="modal-btn modal-btn-ok" onclick="closeAlertModal()">확인</button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>



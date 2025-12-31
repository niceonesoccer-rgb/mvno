<?php
/**
 * 통신사폰 상품 목록 페이지
 * 경로: /seller/products/mno-list.php
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

// 필터 파라미터
$status = $_GET['status'] ?? '';
if ($status === '') {
    $status = null;
}
$searchDeviceName = $_GET['search_device_name'] ?? '';
$searchDeliveryMethod = $_GET['search_delivery_method'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
if (!in_array($perPage, [10, 20, 50, 100, 500])) {
    $perPage = 10;
}

// DB에서 통신사폰 상품 목록 가져오기
$products = [];
$totalProducts = 0;
$totalPages = 1;

try {
    $pdo = getDBConnection();
    if ($pdo) {
        // WHERE 조건 구성
        $sellerId = (string)$currentUser['user_id'];
        $whereConditions = ['p.seller_id = :seller_id', "p.product_type = 'mno'"];
        $params = [':seller_id' => $sellerId];
        
        // 상태 필터
        if ($status && $status !== '') {
            $whereConditions[] = 'p.status = :status';
            $params[':status'] = $status;
        } else {
            $whereConditions[] = "p.status != 'deleted'";
        }
        
        // 단말기명 검색
        if ($searchDeviceName && $searchDeviceName !== '') {
            $whereConditions[] = '(mno.device_name IS NOT NULL AND mno.device_name LIKE :search_device_name)';
            $params[':search_device_name'] = '%' . $searchDeviceName . '%';
        }
        
        // 단말기 수령방법 검색
        if ($searchDeliveryMethod && $searchDeliveryMethod !== '') {
            $searchLower = strtolower($searchDeliveryMethod);
            if ($searchLower === '택배' || $searchLower === 'delivery') {
                $whereConditions[] = "mno.delivery_method = 'delivery'";
            } else if ($searchLower === '내방' || $searchLower === 'visit') {
                $whereConditions[] = "mno.delivery_method = 'visit'";
            } else {
                // 텍스트로 검색 (내방 지역명 포함)
                $whereConditions[] = "(mno.delivery_method = 'visit' AND mno.visit_region IS NOT NULL AND mno.visit_region LIKE :search_delivery_method)";
                $params[':search_delivery_method'] = '%' . $searchDeliveryMethod . '%';
            }
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // 전체 개수 조회
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM products p
            LEFT JOIN product_mno_details mno ON p.id = mno.product_id
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
                mno.device_name AS product_name,
                mno.delivery_method,
                mno.visit_region,
                COALESCE(prs.total_review_count, 0) AS review_count,
                CASE WHEN EXISTS (
                    SELECT 1 FROM rotation_advertisements ra 
                    WHERE ra.product_id = p.id 
                    AND ra.status = 'active' 
                    AND ra.end_datetime > NOW()
                ) THEN 1 ELSE 0 END AS has_active_ad
            FROM products p
            LEFT JOIN product_mno_details mno ON p.id = mno.product_id
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
    error_log("Error fetching MNO products: " . $e->getMessage());
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

// 페이지별 스타일 (mvno-list.php와 동일)
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
    
    .page-info {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        padding: 12px 20px;
        background: white;
        border-bottom: 1px solid #e5e7eb;
        font-size: 14px;
        color: #6b7280;
    }
    
    .page-info strong {
        color: #374151;
        margin-left: 4px;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        margin-top: 24px;
        padding: 24px 20px;
        background: transparent;
    }
    
    .pagination-btn {
        padding: 8px 16px;
        font-size: 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
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
        min-width: 44px;
        height: 36px;
        font-weight: 500;
    }
    
    .pagination-btn:hover:not(.disabled):not(.active) {
        background: #f9fafb;
        border-color: #10b981;
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
        pointer-events: none;
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
        <h1>통신사폰 등록상품</h1>
        <div style="display: flex; align-items: center; gap: 8px;">
            <label style="font-size: 14px; color: #374151; font-weight: 600;">페이지당 표시:</label>
            <select class="filter-select" id="per_page_select" onchange="changePerPage(this.value)" style="width: 80px;">
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
                    <label class="filter-label">검색:</label>
                    <input type="text" class="filter-input" id="filter_device_name" 
                           placeholder="단말기명" 
                           value="<?php echo htmlspecialchars($searchDeviceName); ?>"
                           style="width: 250px;"
                           onkeypress="if(event.key==='Enter') applyFilters()">
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">수령방법:</label>
                    <input type="text" class="filter-input" id="filter_delivery_method" 
                           placeholder="택배/내방/지역명 검색" 
                           value="<?php echo htmlspecialchars($searchDeliveryMethod); ?>"
                           style="width: 250px;"
                           onkeypress="if(event.key==='Enter') applyFilters()">
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
                <div class="empty-state-text">새로운 통신사폰 상품을 등록해보세요</div>
                <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                    <a href="/MVNO/seller/products/mno.php" class="btn btn-primary">통신사폰 등록</a>
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
                        <th>상품등록번호</th>
                        <th>단말기명</th>
                        <th>단말기 수령방법</th>
                        <th>조회수</th>
                        <th>찜</th>
                        <th>리뷰</th>
                        <th>신청</th>
                        <th>상태</th>
                        <th>등록일</th>
                        <th>관리</th>
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
                            <td><?php 
                                $productNumber = getProductNumberByType($product['id'], 'mno');
                                echo $productNumber ? htmlspecialchars($productNumber) : htmlspecialchars($product['id'] ?? '-');
                            ?></td>
                            <td>
                                <a href="javascript:void(0);" onclick="showProductInfo(<?php echo $product['id']; ?>, 'mno')" style="color: #3b82f6; text-decoration: none; font-weight: 600; cursor: pointer;">
                                    <?php echo htmlspecialchars($product['product_name'] ?? '-'); ?>
                                </a>
                            </td>
                            <td>
                                <?php 
                                $deliveryMethod = $product['delivery_method'] ?? 'delivery';
                                if ($deliveryMethod === 'visit') {
                                    $visitRegion = htmlspecialchars($product['visit_region'] ?? '');
                                    echo '내방' . ($visitRegion ? ' (' . $visitRegion . ')' : '');
                                } else {
                                    echo '택배';
                                }
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
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-edit" onclick="editProduct(<?php echo $product['id']; ?>)">수정</button>
                                    <button class="btn btn-sm btn-copy" onclick="copyProduct(<?php echo $product['id']; ?>)">복사</button>
                                    <?php 
                                    $hasActiveAd = intval($product['has_active_ad'] ?? 0);
                                    if ($hasActiveAd): 
                                    ?>
                                        <span style="color: #64748b; font-size: 12px; margin-left: 8px;">광고중</span>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm" onclick="openAdModal(<?php echo $product['id']; ?>, 'mno', '<?php echo htmlspecialchars($product['product_name'] ?? '', ENT_QUOTES); ?>')" style="background: #6366f1; color: white; border: none; padding: 4px 12px; border-radius: 4px; margin-left: 4px; font-size: 12px; cursor: pointer;">광고</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- 페이지네이션 (하단) -->
    <?php if ($totalProducts > 0 && $totalPages > 0): ?>
        <?php
        $paginationParams = [];
        if ($status) $paginationParams['status'] = $status;
        if ($searchDeviceName) $paginationParams['search_device_name'] = $searchDeviceName;
        if ($searchDeliveryMethod) $paginationParams['search_delivery_method'] = $searchDeliveryMethod;
        $paginationParams['per_page'] = $perPage;
        $paginationQuery = http_build_query($paginationParams);
        ?>
        <div class="pagination">
            <?php if ($totalPages > 1): ?>
                <a href="?<?php echo $paginationQuery; ?>&page=<?php echo max(1, $page - 1); ?>" 
                   class="pagination-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">이전</a>
                
                <?php 
                $startPage = max(1, $page - 4);
                $endPage = min($totalPages, $startPage + 9);
                if ($endPage - $startPage < 9) {
                    $startPage = max(1, $endPage - 9);
                }
                
                if ($startPage > 1): ?>
                    <a href="?<?php echo $paginationQuery; ?>&page=1" class="pagination-btn">1</a>
                    <?php if ($startPage > 2): ?>
                        <span class="pagination-btn" style="border: none; background: transparent; cursor: default;">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a href="?<?php echo $paginationQuery; ?>&page=<?php echo $i; ?>" 
                       class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                        <span class="pagination-btn" style="border: none; background: transparent; cursor: default;">...</span>
                    <?php endif; ?>
                    <a href="?<?php echo $paginationQuery; ?>&page=<?php echo $totalPages; ?>" class="pagination-btn"><?php echo $totalPages; ?></a>
                <?php endif; ?>
                
                <a href="?<?php echo $paginationQuery; ?>&page=<?php echo min($totalPages, $page + 1); ?>" 
                   class="pagination-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">다음</a>
            <?php else: ?>
                <span class="pagination-btn active">1</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- 상품 정보 모달 -->
<div id="productInfoModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000; overflow-y: auto;">
    <div style="position: relative; max-width: 800px; margin: 40px auto; background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 2px solid #e5e7eb; padding-bottom: 16px;">
            <h2 style="font-size: 24px; font-weight: 700; color: #1f2937; margin: 0;">상품 정보</h2>
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
.discount-selection-table-wrapper {
    width: 100%;
    overflow-x: auto;
    margin-top: 16px;
}
.discount-selection-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}
.discount-selection-table thead {
    background: #f9fafb;
}
.discount-selection-table th {
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border: 1px solid #e5e7eb;
    font-size: 14px;
}
.discount-selection-table td {
    padding: 12px 16px;
    border: 1px solid #e5e7eb;
    color: #1f2937;
    font-size: 14px;
}
.discount-provider-cell {
    font-weight: 600;
    background: #f9fafb;
    vertical-align: top;
}
.discount-type-cell {
    font-weight: 500;
    vertical-align: top;
}
.discount-amount-display {
    display: inline-block;
    padding: 6px 12px;
    background: #6366f1;
    color: white;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    min-width: 60px;
    text-align: center;
}
</style>

<script>
function applyFilters() {
    const status = document.getElementById('filter_status').value;
    const deviceName = document.getElementById('filter_device_name').value.trim();
    const deliveryMethod = document.getElementById('filter_delivery_method').value.trim();
    const params = new URLSearchParams(window.location.search);
    
    if (status && status !== '') {
        params.set('status', status);
    } else {
        params.delete('status');
    }
    if (deviceName) {
        params.set('search_device_name', deviceName);
    } else {
        params.delete('search_device_name');
    }
    if (deliveryMethod) {
        params.set('search_delivery_method', deliveryMethod);
    } else {
        params.delete('search_delivery_method');
    }
    params.delete('page');
    
    window.location.href = '?' + params.toString();
}

function changePerPage(perPage) {
    const params = new URLSearchParams(window.location.search);
    params.set('per_page', perPage);
    params.delete('page');
    window.location.href = '?' + params.toString();
}

function editProduct(productId) {
    window.location.href = '/MVNO/seller/products/mno.php?id=' + productId;
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
            product_type: 'mno'
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

function showProductInfo(productId, productType = 'mno') {
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
                console.log('Product data:', product); // 디버깅용
                let html = '';
                
                // 판매 상태
                html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">판매 상태</h3>';
                html += '<table class="product-info-table">';
                html += '<tr><th>상태</th><td>' + (product.status === 'active' ? '판매중' : '판매종료') + '</td></tr>';
                html += '</table></div>';
                
                // 단말기
                html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">단말기</h3>';
                html += '<table class="product-info-table">';
                html += '<tr><th>단말기명</th><td>' + (product.device_name || '-') + '</td></tr>';
                html += '<tr><th>단말기 출고가</th><td>' + (product.device_price ? number_format(product.device_price) + '원' : '-') + '</td></tr>';
                html += '<tr><th>용량</th><td>' + (product.device_capacity || '-') + '</td></tr>';
                
                // 색상
                let colors = '-';
                if (product.device_colors) {
                    const colorArray = typeof product.device_colors === 'string' ? JSON.parse(product.device_colors) : product.device_colors;
                    if (Array.isArray(colorArray) && colorArray.length > 0) {
                        colors = colorArray.join(', ');
                    }
                }
                html += '<tr><th>색상</th><td>' + colors + '</td></tr>';
                html += '</table></div>';
                
                // 할인방법
                const allDiscounts = buildAllDiscountTables(product);
                if (allDiscounts) {
                    html += allDiscounts;
                }
                
                // 단말기 수령방법
                html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">단말기 수령방법</h3>';
                html += '<table class="product-info-table">';
                let deliveryMethod = '-';
                if (product.delivery_method === 'delivery') {
                    deliveryMethod = '택배';
                } else if (product.delivery_method === 'visit') {
                    deliveryMethod = '내방' + (product.visit_region ? ' (' + product.visit_region + ')' : '');
                }
                html += '<tr><th>수령방법</th><td>' + deliveryMethod + '</td></tr>';
                html += '</table></div>';
                
                // 부가서비스 및 유지기간
                if (product.promotion_title || (product.promotions && (typeof product.promotions === 'string' ? JSON.parse(product.promotions) : product.promotions).length > 0)) {
                    html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">부가서비스 및 유지기간</h3>';
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
                
                // 리다이렉트 URL
                if (product.redirect_url) {
                    html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">신청 후 리다이렉트 URL</h3>';
                    html += '<table class="product-info-table">';
                    html += '<tr><th>URL</th><td>' + product.redirect_url + '</td></tr>';
                    html += '</table></div>';
                }
                
                // 기타 정보
                html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">기타 정보</h3>';
                html += '<table class="product-info-table">';
                html += '<tr><th>서비스 타입</th><td>' + (product.service_type || '-') + '</td></tr>';
                if (product.contract_period_value) html += '<tr><th>약정기간</th><td>' + product.contract_period_value + '일</td></tr>';
                if (product.price_main) html += '<tr><th>기본 요금</th><td>' + number_format(product.price_main) + '원</td></tr>';
                html += '<tr><th>등록일</th><td>' + (product.created_at ? new Date(product.created_at).toLocaleString('ko-KR') : '-') + '</td></tr>';
                html += '</table></div>';
                
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

function buildAllDiscountTables(product) {
    // 공통지원할인과 선택약정할인을 모두 포함하는 하나의 테이블로 구성
    const allDiscountOptions = [];
    
    // 숫자 비교를 위한 헬퍼 함수
    function isNot9999(value) {
        if (value === undefined || value === null) return false;
        const numValue = parseFloat(value);
        const result = !isNaN(numValue) && numValue !== 9999;
        if (!result && value !== undefined && value !== null) {
            console.log('Filtered out 9999 value:', value, 'parsed as:', numValue);
        }
        return result;
    }
    
    // 공통지원할인 데이터 수집
    let commonProviders = [];
    let commonNewDiscounts = [];
    let commonPortDiscounts = [];
    let commonChangeDiscounts = [];
    
    try {
        commonProviders = product.common_provider ? (typeof product.common_provider === 'string' ? JSON.parse(product.common_provider) : product.common_provider) : [];
        commonNewDiscounts = product.common_discount_new ? (typeof product.common_discount_new === 'string' ? JSON.parse(product.common_discount_new) : product.common_discount_new) : [];
        commonPortDiscounts = product.common_discount_port ? (typeof product.common_discount_port === 'string' ? JSON.parse(product.common_discount_port) : product.common_discount_port) : [];
        commonChangeDiscounts = product.common_discount_change ? (typeof product.common_discount_change === 'string' ? JSON.parse(product.common_discount_change) : product.common_discount_change) : [];
    } catch (e) {
        console.error('Error parsing common discount data:', e);
    }
    
    for (let i = 0; i < commonProviders.length; i++) {
        const provider = commonProviders[i] || '-';
        
        if (isNot9999(commonPortDiscounts[i])) {
            allDiscountOptions.push({ provider, discountType: '공통지원할인', subscriptionType: '번호이동', amount: commonPortDiscounts[i] });
        }
        if (isNot9999(commonChangeDiscounts[i])) {
            allDiscountOptions.push({ provider, discountType: '공통지원할인', subscriptionType: '기기변경', amount: commonChangeDiscounts[i] });
        }
        if (isNot9999(commonNewDiscounts[i])) {
            allDiscountOptions.push({ provider, discountType: '공통지원할인', subscriptionType: '신규가입', amount: commonNewDiscounts[i] });
        }
    }
    
    // 선택약정할인 데이터 수집
    let contractProviders = [];
    let contractNewDiscounts = [];
    let contractPortDiscounts = [];
    let contractChangeDiscounts = [];
    
    try {
        contractProviders = product.contract_provider ? (typeof product.contract_provider === 'string' ? JSON.parse(product.contract_provider) : product.contract_provider) : [];
        contractNewDiscounts = product.contract_discount_new ? (typeof product.contract_discount_new === 'string' ? JSON.parse(product.contract_discount_new) : product.contract_discount_new) : [];
        contractPortDiscounts = product.contract_discount_port ? (typeof product.contract_discount_port === 'string' ? JSON.parse(product.contract_discount_port) : product.contract_discount_port) : [];
        contractChangeDiscounts = product.contract_discount_change ? (typeof product.contract_discount_change === 'string' ? JSON.parse(product.contract_discount_change) : product.contract_discount_change) : [];
    } catch (e) {
        console.error('Error parsing contract discount data:', e);
    }
    
    for (let i = 0; i < contractProviders.length; i++) {
        const provider = contractProviders[i] || '-';
        
        if (isNot9999(contractPortDiscounts[i])) {
            allDiscountOptions.push({ provider, discountType: '선택약정할인', subscriptionType: '번호이동', amount: contractPortDiscounts[i] });
        }
        if (isNot9999(contractChangeDiscounts[i])) {
            allDiscountOptions.push({ provider, discountType: '선택약정할인', subscriptionType: '기기변경', amount: contractChangeDiscounts[i] });
        }
        if (isNot9999(contractNewDiscounts[i])) {
            allDiscountOptions.push({ provider, discountType: '선택약정할인', subscriptionType: '신규가입', amount: contractNewDiscounts[i] });
        }
    }
    
    if (allDiscountOptions.length === 0) {
        return null;
    }
    
    // 통신사별, 할인종류별로 그룹화
    const groupedByProviderAndDiscount = {};
    allDiscountOptions.forEach(option => {
        const key = `${option.provider}_${option.discountType}`;
        if (!groupedByProviderAndDiscount[key]) {
            groupedByProviderAndDiscount[key] = {
                provider: option.provider,
                discountType: option.discountType,
                options: []
            };
        }
        groupedByProviderAndDiscount[key].options.push(option);
    });
    
    // 통신사별로 다시 그룹화
    const finalGrouped = {};
    Object.keys(groupedByProviderAndDiscount).forEach(key => {
        const item = groupedByProviderAndDiscount[key];
        if (!finalGrouped[item.provider]) {
            finalGrouped[item.provider] = [];
        }
        finalGrouped[item.provider].push(item);
    });
    
    // 테이블 HTML 생성
    let html = '<div style="margin-top: 32px; margin-bottom: 16px;"><strong style="font-size: 18px; color: #1f2937; display: block; margin-bottom: 16px;">할인방법 선택</strong>';
    html += '<div class="discount-selection-table-wrapper">';
    html += '<table class="discount-selection-table">';
    html += '<thead><tr><th>통신사</th><th>할인종류</th><th>가입유형</th><th>가격</th></tr></thead>';
    html += '<tbody>';
    
    Object.keys(finalGrouped).forEach(provider => {
        const providerGroups = finalGrouped[provider];
        let providerRowSpan = 0;
        
        // 통신사별 총 행 개수 계산
        providerGroups.forEach(group => {
            providerRowSpan += group.options.length;
        });
        
        providerGroups.forEach((group, groupIndex) => {
            group.options.forEach((option, optionIndex) => {
                html += '<tr>';
                
                // 통신사 셀 (첫 번째 그룹의 첫 번째 옵션에만 표시)
                if (groupIndex === 0 && optionIndex === 0) {
                    html += `<td rowspan="${providerRowSpan}" class="discount-provider-cell">${provider}</td>`;
                }
                
                // 할인종류 셀 (각 그룹의 첫 번째 옵션에만 표시)
                if (optionIndex === 0) {
                    html += `<td rowspan="${group.options.length}" class="discount-type-cell">${group.discountType}</td>`;
                }
                
                // 가입유형
                html += `<td>${option.subscriptionType}</td>`;
                
                // 할인금액 (고객 화면과 동일한 스타일의 박스)
                const amount = parseFloat(option.amount);
                let formattedAmount;
                if (amount % 1 === 0) {
                    formattedAmount = amount < 0 
                        ? `-${Math.abs(amount).toLocaleString('ko-KR')}`
                        : `${amount.toLocaleString('ko-KR')}`;
                } else {
                    formattedAmount = amount < 0 
                        ? `-${Math.abs(amount).toLocaleString('ko-KR', { minimumFractionDigits: 1, maximumFractionDigits: 2 })}`
                        : `${amount.toLocaleString('ko-KR', { minimumFractionDigits: 1, maximumFractionDigits: 2 })}`;
                }
                
                html += `<td><span class="discount-amount-display">${formattedAmount}</span></td>`;
                html += '</tr>';
            });
        });
    });
    
    html += '</tbody></table></div></div>';
    return html;
}

function buildCustomerDiscountTable(product, type) {
    const prefix = type === 'common' ? 'common' : 'contract';
    const discountTypeName = type === 'common' ? '공통지원할인' : '선택약정할인';
    const providers = product[prefix + '_provider'] ? (typeof product[prefix + '_provider'] === 'string' ? JSON.parse(product[prefix + '_provider']) : product[prefix + '_provider']) : [];
    const newDiscounts = product[prefix + '_discount_new'] ? (typeof product[prefix + '_discount_new'] === 'string' ? JSON.parse(product[prefix + '_discount_new']) : product[prefix + '_discount_new']) : [];
    const portDiscounts = product[prefix + '_discount_port'] ? (typeof product[prefix + '_discount_port'] === 'string' ? JSON.parse(product[prefix + '_discount_port']) : product[prefix + '_discount_port']) : [];
    const changeDiscounts = product[prefix + '_discount_change'] ? (typeof product[prefix + '_discount_change'] === 'string' ? JSON.parse(product[prefix + '_discount_change']) : product[prefix + '_discount_change']) : [];
    
    if (!providers || providers.length === 0) {
        return null;
    }
    
    // 할인 옵션 데이터 구성 (9999 값 제외)
    const discountOptions = [];
    
    for (let i = 0; i < providers.length; i++) {
        const provider = providers[i] || '-';
        
        // 번호이동
        if (portDiscounts[i] !== undefined && portDiscounts[i] !== null && portDiscounts[i] !== 9999 && portDiscounts[i] !== '9999') {
            discountOptions.push({
                provider: provider,
                discountType: discountTypeName,
                subscriptionType: '번호이동',
                amount: portDiscounts[i]
            });
        }
        
        // 기기변경
        if (changeDiscounts[i] !== undefined && changeDiscounts[i] !== null && changeDiscounts[i] !== 9999 && changeDiscounts[i] !== '9999') {
            discountOptions.push({
                provider: provider,
                discountType: discountTypeName,
                subscriptionType: '기기변경',
                amount: changeDiscounts[i]
            });
        }
        
        // 신규가입
        if (newDiscounts[i] !== undefined && newDiscounts[i] !== null && newDiscounts[i] !== 9999 && newDiscounts[i] !== '9999') {
            discountOptions.push({
                provider: provider,
                discountType: discountTypeName,
                subscriptionType: '신규가입',
                amount: newDiscounts[i]
            });
        }
    }
    
    if (discountOptions.length === 0) {
        return null;
    }
    
    // 통신사별, 할인종류별로 그룹화
    const groupedByProviderAndDiscount = {};
    discountOptions.forEach(option => {
        const key = `${option.provider}_${option.discountType}`;
        if (!groupedByProviderAndDiscount[key]) {
            groupedByProviderAndDiscount[key] = {
                provider: option.provider,
                discountType: option.discountType,
                options: []
            };
        }
        groupedByProviderAndDiscount[key].options.push(option);
    });
    
    // 통신사별로 다시 그룹화
    const finalGrouped = {};
    Object.keys(groupedByProviderAndDiscount).forEach(key => {
        const item = groupedByProviderAndDiscount[key];
        if (!finalGrouped[item.provider]) {
            finalGrouped[item.provider] = [];
        }
        finalGrouped[item.provider].push(item);
    });
    
    // 테이블 HTML 생성
    let html = '<div class="discount-selection-table-wrapper">';
    html += '<table class="discount-selection-table">';
    html += '<thead><tr><th>통신사</th><th>할인종류</th><th>가입유형</th><th>가격</th></tr></thead>';
    html += '<tbody>';
    
    Object.keys(finalGrouped).forEach(provider => {
        const providerGroups = finalGrouped[provider];
        let providerRowSpan = 0;
        
        // 통신사별 총 행 개수 계산
        providerGroups.forEach(group => {
            providerRowSpan += group.options.length;
        });
        
        providerGroups.forEach((group, groupIndex) => {
            group.options.forEach((option, optionIndex) => {
                html += '<tr>';
                
                // 통신사 셀 (첫 번째 그룹의 첫 번째 옵션에만 표시)
                if (groupIndex === 0 && optionIndex === 0) {
                    html += `<td rowspan="${providerRowSpan}" class="discount-provider-cell">${provider}</td>`;
                }
                
                // 할인종류 셀 (각 그룹의 첫 번째 옵션에만 표시)
                if (optionIndex === 0) {
                    html += `<td rowspan="${group.options.length}" class="discount-type-cell">${group.discountType}</td>`;
                }
                
                // 가입유형
                html += `<td>${option.subscriptionType}</td>`;
                
                // 할인금액 (고객 화면과 동일한 스타일의 박스)
                const amount = parseFloat(option.amount);
                let formattedAmount;
                if (amount % 1 === 0) {
                    formattedAmount = amount < 0 
                        ? `-${Math.abs(amount).toLocaleString('ko-KR')}`
                        : `${amount.toLocaleString('ko-KR')}`;
                } else {
                    formattedAmount = amount < 0 
                        ? `-${Math.abs(amount).toLocaleString('ko-KR', { minimumFractionDigits: 1, maximumFractionDigits: 2 })}`
                        : `${amount.toLocaleString('ko-KR', { minimumFractionDigits: 1, maximumFractionDigits: 2 })}`;
                }
                
                html += `<td><span class="discount-amount-display">${formattedAmount}</span></td>`;
                html += '</tr>';
            });
        });
    });
    
    html += '</tbody></table></div>';
    return html;
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
                product_type: 'mno'
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


<?php
/**
 * 판매자 상품 목록 페이지 (탭 방식)
 * 경로: /seller/products/list.php
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

// 탭 파라미터 (기본값: 전체)
$activeTab = $_GET['tab'] ?? 'all';
$validTabs = ['all', 'mvno', 'mno', 'internet'];
if (!in_array($activeTab, $validTabs)) {
    $activeTab = 'all';
}

// 상태 필터 (기본값: 빈 문자열 = 전체)
$status = $_GET['status'] ?? '';
// status가 명시적으로 전달되지 않았거나 빈 문자열이면 전체 표시
if ($status === '') {
    $status = null; // null로 설정하여 전체 상태 표시
}
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// DB에서 상품 목록 가져오기
$products = [];
$totalProducts = 0;
$totalPages = 1;

try {
    $pdo = getDBConnection();
    if ($pdo) {
        // WHERE 조건 구성
        // seller_id는 문자열로 저장되어 있으므로 문자열로 비교
        $sellerId = (string)$currentUser['user_id'];
        $whereConditions = ['p.seller_id = :seller_id'];
        $params = [':seller_id' => $sellerId];
        
        // 탭 필터
        if ($activeTab !== 'all') {
            $whereConditions[] = 'p.product_type = :product_type';
            $params[':product_type'] = $activeTab;
        }
        
        // 상태 필터
        if ($status && $status !== '') {
            $whereConditions[] = 'p.status = :status';
            $params[':status'] = $status;
        } else {
            // 상태 필터가 없으면 active와 inactive 모두 표시 (deleted 제외)
            $whereConditions[] = "p.status != 'deleted'";
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // 전체 개수 조회
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM products p
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
                CASE p.product_type
                    WHEN 'mvno' THEN mvno.plan_name
                    WHEN 'mno' THEN mno.device_name
                    WHEN 'internet' THEN CONCAT(inet.registration_place, ' ', inet.speed_option)
                END AS product_name,
                CASE p.product_type
                    WHEN 'mvno' THEN mvno.provider
                    WHEN 'mno' THEN 'SKT/KT/LG U+'
                    WHEN 'internet' THEN inet.registration_place
                END AS provider,
                CASE p.product_type
                    WHEN 'mvno' THEN mvno.price_after
                    WHEN 'mno' THEN mno.price_main
                    WHEN 'internet' THEN inet.monthly_fee
                END AS monthly_fee
            FROM products p
            LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id AND p.product_type = 'mvno'
            LEFT JOIN product_mno_details mno ON p.id = mno.product_id AND p.product_type = 'mno'
            LEFT JOIN product_internet_details inet ON p.id = inet.product_id AND p.product_type = 'internet'
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
        
        // 디버깅: 쿼리 결과 확인
        error_log("Product list query - seller_id: " . $currentUser['user_id'] . ", activeTab: " . $activeTab . ", status: " . ($status ?? 'null') . ", totalProducts: " . $totalProducts . ", products count: " . count($products));
        if (count($products) > 0) {
            error_log("First product: " . json_encode($products[0]));
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching products: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
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
    
    /* 탭 스타일 */
    .product-tabs {
        background: white;
        border-radius: 12px;
        padding: 8px;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        display: flex;
        gap: 8px;
        overflow-x: auto;
    }
    
    .product-tab {
        flex: 1;
        min-width: 120px;
        padding: 12px 20px;
        text-align: center;
        font-size: 15px;
        font-weight: 600;
        color: #6b7280;
        background: transparent;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
        position: relative;
    }
    
    .product-tab:hover {
        background: #f9fafb;
        color: #374151;
    }
    
    .product-tab.active {
        background: #10b981;
        color: white;
    }
    
    .product-tab.active:hover {
        background: #059669;
    }
    
    .product-tab-count {
        margin-left: 8px;
        padding: 2px 8px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        font-size: 12px;
    }
    
    .product-tab:not(.active) .product-tab-count {
        background: #e5e7eb;
        color: #6b7280;
    }
    
    .filter-bar {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        display: flex;
        gap: 16px;
        align-items: center;
        flex-wrap: wrap;
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
    
    .badge-mvno {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .badge-mno {
        background: #fce7f3;
        color: #9f1239;
    }
    
    .badge-internet {
        background: #d1fae5;
        color: #065f46;
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
        .product-tabs {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .product-tab {
            min-width: 100px;
            padding: 10px 16px;
            font-size: 14px;
        }
        
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

// 탭별 상품 개수 조회
$tabCounts = ['all' => 0, 'mvno' => 0, 'mno' => 0, 'internet' => 0];
try {
    if ($pdo) {
        $countStmt = $pdo->prepare("
            SELECT product_type, COUNT(*) as count
            FROM products
            WHERE seller_id = :seller_id AND status != 'deleted'
            GROUP BY product_type
        ");
        $sellerId = (string)$currentUser['user_id'];
        $countStmt->execute([':seller_id' => $sellerId]);
        $typeCounts = $countStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $tabCounts['mvno'] = $typeCounts['mvno'] ?? 0;
        $tabCounts['mno'] = $typeCounts['mno'] ?? 0;
        $tabCounts['internet'] = $typeCounts['internet'] ?? 0;
        $tabCounts['all'] = array_sum($tabCounts);
    }
} catch (PDOException $e) {
    error_log("Error fetching tab counts: " . $e->getMessage());
}
?>

<div class="product-list-container">
    <div class="page-header">
        <div>
            <h1>등록 상품</h1>
            <p>등록한 상품을 관리하세요</p>
        </div>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="/MVNO/seller/products/mvno.php" class="btn btn-primary">알뜰폰 등록</a>
            <a href="/MVNO/seller/products/mno.php" class="btn btn-primary">통신사폰 등록</a>
            <a href="/MVNO/seller/products/internet.php" class="btn btn-primary">인터넷 등록</a>
        </div>
    </div>
    
    <!-- 탭 메뉴 -->
    <div class="product-tabs">
        <button class="product-tab <?php echo $activeTab === 'all' ? 'active' : ''; ?>" 
                onclick="switchTab('all')">
            전체
            <span class="product-tab-count"><?php echo $tabCounts['all']; ?></span>
        </button>
        <button class="product-tab <?php echo $activeTab === 'mvno' ? 'active' : ''; ?>" 
                onclick="switchTab('mvno')">
            알뜰폰
            <span class="product-tab-count"><?php echo $tabCounts['mvno']; ?></span>
        </button>
        <button class="product-tab <?php echo $activeTab === 'mno' ? 'active' : ''; ?>" 
                onclick="switchTab('mno')">
            통신사폰
            <span class="product-tab-count"><?php echo $tabCounts['mno']; ?></span>
        </button>
        <button class="product-tab <?php echo $activeTab === 'internet' ? 'active' : ''; ?>" 
                onclick="switchTab('internet')">
            인터넷
            <span class="product-tab-count"><?php echo $tabCounts['internet']; ?></span>
        </button>
    </div>
    
    <!-- 필터 바 -->
    <div class="filter-bar">
        <div class="filter-group">
            <label class="filter-label">상태:</label>
            <select class="filter-select" id="filter_status" onchange="applyFilters()">
                <option value="">전체</option>
                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>판매중</option>
                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>판매종료</option>
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
                <div class="empty-state-text">새로운 상품을 등록해보세요</div>
                <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                    <a href="/MVNO/seller/products/mvno.php" class="btn btn-primary">알뜰폰 등록</a>
                    <a href="/MVNO/seller/products/mno.php" class="btn btn-primary">통신사폰 등록</a>
                    <a href="/MVNO/seller/products/internet.php" class="btn btn-primary">인터넷 등록</a>
                </div>
            </div>
        <?php else: ?>
            <table class="product-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">
                            <div style="display: flex; flex-direction: column; gap: 8px; align-items: center;">
                                <button class="btn btn-sm" onclick="bulkInactive()" style="background: #ef4444; color: white; padding: 4px 8px; font-size: 12px; border-radius: 4px; border: none; cursor: pointer;">판매종료</button>
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" style="cursor: pointer;">
                            </div>
                        </th>
                        <th>번호</th>
                        <th>타입</th>
                        <th>상품명</th>
                        <th>통신사/업체</th>
                        <th>월 요금</th>
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
                            <td style="text-align: center;">
                                <input type="checkbox" class="product-checkbox" value="<?php echo $product['id']; ?>" style="cursor: pointer;">
                            </td>
                            <td><?php echo ($page - 1) * $perPage + $index + 1; ?></td>
                            <td>
                                <?php
                                $boardTypeLabels = [
                                    'mvno' => '알뜰폰',
                                    'mno' => '통신사폰',
                                    'internet' => '인터넷'
                                ];
                                $boardTypeLabel = $boardTypeLabels[$product['product_type']] ?? $product['product_type'];
                                $boardTypeClass = 'badge-' . $product['product_type'];
                                ?>
                                <span class="badge <?php echo $boardTypeClass; ?>"><?php echo htmlspecialchars($boardTypeLabel); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($product['product_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($product['provider'] ?? '-'); ?></td>
                            <td><?php echo number_format($product['monthly_fee'] ?? 0); ?>원</td>
                            <td><?php echo number_format($product['view_count'] ?? 0); ?></td>
                            <td><?php echo number_format($product['favorite_count'] ?? 0); ?></td>
                            <td>
                                <a href="#" 
                                   class="review-link" 
                                   onclick="showProductReviews(<?php echo $product['id']; ?>, '<?php echo $product['product_type']; ?>'); return false;"
                                   style="color: #3b82f6; text-decoration: none; font-weight: 600; cursor: pointer;">
                                    <?php echo number_format($product['review_count'] ?? 0); ?>
                                    <?php if (($product['review_count'] ?? 0) > 0): ?>
                                        <?php
                                        require_once __DIR__ . '/../../../includes/data/plan-data.php';
                                        $avgRating = getSingleProductAverageRating($product['id'], $product['product_type']);
                                        if ($avgRating > 0):
                                        ?>
                                            <span style="color: #f59e0b; margin-left: 4px;">⭐ <?php echo number_format($avgRating, 1); ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td><?php echo number_format($product['application_count'] ?? 0); ?></td>
                            <td>
                                <span class="badge <?php echo ($product['status'] ?? 'active') === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo ($product['status'] ?? 'active') === 'active' ? '판매중' : '판매종료'; ?>
                                </span>
                            </td>
                            <td><?php echo isset($product['created_at']) ? date('Y-m-d', strtotime($product['created_at'])) : '-'; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-edit" onclick="editProduct(<?php echo $product['id']; ?>, '<?php echo $product['product_type']; ?>')">수정</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- 페이지네이션 -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <a href="?tab=<?php echo $activeTab; ?>&status=<?php echo htmlspecialchars($status); ?>&page=<?php echo max(1, $page - 1); ?>" 
                       class="pagination-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">이전</a>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?tab=<?php echo $activeTab; ?>&status=<?php echo htmlspecialchars($status); ?>&page=<?php echo $i; ?>" 
                           class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <a href="?tab=<?php echo $activeTab; ?>&status=<?php echo htmlspecialchars($status); ?>&page=<?php echo min($totalPages, $page + 1); ?>" 
                       class="pagination-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">다음</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function switchTab(tab) {
    const params = new URLSearchParams(window.location.search);
    params.set('tab', tab);
    params.delete('page'); // 탭 변경 시 첫 페이지로
    // 탭 변경 시 상태 필터 초기화 (전체로)
    params.delete('status');
    window.location.href = '?' + params.toString();
}

function applyFilters() {
    const status = document.getElementById('filter_status').value;
    const params = new URLSearchParams(window.location.search);
    
    // tab 파라미터 유지
    const tab = params.get('tab') || 'all';
    params.set('tab', tab);
    
    if (status && status !== '') {
        params.set('status', status);
    } else {
        params.delete('status');
    }
    params.delete('page'); // 필터 변경 시 첫 페이지로
    
    window.location.href = '?' + params.toString();
}

function editProduct(productId, productType) {
    // 상품 타입별 수정 페이지로 이동
    const editUrls = {
        'mvno': '/MVNO/seller/products/mvno.php?id=' + productId,
        'mno': '/MVNO/seller/products/mno.php?id=' + productId,
        'internet': '/MVNO/seller/products/internet.php?id=' + productId
    };
    
    if (editUrls[productType]) {
        window.location.href = editUrls[productType];
    } else {
        alert('상품 수정 기능은 준비 중입니다. (상품 ID: ' + productId + ')');
    }
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

// 리뷰 모달 기능
function showProductReviews(productId, productType) {
    const modal = document.getElementById('productReviewModal');
    const modalContent = document.getElementById('productReviewContent');
    const modalTitle = document.getElementById('productReviewTitle');
    
    if (!modal || !modalContent) return;
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    modalContent.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="spinner"></div><p>리뷰를 불러오는 중...</p></div>';
    
    fetch(`/MVNO/api/get-product-reviews.php?product_id=${productId}&product_type=${productType}`)
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
    if (!modalContent) return;
    
    const reviews = data.reviews || [];
    const averageRating = data.average_rating || 0;
    const reviewCount = data.review_count || 0;
    
    let html = '<div style="padding: 20px;">';
    html += '<div style="background: #f9fafb; padding: 16px; border-radius: 8px; margin-bottom: 20px;">';
    html += `<div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">`;
    html += `<div><strong>평균 별점:</strong> <span style="color: #f59e0b; font-weight: 600;">⭐ ${averageRating.toFixed(1)}</span></div>`;
    html += `<div><strong>리뷰 수:</strong> ${reviewCount}개</div>`;
    html += `</div></div>`;
    
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

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && reviewModal && reviewModal.style.display === 'flex') {
        reviewModal.style.display = 'none';
        document.body.style.overflow = '';
    }
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
</style>

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>

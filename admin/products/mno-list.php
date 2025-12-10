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
$seller_id = $_GET['seller_id'] ?? '';
$device_name = $_GET['device_name'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = intval($_GET['per_page'] ?? 20);
// 허용된 per_page 값만 사용 (10, 50, 100)
if (!in_array($perPage, [10, 50, 100])) {
    $perPage = 20;
}

// DB에서 통신사폰 상품 목록 가져오기
$products = [];
$totalProducts = 0;
$totalPages = 1;

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
        
        // 판매자 필터
        if ($seller_id && $seller_id !== '') {
            $whereConditions[] = 'p.seller_id = :seller_id';
            $params[':seller_id'] = $seller_id;
        }
        
        // 단말기명 필터
        if ($device_name && $device_name !== '') {
            $whereConditions[] = 'mno.device_name LIKE :device_name';
            $params[':device_name'] = '%' . $device_name . '%';
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
                mno.provider,
                mno.price_main AS monthly_fee,
                u.name AS seller_name,
                u.user_id AS seller_user_id
            FROM products p
            LEFT JOIN product_mno_details mno ON p.id = mno.product_id
            LEFT JOIN users u ON p.seller_id = u.user_id
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
    error_log("Error fetching MNO products: " . $e->getMessage());
}

// 판매자 목록 가져오기 (필터용)
$sellers = [];
try {
    if ($pdo) {
        $sellerStmt = $pdo->prepare("
            SELECT DISTINCT u.user_id, u.name, u.company_name
            FROM products p
            LEFT JOIN users u ON p.seller_id = u.user_id
            WHERE p.product_type = 'mno' AND p.status != 'deleted'
            ORDER BY u.name
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
        min-width: 80px;
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
</style>

<div class="product-list-container">
    <div class="page-header">
        <div>
            <h1>통신사폰 상품 관리</h1>
            <p>전체 통신사폰 상품을 관리할 수 있습니다.</p>
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
                    <label class="filter-label">판매자:</label>
                    <select class="filter-select" id="filter_seller_id">
                        <option value="">전체</option>
                        <?php foreach ($sellers as $seller): ?>
                            <option value="<?php echo htmlspecialchars($seller['user_id']); ?>" <?php echo $seller_id === $seller['user_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($seller['company_name'] ? $seller['company_name'] : ($seller['name'] ?? $seller['user_id'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">단말기명:</label>
                    <input type="text" class="filter-input" id="filter_device_name" placeholder="단말기명 검색" value="<?php echo htmlspecialchars($device_name); ?>" style="width: 200px;">
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">등록일:</label>
                    <div class="filter-input-group">
                        <input type="date" class="filter-input" id="filter_date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        <span style="color: #6b7280;">~</span>
                        <input type="date" class="filter-input" id="filter_date_to" value="<?php echo htmlspecialchars($date_to); ?>">
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
                <div class="empty-state-text">검색 조건을 변경해보세요</div>
            </div>
        <?php else: ?>
            <table class="product-table">
                <thead>
                    <tr>
                        <th style="text-align: center;">번호</th>
                        <th style="text-align: center;">단말기명</th>
                        <th>통신사</th>
                        <th>판매자</th>
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
                            <td style="text-align: center;"><?php echo $totalProducts - (($page - 1) * $perPage + $index); ?></td>
                            <td style="text-align: left;"><?php echo htmlspecialchars($product['product_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($product['provider'] ?? '-'); ?></td>
                            <td>
                                <?php 
                                $sellerDisplay = $product['seller_name'] ?? $product['seller_user_id'] ?? '-';
                                if ($product['seller_user_id']) {
                                    echo '<a href="/MVNO/admin/users/member-detail.php?user_id=' . urlencode($product['seller_user_id']) . '" style="color: #3b82f6; text-decoration: none;">' . htmlspecialchars($sellerDisplay) . '</a>';
                                } else {
                                    echo htmlspecialchars($sellerDisplay);
                                }
                                ?>
                            </td>
                            <td style="text-align: right;"><?php echo number_format($product['monthly_fee'] ?? 0); ?>원</td>
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
            
            <!-- 페이지네이션 -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination" style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 20px;">
                    <?php
                    $queryParams = [];
                    if ($status) $queryParams['status'] = $status;
                    if ($seller_id) $queryParams['seller_id'] = $seller_id;
                    if ($device_name) $queryParams['device_name'] = $device_name;
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
    
    // 판매자
    const sellerId = document.getElementById('filter_seller_id').value;
    if (sellerId && sellerId !== '') {
        params.set('seller_id', sellerId);
    }
    
    // 단말기명
    const deviceName = document.getElementById('filter_device_name').value.trim();
    if (deviceName) {
        params.set('device_name', deviceName);
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
    document.getElementById('filter_seller_id').value = '';
    document.getElementById('filter_device_name').value = '';
    document.getElementById('filter_date_from').value = '';
    document.getElementById('filter_date_to').value = '';
    
    window.location.href = window.location.pathname;
}

// Enter 키로 검색
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('filter_device_name');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyFilters();
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>


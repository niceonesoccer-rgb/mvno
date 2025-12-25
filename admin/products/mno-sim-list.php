<?php
/**
 * 관리자 통신사유심(MNO-SIM) 상품 목록 페이지
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
        
        // 전체 개수 조회
        $countStmt = $pdo->prepare("
            SELECT COUNT(DISTINCT p.id) as total
            FROM products p
            INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $totalProducts = $countStmt->fetch()['total'];
        $totalPages = ceil($totalProducts / $perPage);
        
        // 통신사유심 상품 목록 조회
        $offset = ($page - 1) * $perPage;
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                mno_sim.plan_name AS product_name,
                mno_sim.provider,
                mno_sim.price_main AS monthly_fee,
                mno_sim.service_type,
                mno_sim.registration_types,
                p.seller_id AS seller_user_id
            FROM products p
            INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
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
        
        // 판매자 정보 매핑
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
        
        // 통합 검색 필터링 (상품명, 통신사, 판매자명)
        if ($search_query && $search_query !== '') {
            $searchLower = mb_strtolower($search_query, 'UTF-8');
            $products = array_filter($products, function($product) use ($searchLower) {
                $productName = mb_strtolower($product['product_name'] ?? '', 'UTF-8');
                $provider = mb_strtolower($product['provider'] ?? '', 'UTF-8');
                $sellerName = mb_strtolower($product['seller_name'] ?? '', 'UTF-8');
                $companyName = mb_strtolower($product['company_name'] ?? '', 'UTF-8');
                return mb_strpos($productName, $searchLower) !== false || 
                       mb_strpos($provider, $searchLower) !== false ||
                       mb_strpos($sellerName, $searchLower) !== false ||
                       mb_strpos($companyName, $searchLower) !== false;
            });
            $products = array_values($products);
            
            // 필터링 후 페이지네이션 재계산
            $totalProducts = count($products);
            $totalPages = ceil($totalProducts / $perPage);
            $offset = ($page - 1) * $perPage;
            $products = array_slice($products, $offset, $perPage);
        }
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
</style>

<div class="admin-content">
    <div class="product-list-container">
        <div class="page-header">
            <h1>통신사유심 등록상품</h1>
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
                        <input type="text" class="filter-input" id="filter_search_query" placeholder="상품명, 통신사, 판매자명" value="<?php echo htmlspecialchars($search_query); ?>" style="width: 250px;" onkeypress="if(event.key==='Enter') applyFilters()">
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
                    <div class="empty-state-title">등록된 통신사유심 상품이 없습니다</div>
                </div>
            <?php else: ?>
                <table class="product-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">
                                <div style="display: flex; flex-direction: column; gap: 8px; align-items: center;">
                                    <button class="btn-sm btn-danger" onclick="bulkInactive()" style="padding: 4px 8px; font-size: 12px;">판매종료</button>
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" style="cursor: pointer;">
                                </div>
                            </th>
                            <th>번호</th>
                            <th>상품명</th>
                            <th>통신사</th>
                            <th>데이터 속도</th>
                            <th>가입 형태</th>
                            <th>판매자</th>
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
                                <td style="text-align: center;">
                                    <input type="checkbox" class="product-checkbox" value="<?php echo $product['id']; ?>" style="cursor: pointer;">
                                </td>
                                <td><?php echo ($page - 1) * $perPage + $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($product['product_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($product['provider'] ?? '-'); ?></td>
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
                                <td><?php echo htmlspecialchars($product['seller_name'] ?? '-'); ?></td>
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
                        <a href="?status=<?php echo htmlspecialchars($status ?? ''); ?>&search_query=<?php echo htmlspecialchars($search_query); ?>&provider=<?php echo htmlspecialchars($provider); ?>&seller_id=<?php echo htmlspecialchars($seller_id); ?>&date_from=<?php echo htmlspecialchars($date_from); ?>&date_to=<?php echo htmlspecialchars($date_to); ?>&per_page=<?php echo $perPage; ?>&page=<?php echo max(1, $page - 1); ?>" 
                           class="pagination-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">이전</a>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?status=<?php echo htmlspecialchars($status ?? ''); ?>&search_query=<?php echo htmlspecialchars($search_query); ?>&provider=<?php echo htmlspecialchars($provider); ?>&seller_id=<?php echo htmlspecialchars($seller_id); ?>&date_from=<?php echo htmlspecialchars($date_from); ?>&date_to=<?php echo htmlspecialchars($date_to); ?>&per_page=<?php echo $perPage; ?>&page=<?php echo $i; ?>" 
                               class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <a href="?status=<?php echo htmlspecialchars($status ?? ''); ?>&search_query=<?php echo htmlspecialchars($search_query); ?>&provider=<?php echo htmlspecialchars($provider); ?>&seller_id=<?php echo htmlspecialchars($seller_id); ?>&date_from=<?php echo htmlspecialchars($date_from); ?>&date_to=<?php echo htmlspecialchars($date_to); ?>&per_page=<?php echo $perPage; ?>&page=<?php echo min($totalPages, $page + 1); ?>" 
                           class="pagination-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">다음</a>
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
    
    const searchQuery = document.getElementById('filter_search_query').value.trim();
    if (searchQuery) params.set('search_query', searchQuery);
    
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
        alert('선택된 상품이 없습니다.');
        return;
    }
    
    const productCount = checkboxes.length;
    const message = '선택한 ' + productCount + '개의 상품을 판매종료 처리하시겠습니까?';
    
    if (confirm(message)) {
        const productIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
        
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
                alert('선택한 상품이 판매종료 처리되었습니다.');
                location.reload();
            } else {
                alert(data.message || '상품 상태 변경에 실패했습니다.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('상품 상태 변경 중 오류가 발생했습니다.');
        });
    }
}
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>


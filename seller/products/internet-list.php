<?php
/**
 * 인터넷 상품 목록 페이지
 * 경로: /seller/products/internet-list.php
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
$registrationPlace = $_GET['registration_place'] ?? '';
$speedOption = $_GET['speed_option'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
if (!in_array($perPage, [10, 20, 50, 100, 500])) {
    $perPage = 10;
}

// DB에서 인터넷 상품 목록 가져오기
$products = [];
$totalProducts = 0;
$totalPages = 1;

try {
    $pdo = getDBConnection();
    if ($pdo) {
        // WHERE 조건 구성
        $sellerId = (string)$currentUser['user_id'];
        $whereConditions = ['p.seller_id = :seller_id', "p.product_type = 'internet'"];
        $params = [':seller_id' => $sellerId];
        
        // 상태 필터
        if ($status && $status !== '') {
            $whereConditions[] = 'p.status = :status';
            $params[':status'] = $status;
        } else {
            $whereConditions[] = "p.status != 'deleted'";
        }
        
        // 가입처 필터
        if ($registrationPlace && $registrationPlace !== '') {
            $whereConditions[] = 'inet.registration_place = :registration_place';
            $params[':registration_place'] = $registrationPlace;
        }
        
        // 인터넷속도 필터
        if ($speedOption && $speedOption !== '') {
            $whereConditions[] = 'inet.speed_option = :speed_option';
            $params[':speed_option'] = $speedOption;
        }
        
        // 등록일 구간 필터
        if ($dateFrom && $dateFrom !== '') {
            $whereConditions[] = 'DATE(p.created_at) >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo && $dateTo !== '') {
            $whereConditions[] = 'DATE(p.created_at) <= :date_to';
            $params[':date_to'] = $dateTo;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // 전체 개수 조회
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM products p
            INNER JOIN product_internet_details inet ON p.id = inet.product_id
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
                inet.registration_place AS provider,
                inet.service_type AS service_type,
                inet.speed_option AS speed_option,
                inet.monthly_fee AS monthly_fee,
                COALESCE(prs.total_review_count, 0) AS review_count
            FROM products p
            INNER JOIN product_internet_details inet ON p.id = inet.product_id
            LEFT JOIN product_review_statistics prs ON p.id = prs.product_id
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
    error_log("Error fetching Internet products: " . $e->getMessage());
}

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
    
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        margin-top: 24px;
        padding: 20px;
        flex-wrap: nowrap;
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
        <h1>인터넷 등록상품</h1>
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
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success" style="margin-bottom: 24px; padding: 16px; border-radius: 8px; background: #d1fae5; color: #065f46; border: 1px solid #10b981;">
            상품이 성공적으로 처리되었습니다.
        </div>
    <?php endif; ?>
    
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
                    <label class="filter-label">가입처:</label>
                    <select class="filter-select" id="filter_registration_place">
                        <option value="">전체</option>
                        <option value="KT" <?php echo $registrationPlace === 'KT' ? 'selected' : ''; ?>>KT</option>
                        <option value="SKT" <?php echo $registrationPlace === 'SKT' ? 'selected' : ''; ?>>SKT</option>
                        <option value="LG U+" <?php echo $registrationPlace === 'LG U+' ? 'selected' : ''; ?>>LG U+</option>
                        <option value="KT skylife" <?php echo $registrationPlace === 'KT skylife' ? 'selected' : ''; ?>>KT skylife</option>
                        <option value="LG헬로비전" <?php echo $registrationPlace === 'LG헬로비전' ? 'selected' : ''; ?>>LG헬로비전</option>
                        <option value="BTV" <?php echo $registrationPlace === 'BTV' ? 'selected' : ''; ?>>BTV</option>
                        <option value="DLIVE" <?php echo $registrationPlace === 'DLIVE' ? 'selected' : ''; ?>>DLIVE</option>
                        <option value="기타" <?php echo $registrationPlace === '기타' ? 'selected' : ''; ?>>기타</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">인터넷속도:</label>
                    <select class="filter-select" id="filter_speed_option">
                        <option value="">전체</option>
                        <option value="100M" <?php echo $speedOption === '100M' ? 'selected' : ''; ?>>100M</option>
                        <option value="500M" <?php echo $speedOption === '500M' ? 'selected' : ''; ?>>500M</option>
                        <option value="1G" <?php echo $speedOption === '1G' ? 'selected' : ''; ?>>1G</option>
                        <option value="2.5G" <?php echo $speedOption === '2.5G' ? 'selected' : ''; ?>>2.5G</option>
                        <option value="5G" <?php echo $speedOption === '5G' ? 'selected' : ''; ?>>5G</option>
                        <option value="10G" <?php echo $speedOption === '10G' ? 'selected' : ''; ?>>10G</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">등록일:</label>
                    <input type="date" class="filter-input" id="filter_date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                    <span style="color: #6b7280;">~</span>
                    <input type="date" class="filter-input" id="filter_date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
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
                <div class="empty-state-text">새로운 인터넷 상품을 등록해보세요</div>
                <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                    <a href="/MVNO/seller/products/internet.php" class="btn btn-primary">인터넷 등록</a>
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
                        <th>가입처</th>
                        <th>결합여부</th>
                        <th>인터넷속도</th>
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
                            <td class="checkbox-column">
                                <input type="checkbox" class="product-checkbox product-checkbox-item" 
                                       value="<?php echo $product['id']; ?>" 
                                       onchange="updateBulkActions()">
                            </td>
                            <td><?php 
                                $productNumber = getProductNumberByType($product['id'], 'internet');
                                echo $productNumber ? htmlspecialchars($productNumber) : htmlspecialchars($product['id'] ?? '-');
                            ?></td>
                            <td>
                                <a href="javascript:void(0);" onclick="showProductInfo(<?php echo $product['id']; ?>, 'internet')" style="color: #3b82f6; text-decoration: none; font-weight: 600; cursor: pointer;">
                                    <?php echo htmlspecialchars($product['provider'] ?? '-'); ?>
                                </a>
                            </td>
                            <td><?php 
                                $serviceType = $product['service_type'] ?? '인터넷';
                                $serviceTypeDisplay = $serviceType;
                                if ($serviceType === '인터넷+TV') {
                                    $serviceTypeDisplay = '인터넷 + TV 결합';
                                } elseif ($serviceType === '인터넷+TV+핸드폰') {
                                    $serviceTypeDisplay = '인터넷 + TV + 핸드폰 결합';
                                }
                                echo htmlspecialchars($serviceTypeDisplay);
                            ?></td>
                            <td><?php echo htmlspecialchars($product['speed_option'] ?? '-'); ?></td>
                            <td><?php 
                                $monthlyFee = $product['monthly_fee'] ?? '';
                                // Extract numeric value from string (remove "원", commas, and non-numeric characters)
                                $monthlyFeeNumeric = 0;
                                if (!empty($monthlyFee)) {
                                    $cleaned = preg_replace('/[^0-9]/', '', $monthlyFee);
                                    $monthlyFeeNumeric = $cleaned !== '' ? intval($cleaned) : 0;
                                }
                                echo number_format($monthlyFeeNumeric, 0, '', ''); ?>원</td>
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
                    $paginationParams = [];
                    if ($status) $paginationParams['status'] = $status;
                    if ($registrationPlace) $paginationParams['registration_place'] = $registrationPlace;
                    if ($speedOption) $paginationParams['speed_option'] = $speedOption;
                    if ($dateFrom) $paginationParams['date_from'] = $dateFrom;
                    if ($dateTo) $paginationParams['date_to'] = $dateTo;
                    $paginationParams['per_page'] = $perPage;
                    $paginationQuery = http_build_query($paginationParams);
                    ?>
                    <a href="?<?php echo $paginationQuery; ?>&page=<?php echo max(1, $page - 1); ?>" 
                       class="pagination-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">이전</a>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?<?php echo $paginationQuery; ?>&page=<?php echo $i; ?>" 
                           class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <a href="?<?php echo $paginationQuery; ?>&page=<?php echo min($totalPages, $page + 1); ?>" 
                       class="pagination-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">다음</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function changePerPage(perPage) {
    const params = new URLSearchParams(window.location.search);
    params.set('per_page', perPage);
    params.set('page', '1'); // 첫 페이지로 이동
    window.location.href = '?' + params.toString();
}

function applyFilters() {
    const status = document.getElementById('filter_status').value;
    const registrationPlace = document.getElementById('filter_registration_place').value;
    const speedOption = document.getElementById('filter_speed_option').value;
    const dateFrom = document.getElementById('filter_date_from').value;
    const dateTo = document.getElementById('filter_date_to').value;
    const perPage = document.getElementById('filter_per_page').value;
    const params = new URLSearchParams();
    
    if (status && status !== '') {
        params.set('status', status);
    }
    if (registrationPlace && registrationPlace !== '') {
        params.set('registration_place', registrationPlace);
    }
    if (speedOption && speedOption !== '') {
        params.set('speed_option', speedOption);
    }
    if (dateFrom && dateFrom !== '') {
        params.set('date_from', dateFrom);
    }
    if (dateTo && dateTo !== '') {
        params.set('date_to', dateTo);
    }
    if (perPage && perPage !== '10') {
        params.set('per_page', perPage);
    }
    params.delete('page');
    
    window.location.href = '?' + params.toString();
}

function resetFilters() {
    // 모든 필터를 기본값으로 초기화하고 페이지 이동
    window.location.href = window.location.pathname;
}

function editProduct(productId) {
    window.location.href = '/MVNO/seller/products/internet.php?id=' + productId;
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
            product_type: 'internet'
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

function setProductInactive(productId) {
    const message = '이 상품을 판매종료 처리하시겠습니까?';
    if (typeof showConfirm === 'function') {
        showConfirm(message, '판매종료 확인').then(confirmed => {
            if (confirmed) {
                processSetProductInactive(productId);
            }
        });
    } else if (confirm(message)) {
        processSetProductInactive(productId);
    }
}

function processSetProductInactive(productId) {
    fetch('/MVNO/api/product-bulk-update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_ids: [productId],
            status: 'inactive'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof showAlert === 'function') {
                showAlert('상품이 판매종료 처리되었습니다.', '완료');
            } else {
                alert('상품이 판매종료 처리되었습니다.');
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
                product_type: 'internet'
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
                
                if (productType === 'internet') {
                    // 판매 상태
                    html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">판매 상태</h3>';
                    html += '<table class="product-info-table">';
                    html += '<tr><th>상태</th><td>' + (product.status === 'active' ? '판매중' : '판매종료') + '</td></tr>';
                    html += '</table></div>';
                    
                    // 기본 정보
                    html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">기본 정보</h3>';
                    html += '<table class="product-info-table">';
                    html += '<tr><th>가입처</th><td>' + (product.registration_place || '-') + '</td></tr>';
                    html += '<tr><th>결합여부</th><td>' + (product.service_type || '-') + '</td></tr>';
                    html += '<tr><th>인터넷속도</th><td>' + (product.speed_option || '-') + '</td></tr>';
                    // 월 요금 처리 (이미 "원"이 포함되어 있을 수 있음)
                    let monthlyFeeText = '-';
                    if (product.monthly_fee) {
                        const monthlyFeeStr = String(product.monthly_fee);
                        // 숫자만 추출
                        const monthlyFeeNum = monthlyFeeStr.replace(/[^0-9]/g, '');
                        if (monthlyFeeNum) {
                            monthlyFeeText = number_format(parseInt(monthlyFeeNum)) + '원';
                        } else {
                            monthlyFeeText = monthlyFeeStr; // 숫자가 없으면 원본 그대로 표시
                        }
                    }
                    html += '<tr><th>월 요금</th><td>' + monthlyFeeText + '</td></tr>';
                    html += '</table></div>';
                    
                    // 현금지급
                    if (product.cash_payment_names && product.cash_payment_prices) {
                        const cashNames = typeof product.cash_payment_names === 'string' ? JSON.parse(product.cash_payment_names) : product.cash_payment_names;
                        const cashPrices = typeof product.cash_payment_prices === 'string' ? JSON.parse(product.cash_payment_prices) : product.cash_payment_prices;
                        if (Array.isArray(cashNames) && cashNames.length > 0 && cashNames.some(name => name)) {
                            html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">현금지급</h3>';
                            html += '<table class="product-info-table">';
                            cashNames.forEach((name, index) => {
                                if (name && name.trim()) {
                                    // 가격 처리
                                    let priceText = '-';
                                    if (cashPrices[index]) {
                                        const priceStr = String(cashPrices[index]);
                                        const priceNum = priceStr.replace(/[^0-9]/g, '');
                                        if (priceNum) {
                                            priceText = number_format(parseInt(priceNum)) + '원';
                                        } else {
                                            priceText = priceStr; // 숫자가 없으면 원본 그대로 표시
                                        }
                                    }
                                    html += '<tr><th>' + name.trim() + '</th><td>' + priceText + '</td></tr>';
                                }
                            });
                            html += '</table></div>';
                        }
                    }
                    
                    // 상품권 지급
                    if (product.gift_card_names && product.gift_card_prices) {
                        const giftNames = typeof product.gift_card_names === 'string' ? JSON.parse(product.gift_card_names) : product.gift_card_names;
                        const giftPrices = typeof product.gift_card_prices === 'string' ? JSON.parse(product.gift_card_prices) : product.gift_card_prices;
                        if (Array.isArray(giftNames) && giftNames.length > 0 && giftNames.some(name => name)) {
                            html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">상품권 지급</h3>';
                            html += '<table class="product-info-table">';
                            giftNames.forEach((name, index) => {
                                if (name && name.trim()) {
                                    // 가격 처리
                                    let priceText = '-';
                                    if (giftPrices[index]) {
                                        const priceStr = String(giftPrices[index]);
                                        const priceNum = priceStr.replace(/[^0-9]/g, '');
                                        if (priceNum) {
                                            priceText = number_format(parseInt(priceNum)) + '원';
                                        } else {
                                            priceText = priceStr; // 숫자가 없으면 원본 그대로 표시
                                        }
                                    }
                                    html += '<tr><th>' + name.trim() + '</th><td>' + priceText + '</td></tr>';
                                }
                            });
                            html += '</table></div>';
                        }
                    }
                    
                    // 장비 및 기타 서비스
                    if ((product.equipment_names && (typeof product.equipment_names === 'string' ? JSON.parse(product.equipment_names) : product.equipment_names).length > 0) || 
                        (product.installation_names && (typeof product.installation_names === 'string' ? JSON.parse(product.installation_names) : product.installation_names).length > 0)) {
                        html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">장비 및 기타 서비스</h3>';
                        html += '<table class="product-info-table">';
                        if (product.equipment_names) {
                            const equipNames = typeof product.equipment_names === 'string' ? JSON.parse(product.equipment_names) : product.equipment_names;
                            const equipPrices = typeof product.equipment_prices === 'string' ? JSON.parse(product.equipment_prices) : product.equipment_prices;
                            if (Array.isArray(equipNames) && equipNames.length > 0 && equipNames.some(name => name)) {
                                html += '<tr><th>장비 제공</th><td>';
                                const equipItems = [];
                                equipNames.forEach((name, index) => {
                                    if (name) {
                                        equipItems.push(name + (equipPrices[index] ? ' (' + equipPrices[index] + ')' : ''));
                                    }
                                });
                                html += equipItems.join(', ') || '-';
                                html += '</td></tr>';
                            }
                        }
                        if (product.installation_names) {
                            const installNames = typeof product.installation_names === 'string' ? JSON.parse(product.installation_names) : product.installation_names;
                            const installPrices = typeof product.installation_prices === 'string' ? JSON.parse(product.installation_prices) : product.installation_prices;
                            if (Array.isArray(installNames) && installNames.length > 0 && installNames.some(name => name)) {
                                html += '<tr><th>설치 및 기타 서비스</th><td>';
                                const installItems = [];
                                installNames.forEach((name, index) => {
                                    if (name) {
                                        installItems.push(name + (installPrices[index] ? ' (' + installPrices[index] + ')' : ''));
                                    }
                                });
                                html += installItems.join(', ') || '-';
                                html += '</td></tr>';
                            }
                        }
                        html += '</table></div>';
                    }
                    
                    // 프로모션 이벤트
                    if (product.promotion_title || (product.promotions && (typeof product.promotions === 'string' ? JSON.parse(product.promotions) : product.promotions).length > 0)) {
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

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>


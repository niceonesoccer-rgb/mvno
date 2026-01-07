<?php
/**
 * 인터넷 상품 목록 페이지 (관리자)
 * 경로: /admin/products/internet-list.php
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
// 디버깅: 모든 GET 파라미터 확인
error_log("인터넷 상품 페이지 로드: GET 파라미터 = " . json_encode($_GET));
error_log("인터넷 상품 페이지 로드: search_query = '" . $search_query . "'");
error_log("인터넷 상품 페이지 로드: search_query empty 체크 = " . (empty($search_query) ? 'true' : 'false'));
error_log("인터넷 상품 페이지 로드: search_query !== '' 체크 = " . ($search_query !== '' ? 'true' : 'false'));
$registration_place = $_GET['registration_place'] ?? '';
$speed_option = $_GET['speed_option'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = intval($_GET['per_page'] ?? 20);
// 허용된 per_page 값만 사용 (10, 20, 50, 100)
if (!in_array($perPage, [10, 20, 50, 100])) {
    $perPage = 20;
}

// DB에서 인터넷 상품 목록 가져오기
$products = [];
$totalProducts = 0;
$totalPages = 1;

try {
    $pdo = getDBConnection();
    if ($pdo) {
        // 디버깅: 실제 데이터 확인
        if ($search_query && trim($search_query) !== '') {
            $debugStmt = $pdo->prepare("
                SELECT p.id, p.seller_id, u.id as user_table_id, u.user_id, u.role
                FROM products p
                LEFT JOIN users u ON p.seller_id = u.id AND u.role = 'seller'
                WHERE p.product_type = 'internet' AND p.status != 'deleted'
                LIMIT 5
            ");
            $debugStmt->execute();
            $debugResults = $debugStmt->fetchAll();
            error_log("디버깅: products.seller_id와 users 테이블 관계 확인");
            foreach ($debugResults as $row) {
                error_log("  product_id: " . $row['id'] . ", seller_id: " . $row['seller_id'] . ", user_table_id: " . ($row['user_table_id'] ?? 'NULL') . ", user_id: " . ($row['user_id'] ?? 'NULL'));
            }
            
            // user_id로 직접 검색
            $userSearchStmt = $pdo->prepare("
                SELECT u.id, u.user_id, u.role
                FROM users u
                WHERE u.user_id LIKE :search_query AND u.role = 'seller'
                LIMIT 5
            ");
            $userSearchStmt->bindValue(':search_query', '%' . $search_query . '%');
            $userSearchStmt->execute();
            $userResults = $userSearchStmt->fetchAll();
            error_log("디버깅: users 테이블에서 검색 결과");
            foreach ($userResults as $row) {
                error_log("  user_table_id: " . $row['id'] . ", user_id: " . $row['user_id']);
            }
        }
        
        // WHERE 조건 구성
        $whereConditions = ["p.product_type = 'internet'"];
        $params = [];
        
        // 상태 필터
        if ($status && $status !== '') {
            $whereConditions[] = 'p.status = :status';
            $params[':status'] = $status;
        } else {
            $whereConditions[] = "p.status != 'deleted'";
        }
        
        // 통합 검색 필터 (SQL에서 직접 처리)
        // 통합 검색은 WHERE 절에서 처리하지 않고, JOIN과 함께 처리
        
        // 가입처 필터
        if ($registration_place && $registration_place !== '') {
            $whereConditions[] = 'inet.registration_place = :registration_place';
            $params[':registration_place'] = $registration_place;
        }
        
        // 속도 옵션 필터
        if ($speed_option && $speed_option !== '') {
            $whereConditions[] = 'inet.speed_option LIKE :speed_option';
            $params[':speed_option'] = '%' . $speed_option . '%';
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
        if ($search_query && trim($search_query) !== '') {
            $searchParam = '%' . $search_query . '%';
            $searchWhere = " AND (
                inet.speed_option LIKE :search_query1
                OR inet.registration_place LIKE :search_query2
                OR CAST(p.seller_id AS CHAR) LIKE :search_query3
                OR u.user_id LIKE :search_query4
                OR COALESCE(u.seller_name, '') LIKE :search_query5
                OR u.name LIKE :search_query6
                OR COALESCE(u.company_name, '') LIKE :search_query7
            )";
            $params[':search_query1'] = $searchParam;
            $params[':search_query2'] = $searchParam;
            $params[':search_query3'] = $searchParam;
            $params[':search_query4'] = $searchParam;
            $params[':search_query5'] = $searchParam;
            $params[':search_query6'] = $searchParam;
            $params[':search_query7'] = $searchParam;
        }
        
        // 전체 개수 조회
        $countSql = "
            SELECT COUNT(DISTINCT p.id) as total
            FROM products p
            INNER JOIN product_internet_details inet ON p.id = inet.product_id
            {$searchJoin}
            WHERE {$whereClause}{$searchWhere}
        ";
        error_log("인터넷 상품 COUNT SQL: " . $countSql);
        error_log("인터넷 상품 COUNT 파라미터: " . json_encode($params));
        $countStmt = $pdo->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $totalProducts = $countStmt->fetch()['total'];
        error_log("인터넷 상품 검색 결과 개수: " . $totalProducts);
        $totalPages = ceil($totalProducts / $perPage);
        
        // 상품 목록 조회
        try {
            $offset = ($page - 1) * $perPage;
            $listSql = "
                SELECT 
                    p.*,
                    p.seller_id,
                    CAST(p.seller_id AS CHAR) AS seller_user_id,
                    CONCAT(inet.registration_place, ' ', inet.speed_option) AS product_name,
                    inet.registration_place AS provider,
                    inet.monthly_fee AS monthly_fee,
                    inet.speed_option AS speed_option,
                    CAST(p.seller_id AS CHAR) AS seller_name,
                    '' AS company_name
                FROM products p
                INNER JOIN product_internet_details inet ON p.id = inet.product_id
                " . ($searchJoin ? $searchJoin . " " : "") . "
                WHERE {$whereClause}{$searchWhere}
                ORDER BY p.id DESC
                LIMIT :limit OFFSET :offset
            ";
            error_log("인터넷 상품 LIST SQL: " . $listSql);
            $stmt = $pdo->prepare($listSql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $products = $stmt->fetchAll();
            
            // 디버깅: 검색 결과 로그
            error_log("인터넷 상품 검색 결과: 검색어 = " . ($search_query ?: '없음') . ", 결과 수 = " . count($products));
        } catch (PDOException $e) {
            error_log("상품 목록 조회 오류: " . $e->getMessage());
            throw $e;
        }
        
        // 판매자 정보 가져오기 (별도 쿼리 - JOIN이 실패할 수 있으므로)
        if (!empty($products)) {
            $sellerIds = array_unique(array_column($products, 'seller_id'));
            $sellerInfoMap = [];
            if (!empty($sellerIds)) {
                // seller_id를 문자열로 변환하여 user_id와 매칭 시도
                $placeholders = implode(',', array_fill(0, count($sellerIds), '?'));
                $sellerStmt = $pdo->prepare("
                    SELECT user_id, seller_name, name, company_name
                    FROM users
                    WHERE user_id IN ($placeholders)
                ");
                $sellerStmt->execute(array_map('strval', $sellerIds));
                $sellerInfos = $sellerStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($sellerInfos as $seller) {
                    $sellerInfoMap[$seller['user_id']] = $seller;
                }
            }
            
            // 판매자 정보 정리
            foreach ($products as &$product) {
                $sellerId = (string)($product['seller_id'] ?? '');
                
                // seller_id를 user_id로 변환하여 판매자 정보 찾기
                if (isset($sellerInfoMap[$sellerId])) {
                    $sellerInfo = $sellerInfoMap[$sellerId];
                    $product['seller_user_id'] = $sellerInfo['user_id'];
                    $product['seller_name'] = $sellerInfo['seller_name'] ?? $sellerInfo['name'] ?? $sellerInfo['user_id'] ?? '-';
                    $product['company_name'] = $sellerInfo['company_name'] ?? '-';
                } else {
                    $product['seller_user_id'] = $sellerId;
                    $product['seller_name'] = $sellerId;
                    $product['company_name'] = '-';
                }
            }
            unset($product);
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching Internet products: " . $e->getMessage());
    $errorMessage = "상품 목록을 불러오는 중 오류가 발생했습니다: " . htmlspecialchars($e->getMessage());
}


// 가입처 목록 가져오기 (필터용)
$registrationPlaces = [];
try {
    if ($pdo) {
        $placeStmt = $pdo->prepare("
            SELECT DISTINCT registration_place
            FROM product_internet_details
            WHERE registration_place IS NOT NULL AND registration_place != ''
            ORDER BY registration_place
        ");
        $placeStmt->execute();
        $registrationPlaces = $placeStmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (PDOException $e) {
    error_log("Error fetching registration places: " . $e->getMessage());
}
?>

<style>
    .product-list-container {
        width: 100%;
        margin: 0;
        padding: 0 20px;
        box-sizing: border-box;
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
        text-align: right;
    }
    
    .filter-group {
        display: flex;
        align-items: center;
        gap: 8px;
        justify-content: flex-start;
    }
    
    .filter-select,
    .filter-input {
        text-align: left;
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
        text-align: center;
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
    <div class="page-header">
        <div>
            <h1 style="margin: 0;">인터넷 상품 관리</h1>
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
    
    <!-- 디버깅 정보 (개발용) -->
    <?php if ($search_query): ?>
    <div style="background: #fef3c7; border: 1px solid #f59e0b; padding: 12px; margin-bottom: 16px; border-radius: 6px; font-size: 13px;">
        <strong>디버깅 정보:</strong><br>
        GET search_query: <?php echo htmlspecialchars($search_query); ?><br>
        search_query empty: <?php echo empty($search_query) ? 'true' : 'false'; ?><br>
        search_query !== '': <?php echo $search_query !== '' ? 'true' : 'false'; ?><br>
        검색 결과 개수: <?php echo count($products); ?><br>
        전체 상품 개수: <?php echo $totalProducts; ?><br>
        <?php if (count($products) > 0): ?>
            첫 번째 상품 seller_id: <?php echo htmlspecialchars($products[0]['seller_id'] ?? 'N/A'); ?><br>
            첫 번째 상품 seller_user_id: <?php echo htmlspecialchars($products[0]['seller_user_id'] ?? 'N/A'); ?><br>
        <?php endif; ?>
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
                    <label class="filter-label" style="text-align: right;">가입처:</label>
                    <select class="filter-select" id="filter_registration_place">
                        <option value="">전체</option>
                        <?php foreach ($registrationPlaces as $place): ?>
                            <option value="<?php echo htmlspecialchars($place); ?>" <?php echo $registration_place === $place ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($place); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group" style="margin-left: -8px; margin-right: -8px;">
                    <label class="filter-label" style="text-align: right;">인터넷속도:</label>
                    <select class="filter-select" id="filter_speed_option">
                        <option value="">전체</option>
                        <option value="100M" <?php echo $speed_option === '100M' ? 'selected' : ''; ?>>100M</option>
                        <option value="500M" <?php echo $speed_option === '500M' ? 'selected' : ''; ?>>500M</option>
                        <option value="1G" <?php echo $speed_option === '1G' ? 'selected' : ''; ?>>1G</option>
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
                    <input type="text" class="filter-input" id="filter_search_query" placeholder="판매자 ID / 판매자명 / 회사명 / 속도 검색" value="<?php echo htmlspecialchars($search_query); ?>" style="width: 100%;" onkeypress="if(event.key === 'Enter') { event.preventDefault(); applyFilters(); }">
                </div>
            </div>
        </div>
        
        <div class="filter-buttons">
            <button class="search-button" onclick="console.log('검색 버튼 클릭됨'); applyFilters(); return false;" style="width: 100%; text-align: center;">검색</button>
            <button class="search-button" onclick="resetFilters()" style="background: #6b7280; width: 100%; text-align: center;">초기화</button>
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
                        <th style="text-align: center;">상품등록번호</th>
                        <th style="text-align: center;">아이디</th>
                        <th style="text-align: center;">판매자명</th>
                        <th style="text-align: center;">회사명</th>
                        <th>가입처</th>
                        <th>인터넷속도</th>
                        <th style="text-align: right;">월 요금</th>
                        <th style="text-align: right;">조회수</th>
                        <th style="text-align: right;">찜</th>
                        <th style="text-align: right;">리뷰</th>
                        <th style="text-align: right;">신청</th>
                        <th style="text-align: center;">포인트</th>
                        <th style="text-align: center;">혜택내용</th>
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
                                $productNumber = getProductNumberByType($product['id'], 'internet');
                                echo $productNumber ? htmlspecialchars($productNumber) : htmlspecialchars($product['id'] ?? '-');
                            ?></td>
                            <td style="text-align: center;">
                                <?php 
                                $sellerId = $product['seller_user_id'] ?? $product['seller_id'] ?? '-';
                                if ($sellerId && $sellerId !== '-') {
                                    echo '<a href="/MVNO/admin/users/seller-detail.php?user_id=' . urlencode($sellerId) . '" style="color: #3b82f6; text-decoration: none; font-weight: 600;">' . htmlspecialchars($sellerId) . '</a>';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($product['seller_name'] ?? '-'); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($product['company_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($product['provider'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($product['speed_option'] ?? '-'); ?></td>
                            <td style="text-align: right;">
                                <?php 
                                $monthlyFee = $product['monthly_fee'] ?? 0;
                                // 문자열에서 숫자만 추출 (예: "3000원" -> 3000)
                                if (is_string($monthlyFee)) {
                                    $monthlyFee = preg_replace('/[^0-9]/', '', $monthlyFee);
                                }
                                $monthlyFee = (float)$monthlyFee;
                                echo $monthlyFee > 0 ? number_format($monthlyFee) . '원' : '-';
                                ?>
                            </td>
                            <td style="text-align: right;"><?php echo number_format($product['view_count'] ?? 0); ?></td>
                            <td style="text-align: right;"><?php echo number_format($product['favorite_count'] ?? 0); ?></td>
                            <td style="text-align: right;">
                                <a href="#" 
                                   class="review-link" 
                                   onclick="showProductReviews(<?php echo $product['id']; ?>, 'internet'); return false;"
                                   style="color: #3b82f6; text-decoration: none; font-weight: 600; cursor: pointer;">
                                    <?php echo number_format($product['review_count'] ?? 0); ?>
                                    <?php if (($product['review_count'] ?? 0) > 0): ?>
                                        <?php
                                        require_once __DIR__ . '/../../../includes/data/plan-data.php';
                                        $avgRating = getSingleProductAverageRating($product['id'], 'internet');
                                        if ($avgRating > 0):
                                        ?>
                                            <span style="color: #f59e0b; margin-left: 4px;">⭐ <?php echo number_format($avgRating, 1); ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td style="text-align: right;"><?php echo number_format($product['application_count'] ?? 0); ?></td>
                            <td style="text-align: center;">
                                <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                    <?php 
                                    $pointSetting = isset($product['point_setting']) ? intval($product['point_setting']) : 0;
                                    if ($pointSetting > 0): 
                                    ?>
                                        <span style="color: #6366f1; font-weight: 600;"><?php echo number_format($pointSetting); ?>P</span>
                                    <?php else: ?>
                                        <span style="color: #9ca3af;">-</span>
                                    <?php endif; ?>
                                    <button type="button" 
                                            class="point-edit-btn" 
                                            onclick="openPointEditModal(<?php echo $product['id']; ?>, <?php echo $pointSetting; ?>, '<?php echo htmlspecialchars($product['point_benefit_description'] ?? '', ENT_QUOTES); ?>')"
                                            title="포인트 편집"
                                            style="background: none; border: none; padding: 4px; cursor: pointer; color: #6b7280; display: inline-flex; align-items: center; justify-content: center;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                    <?php 
                                    $benefitDesc = $product['point_benefit_description'] ?? '';
                                    if (!empty($benefitDesc)): 
                                    ?>
                                        <span style="color: #10b981; font-weight: 500; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($benefitDesc); ?>">
                                            <?php echo htmlspecialchars($benefitDesc); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #9ca3af;">-</span>
                                    <?php endif; ?>
                                    <button type="button" 
                                            class="point-edit-btn" 
                                            onclick="openPointEditModal(<?php echo $product['id']; ?>, <?php echo $pointSetting; ?>, '<?php echo htmlspecialchars($benefitDesc, ENT_QUOTES); ?>')"
                                            title="혜택내용 편집"
                                            style="background: none; border: none; padding: 4px; cursor: pointer; color: #6b7280; display: inline-flex; align-items: center; justify-content: center;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge <?php echo ($product['status'] ?? 'active') === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo ($product['status'] ?? 'active') === 'active' ? '판매중' : '판매종료'; ?>
                                </span>
                            </td>
                            <td style="text-align: center;"><?php echo isset($product['created_at']) ? date('Y-m-d', strtotime($product['created_at'])) : '-'; ?></td>
                            <td style="text-align: center;">
                                <div class="action-buttons">
                                    <a href="/MVNO/internets/internet-detail.php?id=<?php echo $product['id']; ?>" target="_blank" class="btn btn-sm btn-edit">보기</a>
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
                    if ($registration_place) $queryParams['registration_place'] = $registration_place;
                    if ($speed_option) $queryParams['speed_option'] = $speed_option;
                    if ($search_query) $queryParams['search_query'] = $search_query;
                    if ($date_from) $queryParams['date_from'] = $date_from;
                    if ($date_to) $queryParams['date_to'] = $date_to;
                    $queryParams['per_page'] = $perPage;
                    $queryString = http_build_query($queryParams);
                    ?>
                    
                    <?php
                    // 페이지 그룹 계산 (10개씩 그룹화)
                    $pageGroupSize = 10;
                    $currentGroup = ceil($page / $pageGroupSize);
                    $startPage = ($currentGroup - 1) * $pageGroupSize + 1;
                    $endPage = min($currentGroup * $pageGroupSize, $totalPages);
                    $prevGroupLastPage = ($currentGroup - 1) * $pageGroupSize;
                    $nextGroupFirstPage = $currentGroup * $pageGroupSize + 1;
                    ?>
                    
                    <!-- 이전 버튼 -->
                    <?php if ($currentGroup > 1): ?>
                        <a href="?<?php echo $queryString; ?>&page=<?php echo $prevGroupLastPage; ?>" 
                           class="pagination-btn">이전</a>
                    <?php else: ?>
                        <span class="pagination-btn disabled">이전</span>
                    <?php endif; ?>
                    
                    <!-- 페이지 번호 표시 (현재 그룹만) -->
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?<?php echo $queryString; ?>&page=<?php echo $i; ?>" 
                           class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <!-- 다음 버튼 -->
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
// 페이지 로드 시 URL 파라미터 확인
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const searchQuery = urlParams.get('search_query');
    console.log('페이지 로드 시 URL 파라미터:', window.location.search);
    console.log('페이지 로드 시 search_query:', searchQuery);
    if (searchQuery) {
        console.log('검색 쿼리가 URL에 있습니다:', searchQuery);
    } else {
        console.log('검색 쿼리가 URL에 없습니다.');
    }
});

function applyFilters() {
    console.log('applyFilters 함수 실행 시작');
    const params = new URLSearchParams();
    
    // 상태
    const status = document.getElementById('filter_status').value;
    if (status && status !== '') {
        params.set('status', status);
    }
    
    // 가입처
    const registrationPlace = document.getElementById('filter_registration_place').value;
    if (registrationPlace && registrationPlace !== '') {
        params.set('registration_place', registrationPlace);
    }
    
    // 속도 옵션
    const speedOption = document.getElementById('filter_speed_option').value;
    if (speedOption && speedOption !== '') {
        params.set('speed_option', speedOption);
    }
    
    // 통합 검색
    const searchInput = document.getElementById('filter_search_query');
    console.log('검색 입력 필드:', searchInput);
    const searchQuery = searchInput ? searchInput.value.trim() : '';
    console.log('검색 쿼리 값:', searchQuery);
    if (searchQuery) {
        params.set('search_query', searchQuery);
        console.log('검색 쿼리 전송:', searchQuery);
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
    
    const queryString = params.toString();
    console.log('검색 파라미터:', queryString);
    console.log('이동할 URL:', '?' + queryString);
    window.location.href = '?' + queryString;
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
    document.getElementById('filter_registration_place').value = '';
    document.getElementById('filter_speed_option').value = '';
    document.getElementById('filter_search_query').value = '';
    document.getElementById('filter_date_from').value = '';
    document.getElementById('filter_date_to').value = '';
    
    window.location.href = window.location.pathname;
}

// Enter 키로 검색 및 일괄 작업 초기화
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
    
    // 일괄 변경 셀렉트박스 변경 이벤트
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
        
        // 디버깅: 리뷰 데이터 확인
        if (reviews.length > 0) {
            console.log('First review data:', reviews[0]);
        }
        
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
                // 통신사 이름 처리 (provider 필드 확인)
                const provider = (review.provider && review.provider.trim()) ? review.provider.trim() : '';
                const providerText = provider ? ` | ${provider}` : '';
                html += `<div><strong>${review.author_name || '익명'}</strong>${providerText}</div>`;
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

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

<script>
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
let pendingProductIds = [];
let pendingStatus = '';

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

function applyFilters() {
    const params = new URLSearchParams();
    
    // 상태
    const status = document.getElementById('filter_status').value;
    if (status && status !== '') {
        params.set('status', status);
    }
    
    // 가입처
    const registrationPlace = document.getElementById('filter_registration_place').value;
    if (registrationPlace && registrationPlace !== '') {
        params.set('registration_place', registrationPlace);
    }
    
    // 속도 옵션
    const speedOption = document.getElementById('filter_speed_option').value;
    if (speedOption && speedOption !== '') {
        params.set('speed_option', speedOption);
    }
    
    // 통합 검색
    const searchInput = document.getElementById('filter_search_query');
    const searchQuery = searchInput ? searchInput.value.trim() : '';
    if (searchQuery) {
        params.set('search_query', searchQuery);
        console.log('검색 쿼리 전송:', searchQuery);
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
    
    const queryString = params.toString();
    console.log('검색 파라미터:', queryString);
    window.location.href = '?' + queryString;
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
    document.getElementById('filter_registration_place').value = '';
    document.getElementById('filter_speed_option').value = '';
    document.getElementById('filter_search_query').value = '';
    document.getElementById('filter_date_from').value = '';
    document.getElementById('filter_date_to').value = '';
    
    window.location.href = window.location.pathname;
}

// Enter 키로 검색 및 일괄 작업 초기화
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
    
    // 일괄 변경 셀렉트박스 변경 이벤트
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
        
        // 디버깅: 리뷰 데이터 확인
        if (reviews.length > 0) {
            console.log('First review data:', reviews[0]);
        }
        
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
                // 통신사 이름 처리 (provider 필드 확인)
                const provider = (review.provider && review.provider.trim()) ? review.provider.trim() : '';
                const providerText = provider ? ` | ${provider}` : '';
                html += `<div><strong>${review.author_name || '익명'}</strong>${providerText}</div>`;
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

.point-edit-btn:hover {
    background: #f3f4f6 !important;
    color: #374151 !important;
}

/* 포인트 편집 모달 */
#pointEditModal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

#pointEditModal.show {
    display: flex;
}

.point-edit-modal-content {
    position: relative;
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    padding: 0;
    z-index: 10001;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.point-edit-modal-header {
    padding: 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.point-edit-modal-header h2 {
    font-size: 20px;
    font-weight: 700;
    margin: 0;
    color: #1f2937;
}

.point-edit-modal-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: background 0.2s;
}

.point-edit-modal-close:hover {
    background: #f3f4f6;
}

.point-edit-modal-body {
    padding: 24px;
}

.point-edit-form-group {
    margin-bottom: 20px;
}

.point-edit-form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.point-edit-form-input {
    width: 100%;
    padding: 10px 12px;
    font-size: 14px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    box-sizing: border-box;
}

.point-edit-form-input:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.point-edit-form-textarea {
    width: 100%;
    padding: 10px 12px;
    font-size: 14px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    box-sizing: border-box;
    min-height: 100px;
    resize: vertical;
    font-family: inherit;
}

.point-edit-form-textarea:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.point-edit-form-help {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.point-edit-modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.point-edit-btn {
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.point-edit-btn-primary {
    background: #6366f1;
    color: white;
}

.point-edit-btn-primary:hover {
    background: #4f46e5;
}

.point-edit-btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.point-edit-btn-secondary:hover {
    background: #e5e7eb;
}

.point-edit-btn-danger {
    background: #ef4444;
    color: white;
}

.point-edit-btn-danger:hover {
    background: #dc2626;
}

.point-edit-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>

<!-- 포인트 편집 모달 -->
<div id="pointEditModal" class="point-edit-modal">
    <div class="point-edit-modal-content">
        <div class="point-edit-modal-header">
            <h2>포인트 및 혜택내용 편집</h2>
            <button type="button" class="point-edit-modal-close" onclick="closePointEditModal()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6L18 18" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="point-edit-modal-body">
            <form id="pointEditForm" onsubmit="savePointEdit(event)">
                <input type="hidden" id="pointEditProductId" value="">
                <div class="point-edit-form-group">
                    <label class="point-edit-form-label" for="pointEditPointSetting">
                        포인트 설정
                    </label>
                    <input type="number" 
                           id="pointEditPointSetting" 
                           class="point-edit-form-input" 
                           min="0" 
                           step="1000" 
                           placeholder="0"
                           required>
                    <div class="point-edit-form-help">1000포인트 단위로 입력해주세요. (예: 3000, 5000, 10000) 삭제하려면 0을 입력하세요.</div>
                </div>
                <div class="point-edit-form-group">
                    <label class="point-edit-form-label" for="pointEditBenefitDescription">
                        혜택내용
                    </label>
                    <textarea id="pointEditBenefitDescription" 
                              class="point-edit-form-textarea" 
                              placeholder="예: 네이버페이 5000원 지급 익월말"></textarea>
                    <div class="point-edit-form-help">고객에게 표시될 혜택 내용을 입력해주세요. 삭제하려면 비워두세요.</div>
                </div>
            </form>
        </div>
        <div class="point-edit-modal-footer">
            <button type="button" class="point-edit-btn point-edit-btn-danger" onclick="deletePointEdit()">삭제</button>
            <button type="button" class="point-edit-btn point-edit-btn-secondary" onclick="closePointEditModal()">취소</button>
            <button type="button" class="point-edit-btn point-edit-btn-primary" onclick="savePointEdit()">저장</button>
        </div>
    </div>
</div>

<script>
let currentPointEditProductId = null;

function openPointEditModal(productId, pointSetting, benefitDescription) {
    currentPointEditProductId = productId;
    const modal = document.getElementById('pointEditModal');
    const pointInput = document.getElementById('pointEditPointSetting');
    const benefitInput = document.getElementById('pointEditBenefitDescription');
    const productIdInput = document.getElementById('pointEditProductId');
    
    if (modal && pointInput && benefitInput && productIdInput) {
        productIdInput.value = productId;
        pointInput.value = pointSetting || 0;
        benefitInput.value = benefitDescription || '';
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // 포인트 입력 검증
        pointInput.addEventListener('input', function() {
            const value = parseInt(this.value) || 0;
            if (value > 0 && value % 1000 !== 0) {
                this.setCustomValidity('1000포인트 단위로 입력해주세요.');
            } else {
                this.setCustomValidity('');
            }
        });
    }
}

function closePointEditModal() {
    const modal = document.getElementById('pointEditModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
        currentPointEditProductId = null;
    }
}

function deletePointEdit() {
    if (!confirm('포인트와 혜택내용을 모두 삭제하시겠습니까?')) {
        return;
    }
    
    const productId = document.getElementById('pointEditProductId').value;
    const pointInput = document.getElementById('pointEditPointSetting');
    const benefitInput = document.getElementById('pointEditBenefitDescription');
    
    // 입력 필드 비우기
    if (pointInput) pointInput.value = 0;
    if (benefitInput) benefitInput.value = '';
    
    // 저장 버튼 비활성화
    const saveBtn = document.querySelector('.point-edit-btn-primary');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.textContent = '삭제 중...';
    }
    
    // API 호출
    fetch('/MVNO/api/admin/update-product-point.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            point_setting: 0,
            point_benefit_description: ''
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlertModal('성공', '포인트 및 혜택내용이 삭제되었습니다.');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlertModal('오류', data.message || '삭제에 실패했습니다.');
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = '저장';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlertModal('오류', '삭제 중 오류가 발생했습니다.');
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.textContent = '저장';
        }
    });
}

function savePointEdit(event) {
    if (event) {
        event.preventDefault();
    }
    
    const productId = document.getElementById('pointEditProductId').value;
    const pointSetting = parseInt(document.getElementById('pointEditPointSetting').value) || 0;
    const benefitDescription = document.getElementById('pointEditBenefitDescription').value.trim();
    
    // 포인트 검증
    if (pointSetting > 0 && pointSetting % 1000 !== 0) {
        showAlertModal('오류', '포인트는 1000포인트 단위로 입력해주세요.');
        return;
    }
    
    // 저장 버튼 비활성화
    const saveBtn = document.querySelector('.point-edit-btn-primary');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.textContent = '저장 중...';
    }
    
    // API 호출
    fetch('/MVNO/api/admin/update-product-point.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            point_setting: pointSetting,
            point_benefit_description: benefitDescription
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlertModal('성공', '포인트 및 혜택내용이 저장되었습니다.');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlertModal('오류', data.message || '저장에 실패했습니다.');
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = '저장';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlertModal('오류', '저장 중 오류가 발생했습니다.');
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.textContent = '저장';
        }
    });
}

// 모달 오버레이 클릭 시 닫기
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('pointEditModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closePointEditModal();
            }
        });
    }
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('show')) {
            closePointEditModal();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>


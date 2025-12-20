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
        
        // 통합 검색 필터 (속도 옵션만 SQL에서 처리)
        if ($search_query && $search_query !== '') {
            $whereConditions[] = 'inet.speed_option IS NOT NULL AND inet.speed_option LIKE :search_query';
            $params[':search_query'] = '%' . $search_query . '%';
        }
        
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
        
        // 전체 개수 조회
        $countStmt = $pdo->prepare("
            SELECT COUNT(DISTINCT p.id) as total
            FROM products p
            INNER JOIN product_internet_details inet ON p.id = inet.product_id
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $totalProducts = $countStmt->fetch()['total'];
        $totalPages = ceil($totalProducts / $perPage);
        
        // 상품 목록 조회
        // 통합 검색이 있으면 모든 데이터를 가져온 후 필터링, 없으면 페이지네이션 적용
        try {
            if ($search_query && $search_query !== '') {
                // 통합 검색: 모든 데이터 가져온 후 필터링
                $stmt = $pdo->prepare("
                    SELECT 
                        p.*,
                        p.seller_id,
                        CONCAT(inet.registration_place, ' ', inet.speed_option) AS product_name,
                        inet.registration_place AS provider,
                        inet.monthly_fee AS monthly_fee,
                        inet.speed_option AS speed_option
                    FROM products p
                    INNER JOIN product_internet_details inet ON p.id = inet.product_id
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
                        CONCAT(inet.registration_place, ' ', inet.speed_option) AS product_name,
                        inet.registration_place AS provider,
                        inet.monthly_fee AS monthly_fee,
                        inet.speed_option AS speed_option
                    FROM products p
                    INNER JOIN product_internet_details inet ON p.id = inet.product_id
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
            error_log("상품 목록 조회 오류: " . $e->getMessage());
            throw $e;
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
                    COALESCE(NULLIF(u.company_name,''), NULLIF(u.name,''), u.user_id) AS display_name,
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
            $product['seller_user_id'] = $sellerId;
            if ($sellerId && isset($sellerMap[$sellerId])) {
                $product['seller_name'] = $sellerMap[$sellerId]['display_name'] ?? '-';
                $product['company_name'] = $sellerMap[$sellerId]['company_name'] ?? '-';
            } else {
                $product['seller_name'] = '-';
                $product['company_name'] = '-';
            }
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
                
                // 속도 옵션 검색 (이미 SQL에서 처리했지만 추가 검증)
                $speedOption = mb_strtolower($product['speed_option'] ?? '', 'UTF-8');
                if (mb_strpos($speedOption, $searchLower) !== false) {
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
    error_log("Error fetching Internet products: " . $e->getMessage());
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
    <!-- 상품 관리 네비게이션 탭 -->
    <div class="product-nav-tabs">
        <a href="/MVNO/admin/products/mvno-list.php" class="product-nav-tab">알뜰폰 관리</a>
        <a href="/MVNO/admin/products/mno-list.php" class="product-nav-tab">통신사폰 관리</a>
        <a href="/MVNO/admin/products/internet-list.php" class="product-nav-tab active">인터넷 관리</a>
    </div>
    
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
                    <input type="text" class="filter-input" id="filter_search_query" placeholder="판매자 ID / 판매자명 / 회사명 / 속도 검색" value="<?php echo htmlspecialchars($search_query); ?>" style="width: 100%;">
                </div>
            </div>
        </div>
        
        <div class="filter-buttons">
            <button class="search-button" onclick="applyFilters()" style="width: 100%; text-align: center;">검색</button>
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
            <table class="product-table">
                <thead>
                    <tr>
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
                        <th style="text-align: center;">상태</th>
                        <th style="text-align: center;">등록일</th>
                        <th style="text-align: center;">관리</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $index => $product): ?>
                        <tr>
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
                            <td style="text-align: right;"><?php echo number_format($product['monthly_fee'] ?? 0); ?>원</td>
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
    document.getElementById('filter_registration_place').value = '';
    document.getElementById('filter_speed_option').value = '';
    document.getElementById('filter_search_query').value = '';
    document.getElementById('filter_date_from').value = '';
    document.getElementById('filter_date_to').value = '';
    
    window.location.href = window.location.pathname;
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

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>


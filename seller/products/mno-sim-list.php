<?php
/**
 * 판매자 통신사단독유심(MNO-SIM) 상품 목록 페이지
 * 경로: /seller/products/mno-sim-list.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

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

$sellerId = (string)$currentUser['user_id'];
$error = '';
$success = '';

// 광고 신청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_advertisement') {
    $productId = intval($_POST['product_id'] ?? 0);
    $advertisementDays = intval($_POST['advertisement_days'] ?? 0);
    
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            throw new Exception('데이터베이스 연결에 실패했습니다.');
        }
        
        // system_settings에서 현재 로테이션 시간 가져오기
        $rotationDuration = 30; // 기본값
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'advertisement_rotation_duration'");
        $stmt->execute();
        $durationValue = $stmt->fetchColumn();
        if ($durationValue) {
            $rotationDuration = intval($durationValue);
        }
        
        if ($productId <= 0 || $advertisementDays <= 0) {
            throw new Exception('모든 필드를 올바르게 선택해주세요.');
        }
        
        $pdo->beginTransaction();
        
        // 상품 정보 조회
        $stmt = $pdo->prepare("SELECT id, seller_id, product_type, status FROM products WHERE id = :id AND seller_id = :seller_id");
        $stmt->execute([':id' => $productId, ':seller_id' => $sellerId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception('상품을 찾을 수 없습니다.');
        }
        
        if ($product['status'] !== 'active') {
            throw new Exception('판매중인 상품만 광고할 수 있습니다.');
        }
        
        // 같은 상품의 활성화된 광고 중복 체크
        $stmt = $pdo->prepare("
            SELECT id FROM rotation_advertisements 
            WHERE product_id = :product_id 
            AND status = 'active' 
            AND end_datetime > NOW()
        ");
        $stmt->execute([':product_id' => $productId]);
        if ($stmt->fetch()) {
            throw new Exception('이미 광고 중인 상품입니다. 광고가 종료된 후 다시 신청해주세요.');
        }
        
        // 가격 조회
        // rotation_advertisement_prices 테이블은 mno_sim (언더스코어)를 사용하므로 변환
        $priceProductType = $product['product_type'];
        if ($priceProductType === 'mno-sim') {
            $priceProductType = 'mno_sim';
        }
        
        $stmt = $pdo->prepare("
            SELECT price FROM rotation_advertisement_prices 
            WHERE product_type = :product_type 
            AND advertisement_days = :advertisement_days 
            AND is_active = 1
        ");
        $stmt->execute([
            ':product_type' => $priceProductType,
            ':advertisement_days' => $advertisementDays
        ]);
        $priceData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$priceData) {
            throw new Exception('선택한 조건의 가격 정보를 찾을 수 없습니다.');
        }
        
        $supplyAmount = floatval($priceData['price']); // 공급가액
        $taxAmount = $supplyAmount * 0.1; // 부가세 (10%)
        $totalAmount = $supplyAmount + $taxAmount; // 부가세 포함 총액
        
        // 예치금 잔액 확인 (부가세 포함 금액으로 확인)
        $stmt = $pdo->prepare("SELECT balance FROM seller_deposit_accounts WHERE seller_id = :seller_id FOR UPDATE");
        $stmt->execute([':seller_id' => $sellerId]);
        $balanceData = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentBalance = floatval($balanceData['balance'] ?? 0);
        
        if ($currentBalance < $totalAmount) {
            throw new Exception('예치금 잔액이 부족합니다. 예치금을 충전해주세요.');
        }
        
        // 광고 등록
        // rotation_advertisements 테이블도 mno_sim (언더스코어)를 사용하므로 변환
        $adProductType = $product['product_type'];
        if ($adProductType === 'mno-sim') {
            $adProductType = 'mno_sim';
        }
        
        $startDatetime = date('Y-m-d H:i:s');
        $endDatetime = date('Y-m-d H:i:s', strtotime($startDatetime) + ($advertisementDays * 86400));
        
        $stmt = $pdo->prepare("
            INSERT INTO rotation_advertisements 
            (product_id, seller_id, product_type, rotation_duration, advertisement_days, price, start_datetime, end_datetime, status)
            VALUES (:product_id, :seller_id, :product_type, :rotation_duration, :advertisement_days, :price, :start_datetime, :end_datetime, 'active')
        ");
        $stmt->execute([
            ':product_id' => $productId,
            ':seller_id' => $sellerId,
            ':product_type' => $adProductType,
            ':rotation_duration' => $rotationDuration,
            ':advertisement_days' => $advertisementDays,
            ':price' => $supplyAmount,
            ':start_datetime' => $startDatetime,
            ':end_datetime' => $endDatetime
        ]);
        
        $adId = $pdo->lastInsertId();
        
        // 예치금 차감 (부가세 포함 총액 차감)
        $newBalance = $currentBalance - $totalAmount;
        $pdo->prepare("UPDATE seller_deposit_accounts SET balance = :balance, updated_at = NOW() WHERE seller_id = :seller_id")
            ->execute([':balance' => $newBalance, ':seller_id' => $sellerId]);
        
        // 예치금 내역 기록 (부가세 포함 총액 차감)
        $pdo->prepare("
            INSERT INTO seller_deposit_ledger 
            (seller_id, transaction_type, amount, balance_before, balance_after, advertisement_id, description, created_at)
            VALUES (:seller_id, 'withdraw', :amount, :balance_before, :balance_after, :advertisement_id, :description, NOW())
        ")->execute([
            ':seller_id' => $sellerId,
            ':amount' => -$totalAmount,
            ':balance_before' => $currentBalance,
            ':balance_after' => $newBalance,
            ':advertisement_id' => $adId,
            ':description' => '광고 신청 차감'
        ]);
        
        $pdo->commit();
        $success = '광고 신청이 완료되었습니다.';
        // 페이지 새로고침을 위해 JavaScript로 처리
        echo '<script>alert("광고 신청이 완료되었습니다."); window.location.reload();</script>';
        exit;
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Advertisement apply error: ' . $e->getMessage());
        $error = $e->getMessage();
        echo '<script>alert("' . htmlspecialchars($error, ENT_QUOTES) . '");</script>';
        exit;
    }
}

// 검색 필터 파라미터
$status = $_GET['status'] ?? '';
if ($status === '') {
    $status = null;
}

$search_query = $_GET['search_query'] ?? '';
$provider = $_GET['provider'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = intval($_GET['per_page'] ?? 10);
if (!in_array($perPage, [10, 20, 50, 100, 500])) {
    $perPage = 10;
}

// DB에서 통신사단독유심(mno-sim) 상품 목록만 가져오기
$products = [];
$totalProducts = 0;
$totalPages = 1;

try {
    $pdo = getDBConnection();
    if ($pdo) {
        $sellerId = (string)$currentUser['user_id'];
        $whereConditions = ['p.seller_id = :seller_id', "p.product_type = 'mno-sim'"];
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
            $whereConditions[] = 'mno_sim.provider = :provider';
            $params[':provider'] = $provider;
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
        
        // 통신사단독유심 상품 목록 조회
        $offset = ($page - 1) * $perPage;
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                mno_sim.plan_name AS product_name,
                mno_sim.provider,
                mno_sim.price_main AS monthly_fee,
                mno_sim.price_after,
                mno_sim.service_type,
                mno_sim.registration_types,
                mno_sim.discount_period,
                mno_sim.discount_period_value,
                mno_sim.discount_period_unit,
                COALESCE(prs.total_review_count, 0) AS review_count,
                CASE WHEN EXISTS (
                    SELECT 1 FROM rotation_advertisements ra 
                    WHERE ra.product_id = p.id 
                    AND ra.status = 'active' 
                    AND ra.end_datetime > NOW()
                ) THEN 1 ELSE 0 END AS has_active_ad
            FROM products p
            INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
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
        
        // 통합 검색 필터링 (상품명, 통신사)
        if ($search_query && $search_query !== '') {
            $searchLower = mb_strtolower($search_query, 'UTF-8');
            $products = array_filter($products, function($product) use ($searchLower) {
                $productName = mb_strtolower($product['product_name'] ?? '', 'UTF-8');
                $provider = mb_strtolower($product['provider'] ?? '', 'UTF-8');
                return mb_strpos($productName, $searchLower) !== false || 
                       mb_strpos($provider, $searchLower) !== false;
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

// 예치금 잔액 조회 (광고 신청 모달용)
$balance = 0;
try {
    if ($pdo) {
        $balanceStmt = $pdo->prepare("SELECT balance FROM seller_deposit_accounts WHERE seller_id = :seller_id");
        $balanceStmt->execute([':seller_id' => $sellerId]);
        $balanceResult = $balanceStmt->fetch(PDO::FETCH_ASSOC);
        $balance = floatval($balanceResult['balance'] ?? 0);
    }
} catch (PDOException $e) {
    error_log("Error fetching balance: " . $e->getMessage());
}

// 광고 기간 옵션
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
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .btn-sm {
        padding: 6px 12px;
        font-size: 13px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
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
';

include __DIR__ . '/../includes/seller-header.php';
?>

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
                        <option value="KT" <?php echo $provider === 'KT' ? 'selected' : ''; ?>>KT</option>
                        <option value="SKT" <?php echo $provider === 'SKT' ? 'selected' : ''; ?>>SKT</option>
                        <option value="LG U+" <?php echo $provider === 'LG U+' ? 'selected' : ''; ?>>LG U+</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">검색:</label>
                    <input type="text" class="filter-input" id="filter_search_query" placeholder="상품명, 통신사" value="<?php echo htmlspecialchars($search_query); ?>" style="width: 250px;" onkeypress="if(event.key==='Enter') applyFilters()">
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
                <div class="empty-state-text">새로운 통신사단독유심 상품을 등록해보세요</div>
                <div style="margin-top: 20px;">
                    <a href="/MVNO/seller/products/mno-sim.php" class="btn-sm btn-edit" style="text-decoration: none;">통신사단독유심 등록</a>
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
                        <th>번호</th>
                        <th>상품명</th>
                        <th>통신사</th>
                        <th>데이터 속도</th>
                        <th>가입 형태</th>
                        <th>월 요금</th>
                        <th>할인기간</th>
                        <th>할인기간요금</th>
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
                            <td><?php echo $totalProducts - (($page - 1) * $perPage + $index); ?></td>
                            <td>
                                <a href="javascript:void(0);" onclick="showProductInfo(<?php echo $product['id']; ?>, 'mno-sim')" style="color: #3b82f6; text-decoration: none; font-weight: 600; cursor: pointer;" title="<?php echo htmlspecialchars($product['product_name'] ?? '-'); ?>">
                                    <?php 
                                    $productName = $product['product_name'] ?? '-';
                                    if ($productName !== '-' && mb_strlen($productName, 'UTF-8') > 15) {
                                        echo htmlspecialchars(mb_substr($productName, 0, 15, 'UTF-8')) . '...';
                                    } else {
                                        echo htmlspecialchars($productName);
                                    }
                                    ?>
                                </a>
                            </td>
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
                            <td>
                                <?php
                                $monthlyFee = $product['monthly_fee'] ?? 0;
                                // 문자열인 경우 숫자만 추출
                                if (is_string($monthlyFee)) {
                                    $monthlyFee = preg_replace('/[^0-9.]/', '', $monthlyFee);
                                }
                                $monthlyFee = intval($monthlyFee);
                                echo $monthlyFee > 0 ? number_format($monthlyFee, 0, '', '') . '원' : '-';
                                ?>
                            </td>
                            <td>
                                <?php
                                $discountPeriod = $product['discount_period'] ?? '';
                                $discountPeriodValue = $product['discount_period_value'] ?? '';
                                $discountPeriodUnit = $product['discount_period_unit'] ?? '';
                                
                                if ($discountPeriod && $discountPeriod !== '프로모션 없음') {
                                    if ($discountPeriod === '직접입력' && $discountPeriodValue) {
                                        echo htmlspecialchars($discountPeriodValue . ($discountPeriodUnit ?: '개월'));
                                    } else {
                                        echo htmlspecialchars($discountPeriod);
                                    }
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                $priceAfter = $product['price_after'] ?? null;
                                if ($priceAfter !== null && $priceAfter !== '') {
                                    // 숫자로 변환
                                    if (is_string($priceAfter)) {
                                        $priceAfter = preg_replace('/[^0-9.]/', '', $priceAfter);
                                    }
                                    $priceAfterNum = floatval($priceAfter);
                                    if ($priceAfterNum == 0) {
                                        echo '공짜';
                                    } else {
                                        echo number_format($priceAfterNum, 0, '', '') . '원';
                                    }
                                } else {
                                    echo '-';
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
                                    <button class="btn-sm btn-edit" onclick="editProduct(<?php echo $product['id']; ?>)">수정</button>
                                    <button class="btn-sm btn-copy" onclick="copyProduct(<?php echo $product['id']; ?>)">복사</button>
                                    <?php 
                                    $hasActiveAd = intval($product['has_active_ad'] ?? 0);
                                    if ($hasActiveAd): 
                                    ?>
                                        <span class="btn-sm" style="background: #f59e0b; color: white; border: none; padding: 4px 12px; border-radius: 4px; margin-left: 4px; font-size: 12px; font-weight: 600; display: inline-block;">광고중</span>
                                    <?php else: ?>
                                        <button type="button" onclick="openAdModal(<?php echo $product['id']; ?>, 'mno-sim', '<?php echo htmlspecialchars($product['product_name'] ?? '', ENT_QUOTES); ?>')" class="btn-sm" style="background: #6366f1; color: white; border: none; padding: 4px 12px; border-radius: 4px; margin-left: 4px; font-size: 12px; cursor: pointer;">광고</button>
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
                ?>
                <?php
                $paginationParams = [];
                if ($status) $paginationParams['status'] = $status;
                if ($search_query) $paginationParams['search_query'] = $search_query;
                if ($provider) $paginationParams['provider'] = $provider;
                $paginationParams['per_page'] = $perPage;
                $paginationQuery = http_build_query($paginationParams);
                ?>
                <div class="pagination">
                    <?php if ($prevGroupLastPage > 0): ?>
                        <a href="?<?php echo $paginationQuery; ?>&page=<?php echo $prevGroupLastPage; ?>" 
                           class="pagination-btn">이전</a>
                    <?php else: ?>
                        <span class="pagination-btn disabled">이전</span>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?<?php echo $paginationQuery; ?>&page=<?php echo $i; ?>" 
                           class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($nextGroupFirstPage <= $totalPages): ?>
                        <a href="?<?php echo $paginationQuery; ?>&page=<?php echo $nextGroupFirstPage; ?>" 
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
                product_type: 'mno-sim'
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

function editProduct(productId) {
    window.location.href = '/MVNO/seller/products/mno-sim.php?id=' + productId;
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
            product_type: 'mno-sim'
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
                
                // 상품 타입별 필드 표시
                if (productType === 'mno-sim') {
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
                        if (product.contract_period === '선택약정할인' || product.contract_period === '공시지원할인') {
                            if (product.contract_period_discount_value) {
                                contractText += ' ' + product.contract_period_discount_value + (product.contract_period_discount_unit || '개월');
                            }
                        }
                        html += '<tr><th>할인방법</th><td>' + contractText + '</td></tr>';
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
                    if (product.plan_maintenance_period_type) {
                        let maintenanceText = product.plan_maintenance_period_type;
                        if (product.plan_maintenance_period_type === '직접입력' && product.plan_maintenance_period_value) {
                            maintenanceText = (product.plan_maintenance_period_prefix || 'M') + '+' + product.plan_maintenance_period_value + (product.plan_maintenance_period_unit || '개월');
                        }
                        html += '<tr><th>요금제 유지기간</th><td>' + maintenanceText + '</td></tr>';
                    }
                    if (product.sim_change_restriction_period_type) {
                        let restrictionText = product.sim_change_restriction_period_type;
                        if (product.sim_change_restriction_period_type === '직접입력' && product.sim_change_restriction_period_value) {
                            restrictionText = (product.sim_change_restriction_period_prefix || 'M') + '+' + product.sim_change_restriction_period_value + (product.sim_change_restriction_period_unit || '개월');
                        }
                        html += '<tr><th>유심기변 불가기간</th><td>' + restrictionText + '</td></tr>';
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
                    
                    // 혜택
                    if (product.promotion_title || (product.promotions && product.promotions.length > 0)) {
                        html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">혜택</h3>';
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
                    
                    // 리다이렉트 URL
                    if (product.redirect_url) {
                        html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">신청 후 리다이렉트 URL</h3>';
                        html += '<table class="product-info-table">';
                        html += '<tr><th>URL</th><td>' + product.redirect_url + '</td></tr>';
                        html += '</table></div>';
                    }
                    
                    // 등록일
                    html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">기타 정보</h3>';
                    html += '<table class="product-info-table">';
                    html += '<tr><th>등록일</th><td>' + (product.created_at ? new Date(product.created_at).toLocaleString('ko-KR') : '-') + '</td></tr>';
                    html += '</table></div>';
                } else if (productType === 'mvno') {
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
                } else if (productType === 'mno') {
                    // mno는 이미 상세하게 구현되어 있으므로 그대로 유지
                    // (기존 코드는 buildAllDiscountTables 함수를 사용하므로 그대로 두고, 추가 필드만 보완)
                    html += '<div style="text-align: center; padding: 40px; color: #6b7280;">상품 정보를 불러오는 중...</div>';
                } else if (productType === 'internet') {
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
                    html += '<tr><th>월 요금</th><td>' + (product.monthly_fee ? number_format(product.monthly_fee) + '원' : '-') + '</td></tr>';
                    html += '</table></div>';
                    
                    // 현금지급
                    if (product.cash_payment_names && product.cash_payment_prices) {
                        const cashNames = typeof product.cash_payment_names === 'string' ? JSON.parse(product.cash_payment_names) : product.cash_payment_names;
                        const cashPrices = typeof product.cash_payment_prices === 'string' ? JSON.parse(product.cash_payment_prices) : product.cash_payment_prices;
                        if (Array.isArray(cashNames) && cashNames.length > 0) {
                            html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">현금지급</h3>';
                            html += '<table class="product-info-table">';
                            cashNames.forEach((name, index) => {
                                if (name || cashPrices[index]) {
                                    html += '<tr><th>' + (name || '항목 ' + (index + 1)) + '</th><td>' + (cashPrices[index] || '-') + '</td></tr>';
                                }
                            });
                            html += '</table></div>';
                        }
                    }
                    
                    // 상품권 지급
                    if (product.gift_card_names && product.gift_card_prices) {
                        const giftNames = typeof product.gift_card_names === 'string' ? JSON.parse(product.gift_card_names) : product.gift_card_names;
                        const giftPrices = typeof product.gift_card_prices === 'string' ? JSON.parse(product.gift_card_prices) : product.gift_card_prices;
                        if (Array.isArray(giftNames) && giftNames.length > 0) {
                            html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">상품권 지급</h3>';
                            html += '<table class="product-info-table">';
                            giftNames.forEach((name, index) => {
                                if (name || giftPrices[index]) {
                                    html += '<tr><th>' + (name || '항목 ' + (index + 1)) + '</th><td>' + (giftPrices[index] || '-') + '</td></tr>';
                                }
                            });
                            html += '</table></div>';
                        }
                    }
                    
                    // 장비 및 기타 서비스
                    if ((product.equipment_names && product.equipment_names.length > 0) || (product.installation_names && product.installation_names.length > 0)) {
                        html += '<div style="margin-bottom: 32px;"><h3 style="font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">장비 및 기타 서비스</h3>';
                        html += '<table class="product-info-table">';
                        if (product.equipment_names) {
                            const equipNames = typeof product.equipment_names === 'string' ? JSON.parse(product.equipment_names) : product.equipment_names;
                            const equipPrices = typeof product.equipment_prices === 'string' ? JSON.parse(product.equipment_prices) : product.equipment_prices;
                            if (Array.isArray(equipNames) && equipNames.length > 0) {
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
                            if (Array.isArray(installNames) && installNames.length > 0) {
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
<div id="adModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 32px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="margin: 0; font-size: 20px; font-weight: 600;">광고 신청</h2>
            <button type="button" onclick="closeAdModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        
        <form method="POST" id="adForm">
            <input type="hidden" name="action" value="apply_advertisement">
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
                    <?php 
                    $advertisementDaysOptions = [1, 2, 3, 5, 7, 10, 14, 30];
                    foreach ($advertisementDaysOptions as $days): 
                    ?>
                        <option value="<?= $days ?>"><?= $days ?>일</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="modalPricePreview" style="margin-bottom: 24px; padding: 20px; background: #f8fafc; border-radius: 8px; display: none;">
                <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">광고 금액</div>
                <div id="modalPriceAmount"></div>
                <div id="modalBalanceCheck" style="margin-top: 12px; font-size: 14px;"></div>
            </div>
            
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
const currentBalance = <?= isset($balance) ? $balance : 0 ?>;
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
        document.getElementById('modalSubmitBtn').disabled = true;
        document.getElementById('modalSubmitBtn').style.background = '#cbd5e1';
        document.getElementById('modalSubmitBtn').style.color = '#64748b';
        document.getElementById('modalSubmitBtn').style.cursor = 'not-allowed';
    }
}

document.getElementById('modalAdvertisementDays')?.addEventListener('change', updateModalPrice);

// 광고 신청 폼 제출 처리
document.getElementById('adForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const productId = document.getElementById('modalProductId').value;
    const advertisementDays = document.getElementById('modalAdvertisementDays').value;
    
    if (!productId || !advertisementDays) {
        alert('모든 필드를 올바르게 선택해주세요.');
        return;
    }
    
    // 버튼 비활성화
    const submitBtn = document.getElementById('modalSubmitBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = '신청 중...';
    submitBtn.style.background = '#cbd5e1';
    submitBtn.style.cursor = 'not-allowed';
    
    try {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('advertisement_days', advertisementDays);
        
        const response = await fetch('/MVNO/api/advertisement-apply.php', {
            method: 'POST',
            body: formData
        });
        
        // 응답 상태 확인
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // 응답 본문 가져오기
        const responseText = await response.text();
        console.log('API 응답:', responseText);
        
        // JSON 파싱 시도
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON 파싱 오류:', parseError);
            console.error('응답 내용:', responseText);
            throw new Error('서버 응답을 처리할 수 없습니다. 서버 오류가 발생한 것 같습니다.');
        }
        
        if (data.success) {
            // 성공 시 모달 닫고 광고내역 페이지로 이동
            closeAdModal();
            window.location.href = '/MVNO/seller/advertisement/list.php';
        } else {
            // 실패 시 (이미 광고중인 경우 포함) 모달로 메시지 표시
            alert(data.message || '광고 신청에 실패했습니다.');
            
            // 버튼 다시 활성화
            submitBtn.disabled = false;
            submitBtn.textContent = '광고 신청';
            submitBtn.style.background = '#6366f1';
            submitBtn.style.color = '#fff';
            submitBtn.style.cursor = 'pointer';
        }
    } catch (error) {
        console.error('광고 신청 오류:', error);
        console.error('오류 상세:', error.message, error.stack);
        alert('광고 신청 중 오류가 발생했습니다.\n' + (error.message || '알 수 없는 오류가 발생했습니다.'));
        
        // 버튼 다시 활성화
        submitBtn.disabled = false;
        submitBtn.textContent = '광고 신청';
        submitBtn.style.background = '#6366f1';
        submitBtn.style.color = '#fff';
        submitBtn.style.cursor = 'pointer';
    }
});
</script>

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>


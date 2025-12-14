<?php
/**
 * 인터넷 주문 관리 페이지
 * 경로: /seller/orders/internet.php
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

// 필터 파라미터
$status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
$searchKeyword = $_GET['search_keyword'] ?? ''; // 통합검색 (주문번호, 고객명, 전화번호)
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$dateRange = $_GET['date_range'] ?? '7'; // 기본값 7일
$page = max(1, intval($_GET['page'] ?? 1));

// 기간 선택에 따라 날짜 자동 설정 (기본값 7일)
if (empty($dateRange)) {
    $dateRange = '7';
}
if ($dateRange && $dateRange !== 'all') {
    $endDate = date('Y-m-d');
    switch ($dateRange) {
        case '7':
            $dateFrom = date('Y-m-d', strtotime('-7 days'));
            $dateTo = $endDate;
            break;
        case '30':
            $dateFrom = date('Y-m-d', strtotime('-30 days'));
            $dateTo = $endDate;
            break;
        case '365':
            $dateFrom = date('Y-m-d', strtotime('-365 days'));
            $dateTo = $endDate;
            break;
    }
} elseif ($dateRange === 'all') {
    $dateFrom = '';
    $dateTo = '';
}
$perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
if (!in_array($perPage, [10, 20, 50, 100])) {
    $perPage = 10;
}

// DB에서 주문 목록 가져오기
$orders = [];
$totalOrders = 0;
$totalPages = 1;

try {
    $pdo = getDBConnection();
    if ($pdo) {
        $sellerId = (string)$currentUser['user_id'];
        
        // WHERE 조건 구성
        $whereConditions = [
            'a.seller_id = :seller_id',
            "a.product_type = 'internet'"
        ];
        $params = [':seller_id' => $sellerId];
        
        // 진행상황 필터
        if (!empty($status)) {
            $whereConditions[] = 'a.application_status = :status';
            $params[':status'] = $status;
        }
        
        // 통합검색 (주문번호, 고객명, 전화번호)
        if ($searchKeyword && $searchKeyword !== '') {
            $searchConditions = [];
            $searchConditions[] = 'c.name LIKE :search_keyword';
            // 전화번호 검색 (하이픈, 공백 제거 후 검색)
            $cleanPhoneKeyword = preg_replace('/[^0-9]/', '', $searchKeyword); // 숫자만 추출
            if (strlen($cleanPhoneKeyword) >= 3) {
                $searchConditions[] = "REPLACE(REPLACE(REPLACE(c.phone, '-', ''), ' ', ''), '.', '') LIKE :search_keyword_phone";
                $params[':search_keyword_phone'] = '%' . $cleanPhoneKeyword . '%';
            } else {
                // 3자리 미만이면 원본 검색어로도 검색
                $searchConditions[] = 'c.phone LIKE :search_keyword';
            }
            // 주문번호 검색 (created_at 기반: YYYYMMDD-HHMMSS00 형식, 하이픈 없이도 검색 가능)
            $cleanKeyword = preg_replace('/[^0-9]/', '', $searchKeyword); // 숫자만 추출
            if (strlen($cleanKeyword) >= 4) {
                // 날짜 부분 검색 (YYYYMMDD)
                if (strlen($cleanKeyword) >= 8) {
                    $datePart = substr($cleanKeyword, 0, 8);
                    $searchConditions[] = "DATE_FORMAT(a.created_at, '%Y%m%d') LIKE :search_keyword_date";
                    $params[':search_keyword_date'] = '%' . $datePart . '%';
                }
                // 시간 부분 검색 (HHMMSS)
                if (strlen($cleanKeyword) > 8) {
                    $timePart = substr($cleanKeyword, 8);
                    $searchConditions[] = "DATE_FORMAT(a.created_at, '%H%i%s') LIKE :search_keyword_time";
                    $params[':search_keyword_time'] = '%' . $timePart . '%';
                } elseif (strlen($cleanKeyword) < 8) {
                    // 8자리 미만이면 날짜 부분으로 검색
                    $searchConditions[] = "DATE_FORMAT(a.created_at, '%Y%m%d') LIKE :search_keyword_date";
                    $params[':search_keyword_date'] = '%' . $cleanKeyword . '%';
                }
            }
            $params[':search_keyword'] = '%' . $searchKeyword . '%';
            $whereConditions[] = '(' . implode(' OR ', $searchConditions) . ')';
        }
        
        // 날짜 필터
        if ($dateFrom && $dateFrom !== '') {
            $whereConditions[] = 'DATE(a.created_at) >= :date_from';
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo && $dateTo !== '') {
            $whereConditions[] = 'DATE(a.created_at) <= :date_to';
            $params[':date_to'] = $dateTo;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // 전체 개수 조회
        $countSql = "
            SELECT COUNT(*) as total
            FROM product_applications a
            INNER JOIN application_customers c ON a.id = c.application_id
            WHERE $whereClause
        ";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalOrders = $countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        $totalPages = max(1, ceil($totalOrders / $perPage));
        
        // 주문 목록 조회
        $offset = ($page - 1) * $perPage;
        $sql = "
            SELECT 
                a.id,
                a.product_id,
                a.application_status,
                a.created_at,
                c.name,
                c.phone,
                c.email,
                c.additional_info,
                p.id as product_id,
                internet.registration_place,
                internet.speed_option,
                internet.monthly_fee,
                internet.cash_payment_names,
                internet.cash_payment_prices,
                internet.gift_card_names,
                internet.gift_card_prices,
                internet.equipment_names,
                internet.equipment_prices,
                internet.installation_names,
                internet.installation_prices
            FROM product_applications a
            INNER JOIN application_customers c ON a.id = c.application_id
            INNER JOIN products p ON a.product_id = p.id
            LEFT JOIN product_internet_details internet ON p.id = internet.product_id
            WHERE $whereClause
            ORDER BY a.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // additional_info 및 JSON 필드 디코딩
        foreach ($orders as &$order) {
            if (!empty($order['additional_info'])) {
                $order['additional_info'] = json_decode($order['additional_info'], true) ?: [];
            } else {
                $order['additional_info'] = [];
            }
            
            // JSON 필드 디코딩
            $jsonFields = [
                'cash_payment_names', 'cash_payment_prices',
                'gift_card_names', 'gift_card_prices',
                'equipment_names', 'equipment_prices',
                'installation_names', 'installation_prices'
            ];
            
            foreach ($jsonFields as $field) {
                if (!empty($order[$field])) {
                    $order[$field] = json_decode($order[$field], true) ?: [];
                } else {
                    $order[$field] = [];
                }
            }
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching orders: " . $e->getMessage());
}

// 상태별 한글명
$statusLabels = [
    'received' => '접수',
    'activating' => '개통중',
    'on_hold' => '보류',
    'cancelled' => '취소',
    'activation_completed' => '개통완료',
    'installation_completed' => '설치완료',
    // 기존 상태 호환성 유지
    'pending' => '접수',
    'processing' => '개통중',
    'completed' => '설치완료',
    'rejected' => '보류'
];

// 가입형태 한글명
$subscriptionTypeLabels = [
    'new' => '신규가입',
    'port' => '번호이동',
    'change' => '기기변경'
];

$pageStyles = '
    .orders-container {
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .orders-header {
        margin-bottom: 24px;
    }
    
    .orders-header h1 {
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 8px;
    }
    
    .orders-filters {
        background: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }
    
    .filter-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }
    
    .filter-row:last-child {
        margin-bottom: 0;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
    }
    
    .filter-label {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
    }
    
    .filter-input,
    .filter-select {
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .filter-input:focus,
    .filter-select:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .filter-actions {
        display: flex;
        gap: 8px;
        margin-top: 16px;
    }
    
    .btn-filter {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }
    
    .btn-filter-primary {
        background: #10b981;
        color: white;
    }
    
    .btn-filter-primary:hover {
        background: #059669;
    }
    
    .btn-filter-secondary {
        background: #f3f4f6;
        color: #374151;
    }
    
    .btn-filter-secondary:hover {
        background: #e5e7eb;
    }
    
    .orders-table-container {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        overflow-x: auto;
    }
    
    .orders-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .orders-table th {
        background: #f9fafb;
        padding: 12px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .orders-table td {
        padding: 16px 12px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 14px;
        color: #1f2937;
    }
    
    .orders-table tr:hover {
        background: #f9fafb;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }
    
    .status-processing {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .status-completed {
        background: #d1fae5;
        color: #065f46;
    }
    
    .status-cancelled {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .status-rejected {
        background: #f3f4f6;
        color: #374151;
    }
    
    .status-received {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .status-activating {
        background: #fef3c7;
        color: #92400e;
    }
    
    .status-on_hold {
        background: #f3f4f6;
        color: #374151;
    }
    
    .status-activation_completed {
        background: #d1fae5;
        color: #065f46;
    }
    
    .status-installation_completed {
        background: #d1fae5;
        color: #065f46;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        margin-top: 24px;
    }
    
    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 14px;
        color: #374151;
        border: 1px solid #d1d5db;
        background: white;
    }
    
    .pagination a:hover {
        background: #f3f4f6;
        border-color: #10b981;
    }
    
    .pagination .current {
        background: #10b981;
        color: white;
        border-color: #10b981;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }
    
    .empty-state-icon {
        font-size: 48px;
        margin-bottom: 16px;
    }
    
    .empty-state-text {
        font-size: 16px;
        margin-bottom: 8px;
    }
    
    .empty-state-subtext {
        font-size: 14px;
        color: #9ca3af;
    }
    
    .product-name-link {
        color: #10b981;
        cursor: pointer;
        text-decoration: underline;
        font-weight: 500;
    }
    
    .product-name-link:hover {
        color: #059669;
    }
    
    .product-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        overflow: auto;
    }
    
    .product-modal-content {
        background-color: #fff;
        margin: 5% auto;
        padding: 0;
        border-radius: 12px;
        width: 90%;
        max-width: 800px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        max-height: 85vh;
        overflow-y: auto;
    }
    
    .product-modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f9fafb;
        border-radius: 12px 12px 0 0;
    }
    
    .product-modal-header h2 {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
    }
    
    .product-modal-close {
        color: #6b7280;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        line-height: 1;
    }
    
    .product-modal-close:hover {
        color: #1f2937;
    }
    
    .product-modal-body {
        padding: 24px;
    }
    
    .product-info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    
    .product-info-table th {
        background: #f9fafb;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #374151;
        border: 1px solid #e5e7eb;
        width: 30%;
    }
    
    .product-info-table td {
        padding: 12px;
        border: 1px solid #e5e7eb;
        color: #1f2937;
    }
    
    .product-info-table tr:nth-child(even) {
        background: #f9fafb;
    }
';

include __DIR__ . '/../includes/seller-header.php';
?>

<div class="orders-container">
    <div class="orders-header">
        <h1>인터넷 주문 관리</h1>
        <p>인터넷 상품 주문 내역을 확인하고 관리하세요</p>
    </div>
    
    <!-- 필터 -->
    <div class="orders-filters">
        <form method="GET" action="">
            <div class="filter-row">
                <div class="filter-group">
                    <label class="filter-label">기간</label>
                    <select name="date_range" class="filter-select" id="date_range">
                        <option value="7" <?php echo $dateRange === '7' ? 'selected' : ''; ?>>7일</option>
                        <option value="30" <?php echo $dateRange === '30' ? 'selected' : ''; ?>>30일</option>
                        <option value="365" <?php echo $dateRange === '365' ? 'selected' : ''; ?>>1년</option>
                        <option value="all" <?php echo $dateRange === 'all' ? 'selected' : ''; ?>>전체</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">진행상황</label>
                    <select name="status" class="filter-select">
                        <option value="">전체</option>
                        <option value="received" <?php echo (!empty($status) && $status === 'received') ? 'selected' : ''; ?>>접수</option>
                        <option value="activating" <?php echo (!empty($status) && $status === 'activating') ? 'selected' : ''; ?>>개통중</option>
                        <option value="on_hold" <?php echo (!empty($status) && $status === 'on_hold') ? 'selected' : ''; ?>>보류</option>
                        <option value="cancelled" <?php echo (!empty($status) && $status === 'cancelled') ? 'selected' : ''; ?>>취소</option>
                        <option value="activation_completed" <?php echo (!empty($status) && $status === 'activation_completed') ? 'selected' : ''; ?>>개통완료</option>
                        <option value="installation_completed" <?php echo (!empty($status) && $status === 'installation_completed') ? 'selected' : ''; ?>>설치완료</option>
                    </select>
                </div>
                
                <div class="filter-group" style="flex: 2;">
                    <label class="filter-label">통합검색</label>
                    <input type="text" name="search_keyword" class="filter-input" placeholder="주문번호, 고객명, 전화번호 검색" value="<?php echo htmlspecialchars($searchKeyword); ?>">
                </div>
                
                <!-- 날짜 입력 필드는 숨김 처리 (기간 선택 시 자동 설정) -->
                <input type="hidden" name="date_from" id="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                <input type="hidden" name="date_to" id="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                
                <div class="filter-actions" style="display: flex; align-items: flex-end; gap: 8px; margin-top: 0;">
                    <button type="submit" class="btn-filter btn-filter-primary">검색</button>
                    <a href="?" class="btn-filter btn-filter-secondary">초기화</a>
                </div>
                
                <div class="filter-group" style="margin-left: auto; text-align: right;">
                    <label class="filter-label">페이지당 표시</label>
                    <select name="per_page" class="filter-select" style="min-width: 100px;">
                        <option value="10" <?php echo $perPage === 10 ? 'selected' : ''; ?>>10개</option>
                        <option value="20" <?php echo $perPage === 20 ? 'selected' : ''; ?>>20개</option>
                        <option value="50" <?php echo $perPage === 50 ? 'selected' : ''; ?>>50개</option>
                        <option value="100" <?php echo $perPage === 100 ? 'selected' : ''; ?>>100개</option>
                    </select>
                </div>
            </div>
        </form>
    </div>
    
    <!-- 주문 목록 -->
    <div class="orders-table-container">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <div class="empty-state-text">주문 내역이 없습니다</div>
                <div class="empty-state-subtext">고객이 주문하면 여기에 표시됩니다</div>
            </div>
        <?php else: ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>순번</th>
                        <th>주문번호</th>
                        <th>신청 인터넷 회선</th>
                        <th>속도</th>
                        <th>기존 인터넷 회선</th>
                        <th>고객명</th>
                        <th>전화번호</th>
                        <th>이메일</th>
                        <th>진행상황</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $orderIndex = $totalOrders - (($page - 1) * $perPage);
                    foreach ($orders as $order): 
                    ?>
                        <tr>
                            <td><?php echo $orderIndex--; ?></td>
                            <td>
                                <?php 
                                $createdAt = new DateTime($order['created_at']);
                                $datePart = $createdAt->format('Ymd');
                                $timePart = $createdAt->format('His') . '00'; // 시분초 + 00으로 8자리
                                echo $datePart . '-' . $timePart;
                                ?>
                            </td>
                            <td>
                                <span class="product-name-link" onclick="showProductInfo(<?php echo htmlspecialchars(json_encode($order)); ?>, 'internet')">
                                    <?php 
                                    $place = htmlspecialchars($order['registration_place'] ?? '');
                                    echo $place ?: '-';
                                    ?>
                                </span>
                            </td>
                            <td>
                                <span class="product-name-link" onclick="showProductInfo(<?php echo htmlspecialchars(json_encode($order)); ?>, 'internet')">
                                    <?php 
                                    $speed = htmlspecialchars($order['speed_option'] ?? '');
                                    echo $speed ?: '-';
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                // 기존 인터넷 회선 정보 가져오기
                                $existingCompany = $order['additional_info']['currentCompany'] ?? 
                                                   $order['additional_info']['existing_company'] ?? 
                                                   $order['additional_info']['existingCompany'] ?? '';
                                echo $existingCompany ? htmlspecialchars($existingCompany) : '-';
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($order['name']); ?></td>
                            <td><?php echo htmlspecialchars($order['phone']); ?></td>
                            <td><?php echo htmlspecialchars($order['email'] ?? '-'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $order['application_status']; ?>">
                                    <?php echo $statusLabels[$order['application_status']] ?? $order['application_status']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- 페이지네이션 -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">이전</a>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">다음</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateRangeSelect = document.getElementById('date_range');
    const dateFromInput = document.getElementById('date_from');
    const dateToInput = document.getElementById('date_to');
    
    if (dateRangeSelect && dateFromInput && dateToInput) {
        // 기간 선택 변경 시 날짜 자동 업데이트
        dateRangeSelect.addEventListener('change', function() {
            const today = new Date();
            const endDate = today.toISOString().split('T')[0];
            let startDate = '';
            
            switch(this.value) {
                case '7':
                    const date7 = new Date(today);
                    date7.setDate(date7.getDate() - 7);
                    startDate = date7.toISOString().split('T')[0];
                    break;
                case '30':
                    const date30 = new Date(today);
                    date30.setDate(date30.getDate() - 30);
                    startDate = date30.toISOString().split('T')[0];
                    break;
                case '365':
                    const date365 = new Date(today);
                    date365.setDate(date365.getDate() - 365);
                    startDate = date365.toISOString().split('T')[0];
                    break;
                case 'all':
                    startDate = '';
                    endDate = '';
                    break;
            }
            
            dateFromInput.value = startDate;
            dateToInput.value = endDate;
        });
        
        // 날짜 직접 입력 시 기간 선택을 'all'로 변경
        dateFromInput.addEventListener('change', function() {
            if (this.value || dateToInput.value) {
                dateRangeSelect.value = 'all';
            }
        });
        
        dateToInput.addEventListener('change', function() {
            if (this.value || dateFromInput.value) {
                dateRangeSelect.value = 'all';
            }
        });
    }
});

// 상품 정보 모달 표시
function showProductInfo(order, productType) {
    const modal = document.getElementById('productInfoModal');
    const modalBody = document.getElementById('productInfoModalBody');
    
    let html = '';
    
    if (productType === 'internet') {
        // JSON 필드 파싱
        const parseJsonField = (field) => {
            if (!field) return [];
            if (typeof field === 'string') {
                try {
                    return JSON.parse(field);
                } catch (e) {
                    return [];
                }
            }
            return Array.isArray(field) ? field : [];
        };
        
        const cashNames = parseJsonField(order.cash_payment_names);
        const cashPrices = parseJsonField(order.cash_payment_prices);
        const giftNames = parseJsonField(order.gift_card_names);
        const giftPrices = parseJsonField(order.gift_card_prices);
        const equipNames = parseJsonField(order.equipment_names);
        const equipPrices = parseJsonField(order.equipment_prices);
        const installNames = parseJsonField(order.installation_names);
        const installPrices = parseJsonField(order.installation_prices);
        
        html = `
            <table class="product-info-table">
                <tr>
                    <th>인터넷 가입처</th>
                    <td>${order.registration_place || '-'}</td>
                </tr>
                <tr>
                    <th>가입 속도</th>
                    <td>${order.speed_option || '-'}</td>
                </tr>
                <tr>
                    <th>월 요금제</th>
                    <td>${order.monthly_fee ? number_format(order.monthly_fee) + '원' : '-'}</td>
                </tr>
            </table>
        `;
        
        // 현금지급 정보
        if (cashNames.length > 0) {
            html += `<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">현금지급</h3>`;
            html += `<table class="product-info-table">`;
            cashNames.forEach((name, index) => {
                const price = cashPrices[index] || '';
                html += `
                    <tr>
                        <th>${name || '-'}</th>
                        <td>${price ? number_format(price) + '원' : '-'}</td>
                    </tr>
                `;
            });
            html += `</table>`;
        }
        
        // 상품권 지급 정보
        if (giftNames.length > 0) {
            html += `<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">상품권 지급</h3>`;
            html += `<table class="product-info-table">`;
            giftNames.forEach((name, index) => {
                const price = giftPrices[index] || '';
                html += `
                    <tr>
                        <th>${name || '-'}</th>
                        <td>${price ? number_format(price) + '원' : '-'}</td>
                    </tr>
                `;
            });
            html += `</table>`;
        }
        
        // 장비 제공 정보
        if (equipNames.length > 0) {
            html += `<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">장비 제공</h3>`;
            html += `<table class="product-info-table">`;
            equipNames.forEach((name, index) => {
                const price = equipPrices[index] || '';
                html += `
                    <tr>
                        <th>${name || '-'}</th>
                        <td>${price ? number_format(price) + '원' : '-'}</td>
                    </tr>
                `;
            });
            html += `</table>`;
        }
        
        // 설치 및 기타 서비스 정보
        if (installNames.length > 0) {
            html += `<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">설치 및 기타 서비스</h3>`;
            html += `<table class="product-info-table">`;
            installNames.forEach((name, index) => {
                const price = installPrices[index] || '';
                html += `
                    <tr>
                        <th>${name || '-'}</th>
                        <td>${price ? number_format(price) + '원' : '-'}</td>
                    </tr>
                `;
            });
            html += `</table>`;
        }
        
        // 기존 인터넷 회선 정보
        const additionalInfo = order.additional_info || {};
        const existingCompany = additionalInfo.currentCompany || additionalInfo.existing_company || additionalInfo.existingCompany || '';
        if (existingCompany) {
            html += `<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">기존 인터넷 회선</h3>`;
            html += `<table class="product-info-table">`;
            html += `
                <tr>
                    <th>기존 인터넷 회선</th>
                    <td>${existingCompany}</td>
                </tr>
            `;
            html += `</table>`;
        }
    }
    
    modalBody.innerHTML = html;
    modal.style.display = 'block';
}

// 숫자 포맷팅 함수
function number_format(num) {
    if (!num) return '0';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// 모달 닫기
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('productInfoModal');
    const closeBtn = document.querySelector('.product-modal-close');
    
    if (closeBtn) {
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        };
    }
    
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };
});
</script>

<!-- 상품 정보 모달 -->
<div id="productInfoModal" class="product-modal">
    <div class="product-modal-content">
        <div class="product-modal-header">
            <h2>상품 정보</h2>
            <span class="product-modal-close">&times;</span>
        </div>
        <div class="product-modal-body" id="productInfoModalBody">
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>

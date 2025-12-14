<?php
/**
 * 알뜰폰 주문 관리 페이지
 * 경로: /seller/orders/mvno.php
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
$status = $_GET['status'] ?? '';
if ($status === '') {
    $status = null;
}
$searchName = $_GET['search_name'] ?? '';
$searchPhone = $_GET['search_phone'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
if (!in_array($perPage, [10, 20, 50, 100])) {
    $perPage = 20;
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
            "a.product_type = 'mvno'"
        ];
        $params = [':seller_id' => $sellerId];
        
        // 상태 필터
        if ($status && $status !== '') {
            $whereConditions[] = 'a.application_status = :status';
            $params[':status'] = $status;
        }
        
        // 이름 검색
        if ($searchName && $searchName !== '') {
            $whereConditions[] = 'c.name LIKE :search_name';
            $params[':search_name'] = '%' . $searchName . '%';
        }
        
        // 전화번호 검색
        if ($searchPhone && $searchPhone !== '') {
            $whereConditions[] = 'c.phone LIKE :search_phone';
            $params[':search_phone'] = '%' . $searchPhone . '%';
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
                mvno.plan_name,
                mvno.provider
            FROM product_applications a
            INNER JOIN application_customers c ON a.id = c.application_id
            INNER JOIN products p ON a.product_id = p.id
            LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id
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
        
        // additional_info JSON 디코딩
        foreach ($orders as &$order) {
            if (!empty($order['additional_info'])) {
                $order['additional_info'] = json_decode($order['additional_info'], true) ?: [];
            } else {
                $order['additional_info'] = [];
            }
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching orders: " . $e->getMessage());
}

// 상태별 한글명
$statusLabels = [
    'pending' => '대기중',
    'processing' => '처리중',
    'completed' => '완료',
    'cancelled' => '취소',
    'rejected' => '거부'
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
';

include __DIR__ . '/../includes/seller-header.php';
?>

<div class="orders-container">
    <div class="orders-header">
        <h1>알뜰폰 주문 관리</h1>
        <p>알뜰폰 상품 주문 내역을 확인하고 관리하세요</p>
    </div>
    
    <!-- 필터 -->
    <div class="orders-filters">
        <form method="GET" action="">
            <div class="filter-row">
                <div class="filter-group">
                    <label class="filter-label">상태</label>
                    <select name="status" class="filter-select">
                        <option value="">전체</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>대기중</option>
                        <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>처리중</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>완료</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>취소</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>거부</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">고객명</label>
                    <input type="text" name="search_name" class="filter-input" placeholder="고객명 검색" value="<?php echo htmlspecialchars($searchName); ?>">
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">전화번호</label>
                    <input type="text" name="search_phone" class="filter-input" placeholder="전화번호 검색" value="<?php echo htmlspecialchars($searchPhone); ?>">
                </div>
            </div>
            
            <div class="filter-row">
                <div class="filter-group">
                    <label class="filter-label">신청일 (시작)</label>
                    <input type="date" name="date_from" class="filter-input" value="<?php echo htmlspecialchars($dateFrom); ?>">
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">신청일 (종료)</label>
                    <input type="date" name="date_to" class="filter-input" value="<?php echo htmlspecialchars($dateTo); ?>">
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">페이지당 표시</label>
                    <select name="per_page" class="filter-select">
                        <option value="10" <?php echo $perPage === 10 ? 'selected' : ''; ?>>10개</option>
                        <option value="20" <?php echo $perPage === 20 ? 'selected' : ''; ?>>20개</option>
                        <option value="50" <?php echo $perPage === 50 ? 'selected' : ''; ?>>50개</option>
                        <option value="100" <?php echo $perPage === 100 ? 'selected' : ''; ?>>100개</option>
                    </select>
                </div>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn-filter btn-filter-primary">검색</button>
                <a href="?" class="btn-filter btn-filter-secondary">초기화</a>
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
                        <th>상품명</th>
                        <th>가입형태</th>
                        <th>고객명</th>
                        <th>전화번호</th>
                        <th>이메일</th>
                        <th>상태</th>
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
                                <?php 
                                $productName = htmlspecialchars($order['plan_name'] ?? '상품명 없음');
                                $provider = htmlspecialchars($order['provider'] ?? '');
                                echo $provider ? "{$provider} {$productName}" : $productName;
                                ?>
                            </td>
                            <td>
                                <?php 
                                $subType = $order['additional_info']['subscription_type'] ?? '';
                                echo $subType ? ($subscriptionTypeLabels[$subType] ?? $subType) : '-';
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

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>

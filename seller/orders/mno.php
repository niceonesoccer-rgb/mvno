<?php
/**
 * 통신사폰 주문 관리 페이지
 * 경로: /seller/orders/mno.php
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
            "a.product_type = 'mno'"
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
                mno.device_name,
                mno.device_capacity,
                mno.delivery_method,
                mno.visit_region,
                mno.common_provider,
                mno.common_discount_new,
                mno.common_discount_port,
                mno.common_discount_change,
                mno.contract_provider,
                mno.contract_discount_new,
                mno.contract_discount_port,
                mno.contract_discount_change
            FROM product_applications a
            INNER JOIN application_customers c ON a.id = c.application_id
            INNER JOIN products p ON a.product_id = p.id
            LEFT JOIN product_mno_details mno ON p.id = mno.product_id
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
        
        // additional_info JSON 디코딩 및 상품 정보 JSON 디코딩
        foreach ($orders as &$order) {
            if (!empty($order['additional_info'])) {
                $order['additional_info'] = json_decode($order['additional_info'], true) ?: [];
            } else {
                $order['additional_info'] = [];
            }
            
            // 상품 정보 JSON 디코딩
            $jsonFields = [
                'common_provider', 'common_discount_new', 'common_discount_port', 'common_discount_change',
                'contract_provider', 'contract_discount_new', 'contract_discount_port', 'contract_discount_change'
            ];
            foreach ($jsonFields as $field) {
                if (!empty($order[$field])) {
                    $decoded = json_decode($order[$field], true);
                    $order[$field] = is_array($decoded) ? $decoded : [];
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
    'change' => '기기변경',
    '신규가입' => '신규가입',
    '번호이동' => '번호이동',
    '기기변경' => '기기변경'
];

// 할인방법 한글명
$discountTypeLabels = [
    'common' => '공통지원할인',
    'contract' => '선택약정할인',
    '공통지원할인' => '공통지원할인',
    '선택약정할인' => '선택약정할인'
];

/**
 * 주문 정보에서 통신사, 할인방법, 가입형태, 가격 추출
 */
function extractOrderDetails($order) {
    $additionalInfo = $order['additional_info'] ?? [];
    
    // additional_info에서 직접 정보 가져오기
    $carrier = $additionalInfo['carrier'] ?? $additionalInfo['provider'] ?? $additionalInfo['selected_provider'] ?? '';
    $discountType = $additionalInfo['discount_type'] ?? $additionalInfo['discountType'] ?? $additionalInfo['selected_discount_type'] ?? '';
    $subscriptionType = $additionalInfo['subscription_type'] ?? $additionalInfo['subscriptionType'] ?? $additionalInfo['selected_subscription_type'] ?? '';
    $price = $additionalInfo['price'] ?? $additionalInfo['amount'] ?? $additionalInfo['selected_amount'] ?? '';
    
    // additional_info에 정보가 없으면 상품 정보에서 찾기
    if (empty($carrier) || empty($discountType) || empty($subscriptionType) || empty($price)) {
        // subscription_type으로 가입형태 확인
        $subType = $subscriptionType ?: ($additionalInfo['subscription_type'] ?? '');
        if ($subType) {
            // 가입형태 매핑
            $subTypeMap = [
                'new' => 'new_subscription',
                'port' => 'number_port',
                'change' => 'device_change',
                '신규가입' => 'new_subscription',
                '번호이동' => 'number_port',
                '기기변경' => 'device_change'
            ];
            $subTypeKey = $subTypeMap[$subType] ?? '';
            
            // 할인방법 확인
            $isCommon = !empty($order['common_provider']) && is_array($order['common_provider']);
            $isContract = !empty($order['contract_provider']) && is_array($order['contract_provider']);
            
            if ($isCommon && !empty($subTypeKey)) {
                // 공통지원할인에서 찾기
                $discountField = 'common_discount_' . ($subTypeKey === 'new_subscription' ? 'new' : ($subTypeKey === 'number_port' ? 'port' : 'change'));
                $providers = $order['common_provider'] ?? [];
                $discounts = $order[$discountField] ?? [];
                
                if (!empty($providers) && !empty($discounts)) {
                    // 첫 번째 통신사와 할인금액 사용
                    $carrier = $carrier ?: (is_array($providers) ? ($providers[0] ?? '') : $providers);
                    $discountType = $discountType ?: '공통지원할인';
                    $subscriptionType = $subscriptionType ?: $subType;
                    if (is_array($discounts)) {
                        $price = $price ?: ($discounts[0] ?? '');
                    } else {
                        $price = $price ?: $discounts;
                    }
                }
            } elseif ($isContract && !empty($subTypeKey)) {
                // 선택약정할인에서 찾기
                $discountField = 'contract_discount_' . ($subTypeKey === 'new_subscription' ? 'new' : ($subTypeKey === 'number_port' ? 'port' : 'change'));
                $providers = $order['contract_provider'] ?? [];
                $discounts = $order[$discountField] ?? [];
                
                if (!empty($providers) && !empty($discounts)) {
                    // 첫 번째 통신사와 할인금액 사용
                    $carrier = $carrier ?: (is_array($providers) ? ($providers[0] ?? '') : $providers);
                    $discountType = $discountType ?: '선택약정할인';
                    $subscriptionType = $subscriptionType ?: $subType;
                    if (is_array($discounts)) {
                        $price = $price ?: ($discounts[0] ?? '');
                    } else {
                        $price = $price ?: $discounts;
                    }
                }
            }
        }
    }
    
    return [
        'carrier' => $carrier ?: '-',
        'discount_type' => $discountType ?: '-',
        'subscription_type' => $subscriptionType ?: '-',
        'price' => $price !== '' && $price !== null ? (is_numeric($price) ? number_format($price) : $price) : '-'
    ];
}

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
        <h1>통신사폰 주문 관리</h1>
        <p>통신사폰 상품 주문 내역을 확인하고 관리하세요</p>
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
                        <th>단말기 수령방법</th>
                        <th>용량</th>
                        <th>색상</th>
                        <th>통신사</th>
                        <th>할인방법</th>
                        <th>가입형태</th>
                        <th>가격</th>
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
                            <td><?php echo htmlspecialchars($order['device_name'] ?? '상품명 없음'); ?></td>
                            <td>
                                <?php 
                                $deliveryMethod = $order['delivery_method'] ?? '';
                                $visitRegion = $order['visit_region'] ?? '';
                                if ($deliveryMethod === 'delivery') {
                                    echo '택배';
                                } elseif ($deliveryMethod === 'visit') {
                                    echo '내방' . ($visitRegion ? ' (' . htmlspecialchars($visitRegion) . ')' : '');
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($order['device_capacity'] ?? '-'); ?></td>
                            <td>
                                <?php 
                                $selectedColors = $order['additional_info']['device_colors'] ?? [];
                                if (is_array($selectedColors) && !empty($selectedColors)) {
                                    echo htmlspecialchars(implode(', ', $selectedColors));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <?php 
                            $orderDetails = extractOrderDetails($order);
                            ?>
                            <td><?php echo htmlspecialchars($orderDetails['carrier']); ?></td>
                            <td><?php echo htmlspecialchars($orderDetails['discount_type']); ?></td>
                            <td>
                                <?php 
                                $subType = $orderDetails['subscription_type'];
                                echo $subType !== '-' ? ($subscriptionTypeLabels[$subType] ?? $subType) : '-';
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($orderDetails['price']); ?></td>
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

<?php
/**
 * 인터넷 접수건 목록 페이지 (관리자)
 * 경로: /admin/orders/internet-list.php
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/product-functions.php';
require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/plan-data.php';

// 상품 정보 조회 API 처리
if (isset($_GET['action']) && $_GET['action'] === 'get_product_info' && isset($_GET['product_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $pdo = getDBConnection();
    $productId = intval($_GET['product_id']);
    $productInfo = [];
    
    if ($pdo && $productId > 0) {
        try {
            // 기본 상품 정보
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :product_id");
            $stmt->execute([':product_id' => $productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                $productInfo = $product;
                
                // Internet 상세 정보
                if ($product['product_type'] === 'internet') {
                    $detailStmt = $pdo->prepare("SELECT * FROM product_internet_details WHERE product_id = :product_id");
                    $detailStmt->execute([':product_id' => $productId]);
                    $detail = $detailStmt->fetch(PDO::FETCH_ASSOC);
                    if ($detail) {
                        $productInfo['internet_details'] = $detail;
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'product' => $productInfo
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => '상품 정보 조회 중 오류가 발생했습니다: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 상품 ID입니다.'
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// 페이지네이션 파라미터만 처리 (필터 없이 전체 데이터 조회)
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = intval($_GET['per_page'] ?? 20);
if (!in_array($perPage, [10, 20, 50, 100])) {
    $perPage = 20;
}

// 오류 수집 배열
$errors = [];
$warnings = [];

// 데이터베이스 연결
$pdo = getDBConnection();

if (!$pdo) {
    $errors[] = [
        'type' => 'error',
        'message' => '데이터베이스 연결에 실패했습니다. DB 설정을 확인해주세요.',
        'timestamp' => date('Y-m-d H:i:s')
    ];
} else {
    // 테이블 존재 여부 확인
    try {
        $tables = ['product_applications', 'application_customers', 'products', 'product_internet_details'];
        $missingTables = [];
        
        foreach ($tables as $table) {
            $checkStmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if ($checkStmt->rowCount() === 0) {
                $missingTables[] = $table;
            }
        }
        
        if (!empty($missingTables)) {
            $errors[] = [
                'type' => 'error',
                'message' => '필수 테이블이 없습니다: ' . implode(', ', $missingTables) . '. 데이터베이스 스키마를 확인해주세요.',
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    } catch (Exception $e) {
        $warnings[] = [
            'type' => 'warning',
            'message' => '테이블 존재 여부 확인 중 오류가 발생했습니다: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

// 접수건 조회 - 필터 없이 전체 데이터 조회
$applications = [];
$total = 0;
$totalPages = 0;

if ($pdo && empty($errors)) {
    try {
        // 전체 개수 조회
        $countStmt = $pdo->query("
            SELECT COUNT(*) as cnt
            FROM product_applications a
            INNER JOIN application_customers c ON a.id = c.application_id
            WHERE a.product_type = 'internet'
        ");
        
        if ($countStmt === false) {
            throw new Exception('COUNT 쿼리 실행 실패');
        }
        
        $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
        $total = $countResult ? (int)$countResult['cnt'] : 0;
        $totalPages = ceil($total / $perPage);
        
        if ($total === 0) {
            $warnings[] = [
                'type' => 'warning',
                'message' => '데이터베이스에 인터넷 접수건이 없습니다. product_applications 테이블에서 product_type = "internet"인 데이터가 있는지 확인해주세요.',
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        // 접수건 목록 조회
        $offset = ($page - 1) * $perPage;
        $selectStmt = $pdo->prepare("
            SELECT 
                a.id as application_id,
                a.order_number,
                a.product_id,
                a.seller_id,
                a.application_status,
                a.created_at as order_date,
                a.updated_at,
                c.id as customer_id,
                c.user_id as customer_user_id,
                c.name as customer_name,
                c.phone as customer_phone,
                c.email as customer_email,
                c.address,
                c.address_detail,
                c.birth_date,
                c.gender,
                c.additional_info,
                inet.registration_place,
                inet.speed_option,
                inet.monthly_fee,
                p.status as product_status
            FROM product_applications a
            INNER JOIN application_customers c ON a.id = c.application_id
            LEFT JOIN products p ON a.product_id = p.id
            LEFT JOIN product_internet_details inet ON p.id = inet.product_id
            WHERE a.product_type = 'internet'
            ORDER BY a.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $selectStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $selectStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        if (!$selectStmt->execute()) {
            $errorInfo = $selectStmt->errorInfo();
            throw new Exception('SELECT 쿼리 실행 실패: ' . ($errorInfo[2] ?? '알 수 없는 오류'));
        }
        
        $applications = $selectStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 쿼리 결과 확인
        if ($total > 0 && empty($applications)) {
            $warnings[] = [
                'type' => 'warning',
                'message' => "총 {$total}건의 접수건이 있지만 목록을 불러올 수 없습니다. 페이지 번호({$page})나 페이지당 항목 수({$perPage})를 확인해주세요.",
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        // 조인 결과 확인
        $joinIssues = 0;
        foreach ($applications as $app) {
            if (empty($app['customer_name']) && !empty($app['application_id'])) {
                $joinIssues++;
            }
        }
        if ($joinIssues > 0) {
            $warnings[] = [
                'type' => 'warning',
                'message' => "{$joinIssues}건의 접수건에서 고객 정보(application_customers) 조인이 실패했습니다. 데이터 무결성을 확인해주세요.",
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        // 신청 시점의 상품 정보를 우선 사용하도록 처리
        foreach ($applications as &$app) {
            // additional_info 파싱
            $additionalInfo = [];
            if (!empty($app['additional_info'])) {
                $decoded = json_decode($app['additional_info'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $additionalInfo = $decoded;
                }
            }
            
            // 신청 시점의 상품 정보를 우선 사용 (product_snapshot)
            // 사용자가 신청했던 당시의 값이 나중에 변경되어도 유지되어야 함
            $productSnapshot = $additionalInfo['product_snapshot'] ?? null;
            if ($productSnapshot && !empty($productSnapshot)) {
                // product_snapshot이 있으면 신청 시점 정보로 덮어쓰기
                if (isset($productSnapshot['registration_place']) && $productSnapshot['registration_place'] !== '') {
                    $app['registration_place'] = $productSnapshot['registration_place'];
                }
                if (isset($productSnapshot['speed_option']) && $productSnapshot['speed_option'] !== '') {
                    $app['speed_option'] = $productSnapshot['speed_option'];
                }
                if (isset($productSnapshot['monthly_fee']) && $productSnapshot['monthly_fee'] !== '') {
                    $app['monthly_fee'] = $productSnapshot['monthly_fee'];
                }
                if (isset($productSnapshot['service_type']) && $productSnapshot['service_type'] !== '') {
                    $app['service_type'] = $productSnapshot['service_type'];
                }
                // JSON 필드들도 처리
                $jsonFields = ['cash_payment_names', 'cash_payment_prices', 'gift_card_names', 'gift_card_prices',
                              'equipment_names', 'equipment_prices', 'installation_names', 'installation_prices'];
                foreach ($jsonFields as $field) {
                    if (isset($productSnapshot[$field]) && $productSnapshot[$field] !== '') {
                        $app[$field] = $productSnapshot[$field];
                    }
                }
            }
            // product_snapshot이 없으면 현재 테이블 값 사용 (fallback)
        }
        unset($app);
        
        // 판매자 정보 추가 및 전체 판매자 정보 저장
        $sellersData = [];
        foreach ($applications as &$app) {
            $sellerId = $app['seller_id'] ?? null;
            if ($sellerId) {
                $seller = getSellerById($sellerId);
                if ($seller) {
                    $app['seller_user_id'] = $seller['user_id'] ?? $sellerId;
                    $app['seller_name'] = $seller['name'] ?? ($seller['company_name'] ?? '판매자 정보 없음');
                    $app['seller_company_name'] = $seller['company_name'] ?? '';
                    // 전체 판매자 정보 저장 (모달용)
                    if (!isset($sellersData[$sellerId])) {
                        $sellersData[$sellerId] = $seller;
                    }
                } else {
                    $app['seller_user_id'] = $sellerId;
                    $app['seller_name'] = '판매자 정보 없음';
                    $app['seller_company_name'] = '';
                }
            }
        }
        unset($app);
        
        // 데이터 무결성 확인
        $missingDataCount = 0;
        foreach ($applications as $app) {
            if (empty($app['application_id']) || empty($app['customer_name'])) {
                $missingDataCount++;
            }
        }
        if ($missingDataCount > 0) {
            $warnings[] = [
                'type' => 'warning',
                'message' => "{$missingDataCount}건의 접수건에서 필수 데이터(신청ID, 고객명)가 누락되었습니다.",
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    } catch (Exception $e) {
        error_log("접수건 조회 실패: " . $e->getMessage());
        $errors[] = [
            'type' => 'error',
            'message' => '접수건 조회 중 오류가 발생했습니다: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $applications = [];
        $total = 0;
        $totalPages = 0;
    }
}

// 상태 한글 변환
$statusMap = [
    'received' => '접수',
    'pending' => '접수',
    'activating' => '개통중',
    'processing' => '개통중',
    'on_hold' => '보류',
    'rejected' => '보류',
    'cancelled' => '취소',
    'activation_completed' => '개통완료',
    'installation_completed' => '설치완료',
    'completed' => '설치완료'
];
?>

<style>
    .order-list-container {
        max-width: 1800px;
        margin: 0 auto;
        overflow-x: auto;
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
    
    .orders-table {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        overflow-x: auto;
    }
    
    .table-header {
        background: #f9fafb;
        padding: 16px;
        border-bottom: 2px solid #e5e7eb;
        display: grid;
        grid-template-columns: 60px 120px 100px 120px 120px 120px 100px 120px 120px 120px 120px 100px;
        gap: 12px;
        font-weight: 600;
        font-size: 13px;
        color: #374151;
        min-width: 1420px;
    }
    
    .table-row {
        padding: 16px;
        border-bottom: 1px solid #e5e7eb;
        display: grid;
        grid-template-columns: 60px 120px 100px 120px 120px 120px 100px 120px 120px 120px 120px 100px;
        gap: 12px;
        align-items: center;
        transition: background 0.2s;
        min-width: 1420px;
    }
    
    .table-row:hover {
        background: #f9fafb;
    }
    
    .table-row:last-child {
        border-bottom: none;
    }
    
    .table-cell {
        font-size: 13px;
        color: #1f2937;
        word-break: break-word;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-received,
    .status-pending {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .status-activating,
    .status-processing {
        background: #fef3c7;
        color: #92400e;
    }
    
    .status-on_hold,
    .status-rejected {
        background: #f3f4f6;
        color: #374151;
    }
    
    .status-cancelled {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .status-activation_completed {
        background: #d1fae5;
        color: #065f46;
    }
    
    .status-installation_completed,
    .status-completed {
        background: #d1fae5;
        color: #065f46;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        margin-top: 24px;
        flex-wrap: wrap;
    }
    
    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        text-decoration: none;
        color: #374151;
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .pagination a:hover {
        background: #f3f4f6;
        border-color: #9ca3af;
    }
    
    .pagination .current {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }
    
    .empty-state svg {
        width: 64px;
        height: 64px;
        margin-bottom: 16px;
        opacity: 0.5;
    }
    
    .empty-state h3 {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 8px;
    }
    
    .empty-state p {
        font-size: 14px;
    }
    
    .alert-container {
        margin-bottom: 24px;
    }
    
    .alert {
        padding: 16px 20px;
        border-radius: 8px;
        margin-bottom: 12px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        border-left: 4px solid;
    }
    
    .alert-error {
        background: #fef2f2;
        border-left-color: #ef4444;
        color: #991b1b;
    }
    
    .alert-warning {
        background: #fffbeb;
        border-left-color: #f59e0b;
        color: #92400e;
    }
    
    .alert-icon {
        flex-shrink: 0;
        width: 20px;
        height: 20px;
        margin-top: 2px;
    }
    
    .alert-content {
        flex: 1;
    }
    
    .alert-title {
        font-weight: 600;
        margin-bottom: 4px;
        font-size: 14px;
    }
    
    .alert-message {
        font-size: 13px;
        line-height: 1.5;
    }
    
    .alert-timestamp {
        font-size: 11px;
        opacity: 0.7;
        margin-top: 4px;
    }
    
    .clickable-cell {
        color: #3b82f6;
        cursor: pointer;
        text-decoration: underline;
        transition: color 0.2s;
    }
    
    .clickable-cell:hover {
        color: #2563eb;
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
        padding: 20px;
    }
    
    .modal-content {
        background: white;
        border-radius: 12px;
        max-width: 800px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }
    
    .modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h2 {
        font-size: 20px;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
    }
    
    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        color: #6b7280;
        cursor: pointer;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        transition: background 0.2s;
    }
    
    .modal-close:hover {
        background: #f3f4f6;
    }
    
    .modal-body {
        padding: 24px;
    }
    
    .detail-row {
        display: grid;
        grid-template-columns: 150px 1fr;
        gap: 16px;
        padding: 12px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .detail-row:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        font-weight: 600;
        color: #374151;
        font-size: 14px;
    }
    
    .detail-value {
        color: #1f2937;
        font-size: 14px;
        word-break: break-word;
    }
    
    .product-info-text {
        background: #f9fafb;
        padding: 16px;
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        font-size: 13px;
        line-height: 1.6;
        white-space: pre-wrap;
        word-break: break-word;
        max-height: 500px;
        overflow-y: auto;
    }
</style>

<div class="order-list-container">
    <div class="page-header">
        <h1>인터넷 접수건 관리</h1>
        <div style="font-size: 14px; color: #6b7280;">
            총 <strong><?php echo number_format($total); ?></strong>건
        </div>
    </div>
    
    <!-- 에러 및 경고 알림 -->
    <?php if (!empty($errors) || !empty($warnings)): ?>
    <div class="alert-container">
        <?php foreach ($errors as $error): ?>
        <div class="alert alert-error">
            <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <div class="alert-content">
                <div class="alert-title">오류 발생</div>
                <div class="alert-message"><?php echo htmlspecialchars($error['message']); ?></div>
                <?php if (isset($error['timestamp'])): ?>
                <div class="alert-timestamp"><?php echo htmlspecialchars($error['timestamp']); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php foreach ($warnings as $warning): ?>
        <div class="alert alert-warning">
            <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <div class="alert-content">
                <div class="alert-title">주의사항</div>
                <div class="alert-message"><?php echo htmlspecialchars($warning['message']); ?></div>
                <?php if (isset($warning['timestamp'])): ?>
                <div class="alert-timestamp"><?php echo htmlspecialchars($warning['timestamp']); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- 접수건 목록 -->
    <div class="orders-table">
        <div class="table-header">
            <div>순번</div>
            <div>주문번호</div>
            <div>판매자아이디</div>
            <div>판매자명</div>
            <div>가입처</div>
            <div>인터넷속도</div>
            <div>월 요금</div>
            <div>회원아이디</div>
            <div>고객명</div>
            <div>전화번호</div>
            <div>접수일</div>
            <div>진행상황</div>
        </div>
        
        <?php 
        // total이 0보다 크거나 applications가 있으면 표시
        if ($total > 0 || count($applications) > 0): 
            if (count($applications) > 0): 
                $startNum = ($page - 1) * $perPage + 1;
                foreach ($applications as $index => $app): 
                    $rowNum = $startNum + $index;
                    // 접수일 포맷팅
                    $orderDate = $app['order_date'] ?? '';
                    $formattedDate = $orderDate ? date('Y-m-d', strtotime($orderDate)) : '-';
        ?>
            <div class="table-row">
                <div class="table-cell"><?php echo $rowNum; ?></div>
                <div class="table-cell"><?php echo htmlspecialchars($app['order_number'] ?? ($app['application_id'] ?? '-')); ?></div>
                <div class="table-cell">
                    <?php if (!empty($app['seller_user_id']) && $app['seller_user_id'] !== '-'): ?>
                        <span class="clickable-cell" onclick="showSellerModal('<?php echo htmlspecialchars($app['seller_id'] ?? ''); ?>')">
                            <?php echo htmlspecialchars($app['seller_user_id']); ?>
                        </span>
                    <?php else: ?>
                        <?php echo htmlspecialchars($app['seller_user_id'] ?? '-'); ?>
                    <?php endif; ?>
                </div>
                <div class="table-cell">
                    <div><?php echo htmlspecialchars($app['seller_name'] ?? '-'); ?></div>
                    <?php if (!empty($app['seller_company_name'])): ?>
                        <div style="font-size: 11px; color: #6b7280;"><?php echo htmlspecialchars($app['seller_company_name']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="table-cell"><?php echo htmlspecialchars($app['registration_place'] ?? '-'); ?></div>
                <div class="table-cell">
                    <?php if (!empty($app['speed_option']) && $app['speed_option'] !== '-'): ?>
                        <span class="clickable-cell" onclick="showProductModal(<?php echo htmlspecialchars(json_encode($app, JSON_UNESCAPED_UNICODE)); ?>)">
                            <?php echo htmlspecialchars($app['speed_option']); ?>
                        </span>
                    <?php else: ?>
                        <?php echo htmlspecialchars($app['speed_option'] ?? '-'); ?>
                    <?php endif; ?>
                </div>
                <div class="table-cell" style="text-align: right;"><?php echo $app['monthly_fee'] ? number_format($app['monthly_fee']) . '원' : '-'; ?></div>
                <div class="table-cell"><?php echo $app['customer_user_id'] ? htmlspecialchars($app['customer_user_id']) : '-'; ?></div>
                <div class="table-cell">
                    <?php if (!empty($app['customer_name']) && $app['customer_name'] !== '-'): ?>
                        <span class="clickable-cell" onclick="showCustomerModal(<?php echo htmlspecialchars(json_encode($app, JSON_UNESCAPED_UNICODE)); ?>)">
                            <?php echo htmlspecialchars($app['customer_name']); ?>
                        </span>
                    <?php else: ?>
                        <?php echo htmlspecialchars($app['customer_name'] ?? '-'); ?>
                    <?php endif; ?>
                </div>
                <div class="table-cell"><?php echo htmlspecialchars($app['customer_phone'] ?? '-'); ?></div>
                <div class="table-cell"><?php echo htmlspecialchars($formattedDate); ?></div>
                <div class="table-cell">
                    <?php
                    // 상태 정규화
                    $appStatus = strtolower(trim($app['application_status'] ?? ''));
                    if (in_array($appStatus, ['pending', ''])) {
                        $appStatus = 'received';
                    } elseif ($appStatus === 'processing') {
                        $appStatus = 'activating';
                    } elseif ($appStatus === 'rejected') {
                        $appStatus = 'on_hold';
                    } elseif ($appStatus === 'completed') {
                        $appStatus = 'installation_completed';
                    }
                    $statusLabel = $statusMap[$appStatus] ?? $app['application_status'];
                    ?>
                    <span class="status-badge status-<?php echo htmlspecialchars($appStatus); ?>">
                        <?php echo htmlspecialchars($statusLabel); ?>
                    </span>
                </div>
            </div>
        <?php 
                endforeach; 
            else: 
        ?>
            <div style="padding: 40px; text-align: center; color: #6b7280; grid-column: 1 / -1;">
                <div style="margin-bottom: 8px;">데이터를 불러오는 중 오류가 발생했습니다.</div>
                <div style="font-size: 12px;">총 <?php echo number_format($total); ?>건이 있지만 목록을 불러올 수 없습니다.</div>
            </div>
        <?php 
            endif; 
        else: 
        ?>
            <div class="empty-state" style="grid-column: 1 / -1;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
                </svg>
                <h3>접수건이 없습니다</h3>
                <p>등록된 인터넷 접수건이 없습니다.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- 페이지네이션 -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&per_page=<?php echo $perPage; ?>">이전</a>
            <?php endif; ?>
            
            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            
            for ($i = $startPage; $i <= $endPage; $i++):
            ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&per_page=<?php echo $perPage; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&per_page=<?php echo $perPage; ?>">다음</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- 판매자 정보 모달 -->
<div class="modal-overlay" id="sellerModal" onclick="closeModal('sellerModal')">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2>판매자 정보</h2>
            <button class="modal-close" onclick="closeModal('sellerModal')">&times;</button>
        </div>
        <div class="modal-body" id="sellerModalContent">
            <!-- 내용이 여기에 동적으로 추가됩니다 -->
        </div>
    </div>
</div>

<!-- 상품 정보 모달 -->
<div class="modal-overlay" id="productModal" onclick="closeModal('productModal')">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2>상품 정보</h2>
            <button class="modal-close" onclick="closeModal('productModal')">&times;</button>
        </div>
        <div class="modal-body" id="productModalContent">
            <!-- 내용이 여기에 동적으로 추가됩니다 -->
        </div>
    </div>
</div>

<!-- 고객 정보 모달 -->
<div class="modal-overlay" id="customerModal" onclick="closeModal('customerModal')">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2>고객 정보</h2>
            <button class="modal-close" onclick="closeModal('customerModal')">&times;</button>
        </div>
        <div class="modal-body" id="customerModalContent">
            <!-- 내용이 여기에 동적으로 추가됩니다 -->
        </div>
    </div>
</div>

<script>
// 판매자 데이터를 JavaScript에서 사용할 수 있도록 전달
const sellersData = <?php echo json_encode($sellersData ?? [], JSON_UNESCAPED_UNICODE); ?>;

// 모달 닫기 함수
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// HTML 이스케이프 함수
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 판매자 정보 모달 표시
function showSellerModal(sellerId) {
    const seller = sellersData[sellerId];
    if (!seller) {
        alert('판매자 정보를 찾을 수 없습니다.');
        return;
    }
    
    const content = document.getElementById('sellerModalContent');
    let html = '<div class="detail-info">';
    
    // 기본 정보
    html += '<div class="detail-row"><div class="detail-label">아이디</div><div class="detail-value">' + escapeHtml(seller.user_id || '-') + '</div></div>';
    html += '<div class="detail-row"><div class="detail-label">이름</div><div class="detail-value">' + escapeHtml(seller.name || '-') + '</div></div>';
    html += '<div class="detail-row"><div class="detail-label">이메일</div><div class="detail-value">' + escapeHtml(seller.email || '-') + '</div></div>';
    
    if (seller.created_at) {
        html += '<div class="detail-row"><div class="detail-label">가입일</div><div class="detail-value">' + escapeHtml(seller.created_at) + '</div></div>';
    }
    
    // 연락처 정보
    if (seller.phone) {
        html += '<div class="detail-row"><div class="detail-label">전화번호</div><div class="detail-value">' + escapeHtml(seller.phone) + '</div></div>';
    }
    if (seller.mobile) {
        html += '<div class="detail-row"><div class="detail-label">휴대폰</div><div class="detail-value">' + escapeHtml(seller.mobile) + '</div></div>';
    }
    
    // 주소 정보
    if (seller.address) {
        html += '<div class="detail-row"><div class="detail-label">주소</div><div class="detail-value">' + escapeHtml(seller.address);
        if (seller.address_detail) {
            html += ' ' + escapeHtml(seller.address_detail);
        }
        html += '</div></div>';
    }
    
    // 사업자 정보
    if (seller.business_number) {
        html += '<div class="detail-row"><div class="detail-label">사업자등록번호</div><div class="detail-value">' + escapeHtml(seller.business_number) + '</div></div>';
    }
    if (seller.company_name) {
        html += '<div class="detail-row"><div class="detail-label">회사명</div><div class="detail-value">' + escapeHtml(seller.company_name) + '</div></div>';
    }
    if (seller.company_representative) {
        html += '<div class="detail-row"><div class="detail-label">대표자명</div><div class="detail-value">' + escapeHtml(seller.company_representative) + '</div></div>';
    }
    if (seller.business_type) {
        html += '<div class="detail-row"><div class="detail-label">업태</div><div class="detail-value">' + escapeHtml(seller.business_type) + '</div></div>';
    }
    if (seller.business_item) {
        html += '<div class="detail-row"><div class="detail-label">업종</div><div class="detail-value">' + escapeHtml(seller.business_item) + '</div></div>';
    }
    
    html += '</div>';
    content.innerHTML = html;
    document.getElementById('sellerModal').style.display = 'flex';
}

// 숫자 포맷팅 함수
function number_format(num) {
    if (!num && num !== 0) return '0';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// 상품 정보 모달 표시 (신청 시점 정보 포함)
function showProductModal(orderData) {
    const content = document.getElementById('productModalContent');
    document.getElementById('productModal').style.display = 'flex';
    
    // orderData에서 신청 시점 정보 사용 (이미 목록에서 처리됨)
    let html = '<div class="product-info-text">';
    
    // additional_info에서 product_snapshot 확인
    let productSnapshot = {};
    if (orderData.additional_info) {
        try {
            const additionalInfo = typeof orderData.additional_info === 'string' 
                ? JSON.parse(orderData.additional_info) 
                : orderData.additional_info;
            productSnapshot = additionalInfo.product_snapshot || {};
        } catch (e) {
            console.error('Error parsing additional_info:', e);
        }
    }
    
    // 신청 시점 정보가 있으면 사용, 없으면 현재 테이블 값 사용
    const registrationPlace = productSnapshot.registration_place || orderData.registration_place || '-';
    const speedOption = productSnapshot.speed_option || orderData.speed_option || '-';
    let monthlyFee = productSnapshot.monthly_fee || orderData.monthly_fee || '-';
    const serviceType = productSnapshot.service_type || orderData.service_type || '-';
    
    // monthly_fee 포맷팅
    if (monthlyFee !== '-') {
        if (typeof monthlyFee === 'number') {
            monthlyFee = number_format(monthlyFee) + '원';
        } else if (typeof monthlyFee === 'string') {
            // 숫자만 추출하여 포맷팅
            const numericValue = monthlyFee.replace(/[^0-9]/g, '');
            if (numericValue) {
                monthlyFee = number_format(parseInt(numericValue)) + '원';
            }
        }
    }
    
    html += '<div class="detail-info">';
    html += '<div class="detail-row"><div class="detail-label">인터넷 가입처</div><div class="detail-value">' + escapeHtml(registrationPlace) + '</div></div>';
    html += '<div class="detail-row"><div class="detail-label">가입 속도</div><div class="detail-value">' + escapeHtml(speedOption) + '</div></div>';
    html += '<div class="detail-row"><div class="detail-label">월 요금제</div><div class="detail-value">' + escapeHtml(monthlyFee) + '</div></div>';
    html += '<div class="detail-row"><div class="detail-label">결합여부</div><div class="detail-value">' + escapeHtml(serviceType) + '</div></div>';
    html += '</div>';
    
    content.innerHTML = html;
}

// 고객 정보 모달 표시
function showCustomerModal(customerData) {
    const content = document.getElementById('customerModalContent');
    let html = '<div class="detail-info">';
    
    // 기본 정보
    html += '<div class="detail-row"><div class="detail-label">회원아이디</div><div class="detail-value">' + escapeHtml(customerData.customer_user_id || '-') + '</div></div>';
    html += '<div class="detail-row"><div class="detail-label">고객명</div><div class="detail-value">' + escapeHtml(customerData.customer_name || '-') + '</div></div>';
    html += '<div class="detail-row"><div class="detail-label">전화번호</div><div class="detail-value">' + escapeHtml(customerData.customer_phone || '-') + '</div></div>';
    html += '<div class="detail-row"><div class="detail-label">이메일</div><div class="detail-value">' + escapeHtml(customerData.customer_email || '-') + '</div></div>';
    
    // 주소 정보
    if (customerData.address) {
        html += '<div class="detail-row"><div class="detail-label">주소</div><div class="detail-value">' + escapeHtml(customerData.address);
        if (customerData.address_detail) {
            html += ' ' + escapeHtml(customerData.address_detail);
        }
        html += '</div></div>';
    }
    
    // 생년월일
    if (customerData.birth_date) {
        html += '<div class="detail-row"><div class="detail-label">생년월일</div><div class="detail-value">' + escapeHtml(customerData.birth_date) + '</div></div>';
    }
    
    // 성별
    if (customerData.gender) {
        let genderText = customerData.gender;
        if (genderText === 'male') genderText = '남성';
        else if (genderText === 'female') genderText = '여성';
        else if (genderText === 'other') genderText = '기타';
        html += '<div class="detail-row"><div class="detail-label">성별</div><div class="detail-value">' + escapeHtml(genderText) + '</div></div>';
    }
    
    // 추가 정보
    if (customerData.additional_info) {
        try {
            const additionalInfo = JSON.parse(customerData.additional_info);
            html += '<div class="detail-row"><div class="detail-label">추가 정보</div><div class="detail-value"><pre style="background: #f9fafb; padding: 12px; border-radius: 6px; font-size: 12px; white-space: pre-wrap;">' + escapeHtml(JSON.stringify(additionalInfo, null, 2)) + '</pre></div></div>';
        } catch (e) {
            html += '<div class="detail-row"><div class="detail-label">추가 정보</div><div class="detail-value">' + escapeHtml(customerData.additional_info) + '</div></div>';
        }
    }
    
    html += '</div>';
    content.innerHTML = html;
    document.getElementById('customerModal').style.display = 'flex';
}

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal('sellerModal');
        closeModal('productModal');
        closeModal('customerModal');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>








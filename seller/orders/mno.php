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
$status = isset($_GET['status']) && trim($_GET['status']) !== '' ? trim($_GET['status']) : null;
$searchKeyword = trim($_GET['search_keyword'] ?? '');
$dateRange = $_GET['date_range'] ?? '7';
$page = max(1, intval($_GET['page'] ?? 1));
$perPageValue = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
$perPage = in_array($perPageValue, [10, 20, 50, 100]) ? $perPageValue : 10;

// 날짜 설정
$dateFrom = '';
$dateTo = '';
if ($dateRange !== 'all') {
    $days = ['7' => 7, '30' => 30, '365' => 365][$dateRange] ?? 7;
    $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
    $dateTo = date('Y-m-d');
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
        
        // 진행상황 필터
        if (!empty($status)) {
            // 'received' 필터링 시 빈 문자열, null, 'pending'도 포함 (정규화 로직과 일치)
            if ($status === 'received') {
                $whereConditions[] = "(a.application_status = :status OR a.application_status = '' OR a.application_status IS NULL OR LOWER(TRIM(a.application_status)) = 'pending')";
                $params[':status'] = $status;
            } else {
                $whereConditions[] = 'a.application_status = :status';
                $params[':status'] = $status;
            }
        }
        
        // 통합검색
        if ($searchKeyword && $searchKeyword !== '') {
            $searchConditions = [];
            
            // 고객명 검색
            $searchConditions[] = 'c.name LIKE :search_name';
            $params[':search_name'] = '%' . $searchKeyword . '%';
            
            // 전화번호 검색
            $cleanPhone = preg_replace('/[^0-9]/', '', $searchKeyword);
            if (strlen($cleanPhone) >= 3) {
                $searchConditions[] = "REPLACE(REPLACE(REPLACE(c.phone, '-', ''), ' ', ''), '.', '') LIKE :search_phone";
                $params[':search_phone'] = '%' . $cleanPhone . '%';
            } else {
                $searchConditions[] = 'c.phone LIKE :search_phone_fallback';
                $params[':search_phone_fallback'] = '%' . $searchKeyword . '%';
            }
            
            // 주문번호 검색
            $cleanOrder = preg_replace('/[^0-9]/', '', $searchKeyword);
            if (strlen($cleanOrder) >= 2) {
                $searchConditions[] = "REPLACE(a.order_number, '-', '') LIKE :search_order";
                $params[':search_order'] = '%' . $cleanOrder . '%';
                
                if (strlen($cleanOrder) >= 6) {
                    $dateStr = '20' . substr($cleanOrder, 0, 2) . substr($cleanOrder, 2, 2) . substr($cleanOrder, 4, 2);
                    $searchConditions[] = "DATE_FORMAT(a.created_at, '%Y%m%d') LIKE :search_date";
                    $params[':search_date'] = '%' . $dateStr . '%';
                }
            }
            
            if (!empty($searchConditions)) {
                $whereConditions[] = '(' . implode(' OR ', $searchConditions) . ')';
            }
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
        
        // 전체 개수 조회 (중복 방지를 위해 DISTINCT 사용)
        $countSql = "
            SELECT COUNT(DISTINCT a.id) as total
            FROM product_applications a
            INNER JOIN application_customers c ON a.id = c.application_id
            WHERE $whereClause
        ";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalOrders = $countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        $totalPages = $perPage > 0 ? max(1, ceil($totalOrders / $perPage)) : 1;
        
        // 주문 목록 조회 (중복 방지를 위해 DISTINCT 사용)
        $offset = ($page - 1) * $perPage;
        $sql = "
            SELECT DISTINCT
                a.id as application_id,
                a.order_number,
                a.product_id,
                a.application_status,
                a.status_changed_at,
                a.created_at,
                c.name,
                c.phone,
                c.email,
                c.additional_info,
                p.id as product_id,
                mno.device_name,
                mno.device_price,
                mno.device_capacity,
                mno.device_colors,
                mno.delivery_method,
                mno.visit_region,
                mno.service_type,
                mno.contract_period,
                mno.contract_period_value,
                mno.price_main,
                mno.data_amount,
                mno.data_amount_value,
                mno.data_unit,
                mno.data_exhausted,
                mno.call_type,
                mno.call_amount,
                mno.sms_type,
                mno.sms_amount,
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
            ORDER BY a.created_at DESC, a.id DESC
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
        
        // 주문 데이터 정규화
        foreach ($orders as &$order) {
            // 디버깅: 원본 상태 값 저장
            $order['_debug_original_status'] = $order['application_status'] ?? null;
            $orderStatus = strtolower(trim($order['application_status'] ?? ''));
            $order['_debug_normalized_status'] = $orderStatus;
            
            // 정규화 로직 수정: pending과 빈 값만 received로 변환
            if (in_array($orderStatus, ['pending', ''])) {
                $order['application_status'] = 'received';
            } else {
                // 유효한 상태 값이면 그대로 사용, 아니면 원본 유지
                $validStatuses = ['received', 'activating', 'on_hold', 'cancelled', 'activation_completed', 'installation_completed', 'closed', 'processing', 'completed', 'rejected'];
                $order['application_status'] = in_array($orderStatus, $validStatuses) ? $orderStatus : ($order['application_status'] ?? 'received');
            }
            
            $order['_debug_final_status'] = $order['application_status'];
            
            $order['additional_info'] = json_decode($order['additional_info'] ?? '{}', true) ?: [];
            
            $snapshot = $order['additional_info']['product_snapshot'] ?? [];
            if ($snapshot) {
                $exclude = ['id', 'product_id', 'seller_id', 'order_number', 'application_id', 'created_at'];
                foreach ($snapshot as $key => $value) {
                    if (!in_array($key, $exclude) && $value !== null) {
                        $order[$key] = $value;
                    }
                }
            }
            
            $jsonFields = ['common_provider', 'common_discount_new', 'common_discount_port', 'common_discount_change',
                          'contract_provider', 'contract_discount_new', 'contract_discount_port', 'contract_discount_change'];
            foreach ($jsonFields as $field) {
                $order[$field] = is_string($order[$field] ?? null) 
                    ? (json_decode($order[$field], true) ?: []) 
                    : (is_array($order[$field] ?? null) ? $order[$field] : []);
            }
        }
        unset($order);
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
    'pending' => '접수',
    'processing' => '개통중',
    'completed' => '설치완료',
    'rejected' => '보류',
    'closed' => '종료',
    'terminated' => '종료'
];

// 가입형태 표시 함수 사용 (판매자용)
require_once __DIR__ . '/../../includes/data/contract-type-functions.php';

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
    
    // price는 '0'도 유효한 값이므로 isset으로 확인 (empty는 '0'을 false로 판단함)
    $price = null;
    if (isset($additionalInfo['price'])) {
        $price = $additionalInfo['price'];
    } elseif (isset($additionalInfo['amount'])) {
        $price = $additionalInfo['amount'];
    } elseif (isset($additionalInfo['selected_amount'])) {
        $price = $additionalInfo['selected_amount'];
    }
    
    // additional_info에 정보가 없으면 상품 정보에서 찾기
    // price는 '0'도 유효한 값이므로 isset으로 확인
    if (empty($carrier) || empty($discountType) || empty($subscriptionType) || !isset($price)) {
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
                    // price가 설정되지 않은 경우에만 상품 정보에서 가져오기
                    if (!isset($price)) {
                        if (is_array($discounts)) {
                            $price = $discounts[0] ?? '';
                        } else {
                            $price = $discounts;
                        }
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
                    // price가 설정되지 않은 경우에만 상품 정보에서 가져오기
                    if (!isset($price)) {
                        if (is_array($discounts)) {
                            $price = $discounts[0] ?? '';
                        } else {
                            $price = $discounts;
                        }
                    }
                }
            }
        }
    }
    
    // price 표시 처리: '0'도 유효한 값이므로 그대로 표시
    $priceDisplay = '-';
    if ($price !== null && $price !== '') {
        if ($price === '0' || $price === 0) {
            $priceDisplay = '0';
        } elseif (is_numeric($price)) {
            $priceDisplay = number_format($price);
        } else {
            $priceDisplay = $price;
        }
    }
    
    return [
        'carrier' => $carrier ?: '-',
        'discount_type' => $discountType ?: '-',
        'subscription_type' => $subscriptionType ?: '-',
        'price' => $priceDisplay
    ];
}

$pageStyles = '
    .orders-container {
        max-width: 95%;
        margin: 0 auto;
        width: 100%;
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
        white-space: nowrap;
    }
    
    .orders-table td {
        padding: 16px 12px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 14px;
        color: #1f2937;
        white-space: nowrap;
        min-width: fit-content;
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
        white-space: nowrap;
    }
    
    .status-cell-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }
    
    .status-edit-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 6px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        background: white;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.2s;
        line-height: 1;
    }
    
    .status-edit-btn:hover {
        background: #f3f4f6;
        border-color: #10b981;
        color: #10b981;
    }
    
    .status-edit-btn:active {
        transform: scale(0.95);
    }
    
    .status-modal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        align-items: center;
        justify-content: center;
    }
    
    .status-modal-content {
        background-color: white;
        padding: 24px;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        width: 90%;
        max-width: 400px;
        position: relative;
    }
    
    .status-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .status-modal-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: #111827;
    }
    
    .status-modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #6b7280;
        padding: 0;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: all 0.2s;
    }
    
    .status-modal-close:hover {
        background: #f3f4f6;
        color: #111827;
    }
    
    .status-modal-body {
        margin-bottom: 20px;
    }
    
    .status-modal-body label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
    }
    
    .status-modal-select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        background: white;
        color: #374151;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .status-modal-select:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .status-modal-actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }
    
    .status-modal-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .status-modal-btn-cancel {
        background: #f3f4f6;
        color: #374151;
    }
    
    .status-modal-btn-cancel:hover {
        background: #e5e7eb;
    }
    
    .status-modal-btn-save {
        background: #10b981;
        color: white;
    }
    
    .status-modal-btn-save:hover {
        background: #059669;
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
    
    .status-closed {
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
        max-width: 900px;
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
    
    .discount-selection-modal-body {
        margin-top: 32px;
    }
    
    .discount-selection-table-wrapper {
        width: 100%;
        overflow-x: auto;
        margin-top: 16px;
    }
    
    .discount-selection-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
    }
    
    .discount-selection-table thead {
        background: #f9fafb;
    }
    
    .discount-selection-table th {
        padding: 12px 16px;
        text-align: left;
        font-weight: 600;
        color: #374151;
        border: 1px solid #e5e7eb;
        font-size: 14px;
    }
    
    .discount-selection-table td {
        padding: 12px 16px;
        border: 1px solid #e5e7eb;
        color: #1f2937;
        font-size: 14px;
    }
    
    .discount-provider-cell {
        font-weight: 600;
        background: #f9fafb;
        vertical-align: top;
    }
    
    .discount-type-cell {
        font-weight: 500;
        vertical-align: top;
    }
    
    .discount-amount-display {
        display: inline-block;
        padding: 6px 12px;
        background: #6366f1;
        color: white;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        min-width: 60px;
        text-align: center;
    }
';

include __DIR__ . '/../includes/seller-header.php';
?>

<!-- 디버깅 정보 -->
<?php if (!empty($orders) && isset($_GET['debug'])): ?>
<div style="background: #fff3cd; border: 2px solid #ffc107; padding: 15px; margin: 20px; border-radius: 8px; font-family: monospace; font-size: 12px;">
    <h3 style="margin-top: 0; color: #856404;">🔍 디버깅 정보</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #ffeaa7;">
                <th style="padding: 8px; border: 1px solid #ddd;">주문번호</th>
                <th style="padding: 8px; border: 1px solid #ddd;">DB 원본</th>
                <th style="padding: 8px; border: 1px solid #ddd;">정규화 후</th>
                <th style="padding: 8px; border: 1px solid #ddd;">최종 상태</th>
                <th style="padding: 8px; border: 1px solid #ddd;">application_id</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($orders, 0, 5) as $order): ?>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($order['order_number'] ?? '-'); ?></td>
                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars(var_export($order['_debug_original_status'] ?? 'NULL', true)); ?></td>
                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars(var_export($order['_debug_normalized_status'] ?? 'NULL', true)); ?></td>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold; color: #d63031;"><?php echo htmlspecialchars($order['_debug_final_status'] ?? 'NULL'); ?></td>
                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($order['application_id'] ?? 'NULL'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p style="margin: 10px 0 0 0; color: #856404;">
        <strong>참고:</strong> URL에 <code>?debug=1</code>을 추가하면 이 정보가 표시됩니다.
    </p>
</div>
<?php endif; ?>

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
                        <option value="" <?php echo (empty($status) || $status === null) ? 'selected' : ''; ?>>전체</option>
                        <option value="received" <?php echo ($status === 'received') ? 'selected' : ''; ?>>접수</option>
                        <option value="activating" <?php echo ($status === 'activating') ? 'selected' : ''; ?>>개통중</option>
                        <option value="on_hold" <?php echo ($status === 'on_hold') ? 'selected' : ''; ?>>보류</option>
                        <option value="cancelled" <?php echo ($status === 'cancelled') ? 'selected' : ''; ?>>취소</option>
                        <option value="activation_completed" <?php echo ($status === 'activation_completed') ? 'selected' : ''; ?>>개통완료</option>
                        <option value="closed" <?php echo ($status === 'closed') ? 'selected' : ''; ?>>종료</option>
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
                        <th>상태변경시각</th>
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
                            <td><?php echo htmlspecialchars($order['order_number'] ?? '-'); ?></td>
                            <td>
                                <span class="product-name-link" onclick="showProductInfo(<?php echo htmlspecialchars(json_encode($order)); ?>, 'mno')">
                                    <?php echo htmlspecialchars($order['device_name'] ?? '상품명 없음'); ?>
                                </span>
                            </td>
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
                                echo getContractTypeForAdmin($order);
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($orderDetails['price']); ?></td>
                            <td><?php echo htmlspecialchars($order['name']); ?></td>
                            <td><?php echo htmlspecialchars($order['phone']); ?></td>
                            <td><?php echo htmlspecialchars($order['email'] ?? '-'); ?></td>
                            <td>
                                <?php 
                                $statusChangedAt = $order['status_changed_at'] ?? null;
                                if ($statusChangedAt) {
                                    echo date('Y-m-d H:i', strtotime($statusChangedAt));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="status-cell-wrapper">
                                    <span class="status-badge status-<?php echo $order['application_status']; ?>">
                                        <?php echo $statusLabels[$order['application_status']] ?? $order['application_status']; ?>
                                    </span>
                                    <?php 
                                    $appId = $order['application_id'] ?? $order['id'] ?? null;
                                    $currentStatus = htmlspecialchars($order['application_status'] ?? 'received', ENT_QUOTES);
                                    if (!$appId) {
                                        error_log("Missing application_id for order: " . json_encode($order));
                                    }
                                    ?>
                                    <button type="button" class="status-edit-btn" onclick="openStatusEditModal(<?php echo $appId; ?>, '<?php echo $currentStatus; ?>')" title="상태 변경" data-app-id="<?php echo $appId; ?>" data-status="<?php echo $currentStatus; ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </button>
                                </div>
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
        const updateDates = () => {
            const days = {7: 7, 30: 30, 365: 365}[dateRangeSelect.value];
            if (days) {
                const date = new Date();
                date.setDate(date.getDate() - days);
                dateFromInput.value = date.toISOString().split('T')[0];
                dateToInput.value = new Date().toISOString().split('T')[0];
            } else {
                dateFromInput.value = dateToInput.value = '';
            }
        };
        dateRangeSelect.addEventListener('change', updateDates);
    }
});

// 상품 정보 모달 표시
function showProductInfo(order, productType) {
    const modal = document.getElementById('productInfoModal');
    const modalBody = document.getElementById('productInfoModalBody');
    
    let html = '';
    
    if (productType === 'mno') {
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
        
        const deviceColors = parseJsonField(order.device_colors);
        const commonProvider = parseJsonField(order.common_provider);
        const commonDiscountNew = parseJsonField(order.common_discount_new);
        const commonDiscountPort = parseJsonField(order.common_discount_port);
        const commonDiscountChange = parseJsonField(order.common_discount_change);
        const contractProvider = parseJsonField(order.contract_provider);
        const contractDiscountNew = parseJsonField(order.contract_discount_new);
        const contractDiscountPort = parseJsonField(order.contract_discount_port);
        const contractDiscountChange = parseJsonField(order.contract_discount_change);
        
        // 주문 시 선택한 정보 가져오기
        const additionalInfo = order.additional_info || {};
        const subscriptionType = additionalInfo.subscription_type || '';
        const selectedCarrier = additionalInfo.carrier || additionalInfo.provider || '';
        const selectedDiscountType = additionalInfo.discount_type || '';
        const selectedPrice = additionalInfo.price || '';
        const selectedColors = additionalInfo.device_colors || [];
        
        // 주문 정보 섹션 (기본 정보와 주문 선택 정보 통합)
        html = `<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">주문 정보</h3>`;
        html += `<table class="product-info-table">`;
        
        // 단말기 정보 (상단에 표시)
        html += `<tr><th>단말기명</th><td>${order.device_name || '-'}</td></tr>`;
        html += `<tr><th>단말기 출고가</th><td>${order.device_price ? number_format(Math.round(parseFloat(order.device_price))) + '원' : '-'}</td></tr>`;
        html += `<tr><th>용량</th><td>${order.device_capacity || '-'}</td></tr>`;
        if (selectedColors.length > 0) {
            html += `<tr><th>선택한 색상</th><td>${selectedColors.join(', ')}</td></tr>`;
        }
        
        // 주문 시 선택한 정보 (요청된 순서대로)
        if (selectedCarrier) {
            html += `<tr><th>통신사</th><td>${selectedCarrier}</td></tr>`;
        }
        if (subscriptionType) {
            // 판매자용 표시: 신규, 번이, 기변
            const subTypeLabels = {
                'new': '신규',
                'mnp': '번이',
                'port': '번이', // 하위 호환성
                'change': '기변'
            };
            html += `<tr><th>가입형태</th><td>${subTypeLabels[subscriptionType] || subscriptionType}</td></tr>`;
        }
        if (selectedDiscountType) {
            html += `<tr><th>할인방법</th><td>${selectedDiscountType}</td></tr>`;
        }
        if (selectedPrice) {
            html += `<tr><th>가격</th><td>${selectedPrice}</td></tr>`;
        }
        html += `<tr><th>단말기 수령방법</th><td>${order.delivery_method === 'delivery' ? '택배' : order.delivery_method === 'visit' ? '내방' + (order.visit_region ? ' (' + order.visit_region + ')' : '') : '-'}</td></tr>`;
        
        html += `</table>`;
        
        // 할인 정보 테이블 (판매자 확인용)
        const discountTable = buildDiscountTableForOrder(order);
        if (discountTable) {
            html += discountTable;
        }
    }
    
    modalBody.innerHTML = html;
    modal.style.display = 'block';
}

// 할인 정보 테이블 생성 함수 (버튼 없이 정보만 표시)
function buildDiscountTableForOrder(order) {
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
    
    // 숫자 비교를 위한 헬퍼 함수
    function isNot9999(value) {
        if (value === undefined || value === null) return false;
        const numValue = parseFloat(value);
        return !isNaN(numValue) && numValue !== 9999;
    }
    
    const allDiscountOptions = [];
    
    // 공통지원할인 데이터 수집
    const commonProviders = parseJsonField(order.common_provider);
    const commonNewDiscounts = parseJsonField(order.common_discount_new);
    const commonPortDiscounts = parseJsonField(order.common_discount_port);
    const commonChangeDiscounts = parseJsonField(order.common_discount_change);
    
    for (let i = 0; i < commonProviders.length; i++) {
        const provider = commonProviders[i] || '-';
        
        if (isNot9999(commonPortDiscounts[i])) {
            allDiscountOptions.push({ provider, discountType: '공통지원할인', subscriptionType: '번호이동', amount: commonPortDiscounts[i] });
        }
        if (isNot9999(commonChangeDiscounts[i])) {
            allDiscountOptions.push({ provider, discountType: '공통지원할인', subscriptionType: '기기변경', amount: commonChangeDiscounts[i] });
        }
        if (isNot9999(commonNewDiscounts[i])) {
            allDiscountOptions.push({ provider, discountType: '공통지원할인', subscriptionType: '신규가입', amount: commonNewDiscounts[i] });
        }
    }
    
    // 선택약정할인 데이터 수집
    const contractProviders = parseJsonField(order.contract_provider);
    const contractNewDiscounts = parseJsonField(order.contract_discount_new);
    const contractPortDiscounts = parseJsonField(order.contract_discount_port);
    const contractChangeDiscounts = parseJsonField(order.contract_discount_change);
    
    for (let i = 0; i < contractProviders.length; i++) {
        const provider = contractProviders[i] || '-';
        
        if (isNot9999(contractPortDiscounts[i])) {
            allDiscountOptions.push({ provider, discountType: '선택약정할인', subscriptionType: '번호이동', amount: contractPortDiscounts[i] });
        }
        if (isNot9999(contractChangeDiscounts[i])) {
            allDiscountOptions.push({ provider, discountType: '선택약정할인', subscriptionType: '기기변경', amount: contractChangeDiscounts[i] });
        }
        if (isNot9999(contractNewDiscounts[i])) {
            allDiscountOptions.push({ provider, discountType: '선택약정할인', subscriptionType: '신규가입', amount: contractNewDiscounts[i] });
        }
    }
    
    if (allDiscountOptions.length === 0) {
        return null;
    }
    
    // 통신사별, 할인종류별로 그룹화
    const groupedByProviderAndDiscount = {};
    allDiscountOptions.forEach(option => {
        const key = `${option.provider}_${option.discountType}`;
        if (!groupedByProviderAndDiscount[key]) {
            groupedByProviderAndDiscount[key] = {
                provider: option.provider,
                discountType: option.discountType,
                options: []
            };
        }
        groupedByProviderAndDiscount[key].options.push(option);
    });
    
    // 통신사별로 다시 그룹화
    const finalGrouped = {};
    Object.keys(groupedByProviderAndDiscount).forEach(key => {
        const item = groupedByProviderAndDiscount[key];
        if (!finalGrouped[item.provider]) {
            finalGrouped[item.provider] = [];
        }
        finalGrouped[item.provider].push(item);
    });
    
    // 테이블 HTML 생성
    let html = '<div class="discount-selection-modal-body" style="margin-top: 32px;">';
    html += '<div class="discount-selection-table-wrapper">';
    html += '<table class="discount-selection-table">';
    html += '<thead><tr><th>통신사</th><th>할인종류</th><th>가입유형</th><th>가격</th></tr></thead>';
    html += '<tbody>';
    
    Object.keys(finalGrouped).forEach(provider => {
        const providerGroups = finalGrouped[provider];
        let providerRowSpan = 0;
        
        // 통신사별 총 행 개수 계산
        providerGroups.forEach(group => {
            providerRowSpan += group.options.length;
        });
        
        providerGroups.forEach((group, groupIndex) => {
            group.options.forEach((option, optionIndex) => {
                html += '<tr>';
                
                // 통신사 셀 (첫 번째 그룹의 첫 번째 옵션에만 표시)
                if (groupIndex === 0 && optionIndex === 0) {
                    html += `<td rowspan="${providerRowSpan}" class="discount-provider-cell">${provider}</td>`;
                }
                
                // 할인종류 셀 (각 그룹의 첫 번째 옵션에만 표시)
                if (optionIndex === 0) {
                    html += `<td rowspan="${group.options.length}" class="discount-type-cell">${group.discountType}</td>`;
                }
                
                // 가입유형
                html += `<td>${option.subscriptionType}</td>`;
                
                // 가격 (버튼 없이 박스 스타일로 표시)
                const amount = parseFloat(option.amount);
                let formattedAmount;
                if (amount % 1 === 0) {
                    formattedAmount = amount < 0 
                        ? `-${Math.abs(amount).toLocaleString('ko-KR')}`
                        : `${amount.toLocaleString('ko-KR')}`;
                } else {
                    formattedAmount = amount < 0 
                        ? `-${Math.abs(amount).toLocaleString('ko-KR', { minimumFractionDigits: 1, maximumFractionDigits: 2 })}`
                        : `${amount.toLocaleString('ko-KR', { minimumFractionDigits: 1, maximumFractionDigits: 2 })}`;
                }
                
                html += `<td><span class="discount-amount-display">${formattedAmount}</span></td>`;
                html += '</tr>';
            });
        });
    });
    
    html += '</tbody></table></div></div>';
    return html;
}

// 숫자 포맷팅 함수
function number_format(num) {
    if (!num) return '0';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// 상태 변경 모달 열기
function openStatusEditModal(applicationId, currentStatus) {
    console.log('openStatusEditModal called:', { applicationId, currentStatus });
    
    const modal = document.getElementById('statusEditModal');
    const select = document.getElementById('statusEditSelect');
    
    if (!modal || !select) {
        console.error('Modal or select element not found:', { modal: !!modal, select: !!select });
        return;
    }
    
    // 현재 상태 정규화 및 기본값 설정
    let status = 'received'; // 기본값
    if (currentStatus) {
        const normalizedStatus = String(currentStatus).trim().toLowerCase();
        if (normalizedStatus !== '') {
            // 'pending' 값도 'received'로 매핑
            status = (normalizedStatus === 'pending') ? 'received' : normalizedStatus;
        }
    }
    
    // 셀렉트박스에 값 설정 (값이 유효한 옵션인지 확인)
    const validStatuses = ['received', 'activating', 'on_hold', 'cancelled', 'activation_completed', 'closed'];
    if (validStatuses.includes(status)) {
        select.value = status;
    } else {
        // 유효하지 않은 값이면 기본값 'received' 사용
        select.value = 'received';
    }
    
    select.setAttribute('data-application-id', applicationId);
    console.log('Modal opened with:', { applicationId, status, selectValue: select.value });
    
    // 모달 표시
    modal.style.display = 'flex';
}

// 상태 변경 모달 닫기
function closeStatusEditModal() {
    const modal = document.getElementById('statusEditModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// 주문 상태 변경 함수
function updateOrderStatus() {
    const select = document.getElementById('statusEditSelect');
    if (!select) {
        console.error('statusEditSelect element not found');
        return;
    }
    
    const applicationId = select.getAttribute('data-application-id');
    const newStatus = select.value;
    
    if (!applicationId || !newStatus) {
        console.error('Missing applicationId or newStatus:', { applicationId, newStatus });
        alert('필수 정보가 누락되었습니다.');
        return;
    }
    
    console.log('Updating order status:', { applicationId, newStatus });
    
    // 상태 레이블 매핑
    const statusLabels = {
        'received': '접수',
        'activating': '개통중',
        'on_hold': '보류',
        'cancelled': '취소',
        'activation_completed': '개통완료',
        'installation_completed': '설치완료',
        'closed': '종료',
        'terminated': '종료'
    };
    
    const statusLabel = statusLabels[newStatus] || newStatus;
    
    // API 호출
    const requestBody = `application_id=${applicationId}&status=${encodeURIComponent(newStatus)}`;
    console.log('API Request:', {
        url: '/MVNO/api/update-order-status.php',
        method: 'POST',
        body: requestBody
    });
    
    fetch('/MVNO/api/update-order-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: requestBody
    })
    .then(response => {
        console.log('API Response status:', response.status, response.statusText);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        console.log('API Response text:', text);
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e, 'Response text:', text);
            throw new Error('서버 응답을 파싱할 수 없습니다: ' + text.substring(0, 100));
        }
        console.log('API Response data:', data);
        
        if (data.success) {
            closeStatusEditModal();
            if (typeof showAlert === 'function') {
                showAlert('상태가 변경되었습니다.', '완료');
            } else {
                alert('상태가 변경되었습니다.');
            }
            // 페이지 새로고침
            setTimeout(() => {
                location.reload();
            }, 500);
        } else {
            const errorMsg = data.message || '상태 변경에 실패했습니다.';
            console.error('API Error:', data);
            if (typeof showAlert === 'function') {
                showAlert(errorMsg + (data.debug ? '\n디버그: ' + JSON.stringify(data.debug) : ''), '오류', true);
            } else {
                alert(errorMsg + (data.debug ? '\n디버그: ' + JSON.stringify(data.debug) : ''));
            }
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        if (typeof showAlert === 'function') {
            showAlert('상태 변경 중 오류가 발생했습니다: ' + error.message, '오류', true);
        } else {
            alert('상태 변경 중 오류가 발생했습니다: ' + error.message);
        }
    });
}

// 모달 닫기 이벤트
document.addEventListener('DOMContentLoaded', function() {
    const statusModal = document.getElementById('statusEditModal');
    const statusModalClose = document.querySelector('.status-modal-close');
    
    if (statusModalClose) {
        statusModalClose.addEventListener('click', closeStatusEditModal);
    }
    
    if (statusModal) {
        statusModal.addEventListener('click', function(event) {
            if (event.target === statusModal) {
                closeStatusEditModal();
            }
        });
    }
});

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

<!-- 상태 변경 모달 -->
<div id="statusEditModal" class="status-modal">
    <div class="status-modal-content">
        <div class="status-modal-header">
            <h3>진행상황 변경</h3>
            <button type="button" class="status-modal-close">&times;</button>
        </div>
        <div class="status-modal-body">
            <label for="statusEditSelect">진행상황 선택</label>
            <select id="statusEditSelect" class="status-modal-select">
                <option value="received" selected>접수</option>
                <option value="activating">개통중</option>
                <option value="on_hold">보류</option>
                <option value="cancelled">취소</option>
                <option value="activation_completed">개통완료</option>
                <option value="closed">종료</option>
            </select>
        </div>
        <div class="status-modal-actions">
            <button type="button" class="status-modal-btn status-modal-btn-cancel" onclick="closeStatusEditModal()">취소</button>
            <button type="button" class="status-modal-btn status-modal-btn-save" onclick="updateOrderStatus()">변경</button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>


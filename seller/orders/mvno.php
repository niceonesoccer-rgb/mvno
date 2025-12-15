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
            "a.product_type = 'mvno'"
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
            $searchConditions[] = '(SELECT c.name FROM application_customers c WHERE c.application_id = a.id LIMIT 1) LIKE :search_keyword';
            // 전화번호 검색 (하이픈, 공백 제거 후 검색)
            $cleanPhoneKeyword = preg_replace('/[^0-9]/', '', $searchKeyword); // 숫자만 추출
            if (strlen($cleanPhoneKeyword) >= 3) {
                $searchConditions[] = "REPLACE(REPLACE(REPLACE((SELECT c.phone FROM application_customers c WHERE c.application_id = a.id LIMIT 1), '-', ''), ' ', ''), '.', '') LIKE :search_keyword_phone";
                $params[':search_keyword_phone'] = '%' . $cleanPhoneKeyword . '%';
            } else {
                // 3자리 미만이면 원본 검색어로도 검색
                $searchConditions[] = '(SELECT c.phone FROM application_customers c WHERE c.application_id = a.id LIMIT 1) LIKE :search_keyword';
            }
            // 주문번호 검색 (created_at 기반: YYMMDDHH-MMXXXXXX 형식, 하이픈 없이도 검색 가능)
            $cleanKeyword = preg_replace('/[^0-9]/', '', $searchKeyword); // 숫자만 추출
            if (strlen($cleanKeyword) >= 2) {
                // 앞 8자리 검색 (YYMMDDHH: 년월일시간)
                if (strlen($cleanKeyword) >= 8) {
                    $dateTimePart = substr($cleanKeyword, 0, 8);
                    // YYMMDDHH 형식으로 변환하여 검색 (예: 25121518 -> 2025-12-15 18:xx:xx)
                    $year = '20' . substr($dateTimePart, 0, 2);
                    $month = substr($dateTimePart, 2, 2);
                    $day = substr($dateTimePart, 4, 2);
                    $hour = substr($dateTimePart, 6, 2);
                    $searchConditions[] = "DATE_FORMAT(a.created_at, '%Y%m%d%H') LIKE :search_keyword_datetime";
                    $params[':search_keyword_datetime'] = '%' . $year . $month . $day . $hour . '%';
                }
                // 뒤 8자리 검색 (MMXXXXXX: 분 + 주문ID)
                if (strlen($cleanKeyword) > 8) {
                    $minutePart = substr($cleanKeyword, 8, 2);
                    $orderIdPart = substr($cleanKeyword, 10);
                    $searchConditions[] = "DATE_FORMAT(a.created_at, '%i') LIKE :search_keyword_minute";
                    $params[':search_keyword_minute'] = '%' . $minutePart . '%';
                    if (strlen($orderIdPart) > 0) {
                        $searchConditions[] = "CAST(a.id AS CHAR) LIKE :search_keyword_orderid";
                        $params[':search_keyword_orderid'] = '%' . $orderIdPart . '%';
                    }
                } elseif (strlen($cleanKeyword) < 8) {
                    // 8자리 미만이면 날짜/시간 부분으로 검색
                    if (strlen($cleanKeyword) >= 6) {
                        // YYMMDD 형식
                        $year = '20' . substr($cleanKeyword, 0, 2);
                        $month = substr($cleanKeyword, 2, 2);
                        $day = substr($cleanKeyword, 4, 2);
                        $searchConditions[] = "DATE_FORMAT(a.created_at, '%Y%m%d') LIKE :search_keyword_date";
                        $params[':search_keyword_date'] = '%' . $year . $month . $day . '%';
                    } else {
                        // YYMM 또는 YY 형식
                        $searchConditions[] = "DATE_FORMAT(a.created_at, '%y%m') LIKE :search_keyword_ym";
                        $params[':search_keyword_ym'] = '%' . $cleanKeyword . '%';
                    }
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
        
        // 전체 개수 조회 (중복 방지를 위해 DISTINCT 사용)
        $countSql = "
            SELECT COUNT(DISTINCT a.id) as total
            FROM product_applications a
            WHERE EXISTS (
                SELECT 1 FROM application_customers c WHERE c.application_id = a.id
            )
            AND $whereClause
        ";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalOrders = $countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        $totalPages = max(1, ceil($totalOrders / $perPage));
        
        // 주문 목록 조회 (중복 방지를 위해 서브쿼리 사용)
        $offset = ($page - 1) * $perPage;
        $sql = "
            SELECT 
                a.id as application_id,
                a.product_id,
                a.application_status,
                a.created_at,
                (SELECT c.name FROM application_customers c WHERE c.application_id = a.id LIMIT 1) as name,
                (SELECT c.phone FROM application_customers c WHERE c.application_id = a.id LIMIT 1) as phone,
                (SELECT c.email FROM application_customers c WHERE c.application_id = a.id LIMIT 1) as email,
                (SELECT c.additional_info FROM application_customers c WHERE c.application_id = a.id LIMIT 1) as additional_info,
                p.id as product_id,
                mvno.plan_name,
                mvno.provider,
                mvno.service_type,
                mvno.contract_period,
                mvno.contract_period_days,
                mvno.discount_period,
                mvno.price_main,
                mvno.price_after,
                mvno.data_amount,
                mvno.data_amount_value,
                mvno.data_unit,
                mvno.data_additional,
                mvno.data_additional_value,
                mvno.data_exhausted,
                mvno.data_exhausted_value,
                mvno.call_type,
                mvno.call_amount,
                mvno.additional_call_type,
                mvno.additional_call,
                mvno.sms_type,
                mvno.sms_amount,
                mvno.mobile_hotspot,
                mvno.mobile_hotspot_value,
                mvno.regular_sim_available,
                mvno.regular_sim_price,
                mvno.nfc_sim_available,
                mvno.nfc_sim_price,
                mvno.esim_available,
                mvno.esim_price,
                mvno.over_data_price,
                mvno.over_voice_price,
                mvno.over_video_price,
                mvno.over_sms_price,
                mvno.over_lms_price,
                mvno.over_mms_price,
                mvno.promotion_title,
                mvno.promotions,
                mvno.benefits
            FROM product_applications a
            INNER JOIN products p ON a.product_id = p.id
            LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id
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
        
        // additional_info 및 JSON 필드 디코딩
        foreach ($orders as &$order) {
            if (!empty($order['additional_info'])) {
                $order['additional_info'] = json_decode($order['additional_info'], true) ?: [];
            } else {
                $order['additional_info'] = [];
            }
            
            // product_snapshot에서 상품 정보 가져오기 (신청 당시 정보)
            $productSnapshot = $order['additional_info']['product_snapshot'] ?? [];
            if (!empty($productSnapshot) && is_array($productSnapshot)) {
                // product_snapshot의 모든 정보로 현재 상품 정보 덮어쓰기 (신청 당시 정보 유지)
                // 단, id, product_id, seller_id 등은 제외
                $excludeKeys = ['id', 'product_id', 'seller_id'];
                foreach ($productSnapshot as $key => $value) {
                    if (!in_array($key, $excludeKeys) && $value !== null) {
                        $order[$key] = $value;
                    }
                }
            }
            
            // JSON 필드 디코딩
            $jsonFields = ['promotions', 'benefits'];
            foreach ($jsonFields as $field) {
                if (!empty($order[$field])) {
                    // 문자열인 경우에만 디코딩
                    if (is_string($order[$field])) {
                        $order[$field] = json_decode($order[$field], true) ?: [];
                    } elseif (!is_array($order[$field])) {
                        $order[$field] = [];
                    }
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
    
    .product-info-section {
        margin-bottom: 24px;
    }
    
    .product-info-section h3 {
        margin: 0 0 12px 0;
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        padding-bottom: 8px;
        border-bottom: 2px solid #10b981;
    }
    
    .product-info-table th {
        white-space: nowrap;
        vertical-align: top;
    }
    
    .product-info-table td {
        word-break: break-word;
    }
    
    /* 모바일 반응형 */
    @media (max-width: 768px) {
        .product-modal-content {
            width: 95%;
            margin: 2% auto;
            max-height: 95vh;
        }
        
        .product-modal-header {
            padding: 16px;
        }
        
        .product-modal-header h2 {
            font-size: 18px;
        }
        
        .product-modal-body {
            padding: 16px;
        }
        
        .product-info-table {
            font-size: 14px;
        }
        
        .product-info-table th,
        .product-info-table td {
            padding: 8px;
        }
        
        .product-info-table th {
            width: 35%;
            font-size: 13px;
        }
        
        .product-info-section h3 {
            font-size: 16px;
        }
        
        .product-info-table td {
            font-size: 13px;
        }
    }
    
    @media (max-width: 480px) {
        .product-modal-content {
            width: 100%;
            margin: 0;
            border-radius: 0;
            max-height: 100vh;
        }
        
        .product-modal-header {
            padding: 12px;
            border-radius: 0;
        }
        
        .product-modal-body {
            padding: 12px;
        }
        
        .product-info-table {
            font-size: 12px;
            display: block;
            overflow-x: auto;
        }
        
        .product-info-table th,
        .product-info-table td {
            padding: 6px;
            font-size: 12px;
        }
        
        .product-info-table th {
            width: 40%;
            min-width: 100px;
        }
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
                        <th>통신사</th>
                        <th>상품명</th>
                        <th>가입형태</th>
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
                                // 주문 ID 확인 (application_id 우선 사용)
                                $orderId = isset($order['application_id']) ? intval($order['application_id']) : (isset($order['id']) ? intval($order['id']) : 0);
                                
                                if ($orderId <= 0) {
                                    // 디버깅: 주문 ID가 없는 경우
                                    error_log("MVNO Order - Invalid order ID. Available keys: " . implode(', ', array_keys($order)));
                                    $orderId = 0;
                                }
                                
                                $createdAt = new DateTime($order['created_at']);
                                // 앞 8자리: YY(년 2자리) + MM(월 2자리) + DD(일 2자리) + HH(시간 2자리)
                                $dateTimePart = $createdAt->format('ymdH'); // 년(2자리)월일시간
                                // 뒤 8자리: MM(분 2자리) + 주문ID(6자리)
                                $minutePart = $createdAt->format('i'); // 분 2자리
                                $orderIdPadded = str_pad($orderId, 6, '0', STR_PAD_LEFT); // 주문ID 6자리
                                
                                // 형식: YYMMDDHH-MMXXXXXX (8자리-8자리)
                                // 예: 25121518-0004000001, 25121518-0004000002
                                $orderNumber = $dateTimePart . '-' . $minutePart . $orderIdPadded;
                                
                                // 디버깅: 주문 ID 확인 (임시)
                                // echo '<!-- Order ID: ' . $orderId . ', Keys: ' . implode(', ', array_keys($order)) . ' -->';
                                
                                echo htmlspecialchars($orderNumber);
                                ?>
                            </td>
                            <td>
                                <?php
                                // 주문 데이터를 JSON으로 안전하게 인코딩
                                $orderData = $order;
                                // NULL 값 처리 및 데이터 정리
                                foreach ($orderData as $key => $value) {
                                    if ($value === null) {
                                        $orderData[$key] = '';
                                    }
                                }
                                // JSON 인코딩 (에러 처리 포함)
                                $orderJson = json_encode($orderData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                if ($orderJson === false) {
                                    // JSON 인코딩 실패 시 기본값 사용
                                    $orderJson = '{}';
                                }
                                // HTML 속성에 안전하게 삽입하기 위해 이스케이프
                                $orderJsonEscaped = htmlspecialchars($orderJson, ENT_QUOTES, 'UTF-8');
                                ?>
                                <?php 
                                $provider = htmlspecialchars($order['provider'] ?? '-');
                                echo $provider;
                                ?>
                            </td>
                            <td>
                                <span class="product-name-link" data-order="<?php echo $orderJsonEscaped; ?>" onclick="showProductInfo(JSON.parse(this.getAttribute('data-order')), 'mvno')">
                                    <?php 
                                    $productName = htmlspecialchars($order['plan_name'] ?? '상품명 없음');
                                    echo $productName;
                                    ?>
                                </span>
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
                                <div class="status-cell-wrapper">
                                    <span class="status-badge status-<?php echo $order['application_status']; ?>">
                                        <?php echo $statusLabels[$order['application_status']] ?? $order['application_status']; ?>
                                    </span>
                                    <button type="button" class="status-edit-btn" onclick="openStatusEditModal(<?php echo isset($order['application_id']) ? $order['application_id'] : $order['id']; ?>, '<?php echo htmlspecialchars($order['application_status'], ENT_QUOTES); ?>')" title="상태 변경">
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
    try {
        const modal = document.getElementById('productInfoModal');
        const modalBody = document.getElementById('productInfoModalBody');
        
        if (!modal || !modalBody) {
            console.error('Modal elements not found');
            alert('상품 정보를 표시할 수 없습니다.');
            return;
        }
        
        if (!order || typeof order !== 'object') {
            console.error('Invalid order data:', order);
            alert('상품 정보를 불러올 수 없습니다.');
            return;
        }
        
        let html = '';
        
        if (productType === 'mvno') {
            const additionalInfo = order.additional_info || {};
            const productSnapshot = additionalInfo.product_snapshot || {};
            
            // 직접입력/직접선택 텍스트 제거 헬퍼 함수
            const removeDirectInputText = (value) => {
                if (!value || value === '-') return value;
                let cleaned = String(value);
                // "직접입력" 제거 (앞뒤 공백 포함)
                cleaned = cleaned.replace(/\s*직접입력\s*/g, '');
                // "직접선택" 제거 (앞뒤 공백 포함)
                cleaned = cleaned.replace(/\s*직접선택\s*/g, '');
                // 앞뒤 공백 제거
                cleaned = cleaned.trim();
                return cleaned || value; // 빈 문자열이면 원본 반환
            };
            
            // 고객이 가입한 정보를 우선 사용 (product_snapshot에서), 없으면 상품 기본 정보 사용
            const getValue = (customerKey, productKey, defaultValue = null) => {
                // product_snapshot에서 먼저 확인
                if (productSnapshot[customerKey] !== undefined && productSnapshot[customerKey] !== null) {
                    const value = productSnapshot[customerKey];
                    // 빈 문자열도 유효한 값으로 처리 (빈 문자열이면 빈 문자열 반환)
                    return value;
                }
                // additionalInfo에서 확인
                if (additionalInfo[customerKey] !== undefined && additionalInfo[customerKey] !== null) {
                    const value = additionalInfo[customerKey];
                    return value;
                }
                // order에서 확인
                if (order[productKey] !== undefined && order[productKey] !== null) {
                    return order[productKey];
                }
                // 기본값 반환 (null이면 빈 문자열 반환)
                return defaultValue !== null ? defaultValue : '';
            };
            
            // 가입 형태
            const subscriptionType = additionalInfo.subscription_type || '';
            const subscriptionTypeLabel = subscriptionType === 'new' ? '신규가입' : 
                                         subscriptionType === 'port' ? '번호이동' : 
                                         subscriptionType === 'change' ? '기기변경' : 
                                         subscriptionType || '-';
            
            // 통신 기술 (service_type)
            const serviceType = getValue('service_type', 'service_type');
            const serviceTypeLabel = serviceType === '5g' ? '5G' : 
                                    serviceType === 'lte' ? 'LTE' : 
                                    serviceType === '3g' ? '3G' : 
                                    serviceType || '-';
            
            // 통신망 (provider)
            const provider = getValue('provider', 'provider');
            // provider 값이 이미 "알뜰폰"을 포함하고 있으면 추가하지 않음
            let providerLabel = '-';
            if (provider) {
                if (provider.includes('알뜰폰')) {
                    providerLabel = provider;
                } else {
                    providerLabel = provider + (serviceTypeLabel !== '-' ? '알뜰폰' : '');
                }
            }
            
            // 약정기간
            const contractPeriod = getValue('contract_period', 'contract_period');
            const contractPeriodDays = order.contract_period_days ? parseInt(order.contract_period_days) : 0;
            let contractPeriodLabel = '-';
            if (contractPeriod === '무약정' || contractPeriod === 'none') {
                contractPeriodLabel = '무약정';
            } else if (contractPeriodDays > 0) {
                contractPeriodLabel = contractPeriodDays + '일';
            } else if (contractPeriod) {
                contractPeriodLabel = contractPeriod;
            }
            
            // 가입 형태 (신규, 번이, 기변)
            const subscriptionTypes = [];
            if (subscriptionType === 'new' || (order.contract_period && order.contract_period.includes('신규'))) subscriptionTypes.push('신규');
            if (subscriptionType === 'port' || (order.contract_period && order.contract_period.includes('번호이동'))) subscriptionTypes.push('번호이동');
            if (subscriptionType === 'change' || (order.contract_period && order.contract_period.includes('기기변경'))) subscriptionTypes.push('기기변경');
            const subscriptionTypesLabel = subscriptionTypes.length > 0 ? subscriptionTypes.join(', ') : subscriptionTypeLabel;
            
            // 데이터 제공량
            const dataAmount = getValue('data_amount', 'data_amount');
            const dataAmountValue = getValue('data_amount_value', 'data_amount_value');
            const dataUnit = getValue('data_unit', 'data_unit');
            let dataAmountLabel = '-';
            if (dataAmountValue && dataAmountValue !== '-' && dataUnit && dataUnit !== '-') {
                dataAmountLabel = '월 ' + dataAmountValue + dataUnit;
            } else if (dataAmount && dataAmount !== '-') {
                dataAmountLabel = '월 ' + dataAmount;
            }
            
            // 데이터 추가제공
            const dataAdditional = getValue('data_additional', 'data_additional');
            const dataAdditionalValue = getValue('data_additional_value', 'data_additional_value');
            let dataAdditionalLabel = '-';
            if (dataAdditional === '직접입력' && dataAdditionalValue) {
                dataAdditionalLabel = removeDirectInputText(dataAdditionalValue);
            } else if (dataAdditional && dataAdditional !== '없음') {
                dataAdditionalLabel = removeDirectInputText(dataAdditional);
            } else {
                dataAdditionalLabel = '없음';
            }
            
            // 데이터 소진시
            const dataExhausted = getValue('data_exhausted', 'data_exhausted');
            const dataExhaustedValue = getValue('data_exhausted_value', 'data_exhausted_value');
            let dataExhaustedLabel = '-';
            if (dataExhaustedValue && dataExhaustedValue !== '-') {
                let combined = dataExhaustedValue + (dataExhausted && dataExhausted !== '-' ? ' ' + dataExhausted : '');
                dataExhaustedLabel = removeDirectInputText(combined);
            } else if (dataExhausted && dataExhausted !== '-') {
                dataExhaustedLabel = removeDirectInputText(dataExhausted);
            }
            
            // 통화
            const callType = getValue('call_type', 'call_type');
            const callAmount = getValue('call_amount', 'call_amount');
            let callLabel = '-';
            if (callType) {
                if (callAmount && callAmount !== '-') {
                    let combined = callType + ' ' + callAmount;
                    callLabel = removeDirectInputText(combined);
                } else {
                    callLabel = removeDirectInputText(callType);
                }
            }
            
            // 부가통화
            const additionalCallType = getValue('additional_call_type', 'additional_call_type');
            const additionalCall = getValue('additional_call', 'additional_call');
            let additionalCallLabel = '-';
            if (additionalCallType) {
                if (additionalCall && additionalCall !== '-') {
                    let combined = additionalCallType + ' ' + additionalCall;
                    additionalCallLabel = removeDirectInputText(combined);
                } else {
                    additionalCallLabel = removeDirectInputText(additionalCallType);
                }
            }
            
            // 문자
            const smsType = getValue('sms_type', 'sms_type');
            const smsAmount = getValue('sms_amount', 'sms_amount');
            let smsLabel = '-';
            if (smsType) {
                if (smsAmount && smsAmount !== '-') {
                    let combined = smsType + ' ' + smsAmount;
                    smsLabel = removeDirectInputText(combined);
                } else {
                    smsLabel = removeDirectInputText(smsType);
                }
            }
            
            // 테더링(핫스팟)
            const mobileHotspot = getValue('mobile_hotspot', 'mobile_hotspot');
            const mobileHotspotValue = getValue('mobile_hotspot_value', 'mobile_hotspot_value');
            let mobileHotspotLabel = '-';
            if (mobileHotspotValue && mobileHotspotValue !== '-') {
                let combined = mobileHotspotValue + (mobileHotspot && mobileHotspot !== '-' ? ' ' + mobileHotspot : '');
                mobileHotspotLabel = removeDirectInputText(combined);
            } else if (mobileHotspot && mobileHotspot !== '-') {
                mobileHotspotLabel = removeDirectInputText(mobileHotspot);
            }
            
            // 유심 정보
            const regularSimAvailable = getValue('regular_sim_available', 'regular_sim_available');
            const regularSimPrice = getValue('regular_sim_price', 'regular_sim_price');
            const regularSimLabel = regularSimAvailable === '배송가능' && regularSimPrice ? 
                                   '배송가능 (' + number_format(regularSimPrice) + '원)' : 
                                   regularSimAvailable === '배송불가' ? '배송불가' : 
                                   regularSimAvailable || '-';
            
            const nfcSimAvailable = getValue('nfc_sim_available', 'nfc_sim_available');
            const nfcSimPrice = getValue('nfc_sim_price', 'nfc_sim_price');
            const nfcSimLabel = nfcSimAvailable === '배송가능' && nfcSimPrice ? 
                               '배송가능 (' + number_format(nfcSimPrice) + '원)' : 
                               nfcSimAvailable === '배송불가' ? '배송불가' : 
                               nfcSimAvailable || '-';
            
            const esimAvailable = getValue('esim_available', 'esim_available');
            const esimPrice = getValue('esim_price', 'esim_price');
            const esimLabel = esimAvailable === '개통가능' && esimPrice ? 
                             '개통가능 (' + number_format(esimPrice) + '원)' : 
                             esimAvailable === '개통불가' ? '개통불가' : 
                             esimAvailable || '-';
            
            // 기본 제공 초과 시
            const overDataPrice = getValue('over_data_price', 'over_data_price');
            const overVoicePrice = getValue('over_voice_price', 'over_voice_price');
            const overVideoPrice = getValue('over_video_price', 'over_video_price');
            const overSmsPrice = getValue('over_sms_price', 'over_sms_price');
            const overLmsPrice = getValue('over_lms_price', 'over_lms_price');
            const overMmsPrice = getValue('over_mms_price', 'over_mms_price');
            
            // 프로모션 및 혜택
            const parseJsonField = (field) => {
                if (!field) return [];
                if (typeof field === 'string') {
                    try {
                        return JSON.parse(field);
                    } catch (e) {
                        return Array.isArray(field) ? field : [field];
                    }
                }
                return Array.isArray(field) ? field : [];
            };
            
            // benefits와 promotions는 productSnapshot에서 우선 가져오기
            const benefitsRaw = getValue('benefits', 'benefits');
            const promotionsRaw = getValue('promotions', 'promotions');
            const promotionTitleRaw = getValue('promotion_title', 'promotion_title');
            
            const promotions = parseJsonField(promotionsRaw);
            const benefits = parseJsonField(benefitsRaw);
            const promotionTitle = promotionTitleRaw || '';
            
            // 값이 '-'가 아닌 경우에만 행 추가하는 헬퍼 함수
            const addRowIfNotDash = (rows, label, value) => {
                if (value && value !== '-') {
                    rows.push(`<tr><th>${label}</th><td>${value}</td></tr>`);
                }
            };
            
            // 기본 정보 섹션
            let basicInfoRows = [];
            if (order.plan_name && order.plan_name !== '-') {
                basicInfoRows.push(`<tr><th>요금제 이름</th><td>${order.plan_name}</td></tr>`);
            }
            addRowIfNotDash(basicInfoRows, '통신사 약정', contractPeriodLabel);
            addRowIfNotDash(basicInfoRows, '통신망', providerLabel);
            addRowIfNotDash(basicInfoRows, '통신 기술', serviceTypeLabel);
            addRowIfNotDash(basicInfoRows, '가입 형태', subscriptionTypesLabel);
            
            if (basicInfoRows.length > 0) {
                html += `
                    <div class="product-info-section">
                        <h3>기본 정보</h3>
                        <table class="product-info-table">
                            ${basicInfoRows.join('')}
                        </table>
                    </div>
                `;
            }
            
            // 데이터 정보 섹션
            let dataInfoRows = [];
            addRowIfNotDash(dataInfoRows, '통화', callLabel);
            addRowIfNotDash(dataInfoRows, '문자', smsLabel);
            addRowIfNotDash(dataInfoRows, '데이터 제공량', dataAmountLabel);
            addRowIfNotDash(dataInfoRows, '데이터 추가제공', dataAdditionalLabel);
            addRowIfNotDash(dataInfoRows, '데이터 소진시', dataExhaustedLabel);
            addRowIfNotDash(dataInfoRows, '부가통화', additionalCallLabel);
            addRowIfNotDash(dataInfoRows, '테더링(핫스팟)', mobileHotspotLabel);
            
            if (dataInfoRows.length > 0) {
                html += `
                    <div class="product-info-section">
                        <h3>데이터 정보</h3>
                        <table class="product-info-table">
                            ${dataInfoRows.join('')}
                        </table>
                    </div>
                `;
            }
            
            // 유심 정보 섹션
            let simInfoRows = [];
            addRowIfNotDash(simInfoRows, '일반 유심', regularSimLabel);
            addRowIfNotDash(simInfoRows, 'NFC 유심', nfcSimLabel);
            addRowIfNotDash(simInfoRows, 'eSIM', esimLabel);
            
            if (simInfoRows.length > 0) {
                html += `
                    <div class="product-info-section">
                        <h3>유심 정보</h3>
                        <table class="product-info-table">
                            ${simInfoRows.join('')}
                        </table>
                    </div>
                `;
            }
            
            // 기본 제공 초과 시 섹션
            let overLimitRows = [];
            addRowIfNotDash(overLimitRows, '데이터', overDataPrice);
            addRowIfNotDash(overLimitRows, '음성', overVoicePrice);
            addRowIfNotDash(overLimitRows, '영상통화', overVideoPrice);
            addRowIfNotDash(overLimitRows, '단문메시지(SMS)', overSmsPrice);
            addRowIfNotDash(overLimitRows, '텍스트형(LMS,MMS)', overLmsPrice);
            addRowIfNotDash(overLimitRows, '멀티미디어형(MMS)', overMmsPrice);
            
            if (overLimitRows.length > 0) {
                html += `
                    <div class="product-info-section">
                        <h3>기본 제공 초과 시</h3>
                        <table class="product-info-table">
                            ${overLimitRows.join('')}
                        </table>
                    </div>
                `;
            }
            
            // 프로모션 이벤트 섹션 (아코디언에 있는 것)
            if (promotionTitle || promotions.length > 0) {
                html += `
                    <div class="product-info-section">
                        <h3>프로모션 이벤트</h3>
                        ${promotionTitle ? `<p style="margin-bottom: 12px; font-weight: 600; color: #1f2937;">${promotionTitle}</p>` : ''}
                        ${promotions.length > 0 ? `<ul style="margin: 0 0 0 20px; padding: 0;"><li style="margin-bottom: 8px;">${promotions.join('</li><li style="margin-bottom: 8px;">')}</li></ul>` : ''}
                    </div>
                `;
            }
            
            // 혜택 및 유의사항 섹션
            if (benefits.length > 0) {
                // 줄바꿈을 <br>로 변환하는 헬퍼 함수
                const formatBenefit = (text) => {
                    if (!text) return '';
                    // HTML 이스케이프 후 줄바꿈을 <br>로 변환
                    return String(text)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;')
                        .replace(/\n/g, '<br>');
                };
                
                html += `
                    <div class="product-info-section">
                        <h3>혜택 및 유의사항</h3>
                        <ul style="margin: 0 0 0 20px; padding: 0;">
                            ${benefits.map(benefit => `<li style="margin-bottom: 8px; white-space: pre-wrap;">${formatBenefit(benefit)}</li>`).join('')}
                        </ul>
                    </div>
                `;
            }
            
            modalBody.innerHTML = html;
            modal.style.display = 'block';
        }
    } catch (error) {
        console.error('Error showing product info:', error);
        alert('상품 정보를 표시하는 중 오류가 발생했습니다.');
    }
}

// 숫자 포맷팅 함수
function number_format(num) {
    if (!num && num !== 0) return '0';
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

// 상태 변경 모달 열기
function openStatusEditModal(applicationId, currentStatus) {
    const modal = document.getElementById('statusEditModal');
    const select = document.getElementById('statusEditSelect');
    
    if (!modal || !select) return;
    
    // 현재 상태 선택
    select.value = currentStatus;
    select.setAttribute('data-application-id', applicationId);
    
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
    if (!select) return;
    
    const applicationId = select.getAttribute('data-application-id');
    const newStatus = select.value;
    
    if (!applicationId || !newStatus) {
        return;
    }
    
    // API 호출
    fetch('/MVNO/api/update-order-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `application_id=${applicationId}&status=${encodeURIComponent(newStatus)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeStatusEditModal();
            if (typeof showAlert === 'function') {
                showAlert('상태가 변경되었습니다.', '완료');
            } else {
                alert('상태가 변경되었습니다.');
            }
            // 페이지 새로고침
            location.reload();
        } else {
            if (typeof showAlert === 'function') {
                showAlert(data.message || '상태 변경에 실패했습니다.', '오류', true);
            } else {
                alert(data.message || '상태 변경에 실패했습니다.');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showAlert === 'function') {
            showAlert('상태 변경 중 오류가 발생했습니다.', '오류', true);
        } else {
            alert('상태 변경 중 오류가 발생했습니다.');
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
                <option value="received">접수</option>
                <option value="activating">개통중</option>
                <option value="on_hold">보류</option>
                <option value="cancelled">취소</option>
                <option value="activation_completed">개통완료</option>
                <option value="installation_completed">설치완료</option>
            </select>
        </div>
        <div class="status-modal-actions">
            <button type="button" class="status-modal-btn status-modal-btn-cancel" onclick="closeStatusEditModal()">취소</button>
            <button type="button" class="status-modal-btn status-modal-btn-save" onclick="updateOrderStatus()">변경</button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>


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

// 페이지네이션 및 검색 파라미터 처리
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = intval($_GET['per_page'] ?? 10);
if (!in_array($perPage, [10, 50, 100, 500])) {
    $perPage = 10;
}
$searchKeyword = trim($_GET['search'] ?? '');

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

// 접수건 조회 - 통합 검색 포함
$applications = [];
$total = 0;
$totalPages = 0;

if ($pdo && empty($errors)) {
    try {
        // 검색 조건 생성
        $whereConditions = ["a.product_type = 'internet'"];
        $params = [];
        
        // 통합검색
        if ($searchKeyword && $searchKeyword !== '') {
            $searchConditions = [];
            
            // 주문번호 검색
            $cleanOrder = preg_replace('/[^0-9]/', '', $searchKeyword);
            if (strlen($cleanOrder) >= 2) {
                $searchConditions[] = "REPLACE(a.order_number, '-', '') LIKE :search_order";
                $params[':search_order'] = '%' . $cleanOrder . '%';
                $searchConditions[] = 'a.order_number LIKE :search_order_original';
                $params[':search_order_original'] = '%' . $searchKeyword . '%';
            }
            
            // 판매자 아이디 검색
            $searchConditions[] = 's.user_id LIKE :search_seller_id';
            $params[':search_seller_id'] = '%' . $searchKeyword . '%';
            
            // 판매자명 검색
            $searchConditions[] = 's.name LIKE :search_seller_name';
            $params[':search_seller_name'] = '%' . $searchKeyword . '%';
            $searchConditions[] = 's.company_name LIKE :search_seller_company';
            $params[':search_seller_company'] = '%' . $searchKeyword . '%';
            
            // 회원아이디 검색
            $searchConditions[] = 'c.user_id LIKE :search_customer_id';
            $params[':search_customer_id'] = '%' . $searchKeyword . '%';
            
            // 고객명 검색
            $searchConditions[] = 'c.name LIKE :search_customer_name';
            $params[':search_customer_name'] = '%' . $searchKeyword . '%';
            
            // 전화번호 검색
            $cleanPhone = preg_replace('/[^0-9]/', '', $searchKeyword);
            if (strlen($cleanPhone) >= 3) {
                $searchConditions[] = "REPLACE(REPLACE(REPLACE(c.phone, '-', ''), ' ', ''), '.', '') LIKE :search_phone";
                $params[':search_phone'] = '%' . $cleanPhone . '%';
            } else {
                $searchConditions[] = 'c.phone LIKE :search_phone_fallback';
                $params[':search_phone_fallback'] = '%' . $searchKeyword . '%';
            }
            
            if (!empty($searchConditions)) {
                $whereConditions[] = '(' . implode(' OR ', $searchConditions) . ')';
            }
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // 전체 개수 조회
        $countSql = "
            SELECT COUNT(DISTINCT a.id) as cnt
            FROM product_applications a
            INNER JOIN application_customers c ON a.id = c.application_id
            LEFT JOIN users s ON a.seller_id = s.user_id AND s.role = 'seller'
            WHERE $whereClause
        ";
        
        $countStmt = $pdo->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        
        if (!$countStmt->execute()) {
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
        $selectSql = "
            SELECT DISTINCT
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
                inet.service_type,
                inet.speed_option,
                inet.monthly_fee,
                p.status as product_status,
                (SELECT ABS(delta) FROM user_point_ledger 
                 WHERE user_id = c.user_id 
                   AND item_id = a.product_id 
                   AND type = 'internet' 
                   AND delta < 0 
                   AND created_at <= a.created_at
                 ORDER BY created_at DESC LIMIT 1) as used_point
            FROM product_applications a
            INNER JOIN application_customers c ON a.id = c.application_id
            LEFT JOIN products p ON a.product_id = p.id
            LEFT JOIN product_internet_details inet ON p.id = inet.product_id
            LEFT JOIN users s ON a.seller_id = s.user_id AND s.role = 'seller'
            WHERE $whereClause
            ORDER BY a.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $selectStmt = $pdo->prepare($selectSql);
        foreach ($params as $key => $value) {
            $selectStmt->bindValue($key, $value);
        }
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
                // point_setting과 point_benefit_description도 product_snapshot에서 가져오기
                if (isset($productSnapshot['point_setting']) && $productSnapshot['point_setting'] !== '') {
                    $app['point_setting'] = $productSnapshot['point_setting'];
                }
                if (isset($productSnapshot['point_benefit_description']) && $productSnapshot['point_benefit_description'] !== '') {
                    $app['point_benefit_description'] = $productSnapshot['point_benefit_description'];
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
        max-width: 100%;
        margin: 0 auto;
        overflow-x: auto;
        width: 100%;
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
        grid-template-columns: 50px 60px 120px 100px 150px 120px 150px 120px 100px 100px 120px 120px 100px 200px 140px;
        gap: 12px;
        font-weight: 600;
        font-size: 13px;
        color: #374151;
        min-width: 2000px;
        white-space: nowrap !important;
    }
    
    .table-row {
        padding: 16px;
        border-bottom: 1px solid #e5e7eb;
        display: grid;
        grid-template-columns: 50px 60px 120px 100px 150px 120px 150px 120px 100px 100px 120px 120px 100px 200px 140px;
        gap: 12px;
        align-items: center;
        transition: background 0.2s;
        min-width: 2000px;
        white-space: nowrap !important;
        grid-auto-flow: column;
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
        white-space: nowrap !important;
        overflow: hidden;
        text-overflow: ellipsis;
        word-break: keep-all;
        line-height: 1.4;
    }
    
    .table-cell * {
        white-space: nowrap !important;
    }
    
    .table-cell > div {
        display: inline !important;
    }
    
    .table-cell > span {
        display: inline !important;
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
    
    .filter-bar {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }
    
    .filter-input {
        padding: 10px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.2s;
    }
    
    .filter-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .filter-select {
        padding: 10px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        background: white;
        cursor: pointer;
        transition: border-color 0.2s;
    }
    
    .filter-select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .filter-button {
        padding: 10px 24px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .filter-button:hover {
        background: #2563eb;
    }
    
    .reset-button {
        padding: 10px 24px;
        background: #6b7280;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
        text-decoration: none;
        display: inline-block;
    }
    
    .reset-button:hover {
        background: #4b5563;
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
    
    .product-info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        background: white;
    }
    
    .product-info-table th {
        background: #f9fafb;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #374151;
        border: 1px solid #e5e7eb;
        width: 30%;
        font-size: 14px;
    }
    
    .product-info-table td {
        padding: 12px;
        border: 1px solid #e5e7eb;
        color: #1f2937;
        font-size: 14px;
    }
    
    .product-info-table tr:nth-child(even) {
        background: #f9fafb;
    }
    
    .product-info-table tr:hover {
        background: #f3f4f6;
    }
    
    .checkbox-column {
        width: 50px;
        text-align: center;
    }
    
    .order-checkbox {
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
        background: #3b82f6;
        color: white;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .bulk-actions-btn:hover:not(:disabled) {
        background: #2563eb;
    }
    
    .bulk-actions-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .status-cell-wrapper {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: flex-start;
        gap: 8px;
        white-space: nowrap;
        overflow: visible;
        flex-wrap: nowrap;
        flex-shrink: 0;
    }
    
    .table-cell:has(.status-cell-wrapper) {
        overflow: visible !important;
        white-space: nowrap !important;
        min-width: 140px;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
        flex-shrink: 0;
        line-height: 1;
    }
    
    .status-edit-btn {
        background: none;
        border: none;
        padding: 4px;
        cursor: pointer;
        color: #6b7280;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: all 0.2s;
        flex-shrink: 0;
        white-space: nowrap;
        line-height: 1;
        vertical-align: middle;
    }
    
    .status-edit-btn:hover {
        background: #f3f4f6;
        color: #374151;
    }
    
    .status-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }
    
    .status-modal {
        background: white;
        border-radius: 12px;
        padding: 24px;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 20px 25px rgba(0, 0, 0, 0.2);
    }
    
    .status-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .status-modal-header h3 {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
    }
    
    .status-modal-close {
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
    
    .status-modal-close:hover {
        background: #f3f4f6;
    }
    
    .status-modal-body {
        margin-bottom: 20px;
    }
    
    .status-modal-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .status-modal-select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        background: white;
    }
    
    .status-modal-footer {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }
    
    .status-modal-btn {
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }
    
    .status-modal-btn-cancel {
        background: #f3f4f6;
        color: #374151;
    }
    
    .status-modal-btn-cancel:hover {
        background: #e5e7eb;
    }
    
    .status-modal-btn-confirm {
        background: #3b82f6;
        color: white;
    }
    
    .status-modal-btn-confirm:hover {
        background: #2563eb;
    }
    
    /* Alert Modal */
    .alert-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 3000;
        align-items: center;
        justify-content: center;
    }
    
    .alert-modal {
        background: white;
        border-radius: 12px;
        padding: 24px;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    .alert-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    
    .alert-modal-header h3 {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
    }
    
    .alert-modal-close {
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
    
    .alert-modal-close:hover {
        background: #f3f4f6;
    }
    
    .alert-modal-body {
        margin-bottom: 20px;
        color: #374151;
        font-size: 14px;
        line-height: 1.6;
    }
    
    .alert-modal-footer {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }
    
    .alert-modal-btn {
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }
    
    .alert-modal-btn-confirm {
        background: #3b82f6;
        color: white;
    }
    
    .alert-modal-btn-confirm:hover {
        background: #2563eb;
    }
</style>

<div class="order-list-container">
    <div class="page-header">
        <h1>인터넷 접수건 관리</h1>
        <div style="font-size: 14px; color: #6b7280;">
            총 <strong><?php echo number_format($total); ?></strong>건
        </div>
    </div>
    
    <!-- 통합 검색 필터 -->
    <div class="filter-bar">
        <form method="GET" action="" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 300px;">
                <input type="text" 
                       name="search" 
                       value="<?php echo htmlspecialchars($searchKeyword); ?>" 
                       placeholder="주문번호, 판매자 아이디, 판매자명, 회원아이디, 고객명, 전화번호로 검색" 
                       class="filter-input"
                       style="width: 100%;">
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <label for="per_page" style="font-size: 14px; font-weight: 600; color: #374151; white-space: nowrap;">페이지당 표시:</label>
                <select name="per_page" id="per_page" class="filter-select" onchange="this.form.submit()" style="min-width: 100px;">
                    <option value="10" <?php echo $perPage == 10 ? 'selected' : ''; ?>>10개</option>
                    <option value="50" <?php echo $perPage == 50 ? 'selected' : ''; ?>>50개</option>
                    <option value="100" <?php echo $perPage == 100 ? 'selected' : ''; ?>>100개</option>
                    <option value="500" <?php echo $perPage == 500 ? 'selected' : ''; ?>>500개</option>
                </select>
            </div>
            <button type="submit" class="filter-button">검색</button>
            <?php if ($searchKeyword): ?>
                <a href="?per_page=<?php echo $perPage; ?>" class="reset-button">초기화</a>
            <?php endif; ?>
        </form>
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
        <!-- 일괄 변경 UI -->
        <div class="bulk-actions" id="bulkActions">
            <span class="bulk-actions-info">
                <span id="selectedCount">0</span>개 선택됨
            </span>
            <select id="bulkStatusSelect" class="bulk-actions-select">
                <option value="">진행상황 선택</option>
                <option value="received">접수</option>
                <option value="on_hold">보류</option>
                <option value="cancelled">취소</option>
                <option value="installation_completed">설치완료</option>
                <option value="closed">종료</option>
            </select>
            <button type="button" class="bulk-actions-btn" onclick="bulkUpdateStatus()" id="bulkUpdateBtn" disabled>일괄 변경</button>
        </div>
        
        <div class="table-header">
            <div class="checkbox-column">
                <input type="checkbox" id="selectAll" class="order-checkbox" onchange="toggleSelectAll(this)">
            </div>
            <div>순번</div>
            <div>주문번호</div>
            <div>판매자아이디</div>
            <div>판매자명</div>
            <div>신청 인터넷 회선</div>
            <div>결합여부</div>
            <div>속도</div>
            <div>기존 인터넷 회선</div>
            <div>월 요금</div>
            <div>회원아이디</div>
            <div>고객명</div>
            <div>전화번호</div>
            <div>포인트</div>
            <div>혜택내용</div>
            <div></div>
        </div>
        
        <?php 
        // total이 0보다 크거나 applications가 있으면 표시
        if ($total > 0 || count($applications) > 0): 
            if (count($applications) > 0): 
                foreach ($applications as $index => $app): 
                    $rowNum = $total - ($page - 1) * $perPage - $index;
                    // 접수일 포맷팅
                    $orderDate = $app['order_date'] ?? '';
                    $formattedDate = $orderDate ? date('Y-m-d', strtotime($orderDate)) : '-';
        ?>
            <div class="table-row">
                <div class="table-cell checkbox-column">
                    <input type="checkbox" class="order-checkbox order-checkbox-item" value="<?php echo $app['application_id']; ?>">
                </div>
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
                <div class="table-cell" style="white-space: nowrap !important; display: flex; align-items: center; gap: 4px; flex-wrap: nowrap;">
                    <span style="white-space: nowrap !important; flex-shrink: 0;"><?php echo htmlspecialchars($app['seller_name'] ?? '-'); ?></span>
                    <?php if (!empty($app['seller_company_name'])): ?>
                        <span style="font-size: 11px; color: #6b7280; white-space: nowrap !important; flex-shrink: 0;">(<?php echo htmlspecialchars($app['seller_company_name']); ?>)</span>
                    <?php endif; ?>
                </div>
                <div class="table-cell">
                    <?php if (!empty($app['registration_place']) && $app['registration_place'] !== '-'): ?>
                        <span class="clickable-cell" onclick="showProductModal(<?php echo htmlspecialchars(json_encode($app, JSON_UNESCAPED_UNICODE)); ?>)">
                            <?php echo htmlspecialchars($app['registration_place']); ?>
                        </span>
                    <?php else: ?>
                        <?php echo htmlspecialchars($app['registration_place'] ?? '-'); ?>
                    <?php endif; ?>
                </div>
                <div class="table-cell" style="white-space: nowrap !important;">
                    <?php 
                    $serviceType = $app['service_type'] ?? '인터넷';
                    $serviceTypeDisplay = $serviceType;
                    if ($serviceType === '인터넷+TV') {
                        $serviceTypeDisplay = '인터넷 + TV 결합';
                    } elseif ($serviceType === '인터넷+TV+핸드폰') {
                        $serviceTypeDisplay = '인터넷 + TV + 핸드폰 결합';
                    }
                    echo htmlspecialchars($serviceTypeDisplay);
                    ?>
                </div>
                <div class="table-cell"><?php echo htmlspecialchars($app['speed_option'] ?? '-'); ?></div>
                <div class="table-cell">
                    <?php
                    // additional_info 파싱
                    $additionalInfo = [];
                    if (!empty($app['additional_info'])) {
                        $decoded = json_decode($app['additional_info'], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $additionalInfo = $decoded;
                        }
                    }
                    // 기존 인터넷 회선 정보 가져오기
                    $existingCompany = $additionalInfo['currentCompany'] ?? 
                                       $additionalInfo['existing_company'] ?? 
                                       $additionalInfo['existingCompany'] ?? '';
                    echo $existingCompany ? htmlspecialchars($existingCompany) : '-';
                    ?>
                </div>
                <div class="table-cell" style="text-align: right; white-space: nowrap !important;">
                    <?php 
                    $monthlyFee = $app['monthly_fee'] ?? 0;
                    // 문자열에서 숫자만 추출 (예: "3000원" -> 3000)
                    if (is_string($monthlyFee)) {
                        $monthlyFee = preg_replace('/[^0-9]/', '', $monthlyFee);
                    }
                    $monthlyFee = (float)$monthlyFee;
                    echo $monthlyFee > 0 ? number_format($monthlyFee) . '원' : '-';
                    ?>
                </div>
                <div class="table-cell">
                    <?php if (!empty($app['customer_user_id']) && $app['customer_user_id'] !== '-'): ?>
                        <span class="clickable-cell" onclick="showCustomerModal(<?php echo htmlspecialchars(json_encode($app, JSON_UNESCAPED_UNICODE)); ?>)">
                            <?php echo htmlspecialchars($app['customer_user_id']); ?>
                        </span>
                    <?php else: ?>
                        <?php echo htmlspecialchars($app['customer_user_id'] ?? '-'); ?>
                    <?php endif; ?>
                </div>
                <div class="table-cell"><?php echo htmlspecialchars($app['customer_name'] ?? '-'); ?></div>
                <div class="table-cell"><?php echo htmlspecialchars($app['customer_phone'] ?? '-'); ?></div>
                <div class="table-cell">
                    <?php 
                    $usedPoint = isset($app['used_point']) ? intval($app['used_point']) : 0;
                    if ($usedPoint > 0): 
                        $formattedPoint = number_format($usedPoint);
                    ?>
                        <span style="color: #6366f1; font-weight: 600;" title="포인트 사용: <?php echo $formattedPoint; ?>원">
                            <?php echo $formattedPoint; ?>원
                        </span>
                    <?php else: ?>
                        <span style="color: #9ca3af;">-</span>
                    <?php endif; ?>
                </div>
                <div class="table-cell" style="color: #10b981; font-weight: 500; white-space: nowrap;" title="<?php echo !empty($app['point_benefit_description']) ? htmlspecialchars($app['point_benefit_description']) : ''; ?>">
                    <?php if (!empty($app['point_benefit_description'])): ?>
                        <?php echo htmlspecialchars($app['point_benefit_description']); ?>
                    <?php else: ?>
                        <span style="color: #9ca3af;">-</span>
                    <?php endif; ?>
                </div>
                <div class="table-cell">
                    <div class="status-cell-wrapper">
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
                        $currentStatus = htmlspecialchars($appStatus, ENT_QUOTES);
                        $appId = $app['application_id'];
                        ?>
                        <span class="status-badge status-<?php echo htmlspecialchars($appStatus); ?>">
                            <?php echo htmlspecialchars($statusLabel); ?>
                        </span>
                        <button type="button" class="status-edit-btn" onclick="openStatusEditModal(<?php echo $appId; ?>, '<?php echo $currentStatus; ?>')" title="상태 변경">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>
                    </div>
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
    <?php if ($totalPages > 1): 
        $paginationParams = array_filter([
            'search' => $searchKeyword,
            'per_page' => $perPage
        ], fn($v) => $v !== '');
        // 페이지 그룹 계산 (10개씩 그룹화)
        $pageGroupSize = 10;
        $currentGroup = ceil($page / $pageGroupSize);
        $startPage = ($currentGroup - 1) * $pageGroupSize + 1;
        $endPage = min($currentGroup * $pageGroupSize, $totalPages);
        $prevGroupLastPage = ($currentGroup - 1) * $pageGroupSize;
        $nextGroupFirstPage = $currentGroup * $pageGroupSize + 1;
    ?>
        <div class="pagination">
            <?php if ($currentGroup > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($paginationParams, ['page' => $prevGroupLastPage])); ?>">이전</a>
            <?php endif; ?>
            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?<?php echo http_build_query(array_merge($paginationParams, ['page' => $i])); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($nextGroupFirstPage <= $totalPages): ?>
                <a href="?<?php echo http_build_query(array_merge($paginationParams, ['page' => $nextGroupFirstPage])); ?>">다음</a>
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
        showAlertModal('판매자 정보를 찾을 수 없습니다.');
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

// 가격 포맷팅 함수
function formatPrice(price) {
    if (!price || price === '-' || price === '') return '-';
    
    // 문자열로 변환
    const priceStr = String(price);
    
    // 이미 단위가 포함되어 있는지 확인 (한글이 포함된 경우)
    if (/[가-힣]/.test(priceStr)) {
        // 숫자 부분만 추출하여 포맷팅 (소수점 제거)
        const numericValue = priceStr.replace(/[^0-9]/g, '');
        if (!numericValue) return priceStr; // 숫자가 없으면 원본 반환
        
        const formatted = number_format(parseInt(numericValue));
        // 원본에서 단위 추출 (예: "3000원" -> "원")
        const unitMatch = priceStr.match(/[가-힣]+/);
        const unit = unitMatch ? unitMatch[0] : '원';
        return formatted + unit;
    }
    
    // 숫자만 있는 경우
    const numericValue = priceStr.replace(/[^0-9.]/g, '');
    if (!numericValue) return priceStr;
    
    const num = parseFloat(numericValue);
    if (isNaN(num)) return priceStr;
    
    return number_format(Math.floor(num)) + '원';
}

// 상품 정보 모달 표시 (판매자 페이지와 동일한 형태)
function showProductModal(orderData) {
    const content = document.getElementById('productModalContent');
    document.getElementById('productModal').style.display = 'flex';
    
    // orderData가 문자열인 경우 파싱
    if (typeof orderData === 'string') {
        try {
            orderData = JSON.parse(orderData);
        } catch (e) {
            console.error('Failed to parse order data:', e);
            showAlertModal('상품 정보를 불러올 수 없습니다.');
            return;
        }
    }
    
    if (!orderData || typeof orderData !== 'object') {
        console.error('Invalid order data:', orderData);
        showAlertModal('상품 정보를 불러올 수 없습니다.');
        return;
    }
    
    // additional_info 파싱
    let additionalInfo = {};
    if (orderData.additional_info) {
        try {
            additionalInfo = typeof orderData.additional_info === 'string' 
                ? JSON.parse(orderData.additional_info) 
                : orderData.additional_info;
        } catch (e) {
            console.error('Error parsing additional_info:', e);
        }
    }
    
    // product_snapshot에서 신청 시점 정보 가져오기
    const productSnapshot = additionalInfo.product_snapshot || {};
    
    // order 객체를 판매자 페이지와 동일한 형식으로 변환
    const order = {
        registration_place: productSnapshot.registration_place || orderData.registration_place || '-',
        speed_option: productSnapshot.speed_option || orderData.speed_option || '-',
        monthly_fee: productSnapshot.monthly_fee || orderData.monthly_fee || '-',
        service_type: productSnapshot.service_type || orderData.service_type || '인터넷',
        cash_payment_names: productSnapshot.cash_payment_names || orderData.cash_payment_names,
        cash_payment_prices: productSnapshot.cash_payment_prices || orderData.cash_payment_prices,
        gift_card_names: productSnapshot.gift_card_names || orderData.gift_card_names,
        gift_card_prices: productSnapshot.gift_card_prices || orderData.gift_card_prices,
        equipment_names: productSnapshot.equipment_names || orderData.equipment_names,
        equipment_prices: productSnapshot.equipment_prices || orderData.equipment_prices,
        installation_names: productSnapshot.installation_names || orderData.installation_names,
        installation_prices: productSnapshot.installation_prices || orderData.installation_prices,
        additional_info: additionalInfo
    };
    
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
    
    // 필드명 정리 함수
    const cleanFieldName = (name) => {
        if (!name || typeof name !== 'string') return name;
        name = name.trim();
        const corrections = [
            { pattern: /와이파이공유기\s*[ㅇㄹㅁㄴㅂㅅ]+/g, replacement: '와이파이공유기' },
            { pattern: /와이파이공유기\s*[ㅇㄹ]/g, replacement: '와이파이공유기' },
            { pattern: /스?\s*설[ㅊㅈ]?이비/g, replacement: '설치비' },
            { pattern: /설[ㅊㅈ]?이비/g, replacement: '설치비' },
            { pattern: /\s+/g, replacement: ' ' },
        ];
        corrections.forEach(({ pattern, replacement }) => {
            name = name.replace(pattern, replacement);
        });
        name = name.replace(/[^\uAC00-\uD7A3a-zA-Z0-9\s]/g, '');
        name = name.replace(/\s+[ㅇㄹㅁㄴㅂㅅㅇㄹ]+$/g, '');
        return name.trim();
    };
    
    // 중복 제거 및 유효성 검사 함수
    const cleanNamePricePairs = (names, prices) => {
        const seen = new Set();
        const result = [];
        for (let i = 0; i < names.length; i++) {
            const name = cleanFieldName(names[i]);
            const price = prices[i] || '';
            if (!name || name.trim() === '' || name === '-') continue;
            const key = name.toLowerCase().trim();
            if (seen.has(key)) continue;
            seen.add(key);
            result.push({ name: name, price: price });
        }
        return result;
    };
    
    // 월 요금제 검증 및 정리
    const validateMonthlyFee = (fee) => {
        if (!fee) return '-';
        return formatPrice(fee);
    };
    
    const serviceType = order.service_type || '인터넷';
    let serviceTypeDisplay = serviceType;
    if (serviceType === '인터넷+TV') {
        serviceTypeDisplay = '인터넷 + TV 결합';
    } else if (serviceType === '인터넷+TV+핸드폰') {
        serviceTypeDisplay = '인터넷 + TV + 핸드폰 결합';
    }
    
    const cashPairs = cleanNamePricePairs(
        parseJsonField(order.cash_payment_names),
        parseJsonField(order.cash_payment_prices)
    );
    const giftPairs = cleanNamePricePairs(
        parseJsonField(order.gift_card_names),
        parseJsonField(order.gift_card_prices)
    );
    const equipPairs = cleanNamePricePairs(
        parseJsonField(order.equipment_names),
        parseJsonField(order.equipment_prices)
    );
    const installPairs = cleanNamePricePairs(
        parseJsonField(order.installation_names),
        parseJsonField(order.installation_prices)
    );
    
    let html = '';
    
    // 기본 정보 테이블 (판매자 페이지와 동일한 형식)
    html = `
        <table class="product-info-table">
            <tr>
                <th>인터넷 가입처</th>
                <td>${escapeHtml(order.registration_place || '-')}</td>
            </tr>
            <tr>
                <th>결합여부</th>
                <td>${escapeHtml(serviceTypeDisplay)}</td>
            </tr>
            <tr>
                <th>가입 속도</th>
                <td>${escapeHtml(order.speed_option || '-')}</td>
            </tr>
            <tr>
                <th>월 요금제</th>
                <td>${validateMonthlyFee(order.monthly_fee)}</td>
            </tr>
    `;
    
    // 포인트 사용 정보 추가
    if (orderData.used_point && parseInt(orderData.used_point) > 0) {
        const usedPoint = parseInt(orderData.used_point);
        const formattedPoint = usedPoint.toLocaleString('ko-KR');
        html += `
            <tr>
                <th>포인트 사용</th>
                <td style="color: #6366f1; font-weight: 600;">${formattedPoint}원</td>
            </tr>
        `;
        
        // 할인 혜택 내용 표시 (product_snapshot에서 가져오기)
        const pointBenefitDescription = productSnapshot.point_benefit_description || orderData.point_benefit_description || '';
        if (pointBenefitDescription) {
            html += `
                <tr>
                    <th>할인 혜택</th>
                    <td style="color: #10b981; font-weight: 500;">${escapeHtml(pointBenefitDescription)}</td>
                </tr>
            `;
        }
    }
    
    html += `</table>`;
    
    // 현금지급 정보
    if (cashPairs.length > 0) {
        html += `<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">현금지급</h3>`;
        html += `<table class="product-info-table">`;
        cashPairs.forEach((item) => {
            html += `
                <tr>
                    <th>${escapeHtml(item.name || '-')}</th>
                    <td>${formatPrice(item.price)}</td>
                </tr>
            `;
        });
        html += `</table>`;
    }
    
    // 상품권 지급 정보
    if (giftPairs.length > 0) {
        html += `<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">상품권 지급</h3>`;
        html += `<table class="product-info-table">`;
        giftPairs.forEach((item) => {
            html += `
                <tr>
                    <th>${escapeHtml(item.name || '-')}</th>
                    <td>${formatPrice(item.price)}</td>
                </tr>
            `;
        });
        html += `</table>`;
    }
    
    // 장비 제공 정보
    if (equipPairs.length > 0) {
        html += `<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">장비 제공</h3>`;
        html += `<table class="product-info-table">`;
        equipPairs.forEach((item) => {
            html += `
                <tr>
                    <th>${escapeHtml(item.name || '-')}</th>
                    <td>${formatPrice(item.price)}</td>
                </tr>
            `;
        });
        html += `</table>`;
    }
    
    // 설치 및 기타 서비스 정보
    if (installPairs.length > 0) {
        html += `<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">설치 및 기타 서비스</h3>`;
        html += `<table class="product-info-table">`;
        installPairs.forEach((item) => {
            html += `
                <tr>
                    <th>${escapeHtml(item.name || '-')}</th>
                    <td>${formatPrice(item.price)}</td>
                </tr>
            `;
        });
        html += `</table>`;
    }
    
    // 기존 인터넷 회선 정보
    const existingCompany = additionalInfo.currentCompany || additionalInfo.existing_company || additionalInfo.existingCompany || '';
    if (existingCompany) {
        html += `<h3 style="margin-top: 24px; margin-bottom: 12px; font-size: 16px; color: #1f2937;">기존 인터넷 회선</h3>`;
        html += `<table class="product-info-table">`;
        html += `
            <tr>
                <th>기존 인터넷 회선</th>
                <td>${escapeHtml(existingCompany)}</td>
            </tr>
        `;
        html += `</table>`;
    }
    
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
    
    html += '</div>';
    content.innerHTML = html;
    document.getElementById('customerModal').style.display = 'flex';
}

// Alert Modal 함수
function showAlertModal(message, title = '알림') {
    const modal = document.getElementById('alertModal');
    const titleEl = document.getElementById('alertModalTitle');
    const messageEl = document.getElementById('alertModalMessage');
    
    if (modal && titleEl && messageEl) {
        titleEl.textContent = title;
        messageEl.textContent = message;
        modal.style.display = 'flex';
    }
}

function closeAlertModal() {
    const modal = document.getElementById('alertModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal('sellerModal');
        closeModal('productModal');
        closeModal('customerModal');
        closeStatusEditModal();
        closeAlertModal();
    }
});

// Alert Modal 클릭 시 닫기
const alertModal = document.getElementById('alertModal');
if (alertModal) {
    alertModal.addEventListener('click', function(event) {
        if (event.target === alertModal) {
            closeAlertModal();
        }
    });
}

// 전체 선택/해제
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.order-checkbox-item');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateBulkActions();
}

// 선택된 주문 ID 목록 가져오기
function getSelectedOrderIds() {
    const checkboxes = document.querySelectorAll('.order-checkbox-item:checked');
    return Array.from(checkboxes).map(cb => parseInt(cb.value)).filter(id => !isNaN(id) && id > 0);
}

// 일괄 변경 UI 업데이트
function updateBulkActions() {
    const selectedIds = getSelectedOrderIds();
    const selectedCount = selectedIds.length;
    const bulkActions = document.getElementById('bulkActions');
    const selectedCountSpan = document.getElementById('selectedCount');
    const bulkUpdateBtn = document.getElementById('bulkUpdateBtn');
    const bulkStatusSelect = document.getElementById('bulkStatusSelect');
    const selectAllCheckbox = document.getElementById('selectAll');
    
    if (selectedCountSpan) {
        selectedCountSpan.textContent = selectedCount;
    }
    
    if (bulkActions) {
        bulkActions.style.display = selectedCount > 0 ? 'flex' : 'none';
    }
    
    if (bulkUpdateBtn) {
        bulkUpdateBtn.disabled = selectedCount === 0 || !bulkStatusSelect || !bulkStatusSelect.value;
    }
    
    // 전체 선택 체크박스 상태 업데이트
    if (selectAllCheckbox) {
        const allCheckboxes = document.querySelectorAll('.order-checkbox-item');
        const checkedCount = document.querySelectorAll('.order-checkbox-item:checked').length;
        selectAllCheckbox.checked = allCheckboxes.length > 0 && checkedCount === allCheckboxes.length;
        selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < allCheckboxes.length;
    }
}

// 상태 변경 모달 열기
function openStatusEditModal(applicationId, currentStatus) {
    const modal = document.getElementById('statusEditModal');
    const select = document.getElementById('statusEditSelect');
    
    if (!modal || !select) return;
    
    // 현재 상태 정규화 및 기본값 설정
    let status = 'received';
    if (currentStatus) {
        const normalizedStatus = String(currentStatus).trim().toLowerCase();
        if (normalizedStatus !== '') {
            status = (normalizedStatus === 'pending') ? 'received' : normalizedStatus;
        }
    }
    
    // 셀렉트박스에 값 설정
    const validStatuses = ['received', 'on_hold', 'cancelled', 'installation_completed', 'closed'];
    if (validStatuses.includes(status)) {
        select.value = status;
    } else {
        select.value = 'received';
    }
    
    select.setAttribute('data-application-id', applicationId);
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
            showAlertModal('상태가 변경되었습니다.', '성공');
            setTimeout(() => {
                location.reload();
            }, 500);
        } else {
            showAlertModal(data.message || '상태 변경에 실패했습니다.', '오류');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlertModal('상태 변경 중 오류가 발생했습니다.', '오류');
    });
}

// 일괄 상태 변경
function bulkUpdateStatus() {
    const selectedIds = getSelectedOrderIds();
    const statusSelect = document.getElementById('bulkStatusSelect');
    
    if (selectedIds.length === 0) {
        showAlertModal('선택된 주문이 없습니다.');
        return;
    }
    
    if (!statusSelect || !statusSelect.value) {
        showAlertModal('변경할 진행상황을 선택해주세요.');
        return;
    }
    
    const newStatus = statusSelect.value;
    const statusLabels = {
        'received': '접수',
        'on_hold': '보류',
        'cancelled': '취소',
        'installation_completed': '설치완료',
        'closed': '종료'
    };
    
    if (!confirm(`선택한 ${selectedIds.length}개의 주문을 "${statusLabels[newStatus] || newStatus}"로 변경하시겠습니까?`)) {
        return;
    }
    
    // 일괄 변경 API 호출
    const promises = selectedIds.map(id => {
        return fetch('/MVNO/api/update-order-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `application_id=${id}&status=${encodeURIComponent(newStatus)}`
        })
        .then(response => response.json())
        .then(data => ({ id, success: data.success, message: data.message }));
    });
    
    // 모든 요청 완료 대기
    Promise.all(promises)
        .then(results => {
            const successCount = results.filter(r => r.success).length;
            const failCount = results.length - successCount;
            
            if (failCount === 0) {
                showAlertModal(`${successCount}개의 주문 상태가 변경되었습니다.`, '성공');
                setTimeout(() => {
                    location.reload();
                }, 500);
            } else {
                showAlertModal(`${successCount}개 성공, ${failCount}개 실패했습니다.`, '알림');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        })
        .catch(error => {
            console.error('Bulk update error:', error);
            showAlertModal('일괄 변경 중 오류가 발생했습니다: ' + error.message, '오류');
        });
}

// 초기화
document.addEventListener('DOMContentLoaded', function() {
    // 체크박스 이벤트 리스너 추가
    const checkboxes = document.querySelectorAll('.order-checkbox-item');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkActions();
        });
    });
    
    // 일괄 변경 셀렉트박스 변경 이벤트
    const bulkStatusSelect = document.getElementById('bulkStatusSelect');
    if (bulkStatusSelect) {
        bulkStatusSelect.addEventListener('change', function() {
            const bulkUpdateBtn = document.getElementById('bulkUpdateBtn');
            if (bulkUpdateBtn) {
                bulkUpdateBtn.disabled = !this.value || getSelectedOrderIds().length === 0;
            }
        });
    }
    
    // 상태 변경 모달 이벤트
    const statusModal = document.getElementById('statusEditModal');
    if (statusModal) {
        statusModal.addEventListener('click', function(event) {
            if (event.target === statusModal) {
                closeStatusEditModal();
            }
        });
    }
    
    // 초기 상태 업데이트
    updateBulkActions();
});
</script>

<!-- 상태 변경 모달 -->
<div id="statusEditModal" class="status-modal-overlay">
    <div class="status-modal">
        <div class="status-modal-header">
            <h3>진행상황 변경</h3>
            <button class="status-modal-close" onclick="closeStatusEditModal()">&times;</button>
        </div>
        <div class="status-modal-body">
            <label for="statusEditSelect" class="status-modal-label">진행상황 선택</label>
            <select id="statusEditSelect" class="status-modal-select">
                <option value="received">접수</option>
                <option value="on_hold">보류</option>
                <option value="cancelled">취소</option>
                <option value="installation_completed">설치완료</option>
                <option value="closed">종료</option>
            </select>
        </div>
        <div class="status-modal-footer">
            <button class="status-modal-btn status-modal-btn-cancel" onclick="closeStatusEditModal()">취소</button>
            <button class="status-modal-btn status-modal-btn-confirm" onclick="updateOrderStatus()">확인</button>
        </div>
    </div>
</div>

<!-- Alert Modal -->
<div id="alertModal" class="alert-modal-overlay">
    <div class="alert-modal">
        <div class="alert-modal-header">
            <h3 id="alertModalTitle">알림</h3>
            <button class="alert-modal-close" onclick="closeAlertModal()">&times;</button>
        </div>
        <div class="alert-modal-body" id="alertModalMessage">
        </div>
        <div class="alert-modal-footer">
            <button class="alert-modal-btn alert-modal-btn-confirm" onclick="closeAlertModal()">확인</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>












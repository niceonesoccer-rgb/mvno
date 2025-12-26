<?php
/**
 * 통신사단독유심 주문 관리 페이지
 * 경로: /seller/orders/mno-sim.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/product-functions.php';

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
$dateRange = $_GET['date_range'] ?? '7'; // 기본값 7일
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
            "a.product_type = 'mno-sim'"
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
        // MVNO 페이지와 동일하게 products 조인 없이 카운트만 수행
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
        
        // 주문 목록 조회
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
                p.id as product_table_id,
                mno_sim.plan_name,
                mno_sim.provider,
                mno_sim.service_type,
                mno_sim.contract_period,
                mno_sim.contract_period_discount_value,
                mno_sim.contract_period_discount_unit,
                mno_sim.price_main,
                mno_sim.price_after,
                mno_sim.data_amount,
                mno_sim.data_amount_value,
                mno_sim.data_unit,
                mno_sim.data_additional,
                mno_sim.data_additional_value,
                mno_sim.data_exhausted,
                mno_sim.data_exhausted_value,
                mno_sim.call_type,
                mno_sim.call_amount,
                mno_sim.additional_call_type,
                mno_sim.additional_call,
                mno_sim.sms_type,
                mno_sim.sms_amount,
                mno_sim.mobile_hotspot,
                mno_sim.mobile_hotspot_value,
                mno_sim.regular_sim_available,
                mno_sim.regular_sim_price,
                mno_sim.nfc_sim_available,
                mno_sim.nfc_sim_price,
                mno_sim.esim_available,
                mno_sim.esim_price,
                mno_sim.over_data_price,
                mno_sim.over_voice_price,
                mno_sim.over_video_price,
                mno_sim.over_sms_price,
                mno_sim.over_lms_price,
                mno_sim.over_mms_price,
                mno_sim.promotion_title,
                mno_sim.promotions,
                mno_sim.benefits
            FROM product_applications a
            INNER JOIN application_customers c ON a.id = c.application_id
            INNER JOIN products p ON a.product_id = p.id
            LEFT JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
            WHERE $whereClause
            ORDER BY a.created_at DESC, a.id DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        // 디버깅: 바인딩된 파라미터 확인
        error_log("MNO-SIM Orders Query - Bound limit: " . $perPage . " (type: " . gettype($perPage) . ")");
        error_log("MNO-SIM Orders Query - Bound offset: " . $offset . " (type: " . gettype($offset) . ")");
        
        // 디버깅: 쿼리 실행 전 파라미터 확인
        error_log("MNO-SIM Orders Query - About to execute with params: " . json_encode($params, JSON_UNESCAPED_UNICODE));
        error_log("MNO-SIM Orders Query - Limit: " . $perPage . ", Offset: " . $offset);
        
        try {
            $execResult = $stmt->execute();
            error_log("MNO-SIM Orders Query - Execute result: " . ($execResult ? 'SUCCESS' : 'FAILED'));
            if (!$execResult) {
                $errorInfo = $stmt->errorInfo();
                error_log("MNO-SIM Orders Query - Execute failed. Error Info: " . json_encode($errorInfo, JSON_UNESCAPED_UNICODE));
                $orders = [];
            } else {
                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("MNO-SIM Orders Query - Fetched " . count($orders) . " rows");
                if (count($orders) > 0) {
                    error_log("MNO-SIM Orders Query - First order before normalization: " . json_encode($orders[0], JSON_UNESCAPED_UNICODE));
                }
            }
        } catch (PDOException $e) {
            error_log("MNO-SIM Orders Query - PDO Exception: " . $e->getMessage());
            error_log("MNO-SIM Orders Query - Error Code: " . $e->getCode());
            error_log("MNO-SIM Orders Query - SQL State: " . $e->getCode());
            error_log("MNO-SIM Orders Query - Trace: " . $e->getTraceAsString());
            $orders = [];
        } catch (Exception $e) {
            error_log("MNO-SIM Orders Query - General Exception: " . $e->getMessage());
            error_log("MNO-SIM Orders Query - Trace: " . $e->getTraceAsString());
            $orders = [];
        }
        
        // 디버깅: 정규화 전 orders 상태 확인
        error_log("MNO-SIM Orders Query - Orders count before normalization: " . count($orders));
        
        // 디버깅: 쿼리 결과 로그
        error_log("MNO-SIM Orders Query - Total results: " . count($orders));
        error_log("MNO-SIM Orders Query - SQL: " . $sql);
        error_log("MNO-SIM Orders Query - Orders array type: " . gettype($orders));
        error_log("MNO-SIM Orders Query - Orders empty check: " . (empty($orders) ? 'TRUE' : 'FALSE'));
        
        // 실제 쿼리와 동일한 조건으로 직접 테스트
        if (count($orders) == 0) {
            error_log("MNO-SIM Orders Query - Testing with same SQL but direct LIMIT values...");
            $testSqlDirect = str_replace(['LIMIT :limit OFFSET :offset'], ["LIMIT {$perPage} OFFSET {$offset}"], $sql);
            $testStmtDirect = $pdo->prepare($testSqlDirect);
            foreach ($params as $key => $value) {
                $testStmtDirect->bindValue($key, $value);
            }
            $testStmtDirect->execute();
            $testOrdersDirect = $testStmtDirect->fetchAll(PDO::FETCH_ASSOC);
            error_log("MNO-SIM Orders Query - Direct LIMIT test result: " . count($testOrdersDirect) . " rows");
            if (count($testOrdersDirect) > 0) {
                error_log("MNO-SIM Orders Query - Direct LIMIT test first order: " . json_encode($testOrdersDirect[0], JSON_UNESCAPED_UNICODE));
                // 직접 LIMIT으로 결과가 나오면 orders에 할당
                $orders = $testOrdersDirect;
                error_log("MNO-SIM Orders Query - Orders updated from direct LIMIT test");
            }
        }
        if (count($orders) > 0) {
            error_log("MNO-SIM Orders Query - First order keys: " . implode(', ', array_keys($orders[0])));
        } else {
            // products 조인 없이 테스트
            $testSql = "
                SELECT DISTINCT
                    a.id as application_id,
                    a.order_number,
                    a.product_id
                FROM product_applications a
                INNER JOIN application_customers c ON a.id = c.application_id
                WHERE $whereClause
                LIMIT 1
            ";
            $testStmt = $pdo->prepare($testSql);
            foreach ($params as $key => $value) {
                $testStmt->bindValue($key, $value);
            }
            $testStmt->execute();
            $testOrders = $testStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("MNO-SIM Orders Query - Test without products join: " . count($testOrders) . " results");
            if (count($testOrders) > 0) {
                error_log("MNO-SIM Orders Query - Test order product_id: " . ($testOrders[0]['product_id'] ?? 'NULL'));
                // 해당 product_id가 products 테이블에 있는지 확인
                $productCheckStmt = $pdo->prepare("SELECT id FROM products WHERE id = :product_id");
                $productCheckStmt->execute([':product_id' => $testOrders[0]['product_id']]);
                $productExists = $productCheckStmt->fetch(PDO::FETCH_ASSOC);
                error_log("MNO-SIM Orders Query - Product exists in products table: " . ($productExists ? 'YES' : 'NO'));
            }
        }
        
        if (count($orders) > 0) {
            error_log("MNO-SIM Orders Query - First order: " . json_encode($orders[0], JSON_UNESCAPED_UNICODE));
        } else {
            // 결과가 없을 때 원인 파악을 위한 추가 쿼리
            error_log("MNO-SIM Orders Query - No results found. Debugging...");
            
            // 1. 기본 카운트 (조인 없이)
            $debugStmt = $pdo->prepare("
                SELECT COUNT(*) as cnt FROM product_applications 
                WHERE seller_id = :seller_id AND product_type = 'mno-sim'
            ");
            $debugStmt->execute([':seller_id' => $sellerId]);
            $debugCount = $debugStmt->fetch(PDO::FETCH_ASSOC);
            error_log("MNO-SIM Orders Query - Debug 1: mno-sim applications count (no join): " . ($debugCount['cnt'] ?? 0));
            
            // 2. application_customers 조인 확인
            $debugStmt2 = $pdo->prepare("
                SELECT COUNT(*) as cnt 
                FROM product_applications a
                INNER JOIN application_customers c ON a.id = c.application_id
                WHERE a.seller_id = :seller_id AND a.product_type = 'mno-sim'
            ");
            $debugStmt2->execute([':seller_id' => $sellerId]);
            $debugCount2 = $debugStmt2->fetch(PDO::FETCH_ASSOC);
            error_log("MNO-SIM Orders Query - Debug 2: with customers join count: " . ($debugCount2['cnt'] ?? 0));
            
            // 3. products 조인 확인
            $debugStmt3 = $pdo->prepare("
                SELECT COUNT(*) as cnt 
                FROM product_applications a
                INNER JOIN application_customers c ON a.id = c.application_id
                INNER JOIN products p ON a.product_id = p.id
                WHERE a.seller_id = :seller_id AND a.product_type = 'mno-sim'
            ");
            $debugStmt3->execute([':seller_id' => $sellerId]);
            $debugCount3 = $debugStmt3->fetch(PDO::FETCH_ASSOC);
            error_log("MNO-SIM Orders Query - Debug 3: with products join count: " . ($debugCount3['cnt'] ?? 0));
            
            // 4. 날짜 필터 확인
            if ($dateFrom && $dateTo) {
                $debugStmt4 = $pdo->prepare("
                    SELECT COUNT(*) as cnt 
                    FROM product_applications a
                    INNER JOIN application_customers c ON a.id = c.application_id
                    INNER JOIN products p ON a.product_id = p.id
                    WHERE a.seller_id = :seller_id 
                    AND a.product_type = 'mno-sim'
                    AND DATE(a.created_at) >= :date_from
                    AND DATE(a.created_at) <= :date_to
                ");
                $debugStmt4->execute([
                    ':seller_id' => $sellerId,
                    ':date_from' => $dateFrom,
                    ':date_to' => $dateTo
                ]);
                $debugCount4 = $debugStmt4->fetch(PDO::FETCH_ASSOC);
                error_log("MNO-SIM Orders Query - Debug 4: with date filter count: " . ($debugCount4['cnt'] ?? 0));
                error_log("MNO-SIM Orders Query - Debug 4: date_from: " . $dateFrom . ", date_to: " . $dateTo);
            }
            
            // 5. 실제 신청 날짜 확인
            $debugStmt5 = $pdo->prepare("
                SELECT a.id, a.order_number, a.created_at, DATE(a.created_at) as created_date
                FROM product_applications a
                WHERE a.seller_id = :seller_id AND a.product_type = 'mno-sim'
                ORDER BY a.created_at DESC
                LIMIT 5
            ");
            $debugStmt5->execute([':seller_id' => $sellerId]);
            $debugDates = $debugStmt5->fetchAll(PDO::FETCH_ASSOC);
            error_log("MNO-SIM Orders Query - Debug 5: recent applications dates: " . json_encode($debugDates, JSON_UNESCAPED_UNICODE));
        }
        
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
            
            foreach (['promotions', 'benefits'] as $field) {
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

// 상태별 한글명 (공통 함수 사용)
// getApplicationStatusLabel() 함수가 product-functions.php에 정의되어 있음
// 기존 코드와의 호환성을 위해 $statusLabels를 공통 함수를 호출하는 방식으로 변경
// 배열 대신 함수를 사용하도록 변경

// 가입형태 표시 함수 사용 (판매자용)
require_once __DIR__ . '/../../includes/data/contract-type-functions.php';

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

<!-- 디버깅 정보 -->
<?php if (isset($_GET['debug'])): ?>
<div style="background: #fff3cd; border: 2px solid #ffc107; padding: 15px; margin: 20px; border-radius: 8px; font-family: monospace; font-size: 12px;">
    <h3 style="margin-top: 0; color: #856404;">🔍 디버깅 정보</h3>
    
    <div style="margin-bottom: 15px;">
        <strong>쿼리 정보:</strong><br>
        <span style="color: #856404;">seller_id:</span> <?php echo htmlspecialchars($sellerId ?? 'N/A'); ?><br>
        <span style="color: #856404;">dateRange:</span> <?php echo htmlspecialchars($dateRange ?? 'N/A'); ?><br>
        <span style="color: #856404;">dateFrom:</span> <?php echo htmlspecialchars($dateFrom ?? 'N/A'); ?><br>
        <span style="color: #856404;">dateTo:</span> <?php echo htmlspecialchars($dateTo ?? 'N/A'); ?><br>
        <span style="color: #856404;">status:</span> <?php echo htmlspecialchars($status ?? 'N/A'); ?><br>
        <span style="color: #856404;">totalOrders:</span> <?php echo $totalOrders; ?><br>
        <span style="color: #856404;">orders count:</span> <?php echo count($orders); ?><br>
        <span style="color: #856404;">orders empty check:</span> <?php echo empty($orders) ? 'TRUE (empty)' : 'FALSE (has data)'; ?><br>
        <span style="color: #856404;">orders is_array:</span> <?php echo is_array($orders) ? 'YES' : 'NO'; ?><br>
        <span style="color: #856404;">page:</span> <?php echo $page ?? 1; ?><br>
        <span style="color: #856404;">perPage:</span> <?php echo $perPage ?? 10; ?><br>
        <span style="color: #856404;">offset:</span> <?php echo isset($page, $perPage) ? (($page - 1) * $perPage) : 'N/A'; ?><br>
    </div>
    
    <?php 
    // 실제 쿼리 재실행하여 결과 확인
    if (isset($pdo) && isset($sellerId) && isset($whereClause) && isset($params)) {
        try {
            $debugSql = "
                SELECT DISTINCT
                    a.id as application_id,
                    a.order_number,
                    a.product_id,
                    a.application_status,
                    a.created_at,
                    c.name,
                    c.phone,
                    c.email
                FROM product_applications a
                INNER JOIN application_customers c ON a.id = c.application_id
                INNER JOIN products p ON a.product_id = p.id
                LEFT JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
                WHERE $whereClause
                ORDER BY a.created_at DESC, a.id DESC
                LIMIT 5
            ";
            $debugStmt = $pdo->prepare($debugSql);
            foreach ($params as $key => $value) {
                $debugStmt->bindValue($key, $value);
            }
            $debugStmt->execute();
            $debugOrders = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div style="margin-bottom: 15px; padding: 10px; background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 4px;">
        <strong style="color: #004085;">🔍 실시간 쿼리 테스트:</strong><br>
        <span style="color: #004085;">디버그 쿼리 결과: </span><?php echo count($debugOrders); ?>개<br>
        <?php if (count($debugOrders) > 0): ?>
        <div style="margin-top: 10px;">
            <strong>첫 번째 결과:</strong><br>
            <pre style="background: white; padding: 10px; border: 1px solid #b3d9ff; border-radius: 4px; overflow-x: auto; font-size: 11px; max-height: 200px; overflow-y: auto;"><?php echo htmlspecialchars(json_encode($debugOrders[0], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>
        </div>
        <?php else: ?>
        <div style="color: #721c24; margin-top: 10px;">
            ⚠️ 디버그 쿼리도 결과가 없습니다. WHERE 조건을 확인하세요.<br>
            <strong>WHERE 절:</strong> <?php echo htmlspecialchars($whereClause); ?><br>
            <strong>파라미터:</strong> <?php echo htmlspecialchars(json_encode($params, JSON_UNESCAPED_UNICODE)); ?>
        </div>
        <?php endif; ?>
    </div>
    <?php 
        } catch (Exception $e) {
    ?>
    <div style="margin-bottom: 15px; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">
        <strong style="color: #721c24;">❌ 디버그 쿼리 실행 오류:</strong><br>
        <?php echo htmlspecialchars($e->getMessage()); ?>
    </div>
    <?php 
        }
    }
    ?>
    
    <?php if (is_array($orders) && count($orders) > 0): ?>
    <div style="margin-bottom: 15px; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">
        <strong style="color: #155724;">✅ 주문 데이터가 있습니다!</strong><br>
        <span style="color: #155724;">첫 번째 주문 데이터:</span><br>
        <pre style="background: white; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; overflow-x: auto; font-size: 11px;"><?php echo htmlspecialchars(json_encode($orders[0], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>
    </div>
    <?php else: ?>
    <div style="margin-bottom: 15px; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">
        <strong style="color: #721c24;">❌ 주문 데이터가 없습니다!</strong><br>
        <span style="color: #721c24;">orders 변수 타입: </span><?php echo gettype($orders); ?><br>
        <span style="color: #721c24;">orders 값: </span><?php echo var_export($orders, true); ?><br>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($orders)): ?>
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
    <?php else: ?>
    <div style="color: #d63031; font-weight: bold;">
        ⚠️ 주문이 없습니다. 에러 로그를 확인하세요.
    </div>
    <?php endif; ?>
    
    <p style="margin: 10px 0 0 0; color: #856404;">
        <strong>참고:</strong> URL에 <code>?debug=1</code>을 추가하면 이 정보가 표시됩니다.<br>
        에러 로그 위치: <code>C:\xampp\apache\logs\error.log</code> 또는 <code>C:\xampp\php\logs\php_error_log</code>
    </p>
</div>
<?php endif; ?>

<div class="orders-container">
    <div class="orders-header">
        <h1>통신사단독유심 주문 관리</h1>
        <p>통신사단독유심 상품 주문 내역을 확인하고 관리하세요</p>
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
                    <select name="status" id="status_select" class="filter-select">
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
                        <th>통신사</th>
                        <th>상품명</th>
                        <th>가입형태</th>
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
                            <td><?php echo htmlspecialchars($order['provider'] ?? '-'); ?></td>
                            <td>
                                <span class="product-name-link" onclick="showProductInfo(<?php echo htmlspecialchars(json_encode($order)); ?>, 'mvno')">
                                    <?php echo htmlspecialchars($order['plan_name'] ?? '상품명 없음'); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                echo getContractTypeForAdmin($order);
                                ?>
                            </td>
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
                                        <?php echo getApplicationStatusLabel($order['application_status']); ?>
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
            <?php if ($totalPages > 1): 
                $paginationParams = array_filter($_GET, fn($v, $k) => $k !== 'status' || $v !== '', ARRAY_FILTER_USE_BOTH);
            ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($paginationParams, ['page' => $page - 1])); ?>">이전</a>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?<?php echo http_build_query(array_merge($paginationParams, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?php echo http_build_query(array_merge($paginationParams, ['page' => $page + 1])); ?>">다음</a>
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
    const statusSelect = document.getElementById('status_select');
    const filterForm = document.querySelector('.orders-filters form');
    
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
        [dateFromInput, dateToInput].forEach(input => {
            input.addEventListener('change', () => {
                if (input.value) dateRangeSelect.value = 'all';
            });
        });
    }
    
    if (statusSelect) {
        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('status')) statusSelect.value = '';
        
        if (filterForm) {
            filterForm.addEventListener('submit', () => {
                if (!statusSelect.value) statusSelect.removeAttribute('name');
            });
        }
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
        
        if (productType === 'mno-sim') {
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
            
            // 가입 형태 (판매자용: 신규, 번이, 기변)
            const subscriptionType = additionalInfo.subscription_type || '';
            const subscriptionTypeLabel = subscriptionType === 'new' ? '신규' : 
                                         (subscriptionType === 'mnp' || subscriptionType === 'port') ? '번이' : 
                                         subscriptionType === 'change' ? '기변' : 
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
            if (subscriptionType === 'mnp' || subscriptionType === 'port' || (order.contract_period && order.contract_period.includes('번호이동'))) subscriptionTypes.push('번이');
            if (subscriptionType === 'change' || (order.contract_period && order.contract_period.includes('기기변경'))) subscriptionTypes.push('기변');
            const subscriptionTypesLabel = subscriptionTypes.length > 0 ? subscriptionTypes.join(', ') : subscriptionTypeLabel;
            
            // 데이터 제공량 (저장된 값 그대로 표시 - 단위 포함 가능)
            const dataAmount = getValue('data_amount', 'data_amount');
            const dataAmountValue = getValue('data_amount_value', 'data_amount_value');
            const dataUnit = getValue('data_unit', 'data_unit');
            let dataAmountLabel = '-';
            if (dataAmount === '직접입력' && dataAmountValue && dataAmountValue !== '-') {
                // 직접입력인 경우 저장된 값 그대로 표시 (단위가 이미 포함되어 있을 수 있음)
                // "월" 접두사가 없으면 추가
                if (dataAmountValue.toLowerCase().includes('월') || dataAmountValue.toLowerCase().includes('month')) {
                    dataAmountLabel = dataAmountValue;
                } else {
                    dataAmountLabel = '월 ' + dataAmountValue;
                }
            } else if (dataAmount && dataAmount !== '-' && dataAmount !== '직접입력') {
                dataAmountLabel = '월 ' + dataAmount;
            }
            
            // 데이터 추가제공 (저장된 값 그대로 표시)
            const dataAdditional = getValue('data_additional', 'data_additional');
            const dataAdditionalValue = getValue('data_additional_value', 'data_additional_value');
            let dataAdditionalLabel = '-';
            if (dataAdditional === '직접입력' && dataAdditionalValue) {
                // 소문자 단위를 대문자로 변환 (10gb -> 10GB)
                let displayValue = dataAdditionalValue;
                displayValue = displayValue.replace(/gb/gi, 'GB').replace(/mb/gi, 'MB').replace(/tb/gi, 'TB');
                dataAdditionalLabel = displayValue;
            } else if (dataAdditional && dataAdditional !== '없음') {
                dataAdditionalLabel = dataAdditional;
            } else {
                dataAdditionalLabel = '없음';
            }
            
            // 데이터 소진시 (저장된 값 그대로 표시)
            const dataExhausted = getValue('data_exhausted', 'data_exhausted');
            const dataExhaustedValue = getValue('data_exhausted_value', 'data_exhausted_value');
            let dataExhaustedLabel = '-';
            if (dataExhausted === '직접입력' && dataExhaustedValue && dataExhaustedValue !== '-') {
                // 직접입력인 경우 저장된 값 그대로 표시 (단위 포함 가능)
                dataExhaustedLabel = dataExhaustedValue;
            } else if (dataExhausted && dataExhausted !== '-' && dataExhausted !== '직접입력') {
                dataExhaustedLabel = dataExhausted;
            }
            
            // 통화 (저장된 값 그대로 표시 - 단위 포함 가능)
            const callType = getValue('call_type', 'call_type');
            const callAmount = getValue('call_amount', 'call_amount');
            let callLabel = '-';
            if (callType) {
                if (callAmount && callAmount !== '-') {
                    // DB에 저장된 값이 "100분" 형식이면 그대로 표시
                    // 숫자만 있으면 "분" 단위 추가
                    let displayAmount = callAmount;
                    if (/^\d+$/.test(callAmount)) {
                        displayAmount = callAmount + '분';
                    }
                    const cleanedType = callType === '직접입력' ? '' : callType;
                    callLabel = cleanedType ? (cleanedType + ' ' + displayAmount) : displayAmount;
                } else {
                    callLabel = callType === '직접입력' ? '-' : callType;
                }
            }
            
            // 부가통화 (저장된 값 그대로 표시 - 단위 포함 가능)
            const additionalCallType = getValue('additional_call_type', 'additional_call_type');
            const additionalCall = getValue('additional_call', 'additional_call');
            let additionalCallLabel = '-';
            if (additionalCallType) {
                if (additionalCall && additionalCall !== '-') {
                    // DB에 저장된 값이 "100분" 형식이면 그대로 표시
                    // 숫자만 있으면 "분" 단위 추가
                    let displayAmount = additionalCall;
                    if (/^\d+$/.test(additionalCall)) {
                        displayAmount = additionalCall + '분';
                    }
                    const cleanedType = additionalCallType === '직접입력' ? '' : additionalCallType;
                    additionalCallLabel = cleanedType ? (cleanedType + ' ' + displayAmount) : displayAmount;
                } else {
                    additionalCallLabel = additionalCallType === '직접입력' ? '-' : additionalCallType;
                }
            }
            
            // 문자 (저장된 값 그대로 표시 - 단위 포함 가능)
            const smsType = getValue('sms_type', 'sms_type');
            const smsAmount = getValue('sms_amount', 'sms_amount');
            let smsLabel = '-';
            if (smsType) {
                if (smsAmount && smsAmount !== '-') {
                    // "직접입력" 텍스트 제거 후 값 표시 (단위가 포함되어 있을 수 있음)
                    const cleanedType = smsType === '직접입력' ? '' : smsType;
                    smsLabel = cleanedType ? (cleanedType + ' ' + smsAmount) : smsAmount;
                } else {
                    smsLabel = smsType === '직접입력' ? '-' : smsType;
                }
            }
            
            // 테더링(핫스팟) (저장된 값 그대로 표시)
            const mobileHotspot = getValue('mobile_hotspot', 'mobile_hotspot');
            const mobileHotspotValue = getValue('mobile_hotspot_value', 'mobile_hotspot_value');
            let mobileHotspotLabel = '-';
            if (mobileHotspot === '직접선택' && mobileHotspotValue && mobileHotspotValue !== '-') {
                // DB에 저장된 값이 "20GB" 형식이면 그대로 표시
                // 소문자 단위를 대문자로 변환 (20gb -> 20GB)
                let displayValue = mobileHotspotValue;
                displayValue = displayValue.replace(/gb/gi, 'GB').replace(/mb/gi, 'MB').replace(/tb/gi, 'TB');
                mobileHotspotLabel = displayValue;
            } else if (mobileHotspot && mobileHotspot !== '-' && mobileHotspot !== '직접선택') {
                mobileHotspotLabel = mobileHotspot;
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
            
            // 기본 제공 초과 시 (DB에 저장된 값이 "22.53원/MB" 형식이면 그대로 표시)
            const formatOverPrice = (price, defaultUnit) => {
                if (!price || price === '-' || price === '' || price === null) return null;
                // DB에 저장된 값이 이미 단위가 포함된 형식이면 그대로 표시
                if (/[\d.]+[가-힣/]+/.test(price)) {
                    // 숫자와 한글/슬래시가 함께 있는 경우 (예: "22.53원/MB", "1.98원/초")
                    const match = price.match(/^([\d.]+)(.+)$/);
                    if (match) {
                        const num = parseFloat(match[1]);
                        if (!isNaN(num)) {
                            return num.toLocaleString('ko-KR') + match[2];
                        }
                    }
                    return price;
                }
                // 숫자만 있는 경우 기본 단위 추가
                const numValue = String(price).replace(/[^0-9.]/g, '');
                if (!numValue || numValue === '') return null;
                const num = parseFloat(numValue);
                if (isNaN(num)) return null;
                return num.toLocaleString('ko-KR') + defaultUnit;
            };
            
            const overDataPriceRaw = getValue('over_data_price', 'over_data_price');
            const overVoicePriceRaw = getValue('over_voice_price', 'over_voice_price');
            const overVideoPriceRaw = getValue('over_video_price', 'over_video_price');
            const overSmsPriceRaw = getValue('over_sms_price', 'over_sms_price');
            const overLmsPriceRaw = getValue('over_lms_price', 'over_lms_price');
            const overMmsPriceRaw = getValue('over_mms_price', 'over_mms_price');
            
            // DB에 저장된 값 그대로 표시 (단위가 포함되어 있으면 그대로, 없으면 기본 단위 추가)
            const overDataPrice = formatOverPrice(overDataPriceRaw, '원/MB');
            const overVoicePrice = formatOverPrice(overVoicePriceRaw, '원/초');
            const overVideoPrice = formatOverPrice(overVideoPriceRaw, '원/초');
            const overSmsPrice = formatOverPrice(overSmsPriceRaw, '원/건');
            const overLmsPrice = formatOverPrice(overLmsPriceRaw, '원/건');
            const overMmsPrice = formatOverPrice(overMmsPriceRaw, '원/건');
            
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
            
            // 값이 '-'가 아니고 null이 아닌 경우에만 행 추가하는 헬퍼 함수
            const addRowIfNotDash = (rows, label, value) => {
                if (value && value !== '-' && value !== null && value !== '') {
                    rows.push(`<tr><th>${label}</th><td>${value}</td></tr>`);
                }
            };
            
            // 가격 정보 처리
            const priceMain = getValue('price_main', 'price_main');
            const priceAfter = getValue('price_after', 'price_after');
            const discountPeriod = getValue('discount_period', 'discount_period');
            
            let priceMainLabel = '-';
            if (priceMain && priceMain !== '-' && priceMain !== null && priceMain !== '') {
                const priceNum = parseFloat(String(priceMain).replace(/[^0-9.]/g, ''));
                if (!isNaN(priceNum)) {
                    priceMainLabel = '월 ' + number_format(priceNum) + '원';
                } else {
                    priceMainLabel = priceMain;
                }
            }
            
            let priceAfterLabel = '-';
            if (priceAfter && priceAfter !== '-' && priceAfter !== null && priceAfter !== '') {
                const priceNum = parseFloat(String(priceAfter).replace(/[^0-9.]/g, ''));
                if (!isNaN(priceNum)) {
                    priceAfterLabel = '월 ' + number_format(priceNum) + '원';
                } else {
                    priceAfterLabel = priceAfter;
                }
            }
            
            // 기본 정보 섹션
            let basicInfoRows = [];
            if (order.plan_name && order.plan_name !== '-') {
                basicInfoRows.push(`<tr><th>요금제 이름</th><td>${order.plan_name}</td></tr>`);
            }
            addRowIfNotDash(basicInfoRows, '통신사 약정', contractPeriodLabel);
            addRowIfNotDash(basicInfoRows, '통신망', providerLabel);
            addRowIfNotDash(basicInfoRows, '통신 기술', serviceTypeLabel);
            addRowIfNotDash(basicInfoRows, '가입 형태', subscriptionTypesLabel);
            addRowIfNotDash(basicInfoRows, '기본 요금', priceMainLabel);
            addRowIfNotDash(basicInfoRows, '할인 후 요금', priceAfterLabel);
            addRowIfNotDash(basicInfoRows, '할인 기간', discountPeriod);
            
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
            addRowIfNotDash(overLimitRows, '텍스트형(LMS)', overLmsPrice);
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


<?php
/**
 * 상품 검색 API
 * 이벤트 등록 시 상품 검색용
 * 상품명, 판매자 아이디, 판매자명으로 검색 가능
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';

// 관리자 권한 체크
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
    exit;
}

// 검색 파라미터
$productType = trim($_GET['product_type'] ?? '');

// 판매자 검색 파라미터
$sellerId = trim($_GET['seller_id'] ?? '');
$sellerName = trim($_GET['seller_name'] ?? '');

// 카테고리별 필터 파라미터
$provider = trim($_GET['provider'] ?? '');
$contractPeriod = trim($_GET['contract_period'] ?? '');
$priceMin = trim($_GET['price_min'] ?? '');
$priceMax = trim($_GET['price_max'] ?? '');
$serviceType = trim($_GET['service_type'] ?? '');
$deviceName = trim($_GET['device_name'] ?? '');
$deliveryMethod = trim($_GET['delivery_method'] ?? '');
$registrationPlace = trim($_GET['registration_place'] ?? '');
$speedOption = trim($_GET['speed_option'] ?? '');

// 카테고리 유효성 검사
$validProductTypes = ['mvno', 'mno', 'mno_sim', 'internet'];
if (empty($productType)) {
    echo json_encode(['success' => false, 'message' => '카테고리를 선택해주세요.']);
    exit;
}

if (!in_array($productType, $validProductTypes)) {
    echo json_encode(['success' => false, 'message' => '유효하지 않은 카테고리입니다.']);
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => '데이터베이스 연결에 실패했습니다.']);
    exit;
}

try {
    // WHERE 조건 구성
    // 통신사단독유심의 경우 status 조건을 완화 (inactive 제외, deleted만 제외)
    $whereConditions = [];
    if ($productType === 'mno_sim') {
        // 통신사단독유심: deleted만 제외
        $whereConditions[] = "p.status != 'deleted'";
    } else {
        // 다른 상품: active만 표시
        $whereConditions[] = "p.status = 'active'";
    }
    $params = [];
    
    // 카테고리 필터
    $isMnoSim = false;
    if (!empty($productType)) {
        if ($productType === 'mno_sim') {
            // 통신사단독유심은 mno 또는 mno-sim 타입이면서 product_mno_sim_details 테이블에 존재하는 상품
            // 실제 데이터베이스에 'mno-sim' 타입이 있을 수 있으므로 둘 다 검색
            $whereConditions[] = "(p.product_type = 'mno' OR p.product_type = 'mno-sim')";
            $isMnoSim = true;
        } else {
            $whereConditions[] = "p.product_type = :product_type";
            $params[':product_type'] = $productType;
        }
    }
    
    // 판매자 정보 조인 (항상 조인하여 판매자 정보 가져오기)
    $joinSeller = true;
    
    // 카테고리별 필터 추가
    if ($productType === 'mvno') {
        // 알뜰폰 필터
        if (!empty($provider)) {
            $whereConditions[] = "mvno.provider = :provider";
            $params[':provider'] = $provider;
        }
        if (!empty($contractPeriod)) {
            $whereConditions[] = "mvno.contract_period = :contract_period";
            $params[':contract_period'] = $contractPeriod;
        }
        if (!empty($priceMin)) {
            $whereConditions[] = "mvno.price_after >= :price_min";
            $params[':price_min'] = floatval($priceMin);
        }
        if (!empty($priceMax)) {
            $whereConditions[] = "mvno.price_after <= :price_max";
            $params[':price_max'] = floatval($priceMax);
        }
        if (!empty($serviceType)) {
            $whereConditions[] = "mvno.service_type = :service_type";
            $params[':service_type'] = $serviceType;
        }
    } else if ($productType === 'mno' && !$isMnoSim) {
        // 통신사폰 필터
        if (!empty($deviceName)) {
            $whereConditions[] = "mno.device_name LIKE :device_name";
            $params[':device_name'] = '%' . $deviceName . '%';
        }
        if (!empty($deliveryMethod)) {
            $whereConditions[] = "mno.delivery_method = :delivery_method";
            $params[':delivery_method'] = $deliveryMethod;
        }
        if (!empty($provider)) {
            // 통신사는 JSON 배열 필드에서 검색
            // 다양한 형식 지원: "lg" -> "LG U+", "lgu+" -> "LG U+", "skt" -> "SKT", "kt" -> "KT"
            $providerLower = strtolower($provider);
            $providerMap = [
                'kt' => ['KT'],
                'skt' => ['SKT'],
                'lg' => ['LG U+', 'LGU+'],
                'lgu+' => ['LG U+', 'LGU+'],
                'lgu' => ['LG U+', 'LGU+']
            ];
            
            // 매핑된 통신사명들 가져오기
            $providerSearches = $providerMap[$providerLower] ?? [strtoupper($provider)];
            
            // 여러 형식으로 검색 (공백 유무 모두 처리)
            // JSON 배열에서 정확히 통신사가 포함된 경우만 검색
            $providerConditions = [];
            $paramIndex = 0;
            foreach ($providerSearches as $providerSearch) {
                // JSON 배열 형식: ["SKT", "KT", "LG U+"]
                // JSON_CONTAINS를 사용하여 정확한 값만 매칭
                $paramKey1 = ':provider_json_contains_' . $paramIndex;
                $paramKey2 = ':provider_json_contains2_' . $paramIndex;
                $paramKey3 = ':provider_json_like_' . $paramIndex;
                $paramKey4 = ':provider_json_like2_' . $paramIndex;
                
                // JSON_CONTAINS 사용 (MySQL 5.7+)
                // JSON_CONTAINS가 실패할 경우를 대비해 LIKE도 함께 사용
                $providerConditions[] = "(
                    (mno.common_provider IS NOT NULL AND mno.common_provider != '' AND (
                        JSON_CONTAINS(mno.common_provider, {$paramKey1}) = 1
                        OR mno.common_provider LIKE {$paramKey3}
                    ))
                    OR (mno.contract_provider IS NOT NULL AND mno.contract_provider != '' AND (
                        JSON_CONTAINS(mno.contract_provider, {$paramKey2}) = 1
                        OR mno.contract_provider LIKE {$paramKey4}
                    ))
                )";
                // JSON_CONTAINS용: JSON 문자열로 변환
                $params[$paramKey1] = '"' . $providerSearch . '"';
                $params[$paramKey2] = '"' . $providerSearch . '"';
                // LIKE용: 큰따옴표로 감싸진 값 검색
                $params[$paramKey3] = '%"' . $providerSearch . '"%';
                $params[$paramKey4] = '%"' . $providerSearch . '"%';
                $paramIndex++;
            }
            
            if (!empty($providerConditions)) {
                $whereConditions[] = '(' . implode(' OR ', $providerConditions) . ')';
            }
        }
    } else if ($productType === 'mno_sim' || $isMnoSim) {
        // 통신사단독유심 필터
        if (!empty($provider)) {
            $whereConditions[] = "mno_sim.provider = :mno_sim_provider";
            $params[':mno_sim_provider'] = $provider;
        }
        if (!empty($contractPeriod)) {
            $whereConditions[] = "mno_sim.contract_period = :mno_sim_contract_period";
            $params[':mno_sim_contract_period'] = $contractPeriod;
        }
    } else if ($productType === 'internet') {
        // 인터넷 필터
        if (!empty($registrationPlace)) {
            $whereConditions[] = "inet.registration_place LIKE :registration_place";
            $params[':registration_place'] = '%' . $registrationPlace . '%';
        }
        if (!empty($speedOption)) {
            $whereConditions[] = "inet.speed_option LIKE :speed_option";
            $params[':speed_option'] = '%' . $speedOption . '%';
        }
        if (!empty($serviceType)) {
            $whereConditions[] = "inet.service_type = :service_type";
            $params[':service_type'] = $serviceType;
        }
    }
    
    // 판매자 검색 필터 추가
    if (!empty($sellerId)) {
        $whereConditions[] = "CAST(p.seller_id AS CHAR) COLLATE utf8mb4_unicode_ci = :seller_id";
        $params[':seller_id'] = $sellerId;
    }
    if (!empty($sellerName)) {
        $whereConditions[] = "(u.seller_name LIKE :seller_name OR u.name LIKE :seller_name OR u.company_name LIKE :seller_name)";
        $params[':seller_name'] = '%' . $sellerName . '%';
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // 판매자 정보 조인 (항상 조인하여 판매자 정보 가져오기)
    // seller_id(INT)와 user_id(VARCHAR) 타입이 다르므로 문자열로 변환하여 비교
    // collation 불일치 문제 해결을 위해 COLLATE 명시
    $sellerJoin = "LEFT JOIN users u ON CAST(p.seller_id AS CHAR) COLLATE utf8mb4_unicode_ci = u.user_id COLLATE utf8mb4_unicode_ci AND u.role = 'seller'";
    
    // 통신사단독유심 조인 (mno_sim 카테고리 선택 시)
    $mnoSimJoin = $isMnoSim ? "INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id" : "";
    
    // 모든 상품 타입에서 검색
    if ($isMnoSim) {
        // 통신사단독유심인 경우
        $sql = "
            SELECT 
                p.id,
                p.seller_id,
                p.product_type,
                COALESCE(mno_sim.plan_name, '알 수 없음') AS product_name,
                '통신사단독유심' AS product_type_name,
                mno_sim.provider,
                mno_sim.contract_period,
                mno_sim.price_main,
                mno_sim.price_after,
                mno_sim.discount_period,
                mno_sim.discount_period_value,
                mno_sim.discount_period_unit,
                mno_sim.data_amount,
                mno_sim.data_amount_value,
                mno_sim.data_unit,
                mno_sim.data_additional,
                mno_sim.data_additional_value,
                mno_sim.call_type,
                mno_sim.call_amount,
                mno_sim.sms_type,
                mno_sim.sms_amount,
                mno_sim.service_type,
                COALESCE(NULLIF(u.seller_name,''), NULLIF(u.name,''), u.user_id, '') AS seller_name,
                COALESCE(u.company_name,'') AS company_name
            FROM products p
            INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
            {$sellerJoin}
            WHERE {$whereClause}
            ORDER BY p.created_at DESC
            LIMIT 50
        ";
        
        // 통신사단독유심 검색 디버깅
        error_log('MNO_SIM Search - SQL: ' . $sql);
        error_log('MNO_SIM Search - WhereClause: ' . $whereClause);
        error_log('MNO_SIM Search - Params: ' . json_encode($params, JSON_UNESCAPED_UNICODE));
    } else {
        // 일반 상품인 경우
        $sql = "
            SELECT 
                p.id,
                p.seller_id,
                p.product_type,
                CASE 
                    WHEN p.product_type = 'mvno' THEN mvno.plan_name
                    WHEN p.product_type = 'mno' THEN mno.device_name
                    WHEN p.product_type = 'internet' THEN CONCAT(inet.registration_place, ' ', inet.speed_option)
                    ELSE '알 수 없음'
                END AS product_name,
                CASE 
                    WHEN p.product_type = 'mvno' THEN ''
                    WHEN p.product_type = 'mno' THEN '통신사폰'
                    WHEN p.product_type = 'internet' THEN '인터넷'
                    ELSE '기타'
                END AS product_type_name,
                -- MVNO 상세 정보
                mvno.provider AS mvno_provider,
                mvno.price_main AS mvno_price_main,
                mvno.price_after AS mvno_price_after,
                mvno.discount_period AS mvno_discount_period,
                mvno.data_amount AS mvno_data_amount,
                mvno.data_amount_value AS mvno_data_amount_value,
                mvno.data_unit AS mvno_data_unit,
                mvno.call_type AS mvno_call_type,
                mvno.call_amount AS mvno_call_amount,
                mvno.sms_type AS mvno_sms_type,
                mvno.sms_amount AS mvno_sms_amount,
                mvno.service_type AS mvno_service_type,
                -- MNO 상세 정보
                mno.device_name AS mno_device_name,
                mno.device_price AS mno_device_price,
                mno.device_capacity AS mno_device_capacity,
                mno.common_provider AS mno_common_provider,
                mno.common_discount_new AS mno_common_discount_new,
                mno.common_discount_port AS mno_common_discount_port,
                mno.common_discount_change AS mno_common_discount_change,
                mno.contract_provider AS mno_contract_provider,
                mno.contract_discount_new AS mno_contract_discount_new,
                mno.contract_discount_port AS mno_contract_discount_port,
                mno.contract_discount_change AS mno_contract_discount_change,
                mno.service_type AS mno_service_type,
                mno.contract_period AS mno_contract_period,
                mno.contract_period_value AS mno_contract_period_value,
                mno.price_main AS mno_price_main,
                mno.data_amount AS mno_data_amount,
                mno.data_amount_value AS mno_data_amount_value,
                mno.data_unit AS mno_data_unit,
                mno.call_type AS mno_call_type,
                mno.call_amount AS mno_call_amount,
                mno.sms_type AS mno_sms_type,
                mno.sms_amount AS mno_sms_amount,
                mno.delivery_method AS mno_delivery_method,
                mno.visit_region AS mno_visit_region,
                -- Internet 상세 정보
                inet.monthly_fee AS internet_monthly_fee,
                inet.registration_place AS internet_registration_place,
                inet.speed_option AS internet_speed_option,
                inet.service_type AS internet_service_type,
                COALESCE(NULLIF(u.seller_name,''), NULLIF(u.name,''), u.user_id, '') AS seller_name,
                COALESCE(u.company_name,'') AS company_name
            FROM products p
            LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id AND p.product_type = 'mvno'
            LEFT JOIN product_mno_details mno ON p.id = mno.product_id AND p.product_type = 'mno'
            LEFT JOIN product_internet_details inet ON p.id = inet.product_id AND p.product_type = 'internet'
            {$sellerJoin}
            WHERE {$whereClause}
            ORDER BY p.created_at DESC
            LIMIT 50
        ";
    }
    
    try {
        // SQL 쿼리와 파라미터 디버깅
        error_log('Product search SQL: ' . $sql);
        error_log('Product search Params: ' . json_encode($params, JSON_UNESCAPED_UNICODE));
        
        $stmt = $pdo->prepare($sql);
        
        // 파라미터 바인딩
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 통신사단독유심 검색 결과 로그
        if ($isMnoSim) {
            error_log('MNO_SIM Search - Result count: ' . count($products));
            if (count($products) === 0) {
                // 데이터가 없는 경우 테이블 존재 여부 확인
                $checkTable = $pdo->query("SHOW TABLES LIKE 'product_mno_sim_details'");
                $tableExists = $checkTable->rowCount() > 0;
                error_log('MNO_SIM Search - Table exists: ' . ($tableExists ? 'YES' : 'NO'));
                
                if ($tableExists) {
                    // 테이블에 데이터가 있는지 확인
                    $checkData = $pdo->query("SELECT COUNT(*) as cnt FROM product_mno_sim_details");
                    $dataCount = $checkData->fetch(PDO::FETCH_ASSOC)['cnt'];
                    error_log('MNO_SIM Search - Data count in table: ' . $dataCount);
                    
                    // active 상태인 상품이 있는지 확인 (mno 또는 mno-sim)
                    $checkActive = $pdo->query("
                        SELECT COUNT(*) as cnt 
                        FROM products p 
                        INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id 
                        WHERE p.status != 'deleted' AND (p.product_type = 'mno' OR p.product_type = 'mno-sim')
                    ");
                    $activeCount = $checkActive->fetch(PDO::FETCH_ASSOC)['cnt'];
                    error_log('MNO_SIM Search - Non-deleted products count: ' . $activeCount);
                    
                    // 상세 상태 확인
                    $checkStatus = $pdo->query("
                        SELECT p.status, p.product_type, COUNT(*) as cnt
                        FROM products p 
                        INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id 
                        GROUP BY p.status, p.product_type
                    ");
                    $statusResults = $checkStatus->fetchAll(PDO::FETCH_ASSOC);
                    error_log('MNO_SIM Search - Status breakdown: ' . json_encode($statusResults, JSON_UNESCAPED_UNICODE));
                }
            }
        }
    } catch (PDOException $e) {
        error_log('Product search SQL error: ' . $e->getMessage());
        error_log('SQL: ' . $sql);
        error_log('Params: ' . json_encode($params, JSON_UNESCAPED_UNICODE));
        echo json_encode([
            'success' => false, 
            'message' => '검색 쿼리 실행 중 오류가 발생했습니다: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $result = [];
    foreach ($products as $product) {
        $sellerId = (string)($product['seller_id'] ?? '');
        $sellerName = trim($product['seller_name'] ?? '');
        $companyName = trim($product['company_name'] ?? '');
        
        // 판매자 정보가 없으면 기본값 설정
        if (empty($sellerName)) {
            $sellerName = $sellerId ?: '-';
        }
        if (empty($companyName)) {
            $companyName = '-';
        }
        
        // 상품 타입별 상세 정보 추출
        $productData = [
            'id' => (int)$product['id'],
            'name' => $product['product_name'] ?? '알 수 없음',
            'type' => $product['product_type_name'] ?? '기타',
            'product_type' => $product['product_type'],
            'seller_id' => $sellerId,
            'seller_name' => $sellerName,
            'company_name' => $companyName
        ];
        
        // MVNO 상품 정보
        if ($product['product_type'] === 'mvno') {
            $productData['provider'] = $product['mvno_provider'] ?? '';
            $productData['price_main'] = $product['mvno_price_main'] ?? 0;
            $productData['price_after'] = $product['mvno_price_after'] ?? 0;
            $productData['discount_period'] = $product['mvno_discount_period'] ?? '';
            $productData['data_amount'] = $product['mvno_data_amount'] ?? '';
            $productData['data_amount_value'] = $product['mvno_data_amount_value'] ?? '';
            $productData['data_unit'] = $product['mvno_data_unit'] ?? '';
            $productData['call_type'] = $product['mvno_call_type'] ?? '';
            $productData['call_amount'] = $product['mvno_call_amount'] ?? '';
            $productData['sms_type'] = $product['mvno_sms_type'] ?? '';
            $productData['sms_amount'] = $product['mvno_sms_amount'] ?? '';
            $productData['service_type'] = $product['mvno_service_type'] ?? '';
        }
        // MNO 상품 정보
        elseif ($product['product_type'] === 'mno') {
            $productData['device_name'] = $product['mno_device_name'] ?? '';
            $productData['device_price'] = $product['mno_device_price'] ?? 0;
            $productData['device_capacity'] = $product['mno_device_capacity'] ?? '';
            $productData['common_provider'] = $product['mno_common_provider'] ?? '';
            $productData['common_discount_new'] = $product['mno_common_discount_new'] ?? '';
            $productData['common_discount_port'] = $product['mno_common_discount_port'] ?? '';
            $productData['common_discount_change'] = $product['mno_common_discount_change'] ?? '';
            $productData['contract_provider'] = $product['mno_contract_provider'] ?? '';
            $productData['contract_discount_new'] = $product['mno_contract_discount_new'] ?? '';
            $productData['contract_discount_port'] = $product['mno_contract_discount_port'] ?? '';
            $productData['contract_discount_change'] = $product['mno_contract_discount_change'] ?? '';
            $productData['service_type'] = $product['mno_service_type'] ?? '';
            $productData['contract_period'] = $product['mno_contract_period'] ?? '';
            $productData['contract_period_value'] = $product['mno_contract_period_value'] ?? '';
            $productData['price_main'] = $product['mno_price_main'] ?? 0;
            $productData['data_amount'] = $product['mno_data_amount'] ?? '';
            $productData['data_amount_value'] = $product['mno_data_amount_value'] ?? '';
            $productData['data_unit'] = $product['mno_data_unit'] ?? '';
            $productData['call_type'] = $product['mno_call_type'] ?? '';
            $productData['call_amount'] = $product['mno_call_amount'] ?? '';
            $productData['sms_type'] = $product['mno_sms_type'] ?? '';
            $productData['sms_amount'] = $product['mno_sms_amount'] ?? '';
            $productData['delivery_method'] = $product['mno_delivery_method'] ?? '';
            $productData['visit_region'] = $product['mno_visit_region'] ?? '';
        }
        // Internet 상품 정보
        elseif ($product['product_type'] === 'internet') {
            $productData['monthly_fee'] = $product['internet_monthly_fee'] ?? 0;
            $productData['registration_place'] = $product['internet_registration_place'] ?? '';
            $productData['speed_option'] = $product['internet_speed_option'] ?? '';
            $productData['service_type'] = $product['internet_service_type'] ?? '';
        }
        // MNO-SIM 상품 정보 (이미 SQL에서 가져옴)
        elseif ($isMnoSim) {
            $productData['provider'] = $product['provider'] ?? '';
            $productData['contract_period'] = $product['contract_period'] ?? '';
            $productData['price_main'] = $product['price_main'] ?? 0;
            $productData['price_after'] = $product['price_after'] ?? 0;
            $productData['discount_period'] = $product['discount_period'] ?? '';
            $productData['discount_period_value'] = $product['discount_period_value'] ?? null;
            $productData['discount_period_unit'] = $product['discount_period_unit'] ?? '';
            $productData['data_amount'] = $product['data_amount'] ?? '';
            $productData['data_amount_value'] = $product['data_amount_value'] ?? '';
            $productData['data_unit'] = $product['data_unit'] ?? '';
            $productData['data_additional'] = $product['data_additional'] ?? '';
            $productData['data_additional_value'] = $product['data_additional_value'] ?? '';
            $productData['call_type'] = $product['call_type'] ?? '';
            $productData['call_amount'] = $product['call_amount'] ?? '';
            $productData['sms_type'] = $product['sms_type'] ?? '';
            $productData['sms_amount'] = $product['sms_amount'] ?? '';
            $productData['service_type'] = $product['service_type'] ?? '';
        }
        
        $result[] = $productData;
    }
    
    echo json_encode([
        'success' => true,
        'products' => $result,
        'count' => count($result)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log('Product search error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '검색 중 오류가 발생했습니다.']);
}


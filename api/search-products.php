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
    $whereConditions = ['p.status = \'active\''];
    $params = [];
    
    // 카테고리 필터
    $isMnoSim = false;
    if (!empty($productType)) {
        if ($productType === 'mno_sim') {
            // 통신사단독유심은 mno 타입이면서 product_mno_sim_details 테이블에 존재하는 상품
            $whereConditions[] = "p.product_type = 'mno'";
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
            // 통신사는 JSON 필드에서 검색
            $whereConditions[] = "(mno.common_provider LIKE :provider_json OR mno.contract_provider LIKE :provider_json)";
            $params[':provider_json'] = '%' . $provider . '%';
        }
    } else if ($productType === 'mno_sim' || $isMnoSim) {
        // 통신사단독유심 필터
        if (!empty($provider)) {
            $whereConditions[] = "mno_sim.provider = :provider";
            $params[':provider'] = $provider;
        }
        if (!empty($contractPeriod)) {
            $whereConditions[] = "mno_sim.contract_period = :contract_period";
            $params[':contract_period'] = $contractPeriod;
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
                COALESCE(NULLIF(u.seller_name,''), NULLIF(u.name,''), u.user_id, '') AS seller_name,
                COALESCE(u.company_name,'') AS company_name
            FROM products p
            INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
            {$sellerJoin}
            WHERE {$whereClause}
            ORDER BY p.created_at DESC
            LIMIT 50
        ";
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
                    WHEN p.product_type = 'mvno' THEN '알뜰폰'
                    WHEN p.product_type = 'mno' THEN '통신사폰'
                    WHEN p.product_type = 'internet' THEN '인터넷'
                    ELSE '기타'
                END AS product_type_name,
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
        
        $result[] = [
            'id' => (int)$product['id'],
            'name' => $product['product_name'] ?? '알 수 없음',
            'type' => $product['product_type_name'] ?? '기타',
            'product_type' => $product['product_type'],
            'seller_id' => $sellerId,
            'seller_name' => $sellerName,
            'company_name' => $companyName
        ];
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


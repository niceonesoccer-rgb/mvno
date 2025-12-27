<?php
/**
 * 더보기 API - 상품 목록 추가 로드
 * GET 파라미터:
 *   - type: 'internet', 'mno-sim', 'mvno', 'mno'
 *   - page: 페이지 번호 (1부터 시작)
 *   - limit: 한 번에 가져올 개수 (기본값: 10)
 *   - provider: 통신사 필터 (선택)
 *   - service_type: 서비스 타입 필터 (선택)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';
require_once __DIR__ . '/../includes/data/product-functions.php';

// 관리자 여부 확인
$isAdmin = false;
try {
    if (function_exists('isAdmin') && function_exists('getCurrentUser')) {
        $currentUser = getCurrentUser();
        if ($currentUser) {
            $isAdmin = isAdmin($currentUser['user_id']);
        }
    }
} catch (Exception $e) {
    // 관리자 체크 실패 시 일반 사용자로 처리
}

$type = $_GET['type'] ?? '';
$page = intval($_GET['page'] ?? 1);
$limit = intval($_GET['limit'] ?? 20);
$filterProvider = $_GET['provider'] ?? '';
$filterServiceType = $_GET['service_type'] ?? '';
$format = $_GET['format'] ?? 'json'; // 'json' or 'html'

// 유효성 검사
if (!in_array($type, ['internet', 'mno-sim', 'mvno', 'mno'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product type']);
    exit;
}

if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 100) $limit = 10;

$offset = ($page - 1) * $limit;

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    $statusCondition = $isAdmin ? "AND p.status != 'deleted'" : "AND p.status = 'active'";
    $products = [];
    $totalCount = 0;

    switch ($type) {
        case 'internet':
            // 인터넷 상품
            $whereConditions = ["p.product_type = 'internet'", $statusCondition];
            $params = [];
            
            if (!empty($filterProvider)) {
                $whereConditions[] = 'inet.registration_place = :provider';
                $params[':provider'] = $filterProvider;
            }
            if (!empty($filterServiceType)) {
                $whereConditions[] = 'inet.service_type = :service_type';
                $params[':service_type'] = $filterServiceType;
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            // 전체 개수 조회
            $countStmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM products p
                INNER JOIN product_internet_details inet ON p.id = inet.product_id
                {$whereClause}
            ");
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // 상품 목록 조회
            $stmt = $pdo->prepare("
                SELECT 
                    p.id,
                    p.seller_id,
                    p.status,
                    p.view_count,
                    p.favorite_count,
                    p.application_count,
                    inet.registration_place,
                    inet.service_type,
                    inet.speed_option,
                    inet.monthly_fee,
                    inet.cash_payment_names,
                    inet.cash_payment_prices,
                    inet.gift_card_names,
                    inet.gift_card_prices,
                    inet.equipment_names,
                    inet.equipment_prices,
                    inet.installation_names,
                    inet.installation_prices,
                    inet.promotion_title,
                    inet.promotions
                FROM products p
                INNER JOIN product_internet_details inet ON p.id = inet.product_id
                {$whereClause}
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // JSON 필드 디코딩
            foreach ($products as &$product) {
                $jsonFields = [
                    'cash_payment_names', 'cash_payment_prices',
                    'gift_card_names', 'gift_card_prices',
                    'equipment_names', 'equipment_prices',
                    'installation_names', 'installation_prices'
                ];
                
                foreach ($jsonFields as $field) {
                    if (!empty($product[$field])) {
                        $decoded = json_decode($product[$field], true);
                        $product[$field] = is_array($decoded) ? $decoded : [];
                    } else {
                        $product[$field] = [];
                    }
                }
                
                if (!empty($product['promotions'])) {
                    $product['promotions'] = json_decode($product['promotions'], true) ?: [];
                } else {
                    $product['promotions'] = [];
                }
            }
            
            // HTML 생성
            require_once __DIR__ . '/../internets/internets.php';
            $htmlFragments = [];
            
            foreach ($products as $product) {
                ob_start();
                
                // 변수 준비
                $iconPath = getInternetIconPath($product['registration_place']);
                $speedOption = htmlspecialchars($product['speed_option'] ?? '');
                $applicationCount = number_format($product['application_count'] ?? 0);
                
                // 월 요금 처리
                $monthlyFeeRaw = $product['monthly_fee'] ?? '';
                if (!empty($monthlyFeeRaw)) {
                    if (is_numeric($monthlyFeeRaw)) {
                        $numericValue = (int)floatval($monthlyFeeRaw);
                        $monthlyFee = number_format($numericValue, 0, '', ',') . '원';
                    } elseif (preg_match('/^(\d+)(.+)$/', $monthlyFeeRaw, $matches)) {
                        $numericValue = (int)$matches[1];
                        $monthlyFee = number_format($numericValue, 0, '', ',') . $matches[2];
                    } else {
                        $numericValue = (int)floatval($monthlyFeeRaw);
                        $monthlyFee = number_format($numericValue, 0, '', ',') . '원';
                    }
                } else {
                    $monthlyFee = '0원';
                }
                
                // 혜택 정보
                $cashNames = $product['cash_payment_names'] ?? [];
                $cashPrices = $product['cash_payment_prices'] ?? [];
                $giftNames = $product['gift_card_names'] ?? [];
                $giftPrices = $product['gift_card_prices'] ?? [];
                $equipNames = $product['equipment_names'] ?? [];
                $equipPrices = $product['equipment_prices'] ?? [];
                $installNames = $product['installation_names'] ?? [];
                $installPrices = $product['installation_prices'] ?? [];
                
                // 서비스 타입
                $serviceType = $product['service_type'] ?? '인터넷';
                $serviceTypeDisplay = $serviceType;
                if ($serviceType === '인터넷+TV') {
                    $serviceTypeDisplay = '인터넷 + TV 결합';
                } elseif ($serviceType === '인터넷+TV+핸드폰') {
                    $serviceTypeDisplay = '인터넷 + TV + 핸드폰 결합';
                }
                
                // 프로모션
                $promotionTitle = $product['promotion_title'] ?? '';
                $promotions = $product['promotions'] ?? [];
                
                // 템플릿 포함
                include __DIR__ . '/../internets/product-item-template.php';
                $htmlFragments[] = ob_get_clean();
            }
            
            $hasMore = ($offset + $limit) < $totalCount;
            $remaining = max(0, $totalCount - ($offset + $limit));
            
            echo json_encode([
                'success' => true,
                'html' => $htmlFragments,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $totalCount,
                    'hasMore' => $hasMore,
                    'remaining' => $remaining
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
            break;

        case 'mno-sim':
            // 통신사단독유심 상품
            $whereConditions = ["p.product_type = 'mno-sim'", $statusCondition];
            $params = [];
            
            if (!empty($filterProvider)) {
                $whereConditions[] = 'mno_sim.provider = :provider';
                $params[':provider'] = $filterProvider;
            }
            if (!empty($filterServiceType)) {
                $whereConditions[] = 'mno_sim.service_type = :service_type';
                $params[':service_type'] = $filterServiceType;
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            // 전체 개수 조회
            $countStmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM products p
                INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
                {$whereClause}
            ");
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // 상품 목록 조회
            $stmt = $pdo->prepare("
                SELECT 
                    p.id,
                    p.seller_id,
                    p.status,
                    p.view_count,
                    p.favorite_count,
                    p.application_count,
                    mno_sim.provider,
                    mno_sim.service_type,
                    mno_sim.plan_name,
                    mno_sim.contract_period,
                    mno_sim.contract_period_discount_value,
                    mno_sim.contract_period_discount_unit,
                    mno_sim.price_main,
                    mno_sim.price_main_unit,
                    mno_sim.discount_period,
                    mno_sim.discount_period_value,
                    mno_sim.discount_period_unit,
                    mno_sim.price_after_type,
                    mno_sim.price_after,
                    mno_sim.price_after_unit,
                    mno_sim.data_amount,
                    mno_sim.data_amount_value,
                    mno_sim.data_unit,
                    mno_sim.data_additional,
                    mno_sim.data_additional_value,
                    mno_sim.data_exhausted,
                    mno_sim.data_exhausted_value,
                    mno_sim.call_type,
                    mno_sim.call_amount,
                    mno_sim.call_amount_unit,
                    mno_sim.additional_call_type,
                    mno_sim.additional_call,
                    mno_sim.additional_call_unit,
                    mno_sim.sms_type,
                    mno_sim.sms_amount,
                    mno_sim.sms_amount_unit,
                    mno_sim.promotion_title,
                    mno_sim.promotions,
                    mno_sim.benefits
                FROM products p
                INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
                {$whereClause}
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'mvno':
            // 알뜰폰 상품 (plan-data.php 사용)
            require_once __DIR__ . '/../includes/data/plan-data.php';
            $allPlans = getPlansDataFromDB(10000, $isAdmin ? 'all' : 'active');
            
            // 필터 적용
            if (!empty($filterProvider)) {
                $allPlans = array_filter($allPlans, function($plan) use ($filterProvider) {
                    return isset($plan['provider']) && $plan['provider'] === $filterProvider;
                });
            }
            
            $totalCount = count($allPlans);
            $products = array_slice($allPlans, $offset, $limit);
            break;

        case 'mno':
            // 통신사폰 상품 (phone-data.php 사용)
            require_once __DIR__ . '/../includes/data/phone-data.php';
            $allPhones = getPhonesData(10000);
            
            // 필터 적용
            if (!empty($filterProvider)) {
                $allPhones = array_filter($allPhones, function($phone) use ($filterProvider) {
                    return isset($phone['provider']) && $phone['provider'] === $filterProvider;
                });
            }
            
            $totalCount = count($allPhones);
            $products = array_slice($allPhones, $offset, $limit);
            break;
    }

    $hasMore = ($offset + $limit) < $totalCount;
    $remaining = max(0, $totalCount - ($offset + $limit));

    // JSON 형식으로 반환 (mvno, mno, mno-sim은 JSON, internet은 HTML)
    echo json_encode([
        'success' => true,
        'products' => $products,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $totalCount,
            'hasMore' => $hasMore,
            'remaining' => $remaining
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '데이터를 불러오는 중 오류가 발생했습니다.',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}


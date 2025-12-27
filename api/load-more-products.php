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

// 에러 표시 (개발 환경)
error_reporting(E_ALL);
ini_set('display_errors', 0); // JSON 응답이므로 화면에 출력하지 않음
ini_set('log_errors', 1);

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

// 디버깅: 요청 파라미터 로그
error_log("=== load-more-products.php 요청 ===");
error_log("type: {$type}, page: {$page}, limit: {$limit}, offset: " . (($page - 1) * $limit));
error_log("GET 파라미터: " . json_encode($_GET, JSON_UNESCAPED_UNICODE));

// 유효성 검사
if (!in_array($type, ['internet', 'mno-sim', 'mvno', 'mno'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product type']);
    exit;
}

if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 100) {
    error_log("⚠️ limit 유효성 검사 실패: limit={$limit}, 기본값 10으로 변경");
    $limit = 10;
}

$offset = ($page - 1) * $limit;
error_log("계산된 offset: {$offset}, 최종 limit: {$limit}");

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    $statusCondition = $isAdmin ? "p.status != 'deleted'" : "p.status = 'active'";
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
            // getInternetIconPath 함수만 정의 (internets.php 전체를 require하지 않음)
            if (!function_exists('getInternetIconPath')) {
                function getInternetIconPath($registrationPlace) {
                    $iconMap = [
                        'KT' => '/MVNO/assets/images/internets/kt.svg',
                        'SKT' => '/MVNO/assets/images/internets/broadband.svg',
                        'LG U+' => '/MVNO/assets/images/internets/lgu.svg',
                        'KT skylife' => '/MVNO/assets/images/internets/ktskylife.svg',
                        'LG헬로비전' => '/MVNO/assets/images/internets/hellovision.svg',
                        'BTV' => '/MVNO/assets/images/internets/btv.svg',
                        'DLIVE' => '/MVNO/assets/images/internets/dlive.svg',
                    ];
                    return $iconMap[$registrationPlace] ?? '';
                }
            }
            
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
            
            // HTML 생성
            require_once __DIR__ . '/../includes/data/plan-data.php';
            require_once __DIR__ . '/../includes/data/product-functions.php';
            
            // 필요한 함수들이 있는지 확인
            if (!function_exists('getSellerById')) {
                error_log("mno-sim API: getSellerById 함수가 없습니다.");
            }
            if (!function_exists('getSellerDisplayName')) {
                error_log("mno-sim API: getSellerDisplayName 함수가 없습니다.");
            }
            if (!function_exists('getProductAverageRating')) {
                error_log("mno-sim API: getProductAverageRating 함수가 없습니다.");
            }
            
            // 데이터 포맷팅 함수들 (mno-sim.php에서 가져옴)
            if (!function_exists('formatDataAmount')) {
                function formatDataAmount($dataAmount, $dataAmountValue, $dataUnit) {
                    if ($dataAmount === '무제한') {
                        return '무제한';
                    } elseif (!empty($dataAmountValue) && !empty($dataUnit)) {
                        return number_format($dataAmountValue) . $dataUnit;
                    } elseif (!empty($dataAmount)) {
                        return $dataAmount;
                    }
                    return '-';
                }
            }
            
            if (!function_exists('formatCallAmount')) {
                function formatCallAmount($callType, $callAmount, $callAmountUnit) {
                    if ($callType === '무제한') {
                        return '무제한';
                    } elseif (!empty($callAmount) && !empty($callAmountUnit)) {
                        return number_format($callAmount) . $callAmountUnit;
                    } elseif (!empty($callType)) {
                        return $callType;
                    }
                    return '-';
                }
            }
            
            if (!function_exists('formatDiscountPeriod')) {
                function formatDiscountPeriod($discountPeriod, $discountPeriodValue, $discountPeriodUnit) {
                    if ($discountPeriod === '프로모션 없음' || empty($discountPeriod)) {
                        return '-';
                    } elseif (!empty($discountPeriodValue) && !empty($discountPeriodUnit)) {
                        return $discountPeriodValue . $discountPeriodUnit;
                    } elseif (!empty($discountPeriod)) {
                        return $discountPeriod;
                    }
                    return '-';
                }
            }
            
            $htmlFragments = [];
            
            // 디버깅: 상품 개수 확인
            error_log("mno-sim API: 상품 개수 = " . count($products));
            
            foreach ($products as $product) {
                ob_start();
                try {
                
                // 판매자명 가져오기
                $sellerId = $product['seller_id'] ?? null;
                $sellerName = '';
                if ($sellerId) {
                    $seller = getSellerById($sellerId);
                    $sellerName = getSellerDisplayName($seller);
                }
                
                // provider 필드에 판매자명 사용
                $displayProvider = !empty($sellerName) ? $sellerName : ($product['provider'] ?? '판매자 정보 없음');
                
                // 데이터 변환
                $provider = htmlspecialchars($product['provider'] ?? '');
                $serviceType = htmlspecialchars($product['service_type'] ?? '');
                $planName = htmlspecialchars($product['plan_name'] ?? '');
                $contractPeriod = htmlspecialchars($product['contract_period'] ?? '');
                $contractPeriodValue = $product['contract_period_discount_value'] ?? null;
                $contractPeriodUnit = $product['contract_period_discount_unit'] ?? '';
                
                // 할인방법 표시
                $discountMethod = $contractPeriod;
                if (!empty($contractPeriodValue) && !empty($contractPeriodUnit)) {
                    $discountMethod .= ' ' . $contractPeriodValue . $contractPeriodUnit;
                }
                
                // 제목
                $title = $provider . ' | ' . $serviceType;
                if (!empty($discountMethod)) {
                    $title .= ' | ' . $discountMethod;
                }
                
                // 데이터 정보
                $dataAmount = formatDataAmount(
                    $product['data_amount'] ?? '',
                    $product['data_amount_value'] ?? null,
                    $product['data_unit'] ?? ''
                );
                $dataAdditional = !empty($product['data_additional_value']) 
                    ? htmlspecialchars($product['data_additional_value']) 
                    : (!empty($product['data_additional']) && $product['data_additional'] !== '없음' 
                        ? htmlspecialchars($product['data_additional']) : '');
                $dataExhausted = !empty($product['data_exhausted_value']) 
                    ? htmlspecialchars($product['data_exhausted_value'])
                    : (!empty($product['data_exhausted']) 
                        ? htmlspecialchars($product['data_exhausted']) : '');
                
                $dataMain = $dataAmount;
                if (!empty($dataAdditional)) {
                    $dataMain .= ' + ' . $dataAdditional;
                }
                if (!empty($dataExhausted)) {
                    $dataMain .= ' +' . $dataExhausted;
                }
                
                // 기능
                $callAmount = formatCallAmount(
                    $product['call_type'] ?? '',
                    $product['call_amount'] ?? null,
                    $product['call_amount_unit'] ?? ''
                );
                $additionalCall = formatCallAmount(
                    $product['additional_call_type'] ?? '',
                    $product['additional_call'] ?? null,
                    $product['additional_call_unit'] ?? ''
                );
                $smsAmount = formatCallAmount(
                    $product['sms_type'] ?? '',
                    $product['sms_amount'] ?? null,
                    $product['sms_amount_unit'] ?? ''
                );
                
                $features = [];
                if ($callAmount !== '-') {
                    $features[] = '통화 ' . $callAmount;
                }
                if ($smsAmount !== '-') {
                    $features[] = '문자 ' . $smsAmount;
                }
                if ($additionalCall !== '-') {
                    $features[] = '부가영상통화 ' . $additionalCall;
                }
                
                // 가격 정보
                $regularPriceValue = (int)$product['price_main'];
                $promotionPriceValue = (int)$product['price_after'];
                $discountPeriod = formatDiscountPeriod(
                    $product['discount_period'] ?? '',
                    $product['discount_period_value'] ?? null,
                    $product['discount_period_unit'] ?? ''
                );
                
                $hasPromotion = ($promotionPriceValue > 0);
                
                if ($hasPromotion) {
                    $priceMain = '월 ' . number_format($promotionPriceValue) . ($product['price_after_unit'] ?: '원');
                    $priceAfter = '';
                    if ($regularPriceValue > 0 && $discountPeriod !== '-') {
                        $priceAfter = $discountPeriod . ' 후 월 ' . number_format($regularPriceValue) . ($product['price_main_unit'] ?: '원');
                    } elseif ($regularPriceValue > 0) {
                        $priceAfter = '월 ' . number_format($regularPriceValue) . ($product['price_main_unit'] ?: '원');
                    }
                } else {
                    if ($regularPriceValue > 0) {
                        $priceMain = '월 ' . number_format($regularPriceValue) . ($product['price_main_unit'] ?: '원');
                        $priceAfter = '';
                    } else {
                        $priceMain = '월 0원';
                        $priceAfter = '';
                    }
                }
                
                // 선택 수
                $applicationCount = (int)($product['application_count'] ?? 0);
                $selectionCount = number_format($applicationCount) . '명이 선택';
                
                // 찜 상태 확인
                $isFavorited = false;
                if (function_exists('isLoggedIn') && isLoggedIn()) {
                    $currentUser = getCurrentUser();
                    $currentUserId = $currentUser['user_id'] ?? null;
                    if ($currentUserId) {
                        try {
                            $favStmt = $pdo->prepare("
                                SELECT COUNT(*) as count 
                                FROM product_favorites 
                                WHERE product_id = :product_id 
                                AND user_id = :user_id 
                                AND product_type = 'mno-sim'
                            ");
                            $favStmt->execute([
                                ':product_id' => $product['id'],
                                ':user_id' => $currentUserId
                            ]);
                            $favResult = $favStmt->fetch(PDO::FETCH_ASSOC);
                            $isFavorited = ($favResult['count'] ?? 0) > 0;
                        } catch (Exception $e) {
                            // 에러 무시
                        }
                    }
                }
                
                // 평점 가져오기
                $productId = (int)$product['id'];
                $averageRating = getProductAverageRating($productId, 'mno-sim');
                $rating = ($averageRating > 0) ? number_format($averageRating, 1) : '';
                
                // 혜택 데이터
                $gifts = [];
                $promotionTitle = $product['promotion_title'] ?? '';
                
                if (!empty($product['promotions'])) {
                    $promotions = json_decode($product['promotions'], true);
                    if (is_array($promotions)) {
                        $gifts = array_filter($promotions, function($p) {
                            return !empty(trim($p));
                        });
                    }
                }
                
                if (empty($gifts) && !empty($product['benefits'])) {
                    $benefits = json_decode($product['benefits'], true);
                    if (is_array($benefits)) {
                        $gifts = array_filter($benefits, function($b) {
                            return !empty(trim($b));
                        });
                    }
                }
                
                // plan 배열 구성
                $plan = [
                    'id' => $product['id'],
                    'provider' => $displayProvider,
                    'rating' => $rating,
                    'title' => $title,
                    'plan_name' => $planName,
                    'data_main' => $dataMain,
                    'features' => $features,
                    'price_main' => $priceMain,
                    'price_after' => $priceAfter,
                    'selection_count' => $selectionCount,
                    'is_favorited' => $isFavorited,
                    'gifts' => $gifts,
                    'promotion_title' => $promotionTitle,
                    'link_url' => '/MVNO/mno-sim/mno-sim-detail.php?id=' . $product['id'],
                    'item_type' => 'mno-sim'
                ];
                
                // 카드 컴포넌트 사용
                $card_wrapper_class = '';
                $layout_type = 'list';
                include __DIR__ . '/../includes/components/plan-card.php';
                
                // 구분선 추가
                echo '<hr class="plan-card-divider">';
                
                $html = ob_get_clean();
                if (!empty(trim($html))) {
                    $htmlFragments[] = $html;
                } else {
                    error_log("mno-sim API: HTML이 비어있음 - 상품 ID: " . ($product['id'] ?? 'unknown'));
                }
                } catch (Exception $e) {
                    ob_end_clean();
                    error_log("mno-sim API: HTML 생성 오류 - " . $e->getMessage());
                    error_log("스택 트레이스: " . $e->getTraceAsString());
                    error_log("파일: " . $e->getFile() . ", 라인: " . $e->getLine());
                    // 에러가 발생해도 계속 진행 (다음 상품 처리)
                } catch (Error $e) {
                    ob_end_clean();
                    error_log("mno-sim API: Fatal Error - " . $e->getMessage());
                    error_log("스택 트레이스: " . $e->getTraceAsString());
                    error_log("파일: " . $e->getFile() . ", 라인: " . $e->getLine());
                    // Fatal Error는 전체 프로세스를 중단시킴
                    throw $e;
                }
            }
            
            $hasMore = ($offset + $limit) < $totalCount;
            $remaining = max(0, $totalCount - ($offset + $limit));
            
            // HTML이 비어있으면 실패로 처리
            if (empty($htmlFragments)) {
                error_log("mno-sim API: HTML 배열이 비어있음 - 상품 개수: " . count($products) . ", 총 개수: " . $totalCount);
                // 실제로 상품이 있는데 HTML이 비어있으면 에러, 상품이 없으면 정상
                if (count($products) > 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'HTML generation failed',
                        'html' => [],
                        'pagination' => [
                            'page' => $page,
                            'limit' => $limit,
                            'total' => $totalCount,
                            'hasMore' => ($offset + $limit) < $totalCount,
                            'remaining' => max(0, $totalCount - ($offset + $limit))
                        ]
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }
            
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
            
            // 디버깅: 슬라이스 전 로그
            error_log("=== mvno API 처리 시작 ===");
            error_log("전체 상품 개수: {$totalCount}");
            error_log("요청 파라미터 - page: {$page}, limit: {$limit}, offset: {$offset}");
            error_log("array_slice 호출: array_slice(\$allPlans, {$offset}, {$limit})");
            
            $products = array_slice($allPlans, $offset, $limit);
            
            // 디버깅: 슬라이스 후 로그
            error_log("슬라이스 결과 - 반환할 상품 개수: " . count($products));
            if (count($products) > 0) {
                error_log("첫 번째 상품 ID: " . ($products[0]['id'] ?? 'N/A'));
                error_log("마지막 상품 ID: " . ($products[count($products)-1]['id'] ?? 'N/A'));
            }
            
            // HTML 생성
            $htmlFragments = [];
            
            foreach ($products as $plan) {
                ob_start();
                try {
                    // 래퍼 div로 감싸서 하나의 요소로 만들기
                    echo '<div class="plan-item-wrapper">';
                    
                    // 카드 컴포넌트 사용
                    $card_wrapper_class = '';
                    $layout_type = 'list';
                    include __DIR__ . '/../includes/components/plan-card.php';
                    
                    // 구분선 추가
                    echo '<hr class="plan-card-divider">';
                    
                    echo '</div>'; // 래퍼 닫기
                    
                    $html = ob_get_clean();
                    if (!empty(trim($html))) {
                        $htmlFragments[] = $html;
                    }
                } catch (Exception $e) {
                    ob_end_clean();
                    error_log("mvno API: HTML 생성 오류 - " . $e->getMessage());
                } catch (Error $e) {
                    ob_end_clean();
                    error_log("mvno API: Fatal Error - " . $e->getMessage());
                    throw $e;
                }
            }
            
            $hasMore = ($offset + $limit) < $totalCount;
            $remaining = max(0, $totalCount - ($offset + $limit));
            
            // 디버깅: HTML 생성 결과 확인
            error_log("=== mvno API HTML 생성 완료 ===");
            error_log("HTML 개수: " . count($htmlFragments));
            error_log("상품 개수: " . count($products));
            error_log("offset: {$offset}, limit: {$limit}");
            error_log("hasMore: " . ($hasMore ? 'true' : 'false'));
            error_log("remaining: {$remaining}");
            
            if (count($htmlFragments) !== count($products)) {
                error_log("⚠️ 경고: HTML 개수(" . count($htmlFragments) . ")와 상품 개수(" . count($products) . ")가 일치하지 않습니다!");
            }
            
            if (count($htmlFragments) > $limit) {
                error_log("⚠️ 경고: HTML 개수(" . count($htmlFragments) . ")가 limit({$limit})보다 많습니다!");
            }
            
            // HTML이 비어있으면 실패로 처리
            if (empty($htmlFragments) && count($products) > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'HTML generation failed',
                    'html' => [],
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $totalCount,
                        'hasMore' => $hasMore,
                        'remaining' => $remaining
                    ]
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
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
            $phones = array_slice($allPhones, $offset, $limit);
            
            // HTML 생성
            $htmlFragments = [];
            
            foreach ($phones as $phone) {
                ob_start();
                try {
                    // 카드 컴포넌트 사용
                    $card_wrapper_class = '';
                    $layout_type = 'list';
                    include __DIR__ . '/../includes/components/phone-card.php';
                    
                    // 구분선 추가
                    echo '<hr class="plan-card-divider">';
                    
                    $html = ob_get_clean();
                    if (!empty(trim($html))) {
                        $htmlFragments[] = $html;
                    }
                } catch (Exception $e) {
                    ob_end_clean();
                    error_log("mno API: HTML 생성 오류 - " . $e->getMessage());
                } catch (Error $e) {
                    ob_end_clean();
                    error_log("mno API: Fatal Error - " . $e->getMessage());
                    throw $e;
                }
            }
            
            $hasMore = ($offset + $limit) < $totalCount;
            $remaining = max(0, $totalCount - ($offset + $limit));
            
            // HTML이 비어있으면 실패로 처리
            if (empty($htmlFragments) && count($phones) > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'HTML generation failed',
                    'html' => [],
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $totalCount,
                        'hasMore' => $hasMore,
                        'remaining' => $remaining
                    ]
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
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
    }

    $hasMore = ($offset + $limit) < $totalCount;
    $remaining = max(0, $totalCount - ($offset + $limit));

    // JSON 형식으로 반환 (기본값 - 사용되지 않음)
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

} catch (Throwable $e) {
    http_response_code(500);
    $errorMessage = $e->getMessage();
    $errorFile = $e->getFile();
    $errorLine = $e->getLine();
    $errorTrace = $e->getTraceAsString();
    
    error_log("load-more-products.php 에러: " . $errorMessage);
    error_log("파일: " . $errorFile . ", 라인: " . $errorLine);
    error_log("스택 트레이스: " . $errorTrace);
    
    // 개발 환경에서는 상세 에러 정보 반환
    $isDevelopment = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false);
    
    echo json_encode([
        'success' => false,
        'message' => '데이터를 불러오는 중 오류가 발생했습니다.',
        'error' => $errorMessage,
        'file' => $errorFile,
        'line' => $errorLine,
        'trace' => $isDevelopment ? $errorTrace : null
    ], JSON_UNESCAPED_UNICODE);
}


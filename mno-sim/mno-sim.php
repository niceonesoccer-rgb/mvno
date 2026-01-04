<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mno-sim';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

// 로그인 체크를 위한 auth-functions 포함
require_once '../includes/data/auth-functions.php';
require_once '../includes/data/db-config.php';
require_once '../includes/data/product-functions.php';
require_once '../includes/data/privacy-functions.php';

// 개인정보 설정 로드
$privacySettings = getPrivacySettings();

// 헤더에 CSS 추가를 위한 플래그 설정
$add_inline_css = true;
// 헤더 포함
include '../includes/header.php';

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

// 필터 파라미터
$filterProvider = $_GET['provider'] ?? '';
$filterServiceType = $_GET['service_type'] ?? '';
$filterPromotion = isset($_GET['promotion']) && $_GET['promotion'] === '1';

// 페이지 번호 가져오기
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// 데이터베이스에서 통신사단독유심 상품 목록 가져오기
$mnoSimProducts = [];
$providers = [];
$serviceTypes = [];
$advertisementProducts = []; // 광고중 상품 목록
$advertisementProductIds = []; // 광고중 상품 ID 목록 (중복 체크용)
$rotationDuration = null; // DB에서 가져온 값만 사용

try {
    $pdo = getDBConnection();
    if ($pdo) {
        // system_settings에서 로테이션 시간 가져오기 (DB에 반드시 있어야 함)
        try {
            $durationStmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'advertisement_rotation_duration'");
            $durationStmt->execute();
            $durationValue = $durationStmt->fetchColumn();
            if ($durationValue) {
                $rotationDuration = intval($durationValue);
                // 디버깅: DB에서 가져온 값 확인
                // error_log('DB에서 가져온 로테이션 시간: ' . $rotationDuration . '초');
            } else {
                // DB에 값이 없으면 로그만 남기고 0으로 설정 (로테이션 안 함)
                error_log('경고: advertisement_rotation_duration 설정이 DB에 없습니다. 관리자 페이지에서 설정해주세요.');
                $rotationDuration = 0;
            }
        } catch (PDOException $e) {
            error_log('Rotation duration 조회 오류: ' . $e->getMessage());
            $rotationDuration = 0;
        }
        
        // 광고중인 상품 조회 (최상단 로테이션용)
        // product_type은 DB에서 'mno_sim' (언더스코어)로 저장됨
        // 필터 조건 추가: 필터가 설정되었을 때만 스폰서 상품도 필터링
        $adWhereConditions = [
            "ra.product_type = 'mno_sim'",
            "ra.status = 'active'",
            "p.status = 'active'",
            "ra.end_datetime > NOW()"
        ];
        $adParams = [];
        
        // 통신사 필터
        if (!empty($filterProvider)) {
            $adWhereConditions[] = 'mno_sim.provider = :ad_provider';
            $adParams[':ad_provider'] = $filterProvider;
        }
        
        // 데이터 속도 필터
        if (!empty($filterServiceType)) {
            $adWhereConditions[] = 'mno_sim.service_type = :ad_service_type';
            $adParams[':ad_service_type'] = $filterServiceType;
        }
        
        // 프로모션 필터 (프로모션 기간이 있는 상품만 표시)
        if ($filterPromotion) {
            $adWhereConditions[] = "(
                (mno_sim.discount_period IS NOT NULL 
                 AND mno_sim.discount_period != '' 
                 AND mno_sim.discount_period != '프로모션 없음')
                OR mno_sim.price_after > 0
            )";
        }
        
        $adWhereClause = 'WHERE ' . implode(' AND ', $adWhereConditions);
        
        $adStmt = $pdo->prepare("
            SELECT 
                ra.product_id,
                ra.rotation_duration,
                ra.created_at,
                ra.start_datetime,
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
            FROM rotation_advertisements ra
            INNER JOIN products p ON ra.product_id = p.id
            INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
            {$adWhereClause}
            ORDER BY ra.display_order ASC, ra.created_at ASC
        ");
        foreach ($adParams as $key => $value) {
            $adStmt->bindValue($key, $value);
        }
        $adStmt->execute();
        $advertisementProductsRaw = $adStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 서버 사이드 로테이션: 페이지 로드 시점에 순서 계산
        // 동작 방식: 매 N초마다 첫 번째 상품이 맨 뒤로 이동
        // 예: [1,2,3] → 6초 후 [2,3,1] → 6초 후 [3,1,2] → 6초 후 [1,2,3]
        // DB에서 가져온 rotationDuration 값이 있어야만 로테이션 수행
        if (count($advertisementProductsRaw) > 1 && $rotationDuration !== null && $rotationDuration > 0) {
            // 한국 시간대 설정
            date_default_timezone_set('Asia/Seoul');
            
            // 오늘 자정(00:00:00)을 기준으로 경과 시간 계산
            $today = date('Y-m-d');
            $baseTimestamp = strtotime($today . ' 00:00:00');
            $currentTimestamp = time();
            $elapsedSeconds = $currentTimestamp - $baseTimestamp;
            
            // 몇 번 회전했는지 계산 (경과 시간 / 로테이션 시간)
            $rotationCycles = floor($elapsedSeconds / $rotationDuration);
            
            // 광고 개수로 나눈 나머지 = 실제 회전 횟수
            $adCount = count($advertisementProductsRaw);
            $rotationOffset = $rotationCycles % $adCount;
            
            // 회전 횟수만큼 첫 번째 상품을 맨 뒤로 이동
            if ($rotationOffset > 0) {
                $advertisementProductsRaw = array_merge(
                    array_slice($advertisementProductsRaw, $rotationOffset),
                    array_slice($advertisementProductsRaw, 0, $rotationOffset)
                );
            }
        }
        
        // 광고 상품 데이터에 판매자명 추가 및 HTML 렌더링
        // plan-data.php를 먼저 로드 (getProductAverageRating 함수 필요)
        require_once __DIR__ . '/../includes/data/plan-data.php';
        require_once __DIR__ . '/../includes/data/product-functions.php';
        
        // 판매자 정보 배치 조회 (성능 최적화: 30개 상품 × 100명 접속 = 3,000개 쿼리 → 1개 쿼리로 감소)
        $sellerIds = [];
        foreach ($advertisementProductsRaw as $adProduct) {
            $sellerId = (string)($adProduct['seller_id'] ?? '');
            if ($sellerId !== '') {
                $sellerIds[$sellerId] = true;
            }
        }
        
        $sellerMap = [];
        if (!empty($sellerIds)) {
            $idList = array_keys($sellerIds);
            $placeholders = implode(',', array_fill(0, count($idList), '?'));
            $sellerStmt = $pdo->prepare("
                SELECT
                    u.user_id,
                    u.seller_name,
                    u.company_name,
                    u.name
                FROM users u
                WHERE u.role = 'seller'
                  AND u.user_id IN ($placeholders)
            ");
            $sellerStmt->execute($idList);
            foreach ($sellerStmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
                $sellerMap[(string)$s['user_id']] = $s;
            }
        }
        
        $advertisementProducts = [];
        foreach ($advertisementProductsRaw as $adProduct) {
            // 판매자명 가져오기 (배치 조회 결과 사용)
            $sellerId = (string)($adProduct['seller_id'] ?? '');
            $sellerName = '';
            if ($sellerId && isset($sellerMap[$sellerId])) {
                $seller = $sellerMap[$sellerId];
                // getSellerDisplayName 로직: seller_name > company_name > name
                $sellerName = !empty($seller['seller_name']) 
                    ? $seller['seller_name'] 
                    : (!empty($seller['company_name']) 
                        ? $seller['company_name'] 
                        : (!empty($seller['name']) ? $seller['name'] : $sellerId));
            }
            
            // 광고 상품용 plan 배열 구성 (일반 상품과 동일한 로직 사용)
            $adProductData = $adProduct;
            
            // provider 필드에 판매자명 사용 (통신사명 대신)
            $displayProvider = !empty($sellerName) ? $sellerName : ($adProduct['provider'] ?? '판매자 정보 없음');
            
            // 알뜰폰 카드 형식에 맞게 데이터 변환
            $provider = htmlspecialchars($adProduct['provider'] ?? '');
            $serviceType = htmlspecialchars($adProduct['service_type'] ?? '');
            $planName = htmlspecialchars($adProduct['plan_name'] ?? '');
            $contractPeriod = htmlspecialchars($adProduct['contract_period'] ?? '');
            $contractPeriodValue = $adProduct['contract_period_discount_value'] ?? null;
            $contractPeriodUnit = $adProduct['contract_period_discount_unit'] ?? '';
            
            // 제목 구성
            $title = $provider . ' | ' . $serviceType;
            if ($contractPeriod) {
                $discountMethod = $contractPeriod;
                if ($contractPeriodValue && $contractPeriodUnit) {
                    $discountMethod .= ' ' . $contractPeriodValue . $contractPeriodUnit;
                }
                $title .= ' | ' . $discountMethod;
            }
            
            // 데이터 정보 포맷팅
            $dataAmount = formatDataAmount(
                $adProduct['data_amount'] ?? '',
                $adProduct['data_amount_value'] ?? null,
                $adProduct['data_unit'] ?? ''
            );
            $dataAdditional = !empty($adProduct['data_additional_value']) 
                ? htmlspecialchars($adProduct['data_additional_value']) 
                : (!empty($adProduct['data_additional']) && $adProduct['data_additional'] !== '없음' 
                    ? htmlspecialchars($adProduct['data_additional']) : '');
            $dataExhausted = !empty($adProduct['data_exhausted_value']) 
                ? htmlspecialchars($adProduct['data_exhausted_value'])
                : (!empty($adProduct['data_exhausted']) 
                    ? htmlspecialchars($adProduct['data_exhausted']) : '');
            
            $dataMain = $dataAmount;
            if (!empty($dataAdditional)) {
                $dataMain .= ' + ' . $dataAdditional;
            }
            if (!empty($dataExhausted)) {
                $dataMain .= ' +' . $dataExhausted;
            }
            
            // 기능 포맷팅
            $callAmount = formatCallAmount(
                $adProduct['call_type'] ?? '',
                $adProduct['call_amount'] ?? null,
                $adProduct['call_amount_unit'] ?? ''
            );
            $features = [];
            if ($callAmount !== '-') {
                $features[] = '통화 ' . $callAmount;
            }
            $smsAmount = formatCallAmount(
                $adProduct['sms_type'] ?? '',
                $adProduct['sms_amount'] ?? null,
                $adProduct['sms_amount_unit'] ?? ''
            );
            if ($smsAmount !== '-') {
                $features[] = '문자 ' . $smsAmount;
            }
            
            // 가격 정보
            $regularPriceValue = (int)$adProduct['price_main'];
            $promotionPriceValue = (int)$adProduct['price_after'];
            $discountPeriod = formatDiscountPeriod(
                $adProduct['discount_period'] ?? '',
                $adProduct['discount_period_value'] ?? null,
                $adProduct['discount_period_unit'] ?? ''
            );
            
            $hasPromotion = ($promotionPriceValue > 0);
            if ($hasPromotion) {
                $priceMain = '월 ' . number_format($promotionPriceValue) . ($adProduct['price_after_unit'] ?: '원');
                $priceAfter = '';
                if ($regularPriceValue > 0 && $discountPeriod !== '-') {
                    $priceAfter = $discountPeriod . ' 후 월 ' . number_format($regularPriceValue) . ($adProduct['price_main_unit'] ?: '원');
                } elseif ($regularPriceValue > 0) {
                    $priceAfter = '월 ' . number_format($regularPriceValue) . ($adProduct['price_main_unit'] ?: '원');
                }
            } else {
                if ($regularPriceValue > 0) {
                    $priceMain = '월 ' . number_format($regularPriceValue) . ($adProduct['price_main_unit'] ?: '원');
                } else {
                    $priceMain = '월 0원';
                }
                $priceAfter = '';
            }
            
            // 선택 수
            $applicationCount = (int)($adProduct['application_count'] ?? 0);
            $selectionCount = number_format($applicationCount) . '명이 선택';
            
            // 평점
            $productIdForRating = (int)$adProduct['product_id'];
            $averageRating = getProductAverageRating($productIdForRating, 'mno-sim');
            $rating = ($averageRating > 0) ? number_format($averageRating, 1) : '';
            
            // 혜택
            $gifts = [];
            $promotionTitle = $adProduct['promotion_title'] ?? '';
            if (!empty($adProduct['promotions'])) {
                $promotions = json_decode($adProduct['promotions'], true);
                if (is_array($promotions)) {
                    $gifts = array_filter($promotions, function($p) {
                        return !empty(trim($p));
                    });
                }
            }
            if (empty($gifts) && !empty($adProduct['benefits'])) {
                $benefits = json_decode($adProduct['benefits'], true);
                if (is_array($benefits)) {
                    $gifts = array_filter($benefits, function($b) {
                        return !empty(trim($b));
                    });
                }
            }
            
            // plan 배열 구성
            $plan = [
                'id' => $adProduct['product_id'],
                'provider' => $displayProvider,
                'rating' => $rating,
                'title' => $title,
                'plan_name' => $planName,
                'data_main' => $dataMain,
                'features' => $features,
                'price_main' => $priceMain,
                'price_after' => $priceAfter,
                'selection_count' => $selectionCount,
                'is_favorited' => false,
                'gifts' => $gifts,
                'promotion_title' => $promotionTitle,
                'link_url' => '/MVNO/mno-sim/mno-sim-detail.php?id=' . $adProduct['product_id'],
                'item_type' => 'mno-sim',
                'is_advertising' => true // 스폰서 배지 표시용
            ];
            
            // HTML 렌더링 (출력 버퍼 사용) - 일반 카드와 동일하게
            ob_start();
            $card_wrapper_class = ''; // 일반 카드와 동일하게
            $layout_type = 'list';
            include __DIR__ . '/../includes/components/plan-card.php';
            $adProductHtml = ob_get_clean();
            
            // JSON에 HTML 포함
            $adProduct['html'] = $adProductHtml;
            $advertisementProducts[] = $adProduct;
        }
        
        // 광고중 상품 ID 목록 생성
        foreach ($advertisementProducts as $adProduct) {
            $advertisementProductIds[] = (int)$adProduct['product_id'];
        }
        
        // 고객용 페이지이므로 관리자여도 active 상태만 표시
        $statusCondition = "p.status = 'active'";
        
        // WHERE 조건 구성
        $whereConditions = ["p.product_type = 'mno-sim'", $statusCondition];
        $params = [];
        
        // 통신사 필터
        if (!empty($filterProvider)) {
            $whereConditions[] = 'mno_sim.provider = :provider';
            $params[':provider'] = $filterProvider;
        }
        
        // 데이터 속도 필터
        if (!empty($filterServiceType)) {
            $whereConditions[] = 'mno_sim.service_type = :service_type';
            $params[':service_type'] = $filterServiceType;
        }
        
        // 프로모션 필터 (프로모션 기간이 있는 상품만 표시)
        if ($filterPromotion) {
            $whereConditions[] = "(
                (mno_sim.discount_period IS NOT NULL 
                 AND mno_sim.discount_period != '' 
                 AND mno_sim.discount_period != '프로모션 없음')
                OR mno_sim.price_after > 0
            )";
        }
        
        // 스폰서 상품 제외 (스폰서 상품은 별도 섹션에만 표시되므로 일반 리스트에서는 제외)
        $whereConditions[] = "NOT EXISTS (
            SELECT 1 FROM rotation_advertisements ra 
            WHERE ra.product_id = p.id 
            AND ra.product_type = 'mno_sim'
            AND ra.status = 'active' 
            AND ra.end_datetime > NOW()
        )";
        
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        
        // 전체 개수 조회 (스폰서 상품 제외한 일반 상품 개수만 계산)
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
        $totalCount = intval($countStmt->fetch(PDO::FETCH_ASSOC)['total']);
        
        // 상품 목록 조회 (LIMIT/OFFSET 추가)
        // 스폰서 상품은 제외하고 일반 상품만 조회 (스폰서 상품은 별도 섹션에만 표시)
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
                mno_sim.benefits,
                0 AS is_advertising
            FROM products p
            INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
            {$whereClause}
            ORDER BY p.updated_at DESC, p.id DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $mnoSimProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 필터 옵션 수집 (6G 제외)
        $filterStmt = $pdo->prepare("
            SELECT DISTINCT 
                mno_sim.provider,
                mno_sim.service_type
            FROM products p
            INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
            WHERE p.product_type = 'mno-sim' 
            AND {$statusCondition}
            AND mno_sim.service_type != '6G'
            ORDER BY mno_sim.provider, mno_sim.service_type
        ");
        $filterStmt->execute();
        $filterOptions = $filterStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($filterOptions as $option) {
            if (!empty($option['provider']) && !in_array($option['provider'], $providers)) {
                $providers[] = $option['provider'];
            }
            if (!empty($option['service_type']) && $option['service_type'] !== '6G' && !in_array($option['service_type'], $serviceTypes)) {
                $serviceTypes[] = $option['service_type'];
            }
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching MNO-SIM products: " . $e->getMessage());
}

// plan-data.php는 광고 상품 처리 전에 먼저 로드 (getSellerById 함수 필요)
require_once __DIR__ . '/../includes/data/plan-data.php';

// 통신사별 아이콘 경로 매핑
function getProviderIconPath($provider) {
    $iconMap = [
        'KT' => '/MVNO/assets/images/internets/kt.svg',
        'SKT' => '/MVNO/assets/images/internets/broadband.svg',
        'LG U+' => '/MVNO/assets/images/internets/lgu.svg',
    ];
    return $iconMap[$provider] ?? '';
}

// 데이터 포맷팅 함수
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

function formatPriceAfter($priceAfterType, $priceAfter, $priceAfterUnit) {
    if ($priceAfterType === 'none' || $priceAfter === null) {
        return '-';
    } elseif ($priceAfterType === 'free' || $priceAfter == 0) {
        return '무료';
    } elseif (!empty($priceAfter)) {
        $unit = $priceAfterUnit ?: '원';
        return number_format((int)$priceAfter) . $unit;
    }
    return '-';
}
?>


<main class="main-content">
    <!-- 필터 섹션 -->
    <div class="plans-filter-section">
        <div class="plans-filter-inner">
            <div class="plans-filter-group">
                <div class="plans-filter-row">
                    <?php 
                    // 기본값 상태 확인: 모든 필터가 비어있을 때
                    $isDefaultState = empty($filterProvider) && empty($filterServiceType) && !$filterPromotion;
                    ?>
                    <button class="plans-filter-btn <?php echo $isDefaultState ? 'active' : ''; ?>" onclick="clearFilters()">
                        <span class="plans-filter-text">전체</span>
                    </button>
                    
                    <?php if (!empty($providers)): ?>
                        <?php foreach ($providers as $provider): ?>
                            <button class="plans-filter-btn <?php echo ($filterProvider === $provider) ? 'active' : ''; ?>" 
                                    onclick="filterByProvider('<?php echo htmlspecialchars($provider); ?>')">
                                <span class="plans-filter-text"><?php echo htmlspecialchars($provider); ?></span>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($serviceTypes)): ?>
                        <?php foreach ($serviceTypes as $serviceType): ?>
                            <button class="plans-filter-btn <?php echo ($filterServiceType === $serviceType) ? 'active' : ''; ?>" 
                                    onclick="filterByServiceType('<?php echo htmlspecialchars($serviceType); ?>')">
                                <span class="plans-filter-text"><?php echo htmlspecialchars($serviceType); ?></span>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <!-- 프로모션 필터 -->
                    <button class="plans-filter-btn <?php echo $filterPromotion ? 'active' : ''; ?>" 
                            onclick="filterByPromotion()">
                        <span class="plans-filter-text">프로모션</span>
                    </button>
                </div>
            </div>
        </div>
        <hr class="plans-filter-divider">
    </div>

    <div class="content-layout">
        <div class="plans-main-layout">
            <div class="plans-left-section">
                <section class="theme-plans-list-section">
        
        <!-- 결과 개수 표시 -->
        <?php if (!empty($mnoSimProducts) || $totalCount > 0): ?>
            <div class="plans-results-count">
                <span><?php echo number_format($totalCount); ?>개 검색</span>
            </div>
        <?php endif; ?>
        
        <!-- 카드 그리드 -->
        <?php if (empty($mnoSimProducts)): ?>
            <div style="text-align: center; padding: 60px 20px; color: #6b7280;">
                <p>등록된 통신사단독유심 상품이 없습니다.</p>
            </div>
        <?php else: ?>
            <div class="plans-list-container" id="mno-sim-products-container">
                <!-- 최상단 광고 로테이션 섹션 - 모든 광고 상품 표시 -->
                <?php if (!empty($advertisementProducts)): ?>
                <?php foreach ($advertisementProducts as $index => $adProduct): ?>
                    <div class="advertisement-card-item" data-ad-index="<?php echo $index; ?>">
                            <?php
                            // 광고 상품용 plan 배열 구성
                            $sellerId = $adProduct['seller_id'] ?? null;
                            $sellerName = '';
                            if ($sellerId) {
                                $seller = getSellerById($sellerId);
                                $sellerName = getSellerDisplayName($seller);
                            }
                            
                            $displayProvider = !empty($sellerName) ? $sellerName : ($adProduct['provider'] ?? '판매자 정보 없음');
                            $provider = htmlspecialchars($adProduct['provider'] ?? '');
                            $serviceType = htmlspecialchars($adProduct['service_type'] ?? '');
                            $planName = htmlspecialchars($adProduct['plan_name'] ?? '');
                            $contractPeriod = htmlspecialchars($adProduct['contract_period'] ?? '');
                            $contractPeriodValue = $adProduct['contract_period_discount_value'] ?? null;
                            $contractPeriodUnit = $adProduct['contract_period_discount_unit'] ?? '';
                            
                            $title = $provider . ' | ' . $serviceType;
                            if ($contractPeriod) {
                                $discountMethod = $contractPeriod;
                                if ($contractPeriodValue && $contractPeriodUnit) {
                                    $discountMethod .= ' ' . $contractPeriodValue . $contractPeriodUnit;
                                }
                                $title .= ' | ' . $discountMethod;
                            }
                            
                            $dataAmount = formatDataAmount(
                                $adProduct['data_amount'] ?? '',
                                $adProduct['data_amount_value'] ?? null,
                                $adProduct['data_unit'] ?? ''
                            );
                            $dataAdditional = !empty($adProduct['data_additional_value']) 
                                ? htmlspecialchars($adProduct['data_additional_value']) 
                                : (!empty($adProduct['data_additional']) && $adProduct['data_additional'] !== '없음' 
                                    ? htmlspecialchars($adProduct['data_additional']) : '');
                            $dataExhausted = !empty($adProduct['data_exhausted_value']) 
                                ? htmlspecialchars($adProduct['data_exhausted_value'])
                                : (!empty($adProduct['data_exhausted']) 
                                    ? htmlspecialchars($adProduct['data_exhausted']) : '');
                            
                            $dataMain = $dataAmount;
                            if (!empty($dataAdditional)) {
                                $dataMain .= ' + ' . $dataAdditional;
                            }
                            if (!empty($dataExhausted)) {
                                $dataMain .= ' +' . $dataExhausted;
                            }
                            
                            $callAmount = formatCallAmount(
                                $adProduct['call_type'] ?? '',
                                $adProduct['call_amount'] ?? null,
                                $adProduct['call_amount_unit'] ?? ''
                            );
                            $features = [];
                            if ($callAmount !== '-') {
                                $features[] = '통화 ' . $callAmount;
                            }
                            $smsAmount = formatCallAmount(
                                $adProduct['sms_type'] ?? '',
                                $adProduct['sms_amount'] ?? null,
                                $adProduct['sms_amount_unit'] ?? ''
                            );
                            if ($smsAmount !== '-') {
                                $features[] = '문자 ' . $smsAmount;
                            }
                            
                            $regularPriceValue = (int)$adProduct['price_main'];
                            $promotionPriceValue = (int)$adProduct['price_after'];
                            $discountPeriod = formatDiscountPeriod(
                                $adProduct['discount_period'] ?? '',
                                $adProduct['discount_period_value'] ?? null,
                                $adProduct['discount_period_unit'] ?? ''
                            );
                            
                            $hasPromotion = ($promotionPriceValue > 0);
                            if ($hasPromotion) {
                                $priceMain = '월 ' . number_format($promotionPriceValue) . ($adProduct['price_after_unit'] ?: '원');
                                $priceAfter = '';
                                if ($regularPriceValue > 0 && $discountPeriod !== '-') {
                                    $priceAfter = $discountPeriod . ' 후 월 ' . number_format($regularPriceValue) . ($adProduct['price_main_unit'] ?: '원');
                                } elseif ($regularPriceValue > 0) {
                                    $priceAfter = '월 ' . number_format($regularPriceValue) . ($adProduct['price_main_unit'] ?: '원');
                                }
                            } else {
                                if ($regularPriceValue > 0) {
                                    $priceMain = '월 ' . number_format($regularPriceValue) . ($adProduct['price_main_unit'] ?: '원');
                                } else {
                                    $priceMain = '월 0원';
                                }
                                $priceAfter = '';
                            }
                            
                            $applicationCount = (int)($adProduct['application_count'] ?? 0);
                            $selectionCount = number_format($applicationCount) . '명이 선택';
                            
                            $productIdForRating = (int)$adProduct['product_id'];
                            $averageRating = getProductAverageRating($productIdForRating, 'mno-sim');
                            $rating = ($averageRating > 0) ? number_format($averageRating, 1) : '';
                            
                            $gifts = [];
                            $promotionTitle = $adProduct['promotion_title'] ?? '';
                            if (!empty($adProduct['promotions'])) {
                                $promotions = json_decode($adProduct['promotions'], true);
                                if (is_array($promotions)) {
                                    $gifts = array_filter($promotions, function($p) {
                                        return !empty(trim($p));
                                    });
                                }
                            }
                            if (empty($gifts) && !empty($adProduct['benefits'])) {
                                $benefits = json_decode($adProduct['benefits'], true);
                                if (is_array($benefits)) {
                                    $gifts = array_filter($benefits, function($b) {
                                        return !empty(trim($b));
                                    });
                                }
                            }
                            
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
                                            ':product_id' => $adProduct['product_id'],
                                            ':user_id' => $currentUserId
                                        ]);
                                        $favResult = $favStmt->fetch(PDO::FETCH_ASSOC);
                                        $isFavorited = ($favResult['count'] ?? 0) > 0;
                                    } catch (Exception $e) {
                                        // 에러 무시
                                    }
                                }
                            }
                            
                            $plan = [
                                'id' => $adProduct['product_id'],
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
                                'link_url' => '/MVNO/mno-sim/mno-sim-detail.php?id=' . $adProduct['product_id'],
                                'item_type' => 'mno-sim',
                                'is_advertising' => true
                            ];
                            
                            $card_wrapper_class = '';
                            $layout_type = 'list';
                            include __DIR__ . '/../includes/components/plan-card.php';
                            ?>
                            <!-- 카드 구분선 -->
                            <hr class="plan-card-divider">
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php foreach ($mnoSimProducts as $product): ?>
                    <div class="regular-card-item">
                    <?php
                    // 판매자명 가져오기 (알뜰폰 카드와 동일하게)
                    $sellerId = $product['seller_id'] ?? null;
                    $sellerName = '';
                    if ($sellerId) {
                        $seller = getSellerById($sellerId);
                        $sellerName = getSellerDisplayName($seller);
                    }
                    
                    // provider 필드에 판매자명 사용 (통신사명 대신)
                    $displayProvider = !empty($sellerName) ? $sellerName : ($product['provider'] ?? '판매자 정보 없음');
                    
                    // 알뜰폰 카드 형식에 맞게 데이터 변환
                    $provider = htmlspecialchars($product['provider'] ?? ''); // 통신사명 (제목에 사용)
                    $serviceType = htmlspecialchars($product['service_type'] ?? '');
                    $planName = htmlspecialchars($product['plan_name'] ?? '');
                    $contractPeriod = htmlspecialchars($product['contract_period'] ?? '');
                    $contractPeriodValue = $product['contract_period_discount_value'] ?? null;
                    $contractPeriodUnit = $product['contract_period_discount_unit'] ?? '';
                    
                    // 할인방법 표시: "선택약정할인 24개월" 형식 (원본 그대로)
                    $discountMethod = $contractPeriod;
                    if (!empty($contractPeriodValue) && !empty($contractPeriodUnit)) {
                        $discountMethod .= ' ' . $contractPeriodValue . $contractPeriodUnit;
                    }
                    
                    // 제목: "KT | LTE | 선택약정 24개월" 형식 (통신사명 사용)
                    $title = $provider . ' | ' . $serviceType;
                    if (!empty($discountMethod)) {
                        $title .= ' | ' . $discountMethod;
                    }
                    
                    // 요금제명은 별도로 표시 (plan-name 필드 추가)
                    $planNameDisplay = $planName;
                    
                    // 데이터 정보: "100GB + 매일10G +10Mbps 무제한" 형식
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
                    
                    // 기능: "통화 150분 | 문자 50건 | 부가영상통화 300분" 형식
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
                    // 통신사단독유심: price_main = 원래 요금, price_after = 프로모션 금액
                    $regularPriceValue = (int)$product['price_main']; // 원래 요금
                    $promotionPriceValue = (int)$product['price_after']; // 프로모션 금액
                    $discountPeriod = formatDiscountPeriod(
                        $product['discount_period'] ?? '',
                        $product['discount_period_value'] ?? null,
                        $product['discount_period_unit'] ?? ''
                    );
                    
                    // 프로모션 금액이 있는 경우와 없는 경우를 구분
                    $hasPromotion = ($promotionPriceValue > 0);
                    
                    if ($hasPromotion) {
                        // 프로모션 금액이 있는 경우:
                        // 1. 프로모션 금액 (위) - 프로모션기간 동안 부과되는 금액
                        $priceMain = '월 ' . number_format($promotionPriceValue) . ($product['price_after_unit'] ?: '원');
                        
                        // 2. 프로모션기간 후 원래 요금 (아래) - "프로모션기간 후 월 원래요금" 형식
                        $priceAfter = '';
                        if ($regularPriceValue > 0 && $discountPeriod !== '-') {
                            $priceAfter = $discountPeriod . ' 후 월 ' . number_format($regularPriceValue) . ($product['price_main_unit'] ?: '원');
                        } elseif ($regularPriceValue > 0) {
                            $priceAfter = '월 ' . number_format($regularPriceValue) . ($product['price_main_unit'] ?: '원');
                        }
                    } else {
                        // 프로모션 금액이 없는 경우:
                        // 원래 요금만 표시 (위) - "월" 붙임
                        if ($regularPriceValue > 0) {
                            $priceMain = '월 ' . number_format($regularPriceValue) . ($product['price_main_unit'] ?: '원');
                            $priceAfter = ''; // 아래는 표시하지 않음
                        } else {
                            $priceMain = '월 0원';
                            $priceAfter = '';
                        }
                    }
                    
                    // 디버깅 (개발 중에만)
                    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
                        echo "<!-- 가격 디버깅: 상품 ID {$product['id']} -->";
                        echo "<!-- priceMainValue: {$priceMainValue} -->";
                        echo "<!-- priceAfterValue: " . htmlspecialchars($priceAfterValue) . " -->";
                        echo "<!-- discountPeriod: " . htmlspecialchars($discountPeriod) . " -->";
                        echo "<!-- hasPromotion: " . ($hasPromotion ? 'true' : 'false') . " -->";
                        echo "<!-- priceMain: " . htmlspecialchars($priceMain) . " -->";
                        echo "<!-- priceAfter: " . htmlspecialchars($priceAfter) . " -->";
                    }
                    
                    // 할인기간 정보는 더 이상 별도로 표시하지 않음 (price_after에 포함)
                    
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
                    // 별점이 있으면 표시 (0보다 큰 경우만)
                    $rating = ($averageRating > 0) ? number_format($averageRating, 1) : '';
                    
                    // 혜택 데이터 가져오기 (promotions 또는 benefits)
                    $gifts = [];
                    $promotionTitle = $product['promotion_title'] ?? '';
                    
                    // 디버깅: 원본 데이터 확인
                    $debugPromotions = $product['promotions'] ?? null;
                    $debugBenefits = $product['benefits'] ?? null;
                    
                    // promotions 필드가 있으면 사용 (JSON 형식)
                    if (!empty($product['promotions'])) {
                        $promotions = json_decode($product['promotions'], true);
                        if (is_array($promotions)) {
                            $gifts = array_filter($promotions, function($p) {
                                return !empty(trim($p));
                            });
                        }
                    }
                    
                    // benefits 필드가 있으면 사용 (JSON 형식)
                    if (empty($gifts) && !empty($product['benefits'])) {
                        $benefits = json_decode($product['benefits'], true);
                        if (is_array($benefits)) {
                            $gifts = array_filter($benefits, function($b) {
                                return !empty(trim($b));
                            });
                        }
                    }
                    
                    // 디버깅 정보 출력 (개발 중에만)
                    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
                        echo "<!-- 디버깅: 상품 ID {$product['id']} -->";
                        echo "<!-- promotions 원본: " . htmlspecialchars($debugPromotions ?? 'null') . " -->";
                        echo "<!-- benefits 원본: " . htmlspecialchars($debugBenefits ?? 'null') . " -->";
                        echo "<!-- promotion_title: " . htmlspecialchars($promotionTitle) . " -->";
                        echo "<!-- gifts 개수: " . count($gifts) . " -->";
                        echo "<!-- gifts 내용: " . htmlspecialchars(json_encode($gifts, JSON_UNESCAPED_UNICODE)) . " -->";
                    }
                    
                    // 광고중 여부 확인
                    $isAdvertising = isset($product['is_advertising']) && (int)$product['is_advertising'] === 1;
                    
                    // plan 배열 구성 (알뜰폰 카드 형식)
                    $plan = [
                        'id' => $product['id'],
                        'provider' => $displayProvider, // 판매자명 사용 (알뜰폰과 동일)
                        'rating' => $rating,
                        'title' => $title,
                        'plan_name' => $planName, // 요금제명 추가
                        'data_main' => $dataMain,
                        'features' => $features,
                        'price_main' => $priceMain, // 프로모션 금액 (프로모션기간 동안 부과되는 금액, 0이면 "공짜")
                        'price_after' => $priceAfter, // 프로모션기간 월요금 (프로모션기간 후 부과되는 원래 요금, 예: "7개월 후 2,000원")
                        'selection_count' => $selectionCount,
                        'is_favorited' => $isFavorited,
                        'gifts' => $gifts, // 혜택 목록
                        'promotion_title' => $promotionTitle, // 프로모션 제목
                        'link_url' => '/MVNO/mno-sim/mno-sim-detail.php?id=' . $product['id'], // 상세 페이지 링크
                        'item_type' => 'mno-sim', // 찜 버튼용 타입
                        'is_advertising' => $isAdvertising // 광고중 여부
                    ];
                    
                    // 알뜰폰 카드 컴포넌트 사용
                    $card_wrapper_class = '';
                    $layout_type = 'list';
                    include __DIR__ . '/../includes/components/plan-card.php';
                    ?>
                    
                    <!-- 카드 구분선 (모바일용) -->
                    <hr class="plan-card-divider">
                    </div>
                <?php endforeach; ?>
                <?php 
                $currentCount = count($mnoSimProducts);
                $remainingCount = max(0, $totalCount - ($offset + $currentCount));
                $nextPage = $page + 1;
                $hasMore = ($offset + $currentCount) < $totalCount;
                ?>
                <?php if ($hasMore && $totalCount > 0): ?>
                <div class="load-more-container" id="load-more-anchor">
                    <button id="load-more-mno-sim-btn" class="load-more-btn" 
                        data-type="mno-sim" 
                        data-page="2" 
                        data-total="<?php echo $totalCount; ?>" 
                        <?php echo !empty($filterProvider) ? ' data-provider="' . htmlspecialchars($filterProvider) . '"' : ''; ?>
                        <?php echo !empty($filterServiceType) ? ' data-service-type="' . htmlspecialchars($filterServiceType) . '"' : ''; ?>
                        <?php echo $filterPromotion ? ' data-promotion="1"' : ''; ?>>
                        더보기 (<span id="remaining-count"><?php echo number_format($remainingCount); ?></span>개 남음)
                    </button>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

<!-- 아코디언 스크립트 -->
<script src="/MVNO/assets/js/plan-accordion.js"></script>
<!-- 찜하기 스크립트 -->
<script src="/MVNO/assets/js/favorite-heart.js" defer></script>
<!-- 공유 스크립트 -->
<script src="/MVNO/assets/js/share.js" defer></script>
<!-- 더보기 기능 스크립트 -->
<script src="/MVNO/assets/js/load-more-products.js?v=2"></script>
<script>
// 필터 함수들
function filterByProvider(provider) {
    const url = new URL(window.location.href);
    if (url.searchParams.get('provider') === provider) {
        url.searchParams.delete('provider');
    } else {
        url.searchParams.set('provider', provider);
    }
    window.location.href = url.toString();
}

function filterByServiceType(serviceType) {
    const url = new URL(window.location.href);
    if (url.searchParams.get('service_type') === serviceType) {
        url.searchParams.delete('service_type');
    } else {
        url.searchParams.set('service_type', serviceType);
    }
    window.location.href = url.toString();
}

function filterByPromotion() {
    const url = new URL(window.location.href);
    if (url.searchParams.get('promotion') === '1') {
        url.searchParams.delete('promotion');
    } else {
        url.searchParams.set('promotion', '1');
    }
    window.location.href = url.toString();
}

function clearFilters() {
    const url = new URL(window.location.href);
    url.searchParams.delete('provider');
    url.searchParams.delete('service_type');
    url.searchParams.delete('promotion');
    window.location.href = url.toString();
}
</script>

<style>
/* 더보기 버튼 컨테이너 - 카드와 같은 너비 */
.load-more-container {
    width: 100%;
    max-width: 100%;
    padding: 30px 0;
    box-sizing: border-box;
    margin: 0;
}

/* 더보기 버튼 스타일 - 카드와 같은 너비 */
.load-more-btn {
    display: block;
    width: 100%;
    max-width: 100%;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white !important;
    text-decoration: none;
    text-align: center;
    border: none;
    padding: 16px 32px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
    box-sizing: border-box;
}

.load-more-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
}

.load-more-btn:active:not(:disabled) {
    transform: translateY(0);
}

.load-more-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

<!-- 통신사단독유심 전용 하트 클릭 이벤트 차단 스크립트 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 통신사단독유심 페이지 전용: 하트 클릭 시 링크 이동 차단
    function preventLinkNavigationOnFavoriteClick() {
        const favoriteButtons = document.querySelectorAll('.plan-favorite-btn-inline[data-item-type="mno-sim"]');
        const cardLinks = document.querySelectorAll('article.basic-plan-card > a.plan-card-link');
        
        // 각 카드 링크에 이벤트 리스너 추가
        cardLinks.forEach(function(link) {
            // capture phase에서 실행하여 다른 리스너보다 먼저 실행
            link.addEventListener('click', function(e) {
                const clickedElement = e.target;
                const favoriteButton = clickedElement.closest('.plan-favorite-btn-inline');
                const shareButton = clickedElement.closest('[data-share-url]');
                
                if (favoriteButton || shareButton) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    return false;
                }
            }, { capture: true, passive: false });
            
            // mousedown에서도 차단
            link.addEventListener('mousedown', function(e) {
                const clickedElement = e.target;
                const favoriteButton = clickedElement.closest('.plan-favorite-btn-inline');
                
                if (favoriteButton) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    return false;
                }
            }, { capture: true, passive: false });
        });
        
        // 각 찜 버튼에 직접 이벤트 리스너 추가
        favoriteButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                e.stopImmediatePropagation();
            }, { capture: true, passive: false });
        });
    }
    
    // 즉시 실행
    preventLinkNavigationOnFavoriteClick();
    
    // DOM이 완전히 로드된 후에도 다시 실행
    setTimeout(preventLinkNavigationOnFavoriteClick, 100);
    setTimeout(preventLinkNavigationOnFavoriteClick, 500);
    
    // 동적으로 추가된 요소를 위해 MutationObserver 사용
    const observer = new MutationObserver(function(mutations) {
        preventLinkNavigationOnFavoriteClick();
    });
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
</script>

<style>
/* 통신사폰 페이지 아코디언 버튼 높이 조정 (모바일 터치 최적화) */
.plan-accordion-trigger {
    min-height: 48px !important;
    padding: 12px 16px !important;
}

/* 모바일에서 더 큰 터치 영역 확보 */
@media (max-width: 768px) {
    .plan-accordion-trigger {
        min-height: 52px !important;
        padding: 14px 16px !important;
    }
}

/* 광고 로테이션 섹션 스타일 - plans-list-container의 gap 속성이 간격을 담당 */
/* 광고 상품 wrapper를 레이아웃에서 제거하여 plans-list-container의 gap이 직접 자식에 적용되도록 */
#advertisementRotationWrapper {
    display: contents; /* 레이아웃에서 제거하여 자식 요소들이 부모의 gap을 받도록 */
}

/* 광고 상품 순서 로테이션용 스타일 */
.advertisement-card-item {
    position: relative;
    transition: transform 0.5s ease-in-out;
}

/* 광고 상품들 사이 간격 확보 (wrapper가 있을 경우를 대비) */
#advertisementRotationWrapper > .advertisement-card-item:not(:last-child) {
    margin-bottom: var(--spacing-md, 16px);
}

/* 스폰서 배지 스타일 */
.sponsor-text {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-sm); /* .plan-provider-rating-group과 동일한 간격 */
}

.sponsor-badge {
    display: inline-block;
    background: #3b82f6;
    color: white;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
    letter-spacing: 0.5px;
    flex-shrink: 0;
}

.provider-name-text {
    display: inline-block;
}
</style>

<script>
// 아코디언 기능 (plan-accordion.js가 로드되지 않을 경우를 대비한 폴백)
document.addEventListener('DOMContentLoaded', function() {
    // 디버깅 모드 확인 (URL에 ?debug=1이 있을 때만 로그 출력)
    const urlParams = new URLSearchParams(window.location.search);
    const isDebugMode = urlParams.get('debug') === '1';
    
    if (isDebugMode) {
        console.log('=== 통신사단독유심 아코디언 디버깅 ===');
    }
    
    const accordionTriggers = document.querySelectorAll('.plan-accordion-trigger');
    
    if (isDebugMode) {
        console.log('아코디언 버튼 개수:', accordionTriggers.length);
    }
    
    // 이미 이벤트가 바인딩되어 있는지 확인하기 위한 플래그
    const processedTriggers = new Set();
    
    accordionTriggers.forEach((trigger, index) => {
        // 중복 바인딩 방지
        if (processedTriggers.has(trigger)) {
            if (isDebugMode) {
                console.log(`아코디언 ${index + 1}: 이미 처리됨`);
            }
            return;
        }
        processedTriggers.add(trigger);
        
        const accordion = trigger.closest('.plan-accordion');
        const content = accordion ? accordion.querySelector('.plan-accordion-content') : null;
        const arrow = trigger.querySelector('.plan-accordion-arrow');
        
        if (isDebugMode) {
            console.log(`아코디언 ${index + 1}:`, {
                element: trigger,
                ariaExpanded: trigger.getAttribute('aria-expanded'),
                hasContent: !!content,
                initialDisplay: content?.style.display || 'none',
                gifts: accordion?.querySelector('.plan-gifts-text-accordion')?.textContent
            });
        }
        
        // 초기 상태 확인 및 설정
        const initialExpanded = trigger.getAttribute('aria-expanded') === 'true';
        if (content) {
            // 초기 상태가 닫혀있어야 함
            if (initialExpanded && content.style.display !== 'block') {
                content.style.display = 'none';
                trigger.setAttribute('aria-expanded', 'false');
            } else if (!initialExpanded) {
                content.style.display = 'none';
            }
        }
        
        // 카드 링크 확인
        const cardLink = trigger.closest('.plan-card-link');
        const isInsideLink = !!cardLink;
        if (isDebugMode) {
            console.log(`아코디언 ${index + 1} 카드 링크 내부 여부:`, isInsideLink);
        }
        
        // 클릭 이벤트 바인딩 (capture 단계에서 먼저 실행)
        const clickHandler = function(e) {
            // 찜 버튼이나 공유 버튼 클릭 시 아코디언 동작 방지
            const clickedFavorite = e.target.closest('.plan-favorite-btn-inline');
            const clickedShare = e.target.closest('[data-share-url]');
            if (clickedFavorite || clickedShare) {
                return; // 찜/공유 버튼 클릭 시 아코디언 동작하지 않음
            }
            
            if (isDebugMode) {
                console.log('=== 아코디언 클릭 이벤트 발생! ===', index + 1);
                console.log('이벤트 타겟:', e.target);
                console.log('이벤트 currentTarget:', e.currentTarget);
                console.log('이벤트 버블링 단계:', e.eventPhase);
            }
            
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
            const accordion = trigger.closest('.plan-accordion');
            const content = accordion ? accordion.querySelector('.plan-accordion-content') : null;
            const arrow = trigger.querySelector('.plan-accordion-arrow');
            
            if (!content) {
                if (isDebugMode) {
                    console.error('아코디언 콘텐츠를 찾을 수 없습니다.');
                }
                return false;
            }
            
            if (isDebugMode) {
                console.log('아코디언 클릭 처리:', {
                    index: index + 1,
                    beforeState: isExpanded ? '열림' : '닫힘',
                    currentDisplay: content.style.display,
                    willToggleTo: !isExpanded ? '열기' : '닫기'
                });
            }
            
            // aria-expanded 상태 토글
            trigger.setAttribute('aria-expanded', !isExpanded);
            
            // 콘텐츠 표시/숨김
            if (isExpanded) {
                content.style.display = 'none';
                if (arrow) {
                    arrow.style.transform = 'rotate(0deg)';
                }
            } else {
                content.style.display = 'block';
                if (arrow) {
                    arrow.style.transform = 'rotate(180deg)';
                }
            }
            
            if (isDebugMode) {
                console.log('아코디언 토글 완료:', {
                    afterState: !isExpanded ? '열림' : '닫힘',
                    newDisplay: content.style.display
                });
            }
            
            return false;
        };
        
        // capture 단계에서 이벤트 바인딩 (다른 이벤트보다 먼저 실행)
        trigger.addEventListener('click', clickHandler, true);
        
        // 추가: 마우스다운 이벤트도 처리
        trigger.addEventListener('mousedown', function(e) {
            // 찜 버튼이나 공유 버튼 클릭 시 아코디언 동작 방지
            const clickedFavorite = e.target.closest('.plan-favorite-btn-inline');
            const clickedShare = e.target.closest('[data-share-url]');
            if (clickedFavorite || clickedShare) {
                return; // 찜/공유 버튼 클릭 시 아코디언 동작하지 않음
            }
            
            if (isDebugMode) {
                console.log('아코디언 마우스다운:', index + 1);
            }
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
        }, true);
        
        // 추가: 포인터 이벤트도 처리
        trigger.addEventListener('pointerdown', function(e) {
            // 찜 버튼이나 공유 버튼 클릭 시 아코디언 동작 방지
            const clickedFavorite = e.target.closest('.plan-favorite-btn-inline');
            const clickedShare = e.target.closest('[data-share-url]');
            if (clickedFavorite || clickedShare) {
                return; // 찜/공유 버튼 클릭 시 아코디언 동작하지 않음
            }
            
            if (isDebugMode) {
                console.log('아코디언 포인터다운:', index + 1);
            }
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
        }, true);
        
        // 이벤트 리스너가 제대로 바인딩되었는지 확인
        if (isDebugMode) {
            console.log(`아코디언 ${index + 1} 이벤트 바인딩 완료`);
        }
    });
    
    if (isDebugMode) {
        console.log('아코디언 이벤트 바인딩 완료');
    }
});

function filterByProvider(provider) {
    const url = new URL(window.location.href);
    if (url.searchParams.get('provider') === provider) {
        url.searchParams.delete('provider');
    } else {
        url.searchParams.set('provider', provider);
    }
    window.location.href = url.toString();
}

function filterByServiceType(serviceType) {
    const url = new URL(window.location.href);
    if (url.searchParams.get('service_type') === serviceType) {
        url.searchParams.delete('service_type');
    } else {
        url.searchParams.set('service_type', serviceType);
    }
    window.location.href = url.toString();
}

function filterByPromotion() {
    const url = new URL(window.location.href);
    if (url.searchParams.get('promotion') === '1') {
        url.searchParams.delete('promotion');
    } else {
        url.searchParams.set('promotion', '1');
    }
    window.location.href = url.toString();
}

function clearFilters() {
    const url = new URL(window.location.href);
    url.searchParams.delete('provider');
    url.searchParams.delete('service_type');
    url.searchParams.delete('promotion');
    window.location.href = url.toString();
}
</script>


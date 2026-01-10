<?php
$current_page = 'mvno';
$is_main_page = true;

include '../includes/header.php';

require_once '../includes/data/plan-data.php';
require_once '../includes/data/filter-data.php';
require_once __DIR__ . '/mvno-advertisement-helper.php';

// 필터 파라미터 (스폰서 상품 필터링 전에 먼저 설정)
$filterProvider = $_GET['provider'] ?? '';
$filterPriceRange = $_GET['price_range'] ?? '';

// 광고 상품 조회
list($advertisementProducts, $rotationDuration, $advertisementProductIds) = getMvnoAdvertisementProducts();
error_log("[MVNO 페이지] getMvnoAdvertisementProducts() 반환값 - 광고 상품 개수: " . count($advertisementProducts) . ", ID 목록: " . implode(', ', $advertisementProductIds));

// 필터 적용: 스폰서 상품도 필터 조건에 맞는 것만 표시
if (!empty($filterProvider) || !empty($filterPriceRange)) {
    $filteredAdvertisementProducts = [];
    $filteredAdvertisementProductIds = [];
    
    // provider 필터 매핑 (일반 필터와 동일)
    $providerMapping = [
        'SK알뜰폰' => ['SK알뜰폰', 'SK 알뜰폰', 'SKT알뜰폰', 'SKT 알뜰폰', 'SK'],
        'KT알뜰폰' => ['KT알뜰폰', 'KT 알뜰폰', 'KT'],
        'LGU+알뜰폰' => ['LGU+알뜰폰', 'LG U+알뜰폰', 'LG U+ 알뜰폰', 'LGU+ 알뜰폰', 'LG U+', 'LGU+', 'LG알뜰폰']
    ];
    $allowedProviders = !empty($filterProvider) ? ($providerMapping[$filterProvider] ?? [$filterProvider]) : null;
    
    foreach ($advertisementProducts as $adProduct) {
        $matches = true;
        
        // provider 필터
        if (!empty($filterProvider) && $allowedProviders) {
            $productProvider = $adProduct['provider'] ?? '';
            $providerMatch = false;
            foreach ($allowedProviders as $allowedProvider) {
                if ($productProvider === $allowedProvider || strpos($productProvider, $allowedProvider) !== false) {
                    $providerMatch = true;
                    break;
                }
            }
            if (!$providerMatch) {
                $matches = false;
            }
        }
        
        // price_range 필터
        if ($matches && !empty($filterPriceRange)) {
            $priceAfter = isset($adProduct['price_after']) ? (float)$adProduct['price_after'] : 0;
            $priceMatch = false;
            switch ($filterPriceRange) {
                case '1천':
                    $priceMatch = ($priceAfter <= 1000 || $priceAfter == 0);
                    break;
                case '5천':
                    $priceMatch = ($priceAfter > 1000 && $priceAfter <= 5000);
                    break;
                case '1만':
                    $priceMatch = ($priceAfter > 5000 && $priceAfter <= 10000);
                    break;
                case '1.5만':
                    $priceMatch = ($priceAfter > 10000 && $priceAfter <= 15000);
                    break;
                case '3만':
                    $priceMatch = ($priceAfter > 15000 && $priceAfter <= 30000);
                    break;
            }
            if (!$priceMatch) {
                $matches = false;
            }
        }
        
        if ($matches) {
            $filteredAdvertisementProducts[] = $adProduct;
            $productId = $adProduct['id'] ?? $adProduct['product_id'] ?? null;
            if ($productId) {
                $filteredAdvertisementProductIds[] = (int)$productId;
            }
        }
    }
    
    $advertisementProducts = $filteredAdvertisementProducts;
    $advertisementProductIds = $filteredAdvertisementProductIds;
    error_log("[MVNO 페이지] 필터 적용 후 스폰서 상품 개수: " . count($advertisementProducts) . ", ID 목록: " . implode(', ', $advertisementProductIds));
}

// 디버깅: 광고 상품 개수 및 데이터 확인 (브라우저 콘솔 또는 HTML 주석으로 확인 가능)
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    error_log("=== MVNO 광고 디버깅 ===");
    error_log("광고 상품 개수 (raw): " . count($advertisementProducts));
    if (!empty($advertisementProducts)) {
        error_log("첫 번째 광고 상품 키: " . implode(', ', array_keys($advertisementProducts[0])));
        error_log("첫 번째 광고 상품 id: " . ($advertisementProducts[0]['id'] ?? '없음'));
        error_log("첫 번째 광고 상품 product_id: " . ($advertisementProducts[0]['product_id'] ?? '없음'));
        error_log("첫 번째 광고 상품 plan_name: " . ($advertisementProducts[0]['plan_name'] ?? '없음'));
    }
    error_log("광고 상품 ID 목록: " . implode(', ', $advertisementProductIds));
    error_log("로테이션 시간: " . ($rotationDuration ?? 'null'));
}

// 디버깅 모드 (필터 파라미터가 있으면 항상 디버깅)
$debugMode = isset($_GET['debug']) && $_GET['debug'] === '1';
$hasFilter = !empty($filterProvider) || !empty($filterPriceRange);

// 필터가 있으면 항상 디버깅 로그 출력
if ($hasFilter || $debugMode) {
    error_log("=== MVNO 필터 디버깅 ===");
    error_log("filterProvider: '" . $filterProvider . "'");
    error_log("filterPriceRange: '" . $filterPriceRange . "'");
    error_log("GET 파라미터: " . json_encode($_GET, JSON_UNESCAPED_UNICODE));
}

// 페이지 번호 가져오기
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// 원본 DB 데이터를 직접 가져와서 필터링 (변환 전에 필터링)
require_once __DIR__ . '/../includes/data/db-config.php';
$pdo = getDBConnection();

$allProducts = [];
$totalCount = 0;

if ($pdo) {
    // WHERE 조건 구성
    $whereConditions = ["p.product_type = 'mvno'", "p.status = 'active'"];
    $params = [];
    
    // 광고 상품 제외
    if (!empty($advertisementProductIds)) {
        $placeholders = implode(',', array_fill(0, count($advertisementProductIds), '?'));
        $whereConditions[] = "p.id NOT IN ($placeholders)";
        $params = array_merge($params, $advertisementProductIds);
    }
    
    // Provider 필터 (원본 DB 데이터 기준)
    if (!empty($filterProvider)) {
        // provider 필터 매핑 (다양한 형식 지원)
        $providerMapping = [
            'SK알뜰폰' => ['SK알뜰폰', 'SK 알뜰폰', 'SKT알뜰폰', 'SKT 알뜰폰', 'SK'],
            'KT알뜰폰' => ['KT알뜰폰', 'KT 알뜰폰', 'KT'],
            'LGU+알뜰폰' => ['LGU+알뜰폰', 'LG U+알뜰폰', 'LG U+ 알뜰폰', 'LGU+ 알뜰폰', 'LG U+', 'LGU+', 'LG알뜰폰']
        ];
        
        $allowedProviders = $providerMapping[$filterProvider] ?? [$filterProvider];
        
        if ($hasFilter || $debugMode) {
            error_log("Provider 필터 적용 시작: '{$filterProvider}' -> 허용된 값: " . implode(', ', $allowedProviders));
        }
        
        $providerPlaceholders = implode(',', array_fill(0, count($allowedProviders), '?'));
        $whereConditions[] = "mvno.provider IN ($providerPlaceholders)";
        $params = array_merge($params, $allowedProviders);
    }
    
    // 가격 필터 (원본 DB 데이터 기준) - price_after는 숫자 값
    if (!empty($filterPriceRange)) {
        if ($hasFilter || $debugMode) {
            error_log("가격 필터 적용 시작: '{$filterPriceRange}'");
        }
        
        switch ($filterPriceRange) {
            case '1천':
                $whereConditions[] = "(mvno.price_after IS NULL OR mvno.price_after = 0 OR mvno.price_after <= 1000)";
                break;
            case '5천':
                $whereConditions[] = "mvno.price_after > 1000 AND mvno.price_after <= 5000";
                break;
            case '1만':
                $whereConditions[] = "mvno.price_after > 5000 AND mvno.price_after <= 10000";
                break;
            case '1.5만':
                $whereConditions[] = "mvno.price_after > 10000 AND mvno.price_after <= 15000";
                break;
            case '3만':
                $whereConditions[] = "mvno.price_after > 15000 AND mvno.price_after <= 30000";
                break;
        }
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    // 전체 개수 조회
    $countSql = "
        SELECT COUNT(*) as total
        FROM products p
        INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
        {$whereClause}
    ";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 디버깅: DB에서 가져온 전체 개수
    error_log("[MVNO 페이지] ========== 디버깅 시작 ==========");
    error_log("[MVNO 페이지] DB 쿼리 결과 - 전체 개수: {$totalCount}");
    error_log("[MVNO 페이지] WHERE 조건: {$whereClause}");
    error_log("[MVNO 페이지] 광고 상품 ID 개수: " . count($advertisementProductIds));
    if (!empty($advertisementProductIds)) {
        error_log("[MVNO 페이지] 광고 상품 ID 목록: " . implode(', ', $advertisementProductIds));
    }
    
    // 추가 디버깅: products 테이블의 전체 active 상품 개수 확인
    $debugStmt = $pdo->query("SELECT COUNT(*) as cnt FROM products WHERE product_type = 'mvno' AND status = 'active'");
    $debugResult = $debugStmt->fetch();
    $allActiveCount = $debugResult['cnt'] ?? 0;
    error_log("[MVNO 페이지] products 테이블의 전체 active 상품 개수: {$allActiveCount}");
    
    // product_mvno_details가 없는 상품 확인
    $missingStmt = $pdo->query("
        SELECT p.id 
        FROM products p
        LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id
        WHERE p.product_type = 'mvno' 
        AND p.status = 'active'
        AND mvno.product_id IS NULL
    ");
    $missingProducts = $missingStmt->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($missingProducts)) {
        error_log("[MVNO 페이지] ⚠ product_mvno_details가 없는 상품 ID: " . implode(', ', $missingProducts));
    } else {
        error_log("[MVNO 페이지] ✓ 모든 active 상품에 product_mvno_details가 있습니다.");
    }
    
    if ($hasFilter || $debugMode) {
        error_log("필터 적용 후 전체 개수: {$totalCount}");
    }
    
    // 상품 목록 조회 (원본 DB 데이터)
    $sql = "
        SELECT 
            p.id,
            p.seller_id,
            p.status,
            p.view_count,
            p.favorite_count,
            p.review_count,
            p.share_count,
            p.application_count,
            mvno.provider,
            mvno.service_type,
            mvno.plan_name,
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
            mvno.promotion_title,
            mvno.promotions
        FROM products p
        INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
        {$whereClause}
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $allParams = array_merge($params, [$limit, $offset]);
    $stmt->execute($allParams);
    $allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($hasFilter || $debugMode) {
        error_log("조회된 원본 상품 개수: " . count($allProducts));
        if (!empty($allProducts)) {
            $providerSamples = [];
            $priceSamples = [];
            for ($i = 0; $i < min(20, count($allProducts)); $i++) {
                $providerSamples[] = $allProducts[$i]['provider'] ?? '없음';
                $priceSamples[] = $allProducts[$i]['price_after'] ?? '없음';
            }
            $uniqueProviders = array_unique($providerSamples);
            error_log("원본 Provider 샘플 (처음 20개, 고유값): " . implode(', ', $uniqueProviders));
            error_log("원본 Price_after 샘플 (처음 20개): " . implode(', ', $priceSamples));
        }
    }
    
    // 원본 데이터를 plan 카드 형식으로 변환
    $allPlans = [];
    foreach ($allProducts as $product) {
        try {
            $plan = convertMvnoProductToPlanCard($product);
            // 찜 상태 확인
            $plan['is_favorited'] = false;
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
                            AND product_type = 'mvno'
                        ");
                        $favStmt->execute([
                            ':product_id' => $product['id'],
                            ':user_id' => $currentUserId
                        ]);
                        $favResult = $favStmt->fetch(PDO::FETCH_ASSOC);
                        $plan['is_favorited'] = ($favResult['count'] ?? 0) > 0;
                    } catch (Exception $e) {
                        // 에러 무시
                    }
                }
            }
            $allPlans[] = $plan;
        } catch (Exception $e) {
            error_log("상품 변환 실패 (ID: {$product['id']}): " . $e->getMessage());
        }
    }
} else {
    // DB 연결 실패 시 기존 방식 사용
    $allPlans = getPlansDataFromDB(10000, 'active');
    $totalCount = count($allPlans);
}

$mvno_filters = getMvnoFilters();

// 전체 개수는 이미 DB 쿼리에서 계산됨
// $plans는 이미 변환된 $allPlans (필터링 및 변환 완료)
$plans = $allPlans;

// 디버깅: 최종 개수 확인
error_log("[MVNO 페이지] 변환 후 전체 상품 개수: " . count($allPlans));
error_log("[MVNO 페이지] 광고 상품 개수: " . count($advertisementProducts));
error_log("[MVNO 페이지] 최종 표시 개수 (totalCount): {$totalCount}");
if (!empty($advertisementProductIds)) {
    error_log("[MVNO 페이지] 광고 상품 ID 목록: " . implode(', ', $advertisementProductIds));
}
error_log("[MVNO 페이지] ========== 디버깅 종료 ==========");

// 광고 상품을 plan 카드 형식으로 변환
$advertisementPlans = [];
$debugMode = isset($_GET['debug']) && $_GET['debug'] === '1';
if ($debugMode) {
    error_log("광고 상품 변환 시작, 개수: " . count($advertisementProducts));
}
if (!empty($advertisementProducts)) {
    require_once __DIR__ . '/../includes/data/product-functions.php';
    foreach ($advertisementProducts as $index => $adProduct) {
        if ($debugMode) {
            error_log("광고 상품 #{$index} 처리 시작");
            error_log("광고 상품 키: " . implode(', ', array_keys($adProduct)));
        }
        
        // id 필드가 없으면 product_id를 id로 설정 (convertMvnoProductToPlanCard 함수가 id 필드를 요구)
        if (!isset($adProduct['id']) && isset($adProduct['product_id'])) {
            $adProduct['id'] = $adProduct['product_id'];
        }
        // product_id가 없으면 id를 product_id로 설정
        if (!isset($adProduct['product_id']) && isset($adProduct['id'])) {
            $adProduct['product_id'] = $adProduct['id'];
        }
        
        // convertMvnoProductToPlanCard 함수 사용하여 변환
        try {
            $planCard = convertMvnoProductToPlanCard($adProduct);
            if ($debugMode) {
                error_log("광고 상품 #{$index} 변환 성공");
            }
        } catch (Exception $e) {
            error_log("광고 상품 #{$index} 변환 실패: " . $e->getMessage());
            continue;
        }
        $productIdForLink = $adProduct['product_id'] ?? $adProduct['id'] ?? 0;
        $planCard['link_url'] = '/MVNO/mvno/mvno-plan-detail.php?id=' . $productIdForLink;
        $planCard['item_type'] = 'mvno';
        $planCard['is_advertising'] = true;
        // 찜 상태 확인
        $planCard['is_favorited'] = false;
        if (function_exists('isLoggedIn') && isLoggedIn()) {
            try {
                $currentUser = getCurrentUser();
                $currentUserId = $currentUser['user_id'] ?? null;
                if ($currentUserId) {
                    $pdo = getDBConnection();
                    if ($pdo) {
                        $favStmt = $pdo->prepare("
                            SELECT COUNT(*) as count 
                            FROM product_favorites 
                            WHERE product_id = :product_id 
                            AND user_id = :user_id 
                            AND product_type = 'mvno'
                        ");
                        $productIdForFavorite = $adProduct['product_id'] ?? $adProduct['id'] ?? 0;
                        $favStmt->execute([
                            ':product_id' => $productIdForFavorite,
                            ':user_id' => $currentUserId
                        ]);
                        $favResult = $favStmt->fetch(PDO::FETCH_ASSOC);
                        $planCard['is_favorited'] = ($favResult['count'] ?? 0) > 0;
                    }
                }
            } catch (Exception $e) {
                // 에러 무시
            }
        }
        $advertisementPlans[] = $planCard;
    }
}
?>

<main class="main-content">
    <div class="plans-filter-section">
        <div class="plans-filter-inner">
            <div class="plans-filter-group">
                <div class="plans-filter-row">
                    <button class="plans-filter-btn <?php echo (empty($filterProvider) && empty($filterPriceRange)) ? 'active' : ''; ?>" 
                            onclick="clearFilters()">
                        <span class="plans-filter-text">전체</span>
                    </button>
                    
                    <!-- 통신사 필터 -->
                    <button class="plans-filter-btn <?php echo ($filterProvider === 'SK알뜰폰') ? 'active' : ''; ?>" 
                            onclick="filterByProvider('SK알뜰폰')">
                        <span class="plans-filter-text">SK알뜰폰</span>
                    </button>
                    <button class="plans-filter-btn <?php echo ($filterProvider === 'KT알뜰폰') ? 'active' : ''; ?>" 
                            onclick="filterByProvider('KT알뜰폰')">
                        <span class="plans-filter-text">KT알뜰폰</span>
                    </button>
                    <button class="plans-filter-btn <?php echo ($filterProvider === 'LG알뜰폰' || $filterProvider === 'LG U+알뜰폰' || $filterProvider === 'LGU+알뜰폰') ? 'active' : ''; ?>" 
                            onclick="filterByProvider('LG알뜰폰')">
                        <span class="plans-filter-text">LGU+알뜰폰</span>
                    </button>
                    
                    <!-- 가격 필터 -->
                    <button class="plans-filter-btn <?php echo ($filterPriceRange === '1천') ? 'active' : ''; ?>" 
                            onclick="filterByPriceRange('1천')">
                        <span class="plans-filter-text">1천</span>
                    </button>
                    <button class="plans-filter-btn <?php echo ($filterPriceRange === '5천') ? 'active' : ''; ?>" 
                            onclick="filterByPriceRange('5천')">
                        <span class="plans-filter-text">5천</span>
                    </button>
                    <button class="plans-filter-btn <?php echo ($filterPriceRange === '1만') ? 'active' : ''; ?>" 
                            onclick="filterByPriceRange('1만')">
                        <span class="plans-filter-text">1만</span>
                    </button>
                    <button class="plans-filter-btn <?php echo ($filterPriceRange === '1.5만') ? 'active' : ''; ?>" 
                            onclick="filterByPriceRange('1.5만')">
                        <span class="plans-filter-text">1.5만</span>
                    </button>
                    <button class="plans-filter-btn <?php echo ($filterPriceRange === '3만') ? 'active' : ''; ?>" 
                            onclick="filterByPriceRange('3만')">
                        <span class="plans-filter-text">3만</span>
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
                    <?php
                    $section_title = number_format($totalCount) . '개 검색';
                    ?>
                    <?php if (!empty($section_title)): ?>
                    <div class="plans-results-count">
                        <span><?php echo htmlspecialchars($section_title); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="plans-list-container" id="mvno-products-container">
                        <!-- 최상단 광고 로테이션 섹션 - 모든 광고 상품 표시 -->
                        <?php 
                        // 디버깅: 페이지에 직접 출력
                        if (isset($_GET['debug']) && $_GET['debug'] === '1') {
                            echo '<!-- 디버깅 정보 -->';
                            echo '<!-- 광고 상품 개수 (raw): ' . count($advertisementProducts) . ' -->';
                            echo '<!-- 광고 상품 개수 (변환 후): ' . count($advertisementPlans) . ' -->';
                            if (!empty($advertisementProducts)) {
                                echo '<!-- 첫 번째 광고 상품 키: ' . implode(', ', array_keys($advertisementProducts[0])) . ' -->';
                                echo '<!-- 첫 번째 광고 상품 id: ' . ($advertisementProducts[0]['id'] ?? '없음') . ' -->';
                                echo '<!-- 첫 번째 광고 상품 product_id: ' . ($advertisementProducts[0]['product_id'] ?? '없음') . ' -->';
                            }
                        }
                        if (!empty($advertisementPlans)): ?>
                            <?php foreach ($advertisementPlans as $index => $adPlan): ?>
                                <div class="advertisement-card-item" data-ad-index="<?php echo $index; ?>">
                                    <?php
                                    $plan = $adPlan;
                                    $card_wrapper_class = '';
                                    $layout_type = 'list';
                                    include __DIR__ . '/../includes/components/plan-card.php';
                                    ?>
                                    <!-- 카드 구분선 -->
                                    <hr class="plan-card-divider">
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <!-- 일반 상품 목록 -->
                        <?php foreach ($plans as $plan): ?>
                            <?php
                            $card_wrapper_class = '';
                            $layout_type = 'list';
                            include __DIR__ . '/../includes/components/plan-card.php';
                            ?>
                            <!-- 카드 구분선 (모바일용) -->
                            <hr class="plan-card-divider">
                        <?php endforeach; ?>
                    </div>
                    <?php 
                    $totalPlansCount = isset($totalCount) ? $totalCount : 0;
                    $currentCount = count($plans);
                    $remainingCount = max(0, $totalPlansCount - ($offset + $currentCount));
                    $nextPage = $page + 1;
                    $hasMore = ($offset + $currentCount) < $totalPlansCount;
                    ?>
                    <?php if ($hasMore && $totalPlansCount > 0): ?>
                    <div class="load-more-container" id="load-more-anchor">
                        <button id="load-more-mvno-btn" class="load-more-btn" data-type="mvno" data-page="2" data-total="<?php echo $totalPlansCount; ?>"
                                <?php echo !empty($filterProvider) ? ' data-provider="' . htmlspecialchars($filterProvider) . '"' : ''; ?>
                                <?php echo !empty($filterPriceRange) ? ' data-price-range="' . htmlspecialchars($filterPriceRange) . '"' : ''; ?>>
                            더보기 (<span id="remaining-count"><?php echo number_format($remainingCount); ?></span>개 남음)
                        </button>
                    </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

<script src="<?php echo getAssetPath('/assets/js/plan-accordion.js'); ?>" defer></script>
<script src="<?php echo getAssetPath('/assets/js/favorite-heart.js'); ?>" defer></script>
<script src="<?php echo getAssetPath('/assets/js/share.js'); ?>" defer></script>
<!-- 더보기 기능 스크립트 -->
<script src="<?php echo getAssetPath('/assets/js/load-more-products.js'); ?>?v=2"></script>

<style>
/* 더보기 버튼 컨테이너 - 카드와 같은 너비 */
.load-more-container {
    width: 100%;
    max-width: 100%;
    padding: 30px 0;
    box-sizing: border-box;
    margin: 0;
}

/* 더보기 버튼 스타일 - 카드와 같은 너비로 설정 */
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

<style>
/* 더보기 버튼 컨테이너 */
.load-more-container {
    width: 100%;
    padding: 30px 20px;
    box-sizing: border-box;
}

/* 더보기 버튼 스타일 (좌우 길게) - 링크도 버튼처럼 보이게 */
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

<style>
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
// 필터 스크롤 고정 기능
(function() {
    const filterSection = document.querySelector('.plans-filter-section');
    const resultsCount = document.querySelector('.plans-results-count');
    const themeSection = document.querySelector('.theme-plans-list-section');
    
    if (!filterSection) return;
    
    let isFilterSticky = false;
    let isFilterFixed = false;
    let filterOriginalTop = 0;
    
    function handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const filterRect = filterSection.getBoundingClientRect();
        const filterTop = filterRect.top;
        
        if (filterOriginalTop === 0 && scrollTop === 0) {
            filterOriginalTop = filterRect.top + scrollTop;
        }
        
        if (scrollTop > 10 && !isFilterSticky) {
            filterSection.classList.add('filter-sticky');
            if (resultsCount) resultsCount.classList.add('filter-active');
            if (themeSection) themeSection.classList.add('filter-active');
            isFilterSticky = true;
        }
        
        if (filterTop < 0 && isFilterSticky && !isFilterFixed) {
            filterSection.classList.remove('filter-sticky');
            filterSection.classList.add('filter-fixed');
            isFilterFixed = true;
        } else if (scrollTop < filterOriginalTop - 50 && isFilterFixed) {
            filterSection.classList.remove('filter-fixed');
            filterSection.classList.add('filter-sticky');
            isFilterFixed = false;
        } else if (scrollTop <= 10 && isFilterSticky) {
            filterSection.classList.remove('filter-sticky', 'filter-fixed');
            if (resultsCount) resultsCount.classList.remove('filter-active');
            if (themeSection) themeSection.classList.remove('filter-active');
            isFilterSticky = false;
            isFilterFixed = false;
        }
    }
    
    let ticking = false;
    function onScroll() {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                handleScroll();
                ticking = false;
            });
            ticking = true;
        }
    }
    
    window.addEventListener('scroll', onScroll, { passive: true });
    handleScroll();
})();

// 필터 함수들
function filterByProvider(provider) {
    console.log('[필터] filterByProvider 호출:', provider);
    try {
        const url = new URL(window.location.href);
        const currentProvider = url.searchParams.get('provider');
        console.log('[필터] 현재 provider:', currentProvider);
        
        if (currentProvider === provider) {
            url.searchParams.delete('provider');
            console.log('[필터] provider 제거');
        } else {
            url.searchParams.set('provider', provider);
            console.log('[필터] provider 설정:', provider);
        }
        url.searchParams.delete('page'); // 필터 변경 시 첫 페이지로
        url.searchParams.set('debug', '1'); // 디버깅 모드 활성화
        
        const finalUrl = url.toString();
        console.log('[필터] 최종 URL:', finalUrl);
        console.log('[필터] 페이지 이동 시작...');
        window.location.href = finalUrl;
    } catch (e) {
        console.error('[필터] 오류 발생:', e);
        alert('필터 적용 중 오류가 발생했습니다: ' + e.message);
    }
}

function filterByPriceRange(priceRange) {
    console.log('[필터] filterByPriceRange 호출:', priceRange);
    try {
        const url = new URL(window.location.href);
        const currentPriceRange = url.searchParams.get('price_range');
        console.log('[필터] 현재 price_range:', currentPriceRange);
        
        if (currentPriceRange === priceRange) {
            url.searchParams.delete('price_range');
            console.log('[필터] price_range 제거');
        } else {
            url.searchParams.set('price_range', priceRange);
            console.log('[필터] price_range 설정:', priceRange);
        }
        url.searchParams.delete('page'); // 필터 변경 시 첫 페이지로
        url.searchParams.set('debug', '1'); // 디버깅 모드 활성화
        
        const finalUrl = url.toString();
        console.log('[필터] 최종 URL:', finalUrl);
        console.log('[필터] 페이지 이동 시작...');
        window.location.href = finalUrl;
    } catch (e) {
        console.error('[필터] 오류 발생:', e);
        alert('필터 적용 중 오류가 발생했습니다: ' + e.message);
    }
}

function clearFilters() {
    console.log('[필터] clearFilters 호출');
    try {
        const url = new URL(window.location.href);
        url.searchParams.delete('provider');
        url.searchParams.delete('price_range');
        url.searchParams.delete('page');
        url.searchParams.delete('debug');
        
        const finalUrl = url.toString();
        console.log('[필터] 최종 URL:', finalUrl);
        console.log('[필터] 페이지 이동 시작...');
        window.location.href = finalUrl;
    } catch (e) {
        console.error('[필터] 오류 발생:', e);
        alert('필터 초기화 중 오류가 발생했습니다: ' + e.message);
    }
}

// 페이지 로드 시 필터 상태 확인
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const provider = urlParams.get('provider');
    const priceRange = urlParams.get('price_range');
    console.log('[필터] 페이지 로드 - 현재 필터 상태:');
    console.log('[필터] provider:', provider || '없음');
    console.log('[필터] price_range:', priceRange || '없음');
    
    // 필터 버튼 확인
    const filterButtons = document.querySelectorAll('.plans-filter-btn');
    console.log('[필터] 필터 버튼 개수:', filterButtons.length);
    filterButtons.forEach((btn, index) => {
        console.log(`[필터] 버튼 ${index + 1}:`, btn.textContent.trim(), 'onclick:', btn.getAttribute('onclick'));
    });
});

// 필터 함수들
function filterByProvider(provider) {
    const url = new URL(window.location.href);
    if (url.searchParams.get('provider') === provider) {
        url.searchParams.delete('provider');
    } else {
        url.searchParams.set('provider', provider);
    }
    url.searchParams.delete('page'); // 필터 변경 시 첫 페이지로
    window.location.href = url.toString();
}

function filterByPriceRange(priceRange) {
    const url = new URL(window.location.href);
    if (url.searchParams.get('price_range') === priceRange) {
        url.searchParams.delete('price_range');
    } else {
        url.searchParams.set('price_range', priceRange);
    }
    url.searchParams.delete('page'); // 필터 변경 시 첫 페이지로
    window.location.href = url.toString();
}

function clearFilters() {
    const url = new URL(window.location.href);
    url.searchParams.delete('provider');
    url.searchParams.delete('price_range');
    url.searchParams.delete('page');
    window.location.href = url.toString();
}
</script>

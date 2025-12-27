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

// 페이지 번호 가져오기
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// 데이터베이스에서 통신사단독유심 상품 목록 가져오기
$mnoSimProducts = [];
$providers = [];
$serviceTypes = [];

try {
    $pdo = getDBConnection();
    if ($pdo) {
        // 관리자는 inactive 상태도 볼 수 있음
        $statusCondition = $isAdmin ? "p.status != 'deleted'" : "p.status = 'active'";
        
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
        $totalCount = intval($countStmt->fetch(PDO::FETCH_ASSOC)['total']);
        
        // 상품 목록 조회 (LIMIT/OFFSET 추가)
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
        
        // 필터 옵션 수집
        $filterStmt = $pdo->prepare("
            SELECT DISTINCT 
                mno_sim.provider,
                mno_sim.service_type
            FROM products p
            INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
            WHERE p.product_type = 'mno-sim' AND {$statusCondition}
            ORDER BY mno_sim.provider, mno_sim.service_type
        ");
        $filterStmt->execute();
        $filterOptions = $filterStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($filterOptions as $option) {
            if (!empty($option['provider']) && !in_array($option['provider'], $providers)) {
                $providers[] = $option['provider'];
            }
            if (!empty($option['service_type']) && !in_array($option['service_type'], $serviceTypes)) {
                $serviceTypes[] = $option['service_type'];
            }
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching MNO-SIM products: " . $e->getMessage());
}

// 통신사별 아이콘 경로 매핑
function getProviderIconPath($provider) {
    $iconMap = [
        'KT' => '/MVNO/assets/images/internets/kt.svg',
        'SKT' => '/MVNO/assets/images/internets/broadband.svg',
        'LG U+' => '/MVNO/assets/images/internets/lgu.svg',
    ];
    return $iconMap[$provider] ?? '';
}

// plan-data.php는 한 번만 로드
require_once __DIR__ . '/../includes/data/plan-data.php';

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
                    <button class="plans-filter-btn" onclick="clearFilters()">
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
                </div>
            </div>
        </div>
        <hr class="plans-filter-divider">
    </div>

    <div class="content-layout">
        <div class="plans-main-layout">
            <div class="plans-left-section">
                <section class="theme-plans-list-section">
        
        <!-- 카드 그리드 -->
        <?php if (empty($mnoSimProducts)): ?>
            <div style="text-align: center; padding: 60px 20px; color: #6b7280;">
                <p>등록된 통신사단독유심 상품이 없습니다.</p>
            </div>
        <?php else: ?>
            <div class="plans-list-container" id="mno-sim-products-container">
                <?php foreach ($mnoSimProducts as $product): ?>
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
                        'item_type' => 'mno-sim' // 찜 버튼용 타입
                    ];
                    
                    // 알뜰폰 카드 컴포넌트 사용
                    $card_wrapper_class = '';
                    $layout_type = 'list';
                    include __DIR__ . '/../includes/components/plan-card.php';
                    ?>
                    
                    <!-- 카드 구분선 (모바일용) -->
                    <hr class="plan-card-divider">
                <?php endforeach; ?>
                <?php 
                $currentCount = count($mnoSimProducts);
                $remainingCount = max(0, $totalCount - ($offset + $currentCount));
                $nextPage = $page + 1;
                $hasMore = ($offset + $currentCount) < $totalCount;
                ?>
                <?php if ($hasMore && $totalCount > 0): ?>
                <div class="load-more-container" id="load-more-anchor">
                    <a href="?page=<?php echo $nextPage; ?><?php echo !empty($filterProvider) ? '&provider=' . urlencode($filterProvider) : ''; ?><?php echo !empty($filterServiceType) ? '&service_type=' . urlencode($filterServiceType) : ''; ?>" class="load-more-btn">
                        더보기 (<?php echo number_format($remainingCount); ?>개 남음)
                    </a>
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
<script>
// 더보기 버튼 클릭 시 현재 스크롤 위치 저장 및 복원
(function() {
    'use strict';
    
    const DEBUG = true; // 디버깅 모드 (false로 설정하면 콘솔 로그 비활성화)
    let isRestoring = false; // 복원 중 플래그
    let restoreAttempts = 0; // 복원 시도 횟수
    const MAX_RESTORE_ATTEMPTS = 10; // 최대 복원 시도 횟수
    let scrollRestoreBlocked = false; // 브라우저 자동 스크롤 복원 차단 플래그
    
    function log(message, data) {
        if (DEBUG) {
            console.log('[더보기 스크롤] ' + message, data || '');
        }
    }
    
    // 브라우저의 자동 스크롤 복원 설정
    // 더보기 버튼 클릭으로 온 경우에만 수동 복원, 일반 새로고침은 브라우저 기본 복원 사용
    function configureBrowserScrollRestore() {
        if ('scrollRestoration' in history) {
            const urlParams = new URLSearchParams(window.location.search);
            const hasPageParam = urlParams.has('page') && parseInt(urlParams.get('page')) > 1;
            const fromButton = sessionStorage.getItem('mnoSimLoadMoreFromButton') === 'true';
            
            // 더보기 버튼 클릭으로 온 경우에만 수동 복원
            if (hasPageParam && fromButton) {
                history.scrollRestoration = 'manual';
                log('더보기 버튼 클릭으로 판단 - 브라우저 자동 스크롤 복원 비활성화');
            } else {
                history.scrollRestoration = 'auto';
                log('일반 페이지 로드 - 브라우저 자동 스크롤 복원 활성화');
            }
        }
    }
    
    // 브라우저 자동 스크롤 복원 설정
    configureBrowserScrollRestore();
    
    // 더보기 버튼 클릭 시 현재 스크롤 위치 저장
    document.addEventListener('DOMContentLoaded', function() {
        const loadMoreBtn = document.querySelector('.load-more-btn');
        if (loadMoreBtn) {
            log('더보기 버튼 찾음');
            loadMoreBtn.addEventListener('click', function(e) {
                // 현재 화면에 보이는 카드 중 가장 위쪽 카드의 ID를 찾아서 저장
                const cards = document.querySelectorAll('article.basic-plan-card[data-plan-id]');
                const scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
                const windowHeight = window.innerHeight;
                const viewportTop = scrollPosition;
                
                // 더보기 버튼의 위치 확인
                const loadMoreBtnRect = loadMoreBtn.getBoundingClientRect();
                const btnTop = loadMoreBtnRect.top + scrollPosition;
                
                // 화면에 보이는 카드 중 가장 위쪽 카드 찾기
                let targetCard = null;
                let targetCardId = null;
                let targetCardTop = null;
                let minDistance = Infinity;
                
                for (let i = 0; i < cards.length; i++) {
                    const card = cards[i];
                    const cardRect = card.getBoundingClientRect();
                    const cardTop = cardRect.top + scrollPosition;
                    const cardBottom = cardTop + cardRect.height;
                    const cardId = card.getAttribute('data-plan-id');
                    
                    // 더보기 버튼보다 위에 있는 카드만 고려
                    if (cardTop < btnTop - 50 && cardId) {
                        // 화면에 보이는 카드 찾기 (화면 상단에서 화면 하단까지)
                        if (cardTop < viewportTop + windowHeight && cardBottom > viewportTop) {
                            // 화면 상단에 가장 가까운 카드 찾기
                            const distance = Math.abs(cardTop - viewportTop);
                            if (distance < minDistance) {
                                minDistance = distance;
                                targetCard = card;
                                targetCardId = cardId;
                                targetCardTop = cardTop;
                            }
                        }
                    }
                }
                
                // 카드를 찾지 못했으면, 더보기 버튼 위쪽의 마지막 카드 찾기
                if (targetCardId === null) {
                    for (let i = cards.length - 1; i >= 0; i--) {
                        const card = cards[i];
                        const cardRect = card.getBoundingClientRect();
                        const cardTop = cardRect.top + scrollPosition;
                        const cardId = card.getAttribute('data-plan-id');
                        
                        // 더보기 버튼보다 위에 있고, 화면 상단보다 아래에 있는 카드
                        if (cardTop < btnTop - 50 && cardTop > viewportTop - 300 && cardId) {
                            targetCard = card;
                            targetCardId = cardId;
                            targetCardTop = cardTop;
                            log('더보기 버튼 위쪽 마지막 카드 찾음', {
                                cardId: cardId,
                                cardTop: cardTop,
                                btnTop: btnTop,
                                viewportTop: viewportTop
                            });
                            break;
                        }
                    }
                }
                
                const scrollHeight = document.documentElement.scrollHeight;
                const clientHeight = document.documentElement.clientHeight;
                
                log('더보기 버튼 클릭 - 카드 ID 저장', {
                    scrollPosition: scrollPosition,
                    targetCardId: targetCardId,
                    targetCardTop: targetCardTop,
                    scrollHeight: scrollHeight,
                    clientHeight: clientHeight,
                    windowHeight: windowHeight,
                    totalCards: cards.length,
                    targetCardFound: targetCard !== null,
                    btnTop: btnTop,
                    distanceFromTop: targetCardTop,
                    distanceFromButton: targetCardTop ? (btnTop - targetCardTop) : null
                });
                
                // 카드 ID와 위치를 모두 저장 (ID가 없을 경우를 대비)
                if (targetCardId) {
                    sessionStorage.setItem('mnoSimLoadMoreCardId', targetCardId);
                    sessionStorage.setItem('mnoSimLoadMoreCardTop', targetCardTop.toString());
                } else {
                    // 카드 ID를 찾지 못했으면 스크롤 위치만 저장 (폴백)
                    const fallbackPosition = Math.min(scrollPosition, btnTop - 200);
                    sessionStorage.setItem('mnoSimLoadMoreScrollPosition', fallbackPosition.toString());
                    log('카드 ID를 찾지 못함 - 스크롤 위치만 저장', {
                        fallbackPosition: fallbackPosition
                    });
                }
                
                sessionStorage.setItem('mnoSimLoadMoreTimestamp', Date.now().toString());
                sessionStorage.setItem('mnoSimLoadMoreRestored', 'false'); // 복원 완료 플래그
                sessionStorage.setItem('mnoSimLoadMoreFromButton', 'true'); // 더보기 버튼 클릭으로 온 것임을 표시
                sessionStorage.setItem('mnoSimLoadMoreScrollHeight', scrollHeight.toString()); // 현재 문서 높이도 저장
                sessionStorage.setItem('mnoSimLoadMoreCardCount', cards.length.toString()); // 현재 카드 개수도 저장
            });
        } else {
            log('더보기 버튼을 찾을 수 없음');
        }
    });

    // 페이지 로드 시 스크롤 위치 복원 (카드 ID 기반)
    function restoreScrollPosition() {
        // URL에 page 파라미터가 없으면 (더보기 버튼을 클릭한 것이 아니면) 스크롤 복원하지 않음
        const urlParams = new URLSearchParams(window.location.search);
        const hasPageParam = urlParams.has('page') && parseInt(urlParams.get('page')) > 1;
        const fromButton = sessionStorage.getItem('mnoSimLoadMoreFromButton') === 'true';
        
        if (!hasPageParam || !fromButton) {
            // page 파라미터가 없거나 더보기 버튼 클릭이 아니면 저장된 데이터 삭제
            sessionStorage.removeItem('mnoSimLoadMoreCardId');
            sessionStorage.removeItem('mnoSimLoadMoreCardTop');
            sessionStorage.removeItem('mnoSimLoadMoreScrollPosition');
            sessionStorage.removeItem('mnoSimLoadMoreTimestamp');
            sessionStorage.removeItem('mnoSimLoadMoreRestored');
            sessionStorage.removeItem('mnoSimLoadMoreFromButton');
            return;
        }
        
        // 이미 복원이 완료되었으면 스킵
        const isRestored = sessionStorage.getItem('mnoSimLoadMoreRestored') === 'true';
        if (isRestored && restoreAttempts > 3) {
            return;
        }
        
        restoreAttempts++;
        
        const savedCardId = sessionStorage.getItem('mnoSimLoadMoreCardId');
        const savedCardTop = sessionStorage.getItem('mnoSimLoadMoreCardTop');
        const savedScrollPosition = sessionStorage.getItem('mnoSimLoadMoreScrollPosition'); // 폴백용
        const savedTimestamp = sessionStorage.getItem('mnoSimLoadMoreTimestamp');
        
        let targetPosition = null;
        let restoreMethod = null;
        
        // 카드 ID 기반 복원 시도
        if (savedCardId) {
            const targetCard = document.querySelector(`article.basic-plan-card[data-plan-id="${savedCardId}"]`);
            if (targetCard) {
                const cardRect = targetCard.getBoundingClientRect();
                targetPosition = cardRect.top + (window.pageYOffset || document.documentElement.scrollTop);
                restoreMethod = 'cardId';
                log('카드 ID로 카드 찾음', {
                    cardId: savedCardId,
                    cardTop: targetPosition,
                    savedCardTop: savedCardTop
                });
            } else {
                log('⚠️ 저장된 카드 ID로 카드를 찾을 수 없음', {
                    cardId: savedCardId
                });
            }
        }
        
        // 카드 ID로 찾지 못했고, 저장된 카드 위치가 있으면 사용
        if (targetPosition === null && savedCardTop) {
            targetPosition = parseInt(savedCardTop);
            restoreMethod = 'cardTop';
            log('저장된 카드 위치 사용', {
                cardTop: targetPosition
            });
        }
        
        // 여전히 없으면 폴백으로 스크롤 위치 사용
        if (targetPosition === null && savedScrollPosition) {
            targetPosition = parseInt(savedScrollPosition);
            restoreMethod = 'scrollPosition';
            log('폴백: 저장된 스크롤 위치 사용', {
                scrollPosition: targetPosition
            });
        }
        
        if (targetPosition === null) {
            log('⚠️ 복원할 위치를 찾을 수 없음');
            return;
        }
        
        const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight;
        const savedScrollHeight = sessionStorage.getItem('mnoSimLoadMoreScrollHeight');
        
        log('스크롤 위치 복원 시도 #' + restoreAttempts, {
            restoreMethod: restoreMethod,
            savedCardId: savedCardId,
            savedCardTop: savedCardTop,
            savedScrollPosition: savedScrollPosition,
            targetPosition: targetPosition,
            currentScroll: currentScroll,
            documentReadyState: document.readyState,
            scrollHeight: scrollHeight,
            savedScrollHeight: savedScrollHeight,
            isRestored: isRestored,
            isRestoring: isRestoring,
            scrollRestoreBlocked: scrollRestoreBlocked
        });
        
        // 스크롤 위치가 0이 아니고, 현재 위치와 다르면 복원
        // 차이가 5px 이상이면 복원
        if (targetPosition > 0 && Math.abs(targetPosition - currentScroll) > 5) {
            log('✅ 스크롤 위치 복원 실행', {
                restoreMethod: restoreMethod,
                targetPosition: targetPosition,
                currentScroll: currentScroll,
                difference: Math.abs(targetPosition - currentScroll)
            });
            isRestoring = true;
            
            // 즉시 스크롤 복원
                window.scrollTo({
                top: targetPosition,
                behavior: 'auto'
            });
            
            // requestAnimationFrame으로도 복원 (더 정확하게)
            requestAnimationFrame(function() {
                const rafScroll = window.pageYOffset || document.documentElement.scrollTop;
                log('requestAnimationFrame 복원', {
                    before: rafScroll,
                    target: targetPosition
                });
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'auto'
                });
            });
            
            // 복원 후 확인
            setTimeout(function() {
                const afterScroll = window.pageYOffset || document.documentElement.scrollTop;
                const success = Math.abs(targetPosition - afterScroll) < 30; // 30px 이내면 성공
                
                log('스크롤 복원 후 위치 확인', {
                    restoreMethod: restoreMethod,
                    expected: targetPosition,
                    actual: afterScroll,
                    difference: Math.abs(targetPosition - afterScroll),
                    success: success,
                    attempt: restoreAttempts,
                    scrollHeight: document.documentElement.scrollHeight
                });
                
                if (success || restoreAttempts >= MAX_RESTORE_ATTEMPTS) {
                    // 복원 성공 또는 최대 시도 횟수 도달
                    sessionStorage.setItem('mnoSimLoadMoreRestored', 'true');
                    isRestoring = false;
                    scrollRestoreBlocked = false; // 스크롤 차단 해제
                    
                    log('✅ 스크롤 복원 완료', {
                        restoreMethod: restoreMethod,
                        success: success,
                        finalPosition: afterScroll,
                        targetPosition: targetPosition
                    });
                    
                    // 최종 복원 후 sessionStorage 정리 (약간의 지연 후)
                    if (restoreAttempts >= MAX_RESTORE_ATTEMPTS) {
                        setTimeout(function() {
                            sessionStorage.removeItem('mnoSimLoadMoreCardId');
                            sessionStorage.removeItem('mnoSimLoadMoreCardTop');
                            sessionStorage.removeItem('mnoSimLoadMoreScrollPosition');
                            sessionStorage.removeItem('mnoSimLoadMoreTimestamp');
                            sessionStorage.removeItem('mnoSimLoadMoreRestored');
                            sessionStorage.removeItem('mnoSimLoadMoreFromButton');
                            sessionStorage.removeItem('mnoSimLoadMoreScrollHeight');
                            log('sessionStorage 정리 완료');
                        }, 1000);
                    }
                } else {
                    log('⚠️ 스크롤 복원 실패 - 재시도 필요', {
                        expected: targetPosition,
                        actual: afterScroll,
                        difference: Math.abs(targetPosition - afterScroll)
                    });
                    isRestoring = false;
                }
            }, 50);
        } else {
            log('ℹ️ 스크롤 복원 불필요 - 이미 올바른 위치', {
                targetPosition: targetPosition,
                currentScroll: currentScroll,
                difference: Math.abs(targetPosition - currentScroll)
            });
            sessionStorage.setItem('mnoSimLoadMoreRestored', 'true');
            scrollRestoreBlocked = false; // 스크롤 차단 해제
        }
    }
    
    // URL에 해시가 있으면 제거 (자동 스크롤 방지)
    if (window.location.hash) {
        log('URL 해시 제거', window.location.hash);
        history.replaceState(null, null, window.location.pathname + window.location.search);
    }
    
    // 페이지 로드 시 스크롤 처리
    const urlParams = new URLSearchParams(window.location.search);
    const hasPageParam = urlParams.has('page') && parseInt(urlParams.get('page')) > 1;
    const hasSavedCardId = sessionStorage.getItem('mnoSimLoadMoreCardId') !== null;
    const hasSavedPosition = sessionStorage.getItem('mnoSimLoadMoreScrollPosition') !== null;
    const fromButton = sessionStorage.getItem('mnoSimLoadMoreFromButton') === 'true';
    
    if (!hasPageParam || !fromButton) {
        // 더보기 버튼 클릭이 아닌 경우: 브라우저 기본 스크롤 복원 사용
        log('일반 페이지 로드 - 브라우저 기본 스크롤 복원 사용');
        
        // 헤더 메뉴 클릭으로 온 경우 저장된 데이터 정리
        if (!hasPageParam) {
            sessionStorage.removeItem('mnoSimLoadMoreCardId');
            sessionStorage.removeItem('mnoSimLoadMoreCardTop');
            sessionStorage.removeItem('mnoSimLoadMoreScrollPosition');
            sessionStorage.removeItem('mnoSimLoadMoreTimestamp');
            sessionStorage.removeItem('mnoSimLoadMoreRestored');
            sessionStorage.removeItem('mnoSimLoadMoreFromButton');
            log('헤더 메뉴 클릭으로 판단 - 저장된 데이터 정리');
        }
    } else {
        // 더보기 버튼 클릭으로 온 경우: 스크롤을 맨 위로 고정하고 나중에 복원
        window.scrollTo(0, 0);
        scrollRestoreBlocked = true;
        log('더보기 버튼 클릭으로 판단 - 스크롤 맨 위로 고정 후 복원 대기', {
            hasSavedCardId: hasSavedCardId,
            hasSavedPosition: hasSavedPosition
        });
        
        // 스크롤 위치를 계속 맨 위로 고정 (브라우저가 자동으로 복원하려는 것을 방지)
        const blockScrollInterval = setInterval(function() {
            if (!scrollRestoreBlocked) {
                clearInterval(blockScrollInterval);
                return;
            }
            
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            if (currentScroll > 0 && !isRestoring) {
                window.scrollTo(0, 0);
            }
        }, 10);
        
        // 300ms 후 스크롤 차단 해제하고 복원 시작
        setTimeout(function() {
            scrollRestoreBlocked = false;
            clearInterval(blockScrollInterval);
            log('스크롤 차단 해제 - 복원 시작');
            restoreScrollPosition();
        }, 300);
    }
    
    // DOMContentLoaded에서 복원 시도 (더보기 버튼 클릭으로 온 경우만)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            log('DOMContentLoaded 이벤트');
            const urlParams = new URLSearchParams(window.location.search);
            const hasPageParam = urlParams.has('page') && parseInt(urlParams.get('page')) > 1;
            const fromButton = sessionStorage.getItem('mnoSimLoadMoreFromButton') === 'true';
            
            if (hasPageParam && fromButton && !scrollRestoreBlocked) {
                restoreScrollPosition();
                setTimeout(restoreScrollPosition, 50);
                setTimeout(restoreScrollPosition, 100);
            }
        });
    } else {
        log('DOMContentLoaded 이미 발생함');
        const urlParams = new URLSearchParams(window.location.search);
        const hasPageParam = urlParams.has('page') && parseInt(urlParams.get('page')) > 1;
        const fromButton = sessionStorage.getItem('mnoSimLoadMoreFromButton') === 'true';
        
        if (hasPageParam && fromButton && !scrollRestoreBlocked) {
            restoreScrollPosition();
            setTimeout(restoreScrollPosition, 50);
            setTimeout(restoreScrollPosition, 100);
        }
    }
    
    // window.load에서도 복원 시도 (이미지 등 모든 리소스 로드 후)
    window.addEventListener('load', function() {
        log('window.load 이벤트');
        const urlParams = new URLSearchParams(window.location.search);
        const hasPageParam = urlParams.has('page') && parseInt(urlParams.get('page')) > 1;
        const fromButton = sessionStorage.getItem('mnoSimLoadMoreFromButton') === 'true';
        
        if (hasPageParam && fromButton && !scrollRestoreBlocked) {
            restoreScrollPosition();
            setTimeout(restoreScrollPosition, 100);
            setTimeout(restoreScrollPosition, 200);
            setTimeout(restoreScrollPosition, 300);
            setTimeout(restoreScrollPosition, 500);
        }
    });
    
    // 스크롤 이벤트 모니터링 및 의도치 않은 스크롤 변경 방지
    if (DEBUG) {
        let scrollCheckCount = 0;
        let lastScrollPosition = window.pageYOffset || document.documentElement.scrollTop;
        
        window.addEventListener('scroll', function() {
            if (isRestoring) {
                return; // 복원 중에는 모니터링 스킵
            }
            
            scrollCheckCount++;
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            const savedPosition = sessionStorage.getItem('mnoSimLoadMoreScrollPosition');
            const isRestored = sessionStorage.getItem('mnoSimLoadMoreRestored') === 'true';
            
            // 복원이 완료되지 않았고 저장된 위치가 있으면, 의도치 않은 스크롤 변경 방지
            if (savedPosition && !isRestored && restoreAttempts < MAX_RESTORE_ATTEMPTS) {
                const targetPosition = parseInt(savedPosition);
                // 스크롤이 저장된 위치에서 크게 벗어나면 다시 복원 시도
                if (Math.abs(currentScroll - targetPosition) > 100) {
                    log('의도치 않은 스크롤 변경 감지 - 복원 재시도', {
                        currentScroll: currentScroll,
                        targetPosition: targetPosition,
                        difference: Math.abs(currentScroll - targetPosition)
                    });
                    setTimeout(restoreScrollPosition, 0);
                }
            }
            
            if (scrollCheckCount % 20 === 0 && savedPosition) { // 20번마다 한 번씩만 로그
                log('스크롤 이벤트', {
                    currentScroll: currentScroll,
                    savedPosition: savedPosition,
                    difference: Math.abs(currentScroll - parseInt(savedPosition)),
                    isRestored: isRestored
                });
            }
            
            lastScrollPosition = currentScroll;
        }, { passive: true });
    }
})();
</script>

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

function clearFilters() {
    const url = new URL(window.location.href);
    url.searchParams.delete('provider');
    url.searchParams.delete('service_type');
    window.location.href = url.toString();
}
</script>


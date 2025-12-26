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
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
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
            <div class="plans-list-container">
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
                    $priceMain = '월 ' . number_format((int)$product['price_main']) . ($product['price_main_unit'] ?: '원');
                    $discountPeriod = formatDiscountPeriod(
                        $product['discount_period'] ?? '',
                        $product['discount_period_value'] ?? null,
                        $product['discount_period_unit'] ?? ''
                    );
                    $priceAfterValue = formatPriceAfter(
                        $product['price_after_type'] ?? '',
                        $product['price_after'] ?? null,
                        $product['price_after_unit'] ?? ''
                    );
                    
                    $priceAfter = '';
                    if ($discountPeriod !== '-' && $priceAfterValue !== '-') {
                        $priceAfter = $discountPeriod . ' 이후 ' . $priceAfterValue;
                    } elseif ($priceAfterValue !== '-') {
                        $priceAfter = $priceAfterValue;
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
                        'price_main' => $priceMain,
                        'price_after' => $priceAfter,
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


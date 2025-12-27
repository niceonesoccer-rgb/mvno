<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

// 로그인 체크를 위한 auth-functions 포함 (세션 설정과 함께 세션을 시작함)
require_once '../includes/data/auth-functions.php';

// 로그인 체크 - 로그인하지 않은 경우 회원가입 모달로 리다이렉트
if (!isLoggedIn()) {
    // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    // 로그인 모달이 있는 홈으로 리다이렉트 (모달 자동 열기)
    header('Location: /MVNO/?show_login=1');
    exit;
}

// type 파라미터 확인 (mvno, mno, 또는 mno-sim)
$type = isset($_GET['type']) ? $_GET['type'] : 'mvno';
$is_mno = ($type === 'mno');
$is_mno_sim = ($type === 'mno-sim');

// 헤더 포함
include '../includes/header.php';

// 현재 사용자 ID 가져오기
$currentUserId = getCurrentUserId();
if (!$currentUserId) {
    // 세션 정리 후 로그인 페이지로 리다이렉트
    if (isset($_SESSION['logged_in'])) {
        unset($_SESSION['logged_in']);
    }
    if (isset($_SESSION['user_id'])) {
        unset($_SESSION['user_id']);
    }
    // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: /MVNO/?show_login=1');
    exit;
}

// 데이터베이스에서 찜한 상품 ID 가져오기
require_once '../includes/data/db-config.php';
$pdo = getDBConnection();

// 변수 초기화 (혼선 방지)
$plans = [];
$phones = [];
$mnoSimPlans = [];
$wishlistProductIds = [];

if ($pdo) {
    try {
        if ($is_mno_sim) {
            $productType = 'mno-sim';
        } elseif ($is_mno) {
            $productType = 'mno';
        } else {
            $productType = 'mvno';
        }
        $stmt = $pdo->prepare("
            SELECT product_id 
            FROM product_favorites 
            WHERE user_id = :user_id AND product_type = :product_type
        ");
        $stmt->execute([
            ':user_id' => (string)$currentUserId,
            ':product_type' => $productType
        ]);
        $wishlistProductIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (PDOException $e) {
        error_log("Error fetching wishlist: " . $e->getMessage());
        $wishlistProductIds = [];
    }
}

// 페이지 번호 가져오기
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// 타입에 따라 데이터 가져오기
if ($is_mno_sim) {
    // 통신사단독유심 데이터
    require_once '../includes/data/plan-data.php';
    require_once '../includes/data/product-functions.php';
    
    // 위시리스트에 있는 통신사단독유심 상품만 가져오기
    if (!empty($wishlistProductIds)) {
        // 이름이 있는 파라미터로 변경
        $placeholders = [];
        $params = [];
        foreach ($wishlistProductIds as $idx => $id) {
            $paramName = ':wishlist_id_' . $idx;
            $placeholders[] = $paramName;
            $params[$paramName] = $id;
        }
        $placeholdersStr = implode(',', $placeholders);
        
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
            WHERE p.id IN ({$placeholdersStr}) AND p.status = 'active'
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        // 모든 파라미터 바인딩
        foreach ($params as $paramName => $value) {
            $stmt->bindValue($paramName, $value, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $mnoSimProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 전체 개수 조회
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM products p
            INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
            WHERE p.id IN ({$placeholdersStr}) AND p.status = 'active'
        ");
        // 모든 파라미터 바인딩
        foreach ($params as $paramName => $value) {
            $countStmt->bindValue($paramName, $value, PDO::PARAM_INT);
        }
        $countStmt->execute();
        $totalCount = intval($countStmt->fetch(PDO::FETCH_ASSOC)['total']);
        
        // plan 카드 형식으로 변환
        foreach ($mnoSimProducts as $product) {
            $plan = convertMnoSimProductToPlanCard($product);
            $plan['is_favorited'] = true;
            $plan['link_url'] = '/MVNO/mno-sim/mno-sim-detail.php?id=' . $product['id'];
            $plan['item_type'] = 'mno-sim'; // 찜 버튼용 타입 (확실히 설정)
            $mnoSimPlans[] = $plan;
        }
    } else {
        $totalCount = 0;
    }
    
    $page_title = '찜한 통신사단독유심 내역';
    $result_count = number_format($totalCount) . '개의 결과';
} elseif ($is_mno) {
    // 통신사폰 데이터
    require_once '../includes/data/phone-data.php';
    
    // 모든 통신사폰 데이터 가져오기
    $allPhones = getPhonesData(10000);
    
    // 위시리스트에 있는 상품만 필터링
    if (!empty($wishlistProductIds)) {
        $filteredPhones = array_filter($allPhones, function($phone) use ($wishlistProductIds) {
            $phoneId = isset($phone['id']) ? (int)$phone['id'] : null;
            return $phoneId && in_array($phoneId, $wishlistProductIds, true);
        });
        $filteredPhones = array_values($filteredPhones); // 인덱스 재정렬
        $totalCount = count($filteredPhones);
        $phones = array_slice($filteredPhones, $offset, $limit);
    } else {
        $totalCount = 0;
        $phones = [];
    }
    
    $page_title = '찜한 통신사폰 요금제';
    $result_count = number_format($totalCount) . '개의 결과';
} else {
    // 알뜰폰 데이터
    require_once '../includes/data/plan-data.php';
    
    // 모든 알뜰폰 데이터 가져오기
    $allPlans = getPlansDataFromDB(10000, 'active');
    
    // 위시리스트에 있는 상품만 필터링
    if (!empty($wishlistProductIds)) {
        $filteredPlans = array_filter($allPlans, function($plan) use ($wishlistProductIds) {
            $planId = isset($plan['id']) ? (int)$plan['id'] : null;
            return $planId && in_array($planId, $wishlistProductIds, true);
        });
        $filteredPlans = array_values($filteredPlans); // 인덱스 재정렬
        $totalCount = count($filteredPlans);
        $plans = array_slice($filteredPlans, $offset, $limit);
    } else {
        $totalCount = 0;
        $plans = [];
    }
    
    $page_title = '찜한 알뜰폰 요금제';
    $result_count = number_format($totalCount) . '개의 결과';
}
?>

<main class="main-content">
    <div class="content-layout wishlist-page">
        <!-- 페이지 제목 -->
        <div style="margin-bottom: 24px; padding-top: 24px; display: flex; align-items: center; gap: 12px;">
            <a href="/MVNO/mypage/mypage.php" style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; transition: background-color 0.2s; text-decoration: none; color: inherit;" onmouseover="this.style.backgroundColor='#f3f4f6'" onmouseout="this.style.backgroundColor='transparent'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="transform: rotate(180deg);">
                    <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            <h1 style="font-size: 24px; font-weight: 700; margin: 0; flex: 1;"><?php echo htmlspecialchars($page_title); ?></h1>
        </div>

        <!-- 검색 결과 개수 -->
        <div class="plans-results-count">
            <span><?php echo htmlspecialchars($result_count); ?></span>
        </div>

        <!-- 요금제/통신사폰 카드 목록 -->
        <?php if ($is_mno_sim): ?>
            <!-- 통신사단독유심 목록 레이아웃 -->
            <div class="plans-list-container" id="mno-sim-products-container">
                <?php foreach ($mnoSimPlans as $plan): ?>
                    <div class="plan-item-wrapper">
                        <?php
                        $card_wrapper_class = '';
                        $layout_type = 'list';
                        include '../includes/components/plan-card.php';
                        ?>
                        <hr class="plan-card-divider">
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($is_mno): ?>
            <!-- 통신사폰 목록 레이아웃 -->
            <?php
            $section_title = '';
            $is_wishlist = true; // 위시리스트 페이지 플래그
            include '../includes/layouts/phone-list-layout.php';
            ?>
        <?php else: ?>
            <!-- 알뜰폰 목록 레이아웃 -->
            <?php
            $section_title = '';
            $is_wishlist = true; // 위시리스트 페이지 플래그
            include '../includes/layouts/plan-list-layout.php';
            ?>
        <?php endif; ?>
        
        <!-- 더보기 버튼 -->
        <?php 
        $currentCount = 0;
        $hasMore = false;
        if ($is_mno_sim) {
            $currentCount = count($mnoSimPlans);
            $hasMore = ($offset + $currentCount) < $totalCount;
        } elseif ($is_mno) {
            $currentCount = count($phones);
            $hasMore = ($offset + $currentCount) < $totalCount;
        } else {
            $currentCount = count($plans);
            $hasMore = ($offset + $currentCount) < $totalCount;
        }
        $remainingCount = max(0, $totalCount - ($offset + $currentCount));
        ?>
        <?php if ($hasMore && $totalCount > 0): ?>
            <div class="load-more-container" id="load-more-anchor">
                <button id="load-more-wishlist-btn" class="load-more-btn" 
                        data-type="<?php echo $is_mno_sim ? 'mno-sim' : ($is_mno ? 'mno' : 'mvno'); ?>" 
                        data-page="2" 
                        data-total="<?php echo $totalCount; ?>"
                        data-wishlist="true">
                    더보기 (<span id="remaining-count"><?php echo number_format($remainingCount); ?></span>개 남음)
                </button>
            </div>
        <?php endif; ?>
    </div>
</main>

<script src="../assets/js/plan-accordion.js" defer></script>
<script src="../assets/js/favorite-heart.js" defer></script>
<script src="../assets/js/share.js" defer></script>
<!-- 더보기 기능 스크립트 -->
<script src="/MVNO/assets/js/load-more-products.js?v=2"></script>

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

<?php
// 푸터 포함
include '../includes/footer.php';
?>

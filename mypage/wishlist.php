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

// type 파라미터 확인 (mvno 또는 mno)
$type = isset($_GET['type']) ? $_GET['type'] : 'mvno';
$is_mno = ($type === 'mno');

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
$wishlistProductIds = [];

if ($pdo) {
    try {
        $productType = $is_mno ? 'mno' : 'mvno';
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

// 타입에 따라 데이터 가져오기
if ($is_mno) {
    // 통신사폰 데이터
    require_once '../includes/data/phone-data.php';
    
    // 모든 통신사폰 데이터 가져오기 (큰 limit 사용)
    $allPhones = getPhonesData(1000);
    
    // 위시리스트에 있는 상품만 필터링
    if (!empty($wishlistProductIds)) {
        $phones = array_filter($allPhones, function($phone) use ($wishlistProductIds) {
            $phoneId = isset($phone['id']) ? (int)$phone['id'] : null;
            return $phoneId && in_array($phoneId, $wishlistProductIds, true);
        });
        $phones = array_values($phones); // 인덱스 재정렬
    }
    
    $page_title = '찜한 통신사폰 요금제';
    $result_count = count($phones) . '개의 결과';
} else {
    // 알뜰폰 데이터
    require_once '../includes/data/plan-data.php';
    
    // 모든 알뜰폰 데이터 가져오기 (큰 limit 사용)
    $allPlans = getPlansData(1000);
    
    // 위시리스트에 있는 상품만 필터링
    if (!empty($wishlistProductIds)) {
        $plans = array_filter($allPlans, function($plan) use ($wishlistProductIds) {
            $planId = isset($plan['id']) ? (int)$plan['id'] : null;
            return $planId && in_array($planId, $wishlistProductIds, true);
        });
        $plans = array_values($plans); // 인덱스 재정렬
    }
    
    $page_title = '찜한 알뜰폰 요금제';
    $result_count = count($plans) . '개의 결과';
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
        <?php if ($is_mno): ?>
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
    </div>
</main>

<script src="../assets/js/plan-accordion.js" defer></script>
<script src="../assets/js/favorite-heart.js" defer></script>
<script src="../assets/js/share.js" defer></script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';

// type 파라미터 확인 (mvno 또는 mno)
$type = isset($_GET['type']) ? $_GET['type'] : 'mvno';
$is_mno = ($type === 'mno');

// 헤더 포함
include '../includes/header.php';

// 변수 초기화 (혼선 방지)
$plans = [];
$phones = [];

// 타입에 따라 데이터 가져오기
if ($is_mno) {
    // 통신사폰 데이터
    require_once '../includes/data/phone-data.php';
    $phones = getPhonesData(10);
    $page_title = '찜한 통신사폰 요금제';
    $result_count = count($phones) . '개의 결과';
} else {
    // 알뜰폰 데이터
    require_once '../includes/data/plan-data.php';
    $plans = getPlansData(10);
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

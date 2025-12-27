<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mno';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

// 모니터링 시스템 (선택사항 - 주석 해제하여 사용)
// require_once 'includes/monitor.php';
// $monitor = new ConnectionMonitor();
// $monitor->logConnection();

// 헤더 포함
include '../includes/header.php';

// 통신사폰 데이터 가져오기
require_once '../includes/data/phone-data.php';

// 페이지 번호 가져오기
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$allPhones = getPhonesData(10000); // 전체 가져온 후 슬라이스
$totalCount = count($allPhones);
$phones = array_slice($allPhones, $offset, $limit);
?>

<main class="main-content">
    <!-- 통신사폰 목록 섹션 -->
    <div class="content-layout">
        <div class="plans-main-layout">
            <!-- 왼쪽 섹션: 통신사폰 목록 -->
            <div class="plans-left-section">
                <!-- 테마별 통신사폰 섹션 -->
                <section class="theme-plans-list-section">
                    <!-- 통신사폰 목록 레이아웃 -->
                    <?php
                    $section_title = '';
                    include '../includes/layouts/phone-list-layout.php';
                    ?>
                    <?php 
                    $currentCount = count($phones);
                    $remainingCount = max(0, $totalCount - ($offset + $currentCount));
                    $nextPage = $page + 1;
                    $hasMore = ($offset + $currentCount) < $totalCount;
                    ?>
                    <?php if ($hasMore && $totalCount > 0): ?>
                    <div class="load-more-container" id="load-more-anchor">
                        <a href="?page=<?php echo $nextPage; ?>#load-more-anchor" class="load-more-btn">
                            더보기 (<?php echo number_format($remainingCount); ?>개 남음)
                        </a>
                    </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</main>

<?php
// 푸터 포함
include '../includes/footer.php';
?>

<script src="/MVNO/assets/js/plan-accordion.js" defer></script>
<script src="/MVNO/assets/js/favorite-heart.js" defer></script>
<script src="/MVNO/assets/js/share.js" defer></script>

<script>
// 더보기 버튼 클릭 시 현재 스크롤 위치 저장
document.addEventListener('DOMContentLoaded', function() {
    const loadMoreBtn = document.querySelector('.load-more-btn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            // 현재 스크롤 위치 저장
            const scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
            sessionStorage.setItem('loadMoreScrollPosition', scrollPosition.toString());
        });
    }
});

// 페이지 로드 후 스크롤 위치 복원
window.addEventListener('DOMContentLoaded', function() {
    // 저장된 스크롤 위치가 있으면 복원
    const savedScrollPosition = sessionStorage.getItem('loadMoreScrollPosition');
    if (savedScrollPosition !== null) {
        // 저장된 위치 삭제
        sessionStorage.removeItem('loadMoreScrollPosition');
        
        // DOM이 완전히 로드될 때까지 대기 후 스크롤 복원
        setTimeout(function() {
            window.scrollTo({
                top: parseInt(savedScrollPosition),
                behavior: 'auto' // 즉시 이동 (smooth 대신)
            });
        }, 50);
    } else if (window.location.hash === '#load-more-anchor') {
        // 저장된 위치가 없고 앵커가 있으면 앵커 위치로 이동
        setTimeout(function() {
            const element = document.querySelector('#load-more-anchor');
            if (element) {
                const offset = 100; // 더보기 버튼 위쪽 여백
                const elementPosition = element.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - offset;
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        }, 100);
    }
});
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

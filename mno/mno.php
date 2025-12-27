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
require_once '../includes/data/filter-data.php';

// 페이지 번호 가져오기
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$allPhones = getPhonesData(10000); // 전체 가져온 후 슬라이스
$totalCount = count($allPhones);
$phones = array_slice($allPhones, $offset, $limit);
$mno_filters = getMnoFilters();
?>

<main class="main-content">
    <div class="plans-filter-section">
        <div class="plans-filter-inner">
            <div class="plans-filter-group">
                <div class="plans-filter-row">
                    <button class="plans-filter-btn">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="16" height="16">
                            <path d="M3 7C3 6.44772 3.44772 6 4 6H20C20.5523 6 21 6.44772 21 7C21 7.55228 20.5523 8 20 8H4C3.44772 8 3 7.55228 3 7Z" fill="#3F4750"/>
                            <path d="M3 12C3 11.4477 3.44772 11 4 11H20C20.5523 11 21 11.4477 21 12C21 12.5523 20.5523 13 20 13H4C3.44772 13 3 12.5523 3 12Z" fill="#3F4750"/>
                            <path d="M4 16C3.44772 16 3 16.4477 3 17C3 17.5523 3.44772 18 4 18H20C20.5523 18 21 17.5523 21 17C21 16.4477 20.5523 16 20 16H4Z" fill="#3F4750"/>
                            <circle cx="6" cy="7" r="1.5" fill="#6366F1"/>
                            <circle cx="18" cy="12" r="1.5" fill="#6366F1"/>
                            <circle cx="6" cy="17" r="1.5" fill="#6366F1"/>
                        </svg>
                        <span class="plans-filter-text">필터</span>
                    </button>
                    <?php foreach ($mno_filters as $filter): ?>
                        <button class="plans-filter-btn" data-filter="<?php echo htmlspecialchars($filter); ?>">
                            <span class="plans-filter-text"><?php echo htmlspecialchars($filter); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <hr class="plans-filter-divider">
    </div>

    <!-- 통신사폰 목록 섹션 -->
    <div class="content-layout">
        <div class="plans-main-layout">
            <!-- 왼쪽 섹션: 통신사폰 목록 -->
            <div class="plans-left-section">
                <!-- 테마별 통신사폰 섹션 -->
                <section class="theme-plans-list-section">
                    <!-- 통신사폰 목록 레이아웃 -->
                    <?php
                    $section_title = number_format($totalCount) . '개의 결과';
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
                        <button id="load-more-mno-btn" class="load-more-btn" data-type="mno" data-page="2" data-total="<?php echo $totalCount; ?>">
                            더보기 (<span id="remaining-count"><?php echo number_format($remainingCount); ?></span>개 남음)
                        </button>
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

// 필터 버튼 클릭 이벤트
(function() {
    const filterButtons = document.querySelectorAll('.plans-filter-btn[data-filter]');
    const phoneItems = document.querySelectorAll('.basic-plan-card');
    
    if (filterButtons.length === 0 || phoneItems.length === 0) return;
    
    function applyFilters() {
        const activeFilters = Array.from(document.querySelectorAll('.plans-filter-btn.active[data-filter]'))
            .map(btn => btn.getAttribute('data-filter'))
            .filter(f => f !== null);
        
        let visibleCount = 0;
        
        phoneItems.forEach(item => {
            if (activeFilters.length === 0) {
                item.style.display = '';
                visibleCount++;
                return;
            }
            
            const phoneText = item.textContent.toLowerCase();
            const matches = activeFilters.some(filter => {
                const filterText = filter.replace('#', '').toLowerCase();
                return phoneText.includes(filterText);
            });
            
            if (matches) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
        
        // 결과 개수 업데이트
        const resultsCountElement = document.querySelector('.plans-results-count span');
        if (resultsCountElement) {
            resultsCountElement.textContent = visibleCount.toLocaleString() + '개의 결과';
        }
    }
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.classList.toggle('active');
            applyFilters();
        });
    });
})();
</script>

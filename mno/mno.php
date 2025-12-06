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
$phones = getPhonesData(10);
?>

<main class="main-content">
    <!-- 필터 섹션 (스크롤 시 상단 고정) - 통신사폰 전용 -->
    <div class="mno-filter-section">
        <div class="mno-filter-inner">

            <!-- 필터 및 정렬 버튼 그룹 -->
            <div class="mno-filter-group">
                <!-- 첫 번째 행: 필터 + 인터넷 결합 + 해시태그 버튼들 -->
                <div class="mno-filter-row">
                    <button class="mno-filter-btn">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="16" height="16">
                            <path d="M3 7C3 6.44772 3.44772 6 4 6H20C20.5523 6 21 6.44772 21 7C21 7.55228 20.5523 8 20 8H4C3.44772 8 3 7.55228 3 7Z" fill="#3F4750"></path>
                            <path d="M3 12C3 11.4477 3.44772 11 4 11H20C20.5523 11 21 11.4477 21 12C21 12.5523 20.5523 13 20 13H4C3.44772 13 3 12.5523 3 12Z" fill="#3F4750"></path>
                            <path d="M4 16C3.44772 16 3 16.4477 3 17C3 17.5523 3.44772 18 4 18H20C20.5523 18 21 17.5523 21 17C21 16.4477 20.5523 16 20 16H4Z" fill="#3F4750"></path>
                            <circle cx="6" cy="7" r="1.5" fill="#6366F1"></circle>
                            <circle cx="18" cy="12" r="1.5" fill="#6366F1"></circle>
                            <circle cx="6" cy="17" r="1.5" fill="#6366F1"></circle>
                        </svg>
                        <span class="mno-filter-text">필터</span>
                    </button>
                    <?php
                    // 통신사폰 필터 가져오기
                    require_once '../includes/data/filter-data.php';
                    $mno_filters = getMnoFilters();
                    foreach ($mno_filters as $filter): ?>
                    <button class="mno-filter-btn" data-filter="<?php echo htmlspecialchars($filter); ?>">
                        <span class="mno-filter-text"><?php echo htmlspecialchars($filter); ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <hr class="mno-filter-divider">
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
                    // 통신사폰 데이터 가져오기
                    require_once '../includes/data/phone-data.php';
                    $phones = getPhonesData(10);
                    $section_title = '2,415개의 결과';
                    include '../includes/layouts/phone-list-layout.php';
                    ?>
                    
                </section>

                <!-- 페이지네이션 -->
                <div class="pagination-wrapper" data-sentry-component="LinkPagination" data-sentry-source-file="LinkPagination.tsx">
                    <ul class="pagination-list">
                        <li>
                            <a class="pagination-btn pagination-nav" href="mno.php?page=10" aria-label="이전 페이지">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" data-sentry-element="svg" data-sentry-component="ChevronLeftIcon" data-sentry-source-file="ChevronLeftIcon.tsx">
                                    <path d="M10.5303 3.53033C10.8232 3.23744 10.8232 2.76256 10.5303 2.46967C10.2374 2.17678 9.76256 2.17678 9.46967 2.46967L10.5303 3.53033ZM5 8L4.46967 7.46967C4.17678 7.76256 4.17678 8.23744 4.46967 8.53033L5 8ZM9.46967 13.5303C9.76256 13.8232 10.2374 13.8232 10.5303 13.5303C10.8232 13.2374 10.8232 12.7626 10.5303 12.4697L9.46967 13.5303ZM9.46967 2.46967L4.46967 7.46967L5.53033 8.53033L10.5303 3.53033L9.46967 2.46967ZM4.46967 8.53033L9.46967 13.5303L10.5303 12.4697L5.53033 7.46967L4.46967 8.53033Z" data-sentry-element="path" data-sentry-source-file="ChevronLeftIcon.tsx"></path>
                                </svg>
                            </a>
                        </li>
                        <li><a class="pagination-btn pagination-page active" href="mno.php?page=11">11</a></li>
                        <li><a class="pagination-btn pagination-page" href="mno.php?page=12">12</a></li>
                        <li><a class="pagination-btn pagination-page" href="mno.php?page=13">13</a></li>
                        <li><a class="pagination-btn pagination-page" href="mno.php?page=14">14</a></li>
                        <li><a class="pagination-btn pagination-page" href="mno.php?page=15">15</a></li>
                        <li><a class="pagination-btn pagination-page" href="mno.php?page=16">16</a></li>
                        <li><a class="pagination-btn pagination-page" href="mno.php?page=17">17</a></li>
                        <li><a class="pagination-btn pagination-page" href="mno.php?page=18">18</a></li>
                        <li><a class="pagination-btn pagination-page" href="mno.php?page=19">19</a></li>
                        <li><a class="pagination-btn pagination-page" href="mno.php?page=20">20</a></li>
                        <li>
                            <a class="pagination-btn pagination-nav" href="mno.php?page=21" aria-label="다음 페이지">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" data-sentry-element="svg" data-sentry-component="ChevronRightIcon" data-sentry-source-file="ChevronRightIcon.tsx">
                                    <path d="M5.46967 12.4697C5.17678 12.7626 5.17678 13.2374 5.46967 13.5303C5.76256 13.8232 6.23744 13.8232 6.53033 13.5303L5.46967 12.4697ZM11 8L11.5303 8.53033C11.8232 8.23744 11.8232 7.76256 11.5303 7.46967L11 8ZM6.53033 2.46967C6.23744 2.17678 5.76256 2.17678 5.46967 2.46967C5.17678 2.76256 5.17678 3.23744 5.46967 3.53033L6.53033 2.46967ZM6.53033 13.5303L11.5303 8.53033L10.4697 7.46967L5.46967 12.4697L6.53033 13.5303ZM11.5303 7.46967L6.53033 2.46967L5.46967 3.53033L10.4697 8.53033L11.5303 7.46967Z" data-sentry-element="path" data-sentry-source-file="ChevronRightIcon.tsx"></path>
                                </svg>
                            </a>
                        </li>
                    </ul>
                </div>
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
// 필터가 화면에서 사라질 때 상단에 고정 (통신사폰 페이지)
(function() {
    const filterSection = document.querySelector('.mno-filter-section');
    const header = document.getElementById('mainHeader');
    const resultsCount = document.querySelector('.plans-results-count');
    const themeSection = document.querySelector('.theme-plans-list-section');
    
    if (!filterSection) return;
    
    let lastScrollTop = 0;
    let isFilterSticky = false;
    let isFilterFixed = false;
    let filterOriginalTop = 0;
    let filterHeight = 0;
    
    // 필터 높이 계산
    function calculateFilterHeight() {
        if (filterSection) {
            filterHeight = filterSection.offsetHeight;
        }
    }
    
    // 초기 필터 높이 계산
    calculateFilterHeight();
    
    // 리사이즈 시 필터 높이 재계산
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            calculateFilterHeight();
        }, 100);
    });
    
    function handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const filterRect = filterSection.getBoundingClientRect();
        const filterTop = filterRect.top;
        
        // 필터의 원래 위치 저장 (처음 로드 시)
        if (filterOriginalTop === 0 && scrollTop === 0) {
            filterOriginalTop = filterRect.top + scrollTop;
        }
        
        // 스크롤이 시작되면 sticky 모드로 전환
        if (scrollTop > 10 && !isFilterSticky) {
            calculateFilterHeight(); // 필터 높이 재계산
            filterSection.classList.add('filter-sticky');
            if (resultsCount) resultsCount.classList.add('filter-active');
            if (themeSection) themeSection.classList.add('filter-active');
            isFilterSticky = true;
        }
        
        // 필터가 화면 상단 밖으로 나갔는지 확인 (위로 스크롤해서 사라짐)
        if (filterTop < 0 && isFilterSticky && !isFilterFixed) {
            // 필터가 사라졌으므로 상단에 고정
            calculateFilterHeight(); // 필터 높이 재계산
            filterSection.classList.remove('filter-sticky');
            filterSection.classList.add('filter-fixed');
            isFilterFixed = true;
        } 
        // 스크롤이 다시 위로 올라가서 필터의 원래 위치 근처에 도달했는지 확인
        else if (scrollTop < filterOriginalTop - 50 && isFilterFixed) {
            // 필터를 sticky 모드로 복원
            calculateFilterHeight(); // 필터 높이 재계산
            filterSection.classList.remove('filter-fixed');
            filterSection.classList.add('filter-sticky');
            isFilterFixed = false;
        }
        // 스크롤이 맨 위로 돌아갔을 때
        else if (scrollTop <= 10 && isFilterSticky) {
            // 필터를 원래 위치로 복원
            filterSection.classList.remove('filter-sticky');
            filterSection.classList.remove('filter-fixed');
            if (resultsCount) resultsCount.classList.remove('filter-active');
            if (themeSection) themeSection.classList.remove('filter-active');
            isFilterSticky = false;
            isFilterFixed = false;
        }
        
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    }
    
    // 스크롤 이벤트 최적화 (requestAnimationFrame 사용)
    let ticking = false;
    function onScroll() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                handleScroll();
                ticking = false;
            });
            ticking = true;
        }
    }
    
    // 스크롤 이벤트 리스너
    window.addEventListener('scroll', onScroll, { passive: true });
    
    // 초기 실행
    handleScroll();
})();

// 필터 버튼 클릭 이벤트 핸들러 (통신사폰 전용)
(function() {
    const filterButtons = document.querySelectorAll('.mno-filter-btn');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // 필터 버튼만 처리 (필터 아이콘 버튼은 제외)
            const filterValue = this.getAttribute('data-filter');
            if (!filterValue) return; // 필터 버튼이 아니면 무시
            
            // 클릭된 버튼의 active 상태 토글
            if (this.classList.contains('active')) {
                this.classList.remove('active');
            } else {
                this.classList.add('active');
            }
            
            // 필터 적용
            applyFilters();
        });
    });
    
    // 필터 적용 함수
    function applyFilters() {
        const activeFilters = Array.from(document.querySelectorAll('.mno-filter-btn.active'))
            .map(btn => btn.getAttribute('data-filter'))
            .filter(f => f !== null);
        
        // 통신사폰 카드 요소 찾기
        const phoneCards = document.querySelectorAll('.basic-plan-card, .plan-card');
        
        phoneCards.forEach(card => {
            if (activeFilters.length === 0) {
                // 필터가 없으면 모두 표시
                card.style.display = '';
                return;
            }
            
            // 폰 정보 가져오기 (카드 전체 텍스트에서 검색)
            const cardText = card.textContent || '';
            const cardTextLower = cardText.toLowerCase();
            
            // 필터 매칭 확인
            let matches = false;
            activeFilters.forEach(filter => {
                const filterText = filter.replace('#', '').toLowerCase();
                // 필터별 특별 처리
                if (filterText === '갤럭시' && cardTextLower.includes('galaxy')) {
                    matches = true;
                } else if (filterText === '아이폰' && cardTextLower.includes('iphone')) {
                    matches = true;
                } else if (filterText === '공짜' && (cardTextLower.includes('무료') || cardTextLower.includes('공짜') || cardTextLower.includes('0원'))) {
                    matches = true;
                } else if (cardTextLower.includes(filterText)) {
                    matches = true;
                }
            });
            
            // 매칭되면 표시, 아니면 숨김
            card.style.display = matches ? '' : 'none';
            
            // 구분선(hr)도 함께 숨김/표시
            const divider = card.nextElementSibling;
            if (divider && divider.tagName === 'HR' && divider.classList.contains('plan-card-divider')) {
                divider.style.display = matches ? '' : 'none';
            }
        });
    }
})();

</script>

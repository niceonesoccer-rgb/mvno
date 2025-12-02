<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mvno';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

// 모니터링 시스템 (선택사항 - 주석 해제하여 사용)
// require_once 'includes/monitor.php';
// $monitor = new ConnectionMonitor();
// $monitor->logConnection();

// 헤더 포함
include '../includes/header.php';

// 요금제 데이터 가져오기
require_once '../includes/data/plan-data.php';
$plans = getPlansData(10);
?>

<main class="main-content">
    <!-- 필터 섹션 (스크롤 시 상단 고정) -->
    <div class="plans-filter-section">
        <div class="plans-filter-inner">

            <!-- 필터 및 정렬 버튼 그룹 -->
            <div class="plans-filter-group">
                <!-- 첫 번째 행: 필터 + 인터넷 결합 + 해시태그 버튼들 -->
                <div class="plans-filter-row">
                    <button class="plans-filter-btn">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M14.5 11.5C16.0487 11.5 17.3625 10.4941 17.8237 9.09999H19.9C20.5075 9.09999 21 8.60751 21 7.99999C21 7.39248 20.5075 6.89999 19.9 6.89999H17.8236C17.3625 5.50589 16.0487 4.5 14.5 4.5C12.9513 4.5 11.6375 5.50589 11.1764 6.89999H4.1C3.49249 6.89999 3 7.39248 3 7.99999C3 8.60751 3.49249 9.09999 4.1 9.09999H11.1763C11.6375 10.4941 12.9513 11.5 14.5 11.5ZM14.5 9.5C15.3284 9.5 16 8.82843 16 8C16 7.17157 15.3284 6.5 14.5 6.5C13.6716 6.5 13 7.17157 13 8C13 8.82843 13.6716 9.5 14.5 9.5Z" fill="#3F4750"></path>
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M3 16C3 15.3925 3.49249 14.9 4.1 14.9H6.17635C6.6375 13.5059 7.95128 12.5 9.5 12.5C11.0487 12.5 12.3625 13.5059 12.8236 14.9H19.9C20.5075 14.9 21 15.3925 21 16C21 16.6075 20.5075 17.1 19.9 17.1H12.8237C12.3625 18.4941 11.0487 19.5 9.5 19.5C7.95128 19.5 6.6375 18.4941 6.17635 17.1H4.1C3.49249 17.1 3 16.6075 3 16ZM11 16C11 16.8284 10.3284 17.5 9.5 17.5C8.67157 17.5 8 16.8284 8 16C8 15.1716 8.67157 14.5 9.5 14.5C10.3284 14.5 11 15.1716 11 16Z" fill="#3F4750"></path>
                        </svg>
                        <span class="plans-filter-text">필터</span>
                    </button>
                    <button class="plans-filter-btn">
                        <span class="plans-filter-text">인터넷 결합</span>
                    </button>
                    <button class="plans-filter-btn">
                        <span class="plans-filter-text">#베스트 요금제</span>
                    </button>
                    <button class="plans-filter-btn">
                        <span class="plans-filter-text">#만원 미만</span>
                    </button>
                    <button class="plans-filter-btn">
                        <span class="plans-filter-text">#장기 할인</span>
                    </button>
                    <button class="plans-filter-btn">
                        <span class="plans-filter-text">#100원</span>
                    </button>
                </div>
            </div>
        </div>
        <hr class="plans-filter-divider">
    </div>

    <!-- 요금제 목록 섹션 -->
    <div class="content-layout">
        <div class="plans-main-layout">
            <!-- 왼쪽 섹션: 요금제 목록 -->
            <div class="plans-left-section">
                <!-- 테마별 요금제 섹션 -->
                <section class="theme-plans-list-section">

                    <!-- 요금제 목록 레이아웃 -->
                    <?php
                    // 요금제 데이터 가져오기
                    require_once '../includes/data/plan-data.php';
                    $plans = getPlansData(10);
                    $section_title = '2,415개의 결과';
                    include '../includes/layouts/plan-list-layout.php';
                    ?>
                    
                </section>

                <!-- 페이지네이션 -->
                <div class="pagination-wrapper" data-sentry-component="LinkPagination" data-sentry-source-file="LinkPagination.tsx">
                    <ul class="pagination-list">
                        <li>
                            <a class="pagination-btn pagination-nav" href="mvno.php?page=10" aria-label="이전 페이지">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" data-sentry-element="svg" data-sentry-component="ChevronLeftIcon" data-sentry-source-file="ChevronLeftIcon.tsx">
                                    <path d="M10.5303 3.53033C10.8232 3.23744 10.8232 2.76256 10.5303 2.46967C10.2374 2.17678 9.76256 2.17678 9.46967 2.46967L10.5303 3.53033ZM5 8L4.46967 7.46967C4.17678 7.76256 4.17678 8.23744 4.46967 8.53033L5 8ZM9.46967 13.5303C9.76256 13.8232 10.2374 13.8232 10.5303 13.5303C10.8232 13.2374 10.8232 12.7626 10.5303 12.4697L9.46967 13.5303ZM9.46967 2.46967L4.46967 7.46967L5.53033 8.53033L10.5303 3.53033L9.46967 2.46967ZM4.46967 8.53033L9.46967 13.5303L10.5303 12.4697L5.53033 7.46967L4.46967 8.53033Z" data-sentry-element="path" data-sentry-source-file="ChevronLeftIcon.tsx"></path>
                                </svg>
                            </a>
                        </li>
                        <li><a class="pagination-btn pagination-page active" href="mvno.php?page=11">11</a></li>
                        <li><a class="pagination-btn pagination-page" href="mvno.php?page=12">12</a></li>
                        <li><a class="pagination-btn pagination-page" href="mvno.php?page=13">13</a></li>
                        <li><a class="pagination-btn pagination-page" href="mvno.php?page=14">14</a></li>
                        <li><a class="pagination-btn pagination-page" href="mvno.php?page=15">15</a></li>
                        <li><a class="pagination-btn pagination-page" href="mvno.php?page=16">16</a></li>
                        <li><a class="pagination-btn pagination-page" href="mvno.php?page=17">17</a></li>
                        <li><a class="pagination-btn pagination-page" href="mvno.php?page=18">18</a></li>
                        <li><a class="pagination-btn pagination-page" href="mvno.php?page=19">19</a></li>
                        <li><a class="pagination-btn pagination-page" href="mvno.php?page=20">20</a></li>
                        <li>
                            <a class="pagination-btn pagination-nav" href="mvno.php?page=21" aria-label="다음 페이지">
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

<script>
// 필터가 화면에서 사라질 때 상단에 고정 (요금제 페이지)
(function() {
    const filterSection = document.querySelector('.plans-filter-section');
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

// 필터 버튼 클릭 이벤트 핸들러
(function() {
    const filterButtons = document.querySelectorAll('.plans-filter-btn');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // 클릭된 버튼의 active 상태 토글
            if (this.classList.contains('active')) {
                this.classList.remove('active');
            } else {
                this.classList.add('active');
            }
        });
    });
})();

</script>

<?php
$current_page = 'mvno';
$is_main_page = true;

include '../includes/header.php';

require_once '../includes/data/plan-data.php';
require_once '../includes/data/filter-data.php';

$plans = getPlansDataFromDB(1000, 'active');
$mvno_filters = getMvnoFilters();
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
                    <?php foreach ($mvno_filters as $filter): ?>
                        <button class="plans-filter-btn" data-filter="<?php echo htmlspecialchars($filter); ?>">
                            <span class="plans-filter-text"><?php echo htmlspecialchars($filter); ?></span>
                        </button>
                    <?php endforeach; ?>
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
                    $section_title = count($plans) . '개의 결과';
                    include '../includes/layouts/plan-list-layout.php';
                    ?>
                </section>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

<script src="/MVNO/assets/js/plan-accordion.js" defer></script>
<script src="/MVNO/assets/js/favorite-heart.js" defer></script>
<script src="/MVNO/assets/js/share.js" defer></script>

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
    const planItems = document.querySelectorAll('.plan-item, [data-plan-id]');
    
    if (filterButtons.length === 0 || planItems.length === 0) return;
    
    function applyFilters() {
        const activeFilters = Array.from(document.querySelectorAll('.plans-filter-btn.active[data-filter]'))
            .map(btn => btn.getAttribute('data-filter'))
            .filter(f => f !== null);
        
        planItems.forEach(item => {
            if (activeFilters.length === 0) {
                item.style.display = '';
                return;
            }
            
            const planText = item.textContent.toLowerCase();
            const matches = activeFilters.some(filter => {
                const filterText = filter.replace('#', '').toLowerCase();
                return planText.includes(filterText);
            });
            
            item.style.display = matches ? '' : 'none';
        });
    }
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.classList.toggle('active');
            applyFilters();
        });
    });
})();
</script>

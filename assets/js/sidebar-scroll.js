// 사이드바 스크롤 위치 초기화 및 필터 섹션 높이 계산
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.plans-sidebar');
    const filterSection = document.querySelector('.plans-filter-section');
    
    if (!sidebar) return;
    
    // 필터 섹션 높이를 CSS 변수로 설정
    if (filterSection) {
        const updateFilterHeight = () => {
            const filterHeight = filterSection.offsetHeight;
            document.documentElement.style.setProperty('--filter-section-height', filterHeight + 'px');
        };
        
        // 초기 높이 설정
        updateFilterHeight();
        
        // 리사이즈 이벤트로 높이 업데이트
        window.addEventListener('resize', updateFilterHeight, { passive: true });
        
        // MutationObserver로 필터 섹션 내용 변경 감지
        const observer = new MutationObserver(updateFilterHeight);
        observer.observe(filterSection, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    }
    
    // 초기 로드 시 사이드바 스크롤을 맨 위로 초기화
    sidebar.scrollTop = 0;
    
    // 사이드바의 원래 위치 저장
    const sidebarOriginalTop = sidebar.offsetTop;
    let wasSticky = false;
    let userScrolled = false; // 사용자가 사이드바를 스크롤했는지 추적
    
    // 사용자가 사이드바를 직접 스크롤했는지 감지
    sidebar.addEventListener('scroll', function() {
        if (sidebar.scrollTop > 0) {
            userScrolled = true;
        }
    }, { passive: true });
    
    // 스크롤 이벤트로 사이드바가 sticky가 될 때 스크롤 위치 초기화
    window.addEventListener('scroll', function() {
        if (!filterSection) return;
        
        const filterHeight = filterSection.offsetHeight;
        const headerHeight = 60; // 헤더 높이
        const dividerHeight = 1; // divider 높이
        const expectedTop = headerHeight + filterHeight + dividerHeight;
        
        const rect = sidebar.getBoundingClientRect();
        const isSticky = rect.top <= expectedTop && rect.top >= expectedTop - 5; // 5px 여유
        
        // 사이드바가 sticky 상태가 되었을 때 (이전에는 sticky가 아니었을 때)
        // 사용자가 사이드바를 스크롤하지 않았을 때만 초기화
        if (isSticky && !wasSticky && !userScrolled) {
            // 사이드바 스크롤을 맨 위로 초기화
            sidebar.scrollTop = 0;
        }
        
        wasSticky = isSticky;
    }, { passive: true });
    
    // 페이지 로드 후에도 한 번 더 확인 (사용자가 스크롤하지 않았을 때만)
    setTimeout(() => {
        if (!userScrolled) {
            sidebar.scrollTop = 0;
        }
    }, 100);
});


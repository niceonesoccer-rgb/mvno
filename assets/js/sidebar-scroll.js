// 사이드바 스크롤 위치 초기화
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.plans-sidebar');
    
    if (!sidebar) return;
    
    // 초기 로드 시 사이드바 스크롤을 맨 위로 초기화
    sidebar.scrollTop = 0;
    
    // 사이드바의 원래 위치 저장
    const sidebarOriginalTop = sidebar.offsetTop;
    let wasSticky = false;
    
    // 스크롤 이벤트로 사이드바가 sticky가 될 때 스크롤 위치 초기화
    window.addEventListener('scroll', function() {
        const rect = sidebar.getBoundingClientRect();
        const isSticky = rect.top <= 20 && rect.top >= 0;
        
        // 사이드바가 sticky 상태가 되었을 때 (이전에는 sticky가 아니었을 때)
        if (isSticky && !wasSticky) {
            // 사이드바 스크롤을 맨 위로 초기화
            sidebar.scrollTop = 0;
        }
        
        wasSticky = isSticky;
    }, { passive: true });
    
    // 페이지 로드 후에도 한 번 더 확인
    setTimeout(() => {
        sidebar.scrollTop = 0;
    }, 100);
});


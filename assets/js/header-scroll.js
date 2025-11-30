// 모바일에서 네비게이션을 하단에 고정하고, 스크롤 시 헤더 숨기기
(function() {
    'use strict';
    
    let lastScrollTop = 0;
    let scrollThreshold = 30; // 스크롤 임계값 (px) - 조금만 내려와도 숨기기
    let ticking = false;
    let navMoved = false;
    
    const header = document.getElementById('mainHeader');
    const navWrapper = header ? header.querySelector('.nav-wrapper') : null;
    const nav = header ? header.querySelector('.nav') : null;
    const body = document.body;
    
    if (!header || !navWrapper || !nav) {
        return; // 요소가 없으면 실행하지 않음
    }
    
    // 모바일/데스크톱 체크 및 nav 이동 처리
    function handleMobileNav() {
        const isMobile = window.innerWidth <= 767;
        const isMainPage = window.IS_MAIN_PAGE !== false; // 기본값 true (변수가 없으면 true로 간주)
        
        if (isMobile && !navMoved && isMainPage) {
            // 모바일 + 메인 페이지: nav를 body로 이동하여 하단에 고정
            nav.classList.add('mobile-bottom-nav');
            body.appendChild(nav);
            navMoved = true;
        } else if ((!isMobile || !isMainPage) && navMoved) {
            // 데스크톱이거나 서브페이지: nav를 원래 위치로 복원
            nav.classList.remove('mobile-bottom-nav');
            navWrapper.appendChild(nav);
            navMoved = false;
        }
    }
    
    function handleScroll() {
        if (ticking) return;
        
        ticking = true;
        requestAnimationFrame(function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const isMobile = window.innerWidth <= 767;
            
            if (isMobile) {
                // 스크롤 임계값을 넘었을 때만 동작
                if (scrollTop > scrollThreshold) {
                    // 스크롤 다운 시 헤더 전체 숨기기
                    if (scrollTop > lastScrollTop) {
                        header.classList.add('header-hidden');
                    } 
                    // 스크롤 업 시 헤더 보이기
                    else if (scrollTop < lastScrollTop) {
                        header.classList.remove('header-hidden');
                    }
                } else {
                    // 상단에 있을 때는 항상 보이기
                    header.classList.remove('header-hidden');
                }
            } else {
                // 데스크톱에서는 항상 보이기
                header.classList.remove('header-hidden');
            }
            
            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
            ticking = false;
        });
    }
    
    // 초기 설정 및 리사이즈 처리
    function init() {
        handleMobileNav();
        handleScroll();
    }
    
    // DOM 로드 완료 후 실행
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // 스크롤 이벤트 리스너
    window.addEventListener('scroll', handleScroll, { passive: true });
    
    // 리사이즈 이벤트 리스너 (모바일/데스크톱 전환 시)
    window.addEventListener('resize', function() {
        handleMobileNav();
        if (window.innerWidth > 767) {
            header.classList.remove('header-hidden');
        }
    }, { passive: true });
})();


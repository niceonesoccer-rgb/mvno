// 네비게이션 링크 클릭 시 좌우 스크롤 방지
(function() {
    'use strict';
    
    let savedScrollX = 0;
    let savedScrollY = 0;
    let isLocking = false;
    
    // 스크롤을 완전히 잠그는 함수
    function lockScrollPosition() {
        if (isLocking) return;
        
        isLocking = true;
        savedScrollX = window.scrollX || window.pageXOffset || 0;
        savedScrollY = window.scrollY || window.pageYOffset || 0;
        
        // 즉시 스크롤 고정
        window.scrollTo(savedScrollX, savedScrollY);
        
        // 여러 타이밍에 복원 (더블 버퍼링 방지)
        const restore = function() {
            const currentX = window.scrollX || window.pageXOffset || 0;
            const currentY = window.scrollY || window.pageYOffset || 0;
            
            if (currentX !== savedScrollX || currentY !== savedScrollY) {
                window.scrollTo(savedScrollX, savedScrollY);
            }
        };
        
        // 즉시 실행
        restore();
        
        // 여러 타이밍에 복원 시도
        setTimeout(restore, 0);
        setTimeout(restore, 1);
        setTimeout(restore, 5);
        setTimeout(restore, 10);
        setTimeout(restore, 20);
        setTimeout(restore, 50);
        setTimeout(restore, 100);
        
        // requestAnimationFrame으로도 복원
        requestAnimationFrame(restore);
        requestAnimationFrame(function() {
            requestAnimationFrame(restore);
            setTimeout(function() {
                isLocking = false;
            }, 150);
        });
    }
    
    function preventNavScroll() {
        const navLinks = document.querySelectorAll('.nav-link');
        const navWrapper = document.querySelector('.nav-wrapper');
        const nav = document.querySelector('.nav');
        
        // 모든 네비게이션 링크에 이벤트 리스너 추가
        navLinks.forEach(function(link) {
            // 마우스 다운 시 스크롤 위치 저장
            link.addEventListener('mousedown', function(e) {
                savedScrollX = window.scrollX || window.pageXOffset || 0;
                savedScrollY = window.scrollY || window.pageYOffset || 0;
                lockScrollPosition();
            }, { passive: false });
            
            // 클릭 시 스크롤 고정
            link.addEventListener('click', function(e) {
                savedScrollX = window.scrollX || window.pageXOffset || 0;
                savedScrollY = window.scrollY || window.pageYOffset || 0;
                
                // 즉시 스크롤 고정
                lockScrollPosition();
                
                // 링크 이동 전 스크롤 고정
                const href = this.getAttribute('href');
                if (href && href !== '#' && !href.startsWith('javascript:')) {
                    // 링크 이동 후에도 스크롤 고정
                    setTimeout(lockScrollPosition, 0);
                    setTimeout(lockScrollPosition, 10);
                    setTimeout(lockScrollPosition, 50);
                    setTimeout(lockScrollPosition, 100);
                    setTimeout(lockScrollPosition, 200);
                }
            }, { passive: false });
            
            // 포커스 시 스크롤 방지
            link.addEventListener('focus', function(e) {
                savedScrollX = window.scrollX || window.pageXOffset || 0;
                savedScrollY = window.scrollY || window.pageYOffset || 0;
                lockScrollPosition();
            }, { passive: false });
            
            // 포커스인 시 스크롤 방지
            link.addEventListener('focusin', function(e) {
                savedScrollX = window.scrollX || window.pageXOffset || 0;
                savedScrollY = window.scrollY || window.pageYOffset || 0;
                lockScrollPosition();
            }, { passive: false });
        });
        
        // 스크롤 이벤트 감시 - 네비게이션 영역에서 발생하는 스크롤 차단
        let scrollTimer;
        window.addEventListener('scroll', function() {
            // 스크롤이 발생하면 즉시 복원
            if (savedScrollX !== undefined && savedScrollY !== undefined) {
                clearTimeout(scrollTimer);
                scrollTimer = setTimeout(function() {
                    const currentX = window.scrollX || window.pageXOffset || 0;
                    const currentY = window.scrollY || window.pageYOffset || 0;
                    
                    // 가로 스크롤이 변경되었으면 복원
                    if (currentX !== savedScrollX) {
                        window.scrollTo(savedScrollX, currentY);
                    }
                }, 0);
            }
        }, { passive: true });
        
        // 네비게이션 영역 자체의 스크롤도 방지
        if (navWrapper) {
            navWrapper.addEventListener('scroll', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.scrollLeft = 0;
                this.scrollTop = 0;
            }, { passive: false });
        }
        
        if (nav) {
            nav.addEventListener('scroll', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.scrollLeft = 0;
                this.scrollTop = 0;
            }, { passive: false });
        }
    }
    
    // DOM 로드 완료 후 실행
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', preventNavScroll);
    } else {
        preventNavScroll();
    }
    
    // 동적으로 추가된 링크도 처리
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                preventNavScroll();
            }
        });
    });
    
    // nav 요소 감시
    const nav = document.querySelector('.nav');
    if (nav) {
        observer.observe(nav, { childList: true, subtree: true });
    }
    
    // 초기 스크롤 위치 저장
    savedScrollX = window.scrollX || window.pageXOffset || 0;
    savedScrollY = window.scrollY || window.pageYOffset || 0;
})();


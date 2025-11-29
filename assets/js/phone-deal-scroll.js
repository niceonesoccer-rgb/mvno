// 휴대폰 특가 섹션 자동 스크롤 애니메이션 및 화살표 네비게이션
(function() {
    'use strict';
    
    function initPhoneDealScroll() {
        const swiper = document.getElementById('phoneDealSwiper');
        const prevBtn = document.querySelector('.swiper-nav-btn.prev');
        const nextBtn = document.querySelector('.swiper-nav-btn.next');
        
        if (!swiper) return;
        
        // 처음 시작 시 왼쪽 끝으로 스크롤
        swiper.scrollLeft = 0;
        
        // 화살표 버튼 상태 업데이트 함수
        function updateButtonStates() {
            if (!prevBtn || !nextBtn) return;
            
            const maxScroll = swiper.scrollWidth - swiper.clientWidth;
            const currentScroll = swiper.scrollLeft;
            
            // 왼쪽 끝에 있으면 왼쪽 화살표 비활성화
            prevBtn.disabled = currentScroll <= 0;
            
            // 오른쪽 끝에 있으면 오른쪽 화살표 비활성화
            nextBtn.disabled = currentScroll >= maxScroll - 1; // 1px 오차 허용
        }
        
        // 초기 상태 업데이트
        updateButtonStates();
        
        // 화살표를 스와이퍼의 중앙 높이에 위치시키기
        function positionArrows() {
            if (!prevBtn || !nextBtn || !swiper.parentElement) return;
            
            // 스와이퍼의 첫 번째 카드를 기준으로 중앙 위치 계산
            const firstCard = swiper.querySelector('.phone-deal-card');
            if (firstCard) {
                // 카드의 중앙 높이 계산
                const cardHeight = firstCard.offsetHeight;
                const wrapperHeight = swiper.parentElement.offsetHeight || swiper.offsetHeight;
                const centerPosition = Math.max(cardHeight / 2, wrapperHeight / 2);
                
                // 화살표를 중앙에 위치
                prevBtn.style.top = centerPosition + 'px';
                nextBtn.style.top = centerPosition + 'px';
            }
        }
        
        // 초기 위치 설정 (약간의 지연 후 실행하여 카드가 로드된 후 계산)
        setTimeout(positionArrows, 100);
        
        // 리사이즈 시 위치 재계산
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(positionArrows, 100);
        });
        
        // 스크롤 이벤트 리스너 추가
        swiper.addEventListener('scroll', updateButtonStates);
        
        // 스크롤 애니메이션 함수
        function smoothScrollTo(target, duration) {
            const start = swiper.scrollLeft;
            const distance = target - start;
            const startTime = performance.now();
            
            function animate(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                // easeOutCubic 이징 함수
                const easeOutCubic = 1 - Math.pow(1 - progress, 3);
                
                swiper.scrollLeft = start + (distance * easeOutCubic);
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    // 애니메이션 완료 후 버튼 상태 업데이트
                    updateButtonStates();
                }
            }
            
            requestAnimationFrame(animate);
        }
        
        // 왼쪽 화살표 클릭 이벤트
        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                const scrollAmount = 300; // 한 번에 스크롤할 거리
                const target = Math.max(0, swiper.scrollLeft - scrollAmount);
                smoothScrollTo(target, 300);
            });
        }
        
        // 오른쪽 화살표 클릭 이벤트
        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                const maxScroll = swiper.scrollWidth - swiper.clientWidth;
                const scrollAmount = 300; // 한 번에 스크롤할 거리
                const target = Math.min(maxScroll, swiper.scrollLeft + scrollAmount);
                smoothScrollTo(target, 300);
            });
        }
        
        // 마우스 오버 시 화살표 표시/숨김 처리
        const swiperWrapper = swiper.parentElement;
        if (swiperWrapper && prevBtn && nextBtn) {
            // 초기에는 화살표 숨김
            prevBtn.style.opacity = '0';
            prevBtn.style.pointerEvents = 'none';
            nextBtn.style.opacity = '0';
            nextBtn.style.pointerEvents = 'none';
            
            // 마우스 오버 시 화살표 표시
            swiperWrapper.addEventListener('mouseenter', function() {
                prevBtn.style.opacity = '1';
                prevBtn.style.pointerEvents = 'auto';
                nextBtn.style.opacity = '1';
                nextBtn.style.pointerEvents = 'auto';
            });
            
            // 마우스 아웃 시 화살표 숨김
            swiperWrapper.addEventListener('mouseleave', function() {
                prevBtn.style.opacity = '0';
                prevBtn.style.pointerEvents = 'none';
                nextBtn.style.opacity = '0';
                nextBtn.style.pointerEvents = 'none';
            });
        }
    }
    
    // DOM 로드 완료 후 실행
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPhoneDealScroll);
    } else {
        initPhoneDealScroll();
    }
})();


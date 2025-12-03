/**
 * 찜하기 하트 버튼 모듈
 * 여러 페이지에서 재사용 가능한 하트 토글 기능
 */

(function() {
    'use strict';
    
    // 핑크 하트 path (채워진 하트)
    const FILLED_HEART_PATH = 'M21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C17.3144 15.2837 15.1837 17.5556 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803Z';
    
    // 회색 테두리 하트 path (빈 하트)
    const OUTLINE_HEART_PATH = 'M17.9623 11.5427C18.5031 11.0065 18.8 10.2886 18.8 9.54803C18.8 8.80746 18.5032 8.08961 17.9623 7.5534C17.4166 7.01196 16.6657 6.7 15.8748 6.7C15.0838 6.7 14.3335 7.01145 13.7879 7.55284L13.549 7.7898C12.6914 8.64035 11.3084 8.64041 10.4508 7.78993L10.2121 7.55325C9.06574 6.41633 7.18394 6.41618 6.03759 7.55311C4.92082 8.66071 4.92079 10.4353 6.03758 11.543L12.0178 17.474C13.2794 16.2826 14.4839 15.0586 15.7184 13.804C16.4497 13.0609 17.1918 12.3068 17.9623 11.5427ZM11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C18.7821 13.8281 18.0602 14.5615 17.3378 15.2955C15.8837 16.7728 14.4273 18.2525 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166Z';
    
    /**
     * 하트를 회색 테두리 하트로 설정
     */
    function setOutlineHeart(iconPath) {
        iconPath.setAttribute('d', OUTLINE_HEART_PATH);
        iconPath.setAttribute('fill', '#868E96');
        iconPath.setAttribute('fill-rule', 'evenodd');
        iconPath.setAttribute('clip-rule', 'evenodd');
        iconPath.removeAttribute('stroke');
        iconPath.removeAttribute('stroke-width');
        iconPath.removeAttribute('class');
    }
    
    /**
     * 하트를 핑크 하트로 설정
     */
    function setFilledHeart(iconPath) {
        iconPath.setAttribute('d', FILLED_HEART_PATH);
        iconPath.setAttribute('fill', '#FA5252');
        iconPath.removeAttribute('fill-rule');
        iconPath.removeAttribute('clip-rule');
        iconPath.removeAttribute('stroke');
        iconPath.removeAttribute('stroke-width');
    }
    
    /**
     * 하트 상태 토글
     */
    function toggleHeart(button) {
        const svg = button.querySelector('svg');
        const iconPath = svg ? svg.querySelector('path') : null;
        
        if (!iconPath) return;
        
        const currentFill = iconPath.getAttribute('fill');
        const currentPath = iconPath.getAttribute('d');
        // 핑크 하트인지 확인 (fill이 #FA5252이거나 path가 FILLED_HEART_PATH와 같은 경우)
        const isFilled = currentFill === '#FA5252' || currentPath === FILLED_HEART_PATH;
        
        if (isFilled) {
            // 핑크 하트 → 회색 하트 (원래 상태로 복원)
            setOutlineHeart(iconPath);
            button.classList.add('favorite-unselected');
        } else {
            // 회색 하트 → 핑크 하트
            setFilledHeart(iconPath);
            button.classList.remove('favorite-unselected');
        }
    }
    
    /**
     * 모든 하트 버튼을 회색 테두리 하트로 초기화
     */
    function initializeHearts() {
        const favoriteButtons = document.querySelectorAll('.plan-favorite-btn-inline');
        
        favoriteButtons.forEach(function(button) {
            const svg = button.querySelector('svg');
            const iconPath = svg ? svg.querySelector('path') : null;
            
            if (iconPath) {
                const currentFill = iconPath.getAttribute('fill');
                // 핑크 하트인 경우에만 회색 테두리로 변경
                if (currentFill === '#FA5252') {
                    setOutlineHeart(iconPath);
                }
            }
        });
    }
    
    /**
     * 하트 버튼 이벤트 리스너 등록
     */
    function initFavoriteButtons() {
        const favoriteButtons = document.querySelectorAll('.plan-favorite-btn-inline');
        
        favoriteButtons.forEach(function(button) {
            // 캡처 단계에서 실행하여 다른 이벤트보다 먼저 처리
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation(); // 이벤트 전파 완전 차단
                e.stopImmediatePropagation(); // 같은 요소의 다른 리스너도 차단
                toggleHeart(button);
                return false;
            }, true); // 캡처 단계에서 처리
        });
    }
    
    /**
     * 모듈 초기화
     */
    function init() {
        // DOM이 로드된 후 실행
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initializeHearts();
                initFavoriteButtons();
            });
        } else {
            initializeHearts();
            initFavoriteButtons();
        }
    }
    
    // 모듈 초기화 실행
    init();
    
    // 전역으로 export (필요한 경우)
    window.FavoriteHeart = {
        toggle: toggleHeart,
        init: init
    };
})();


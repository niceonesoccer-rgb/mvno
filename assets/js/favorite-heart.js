/**
 * 찜하기 기능 모듈
 * 하트 버튼 클릭 시 찜 추가/삭제 기능
 */

(function() {
    'use strict';

    /**
     * 찜 상태 토글 (UI만 업데이트)
     * 실제 서버 요청은 추후 구현
     */
    function toggleFavorite(button) {
        const itemId = button.getAttribute('data-item-id');
        const itemType = button.getAttribute('data-item-type');
        const isFavorited = button.classList.contains('favorited');
        
        if (!itemId || !itemType) {
            console.warn('찜하기 버튼에 필요한 데이터 속성이 없습니다.');
            return;
        }

        // 로그인 체크
        const isLoggedIn = (typeof window !== 'undefined' && window.isLoggedIn === true);
        if (!isLoggedIn) {
            // 로그인하지 않은 경우 로그인 모달 열기
            if (typeof openLoginModal === 'function') {
                openLoginModal(false);
            } else {
                // 모달이 아직 로드되지 않은 경우
                setTimeout(() => {
                    if (typeof openLoginModal === 'function') {
                        openLoginModal(false);
                    } else {
                        // 로그인 페이지로 이동
                        const loginPath = (window.BASE_PATH || '') + '/auth/login.php';
                        window.location.href = loginPath;
                    }
                }, 100);
            }
            return; // UI 상태 변경하지 않음
        }

        // UI 상태 토글
        if (isFavorited) {
            // 찜 해제
            button.classList.remove('favorited');
            button.setAttribute('aria-label', '찜하기');
            updateFavoriteIcon(button, false);
            
            // 서버에 찜 삭제 요청
            trackFavoriteToServer(itemType, itemId, 'remove');
        } else {
            // 찜 추가
            button.classList.add('favorited');
            button.setAttribute('aria-label', '찜 해제');
            updateFavoriteIcon(button, true);
            
            // 서버에 찜 추가 요청
            trackFavoriteToServer(itemType, itemId, 'add');
        }
    }

    /**
     * 서버에 찜 추적 요청
     */
    function trackFavoriteToServer(itemType, itemId, action) {
        // 인터넷 타입은 찜 불가
        if (itemType === 'internet') {
            console.warn('인터넷 상품은 찜할 수 없습니다.');
            return;
        }
        
        console.log('찜 요청:', { itemType, itemId, action });
        
        const sellerId = document.querySelector('[data-seller-id]')?.getAttribute('data-seller-id') || null;
        
        // API 경로 설정
        // window.API_PATH가 설정되어 있으면 사용, 없으면 window.BASE_PATH + '/api' 사용
        let baseApiPath = '';
        if (typeof window !== 'undefined' && window.API_PATH) {
            baseApiPath = window.API_PATH;
        } else if (typeof window !== 'undefined' && window.BASE_PATH) {
            baseApiPath = window.BASE_PATH + '/api';
        } else {
            // 둘 다 없으면 빈 문자열 (루트 설치)
            baseApiPath = '/api';
        }
        
        const apiPath = baseApiPath + '/analytics/track-favorite.php';
        
        console.log('API 경로:', apiPath, 'BASE_PATH:', window.BASE_PATH, 'API_PATH:', window.API_PATH, 'baseApiPath:', baseApiPath);
        
        fetch(apiPath, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                product_type: itemType,
                product_id: itemId,
                action: action,
                seller_id: sellerId || ''
            })
        })
        .then(response => {
            // 401 에러 (로그인 필요) 체크
            if (response.status === 401) {
                // UI 되돌리기
                const button = document.querySelector(`[data-item-id="${itemId}"][data-item-type="${itemType}"]`);
                if (button) {
                    if (action === 'add') {
                        button.classList.remove('favorited');
                        updateFavoriteIcon(button, false);
                        button.setAttribute('aria-label', '찜하기');
                    } else {
                        button.classList.add('favorited');
                        updateFavoriteIcon(button, true);
                        button.setAttribute('aria-label', '찜 해제');
                    }
                }
                
                // 로그인 모달 열기
                if (typeof openLoginModal === 'function') {
                    openLoginModal(false);
                } else {
                    setTimeout(() => {
                        if (typeof openLoginModal === 'function') {
                            openLoginModal(false);
                        } else {
                            const loginPath = (window.BASE_PATH || '') + '/auth/login.php';
                            window.location.href = loginPath;
                        }
                    }, 100);
                }
                
                // 에러로 처리하여 catch 블록으로 이동하지 않음
                return Promise.reject(new Error('로그인이 필요합니다.'));
            }
            
            // 응답 상태 확인
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success) {
                console.log('찜 처리 성공:', data);
                // 필요시 favorite_count 업데이트
                if (data.favorite_count !== undefined) {
                    const favoriteCountElements = document.querySelectorAll(`[data-product-id="${itemId}"] .favorite-count`);
                    favoriteCountElements.forEach(el => {
                        el.textContent = data.favorite_count;
                    });
                }
                // 성공 메시지 표시 (선택사항)
                if (action === 'add') {
                    console.log(`상품 ID ${itemId} (${itemType})이(가) 찜 목록에 추가되었습니다.`);
                } else {
                    console.log(`상품 ID ${itemId} (${itemType})이(가) 찜 목록에서 제거되었습니다.`);
                }
            } else {
                console.error('찜 처리 실패:', data.message || '알 수 없는 오류', data);
                if (data.error_details) {
                    console.error('에러 상세 정보:', data.error_details);
                }
                // 실패 시 UI 되돌리기
                const button = document.querySelector(`[data-item-id="${itemId}"][data-item-type="${itemType}"]`);
                if (button) {
                    if (action === 'add') {
                        button.classList.remove('favorited');
                        updateFavoriteIcon(button, false);
                        button.setAttribute('aria-label', '찜하기');
                    } else {
                        button.classList.add('favorited');
                        updateFavoriteIcon(button, true);
                        button.setAttribute('aria-label', '찜 해제');
                    }
                }
            }
        })
        .catch(error => {
            console.error('찜 추적 오류:', error);
            // 네트워크 오류 시에도 UI 되돌리기
            const button = document.querySelector(`[data-item-id="${itemId}"][data-item-type="${itemType}"]`);
            if (button) {
                if (action === 'add') {
                    button.classList.remove('favorited');
                    updateFavoriteIcon(button, false);
                    button.setAttribute('aria-label', '찜하기');
                } else {
                    button.classList.add('favorited');
                    updateFavoriteIcon(button, true);
                    button.setAttribute('aria-label', '찜 해제');
                }
            }
        });
    }

    /**
     * 찜 아이콘 업데이트
     */
    function updateFavoriteIcon(button, isFavorited) {
        const iconPath = button.querySelector('.favorite-icon-path');
        if (!iconPath) {
            console.warn('찜 아이콘 path를 찾을 수 없습니다.');
            return;
        }

        // 인라인 fill 속성 완전 제거 (CSS로만 제어)
        iconPath.removeAttribute('fill');
        iconPath.removeAttribute('stroke');
        iconPath.style.removeProperty('fill');
        iconPath.style.removeProperty('stroke');
        
        if (isFavorited) {
            // 찜된 상태: 빨간색으로 채워진 하트 (채워진 형태)
            const filledHeartPath = 'M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z';
            iconPath.setAttribute('d', filledHeartPath);
        } else {
            // 찜 안 된 상태: 회색 테두리 하트 (빈 형태)
            const outlineHeartPath = 'M17.9623 11.5427C18.5031 11.0065 18.8 10.2886 18.8 9.54803C18.8 8.80746 18.5032 8.08961 17.9623 7.5534C17.4166 7.01196 16.6657 6.7 15.8748 6.7C15.0838 6.7 14.3335 7.01145 13.7879 7.55284L13.549 7.7898C12.6914 8.64035 11.3084 8.64041 10.4508 7.78993L10.2121 7.55325C9.06574 6.41633 7.18394 6.41618 6.03759 7.55311C4.92082 8.66071 4.92079 10.4353 6.03758 11.543L12.0178 17.474C13.2794 16.2826 14.4839 15.0586 15.7184 13.804C16.4497 13.0609 17.1918 12.3068 17.9623 11.5427ZM11.0539 19.6166L4.48838 13.105C2.50387 11.1368 2.50387 7.95928 4.48838 5.99107C6.49239 4.00353 9.75714 4.00353 11.7611 5.99107L11.9998 6.22775L12.2383 5.99121C13.1992 5.03776 14.5078 4.5 15.8748 4.5C17.2418 4.5 18.5503 5.03771 19.5112 5.99107C20.4657 6.93733 21 8.21636 21 9.54803C21 10.8797 20.4656 12.1588 19.5112 13.105C18.7821 13.8281 18.0602 14.5615 17.3378 15.2955C15.8837 16.7728 14.4273 18.2525 12.9048 19.6553C12.3824 20.1296 11.5538 20.1123 11.0539 19.6166Z';
            iconPath.setAttribute('d', outlineHeartPath);
        }
        
        // CSS가 적용되도록 강제 (리플로우 트리거)
        void iconPath.offsetHeight;
    }

    /**
     * 페이지 로드 시 찜 상태 초기화
     * PHP에서 설정한 찜 상태에 맞게 아이콘 업데이트
     */
    function initializeFavoriteStates() {
        const favoriteButtons = document.querySelectorAll('.plan-favorite-btn-inline[data-item-id]');
        favoriteButtons.forEach(button => {
            const isFavorited = button.classList.contains('favorited');
            const iconPath = button.querySelector('.favorite-icon-path');
            
            if (iconPath) {
                // 인라인 fill 속성 제거 (CSS로만 제어)
                iconPath.removeAttribute('fill');
                iconPath.removeAttribute('stroke');
                iconPath.style.removeProperty('fill');
                iconPath.style.removeProperty('stroke');
            }
            
            // 아이콘을 현재 찜 상태에 맞게 업데이트
            updateFavoriteIcon(button, isFavorited);
        });
    }

    /**
     * 찜 버튼 이벤트 리스너 등록
     */
    function initFavoriteButtons() {
        // 1. 판매자/관리자 페이지에서는 찜 기능 비활성화
        const currentPath = window.location.pathname;
        if (currentPath.includes('/seller/') || 
            currentPath.includes('/admin/')) {
            return; // 판매자/관리자 페이지에서는 찜 기능 사용 안 함
        }
        
        // 2. 찜 버튼이 실제로 존재하는지 확인
        const favoriteButtons = document.querySelectorAll('.plan-favorite-btn-inline[data-item-id]');
        if (favoriteButtons.length === 0) {
            return; // 찜 버튼이 없으면 실행하지 않음
        }
        
        // 3. 페이지 로드 시 찜 상태 초기화 (PHP에서 설정한 상태 반영)
        initializeFavoriteStates();
        
        // 4. 부모 링크의 클릭 이벤트를 차단하는 함수
        function preventParentLinkClick(e) {
            // 하트 버튼이나 그 자식 요소를 클릭한 경우에만 링크 이동 차단
            const clickedElement = e.target;
            const favoriteButton = clickedElement.closest('.plan-favorite-btn-inline');
            
            if (favoriteButton) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                return false;
            }
        }
        
        // 5. 모든 plan-card-link에 이벤트 리스너 추가 (capture phase에서 먼저 실행)
        document.querySelectorAll('a.plan-card-link').forEach(function(link) {
            // capture phase에서 실행하여 다른 리스너보다 먼저 실행
            link.addEventListener('click', preventParentLinkClick, true);
            link.addEventListener('mousedown', preventParentLinkClick, true);
            link.addEventListener('mouseup', preventParentLinkClick, true);
            // 모바일 터치 이벤트 처리
            link.addEventListener('touchstart', preventParentLinkClick, true);
            link.addEventListener('touchend', preventParentLinkClick, true);
        });
        
        // 6. 각 찜 버튼에 직접 이벤트 바인딩
        favoriteButtons.forEach(function(favoriteButton) {
            const itemType = favoriteButton.getAttribute('data-item-type');
            if (itemType === 'internet') {
                return; // 인터넷 상품은 찜 불가
            }
            
            // 찜 버튼 클릭 핸들러
            function handleFavoriteClick(e) {
                // 이벤트 타입 체크 (touchend 또는 click만 처리하여 중복 방지)
                // touchstart는 제외하고 touchend만 처리 (모바일 터치 지원)
                if (e.type === 'touchstart') {
                    // touchstart에서는 이벤트 전파만 차단 (touchend에서 처리)
                    e.stopPropagation();
                    return false;
                }
                
                // 모든 이벤트 전파 차단
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                // 찜 토글 실행 (touchend 또는 click에서만)
                toggleFavorite(favoriteButton);
                
                return false;
            }
            
            // 모든 이벤트 타입에 대해 핸들러 등록 (capture phase)
            // touchstart는 이벤트 전파만 차단, touchend에서 실제 처리
            ['click', 'mousedown', 'mouseup', 'pointerdown', 'pointerup', 'touchstart', 'touchend'].forEach(function(eventType) {
                favoriteButton.addEventListener(eventType, handleFavoriteClick, true);
            });
        });
        
        // 7. 이벤트 위임 방식도 유지 (동적으로 추가된 버튼용)
        const handleFavoriteButtonEvent = function(e) {
            const favoriteButton = e.target.closest('.plan-favorite-btn-inline');
            
            if (favoriteButton && 
                favoriteButton.hasAttribute('data-item-id') &&
                favoriteButton.classList.contains('plan-favorite-btn-inline')) {
                
                const itemType = favoriteButton.getAttribute('data-item-type');
                if (itemType === 'internet') {
                    return;
                }
                
                // 링크 이동 완전 차단
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                // click 또는 touchend 이벤트일 때 찜 토글 (모바일 터치 지원)
                if (e.type === 'click' || e.type === 'touchend') {
                    toggleFavorite(favoriteButton);
                }
                
                return false;
            }
        };
        
        // 모든 마우스/포인터/터치 이벤트에 대해 처리 (동적 버튼용, capture phase)
        ['mousedown', 'mouseup', 'pointerdown', 'pointerup', 'touchstart', 'touchend', 'click'].forEach(function(eventType) {
            document.addEventListener(eventType, handleFavoriteButtonEvent, true);
        });
    }

    // DOM이 로드되면 초기화
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFavoriteButtons);
    } else {
        initFavoriteButtons();
    }
    
    // 전역으로 노출 (AJAX로 추가된 카드의 찜 상태 초기화용)
    window.initFavoriteHearts = initFavoriteButtons;
    window.initFavoriteButtons = initFavoriteButtons;
    window.initializeFavoriteStates = initializeFavoriteStates;
})();

/**
 * 공유하기 기능
 * Web Share API를 지원하는 경우 사용하고, 그렇지 않으면 클립보드에 복사
 */

(function() {
    'use strict';

    /**
     * 클립보드에 텍스트 복사
     */
    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text).then(() => {
                return true;
            }).catch(() => {
                // 클립보드 API 실패 시 fallback 사용
                return fallbackCopyToClipboard(text);
            });
        } else {
            return Promise.resolve(fallbackCopyToClipboard(text));
        }
    }

    /**
     * 클립보드 복사 fallback (구형 브라우저용)
     */
    function fallbackCopyToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            const successful = document.execCommand('copy');
            document.body.removeChild(textArea);
            return successful;
        } catch (err) {
            document.body.removeChild(textArea);
            return false;
        }
    }

    /**
     * 공유하기 실행 (버튼 요소 포함)
     */
    function shareUrlWithButton(urlToShare, title, buttonElement) {
        // 항상 클립보드에 복사하고 토스트 메시지만 표시 (새 창 열기 없음)
        copyToClipboardAndShowMessage(urlToShare, buttonElement);
    }

    /**
     * 클립보드에 복사하고 메시지 표시
     */
    function copyToClipboardAndShowMessage(url, buttonElement) {
        copyToClipboard(url).then((success) => {
            if (success) {
                showToastMessage('공유 링크를 복사했어요', buttonElement);
            } else {
                showToastMessage('링크 복사에 실패했습니다. 브라우저를 확인해주세요.', buttonElement);
            }
        });
    }

    /**
     * 토스트 메시지 표시
     */
    function showToastMessage(message, buttonElement) {
        // 기존 토스트가 있으면 제거
        const existingToast = document.querySelector('.share-toast');
        if (existingToast) {
            existingToast.remove();
        }

        // 토스트 메시지 생성
        const toast = document.createElement('div');
        toast.className = 'share-toast';
        
        // 체크 아이콘 추가
        const iconWrapper = document.createElement('div');
        iconWrapper.className = 'share-toast-icon';
        iconWrapper.innerHTML = `
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 16.17L4.83 12L3.41 13.41L9 19L21 7L19.59 5.59L9 16.17Z" fill="white"/>
            </svg>
        `;
        
        // 메시지 텍스트
        const messageText = document.createElement('span');
        messageText.className = 'share-toast-text';
        messageText.textContent = message;
        
        toast.appendChild(iconWrapper);
        toast.appendChild(messageText);
        document.body.appendChild(toast);

        // 버튼 위치 기준으로 토스트 위치 설정 (공유하기 버튼 왼쪽에 표시)
        if (buttonElement) {
            // 공유하기 버튼 위치 가져오기
            const buttonRect = buttonElement.getBoundingClientRect();
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // 공유하기 버튼의 왼쪽에 표시 (버튼 왼쪽 끝에서 약간 떨어진 위치)
            const leftPosition = (buttonRect.left - 10) + 'px'; // 버튼 왼쪽에서 10px 떨어진 위치
            // 공유하기 버튼의 중간 높이 위치
            const topPosition = (buttonRect.top + scrollTop + buttonRect.height / 2) + 'px';
            
            toast.style.left = leftPosition;
            toast.style.top = topPosition;
            toast.style.transform = 'translateX(-100%) translateY(-50%) scale(0.9)'; // 왼쪽 정렬, 초기 스케일
            toast.classList.add('toast-left'); // 왼쪽 정렬 클래스 추가
        } else {
            // 버튼이 없는 경우 화면 하단 중앙에 표시
            toast.style.bottom = '24px';
            toast.style.left = '50%';
            toast.style.transform = 'translateX(-50%) translateY(0) scale(0.9)';
        }

        // 애니메이션을 위해 약간의 지연 후 표시
        setTimeout(() => {
            toast.classList.add('show');
            // show 클래스 추가 시 transform 업데이트
            if (buttonElement) {
                toast.style.transform = 'translateX(-100%) translateY(-50%) scale(1)';
            } else {
                toast.style.transform = 'translateX(-50%) translateY(0) scale(1)';
            }
        }, 10);

        // 0.8초 후 제거
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 800);
    }

    /**
     * 공유 버튼 이벤트 리스너 등록
     */
    function initShareButtons() {
        // 이벤트 위임 방식으로 모든 공유 버튼 처리
        document.addEventListener('click', function(e) {
            // 클릭된 요소가 공유 버튼이거나 공유 버튼 내부 요소인지 확인
            // SVG나 path를 클릭해도 버튼을 찾을 수 있도록 closest 사용
            const shareButton = e.target.closest('[data-share-url]');
            
            if (shareButton && shareButton.hasAttribute('data-share-url')) {
                e.preventDefault();
                e.stopPropagation();
                
                const urlToShare = shareButton.getAttribute('data-share-url');
                
                if (urlToShare) {
                    // 위시리스트 공유 버튼인 경우
                    if (shareButton.id === 'wishlistShareBtn') {
                        const pageTitle = document.querySelector('h1')?.textContent || '위시리스트';
                        shareUrlWithButton(urlToShare, pageTitle, shareButton);
                    } else {
                        shareUrlWithButton(urlToShare, null, shareButton);
                    }
                }
            }
        }, true); // 캡처 단계에서 처리하여 다른 이벤트보다 먼저 실행
    }

    // DOM이 로드되면 초기화
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initShareButtons);
    } else {
        initShareButtons();
    }
})();


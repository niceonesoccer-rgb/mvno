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
        // 클립보드 API 사용 시도 (HTTPS 또는 localhost에서만 동작)
        const isSecureContext = window.isSecureContext || 
                               location.protocol === 'https:' || 
                               location.hostname === 'localhost' || 
                               location.hostname === '127.0.0.1';
        
        if (isSecureContext && navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text).then(() => {
                return true;
            }).catch((err) => {
                console.warn('클립보드 API 실패, fallback 사용:', err);
                // 클립보드 API 실패 시 fallback 사용
                return fallbackCopyToClipboard(text);
            });
        } else {
            // 클립보드 API 미지원 또는 비보안 컨텍스트 시 fallback 사용
            return fallbackCopyToClipboard(text);
        }
    }

    /**
     * 클립보드 복사 fallback (구형 브라우저용)
     */
    function fallbackCopyToClipboard(text) {
        return new Promise((resolve) => {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '0';
            textArea.style.top = '0';
            textArea.style.width = '2em';
            textArea.style.height = '2em';
            textArea.style.padding = '0';
            textArea.style.border = 'none';
            textArea.style.outline = 'none';
            textArea.style.boxShadow = 'none';
            textArea.style.background = 'transparent';
            textArea.style.opacity = '0';
            textArea.setAttribute('readonly', '');
            textArea.setAttribute('aria-hidden', 'true');
            document.body.appendChild(textArea);
            
            // 포커스를 textArea에 주고 선택
            textArea.focus();
            textArea.select();
            textArea.setSelectionRange(0, text.length);
            
            // iOS Safari에서 선택을 위해 범위 설정
            if (navigator.userAgent.match(/ipad|iphone/i)) {
                const range = document.createRange();
                range.selectNodeContents(textArea);
                const selection = window.getSelection();
                if (selection.rangeCount > 0) {
                    selection.removeAllRanges();
                }
                selection.addRange(range);
                textArea.setSelectionRange(0, text.length);
            }
            
            try {
                const successful = document.execCommand('copy');
                // 복사 후 즉시 제거하지 않고 약간 지연
                setTimeout(() => {
                    if (textArea.parentNode) {
                        textArea.parentNode.removeChild(textArea);
                    }
                }, 100);
                resolve(successful);
            } catch (err) {
                console.error('클립보드 복사 실패:', err);
                if (textArea.parentNode) {
                    textArea.parentNode.removeChild(textArea);
                }
                resolve(false);
            }
        });
    }

    /**
     * 공유하기 실행 (버튼 요소 포함)
     */
    function shareUrlWithButton(urlToShare, title, buttonElement) {
        // 항상 클립보드에 복사하고 토스트 메시지만 표시 (새 창 열기 없음)
        copyToClipboardAndShowMessage(urlToShare, buttonElement);
        
        // 공유 추적
        trackShareToServer(urlToShare, buttonElement);
    }
    
    /**
     * 서버에 공유 추적 요청
     */
    function trackShareToServer(url, buttonElement) {
        // 상품 정보 추출 (URL에서 또는 버튼의 data 속성에서)
        const productType = buttonElement?.getAttribute('data-product-type') || 
                           document.querySelector('[data-product-type]')?.getAttribute('data-product-type') || '';
        const productId = buttonElement?.getAttribute('data-product-id') || 
                         document.querySelector('[data-product-id]')?.getAttribute('data-product-id') || '';
        const sellerId = buttonElement?.getAttribute('data-seller-id') || 
                        document.querySelector('[data-seller-id]')?.getAttribute('data-seller-id') || null;
        
        if (!productType || !productId) {
            // URL에서 추출 시도
            const urlMatch = url.match(/\/(mvno|mno|internet)\/([^\/]+)/);
            if (urlMatch) {
                const extractedType = urlMatch[1] === 'mvno' ? 'mvno' : (urlMatch[1] === 'mno' ? 'mno' : 'internet');
                const extractedId = urlMatch[2];
                
                fetch('/MVNO/api/analytics/track-share.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        product_type: extractedType,
                        product_id: extractedId,
                        share_method: 'link',
                        seller_id: sellerId || ''
                    })
                }).catch(error => {
                    console.error('공유 추적 오류:', error);
                });
            }
            return;
        }
        
        fetch('/MVNO/api/analytics/track-share.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                product_type: productType,
                product_id: productId,
                share_method: 'link',
                seller_id: sellerId || ''
            })
        }).catch(error => {
            console.error('공유 추적 오류:', error);
        });
    }

    /**
     * 클립보드에 복사하고 메시지 표시
     */
    function copyToClipboardAndShowMessage(url, buttonElement) {
        if (!url || url.trim() === '') {
            console.error('공유할 URL이 없습니다.');
            if (typeof showAlert === 'function') {
                showAlert('공유할 링크가 없습니다.', '오류');
            } else {
                alert('공유할 링크가 없습니다.');
            }
            return;
        }
        
        // 상대 경로를 절대 경로로 변환
        let absoluteUrl = url;
        if (url.startsWith('/')) {
            absoluteUrl = window.location.origin + url;
        } else if (!url.startsWith('http://') && !url.startsWith('https://')) {
            absoluteUrl = window.location.origin + '/' + url;
        }
        
        copyToClipboard(absoluteUrl).then((success) => {
            if (success) {
                // 모달로 복사 완료 알림
                if (typeof showAlert === 'function') {
                    showAlert('복사되었습니다.', '링크 복사 완료');
                } else {
                    alert('복사되었습니다.');
                }
            } else {
                // 복사 실패 시
                if (typeof showAlert === 'function') {
                    showAlert('링크 복사에 실패했습니다. 브라우저를 확인해주세요.', '오류');
                } else {
                    alert('링크 복사에 실패했습니다. 브라우저를 확인해주세요.');
                }
            }
        }).catch((err) => {
            console.error('클립보드 복사 오류:', err);
            if (typeof showAlert === 'function') {
                showAlert('링크 복사에 실패했습니다. 브라우저를 확인해주세요.', '오류');
            } else {
                alert('링크 복사에 실패했습니다. 브라우저를 확인해주세요.');
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
        // 캡처 단계에서 처리하여 다른 이벤트보다 먼저 실행
        
        document.addEventListener('mousedown', function(e) {
            const shareButton = e.target.closest('[data-share-url]');
            if (shareButton && shareButton.hasAttribute('data-share-url')) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
            }
        }, true);

        document.addEventListener('click', function(e) {
            // 클릭된 요소가 공유 버튼이거나 공유 버튼 내부 요소인지 확인
            // SVG나 path를 클릭해도 버튼을 찾을 수 있도록 closest 사용
            const shareButton = e.target.closest('[data-share-url]');
            
            if (shareButton && shareButton.hasAttribute('data-share-url')) {
                // 이벤트 전파 완전 차단
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                // 부모 링크 찾기 및 클릭 방지
                const parentLink = shareButton.closest('a.plan-card-link');
                if (parentLink) {
                    // 링크의 기본 동작 방지 (캡처 단계에서)
                    const preventLinkClick = function(linkEvent) {
                        linkEvent.preventDefault();
                        linkEvent.stopPropagation();
                        linkEvent.stopImmediatePropagation();
                    };
                    parentLink.addEventListener('click', preventLinkClick, { capture: true, once: true });
                    parentLink.addEventListener('mousedown', preventLinkClick, { capture: true, once: true });
                }
                
                const urlToShare = shareButton.getAttribute('data-share-url');
                
                if (urlToShare && urlToShare.trim() !== '') {
                    console.log('공유 버튼 클릭, URL:', urlToShare);
                    // 위시리스트 공유 버튼인 경우
                    if (shareButton.id === 'wishlistShareBtn') {
                        const pageTitle = document.querySelector('h1')?.textContent || '위시리스트';
                        shareUrlWithButton(urlToShare, pageTitle, shareButton);
                    } else {
                        shareUrlWithButton(urlToShare, null, shareButton);
                    }
                } else {
                    console.warn('공유 버튼에 URL이 없습니다:', shareButton);
                    if (typeof showAlert === 'function') {
                        showAlert('공유할 링크가 없습니다.', '오류');
                    } else {
                        alert('공유할 링크가 없습니다.');
                    }
                }
                
                return false;
            }
        }, true);
    }

    // DOM이 로드되면 초기화
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initShareButtons);
    } else {
        initShareButtons();
    }
})();

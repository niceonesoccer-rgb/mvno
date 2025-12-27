/**
 * 더보기 기능 - 상품 목록 추가 로드
 * 더보기 버튼 클릭 또는 무한 스크롤로 추가 상품 로드
 */

(function() {
    'use strict';

    // 설정
    const LOAD_MORE_MODE = 'button'; // 'button' 또는 'infinite'
    const ITEMS_PER_PAGE = 20;

    // 더보기 버튼 초기화
    function initLoadMoreButton() {
        const loadMoreBtn = document.getElementById('load-more-internet-btn') || 
                           document.getElementById('load-more-mno-sim-btn') ||
                           document.getElementById('load-more-mvno-btn') ||
                           document.getElementById('load-more-mno-btn');
        
        if (!loadMoreBtn) return;

        const productType = loadMoreBtn.getAttribute('data-type');
        let currentPage = parseInt(loadMoreBtn.getAttribute('data-page')) || 2;
        let isLoading = false;

        loadMoreBtn.addEventListener('click', function() {
            if (isLoading) return;
            
            isLoading = true;
            loadMoreBtn.disabled = true;
            loadMoreBtn.textContent = '로딩 중...';

            loadMoreProducts(productType, currentPage, function(success, data) {
                isLoading = false;
                loadMoreBtn.disabled = false;

                if (success && data) {
                    // 상품 추가 (HTML 또는 JSON)
                    appendProducts(productType, data);
                    
                    // 페이지 번호 증가
                    currentPage++;
                    loadMoreBtn.setAttribute('data-page', currentPage);

                    // 남은 개수 업데이트
                    const remainingCount = data.pagination.remaining;
                    const remainingSpan = document.getElementById('remaining-count');
                    if (remainingSpan) {
                        remainingSpan.textContent = remainingCount;
                    }

                    // 더 이상 로드할 상품이 없으면 버튼 숨김
                    if (!data.pagination.hasMore) {
                        loadMoreBtn.style.display = 'none';
                    } else {
                        loadMoreBtn.textContent = `더보기 (${remainingCount}개 남음)`;
                    }
                } else {
                    loadMoreBtn.textContent = '더 이상 불러올 상품이 없습니다.';
                    setTimeout(() => {
                        loadMoreBtn.style.display = 'none';
                    }, 2000);
                }
            });
        });
    }

    // 무한 스크롤 초기화
    function initInfiniteScroll() {
        const container = document.getElementById('internet-products-container') ||
                         document.getElementById('mno-sim-products-container') ||
                         document.getElementById('mvno-products-container') ||
                         document.getElementById('mno-products-container');
        
        if (!container) return;

        const loadMoreBtn = document.getElementById('load-more-internet-btn') || 
                           document.getElementById('load-more-mno-sim-btn') ||
                           document.getElementById('load-more-mvno-btn') ||
                           document.getElementById('load-more-mno-btn');
        
        if (!loadMoreBtn) return;

        const productType = loadMoreBtn.getAttribute('data-type');
        let currentPage = parseInt(loadMoreBtn.getAttribute('data-page')) || 2;
        let isLoading = false;

        // Intersection Observer로 버튼 감지
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting && !isLoading) {
                    isLoading = true;
                    loadMoreBtn.disabled = true;
                    loadMoreBtn.textContent = '로딩 중...';

                    loadMoreProducts(productType, currentPage, function(success, data) {
                        isLoading = false;
                        loadMoreBtn.disabled = false;

                        if (success && data) {
                            // 상품 추가 (HTML 또는 JSON)
                            appendProducts(productType, data);
                            
                            // 페이지 번호 증가
                            currentPage++;
                            loadMoreBtn.setAttribute('data-page', currentPage);

                            // 남은 개수 업데이트
                            const remainingCount = data.pagination.remaining;
                            const remainingSpan = document.getElementById('remaining-count');
                            if (remainingSpan) {
                                remainingSpan.textContent = remainingCount;
                            }

                            // 더 이상 로드할 상품이 없으면 버튼 숨김
                            if (!data.pagination.hasMore) {
                                loadMoreBtn.style.display = 'none';
                                observer.disconnect();
                            } else {
                                loadMoreBtn.textContent = `더보기 (${remainingCount}개 남음)`;
                            }
                        } else {
                            loadMoreBtn.style.display = 'none';
                            observer.disconnect();
                        }
                    });
                }
            });
        }, {
            rootMargin: '100px' // 버튼이 화면에 나타나기 100px 전에 로드 시작
        });

        observer.observe(loadMoreBtn);
    }

    // API 호출하여 더 많은 상품 로드
    function loadMoreProducts(type, page, callback) {
        const url = `/MVNO/api/load-more-products.php?type=${type}&page=${page}&limit=${ITEMS_PER_PAGE}`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    callback(true, data);
                } else {
                    console.error('상품 로드 실패:', data.message);
                    callback(false, null);
                }
            })
            .catch(error => {
                console.error('상품 로드 오류:', error);
                callback(false, null);
            });
    }

    // 상품을 DOM에 추가
    function appendProducts(type, data) {
        const container = document.getElementById('internet-products-container') ||
                         document.getElementById('mno-sim-products-container') ||
                         document.getElementById('mvno-products-container') ||
                         document.getElementById('mno-products-container');
        
        if (!container) return;

        // HTML 배열인 경우 (API에서 HTML을 직접 반환한 경우)
        if (data.html && Array.isArray(data.html)) {
            data.html.forEach(html => {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                while (tempDiv.firstChild) {
                    container.appendChild(tempDiv.firstChild);
                }
            });
            
            // 아코디언 재초기화
            if (window.initAccordions) {
                window.initAccordions();
            } else if (typeof initAccordions === 'function') {
                initAccordions();
            }
            
            // plan-accordion.js가 로드되어 있으면 재초기화
            if (typeof setupAccordions === 'function') {
                setupAccordions();
            }
        } 
        // JSON 데이터인 경우 (mvno, mno, mno-sim)
        else if (data.products && Array.isArray(data.products)) {
            // TODO: JSON 데이터를 HTML로 변환하는 로직 필요
            // 현재는 페이지 새로고침으로 처리
            console.log('JSON 데이터를 HTML로 변환하는 로직이 필요합니다.');
        }
    }

    // 페이지 로드 시 초기화
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            if (LOAD_MORE_MODE === 'button') {
                initLoadMoreButton();
            } else {
                initInfiniteScroll();
            }
        });
    } else {
        if (LOAD_MORE_MODE === 'button') {
            initLoadMoreButton();
        } else {
            initInfiniteScroll();
        }
    }

    // 전역으로 노출 (각 페이지에서 오버라이드 가능)
    window.appendProducts = appendProducts;
    window.generateProductHtml = generateProductHtml;

})();


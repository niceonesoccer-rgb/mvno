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
                           document.getElementById('load-more-mno-btn') ||
                           document.getElementById('load-more-wishlist-btn') ||
                           document.getElementById('load-more-mvno-order-btn') ||
                           document.getElementById('load-more-mno-order-btn') ||
                           document.getElementById('load-more-mno-sim-order-btn') ||
                           document.getElementById('load-more-internet-order-btn');
        
        if (!loadMoreBtn) return;

        const productType = loadMoreBtn.getAttribute('data-type');
        let currentPage = parseInt(loadMoreBtn.getAttribute('data-page')) || 2;
        let isLoading = false;
        const isWishlist = loadMoreBtn.getAttribute('data-wishlist') === 'true';
        const isOrder = loadMoreBtn.getAttribute('data-order') === 'true';

        loadMoreBtn.addEventListener('click', function() {
            if (isLoading) return;
            
            isLoading = true;
            loadMoreBtn.disabled = true;
            loadMoreBtn.textContent = '로딩 중...';

            // 필터 파라미터 가져오기 (mno-sim용)
            const filterProvider = loadMoreBtn.getAttribute('data-provider') || '';
            const filterServiceType = loadMoreBtn.getAttribute('data-service-type') || '';

            // 디버깅: 요청 전 로그
            console.log('더보기 요청:', {
                type: productType,
                page: currentPage,
                limit: ITEMS_PER_PAGE,
                filterProvider: filterProvider,
                filterServiceType: filterServiceType,
                isWishlist: isWishlist,
                isOrder: isOrder
            });

            loadMoreProducts(productType, currentPage, filterProvider, filterServiceType, isWishlist, isOrder, function(success, data) {
                isLoading = false;
                loadMoreBtn.disabled = false;

                console.log('더보기 응답:', { 
                    success, 
                    data, 
                    htmlLength: data?.html?.length,
                    htmlCount: data?.html ? data.html.length : 0,
                    pagination: data?.pagination,
                    requestedPage: currentPage,
                    requestedLimit: ITEMS_PER_PAGE
                });

                if (success && data && data.html && Array.isArray(data.html) && data.html.length > 0) {
                    // 상품 추가 (HTML 또는 JSON)
                    appendProducts(productType, data);
                    
                    // 페이지 번호 증가
                    currentPage++;
                    loadMoreBtn.setAttribute('data-page', currentPage);

                    // 남은 개수 업데이트 (알뜰폰과 동일하게 number_format 적용)
                    const remainingCount = data.pagination ? data.pagination.remaining : 0;
                    const remainingSpan = document.getElementById('remaining-count');
                    if (remainingSpan) {
                        remainingSpan.textContent = remainingCount.toLocaleString();
                    }

                    // 더 이상 로드할 상품이 없으면 버튼 숨김
                    // hasMore가 false이거나 remaining이 0 이하이면 더 이상 없음
                    const hasMore = data.pagination && data.pagination.hasMore === true;
                    const remaining = data.pagination ? (data.pagination.remaining || 0) : 0;
                    
                    if (!hasMore || remaining <= 0) {
                        loadMoreBtn.textContent = '더 이상 불러올 상품이 없습니다.';
                        setTimeout(() => {
                            loadMoreBtn.style.display = 'none';
                        }, 2000);
                    } else {
                        loadMoreBtn.textContent = `더보기 (${remainingCount.toLocaleString()}개 남음)`;
                    }
                } else {
                    console.error('더보기 실패:', { success, data, htmlLength: data?.html?.length });
                    // 실패해도 pagination 정보가 있으면 버튼 유지
                    if (data && data.pagination) {
                        const hasMore = data.pagination.hasMore === true;
                        const remainingCount = data.pagination.remaining || 0;
                        
                        if (hasMore && remainingCount > 0) {
                            // 아직 더 있을 수 있으므로 버튼 유지하고 재시도 가능하도록
                            loadMoreBtn.textContent = `더보기 (${remainingCount.toLocaleString()}개 남음)`;
                            console.warn('HTML이 비어있지만 더 많은 상품이 있을 수 있습니다. 다시 시도해주세요.');
                        } else {
                            loadMoreBtn.textContent = '더 이상 불러올 상품이 없습니다.';
                            setTimeout(() => {
                                loadMoreBtn.style.display = 'none';
                            }, 2000);
                        }
                    } else {
                        loadMoreBtn.textContent = '더 이상 불러올 상품이 없습니다.';
                        setTimeout(() => {
                            loadMoreBtn.style.display = 'none';
                        }, 2000);
                    }
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
                           document.getElementById('load-more-mno-btn') ||
                           document.getElementById('load-more-wishlist-btn');
        
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

                    // 필터 파라미터 가져오기 (mno-sim용)
                    const filterProvider = loadMoreBtn.getAttribute('data-provider') || '';
                    const filterServiceType = loadMoreBtn.getAttribute('data-service-type') || '';
                    const isWishlist = loadMoreBtn.getAttribute('data-wishlist') === 'true';
                    const isOrder = loadMoreBtn.getAttribute('data-order') === 'true';

                    loadMoreProducts(productType, currentPage, filterProvider, filterServiceType, isWishlist, isOrder, function(success, data) {
                        isLoading = false;
                        loadMoreBtn.disabled = false;

                        if (success && data) {
                            // 상품 추가 (HTML 또는 JSON)
                            appendProducts(productType, data);
                            
                            // 페이지 번호 증가
                            currentPage++;
                            loadMoreBtn.setAttribute('data-page', currentPage);

                            // 남은 개수 업데이트 (알뜰폰과 동일하게 number_format 적용)
                            const remainingCount = data.pagination.remaining;
                            const remainingSpan = document.getElementById('remaining-count');
                            if (remainingSpan) {
                                remainingSpan.textContent = remainingCount.toLocaleString();
                            }

                            // 더 이상 로드할 상품이 없으면 버튼 숨김 (알뜰폰과 동일한 로직)
                            if (!data.pagination.hasMore) {
                                loadMoreBtn.style.display = 'none';
                                observer.disconnect();
                            } else {
                                loadMoreBtn.textContent = `더보기 (${remainingCount.toLocaleString()}개 남음)`;
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
    function loadMoreProducts(type, page, filterProvider, filterServiceType, isWishlist, isOrder, callback) {
        let url = `/MVNO/api/load-more-products.php?type=${type}&page=${page}&limit=${ITEMS_PER_PAGE}`;
        
        // 주문내역 파라미터 추가
        if (isOrder) {
            url += `&order=true`;
        }
        
        // 위시리스트 파라미터 추가
        if (isWishlist) {
            url += `&wishlist=true`;
        }
        
        // 필터 파라미터 추가 (mno-sim용)
        if (filterProvider) {
            url += `&provider=${encodeURIComponent(filterProvider)}`;
        }
        if (filterServiceType) {
            url += `&service_type=${encodeURIComponent(filterServiceType)}`;
        }
        
        // 스폰서 광고는 모두 첫 페이지에 표시되므로 더보기에서는 처리하지 않음
        
        console.log('API 요청 URL:', url);
        console.log('API 요청 파라미터:', {
            type: type,
            page: page,
            limit: ITEMS_PER_PAGE,
            filterProvider: filterProvider,
            filterServiceType: filterServiceType,
            isWishlist: isWishlist
        });
        
        fetch(url)
            .then(async response => {
                const responseText = await response.text();
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    console.error('JSON 파싱 오류:', e);
                    console.error('응답 텍스트:', responseText);
                    throw new Error('Invalid JSON response: ' + responseText.substring(0, 200));
                }
                
                if (!response.ok) {
                    console.error('API 에러 응답:', data);
                    if (data.error) {
                        console.error('에러 메시지:', data.error);
                        console.error('에러 파일:', data.file, '라인:', data.line);
                    }
                    throw new Error(data.message || 'HTTP error! status: ' + response.status);
                }
                
                if (data.success) {
                    callback(true, data);
                } else {
                    console.error('상품 로드 실패:', data.message || '알 수 없는 오류');
                    if (data.error) {
                        console.error('에러 상세:', data.error);
                    }
                    callback(false, data); // 에러 정보도 전달
                }
            })
            .catch(error => {
                console.error('상품 로드 오류:', error);
                console.error('요청 URL:', url);
                callback(false, null);
            });
    }

    // 상품을 DOM에 추가
    function appendProducts(type, data) {
        const container = document.getElementById('internet-products-container') ||
                         document.getElementById('mno-sim-products-container') ||
                         document.getElementById('mvno-products-container') ||
                         document.getElementById('mno-products-container') ||
                         document.getElementById('mvno-orders-container') ||
                         document.getElementById('mno-orders-container') ||
                         document.getElementById('mno-sim-orders-container') ||
                         document.getElementById('internet-orders-container');
        
        if (!container) {
            console.error('appendProducts: 컨테이너를 찾을 수 없습니다.');
            return;
        }

        // 추가 전 현재 아이템 개수 확인
        const beforeCount = container.children.length;
        console.log('appendProducts 시작:', {
            type: type,
            htmlCount: data?.html?.length || 0,
            beforeCount: beforeCount,
            containerId: container.id
        });

        // HTML 배열인 경우 (API에서 HTML을 직접 반환한 경우)
        if (data.html && Array.isArray(data.html)) {
            // 더보기 버튼 컨테이너 찾기 (임시로 제거하기 위해)
            const loadMoreContainer = container.querySelector('.load-more-container');
            
            let addedCount = 0;
            data.html.forEach((html, index) => {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                
                // 래퍼가 있으면 래퍼 자체를 추가, 없으면 모든 자식 요소를 추가
                const wrapper = tempDiv.querySelector('.plan-item-wrapper, .phone-item-wrapper, .internet-item-wrapper, .order-item-wrapper');
                if (wrapper) {
                    // 래퍼가 있으면 래퍼를 직접 추가
                    if (loadMoreContainer) {
                        container.insertBefore(wrapper, loadMoreContainer);
                    } else {
                        container.appendChild(wrapper);
                    }
                    addedCount++;
                } else {
                    // 래퍼가 없으면 모든 자식 요소를 개별적으로 추가 (기존 로직)
                    while (tempDiv.firstChild) {
                        if (loadMoreContainer) {
                            container.insertBefore(tempDiv.firstChild, loadMoreContainer);
                        } else {
                            container.appendChild(tempDiv.firstChild);
                        }
                        addedCount++;
                    }
                }
            });
            
            // 추가 후 현재 아이템 개수 확인
            const afterCount = container.children.length;
            console.log('appendProducts 완료:', {
                htmlArrayLength: data.html.length,
                addedCount: addedCount,
                beforeCount: beforeCount,
                afterCount: afterCount,
                actualAdded: afterCount - beforeCount
            });
            
            if (afterCount - beforeCount !== data.html.length) {
                console.warn('⚠️ 경고: 추가된 아이템 개수가 HTML 배열 길이와 일치하지 않습니다!', {
                    expected: data.html.length,
                    actual: afterCount - beforeCount
                });
            }
            
            // 더보기 버튼을 맨 아래로 이동
            if (loadMoreContainer && loadMoreContainer.parentNode === container) {
                container.appendChild(loadMoreContainer);
            }
            
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
            
            // 찜하기 버튼 재초기화 (favorite-heart.js)
            if (typeof initFavoriteHearts === 'function') {
                initFavoriteHearts();
            } else if (typeof initFavoriteButtons === 'function') {
                initFavoriteButtons();
            }
            
            // 새로 추가된 카드의 찜 상태 초기화
            if (typeof initializeFavoriteStates === 'function') {
                initializeFavoriteStates();
            }
            
            // 공유 버튼 재초기화 (share.js)
            if (typeof initShareButtons === 'function') {
                initShareButtons();
            }
            
            // 주문내역 카드 클릭 이벤트 재초기화
            const newApplicationCards = container.querySelectorAll('.application-card:not([data-click-initialized])');
            newApplicationCards.forEach(card => {
                card.setAttribute('data-click-initialized', 'true');
                const applicationId = card.getAttribute('data-application-id');
                if (applicationId) {
                    card.addEventListener('click', function(e) {
                        if (typeof openModal === 'function') {
                            openModal(applicationId);
                        }
                    });
                }
            });
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
    
    // 버전: 2024-01-01 (캐시 무효화용)

})();


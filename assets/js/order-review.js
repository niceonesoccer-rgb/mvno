/**
 * 주문 페이지 리뷰 관리 공통 모듈
 * 
 * @param {Object} config 설정 객체
 * @param {string} config.prefix 클래스명 및 ID prefix (예: 'internet', 'mno', 'mvno')
 * @param {string} config.itemIdAttr 아이템 ID 속성명 (예: 'data-internet-id', 'data-phone-id', 'data-plan-id')
 * @param {string} config.speedLabel 속도 관련 라벨 (예: '설치 빨라요', '개통 빨라요')
 * @param {string} config.textareaId 텍스트 영역 ID (기본값: '{prefix}ReviewText' 또는 'reviewText')
 * @param {Function} config.onReviewSubmit 리뷰 제출 콜백 함수 (itemId, reviewData)
 * @param {Function} config.onReviewDelete 리뷰 삭제 콜백 함수 (itemId)
 * @param {Function} config.onReviewUpdate 리뷰 작성 후 UI 업데이트 콜백 함수 (itemId)
 * @param {Function} config.onReviewDeleteUpdate 리뷰 삭제 후 UI 업데이트 콜백 함수 (itemId)
 * @param {boolean} config.showSuccessModal 성공 모달 표시 여부 (기본값: false)
 */
(function() {
    'use strict';

    function OrderReviewManager(config) {
        this.prefix = config.prefix || 'order';
        this.itemIdAttr = config.itemIdAttr || 'data-item-id';
        this.speedLabel = config.speedLabel || '개통 빨라요';
        this.textareaId = config.textareaId || (this.prefix === 'internet' ? this.prefix + 'ReviewText' : 'reviewText');
        this.onReviewSubmit = config.onReviewSubmit || null;
        this.onReviewDelete = config.onReviewDelete || null;
        this.onReviewUpdate = config.onReviewUpdate || null;
        this.onReviewDeleteUpdate = config.onReviewDeleteUpdate || null;
        this.showSuccessModal = config.showSuccessModal || false;

        // 모달 ID
        this.reviewModalId = this.prefix + 'ReviewModal';
        this.deleteModalId = this.prefix + 'ReviewDeleteModal';
        this.successModalId = this.prefix + 'ReviewSuccessModal';
        this.formId = this.prefix + 'ReviewForm';

        // 스크롤 위치 저장
        this.reviewModalScrollPosition = 0;
        
        // 이벤트 리스너 중복 등록 방지 (prefix별로)
        this.eventListenersSetup = false;
        
        // 초기화 실행
        this.init();
    }

    OrderReviewManager.prototype = {
        init: function() {
            const self = this;
            function setup() {
                if (!self.eventListenersSetup) {
                    // 모달이 존재하는지 확인
                    const modal = document.getElementById(self.reviewModalId);
                    if (modal) {
                        console.log('모달 확인됨, 이벤트 리스너 등록:', self.prefix);
                        self.setupEventListeners();
                    } else {
                        console.log('모달 아직 없음, 재시도:', self.reviewModalId);
                        // 모달이 아직 없으면 200ms 후 재시도 (최대 5번)
                        if (!self.retryCount) {
                            self.retryCount = 0;
                        }
                        if (self.retryCount < 5) {
                            self.retryCount++;
                            setTimeout(setup, 200);
                        } else {
                            console.error('모달을 찾을 수 없습니다:', self.reviewModalId);
                        }
                    }
                }
            }
            
            // DOM이 준비되었는지 확인
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(setup, 100);
                });
            } else {
                // DOM이 이미 준비된 경우 약간의 지연 후 실행 (모달이 포함될 시간 확보)
                setTimeout(setup, 200);
            }
        },

        // 스크롤바 너비 계산
        getScrollbarWidth: function() {
            const outer = document.createElement('div');
            outer.style.visibility = 'hidden';
            outer.style.overflow = 'scroll';
            outer.style.msOverflowStyle = 'scrollbar';
            document.body.appendChild(outer);
            
            const inner = document.createElement('div');
            outer.appendChild(inner);
            
            const scrollbarWidth = outer.offsetWidth - inner.offsetWidth;
            
            outer.parentNode.removeChild(outer);
            
            return scrollbarWidth;
        },

        // 리뷰 작성 모달 열기
        openReviewModal: function(itemId) {
            console.log('openReviewModal 호출:', itemId, 'Modal ID:', this.reviewModalId);
            const modal = document.getElementById(this.reviewModalId);
            console.log('모달 요소:', modal);
            if (modal) {
                // 현재 스크롤 위치 저장
                this.reviewModalScrollPosition = window.pageYOffset || document.documentElement.scrollTop;
                
                // 스크롤바 너비 계산
                const scrollbarWidth = this.getScrollbarWidth();
                
                // body 스크롤 방지
                document.body.style.overflow = 'hidden';
                document.body.style.position = 'fixed';
                document.body.style.top = `-${this.reviewModalScrollPosition}px`;
                document.body.style.width = '100%';
                document.body.style.paddingRight = `${scrollbarWidth}px`;
                document.documentElement.style.overflow = 'hidden';
                
                modal.style.display = 'flex';
                console.log('모달 display 설정:', modal.style.display);
                const attrName = this.itemIdAttr.replace('data-', '');
                modal.setAttribute(attrName, itemId);
                
                // 텍스트 영역 포커스
                setTimeout(() => {
                    const textarea = document.getElementById(this.textareaId);
                    if (textarea) {
                        textarea.focus();
                    }
                }, 100);
            } else {
                console.error('모달을 찾을 수 없습니다:', this.reviewModalId);
            }
        },

        // 리뷰 작성 모달 닫기
        closeReviewModal: function() {
            const modal = document.getElementById(this.reviewModalId);
            if (modal) {
                modal.style.display = 'none';
                
                // body 스크롤 복원
                document.body.style.overflow = '';
                document.body.style.position = '';
                document.body.style.top = '';
                document.body.style.width = '';
                document.body.style.paddingRight = '';
                document.documentElement.style.overflow = '';
                
                // 저장된 스크롤 위치로 복원
                window.scrollTo(0, this.reviewModalScrollPosition);
                
                // 폼 초기화
                const form = document.getElementById(this.formId);
                if (form) {
                    form.reset();
                }
            }
        },

        // 삭제 모달 열기
        openDeleteModal: function(itemId) {
            console.log('삭제 모달 열기:', itemId);
            const deleteModal = document.getElementById(this.deleteModalId);
            if (deleteModal) {
                const scrollbarWidth = this.getScrollbarWidth();
                
                document.body.style.overflow = 'hidden';
                document.body.style.position = 'fixed';
                document.body.style.top = `-${window.pageYOffset || document.documentElement.scrollTop}px`;
                document.body.style.width = '100%';
                document.body.style.paddingRight = `${scrollbarWidth}px`;
                document.documentElement.style.overflow = 'hidden';
                
                deleteModal.style.display = 'flex';
                // data- 속성으로 저장
                deleteModal.setAttribute(this.itemIdAttr, itemId);
                console.log('삭제 모달에 itemId 설정:', this.itemIdAttr, itemId);
            } else {
                console.error('삭제 모달을 찾을 수 없습니다:', this.deleteModalId);
            }
        },

        // 삭제 모달 닫기
        closeDeleteModal: function() {
            const deleteModal = document.getElementById(this.deleteModalId);
            if (deleteModal) {
                deleteModal.style.display = 'none';
                
                const scrollTop = parseInt(document.body.style.top || '0') * -1;
                document.body.style.overflow = '';
                document.body.style.position = '';
                document.body.style.top = '';
                document.body.style.width = '';
                document.body.style.paddingRight = '';
                document.documentElement.style.overflow = '';
                window.scrollTo(0, scrollTop);
            }
        },

        // 성공 모달 열기
        openSuccessModal: function() {
            if (!this.showSuccessModal) return;
            
            const modal = document.getElementById(this.successModalId);
            if (modal) {
                modal.style.display = 'flex';
            }
        },

        // 성공 모달 닫기
        closeSuccessModal: function() {
            if (!this.showSuccessModal) return;
            
            const modal = document.getElementById(this.successModalId);
            if (modal) {
                modal.style.display = 'none';
                
                document.body.style.overflow = '';
                document.body.style.position = '';
                document.body.style.top = '';
                document.body.style.width = '';
                document.body.style.paddingRight = '';
                document.documentElement.style.overflow = '';
                
                window.scrollTo(0, this.reviewModalScrollPosition);
            }
        },
        
        // 별점 선택 기능 설정
        setupStarRating: function() {
            const self = this;
            const starRatings = document.querySelectorAll('.' + this.prefix + '-star-rating');
            
            starRatings.forEach(function(ratingContainer) {
                const stars = ratingContainer.querySelectorAll('.' + self.prefix + '-star-label');
                
                // 별 클릭 이벤트
                stars.forEach(function(star) {
                    star.addEventListener('click', function(e) {
                        e.preventDefault();
                        const rating = parseInt(this.getAttribute('data-rating'));
                        const input = ratingContainer.querySelector('input[value="' + rating + '"]');
                        if (input) {
                            input.checked = true;
                            self.updateStarDisplay(ratingContainer, rating);
                        }
                    });
                    
                    // 호버 이벤트
                    star.addEventListener('mouseenter', function() {
                        const rating = parseInt(this.getAttribute('data-rating'));
                        self.updateStarDisplay(ratingContainer, rating, true);
                    });
                });
                
                // 마우스가 별점 영역을 벗어나면 선택된 값으로 복원
                ratingContainer.addEventListener('mouseleave', function() {
                    const checkedInput = ratingContainer.querySelector('input[type="radio"]:checked');
                    if (checkedInput) {
                        const rating = parseInt(checkedInput.value);
                        self.updateStarDisplay(ratingContainer, rating, false);
                    } else {
                        self.updateStarDisplay(ratingContainer, 0, false);
                    }
                });
            });
        },
        
        // 별점 표시 업데이트
        updateStarDisplay: function(ratingContainer, rating, isHover) {
            const stars = ratingContainer.querySelectorAll('.' + this.prefix + '-star-label');
            const starColor = '#ef4444'; // 빨간색
            const defaultColor = '#d1d5db'; // 회색
            const hoverColor = '#fca5a5'; // 연한 빨간색
            
            stars.forEach(function(star) {
                const starRating = parseInt(star.getAttribute('data-rating'));
                if (starRating <= rating) {
                    star.style.color = isHover ? hoverColor : starColor;
                } else {
                    star.style.color = defaultColor;
                }
            });
        },

        // 리뷰 삭제 확인
        confirmDeleteReview: function(itemId) {
            if (this.onReviewDelete) {
                this.onReviewDelete(itemId);
            } else {
                // 기본 동작
                console.log('리뷰 삭제 - Item ID:', itemId);
            }
            
            this.showToast('리뷰가 삭제되었습니다.');
            this.closeDeleteModal();
            
            if (this.onReviewDeleteUpdate) {
                this.onReviewDeleteUpdate(itemId);
            }
        },

        // 토스트 메시지 표시
        showToast: function(message) {
            const toastClass = '.' + this.prefix + '-review-toast';
            const existingToast = document.querySelector(toastClass);
            if (existingToast) {
                existingToast.remove();
            }

            const toast = document.createElement('div');
            toast.className = this.prefix + '-review-toast';
            toast.textContent = message;
            document.body.appendChild(toast);

            const toastTop = window.innerHeight / 2;
            const toastLeft = window.innerWidth / 2;

            toast.style.top = toastTop + 'px';
            toast.style.left = toastLeft + 'px';
            toast.style.transform = 'translateX(-50%) translateY(-50%) translateY(10px)';

            setTimeout(() => {
                toast.classList.add(this.prefix + '-review-toast-visible');
            }, 10);

            setTimeout(() => {
                toast.classList.remove(this.prefix + '-review-toast-visible');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, 700);
        },

        // 이벤트 리스너 설정
        setupEventListeners: function() {
            // 중복 등록 방지 (prefix별로)
            const setupKey = 'orderReviewSetup_' + this.prefix;
            if (window[setupKey]) {
                console.log('이벤트 리스너 이미 등록됨:', this.prefix);
                return;
            }
            window[setupKey] = true;
            this.eventListenersSetup = true;
            
            console.log('이벤트 리스너 등록 시작:', this.prefix);
            
            const self = this;
            const buttonClass = '.' + this.prefix + '-order-review-btn';
            const editButtonClass = '.' + this.prefix + '-order-review-edit-btn';
            const deleteButtonClass = '.' + this.prefix + '-order-review-delete-btn';
            
            console.log('버튼 클래스:', buttonClass, editButtonClass, deleteButtonClass);

            // 이벤트 위임을 사용하여 동적으로 추가된 버튼도 처리
            // 리뷰쓰기 버튼
            document.addEventListener('click', function(e) {
                // 버튼 자체를 클릭한 경우
                if (e.target.classList.contains(self.prefix + '-order-review-btn')) {
                    const button = e.target;
                    const itemId = button.getAttribute(self.itemIdAttr);
                    if (itemId && !button.disabled) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('리뷰쓰기 버튼 클릭 (직접):', itemId, 'Prefix:', self.prefix);
                        self.openReviewModal(itemId);
                        return;
                    }
                }
                
                // 버튼 내부 요소를 클릭한 경우
                const button = e.target.closest(buttonClass);
                if (button) {
                    const itemId = button.getAttribute(self.itemIdAttr);
                    if (itemId && !button.disabled) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('리뷰쓰기 버튼 클릭 (closest):', itemId, 'Prefix:', self.prefix);
                        self.openReviewModal(itemId);
                    }
                }
            });

            // 수정 버튼
            document.addEventListener('click', function(e) {
                const button = e.target.closest(editButtonClass);
                if (button) {
                    const itemId = button.getAttribute(self.itemIdAttr);
                    if (itemId) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('수정 버튼 클릭:', itemId, 'Prefix:', self.prefix);
                        self.openReviewModal(itemId);
                        // TODO: 기존 리뷰 데이터를 모달에 로드
                    }
                }
            });

            // 삭제 버튼
            document.addEventListener('click', function(e) {
                const button = e.target.closest(deleteButtonClass);
                if (button) {
                    const itemId = button.getAttribute(self.itemIdAttr);
                    if (itemId) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('삭제 버튼 클릭:', itemId, 'Prefix:', self.prefix);
                        self.openDeleteModal(itemId);
                    }
                }
            });
            
            // 점 3개 메뉴 버튼 클릭 이벤트
            const menuBtnClass = '.' + this.prefix + '-order-menu-btn';
            document.addEventListener('click', function(e) {
                const menuBtn = e.target.closest(menuBtnClass);
                if (menuBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const itemId = menuBtn.getAttribute(self.itemIdAttr);
                    if (itemId) {
                        self.toggleMenuDropdown(itemId);
                    }
                }
            });
            
            // 메뉴 외부 클릭 시 닫기
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.' + self.prefix + '-order-menu-group') && 
                    !e.target.closest('.' + self.prefix + '-order-menu-dropdown')) {
                    self.closeAllMenuDropdowns();
                }
            });

            // 리뷰 모달 이벤트
            const reviewModal = document.getElementById(this.reviewModalId);
            if (reviewModal) {
                const closeBtn = reviewModal.querySelector('.' + this.prefix + '-review-modal-close');
                const cancelBtn = reviewModal.querySelector('.' + this.prefix + '-review-btn-cancel');
                const overlay = reviewModal.querySelector('.' + this.prefix + '-review-modal-overlay');

                if (closeBtn) {
                    closeBtn.addEventListener('click', () => self.closeReviewModal());
                }
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', () => self.closeReviewModal());
                }
                if (overlay) {
                    overlay.addEventListener('click', () => self.closeReviewModal());
                }

                // ESC 키로 모달 닫기
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && reviewModal.style.display === 'flex') {
                        self.closeReviewModal();
                    }
                });
            }

            // 삭제 모달 이벤트
            const deleteModal = document.getElementById(this.deleteModalId);
            if (deleteModal) {
                const closeBtn = deleteModal.querySelector('.' + this.prefix + '-review-delete-modal-close');
                const cancelBtn = deleteModal.querySelector('.' + this.prefix + '-review-delete-btn-cancel');
                const confirmBtn = deleteModal.querySelector('.' + this.prefix + '-review-delete-btn-confirm');
                const overlay = deleteModal.querySelector('.' + this.prefix + '-review-delete-modal-overlay');

                if (closeBtn) {
                    closeBtn.addEventListener('click', () => self.closeDeleteModal());
                }
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', () => self.closeDeleteModal());
                }
                if (overlay) {
                    overlay.addEventListener('click', () => self.closeDeleteModal());
                }
                if (confirmBtn) {
                    confirmBtn.addEventListener('click', function() {
                        const itemId = deleteModal.getAttribute(self.itemIdAttr);
                        console.log('삭제 확인 버튼 클릭, itemId:', itemId);
                        if (itemId) {
                            self.confirmDeleteReview(itemId);
                        } else {
                            console.error('itemId를 찾을 수 없습니다');
                        }
                    });
                } else {
                    console.error('삭제 확인 버튼을 찾을 수 없습니다');
                }

                // ESC 키로 모달 닫기
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && deleteModal.style.display === 'flex') {
                        self.closeDeleteModal();
                    }
                });
            }

            // 별점 선택 이벤트 (마우스로 별 클릭)
            this.setupStarRating();
            
            // 성공 모달 이벤트
            if (this.showSuccessModal) {
                const successModal = document.getElementById(this.successModalId);
                if (successModal) {
                    const confirmBtn = successModal.querySelector('.' + this.prefix + '-review-success-btn-confirm');
                    const overlay = successModal.querySelector('.' + this.prefix + '-review-success-modal-overlay');

                    if (confirmBtn) {
                        confirmBtn.addEventListener('click', () => self.closeSuccessModal());
                    }
                    if (overlay) {
                        overlay.addEventListener('click', () => self.closeSuccessModal());
                    }

                    // ESC 키로 모달 닫기
                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape' && successModal.style.display === 'flex') {
                            self.closeSuccessModal();
                        }
                    });
                }
            }

            // 리뷰 작성 폼 제출
            const reviewForm = document.getElementById(this.formId);
            if (reviewForm) {
                console.log('리뷰 폼 찾음:', this.formId);
                
                // 폼 제출 이벤트
                reviewForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log('폼 제출 이벤트 발생');
                    self.handleReviewSubmit();
                });
                
                // 제출 버튼 클릭 이벤트 (추가 보험)
                const submitBtn = reviewForm.querySelector('.' + self.prefix + '-review-btn-submit');
                if (submitBtn) {
                    console.log('제출 버튼 찾음');
                    submitBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        console.log('제출 버튼 클릭');
                        self.handleReviewSubmit();
                    });
                }
            } else {
                console.error('리뷰 폼을 찾을 수 없습니다:', this.formId);
            }
        },
        
        // 리뷰 제출 처리
        handleReviewSubmit: function() {
            const self = this;
            const reviewForm = document.getElementById(this.formId);
            if (!reviewForm) {
                console.error('리뷰 폼을 찾을 수 없습니다:', this.formId);
                return;
            }
            
            const modal = document.getElementById(this.reviewModalId);
            const attrName = this.itemIdAttr.replace('data-', '');
            const itemId = modal ? modal.getAttribute(attrName) : null;
            const reviewText = document.getElementById(this.textareaId) ? document.getElementById(this.textareaId).value.trim() : '';
            const kindnessRatingInput = reviewForm.querySelector('input[name="kindness_rating"]:checked');
            const speedRatingInput = reviewForm.querySelector('input[name="speed_rating"]:checked');
            const kindnessRating = kindnessRatingInput ? parseInt(kindnessRatingInput.value) : null;
            const speedRating = speedRatingInput ? parseInt(speedRatingInput.value) : null;

            console.log('리뷰 제출 처리 시작:', {
                itemId: itemId,
                kindnessRating: kindnessRating,
                speedRating: speedRating,
                reviewText: reviewText
            });

            if (!kindnessRating) {
                this.showToast('친절해요 별점을 선택해주세요.');
                return;
            }

            if (!speedRating) {
                this.showToast(this.speedLabel + ' 별점을 선택해주세요.');
                return;
            }

            if (!reviewText) {
                this.showToast('리뷰 내용을 입력해주세요.');
                return;
            }

            if (!itemId) {
                this.showToast('오류가 발생했습니다. 다시 시도해주세요.');
                return;
            }

            const reviewData = {
                kindnessRating: kindnessRating,
                speedRating: speedRating,
                reviewText: reviewText
            };

            // 리뷰 별점 추적
            this.trackReviewRating(itemId, {
                kindness_rating: kindnessRating,
                speed_rating: speedRating
            });

            if (this.onReviewSubmit) {
                this.onReviewSubmit(itemId, reviewData);
            } else {
                // 기본 동작
                console.log('리뷰 작성 - Item ID:', itemId, 'Review Data:', reviewData);
            }

            if (this.showSuccessModal) {
                this.closeReviewModal();
                this.openSuccessModal();
            } else {
                this.showToast('리뷰가 작성되었습니다.');
                this.closeReviewModal();
            }

            if (this.onReviewUpdate) {
                this.onReviewUpdate(itemId);
            }
        },
        
        // 점 3개 메뉴 드롭다운 토글
        toggleMenuDropdown: function(itemId) {
            const dropdownId = this.prefix + '-order-menu-' + itemId;
            const dropdown = document.getElementById(dropdownId);
            if (dropdown) {
                // 다른 모든 드롭다운 닫기
                this.closeAllMenuDropdowns();
                // 현재 드롭다운 토글
                const isVisible = dropdown.style.display === 'flex' || (dropdown.style.display !== 'none' && window.getComputedStyle(dropdown).display !== 'none');
                if (isVisible) {
                    dropdown.style.display = 'none';
                } else {
                    dropdown.style.display = 'flex';
                }
            }
        },
        
        // 모든 메뉴 드롭다운 닫기
        closeAllMenuDropdowns: function() {
            const dropdowns = document.querySelectorAll('.' + this.prefix + '-order-menu-dropdown');
            dropdowns.forEach(function(dropdown) {
                dropdown.style.display = 'none';
            });
        }
    };

    // 전역으로 노출
    window.OrderReviewManager = OrderReviewManager;
})();


<?php
/**
 * 인터넷 리뷰 작성 모달 컴포넌트
 */
?>

<!-- 리뷰 작성 모달 -->
<div class="internet-review-modal" id="internetReviewModal" style="display: none;">
    <div class="internet-review-modal-overlay"></div>
    <div class="internet-review-modal-content">
        <div class="internet-review-modal-header">
            <div class="internet-review-modal-header-content">
                <div class="internet-review-modal-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="#6366f1"/>
                    </svg>
                </div>
                <h3 class="internet-review-modal-title">리뷰 작성</h3>
            </div>
            <button type="button" class="internet-review-modal-close" aria-label="닫기">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="internet-review-modal-body">
            <form id="internetReviewForm">
                <div class="internet-review-form-group internet-rating-group-row">
                    <div class="internet-rating-item">
                        <label class="internet-review-form-label">
                            <span class="label-icon">😊</span>
                            <span class="label-text">친절해요</span>
                        </label>
                        <div class="internet-star-rating" data-rating-type="kindness">
                            <?php 
                            $uniqueId1 = uniqid('internet-kindness-');
                            for ($i = 5; $i >= 1; $i--): 
                            ?>
                                <input type="radio" id="kindness-star<?php echo $i; ?>-<?php echo $uniqueId1; ?>" name="kindness_rating" value="<?php echo $i; ?>" required>
                                <label for="kindness-star<?php echo $i; ?>-<?php echo $uniqueId1; ?>" class="star-label" data-rating="<?php echo $i; ?>">
                                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
                                    </svg>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="internet-rating-item">
                        <label class="internet-review-form-label">
                            <span class="label-icon">⚡</span>
                            <span class="label-text">설치 빨라요</span>
                        </label>
                        <div class="internet-star-rating" data-rating-type="speed">
                            <?php 
                            $uniqueId2 = uniqid('internet-speed-');
                            for ($i = 5; $i >= 1; $i--): 
                            ?>
                                <input type="radio" id="speed-star<?php echo $i; ?>-<?php echo $uniqueId2; ?>" name="speed_rating" value="<?php echo $i; ?>" required>
                                <label for="speed-star<?php echo $i; ?>-<?php echo $uniqueId2; ?>" class="star-label" data-rating="<?php echo $i; ?>">
                                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
                                    </svg>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <div class="internet-review-form-group">
                    <label for="internetReviewText" class="internet-review-form-label">
                        <span class="label-icon">✍️</span>
                        <span class="label-text">리뷰 내용</span>
                    </label>
                    <textarea 
                        id="internetReviewText" 
                        name="reviewText" 
                        class="internet-review-textarea" 
                        placeholder="서비스 이용 경험을 자세히 작성해주세요. 다른 고객들에게 도움이 됩니다."
                        rows="6"
                        maxlength="1000"
                        required
                    ></textarea>
                    <div class="textarea-counter">
                        <span id="reviewTextCounter">0</span> / 1000자
                    </div>
                </div>
                <div class="internet-review-modal-footer">
                    <button type="button" class="internet-review-btn-cancel">취소</button>
                    <button type="submit" class="internet-review-btn-submit">
                        <span>작성하기</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* 인터넷 리뷰 모달 스타일 */
.internet-review-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.3s;
    overflow-y: auto;
    padding: 0;
}

.internet-review-modal[style*="display: block"],
.internet-review-modal.show {
    opacity: 1;
    visibility: visible;
}

.internet-review-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 10000;
}

.internet-review-modal-content {
    position: relative;
    background: #ffffff;
    border-radius: 0;
    width: 100%;
    max-width: 100%;
    max-height: 100vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(0, 0, 0, 0.05);
    transform: scale(0.95) translateY(20px);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    z-index: 10001;
    margin: auto;
}

@media (min-width: 641px) {
    .internet-review-modal {
        padding: 20px;
    }
    
    .internet-review-modal-content {
        border-radius: 24px;
        width: 90%;
        max-width: 700px;
        max-height: calc(100vh - 40px);
    }
}

.internet-review-modal.show .internet-review-modal-content {
    transform: scale(1) translateY(0);
}

.internet-review-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 28px 32px 24px;
    border-bottom: 1px solid #f1f5f9;
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
}

.internet-review-modal-header-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.internet-review-modal-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.internet-review-modal-title {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    letter-spacing: -0.02em;
}

.internet-review-modal-close {
    background: #f1f5f9;
    border: none;
    cursor: pointer;
    padding: 10px;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    width: 40px;
    height: 40px;
}

.internet-review-modal-close:hover {
    background: #e2e8f0;
    color: #1e293b;
    transform: rotate(90deg);
}

.internet-review-modal-body {
    padding: 32px;
    overflow-y: auto;
    flex: 1;
}

.internet-review-form-group {
    margin-bottom: 32px;
}

.internet-rating-group-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 0;
}

.internet-rating-item {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.internet-review-form-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 0;
}

.label-icon {
    font-size: 20px;
    line-height: 1;
}

.label-text {
    letter-spacing: -0.01em;
}

.internet-star-rating {
    display: flex;
    gap: 8px;
    justify-content: center;
    align-items: center;
}

.internet-star-rating input[type="radio"] {
    display: none;
}

.internet-star-rating .star-label {
    cursor: pointer;
    color: #e2e8f0;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    padding: 4px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* 기본 별 색상 */
.internet-star-rating .star-label {
    color: #e2e8f0;
}

/* 호버 효과 (확대) */
.internet-star-rating .star-label:hover {
    transform: scale(1.15);
    background: rgba(251, 191, 36, 0.1);
}

/* 호버된 별과 그 이전 별들 모두 노란색으로 (JavaScript로 처리) */
.internet-star-rating .star-label.hover-active {
    color: #fbbf24 !important;
}

/* 선택된 별과 그 이전 별들 모두 노란색 (인덱스 기반) */
.internet-star-rating .star-label.active {
    color: #fbbf24 !important;
}

/* active 클래스가 없는 별은 회색 */
.internet-star-rating .star-label:not(.active) {
    color: #e2e8f0;
}

.internet-review-textarea {
    width: 100%;
    padding: 16px 20px;
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    font-size: 15px;
    font-family: inherit;
    color: #1e293b;
    background: #f8fafc;
    resize: vertical;
    min-height: 140px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    line-height: 1.6;
}

.internet-review-textarea:focus {
    outline: none;
    border-color: #6366f1;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.internet-review-textarea::placeholder {
    color: #94a3b8;
}

.textarea-counter {
    display: flex;
    justify-content: flex-end;
    margin-top: 8px;
    font-size: 13px;
    color: #64748b;
}

.textarea-counter span {
    font-weight: 600;
    color: #6366f1;
}

.internet-review-modal-footer {
    display: flex;
    gap: 12px;
    padding-top: 24px;
    border-top: 1px solid #f1f5f9;
    margin-top: 8px;
}

.internet-review-btn-cancel,
.internet-review-btn-submit {
    flex: 1;
    padding: 16px 24px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: none;
}

.internet-review-btn-cancel {
    background: #f1f5f9;
    color: #64748b;
}

.internet-review-btn-cancel:hover {
    background: #e2e8f0;
    color: #475569;
    transform: translateY(-1px);
}

.internet-review-btn-submit {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
}

.internet-review-btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.5);
}

.internet-review-btn-submit:active {
    transform: translateY(0);
}

.internet-review-btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.internet-review-btn-submit svg {
    transition: transform 0.2s;
}

.internet-review-btn-submit:hover svg {
    transform: translateX(2px);
}

/* 반응형 디자인 */
@media (max-width: 640px) {
    .internet-review-modal {
        padding: 0;
    }
    
    .internet-review-modal-content {
        width: 100%;
        max-width: 100%;
        border-radius: 0;
        max-height: 100vh;
    }
}
    
    .internet-review-modal-header {
        padding: 24px 20px 20px;
    }
    
    .internet-review-modal-body {
        padding: 24px 20px;
    }
    
    .internet-rating-group-row {
        grid-template-columns: 1fr;
        gap: 28px;
    }
    
    .internet-review-modal-title {
        font-size: 20px;
    }
    
    .internet-star-rating {
        gap: 6px;
    }
    
    .internet-star-rating .star-label svg {
        width: 32px;
        height: 32px;
    }
}

/* 애니메이션 */
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        transform: translateY(30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.internet-review-modal.show .internet-review-modal-content {
    animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>

<script>
// 리뷰 모달 열기/닫기 애니메이션
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('internetReviewModal');
    const modalOverlay = modal ? modal.querySelector('.internet-review-modal-overlay') : null;
    const modalClose = modal ? modal.querySelector('.internet-review-modal-close') : null;
    const cancelBtn = document.querySelector('.internet-review-btn-cancel');
    const reviewTextarea = document.getElementById('internetReviewText');
    const reviewTextCounter = document.getElementById('reviewTextCounter');
    
    // 모달 열기 함수
    function openReviewModal() {
        if (modal) {
            // 현재 스크롤 위치 저장
            const scrollY = window.scrollY;
            document.body.style.position = 'fixed';
            document.body.style.top = `-${scrollY}px`;
            document.body.style.width = '100%';
            document.body.style.overflow = 'hidden';
            
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
        }
    }
    
    // 모달 닫기 함수
    function closeReviewModal() {
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                
                // 스크롤 위치 복원
                const scrollY = document.body.style.top;
                document.body.style.position = '';
                document.body.style.top = '';
                document.body.style.width = '';
                document.body.style.overflow = '';
                if (scrollY) {
                    window.scrollTo(0, parseInt(scrollY || '0') * -1);
                }
                
                // 폼 초기화
                const form = document.getElementById('internetReviewForm');
                if (form) {
                    form.reset();
                    // 별점 초기화
                    const starLabels = form.querySelectorAll('.star-label');
                    starLabels.forEach(label => {
                        label.classList.remove('active');
                        label.classList.remove('hover-active');
                    });
                }
                if (reviewTextCounter) {
                    reviewTextCounter.textContent = '0';
                }
            }, 300);
        }
    }
    
    // 닫기 이벤트
    if (modalOverlay) {
        modalOverlay.addEventListener('click', closeReviewModal);
    }
    
    if (modalClose) {
        modalClose.addEventListener('click', closeReviewModal);
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeReviewModal);
    }
    
    // ESC 키로 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('show')) {
            closeReviewModal();
        }
    });
    
    // 텍스트 카운터
    if (reviewTextarea && reviewTextCounter) {
        reviewTextarea.addEventListener('input', function() {
            const length = this.value.length;
            reviewTextCounter.textContent = length;
            if (length > 1000) {
                reviewTextCounter.style.color = '#ef4444';
            } else {
                reviewTextCounter.style.color = '#6366f1';
            }
        });
    }
    
    // 별점 호버 및 클릭 이벤트
    const starRatings = document.querySelectorAll('.internet-star-rating');
    
    starRatings.forEach(ratingGroup => {
        const starLabels = Array.from(ratingGroup.querySelectorAll('.star-label'));
        const ratingType = ratingGroup.getAttribute('data-rating-type');
        
        starLabels.forEach((label, index) => {
            // 호버 이벤트: 왼쪽부터 해당 별까지 모든 별 하이라이트
            // index는 0부터 시작 (0=왼쪽 첫번째, 4=왼쪽 다섯번째)
            label.addEventListener('mouseenter', function() {
                const position = index + 1; // 왼쪽에서 몇 번째 별인지 (1~5)
                const sameTypeLabels = Array.from(ratingGroup.querySelectorAll('.star-label'));
                
                sameTypeLabels.forEach((l, idx) => {
                    if (idx <= index) {
                        // 왼쪽부터 현재 별까지 활성화 (idx <= index)
                        l.classList.add('hover-active');
                    } else {
                        l.classList.remove('hover-active');
                    }
                });
            });
            
            // 마우스가 벗어날 때 호버 효과 제거 (선택된 별은 유지)
            ratingGroup.addEventListener('mouseleave', function() {
                const sameTypeLabels = ratingGroup.querySelectorAll('.star-label');
                sameTypeLabels.forEach(l => {
                    l.classList.remove('hover-active');
                });
            });
            
            // 클릭 이벤트: 왼쪽부터 해당 별까지 모든 별 활성화
            label.addEventListener('click', function() {
                const position = index + 1; // 왼쪽에서 몇 번째 별인지 (1~5)
                const rating = parseInt(this.getAttribute('data-rating')); // 실제 별점 값 (5~1)
                const radioInput = this.previousElementSibling;
                
                if (radioInput) {
                    radioInput.checked = true;
                }
                
                // 같은 타입의 별점 업데이트 (왼쪽부터 현재 별까지 활성화)
                const sameTypeLabels = Array.from(ratingGroup.querySelectorAll('.star-label'));
                sameTypeLabels.forEach((l, idx) => {
                    if (idx <= index) {
                        // 왼쪽부터 현재 별까지 활성화 (idx <= index)
                        l.classList.add('active');
                    } else {
                        l.classList.remove('active');
                    }
                });
            });
        });
    });
});
</script>


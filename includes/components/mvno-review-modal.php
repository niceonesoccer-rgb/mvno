<?php
/**
 * 요금제 리뷰 작성 모달 컴포넌트
 */
?>

<!-- 리뷰 작성 모달 -->
<div class="mvno-review-modal" id="mvnoReviewModal" style="display: none;">
    <div class="mvno-review-modal-overlay"></div>
    <div class="mvno-review-modal-content">
        <div class="mvno-review-modal-header">
            <h3 class="mvno-review-modal-title">리뷰 작성</h3>
            <button type="button" class="mvno-review-modal-close" aria-label="닫기">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="mvno-review-modal-body">
            <form id="mvnoReviewForm">
                <div class="mvno-review-form-group">
                    <label for="reviewText" class="mvno-review-form-label">리뷰 내용</label>
                    <textarea 
                        id="reviewText" 
                        name="reviewText" 
                        class="mvno-review-textarea" 
                        placeholder="리뷰를 작성해주세요."
                        rows="8"
                        required
                    ></textarea>
                </div>
                <div class="mvno-review-modal-footer">
                    <button type="button" class="mvno-review-btn-cancel">취소</button>
                    <button type="submit" class="mvno-review-btn-submit">작성하기</button>
                </div>
            </form>
        </div>
    </div>
</div>


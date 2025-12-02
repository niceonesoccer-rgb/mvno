<?php
/**
 * 통신사폰 리뷰 작성 모달 컴포넌트
 */
?>

<!-- 리뷰 작성 모달 -->
<div class="mno-review-modal" id="mnoReviewModal" style="display: none;">
    <div class="mno-review-modal-overlay"></div>
    <div class="mno-review-modal-content">
        <div class="mno-review-modal-header">
            <h3 class="mno-review-modal-title">리뷰 작성</h3>
            <button type="button" class="mno-review-modal-close" aria-label="닫기">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="mno-review-modal-body">
            <form id="mnoReviewForm">
                <div class="mno-review-form-group">
                    <label for="reviewText" class="mno-review-form-label">리뷰 내용</label>
                    <textarea 
                        id="reviewText" 
                        name="reviewText" 
                        class="mno-review-textarea" 
                        placeholder="리뷰를 작성해주세요."
                        rows="8"
                        required
                    ></textarea>
                </div>
                <div class="mno-review-modal-footer">
                    <button type="button" class="mno-review-btn-cancel">취소</button>
                    <button type="submit" class="mno-review-btn-submit">작성하기</button>
                </div>
            </form>
        </div>
    </div>
</div>


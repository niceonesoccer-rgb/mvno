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
            <h3 class="internet-review-modal-title">리뷰 작성</h3>
            <button type="button" class="internet-review-modal-close" aria-label="닫기">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="internet-review-modal-body">
            <form id="internetReviewForm">
                <div class="internet-review-form-group internet-rating-group-row">
                    <div class="internet-rating-item">
                        <label class="internet-review-form-label">친절해요</label>
                        <div class="internet-star-rating" data-rating-type="kindness">
                            <?php 
                            $uniqueId1 = uniqid('internet-kindness-');
                            for ($i = 5; $i >= 1; $i--): 
                            ?>
                                <input type="radio" id="kindness-star<?php echo $i; ?>-<?php echo $uniqueId1; ?>" name="kindness_rating" value="<?php echo $i; ?>" required>
                                <label for="kindness-star<?php echo $i; ?>-<?php echo $uniqueId1; ?>" class="star-label" data-rating="<?php echo $i; ?>">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
                                    </svg>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="internet-rating-item">
                        <label class="internet-review-form-label">설치 빨라요</label>
                        <div class="internet-star-rating" data-rating-type="speed">
                            <?php 
                            $uniqueId2 = uniqid('internet-speed-');
                            for ($i = 5; $i >= 1; $i--): 
                            ?>
                                <input type="radio" id="speed-star<?php echo $i; ?>-<?php echo $uniqueId2; ?>" name="speed_rating" value="<?php echo $i; ?>" required>
                                <label for="speed-star<?php echo $i; ?>-<?php echo $uniqueId2; ?>" class="star-label" data-rating="<?php echo $i; ?>">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
                                    </svg>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <div class="internet-review-form-group">
                    <label for="internetReviewText" class="internet-review-form-label">리뷰 내용</label>
                    <textarea 
                        id="internetReviewText" 
                        name="reviewText" 
                        class="internet-review-textarea" 
                        placeholder="리뷰를 작성해주세요."
                        rows="8"
                        required
                    ></textarea>
                </div>
                <div class="internet-review-modal-footer">
                    <button type="button" class="internet-review-btn-cancel">취소</button>
                    <button type="submit" class="internet-review-btn-submit">작성하기</button>
                </div>
            </form>
        </div>
    </div>
</div>


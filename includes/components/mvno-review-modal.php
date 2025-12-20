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
                <div class="mvno-review-form-group mvno-rating-group-row">
                    <div class="mvno-rating-item">
                        <label class="mvno-review-form-label">친절해요</label>
                        <div class="mvno-star-rating" data-rating-type="kindness">
                            <?php 
                            $uniqueId1 = uniqid('mvno-kindness-');
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
                    <div class="mvno-rating-item">
                        <label class="mvno-review-form-label">개통 빨라요</label>
                        <div class="mvno-star-rating" data-rating-type="speed">
                            <?php 
                            $uniqueId2 = uniqid('mvno-speed-');
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
                    <div style="display: flex; gap: 12px; width: 100%;">
                        <!-- 삭제 버튼 (수정 모드일 때만 표시) -->
                        <button type="button" class="mvno-review-btn-delete" id="mvnoReviewDeleteBtn" style="display: none;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 6H5H21M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10 11V17M14 11V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>삭제</span>
                        </button>
                        <div style="flex: 1;"></div>
                        <button type="button" class="mvno-review-btn-cancel">취소</button>
                        <button type="submit" class="mvno-review-btn-submit">작성하기</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


<?php
/**
 * 주문 페이지 리뷰 작성/수정 모달 컴포넌트 (공통)
 * 
 * @param string $prefix 클래스명 및 ID prefix (예: 'internet', 'mno', 'mvno')
 * @param string $speedLabel 속도 관련 라벨 (예: '설치 빨라요', '개통 빨라요')
 * @param string $formId 폼 ID (기본값: '{prefix}ReviewForm')
 * @param string $modalId 모달 ID (기본값: '{prefix}ReviewModal')
 * @param string $textareaId 텍스트 영역 ID (기본값: '{prefix}ReviewText' 또는 'reviewText')
 */
$prefix = $prefix ?? 'order';
$speedLabel = $speedLabel ?? '개통 빨라요';
$formId = $formId ?? $prefix . 'ReviewForm';
$modalId = $modalId ?? $prefix . 'ReviewModal';
$textareaId = $textareaId ?? ($prefix === 'internet' ? $prefix . 'ReviewText' : 'reviewText');
?>

<!-- 리뷰 작성 모달 -->
<div class="<?php echo htmlspecialchars($prefix); ?>-review-modal" id="<?php echo htmlspecialchars($modalId); ?>" style="display: none;">
    <div class="<?php echo htmlspecialchars($prefix); ?>-review-modal-overlay"></div>
    <div class="<?php echo htmlspecialchars($prefix); ?>-review-modal-content">
        <div class="<?php echo htmlspecialchars($prefix); ?>-review-modal-header">
            <h3 class="<?php echo htmlspecialchars($prefix); ?>-review-modal-title">리뷰 작성</h3>
            <button type="button" class="<?php echo htmlspecialchars($prefix); ?>-review-modal-close" aria-label="닫기">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="<?php echo htmlspecialchars($prefix); ?>-review-modal-body">
            <form id="<?php echo htmlspecialchars($formId); ?>">
                <div class="<?php echo htmlspecialchars($prefix); ?>-review-form-group <?php echo htmlspecialchars($prefix); ?>-rating-group-row">
                    <div class="<?php echo htmlspecialchars($prefix); ?>-rating-item">
                        <label class="<?php echo htmlspecialchars($prefix); ?>-review-form-label">친절해요</label>
                        <div class="<?php echo htmlspecialchars($prefix); ?>-star-rating" data-rating-type="kindness">
                            <?php 
                            $uniqueId1 = uniqid($prefix . '-kindness-');
                            for ($i = 1; $i <= 5; $i++): 
                            ?>
                                <input type="radio" id="kindness-star<?php echo $i; ?>-<?php echo $uniqueId1; ?>" name="kindness_rating" value="<?php echo $i; ?>" class="hidden-radio">
                                <label for="kindness-star<?php echo $i; ?>-<?php echo $uniqueId1; ?>" class="<?php echo htmlspecialchars($prefix); ?>-star-label" data-rating="<?php echo $i; ?>">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
                                    </svg>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="<?php echo htmlspecialchars($prefix); ?>-rating-item <?php echo htmlspecialchars($prefix); ?>-rating-item-spaced">
                        <label class="<?php echo htmlspecialchars($prefix); ?>-review-form-label"><?php echo htmlspecialchars($speedLabel); ?></label>
                        <div class="<?php echo htmlspecialchars($prefix); ?>-star-rating" data-rating-type="speed">
                            <?php 
                            $uniqueId2 = uniqid($prefix . '-speed-');
                            for ($i = 1; $i <= 5; $i++): 
                            ?>
                                <input type="radio" id="speed-star<?php echo $i; ?>-<?php echo $uniqueId2; ?>" name="speed_rating" value="<?php echo $i; ?>" class="hidden-radio">
                                <label for="speed-star<?php echo $i; ?>-<?php echo $uniqueId2; ?>" class="<?php echo htmlspecialchars($prefix); ?>-star-label" data-rating="<?php echo $i; ?>">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
                                    </svg>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <div class="<?php echo htmlspecialchars($prefix); ?>-review-form-group">
                    <label for="<?php echo htmlspecialchars($textareaId); ?>" class="<?php echo htmlspecialchars($prefix); ?>-review-form-label">리뷰 내용</label>
                    <textarea 
                        id="<?php echo htmlspecialchars($textareaId); ?>" 
                        name="reviewText" 
                        class="<?php echo htmlspecialchars($prefix); ?>-review-textarea" 
                        placeholder="리뷰를 작성해주세요."
                        rows="10"
                        maxlength="300"
                        required
                    ></textarea>
                    <div class="textarea-counter">
                        <span id="<?php echo htmlspecialchars($textareaId); ?>Counter">0</span> / 300자
                    </div>
                </div>
                <div class="<?php echo htmlspecialchars($prefix); ?>-review-modal-footer">
                    <button type="button" class="<?php echo htmlspecialchars($prefix); ?>-review-btn-cancel">취소</button>
                    <button type="submit" class="<?php echo htmlspecialchars($prefix); ?>-review-btn-submit">작성하기</button>
                </div>
            </form>
        </div>
    </div>
</div>


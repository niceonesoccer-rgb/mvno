<?php
/**
 * 주문 페이지 리뷰 작성 완료 모달 컴포넌트 (공통)
 * 
 * @param string $prefix 클래스명 및 ID prefix (예: 'internet', 'mno', 'mvno')
 * @param string $modalId 모달 ID (기본값: '{prefix}ReviewSuccessModal')
 */
$prefix = $prefix ?? 'order';
$modalId = $modalId ?? $prefix . 'ReviewSuccessModal';
?>

<!-- 리뷰 작성 완료 모달 -->
<div class="<?php echo htmlspecialchars($prefix); ?>-review-success-modal" id="<?php echo htmlspecialchars($modalId); ?>" style="display: none;">
    <div class="<?php echo htmlspecialchars($prefix); ?>-review-success-modal-overlay"></div>
    <div class="<?php echo htmlspecialchars($prefix); ?>-review-success-modal-content">
        <div class="<?php echo htmlspecialchars($prefix); ?>-review-success-modal-body">
            <div class="<?php echo htmlspecialchars($prefix); ?>-review-success-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" fill="#40C057" opacity="0.1"/>
                    <path d="M9 12L11 14L15 10" stroke="#40C057" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h3 class="<?php echo htmlspecialchars($prefix); ?>-review-success-title">리뷰가 작성되었습니다</h3>
            <p class="<?php echo htmlspecialchars($prefix); ?>-review-success-message">소중한 리뷰 감사합니다.</p>
        </div>
        <div class="<?php echo htmlspecialchars($prefix); ?>-review-success-modal-footer">
            <button type="button" class="<?php echo htmlspecialchars($prefix); ?>-review-success-btn-confirm">확인</button>
        </div>
    </div>
</div>















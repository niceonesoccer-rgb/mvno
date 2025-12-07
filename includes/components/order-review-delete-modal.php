<?php
/**
 * 주문 페이지 리뷰 삭제 확인 모달 컴포넌트 (공통)
 * 
 * @param string $prefix 클래스명 및 ID prefix (예: 'internet', 'mno', 'mvno')
 * @param string $modalId 모달 ID (기본값: '{prefix}ReviewDeleteModal')
 */
$prefix = $prefix ?? 'order';
$modalId = $modalId ?? $prefix . 'ReviewDeleteModal';
?>

<!-- 리뷰 삭제 확인 모달 -->
<div class="<?php echo htmlspecialchars($prefix); ?>-review-delete-modal" id="<?php echo htmlspecialchars($modalId); ?>" style="display: none;">
    <div class="<?php echo htmlspecialchars($prefix); ?>-review-delete-modal-overlay"></div>
    <div class="<?php echo htmlspecialchars($prefix); ?>-review-delete-modal-content">
        <div class="<?php echo htmlspecialchars($prefix); ?>-review-delete-modal-header">
            <h3 class="<?php echo htmlspecialchars($prefix); ?>-review-delete-modal-title">리뷰 삭제</h3>
            <button type="button" class="<?php echo htmlspecialchars($prefix); ?>-review-delete-modal-close" aria-label="닫기">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="<?php echo htmlspecialchars($prefix); ?>-review-delete-modal-body">
            <p class="<?php echo htmlspecialchars($prefix); ?>-review-delete-message">정말 리뷰를 삭제하시겠습니까?</p>
            <p class="<?php echo htmlspecialchars($prefix); ?>-review-delete-submessage">삭제된 리뷰는 복구할 수 없습니다.</p>
        </div>
        <div class="<?php echo htmlspecialchars($prefix); ?>-review-delete-modal-footer">
            <button type="button" class="<?php echo htmlspecialchars($prefix); ?>-review-delete-btn-cancel">취소</button>
            <button type="button" class="<?php echo htmlspecialchars($prefix); ?>-review-delete-btn-confirm">삭제</button>
        </div>
    </div>
</div>













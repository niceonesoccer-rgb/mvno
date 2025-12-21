<?php
/**
 * 인터넷 리뷰 삭제 확인 모달 컴포넌트
 */
?>

<!-- 리뷰 삭제 확인 모달 -->
<div class="internet-review-delete-modal" id="internetReviewDeleteModal" style="display: none;">
    <div class="internet-review-delete-modal-overlay"></div>
    <div class="internet-review-delete-modal-content">
        <div class="internet-review-delete-modal-header">
            <h3 class="internet-review-delete-modal-title">리뷰 삭제</h3>
            <button type="button" class="internet-review-delete-modal-close" aria-label="닫기">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="internet-review-delete-modal-body">
            <p class="internet-review-delete-message">정말 리뷰를 삭제하시겠습니까?</p>
            <p class="internet-review-delete-submessage">삭제된 리뷰는 복구할 수 없습니다.</p>
        </div>
        <div class="internet-review-delete-modal-footer">
            <button type="button" class="internet-review-delete-btn-cancel">취소</button>
            <button type="button" class="internet-review-delete-btn-confirm">삭제</button>
        </div>
    </div>
</div>

<style>
/* 인터넷 리뷰 삭제 모달 스타일 */
.internet-review-delete-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10001;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.3s;
    padding: 20px;
}

.internet-review-delete-modal.show,
.internet-review-delete-modal[style*="display: flex"] {
    opacity: 1;
    visibility: visible;
}

.internet-review-delete-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 10001;
}

.internet-review-delete-modal-content {
    position: relative;
    background: #ffffff;
    border-radius: 20px;
    width: 100%;
    max-width: 400px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    transform: scale(0.95) translateY(20px);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    z-index: 10002;
}

.internet-review-delete-modal.show .internet-review-delete-modal-content {
    transform: scale(1) translateY(0);
}

.internet-review-delete-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px 24px 20px;
    border-bottom: 1px solid #f1f5f9;
}

.internet-review-delete-modal-title {
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    letter-spacing: -0.02em;
}

.internet-review-delete-modal-close {
    background: #f1f5f9;
    border: none;
    cursor: pointer;
    padding: 8px;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    width: 32px;
    height: 32px;
}

.internet-review-delete-modal-close:hover {
    background: #e2e8f0;
    color: #1e293b;
}

.internet-review-delete-modal-body {
    padding: 24px;
    text-align: center;
}

.internet-review-delete-message {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 8px 0;
}

.internet-review-delete-submessage {
    font-size: 14px;
    color: #64748b;
    margin: 0;
}

.internet-review-delete-modal-footer {
    display: flex;
    gap: 12px;
    padding: 20px 24px 24px;
    border-top: 1px solid #f1f5f9;
}

.internet-review-delete-btn-cancel,
.internet-review-delete-btn-confirm {
    flex: 1;
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
}

.internet-review-delete-btn-cancel {
    background: #f1f5f9;
    color: #64748b;
}

.internet-review-delete-btn-cancel:hover {
    background: #e2e8f0;
    color: #475569;
}

.internet-review-delete-btn-confirm {
    background: #ef4444;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.internet-review-delete-btn-confirm:hover {
    background: #dc2626;
    box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
    transform: translateY(-1px);
}

.internet-review-delete-btn-confirm:active {
    transform: translateY(0);
}

.internet-review-delete-btn-confirm:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* 반응형 */
@media (max-width: 640px) {
    .internet-review-delete-modal {
        padding: 0;
    }
    
    .internet-review-delete-modal-content {
        border-radius: 0;
        max-width: 100%;
    }
}
</style>


























/**
 * 공통 모달 컴포넌트
 * alert, confirm을 모달로 대체
 */

// 모달 HTML 생성
function createModalHTML() {
    if (document.getElementById('common-modal')) {
        return; // 이미 존재하면 생성하지 않음
    }
    
    const modalHTML = `
        <div id="common-modal" class="common-modal" style="display: none;">
            <div class="common-modal-overlay"></div>
            <div class="common-modal-content">
                <div class="common-modal-header">
                    <h3 class="common-modal-title" id="modal-title">알림</h3>
                    <button class="common-modal-close" id="modal-close">&times;</button>
                </div>
                <div class="common-modal-body" id="modal-body">
                    <p id="modal-message"></p>
                </div>
                <div class="common-modal-footer" id="modal-footer">
                    <button class="common-modal-btn common-modal-btn-primary" id="modal-confirm">확인</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // 스타일 추가
    if (!document.getElementById('common-modal-styles')) {
        const style = document.createElement('style');
        style.id = 'common-modal-styles';
        style.textContent = `
            .common-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                animation: fadeIn 0.2s ease-out;
            }
            
            .common-modal-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(4px);
            }
            
            .common-modal-content {
                position: relative;
                background: white;
                border-radius: 12px;
                width: 90%;
                max-width: 400px;
                max-height: 90vh;
                overflow: hidden;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                animation: slideUp 0.3s ease-out;
                z-index: 10001;
            }
            
            .common-modal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 20px 24px;
                border-bottom: 1px solid #e5e7eb;
            }
            
            .common-modal-title {
                font-size: 20px;
                font-weight: 700;
                color: #1f2937;
                margin: 0;
            }
            
            .common-modal-close {
                background: none;
                border: none;
                font-size: 28px;
                color: #9ca3af;
                cursor: pointer;
                padding: 0;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 6px;
                transition: all 0.2s;
            }
            
            .common-modal-close:hover {
                background: #f3f4f6;
                color: #374151;
            }
            
            .common-modal-body {
                padding: 24px;
                color: #374151;
                font-size: 15px;
                line-height: 1.6;
            }
            
            .common-modal-footer {
                display: flex;
                gap: 12px;
                padding: 16px 24px;
                border-top: 1px solid #e5e7eb;
                justify-content: flex-end;
            }
            
            .common-modal-btn {
                padding: 10px 20px;
                border: none;
                border-radius: 8px;
                font-size: 15px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
                min-width: 80px;
            }
            
            .common-modal-btn-primary {
                background: #6366f1;
                color: white;
            }
            
            .common-modal-btn-primary:hover {
                background: #4f46e5;
            }
            
            .common-modal-btn-secondary {
                background: #f3f4f6;
                color: #374151;
            }
            
            .common-modal-btn-secondary:hover {
                background: #e5e7eb;
            }
            
            .common-modal-btn-danger {
                background: #ef4444;
                color: white;
            }
            
            .common-modal-btn-danger:hover {
                background: #dc2626;
            }
            
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
                    transform: translateY(20px);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
            
            @media (max-width: 640px) {
                .common-modal-content {
                    width: 95%;
                    margin: 20px;
                }
                
                .common-modal-header {
                    padding: 16px 20px;
                }
                
                .common-modal-body {
                    padding: 20px;
                }
                
                .common-modal-footer {
                    padding: 12px 20px;
                }
            }
        `;
        document.head.appendChild(style);
    }
}

// 모달 초기화
function initModal() {
    createModalHTML();
    
    const modal = document.getElementById('common-modal');
    const closeBtn = document.getElementById('modal-close');
    const overlay = modal.querySelector('.common-modal-overlay');
    
    // 닫기 이벤트
    const closeModal = () => {
        modal.style.display = 'none';
    };
    
    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);
    
    // ESC 키로 닫기
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display !== 'none') {
            closeModal();
        }
    });
}

// Alert 모달
function showAlert(message, title = '알림') {
    return new Promise((resolve) => {
        initModal();
        
        const modal = document.getElementById('common-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalMessage = document.getElementById('modal-message');
        const modalFooter = document.getElementById('modal-footer');
        const confirmBtn = document.getElementById('modal-confirm');
        
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        
        modalFooter.innerHTML = `
            <button class="common-modal-btn common-modal-btn-primary" id="modal-confirm">확인</button>
        `;
        
        modal.style.display = 'flex';
        
        // 확인 버튼 클릭
        document.getElementById('modal-confirm').addEventListener('click', () => {
            modal.style.display = 'none';
            resolve(true);
        });
    });
}

// Confirm 모달
function showConfirm(message, title = '확인') {
    return new Promise((resolve) => {
        initModal();
        
        const modal = document.getElementById('common-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalMessage = document.getElementById('modal-message');
        const modalFooter = document.getElementById('modal-footer');
        
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        
        modalFooter.innerHTML = `
            <button class="common-modal-btn common-modal-btn-secondary" id="modal-cancel">취소</button>
            <button class="common-modal-btn common-modal-btn-primary" id="modal-confirm">확인</button>
        `;
        
        modal.style.display = 'flex';
        
        // 취소 버튼
        document.getElementById('modal-cancel').addEventListener('click', () => {
            modal.style.display = 'none';
            resolve(false);
        });
        
        // 확인 버튼
        document.getElementById('modal-confirm').addEventListener('click', () => {
            modal.style.display = 'none';
            resolve(true);
        });
    });
}

// 전역 함수로 등록 (기존 alert, confirm 대체)
window.showAlert = showAlert;
window.showConfirm = showConfirm;

// 페이지 로드 시 초기화
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initModal);
} else {
    initModal();
}












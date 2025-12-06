<?php
/**
 * 포인트 사용 모달 컴포넌트
 * 신청하기 버튼 클릭 시 포인트 사용 모달 표시
 * 
 * @param string $type 신청 타입 ('mvno', 'mno', 'internet')
 * @param int $item_id 아이템 ID
 * @param string $item_name 아이템 이름
 */
if (!isset($type)) {
    $type = 'mvno';
}
if (!isset($item_id)) {
    $item_id = 0;
}
if (!isset($item_name)) {
    $item_name = '';
}

// 포인트 설정 로드
require_once __DIR__ . '/../data/point-settings.php';

// 현재 사용자 포인트 조회
$user_id = 'default'; // 실제로는 세션에서 가져옴
$user_point = getUserPoint($user_id);
$current_balance = $user_point['balance'] ?? 0;
$max_usable = $point_settings['max_usable_point'];

// 기본 차감 포인트 설정
$default_point = 0;
switch ($type) {
    case 'mvno':
        $default_point = $point_settings['mvno_application_point'];
        break;
    case 'mno':
        $default_point = $point_settings['mno_application_point'];
        break;
    case 'internet':
        $default_point = $point_settings['internet_application_point'];
        break;
}

$modal_id = "pointUsageModal_{$type}_{$item_id}";
?>

<!-- 포인트 사용 모달 -->
<div id="<?php echo $modal_id; ?>" class="point-usage-modal" style="display: none;">
    <div class="point-usage-modal-overlay"></div>
    <div class="point-usage-modal-content">
        <div class="point-usage-modal-header">
            <h3 class="point-usage-modal-title">포인트 사용</h3>
            <button type="button" class="point-usage-modal-close" aria-label="닫기">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        
        <div class="point-usage-modal-body">
            <!-- 포인트 정보 -->
            <div class="point-info-section">
                <div class="point-balance-info">
                    <span class="point-balance-label">보유 포인트</span>
                    <span class="point-balance-value"><?php echo number_format($current_balance); ?>원</span>
                </div>
                <div class="point-max-info">
                    <span class="point-max-label">최대 사용 가능</span>
                    <span class="point-max-value"><?php echo number_format(min($current_balance, $max_usable)); ?>원</span>
                </div>
            </div>
            
            <!-- 안내 메시지 -->
            <div class="point-notice">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="#6366f1" stroke-width="2"/>
                    <path d="M12 8V12M12 16H12.01" stroke="#6366f1" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span><?php echo htmlspecialchars($point_settings['usage_message']); ?></span>
            </div>
            
            <!-- 포인트 입력 -->
            <div class="point-input-section">
                <label class="point-input-label">사용할 포인트</label>
                <div class="point-input-wrapper">
                    <input 
                        type="number" 
                        class="point-input" 
                        id="pointInput_<?php echo $modal_id; ?>"
                        min="0" 
                        max="<?php echo min($current_balance, $max_usable); ?>"
                        value="<?php echo $default_point; ?>"
                        placeholder="0"
                    >
                    <span class="point-input-unit">원</span>
                </div>
                <div class="point-quick-buttons">
                    <button type="button" class="point-quick-btn" data-point="0">0원</button>
                    <button type="button" class="point-quick-btn" data-point="<?php echo min($current_balance, $max_usable); ?>">전액 사용</button>
                </div>
            </div>
            
            <!-- 잔액 정보 -->
            <div class="point-balance-preview">
                <span class="point-balance-preview-label">사용 후 잔액</span>
                <span class="point-balance-preview-value" id="balancePreview_<?php echo $modal_id; ?>">
                    <?php echo number_format($current_balance - $default_point); ?>원
                </span>
            </div>
        </div>
        
        <div class="point-usage-modal-footer">
            <button type="button" class="point-usage-cancel-btn">취소</button>
            <button type="button" class="point-usage-confirm-btn" data-type="<?php echo $type; ?>" data-item-id="<?php echo $item_id; ?>">
                확인
            </button>
        </div>
    </div>
</div>

<style>
/* 포인트 사용 모달 스타일 */
.point-usage-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.point-usage-modal.show {
    display: flex;
}

.point-usage-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.point-usage-modal-content {
    position: relative;
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 400px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    z-index: 10001;
}

.point-usage-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
}

.point-usage-modal-title {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.point-usage-modal-close {
    background: none;
    border: none;
    padding: 4px;
    cursor: pointer;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.2s;
}

.point-usage-modal-close:hover {
    color: #374151;
}

.point-usage-modal-body {
    padding: 24px;
}

.point-info-section {
    display: flex;
    gap: 16px;
    margin-bottom: 20px;
    padding: 16px;
    background: #f9fafb;
    border-radius: 12px;
}

.point-balance-info,
.point-max-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.point-balance-label,
.point-max-label {
    font-size: 13px;
    color: #6b7280;
}

.point-balance-value,
.point-max-value {
    font-size: 18px;
    font-weight: 700;
    color: #6366f1;
}

.point-notice {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: #eef2ff;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    color: #4338ca;
}

.point-input-section {
    margin-bottom: 20px;
}

.point-input-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.point-input-wrapper {
    position: relative;
    margin-bottom: 12px;
}

.point-input {
    width: 100%;
    padding: 12px 48px 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    outline: none;
    transition: border-color 0.2s;
    box-sizing: border-box;
}

.point-input:focus {
    border-color: #6366f1;
}

.point-input-unit {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 14px;
    color: #6b7280;
    pointer-events: none;
}

.point-quick-buttons {
    display: flex;
    gap: 8px;
}

.point-quick-btn {
    flex: 1;
    padding: 8px 16px;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 13px;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s;
}

.point-quick-btn:hover {
    background: #e5e7eb;
    border-color: #d1d5db;
}

.point-balance-preview {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
}

.point-balance-preview-label {
    font-size: 14px;
    color: #6b7280;
}

.point-balance-preview-value {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
}

.point-usage-modal-footer {
    display: flex;
    gap: 12px;
    padding: 20px 24px;
    border-top: 1px solid #e5e7eb;
}

.point-usage-cancel-btn,
.point-usage-confirm-btn {
    flex: 1;
    padding: 14px 24px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.point-usage-cancel-btn {
    background: #f3f4f6;
    color: #374151;
}

.point-usage-cancel-btn:hover {
    background: #e5e7eb;
}

.point-usage-confirm-btn {
    background: #6366f1;
    color: white;
}

.point-usage-confirm-btn:hover {
    background: #4f46e5;
}

.point-usage-confirm-btn:disabled {
    background: #d1d5db;
    cursor: not-allowed;
}

@media (max-width: 640px) {
    .point-usage-modal-content {
        width: 95%;
        max-height: 85vh;
    }
    
    .point-info-section {
        flex-direction: column;
        gap: 12px;
    }
}
</style>

<script>
(function() {
    const modalId = '<?php echo $modal_id; ?>';
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    const overlay = modal.querySelector('.point-usage-modal-overlay');
    const closeBtn = modal.querySelector('.point-usage-modal-close');
    const cancelBtn = modal.querySelector('.point-usage-cancel-btn');
    const confirmBtn = modal.querySelector('.point-usage-confirm-btn');
    const pointInput = document.getElementById('pointInput_' + modalId);
    const balancePreview = document.getElementById('balancePreview_' + modalId);
    const quickBtns = modal.querySelectorAll('.point-quick-btn');
    
    const currentBalance = <?php echo $current_balance; ?>;
    const maxUsable = <?php echo min($current_balance, $max_usable); ?>;
    
    // 모달 열기 (전역 함수)
    window.openPointUsageModal = function(type, itemId) {
        // 해당 타입과 아이템 ID에 맞는 모달 찾기
        const targetModalId = `pointUsageModal_${type}_${itemId}`;
        const targetModal = document.getElementById(targetModalId);
        
        if (targetModal) {
            targetModal.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // 포인트 잔액 최신화
            fetch('/MVNO/api/point-balance.php?user_id=default')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const balanceValue = targetModal.querySelector('.point-balance-value');
                        const maxValue = targetModal.querySelector('.point-max-value');
                        const pointInput = targetModal.querySelector('.point-input');
                        
                        if (balanceValue) {
                            balanceValue.textContent = formatNumber(data.balance) + '원';
                        }
                        if (maxValue) {
                            const maxUsable = Math.min(data.balance, <?php echo $max_usable; ?>);
                            maxValue.textContent = formatNumber(maxUsable) + '원';
                            if (pointInput) {
                                pointInput.setAttribute('max', maxUsable);
                            }
                        }
                    }
                });
        }
    };
    
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    // 모달 닫기
    function closeModal() {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
    
    // 포인트 입력 변경 시 잔액 미리보기 업데이트
    function updateBalancePreview() {
        const inputValue = parseInt(pointInput.value) || 0;
        const usedPoint = Math.min(Math.max(0, inputValue), maxUsable);
        const remainingBalance = currentBalance - usedPoint;
        balancePreview.textContent = formatNumber(remainingBalance) + '원';
        
        // 입력값 제한
        if (inputValue > maxUsable) {
            pointInput.value = maxUsable;
        }
        if (inputValue < 0) {
            pointInput.value = 0;
        }
    }
    
    // 빠른 선택 버튼
    quickBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const point = parseInt(this.getAttribute('data-point')) || 0;
            pointInput.value = point;
            updateBalancePreview();
        });
    });
    
    // 입력 이벤트
    pointInput.addEventListener('input', updateBalancePreview);
    
    // 확인 버튼 클릭
    confirmBtn.addEventListener('click', function() {
        const usedPoint = parseInt(pointInput.value) || 0;
        
        if (usedPoint < 0 || usedPoint > maxUsable) {
            showAlert('사용 가능한 포인트 범위를 벗어났습니다.');
            return;
        }
        
        const type = this.getAttribute('data-type');
        const itemId = this.getAttribute('data-item-id');
        
        // 포인트 사용 정보를 전역 변수에 저장 (기존 신청 모달로 전달)
        window.pointUsageData = {
            type: type,
            itemId: itemId,
            usedPoint: usedPoint
        };
        
        // 모달 닫기
        closeModal();
        
        // 기존 신청 모달 열기 (이벤트 발생)
        const event = new CustomEvent('pointUsageConfirmed', {
            detail: {
                type: type,
                itemId: itemId,
                usedPoint: usedPoint
            }
        });
        document.dispatchEvent(event);
    });
    
    // 닫기 이벤트
    if (overlay) overlay.addEventListener('click', closeModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    
    // ESC 키로 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('show')) {
            closeModal();
        }
    });
})();
</script>


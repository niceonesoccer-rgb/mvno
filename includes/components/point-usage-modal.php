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
if (!isset($point_settings)) {
    require_once __DIR__ . '/../data/point-settings.php';
}
require_once __DIR__ . '/../data/auth-functions.php';

// 포인트 설정이 없으면 기본값 사용
if (!isset($point_settings) || !is_array($point_settings)) {
    $point_settings = [
        'max_usable_point' => 50000,
        'mvno_application_point' => 1000,
        'mno_application_point' => 1000,
        'internet_application_point' => 1000,
        'usage_message' => '포인트를 사용하시면 개통 시 추가 할인을 받으실 수 있습니다.',
    ];
}

// 현재 사용자 포인트 조회
$currentUser = getCurrentUser();
$user_id = $currentUser['user_id'] ?? null;
$user_point = $user_id ? getUserPoint($user_id) : ['balance' => 0, 'history' => []];
$current_balance = $user_point['balance'] ?? 0;
$max_usable = $point_settings['max_usable_point'] ?? 50000;

// 최대 사용 가능 포인트 (1000 단위로 반올림)
$max_usable_point = min($current_balance, $max_usable);
$max_usable_point_rounded = floor($max_usable_point / 1000) * 1000; // 1000 단위로 내림

// 기본 차감 포인트 설정
$default_point = 0;
switch ($type) {
    case 'mvno':
        $default_point = $point_settings['mvno_application_point'] ?? 1000;
        break;
    case 'mno':
        $default_point = $point_settings['mno_application_point'] ?? 1000;
        break;
    case 'internet':
        $default_point = $point_settings['internet_application_point'] ?? 1000;
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
                    <span class="point-balance-value"><?php echo number_format($current_balance); ?>P</span>
                </div>
                <div class="point-max-info">
                    <span class="point-max-label">최대 사용 가능</span>
                    <span class="point-max-value" id="pointMaxValue_<?php echo $modal_id; ?>">-</span>
                </div>
            </div>
            
            <!-- 안내 메시지 -->
            <div class="point-notice">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="#6366f1" stroke-width="2"/>
                    <path d="M12 8V12M12 16H12.01" stroke="#6366f1" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span>포인트를 사용하시면 개통 시 추가 할인을 받으실 수 있습니다.</span>
            </div>
            
            <!-- 할인 혜택 내용 표시 영역 (동적으로 로드됨) -->
            <div id="pointBenefitSection_<?php echo $modal_id; ?>" class="point-benefit-section" style="display: none; background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <strong style="color: #065f46; font-size: 14px;">개통 시 혜택</strong>
                </div>
                <p id="pointBenefitText_<?php echo $modal_id; ?>" style="color: #047857; font-size: 14px; margin: 0; line-height: 1.6; white-space: pre-line;"></p>
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
                        step="1000"
                        max="<?php echo $current_balance; ?>"
                        value="<?php echo $default_point; ?>"
                        placeholder="0"
                    >
                    <span class="point-input-unit">포인트</span>
                </div>
                <div class="point-quick-buttons" id="pointQuickButtons_<?php echo $modal_id; ?>">
                    <!-- 동적으로 생성됨 -->
                </div>
                <div class="point-input-help" style="font-size: 12px; color: #6b7280; margin-top: 8px;">
                    1000포인트 단위로만 사용 가능합니다.
                </div>
            </div>
            
            <!-- 잔액 정보 -->
            <div class="point-balance-preview">
                <span class="point-balance-preview-label">사용 후 잔액</span>
                <span class="point-balance-preview-value" id="balancePreview_<?php echo $modal_id; ?>">
                    <?php echo number_format($current_balance - $default_point); ?>P
                </span>
            </div>
        </div>
        
        <div class="point-usage-modal-footer">
            <button type="button" class="point-usage-cancel-btn">취소</button>
            <button type="button" class="point-usage-confirm-btn" data-type="<?php echo $type; ?>" data-item-id="<?php echo $item_id; ?>">
                혜택신청하기
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
    display: none;
    align-items: center;
    justify-content: center;
}

.point-usage-modal.show {
    display: flex !important;
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
    max-width: 600px;
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
    flex-direction: column;
    gap: 12px;
    margin-bottom: 24px;
}

.point-balance-info,
.point-max-info {
    display: flex;
    flex-direction: row;
    gap: 12px;
    align-items: center;
    justify-content: center;
    padding: 20px 16px;
    border-radius: 16px;
    width: 100%;
    box-sizing: border-box;
    position: relative;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
    white-space: nowrap;
}

.point-balance-info {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.point-max-info {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border: none;
    box-shadow: 0 4px 12px rgba(245, 87, 108, 0.3);
}

.point-balance-info:hover,
.point-max-info:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
}

@media (min-width: 640px) {
    .point-info-section {
        flex-direction: row;
        gap: 16px;
    }
    
    .point-balance-info,
    .point-max-info {
        flex: 1;
    }
}

.point-balance-label,
.point-max-label {
    font-size: 15px;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.9);
    letter-spacing: -0.3px;
    white-space: nowrap;
    flex-shrink: 0;
}

.point-balance-value,
.point-max-value {
    font-size: 24px;
    font-weight: 800;
    color: #ffffff;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    letter-spacing: -0.5px;
    white-space: nowrap;
    flex-shrink: 0;
}

@media (min-width: 640px) {
    .point-balance-info,
    .point-max-info {
        flex-direction: column;
        gap: 12px;
    }
    
    .point-balance-value,
    .point-max-value {
        font-size: 28px;
    }
}

.point-notice {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 14px 16px;
    background: #eef2ff;
    border: 1px solid #c7d2fe;
    border-radius: 8px;
    margin-bottom: 24px;
    font-size: 14px;
    color: #4338ca;
}

.point-input-section {
    margin-bottom: 24px;
    padding: 24px;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.point-input-label {
    display: block;
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 12px;
    letter-spacing: -0.3px;
}

.point-input-wrapper {
    position: relative;
    margin-bottom: 12px;
}

.point-input {
    width: 100%;
    padding: 16px 52px 16px 20px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    outline: none;
    transition: all 0.2s;
    box-sizing: border-box;
    background: #ffffff;
}

.point-input:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    transform: translateY(-1px);
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
    padding: 14px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 700;
    color: #ffffff;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 52px;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    letter-spacing: -0.3px;
}

.point-quick-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

.point-quick-btn:active {
    transform: translateY(0);
}

.point-balance-preview {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 12px;
    padding: 20px 24px;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 2px solid #bae6fd;
    border-radius: 16px;
    margin-top: 4px;
    box-shadow: 0 2px 8px rgba(14, 165, 233, 0.1);
}

.point-balance-preview-label {
    font-size: 16px;
    font-weight: 600;
    color: #0369a1;
    letter-spacing: -0.3px;
}

.point-balance-preview-value {
    font-size: 24px;
    font-weight: 800;
    color: #0369a1;
    letter-spacing: -0.5px;
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
    
    // maxUsable 값을 모달 요소에서 가져오는 헬퍼 함수
    function getMaxUsable() {
        const maxUsableAttr = modal.getAttribute('data-max-usable');
        return maxUsableAttr ? parseInt(maxUsableAttr) : currentBalance;
    }
    
    // 모달 열기 (전역 함수)
    window.openPointUsageModal = function(type, itemId) {
        // 해당 타입과 아이템 ID에 맞는 모달 찾기
        const targetModalId = `pointUsageModal_${type}_${itemId}`;
        console.log('openPointUsageModal 호출됨:', { type, itemId, targetModalId });
        const targetModal = document.getElementById(targetModalId);
        console.log('모달 요소 찾기:', targetModal ? '찾음' : '찾지 못함', targetModalId);
        
        if (targetModal) {
            console.log('모달 표시 시작');
            // 현재 스크롤 위치 저장
            const scrollY = window.scrollY;
            document.body.style.position = 'fixed';
            document.body.style.top = `-${scrollY}px`;
            document.body.style.width = '100%';
            document.body.style.overflow = 'hidden';
            
            targetModal.classList.add('show');
            targetModal.style.display = 'flex';
            console.log('모달 표시 완료, 클래스:', targetModal.className);
            
            // 포인트 잔액 최신화 및 할인 혜택 내용 로드
            Promise.all([
                fetch('/MVNO/api/point-balance.php?user_id=default').then(r => r.json()),
                fetch(`/MVNO/api/get-product-point-setting.php?type=${type}&id=${itemId}`).then(r => r.json())
            ])
            .then(([balanceData, pointSettingData]) => {
                // 포인트 잔액 업데이트
                if (balanceData.success) {
                    const balanceValue = targetModal.querySelector('.point-balance-value');
                    const maxValue = document.getElementById('pointMaxValue_' + targetModalId);
                    const pointInput = targetModal.querySelector('.point-input');
                    const quickButtonsContainer = document.getElementById('pointQuickButtons_' + targetModalId);
                    
                    if (balanceValue) {
                        balanceValue.textContent = formatNumber(balanceData.balance) + 'P';
                    }
                    
                    // 상품의 point_setting 값 사용
                    if (pointSettingData.success) {
                        const productPointSetting = parseInt(pointSettingData.point_setting) || 0;
                        // 최대 사용 가능 포인트 = min(상품 point_setting, 보유 포인트)를 1000 단위로 내림
                        const rawMaxUsable = Math.min(productPointSetting, balanceData.balance);
                        const actualMaxUsable = Math.floor(rawMaxUsable / 1000) * 1000; // 1000 단위로 내림
                        
                        if (maxValue) {
                            maxValue.textContent = formatNumber(actualMaxUsable) + 'P';
                        }
                        if (pointInput) {
                            pointInput.setAttribute('max', actualMaxUsable);
                            // 모달 요소에 maxUsable 값 저장 (로컬 함수에서 접근 가능하도록)
                            targetModal.setAttribute('data-max-usable', actualMaxUsable);
                        }
                        
                        // 빠른 선택 버튼 업데이트
                        if (quickButtonsContainer) {
                            const roundedMax = actualMaxUsable;
                            quickButtonsContainer.innerHTML = '';
                            if (roundedMax > 0) {
                                const quickBtn = document.createElement('button');
                                quickBtn.type = 'button';
                                quickBtn.className = 'point-quick-btn';
                                quickBtn.setAttribute('data-point', roundedMax);
                                quickBtn.textContent = formatNumber(roundedMax) + 'P 사용';
                                quickBtn.addEventListener('click', function() {
                                    let point = parseInt(this.getAttribute('data-point')) || 0;
                                    if (point > 0 && point % 1000 !== 0) {
                                        point = Math.round(point / 1000) * 1000;
                                    }
                                    pointInput.value = point;
                                    updateBalancePreview();
                                });
                                quickButtonsContainer.appendChild(quickBtn);
                            }
                        }
                    }
                }
                
                // 할인 혜택 내용 표시
                if (pointSettingData.success && pointSettingData.point_benefit_description) {
                    const benefitSection = document.getElementById('pointBenefitSection_' + targetModalId);
                    const benefitText = document.getElementById('pointBenefitText_' + targetModalId);
                    
                    if (benefitSection && benefitText) {
                        benefitText.textContent = pointSettingData.point_benefit_description;
                        benefitSection.style.display = 'block';
                    }
                } else {
                    // 할인 혜택이 없으면 섹션 숨기기
                    const benefitSection = document.getElementById('pointBenefitSection_' + targetModalId);
                    if (benefitSection) {
                        benefitSection.style.display = 'none';
                    }
                }
            })
            .catch(error => {
                console.error('포인트 정보 로드 오류:', error);
            });
        }
    };
    
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    // 모달 닫기
    function closeModal() {
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.top = '';
    }
    
    // 포인트 입력 변경 시 잔액 미리보기 업데이트
    function updateBalancePreview() {
        const inputValueStr = pointInput.value.trim();
        const maxUsable = getMaxUsable();
        
        // 빈 문자열이면 0으로 처리하지 않고 잔액만 업데이트
        if (inputValueStr === '') {
            balancePreview.textContent = formatNumber(currentBalance) + 'P';
            return;
        }
        
        let inputValue = parseInt(inputValueStr) || 0;
        
        // 입력값 제한 (입력 중에는 반올림하지 않음)
        if (inputValue < 0) {
            inputValue = 0;
        }
        if (inputValue > maxUsable) {
            inputValue = maxUsable;
        }
        
        const usedPoint = Math.min(Math.max(0, inputValue), maxUsable);
        const remainingBalance = currentBalance - usedPoint;
        balancePreview.textContent = formatNumber(remainingBalance) + 'P';
    }
    
    // 포인트 입력 포커스 시
    pointInput.addEventListener('focus', function() {
        // 값이 0이면 빈 문자열로 변경 (사용자가 새로 입력할 수 있도록)
        if (parseInt(this.value) === 0) {
            this.value = '';
        }
    });
    
    // 포인트 입력 포커스 해제 시 (blur)
    pointInput.addEventListener('blur', function() {
        const inputValueStr = this.value.trim();
        const maxUsable = getMaxUsable();
        
        // 빈 문자열이면 0으로 설정
        if (inputValueStr === '') {
            this.value = 0;
            updateBalancePreview();
            return;
        }
        
        let inputValue = parseInt(inputValueStr) || 0;
        
        // 입력값 제한 (음수 방지)
        if (inputValue < 0) {
            inputValue = 0;
        }
        
        // 최대 사용 가능 포인트보다 크면 먼저 제한
        if (inputValue > maxUsable) {
            inputValue = maxUsable;
        }
        
        // 1000 포인트 단위로 조정
        if (inputValue > 0) {
            if (inputValue % 1000 !== 0) {
                // 1000 단위가 아니면 가장 가까운 1000 단위로 반올림
                inputValue = Math.round(inputValue / 1000) * 1000;
            }
            
            // 반올림 후에도 maxUsable보다 크면, maxUsable을 1000 단위로 내림한 값으로 설정
            if (inputValue > maxUsable) {
                // maxUsable을 1000 단위로 내림
                inputValue = Math.floor(maxUsable / 1000) * 1000;
            }
        }
        
        this.value = inputValue;
        updateBalancePreview();
    });
    
    // 빠른 선택 버튼
    quickBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            let point = parseInt(this.getAttribute('data-point')) || 0;
            // 1000 포인트 단위로 반올림
            if (point > 0 && point % 1000 !== 0) {
                point = Math.round(point / 1000) * 1000;
            }
            pointInput.value = point;
            updateBalancePreview();
        });
    });
    
    // 입력 이벤트 (입력 중에는 반올림하지 않고 잔액만 업데이트)
    pointInput.addEventListener('input', updateBalancePreview);
    
    // 확인 버튼 클릭
    confirmBtn.addEventListener('click', function() {
        const usedPoint = parseInt(pointInput.value) || 0;
        const maxUsable = getMaxUsable();
        
        // 1000 포인트 단위 검증
        if (usedPoint > 0 && usedPoint % 1000 !== 0) {
            showAlert('포인트는 1000포인트 단위로만 사용 가능합니다.');
            pointInput.focus();
            return;
        }
        
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
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            e.stopPropagation();
            closeModal();
        });
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeModal();
        });
    }
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeModal();
        });
    }
    
    // ESC 키로 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('show')) {
            closeModal();
        }
    });
})();
</script>


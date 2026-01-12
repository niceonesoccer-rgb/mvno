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
            
            <!-- 할인 혜택 내용 표시 영역 (동적으로 로드됨) -->
            <div id="pointBenefitSection_<?php echo $modal_id; ?>" class="point-benefit-section" style="display: none; background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                    <strong style="color: #166534; font-size: 16px; font-weight: 600; letter-spacing: -0.2px;">개통 시 혜택</strong>
                </div>
                <p id="pointBenefitText_<?php echo $modal_id; ?>" style="color: #15803d; font-size: 16px; font-weight: 500; margin: 0; line-height: 1.6; white-space: pre-line; letter-spacing: -0.1px;"></p>
            </div>
            
            <!-- 최대 사용 가능 포인트 버튼 -->
            <div class="point-max-button-section">
                <button type="button" class="point-max-button" id="pointMaxButton_<?php echo $modal_id; ?>">
                    <span id="pointMaxButtonValue_<?php echo $modal_id; ?>">-</span>P 사용
                </button>
                <p class="point-usage-notice">신청후 미개통시에도 소멸된 포인트는 환불되지 않습니다.</p>
            </div>
            
            <!-- 잔액 정보 -->
            <div class="point-balance-preview">
                <span class="point-balance-preview-label">사용 후 잔액</span>
                <span class="point-balance-preview-value" id="balancePreview_<?php echo $modal_id; ?>">
                    <?php echo number_format($current_balance); ?>P
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
    flex-direction: row;
    gap: 12px;
    margin-bottom: 24px;
}

.point-balance-info,
.point-max-info {
    display: flex;
    flex-direction: row;
    gap: 8px;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    border-radius: 12px;
    flex: 1;
    box-sizing: border-box;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
}

.point-balance-info {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
}

.point-max-info {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
}

.point-balance-label,
.point-max-label {
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
    letter-spacing: -0.2px;
    white-space: nowrap;
}

.point-balance-value,
.point-max-value {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    letter-spacing: -0.3px;
    white-space: nowrap;
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

.point-max-button-section {
    margin-bottom: 24px;
}

.point-max-button {
    width: 100%;
    padding: 16px 24px;
    background: #6366f1;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    color: #ffffff;
    cursor: pointer;
    transition: all 0.2s;
    letter-spacing: -0.2px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 8px;
}

.point-usage-notice {
    font-size: 12px;
    color: #9ca3af;
    text-align: center;
    margin: 0;
    line-height: 1.5;
    letter-spacing: -0.1px;
}

.point-max-button:hover {
    background: #4f46e5;
}

.point-max-button:active {
    transform: translateY(0);
}

.point-max-button:disabled {
    background: #d1d5db;
    cursor: not-allowed;
    box-shadow: none;
}

.point-balance-preview {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    margin-top: 4px;
}

.point-balance-preview-label {
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
    letter-spacing: -0.2px;
}

.point-balance-preview-value {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    letter-spacing: -0.3px;
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
    const pointMaxButton = document.getElementById('pointMaxButton_' + modalId);
    const pointMaxButtonValue = document.getElementById('pointMaxButtonValue_' + modalId);
    const balancePreview = document.getElementById('balancePreview_' + modalId);
    
    const currentBalance = <?php echo $current_balance; ?>;
    
    // maxUsable 값을 모달 요소에서 가져오는 헬퍼 함수
    function getMaxUsable() {
        const maxUsableAttr = modal.getAttribute('data-max-usable');
        return maxUsableAttr ? parseInt(maxUsableAttr) : 0;
    }
    
    // 모달 열기 (전역 함수)
    window.openPointUsageModal = function(type, itemId) {
        // 해당 타입과 아이템 ID에 맞는 모달 찾기
        const targetModalId = `pointUsageModal_${type}_${itemId}`;
        console.log('openPointUsageModal 호출됨:', { type, itemId, targetModalId });
        const targetModal = document.getElementById(targetModalId);
        console.log('모달 요소 찾기:', targetModal ? '찾음' : '찾지 못함', targetModalId);
        
        if (!targetModal) {
            console.warn('모달을 찾을 수 없습니다. 기존 신청 모달로 진행합니다.');
            // 모달이 없으면 바로 다음 단계로 진행
            const event = new CustomEvent('pointUsageConfirmed', {
                detail: {
                    type: type,
                    itemId: itemId,
                    usedPoint: 0
                }
            });
            document.dispatchEvent(event);
            return;
        }
        
        // 포인트 잔액 최신화 및 할인 혜택 내용 로드
        Promise.all([
            fetch('<?php echo getApiPath("/api/point-balance.php"); ?>?user_id=default').then(r => r.json()),
            fetch(`<?php echo getApiPath("/api/get-product-point-setting.php"); ?>?type=${type}&id=${itemId}`).then(r => r.json())
        ])
        .then(([balanceData, pointSettingData]) => {
            // 최대 사용 가능 포인트 계산
            let actualMaxUsable = 0;
            if (balanceData.success) {
                if (pointSettingData.success) {
                    // 상품의 point_setting 값 사용
                    const productPointSetting = parseInt(pointSettingData.point_setting) || 0;
                    // 최대 사용 가능 포인트 = min(상품 point_setting, 보유 포인트)를 1000 단위로 내림
                    const rawMaxUsable = Math.min(productPointSetting, balanceData.balance);
                    actualMaxUsable = Math.floor(rawMaxUsable / 1000) * 1000; // 1000 단위로 내림
                } else {
                    // point_setting 정보가 없으면 보유 포인트를 기준으로 계산
                    actualMaxUsable = Math.floor(balanceData.balance / 1000) * 1000; // 1000 단위로 내림
                }
            }
            
            // 사용 가능한 포인트가 없으면 모달을 띄우지 않고 바로 다음 단계로 진행
            if (actualMaxUsable <= 0) {
                console.log('사용 가능한 포인트가 없습니다. 바로 다음 단계로 진행합니다.');
                const event = new CustomEvent('pointUsageConfirmed', {
                    detail: {
                        type: type,
                        itemId: itemId,
                        usedPoint: 0
                    }
                });
                document.dispatchEvent(event);
                return;
            }
            
            // 모달 표시 시작
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
            
            // 포인트 잔액 업데이트
            if (balanceData.success) {
                const balanceValue = targetModal.querySelector('.point-balance-value');
                const maxValue = document.getElementById('pointMaxValue_' + targetModalId);
                const pointMaxBtn = document.getElementById('pointMaxButton_' + targetModalId);
                const pointMaxBtnValue = document.getElementById('pointMaxButtonValue_' + targetModalId);
                
                if (balanceValue) {
                    balanceValue.textContent = formatNumber(balanceData.balance) + 'P';
                }
                
                if (maxValue) {
                    maxValue.textContent = formatNumber(actualMaxUsable) + 'P';
                }
                
                // 모달 요소에 maxUsable 값 저장
                targetModal.setAttribute('data-max-usable', actualMaxUsable);
                
                // 최대 사용 가능 포인트 버튼에 표시
                if (pointMaxBtnValue) {
                    pointMaxBtnValue.textContent = formatNumber(actualMaxUsable);
                }
                
                // 잔액 미리보기 업데이트
                const balancePreview = document.getElementById('balancePreview_' + targetModalId);
                if (balancePreview) {
                    const remainingBalance = balanceData.balance - actualMaxUsable;
                    balancePreview.textContent = formatNumber(remainingBalance) + 'P';
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
            // 오류 발생 시에도 다음 단계로 진행
            const event = new CustomEvent('pointUsageConfirmed', {
                detail: {
                    type: type,
                    itemId: itemId,
                    usedPoint: 0
                }
            });
            document.dispatchEvent(event);
        });
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
    
    // 최대 사용 가능 포인트 버튼 클릭
    if (pointMaxButton) {
        pointMaxButton.addEventListener('click', function() {
            const maxUsable = getMaxUsable();
            if (maxUsable > 0) {
                // 최대 사용 가능 포인트로 설정
                const type = confirmBtn.getAttribute('data-type');
                const itemId = confirmBtn.getAttribute('data-item-id');
                
                // 포인트 사용 정보를 전역 변수에 저장
                window.pointUsageData = {
                    type: type,
                    itemId: itemId,
                    usedPoint: maxUsable
                };
                
                // 모달 닫기
                closeModal();
                
                // 기존 신청 모달 열기 (이벤트 발생)
                const event = new CustomEvent('pointUsageConfirmed', {
                    detail: {
                        type: type,
                        itemId: itemId,
                        usedPoint: maxUsable
                    }
                });
                document.dispatchEvent(event);
            }
        });
    }
    
    // 확인 버튼 클릭 (최대 사용 가능 포인트로 자동 설정)
    confirmBtn.addEventListener('click', function() {
        const maxUsable = getMaxUsable();
        const type = this.getAttribute('data-type');
        const itemId = this.getAttribute('data-item-id');
        
        // 최대 사용 가능 포인트로 자동 설정
        const usedPoint = maxUsable;
        
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


<?php
/**
 * 판매자 인터넷 상품 등록 페이지
 * 경로: /seller/products/internet.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = getCurrentUser();

// 판매자 로그인 체크
if (!$currentUser || $currentUser['role'] !== 'seller') {
    header('Location: /MVNO/seller/login.php');
    exit;
}

// 판매자 승인 체크
$approvalStatus = $currentUser['approval_status'] ?? 'pending';
if ($approvalStatus !== 'approved') {
    header('Location: /MVNO/seller/waiting.php');
    exit;
}

// 탈퇴 요청 상태 확인
if (isset($currentUser['withdrawal_requested']) && $currentUser['withdrawal_requested'] === true) {
    header('Location: /MVNO/seller/waiting.php');
    exit;
}

// 인터넷 권한 확인
$hasPermission = hasSellerPermission($currentUser['user_id'], 'internet');
if (!$hasPermission) {
    $noPermission = true;
}

// 페이지별 스타일
$pageStyles = '
    .product-register-container {
        max-width: 900px;
        margin: 0 auto;
    }
    
    .page-header {
        margin-bottom: 32px;
    }
    
    .page-header h1 {
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 8px;
    }
    
    .page-header p {
        font-size: 16px;
        color: #6b7280;
    }
    
    .product-form {
        background: white;
        border-radius: 12px;
        padding: 32px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }
    
    .form-section {
        margin-bottom: 32px;
    }
    
    .form-section-title {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .form-group {
        margin-bottom: 24px;
    }
    
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .form-label .required {
        color: #ef4444;
        margin-left: 4px;
    }
    
    .form-control {
        width: 100%;
        padding: 12px 16px;
        font-size: 15px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        transition: all 0.2s;
        background: white;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .form-select {
        width: 100%;
        padding: 12px 16px;
        font-size: 15px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: white;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .form-select:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .form-textarea {
        width: 100%;
        padding: 12px 16px;
        font-size: 15px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        min-height: 120px;
        resize: vertical;
        font-family: inherit;
        transition: all 0.2s;
    }
    
    .form-textarea:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .form-help {
        font-size: 13px;
        color: #6b7280;
        margin-top: 6px;
    }
    
    .form-checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        margin-top: 8px;
    }
    
    .form-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .form-checkbox input[type="checkbox"],
    .form-checkbox input[type="radio"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    
    .form-checkbox label {
        font-size: 14px;
        color: #374151;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .registration-logo {
        width: 100px;
        height: 40px;
        object-fit: contain;
        display: inline-block;
    }
    
    .registration-logo-dlive {
        object-fit: cover;
        padding: 0;
        margin: 0;
        width: 120px;
        height: 35px;
    }
    
    .registration-logo-kt {
        height: 24px;
    }
    
    .custom-select-wrapper {
        position: relative;
        flex: 1;
    }
    
    .custom-select {
        display: none;
    }
    
    .custom-select-trigger {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        font-size: 15px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: white;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .custom-select-trigger:hover {
        border-color: #10b981;
    }
    
    .custom-select-trigger:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .custom-select-trigger .selected-value {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .custom-select-trigger .selected-value img {
        max-width: 100px;
        max-height: 40px;
        object-fit: contain;
    }
    
    .custom-select-trigger .arrow {
        width: 0;
        height: 0;
        border-left: 5px solid transparent;
        border-right: 5px solid transparent;
        border-top: 6px solid #6b7280;
        transition: transform 0.2s;
    }
    
    .custom-select-trigger.open .arrow {
        transform: rotate(180deg);
    }
    
    .custom-options {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        margin-top: 4px;
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .custom-options.open {
        display: block;
    }
    
    .custom-option {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 12px 16px;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .custom-option:hover {
        background: #f3f4f6;
    }
    
    .custom-option.selected {
        background: #d1fae5;
    }
    
    .custom-option img {
        max-width: 100px;
        max-height: 40px;
        object-fit: contain;
    }
    
    .custom-option[data-value="KT"] img {
        height: 24px;
    }
    
    .custom-option[data-value="DLIVE"] img {
        height: 35px;
        object-fit: cover;
    }
    
    .custom-option[data-value="기타"] {
        padding-left: 16px;
    }
    
    .form-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid #e5e7eb;
    }
    
    .btn {
        padding: 12px 24px;
        font-size: 15px;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary {
        background: #10b981;
        color: white;
    }
    
    .btn-primary:hover {
        background: #059669;
    }
    
    .btn-secondary {
        background: #f3f4f6;
        color: #374151;
    }
    
    .btn-secondary:hover {
        background: #e5e7eb;
    }
    
    .alert {
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
    }
    
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #10b981;
    }
    
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #ef4444;
    }
    
    .gift-input-group {
        display: flex;
        gap: 0;
        margin-bottom: 8px;
    }
    
    .gift-input-group .form-control {
        flex: 1;
    }
    
    .btn-remove {
        padding: 12px 16px;
        background: #ef4444;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
    }
    
    .btn-add {
        padding: 8px 16px;
        background: #f3f4f6;
        color: #374151;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        margin-top: 8px;
    }
';

include __DIR__ . '/../includes/seller-header.php';
?>

<?php if (isset($noPermission) && $noPermission): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof showAlert === 'function') {
        showAlert('등록권한이 없습니다.\n관리자에게 문의하세요.', '권한 없음').then(function() {
            window.location.href = '/MVNO/seller/';
        });
    } else {
        alert('등록권한이 없습니다.\n관리자에게 문의하세요.');
        window.location.href = '/MVNO/seller/';
    }
});
</script>
<?php exit; endif; ?>

<div class="product-register-container">
    <div class="page-header">
        <h1>인터넷 상품 등록</h1>
        <p>새로운 인터넷 상품을 등록하세요</p>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            상품이 성공적으로 등록되었습니다.
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            상품 등록 중 오류가 발생했습니다. 다시 시도해주세요.
        </div>
    <?php endif; ?>
    
    <form id="productForm" class="product-form" method="POST" action="/MVNO/api/product-register-internet.php">
        
        <!-- 인터넷가입처 -->
        <div class="form-section">
            <div class="form-section-title">인터넷가입처</div>
            
            <div class="form-group">
                <label class="form-label" for="registration_place">가입처 업체</label>
                <div class="custom-select-wrapper">
                    <select name="registration_place" id="registration_place" class="custom-select">
                        <option value="">선택하세요</option>
                        <option value="KT">KT</option>
                        <option value="SKT">SKT</option>
                        <option value="LG U+">LG U+</option>
                        <option value="KT skylife">KT skylife</option>
                        <option value="LG헬로비전">LG헬로비전</option>
                        <option value="BTV">BTV</option>
                        <option value="DLIVE">DLIVE</option>
                        <option value="기타">기타</option>
                    </select>
                    <div class="custom-select-trigger" id="custom-select-trigger">
                        <div class="selected-value">
                            <span>선택하세요</span>
                        </div>
                        <div class="arrow"></div>
                    </div>
                    <div class="custom-options" id="custom-options">
                        <div class="custom-option" data-value="">
                            <span>선택하세요</span>
                        </div>
                        <div class="custom-option" data-value="KT">
                            <img src="/MVNO/assets/images/internets/kt.svg" alt="KT">
                        </div>
                        <div class="custom-option" data-value="SKT">
                            <img src="/MVNO/assets/images/internets/broadband.svg" alt="SKT">
                        </div>
                        <div class="custom-option" data-value="LG U+">
                            <img src="/MVNO/assets/images/internets/lgu.svg" alt="LG U+">
                        </div>
                        <div class="custom-option" data-value="KT skylife">
                            <img src="/MVNO/assets/images/internets/ktskylife.svg" alt="KT skylife">
                        </div>
                        <div class="custom-option" data-value="LG헬로비전">
                            <img src="/MVNO/assets/images/internets/hellovision.svg" alt="LG헬로비전">
                        </div>
                        <div class="custom-option" data-value="BTV">
                            <img src="/MVNO/assets/images/internets/btv.svg" alt="BTV">
                        </div>
                        <div class="custom-option" data-value="DLIVE">
                            <img src="/MVNO/assets/images/internets/dlive.svg" alt="DLIVE">
                        </div>
                        <div class="custom-option" data-value="기타">
                            <span>기타</span>
                        </div>
                    </div>
                </div>
                <div class="form-help">가입 가능한 업체를 선택하세요</div>
            </div>
        </div>
        
        <!-- 가입속도 -->
        <div class="form-section">
            <div class="form-section-title">가입속도</div>
            
            <div class="form-group">
                <label class="form-label">속도 선택</label>
                <div class="form-checkbox-group">
                    <div class="form-checkbox">
                        <input type="radio" name="speed_option" id="speed_100m" value="100M">
                        <label for="speed_100m">100M</label>
                    </div>
                    <div class="form-checkbox">
                        <input type="radio" name="speed_option" id="speed_500m" value="500M">
                        <label for="speed_500m">500M</label>
                    </div>
                    <div class="form-checkbox">
                        <input type="radio" name="speed_option" id="speed_1g" value="1G">
                        <label for="speed_1g">1G</label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 현금지급 -->
        <div class="form-section">
            <div class="form-section-title">현금지급</div>
            
            <div class="form-group">
                <label class="form-label">항목</label>
                <div id="cash-payment-container">
                    <div class="gift-input-group">
                        <div style="display: flex; align-items: center; justify-content: center; padding: 0 12px; background: #f3f4f6; border: 1px solid #d1d5db; border-right: none; border-radius: 8px 0 0 8px; width: 40px;">
                            <img src="/MVNO/assets/images/won.svg" alt="원" style="width: 20px; height: 20px; object-fit: contain;">
                        </div>
                        <input type="text" name="cash_payments[]" class="form-control" placeholder="항목 입력" maxlength="20" style="border-left: none; border-right: none; border-radius: 0;">
                        <button type="button" class="btn-add" onclick="addCashPaymentField()" style="margin-top: 0; border-radius: 0 8px 8px 0; border-left: none;">추가</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 제출 버튼 -->
        <div class="form-actions">
            <a href="/MVNO/seller/products/list.php" class="btn btn-secondary">취소</a>
            <button type="submit" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 13l4 4L19 7"/>
                </svg>
                등록하기
            </button>
        </div>
    </form>
</div>

<script>
// 가입처 로고 매핑
const registrationLogos = {
    'KT': '/MVNO/assets/images/internets/kt.svg',
    'SKT': '/MVNO/assets/images/internets/broadband.svg',
    'LG U+': '/MVNO/assets/images/internets/lgu.svg',
    'KT skylife': '/MVNO/assets/images/internets/ktskylife.svg',
    'LG헬로비전': '/MVNO/assets/images/internets/hellovision.svg',
    'BTV': '/MVNO/assets/images/internets/btv.svg',
    'DLIVE': '/MVNO/assets/images/internets/dlive.svg',
    '기타': ''
};

// 커스텀 드롭다운 초기화
document.addEventListener('DOMContentLoaded', function() {
    const customSelect = document.querySelector('.custom-select');
    const customTrigger = document.getElementById('custom-select-trigger');
    const customOptions = document.getElementById('custom-options');
    const options = customOptions.querySelectorAll('.custom-option');
    
    // 트리거 클릭 시 옵션 열기/닫기
    if (customTrigger && customOptions) {
        customTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = customTrigger.classList.contains('open');
            
            // 다른 열린 드롭다운 닫기
            document.querySelectorAll('.custom-select-trigger.open').forEach(trigger => {
                if (trigger !== customTrigger) {
                    trigger.classList.remove('open');
                    trigger.nextElementSibling.classList.remove('open');
                }
            });
            
            if (isOpen) {
                customTrigger.classList.remove('open');
                customOptions.classList.remove('open');
            } else {
                customTrigger.classList.add('open');
                customOptions.classList.add('open');
            }
        });
        
        // 옵션 클릭 시 선택
        options.forEach(option => {
            option.addEventListener('click', function() {
                const value = this.getAttribute('data-value');
                const hiddenSelect = customSelect;
                
                // hidden select 업데이트
                hiddenSelect.value = value;
                
                // 트리거 업데이트
                const selectedOption = this.cloneNode(true);
                const selectedValueDiv = customTrigger.querySelector('.selected-value');
                selectedValueDiv.innerHTML = '';
                
                if (value && registrationLogos[value]) {
                    const img = document.createElement('img');
                    img.src = registrationLogos[value];
                    img.alt = value;
                    
                    if (value === 'DLIVE') {
                        img.style.height = '35px';
                        img.style.objectFit = 'cover';
                    } else if (value === 'KT') {
                        img.style.height = '24px';
                    } else {
                        img.style.height = '40px';
                        img.style.objectFit = 'contain';
                    }
                    
                    selectedValueDiv.appendChild(img);
                } else {
                    const span = document.createElement('span');
                    span.textContent = '선택하세요';
                    selectedValueDiv.appendChild(span);
                }
                
                // 선택된 옵션 표시 업데이트
                options.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                
                // 드롭다운 닫기
                customTrigger.classList.remove('open');
                customOptions.classList.remove('open');
                
                // change 이벤트 트리거
                const event = new Event('change', { bubbles: true });
                hiddenSelect.dispatchEvent(event);
            });
        });
        
        // 외부 클릭 시 드롭다운 닫기
        document.addEventListener('click', function(e) {
            if (!customTrigger.contains(e.target) && !customOptions.contains(e.target)) {
                customTrigger.classList.remove('open');
                customOptions.classList.remove('open');
            }
        });
    }
});

let cashPaymentCount = 1;

function addCashPaymentField() {
    const container = document.getElementById('cash-payment-container');
    const newField = document.createElement('div');
    newField.className = 'gift-input-group';
    newField.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: center; padding: 0 12px; background: #f3f4f6; border: 1px solid #d1d5db; border-right: none; border-radius: 8px 0 0 8px; width: 40px;">
            <img src="/MVNO/assets/images/won.svg" alt="원" style="width: 20px; height: 20px; object-fit: contain;">
        </div>
        <input type="text" name="cash_payments[]" class="form-control" placeholder="항목 입력" maxlength="20" style="border-left: none; border-right: none; border-radius: 0;">
        <button type="button" class="btn-remove" onclick="removeCashPaymentField(this)" style="border-radius: 0 8px 8px 0; border-left: none;">삭제</button>
    `;
    container.appendChild(newField);
    cashPaymentCount++;
}

function removeCashPaymentField(button) {
    const container = document.getElementById('cash-payment-container');
    if (container.children.length > 1) {
        button.parentElement.remove();
    }
}

document.getElementById('productForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/MVNO/api/product-register-internet.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/MVNO/seller/products/internet.php?success=1';
        } else {
            alert(data.message || '상품 등록에 실패했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('상품 등록 중 오류가 발생했습니다.');
    });
});
</script>

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>






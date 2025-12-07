<?php
/**
 * 판매자 통신사폰 상품 등록 페이지
 * 경로: /seller/products/mno.php
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

// 통신사폰 권한 확인
$hasPermission = hasSellerPermission($currentUser['user_id'], 'mno');
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
    
    .form-checkbox input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    
    .form-checkbox label {
        font-size: 14px;
        color: #374151;
        cursor: pointer;
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
        gap: 8px;
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
        <h1>통신사폰 상품 등록</h1>
        <p>새로운 통신사폰 요금제를 등록하세요</p>
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
    
    <form id="productForm" class="product-form" method="POST" action="/MVNO/api/product-register.php">
        <input type="hidden" name="board_type" value="mno">
        
        <!-- 기본 정보 -->
        <div class="form-section">
            <div class="form-section-title">기본 정보</div>
            
            <div class="form-group">
                <label class="form-label" for="provider">
                    통신사 <span class="required">*</span>
                </label>
                <select name="provider" id="provider" class="form-select" required>
                    <option value="">선택하세요</option>
                    <option value="KT">KT</option>
                    <option value="SKT">SKT</option>
                    <option value="LG U+">LG U+</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="phone_name">
                    기기명 <span class="required">*</span>
                </label>
                <input type="text" name="phone_name" id="phone_name" class="form-control" required placeholder="예: 갤럭시 S24, 아이폰 15 Pro">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="plan_name">
                    요금제명 <span class="required">*</span>
                </label>
                <input type="text" name="plan_name" id="plan_name" class="form-control" required placeholder="예: 5GX 슈퍼플랜">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="data_main">
                    데이터 용량 <span class="required">*</span>
                </label>
                <input type="text" name="data_main" id="data_main" class="form-control" required placeholder="예: 무제한">
                <div class="form-help">주요 데이터 용량을 입력하세요</div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="service_type">
                    서비스 타입 <span class="required">*</span>
                </label>
                <select name="service_type" id="service_type" class="form-select" required>
                    <option value="">선택하세요</option>
                    <option value="LTE">LTE</option>
                    <option value="5G">5G</option>
                </select>
            </div>
        </div>
        
        <!-- 요금 정보 -->
        <div class="form-section">
            <div class="form-section-title">요금 정보</div>
            
            <div class="form-group">
                <label class="form-label" for="price_main">
                    월 요금 <span class="required">*</span>
                </label>
                <input type="number" name="price_main" id="price_main" class="form-control" required placeholder="예: 75000" min="0">
                <div class="form-help">원 단위로 입력하세요</div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="device_price">
                    기기 가격
                </label>
                <input type="number" name="device_price" id="device_price" class="form-control" placeholder="예: 990000" min="0">
                <div class="form-help">기기 할부 가격을 입력하세요</div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="installment_months">
                    할부 개월
                </label>
                <select name="installment_months" id="installment_months" class="form-select">
                    <option value="">선택하세요</option>
                    <option value="12">12개월</option>
                    <option value="24">24개월</option>
                    <option value="36">36개월</option>
                </select>
            </div>
        </div>
        
        <!-- 기능/특징 -->
        <div class="form-section">
            <div class="form-section-title">기능 및 특징</div>
            
            <div class="form-group">
                <label class="form-label">기본 기능</label>
                <div class="form-checkbox-group">
                    <div class="form-checkbox">
                        <input type="checkbox" name="features[]" id="feature_call" value="통화 무제한">
                        <label for="feature_call">통화 무제한</label>
                    </div>
                    <div class="form-checkbox">
                        <input type="checkbox" name="features[]" id="feature_sms" value="문자 무제한">
                        <label for="feature_sms">문자 무제한</label>
                    </div>
                    <div class="form-checkbox">
                        <input type="checkbox" name="features[]" id="feature_data" value="데이터 무제한">
                        <label for="feature_data">데이터 무제한</label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 혜택/사은품 -->
        <div class="form-section">
            <div class="form-section-title">혜택 및 사은품</div>
            
            <div class="form-group">
                <label class="form-label">사은품 목록</label>
                <div id="gifts-container">
                    <div class="gift-input-group">
                        <input type="text" name="gifts[]" class="form-control" placeholder="예: 액정보호필름, 케이스">
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addGiftField()">+ 혜택 추가</button>
            </div>
        </div>
        
        <!-- 상세 설명 -->
        <div class="form-section">
            <div class="form-section-title">상세 정보</div>
            
            <div class="form-group">
                <label class="form-label" for="description">
                    상품 설명
                </label>
                <textarea name="description" id="description" class="form-textarea" placeholder="상품에 대한 자세한 설명을 입력하세요"></textarea>
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
let giftCount = 1;

function addGiftField() {
    const container = document.getElementById('gifts-container');
    const newField = document.createElement('div');
    newField.className = 'gift-input-group';
    newField.innerHTML = `
        <input type="text" name="gifts[]" class="form-control" placeholder="예: 액정보호필름, 케이스">
        <button type="button" class="btn-remove" onclick="removeGiftField(this)">삭제</button>
    `;
    container.appendChild(newField);
    giftCount++;
}

function removeGiftField(button) {
    const container = document.getElementById('gifts-container');
    if (container.children.length > 1) {
        button.parentElement.remove();
    }
}

document.getElementById('productForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/MVNO/api/product-register.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/MVNO/seller/products/mno.php?success=1';
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

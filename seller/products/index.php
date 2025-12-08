<?php
/**
 * 판매자 상품 등록 페이지
 * 경로: /seller/products/
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
    
    .permission-notice {
        padding: 16px;
        background: #fef3c7;
        border: 1px solid #f59e0b;
        border-radius: 8px;
        margin-bottom: 24px;
    }
    
    .permission-notice-title {
        font-size: 16px;
        font-weight: 600;
        color: #92400e;
        margin-bottom: 8px;
    }
    
    .permission-notice-text {
        font-size: 14px;
        color: #78350f;
    }
';

include __DIR__ . '/../includes/seller-header.php';

// 판매자 권한 확인
$hasMvnoPermission = hasSellerPermission($currentUser['user_id'], 'mvno');
$hasMnoPermission = hasSellerPermission($currentUser['user_id'], 'mno');
$hasInternetPermission = hasSellerPermission($currentUser['user_id'], 'internet');
?>

<div class="product-register-container">
    <div class="page-header">
        <h1>상품 등록</h1>
        <p>새로운 상품을 등록하세요</p>
    </div>
    
    <?php if (!$hasMvnoPermission && !$hasMnoPermission && !$hasInternetPermission): ?>
        <div class="permission-notice">
            <div class="permission-notice-title">권한이 없습니다</div>
            <div class="permission-notice-text">
                상품을 등록하려면 관리자로부터 게시판 권한을 받아야 합니다. 관리자에게 권한을 요청하세요.
            </div>
        </div>
    <?php else: ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                상품이 성공적으로 등록되었습니다.
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <?php if ($_GET['error'] === 'no_permission'): ?>
                <div class="permission-notice">
                    <div class="permission-notice-title">권한이 없습니다</div>
                    <div class="permission-notice-text">
                        해당 게시판에 상품을 등록할 권한이 없습니다. 관리자에게 권한을 요청하세요.
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    상품 등록 중 오류가 발생했습니다. 다시 시도해주세요.
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <form id="productForm" class="product-form" method="POST" action="/MVNO/api/product-register.php">
            <!-- 기본 정보 -->
            <div class="form-section">
                <div class="form-section-title">기본 정보</div>
                
                <div class="form-group">
                    <label class="form-label" for="provider">
                        통신사/제공사 <span class="required">*</span>
                    </label>
                    <select name="provider" id="provider" class="form-select" required>
                        <option value="">선택하세요</option>
                        <option value="kt">KT</option>
                        <option value="skt">SKT</option>
                        <option value="lg">LG U+</option>
                        <option value="kt_mvno">KT 알뜰폰</option>
                        <option value="skt_mvno">SKT 알뜰폰</option>
                        <option value="lg_mvno">LG U+ 알뜰폰</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="plan_name">
                        요금제명 <span class="required">*</span>
                    </label>
                    <input type="text" name="plan_name" id="plan_name" class="form-control" required placeholder="예: 5GX 슈퍼플랜">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="monthly_fee">
                        월 요금 <span class="required">*</span>
                    </label>
                    <input type="number" name="monthly_fee" id="monthly_fee" class="form-control" required placeholder="예: 75000" min="0">
                    <div class="form-help">원 단위로 입력하세요</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="speed">
                        속도/용량
                    </label>
                    <input type="text" name="speed" id="speed" class="form-control" placeholder="예: 5G 무제한, 100Mbps">
                    <div class="form-help">인터넷 상품의 경우 속도를, 통신 상품의 경우 데이터 용량을 입력하세요</div>
                </div>
            </div>
            
            <!-- 상세 정보 -->
            <div class="form-section">
                <div class="form-section-title">상세 정보</div>
                
                <div class="form-group">
                    <label class="form-label" for="description">
                        상품 설명
                    </label>
                    <textarea name="description" id="description" class="form-textarea" placeholder="상품에 대한 자세한 설명을 입력하세요"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="benefits">
                        혜택/특징
                    </label>
                    <textarea name="benefits" id="benefits" class="form-textarea" placeholder="주요 혜택이나 특징을 입력하세요"></textarea>
                </div>
            </div>
            
            <!-- 제출 버튼 -->
            <div class="form-actions">
                <a href="/seller/products/list.php" class="btn btn-secondary">취소</a>
                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 13l4 4L19 7"/>
                    </svg>
                    등록하기
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
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
            window.location.href = '/seller/products/?success=1';
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








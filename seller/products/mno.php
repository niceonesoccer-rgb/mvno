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
        min-width: 70px;
        width: 70px;
    }
    
    .btn-add {
        padding: 12px 16px;
        background: #10b981;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        margin-top: 8px;
        min-width: 70px;
        width: 70px;
    }
    
    .btn-add:hover {
        background: #059669;
    }
    
    .input-with-unit {
        position: relative;
        display: inline-block;
        width: 100%;
    }
    
    .input-with-unit input {
        padding-right: 40px;
    }
    
    .input-with-unit .unit {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 15px;
        color: #6b7280;
        pointer-events: none;
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
    
    <form id="productForm" class="product-form" method="POST" action="/MVNO/api/product-register-mno.php">
        
        <!-- 기본 정보 -->
        <div class="form-section">
            <div class="form-section-title">단말기</div>
            
            <div class="form-group" style="display: flex; gap: 16px; align-items: flex-start;">
                <div style="flex: 1;">
                    <label class="form-label" for="device_name">
                        단말기명
                    </label>
                    <input type="text" name="device_name" id="device_name" class="form-control" placeholder="iPhone 18" maxlength="20">
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="device_price">
                        단말기 출고가
                    </label>
                    <div class="input-with-unit">
                        <input type="text" name="device_price" id="device_price" class="form-control" placeholder="2,700,000" maxlength="12">
                        <span class="unit">원</span>
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="device_capacity">
                        용량
                    </label>
                    <input type="text" name="device_capacity" id="device_capacity" class="form-control" placeholder="1TB" maxlength="10">
                </div>
            </div>
            
            <!-- 할인방법 -->
            <div class="form-group" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; background: #f9fafb;">
                <label class="form-label" style="font-size: 16px; margin-bottom: 20px;">할인방법</label>
                
                <!-- 공통지원할인과 선택약정할인 나란히 배치 -->
                <div style="display: flex; gap: 24px; align-items: flex-start;">
                    <!-- 공통지원할인 -->
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                            <label class="form-label" style="font-size: 14px; font-weight: 600; color: #374151; margin: 0;">공통지원할인</label>
                            <span style="font-size: 12px; color: #374151; font-weight: 600;">( 정책없음 = 9999 )</span>
                        </div>
                        <div id="common-discount-container">
                            <div class="common-discount-row" style="display: flex; gap: 8px; flex-wrap: nowrap; margin-bottom: 8px; align-items: flex-end;">
                                <div style="flex: 1; min-width: 0;">
                                    <label class="form-label" style="font-size: 13px; font-weight: 500; margin-bottom: 8px;">통신사</label>
                                    <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px; font-weight: 600; font-size: 14px; height: 40px; line-height: 20px; box-sizing: border-box; display: flex; align-items: center;">SKT</div>
                                    <input type="hidden" name="common_provider[]" value="SKT">
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <label class="form-label" style="font-size: 13px; font-weight: 500; margin-bottom: 8px;">신규가입</label>
                                    <input type="text" name="common_discount_new[]" class="form-control common-discount-input" placeholder="9999" value="9999" maxlength="7" style="padding: 10px 14px; font-size: 14px; height: 40px; box-sizing: border-box;">
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <label class="form-label" style="font-size: 13px; font-weight: 500; margin-bottom: 8px;">번호이동</label>
                                    <input type="text" name="common_discount_port[]" class="form-control common-discount-input" placeholder="-198" maxlength="7" style="padding: 10px 14px; font-size: 14px; height: 40px; box-sizing: border-box;">
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <label class="form-label" style="font-size: 13px; font-weight: 500; margin-bottom: 8px;">기기변경</label>
                                    <input type="text" name="common_discount_change[]" class="form-control common-discount-input" placeholder="191.6" maxlength="7" style="padding: 10px 14px; font-size: 14px; height: 40px; box-sizing: border-box;">
                                </div>
                            </div>
                            <div class="common-discount-row" style="display: flex; gap: 8px; flex-wrap: nowrap; margin-bottom: 8px; align-items: center;">
                                <div style="flex: 1; min-width: 0;">
                                    <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px; font-weight: 600; font-size: 14px; height: 40px; line-height: 20px; box-sizing: border-box; display: flex; align-items: center;">KT</div>
                                    <input type="hidden" name="common_provider[]" value="KT">
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <input type="text" name="common_discount_new[]" class="form-control common-discount-input" placeholder="9999" value="9999" maxlength="7" style="padding: 10px 14px; font-size: 14px; height: 40px; box-sizing: border-box;">
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <input type="text" name="common_discount_port[]" class="form-control common-discount-input" placeholder="-198" maxlength="7" style="padding: 10px 14px; font-size: 14px; height: 40px; box-sizing: border-box;">
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <input type="text" name="common_discount_change[]" class="form-control common-discount-input" placeholder="191.6" maxlength="7" style="padding: 10px 14px; font-size: 14px; height: 40px; box-sizing: border-box;">
                                </div>
                            </div>
                            <div class="common-discount-row" style="display: flex; gap: 8px; flex-wrap: nowrap; margin-bottom: 0; align-items: center;">
                                <div style="flex: 1; min-width: 0;">
                                    <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px; font-weight: 600; font-size: 14px; height: 40px; line-height: 20px; box-sizing: border-box; display: flex; align-items: center;">LGU+</div>
                                    <input type="hidden" name="common_provider[]" value="LG U+">
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <input type="text" name="common_discount_new[]" class="form-control common-discount-input" placeholder="9999" value="9999" maxlength="7" style="padding: 10px 14px; font-size: 14px; height: 40px; box-sizing: border-box;">
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <input type="text" name="common_discount_port[]" class="form-control common-discount-input" placeholder="-198" maxlength="7" style="padding: 10px 14px; font-size: 14px; height: 40px; box-sizing: border-box;">
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <input type="text" name="common_discount_change[]" class="form-control common-discount-input" placeholder="191.6" maxlength="7" style="padding: 10px 14px; font-size: 14px; height: 40px; box-sizing: border-box;">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 선택약정할인 -->
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                            <label class="form-label" style="font-size: 14px; font-weight: 600; color: #374151; margin: 0;">선택약정할인</label>
                            <span style="font-size: 12px; color: #374151; font-weight: 600;">( 정책없음 = 9999 )</span>
                        </div>
                        <div id="contract-discount-container">
                            <div class="contract-discount-row" style="display: flex; gap: 8px; flex-wrap: nowrap; margin-bottom: 8px; align-items: flex-end;">
                                <div style="flex: 1; min-width: 0;">
                                    <label class="form-label" style="font-size: 13px; font-weight: 500; margin-bottom: 8px;">통신사</label>
                                    <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px; font-weight: 600; font-size: 14px; height: 40px; line-height: 20px; box-sizing: border-box; display: flex; align-items: center;">SKT</div>
                                    <input type="hidden" name="contract_provider[]" value="SKT">
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <label class="form-label" style="font-size: 13px; font-weight: 500; margin-bottom: 8px;">신규가입</label>
                                    <input type="text" name="contract_discount_new[]" class="form-control contract-discount-input" placeholder="9999" value="9999" maxlength="7" style="padding: 10px 14px; font-size: 14px; height: 40px; box-sizing: border-box;">
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <label class="form-label" style="font-size: 13px; font-weight: 500; margin-bottom: 8px;">번호이동</label>
                                    <input type="text" name="contract_discount_port[]" class="form-control contract-discount-input" placeholder="198" maxlength="7" style="padding: 10px 14px; font-size: 14px; height: 40px; box-sizing: border-box;">
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <label class="form-label" style="font-size: 13px; font-weight: 500; margin-bottom: 8px;">기기변경</label>
                                    <input type="text" name="contract_discount_change[]" class="form-control contract-discount-input" placeholder="150" maxlength="7" style="padding: 10px 14px; font-size: 14px; height: 40px; box-sizing: border-box;">
                                </div>
                            </div>
                            <div class="contract-discount-row" style="display: flex; gap: 8px; flex-wrap: nowrap; margin-bottom: 8px; align-items: center;">
                                <div style="flex: 1; min-width: 0;">
                                    <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px; font-weight: 600; font-size: 14px; height: 40px; line-height: 20px; box-sizing: border-box; display: flex; align-items: center;">KT</div>
                                    <input type="hidden" name="contract_provider[]" value="KT">
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <input type="text" name="contract_discount_new[]" class="form-control contract-discount-input" placeholder="9999" value="9999" maxlength="7" style="padding: 10px 14px; font-size: 14px; height: 40px; box-sizing: border-box;">
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <input type="text" name="contract_discount_port[]" class="form-control contract-discount-input" placeholder="198" maxlength="7" style="padding: 10px 14px; font-size: 14px; height: 40px; box-sizing: border-box;">
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <input type="text" name="contract_discount_change[]" class="form-control contract-discount-input" placeholder="150" maxlength="7" style="padding: 10px 14px; font-size: 14px; height: 40px; box-sizing: border-box;">
                                </div>
                            </div>
                            <div class="contract-discount-row" style="display: flex; gap: 8px; flex-wrap: nowrap; margin-bottom: 0; align-items: center;">
                                <div style="flex: 1; min-width: 0;">
                                    <div class="form-control" style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px 14px; font-weight: 600; font-size: 14px; height: 40px; line-height: 20px; box-sizing: border-box; display: flex; align-items: center;">LGU+</div>
                                    <input type="hidden" name="contract_provider[]" value="LG U+">
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <input type="text" name="contract_discount_new[]" class="form-control contract-discount-input" placeholder="9999" value="9999" maxlength="7" style="padding: 10px 14px; font-size: 14px; height: 40px; box-sizing: border-box;">
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <input type="text" name="contract_discount_port[]" class="form-control contract-discount-input" placeholder="198" maxlength="7" style="padding: 10px 14px; font-size: 14px; height: 40px; box-sizing: border-box;">
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <input type="text" name="contract_discount_change[]" class="form-control contract-discount-input" placeholder="150" maxlength="7" style="padding: 10px 14px; font-size: 14px; height: 40px; box-sizing: border-box;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 택배 방문시 지역 선택 -->
        <div class="form-section">
            <div class="form-section-title">단말기 수령방법</div>
            
            <div class="form-group">
                <div class="form-checkbox-group">
                    <div class="form-checkbox">
                        <input type="radio" name="delivery_method" id="delivery_enabled" value="delivery" checked>
                        <label for="delivery_enabled">택배</label>
                    </div>
                    <div class="form-checkbox" style="display: flex; align-items: center; gap: 8px;">
                        <input type="radio" name="delivery_method" id="visit_enabled" value="visit">
                        <label for="visit_enabled" style="margin: 0; cursor: pointer;">내방</label>
                        <input type="text" name="visit_region" id="visit_region" class="form-control" placeholder="영등포 강남" maxlength="8" style="width: 150px; padding: 8px 12px; font-size: 14px; margin: 0; opacity: 0.5; background-color: #f3f4f6; cursor: pointer;" tabindex="-1">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 부가서비스 및 유지기간 -->
        <div class="form-section">
            <div class="form-section-title">부가서비스 및 유지기간</div>
            
            <div class="form-group">
                <label class="form-label" for="promotion_title">
                    제목
                </label>
                <input type="text" name="promotion_title" id="promotion_title" class="form-control" placeholder="부가서비스 및 유지기간" maxlength="100">
            </div>
            
            <div class="form-group">
                <label class="form-label">항목</label>
                <div id="promotion-container">
                    <div class="gift-input-group">
                        <input type="text" name="promotions[]" class="form-control" placeholder="부가 미가입시 +10" maxlength="30">
                        <button type="button" class="btn-add" onclick="addPromotionField()">추가</button>
                    </div>
                    <div class="gift-input-group">
                        <input type="text" name="promotions[]" class="form-control" placeholder="파손보험 5700원" maxlength="30">
                        <button type="button" class="btn-remove" onclick="removePromotionField(this)">삭제</button>
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
document.addEventListener('DOMContentLoaded', function() {
    
    // 방문시 텍스트 필드 활성화/비활성화
    const deliveryRadio = document.getElementById('delivery_enabled');
    const visitRadio = document.getElementById('visit_enabled');
    const visitRegionInput = document.getElementById('visit_region');
    
    function toggleVisitRegionInput() {
        if (visitRegionInput) {
            if (visitRadio && visitRadio.checked) {
                // 내방 선택 시: 모든 제한 제거, 입력 가능하도록
                visitRegionInput.removeAttribute('readonly');
                visitRegionInput.removeAttribute('disabled');
                visitRegionInput.removeAttribute('tabindex');
                visitRegionInput.style.opacity = '1';
                visitRegionInput.style.backgroundColor = '#ffffff';
                visitRegionInput.style.cursor = 'text';
                visitRegionInput.style.pointerEvents = 'auto';
                // 포커스 주기
                setTimeout(function() {
                    visitRegionInput.focus();
                }, 100);
            } else {
                // 택배 선택 시: 입력 불가 상태로 (하지만 클릭은 받을 수 있도록)
                visitRegionInput.setAttribute('readonly', 'readonly');
                visitRegionInput.setAttribute('tabindex', '-1');
                visitRegionInput.style.opacity = '0.5';
                visitRegionInput.style.backgroundColor = '#f3f4f6';
                visitRegionInput.style.cursor = 'pointer'; // 클릭 가능하다는 표시
                visitRegionInput.style.pointerEvents = 'auto'; // 클릭 이벤트는 받을 수 있도록
                // 택배 선택 시 입력값 초기화
                visitRegionInput.value = '';
                visitRegionInput.blur();
            }
        }
    }
    
    // 라디오 버튼 변경 이벤트
    if (deliveryRadio) {
        deliveryRadio.addEventListener('change', toggleVisitRegionInput);
    }
    
    if (visitRadio) {
        visitRadio.addEventListener('change', toggleVisitRegionInput);
    }
    
    // 텍스트 필드 클릭 이벤트 (내방 선택 시에만 작동)
    if (visitRegionInput) {
        visitRegionInput.addEventListener('click', function(e) {
            // 텍스트 필드 클릭 시 라디오 버튼 자동 선택
            if (visitRadio && !visitRadio.checked) {
                visitRadio.checked = true;
                toggleVisitRegionInput();
                e.preventDefault();
                e.stopPropagation();
            } else if (visitRadio && visitRadio.checked) {
                // 이미 선택된 경우 포커스만
                this.focus();
            }
        });
        
        visitRegionInput.addEventListener('mousedown', function(e) {
            // 마우스 다운 시에도 라디오 버튼 선택
            if (visitRadio && !visitRadio.checked) {
                visitRadio.checked = true;
                toggleVisitRegionInput();
                e.preventDefault();
                e.stopPropagation();
            }
        });
        
        visitRegionInput.addEventListener('focus', function() {
            // 텍스트 필드 포커스 시 라디오 버튼 자동 선택
            if (visitRadio && !visitRadio.checked) {
                visitRadio.checked = true;
                toggleVisitRegionInput();
            }
        });
    }
    
    // 초기 상태 설정
    toggleVisitRegionInput();
    
    // 단말기 출고가: 정수 8자리, 천단위 콤마 표시
    const devicePrice = document.getElementById('device_price');
    if (devicePrice) {
        devicePrice.addEventListener('input', function() {
            // 숫자만 입력 (콤마 제거)
            let value = this.value.replace(/[^0-9]/g, '');
            // 8자리 제한
            if (value.length > 8) {
                value = value.slice(0, 8);
            }
            // 천단위 콤마 추가
            if (value) {
                this.value = parseInt(value).toLocaleString('ko-KR');
            } else {
                this.value = '';
            }
        });
        
        devicePrice.addEventListener('focus', function() {
            // 포커스 시 콤마 제거
            this.value = this.value.replace(/,/g, '');
        });
        
        devicePrice.addEventListener('blur', function() {
            // 포커스 해제 시 콤마 추가
            let value = this.value.replace(/[^0-9]/g, '');
            if (value) {
                this.value = parseInt(value).toLocaleString('ko-KR');
            }
        });
    }
    
    // 할인 필드들: 정수 4자리 소수 2자리 (최대 9999.99)
    function initDiscountField(field) {
        field.addEventListener('input', function() {
            let value = this.value.replace(/[^0-9.-]/g, '');
            const sign = value.match(/^-/);
            value = value.replace(/[-]/g, '');
            
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            if (parts[0] && parts[0].length > 4) {
                value = parts[0].slice(0, 4) + (parts[1] ? '.' + parts[1] : '');
            }
            if (parts[1] && parts[1].length > 2) {
                value = parts[0] + '.' + parts[1].slice(0, 2);
            }
            
            if (sign) {
                value = '-' + value;
            }
            
            this.value = value;
        });
    }
    
    // 초기 할인 필드들에 이벤트 리스너 추가
    document.querySelectorAll('.common-discount-input, .contract-discount-input').forEach(function(field) {
        initDiscountField(field);
    });
});

function addPromotionField() {
    const container = document.getElementById('promotion-container');
    const newField = document.createElement('div');
    newField.className = 'gift-input-group';
    const placeholders = ['부가 미가입시 +10', '파손보험 5700원'];
    const placeholderIndex = (container.children.length - 1) % placeholders.length;
    newField.innerHTML = `
        <input type="text" name="promotions[]" class="form-control" placeholder="${placeholders[placeholderIndex]}" maxlength="30">
        <button type="button" class="btn-remove" onclick="removePromotionField(this)">삭제</button>
    `;
    container.appendChild(newField);
}

function removePromotionField(button) {
    const container = document.getElementById('promotion-container');
    if (container.children.length > 1) {
        button.parentElement.remove();
    }
}

document.getElementById('productForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/MVNO/api/product-register-mno.php', {
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

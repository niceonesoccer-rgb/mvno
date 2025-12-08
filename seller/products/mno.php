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
                        <label for="visit_enabled" style="margin: 0;">내방</label>
                        <input type="text" name="visit_region" id="visit_region" class="form-control" placeholder="영등포 강남" maxlength="8" style="width: 150px; padding: 8px 12px; font-size: 14px; margin: 0; opacity: 0.5; background-color: #f3f4f6;" tabindex="-1">
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
// 약정기간 직접입력 필드 토글
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
                    visitRegionInput.click(); // 클릭 이벤트도 트리거
                }, 100);
            } else {
                // 택배 선택 시: 입력 불가 상태로
                visitRegionInput.setAttribute('readonly', 'readonly');
                visitRegionInput.setAttribute('tabindex', '-1');
                visitRegionInput.style.opacity = '0.5';
                visitRegionInput.style.backgroundColor = '#f3f4f6';
                visitRegionInput.style.cursor = 'default';
                visitRegionInput.style.pointerEvents = 'none';
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
            if (visitRadio && visitRadio.checked) {
                this.focus();
            } else {
                e.preventDefault();
                e.stopPropagation();
            }
        });
        
        visitRegionInput.addEventListener('focus', function() {
            if (!visitRadio || !visitRadio.checked) {
                this.blur();
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
    
    // 할인 필드들: 정수 4자리 소수 2자리 (최대 9999.99) - 동적으로 추가된 필드에도 적용
    function initDiscountField(field) {
        field.addEventListener('input', function() {
            let value = this.value.replace(/[^0-9.-]/g, '');
            // 부호는 맨 앞에만 허용 (-만 허용)
            const sign = value.match(/^-/);
            value = value.replace(/[-]/g, '');
            
            // 소수점이 하나만 있도록 제한
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            // 정수 부분 4자리 제한
            if (parts[0] && parts[0].length > 4) {
                value = parts[0].slice(0, 4) + (parts[1] ? '.' + parts[1] : '');
            }
            // 소수 부분 2자리 제한
            if (parts[1] && parts[1].length > 2) {
                value = parts[0] + '.' + parts[1].slice(0, 2);
            }
            
            // 부호 복원 (-만)
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


function initDiscountField(field) {
    field.addEventListener('input', function() {
        let value = this.value.replace(/[^0-9.-]/g, '');
        // 부호는 맨 앞에만 허용 (-만 허용)
        const sign = value.match(/^-/);
        value = value.replace(/[-]/g, '');
        
        // 소수점이 하나만 있도록 제한
        const parts = value.split('.');
        if (parts.length > 2) {
            value = parts[0] + '.' + parts.slice(1).join('');
        }
        // 정수 부분 4자리 제한
        if (parts[0] && parts[0].length > 4) {
            value = parts[0].slice(0, 4) + (parts[1] ? '.' + parts[1] : '');
        }
        // 소수 부분 2자리 제한
        if (parts[1] && parts[1].length > 2) {
            value = parts[0] + '.' + parts[1].slice(0, 2);
        }
        
        // 부호 복원 (-만)
        if (sign) {
            value = '-' + value;
        }
        
        this.value = value;
    });
}

let promotionCount = 3;

function addPromotionField() {
    const container = document.getElementById('promotion-container');
    const newField = document.createElement('div');
    newField.className = 'gift-input-group';
    const placeholders = ['부가 미가입시 +10', '파손보험 5700원'];
    const placeholderIndex = (promotionCount - 3) % placeholders.length;
    newField.innerHTML = `
        <input type="text" name="promotions[]" class="form-control" placeholder="${placeholders[placeholderIndex]}" maxlength="30">
        <button type="button" class="btn-remove" onclick="removePromotionField(this)">삭제</button>
    `;
    container.appendChild(newField);
    promotionCount++;
}

function removePromotionField(button) {
    const container = document.getElementById('promotion-container');
    if (container.children.length > 1) {
        button.parentElement.remove();
    }
}

// 방문 가능 지역 추가/삭제 함수
function addDeliveryRegionRow() {
    const container = document.getElementById('delivery-region-container');
    const newRow = document.createElement('div');
    newRow.className = 'delivery-region-row';
    newRow.style.cssText = 'display: flex; gap: 16px; align-items: flex-start; margin-bottom: 16px;';
    newRow.innerHTML = `
        <div style="flex: 1;">
            <label class="form-label">
                시/도
            </label>
            <input type="text" name="delivery_sido[]" class="form-control" placeholder="시/도 입력 (예: 서울특별시)" maxlength="50">
        </div>
        
        <div style="flex: 1;">
            <label class="form-label">
                시/군/구
            </label>
            <input type="text" name="delivery_sigungu[]" class="form-control" placeholder="시/군/구 입력" maxlength="50">
        </div>
        
        <div style="flex: 1;">
            <label class="form-label">
                읍/면/동
            </label>
            <input type="text" name="delivery_eupmyeondong[]" class="form-control" placeholder="읍/면/동 입력" maxlength="50">
        </div>
        
        <div style="display: flex; align-items: flex-end; padding-bottom: 0;">
            <button type="button" class="btn-remove" onclick="removeDeliveryRegionRow(this)" style="margin-top: 0;">삭제</button>
        </div>
    `;
    container.appendChild(newRow);
}

function removeDeliveryRegionRow(button) {
    const container = document.getElementById('delivery-region-container');
    if (container.children.length > 1) {
        button.closest('.delivery-region-row').remove();
    } else {
        alert('최소 하나의 지역은 입력해야 합니다.');
    }
}

let benefitCount = 1;

function addBenefitField() {
    const container = document.getElementById('benefits-container');
    const newField = document.createElement('div');
    newField.className = 'gift-input-group';
    newField.innerHTML = `
        <textarea name="benefits[]" class="form-textarea" style="min-height: 80px;" placeholder="혜택 및 유의사항을 입력하세요"></textarea>
    `;
    container.appendChild(newField);
    benefitCount++;
}

function removeBenefitField(button) {
    const container = document.getElementById('benefits-container');
    if (container.children.length > 1) {
        button.parentElement.remove();
    }
}

document.getElementById('productForm').addEventListener('submit', function(e) {
    '흰색': '#ffffff',
    'white': '#ffffff',
    '레드': '#dc2626',
    '빨강': '#dc2626',
    'red': '#dc2626',
    '그린': '#10b981',
    '초록': '#10b981',
    'green': '#10b981',
    '블루': '#3b82f6',
    '파랑': '#3b82f6',
    'blue': '#3b82f6',
    '옐로우': '#fbbf24',
    '노랑': '#fbbf24',
    'yellow': '#fbbf24',
    '퍼플': '#a855f7',
    '보라': '#a855f7',
    'purple': '#a855f7',
    '오렌지': '#f97316',
    '주황': '#f97316',
    'orange': '#f97316',
    '핑크': '#ec4899',
    '분홍': '#ec4899',
    'pink': '#ec4899',
    
    // 특수 색상
    '코랄레드': '#FF7F50',
    '코랄': '#FF7F50',
    'coral': '#FF7F50',
    'coralred': '#FF7F50',
    '제트블랙': '#1f2937',
    'jetblack': '#1f2937',
    'jet black': '#1f2937',
    '블루 쉐도우': '#1e3a8a',
    '블루쉐도우': '#1e3a8a',
    'blueshadow': '#1e3a8a',
    'blue shadow': '#1e3a8a',
    '실버 쉐도우': '#d1d5db',
    '실버쉐도우': '#d1d5db',
    'silver': '#d1d5db',
    'silvershadow': '#d1d5db',
    'silver shadow': '#d1d5db',
    '골드': '#fbbf24',
    'gold': '#fbbf24',
    '로즈골드': '#e8b4b8',
    '로즈골': '#e8b4b8',
    'rosegold': '#e8b4b8',
    'rose gold': '#e8b4b8',
    '스페이스 그레이': '#6b7280',
    '스페이스그레이': '#6b7280',
    'spacegray': '#6b7280',
    'space gray': '#6b7280',
    '미드나잇 그린': '#065f46',
    '미드나잇그린': '#065f46',
    'midnightgreen': '#065f46',
    'midnight green': '#065f46',
    '그래파이트': '#374151',
    'graphite': '#374151',
    '시에라 블루': '#3b82f6',
    '시에라블루': '#3b82f6',
    'sierablue': '#3b82f6',
    'sierra blue': '#3b82f6',
    '딥퍼플': '#7c3aed',
    '딥 퍼플': '#7c3aed',
    'deeppurple': '#7c3aed',
    'deep purple': '#7c3aed',
    '스타라이트': '#fbbf24',
    'starlight': '#fbbf24',
    '프로': '#000000',
    'pro': '#000000',
    '맥스': '#000000',
    'max': '#000000',
    '미니': '#ffffff',
    'mini': '#ffffff',
    '에어': '#d1d5db',
    'air': '#d1d5db',
    
    // 회색 계열
    '그레이': '#6b7280',
    '회색': '#6b7280',
    'gray': '#6b7280',
    'grey': '#6b7280',
    '다크그레이': '#374151',
    '다크 그레이': '#374151',
    'darkgray': '#374151',
    'dark gray': '#374151',
    '라이트그레이': '#d1d5db',
    '라이트 그레이': '#d1d5db',
    'lightgray': '#d1d5db',
    'light gray': '#d1d5db',
    
    // 기타
    '네이비': '#1e3a8a',
    'navy': '#1e3a8a',
    '마린': '#1e40af',
    'marine': '#1e40af',
    '터키': '#06b6d4',
    'turquoise': '#06b6d4',
    '민트': '#10b981',
    'mint': '#10b981',
    '라벤더': '#a78bfa',
    'lavender': '#a78bfa',
    '바이올렛': '#8b5cf6',
    'violet': '#8b5cf6',
    '인디고': '#6366f1',
    'indigo': '#6366f1',
    '시안': '#06b6d4',
    'cyan': '#06b6d4',
    '마젠타': '#d946ef',
    'magenta': '#d946ef',
    '라임': '#84cc16',
    'lime': '#84cc16',
    '앰버': '#f59e0b',
    'amber': '#f59e0b',
    '티얼': '#14b8a6',
    'teal': '#14b8a6',
    '에메랄드': '#10b981',
    'emerald': '#10b981',
    '스칼렛': '#ef4444',
    'scarlet': '#ef4444',
    '크림슨': '#dc2626',
    'crimson': '#dc2626',
    '버건디': '#991b1b',
    'burgundy': '#991b1b',
    '새먼': '#fa8072',
    'salmon': '#fa8072',
    '피치': '#ffcba4',
    'peach': '#ffcba4',
    '베이지': '#f5f5dc',
    'beige': '#f5f5dc',
    '아이보리': '#fffff0',
    'ivory': '#fffff0',
    '크림': '#fffdd0',
    'cream': '#fffdd0',
    '카키': '#c3b091',
    'khaki': '#c3b091',
    '올리브': '#808000',
    'olive': '#808000',
    '차콜': '#36454f',
    'charcoal': '#36454f',
    '슬레이트': '#708090',
    'slate': '#708090',
    '스틸': '#4682b4',
    'steel': '#4682b4',
    '브론즈': '#cd7f32',
    'bronze': '#cd7f32',
    '코퍼': '#b87333',
    'copper': '#b87333',
    '실버': '#c0c0c0',
    'silver': '#c0c0c0',
    '플래티넘': '#e5e4e2',
    'platinum': '#e5e4e2',
    '타이타늄': '#878681',
    'titanium': '#878681',
    '알루미늄': '#848789',
    'aluminum': '#848789',
    '알루미니움': '#848789',
    'aluminium': '#848789',
    
    // 스마트폰 인기 색상
    '네추럴 티타늄': '#f5f5f0',
    '네추럴티타늄': '#f5f5f0',
    'naturaltitanium': '#f5f5f0',
    'natural titanium': '#f5f5f0',
    '블루 티타늄': '#5f9ea0',
    '블루티타늄': '#5f9ea0',
    'bluetitanium': '#5f9ea0',
    'blue titanium': '#5f9ea0',
    '화이트 티타늄': '#e8e8e0',
    '화이트티타늄': '#e8e8e0',
    'whitetitanium': '#e8e8e0',
    'white titanium': '#e8e8e0',
    '블랙 티타늄': '#2c2c2c',
    '블랙티타늄': '#2c2c2c',
    'blacktitanium': '#2c2c2c',
    'black titanium': '#2c2c2c',
    '프로맥스': '#1a1a1a',
    'promax': '#1a1a1a',
    '프로 맥스': '#1a1a1a',
    '프로맥스 블랙': '#000000',
    'promaxblack': '#000000',
    '프로맥스 블루': '#007aff',
    'promaxblue': '#007aff',
    '프로맥스 골드': '#ffd700',
    'promaxgold': '#ffd700',
    '프로맥스 네추럴': '#f5f5f0',
    'promaxnatural': '#f5f5f0',
    '아이폰 블랙': '#000000',
    'iphoneblack': '#000000',
    '아이폰 화이트': '#ffffff',
    'iphonewhite': '#ffffff',
    '아이폰 레드': '#ff3b30',
    'iphonered': '#ff3b30',
    '아이폰 블루': '#007aff',
    'iphoneblue': '#007aff',
    '아이폰 그린': '#34c759',
    'iphonegreen': '#34c759',
    '아이폰 옐로우': '#ffcc00',
    'iphoneyellow': '#ffcc00',
    '아이폰 오렌지': '#ff9500',
    'iphoneorange': '#ff9500',
    '아이폰 퍼플': '#af52de',
    'iphonepurple': '#af52de',
    '아이폰 핑크': '#ff2d55',
    'iphonepink': '#ff2d55',
    
    // 갤럭시 인기 색상
    '팬텀 블랙': '#000000',
    '팬텀블랙': '#000000',
    'phantomblack': '#000000',
    'phantom black': '#000000',
    '팬텀 화이트': '#ffffff',
    '팬텀화이트': '#ffffff',
    'phantomwhite': '#ffffff',
    'phantom white': '#ffffff',
    '팬텀 그린': '#00d9a5',
    '팬텀그린': '#00d9a5',
    'phantomgreen': '#00d9a5',
    'phantom green': '#00d9a5',
    '팬텀 핑크': '#ffc0cb',
    '팬텀핑크': '#ffc0cb',
    'phantompink': '#ffc0cb',
    'phantom pink': '#ffc0cb',
    '팬텀 실버': '#c0c0c0',
    '팬텀실버': '#c0c0c0',
    'phantomsilver': '#c0c0c0',
    'phantom silver': '#c0c0c0',
    '팬텀 바이올렛': '#8b5cf6',
    '팬텀바이올렛': '#8b5cf6',
    'phantomviolet': '#8b5cf6',
    'phantom violet': '#8b5cf6',
    '팬텀 브라운': '#8b4513',
    '팬텀브라운': '#8b4513',
    'phantombrown': '#8b4513',
    'phantom brown': '#8b4513',
    '팬텀 레드': '#dc2626',
    '팬텀레드': '#dc2626',
    'phantomred': '#dc2626',
    'phantom red': '#dc2626',
    '팬텀 블루': '#3b82f6',
    '팬텀블루': '#3b82f6',
    'phantomblue': '#3b82f6',
    'phantom blue': '#3b82f6',
    '아우라 블루': '#0066cc',
    '아우라블루': '#0066cc',
    'aurablue': '#0066cc',
    'aura blue': '#0066cc',
    '아우라 레드': '#cc0000',
    '아우라레드': '#cc0000',
    'aurared': '#cc0000',
    'aura red': '#cc0000',
    '아우라 핑크': '#ff69b4',
    '아우라핑크': '#ff69b4',
    'aurapink': '#ff69b4',
    'aura pink': '#ff69b4',
    '아우라 화이트': '#ffffff',
    '아우라화이트': '#ffffff',
    'aurawhite': '#ffffff',
    'aura white': '#ffffff',
    '아우라 블랙': '#000000',
    '아우라블랙': '#000000',
    'aurablack': '#000000',
    'aura black': '#000000',
    '아우라 그린': '#00d9a5',
    '아우라그린': '#00d9a5',
    'auragreen': '#00d9a5',
    'aura green': '#00d9a5',
    '아우라 실버': '#c0c0c0',
    '아우라실버': '#c0c0c0',
    'aurasilver': '#c0c0c0',
    'aura silver': '#c0c0c0',
    '아우라 바이올렛': '#8b5cf6',
    '아우라바이올렛': '#8b5cf6',
    'auraviolet': '#8b5cf6',
    'aura violet': '#8b5cf6',
    '아우라 브라운': '#8b4513',
    '아우라브라운': '#8b4513',
    'aurabrown': '#8b4513',
    'aura brown': '#8b4513',
    '아우라 레드': '#dc2626',
    '아우라레드': '#dc2626',
    'aurared': '#dc2626',
    'aura red': '#dc2626',
    '아우라 오렌지': '#ff9500',
    '아우라오렌지': '#ff9500',
    'auraorange': '#ff9500',
    'aura orange': '#ff9500',
    '아우라 옐로우': '#ffcc00',
    '아우라옐로우': '#ffcc00',
    'aurayellow': '#ffcc00',
    'aura yellow': '#ffcc00',
    '아우라 골드': '#ffd700',
    '아우라골드': '#ffd700',
    'auragold': '#ffd700',
    'aura gold': '#ffd700',
    '아우라 티타늄': '#878681',
    '아우라티타늄': '#878681',
    'auratitanium': '#878681',
    'aura titanium': '#878681',
    '아우라 그레이': '#6b7280',
    '아우라그레이': '#6b7280',
    'auragray': '#6b7280',
    'aura gray': '#6b7280',
    
    // 추가 일반 색상
    '브라운': '#8b4513',
    '갈색': '#8b4513',
    'brown': '#8b4513',
    '다크 브라운': '#654321',
    '다크브라운': '#654321',
    'darkbrown': '#654321',
    'dark brown': '#654321',
    '라이트 브라운': '#d2b48c',
    '라이트브라운': '#d2b48c',
    'lightbrown': '#d2b48c',
    'light brown': '#d2b48c',
    '초콜릿': '#7b3f00',
    'chocolate': '#7b3f00',
    '카라멜': '#af6e4d',
    'caramel': '#af6e4d',
    '모카': '#6f4e37',
    'mocha': '#6f4e37',
    '에스프레소': '#4a3728',
    'espresso': '#4a3728',
    '탄': '#2f1b14',
    'tan': '#2f1b14',
    '베이지 브라운': '#a0826d',
    '베이지브라운': '#a0826d',
    'beigebrown': '#a0826d',
    'beige brown': '#a0826d',
    '샌드': '#c2b280',
    'sand': '#c2b280',
    '바닐라': '#f3e5ab',
    'vanilla': '#f3e5ab',
    '카푸치노': '#4e433f',
    'cappuccino': '#4e433f',
    '라떼': '#c19a6b',
    'latte': '#c19a6b',
    
    // 파스텔 색상
    '파스텔 블루': '#aec6cf',
    '파스텔블루': '#aec6cf',
    'pastelblue': '#aec6cf',
    'pastel blue': '#aec6cf',
    '파스텔 핑크': '#ffb6c1',
    '파스텔핑크': '#ffb6c1',
    'pastelpink': '#ffb6c1',
    'pastel pink': '#ffb6c1',
    '파스텔 그린': '#b2d4b2',
    '파스텔그린': '#b2d4b2',
    'pastelgreen': '#b2d4b2',
    'pastel green': '#b2d4b2',
    '파스텔 옐로우': '#fffacd',
    '파스텔옐로우': '#fffacd',
    'pastelyellow': '#fffacd',
    'pastel yellow': '#fffacd',
    '파스텔 퍼플': '#dda0dd',
    '파스텔퍼플': '#dda0dd',
    'pastelpurple': '#dda0dd',
    'pastel purple': '#dda0dd',
    '파스텔 오렌지': '#ffcc99',
    '파스텔오렌지': '#ffcc99',
    'pastelorange': '#ffcc99',
    'pastel orange': '#ffcc99',
    '파스텔 레드': '#ff9999',
    '파스텔레드': '#ff9999',
    'pastelred': '#ff9999',
    'pastel red': '#ff9999',
    
    // 네온 색상
    '네온 그린': '#39ff14',
    '네온그린': '#39ff14',
    'neongreen': '#39ff14',
    'neon green': '#39ff14',
    '네온 블루': '#00ffff',
    '네온블루': '#00ffff',
    'neonblue': '#00ffff',
    'neon blue': '#00ffff',
    '네온 핑크': '#ff1493',
    '네온핑크': '#ff1493',
    'neonpink': '#ff1493',
    'neon pink': '#ff1493',
    '네온 옐로우': '#ffff00',
    '네온옐로우': '#ffff00',
    'neonyellow': '#ffff00',
    'neon yellow': '#ffff00',
    '네온 오렌지': '#ff6600',
    '네온오렌지': '#ff6600',
    'neonorange': '#ff6600',
    'neon orange': '#ff6600',
    '네온 레드': '#ff073a',
    '네온레드': '#ff073a',
    'neonred': '#ff073a',
    'neon red': '#ff073a',
    '네온 퍼플': '#bf00ff',
    '네온퍼플': '#bf00ff',
    'neonpurple': '#bf00ff',
    'neon purple': '#bf00ff',
    
    // 메탈릭 색상
    '메탈릭 블루': '#32527b',
    '메탈릭블루': '#32527b',
    'metallicblue': '#32527b',
    'metallic blue': '#32527b',
    '메탈릭 실버': '#c0c0c0',
    '메탈릭실버': '#c0c0c0',
    'metallicsilver': '#c0c0c0',
    'metallic silver': '#c0c0c0',
    '메탈릭 골드': '#ffd700',
    '메탈릭골드': '#ffd700',
    'metallicgold': '#ffd700',
    'metallic gold': '#ffd700',
    '메탈릭 로즈': '#e8b4b8',
    '메탈릭로즈': '#e8b4b8',
    'metallicrose': '#e8b4b8',
    'metallic rose': '#e8b4b8',
    '메탈릭 그린': '#2d5016',
    '메탈릭그린': '#2d5016',
    'metallicgreen': '#2d5016',
    'metallic green': '#2d5016',
    '메탈릭 레드': '#8b0000',
    '메탈릭레드': '#8b0000',
    'metallicred': '#8b0000',
    'metallic red': '#8b0000',
    '메탈릭 블랙': '#1a1a1a',
    '메탈릭블랙': '#1a1a1a',
    'metallicblack': '#1a1a1a',
    'metallic black': '#1a1a1a',
    
    // 어두운 색상
    '다크 블루': '#00008b',
    '다크블루': '#00008b',
    'darkblue': '#00008b',
    'dark blue': '#00008b',
    '다크 그린': '#006400',
    '다크그린': '#006400',
    'darkgreen': '#006400',
    'dark green': '#006400',
    '다크 레드': '#8b0000',
    '다크레드': '#8b0000',
    'darkred': '#8b0000',
    'dark red': '#8b0000',
    '다크 퍼플': '#4b0082',
    '다크퍼플': '#4b0082',
    'darkpurple': '#4b0082',
    'dark purple': '#4b0082',
    '다크 오렌지': '#ff8c00',
    '다크오렌지': '#ff8c00',
    'darkorange': '#ff8c00',
    'dark orange': '#ff8c00',
    '다크 핑크': '#c71585',
    '다크핑크': '#c71585',
    'darkpink': '#c71585',
    'dark pink': '#c71585',
    '다크 옐로우': '#b8860b',
    '다크옐로우': '#b8860b',
    'darkyellow': '#b8860b',
    'dark yellow': '#b8860b',
    
    // 밝은 색상
    '라이트 블루': '#add8e6',
    '라이트블루': '#add8e6',
    'lightblue': '#add8e6',
    'light blue': '#add8e6',
    '라이트 그린': '#90ee90',
    '라이트그린': '#90ee90',
    'lightgreen': '#90ee90',
    'light green': '#90ee90',
    '라이트 레드': '#ffcccb',
    '라이트레드': '#ffcccb',
    'lightred': '#ffcccb',
    'light red': '#ffcccb',
    '라이트 퍼플': '#dda0dd',
    '라이트퍼플': '#dda0dd',
    'lightpurple': '#dda0dd',
    'light purple': '#dda0dd',
    '라이트 오렌지': '#ffd580',
    '라이트오렌지': '#ffd580',
    'lightorange': '#ffd580',
    'light orange': '#ffd580',
    '라이트 핑크': '#ffb6c1',
    '라이트핑크': '#ffb6c1',
    'lightpink': '#ffb6c1',
    'light pink': '#ffb6c1',
    '라이트 옐로우': '#ffffe0',
    '라이트옐로우': '#ffffe0',
    'lightyellow': '#ffffe0',
    'light yellow': '#ffffe0',
    
    // 기타 인기 색상
    '로즈': '#ff007f',
    'rose': '#ff007f',
    '체리': '#de3163',
    'cherry': '#de3163',
    '와인': '#722f37',
    'wine': '#722f37',
    '라즈베리': '#e30b5d',
    'raspberry': '#e30b5d',
    '스트로베리': '#fc5a8d',
    'strawberry': '#fc5a8d',
    '워터멜론': '#fc6c85',
    'watermelon': '#fc6c85',
    '코랄': '#ff7f50',
    'coral': '#ff7f50',
    '터콰이즈': '#40e0d0',
    'turquoise': '#40e0d0',
    '아쿠아': '#00ffff',
    'aqua': '#00ffff',
    '스카이': '#87ceeb',
    'sky': '#87ceeb',
    '오션': '#006994',
    'ocean': '#006994',
    '마린 블루': '#013220',
    'marineblue': '#013220',
    'marine blue': '#013220',
    '포레스트': '#228b22',
    'forest': '#228b22',
    '에메랄드 그린': '#50c878',
    '에메랄드그린': '#50c878',
    'emeraldgreen': '#50c878',
    'emerald green': '#50c878',
    '민트 그린': '#98ff98',
    '민트그린': '#98ff98',
    'mintgreen': '#98ff98',
    'mint green': '#98ff98',
    '라임 그린': '#32cd32',
    '라임그린': '#32cd32',
    'limegreen': '#32cd32',
    'lime green': '#32cd32',
    '올리브 그린': '#808000',
    '올리브그린': '#808000',
    'olivegreen': '#808000',
    'olive green': '#808000',
    '차콜 그레이': '#36454f',
    '차콜그레이': '#36454f',
    'charcoalgray': '#36454f',
    'charcoal gray': '#36454f',
    '슬레이트 그레이': '#708090',
    '슬레이트그레이': '#708090',
    'slategray': '#708090',
    'slate gray': '#708090',
    '스틸 그레이': '#71797e',
    '스틸그레이': '#71797e',
    'steelgray': '#71797e',
    'steel gray': '#71797e',
    '아이언': '#4e4e4e',
    'iron': '#4e4e4e',
    '건': '#2c3539',
    'gun': '#2c3539',
    '건메탈': '#2c3439',
    'gunmetal': '#2c3439',
    '건 메탈': '#2c3439',
    '애쉬': '#b2beb5',
    'ash': '#b2beb5',
    '스모키': '#605b56',
    'smoky': '#605b56',
    '스모크': '#605b56',
    'smoke': '#605b56',
    '머스타드': '#ffdb58',
    'mustard': '#ffdb58',
    '허니': '#ffc30b',
    'honey': '#ffc30b',
    '선셋': '#ff7f50',
    'sunset': '#ff7f50',
    '선라이즈': '#ff6347',
    'sunrise': '#ff6347',
    '새벽': '#ff6b6b',
    'dawn': '#ff6b6b',
    '트와일라잇': '#4e5180',
    'twilight': '#4e5180',
    '미드나잇': '#191970',
    'midnight': '#191970',
    '섀도우': '#2f2f2f',
    'shadow': '#2f2f2f',
    '온': '#ffffff',
    'on': '#ffffff',
    '오프': '#000000',
    'off': '#000000',
    '온오프': '#6b7280',
    'onoff': '#6b7280',
    '온 오프': '#6b7280',
    '온오프 화이트': '#ffffff',
    'onoffwhite': '#ffffff',
    '온오프 블랙': '#000000',
    'onoffblack': '#000000',
    '온오프 그레이': '#6b7280',
    'onoffgray': '#6b7280',
    '온오프 그레이': '#6b7280'
};

// 색상 이름으로 색상 찾기
function findColorByName(name) {
    if (!name) return null;
    
    // 입력값 정규화: 소문자 변환, 공백 제거, 특수문자 제거
    const normalizedName = name.toLowerCase().trim().replace(/\s+/g, '').replace(/[^a-z가-힣0-9]/g, '');
    
    // 정확한 매칭 (공백 제거한 버전)
    if (colorNameMap[normalizedName]) {
        return colorNameMap[normalizedName];
    }
    
    // 모든 키를 정규화하여 비교
    for (const key in colorNameMap) {
        const normalizedKey = key.toLowerCase().replace(/\s+/g, '').replace(/[^a-z가-힣0-9]/g, '');
        
        // 정확한 매칭
        if (normalizedName === normalizedKey) {
            return colorNameMap[key];
        }
        
        // 부분 매칭 (입력값이 키에 포함되거나, 키가 입력값에 포함되는 경우)
        if (normalizedName.includes(normalizedKey) || normalizedKey.includes(normalizedName)) {
            // 최소 3글자 이상 일치하는 경우만 매칭
            if (normalizedKey.length >= 3 && normalizedName.length >= 3) {
                return colorNameMap[key];
            }
        }
    }
    
    // 유사도 기반 매칭 (오타 허용)
    let bestMatch = null;
    let bestScore = 0;
    
    for (const key in colorNameMap) {
        const normalizedKey = key.toLowerCase().replace(/\s+/g, '').replace(/[^a-z가-힣0-9]/g, '');
        
        // 레벤슈타인 거리 기반 유사도 계산 (간단한 버전)
        const similarity = calculateSimilarity(normalizedName, normalizedKey);
        
        if (similarity > bestScore && similarity > 0.6) { // 60% 이상 유사하면 매칭
            bestScore = similarity;
            bestMatch = colorNameMap[key];
        }
    }
    
    return bestMatch;
}

// 간단한 유사도 계산 함수
function calculateSimilarity(str1, str2) {
    if (str1 === str2) return 1.0;
    if (str1.length === 0 || str2.length === 0) return 0.0;
    
    // 공통 문자 개수 계산
    let commonChars = 0;
    const minLength = Math.min(str1.length, str2.length);
    const maxLength = Math.max(str1.length, str2.length);
    
    for (let i = 0; i < minLength; i++) {
        if (str1[i] === str2[i]) {
            commonChars++;
        }
    }
    
    // 시작 부분 일치 가중치
    let prefixMatch = 0;
    for (let i = 0; i < minLength; i++) {
        if (str1[i] === str2[i]) {
            prefixMatch++;
        } else {
            break;
        }
    }
    
    // 유사도 계산: 공통 문자 비율 + 시작 일치 가중치
    const baseSimilarity = commonChars / maxLength;
    const prefixBonus = prefixMatch / maxLength * 0.3;
    
    return Math.min(1.0, baseSimilarity + prefixBonus);
}

// 색상 추가
function addColorItem() {
    const container = document.getElementById('color-container');
    const colorItem = document.createElement('div');
    colorItem.className = 'color-item';
    colorItem.style.cssText = 'display: flex; flex-direction: column; align-items: center; gap: 8px;';
    
    const randomColor = '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0');
    
    colorItem.innerHTML = `
        <div class="color-swatch" style="width: 60px; height: 60px; border-radius: 50%; border: 2px solid #e5e7eb; cursor: pointer; position: relative; background-color: ${randomColor}; overflow: hidden;" data-color="${randomColor}">
            <input type="color" class="color-picker" value="${randomColor}" style="position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
            <input type="hidden" name="device_colors[]" value='{"name":"새 색상","color":"${randomColor}"}'>
        </div>
        <input type="text" class="color-name-input" value="새 색상" style="width: 100px; text-align: center; border: 1px solid #e5e7eb; border-radius: 4px; padding: 4px 8px; font-size: 12px;" maxlength="20">
        <button type="button" class="btn-remove-color" onclick="removeColorItem(this)" style="padding: 4px 8px; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 11px;">삭제</button>
    `;
    
    container.appendChild(colorItem);
    
    // 색상 피커 이벤트
    const colorPicker = colorItem.querySelector('.color-picker');
    colorPicker.addEventListener('input', function() {
        const swatch = colorItem.querySelector('.color-swatch');
        const newColor = this.value;
        swatch.style.backgroundColor = newColor;
        swatch.setAttribute('data-color', newColor);
        
        const hiddenInput = colorItem.querySelector('input[type="hidden"]');
        const nameInput = colorItem.querySelector('.color-name-input');
        hiddenInput.value = JSON.stringify({name: nameInput.value, color: newColor});
    });
    
    // 색상 이름 입력 이벤트 (실시간)
    const nameInput = colorItem.querySelector('.color-name-input');
    nameInput.addEventListener('input', function() {
        const swatch = colorItem.querySelector('.color-swatch');
        const colorPicker = colorItem.querySelector('.color-picker');
        const hiddenInput = colorItem.querySelector('input[type="hidden"]');
        
        // 색상 이름으로 색상 찾기 (실시간)
        const foundColor = findColorByName(this.value);
        if (foundColor) {
            // 색상을 찾았으면 즉시 색상 변경
            swatch.style.backgroundColor = foundColor;
            swatch.setAttribute('data-color', foundColor);
            colorPicker.value = foundColor;
        }
        
        // hidden input 실시간 업데이트
        const colorValue = swatch.getAttribute('data-color');
        hiddenInput.value = JSON.stringify({name: this.value, color: colorValue});
    });
    
    // 색상 선택 이벤트 (테두리 표시)
    const swatch = colorItem.querySelector('.color-swatch');
    swatch.addEventListener('click', function(e) {
        // color-picker가 클릭된 경우는 제외
        if (e.target.classList.contains('color-picker')) {
            return;
        }
        selectColor(this);
    });
}

// 색상 삭제
function removeColorItem(button) {
    const colorItem = button.closest('.color-item');
    if (colorItem) {
        colorItem.remove();
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


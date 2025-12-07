<?php
/**
 * 판매자 알뜰폰 상품 등록 페이지
 * 경로: /seller/products/mvno.php
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

// 알뜰폰 권한 확인
$hasPermission = hasSellerPermission($currentUser['user_id'], 'mvno');
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
    
    .input-with-suffix {
        position: relative;
        display: flex;
        align-items: center;
    }
    
    .input-with-suffix .form-control {
        flex: 1;
        padding-right: 40px;
    }
    
    .input-suffix {
        position: absolute;
        right: 16px;
        font-size: 15px;
        color: #374151;
        font-weight: 500;
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
        <h1>초저가 알뜰요금제</h1>
        <p>새로운 알뜰폰 요금제를 등록하세요</p>
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
        <input type="hidden" name="board_type" value="mvno">
        
        <!-- 요금 정보 -->
        <div class="form-section">
            <div class="form-section-title">요금 정보</div>
            
            <!-- 통신사 및 데이터 속도 -->
            <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb;">
                <div class="form-group" style="display: flex; gap: 16px; align-items: flex-end;">
                    <div style="flex: 1;">
                        <label class="form-label" for="provider">
                            통신사 <span class="required">*</span>
                        </label>
                        <select name="provider" id="provider" class="form-select" required>
                            <option value="">선택하세요</option>
                            <option value="SK알뜰폰">SK알뜰폰</option>
                            <option value="KT알뜰폰">KT알뜰폰</option>
                            <option value="LG알뜰폰">LG알뜰폰</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label class="form-label" for="service_type">
                            데이터 속도 <span class="required">*</span>
                        </label>
                        <select name="service_type" id="service_type" class="form-select" required>
                            <option value="">선택하세요</option>
                            <option value="LTE">LTE</option>
                            <option value="5G">5G</option>
                            <option value="6G">6G</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- 요금제 이름 및 유지기간 -->
            <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb;">
                <div class="form-group" style="display: flex; gap: 16px; align-items: flex-start;">
                    <div style="flex: 1;">
                        <label class="form-label" for="plan_name">
                            요금제 이름 <span class="required">*</span>
                        </label>
                        <input type="text" name="plan_name" id="plan_name" class="form-control" required placeholder="평생할인 무제한 요금제" maxlength="30">
                        <div class="form-help">30자 이내로 입력하세요</div>
                    </div>
                    <div style="flex: 1;">
                        <label class="form-label" for="contract_period">
                            요금제 유지기간
                        </label>
                        <select name="contract_period" id="contract_period" class="form-select">
                            <option value="">선택</option>
                            <option value="무약정">무약정</option>
                            <option value="직접입력">직접입력</option>
                        </select>
                        <div class="input-with-suffix" id="contract_period_custom_wrapper" style="display: none; margin-top: 8px;">
                            <input type="number" name="contract_period_custom" id="contract_period_custom" class="form-control" placeholder="93" min="0" required>
                            <span class="input-suffix">일</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 월 납부금, 할인 후 납부금, 할인기간 -->
            <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb;">
                <div class="form-group" style="display: flex; gap: 16px; align-items: flex-start;">
                    <div style="flex: 1;">
                        <label class="form-label" for="price_main">
                            월 납부금 <span class="required">*</span>
                        </label>
                        <div class="input-with-suffix">
                            <input type="number" name="price_main" id="price_main" class="form-control" required placeholder="500" min="0" max="999999" style="padding-right: 45px;">
                            <span class="input-suffix" style="right: 8px;">원</span>
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <label class="form-label" for="price_after">
                            할인 후 납부금
                        </label>
                        <div class="input-with-suffix">
                            <input type="number" name="price_after" id="price_after" class="form-control" placeholder="1500" min="0" max="999999" style="padding-right: 45px;">
                            <span class="input-suffix" style="right: 8px;">원</span>
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <label class="form-label" for="discount_period">
                            할인기간
                        </label>
                        <input type="text" name="discount_period" id="discount_period" class="form-control" placeholder="7개월" maxlength="10">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 데이터 정보 -->
        <div class="form-section">
            <div class="form-section-title">데이터 정보</div>
            
            <!-- 첫 번째 줄: 기본 데이터, 추가 데이터 -->
            <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb;">
                <div class="form-group" style="display: flex; gap: 16px; align-items: flex-start;">
                    <div style="flex: 1;">
                        <label class="form-label" for="data_amount">
                            기본 데이터 <span class="required">*</span>
                        </label>
                        <select name="data_amount" id="data_amount" class="form-select" required>
                            <option value="">선택하세요</option>
                            <option value="무제한">무제한</option>
                            <option value="직접입력">직접입력</option>
                        </select>
                        <div class="input-with-suffix" id="data_amount_custom_wrapper" style="display: none; margin-top: 8px;">
                            <input type="number" name="data_amount_custom" id="data_amount_custom" class="form-control" placeholder="100" min="0" required style="padding-right: 85px;">
                            <div style="position: absolute; right: 8px; display: flex; align-items: center;">
                                <select name="data_amount_unit" id="data_amount_unit" class="form-select" style="width: 65px; padding: 12px 15px 12px 8px; border: none; background: transparent; cursor: pointer; color: #374151; font-size: 15px; font-weight: 500; appearance: none; -webkit-appearance: none; -moz-appearance: none;">
                                    <option value="GB">/ GB</option>
                                    <option value="MB">/ MB</option>
                                </select>
                                <span style="position: absolute; right: 5px; color: #374151; font-size: 10px; pointer-events: none;">▼</span>
                            </div>
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <label class="form-label" for="additional_data">
                            추가 데이터
                        </label>
                        <input type="text" name="additional_data" id="additional_data" class="form-control" placeholder="평생 일 2G" maxlength="10">
                        <div class="form-help">10자 이내로 입력하세요</div>
                    </div>
                </div>
            </div>
            
            <!-- 두 번째 줄: 통화, 부가·영상통화, 문자 -->
            <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb;">
                <div class="form-group" style="display: flex; gap: 16px; align-items: flex-start;">
                    <div style="flex: 1;">
                        <label class="form-label" for="call_type">
                            통화 <span class="required">*</span>
                        </label>
                        <select name="call_type" id="call_type" class="form-select" required>
                            <option value="">선택하세요</option>
                            <option value="무제한">무제한</option>
                            <option value="직접입력">직접입력</option>
                        </select>
                        <div class="input-with-suffix" id="call_type_custom_wrapper" style="display: none; margin-top: 8px;">
                            <input type="number" name="call_type_custom" id="call_type_custom" class="form-control" placeholder="300" min="0" required style="padding-right: 85px;">
                            <div style="position: absolute; right: 8px; display: flex; align-items: center;">
                                <select name="call_type_unit" id="call_type_unit" class="form-select" style="width: 65px; padding: 12px 15px 12px 8px; border: none; background: transparent; cursor: pointer; color: #374151; font-size: 15px; font-weight: 500; appearance: none; -webkit-appearance: none; -moz-appearance: none;">
                                    <option value="분">/ 분</option>
                                    <option value="시간">/ 시간</option>
                                </select>
                                <span style="position: absolute; right: 5px; color: #374151; font-size: 10px; pointer-events: none;">▼</span>
                            </div>
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <label class="form-label" for="additional_call">
                            부가·영상통화
                        </label>
                        <div class="input-with-suffix">
                            <input type="number" name="additional_call" id="additional_call" class="form-control" placeholder="300" min="0" max="99999" style="padding-right: 45px;">
                            <span class="input-suffix" style="right: 8px;">분</span>
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <label class="form-label" for="sms_type">
                            문자 <span class="required">*</span>
                        </label>
                        <select name="sms_type" id="sms_type" class="form-select" required>
                            <option value="">선택하세요</option>
                            <option value="무제한">무제한</option>
                            <option value="직접입력">직접입력</option>
                        </select>
                        <div id="sms_type_custom_wrapper" style="display: none; margin-top: 8px;">
                            <div class="input-with-suffix">
                                <input type="number" name="sms_type_custom" id="sms_type_custom" class="form-control" placeholder="100" min="0">
                                <span class="input-suffix" id="sms_type_unit_display">/ 건</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 세 번째 줄: 데이터 소진시, 테더링·핫스팟 -->
            <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb;">
                <div class="form-group" style="display: flex; gap: 16px; align-items: flex-start;">
                    <div style="flex: 1;">
                        <label class="form-label" for="data_exhausted">
                            데이터 소진시
                        </label>
                        <select name="data_exhausted" id="data_exhausted" class="form-select">
                            <option value="">선택하세요</option>
                            <option value="5Mbps 무제한">5Mbps 무제한</option>
                            <option value="3Mbps 무제한">3Mbps 무제한</option>
                            <option value="1Mbps 무제한">1Mbps 무제한</option>
                            <option value="직접입력">직접입력</option>
                        </select>
                        <input type="text" name="data_exhausted_custom" id="data_exhausted_custom" class="form-control" style="display: none; margin-top: 8px;" placeholder="10Mbps 무제한" maxlength="15">
                    </div>
                    <div style="flex: 1;">
                        <label class="form-label" for="mobile_hotspot">
                            테더링·핫스팟
                        </label>
                        <select name="mobile_hotspot" id="mobile_hotspot" class="form-select">
                            <option value="">선택하세요</option>
                            <option value="데이터 제공량 내">데이터 제공량 내</option>
                            <option value="직접입력">직접입력</option>
                        </select>
                        <input type="text" name="mobile_hotspot_custom" id="mobile_hotspot_custom" class="form-control" style="display: none; margin-top: 8px;" placeholder="50G" maxlength="10">
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- 유심 정보 -->
        <div class="form-section">
            <div class="form-section-title">유심 정보</div>
            
            <!-- 일반 유심 -->
            <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb;">
                <div style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 16px;">일반 유심</div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="regular_sim_available">
                    </label>
                    <select name="regular_sim_available" id="regular_sim_available" class="form-select">
                        <option value="" selected>선택</option>
                        <option value="배송불가">배송불가</option>
                        <option value="배송가능">배송가능</option>
                    </select>
                </div>
                <div class="form-group" id="regular_sim_price_wrapper" style="display: none;">
                    <label class="form-label" for="regular_sim_price">
                    </label>
                    <div class="input-with-suffix">
                        <input type="number" name="regular_sim_price" id="regular_sim_price" class="form-control" placeholder="2200" min="0" max="99999" style="padding-right: 45px;">
                        <span class="input-suffix" style="right: 8px;">원</span>
                    </div>
                </div>
            </div>
            
            <!-- NFC 유심 -->
            <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb;">
                <div style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 16px;">NFC 유심</div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="nfc_sim_available">
                    </label>
                    <select name="nfc_sim_available" id="nfc_sim_available" class="form-select">
                        <option value="" selected>선택</option>
                        <option value="배송불가">배송불가</option>
                        <option value="배송가능">배송가능</option>
                    </select>
                </div>
                <div class="form-group" id="nfc_sim_price_wrapper" style="display: none;">
                    <label class="form-label" for="nfc_sim_price">
                    </label>
                    <div class="input-with-suffix">
                        <input type="number" name="nfc_sim_price" id="nfc_sim_price" class="form-control" placeholder="4400" min="0" max="99999" style="padding-right: 45px;">
                        <span class="input-suffix" style="right: 8px;">원</span>
                    </div>
                </div>
            </div>
            
            <!-- eSIM -->
            <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb;">
                <div style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 16px;">eSIM</div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="esim_available">
                    </label>
                    <select name="esim_available" id="esim_available" class="form-select">
                        <option value="" selected>선택</option>
                        <option value="개통불가">개통불가</option>
                        <option value="개통가능">개통가능</option>
                    </select>
                </div>
                <div class="form-group" id="esim_price_wrapper" style="display: none;">
                    <label class="form-label" for="esim_price">
                    </label>
                    <div class="input-with-suffix">
                        <input type="number" name="esim_price" id="esim_price" class="form-control" placeholder="2750" min="0" max="99999" style="padding-right: 45px;">
                        <span class="input-suffix" style="right: 8px;">원</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 프로모션 혜택 -->
        <div class="form-section">
            <div class="form-section-title">프로모션 혜택</div>
            
            <div class="form-group">
                <label class="form-label" for="promotion_title">
                    제목
                </label>
                <input type="text" name="promotion_title" id="promotion_title" class="form-control" placeholder="프로모션 추가혜택, 사은품 6개" maxlength="20">
            </div>
            
            <div class="form-group">
                <label class="form-label">항목</label>
                <div id="promotion-items-container">
                    <div class="gift-input-group">
                        <input type="text" name="promotion_items[]" class="form-control" placeholder="Npay 30,000원">
                        <button type="button" class="btn-remove" onclick="removePromotionItem(this)">삭제</button>
                    </div>
                    <div class="gift-input-group">
                        <input type="text" name="promotion_items[]" class="form-control" placeholder="CU 20,000원">
                        <button type="button" class="btn-remove" onclick="removePromotionItem(this)">삭제</button>
                    </div>
                    <div class="gift-input-group">
                        <input type="text" name="promotion_items[]" class="form-control" placeholder="GS25 3,000원">
                        <button type="button" class="btn-remove" onclick="removePromotionItem(this)">삭제</button>
                    </div>
                    <div class="gift-input-group">
                        <input type="text" name="promotion_items[]" class="form-control" placeholder="데이터쿠폰 20GB">
                        <button type="button" class="btn-remove" onclick="removePromotionItem(this)">삭제</button>
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addPromotionItem()">+ 항목 추가</button>
            </div>
        </div>
        
        <!-- 기본 제공 초과 시 -->
        <div class="form-section">
            <div class="form-section-title">기본 제공 초과 시</div>
            
            <!-- 데이터, 음성, 영상통화 -->
            <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb;">
                <div class="form-group" style="display: flex; gap: 16px; align-items: flex-start;">
                    <div style="flex: 1;">
                        <label class="form-label" for="over_data_price">
                            데이터
                        </label>
                        <div class="input-with-suffix">
                            <input type="number" name="over_data_price" id="over_data_price" class="form-control" placeholder="22.53" min="0" step="0.01" max="999.99" style="padding-right: 60px;">
                            <span class="input-suffix" style="right: 8px;">원/MB</span>
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <label class="form-label" for="over_voice_price">
                            음성
                        </label>
                        <div class="input-with-suffix">
                            <input type="number" name="over_voice_price" id="over_voice_price" class="form-control" placeholder="1.98" min="0" step="0.01" max="999.99" style="padding-right: 60px;">
                            <span class="input-suffix" style="right: 8px;">원/초</span>
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <label class="form-label" for="over_video_price">
                            영상통화
                        </label>
                        <div class="input-with-suffix">
                            <input type="number" name="over_video_price" id="over_video_price" class="form-control" placeholder="3.3" min="0" step="0.01" max="999.99" style="padding-right: 60px;">
                            <span class="input-suffix" style="right: 8px;">원/초</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 단문메시지(SMS), 장문 텍스트형(MMS), 멀티미디어형 MMS -->
            <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e5e7eb;">
                <div class="form-group" style="display: flex; gap: 16px; align-items: flex-start;">
                    <div style="flex: 1;">
                        <label class="form-label" for="over_sms_price">
                            단문메시지(SMS)
                        </label>
                        <div class="input-with-suffix">
                            <input type="number" name="over_sms_price" id="over_sms_price" class="form-control" placeholder="22" min="0" step="0.01" max="999.99" style="padding-right: 60px;">
                            <span class="input-suffix" style="right: 8px;">원/건</span>
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <label class="form-label" for="over_mms_price">
                            장문 텍스트형(MMS)
                        </label>
                        <div class="input-with-suffix">
                            <input type="number" name="over_mms_price" id="over_mms_price" class="form-control" placeholder="33" min="0" step="0.01" max="999.99" style="padding-right: 60px;">
                            <span class="input-suffix" style="right: 8px;">원/건</span>
                        </div>
                    </div>
                    <div style="flex: 1;">
                        <label class="form-label" for="over_multimedia_mms_price">
                            멀티미디어형 MMS
                        </label>
                        <div class="input-with-suffix">
                            <input type="number" name="over_multimedia_mms_price" id="over_multimedia_mms_price" class="form-control" placeholder="110" min="0" step="0.01" max="999.99" style="padding-right: 60px;">
                            <span class="input-suffix" style="right: 8px;">원/건</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 혜택 및 유의사항 -->
        <div class="form-section">
            <div class="form-section-title">혜택 및 유의사항</div>
            
            <div class="form-group">
                <textarea name="benefits" id="benefits" class="form-textarea" style="min-height: 80px;" placeholder="혜택 및 유의사항을 입력하세요"></textarea>
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
let benefitCount = 1;

function addBenefitField() {
    const container = document.getElementById('benefits-container');
    const newField = document.createElement('div');
    newField.className = 'gift-input-group';
    newField.innerHTML = `
        <textarea name="benefits[]" class="form-textarea" style="min-height: 80px;" placeholder="혜택 및 유의사항을 입력하세요"></textarea>
        <button type="button" class="btn-remove" onclick="removeBenefitField(this)">삭제</button>
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

let promotionItemCount = 4;

function addPromotionItem() {
    const container = document.getElementById('promotion-items-container');
    const newField = document.createElement('div');
    newField.className = 'gift-input-group';
    newField.innerHTML = `
        <input type="text" name="promotion_items[]" class="form-control" placeholder="항목 입력">
        <button type="button" class="btn-remove" onclick="removePromotionItem(this)">삭제</button>
    `;
    container.appendChild(newField);
    promotionItemCount++;
}

function removePromotionItem(button) {
    const container = document.getElementById('promotion-items-container');
    if (container.children.length > 1) {
        button.parentElement.remove();
    }
}

// 통화 직접입력 필드 표시/숨김
document.getElementById('call_type').addEventListener('change', function() {
    const customWrapper = document.getElementById('call_type_custom_wrapper');
    const customInput = document.getElementById('call_type_custom');
    if (this.value === '직접입력') {
        customWrapper.style.display = 'flex';
        customInput.required = true;
    } else {
        customWrapper.style.display = 'none';
        customInput.required = false;
        customInput.value = '';
    }
});

// 문자 직접입력 필드 표시/숨김
document.getElementById('sms_type').addEventListener('change', function() {
    const customWrapper = document.getElementById('sms_type_custom_wrapper');
    const customInput = document.getElementById('sms_type_custom');
    if (this.value === '직접입력') {
        customWrapper.style.display = 'block';
        customInput.required = true;
    } else {
        customWrapper.style.display = 'none';
        customInput.required = false;
        customInput.value = '';
    }
});

// 요금제 유지기간 직접입력 필드 표시/숨김
document.getElementById('contract_period').addEventListener('change', function() {
    const customWrapper = document.getElementById('contract_period_custom_wrapper');
    const customInput = document.getElementById('contract_period_custom');
    if (this.value === '직접입력') {
        customWrapper.style.display = 'flex';
        customInput.required = true;
    } else {
        customWrapper.style.display = 'none';
        customInput.required = false;
        customInput.value = '';
    }
});

// 데이터 직접입력 필드 표시/숨김
document.getElementById('data_amount').addEventListener('change', function() {
    const customWrapper = document.getElementById('data_amount_custom_wrapper');
    const customInput = document.getElementById('data_amount_custom');
    if (this.value === '직접입력') {
        customWrapper.style.display = 'flex';
        customInput.required = true;
    } else {
        customWrapper.style.display = 'none';
        customInput.required = false;
        customInput.value = '';
    }
});

// 데이터 소진시 직접입력 필드 표시/숨김
document.getElementById('data_exhausted').addEventListener('change', function() {
    const customInput = document.getElementById('data_exhausted_custom');
    if (this.value === '직접입력') {
        customInput.style.display = 'block';
        customInput.required = true;
    } else {
        customInput.style.display = 'none';
        customInput.required = false;
        customInput.value = '';
    }
});

// 테더링·핫스팟 직접입력 필드 표시/숨김
document.getElementById('mobile_hotspot').addEventListener('change', function() {
    const customInput = document.getElementById('mobile_hotspot_custom');
    if (this.value === '직접입력') {
        customInput.style.display = 'block';
        customInput.required = true;
    } else {
        customInput.style.display = 'none';
        customInput.required = false;
        customInput.value = '';
    }
});

// 일반 유심 배송가능 선택 시 가격 필드 표시
document.getElementById('regular_sim_available').addEventListener('change', function() {
    const priceWrapper = document.getElementById('regular_sim_price_wrapper');
    if (this.value === '배송가능') {
        priceWrapper.style.display = 'block';
    } else {
        priceWrapper.style.display = 'none';
        document.getElementById('regular_sim_price').value = '';
    }
});

// NFC 유심 배송가능 선택 시 가격 필드 표시
document.getElementById('nfc_sim_available').addEventListener('change', function() {
    const priceWrapper = document.getElementById('nfc_sim_price_wrapper');
    if (this.value === '배송가능') {
        priceWrapper.style.display = 'block';
    } else {
        priceWrapper.style.display = 'none';
        document.getElementById('nfc_sim_price').value = '';
    }
});

// eSIM 개통가능 선택 시 가격 필드 표시
document.getElementById('esim_available').addEventListener('change', function() {
    const priceWrapper = document.getElementById('esim_price_wrapper');
    if (this.value === '개통가능') {
        priceWrapper.style.display = 'block';
    } else {
        priceWrapper.style.display = 'none';
        document.getElementById('esim_price').value = '';
    }
});

// 페이지 로드 시 기본값 확인
document.addEventListener('DOMContentLoaded', function() {
    // 일반 유심
    if (document.getElementById('regular_sim_available').value === '배송가능') {
        document.getElementById('regular_sim_price_wrapper').style.display = 'block';
    }
    // NFC 유심
    if (document.getElementById('nfc_sim_available').value === '배송가능') {
        document.getElementById('nfc_sim_price_wrapper').style.display = 'block';
    }
    // eSIM
    if (document.getElementById('esim_available').value === '개통가능') {
        document.getElementById('esim_price_wrapper').style.display = 'block';
    }
});

// 데이터 필드 입력 검증 (소수점 앞 3자리, 소수점 뒤 2자리)
document.getElementById('over_data_price').addEventListener('input', function() {
    let value = this.value;
    if (value.includes('.')) {
        const parts = value.split('.');
        if (parts[0].length > 3) {
            parts[0] = parts[0].substring(0, 3);
        }
        if (parts[1] && parts[1].length > 2) {
            parts[1] = parts[1].substring(0, 2);
        }
        this.value = parts.join('.');
    } else {
        if (value.length > 3) {
            this.value = value.substring(0, 3);
        }
    }
});

// 음성 필드 입력 검증 (소수점 앞 3자리, 소수점 뒤 2자리)
document.getElementById('over_voice_price').addEventListener('input', function() {
    let value = this.value;
    if (value.includes('.')) {
        const parts = value.split('.');
        if (parts[0].length > 3) {
            parts[0] = parts[0].substring(0, 3);
        }
        if (parts[1] && parts[1].length > 2) {
            parts[1] = parts[1].substring(0, 2);
        }
        this.value = parts.join('.');
    } else {
        if (value.length > 3) {
            this.value = value.substring(0, 3);
        }
    }
});

// 영상통화 필드 입력 검증 (소수점 앞 3자리, 소수점 뒤 2자리)
document.getElementById('over_video_price').addEventListener('input', function() {
    let value = this.value;
    if (value.includes('.')) {
        const parts = value.split('.');
        if (parts[0].length > 3) {
            parts[0] = parts[0].substring(0, 3);
        }
        if (parts[1] && parts[1].length > 2) {
            parts[1] = parts[1].substring(0, 2);
        }
        this.value = parts.join('.');
    } else {
        if (value.length > 3) {
            this.value = value.substring(0, 3);
        }
    }
});

// 문자(SMS) 필드 입력 검증 (정수 3자리, 소수점 2자리)
document.getElementById('over_sms_price').addEventListener('input', function() {
    let value = this.value;
    if (value.includes('.')) {
        const parts = value.split('.');
        if (parts[0].length > 3) {
            parts[0] = parts[0].substring(0, 3);
        }
        if (parts[1] && parts[1].length > 2) {
            parts[1] = parts[1].substring(0, 2);
        }
        this.value = parts.join('.');
    } else {
        if (value.length > 3) {
            this.value = value.substring(0, 3);
        }
    }
});

// 텍스트형(MMS) 필드 입력 검증 (정수 3자리, 소수점 2자리)
document.getElementById('over_mms_price').addEventListener('input', function() {
    let value = this.value;
    if (value.includes('.')) {
        const parts = value.split('.');
        if (parts[0].length > 3) {
            parts[0] = parts[0].substring(0, 3);
        }
        if (parts[1] && parts[1].length > 2) {
            parts[1] = parts[1].substring(0, 2);
        }
        this.value = parts.join('.');
    } else {
        if (value.length > 3) {
            this.value = value.substring(0, 3);
        }
    }
});

document.getElementById('productForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // 통화 직접입력 처리
    const callType = document.getElementById('call_type').value;
    if (callType === '직접입력') {
        const callCustom = document.getElementById('call_type_custom').value;
        const callUnit = document.getElementById('call_type_unit').value;
        if (!callCustom) {
            alert('통화 시간을 입력해주세요.');
            return;
        }
        formData.set('call_type', callCustom + callUnit);
    }
    
    const smsType = document.getElementById('sms_type').value;
    if (smsType === '직접입력') {
        const smsCustom = document.getElementById('sms_type_custom').value;
        const smsUnit = document.getElementById('sms_type_unit').value;
        if (!smsCustom) {
            alert('문자 시간을 입력해주세요.');
            return;
        }
        formData.set('sms_type', smsCustom + smsUnit);
    }
    
    // 요금제 유지기간 직접입력 처리
    const contractPeriod = document.getElementById('contract_period').value;
    if (contractPeriod === '직접입력') {
        const contractCustom = document.getElementById('contract_period_custom').value;
        if (!contractCustom) {
            alert('요금제 유지기간을 입력해주세요.');
            return;
        }
        formData.set('contract_period', contractCustom + '일');
    }
    
    // 데이터 소진시 직접입력 처리
    const dataExhausted = document.getElementById('data_exhausted').value;
    if (dataExhausted === '직접입력') {
        const dataExhaustedCustom = document.getElementById('data_exhausted_custom').value;
        if (!dataExhaustedCustom) {
            alert('데이터 소진시 내용을 입력해주세요.');
            return;
        }
        formData.set('data_exhausted', dataExhaustedCustom);
    }
    
    // 테더링·핫스팟 직접입력 처리
    const mobileHotspot = document.getElementById('mobile_hotspot').value;
    if (mobileHotspot === '직접입력') {
        const mobileHotspotCustom = document.getElementById('mobile_hotspot_custom').value;
        if (!mobileHotspotCustom) {
            alert('테더링·핫스팟 내용을 입력해주세요.');
            return;
        }
        formData.set('mobile_hotspot', mobileHotspotCustom);
    }
    
    fetch('/MVNO/api/product-register.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/MVNO/seller/products/mvno.php?success=1';
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

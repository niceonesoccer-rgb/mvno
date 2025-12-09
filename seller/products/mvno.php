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
    
    .btn-add-item {
        padding: 12px 16px;
        background: #10b981;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
        transition: all 0.2s;
    }
    
    .btn-add-item:hover {
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
        <h1>알뜰폰 상품 등록</h1>
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
    
    <form id="productForm" class="product-form" method="POST" action="/MVNO/api/product-register-mvno.php">
        
        <!-- 기본 정보 -->
        <div class="form-section">
            <div class="form-section-title">요금제</div>
            
            <div class="form-group" style="display: flex; gap: 16px; align-items: flex-start;">
                <div style="flex: 1;">
                    <label class="form-label" for="provider">
                        통신사 <span class="required">*</span>
                    </label>
                    <select name="provider" id="provider" class="form-select" required>
                        <option value="">선택하세요</option>
                        <option value="KT알뜰폰">KT알뜰폰</option>
                        <option value="SK알뜰폰">SK알뜰폰</option>
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
            
            <div class="form-group">
                <label class="form-label" for="plan_name">
                    요금제명
                </label>
                <input type="text" name="plan_name" id="plan_name" class="form-control" required placeholder="데이터 100G 평생요금" maxlength="30">
            </div>
            
            <div class="form-group" style="display: flex; gap: 16px; align-items: flex-start;">
                <div style="flex: 1;">
                    <label class="form-label" for="contract_period">
                        약정기간
                    </label>
                    <select name="contract_period" id="contract_period" class="form-select">
                        <option value="무약정">무약정</option>
                        <option value="직접입력">직접입력</option>
                    </select>
                    <div id="contract_period_input" style="display: none; margin-top: 12px;">
                        <div class="input-with-unit" style="max-width: 200px;">
                            <input type="number" name="contract_period_days" id="contract_period_days" class="form-control" placeholder="일 수 입력" min="1" max="99999" maxlength="5">
                            <span class="unit">일</span>
                        </div>
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="discount_period">
                        할인기간(프로모션기간)
                    </label>
                    <input type="text" name="discount_period" id="discount_period" class="form-control" placeholder="7개월" maxlength="10">
                </div>
            </div>
            
            <div class="form-group" style="display: flex; gap: 16px; align-items: flex-start;">
                <div style="flex: 1;">
                    <label class="form-label" for="price_main">
                        월 요금 <span class="required">*</span>
                    </label>
                    <div class="input-with-unit" style="max-width: 200px;">
                        <input type="text" name="price_main" id="price_main" class="form-control" required placeholder="1500" maxlength="5">
                        <span class="unit">원</span>
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="price_after_type">
                        할인 후 요금(프로모션기간)
                    </label>
                    <select name="price_after_type" id="price_after_type" class="form-select" style="max-width: 200px;">
                        <option value="">선택하세요</option>
                        <option value="free">공짜</option>
                        <option value="custom">직접입력</option>
                    </select>
                    <div id="price_after_input" style="display: none; margin-top: 12px;">
                        <div class="input-with-unit" style="max-width: 200px;">
                            <input type="text" id="price_after" class="form-control" placeholder="500" maxlength="5">
                            <span class="unit">원</span>
                        </div>
                    </div>
                    <input type="hidden" name="price_after" id="price_after_hidden" value="">
                </div>
            </div>
        </div>
        
        <!-- 데이터 정보 -->
        <div class="form-section">
            <div class="form-section-title">데이터 정보</div>
            
            <div class="form-group" style="display: flex; gap: 16px; align-items: flex-start;">
                <div style="flex: 1;">
                    <label class="form-label" for="data_amount">
                        데이터 제공량 <span class="required">*</span>
                    </label>
                    <select name="data_amount" id="data_amount" class="form-select" required>
                        <option value="">선택하세요</option>
                        <option value="무제한">무제한</option>
                        <option value="직접입력">직접입력</option>
                    </select>
                    <div id="data_amount_input" style="display: none; margin-top: 12px;">
                        <div class="input-with-unit" style="max-width: 200px;">
                            <input type="number" name="data_amount_value" id="data_amount_value" class="form-control" placeholder="100" min="0" max="99999" maxlength="5" style="padding-right: 70px;">
                            <select name="data_unit" id="data_unit" class="form-select" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 60px; height: auto; border: none; background: transparent; padding: 0 20px 0 0; appearance: auto; -webkit-appearance: menulist; -moz-appearance: menulist; cursor: pointer; font-size: 15px; color: #6b7280;">
                                <option value="GB">GB</option>
                                <option value="MB">MB</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="data_additional">
                        데이터 추가제공 <span class="required">*</span>
                    </label>
                    <select name="data_additional" id="data_additional" class="form-select" required>
                        <option value="">선택하세요</option>
                        <option value="없음">없음</option>
                        <option value="직접입력">직접입력</option>
                    </select>
                    <div id="data_additional_input" style="display: none; margin-top: 12px;">
                        <input type="text" name="data_additional_value" id="data_additional_value" class="form-control" placeholder="매일 20GB" maxlength="15">
                    </div>
                </div>
                
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
                    <div id="data_exhausted_input" style="display: none; margin-top: 12px;">
                        <input type="text" name="data_exhausted_value" id="data_exhausted_value" class="form-control" placeholder="10Mbps 무제한" maxlength="50">
                    </div>
                </div>
            </div>
            
            <div class="form-group" style="display: flex; gap: 16px; align-items: flex-start;">
                <div style="flex: 1;">
                    <label class="form-label" for="call_type">
                        통화 <span class="required">*</span>
                    </label>
                    <select name="call_type" id="call_type" class="form-select" required>
                        <option value="">선택하세요</option>
                        <option value="무제한">무제한</option>
                        <option value="기본제공">기본제공</option>
                        <option value="직접입력">직접입력</option>
                    </select>
                    <div id="call_type_input" style="display: none; margin-top: 12px;">
                        <div class="input-with-unit" style="max-width: 200px;">
                            <input type="number" name="call_amount" id="call_amount" class="form-control" placeholder="300" min="0" max="99999" maxlength="5">
                            <span class="unit">분</span>
                        </div>
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="additional_call">
                        부가·영상통화 <span class="required">*</span>
                    </label>
                    <select name="additional_call_type" id="additional_call_type" class="form-select" required>
                        <option value="">선택하세요</option>
                        <option value="무제한">무제한</option>
                        <option value="기본제공">기본제공</option>
                        <option value="직접입력">직접입력</option>
                    </select>
                    <div id="additional_call_input" style="display: none; margin-top: 12px;">
                        <div class="input-with-unit" style="max-width: 200px;">
                            <input type="number" name="additional_call" id="additional_call" class="form-control" placeholder="300" min="0" max="99999" maxlength="5">
                            <span class="unit">분</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group" style="display: flex; gap: 16px; align-items: flex-start;">
                <div style="flex: 1;">
                    <label class="form-label" for="sms_type">
                        문자 <span class="required">*</span>
                    </label>
                    <select name="sms_type" id="sms_type" class="form-select" required>
                        <option value="">선택하세요</option>
                        <option value="무제한">무제한</option>
                        <option value="기본제공">기본제공</option>
                        <option value="직접입력">직접입력</option>
                    </select>
                    <div id="sms_type_input" style="display: none; margin-top: 12px;">
                        <div class="input-with-unit" style="max-width: 200px;">
                            <input type="number" name="sms_amount" id="sms_amount" class="form-control" placeholder="300" min="0" max="99999" maxlength="5">
                            <span class="unit">건</span>
                        </div>
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="mobile_hotspot">
                        테더링(핫스팟) <span class="required">*</span>
                    </label>
                    <select name="mobile_hotspot" id="mobile_hotspot" class="form-select" required>
                        <option value="">선택하세요</option>
                        <option value="기본 제공량 내에서 사용">기본 제공량 내에서 사용</option>
                        <option value="직접선택">직접선택</option>
                    </select>
                    <div id="mobile_hotspot_input" style="display: none; margin-top: 12px;">
                        <input type="text" name="mobile_hotspot_value" id="mobile_hotspot_value" class="form-control" placeholder="50GB" maxlength="10">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 유심 정보 -->
        <div class="form-section">
            <div class="form-section-title">유심 정보</div>
            
            <div class="form-group" style="display: flex; gap: 16px; align-items: flex-start;">
                <div style="flex: 1;">
                    <label class="form-label" for="regular_sim_available">
                        일반유심
                    </label>
                    <select name="regular_sim_available" id="regular_sim_available" class="form-select">
                        <option value="">선택</option>
                        <option value="배송불가">배송불가</option>
                        <option value="배송가능">배송가능</option>
                    </select>
                    <div id="regular_sim_price_input" style="display: none; margin-top: 12px;">
                        <div class="input-with-unit" style="max-width: 200px;">
                            <input type="text" name="regular_sim_price" id="regular_sim_price" class="form-control" placeholder="2200" maxlength="5">
                            <span class="unit">원</span>
                        </div>
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="nfc_sim_available">
                        NFC유심
                    </label>
                    <select name="nfc_sim_available" id="nfc_sim_available" class="form-select">
                        <option value="">선택</option>
                        <option value="배송불가">배송불가</option>
                        <option value="배송가능">배송가능</option>
                    </select>
                    <div id="nfc_sim_price_input" style="display: none; margin-top: 12px;">
                        <div class="input-with-unit" style="max-width: 200px;">
                            <input type="text" name="nfc_sim_price" id="nfc_sim_price" class="form-control" placeholder="4400" maxlength="5">
                            <span class="unit">원</span>
                        </div>
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="esim_available">
                        eSIM
                    </label>
                    <select name="esim_available" id="esim_available" class="form-select">
                        <option value="">선택</option>
                        <option value="개통불가">개통불가</option>
                        <option value="개통가능">개통가능</option>
                    </select>
                    <div id="esim_price_input" style="display: none; margin-top: 12px;">
                        <div class="input-with-unit" style="max-width: 200px;">
                            <input type="text" name="esim_price" id="esim_price" class="form-control" placeholder="2750" maxlength="5">
                            <span class="unit">원</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 기본 제공 초과 시 -->
        <div class="form-section">
            <div class="form-section-title">기본 제공 초과 시</div>
            
            <div class="form-group" style="display: flex; gap: 16px; align-items: flex-start;">
                <div style="flex: 1;">
                    <label class="form-label" for="over_data_price">
                        데이터
                    </label>
                    <div class="input-with-unit" style="max-width: 200px;">
                        <input type="text" name="over_data_price" id="over_data_price" class="form-control" placeholder="22.53" maxlength="6">
                        <span class="unit">원/MB</span>
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="over_voice_price">
                        음성
                    </label>
                    <div class="input-with-unit" style="max-width: 200px;">
                        <input type="text" name="over_voice_price" id="over_voice_price" class="form-control" placeholder="1.98" maxlength="6">
                        <span class="unit">원/MB</span>
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="over_video_price">
                        영상통화
                    </label>
                    <div class="input-with-unit" style="max-width: 200px;">
                        <input type="text" name="over_video_price" id="over_video_price" class="form-control" placeholder="3.3" maxlength="6">
                        <span class="unit">원/초</span>
                    </div>
                </div>
            </div>
            
            <div class="form-group" style="display: flex; gap: 16px; align-items: flex-start;">
                <div style="flex: 1;">
                    <label class="form-label" for="over_sms_price">
                        단문메시지(SMS)
                    </label>
                    <div class="input-with-unit" style="max-width: 200px;">
                        <input type="text" name="over_sms_price" id="over_sms_price" class="form-control" placeholder="22" maxlength="5">
                        <span class="unit">원/건</span>
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="over_lms_price">
                        텍스트형(LMS,MMS)
                    </label>
                    <div class="input-with-unit" style="max-width: 200px;">
                        <input type="text" name="over_lms_price" id="over_lms_price" class="form-control" placeholder="33" maxlength="5">
                        <span class="unit">원/건</span>
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="over_mms_price">
                        멀티미디어형(MMS)
                    </label>
                    <div class="input-with-unit" style="max-width: 200px;">
                        <input type="text" name="over_mms_price" id="over_mms_price" class="form-control" placeholder="110" maxlength="5">
                        <span class="unit">원/건</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 프로모션 이벤트 -->
        <div class="form-section">
            <div class="form-section-title">프로모션 이벤트</div>
            
            <div class="form-group">
                <label class="form-label" for="promotion_title">
                    제목
                </label>
                <input type="text" name="promotion_title" id="promotion_title" class="form-control" placeholder="쿠폰북 최대 5만원 지급" maxlength="100">
            </div>
            
            <div class="form-group">
                <label class="form-label">항목</label>
                <div id="promotion-container">
                    <div class="gift-input-group">
                        <input type="text" name="promotions[]" class="form-control" placeholder="Npay 2,000" maxlength="30">
                        <button type="button" class="btn-add-item" onclick="addPromotionField()">추가</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 혜택 및 유의사항 -->
        <div class="form-section">
            <div class="form-section-title">혜택 및 유의사항</div>
            
            <div class="form-group">
                <div id="benefits-container">
                    <div class="gift-input-group">
                        <textarea name="benefits[]" class="form-textarea" style="min-height: 80px;" placeholder="혜택 및 유의사항을 입력하세요"></textarea>
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
    // 헬퍼 함수: 직접입력 필드 토글
    function toggleInputField(selectId, inputContainerId, triggerValue, inputId = null, additionalSelectId = null) {
        const select = document.getElementById(selectId);
        const container = document.getElementById(inputContainerId);
        if (!select || !container) {
            console.error('toggleInputField: 요소를 찾을 수 없습니다.', selectId, inputContainerId);
            return;
        }
        
        // 초기 상태 설정
        const initialValue = select.value;
        const isInitiallyShow = initialValue === triggerValue;
        container.style.display = isInitiallyShow ? 'block' : 'none';
        
        if (inputId) {
            const input = document.getElementById(inputId);
            if (input) {
                input.disabled = !isInitiallyShow;
                if (!isInitiallyShow) {
                    input.setAttribute('disabled', 'disabled');
                } else {
                    input.removeAttribute('disabled');
                }
            }
        }
        
        if (additionalSelectId) {
            const additionalSelect = document.getElementById(additionalSelectId);
            if (additionalSelect) {
                additionalSelect.disabled = !isInitiallyShow;
                if (!isInitiallyShow) {
                    additionalSelect.setAttribute('disabled', 'disabled');
                } else {
                    additionalSelect.removeAttribute('disabled');
                }
            }
        }
        
        // change 이벤트 리스너
        select.addEventListener('change', function() {
            const isShow = this.value === triggerValue;
            container.style.display = isShow ? 'block' : 'none';
            
            if (inputId) {
                const input = document.getElementById(inputId);
                if (input) {
                    if (isShow) {
                        input.removeAttribute('disabled');
                        input.focus();
                    } else {
                        input.setAttribute('disabled', 'disabled');
                        input.value = '';
                    }
                }
            }
            
            if (additionalSelectId) {
                const additionalSelect = document.getElementById(additionalSelectId);
                if (additionalSelect) {
                    if (isShow) {
                        additionalSelect.removeAttribute('disabled');
                    } else {
                        additionalSelect.setAttribute('disabled', 'disabled');
                    }
                }
            }
        });
    }
    
    // 헬퍼 함수: 숫자 입력 제한
    function limitNumericInput(inputId, maxLength, disabled = false) {
        const input = document.getElementById(inputId);
        if (!input) return;
        
        // disabled는 toggleInputField에서 관리하므로 여기서는 설정하지 않음
        // if (disabled) input.disabled = true;
        
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (maxLength && this.value.length > maxLength) {
                this.value = this.value.slice(0, maxLength);
            }
        });
    }
    
    // 헬퍼 함수: 텍스트 길이 제한
    function limitTextInput(inputId, maxLength, disabled = false) {
        const input = document.getElementById(inputId);
        if (!input) return;
        
        // disabled는 toggleInputField에서 관리하므로 여기서는 설정하지 않음
        // if (disabled) input.disabled = true;
        
        input.addEventListener('input', function() {
            if (this.value.length > maxLength) {
                this.value = this.value.slice(0, maxLength);
            }
        });
    }
    
    // 헬퍼 함수: 소수점 입력 제한 (정수3자리, 소수2자리)
    function limitDecimalInput(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;
        
        input.addEventListener('input', function() {
            let value = this.value.replace(/[^0-9.]/g, '');
            const parts = value.split('.');
            
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            if (parts[0] && parts[0].length > 3) {
                value = parts[0].slice(0, 3) + (parts[1] ? '.' + parts[1] : '');
            }
            if (parts[1] && parts[1].length > 2) {
                value = parts[0] + '.' + parts[1].slice(0, 2);
            }
            this.value = value;
        });
    }
    
    // 헬퍼 함수: 가격 입력 (천단위 표시)
    function setupPriceInput(inputId, disabled = false) {
        const input = document.getElementById(inputId);
        if (!input) return;
        
        if (disabled) input.disabled = true;
        
        input.addEventListener('input', function() {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value.length > 5) value = value.slice(0, 5);
            this.value = value;
        });
        
        input.addEventListener('blur', function() {
            if (this.value) {
                this.value = parseInt(this.value).toLocaleString('ko-KR');
            }
        });
        
        input.addEventListener('focus', function() {
            this.value = this.value.replace(/,/g, '');
        });
    }
    
    // 약정기간
    toggleInputField('contract_period', 'contract_period_input', '직접입력', 'contract_period_days');
    limitNumericInput('contract_period_days', 5);
    
    // 통화
    toggleInputField('call_type', 'call_type_input', '직접입력', 'call_amount');
    limitNumericInput('call_amount', 5, false);
    
    // 문자
    toggleInputField('sms_type', 'sms_type_input', '직접입력', 'sms_amount');
    limitNumericInput('sms_amount', 5, false);
    
    // 데이터 제공량
    toggleInputField('data_amount', 'data_amount_input', '직접입력', 'data_amount_value', 'data_unit');
    limitNumericInput('data_amount_value', 5, false);
    const dataUnitSelect = document.getElementById('data_unit');
    if (dataUnitSelect) {
        // 초기 상태 설정
        const dataAmount = document.getElementById('data_amount');
        if (dataAmount && dataAmount.value !== '직접입력') {
            dataUnitSelect.disabled = true;
        }
    }
    
    // 데이터 추가제공 (텍스트 입력)
    toggleInputField('data_additional', 'data_additional_input', '직접입력', 'data_additional_value');
    limitTextInput('data_additional_value', 15, false);
    
    // 데이터 소진시 (텍스트 입력)
    toggleInputField('data_exhausted', 'data_exhausted_input', '직접입력', 'data_exhausted_value');
    limitTextInput('data_exhausted_value', 50, false);
    
    // 부가·영상통화
    toggleInputField('additional_call_type', 'additional_call_input', '직접입력', 'additional_call');
    limitNumericInput('additional_call', 5, false);
    
    // 테더링(핫스팟)
    toggleInputField('mobile_hotspot', 'mobile_hotspot_input', '직접선택', 'mobile_hotspot_value');
    limitTextInput('mobile_hotspot_value', 10, false);
    
    // 일반 유심
    toggleInputField('regular_sim_available', 'regular_sim_price_input', '배송가능', 'regular_sim_price');
    setupPriceInput('regular_sim_price', true);
    
    // NFC 유심
    toggleInputField('nfc_sim_available', 'nfc_sim_price_input', '배송가능', 'nfc_sim_price');
    setupPriceInput('nfc_sim_price', true);
    
    // eSIM
    toggleInputField('esim_available', 'esim_price_input', '개통가능', 'esim_price');
    setupPriceInput('esim_price', true);
    
    // 기본 제공 초과 시 가격
    limitDecimalInput('over_data_price');
    limitDecimalInput('over_voice_price');
    limitDecimalInput('over_video_price');
    limitNumericInput('over_sms_price', 5);
    limitNumericInput('over_lms_price', 5);
    limitNumericInput('over_mms_price', 5);
    
    // 요금 및 기타 입력
    limitNumericInput('price_main', 5);
    limitNumericInput('price_after', 5);
    limitTextInput('discount_period', 10);
    
    // 할인 후 요금 타입 선택
    const priceAfterType = document.getElementById('price_after_type');
    const priceAfterInput = document.getElementById('price_after_input');
    const priceAfterField = document.getElementById('price_after');
    const priceAfterHidden = document.getElementById('price_after_hidden');
    
    if (priceAfterType && priceAfterInput && priceAfterHidden) {
        priceAfterType.addEventListener('change', function() {
            if (this.value === 'free') {
                // 공짜 선택 시
                priceAfterInput.style.display = 'none';
                priceAfterHidden.value = '0';
                if (priceAfterField) priceAfterField.value = '';
            } else if (this.value === 'custom') {
                // 직접입력 선택 시
                priceAfterInput.style.display = 'block';
                priceAfterHidden.value = '';
                if (priceAfterField) {
                    priceAfterField.focus();
                }
            } else {
                // 선택 안함
                priceAfterInput.style.display = 'none';
                priceAfterHidden.value = '';
                if (priceAfterField) priceAfterField.value = '';
            }
        });
        
        // 직접입력 필드 값 변경 시 hidden 필드 업데이트
        if (priceAfterField) {
            priceAfterField.addEventListener('input', function() {
                if (priceAfterType.value === 'custom') {
                    priceAfterHidden.value = this.value.replace(/[^0-9]/g, '');
                }
            });
        }
    }
    
    // 폼 제출
    document.getElementById('productForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // 필수 필드 검증: 직접입력 필드
        const callType = document.getElementById('call_type');
        const callAmount = document.getElementById('call_amount');
        if (callType && callType.value === '직접입력') {
            if (!callAmount || !callAmount.value.trim()) {
                alert('통화를 입력해주세요.');
                if (callAmount) callAmount.focus();
                return;
            }
        }
        
        const smsType = document.getElementById('sms_type');
        const smsAmount = document.getElementById('sms_amount');
        if (smsType && smsType.value === '직접입력') {
            if (!smsAmount || !smsAmount.value.trim()) {
                alert('문자를 입력해주세요.');
                if (smsAmount) smsAmount.focus();
                return;
            }
        }
        
        const dataAmount = document.getElementById('data_amount');
        const dataAmountValue = document.getElementById('data_amount_value');
        if (dataAmount && dataAmount.value === '직접입력') {
            if (!dataAmountValue || !dataAmountValue.value.trim()) {
                alert('데이터 제공량을 입력해주세요.');
                if (dataAmountValue) dataAmountValue.focus();
                return;
            }
        }
        
        const dataAdditional = document.getElementById('data_additional');
        const dataAdditionalValue = document.getElementById('data_additional_value');
        if (dataAdditional && dataAdditional.value === '직접입력') {
            if (!dataAdditionalValue || !dataAdditionalValue.value.trim()) {
                alert('데이터 추가제공을 입력해주세요.');
                if (dataAdditionalValue) dataAdditionalValue.focus();
                return;
            }
        }
        
        const additionalCallType = document.getElementById('additional_call_type');
        const additionalCallInput = document.getElementById('additional_call');
        if (additionalCallType && additionalCallType.value === '직접입력') {
            if (!additionalCallInput || !additionalCallInput.value.trim()) {
                alert('부가·영상통화를 입력해주세요.');
                if (additionalCallInput) additionalCallInput.focus();
                return;
            }
        }
        
        const mobileHotspot = document.getElementById('mobile_hotspot');
        const mobileHotspotInput = document.getElementById('mobile_hotspot_value');
        if (mobileHotspot && mobileHotspot.value === '직접선택') {
            if (!mobileHotspotInput || !mobileHotspotInput.value.trim()) {
                alert('테더링(핫스팟)을 입력해주세요.');
                if (mobileHotspotInput) mobileHotspotInput.focus();
                return;
            }
        }
        
        // 데이터 추가제공 값 최종 처리
        const dataAdditional = document.getElementById('data_additional');
        const dataAdditionalValue = document.getElementById('data_additional_value');
        if (dataAdditional) {
            if (dataAdditional.value === '없음') {
                // "없음" 선택 시 값 초기화
                if (dataAdditionalValue) {
                    dataAdditionalValue.value = '';
                    dataAdditionalValue.disabled = false; // 제출을 위해 활성화
                }
            } else if (dataAdditional.value === '직접입력') {
                // "직접입력" 선택 시 입력값 사용 (이미 검증됨)
                if (dataAdditionalValue) {
                    dataAdditionalValue.disabled = false; // 제출을 위해 활성화
                }
            } else {
                // 선택 안함
                if (dataAdditionalValue) {
                    dataAdditionalValue.value = '';
                    dataAdditionalValue.disabled = false;
                }
            }
        }
        
        // 할인 후 요금 값 최종 처리
        const priceAfterType = document.getElementById('price_after_type');
        const priceAfterHidden = document.getElementById('price_after_hidden');
        const priceAfterField = document.getElementById('price_after');
        
        if (priceAfterType && priceAfterHidden) {
            if (priceAfterType.value === 'free') {
                // 공짜 선택 시 0으로 설정
                priceAfterHidden.value = '0';
            } else if (priceAfterType.value === 'custom' && priceAfterField) {
                // 직접입력 시 입력값 사용
                priceAfterHidden.value = priceAfterField.value.replace(/[^0-9]/g, '');
            } else {
                // 선택 안함
                priceAfterHidden.value = '';
            }
        }
        
        const formData = new FormData(this);
        
        fetch('/MVNO/api/product-register-mvno.php', {
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
});

// 전역 함수로 선언 (onclick에서 호출 가능하도록)
window.addPromotionField = function() {
    const container = document.getElementById('promotion-container');
    if (!container) {
        console.error('promotion-container를 찾을 수 없습니다.');
        return;
    }
    const newField = document.createElement('div');
    newField.className = 'gift-input-group';
    newField.innerHTML = `
        <input type="text" name="promotions[]" class="form-control" placeholder="Npay 2,000" maxlength="30">
        <button type="button" class="btn-remove" onclick="removePromotionField(this)">삭제</button>
    `;
    container.appendChild(newField);
};

window.removePromotionField = function(button) {
    const container = document.getElementById('promotion-container');
    if (!container) {
        console.error('promotion-container를 찾을 수 없습니다.');
        return;
    }
    if (container.children.length > 1) {
        button.parentElement.remove();
    }
};
</script>

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>

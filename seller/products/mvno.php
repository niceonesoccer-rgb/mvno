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
    
    <form id="productForm" class="product-form" method="POST" action="/MVNO/api/product-register.php">
        <input type="hidden" name="board_type" value="mvno">
        
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
                    <label class="form-label" for="price_after">
                        할인 후 요금(프로모션기간)
                    </label>
                    <div class="input-with-unit" style="max-width: 200px;">
                        <input type="text" name="price_after" id="price_after" class="form-control" placeholder="500" maxlength="5">
                        <span class="unit">원</span>
                    </div>
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
                        부가·영상통화
                    </label>
                    <select name="additional_call_type" id="additional_call_type" class="form-select">
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
                        테더링(핫스팟)
                    </label>
                    <select name="mobile_hotspot" id="mobile_hotspot" class="form-select">
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
                <input type="text" name="promotion_title" id="promotion_title" class="form-control" placeholder="프로모션 이벤트 제목을 입력하세요" maxlength="100">
            </div>
            
            <div class="form-group">
                <label class="form-label">항목</label>
                <div id="promotion-container">
                    <div class="gift-input-group">
                        <input type="text" name="promotions[]" class="form-control" placeholder="Npay 2,000" maxlength="30">
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addPromotionField()">+ 항목 추가</button>
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
// 약정기간 직접입력 필드 토글
document.addEventListener('DOMContentLoaded', function() {
    const contractPeriodSelect = document.getElementById('contract_period');
    const contractPeriodInput = document.getElementById('contract_period_input');
    
    if (contractPeriodSelect && contractPeriodInput) {
        contractPeriodSelect.addEventListener('change', function() {
            if (this.value === '직접입력') {
                contractPeriodInput.style.display = 'block';
                document.getElementById('contract_period_days').focus();
            } else {
                contractPeriodInput.style.display = 'none';
                document.getElementById('contract_period_days').value = '';
            }
        });
        
        // 숫자만 입력되도록 제한 (5자리)
        const contractPeriodDays = document.getElementById('contract_period_days');
        if (contractPeriodDays) {
            contractPeriodDays.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 5) {
                    this.value = this.value.slice(0, 5);
                }
            });
        }
    }
    
    // 통화 직접입력 필드 토글
    const callTypeSelect = document.getElementById('call_type');
    const callTypeInput = document.getElementById('call_type_input');
    const callAmount = document.getElementById('call_amount');
    
    if (callTypeSelect && callTypeInput) {
        callTypeSelect.addEventListener('change', function() {
            if (this.value === '직접입력') {
                callTypeInput.style.display = 'block';
                if (callAmount) {
                    callAmount.disabled = false;
                    callAmount.focus();
                }
            } else {
                callTypeInput.style.display = 'none';
                if (callAmount) {
                    callAmount.value = '';
                    callAmount.disabled = true;
                }
            }
        });
        
        // 숫자만 입력되도록 제한 (5자리)
        if (callAmount) {
            callAmount.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 5) {
                    this.value = this.value.slice(0, 5);
                }
            });
            
            // 초기 상태에서 비활성화
            callAmount.disabled = true;
        }
    }
    
    // 문자 직접입력 필드 토글
    const smsTypeSelect = document.getElementById('sms_type');
    const smsTypeInput = document.getElementById('sms_type_input');
    const smsAmount = document.getElementById('sms_amount');
    
    if (smsTypeSelect && smsTypeInput) {
        smsTypeSelect.addEventListener('change', function() {
            if (this.value === '직접입력') {
                smsTypeInput.style.display = 'block';
                if (smsAmount) {
                    smsAmount.disabled = false;
                    smsAmount.focus();
                }
            } else {
                smsTypeInput.style.display = 'none';
                if (smsAmount) {
                    smsAmount.value = '';
                    smsAmount.disabled = true;
                }
            }
        });
        
        // 숫자만 입력되도록 제한 (5자리)
        if (smsAmount) {
            smsAmount.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 5) {
                    this.value = this.value.slice(0, 5);
                }
            });
            
            // 초기 상태에서 비활성화
            smsAmount.disabled = true;
        }
    }
    
    // 데이터 제공량 직접입력 필드 토글
    const dataAmountSelect = document.getElementById('data_amount');
    const dataAmountInput = document.getElementById('data_amount_input');
    const dataAmountValue = document.getElementById('data_amount_value');
    const dataUnitSelect = document.getElementById('data_unit');
    
    if (dataAmountSelect && dataAmountInput) {
        dataAmountSelect.addEventListener('change', function() {
            if (this.value === '직접입력') {
                dataAmountInput.style.display = 'block';
                if (dataAmountValue) {
                    dataAmountValue.disabled = false;
                    dataAmountValue.focus();
                }
                if (dataUnitSelect) {
                    dataUnitSelect.disabled = false;
                }
            } else {
                dataAmountInput.style.display = 'none';
                if (dataAmountValue) {
                    dataAmountValue.value = '';
                    dataAmountValue.disabled = true;
                }
                if (dataUnitSelect) {
                    dataUnitSelect.disabled = true;
                }
            }
        });
        
        // 숫자만 입력되도록 제한 (5자리)
        if (dataAmountValue) {
            dataAmountValue.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 5) {
                    this.value = this.value.slice(0, 5);
                }
            });
            
            // 초기 상태에서 비활성화
            dataAmountValue.disabled = true;
        }
        
        // 초기 상태에서 단위 선택 드롭다운 비활성화
        if (dataUnitSelect) {
            dataUnitSelect.disabled = true;
        }
    }
    
    // 데이터 소진시 직접입력 필드 토글
    const dataExhaustedSelect = document.getElementById('data_exhausted');
    const dataExhaustedInput = document.getElementById('data_exhausted_input');
    const dataExhaustedValue = document.getElementById('data_exhausted_value');
    
    if (dataExhaustedSelect && dataExhaustedInput) {
        dataExhaustedSelect.addEventListener('change', function() {
            if (this.value === '직접입력') {
                dataExhaustedInput.style.display = 'block';
                if (dataExhaustedValue) {
                    dataExhaustedValue.disabled = false;
                    dataExhaustedValue.focus();
                }
            } else {
                dataExhaustedInput.style.display = 'none';
                if (dataExhaustedValue) {
                    dataExhaustedValue.value = '';
                    dataExhaustedValue.disabled = true;
                }
            }
        });
        
        // 숫자만 입력되도록 제한 (5자리)
        if (dataExhaustedValue) {
            dataExhaustedValue.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 5) {
                    this.value = this.value.slice(0, 5);
                }
            });
            
            // 초기 상태에서 비활성화
            dataExhaustedValue.disabled = true;
        }
    }
    
    // 부가·영상통화 직접입력 필드 토글
    const additionalCallTypeSelect = document.getElementById('additional_call_type');
    const additionalCallInput = document.getElementById('additional_call_input');
    const additionalCall = document.getElementById('additional_call');
    
    if (additionalCallTypeSelect && additionalCallInput) {
        additionalCallTypeSelect.addEventListener('change', function() {
            if (this.value === '직접입력') {
                additionalCallInput.style.display = 'block';
                if (additionalCall) {
                    additionalCall.disabled = false;
                    additionalCall.focus();
                }
            } else {
                additionalCallInput.style.display = 'none';
                if (additionalCall) {
                    additionalCall.value = '';
                    additionalCall.disabled = true;
                }
            }
        });
        
        // 숫자만 입력되도록 제한 (5자리)
        if (additionalCall) {
            additionalCall.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 5) {
                    this.value = this.value.slice(0, 5);
                }
            });
            
            // 초기 상태에서 비활성화
            additionalCall.disabled = true;
        }
    }
    
    // 테더링(핫스팟) 직접선택 필드 토글
    const mobileHotspotSelect = document.getElementById('mobile_hotspot');
    const mobileHotspotInput = document.getElementById('mobile_hotspot_input');
    const mobileHotspotValue = document.getElementById('mobile_hotspot_value');
    
    if (mobileHotspotSelect && mobileHotspotInput) {
        mobileHotspotSelect.addEventListener('change', function() {
            if (this.value === '직접선택') {
                mobileHotspotInput.style.display = 'block';
                if (mobileHotspotValue) {
                    mobileHotspotValue.disabled = false;
                    mobileHotspotValue.focus();
                }
            } else {
                mobileHotspotInput.style.display = 'none';
                if (mobileHotspotValue) {
                    mobileHotspotValue.value = '';
                    mobileHotspotValue.disabled = true;
                }
            }
        });
        
        // 텍스트 입력 제한 (10글자)
        if (mobileHotspotValue) {
            mobileHotspotValue.addEventListener('input', function() {
                if (this.value.length > 10) {
                    this.value = this.value.slice(0, 10);
                }
            });
            
            // 초기 상태에서 비활성화
            mobileHotspotValue.disabled = true;
        }
    }
    
    // 일반 유심 배송가능 필드 토글
    const regularSimSelect = document.getElementById('regular_sim_available');
    const regularSimPriceInput = document.getElementById('regular_sim_price_input');
    const regularSimPrice = document.getElementById('regular_sim_price');
    
    if (regularSimSelect && regularSimPriceInput) {
        regularSimSelect.addEventListener('change', function() {
            if (this.value === '배송가능') {
                regularSimPriceInput.style.display = 'block';
                if (regularSimPrice) {
                    regularSimPrice.disabled = false;
                    regularSimPrice.focus();
                }
            } else {
                regularSimPriceInput.style.display = 'none';
                if (regularSimPrice) {
                    regularSimPrice.value = '';
                    regularSimPrice.disabled = true;
                }
            }
        });
        
        // 숫자만 입력 및 천단위 표시 (5자리)
        if (regularSimPrice) {
            regularSimPrice.addEventListener('input', function() {
                let value = this.value.replace(/[^0-9]/g, '');
                if (value.length > 5) {
                    value = value.slice(0, 5);
                }
                this.value = value;
            });
            
            regularSimPrice.addEventListener('blur', function() {
                if (this.value) {
                    this.value = parseInt(this.value).toLocaleString('ko-KR');
                }
            });
            
            regularSimPrice.addEventListener('focus', function() {
                this.value = this.value.replace(/,/g, '');
            });
            
            regularSimPrice.disabled = true;
        }
    }
    
    // NFC 유심 배송가능 필드 토글
    const nfcSimSelect = document.getElementById('nfc_sim_available');
    const nfcSimPriceInput = document.getElementById('nfc_sim_price_input');
    const nfcSimPrice = document.getElementById('nfc_sim_price');
    
    if (nfcSimSelect && nfcSimPriceInput) {
        nfcSimSelect.addEventListener('change', function() {
            if (this.value === '배송가능') {
                nfcSimPriceInput.style.display = 'block';
                if (nfcSimPrice) {
                    nfcSimPrice.disabled = false;
                    nfcSimPrice.focus();
                }
            } else {
                nfcSimPriceInput.style.display = 'none';
                if (nfcSimPrice) {
                    nfcSimPrice.value = '';
                    nfcSimPrice.disabled = true;
                }
            }
        });
        
        // 숫자만 입력 및 천단위 표시 (5자리)
        if (nfcSimPrice) {
            nfcSimPrice.addEventListener('input', function() {
                let value = this.value.replace(/[^0-9]/g, '');
                if (value.length > 5) {
                    value = value.slice(0, 5);
                }
                this.value = value;
            });
            
            nfcSimPrice.addEventListener('blur', function() {
                if (this.value) {
                    this.value = parseInt(this.value).toLocaleString('ko-KR');
                }
            });
            
            nfcSimPrice.addEventListener('focus', function() {
                this.value = this.value.replace(/,/g, '');
            });
            
            nfcSimPrice.disabled = true;
        }
    }
    
    // eSIM 개통가능 필드 토글
    const esimSelect = document.getElementById('esim_available');
    const esimPriceInput = document.getElementById('esim_price_input');
    const esimPrice = document.getElementById('esim_price');
    
    if (esimSelect && esimPriceInput) {
        esimSelect.addEventListener('change', function() {
            if (this.value === '개통가능') {
                esimPriceInput.style.display = 'block';
                if (esimPrice) {
                    esimPrice.disabled = false;
                    esimPrice.focus();
                }
            } else {
                esimPriceInput.style.display = 'none';
                if (esimPrice) {
                    esimPrice.value = '';
                    esimPrice.disabled = true;
                }
            }
        });
        
        // 숫자만 입력 및 천단위 표시 (5자리)
        if (esimPrice) {
            esimPrice.addEventListener('input', function() {
                let value = this.value.replace(/[^0-9]/g, '');
                if (value.length > 5) {
                    value = value.slice(0, 5);
                }
                this.value = value;
            });
            
            esimPrice.addEventListener('blur', function() {
                if (this.value) {
                    this.value = parseInt(this.value).toLocaleString('ko-KR');
                }
            });
            
            esimPrice.addEventListener('focus', function() {
                this.value = this.value.replace(/,/g, '');
            });
            
            esimPrice.disabled = true;
        }
    }
    
    // 기본 제공 초과 시 필드 검증
    // 데이터: 정수3자리 소수2자리 (최대 999.99)
    const overDataPrice = document.getElementById('over_data_price');
    if (overDataPrice) {
        overDataPrice.addEventListener('input', function() {
            let value = this.value.replace(/[^0-9.]/g, '');
            // 소수점이 하나만 있도록 제한
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            // 정수 부분 3자리 제한
            if (parts[0] && parts[0].length > 3) {
                value = parts[0].slice(0, 3) + (parts[1] ? '.' + parts[1] : '');
            }
            // 소수 부분 2자리 제한
            if (parts[1] && parts[1].length > 2) {
                value = parts[0] + '.' + parts[1].slice(0, 2);
            }
            this.value = value;
        });
    }
    
    // 음성: 정수3자리 소수2자리 (최대 999.99)
    const overVoicePrice = document.getElementById('over_voice_price');
    if (overVoicePrice) {
        overVoicePrice.addEventListener('input', function() {
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
    
    // 영상통화: 정수3자리 소수2자리 (최대 999.99)
    const overVideoPrice = document.getElementById('over_video_price');
    if (overVideoPrice) {
        overVideoPrice.addEventListener('input', function() {
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
    
    // 단문메시지(SMS): 숫자 5자리
    const overSmsPrice = document.getElementById('over_sms_price');
    if (overSmsPrice) {
        overSmsPrice.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 5) {
                this.value = this.value.slice(0, 5);
            }
        });
    }
    
    // 텍스트형(LMS,MMS): 숫자 5자리
    const overLmsPrice = document.getElementById('over_lms_price');
    if (overLmsPrice) {
        overLmsPrice.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 5) {
                this.value = this.value.slice(0, 5);
            }
        });
    }
    
    // 멀티미디어형(MMS): 숫자 5자리
    const overMmsPrice = document.getElementById('over_mms_price');
    if (overMmsPrice) {
        overMmsPrice.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 5) {
                this.value = this.value.slice(0, 5);
            }
        });
    }
    
    // 월 요금: 숫자 5자리
    const priceMain = document.getElementById('price_main');
    if (priceMain) {
        priceMain.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 5) {
                this.value = this.value.slice(0, 5);
            }
        });
    }
    
    // 할인 후 요금: 숫자 5자리
    const priceAfter = document.getElementById('price_after');
    if (priceAfter) {
        priceAfter.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 5) {
                this.value = this.value.slice(0, 5);
            }
        });
    }
    
    // 할인기간: 텍스트 10자리
    const discountPeriod = document.getElementById('discount_period');
    if (discountPeriod) {
        discountPeriod.addEventListener('input', function() {
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });
    }
});

let promotionCount = 1;

function addPromotionField() {
    const container = document.getElementById('promotion-container');
    const newField = document.createElement('div');
    newField.className = 'gift-input-group';
    newField.innerHTML = `
        <input type="text" name="promotions[]" class="form-control" placeholder="Npay 2,000" maxlength="30">
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
    e.preventDefault();
    
    const formData = new FormData(this);
    
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

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
            <div class="form-section-title">기본 정보</div>
            
            <div class="form-group">
                <label class="form-label" for="provider">
                    통신사 <span class="required">*</span>
                </label>
                <select name="provider" id="provider" class="form-select" required>
                    <option value="">선택하세요</option>
                    <option value="KT알뜰폰">KT알뜰폰</option>
                    <option value="LG알뜰폰">LG알뜰폰</option>
                    <option value="SK알뜰폰">SK알뜰폰</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="plan_name">
                    요금제 이름 <span class="required">*</span>
                </label>
                <input type="text" name="plan_name" id="plan_name" class="form-control" required placeholder="예: 데이터 100GB">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="contract_period_type">
                    유지기간
                </label>
                <select name="contract_period_type" id="contract_period_type" class="form-select">
                    <option value="">선택하세요</option>
                    <option value="무약정">무약정</option>
                    <option value="직접입력">직접입력</option>
                </select>
                <div id="contract_period_input_container" style="display: none; margin-top: 12px;">
                    <div style="position: relative;">
                        <input type="number" name="contract_period" id="contract_period" class="form-control" placeholder="93" min="0" step="1" style="padding-right: 50px;">
                        <span style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 15px; color: #6b7280; pointer-events: none; background-color: #f0f9ff; padding: 4px 8px; border-radius: 4px;">일</span>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="service_type">
                    통신속도 <span class="required">*</span>
                </label>
                <select name="service_type" id="service_type" class="form-select" required>
                    <option value="">선택하세요</option>
                    <option value="LTE">LTE</option>
                    <option value="5G">5G</option>
                    <option value="6G">6G</option>
                </select>
            </div>
        </div>
        
        <!-- 데이터 정보 -->
        <div class="form-section">
            <div class="form-section-title">데이터 정보</div>
            
            <div class="form-group">
                <label class="form-label" for="data_amount">
                    데이터 제공량 <span class="required">*</span>
                </label>
                <select name="data_type" id="data_type" class="form-select" required>
                    <option value="">선택하세요</option>
                    <option value="무제한">무제한</option>
                    <option value="직접입력">직접입력</option>
                </select>
                <div id="data_input_container" style="display: none; margin-top: 12px;">
                    <div style="position: relative;">
                        <input type="number" name="data_amount" id="data_amount" class="form-control" placeholder="100" min="0" step="0.01" style="padding-right: 80px;">
                        <select name="data_unit" id="data_unit" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); border: none; background: #f0f9ff; padding: 4px 8px; border-radius: 4px; font-size: 15px; color: #6b7280; cursor: pointer; outline: none;">
                            <option value="GB">GB</option>
                            <option value="MB">MB</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="data_exhausted">
                    데이터 소진시
                </label>
                <select name="data_exhausted" id="data_exhausted" class="form-select">
                    <option value="">선택하세요</option>
                    <option value="5Mbps 무제한">5Mbps 무제한</option>
                    <option value="3Mbps 무제한">3Mbps 무제한</option>
                    <option value="1Mbps 무제한">1Mbps 무제한</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="call_type">
                    통화 <span class="required">*</span>
                </label>
                <select name="call_type" id="call_type" class="form-select" required>
                    <option value="">선택하세요</option>
                    <option value="무제한">무제한</option>
                    <option value="직접입력">직접입력</option>
                </select>
                <div id="call_input_container" style="display: none; margin-top: 12px;">
                    <div style="position: relative;">
                        <input type="number" name="call_amount" id="call_amount" class="form-control" placeholder="입력" min="0" step="1" style="padding-right: 80px;">
                        <select name="call_unit" id="call_unit" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); border: none; background: #f0f9ff; padding: 4px 8px; border-radius: 4px; font-size: 15px; color: #6b7280; cursor: pointer; outline: none;">
                            <option value="/분">/분</option>
                            <option value="/초">/초</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="sms_type">
                    문자 <span class="required">*</span>
                </label>
                <select name="sms_type" id="sms_type" class="form-select" required>
                    <option value="">선택하세요</option>
                    <option value="무제한">무제한</option>
                    <option value="직접입력">직접입력</option>
                </select>
                <div id="sms_input_container" style="display: none; margin-top: 12px;">
                    <div style="position: relative;">
                        <input type="number" name="sms_amount" id="sms_amount" class="form-control" placeholder="입력" min="0" step="1" style="padding-right: 80px;">
                        <span style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 15px; color: #6b7280; pointer-events: none; background-color: #f0f9ff; padding: 4px 8px; border-radius: 4px;">/건</span>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="additional_call">
                    영상·부가통화
                </label>
                <div style="position: relative;">
                    <input type="number" name="additional_call" id="additional_call" class="form-control" placeholder="300" min="0" step="1" style="padding-right: 50px;">
                    <span style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 15px; color: #6b7280; pointer-events: none; background-color: #f0f9ff; padding: 4px 8px; border-radius: 4px;">분</span>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="mobile_hotspot">
                    테더링(핫스팟)
                </label>
                <select name="mobile_hotspot_type" id="mobile_hotspot_type" class="form-select">
                    <option value="">선택하세요</option>
                    <option value="데이터 제공량 내">데이터 제공량 내</option>
                    <option value="직접입력">직접입력</option>
                </select>
                <div id="mobile_hotspot_input_container" style="display: none; margin-top: 12px;">
                    <div style="position: relative;">
                        <input type="number" name="mobile_hotspot" id="mobile_hotspot" class="form-control" placeholder="100" min="0" step="0.01" style="padding-right: 80px;">
                        <select name="mobile_hotspot_unit" id="mobile_hotspot_unit" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); border: none; background: #f0f9ff; padding: 4px 8px; border-radius: 4px; font-size: 15px; color: #6b7280; cursor: pointer; outline: none;">
                            <option value="GB">GB</option>
                            <option value="MB">MB</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 유심 정보 -->
        <div class="form-section">
            <div class="form-section-title">유심 정보</div>
            
            <!-- 일반 유심 -->
            <div class="form-group">
                <label class="form-label">일반 유심</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="form-label" for="regular_sim_available" style="font-size: 13px; margin-bottom: 6px;">
                            배송
                        </label>
                        <select name="regular_sim_available" id="regular_sim_available" class="form-select">
                            <option value="배송불가">배송불가</option>
                            <option value="배송가능">배송가능</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="regular_sim_price_type" style="font-size: 13px; margin-bottom: 6px;">
                            가격
                        </label>
                        <select name="regular_sim_price_type" id="regular_sim_price_type" class="form-select">
                            <option value="">선택하세요</option>
                            <option value="무료제공">무료제공</option>
                            <option value="직접입력">직접입력</option>
                        </select>
                        <div id="regular_sim_price_input_container" style="display: none; margin-top: 8px;">
                            <div style="position: relative;">
                                <input type="number" name="regular_sim_price" id="regular_sim_price" class="form-control" placeholder="6600" min="0" step="1" style="padding-right: 50px;">
                                <span style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 15px; color: #6b7280; pointer-events: none; background-color: #f0f9ff; padding: 4px 8px; border-radius: 4px;">원</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- NFC 유심 -->
            <div class="form-group">
                <label class="form-label">NFC 유심</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="form-label" for="nfc_sim_available" style="font-size: 13px; margin-bottom: 6px;">
                            배송
                        </label>
                        <select name="nfc_sim_available" id="nfc_sim_available" class="form-select">
                            <option value="배송불가">배송불가</option>
                            <option value="배송가능">배송가능</option>
                        </select>
                        <div id="nfc_sim_delivery_input_container" style="display: none; margin-top: 8px;">
                            <div style="position: relative;">
                                <input type="number" name="nfc_sim_delivery" id="nfc_sim_delivery" class="form-control" placeholder="7700" min="0" step="1" style="padding-right: 50px;">
                                <span style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 15px; color: #6b7280; pointer-events: none; background-color: #f0f9ff; padding: 4px 8px; border-radius: 4px;">원</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="form-label" for="nfc_sim_price_type" style="font-size: 13px; margin-bottom: 6px;">
                            가격
                        </label>
                        <select name="nfc_sim_price_type" id="nfc_sim_price_type" class="form-select">
                            <option value="">선택하세요</option>
                            <option value="무료제공">무료제공</option>
                            <option value="직접입력">직접입력</option>
                        </select>
                        <div id="nfc_sim_price_input_container" style="display: none; margin-top: 8px;">
                            <div style="position: relative;">
                                <input type="number" name="nfc_sim_price" id="nfc_sim_price" class="form-control" placeholder="입력" min="0" step="1" style="padding-right: 50px;">
                                <span style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 15px; color: #6b7280; pointer-events: none; background-color: #f0f9ff; padding: 4px 8px; border-radius: 4px;">원</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- eSIM -->
            <div class="form-group">
                <label class="form-label">eSIM</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="form-label" for="esim_available" style="font-size: 13px; margin-bottom: 6px;">
                            개통
                        </label>
                        <select name="esim_available" id="esim_available" class="form-select">
                            <option value="개통불가">개통불가</option>
                            <option value="개통가능">개통가능</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="esim_price_type" style="font-size: 13px; margin-bottom: 6px;">
                            가격
                        </label>
                        <select name="esim_price_type" id="esim_price_type" class="form-select">
                            <option value="">선택하세요</option>
                            <option value="무료제공">무료제공</option>
                            <option value="직접입력">직접입력</option>
                        </select>
                        <div id="esim_price_input_container" style="display: none; margin-top: 8px;">
                            <div style="position: relative;">
                                <input type="number" name="esim_price" id="esim_price" class="form-control" placeholder="2750" min="0" step="1" style="padding-right: 50px;">
                                <span style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 15px; color: #6b7280; pointer-events: none; background-color: #f0f9ff; padding: 4px 8px; border-radius: 4px;">원</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 기본 제공 초과 시 -->
        <div class="form-section">
            <div class="form-section-title">기본 제공 초과 시</div>
            
            <div class="form-group">
                <label class="form-label" for="over_data_price">
                    데이터
                </label>
                <div style="position: relative;">
                    <input type="number" name="over_data_price" id="over_data_price" class="form-control" placeholder="22.53" min="0" step="0.01" style="padding-right: 80px;">
                    <span style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 15px; color: #6b7280; pointer-events: none; background-color: #f0f9ff; padding: 4px 8px; border-radius: 4px;">원/MB</span>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="over_voice_price">
                    음성 통화
                </label>
                <div style="position: relative;">
                    <input type="number" name="over_voice_price" id="over_voice_price" class="form-control" placeholder="1.98" min="0" step="0.01" style="padding-right: 80px;">
                    <span style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 15px; color: #6b7280; pointer-events: none; background-color: #f0f9ff; padding: 4px 8px; border-radius: 4px;">원/초</span>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="over_video_price">
                    영상통화
                </label>
                <div style="position: relative;">
                    <input type="number" name="over_video_price" id="over_video_price" class="form-control" placeholder="3.3" min="0" step="0.01" style="padding-right: 80px;">
                    <span style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 15px; color: #6b7280; pointer-events: none; background-color: #f0f9ff; padding: 4px 8px; border-radius: 4px;">원/초</span>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="over_sms_price">
                    단문메시지(SMS)
                </label>
                <div style="position: relative;">
                    <input type="number" name="over_sms_price" id="over_sms_price" class="form-control" placeholder="22" min="0" step="1" style="padding-right: 80px;">
                    <span style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 15px; color: #6b7280; pointer-events: none; background-color: #f0f9ff; padding: 4px 8px; border-radius: 4px;">원/건</span>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="over_mms_price">
                    텍스트형(MMS)
                </label>
                <div style="position: relative;">
                    <input type="number" name="over_mms_price" id="over_mms_price" class="form-control" placeholder="44" min="0" step="1" style="padding-right: 80px;">
                    <span style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 15px; color: #6b7280; pointer-events: none; background-color: #f0f9ff; padding: 4px 8px; border-radius: 4px;">원/건</span>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="over_mms_multimedia_price">
                    멀티미디어형(MMS)
                </label>
                <div style="position: relative;">
                    <input type="number" name="over_mms_multimedia_price" id="over_mms_multimedia_price" class="form-control" placeholder="110" min="0" step="1" style="padding-right: 80px;">
                    <span style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 15px; color: #6b7280; pointer-events: none; background-color: #f0f9ff; padding: 4px 8px; border-radius: 4px;">원/건</span>
                </div>
            </div>
        </div>
        
        <!-- 요금 정보 -->
        <div class="form-section">
            <div class="form-section-title">요금 정보</div>
            
            <div class="form-group">
                <label class="form-label" for="price_main">
                    월 요금 <span class="required">*</span>
                </label>
                <input type="number" name="price_main" id="price_main" class="form-control" required placeholder="예: 17000" min="0">
                <div class="form-help">원 단위로 입력하세요</div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="price_after">
                    할인 후 요금
                </label>
                <input type="text" name="price_after" id="price_after" class="form-control" placeholder="예: 7개월 이후 42,900원">
                <div class="form-help">할인 기간이 끝난 후 요금을 입력하세요</div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="discount_period">
                    할인 기간
                </label>
                <input type="text" name="discount_period" id="discount_period" class="form-control" placeholder="예: 7개월">
            </div>
        </div>
        
        <!-- 혜택 및 유의사항 -->
        <div class="form-section">
            <div class="form-section-title">혜택 및 유의사항</div>
            
            <div class="form-group">
                <label class="form-label">혜택 및 유의사항</label>
                <div id="benefits-container">
                    <div class="gift-input-group">
                        <textarea name="benefits[]" class="form-textarea" style="min-height: 80px;" placeholder="혜택 및 유의사항을 입력하세요"></textarea>
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addBenefitField()">+ 항목 추가</button>
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
let benefitCount = 1;

// 유지기간 필드 직접입력 처리
document.getElementById('contract_period_type').addEventListener('change', function() {
    const container = document.getElementById('contract_period_input_container');
    if (this.value === '직접입력') {
        container.style.display = 'block';
        document.getElementById('contract_period').required = true;
    } else {
        container.style.display = 'none';
        document.getElementById('contract_period').required = false;
        document.getElementById('contract_period').value = '';
    }
});

// 통화 필드 직접입력 처리
document.getElementById('call_type').addEventListener('change', function() {
    const container = document.getElementById('call_input_container');
    if (this.value === '직접입력') {
        container.style.display = 'block';
        document.getElementById('call_amount').required = true;
    } else {
        container.style.display = 'none';
        document.getElementById('call_amount').required = false;
        document.getElementById('call_amount').value = '';
    }
});

// 문자 필드 직접입력 처리
document.getElementById('sms_type').addEventListener('change', function() {
    const container = document.getElementById('sms_input_container');
    if (this.value === '직접입력') {
        container.style.display = 'block';
        document.getElementById('sms_amount').required = true;
    } else {
        container.style.display = 'none';
        document.getElementById('sms_amount').required = false;
        document.getElementById('sms_amount').value = '';
    }
});

// 데이터 제공량 필드 직접입력 처리
document.getElementById('data_type').addEventListener('change', function() {
    const container = document.getElementById('data_input_container');
    if (this.value === '직접입력') {
        container.style.display = 'block';
        document.getElementById('data_amount').required = true;
    } else {
        container.style.display = 'none';
        document.getElementById('data_amount').required = false;
        document.getElementById('data_amount').value = '';
    }
});

// 모바일 핫스팟 필드 직접입력 처리
document.getElementById('mobile_hotspot_type').addEventListener('change', function() {
    const container = document.getElementById('mobile_hotspot_input_container');
    if (this.value === '직접입력') {
        container.style.display = 'block';
        document.getElementById('mobile_hotspot').required = true;
    } else {
        container.style.display = 'none';
        document.getElementById('mobile_hotspot').required = false;
        document.getElementById('mobile_hotspot').value = '';
    }
});

// 일반 유심 가격 필드 직접입력 처리
document.getElementById('regular_sim_price_type').addEventListener('change', function() {
    const container = document.getElementById('regular_sim_price_input_container');
    if (this.value === '직접입력') {
        container.style.display = 'block';
        document.getElementById('regular_sim_price').required = true;
    } else {
        container.style.display = 'none';
        document.getElementById('regular_sim_price').required = false;
        document.getElementById('regular_sim_price').value = '';
    }
});

// NFC 유심 가격 필드 직접입력 처리
document.getElementById('nfc_sim_price_type').addEventListener('change', function() {
    const container = document.getElementById('nfc_sim_price_input_container');
    if (this.value === '직접입력') {
        container.style.display = 'block';
        document.getElementById('nfc_sim_price').required = true;
    } else {
        container.style.display = 'none';
        document.getElementById('nfc_sim_price').required = false;
        document.getElementById('nfc_sim_price').value = '';
    }
});

// eSIM 가격 필드 직접입력 처리
document.getElementById('esim_price_type').addEventListener('change', function() {
    const container = document.getElementById('esim_price_input_container');
    if (this.value === '직접입력') {
        container.style.display = 'block';
        document.getElementById('esim_price').required = true;
    } else {
        container.style.display = 'none';
        document.getElementById('esim_price').required = false;
        document.getElementById('esim_price').value = '';
    }
});

// NFC 유심 배송 필드 입력 처리
document.getElementById('nfc_sim_available').addEventListener('change', function() {
    const container = document.getElementById('nfc_sim_delivery_input_container');
    if (this.value === '배송가능') {
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
        document.getElementById('nfc_sim_delivery').value = '';
    }
});

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

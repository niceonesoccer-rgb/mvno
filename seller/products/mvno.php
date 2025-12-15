<?php

/**
 * 판매자 알뜰폰 상품 등록 페이지
 * 경로: /seller/products/mvno.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

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

// 정수 필드 포맷팅 함수: 소수점 제거하고 정수로만 표시
function formatIntegerForInput($value) {
    if ($value === null || $value === '') {
        return '';
    }
    return (string)intval(floatval($value));
}

// 소수 가능 필드 포맷팅 함수: 소수점이 0이면 정수로, 있으면 소수점 유지
function formatDecimalForInput($value) {
    if ($value === null || $value === '') {
        return '';
    }
    $floatValue = floatval($value);
    // 소수점 부분이 0이면 정수로 반환
    if ($floatValue == intval($floatValue)) {
        return (string)intval($floatValue);
    }
    // 소수점이 있으면 그대로 반환 (불필요한 0 제거)
    $str = (string)$floatValue;
    // 끝의 불필요한 0과 소수점 제거
    return rtrim(rtrim($str, '0'), '.');
}

// 수정 모드: 상품 데이터 불러오기
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$productData = null;
$isEditMode = false;

if ($productId > 0) {
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            $sellerId = (string)$currentUser['user_id'];
            
            // 기본 상품 정보 조회
            $stmt = $pdo->prepare("
                SELECT * FROM products 
                WHERE id = :product_id AND seller_id = :seller_id AND product_type = 'mvno' AND status != 'deleted'
            ");
            $stmt->execute([
                ':product_id' => $productId,
                ':seller_id' => $sellerId
            ]);
            $product = $stmt->fetch();
            
            if ($product) {
                // MVNO 상세 정보 조회
                $detailStmt = $pdo->prepare("
                    SELECT * FROM product_mvno_details 
                    WHERE product_id = :product_id
                ");
                $detailStmt->execute([':product_id' => $productId]);
                $detailStmt->setFetchMode(PDO::FETCH_ASSOC);
                $productDetail = $detailStmt->fetch();
                
                if ($productDetail) {
                    $isEditMode = true;
                    $productData = array_merge($product, $productDetail);
                    
                    // price_after 처리: null이면 'free' (공짜), 숫자(0 포함)면 그대로 표시
                    // PDO에서 가져온 값이 실제 null인지 확인 (0과 구분)
                    // PDO::FETCH_ASSOC를 사용하면 null 값이 PHP null로 반환됨
                    if ($productData['price_after'] === null || 
                        (is_string($productData['price_after']) && (trim($productData['price_after']) === '' || strtolower(trim($productData['price_after'])) === 'null'))) {
                        // null이면 'free'로 처리 (공짜)
                        $productData['price_after'] = 'free';
                        $productData['price_after_type_hidden'] = 'free';
                    } else {
                        // 숫자로 변환하여 저장 (0도 숫자이므로 그대로 저장)
                        $priceAfterValue = floatval($productData['price_after']);
                        $productData['price_after'] = $priceAfterValue;
                        $productData['price_after_type_hidden'] = 'custom';
                    }
                    
                    // JSON 필드 디코딩
                    if (!empty($productData['promotions'])) {
                        $productData['promotions'] = json_decode($productData['promotions'], true) ?: [];
                    } else {
                        $productData['promotions'] = [];
                    }
                    
                    if (!empty($productData['benefits'])) {
                        $productData['benefits'] = json_decode($productData['benefits'], true) ?: [];
                    } else {
                        $productData['benefits'] = [];
                    }
                    
                    if (!empty($productData['registration_types'])) {
                        $productData['registration_types'] = json_decode($productData['registration_types'], true) ?: [];
                    } else {
                        $productData['registration_types'] = [];
                    }
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Error loading product: " . $e->getMessage());
    }
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
        <h1><?php echo $isEditMode ? '알뜰폰 상품 수정' : '알뜰폰 상품 등록'; ?></h1>
        <p><?php echo $isEditMode ? '알뜰폰 요금제 정보를 수정하세요' : '새로운 알뜰폰 요금제를 등록하세요'; ?></p>
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
        <?php if ($isEditMode): ?>
            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
        <?php endif; ?>
        
        <!-- 판매 상태 -->
        <div class="form-section">
            <div class="form-section-title">판매 상태</div>
            <div class="form-group" style="max-width: 30%;">
                <label class="form-label" for="product_status">상태</label>
                <select name="product_status" id="product_status" class="form-select" style="width: auto; min-width: 120px;">
                    <option value="active" <?php echo ($isEditMode && isset($product['status']) && $product['status'] === 'active') ? 'selected' : (!$isEditMode ? 'selected' : ''); ?>>판매중</option>
                    <option value="inactive" <?php echo ($isEditMode && isset($product['status']) && $product['status'] === 'inactive') ? 'selected' : ''; ?>>판매종료</option>
                </select>
            </div>
        </div>
        
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
                        <option value="KT알뜰폰" <?php echo (isset($productData['provider']) && $productData['provider'] === 'KT알뜰폰') ? 'selected' : ''; ?>>KT알뜰폰</option>
                        <option value="SK알뜰폰" <?php echo (isset($productData['provider']) && $productData['provider'] === 'SK알뜰폰') ? 'selected' : ''; ?>>SK알뜰폰</option>
                        <option value="LG알뜰폰" <?php echo (isset($productData['provider']) && $productData['provider'] === 'LG알뜰폰') ? 'selected' : ''; ?>>LG알뜰폰</option>
                    </select>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="service_type">
                        데이터 속도 <span class="required">*</span>
                    </label>
                    <select name="service_type" id="service_type" class="form-select" required>
                        <option value="">선택하세요</option>
                        <option value="LTE" <?php echo (isset($productData['service_type']) && $productData['service_type'] === 'LTE') ? 'selected' : ''; ?>>LTE</option>
                        <option value="5G" <?php echo (isset($productData['service_type']) && $productData['service_type'] === '5G') ? 'selected' : ''; ?>>5G</option>
                        <option value="6G" <?php echo (isset($productData['service_type']) && $productData['service_type'] === '6G') ? 'selected' : ''; ?>>6G</option>
                    </select>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label">
                        가입 형태 <span class="required">*</span>
                    </label>
                    <div class="form-checkbox-group">
                        <?php
                        $registrationTypes = [];
                        if (!empty($productData['registration_types'])) {
                            if (is_string($productData['registration_types'])) {
                                $registrationTypes = json_decode($productData['registration_types'], true) ?: [];
                            } else {
                                $registrationTypes = $productData['registration_types'];
                            }
                        }
                        ?>
                        <div class="form-checkbox">
                            <input type="checkbox" name="registration_types[]" id="registration_type_new" value="신규" <?php echo in_array('신규', $registrationTypes) ? 'checked' : ''; ?>>
                            <label for="registration_type_new">신규</label>
                        </div>
                        <div class="form-checkbox">
                            <input type="checkbox" name="registration_types[]" id="registration_type_port" value="번이" <?php echo in_array('번이', $registrationTypes) ? 'checked' : ''; ?>>
                            <label for="registration_type_port">번이</label>
                        </div>
                        <div class="form-checkbox">
                            <input type="checkbox" name="registration_types[]" id="registration_type_change" value="기변" <?php echo in_array('기변', $registrationTypes) ? 'checked' : ''; ?>>
                            <label for="registration_type_change">기변</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="plan_name">
                    요금제명
                </label>
                <input type="text" name="plan_name" id="plan_name" class="form-control" required placeholder="데이터 100G 평생요금" maxlength="30" value="<?php echo isset($productData['plan_name']) ? htmlspecialchars($productData['plan_name']) : ''; ?>">
            </div>
            
            <div class="form-group" style="display: flex; gap: 16px; align-items: flex-start;">
                <div style="flex: 1;">
                    <label class="form-label" for="contract_period">
                        약정기간
                    </label>
                    <select name="contract_period" id="contract_period" class="form-select">
                        <option value="무약정" <?php echo (isset($productData['contract_period']) && $productData['contract_period'] === '무약정') ? 'selected' : ''; ?>>무약정</option>
                        <option value="직접입력" <?php echo (isset($productData['contract_period']) && $productData['contract_period'] === '직접입력') ? 'selected' : ''; ?>>직접입력</option>
                    </select>
                    <div id="contract_period_input" style="display: <?php echo (isset($productData['contract_period']) && $productData['contract_period'] === '직접입력') ? 'block' : 'none'; ?>; margin-top: 12px;">
                        <div class="input-with-unit" style="max-width: 200px;">
                            <input type="number" name="contract_period_days" id="contract_period_days" class="form-control" placeholder="일 수 입력" min="1" max="99999" maxlength="5" value="<?php echo isset($productData['contract_period_days']) ? htmlspecialchars($productData['contract_period_days']) : ''; ?>">
                            <span class="unit">일</span>
                        </div>
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="discount_period">
                        할인기간(프로모션기간)
                    </label>
                    <input type="text" name="discount_period" id="discount_period" class="form-control" placeholder="7개월" maxlength="10" value="<?php echo isset($productData['discount_period']) ? htmlspecialchars($productData['discount_period']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group" style="display: flex; gap: 16px; align-items: flex-start;">
                <div style="flex: 1;">
                    <label class="form-label" for="price_main">
                        월 요금 <span class="required">*</span>
                    </label>
                    <div class="input-with-unit" style="max-width: 200px;">
                        <input type="text" name="price_main" id="price_main" class="form-control" required placeholder="1500" maxlength="5" value="<?php echo isset($productData['price_main']) ? htmlspecialchars(formatIntegerForInput($productData['price_main'])) : ''; ?>">
                        <span class="unit">원</span>
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="price_after_type">
                        할인 후 요금(프로모션기간)
                    </label>
                    <select name="price_after_type" id="price_after_type" class="form-select" style="max-width: 200px;">
                        <option value="">선택하세요</option>
                        <option value="free" <?php echo (isset($productData['price_after']) && ($productData['price_after'] === null || $productData['price_after'] === 'free' || $productData['price_after'] === 'null')) ? 'selected' : ''; ?>>공짜</option>
                        <option value="custom" <?php echo (isset($productData['price_after']) && $productData['price_after'] !== null && $productData['price_after'] !== '' && $productData['price_after'] !== 'free' && $productData['price_after'] !== 'null') ? 'selected' : ''; ?>>직접입력</option>
                    </select>
                    <div id="price_after_input" style="display: <?php echo (isset($productData['price_after']) && $productData['price_after'] !== null && $productData['price_after'] !== '' && $productData['price_after'] !== 'free' && $productData['price_after'] !== 'null') ? 'block' : 'none'; ?>; margin-top: 12px;">
                        <div class="input-with-unit" style="max-width: 200px;">
                            <input type="text" id="price_after" class="form-control" placeholder="500" maxlength="5" value="<?php echo (isset($productData['price_after']) && $productData['price_after'] !== null && $productData['price_after'] !== '' && $productData['price_after'] !== 'free' && $productData['price_after'] !== 'null') ? htmlspecialchars(formatIntegerForInput($productData['price_after'])) : ''; ?>">
                            <span class="unit">원</span>
                        </div>
                    </div>
                    <input type="hidden" name="price_after" id="price_after_hidden" value="<?php echo (isset($productData['price_after']) && $productData['price_after'] !== null && $productData['price_after'] !== '' && $productData['price_after'] !== 'free' && $productData['price_after'] !== 'null') ? htmlspecialchars(formatIntegerForInput($productData['price_after'])) : ''; ?>">
                    <input type="hidden" name="price_after_type_hidden" id="price_after_type_hidden" value="<?php echo isset($productData['price_after_type_hidden']) ? htmlspecialchars($productData['price_after_type_hidden']) : ''; ?>">
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
                        <option value="무제한" <?php echo (isset($productData['data_amount']) && $productData['data_amount'] === '무제한') ? 'selected' : ''; ?>>무제한</option>
                        <option value="직접입력" <?php echo (isset($productData['data_amount']) && $productData['data_amount'] === '직접입력') ? 'selected' : ''; ?>>직접입력</option>
                    </select>
                    <div id="data_amount_input" style="display: <?php echo (isset($productData['data_amount']) && $productData['data_amount'] === '직접입력') ? 'block' : 'none'; ?>; margin-top: 12px;">
                        <div class="input-with-unit" style="max-width: 200px;">
                            <input type="number" name="data_amount_value" id="data_amount_value" class="form-control" placeholder="100" min="0" max="99999" maxlength="5" style="padding-right: 70px;" value="<?php echo isset($productData['data_amount_value']) ? htmlspecialchars($productData['data_amount_value']) : ''; ?>">
                            <select name="data_unit" id="data_unit" class="form-select" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 60px; height: auto; border: none; background: transparent; padding: 0 20px 0 0; appearance: auto; -webkit-appearance: menulist; -moz-appearance: menulist; cursor: pointer; font-size: 15px; color: #6b7280;">
                                <option value="GB" <?php echo (isset($productData['data_unit']) && $productData['data_unit'] === 'GB') ? 'selected' : ''; ?>>GB</option>
                                <option value="MB" <?php echo (isset($productData['data_unit']) && $productData['data_unit'] === 'MB') ? 'selected' : ''; ?>>MB</option>
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
                        <option value="없음" <?php echo (isset($productData['data_additional']) && $productData['data_additional'] === '없음') ? 'selected' : ''; ?>>없음</option>
                        <option value="직접입력" <?php echo (isset($productData['data_additional']) && $productData['data_additional'] === '직접입력') ? 'selected' : ''; ?>>직접입력</option>
                    </select>
                    <div id="data_additional_input" style="display: <?php echo (isset($productData['data_additional']) && $productData['data_additional'] === '직접입력') ? 'block' : 'none'; ?>; margin-top: 12px;">
                        <input type="text" name="data_additional_value" id="data_additional_value" class="form-control" placeholder="매일 20GB" maxlength="15" value="<?php echo isset($productData['data_additional_value']) ? htmlspecialchars($productData['data_additional_value']) : ''; ?>">
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="data_exhausted">
                        데이터 소진시
                    </label>
                    <select name="data_exhausted" id="data_exhausted" class="form-select">
                        <option value="">선택하세요</option>
                        <option value="5Mbps 무제한" <?php echo (isset($productData['data_exhausted']) && $productData['data_exhausted'] === '5Mbps 무제한') ? 'selected' : ''; ?>>5Mbps 무제한</option>
                        <option value="3Mbps 무제한" <?php echo (isset($productData['data_exhausted']) && $productData['data_exhausted'] === '3Mbps 무제한') ? 'selected' : ''; ?>>3Mbps 무제한</option>
                        <option value="1Mbps 무제한" <?php echo (isset($productData['data_exhausted']) && $productData['data_exhausted'] === '1Mbps 무제한') ? 'selected' : ''; ?>>1Mbps 무제한</option>
                        <option value="직접입력" <?php echo (isset($productData['data_exhausted']) && $productData['data_exhausted'] === '직접입력') ? 'selected' : ''; ?>>직접입력</option>
                    </select>
                    <div id="data_exhausted_input" style="display: <?php echo (isset($productData['data_exhausted']) && $productData['data_exhausted'] === '직접입력') ? 'block' : 'none'; ?>; margin-top: 12px;">
                        <input type="text" name="data_exhausted_value" id="data_exhausted_value" class="form-control" placeholder="10Mbps 무제한" maxlength="50" value="<?php echo isset($productData['data_exhausted_value']) ? htmlspecialchars($productData['data_exhausted_value']) : ''; ?>">
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
                        <option value="무제한" <?php echo (isset($productData['call_type']) && $productData['call_type'] === '무제한') ? 'selected' : ''; ?>>무제한</option>
                        <option value="기본제공" <?php echo (isset($productData['call_type']) && $productData['call_type'] === '기본제공') ? 'selected' : ''; ?>>기본제공</option>
                        <option value="직접입력" <?php echo (isset($productData['call_type']) && $productData['call_type'] === '직접입력') ? 'selected' : ''; ?>>직접입력</option>
                    </select>
                    <div id="call_type_input" style="display: <?php echo (isset($productData['call_type']) && $productData['call_type'] === '직접입력') ? 'block' : 'none'; ?>; margin-top: 12px;">
                        <div class="input-with-unit" style="max-width: 200px;">
                            <input type="number" name="call_amount" id="call_amount" class="form-control" placeholder="300" min="0" max="99999" maxlength="5" value="<?php echo isset($productData['call_amount']) ? htmlspecialchars($productData['call_amount']) : ''; ?>">
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
                        <option value="무제한" <?php echo (isset($productData['additional_call_type']) && $productData['additional_call_type'] === '무제한') ? 'selected' : ''; ?>>무제한</option>
                        <option value="기본제공" <?php echo (isset($productData['additional_call_type']) && $productData['additional_call_type'] === '기본제공') ? 'selected' : ''; ?>>기본제공</option>
                        <option value="직접입력" <?php echo (isset($productData['additional_call_type']) && $productData['additional_call_type'] === '직접입력') ? 'selected' : ''; ?>>직접입력</option>
                    </select>
                    <div id="additional_call_input" style="display: <?php echo (isset($productData['additional_call_type']) && $productData['additional_call_type'] === '직접입력') ? 'block' : 'none'; ?>; margin-top: 12px;">
                        <div class="input-with-unit" style="max-width: 200px;">
                            <input type="number" name="additional_call" id="additional_call" class="form-control" placeholder="300" min="0" max="99999" maxlength="5" value="<?php echo isset($productData['additional_call']) ? htmlspecialchars($productData['additional_call']) : ''; ?>">
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
                        <option value="무제한" <?php echo (isset($productData['sms_type']) && $productData['sms_type'] === '무제한') ? 'selected' : ''; ?>>무제한</option>
                        <option value="기본제공" <?php echo (isset($productData['sms_type']) && $productData['sms_type'] === '기본제공') ? 'selected' : ''; ?>>기본제공</option>
                        <option value="직접입력" <?php echo (isset($productData['sms_type']) && $productData['sms_type'] === '직접입력') ? 'selected' : ''; ?>>직접입력</option>
                    </select>
                    <div id="sms_type_input" style="display: <?php echo (isset($productData['sms_type']) && $productData['sms_type'] === '직접입력') ? 'block' : 'none'; ?>; margin-top: 12px;">
                        <div class="input-with-unit" style="max-width: 200px;">
                            <input type="number" name="sms_amount" id="sms_amount" class="form-control" placeholder="300" min="0" max="99999" maxlength="5" value="<?php echo isset($productData['sms_amount']) ? htmlspecialchars($productData['sms_amount']) : ''; ?>">
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
                        <option value="기본 제공량 내에서 사용" <?php echo (isset($productData['mobile_hotspot']) && $productData['mobile_hotspot'] === '기본 제공량 내에서 사용') ? 'selected' : ''; ?>>기본 제공량 내에서 사용</option>
                        <option value="직접선택" <?php echo (isset($productData['mobile_hotspot']) && $productData['mobile_hotspot'] === '직접선택') ? 'selected' : ''; ?>>직접선택</option>
                    </select>
                    <div id="mobile_hotspot_input" style="display: <?php echo (isset($productData['mobile_hotspot']) && $productData['mobile_hotspot'] === '직접선택') ? 'block' : 'none'; ?>; margin-top: 12px;">
                        <input type="text" name="mobile_hotspot_value" id="mobile_hotspot_value" class="form-control" placeholder="50GB" maxlength="10" value="<?php echo isset($productData['mobile_hotspot_value']) ? htmlspecialchars($productData['mobile_hotspot_value']) : ''; ?>">
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
                        <option value="배송불가" <?php echo (isset($productData['regular_sim_available']) && $productData['regular_sim_available'] === '배송불가') ? 'selected' : ''; ?>>배송불가</option>
                        <option value="배송가능" <?php echo (isset($productData['regular_sim_available']) && $productData['regular_sim_available'] === '배송가능') ? 'selected' : ''; ?>>배송가능</option>
                    </select>
                    <div id="regular_sim_price_input" style="display: <?php echo (isset($productData['regular_sim_available']) && $productData['regular_sim_available'] === '배송가능') ? 'block' : 'none'; ?>; margin-top: 12px;">
                        <div class="input-with-unit" style="max-width: 200px;">
                            <input type="text" name="regular_sim_price" id="regular_sim_price" class="form-control" placeholder="2200" maxlength="5" value="<?php echo isset($productData['regular_sim_price']) ? htmlspecialchars(formatIntegerForInput($productData['regular_sim_price'])) : ''; ?>">
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
                        <option value="배송불가" <?php echo (isset($productData['nfc_sim_available']) && $productData['nfc_sim_available'] === '배송불가') ? 'selected' : ''; ?>>배송불가</option>
                        <option value="배송가능" <?php echo (isset($productData['nfc_sim_available']) && $productData['nfc_sim_available'] === '배송가능') ? 'selected' : ''; ?>>배송가능</option>
                    </select>
                    <div id="nfc_sim_price_input" style="display: <?php echo (isset($productData['nfc_sim_available']) && $productData['nfc_sim_available'] === '배송가능') ? 'block' : 'none'; ?>; margin-top: 12px;">
                        <div class="input-with-unit" style="max-width: 200px;">
                            <input type="text" name="nfc_sim_price" id="nfc_sim_price" class="form-control" placeholder="4400" maxlength="5" value="<?php echo isset($productData['nfc_sim_price']) ? htmlspecialchars(formatIntegerForInput($productData['nfc_sim_price'])) : ''; ?>">
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
                        <option value="개통불가" <?php echo (isset($productData['esim_available']) && $productData['esim_available'] === '개통불가') ? 'selected' : ''; ?>>개통불가</option>
                        <option value="개통가능" <?php echo (isset($productData['esim_available']) && $productData['esim_available'] === '개통가능') ? 'selected' : ''; ?>>개통가능</option>
                    </select>
                    <div id="esim_price_input" style="display: <?php echo (isset($productData['esim_available']) && $productData['esim_available'] === '개통가능') ? 'block' : 'none'; ?>; margin-top: 12px;">
                        <div class="input-with-unit" style="max-width: 200px;">
                            <input type="text" name="esim_price" id="esim_price" class="form-control" placeholder="2750" maxlength="5" value="<?php echo isset($productData['esim_price']) ? htmlspecialchars(formatIntegerForInput($productData['esim_price'])) : ''; ?>">
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
                        <input type="text" name="over_data_price" id="over_data_price" class="form-control" placeholder="22.53" maxlength="6" value="<?php echo isset($productData['over_data_price']) ? htmlspecialchars(formatDecimalForInput($productData['over_data_price'])) : ''; ?>">
                        <span class="unit">원/MB</span>
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="over_voice_price">
                        음성
                    </label>
                    <div class="input-with-unit" style="max-width: 200px;">
                        <input type="text" name="over_voice_price" id="over_voice_price" class="form-control" placeholder="1.98" maxlength="6" value="<?php echo isset($productData['over_voice_price']) ? htmlspecialchars(formatDecimalForInput($productData['over_voice_price'])) : ''; ?>">
                        <span class="unit">원/초</span>
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="over_video_price">
                        영상통화
                    </label>
                    <div class="input-with-unit" style="max-width: 200px;">
                        <input type="text" name="over_video_price" id="over_video_price" class="form-control" placeholder="3.3" maxlength="6" value="<?php echo isset($productData['over_video_price']) ? htmlspecialchars(formatDecimalForInput($productData['over_video_price'])) : ''; ?>">
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
                        <input type="text" name="over_sms_price" id="over_sms_price" class="form-control" placeholder="22" maxlength="5" value="<?php echo isset($productData['over_sms_price']) ? htmlspecialchars(formatIntegerForInput($productData['over_sms_price'])) : ''; ?>">
                        <span class="unit">원/건</span>
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="over_lms_price">
                        텍스트형(LMS,MMS)
                    </label>
                    <div class="input-with-unit" style="max-width: 200px;">
                        <input type="text" name="over_lms_price" id="over_lms_price" class="form-control" placeholder="33" maxlength="5" value="<?php echo isset($productData['over_lms_price']) ? htmlspecialchars(formatIntegerForInput($productData['over_lms_price'])) : ''; ?>">
                        <span class="unit">원/건</span>
                    </div>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="over_mms_price">
                        멀티미디어형(MMS)
                    </label>
                    <div class="input-with-unit" style="max-width: 200px;">
                        <input type="text" name="over_mms_price" id="over_mms_price" class="form-control" placeholder="110" maxlength="5" value="<?php echo isset($productData['over_mms_price']) ? htmlspecialchars(formatIntegerForInput($productData['over_mms_price'])) : ''; ?>">
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
                <input type="text" name="promotion_title" id="promotion_title" class="form-control" placeholder="쿠폰북 최대 5만원 지급" maxlength="100" value="<?php echo isset($productData['promotion_title']) ? htmlspecialchars($productData['promotion_title']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">항목</label>
                <div id="promotion-container">
                    <?php if (!empty($productData['promotions']) && is_array($productData['promotions'])): ?>
                        <?php foreach ($productData['promotions'] as $index => $promotion): ?>
                            <div class="gift-input-group">
                                <input type="text" name="promotions[]" class="form-control" placeholder="Npay 2,000" maxlength="30" value="<?php echo htmlspecialchars($promotion); ?>">
                                <?php if ($index === 0): ?>
                                    <button type="button" class="btn-add-item" onclick="addPromotionField()">추가</button>
                                <?php else: ?>
                                    <button type="button" class="btn-remove" onclick="removePromotionField(this)">삭제</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="gift-input-group">
                            <input type="text" name="promotions[]" class="form-control" placeholder="Npay 2,000" maxlength="30">
                            <button type="button" class="btn-add-item" onclick="addPromotionField()">추가</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- 혜택 및 유의사항 -->
        <div class="form-section">
            <div class="form-section-title">혜택 및 유의사항</div>
            
            <div class="form-group">
                <div id="benefits-container">
                    <?php if (!empty($productData['benefits']) && is_array($productData['benefits'])): ?>
                        <?php foreach ($productData['benefits'] as $index => $benefit): ?>
                            <div class="gift-input-group">
                                <textarea name="benefits[]" class="form-textarea" style="min-height: 80px;" placeholder="혜택 및 유의사항을 입력하세요"><?php echo htmlspecialchars($benefit); ?></textarea>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="gift-input-group">
                            <textarea name="benefits[]" class="form-textarea" style="min-height: 80px;" placeholder="혜택 및 유의사항을 입력하세요"></textarea>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- 신청 후 리다이렉트 URL (선택사항) -->
        <div class="form-section">
            <div class="form-section-title">신청 후 리다이렉트 URL</div>
            <div class="form-group" style="max-width: 70%;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; color: #374151; margin-bottom: 12px;">
                    <input type="checkbox" id="enable_redirect_url" style="width: 18px; height: 18px; cursor: pointer;">
                    <span>URL 입력</span>
                </label>
                <div id="redirect_url_container" style="display: none;">
                    <label class="form-label" for="redirect_url">
                        URL
                    </label>
                    <input type="text" name="redirect_url" id="redirect_url" class="form-control" 
                        placeholder="example.com 또는 https://example.com" 
                        value="<?php echo ($isEditMode && isset($productDetail['redirect_url'])) ? htmlspecialchars($productDetail['redirect_url']) : ''; ?>"
                        style="padding: 10px 14px; font-size: 14px;">
                    <small class="form-text" style="display: block; margin-top: 8px; color: #6b7280; font-size: 13px;">
                        입력 시: 고객 신청 후 해당 URL로 이동합니다.<br>
                        미입력 시: 고객 신청 서만 접수(성함, 전화번호, 이메일주소)
                    </small>
                </div>
            </div>
        </div>
        
        <!-- 제출 버튼 -->
        <div class="form-actions">
            <a href="/MVNO/seller/products/mvno-list.php" class="btn btn-secondary">취소</a>
            <button type="submit" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 13l4 4L19 7"/>
                </svg>
                <?php echo $isEditMode ? '수정하기' : '등록하기'; ?>
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
            console.log('toggleInputField: 요소를 찾을 수 없음', selectId, inputContainerId);
            return;
        }
        
        // 초기 상태 설정
        const initialValue = select.value;
        const isInitiallyShow = initialValue === triggerValue;
        // display 속성만 업데이트 (margin-top 등 다른 스타일 유지)
        if (isInitiallyShow) {
            container.style.display = 'block';
        } else {
            container.style.display = 'none';
        }
        console.log('toggleInputField init:', selectId, 'value:', initialValue, 'triggerValue:', triggerValue, 'isShow:', isInitiallyShow, 'display:', container.style.display, 'computed:', window.getComputedStyle(container).display);
        
        if (inputId) {
            const input = document.getElementById(inputId);
            if (input) {
                // 필드가 표시되어 있으면 항상 활성화 (수정 모드에서도 입력 가능하도록)
                if (isInitiallyShow) {
                    input.removeAttribute('disabled');
                    input.disabled = false;
                } else {
                    input.setAttribute('disabled', 'disabled');
                    input.disabled = true;
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
            console.log('toggleInputField change:', selectId, 'value:', this.value, 'triggerValue:', triggerValue, 'isShow:', isShow);
            console.log('container:', container, 'display before:', container.style.display);
            // display 속성만 업데이트 (margin-top 등 다른 스타일 유지)
            container.style.display = isShow ? 'block' : 'none';
            console.log('container display after:', container.style.display);
            
            if (inputId) {
                const input = document.getElementById(inputId);
                if (input) {
                    if (isShow) {
                        input.removeAttribute('disabled');
                        input.disabled = false; // 명시적으로 활성화
                        input.focus();
                        console.log('Input enabled:', inputId);
                    } else {
                        input.setAttribute('disabled', 'disabled');
                        input.disabled = true; // 명시적으로 비활성화
                        input.value = '';
                        console.log('Input disabled:', inputId);
                    }
                } else {
                    console.log('Input not found:', inputId);
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
                // 천단위 구분자 제거 후 숫자로 변환
                const numValue = parseInt(this.value.replace(/,/g, ''));
                if (!isNaN(numValue)) {
                    this.value = numValue.toLocaleString('ko-KR');
                }
            }
        });
        
        input.addEventListener('focus', function() {
            // 천단위 구분자 제거
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
    const regularSimPriceInput = document.getElementById('regular_sim_price');
    if (regularSimPriceInput) {
        // 필드가 표시되어 있으면 활성화
        if (regularSimPriceInput.closest('#regular_sim_price_input').style.display !== 'none') {
            regularSimPriceInput.removeAttribute('disabled');
        }
        setupPriceInput('regular_sim_price', false);
        // 페이지 로드 시 천단위 구분자 제거 (입력 필드는 숫자만 표시)
        if (regularSimPriceInput.value) {
            regularSimPriceInput.value = regularSimPriceInput.value.replace(/,/g, '');
        }
    }
    
    // NFC 유심
    toggleInputField('nfc_sim_available', 'nfc_sim_price_input', '배송가능', 'nfc_sim_price');
    const nfcSimPriceInput = document.getElementById('nfc_sim_price');
    if (nfcSimPriceInput) {
        // 필드가 표시되어 있으면 활성화
        if (nfcSimPriceInput.closest('#nfc_sim_price_input').style.display !== 'none') {
            nfcSimPriceInput.removeAttribute('disabled');
        }
        setupPriceInput('nfc_sim_price', false);
        if (nfcSimPriceInput.value) {
            nfcSimPriceInput.value = nfcSimPriceInput.value.replace(/,/g, '');
        }
    }
    
    // eSIM
    toggleInputField('esim_available', 'esim_price_input', '개통가능', 'esim_price');
    const esimPriceInput = document.getElementById('esim_price');
    if (esimPriceInput) {
        // 필드가 표시되어 있으면 활성화
        if (esimPriceInput.closest('#esim_price_input').style.display !== 'none') {
            esimPriceInput.removeAttribute('disabled');
        }
        setupPriceInput('esim_price', false);
        if (esimPriceInput.value) {
            esimPriceInput.value = esimPriceInput.value.replace(/,/g, '');
        }
    }
    
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
    
    const priceAfterTypeHidden = document.getElementById('price_after_type_hidden');
    
    if (priceAfterType && priceAfterInput && priceAfterHidden && priceAfterTypeHidden) {
        // 페이지 로드 시 초기값 설정
        if (priceAfterType.value === 'free') {
            priceAfterTypeHidden.value = 'free';
        } else if (priceAfterType.value === 'custom') {
            priceAfterTypeHidden.value = 'custom';
        }
        
        priceAfterType.addEventListener('change', function() {
            if (this.value === 'free') {
                // 공짜 선택 시: hidden 필드는 빈 문자열로 설정 (API에서 price_after_type_hidden으로 판단)
                priceAfterInput.style.display = 'none';
                priceAfterHidden.value = '';
                priceAfterTypeHidden.value = 'free';
                if (priceAfterField) priceAfterField.value = '';
            } else if (this.value === 'custom') {
                // 직접입력 선택 시
                priceAfterInput.style.display = 'block';
                priceAfterHidden.value = '';
                priceAfterTypeHidden.value = 'custom';
                if (priceAfterField) {
                    priceAfterField.focus();
                }
            } else {
                // 선택 안함
                priceAfterInput.style.display = 'none';
                priceAfterHidden.value = '';
                priceAfterTypeHidden.value = '';
                if (priceAfterField) priceAfterField.value = '';
            }
        });
        
        // 직접입력 필드 값 변경 시 hidden 필드 업데이트 (0도 가능)
        if (priceAfterField) {
            priceAfterField.addEventListener('input', function() {
                if (priceAfterType.value === 'custom') {
                    const value = this.value.replace(/[^0-9]/g, '');
                    priceAfterHidden.value = value;
                    priceAfterTypeHidden.value = 'custom';
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
                if (typeof showAlert === 'function') {
                    showAlert('통화를 입력해주세요.', '입력 오류');
                } else {
                    alert('통화를 입력해주세요.');
                }
                if (callAmount) callAmount.focus();
                return;
            }
        }
        
        const smsType = document.getElementById('sms_type');
        const smsAmount = document.getElementById('sms_amount');
        if (smsType && smsType.value === '직접입력') {
            if (!smsAmount || !smsAmount.value.trim()) {
                if (typeof showAlert === 'function') {
                    showAlert('문자를 입력해주세요.', '입력 오류');
                } else {
                    alert('문자를 입력해주세요.');
                }
                if (smsAmount) smsAmount.focus();
                return;
            }
        }
        
        const dataAmount = document.getElementById('data_amount');
        const dataAmountValue = document.getElementById('data_amount_value');
        if (dataAmount && dataAmount.value === '직접입력') {
            if (!dataAmountValue || !dataAmountValue.value.trim()) {
                if (typeof showAlert === 'function') {
                    showAlert('데이터 제공량을 입력해주세요.', '입력 오류');
                } else {
                    alert('데이터 제공량을 입력해주세요.');
                }
                if (dataAmountValue) dataAmountValue.focus();
                return;
            }
        }
        
        const dataAdditional = document.getElementById('data_additional');
        const dataAdditionalValue = document.getElementById('data_additional_value');
        if (dataAdditional && dataAdditional.value === '직접입력') {
            if (!dataAdditionalValue || !dataAdditionalValue.value.trim()) {
                if (typeof showAlert === 'function') {
                    showAlert('데이터 추가제공을 입력해주세요.', '입력 오류');
                } else {
                    alert('데이터 추가제공을 입력해주세요.');
                }
                if (dataAdditionalValue) dataAdditionalValue.focus();
                return;
            }
        }
        
        const additionalCallType = document.getElementById('additional_call_type');
        const additionalCallInput = document.getElementById('additional_call');
        if (additionalCallType && additionalCallType.value === '직접입력') {
            if (!additionalCallInput || !additionalCallInput.value.trim()) {
                if (typeof showAlert === 'function') {
                    showAlert('부가·영상통화를 입력해주세요.', '입력 오류');
                } else {
                    alert('부가·영상통화를 입력해주세요.');
                }
                if (additionalCallInput) additionalCallInput.focus();
                return;
            }
        }
        
        const mobileHotspot = document.getElementById('mobile_hotspot');
        const mobileHotspotInput = document.getElementById('mobile_hotspot_value');
        if (mobileHotspot && mobileHotspot.value === '직접선택') {
            if (!mobileHotspotInput || !mobileHotspotInput.value.trim()) {
                if (typeof showAlert === 'function') {
                    showAlert('테더링(핫스팟)을 입력해주세요.', '입력 오류');
                } else {
                    alert('테더링(핫스팟)을 입력해주세요.');
                }
                if (mobileHotspotInput) mobileHotspotInput.focus();
                return;
            }
        }
        
        // 데이터 추가제공 값 최종 처리
        if (dataAdditional) {
            const dataAdditionalValue = document.getElementById('data_additional_value');
            if (dataAdditional.value === '없음') {
                // "없음" 선택 시 값 초기화
                if (dataAdditionalValue) {
                    dataAdditionalValue.value = '';
                    dataAdditionalValue.disabled = false;
                }
            } else if (dataAdditional.value === '직접입력') {
                // "직접입력" 선택 시 입력값 사용 (이미 검증됨)
                if (dataAdditionalValue) {
                    dataAdditionalValue.disabled = false;
                }
            } else {
                // 선택 안함
                if (dataAdditionalValue) {
                    dataAdditionalValue.value = '';
                    dataAdditionalValue.disabled = false;
                }
            }
        }
        
        // 가입 형태 필수 검증
        const registrationTypes = document.querySelectorAll('input[name="registration_types[]"]:checked');
        if (registrationTypes.length === 0) {
            if (typeof showAlert === 'function') {
                showAlert('가입 형태를 최소 하나 이상 선택해주세요.', '입력 오류');
            } else {
                alert('가입 형태를 최소 하나 이상 선택해주세요.');
            }
            e.preventDefault();
            return;
        }
        
        // plan_name 필드 확인
        const planNameField = document.getElementById('plan_name');
        if (planNameField && !planNameField.value.trim()) {
            if (typeof showAlert === 'function') {
                showAlert('요금제명을 입력해주세요.', '오류', true);
            } else {
                alert('요금제명을 입력해주세요.');
            }
            planNameField.focus();
            e.preventDefault();
            return;
        }
        
        // 할인 후 요금 값 최종 처리 (FormData 생성 전에 실행)
        const priceAfterType = document.getElementById('price_after_type');
        const priceAfterHidden = document.getElementById('price_after_hidden');
        const priceAfterTypeHidden = document.getElementById('price_after_type_hidden');
        const priceAfterField = document.getElementById('price_after');
        
        if (priceAfterType && priceAfterHidden && priceAfterTypeHidden) {
            // 현재 선택된 값을 다시 확인하여 설정
            if (priceAfterType.value === 'free') {
                // 공짜 선택 시: hidden 필드는 빈 문자열로 설정 (API에서 price_after_type_hidden으로 판단)
                priceAfterHidden.value = '';
                priceAfterTypeHidden.value = 'free';
                if (priceAfterField) priceAfterField.value = '';
            } else if (priceAfterType.value === 'custom' && priceAfterField) {
                // 직접입력 시 입력값 사용 (0도 가능)
                const inputValue = priceAfterField.value.replace(/[^0-9]/g, '');
                priceAfterHidden.value = inputValue || '0';
                priceAfterTypeHidden.value = 'custom';
            } else {
                // 선택 안함
                priceAfterHidden.value = '';
                priceAfterTypeHidden.value = '';
            }
        }
        
        const formData = new FormData(this);
        
        // redirect_url 처리: 체크박스가 체크되지 않았으면 빈 값으로 설정
        const enableRedirectUrlCheckbox = document.getElementById('enable_redirect_url');
        const redirectUrlInput = document.getElementById('redirect_url');
        if (enableRedirectUrlCheckbox && redirectUrlInput) {
            if (!enableRedirectUrlCheckbox.checked) {
                // 체크박스가 체크되지 않았으면 redirect_url을 빈 값으로 설정
                formData.set('redirect_url', '');
            } else if (redirectUrlInput.value.trim() === '') {
                // 체크박스가 체크되었지만 값이 비어있으면 빈 값으로 설정
                formData.set('redirect_url', '');
            } else {
                // 체크박스가 체크되고 값이 있으면 모든 공백 제거 후 저장
                const urlValue = redirectUrlInput.value.replace(/\s+/g, '').trim();
                formData.set('redirect_url', urlValue);
            }
        }
        
        // 실제 제출 함수
        const submitForm = function() {
            fetch('/MVNO/api/product-register-mvno.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 수정 모드면 등록 상품 리스트로, 등록 모드면 등록 페이지로
                    const productId = formData.get('product_id');
                    if (productId && productId !== '0') {
                        // 수정 모드: 등록 상품 리스트로 리다이렉트
                        window.location.href = '/MVNO/seller/products/mvno-list.php?success=1';
                    } else {
                        // 등록 모드: 등록 페이지로 리다이렉트
                        window.location.href = '/MVNO/seller/products/mvno.php?success=1';
                    }
                } else {
                    if (typeof showAlert === 'function') {
                        showAlert(data.message || '상품 등록에 실패했습니다.', '오류', true);
                    } else {
                        alert(data.message || '상품 등록에 실패했습니다.');
                    }
                }
            })
            .catch(error => {
                const errorMessage = '상품 등록 중 오류가 발생했습니다.';
                if (typeof showAlert === 'function') {
                    showAlert(errorMessage, '오류', true);
                } else {
                    alert(errorMessage);
                }
            });
        };
        
        // 수정 모드일 때 확인 모달 띄우기
        const productId = formData.get('product_id');
        if (productId && productId !== '0') {
            // 수정 모드: 확인 모달 띄우기
            if (typeof showConfirm === 'function') {
                showConfirm('상품을 수정하시겠습니까?', '수정 확인', true).then(function(confirmed) {
                    if (confirmed) {
                        submitForm();
                    }
                });
            } else {
                if (confirm('상품을 수정하시겠습니까?')) {
                    submitForm();
                }
            }
        } else {
            // 등록 모드: 바로 제출
            submitForm();
        }
    });
});

// 프로모션 필드 추가/삭제 함수 (mno.php 방식 참조)
function addPromotionField() {
    const container = document.getElementById('promotion-container');
    if (!container) return;
    const newField = document.createElement('div');
    newField.className = 'gift-input-group';
    newField.innerHTML = `
        <input type="text" name="promotions[]" class="form-control" placeholder="Npay 2,000" maxlength="30">
        <button type="button" class="btn-remove" onclick="removePromotionField(this)">삭제</button>
    `;
    container.appendChild(newField);
}

function removePromotionField(button) {
    const container = document.getElementById('promotion-container');
    if (container && container.children.length > 1) {
        button.parentElement.remove();
    }
}

// 혜택 필드 추가/삭제 함수
function addBenefitField() {
    const container = document.getElementById('benefits-container');
    if (!container) return;
    const newField = document.createElement('div');
    newField.className = 'gift-input-group';
    newField.innerHTML = `
        <textarea name="benefits[]" class="form-textarea" style="min-height: 80px;" placeholder="혜택 및 유의사항을 입력하세요"></textarea>
        <button type="button" class="btn-remove" onclick="removeBenefitField(this)">삭제</button>
    `;
    container.appendChild(newField);
}

function removeBenefitField(button) {
    const container = document.getElementById('benefits-container');
    if (container && container.children.length > 1) {
        button.parentElement.remove();
    }
}

// URL 입력 체크박스 토글 기능
document.addEventListener('DOMContentLoaded', function() {
    const enableRedirectUrlCheckbox = document.getElementById('enable_redirect_url');
    const redirectUrlContainer = document.getElementById('redirect_url_container');
    const redirectUrlInput = document.getElementById('redirect_url');
    
    if (enableRedirectUrlCheckbox && redirectUrlContainer) {
        // 수정 모드일 때 기존 URL이 있으면 체크박스 체크
        <?php if ($isEditMode && isset($productDetail['redirect_url']) && !empty($productDetail['redirect_url'])): ?>
        enableRedirectUrlCheckbox.checked = true;
        redirectUrlContainer.style.display = 'block';
        <?php endif; ?>
        
        // 체크박스 변경 이벤트
        enableRedirectUrlCheckbox.addEventListener('change', function() {
            if (this.checked) {
                redirectUrlContainer.style.display = 'block';
                setTimeout(() => {
                    redirectUrlInput.focus();
                }, 100);
            } else {
                redirectUrlContainer.style.display = 'none';
                redirectUrlInput.value = ''; // 체크 해제 시 입력값 초기화
            }
        });
    }
});

</script>

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>

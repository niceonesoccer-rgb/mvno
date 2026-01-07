<?php
/**
 * 판매자 인터넷 상품 등록 페이지
 * 경로: /seller/products/internet.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/app-settings.php';

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
                WHERE id = :product_id AND seller_id = :seller_id AND product_type = 'internet' AND status != 'deleted'
            ");
            $stmt->execute([
                ':product_id' => $productId,
                ':seller_id' => $sellerId
            ]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                // Internet 상세 정보 조회
                $detailStmt = $pdo->prepare("
                    SELECT * FROM product_internet_details 
                    WHERE product_id = :product_id
                ");
                $detailStmt->execute([':product_id' => $productId]);
                $detailStmt->setFetchMode(PDO::FETCH_ASSOC);
                $productDetail = $detailStmt->fetch();
                
                if ($productDetail) {
                    $isEditMode = true;
                    $productData = array_merge($product, $productDetail);
                    
                    // service_type 기본값 설정 (기존 데이터에 없을 수 있음)
                    if (empty($productData['service_type'])) {
                        $productData['service_type'] = '인터넷';
                    }
                    
                    // JSON 필드 디코딩
                    $jsonFields = [
                        'cash_payment_names', 'cash_payment_prices',
                        'gift_card_names', 'gift_card_prices',
                        'equipment_names', 'equipment_prices',
                        'installation_names', 'installation_prices'
                    ];
                    
                    // 프로모션 필드 디코딩
                    if (!empty($productData['promotions'])) {
                        $productData['promotions'] = json_decode($productData['promotions'], true) ?: [];
                    } else {
                        $productData['promotions'] = [];
                    }
                    
                    // 필드명 정리 함수 (인코딩 오류 및 오타 수정)
                    $cleanFieldName = function($name) {
                        if (empty($name) || !is_string($name)) return '';
                        
                        // 공백 제거
                        $name = trim($name);
                        
                        // 일반적인 오타 및 인코딩 오류 수정
                        $corrections = [
                            // 와이파이공유기 관련 오타
                            '/와이파이공유기\s*[ㅇㄹㅁㄴㅂㅅ]+/u' => '와이파이공유기',
                            '/와이파이공유기\s*[ㅇㄹ]/u' => '와이파이공유기',
                            // 설치비 관련 오타
                            '/스?\s*설[ㅊㅈ]?이비/u' => '설치비',
                            '/설[ㅊㅈ]?이비/u' => '설치비',
                        ];
                        
                        // 패턴 기반 수정
                        foreach ($corrections as $pattern => $replacement) {
                            $name = preg_replace($pattern, $replacement, $name);
                        }
                        
                        // 특수문자나 이상한 문자 제거 (한글, 숫자, 영문, 공백만 허용)
                        $name = preg_replace('/[^\p{Hangul}\p{L}\p{N}\s]/u', '', $name);
                        
                        // 단어 끝에 의미없는 자음이 붙은 경우 제거
                        $name = preg_replace('/\s+[ㅇㄹㅁㄴㅂㅅㅇㄹ]+$/u', '', $name);
                        
                        // 앞뒤 공백 제거
                        $name = trim($name);
                        
                        return $name;
                    };
                    
                    // 중복 제거 및 정리 함수
                    $cleanArrayData = function($names, $prices) use ($cleanFieldName) {
                        if (empty($names) || !is_array($names)) return ['names' => [], 'prices' => []];
                        
                        $seen = [];
                        $cleaned = ['names' => [], 'prices' => []];
                        
                        foreach ($names as $index => $name) {
                            $cleanedName = $cleanFieldName($name);
                            
                            if (empty($cleanedName) || $cleanedName === '-') continue;
                            
                            // 중복 제거
                            $key = mb_strtolower($cleanedName, 'UTF-8');
                            if (isset($seen[$key])) continue;
                            $seen[$key] = true;
                            
                            $cleaned['names'][] = $cleanedName;
                            $cleaned['prices'][] = $prices[$index] ?? '';
                        }
                        
                        return $cleaned;
                    };
                    
                    foreach ($jsonFields as $field) {
                        if (!empty($productData[$field])) {
                            $decoded = json_decode($productData[$field], true);
                            $productData[$field] = is_array($decoded) ? $decoded : [];
                        } else {
                            $productData[$field] = [];
                        }
                    }
                    
                    // 이름 필드 정리 및 중복 제거
                    $nameFields = ['cash_payment_names', 'gift_card_names', 'equipment_names', 'installation_names'];
                    foreach ($nameFields as $nameField) {
                        $priceField = str_replace('_names', '_prices', $nameField);
                        if (isset($productData[$nameField]) && isset($productData[$priceField])) {
                            $cleaned = $cleanArrayData($productData[$nameField], $productData[$priceField]);
                            $productData[$nameField] = $cleaned['names'];
                            $productData[$priceField] = $cleaned['prices'];
                        }
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
        max-width: 1125px;
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
        background: #f9fafb;
        border-radius: 12px;
        padding: 24px;
        border: 1px solid #e5e7eb;
    }
    
    .form-section-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr;
        gap: 16px;
        margin-bottom: 32px;
        background: #f9fafb;
        border-radius: 12px;
        padding: 24px;
        border: 1px solid #e5e7eb;
    }
    
    .form-section-item {
        display: flex;
        flex-direction: column;
    }
    
    .form-section-item .form-section-title {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e5e7eb;
    }
    
    @media (max-width: 1200px) {
        .form-section-row {
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
    }
    
    @media (max-width: 768px) {
        .form-section-row {
            grid-template-columns: 1fr;
            gap: 24px;
        }
    }
    
    .form-section-title {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .form-section-title-icon {
        width: 24px;
        height: 24px;
        object-fit: contain;
        flex-shrink: 0;
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
    
    #messageContainer {
        margin-bottom: 24px;
        min-height: 0;
    }
    
    #messageContainer .alert {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    .gift-input-group {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
        align-items: stretch;
    }
    
    .gift-input-group .form-control {
        flex: 1;
    }
    
    .item-icon-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 12px;
        background: #f3f4f6;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        width: 40px;
        flex-shrink: 0;
    }
    
    .item-name-input {
        flex: 1;
        border: 1px solid #d1d5db;
        border-radius: 8px;
    }
    
    .item-name-input:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .item-price-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        flex: 1.5;
        border: 1px solid #d1d5db;
        border-radius: 8px;
    }
    
    .item-price-input-wrapper:focus-within {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .item-price-input-wrapper .form-control {
        border: none;
        padding-right: 35px;
        border-radius: 8px;
    }
    
    .item-price-input-wrapper .form-control:focus {
        outline: none;
        box-shadow: none;
    }
    
    .item-price-suffix {
        position: absolute;
        right: 12px;
        color: #6b7280;
        font-size: 13px;
        pointer-events: none;
        z-index: 1;
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
    
    .btn-add-item {
        padding: 12px 16px;
        background: #f3f4f6;
        color: #374151;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        white-space: nowrap;
    }
    
    .btn-add-item:hover {
        background: #e5e7eb;
    }
    
    .price-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .price-input-wrapper .form-control {
        text-align: right;
        flex: 1;
    }
    
    .price-input-wrapper .form-select {
        max-width: 80px;
    }
    
    .price-input-suffix {
        position: absolute;
        right: 12px;
        color: #6b7280;
        font-size: 14px;
        pointer-events: none;
        z-index: 1;
    }
    
    .price-input-wrapper .form-control[data-suffix] {
        padding-right: 50px;
    }
    
    .item-price-input-wrapper .form-select {
        border-left: none;
        border-radius: 0 8px 8px 0;
    }
    
    .item-price-input-wrapper .form-control {
        border-right: none;
        border-radius: 8px 0 0 8px;
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
        <h1><?php echo $isEditMode ? '인터넷 상품 수정' : '인터넷 상품 등록'; ?></h1>
        <p><?php echo $isEditMode ? '인터넷 상품 정보를 수정하세요' : '새로운 인터넷 상품을 등록하세요'; ?></p>
    </div>
    
    <!-- 동적 메시지 표시 영역 -->
    <div id="messageContainer" style="display: block; min-height: 0;"></div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            상품이 성공적으로 <?php echo $isEditMode ? '수정' : '등록'; ?>되었습니다.
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            상품 <?php echo $isEditMode ? '수정' : '등록'; ?> 중 오류가 발생했습니다. 다시 시도해주세요.
        </div>
    <?php endif; ?>
    
    <form id="productForm" class="product-form" method="POST" action="/MVNO/api/product-register-internet.php" novalidate>
        <?php if ($isEditMode): ?>
            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($productId); ?>">
        <?php endif; ?>
        
        <!-- 판매 상태 -->
        <div class="form-section">
            <div class="form-section-title">판매 상태</div>
            <div class="form-group" style="max-width: 30%;">
                <label class="form-label" for="product_status">상태 선택</label>
                <select name="status" id="product_status" class="form-select" style="width: auto; min-width: 120px;">
                    <option value="active" <?php echo ($isEditMode && isset($productData['status']) && $productData['status'] === 'active') ? 'selected' : (!$isEditMode ? 'selected' : ''); ?>>판매중</option>
                    <option value="inactive" <?php echo ($isEditMode && isset($productData['status']) && $productData['status'] === 'inactive') ? 'selected' : ''; ?>>판매종료</option>
                </select>
                <div class="form-help">상품의 판매 상태를 선택하세요</div>
            </div>
        </div>
        
        <!-- 인터넷가입처 / 결합여부 / 인터넷속도 / 사용요금 한 줄 -->
        <div class="form-section-row">
            <!-- 인터넷가입처 -->
            <div class="form-section-item">
                <div class="form-section-title">인터넷가입처</div>
                <div class="form-group">
                    <div class="custom-select-wrapper">
                        <select name="registration_place" id="registration_place" class="custom-select">
                            <option value="">선택하세요</option>
                            <option value="KT" <?php echo (isset($productData['registration_place']) && $productData['registration_place'] === 'KT') ? 'selected' : ''; ?>>KT</option>
                            <option value="SKT" <?php echo (isset($productData['registration_place']) && $productData['registration_place'] === 'SKT') ? 'selected' : ''; ?>>SKT</option>
                            <option value="LG U+" <?php echo (isset($productData['registration_place']) && $productData['registration_place'] === 'LG U+') ? 'selected' : ''; ?>>LG U+</option>
                            <option value="KT skylife" <?php echo (isset($productData['registration_place']) && $productData['registration_place'] === 'KT skylife') ? 'selected' : ''; ?>>KT skylife</option>
                            <option value="LG헬로비전" <?php echo (isset($productData['registration_place']) && $productData['registration_place'] === 'LG헬로비전') ? 'selected' : ''; ?>>LG헬로비전</option>
                            <option value="BTV" <?php echo (isset($productData['registration_place']) && $productData['registration_place'] === 'BTV') ? 'selected' : ''; ?>>BTV</option>
                            <option value="DLIVE" <?php echo (isset($productData['registration_place']) && $productData['registration_place'] === 'DLIVE') ? 'selected' : ''; ?>>DLIVE</option>
                            <option value="기타" <?php echo (isset($productData['registration_place']) && $productData['registration_place'] === '기타') ? 'selected' : ''; ?>>기타</option>
                        </select>
                        <div class="custom-select-trigger" id="custom-select-trigger">
                            <div class="selected-value">
                                <?php if (isset($productData['registration_place']) && !empty($productData['registration_place'])): ?>
                                    <?php
                                    $selectedPlace = $productData['registration_place'];
                                    $logoPath = '';
                                    switch($selectedPlace) {
                                        case 'KT': $logoPath = '/MVNO/assets/images/internets/kt.svg'; break;
                                        case 'SKT': $logoPath = '/MVNO/assets/images/internets/broadband.svg'; break;
                                        case 'LG U+': $logoPath = '/MVNO/assets/images/internets/lgu.svg'; break;
                                        case 'KT skylife': $logoPath = '/MVNO/assets/images/internets/ktskylife.svg'; break;
                                        case 'LG헬로비전': $logoPath = '/MVNO/assets/images/internets/hellovision.svg'; break;
                                        case 'BTV': $logoPath = '/MVNO/assets/images/internets/btv.svg'; break;
                                        case 'DLIVE': $logoPath = '/MVNO/assets/images/internets/dlive.svg'; break;
                                    }
                                    ?>
                                    <?php if ($logoPath): ?>
                                        <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="<?php echo htmlspecialchars($selectedPlace); ?>" style="max-width: 100px; max-height: 40px; object-fit: contain; <?php echo $selectedPlace === 'KT' ? 'height: 24px;' : ($selectedPlace === 'DLIVE' ? 'height: 35px; object-fit: cover;' : ''); ?>">
                                    <?php else: ?>
                                        <span><?php echo htmlspecialchars($selectedPlace); ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span>선택하세요</span>
                                <?php endif; ?>
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
            
            <!-- 결합여부 -->
            <div class="form-section-item">
                <div class="form-section-title">결합여부</div>
                <div class="form-group">
                    <?php
                    // DB에서 결합여부 옵션 불러오기
                    $defaultServiceTypes = [
                        ['value' => '인터넷', 'label' => '인터넷'],
                        ['value' => '인터넷+TV', 'label' => '인터넷 + TV 결합'],
                        ['value' => '인터넷+TV+핸드폰', 'label' => '인터넷 + TV + 핸드폰 결합']
                    ];
                    $serviceTypeSettings = getAppSettings('internet_service_types', ['options' => $defaultServiceTypes]);
                    $serviceTypeOptions = $serviceTypeSettings['options'] ?? $defaultServiceTypes;
                    ?>
                    <select name="service_type" id="service_type" class="form-select" required>
                        <option value="">선택하세요</option>
                        <?php foreach ($serviceTypeOptions as $option): ?>
                            <option value="<?php echo htmlspecialchars($option['value']); ?>" <?php echo (isset($productData['service_type']) && $productData['service_type'] === $option['value']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($option['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-help">결합여부를 선택하세요</div>
                </div>
            </div>
            
            <!-- 인터넷속도 -->
            <div class="form-section-item">
                <div class="form-section-title">인터넷속도</div>
                <div class="form-group">
                    <select name="speed_option" id="speed_option" class="form-select">
                        <option value="">선택하세요</option>
                        <option value="100M" <?php echo (isset($productData['speed_option']) && $productData['speed_option'] === '100M') ? 'selected' : ''; ?>>100MB</option>
                        <option value="500M" <?php echo (isset($productData['speed_option']) && $productData['speed_option'] === '500M') ? 'selected' : ''; ?>>500MB</option>
                        <option value="1G" <?php echo (isset($productData['speed_option']) && $productData['speed_option'] === '1G') ? 'selected' : ''; ?>>1GB</option>
                        <option value="2.5G" <?php echo (isset($productData['speed_option']) && $productData['speed_option'] === '2.5G') ? 'selected' : ''; ?>>2.5GB</option>
                        <option value="5G" <?php echo (isset($productData['speed_option']) && $productData['speed_option'] === '5G') ? 'selected' : ''; ?>>5GB</option>
                        <option value="10G" <?php echo (isset($productData['speed_option']) && $productData['speed_option'] === '10G') ? 'selected' : ''; ?>>10GB</option>
                    </select>
                    <div class="form-help">인터넷 속도를 선택하세요</div>
                </div>
            </div>
            
            <!-- 사용요금 -->
            <div class="form-section-item">
                <div class="form-section-title">사용요금</div>
                <div class="form-group">
                    <div class="price-input-wrapper" style="display: flex; gap: 8px; align-items: center;">
                        <input type="text" name="monthly_fee" id="monthly_fee" class="form-control" placeholder="0" maxlength="10" inputmode="numeric" pattern="[0-9]*" style="flex: 1;" value="<?php 
                            if (isset($productData['monthly_fee']) && !empty($productData['monthly_fee'])) {
                                // DB에 저장된 값이 "17000원" 형식이면 숫자만 추출
                                if (preg_match('/^(\d+)(.+)$/', $productData['monthly_fee'], $matches)) {
                                    echo number_format((int)$matches[1]);
                                } else {
                                    echo number_format((int)$productData['monthly_fee']);
                                }
                            }
                        ?>">
                        <select name="monthly_fee_unit" id="monthly_fee_unit" class="form-select" style="max-width: 80px;">
                            <option value="원" <?php 
                                if (isset($productData['monthly_fee']) && !empty($productData['monthly_fee'])) {
                                    if (preg_match('/^(\d+)(.+)$/', $productData['monthly_fee'], $matches)) {
                                        echo $matches[2] === '원' ? 'selected' : '';
                                    } else {
                                        echo 'selected';
                                    }
                                } else {
                                    echo 'selected';
                                }
                            ?>>원</option>
                        </select>
                    </div>
                    <div class="form-help">월 요금제 금액을 입력하세요 (최대 10자)</div>
                </div>
            </div>
        </div>
        
        <!-- 현금지급 -->
        <div class="form-section">
            <div class="form-section-title">
                <img src="/MVNO/assets/images/icons/cash.svg" alt="현금" class="form-section-title-icon">
                현금지급
            </div>
            
            <div class="form-group">
                <label class="form-label">항목</label>
                <div id="cash-payment-container">
                    <?php 
                    $cashNames = $productData['cash_payment_names'] ?? [];
                    $cashPrices = $productData['cash_payment_prices'] ?? [];
                    $cashCount = max(1, count($cashNames));
                    for ($i = 0; $i < $cashCount; $i++): 
                    ?>
                    <div class="gift-input-group">
                        <div class="item-icon-wrapper">
                            <img src="/MVNO/assets/images/icons/cash.svg" alt="현금" style="width: 20px; height: 20px; object-fit: contain;">
                        </div>
                        <input type="text" name="cash_payment_names[]" class="form-control item-name-input" placeholder="현금" maxlength="30" value="<?php echo htmlspecialchars($cashNames[$i] ?? ''); ?>">
                        <div class="item-price-input-wrapper" style="display: flex; gap: 8px; align-items: center;">
                            <input type="text" name="cash_payment_prices[]" class="form-control" placeholder="50,000" maxlength="10" inputmode="numeric" pattern="[0-9]*" style="flex: 1; border: none; padding-right: 8px;" value="<?php 
                                if (isset($cashPrices[$i]) && !empty($cashPrices[$i])) {
                                    // DB에 저장된 값이 "50000원" 형식이면 숫자만 추출하여 정수로 표시
                                    if (preg_match('/^(\d+)(.+)$/', $cashPrices[$i], $matches)) {
                                        echo number_format((int)$matches[1]);
                                    } else {
                                        echo number_format((int)$cashPrices[$i]);
                                    }
                                }
                            ?>">
                            <select name="cash_payment_price_units[]" class="form-select" style="max-width: 80px; border: none; padding: 12px 8px;">
                                <option value="원" <?php 
                                    if (isset($cashPrices[$i]) && !empty($cashPrices[$i])) {
                                        if (preg_match('/^(\d+)(.+)$/', $cashPrices[$i], $matches)) {
                                            echo $matches[2] === '원' ? 'selected' : '';
                                        } else {
                                            echo 'selected';
                                        }
                                    } else {
                                        echo 'selected';
                                    }
                                ?>>원</option>
                            </select>
                        </div>
                        <?php if ($i === 0): ?>
                            <button type="button" class="btn-add" onclick="addCashPaymentField()" style="margin-top: 0;">추가</button>
                        <?php else: ?>
                            <button type="button" class="btn-remove" onclick="removeField('cash', this)">삭제</button>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        
        <!-- 상품권 지급 -->
        <div class="form-section">
            <div class="form-section-title">
                <img src="/MVNO/assets/images/icons/gift-card.svg" alt="상품권" class="form-section-title-icon">
                상품권 지급
            </div>
            
            <div class="form-group">
                <label class="form-label">항목</label>
                <div id="gift-card-container">
                    <?php 
                    $giftNames = $productData['gift_card_names'] ?? [];
                    $giftPrices = $productData['gift_card_prices'] ?? [];
                    $giftCount = max(1, count($giftNames));
                    for ($i = 0; $i < $giftCount; $i++): 
                    ?>
                    <div class="gift-input-group">
                        <div class="item-icon-wrapper">
                            <img src="/MVNO/assets/images/icons/gift-card.svg" alt="상품권" style="width: 20px; height: 20px; object-fit: contain;">
                        </div>
                        <input type="text" name="gift_card_names[]" class="form-control item-name-input" placeholder="상품권" maxlength="30" value="<?php echo htmlspecialchars($giftNames[$i] ?? ''); ?>">
                        <div class="item-price-input-wrapper" style="display: flex; gap: 8px; align-items: center;">
                            <input type="text" name="gift_card_prices[]" class="form-control" placeholder="170,000" maxlength="10" inputmode="numeric" pattern="[0-9]*" style="flex: 1; border: none; padding-right: 8px;" value="<?php 
                                if (isset($giftPrices[$i]) && !empty($giftPrices[$i])) {
                                    // DB에 저장된 값이 "170000원" 형식이면 숫자만 추출하여 정수로 표시
                                    if (preg_match('/^(\d+)(.+)$/', $giftPrices[$i], $matches)) {
                                        echo number_format((int)$matches[1]);
                                    } else {
                                        echo number_format((int)$giftPrices[$i]);
                                    }
                                }
                            ?>">
                            <select name="gift_card_price_units[]" class="form-select" style="max-width: 80px; border: none; padding: 12px 8px;">
                                <option value="원" <?php 
                                    if (isset($giftPrices[$i]) && !empty($giftPrices[$i])) {
                                        if (preg_match('/^(\d+)(.+)$/', $giftPrices[$i], $matches)) {
                                            echo $matches[2] === '원' ? 'selected' : '';
                                        } else {
                                            echo 'selected';
                                        }
                                    } else {
                                        echo 'selected';
                                    }
                                ?>>원</option>
                            </select>
                        </div>
                        <?php if ($i === 0): ?>
                            <button type="button" class="btn-add" onclick="addGiftCardField()" style="margin-top: 0;">추가</button>
                        <?php else: ?>
                            <button type="button" class="btn-remove" onclick="removeField('giftCard', this)">삭제</button>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        
        <!-- 장비 및 기타 서비스 -->
        <div class="form-section">
            <div class="form-section-title">
                <img src="/MVNO/assets/images/icons/equipment.svg" alt="장비" class="form-section-title-icon">
                장비 및 기타 서비스
            </div>
            
            <div class="form-group">
                <label class="form-label">장비 제공</label>
                <div id="equipment-container">
                    <?php 
                    $equipNames = $productData['equipment_names'] ?? [];
                    $equipPrices = $productData['equipment_prices'] ?? [];
                    $equipCount = max(1, count($equipNames));
                    for ($i = 0; $i < $equipCount; $i++): 
                    ?>
                    <div class="gift-input-group">
                        <div class="item-icon-wrapper">
                            <img src="/MVNO/assets/images/icons/equipment.svg" alt="장비" style="width: 20px; height: 20px; object-fit: contain;">
                        </div>
                        <input type="text" name="equipment_names[]" class="form-control item-name-input" placeholder="와이파이 공유기" maxlength="30" value="<?php echo htmlspecialchars($equipNames[$i] ?? ''); ?>">
                        <input type="text" name="equipment_prices[]" class="form-control" placeholder="무료(월1,100원 상당)" value="<?php echo isset($equipPrices[$i]) && !empty($equipPrices[$i]) ? htmlspecialchars($equipPrices[$i]) : ''; ?>">
                        <?php if ($i === 0): ?>
                            <button type="button" class="btn-add" onclick="addEquipmentField()" style="margin-top: 0;">추가</button>
                        <?php else: ?>
                            <button type="button" class="btn-remove" onclick="removeField('equipment', this)">삭제</button>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">설치 및 기타 서비스</label>
                <div id="installation-container">
                    <?php 
                    $installNames = $productData['installation_names'] ?? [];
                    $installPrices = $productData['installation_prices'] ?? [];
                    $installCount = max(1, count($installNames));
                    for ($i = 0; $i < $installCount; $i++): 
                    ?>
                    <div class="gift-input-group">
                        <div class="item-icon-wrapper">
                            <img src="/MVNO/assets/images/icons/installation.svg" alt="설치" style="width: 20px; height: 20px; object-fit: contain;">
                        </div>
                        <input type="text" name="installation_names[]" class="form-control item-name-input" placeholder="인터넷,TV설치비" maxlength="30" value="<?php echo htmlspecialchars($installNames[$i] ?? ''); ?>">
                        <input type="text" name="installation_prices[]" class="form-control" placeholder="무료(36,000원 상당)" value="<?php echo isset($installPrices[$i]) && !empty($installPrices[$i]) ? htmlspecialchars($installPrices[$i]) : ''; ?>">
                        <?php if ($i === 0): ?>
                            <button type="button" class="btn-add" onclick="addInstallationField()" style="margin-top: 0;">추가</button>
                        <?php else: ?>
                            <button type="button" class="btn-remove" onclick="removeField('installation', this)">삭제</button>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
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
        
        <!-- 포인트 할인 혜택 설정 -->
        <div class="form-section">
            <div class="form-section-title">포인트 할인 혜택 설정</div>
            
            <div class="form-group">
                <label class="form-label" for="point_setting">
                    포인트 설정 (원)
                    <span style="font-size: 12px; color: #6b7280; font-weight: normal; margin-left: 4px;">고객이 사용할 수 있는 포인트 금액을 입력하세요 (1000원 단위)</span>
                </label>
                <input 
                    type="number" 
                    name="point_setting" 
                    id="point_setting" 
                    class="form-control" 
                    value="<?php echo isset($productData['point_setting']) ? htmlspecialchars($productData['point_setting']) : '0'; ?>"
                    min="0" 
                    step="1000"
                    placeholder="예: 3000"
                    style="max-width: 300px;"
                >
                <small class="form-text" style="display: block; margin-top: 8px; color: #6b7280; font-size: 13px;">
                    고객이 이 상품 신청 시 사용할 수 있는 포인트 금액입니다. 0으로 설정하면 포인트 사용이 불가능합니다. (1000원 단위로 입력)
                </small>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="point_benefit_description">
                    할인 혜택 내용
                    <span style="font-size: 12px; color: #6b7280; font-weight: normal; margin-left: 4px;">포인트 사용 시 제공되는 혜택을 입력하세요</span>
                </label>
                <textarea 
                    name="point_benefit_description" 
                    id="point_benefit_description" 
                    class="form-textarea" 
                    rows="3"
                    maxlength="500"
                    placeholder="예: 네이버페이 5000지급 익월말"
                    style="max-width: 100%;"
                ><?php echo isset($productData['point_benefit_description']) ? htmlspecialchars($productData['point_benefit_description']) : ''; ?></textarea>
                <small class="form-text" style="display: block; margin-top: 8px; color: #6b7280; font-size: 13px;">
                    포인트 사용 시 고객에게 제공되는 할인 혜택 내용을 입력하세요. 
                    예: "네이버페이 5000지급 익월말", "쿠폰 3000원 지급", "추가 할인 5000원" 등
                </small>
            </div>
            
            <div style="background: #eef2ff; padding: 12px; border-radius: 8px; margin-top: 12px;">
                <strong style="color: #4338ca;">💡 안내:</strong>
                <ul style="margin: 8px 0 0 20px; padding: 0; color: #4338ca; font-size: 13px;">
                    <li>포인트 설정이 0보다 크면 고객이 포인트를 사용할 수 있습니다.</li>
                    <li>할인 혜택 내용은 고객이 포인트 사용 모달에서 확인할 수 있습니다.</li>
                    <li>관리자 주문 관리 페이지에서도 할인 혜택 내용이 표시됩니다.</li>
                    <li>포인트 설정이 0이거나 할인 혜택이 없으면 포인트 모달을 건너뛰고 바로 신청 모달로 이동합니다.</li>
                </ul>
            </div>
        </div>
        
        <!-- 제출 버튼 -->
        <div class="form-actions">
            <a href="/MVNO/seller/products/internet-list.php" class="btn btn-secondary">취소</a>
            <button type="button" id="submitBtn" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 13l4 4L19 7"/>
                </svg>
                <?php echo $isEditMode ? '수정하기' : '등록하기'; ?>
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
                
                // hidden select 업데이트
                customSelect.value = value;
                
                // 트리거 업데이트
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
                customSelect.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
        
        // 외부 클릭 시 드롭다운 닫기
        document.addEventListener('click', function(e) {
            if (!customTrigger.contains(e.target) && !customOptions.contains(e.target)) {
                customTrigger.classList.remove('open');
                customOptions.classList.remove('open');
            }
        });
        
        // 수정 모드일 때 초기값 설정
        <?php if ($isEditMode && isset($productData['registration_place']) && !empty($productData['registration_place'])): ?>
        const initialValue = '<?php echo htmlspecialchars($productData['registration_place'], ENT_QUOTES); ?>';
        customSelect.value = initialValue;
        const selectedOption = customOptions.querySelector('[data-value="' + initialValue + '"]');
        if (selectedOption) {
            const selectedValueDiv = customTrigger.querySelector('.selected-value');
            selectedValueDiv.innerHTML = '';
            
            if (initialValue && registrationLogos[initialValue]) {
                const img = document.createElement('img');
                img.src = registrationLogos[initialValue];
                img.alt = initialValue;
                
                if (initialValue === 'DLIVE') {
                    img.style.height = '35px';
                    img.style.objectFit = 'cover';
                } else if (initialValue === 'KT') {
                    img.style.height = '24px';
                } else {
                    img.style.height = '40px';
                    img.style.objectFit = 'contain';
                }
                
                selectedValueDiv.appendChild(img);
            } else {
                const span = document.createElement('span');
                span.textContent = initialValue || '선택하세요';
                selectedValueDiv.appendChild(span);
            }
            
            options.forEach(opt => opt.classList.remove('selected'));
            selectedOption.classList.add('selected');
        }
        <?php endif; ?>
    }
});

// 필드 추가/삭제 함수
const fieldConfigs = {
    cash: {
        container: 'cash-payment-container',
        icon: '/MVNO/assets/images/icons/cash.svg',
        iconAlt: '현금',
        nameField: 'cash_payment_names[]',
        priceField: 'cash_payment_prices[]',
        namePlaceholder: '현금',
        pricePlaceholder: '50,000'
    },
    giftCard: {
        container: 'gift-card-container',
        icon: '/MVNO/assets/images/icons/gift-card.svg',
        iconAlt: '상품권',
        nameField: 'gift_card_names[]',
        priceField: 'gift_card_prices[]',
        namePlaceholder: '상품권',
        pricePlaceholder: '170,000원'
    },
    equipment: {
        container: 'equipment-container',
        icon: '/MVNO/assets/images/icons/equipment.svg',
        iconAlt: '장비',
        nameField: 'equipment_names[]',
        priceField: 'equipment_prices[]',
        namePlaceholder: '와이파이 공유기',
        pricePlaceholder: '무료(월1,100원 상당)'
    },
    installation: {
        container: 'installation-container',
        icon: '/MVNO/assets/images/icons/installation.svg',
        iconAlt: '설치',
        nameField: 'installation_names[]',
        priceField: 'installation_prices[]',
        namePlaceholder: '인터넷,TV설치비',
        pricePlaceholder: '무료(36,000원 상당)'
    }
};

function addField(type) {
    const config = fieldConfigs[type];
    if (!config) return;
    
    const container = document.getElementById(config.container);
    const newField = document.createElement('div');
    newField.className = 'gift-input-group';
    
    // 장비와 설치 필드는 "원" 단위 없이 일반 텍스트 필드
    const isTextOnlyField = (type === 'equipment' || type === 'installation');
    const priceInputHTML = isTextOnlyField 
        ? `<input type="text" name="${config.priceField}" class="form-control" placeholder="${config.pricePlaceholder}">`
        : `<div class="item-price-input-wrapper" style="display: flex; gap: 8px; align-items: center;">
            <input type="text" name="${config.priceField}" class="form-control" placeholder="${config.pricePlaceholder.replace('원', '')}" maxlength="10" style="flex: 1; border: none; padding-right: 8px;">
            <select name="${type === 'cash' ? 'cash_payment_price_units[]' : 'gift_card_price_units[]'}" class="form-select" style="max-width: 80px; border: none; padding: 12px 8px;">
                <option value="원" selected>원</option>
            </select>
        </div>`;
    
    newField.innerHTML = `
        <div class="item-icon-wrapper">
            <img src="${config.icon}" alt="${config.iconAlt}" style="width: 20px; height: 20px; object-fit: contain;">
        </div>
        <input type="text" name="${config.nameField}" class="form-control item-name-input" placeholder="${config.namePlaceholder}" maxlength="30">
        ${priceInputHTML}
        <button type="button" class="btn-remove" onclick="removeField('${type}', this)">삭제</button>
    `;
    container.appendChild(newField);
    
    // 현금지급과 상품권 필드는 숫자만 입력되도록 설정
    if (type === 'cash' || type === 'giftCard') {
        const priceInput = newField.querySelector(`input[name="${config.priceField}"]`);
        if (priceInput) {
            setupNumericInput(priceInput);
        }
    }
}

function removeField(type, button) {
    const config = fieldConfigs[type];
    if (!config) return;
    
    const container = document.getElementById(config.container);
    if (container.children.length > 1) {
        button.parentElement.remove();
    }
}

// 정수만 입력받는 필드 설정 함수 (소수점, 문자 등 모든 비정수 입력 차단)
function setupNumericInput(input) {
    if (!input) return;
    
    // 키보드 입력 차단: 소수점, 마이너스, e, E 등 입력 방지
    input.addEventListener('keydown', function(e) {
        // 허용된 키: 숫자(0-9), 백스페이스, Delete, Tab, Arrow keys, Home, End
        const allowedKeys = [
            'Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
            'Home', 'End'
        ];
        
        // Ctrl/Cmd + A, C, V, X 허용 (복사/붙여넣기)
        if ((e.ctrlKey || e.metaKey) && ['a', 'c', 'v', 'x'].includes(e.key.toLowerCase())) {
            return true;
        }
        
        // 숫자 키 허용
        if (e.key >= '0' && e.key <= '9') {
            return true;
        }
        
        // 허용된 키인지 확인
        if (allowedKeys.includes(e.key)) {
            return true;
        }
        
        // 그 외 모든 키 차단 (소수점, 마이너스, e, E 등)
        e.preventDefault();
        return false;
    });
    
    // 붙여넣기 시 정수만 추출
    input.addEventListener('paste', function(e) {
        e.preventDefault();
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        // 붙여넣은 텍스트에서 숫자만 추출
        const numericOnly = pastedText.replace(/[^0-9]/g, '');
        if (numericOnly) {
            const maxLength = parseInt(this.getAttribute('maxlength')) || 10;
            const value = numericOnly.substring(0, maxLength);
            this.value = value;
        }
    });
    
    // 입력 시 숫자만 허용 (추가 보안) 및 최대 길이 제한
    input.addEventListener('input', function() {
        // 소수점, 쉼표 등 모든 비숫자 제거
        let value = this.value.replace(/[^0-9]/g, '');
        // maxlength 속성이 있으면 그 값으로, 없으면 10자리로 제한
        const maxLength = parseInt(this.getAttribute('maxlength')) || 10;
        if (value.length > maxLength) {
            value = value.substring(0, maxLength);
        }
        this.value = value;
    });
    
    // 포커스 시 쉼표 및 소수점 제거
    input.addEventListener('focus', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
    
    // 블러 시 천단위 구분자 추가 (정수로 변환)
    input.addEventListener('blur', function() {
        if (this.value) {
            // 소수점 제거 후 정수로 변환
            const numValue = parseInt(this.value.replace(/[^0-9]/g, ''));
            if (!isNaN(numValue)) {
                this.value = numValue.toLocaleString('ko-KR');
            } else {
                this.value = '';
            }
        }
    });
}

// 월 요금제 필드 숫자만 입력되도록 설정
document.addEventListener('DOMContentLoaded', function() {
    const monthlyFeeInput = document.getElementById('monthly_fee');
    if (monthlyFeeInput) {
        setupNumericInput(monthlyFeeInput);
    }
    
    // 현금지급 가격 필드들 숫자만 입력
    document.querySelectorAll('input[name="cash_payment_prices[]"]').forEach(input => {
        setupNumericInput(input);
    });
    
    // 상품권 지급 가격 필드들 숫자만 입력
    document.querySelectorAll('input[name="gift_card_prices[]"]').forEach(input => {
        setupNumericInput(input);
    });
});

// 필드 추가 함수 (기존 호출 호환성 유지 - 전역 스코프에 정의)
window.addCashPaymentField = function() { addField('cash'); };
window.addGiftCardField = function() { addField('giftCard'); };
window.addEquipmentField = function() { addField('equipment'); };
window.addInstallationField = function() { addField('installation'); };

// 프로모션 필드 추가 함수
function addPromotionField() {
    const container = document.getElementById('promotion-container');
    const newField = document.createElement('div');
    newField.className = 'gift-input-group';
    newField.innerHTML = `
        <input type="text" name="promotions[]" class="form-control" placeholder="Npay 2,000" maxlength="30">
        <button type="button" class="btn-remove" onclick="removePromotionField(this)">삭제</button>
    `;
    container.appendChild(newField);
}

// 프로모션 필드 삭제 함수
function removePromotionField(button) {
    const container = document.getElementById('promotion-container');
    if (container.children.length > 1) {
        button.parentElement.remove();
    }
}

// 제출 버튼 클릭 시 즉시 포커스 제거 (mousedown 이벤트로 submit보다 먼저 처리)
// 주의: 이 이벤트는 클릭 이벤트를 방해하지 않도록 stopPropagation을 사용하지 않음
document.addEventListener('DOMContentLoaded', function() {
    const submitButton = document.getElementById('submitBtn');
    if (submitButton) {
        submitButton.addEventListener('mousedown', function(e) {
            console.log('제출 버튼 mousedown 이벤트');
            // 모든 입력 필드의 포커스 즉시 제거
            document.querySelectorAll('input, select, textarea').forEach(function(el) {
                if (document.activeElement === el) {
                    el.blur();
                }
            });
        }, { passive: true }); // passive 옵션으로 성능 개선
    }
});

// 엔터 키로 폼 제출 방지 및 제출 버튼 이벤트 설정
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded: 폼 이벤트 리스너 설정 시작');
    
    const productForm = document.getElementById('productForm');
    const submitBtn = document.getElementById('submitBtn');
    
    // 폼과 버튼 존재 확인
    if (!productForm) {
        console.error('오류: productForm을 찾을 수 없습니다!');
        return;
    }
    
    if (!submitBtn) {
        console.error('오류: submitBtn을 찾을 수 없습니다!');
        return;
    }
    
    console.log('폼과 버튼 요소 확인 완료');
    
    // 엔터 키로 폼 제출 방지
    const formInputs = productForm.querySelectorAll('input, textarea, select');
    formInputs.forEach(input => {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // 제출 버튼 클릭 이벤트
    submitBtn.addEventListener('click', function(e) {
        console.log('=== 제출 버튼 클릭 이벤트 발생 ===');
        console.log('버튼 상태:', {
            disabled: this.disabled,
            type: this.type,
            id: this.id
        });
        
        e.preventDefault();
        e.stopPropagation();
        
        // 버튼이 비활성화되어 있으면 중단
        if (this.disabled) {
            console.warn('버튼이 비활성화되어 있습니다.');
            return;
        }
        
        // 폼 검증 전에 숫자 필드의 쉼표 제거 (검증을 위해)
        const numericFields = productForm.querySelectorAll('input[inputmode="numeric"], input[pattern="[0-9]*"]');
        numericFields.forEach(field => {
            if (field.value && field.value.includes(',')) {
                const originalValue = field.value;
                field.value = field.value.replace(/,/g, '');
                console.log(`숫자 필드 정리: ${field.name || field.id} - "${originalValue}" → "${field.value}"`);
            }
        });
        
        // 폼 검증
        console.log('폼 검증 시작...');
        
        // 모든 required 필드 확인
        const requiredFields = productForm.querySelectorAll('[required]');
        console.log('Required 필드 개수:', requiredFields.length);
        
        // 모든 required 필드의 상태 출력
        const allFieldsStatus = [];
        requiredFields.forEach((field, index) => {
            const fieldStatus = {
                index: index,
                name: field.name || field.id,
                tagName: field.tagName,
                type: field.type,
                value: field.value,
                isValid: field.validity.valid,
                valueMissing: field.validity.valueMissing,
                validationMessage: field.validationMessage,
                willValidate: field.willValidate
            };
            allFieldsStatus.push(fieldStatus);
            console.log(`필드 ${index + 1}:`, fieldStatus);
        });
        
        const invalidFields = [];
        requiredFields.forEach(field => {
            if (!field.validity.valid) {
                invalidFields.push({
                    name: field.name || field.id,
                    value: field.value,
                    validationMessage: field.validationMessage,
                    validity: {
                        valueMissing: field.validity.valueMissing,
                        typeMismatch: field.validity.typeMismatch,
                        patternMismatch: field.validity.patternMismatch,
                        tooShort: field.validity.tooShort,
                        tooLong: field.validity.tooLong,
                        rangeUnderflow: field.validity.rangeUnderflow,
                        rangeOverflow: field.validity.rangeOverflow,
                        stepMismatch: field.validity.stepMismatch,
                        badInput: field.validity.badInput,
                        customError: field.validity.customError
                    }
                });
            }
        });
        
        console.log('전체 필드 상태:', allFieldsStatus);
        
        if (invalidFields.length > 0) {
            console.error('✗ 폼 검증 실패 - 유효하지 않은 필드:');
            invalidFields.forEach(field => {
                console.error(`  - ${field.name}:`, {
                    value: field.value,
                    message: field.validationMessage,
                    validity: field.validity
                });
            });
        } else {
            console.log('모든 required 필드가 유효합니다.');
        }
        
        // checkValidity 결과 확인
        const formIsValid = productForm.checkValidity();
        console.log('productForm.checkValidity() 결과:', formIsValid);
        
        if (formIsValid) {
            console.log('✓ 폼 검증 통과, 제출 이벤트 발생');
            // 직접 submit 이벤트 트리거
            const submitEvent = new Event('submit', { 
                cancelable: true, 
                bubbles: true 
            });
            const dispatched = productForm.dispatchEvent(submitEvent);
            console.log('제출 이벤트 dispatch 결과:', dispatched);
        } else {
            console.log('✗ 폼 검증 실패 - reportValidity 호출');
            // 어떤 필드가 문제인지 더 명확하게 표시
            const firstInvalidField = productForm.querySelector(':invalid');
            if (firstInvalidField) {
                console.error('첫 번째 유효하지 않은 필드:', {
                    name: firstInvalidField.name || firstInvalidField.id,
                    value: firstInvalidField.value,
                    validationMessage: firstInvalidField.validationMessage,
                    validity: firstInvalidField.validity
                });
                // 필드에 포커스 주기
                firstInvalidField.focus();
                firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            productForm.reportValidity();
        }
    });
    
    // 버튼이 실제로 존재하고 클릭 가능한지 확인
    console.log('제출 버튼 설정 완료:', {
        exists: !!submitBtn,
        disabled: submitBtn.disabled,
        type: submitBtn.type,
        className: submitBtn.className
    });
    
    // 폼 제출 이벤트
    productForm.addEventListener('submit', function(e) {
        console.log('=== 폼 제출 이벤트 발생 ===');
        e.preventDefault();
        e.stopPropagation();
        
        console.log('폼 제출 시작');
    
    const isEditMode = <?php echo $isEditMode ? 'true' : 'false'; ?>;
    
    // 즉시 모든 입력 필드의 포커스 제거 (제출 버튼 클릭 시 즉시 처리)
    const activeElement = document.activeElement;
    if (activeElement && activeElement.tagName !== 'BUTTON') {
        activeElement.blur();
    }
    
    // 모든 입력 필드의 포커스 제거
    document.querySelectorAll('input, select, textarea').forEach(function(el) {
        if (el === activeElement) {
            el.blur();
        }
        // 추가로 readonly를 임시로 설정하여 포커스 이동 방지
        if (el.tagName === 'INPUT' && el.type !== 'hidden') {
            el.setAttribute('readonly', 'readonly');
        }
    });
    
    console.log('포커스 제거 완료, 데이터 처리 시작');
    
    // 월 요금제 필드 처리: 쉼표 및 소수점 제거 후 정수로 변환 및 단위와 결합
    const monthlyFeeInput = document.getElementById('monthly_fee');
    const monthlyFeeUnit = document.getElementById('monthly_fee_unit');
    if (monthlyFeeInput) {
        // 포커스 제거 및 readonly 설정으로 포커스 이동 방지
        monthlyFeeInput.blur();
        monthlyFeeInput.setAttribute('readonly', 'readonly');
        
        if (monthlyFeeInput.value) {
            // 표시용 쉼표와 소수점 제거 후 순수 정수만 추출
            const cleanValue = monthlyFeeInput.value.toString().replace(/[,.]/g, '').replace(/[^0-9]/g, '');
            const value = parseInt(cleanValue) || 0;
            
            if (monthlyFeeUnit) {
                const unit = monthlyFeeUnit.value || '원';
                // 저장 시에는 쉼표 없이 숫자+단위만 저장
                monthlyFeeInput.value = value + unit;
            } else {
                monthlyFeeInput.value = value;
            }
        }
        
        // readonly 제거
        monthlyFeeInput.removeAttribute('readonly');
    }
    
    // 현금지급 가격 필드 처리: 쉼표 및 소수점 제거 후 정수로 변환, 단위와 결합하여 텍스트로 저장
    document.querySelectorAll('input[name="cash_payment_prices[]"]').forEach(function(input, index) {
        // 포커스 제거 및 readonly 설정으로 포커스 이동 방지
        input.blur();
        input.setAttribute('readonly', 'readonly');
        
        if (input.value) {
            // 쉼표와 소수점 제거 후 숫자만 추출
            const cleanValue = input.value.toString().replace(/[,.]/g, '').replace(/[^0-9]/g, '');
            // 정수로 변환 (소수점 완전 제거)
            const value = parseInt(cleanValue) || 0;
            const unitSelect = document.querySelectorAll('select[name="cash_payment_price_units[]"]')[index];
            const unit = unitSelect ? unitSelect.value : '원';
            // 텍스트 형식으로 저장 (예: "50000원")
            input.value = value + unit;
        }
        
        // readonly 제거
        input.removeAttribute('readonly');
    });
    
    // 상품권 지급 가격 필드 처리: 쉼표 및 소수점 제거 후 정수로 변환, 단위와 결합하여 텍스트로 저장
    document.querySelectorAll('input[name="gift_card_prices[]"]').forEach(function(input, index) {
        // 포커스 제거 및 readonly 설정으로 포커스 이동 방지
        input.blur();
        input.setAttribute('readonly', 'readonly');
        
        if (input.value) {
            // 쉼표와 소수점 제거 후 숫자만 추출
            const cleanValue = input.value.toString().replace(/[,.]/g, '').replace(/[^0-9]/g, '');
            // 정수로 변환 (소수점 완전 제거)
            const value = parseInt(cleanValue) || 0;
            const unitSelect = document.querySelectorAll('select[name="gift_card_price_units[]"]')[index];
            const unit = unitSelect ? unitSelect.value : '원';
            // 텍스트 형식으로 저장 (예: "170000원")
            input.value = value + unit;
        }
        
        // readonly 제거
        input.removeAttribute('readonly');
    });
    
    // 모든 입력 필드의 포커스 제거 및 readonly 제거 (값 변경 후)
    document.querySelectorAll('input, select, textarea').forEach(function(el) {
        el.blur();
        // readonly 제거
        if (el.tagName === 'INPUT' && el.type !== 'hidden') {
            el.removeAttribute('readonly');
        }
    });
    
    // 버튼에 포커스 주기 및 비활성화 (중복 제출 방지)
    const submitButton = document.getElementById('submitBtn');
    if (submitButton) {
        submitButton.focus();
        setTimeout(function() {
            submitButton.blur();
        }, 10);
        
        // 제출 버튼 비활성화
        submitButton.disabled = true;
        submitButton.style.opacity = '0.6';
        submitButton.style.cursor = 'not-allowed';
        console.log('제출 버튼 비활성화됨');
    }
    
    // 포커스 제거를 확실히 하기 위해 약간의 지연 후 제출
    setTimeout(function() {
        console.log('API 호출 시작');
        
        // FormData 생성 전에 빈 필드 제거
        const form = document.getElementById('productForm');
        
        // 장비 및 설치 필드에서 빈 값 제거
        // 이름과 가격이 모두 비어있는 행 제거
        const removeEmptyFields = function(nameSelector, priceSelector) {
            const nameInputs = Array.from(form.querySelectorAll(nameSelector));
            const priceInputs = Array.from(form.querySelectorAll(priceSelector));
            
            // 역순으로 순회하여 제거 (인덱스 변경 방지)
            for (let i = nameInputs.length - 1; i >= 0; i--) {
                const nameValue = (nameInputs[i].value || '').trim();
                const priceValue = (priceInputs[i] ? (priceInputs[i].value || '').trim() : '');
                
                // 이름과 가격이 모두 비어있으면 해당 행의 모든 필드 제거
                if (!nameValue && !priceValue) {
                    const group = nameInputs[i].closest('.gift-input-group');
                    if (group && group.parentElement.children.length > 1) {
                        // 첫 번째 행이 아니면 제거
                        group.remove();
                    } else if (group) {
                        // 첫 번째 행이면 값만 비우기
                        nameInputs[i].value = '';
                        if (priceInputs[i]) priceInputs[i].value = '';
                    }
                }
            }
        };
        
        // 빈 필드 제거 실행
        removeEmptyFields('input[name="equipment_names[]"]', 'input[name="equipment_prices[]"]');
        removeEmptyFields('input[name="installation_names[]"]', 'input[name="installation_prices[]"]');
        
        // FormData 생성
        const formData = new FormData(form);
        
        // 빈 값 제거: 장비 및 설치 필드에서 이름이 비어있는 항목의 가격도 제거
        const cleanFormData = new FormData();
        const equipmentNames = [];
        const equipmentPrices = [];
        const installationNames = [];
        const installationPrices = [];
        const promotions = [];
        
        // FormData를 순회하며 빈 값 필터링
        for (let [key, value] of formData.entries()) {
            if (key === 'equipment_names[]') {
                const trimmedValue = (value || '').trim();
                if (trimmedValue) {
                    equipmentNames.push(trimmedValue);
                }
            } else if (key === 'equipment_prices[]') {
                equipmentPrices.push((value || '').trim());
            } else if (key === 'installation_names[]') {
                const trimmedValue = (value || '').trim();
                if (trimmedValue) {
                    installationNames.push(trimmedValue);
                }
            } else if (key === 'installation_prices[]') {
                installationPrices.push((value || '').trim());
            } else if (key === 'promotions[]') {
                const trimmedValue = (value || '').trim();
                if (trimmedValue) {
                    promotions.push(trimmedValue);
                }
            } else {
                // 다른 필드는 그대로 추가
                cleanFormData.append(key, value);
            }
        }
        
        // 장비 필드: 이름과 가격을 쌍으로 매칭하여 이름이 있는 것만 추가
        equipmentNames.forEach((name, index) => {
            cleanFormData.append('equipment_names[]', name);
            cleanFormData.append('equipment_prices[]', equipmentPrices[index] || '');
        });
        
        // 설치 필드: 이름과 가격을 쌍으로 매칭하여 이름이 있는 것만 추가
        installationNames.forEach((name, index) => {
            cleanFormData.append('installation_names[]', name);
            cleanFormData.append('installation_prices[]', installationPrices[index] || '');
        });
        
        // 프로모션 필드: 빈 값이 아닌 것만 추가
        promotions.forEach((promotion) => {
            cleanFormData.append('promotions[]', promotion);
        });
        
        // FormData 내용 확인 (디버깅용)
        console.log('FormData 생성 완료 (빈 값 제거됨)');
        for (let pair of cleanFormData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        console.log('API 호출 시작: /MVNO/api/product-register-internet.php');
        console.log('전송할 FormData:', Array.from(cleanFormData.entries()));
        
        fetch('/MVNO/api/product-register-internet.php', {
            method: 'POST',
            body: cleanFormData
        })
        .then(response => {
            console.log('API 응답 받음, 상태:', response.status, response.statusText);
            // 응답 상태 확인
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            // JSON 파싱 시도
            return response.text().then(text => {
                console.log('응답 텍스트:', text.substring(0, 200));
                try {
                    const jsonData = JSON.parse(text);
                    console.log('파싱된 JSON:', jsonData);
                    return jsonData;
                } catch (e) {
                    console.error('JSON 파싱 오류:', e);
                    console.error('원본 텍스트:', text);
                    throw new Error('서버 응답을 파싱할 수 없습니다: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            console.log('API 응답:', data);
            
            if (data && data.success) {
                console.log('✓ 상품 저장 성공');
                if (isEditMode) {
                    // 수정 모드: 성공 메시지와 함께 리스트 페이지로 이동
                    if (typeof showAlert === 'function') {
                        showAlert('상품이 성공적으로 수정되었습니다.', '완료').then(() => {
                            window.location.href = '/MVNO/seller/products/internet-list.php?success=1';
                        });
                    } else {
                        showMessage('상품이 성공적으로 수정되었습니다.', 'success');
                        setTimeout(() => {
                            window.location.href = '/MVNO/seller/products/internet-list.php?success=1';
                        }, 1500);
                    }
                } else {
                    // 등록 모드: 리스트 페이지로 이동
                    showMessage('상품이 성공적으로 등록되었습니다.', 'success');
                    setTimeout(() => {
                        window.location.href = '/MVNO/seller/products/internet-list.php?success=1';
                    }, 1500);
                }
            } else {
                console.error('✗ API 오류 응답');
                // 오류 메시지를 상단에 표시
                let errorMsg = (data && data.message) || (isEditMode ? '상품 수정에 실패했습니다.' : '상품 등록에 실패했습니다.');
                
                // 개발 환경에서 상세 오류 정보 표시
                if (data && data.error_detail && window.location.hostname === 'localhost') {
                    errorMsg += '\n\n상세 오류: ' + data.error_detail;
                }
                
                console.error('API 오류:', errorMsg);
                if (data && data.error_detail) {
                    console.error('오류 상세:', data.error_detail);
                }
                showMessage(errorMsg, 'error');
                
                // 에러 발생 시 버튼 다시 활성화
                const errorSubmitButton = document.getElementById('submitBtn');
                if (errorSubmitButton) {
                    errorSubmitButton.disabled = false;
                    errorSubmitButton.style.opacity = '1';
                    errorSubmitButton.style.cursor = 'pointer';
                }
            }
        })
        .catch(error => {
            console.error('=== Fetch 오류 발생 ===');
            console.error('에러 객체:', error);
            console.error('에러 메시지:', error.message);
            console.error('에러 스택:', error.stack);
            
            const errorMsg = isEditMode 
                ? '상품 수정 중 오류가 발생했습니다: ' + error.message 
                : '상품 등록 중 오류가 발생했습니다: ' + error.message;
            
            showMessage(errorMsg, 'error');
            
            // 버튼 다시 활성화 (에러 발생 시)
            const catchSubmitButton = document.getElementById('submitBtn');
            if (catchSubmitButton) {
                catchSubmitButton.disabled = false;
                catchSubmitButton.style.opacity = '1';
                catchSubmitButton.style.cursor = 'pointer';
            }
        });
    }, 50); // 50ms 지연으로 포커스 제거 확실히 처리
    });
    
    console.log('폼 이벤트 리스너 설정 완료');
}); // DOMContentLoaded 종료

// 상단에 메시지를 표시하는 함수
function showMessage(message, type) {
    console.log('showMessage 호출:', message, type);
    
    const messageContainer = document.getElementById('messageContainer');
    if (!messageContainer) {
        console.error('messageContainer를 찾을 수 없습니다!');
        // 메시지 컨테이너가 없으면 alert로 대체
        alert(message);
        return;
    }
    
    // 기존 메시지 제거
    messageContainer.innerHTML = '';
    
    // 새 메시지 생성
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-' + (type === 'success' ? 'success' : 'error');
    alertDiv.textContent = message;
    alertDiv.style.display = 'block'; // 확실히 표시되도록
    
    // 메시지 컨테이너에 추가
    messageContainer.appendChild(alertDiv);
    
    // 메시지 컨테이너가 보이도록 스타일 설정
    messageContainer.style.display = 'block';
    
    // 페이지 상단으로 스크롤
    setTimeout(function() {
        messageContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
    
    // 성공 메시지는 3초 후 자동 제거, 오류 메시지는 수동 제거
    if (type === 'success') {
        setTimeout(function() {
            alertDiv.style.transition = 'opacity 0.3s';
            alertDiv.style.opacity = '0';
            setTimeout(function() {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 300);
        }, 3000);
    }
}
</script>

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>

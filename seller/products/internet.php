<?php
/**
 * 판매자 인터넷 상품 등록 페이지
 * 경로: /seller/products/internet.php
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
                    
                    // JSON 필드 디코딩
                    $jsonFields = [
                        'cash_payment_names', 'cash_payment_prices',
                        'gift_card_names', 'gift_card_prices',
                        'equipment_names', 'equipment_prices',
                        'installation_names', 'installation_prices'
                    ];
                    
                    foreach ($jsonFields as $field) {
                        if (!empty($productData[$field])) {
                            $decoded = json_decode($productData[$field], true);
                            $productData[$field] = is_array($decoded) ? $decoded : [];
                        } else {
                            $productData[$field] = [];
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
        background: #f9fafb;
        border-radius: 12px;
        padding: 24px;
        border: 1px solid #e5e7eb;
    }
    
    .form-section-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
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
    
    .price-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    
    .price-input-wrapper .form-control {
        text-align: right;
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
    
    <form id="productForm" class="product-form" method="POST" action="/MVNO/api/product-register-internet.php">
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
        
        <!-- 인터넷가입처 / 인터넷속도 / 사용요금 한 줄 -->
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
            
            <!-- 인터넷속도 -->
            <div class="form-section-item">
                <div class="form-section-title">인터넷속도</div>
                <div class="form-group">
                    <select name="speed_option" id="speed_option" class="form-select">
                        <option value="">선택하세요</option>
                        <option value="100M" <?php echo (isset($productData['speed_option']) && $productData['speed_option'] === '100M') ? 'selected' : ''; ?>>100M</option>
                        <option value="500M" <?php echo (isset($productData['speed_option']) && $productData['speed_option'] === '500M') ? 'selected' : ''; ?>>500M</option>
                        <option value="1G" <?php echo (isset($productData['speed_option']) && $productData['speed_option'] === '1G') ? 'selected' : ''; ?>>1G</option>
                        <option value="2.5G" <?php echo (isset($productData['speed_option']) && $productData['speed_option'] === '2.5G') ? 'selected' : ''; ?>>2.5G</option>
                        <option value="5G" <?php echo (isset($productData['speed_option']) && $productData['speed_option'] === '5G') ? 'selected' : ''; ?>>5G</option>
                        <option value="10G" <?php echo (isset($productData['speed_option']) && $productData['speed_option'] === '10G') ? 'selected' : ''; ?>>10G</option>
                    </select>
                    <div class="form-help">인터넷 속도를 선택하세요</div>
                </div>
            </div>
            
            <!-- 사용요금 -->
            <div class="form-section-item">
                <div class="form-section-title">사용요금</div>
                <div class="form-group">
                    <div class="price-input-wrapper">
                        <input type="text" name="monthly_fee" id="monthly_fee" class="form-control" placeholder="0" maxlength="10" data-suffix="원" value="<?php echo isset($productData['monthly_fee']) ? number_format($productData['monthly_fee']) : ''; ?>">
                        <span class="price-input-suffix">원</span>
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
                        <div class="item-price-input-wrapper">
                            <input type="text" name="cash_payment_prices[]" class="form-control" placeholder="50,000원" value="<?php echo isset($cashPrices[$i]) && !empty($cashPrices[$i]) ? number_format($cashPrices[$i]) : ''; ?>">
                            <span class="item-price-suffix">원</span>
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
                        <div class="item-price-input-wrapper">
                            <input type="text" name="gift_card_prices[]" class="form-control" placeholder="170,000원" value="<?php echo isset($giftPrices[$i]) && !empty($giftPrices[$i]) ? number_format($giftPrices[$i]) : ''; ?>">
                            <span class="item-price-suffix">원</span>
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
        
        <!-- 제출 버튼 -->
        <div class="form-actions">
            <a href="/MVNO/seller/products/internet-list.php" class="btn btn-secondary">취소</a>
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
        pricePlaceholder: '50,000원'
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
        : `<div class="item-price-input-wrapper">
            <input type="text" name="${config.priceField}" class="form-control" placeholder="${config.pricePlaceholder}">
            <span class="item-price-suffix">원</span>
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

// 숫자만 입력받는 필드 설정 함수
function setupNumericInput(input) {
    if (!input) return;
    
    // 입력 시 숫자만 허용
    input.addEventListener('input', function() {
        let value = this.value.replace(/[^0-9]/g, '');
        this.value = value;
    });
    
    // 포커스 시 쉼표 제거
    input.addEventListener('focus', function() {
        this.value = this.value.replace(/,/g, '');
    });
    
    // 블러 시 천단위 구분자 추가
    input.addEventListener('blur', function() {
        if (this.value) {
            const numValue = parseInt(this.value.replace(/,/g, ''));
            if (!isNaN(numValue)) {
                this.value = numValue.toLocaleString('ko-KR');
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

// 필드 추가 함수 (기존 호출 호환성 유지)
function addCashPaymentField() { addField('cash'); }
function addGiftCardField() { addField('giftCard'); }
function addEquipmentField() { addField('equipment'); }
function addInstallationField() { addField('installation'); }

document.getElementById('productForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const isEditMode = <?php echo $isEditMode ? 'true' : 'false'; ?>;
    
    // 월 요금제 필드에서 쉼표 제거
    const monthlyFeeInput = document.getElementById('monthly_fee');
    if (monthlyFeeInput && monthlyFeeInput.value) {
        monthlyFeeInput.value = monthlyFeeInput.value.replace(/,/g, '');
    }
    
    // 가격 필드들에서 쉼표 제거 (현금지급, 상품권만 - 장비와 설치 필드는 텍스트 필드이므로 제외)
    document.querySelectorAll('input[name="cash_payment_prices[]"], input[name="gift_card_prices[]"]').forEach(input => {
        if (input.value) {
            input.value = input.value.replace(/,/g, '');
        }
    });
    
    fetch('/MVNO/api/product-register-internet.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (isEditMode) {
                // 수정 모드: 성공 메시지와 함께 리스트 페이지로 이동
                if (typeof showAlert === 'function') {
                    showAlert('상품이 성공적으로 수정되었습니다.', '완료').then(() => {
                        window.location.href = '/MVNO/seller/products/internet-list.php?success=1';
                    });
                } else {
                    alert('상품이 성공적으로 수정되었습니다.');
                    window.location.href = '/MVNO/seller/products/internet-list.php?success=1';
                }
            } else {
                // 등록 모드: 리스트 페이지로 이동
                window.location.href = '/MVNO/seller/products/internet-list.php?success=1';
            }
        } else {
            alert(data.message || (isEditMode ? '상품 수정에 실패했습니다.' : '상품 등록에 실패했습니다.'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(isEditMode ? '상품 수정 중 오류가 발생했습니다.' : '상품 등록 중 오류가 발생했습니다.');
    });
});
</script>

<?php include __DIR__ . '/../includes/seller-footer.php'; ?>

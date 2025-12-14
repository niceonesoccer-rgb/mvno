<?php
/**
 * 판매자 통신사폰 상품 등록 페이지
 * 경로: /seller/products/mno.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 데이터베이스 연결
$pdo = getDBConnection();

// 제조사 목록 가져오기
$manufacturers = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM device_manufacturers WHERE status = 'active' ORDER BY display_order ASC, name ASC");
        $manufacturers = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching manufacturers: " . $e->getMessage());
    }
}

$currentUser = getCurrentUser();

// 수정 모드 확인
$editMode = false;
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$productData = null;
$deviceData = null;

if ($productId > 0 && $pdo) {
    try {
        // 상품 기본 정보 가져오기
        $stmt = $pdo->prepare("
            SELECT p.*, mno.* 
            FROM products p
            LEFT JOIN product_mno_details mno ON p.id = mno.product_id
            WHERE p.id = ? AND p.seller_id = ? AND p.product_type = 'mno'
        ");
        $stmt->execute([$productId, $currentUser['user_id']]);
        $productData = $stmt->fetch();
        
        if ($productData) {
            $editMode = true;
            $deviceData = $productData;
            
            // device_id가 있으면 devices 테이블에서 제조사 정보 가져오기
            if (!empty($deviceData['device_id'])) {
                try {
                    $deviceStmt = $pdo->prepare("
                        SELECT d.*, m.id as manufacturer_id, m.name as manufacturer_name
                        FROM devices d
                        LEFT JOIN device_manufacturers m ON d.manufacturer_id = m.id
                        WHERE d.id = ? AND d.status = 'active'
                        LIMIT 1
                    ");
                    $deviceStmt->execute([$deviceData['device_id']]);
                    $deviceInfo = $deviceStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($deviceInfo) {
                        if (!empty($deviceInfo['manufacturer_id'])) {
                            $deviceData['manufacturer_id'] = $deviceInfo['manufacturer_id'];
                            $deviceData['device_id'] = $deviceInfo['id'];
                        } else {
                            error_log("Device ID " . $deviceData['device_id'] . " found but manufacturer_id is empty");
                        }
                    } else {
                        error_log("Device ID " . $deviceData['device_id'] . " not found in devices table");
                    }
                } catch (PDOException $e) {
                    error_log("Error fetching device info by device_id: " . $e->getMessage());
                }
            }
            
            // manufacturer_id가 여전히 없으면 device_name과 storage로 찾기
            if (empty($deviceData['manufacturer_id']) && !empty($deviceData['device_name'])) {
                try {
                    $deviceStmt = $pdo->prepare("
                        SELECT d.*, m.id as manufacturer_id, m.name as manufacturer_name
                        FROM devices d
                        LEFT JOIN device_manufacturers m ON d.manufacturer_id = m.id
                        WHERE d.name = ? AND d.status = 'active'
                        " . (!empty($deviceData['device_capacity']) ? "AND d.storage = ?" : "") . "
                        LIMIT 1
                    ");
                    $params = [$deviceData['device_name']];
                    if (!empty($deviceData['device_capacity'])) {
                        $params[] = $deviceData['device_capacity'];
                    }
                    $deviceStmt->execute($params);
                    $deviceInfo = $deviceStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($deviceInfo && !empty($deviceInfo['manufacturer_id'])) {
                        $deviceData['manufacturer_id'] = $deviceInfo['manufacturer_id'];
                        if (empty($deviceData['device_id'])) {
                            $deviceData['device_id'] = $deviceInfo['id'];
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Error fetching device info by device_name: " . $e->getMessage());
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Error fetching product data: " . $e->getMessage());
    }
}

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
    
    .form-select optgroup {
        font-weight: 600;
        color: #1f2937;
        background-color: #f9fafb;
        padding: 8px 12px;
    }
    
    .form-select optgroup option {
        padding-left: 24px;
        font-weight: normal;
        color: #374151;
    }
    
    /* 용량 부분 강조를 위한 스타일 (브라우저 제한으로 완벽하지 않을 수 있음) */
    .form-select option {
        position: relative;
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
    
    .btn-danger {
        background: #ef4444;
        color: white;
    }
    
    .btn-danger:hover {
        background: #dc2626;
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
    
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    
    .modal-overlay.active {
        display: flex;
    }
    
    .modal-content-box {
        background: white;
        border-radius: 12px;
        padding: 32px;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        position: relative;
        animation: modalSlideIn 0.3s ease-out;
    }
    
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .modal-header-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }
    
    .modal-title-box {
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
    }
    
    .modal-close-btn {
        background: none;
        border: none;
        font-size: 28px;
        color: #6b7280;
        cursor: pointer;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        transition: all 0.2s;
    }
    
    .modal-close-btn:hover {
        background: #f3f4f6;
        color: #1f2937;
    }
    
    .modal-body-box {
        margin-bottom: 24px;
    }
    
    .modal-message {
        font-size: 16px;
        color: #374151;
        line-height: 1.6;
    }
    
    .modal-message.success {
        color: #059669;
    }
    
    .modal-message.error {
        color: #dc2626;
    }
    
    .modal-footer-box {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }
    
    .modal-btn {
        padding: 12px 24px;
        font-size: 15px;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .modal-btn-primary {
        background: #10b981;
        color: white;
    }
    
    .modal-btn-primary:hover {
        background: #059669;
    }
    
    .modal-btn-secondary {
        background: #f3f4f6;
        color: #374151;
    }
    
    .modal-btn-secondary:hover {
        background: #e5e7eb;
    }
    
    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 0.8s linear infinite;
        margin-right: 8px;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
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
        <h1><?php echo $editMode ? '통신사폰 상품 수정' : '통신사폰 상품 등록'; ?></h1>
        <p><?php echo $editMode ? '통신사폰 요금제 정보를 수정하세요' : '새로운 통신사폰 요금제를 등록하세요'; ?></p>
    </div>
    
    <?php if ($editMode && !$productData): ?>
        <div class="alert alert-error">
            상품을 찾을 수 없거나 수정 권한이 없습니다.
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            상품이 성공적으로 <?php echo $editMode ? '수정' : '등록'; ?>되었습니다.
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            상품 <?php echo $editMode ? '수정' : '등록'; ?> 중 오류가 발생했습니다. 다시 시도해주세요.
        </div>
    <?php endif; ?>
    
    <form id="productForm" class="product-form" method="POST" action="/MVNO/api/product-register-mno.php">
        <?php if ($editMode): ?>
            <input type="hidden" name="product_id" id="product_id" value="<?php echo $productId; ?>">
        <?php endif; ?>
        
        <!-- 판매 상태 -->
        <div class="form-section">
            <div class="form-section-title">판매 상태</div>
            <div class="form-group" style="max-width: 30%;">
                <label class="form-label" for="product_status">상태</label>
                <select name="product_status" id="product_status" class="form-select" style="width: auto; min-width: 120px;">
                    <option value="active" <?php echo ($editMode && isset($productData['status']) && $productData['status'] === 'active') ? 'selected' : (!$editMode ? 'selected' : ''); ?>>판매중</option>
                    <option value="inactive" <?php echo ($editMode && isset($productData['status']) && $productData['status'] === 'inactive') ? 'selected' : ''; ?>>판매종료</option>
                </select>
            </div>
        </div>
        
        <!-- 기본 정보 -->
        <div class="form-section">
            <div class="form-section-title">단말기</div>
            
            <div class="form-group" style="display: flex; gap: 16px; align-items: flex-start;">
                <div style="flex: 1;">
                    <label class="form-label" for="manufacturer_id">
                        제조사
                    </label>
                    <select name="manufacturer_id" id="manufacturer_id" class="form-select">
                        <option value="">제조사를 선택하세요</option>
                        <?php foreach ($manufacturers as $manufacturer): ?>
                            <option value="<?php echo htmlspecialchars($manufacturer['id']); ?>" 
                                <?php echo ($editMode && isset($deviceData['manufacturer_id']) && $deviceData['manufacturer_id'] == $manufacturer['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($manufacturer['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="flex: 1;">
                    <label class="form-label" for="device_id">
                        단말기명
                    </label>
                    <select name="device_id" id="device_id" class="form-select" <?php echo ($editMode && isset($deviceData['manufacturer_id'])) ? '' : 'disabled'; ?>>
                        <option value=""><?php echo ($editMode && isset($deviceData['manufacturer_id'])) ? '단말기를 선택하세요' : '제조사를 먼저 선택하세요'; ?></option>
                    </select>
                    <input type="hidden" name="device_name" id="device_name">
                    <input type="hidden" name="device_price" id="device_price">
                    <input type="hidden" name="device_capacity" id="device_capacity">
                </div>
            </div>
            
            <!-- 단말기 색상 -->
            <div class="form-group">
                <label class="form-label" for="device_colors">
                    단말기 색상
                </label>
                <div id="device-colors-container" style="display: flex; flex-wrap: wrap; gap: 12px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; background: #f9fafb; min-height: 50px;">
                    <div style="width: 100%; color: #6b7280; font-size: 14px; margin-bottom: 8px;">단말기를 선택하면 색상이 표시됩니다.</div>
                </div>
                <small class="form-text text-muted">단말기를 선택하면 해당 단말기의 색상 목록이 표시됩니다. 판매할 색상을 선택하세요.</small>
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
                        value="<?php echo ($editMode && isset($deviceData['redirect_url'])) ? htmlspecialchars($deviceData['redirect_url']) : ''; ?>"
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
            <?php if ($editMode): ?>
                <button type="button" class="btn btn-danger" onclick="deleteProduct()">삭제</button>
            <?php endif; ?>
            <a href="<?php echo $editMode ? '/MVNO/seller/products/mno-list.php' : '/MVNO/seller/products/list.php'; ?>" class="btn btn-secondary">취소</a>
            <button type="submit" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 13l4 4L19 7"/>
                </svg>
                <?php echo $editMode ? '수정하기' : '등록하기'; ?>
            </button>
        </div>
    </form>
</div>

<!-- 등록 결과 모달 -->
<div id="registerModal" class="modal-overlay">
    <div class="modal-content-box">
        <div class="modal-header-box">
            <h2 class="modal-title-box" id="modalTitle">알림</h2>
            <button type="button" class="modal-close-btn" onclick="closeRegisterModal()">&times;</button>
        </div>
        <div class="modal-body-box">
            <p class="modal-message" id="modalMessage"></p>
        </div>
        <div class="modal-footer-box">
            <button type="button" class="modal-btn modal-btn-primary" id="modalConfirmBtn" onclick="closeRegisterModal()">확인</button>
        </div>
    </div>
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
    
    // 제조사 선택 시 단말기 목록 가져오기
    const manufacturerSelect = document.getElementById('manufacturer_id');
    const deviceSelect = document.getElementById('device_id');
    const deviceNameInput = document.getElementById('device_name');
    const devicePriceInput = document.getElementById('device_price');
    const deviceCapacityInput = document.getElementById('device_capacity');
    
    // 단말기 목록 로드 함수 (공통) - 전역으로 사용 가능하도록
    window.loadDeviceList = function(manufacturerId, selectDeviceId) {
        if (!manufacturerId || !deviceSelect) return;
        
        fetch(`/MVNO/api/get-devices-by-manufacturer.php?manufacturer_id=${manufacturerId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.grouped) {
                    deviceSelect.innerHTML = '<option value="">단말기를 선택하세요</option>';
                    
                    // 단말기명별로 그룹화하여 표시
                    Object.keys(data.grouped).forEach(deviceName => {
                        const devices = data.grouped[deviceName];
                        
                        if (devices.length === 1) {
                            const device = devices[0];
                            const displayText = `${device.name} | ${device.storage || ''} | ${device.release_price ? (parseInt(device.release_price).toLocaleString('ko-KR') + '원') : ''}`.trim();
                            const option = document.createElement('option');
                            option.value = device.id;
                            option.textContent = displayText;
                            option.setAttribute('data-name', device.name || '');
                            option.setAttribute('data-price', device.release_price || '');
                            option.setAttribute('data-capacity', device.storage || '');
                            deviceSelect.appendChild(option);
                        } else {
                            // 여러 용량이 있으면 optgroup 사용
                            const optgroup = document.createElement('optgroup');
                            optgroup.label = deviceName;
                            
                            devices.forEach(device => {
                                const capacityText = device.storage || '';
                                const priceText = device.release_price ? (parseInt(device.release_price).toLocaleString('ko-KR') + '원') : '';
                                const option = document.createElement('option');
                                option.value = device.id;
                                // 선택 후에도 단말기명이 보이도록 단말기명 포함
                                option.textContent = `${deviceName} [${capacityText}] ${priceText}`.trim();
                                option.setAttribute('data-name', device.name || '');
                                option.setAttribute('data-price', device.release_price || '');
                                option.setAttribute('data-capacity', device.storage || '');
                                optgroup.appendChild(option);
                            });
                            
                            deviceSelect.appendChild(optgroup);
                        }
                    });
                    
                    deviceSelect.disabled = false;
                    
                    // 특정 단말기를 선택해야 하는 경우
                    if (selectDeviceId) {
                        const deviceId = selectDeviceId.toString();
                        deviceSelect.value = deviceId;
                        
                        // 선택된 옵션에서 데이터 가져오기
                        const selectedOption = deviceSelect.querySelector(`option[value="${deviceId}"]`);
                        if (selectedOption) {
                            const deviceName = selectedOption.getAttribute('data-name') || '';
                            const devicePrice = selectedOption.getAttribute('data-price') || '';
                            const deviceCapacity = selectedOption.getAttribute('data-capacity') || '';
                            
                            if (deviceNameInput) deviceNameInput.value = deviceName;
                            if (devicePriceInput) devicePriceInput.value = devicePrice.toString().replace(/,/g, '');
                            if (deviceCapacityInput) deviceCapacityInput.value = deviceCapacity;
                            
                        }
                        
                        // change 이벤트 트리거
                        deviceSelect.dispatchEvent(new Event('change'));
                    }
                } else {
                    deviceSelect.innerHTML = '<option value="">등록된 단말기가 없습니다</option>';
                    deviceSelect.disabled = true;
                }
            })
            .catch(error => {
                deviceSelect.innerHTML = '<option value="">단말기 목록을 불러올 수 없습니다</option>';
                deviceSelect.disabled = true;
            });
    };
    
    if (manufacturerSelect && deviceSelect) {
        manufacturerSelect.addEventListener('change', function() {
            const manufacturerId = this.value;
            
            if (!manufacturerId) {
                deviceSelect.innerHTML = '<option value="">제조사를 먼저 선택하세요</option>';
                deviceSelect.disabled = true;
                if (deviceNameInput) deviceNameInput.value = '';
                if (devicePriceInput) devicePriceInput.value = '';
                if (deviceCapacityInput) deviceCapacityInput.value = '';
                return;
            }
            
            // 공통 함수 사용
            window.loadDeviceList(manufacturerId, null);
        });
        
        // 단말기 선택 시 값 설정 (분리해서 저장)
        deviceSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.value) {
                const deviceId = selectedOption.value;
                
                // 단말기명만 추출 (용량, 가격 제외)
                const deviceName = selectedOption.getAttribute('data-name') || '';
                // 가격은 숫자만 저장 (콤마 제거)
                const price = selectedOption.getAttribute('data-price') || '';
                const devicePrice = price ? price.toString().replace(/,/g, '') : '';
                // 용량만 추출
                const deviceCapacity = selectedOption.getAttribute('data-capacity') || '';
                
                // 각각 분리해서 저장
                deviceNameInput.value = deviceName;        // 예: "iPhone 16 Pro"
                devicePriceInput.value = devicePrice;       // 예: "1155000"
                deviceCapacityInput.value = deviceCapacity; // 예: "256GB"
                
                // 단말기 색상 정보 가져오기
                loadDeviceColors(deviceId);
            } else {
                deviceNameInput.value = '';
                devicePriceInput.value = '';
                deviceCapacityInput.value = '';
                
                // 색상 컨테이너 초기화
                const colorContainer = document.getElementById('device-colors-container');
                if (colorContainer) {
                    colorContainer.innerHTML = '<div style="width: 100%; color: #6b7280; font-size: 14px;">단말기를 선택하면 색상이 표시됩니다.</div>';
                }
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
    
    // 수정 모드: 기존 데이터 로드
    <?php if ($editMode && $productData): ?>
    (function() {
        const editData = <?php echo json_encode($productData, JSON_UNESCAPED_UNICODE); ?>;
        
        // 수정 모드: 제조사 선택 및 전체 단말기 목록 즉시 로드
        // 요소를 직접 가져오기
        const editManufacturerSelect = document.getElementById('manufacturer_id');
        const editDeviceSelect = document.getElementById('device_id');
        const editDeviceNameInput = document.getElementById('device_name');
        const editDevicePriceInput = document.getElementById('device_price');
        const editDeviceCapacityInput = document.getElementById('device_capacity');
        
        // 단말기 목록 로드 함수
        function loadDeviceListForEdit(manufacturerId, selectDeviceId) {
            if (!manufacturerId || !editDeviceSelect) return;
            
            fetch(`/MVNO/api/get-devices-by-manufacturer.php?manufacturer_id=${manufacturerId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.grouped) {
                        editDeviceSelect.innerHTML = '<option value="">단말기를 선택하세요</option>';
                        
                        Object.keys(data.grouped).forEach(deviceName => {
                            const devices = data.grouped[deviceName];
                            
                            if (devices.length === 1) {
                                const device = devices[0];
                                const displayText = `${device.name} | ${device.storage || ''} | ${device.release_price ? (parseInt(device.release_price).toLocaleString('ko-KR') + '원') : ''}`.trim();
                                const option = document.createElement('option');
                                option.value = device.id;
                                option.textContent = displayText;
                                option.setAttribute('data-name', device.name || '');
                                option.setAttribute('data-price', device.release_price || '');
                                option.setAttribute('data-capacity', device.storage || '');
                                editDeviceSelect.appendChild(option);
                            } else {
                                const optgroup = document.createElement('optgroup');
                                optgroup.label = deviceName;
                                
                                devices.forEach(device => {
                                    const capacityText = device.storage || '';
                                    const priceText = device.release_price ? (parseInt(device.release_price).toLocaleString('ko-KR') + '원') : '';
                                    const option = document.createElement('option');
                                    option.value = device.id;
                                    option.textContent = `${deviceName} [${capacityText}] ${priceText}`.trim();
                                    option.setAttribute('data-name', device.name || '');
                                    option.setAttribute('data-price', device.release_price || '');
                                    option.setAttribute('data-capacity', device.storage || '');
                                    optgroup.appendChild(option);
                                });
                                
                                editDeviceSelect.appendChild(optgroup);
                            }
                        });
                        
                        editDeviceSelect.disabled = false;
                        
                        if (selectDeviceId) {
                            const deviceId = selectDeviceId.toString();
                            editDeviceSelect.value = deviceId;
                            
                            const selectedOption = editDeviceSelect.querySelector(`option[value="${deviceId}"]`);
                            if (selectedOption) {
                                const deviceName = selectedOption.getAttribute('data-name') || editData.device_name || '';
                                const devicePrice = selectedOption.getAttribute('data-price') || editData.device_price || '';
                                const deviceCapacity = selectedOption.getAttribute('data-capacity') || editData.device_capacity || '';
                                
                                if (editDeviceNameInput) editDeviceNameInput.value = deviceName;
                                if (editDevicePriceInput) editDevicePriceInput.value = devicePrice.toString().replace(/,/g, '');
                                if (editDeviceCapacityInput) editDeviceCapacityInput.value = deviceCapacity;
                            }
                            
                            editDeviceSelect.dispatchEvent(new Event('change'));
                        }
                    }
                })
                .catch(error => {
                    // 단말기 목록 로드 실패
                });
        }
        
        // manufacturer_id가 없으면 device_id로 제조사 정보 가져오기
        if (!editData.manufacturer_id && editData.device_id && editManufacturerSelect && editDeviceSelect) {
            fetch(`/MVNO/api/get-device-info.php?device_id=${editData.device_id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.manufacturer_id) {
                        editData.manufacturer_id = data.manufacturer_id;
                        editManufacturerSelect.value = data.manufacturer_id;
                        // 일반 모드의 loadDeviceList 함수 사용 (통일성)
                        if (typeof window.loadDeviceList === 'function') {
                            window.loadDeviceList(data.manufacturer_id, editData.device_id);
                        } else {
                            loadDeviceListForEdit(data.manufacturer_id, editData.device_id);
                        }
                    } else {
                        const errorMessage = '수정 모드 초기화 오류:<br><br>device_id로 제조사 정보를 가져올 수 없습니다.';
                        if (typeof openRegisterModal === 'function') {
                            openRegisterModal('수정 모드 오류', errorMessage, 'error');
                        } else {
                            alert('수정 모드 초기화 오류');
                        }
                    }
                })
                .catch(error => {
                    const errorMessage = '수정 모드 초기화 오류:<br><br>제조사 정보를 가져오는 중 오류가 발생했습니다.';
                    if (typeof openRegisterModal === 'function') {
                        openRegisterModal('수정 모드 오류', errorMessage, 'error');
                    } else {
                        alert('수정 모드 초기화 오류');
                    }
                });
        } else if (editData.manufacturer_id && editManufacturerSelect && editDeviceSelect) {
            // manufacturer_id가 있으면 바로 단말기 목록 로드
            editManufacturerSelect.value = editData.manufacturer_id;
            // 일반 모드의 loadDeviceList 함수 사용 (통일성)
            if (typeof window.loadDeviceList === 'function') {
                window.loadDeviceList(editData.manufacturer_id, editData.device_id);
            } else {
                loadDeviceListForEdit(editData.manufacturer_id, editData.device_id);
            }
        } else {
            // 필수 요소가 없으면 에러 표시
            const errorInfo = [];
            if (!editData.manufacturer_id && !editData.device_id) {
                errorInfo.push('제조사 ID와 단말기 ID가 모두 없습니다.');
            }
            if (!editManufacturerSelect) {
                errorInfo.push('제조사 셀렉트 박스를 찾을 수 없습니다.');
            }
            if (!editDeviceSelect) {
                errorInfo.push('단말기 셀렉트 박스를 찾을 수 없습니다.');
            }
            
            if (errorInfo.length > 0) {
                const errorMessage = '수정 모드 초기화 오류:<br><br>' + errorInfo.join('<br>');
                if (typeof openRegisterModal === 'function') {
                    openRegisterModal('수정 모드 오류', errorMessage, 'error');
                } else {
                    alert('수정 모드 초기화 오류: ' + errorInfo.join(', '));
                }
            }
        }
        
        // 할인 정보 로드 (JSON 파싱)
        if (editData.common_provider) {
            try {
                const commonProviders = JSON.parse(editData.common_provider);
                const commonDiscountNew = editData.common_discount_new ? JSON.parse(editData.common_discount_new) : [];
                const commonDiscountPort = editData.common_discount_port ? JSON.parse(editData.common_discount_port) : [];
                const commonDiscountChange = editData.common_discount_change ? JSON.parse(editData.common_discount_change) : [];
                
                const commonRows = document.querySelectorAll('.common-discount-row');
                commonRows.forEach((row, index) => {
                    if (commonProviders[index]) {
                        const providerInput = row.querySelector('input[name="common_provider[]"]');
                        if (providerInput) providerInput.value = commonProviders[index];
                        
                        const newInput = row.querySelector('input[name="common_discount_new[]"]');
                        if (newInput && commonDiscountNew[index]) newInput.value = commonDiscountNew[index];
                        
                        const portInput = row.querySelector('input[name="common_discount_port[]"]');
                        if (portInput && commonDiscountPort[index]) portInput.value = commonDiscountPort[index];
                        
                        const changeInput = row.querySelector('input[name="common_discount_change[]"]');
                        if (changeInput && commonDiscountChange[index]) changeInput.value = commonDiscountChange[index];
                    }
                });
            } catch (e) {
                // 할인 데이터 파싱 오류
            }
        }
        
        if (editData.contract_provider) {
            try {
                const contractProviders = JSON.parse(editData.contract_provider);
                const contractDiscountNew = editData.contract_discount_new ? JSON.parse(editData.contract_discount_new) : [];
                const contractDiscountPort = editData.contract_discount_port ? JSON.parse(editData.contract_discount_port) : [];
                const contractDiscountChange = editData.contract_discount_change ? JSON.parse(editData.contract_discount_change) : [];
                
                const contractRows = document.querySelectorAll('.contract-discount-row');
                contractRows.forEach((row, index) => {
                    if (contractProviders[index]) {
                        const providerInput = row.querySelector('input[name="contract_provider[]"]');
                        if (providerInput) providerInput.value = contractProviders[index];
                        
                        const newInput = row.querySelector('input[name="contract_discount_new[]"]');
                        if (newInput && contractDiscountNew[index]) newInput.value = contractDiscountNew[index];
                        
                        const portInput = row.querySelector('input[name="contract_discount_port[]"]');
                        if (portInput && contractDiscountPort[index]) portInput.value = contractDiscountPort[index];
                        
                        const changeInput = row.querySelector('input[name="contract_discount_change[]"]');
                        if (changeInput && contractDiscountChange[index]) changeInput.value = contractDiscountChange[index];
                    }
                });
            } catch (e) {
                // 할인 데이터 파싱 오류
            }
        }
        
        // 기타 필드들 채우기
        if (editData.service_type) {
            const serviceTypeSelect = document.querySelector('select[name="service_type"]');
            if (serviceTypeSelect) serviceTypeSelect.value = editData.service_type;
        }
        
        if (editData.contract_period) {
            const contractPeriodSelect = document.querySelector('select[name="contract_period"]');
            if (contractPeriodSelect) contractPeriodSelect.value = editData.contract_period;
        }
        
        if (editData.contract_period_value) {
            const contractPeriodValueInput = document.querySelector('input[name="contract_period_value"]');
            if (contractPeriodValueInput) contractPeriodValueInput.value = editData.contract_period_value;
        }
        
        if (editData.price_main) {
            const priceMainInput = document.querySelector('input[name="price_main"]');
            if (priceMainInput) priceMainInput.value = editData.price_main;
        }
        
        // 데이터량, 통화, SMS 등 나머지 필드들도 채우기
        const fieldsToFill = [
            'data_amount', 'data_amount_value', 'data_unit', 'data_exhausted', 'data_exhausted_value',
            'call_type', 'call_amount', 'additional_call_type', 'additional_call',
            'sms_type', 'sms_amount', 'mobile_hotspot', 'mobile_hotspot_value',
            'regular_sim_available', 'regular_sim_price', 'nfc_sim_available', 'nfc_sim_price',
            'esim_available', 'esim_price', 'over_data_price', 'over_voice_price',
            'over_video_price', 'over_sms_price', 'over_lms_price', 'over_mms_price',
            'promotion_title', 'delivery_method', 'visit_region'
        ];
        
        fieldsToFill.forEach(fieldName => {
            if (editData[fieldName] !== null && editData[fieldName] !== undefined && editData[fieldName] !== '') {
                const input = document.querySelector(`input[name="${fieldName}"], select[name="${fieldName}"]`);
                if (input) {
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        input.checked = editData[fieldName] === '1' || editData[fieldName] === 'true' || editData[fieldName] === true;
                    } else {
                        input.value = editData[fieldName];
                    }
                }
            }
        });
        
        // 프로모션 필드 동적 추가
        if (editData.promotions) {
            try {
                const promotions = JSON.parse(editData.promotions);
                const promotionContainer = document.getElementById('promotion-container');
                if (promotionContainer && Array.isArray(promotions) && promotions.length > 0) {
                    // 기존 필드 모두 제거
                    promotionContainer.innerHTML = '';
                    
                    // 프로모션 배열의 각 항목에 대해 필드 추가
                    promotions.forEach((promotion, index) => {
                        const newField = document.createElement('div');
                        newField.className = 'gift-input-group';
                        const isFirst = index === 0;
                        newField.innerHTML = `
                            <input type="text" name="promotions[]" class="form-control" placeholder="부가 미가입시 +10" maxlength="30" value="${promotion || ''}">
                            ${isFirst ? '<button type="button" class="btn-add" onclick="addPromotionField()">추가</button>' : '<button type="button" class="btn-remove" onclick="removePromotionField(this)">삭제</button>'}
                        `;
                        promotionContainer.appendChild(newField);
                    });
                    
                    // 프로모션이 없으면 기본 필드 하나 추가
                    if (promotions.length === 0) {
                        const newField = document.createElement('div');
                        newField.className = 'gift-input-group';
                        newField.innerHTML = `
                            <input type="text" name="promotions[]" class="form-control" placeholder="부가 미가입시 +10" maxlength="30">
                            <button type="button" class="btn-add" onclick="addPromotionField()">추가</button>
                        `;
                        promotionContainer.appendChild(newField);
                    }
                }
            } catch (e) {
                // 프로모션 데이터 파싱 오류
            }
        }
        
        if (editData.benefits) {
            try {
                const benefits = JSON.parse(editData.benefits);
                // 혜택 동적 추가 로직 필요 시 구현
            } catch (e) {
                // 혜택 데이터 파싱 오류
            }
        }
        
        // 수정 모드에서 단말기 색상 로드
        if (editData.device_id) {
            // 단말기 ID가 있으면 색상 정보 가져오기
            loadDeviceColors(editData.device_id);
            
            // 저장된 색상 체크
            if (editData.device_colors) {
                try {
                    const savedColors = JSON.parse(editData.device_colors);
                    if (Array.isArray(savedColors) && savedColors.length > 0) {
                        // 색상 정보 로드 후 체크박스 선택
                        setTimeout(() => {
                            savedColors.forEach(colorName => {
                                const checkbox = document.querySelector(`input[name="device_colors[]"][value="${colorName}"]`);
                                if (checkbox) {
                                    checkbox.checked = true;
                                }
                            });
                        }, 500);
                    }
                } catch (e) {
                    console.error('Error parsing device_colors:', e);
                }
            }
        }
        
        // delivery_method 라디오 버튼 처리
        if (editData.delivery_method) {
            const deliveryRadio = document.getElementById('delivery_enabled');
            const visitRadio = document.getElementById('visit_enabled');
            
            if (editData.delivery_method === 'visit' && visitRadio) {
                visitRadio.checked = true;
                if (typeof toggleVisitRegionInput === 'function') {
                    toggleVisitRegionInput();
                }
            } else if (deliveryRadio) {
                deliveryRadio.checked = true;
                if (typeof toggleVisitRegionInput === 'function') {
                    toggleVisitRegionInput();
                }
            }
        }
    })();
    <?php endif; ?>
});

// 단말기 색상 정보 가져오기
function loadDeviceColors(deviceId) {
    const colorContainer = document.getElementById('device-colors-container');
    if (!colorContainer || !deviceId) return;
    
    // 로딩 표시
    colorContainer.innerHTML = '<div style="width: 100%; color: #6b7280; font-size: 14px;">색상 정보를 불러오는 중...</div>';
    
    fetch(`/MVNO/api/get-device-info.php?device_id=${deviceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.colors && Array.isArray(data.colors) && data.colors.length > 0) {
                // 색상 체크박스 생성
                colorContainer.innerHTML = '';
                data.colors.forEach(color => {
                    const colorName = color.name || color;
                    const colorValue = color.value || '';
                    
                    const colorItem = document.createElement('div');
                    colorItem.style.cssText = 'display: flex; align-items: center; gap: 8px;';
                    
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = 'device_colors[]';
                    checkbox.value = colorName;
                    checkbox.id = `color_${colorName.replace(/\s+/g, '_')}`;
                    checkbox.style.cssText = 'width: 18px; height: 18px; cursor: pointer;';
                    
                    const label = document.createElement('label');
                    label.htmlFor = checkbox.id;
                    label.textContent = colorName;
                    label.style.cssText = 'cursor: pointer; font-size: 14px; color: #374151; margin: 0;';
                    
                    // 색상값이 있으면 색상 표시
                    if (colorValue) {
                        const colorIndicator = document.createElement('span');
                        colorIndicator.style.cssText = `display: inline-block; width: 20px; height: 20px; border-radius: 4px; background-color: ${colorValue}; border: 1px solid #d1d5db; margin-right: 4px;`;
                        label.insertBefore(colorIndicator, label.firstChild);
                    }
                    
                    colorItem.appendChild(checkbox);
                    colorItem.appendChild(label);
                    colorContainer.appendChild(colorItem);
                });
            } else {
                colorContainer.innerHTML = '<div style="width: 100%; color: #6b7280; font-size: 14px;">등록된 색상 정보가 없습니다.</div>';
            }
        })
        .catch(error => {
            console.error('Error loading device colors:', error);
            colorContainer.innerHTML = '<div style="width: 100%; color: #ef4444; font-size: 14px;">색상 정보를 불러오는 중 오류가 발생했습니다.</div>';
        });
}

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

function deleteProduct() {
    const productId = document.getElementById('product_id')?.value;
    if (!productId) {
        openRegisterModal('오류', '상품 ID를 찾을 수 없습니다.', 'error');
        return;
    }
    
    const message = '이 상품을 삭제하시겠습니까?\n삭제된 상품은 복구할 수 없습니다.';
    if (typeof showConfirm === 'function') {
        showConfirm(message, '상품 삭제').then(confirmed => {
            if (confirmed) {
                processDeleteProduct(productId);
            }
        });
    } else if (confirm(message)) {
        processDeleteProduct(productId);
    }
}

function processDeleteProduct(productId) {
    fetch('/MVNO/api/product-delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof showAlert === 'function') {
                showAlert('상품이 삭제되었습니다.', '완료').then(() => {
                    window.location.href = '/MVNO/seller/products/mno-list.php';
                });
            } else {
                alert('상품이 삭제되었습니다.');
                window.location.href = '/MVNO/seller/products/mno-list.php';
            }
        } else {
            if (typeof showAlert === 'function') {
                showAlert(data.message || '상품 삭제에 실패했습니다.', '오류', true);
            } else {
                alert(data.message || '상품 삭제에 실패했습니다.');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showAlert === 'function') {
            showAlert('상품 삭제 중 오류가 발생했습니다.', '오류', true);
        } else {
            alert('상품 삭제 중 오류가 발생했습니다.');
        }
    });
}

// 모달 열기
function openRegisterModal(title, message, type = 'info') {
    const modal = document.getElementById('registerModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');
    
    modalTitle.textContent = title;
    
    // HTML 메시지인 경우 innerHTML 사용, 일반 텍스트인 경우 textContent 사용
    if (typeof message === 'string' && message.includes('<br>')) {
        modalMessage.innerHTML = message;
    } else {
        modalMessage.textContent = message;
    }
    
    modalMessage.className = 'modal-message ' + type;
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// 모달 닫기
function closeRegisterModal() {
    const modal = document.getElementById('registerModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// 모달 외부 클릭 시 닫기
document.getElementById('registerModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRegisterModal();
    }
});

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('registerModal');
        if (modal.classList.contains('active')) {
            closeRegisterModal();
        }
    }
});

document.getElementById('productForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // 필수 필드 검증
    const deviceIdSelect = document.getElementById('device_id');
    const deviceId = deviceIdSelect ? deviceIdSelect.value : '';
    const deviceNameInput = document.getElementById('device_name');
    let deviceName = deviceNameInput ? deviceNameInput.value : '';
    const devicePriceInput = document.getElementById('device_price');
    let devicePrice = devicePriceInput ? devicePriceInput.value : '';
    const deviceCapacityInput = document.getElementById('device_capacity');
    let deviceCapacity = deviceCapacityInput ? deviceCapacityInput.value : '';
    const manufacturerId = document.getElementById('manufacturer_id') ? document.getElementById('manufacturer_id').value : '';
    
    // device_id는 선택되어 있지만 hidden 필드가 비어있는 경우, 옵션 텍스트에서 파싱
    if (deviceId && deviceId.trim() !== '' && (!deviceName || deviceName.trim() === '')) {
        // 선택된 옵션 찾기
        let selectedOption = null;
        if (deviceIdSelect.selectedOptions && deviceIdSelect.selectedOptions.length > 0) {
            selectedOption = deviceIdSelect.selectedOptions[0];
        } else {
            // value로 찾기 (optgroup 내부 옵션도 포함)
            const allOptions = deviceIdSelect.querySelectorAll('option');
            for (let i = 0; i < allOptions.length; i++) {
                if (allOptions[i].value === deviceId) {
                    selectedOption = allOptions[i];
                    break;
                }
            }
        }
        
        if (selectedOption && selectedOption.value) {
            // 먼저 data 속성 시도
            deviceName = selectedOption.getAttribute('data-name') || '';
            const priceAttr = selectedOption.getAttribute('data-price') || '';
            devicePrice = priceAttr ? priceAttr.toString().replace(/,/g, '') : '';
            deviceCapacity = selectedOption.getAttribute('data-capacity') || '';
            
            // data 속성이 없으면 옵션 텍스트에서 파싱
            if (!deviceName || !devicePrice || !deviceCapacity) {
                const optionText = (selectedOption.textContent || selectedOption.innerText || '').trim();
                
                // 형식 1: "Galaxy S23 Ultra [256GB] 1,599,400원"
                const match1 = optionText.match(/^(.+?)\s*\[(.+?)\]\s*(.+?)$/);
                if (match1) {
                    deviceName = deviceName || match1[1].trim();
                    deviceCapacity = deviceCapacity || match1[2].trim();
                    const priceText = match1[3].trim().replace(/원/g, '').replace(/,/g, '').trim();
                    devicePrice = devicePrice || priceText;
                } else {
                    // 형식 2: "Galaxy S23 Ultra | 256GB | 1,599,400원"
                    const parts = optionText.split('|').map(p => p.trim());
                    if (parts.length >= 3) {
                        deviceName = deviceName || parts[0];
                        deviceCapacity = deviceCapacity || parts[1];
                        devicePrice = devicePrice || parts[2].replace(/원/g, '').replace(/,/g, '').trim();
                    }
                }
            }
            
            // Hidden 필드 채우기
            if (deviceNameInput && deviceName) {
                deviceNameInput.value = deviceName;
            }
            if (devicePriceInput && devicePrice) {
                devicePriceInput.value = devicePrice;
            }
            if (deviceCapacityInput && deviceCapacity) {
                deviceCapacityInput.value = deviceCapacity;
            }
            
            // 다시 읽어서 확인
            deviceName = deviceNameInput ? deviceNameInput.value : '';
            devicePrice = devicePriceInput ? devicePriceInput.value : '';
            deviceCapacity = deviceCapacityInput ? deviceCapacityInput.value : '';
        }
    }
    
    if (!deviceId || deviceId.trim() === '' || !deviceName || deviceName.trim() === '') {
        openRegisterModal('입력 오류', '단말기를 선택해주세요.', 'error');
        if (deviceIdSelect) {
            deviceIdSelect.focus();
        }
        return;
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
            // 체크박스가 체크되고 값이 있으면 그대로 사용
            formData.set('redirect_url', redirectUrlInput.value.trim());
        }
    }
    
    // 로딩 모달 표시
    openRegisterModal('등록 중...', '상품을 등록하고 있습니다. 잠시만 기다려주세요.', 'info');
    const modalMessage = document.getElementById('modalMessage');
    modalMessage.innerHTML = '<div style="display: flex; align-items: center; justify-content: center;"><span class="loading-spinner"></span>상품을 등록하고 있습니다. 잠시만 기다려주세요.</div>';
    document.getElementById('modalConfirmBtn').style.display = 'none';
    
    // 제출 버튼 비활성화
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    
    fetch('/MVNO/api/product-register-mno.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 성공 모달
            const isEditMode = <?php echo $editMode ? 'true' : 'false'; ?>;
            document.getElementById('modalTitle').textContent = isEditMode ? '수정 완료' : '등록 완료';
            modalMessage.innerHTML = isEditMode ? '상품이 성공적으로 수정되었습니다.' : '상품이 성공적으로 등록되었습니다.';
            modalMessage.className = 'modal-message success';
            document.getElementById('modalConfirmBtn').style.display = 'block';
            document.getElementById('modalConfirmBtn').onclick = function() {
                if (isEditMode) {
                    window.location.href = '/MVNO/seller/products/mno-list.php';
                } else {
                    window.location.href = '/MVNO/seller/products/mno.php?success=1';
                }
            };
        } else {
            // 실패 모달 - 상세 에러 정보 표시
            document.getElementById('modalTitle').textContent = '등록 실패';
            
            let errorHtml = '<div style="margin-bottom: 12px;"><strong>' + (data.message || '상품 등록에 실패했습니다.') + '</strong></div>';
            
            if (data.error_details) {
                errorHtml += '<div style="margin-top: 16px; padding: 12px; background: #fef2f2; border-radius: 8px; border-left: 4px solid #ef4444;">';
                errorHtml += '<div style="font-size: 13px; color: #991b1b; font-weight: 600; margin-bottom: 8px;">상세 오류 정보:</div>';
                errorHtml += '<div style="font-size: 12px; color: #7f1d1d; font-family: monospace; white-space: pre-wrap; word-break: break-all;">' + data.error_details + '</div>';
                errorHtml += '</div>';
            }
            
            if (data.solution) {
                errorHtml += '<div style="margin-top: 16px; padding: 16px; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">';
                errorHtml += '<div style="font-size: 14px; color: #92400e; font-weight: 600; margin-bottom: 8px;">💡 해결 방법:</div>';
                errorHtml += '<div style="font-size: 13px; color: #78350f; margin-bottom: 12px;">' + data.solution + '</div>';
                if (data.solution.includes('install_mno_tables.php')) {
                    errorHtml += '<a href="/MVNO/database/install_mno_tables.php" target="_blank" style="display: inline-block; padding: 10px 20px; background: #f59e0b; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; margin-top: 8px;">테이블 생성 페이지 열기</a>';
                }
                errorHtml += '</div>';
            }
            
            
            if (data.error_trace) {
                errorHtml += '<details style="margin-top: 12px;">';
                errorHtml += '<summary style="font-size: 12px; color: #6b7280; cursor: pointer; padding: 8px; background: #f3f4f6; border-radius: 6px;">스택 트레이스 보기</summary>';
                errorHtml += '<div style="margin-top: 8px; padding: 12px; background: #f9fafb; border-radius: 8px; font-size: 11px; color: #4b5563; font-family: monospace; white-space: pre-wrap; max-height: 200px; overflow-y: auto;">' + data.error_trace + '</div>';
                errorHtml += '</details>';
            }
            
            modalMessage.innerHTML = errorHtml;
            modalMessage.className = 'modal-message error';
            document.getElementById('modalConfirmBtn').style.display = 'block';
            document.getElementById('modalConfirmBtn').onclick = closeRegisterModal;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        document.getElementById('modalTitle').textContent = '오류 발생';
        modalMessage.textContent = '상품 등록 중 오류가 발생했습니다. 다시 시도해주세요.';
        modalMessage.className = 'modal-message error';
        document.getElementById('modalConfirmBtn').style.display = 'block';
        document.getElementById('modalConfirmBtn').onclick = closeRegisterModal;
        submitBtn.disabled = false;
    });
});

// URL 입력 체크박스 토글 기능
document.addEventListener('DOMContentLoaded', function() {
    const enableRedirectUrlCheckbox = document.getElementById('enable_redirect_url');
    const redirectUrlContainer = document.getElementById('redirect_url_container');
    const redirectUrlInput = document.getElementById('redirect_url');
    
    if (enableRedirectUrlCheckbox && redirectUrlContainer) {
        // 수정 모드일 때 기존 URL이 있으면 체크박스 체크
        <?php if ($editMode && isset($deviceData['redirect_url']) && !empty($deviceData['redirect_url'])): ?>
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

<?php
/**
 * 판매자 가입 페이지
 * 경로: /MVNO/seller/register.php
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';

// 이미 로그인한 경우 리다이렉트
if (isLoggedIn()) {
    $currentUser = getCurrentUser();
    if ($currentUser && $currentUser['role'] === 'seller') {
        header('Location: /MVNO/seller/');
        exit;
    } else {
        header('Location: /MVNO/');
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = strtolower(trim($_POST['user_id'] ?? '')); // 소문자로 변환
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    // 이메일 처리 (@ 앞부분과 뒷부분 분리 또는 전체 이메일)
    $emailLocal = trim($_POST['email_local'] ?? '');
    $emailDomain = trim($_POST['email_domain'] ?? '');
    $emailCustom = trim($_POST['email_custom'] ?? '');
    
    // 이메일 조합
    if (!empty($emailCustom)) {
        $email = $emailLocal . '@' . $emailCustom;
    } else {
        $email = $emailLocal . '@' . $emailDomain;
    }
    $email = trim($email);
    
    $name = trim($_POST['name'] ?? '');
    
    // 기본 필드 검증
    if (empty($userId) || empty($password) || empty($email) || empty($name)) {
        $error = '모든 필드를 입력해주세요.';
    } elseif (!preg_match('/^[a-z0-9]{5,20}$/', $userId)) {
        $error = '아이디는 소문자 영문자와 숫자 조합 5-20자로 입력해주세요.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '올바른 이메일 형식이 아닙니다.';
    } elseif ($password !== $passwordConfirm) {
        $error = '비밀번호가 일치하지 않습니다.';
    } elseif (strlen($password) < 8) {
        $error = '비밀번호는 최소 8자 이상이어야 합니다.';
    } else {
        // 판매자 추가 정보 수집
        $additionalData = [];
        $additionalData['phone'] = trim($_POST['phone'] ?? '');
        $additionalData['mobile'] = trim($_POST['mobile'] ?? '');
        $additionalData['postal_code'] = trim($_POST['postal_code'] ?? '');
        $additionalData['address'] = trim($_POST['address'] ?? '');
        $additionalData['address_detail'] = trim($_POST['address_detail'] ?? '');
        $additionalData['business_number'] = trim($_POST['business_number'] ?? '');
        $additionalData['company_name'] = trim($_POST['company_name'] ?? '');
        $additionalData['company_representative'] = trim($_POST['company_representative'] ?? '');
        $additionalData['business_type'] = trim($_POST['business_type'] ?? '');
        $additionalData['business_item'] = trim($_POST['business_item'] ?? '');
        
        // 판매자 필수 필드 확인
        if (empty($additionalData['business_number']) || empty($additionalData['company_name'])) {
            $error = '사업자등록번호와 회사명은 필수 입력 항목입니다.';
        } elseif (mb_strlen($additionalData['company_name']) > 20) {
            $error = '회사명은 20자 이내로 입력해주세요.';
        } elseif (!empty($additionalData['company_representative']) && mb_strlen($additionalData['company_representative']) > 20) {
            $error = '대표자명은 20자 이내로 입력해주세요.';
        } elseif (!empty($additionalData['business_type']) && mb_strlen($additionalData['business_type']) > 20) {
            $error = '업종은 20자 이내로 입력해주세요.';
        } elseif (!empty($additionalData['business_item']) && mb_strlen($additionalData['business_item']) > 20) {
            $error = '업태는 20자 이내로 입력해주세요.';
        } else {
            // 휴대폰 번호 검증 (입력된 경우에만)
            if (!empty($additionalData['mobile'])) {
                $mobileNumbers = preg_replace('/[^\d]/', '', $additionalData['mobile']);
                if (!preg_match('/^010\d{8}$/', $mobileNumbers)) {
                    $error = '휴대폰 번호는 010으로 시작하는 11자리 숫자여야 합니다.';
                }
            }
            
            // 전화번호 형식 검증 (입력된 경우에만)
            if (empty($error) && !empty($additionalData['phone'])) {
                $phoneNumbers = preg_replace('/[^\d]/', '', $additionalData['phone']);
                if (strlen($phoneNumbers) < 9 || strlen($phoneNumbers) > 11) {
                    $error = '전화번호 형식이 올바르지 않습니다.';
                }
            }
            
            if (empty($error)) {
                // 사업자등록증 이미지 업로드 처리
                if (isset($_FILES['business_license_image']) && $_FILES['business_license_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../uploads/sellers/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileExtension = pathinfo($_FILES['business_license_image']['name'], PATHINFO_EXTENSION);
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
                    if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
                        $error = '허용되지 않은 파일 형식입니다. (JPG, PNG, GIF, PDF만 가능)';
                    } else {
                        $fileName = $userId . '_license_' . time() . '.' . $fileExtension;
                        $uploadPath = $uploadDir . $fileName;
                        
                        // 파일 크기 확인 (5MB)
                        if ($_FILES['business_license_image']['size'] > 5 * 1024 * 1024) {
                            $error = '파일 크기는 5MB 이하여야 합니다.';
                        } elseif (move_uploaded_file($_FILES['business_license_image']['tmp_name'], $uploadPath)) {
                            $additionalData['business_license_image'] = '/MVNO/uploads/sellers/' . $fileName;
                        } else {
                            $error = '파일 업로드에 실패했습니다.';
                        }
                    }
                } else {
                    $error = '사업자등록증 이미지를 업로드해주세요.';
                }
            }
        }
        
        if (empty($error)) {
            $result = registerDirectUser($userId, $password, $email, $name, 'seller', $additionalData);
            if ($result['success']) {
                // 가입 성공 시 판매자 센터로 리다이렉트
                header('Location: /MVNO/seller/?register=success');
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>판매자 가입 - 모요</title>
    <link rel="stylesheet" href="/MVNO/assets/css/style.css">
    <script src="/MVNO/assets/js/modal.js" defer></script>

<style>
    .seller-register-container {
        max-width: 800px;
        margin: 40px auto;
        padding: 40px 24px;
    }
    
    .register-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .register-header h1 {
        font-size: 32px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 12px;
    }
    
    .register-header p {
        font-size: 16px;
        color: #6b7280;
    }
    
    .error-message {
        padding: 16px;
        background: #fee2e2;
        color: #991b1b;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
        border: 1px solid #ef4444;
    }
    
    .success-message {
        padding: 16px;
        background: #d1fae5;
        color: #065f46;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
        border: 1px solid #10b981;
        text-align: center;
    }
    
    .register-form {
        background: white;
        border-radius: 12px;
        padding: 32px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .form-section {
        margin-bottom: 32px;
    }
    
    .form-section-title {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .form-group label .required {
        color: #ef4444;
        margin-left: 4px;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 15px;
        transition: border-color 0.2s;
        box-sizing: border-box;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    /* 이메일 입력 필드 스타일 */
    .email-input-group {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .email-input-group input[type="text"] {
        flex: 1;
        min-width: 120px;
    }
    
    .email-input-group select {
        flex: 1;
        min-width: 150px;
    }
    
    .email-input-group .email-at {
        font-size: 16px;
        color: #6b7280;
        white-space: nowrap;
        padding: 0 4px;
    }
    
    .form-help {
        font-size: 13px;
        color: #6b7280;
        margin-top: 6px;
    }
    
    .file-upload-area {
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        padding: 24px;
        text-align: center;
        background: #f9fafb;
        transition: all 0.2s;
    }
    
    .file-upload-area:hover {
        border-color: #6366f1;
        background: #f3f4f6;
    }
    
    .file-upload-area.has-file {
        border-color: #10b981;
        background: #f0fdf4;
    }
    
    .file-upload-input {
        display: none;
    }
    
    .file-upload-label {
        cursor: pointer;
        display: block;
    }
    
    .file-upload-icon {
        width: 48px;
        height: 48px;
        margin: 0 auto 12px;
        color: #9ca3af;
    }
    
    .file-upload-text {
        font-size: 14px;
        color: #6b7280;
        margin-bottom: 8px;
    }
    
    .file-upload-hint {
        font-size: 12px;
        color: #9ca3af;
    }
    
    .file-preview {
        margin-top: 16px;
        padding: 16px;
        background: white;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }
    
    .file-preview img {
        max-width: 100%;
        max-height: 300px;
        border-radius: 8px;
    }
    
    .register-button {
        width: 100%;
        padding: 16px;
        background: #6366f1;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
        margin-top: 24px;
    }
    
    .register-button:hover {
        background: #4f46e5;
    }
    
    .register-button:disabled {
        background: #9ca3af;
        cursor: not-allowed;
    }
    
    .login-link {
        text-align: center;
        margin-top: 24px;
        font-size: 14px;
        color: #6b7280;
    }
    
    .login-link a {
        color: #6366f1;
        text-decoration: none;
        font-weight: 500;
    }
    
    .login-link a:hover {
        text-decoration: underline;
    }
    
    /* 단계 표시 */
    .step-indicator {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 40px;
        padding: 0 20px;
    }
    
    .step-item {
        flex: 1;
        text-align: center;
        position: relative;
    }
    
    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e5e7eb;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 8px;
        font-weight: 700;
        transition: all 0.3s;
    }
    
    .step-item.active .step-number {
        background: #6366f1;
        color: white;
    }
    
    .step-item.completed .step-number {
        background: #10b981;
        color: white;
    }
    
    .step-label {
        font-size: 14px;
        font-weight: 600;
        color: #6b7280;
        transition: color 0.3s;
    }
    
    .step-item.active .step-label {
        color: #6366f1;
    }
    
    .step-item.completed .step-label {
        color: #10b981;
    }
    
    .step-line {
        flex: 1;
        height: 2px;
        background: #e5e7eb;
        margin: 0 10px;
        margin-top: -20px;
        transition: background 0.3s;
    }
    
    .step-line.completed {
        background: #10b981;
    }
    
    .step-content {
        display: none;
    }
    
    .step-content.active {
        display: block;
    }
    
    .step-buttons {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-top: 32px;
    }
    
    .btn-step {
        flex: 1;
        padding: 14px 24px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-prev {
        background: #f3f4f6;
        color: #374151;
    }
    
    .btn-prev:hover {
        background: #e5e7eb;
    }
    
    .btn-next,
    .btn-submit {
        background: #6366f1;
        color: white;
    }
    
    .btn-next:hover,
    .btn-submit:hover {
        background: #4f46e5;
    }
    
    .btn-step:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    /* 중복확인 버튼 */
    .check-duplicate-btn {
        padding: 12px 20px;
        background: #6366f1;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        transition: background 0.2s;
    }
    
    .check-duplicate-btn:hover {
        background: #4f46e5;
    }
    
    .check-duplicate-btn:disabled {
        background: #9ca3af;
        cursor: not-allowed;
    }
    
    .check-result {
        margin-top: 8px;
        font-size: 13px;
        font-weight: 500;
    }
    
    .check-result.success {
        color: #10b981;
    }
    
    .check-result.error {
        color: #ef4444;
    }
    
    .check-result.checking {
        color: #6b7280;
    }
    
    input.checked-valid {
        border-color: #10b981;
    }
    
    input.checked-invalid {
        border-color: #ef4444;
    }
</style>
</head>
<body>
<main class="main-content">
    <div class="seller-register-container">
        <div class="register-header">
            <h1>판매자 가입</h1>
            <p>모요 판매자로 가입하여 상품을 등록하고 판매하세요</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['register']) && $_GET['register'] === 'success'): ?>
            <div class="success-message">
                판매자 가입이 완료되었습니다.<br>
                관리자 승인 후 상품 등록이 가능합니다.<br>
                <a href="/MVNO/seller/login.php" style="color: #065f46; font-weight: 600; margin-top: 12px; display: inline-block;">로그인하러 가기</a>
            </div>
        <?php endif; ?>
        
        <?php if (!isset($_GET['register']) || $_GET['register'] !== 'success'): ?>
            <div class="register-form">
                <!-- 단계 표시 -->
                <div class="step-indicator" id="stepIndicator">
                    <div class="step-item active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-label">기본 정보</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step-item" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-label">서류 업로드</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step-item" data-step="3">
                        <div class="step-number">3</div>
                        <div class="step-label">사업자 정보</div>
                    </div>
                </div>
                
                <form method="POST" enctype="multipart/form-data" id="registerForm">
                    <!-- 기본 정보 -->
                    <div class="form-section step-content active" id="step1">
                        <h2 class="form-section-title">1단계: 기본 정보</h2>
                        
                        <div class="form-group">
                            <label for="user_id">아이디 <span class="required">*</span></label>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" id="user_id" name="user_id" required value="<?php echo htmlspecialchars($_POST['user_id'] ?? ''); ?>" style="flex: 1;" pattern="[a-z0-9]{5,20}" title="소문자 영문자와 숫자 조합 5-20자로 입력해주세요." minlength="5" maxlength="20">
                                <button type="button" id="checkUserIdBtn" class="check-duplicate-btn" onclick="checkDuplicate('user_id')">중복확인</button>
                            </div>
                            <div class="form-help">소문자 영문자와 숫자 조합 5-20자</div>
                            <div id="userIdCheckResult" class="check-result"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email_local">이메일 <span class="required">*</span></label>
                            <div class="email-input-group">
                                <input type="text" id="email_local" name="email_local" required 
                                       value="<?php echo htmlspecialchars(explode('@', $_POST['email'] ?? '')[0] ?? ''); ?>" 
                                       placeholder="이메일 아이디">
                                <span class="email-at">@</span>
                                <select id="email_domain" name="email_domain" onchange="handleEmailDomainChange()">
                                    <option value="">선택하세요</option>
                                    <option value="naver.com" <?php echo (isset($_POST['email_domain']) && $_POST['email_domain'] === 'naver.com') || (isset($_POST['email']) && strpos($_POST['email'], '@naver.com') !== false) ? 'selected' : ''; ?>>naver.com</option>
                                    <option value="gmail.com" <?php echo (isset($_POST['email_domain']) && $_POST['email_domain'] === 'gmail.com') || (isset($_POST['email']) && strpos($_POST['email'], '@gmail.com') !== false) ? 'selected' : ''; ?>>gmail.com</option>
                                    <option value="hanmail.net" <?php echo (isset($_POST['email_domain']) && $_POST['email_domain'] === 'hanmail.net') || (isset($_POST['email']) && strpos($_POST['email'], '@hanmail.net') !== false) ? 'selected' : ''; ?>>hanmail.net</option>
                                    <option value="nate.com" <?php echo (isset($_POST['email_domain']) && $_POST['email_domain'] === 'nate.com') || (isset($_POST['email']) && strpos($_POST['email'], '@nate.com') !== false) ? 'selected' : ''; ?>>nate.com</option>
                                    <option value="custom">직접 입력</option>
                                </select>
                                <input type="text" id="email_custom" name="email_custom" 
                                       value="<?php 
                                       $emailParts = explode('@', $_POST['email'] ?? '');
                                       if (count($emailParts) === 2) {
                                           $domain = $emailParts[1];
                                           $commonDomains = ['naver.com', 'gmail.com', 'hanmail.net', 'nate.com'];
                                           if (!in_array($domain, $commonDomains)) {
                                               echo htmlspecialchars($domain);
                                           }
                                       }
                                       ?>" 
                                       placeholder="도메인 입력" 
                                       style="display: none;">
                                <button type="button" id="checkEmailBtn" class="check-duplicate-btn" onclick="checkDuplicate('email')">중복확인</button>
                            </div>
                            <input type="hidden" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            <div id="emailCheckResult" class="check-result"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="name">이름 <span class="required">*</span></label>
                            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">비밀번호 <span class="required">*</span></label>
                            <input type="password" id="password" name="password" required minlength="8">
                            <div class="form-help">최소 8자 이상 입력해주세요.</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="password_confirm">비밀번호 확인 <span class="required">*</span></label>
                            <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
                        </div>
                    </div>
                    
                    <!-- 사업자등록증 -->
                    <div class="form-section step-content" id="step2" style="display: none;">
                        <h2 class="form-section-title">2단계: 사업자등록증 업로드 <span class="required">*</span></h2>
                        
                        <div class="form-group">
                            <label>사업자등록증 이미지</label>
                            <div class="file-upload-area" id="fileUploadArea">
                                <input type="file" id="business_license_image" name="business_license_image" accept="image/*,application/pdf" class="file-upload-input" required>
                                <label for="business_license_image" class="file-upload-label">
                                    <svg class="file-upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                        <polyline points="17 8 12 3 7 8"/>
                                        <line x1="12" y1="3" x2="12" y2="15"/>
                                    </svg>
                                    <div class="file-upload-text">클릭하여 파일 선택</div>
                                    <div class="file-upload-hint">JPG, PNG, GIF, PDF (최대 5MB)</div>
                                </label>
                            </div>
                            <div id="filePreview" class="file-preview" style="display: none;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                    <strong style="font-size: 14px; color: #1f2937;">업로드된 파일</strong>
                                    <button type="button" onclick="removeFile()" style="padding: 4px 12px; background: #ef4444; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;">삭제</button>
                                </div>
                                <div id="previewContent"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 사업자 정보 -->
                    <div class="form-section step-content" id="step3" style="display: none;">
                        <h2 class="form-section-title">3단계: 사업자 정보</h2>
                        
                        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 24px 0 16px 0;">사업자 등록 정보</h3>
                        
                        <div class="form-group">
                            <label for="business_number">사업자등록번호 <span class="required">*</span></label>
                            <input type="text" id="business_number" name="business_number" placeholder="123-45-67890" required value="<?php echo htmlspecialchars($_POST['business_number'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="company_name">회사명 <span class="required">*</span></label>
                            <input type="text" id="company_name" name="company_name" placeholder="(주)회사명" required maxlength="20" value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>">
                            <div class="form-help">20자 이내로 입력해주세요.</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="company_representative">대표자명</label>
                            <input type="text" id="company_representative" name="company_representative" placeholder="홍길동" maxlength="20" value="<?php echo htmlspecialchars($_POST['company_representative'] ?? ''); ?>">
                            <div class="form-help">20자 이내로 입력해주세요.</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="business_type">업종</label>
                            <input type="text" id="business_type" name="business_type" placeholder="도매 및 소매업" maxlength="20" value="<?php echo htmlspecialchars($_POST['business_type'] ?? ''); ?>">
                            <div class="form-help">20자 이내로 입력해주세요.</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="business_item">업태</label>
                            <input type="text" id="business_item" name="business_item" placeholder="통신판매업" maxlength="20" value="<?php echo htmlspecialchars($_POST['business_item'] ?? ''); ?>">
                            <div class="form-help">20자 이내로 입력해주세요.</div>
                        </div>
                        
                        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 24px 0 16px 0;">주소 정보</h3>
                        
                        <div class="form-group">
                            <label for="address">주소</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" id="address" name="address" placeholder="서울시 강남구" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" style="flex: 1;" readonly>
                                <button type="button" id="searchAddressBtn" class="check-duplicate-btn" onclick="searchAddress()">주소 검색</button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address_detail">상세주소</label>
                            <input type="text" id="address_detail" name="address_detail" placeholder="상세주소를 입력하세요" value="<?php echo htmlspecialchars($_POST['address_detail'] ?? ''); ?>">
                        </div>
                        
                        <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin: 24px 0 16px 0;">연락처 정보</h3>
                        
                        <div class="form-group">
                            <label for="mobile">휴대폰</label>
                            <input type="tel" id="mobile" name="mobile" placeholder="010-1234-5678" value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">전화번호</label>
                            <input type="tel" id="phone" name="phone" placeholder="02-1234-5678" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- 단계별 버튼 -->
                    <div class="step-buttons">
                        <button type="button" class="btn-step btn-prev" id="prevBtn" style="display: none;" onclick="prevStep()">이전</button>
                        <button type="button" class="btn-step btn-next" id="nextBtn" onclick="nextStep()">다음</button>
                        <button type="submit" class="btn-step btn-submit" id="submitBtn" style="display: none;">판매자 가입 신청</button>
                    </div>
                </form>
                
                <div class="login-link">
                    이미 계정이 있으신가요? <a href="/MVNO/seller/login.php">로그인</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    let currentStep = 1;
    const totalSteps = 3;
    
    // 단계 이동 함수
    function showStep(step) {
        // 모든 단계 숨기기
        document.querySelectorAll('.step-content').forEach(content => {
            content.classList.remove('active');
            content.style.display = 'none';
        });
        
        // 현재 단계 표시
        const stepContent = document.getElementById('step' + step);
        if (stepContent) {
            stepContent.classList.add('active');
            stepContent.style.display = 'block';
        }
        
        // 단계 표시기 업데이트
        document.querySelectorAll('.step-item').forEach((item, index) => {
            const stepNum = index + 1;
            item.classList.remove('active', 'completed');
            
            if (stepNum < step) {
                item.classList.add('completed');
            } else if (stepNum === step) {
                item.classList.add('active');
            }
        });
        
        // 단계 라인 업데이트
        document.querySelectorAll('.step-line').forEach((line, index) => {
            if (index + 1 < step) {
                line.classList.add('completed');
            } else {
                line.classList.remove('completed');
            }
        });
        
        // 버튼 표시/숨김
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const submitBtn = document.getElementById('submitBtn');
        
        if (step === 1) {
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'block';
            submitBtn.style.display = 'none';
        } else if (step === totalSteps) {
            prevBtn.style.display = 'block';
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'block';
        } else {
            prevBtn.style.display = 'block';
            nextBtn.style.display = 'block';
            submitBtn.style.display = 'none';
        }
        
        currentStep = step;
    }
    
    // 다음 단계
    function nextStep() {
        if (validateStep(currentStep)) {
            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
            }
        }
    }
    
    // 이전 단계
    function prevStep() {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    }
    
    // 이메일 조합 함수
    function getCombinedEmail() {
        const emailLocal = document.getElementById('email_local').value.trim();
        const emailDomain = document.getElementById('email_domain').value;
        const emailCustom = document.getElementById('email_custom').value.trim();
        
        if (!emailLocal) {
            return '';
        }
        
        let domain = '';
        if (emailDomain === 'custom') {
            domain = emailCustom;
        } else {
            domain = emailDomain;
        }
        
        if (!domain) {
            return '';
        }
        
        return emailLocal + '@' + domain;
    }
    
    // 이메일 도메인 선택 변경 처리
    function handleEmailDomainChange() {
        const emailDomain = document.getElementById('email_domain').value;
        const emailCustom = document.getElementById('email_custom');
        
        if (emailDomain === 'custom') {
            emailCustom.style.display = 'block';
            emailCustom.required = true;
            emailCustom.focus();
        } else {
            emailCustom.style.display = 'none';
            emailCustom.required = false;
            emailCustom.value = '';
        }
        
        // 이메일 조합하여 hidden 필드 업데이트
        updateEmailField();
        
        // 중복확인 상태 초기화
        emailChecked = false;
        emailValid = false;
        document.getElementById('email_local').classList.remove('checked-valid', 'checked-invalid');
        document.getElementById('emailCheckResult').innerHTML = '';
        document.getElementById('emailCheckResult').className = 'check-result';
    }
    
    // 이메일 필드 업데이트
    function updateEmailField() {
        const combinedEmail = getCombinedEmail();
        document.getElementById('email').value = combinedEmail;
    }
    
    // 중복확인
    let userIdChecked = false;
    let emailChecked = false;
    let userIdValid = false;
    let emailValid = false;
    
    function checkDuplicate(type) {
        let value = '';
        let input = null;
        
        if (type === 'email') {
            value = getCombinedEmail();
            input = document.getElementById('email_local');
        } else {
            input = document.getElementById(type);
            value = input.value.trim();
        }
        
        const checkBtn = document.getElementById('check' + (type === 'user_id' ? 'UserId' : 'Email') + 'Btn');
        const resultDiv = document.getElementById((type === 'user_id' ? 'userId' : 'email') + 'CheckResult');
        
        if (!value) {
            showAlert(type === 'user_id' ? '아이디를 입력해주세요.' : '이메일을 입력해주세요.').then(() => {
                if (type === 'email') {
                    document.getElementById('email_local').focus();
                } else {
                    input.focus();
                }
            });
            return;
        }
        
        // 이메일 형식 검증
        if (type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                resultDiv.innerHTML = '<span class="error">올바른 이메일 형식이 아닙니다.</span>';
                resultDiv.className = 'check-result error';
                document.getElementById('email_local').classList.remove('checked-valid');
                document.getElementById('email_local').classList.add('checked-invalid');
                emailChecked = true;
                emailValid = false;
                return;
            }
        }
        
        // 아이디 형식 검증 (소문자 영문자와 숫자 조합 5-20자)
        if (type === 'user_id') {
            const userIdRegex = /^[a-z0-9]{5,20}$/;
            if (!userIdRegex.test(value)) {
                resultDiv.innerHTML = '<span class="error">소문자 영문자와 숫자 조합 5-20자로 입력해주세요.</span>';
                resultDiv.className = 'check-result error';
                input.classList.remove('checked-valid');
                input.classList.add('checked-invalid');
                userIdChecked = true;
                userIdValid = false;
                return;
            }
            
            // 금지된 아이디는 서버에서 체크하므로 클라이언트 측 사전 검증은 생략
        }
        
        // 중복확인 중
        checkBtn.disabled = true;
        checkBtn.textContent = '확인 중...';
        resultDiv.innerHTML = '<span class="checking">확인 중...</span>';
        resultDiv.className = 'check-result checking';
        
        fetch(`/MVNO/api/check-seller-duplicate.php?type=${type}&value=${encodeURIComponent(value)}`)
            .then(response => response.json())
            .then(data => {
                checkBtn.disabled = false;
                checkBtn.textContent = '중복확인';
                
                if (data.success && !data.duplicate) {
                    resultDiv.innerHTML = '<span class="success">✓ ' + data.message + '</span>';
                    resultDiv.className = 'check-result success';
                    if (type === 'email') {
                        document.getElementById('email_local').classList.remove('checked-invalid');
                        document.getElementById('email_local').classList.add('checked-valid');
                    } else {
                        input.classList.remove('checked-invalid');
                        input.classList.add('checked-valid');
                    }
                    
                    if (type === 'user_id') {
                        userIdChecked = true;
                        userIdValid = true;
                    } else {
                        emailChecked = true;
                        emailValid = true;
                    }
                } else {
                    resultDiv.innerHTML = '<span class="error">✗ ' + data.message + '</span>';
                    resultDiv.className = 'check-result error';
                    if (type === 'email') {
                        document.getElementById('email_local').classList.remove('checked-valid');
                        document.getElementById('email_local').classList.add('checked-invalid');
                    } else {
                        input.classList.remove('checked-valid');
                        input.classList.add('checked-invalid');
                    }
                    
                    if (type === 'user_id') {
                        userIdChecked = true;
                        userIdValid = false;
                    } else {
                        emailChecked = true;
                        emailValid = false;
                    }
                }
            })
            .catch(error => {
                checkBtn.disabled = false;
                checkBtn.textContent = '중복확인';
                resultDiv.innerHTML = '<span class="error">확인 중 오류가 발생했습니다.</span>';
                resultDiv.className = 'check-result error';
                console.error('Error:', error);
            });
    }
    
    // 아이디/이메일 입력 시 중복확인 상태 초기화 및 소문자 영문자와 숫자만 입력 제한
    document.getElementById('user_id').addEventListener('input', function(e) {
        // 소문자 영문자와 숫자만 허용 (대문자는 소문자로 변환)
        this.value = this.value.replace(/[^a-z0-9]/gi, '').toLowerCase();
        
        userIdChecked = false;
        userIdValid = false;
        this.classList.remove('checked-valid', 'checked-invalid');
        document.getElementById('userIdCheckResult').innerHTML = '';
        document.getElementById('userIdCheckResult').className = 'check-result';
    });
    
    // 이메일 입력 필드 변경 시 처리
    document.getElementById('email_local').addEventListener('input', function() {
        updateEmailField();
        emailChecked = false;
        emailValid = false;
        this.classList.remove('checked-valid', 'checked-invalid');
        document.getElementById('emailCheckResult').innerHTML = '';
        document.getElementById('emailCheckResult').className = 'check-result';
    });
    
    document.getElementById('email_custom').addEventListener('input', function() {
        updateEmailField();
        emailChecked = false;
        emailValid = false;
        document.getElementById('email_local').classList.remove('checked-valid', 'checked-invalid');
        document.getElementById('emailCheckResult').innerHTML = '';
        document.getElementById('emailCheckResult').className = 'check-result';
    });
    
    // 단계별 유효성 검사
    function validateStep(step) {
        let isValid = true;
        
        if (step === 1) {
            const userId = document.getElementById('user_id').value.trim();
            const emailLocal = document.getElementById('email_local').value.trim();
            const emailDomain = document.getElementById('email_domain').value;
            const emailCustom = document.getElementById('email_custom').value.trim();
            const email = getCombinedEmail();
            const name = document.getElementById('name').value.trim();
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            
            if (!userId) {
                showAlert('아이디를 입력해주세요.').then(() => {
                    document.getElementById('user_id').focus();
                });
                isValid = false;
            } else if (!userIdChecked) {
                showAlert('아이디 중복확인을 해주세요.').then(() => {
                    document.getElementById('user_id').focus();
                });
                isValid = false;
            } else if (!userIdValid) {
                showAlert('사용 가능한 아이디를 입력해주세요.').then(() => {
                    document.getElementById('user_id').focus();
                });
                isValid = false;
            } else if (!emailLocal) {
                showAlert('이메일 아이디를 입력해주세요.').then(() => {
                    document.getElementById('email_local').focus();
                });
                isValid = false;
            } else if (!emailDomain) {
                showAlert('이메일 도메인을 선택해주세요.').then(() => {
                    document.getElementById('email_domain').focus();
                });
                isValid = false;
            } else if (emailDomain === 'custom' && !emailCustom) {
                showAlert('이메일 도메인을 입력해주세요.').then(() => {
                    document.getElementById('email_custom').focus();
                });
                isValid = false;
            } else if (!emailChecked) {
                showAlert('이메일 중복확인을 해주세요.').then(() => {
                    document.getElementById('email_local').focus();
                });
                isValid = false;
            } else if (!emailValid) {
                showAlert('사용 가능한 이메일을 입력해주세요.').then(() => {
                    document.getElementById('email_local').focus();
                });
                isValid = false;
            } else if (!name) {
                showAlert('이름을 입력해주세요.').then(() => {
                    document.getElementById('name').focus();
                });
                isValid = false;
            } else if (password.length < 8) {
                showAlert('비밀번호는 최소 8자 이상이어야 합니다.').then(() => {
                    document.getElementById('password').focus();
                });
                isValid = false;
            } else if (password !== passwordConfirm) {
                showAlert('비밀번호가 일치하지 않습니다.').then(() => {
                    document.getElementById('password_confirm').focus();
                });
                isValid = false;
            }
        } else if (step === 2) {
            const fileInput = document.getElementById('business_license_image');
            if (!fileInput.files || fileInput.files.length === 0) {
                showAlert('사업자등록증 이미지를 업로드해주세요.');
                isValid = false;
            }
        } else if (step === 3) {
            const businessNumber = document.getElementById('business_number').value.trim();
            const companyName = document.getElementById('company_name').value.trim();
            const companyRepresentative = document.getElementById('company_representative').value.trim();
            const businessType = document.getElementById('business_type').value.trim();
            const businessItem = document.getElementById('business_item').value.trim();
            
            if (!businessNumber) {
                showAlert('사업자등록번호를 입력해주세요.').then(() => {
                    document.getElementById('business_number').focus();
                });
                isValid = false;
            } else if (!companyName) {
                showAlert('회사명을 입력해주세요.').then(() => {
                    document.getElementById('company_name').focus();
                });
                isValid = false;
            } else if (companyName.length > 20) {
                showAlert('회사명은 20자 이내로 입력해주세요.').then(() => {
                    document.getElementById('company_name').focus();
                });
                isValid = false;
            } else if (companyRepresentative.length > 20) {
                showAlert('대표자명은 20자 이내로 입력해주세요.').then(() => {
                    document.getElementById('company_representative').focus();
                });
                isValid = false;
            } else if (businessType.length > 20) {
                showAlert('업종은 20자 이내로 입력해주세요.').then(() => {
                    document.getElementById('business_type').focus();
                });
                isValid = false;
            } else if (businessItem.length > 20) {
                showAlert('업태는 20자 이내로 입력해주세요.').then(() => {
                    document.getElementById('business_item').focus();
                });
                isValid = false;
            } else {
                // 휴대폰 검증 (입력된 경우에만)
                const mobile = document.getElementById('mobile').value.trim();
                if (mobile) {
                    const mobileNumbers = mobile.replace(/[^\d]/g, '');
                    if (!mobileNumbers.startsWith('010')) {
                        showAlert('휴대폰 번호는 010으로 시작해야 합니다.').then(() => {
                            document.getElementById('mobile').focus();
                        });
                        isValid = false;
                    } else if (mobileNumbers.length !== 11) {
                        showAlert('휴대폰 번호는 11자리여야 합니다.').then(() => {
                            document.getElementById('mobile').focus();
                        });
                        isValid = false;
                    }
                }
                
                // 전화번호 검증 (입력된 경우에만)
                const phone = document.getElementById('phone').value.trim();
                if (phone) {
                    const phoneNumbers = phone.replace(/[^\d]/g, '');
                    if (phoneNumbers.length < 9 || phoneNumbers.length > 11) {
                        showAlert('전화번호 형식이 올바르지 않습니다.').then(() => {
                            document.getElementById('phone').focus();
                        });
                        isValid = false;
                    }
                }
            }
        }
        
        return isValid;
    }
    
    // 초기화
    showStep(1);
    
    // 페이지 로드 시 이메일 도메인 선택 상태 확인
    const emailDomain = document.getElementById('email_domain');
    if (emailDomain.value === 'custom') {
        document.getElementById('email_custom').style.display = 'block';
        document.getElementById('email_custom').required = true;
    }
    
    // 이메일 필드 초기 업데이트
    updateEmailField();
    
    // 파일 업로드 미리보기 및 OCR 처리
    document.getElementById('business_license_image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const uploadArea = document.getElementById('fileUploadArea');
        const preview = document.getElementById('filePreview');
        const previewContent = document.getElementById('previewContent');
        
        if (file) {
            uploadArea.classList.add('has-file');
            preview.style.display = 'block';
            
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewContent.innerHTML = '<img src="' + e.target.result + '" alt="사업자등록증 미리보기">';
                };
                reader.readAsDataURL(file);
                
                // OCR 처리하여 정보 자동 입력
                extractBusinessInfo(file);
            } else {
                previewContent.innerHTML = '<div style="padding: 20px; text-align: center; color: #6b7280;"><strong>' + file.name + '</strong><br><span style="font-size: 12px;">PDF 파일</span></div>';
            }
        }
    });
    
    // 사업자등록증에서 정보 추출
    function extractBusinessInfo(file) {
        // 로딩 표시
        const previewContent = document.getElementById('previewContent');
        const loadingMsg = '<div style="padding: 20px; text-align: center; color: #6366f1;"><strong>정보 추출 중...</strong><br><span style="font-size: 12px;">잠시만 기다려주세요.</span></div>';
        previewContent.innerHTML = loadingMsg;
        
        const formData = new FormData();
        formData.append('image', file);
        
        fetch('/MVNO/api/extract-business-info.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('서버 응답 오류: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // 미리보기 이미지 다시 표시
            const reader = new FileReader();
            reader.onload = function(e) {
                previewContent.innerHTML = '<img src="' + e.target.result + '" alt="사업자등록증 미리보기">';
            };
            reader.readAsDataURL(file);
            
            if (data.success && data.data) {
                const info = data.data;
                let filledCount = 0;
                
                // 추출된 정보를 입력 필드에 자동 입력
                if (info.business_number && info.business_number.trim()) {
                    document.getElementById('business_number').value = formatBusinessNumber(info.business_number.trim());
                    filledCount++;
                }
                if (info.company_name && info.company_name.trim()) {
                    document.getElementById('company_name').value = info.company_name.trim();
                    filledCount++;
                }
                if (info.representative && info.representative.trim()) {
                    document.getElementById('company_representative').value = info.representative.trim();
                    filledCount++;
                }
                if (info.business_type && info.business_type.trim()) {
                    document.getElementById('business_type').value = info.business_type.trim();
                    filledCount++;
                }
                if (info.business_item && info.business_item.trim()) {
                    document.getElementById('business_item').value = info.business_item.trim();
                    filledCount++;
                }
                if (info.address && info.address.trim()) {
                    document.getElementById('address').value = info.address.trim();
                    filledCount++;
                }
                
                if (filledCount > 0) {
                    showAlert('정보가 자동으로 입력되었습니다. (' + filledCount + '개 필드) 확인 후 수정해주세요.');
                } else {
                    console.log('OCR 응답:', data);
                    // 정보가 추출되지 않았지만 성공 응답인 경우
                    if (data.message) {
                        console.log('OCR 메시지:', data.message);
                    }
                }
            } else {
                console.error('OCR 처리 실패:', data);
                // 미리보기 이미지는 이미 표시됨
            }
        })
        .catch(error => {
            console.error('OCR 처리 오류:', error);
            // 미리보기 이미지 다시 표시
            const reader = new FileReader();
            reader.onload = function(e) {
                previewContent.innerHTML = '<img src="' + e.target.result + '" alt="사업자등록증 미리보기">';
            };
            reader.readAsDataURL(file);
        });
    }
    
    function removeFile() {
        document.getElementById('business_license_image').value = '';
        document.getElementById('fileUploadArea').classList.remove('has-file');
        document.getElementById('filePreview').style.display = 'none';
        document.getElementById('previewContent').innerHTML = '';
    }
    
    // 비밀번호 확인
    document.getElementById('password_confirm').addEventListener('input', function() {
        const password = document.getElementById('password').value;
        const passwordConfirm = this.value;
        
        if (password !== passwordConfirm && passwordConfirm.length > 0) {
            this.setCustomValidity('비밀번호가 일치하지 않습니다.');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // 폼 제출 시 최종 검증
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        if (!validateStep(3)) {
            e.preventDefault();
            showStep(3);
        }
    });
    
    // 주소 검색 (다음 우편번호 API)
    function searchAddress() {
        new daum.Postcode({
            oncomplete: function(data) {
                // 주소 선택 시 실행되는 함수
                let addr = ''; // 주소 변수
                
                // 사용자가 선택한 주소 타입에 따라 해당 주소 값을 가져온다.
                if (data.userSelectedType === 'R') { // 사용자가 도로명 주소를 선택했을 경우
                    addr = data.roadAddress;
                } else { // 사용자가 지번 주소를 선택했을 경우(J)
                    addr = data.jibunAddress;
                }
                
                // 주소 필드에 값 설정
                document.getElementById('address').value = addr;
                // 커서를 상세주소 필드로 이동
                document.getElementById('address_detail').focus();
            }
        }).open();
    }
    
    // 사업자등록번호 하이픈 자동 입력 (123-45-67890)
    function formatBusinessNumber(value) {
        // 숫자만 추출
        const numbers = value.replace(/[^\d]/g, '');
        
        // 최대 10자리까지만 허용
        const limited = numbers.slice(0, 10);
        
        // 형식: 123-45-67890 (3-2-5)
        if (limited.length <= 3) {
            return limited;
        } else if (limited.length <= 5) {
            return limited.slice(0, 3) + '-' + limited.slice(3);
        } else {
            return limited.slice(0, 3) + '-' + limited.slice(3, 5) + '-' + limited.slice(5);
        }
    }
    
    // 전화번호 하이픈 자동 입력
    function formatPhoneNumber(value, isMobile = false) {
        // 숫자만 추출
        const numbers = value.replace(/[^\d]/g, '');
        
        if (isMobile) {
            // 휴대폰: 010-1234-5678 (3-4-4)
            const limited = numbers.slice(0, 11);
            if (limited.length <= 3) {
                return limited;
            } else if (limited.length <= 7) {
                return limited.slice(0, 3) + '-' + limited.slice(3);
            } else {
                return limited.slice(0, 3) + '-' + limited.slice(3, 7) + '-' + limited.slice(7);
            }
        } else {
            // 일반 전화번호 형식 처리
            const limited = numbers.slice(0, 12);
            
            // 전국대표번호 4자리 (1588, 1544, 1577, 1600 등)
            if (limited.startsWith('1588') || limited.startsWith('1544') || 
                limited.startsWith('1577') || limited.startsWith('1600') ||
                limited.startsWith('1800') || limited.startsWith('1566') ||
                limited.startsWith('1599') || limited.startsWith('1644')) {
                // 1588-1234 (4-4)
                if (limited.length <= 4) {
                    return limited;
                } else {
                    return limited.slice(0, 4) + '-' + limited.slice(4, 8);
                }
            }
            // 전국대표번호 3자리 (070, 080)
            else if (limited.startsWith('070') || limited.startsWith('080')) {
                // 070-1234-5678 (3-4-4)
                if (limited.length <= 3) {
                    return limited;
                } else if (limited.length <= 7) {
                    return limited.slice(0, 3) + '-' + limited.slice(3);
                } else {
                    return limited.slice(0, 3) + '-' + limited.slice(3, 7) + '-' + limited.slice(7);
                }
            }
            // 지역번호 2자리 (서울 02)
            else if (limited.startsWith('02')) {
                // 02-1234-5678 (2-4-4)
                if (limited.length <= 2) {
                    return limited;
                } else if (limited.length <= 6) {
                    return limited.slice(0, 2) + '-' + limited.slice(2);
                } else {
                    return limited.slice(0, 2) + '-' + limited.slice(2, 6) + '-' + limited.slice(6);
                }
            }
            // 지역번호 3자리 (031, 032, 033, 041, 042, 043, 044, 051, 052, 053, 054, 055, 061, 062, 063, 064)
            else if (limited.length >= 3) {
                // 031-123-4567 (3-3-4)
                if (limited.length <= 3) {
                    return limited;
                } else if (limited.length <= 6) {
                    return limited.slice(0, 3) + '-' + limited.slice(3);
                } else {
                    return limited.slice(0, 3) + '-' + limited.slice(3, 6) + '-' + limited.slice(6);
                }
            } else {
                return limited;
            }
        }
    }
    
    // 사업자등록번호 입력 필드에 이벤트 리스너 추가
    document.getElementById('business_number').addEventListener('input', function(e) {
        const cursorPosition = e.target.selectionStart;
        const oldValue = e.target.value;
        const newValue = formatBusinessNumber(e.target.value);
        
        e.target.value = newValue;
        
        // 커서 위치 조정
        const diff = newValue.length - oldValue.length;
        const newCursorPosition = cursorPosition + diff;
        e.target.setSelectionRange(newCursorPosition, newCursorPosition);
    });
    
    // 휴대폰 입력 필드에 이벤트 리스너 추가
    document.getElementById('mobile').addEventListener('input', function(e) {
        // 숫자만 추출
        const numbers = e.target.value.replace(/[^\d]/g, '');
        
        // 010으로 시작하지 않으면 입력 제한
        if (numbers.length > 0) {
            // 첫 번째 숫자가 0이 아니면 무시
            if (numbers.length === 1 && numbers[0] !== '0') {
                e.target.value = '';
                return;
            }
            // 두 번째 숫자가 1이 아니면 무시
            if (numbers.length === 2 && numbers[1] !== '1') {
                e.target.value = numbers[0];
                return;
            }
            // 세 번째 숫자가 0이 아니면 무시
            if (numbers.length === 3 && numbers[2] !== '0') {
                e.target.value = numbers.slice(0, 2);
                return;
            }
            // 010으로 시작하지 않는 경우 (4자리 이상에서 체크)
            if (numbers.length >= 4 && !numbers.startsWith('010')) {
                e.target.value = numbers.slice(0, 3);
                return;
            }
        }
        
        const cursorPosition = e.target.selectionStart;
        const oldValue = e.target.value;
        const newValue = formatPhoneNumber(e.target.value, true);
        
        e.target.value = newValue;
        
        // 커서 위치 조정
        const diff = newValue.length - oldValue.length;
        const newCursorPosition = cursorPosition + diff;
        e.target.setSelectionRange(newCursorPosition, newCursorPosition);
    });
    
    // 전화번호 입력 필드에 이벤트 리스너 추가
    document.getElementById('phone').addEventListener('input', function(e) {
        const cursorPosition = e.target.selectionStart;
        const oldValue = e.target.value;
        const newValue = formatPhoneNumber(e.target.value, false);
        
        e.target.value = newValue;
        
        // 커서 위치 조정
        const diff = newValue.length - oldValue.length;
        const newCursorPosition = cursorPosition + diff;
        e.target.setSelectionRange(newCursorPosition, newCursorPosition);
    });
</script>
<!-- 다음 우편번호 API 스크립트 -->
<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>

</body>
</html>


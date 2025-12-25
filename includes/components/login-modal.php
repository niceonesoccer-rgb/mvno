<?php
/**
 * 로그인 모달 컴포넌트
 */
require_once __DIR__ . '/../../includes/data/auth-functions.php';

// 로그인 상태 확인
$isLoggedIn = isLoggedIn();

// 관리자/판매자 페이지 확인
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
$isAdminPage = strpos($currentPath, '/admin/') !== false;
$isSellerPage = strpos($currentPath, '/seller/') !== false;
$hideSnsLogin = $isAdminPage || $isSellerPage;

$error = $_GET['error'] ?? '';
$errorMessages = [
    'invalid_request' => '잘못된 요청입니다.',
    'invalid_state' => '보안 검증에 실패했습니다.',
    'token_failed' => '인증 토큰을 받는데 실패했습니다.',
    'user_info_failed' => '사용자 정보를 가져오는데 실패했습니다.',
    'invalid_user' => '유효하지 않은 사용자입니다.',
    'invalid_provider' => '지원하지 않는 로그인 방식입니다.'
];
$errorMessage = $errorMessages[$error] ?? '';
// 기본적으로 로그인 모드 (URL 파라미터가 명시적으로 register=true일 때만 회원가입 모드)
$isRegisterMode = false; // 기본값은 로그인 모드
?>
<style>
.login-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    display: none;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.2s ease-out;
}

.login-modal.login-modal-active {
    display: flex;
}

.login-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.login-modal-content {
    position: relative;
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 420px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    animation: slideUp 0.3s ease-out;
    z-index: 10001;
}

.login-modal-header {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 16px 20px 0;
    position: sticky;
    top: 0;
    background: white;
    z-index: 1;
}

.login-modal-header:has(.login-modal-title) {
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
}

.login-modal-title {
    font-size: 22px;
    font-weight: 700;
    color: #111827;
    margin: 0;
    letter-spacing: -0.02em;
}

.login-modal-close {
    background: none;
    border: none;
    font-size: 28px;
    color: #9ca3af;
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

.login-modal-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.login-modal-body {
    padding: 40px 24px 28px;
    max-width: 420px;
    margin: 0 auto;
}

.login-benefits-section {
    margin-bottom: 0;
}

.login-benefits-title {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    text-align: center;
    margin-bottom: 28px;
    line-height: 1.4;
    letter-spacing: -0.02em;
}

.login-benefits-list {
    display: flex;
    flex-direction: column;
    gap: 24px;
    margin-bottom: 36px;
}

.login-benefit-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.login-benefit-icon {
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    transition: transform 0.2s;
}

.login-benefit-icon:hover {
    transform: scale(1.05);
}

.login-benefit-icon.shield {
    background: #d1fae5;
    color: #10b981;
}

.login-benefit-icon.search {
    background: #fef3c7;
    color: #f59e0b;
}

.login-benefit-icon.phone {
    background: #dbeafe;
    color: #3b82f6;
}

.login-benefit-text {
    flex: 1;
    font-size: 15px;
    color: #374151;
    line-height: 1.6;
    padding-top: 2px;
    font-weight: 400;
}

.login-primary-button {
    width: 100%;
    padding: 16px 20px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: none;
    margin-bottom: 16px;
}

.login-primary-button img {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
}

.login-primary-button.kakao {
    background: #fee500;
    color: #000000;
}

.login-primary-button.kakao:hover {
    background: #fdd835;
}

.login-primary-button.naver {
    background: #03A94D;
    color: white;
}

.login-primary-button.naver:hover {
    background: #028a3f;
}

.login-primary-button.google {
    background: white;
    color: #1f2937;
    border: 1px solid #e5e7eb;
}

.login-primary-button.google:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}

.login-browse-link {
    text-align: center;
    font-size: 14px;
    color: #6b7280;
    cursor: pointer;
    text-decoration: none;
    display: block;
    margin-top: 8px;
    transition: color 0.2s;
}

.login-browse-link:hover {
    color: #111827;
    text-decoration: underline;
}

.login-error-message {
    padding: 12px 16px;
    background: #fee2e2;
    color: #991b1b;
    border-radius: 8px;
    margin-bottom: 24px;
    font-size: 14px;
}

.login-sns-section {
    margin-bottom: 0;
}

.login-sns-title {
    font-size: 18px;
    font-weight: 600;
    color: #111827;
    margin-bottom: 20px;
    text-align: center;
    letter-spacing: -0.01em;
}

.login-sns-buttons {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.login-sns-buttons .login-primary-button {
    margin-bottom: 0;
}

.login-sns-button-svg {
    display: block;
    width: 100%;
    height: auto;
    object-fit: contain;
}

.login-primary-button-svg {
    display: block;
    width: 100%;
    height: auto;
    object-fit: contain;
}

.login-sns-button-img {
    display: block;
    width: 100%;
    height: auto;
    object-fit: contain;
    transition: all 0.2s ease;
    border-radius: 12px;
    cursor: pointer;
}

.login-sns-button-img:hover {
    opacity: 0.85;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.login-sns-button-img.google {
    border: 1px solid #e5e7eb;
    box-sizing: border-box;
}

.login-sns-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: white;
    font-size: 15px;
    font-weight: 500;
    color: #1f2937;
    cursor: pointer;
    transition: all 0.2s;
    width: 100%;
}

.login-sns-button img {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
}

.login-sns-button:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}

.login-sns-button.naver {
    background: #03A94D;
    color: white;
    border-color: #03A94D;
}

.login-sns-button.naver:hover {
    background: #028a3f;
}

.login-sns-button.kakao {
    background: #fee500;
    color: #000000;
    border-color: #fee500;
    border-radius: 12px;
}

.login-sns-button.kakao:hover {
    background: #fdd835;
}

.login-sns-button.google {
    background: white;
    color: #1f2937;
}

.login-divider {
    display: flex;
    align-items: center;
    margin: 28px 0;
    color: #9ca3af;
    font-size: 14px;
}

.login-divider::before,
.login-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e5e7eb;
}

.login-divider span {
    padding: 0 16px;
}

.login-form-section {
    margin-top: 24px;
}

.login-form-group {
    margin-bottom: 20px;
}

.login-form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.login-form-group {
    position: relative;
}

.login-form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.login-form-group input,
.login-form-group select {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 15px;
    transition: border-color 0.2s;
    box-sizing: border-box;
}

.login-form-group input:focus,
.login-form-group select:focus {
    outline: none;
    border-color: #6366f1;
}

.login-form-group.password-wrapper {
    position: relative;
}

.login-form-group.password-wrapper input {
    padding-right: 45px;
}

.password-toggle-btn {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    transition: color 0.2s;
    height: 20px;
    width: 20px;
    outline: none;
}

.password-toggle-btn:focus {
    outline: none;
    box-shadow: none;
}

.password-toggle-btn:focus-visible {
    outline: none;
    box-shadow: none;
}

.login-form-group.password-wrapper {
    display: flex;
    flex-direction: column;
}

.login-form-group.password-wrapper input {
    position: relative;
}

.login-form-group.password-wrapper .password-toggle-btn {
    position: absolute;
    right: 12px;
    top: calc(20px + 8px + 12px + 11.5px); /* label height + margin-bottom + input padding-top + half input line-height */
    transform: translateY(-50%);
    margin-top: 0;
}

.password-toggle-btn:hover {
    color: #374151;
}

.password-toggle-btn svg {
    width: 20px;
    height: 20px;
}

.login-form-group input:focus {
    outline: none;
    border-color: #6366f1;
}

.login-form-group input.checked-valid {
    border-color: #10b981;
}

.login-form-group input.checked-invalid {
    border-color: #ef4444;
}

.login-form-group input.checking {
    border-color: #6366f1;
}

.user-id-check-result {
    margin-top: 6px;
    font-size: 13px;
    min-height: 18px;
}

.user-id-check-result.success {
    color: #10b981;
}

.user-id-check-result.error {
    color: #ef4444;
}

.user-id-check-result.checking {
    color: #6b7280;
}

.login-form-button {
    width: 100%;
    padding: 14px;
    background: #6366f1;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.login-form-button:hover {
    background: #4f46e5;
}

.login-form-switch {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
    color: #6b7280;
}

.login-form-switch a {
    color: #6366f1;
    text-decoration: none;
    font-weight: 500;
    margin-left: 4px;
}

.login-form-switch a:hover {
    text-decoration: underline;
}

.login-register-link {
    text-align: center;
    margin-top: 24px;
    font-size: 14px;
    color: #6b7280;
}

.login-register-link a {
    color: #6366f1;
    text-decoration: none;
    font-weight: 500;
}

.login-register-link a:hover {
    text-decoration: underline;
}

/* 회원가입 완료 안내 모달 */
.register-success-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 11000;
    display: none;
    align-items: center;
    justify-content: center;
}

.register-success-modal.active {
    display: flex;
}

.register-success-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(3px);
}

.register-success-content {
    position: relative;
    z-index: 11001;
    width: 90%;
    max-width: 360px;
    background: #ffffff;
    border-radius: 14px;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,.18), 0 10px 10px -5px rgba(0,0,0,.08);
    padding: 20px 18px 16px;
    animation: slideUp 0.2s ease-out;
}

.register-success-title {
    margin: 0 0 10px;
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    letter-spacing: -0.01em;
}

.register-success-message {
    margin: 0 0 16px;
    font-size: 14px;
    color: #374151;
    line-height: 1.6;
}

.register-success-actions {
    display: flex;
    justify-content: flex-end;
}

.register-success-ok {
    border: none;
    background: #6366f1;
    color: #fff;
    font-weight: 600;
    padding: 10px 14px;
    border-radius: 10px;
    cursor: pointer;
}

.register-success-ok:hover {
    background: #4f46e5;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@media (max-width: 640px) {
    .login-modal-content {
        width: 100%;
        max-width: 100%;
        max-height: 100vh;
        height: 100vh;
        margin: 0;
        border-radius: 0;
        box-shadow: none;
    }
    
    .login-modal-header {
        padding: 16px 20px;
    }
    
    .login-modal-body {
        padding: 32px 20px 24px;
        max-width: 100%;
    }
    
    .login-benefits-title {
        font-size: 18px;
        margin-bottom: 20px;
    }
    
    .login-sns-title {
        font-size: 16px;
        margin-bottom: 16px;
    }
    
    .login-benefits-list {
        gap: 20px;
        margin-bottom: 28px;
    }
    
    .login-benefit-icon {
        width: 40px;
        height: 40px;
    }
    
    .login-benefit-text {
        font-size: 14px;
    }
    
    .login-sns-buttons {
        gap: 12px;
    }
}
</style>

<div id="loginModal" class="login-modal">
    <div class="login-modal-overlay"></div>
    <div class="login-modal-content">
        <div class="login-modal-header">
            <h3 class="login-modal-title" id="loginModalTitle"><?php echo $isRegisterMode ? '회원가입' : '로그인'; ?></h3>
            <button class="login-modal-close" id="loginModalClose">&times;</button>
        </div>
        <div class="login-modal-body">
            <?php if ($errorMessage): ?>
                <div class="login-error-message">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($hideSnsLogin): ?>
                <!-- 관리자/판매자 페이지: SNS 로그인 숨김 -->
                <div style="text-align: center; padding: 40px 20px; color: #6b7280;">
                    <p style="font-size: 16px; margin-bottom: 8px;">관리자 및 판매자는</p>
                    <p style="font-size: 14px;">아이디와 비밀번호로 로그인해주세요.</p>
                </div>
            <?php else: ?>
                <!-- 로그인 모드 (기본) -->
                <div id="loginModeContent" style="display: <?php echo $isRegisterMode ? 'none' : 'block'; ?>;">
                <!-- 로그인 모드: SNS 로그인 + 일반 로그인 -->
                <div class="login-sns-section">
                    <!-- SNS 로그인 버튼 -->
                    <div class="login-sns-buttons" style="margin-bottom: 32px;">
                        <img src="/MVNO/assets/images/logo/button-kakao-login.png" alt="카카오톡 로그인" class="login-sns-button-img" onclick="snsLoginModal('kakao')">
                        <img src="/MVNO/assets/images/logo/button-naver-login.png" alt="네이버로 로그인" class="login-sns-button-img" onclick="snsLoginModal('naver')">
                        <img src="/MVNO/assets/images/logo/button-google-login.png" alt="구글 로그인" class="login-sns-button-img google" onclick="snsLoginModal('google')">
                    </div>
                    
                    <!-- 일반 로그인 폼 -->
                    <div class="login-form-section">
                        <form id="loginForm" method="POST" action="/MVNO/api/direct-login.php">
                            <div class="login-form-group">
                                <input type="text" id="login_user_id" name="user_id" placeholder="아이디" required>
                            </div>
                            <div class="login-form-group">
                                <input type="password" id="login_password" name="password" placeholder="비밀번호" required>
                            </div>
                            <button type="submit" class="login-form-button">로그인</button>
                        </form>
                        <div class="login-form-switch">
                            아직 회원이 아니신가요? <a href="#" onclick="switchToRegisterMode(); return false;">회원가입</a>
                        </div>
                    </div>
                </div>
                </div>
                
                <!-- 회원가입 모드 -->
                <div id="registerModeContent" style="display: <?php echo $isRegisterMode ? 'block' : 'none'; ?>;">
                <div class="login-benefits-section">
                    <h3 class="login-benefits-title">SNS로 회원가입</h3>
                    
                    <div class="login-sns-buttons" style="margin-top: 24px;">
                        <img src="/MVNO/assets/images/logo/button-kakao-login.png" alt="카카오톡 로그인" class="login-sns-button-img" onclick="snsLoginModal('kakao')">
                        <img src="/MVNO/assets/images/logo/button-naver-login.png" alt="네이버로 로그인" class="login-sns-button-img" onclick="snsLoginModal('naver')">
                        <img src="/MVNO/assets/images/logo/button-google-login.png" alt="구글 로그인" class="login-sns-button-img google" onclick="snsLoginModal('google')">
                    </div>
                    
                    <div class="login-divider">
                        <span>또는</span>
                    </div>
                    
                    <!-- 일반 회원가입 폼 -->
                    <div class="login-form-section">
                        <form id="registerForm" method="POST" action="/MVNO/api/direct-register.php">
                            <input type="hidden" name="role" value="user">
                            <input type="hidden" id="register_user_id_checked" name="user_id_checked" value="0">
                            <div class="login-form-group">
                                <label for="register_user_id">아이디 <span style="color: #ef4444;">*</span></label>
                                <input type="text" id="register_user_id" name="user_id" required placeholder="아이디 입력 (영문 소문자, 숫자만 가능, 5-20자)" pattern="[a-z0-9]{5,20}" title="영문 소문자와 숫자만 입력 가능하며 5-20자입니다" minlength="5" maxlength="20" autocomplete="username" autocapitalize="none" inputmode="text">
                                <div id="userIdCheckResult" style="margin-top: 6px; font-size: 13px; min-height: 18px;"></div>
                            </div>
                            <div class="login-form-group">
                                <label for="register_name">이름 <span style="color: #ef4444;">*</span></label>
                                <input type="text" id="register_name" name="name" required placeholder="이름 입력 (최대 15자)" maxlength="15">
                            </div>
                            <div class="login-form-group">
                                <label for="register_phone">휴대폰번호 <span style="color: #ef4444;">*</span></label>
                                <input type="tel" id="register_phone" name="phone" required placeholder="010-1234-5678" pattern="^010-\d{4}-\d{4}$" maxlength="13" title="010으로 시작하는 번호만 가능합니다">
                            </div>
                            <div class="login-form-group">
                                <label for="register_email">이메일 <span style="color: #ef4444;">*</span></label>
                                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                    <input type="text" id="register_email_local" name="email_local" required placeholder="이메일 아이디 (영문 소문자, 숫자만)" maxlength="50" pattern="[a-z0-9]+" title="영문 소문자와 숫자만 입력 가능합니다" style="flex: 1; min-width: 120px;">
                                    <span style="line-height: 44px; flex-shrink: 0;">@</span>
                                    <select id="register_email_domain" name="email_domain" required style="flex: 1; min-width: 140px;">
                                        <option value="">도메인 선택</option>
                                        <option value="naver.com">naver.com</option>
                                        <option value="gmail.com">gmail.com</option>
                                        <option value="hanmail.net">hanmail.net</option>
                                        <option value="nate.com">nate.com</option>
                                        <option value="custom">직접입력</option>
                                    </select>
                                </div>
                                <input type="text" id="register_email_custom" name="email_custom" placeholder="직접입력 도메인 (naver.com, gmail.com, hanmail.net, nate.com 중 하나)" maxlength="50" style="display: none; margin-top: 8px; width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px; box-sizing: border-box;">
                                <div id="emailCustomCheckResult" style="display: none; margin-top: 6px; font-size: 13px; min-height: 18px;"></div>
                                <div id="emailCheckResult" style="margin-top: 6px; font-size: 13px; min-height: 18px;"></div>
                                <input type="hidden" id="register_email" name="email">
                            </div>
                            <div class="login-form-group password-wrapper">
                                <label for="register_password">비밀번호</label>
                                <input type="password" id="register_password" name="password" required minlength="8" maxlength="20" placeholder="비밀번호 입력 (영문자, 숫자, 특수문자 중 2가지 이상 조합 8-20자)">
                                <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('register_password', this)" aria-label="비밀번호 표시/숨김">
                                    <svg id="register_password_eye" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                            <div class="login-form-group password-wrapper">
                                <label for="register_password_confirm">비밀번호 확인</label>
                                <input type="password" id="register_password_confirm" name="password_confirm" required minlength="8" maxlength="20" placeholder="비밀번호 확인">
                                <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('register_password_confirm', this)" aria-label="비밀번호 표시/숨김">
                                    <svg id="register_password_confirm_eye" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                                <div id="passwordConfirmCheckResult" style="margin-top: 6px; font-size: 13px; min-height: 18px;"></div>
                            </div>
                            <button type="submit" class="login-form-button">회원가입</button>
                        </form>
                        <div class="login-form-switch">
                            이미 계정이 있으신가요? <a href="#" onclick="switchToLoginMode(); return false;">로그인</a>
                        </div>
                    </div>
                    
                    <a href="#" class="login-browse-link" onclick="closeLoginModal(); return false;">일단 둘러볼게요</a>
                </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 회원가입 완료 안내 모달 -->
<div id="registerSuccessModal" class="register-success-modal" aria-hidden="true">
    <div class="register-success-overlay" onclick="closeRegisterSuccessModal()"></div>
    <div class="register-success-content" role="dialog" aria-modal="true" aria-labelledby="registerSuccessTitle">
        <h4 id="registerSuccessTitle" class="register-success-title">회원가입 완료</h4>
        <p id="registerSuccessMessage" class="register-success-message">회원가입이 완료되었습니다. 로그인해주세요.</p>
        <div class="register-success-actions">
            <button type="button" class="register-success-ok" onclick="closeRegisterSuccessModal()">확인</button>
        </div>
    </div>
</div>

<script>
// 로그인 상태 확인 (PHP에서 전달)
const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

function openRegisterSuccessModal(message) {
    const modal = document.getElementById('registerSuccessModal');
    const msgEl = document.getElementById('registerSuccessMessage');
    if (msgEl) msgEl.textContent = message || '회원가입이 완료되었습니다. 로그인해주세요.';
    if (modal) {
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
    }
}

function closeRegisterSuccessModal() {
    const modal = document.getElementById('registerSuccessModal');
    if (modal) {
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
    }
    // 확인 후 로그인 모드로 전환 (모달은 열린 채로 로그인 화면 표시)
    if (typeof switchToLoginMode === 'function') {
        switchToLoginMode();
    }
}

// 로그인 모달 열기/닫기
function openLoginModal(isRegister = false) {
    // 로그인되어 있으면 모달을 열지 않음
    if (isLoggedIn) {
        return;
    }
    
    // 현재 URL을 세션에 저장 (로그인/회원가입 후 돌아올 페이지)
    const currentUrl = window.location.href;
    fetch('/MVNO/api/save-redirect-url.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ redirect_url: currentUrl })
    }).catch(error => {
        console.error('Failed to save redirect URL:', error);
    });
    
    const modal = document.getElementById('loginModal');
    const title = document.getElementById('loginModalTitle');
    const loginContent = document.getElementById('loginModeContent');
    const registerContent = document.getElementById('registerModeContent');
    
    // 항상 로그인 모드로 초기화 (명시적으로 회원가입 모드를 요청한 경우만 회원가입 모드)
    if (isRegister === true) {
        title.textContent = '회원가입';
        if (loginContent) loginContent.style.display = 'none';
        if (registerContent) registerContent.style.display = 'block';
    } else {
        // 기본값은 항상 로그인 모드
        title.textContent = '로그인';
        if (loginContent) loginContent.style.display = 'block';
        if (registerContent) registerContent.style.display = 'none';
    }
    
    modal.classList.add('login-modal-active');
    // 배경 고정
    const scrollY = window.scrollY;
    document.body.style.overflow = 'hidden';
    document.body.style.position = 'fixed';
    document.body.style.top = `-${scrollY}px`;
    document.body.style.width = '100%';
    document.body.style.left = '0';
    document.body.style.right = '0';
    document.documentElement.style.overflow = 'hidden';
    window.scrollPosition = scrollY;
}

function closeLoginModal() {
    const modal = document.getElementById('loginModal');
    modal.classList.remove('login-modal-active');
    
    // 배경 고정 해제
    const scrollY = window.scrollPosition || parseInt(document.body.style.top || '0') * -1;
    document.body.style.overflow = '';
    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.width = '';
    document.body.style.left = '';
    document.body.style.right = '';
    document.documentElement.style.overflow = '';
    if (scrollY) {
        window.scrollTo(0, scrollY);
    }
}

// SNS 로그인
function snsLoginModal(provider) {
    // 현재 URL을 세션에 저장 (로그인 후 돌아올 페이지)
    const currentUrl = window.location.href;
    fetch('/MVNO/api/save-redirect-url.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ redirect_url: currentUrl })
    }).then(() => {
        // URL 저장 후 SNS 로그인 진행
        return fetch(`/MVNO/api/sns-login.php?action=${provider}`);
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // redirect가 있으면 바로 이동 (테스트용 자동 로그인)
            if (data.redirect) {
                window.location.href = data.redirect;
            } else if (data.auth_url) {
                // auth_url이 있으면 OAuth 인증 페이지로 이동
                window.location.href = data.auth_url;
            }
        } else {
            alert(data.message || '로그인에 실패했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('로그인 중 오류가 발생했습니다.');
    });
}

// 모달 초기화 및 폼 처리
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('loginModal');
    const closeBtn = document.getElementById('loginModalClose');
    const overlay = modal ? modal.querySelector('.login-modal-overlay') : null;
    
    // 닫기 버튼
    if (closeBtn) {
        closeBtn.addEventListener('click', closeLoginModal);
    }
    
    // 오버레이 클릭 시 닫기
    if (overlay) {
        overlay.addEventListener('click', closeLoginModal);
    }
    
    // 로그인 폼 제출 처리
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('/MVNO/api/direct-login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect || '/MVNO/';
                } else {
                    alert(data.message || '로그인에 실패했습니다.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('로그인 중 오류가 발생했습니다.');
            });
        });
    }
    
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // 필수 필드 검증
            const userId = document.getElementById('register_user_id').value.trim();
            const phone = document.getElementById('register_phone').value.trim();
            const name = document.getElementById('register_name').value.trim();
            const password = document.getElementById('register_password').value;
            const passwordConfirm = document.getElementById('register_password_confirm').value;
            
            if (!userId || !phone || !name) {
                alert('아이디, 휴대폰번호, 이름은 필수 입력 항목입니다.');
                return;
            }
            
            // 아이디 검증: 영문 소문자, 숫자만 가능, 5-20자
            const userIdPattern = /^[a-z0-9]{5,20}$/;
            if (!userIdPattern.test(userId)) {
                alert('아이디는 영문 소문자와 숫자만 사용할 수 있으며 5자 이상 20자 이내여야 합니다.');
                document.getElementById('register_user_id').focus();
                return;
            }
            
            // 아이디 중복 확인 상태 체크 (실시간 확인이 완료되었는지)
            const userIdInput = document.getElementById('register_user_id');
            if (userIdInput && !userIdInput.classList.contains('checked-valid')) {
                alert('아이디 중복 확인을 완료해주세요.');
                document.getElementById('register_user_id').focus();
                return;
            }

            // 서버 검증용 hidden 플래그 (checked-valid이면 1로 설정)
            const userIdCheckedHidden = document.getElementById('register_user_id_checked');
            if (userIdCheckedHidden) {
                userIdCheckedHidden.value = userIdInput.classList.contains('checked-valid') ? '1' : '0';
            }
            
            // 이름 길이 검증 (15자 이내)
            if (name.length > 15) {
                alert('이름은 15자 이내로 입력해주세요.');
                document.getElementById('register_name').focus();
                return;
            }
            
            // 전화번호 형식 검증 (010으로만 시작)
            const phonePattern = /^010-\d{4}-\d{4}$/;
            if (!phonePattern.test(phone)) {
                alert('휴대폰번호는 010으로 시작하는 번호만 가능합니다. (010-XXXX-XXXX 형식)');
                document.getElementById('register_phone').focus();
                return;
            }
            
            // 이메일 조합 및 검증
            const emailLocal = document.getElementById('register_email_local').value.trim();
            const emailDomain = document.getElementById('register_email_domain').value;
            const emailCustom = document.getElementById('register_email_custom').value.trim();
            
            if (!emailLocal) {
                alert('이메일 아이디를 입력해주세요.');
                document.getElementById('register_email_local').focus();
                return;
            }
            
            // 영문 소문자와 숫자만 허용
            if (!/^[a-z0-9]+$/.test(emailLocal)) {
                alert('이메일 아이디는 영문 소문자와 숫자만 사용할 수 있습니다.');
                document.getElementById('register_email_local').focus();
                return;
            }
            
            if (emailLocal.length > 50) {
                alert('이메일 아이디는 50자 이내로 입력해주세요.');
                document.getElementById('register_email_local').focus();
                return;
            }
            
            let finalEmail = '';
            if (emailDomain === 'custom') {
                if (!emailCustom) {
                    alert('직접입력 도메인을 입력해주세요.');
                    document.getElementById('register_email_custom').focus();
                    return;
                }
                finalEmail = emailLocal + '@' + emailCustom;
            } else {
                if (!emailDomain) {
                    alert('이메일 도메인을 선택해주세요.');
                    document.getElementById('register_email_domain').focus();
                    return;
                }
                finalEmail = emailLocal + '@' + emailDomain;
            }
            
            // 전체 이메일 주소 길이 검증 (50자 이내)
            if (finalEmail.length > 50) {
                alert('이메일 주소는 50자 이내로 입력해주세요.');
                return;
            }
            
            // 허용된 도메인 검증 (드롭다운 선택 시에만 적용, 직접입력은 형식만 검증)
            if (emailDomain !== 'custom') {
                const allowedDomains = ['naver.com', 'gmail.com', 'hanmail.net', 'nate.com'];
                const emailDomainPart = finalEmail.split('@')[1].toLowerCase();
                
                if (!allowedDomains.includes(emailDomainPart)) {
                    alert('허용된 이메일 도메인만 사용할 수 있습니다. (naver.com, gmail.com, hanmail.net, nate.com)');
                    document.getElementById('register_email_domain').focus();
                    return;
                }
            }
            
            // 이메일을 hidden 필드에 설정
            document.getElementById('register_email').value = finalEmail;
            
            // 비밀번호 검증: 영문자, 숫자, 특수문자 중 2가지 이상 조합, 8-20자
            if (password.length < 8 || password.length > 20) {
                alert('비밀번호는 8자 이상 20자 이내로 입력해주세요.');
                document.getElementById('register_password').focus();
                return;
            }
            
            // 영문자(대소문자 구분 없이), 숫자, 특수문자(@#$%^&*!?_-=) 중 2가지 이상 조합 확인
            const hasLetter = /[A-Za-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecialChar = /[@#$%^&*!?_\-=]/.test(password);
            
            // 2가지 이상 조합인지 확인
            const combinationCount = (hasLetter ? 1 : 0) + (hasNumber ? 1 : 0) + (hasSpecialChar ? 1 : 0);
            
            if (combinationCount < 2) {
                alert('비밀번호는 영문자, 숫자, 특수문자(@#$%^&*!?_-=) 중 2가지 이상 조합해야 합니다.');
                document.getElementById('register_password').focus();
                return;
            }
            
            if (password !== passwordConfirm) {
                alert('비밀번호가 일치하지 않습니다.');
                return;
            }
            
            // AJAX로 회원가입 (모달에서 끝내기)
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const prevText = submitBtn ? submitBtn.textContent : null;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = '처리중...';
            }

            fetch('/MVNO/api/direct-register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data && data.success) {
                    openRegisterSuccessModal(data.message || '회원가입이 완료되었습니다. 로그인해주세요.');
                    // 가입 완료 후 로그인 모드로 전환 + 입력 초기화
                    this.reset();
                    const userIdCheckedHidden2 = document.getElementById('register_user_id_checked');
                    if (userIdCheckedHidden2) userIdCheckedHidden2.value = '0';
                    const userIdInput2 = document.getElementById('register_user_id');
                    if (userIdInput2) userIdInput2.classList.remove('checked-valid', 'checked-invalid', 'checking');
                    const userIdCheckResult2 = document.getElementById('userIdCheckResult');
                    if (userIdCheckResult2) {
                        userIdCheckResult2.textContent = '';
                        userIdCheckResult2.className = 'user-id-check-result';
                    }
                } else {
                    alert((data && data.message) ? data.message : '회원가입에 실패했습니다.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('회원가입 중 오류가 발생했습니다.');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = prevText || '회원가입';
                }
            });
        });
        
        // 전화번호 자동 포맷팅 (010-XXXX-XXXX, 010으로만 시작)
        const phoneInput = document.getElementById('register_phone');
        if (phoneInput) {
            let previousValue = '';
            
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/[^\d]/g, '');
                
                // 입력된 숫자 길이에 따라 검증
                let isValid = false;
                if (value.length === 0) {
                    isValid = true; // 빈 값은 허용
                } else if (value.length === 1) {
                    isValid = value === '0'; // 첫 번째는 0만 허용
                } else if (value.length === 2) {
                    isValid = value === '01'; // 두 번째는 1만 허용
                } else if (value.length >= 3) {
                    isValid = value.startsWith('010'); // 세 번째부터는 010으로 시작해야 함
                }
                
                // 유효하지 않으면 이전 값으로 되돌림
                if (!isValid) {
                    e.target.value = previousValue;
                    return;
                }
                
                // 최대 11자리 제한
                if (value.length > 11) {
                    value = value.substring(0, 11);
                }
                
                // 포맷팅 (010-XXXX-XXXX)
                if (value.length > 7) {
                    value = value.substring(0, 3) + '-' + value.substring(3, 7) + '-' + value.substring(7);
                } else if (value.length > 3) {
                    value = value.substring(0, 3) + '-' + value.substring(3);
                }
                
                e.target.value = value;
                previousValue = value;
            });
            
            // 포커스 아웃 시 010으로 시작하는지 확인
            phoneInput.addEventListener('blur', function(e) {
                const value = e.target.value.replace(/[^\d]/g, '');
                if (value.length > 0 && !value.startsWith('010')) {
                    alert('휴대폰번호는 010으로 시작하는 번호만 가능합니다.');
                    e.target.value = '';
                }
            });
        }
        
        // 이메일 아이디 입력 필터링 (영문 소문자, 숫자만)
        const emailLocalInput = document.getElementById('register_email_local');
        const emailCheckResult = document.getElementById('emailCheckResult');
        if (emailLocalInput && emailCheckResult) {
            let previousValue = '';
            
            emailLocalInput.addEventListener('input', function(e) {
                let value = e.target.value;
                const originalValue = value;
                
                // 영문 소문자와 숫자만 허용
                value = value.replace(/[^a-z0-9]/g, '');
                
                // 한글이나 다른 문자가 제거되었는지 확인
                if (originalValue !== value) {
                    // 한글이나 특수문자 입력 감지
                    emailCheckResult.textContent = '이메일 주소는 영문자와 숫자만 입력 가능합니다.';
                    emailCheckResult.className = 'user-id-check-result error';
                    emailCheckResult.style.color = '#ef4444';
                } else {
                    // 정상 입력(영문자, 숫자만)이거나 빈 값이면 메시지 제거
                    emailCheckResult.textContent = '';
                    emailCheckResult.className = '';
                }
                
                e.target.value = value;
                previousValue = value;
            });
            
            // 대문자 입력 시 자동으로 소문자로 변환
            emailLocalInput.addEventListener('keypress', function(e) {
                const char = String.fromCharCode(e.which);
                if (/[A-Z]/.test(char)) {
                    e.preventDefault();
                    const start = e.target.selectionStart;
                    const end = e.target.selectionEnd;
                    const value = e.target.value;
                    e.target.value = value.substring(0, start) + char.toLowerCase() + value.substring(end);
                    e.target.setSelectionRange(start + 1, start + 1);
                }
            });
        }
        
        // 이메일 도메인 선택 처리
        const emailDomainSelect = document.getElementById('register_email_domain');
        const emailCustomInput = document.getElementById('register_email_custom');
        const emailCustomCheckResult = document.getElementById('emailCustomCheckResult');
        if (emailDomainSelect && emailCustomInput && emailCustomCheckResult) {
            emailDomainSelect.addEventListener('change', function() {
                if (this.value === 'custom') {
                    emailCustomInput.style.display = 'block';
                    emailCustomInput.required = true;
                    emailCustomCheckResult.style.display = 'block';
                } else {
                    emailCustomInput.style.display = 'none';
                    emailCustomInput.required = false;
                    emailCustomInput.value = '';
                    emailCustomCheckResult.style.display = 'none';
                    emailCustomCheckResult.textContent = '';
                    emailCustomCheckResult.className = '';
                }
            });
            
            // 직접입력 도메인 입력 필터링 (입력 중에는 필터링만)
            emailCustomInput.addEventListener('input', function(e) {
                let domain = e.target.value;
                const originalValue = domain;
                
                // 영문 소문자와 숫자, 점만 허용 (대문자는 소문자로 변환, 한글/특수문자 제거)
                domain = domain.toLowerCase().replace(/[^a-z0-9.]/g, '');
                
                // 입력 필터링 적용
                if (originalValue !== domain) {
                    e.target.value = domain;
                }
                
                // 입력 중에는 메시지 숨김
                emailCustomCheckResult.textContent = '';
                emailCustomCheckResult.className = '';
            });
            
            // 직접입력 도메인 포커스 이동 시 검증
            emailCustomInput.addEventListener('blur', function(e) {
                let domain = e.target.value.trim();
                
                if (domain.length === 0) {
                    emailCustomCheckResult.textContent = '';
                    emailCustomCheckResult.className = '';
                    return;
                }
                
                // 연속된 점 체크
                if (/\.{2,}/.test(domain)) {
                    emailCustomCheckResult.textContent = '이메일 형식에 맞게 입력해주세요.';
                    emailCustomCheckResult.className = 'user-id-check-result error';
                    emailCustomCheckResult.style.color = '#ef4444';
                    e.target.focus();
                    return;
                }
                
                // 점으로 시작하거나 끝나면 안됨
                if (domain.startsWith('.') || domain.endsWith('.')) {
                    emailCustomCheckResult.textContent = '이메일 형식에 맞게 입력해주세요.';
                    emailCustomCheckResult.className = 'user-id-check-result error';
                    emailCustomCheckResult.style.color = '#ef4444';
                    e.target.focus();
                    return;
                }
                
                // 최소한 하나의 점이 있어야 함 (예: naver.com)
                if (!domain.includes('.')) {
                    emailCustomCheckResult.textContent = '이메일 형식에 맞게 입력해주세요.';
                    emailCustomCheckResult.className = 'user-id-check-result error';
                    emailCustomCheckResult.style.color = '#ef4444';
                    e.target.focus();
                    return;
                }
                
                // 점(.) 앞에 영문자/숫자가 있어야 함
                const parts = domain.split('.');
                let isValidFormat = true;
                for (let i = 0; i < parts.length; i++) {
                    if (parts[i].length === 0) {
                        isValidFormat = false;
                        break;
                    }
                    // 각 부분이 영문자/숫자로 시작해야 함
                    if (!/^[a-z0-9]/.test(parts[i])) {
                        isValidFormat = false;
                        break;
                    }
                }
                
                if (!isValidFormat) {
                    emailCustomCheckResult.textContent = '이메일 형식에 맞게 입력해주세요.';
                    emailCustomCheckResult.className = 'user-id-check-result error';
                    emailCustomCheckResult.style.color = '#ef4444';
                    e.target.focus();
                    return;
                }
                
                // 형식 검증 통과 시 성공 메시지 (도메인 제한 없음)
                emailCustomCheckResult.textContent = '✓ 사용 가능한 도메인입니다.';
                emailCustomCheckResult.className = 'user-id-check-result success';
                emailCustomCheckResult.style.color = '#10b981';
            });
        }
        
        // 아이디 실시간 중복 확인
        const userIdInput = document.getElementById('register_user_id');
        const userIdCheckResult = document.getElementById('userIdCheckResult');
        const userIdCheckedHidden = document.getElementById('register_user_id_checked');
        let userIdCheckTimeout = null;
        
        if (userIdInput && userIdCheckResult) {
            userIdInput.addEventListener('input', function(e) {
                // 대문자 입력 시 소문자로 강제 변환 + 영문 소문자/숫자만 허용
                const normalized = e.target.value.toLowerCase().replace(/[^a-z0-9]/g, '');
                if (e.target.value !== normalized) {
                    e.target.value = normalized;
                }

                const userId = e.target.value.trim();
                
                // 입력 필드 클래스 초기화
                userIdInput.classList.remove('checked-valid', 'checked-invalid', 'checking');
                if (userIdCheckedHidden) userIdCheckedHidden.value = '0';
                userIdCheckResult.className = 'user-id-check-result';
                userIdCheckResult.textContent = '';
                
                // 기존 timeout 취소
                if (userIdCheckTimeout) {
                    clearTimeout(userIdCheckTimeout);
                }
                
                // 길이 체크 (5-20자)
                if (userId.length < 5 || userId.length > 20) {
                    if (userId.length > 0) {
                        userIdCheckResult.textContent = '아이디는 5자 이상 20자 이내로 입력해주세요.';
                        userIdCheckResult.className = 'user-id-check-result error';
                        userIdInput.classList.add('checked-invalid');
                    }
                    return;
                }
                
                // 영문 소문자, 숫자만 허용
                if (!/^[a-z0-9]+$/.test(userId)) {
                    userIdCheckResult.textContent = '아이디는 영문 소문자와 숫자만 사용할 수 있습니다.';
                    userIdCheckResult.className = 'user-id-check-result error';
                    userIdInput.classList.add('checked-invalid');
                    return;
                }
                
                // 500ms 후 서버에 중복 확인 요청 (debounce)
                userIdInput.classList.add('checking');
                userIdCheckResult.textContent = '확인 중...';
                userIdCheckResult.className = 'user-id-check-result checking';
                
                userIdCheckTimeout = setTimeout(function() {
                    fetch(`/MVNO/api/check-user-duplicate.php?type=user_id&value=${encodeURIComponent(userId)}`)
                        .then(response => response.json())
                        .then(data => {
                            userIdInput.classList.remove('checking');
                            
                            if (data.available) {
                                // 사용 가능
                                userIdCheckResult.textContent = '✓ ' + data.message;
                                userIdCheckResult.className = 'user-id-check-result success';
                                userIdInput.classList.remove('checked-invalid');
                                userIdInput.classList.add('checked-valid');
                                if (userIdCheckedHidden) userIdCheckedHidden.value = '1';
                            } else {
                                // 사용 불가
                                userIdCheckResult.textContent = '✗ ' + data.message;
                                userIdCheckResult.className = 'user-id-check-result error';
                                userIdInput.classList.remove('checked-valid');
                                userIdInput.classList.add('checked-invalid');
                                if (userIdCheckedHidden) userIdCheckedHidden.value = '0';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            userIdInput.classList.remove('checking');
                            userIdCheckResult.textContent = '확인 중 오류가 발생했습니다.';
                            userIdCheckResult.className = 'user-id-check-result error';
                            userIdInput.classList.add('checked-invalid');
                            if (userIdCheckedHidden) userIdCheckedHidden.value = '0';
                        });
                }, 500);
            });
        }
        
        // 비밀번호 확인 실시간 검증
        const passwordInput = document.getElementById('register_password');
        const passwordConfirmInput = document.getElementById('register_password_confirm');
        const passwordConfirmCheckResult = document.getElementById('passwordConfirmCheckResult');
        
        if (passwordInput && passwordConfirmInput && passwordConfirmCheckResult) {
            function checkPasswordMatch() {
                const password = passwordInput.value;
                const passwordConfirm = passwordConfirmInput.value;
                
                if (passwordConfirm.length === 0) {
                    passwordConfirmCheckResult.textContent = '';
                    passwordConfirmCheckResult.className = '';
                    return;
                }
                
                if (password !== passwordConfirm) {
                    passwordConfirmCheckResult.textContent = '비밀번호가 일치하지 않습니다.';
                    passwordConfirmCheckResult.className = 'user-id-check-result error';
                    passwordConfirmCheckResult.style.color = '#ef4444';
                } else {
                    passwordConfirmCheckResult.textContent = '✓ 비밀번호가 일치합니다.';
                    passwordConfirmCheckResult.className = 'user-id-check-result success';
                    passwordConfirmCheckResult.style.color = '#10b981';
                }
            }
            
            // 비밀번호 입력 시 확인
            passwordInput.addEventListener('input', checkPasswordMatch);
            
            // 비밀번호 확인 입력 시 확인
            passwordConfirmInput.addEventListener('input', checkPasswordMatch);
        }
    }
    
    // ESC 키로 닫기 (전역 이벤트)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('login-modal-active')) {
            closeLoginModal();
        }
    });
});

// 회원가입 모드로 전환 (모달 내 동적 전환)
function switchToRegisterMode() {
    // 로그인되어 있으면 모달을 열지 않음
    if (isLoggedIn) {
        return;
    }
    
    const title = document.getElementById('loginModalTitle');
    const loginContent = document.getElementById('loginModeContent');
    const registerContent = document.getElementById('registerModeContent');
    
    if (title) title.textContent = '회원가입';
    if (loginContent) loginContent.style.display = 'none';
    if (registerContent) registerContent.style.display = 'block';
    
    // 모달 상단으로 스크롤
    const modalBody = document.querySelector('.login-modal-body');
    if (modalBody) {
        modalBody.scrollTop = 0;
    }
}

// 로그인 모드로 전환 (모달 내 동적 전환)
function switchToLoginMode() {
    const title = document.getElementById('loginModalTitle');
    const loginContent = document.getElementById('loginModeContent');
    const registerContent = document.getElementById('registerModeContent');
    
    if (title) title.textContent = '로그인';
    if (loginContent) loginContent.style.display = 'block';
    if (registerContent) registerContent.style.display = 'none';
    
    // 모달 상단으로 스크롤
    const modalBody = document.querySelector('.login-modal-body');
    if (modalBody) {
        modalBody.scrollTop = 0;
    }
}

// 비밀번호 표시/숨김 토글 함수
function togglePasswordVisibility(inputId, button) {
    const input = document.getElementById(inputId);
    const eyeIcon = button.querySelector('svg');
    
    if (input.type === 'password') {
        input.type = 'text';
        // 눈을 뜬 아이콘으로 변경 (눈을 뜨고 대각선으로 가려진 아이콘)
        eyeIcon.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
            <line x1="1" y1="1" x2="23" y2="23" stroke="currentColor" stroke-width="2" stroke-linecap="round"></line>
        `;
    } else {
        input.type = 'password';
        // 눈을 감은 아이콘으로 변경 (눈 아이콘)
        eyeIcon.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" fill="none"></path>
            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"></circle>
        `;
    }
}

// 전역 함수로 등록
window.openLoginModal = openLoginModal;
window.closeLoginModal = closeLoginModal;
window.switchToRegisterMode = switchToRegisterMode;
window.switchToLoginMode = switchToLoginMode;
window.togglePasswordVisibility = togglePasswordVisibility;
window.openRegisterSuccessModal = openRegisterSuccessModal;
window.closeRegisterSuccessModal = closeRegisterSuccessModal;
</script>



























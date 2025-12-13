<?php
/**
 * 로그인 모달 컴포넌트
 */
require_once __DIR__ . '/../../includes/data/auth-functions.php';

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

.login-form-group input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 15px;
    transition: border-color 0.2s;
    box-sizing: border-box;
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
                        <img src="/MVNO/assets/images/logo/button-kakao-login.png" alt="카카오톡 로그인" class="login-sns-button-img" onclick="snsLoginModal('kakao')" style="cursor: pointer; width: 100%; height: auto; border-radius: 12px;">
                        <img src="/MVNO/assets/images/logo/button-naver-login.png" alt="네이버로 로그인" class="login-sns-button-img" onclick="snsLoginModal('naver')" style="cursor: pointer; width: 100%; height: auto; border-radius: 12px;">
                        <img src="/MVNO/assets/images/logo/button-google-login.png" alt="구글 로그인" class="login-sns-button-img google" onclick="snsLoginModal('google')" style="cursor: pointer; width: 100%; height: auto; border-radius: 12px;">
                    </div>
                    
                    <!-- 일반 로그인 폼 -->
                    <div class="login-form-section">
                        <form id="loginForm" method="POST" action="/MVNO/api/direct-login.php">
                            <div class="login-form-group" style="margin-bottom: 16px;">
                                <input type="text" id="login_user_id" name="user_id" placeholder="아이디 입력 필드" required style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px;">
                            </div>
                            <div class="login-form-group" style="margin-bottom: 20px;">
                                <input type="password" id="login_password" name="password" placeholder="비밀번호 입력 필드" required style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 15px;">
                            </div>
                            <button type="submit" class="login-form-button">로그인</button>
                        </form>
                        <div class="login-form-switch" style="margin-top: 24px; text-align: center;">
                            아직 회원이 아니신가요? <a href="#" onclick="switchToRegisterMode(); return false;" style="color: #6366f1; text-decoration: none; font-weight: 500;">회원가입</a>
                        </div>
                    </div>
                </div>
                </div>
                
                <!-- 회원가입 모드 -->
                <div id="registerModeContent" style="display: <?php echo $isRegisterMode ? 'block' : 'none'; ?>;">
                <div class="login-benefits-section">
                    <h3 class="login-benefits-title">SNS로 회원가입</h3>
                    
                    <div class="login-sns-buttons" style="margin-top: 24px;">
                        <img src="/MVNO/assets/images/logo/button-kakao-login.png" alt="카카오톡 로그인" class="login-sns-button-img" onclick="snsLoginModal('kakao')" style="cursor: pointer; width: 100%; height: auto; border-radius: 12px;">
                        <img src="/MVNO/assets/images/logo/button-naver-login.png" alt="네이버로 로그인" class="login-sns-button-img" onclick="snsLoginModal('naver')" style="cursor: pointer; width: 100%; height: auto; border-radius: 12px;">
                        <img src="/MVNO/assets/images/logo/button-google-login.png" alt="구글 로그인" class="login-sns-button-img google" onclick="snsLoginModal('google')" style="cursor: pointer; width: 100%; height: auto; border-radius: 12px;">
                    </div>
                    
                    <div class="login-divider">
                        <span>또는</span>
                    </div>
                    
                    <!-- 일반 회원가입 폼 -->
                    <div class="login-form-section">
                        <form id="registerForm" method="POST" action="/MVNO/auth/register.php">
                            <input type="hidden" name="role" value="user">
                            <div class="login-form-group">
                                <label for="register_user_id">아이디 <span style="color: #ef4444;">*</span></label>
                                <input type="text" id="register_user_id" name="user_id" required placeholder="아이디 입력 (영문, 숫자만 가능, 최소 4자)" pattern="[A-Za-z0-9]+" title="영문과 숫자만 입력 가능합니다">
                                <div id="userIdCheckResult" style="margin-top: 6px; font-size: 13px; min-height: 18px;"></div>
                            </div>
                            <div class="login-form-group">
                                <label for="register_name">이름 <span style="color: #ef4444;">*</span></label>
                                <input type="text" id="register_name" name="name" required placeholder="이름 입력">
                            </div>
                            <div class="login-form-group">
                                <label for="register_phone">휴대폰번호 <span style="color: #ef4444;">*</span></label>
                                <input type="tel" id="register_phone" name="phone" required placeholder="010-1234-5678" pattern="010-\d{4}-\d{4}" maxlength="13">
                            </div>
                            <div class="login-form-group">
                                <label for="register_email">이메일 <span style="color: #ef4444;">*</span></label>
                                <input type="email" id="register_email" name="email" required placeholder="이메일 입력">
                            </div>
                            <div class="login-form-group password-wrapper">
                                <label for="register_password">비밀번호</label>
                                <input type="password" id="register_password" name="password" required minlength="8" placeholder="비밀번호 입력 (영문 대소문자, 숫자, 특수문자 포함 8자 이상)">
                                <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('register_password', this)" aria-label="비밀번호 표시/숨김">
                                    <svg id="register_password_eye" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                            <div class="login-form-group password-wrapper">
                                <label for="register_password_confirm">비밀번호 확인</label>
                                <input type="password" id="register_password_confirm" name="password_confirm" required minlength="8" placeholder="비밀번호 확인">
                                <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('register_password_confirm', this)" aria-label="비밀번호 표시/숨김">
                                    <svg id="register_password_confirm_eye" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
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

<script>
// 로그인 모달 열기/닫기
function openLoginModal(isRegister = false) {
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
    fetch(`/MVNO/api/sns-login.php?action=${provider}`)
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

// 모달 초기화
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('loginModal');
    const closeBtn = document.getElementById('loginModalClose');
    const overlay = modal.querySelector('.login-modal-overlay');
    
    // 닫기 버튼
    if (closeBtn) {
        closeBtn.addEventListener('click', closeLoginModal);
    }
    
    // 오버레이 클릭 시 닫기
    if (overlay) {
        overlay.addEventListener('click', closeLoginModal);
    }
    
    // ESC 키로 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('login-modal-active')) {
            closeLoginModal();
        }
    });
    
});

// 로그인 폼 제출 처리
document.addEventListener('DOMContentLoaded', function() {
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
            const email = document.getElementById('register_email').value.trim();
            const password = document.getElementById('register_password').value;
            const passwordConfirm = document.getElementById('register_password_confirm').value;
            
            if (!userId || !phone || !name || !email) {
                alert('아이디, 휴대폰번호, 이름, 이메일은 필수 입력 항목입니다.');
                return;
            }
            
            // 아이디 검증: 영문, 숫자만 가능
            const userIdPattern = /^[A-Za-z0-9]+$/;
            if (!userIdPattern.test(userId)) {
                alert('아이디는 영문과 숫자만 사용할 수 있습니다.');
                document.getElementById('register_user_id').focus();
                return;
            }
            
            // 아이디 최소 길이 확인
            if (userId.length < 4) {
                alert('아이디는 최소 4자 이상이어야 합니다.');
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
            
            // 전화번호 형식 검증 (010-XXXX-XXXX)
            const phonePattern = /^010-\d{4}-\d{4}$/;
            if (!phonePattern.test(phone)) {
                alert('휴대폰번호는 010-XXXX-XXXX 형식으로 입력해주세요.');
                document.getElementById('register_phone').focus();
                return;
            }
            
            // 비밀번호 검증: 영문 대소문자, 숫자, 특수문자 포함, 최소 8자리
            if (password.length < 8) {
                alert('비밀번호는 최소 8자 이상이어야 합니다.');
                document.getElementById('register_password').focus();
                return;
            }
            
            // 영문 대소문자, 숫자, 특수문자(@#$%^&*!?_-=) 포함 확인
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecialChar = /[@#$%^&*!?_\-=]/.test(password);
            
            if (!hasUpperCase || !hasLowerCase || !hasNumber || !hasSpecialChar) {
                alert('비밀번호는 영문 대소문자, 숫자, 특수문자(@#$%^&*!?_-=)를 포함해야 합니다.');
                document.getElementById('register_password').focus();
                return;
            }
            
            if (password !== passwordConfirm) {
                alert('비밀번호가 일치하지 않습니다.');
                return;
            }
            
            // 폼 제출 (페이지 리로드)
            this.submit();
        });
        
        // 전화번호 자동 포맷팅 (010-XXXX-XXXX)
        const phoneInput = document.getElementById('register_phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/[^\d]/g, '');
                if (value.length > 11) value = value.substring(0, 11);
                
                if (value.length > 7) {
                    value = value.substring(0, 3) + '-' + value.substring(3, 7) + '-' + value.substring(7);
                } else if (value.length > 3) {
                    value = value.substring(0, 3) + '-' + value.substring(3);
                }
                
                e.target.value = value;
            });
        }
        
        // 아이디 실시간 중복 확인
        const userIdInput = document.getElementById('register_user_id');
        const userIdCheckResult = document.getElementById('userIdCheckResult');
        let userIdCheckTimeout = null;
        let isUserIdValid = false;
        
        if (userIdInput && userIdCheckResult) {
            userIdInput.addEventListener('input', function(e) {
                const userId = e.target.value.trim();
                
                // 입력 필드 클래스 초기화
                userIdInput.classList.remove('checked-valid', 'checked-invalid', 'checking');
                userIdCheckResult.className = 'user-id-check-result';
                userIdCheckResult.textContent = '';
                isUserIdValid = false;
                
                // 기존 timeout 취소
                if (userIdCheckTimeout) {
                    clearTimeout(userIdCheckTimeout);
                }
                
                // 최소 길이 체크
                if (userId.length < 4) {
                    if (userId.length > 0) {
                        userIdCheckResult.textContent = '아이디는 최소 4자 이상이어야 합니다.';
                        userIdCheckResult.className = 'user-id-check-result error';
                        userIdInput.classList.add('checked-invalid');
                    }
                    return;
                }
                
                // 영문, 숫자만 허용
                if (!/^[A-Za-z0-9]+$/.test(userId)) {
                    userIdCheckResult.textContent = '아이디는 영문과 숫자만 사용할 수 있습니다.';
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
                                isUserIdValid = true;
                            } else {
                                // 사용 불가
                                userIdCheckResult.textContent = '✗ ' + data.message;
                                userIdCheckResult.className = 'user-id-check-result error';
                                userIdInput.classList.remove('checked-valid');
                                userIdInput.classList.add('checked-invalid');
                                isUserIdValid = false;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            userIdInput.classList.remove('checking');
                            userIdCheckResult.textContent = '확인 중 오류가 발생했습니다.';
                            userIdCheckResult.className = 'user-id-check-result error';
                            userIdInput.classList.add('checked-invalid');
                            isUserIdValid = false;
                        });
                }, 500);
            });
            
            // 폼 제출 시 아이디 검증 확인
            registerForm.addEventListener('submit', function(e) {
                if (!isUserIdValid) {
                    e.preventDefault();
                    const userId = userIdInput.value.trim();
                    if (userId.length >= 4 && /^[A-Za-z0-9]+$/.test(userId)) {
                        alert('아이디 중복 확인을 완료해주세요.');
                        userIdInput.focus();
                    }
                }
            });
        }
    }
});

// 회원가입 모드로 전환 (모달 내 동적 전환)
function switchToRegisterMode() {
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
</script>


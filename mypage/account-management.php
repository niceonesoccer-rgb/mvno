<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

// 로그인 체크를 위한 auth-functions 포함 (세션 설정과 함께 세션을 시작함)
require_once '../includes/data/auth-functions.php';
require_once '../includes/data/path-config.php';

// 로그인 체크 - 로그인하지 않은 경우 회원가입 모달로 리다이렉트
if (!isLoggedIn()) {
    // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    // 로그인 모달이 있는 홈으로 리다이렉트 (모달 자동 열기)
    header('Location: ' . getAssetPath('/') . '?show_login=1');
    exit;
}

// 현재 사용자 정보 가져오기
$currentUser = getCurrentUser();
if (!$currentUser) {
    // 세션 정리 후 로그인 페이지로 리다이렉트
    if (isset($_SESSION['logged_in'])) {
        unset($_SESSION['logged_in']);
    }
    if (isset($_SESSION['user_id'])) {
        unset($_SESSION['user_id']);
    }
    // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . getAssetPath('/') . '?show_login=1');
    exit;
}

require_once '../includes/data/point-settings.php';
$userPoint = getUserPoint($currentUser['user_id'] ?? '');
$pointBalance = (int)($userPoint['balance'] ?? 0);

// 헤더 포함
include '../includes/header.php';
?>

<style>
/* 모바일에서는 전체 화면, PC에서는 중앙에 작은 모달 */
@media (min-width: 768px) {
    #emailModal > div,
    #passwordModal > div {
        width: 100% !important;
        max-width: 500px !important;
        height: auto !important;
        max-height: 90vh !important;
        border-radius: 12px !important;
    }
}

@media (max-width: 767px) {
    #emailModal > div,
    #passwordModal > div {
        width: 100% !important;
        max-width: 100% !important;
        height: 100% !important;
        max-height: 100% !important;
        border-radius: 0 !important;
    }
}
</style>

<main class="main-content">
    <div style="width: 100%; max-width: 460px; margin: 0 auto; padding: 20px;" class="account-settings-container">
        <!-- 뒤로가기 버튼 및 제목 -->
        <div style="margin-bottom: 24px; padding: 20px 0;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <a href="<?php echo getAssetPath('/mypage/mypage.php'); ?>" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <h1 style="font-size: 20px; font-weight: bold; margin: 0; color: #212529;">계정 설정</h1>
            </div>
        </div>

        <!-- 계정 정보 섹션 -->
        <div style="background-color: #ffffff; border-radius: 8px; padding: 24px; margin-bottom: 24px;">
            <div style="margin-bottom: 20px;">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; border-bottom: 1px solid #e5e7eb;">
                    <span style="font-size: 16px; color: #6b7280; font-weight: 500;">이름</span>
                    <span style="font-size: 16px; color: #212529; font-weight: 500;"><?php echo htmlspecialchars($currentUser['name'] ?? '-'); ?></span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; border-bottom: 1px solid #e5e7eb;">
                    <span style="font-size: 16px; color: #6b7280; font-weight: 500;">아이디</span>
                    <span id="displayUserId" style="font-size: 16px; color: #212529; font-weight: 500;">
                        <?php echo htmlspecialchars($currentUser['user_id'] ?? '-'); ?>
                    </span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; border-bottom: 1px solid #e5e7eb;">
                    <span style="font-size: 16px; color: #6b7280; font-weight: 500;">연락처</span>
                    <span style="font-size: 16px; color: #212529; font-weight: 500;"><?php echo htmlspecialchars($currentUser['phone'] ?? '-'); ?></span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; border-bottom: 1px solid #e5e7eb;">
                    <span style="font-size: 16px; color: #6b7280; font-weight: 500;">이메일</span>
                    <div style="display: flex; align-items: center; gap: 8px; flex: 1; justify-content: flex-end;">
                        <span id="displayUserEmail" style="font-size: 16px; color: #212529; font-weight: 500;"><?php echo htmlspecialchars($currentUser['email'] ?? '-'); ?></span>
                        <button type="button" id="editEmailBtn" style="background: none; border: none; color: #6366f1; font-size: 14px; cursor: pointer; padding: 4px 8px; font-weight: 500;">
                            수정
                        </button>
                    </div>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; border-bottom: 1px solid #e5e7eb;">
                    <span style="font-size: 16px; color: #6b7280; font-weight: 500;">비밀번호</span>
                    <button type="button" id="changePasswordBtn" style="background: none; border: none; color: #6366f1; font-size: 14px; cursor: pointer; padding: 4px 8px; font-weight: 500;">
                        변경
                    </button>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0;">
                    <span style="font-size: 16px; color: #6b7280; font-weight: 500;">포인트</span>
                    <span style="font-size: 16px; color: #212529; font-weight: 500;"><?php echo number_format($pointBalance); ?>원</span>
                </div>
            </div>
        </div>

        <!-- 회원 탈퇴 버튼 -->
        <div style="margin-top: 32px;">
            <a href="<?php echo getAssetPath('/mypage/withdraw.php'); ?>" style="display: block; width: 100%; padding: 16px; background-color: transparent; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 16px; color: #212529; text-align: center; text-decoration: none; font-weight: 500;">
                회원 탈퇴
            </a>
        </div>
    </div>
</main>

<!-- 이메일 수정 모달 -->
<div id="emailModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 1000; overflow: hidden; width: 100%; height: 100%; align-items: center; justify-content: center;">
    <div style="position: relative; width: 100%; max-width: 500px; height: 100%; max-height: 90vh; display: flex; flex-direction: column; background: white; margin: 0; padding: 0; border-radius: 12px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2); overflow: hidden;">
        <!-- 모달 헤더 -->
        <div style="flex-shrink: 0; background: white; padding: 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
            <h3 style="font-size: 20px; font-weight: bold; margin: 0;">이메일 수정</h3>
            <button type="button" id="closeEmailModal" style="background: none; border: none; cursor: pointer; padding: 4px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#374151" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        
        <!-- 모달 내용 -->
        <div style="flex: 1; overflow-y: auto; padding: 20px;">
            <form id="emailForm">
                <!-- 1단계: 이메일 입력 및 인증번호 발송 -->
                <div id="emailStep1">
                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                            새 이메일 주소
                        </label>
                        <div style="display: flex; gap: 8px;">
                            <input 
                                type="email" 
                                id="emailInput" 
                                name="email" 
                                placeholder="example@email.com" 
                                required
                                style="flex: 1; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; box-sizing: border-box; outline: none; transition: border-color 0.2s;"
                                onfocus="this.style.borderColor='#6366f1'"
                                onblur="this.style.borderColor='#d1d5db'"
                            >
                            <button 
                                type="button" 
                                id="sendVerificationBtn"
                                style="padding: 12px 20px; background-color: #6366f1; border: none; border-radius: 8px; font-size: 14px; color: white; font-weight: 500; cursor: pointer; white-space: nowrap;"
                            >
                                인증번호 발송
                            </button>
                        </div>
                        <div id="emailError" style="display: none; color: #ef4444; font-size: 13px; margin-top: 8px;"></div>
                        <div id="emailInputError" style="display: none; color: #ef4444; font-size: 13px; margin-top: 8px;"></div>
                        <div id="emailSuccess" style="display: none; color: #10b981; font-size: 13px; margin-top: 8px;"></div>
                    </div>
                    
                    <div style="display: flex; gap: 12px;">
                        <button 
                            type="button" 
                            id="cancelEmailBtn"
                            style="flex: 1; padding: 14px; background-color: #f3f4f6; border: none; border-radius: 8px; font-size: 16px; color: #374151; font-weight: 500; cursor: pointer;"
                        >
                            취소
                        </button>
                    </div>
                </div>
                
                <!-- 2단계: 인증번호 입력 -->
                <div id="emailStep2" style="display: none;">
                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                            인증번호 입력
                        </label>
                        <input 
                            type="text" 
                            id="verificationCodeInput" 
                            name="verification_code" 
                            placeholder="6자리 인증번호를 입력하세요" 
                            maxlength="6"
                            style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; box-sizing: border-box; outline: none; transition: border-color 0.2s; text-align: center; letter-spacing: 5px; font-size: 20px; font-weight: 600;"
                            onfocus="this.style.borderColor='#6366f1'"
                            onblur="this.style.borderColor='#d1d5db'"
                        >
                        <div id="verificationError" style="display: none; color: #ef4444; font-size: 13px; margin-top: 8px;"></div>
                        <div id="verificationSuccess" style="display: none; color: #10b981; font-size: 13px; margin-top: 8px;"></div>
                        <div style="margin-top: 8px; font-size: 12px; color: #6b7280; text-align: center;">
                            <button 
                                type="button" 
                                id="resendVerificationBtn"
                                style="background: none; border: none; color: #6366f1; font-size: 12px; cursor: pointer; text-decoration: underline;"
                            >
                                인증번호 다시 받기
                            </button>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 12px;">
                        <button 
                            type="button" 
                            id="backToEmailBtn"
                            style="flex: 1; padding: 14px; background-color: #f3f4f6; border: none; border-radius: 8px; font-size: 16px; color: #374151; font-weight: 500; cursor: pointer;"
                        >
                            이전
                        </button>
                        <button 
                            type="button" 
                            id="verifyCodeBtn"
                            style="flex: 1; padding: 14px; background-color: #6366f1; border: none; border-radius: 8px; font-size: 16px; color: white; font-weight: 500; cursor: pointer;"
                        >
                            인증 확인
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 비밀번호 변경 모달 -->
<div id="passwordModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: white; z-index: 1000; overflow: hidden; width: 100%; height: 100%;">
    <div style="position: relative; width: 100%; height: 100%; display: flex; flex-direction: column; background: white; margin: 0; padding: 0;">
        <!-- 모달 헤더 -->
        <div style="flex-shrink: 0; background: white; padding: 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
            <h3 style="font-size: 20px; font-weight: bold; margin: 0;">비밀번호 변경</h3>
            <button type="button" id="closePasswordModal" style="background: none; border: none; cursor: pointer; padding: 4px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#374151" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        
        <!-- 모달 내용 -->
        <div style="flex: 1; overflow-y: auto; padding: 20px;">
            <form id="passwordForm">
                <!-- 이메일 인증 섹션 (이메일이 있는 경우) -->
                <div id="passwordEmailVerification" style="display: none; margin-bottom: 24px; padding: 16px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                            이메일 인증
                        </label>
                        <div style="font-size: 13px; color: #6b7280; margin-bottom: 12px;">
                            등록된 이메일로 인증번호를 발송합니다.
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <input 
                                type="text" 
                                id="passwordVerificationCode" 
                                placeholder="인증번호 입력" 
                                maxlength="6"
                                style="flex: 1; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; box-sizing: border-box; outline: none; text-align: center; letter-spacing: 5px; font-size: 18px; font-weight: 600;"
                            >
                            <button 
                                type="button" 
                                id="sendPasswordVerificationBtn"
                                style="padding: 12px 20px; background-color: #6366f1; border: none; border-radius: 8px; font-size: 14px; color: white; font-weight: 500; cursor: pointer; white-space: nowrap;"
                            >
                                인증번호 발송
                            </button>
                        </div>
                        <div id="passwordVerificationError" style="display: none; color: #ef4444; font-size: 13px; margin-top: 8px;"></div>
                        <div id="passwordVerificationSuccess" style="display: none; color: #10b981; font-size: 13px; margin-top: 8px;"></div>
                    </div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        새 비밀번호
                    </label>
                    <div style="position: relative;">
                        <input 
                            type="password" 
                            id="newPassword" 
                            name="new_password" 
                            placeholder="새 비밀번호를 입력하세요" 
                            required
                            minlength="8"
                            style="width: 100%; padding: 12px 40px 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; box-sizing: border-box; outline: none; transition: border-color 0.2s;"
                            onfocus="this.style.borderColor='#6366f1'"
                            onblur="this.style.borderColor='#d1d5db'"
                        >
                        <button 
                            type="button" 
                            id="toggleNewPassword"
                            style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px; display: flex; align-items: center; color: #6b7280; z-index: 10;"
                            onclick="togglePasswordVisibility('newPassword', 'toggleNewPassword')"
                            title="비밀번호 표시/숨김"
                        >
                            <svg id="iconNewPassword" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                        8자 이상, 영문자/숫자/특수문자 중 2가지 이상 조합 (공백 불가)
                    </div>
                    <div id="newPasswordError" style="display: none; color: #ef4444; font-size: 13px; margin-top: 8px;"></div>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        새 비밀번호 확인
                    </label>
                    <div style="position: relative;">
                        <input 
                            type="password" 
                            id="confirmPassword" 
                            name="confirm_password" 
                            placeholder="새 비밀번호를 다시 입력하세요" 
                            required
                            style="width: 100%; padding: 12px 40px 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; box-sizing: border-box; outline: none; transition: border-color 0.2s;"
                            onfocus="this.style.borderColor='#6366f1'"
                            onblur="this.style.borderColor='#d1d5db'"
                        >
                        <button 
                            type="button" 
                            id="toggleConfirmPassword"
                            style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px; display: flex; align-items: center; color: #6b7280; z-index: 10;"
                            onclick="togglePasswordVisibility('confirmPassword', 'toggleConfirmPassword')"
                            title="비밀번호 표시/숨김"
                        >
                            <svg id="iconConfirmPassword" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <div id="confirmPasswordError" style="display: none; color: #ef4444; font-size: 13px; margin-top: 8px;"></div>
                </div>
                
                <div style="display: flex; gap: 12px;">
                    <button 
                        type="button" 
                        id="cancelPasswordBtn"
                        style="flex: 1; padding: 14px; background-color: #f3f4f6; border: none; border-radius: 8px; font-size: 16px; color: #374151; font-weight: 500; cursor: pointer;"
                    >
                        취소
                    </button>
                    <button 
                        type="submit" 
                        id="submitPasswordBtn"
                        style="flex: 1; padding: 14px; background-color: #6366f1; border: none; border-radius: 8px; font-size: 16px; color: white; font-weight: 500; cursor: pointer;"
                    >
                        확인
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// API 경로 설정 (PHP에서 동적으로 생성)
const API_BASE_URL = '<?php echo getApiPath(""); ?>';
const API_SEND_EMAIL_VERIFICATION = '<?php echo getApiPath("/api/send-email-verification.php"); ?>';
const API_VERIFY_EMAIL_CODE = '<?php echo getApiPath("/api/verify-email-code.php"); ?>';
const API_UPDATE_EMAIL = '<?php echo getApiPath("/api/update-email.php"); ?>';
const API_CHANGE_PASSWORD = '<?php echo getApiPath("/api/change-password.php"); ?>';

// 디버깅: 생성된 경로 확인 (콘솔에서 확인 가능)
console.log('API 경로 설정:', {
    API_SEND_EMAIL_VERIFICATION: API_SEND_EMAIL_VERIFICATION,
    API_VERIFY_EMAIL_CODE: API_VERIFY_EMAIL_CODE,
    API_CHANGE_PASSWORD: API_CHANGE_PASSWORD,
    BASE_PATH: '<?php echo defined("BASE_PATH") ? BASE_PATH : "undefined"; ?>'
});

// 비밀번호 표시/숨김 토글 함수
function togglePasswordVisibility(inputId, buttonId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    let iconId = '';
    
    if (inputId === 'newPassword') {
        iconId = 'iconNewPassword';
    } else if (inputId === 'confirmPassword') {
        iconId = 'iconConfirmPassword';
    }
    
    const icon = document.getElementById(iconId);
    const button = document.getElementById(buttonId);
    
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            // 눈 아이콘 (표시 상태) - 취소선 있는 눈
            icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
        }
        if (button) {
            button.style.color = '#6366f1'; // 활성화된 색상
            button.title = '비밀번호 숨기기';
        }
    } else {
        input.type = 'password';
        if (icon) {
            // 눈 아이콘 (숨김 상태) - 일반 눈
            icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
        }
        if (button) {
            button.style.color = '#6b7280'; // 기본 색상
            button.title = '비밀번호 표시';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // 아이디는 가입 시 자동 생성되므로 수정 불가 (읽기 전용)
    
    // 이메일 관련
    const displayUserEmail = document.getElementById('displayUserEmail');
    const editEmailBtn = document.getElementById('editEmailBtn');
    const emailModal = document.getElementById('emailModal');
    const closeEmailModal = document.getElementById('closeEmailModal');
    const cancelEmailBtn = document.getElementById('cancelEmailBtn');
    const emailForm = document.getElementById('emailForm');
    const emailInput = document.getElementById('emailInput');
    const emailError = document.getElementById('emailError');
    const emailSuccess = document.getElementById('emailSuccess');
    const emailStep1 = document.getElementById('emailStep1');
    const emailStep2 = document.getElementById('emailStep2');
    const sendVerificationBtn = document.getElementById('sendVerificationBtn');
    const verificationCodeInput = document.getElementById('verificationCodeInput');
    const verifyCodeBtn = document.getElementById('verifyCodeBtn');
    const resendVerificationBtn = document.getElementById('resendVerificationBtn');
    const backToEmailBtn = document.getElementById('backToEmailBtn');
    const verificationError = document.getElementById('verificationError');
    const verificationSuccess = document.getElementById('verificationSuccess');
    
    // 현재 이메일 값 저장 (초기값)
    let originalEmail = '<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>';
    let currentVerificationToken = null;
    let currentVerificationEmail = null;
    
    // 비밀번호 모달 관련
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    const passwordModal = document.getElementById('passwordModal');
    const closePasswordModal = document.getElementById('closePasswordModal');
    const cancelPasswordBtn = document.getElementById('cancelPasswordBtn');
    const passwordForm = document.getElementById('passwordForm');
    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    const newPasswordError = document.getElementById('newPasswordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');
    const passwordEmailVerification = document.getElementById('passwordEmailVerification');
    const sendPasswordVerificationBtn = document.getElementById('sendPasswordVerificationBtn');
    const passwordVerificationCode = document.getElementById('passwordVerificationCode');
    const passwordVerificationError = document.getElementById('passwordVerificationError');
    const passwordVerificationSuccess = document.getElementById('passwordVerificationSuccess');
    
    let passwordVerificationToken = null;
    
    // 모달 닫기 함수
    function closeModal(modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
    
    // 이메일 수정 버튼 클릭
    if (editEmailBtn) {
        editEmailBtn.addEventListener('click', function() {
            if (emailModal) {
                emailModal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                resetEmailModal();
                if (emailInput) {
                    emailInput.value = '';
                    emailInput.focus();
                }
            }
        });
    }
    
    // 이메일 모달 초기화
    function resetEmailModal() {
        emailStep1.style.display = 'block';
        emailStep2.style.display = 'none';
        emailError.style.display = 'none';
        emailInputError.style.display = 'none';
        emailSuccess.style.display = 'none';
        verificationError.style.display = 'none';
        verificationSuccess.style.display = 'none';
        emailInput.value = '';
        verificationCodeInput.value = '';
        currentVerificationToken = null;
        currentVerificationEmail = null;
    }
    
    // 이메일 모달 닫기
    if (closeEmailModal) {
        closeEmailModal.addEventListener('click', function() {
            closeModal(emailModal);
            emailForm.reset();
            resetEmailModal();
        });
    }
    
    if (cancelEmailBtn) {
        cancelEmailBtn.addEventListener('click', function() {
            closeModal(emailModal);
            emailForm.reset();
            resetEmailModal();
        });
    }
    
    // 인증번호 발송 버튼
    if (sendVerificationBtn) {
        sendVerificationBtn.addEventListener('click', function() {
            const email = emailInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            emailError.style.display = 'none';
            emailSuccess.style.display = 'none';
            
            if (!email) {
                emailError.textContent = '이메일 주소를 입력해주세요.';
                emailError.style.display = 'block';
                return;
            }
            
            if (!emailRegex.test(email)) {
                emailError.textContent = '올바른 이메일 형식을 입력해주세요.';
                emailError.style.display = 'block';
                return;
            }
            
            if (email === originalEmail) {
                emailError.textContent = '현재 사용 중인 이메일과 동일합니다.';
                emailError.style.display = 'block';
                return;
            }
            
            // 인증번호 발송
            sendVerificationBtn.disabled = true;
            sendVerificationBtn.textContent = '발송 중...';
            
            // FormData 사용 (JSON이 서버에서 거부될 수 있으므로)
            const formData = new FormData();
            formData.append('email', email);
            formData.append('type', 'email_change');
            
            fetch(API_SEND_EMAIL_VERIFICATION, {
                method: 'POST',
                credentials: 'include', // 세션 쿠키 전송
                body: formData
            })
            .then(response => {
                // 응답 텍스트로 받기 (JSON 파싱은 나중에)
                return response.text().then(text => {
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        // JSON 파싱 실패 시 에러로 처리
                        throw new Error('서버 응답 파싱 오류: ' + text.substring(0, 200));
                    }
                    
                    // response.ok가 false이고 JSON 파싱이 성공한 경우
                    if (!response.ok) {
                        // 에러 데이터를 포함한 에러 객체 생성
                        const error = new Error(data.message || '서버 오류: ' + response.status);
                        error.data = data;
                        error.status = response.status;
                        throw error;
                    }
                    
                    return data;
                });
            })
            .then(data => {
                sendVerificationBtn.disabled = false;
                sendVerificationBtn.textContent = '인증번호 발송';
                
                if (data.success) {
                    // 이메일 발송 실패 여부 확인
                    if (data.email_send_failed) {
                        // 이메일 발송 실패 시 안내 메시지
                        emailError.innerHTML = '이메일 발송에 실패했습니다.<br>' +
                            '<small style="color: #6b7280;">이메일 설정을 확인하시거나, 관리자에게 문의해주세요.</small><br>' +
                            '<small style="color: #6b7280;">임시로 인증번호를 확인하려면 관리자 페이지를 이용해주세요.</small>';
                        emailError.style.display = 'block';
                        sendVerificationBtn.disabled = false;
                        sendVerificationBtn.textContent = '인증번호 발송';
                        return;
                    }
                    
                    let successMessage = '인증번호가 발송되었습니다. 이메일을 확인해주세요.';
                    
                    // 개발 환경에서 인증번호가 반환된 경우 표시
                    if (data.development_mode && data.verification_code) {
                        const testUrl = '<?php echo getAssetPath("/admin/test-email-verification.php"); ?>';
                        successMessage = '인증번호가 생성되었습니다. (개발 환경)<br>' +
                                       '<strong style="font-size: 18px; color: #6366f1; letter-spacing: 3px;">인증번호: ' + data.verification_code + '</strong><br>' +
                                       '<small style="color: #6b7280;">또는 <a href="' + testUrl + '" target="_blank" style="color: #6366f1;">인증번호 확인 페이지</a>에서 확인하세요.</small>';
                    } else {
                        // 실제 서버에서도 이메일이 도착하지 않을 경우를 대비해 안내 추가
                        successMessage += '<br><small style="color: #6b7280; margin-top: 8px; display: block;">이메일이 도착하지 않으면 스팸함을 확인하거나, 잠시 후 다시 시도해주세요.</small>';
                    }
                    
                    emailSuccess.innerHTML = successMessage;
                    emailSuccess.style.display = 'block';
                    currentVerificationEmail = email;
                    
                    // 개발 환경에서 인증번호가 있으면 자동 입력
                    if (data.development_mode && data.verification_code && verificationCodeInput) {
                        verificationCodeInput.value = data.verification_code;
                    }
                    
                    // 2단계로 이동
                    emailStep1.style.display = 'none';
                    emailStep2.style.display = 'block';
                    if (verificationCodeInput) {
                        verificationCodeInput.focus();
                    }
                } else {
                    emailError.textContent = data.message || '인증번호 발송에 실패했습니다.';
                    emailError.style.display = 'block';
                    console.error('API 오류:', data);
                }
            })
            .catch(error => {
                sendVerificationBtn.disabled = false;
                sendVerificationBtn.textContent = '인증번호 발송';
                console.error('Fetch 오류:', error);
                
                // 에러 객체에 data 속성이 있는 경우 (서버에서 JSON 에러 응답을 보낸 경우)
                if (error.data && error.data.message) {
                    emailError.textContent = error.data.message;
                } else {
                    emailError.textContent = '인증번호 발송 중 오류가 발생했습니다: ' + (error.message || '알 수 없는 오류');
                }
                emailError.style.display = 'block';
            });
        });
    }
    
    // 인증번호 확인 버튼
    if (verifyCodeBtn) {
        verifyCodeBtn.addEventListener('click', function() {
            const code = verificationCodeInput.value.trim();
            
            verificationError.style.display = 'none';
            verificationSuccess.style.display = 'none';
            
            if (!code || code.length !== 6) {
                verificationError.textContent = '6자리 인증번호를 입력해주세요.';
                verificationError.style.display = 'block';
                return;
            }
            
            if (!currentVerificationEmail) {
                verificationError.textContent = '이메일 정보가 없습니다. 다시 시도해주세요.';
                verificationError.style.display = 'block';
                return;
            }
            
            verifyCodeBtn.disabled = true;
            verifyCodeBtn.textContent = '인증 중...';
            
                // FormData 사용
                const formData = new FormData();
                formData.append('email', currentVerificationEmail);
                formData.append('verification_code', code);
                formData.append('type', 'email_change');
                
                fetch(API_VERIFY_EMAIL_CODE, {
                    method: 'POST',
                    credentials: 'include', // 세션 쿠키 전송
                    body: formData
                })
            .then(response => {
                return response.text().then(text => {
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        throw new Error('서버 응답 파싱 오류: ' + text.substring(0, 200));
                    }
                    
                    if (!response.ok) {
                        const error = new Error(data.message || '서버 오류: ' + response.status);
                        error.data = data;
                        throw error;
                    }
                    
                    return data;
                });
            })
            .then(data => {
                verifyCodeBtn.disabled = false;
                verifyCodeBtn.textContent = '인증 확인';
                
                if (data.success) {
                    currentVerificationToken = data.verification_token;
                    verificationSuccess.textContent = '인증이 완료되었습니다.';
                    verificationSuccess.style.display = 'block';
                    
                    // 이메일 변경 완료
                    updateEmail();
                } else {
                    verificationError.textContent = data.message || '인증번호가 일치하지 않습니다.';
                    verificationError.style.display = 'block';
                }
            })
            .catch(error => {
                verifyCodeBtn.disabled = false;
                verifyCodeBtn.textContent = '인증 확인';
                if (error.data && error.data.message) {
                    verificationError.textContent = error.data.message;
                } else {
                    verificationError.textContent = '인증 처리 중 오류가 발생했습니다: ' + (error.message || '알 수 없는 오류');
                }
                verificationError.style.display = 'block';
            });
        });
    }
    
    // 인증번호 다시 받기
    if (resendVerificationBtn) {
        resendVerificationBtn.addEventListener('click', function() {
            if (currentVerificationEmail) {
                sendVerificationBtn.click();
            }
        });
    }
    
    // 이전 버튼
    if (backToEmailBtn) {
        backToEmailBtn.addEventListener('click', function() {
            emailStep1.style.display = 'block';
            emailStep2.style.display = 'none';
            verificationError.style.display = 'none';
            verificationSuccess.style.display = 'none';
            verificationCodeInput.value = '';
        });
    }
    
    // 이메일 변경 완료 함수
    function updateEmail() {
        if (!currentVerificationToken || !currentVerificationEmail) {
            return;
        }
        
        // FormData 사용
        const formData = new FormData();
        formData.append('email', currentVerificationEmail);
        formData.append('verification_token', currentVerificationToken);
        
        fetch(API_UPDATE_EMAIL, {
            method: 'POST',
            credentials: 'include', // 세션 쿠키 전송
            body: formData
        })
        .then(response => {
            return response.text().then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('서버 응답 파싱 오류: ' + text.substring(0, 200));
                }
                
                if (!response.ok) {
                    const error = new Error(data.message || '서버 오류: ' + response.status);
                    error.data = data;
                    throw error;
                }
                
                return data;
            });
        })
        .then(data => {
            if (data.success) {
                originalEmail = currentVerificationEmail;
                if (displayUserEmail) displayUserEmail.textContent = currentVerificationEmail;
                closeModal(emailModal);
                emailForm.reset();
                resetEmailModal();
                showAlert('이메일 주소가 변경되었습니다.');
            } else {
                verificationError.textContent = data.message || '이메일 변경에 실패했습니다.';
                verificationError.style.display = 'block';
            }
        })
        .catch(error => {
            if (error.data && error.data.message) {
                verificationError.textContent = error.data.message;
            } else {
                verificationError.textContent = '이메일 변경 중 오류가 발생했습니다: ' + (error.message || '알 수 없는 오류');
            }
            verificationError.style.display = 'block';
        });
    }
    
    // 이메일 입력 시 자동 포맷팅 (대문자→소문자, 영문자/숫자/_/-/@/. 만 허용)
    const emailInputError = document.getElementById('emailInputError');
    if (emailInput) {
        emailInput.addEventListener('input', function(e) {
            const originalValue = this.value;
            // 대문자를 소문자로 변환
            let value = originalValue.toLowerCase();
            // 허용되지 않은 문자 찾기
            const invalidChars = originalValue.match(/[^a-z0-9_\-@.A-Z]/g);
            
            // 영문자, 숫자, _, -, @, . 만 허용
            value = value.replace(/[^a-z0-9_\-@.]/g, '');
            
            // 허용되지 않은 문자가 있었으면 경고 표시
            if (invalidChars && invalidChars.length > 0) {
                const uniqueInvalidChars = [...new Set(invalidChars)];
                emailInputError.textContent = `허용되지 않은 문자입니다: ${uniqueInvalidChars.join(', ')}. 영문자, 숫자, _, -, @, . 만 입력 가능합니다.`;
                emailInputError.style.display = 'block';
                // 3초 후 자동 숨김
                setTimeout(() => {
                    emailInputError.style.display = 'none';
                }, 3000);
            } else {
                emailInputError.style.display = 'none';
            }
            
            this.value = value;
        });
        
        // 붙여넣기 시에도 처리
        emailInput.addEventListener('paste', function(e) {
            setTimeout(() => {
                const originalValue = this.value;
                let value = originalValue.toLowerCase();
                const invalidChars = originalValue.match(/[^a-z0-9_\-@.A-Z]/g);
                
                value = value.replace(/[^a-z0-9_\-@.]/g, '');
                
                if (invalidChars && invalidChars.length > 0) {
                    const uniqueInvalidChars = [...new Set(invalidChars)];
                    emailInputError.textContent = `허용되지 않은 문자입니다: ${uniqueInvalidChars.join(', ')}. 영문자, 숫자, _, -, @, . 만 입력 가능합니다.`;
                    emailInputError.style.display = 'block';
                    setTimeout(() => {
                        emailInputError.style.display = 'none';
                    }, 3000);
                } else {
                    emailInputError.style.display = 'none';
                }
                
                this.value = value;
            }, 0);
        });
        
        // 포커스가 벗어나면 에러 메시지 숨김
        emailInput.addEventListener('blur', function() {
            setTimeout(() => {
                emailInputError.style.display = 'none';
            }, 200);
        });
    }
    
    // 인증번호 입력 시 자동 포맷팅
    if (verificationCodeInput) {
        verificationCodeInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
    }
    
    // 비밀번호 모달 열기
    if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', function() {
            passwordModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // 이메일이 있으면 인증 섹션 표시
            const userEmail = '<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>';
            if (userEmail && passwordEmailVerification) {
                passwordEmailVerification.style.display = 'block';
                passwordVerificationToken = null;
                passwordVerificationCode.value = '';
                passwordVerificationError.style.display = 'none';
                passwordVerificationSuccess.style.display = 'none';
            } else {
                passwordEmailVerification.style.display = 'none';
            }
            
            // 모든 비밀번호 필드를 숨김 상태로 초기화 (눈 아이콘은 항상 표시)
            if (newPassword) {
                newPassword.type = 'password';
                const iconNew = document.getElementById('iconNewPassword');
                if (iconNew) {
                    iconNew.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
                }
                const btnNew = document.getElementById('toggleNewPassword');
                if (btnNew) {
                    btnNew.style.color = '#6b7280';
                    btnNew.title = '비밀번호 표시';
                }
                newPassword.focus();
            }
            if (confirmPassword) {
                confirmPassword.type = 'password';
                const iconConfirm = document.getElementById('iconConfirmPassword');
                if (iconConfirm) {
                    iconConfirm.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
                }
                const btnConfirm = document.getElementById('toggleConfirmPassword');
                if (btnConfirm) {
                    btnConfirm.style.color = '#6b7280';
                    btnConfirm.title = '비밀번호 표시';
                }
            }
        });
    }
    
    // 비밀번호 변경용 인증번호 발송
    if (sendPasswordVerificationBtn) {
        sendPasswordVerificationBtn.addEventListener('click', function() {
            const userEmail = '<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>';
            if (!userEmail) {
                return;
            }
            
            passwordVerificationError.style.display = 'none';
            passwordVerificationSuccess.style.display = 'none';
            
            sendPasswordVerificationBtn.disabled = true;
            sendPasswordVerificationBtn.textContent = '발송 중...';
            
            // FormData 사용
            const formData = new FormData();
            formData.append('email', userEmail);
            formData.append('type', 'password_change');
            
            fetch(API_SEND_EMAIL_VERIFICATION, {
                method: 'POST',
                credentials: 'include', // 세션 쿠키 전송
                body: formData
            })
            .then(response => {
                return response.text().then(text => {
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        throw new Error('서버 응답 파싱 오류: ' + text.substring(0, 200));
                    }
                    
                    if (!response.ok) {
                        const error = new Error(data.message || '서버 오류: ' + response.status);
                        error.data = data;
                        error.status = response.status;
                        throw error;
                    }
                    
                    return data;
                });
            })
            .then(data => {
                sendPasswordVerificationBtn.disabled = false;
                sendPasswordVerificationBtn.textContent = '인증번호 발송';
                
                if (data.success) {
                    let successMessage = '인증번호가 발송되었습니다. 이메일을 확인해주세요.';
                    
                    // 개발 환경에서 인증번호가 반환된 경우 표시
                    if (data.development_mode && data.verification_code) {
                        const testUrl = '<?php echo getAssetPath("/admin/test-email-verification.php"); ?>';
                        successMessage = '인증번호가 생성되었습니다. (개발 환경)<br>' +
                                       '<strong style="font-size: 18px; color: #6366f1; letter-spacing: 3px;">인증번호: ' + data.verification_code + '</strong><br>' +
                                       '<small style="color: #6b7280;">또는 <a href="' + testUrl + '" target="_blank" style="color: #6366f1;">인증번호 확인 페이지</a>에서 확인하세요.</small>';
                        
                        // 자동 입력
                        if (passwordVerificationCode) {
                            passwordVerificationCode.value = data.verification_code;
                        }
                    }
                    
                    passwordVerificationSuccess.innerHTML = successMessage;
                    passwordVerificationSuccess.style.display = 'block';
                    if (passwordVerificationCode) {
                        passwordVerificationCode.focus();
                    }
                } else {
                    passwordVerificationError.textContent = data.message || '인증번호 발송에 실패했습니다.';
                    passwordVerificationError.style.display = 'block';
                    console.error('API 오류:', data);
                }
            })
            .catch(error => {
                sendPasswordVerificationBtn.disabled = false;
                sendPasswordVerificationBtn.textContent = '인증번호 발송';
                console.error('Fetch 오류:', error);
                
                if (error.data && error.data.message) {
                    passwordVerificationError.textContent = error.data.message;
                } else {
                    passwordVerificationError.textContent = '인증번호 발송 중 오류가 발생했습니다: ' + (error.message || '알 수 없는 오류');
                }
                passwordVerificationError.style.display = 'block';
            });
        });
    }
    
    // 비밀번호 변경용 인증번호 확인
    if (passwordVerificationCode) {
        passwordVerificationCode.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
            
            // 6자리 입력 시 자동 인증
            if (this.value.length === 6) {
                const userEmail = '<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>';
                if (!userEmail) {
                    return;
                }
                
                passwordVerificationError.style.display = 'none';
                passwordVerificationSuccess.style.display = 'none';
                
                // FormData 사용
                const formData = new FormData();
                formData.append('email', userEmail);
                formData.append('verification_code', this.value);
                formData.append('type', 'password_change');
                
                fetch(API_VERIFY_EMAIL_CODE, {
                    method: 'POST',
                    credentials: 'include', // 세션 쿠키 전송
                    body: formData
                })
                .then(response => {
                    return response.text().then(text => {
                        let data;
                        try {
                            data = JSON.parse(text);
                        } catch (e) {
                            throw new Error('서버 응답 파싱 오류: ' + text.substring(0, 200));
                        }
                        
                        if (!response.ok) {
                            const error = new Error(data.message || '서버 오류: ' + response.status);
                            error.data = data;
                            throw error;
                        }
                        
                        return data;
                    });
                })
                .then(data => {
                    if (data.success) {
                        passwordVerificationToken = data.verification_token;
                        passwordVerificationSuccess.textContent = '인증이 완료되었습니다.';
                        passwordVerificationSuccess.style.display = 'block';
                    } else {
                        passwordVerificationError.textContent = data.message || '인증번호가 일치하지 않습니다.';
                        passwordVerificationError.style.display = 'block';
                    }
                })
                .catch(error => {
                    if (error.data && error.data.message) {
                        passwordVerificationError.textContent = error.data.message;
                    } else {
                        passwordVerificationError.textContent = '인증 처리 중 오류가 발생했습니다: ' + (error.message || '알 수 없는 오류');
                    }
                    passwordVerificationError.style.display = 'block';
                });
            }
        });
    }
    
    // 비밀번호 모달 닫기
    if (closePasswordModal) {
        closePasswordModal.addEventListener('click', function() {
            closeModal(passwordModal);
            passwordForm.reset();
            newPasswordError.style.display = 'none';
            confirmPasswordError.style.display = 'none';
        });
    }
    
    if (cancelPasswordBtn) {
        cancelPasswordBtn.addEventListener('click', function() {
            closeModal(passwordModal);
            passwordForm.reset();
            newPasswordError.style.display = 'none';
            confirmPasswordError.style.display = 'none';
        });
    }
    
    // 비밀번호 입력 필터링 (영문자, 숫자, 특수문자만 허용, 공백 불가)
    function filterPasswordInput(input) {
        // 공백 제거 및 영문자, 숫자, 특수문자만 허용
        let value = input.value.replace(/\s/g, ''); // 공백 제거
        value = value.replace(/[^a-zA-Z0-9!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/g, ''); // 허용된 문자만 남김
        input.value = value;
    }
    
    // 새 비밀번호 실시간 유효성 검사 및 필터링
    if (newPassword) {
        newPassword.addEventListener('input', function() {
            filterPasswordInput(this);
            
            const pwd = this.value;
            if (pwd.length === 0) {
                newPasswordError.style.display = 'none';
                return;
            }
            
            if (pwd.length < 8) {
                newPasswordError.textContent = '비밀번호는 8자 이상이어야 합니다.';
                newPasswordError.style.display = 'block';
                return;
            }
            
            const hasLetter = /[a-zA-Z]/.test(pwd);
            const hasNumber = /[0-9]/.test(pwd);
            const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd);
            
            const typeCount = [hasLetter, hasNumber, hasSpecial].filter(Boolean).length;
            
            if (typeCount < 2) {
                newPasswordError.textContent = '영문자, 숫자, 특수문자 중 2가지 이상을 조합해주세요.';
                newPasswordError.style.display = 'block';
            } else {
                newPasswordError.style.display = 'none';
            }
        });
        newPassword.addEventListener('paste', function() {
            setTimeout(() => {
                filterPasswordInput(this);
            }, 0);
        });
    }
    
    // 새 비밀번호 확인 입력 필터링
    if (confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            filterPasswordInput(this);
        });
        confirmPassword.addEventListener('paste', function() {
            setTimeout(() => {
                filterPasswordInput(this);
            }, 0);
        });
    }
    
    // 비밀번호 확인 실시간 검증
    if (confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            if (newPassword.value && confirmPassword.value) {
                if (newPassword.value !== confirmPassword.value) {
                    confirmPasswordError.textContent = '비밀번호가 일치하지 않습니다.';
                    confirmPasswordError.style.display = 'block';
                } else {
                    confirmPasswordError.style.display = 'none';
                }
            }
        });
    }
    
    // 비밀번호 폼 제출
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const newPwd = newPassword.value;
            const confirmPwd = confirmPassword.value;
            
            // 유효성 검사
            let isValid = true;
            
            // 비밀번호 유효성 검사: 8자 이상, 영문자/숫자/특수문자 중 2가지 이상 조합
            let passwordErrorMsg = '';
            if (newPwd.length < 8) {
                passwordErrorMsg = '비밀번호는 8자 이상이어야 합니다.';
            } else {
                const hasLetter = /[a-zA-Z]/.test(newPwd);
                const hasNumber = /[0-9]/.test(newPwd);
                const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(newPwd);
                
                const typeCount = [hasLetter, hasNumber, hasSpecial].filter(Boolean).length;
                
                if (typeCount < 2) {
                    passwordErrorMsg = '영문자, 숫자, 특수문자 중 2가지 이상을 조합해주세요.';
                }
            }
            
            if (passwordErrorMsg) {
                newPasswordError.textContent = passwordErrorMsg;
                newPasswordError.style.display = 'block';
                isValid = false;
            } else {
                newPasswordError.style.display = 'none';
            }
            
            if (newPwd !== confirmPwd) {
                confirmPasswordError.textContent = '비밀번호가 일치하지 않습니다.';
                confirmPasswordError.style.display = 'block';
                isValid = false;
            } else {
                confirmPasswordError.style.display = 'none';
            }
            
            if (!isValid) {
                return;
            }
            
            // 이메일 인증 확인 (이메일이 있는 경우)
            const userEmail = '<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>';
            if (userEmail && !passwordVerificationToken) {
                passwordVerificationError.textContent = '이메일 인증이 필요합니다.';
                passwordVerificationError.style.display = 'block';
                return;
            }
            
            // FormData 사용
            const formData = new FormData();
            formData.append('new_password', newPwd);
            
            if (passwordVerificationToken) {
                formData.append('verification_token', passwordVerificationToken);
            }
            
            fetch(API_CHANGE_PASSWORD, {
                method: 'POST',
                credentials: 'include', // 세션 쿠키 전송
                body: formData
            })
            .then(response => {
                return response.text().then(text => {
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        throw new Error('서버 응답 파싱 오류: ' + text.substring(0, 200));
                    }
                    
                    if (!response.ok) {
                        const error = new Error(data.message || '서버 오류: ' + response.status);
                        error.data = data;
                        throw error;
                    }
                    
                    return data;
                });
            })
            .then(data => {
                if (data.success) {
                    closeModal(passwordModal);
                    passwordForm.reset();
                    passwordVerificationToken = null;
                    if (passwordVerificationCode) passwordVerificationCode.value = '';
                    showAlert('비밀번호가 변경되었습니다.');
                } else {
                    if (data.requires_email_verification) {
                        passwordVerificationError.textContent = data.message || '이메일 인증이 필요합니다.';
                        passwordVerificationError.style.display = 'block';
                    } else {
                        showAlert(data.message || '비밀번호 변경에 실패했습니다.');
                    }
                }
            })
            .catch(error => {
                if (error.data && error.data.message) {
                    showAlert('비밀번호 변경 오류: ' + error.data.message);
                } else {
                    showAlert('비밀번호 변경 중 오류가 발생했습니다: ' + (error.message || '알 수 없는 오류'));
                }
            });
        });
    }
    
    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (emailModal.style.display === 'flex') {
                closeModal(emailModal);
                emailForm.reset();
                emailError.style.display = 'none';
            }
            if (passwordModal.style.display === 'flex') {
                closeModal(passwordModal);
                passwordForm.reset();
                newPasswordError.style.display = 'none';
                confirmPasswordError.style.display = 'none';
            }
        }
    });
});
</script>

<?php
// 푸터 포함
include '../includes/footer.php';
?>


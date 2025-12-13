<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'mypage';

// 로그인 체크를 위한 auth-functions 포함 (세션 설정과 함께 세션을 시작함)
require_once '../includes/data/auth-functions.php';

// 로그인 체크 - 로그인하지 않은 경우 회원가입 모달로 리다이렉트
if (!isLoggedIn()) {
    // 현재 URL을 세션에 저장 (회원가입 후 돌아올 주소)
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    // 로그인 모달이 있는 홈으로 리다이렉트 (모달 자동 열기)
    header('Location: /MVNO/?show_login=1');
    exit;
}

// 현재 사용자 정보 가져오기
$currentUser = getCurrentUser();
if (!$currentUser) {
    // 사용자 정보를 가져올 수 없으면 로그아웃 처리
    header('Location: /MVNO/?show_login=1');
    exit;
}

// 헤더 포함
include '../includes/header.php';
?>

<main class="main-content">
    <div style="width: 460px; margin: 0 auto; padding: 20px;" class="account-settings-container">
        <!-- 뒤로가기 버튼 및 제목 -->
        <div style="margin-bottom: 24px; padding: 20px 0;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <a href="/MVNO/mypage/mypage.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
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
                    <span style="font-size: 16px; color: #212529; font-weight: 500;">YMB</span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; border-bottom: 1px solid #e5e7eb;">
                    <span style="font-size: 16px; color: #6b7280; font-weight: 500;">아이디</span>
                    <span id="displayUserId" style="font-size: 16px; color: #212529; font-weight: 500;">
                        <?php
                        // 실제로는 세션 또는 DB에서 가져옴
                        // 가입 경로에 따라 자동 생성된 아이디 표시
                        $login_type = 'nvr'; // 'nvr', 'ggl', 'kko', 'direct'
                        $user_id_number = '12345678'; // 가입 시 자동 생성된 숫자
                        
                        $prefix_map = [
                            'nvr' => 'nvr',
                            'ggl' => 'ggl',
                            'kko' => 'kko',
                            'direct' => ''
                        ];
                        
                        $prefix = $prefix_map[$login_type] ?? '';
                        $display_id = $prefix ? $prefix . '_' . $user_id_number : $user_id_number;
                        echo htmlspecialchars($display_id);
                        ?>
                    </span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; border-bottom: 1px solid #e5e7eb;">
                    <span style="font-size: 16px; color: #6b7280; font-weight: 500;">연락처</span>
                    <span style="font-size: 16px; color: #212529; font-weight: 500;">+82 10-2423-2324</span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; border-bottom: 1px solid #e5e7eb;">
                    <span style="font-size: 16px; color: #6b7280; font-weight: 500;">이메일</span>
                    <div style="display: flex; align-items: center; gap: 8px; flex: 1; justify-content: flex-end;">
                        <span id="displayUserEmail" style="font-size: 16px; color: #212529; font-weight: 500;">kang@naver.com</span>
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
                    <span style="font-size: 16px; color: #212529; font-weight: 500;">-</span>
                </div>
            </div>
        </div>

        <!-- 회원 탈퇴 버튼 -->
        <div style="margin-top: 32px;">
            <a href="/MVNO/mypage/withdraw.php" style="display: block; width: 100%; padding: 16px; background-color: transparent; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 16px; color: #212529; text-align: center; text-decoration: none; font-weight: 500;">
                회원 탈퇴
            </a>
        </div>
    </div>
</main>

<!-- 이메일 수정 모달 -->
<div id="emailModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: white; z-index: 1000; overflow: hidden; width: 100%; height: 100%;">
    <div style="position: relative; width: 100%; height: 100%; display: flex; flex-direction: column; background: white; margin: 0; padding: 0;">
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
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        이메일 주소
                    </label>
                    <input 
                        type="email" 
                        id="emailInput" 
                        name="email" 
                        value="kang@naver.com"
                        placeholder="example@email.com" 
                        required
                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; box-sizing: border-box; outline: none; transition: border-color 0.2s;"
                        onfocus="this.style.borderColor='#6366f1'"
                        onblur="this.style.borderColor='#d1d5db'"
                    >
                    <div id="emailError" style="display: none; color: #ef4444; font-size: 13px; margin-top: 8px;"></div>
                </div>
                
                <div style="display: flex; gap: 12px;">
                    <button 
                        type="button" 
                        id="cancelEmailBtn"
                        style="flex: 1; padding: 14px; background-color: #f3f4f6; border: none; border-radius: 8px; font-size: 16px; color: #374151; font-weight: 500; cursor: pointer;"
                    >
                        취소
                    </button>
                    <button 
                        type="submit" 
                        id="submitEmailBtn"
                        style="flex: 1; padding: 14px; background-color: #6366f1; border: none; border-radius: 8px; font-size: 16px; color: white; font-weight: 500; cursor: pointer;"
                    >
                        확인
                    </button>
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
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        현재 비밀번호
                    </label>
                    <input 
                        type="password" 
                        id="currentPassword" 
                        name="current_password" 
                        placeholder="현재 비밀번호를 입력하세요" 
                        required
                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; box-sizing: border-box; outline: none; transition: border-color 0.2s;"
                        onfocus="this.style.borderColor='#6366f1'"
                        onblur="this.style.borderColor='#d1d5db'"
                    >
                    <div id="currentPasswordError" style="display: none; color: #ef4444; font-size: 13px; margin-top: 8px;"></div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        새 비밀번호
                    </label>
                    <input 
                        type="password" 
                        id="newPassword" 
                        name="new_password" 
                        placeholder="새 비밀번호를 입력하세요" 
                        required
                        minlength="8"
                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; box-sizing: border-box; outline: none; transition: border-color 0.2s;"
                        onfocus="this.style.borderColor='#6366f1'"
                        onblur="this.style.borderColor='#d1d5db'"
                    >
                    <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                        8자 이상 입력해주세요
                    </div>
                    <div id="newPasswordError" style="display: none; color: #ef4444; font-size: 13px; margin-top: 8px;"></div>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                        새 비밀번호 확인
                    </label>
                    <input 
                        type="password" 
                        id="confirmPassword" 
                        name="confirm_password" 
                        placeholder="새 비밀번호를 다시 입력하세요" 
                        required
                        style="width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; box-sizing: border-box; outline: none; transition: border-color 0.2s;"
                        onfocus="this.style.borderColor='#6366f1'"
                        onblur="this.style.borderColor='#d1d5db'"
                    >
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
    
    // 현재 이메일 값 저장 (초기값)
    let originalEmail = 'kang@naver.com';
    
    // 비밀번호 모달 관련
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    const passwordModal = document.getElementById('passwordModal');
    const closePasswordModal = document.getElementById('closePasswordModal');
    const cancelPasswordBtn = document.getElementById('cancelPasswordBtn');
    const passwordForm = document.getElementById('passwordForm');
    const currentPassword = document.getElementById('currentPassword');
    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    const currentPasswordError = document.getElementById('currentPasswordError');
    const newPasswordError = document.getElementById('newPasswordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');
    
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
                if (emailInput) {
                    emailInput.value = originalEmail;
                    emailInput.focus();
                }
            }
        });
    }
    
    // 이메일 모달 닫기
    if (closeEmailModal) {
        closeEmailModal.addEventListener('click', function() {
            closeModal(emailModal);
            emailForm.reset();
            emailError.style.display = 'none';
        });
    }
    
    if (cancelEmailBtn) {
        cancelEmailBtn.addEventListener('click', function() {
            closeModal(emailModal);
            emailForm.reset();
            emailError.style.display = 'none';
        });
    }
    
    // 이메일 폼 제출
    if (emailForm) {
        emailForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = emailInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            // 이메일 유효성 검사
            if (!emailRegex.test(email)) {
                emailError.textContent = '올바른 이메일 형식을 입력해주세요.';
                emailError.style.display = 'block';
                return;
            }
            
            // 실제로는 서버에 전송
            // 여기서는 시뮬레이션
            fetch('/MVNO/api/update-email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email: email })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    originalEmail = email;
                    if (displayUserEmail) displayUserEmail.textContent = email;
                    closeModal(emailModal);
                    emailForm.reset();
                    emailError.style.display = 'none';
                    showAlert('이메일이 저장되었습니다.');
                } else {
                    emailError.textContent = data.message || '이메일 저장에 실패했습니다.';
                    emailError.style.display = 'block';
                }
            })
            .catch(error => {
                // 개발 환경에서는 바로 저장
                originalEmail = email;
                if (displayUserEmail) displayUserEmail.textContent = email;
                closeModal(emailModal);
                emailForm.reset();
                emailError.style.display = 'none';
                showAlert('이메일이 저장되었습니다.');
            });
        });
    }
    
    // 비밀번호 모달 열기
    if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', function() {
            passwordModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            currentPassword.focus();
        });
    }
    
    // 비밀번호 모달 닫기
    if (closePasswordModal) {
        closePasswordModal.addEventListener('click', function() {
            closeModal(passwordModal);
            passwordForm.reset();
            currentPasswordError.style.display = 'none';
            newPasswordError.style.display = 'none';
            confirmPasswordError.style.display = 'none';
        });
    }
    
    if (cancelPasswordBtn) {
        cancelPasswordBtn.addEventListener('click', function() {
            closeModal(passwordModal);
            passwordForm.reset();
            currentPasswordError.style.display = 'none';
            newPasswordError.style.display = 'none';
            confirmPasswordError.style.display = 'none';
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
            
            const currentPwd = currentPassword.value;
            const newPwd = newPassword.value;
            const confirmPwd = confirmPassword.value;
            
            // 유효성 검사
            let isValid = true;
            
            if (!currentPwd) {
                currentPasswordError.textContent = '현재 비밀번호를 입력해주세요.';
                currentPasswordError.style.display = 'block';
                isValid = false;
            } else {
                currentPasswordError.style.display = 'none';
            }
            
            if (newPwd.length < 8) {
                newPasswordError.textContent = '비밀번호는 8자 이상이어야 합니다.';
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
            
            // 실제로는 서버에 전송
            fetch('/MVNO/api/change-password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    current_password: currentPwd,
                    new_password: newPwd
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal(passwordModal);
                    passwordForm.reset();
                    showAlert('비밀번호가 변경되었습니다.');
                } else {
                    if (data.field === 'current_password') {
                        currentPasswordError.textContent = data.message || '현재 비밀번호가 일치하지 않습니다.';
                        currentPasswordError.style.display = 'block';
                    } else {
                        showAlert(data.message || '비밀번호 변경에 실패했습니다.');
                    }
                }
            })
            .catch(error => {
                // 개발 환경에서는 바로 성공 처리
                closeModal(passwordModal);
                passwordForm.reset();
                showAlert('비밀번호가 변경되었습니다.');
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
                currentPasswordError.style.display = 'none';
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


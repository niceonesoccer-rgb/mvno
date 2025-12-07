<?php
/**
 * SNS 회원가입 후 추가 정보 입력 페이지
 * 이름, 이메일, 전화번호 필수 입력
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';

// 로그인 확인
if (!isLoggedIn()) {
    header('Location: /MVNO/auth/login.php');
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['role'] !== 'user') {
    header('Location: /MVNO/');
    exit;
}

// 이미 전화번호가 있으면 메인으로 리다이렉트
if (!empty($currentUser['phone'])) {
    header('Location: /MVNO/');
    exit;
}

$error = '';
$success = false;

// 기본값 설정 (SNS에서 받은 정보)
$defaultName = $currentUser['name'] ?? '';
$defaultEmail = $currentUser['email'] ?? '';
$defaultPhone = $currentUser['phone'] ?? '';

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>추가 정보 입력 - 모요</title>
    <link rel="stylesheet" href="/MVNO/assets/css/style.css">
    <style>
        .complete-profile-container {
            max-width: 500px;
            margin: 60px auto;
            padding: 40px 24px;
        }
        
        .complete-profile-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .complete-profile-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .complete-profile-header p {
            font-size: 14px;
            color: #6b7280;
        }
        
        .error-message {
            padding: 12px 16px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
        }
        
        .success-message {
            padding: 12px 16px;
            background: #d1fae5;
            color: #065f46;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
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
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #6366f1;
        }
        
        .form-group input.error {
            border-color: #ef4444;
        }
        
        .form-help {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .submit-button {
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
        
        .submit-button:hover {
            background: #4f46e5;
        }
        
        .submit-button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="complete-profile-container">
        <div class="complete-profile-header">
            <h1>추가 정보 입력</h1>
            <p>SNS 로그인 후 추가 정보를 입력해주세요</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message" id="errorMessage">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">
                정보가 성공적으로 저장되었습니다. 잠시 후 메인 페이지로 이동합니다.
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = '/MVNO/';
                }, 2000);
            </script>
        <?php else: ?>
            <form id="completeProfileForm" method="POST" action="/MVNO/api/update-user-phone.php">
                <div class="form-group">
                    <label for="name">이름 *</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="<?php echo htmlspecialchars($defaultName); ?>"
                        required
                        maxlength="50"
                    >
                    <div class="form-help">실명을 입력해주세요</div>
                </div>
                
                <div class="form-group">
                    <label for="email">이메일 *</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($defaultEmail); ?>"
                        required
                    >
                    <div class="form-help">정확한 이메일 주소를 입력해주세요</div>
                </div>
                
                <div class="form-group">
                    <label for="phone">휴대폰 번호 *</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        value="<?php echo htmlspecialchars($defaultPhone); ?>"
                        placeholder="010-1234-5678"
                        pattern="010-\d{4}-\d{4}"
                        maxlength="13"
                        required
                    >
                    <div class="form-help">010으로 시작하는 휴대폰 번호를 입력해주세요 (예: 010-1234-5678)</div>
                </div>
                
                <button type="submit" class="submit-button" id="submitButton">
                    완료
                </button>
            </form>
        <?php endif; ?>
    </div>
    
    <script>
        const phoneInput = document.getElementById('phone');
        const form = document.getElementById('completeProfileForm');
        const submitButton = document.getElementById('submitButton');
        
        // 전화번호 자동 포맷팅
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\d]/g, '');
            
            // 010으로 시작하도록 강제
            if (value.length > 0 && !value.startsWith('010')) {
                value = '010' + value.replace(/^010/, '');
            }
            
            // 11자리 제한
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            
            // 하이픈 추가
            if (value.length > 3) {
                value = value.substring(0, 3) + '-' + value.substring(3);
            }
            if (value.length > 8) {
                value = value.substring(0, 8) + '-' + value.substring(8, 12);
            }
            
            e.target.value = value;
            
            // 형식 검증
            const phoneNumbers = value.replace(/[^\d]/g, '');
            if (phoneNumbers.length === 11 && /^010\d{8}$/.test(phoneNumbers)) {
                e.target.classList.remove('error');
            } else if (phoneNumbers.length > 0) {
                e.target.classList.add('error');
            } else {
                e.target.classList.remove('error');
            }
        });
        
        // 포커스 시 자동 입력
        phoneInput.addEventListener('focus', function(e) {
            if (!e.target.value || !e.target.value.startsWith('010')) {
                e.target.value = '010-';
            }
        });
        
        // 블러 시 형식 검증
        phoneInput.addEventListener('blur', function(e) {
            const phoneNumbers = e.target.value.replace(/[^\d]/g, '');
            if (phoneNumbers.length > 0 && (!/^010\d{8}$/.test(phoneNumbers))) {
                e.target.classList.add('error');
            }
        });
        
        // 폼 제출
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = phoneInput.value.trim();
            const phoneNumbers = phone.replace(/[^\d]/g, '');
            
            // 검증
            if (!name) {
                showError('이름을 입력해주세요.');
                return;
            }
            
            if (!email) {
                showError('이메일을 입력해주세요.');
                return;
            }
            
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showError('올바른 이메일 형식이 아닙니다.');
                return;
            }
            
            if (!/^010\d{8}$/.test(phoneNumbers)) {
                showError('휴대폰 번호는 010으로 시작하는 11자리 숫자여야 합니다.');
                phoneInput.classList.add('error');
                return;
            }
            
            // 제출 버튼 비활성화
            submitButton.disabled = true;
            submitButton.textContent = '처리 중...';
            
            // AJAX로 전송
            const formData = new FormData(form);
            
            fetch('/MVNO/api/update-user-phone.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 성공 메시지 표시
                    const errorDiv = document.getElementById('errorMessage');
                    if (errorDiv) {
                        errorDiv.style.display = 'none';
                    }
                    
                    const successDiv = document.createElement('div');
                    successDiv.className = 'success-message';
                    successDiv.textContent = data.message + ' 잠시 후 메인 페이지로 이동합니다.';
                    form.parentNode.insertBefore(successDiv, form);
                    form.style.display = 'none';
                    
                    // 메인 페이지로 리다이렉트
                    setTimeout(function() {
                        window.location.href = '/MVNO/';
                    }, 2000);
                } else {
                    showError(data.message || '정보 저장에 실패했습니다.');
                    submitButton.disabled = false;
                    submitButton.textContent = '완료';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.');
                submitButton.disabled = false;
                submitButton.textContent = '완료';
            });
        });
        
        function showError(message) {
            let errorDiv = document.getElementById('errorMessage');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.id = 'errorMessage';
                errorDiv.className = 'error-message';
                form.parentNode.insertBefore(errorDiv, form);
            }
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            
            // 스크롤 to error
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    </script>
</body>
</html>

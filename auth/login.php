<?php
/**
 * 로그인 페이지
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';

// 이미 로그인한 경우 리다이렉트
if (isLoggedIn()) {
    header('Location: /MVNO/');
    exit;
}

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
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인 - 모요</title>
    <link rel="stylesheet" href="/MVNO/assets/css/style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 60px auto;
            padding: 40px 24px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .login-header p {
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
        
        .sns-login-section {
            margin-bottom: 32px;
        }
        
        .sns-login-title {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 16px;
            text-align: center;
        }
        
        .sns-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .sns-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 14px 20px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            font-size: 15px;
            font-weight: 500;
            color: #1f2937;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .sns-button:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }
        
        .sns-button.naver {
            background: #03c75a;
            color: white;
            border-color: #03c75a;
        }
        
        .sns-button.naver:hover {
            background: #02b350;
        }
        
        .sns-button.kakao {
            background: #fee500;
            color: #000000;
            border-color: #fee500;
        }
        
        .sns-button.kakao:hover {
            background: #fdd835;
        }
        
        .sns-button.google {
            background: white;
            color: #1f2937;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 32px 0;
            color: #9ca3af;
            font-size: 14px;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }
        
        .divider span {
            padding: 0 16px;
        }
        
        .direct-login-section {
            margin-top: 32px;
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
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #6366f1;
        }
        
        .login-button {
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
        
        .login-button:hover {
            background: #4f46e5;
        }
        
        .register-link {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: #6b7280;
        }
        
        .register-link a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 500;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>로그인</h1>
            <p>모요에 오신 것을 환영합니다</p>
        </div>
        
        <?php if ($errorMessage): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>
        
        <!-- 직접 로그인 (관리자, 서브관리자, 판매자용) -->
        <div class="direct-login-section">
            <form id="directLoginForm" method="POST" action="/MVNO/api/direct-login.php">
                <div class="form-group">
                    <label for="user_id">아이디</label>
                    <input type="text" id="user_id" name="user_id" value="admin" required>
                </div>
                <div class="form-group">
                    <label for="password">비밀번호</label>
                    <input type="password" id="password" name="password" value="000000" required>
                </div>
                <button type="submit" class="login-button">로그인</button>
            </form>
            
            <div class="register-link">
                계정이 없으신가요? <a href="/MVNO/auth/register.php">회원가입</a>
            </div>
            <div class="register-link" style="margin-top: 12px; font-size: 13px; color: #9ca3af;">
                일반 회원은 SNS 로그인을 통해 가입해주세요.
            </div>
        </div>
    </div>
    
    <script>
        // 직접 로그인 폼 처리
        document.getElementById('directLoginForm').addEventListener('submit', function(e) {
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
                    showAlert(data.message || '로그인에 실패했습니다.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('로그인 중 오류가 발생했습니다.');
            });
        });
    </script>
</body>
</html>


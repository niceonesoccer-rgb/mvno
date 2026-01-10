<?php
/**
 * 로그인 페이지
 */

require_once __DIR__ . '/../includes/data/path-config.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

// 이미 로그인한 경우 리다이렉트
if (isLoggedIn()) {
    // 세션에 저장된 리다이렉트 URL 확인
    $redirectUrl = $_SESSION['redirect_url'] ?? getAssetPath('/');
    if (isset($_SESSION['redirect_url'])) {
        unset($_SESSION['redirect_url']);
    }
    header('Location: ' . $redirectUrl);
    exit;
}

// 로그인 페이지 접근 시 현재 URL을 리다이렉트 URL로 저장 (GET 파라미터로 전달된 경우)
if (isset($_GET['redirect'])) {
    $_SESSION['redirect_url'] = $_GET['redirect'];
} elseif (!isset($_SESSION['redirect_url'])) {
    // 리다이렉트 URL이 없으면 현재 페이지 URL 저장 (쿼리 파라미터 제외)
    $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') 
        . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    // 로그인 페이지 자체는 제외하고, 이전 페이지가 있으면 그걸 사용
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (!empty($referer) && strpos($referer, '/auth/login.php') === false) {
        $_SESSION['redirect_url'] = $referer;
    }
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
$isRegisterMode = isset($_GET['register']) && $_GET['register'] === 'true';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인 - 유심킹</title>
    <link rel="stylesheet" href="<?php echo getAssetPath('/assets/css/style.css'); ?>">
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
            <h1><?php echo $isRegisterMode ? '회원가입' : '로그인'; ?></h1>
            <p>유심킹에 오신 것을 환영합니다</p>
        </div>
        
        <?php if ($errorMessage): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>
        
        <!-- SNS 로그인/가입 -->
        <div class="sns-login-section">
            <div class="sns-login-title"><?php echo $isRegisterMode ? 'SNS로 회원가입' : 'SNS로 로그인'; ?></div>
            <div class="sns-buttons">
                <button type="button" class="sns-button naver" onclick="snsLogin('naver')">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 0C4.477 0 0 4.477 0 10s4.477 10 10 10 10-4.477 10-10S15.523 0 10 0zm4.5 5.5h-2v9h-5v-9h-2v11h9v-11z"/>
                    </svg>
                    네이버로 <?php echo $isRegisterMode ? '가입' : '로그인'; ?>
                </button>
                <button type="button" class="sns-button kakao" onclick="snsLogin('kakao')">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 0C4.477 0 0 4.477 0 10c0 3.55 2.186 6.59 5.3 7.93L3.5 20l3.2-1.77C7.5 18.33 8.71 18.5 10 18.5c5.523 0 10-4.477 10-10S15.523 0 10 0z"/>
                    </svg>
                    카카오로 <?php echo $isRegisterMode ? '가입' : '로그인'; ?>
                </button>
                <button type="button" class="sns-button google" onclick="snsLogin('google')">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M19.6 10.227c0-.709-.064-1.39-.182-2.045H10v3.868h5.382a4.6 4.6 0 01-1.996 3.018v2.51h3.232c1.891-1.742 2.982-4.305 2.982-7.35z" fill="#4285F4"/>
                        <path d="M10 20c2.7 0 4.964-.895 6.618-2.423l-3.232-2.509c-.895.6-2.04.955-3.386.955-2.605 0-4.81-1.76-5.595-4.123H1.064v2.59A9.996 9.996 0 0010 20z" fill="#34A853"/>
                        <path d="M4.405 11.914c-.2-.6-.314-1.24-.314-1.914 0-.673.114-1.314.314-1.914V5.496H1.064A9.996 9.996 0 000 10c0 1.614.386 3.14 1.064 4.504l3.34-2.59z" fill="#FBBC05"/>
                        <path d="M10 3.977c1.468 0 2.786.505 3.823 1.496l2.868-2.868C14.959.99 12.695 0 10 0 6.09 0 2.71 2.24 1.064 5.496l3.34 2.59C5.19 5.732 7.395 3.977 10 3.977z" fill="#EA4335"/>
                    </svg>
                    구글로 <?php echo $isRegisterMode ? '가입' : '로그인'; ?>
                </button>
            </div>
        </div>
        
        <?php if (!$isRegisterMode): ?>
        <div class="divider">
            <span>또는</span>
        </div>
        
        <!-- 직접 로그인 (일반 회원, 관리자, 서브관리자, 판매자용) -->
        <div class="direct-login-section">
            <form id="directLoginForm" method="POST" action="/MVNO/api/direct-login.php">
                <div class="form-group">
                    <label for="user_id">아이디</label>
                    <input type="text" id="user_id" name="user_id" required>
                </div>
                <div class="form-group">
                    <label for="password">비밀번호</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="login-button">로그인</button>
            </form>
            
            <div class="register-link">
                계정이 없으신가요? <a href="#" onclick="openLoginModal(true); return false;">회원가입</a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php
    // 모달 기반 회원가입/로그인을 사용 (register.php 페이지 제거용)
    require_once __DIR__ . '/../includes/components/login-modal.php';
    ?>
    
    <script>
        function snsLogin(provider) {
            fetch('<?php echo getApiPath('/api/sns-login.php'); ?>?action=' + provider)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.auth_url;
                    } else {
                        alert(data.message || '<?php echo $isRegisterMode ? '가입' : '로그인'; ?>에 실패했습니다.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('<?php echo $isRegisterMode ? '가입' : '로그인'; ?> 중 오류가 발생했습니다.');
                });
        }
        
        <?php if (!$isRegisterMode): ?>
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
                    alert(data.message || '로그인에 실패했습니다.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('로그인 중 오류가 발생했습니다.');
            });
        });
        <?php endif; ?>

        // /auth/login.php?register=true 로 접근 시 회원가입 모달 자동 오픈
        <?php if ($isRegisterMode): ?>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof openLoginModal === 'function') {
                openLoginModal(true);
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>


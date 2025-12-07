<?php
/**
 * 판매자 전용 로그인 페이지
 * 경로: /MVNO/seller/login.php
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 이미 로그인한 판매자는 판매자 센터로 리다이렉트
$currentUser = getCurrentUser();
if ($currentUser && $currentUser['role'] === 'seller') {
    header('Location: /MVNO/seller/');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = trim($_POST['user_id'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($userId) || empty($password)) {
        $error = '아이디와 비밀번호를 입력해주세요.';
    } else {
        $result = loginDirectUser($userId, $password);
        if ($result['success']) {
            $user = $result['user'];
            // 판매자만 로그인 허용
            if ($user['role'] === 'seller') {
                // 승인 상태 확인
                $isApproved = isset($user['seller_approved']) && $user['seller_approved'] === true;
                
                if (!$isApproved) {
                    // 승인되지 않은 경우 로그인 제한
                    logoutUser();
                    $error = '승인 대기중입니다. 관리자 승인 후 로그인할 수 있습니다.';
                } else {
                    header('Location: /MVNO/seller/');
                    exit;
                }
            } else {
                $error = '판매자만 로그인할 수 있습니다.';
                logoutUser();
            }
        } else {
            $error = $result['message'] ?? '로그인에 실패했습니다.';
        }
    }
}

// 판매자 로그인 페이지는 헤더 없음
?>

<style>
    .seller-login-container {
        max-width: 450px;
        margin: 60px auto;
        padding: 40px 24px;
    }
    
    .login-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .login-header h1 {
        font-size: 32px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 12px;
    }
    
    .login-header p {
        font-size: 16px;
        color: #6b7280;
    }
    
    .login-form {
        background: white;
        border-radius: 12px;
        padding: 32px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .error-message {
        padding: 12px 16px;
        background: #fee2e2;
        color: #991b1b;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
        border: 1px solid #ef4444;
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
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    .login-button {
        width: 100%;
        padding: 14px;
        background: #6366f1;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
        margin-top: 8px;
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

<main class="main-content">
    <div class="seller-login-container">
        <div class="login-header">
            <h1>판매자 로그인</h1>
            <p>판매자 센터에 로그인하세요</p>
        </div>
        
        <div class="login-form">
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label for="user_id">아이디</label>
                    <input type="text" id="user_id" name="user_id" value="57575757" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">비밀번호</label>
                    <input type="password" id="password" name="password" value="57575757" required>
                </div>
                
                <button type="submit" class="login-button">로그인</button>
            </form>
            
            <div class="register-link">
                계정이 없으신가요? <a href="/MVNO/seller/register.php">판매자 가입</a>
            </div>
        </div>
    </div>
</main>


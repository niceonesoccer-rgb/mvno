<?php
/**
 * 관리자 전용 로그인 페이지
 * 경로: /MVNO/admin/login.php
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 이미 로그인한 관리자는 관리자 센터로 리다이렉트
$currentUser = getCurrentUser();
if ($currentUser && isAdmin($currentUser['user_id'])) {
    header('Location: /MVNO/admin/');
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
            // 관리자/부관리자만 로그인 허용
            if ($user['role'] === 'admin' || $user['role'] === 'sub_admin') {
                header('Location: /MVNO/admin/');
                exit;
            } else {
                $error = '관리자만 로그인할 수 있습니다.';
                logoutUser();
            }
        } else {
            $error = $result['message'] ?? '로그인에 실패했습니다.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 로그인 - 유심킹</title>
    <link rel="stylesheet" href="/MVNO/assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .admin-login-container {
            max-width: 450px;
            width: 100%;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
            color: white;
        }
        
        .login-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        
        .login-header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .login-form {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
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
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="login-header">
            <h1>관리자 로그인</h1>
            <p>관리자 센터에 로그인하세요</p>
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
                    <input type="text" id="user_id" name="user_id" value="admin" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">비밀번호</label>
                    <input type="password" id="password" name="password" value="admin" required>
                </div>
                
                <button type="submit" class="login-button">로그인</button>
            </form>
        </div>
    </div>
</body>
</html>







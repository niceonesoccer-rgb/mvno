<?php
/**
 * Admin 계정 복구 스크립트
 * admin / admin 계정을 생성합니다.
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 관리자 권한 체크 (또는 직접 실행 가능하도록)
$isDirectAccess = isset($_GET['direct']) && $_GET['direct'] === 'yes';

if (!$isDirectAccess && !isAdmin()) {
    // 직접 접근이 아니고 관리자도 아니면 로그인 페이지로
    header('Location: /MVNO/admin/');
    exit;
}

$success = false;
$error = '';

// admin 계정 생성
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $isDirectAccess) {
    try {
        // DB-only: users 테이블에서 admin 계정 확인 후 생성
        $existing = getUserById('admin');
        if ($existing && (($existing['role'] ?? '') === 'admin' || ($existing['role'] ?? '') === 'sub_admin')) {
            $error = 'admin 계정이 이미 존재합니다.';
        } else {
            $additional = [
                'phone' => '010-0000-0000',
                'created_by' => 'system',
                'memo' => 'restored_by_script'
            ];

            $result = registerDirectUser('admin', 'admin', null, '관리자', 'admin', $additional);
            if ($result['success'] ?? false) {
                $success = true;
            } else {
                $error = $result['message'] ?? '계정 생성에 실패했습니다.';
            }
        }
    } catch (Exception $e) {
        $error = '오류 발생: ' . $e->getMessage();
    }
}

// 직접 접근인 경우 자동 실행
if ($isDirectAccess && !$success && empty($error)) {
    // POST 요청으로 처리
    $_SERVER['REQUEST_METHOD'] = 'POST';
    // 위의 로직 재실행을 위해 리다이렉트
    header('Location: /MVNO/admin/settings/restore-admin.php?direct=yes&auto=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin 계정 복구</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-top: 0;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .info-box strong {
            display: block;
            margin-bottom: 8px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin 계정 복구</h1>
        
        <?php if ($success): ?>
        <div class="alert alert-success">
            <strong>✅ 성공!</strong> Admin 계정이 성공적으로 생성되었습니다.
        </div>
        <div class="info-box">
            <strong>계정 정보:</strong>
            <p>아이디: <strong>admin</strong></p>
            <p>비밀번호: <strong>admin</strong></p>
            <p style="color: #dc3545; font-weight: bold; margin-top: 10px;">
                ⚠️ 보안을 위해 로그인 후 비밀번호를 변경하세요!
            </p>
        </div>
        <div style="text-align: center; margin-top: 20px;">
            <a href="/MVNO/admin/login.php" class="btn">로그인 페이지로 이동</a>
        </div>
        <?php elseif ($error): ?>
        <div class="alert alert-error">
            <strong>❌ 오류:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
        <div style="text-align: center; margin-top: 20px;">
            <a href="/MVNO/admin/" class="btn">관리자 페이지로 이동</a>
        </div>
        <?php else: ?>
        <div class="info-box">
            <strong>⚠️ 주의사항</strong>
            <p>이 스크립트는 admin 계정을 복구합니다.</p>
            <p>생성되는 계정 정보:</p>
            <ul>
                <li>아이디: <strong>admin</strong></li>
                <li>비밀번호: <strong>admin</strong></li>
            </ul>
        </div>
        <form method="POST">
            <div style="text-align: center; margin-top: 20px;">
                <button type="submit" class="btn btn-danger">Admin 계정 생성</button>
                <a href="/MVNO/admin/" class="btn" style="margin-left: 10px;">취소</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>






















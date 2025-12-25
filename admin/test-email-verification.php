<?php
/**
 * 개발 환경용 이메일 인증번호 확인 페이지
 * 로그인한 사용자의 최근 인증번호를 확인할 수 있습니다.
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';

// 로그인 체크
if (!isLoggedIn()) {
    header('Location: /MVNO/?show_login=1');
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser) {
    die('사용자 정보를 찾을 수 없습니다.');
}

// 최근 인증번호 조회
$verifications = [];
try {
    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->prepare("
            SELECT id, email, verification_code, type, status, expires_at, verified_at, created_at
            FROM email_verifications
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([':user_id' => $currentUser['user_id']]);
        $verifications = $stmt->fetchAll();
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>이메일 인증번호 확인 (개발용)</title>
    <style>
        body {
            font-family: 'Malgun Gothic', Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f9fafb;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1f2937;
            margin-bottom: 10px;
        }
        .info {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #92400e;
        }
        .user-info {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        .code {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            color: #6366f1;
            letter-spacing: 3px;
        }
        .status-pending {
            color: #f59e0b;
            font-weight: 600;
        }
        .status-verified {
            color: #10b981;
            font-weight: 600;
        }
        .status-expired {
            color: #ef4444;
            font-weight: 600;
        }
        .empty {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #6366f1;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 이메일 인증번호 확인 (개발용)</h1>
        
        <div class="info">
            ⚠️ <strong>개발 환경 전용 페이지입니다.</strong><br>
            XAMPP 환경에서는 이메일 발송이 작동하지 않으므로, 여기서 인증번호를 확인하세요.
        </div>
        
        <div class="user-info">
            <strong>사용자:</strong> <?php echo htmlspecialchars($currentUser['user_id']); ?> 
            (<?php echo htmlspecialchars($currentUser['name'] ?? '-'); ?>)
        </div>
        
        <?php if (isset($error)): ?>
            <div style="background: #fee2e2; border: 1px solid #ef4444; border-radius: 8px; padding: 15px; color: #991b1b; margin-bottom: 20px;">
                오류: <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($verifications)): ?>
            <div class="empty">
                발송된 인증번호가 없습니다.
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>이메일</th>
                        <th>인증번호</th>
                        <th>타입</th>
                        <th>상태</th>
                        <th>만료 시간</th>
                        <th>생성 시간</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($verifications as $v): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($v['email']); ?></td>
                            <td>
                                <span class="code"><?php echo htmlspecialchars($v['verification_code']); ?></span>
                            </td>
                            <td>
                                <?php 
                                echo $v['type'] === 'email_change' ? '이메일 변경' : '비밀번호 변경';
                                ?>
                            </td>
                            <td>
                                <?php
                                $status = $v['status'];
                                $statusText = [
                                    'pending' => '대기중',
                                    'verified' => '인증완료',
                                    'expired' => '만료됨'
                                ];
                                $statusClass = 'status-' . $status;
                                echo '<span class="' . $statusClass . '">' . ($statusText[$status] ?? $status) . '</span>';
                                ?>
                            </td>
                            <td>
                                <?php 
                                $expiresAt = strtotime($v['expires_at']);
                                $now = time();
                                if ($expiresAt < $now) {
                                    echo '<span style="color: #ef4444;">만료됨</span>';
                                } else {
                                    $remaining = $expiresAt - $now;
                                    $minutes = floor($remaining / 60);
                                    echo $minutes . '분 남음';
                                }
                                ?>
                                <br>
                                <small style="color: #6b7280;">
                                    <?php echo date('Y-m-d H:i:s', $expiresAt); ?>
                                </small>
                            </td>
                            <td>
                                <?php echo date('Y-m-d H:i:s', strtotime($v['created_at'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <a href="/MVNO/mypage/account-management.php" class="back-link">← 계정 설정으로 돌아가기</a>
    </div>
</body>
</html>





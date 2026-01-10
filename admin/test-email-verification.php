<?php
/**
 * 이메일 인증번호 확인 페이지 (관리자용)
 * 이메일 발송 실패 시 임시로 인증번호를 확인할 수 있습니다.
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/path-config.php';

// 관리자 인증
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    header('Location: ' . getAssetPath('/admin/login.php'));
    exit;
}

require_once __DIR__ . '/includes/admin-header.php';

// DB에서 최근 인증번호 조회
$pdo = getDBConnection();
$recentVerifications = [];

if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                ev.id,
                ev.user_id,
                ev.email,
                ev.verification_code,
                ev.verification_token,
                ev.type,
                ev.status,
                ev.created_at,
                ev.expires_at,
                ev.verified_at,
                u.name as user_name,
                u.user_id as user_user_id
            FROM email_verifications ev
            LEFT JOIN users u ON ev.user_id = u.user_id
            ORDER BY ev.created_at DESC
            LIMIT 50
        ");
        $recentVerifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "데이터 조회 오류: " . $e->getMessage();
    }
}
?>

<style>
    .admin-container {
        margin-top: 80px;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
        padding: 24px;
    }
    
    .admin-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 24px;
    }
    
    h1 {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 24px;
        color: #1f2937;
    }
    
    .info-box {
        background: #eff6ff;
        border-left: 4px solid #3b82f6;
        padding: 16px;
        margin-bottom: 24px;
        border-radius: 4px;
    }
    
    .info-box p {
        margin: 4px 0;
        font-size: 14px;
        color: #1e40af;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 16px;
    }
    
    th, td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        font-size: 14px;
    }
    
    th {
        background: #f9fafb;
        font-weight: 600;
        color: #374151;
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
        color: #9ca3af;
    }
    
    .code-display {
        font-family: 'Courier New', monospace;
        font-size: 18px;
        font-weight: 700;
        color: #6366f1;
        letter-spacing: 3px;
        background: #f3f4f6;
        padding: 8px 12px;
        border-radius: 6px;
        display: inline-block;
    }
    
    .type-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .type-email_change {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .type-password_change {
        background: #fce7f3;
        color: #9f1239;
    }
</style>

<div class="admin-container">
    <h1>📧 이메일 인증번호 확인</h1>
    
    <div class="admin-card">
        <div class="info-box">
            <p><strong>⚠️ 주의사항</strong></p>
            <p>• 이 페이지는 관리자만 접근 가능합니다.</p>
            <p>• 이메일 발송 실패 시 임시로 인증번호를 확인할 수 있습니다.</p>
            <p>• 인증번호는 보안상 중요한 정보이므로 외부에 노출되지 않도록 주의하세요.</p>
            <p>• 만료된 인증번호는 사용할 수 없습니다.</p>
        </div>
        
        <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 16px;">최근 인증번호 (최대 50개)</h2>
        
        <?php if (!empty($recentVerifications)): ?>
            <table>
                <thead>
                    <tr>
                        <th>발송 시간</th>
                        <th>사용자</th>
                        <th>이메일</th>
                        <th>타입</th>
                        <th>인증번호</th>
                        <th>상태</th>
                        <th>만료 시간</th>
                        <th>인증 시간</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentVerifications as $verification): ?>
                        <?php
                        $isExpired = strtotime($verification['expires_at']) < time();
                        $statusClass = 'status-' . $verification['status'];
                        if ($isExpired && $verification['status'] === 'pending') {
                            $statusClass = 'status-expired';
                            $verification['status'] = 'expired';
                        }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($verification['created_at']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($verification['user_name'] ?? $verification['user_user_id'] ?? '-'); ?>
                                <br>
                                <small style="color: #9ca3af;">(<?php echo htmlspecialchars($verification['user_id']); ?>)</small>
                            </td>
                            <td><?php echo htmlspecialchars($verification['email']); ?></td>
                            <td>
                                <span class="type-badge type-<?php echo htmlspecialchars($verification['type']); ?>">
                                    <?php echo $verification['type'] === 'email_change' ? '이메일 변경' : '비밀번호 변경'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($verification['status'] === 'pending' && !$isExpired): ?>
                                    <span class="code-display"><?php echo htmlspecialchars($verification['verification_code']); ?></span>
                                <?php else: ?>
                                    <span style="color: #9ca3af;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="<?php echo $statusClass; ?>">
                                    <?php
                                    if ($verification['status'] === 'pending' && !$isExpired) {
                                        echo '대기중';
                                    } elseif ($verification['status'] === 'verified') {
                                        echo '인증완료';
                                    } else {
                                        echo '만료됨';
                                    }
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($verification['expires_at']); ?>
                                <?php if ($isExpired): ?>
                                    <br><small style="color: #ef4444;">(만료)</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $verification['verified_at'] ? htmlspecialchars($verification['verified_at']) : '-'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: #9ca3af; text-align: center; padding: 40px;">발송된 인증번호가 없습니다.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

<?php
/**
 * 판매자 승인 대기 페이지
 * 경로: /MVNO/seller/waiting.php
 */

require_once __DIR__ . '/../includes/data/path-config.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = getCurrentUser();

// 판매자 로그인 체크
if (!$currentUser || $currentUser['role'] !== 'seller') {
    header('Location: ' . getAssetPath('/seller/login.php'));
    exit;
}

// 이미 승인된 경우 판매자 센터로 리다이렉트
$isApproved = isset($currentUser['seller_approved']) && $currentUser['seller_approved'] === true;
if ($isApproved) {
    header('Location: ' . getBasePath() . '/seller/');
    exit;
}

$approvalStatus = $currentUser['approval_status'] ?? 'pending';
$isApproved = isset($currentUser['seller_approved']) && $currentUser['seller_approved'] === true;
$isWithdrawalRequested = isset($currentUser['withdrawal_requested']) && $currentUser['withdrawal_requested'] === true;

// 상태 메시지 설정 (모두 "승인 대기중"으로 통일)
$statusText = '승인 대기 중입니다';
$statusDescription = '관리자 승인 후 상품 등록 및 판매가 가능합니다.<br>승인까지 시간이 걸릴 수 있습니다.';

// 상품 및 주문 확인 (간단한 체크 - 실제 데이터 구조에 맞게 수정 필요)
$hasProducts = false;
$hasOrders = false;
$canWithdraw = true; // 탈퇴 가능 여부

// TODO: 실제 상품 데이터와 주문 데이터를 확인하는 로직 추가
// 예: 상품이 있고, 그 상품에 주문이 있는 경우 탈퇴 불가
// if ($hasProducts && $hasOrders) {
//     $canWithdraw = false;
//     $statusDescription = '승인 보류 상태입니다.<br>등록하신 상품에 신청내역이 있어 탈퇴할 수 없습니다.<br>회원탈퇴는 1년 후 관리자가 처리합니다.';
// }

if ($isWithdrawalRequested) {
    $statusText = '탈퇴 요청이 접수되었습니다';
    $statusDescription = '판매자 계정 탈퇴 요청이 접수되었습니다.<br>관리자 검토 후 처리됩니다.<br>탈퇴 처리 전까지 상품 및 거래 정보는 보존됩니다.';
    $canWithdraw = false;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>승인 대기 - 판매자 센터</title>
    <link rel="stylesheet" href="<?php echo getAssetPath('/assets/css/style.css'); ?>">
    <script src="<?php echo getAssetPath('/assets/js/modal.js'); ?>" defer></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .waiting-container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 16px;
            padding: 48px 32px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .waiting-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: #fef3c7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .waiting-icon svg {
            width: 48px;
            height: 48px;
            color: #f59e0b;
        }
        
        .waiting-title {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 12px;
        }
        
        .waiting-description {
            font-size: 16px;
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        
        .user-info {
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 32px;
            text-align: left;
        }
        
        .user-info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .user-info-item:last-child {
            border-bottom: none;
        }
        
        .user-info-label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .user-info-value {
            font-size: 14px;
            color: #1f2937;
            font-weight: 600;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 8px;
        }
        
        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-badge.on-hold {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-badge.rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: #6366f1;
            color: white;
        }
        
        .btn-primary:hover {
            background: #4f46e5;
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .logout-link {
            margin-top: 24px;
            font-size: 14px;
            color: #6b7280;
        }
        
        .logout-link a {
            color: #6366f1;
            text-decoration: none;
        }
        
        .logout-link a:hover {
            text-decoration: underline;
        }
        
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal {
            background: white;
            border-radius: 12px;
            padding: 24px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
        }
        
        .modal-message {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 24px;
            line-height: 1.6;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        .modal-btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        
        .modal-btn-cancel {
            background: #f3f4f6;
            color: #374151;
        }
        
        .modal-btn-cancel:hover {
            background: #e5e7eb;
        }
        
        .modal-btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .modal-btn-danger:hover {
            background: #dc2626;
        }
    </style>
</head>
<body>
    <div class="waiting-container">
        <div class="waiting-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        </div>
        
        <h1 class="waiting-title"><?php echo htmlspecialchars($statusText); ?></h1>
        <p class="waiting-description"><?php echo $statusDescription; ?></p>
        
        <div class="user-info">
            <div class="user-info-item">
                <span class="user-info-label">아이디</span>
                <span class="user-info-value"><?php echo htmlspecialchars($currentUser['user_id'] ?? ''); ?></span>
            </div>
            <div class="user-info-item">
                <span class="user-info-label">이름</span>
                <span class="user-info-value"><?php echo htmlspecialchars($currentUser['name'] ?? ''); ?></span>
            </div>
            <div class="user-info-item">
                <span class="user-info-label">이메일</span>
                <span class="user-info-value"><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></span>
            </div>
            <div class="user-info-item">
                <span class="user-info-label">상태</span>
                <span class="user-info-value">
                    <span class="status-badge <?php 
                        if ($isWithdrawalRequested) echo 'on-hold';
                        else echo 'on-hold';
                    ?>">
                        <?php 
                        if ($isWithdrawalRequested) {
                            echo '탈퇴 요청';
                        } else {
                            echo '승인보류';
                        }
                        ?>
                    </span>
                </span>
            </div>
            <?php if ($isWithdrawalRequested && isset($currentUser['withdrawal_requested_at'])): ?>
                <div class="user-info-item">
                    <span class="user-info-label">탈퇴 요청일</span>
                    <span class="user-info-value"><?php echo htmlspecialchars($currentUser['withdrawal_requested_at']); ?></span>
                </div>
                <?php if (isset($currentUser['withdrawal_reason']) && !empty($currentUser['withdrawal_reason'])): ?>
                    <div class="user-info-item">
                        <span class="user-info-label">탈퇴 사유</span>
                        <span class="user-info-value"><?php echo htmlspecialchars($currentUser['withdrawal_reason']); ?></span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="action-buttons">
            <?php if ($isWithdrawalRequested): ?>
                <a href="<?php echo getBasePath(); ?>/" class="btn btn-secondary">홈으로 가기</a>
                <button onclick="location.reload()" class="btn btn-primary">새로고침</button>
            <?php else: ?>
                <a href="<?php echo getBasePath(); ?>/" class="btn btn-secondary">홈으로 가기</a>
                <?php if ($canWithdraw): ?>
                    <button onclick="showDeleteAccountModal()" class="btn btn-danger">탈퇴</button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="logout-link">
            <a href="<?php echo getAssetPath('/seller/logout.php'); ?>">로그아웃</a>
        </div>
    </div>
    
    <!-- 가입정보 삭제 확인 모달 -->
    <div class="modal-overlay" id="deleteAccountModal">
        <div class="modal">
            <div class="modal-title">판매자 계정 탈퇴 확인</div>
            <div class="modal-message">
                정말로 판매자 계정을 탈퇴하시겠습니까?<br>
                <strong style="color: #ef4444;">이 작업은 되돌릴 수 없으며, 모든 판매자 정보가 영구적으로 삭제됩니다.</strong><br>
                승인 대기 중인 상태에서 탈퇴하시면 다시 가입하셔야 합니다.
            </div>
            <div class="modal-actions">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeDeleteAccountModal()">취소</button>
                <button type="button" class="modal-btn modal-btn-danger" onclick="deleteAccount()">탈퇴</button>
            </div>
        </div>
    </div>
    
    <script>
        function showDeleteAccountModal() {
            document.getElementById('deleteAccountModal').classList.add('active');
        }
        
        function closeDeleteAccountModal() {
            document.getElementById('deleteAccountModal').classList.remove('active');
        }
        
        function deleteAccount() {
            const confirmMessage = '정말로 판매자 계정을 탈퇴하시겠습니까?\n이 작업은 되돌릴 수 없습니다.';
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            fetch('<?php echo getApiPath('/api/delete-seller-account.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    confirm: true
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('판매자 계정이 탈퇴되었습니다.');
                    window.location.href = '<?php echo getBasePath(); ?>/';
                } else {
                    alert('탈퇴에 실패했습니다: ' + (data.message || '알 수 없는 오류'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('탈퇴 중 오류가 발생했습니다.');
            });
        }
        
        // 모달 외부 클릭 시 닫기
        document.getElementById('deleteAccountModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteAccountModal();
            }
        });
    </script>
</body>
</html>


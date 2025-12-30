<?php
/**
 * 입찰 상세 페이지 (판매자)
 * 경로: /seller/bidding/detail.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = getCurrentUser();

// 판매자 로그인 체크
if (!$currentUser || $currentUser['role'] !== 'seller') {
    header('Location: /MVNO/seller/login.php');
    exit;
}

// 판매자 승인 체크
$approvalStatus = $currentUser['approval_status'] ?? 'pending';
if ($approvalStatus !== 'approved') {
    header('Location: /MVNO/seller/waiting.php');
    exit;
}

$pdo = getDBConnection();
$error = '';
$success = '';
$participationId = intval($_GET['id'] ?? 0);
$participation = null;
$round = null;
$sellerId = (string)$currentUser['user_id'];

// 입찰 정보 조회
if ($participationId > 0) {
    try {
        if ($pdo) {
            $stmt = $pdo->prepare("
                SELECT 
                    bp.*,
                    br.*
                FROM bidding_participations bp
                INNER JOIN bidding_rounds br ON bp.bidding_round_id = br.id
                WHERE bp.id = :id AND bp.seller_id = :seller_id
            ");
            $stmt->execute([':id' => $participationId, ':seller_id' => $sellerId]);
            $participation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($participation) {
                $round = $participation;
            }
        }
    } catch (PDOException $e) {
        $error = "정보를 불러오는 중 오류가 발생했습니다: " . $e->getMessage();
        error_log("Bidding detail load error: " . $e->getMessage());
    }
}

// POST 처리: 입찰 취소
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    if (!$participation) {
        $error = '입찰 정보를 찾을 수 없습니다.';
    } elseif ($participation['status'] !== 'pending') {
        $error = '취소 가능한 상태가 아닙니다. (대기 상태만 취소 가능)';
    } elseif ($round['status'] !== 'bidding') {
        $error = '입찰 기간이 종료되어 취소할 수 없습니다.';
    } else {
        try {
            if (!$pdo) {
                throw new Exception('DB 연결에 실패했습니다.');
            }
            
            $now = date('Y-m-d H:i:s');
            if (strtotime($round['bidding_end_at']) < strtotime($now)) {
                throw new Exception('입찰 기간이 종료되어 취소할 수 없습니다.');
            }
            
            $pdo->beginTransaction();
            
            // 예치금 환불
            $refundAmount = $participation['deposit_used'];
            $depStmt = $pdo->prepare("SELECT balance FROM seller_deposits WHERE seller_id = :seller_id FOR UPDATE");
            $depStmt->execute([':seller_id' => $sellerId]);
            $deposit = $depStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$deposit) {
                // 예치금 계정이 없으면 생성
                $insStmt = $pdo->prepare("INSERT INTO seller_deposits (seller_id, balance) VALUES (:seller_id, 0)");
                $insStmt->execute([':seller_id' => $sellerId]);
                $deposit = ['balance' => 0];
            }
            
            $newBalance = $deposit['balance'] + $refundAmount;
            $updateStmt = $pdo->prepare("UPDATE seller_deposits SET balance = :balance WHERE seller_id = :seller_id");
            $updateStmt->execute([':balance' => $newBalance, ':seller_id' => $sellerId]);
            
            // 거래 내역 기록
            $transStmt = $pdo->prepare("
                INSERT INTO seller_deposit_transactions (
                    seller_id, transaction_type, amount, balance_before, balance_after,
                    reference_id, reference_type, description
                ) VALUES (
                    :seller_id, 'refund', :amount, :balance_before, :balance_after,
                    :reference_id, :reference_type, :description
                )
            ");
            $transStmt->execute([
                ':seller_id' => $sellerId,
                ':amount' => $refundAmount,
                ':balance_before' => $deposit['balance'],
                ':balance_after' => $newBalance,
                ':reference_id' => $participationId,
                ':reference_type' => 'bidding_participation',
                ':description' => '입찰 취소 환불'
            ]);
            
            // 입찰 상태 변경
            $cancelStmt = $pdo->prepare("
                UPDATE bidding_participations 
                SET status = 'cancelled', cancelled_at = :cancelled_at, deposit_refunded = :deposit_refunded
                WHERE id = :id
            ");
            $cancelStmt->execute([
                ':cancelled_at' => $now,
                ':deposit_refunded' => $refundAmount,
                ':id' => $participationId
            ]);
            
            $pdo->commit();
            
            // 성공 시 목록으로 리다이렉트
            header('Location: /MVNO/seller/bidding/list.php?success=cancelled');
            exit;
            
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = '입찰 취소 중 오류가 발생했습니다: ' . $e->getMessage();
            error_log("Bidding cancel error: " . $e->getMessage());
        } catch (Exception $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/seller-header.php';

// 카테고리 라벨
$categoryLabels = [
    'mno' => '통신사폰',
    'mvno' => '알뜰폰',
    'mno_sim' => '통신사단독유심'
];

// 상태 라벨
$statusLabels = [
    'pending' => '대기',
    'won' => '낙찰',
    'lost' => '낙찰실패',
    'cancelled' => '취소'
];

if (!$participation) {
    $error = '입찰 정보를 찾을 수 없습니다.';
}
?>

<style>
    .page-header {
        margin-bottom: 32px;
    }
    
    .page-title {
        font-size: 32px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
    }
    
    .page-description {
        color: #64748b;
        font-size: 15px;
    }
    
    .btn-secondary {
        background: #f3f4f6;
        color: #374151;
        padding: 12px 24px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 24px;
    }
    
    .btn-secondary:hover {
        background: #e5e7eb;
    }
    
    .content-card {
        background: white;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        margin-bottom: 24px;
    }
    
    .section-title {
        font-size: 20px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 24px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .info-row {
        display: flex;
        padding: 16px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .info-row:last-child {
        border-bottom: none;
    }
    
    .info-label {
        width: 150px;
        font-weight: 600;
        color: #64748b;
    }
    
    .info-value {
        flex: 1;
        color: #1e293b;
    }
    
    .badge {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-won {
        background: #d1fae5;
        color: #065f46;
    }
    
    .badge-lost {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .badge-pending {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .badge-cancelled {
        background: #f3f4f6;
        color: #6b7280;
    }
    
    .error-message {
        background: #fee2e2;
        color: #991b1b;
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        border-left: 4px solid #dc2626;
    }
    
    .success-message {
        background: #d1fae5;
        color: #065f46;
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        border-left: 4px solid #10b981;
    }
    
    .btn-danger {
        background: #ef4444;
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
    }
    
    .btn-danger:hover {
        background: #dc2626;
    }
    
    .btn-danger:disabled {
        background: #d1d5db;
        cursor: not-allowed;
    }
    
    .cancel-form {
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid #e5e7eb;
    }
    
    .warning-box {
        background: #fef3c7;
        color: #92400e;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 16px;
        border-left: 4px solid #f59e0b;
    }
</style>

<div class="page-header">
    <h1 class="page-title">입찰 상세</h1>
    <p class="page-description">입찰 참여 내역을 확인하고 관리할 수 있습니다.</p>
</div>

<a href="/MVNO/seller/bidding/list.php" class="btn-secondary">← 목록으로</a>

<?php if ($error): ?>
    <div class="error-message">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="success-message">
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<?php if ($participation && $round): ?>
    <div class="content-card">
        <h2 class="section-title">입찰 정보</h2>
        
        <div class="info-row">
            <div class="info-label">카테고리</div>
            <div class="info-value"><?php echo htmlspecialchars($categoryLabels[$round['category']] ?? $round['category']); ?></div>
        </div>
        
        <div class="info-row">
            <div class="info-label">입찰 금액</div>
            <div class="info-value"><strong style="font-size: 18px;"><?php echo number_format($participation['bid_amount']); ?>원</strong></div>
        </div>
        
        <div class="info-row">
            <div class="info-label">상태</div>
            <div class="info-value">
                <span class="badge badge-<?php echo $participation['status']; ?>">
                    <?php echo htmlspecialchars($statusLabels[$participation['status']] ?? $participation['status']); ?>
                </span>
            </div>
        </div>
        
        <div class="info-row">
            <div class="info-label">순위</div>
            <div class="info-value"><?php echo $participation['rank'] ?? '미정'; ?></div>
        </div>
        
        <div class="info-row">
            <div class="info-label">입찰일시</div>
            <div class="info-value"><?php echo date('Y-m-d H:i:s', strtotime($participation['bid_at'])); ?></div>
        </div>
        
        <?php if ($participation['cancelled_at']): ?>
            <div class="info-row">
                <div class="info-label">취소일시</div>
                <div class="info-value"><?php echo date('Y-m-d H:i:s', strtotime($participation['cancelled_at'])); ?></div>
            </div>
        <?php endif; ?>
        
        <?php if ($participation['won_at']): ?>
            <div class="info-row">
                <div class="info-label">낙찰일시</div>
                <div class="info-value"><?php echo date('Y-m-d H:i:s', strtotime($participation['won_at'])); ?></div>
            </div>
        <?php endif; ?>
        
        <div class="info-row">
            <div class="info-label">사용된 예치금</div>
            <div class="info-value"><?php echo number_format($participation['deposit_used']); ?>원</div>
        </div>
        
        <?php if ($participation['deposit_refunded'] > 0): ?>
            <div class="info-row">
                <div class="info-label">환불된 예치금</div>
                <div class="info-value"><?php echo number_format($participation['deposit_refunded']); ?>원</div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="content-card">
        <h2 class="section-title">입찰 라운드 정보</h2>
        
        <div class="info-row">
            <div class="info-label">입찰 기간</div>
            <div class="info-value">
                <?php echo date('Y-m-d H:i', strtotime($round['bidding_start_at'])); ?> ~ 
                <?php echo date('Y-m-d H:i', strtotime($round['bidding_end_at'])); ?>
            </div>
        </div>
        
        <div class="info-row">
            <div class="info-label">게시 기간</div>
            <div class="info-value">
                <?php echo date('Y-m-d', strtotime($round['display_start_at'])); ?> ~ 
                <?php echo date('Y-m-d', strtotime($round['display_end_at'])); ?>
            </div>
        </div>
        
        <div class="info-row">
            <div class="info-label">입찰 금액 범위</div>
            <div class="info-value">
                <?php echo number_format($round['min_bid_amount']); ?>원 ~ <?php echo number_format($round['max_bid_amount']); ?>원
            </div>
        </div>
        
        <div class="info-row">
            <div class="info-label">운용 방식</div>
            <div class="info-value">
                <?php echo $round['rotation_type'] === 'fixed' ? '고정' : '순환 (' . $round['rotation_interval_minutes'] . '분)'; ?>
            </div>
        </div>
    </div>
    
    <?php if ($participation['status'] === 'pending' && $round['status'] === 'bidding'): ?>
        <div class="content-card">
            <h2 class="section-title">입찰 취소</h2>
            <div class="warning-box">
                <strong>주의:</strong> 입찰을 취소하면 예치금이 환불되지만, 같은 라운드에 다시 입찰할 수 있습니다.
            </div>
            <form method="POST" action="" class="cancel-form" onsubmit="return confirm('정말 입찰을 취소하시겠습니까? 예치금이 환불됩니다.');">
                <input type="hidden" name="action" value="cancel">
                <button type="submit" class="btn-danger">입찰 취소</button>
            </form>
        </div>
    <?php endif; ?>
    
<?php else: ?>
    <div class="content-card">
        <p style="color: #991b1b;">입찰 정보를 찾을 수 없습니다.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/seller-footer.php'; ?>


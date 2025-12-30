<?php
/**
 * 입찰 참여 페이지 (판매자)
 * 경로: /seller/bidding/participate.php
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

// 탈퇴 요청 상태 확인
if (isset($currentUser['withdrawal_requested']) && $currentUser['withdrawal_requested'] === true) {
    header('Location: /MVNO/seller/waiting.php');
    exit;
}

$pdo = getDBConnection();
$error = '';
$success = '';
$roundId = $_GET['round_id'] ?? null;
$round = null;
$sellerId = (string)$currentUser['user_id'];
$existingParticipation = null;
$deposit = null;

// 라운드 정보 조회
if ($roundId) {
    try {
        if ($pdo) {
            // 라운드 정보
            $stmt = $pdo->prepare("SELECT * FROM bidding_rounds WHERE id = :id");
            $stmt->execute([':id' => $roundId]);
            $round = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($round) {
                // 기존 참여 정보 확인
                $partStmt = $pdo->prepare("SELECT * FROM bidding_participations WHERE bidding_round_id = :round_id AND seller_id = :seller_id");
                $partStmt->execute([':round_id' => $roundId, ':seller_id' => $sellerId]);
                $existingParticipation = $partStmt->fetch(PDO::FETCH_ASSOC);
                
                // 예치금 조회
                $depStmt = $pdo->prepare("SELECT * FROM seller_deposits WHERE seller_id = :seller_id");
                $depStmt->execute([':seller_id' => $sellerId]);
                $deposit = $depStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$deposit) {
                    // 예치금 계정이 없으면 생성
                    $insStmt = $pdo->prepare("INSERT INTO seller_deposits (seller_id, balance) VALUES (:seller_id, 0)");
                    $insStmt->execute([':seller_id' => $sellerId]);
                    $deposit = ['balance' => 0];
                }
            }
        }
    } catch (PDOException $e) {
        $error = "정보를 불러오는 중 오류가 발생했습니다: " . $e->getMessage();
        error_log("Bidding participate load error: " . $e->getMessage());
    }
}

// POST 처리: 입찰 참여
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'participate') {
    $bidAmount = floatval($_POST['bid_amount'] ?? 0);
    $postRoundId = intval($_POST['round_id'] ?? 0);
    
    if (!$round || $round['id'] != $postRoundId) {
        $error = '잘못된 입찰 라운드입니다.';
    } elseif ($existingParticipation) {
        $error = '이미 입찰에 참여하셨습니다.';
    } elseif ($bidAmount < $round['min_bid_amount']) {
        $error = '최소 입찰금액은 ' . number_format($round['min_bid_amount']) . '원입니다.';
    } elseif ($bidAmount > $round['max_bid_amount']) {
        $error = '최대 입찰금액은 ' . number_format($round['max_bid_amount']) . '원입니다.';
    } elseif ($deposit['balance'] < $bidAmount) {
        $error = '예치금이 부족합니다. 현재 예치금: ' . number_format($deposit['balance']) . '원';
    } elseif ($round['status'] !== 'bidding') {
        $error = '현재 입찰 중인 라운드가 아닙니다.';
    } else {
        try {
            if (!$pdo) {
                throw new Exception('DB 연결에 실패했습니다.');
            }
            
            $pdo->beginTransaction();
            
            // 예치금 재확인 및 차감 (FOR UPDATE로 잠금)
            $lockStmt = $pdo->prepare("SELECT balance FROM seller_deposits WHERE seller_id = :seller_id FOR UPDATE");
            $lockStmt->execute([':seller_id' => $sellerId]);
            $lockedDeposit = $lockStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$lockedDeposit || $lockedDeposit['balance'] < $bidAmount) {
                throw new Exception('예치금이 부족합니다.');
            }
            
            // 입찰 참여 등록
            $partStmt = $pdo->prepare("
                INSERT INTO bidding_participations (
                    bidding_round_id, seller_id, bid_amount, deposit_used, status
                ) VALUES (
                    :round_id, :seller_id, :bid_amount, :deposit_used, 'pending'
                )
            ");
            $partStmt->execute([
                ':round_id' => $roundId,
                ':seller_id' => $sellerId,
                ':bid_amount' => $bidAmount,
                ':deposit_used' => $bidAmount
            ]);
            
            $participationId = $pdo->lastInsertId();
            
            // 예치금 차감
            $newBalance = $lockedDeposit['balance'] - $bidAmount;
            $updateStmt = $pdo->prepare("UPDATE seller_deposits SET balance = :balance WHERE seller_id = :seller_id");
            $updateStmt->execute([':balance' => $newBalance, ':seller_id' => $sellerId]);
            
            // 거래 내역 기록
            $transStmt = $pdo->prepare("
                INSERT INTO seller_deposit_transactions (
                    seller_id, transaction_type, amount, balance_before, balance_after,
                    reference_id, reference_type, description
                ) VALUES (
                    :seller_id, 'bid_deduction', :amount, :balance_before, :balance_after,
                    :reference_id, :reference_type, :description
                )
            ");
            $transStmt->execute([
                ':seller_id' => $sellerId,
                ':amount' => -$bidAmount,
                ':balance_before' => $lockedDeposit['balance'],
                ':balance_after' => $newBalance,
                ':reference_id' => $participationId,
                ':reference_type' => 'bidding_participation',
                ':description' => '입찰 참여: ' . ($round['category'] === 'mno' ? '통신사폰' : ($round['category'] === 'mvno' ? '알뜰폰' : '통신사단독유심'))
            ]);
            
            $pdo->commit();
            
            // 성공 시 목록으로 리다이렉트
            header('Location: /MVNO/seller/bidding/list.php?success=participated');
            exit;
            
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = '입찰 참여 중 오류가 발생했습니다: ' . $e->getMessage();
            error_log("Bidding participate error: " . $e->getMessage());
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
    'upcoming' => '예정',
    'bidding' => '입찰중',
    'closed' => '마감',
    'displaying' => '게시중',
    'finished' => '종료'
];

if (!$round) {
    $error = '입찰 라운드를 찾을 수 없습니다.';
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
    
    .info-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        margin-bottom: 24px;
    }
    
    .info-title {
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .info-row {
        display: flex;
        padding: 12px 0;
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
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-bidding {
        background: #fef3c7;
        color: #92400e;
    }
    
    .form-container {
        background: white;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    }
    
    .form-group {
        margin-bottom: 24px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #374151;
        font-size: 14px;
    }
    
    .form-label .required {
        color: #ef4444;
        margin-left: 4px;
    }
    
    .form-input {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.2s;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .form-help {
        display: block;
        margin-top: 6px;
        font-size: 12px;
        color: #6b7280;
    }
    
    .form-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid #e5e7eb;
    }
    
    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    
    .btn-secondary {
        background: #f3f4f6;
        color: #374151;
    }
    
    .btn-secondary:hover {
        background: #e5e7eb;
    }
    
    .error-message {
        background: #fee2e2;
        color: #991b1b;
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        border-left: 4px solid #dc2626;
    }
    
    .warning-message {
        background: #fef3c7;
        color: #92400e;
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        border-left: 4px solid #f59e0b;
    }
    
    .balance-info {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 24px;
        text-align: center;
    }
    
    .balance-amount {
        font-size: 32px;
        font-weight: 700;
        margin-top: 8px;
    }
</style>

<div class="page-header">
    <h1 class="page-title">입찰 참여</h1>
    <p class="page-description">입찰 라운드에 참여합니다.</p>
</div>

<?php if ($error): ?>
    <div class="error-message">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if (!$round): ?>
    <div class="error-message">
        입찰 라운드를 찾을 수 없습니다.
    </div>
<?php elseif ($existingParticipation): ?>
    <div class="warning-message">
        이미 이 입찰에 참여하셨습니다.
        <a href="/MVNO/seller/bidding/detail.php?id=<?php echo $existingParticipation['id']; ?>" style="color: #92400e; text-decoration: underline; margin-left: 8px;">
            참여 내역 보기
        </a>
    </div>
<?php elseif ($round['status'] !== 'bidding'): ?>
    <div class="warning-message">
        현재 입찰 중인 라운드가 아닙니다. (상태: <?php echo htmlspecialchars($statusLabels[$round['status']] ?? $round['status']); ?>)
    </div>
<?php else: ?>
    
    <?php if ($deposit): ?>
        <div class="balance-info">
            <div>현재 예치금</div>
            <div class="balance-amount"><?php echo number_format($deposit['balance']); ?>원</div>
        </div>
    <?php endif; ?>
    
    <div class="info-card">
        <h2 class="info-title">입찰 라운드 정보</h2>
        <div class="info-row">
            <div class="info-label">카테고리</div>
            <div class="info-value"><?php echo htmlspecialchars($categoryLabels[$round['category']] ?? $round['category']); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">상태</div>
            <div class="info-value">
                <span class="badge badge-<?php echo $round['status']; ?>">
                    <?php echo htmlspecialchars($statusLabels[$round['status']] ?? $round['status']); ?>
                </span>
            </div>
        </div>
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
    
    <div class="form-container">
        <form method="POST" action="" id="participateForm">
            <input type="hidden" name="action" value="participate">
            <input type="hidden" name="round_id" value="<?php echo $round['id']; ?>">
            
            <div class="form-group">
                <label class="form-label">
                    입찰 금액 <span class="required">*</span>
                </label>
                <input 
                    type="number" 
                    name="bid_amount" 
                    class="form-input" 
                    min="<?php echo $round['min_bid_amount']; ?>"
                    max="<?php echo $round['max_bid_amount']; ?>"
                    step="1000"
                    value="<?php echo $round['min_bid_amount']; ?>"
                    required
                >
                <small class="form-help">
                    최소: <?php echo number_format($round['min_bid_amount']); ?>원, 
                    최대: <?php echo number_format($round['max_bid_amount']); ?>원
                </small>
            </div>
            
            <?php if ($deposit && $deposit['balance'] < $round['min_bid_amount']): ?>
                <div class="error-message">
                    예치금이 부족합니다. 예치금 관리 페이지에서 예치금을 충전해주세요.
                    <a href="/MVNO/seller/bidding/deposits.php" style="color: #991b1b; text-decoration: underline; margin-left: 8px;">
                        예치금 관리
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="form-actions">
                <a href="/MVNO/seller/bidding/list.php" class="btn btn-secondary">취소</a>
                <button type="submit" class="btn btn-primary" <?php echo ($deposit && $deposit['balance'] < $round['min_bid_amount']) ? 'disabled' : ''; ?>>
                    입찰 참여
                </button>
            </div>
        </form>
    </div>
    
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/seller-footer.php'; ?>

